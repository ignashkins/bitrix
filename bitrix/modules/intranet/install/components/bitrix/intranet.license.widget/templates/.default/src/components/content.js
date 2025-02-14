import {Vue} from 'ui.vue';
import {Popup} from "main.popup";

export const ContentComponent = {
	props: [
		"license",
		"market",
		"telephony",
		"isAdmin",
		"isCloud",
		"partner",
	],
	computed: {
		localize(state)
		{
			return Vue.getFilteredPhrases('INTRANET_LICENSE_WIDGET_');
		},
		getTariffIconCLass()
		{
			if (this.license.isAlmostExpired)
			{
				return 'license-widget-item-icon--low';
			}
			else if (this.license.isExpired)
			{
				return 'license-widget-item-icon--expired';
			}

			return 'license-widget-item-icon--start';
		},
		getMarketClass()
		{
			if (this.market.isExpired || this.market.isAlmostExpired)
			{
				return 'license-widget-item--expired';
			}
			else if (this.market.isPaid || this.market.isDemo)
			{
				return 'license-widget-item--active';
			}

			return '';
		},
		getMarketIconClass()
		{
			if (this.market.isExpired)
			{
				return 'license-widget-item-icon--expired';
			}
			else if (this.market.isAlmostExpired)
			{
				return 'license-widget-item-icon--low';
			}
			else
			{
				return 'license-widget-item-icon--mp';
			}
		},
	},
	methods: {
		sendAnalytics(code)
		{
			BX.ajax.runAction("intranet.license.analyticsLabel", {
				data: {},
				analyticsLabel: {
					helperCode: code,
					headerPopup: "Y"
				}
			}).then((response) => {}, (response) => {});
		},
		showInfoHelper(type)
		{
			let articleCode = "";

			if (type === "market")
			{
				articleCode = "limit_benefit_market";
			}
			else if (type === "whyPay")
			{
				articleCode = "limit_why_pay_tariff";
			}

			BX.UI.InfoHelper.show(articleCode);
			this.sendAnalytics(articleCode);
		},
		showHelper()
		{
			if (this.license.isFreeTariff)
			{
				const article = this.isCloud ? "limit_support_bitrix" : "limit_support_bitrix_box";
				BX.UI.InfoHelper.show(article);
			}
			else
			{
				const article = this.isCloud ? "12925062" : "12983582";
				BX.Helper.show(`redirect=detail&code=${article}`);
			}
		},
		showPartner()
		{
			if (this.partner.isPartnerOrder)
			{
				const params = {id: this.partner.orderPartnerJs.id, sec: this.partner.orderPartnerJs.sec};

				BX.PopupWindowManager.create("B24PartnerOrderForm", null, {
					autoHide: true,
					zIndex: 0,
					offsetLeft: 0,
					offsetTop: 0,
					overlay: true,
					height: Math.min(document.documentElement.clientHeight - 100, 740),
					width: 560,
					draggable: {restrict:true},
					closeByEsc: true,
					contentColor: "white",
					contentNoPaddings: true,
					content:
						'<script data-b24-form="inline/'+params.id+'/'+params.sec+'" data-skip-moving="true">'+
						'(function(w,d,u){'+
						'var s=d.createElement("script");s.async=true;s.src=u+"?"+(Date.now()/180000|0);'+
						'var h=d.getElementsByTagName("script")[0];h.parentNode.insertBefore(s,h);'+
						'})(window,document,"https://cp.bitrix.ru/upload/crm/form/loader_${params.id}_${params.sec}.js");'+
						'</script>',
					events: {
						onPopupFirstShow: function()
						{
							(function(w,d,u){
								var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
								var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
							})(window,document,'https://cp.bitrix.ru/upload/crm/form/loader_'+params.id+'_'+params.sec+'.js')
						}
					}
				}).show();
			}
			else
			{
				showPartnerForm(this.partner.connectPartnerJs);
			}
		},
		showMarketDemoPopup(e)
		{
			BX.loadExt('marketplace').then(() => {
				BX.rest.Marketplace.openDemoSubscription(() => {
					window.location.href = '/settings/license.php?subscription_trial=Y&analyticsLabel[headerPopup]=Y'
				});
			});
		},
	},
	template: `
		<div class="license-widget">
			<div 
				class="license-widget-item license-widget-item--main"
				:class="{ 'license-widget-item--expired' : license.isExpired || license.isAlmostExpired }"
			>
				<div class="license-widget-inner">
					<div 
						class="license-widget-item-icon"
						:class="getTariffIconCLass"
					></div>
					<div class="license-widget-item-content">
						<div class="license-widget-item-name">
							<span>{{ license.name }}</span>
							<!--<span data-hint="Hint"></span>-->
						</div>
						<div class="license-widget-item-link">
							<span 
								v-if="license.isFreeTariff"
								key="licenseDesc"
								class="license-widget-item-link-text"
								@click="showInfoHelper('whyPay')"
							> 
								{{ localize.INTRANET_LICENSE_WIDGET_DESCRIPTION_WHY }} 
							</span>
							<a 
								v-else-if="license.isDemo"
								key="licenseDesc"
								:href="license.demoPath"
								class="license-widget-item-link-text"
								target="_blank"
							> 
								{{ localize.INTRANET_LICENSE_WIDGET_DESCRIPTION_TARIFF }} 
							</a>
							<a 
								v-else
								key="licenseDesc"
								:href="license.myPath"
								class="license-widget-item-link-text"
								target="_blank"
							> 
								{{ localize.INTRANET_LICENSE_WIDGET_DESCRIPTION_TARIFF }} 
							</a>
						</div>
						<div 
							v-if="license.isExpired || license.isAlmostExpired" 
							class="license-widget-item-info license-widget-item-info--exp"
						>
							<span class="license-widget-item-info-text">
								{{ license.daysLeftMessage }}
							</span>
						</div>
						<div v-if="!license.isExpired && !license.isAlmostExpired 
									&& !license.isFreeTariff && !license.isUnlimitedDateTariff" 
							class="license-widget-item-info"
						>
							<span class="license-widget-item-info-text">{{ license.tillMessage }}</span>
						</div>
					</div>
				</div>
				<span 
					v-if="license.isAutoPay" 
					key="licenseButton"
					class="license-widget-item-btn license-widget-item-btn--disabled"
				> 
					{{
						license.isFreeTariff || license.isDemo
						? localize.INTRANET_LICENSE_WIDGET_BUY
						: localize.INTRANET_LICENSE_WIDGET_PROLONG
					}} 
				</span>
				<a 
					v-else
					key="licenseButton"
					:href="license.allPath" 
					target="_blank" 
					class="license-widget-item-btn"
				> 
					{{
						license.isFreeTariff || license.isDemo
						? localize.INTRANET_LICENSE_WIDGET_BUY
						: localize.INTRANET_LICENSE_WIDGET_PROLONG
					}} 
				</a>
			</div>
			
			<div class="license-widget-block">
				<div 
					class="license-widget-item"
					:class="{ 'license-widget-item--active' : telephony.isConnected }"	
				>
					<div class="license-widget-inner">
						<div class="license-widget-item-icon license-widget-item-icon--tel"></div>
						<div class="license-widget-item-content">
							<div class="license-widget-item-name">
								<span>{{ localize.INTRANET_LICENSE_WIDGET_TELEPHONY }}</span>
								<!--<span data-hint="Hint"></span>-->
							</div>
							<div v-if="telephony.isConnected" class="license-widget-item-info">
								<a 
									:href="telephony.buyPath" 
									class="license-widget-item-info-text"
								> 
									{{ localize.INTRANET_LICENSE_WIDGET_TELEPHONY_CONNECTED }}
								</a>
							</div>
							<div 
								v-if="telephony.isConnected" 
								class="license-widget-item-text"
								v-html="telephony.balanceFormatted"
							>
							</div>
							<!--<div class="license-widget-item-text"Low balance</div>
							<div class="license-widget-item-text">99 �</div>-->
							
							<a 
								v-if="!telephony.isConnected" 
								:href="telephony.buyPath" 
								class="license-widget-item-btn"
								target="_blank"
							> 
								{{ localize.INTRANET_LICENSE_WIDGET_CONNECT }} 
							</a>
	
						</div>
					</div>
				</div>
	
				<div class="license-widget-item" :class="{ 'license-widget-item--active' : license.isDemo }">
					<div class="license-widget-inner">
						<div 
							class="license-widget-item-icon"
							:class="[
								license.isDemo && license.isDemoExpired 
								? 'license-widget-item-icon--expdemo' 
								: 'license-widget-item-icon--demo'
							]"
						></div>
						<div class="license-widget-item-content">
							<div class="license-widget-item-name">
								<span>{{ localize.INTRANET_LICENSE_WIDGET_DEMO }}</span>
								<!--<span data-hint="Hint"></span>-->
							</div>
							<div v-if="license.isDemo" class="license-widget-item-link">
								<a 
									class="license-widget-item-link-text" 
									:href="license.demoPath"
									target="_blank"
								>
									{{ localize.INTRANET_LICENSE_WIDGET_OPPORTUNITIES }}
								</a>
							</div>
							<div v-if="license.isDemo && !license.isDemoExpired" class="license-widget-item-info">
								<span class="license-widget-item-info-text">
									{{ license.tillMessage }}
								</span>
							</div>
							<div 
								v-if="license.isDemo && license.isDemoExpired" 
								class="license-widget-item-info license-widget-item-info--exp"
							>
								<span class="license-widget-item-info-text">
									{{ license.daysLeftMessage }}
								</span>
							</div>
							<div 
								v-if="!license.isDemo && !license.isDemoAvailable" 
								class="license-widget-item-text"
							>
									{{ localize.INTRANET_LICENSE_WIDGET_USED }}
							</div>
							<a 
								v-if="license.isDemoAvailable && !license.isDemo"
								:href="license.demoPath"
								class="license-widget-item-btn"
								target="_blank"
							> 
								{{ localize.INTRANET_LICENSE_WIDGET_TURN_ON }} 
							</a>
						</div>
					</div>
				</div>
			</div>

			<div 
				v-if="market.isMarketAvailable"
				class="license-widget-item license-widget-item--wide"
				:class="getMarketClass"
			>
				<div class="license-widget-inner">
					<div 
						class="license-widget-item-icon"
						:class="getMarketIconClass"
					></div>
					<div class="license-widget-item-content">
						<div class="license-widget-item-name">
							<span>{{ localize.INTRANET_LICENSE_WIDGET_MARKET }}</span>
							<!--<span data-hint="Hint"></span>-->
						</div>
						<div class="license-widget-item-link">
							<span class="license-widget-item-link-text" @click="showInfoHelper('market')">
								{{ localize.INTRANET_LICENSE_WIDGET_MARKET_DESCRIPTION }}
							</span>
						</div>
						
						<div 
							v-if="market.isExpired || market.isAlmostExpired" 
							key="marketTill"
							class="license-widget-item-info"
						>
							<span class="license-widget-item-info-text">
								{{ market.daysLeftMessage }}
							</span>
						</div>
						<div 
							v-else-if="market.isPaid || market.isDemo" 
							key="marketTill"
							class="license-widget-item-info"
						>
							<span class="license-widget-item-info-text">
								{{ market.tillMessage }}
							</span>
						</div>
					</div>
				</div>
				<a 
					v-if="!market.isPaid && !market.isDemo && !market.isDemoUsed && this.isAdmin"
					key="marketButton"
					class="license-widget-item-btn"
					target="_blank"
					@click="showMarketDemoPopup($event)"
				> 
					{{ localize.INTRANET_LICENSE_WIDGET_TRY }} 
				</a>
				
				<a 
					v-else="market.isPaid || market.isDemo"
					key="marketButton"
					:href="market.buyPath"
					class="license-widget-item-btn"
					target="_blank"
				> 
					{{
						market.isPaid
						? localize.INTRANET_LICENSE_WIDGET_PROLONG
						: localize.INTRANET_LICENSE_WIDGET_BUY
					}} 
				</a>
			</div>		
			
			<div class="license-widget-option-list">
				<a 
					class="license-widget-option" 
					@click="showPartner"
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="#525C69" opacity=".5">
						<path fill-rule="evenodd" d="M8.033 14.294a5.26 5.26 0 002.283.008l3.072 3.07a9.214 9.214 0 01-8.42-.013zm5.481-2.942l3.716 3.715a10.027 10.027 0 01-2.162 2.163l-3.716-3.716a4.824 4.824 0 001.256-.907c.377-.378.68-.802.906-1.255zm-8.637.015c.226.447.526.867.9 1.24.373.374.793.674 1.24.9L3.303 17.22a10.022 10.022 0 01-2.14-2.14l3.714-3.713zm-3.866-6.37l3.07 3.069a5.26 5.26 0 00.008 2.285l-3.064 3.064a9.214 9.214 0 01-.014-8.419zm16.348-.028a9.214 9.214 0 01.014 8.418l-3.07-3.07a5.26 5.26 0 00-.007-2.285zM3.316 1.154l3.716 3.715a4.826 4.826 0 00-1.256.907c-.378.378-.68.803-.906 1.256L1.154 3.316a10.032 10.032 0 012.162-2.162zm11.765.01a10.038 10.038 0 012.14 2.139l-3.715 3.714a4.826 4.826 0 00-.898-1.241 4.828 4.828 0 00-1.241-.899l3.714-3.714zm-1.666-.14l-3.062 3.063a5.26 5.26 0 00-2.288-.008L4.996 1.011a9.214 9.214 0 018.42.014z"/>
					</svg>
					<div class="license-widget-option-text">
						{{	
							partner.isPartnerOrder 
							? localize.INTRANET_LICENSE_WIDGET_PARTNER_ORDER
							: localize.INTRANET_LICENSE_WIDGET_PARTNER_CONNECT
						}} 
					</div>
				</a>
				<a class="license-widget-option" @click="showHelper">
					<svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" fill="#525C69" opacity=".5">
						<path fill-rule="evenodd" d="M16.996 4.652a1.6 1.6 0 011.593 1.455l.007.145v7.268a1.6 1.6 0 01-1.455 1.594l-.145.006H15.11l-.001 2.096a.3.3 0 01-.477.243l-.05-.048-1.963-2.292-4.046.001a1.6 1.6 0 01-1.593-1.454l-.007-.146v-1.382l6.43.001a2.1 2.1 0 002.096-1.95l.005-.15V4.652h1.492zM12.346 0a1.6 1.6 0 011.6 1.6v7.268a1.6 1.6 0 01-1.6 1.6l-5.373-.001-2.974 2.977a.3.3 0 01-.512-.212l-.001-2.765H1.6a1.6 1.6 0 01-1.6-1.6V1.6A1.6 1.6 0 011.6 0h10.747z"/>
					</svg>
					<div class="license-widget-option-text">{{ localize.INTRANET_LICENSE_WIDGET_SUPPORT }}</div>
				</a>
				<a class="license-widget-option" :href="license.ordersPath" target="_blank">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#525C69" opacity=".5">
						<path fill-rule="evenodd" d="M12.566 6.992a5.122 5.122 0 110 10.244 5.122 5.122 0 010-10.244zM9.383 0c.409 0 .798.179 1.064.49l2.251 2.626c.218.254.338.578.338.912v.911a7.365 7.365 0 00-2.005.176v-.843L9.126 2.006H2.006v11.03h3.413c.09.705.283 1.379.562 2.005H1.402A1.402 1.402 0 010 13.64V1.402C0 .628.628 0 1.402 0h7.98zm5.353 9.991l-2.75 2.75-1.147-1.147-.811.811 1.914 1.914.044.043 3.56-3.56-.81-.81zM6.67 8.022a7.12 7.12 0 00-.85 1.583h-1.81V8.023h2.66zm2.354-3.008l-.001.884c-.36.205-.7.439-1.019.7H4.011V5.014h5.014z"/>
					</svg>
					<div class="license-widget-option-text">{{ localize.INTRANET_LICENSE_WIDGET_ORDERS }}</div>
				</a>
			</div>
		</div>
	`,
};
