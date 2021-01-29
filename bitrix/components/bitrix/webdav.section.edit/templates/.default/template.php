<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
if (!empty($arResult["ERROR_MESSAGE"]))
{
	ShowError($arResult["ERROR_MESSAGE"]);
}

$arResult["FIELDS"] = array(); 
if ($arResult["SECTION"]["ID"] > 0)
{
	$arResult["FIELDS"][] = array("id" => "CREATED", "name" => GetMessage("WD_CREATED"), "type" => "label"); 
	$arResult["DATA"]["CREATED"] = $arResult["SECTION"]["DATE_CREATE"]; 
	if (!empty($arResult["USER"]["USER_".$arResult["SECTION"]["CREATED_BY"]]))
	{
		$res = $arResult["USER"]["USER_".$arResult["SECTION"]["CREATED_BY"]];
		$arResult["DATA"]["CREATED"] .= ' [<a href="'.$res["URL"].'">'.$res["ID"].'</a>]('.$res["LOGIN"].') '.$res["NAME"].' '.$res["LAST_NAME"]; 
	}
	$arResult["FIELDS"][] = array("id" => "UPDATED", "name" => GetMessage("WD_LAST_UPDATE"), "type" => "label"); 
	$arResult["DATA"]["UPDATED"] = $arResult["SECTION"]["TIMESTAMP_X"]; 
	if (!empty($arResult["USER"]["USER_".$arResult["SECTION"]["MODIFIED_BY"]]))
	{
		$res = $arResult["USER"]["USER_".$arResult["SECTION"]["MODIFIED_BY"]];
		$arResult["DATA"]["UPDATED"] .= ' [<a href="'.$res["URL"].'">'.$res["ID"].'</a>]('.$res["LOGIN"].') '.$res["NAME"].' '.$res["LAST_NAME"]; 
	}
}
$arResult["FIELDS"][] = array("id" => "IBLOCK_SECTION_ID", "name" => GetMessage("WD_PARENT_SECTION"), "type" => "custom"); 
$arResult["DATA"]["IBLOCK_SECTION_ID"] = 
	'<select name="IBLOCK_SECTION_ID">'.
		'<option value="0" '.(empty($_REQUEST["IBLOCK_SECTION_ID"]) || $_REQUEST["IBLOCK_SECTION_ID"] == 0 ? ' selected="selected"' : '').
			((empty($arResult["SECTION"]["~IBLOCK_SECTION_ID"]) || $arResult["SECTION"]["~IBLOCK_SECTION_ID"] == 0) ? ' class="selected"' : '').'>'.
			GetMessage("WD_CONTENT").'</option>'; 
foreach ($arResult["SECTION_LIST"] as $res)
{
	$arResult["DATA"]["IBLOCK_SECTION_ID"] .= 
		'<option value="'.htmlspecialchars($res["ID"]).'" '.($_REQUEST["IBLOCK_SECTION_ID"] == $res["ID"] ? 'selected="selected"' : '').
			($arResult["SECTION"]["~IBLOCK_SECTION_ID"] == $res["ID"] ? ' class="selected"' : '').'>'.
			str_repeat(".", $res["DEPTH_LEVEL"]).($res["NAME"]).'</option>'; 
}
$arResult["DATA"]["IBLOCK_SECTION_ID"] .= 
	'</select>'; 
$arResult["FIELDS"][] = array("id" => "NAME", "name" => GetMessage("WD_NAME"), "type" => "text"); 
$arResult["DATA"]["NAME"] = $_REQUEST["NAME"]; 

?><?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
		"FORM_ID" => $arParams["FORM_ID"],
		"TABS" => array(
			array(
				"id" => "tab1", "name" => GetMessage("WD_FOLDER"), 
				"fields" => $arResult["FIELDS"]
			)
		),
		"BUTTONS" => array(
			"back_url" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))), 
			"custom_html" => '<input type="hidden" name="SECTION_ID" value="'.$arParams["SECTION_ID"].'" /><input type="hidden" name="edit_section" value="Y" />'
),
		"DATA"=> $arResult["DATA"],
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);?>
<?
if ($this->__component->__parent)
{
	$this->__component->__parent->arResult["arButtons"] = is_array($this->__component->__parent->arResult["arButtons"]) ? $this->__component->__parent->arResult["arButtons"] : array(); 
	$this->__component->__parent->arResult["arButtons"][] = array(
		"TEXT" => GetMessage("WD_DELETE_SECTION"),
		"LINK" => "javascript:WDDrop('".CUtil::JSEscape($arResult["URL"]["DELETE"])."');",
		"ICON" => "btn-delete section-delete"); 
}
?>