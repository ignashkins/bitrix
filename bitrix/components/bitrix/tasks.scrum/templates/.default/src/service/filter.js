import {Dom} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {RequestSender} from '../utility/request.sender';

type ListTypeItem = {
	NAME: string,
	VALUE: string
}

type ValueTypeField = {
	name: string,
	value: string
}

type Params = {
	filterId: string,
	requestSender?: RequestSender
}

export class Filter extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Filter');

		this.filterId = params.filterId;
		this.requestSender = params.requestSender;

		this.searchFieldApplied = false;

		this.initUiFilterManager();
		this.bindHandlers();
	}

	initUiFilterManager()
	{
		/* eslint-disable */
		this.filterManager = BX.Main.filterManager.getById(this.filterId);
		/* eslint-enable */
	}

	bindHandlers()
	{
		EventEmitter.subscribe('BX.Main.Filter:apply', this.onApplyFilter.bind(this));
	}

	isSearchFieldApplied(): boolean
	{
		return this.searchFieldApplied;
	}

	getSearchContainer(): HTMLElement
	{
		return this.filterManager.getSearch().getContainer();
	}

	scrollToSearchContainer()
	{
		const filterSearchContainer = this.getSearchContainer();
		if (!this.isNodeInViewport(filterSearchContainer))
		{
			filterSearchContainer.scrollIntoView(true);
		}
	}

	onApplyFilter(event: BaseEvent)
	{
		const [filterId, values, filterInstance, promise, params] = event.getCompatData();

		if (filterInstance.getSearch().getSearchString())
		{
			this.searchFieldApplied = true;
		}
		else
		{
			this.searchFieldApplied = false;
		}

		if (this.filterId !== filterId)
		{
			return;
		}

		params.autoResolve = false;

		this.emit('applyFilter', {
			promise: promise
		});
	}

	addItemToListTypeField(name: string, item: ListTypeItem)
	{
		//todo set item to list after epic crud actions

		const fieldInstances = this.filterManager.getField(name);
		const fieldOptions = this.filterManager.getFieldByName(name);

		if (!fieldInstances || !fieldOptions)
		{
			return;
		}

		fieldInstances.options.ITEMS.push(item);
		fieldOptions.ITEMS.push(item);

		const itemsNode = fieldInstances.node.querySelector('[data-name='+name+']');

		const items = Dom.attr(itemsNode, 'data-items');
		items.push(item);
		Dom.attr(itemsNode, 'data-items', items);
	}

	setValueToField(value: ValueTypeField)
	{
		const filterApi = this.filterManager.getApi();
		const filterFieldsValues = this.filterManager.getFilterFieldsValues();

		filterFieldsValues[value.name] = value.value;

		filterApi.setFields(filterFieldsValues);
		filterApi.apply();
	}

	setValuesToField(values: ValueTypeField[], resetFields = false)
	{
		const filterApi = this.filterManager.getApi();

		let filterFieldsValues = {};
		if (!resetFields)
		{
			filterFieldsValues = this.filterManager.getFilterFieldsValues();
		}

		values.forEach((value) => {
			filterFieldsValues[value.name] = value.value;
		});

		filterApi.setFields(filterFieldsValues);
		filterApi.apply();
	}

	getValueFromField(value: ValueTypeField): string
	{
		const filterFieldsValues = this.filterManager.getFilterFieldsValues();
		return filterFieldsValues[value.name];
	}

	resetFilter()
	{
		this.filterManager.resetFilter();
	}

	applyFilter()
	{
		this.filterManager.applyFilter();
	}

	isNodeInViewport(element: HTMLElement): boolean
	{
		const rect = element.getBoundingClientRect();
		return (
			rect.top >= 0 &&
			rect.left >= 0 &&
			rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
			rect.right <= (window.innerWidth || document.documentElement.clientWidth)
		);
	}
}