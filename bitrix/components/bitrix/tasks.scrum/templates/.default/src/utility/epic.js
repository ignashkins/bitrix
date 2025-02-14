import {Event, Runtime, Tag, Text, Loc, Type, Dom} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Label} from 'ui.label';

import {Filter} from '../service/filter';
import {SidePanel} from '../service/side.panel';

import {Entity} from '../entity/entity';

import {RequestSender} from './request.sender';
import {EntityStorage} from './entity.storage';
import {TagSearcher} from './tag.searcher';

import type {EpicType} from '../item/item';

import '../css/epic.css';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	sidePanel: SidePanel,
	entity: Entity,
	filter: Filter,
	tagSearcher: TagSearcher,
}

export class Epic extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Epic');

		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.sidePanel = params.sidePanel;
		this.entity = params.entity;
		this.filter = params.filter;
		this.tagSearcher = params.tagSearcher;

		this.form = null;

		this.defaultColor = '#69dafc';
		this.selectedColor = '';

		this.currentEpic = null;
	}

	getCurrentEpic(): ?Object
	{
		return this.currentEpic;
	}

	openAddForm()
	{
		this.id = Text.getRandom();
		this.sidePanelId = 'tasks-scrum-epic-' + this.id;

		this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadAddForm.bind(this));
		this.sidePanel.openSidePanel(this.sidePanelId, {
			contentCallback: () => {
				return new Promise((resolve, reject) => {
					resolve(this.buildAddForm());
				});
			},
			zIndex: 1000
		});
	}

	openEditForm(epicId: Number)
	{
		this.id = Text.getRandom();
		this.sidePanelId = 'tasks-scrum-epic-' + this.id;

		this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadEditForm.bind(this));
		this.sidePanel.openSidePanel(this.sidePanelId, {
			contentCallback: () => {
				return new Promise((resolve, reject) => {
					this.getEpic(epicId).then((response) => {
						this.currentEpic = response.data;
						resolve(this.buildEditForm());
					});
				});
			},
			zIndex: 1000
		});
	}

	openViewForm(epicId: Number)
	{
		this.id = Text.getRandom();
		this.sidePanelId = 'tasks-scrum-epic-' + this.id;

		this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadViewForm.bind(this));
		this.sidePanel.openSidePanel(this.sidePanelId, {
			contentCallback: () => {
				return new Promise((resolve, reject) => {
					this.getEpic(epicId).then((response) => {
						this.currentEpic = response.data;
						resolve(this.buildViewForm());
					});
				});
			},
			zIndex: 1000
		});
	}

	openEpicsList()
	{
		this.id = Text.getRandom();
		this.sidePanelId = 'tasks-scrum-epic-' + this.id;

		EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeGridRequest.bind(this));

		this.sidePanel.openSidePanel(this.sidePanelId, {
			contentCallback: () => {
				this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadListGrid.bind(this));
				this.sidePanel.subscribeOnce('onCloseSidePanel', this.destroyGrid.bind(this));
				return new Promise((resolve, reject) => {
					resolve(Tag.render`
					<div class="tasks-scrum-epics-list">
						<div class="tasks-scrum-epic-header">
							<div class="tasks-scrum-epic-header-title">
								${Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_LIST_TITLE')}
							</div>
							<div class="tasks-scrum-epic-header-add-button">
								<button class="ui-btn ui-btn-primary ui-btn-sm">
									${Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD')}
								</button>
							</div>
						</div>
						<div class="tasks-scrum-epics-list-grid"></div>
					</div>
				`);
				});
			},
			zIndex: 1000
		});
	}

	onLoadAddForm(baseEvent)
	{
		const sidePanel = baseEvent.getData();
		this.form = sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');
		this.currentEpic = null;
		this.onLoadEditor();
		this.onLoadColorPicker();
		this.onLoadAddButtons().then((buttonsContainer) => {
			Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', () => {
				this.requestSender.createEpic(this.getRequestData()).then((response) => {
					this.onAfterCreateEpic(response.data);
					if (this.sidePanel.isPreviousSidePanelExist(sidePanel))
					{
						this.sidePanel.reloadPreviousSidePanel(sidePanel);
						sidePanel.close();
					}
					else
					{
						sidePanel.close(false, () => {
							this.openEpicsList();
						});
					}
				}).catch((response) => {
					this.requestSender.showErrorAlert(
						response,
						Loc.getMessage('TASKS_SCRUM_EPIC_CREATE_ERROR_TITLE_POPUP')
					);
					sidePanel.close();
				});
			});
			Event.bind(buttonsContainer.querySelector('[name=cancel]'), 'click', () => sidePanel.close());
		});
	}

	onLoadViewForm(baseEvent)
	{
		const sidePanel = baseEvent.getData();
		this.form = sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');
		this.onLoadDescription();
		this.onLoadFiles();
		this.onLoadViewButtons().then((buttonsContainer) => {
			Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', () => {
				sidePanel.close(false, () => {
					this.openEditForm(this.currentEpic.id);
				});
			});
			Event.bind(buttonsContainer.querySelector('[name=cancel]'), 'click', () => sidePanel.close());
		});
	}

	onLoadEditForm(baseEvent)
	{
		const sidePanel = baseEvent.getData();
		this.form = sidePanel.getContainer().querySelector('.tasks-scrum-epic-form');
		this.onLoadEditor();
		this.onLoadColorPicker();
		this.onLoadEditButtons().then((buttonsContainer) => {
			Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', () => {
				this.requestSender.editEpic(this.getRequestData()).then((response) => {
					this.emit('onAfterEditEpic', response);
					sidePanel.close(false, () => {
						this.reloadGrid();
					});
				}).catch((response) => {
					this.removeClockIconFromButton();
					this.requestSender.showErrorAlert(
						response,
						Loc.getMessage('TASKS_SCRUM_EPIC_UPDATE_ERROR_TITLE_POPUP')
					);
				});
			});
			Event.bind(buttonsContainer.querySelector('[name=cancel]'), 'click', () => sidePanel.close());
		});
	}

	reloadGrid()
	{
		/* eslint-disable */
		if (BX && BX.Main && BX.Main.gridManager)
		{
			const gridManager = BX.Main.gridManager.getById(this.gridId);
			if (gridManager)
			{
				gridManager.instance.reload();
			}
		}
		/* eslint-enable */
	}

	destroyGrid()
	{
		/* eslint-disable */
		if (BX && BX.Main && BX.Main.gridManager)
		{
			const gridManager = BX.Main.gridManager.getById(this.gridId);
			if (gridManager)
			{
				gridManager.instance.destroy();
			}
		}
		/* eslint-enable */
	}

	onLoadEditor()
	{
		this.getDescriptionEditor().then((editorHtml) => {
			const descriptionContainer = this.form.querySelector('.tasks-scrum-epic-form-description');
			Runtime.html(descriptionContainer, editorHtml).then(() => {
				this.editor = window.LHEPostForm.getHandler(this.id);
				window.BXHtmlEditor.Get(this.id);
				EventEmitter.emit(this.editor.eventNode, 'OnShowLHE', [true]);
				setTimeout(() => {
					this.form.querySelector('.tasks-scrum-epic-form-header-title-control').focus();
				}, 100);
			});
		});
	}

	onLoadDescription()
	{
		const descriptionContainer = this.form.querySelector('.tasks-scrum-epic-form-description');
		this.requestSender.getEpicDescription({
			epicId: this.currentEpic.id,
			text: this.currentEpic.description
		}).then(response => {
			Runtime.html(descriptionContainer, response.data);
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	onLoadFiles()
	{
		const filesContainer = this.form.querySelector('.tasks-scrum-epic-form-files');
		this.requestSender.getEpicFiles({
			epicId: this.currentEpic.id
		}).then(response => {
			Runtime.html(filesContainer, response.data.html);
		});
	}

	onLoadColorPicker()
	{
		this.selectedColor = (this.currentEpic ? this.currentEpic.info.color : this.defaultColor);
		const colorBlockNode = this.form.querySelector('.tasks-scrum-epic-header-color');
		Event.bind(colorBlockNode, 'click', () => {
			const colorNode = colorBlockNode.querySelector('.tasks-scrum-epic-header-color-current');
			const picker = this.getColorPicker(colorNode);
			picker.open();
		});
	}

	onLoadAddButtons(): Promise
	{
		return this.getAddEpicFormButtons().then((buttonsHtml) => {
			const buttonsContainer = this.form.querySelector('.tasks-scrum-epic-form-buttons');
			return Runtime.html(buttonsContainer, buttonsHtml).then(() => buttonsContainer);
		});
	}

	onLoadViewButtons(): Promise
	{
		return this.getViewEpicFormButtons().then((buttonsHtml) => {
			const buttonsContainer = this.form.querySelector('.tasks-scrum-epic-form-buttons');
			return Runtime.html(buttonsContainer, buttonsHtml).then(() => buttonsContainer);
		});
	}

	onLoadEditButtons(): Promise
	{
		return this.getAddEpicFormButtons().then((buttonsHtml) => {
			const buttonsContainer = this.form.querySelector('.tasks-scrum-epic-form-buttons');
			return Runtime.html(buttonsContainer, buttonsHtml).then(() => buttonsContainer);
		});
	}

	onLoadListGrid(baseEvent)
	{
		const sidePanel = baseEvent.getData();
		const form = sidePanel.getContainer().querySelector('.tasks-scrum-epics-list');
		const list = sidePanel.getContainer().querySelector('.tasks-scrum-epics-list-grid');

		this.getEpicsList().then((responseData) => {
			if (responseData.html)
			{
				Runtime.html(list, responseData.html);

				this.prepareTagsList(list);

				const buttonNode = form.querySelector('.tasks-scrum-epic-header-add-button');
				Event.bind(buttonNode, 'click', () => {
					this.openAddForm();
				});
			}
			else
			{
				Dom.remove(form.querySelector('.tasks-scrum-epic-header-add-button'));
				Dom.append(this.getEmptyEpicListForm(), list);
				const buttonNode = list.querySelector('.tasks-scrum-epics-empty-button');
				Event.bind(buttonNode, 'click', () => {
					this.openAddForm();
				});
			}
		});
	}

	onBeforeGridRequest(event: BaseEvent)
	{
		const [, eventArgs] = event.getCompatData();

		eventArgs.sessid = BX.bitrix_sessid();
		eventArgs.method = 'POST';

		if (!eventArgs.url)
		{
			eventArgs.url = this.requestSender.getEpicListUrl();
		}

		eventArgs.data = {
			...eventArgs.data,
			entityId: this.entity.getId(),
			gridId: this.gridId,
			signedParameters: this.requestSender.getSignedParameters()
		};
	}

	getEpicsList() : Promise
	{
		this.gridId = 'EntityEpicsGrid_' + this.entity.getId();
		return new Promise((resolve, reject) => {
			this.requestSender.getEpicsList({
				entityId: this.entity.getId(),
				gridId: this.gridId
			}).then(response => {
				resolve(response.data);
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		});
	}

	getEpic(id: Number): Promise
	{
		return this.requestSender.getEpic({id: id});
	}

	buildAddForm(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epic-form">
				${this.buildFormHeader(Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_FORM_TITLE'))}
				<div class="tasks-scrum-epic-form-container">
					${this.buildFormContainerHeader('', '#69dafc')}
					<div class="tasks-scrum-epic-form-body">
						<div class="tasks-scrum-epic-form-description"></div>
					</div>
				</div>
				<div class="tasks-scrum-epic-form-buttons"></div>
			</div>
		`;
	}

	buildViewForm(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epic-form">
				${this.buildFormHeader(Loc.getMessage('TASKS_SCRUM_SPRINT_VIEW_EPIC_FORM_TITLE'))}
				<div class="tasks-scrum-epic-form-container">
					<div class="tasks-scrum-epic-form-header">
						<div class="tasks-scrum-epic-form-header-title">
							${Text.encode(this.currentEpic.name)}
						</div>
						<div class="tasks-scrum-epic-form-header-separate"></div>
						<div class="tasks-scrum-epic-header-color">
							<div class="tasks-scrum-epic-header-color-current" style=
								"background-color: ${Text.encode(this.currentEpic.info.color)};">
							</div>
						<div class="tasks-scrum-epic-header-color-btn-angle">
						</div>
						</div>
					</div>
					<div class="tasks-scrum-epic-form-body">
						<div class="tasks-scrum-epic-form-description" style="padding: 15px 10px 15px 10px;"></div>
						<div class="tasks-scrum-epic-form-files"></div>
					</div>
				</div>
				<div class="tasks-scrum-epic-form-buttons"></div>
			</div>
		`;
	}

	buildEditForm(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epic-form">
				${this.buildFormHeader(Loc.getMessage('TASKS_SCRUM_SPRINT_EDIT_EPIC_FORM_TITLE'))}
				<div class="tasks-scrum-epic-form-container">
					${this.buildFormContainerHeader(this.currentEpic.name, this.currentEpic.info.color)}
					<div class="tasks-scrum-epic-form-body">
						<div class="tasks-scrum-epic-form-description"></div>
					</div>
				</div>
				<div class="tasks-scrum-epic-form-buttons"></div>
			</div>
		`;
	}

	getEmptyEpicListForm(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epics-empty">
				<div class="tasks-scrum-epics-empty-first-title">
					${Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_FIRST_TITLE')}
				</div>
				<div class="tasks-scrum-epics-empty-image">
					<svg width="124px" height="123px" viewBox="0 0 124 123" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
						<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" opacity="0.28">
							<path d="M83,105 L83,81.4375 L105,81.4375 L105,18 L17,18 L17,81.4375 L39,81.4375 L39,105 L83,105 Z M10.9411765,0 L113.058824,0 C119.101468,0 124,4.85902727 124,10.8529412 L124,112.147059 C124,118.140973 119.101468,123 113.058824,123 L10.9411765,123 C4.89853156,123 0,118.140973 0,112.147059 L0,10.8529412 C0,4.85902727 4.89853156,0 10.9411765,0 Z M44.0142862,47.0500004 L54.2142857,57.4416671 L79.7142857,32 L87,42.75 L54.2142857,75 L36,57.0833333 L44.0142862,47.0500004 Z" fill="#A8ADB4" />
						</g>
					</svg>
				</div>
				<div class="tasks-scrum-epics-empty-second-title">
					${Loc.getMessage('TASKS_SCRUM_EPICS_EMPTY_SECOND_TITLE')}
				</div>
				<div class="tasks-scrum-epics-empty-button">
					<button class="ui-btn ui-btn-primary ui-btn-lg">
						${Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD')}
					</button>
				</div>
			</div>
		`;
	}

	buildFormHeader(title: String): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epic-header">
				<div class="tasks-scrum-epic-header-title">
					${title}
				</div>
			</div>
		`;
	}

	buildFormContainerHeader(name: String, color: String): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-epic-form-header">
				<div class="tasks-scrum-epic-form-header-title">
					<input type="text" name="name" value="${Text.encode(name)}" class=
						"tasks-scrum-epic-form-header-title-control" placeholder=
						"${Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_EPIC_NAME_PLACEHOLDER')}">
				</div>
				<div class="tasks-scrum-epic-form-header-separate"></div>
				<div class="tasks-scrum-epic-header-color">
					<div class="tasks-scrum-epic-header-color-current" style=
						"background-color: ${Text.encode(color)};">
					</div>
					<div class="tasks-scrum-epic-header-color-btn-angle">
					</div>
				</div>
			</div>
		`;
	}

	getDescriptionEditor(): Promise
	{
		const requestData = {
			editorId: this.id
		};

		if (this.currentEpic)
		{
			requestData.epicId = this.currentEpic.id;
			requestData.text = this.currentEpic.description;
		}

		return new Promise((resolve, reject) => {
			this.requestSender.getEpicDescriptionEditor(requestData).then(response => {
				resolve(response.data.html);
			})
		});
	}

	getAddEpicFormButtons(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getAddEpicFormButtons().then(response => {
				resolve(response.data.html);
			})
		});
	}

	getViewEpicFormButtons(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getViewEpicFormButtonsAction().then(response => {
				resolve(response.data.html);
			})
		});
	}

	getColorPicker(colorNode)
	{
		/* eslint-disable */
		return new BX.ColorPicker({
			bindElement: colorNode,
			defaultColor: this.selectedColor,
			onColorSelected: (color, picker) => {
				this.selectedColor = color;
				colorNode.style.backgroundColor = color;
			},
			popupOptions: {
				zIndex: 1100,
				className: 'tasks-scrum-epic-color-popup'

			},
			colors: [
				["#aae9fc", "#bbecf1", "#98e1dc", "#e3f299", "#ffee95", "#ffdd93", "#dfd3b6", "#e3c6bb"],
				["#ffad97", "#ffbdbb", "#ffcbd8", "#ffc4e4", "#c4baed", "#dbdde0", "#bfc5cd", "#a2a8b0"]
			]
		});
		/* eslint-enable */
	}

	getRequestData()
	{
		const requestData = {};

		if (this.currentEpic)
		{
			requestData.epicId = this.currentEpic.id;
		}

		requestData.entityId = this.entity.getId();
		requestData.name = (this.form.querySelector('[name=name]').value).trim();
		requestData.description = this.editor.oEditor.GetContent();
		requestData.color = this.selectedColor;
		requestData.files = this.getAttachmentsFiles();

		return requestData;
	}

	getAttachmentsFiles(): Array
	{
		const files = [];

		if (!this.editor || !Type.isPlainObject(this.editor.arFiles) || !Type.isPlainObject(this.editor.controllers))
		{
			return files;
		}

		const fileControllers = [];
		Object.values(this.editor.arFiles).forEach((controller) => {
			if(!fileControllers.includes(controller))
			{
				fileControllers.push(controller);
			}
		});

		fileControllers.forEach((fileController) => {
			if (
				this.editor.controllers[fileController] &&
				Type.isPlainObject(this.editor.controllers[fileController].values)
			)
			{
				Object.keys(this.editor.controllers[fileController].values).forEach((fileId) => {
					if (!files.includes(fileId))
					{
						files.push(fileId);
					}
				})
			}
		});

		return files;
	}

	prepareTagsList(container: HTMLElement)
	{
		const tagsContainers = container.querySelectorAll('.tasks-scrum-epic-grid-tags');
		tagsContainers.forEach((tagsContainer) => {
			const tags = this.getTagsFromNode(tagsContainer);
			Dom.clean(tagsContainer);
			tags.forEach((tag) => {
				Dom.append(this.getTagNode(tag), tagsContainer);
			});
		});
	}

	getTagsFromNode(node: HTMLElement): Array
	{
		const tags = [];
		node.childNodes.forEach((childNode) => {
			tags.push(childNode.textContent.trim());
		});
		return tags;
	}

	getTagNode(tag: string): HTMLElement
	{
		const tagLabel = new Label({
			text: tag,
			color: Label.Color.TAG_LIGHT,
			fill: true,
			size: Label.Size.SM,
			customClass: ''
		});
		const container = tagLabel.getContainer();
		Event.bind(container, 'click', () => {
			this.emit('filterByTag', tag);
			this.sidePanel.closeTopSidePanel(true);
		});
		return container;
	}

	removeClockIconFromButton()
	{
		const buttonsContainer = this.form.querySelector('.tasks-scrum-epic-form-buttons');
		if (buttonsContainer)
		{
			Dom.removeClass(buttonsContainer.querySelector('[name=save]'), 'ui-btn-wait');
		}
	}

	onAfterCreateEpic(epic: EpicType)
	{
		this.tagSearcher.addEpicToSearcher(epic);
		this.filter.addItemToListTypeField('EPIC', {
			NAME: epic.name.trim(),
			VALUE: String(epic.id)
		});
	}

	onAfterUpdateEpic(epic: EpicType)
	{
		this.entityStorage.getAllItems().forEach((item) => {
			const itemEpic = item.getEpic();
			if (itemEpic && itemEpic.id === epic.id)
			{
				item.setEpicAndTags(epic);
			}
		});

		const oldEpicInfo = this.getCurrentEpic();
		if (oldEpicInfo)
		{
			this.tagSearcher.removeEpicFromSearcher(oldEpicInfo);
		}

		this.tagSearcher.addEpicToSearcher(epic);
	}

	onAfterRemoveEpic(epic: EpicType)
	{
		this.entityStorage.getAllItems().forEach((item) => {
			const itemEpic = item.getEpic();
			if (itemEpic && itemEpic.id === epic.id)
			{
				item.setEpicAndTags(null);
			}
		});
		this.tagSearcher.removeEpicFromSearcher(epic);
		this.sidePanel.reloadTopSidePanel(true);
	}
}