import {Vue} from 'ui.vue';
import {VuexBuilder} from 'ui.vue.vuex';
import {rest as Rest} from 'rest.client';
import {Manager} from 'salescenter.manager';
import {Loader} from 'main.loader';
import {Type, Loc, ajax as Ajax, Event} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {MenuManager} from 'main.popup';
import 'ui.notification';
import {ApplicationModel} from './models/application';
import {OrderCreationModel} from './models/ordercreation';
import Chat from './chat';
import Deal from './deal';
import './css/component.css';

export class App
{
	constructor(options = {
		dialogId: null,
		sessionId: null,
		lineId: null,
		orderAddPullTag: null,
		landingPublicationPullTag: null,
		landingUnPublicationPullTag: null,
		isFrame: true,
		isOrderPublicUrlAvailable: false,
		isCatalogAvailable: false,
		isOrderPublicUrlExists: false,
		isWithOrdersMode: true,
	})
	{
		this.slider = BX.SidePanel.Instance.getTopSlider();
		this.dialogId = options.dialogId;
		this.sessionId = parseInt(options.sessionId);
		this.lineId = parseInt(options.lineId);
		this.orderAddPullTag = options.orderAddPullTag;
		this.landingPublicationPullTag = options.landingPublicationPullTag;
		this.landingUnPublicationPullTag = options.landingUnPublicationPullTag;
		this.paySystemList = options.paySystemList;
		this.cashboxList = options.cashboxList;
		this.options = options;
		this.isProgress = false;
		this.fillPagesTimeout = false;
		this.disableSendButton = false;
		this.context = '';
		this.fillPagesQueue = [];
		this.ownerTypeId = '';
		this.ownerId = '';
		this.orderId = parseInt(options.orderId);
		this.stageOnOrderPaid = null;
		this.stageOnDeliveryFinished = null;
		this.sendingMethod = '';
		this.sendingMethodDesc = {};
		this.orderPublicUrl = '';
		this.fileControl = options.fileControl;

		if(Type.isString(options.stageOnOrderPaid))
		{
			this.stageOnOrderPaid = options.stageOnOrderPaid;
		}

		if(Type.isString(options.stageOnDeliveryFinished))
		{
			this.stageOnDeliveryFinished = options.stageOnDeliveryFinished;
		}

		if(Type.isBoolean(options.isFrame))
		{
			this.isFrame = options.isFrame;
		}
		else
		{
			this.isFrame = true;
		}

		if(Type.isBoolean(options.isOrderPublicUrlAvailable))
		{
			this.isOrderPublicUrlAvailable = options.isOrderPublicUrlAvailable;
		}
		else
		{
			this.isOrderPublicUrlAvailable = false;
		}

		if(Type.isBoolean(options.isOrderPublicUrlExists))
		{
			this.isOrderPublicUrlExists = options.isOrderPublicUrlExists;
		}
		else
		{
			this.isOrderPublicUrlExists = false;
		}

		if(Type.isString(options.orderPublicUrl))
		{
			this.orderPublicUrl = options.orderPublicUrl;
		}

		if(Type.isBoolean(options.isCatalogAvailable))
		{
			this.isCatalogAvailable = options.isCatalogAvailable;
		}
		else
		{
			this.isCatalogAvailable = false;
		}

		if(Type.isBoolean(options.isWithOrdersMode))
		{
			this.isWithOrdersMode = options.isWithOrdersMode;
		}
		else
		{
			this.isWithOrdersMode = false;
		}

		if(Type.isBoolean(options.disableSendButton))
		{
			this.disableSendButton = options.disableSendButton;
		}

		if(options.ownerTypeId)
		{
			this.ownerTypeId = options.ownerTypeId;
		}

		if(options.ownerId)
		{
			this.ownerId = options.ownerId;
		}

		if(Type.isString(options.context) && options.context.length > 0)
		{
			this.context = options.context;
		}
		else if(this.sessionId && this.dialogId)
		{
			this.context = 'imopenlines_app';
		}

		if(Type.isBoolean(options.isPaymentsLimitReached))
		{
			this.isPaymentsLimitReached = options.isPaymentsLimitReached;
		}
		else
		{
			this.isPaymentsLimitReached = false;
		}

		if(!Type.isUndefined(options.sendingMethod))
		{
			this.sendingMethod = options.sendingMethod;
			this.sendingMethodDesc = this.options.sendingMethodDesc;
		}

		this.isPaymentCreationAvailable = (
			(this.sessionId > 0 && this.dialogId.length > 0) || (this.ownerTypeId && this.ownerId)
		);
		this.connector = Type.isString(options.connector) ? options.connector : '';

		Event.ready(() =>
		{
			this.pull = BX.PULL;
			this.initPull();
			this.isSiteExists = Manager.isSiteExists;
		});

		App.initStore()
			.then((result) => this.initTemplate(result))
			.catch((error) => App.showError(error))
		;
	}

	static initStore()
	{
		const builder = new VuexBuilder();

		return builder.addModel(ApplicationModel.create())
			.addModel(OrderCreationModel.create())
			.useNamespace(true)
			.build();
	}

	initPull()
	{
		if(this.pull)
		{
			if(Type.isString(this.orderAddPullTag))
			{
				this.pull.subscribe({
					moduleId: 'salescenter',
					command: this.orderAddPullTag,
					callback: (params) =>
					{
						if(parseInt(params.sessionId) === this.sessionId && params.orderId > 0)
						{
							Manager.showOrdersListAfterCreate(params.orderId);
						}
					},
				});
			}

			if(Type.isString(this.landingPublicationPullTag))
			{
				this.pull.subscribe({
					moduleId: 'salescenter',
					command: this.landingPublicationPullTag,
					callback: (params) =>
					{
						if(parseInt(params.landingId) > 0)
						{
							this.fillPages();
						}
						if(params.hasOwnProperty('isOrderPublicUrlAvailable') && Type.isBoolean(params.isOrderPublicUrlAvailable))
						{
							this.isOrderPublicUrlAvailable = params.isOrderPublicUrlAvailable;
							this.isOrderPublicUrlExists = true;
						}
					},
				});
			}

			if(Type.isString(this.landingUnPublicationPullTag))
			{
				this.pull.subscribe({
					moduleId: 'salescenter',
					command: this.landingUnPublicationPullTag,
					callback: (params) =>
					{
						if(parseInt(params.landingId) > 0)
						{
							this.fillPages();
						}
						if(params.hasOwnProperty('isOrderPublicUrlAvailable') && Type.isBoolean(params.isOrderPublicUrlAvailable))
						{
							this.isOrderPublicUrlAvailable = params.isOrderPublicUrlAvailable;
							this.isOrderPublicUrlExists = true;
						}
					},
				});
			}
		}
	}

	initTemplate(result)
	{
		return new Promise((resolve) =>
		{
			const context = this;
			this.store = result.store;

			this.templateEngine = Vue.create({
				el: document.getElementById('salescenter-app-root'),
				components: {
					'chat': Chat,
					'deal': Deal,
				},
				template:
					this.context === 'deal'
						? `<deal :key="componentKey" @on-reload="reload"/>`
						: `<chat :key="componentKey" @on-reload="reload"/>`
					,
				store: this.store,
				created()
				{
					this.$app = context;
					this.$nodes = {
						footer: document.getElementById('footer'),
						leftPanel: document.getElementById('left-panel'),
						title: document.getElementById('pagetitle'),
						paymentsLimit: document.getElementById('salescenter-payment-limit-container'),
						orderSelector: document.getElementById('salescenter-app-order-selector'),
					};
					this.initOrderSelector();
				},
				mounted()
				{
					resolve();
				},
				methods: {
					reload(arParams)
					{
						this.$root.$app.getLoader().show(document.body);

						BX.ajax.runComponentAction(
							'bitrix:salescenter.app',
							'getComponentResult',
							{
								mode: 'class',
								data: {
									arParams
								}
							}
						).then(function(response) {
							if (response.data)
							{
								this.$root.$app.options = response.data;
								this.$root.$app.orderId = this.$root.$app.options.orderId;
								this.componentKey += 1;

								this.$root.$app.getLoader().hide();
							}
						}.bind(this));
					},
					initOrderSelector()
					{
						try
						{
							if
							(
								this.$app.options.orderList.length < 2
								|| this.$app.options.templateMode !== 'create'
								|| !this.$app.options.orderId
							)
							{
								return;
							}

							const orderSelectorBtn = this.$nodes.orderSelector.querySelector('.salescenter-app-order-selector-text');
							if (!orderSelectorBtn)
							{
								return;
							}

							orderSelectorBtn.innerText = Loc.getMessage('SALESCENTER_ORDER_SELECTOR_ORDER_NUM')
								.replace('#ORDER_ID#', this.$app.options.orderId);

							orderSelectorBtn.setAttribute('data-hint', Loc.getMessage('SALESCENTER_ORDER_SELECTOR_TOOLTIP'));

							let popupMenu;
							let menuItems = [];
							this.$app.options.orderList.map(orderId => {
								const orderCaption = Loc.getMessage('SALESCENTER_ORDER_SELECTOR_ORDER_NUM').replace('#ORDER_ID#', orderId);
								menuItems.push({
									text: orderCaption,
									onclick: event => {
										popupMenu.close();
										orderSelectorBtn.innerText = orderCaption;
										this.reload({
											context: this.$app.options.context,
											orderId: orderId,
											ownerTypeId: this.$app.options.ownerTypeId,
											ownerId: this.$app.options.ownerId,
											templateMode: this.$app.options.templateMode,
											mode: this.$app.options.mode,
											initialMode: this.$app.options.initialMode,
										});
									}
								});
							});
							popupMenu = MenuManager.create({
								id: 'deal-order-selector',
								bindElement: orderSelectorBtn,
								items: menuItems
							});

							this.$nodes.orderSelector.classList.remove('is-hidden');
							this.$nodes.orderSelector.addEventListener('click', e => {
								e.preventDefault();
								popupMenu.show();
								BX.UI.Hint.hide();
							});

							BX.UI.Hint.init(this.$nodes.orderSelector);
						}
						catch (err)
						{
							//
						}
					},
				},
				data() {
					return {
						componentKey: 0,
					};
				},
			});
		});
	}

	closeApplication()
	{
		if(this.slider)
		{
			this.slider.close();
		}
	}

	fillPages()
	{
		return new Promise((resolve) =>
		{
			if(this.isProgress)
			{
				this.fillPagesQueue.push(resolve);
			}
			else
			{
				if(this.fillPagesTimeout)
				{
					clearTimeout(this.fillPagesTimeout);
				}
				this.fillPagesTimeout = setTimeout(() =>
				{
					this.startProgress();
					Rest.callMethod('salescenter.page.list', {}).then((result) => {
						this.store.commit('application/setPages', {pages: result.answer.result.pages});
						this.stopProgress();
						resolve();
						this.fillPagesQueue.forEach((item) =>
						{
							item();
						});
						this.fillPagesQueue = [];
					});
				}, 100);
			}
		});
	}

	static showError(error)
	{
		// console.error(error);
	}

	getLoader()
	{
		if(!this.loader)
		{
			this.loader = new Loader({size: 200, mode: 'custom'});
		}

		return this.loader;
	}

	showLoader()
	{
		if(this.templateEngine)
		{
			this.getLoader().show(this.templateEngine.$el);
		}
	}

	hideLoader()
	{
		this.getLoader().hide();
	}

	startProgress(buttonEvent = null)
	{
		this.isProgress = true;
		this.templateEngine.$emit('on-start-progress');
		this.showLoader();
		if (Type.isDomNode(buttonEvent))
		{
			buttonEvent.classList.add('ui-btn-wait');
		}
	}

	stopProgress(buttonEvent = null)
	{
		this.isProgress = false;
		this.templateEngine.$emit('on-stop-progress');
		this.hideLoader();
		if (Type.isDomNode(buttonEvent))
		{
			buttonEvent.classList.remove('ui-btn-wait');
		}
	}

	hidePage(page)
	{
		return new Promise((resolve, reject) => {
			let promise;
			if(page.landingId > 0)
			{
				promise = Manager.hidePage(page);
			}
			else
			{
				promise = Manager.deleteUrl(page);
			}
			promise.then(() =>
			{
				this.store.commit('application/removePage', {page});
				resolve();
			}).catch((result) =>
			{
				App.showError(result.answer.error_description);
				reject(result.answer.error_description);
			});
		});
	}

	sendPage(pageId)
	{
		if(this.isProgress)
		{
			return;
		}
		if(this.disableSendButton)
		{
			return;
		}
		const pages = this.store.getters['application/getPages']();
		let page;
		for(let index in pages)
		{
			if(pages.hasOwnProperty(index) && pages[index].id === pageId)
			{
				page = pages[index];
				break;
			}
		}
		let source = 'other';
		if(page.landingId > 0)
		{
			if(parseInt(page.siteId) === parseInt(Manager.connectedSiteId))
			{
				source = 'landing_store_chat';
			}
			else
			{
				source = 'landing_other';
			}
		}
		if(!this.dialogId)
		{
			this.slider.data.set('action', 'sendPage');
			this.slider.data.set('page', page);
			this.slider.data.set('pageId', pageId);
			if(this.context === 'sms')
			{
				this.startProgress();
				BX.Salescenter.Manager.addAnalyticAction({
					analyticsLabel: 'salescenterSendSms',
					context: this.context,
					source: source,
					type: page.isWebform ? 'form' : 'info',
					code: page.code,
				}).then(() =>
				{
					this.stopProgress();
					this.closeApplication();
				});
			}
			else
			{
				this.closeApplication();
			}
			return;
		}
		this.startProgress();

		Ajax.runAction('salescenter.page.send', {
			analyticsLabel: 'salescenterSendChat',
			getParameters: {
				dialogId: this.dialogId,
				context: this.context,
				source: source,
				type: page.isWebform ? 'form' : 'info',
				connector: this.connector,
				code: page.code,
			},
			data: {
				id: pageId,
				options: {
					dialogId: this.dialogId,
					sessionId: this.sessionId,
				},
			}
		}).then(() =>
		{
			this.stopProgress();
			this.closeApplication();
		}).catch((result) =>
		{
			App.showError(result.errors.pop().message);
			this.stopProgress();
		});
	}
	sendShipment(buttonEvent)
	{
		if (!this.isPaymentCreationAvailable)
		{
			this.closeApplication();
			return null;
		}

		if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress)
		{
			return null;
		}

		this.startProgress(buttonEvent);

		let data = {
			ownerTypeId: this.ownerTypeId,
			ownerId: this.ownerId,
			orderId: this.orderId,
			deliveryId: this.store.getters['orderCreation/getDeliveryId'],
			deliveryPrice: this.store.getters['orderCreation/getDelivery'],
			expectedDeliveryPrice: this.store.getters['orderCreation/getExpectedDelivery'],
			deliveryResponsibleId: this.store.getters['orderCreation/getDeliveryResponsibleId'],
			personTypeId: this.store.getters['orderCreation/getPersonTypeId'],
			shipmentPropValues: this.store.getters['orderCreation/getPropertyValues'],
			deliveryExtraServicesValues: this.store.getters['orderCreation/getDeliveryExtraServicesValues'],
		};

		if (this.stageOnDeliveryFinished !== null)
		{
			data.stageOnDeliveryFinished = this.stageOnDeliveryFinished;
		}

		BX.ajax.runAction('salescenter.order.createShipment', {
			data: {
				basketItems: this.store.getters['orderCreation/getBasket'](),
				options: data,
			},
			analyticsLabel: 'salescenterCreateShipment',
		}).then((result) =>
		{
			this.store.dispatch('orderCreation/resetBasket');
			this.stopProgress(buttonEvent);

			if (result.data)
			{
				if (result.data.order)
				{
					this.slider.data.set('order', result.data.order);
				}
				if (result.data.deal)
				{
					this.slider.data.set('deal', result.data.deal);
				}
			}

			this.closeApplication();
			this.emitGlobalEvent('salescenter.app:onshipmentcreated');
		}).catch((data) =>
		{
			data.errors.forEach((error) => {
				alert(error.message);
			});
			this.stopProgress(buttonEvent);
			App.showError(data);
		});
	}

	sendPayment(buttonEvent, skipPublicMessage = 'n')
	{
		if (!this.isPaymentCreationAvailable)
		{
			this.closeApplication();
			return null;
		}

		if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress)
		{
			return null;
		}

		this.startProgress(buttonEvent);

		let data = {
			dialogId: this.dialogId,
			sendingMethod: this.sendingMethod,
			sendingMethodDesc: this.sendingMethodDesc,
			sessionId: this.sessionId,
			lineId: this.lineId,
			ownerTypeId: this.ownerTypeId,
			orderId: this.orderId,
			ownerId: this.ownerId,
			skipPublicMessage,
			deliveryId: this.store.getters['orderCreation/getDeliveryId'],
			deliveryPrice: this.store.getters['orderCreation/getDelivery'],
			expectedDeliveryPrice: this.store.getters['orderCreation/getExpectedDelivery'],
			deliveryResponsibleId: this.store.getters['orderCreation/getDeliveryResponsibleId'],
			personTypeId: this.store.getters['orderCreation/getPersonTypeId'],
			shipmentPropValues: this.store.getters['orderCreation/getPropertyValues'],
			deliveryExtraServicesValues: this.store.getters['orderCreation/getDeliveryExtraServicesValues'],
			connector: this.connector,
			context: this.context,
		};

		if (this.stageOnOrderPaid !== null)
		{
			data.stageOnOrderPaid = this.stageOnOrderPaid;
		}
		if (this.stageOnDeliveryFinished !== null)
		{
			data.stageOnDeliveryFinished = this.stageOnDeliveryFinished;
		}

		BX.ajax.runAction('salescenter.order.createPayment', {
			data: {
				basketItems: this.store.getters['orderCreation/getBasket'](),
				options: data,
			},
			analyticsLabel: (this.context === 'deal') ? 'salescenterCreatePaymentSms' : 'salescenterCreatePayment',
			getParameters: {
				dialogId: this.dialogId,
				context: this.context,
				connector: this.connector,
				skipPublicMessage: skipPublicMessage,
			}
		}).then((result) =>
		{
			this.store.dispatch('orderCreation/resetBasket');
			this.stopProgress(buttonEvent);
			if (skipPublicMessage === 'y')
			{
				let notify = {
					content: Loc.getMessage('SALESCENTER_ORDER_CREATE_NOTIFICATION').replace('#ORDER_ID#', result.data.order.number),
				};
				notify.actions = [{
					title: Loc.getMessage('SALESCENTER_VIEW'),
					events: {
						click() {
							Manager.showOrderAdd(result.data.order.id);
						},
					},
				}];
				BX.UI.Notification.Center.notify(notify);
				Manager.showOrdersList({
					orderId: result.data.order.id,
					ownerId: this.ownerId,
					ownerTypeId: this.ownerTypeId,
				});
			}
			else
			{
				this.slider.data.set('action', 'sendPayment');
				this.slider.data.set('order', result.data.order);

				if (result.data.deal)
				{
					this.slider.data.set('deal', result.data.deal);
				}
				this.closeApplication();
			}
			this.emitGlobalEvent('salescenter.app:onpaymentcreated');
		}).catch((data) =>
		{
			data.errors.forEach((error) => {
				alert(error.message);
			});
			this.stopProgress(buttonEvent);
			App.showError(data);
		});
	}

	resendPayment(buttonEvent)
	{
		if (!this.isPaymentCreationAvailable)
		{
			this.closeApplication();
			return null;
		}

		if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress)
		{
			return null;
		}

		this.startProgress(buttonEvent);

		BX.ajax.runAction('salescenter.order.resendPayment', {
			data: {
				orderId: this.orderId,
				paymentId: this.options.paymentId,
				shipmentId: this.options.shipmentId,
				options: {
					sendingMethod: this.sendingMethod,
					sendingMethodDesc: this.sendingMethodDesc,
					stageOnOrderPaid: this.stageOnOrderPaid
				},
			},
			getParameters: {
				context: this.context,
			}
		}).then((result) =>
		{
			this.stopProgress(buttonEvent);
			this.closeApplication();
			this.emitGlobalEvent('salescenter.app:onpaymentresend');
		}).catch((data) =>
		{
			data.errors.forEach((error) => {
				alert(error.message);
			});
			this.stopProgress(buttonEvent);
			App.showError(data);
		});
	}
	hideNoPaymentSystemsBanner()
	{
		const userOptionName = this.options.orderCreationOption || false;
		const userOptionKeyName = this.options.paySystemBannerOptionName || false;
		if (userOptionName && userOptionKeyName)
		{
			BX.userOptions.save('salescenter', userOptionName, userOptionKeyName, 'Y');
		}
	}
	getOrdersCount()
	{
		if(this.sessionId > 0)
		{
			return Rest.callMethod('salescenter.order.getActiveOrdersCount', {
				sessionId: this.sessionId
			});
		}
		else
		{
			return new Promise((resolve, reject) => {});
		}
	}

	getPaymentsCount()
	{
		if (this.sessionId > 0)
		{
			return Rest.callMethod('salescenter.order.getActivePaymentsCount', {
				sessionId: this.sessionId
			});
		}
		else
		{
			return new Promise((resolve, reject) => {});
		}
	}

	emitGlobalEvent(eventName, data)
	{
		EventEmitter.emit(eventName, data);
		BX.SidePanel.Instance.postMessage(this.slider, eventName, data);
	}
}