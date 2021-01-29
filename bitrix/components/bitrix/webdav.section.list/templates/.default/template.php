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
$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
$arParams["BASE_URL"] = trim(str_replace(":443", "", $arParams["BASE_URL"]));
$arHeaders = array(
	array("id" => "ID", "name" => "ID", "sort" => "id", "default" => (in_array("ID", $arParams["COLUMNS"]))), 
	array("id" => "ACTIVE", "name" => GetMessage("WD_TITLE_ACTIVE"), "sort" => "active", "default" => (in_array("SORT", $arParams["COLUMNS"]))), 
	array("id" => "SORT", "name" => GetMessage("WD_TITLE_SORT"), "sort" => "sort", "default" => (in_array("SORT", $arParams["COLUMNS"]))), 
	array("id" => "CODE", "name" => GetMessage("WD_TITLE_CODE"), "sort" => "code", "default" => (in_array("CODE", $arParams["COLUMNS"]))), 
	array("id" => "EXTERNAL_ID", "name" => GetMessage("WD_TITLE_EXTCODE"), "sort" => "external_id", "default" => (in_array("EXTERNAL_ID", $arParams["COLUMNS"]))), 
	array("id" => "NAME", "name" => GetMessage("WD_TITLE_NAME"), "sort" => "name", "default" => (in_array("NAME", $arParams["COLUMNS"]))), 
	array("id" => "TIMESTAMP_X", "name" => GetMessage("WD_TITLE_TIMESTAMP"), "sort" => "timestamp_x", "default" => (in_array("TIMESTAMP_X", $arParams["COLUMNS"]))), 
	array("id" => "FILE_SIZE", "name" => GetMessage("WD_TITLE_FILE_SIZE"), "sort" => false, "default" => (in_array("FILE_SIZE", $arParams["COLUMNS"]))), 
	array("id" => "ELEMENT_CNT", "name" => GetMessage("WD_TITLE_ELS"), "sort" => false, "default" => (in_array("ELEMENT_CNT", $arParams["COLUMNS"]))), 
	array("id" => "SECTION_CNT", "name" => GetMessage("WD_TITLE_SECS"), "sort" => false, "default" => (in_array("SECTION_CNT", $arParams["COLUMNS"]))), 
	array("id" => "USER_NAME", "name" => GetMessage("WD_TITLE_MODIFIED_BY"), "sort" => "modified_by", "default" => (in_array("USER_NAME", $arParams["COLUMNS"]))), 
	array("id" => "DATE_CREATE", "name" => GetMessage("WD_TITLE_ADMIN_DCREATE"), "sort" => "created", "default" => (in_array("DATE_CREATE", $arParams["COLUMNS"]))), 
	array("id" => "CREATED_USER_NAME", "name" => GetMessage("WD_TITLE_ADMIN_WCREATE2"), "sort" => "created_by", "default" => (in_array("CREATED_USER_NAME", $arParams["COLUMNS"]))), 
//	array("id" => "SHOW_COUNTER", "name" => GetMessage("WD_TITLE_EXTERNAL_SHOWS"), "sort" => "show_counter", "default" => (in_array("SHOW_COUNTER", $arParams["COLUMNS"]))), 
	array("id" => "PREVIEW_TEXT", "name" => GetMessage("WD_TITLE_EXTERNAL_PREV_TEXT"), "sort" => false, "default" => (in_array("PREVIEW_TEXT", $arParams["COLUMNS"]))), 
	array("id" => "DETAIL_TEXT", "name" => GetMessage("WD_TITLE_EXTERNAL_DET_TEXT"), "sort" => false, "default" => (in_array("DETAIL_TEXT", $arParams["COLUMNS"]))), 
	array("id" => "TAGS", "name" => GetMessage("WD_TITLE_TAGS"), "sort" => "tags", "default" => (in_array("TAGS", $arParams["COLUMNS"]))), 
); 
$db_res = CIBlockProperty::GetList(Array("SORT"=>"ASC", "NAME"=>"ASC"),	Array("ACTIVE"=>"Y","IBLOCK_ID"=>$arParams["IBLOCK_ID"]));
while($res = $db_res->Fetch())
{
	if (in_array($res["CODE"], array("WEBDAV_INFO", "FILE")))
		continue; 
	$arHeaders[] = array("id" => "PROPERTY_".$res["CODE"], "name" => trim(empty($res["NAME"]) ? $res["CODE"] : $res["NAME"]), "sort" => strtolower("PROPERTY_".$res["CODE"]), 
		"default" => (in_array("PROPERTY_".$res["CODE"], $arParams["COLUMNS"]))); 
	
}
if ($arParams["PERMISSION"] >= "U")
{
	$arHeaders = array_merge($arHeaders, array(
	array("id" => "WF_STATUS_ID", "name" => GetMessage("WD_TITLE_STATUS"), "sort" => false, "default" => ($arParams["WORKFLOW"] == "workflow" && $arParams["SHOW_WORKFLOW"] != "N")), 
	array("id" => "WF_NEW", "name" => GetMessage("WD_TITLE_EXTERNAL_WFNEW"), "sort" => false, "default" => (in_array("WF_NEW", $arParams["COLUMNS"]))), 
	array("id" => "LOCK_STATUS", "name" => GetMessage("WD_TITLE_EXTERNAL_LOCK"), "sort" => false, "default" => (in_array("LOCK_STATUS", $arParams["COLUMNS"]))), 
	array("id" => "LOCKED_USER_NAME", "name" => GetMessage("WD_TITLE_EXTERNAL_LOCK_BY"), "sort" => false, "default" => (in_array("LOCKED_USER_NAME", $arParams["COLUMNS"]))), 
	array("id" => "WF_DATE_LOCK", "name" => GetMessage("WD_TITLE_EXTERNAL_LOCK_WHEN"), "sort" => false, "default" => (in_array("WF_DATE_LOCK", $arParams["COLUMNS"]))), 
	array("id" => "WF_COMMENTS", "name" => GetMessage("WD_TITLE_EXTERNAL_COM"), "sort" => false, "default" => (in_array("WF_COMMENTS", $arParams["COLUMNS"]))), 
	array("id" => "BP_PUBLISHED", "name" => GetMessage("WD_TITLE_PUBLIC"), "sort" => false, "default" => ($arParams["WORKFLOW"] == "bizproc" || CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "BIZPROC") != "N")), 
	array("id" => "BIZPROC", "name" => GetMessage("IBLIST_A_BP_H"), "sort" => false, "default" => ($arParams["WORKFLOW"] == "bizproc")), 
	array("id" => "BP_VERSIONS", "name" => GetMessage("WD_VERSIONS"), "sort" => false, "default" => ($arParams["WORKFLOW"] == "bizproc")), 
	)); 
}
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
	$custom_html = GetMessage("WD_MOVE_TO").'<select name="IBLOCK_SECTION_ID">'.	
		'<option value="0"' . ($arParams["SECTION_ID"] == 0 ? "selected" : "").'>'.GetMessage("WD_CONTENT").'</option>'; 
	foreach ($arResult["SECTION_LIST"] as $res)
	{
		$custom_html .= '<option value="'.$res["ID"].'" '.($arParams["SECTION_ID"] == $res["ID"] ? "selected=\"selected\"" : "").'>'.
			str_repeat(".", $res["DEPTH_LEVEL"]).$res["NAME"].'</option>'; 
	}
	$custom_html .= '</select>'; 
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
			"custom_html" => $custom_html
        ),
		"ACTION_ALL_ROWS" => true,
		"NAV_OBJECT" => $arResult["NAV_RESULT"],
		"AJAX_MODE" => "N",
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);?><?
}

if (!empty($arParams["SHOW_NOTE"])):
?>
<br />
<div class="wd-help-list selected" id="wd_list_note"><?=$arParams["~SHOW_NOTE"]?></div>
<?
endif;

if ($arParams["WORKFLOW"] == "workflow" && $arParams["PERMISSION"] >= "U" && $arParams["SHOW_WORKFLOW"] != "N"):?>
<br />
<div class="wd-help-list selected">
<?
if ($arParams["PERMISSION"] >= "W" && CWorkflow::IsAdmin()):
?><?=GetMessage("WD_WF_COMMENT1")?><br /><?
elseif (!in_array(2, $arResult["WF_STATUSES_PERMISSION"])):
?><?=GetMessage("WD_WF_COMMENT2")?><br /><?
else:
	foreach ($arResult["WF_STATUSES_PERMISSION"] as $key => $val):
		if ($val == 2):
			$arr[] = htmlspecialcharsEx($arResult["WF_STATUSES"][$key]);
		endif;
	endforeach;
	
	if (count($arr) == 1):
	?><?=str_replace("#STATUS#", $arr[0], GetMessage("WD_WF_ATTENTION2"))?><br /><?
	else:
	?><?=str_replace("#STATUS#", $arr[0], GetMessage("WD_WF_ATTENTION3"))?><br /><?
	endif;
endif;

if ($arParams["PERMISSION"] >= "W"):
?><?=GetMessage("WD_WF_ATTENTION1")?><br /><?
endif;
?>
</div>
<?endif;?>
<?if ($arParams["PERMISSION"] >= "U"):?>
<script>
if (/*@cc_on ! @*/ false)
{
	try {
		if (new ActiveXObject("SharePoint.OpenDocuments.2"))
		{
			var res = document.getElementsByTagName("A"); 
			for (var ii = 0; ii < res.length; ii++)
			{
				if (res[ii].className.indexOf("element-edit-office") >= 0) { res[ii].style.display = 'block'; }
			}
		}
	} catch(e) { }
}
</script>
<?
endif;
?>