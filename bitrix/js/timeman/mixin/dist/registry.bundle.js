this.BX = this.BX || {};
this.BX.Timeman = this.BX.Timeman || {};
(function (exports) {
	'use strict';

	var Time = {
	  computed: {
	    fullTime: function fullTime() {
	      return this.workTime + this.personalTime;
	    },
	    workTime: function workTime() {
	      return this.$store.getters['monitor/getWorkingEntities'].reduce(function (sum, entry) {
	        return sum + entry.time;
	      }, 0);
	    },
	    personalTime: function personalTime() {
	      return this.$store.getters['monitor/getPersonalEntities'].reduce(function (sum, entry) {
	        return sum + entry.time;
	      }, 0);
	    }
	  },
	  methods: {
	    formatSeconds: function formatSeconds(seconds) {
	      if (seconds < 1) {
	        return 0 + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_MIXIN_TIME_MINUTES_SHORT');
	      } else if (seconds < 60) {
	        return this.$Bitrix.Loc.getMessage('TIMEMAN_MIXIN_TIME_LESS_THAN_MINUTE');
	      }

	      var hours = Math.floor(seconds / 3600);
	      var minutes = Math.round(seconds / 60 % 60);

	      if (minutes === 60) {
	        hours += 1;
	        minutes = 0;
	      }

	      if (hours > 0) {
	        hours = hours + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_MIXIN_TIME_HOUR_SHORT');

	        if (minutes > 0) {
	          minutes = minutes + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_MIXIN_TIME_MINUTES_SHORT');
	          return hours + ' ' + minutes;
	        }

	        return hours;
	      }

	      return minutes + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_MIXIN_TIME_MINUTES_SHORT');
	    },
	    calculateEntryTime: function calculateEntryTime(entry) {
	      var time = entry.time.map(function (interval) {
	        var finish = interval.finish ? new Date(interval.finish) : new Date();
	        return finish - new Date(interval.start);
	      }).reduce(function (sum, interval) {
	        return sum + interval;
	      }, 0);
	      return Math.round(time / 1000);
	    },
	    getEntityByPrivateCode: function getEntityByPrivateCode(privateCode) {
	      return this.monitor.entity.find(function (entity) {
	        return entity.privateCode === privateCode;
	      });
	    }
	  }
	};

	exports.Time = Time;

}((this.BX.Timeman.Mixin = this.BX.Timeman.Mixin || {})));
//# sourceMappingURL=registry.bundle.js.map
