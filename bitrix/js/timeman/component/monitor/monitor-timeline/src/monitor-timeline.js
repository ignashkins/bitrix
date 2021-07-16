import {Vue} from "ui.vue";
import {Vuex} from 'ui.vuex';
import {Time} from "timeman.mixin";
import {EntityGroup} from "timeman.const";
import {DescriptionItem} from "./component/description-item/description-item";
import {Timeline} from "./component/timeline/timeline";

import "./monitor-timeline.css";

Vue.component('bx-timeman-component-monitor-timeline', {
	components:
	{
		DescriptionItem,
		Timeline
	},
	mixins: [Time],
	computed:
	{
		...Vuex.mapState({
			monitor: state => state.monitor,
		}),
		EntityGroup: () => EntityGroup,
		timelineItems()
		{
			return [
				{
					id: 1,
					time: this.workTime,
					primaryColor: EntityGroup.working.primaryColor
				},
				{
					id: 2,
					time: this.personalTime,
					primaryColor: EntityGroup.personal.primaryColor
				},
			]
		}
	},
	// language=Vue
	template: `
		<div class="bx-monitor-timeline-wrap">
			<div class="bx-monitor-container">
			  	<div class="bx-monitor-timeline-content">
					<Timeline
						:items="timelineItems"
						:fullTime="fullTime"
					/>
				</div>
			  	
				<div class="bx-monitor-timeline-description">
					<div class="bx-monitor-timeline-description-container">  
						<DescriptionItem
							:title="$Bitrix.Loc.getMessage('TIMEMAN_MONITOR_TIMELINE_WORK_TIME')"
							:value="formatSeconds(workTime)"
							:dot="{color: EntityGroup.working.primaryColor}"
						/>
						<DescriptionItem
							:title="$Bitrix.Loc.getMessage('TIMEMAN_MONITOR_TIMELINE_PERSONAL_TIME')"
							:value="formatSeconds(personalTime)"
							:dot="{color: EntityGroup.personal.primaryColor}"
						/>
					</div>
				</div>
			</div>
		</div>
	`
});