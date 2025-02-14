import {Vue} from 'ui.vue';
import {ajax} from 'main.core';
import {Manager} from 'salescenter.manager';
import {Ears} from 'ui.ears';
import StringControl from './properties/string';
import AddressControl from './properties/address';
import CheckboxService from './services/checkbox';
import DropdownService from './services/dropdown';
import Hint from 'salescenter.component.stage-block.hint';
import {Tag, Text} from 'main.core';
import 'currency';

import './css/deliveryselector.css';

export default {
	components: {
		/**
		 * Properties Control Types
		 */
		'ADDRESS-control': AddressControl,
		'STRING-control': StringControl,
		/**
		 * Extra Services Control Types
		 */
		'checkbox-service': CheckboxService,
		'dropdown-service': DropdownService,
	},
	props: {
		initDeliveryServiceId: {required: false},
		initRelatedServicesValues: {required: false},
		initRelatedPropsValues: {required: false},
		initRelatedPropsOptions: {required: false},
		initResponsibleId: {default: null, required: false},
		initEnteredDeliveryPrice: {required: false},
		initIsCalculated: {required: false},
		personTypeId: {required: true},
		action: {type: String, required: true },
		actionData: {type: Object, required: true },
		externalSum: {required: true},
		externalSumLabel: {type: String, required: true},
		currency: {type: String, required: true},
		currencySymbol: {type: String, required: true},
		availableServiceIds: {type: Array, required: true},
		excludedServiceIds: {type: Array, required: true},
		editable: {type: Boolean, required: true},
	},
	data()
	{
		return {
			/**
			 * Selected Service
			 */
			selectedDeliveryService: null,
			deliveryServices: [],

			/**
			 * Props
			 */
			relatedProps: [],
			relatedPropsOfAddressType: [],
			relatedPropsOfOtherTypes: [],
			relatedPropsValues: {},

			/**
			 * Extra Services
			 */
			relatedServices: [],
			relatedServicesValues: {},

			/**
			 * Prices
			 */
			estimatedDeliveryPrice: null,
			enteredDeliveryPrice: null,
			/**
			 * Responsible User
			 */
			responsibleUser: null,

			/**
			 * Processing Indicators
			 */
			isCalculated: false,
			isCalculating: false,

			calculateErrors: [],

			restrictionsHintPopup: null,
		};
	},
	methods: {
		initialize()
		{
			ajax.runAction(
				'salescenter.deliveryselector.getinitializationdata',
				{data: {
						personTypeId: this.personTypeId,
						responsibleId: this.initResponsibleId,
						excludedServiceIds: this.excludedServiceIds
					}}
			).then((result) => {
				/**
				 * Delivery services
				 */

				this.deliveryServices = result.data.services;

				if (this.deliveryServices.length > 0)
				{
					let initDeliveryServiceId = this.selectedDeliveryService
						? this.selectedDeliveryService.id
						: (this.initDeliveryServiceId ? this.initDeliveryServiceId : null);

					if (initDeliveryServiceId)
					{
						for (let deliveryService of this.deliveryServices)
						{
							if (deliveryService.id == initDeliveryServiceId)
							{
								this.selectedDeliveryService = deliveryService;
								break;
							}

							for (let profile of deliveryService.profiles)
							{
								if (profile.id == initDeliveryServiceId)
								{
									this.selectedDeliveryService = profile;
									break;
								}
							}
						}
					}
				}

				/**
				 * Related props
				 */
				let relatedProps = result.data.properties;

				/**
				 * Setting default values to related props
				 */
				for (let relatedProp of relatedProps)
				{
					let initValue = null;

					if (this.initRelatedPropsValues
						&& this.initRelatedPropsValues.hasOwnProperty(relatedProp.id)
					)
					{
						initValue = this.initRelatedPropsValues[relatedProp.id];
					}
					else if (relatedProp.initValue)
					{
						initValue = relatedProp.initValue;
					}

					if (initValue !== null)
					{
						initValue = (typeof initValue === 'object') ? JSON.stringify(initValue) : initValue;

						Vue.set(this.relatedPropsValues, relatedProp.id, initValue);
					}
				}

				this.relatedProps = relatedProps;
				this.relatedPropsOfAddressType = this.relatedProps.filter((item) => item.type === 'ADDRESS');
				this.relatedPropsOfOtherTypes = this.relatedProps.filter((item) => item.type !== 'ADDRESS');

				/**
				 * Related services
				 */
				let relatedServices = result.data.extraServices;

				for (let relatedService of relatedServices)
				{
					let initValue = null;

					if (this.initRelatedServicesValues
						&& this.initRelatedServicesValues.hasOwnProperty(relatedService.id)
					)
					{
						initValue = this.initRelatedServicesValues[relatedService.id];
					}
					else if (relatedService.initValue)
					{
						initValue = relatedService.initValue;
					}

					if (initValue !== null)
					{
						Vue.set(this.relatedServicesValues, relatedService.id, initValue);
					}
				}
				this.relatedServices = relatedServices;

				/**
				 * Responsible
				 */
				this.responsibleUser = result.data.responsible;

				/**
				 * Misc
				 */
				this._userPageTemplate = result.data.userPageTemplate;

				this._deliverySettingsUrl = result.data.deliverySettingsUrl;

				/**
				 *
				 */
				if (this.initEnteredDeliveryPrice !== null)
				{
					this.enteredDeliveryPrice = this.initEnteredDeliveryPrice;
				}

				if (this.initIsCalculated !== null)
				{
					this.isCalculated = this.initIsCalculated;
				}

				(new Ears({
					container: this.$refs['delivery-methods'],
					smallSize: true,
					noScrollbar: true
				})).init();

				this.emitChange();
			});
		},
		calculate()
		{
			if (!this.isCalculatingAllowed)
			{
				return;
			}

			this.isCalculating = true;

			let calculationFinallyCallback = (status, payload) =>
			{
				this.isCalculating = false;
				this.isCalculated = true;
				this.emitChange();
			};

			let actionData = Object.assign(
				{},
				this.actionData,
				{
					deliveryServiceId: this.selectedDeliveryServiceId,
					shipmentPropValues: this.currentRelatedPropsValues,
					deliveryRelatedServiceValues: this.currentRelatedServicesValues,
					deliveryResponsibleId: this.responsibleUser ? this.responsibleUser.id : null,
				}
			);

			ajax.runAction(this.action, {data: actionData}).then((result) => {
				let deliveryPrice = result.data.deliveryPrice;

				this.estimatedDeliveryPrice = deliveryPrice;
				this.enteredDeliveryPrice = deliveryPrice;

				this.calculateErrors = [];
				calculationFinallyCallback();
			}).catch((result) => {
				this.estimatedDeliveryPrice = null;
				this.enteredDeliveryPrice = 0.00;

				this.calculateErrors = result.errors.map((item) => item.message);
				calculationFinallyCallback();
			});
		},
		openChangeResponsibleDialog(event)
		{
			let self = this;

			if (typeof(this._userEditor) === 'undefined')
			{
				this._userEditor = new BX.Crm.EntityEditorUserSelector();

				this._userEditor.initialize(
					'deliverySelectorUserEditor',
					{
						callback(item, type, search, bUndeleted)
						{
							self.responsibleUser = {
								id: type.entityId,
								name: type.name,
								photo: type.avatar,
							};
							self.emitChange();
						}
					}
				);
			}

			this._userEditor.open(
				event.target.parentElement
			);
		},
		onDeliveryServiceChanged(deliveryService, selfCall = false)
		{
			if (!this.editable)
			{
				return;
			}

			if (!this.isServiceAvailable(deliveryService) && !selfCall)
			{
				return;
			}

			if (!deliveryService.parentId && deliveryService.profiles.length > 0)
			{
				this.onDeliveryServiceChanged(deliveryService.profiles[0], true);
			}
			else
			{
				this.selectedDeliveryService = deliveryService;

				this.emitChange();
			}
		},
		isServiceAvailable(service)
		{
			return this.availableServiceIds.includes(service.id);
		},
		onPropValueChanged(event, relatedProp)
		{
			Vue.set(this.relatedPropsValues, relatedProp.id, event);
			this.emitChange();
		},
		onServiceValueChanged(event, relatedService)
		{
			Vue.set(this.relatedServicesValues, relatedService.id, event);
			this.emitChange();
		},
		responsibleUserClicked()
		{
			Manager.openSlider(this.responsibleUserLink);
		},
		emitChange()
		{
			this.$emit('change', this.state);
		},
		formatMoney(value)
		{
			return BX.Currency.currencyFormat(value, this.currency, false);
		},
		isNumber: function(event) {
			event = (event) ? event : window.event;
			var charCode = (event.which) ? event.which : event.keyCode;
			if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
				event.preventDefault();
			} else {
				return true;
			}
			return false;
		},
		getPropValue(relatedProp)
		{
			if (!this.relatedPropsValues)
			{
				return null;
			}
			return this.relatedPropsValues.hasOwnProperty(relatedProp.id) ? this.relatedPropsValues[relatedProp.id] : null;
		},
		getPropOptions(relatedProp)
		{
			if (!this.initRelatedPropsOptions)
			{
				return null;
			}
			return this.initRelatedPropsOptions.hasOwnProperty(relatedProp.id) ? this.initRelatedPropsOptions[relatedProp.id] : null;
		},
		getServiceValue(relatedService)
		{
			if (!this.relatedServicesValues)
			{
				return null;
			}
			return this.relatedServicesValues.hasOwnProperty(relatedService.id) ? this.relatedServicesValues[relatedService.id] : null;
		},
		onAddMoreClicked()
		{
			if (!this.editable)
			{
				return;
			}

			Manager.openSlider(this._deliverySettingsUrl).then(() => {
				this.initialize();
				this.$emit('settings-changed');
			});
		},
		getDeliveryServiceById(id)
		{
			for (let deliveryService of this.deliveryServices)
			{
				if (deliveryService.id == id)
				{
					return deliveryService;
				}
			}

			return null;
		},
		isParentDeliveryServiceSelected(deliveryService)
		{
			if (!this.selectedParentDeliveryService)
			{
				return false;
			}

			return this.selectedParentDeliveryService.id == deliveryService.id;
		},
		onRestrictionsHintShow(e, profile)
		{
			this.restrictionsHintPopup = new Hint.Popup();
			this.restrictionsHintPopup.show(e.target, this.buildRestrictionsNode(profile));

		},
		buildRestrictionsNode(profile)
		{
			let restrictionsNodes = profile.restrictions.map((restriction) => `<div>${Text.encode(restriction)}</div>`);

			return Tag.render `<div>${restrictionsNodes.join('')}</div>`;
		},
		onRestrictionsHintHide(e)
		{
			if (this.restrictionsHintPopup)
			{
				this.restrictionsHintPopup.hide();
			}
		},
	},
	created()
	{
		this.initialize();
	},
	watch: {
		enteredDeliveryPrice(value)
		{
			this.emitChange();
		},
		areProfilesVisible(newValue, oldValue)
		{
			if (!oldValue && newValue)
			{
				//uncomment the block belowe to apply the ears plugin to profiles' section
				setTimeout(() => {
					(new Ears({
						container: this.$refs['delivery-profiles'],
						smallSize: true,
						noScrollbar: true,
						className: 'salescenter-delivery-ears'
					})).init();
				}, 0);
			}
		}
	},
	computed: {
		state()
		{
			return {
				deliveryServiceId: this.selectedDeliveryServiceId,
				deliveryServiceName: this.selectedDeliveryServiceName,
				deliveryPrice: this.deliveryPrice,
				estimatedDeliveryPrice: this.estimatedDeliveryPrice,
				relatedPropsValues: this.currentRelatedPropsValues,
				relatedServicesValues: this.currentRelatedServicesValues,
				responsibleUser: this.responsibleUser,
			};
		},
		selectedDeliveryServiceId()
		{
			return this.selectedDeliveryService ? this.selectedDeliveryService.id : null;
		},
		selectedDeliveryServiceName()
		{
			if (!this.selectedDeliveryService)
			{
				return null;
			}

			if (this.selectedParentDeliveryService === this.selectedDeliveryService)
			{
				return this.selectedDeliveryService.name;
			}

			return this.selectedParentDeliveryService.name + ': ' + this.selectedDeliveryService.name;
		},
		selectedParentDeliveryService()
		{
			if (!this.selectedDeliveryService)
			{
				return null;
			}

			return this.selectedDeliveryService.parentId
				? this.getDeliveryServiceById(this.selectedDeliveryService.parentId)
				: this.selectedDeliveryService;
		},
		selectedNoDelivery()
		{
			return this.selectedDeliveryService && this.selectedDeliveryService['code'] === 'NO_DELIVERY';
		},
		isCalculatingAllowed()
		{
			return this.selectedDeliveryServiceId
				&& this.arePropValuesReady
				&& !this.isCalculating
				&& this.editable;
		},
		currentRelatedPropsValues()
		{
			let result = [];

			if (!this.selectedDeliveryServiceId)
			{
				return result;
			}

			for (let relatedProp of this.relatedProps)
			{
				if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				if (this.relatedPropsValues.hasOwnProperty(relatedProp.id))
				{
					result.push(
						{
							id: relatedProp.id,
							value: this.relatedPropsValues[relatedProp.id]
						}
					);
				}
			}

			return result;
		},
		isResponsibleUserSectionVisible()
		{
			return this.responsibleUser && !this.selectedNoDelivery;
		},
		responsibleUserLink()
		{
			if (!this.responsibleUser)
			{
				return '';
			}

			return this._userPageTemplate.replace('#user_id#', this.responsibleUser.id);
		},
		arePropValuesReady()
		{
			if (!this.selectedDeliveryServiceId)
			{
				return false;
			}

			for (let relatedProp of this.relatedProps)
			{
				if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				if (relatedProp.required && !this.relatedPropsValues[relatedProp.id])
				{
					return false;
				}
			}

			return true;
		},
		currentRelatedServicesValues()
		{
			let result = [];

			if (!this.selectedDeliveryServiceId)
			{
				return result;
			}

			for (let relatedService of this.relatedServices)
			{
				if (!relatedService.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				if (this.relatedServicesValues.hasOwnProperty(relatedService.id))
				{
					result.push(
						{
							id: relatedService.id,
							value: this.relatedServicesValues[relatedService.id]
						}
					);
				}
			}

			return result;
		},
		totalPrice()
		{
			let result = this.externalSum;

			if (this.deliveryPrice !== null)
			{
				result += this.deliveryPrice;
			}
			return result;
		},
		totalPriceFormatted()
		{
			return this.formatMoney(this.totalPrice);
		},
		deliveryPrice()
		{
			if (!this.selectedDeliveryServiceId)
			{
				return null;
			}

			if (this.selectedNoDelivery)
			{
				return 0;
			}

			if (this.enteredDeliveryPrice)
			{
				return +this.enteredDeliveryPrice;
			}

			return null;
		},
		deliveryPriceFormatted()
		{
			return this.formatMoney(this.deliveryPrice);
		},
		estimatedDeliveryPriceFormatted()
		{
			return this.formatMoney(this.estimatedDeliveryPrice);
		},
		externalSumFormatted()
		{
			return this.formatMoney(this.externalSum);
		},
		calculateDeliveryPriceButtonClass()
		{
			return {
				'ui-btn': true,
				'ui-btn-light-border': true,
				'salescenter-delivery-bottom-update-icon': true,
				'ui-btn-disabled': !this.isCalculatingAllowed,
			};
		},
		localize()
		{
			return Vue.getFilteredPhrases('SALE_DELIVERY_SERVICE_SELECTOR_');
		},
		extraServicesCount()
		{
			let result = 0;

			for (let relatedService of this.relatedServices)
			{
				if (!relatedService.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				result++;
			}

			return result;
		},
		relatedPropsOfAddressTypeCount()
		{
			let result = 0;

			for (let relatedProp of this.relatedPropsOfAddressType)
			{
				if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				result++;
			}

			return result;
		},
		relatedPropsOfOtherTypeCount()
		{
			let result = 0;

			for (let relatedProp of this.relatedPropsOfOtherTypes)
			{
				if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				result++;
			}

			return result;
		},
		areProfilesVisible()
		{
			return (this.selectedParentDeliveryService && this.selectedParentDeliveryService.profiles.length > 0);
		},
		selectedParentServiceName()
		{
			return this.selectedParentDeliveryService ? this.selectedParentDeliveryService.name : '';
		},
		selectedParentServiceProfiles()
		{
			if (!this.selectedParentDeliveryService)
			{
				return [];
			}

			return this.selectedParentDeliveryService.profiles;
		},
	},
	template: `
		<div class="salescenter-delivery">
			<div class="salescenter-delivery-header">
				<div class="salescenter-delivery-car-title--sm">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_DELIVERY_METHOD}}</div>
				<div class="salescenter-delivery-method" ref="delivery-methods">
					<div
						v-for="deliveryService in deliveryServices"
						@click="onDeliveryServiceChanged(deliveryService)"
						:class="{
							'salescenter-delivery-method-item': true,
							'salescenter-delivery-method-item--selected': isParentDeliveryServiceSelected(deliveryService)
						}"
						:data-role="isParentDeliveryServiceSelected(deliveryService) ? 'ui-ears-active' : ''"
					>
						<div class="salescenter-delivery-method-image">
							<img :src="deliveryService.logo">
						</div>
						<div class="salescenter-delivery-method-info">
							<div v-if="deliveryService.title" class="salescenter-delivery-method-title">{{deliveryService.title}}</div>
							<div v-else="deliveryService.title" class="salescenter-delivery-method-title"></div>
							<div class="salescenter-delivery-method-name">{{deliveryService.name}}</div>
						</div>
					</div>
					<div @click="onAddMoreClicked" class="salescenter-delivery-method-item salescenter-delivery-method-item--add">
						<div class="salescenter-delivery-method-image-more"></div>
						<div class="salescenter-delivery-method-info">
							<div class="salescenter-delivery-method-name">
								{{localize.SALE_DELIVERY_SERVICE_SELECTOR_ADD_MORE}}
							</div>
						</div>
					</div>
				</div>
			</div>
			<div v-show="areProfilesVisible">
				<div class="salescenter-delivery-car-title--sm">{{selectedParentServiceName}}: {{localize.SALE_DELIVERY_SERVICE_SELECTOR_SHIPPING_SERVICES}}</div>
				<div ref="delivery-profiles" class="salescenter-delivery-car salescenter-delivery-car--ya-delivery">
					<div
						v-for="(profile, index) in selectedParentServiceProfiles"
						@click="onDeliveryServiceChanged(profile)"
						:class="{'salescenter-delivery-car-item': true, 'salescenter-delivery-car-item--selected': selectedDeliveryService.id == profile.id, 'salescenter-delivery-car-item--disabled': !isServiceAvailable(profile)}"
						:data-role="selectedDeliveryService.id == profile.id ? 'ui-ears-active' : ''"
					>
						<div v-show="index === 0" class="salescenter-delivery-car-lable">
							{{localize.SALE_DELIVERY_SERVICE_SELECTOR_PROFITABLE}}
						</div>
						<div class="salescenter-delivery-car-container">
							<div
								class="salescenter-delivery-car-image"
								:style="{ backgroundImage: 'url(' + profile.logo + ')' }"
							></div>
							<div class="salescenter-delivery-car-param">
								<div class="salescenter-delivery-car-title">
									{{profile.name}}
									<div
										v-show="profile.restrictions"
										@mouseenter="onRestrictionsHintShow($event, profile)"
										@mouseleave="onRestrictionsHintHide($event)"
										class="salescenter-delivery-car-title-info"
									></div>
								</div>
								<div class="salescenter-delivery-car-info">{{profile.description}}</div>
							</div>
						</div>
					</div>
				</div>
			</div>
							
			<div v-show="extraServicesCount > 0" class="salescenter-delivery-additionally">
				<div class="salescenter-delivery-additionally-options">
					<component
						v-for="relatedService in relatedServices"
						v-show="relatedService.deliveryServiceIds.includes(selectedDeliveryServiceId)"
						:is="relatedService.type + '-service'"
						:key="relatedService.id"
						:name="relatedService.name"
						:initValue="getServiceValue(relatedService)"						
						:options="relatedService.options"
						:editable="editable"
						@change="onServiceValueChanged($event, relatedService)"
					>
					</component>
				</div>
			</div>			
			<div v-show="relatedPropsOfAddressTypeCount > 0" class="salescenter-delivery-path">
				<div
					v-for="(relatedProp, index) in relatedPropsOfAddressType"
					v-show="relatedProp.deliveryServiceIds.includes(selectedDeliveryServiceId)"
					class="salescenter-delivery-path-item"
				>
					<div class="salescenter-delivery-path-title">{{relatedProp.name}}</div>
					<component
						:is="'ADDRESS-control'"
						:key="relatedProp.id"
						:name="'PROPS_' + relatedProp.id"							
						:initValue="getPropValue(relatedProp)"
						:editable="editable"
						:options="getPropOptions(relatedProp)"
						:isStartMarker="index === 0"
						@change="onPropValueChanged($event, relatedProp)"
					></component>
				</div>
			</div>
			
			<div v-show="relatedPropsOfOtherTypeCount > 0" class="salescenter-delivery-path">
				<div
					v-for="(relatedProp, index) in relatedPropsOfOtherTypes"
					v-show="relatedProp.deliveryServiceIds.includes(selectedDeliveryServiceId)"
					class="salescenter-delivery-path-item"
				>
					<div class="salescenter-delivery-path-title-ordinary">{{relatedProp.name}}</div>
					<div class="salescenter-delivery-path-control">
						<component
							:is="relatedProp.type + '-control'"
							:key="relatedProp.id"
							:name="'PROPS_' + relatedProp.id"
							:editable="editable"
							:initValue="getPropValue(relatedProp)"
							:settings="relatedProp.settings"
							:options="getPropOptions(relatedProp)"
							@change="onPropValueChanged($event, relatedProp)"
						></component>
					</div>
				</div>
			</div>
			<div v-if="isResponsibleUserSectionVisible" class="salescenter-delivery-manager-wrapper">
				<div class="ui-ctl-label-text">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_RESPONSIBLE_MANAGER}}</div>
				<div class="salescenter-delivery-manager">
					<div class="salescenter-delivery-manager-avatar" :style="responsibleUser.photo ? {'background-image': 'url(' + responsibleUser.photo + ')'} : {}"></div>
					<div class="salescenter-delivery-manager-content">
						<div @click="responsibleUserClicked" class="salescenter-delivery-manager-name">{{responsibleUser.name}}</div>
					</div>
					<div v-if="editable" @click="openChangeResponsibleDialog" class="salescenter-delivery-manager-edit">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CHANGE_RESPONSIBLE}}</div>
				</div>
			</div>
					
			<div v-show="!selectedNoDelivery">
				<template v-if="calculateErrors">
					<div v-for="(error, index) in calculateErrors" class="ui-alert ui-alert-danger ui-alert-icon-danger salescenter-delivery-errors-container-alert">
						<span  class="ui-alert-message">
							<span v-html="error"></span>
						</span>
					</div>
				</template>
				<div class="salescenter-delivery-bottom">
					<div v-if="editable && selectedDeliveryService" class="salescenter-delivery-bottom-row">					
						<div class="salescenter-delivery-bottom-col">
							<span v-show="!isCalculating" @click="calculate" :class="calculateDeliveryPriceButtonClass">{{isCalculated ? localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATE_UPDATE : localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATE}}</span>
							
							<span v-show="isCalculating" class="salescenter-delivery-waiter">
								<span class="salescenter-delivery-waiter-alert">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATING_LABEL}}</span>
								<span class="salescenter-delivery-waiter-text">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATING_REQUEST_SENT}} {{selectedParentDeliveryService ? selectedParentDeliveryService.name : ''}}</span>
							</span>
						</div>
					</div>
					<div v-show="isCalculated" class="salescenter-delivery-bottom-row">
						<div class="salescenter-delivery-bottom-col"></div>
						<div class="salescenter-delivery-bottom-col">
							<table class="salescenter-delivery-table-total">
								<tr v-show="editable">
									<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_EXPECTED_DELIVERY_PRICE}}:</td>
									<td>
										<span v-html="estimatedDeliveryPriceFormatted"></span>&nbsp;<span v-html="currencySymbol"></span>
									</td>
								</tr>
								<tr>
									<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLIENT_DELIVERY_PRICE}}:</td>
									<td>
										<div class="ui-ctl ui-ctl-md ui-ctl-wa salescenter-delivery-bottom-input-symbol">
											<input :disabled="!editable" v-model="enteredDeliveryPrice" @keypress="isNumber($event)" type="text" class="ui-ctl-element ui-ctl-textbox">
											<span v-html="currencySymbol"></span>
										</div>
									</td>
								</tr>
								<tr>
									<td>{{externalSumLabel}}:</td>
									<td>
										<span v-html="externalSumFormatted"></span><span class="salescenter-delivery-table-total-symbol" v-html="currencySymbol"></span>
									</td>
								</tr>
								<tr>
									<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_DELIVERY_DELIVERY}}:</td>
									<td>
										<span v-show="deliveryPrice > 0">
											<span v-html="deliveryPriceFormatted"></span>&nbsp;<span v-html="currencySymbol"></span>
										</span>
										<span v-show="!deliveryPrice" class="salescenter-delivery-status salescenter-delivery-status--success">
											{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLIENT_DELIVERY_PRICE_FREE}}
										</span>
									</td>
								</tr>
								
								<tr class="salescenter-delivery-table-total-result">
									<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_TOTAL}}:</td>
									<td>
										<span v-html="totalPriceFormatted"></span>
										<span class="salescenter-delivery-table-total-symbol" v-html="currencySymbol"></span>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
};
