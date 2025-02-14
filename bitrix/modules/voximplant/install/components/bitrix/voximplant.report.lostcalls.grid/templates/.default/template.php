<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load(["ui.icons", "ui.hint"]);

$rows = [];

function getCompareLayout($value)
{
	if (is_null($value))
	{
		return '&mdash;';
	}

	$result = $value;

	if ($value > 0)
	{
		$result = '<div class="telephony-report-lost-calls-grid-value-up">+' .
				  	htmlspecialcharsEx($result) .
				  '</div>';
	}
	elseif ($value < 0)
	{
		$result = '<div class="telephony-report-lost-calls-grid-value-down">' .
				  	htmlspecialcharsEx($result) .
				  '</div>';
	}

	return $result;
}

function createLink($node, $url)
{
	return
		'<div class="telephony-report-lost-calls-grid-value-clickable" data-target="'.htmlspecialcharsbx($url).'">'
		. $node .
		'</div>';
}

function wrapGridValue($node)
{
	return
		'<div class="telephony-report-lost-calls-grid-value">'
		. $node .
		'</div>';
}

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$columns = $row["columns"];

	$columns["DATE"] = wrapGridValue(htmlspecialcharsEx($columns["DATE"]["valueFormatted"]));
	$columns["LOST_CALLS_COUNT"] = wrapGridValue(createLink($columns["LOST_CALLS_COUNT"]["value"], $columns["LOST_CALLS_COUNT"]["url"]));
	$columns["DYNAMICS"] = wrapGridValue(getCompareLayout($columns["DYNAMICS"]["value"]));

	$rows[] = [
		"id" => $row["id"],
		"columns" => $columns,
		"actions" => []
	];
}

$arResult["GRID"]["ROWS"] = $rows;

?>

<?
$APPLICATION->IncludeComponent(
	"bitrix:main.ui.grid",
	"",
	array(
		"GRID_ID" => $arResult["GRID"]["ID"],
		"COLUMNS" => $arResult["GRID"]["COLUMNS"],
		"ROWS" => $arResult["GRID"]["ROWS"],
		"SHOW_ROW_CHECKBOXES" => false,
		"SHOW_GRID_SETTINGS_MENU" => false,
		"SHOW_PAGINATION" => false,
		"SHOW_SELECTED_COUNTER" => false,
		"SHOW_TOTAL_COUNTER" => false,
		"ACTION_PANEL" => [],
		"TOTAL_ROWS_COUNT" => null,
		"ALLOW_COLUMNS_SORT" => true,
		"ALLOW_COLUMNS_RESIZE" => false,
		"ALLOW_SORT" => true
	)
);
?>
<script>
	BX.message({
		'TELEPHONY_REPORT_LOST_CALLS_HELP': '<?= GetMessageJS("TELEPHONY_REPORT_LOST_CALLS_HELP")?>'
	});

	BX.Voximplant.Report.LostCalls.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>'
	});

	BX.ajax.UpdatePageData = function(){};
</script>
