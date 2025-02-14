this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
(function (exports,mobile_im_application_core,main_date,mobile_pull_client,im_model,im_provider_rest,im_lib_localstorage,im_lib_timer,pull_component_status,ui_vue,im_lib_logger,im_const,im_lib_utils,im_component_dialog,im_view_quotepanel,main_core_events,ui_vue_vuex,ui_vue_components_smiles,im_mixin) {
	'use strict';

	/**
	 * Bitrix Mobile Dialog
	 * Dialog Pull commands (Pull Command Handler)
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2020 Bitrix
	 */
	var MobileImCommandHandler = /*#__PURE__*/function () {
	  babelHelpers.createClass(MobileImCommandHandler, null, [{
	    key: "create",
	    value: function create() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return new this(params);
	    }
	  }]);

	  function MobileImCommandHandler() {
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, MobileImCommandHandler);
	    this.controller = params.controller;
	    this.store = params.store;
	    this.dialog = params.dialog;
	  }

	  babelHelpers.createClass(MobileImCommandHandler, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'im';
	    }
	  }, {
	    key: "handleUserInvite",
	    value: function handleUserInvite(params, extra, command) {
	      var _this = this;

	      if (!params.invited) {
	        setTimeout(function () {
	          _this.dialog.redrawHeader();
	        }, 100);
	      }
	    }
	  }, {
	    key: "handleMessage",
	    value: function handleMessage(params, extra, command) {
	      var currentHeaderName = BXMobileApp.UI.Page.TopBar.title.params.text;
	      var senderId = params.message.senderId;

	      if (params.users[senderId].name !== currentHeaderName) {
	        this.dialog.redrawHeader();
	      }
	    }
	  }]);
	  return MobileImCommandHandler;
	}();

	/**
	 * Bitrix Mobile Dialog
	 * Dialog Rest answers (Rest Answer Handler)
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2019 Bitrix
	 */
	var MobileRestAnswerHandler = /*#__PURE__*/function (_BaseRestHandler) {
	  babelHelpers.inherits(MobileRestAnswerHandler, _BaseRestHandler);

	  function MobileRestAnswerHandler(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, MobileRestAnswerHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MobileRestAnswerHandler).call(this, params));

	    if (babelHelpers.typeof(params.context) === 'object' && params.context) {
	      _this.context = params.context;
	    }

	    return _this;
	  }

	  babelHelpers.createClass(MobileRestAnswerHandler, [{
	    key: "handleImCallGetCallLimitsSuccess",
	    value: function handleImCallGetCallLimitsSuccess(data) {
	      this.store.commit('application/set', {
	        call: {
	          serverEnabled: data.callServerEnabled,
	          maxParticipants: data.maxParticipants
	        }
	      });
	    }
	  }, {
	    key: "handleImChatGetSuccess",
	    value: function handleImChatGetSuccess(data) {
	      this.store.commit('application/set', {
	        dialog: {
	          chatId: data.id,
	          dialogId: data.dialog_id,
	          diskFolderId: data.disk_folder_id
	        }
	      });

	      if (data.restrictions) {
	        this.store.dispatch('dialogues/update', {
	          dialogId: data.dialog_id,
	          fields: data.restrictions
	        });
	      }
	    }
	  }, {
	    key: "handleImChatGetError",
	    value: function handleImChatGetError(error) {
	      if (error.ex.error === 'ACCESS_ERROR') {
	        app.closeController();
	      }
	    }
	  }, {
	    key: "handleMobileBrowserConstGetSuccess",
	    value: function handleMobileBrowserConstGetSuccess(data) {
	      this.store.commit('application/set', {
	        disk: {
	          enabled: true,
	          maxFileSize: data.phpUploadMaxFilesize
	        }
	      });
	      this.context.addLocalize(data);
	      BX.message(data);
	      im_lib_localstorage.LocalStorage.set(this.controller.getSiteId(), 0, 'serverVariables', data || {});
	    }
	  }, {
	    key: "handleImDialogMessagesGetInitSuccess",
	    value: function handleImDialogMessagesGetInitSuccess() {// EventEmitter.emit(EventType.dialog.readVisibleMessages, {chatId: this.controller.application.getChatId()});
	    }
	  }, {
	    key: "handleImMessageAddSuccess",
	    value: function handleImMessageAddSuccess(messageId, message) {
	      this.context.messagesQueue = this.context.messagesQueue.filter(function (el) {
	        return el.id !== message.id;
	      });
	    }
	  }, {
	    key: "handleImMessageAddError",
	    value: function handleImMessageAddError(error, message) {
	      this.context.messagesQueue = this.context.messagesQueue.filter(function (el) {
	        return el.id !== message.id;
	      });
	    }
	  }, {
	    key: "handleImDiskFileCommitSuccess",
	    value: function handleImDiskFileCommitSuccess(result, message) {
	      this.context.messagesQueue = this.context.messagesQueue.filter(function (el) {
	        return el.id !== message.id;
	      });
	    }
	  }]);
	  return MobileRestAnswerHandler;
	}(im_provider_rest.BaseRestHandler);

	var LoadingStatus = {
	  template: "\n\t\t<div class=\"bx-mobilechat-loading-window\">\n\t\t\t<svg class=\"bx-mobilechat-loading-circular\" viewBox=\"25 25 50 50\">\n\t\t\t\t<circle class=\"bx-mobilechat-loading-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t\t<circle class=\"bx-mobilechat-loading-inner-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t</svg>\n\t\t\t<h3 class=\"bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-loading-msg\">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_LOADING')}}</h3>\n\t\t</div>\n\t"
	};

	var ErrorStatus = {
	  computed: babelHelpers.objectSpread({}, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-mobilechat-body\" key=\"error-body\">\n\t\t\t<div class=\"bx-mobilechat-warning-window\">\n\t\t\t\t<div class=\"bx-mobilechat-warning-icon\"></div>\n\t\t\t\t<template v-if=\"application.error.description\"> \n\t\t\t\t\t<div class=\"bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg\" v-html=\"application.error.description\"></div>\n\t\t\t\t</template> \n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-warning-msg\">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_ERROR_TITLE')}}</div>\n\t\t\t\t\t<div class=\"bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg\">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_ERROR_DESC')}}</div>\n\t\t\t\t</template> \n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var EmptyStatus = {
	  template: "\n\t\t<div class=\"bx-mobilechat-loading-window\">\n\t\t\t<h3 class=\"bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-loading-msg\">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_EMPTY')}}</h3>\n\t\t</div>\n\t"
	};

	var MobileSmiles = {
	  methods: {
	    onSelectSmile: function onSelectSmile(event) {
	      this.$emit('selectSmile', event);
	    },
	    onSelectSet: function onSelectSet(event) {
	      this.$emit('selectSet', event);
	    },
	    hideSmiles: function hideSmiles() {
	      this.$emit('hideSmiles');
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\">\n\t\t\t<div class=\"bx-messenger-alert-box bx-livechat-alert-box-zero-padding bx-livechat-form-show\" key=\"vote\">\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideSmiles\"></div>\n\t\t\t\t<div class=\"bx-messenger-smiles-box\">\n\t\t\t\t\t<bx-smiles\n\t\t\t\t\t\t@selectSmile=\"onSelectSmile\"\n\t\t\t\t\t\t@selectSet=\"onSelectSet\"\n\t\t\t\t\t/>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t"
	};

	/**
	 * Bitrix im dialog mobile
	 * Dialog vue component
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2019 Bitrix
	 */
	/**
	 * @notice Do not clone this component! It is under development.
	 */

	ui_vue.BitrixVue.component('bx-mobile-im-component-dialog', {
	  mixins: [im_mixin.DialogCore, im_mixin.DialogReadMessages, im_mixin.DialogQuoteMessage, im_mixin.DialogClickOnCommand, im_mixin.DialogClickOnMention, im_mixin.DialogClickOnUserName, im_mixin.DialogClickOnMessageMenu, im_mixin.DialogClickOnMessageRetry, im_mixin.DialogClickOnUploadCancel, im_mixin.DialogClickOnReadList, im_mixin.DialogSetMessageReaction, im_mixin.DialogOpenMessageReactionList, im_mixin.DialogClickOnKeyboardButton, im_mixin.DialogClickOnChatTeaser, im_mixin.DialogClickOnDialog],
	  components: {
	    LoadingStatus: LoadingStatus,
	    ErrorStatus: ErrorStatus,
	    EmptyStatus: EmptyStatus,
	    MobileSmiles: MobileSmiles
	  },
	  data: function data() {
	    return {
	      dialogState: 'loading'
	    };
	  },
	  computed: babelHelpers.objectSpread({
	    EventType: function EventType() {
	      return im_const.EventType;
	    },
	    localize: function localize() {
	      return ui_vue.BitrixVue.getFilteredPhrases(['MOBILE_CHAT_', 'IM_UTILS_'], this.$root.$bitrixMessages);
	    },
	    widgetClassName: function widgetClassName(state) {
	      var className = ['bx-mobilechat-wrapper'];

	      if (this.showMessageDialog) {
	        className.push('bx-mobilechat-chat-start');
	      }

	      return className.join(' ');
	    },
	    quotePanelData: function quotePanelData() {
	      var result = {
	        id: 0,
	        title: '',
	        description: '',
	        color: ''
	      };

	      if (!this.showMessageDialog || !this.dialog.quoteId) {
	        return result;
	      }

	      var message = this.$store.getters['messages/getMessage'](this.dialog.chatId, this.dialog.quoteId);

	      if (!message) {
	        return result;
	      }

	      var user = this.$store.getters['users/get'](message.authorId);
	      var files = this.$store.getters['files/getList'](this.dialog.chatId);
	      var editId = this.$store.getters['dialogues/getEditId'](this.dialog.dialogId);
	      return {
	        id: this.dialog.quoteId,
	        title: editId ? this.$Bitrix.Loc.getMessage('MOBILE_CHAT_EDIT_TITLE') : message.params.NAME ? message.params.NAME : user ? user.name : '',
	        color: user ? user.color : '',
	        description: im_lib_utils.Utils.text.purify(message.text, message.params, files, this.$Bitrix.Loc.getMessages())
	      };
	    },
	    isDialog: function isDialog() {
	      return im_lib_utils.Utils.dialog.isChatId(this.dialog.dialogId);
	    },
	    isGestureQuoteSupported: function isGestureQuoteSupported() {
	      if (this.dialog && this.dialog.type === 'announcement' && !this.dialog.managerList.includes(this.application.common.userId)) {
	        return false;
	      }

	      return ChatPerformance.isGestureQuoteSupported();
	    },
	    isDarkBackground: function isDarkBackground() {
	      return this.application.options.darkBackground;
	    },
	    showMessageDialog: function showMessageDialog() {
	      var _this = this;

	      var result = this.messageCollection && this.messageCollection.length > 0;
	      var timeout = ChatPerformance.getDialogShowTimeout();

	      if (result) {
	        if (timeout > 0) {
	          clearTimeout(this.dialogStateTimeout);
	          this.dialogStateTimeout = setTimeout(function () {
	            _this.dialogState = 'show';
	          }, timeout);
	        } else {
	          this.dialogState = 'show';
	        }
	      } else if (this.dialog && this.dialog.init) {
	        if (timeout > 0) {
	          clearTimeout(this.dialogStateTimeout);
	          this.dialogStateTimeout = setTimeout(function () {
	            _this.dialogState = 'empty';
	          }, timeout);
	        } else {
	          this.dialogState = 'empty';
	        }
	      } else {
	        this.dialogState = 'loading';
	      }

	      return result;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    },
	    messageCollection: function messageCollection(state) {
	      return state.messages.collection[state.application.dialog.chatId];
	    }
	  })),
	  methods: {
	    getApplication: function getApplication() {
	      return this.$Bitrix.Application.get();
	    },
	    logEvent: function logEvent(name) {
	      for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	        params[_key - 1] = arguments[_key];
	      }

	      im_lib_logger.Logger.info.apply(im_lib_logger.Logger, [name].concat(params));
	    },
	    onDialogRequestHistory: function onDialogRequestHistory(event) {
	      this.getApplication().getDialogHistory(event.lastId);
	    },
	    onDialogRequestUnread: function onDialogRequestUnread(event) {
	      this.getApplication().getDialogUnread(event.lastId);
	    },
	    onClickOnUserName: function onClickOnUserName(_ref) {
	      var event = _ref.data;
	      this.getApplication().replyToUser(event.user.id, event.user);
	    },
	    onClickOnUploadCancel: function onClickOnUploadCancel(_ref2) {
	      var event = _ref2.data;
	      this.getApplication().cancelUploadFile(event.file.id);
	    },
	    onClickOnCommand: function onClickOnCommand(_ref3) {
	      var event = _ref3.data;

	      if (event.type === 'put') {
	        this.getApplication().insertText({
	          text: event.value + ' '
	        });
	      } else if (event.type === 'send') {
	        this.getApplication().addMessage(event.value);
	      } else {
	        im_lib_logger.Logger.warn('Unprocessed command', event);
	      }
	    },
	    onClickOnMention: function onClickOnMention(_ref4) {
	      var event = _ref4.data;

	      if (event.type === 'USER') {
	        this.getApplication().openProfile(event.value);
	      } else if (event.type === 'CHAT') {
	        this.getApplication().openDialog(event.value);
	      } else if (event.type === 'CALL') {
	        this.getApplication().openPhoneMenu(event.value);
	      }
	    },
	    onClickOnMessageMenu: function onClickOnMessageMenu(_ref5) {
	      var event = _ref5.data;
	      im_lib_logger.Logger.warn('Message menu:', event);
	      this.getApplication().openMessageMenu(event.message);
	    },
	    onClickOnMessageRetry: function onClickOnMessageRetry(_ref6) {
	      var event = _ref6.data;
	      im_lib_logger.Logger.warn('Message retry:', event);
	      this.getApplication().retrySendMessage(event.message);
	    },
	    onReadMessage: function onReadMessage(_ref7) {
	      var event = _ref7.data;
	      this.getApplication().readMessage(event.id);
	    },
	    onClickOnReadList: function onClickOnReadList(_ref8) {
	      var event = _ref8.data;
	      this.getApplication().openReadedList(event.list);
	    },
	    onQuoteMessage: function onQuoteMessage(_ref9) {
	      var event = _ref9.data;
	      this.getApplication().quoteMessage(event.message.id);
	    },
	    onSetMessageReaction: function onSetMessageReaction(_ref10) {
	      var event = _ref10.data;
	      this.getApplication().reactMessage(event.message.id, event.reaction);
	    },
	    onOpenMessageReactionList: function onOpenMessageReactionList(_ref11) {
	      var event = _ref11.data;
	      this.getApplication().openMessageReactionList(event.message.id, event.values);
	    },
	    onClickOnKeyboardButton: function onClickOnKeyboardButton(_ref12) {
	      var _this2 = this;

	      var event = _ref12.data;

	      if (event.action === 'ACTION') {
	        var _event$params = event.params,
	            dialogId = _event$params.dialogId,
	            messageId = _event$params.messageId,
	            botId = _event$params.botId,
	            action = _event$params.action,
	            value = _event$params.value;

	        if (action === 'SEND') {
	          this.getApplication().addMessage(value);
	          setTimeout(function () {
	            return main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	              chatId: _this2.chatId,
	              duration: 300,
	              cancelIfScrollChange: false
	            });
	          }, 300);
	        } else if (action === 'PUT') {
	          this.getApplication().insertText({
	            text: value + ' '
	          });
	        } else if (action === 'CALL') {
	          this.getApplication().openPhoneMenu(value);
	        } else if (action === 'COPY') {
	          app.exec("copyToClipboard", {
	            text: value
	          });
	          new BXMobileApp.UI.NotificationBar({
	            message: BX.message("MOBILE_MESSAGE_MENU_COPY_SUCCESS"),
	            color: "#af000000",
	            textColor: "#ffffff",
	            groupId: "clipboard",
	            maxLines: 1,
	            align: "center",
	            isGlobal: true,
	            useCloseButton: true,
	            autoHideTimeout: 1500,
	            hideOnTap: true
	          }, "copy").show();
	        } else if (action === 'DIALOG') {
	          this.getApplication().openDialog(value);
	        }

	        return true;
	      }

	      if (event.action === 'COMMAND') {
	        var _event$params2 = event.params,
	            _dialogId = _event$params2.dialogId,
	            _messageId = _event$params2.messageId,
	            _botId = _event$params2.botId,
	            command = _event$params2.command,
	            params = _event$params2.params;
	        this.$Bitrix.RestClient.get().callMethod(im_const.RestMethod.imMessageCommand, {
	          'MESSAGE_ID': _messageId,
	          'DIALOG_ID': _dialogId,
	          'BOT_ID': _botId,
	          'COMMAND': command,
	          'COMMAND_PARAMS': params
	        });
	        return true;
	      }

	      return false;
	    },
	    onClickOnChatTeaser: function onClickOnChatTeaser(_ref13) {
	      var _this3 = this;

	      var event = _ref13.data;
	      this.$Bitrix.Data.get('controller').application.joinParentChat(event.message.id, 'chat' + event.message.params.CHAT_ID).then(function (dialogId) {
	        _this3.getApplication().openDialog(dialogId);
	      }).catch(function () {});
	    },
	    onClickOnDialog: function onClickOnDialog(_ref14) {//this.getApplication().controller.hideSmiles();

	      var event = _ref14.data;
	    },
	    onQuotePanelClose: function onQuotePanelClose() {
	      this.getApplication().quoteMessageClear();
	    },
	    onSmilesSelectSmile: function onSmilesSelectSmile(event) {
	      console.warn('Smile selected:', event);
	      this.getApplication().insertText({
	        text: event.text
	      });
	    },
	    onSmilesSelectSet: function onSmilesSelectSet() {
	      console.warn('Set selected');
	      this.getApplication().setTextFocus();
	    },
	    onHideSmiles: function onHideSmiles() {
	      //this.getApplication().controller.hideSmiles();
	      this.getApplication().setTextFocus();
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<bx-im-component-dialog\n\t\t\t\t:userId=\"application.common.userId\" \n\t\t\t\t:dialogId=\"application.dialog.dialogId\"\n\t\t\t\t:enableReadMessages=\"application.dialog.enableReadMessages\"\n\t\t\t\t:enableReactions=\"true\"\n\t\t\t\t:enableDateActions=\"false\"\n\t\t\t\t:enableCreateContent=\"false\"\n\t\t\t\t:enableGestureQuote=\"application.options.quoteEnable\"\n\t\t\t\t:enableGestureQuoteFromRight=\"application.options.quoteFromRight\"\n\t\t\t\t:enableGestureMenu=\"true\"\n\t\t\t\t:showMessageUserName=\"isDialog\"\n\t\t\t\t:showMessageAvatar=\"isDialog\"\n\t\t\t\t:showMessageMenu=\"false\"\n\t\t\t\t:skipDataRequest=\"true\"\n\t\t\t />\n\t\t\t<template v-if=\"application.options.showSmiles\">\n\t\t\t\t<MobileSmiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\" @hideSmiles=\"onHideSmiles\" />\t\n\t\t\t</template>\n\t\t</div>\n\t"
	}, {
	  immutable: true
	});

	/**
	 * Bitrix Im mobile
	 * Dialog application
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2020 Bitrix
	 */
	var MobileDialogApplication = /*#__PURE__*/function () {
	  /* region 01. Initialize and store data */
	  function MobileDialogApplication() {
	    var _this = this;

	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, MobileDialogApplication);
	    this.inited = false;
	    this.initPromise = new BX.Promise();
	    this.params = params;
	    this.template = null;
	    this.rootNode = this.params.node || document.createElement('div');
	    this.eventBus = new ui_vue.VueVendorV2();
	    this.timer = new im_lib_timer.Timer();
	    this.messagesQueue = [];
	    this.windowFocused = true; //alert('Pause: open console for debug');

	    this.initCore().then(function () {
	      return _this.initComponentParams();
	    }).then(function (result) {
	      return _this.initMobileEntity(result);
	    }).then(function (result) {
	      return _this.initMobileSettings(result);
	    }).then(function () {
	      return _this.initComponent();
	    }).then(function () {
	      return _this.initEnvironment();
	    }).then(function () {
	      return _this.initMobileEnvironment();
	    }).then(function () {
	      return _this.initPullClient();
	    }).then(function () {
	      return _this.initComplete();
	    });
	  }

	  babelHelpers.createClass(MobileDialogApplication, [{
	    key: "initCore",
	    value: function initCore() {
	      var _this2 = this;

	      return new Promise(function (resolve, reject) {
	        mobile_im_application_core.Core.ready().then(function (controller) {
	          _this2.controller = controller;
	          resolve();
	        });
	      });
	    }
	  }, {
	    key: "initComponentParams",
	    value: function initComponentParams() {
	      return BX.componentParameters.init();
	    }
	  }, {
	    key: "initMobileEntity",
	    value: function initMobileEntity(data) {
	      var _this3 = this;

	      console.log('1. initMobileEntity');
	      return new Promise(function (resolve, reject) {
	        if (data.DIALOG_ENTITY) {
	          data.DIALOG_ENTITY = JSON.parse(data.DIALOG_ENTITY);

	          if (data.DIALOG_TYPE === 'user') {
	            _this3.controller.getStore().dispatch('users/set', data.DIALOG_ENTITY).then(function () {
	              resolve(data);
	            });
	          } else if (data.DIALOG_TYPE === 'chat') {
	            _this3.controller.getStore().dispatch('dialogues/set', data.DIALOG_ENTITY).then(function () {
	              resolve(data);
	            });
	          }
	        } else {
	          resolve(data);
	        }
	      });
	    }
	  }, {
	    key: "initMobileSettings",
	    value: function initMobileSettings(data) {
	      var _this4 = this;

	      console.log('2. initMobileSettings'); // todo change to dynamic storage (LocalStorage web, PageParams for mobile)

	      var serverVariables = im_lib_localstorage.LocalStorage.get(this.controller.getSiteId(), 0, 'serverVariables', false);

	      if (serverVariables) {
	        this.addLocalize(serverVariables);
	      }

	      this.storedEvents = data.STORED_EVENTS || [];
	      return new Promise(function (resolve, reject) {
	        ApplicationStorage.getObject('settings.chat', {
	          quoteEnable: ChatPerformance.isGestureQuoteSupported(),
	          quoteFromRight: false,
	          autoplayVideo: ChatPerformance.isAutoPlayVideoSupported(),
	          backgroundType: 'LIGHT_GRAY'
	        }).then(function (options) {
	          _this4.controller.getStore().commit('application/set', {
	            dialog: {
	              dialogId: data.DIALOG_ID
	            },
	            options: {
	              quoteEnable: options.quoteEnable,
	              quoteFromRight: options.quoteFromRight,
	              autoplayVideo: options.autoplayVideo,
	              darkBackground: ChatDialogBackground && ChatDialogBackground[options.backgroundType] && ChatDialogBackground[options.backgroundType].dark
	            }
	          });

	          resolve();
	        });
	      });
	    }
	  }, {
	    key: "initComponent",
	    value: function initComponent() {
	      var _this5 = this;

	      console.log('3. initComponent');
	      this.controller.application.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));
	      this.controller.addRestAnswerHandler(MobileRestAnswerHandler.create({
	        store: this.controller.getStore(),
	        controller: this.controller,
	        context: this
	      }));
	      var dialog = this.controller.getStore().getters['dialogues/get'](this.controller.application.getDialogId());

	      if (dialog) {
	        this.controller.getStore().commit('application/set', {
	          dialog: {
	            chatId: dialog.chatId,
	            diskFolderId: dialog.diskFolderId || 0
	          }
	        });
	      }

	      return this.controller.createVue(this, {
	        el: this.rootNode,
	        template: "<bx-mobile-im-component-dialog/>"
	      }).then(function (vue) {
	        _this5.template = vue;
	        return new Promise(function (resolve, reject) {
	          return resolve();
	        });
	      });
	    }
	  }, {
	    key: "initEnvironment",
	    value: function initEnvironment() {
	      console.log('4. initEnvironment');
	      this.setTextareaMessage = im_lib_utils.Utils.debounce(this.controller.application.setTextareaMessage, 300, this.controller.application);
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initMobileEnvironment",
	    value: function initMobileEnvironment() {
	      var _this6 = this;

	      console.log('5. initMobileEnvironment');
	      BXMobileApp.UI.Page.Scroll.setEnabled(false);
	      BXMobileApp.UI.Page.captureKeyboardEvents(true);
	      BX.addCustomEvent("onKeyboardWillShow", function () {
	        // EventEmitter.emit(EventType.dialog.beforeMobileKeyboard);
	        _this6.controller.getStore().dispatch('application/set', {
	          mobile: {
	            keyboardShow: true
	          }
	        });
	      });
	      BX.addCustomEvent("onKeyboardDidShow", function () {
	        console.log('Keyboard was showed');
	        main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	          chatId: _this6.controller.application.getChatId(),
	          duration: 300,
	          cancelIfScrollChange: true
	        });
	      });
	      BX.addCustomEvent("onKeyboardWillHide", function () {
	        clearInterval(_this6.keyboardOpeningInterval);

	        _this6.controller.getStore().dispatch('application/set', {
	          mobile: {
	            keyboardShow: false
	          }
	        });
	      });

	      var checkWindowFocused = function checkWindowFocused() {
	        BXMobileApp.UI.Page.isVisible({
	          callback: function callback(data) {
	            _this6.windowFocused = data.status === 'visible';

	            if (_this6.windowFocused) {
	              ui_vue.Vue.event.$emit('bitrixmobile:controller:focus');
	            } else {
	              ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	            }
	          }
	        });
	      };

	      BXMobileApp.addCustomEvent("CallEvents::viewOpened", function () {
	        console.warn('CallView show - disable read message');
	        ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	      });
	      BXMobileApp.addCustomEvent("CallEvents::viewClosed", function () {
	        console.warn('CallView hide - enable read message');
	        checkWindowFocused();
	      });
	      BX.addCustomEvent("onAppActive", function () {
	        checkWindowFocused();
	        BXMobileApp.UI.Page.isVisible({
	          callback: function callback(data) {
	            if (data.status !== 'visible') {
	              return false;
	            }

	            _this6.getDialogUnread().then(function () {
	              _this6.processSendMessages();

	              main_core_events.EventEmitter.emit(im_const.EventType.dialog.readVisibleMessages, {
	                chatId: _this6.controller.application.getChatId()
	              });
	              main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollOnStart, {
	                chatId: _this6.controller.application.getChatId()
	              });
	            }).catch(function () {
	              _this6.processSendMessages();
	            });
	          }
	        });
	      });
	      BX.addCustomEvent("onAppPaused", function () {
	        _this6.windowFocused = false;
	        ui_vue.Vue.event.$emit('bitrixmobile:controller:blur'); //app.closeController();z
	      });
	      BX.addCustomEvent("onOpenPageAfter", checkWindowFocused);
	      BX.addCustomEvent("onHidePageBefore", function () {
	        _this6.windowFocused = false;
	        ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	      });
	      BXMobileApp.addCustomEvent("chatbackground::task::status::success", function (params) {
	        var action = params.taskId.toString().split('|')[0];

	        _this6.executeBackgroundTaskSuccess(action, params);
	      });
	      BXMobileApp.addCustomEvent("chatbackground::task::status::failure", function (params) {
	        var action = params.taskId.toString().split('|')[0];

	        _this6.executeBackgroundTaskFailure(action, params);
	      });
	      BXMobileApp.addCustomEvent("chatrecent::push::get", function (params) {
	        mobile_pull_client.PULL.emit({
	          type: mobile_pull_client.PullClient.SubscriptionType.Server,
	          moduleId: _this6.pullMobileHandler.getModuleId(),
	          data: {
	            command: 'messageAdd',
	            params: babelHelpers.objectSpread({}, params, {
	              optionImportant: true
	            })
	          }
	        });
	      });
	      BXMobileApp.UI.Page.TextPanel.setParams(this.getKeyboardParams());
	      this.changeChatKeyboardStatus();
	      BX.MobileUploadProvider.setListener(this.executeUploaderEvent.bind(this));
	      this.fileUpdateProgress = im_lib_utils.Utils.throttle(function (chatId, fileId, progress, size) {
	        _this6.controller.getStore().dispatch('files/update', {
	          chatId: chatId,
	          id: fileId,
	          fields: {
	            size: size,
	            progress: progress
	          }
	        });
	      }, 500);

	      if (!im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        this.userShowWorkPosition = true;
	        setTimeout(function () {
	          _this6.userShowWorkPosition = false;

	          _this6.redrawHeader();
	        }, 1500);
	        setInterval(function () {
	          _this6.redrawHeader();
	        }, 60000);
	      } else {
	        this.chatShowUserCounter = false;
	        setTimeout(function () {
	          _this6.chatShowUserCounter = true;

	          _this6.redrawHeader();
	        }, 1500);
	      }

	      this.redrawHeader();
	      this.widgetCache = new ChatWidgetCache(this.controller.getUserId(), this.controller.getLanguageId());
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initPullClient",
	    value: function initPullClient() {
	      var _this7 = this;

	      console.log('6. initPullClient');

	      if (this.storedEvents && this.storedEvents.length > 0) {
	        //sort events and get first 50 (to match unread messages cache size)
	        this.storedEvents = this.storedEvents.sort(function (a, b) {
	          return a.message.id - b.message.id;
	        });
	        this.storedEvents = this.storedEvents.slice(0, 50);
	        setTimeout(function () {
	          _this7.storedEvents = _this7.storedEvents.filter(function (event) {
	            BX.onCustomEvent('chatrecent::push::get', [event]);
	            return false;
	          });
	        }, 50);
	      }

	      mobile_pull_client.PULL.subscribe(this.pullMobileHandler = new MobileImCommandHandler({
	        store: this.controller.getStore(),
	        controller: this.controller,
	        dialog: this
	      }));
	      mobile_pull_client.PULL.subscribe({
	        type: BX.PullClient.SubscriptionType.Server,
	        moduleId: 'im',
	        command: 'chatUserLeave',
	        callback: function callback(params) {
	          if (params.userId === _this7.controller.application.getUserId() && params.dialogId === _this7.controller.application.getDialogId()) {
	            app.closeController();
	          }
	        }
	      });
	      mobile_pull_client.PULL.subscribe({
	        type: mobile_pull_client.PullClient.SubscriptionType.Status,
	        callback: this.eventStatusInteraction.bind(this)
	      });

	      if (!im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        mobile_pull_client.PULL.subscribe({
	          type: mobile_pull_client.PullClient.SubscriptionType.Online,
	          callback: this.eventOnlineInteraction.bind(this)
	        });
	      }

	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initComplete",
	    value: function initComplete() {
	      var _this8 = this;

	      console.log('7. init complete');
	      this.controller.getStore().subscribe(function (mutation) {
	        return _this8.eventStoreInteraction(mutation);
	      });
	      this.inited = true;
	      this.initPromise.resolve(this);
	      BXMobileApp.Events.postToComponent("chatdialog::init::complete", [{
	        dialogId: this.controller.application.getDialogId()
	      }, true], 'im.recent');
	      return this.requestData();
	    }
	  }, {
	    key: "ready",
	    value: function ready() {
	      if (this.inited) {
	        var promise = new BX.Promise();
	        promise.resolve(this);
	        return promise;
	      }

	      return this.initPromise;
	    }
	  }, {
	    key: "requestData",
	    value: function requestData() {
	      var _this9 = this;

	      console.log('4. requestData');

	      if (this.requestDataSend) {
	        return this.requestDataSend;
	      }

	      this.timer.start('data', 'load', .5, function () {
	        console.warn("ChatDialog.requestData: slow connection show progress icon");
	        app.titleAction("setParams", {
	          useProgress: true,
	          useLetterImage: false
	        });
	      });
	      this.requestDataSend = new Promise(function (resolve, reject) {
	        var _query;

	        var query = (_query = {}, babelHelpers.defineProperty(_query, im_const.RestMethodHandler.mobileBrowserConstGet, [im_const.RestMethod.mobileBrowserConstGet, {}]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imChatGet, [im_const.RestMethod.imChatGet, {
	          dialog_id: _this9.controller.application.getDialogId()
	        }]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imDialogMessagesGetInit, [im_const.RestMethod.imDialogMessagesGet, {
	          dialog_id: _this9.controller.application.getDialogId(),
	          limit: _this9.controller.application.getRequestMessageLimit(),
	          convert_text: 'Y'
	        }]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imRecentUnread, [im_const.RestMethod.imRecentUnread, {
	          dialog_id: _this9.controller.application.getDialogId(),
	          action: 'N'
	        }]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imCallGetCallLimits, [im_const.RestMethod.imCallGetCallLimits, {}]), _query);

	        if (im_lib_utils.Utils.dialog.isChatId(_this9.controller.application.getDialogId())) {
	          query[im_const.RestMethodHandler.imUserGet] = [im_const.RestMethod.imUserGet, {}];
	        } else {
	          query[im_const.RestMethodHandler.imUserListGet] = [im_const.RestMethod.imUserListGet, {
	            id: [_this9.controller.application.getUserId(), _this9.controller.application.getDialogId()]
	          }];
	        }

	        _this9.controller.restClient.callBatch(query, function (response) {
	          if (!response) {
	            _this9.requestDataSend = null;

	            _this9.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	            resolve();
	            return false;
	          }

	          var constGet = response[im_const.RestMethodHandler.mobileBrowserConstGet];

	          if (constGet.error()) {
	            console.warn('Error load dialog', constGet.error().ex.error, constGet.error().ex.error_description);
	            console.warn('Try connect...');
	            setTimeout(function () {
	              return _this9.requestData();
	            }, 5000);
	          } else {
	            _this9.controller.executeRestAnswer(im_const.RestMethodHandler.mobileBrowserConstGet, constGet);
	          }

	          var callLimits = response[im_const.RestMethodHandler.imCallGetCallLimits];

	          if (callLimits && !callLimits.error()) {
	            _this9.controller.executeRestAnswer(im_const.RestMethodHandler.imCallGetCallLimits, callLimits);
	          }

	          var userGet = response[im_const.RestMethodHandler.imUserGet];

	          if (userGet && !userGet.error()) {
	            _this9.controller.executeRestAnswer(im_const.RestMethodHandler.imUserGet, userGet);
	          }

	          var userListGet = response[im_const.RestMethodHandler.imUserListGet];

	          if (userListGet && !userListGet.error()) {
	            _this9.controller.executeRestAnswer(im_const.RestMethodHandler.imUserListGet, userListGet);
	          }

	          var chatGetResult = response[im_const.RestMethodHandler.imChatGet];

	          _this9.controller.executeRestAnswer(im_const.RestMethodHandler.imChatGet, chatGetResult);

	          _this9.redrawHeader();

	          var dialogMessagesGetResult = response[im_const.RestMethodHandler.imDialogMessagesGetInit];

	          if (dialogMessagesGetResult.error()) ; else {
	            app.titleAction("setParams", {
	              useProgress: false,
	              useLetterImage: true
	            });

	            _this9.timer.stop('data', 'load', true);

	            _this9.controller.getStore().dispatch('dialogues/saveDialog', {
	              dialogId: _this9.controller.application.getDialogId(),
	              chatId: _this9.controller.application.getChatId()
	            });

	            if (_this9.controller.pullBaseHandler) {
	              _this9.controller.pullBaseHandler.option.skip = false;
	            }

	            _this9.controller.getStore().dispatch('application/set', {
	              dialog: {
	                enableReadMessages: true
	              }
	            }).then(function () {
	              _this9.controller.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGetInit, dialogMessagesGetResult);
	            });

	            _this9.processSendMessages();
	          }

	          _this9.requestDataSend = null;
	          resolve();
	        }, false, false, im_lib_utils.Utils.getLogTrackingParams({
	          name: 'mobile.im.dialog',
	          dialog: _this9.controller.application.getDialogData()
	        }));
	      });
	      return this.requestDataSend;
	    }
	  }, {
	    key: "executeUploaderEvent",
	    value: function executeUploaderEvent(eventName, eventData, taskId) {
	      if (eventName !== BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS) {
	        console.log("ChatDialog.disk.eventRouter: ", eventName, taskId, eventData);
	      }

	      if (eventName === BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS) {
	        if (eventData.percent > 95) {
	          eventData.percent = 95;
	        }

	        this.fileUpdateProgress(eventData.file.params.chatId, eventData.file.params.file.id, eventData.percent, eventData.byteTotal);
	      } else if (eventName === BX.MobileUploaderConst.FILE_CREATED) {
	        if (eventData.result.status === 'error') {
	          this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
	          console.error('File upload error', eventData.result.errors[0].message);
	        } else {
	          this.controller.getStore().dispatch('files/update', {
	            chatId: eventData.file.params.chatId,
	            id: eventData.file.params.file.id,
	            fields: {
	              status: im_const.FileStatus.wait,
	              progress: 95
	            }
	          });
	        }
	      } else if (eventName === 'onimdiskmessageaddsuccess') {
	        console.info('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_SUCCESS: ', eventData, taskId);
	        var file = eventData.result.FILES['upload' + eventData.result.DISK_ID[0]];
	        this.controller.getStore().dispatch('files/update', {
	          chatId: eventData.file.params.chatId,
	          id: eventData.file.params.file.id,
	          fields: {
	            status: im_const.FileStatus.upload,
	            progress: 100,
	            id: file.id,
	            size: file.size,
	            urlDownload: file.urlDownload,
	            urlPreview: file.urlPreview,
	            urlShow: file.urlShow
	          }
	        });
	      } else if (eventName === 'onimdiskmessageaddfail') {
	        console.error('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_FAIL: ', eventData, taskId);
	        this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
	      } else if (eventName === BX.MobileUploaderConst.TASK_CANCELLED || eventName === BX.MobileUploaderConst.TASK_NOT_FOUND) {
	        this.cancelUploadFile(eventData.file.params.file.id);
	      } else if (eventName === BX.MobileUploaderConst.FILE_CREATED_FAILED || eventName === BX.MobileUploaderConst.FILE_UPLOAD_FAILED || eventName === BX.MobileUploaderConst.FILE_READ_ERROR || eventName === BX.MobileUploaderConst.TASK_STARTED_FAILED) {
	        console.error('ChatDialog.disk.eventRouter: ', eventName, eventData, taskId);
	        this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
	      }

	      return true;
	    }
	  }, {
	    key: "prepareFileData",
	    value: function prepareFileData(files) {
	      var prepareFunction = function prepareFunction(file) {
	        if (file.urlPreview && file.urlPreview.startsWith('file://')) {
	          file.urlPreview = 'bx' + file.urlPreview;
	        }

	        if (file.urlShow && file.urlShow.startsWith('file://')) {
	          file.urlShow = 'bx' + file.urlShow;
	        }

	        if (file.type !== im_const.FileType.image) {
	          return file;
	        }

	        if (file.urlPreview) {
	          if (file.urlPreview.startsWith('/')) {
	            file.urlPreview = currentDomain + file.urlPreview;
	          }

	          file.urlPreview = file.urlPreview.replace('http://', 'bxhttp://').replace('https://', 'bxhttps://');
	        }

	        if (file.urlShow) {
	          if (file.urlShow.startsWith('/')) {
	            file.urlShow = currentDomain + file.urlShow;
	          }

	          file.urlShow = file.urlShow.replace('http://', 'bxhttp://').replace('https://', 'bxhttps://');
	        }

	        return file;
	      };

	      if (files instanceof Array) {
	        return files.map(function (file) {
	          return prepareFunction(file);
	        });
	      } else {
	        return prepareFunction(files);
	      }
	    }
	    /* endregion 01. Initialize and store data */

	    /* region 02. Mobile environment methods */

	  }, {
	    key: "redrawHeader",
	    value: function redrawHeader() {
	      var _this10 = this;

	      var headerProperties;

	      if (im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        headerProperties = this.getChatHeaderParams();
	        this.changeChatKeyboardStatus();
	      } else {
	        headerProperties = this.getUserHeaderParams();
	      }

	      if (!headerProperties) {
	        return false;
	      }

	      this.setHeaderButtons();

	      if (!this.headerMenuInited) {
	        BXMobileApp.UI.Page.TopBar.title.params.useLetterImage = true;
	        BXMobileApp.UI.Page.TopBar.title.setCallback(function () {
	          return _this10.openHeaderMenu();
	        });
	        this.headerMenuInited = true;
	      }

	      if (headerProperties.name) {
	        BXMobileApp.UI.Page.TopBar.title.setText(headerProperties.name);
	      }

	      if (headerProperties.desc) {
	        BXMobileApp.UI.Page.TopBar.title.setDetailText(headerProperties.desc);
	      }

	      if (headerProperties.avatar) {
	        BXMobileApp.UI.Page.TopBar.title.setImage(headerProperties.avatar);
	      } else if (headerProperties.color) {
	        //BXMobileApp.UI.Page.TopBar.title.setImageColor(dialog.color);
	        BXMobileApp.UI.Page.TopBar.title.params.imageColor = headerProperties.color;
	      }

	      return true;
	    }
	  }, {
	    key: "getUserHeaderParams",
	    value: function getUserHeaderParams() {
	      var user = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId());

	      if (!user || !user.init) {
	        return false;
	      }

	      var result = {
	        'name': null,
	        'desc': null,
	        'avatar': null,
	        'color': null
	      };

	      if (user.avatar) {
	        result.avatar = user.avatar;
	      } else {
	        result.color = user.color;
	      }

	      result.name = user.name;
	      var showLastDate = false;

	      if (!this.userShowWorkPosition && user.lastActivityDate) {
	        showLastDate = im_lib_utils.Utils.user.getLastDateText(user, this.getLocalize());
	      }

	      if (showLastDate) {
	        result.desc = showLastDate;
	      } else {
	        if (user.workPosition) {
	          result.desc = user.workPosition;
	        } else {
	          result.desc = this.getLocalize('MOBILE_HEADER_MENU_CHAT_TYPE_USER');
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "getChatHeaderParams",
	    value: function getChatHeaderParams() {
	      var dialog = this.controller.getStore().getters['dialogues/get'](this.controller.application.getDialogId());

	      if (!dialog || !dialog.init) {
	        return false;
	      }

	      var result = {
	        'name': null,
	        'desc': null,
	        'avatar': null,
	        'color': null
	      };

	      if (dialog.avatar) {
	        result.avatar = dialog.avatar;
	      } else {
	        result.color = dialog.color;
	      }

	      if (dialog.entityType === 'GENERAL') {
	        result.avatar = encodeURI(this.controller.getHost() + "/bitrix/mobileapp/mobile/components/bitrix/im.recent/images/avatar_general_x3.png");
	      }

	      result.name = dialog.name;
	      var chatTypeTitle = this.getLocalize('MOBILE_HEADER_MENU_CHAT_TYPE_CHAT_NEW');

	      if (this.chatShowUserCounter && this.getLocalize()['MOBILE_HEADER_MENU_CHAT_USER_COUNT']) {
	        chatTypeTitle = this.getLocalize('MOBILE_HEADER_MENU_CHAT_USER_COUNT').replace('#COUNT#', dialog.userCounter);
	      } else if (this.getLocalize()['MOBILE_HEADER_MENU_CHAT_TYPE_' + dialog.type.toUpperCase() + '_NEW']) {
	        chatTypeTitle = this.getLocalize('MOBILE_HEADER_MENU_CHAT_TYPE_' + dialog.type.toUpperCase() + '_NEW');
	      }

	      result.desc = chatTypeTitle;
	      return result;
	    }
	  }, {
	    key: "changeChatKeyboardStatus",
	    value: function changeChatKeyboardStatus() {
	      var dialog = this.controller.getStore().getters['dialogues/get'](this.controller.application.getDialogId());

	      if (!dialog || !dialog.init) {
	        BXMobileApp.UI.Page.TextPanel.show();
	        return true;
	      }

	      var keyboardShow = true;

	      if (dialog.type === 'announcement' && !dialog.managerList.includes(this.controller.application.getUserId())) {
	        keyboardShow = false;
	      }

	      if (typeof this.keyboardShowFlag !== 'undefined' && (this.keyboardShowFlag && keyboardShow || !this.keyboardShowFlag && !keyboardShow)) {
	        return this.keyboardShowFlag;
	      }

	      if (keyboardShow) {
	        BXMobileApp.UI.Page.TextPanel.show();
	        this.keyboardShowFlag = true;
	      } else {
	        BXMobileApp.UI.Page.TextPanel.hide();
	        this.keyboardShowFlag = false;
	      }

	      return this.keyboardShowFlag;
	    }
	  }, {
	    key: "setHeaderButtons",
	    value: function setHeaderButtons() {
	      var _this11 = this;

	      if (this.callMenuSetted) {
	        return true;
	      }

	      if (im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        var dialogData = this.controller.application.getDialogData();

	        if (!dialogData.init) {
	          return false;
	        }

	        var isAvailableChatCall = Application.getApiVersion() >= 36;
	        var maxParticipants = this.controller.application.getData().call.maxParticipants;

	        if (dialogData.userCounter > maxParticipants || !isAvailableChatCall || dialogData.entityType === 'VIDEOCONF' && dialogData.entityData1 === 'BROADCAST') {
	          if (dialogData.type !== im_const.DialogType.call && dialogData.restrictions.extend) {
	            app.exec("setRightButtons", {
	              items: [{
	                type: "user_plus",
	                callback: function callback() {
	                  fabric.Answers.sendCustomEvent("vueChatAddUserButton", {});

	                  _this11.openAddUserDialog();
	                }
	              }]
	            });
	          } else {
	            app.exec("setRightButtons", {
	              items: []
	            });
	          }

	          this.callMenuSetted = true;
	          return true;
	        }
	      } else {
	        var userData = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true);

	        if (!userData.init) {
	          return false;
	        }

	        if (!userData || userData.bot || userData.network || this.controller.application.getUserId() === parseInt(this.controller.application.getDialogId())) {
	          app.exec("setRightButtons", {
	            items: []
	          });
	          this.callMenuSetted = true;
	          return true;
	        }
	      }

	      app.exec("setRightButtons", {
	        items: [{
	          type: "call_audio",
	          callback: function callback() {
	            if (im_lib_utils.Utils.dialog.isChatId(_this11.controller.application.getDialogId())) {
	              BXMobileApp.Events.postToComponent("onCallInvite", {
	                dialogId: _this11.controller.application.getDialogId(),
	                video: false,
	                chatData: _this11.controller.application.getDialogData()
	              }, "calls");
	            } else {
	              var _userData = _this11.controller.getStore().getters['users/get'](_this11.controller.application.getDialogId(), true);

	              BXMobileApp.Events.postToComponent("onCallInvite", {
	                userId: _this11.controller.application.getDialogId(),
	                video: false,
	                userData: babelHelpers.defineProperty({}, _userData.id, _userData)
	              }, "calls");
	            }
	          }
	        }, {
	          type: "call_video",
	          badgeCode: "call_video",
	          callback: function callback() {
	            fabric.Answers.sendCustomEvent("vueChatCallVideoButton", {});

	            if (im_lib_utils.Utils.dialog.isChatId(_this11.controller.application.getDialogId())) {
	              BXMobileApp.Events.postToComponent("onCallInvite", {
	                dialogId: _this11.controller.application.getDialogId(),
	                video: true,
	                chatData: _this11.controller.application.getDialogData()
	              }, "calls");
	            } else {
	              BXMobileApp.Events.postToComponent("onCallInvite", {
	                dialogId: _this11.controller.application.getDialogId(),
	                video: true,
	                userData: babelHelpers.defineProperty({}, _this11.controller.application.getDialogId(), _this11.controller.getStore().getters['users/get'](_this11.controller.application.getDialogId(), true))
	              }, "calls");
	            }
	          }
	        }]
	      });
	      this.callMenuSetted = true;
	      return true;
	    }
	  }, {
	    key: "openUserList",
	    value: function openUserList() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var _params$users = params.users,
	          users = _params$users === void 0 ? false : _params$users,
	          _params$title = params.title,
	          title = _params$title === void 0 ? '' : _params$title,
	          _params$listType = params.listType,
	          listType = _params$listType === void 0 ? 'LIST' : _params$listType,
	          _params$backdrop = params.backdrop,
	          backdrop = _params$backdrop === void 0 ? true : _params$backdrop;
	      var settings = {
	        title: title,
	        objectName: "ChatUserListInterface"
	      };

	      if (backdrop) {
	        settings.backdrop = {};
	      }

	      app.exec("openComponent", {
	        name: "JSStackComponent",
	        componentCode: 'im.dialog.list',
	        scriptPath: "/mobileapp/jn/im.chat.user.list/?version=" + BX.componentParameters.get('WIDGET_CHAT_USERS_VERSION', '1.0.0'),
	        params: {
	          "DIALOG_ID": this.controller.application.getDialogId(),
	          "DIALOG_OWNER_ID": this.controller.application.getDialogData().ownerId,
	          "USER_ID": this.controller.application.getUserId(),
	          "LIST_TYPE": listType,
	          "USERS": users,
	          "IS_BACKDROP": true
	        },
	        rootWidget: {
	          name: "list",
	          settings: settings
	        }
	      }, false);
	    }
	  }, {
	    key: "openCallMenu",
	    value: function openCallMenu() {
	      var _this12 = this;

	      fabric.Answers.sendCustomEvent("vueChatCallAudioButton", {});
	      var userData = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true);

	      if (userData.phones.personalMobile || userData.phones.workPhone || userData.phones.personalPhone || userData.phones.innerPhone) {
	        BackdropMenu.create('im.dialog.menu.call|' + this.controller.application.getDialogId()).setItems([BackdropMenuItem.create('audio').setTitle(this.getLocalize('MOBILE_HEADER_MENU_AUDIO_CALL')), BackdropMenuItem.create('personalMobile').setTitle(userData.phones.personalMobile).setSubTitle(this.getLocalize('MOBILE_MENU_CALL_MOBILE')).skip(!userData.phones.personalMobile), BackdropMenuItem.create('workPhone').setTitle(userData.phones.workPhone).setSubTitle(this.getLocalize('MOBILE_MENU_CALL_WORK')).skip(!userData.phones.workPhone), BackdropMenuItem.create('personalPhone').setTitle(userData.phones.personalPhone).setSubTitle(this.getLocalize('MOBILE_MENU_CALL_PHONE')).skip(!userData.phones.personalPhone), BackdropMenuItem.create('innerPhone').setTitle(userData.phones.innerPhone).setSubTitle(this.getLocalize('MOBILE_MENU_CALL_PHONE')).skip(!userData.phones.innerPhone)]).setVersion(BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0')).setEventListener(function (name, params, user, backdrop) {
	          if (name !== 'selected') {
	            return false;
	          }

	          if (params.id === 'audio') {
	            BXMobileApp.Events.postToComponent("onCallInvite", {
	              userId: _this12.controller.application.getDialogId(),
	              video: false,
	              userData: babelHelpers.defineProperty({}, user.id, user)
	            }, "calls");
	          } else if (params.id === 'innerPhone') {
	            BX.MobileTools.phoneTo(user.phones[params.id], {
	              callMethod: 'telephony'
	            });
	          } else {
	            BX.MobileTools.phoneTo(user.phones[params.id], {
	              callMethod: 'device'
	            }); // items options
	            //.setType(BackdropMenuItemType.menu)
	            //.disableClose(BX.MobileTools.canUseTelephony())
	            // if (!BX.MobileTools.canUseTelephony())
	            // {
	            // 	BX.MobileTools.phoneTo(user.phones[params.id], {callMethod: 'device'});
	            // 	return false
	            // }
	            //
	            // let subMenu = BackdropMenu
	            // 	.create('im.dialog.menu.call.submenu|'+this.controller.application.getDialogId())
	            // 	.setItems([
	            // 		BackdropMenuItem.create('number')
	            // 			.setType(BackdropMenuItemType.info)
	            // 			.setTitle(this.getLocalize("MOBILE_MENU_CALL_TO")
	            // 			.replace('#PHONE_NUMBER#', user.phones[params.id]))
	            // 			.setHeight(50)
	            // 			.setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setFontStyle('bold')))
	            // 			.setDisabled(),
	            // 		BackdropMenuItem.create('telephony').setTitle(this.getLocalize("MOBILE_CALL_BY_B24")),
	            // 		BackdropMenuItem.create('device').setTitle(this.getLocalize("MOBILE_CALL_BY_MOBILE")),
	            // 	])
	            // 	.setEventListener((name, params, options, backdrop) =>
	            // 	{
	            // 		if (name !== 'selected')
	            // 		{
	            // 			return false;
	            // 		}
	            // 		BX.MobileTools.phoneTo(options.phone, {callMethod: params.id});
	            // 	})
	            // 	.setCustomParams({phone: user.phones[params.id]})
	            // ;
	            // backdrop.showSubMenu(subMenu);
	          }
	        }).setCustomParams(userData).show();
	      } else {
	        BXMobileApp.Events.postToComponent("onCallInvite", {
	          userId: this.controller.application.getDialogId(),
	          video: false,
	          userData: babelHelpers.defineProperty({}, this.controller.application.getDialogId(), userData)
	        }, "calls");
	      }
	    }
	  }, {
	    key: "leaveChat",
	    value: function leaveChat() {
	      var _this13 = this;

	      var confirm = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

	      if (!confirm) {
	        app.confirm({
	          title: this.getLocalize('MOBILE_HEADER_MENU_LEAVE_CONFIRM'),
	          text: '',
	          buttons: [this.getLocalize('MOBILE_HEADER_MENU_LEAVE_YES'), this.getLocalize('MOBILE_HEADER_MENU_LEAVE_NO')],
	          callback: function callback(button) {
	            if (button === 1) {
	              _this13.leaveChat(true);
	            }
	          }
	        });
	        return true;
	      }

	      var dialogId = this.controller.application.getDialogId();
	      this.controller.restClient.callMethod(im_const.RestMethod.imChatLeave, {
	        DIALOG_ID: dialogId
	      }, null, null, im_lib_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imChatLeave,
	        dialog: this.controller.application.getDialogData(dialogId)
	      })).then(function (response) {
	        app.closeController();
	      });
	    }
	  }, {
	    key: "openAddUserDialog",
	    value: function openAddUserDialog() {
	      var listUsers = this.getItemsForAddUserDialog();
	      app.exec("openComponent", {
	        name: "JSStackComponent",
	        componentCode: "im.chat.user.selector",
	        scriptPath: "/mobileapp/jn/im.chat.user.selector/?version=" + BX.componentParameters.get('WIDGET_CHAT_RECIPIENTS_VERSION', '1.0.0'),
	        params: {
	          "DIALOG_ID": this.controller.application.getDialogId(),
	          "USER_ID": this.controller.application.getUserId(),
	          "LIST_USERS": listUsers,
	          "LIST_DEPARTMENTS": [],
	          "SKIP_LIST": [],
	          "SEARCH_MIN_SIZE": BX.componentParameters.get('SEARCH_MIN_TOKEN_SIZE', 3)
	        },
	        rootWidget: {
	          name: "chat.recipients",
	          settings: {
	            objectName: "ChatUserSelectorInterface",
	            title: BX.message('MOBILE_HEADER_MENU_USER_ADD'),
	            limit: 100,
	            items: listUsers.map(function (element) {
	              return ChatDataConverter.getListElementByUser(element);
	            }),
	            scopes: [{
	              title: BX.message('MOBILE_SCOPE_USERS'),
	              id: "user"
	            }, {
	              title: BX.message('MOBILE_SCOPE_DEPARTMENTS'),
	              id: "department"
	            }],
	            backdrop: {
	              showOnTop: true
	            }
	          }
	        }
	      }, false);
	    }
	  }, {
	    key: "getItemsForAddUserDialog",
	    value: function getItemsForAddUserDialog() {
	      var items = [];
	      var itemsIndex = {};

	      if (this.widgetCache.recentList.length > 0) {
	        this.widgetCache.recentList.map(function (element) {
	          if (!element || itemsIndex[element.id]) {
	            return false;
	          }

	          if (element.type !== 'user') {
	            return false;
	          }

	          if (element.user.network || element.user.connector) {
	            return false;
	          }

	          items.push(element.user);
	          itemsIndex[element.id] = true;
	          return true;
	        });
	      }

	      this.widgetCache.colleaguesList.map(function (element) {
	        if (!element || itemsIndex[element.id]) {
	          return false;
	        }

	        if (element.network || element.connector) {
	          return false;
	        }

	        items.push(element);
	        itemsIndex[element.id] = true;
	      });
	      this.widgetCache.lastSearchList.map(function (element) {
	        if (!element || itemsIndex[element.id]) {
	          return false;
	        }

	        if (!element) {
	          return false;
	        }

	        if (element.type !== 'user') {
	          return false;
	        }

	        if (element.user.network || element.user.connector) {
	          return false;
	        }

	        items.push(element.user);
	        itemsIndex[element.id] = true;
	        return true;
	      });
	      /*
	      let skipList = ChatMessengerCommon.getChatUsers();
	      if (skipList.indexOf(this.base.userId) === -1)
	      {
	      	skipList.push(this.base.userId)
	      }
	      items = items.filter((element) => skipList.indexOf(element.id) === -1);
	      */

	      return items;
	    }
	  }, {
	    key: "eventStoreInteraction",

	    /* endregion 02. Mobile environment methods */

	    /* region 02. Push & Pull */
	    value: function eventStoreInteraction(data) {
	      var _this14 = this;

	      if (data.type === 'dialogues/update' && data.payload && data.payload.fields) {
	        if (data.payload.dialogId !== this.controller.application.getDialogId()) {
	          return;
	        }

	        if (typeof data.payload.fields.name !== 'undefined' || typeof data.payload.fields.userCounter !== 'undefined') {
	          if (typeof data.payload.fields.userCounter !== 'undefined') {
	            this.callMenuSetted = false;
	          }

	          this.redrawHeader();
	        }

	        if (typeof data.payload.fields.counter !== 'undefined' && typeof data.payload.dialogId !== 'undefined') {
	          BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
	            dialogId: data.payload.dialogId,
	            counter: data.payload.fields.counter
	          }, true], 'im.recent');
	        }
	      } else if (data.type === 'dialogues/set') {
	        data.payload.forEach(function (dialog) {
	          if (dialog.dialogId !== _this14.controller.application.getDialogId()) {
	            return;
	          }

	          BXMobileApp.Events.postToComponent("chatdialog::counter::change", [{
	            dialogId: dialog.dialogId,
	            counter: dialog.counter
	          }, true], 'im.recent');
	        });
	      }
	    }
	  }, {
	    key: "eventStatusInteraction",
	    value: function eventStatusInteraction(data) {
	      var _this15 = this;

	      if (data.status === mobile_pull_client.PullClient.PullStatus.Online) {
	        if (this.pullRequestMessage) {
	          this.controller.pullBaseHandler.option.skip = true;
	          this.getDialogUnread().then(function () {
	            _this15.controller.pullBaseHandler.option.skip = false;

	            _this15.processSendMessages();

	            main_core_events.EventEmitter.emit(im_const.EventType.dialog.readVisibleMessages, {
	              chatId: _this15.controller.application.getChatId()
	            });
	            main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollOnStart, {
	              chatId: _this15.controller.application.getChatId()
	            });
	          }).catch(function () {
	            _this15.controller.pullBaseHandler.option.skip = false;

	            _this15.processSendMessages();
	          });
	          this.pullRequestMessage = false;
	        } else {
	          this.readMessage();
	          this.processSendMessages();
	        }
	      } else if (data.status === mobile_pull_client.PullClient.PullStatus.Offline) {
	        this.pullRequestMessage = true;
	      }
	    }
	  }, {
	    key: "eventOnlineInteraction",
	    value: function eventOnlineInteraction(data) {
	      if (data.command === 'list' || data.command === 'userStatus') {
	        for (var userId in data.params.users) {
	          if (!data.params.users.hasOwnProperty(userId)) {
	            continue;
	          }

	          this.controller.getStore().dispatch('users/update', {
	            id: data.params.users[userId].id,
	            fields: data.params.users[userId]
	          });

	          if (userId.toString() === this.controller.application.getDialogId()) {
	            this.redrawHeader();
	          }
	        }
	      }
	    }
	    /* endregion 02. Push & Pull */

	  }, {
	    key: "getKeyboardParams",
	    value: function getKeyboardParams() {
	      var _this16 = this;

	      var dialogData = this.controller.application.getDialogData();
	      var siteDir = this.getLocalize('SITE_DIR');
	      return {
	        text: dialogData ? dialogData.textareaMessage : '',
	        placeholder: this.getLocalize('MOBILE_CHAT_PANEL_PLACEHOLDER'),
	        smileButton: {},
	        useImageButton: true,
	        useAudioMessages: true,
	        mentionDataSource: {
	          outsection: "NO",
	          url: siteDir + "mobile/index.php?mobile_action=get_user_list&use_name_format=Y&with_bots"
	        },
	        attachFileSettings: {
	          previewMaxWidth: 640,
	          previewMaxHeight: 640,
	          resize: {
	            targetWidth: -1,
	            targetHeight: -1,
	            sourceType: 1,
	            encodingType: 0,
	            mediaType: 2,
	            allowsEdit: false,
	            saveToPhotoAlbum: true,
	            popoverOptions: false,
	            cameraDirection: 0
	          },
	          sendFileSeparately: true,
	          showAttachedFiles: true,
	          editingMediaFiles: false,
	          maxAttachedFilesCount: 100
	        },
	        attachButton: {
	          items: [{
	            id: "disk",
	            name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_DISK"),
	            dataSource: {
	              multiple: false,
	              url: siteDir + "mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=" + this.controller.application.getUserId(),
	              TABLE_SETTINGS: {
	                searchField: true,
	                showtitle: true,
	                modal: true,
	                name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_DISK_FILES")
	              }
	            }
	          }, {
	            id: "mediateka",
	            name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_GALLERY")
	          }, {
	            id: "camera",
	            name: this.getLocalize("MOBILE_CHAT_PANEL_UPLOAD_CAMERA")
	          }]
	        },
	        action: function action(data) {
	          if (typeof data === "string") {
	            data = {
	              text: data,
	              attachedFiles: []
	            };
	          }

	          var text = data.text.toString().trim();
	          var attachedFiles = data.attachedFiles instanceof Array ? data.attachedFiles : [];

	          if (attachedFiles.length <= 0) {
	            _this16.clearText();

	            _this16.hideSmiles();

	            var editId = _this16.controller.getStore().getters['dialogues/getEditId'](_this16.controller.application.getDialogId());

	            if (editId) {
	              _this16.updateMessage(editId, text);
	            } else {
	              _this16.addMessage(text);
	            }
	          } else {
	            attachedFiles.forEach(function (file) {
	              // disk
	              if (typeof file.dataAttributes !== 'undefined') {
	                fabric.Answers.sendCustomEvent("vueChatFileDisk", {});
	                return _this16.uploadFile({
	                  source: 'disk',
	                  name: file.name,
	                  type: file.type.toString().toLowerCase(),
	                  preview: !file.dataAttributes.URL || !file.dataAttributes.URL.PREVIEW ? null : {
	                    url: file.dataAttributes.URL.PREVIEW,
	                    width: file.dataAttributes.URL.PREVIEW.match(/(width=(\d+))/i)[2],
	                    height: file.dataAttributes.URL.PREVIEW.match(/(height=(\d+))/i)[2]
	                  },
	                  uploadLink: parseInt(file.dataAttributes.ID)
	                });
	              } // audio


	              if (file.type === 'audio/mp4') {
	                fabric.Answers.sendCustomEvent("vueChatFileAudio", {});
	                return _this16.uploadFile({
	                  source: 'audio',
	                  name: 'mobile_audio_' + new Date().toJSON().slice(0, 19).replace('T', '_').split(':').join('-') + '.mp3',
	                  type: 'mp3',
	                  preview: null,
	                  uploadLink: file.url
	                });
	              }

	              var filename = file.name;
	              var fileType = im_model.FilesModel.getType(file.name);

	              if (fileType === im_const.FileType.video) {
	                fabric.Answers.sendCustomEvent("vueChatFileVideo", {});
	              } else if (fileType === im_const.FileType.image) {
	                fabric.Answers.sendCustomEvent("vueChatFileImage", {});
	              } else {
	                fabric.Answers.sendCustomEvent("vueChatFileOther", {});
	              }

	              if (fileType === im_const.FileType.image || fileType === im_const.FileType.video) {
	                var extension = file.name.split('.').slice(-1)[0].toLowerCase();

	                if (file.type === 'image/heic') {
	                  extension = 'jpg';
	                }

	                filename = 'mobile_file_' + new Date().toJSON().slice(0, 19).replace('T', '_').split(':').join('-') + '.' + extension;
	              } // file


	              return _this16.uploadFile({
	                source: 'gallery',
	                name: filename,
	                type: file.type.toString().toLowerCase(),
	                preview: !file.previewUrl ? null : {
	                  url: file.previewUrl,
	                  width: file.previewWidth,
	                  height: file.previewHeight
	                },
	                uploadLink: file.url
	              });
	            });
	          }
	        },
	        callback: function callback(data) {
	          console.log('Textpanel callback', data);

	          if (!data.event) {
	            return false;
	          }

	          if (data.event === "onKeyPress") {
	            var text = data.text.toString();

	            if (text.trim().length > 2) {
	              _this16.controller.application.startWriting();
	            }

	            if (text.length === 0) {
	              _this16.setTextareaMessage({
	                message: ''
	              });

	              _this16.controller.application.stopWriting();
	            } else {
	              _this16.setTextareaMessage({
	                message: text
	              });
	            }
	          } else if (data.event === "onSmileSelect") {
	            _this16.controller.showSmiles();
	          } else if (Application.getPlatform() !== "android") {
	            if (data.event === "getFocus") {
	              if (im_lib_utils.Utils.platform.isIos() && im_lib_utils.Utils.platform.getIosVersion() > 12) {
	                main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	                  chatId: _this16.controller.application.getChatId(),
	                  duration: 300,
	                  cancelIfScrollChange: true
	                });
	              }
	            } else if (data.event === "removeFocus") ;
	          }
	        }
	      };
	    }
	    /* region 04. Rest methods */

	  }, {
	    key: "addMessage",
	    value: function addMessage(text) {
	      var _this17 = this;

	      var file = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

	      if (!text && !file) {
	        return false;
	      }

	      var quiteId = this.controller.getStore().getters['dialogues/getQuoteId'](this.controller.application.getDialogId());

	      if (quiteId) {
	        var quoteMessage = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), quiteId);

	        if (quoteMessage) {
	          var user = null;

	          if (quoteMessage.authorId) {
	            user = this.controller.getStore().getters['users/get'](quoteMessage.authorId);
	          }

	          var files = this.controller.getStore().getters['files/getList'](this.controller.application.getChatId());
	          var message = [];
	          message.push('-'.repeat(54));
	          message.push((user && user.name ? user.name : this.getLocalize('MOBILE_CHAT_SYSTEM_MESSAGE')) + ' [' + im_lib_utils.Utils.date.format(quoteMessage.date, null, this.getLocalize()) + ']');
	          message.push(im_lib_utils.Utils.text.quote(quoteMessage.text, quoteMessage.params, files, this.getLocalize()));
	          message.push('-'.repeat(54));
	          message.push(text);
	          text = message.join("\n");
	          this.quoteMessageClear();
	        }
	      }

	      console.warn('addMessage', text, file);

	      if (!this.controller.application.isUnreadMessagesLoaded()) {
	        this.sendMessage({
	          id: 0,
	          chatId: this.controller.application.getChatId(),
	          dialogId: this.controller.application.getDialogId(),
	          text: text,
	          file: file
	        });
	        this.processSendMessages();
	        return true;
	      }

	      this.controller.getStore().commit('application/increaseDialogExtraCount');
	      var params = {};

	      if (file) {
	        params.FILE_ID = [file.id];
	      }

	      this.controller.getStore().dispatch('messages/add', {
	        chatId: this.controller.application.getChatId(),
	        authorId: this.controller.application.getUserId(),
	        text: text,
	        params: params,
	        sending: !file
	      }).then(function (messageId) {
	        main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	          chatId: _this17.controller.application.getChatId(),
	          cancelIfScrollChange: true
	        });

	        _this17.messagesQueue.push({
	          id: messageId,
	          chatId: _this17.controller.application.getChatId(),
	          dialogId: _this17.controller.application.getDialogId(),
	          text: text,
	          file: file,
	          sending: false
	        });

	        if (_this17.controller.application.getChatId()) {
	          _this17.processSendMessages();
	        } else {
	          _this17.requestData();
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "uploadFile",
	    value: function uploadFile(file) {
	      var _this18 = this;

	      var text = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';

	      if (!file) {
	        return false;
	      }

	      console.warn('addFile', file, text);

	      if (!this.controller.application.isUnreadMessagesLoaded()) {
	        this.addMessage(text, {
	          id: 0,
	          source: file
	        });
	        return true;
	      }

	      this.controller.getStore().dispatch('files/add', this.controller.application.prepareFilesBeforeSave({
	        chatId: this.controller.application.getChatId(),
	        authorId: this.controller.application.getUserId(),
	        name: file.name,
	        type: im_model.FilesModel.getType(file.name),
	        extension: file.name.split('.').splice(-1)[0],
	        size: 0,
	        image: !file.preview ? false : {
	          width: file.preview.width,
	          height: file.preview.height
	        },
	        status: file.source === 'disk' ? im_const.FileStatus.wait : im_const.FileStatus.upload,
	        progress: 0,
	        authorName: this.controller.application.getCurrentUser().name,
	        urlPreview: !file.preview ? '' : file.preview.url
	      })).then(function (fileId) {
	        return _this18.addMessage(text, babelHelpers.objectSpread({
	          id: fileId
	        }, file));
	      });
	      return true;
	    }
	  }, {
	    key: "cancelUploadFile",
	    value: function cancelUploadFile(fileId) {
	      var _this19 = this;

	      var element = this.messagesQueue.find(function (element) {
	        return element.file && element.file.id === fileId;
	      });

	      if (element) {
	        BX.MobileUploadProvider.cancelTasks(['imDialog' + fileId]);
	        this.controller.getStore().dispatch('messages/delete', {
	          chatId: element.chatId,
	          id: element.id
	        }).then(function () {
	          _this19.controller.getStore().dispatch('files/delete', {
	            chatId: element.chatId,
	            id: element.file.id
	          });

	          _this19.messagesQueue = _this19.messagesQueue.filter(function (el) {
	            return el.id !== element.id;
	          });
	        });
	      }
	    }
	  }, {
	    key: "retryUploadFile",
	    value: function retryUploadFile(fileId) {
	      var _this20 = this;

	      var element = this.messagesQueue.find(function (element) {
	        return element.file && element.file.id === fileId;
	      });

	      if (!element) {
	        return false;
	      }

	      this.controller.getStore().dispatch('messages/actionStart', {
	        chatId: element.chatId,
	        id: element.id
	      }).then(function () {
	        _this20.controller.getStore().dispatch('files/update', {
	          chatId: element.chatId,
	          id: element.file.id,
	          fields: {
	            status: im_const.FileStatus.upload,
	            progress: 0
	          }
	        });
	      });
	      element.sending = false;
	      this.processSendMessages();
	      return true;
	    }
	  }, {
	    key: "processSendMessages",
	    value: function processSendMessages() {
	      var _this21 = this;

	      this.messagesQueue.filter(function (element) {
	        return !element.sending;
	      }).forEach(function (element) {
	        element.sending = true;

	        if (element.file) {
	          if (element.file.source === 'disk') {
	            _this21.fileCommit({
	              chatId: element.chatId,
	              dialogId: element.dialogId,
	              diskId: element.file.uploadLink,
	              messageText: element.text,
	              messageId: element.id,
	              fileId: element.file.id,
	              fileType: im_model.FilesModel.getType(element.file.name)
	            }, element);
	          } else {
	            if (_this21.controller.application.getDiskFolderId()) {
	              _this21.sendMessageWithFile(element);
	            } else {
	              element.sending = false;

	              _this21.requestDiskFolderId();
	            }
	          }
	        } else {
	          element.sending = true;

	          _this21.sendMessage(element);
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "processMarkReadMessages",
	    value: function processMarkReadMessages() {
	      this.controller.application.readMessageExecute(this.controller.application.getChatId(), true);
	      return true;
	    }
	  }, {
	    key: "sendMessage",
	    value: function sendMessage(message) {
	      message.text = message.text.replace(/^([-]{21}\n)/gm, '-'.repeat(54) + '\n');
	      this.controller.application.stopWriting(message.dialogId);
	      BXMobileApp.Events.postToComponent('chatbackground::task::add', ['sendMessage|' + message.id, [im_const.RestMethod.imMessageAdd, {
	        'TEMPLATE_ID': message.id,
	        'DIALOG_ID': message.dialogId,
	        'MESSAGE': message.text
	      }], message], 'background');
	    }
	  }, {
	    key: "sendMessageWithFile",
	    value: function sendMessageWithFile(message) {
	      var fileType = im_model.FilesModel.getType(message.file.name);
	      var fileExtension = message.file.name.toString().toLowerCase().split('.').splice(-1)[0];
	      var attachPreviewFile = fileType !== im_const.FileType.image && message.file.preview;
	      var needConvert = fileType === im_const.FileType.image && message.file.type !== 'image/gif' || fileType === im_const.FileType.video;
	      BX.MobileUploadProvider.addTasks([{
	        url: message.file.uploadLink,
	        params: message,
	        name: message.file.name,
	        type: fileExtension,
	        mimeType: fileType === im_const.FileType.audio ? 'audio/mp4' : null,
	        resize: !needConvert ? null : {
	          "quality": 80,
	          "width": 1920,
	          "height": 1080
	        },
	        previewUrl: attachPreviewFile ? message.file.preview.url : '',
	        folderId: this.controller.application.getDiskFolderId(),
	        taskId: 'imDialog' + message.file.id,
	        onDestroyEventName: 'onimdiskmessageaddsuccess'
	      }]);
	    }
	  }, {
	    key: "fileError",
	    value: function fileError(chatId, fileId) {
	      var messageId = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;
	      this.controller.getStore().dispatch('files/update', {
	        chatId: chatId,
	        id: fileId,
	        fields: {
	          status: im_const.FileStatus.error,
	          progress: 0
	        }
	      });

	      if (messageId) {
	        this.controller.getStore().dispatch('messages/actionError', {
	          chatId: chatId,
	          id: messageId,
	          retry: true
	        });
	      }
	    }
	  }, {
	    key: "fileCommit",
	    value: function fileCommit(params, message) {
	      var _this22 = this;

	      var queryParams = {
	        chat_id: params.chatId,
	        message: params.messageText,
	        template_id: params.messageId ? params.messageId : 0,
	        file_template_id: params.fileId ? params.fileId : 0
	      };

	      if (params.uploadId) {
	        queryParams.upload_id = params.uploadId;
	      } else if (params.diskId) {
	        queryParams.disk_id = params.diskId;
	      }

	      this.controller.restClient.callMethod(im_const.RestMethod.imDiskFileCommit, queryParams, null, null, im_lib_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imDiskFileCommit,
	        data: {
	          timMessageType: params.fileType
	        },
	        dialog: this.controller.application.getDialogData(params.dialogId)
	      })).then(function (response) {
	        _this22.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFileCommit, response, message);
	      }).catch(function (error) {
	        _this22.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFileCommit, error, message);
	      });
	      return true;
	    }
	  }, {
	    key: "requestDiskFolderId",
	    value: function requestDiskFolderId() {
	      var _this23 = this;

	      if (this.flagRequestDiskFolderIdSended || this.controller.application.getDiskFolderId()) {
	        return true;
	      }

	      this.flagRequestDiskFolderIdSended = true;
	      this.controller.restClient.callMethod(im_const.RestMethod.imDiskFolderGet, {
	        chat_id: this.controller.application.getChatId()
	      }).then(function (response) {
	        _this23.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFolderGet, response);

	        _this23.flagRequestDiskFolderIdSended = false;

	        _this23.processSendMessages();
	      }).catch(function (error) {
	        _this23.flagRequestDiskFolderIdSended = false;

	        _this23.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFolderGet, error);
	      });
	      return true;
	    }
	  }, {
	    key: "getDialogHistory",
	    value: function getDialogHistory(lastId) {
	      var _this24 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.application.getRequestMessageLimit();
	      this.controller.restClient.callMethod(im_const.RestMethod.imDialogMessagesGet, {
	        'CHAT_ID': this.controller.application.getChatId(),
	        'LAST_ID': lastId,
	        'LIMIT': limit,
	        'CONVERT_TEXT': 'Y'
	      }).then(function (result) {
	        _this24.controller.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGet, result); // this.controller.application.emit(EventType.dialog.requestHistoryResult, {count: result.data().messages.length});

	      }).catch(function (result) {// this.controller.emit(EventType.dialog.requestHistoryResult, {error: result.error().ex});
	      });
	    }
	  }, {
	    key: "getDialogUnread",
	    value: function getDialogUnread(lastId) {
	      var _this25 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.application.getRequestMessageLimit();

	      if (this.promiseGetDialogUnreadWait) {
	        return this.promiseGetDialogUnread;
	      }

	      this.promiseGetDialogUnread = new BX.Promise();
	      this.promiseGetDialogUnreadWait = true;

	      if (!lastId) {
	        lastId = this.controller.getStore().getters['messages/getLastId'](this.controller.application.getChatId());
	      }

	      if (!lastId) {
	        // this.controller.application.emit(EventType.dialog.requestUnreadResult, {error: {error: 'LAST_ID_EMPTY', error_description: 'LastId is empty.'}});
	        this.promiseGetDialogUnread.reject();
	        this.promiseGetDialogUnreadWait = false;
	        return this.promiseGetDialogUnread;
	      }

	      this.controller.application.readMessage(lastId, true, true).then(function () {
	        var _query2;

	        _this25.timer.start('data', 'load', .5, function () {
	          console.warn("ChatDialog.requestData: slow connection show progress icon");
	          app.titleAction("setParams", {
	            useProgress: true,
	            useLetterImage: false
	          });
	        });

	        var query = (_query2 = {}, babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imDialogRead, [im_const.RestMethod.imDialogRead, {
	          dialog_id: _this25.controller.application.getDialogId(),
	          message_id: lastId
	        }]), babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imChatGet, [im_const.RestMethod.imChatGet, {
	          dialog_id: _this25.controller.application.getDialogId()
	        }]), babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imDialogMessagesGetUnread, [im_const.RestMethod.imDialogMessagesGet, {
	          chat_id: _this25.controller.application.getChatId(),
	          first_id: lastId,
	          limit: limit,
	          convert_text: 'Y'
	        }]), _query2);

	        _this25.controller.restClient.callBatch(query, function (response) {
	          if (!response) {
	            _this25.promiseGetDialogUnread.reject();

	            _this25.promiseGetDialogUnreadWait = false;
	            return false;
	          }

	          var chatGetResult = response[im_const.RestMethodHandler.imChatGet];

	          if (!chatGetResult.error()) {
	            _this25.controller.executeRestAnswer(im_const.RestMethodHandler.imChatGet, chatGetResult);
	          }

	          var dialogMessageUnread = response[im_const.RestMethodHandler.imDialogMessagesGetUnread];

	          if (!dialogMessageUnread.error()) {
	            dialogMessageUnread = dialogMessageUnread.data();

	            _this25.controller.getStore().dispatch('users/set', dialogMessageUnread.users);

	            _this25.controller.getStore().dispatch('files/set', _this25.controller.application.prepareFilesBeforeSave(dialogMessageUnread.files));

	            _this25.controller.getStore().dispatch('messages/setAfter', dialogMessageUnread.messages);

	            app.titleAction("setParams", {
	              useProgress: false,
	              useLetterImage: true
	            });

	            _this25.timer.stop('data', 'load', true);
	          }

	          _this25.promiseGetDialogUnread.fulfill(response);

	          _this25.promiseGetDialogUnreadWait = false;
	        }, false, false, im_lib_utils.Utils.getLogTrackingParams({
	          name: im_const.RestMethodHandler.imDialogMessagesGetUnread,
	          dialog: _this25.controller.application.getDialogData()
	        }));
	      });
	      return this.promiseGetDialogUnread;
	    }
	  }, {
	    key: "retrySendMessage",
	    value: function retrySendMessage(message) {
	      var element = this.messagesQueue.find(function (el) {
	        return el.id === message.id;
	      });

	      if (element) {
	        if (element.file && element.file.id) {
	          this.retryUploadFile(element.file.id);
	        }

	        return false;
	      }

	      this.messagesQueue.push({
	        id: message.id,
	        chatId: this.controller.application.getChatId(),
	        dialogId: this.controller.application.getDialogId(),
	        text: message.text,
	        sending: false
	      });
	      this.controller.application.setSendingMessageFlag(message.id);
	      this.processSendMessages();
	    }
	  }, {
	    key: "openProfile",
	    value: function openProfile(userId) {
	      BXMobileApp.Events.postToComponent("onUserProfileOpen", [userId, {
	        backdrop: true
	      }], 'communication');
	    }
	  }, {
	    key: "openDialog",
	    value: function openDialog(dialogId) {
	      BXMobileApp.Events.postToComponent("onOpenDialog", [{
	        dialogId: dialogId
	      }, true], 'im.recent');
	    }
	  }, {
	    key: "openPhoneMenu",
	    value: function openPhoneMenu(number) {
	      BX.MobileTools.phoneTo(number);
	    }
	  }, {
	    key: "openMessageMenu",
	    value: function openMessageMenu(message) {
	      var _this26 = this;

	      if (this.messagesQueue.find(function (el) {
	        return el.id === message.id;
	      })) {
	        return false;
	      }

	      this.controller.getStore().dispatch('messages/update', {
	        id: message.id,
	        chatId: message.chatId,
	        fields: {
	          blink: true
	        }
	      });
	      var currentUser = this.controller.application.getCurrentUser();
	      var dialog = this.controller.application.getDialogData();
	      var messageUser = message.authorId > 0 ? this.controller.getStore().getters['users/get'](message.authorId, true) : null;
	      this.messageMenuInstance = BackdropMenu.create('im.dialog.menu.mess|' + this.controller.application.getDialogId()).setItems([BackdropMenuItem.create('reply').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_REPLY')).setIcon(BackdropMenuIcon.reply).skip(function (message) {
	        var dialog = _this26.controller.application.getDialogData();

	        if (dialog.type === 'announcement' && !dialog.managerList.includes(_this26.controller.application.getUserId())) {
	          return true;
	        }

	        return !message.authorId || message.authorId === _this26.controller.application.getUserId();
	      }), BackdropMenuItem.create('copy').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_COPY')).setIcon(BackdropMenuIcon.copy).skip(function (message) {
	        return message.params.IS_DELETED === 'Y';
	      }), BackdropMenuItem.create('quote').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_QUOTE')).setIcon(BackdropMenuIcon.quote).skip(function (message) {
	        var dialog = _this26.controller.application.getDialogData();

	        if (dialog.type === 'announcement' && !dialog.managerList.includes(_this26.controller.application.getUserId())) {
	          return true;
	        }

	        return message.params.IS_DELETED === 'Y';
	      }), BackdropMenuItem.create('unread').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_UNREAD')).setIcon(BackdropMenuIcon.unread).skip(function (message) {
	        return message.authorId === _this26.controller.application.getUserId() || message.unread;
	      }), BackdropMenuItem.create('read').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_READ')).setIcon(BackdropMenuIcon.checked).skip(function (message) {
	        return !message.unread;
	      }), BackdropMenuItem.create('edit').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_EDIT')).setIcon(BackdropMenuIcon.edit).skip(function (message) {
	        return message.authorId !== _this26.controller.application.getUserId() || message.params.IS_DELETED === 'Y';
	      }), BackdropMenuItem.create('share').setType(BackdropMenuItemType.menu).setIcon(BackdropMenuIcon.circle_plus).setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_SHARE_MENU')).disableClose().skip(currentUser.extranet || dialog.type === 'announcement'), BackdropMenuItem.create('profile').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_PROFILE')).setIcon(BackdropMenuIcon.user).skip(function (message) {
	        if (message.authorId <= 0 || !messageUser) return true;
	        if (!im_lib_utils.Utils.dialog.isChatId(_this26.controller.application.getDialogId())) return true;
	        if (message.authorId === _this26.controller.application.getUserId()) return true;

	        if (messageUser.externalAuthId === 'imconnector' || messageUser.externalAuthId === 'call') {
	          return true;
	        }

	        return false;
	      }), BackdropMenuItem.create('delete').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_DELETE')).setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setColor('#c50000'))).setIcon(BackdropMenuIcon.trash).skip(function (message) {
	        return message.authorId !== _this26.controller.application.getUserId() || message.params.IS_DELETED === 'Y';
	      })]).setVersion(BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0')).setEventListener(function (name, params, message, backdrop) {
	        if (name === 'destroyed') {
	          ui_vue.Vue.event.$emit('bitrixmobile:controller:focus');
	        }

	        if (name !== 'selected') {
	          return false;
	        }

	        if (params.id === 'reply') {
	          _this26.replyToUser(message.authorId);
	        } else if (params.id === 'copy') {
	          _this26.copyMessage(message.id);
	        } else if (params.id === 'quote') {
	          _this26.quoteMessageClear();

	          _this26.quoteMessage(message.id);
	        } else if (params.id === 'edit') {
	          _this26.quoteMessageClear();

	          _this26.editMessage(message.id);
	        } else if (params.id === 'delete') {
	          _this26.deleteMessage(message.id);
	        } else if (params.id === 'unread') {
	          _this26.unreadMessage(message.id);
	        } else if (params.id === 'read') {
	          _this26.readMessage(message.id);
	        } else if (params.id === 'share') {
	          var _dialog = _this26.controller.application.getDialogData();

	          var subMenu = BackdropMenu.create('im.dialog.menu.mess.submenu|' + _this26.controller.application.getDialogId()).setItems([BackdropMenuItem.create('share_task').setIcon(BackdropMenuIcon.task).setTitle(_this26.getLocalize('MOBILE_MESSAGE_MENU_SHARE_TASK')), BackdropMenuItem.create('share_post').setIcon(BackdropMenuIcon.lifefeed).setTitle(_this26.getLocalize('MOBILE_MESSAGE_MENU_SHARE_POST_NEWS')), BackdropMenuItem.create('share_chat').setIcon(BackdropMenuIcon.chat).setTitle(_this26.getLocalize('MOBILE_MESSAGE_MENU_SHARE_CHAT'))]).setEventListener(function (name, params, options, backdrop) {
	            if (name !== 'selected') {
	              return false;
	            }

	            if (params.id === 'share_task') {
	              _this26.shareMessage(message.id, 'TASK');
	            } else if (params.id === 'share_post') {
	              _this26.shareMessage(message.id, 'POST');
	            } else if (params.id === 'share_chat') {
	              _this26.shareMessage(message.id, 'CHAT');
	            }
	          });
	          backdrop.showSubMenu(subMenu);
	        } else if (params.id === 'profile') {
	          _this26.openProfile(message.authorId);
	        } else {
	          console.warn('BackdropMenuItem is not implemented', params);
	        }
	      });
	      this.messageMenuInstance.setCustomParams(message).show();
	      fabric.Answers.sendCustomEvent("vueChatOpenDropdown", {});
	    }
	  }, {
	    key: "openHeaderMenu",
	    value: function openHeaderMenu() {
	      var _this27 = this;

	      fabric.Answers.sendCustomEvent("vueChatOpenHeaderMenu", {});

	      if (!this.headerMenu) {
	        this.headerMenu = HeaderMenu.create().setUseNavigationBarColor().setEventListener(function (name, params, customParams) {
	          if (name !== 'selected') {
	            return false;
	          }

	          if (params.id === 'profile') {
	            _this27.openProfile(_this27.controller.application.getDialogId());
	          } else if (params.id === 'user_list') {
	            _this27.openUserList({
	              listType: 'USERS',
	              title: _this27.getLocalize('MOBILE_HEADER_MENU_USER_LIST'),
	              backdrop: true
	            });
	          } else if (params.id === 'user_add') {
	            _this27.openAddUserDialog();
	          } else if (params.id === 'leave') {
	            _this27.leaveChat();
	          } else if (params.id === 'notify') {
	            _this27.controller.application.muteDialog();
	          } else if (params.id === 'call_chat_call') {
	            BX.MobileTools.phoneTo(_this27.controller.application.getDialogData().entityId);
	          } else if (params.id === 'goto_crm') {
	            var crmData = _this27.controller.application.getDialogCrmData();

	            var openWidget = BX.MobileTools.resolveOpenFunction('/crm/' + crmData.entityType + '/show/' + crmData.entityId + '/');

	            if (openWidget) {
	              openWidget();
	            }
	          } else if (params.id === 'reload') {
	            new BXMobileApp.UI.NotificationBar({
	              message: _this27.getLocalize('MOBILE_HEADER_MENU_RELOAD_WAIT'),
	              color: "#d920b0ff",
	              textColor: "#ffffff",
	              groupId: "refresh",
	              useLoader: true,
	              maxLines: 1,
	              align: "center",
	              hideOnTap: true
	            }, "copy").show();

	            _this27.controller.getStoreBuilder().clearDatabase();

	            reload();
	          }
	        });
	      }

	      if (im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        var dialogData = this.controller.application.getDialogData();
	        var notifyToggleText = !this.controller.application.isDialogMuted() ? this.getLocalize('MOBILE_HEADER_MENU_NOTIFY_DISABLE') : this.getLocalize('MOBILE_HEADER_MENU_NOTIFY_ENABLE');
	        var notifyToggleIcon = !this.controller.application.isDialogMuted() ? HeaderMenuIcon.notify : HeaderMenuIcon.notify_off;
	        var gotoCrmLocalize = '';

	        if (dialogData.type === im_const.DialogType.call || dialogData.type === im_const.DialogType.crm) {
	          var crmData = this.controller.application.getDialogCrmData();

	          if (crmData.enabled) {
	            if (crmData.entityType === im_const.DialogCrmType.lead) {
	              gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_LEAD');
	            } else if (crmData.entityType === im_const.DialogCrmType.company) {
	              gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_COMPANY');
	            } else if (crmData.entityType === im_const.DialogCrmType.contact) {
	              gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_CONTACT');
	            } else if (crmData.entityType === im_const.DialogCrmType.deal) {
	              gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_DEAL');
	            } else {
	              gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM');
	            }
	          }
	        }

	        if (dialogData.type === im_const.DialogType.call) {
	          this.headerMenu.setItems([HeaderMenuItem.create('call_chat_call').setTitle(this.getLocalize('MOBILE_HEADER_MENU_AUDIO_CALL')).setIcon(HeaderMenuIcon.phone).skip(dialogData.entityId === 'UNIFY_CALL_CHAT'), HeaderMenuItem.create('goto_crm').setTitle(gotoCrmLocalize).setIcon(HeaderMenuIcon.lifefeed).skip(dialogData.entityId === 'UNIFY_CALL_CHAT' || !gotoCrmLocalize), HeaderMenuItem.create('reload').setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD')).setIcon(HeaderMenuIcon.reload)]);
	        } else {
	          var items = [HeaderMenuItem.create('notify').setTitle(notifyToggleText).setIcon(notifyToggleIcon), HeaderMenuItem.create('user_list').setTitle(this.getLocalize('MOBILE_HEADER_MENU_USER_LIST')).setIcon(HeaderMenuIcon.user), HeaderMenuItem.create('user_add').setTitle(this.getLocalize('MOBILE_HEADER_MENU_USER_ADD')).setIcon(HeaderMenuIcon.user_plus).skip(!dialogData.restrictions.extend), HeaderMenuItem.create('leave').setTitle(this.getLocalize('MOBILE_HEADER_MENU_LEAVE')).setIcon(HeaderMenuIcon.cross).skip(!dialogData.restrictions.leave), HeaderMenuItem.create('reload').setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD')).setIcon(HeaderMenuIcon.reload)];
	          items.push(HeaderMenuItem.create('reload').setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD')).setIcon(HeaderMenuIcon.reload));

	          if (dialogData.type === im_const.DialogType.crm && gotoCrmLocalize) {
	            items.unshift(HeaderMenuItem.create('goto_crm').setTitle(gotoCrmLocalize).setIcon(HeaderMenuIcon.lifefeed));
	          }

	          this.headerMenu.setItems(items);
	        }
	      } else {
	        this.headerMenu.setItems([HeaderMenuItem.create('profile').setTitle(this.getLocalize('MOBILE_HEADER_MENU_PROFILE')).setIcon('user').skip(im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())), HeaderMenuItem.create('user_add').setTitle(this.getLocalize('MOBILE_HEADER_MENU_USER_ADD')).setIcon(HeaderMenuIcon.user_plus), HeaderMenuItem.create('reload').setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD')).setIcon(HeaderMenuIcon.reload)]);
	      }

	      this.headerMenu.show(true);
	    }
	  }, {
	    key: "shareMessage",
	    value: function shareMessage(messageId, type) {
	      if (!this.controller.isOnline()) {
	        return false;
	      }

	      return this.controller.application.shareMessage(messageId, type);
	    }
	  }, {
	    key: "readMessage",
	    value: function readMessage(messageId) {
	      // if (this.controller.isOnline())
	      // {
	      // 	return false;
	      // }
	      this.controller.application.readMessage(messageId, true, true).then(function (result) {
	        if (result.lastId <= 0) {
	          return true;
	        }

	        BXMobileApp.Events.postToComponent('chatbackground::task::action', ['readMessage', 'readMessage|' + result.dialogId, result, false, 200], 'background');
	      });
	      return true;
	    }
	  }, {
	    key: "unreadMessage",
	    value: function unreadMessage(messageId) {
	      if (!this.controller.isOnline()) {
	        return false;
	      }

	      return this.controller.application.unreadMessage(messageId);
	    }
	  }, {
	    key: "openReadedList",
	    value: function openReadedList(list) {
	      if (!im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        return false;
	      }

	      if (!list || list.length <= 1) {
	        return false;
	      }

	      this.openUserList({
	        users: list.map(function (element) {
	          return element.userId;
	        }),
	        title: this.getLocalize('MOBILE_MESSAGE_LIST_VIEW')
	      });
	    }
	  }, {
	    key: "replyToUser",
	    value: function replyToUser(userId) {
	      var userData = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

	      if (!this.controller.isOnline()) {
	        return false;
	      }

	      if (!userData) {
	        userData = this.controller.getStore().getters['users/get'](userId);
	      }

	      return this.insertText({
	        text: "[USER=".concat(userId, "]").concat(userData.firstName, "[/USER] ")
	      });
	    }
	  }, {
	    key: "copyMessage",
	    value: function copyMessage(id) {
	      var quoteMessage = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), id);
	      var text = '';

	      if (quoteMessage.params.FILE_ID && quoteMessage.params.FILE_ID.length) {
	        text = quoteMessage.params.FILE_ID.map(function (fileId) {
	          return '[DISK=' + fileId + ']';
	        }).join(" ");
	      }

	      if (quoteMessage.text) {
	        if (text) {
	          text += '\n';
	        }

	        text += quoteMessage.text.replace(/^([-]{54}\n)/gm, '-'.repeat(21) + '\n');
	      }

	      app.exec("copyToClipboard", {
	        text: text
	      });
	      new BXMobileApp.UI.NotificationBar({
	        message: BX.message("MOBILE_MESSAGE_MENU_COPY_SUCCESS"),
	        color: "#af000000",
	        textColor: "#ffffff",
	        groupId: "clipboard",
	        maxLines: 1,
	        align: "center",
	        isGlobal: true,
	        useCloseButton: true,
	        autoHideTimeout: 1500,
	        hideOnTap: true
	      }, "copy").show();
	    }
	  }, {
	    key: "quoteMessage",
	    value: function quoteMessage(id) {
	      var _this28 = this;

	      this.controller.getStore().dispatch('dialogues/update', {
	        dialogId: this.controller.application.getDialogId(),
	        fields: {
	          quoteId: id
	        }
	      }).then(function () {
	        if (!_this28.controller.getStore().state.application.mobile.keyboardShow) {
	          _this28.setTextFocus();

	          setTimeout(function () {
	            main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	              chatId: _this28.controller.application.getChatId(),
	              duration: 300,
	              cancelIfScrollChange: false,
	              force: true
	            });
	          }, 300);
	        }
	      });
	    }
	  }, {
	    key: "reactMessage",
	    value: function reactMessage(id, reaction) {
	      BXMobileApp.Events.postToComponent('chatbackground::task::action', ['reactMessage', 'reactMessage|' + id, {
	        messageId: id,
	        action: reaction.action === 'auto' ? 'auto' : reaction.action === 'set' ? 'plus' : 'minus'
	      }, false, 1000], 'background');

	      if (reaction.action === 'set') {
	        setTimeout(function () {
	          return app.exec("callVibration");
	        }, 200);
	      }
	    }
	  }, {
	    key: "openMessageReactionList",
	    value: function openMessageReactionList(id, reactions) {
	      if (!im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        return false;
	      }

	      var users = [];

	      for (var reaction in reactions) {
	        if (!reactions.hasOwnProperty(reaction)) {
	          continue;
	        }

	        users = users.concat(reactions[reaction]);
	      }

	      this.openUserList({
	        users: users,
	        title: this.getLocalize('MOBILE_MESSAGE_LIST_LIKE')
	      });
	    }
	  }, {
	    key: "quoteMessageClear",
	    value: function quoteMessageClear() {
	      this.setText('');
	      this.controller.getStore().dispatch('dialogues/update', {
	        dialogId: this.controller.application.getDialogId(),
	        fields: {
	          quoteId: 0,
	          editId: 0
	        }
	      });
	    }
	  }, {
	    key: "editMessage",
	    value: function editMessage(id) {
	      var _this29 = this;

	      var message = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), id);
	      this.controller.getStore().dispatch('dialogues/update', {
	        dialogId: this.controller.application.getDialogId(),
	        fields: {
	          quoteId: id,
	          editId: id
	        }
	      }).then(function () {
	        setTimeout(function () {
	          return main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	            chatId: _this29.controller.application.getChatId(),
	            duration: 300,
	            cancelIfScrollChange: false,
	            force: true
	          });
	        }, 300);

	        _this29.setTextFocus();
	      });
	      this.setText(message.text);
	    }
	  }, {
	    key: "updateMessage",
	    value: function updateMessage(id, text) {
	      this.quoteMessageClear();
	      this.controller.getStore().dispatch('dialogues/update', {
	        dialogId: this.controller.application.getDialogId(),
	        fields: {
	          editId: 0
	        }
	      });
	      this.editMessageSend(id, text);
	    }
	  }, {
	    key: "editMessageSend",
	    value: function editMessageSend(id, text) {
	      this.controller.restClient.callMethod(im_const.RestMethod.imMessageUpdate, {
	        'MESSAGE_ID': id,
	        'MESSAGE': text
	      }, null, null, im_lib_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imMessageUpdate,
	        data: {
	          timMessageType: 'text'
	        },
	        dialog: this.controller.application.getDialogData()
	      }));
	    }
	  }, {
	    key: "deleteMessage",
	    value: function deleteMessage(id) {
	      var _this30 = this;

	      var message = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), id);
	      var files = this.controller.getStore().getters['files/getList'](this.controller.application.getChatId());
	      var messageText = im_lib_utils.Utils.text.purify(message.text, message.params, files, this.getLocalize());
	      messageText = messageText.length > 50 ? messageText.substr(0, 47) + '...' : messageText;
	      app.confirm({
	        title: this.getLocalize('MOBILE_MESSAGE_MENU_DELETE_CONFIRM'),
	        text: messageText ? '"' + messageText + '"' : '',
	        buttons: [this.getLocalize('MOBILE_MESSAGE_MENU_DELETE_YES'), this.getLocalize('MOBILE_MESSAGE_MENU_DELETE_NO')],
	        callback: function callback(button) {
	          if (button === 1) {
	            _this30.quoteMessageClear();

	            _this30.deleteMessageSend(id);
	          }
	        }
	      });
	    }
	  }, {
	    key: "deleteMessageSend",
	    value: function deleteMessageSend(id) {
	      this.controller.restClient.callMethod(im_const.RestMethod.imMessageDelete, {
	        'MESSAGE_ID': id
	      }, null, null, im_lib_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imMessageDelete,
	        data: {},
	        dialog: this.controller.application.getDialogData(this.controller.application.getDialogId())
	      }));
	    }
	  }, {
	    key: "insertText",
	    value: function insertText(params) {
	      var _this31 = this;

	      BXMobileApp.UI.Page.TextPanel.getText(function (text) {
	        text = text.toString().trim();
	        text = text ? text + ' ' + params.text : params.text;

	        _this31.setText(text);

	        _this31.setTextFocus();
	      });
	    }
	  }, {
	    key: "setText",
	    value: function setText() {
	      var text = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      text = text.toString();

	      if (text) {
	        BXMobileApp.UI.Page.TextPanel.setText(text);
	      } else {
	        BXMobileApp.UI.Page.TextPanel.clear();
	      }

	      this.setTextareaMessage({
	        message: text
	      });
	      console.log('Set new text in textarea', text ? text : '-- empty --');
	    }
	  }, {
	    key: "clearText",
	    value: function clearText() {
	      this.setText();
	    }
	  }, {
	    key: "setTextFocus",
	    value: function setTextFocus() {
	      if (!this.controller.getStore().state.application.mobile.keyboardShow) {
	        BXMobileApp.UI.Page.TextPanel.focus();
	      }
	    }
	  }, {
	    key: "isBackground",
	    value: function isBackground() {
	      if ((typeof BXMobileAppContext === "undefined" ? "undefined" : babelHelpers.typeof(BXMobileAppContext)) !== "object") {
	        return false;
	      }

	      if (typeof BXMobileAppContext.isAppActive === "function" && !BXMobileAppContext.isAppActive()) {
	        return true;
	      }

	      if (typeof BXMobileAppContext.isBackground === "function") {
	        return BXMobileAppContext.isBackground();
	      }

	      return false;
	    }
	  }, {
	    key: "hideSmiles",
	    value: function hideSmiles() {// this.controller.hideSmiles();
	    }
	    /* endregion 05. Templates and template interaction */

	    /* region 05. Interaction and utils */

	  }, {
	    key: "executeBackgroundTaskSuccess",
	    value: function executeBackgroundTaskSuccess(action, _data) {
	      var successObject = {
	        error: function error() {
	          return false;
	        },
	        data: function data() {
	          return _data.result;
	        }
	      };
	      console.log('Dialog.executeBackgroundTaskSuccess', action, _data);

	      if (action === 'sendMessage') {
	        this.controller.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, successObject, _data.extra);
	      } else if (action === 'readMessage') {
	        this.processMarkReadMessages();
	      }
	    }
	  }, {
	    key: "executeBackgroundTaskFailure",
	    value: function executeBackgroundTaskFailure(action, data) {
	      var errorObject = {
	        error: function error() {
	          return {
	            error: data.code,
	            error_description: data.text,
	            ex: {
	              status: data.status
	            }
	          };
	        },
	        data: function data() {
	          return false;
	        }
	      };
	      console.log('Dialog.executeBackgroundTaskFailure', action, data);

	      if (action === 'sendMessage') {
	        this.controller.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, errorObject, data.extra);
	      }
	    }
	    /* endregion 05. Interaction and utils */

	    /* region 06. Interaction and utils */

	  }, {
	    key: "setError",
	    value: function setError() {
	      var code = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var description = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      console.error("MobileChat.error: ".concat(code, " (").concat(description, ")"));
	      var localizeDescription = '';

	      if (code.endsWith('LOCALIZED')) {
	        localizeDescription = description;
	      }

	      this.controller.getStore().commit('application/set', {
	        error: {
	          active: true,
	          code: code,
	          description: localizeDescription
	        }
	      });
	    }
	  }, {
	    key: "clearError",
	    value: function clearError() {
	      this.controller.getStore().commit('application/set', {
	        error: {
	          active: false,
	          code: '',
	          description: ''
	        }
	      });
	    }
	  }, {
	    key: "addLocalize",
	    value: function addLocalize(phrases) {
	      return this.controller.addLocalize(phrases);
	    }
	  }, {
	    key: "getLocalize",
	    value: function getLocalize(name) {
	      return this.controller.getLocalize(name);
	    }
	    /* endregion 06. Interaction and utils */

	  }]);
	  return MobileDialogApplication;
	}();

	exports.MobileDialogApplication = MobileDialogApplication;

}((this.BX.Messenger.Application = this.BX.Messenger.Application || {}),BX.Messenger.Application,BX,BX,BX.Messenger.Model,BX.Messenger.Provider.Rest,BX.Messenger.Lib,BX.Messenger.Lib,window,BX,BX.Messenger.Lib,BX.Messenger.Const,BX.Messenger.Lib,BX.Messenger,window,BX.Event,BX,window,BX.Messenger.Mixin));
//# sourceMappingURL=dialog.bundle.js.map
