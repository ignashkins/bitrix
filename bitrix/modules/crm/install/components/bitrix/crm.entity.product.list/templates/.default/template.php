<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/**
 * @var \CCrmEntityProductListComponent $component
 * @var \CBitrixComponentTemplate $this
 * @var \CMain $APPLICATION
 */

Extension::load([
	'ui.notification',
	'catalog.product-calculator',
]);

/** @var array $grid */
$grid = &$arResult['GRID'];
/** @var string $gridId */
$gridId = $grid['GRID_ID'];
/** @var array $settings */
$settings = &$arResult['SETTINGS'];
/** @var array $currency */
$currency = &$arResult['CURRENCY'];
/** @var array $measures */
$measures = &$arResult['MEASURES'];

/** @var array $moneyTemplate */
$moneyTemplate = [];
$moneyValueIndex = $currency['FORMAT']['TEMPLATE']['VALUE_INDEX'];
foreach ($currency['FORMAT']['TEMPLATE']['PARTS'] as $index => $value)
{
	if ($index == $moneyValueIndex)
	{
		$moneyTemplate[$index] = $value;
	}
	else
	{
		$moneyTemplate[$index] = '<span class="crm-entity-product-info-price-currency">'.$value.'</span>';
	}
}

$taxList = [];
if (!empty($arResult['PRODUCT_VAT_LIST']) && is_array($arResult['PRODUCT_VAT_LIST']))
{
	foreach ($arResult['PRODUCT_VAT_LIST'] as $id => $value)
	{
		$taxList[] = [
			'ID' => $id,
			'VALUE' => $value,
		];
	}
}

$pricePrecision = $arResult['PRICE_PRECISION'];
$quantityPrecision = $arResult['QUANTITY_PRECISION'];
$commonPrecision = $arResult['COMMON_PRECISION'];

$isSetItems = $settings['SET_ITEMS'];
$isReadOnly = !$settings['ALLOW_EDIT'] || $isSetItems;

$containerId = $arResult['PREFIX'].'_crm_entity_product_list_container';

$productTotalContainerId = $arResult['PREFIX'].'_product_sum_total_container';
$rowIdPrefix = $arResult['PREFIX'].'_product_row_';

$jsEventsManagerId = 'PageEventsManager_'.$arResult['COMPONENT_ID'];

$editorConfig = [
	'componentName' => $component->getName(),
	'signedParameters' => $component->getSignedParameters(),
	'reloadUrl' => '/bitrix/components/bitrix/crm.entity.product.list/list.ajax.php',

	'containerId' => $containerId,
	'totalBlockContainerId' => $productTotalContainerId,
	'gridId' => $gridId,
	'formId' => $grid['FORM_ID'],

	'allowEdit' => $settings['ALLOW_EDIT'],
	'dataFieldName' => $arResult['PRODUCT_DATA_FIELD_NAME'],

	'rowIdPrefix' => $rowIdPrefix,

	'pricePrecision' => $pricePrecision,
	'quantityPrecision' => $quantityPrecision,
	'commonPrecision' => $commonPrecision,

	'taxList' => $taxList,
	'allowTax' => $arResult['ALLOW_TAX'] ? 'Y' : 'N',
	'enableTax' => $arResult['ENABLE_TAX'] ? 'Y' : 'N',
	'taxUniform' => $arResult['PRODUCT_ROW_TAX_UNIFORM'],

	'newRowPosition' => $arResult['NEW_ROW_POSITION'],
	'enableDiscount' => $arResult['ENABLE_DISCOUNT'] ? 'Y' : 'N',

	'measures' => $measures['LIST'],
	'defaultMeasure' => $measures['DEFAULT'],

	'currencyId' => $currency['ID'],

	'popupSettings' => $component->getPopupSettings(),
	'languageId' => $component->getLanguageId(),
	'siteId' => $component->getSiteId(),
	'catalogId' => $arResult['CATALOG_ID'],
	'componentId' => $arResult['COMPONENT_ID'],
	'jsEventsManagerId' => $jsEventsManagerId,

	'readOnly' => $isReadOnly,

	'items' => [],
];

$productIdMask = '#PRODUCT_ID_MASK#';
$grid['ROWS']['template_0'] = [
	'ID' => $productIdMask,
	'PRODUCT_ID' => null,
	'PARENT_PRODUCT_ID' => null,
	'IBLOCK_ID' => \CCrmCatalog::GetDefaultID(),
	'OFFERS_IBLOCK_ID' => null,
	'OFFER_ID' => null,
	'PRODUCT_NAME' => '',
	'FIXED_PRODUCT_NAME' => '',
	'QUANTITY' => 1,
	'DISCOUNT_TYPE_ID' => Crm\Discount::PERCENTAGE,
	'DISCOUNT_RATE' => 0,
	'DISCOUNT_SUM' => 0,
	'DISCOUNT_ROW' => 0,
	'PRICE' => 0,
	'PRICE_EXCLUSIVE' => 0,
	'PRICE_NETTO' => 0,
	'PRICE_BRUTTO' => 0,
	'CURRENCY' => $arResult['CURRENCY']['ID'],
	'TAX_RATE' => 0,
	'TAX_INCLUDED' => 'N',
	'TAX_SUM' => 0,
	'SUM' => 0,
	'CUSTOMIZED' => 'N',
	'MEASURE_CODE' => $measures['DEFAULT']['CODE'],
	'MEASURE_NAME' => $measures['DEFAULT']['SYMBOL'],
	'MEASURE_EXISTS' => true,
	'SORT' => null,
	'IS_NEW' => 'N',
];

$rows = [];
foreach ($grid['ROWS'] as $product)
{
	$rawProduct = $product;

	$rawProduct['BASE_PRICE_ID'] = \CCatalogGroup::GetBaseGroup()['ID'];

	$rowId = $rowIdPrefix.$rawProduct['ID'];

	$rawProduct['MEASURE_CODE'] = (string)$rawProduct['MEASURE_CODE'];

	$productName = $rawProduct['PRODUCT_NAME'] ?? '';
	if ($productName === '' && is_numeric($rawProduct['ID']) && $rawProduct['ID'] !== $productIdMask)
	{
		$productName = ((int)$rawProduct['ID'] > 0 && isset($rawProduct['ORIGINAL_PRODUCT_NAME'])
			? $rawProduct['ORIGINAL_PRODUCT_NAME']
			: "[{$rawProduct['ID']}]"
		);
	}

	$fixedProductName = \CCrmProductRow::GetProductTypeName($productName);
	if ($fixedProductName === null)
	{
		$fixedProductName = '';
	}

	$item = [
		'ROW_ID' => $rowId,
		'ID' => $rawProduct['ID'],
		'IBLOCK_ID' => $rawProduct['IBLOCK_ID'],
		'BASE_PRICE_ID' => $rawProduct['BASE_PRICE_ID'],
		'PARENT_PRODUCT_ID' => $rawProduct['PARENT_PRODUCT_ID'],
		'PRODUCT_ID' => $rawProduct['PRODUCT_ID'],
		'OFFERS_IBLOCK_ID' => $rawProduct['OFFERS_IBLOCK_ID'],
		'OFFER_ID' => $rawProduct['OFFER_ID'],
		'PRODUCT_NAME' => $productName,
		// 'IMAGES' => $rawProduct['IMAGES'],
		'FIXED_PRODUCT_NAME' => $fixedProductName,
		'QUANTITY' => $rawProduct['QUANTITY'],
		'DISCOUNT_TYPE_ID' => $rawProduct['DISCOUNT_TYPE_ID'],
		'DISCOUNT_RATE' => $rawProduct['DISCOUNT_RATE'],
		'DISCOUNT_SUM' => $rawProduct['DISCOUNT_SUM'],
		'DISCOUNT_ROW' => $rawProduct['QUANTITY'] * $rawProduct['DISCOUNT_SUM'],
		'PRICE' => $rawProduct['PRICE'],
		'PRICE_EXCLUSIVE' => $rawProduct['PRICE_EXCLUSIVE'],
		'PRICE_NETTO' => $rawProduct['PRICE_NETTO'],
		'PRICE_BRUTTO' => $rawProduct['PRICE_BRUTTO'],
		'CURRENCY' => $rawProduct['CURRENCY'] ?? $arResult['CURRENCY']['ID'],
		'TAX_RATE' => $rawProduct['TAX_RATE'],
		'TAX_INCLUDED' => $rawProduct['TAX_INCLUDED'],
		'TAX_SUM' => $rawProduct['TAX_SUM'],
		'SUM' => $rawProduct['PRICE'] * $rawProduct['QUANTITY'],
		'CUSTOMIZED' => $rawProduct['CUSTOMIZED'],
		'MEASURE_CODE' => $rawProduct['MEASURE_CODE'],
		'MEASURE_NAME' => $rawProduct['MEASURE_NAME'],
		'SORT' => $rawProduct['SORT'],
		'IS_NEW' => $rawProduct['IS_NEW'],
	];

	if ($rawProduct['ID'] !== $productIdMask)
	{
		$editorConfig['items'][] = [
			'rowId' => $rowId,
			'fields' => $item,
		];
	}

	// region MAIN_INFO
	$mainInfoColumn = $item['PRODUCT_NAME'];
	ob_start();
	$APPLICATION->IncludeComponent(
		'bitrix:catalog.grid.product.field',
		'',
		[
			'BUILDER_CONTEXT' => \Bitrix\Crm\Product\Url\ProductBuilder::TYPE_ID,
			'GRID_ID' => $gridId,
			'ROW_ID' => $rowId,
			'GUID' => 'crm_grid_'.$rowId,
			'PRODUCT_FIELDS' => [
				'ID' => $rawProduct['PARENT_PRODUCT_ID'],
				'NAME' => $item['PRODUCT_NAME'],
				'IBLOCK_ID' => $item['IBLOCK_ID'],
				'SKU_IBLOCK_ID' => $item['OFFERS_IBLOCK_ID'],
				'SKU_ID' => $item['OFFER_ID'],
				'BASE_PRICE_ID' => $item['BASE_PRICE_ID'],
			],
			'SKU_TREE' => $rawProduct['SKU_TREE'],
			'MODE' => 'edit',
			'ENABLE_SEARCH' => true,
			'ENABLE_IMAGE_CHANGE_SAVING' => true,
			'ENABLE_INPUT_DETAIL_LINK' => true,
			'IS_NEW' => $item['IS_NEW'],
		]
	);
	$productField = ob_get_clean();
	$product['MAIN_INFO'] = $productField;
	// end region MAIN_INFO

	// region PRICE
	$price = $rawProduct['TAX_INCLUDED'] === 'N' ? $rawProduct['PRICE_NETTO'] : $rawProduct['PRICE_BRUTTO'];
	$price = number_format($price, $pricePrecision, '.', '');
	$priceColumn = CCrmCurrency::MoneyToString($price, $currency['ID']);
	$product['PRICE'] = [
		'PRICE' => [
			'NAME' => $rowId.'_PRICE',
			'VALUE' => $price,
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_PRICE_CURRENCY',
			'VALUE' => $currency['ID'],
		],
	];
	// end region PRICE

	// region QUANTITY
	$quantityColumn = (float)$rawProduct['QUANTITY'].' '.htmlspecialcharsbx($rawProduct['MEASURE_NAME']);
	$product['QUANTITY'] = [
		'PRICE' => [
			'NAME' => $rowId.'_QUANTITY',
			'VALUE' => $rawProduct['QUANTITY'],
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_MEASURE_CODE',
			'VALUE' => $rawProduct['MEASURE_CODE'],
		],
	];
	// end region QUANTITY

	// region DISCOUNT_PRICE
	$discountColumn = CCrmCurrency::MoneyToString($rawProduct['DISCOUNT_SUM'], $currency['ID']);

	if ($rawProduct['DISCOUNT_TYPE_ID'] === Crm\Discount::PERCENTAGE)
	{
		$discountValue = rtrim(rtrim(number_format($rawProduct['DISCOUNT_RATE'], $commonPrecision, '.', ''), '0'), '.');
	}
	else
	{
		$discountValue = rtrim(rtrim(number_format($rawProduct['DISCOUNT_SUM'], $pricePrecision, '.', ''), '0'), '.');
	}

	$product['DISCOUNT_PRICE'] = [
		'PRICE' => [
			'NAME' => $rowId.'_DISCOUNT_PRICE',
			'VALUE' => $discountValue,
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_DISCOUNT_TYPE_ID',
			'VALUE' => $rawProduct['DISCOUNT_TYPE_ID'],
		],
	];
	// end region DISCOUNT_PRICE

	// region DISCOUNT_ROW
	$discountRowValue = (float)$rawProduct['QUANTITY'] * (float)$rawProduct['DISCOUNT_SUM'];
	$discountRowValue = number_format($discountRowValue, $pricePrecision, '.', '');
	$discountRowColumn = CCrmCurrency::MoneyToString($discountRowValue, $currency['ID']);
	$product['DISCOUNT_ROW'] = [
		'PRICE' => [
			'NAME' => $rowId.'_DISCOUNT_ROW',
			'VALUE' => $discountRowValue,
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_DISCOUNT_ROW_CURRENCY',
			'VALUE' => $currency['ID'],
		],
	];
	// end region DISCOUNT_ROW

	// region TAX
	$taxRateColumn = '';
	$taxIncludedColumn = '';
	$taxSumColumn = '';

	if ($arResult['ALLOW_TAX'])
	{
		// region TAX_RATE
		$taxRateSelected = round((float)$rawProduct['TAX_RATE'], $commonPrecision);
		$taxRateColumn = htmlspecialcharsbx($taxRateSelected).' %';

		$taxRates = $arResult['PRODUCT_VAT_LIST'];

		if (!in_array($taxRateSelected, $taxRates, true))
		{
			$taxRates['custom'] = $taxRateSelected;
		}

		asort($taxRates, SORT_NUMERIC);

		$taxRateHtml = '<select class="crm-entity-product-control-select-field"'
			.' id="'.$rowId.'_TAX_RATE"'
			.' data-field-code="TAX_RATE"'
			.' data-product-field="Y" data-parent-id="'.$rowId.'"'
			.'>';

		foreach ($taxRates as $taxId => $taxRate)
		{
			$taxRateHtml .= '<option value="'.htmlspecialcharsbx($taxRate).'" '
				.'data-tax-id="'.$taxId.'"'
				.($taxRateSelected == $taxRate ? 'selected' : '')
				.'>'.htmlspecialcharsbx($taxRate).' %</option>';
		}

		$taxRateHtml .= '</select>';

		$product['TAX_RATE'] = '<div class="crm-entity-product-control-select">'.$taxRateHtml.'</div>';
		// end region TAX_RATE

		// region TAX_INCLUDED
		$taxIncludedColumn = $rawProduct['TAX_INCLUDED']
			? Loc::getMessage('CRM_ENTITY_PL_YES')
			: Loc::getMessage('CRM_ENTITY_PL_NO');
		$product['TAX_INCLUDED'] = '<div class="crm-entity-product-control-checkbox">'
			.'<input type="checkbox"'
			.' id="'.$rowId.'_TAX_INCLUDED"'
			.' data-field-code="TAX_INCLUDED"'
			.' data-product-field="Y" data-parent-id="'.$rowId.'"'
			.($rawProduct['TAX_INCLUDED'] === 'Y' ? ' checked' : '')
			.'>'
			.'</div>';
		// end region TAX_INCLUDED

		// region TAX_SUM
		$taxSum = CCrmCurrency::MoneyToString($rawProduct['TAX_SUM'], $currency['ID']);
		$taxSumColumn = $taxSum;
		$product['TAX_SUM'] = '<div class="crm-entity-product-control-tax-sum-field" id="'.$rowId.'_TAX_SUM">'.$taxSum.'</div>';
		// end region TAX_SUM
	}
	// end region TAX

	// region SUM
	$sum = $rawProduct['PRICE'] * $rawProduct['QUANTITY'];
	$sum = number_format($sum, $pricePrecision, '.', '');
	$sumColumn = CCrmCurrency::MoneyToString($sum, $currency['ID']);

	$product['SUM'] = [
		'PRICE' => [
			'NAME' => $rowId.'_SUM',
			'VALUE' => $sum,
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_SUM_CURRENCY',
			'VALUE' => $currency['ID'],
		],
	];
	// end region SUM

	$columns = [
		'MAIN_INFO' => HtmlFilter::encode($mainInfoColumn),
		//'PROPERTIES' => $properties,
		'PRICE' => $priceColumn,
		'QUANTITY' => $quantityColumn,
		'SUM' => $sumColumn,
		'DISCOUNT_PRICE' => $discountColumn,
		'DISCOUNT_ROW' => $discountRowColumn,
	];
	if ($arResult['ALLOW_TAX'])
	{
		$columns['TAX_RATE'] = $taxRateColumn;
		$columns['TAX_INCLUDED'] = $taxIncludedColumn;
		$columns['TAX_SUM'] = $taxSumColumn;
	}

	$rows[] = [
		'id' => $rawProduct['ID'] === $productIdMask ? 'template_0' : $rawProduct['ID'],
		'raw_data' => $rawProduct,
		'data' => $product,
		'columns' => $columns,
		'has_child' => $isSetItems,
		'parent_id' => \Bitrix\Main\Grid\Context::isInternalRequest() && !empty($rawProduct['PARENT_ID']) ? $rawProduct['PARENT_ID'] : 0,
		'editable' => !$isSetItems && !$isReadOnly,
	];
}

foreach ($rows as $key => $row)
{
	if ($row['id'] === 'template_0')
	{
		$editorConfig['templateGridEditData']['template_0'] = $row['data'];
		$editorConfig['templateItemFields'] = $row['raw_data'];
		$editorConfig['templateIdMask'] = $productIdMask;
	}
	else
	{
		$editorConfig['templateGridEditData'][$row['id']] = $row['data'];
	}
}

// $cols = [];
//
// foreach ($grid['VISIBLE_COLUMNS'] as $column)
// {
// 	/*	if ($column['id'] == 'DISCOUNTS')
// 			continue; */
//
// 	$item = ["column" => $column['id']];
//
// 	/*	if($column['id'] == 'MAIN_INFO' && !$isSetItems)
// 			$item['rowspan'] = 2; */
//
// 	$cols[] = $item;
// }
//
// $rowLayout = [$cols];
//
// if (!$isSetItems)
// {
// 	$rowLayout = array_merge($rowLayout, [
// 		[
// 			["data" => "DISCOUNTS", "column" => "DISCOUNTS", "colspan" => count($grid['VISIBLE_COLUMNS']) - 1]
// 		]
// 	]);
// }
?>
<div class="crm-entity-product-list-wrapper" id="<?=$containerId?>"><?php
	if (!$isReadOnly)
	{
		$panelStatus = ($arResult['NEW_ROW_POSITION'] === 'bottom') ? 'hidden' : 'active';
		$buttonTopPanelClasses = [
			'crm-entity-product-list-add-block',
			'crm-entity-product-list-add-block-top',
			'crm-entity-product-list-add-block-' . $panelStatus,
		];

		$buttonTopPanelClasses = implode(' ', $buttonTopPanelClasses);
		?>
		<div class="<?=$buttonTopPanelClasses?>">
			<div>
				<a class="ui-btn ui-btn-primary"
						data-role="product-list-add-button"
						title="<?=Loc::getMessage('CRM_ENTITY_PL_ADD_PRODUCT_TITLE')?>"
						tabindex="-1">
					<?=Loc::getMessage('CRM_ENTITY_PL_ADD_PRODUCT')?>
				</a>
				<a class="ui-btn ui-btn-light-border"
						data-role="product-list-select-button"
						title="<?=Loc::getMessage('CRM_ENTITY_PL_SELECT_PRODUCT_TITLE')?>"
						tabindex="-1">
					<?=Loc::getMessage('CRM_ENTITY_PL_SELECT_PRODUCT')?>
				</a>
			</div>
			<button class="ui-btn ui-btn-light-border ui-btn-icon-setting"
					data-role="product-list-settings-button"></button>
		</div>
		<?php
	}

	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		[
			'GRID_ID' => $gridId,
			'HEADERS' => $grid['COLUMNS'],
			// 'ROW_LAYOUT' => $rowLayout,
			'SORT' => $grid['SORT'],
			'SORT_VARS' => $grid['SORT_VARS'],
			'ROWS' => $rows,
			'FORM_ID' => $grid['FORM_ID'],
			'TAB_ID' => $grid['TAB_ID'],
			'AJAX_ID' => $grid['AJAX_ID'],
			'AJAX_MODE' => $grid['AJAX_MODE'],
			'AJAX_OPTION_JUMP' => $grid['AJAX_OPTION_JUMP'],
			'AJAX_OPTION_HISTORY' => $grid['AJAX_OPTION_HISTORY'],
			'AJAX_LOADER' => $grid['AJAX_LOADER'],
			'SHOW_NAVIGATION_PANEL' => $grid['SHOW_NAVIGATION_PANEL'],
			'SHOW_PAGINATION' => $grid['SHOW_PAGINATION'],
			'SHOW_TOTAL_COUNTER' => $grid['SHOW_TOTAL_COUNTER'],
			'SHOW_PAGESIZE' => $grid['SHOW_PAGESIZE'],
			'SHOW_ROW_ACTIONS_MENU' => false,
			'PAGINATION' => $grid['PAGINATION'],
			'ALLOW_SORT' => false,
			'ALLOW_ROWS_SORT' => true,
			'ALLOW_ROWS_SORT_IN_EDIT_MODE' => true,
			'ALLOW_ROWS_SORT_INSTANT_SAVE' => false,
			'ENABLE_ROW_COUNT_LOADER' => false,
			'HIDE_FILTER' => true,
			'ENABLE_COLLAPSIBLE_ROWS' => false,
			'ADVANCED_EDIT_MODE' => true,
			'TOTAL_ROWS_COUNT' => $grid['TOTAL_ROWS_COUNT'],
			'NAME_TEMPLATE' => (string)($arParams['~NAME_TEMPLATE'] ?? ''),
			'ACTION_PANEL' => $grid['ACTION_PANEL'],
			'SHOW_ACTION_PANEL' => !empty($grid['ACTION_PANEL']),
			'SHOW_ROW_CHECKBOXES' => false,
			'SETTINGS_WINDOW_TITLE' => $arResult['ENTITY']['TITLE'],
		],
		$component
	);
	if (!$isReadOnly)
	{
		$panelStatus = ($arResult['NEW_ROW_POSITION'] !== 'bottom') ? 'hidden' : 'active';
		$buttonBottomPanelClasses = [
			'crm-entity-product-list-add-block',
			'crm-entity-product-list-add-block-bottom',
			'crm-entity-product-list-add-block-' . $panelStatus,
		];

		$buttonBottomPanelClasses = implode(' ', $buttonBottomPanelClasses);
		?>
		<div class="<?=$buttonBottomPanelClasses?>">
			<div>
				<a class="ui-btn ui-btn-primary"
				   data-role="product-list-add-button"
				   title="<?=Loc::getMessage('CRM_ENTITY_PL_ADD_PRODUCT_TITLE')?>"
				   tabindex="-1">
					<?=Loc::getMessage('CRM_ENTITY_PL_ADD_PRODUCT')?>
				</a>
				<a class="ui-btn ui-btn-light-border"
				   data-role="product-list-select-button"
				   title="<?=Loc::getMessage('CRM_ENTITY_PL_SELECT_PRODUCT_TITLE')?>"
				   tabindex="-1">
					<?=Loc::getMessage('CRM_ENTITY_PL_SELECT_PRODUCT')?>
				</a>
			</div>
			<button class="ui-btn ui-btn-light-border ui-btn-icon-setting"
					data-role="product-list-settings-button"></button>
		</div>
		<?php
	}
	?>
	<div class="crm-entity-total-wrapper crm-product-list-page-content">
		<div class="crm-product-list-result-container" id="<?=$productTotalContainerId?>">
			<table class="crm-product-list-payment-side-table">
				<tr class="crm-product-list-payment-side-table-row">
					<td><?=Loc::getMessage('CRM_PRODUCT_TOTAL_BEFORE_DISCOUNT')?>:</td>
					<td>
						<span data-total="totalWithoutDiscount">
							<?=\CCurrencyLang::CurrencyFormat($arResult['TOTAL_BEFORE_DISCOUNT'], $currency['ID'], false)?>
						</span>
						<span data-role="currency-wrapper" class="crm-product-list-result-grid-item-currency-symbol"><?=$currency['TEXT']?></span>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row">
					<td><?=Loc::getMessage('CRM_DELIVERY_TOTAL')?>:</td>
					<td>
						<span data-total="totalDelivery">
							<?=\CCurrencyLang::CurrencyFormat($arResult['TOTAL_DELIVERY_SUM'], $currency['ID'], false)?>
						</span>
						<span data-role="currency-wrapper" class="crm-product-list-result-grid-item-currency-symbol"><?=$currency['TEXT']?></span>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row crm-product-list-result-grid-benefit">
					<td>
						<?=Loc::getMessage('CRM_PRODUCT_TOTAL_DISCOUNT')?>:
					</td>
					<td>
						<span data-total="totalDiscount">
							<?=\CCurrencyLang::CurrencyFormat($arResult['TOTAL_DISCOUNT'], $currency['ID'], false)?>
						</span>
						<span data-role="currency-wrapper" class="crm-product-list-result-grid-item-currency-symbol"><?=$currency['TEXT']?></span>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row">
					<td><?=Loc::getMessage('CRM_PRODUCT_TOTAL_BEFORE_TAX')?>:</td>
					<td>
						<span data-total="totalWithoutTax">
							<?=\CCurrencyLang::CurrencyFormat($arResult['TOTAL_BEFORE_TAX'], $currency['ID'], false)?>
						</span>
						<span data-role="currency-wrapper" class="crm-product-list-result-grid-item-currency-symbol"><?=$currency['TEXT']?></span>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row">
					<td class="crm-product-list-payment-side-table-td-border">
						<?=Loc::getMessage('CRM_PRODUCT_TOTAL_TAX')?>:
					</td>
					<td class="crm-product-list-payment-side-table-td-border">
						<span data-total="totalTax">
							<?=\CCurrencyLang::CurrencyFormat($arResult['TOTAL_TAX'], $currency['ID'], false)?>
						</span>
						<span data-role="currency-wrapper" class="crm-product-list-result-grid-item-currency-symbol"><?=$currency['TEXT']?></span>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row">
					<td class="crm-product-list-result-grid-total-big">
						<?=Loc::getMessage('CRM_PRODUCT_SUM_TOTAL')?>:
					</td>
					<td class="crm-product-list-result-grid-total-big">
						<span data-total="totalCost">
							<?=\CCurrencyLang::CurrencyFormat($arResult['TOTAL_SUM'], $currency['ID'], false)?>
						</span>
						<span data-role="currency-wrapper" class="crm-product-list-result-grid-item-currency-symbol"><?=$currency['TEXT']?></span>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<input type="hidden" name="<?=htmlspecialcharsbx($arResult['PRODUCT_DATA_FIELD_NAME'])?>" value="" />
	<input type="hidden"
			name="<?=htmlspecialcharsbx($arResult['PRODUCT_DATA_FIELD_NAME'].'_SETTINGS')?>"
			value="" />
</div>
<script>
	BX.message(<?=Json::encode(Loc::loadLanguageFile(__FILE__))?>);
	BX.ready(function() {
		if (!BX.Reflection.getClass('BX.Crm.Entity.ProductList.Instance'))
		{
			BX.Crm.Entity.ProductList.Instance = new BX.Crm.Entity.ProductList.Editor('<?=$arResult['ID']?>');
		}

		BX.Crm.Entity.ProductList.Instance.init(<?=Json::encode($editorConfig)?>);
		BX.Crm["<?=$jsEventsManagerId?>"] = BX.Crm.Entity.ProductList.Instance.getPageEventsManager();
	});
</script>