<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/functions.php")));
__IncludeLang($file);
if (!function_exists("WrapLongWords"))
{
	function WrapLongWords($text = "")
	{
		if (strLen($text) <= 40)
			return $text;
		$word_separator = "\s.,;:!?\#\*\|\[\]\(\)";
		$text = preg_replace("/(?<=[".$word_separator."])(([^".$word_separator."]+))(?=[".$word_separator."])/ise".BX_UTF_PCRE_MODIFIER, 
			"Wrap('\\2')", " ".$text." ");
		return trim($text);
	}
}
if (!function_exists("Wrap"))
{
	function Wrap($str)
	{
		$str = preg_replace("/([^ \n\r\t\x01]{40})(.?+)/is".BX_UTF_PCRE_MODIFIER,"\\1<WBR />&shy;\\2", $str);
		return $str;
	}
}
if (!function_exists("__get_file_array"))
{
	function __get_file_array($id, &$res)
	{
		static $arFilesCache = array(); 
		if (!array_key_exists($id, $arFilesCache))
		{
			$db_res = CFile::GetByID($id);
			$arFilesCache[$id] = $db_res->GetNext(); 
			$arFilesCache[$id]["FILE_ARRAY"] = array(); 
			__parse_file_size($arFilesCache[$id]["FILE_ARRAY"], $arFilesCache[$id]); 
		}
		if (!array_key_exists($id, $arFilesCache))
		{
			$res = $arFilesCache[$id];
			return true; 
		}
		
		return false; 
	}
}
if (!function_exists("__parse_file_size"))
{
	function __parse_file_size(&$res_file, &$res)
	{
		$res_file = is_array($res_file) ? $res_file : array(); 
		$res_file["FILE_SIZE_B"] = $res_file["FILE_SIZE"];
		$res_file["FILE_SIZE_KB"] = round($res_file["FILE_SIZE"]/1024);
		$res_file["FILE_SIZE_MB"] = round($res_file["FILE_SIZE"]/1048576);
		$res["FILE_SIZE"] = $res_file["FILE_SIZE_KB"].GetMessage("WD_KB");
		if ($res_file["FILE_SIZE_KB"] < 1 )
			$res["FILE_SIZE"] = $res_file["FILE_SIZE_B"].GetMessage("WD_B");
		elseif ($res_file["FILE_SIZE_MB"] >= 1 )
			$res["FILE_SIZE"] = $res_file["FILE_SIZE_MB"].GetMessage("WD_MB");
	}
}
if (!function_exists("__parse_user"))
{
	function __parse_user($user_id, $user_url)
	{
		static $arUsersCache = array(); 
		
		if (intVal($user_id) > 0 && !array_key_exists($user_id, $arUsersCache))
		{
			if ($GLOBALS["USER"]->GetID() == $user_id)
			{
				$arUsersCache[$user_id] = array(
					"ID" => $user_id, 
					"NAME" => $GLOBALS["USER"]->GetFirstName(), 
					"LAST_NAME" => $GLOBALS["USER"]->GetLastName(), 
					"LOGIN" => $GLOBALS["USER"]->GetLogin()); 
			}
			else
			{
				$rsUser = CUser::GetByID($user_id);
				$arUsersCache[$user_id] = $rsUser->Fetch();
			}
			$arUsersCache[$user_id]["URL"] = CComponentEngine::MakePathFromTemplate($user_url, array("USER_ID" => $user_id)); 
			$arUsersCache[$user_id]["FULL_NAME"] = trim($arUsersCache[$user_id]["NAME"]." ".$arUsersCache[$user_id]["LAST_NAME"]); 
			if (empty($arUsersCache[$user_id]["FULL_NAME"]))
				$arUsersCache[$user_id]["FULL_NAME"] = $arUsersCache[$user_id]["LOGIN"];
            $arUsersCache[$user_id]["FULL_NAME"]=htmlspecialchars($arUsersCache[$user_id]["FULL_NAME"]);
			$arUsersCache[$user_id]["LINK"] = '<a href="'.$arUsersCache[$user_id]["URL"].'">'.$arUsersCache[$user_id]["FULL_NAME"].'</a>'; 
		}
		if (!empty($arUsersCache[$user_id]))
			return $arUsersCache[$user_id]; 
		return array("ID" => 0, "NAME" => "Guest", "LINK" => "Guest"); 
	}
}
if (!function_exists("__prepare_item_info"))
{
	function __prepare_item_info(&$res, $arParams = array())
	{
		if ($res["TYPE"] == "S")
		{
			if (in_array("SECTION_CNT", $arParams["COLUMNS"]) || in_array("SECTIONS_CNT", $arParams["COLUMNS"]))
				$res["SECTION_CNT"] = $res["SECTIONS_CNT"] = intVal(
					CIBlockSection::GetCount(array(
						"IBLOCK_ID"=>$arParams["IBLOCK_ID"],
						"SECTION_ID"=>$res["ID"])));
			if (in_array("ELEMENT_CNT", $arParams["COLUMNS"]) || in_array("ELEMENTS_CNT", $arParams["COLUMNS"]))
				$res["ELEMENT_CNT"] = $res["ELEMENTS_CNT"] = intVal(
					CIBlockSection::GetSectionElementsCount(
						$res["ID"], Array("CNT_ALL"=>"Y")));
		}
		else
		{
			if (!is_set($res, "FILE_ARRAY") && is_set($res, "FILE"))
				$res["FILE_ARRAY"] = $res["FILE"];
			__parse_file_size($res["FILE_ARRAY"], $res); 
			$res["PROPERTIES"] = array(); 
			$tmp = array(); 
			foreach ($res as $key => $val)
			{
				if (substr($key, -9, 9) != "_VALUE_ID" || 
					!(substr($key, 0, 9) == "PROPERTY_" || substr($key, 0, 10) == "~PROPERTY_"))
					continue; 
				$key = substr($key, 0, strlen($key) - 9); 
				$tmp[$key] = $res[$key."_VALUE"]; 
		 		if (substr($key, 0, 9) == "PROPERTY_")
		 			$res["PROPERTIES"][substr($key, 9)] = array("VALUE" => $res[$key."_VALUE"]); 
			}
			$res["FILE_EXTENTION"] = htmlspecialchars(strtolower(strrchr($res['~NAME'] , '.')));
		}
		
		foreach (array("MODIFIED_BY", "CREATED_BY", "WF_LOCKED_BY") as $user_key)
		{
			if (!array_key_exists("~".$user_key, $res))
				$res["~".$user_key] = $res[$user_key]; 
			$res[$user_key] = __parse_user($res[$user_key], $arParams["USER_VIEW_URL"]); 
		}

		if ($res["TYPE"] == "S")
		{
			$res["URL"] = array(
				"~THIS" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
					array("PATH" => $res["~PATH"], "SECTION_ID" => $res["ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")), 
				"THIS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], 
					array("PATH" => $res["PATH"], "SECTION_ID" => $res["ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")));
			$res["URL"]["EDIT"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], 
				array("PATH" => $res["PATH"], "SECTION_ID" => $res["ID"], "ACTION" => "EDIT"));
			$res["URL"]["DELETE"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], 
				array("PATH" => $res["PATH"], "SECTION_ID" => $res["ID"], "ACTION" => "DROP"));
			$res["URL"]["DELETE"] = WDAddPageParams($res["URL"]["DELETE"], array("edit_section" => "y", "sessid" => bitrix_sessid()), false);
		}
		else
		{
			$res["URL"] = array(
				"~THIS" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
					array("PATH" => $res["PATH"], "SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), "ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["~NAME"])), 
				"THIS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], 
					array("PATH" => $res["PATH"], "SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), "ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["~NAME"])), 
				"~SECTION" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
					array("PATH" => $res["SECTION_PATH"], "SECTION_ID" => $res["IBLOCK_SECTION_ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")), 
				"SECTION" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], 
					array("PATH" => $res["SECTION_PATH"], "SECTION_ID" => $res["IBLOCK_SECTION_ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")), 
				"VIEW" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_URL"], 
					array("PATH" => $res["PATH"], "SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), "ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["~NAME"])), 
				"HIST" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_HISTORY_URL"], 
					array("PATH" => $res["PATH"], "SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), "ELEMENT_ID" => $res["ID"])), 
				"DOWNLOAD" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_HISTORY_GET_URL"], 
					array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ID" => $res["ID"], "ELEMENT_NAME" => $res["~NAME"])), 
				"VERSIONS" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"], 
					array("ELEMENT_ID" => ($res["WF_PARENT_ELEMENT_ID"] ? $res["WF_PARENT_ELEMENT_ID"] : $res["ID"]))), 
				"EDIT" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
					array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "EDIT")), 
				"CLONE" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSION_URL"], 
					array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "CLONE")), 
				"COPY" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
					array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "COPY")), 
				"DELETE" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
					array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "DELETE")), 
				"UNLOCK" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
					array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "UNLOCK")), 
				"LOCK" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
					array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "LOCK")), 
				"BP" => CComponentEngine::MakePathFromTemplate($arParams["~WEBDAV_BIZPROC_VIEW_URL"], 
					array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"])), 
				"BP_START" => CComponentEngine::MakePathFromTemplate($arParams["~WEBDAV_START_BIZPROC_URL"], 
					array("ELEMENT_ID" => $res["ID"])), 
				"BP_TASK" => CComponentEngine::MakePathFromTemplate($arParams["~WEBDAV_TASK_LIST_URL"], 
					array("PATH" => $res["PATH"],"ELEMENT_ID" => $res["ID"])));
			$res["URL"]["DELETE"] = WDAddPageParams($res["URL"]["DELETE"], array("edit" => "y", "sessid" => bitrix_sessid()), false);
			$res["URL"]["UNLOCK"] = WDAddPageParams($res["URL"]["UNLOCK"], array("edit" => "y", "sessid" => bitrix_sessid()), false);
			$res["URL"]["LOCK"] = WDAddPageParams($res["URL"]["LOCK"], array("edit" => "y", "sessid" => bitrix_sessid()), false);
			$res["URL"]["BP_START"] = WDAddPageParams($res["URL"]["BP_START"], 
				array("back_url" => CWebDavBase::_uencode($GLOBALS['APPLICATION']->GetCurPageParam(), array("convert" => "full"))), false);
			if ($res["WF_PARENT_ELEMENT_ID"] > 0)
			{
				$res["URL"]["~PARENT"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
					array(
						"PATH" => $res["PATH"], 
						"SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), 
						"ELEMENT_ID" => $res["WF_PARENT_ELEMENT_ID"], 
						"ELEMENT_NAME" => $res["~NAME"])); 
				$res["URL"]["PARENT"] = CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], 
					array(
						"PATH" => $res["PATH"], 
						"SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), 
						"ELEMENT_ID" => $res["WF_PARENT_ELEMENT_ID"], 
						"ELEMENT_NAME" => $res["~NAME"])); 
			}
		}
		foreach ($res["URL"] as $key => $val)
		{
			$res["URL"][$key] = $val = str_replace(array("\\", "///", "//"), "/", $val);
			if (substr($key, 0, 1) == "~" || array_key_exists("~".$key, $res["URL"]))
				continue; 
			$res["URL"]["~".$key] = $val;
			$res["URL"][$key] = htmlspecialchars($val);
		}
	}
}

if (!function_exists("__build_item_info"))
{
	function __build_item_info(&$res, $arParams)
	{
		static $bTheFirstTimeonPage = true; 
		static $bShowWebdav = true; 
		static $arBPTemplates = array(); 
		if ($res["TYPE"] != "S" && $arBPTemplates != $arParams["TEMPLATES"])
		{
			$bShowWebdav = true; 
			$arBPTemplates = $arParams["TEMPLATES"]; 
			if (is_array($arParams["TEMPLATES"]) && !empty($arParams["TEMPLATES"]))
			{
				foreach ($arParams["TEMPLATES"] as $key => $arTemplateState)
				{
					if (in_array($arTemplateState["AUTO_EXECUTE"], array(2, 3, 6, 7)) && 
						(is_array($arTemplateState["PARAMETERS"]) || is_array($arTemplateState["TEMPLATE_PARAMETERS"])))
					{
						$arTemplateState["TEMPLATE_PARAMETERS"] = (is_array($arTemplateState["PARAMETERS"]) ? 
							$arTemplateState["PARAMETERS"] : $arTemplateState["TEMPLATE_PARAMETERS"]); 
						foreach ($arTemplateState["TEMPLATE_PARAMETERS"] as $val)
						{
							if ($val["Required"] == 1 && empty($val["Default"]))
							{
								$bShowWebdav = false;
								break;
							}
						}
					}
				}
			}
		}
		
/************** Grid Data ******************************************/
		$arActions = array();
		if ($res["TYPE"] == "S")
		{
			if ($res["SHOW"]["EDIT"] == "Y")
			{
				$arActions["section_edit"] = array(
					"ICONCLASS" => "section_edit",
					"TITLE" => GetMessage("WD_CHANGE_SECTION"),
					"TEXT" => GetMessage("WD_CHANGE"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~EDIT"])."');", 
					"DEFAULT" => true);
			}
			if ($res["SHOW"]["DELETE"] == "Y")
			{
				$arActions["section_drop"] = array(
					"ICONCLASS" => "section_drop",
					"TITLE" => GetMessage("WD_DELETE_SECTION"),
					"TEXT" => GetMessage("WD_DELETE"),
					"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage("WD_DELETE_SECTION_CONFIRM"))."')){jsUtils.Redirect([], '".
						CUtil::JSEscape($res["URL"]["~DELETE"])."')};");
			}
		}
		else
		{
			$arActions["element_download"] = array(
				"ICONCLASS" => "element_download",
				"TITLE" => GetMessage("WD_DOWNLOAD_ELEMENT"),
				"TEXT" => GetMessage("WD_DOWNLOAD"),
				"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DOWNLOAD"])."');", 
				"DEFAULT" => true); 
			if ($res["WF_PARENT_ELEMENT_ID"] <= 0 && $res["~TYPE"] != "FILE")
			{
				$arActions["element_view"] = array(
					"ICONCLASS" => "element_view",
					"TITLE" => GetMessage("WD_VIEW_ELEMENT"),
					"TEXT" => GetMessage("WD_VIEW"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VIEW"])."');"); 
			}
	
			if ($arParams["PERMISSION"] >= "U")
			{
				$arActionsTmp = array(); 
				
				if ($res["SHOW"]["UNLOCK"] == "Y")
				{
					$arActionsTmp["element_unlock"] = array(
						"ICONCLASS" => "element_unlock",
						"TITLE" => GetMessage("WD_UNLOCK_ELEMENT"),
						"TEXT" => GetMessage("WD_UNLOCK"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~UNLOCK"])."');");
				}
				
				if ($res["SHOW"]["BP_VIEW"] == "Y")
				{
					$arActionsTmp["bizproc_document"] = array(
						"ICONCLASS" => "bizproc_document",
						"TITLE" => GetMessage("IBLIST_A_BP_H"),
						"TEXT" => GetMessage("IBLIST_A_BP_H"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~BP"])."');");
				}
				if ($res["SHOW"]["BP_START"] == "Y" && is_array($arParams["TEMPLATES"]))
				{
					$arr = array();
					foreach ($arParams["TEMPLATES"] as $key => $arWorkflowTemplate)
					{
						if (!CBPDocument::CanUserOperateDocument(
							CBPCanUserOperateOperation::StartWorkflow,
							$GLOBALS["USER"]->GetID(),
							$res["DOCUMENT_ID"],
							array(
								"UserGroups" => $res["USER_GROUPS"], 
								"DocumentStates" => $res["~arDocumentStates"], 
								"WorkflowTemplateList" => $arTemplates, 
								"WorkflowTemplateId" => $arWorkflowTemplate["ID"]))):
							continue;
						endif;
						$url = $res["URL"]["~BP_START"];
						$url .= (strpos($url, "?") === false ? "?" : "&")."workflow_template_id=".$arWorkflowTemplate["ID"].'&'.bitrix_sessid_get();
						$arr[] = array(
							"ICONCLASS" => "",
							"TITLE" => $arWorkflowTemplate["DESCRIPTION"],
							"TEXT" => $arWorkflowTemplate["NAME"],
							"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($url)."');");
					}
					if (!empty($arr))
					{
						$arActionsTmp["bizproc_start"] = array(
							"ICONCLASS" => "bizproc_start",
							"TITLE" => GetMessage("WD_START_BP_TITLE"),
							"TEXT" => GetMessage("WD_START_BP"),
							"MENU" => $arr);
					}
				}
				
				if ($res["SHOW"]["HISTORY"] == "Y")
				{
					$arActionsTmp["element_history"] = array(
						"ICONCLASS" => "element_history".($res["SHOW"]["BP"] == "Y" ? " bizproc_history" : ""),
						"TITLE" => GetMessage("WD_HIST_ELEMENT_ALT"),
						"TEXT" => GetMessage("WD_HIST_ELEMENT"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~HIST"])."');");
				}
				
				if ($res["SHOW"]["BP_CLONE"] == "Y")
				{
					$arActionsTmp["element_clone"] = array(
						"ICONCLASS" => "bizproc_document",
						"TITLE" => GetMessage("WD_CREATE_VERSION_ALT"),
						"TEXT" => GetMessage("WD_CREATE_VERSION"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~CLONE"])."');");
					$arActionsTmp["element_versions"] = array(
						"ICONCLASS" => "bizproc_document",
						"TITLE" => GetMessage("WD_VERSIONS_ALT"),
						"TEXT" => GetMessage("WD_VERSIONS"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VERSIONS"])."');");
				}
				
				if (!empty($arActionsTmp))
					$arActions += (array("separator_bp" => array("SEPARATOR" => true)) + $arActionsTmp);
				
				
				if ($res["SHOW"]["EDIT"] == "Y")
				{
					$arActions["separator_edit"] = array("SEPARATOR"=>true);
					$arActions["element_edit"] = array(
						"ICONCLASS" => "element_edit",
						"TITLE" => GetMessage("WD_CHANGE_ELEMENT"),
						"TEXT" => GetMessage("WD_CHANGE"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~EDIT"])."');");
					if ($res["SHOW"]["DELETE"] == "Y")
					{
						$arActions["element_delete"] = array(
							"ICONCLASS" => "element_delete",
							"TITLE" => GetMessage("WD_DELETE_ELEMENT"),
							"TEXT" => GetMessage("WD_DELETE"),
							"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRM"))."')){jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')};");
					}
				}
			}
		}

		foreach (array("MODIFIED_BY", "CREATED_BY", "WF_LOCKED_BY") as $user_key)
		{
			$aCols[$user_key] = (is_array($res[$user_key]) ? $res[$user_key] : __parse_user($res[$user_key], $arParams["USER_VIEW_URL"])); 
			$aCols[$user_key] = $aCols[$user_key]["LINK"]; 
		}
			
		if ($res["TYPE"] == "S")
		{
			$aCols["NAME"] = '<div class="section-name"><div class="section-icon"></div><a href="'.$res["URL"]["THIS"].'">'.$res["NAME"].'</a></div>'; 
		}
		else
		{
			$aCols["NAME"] = 
				'<div class="element-name">'.
					'<div class="element-icon ic'.substr($res["FILE_EXTENTION"], 1).'"></div>'.
					($arParams["PERMISSION"] >= "U" && in_array($res['LOCK_STATUS'], array("red", "yellow")) ? 
						('<div class="element-icon element-lamp-'.$res['LOCK_STATUS'].'" title="'.
							($res['LOCK_STATUS'] == "yellow" ? GetMessage("IBLOCK_YELLOW_ALT") : GetMessage("IBLOCK_RED_ALT")).
							($res['LOCK_STATUS'] == "red" && !empty($res['LOCKED_USER_NAME']) ? " ".$res['LOCKED_USER_NAME'] : ""). 
						'"></div>') : ""
					).
					'<a href="'.htmlspecialchars($res["URL"]["THIS"]).'" target="_blank"'.(!empty($res["PREVIEW_TEXT"]) ? ' title="'.GetMessage("WD_DOWNLOAD_ELEMENT").'"' : '').'>'.
						$res["NAME"].
					'</a> '. 
				'</div>'; 
			
			if ($bShowWebdav && $res["SHOW"]["EDIT"] == "Y" && in_array($res["FILE_EXTENTION"], array(".doc", ".docx", ".xls", ".xlsx", ".rtf", ".ppt", ".pptx"))): 
					$aCols["NAME"] .= '<a href="'.$res["URL"]["THIS"].'" class="element-properties element-edit-office"'.
						' onclick="return EditDocWithProgID(\''.CUtil::addslashes($res["URL"]["~THIS"]).'\');" title="'.GetMessage("WD_EDIT_MSOFFICE").'"'.
					'><span></span></a>'; 
			endif; 
			
			if ($arParams["USE_COMMENTS"] == "Y" && intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]) > 0): 
				$aCols["NAME"] .= '<a href="'.$res["URL"]["VIEW"].'" class="element-properties element-comments" title="'.
					GetMessage("WD_COMMENTS_FOR_DOCUMENT")." ".intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]).'">'.intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]).'</a>'; 
				
			endif; 

			$aCols["PROPERTY_FORUM_MESSAGE_CNT"] = '<a href="'.$res["URL"]["VIEW"].'">'.intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]).'</a>'; 
			
			$aCols["BP_PUBLISHED"] = ($res["BP_PUBLISHED"] != "Y" ? GetMessage("WD_N") : GetMessage("WD_Y")); 
			
			$aCols["BIZPROC"] = "";
			if ($arParams["WORKFLOW"] == "bizproc" && !empty($res["arDocumentStates"]))
			{
				$arDocumentStates = $res["arDocumentStates"]; 
				if (count($arDocumentStates) == 1)
				{
					$arDocumentState = reset($arDocumentStates);
					$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]->GetID(), $arDocumentState["ID"]);
					
					$aCols["BIZPROC"] = 
					'<div class="bizproc-item-title">'. 
						'<div class="bizproc-statuses '.
							(!(strlen($arDocumentState["ID"]) <= 0 || strlen($arDocumentState["WORKFLOW_STATUS"]) <= 0) ? 
								'bizproc-status-'.(empty($arTasksWorkflow) ? "inprogress" : "attention") : '').'"></div>'. 
						(!empty($arDocumentState["TEMPLATE_NAME"]) ? $arDocumentState["TEMPLATE_NAME"] : GetMessage("IBLIST_BP")).': '.
						'<span class="bizproc-item-title bizproc-state-title" style="margin-left:1em;">'. 
							'<a href="'.$res["URL"]["BP"].'">'. 
								(strlen($arDocumentState["STATE_TITLE"]) > 0 ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"]). 
							'</a>'. 
						'</span>'.
					'</div>'; 
					
					if (!empty($arTasksWorkflow))
					{
						$tmp = array(); 
						foreach ($arTasksWorkflow as $key => $val)
						{
							$url = CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_TASK_URL"], 
								array("ELEMENT_ID" => $res["ID"], "ID" => $val["ID"])); 
							$url = WDAddPageParams($url, array("back_url" =>  urlencode($GLOBALS['APPLICATION']->GetCurPageParam())), false);
							$tmp[] = '<a href="'.$url.'">'.$val["NAME"].'</a>'; 
						}
						$aCols["BIZPROC"] .= '<div class="bizproc-tasks">'.implode(", ", $tmp).'</div>'; 
					}
				}
				else 
				{
					$arTasks = array(); $bInprogress = false; $tmp = array(); 
					
					foreach ($arDocumentStates as $key => $arDocumentState)
					{
						$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]->GetID(), $arDocumentState["ID"]);
						if (!$bInprogress)
							$bInprogress = (strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0); 
						$tmp[$key] = 
							'<li class="bizproc-item">'.
								'<div class="bizproc-item-title">'.
									'<div class="bizproc-statuses '.
										(strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0 ? 
											'bizproc-status-'.(empty($arTasksWorkflow) ? "inprogress" : "attention") : '').'"></div>'. 
									(!empty($arDocumentState["TEMPLATE_NAME"]) ? $arDocumentState["TEMPLATE_NAME"] : GetMessage("IBLIST_BP")). 
								
								'</div>'.
								'<div class="bizproc-item-title bizproc-state-title">'.
									(strlen($arDocumentState["STATE_TITLE"]) > 0 ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"]). 
								'</div>'; 
						
						if (!empty($arTasksWorkflow))
						{
							$tmp_tasks = array(); 
							foreach ($arTasksWorkflow as $val)
							{
								$url = CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_TASK_URL"], 
										array("ELEMENT_ID" => $res["ID"], "ID" => $val["ID"]));
								$url = WDAddPageParams($url, array("back_url" =>  urlencode($GLOBALS['APPLICATION']->GetCurPageParam())), false);
								$tmp_tasks[] = '<a href="'.$url.'">'.$val["NAME"].'</a>'; 
								$arTasks[] = $val; 
							}
							
						
							$tmp[$key] .= '<div class="bizproc-tasks">'.implode(", ", $tmp_tasks).'</div>'; 
						}
						$tmp[$key] .= 
							'</li>'; 
					}
					$aCols["BIZPROC"] = 
						'<span class="bizproc-item-title">'.
							'<div class="bizproc-statuses'.($bInprogress ? ' bizproc-status-'.(empty($arTasks) ? "inprogress" : "attention") : '' ).'"></div>'.
							GetMessage("WD_BP_R_P").': <a href="'.$res["URL"]["BP"].'" title="'.GetMessage("WD_BP_R_P_TITLE").'">'.count($arDocumentStates).'</a>'.
						'</span>'.
						(!empty($arTasks) ? 
						'<br /><span class="bizproc-item-title">'.
							GetMessage("WD_TASKS").': <a href="'.$res["URL"]["BP_TASK"].'" title="'.GetMessage("WD_TASKS_TITLE").'">'.count($arTasks).'</a></span>' : ''). 
						ShowJSHint('<ol class="bizproc-items">'.implode("", $tmp).'</ol>', array('return' => true)); 
				}
			}
		}
		$aCols["ACTIVE"] = ($res["ACTIVE"] == "Y" ? GetMessage("WD_Y") : GetMessage("WD_N")); 
		
		if ($bTheFirstTimeonPage == true && $res["PERMISSION"] >= "U")
		{
			$bTheFirstTimeonPage = false; 
?>
<script>
if (/*@cc_on ! @*/ false && new ActiveXObject("SharePoint.OpenDocuments.2"))
{
	BX.ready(
		function()	
		{
			setTimeout(
				function ()
				{
					try
					{
						var res = document.getElementsByTagName("A"); 
						for (var ii = 0; ii < res.length; ii++)
						{
							if (res[ii].className.indexOf("element-edit-office") >= 0) { res[ii].style.display = 'block'; }
						}
					}
					catch(e) {}
				}
				, 10
			)
		}
	);
}
</script>
<?			
		}
		return array("actions" => $arActions, "columns" => $aCols); 
	}
}
?>
