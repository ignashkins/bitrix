import {BitrixVue} from "ui.vue";
import "./timeline.css";

export const Timeline = BitrixVue.localComponent('bx-timeman-component-monitor-monitor-timeline-timeline',{
	props: ['items', 'fullTime'],
	computed:
	{
		lineItems()
		{
			return this.items.map(item => {
				return {
					time: item.time,
					width: Math.round(item.time * 100 / this.fullTime),
					primaryColor: item.primaryColor
				}
			});
		}
	},
	// language=Vue
	template: `
		<div class="bx-monitor-timeline">
			<div class="bx-monitor-timeline-line-wrap">
				<div class="bx-monitor-timeline-line-container">
					<div
						class="bx-monitor-timeline-line-item"
						v-for="item of lineItems"
						:key="item.id"
						:style="{ 
						  width: item.time > 0 ? (item.width > 2 ? item.width + '%' : '16px') : 0,
						  background: item.primaryColor 
						}"
					/>
				</div>		  
			</div>
		</div>
	`
});