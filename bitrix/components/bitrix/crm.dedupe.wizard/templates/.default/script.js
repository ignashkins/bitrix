BX.namespace("BX.Crm");

if(typeof(BX.Crm.DedupeWizard) === "undefined")
{
	BX.Crm.DedupeWizard = function ()
	{
		this._id = "";
		this._settings = {};

		this._entityTypeId = 0;
		this._steps = {};
		this._config = {};
		this._typeInfos = {};
		this._currentScope = "";
		this._scopeInfos = {};
		this._contextId = "";

		this._totalItemCount = 0;
		this._totalEntityCount = 0;

		this._mergedItemCount = 0;
		this._mergedEntityCount = 0;

		this._conflictedItemCount = 0;
		this._conflictedEntityCount = 0;

		this._resolvedItemCount = 0;

		this._dedupeSettingsPath = '';
		this._enableCloseConfirmation = false;
		this._enableEntityListReload = false;
	};
	BX.Crm.DedupeWizard.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			this._currentScope = BX.prop.getString(this._settings, "currentScope", "");
			this._scopeInfos = BX.prop.getObject(this._settings, "scopeInfos", {});
			this._contextId = BX.prop.getString(this._settings, "contextId", "");
			this._typeInfos = BX.prop.getObject(this._settings, "typeInfos", {});
			this._config = BX.prop.getObject(this._settings, "config", {});
			this._dedupeSettingsPath = BX.prop.getString(this._settings, "dedupeSettingsPath", "");
			this._mergeMode = 'auto';
			
			this._steps = BX.prop.getObject(this._settings, "steps", {});
			for(var key in this._steps)
			{
				if(!this._steps.hasOwnProperty(key))
				{
					continue;
				}

				this._steps[key].setWizard(this);
			}
			this.bindSliderEvents();
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeId: function()
		{
			return this._entityTypeId;
		},
		getEntityTypeName: function()
		{
			return BX.CrmEntityType.resolveName(this._entityTypeId);
		},
		getConfig: function()
		{
			return BX.clone(this._config);
		},
		setConfig: function(config)
		{
			this._config = config;
			BX.onCustomEvent(this, "onConfigChange");
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.DedupeWizard.messages, name, name);
		},
		getContextId: function()
		{
			return this._contextId;
		},
		getCurrentScope: function()
		{
			return this._currentScope;
		},
		getScopeInfos: function()
		{
			return this._scopeInfos;
		},
		getTypeInfos: function()
		{
			return this._typeInfos;
		},
		layout: function()
		{
			this._steps["scanning"].start();
		},
		getMergerUrl: function()
		{
			return BX.prop.getString(this._settings, "mergerUrl", "");
		},
		getDedupeListUrl: function()
		{
			return BX.prop.getString(this._settings, "dedupeListUrl", "");
		},
		getTotalItemCount: function()
		{
			return this._totalItemCount;
		},
		setTotalItemCount: function(count)
		{
			this._totalItemCount = count;
		},
		getTotalEntityCount: function()
		{
			return this._totalEntityCount;
		},
		setTotalEntityCount: function(count)
		{
			this._totalEntityCount = count;
		},
		getMergedItemCount: function()
		{
			return this._mergedItemCount;
		},
		setMergedItemCount: function(count)
		{
			this._mergedItemCount = count;
		},
		getMergedEntityCount: function()
		{
			return this._mergedEntityCount;
		},
		setMergedEntityCount: function(count)
		{
			this._mergedEntityCount = count;
		},
		getConflictedItemCount: function()
		{
			return this._conflictedItemCount;
		},
		setConflictedItemCount: function(count)
		{
			this._conflictedItemCount = count;
		},
		getConflictedEntityCount: function()
		{
			return this._conflictedEntityCount;
		},
		setConflictedEntityCount: function(count)
		{
			this._conflictedEntityCount = count;
		},
		getDedupeSettingsPath: function()
		{
			return this._dedupeSettingsPath;
		},
		calculateEntityCount: function(items)
		{
			if(!BX.type.isArray(items))
			{
				return;
			}

			var result = {};
			for(var i = 0, length = items.length; i < length; i++)
			{
				var item = items[i];

				var rootEntityId = BX.prop.getInteger(item, "ROOT_ENTITY_ID", 0);
				if(rootEntityId > 0)
				{
					result[rootEntityId.toString()] = true;
				}
				var entityIds = BX.prop.getArray(item, "ENTITY_IDS", []);
				for(var j = 0; j < entityIds.length; j++)
				{
					result[entityIds[j].toString()] = true;
				}
			}
			return Object.keys(result).length;
		},
		getResolvedItemCount: function()
		{
			return this._resolvedItemCount;
		},
		setResolvedItemCount: function(count)
		{
			this._resolvedItemCount = count;
		},
		getUnResolvedItemCount: function()
		{
			return(this._conflictedItemCount - this._resolvedItemCount);
		},
		setMergeMode: function(mergeMode)
		{
			this._mergeMode = mergeMode;
			for (var i in this._steps)
			{
				var step = this._steps[i];
				var node = BX(step.getId());
				if (node)
				{
					node.classList.remove('crm-dedupe-wizard-container-merge-auto');
					node.classList.remove('crm-dedupe-wizard-container-merge-manual');
					node.classList.add('crm-dedupe-wizard-container-merge-' + mergeMode);
				}
			}
		},
		getMergeMode: function()
		{
			return this._mergeMode;
		},
		openDedupeList: function()
		{
			var params = {
				scope: BX.prop.getString(this._config, "scope", ""),
				typeNames: BX.prop.getArray(this._config, "typeNames", [])
			};
			BX.Crm.Page.open(BX.util.add_url_param(this.getDedupeListUrl(), params));
		},
		openMerger: function(contextId)
		{
			var params = {
				scope: BX.prop.getString(this._config, "scope", ""),
				typeNames: BX.prop.getArray(this._config, "typeNames", []),
				externalContextId: contextId
			};

			BX.Crm.Page.open(BX.util.add_url_param(this.getMergerUrl(), params));
		},
		enableCloseConfirmation: function(enableCloseConfirmation)
		{
			this._enableCloseConfirmation = !!enableCloseConfirmation;
		},
		enableEntityListReload: function(enableEntityListReload)
		{
			this._enableEntityListReload = !!enableEntityListReload;
		},
		bindSliderEvents: function()
		{
			BX.addCustomEvent("SidePanel.Slider:onClose", this.onSliderClose.bind(this));
		},
		reloadEntityList: function()
		{
			if (top.BX.CRM && top.BX.CRM.Kanban)
			{
				var kanban = top.BX.CRM.Kanban.Grid.getInstance();
				if (kanban)
				{
					kanban.reload();
				}
			}
			if (top.BX.Main.gridManager)
			{
				var gridId = 'CRM_' + this.getEntityTypeName() + '_LIST_V12'; // does not support deal categories
				var grid = top.BX.Main.gridManager.getInstanceById(gridId);
				if (grid)
				{
					grid.reload();
				}
			}
		},
		onSliderClose: function(event)
		{
			var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
			if(slider !== event.getSlider())
			{
				return;
			}

			if(!slider.isOpen())
			{
				return;
			}

			if(!this._enableCloseConfirmation)
			{
				if (this._enableEntityListReload)
				{
					this.reloadEntityList();
				}
				return;
			}

			event.denyAction();

			var popup = BX.Main.PopupManager.getPopupById('dedupe_wizard_close_confirmation');
			if (!popup)
			{
				popup = BX.Main.PopupManager.create({
					id: 'dedupe_wizard_close_confirmation',
					content: this.getMessage('closeConfirmationText'),
					titleBar: this.getMessage('closeConfirmationTitle'),
					buttons: [
						new BX.UI.CloseButton({
							color: BX.UI.ButtonColor.SUCCESS,
							events : {
								click: function(event) {
									event.getContext().close();
									this._enableCloseConfirmation = false;
									top.BX.SidePanel.Instance.getSliderByWindow(window).close();
								}.bind(this)
							}
						}),
						new BX.UI.CancelButton({
							events : {
								click: function(event) {
									event.getContext().close();
								}.bind(this)
							}
						})
					]
				})
			}
			popup.show();
		},
		onStepStart: function(step)
		{
		},
		onStepEnd: function(step)
		{
			var nextStepId = step.getNextStepId();
			if(!(nextStepId !== "" && this._steps.hasOwnProperty(nextStepId)))
			{
				return;
			}
			this._steps[nextStepId].start();
		}
	};
	if(typeof(BX.Crm.DedupeWizard.messages) === "undefined")
	{
		BX.Crm.DedupeWizard.messages = {};
	}
	BX.Crm.DedupeWizard.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizard();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardStep) === "undefined")
{
	BX.Crm.DedupeWizardStep = function ()
	{
		this._id = "";
		this._settings = {};
		this._wizard = null;
		this._progressBar = null;
	};
	BX.Crm.DedupeWizardStep.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._wizard = BX.prop.get(this._settings, "wizard");
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.prop.getObject(this._settings, "messages", {}), name, name);
		},
		getNextStepId: function()
		{
			return BX.prop.getString(this._settings, "nextStepId", "");
		},
		setWizard: function(wizard)
		{
			this._wizard = wizard;
		},
		getWizard: function()
		{
			return this._wizard;
		},
		getWrapper: function()
		{
			return BX(BX.prop.getString(this._settings, "wrapperId"));
		},
		getTitleWrapper: function()
		{
			return BX(BX.prop.getString(this._settings, "titleWrapperId"));
		},
		getSubtitleWrapper: function()
		{
			return BX(BX.prop.getString(this._settings, "subtitleWrapperId"));
		},
		prepareProgressBar: function()
		{
			if(!this._progressBar)
			{
				this._progressBar = new BX.UI.ProgressBar(
					{
						value: 0,
						maxValue: 100,
						color: BX.UI.ProgressBar.Color.SUCCESS,
						statusType: BX.UI.ProgressBar.Status.PERCENT,
						column: true
					}
				);
			}
			this._progressBar.update(0);
			BX(
				BX.prop.getString(this._settings, "progressBarWrapperId")
			).appendChild(this._progressBar.getContainer());
		},
		setProgressBarValue: function(value)
		{
			this._progressBar.update(value);
		},
		start: function()
		{
			this.getWrapper().style.display = "";
			window.setTimeout(
				function(){ this._wizard.onStepStart(this); }.bind(this),
				0
			);
		},
		end: function()
		{
			this.getWrapper().style.display = "none";
			window.setTimeout(
				function () { this._wizard.onStepEnd(this); }.bind(this),
				0
			);
		}
	};

	BX.Crm.DedupeWizardStep.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardStep();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardScanning) === "undefined")
{
	BX.Crm.DedupeWizardScanning = function ()
	{
		BX.Crm.DedupeWizardScanning.superclass.constructor.apply(this);

		this._indexRebuildContext = "";
		this._configHandler = BX.delegate(this.onConfigButtonClick, this);
		this._scanStartHandler = BX.delegate(this.onScanStartButtonClick, this);
		this._isScanRunning = false;

		BX.addCustomEvent(window, 'SidePanel.Slider:onMessage', this.onSliderMessage.bind(this));
	};
	BX.extend(BX.Crm.DedupeWizardScanning, BX.Crm.DedupeWizardStep);

	/*
	BX.Crm.DedupeWizardScanning.prototype.initialize = function(id, settings)
	{
		BX.Crm.DedupeWizardScanning.superclass.initialize.apply(this, arguments);
	};
	*/
	BX.Crm.DedupeWizardScanning.prototype.start = function()
	{
		BX.Crm.DedupeWizardScanning.superclass.start.apply(this, arguments);
		this.layout();
	};
	BX.Crm.DedupeWizardScanning.prototype.layout = function()
	{
		this.adjustConfigurationTitle();

		var buttonBox = document.body.querySelector('.crm-dedupe-wizard-start-control-box');
		var button = BX(BX.prop.getString(this._settings, "buttonId"));
		if(button)
		{
			buttonBox.classList.add('crm-dedupe-wizard-start-control-box-default-state');
			BX.bind(button, "click", this._scanStartHandler);
		}

		var configButton = BX(BX.prop.getString(this._settings, "configEditButtonId"));
		if(configButton)
		{
			BX.bind(configButton, "click", this._configHandler);
		}

		BX.addCustomEvent(this._wizard, "onConfigChange", BX.delegate(this.onConfigChange, this));
	};
	BX.Crm.DedupeWizardScanning.prototype.adjustConfigurationTitle = function()
	{
		var titleElement = BX(BX.prop.getString(this._settings, "configTitleId"));
		var textTitleElement = BX(BX.prop.getString(this._settings, "configTitleTextId"));
		if(!titleElement && !textTitleElement)
		{
			return;
		}

		var typeInfos = this._wizard.getTypeInfos();
		var config = this._wizard.getConfig();
		var selectedTypeNames = BX.prop.getArray(config, "typeNames", []);
		var currentScope = BX.prop.getString(config, "scope", "");

		var descriptions = [];

		for(var key in typeInfos)
		{
			if(!typeInfos.hasOwnProperty(key))
			{
				continue;
			}

			var typeInfo = typeInfos[key];
			if(currentScope === BX.prop.getString(typeInfo, "SCOPE")
				&& selectedTypeNames.indexOf(BX.prop.getString(typeInfo, "NAME")) >= 0
			)
			{
				descriptions.push(BX.prop.getString(typeInfo, "DESCRIPTION"));
			}
		}

		if (titleElement)
		{
			titleElement.innerHTML = BX.util.htmlspecialchars(descriptions.join(", "));

			BX.bind(titleElement, "click", this._configHandler);
		}
		if (textTitleElement)
		{
			textTitleElement.textContent = descriptions.join(", ");
		}
	};
	BX.Crm.DedupeWizardScanning.prototype.setIsScanRunning = function(isScanRunning)
	{
		this._isScanRunning = !!isScanRunning;

		var editModeContainer = BX(BX.prop.getString(this._settings, "configEditModeContainer"));
		var viewModeContainer = BX(BX.prop.getString(this._settings, "configViewModeContainer"));
		if (isScanRunning)
		{
			editModeContainer ? editModeContainer.style.display = 'none' : null;
			viewModeContainer ? viewModeContainer.style.display = '' : null;
		}
		else
		{
			editModeContainer ? editModeContainer.style.display = '' : null;
			viewModeContainer ? viewModeContainer.style.display = 'none' : null;
		}
	};
	BX.Crm.DedupeWizardScanning.prototype.onConfigChange = function()
	{
		this.adjustConfigurationTitle();
	};
	BX.Crm.DedupeWizardScanning.prototype.onConfigButtonClick = function(e)
	{
		e.preventDefault();

		if(this._isScanRunning)
		{
			return;
		}

		BX.SidePanel.Instance.open(
			this._wizard.getDedupeSettingsPath(),
			{
				allowChangeHistory: false,
				cacheable: false,
				width: 600,
				requestMethod: 'post',
				requestParams: {
					'entityTypeId': this._wizard.getEntityTypeId(),
					'guid': this._wizard.getId()
				}
			}
		);
	};
	BX.Crm.DedupeWizardScanning.prototype.onScanStartButtonClick = function(e)
	{
		this._indexRebuildContext = BX.util.getRandomString(8);
		if(this.rebuildIndex())
		{
			this.prepareProgressBar();

			BX(BX.prop.getString(this._settings, "buttonId")).style.display = "none";
			document.body.querySelector(".crm-dedupe-wizard-start-control-box").classList.remove("crm-dedupe-wizard-start-control-box-default-state");
			document.body.querySelector(".crm-dedupe-wizard-start-icon-scanning").classList.add("crm-dedupe-wizard-start-icon-refresh-repeat-animation");
		}
	};
	BX.Crm.DedupeWizardScanning.prototype.getNextStepId = function()
	{
		return this._wizard.getTotalItemCount() > 0 ? "merging" : "finish";
	};
	BX.Crm.DedupeWizardScanning.prototype.rebuildIndex = function()
	{
		var config = this._wizard.getConfig();
		var selectedTypeNames = BX.prop.getArray(config, "typeNames", []);
		var currentScope = BX.prop.getString(config, "scope", "");

		if(selectedTypeNames.length === 0)
		{
			BX.UI.Notification.Center.notify(
				{
					content: this.getMessage("emptyConfig"),
					position: "top-center",
					autoHideDelay: 5000
				}
			);
			return false;
		}

		this.setIsScanRunning(true);
		BX.ajax.runComponentAction("bitrix:crm.dedupe.wizard", "rebuildIndex", {
			data:
				{
					contextId: this._indexRebuildContext,
					entityTypeName: BX.CrmEntityType.resolveName(this._wizard.getEntityTypeId()),
					types: selectedTypeNames,
					scope: currentScope
				}
		}).then(
			function(response)
			{
				var data = response.data;
				var status = BX.prop.getString(data, "STATUS", "");

				var totalItems = BX.prop.getInteger(data, "TOTAL_ITEMS", 0);
				var processedItems = BX.prop.getInteger(data, "PROCESSED_ITEMS", 0);

				if(status === "PROGRESS")
				{
					window.setTimeout(
						function () { this.rebuildIndex(); }.bind(this),
						400
					);

					if(processedItems > 0 && totalItems > 0)
					{
						this.setProgressBarValue(100 * processedItems/totalItems);
					}
				}
				else if(status === "COMPLETED")
				{
					this.setProgressBarValue(100);

					this._wizard.setTotalItemCount(BX.prop.getInteger(data, "FOUND_ITEMS", 0));
					this._wizard.setTotalEntityCount(BX.prop.getInteger(data, "TOTAL_ENTITIES", 0));

					this.setIsScanRunning(false);
					this._wizard.enableCloseConfirmation(true);
					window.setTimeout(function(){ this.end(); }.bind(this),  200);
				}
			}.bind(this)
		).catch(
			function(){ this.setIsScanRunning(false); }.bind(this)
		);

		return true;
	};
	BX.Crm.DedupeWizardScanning.prototype.onSliderMessage = function(event)
	{
		if (event.getEventId() === 'crm::onMergerSettingsChange')
		{
			var data = event.getData();
			this._wizard.setConfig(data.config);
		}
	};
	BX.Crm.DedupeWizardScanning.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardScanning();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardMerging) === "undefined")
{
	BX.Crm.DedupeWizardMerging = function ()
	{
		BX.Crm.DedupeWizardMerging.superclass.constructor.apply(this);

		this._totalItemCount = 0;
		this._totalEntityCount = 0;

		this._currentItemIndex = 0;

		this._mergedItemCount = 0;
		this._conflictedItemCount = 0;

		this._automaticMergeStartHandler = BX.delegate(this.onAutomaticMergeStartButtonClick, this);
		this._manualMergeStartHandler = BX.delegate(this.onManualMergeStartButtonClick, this);
	};
	BX.extend(BX.Crm.DedupeWizardMerging, BX.Crm.DedupeWizardStep);
	/*
	BX.Crm.DedupeWizardMerging.prototype.initialize = function(id, settings)
	{
		BX.Crm.DedupeWizardMerging.superclass.initialize.apply(this, arguments);
	};
    */
	BX.Crm.DedupeWizardMerging.prototype.start = function()
	{
		this._totalItemCount = this._wizard.getTotalItemCount();
		this._totalEntityCount = this._wizard.getTotalEntityCount();
		this.layout();

		BX.Crm.DedupeWizardMerging.superclass.start.apply(this, arguments);
	};
	BX.Crm.DedupeWizardMerging.prototype.end = function()
	{
		BX.Crm.DedupeWizardMerging.superclass.end.apply(this, arguments);
	};
	BX.Crm.DedupeWizardMerging.prototype.layout = function()
	{
		this.getTitleWrapper().innerHTML = this.getMessage("duplicatesFound").replace("#COUNT#", this._totalEntityCount);
		this.getSubtitleWrapper().innerHTML = this.getMessage("matchesFound").replace("#COUNT#", this._totalItemCount);
		var buttonBox = BX(this.getId()).querySelector('.crm-dedupe-wizard-start-control-box');
		var autoMergeButton = BX(BX.prop.getString(this._settings, "buttonId"));
		var manualMergeButton = BX(BX.prop.getString(this._settings, "alternateButtonId"));
		var icon = document.body.querySelector('.crm-dedupe-wizard-start-icon-merging');

		if(autoMergeButton)
		{
			buttonBox.classList.add('crm-dedupe-wizard-start-control-box-default-state');
			BX.bind(autoMergeButton, "click", this._automaticMergeStartHandler);
			BX.bind(autoMergeButton, "click", function() {
				buttonBox.classList.remove('crm-dedupe-wizard-start-control-box-default-state');
				buttonBox.classList.remove('crm-dedupe-wizard-start-control-box-ready-to-merge-state');
				autoMergeButton.style.display = 'none';

				if(manualMergeButton)
					manualMergeButton.style.display = 'none';

				icon.classList.add('crm-dedupe-wizard-start-icon-refresh-repeat-animation');
			});
		}

		if(manualMergeButton)
		{
			BX.bind(manualMergeButton, "click", this._manualMergeStartHandler);
			BX.bind(manualMergeButton, "click", function() {
				buttonBox.classList.remove('crm-dedupe-wizard-start-control-box-default-state');
				buttonBox.classList.remove('crm-dedupe-wizard-start-control-box-ready-to-merge-state');
				manualMergeButton.style.display = 'none';

				if(autoMergeButton)
					autoMergeButton.style.display = 'none';

				icon.classList.add('crm-dedupe-wizard-start-icon-refresh-repeat-animation');
			});
		}

		var listButtonId = BX(BX.prop.getString(this._settings, "listButtonId"));
		if(listButtonId)
		{
			BX.bind(listButtonId, "click", this.onListButtonClick.bind(this));
		}
	};
	BX.Crm.DedupeWizardMerging.prototype.merge = function()
	{
		var config = this._wizard.getConfig();
		var selectedTypeNames = BX.prop.getArray(config, "typeNames", []);
		var currentScope = BX.prop.getString(config, "scope", "");

		BX.ajax.runComponentAction("bitrix:crm.dedupe.wizard", "merge", {
			data:
				{
					entityTypeName: BX.CrmEntityType.resolveName(this._wizard.getEntityTypeId()),
					types: selectedTypeNames,
					scope: currentScope,
					mode: this._wizard.getMergeMode()
				}
		}).then(
			function(response)
			{
				var data = BX.prop.getObject(response, "data", {});
				var status = BX.prop.getString(data, "STATUS", "");

				if(status === "SUCCESS")
				{
					this._mergedItemCount++;
				}
				else if(status === "CONFLICT")
				{
					this._conflictedItemCount++;
				}
				else if(status === "ERROR")
				{
					BX.UI.Notification.Center.notify(
						{
							content: BX.prop.getString(
								data,
								"MESSAGE",
								"Merge failed an error occurred during the merge operation."
							),
							position: "top-right",
							autoHideDelay: 5000
						}
					);
				}

				this._currentItemIndex++;
				if(status !== "COMPLETED")
				{
					window.setTimeout(
						function () { this.merge(); }.bind(this),
						400
					);

					this.setProgressBarValue(100 * this._currentItemIndex/this._totalItemCount);
				}
				else
				{
					this._wizard.setMergedItemCount(
						this._wizard.getTotalItemCount() - BX.prop.getInteger(data, "TOTAL_ITEMS", 0)
					);
					this._wizard.setMergedEntityCount(
						this._wizard.getTotalEntityCount() - BX.prop.getInteger(data, "TOTAL_ENTITIES", 0)
					);

					this._wizard.setConflictedItemCount(
						this._wizard.getTotalItemCount() - this._wizard.getMergedItemCount()
					);
					this._wizard.setConflictedEntityCount(
						this._wizard.getTotalEntityCount() - this._wizard.getMergedEntityCount()
					);

					this.setProgressBarValue(100);
					window.setTimeout(function(){ this.end(); }.bind(this),  200);
				}
			}.bind(this)
		);
	};
	BX.Crm.DedupeWizardMerging.prototype.onAutomaticMergeStartButtonClick = function(e)
	{
		this._currentItemIndex = 0;

		this._wizard.setMergeMode('auto');
		this.prepareProgressBar();
		this.merge();
	};
	BX.Crm.DedupeWizardMerging.prototype.onManualMergeStartButtonClick = function(e)
	{
		this._currentItemIndex = 0;

		this._wizard.setMergeMode('manual');
		this.prepareProgressBar();
		this.merge();
	};
	BX.Crm.DedupeWizardMerging.prototype.onListButtonClick = function(e)
	{
		this._wizard.openDedupeList();
		e.preventDefault();
	};
	BX.Crm.DedupeWizardMerging.prototype.getNextStepId = function()
	{
		if(this._wizard.getMergedItemCount() > 0)
		{
			return "mergingSummary";
		}
		return this._wizard.getConflictedItemCount() > 0 ? "conflictResolving" : "finish";
	};
	BX.Crm.DedupeWizardMerging.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardMerging();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardMergingSummary) === "undefined")
{
	BX.Crm.DedupeWizardMergingSummary = function()
	{
		BX.Crm.DedupeWizardMergingSummary.superclass.constructor.apply(this);

		this._buttonClickHandler = BX.delegate(this.onButtonClick, this);
	};
	BX.extend(BX.Crm.DedupeWizardMergingSummary, BX.Crm.DedupeWizardStep);
	BX.Crm.DedupeWizardMergingSummary.prototype.start = function()
	{
		this.layout();

		BX.Crm.DedupeWizardMergingSummary.superclass.start.apply(this, arguments);
	};
	BX.Crm.DedupeWizardMergingSummary.prototype.layout = function()
	{
		this.getTitleWrapper().innerHTML = this.getMessage("duplicatesProcessed").replace("#COUNT#", this._wizard.getMergedEntityCount());
		this.getSubtitleWrapper().innerHTML = this.getMessage("matchesProcessed").replace("#COUNT#", this._wizard.getMergedItemCount());

		var button = BX(BX.prop.getString(this._settings, "buttonId"));
		if(button)
		{
			BX.bind(button, "click", this._buttonClickHandler);
		}
	};
	BX.Crm.DedupeWizardMergingSummary.prototype.onButtonClick = function(e)
	{
		this.end();
	};
	BX.Crm.DedupeWizardMergingSummary.prototype.getNextStepId = function()
	{
		return this._wizard.getConflictedItemCount() > 0 ? "conflictResolving" : "finish";
	};
	BX.Crm.DedupeWizardMergingSummary.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardMergingSummary();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardConflictResolving) === "undefined")
{
	BX.Crm.DedupeWizardConflictResolving = function()
	{
		BX.Crm.DedupeWizardConflictResolving.superclass.constructor.apply(this);

		this._buttonClickHandler = BX.delegate(this.onButtonClick, this);
		this._externalEventHandler = null;
		this._contextId = "";
	};
	BX.extend(BX.Crm.DedupeWizardConflictResolving, BX.Crm.DedupeWizardStep);
	BX.Crm.DedupeWizardConflictResolving.prototype.start = function()
	{
		this.layout();

		BX.Crm.DedupeWizardMergingSummary.superclass.start.apply(this, arguments);
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.layout = function()
	{
		this.adjustTitle();
		var button = BX(BX.prop.getString(this._settings, "buttonId"));
		if(button)
		{
			BX.bind(button, "click", this._buttonClickHandler);
		}

		var listButtonId = BX(BX.prop.getString(this._settings, "listButtonId"));
		if(listButtonId)
		{
			BX.bind(listButtonId, "click", this.onListButtonClick.bind(this));
		}
		if (this._wizard.getMergeMode() == 'manual')
		{
			this._buttonClickHandler();
		}
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.adjustTitle = function()
	{
		this.getTitleWrapper().innerHTML = this.getMessage("duplicatesConflicted").replace("#COUNT#", this._wizard.getConflictedEntityCount());
		this.getSubtitleWrapper().innerHTML = this.getMessage("matchesConflicted").replace("#COUNT#", this._wizard.getConflictedItemCount());
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.onButtonClick = function(e)
	{
		this._contextId = this._wizard.getContextId() + "_" + BX.util.getRandomString(6).toUpperCase();

		this._wizard.openMerger(this._contextId);

		if(!this._externalEventHandler)
		{
			this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
		}
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.onListButtonClick = function(e)
	{
		this._wizard.openDedupeList();
		e.preventDefault();
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.onExternalEvent = function(params)
	{
		var eventName = BX.prop.getString(params, "key", "");

		if(eventName !== "onCrmEntityMergeComplete" && eventName !== "onCrmEntityMergeSkip")
		{
			return;
		}

		var value = BX.prop.getObject(params, "value", {});
		if(this._contextId !== BX.prop.getString(value, "context", ""))
		{
			return;
		}

		var entityTypeName = BX.prop.getString(value, "entityTypeName", "");
		if(entityTypeName !== this._wizard.getEntityTypeName())
		{
			return;
		}

		var currentConflictedItemCount = BX.prop.getInteger(value, "length", -1);
		if(currentConflictedItemCount >= 0)
		{
			var conflictedItemCount = this._wizard.getConflictedItemCount();
			if(conflictedItemCount >= currentConflictedItemCount)
			{
				this._wizard.setResolvedItemCount(conflictedItemCount - currentConflictedItemCount);
			}
			else
			{
				this._wizard.setResolvedItemCount(0);
				this._wizard.setConflictedItemCount(currentConflictedItemCount);
			}
		}

		var skipped = BX.prop.getInteger(value, "skipped", 0);
		if (skipped > 0)
		{
			var total = this._wizard.getTotalEntityCount();
			this._wizard.setTotalEntityCount(Math.max(total - skipped, 0));
		}

		if(this._wizard.getUnResolvedItemCount() === 0)
		{
			window.setTimeout(function(){ this.end(); }.bind(this),  0);
		}
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.getNextStepId = function()
	{
		return "finish";
	};
	BX.Crm.DedupeWizardConflictResolving.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardConflictResolving();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardMergingFinish) === "undefined")
{
	BX.Crm.DedupeWizardMergingFinish = function()
	{
		BX.Crm.DedupeWizardMergingFinish.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.DedupeWizardMergingFinish, BX.Crm.DedupeWizardStep);

	BX.Crm.DedupeWizardMergingFinish.prototype.start = function()
	{
		this._wizard.enableEntityListReload(true);
		this._wizard.enableCloseConfirmation(false);
		BX.Crm.DedupeWizardMergingFinish.superclass.start.apply(this, arguments);
		this.layout();
	};
	BX.Crm.DedupeWizardMergingFinish.prototype.layout = function()
	{
		var count = this._wizard.getTotalEntityCount();
		if (parseInt(count) > 0)
		{
			this.getSubtitleWrapper().innerHTML = this.getMessage("duplicatesComplete").replace("#COUNT#", count);
		}
		else
		{
			this.getSubtitleWrapper().innerHTML = this.getMessage("duplicatesCompleteEmpty");
		}

		var backToListLinkId = BX(BX.prop.getString(this._settings, "backToListLinkId"));
		if(backToListLinkId)
		{
			BX.bind(backToListLinkId, "click", this.onListButtonClick.bind(this));
		}
	};
	BX.Crm.DedupeWizardMergingFinish.prototype.onListButtonClick = function(e)
	{
		var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
		if(slider && slider.isOpen())
		{
			slider.close(false);
			e.preventDefault();
		}
	};
	BX.Crm.DedupeWizardMergingFinish.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardMergingFinish();
		self.initialize(id, settings);
		return self;
	};
}

