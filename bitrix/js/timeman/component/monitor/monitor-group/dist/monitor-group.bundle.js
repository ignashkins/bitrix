this.BX = this.BX || {};
this.BX.Timeman = this.BX.Timeman || {};
this.BX.Timeman.Component = this.BX.Timeman.Component || {};
(function (exports,ui_vuex,ui_vue,timeman_mixin,timeman_const,ui_vue_components_hint,ui_vue_portal,main_popup,ui_dialogs_messagebox) {
    'use strict';

    var GroupItem = ui_vue.BitrixVue.localComponent('bx-timeman-component-monitor-monitor-group-group-item', {
      mixins: [timeman_mixin.Time],
      props: ['readOnly', 'group', 'privateCode', 'type', 'title', 'time', 'allowedTime', 'comment', 'hint'],
      data: function data() {
        return {
          action: ''
        };
      },
      computed: babelHelpers.objectSpread({}, ui_vuex.Vuex.mapGetters('monitor', ['getSiteDetailByPrivateCode']), ui_vuex.Vuex.mapState({
        monitor: function monitor(state) {
          return state.monitor;
        }
      }), {
        EntityType: function EntityType() {
          return timeman_const.EntityType;
        },
        EntityGroup: function EntityGroup() {
          return timeman_const.EntityGroup;
        }
      }),
      methods: {
        addPersonal: function addPersonal(privateCode) {
          this.$store.dispatch('monitor/addPersonal', privateCode);
        },
        removePersonal: function removePersonal(privateCode) {
          var _this = this;

          if (this.type === timeman_const.EntityType.absence && this.comment.trim() === '') {
            this.action = function () {
              return _this.$store.dispatch('monitor/removePersonal', _this.privateCode);
            };

            this.onCommentClick();
            return;
          }

          this.$store.dispatch('monitor/removePersonal', privateCode);
        },
        addToStrictlyWorking: function addToStrictlyWorking(privateCode) {
          var _this2 = this;

          if (this.type === timeman_const.EntityType.absence && this.comment.trim() === '') {
            this.action = function () {
              return _this2.$store.dispatch('monitor/addToStrictlyWorking', privateCode);
            };

            this.onCommentClick();
            return;
          }

          this.$store.dispatch('monitor/addToStrictlyWorking', privateCode);
        },
        removeFromStrictlyWorking: function removeFromStrictlyWorking(privateCode) {
          this.$store.dispatch('monitor/removeFromStrictlyWorking', privateCode);
        },
        onCommentClick: function onCommentClick(event) {
          this.$emit('commentClick', {
            event: event,
            group: this.group,
            content: {
              privateCode: this.privateCode,
              title: this.title,
              time: this.time,
              comment: this.comment,
              type: this.type
            },
            onSaveComment: this.action
          });
        },
        onDetailClick: function onDetailClick(event) {
          this.$emit('detailClick', {
            event: event,
            group: this.group,
            content: {
              privateCode: this.privateCode,
              title: this.title,
              detail: this.getSiteDetailByPrivateCode(this.privateCode),
              time: this.time
            }
          });
        }
      },
      // language=Vue1
      template: "\n    \t<div class=\"bx-monitor-group-item-wrap\">\n\t\t\t<div class=\"bx-monitor-group-item\"> \t\t\t  \n\t\t\t\t<template v-if=\"type !== EntityType.group\">\t\t\t\t\t  \n\t\t\t\t\t<div class=\"bx-monitor-group-item-container\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title-container\">\n\t\t\t\t\t\t  \t<div\t\t\t\t\t\t\t\t\t\n                                v-if=\"type === EntityType.absence\"\n                                class=\"bx-monitor-group-item-title-small\"\n\t\t\t\t\t\t\t>\n                              {{ title }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div v-else class=\"bx-monitor-group-item-title\">\n\t\t\t\t\t\t\t\t<template v-if=\"type !== EntityType.site || readOnly\">\n\t\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<a \n\t\t\t\t\t\t\t\t\t\t@click=\"onDetailClick\" \n\t\t\t\t\t\t\t\t\t\thref=\"#\" \n\t\t\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-site-title\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<bx-hint v-if=\"hint\" :text=\"hint\"/>\n\t\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t\tv-if=\"group === EntityGroup.working.value\" \n\t\t\t\t\t\t\t\tclass=\"bx-monitor-group-item-button-comment ui-icon ui-icon-xs\"\n\t\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t  'ui-icon-service-imessage': comment, \n\t\t\t\t\t\t\t\t  'ui-icon-service-light-imessage': !comment \n\t\t\t\t\t\t\t\t}\"\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<i \n\t\t\t\t\t\t\t\t\t@click=\"onCommentClick\" \n\t\t\t\t\t\t\t\t\t:style=\"{\n\t\t\t\t\t\t\t\t\t  backgroundColor: comment ? EntityGroup.working.primaryColor : 'transparent'\n\t\t\t\t\t\t\t\t\t}\"\n\t\t\t\t\t\t\t\t/>\t\t\t\t\t\t\t  \t\t\t\t\t\t\t\t  \n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-time\">\n\t\t\t\t\t\t\t{{ time }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"group === EntityGroup.personal.value && !readOnly\"\n\t\t\t\t\t\t@click=\"removePersonal(privateCode)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-light-border ui-btn-round bx-monitor-group-btn-right\"\n\t\t\t\t\t>\n                    \t{{ $Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_BUTTON_TO_WORKING') }}\n\t\t\t\t\t</button>\t\t\t\t\t  \n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"\n\t\t\t\t\t\t\tgroup === EntityGroup.working.value \n\t\t\t\t\t\t\t&& type !== EntityType.unknown \n\t\t\t\t\t\t\t&& !readOnly\n\t\t\t\t\t\t\"\n\t\t\t\t\t\t@click=\"addPersonal(privateCode)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-light-border ui-btn-round bx-monitor-group-btn-right\" \t\t\t\t\t\t\n\t\t\t\t\t>\n                    \t{{ $Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_BUTTON_TO_PERSONAL') }}\n\t\t\t\t\t</button>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-monitor-group-item-container\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title-container\">\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-item-title-full\">\t\t\t\t\t\t\n\t\t\t\t\t\t\t\t{{ title }}\n\t\t\t\t\t\t\t</div>\n                          \t<bx-hint v-if=\"hint\" :text=\"hint\"/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-monitor-group-item-menu\">\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-item-time\">\n\t\t\t\t\t\t\t\t{{ time }} / {{ allowedTime }}\t\t\t\t\t\t\t\t  \n\t\t\t\t\t\t\t</div>\t\t\t\t\t\t\t  \n\t\t\t\t\t\t</div>\n\t\t\t\t  </div>\t\t\t\t  \n\t\t\t\t</template>\n\t\t\t</div>\t\t\t\n\t\t</div>\n\t"
    });

    ui_vue.Vue.component('bx-timeman-component-monitor-group', {
      components: {
        GroupItem: GroupItem,
        MountingPortal: ui_vue_portal.MountingPortal
      },
      mixins: [timeman_mixin.Time],
      props: ['group', 'readOnly'],
      data: function data() {
        return {
          refreshCount: 0,
          popupInstance: null,
          popupIdSelector: !!this.readOnly ? '#bx-timeman-pwt-popup-preview' : '#bx-timeman-pwt-popup-editor',
          popupContent: {
            privateCode: '',
            title: '',
            time: '',
            comment: '',
            detail: '',
            type: '',
            onSaveComment: ''
          },
          comment: '',
          isCommentPopup: false,
          isDetailPopup: false
        };
      },
      created: function created() {
        var _this = this;

        setInterval(function () {
          return _this.refreshCount++;
        }, 60000);
      },
      computed: babelHelpers.objectSpread({}, ui_vuex.Vuex.mapGetters('monitor', ['getWorkingEntities', 'getPersonalEntities']), ui_vuex.Vuex.mapState({
        monitor: function monitor(state) {
          return state.monitor;
        }
      }), {
        EntityType: function EntityType() {
          return timeman_const.EntityType;
        },
        EntityGroup: function EntityGroup() {
          return timeman_const.EntityGroup;
        },
        displayedGroup: function displayedGroup() {
          if (this.EntityGroup.getValues().includes(this.group)) {
            return this.EntityGroup[this.group];
          }
        },
        items: function items() {
          this.refreshCount;

          switch (this.displayedGroup.value) {
            case timeman_const.EntityGroup.working.value:
              return this.getWorkingEntities;

            case timeman_const.EntityGroup.personal.value:
              return this.getPersonalEntities;
          }
        },
        time: function time() {
          this.refreshCount;

          switch (this.displayedGroup.value) {
            case timeman_const.EntityGroup.working.value:
              return this.workTime;

            case timeman_const.EntityGroup.personal.value:
              return this.personalTime;
          }
        }
      }),
      methods: {
        onCommentClick: function onCommentClick(event) {
          var _this2 = this;

          this.isCommentPopup = true;
          this.popupContent.privateCode = event.content.privateCode;
          this.popupContent.title = event.content.title;
          this.popupContent.time = event.content.time;
          this.popupContent.type = event.content.type;
          this.popupContent.onSaveComment = event.onSaveComment;
          this.comment = event.content.comment;

          if (this.popupInstance !== null) {
            this.popupInstance.destroy();
            this.popupInstance = null;
          }

          var popup = main_popup.PopupManager.create({
            id: "bx-timeman-pwt-external-data",
            targetContainer: document.body,
            autoHide: true,
            closeByEsc: true,
            bindOptions: {
              position: "top"
            },
            events: {
              onPopupDestroy: function onPopupDestroy() {
                _this2.isCommentPopup = false;
                _this2.popupInstance = null;
              }
            }
          }); //little hack for correct open several popups in a row.

          this.$nextTick(function () {
            return _this2.popupInstance = popup;
          });
        },
        onDetailClick: function onDetailClick(event) {
          var _this3 = this;

          this.isDetailPopup = true;
          this.popupContent.privateCode = event.content.privateCode;
          this.popupContent.title = event.content.title;
          this.popupContent.time = event.content.time;
          this.popupContent.detail = event.content.detail;

          if (this.popupInstance !== null) {
            this.popupInstance.destroy();
            this.popupInstance = null;
          }

          var popup = main_popup.PopupManager.create({
            id: "bx-timeman-pwt-external-data",
            targetContainer: document.body,
            autoHide: true,
            closeByEsc: true,
            bindOptions: {
              position: "top"
            },
            events: {
              onPopupDestroy: function onPopupDestroy() {
                _this3.isDetailPopup = false;
                _this3.popupInstance = null;
              }
            }
          }); //little hack for correct open several popups in a row.

          this.$nextTick(function () {
            return _this3.popupInstance = popup;
          });
        },
        saveComment: function saveComment(privateCode) {
          if (this.comment.trim() === '' && this.popupContent.type === timeman_const.EntityType.absence) {
            return;
          }

          this.$store.dispatch('monitor/setComment', {
            privateCode: privateCode,
            comment: this.comment
          });

          if (typeof this.popupContent.onSaveComment === 'function') {
            this.popupContent.onSaveComment();
          }

          this.popupInstance.destroy();
        }
      },
      // language=Vue
      template: "\t\t  \n\t\t<div class=\"bx-monitor-group-wrap\">\t\t\t\n\t\t\t<div class=\"bx-monitor-group\">\t\t\t\t  \n\t\t\t\t<div class=\"bx-monitor-group-header\" v-bind:style=\"{ background: displayedGroup.secondaryColor }\">\n\t\t\t\t\t<div class=\"bx-monitor-group-title-wrap\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-title\">\n\t\t\t\t\t\t\t{{ displayedGroup.title }}\n                        </div>\n                      \t<div class=\"bx-monitor-group-title-wrap\">\n\t\t\t\t\t\t\t<div class=\"bx-monitor-group-subtitle\">\n                              {{ formatSeconds(time) }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div v-if=\"!readOnly\" class=\"bx-monitor-group-subtitle-wrap\">\n\t\t\t\t\t\t<div class=\"bx-monitor-group-hint\">\n\t\t\t\t\t\t\t{{ displayedGroup.hint }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"bx-monitor-group-content\" v-bind:style=\"{ background: displayedGroup.lightColor }\">\n\t\t\t\t\t<transition-group name=\"bx-monitor-group-item\" class=\"bx-monitor-group-content-wrap\">\t\t\t\t\t\t  \n\t\t\t\t\t  \n                      <GroupItem\n\t\t\t\t\t\tv-for=\"item of items\"\n                        :key=\"item.privateCode ? item.privateCode : item.title\"\n\t\t\t\t\t\t:group=\"displayedGroup.value\"\n\t\t\t\t\t\t:privateCode=\"item.privateCode\"\n\t\t\t\t\t  \t:type=\"item.type\"\n\t\t\t\t\t\t:title=\"item.title\"\n                        :comment=\"item.comment\"\n\t\t\t\t\t\t:time=\"formatSeconds(item.time)\"\n\t\t\t\t\t\t:allowedTime=\"item.allowedTime ? formatSeconds(item.allowedTime) : null\"\n\t\t\t\t\t\t:readOnly=\"!!readOnly\"\n\t\t\t\t\t\t:hint=\"item.hint !== '' ? item.hint : null\"\n                        @commentClick=\"onCommentClick\"\n                        @detailClick=\"onDetailClick\"\n\t\t\t\t\t  />\n                      \t\t\t\t\t\t  \n\t\t\t\t\t</transition-group>\n\t\t\t\t</div>\n\t\t\t</div>\n\n            <mounting-portal :mount-to=\"popupIdSelector\" append v-if=\"popupInstance\">\n            \t<div class=\"bx-timeman-monitor-popup-wrap\">\t\t\t\t\t\n\t\t\t\t\t<div class=\"popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height\" style=\"padding: 0\">\n\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-title popup-window-titlebar\">\n\t\t\t\t\t  \t\t<span class=\"popup-window-titlebar-text\">\n\t\t\t\t\t\t\t\t{{ popupContent.title }}\n\t\t\t\t\t \t\t</span>\n\t\t\t\t\t\t\t<span class=\"popup-window-titlebar-text\">\n\t\t\t\t\t\t\t\t{{ popupContent.time }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"popup-window-content\" style=\"overflow: auto; background: transparent;\">\n\t\t\t\t\t\t\t<textarea \n\t\t\t\t\t\t\t\tclass=\"bx-timeman-monitor-popup-input\"\n\t\t\t\t\t\t\t\tid=\"bx-timeman-monitor-popup-input-comment\"\n\t\t\t\t\t\t\t\tv-if=\"isCommentPopup\"\n\t\t\t\t\t\t\t\t:placeholder=\"$Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_ITEM_COMMENT')\"\n\t\t\t\t\t\t\t\tv-model=\"comment\"\n                                @keydown.enter.prevent=\"saveComment(popupContent.privateCode)\"\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\n                            />\n\t\t\t\t\t\t\t<div v-if=\"isDetailPopup\" class=\"bx-timeman-monitor-popup-items-container\">\n\t\t\t\t\t\t\t  \t<div \n\t\t\t\t\t\t\t\t\tv-for=\"detailItem in popupContent.detail\" \n\t\t\t\t\t\t\t\t\tclass=\"bx-timeman-monitor-popup-item\"\n\t\t\t\t\t\t\t  \t>\n\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-content\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"bx-timeman-monitor-popup-content-title\">\n                                        \t{{ detailItem.siteTitle }}\n                                      \t</div>\n                                      \t<div class=\"bx-timeman-monitor-popup-content-title\">\n                                        \t<a target=\"_blank\" :href=\"detailItem.siteUrl\" class=\"bx-timeman-monitor-popup-content-title\">\n                                        \t\t{{ detailItem.siteUrl }}\n                                        \t</a>\n                                      \t</div>\n                                    </div>\n                                    <div class=\"bx-timeman-monitor-popup-time\">\n                                    \t{{ formatSeconds(detailItem.time) }}\n                                    </div>\n\t\t\t\t\t\t\t\t</div>\t\t\t\t\t\t\t\t  \n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"popup-window-buttons\">\n\t\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t\tv-if=\"isCommentPopup\" \n\t\t\t\t\t\t\t\t@click=\"saveComment(popupContent.privateCode)\" \n\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-primary\"\n\t\t\t\t\t\t\t\t:class=\"{'ui-btn-disabled': (comment.trim() === '' && popupContent.type === EntityType.absence)}\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_BUTTON_OK') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t  \t<button @click=\"popupInstance.destroy()\" class=\"ui-btn ui-btn-md ui-btn-light\">\n\t\t\t\t\t\t\t\t<span v-if=\"isCommentPopup\" class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_BUTTON_CANCEL') }}\n\t\t\t\t\t\t\t\t</span>\n                              \t<span v-if=\"isDetailPopup\" class=\"ui-btn-text\">\n\t\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_BUTTON_CLOSE') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\t\t\t\t\t\n\t\t\t\t</div>\n            </mounting-portal>\n\t\t</div>\n\t"
    });

}((this.BX.Timeman.Component.Monitor = this.BX.Timeman.Component.Monitor || {}),BX,BX,BX.Timeman.Mixin,BX.Timeman.Const,window,BX.Vue,BX.Main,BX.UI.Dialogs));
//# sourceMappingURL=monitor-group.bundle.js.map
