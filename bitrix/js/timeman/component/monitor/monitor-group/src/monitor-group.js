import {Vue} from "ui.vue";
import {Vuex} from 'ui.vuex';
import {Time} from "timeman.mixin";
import {EntityType, EntityGroup} from "timeman.const";
import {GroupItem} from './group-item/group-item';
import {MountingPortal} from 'ui.vue.portal';
import {PopupManager} from 'main.popup';
import {MessageBox} from 'ui.dialogs.messagebox';

import "ui.icons";

import "./monitor-group.css";

Vue.component('bx-timeman-component-monitor-group', {
	components:
	{
		GroupItem,
		MountingPortal
	},
	mixins: [Time],
	props: ['group', 'readOnly'],
	data: function()
	{
		return {
			refreshCount: 0,
			popupInstance: null,
			popupIdSelector: !!this.readOnly ? '#bx-timeman-pwt-popup-preview' :  '#bx-timeman-pwt-popup-editor',
			popupContent: {
				privateCode: '',
				title: '',
				time: '',
				comment: '',
				detail: '',
				type: '',
				onSaveComment: '',
			},
			comment: '',
			isCommentPopup: false,
			isDetailPopup: false,
		};
	},
	created()
	{
		setInterval(() => this.refreshCount++, 60000);
	},
	computed:
	{
		...Vuex.mapGetters('monitor',[
			'getWorkingEntities',
			'getPersonalEntities',
		]),
		...Vuex.mapState({
			monitor: state => state.monitor,
		}),
		EntityType: () => EntityType,
		EntityGroup: () => EntityGroup,
		displayedGroup()
		{
			if (this.EntityGroup.getValues().includes(this.group))
			{
				return this.EntityGroup[this.group];
			}
		},
		items()
		{
			this.refreshCount;

			switch(this.displayedGroup.value)
			{
				case EntityGroup.working.value:
					return this.getWorkingEntities;

				case EntityGroup.personal.value:
					return this.getPersonalEntities;
			}
		},
		time()
		{
			this.refreshCount;

			switch(this.displayedGroup.value)
			{
				case EntityGroup.working.value:
					return this.workTime;

				case EntityGroup.personal.value:
					return this.personalTime;
			}
		},
	},
	methods:
	{
		onCommentClick(event)
		{
			this.isCommentPopup = true;
			this.popupContent.privateCode = event.content.privateCode;
			this.popupContent.title = event.content.title;
			this.popupContent.time = event.content.time;
			this.popupContent.type = event.content.type;
			this.popupContent.onSaveComment = event.onSaveComment;
			this.comment = event.content.comment;

			if (this.popupInstance !== null)
			{
				this.popupInstance.destroy();
				this.popupInstance = null;
			}

			const popup = PopupManager.create({
				id: "bx-timeman-pwt-external-data",
				targetContainer: document.body,
				autoHide: true,
				closeByEsc: true,
				bindOptions: {position: "top"},
				events: {
					onPopupDestroy: () =>
					{
						this.isCommentPopup = false;
						this.popupInstance = null
					}
				},
			});

			//little hack for correct open several popups in a row.
			this.$nextTick(() => this.popupInstance = popup);
		},
		onDetailClick(event: BaseEvent)
		{
			this.isDetailPopup = true;
			this.popupContent.privateCode = event.content.privateCode;
			this.popupContent.title = event.content.title;
			this.popupContent.time = event.content.time;
			this.popupContent.detail = event.content.detail;

			if (this.popupInstance !== null)
			{
				this.popupInstance.destroy();
				this.popupInstance = null;
			}

			const popup = PopupManager.create({
				id: "bx-timeman-pwt-external-data",
				targetContainer: document.body,
				autoHide: true,
				closeByEsc: true,
				bindOptions: {position: "top"},
				events: {
					onPopupDestroy: () =>
					{
						this.isDetailPopup = false;
						this.popupInstance = null
					}
				},
			});

			//little hack for correct open several popups in a row.
			this.$nextTick(() => this.popupInstance = popup);
		},
		saveComment(privateCode)
		{
			if (this.comment.trim() === '' && this.popupContent.type === EntityType.absence)
			{
				return;
			}

			this.$store.dispatch('monitor/setComment', {
				privateCode,
				comment: this.comment
			});

			if (typeof this.popupContent.onSaveComment === 'function')
			{
				this.popupContent.onSaveComment();
			}

			this.popupInstance.destroy();
		}
	},
	// language=Vue
	template: `		  
		<div class="bx-monitor-group-wrap">			
			<div class="bx-monitor-group">				  
				<div class="bx-monitor-group-header" v-bind:style="{ background: displayedGroup.secondaryColor }">
					<div class="bx-monitor-group-title-wrap">
						<div class="bx-monitor-group-title">
							{{ displayedGroup.title }}
                        </div>
                      	<div class="bx-monitor-group-title-wrap">
							<div class="bx-monitor-group-subtitle">
                              {{ formatSeconds(time) }}
							</div>
						</div>
					</div>
					<div v-if="!readOnly" class="bx-monitor-group-subtitle-wrap">
						<div class="bx-monitor-group-hint">
							{{ displayedGroup.hint }}
						</div>
					</div>
				</div>
				<div class="bx-monitor-group-content" v-bind:style="{ background: displayedGroup.lightColor }">
					<transition-group name="bx-monitor-group-item" class="bx-monitor-group-content-wrap">						  
					  
                      <GroupItem
						v-for="item of items"
                        :key="item.privateCode ? item.privateCode : item.title"
						:group="displayedGroup.value"
						:privateCode="item.privateCode"
					  	:type="item.type"
						:title="item.title"
                        :comment="item.comment"
						:time="formatSeconds(item.time)"
						:allowedTime="item.allowedTime ? formatSeconds(item.allowedTime) : null"
						:readOnly="!!readOnly"
						:hint="item.hint !== '' ? item.hint : null"
                        @commentClick="onCommentClick"
                        @detailClick="onDetailClick"
					  />
                      						  
					</transition-group>
				</div>
			</div>

            <mounting-portal :mount-to="popupIdSelector" append v-if="popupInstance">
            	<div class="bx-timeman-monitor-popup-wrap">					
					<div class="popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height" style="padding: 0">
						<div class="bx-timeman-monitor-popup-title popup-window-titlebar">
					  		<span class="popup-window-titlebar-text">
								{{ popupContent.title }}
					 		</span>
							<span class="popup-window-titlebar-text">
								{{ popupContent.time }}
							</span>
						</div>
						<div class="popup-window-content" style="overflow: auto; background: transparent;">
							<textarea 
								class="bx-timeman-monitor-popup-input"
								id="bx-timeman-monitor-popup-input-comment"
								v-if="isCommentPopup"
								:placeholder="$Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_ITEM_COMMENT')"
								v-model="comment"
                                @keydown.enter.prevent="saveComment(popupContent.privateCode)"																		
                            />
							<div v-if="isDetailPopup" class="bx-timeman-monitor-popup-items-container">
							  	<div 
									v-for="detailItem in popupContent.detail" 
									class="bx-timeman-monitor-popup-item"
							  	>
									<div class="bx-timeman-monitor-popup-content">
										<div class="bx-timeman-monitor-popup-content-title">
                                        	{{ detailItem.siteTitle }}
                                      	</div>
                                      	<div class="bx-timeman-monitor-popup-content-title">
                                        	<a target="_blank" :href="detailItem.siteUrl" class="bx-timeman-monitor-popup-content-title">
                                        		{{ detailItem.siteUrl }}
                                        	</a>
                                      	</div>
                                    </div>
                                    <div class="bx-timeman-monitor-popup-time">
                                    	{{ formatSeconds(detailItem.time) }}
                                    </div>
								</div>								  
							</div>
						</div>
						<div class="popup-window-buttons">
							<button 
								v-if="isCommentPopup" 
								@click="saveComment(popupContent.privateCode)" 
								class="ui-btn ui-btn-md ui-btn-primary"
								:class="{'ui-btn-disabled': (comment.trim() === '' && popupContent.type === EntityType.absence)}"
							>
								<span class="ui-btn-text">
									{{ $Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_BUTTON_OK') }}
								</span>
							</button>
						  	<button @click="popupInstance.destroy()" class="ui-btn ui-btn-md ui-btn-light">
								<span v-if="isCommentPopup" class="ui-btn-text">
									{{ $Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_BUTTON_CANCEL') }}
								</span>
                              	<span v-if="isDetailPopup" class="ui-btn-text">
									{{ $Bitrix.Loc.getMessage('TIMEMAN_MONITOR_GROUP_BUTTON_CLOSE') }}
								</span>
							</button>
						</div>
					</div>					
				</div>
            </mounting-portal>
		</div>
	`
});