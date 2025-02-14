;(function() {
	"use strict";

	BX.namespace("BX.Sale");

	BX.Sale.ShopPublic = function()
	{
		this.init();
	};

	BX.Sale.ShopPublic.prototype.init = function()
	{
		this.selfFolderUrl = "/shop/settings/";
		if (BX.SidePanel.Instance)
		{
			BX.SidePanel.Instance.bindAnchors({
				rules: [
					{
						condition: [
							this.selfFolderUrl+"iblock_element_edit/",
							this.selfFolderUrl+"cat_product_edit/",
							this.selfFolderUrl+"cat_section_edit/",
							this.selfFolderUrl+"userfield_edit/",
							this.selfFolderUrl+"iblock_edit_property/",
							this.selfFolderUrl+"cat_catalog_edit/",
							this.selfFolderUrl+"sale_cashbox_edit/",
							this.selfFolderUrl+"sale_buyers_profile/",
							this.selfFolderUrl+"sale_buyers_profile_edit/",
							this.selfFolderUrl+"sale_account_edit/",
							this.selfFolderUrl+"sale_transact_edit/",
							this.selfFolderUrl+"cat_store_document_edit/",
							this.selfFolderUrl+"cat_contractor_edit/",
							this.selfFolderUrl+"cat_store_edit/",
							this.selfFolderUrl+"sale_discount_edit/",
							this.selfFolderUrl+"sale_discount_preset_detail/",
							this.selfFolderUrl+"sale_discount_coupon_edit/",
							this.selfFolderUrl+"sale_discount_preset_list/",
							this.selfFolderUrl+"sale_delivery_service_edit/",
							this.selfFolderUrl+"sale_delivery_eservice_edit/",
							this.selfFolderUrl+"sale_pay_system_edit/",
							this.selfFolderUrl+"sale_yandexinvoice_settings/",
							this.selfFolderUrl+"sale_person_type_edit/",
							this.selfFolderUrl+"sale_tax_edit/",
							this.selfFolderUrl+"sale_tax_rate_edit/",
							this.selfFolderUrl+"cat_vat_edit/",
							this.selfFolderUrl+"sale_tax_exempt_edit/",
							this.selfFolderUrl+"cat_measure_edit/",
							this.selfFolderUrl+"cat_group_edit/",
							this.selfFolderUrl+"cat_round_edit/",
							this.selfFolderUrl+"cat_extra_edit/",
							this.selfFolderUrl+"sale_location_node_edit/",
							this.selfFolderUrl+"sale_location_reindex/",
							this.selfFolderUrl+"sale_location_group_edit/",
							this.selfFolderUrl+"sale_location_type_edit/",
							this.selfFolderUrl+"sale_location_import/"
						],
						handler: this.adjustSidePanelOpener
					},
					{
						condition: [
							"/shop/buyer/(\\d+)/edit/"
						],
						options: {
							allowChangeHistory: false
						}
					},
					{
						condition: [
							"/shop/buyer_group/(\\d+)/edit/"
						],
						options: {
							allowChangeHistory: false,
							width: 580
						}
					},
					{
						condition: [
							"/shop/import/instagram/edit/"
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: 700
						}
					},
					{
						condition: [
							"/shop/import/instagram/feedback/"
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: 580
						}
					},
					{
						condition: [
							"/shop/import/instagram/"
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: 1028
						}
					},
					{
						condition: [
							"/shop/orders/details/(\\d+)/",
							"/shop/orders/payment/details/(\\d+)/",
							"/shop/orders/shipment/details/(\\d+)/",
							"/shop/orderform/",
							"/shop/buyer/"
						]
					},
					{
						condition: [
							"/crm/configs/sale/",
							"/crm/configs/ps/add/",
							"/crm/configs/ps/edit/(\\d+)/"
						]
					}
				]
			});
		}

		if (!top.window["adminSidePanel"] || !BX.is_subclass_of(top.window["adminSidePanel"], top.BX.adminSidePanel))
		{
			top.window["adminSidePanel"] = new top.BX.adminSidePanel({
				publicMode: true
			});
		}
	};

	BX.Sale.ShopPublic.prototype.adjustSidePanelOpener = function(event, link)
	{
		if (BX.SidePanel.Instance)
		{
			var isSidePanelParams = (link.url.indexOf("IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER") >= 0);
			if (!isSidePanelParams || (isSidePanelParams && !BX.SidePanel.Instance.getTopSlider()))
			{
				event.preventDefault();
				link.url =	BX.util.add_url_param(link.url, {"publicSidePanel": "Y"});
				BX.SidePanel.Instance.open(link.url, {
					allowChangeHistory: false
				});
			}
		}
	};

})();