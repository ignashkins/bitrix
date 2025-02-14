import type {BaseEvent} from 'main.core.events';
import {EventEmitter} from 'main.core.events';
import {Dom, Tag, Text, Type, Uri} from 'main.core';
import './style.css';

export class IblockProductList
{
	/**
	 * @type {?BX.Main.grid}
	 */
	grid;
	variations = new Map();
	variationsEditData = new Map();

	onSettingsWindowSaveHandler = this.handleOnSettingsWindowSave.bind(this);
	onChangeVariationHandler = this.handleOnChangeVariation.bind(this);
	onBeforeGridRequestHandler = this.handleOnBeforeGridRequest.bind(this);
	onFilterApplyHandler = this.handleOnFilterApply.bind(this);

	constructor(options = {})
	{
		this.gridId = options.gridId;
		this.rowIdMask = options.rowIdMask || '#ID#';
		this.variationFieldNames = options.variationFieldNames || [];
		this.productVariationMap = options.productVariationMap || {};
		this.createNewProductHref = options.createNewProductHref || '';
		this.showCatalogWithOffers = options.showCatalogWithOffers || false;

		this.addCustomClassToGrid();
		this.cacheSelectedVariation();

		EventEmitter.subscribe('BX.Grid.SettingsWindow:save', this.onSettingsWindowSaveHandler);
		EventEmitter.subscribe('SkuProperty::onChange', this.onChangeVariationHandler);
		EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeGridRequestHandler);
		EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApplyHandler);
	}

	addCustomClassToGrid()
	{
		Dom.addClass(this.getGrid().getContainer(), 'catalog-product-grid');
	}

	cacheSelectedVariation()
	{
		this.getGrid().getRows().getBodyChild().forEach((row) => {
			let rowId = row.getId();
			let productId = this.getProductIdByRowId(rowId);
			let variationId = this.getCurrentVariationIdByProduct(productId);

			if (variationId)
			{
				this.variations.set(variationId, row.getNode().cloneNode(true));
				this.variationsEditData.set(variationId, row.getEditData());
			}
		});
	}

	clearAllVariationCache()
	{
		this.variations.clear();
		this.variationsEditData.clear();
	}

	clearVariationCache(variationId)
	{
		this.variations.delete(variationId);
		this.variationsEditData.delete(variationId);
	}

	/**
	 * @returns {?BX.Main.grid}
	 */
	getGrid()
	{
		if (!this.grid)
		{
			this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
		}

		return this.grid;
	}

	handleOnSettingsWindowSave(event: BaseEvent)
	{
		const [settingsWindow] = event.getCompatData();
		const selectedColumns = settingsWindow.getSelectedColumns();
		this.showCatalogWithOffers = selectedColumns.indexOf('CATALOG_PRODUCT') !== -1;
	}

	handleOnChangeVariation(event: BaseEvent)
	{
		const [skuFields] = event.getData();
		const productId = Text.toNumber(skuFields.PARENT_PRODUCT_ID);
		const variationId = Text.toNumber(skuFields.ID);

		if (productId <= 0 || variationId <= 0)
		{
			return;
		}

		this.getVariation(productId, variationId)
			.then((variationNode) => {
				this.updateProductRow(productId, variationId, variationNode);
				this.productVariationMap[productId] = variationId;
			});
	}

	getVariation(productId, variationId)
	{
		if (this.variations.has(variationId))
		{
			return Promise.resolve(this.variations.get(variationId))
		}

		return new Promise((resolve) => {
			this.loadVariation(productId, variationId, resolve);
		})
			.then((variation) => {
				if (Type.isDomNode(variation))
				{
					this.variations.set(variationId, variation);
					return variation;
				}

				return null;
			});
	}

	loadVariation(productId, variationId, resolve)
	{
		const self = this;
		const url = '';
		const method = 'POST';
		const data = {productId, variationId};

		this.getProductRow(productId).stateLoad();
		this.getGrid().getData().request(url, method, data, 'changeVariation', function() {
			const row = self.getProductRow(productId);
			if (row)
			{
				row.stateUnload();
				resolve(this.getRowById(row.getId()));
			}
		});
	}

	getProductIdByRowId(rowId)
	{
		const mask = new RegExp(this.rowIdMask.replace('#ID#', '([0-9]+)'));
		const matches = rowId.match(mask);

		return Type.isArray(matches) ? Text.toNumber(matches[1]) : 0;
	}

	getRowIdByProductId(id)
	{
		return this.rowIdMask.replace('#ID#', id);
	}

	/**
	 * @param id
	 * @returns {?BX.Grid.Row}
	 */
	getProductRow(id)
	{
		const rowId = this.getRowIdByProductId(id);

		return this.getGrid().getRows().getById(rowId);
	}

	/**
	 * @returns {?BX.Grid.Row}
	 */
	getHeadRow()
	{
		return this.getGrid().getRows().getHeadFirstChild();
	}

	updateProductRow(productId, variationId, variationNode)
	{
		if (!productId || !Type.isDomNode(variationNode))
		{
			return;
		}

		const headRow = this.getHeadRow();
		const productRow = this.getProductRow(productId);
		const fields = {};

		[...variationNode.cells].forEach((cell, index) => {
			const cellName = headRow.getCellNameByCellIndex(index);

			if (this.variationFieldNames.includes(cellName))
			{
				let columnCell = productRow.getCellByIndex(index);
				if (columnCell)
				{
					const cellHtml = productRow.getContentContainer(cell).innerHTML;
					productRow.getContentContainer(columnCell).innerHTML = cellHtml;
					fields[cellName] = cellHtml;
				}
			}
		});

		if (this.variationsEditData.has(variationId))
		{
			productRow.setEditData(this.variationsEditData.get(variationId));
		}
		else
		{
			productRow.resetEditData();
			this.variationsEditData.set(variationId, productRow.getEditData());

		}

		if (productRow.isEdit())
		{
			productRow.editCancel();
			productRow.edit();
		}
	}

	handleOnBeforeGridRequest(event: BaseEvent)
	{
		const [, gridData] = event.getData();
		const submitData = BX.prop.get(gridData, 'data', {});

		// reload settings, columns or something else
		if (!submitData.productId && !submitData.FIELDS)
		{
			this.clearAllVariationCache();
		}

		if (submitData.FIELDS)
		{
			for (let rowId in submitData.FIELDS)
			{
				if (!submitData.FIELDS.hasOwnProperty(rowId))
				{
					continue;
				}

				const productId = this.getProductIdByRowId(rowId);
				const variationId = this.getCurrentVariationIdByProduct(productId);
				const newFilesRegExp = new RegExp(/([0-9A-Za-z_]+?(_n\d+)*)\[([A-Za-z_]+)\]/);
				const rowFields = submitData.FIELDS[rowId];
				const morePhotoValues = {};
				if (!Type.isNil(rowFields['MORE_PHOTO_custom']))
				{
					for (let key in rowFields['MORE_PHOTO_custom'])
					{
						if (!rowFields['MORE_PHOTO_custom'].hasOwnProperty(key))
						{
							continue;
						}

						const inputValue = rowFields['MORE_PHOTO_custom'][key];
						if (newFilesRegExp.test(inputValue.name))
						{
							let fileCounter, fileSetting;
							[, fileCounter, , fileSetting] = inputValue.name.match(newFilesRegExp);
							if (fileCounter && fileSetting)
							{
								morePhotoValues[fileCounter] = morePhotoValues[fileCounter] || {};
								morePhotoValues[fileCounter][fileSetting] = inputValue.value;
							}
						}
						else
						{
							morePhotoValues[inputValue.name] = inputValue.value;
						}
					}
				}
				rowFields['MORE_PHOTO'] = morePhotoValues;
				if (variationId && this.showCatalogWithOffers)
				{
					const variationRowId = this.getRowIdByProductId(variationId);
					// clear old cache
					this.clearVariationCache(variationId);

					submitData.FIELDS[variationRowId] = {};

					for (let fieldName of this.variationFieldNames)
					{
						if (!rowFields.hasOwnProperty(fieldName))
						{
							continue;
						}

						submitData.FIELDS[variationRowId][fieldName] = rowFields[fieldName];
						delete submitData.FIELDS[rowId][fieldName];
					}
				}
			}
		}
	}

	getCurrentVariationIdByProduct(productId)
	{
		return productId in this.productVariationMap ? Text.toNumber(this.productVariationMap[productId]) : null;
	}

	handleOnFilterApply(event: BaseEvent)
	{
		const data = event.getData();
		const filterGridId = data[0];
		const filter = data[2] instanceof BX.Main.Filter ? event.getData()[2] : null;

		if (filter && (filterGridId === this.gridId))
		{
			const filterFields = this.getFilterFields(filter);

			let sectionId = '0';

			if (Type.isArray(filterFields))
			{
				const fieldSectionId = this.getFieldSectionId(filterFields);

				if (fieldSectionId)
				{
					const value = fieldSectionId['VALUE'];

					if (Type.isObject(value))
					{
						sectionId = value['VALUE'];
					}
				}
			}

			this.setNewProductButtonHrefSectionId(sectionId);
		}
	}

	getFilterFields(filter: BX.Main.Filter)
	{
		const presets = filter.getParam('PRESETS');

		let tmpFilterPreset = null;

		if (Type.isArray(presets))
		{
			tmpFilterPreset = presets.find((preset) => {
				return preset['ID'] === 'tmp_filter';
			});
		}

		if (tmpFilterPreset)
		{
			return tmpFilterPreset['FIELDS'] || null;
		}

		return null;
	}

	getFieldSectionId(fields: Array)
	{
		return fields.find((field) => {
			return field['ID'] === 'field_SECTION_ID'
		});
	}

	setNewProductButtonHrefSectionId(sectionId: String)
	{
		const uri = new Uri(this.createNewProductHref);
		uri.setQueryParams({
			IBLOCK_SECTION_ID: sectionId,
		});
		const button = document.getElementById('create_new_product_button_' + this.gridId);

		if (Type.isDomNode(button))
		{
			button.href = uri.getPath() + uri.getQuery();
		}
	}
}