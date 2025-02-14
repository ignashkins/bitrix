import {View} from './view/view';
import {Plan} from './view/plan';
import {ActiveSprint} from './view/active.sprint';
import {CompletedSprint} from './view/completed.sprint';

type Params = {
	viewName: string
}

export class Entry
{
	constructor(params: Params)
	{
		this.setParams(params);

		this.buildView(params);
	}

	setParams(params: Params)
	{
		this.setViewName(params.viewName);
	}

	setViewName(viewName: string)
	{
		const availableViews = new Set([
			'plan',
			'activeSprint',
			'completedSprint'
		]);

		if (!availableViews.has(viewName))
		{
			throw Error('Invalid value to activeView parameter');
		}

		this.viewName = viewName;
	}

	getViewName(): string
	{
		return this.viewName;
	}

	setView(view: View)
	{
		if (view instanceof View)
		{
			this.view = view;
		}
		else
		{
			this.view = null;
		}
	}

	getView(): View
	{
		return this.view;
	}

	buildView(params: Params)
	{
		const viewName = this.getViewName();

		if (viewName === 'plan')
		{
			this.setView(new Plan(params));
		}
		else if (viewName === 'activeSprint')
		{
			this.setView(new ActiveSprint(params));
		}
		else if (viewName === 'completedSprint')
		{
			this.setView(new CompletedSprint(params));
		}
	}

	renderTo(container: HTMLElement)
	{
		const view = this.getView();
		if (view instanceof View)
		{
			this.getView().renderTo(container);
		}
	}

	renderTabsTo(container: HTMLElement)
	{
		const view = this.getView();
		if (view instanceof View)
		{
			this.getView().renderTabsTo(container);
		}
	}

	renderCountersTo(container: HTMLElement)
	{
		const view = this.getView();
		if (view instanceof View)
		{
			this.getView().renderCountersTo(container);
		}
	}

	renderSprintStatsTo(container: HTMLElement)
	{
		const view = this.getView();
		if (view instanceof View)
		{
			this.getView().renderSprintStatsTo(container);
		}
	}

	renderButtonsTo(container: HTMLElement)
	{
		const view = this.getView();
		if (view instanceof View)
		{
			this.getView().renderButtonsTo(container);
		}
	}

	openEpicEditForm(epicId: number)
	{
		const view = this.getView();
		if (view instanceof Plan)
		{
			view.openEpicEditForm(epicId);
		}
	}

	openEpicViewForm(epicId: number)
	{
		const view = this.getView();
		if (view instanceof Plan)
		{
			view.openEpicViewForm(epicId);
		}
	}

	removeEpic(epicId: number)
	{
		const view = this.getView();
		if (view instanceof Plan)
		{
			view.removeEpic(epicId);
		}
	}
}