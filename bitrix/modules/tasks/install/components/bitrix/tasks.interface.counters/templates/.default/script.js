this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_popup,main_core,main_core_events) {
	'use strict';

	var Filter = /*#__PURE__*/function () {
	  function Filter(options) {
	    babelHelpers.classCallCheck(this, Filter);
	    this.filterId = options.filterId;
	    this.filterManager = BX.Main.filterManager.getById(this.filterId);
	    this.bindEvents();
	    this.updateFields();
	  }

	  babelHelpers.createClass(Filter, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	    }
	  }, {
	    key: "onFilterApply",
	    value: function onFilterApply() {
	      this.updateFields();
	    }
	  }, {
	    key: "updateFields",
	    value: function updateFields() {
	      this.fields = this.filterManager.getFilterFieldsValues();
	    }
	  }, {
	    key: "isFilteredByField",
	    value: function isFilteredByField(field) {
	      if (!Object.keys(this.fields).includes(field)) {
	        return false;
	      }

	      if (main_core.Type.isArray(this.fields[field])) {
	        return this.fields[field].length > 0;
	      }

	      return this.fields[field] !== '';
	    }
	  }, {
	    key: "isFilteredByFieldValue",
	    value: function isFilteredByFieldValue(field, value) {
	      return this.isFilteredByField(field) && this.fields[field] === value;
	    }
	  }, {
	    key: "toggleByField",
	    value: function toggleByField(field) {
	      var _this = this;

	      var name = Object.keys(field)[0];
	      var value = field[name];

	      if (!this.isFilteredByFieldValue(name, value)) {
	        this.filterManager.getApi().extendFilter(babelHelpers.defineProperty({}, name, value), false, {
	          COUNTER_TYPE: 'TASKS_COUNTER_TYPE_' + value
	        });
	        return;
	      }

	      this.filterManager.getFilterFields().forEach(function (field) {
	        if (field.getAttribute('data-name') === name) {
	          _this.filterManager.getFields().deleteField(field);
	        }
	      });
	      this.filterManager.getSearch().apply();
	    }
	  }, {
	    key: "getFilter",
	    value: function getFilter() {
	      return this.filterManager;
	    }
	  }]);
	  return Filter;
	}();

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-counters--item-counter ", "\">\n\t\t\t\t\t<div class=\"tasks-counters--item-counter-wrapper\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"tasks-counters--item-counter-title\">", "</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t<div class=\"task-counters--popup-item\">\n\t\t\t\t\t<span class=\"tasks-counters--item-counter-num ", "\">", "</span>\n\t\t\t\t\t<span class=\"task-counters--popup-item-text\">", "</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-counters--item-counter-remove\"></div>\n\t\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-counters--item-counter-num-text ", "\">", "</div>\n\t\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-counters--item-counter-num ", "\">\n\t\t\t\t\t<div class=\"tasks-counters--item-counter-num-text --stop --without-animate\">", "</div>\t\t\t\t\t\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var CountersItem = /*#__PURE__*/function () {
	  function CountersItem(options) {
	    babelHelpers.classCallCheck(this, CountersItem);
	    this.count = options.count;
	    this.name = options.name;
	    this.type = options.type;
	    this.color = options.color;
	    this.filterField = options.filterField;
	    this.filterValue = options.filterValue;
	    this.filter = options.filter;
	    this.$container = null;
	    this.$remove = null;
	    this.$counter = null;
	    this.bindEvents();
	  }

	  babelHelpers.createClass(CountersItem, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;

	      main_core_events.EventEmitter.subscribe('BX.Tasks.Counters:active', function (param) {
	        _this !== param.data ? _this.unActive() : null;
	      });
	    }
	  }, {
	    key: "getCounter",
	    value: function getCounter() {
	      if (!this.$counter) {
	        var count = this.count > 99 ? '99+' : this.count;
	        this.$counter = main_core.Tag.render(_templateObject(), this.getCounterColor(), count);
	      }

	      return this.$counter;
	    }
	  }, {
	    key: "getCounterColor",
	    value: function getCounterColor() {
	      if (!this.color) {
	        return null;
	      }

	      return "--".concat(this.color);
	    }
	  }, {
	    key: "animateCounter",
	    value: function animateCounter(start, value) {
	      var _this2 = this;

	      if (start > 99 && value > 99) return;
	      value > 99 ? value = 99 : null;
	      if (start > 99) start = 99;
	      var duration = start - value;
	      if (duration < 0) duration = duration * -1;
	      this.$counter.innerHTML = '';
	      this.getCounter().classList.remove('--update');
	      this.getCounter().classList.remove('--update-multi');

	      if (duration > 5) {
	        setTimeout(function () {
	          _this2.getCounter().style.animationDuration = duration * 50 + 'ms';

	          _this2.getCounter().classList.add('--update-multi');
	        });
	      }

	      var timer = setInterval(function () {
	        value < start ? start-- : start++;
	        var node = main_core.Tag.render(_templateObject2(), value < start ? '--decrement' : '', start);

	        if (start === value) {
	          node.classList.add('--stop');
	          if (duration < 5) _this2.getCounter().classList.add('--update');
	          clearInterval(timer);
	          start === 0 ? _this2.fade() : _this2.unFade();
	        }

	        if (start !== value) {
	          main_core.Event.bind(node, 'animationend', function () {
	            node.parentNode.removeChild(node);
	          });
	        }

	        _this2.$counter.appendChild(node);
	      }, 50);
	    }
	  }, {
	    key: "updateCount",
	    value: function updateCount(param) {
	      if (this.count === param) return;
	      this.animateCounter(this.count, param);
	      this.count = param;
	    }
	  }, {
	    key: "getRemove",
	    value: function getRemove() {
	      if (!this.$remove) {
	        this.$remove = main_core.Tag.render(_templateObject3());
	      }

	      return this.$remove;
	    }
	  }, {
	    key: "fade",
	    value: function fade() {
	      this.getContainer().classList.add('--fade');
	    }
	  }, {
	    key: "unFade",
	    value: function unFade() {
	      this.getContainer().classList.remove('--fade');
	    }
	  }, {
	    key: "active",
	    value: function active(node) {
	      var targetNode = main_core.Type.isDomNode(node) ? node : this.getContainer();
	      targetNode.classList.add('--hover');
	      main_core_events.EventEmitter.emit('BX.Tasks.Counters:active', this);
	    }
	  }, {
	    key: "unActive",
	    value: function unActive(node) {
	      var targetNode = main_core.Type.isDomNode(node) ? node : this.getContainer();
	      targetNode.classList.remove('--hover');
	      main_core_events.EventEmitter.emit('BX.Tasks.Counters:unActive', this);
	    }
	  }, {
	    key: "adjustClick",
	    value: function adjustClick() {
	      main_core_events.EventEmitter.emit('Tasks.Toolbar:onItem', {
	        counter: this
	      });
	      this.$container.classList.contains('--hover') ? this.unActive() : this.active();
	    }
	  }, {
	    key: "getPopupMenuItemContainer",
	    value: function getPopupMenuItemContainer() {
	      var title = main_core.Loc.getMessage('TASKS_COUNTER_OTHER_TASKS').replace('#TITLE#', this.name.toLowerCase());
	      return main_core.Tag.render(_templateObject4(), this.getCounterColor(), this.count, title);
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer(param) {
	      if (!this.$container) {
	        this.$container = main_core.Tag.render(_templateObject5(), Number(this.count) === 0 ? ' --fade' : '', this.getCounter(), this.name, this.getRemove());

	        if (this.filter.isFilteredByFieldValue(this.filterField, this.filterValue)) {
	          this.active(this.$container);
	        }

	        main_core.Event.bind(this.$container, 'click', this.adjustClick.bind(this));
	      }

	      return this.$container;
	    }
	  }]);
	  return CountersItem;
	}();

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-counters tasks-counters--scope\">\n\t\t\t\t<div class=\"tasks-counters--item\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"tasks-counters--item-content\">", "</div>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-counters--item-head\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-counters--item --other\" ", "\">\n\t\t\t\t<div data-role=\"tasks-counters--item-head-other\" class=\"tasks-counters--item-head\">", "</div>\n\t\t\t\t<div class=\"tasks-counters--item-content\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-counters--item-counter--more\">\n\t\t\t\t<div class=\"tasks-counters--item-counter-wrapper\">\n\t\t\t\t\t<div class=\"tasks-counters--item-counter-title\">", ":</div>\n\t\t\t\t\t<div class=\"tasks-counters--item-counter-num\">", "</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject5$1 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-counters--item-counter-arrow\"></div>\n\t\t"]);

	  _templateObject4$1 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-counters--item-counter-arrow\"></div>\n\t\t\t"]);

	  _templateObject3$1 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-counters--item\">", "</div>\n\t\t"]);

	  _templateObject2$1 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div data-role=\"tasks-counters--item-head-read-all\" class=\"tasks-counters--item-head\n\t\t\t\t\t\t", " \n\t\t\t\t\t\t--action \n\t\t\t\t\t\t--read-all\">\n\t\t\t\t<div class=\"tasks-counters--item-head-read-all--icon\"></div>\n\t\t\t\t<div class=\"tasks-counters--item-head-read-all--text\">", "</div>\n\t\t\t\t\n\t\t\t</div>\n\t\t"]);

	  _templateObject$1 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Counters = /*#__PURE__*/function () {
	  babelHelpers.createClass(Counters, null, [{
	    key: "counterTypes",
	    get: function get() {
	      return {
	        my: ['expired', 'my_expired', 'originator_expired', 'accomplices_expired', 'auditor_expired', 'new_comments', 'my_new_comments', 'originator_new_comments', 'accomplices_new_comments', 'auditor_new_comments', 'projects_total_expired', 'projects_total_comments', 'sonet_total_expired', 'sonet_total_comments', 'groups_total_expired', 'groups_total_comments'],
	        other: ['project_expired', 'project_comments', 'projects_foreign_expired', 'projects_foreign_comments', 'groups_foreign_expired', 'groups_foreign_comments', 'sonet_foreign_expired', 'sonet_foreign_comments'],
	        expired: ['expired', 'my_expired', 'originator_expired', 'accomplices_expired', 'auditor_expired', 'project_expired', 'projects_total_expired', 'projects_foreign_expired', 'groups_total_expired', 'groups_foreign_expired', 'sonet_total_expired', 'sonet_foreign_expired'],
	        comment: ['new_comments', 'my_new_comments', 'originator_new_comments', 'accomplices_new_comments', 'auditor_new_comments', 'project_comments', 'projects_total_comments', 'projects_foreign_comments', 'groups_total_comments', 'groups_foreign_comments', 'sonet_total_comments', 'sonet_foreign_comments']
	      };
	    }
	  }]);

	  function Counters(options) {
	    babelHelpers.classCallCheck(this, Counters);
	    this.userId = options.userId;
	    this.targetUserId = options.targetUserId;
	    this.groupId = options.groupId;
	    this.counters = options.counters;
	    this.initialCounterTypes = options.counterTypes;
	    this.renderTo = options.renderTo;
	    this.role = options.role;
	    this.signedParameters = options.signedParameters;
	    this.popupMenu = null;
	    this.$readAll = {
	      cropped: null,
	      layout: null
	    };
	    this.$more = null;
	    this.$moreArrow = null;
	    this.$other = {
	      cropped: null,
	      layout: null
	    };
	    this.$myTaskHead = null;
	    this.filter = new Filter({
	      filterId: options.filterId
	    });
	    this.bindEvents();
	    this.setData(this.counters);
	    this.initPull();
	  }

	  babelHelpers.createClass(Counters, [{
	    key: "isMyTaskList",
	    value: function isMyTaskList() {
	      return this.userId === this.targetUserId;
	    }
	  }, {
	    key: "isUserTaskList",
	    value: function isUserTaskList() {
	      return Object.keys(this.otherCounters).length === 0;
	    }
	  }, {
	    key: "isProjectsTaskList",
	    value: function isProjectsTaskList() {
	      return this.groupId > 0;
	    }
	  }, {
	    key: "isProjectList",
	    value: function isProjectList() {
	      return !this.isUserTaskList() && !this.isProjectsTaskList();
	    }
	  }, {
	    key: "initPull",
	    value: function initPull() {
	      var _this = this;

	      BX.PULL.subscribe({
	        moduleId: 'tasks',
	        callback: function callback(data) {
	          return _this.processPullEvent(data);
	        }
	      });
	      this.extendWatch();
	    }
	  }, {
	    key: "extendWatch",
	    value: function extendWatch() {
	      var _this2 = this;

	      if (this.isProjectsTaskList() || this.isProjectList()) {
	        var tagId = 'TASKS_PROJECTS';

	        if (this.isProjectsTaskList()) {
	          tagId = "TASKS_PROJECTS_".concat(this.groupId);
	        }

	        BX.PULL.extendWatch(tagId, true);
	        setTimeout(function () {
	          return _this2.extendWatch();
	        }, 29 * 60 * 1000);
	      }
	    }
	  }, {
	    key: "processPullEvent",
	    value: function processPullEvent(data) {
	      var eventHandlers = {
	        user_counter: this.onUserCounter.bind(this),
	        project_counter: this.onProjectCounter.bind(this)
	      };
	      var has = Object.prototype.hasOwnProperty;
	      var command = data.command,
	          params = data.params;

	      if (has.call(eventHandlers, command)) {
	        var method = eventHandlers[command];

	        if (method) {
	          method.apply(this, [params]);
	        }
	      }
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	    }
	  }, {
	    key: "onFilterApply",
	    value: function onFilterApply() {
	      var _this3 = this;

	      this.filter.updateFields();

	      if (this.isRoleChanged()) {
	        this.updateRole();
	        this.updateCountersData();
	      } else {
	        var counters = Object.assign({}, this.myCounters, this.otherCounters);
	        Object.values(counters).forEach(function (counter) {
	          if (counter) {
	            _this3.filter.isFilteredByFieldValue(counter.filterField, counter.filterValue) ? counter.active() : counter.unActive();
	          }
	        });
	      }
	    }
	  }, {
	    key: "updateCountersData",
	    value: function updateCountersData() {
	      var _this4 = this;

	      main_core.ajax.runComponentAction('bitrix:tasks.interface.counters', 'getCounters', {
	        mode: 'class',
	        data: {
	          groupId: this.groupId,
	          role: this.role,
	          counters: this.initialCounterTypes
	        },
	        signedParameters: this.signedParameters
	      }).then(function (response) {
	        return _this4.rerender(response.data);
	      }, function (response) {
	        return console.log(response);
	      });
	    }
	  }, {
	    key: "isRoleChanged",
	    value: function isRoleChanged() {
	      return this.role !== (this.filter.isFilteredByField('ROLEID') ? this.filter.fields.ROLEID : 'view_all');
	    }
	  }, {
	    key: "updateRole",
	    value: function updateRole() {
	      this.role = this.filter.isFilteredByField('ROLEID') ? this.filter.fields.ROLEID : 'view_all';
	    }
	  }, {
	    key: "onUserCounter",
	    value: function onUserCounter(data) {
	      var _this5 = this;

	      var has = Object.prototype.hasOwnProperty;

	      if (!this.isUserTaskList() || !this.isMyTaskList() || !has.call(data, this.groupId) || this.userId !== Number(data.userId)) {
	        return;
	      }
	      Object.entries(data[this.groupId][this.role]).forEach(function (_ref) {
	        var _ref2 = babelHelpers.slicedToArray(_ref, 2),
	            type = _ref2[0],
	            value = _ref2[1];

	        if (_this5.myCounters[type]) {
	          _this5.myCounters[type].updateCount(value);

	          if (value === 0) {
	            _this5.myCounters[type].unActive();
	          } else if (Counters.counterTypes.comment.includes(type)) {
	            _this5.$readAllInner.classList.remove('--fade');
	          }
	        }
	      });
	    }
	  }, {
	    key: "onProjectCounter",
	    value: function onProjectCounter(data) {
	      if (this.isUserTaskList()) {
	        return;
	      }

	      this.updateCountersData();
	    }
	  }, {
	    key: "getCounterItem",
	    value: function getCounterItem(param) {
	      return new CountersItem({
	        count: param.count,
	        name: param.name,
	        type: param.type,
	        color: param.color,
	        filterField: param.filterField,
	        filterValue: param.filterValue,
	        filter: this.filter
	      });
	    }
	  }, {
	    key: "getCounterNameByType",
	    value: function getCounterNameByType(type) {
	      if (Counters.counterTypes.expired.includes(type)) {
	        return main_core.Loc.getMessage('TASKS_COUNTER_EXPIRED');
	      } else if (Counters.counterTypes.comment.includes(type)) {
	        return main_core.Loc.getMessage('TASKS_COUNTER_NEW_COMMENTS');
	      }
	    }
	  }, {
	    key: "setData",
	    value: function setData(counters) {
	      var _this6 = this;

	      this.myCounters = {};
	      this.otherCounters = {};
	      Object.entries(counters).forEach(function (_ref3) {
	        var _ref4 = babelHelpers.slicedToArray(_ref3, 2),
	            type = _ref4[0],
	            data = _ref4[1];

	        if (!Counters.counterTypes.my.includes(type) && !Counters.counterTypes.other.includes(type)) {
	          return;
	        }

	        var counterItem = _this6.getCounterItem({
	          type: type,
	          name: _this6.getCounterNameByType(type),
	          count: Number(data.VALUE),
	          color: data.STYLE,
	          filterField: data.FILTER_FIELD,
	          filterValue: data.FILTER_VALUE
	        });

	        if (Counters.counterTypes.my.includes(type)) {
	          _this6.myCounters[type] = counterItem;
	        } else if (Counters.counterTypes.other.includes(type)) {
	          _this6.otherCounters[type] = counterItem;
	        }
	      });
	    }
	  }, {
	    key: "isCroppedBlock",
	    value: function isCroppedBlock(node) {
	      if (node) return node.classList.contains('--cropp');
	    }
	  }, {
	    key: "getReadAllBlock",
	    value: function getReadAllBlock() {
	      var _this7 = this;

	      var counters = Object.assign({}, this.myCounters, this.otherCounters);
	      var newCommentsCount = 0;
	      Object.entries(counters).forEach(function (_ref5) {
	        var _ref6 = babelHelpers.slicedToArray(_ref5, 2),
	            type = _ref6[0],
	            counter = _ref6[1];

	        if (Counters.counterTypes.comment.includes(type)) {
	          newCommentsCount += counter.count;
	        }
	      });
	      this.$readAllInner = main_core.Tag.render(_templateObject$1(), newCommentsCount === 0 ? '--fade' : '', main_core.Loc.getMessage('TASKS_COUNTER_READ_ALL'));
	      var readAllClick = this.isUserTaskList() || this.isProjectsTaskList() && this.role !== 'view_all' ? this.readAllByRole.bind(this) : this.readAllForProjects.bind(this);
	      main_core.Event.bind(this.$readAllInner, 'click', readAllClick);
	      main_core.Event.bind(this.$readAllInner, 'click', function () {
	        return _this7.$readAllInner.classList.add('--fade');
	      });
	      this.$readAll.layout = main_core.Tag.render(_templateObject2$1(), this.$readAllInner);
	      return this.$readAll.layout;
	    }
	  }, {
	    key: "readAllByRole",
	    value: function readAllByRole() {
	      main_core.ajax.runAction('tasks.task.comment.readAll', {
	        data: {
	          groupId: this.groupId,
	          userId: this.userId,
	          role: this.role
	        }
	      });
	    }
	  }, {
	    key: "readAllForProjects",
	    value: function readAllForProjects() {
	      var allCounters = Object.assign(this.myCounters, this.otherCounters);
	      Object.entries(allCounters).forEach(function (_ref7) {
	        var _ref8 = babelHelpers.slicedToArray(_ref7, 1),
	            type = _ref8[0];

	        if (allCounters[type] && Counters.counterTypes.comment.includes(type)) {
	          allCounters[type].updateCount(0);
	          allCounters[type].unActive();
	        }
	      });
	      main_core.ajax.runAction('tasks.task.comment.readProject', {
	        data: {
	          groupId: this.groupId
	        }
	      });
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      var _this8 = this;

	      var itemsNode = [];
	      Object.values(this.otherCounters).forEach(function (counter) {
	        var menuItem = new main_popup.MenuItem({
	          html: counter.getPopupMenuItemContainer().innerHTML
	        });
	        menuItem.onclick = _this8.onPopupItemClick.bind(_this8, menuItem, counter);
	        itemsNode.push(menuItem);
	      });
	      this.popupMenu = new main_popup.Menu({
	        bindElement: this.$moreArrow,
	        className: 'tasks-counters--scope',
	        angle: {
	          offset: 96
	        },
	        autoHide: true,
	        closeEsc: true,
	        offsetTop: 5,
	        offsetLeft: -67,
	        animation: 'fading-slide',
	        items: itemsNode,
	        events: {
	          onPopupShow: function onPopupShow() {
	            return _this8.$more.classList.add('--hover');
	          },
	          onPopupClose: function onPopupClose() {
	            _this8.$more.classList.remove('--hover');

	            _this8.popupMenu.destroy();
	          }
	        }
	      });
	      return this.popupMenu;
	    }
	  }, {
	    key: "onPopupItemClick",
	    value: function onPopupItemClick(item, counter) {
	      counter.adjustClick();
	      this.popupMenu.close();
	    }
	  }, {
	    key: "getMoreArrow",
	    value: function getMoreArrow() {
	      if (!this.$moreArrow) {
	        this.$moreArrow = main_core.Tag.render(_templateObject3$1());
	      }

	      return this.$moreArrow;
	    }
	  }, {
	    key: "getMore",
	    value: function getMore() {
	      var _this9 = this;

	      var value = 0;
	      Object.values(this.otherCounters).forEach(function (counter) {
	        value += Number(counter.count);
	      });
	      var count = value > 99 ? '99+' : value;
	      this.$moreArrow = main_core.Tag.render(_templateObject4$1());
	      this.$more = main_core.Tag.render(_templateObject5$1(), main_core.Loc.getMessage('TASKS_COUNTER_MORE'), count, this.$moreArrow);
	      main_core.Event.bind(this.$more, 'click', function () {
	        return _this9.getPopup().show();
	      });
	      return this.$more;
	    }
	  }, {
	    key: "getOther",
	    value: function getOther() {
	      if (Object.keys(this.otherCounters).length === 0) {
	        return '';
	      }

	      var content = [];
	      var totalCount = 0;
	      Object.values(this.otherCounters).forEach(function (counter) {
	        content.push(counter.getContainer());
	        if (counter.count) totalCount += counter.count;
	      });
	      this.$other.cropped = this.isCroppedBlock(this.$other.layout);
	      this.$other.layout = main_core.Tag.render(_templateObject6(), this.$other.cropped ? '--cropp' : '', main_core.Loc.getMessage('TASKS_COUNTER_OTHER'), content, this.getMore());
	      return this.$other.layout;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var content = [];
	      var totalCount = 0;
	      Object.values(this.myCounters).forEach(function (counter) {
	        content.push(counter.getContainer());
	        if (counter.count) totalCount += counter.count;
	      });
	      this.$myTaskHead = main_core.Tag.render(_templateObject7(), main_core.Loc.getMessage('TASKS_COUNTER_MY'));
	      this.$element = main_core.Tag.render(_templateObject8(), this.$myTaskHead, content, this.getOther(), this.isUserTaskList() && !this.isMyTaskList() ? '' : this.getReadAllBlock());
	      return this.$element;
	    }
	  }, {
	    key: "rerender",
	    value: function rerender(counters) {
	      this.setData(counters);
	      this.render();
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var node = this.getContainer();
	      var fakeNode = node.cloneNode(true);
	      fakeNode.classList.add('task-interface-toolbar');
	      fakeNode.style.position = 'fixed';
	      fakeNode.style.opacity = '0';
	      fakeNode.style.width = 'auto';
	      fakeNode.style.pointerEvents = 'none';
	      document.body.appendChild(fakeNode);
	      this.nodeWidth = fakeNode.offsetWidth;
	      document.body.removeChild(fakeNode);
	      main_core.Dom.replace(this.renderTo.firstChild, node);
	    }
	  }]);
	  return Counters;
	}();

	exports.Counters = Counters;

}((this.BX.Tasks.Counters = this.BX.Tasks.Counters || {}),BX.Main,BX,BX.Event));
//# sourceMappingURL=script.js.map
