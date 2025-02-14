"use strict";
import {Type, Event, Loc, Dom, Browser, Tag, Runtime} from 'main.core';
import {SliderDateTimeControl} from './sliderdatetimecontrol.js';
import {SectionSelector, Reminder, ColorSelector, Location, RepeatSelector, BusyUsersDialog} from 'calendar.controls';
import {Util} from 'calendar.util';
import {Entry, EntryManager} from "calendar.entry";
import {SectionManager} from "calendar.sectionmanager";
import {EventEmitter, BaseEvent} from 'main.core.events';
import {Planner} from "calendar.planner";
import {TagSelector as EntityTagSelector} from 'ui.entity-selector';
import {MobileSyncBanner} from 'calendar.sync.interface';

export class EventEditForm
{
	DOM = {};
	uid = null;
	sliderId = "calendar:edit-entry-slider";
	zIndex = 3100;
	denyClose = false;
	formType = 'slider_main';
	STATE = {READY: 1, REQUEST: 2, ERROR: 3};
	sections = [];
	sectionIndex = {};
	trackingUsersList = [];
	userSettings = {};

	constructor(options = {})
	{
		this.name = options.name || 'eventeditform';
		this.type = options.type || 'user';
		this.userId = options.userId || parseInt(Loc.getMessage('USER_ID'));
		this.ownerId = options.ownerId;
		this.entryId = parseInt(options.entryId) || null;
		this.entry = options.entry || null;
		this.formDataValue = options.formDataValue || {};
		this.emitter = new EventEmitter();
		this.emitter.setEventNamespace('BX.Calendar.EventEditForm');
		this.BX = Util.getBX();

		this.formSettings = {
			pinnedFields : {}
		};
		if (!this.ownerId && this.type === 'user')
		{
			this.ownerId = this.userId;
		}

		if (Type.isDate(options.entryDateFrom) && !this.formDataValue.from)
		{
			this.formDataValue.from = options.entryDateFrom;
			this.formDataValue.to = new Date(options.entryDateFrom.getTime() + 3600);
		}

		this.organizerId = Type.isInteger(parseInt(options.organizerId))
			? parseInt(options.organizerId)
			: this.userId;

		this.participantsEntityList = Type.isArray(options.participantsEntityList)
			? options.participantsEntityList
			: [];

		if (options.entryName && !this.entryId)
		{
			this.formDataValue.name = options.entryName;
		}
		if (options.entryDescription && !this.entryId)
		{
			this.formDataValue.description = options.entryDescription;
		}

		this.refreshPlanner = Runtime.debounce(this.refreshPlannerState, 100, this);
		this.state = this.STATE.READY;
		this.sliderOnClose = this.hide.bind(this);
		this.handlePullBind = this.handlePull.bind(this);
		this.keyHandlerBind = this.keyHandler.bind(this);
	}

	initInSlider(slider, promiseResolve)
	{
		this.sliderId = slider.getUrl();
		this.BX.addCustomEvent(slider, "SidePanel.Slider:onLoad", this.onLoadSlider.bind(this));
		this.BX.addCustomEvent(slider, "SidePanel.Slider:onClose", this.sliderOnClose);
		this.BX.addCustomEvent(slider, "SidePanel.Slider:onBeforeCloseComplete", this.destroy.bind(this));
		this.setCurrentEntry(this.entry || null);

		this.createContent(slider).then(function(html)
			{
				if (Type.isFunction(promiseResolve))
				{
					promiseResolve(html);
				}
			}.bind(this)
		);

		this.opened = true;
		this.bindEventHandlers();
	}

	show(params = {})
	{
		this.setCurrentEntry(params.entry);
		if (params.formType)
		{
			this.formType = params.formType;
		}

		this.BX.SidePanel.Instance.open(this.sliderId, {
			contentCallback: this.createContent.bind(this),
			label: {
				text: Loc.getMessage('CALENDAR_EVENT'),
				bgColor: "#55D0E0"
			},
			events: {
				onClose: this.sliderOnClose,
				onCloseComplete: this.destroy.bind(this),
				onLoad: this.onLoadSlider.bind(this)
			}
		});

		this.opened = true;
		this.bindEventHandlers();
	}

	isOpened()
	{
		return this.opened;
	}

	bindEventHandlers()
	{
		Event.bind(document, 'keydown', this.keyHandlerBind);
		EventEmitter.subscribe('onPullEvent-calendar', this.handlePullBind);

		// region 'protection from closing slider by accident'
		this.mouseUpNodeCheck = null;

		Event.bind(document, 'mousedown', (e)=>{this.mousedownTarget = e.target || e.srcElement;});
		Event.bind(document, 'mouseup', (e)=>{
			let target = e.target || e.srcElement;
			if (this.mousedownTarget !== target)
			{
				this.mouseUpNodeCheck = false;
			}
			setTimeout(()=>{this.mouseUpNodeCheck = null;}, 0);
		});
		// endregion

		this.BX.addCustomEvent(window, "onCalendarControlChildPopupShown", this.BX.proxy(this.denySliderClose, this));
		this.BX.addCustomEvent(window, "onCalendarControlChildPopupClosed", this.BX.proxy(this.allowSliderClose, this));
	}

	onLoadSlider(event)
	{
		this.slider = event.getSlider();
		this.DOM.content = this.slider.layout.content;
		this.sliderId = this.slider.getUrl();

		// Used to execute javasctipt and attach CSS from ajax responce
		this.BX.html(this.slider.layout.content, this.slider.getData().get("sliderContent"));
		this.initControls(this.uid);
		this.setFormValues();
	}

	close()
	{
		if (!this.checkDenyClose())
		{
			this.state = this.STATE.READY;
			this.BX.SidePanel.Instance.close();
		}
	}

	save(options = {})
	{
		if (this.state === this.STATE.REQUEST)
		{
			return false;
		}

		options = Type.isPlainObject(options) ? options : {};

		if (!this.entry.id
			&& this.hasExternalEmailUsers()
			&& Util.checkEmailLimitationPopup()
			&& !options.emailLimitationDialogShown)
		{
			EntryManager.showEmailLimitationDialog({
				callback: () => {
					options.emailLimitationDialogShown = true;
					this.save(options);
				}
			});
			return false;
		}

		if (!this.userSettings.sendFromEmail && this.hasExternalEmailUsers())
		{
			EntryManager.showConfirmedEmailDialog({
				callback: (params) => {
					if (params.sendFromEmail)
					{
						this.userSettings.sendFromEmail = params.sendFromEmail;
					}
					this.save(options);
				}
			});
			return false;
		}

		if (this.entry.id && this.entry.isRecursive()
			&& !options.confirmed
			&& this.getFormDataChanges(['section', 'notify']).length > 0)
		{
			EntryManager.showConfirmEditDialog({
				callback: (params) => {
					this.save({
						recursionMode: params.recursionMode,
						confirmed: true
					});
				}
			});
			return false;
		}

		if (this.entry.id
			&& this.entry.isMeeting()
			&& options.sendInvitesAgain === undefined
			&& this.getFormDataChanges().includes('date&time')
			&& this.entry.getAttendees().find((item) => {return item.STATUS === 'N';})
		)
		{
			EntryManager.showReInviteUsersDialog({
				callback: (params) => {
					options.sendInvitesAgain = params.sendInvitesAgain;
					this.save(options);
				}
			});
			return false;
		}

		Dom.addClass(this.DOM.saveBtn, this.BX.UI.Button.State.CLOCKING);
		Dom.addClass(this.DOM.closeBtn, this.BX.UI.Button.State.DISABLED);
		this.state = this.STATE.REQUEST;

		this.DOM.form.id.value = this.entry.id || 0;

		// Location
		this.DOM.form.location.value = this.locationSelector.getTextValue();

		if (this.editor)
		{
			this.editor.SaveContent();
		}

		let section = this.getCurrentSection();
		if (section)
		{
			// Color
			if (section.COLOR.toLowerCase() !== this.colorSelector.getValue().toLowerCase())
			{
				this.DOM.form.color.value = this.colorSelector.getValue();
			}
			// this.BX.userOptions.save('calendar', 'user_settings', 'lastUsedSection', parseInt(section.ID));
		}

		this.DOM.form.current_date_from.value = options.recursionMode ? Util.formatDate(this.entry.from) : '';
		this.DOM.form.rec_edit_mode.value = options.recursionMode || '';

		if (options.sendInvitesAgain !== undefined)
		{
			this.DOM.form.appendChild(Tag.render`<input name="sendInvitesAgain" type="hidden" value="${options.sendInvitesAgain ? 'Y' : 'N'}">`);
		}

		if (!this.DOM.form.requestUid)
		{
			this.DOM.requestUid = this.DOM.form.appendChild(
				Tag.render`<input name="requestUid" type="hidden">`
			);
		}
		this.DOM.requestUid.value = Util.registerRequestId();

		// Save attendees from userSelector
		Dom.clean(this.DOM.userSelectorValueWarp);
		this.getUserSelectorEntityList().forEach((entity, index) => {
			this.DOM.userSelectorValueWarp.appendChild(Tag.render`
				<input type="hidden" name="attendeesEntityList[${index}][entityId]" value="${entity.entityId}">
			`);
			this.DOM.userSelectorValueWarp.appendChild(Tag.render`
				<input type="hidden" name="attendeesEntityList[${index}][id]" value="${entity.id}">
			`);
		});

		this.BX.ajax.runAction('calendar.api.calendarajax.editEntry', {
			data: new FormData(this.DOM.form),
			analyticsLabel: {
				calendarAction: this.entry.id ? 'edit_event' : 'create_event',
				formType: 'full',
				emailGuests: this.hasExternalEmailUsers() ? 'Y' : 'N',
				markView: Util.getCurrentView() || 'outside',
				markCrm: this.DOM.form['UF_CRM_CAL_EVENT[]'] && this.DOM.form['UF_CRM_CAL_EVENT[]'].value ? 'Y' : 'N',
				markRrule: this.repeatSelector.getType(),
				markMeeting: this.entry.isMeeting() ? 'Y' : 'N',
				markType: this.type
			}
		}).then((response) => {
				this.state = this.STATE.READY;
				this.allowSliderClose();
				this.close();

				Dom.removeClass(this.DOM.closeBtn, this.BX.UI.Button.State.DISABLED);
				Dom.removeClass(this.DOM.saveBtn, this.BX.UI.Button.State.CLOCKING);

				if (response.data.entryId)
				{
					if (this.entry.id)
					{
						EntryManager.showEditEntryNotification(response.data.entryId);
					}
					else
					{
						EntryManager.showNewEntryNotification(response.data.entryId);
					}
				}

				if (response.data.displayMobileBanner)
				{
					new MobileSyncBanner().showInPopup();
				}

				if (response.data.countEventWithEmailGuestAmount)
				{
					Util.setEventWithEmailGuestAmount(response.data.countEventWithEmailGuestAmount);
				}

				if (Type.isArray(response.data.eventList)
					&& response.data.eventList.length
					&& response.data.eventList[0].REMIND
					&& response.data.eventList[0].REMIND.length
				)
				{
					EntryManager.setNewEntryReminders(
						response.data.eventList[0].DT_SKIP_TIME === 'Y' ? 'fullDay' : 'withTime',
						response.data.eventList[0].REMIND
					);
				}

				this.emitter.emit('onSave', new BaseEvent({
					data: {
						responseData: response.data,
						options: options
					}
				}));

				EventEmitter.emit('BX.Calendar:onEntrySave', new BaseEvent({
					data: {
						sliderId: this.sliderId,
						responseData: response.data,
						options: options
					}
				}));
			},
			(response) => {
				Dom.removeClass(this.DOM.saveBtn, this.BX.UI.Button.State.CLOCKING);
				Dom.removeClass(this.DOM.closeBtn, this.BX.UI.Button.State.DISABLED);

				if (response.data && Type.isPlainObject(response.data.busyUsersList))
				{
					this.handleBusyUsersError(response.data.busyUsersList);

					let errors = [];
					response.errors.forEach((error) => {
						if (error.code !== "edit_entry_user_busy")
						{
							errors.push(error);
						}
					});
					response.errors = errors;
				}

				if (response.errors && response.errors.length)
				{
					this.showError(response.errors);
				}

				this.state = this.STATE.ERROR;
			}
		);

		return true;
	}

	handleBusyUsersError(busyUsers)
	{
		let
			users = [],
			userIds = [];

		for (let id in busyUsers)
		{
			if (busyUsers.hasOwnProperty(id))
			{
				users.push(busyUsers[id]);
				userIds.push(id);
			}
		}

		this.busyUsersDialog = new BusyUsersDialog();
		this.busyUsersDialog.subscribe('onSaveWithout', () => {
			this.DOM.form.exclude_users.value = userIds.join(',');
			this.save();
		});

		this.busyUsersDialog.show({users: users});
	}

	clientSideCheck()
	{

	}

	hide(event)
	{
		if (event && event.getSlider && event.getSlider().getUrl() === this.sliderId)
		{
			if (this.checkDenyClose())
			{
				event.denyAction();
			}
			else
			{
				this.BX.removeCustomEvent("SidePanel.Slider::onClose", this.sliderOnClose);
				if (this.attendeesSelector)
				 	this.attendeesSelector.closeAll();

				this.destroy(event);
			}
		}
	}

	destroy(event)
	{
		if (event && event.getSliderPage && event.getSliderPage().getUrl() === this.sliderId)
		{
			this.BX.onCustomEvent('OnCalendarPlannerDoUninstall', [{plannerId: this.plannerId}]);
			Event.unbind(document, 'keydown', this.keyHandlerBind);
			EventEmitter.unsubscribe('onPullEvent-calendar', this.handlePullBind);
			this.BX.SidePanel.Instance.destroy(this.sliderId);
			Util.closeAllPopups();
			this.planner = null;
			this.opened = false;
			Util.clearPlannerWatches();
		}
	}

	createContent(slider)
	{
		let promise = new this.BX.Promise();

		let entry = this.getCurrentEntry();

		this.BX.ajax.runAction('calendar.api.calendarajax.getEditEventSlider', {
			data: {
				event_id: this.entryId || entry.id,
				date_from: entry ? Util.formatDate(entry.from) : '',
				form_type: this.formType,
				type: this.type,
				ownerId: this.ownerId,
				entityList: this.participantsEntityList,
			}
		})
			.then(
				// Success
				(response) => {
					if ((Type.isFunction(slider.isOpen) && slider.isOpen()) || slider.isOpen === true)
					{
						let html = this.BX.util.trim(response.data.html);
						slider.getData().set("sliderContent", html);

						let params = response.data.additionalParams;

						this.uid = params.uniqueId;
						this.editorId = params.editorId;
						this.formSettings = this.getSettings(params.formSettings || []);

						let attendeesEntityList = this.formDataValue.attendeesEntityList
							|| params.attendeesEntityList
							|| [];

						if (!entry.id && this.participantsEntityList.length)
						{
							attendeesEntityList = [...attendeesEntityList, ...this.participantsEntityList];
						}

						if (Type.isArray(attendeesEntityList))
						{
							attendeesEntityList.forEach((item) => {
								if (item.entityId === 'user' && params.userIndex[item.id])
								{
									item.entityType = params.userIndex[item.id].EMAIL_USER ? 'email' : 'employee';
								}
							});
						}

						this.setUserSelectorEntityList(attendeesEntityList);

						this.attendeesPreselectedItems = this.getUserSelectorEntityList().map((item) => {return [item.entityId, item.id]});
						this.setUserSettings(params.userSettings);
						Util.setEventWithEmailGuestAmount(params.countEventWithEmailGuestAmount);
						Util.setEventWithEmailGuestLimit(params.eventWithEmailGuestLimit);
						this.handleSections(params.sections, params.trackingUsersList);
						this.handleLocationData(params.locationFeatureEnabled, params.locationList, params.iblockMeetingRoomList);

						if (!entry.id && !entry.sectionId)
						{
							this.setCurrentEntry();
						}

						this.updateEntryData(params.entry, {
							userSettings: this.userSettings
						});

						const key = this.type + this.ownerId;
						if (this.userSettings.defaultSections && this.userSettings.defaultSections[key])
						{
							SectionManager.setNewEntrySectionId(this.userSettings.defaultSections[key]);
						}

						promise.fulfill(html);
					}
				},
				// Failure
				(response)=>{
					//this.calendar.displayError(response.errors);
				}
			);
		return promise;
	}


	initControls(uid)
	{
		this.DOM.title = this.DOM.content.querySelector(`#${uid}_title`);

		this.DOM.formWrap = this.DOM.content.querySelector(`#${uid}_form_wrap`);
		this.DOM.form = this.DOM.content.querySelector(`#${uid}_form`);
		this.DOM.buttonsWrap = this.DOM.content.querySelector('.calendar-form-buttons-fixed');
		this.DOM.saveBtn = this.DOM.buttonsWrap.querySelector(`#${uid}_save`);
		this.DOM.closeBtn = this.DOM.buttonsWrap.querySelector(`#${uid}_close`);
		Event.bind(this.DOM.saveBtn, 'click', this.save.bind(this));
		Event.bind(this.DOM.closeBtn, 'click', this.close.bind(this));

		this.initFormFieldManager(uid);
		this.initDateTimeControl(uid);
		this.initNameControl(uid);
		this.initEditorControl(uid);
		this.initAttendeesControl(uid);
		this.initPlanner(uid);
		this.initReminderControl(uid);
		this.initSectionSelector(uid);
		this.initLocationControl(uid);
		this.initRepeatRuleControl(uid);
		this.initColorControl(uid);
		this.initCrmUfControl(uid);
		this.initAdditionalControls(uid);

		this.checkLastItemBorder();

		if (this.DOM.buttonsWrap)
		{
			BX.ZIndexManager.register(this.DOM.buttonsWrap);
		}
	}

	updateEntryData(entryData, options = {})
	{
		if (this.entry instanceof Entry)
		{
			let userSettings = options.userSettings || {};

			if (Type.isPlainObject(entryData))
			{
				this.entry.prepareData(entryData);
			}
			else
			{
				if (!this.entry.getTimezoneFrom() || this.entry.getTimezoneTo())
				{
					this.entry.setTimezone(userSettings.timezoneName || userSettings.timezoneDefaultName || null);
				}
			}
		}
	}

	handleSections(sections, trackingUsersList)
	{
		this.sections = sections;
		this.sectionIndex = {};
		this.trackingUsersList = trackingUsersList || [];

		if (Type.isArray(sections))
		{
			sections.forEach((value, ind) => {this.sectionIndex[parseInt(value.ID)] = ind;}, this);
		}
	}

	handleLocationData(locationFeatureEnabled, locationList, iblockMeetingRoomList)
	{
		this.locationFeatureEnabled = !!locationFeatureEnabled;
		this.locationList = locationList || [];
		this.iblockMeetingRoomList = iblockMeetingRoomList || [];

		Location.setLocationList(locationList);
		Location.setMeetingRoomList(iblockMeetingRoomList);
	}

	setUserSettings(userSettings)
	{
		this.userSettings = userSettings;
		Util.setUserSettings(userSettings);
	}

	setFormValues()
	{
		let entry = this.entry;

		// Date time
		this.dateTimeControl.setValue({
			from: this.formDataValue.from || entry.from,
			to: this.formDataValue.to || entry.to,
			fullDay: Type.isBoolean(this.formDataValue.fullDay) ? this.formDataValue.fullDay : entry.fullDay,
			timezoneFrom: entry.getTimezoneFrom() || '',
			timezoneTo: entry.getTimezoneTo() || '',
			timezoneName: this.userSettings.timezoneName
		});

		this.DOM.entryName.value = this.formDataValue.name || entry.getName();

		// Section
		if (this.formDataValue.section)
		{
			entry.sectionId = parseInt(this.formDataValue.section);
		}
		this.DOM.sectionInput.value = this.getCurrentSectionId();
		this.sectionSelector.updateValue();

		if (!this.fieldIsPinned('section'))
		{
			let currentSection = this.getCurrentSection();
			if (currentSection['CAL_TYPE'] !== this.type || currentSection['CAL_TYPE'] === this.type && parseInt(currentSection['OWNER_ID']) !== this.ownerId)
			{
				this.pinField('section');
			}
		}

		// Color
		this.colorSelector.setValue(this.formDataValue.color || entry.getColor() || this.getCurrentSection().COLOR);

		// Reminders
		this.remindersControl.setValue(
			this.formDataValue.reminder || entry.getReminders(),
			true,
			false
		);

		// Recursion
		this.repeatSelector.setValue(entry.getRrule());

		// accessibility
		if (this.DOM.accessibilityInput)
		{
			this.DOM.accessibilityInput.value = entry.accessibility;
		}

		// Location
		this.locationSelector.setValue(this.formDataValue.location || entry.getLocation());

		// Private
		if (this.DOM.privateEventCheckbox)
		{
			this.DOM.privateEventCheckbox.checked = entry.private;
		}

		// Importance
		if (this.DOM.importantEventCheckbox)
		{
			this.DOM.importantEventCheckbox.checked = entry.important;
		}

		if (this.DOM.form.meeting_notify)
		{
			if (this.formDataValue.meetingNotify !== undefined)
			{
				this.DOM.form.meeting_notify.checked = this.formDataValue.meetingNotify;
			}
			if (this.entry.data && this.entry.data.MEETING)
			{
				this.DOM.form.meeting_notify.checked = this.entry.data.MEETING.NOTIFY;
			}
			else
			{
				this.DOM.form.meeting_notify.checked = true; // default value
			}
		}

		if (this.DOM.form.hide_guests)
		{
			if (this.formDataValue.hideGuests !== undefined)
			{
				this.DOM.form.hide_guests.checked = this.formDataValue.hideGuests === 'Y';
			}
			else if (this.entry.data && this.entry.data.MEETING)
			{
				this.DOM.form.hide_guests.checked = this.entry.data.MEETING.HIDE_GUESTS;
			}
			else
			{
				this.DOM.form.hide_guests.checked = true; // default value
			}
		}

		if (this.DOM.form.allow_invite)
		{
			if (this.entry.data)
			{
				this.DOM.form.allow_invite.checked = this.entry.data.MEETING && this.entry.data.MEETING.ALLOW_INVITE;
			}
			else
			{
				this.DOM.form.allow_invite.checked = this.entry.allowInvite;
			}
		}

		this.loadPlannerData({
			entityList: this.getUserSelectorEntityList(),
			from: Util.formatDate(entry.from.getTime() - Util.getDayLength() * 3),
			to: Util.formatDate(entry.to.getTime() + Util.getDayLength() * 10),
			timezone: entry.getTimezoneFrom(),
			location: this.locationSelector.getTextValue()
		}).then(()=>{
			if (this.hasExternalEmailUsers())
			{
				this.showHideGuestsOption();
			}
			else
			{
				this.hideHideGuestsOption();
			}
		});
	}

	switchFullDay(value)
	{
		value = !!this.DOM.fullDay.checked;
		if (value && Type.isString(this.userSettings.timezoneName)
			&& (!this.DOM.fromTz.value || !this.DOM.toTz.value))
		{
			this.DOM.fromTz.value = this.userSettings.timezoneName;
			this.DOM.toTz.value = this.userSettings.timezoneName;
			this.DOM.defTimezone.value = this.userSettings.timezoneName;
		}

		if (value)
		{
			Dom.addClass(this.DOM.dateTimeWrap, 'calendar-options-item-datetime-hide-time');
		}
		else
		{
			Dom.removeClass(this.DOM.dateTimeWrap, 'calendar-options-item-datetime-hide-time');
		}

		if(this.remindersControl)
		{
			this.remindersControl.setFullDayMode(value);
		}

		this.refreshPlanner();
	}

	switchTimezone()
	{
		if (Dom.hasClass(this.DOM.tzCont, 'calendar-options-timezone-collapse'))
		{
			Dom.addClass(this.DOM.tzCont, 'calendar-options-timezone-expand');
			Dom.removeClass(this.DOM.tzCont, 'calendar-options-timezone-collapse');
		}
		else
		{
			Dom.addClass(this.DOM.tzCont, 'calendar-options-timezone-collapse');
			Dom.removeClass(this.DOM.tzCont, 'calendar-options-timezone-expand');
		}
	}

	initFormFieldManager(uid)
	{
		this.DOM.mainBlock = this.DOM.content.querySelector(`#${uid}_main_block_wrap`);
		this.DOM.additionalBlockWrap = this.DOM.content.querySelector(`#${uid}_additional_block_wrap`);
		this.DOM.additionalBlock = this.DOM.content.querySelector(`#${uid}_additional_block`);
		this.DOM.pinnedNamesWrap = this.DOM.content.querySelector(`#${uid}_additional_pinned_names`);
		this.DOM.additionalSwitch = this.DOM.content.querySelector(`#${uid}_additional_switch`);

		Event.bind(this.DOM.additionalSwitch, 'click', () => {
			Dom.toggleClass(this.DOM.additionalSwitch, 'opened');
			Dom.toggleClass(this.DOM.additionalBlock, 'invisible');
		});

		Event.bind(this.DOM.formWrap, 'click', (e) => {
			let target = e.target || e.srcElement;
			if (target && target.getAttribute && target.getAttribute('data-bx-fixfield'))
			{
				let fieldName = target.getAttribute('data-bx-fixfield');
				if (!this.fieldIsPinned(fieldName))
				{
					this.pinField(fieldName);
				}
				else
				{
					this.unPinField(fieldName);
				}
			}
		});
	}

	initDateTimeControl(uid)
	{
		this.dateTimeControl = new SliderDateTimeControl(uid, {
			showTimezone: true,
			outerContent: this.DOM.content
		});

		this.dateTimeControl.subscribe('onChange', (event) => {
			if (event instanceof BaseEvent)
			{
				let value = event.getData().value;
				if (this.remindersControl)
				{
					this.remindersControl.setFullDayMode(value.fullDay);

					if (!this.entry.id && !this.remindersControl.wasChangedByUser())
					{
						const defaultReminders = EntryManager.getNewEntryReminders(
							value.fullDay ? 'fullDay' : 'withTime'
						);

						this.remindersControl.setValue(
							defaultReminders,
							true,
							false
						);
					}
				}

				if (this.planner)
				{
					this.planner.updateSelector(value.from, value.to, value.fullDay);
				}
			}
		});
	}

	initNameControl(uid)
	{
		this.DOM.entryName = this.DOM.content.querySelector(`#${uid}_entry_name`);
		setTimeout(() => {
			this.DOM.entryName.focus();
			this.DOM.entryName.select();
		}, 500);
	}

	initReminderControl(uid)
	{
		this.reminderValues = [];
		this.DOM.reminderWrap = this.DOM.content.querySelector(`#${uid}_reminder`);
		this.DOM.reminderInputsWrap = this.DOM.reminderWrap.appendChild(Tag.render`<span></span>`);

		this.remindersControl = new Reminder({
			wrap: this.DOM.reminderWrap,
			zIndex: this.zIndex,
		});

		this.remindersControl.subscribe('onChange', (event) =>
		{
			if (event instanceof BaseEvent)
			{
				this.reminderValues = event.getData().values;
				Dom.clean(this.DOM.reminderInputsWrap);
				this.reminderValues.forEach((value) => {
					this.DOM.reminderInputsWrap.appendChild(Dom.create('INPUT', {
						props: {name: 'reminder[]', type: 'hidden'},
						attrs: {value: value}}));
				});
			}
		});
	}

	initSectionSelector(uid)
	{
		this.DOM.sectionInput = this.DOM.content.querySelector(`#${uid}_section`);
		this.sectionSelector = new SectionSelector({
			outerWrap: this.DOM.content.querySelector(`#${uid}_section_wrap`),
			defaultCalendarType: this.type,
			defaultOwnerId: this.ownerId,
			sectionList: this.sections,
			sectionGroupList: SectionManager.getSectionGroupList({
				type: this.type || 'user',
				ownerId: this.ownerId || this.userId,
				userId: this.userId,
				trackingUsersList: this.trackingUsersList,
			}),
			mode: 'full',
			zIndex: this.zIndex,
			getCurrentSection: ()=>{
				let section = this.getCurrentSection();
				if (section)
				{
					return {
						id: section.ID,
						name: section.NAME,
						color: section.COLOR
					}
				}
				return false;
			},
			selectCallback: (sectionValue) => {
				if (sectionValue)
				{
					this.DOM.sectionInput.value = sectionValue.id;
					if (this.colorSelector)
					{
						this.colorSelector.setValue(sectionValue.color);
					}
					this.entry.setSectionId(sectionValue.id);

					SectionManager.saveDefaultSectionId(
						sectionValue.id,
						{
							calendarType: this.type,
							ownerId: this.ownerId,
							userId: this.userId,
							sections: this.sections
						});
				}
			}
		});
	}

	initEditorControl(uid)
	{
		if (!window["BXHtmlEditor"])
		{
			return setTimeout(BX.delegate(this.initEditorControl, this), 50);
		}

		this.editor = null;
		if (window["BXHtmlEditor"])
		{
			this.editor = window["BXHtmlEditor"].Get(this.editorId);
		}

		if (
			!this.editor
			&& top["BXHtmlEditor"]
			&& top["BXHtmlEditor"] !== window["BXHtmlEditor"]
		)
		{
			this.editor = top["BXHtmlEditor"].Get(this.editorId);
		}

		if (this.editor && this.editor.IsShown())
		{
			this.customizeHtmlEditor();

			if (this.formDataValue.description)
			{
				this.editor.SetContent(this.formDataValue.description);
			}
		}
		else
		{
			this.BX.addCustomEvent(window["BXHtmlEditor"], 'OnEditorCreated', function (editor)
			{
				if (editor.id === this.editorId)
				{
					this.editor = editor;
					this.customizeHtmlEditor();

					if (this.formDataValue.description)
					{
						this.editor.SetContent(this.formDataValue.description);
					}
				}
			}.bind(this));
		}
	}

	customizeHtmlEditor()
	{
		let editor = this.editor;
		if (editor.toolbar && editor.toolbar.controls && editor.toolbar.controls.spoiler)
		{
			Dom.remove(editor.toolbar.controls.spoiler.pCont);
		}
	}

	initLocationControl(uid)
	{
		this.DOM.locationWrap = this.DOM.content.querySelector(`#${uid}_location_wrap`);
		this.DOM.locationInput = this.DOM.content.querySelector(`#${uid}_location`);

		this.locationSelector = new Location(
			{
				inputName: 'lo_cation', // don't use 'location' word here mantis:107863
				wrap: this.DOM.locationWrap,
				richLocationEnabled: this.locationFeatureEnabled,
				locationList: this.locationList,
				iblockMeetingRoomList: this.iblockMeetingRoomList,
				onChangeCallback: this.refreshPlanner
			}
		);
	}

	initRepeatRuleControl(uid)
	{
		this.DOM.rruleWrap = this.DOM.content.querySelector(`#${uid}_rrule_wrap`);
		this.repeatSelector = new RepeatSelector(
			{
				wrap: this.DOM.rruleWrap,
				rruleType: this.DOM.content.querySelector(`#${uid}_rrule_type`),
				getDate: function() {return this.dateTimeControl.getValue().from;}.bind(this)
			}
		);

		this.dateTimeControl.subscribe('onChange', ()=>{
			if (this.repeatSelector.getType() === 'weekly')
			{
				this.repeatSelector.changeType(this.repeatSelector.getType());
			}
		});
	}

	initAttendeesControl(uid)
	{
		this.DOM.userSelectorWrap = this.DOM.content.querySelector('.calendar-attendees-selector-wrap');
		this.DOM.userSelectorValueWarp = this.DOM.userSelectorWrap.appendChild(Tag.render`<div></div>`);

		this.userTagSelector = new EntityTagSelector({
			dialogOptions: {
				context: 'CALENDAR',
				preselectedItems: this.attendeesPreselectedItems || [],
				zIndex: this.slider.zIndex,
				events: {
					'Item:onSelect': this.handleUserSelectorChanges.bind(this),
					'Item:onDeselect': this.handleUserSelectorChanges.bind(this),
				},
				entities: [
					{
						id: 'user',
						options: {
							inviteGuestLink: true,
							emailUsers: true,
						}
					},
					{
						id: 'project'
					},
					{
						id: 'department',
						options: {selectMode: 'usersAndDepartments'}
					},
					{
						id: 'meta-user',
						options: { 'all-users': true }
					}
				],
				searchTabOptions: {
					stubOptions: {
						title: Loc.getMessage('EC_USER_DIALOG_404_TITLE'),
						subtitle: Loc.getMessage('EC_USER_DIALOG_404_SUBTITLE'),
						icon: '/bitrix/images/calendar/search-email.svg',
						iconOpacity: 100,
						arrow: true,
					}
				},
			}
		});

		this.userTagSelector.renderTo(this.DOM.userSelectorWrap);
		this.DOM.hideGuestsWrap = this.DOM.content.querySelector('.calendar-hide-members-wrap');
	}

	handleUserSelectorChanges()
	{
		if (this.planner)
		{
			this.planner.show();
			this.planner.showLoader();

			const selectedItems = this.userTagSelector.getDialog().getSelectedItems();
			this.setUserSelectorEntityList(selectedItems.map((item) => {
				return {
					entityId: item.entityId,
					id: item.id,
					entityType: item.entityType
				}}));
			this.refreshPlanner();
		}
	}

	hasExternalEmailUsers()
	{
		return !!this.getUserSelectorEntityList().find((item) => {return item.entityType === 'email';});
	}

	showHideGuestsOption()
	{
		this.DOM.hideGuestsWrap.style.display = '';
		const hideGuestsHint = this.DOM.hideGuestsWrap.querySelector('.calendar-hide-members-helper');
		if (Type.isElementNode(hideGuestsHint))
		{
			BX.UI.Hint.initNode(hideGuestsHint);
		}
	}

	hideHideGuestsOption()
	{
		this.DOM.hideGuestsWrap.style.display = 'none';
	}

	setHideGuestsValue(hideGuests = true)
	{
		this.hideGuests = hideGuests;
	}

	initPlanner(uid)
	{
		this.DOM.plannerOuterWrap = this.DOM.content.querySelector(`#${uid}_planner_outer_wrap`);

		this.planner = new Planner({
			wrap: this.DOM.plannerOuterWrap,
			minWidth: parseInt(this.DOM.plannerOuterWrap.offsetWidth),
		});

		this.planner.subscribe('onDateChange', this.handlePlannerSelectorChanges.bind(this));
		this.planner.subscribe('onExpandTimeline', this.handleExpandPlannerTimeline.bind(this));

		this.planner.show();
		this.planner.showLoader();
	}

	loadPlannerData(params = {})
	{
		this.planner.showLoader();

		return new Promise((resolve) => {
			this.BX.ajax.runAction('calendar.api.calendarajax.updatePlanner', {
				data: {
					entryId: this.entry.id || 0,
					ownerId: this.ownerId,
					type: this.type,
					entityList: params.entityList || [],
					dateFrom: params.from || '',
					dateTo: params.to || '',
					timezone: params.timezone || '',
					location: params.location || '',
					entries: params.entrieIds || false
				}
			})
				.then((response) => {
						if (this.planner)
						{
							this.planner.hideLoader();
							let dateTime = this.dateTimeControl.getValue();
							this.planner.update(
								response.data.entries,
								response.data.accessibility
							);
							this.planner.updateSelector(dateTime.from, dateTime.to, dateTime.fullDay);
						}

						if (this.hasExternalEmailUsers())
						{
							this.showHideGuestsOption();
						}
						else
						{
							this.hideHideGuestsOption();
						}
						resolve(response);
					},
					(response) => {resolve(response);}
				);

		});
	}


	initAdditionalControls(uid)
	{
		this.DOM.accessibilityInput = this.DOM.content.querySelector(`#${uid}_accessibility`);
		this.DOM.privateEventCheckbox = this.DOM.content.querySelector(`#${uid}_private`);
		this.DOM.importantEventCheckbox = this.DOM.content.querySelector(`#${uid}_important`);
	}

	initColorControl(uid)
	{
		this.DOM.colorWrap = this.DOM.content.querySelector(`#${uid}_color_selector_wrap`);
		this.colorSelector = new ColorSelector(
			{
				wrap: this.DOM.colorWrap
			}
		);
	}

	initCrmUfControl(uid)
	{
		this.DOM.crmUfWrap = BX(uid + '-uf-crm-wrap');

		if (this.DOM.crmUfWrap)
		{
			let entry = this.getCurrentEntry();
			let loader = this.DOM.crmUfWrap.appendChild(Dom.adjust(Util.getLoader(50), {style: {height: '40px', width: '40px'}}));

			setTimeout(function(){
				this.BX.ajax.runAction('calendar.api.calendarajax.getCrmUserfield', {
					data: {
						event_id: (entry && entry.id) ? entry.id : 0
					}
				}).then(
					// Success
					function(response)
					{
						if (Type.isDomNode(this.DOM.crmUfWrap))
						{
							this.BX.html(this.DOM.crmUfWrap, response.data.html);
						}
					}.bind(this),
					// Failure
					function (response)
					{
						Dom.remove(loader);
					}.bind(this)
				);
			}.bind(this), 800);
		}
	}

	denySliderClose()
	{
		this.denyClose = true;
	}

	allowSliderClose()
	{
		this.denyClose = false;
	}

	checkDenyClose()
	{
		// pending request
		if (this.state === this.STATE.REQUEST)
		{
			return true;
		}

		// Check if closing of slider was caused during selection.
		if (!Type.isNull(this.mouseUpNodeCheck))
		{
			return !this.mouseUpNodeCheck;
		}

		// if (top.BX(this.id + '_time_from_div') && top.BX(this.id + '_time_from_div').style.display !== 'none')
		// 	return true;
		//
		// if (top.BX(this.id + '_time_to_div') && top.BX(this.id + '_time_to_div').style.display !== 'none')
		// 	return true;

		return this.denyClose;
	}

	setCurrentEntry(entry = null, userIndex = null)
	{
		this.entry = EntryManager.getEntryInstance(entry, userIndex, {type: this.type, ownerId: this.ownerId});

		EntryManager.registerEntrySlider(this.entry, this);
	}

	getCurrentEntry()
	{
		return this.entry;
	}

	getCurrentSection()
	{
		let
			section = false,
			sectionId = this.getCurrentSectionId();

		if (sectionId
			&& this.sectionIndex[sectionId] !== undefined
			&& this.sections[this.sectionIndex[sectionId]] !== undefined)
		{
			section = this.sections[this.sectionIndex[sectionId]];
		}

		return section;
	}

	getCurrentSectionId()
	{
		let
			section = 0,
			entry = this.getCurrentEntry();

		if (entry instanceof Entry)
		{
			section = parseInt(entry.sectionId);
		}

		if (!section)
		{
			section = SectionManager.getNewEntrySectionId();
			if (!this.sectionIndex[section])
			{
				section = null;
			}
		}

		if (!section && this.sections[0])
		{
			section = parseInt(this.sections[0].ID);
		}
		return section;
	}

	pinField(fieldName)
	{
		let [placeHolders, placeHoldersAdditional] = this.getPlaceholders();
		let
			field = placeHoldersAdditional[fieldName],
			newField = placeHolders[fieldName],
			fieldHeight = field.offsetHeight;

		field.style.height = fieldHeight + 'px';
		setTimeout(function(){Dom.addClass(field, 'calendar-hide-field');}, 0);
		newField.style.height = '0';

		if (fieldName === 'description')
		{
			setTimeout(function()
			{
				if (!this.DOM.descriptionAdditionalWrap)
				{
					this.DOM.descriptionAdditionalWrap = this.DOM.additionalBlock.querySelector('.calendar-info-panel-description');
				}

				if (this.DOM.descriptionAdditionalWrap)
				{

					while(this.DOM.descriptionAdditionalWrap.firstChild)
					{
						newField.appendChild(this.DOM.descriptionAdditionalWrap.firstChild);
					}
				}
				newField.style.height = fieldHeight + 'px';
			}.bind(this), 200);

			setTimeout(function(){
				Dom.removeClass(field, 'calendar-hide-field');
				field.style.display = 'none';
				newField.style.height = '';
				this.pinnedFieldsIndex[fieldName] = true;
				let editor = window["BXHtmlEditor"].Get(this.editorId);
				if (editor)
				{
					editor.CheckAndReInit();
				}
				this.saveSettings();
				this.updateAdditionalBlockState();
			}.bind(this), 500);
		}
		else
		{
			setTimeout(function()
			{
				while(field.firstChild)
				{
					newField.appendChild(field.firstChild);
				}
				newField.style.height = fieldHeight + 'px';
			}, 200);

			setTimeout(() => {
				Dom.removeClass(field, 'calendar-hide-field');
				field.style.height = '';
				newField.style.height = '';
				this.pinnedFieldsIndex[fieldName] = true;
				this.saveSettings();
				this.updateAdditionalBlockState();
			}, 300);
		}
	}

	unPinField(fieldName)
	{
		let [placeHolders, placeHoldersAdditional] = this.getPlaceholders();
		let
			field = placeHolders[fieldName],
			newField = placeHoldersAdditional[fieldName],
			fieldHeight = field.offsetHeight;

		field.style.height = fieldHeight + 'px';
		setTimeout(function(){
			Dom.addClass(field, 'calendar-hide-field');
		}, 0);
		newField.style.height = '0';

		if (fieldName === 'description')
		{
			setTimeout(function(){
				if (!this.DOM.descriptionAdditionalWrap)
				{
					this.DOM.descriptionAdditionalWrap = this.DOM.additionalBlock.querySelector('.calendar-info-panel-description');
				}

				if (this.DOM.descriptionAdditionalWrap)
				{
					while(field.firstChild)
					{
						this.DOM.descriptionAdditionalWrap.appendChild(field.firstChild);
					}
				}

				newField.style.display = '';
				newField.style.height = fieldHeight + 'px';
			}.bind(this), 200);

			setTimeout(function(){
				Dom.removeClass(field, 'calendar-hide-field');
				field.style.height = '';
				newField.style.height = '';
				this.pinnedFieldsIndex[fieldName] = false;

				let editor = window["BXHtmlEditor"].Get(this.editorId);
				if (editor)
				{
					editor.CheckAndReInit();
				}

				this.saveSettings();
				this.updateAdditionalBlockState();
			}.bind(this), 300);
		}
		else
		{
			setTimeout(function(){
				while(field.firstChild)
				{
					newField.appendChild(field.firstChild);
				}
				newField.style.height = fieldHeight + 'px';
			}, 200);

			setTimeout(function(){
				Dom.removeClass(field, 'calendar-hide-field');
				field.style.height = '';
				newField.style.height = '';
				this.pinnedFieldsIndex[fieldName] = false;

				this.saveSettings();
				this.updateAdditionalBlockState();
			}.bind(this), 300);
		}
	}

	fieldIsPinned(fieldName)
	{
		return this.pinnedFieldsIndex[fieldName];
	}

	getPlaceholders()
	{
		if (!this.placeHolders)
		{
			this.placeHolders = {};
			this.placeHoldersAdditional = {};

			let
				i,
				fieldId,
				nodes = this.DOM.formWrap.querySelectorAll('.calendar-field-additional-placeholder');

			for (i = 0; i < nodes.length; i++)
			{
				fieldId = nodes[i].getAttribute('data-bx-block-placeholer');
				if (fieldId)
				{
					this.placeHoldersAdditional[fieldId] = nodes[i];
				}
			}

			nodes = this.DOM.formWrap.querySelectorAll('.calendar-field-placeholder');
			for (i = 0; i < nodes.length; i++)
			{
				fieldId = nodes[i].getAttribute('data-bx-block-placeholer');
				if (fieldId)
				{
					this.placeHolders[fieldId] = nodes[i];
				}
			}
		}

		return [this.placeHolders, this.placeHoldersAdditional];
	}

	getSettings(settings)
	{
		this.pinnedFieldsIndex = {};
		let i, pinnedFields = [];

		for (i in settings.pinnedFields)
		{
			if (settings.pinnedFields.hasOwnProperty(i))
			{
				pinnedFields.push(settings.pinnedFields[i]);
				this.pinnedFieldsIndex[settings.pinnedFields[i]] = true;
			}
		}
		settings.pinnedFields = pinnedFields;
		return settings;
	}

	saveSettings()
	{
		let fieldName, pinnedFields = [];

		for (fieldName in this.pinnedFieldsIndex)
		{
			if (this.pinnedFieldsIndex.hasOwnProperty(fieldName) && this.pinnedFieldsIndex[fieldName])
			{
				pinnedFields.push(fieldName);
			}
		}

		this.formSettings.pinnedFields = pinnedFields;
		this.BX.userOptions.save('calendar', this.formType, 'pinnedFields', pinnedFields);
	}

	updateAdditionalBlockState(timeout)
	{
		if (timeout !== false)
		{
			if (this.updateAdditionalBlockTimeout)
			{
				clearTimeout(this.updateAdditionalBlockTimeout);
				this.updateAdditionalBlockTimeout = null;
			}
			this.updateAdditionalBlockTimeout = setTimeout(() => {this.updateAdditionalBlockState(false)}, 300);
		}
		else
		{
			let i, names = this.DOM.additionalBlock.getElementsByClassName('js-calendar-field-name');
			Dom.clean(this.DOM.pinnedNamesWrap);
			for (i = 0; i < names.length; i++)
			{
				this.DOM.pinnedNamesWrap.appendChild(Dom.create("SPAN", {props: {className: 'calendar-additional-alt-promo-text'}, html: names[i].innerHTML}));
			}

			if (!names.length)
			{
				Dom.addClass(this.DOM.additionalBlockWrap, 'calendar-additional-block-hidden');
			}
			else if (Dom.hasClass(this.DOM.additionalBlockWrap, 'calendar-additional-block-hidden'))
			{
				Dom.removeClass(this.DOM.additionalBlockWrap, 'calendar-additional-block-hidden');
			}

			this.checkLastItemBorder();
		}
	}

	checkLastItemBorder()
	{
		let
			noBorderClass = 'no-border',
			i, nodes;

		nodes = this.DOM.mainBlock.querySelectorAll('.calendar-options-item-border');
		for (i = 0; i < nodes.length; i++)
		{
			if (i === nodes.length - 1)
			{
				Dom.addClass(nodes[i], noBorderClass);
			}
			else
			{
				Dom.removeClass(nodes[i], noBorderClass);
			}
		}

		nodes = this.DOM.additionalBlock.querySelectorAll('.calendar-options-item-border');
		for (i = 0; i < nodes.length; i++)
		{
			if (i === nodes.length - 1)
			{
				Dom.addClass(nodes[i], noBorderClass);
			}
			else
			{
				Dom.removeClass(nodes[i], noBorderClass);
			}
		}
	}

	handlePlannerSelectorChanges(event)
	{
		if (event instanceof BaseEvent)
		{
			let data = event.getData();
			// Date time
			this.dateTimeControl.setValue({
				from: data.dateFrom,
				to: data.dateTo
			});
			//this.checkLocationAccessibility();
		}
	}

	handleExpandPlannerTimeline(event)
	{
		if (event instanceof BaseEvent)
		{
			let data = event.getData();
			if (data.reload)
			{
				//this.refreshPlanner();
				let dateTime = this.dateTimeControl.getValue();
				this.loadPlannerData({
					entityList: this.getUserSelectorEntityList(),
					from: Util.formatDate(data.dateFrom),
					to: Util.formatDate(data.dateTo),
					timezone: dateTime.timezoneFrom,
					location: this.locationSelector.getTextValue()
				});
			}
		}
	}

	getUserSelectorEntityList()
	{
		return this.selectorEntityList;
	}

	setUserSelectorEntityList(selectorEntityList)
	{
		this.selectorEntityList = selectorEntityList;
	}

	refreshPlannerState()
	{
		let dateTime = this.dateTimeControl.getValue();
		this.loadPlannerData({
			entityList: this.getUserSelectorEntityList(),
			from: Util.formatDate(dateTime.from.getTime() - Util.getDayLength() * 3),
			to: Util.formatDate(dateTime.to.getTime() + Util.getDayLength() * 10),
			timezone: dateTime.timezoneFrom,
			location: this.locationSelector.getTextValue()
		});
	}

	plannerIsShown()
	{
		return this.DOM.plannerWrap && Dom.hasClass(this.DOM.plannerWrap, 'calendar-edit-planner-wrap-shown');
	}

	keyHandler(e)
	{
		if((e.ctrlKey || e.metaKey) && !e.altKey && e.keyCode === Util.getKeyCode('enter'))
		{
			this.save();
		}
	}

	showError(errorList)
	{
		let errorText = '';

		if (Type.isArray(errorList))
		{
			errorList.forEach((error) => {
				if (error.code === "edit_entry_location_busy")
				{
					return Util.showFieldError(error.message, this.DOM.locationWrap, {clearTimeout: 10000});
				}
				errorText += error.message + "\n";
			});
		}

		if (errorText !== '')
		{
			alert(errorText);
		}
	}

	getFormDataChanges(excludes = [])
	{
		const entry = this.entry;
		let fields = [];

		// Name
		if (!excludes.includes('name')
			&& entry.name !== this.DOM.form.name.value)
		{
			fields.push('name');
		}

		// Description
		// if (!excludes.includes('name')
		// 	&& this.descriptionValue !== this.DOM.form.desc.value)
		// {
		// 	fields.push('description');
		// }

		// Location
		if (!excludes.includes('location')
			&&
			this.locationSelector.getTextLocation(Location.parseStringValue(this.entry.getLocation()))
			!==
			this.locationSelector.getTextLocation(Location.parseStringValue(this.locationSelector.getTextValue()))
		)
		{
			fields.push('location');
		}

		// Date + time
		const dateTime = this.dateTimeControl.getValue();
		if (!excludes.includes('date&time')
			&&
			(entry.isFullDay() !== dateTime.fullDay
				|| dateTime.from.toString() !== entry.from.toString()
				|| dateTime.to.toString() !== entry.to.toString()))
		{
			fields.push('date&time');
		}

		// Section
		if (!excludes.includes('section')
			&&
			parseInt(entry.sectionId) !== parseInt(this.DOM.sectionInput.value))
		{
			fields.push('section');
		}

		// Access codes
		if (!excludes.includes('codes')
			&&
			this.getUserSelectorEntityList().map((item)=>{return item.entityId + ':' + item.id}).join('|')
			!==
			entry.getAttendeesEntityList().map((item)=>{return item.entityId + ':' + item.id}).join('|')
		)
		{
			fields.push('codes');
		}

		return fields;
	}

	formDataChanged()
	{
		return this.getFormDataChanges().length > 0;
	}

	getUserCodes()
	{
		const
			codes = [],
			valuesInput = this.DOM.attendeesWrap.querySelectorAll('input[name="EVENT_DESTINATION[]"]');

		for (let i = 0; i < valuesInput.length; i++)
		{
			if (!codes.includes(valuesInput[i].value))
			{
				codes.push(valuesInput[i].value);
			}
		}
		return codes;
	}

	handlePull(event: BaseEvent): void
	{
		if (!event instanceof BaseEvent)
		{
			return;
		}

		const data = event.getData();
		const command = data[0];
		// const params = Type.isObjectLike(data[1]) ? data[1] : {};

		switch(command)
		{
			case 'edit_event':
			case 'delete_event':
			case 'set_meeting_status':
				this.refreshPlannerState();
				break;
		}
	}
}
