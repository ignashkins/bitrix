<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;

Extension::load([
	'ui.vue',
	'salescenter.payment-pay.payment-method',
]);

/**
 * @var array $arParams
 * @var array $arResult
 */

$items = [];
foreach ($arResult['PAYSYSTEMS_LIST'] as $item)
{
	$items[] = array_merge($item, ['SHOW_DESCRIPTION'=>'N']);
}
$items = CUtil::PhpToJSObject($items);
?>

<div id="payment_method-list"></div>

<script>
	var items = <?=$items?>;
	BX.Vue.create({
		el: '#payment_method-list',
		data: () => {return {items}},
		template: `<salescenter-payment_pay-payment_method-list :items='items'/>`,
	});
</script>