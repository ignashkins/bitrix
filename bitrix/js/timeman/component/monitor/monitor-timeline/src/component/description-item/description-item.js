import {BitrixVue} from "ui.vue";
import "./description-item.css";

export const DescriptionItem = BitrixVue.localComponent('bx-timeman-component-monitor-monitor-timeline-description-item',{
	props: ['title', 'value', 'dot'],
	// language=Vue
	template: `
		<div class="bx-monitor-timeline-description-item">
			<div class="bx-monitor-timeline-description-title">
			  <div
				  class="bx-monitor-timeline-description-dot"
				  v-if="dot"
				  :style="{ background: dot.color }"
			  />
			  {{ title }}: {{ value }}
			</div>
		</div>
	`
});