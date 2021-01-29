<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
elseif (!CModule::IncludeModule("iblock")):
	ShowError(GetMessage("W_IBLOCK_IS_NOT_INSTALLED"));
	return 0;
endif;

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	if (!is_object($arParams["OBJECT"]))
	{
		$arParams["OBJECT"] = new CWebDavIblock($arParams['IBLOCK_ID'], $arParams['BASE_URL'], $arParams);
	}
	$ob = $arParams["OBJECT"]; 
	
	$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
	$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
	$arParams["ROOT_SECTION_ID"] = intVal($arParams["ROOT_SECTION_ID"]);
	$arParams["PERMISSION"] = $ob->permission;
	$arParams["CHECK_CREATOR"] = ($arParams["CHECK_CREATOR"] == "Y" ? "Y" : "N");
	$arParams["ELEMENT_ID"] = intVal(!empty($arParams["ELEMENT_ID"]) ? $arParams["ELEMENT_ID"] : $_REQUEST["ELEMENT_ID"]);
	$arParams["ACTION"] = strToUpper(!empty($arParams["ACTION"]) ? $arParams["ACTION"] : $_REQUEST["ACTION"]);
	$arParams["REPLACE_SYMBOLS"] = ($arParams["REPLACE_SYMBOLS"] == "Y" ? "Y" : "N");
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#", 
		"sections_alternative" => "PAGE_NAME=sections&PATH=#PATH#", 
		
		"element" => "PAGE_NAME=element&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#", 
		"element_edit" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#", 
		"element_file" => "PAGE_NAME=element_file&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#", 
		"element_history_get" => "PAGE_NAME=element_history_get&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#", 
		"element_versions" => "PAGE_NAME=element_versions&ELEMENT_ID=#ELEMENT_ID#", 
		
		"user_view" => "PAGE_NAME=user_view&USER_ID=#USER_ID#", 
		
		"webdav_bizproc_view" => "PAGE_NAME=webdav_bizproc_view&ELEMENT_ID=#ELEMENT_ID#", 
		"webdav_bizproc_log" => "PAGE_NAME=webdav_bizproc_log&ID=#ID#", 
		"webdav_start_bizproc" => "PAGE_NAME=webdav_start_bizproc&ELEMENT_ID=#ELEMENT_ID#", 
		"webdav_task" => "PAGE_NAME=webdav_task&ID=#ID#", 
		"webdav_task_list" => "PAGE_NAME=webdav_task_list", );
	
	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $GLOBALS["APPLICATION"]->GetCurPageParam($URL_VALUE, array("PAGE_NAME", "PATH", 
				"SECTION_ID", "ELEMENT_ID", "ACTION", "AJAX_CALL", "USER_ID", "sessid", "save", "login", "edit", "action"));
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialchars($arParams["~".strToUpper($URL)."_URL"]);
	}
	$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") === false);
	if (!$arParams["CONVERT_PATH"])
		$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#"));
/***************** ADDITIONAL **************************************/
	$arParams["SET_STATUS_404"] = ($arParams["SET_STATUS_404"] == "Y" ? "Y" : "N");
	$arParams["USE_WORKFLOW"] = ($ob->workflow == "workflow" ? "Y" : "N"); 
	$arParams["USE_BIZPROC"] = ($ob->workflow == "bizproc" ? "Y" : "N"); 
	if (!empty($arParams["BIZPROC"]) && ($ob->workflow == "bizproc" || $ob->workflow == "bizproc_limited"))
	{
		$arParams["BIZPROC"] = array(
			"MODULE_ID" => "webdav", 
			"ENTITY" => (!WDBpCheckEntity($arParams["BIZPROC"]["ENTITY"]) ? "CIBlockDocumentWebdav" : $arParams["BIZPROC"]["ENTITY"]), 
			"DOCUMENT_TYPE" => (empty($arParams["BIZPROC"]["DOCUMENT_TYPE"]) ? "iblock_".$arParams["IBLOCK_ID"] : $arParams["BIZPROC"]["DOCUMENT_TYPE"]));
		$ob->wfParams["DOCUMENT_TYPE"] = array("webdav", $arParams["BIZPROC"]["ENTITY"], $arParams["BIZPROC"]["DOCUMENT_TYPE"]); 
	}
	$arParams["DOCUMENT_ID"] = $arParams["DOCUMENT_TYPE"] = $arParams["OBJECT"]->wfParams["DOCUMENT_TYPE"];
	$arParams["DOCUMENT_ID"][2] = $arParams["ELEMENT_ID"]; 
	$arParams["BIZPROC"] = array(
		"MODULE_ID" => $arParams["DOCUMENT_TYPE"][0], 
		"ENTITY" => $arParams["DOCUMENT_TYPE"][1], 
		"DOCUMENT_TYPE" => $arParams["DOCUMENT_TYPE"][2]);
		
	$arParams["NAME_FILE_PROPERTY"] = $ob->file_prop; 
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["DISPLAY_PANEL"] = ($arParams["DISPLAY_PANEL"]=="Y"); //Turn off by default
/********************************************************************
				/Input params
********************************************************************/
$db_res = $ob->_get_mixed_list(null, $arParams, $arParams["ELEMENT_ID"]); 
if (!($db_res && $arResult["ELEMENT"] = $db_res->GetNext()))
{
	$db_res = $ob->_get_mixed_list(null, $arParams += array("SHOW_VERSION" => "Y"), $arParams["ELEMENT_ID"]); 
	if (!($db_res && $arResult["ELEMENT"] = $db_res->GetNext()))
	{
		ShowError(GetMessage("WD_ERROR_ELEMENT_NOT_FOUND"));
		if ($arParams["SET_STATUS_404"] == "Y"):
			CHTTP::SetStatus("404 Not Found");
		endif;
		return 0;
	}
}
$ob->_get_file_info_arr($arResult["ELEMENT"]); 
if (!$ob->CheckWebRights($options = array(
		"action" => ($arParams["ACTION"] == "CLONE" ? "clone" : "edit"), 
		"arElement" => $arResult["ELEMENT"])))
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}
elseif ($arParams["ACTION"] != "CLONE")
{
	CIBlockElement::WF_Lock($arParams["ELEMENT_ID"], ($ob->workflow == "workflow"));
	/**
	 * This part of code is necessary because info about element is got 
	 * already but information about locking is absent. We can not lock 
	 * element until check all rulles.
	 */
	if ($arResult["ELEMENT"]["WF_LOCKED_BY"] != $USER->GetID()) 
	{
		$arResult["ELEMENT"]["WF_LOCKED_BY"] = $USER->GetID(); 
		$arResult["ELEMENT"]["LOCK_STATUS"] = "yellow"; 
	}
}

/********************************************************************
				Default params
********************************************************************/
__prepare_item_info($arResult["ELEMENT"], $arParams); 
$arResult["ELEMENT"]['URL']['THIS'] = $ob->_uencode($arResult["ELEMENT"]['URL']['THIS'], array("utf8" => "Y", "convert" => $arParams["CONVERT"])); 
$cache = new CPHPCache;
$cache_path_main = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName."/".$arParams["IBLOCK_ID"]."/");
$arError = array();
$bVarsFromForm = false;
$arUsersCache = array();
$CHILD_ID = 0; 
/************** Parent element *************************************/
$arResult["ELEMENT_ORIGINAL"] = array(); 
if (intVal($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]) > 0 && 
	$arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] != $arParams["ELEMENT_ID"])
{
	$db_res = CIBlockElement::GetList(array(), array("ID" => $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"], "SHOW_NEW" => "Y"));
	if ($db_res && $obElement = $db_res->GetNextElement())
	{
		$arResult["ELEMENT_ORIGINAL"] = $obElement->GetFields() + array("PROPERTIES" => $obElement->GetProperties());
		if ($ob->workflow == "workflow")
			$arParams["ELEMENT_ID"] = $arResult["ELEMENT"]["ID"] = $arResult["ELEMENT_ORIGINAL"]["ID"];
	}
	$ob->_get_file_info_arr($arResult["ELEMENT_ORIGINAL"]); 
	__prepare_item_info($arResult["ELEMENT_ORIGINAL"], $arParams); 
}
/************** Sections info **************************************/
$arResult["ROOT_SECTION"] = $ob->arRootSection; 
$arParams["SECTION_ID"] = ($arResult["ELEMENT"]["IBLOCK_SECTION_ID"] == $arParams["ROOT_SECTION_ID"] ? 0 : $arResult["ELEMENT"]["IBLOCK_SECTION_ID"]);
/************** Another info ***************************************/
$arResult["USERS"] = array(
	$arResult["ELEMENT"]["~MODIFIED_BY"] => $arResult["ELEMENT"]["MODIFIED_BY"], 
	$arResult["ELEMENT"]["~CREATED_BY"] => $arResult["ELEMENT"]["CREATED_BY"], 
	$arResult["ELEMENT"]["~WF_LOCKED_BY"] => $arResult["ELEMENT"]["WF_LOCKED_BY"]);
$arResult["ELEMENT"]["MODIFIED_BY"] = $arResult["ELEMENT"]["~MODIFIED_BY"]; 
$arResult["ELEMENT"]["CREATED_BY"] = $arResult["ELEMENT"]["~CREATED_BY"]; 
$arResult["ELEMENT"]["WF_LOCKED_BY"] = $arResult["ELEMENT"]["~WF_LOCKED_BY"]; 
$arResult["ERROR_MESSAGE"] = "";
$arResult["WF_STATUSES"] = array();
$arResult["WF_STATUSES_PERMISSION"] = array();
$arDocumentStates = $arResult["ELEMENT"]["~arDocumentStates"]; 
$arResult["CurrentUserGroups"] = $ob->USER["GROUPS"];
if ($arResult["ELEMENT"]["CREATED_BY"] == $GLOBALS["USER"]->GetID())
	$arResult["CurrentUserGroups"][] = "author"; 
$arParams["WORKFLOW"] = $arWorkFlow = array(
	"LAST_ID" => $arParams["ELEMENT_ID"], 
	"STATUS_ID" => 0, 
	"STATUS_TITLE" => "", 
	"STATUS_PERMISSION" => "");
/********************************************************************
				/Default params
********************************************************************/

/********************************************************************
				Data
********************************************************************/
/************** Element ********************************************/
$arResult["ELEMENT"]["FULL_NAME"] = $arResult["ELEMENT"]["NAME"];
$arResult["ELEMENT"]["EXTENTION"] = $arResult["ELEMENT"]["FILE_EXTENTION"];
$arResult["ELEMENT"]["ORIGINAL"] = $arResult["ELEMENT_ORIGINAL"];
/************** File ***********************************************/
if (!empty($arResult["ELEMENT"]["ORIGINAL"]))
	__get_file_array($arResult["ELEMENT"]["ORIGINAL"]["PROPERTIES"][$ob->file_prop]["VALUE"], $arResult["ELEMENT"]["ORIGINAL"]); 
/************** Paths **********************************************/
$arResult["ELEMENT"]["URL"] += array(
	"~DOWNLOAD_ORIGINAL" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_HISTORY_GET_URL"], 
		array("ELEMENT_ID" => $arResult["ELEMENT_ORIGINAL"]["ID"], "ID" => $arResult["ELEMENT_ORIGINAL"]["ID"], "ELEMENT_NAME" => $arResult["ELEMENT_ORIGINAL"]["NAME"])), 
	"DOWNLOAD_ORIGINAL" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_HISTORY_GET_URL"], 
		array("ELEMENT_ID" => $arResult["ELEMENT_ORIGINAL"]["ID"], "ID" => $arResult["ELEMENT_ORIGINAL"]["ID"], "ELEMENT_NAME" => $arResult["ELEMENT_ORIGINAL"]["NAME"]))); 
$arResult["ELEMENT"]["URL"]["FILE"] = $arResult["ELEMENT"]["URL"]["THIS"];
$arResult["URL"] = array("WEBDAV_START_BIZPROC" => $arResult["ELEMENT"]["URL"]["BP_START"], "BP" => $arResult["ELEMENT"]["URL"]["BP"]);
$arResult["~ELEMENT"] = $arResult["ELEMENT"]; 
/********************************************************************
				/Data
********************************************************************/

/********************************************************************
				Data
********************************************************************/
$arResult["SECTION_LIST"] = $ob->GetSectionsTree(array("path" => "/"));
if ($arParams["USE_WORKFLOW"] == "Y")
{
	$db_res = CWorkflowStatus::GetDropDownList(($arParams["PERMISSION"] < "W" ? "N" : "Y"),  "desc");
	
	if ($db_res && $res = $db_res->Fetch())
	{
		do 
		{
			$arResult["WF_STATUSES"][intVal($res["REFERENCE_ID"])] = $res["REFERENCE"];
			$arResult["WF_STATUSES_PERMISSION"][intVal($res["REFERENCE_ID"])] = ($arParams["PERMISSION"] < "W" ? 
				CIBlockElement::WF_GetStatusPermission($res["REFERENCE_ID"]) : 2);
			if ($arResult["WF_STATUSES_PERMISSION"][intVal($res["REFERENCE_ID"])] == 2)
				$iEditStatus = intVal($res["REFERENCE_ID"]);
		}while ($res = $db_res->Fetch());
	}
}
/********************************************************************
				/Data
********************************************************************/

/********************************************************************
				Actions
********************************************************************/
if (strToUpper($_REQUEST["edit"]) == "Y" || strToUpper($_REQUEST["EDIT"]) == "Y")
{
	$result = include(str_replace(array("\\", "//"), "/", dirname(__FILE__)."/action.php"));
	if ($result !== true)
	{
		$bVarsFromForm = true;
		$e = new CAdminException($arError);
		$arResult["ERROR_MESSAGE"] = $e->GetString();
		
		foreach (array("ACTIVE", "NAME", "TAGS", "PREVIEW_TEXT", "WF_STATUS_ID", "WF_COMMENTS") as $key) 
		{
			$arResult["ELEMENT"]["~".$key] = $_REQUEST[$key];
			$arResult["ELEMENT"][$key] = htmlspecialcharsEx($_REQUEST[$key]);
		}
	}
}
/********************************************************************
				/Actions
********************************************************************/

$this->IncludeComponentTemplate();

/********************************************************************
				Standart operations
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(($arParams["ACTION"] == "CLONE" ? GetMessage("WD_TITLE_CLONE") : GetMessage("WD_TITLE")));
}
if ($arParams["SET_NAV_CHAIN"] == "Y")
{
	$res = array("section_id" => (!empty($arResult["ELEMENT_ORIGINAL"]) ? $arResult["ELEMENT_ORIGINAL"]["IBLOCK_SECTION_ID"] : $arResult["ELEMENT"]["IBLOCK_SECTION_ID"])); 
	$arResult["NAV_CHAIN"] = $ob->GetNavChain($res, "array");
	$arNavChain = array(); 
	foreach ($arResult["NAV_CHAIN"] as $res)
	{
		$arNavChain[] = $res["URL"];
		$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
			array(
				"PATH" => implode("/", $arNavChain), 
				"SECTION_ID" => $res["ID"], 
				"ELEMENT_ID" => "files", 
				"ELEMENT_NAME" => "files"));
		$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($res["NAME"]), $url);
	}
	if (!empty($arResult["ELEMENT_ORIGINAL"]))
	{
		$GLOBALS["APPLICATION"]->AddChainItem(GetMessage("WD_ORIGINAL").": ".htmlspecialcharsEx($arResult["ELEMENT_ORIGINAL"]["~NAME"]), 
			$arResult["ELEMENT"]["URL"]["VERSIONS"]);
	}
	$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($arResult["ELEMENT"]["~NAME"]));
}

if ($arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized())
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());
/********************************************************************
				/Standart operations
********************************************************************/
?>
