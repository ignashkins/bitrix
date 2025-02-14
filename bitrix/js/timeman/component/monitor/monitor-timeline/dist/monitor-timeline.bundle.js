this.BX = this.BX || {};
this.BX.Timeman = this.BX.Timeman || {};
this.BX.Timeman.Component = this.BX.Timeman.Component || {};
(function (exports,ui_vuex,timeman_mixin,timeman_const,ui_vue) {
    'use strict';

    var DescriptionItem = ui_vue.BitrixVue.localComponent('bx-timeman-component-monitor-monitor-timeline-description-item', {
      props: ['title', 'value', 'dot'],
      // language=Vue
      template: "\n\t\t<div class=\"bx-monitor-timeline-description-item\">\n\t\t\t<div class=\"bx-monitor-timeline-description-title\">\n\t\t\t  <div\n\t\t\t\t  class=\"bx-monitor-timeline-description-dot\"\n\t\t\t\t  v-if=\"dot\"\n\t\t\t\t  :style=\"{ background: dot.color }\"\n\t\t\t  />\n\t\t\t  {{ title }}: {{ value }}\n\t\t\t</div>\n\t\t</div>\n\t"
    });

    var Timeline = ui_vue.BitrixVue.localComponent('bx-timeman-component-monitor-monitor-timeline-timeline', {
      props: ['items', 'fullTime'],
      computed: {
        lineItems: function lineItems() {
          var _this = this;

          return this.items.map(function (item) {
            return {
              time: item.time,
              width: Math.round(item.time * 100 / _this.fullTime),
              primaryColor: item.primaryColor
            };
          });
        }
      },
      // language=Vue
      template: "\n\t\t<div class=\"bx-monitor-timeline\">\n\t\t\t<div class=\"bx-monitor-timeline-line-wrap\">\n\t\t\t\t<div class=\"bx-monitor-timeline-line-container\">\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"bx-monitor-timeline-line-item\"\n\t\t\t\t\t\tv-for=\"item of lineItems\"\n\t\t\t\t\t\t:key=\"item.id\"\n\t\t\t\t\t\t:style=\"{ \n\t\t\t\t\t\t  width: item.time > 0 ? (item.width > 2 ? item.width + '%' : '16px') : 0,\n\t\t\t\t\t\t  background: item.primaryColor \n\t\t\t\t\t\t}\"\n\t\t\t\t\t/>\n\t\t\t\t</div>\t\t  \n\t\t\t</div>\n\t\t</div>\n\t"
    });

    ui_vue.Vue.component('bx-timeman-component-monitor-timeline', {
      components: {
        DescriptionItem: DescriptionItem,
        Timeline: Timeline
      },
      mixins: [timeman_mixin.Time],
      computed: babelHelpers.objectSpread({}, ui_vuex.Vuex.mapState({
        monitor: function monitor(state) {
          return state.monitor;
        }
      }), {
        EntityGroup: function EntityGroup() {
          return timeman_const.EntityGroup;
        },
        timelineItems: function timelineItems() {
          return [{
            id: 1,
            time: this.workTime,
            primaryColor: timeman_const.EntityGroup.working.primaryColor
          }, {
            id: 2,
            time: this.personalTime,
            primaryColor: timeman_const.EntityGroup.personal.primaryColor
          }];
        }
      }),
      // language=Vue
      template: "\n\t\t<div class=\"bx-monitor-timeline-wrap\">\n\t\t\t<div class=\"bx-monitor-container\">\n\t\t\t  \t<div class=\"bx-monitor-timeline-content\">\n\t\t\t\t\t<Timeline\n\t\t\t\t\t\t:items=\"timelineItems\"\n\t\t\t\t\t\t:fullTime=\"fullTime\"\n\t\t\t\t\t/>\n\t\t\t\t</div>\n\t\t\t  \t\n\t\t\t\t<div class=\"bx-monitor-timeline-description\">\n\t\t\t\t\t<div class=\"bx-monitor-timeline-description-container\">  \n\t\t\t\t\t\t<DescriptionItem\n\t\t\t\t\t\t\t:title=\"$Bitrix.Loc.getMessage('TIMEMAN_MONITOR_TIMELINE_WORK_TIME')\"\n\t\t\t\t\t\t\t:value=\"formatSeconds(workTime)\"\n\t\t\t\t\t\t\t:dot=\"{color: EntityGroup.working.primaryColor}\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<DescriptionItem\n\t\t\t\t\t\t\t:title=\"$Bitrix.Loc.getMessage('TIMEMAN_MONITOR_TIMELINE_PERSONAL_TIME')\"\n\t\t\t\t\t\t\t:value=\"formatSeconds(personalTime)\"\n\t\t\t\t\t\t\t:dot=\"{color: EntityGroup.personal.primaryColor}\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
    });

}((this.BX.Timeman.Component.Monitor = this.BX.Timeman.Component.Monitor || {}),BX,BX.Timeman.Mixin,BX.Timeman.Const,BX));
//# sourceMappingURL=monitor-timeline.bundle.js.map
