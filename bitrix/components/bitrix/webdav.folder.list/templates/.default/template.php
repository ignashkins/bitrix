<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;

$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/utils.js"></script>', true);
CUtil::InitJSCore(array()); 

global $by, $order;
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
$arParams["BASE_URL"] = trim(str_replace(":443", "", $arParams["BASE_URL"]));
$arHeaders = array(
	array("id" => "NAME", "name" => GetMessage("WD_TITLE_NAME"), "sort" => "name", "default" => (in_array("NAME", $arParams["COLUMNS"]))), 
	array("id" => "TIMESTAMP_X", "name" => GetMessage("WD_TITLE_TIMESTAMP"), "sort" => "timestamp_x", "default" => (in_array("TIMESTAMP_X", $arParams["COLUMNS"]))), 
	array("id" => "FILE_SIZE", "name" => GetMessage("WD_TITLE_FILE_SIZE"), "sort" => "file_size", "default" => (in_array("FILE_SIZE", $arParams["COLUMNS"]))), 
); 
/********************************************************************
				/Input params
********************************************************************/
if (!empty($arResult["ERROR_MESSAGE"])):
	ShowError($arResult["ERROR_MESSAGE"]);
endif;

$arResult["GRID_DATA"] = (is_array($arResult["GRID_DATA"]) ? $arResult["GRID_DATA"] : array()); 

if ($arParams["PERMISSION"] <= "U" || $arParams["PERMISSION"] == "W" && $arParams["CHECK_CREATOR"] == "Y")
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.grid",
		"",
		array(
			"GRID_ID" => $arParams["GRID_ID"],
			"HEADERS" => $arHeaders , 
			"SORT" => array($by => $order),
			"ROWS" => $arResult["GRID_DATA"],
			"FOOTER" => array(array("title" => GetMessage("WD_ALL"), "value" => $arResult["GRID_DATA_COUNT"])),
			"EDITABLE" => false,
			"ACTIONS" => false,
			"ACTION_ALL_ROWS" => false,
			"NAV_OBJECT" => $arResult["NAV_RESULT"],
			"AJAX_MODE" => "N",
		),
		($this->__component->__parent ? $this->__component->__parent : $component)
	);
}
else
{
?><?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID" => $arParams["GRID_ID"],
		"HEADERS" => $arHeaders, 
		"SORT" => array($by => $order),
		"ROWS" => $arResult["GRID_DATA"],
		"FOOTER" => array(array("title" => GetMessage("WD_ALL"), "value" => $arResult["GRID_DATA_COUNT"])),
		"EDITABLE" => true,
		"ACTIONS" => array(
			"delete" => true, 
			"custom_html" => GetMessage("WD_MOVE_TO").
				'<input type="text" name="IBLOCK_SECTION_ID" id="WD_IBLOCK_SECTION_ID" value="'.$arParams["SECTION_ID"].'" onclick="__wd_show_tree(this);" />'
        ),
		"ACTION_ALL_ROWS" => true,
		"NAV_OBJECT" => $arResult["NAV_RESULT"],
		"AJAX_MODE" => "N",
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);?>
<script>
function __wd_show_tree(obj)
{
	<?=$APPLICATION->GetPopupLink(Array(
		"URL"=>"/bitrix/components/bitrix/webdav/templates/.default/disk_sections_tree.php?lang=".
			LANGUAGE_ID."&site=".SITE_ID."&folder=".urlencode($arParams["FOLDER"])."&active=".urlencode($arParams["SECTION_ID"]),
		"PARAMS" => Array("width" => 450, "height" => 450)
		)
	)?>; 
}
function __wd_add_move_action()
{
	var form = document.forms.form_<?=$arParams["GRID_ID"]?>; 
	if (typeof form != "object" || form == null)
		return false; 
	form.apply.onkeydown = form.apply.onmousedown = function(){document.forms.form_<?=$arParams["GRID_ID"]?>.action_button_<?=$arParams["GRID_ID"]?>.value = "MOVE";}
}
__wd_add_move_action();
</script>
<?
}

if (!empty($arParams["SHOW_NOTE"])):
?>
<br />
<div class="wd-help-list selected" id="wd_list_note"><?=$arParams["~SHOW_NOTE"]?></div>
<?
endif;
?>