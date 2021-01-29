<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
endif;
CPageOption::SetOptionString("main", "nav_page_in_session", "N");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/pubstyles.css");

/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["RESOURCE_TYPE"] = "IBLOCK"; 
	if (!is_object($arParams["OBJECT"]))
	{
		$arParams["OBJECT"] = new CWebDavIblock($arParams['IBLOCK_ID'], $arParams['BASE_URL'], $arParams);
		$arParams["OBJECT"]->IsDir(); 
	}
	$ob = $arParams["OBJECT"]; 

	$arParams["IBLOCK_TYPE"] = $ob->IBLOCK_TYPE;
	$arParams["IBLOCK_ID"] = $ob->IBLOCK_ID;
	$arParams["ROOT_SECTION_ID"] = ($ob->arRootSection ? $ob->arRootSection["ID"] : false);
	$arParams["~SECTION_ID"] = $arParams["SECTION_ID"] = $ob->arParams["item_id"];
	$arParams["CHECK_CREATOR"] = ($arParams["OBJECT"]->check_creator ? "Y" : "N");
	$arParams["USE_COMMENTS"] = ($arParams["USE_COMMENTS"] == "Y" && IsModuleInstalled("forum") ? "Y" : "N");
	$arParams["NAME_FILE_PROPERTY"] = $ob->file_prop; 
	$arParams["FORUM_ID"] = intVal($arParams["FORUM_ID"]);
	$arParams["PERMISSION"] = $ob->permission;
	$arParams["SORT_BY"] = (!empty($arParams["SORT_BY"]) ? $arParams["SORT_BY"] : "NAME");
	$arParams["SORT_ORD"] = ($arParams["SORT_ORD"] != "DESC" ? "ASC" : "DESC");
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#",
		"section_edit" => "PAGE_NAME=section_edit&SECTION_ID=#SECTION_ID#&ACTION=#ACTION#",
		
		"element" => "PAGE_NAME=element&ELEMENT_ID=#ELEMENT_ID#",
		"element_edit" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#",
		"element_history" => "PAGE_NAME=element_history&ELEMENT_ID=#ELEMENT_ID#",
		"element_history_get" => "PAGE_NAME=element_history_get&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#",
		"element_version" => "PAGE_NAME=element_version&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#",
		"element_versions" => "PAGE_NAME=element_version&ELEMENT_ID=#ELEMENT_ID#",
		
		"help" => "PAGE_NAME=help",
		"user_view" => "PAGE_NAME=user_view&USER_ID=#USER_ID#", 
		
		"webdav_bizproc_view" => "PAGE_NAME=webdav_bizproc_view&ELEMENT_ID=#ELEMENT_ID#", 
		"webdav_start_bizproc" => "PAGE_NAME=webdav_start_bizproc&ELEMENT_ID=#ELEMENT_ID#", 
		"webdav_task_list" => "PAGE_NAME=webdav_task_list", 
		"webdav_task" => "PAGE_NAME=webdav_task&ID=#ID#");
	
	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $GLOBALS["APPLICATION"]->GetCurPageParam($URL_VALUE, array("PAGE_NAME", "PATH", 
				"SECTION_ID", "ELEMENT_ID", "ACTION", "AJAX_CALL", "USER_ID", "sessid", "save", "login", "edit", "action", "edit_section"));
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialchars($arParams["~".strToUpper($URL)."_URL"]);
	}
/***************** ADDITIONAL **************************************/
	$arParams["WORKFLOW"] = (!$ob->workflow ? "N" : $ob->workflow); 
	$arParams["DOCUMENT_ID"] = $arParams["DOCUMENT_TYPE"] = $arParams["OBJECT"]->wfParams["DOCUMENT_TYPE"];
	$arParams["COLUMNS"] = (is_array($arParams["COLUMNS"]) ? $arParams["COLUMNS"] : array("NAME", "TIMESTAMP_X", "USER_NAME", "FILE_SIZE"));
	$arParams["PAGE_ELEMENTS"] = intVal(intVal($arParams["PAGE_ELEMENTS"]) > 0 ? $arParams["PAGE_ELEMENTS"] : 50);
	$arParams["PAGE_NAVIGATION_TEMPLATE"] = trim($arParams["PAGE_NAVIGATION_TEMPLATE"]);
	$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
	$arParams["BASE_URL"] = $ob->base_url_full; 
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["STR_TITLE"] = trim($arParams["STR_TITLE"]);
	$arParams["DISPLAY_PANEL"] = ($arParams["DISPLAY_PANEL"]=="Y"); //Turn off by default
/********************************************************************
				/Input params
********************************************************************/

if ($arParams["PERMISSION"] < "R"):
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
endif;

/********************************************************************
				Default params
********************************************************************/
	$arResult["DATA"] = array();
	$arResult["GRID_DATA"] = array(); 
	$arResult["GRID_DATA_COUNT"] = 0; 
	$arResult["SECTION"] = $ob->arParams["dir_array"];
	$arResult["STATUSES"] = array();
	$arResult["ERROR_MESSAGE"] = "";
	$arResult["NAV_CHAIN"] = $ob->GetNavChain();
	$arResult["NAV_CHAIN_UTF8"] = $ob->GetNavChain(array("section_id" => $arParams["SECTION_ID"]), true);
	
	$arNavChain = $arResult["NAV_CHAIN"]; $sCurrenFolder = array_pop($arNavChain);
	$arResult["URL"] = array(
		"UP" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], array("PATH" => implode("/", $arNavChain))),
		"THIS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))),
		"~THIS" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))), 
		"HELP" => CComponentEngine::MakePathFromTemplate($arParams["HELP_URL"], array()));
	$arUsersCache = array();
	if ($arParams["PERMISSION"] > "U")
		$arResult["SECTION_LIST"] = $ob->GetSectionsTree(array("path" => "/")); 
	if (!empty($sCurrenFolder))
	{
		$arResult["GRID_DATA_COUNT"] = -1; 
		$arResult["GRID_DATA"][] = array(
			"id" => "", 
			"data" => array(), 
			"actions" => array(), 
			"columns" => array(
				"NAME" => '<div class="section-up"><div><a href="'.$arResult["URL"]["UP"].'"><span></span></a></div>'.
					'<a href="'.$arResult["URL"]["UP"].'">..</a></div>'), 
			"editable" => false);
	}
	
	$cache = new CPHPCache;
	$cache_path_main = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName."/".$arParams["IBLOCK_ID"]."/");
	
	$bShowSubscribe = false;
	$arParams["FORUM_CAN_VIEW"] = "N";
	$arResult["USER"] = array(
		"SHOW" => array(
			"SUBSCRIBE" => "N"), 
		"SUBSCRIBE" => array());
	$arResult["WF_STATUSES"] = array();
	$arResult["WF_STATUSES_PERMISSION"] = array();
	$arResult["ROOT_SECTION"] = $ob->arRootSection;
	$arParams["GRID_ID"] = "WebDAV".$arParams["IBLOCK_ID"]; 
	
	/* Quote from main.interface.grid */
	$aOptions = CUserOptions::GetOption("main.interface.grid", $arParams["GRID_ID"], array());
	/* ... */
	if(!is_array($aOptions["views"]))
		$aOptions["views"] = array();
	if(!array_key_exists("default", $aOptions["views"]))
		$aOptions["views"]["default"] = array("columns"=>"");
	if($aOptions["current_view"] == '' || !array_key_exists($aOptions["current_view"], $aOptions["views"]))
		$aOptions["current_view"] = "default";
	/* ... */
	$aCurView = $aOptions["views"][$aOptions["current_view"]];
	$aColumns = explode(",", $aCurView["columns"]);
	if (empty($aColumns))
		$aColumns = $arParams["COLUMNS"]; 
	global $by, $order;
	if (!$by)
	{
		$by = (!empty($aCurView["sort_by"]) ? $aCurView["sort_by"] : $arParams["SORT_BY"]);
		$order = (!empty($aCurView["sort_order"]) ? $aCurView["sort_order"] : $arParams["SORT_ORD"]);
	}
	InitSorting();
	
	/* /Quote from main.interface.grid */
/************** Workflow *******************************************/
if ($arParams["WORKFLOW"] == "workflow")
{
	$db_res = CWorkflowStatus::GetDropDownList("Y",  "desc");
	if ($db_res && $res = $db_res->Fetch())
	{
		do 
		{
			$arResult["WF_STATUSES"][$res["REFERENCE_ID"]] = $res["REFERENCE"];
			$arResult["WF_STATUSES_PERMISSION"][$res["REFERENCE_ID"]] = ($arParams["PERMISSION"] < "W" ? 
				CIBlockElement::WF_GetStatusPermission($res["REFERENCE_ID"]) : 2);
		}while ($res = $db_res->Fetch());
	}
	$arResult["STATUSES"] = $arResult["WF_STATUSES"];
}
elseif ($arParams["WORKFLOW"] == "bizproc")
{
	$arParams["BIZPROC_START"] = false;
	$arTemplates = array();
	if ($arParams["PERMISSION"] >= "U")
	{
		$cache_id = "bizproc_templates";
		$cache_path = $cache_path_main."bizproc";
		if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
		{
			$arTemplates = $cache->GetVars();
		}
		else
		{
			$db_res = CBPWorkflowTemplateLoader::GetList(
				array(),
				array("DOCUMENT_TYPE" => $arParams["DOCUMENT_TYPE"]),
				false,
				false,
				array("ID", "AUTO_EXECUTE", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "PARAMETERS", "TEMPLATE")
			);
			while ($arWorkflowTemplate = $db_res->GetNext())
			{
				$arTemplates[$arWorkflowTemplate["ID"]] = $arWorkflowTemplate;
			}
			if ($arParams["CACHE_TIME"] > 0):
				$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
				$cache->EndDataCache($arTemplates);
			endif;
		}
	}
	$arParams["TEMPLATES"] = $arTemplates;
}
/************** Columns ********************************************/
if ($arParams["WORKFLOW"] == "bizproc_limited")
	$arParams["COLUMNS"][] = "BP_PUBLISHED";
elseif ($arParams["WORKFLOW"] == "bizproc")
	$arParams["COLUMNS"] = array_merge($arParams["COLUMNS"], array("BP_PUBLISHED", "BIZPROC", "VERSIONS"));
else
	$arParams["COLUMNS"] = array_diff($arParams["COLUMNS"], array("BP_PUBLISHED", "BIZPROC", "VERSIONS")); 
if ($arParams["SHOW_WORKFLOW"] != "Y" || $arParams["WORKFLOW"] != "workflow")
	$arParams["COLUMNS"] = array_diff($arParams["COLUMNS"], array("WF_STATUS_ID", "WF_NEW", "WF_COMMENTS"));  
if ($arParams["PERMISSION"] < "U")
	$arParams["COLUMNS"] = array_diff($arParams["COLUMNS"], array("ACTIVE", "GLOBAL_ACTIVE", "SORT", "CODE", "EXTERNAL_ID", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO")); 
$arParams["COLUMNS"] = array_unique($arParams["COLUMNS"]); 
/********************************************************************
				/Default params
********************************************************************/

/********************************************************************
				ACTIONS
********************************************************************/
$GLOBALS["APPLICATION"]->ResetException();
$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/action.php");
$result = include($path);
if ($result !== true):
	$oError = $GLOBALS["APPLICATION"]->GetException();
	if ($oError):
		$arResult["ERROR_MESSAGE"] = $oError->GetString();
	endif;
endif;
/********************************************************************
				/ACTIONS
********************************************************************/

/********************************************************************
				Data
********************************************************************/
/************** Forum subscribe ************************************/
if ($arParams["USE_COMMENTS"] == "Y" && CModule::IncludeModule("forum"))
{
	$arParams["USE_COMMENTS"] = $arParams["FORUM_CAN_VIEW"] = (CForumNew::CanUserViewForum($arParams["FORUM_ID"], $GLOBALS['USER']->GetUserGroupArray()) ? "Y" : "N");
	if ($arParams["FORUM_CAN_VIEW"] == "Y" && $GLOBALS['USER']->IsAuthorized())
	{
		$bShowSubscribe = true;
		$arUserSubscribe = array();
		$cache_id = "forum_user_subscribe_".intVal($GLOBALS["USER"]->GetID())."_".$arParams["FORUM_ID"];
		$cache_path = $cache_path_main."forum_subscribe_".$GLOBALS["USER"]->GetID();
		
		if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
		{
			$res = $cache->GetVars();
			$arUserSubscribe = $res["arUserSubscribe"];
		}
		else
		{
			$db_res = CForumSubscribe::GetList(array(), array("USER_ID" => $GLOBALS["USER"]->GetID(), "FORUM_ID" => $arParams["FORUM_ID"]));
			$arUserSubscribe = array();
			if ($db_res && $res = $db_res->Fetch())
			{
				do
				{
					$arUserSubscribe[] = $res;
				} while ($res = $db_res->Fetch());
			}
			
			$arUserSubscribe = array(
				"USER_ID" => intVal($GLOBALS["USER"]->GetID()), 
				"DATA" => $arUserSubscribe);
			
			if ($arParams["CACHE_TIME"] > 0):
				$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
				$cache->EndDataCache(array("arUserSubscribe" => $arUserSubscribe));
			endif;
		}
		$arResult["USER"]["SUBSCRIBE"] = $arUserSubscribe["DATA"];
		if (is_array($arResult["USER"]["SUBSCRIBE"]))
		{
			$arTmp = array("FORUM" => "N", "TOPIC" => "N", "TOPICS" => array());
			foreach ($arResult["USER"]["SUBSCRIBE"] as $res)
			{
				if (intVal($res["FORUM_ID"]) > 0 && intVal($res["TOPIC_ID"]) <= 0)
				{
					$arTmp["FORUM"] = "Y";
					$bShowSubscribe = false;
				}
				else 
				{
					$arTmp["TOPIC"] = "Y";
					$arTmp["TOPICS"][$res["TOPIC_ID"]] = $res;
				}
			}
			$arResult["USER"]["SUBSCRIBE"] += $arTmp;
		}
		$arResult["USER"]["SHOW"]["SUBSCRIBE"] = ($bShowSubscribe ? "Y" : "N");
	}
}
/************** Data ***********************************************/
//if (empty($arResult["DATA"]))
{
	$arSelectedFields = array_merge($arParams["COLUMNS"], $aColumns); 
	$arSelectedProperties = array();
	if ($arParams["USE_COMMENTS"] == "Y")
	{
		$arSelectedFields[] = "PROPERTY_FORUM_MESSAGE_CNT";
		$arSelectedFields[] = "PROPERTY_FORUM_TOPIC_ID";
	}
	
	foreach ($arSelectedFields as $res)
	{
 		if (substr($res, 0, 9) == "PROPERTY_")
			$arSelectedProperties[] = $res;
	}
	
	$options = array("path" => $ob->_path, "depth" => 1); 
	$res = $ob->PROPFIND($options, $files, array("COLUMNS" => $arSelectedFields, "return" => "nav_result", "get_clones" => "Y")); 
	$arResult["SECTION"] = $res["SECTION"]; 
	$arResult["NAV_RESULT"] = $res["NAV_RESULT"];

	if ($arResult["NAV_RESULT"])
	{
		if ($arParams["PAGE_ELEMENTS"] > 0)
		{
			$arResult["NAV_RESULT"]->NavStart($arParams["PAGE_ELEMENTS"], false); 
			$arResult["NAV_STRING"] = $arResult["NAV_RESULT"]->GetPageNavStringEx($navComponentObject, GetMessage("WD_DOCUMENTS"), $arParams["PAGE_NAVIGATION_TEMPLATE"], true);
		}
		$sTaskUrl = "";
		while ($res = $arResult["NAV_RESULT"]->GetNext())
		{
			if ($res["TYPE"] == "S")
			{
				$ob->_get_section_info_arr($res); 
				if (in_array("SECTION_CNT", $aColumns) || in_array("SECTIONS_CNT", $aColumns))
					$res["SECTION_CNT"] = $res["SECTIONS_CNT"] = intVal(
						CIBlockSection::GetCount(array(
							"IBLOCK_ID"=>$arParams["IBLOCK_ID"],
							"SECTION_ID"=>$res["ID"])));
				if (in_array("ELEMENT_CNT", $aColumns) || in_array("ELEMENTS_CNT", $aColumns))
					$res["ELEMENT_CNT"] = $res["ELEMENTS_CNT"] = intVal(
						CIBlockSection::GetSectionElementsCount(
							$res["ID"], Array("CNT_ALL"=>"Y")));
			}
			else
			{
				$ob->_get_file_info_arr($res, array("get_clones" => "Y")); 
			}
			
			$res["~PATH"] = $res["PATH"]; 
			$res["PATH"] = $ob->_uencode($res["~PATH"], array("utf8" => "Y", "convert" => $arParams["CONVERT"])); 
			
/*********************** Name **************************************/
			$res["NAME"] = WrapLongWords($res["NAME"]);
/*********************** Path **************************************/
			__prepare_item_info($res, $arParams); 
/*********************** Actions ***********************************/
			// Subscribe
			if ($res["TYPE"] != "S" && $res["SHOW"]["SUBSCRIBE"] == "Y")
				$res["SUBSCRIBE"] = (!empty($arResult["USER"]["SUBSCRIBE"]["TOPICS"][$res["PROPERTY_FORUM_TOPIC_ID_VALUE"]]) ? "N" : "Y");

			$arResult["DATA"][$res["ID"]] = $res;
/************** Grid Data ******************************************/
			$rs = __build_item_info($res, $arParams); 
			$arActions = $rs["actions"];
			$aCols = $rs["columns"]; 
			$aCols["USER_NAME"] = $aCols["MODIFIED_BY"]; 
			$aCols["CREATED_USER_NAME"] = $aCols["CREATED_BY"]; 
			
			if ($res["TYPE"] == "E")
			{
				foreach ($res as $key => $val)
				{
					if (substr($key, 0, 9) == "PROPERTY_" && substr($key, -6, 6) == "_VALUE")
					{
						$tmp = substr($key, 0, strlen($key) - 6); 
						$res[$tmp] = $val; 
					}
				}
				$aCols["LOCKED_USER_NAME"] = $aCols["WF_LOCKED_BY"]; 
				if ($res["SHOW"]["SUBSCRIBE"] == "Y")
				{
					if ($res["SUBSCRIBE"] == "Y")
						$arActions["element_subscribe"] = array(
							"ICONCLASS" => "element_subscribe",
							"TITLE" => GetMessage("WD_SUBSCRIBE_ELEMENT"),
							"TEXT" => GetMessage("WD_SUBSCRIBE"),
							"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~SUBSCRIBE"])."');"); 
					else
						$arActions["element_subscribe"] = array(
							"ICONCLASS" => "element_unsubscribe",
							"TITLE" => GetMessage("WD_UNSUBSCRIBE_ELEMENT"),
							"TEXT" => GetMessage("WD_UNSUBSCRIBE"),
							"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~UNSUBSCRIBE"])."');"); 
				}
				
				$aCols["WF_STATUS_ID"] = $arResult["STATUSES"][$res['WF_STATUS_ID']]; 
				$aCols["LOCK_STATUS"] = '<div class="element-lamp-'.$res["LOCK_STATUS"].'" title="'.(
					$res["LOCK_STATUS"] == "green" ? GetMessage("IBLOCK_GREEN_ALT") : 
						($res["LOCK_STATUS"] == "yellow" ? GetMessage("IBLOCK_YELLOW_ALT") : GetMessage("IBLOCK_RED_ALT"))).'"></div>'. 
					(($res['LOCK_STATUS']=='red' && $res['LOCKED_USER_NAME']!='') ? $aCols['LOCKED_USER_NAME'] : ''); 
				
				$aCols["BP_VERSIONS"] = ""; $arChildren = array(); 
				if ($arParams["WORKFLOW"] == "bizproc" && !empty($res["CHILDREN"]))
				{
					foreach ($res["CHILDREN"] as $k => $rs) 
					{
						$arBProcesses = $arFlags = array(); 
						if (is_array($rs["arDocumentStates"]) && !empty($rs["arDocumentStates"]))
						{
							foreach ($rs["arDocumentStates"] as $key => $arDocumentState)
							{
								if (!(strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0))
									continue; 
								$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]->GetID(), $arDocumentState["ID"]);
								$bTasks = !empty($arTasksWorkflow); 
								$arFlags["tasks"] = ($arFlags["tasks"] == true ? true : $bTasks); 
								$arFlags["inprogress"]++; 
								$arBProcesses[] = 
									'<div class="bizproc-item-title">'. 
										'<div class="bizproc-statuses bizproc-status-'.($bTasks ? "attention" : "inprogress").'"></div>'. 
										(!empty($arDocumentState["TEMPLATE_NAME"]) ? $arDocumentState["TEMPLATE_NAME"] : GetMessage("IBLIST_BP")).': '.
										'<span class="bizproc-item-title bizproc-state-title" style="margin-left:1em;">'. 
											'<a href="'.CComponentEngine::MakePathFromTemplate(
												$arParams["~WEBDAV_BIZPROC_VIEW_URL"], 
												array("ELEMENT_ID" => $rs["ID"])).'">'.
												($arDocumentState["STATE_TITLE"] ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"]).
											'</a>'. 
										'</span>'.
									'</div>';
							}
						}
						foreach (array("MODIFIED_BY"/*, "CREATED_BY", "WF_LOCKED_BY"*/) as $user_key)
						{
							$rs[$user_key] = (is_array($rs[$user_key]) ? $rs[$user_key] : __parse_user($rs[$user_key], $arParams["USER_VIEW_URL"])); 
							$rs[$user_key] = $rs[$user_key]["LINK"]; 
						}
						$tmp = 	'<div class="bizproc-item-title">'. 
									'<div class="bizproc-statuses"></div>'. 
									'<span class="bizproc-item-title bizproc-state-title">'. 
										'<a href="'.CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSION_URL"], 
											array("ELEMENT_ID" => $rs["ID"], "ACTION" => "EDIT")).'">'.
												$rs["NAME"].'</a> ('.$rs["MODIFIED_BY"].')'.
									'</span>'.
								'</div>'; 
						if (!empty($arBProcesses))
						{
							$tmp = 	
								'<div class="bizproc-item-title">'. 
									'<div class="bizproc-statuses bizproc-status-'.($arFlags["tasks"] ? "attention" : "inprogress").'"></div>'. 
									'<span class="bizproc-item-title bizproc-state-title">'. 
										'<a href="'.CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSION_URL"], 
											array("ELEMENT_ID" => $rs["ID"], "ACTION" => "EDIT")).'">'.$rs["NAME"].'</a> ('.$rs["MODIFIED_BY"].')'.
									'</span>'.
									ShowJSHint('<ol class="bizproc-items"><li>'.implode("</li><li>", $arBProcesses).'</li></ol>', array('return' => true)).
								'</div>'; 
						}
						$arChildren[$k] = $tmp; 
					}
				}
				if (!empty($arChildren))
				{
					$aCols["BP_VERSIONS"] = '<ol class="bizproc-childs"><li>'.implode('</li><li>', $arChildren).'</li></ol>'; 
				}
				else 
				{
					unset($arActions["element_versions"]); 
				}
				
			}

			$editable = ($res["TYPE"] == "S" && !empty($arActions)) || ($res["TYPE"] == "E" && $res["SHOW"]["EDIT"] == "Y"); 

			$arResult["GRID_DATA"][] = array(
				"id" => $res["TYPE"].$res["ID"], 
				"data" => $res, 
				"actions" => array_values($arActions), 
				"columns" => $aCols, 
				"editable" => $editable);
/************** Grid Data/******************************************/
		}
	}
/*	if ($arParams["CACHE_TIME"] > 0)
	{
		$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
		$cache->EndDataCache(
			array(
				"DATA" => $arResult["DATA"],
				"PERMISSION" => $arParams["PERMISSION"], 
				"NAV_CHAIN" => $arResult["NAV_CHAIN"]));
	}
*/
	$arResult["GRID_DATA_COUNT"] += count($arResult["GRID_DATA"]); 
}
/*************** Users *********************************************/
$arResult["USERS"] = $arUsersCache;
/*************** For custom Templates ******************************/

/********************************************************************
				/Data
********************************************************************/

$this->IncludeComponentTemplate();

/********************************************************************
				Standart operations
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$title = (empty($arParams["STR_TITLE"]) ? GetMessage("WD_TITLE") : $arParams["STR_TITLE"]);
	$GLOBALS["APPLICATION"]->SetTitle(empty($sCurrenFolder) ? $title : $sCurrenFolder);
}

if ($arParams["SET_NAV_CHAIN"] == "Y" && !empty($sCurrenFolder))
{
	$res = array(); 
	foreach ($arNavChain as $name)
	{
		$res[] = $name; 
		$GLOBALS["APPLICATION"]->AddChainItem($name, 
			CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $res))));
	}
	$GLOBALS["APPLICATION"]->AddChainItem($sCurrenFolder);
}

if ($arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized())
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());
/********************************************************************
				/Standart operations
********************************************************************/
?>