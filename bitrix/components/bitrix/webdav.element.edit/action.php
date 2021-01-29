<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/********************************************************************
				CANCEL
********************************************************************/
$url = (empty($_REQUEST["back_url"]) ? false : $_REQUEST["back_url"]); 
if (!empty($_REQUEST["cancel"]))
{
	$ob->UNLOCK($options = array("element_id" => $arParams["ELEMENT_ID"])); 
	$arResult["NAV_CHAIN_PATH"] = $ob->GetNavChain(array("section_id" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"]), true);
	if (!$url)
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
			array(
				"PATH" => implode("/", $arResult["NAV_CHAIN_PATH"]), 
				"SECTION_ID" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"], 
				"ELEMENT_ID" => "files", 
				"ELEMENT_NAME" => "files"));
		if ($arParams["ACTION"] == "CLONE")
		{
			$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"], 
				array("ELEMENT_ID" => ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 ? $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] : $arResult["ELEMENT"]["ID"])));
		}
	}
	if ($_REQUEST["AJAX_CALL"] != "Y" || !empty($_REQUEST["bxajaxid"]))
	{
		LocalRedirect($url);
	}
	
	$APPLICATION->RestartBuffer();
	?><?=CUtil::PhpToJSObject(array("result" => strToLower($arParams["ACTION"]."ed"), "url" => $url));?><?
	die();
}
/********************************************************************
				/ CANCEL
********************************************************************/

$CHILD_ID = false; 
$this->IncludeComponentLang("action.php");
if (!check_bitrix_sessid())
{
	$arError[] = array(
		"id" => "bad_sessid",
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
elseif ($ob->workflow == "bizproc" && strlen($_REQUEST["stop_bizproc"]) > 0)
{
	CBPDocument::TerminateWorkflow($_REQUEST["stop_bizproc"], 
		array("webdav", $arParams["BIZPROC"]["ENTITY"], $arParams["ELEMENT_ID"]), $ar);
	foreach ($ar as $a)
	{
		$arError[] = array(
			"id" => "bizproc", 
			"text" => $a["message"]);
	}
}
elseif ($arParams["ACTION"] == "CLONE")
{
	$options = array(
		"clone" => true, 
		"PARENT_ELEMENT_ID" => $arParams["ELEMENT_ID"], 
		"FILE_NAME" => $arResult["ELEMENT"]["NAME"], 
		"NAME" => (!empty($_REQUEST["NAME"]) ? $_REQUEST["NAME"] : $arResult["ELEMENT"]["NAME"]), 
		"WF_STATUS_ID" => "2", 
		"PREVIEW_TEXT" => $_REQUEST["PREVIEW_TEXT"],
		"arFile" => $_FILES[$ob->file_prop]); 
	if (!$ob->put_commit($options) || $options["ELEMENT_ID"] <= 0)
	{
		$arError[] = array(
			"id" => "clone_error",
			"text" => $ob->LAST_ERROR);
	}
	else 
	{
		$ob->UNLOCK($tmp = array("element_id" => $arParams["ELEMENT_ID"])); 
		$arParams["ELEMENT_ID"] = $options["ELEMENT_ID"]; 
	}
}
elseif ($arParams["ACTION"] == "DELETE")
{
	$result = $ob->DELETE(array("element_id" => $arParams["ELEMENT_ID"])); 
	if (intVal($result) != 204)
	{
		$arError[] = array(
			"id" => "delete_error",
			"text" => GetMessage("WD_ERROR_DELETE").$ob->LAST_ERROR);
	}
	elseif ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 && !$url)
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"], 
			array("ELEMENT_ID" => $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"])); 
	}
}
elseif ($arParams["ACTION"] == "LOCK")
{
	$ob->LOCK($options = array("element_id" => $arParams["ELEMENT_ID"])); 
}
elseif ($arParams["ACTION"] == "UNLOCK")
{
	$ob->UNLOCK($options = array("element_id" => $arParams["ELEMENT_ID"])); 
}
elseif (empty($_REQUEST["NAME"]))
{
	$arError[] = array(
		"id" => "empty_element_name",
		"text" => GetMessage("WD_ERROR_EMPTY_ELEMENT_NAME"));
}
elseif ($ob->workflow == "workflow" && !empty($_POST["WF_STATUS_ID"]) && empty($arResult["WF_STATUSES"][$_POST["WF_STATUS_ID"]]))
{
	$arError["empty_files"] = array(
		"id" => "bad_status",
		"text" => GetMessage("WD_ERROR_BAD_STATUS"));
}
else
{
	$result = $IBLOCK_SECTION_ID = false; 
	
	if (is_set($_REQUEST, "IBLOCK_SECTION_ID") && $_REQUEST["IBLOCK_SECTION_ID"] != $arResult["ELEMENT"]["IBLOCK_SECTION_ID"])
	{
		$IBLOCK_SECTION_ID = intVal($_REQUEST["IBLOCK_SECTION_ID"]); 
		if (!empty($arResult["ROOT_SECTION"]))
		{
			$IBLOCK_SECTION_ID = ($IBLOCK_SECTION_ID <= 0 ? $arResult["ROOT_SECTION"]["ID"] : $IBLOCK_SECTION_ID); 
			if ($arResult["ROOT_SECTION"]["ID"] != $IBLOCK_SECTION_ID)
			{
				$arFilter = array(
					"IBLOCK_ID" => $arParams["IBLOCK_ID"], 
					"ID" => $IBLOCK_SECTION_ID, 
					"RIGHT_MARGIN" => $arResult["ROOT_SECTION"]["RIGHT_MARGIN"], 
					"LEFT_MARGIN" => $arResult["ROOT_SECTION"]["LEFT_MARGIN"]);
				$db_res = CIBlockSection::GetList(array(), $arFilter);
				if (!($db_res && $res = $db_res->Fetch()))
					$IBLOCK_SECTION_ID = false; 
			}
		}
		if ($IBLOCK_SECTION_ID == $arResult["ELEMENT"]["IBLOCK_SECTION_ID"])
			$IBLOCK_SECTION_ID = false; 
	}
	
	$_REQUEST["NAME"] = CWebDavIblock::CorrectName($_REQUEST["NAME"]);
	
	if ($arResult["ELEMENT"]["FULL_NAME"] != $_REQUEST["NAME"] || $IBLOCK_SECTION_ID !== false)
	{
		$db_res = CIBlockElement::GetList(array(), array(
			"SECTION_ID" => ($IBLOCK_SECTION_ID === false ? $arParams["SECTION_ID"] : $IBLOCK_SECTION_ID), 
			"NAME" => $_REQUEST["NAME"],
			"!=ID" => $arResult["ELEMENT"]["ID"]));
			
		if ($db_res && $res = $db_res->Fetch())
		{
			$arError[] = array(
				"id" => "element_already_exists",
				"text" => GetMessage("WD_ERROR_ELEMENT_ALREADY_EXISTS"));
		}
	}

	if (empty($arError))
	{
		$options = Array(
			"ELEMENT_ID" => $arResult["ELEMENT"]["ID"], 
			"NAME" => $_REQUEST["NAME"],
			"FILE_NAME" => $_REQUEST["NAME"], 
			"TAGS" => $_REQUEST["TAGS"], 
			"PREVIEW_TEXT" => $_REQUEST["PREVIEW_TEXT"], 
			"arFile" => $_FILES[$ob->file_prop]);
			
		if (is_set($_REQUEST, "ACTIVE"))
			$options["ACTIVE"] = ($_REQUEST["ACTIVE"] == "Y" ? "Y" : "N"); 
		if ($IBLOCK_SECTION_ID !== false)
			$options["IBLOCK_SECTION_ID"] = $IBLOCK_SECTION_ID; 
		
		if ($ob->workflow == "workflow")
		{
			$options["WF_COMMENTS"] = $_REQUEST["WF_COMMENTS"];
			if (intVal($_POST["WF_STATUS_ID"]) > 0)
				$options["WF_STATUS_ID"] = $_POST["WF_STATUS_ID"];
		}

		if (!$ob->put_commit($options))
		{
			$arError[] = array(
				"id" => "bad_action_eit",
				"text" => $be->LAST_ERROR);
		}
		else 
		{
			$arParams["ELEMENT_ID"] = $options["ELEMENT_ID"];
			if ($ob->workflow == "bizproc" && $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 && 
				(!empty($arResult["ELEMENT"]["~arDocumentStates"]) || $_REQUEST["bizproc_index"] > 0))
			{
				$db_res = CIBlockElement::GetByID($arResult["ELEMENT"]["ID"]); 
				if (!($db_res && $res = $db_res->Fetch()))
				{
					$arParams["ELEMENT_ID"] = $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]; 
				}
			}
			$ob->UNLOCK($options = array("element_id" => $arParams["ELEMENT_ID"])); 
		}
	}
}

if (!empty($arError))
	return false; 

if (!$url)
{
	if (!empty($_REQUEST["apply"]))
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
			array("ELEMENT_ID" => $arParams["ELEMENT_ID"], "ACTION" => "EDIT"));
		if ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 && $ob->workflow == "bizproc")
		{
			$db_res = CIBlockElement::GetList(array(), array("ID" => $arResult["ELEMENT"]["ID"], "SHOW_NEW" => "Y"));
			if (!($db_res && $res = $db_res->Fetch()))
				$url = false; 
		}
	}
	if (!$url && $ob->workflow == "bizproc" && $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 || 
		$arParams["ACTION"] == "CLONE")
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"], 
			array("ELEMENT_ID" => ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 ? $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] : $arResult["ELEMENT"]["ID"])));
	}
	if (!$url)
	{
		$arResult["NAV_CHAIN_PATH"] = $ob->GetNavChain(array("section_id" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"]), true);
		$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
			array(
				"PATH" => implode("/", $arResult["NAV_CHAIN_PATH"]), 
				"SECTION_ID" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"], 
				"ELEMENT_ID" => "files", 
				"ELEMENT_NAME" => "files"));
	}
}

if ($_REQUEST["AJAX_CALL"] == "Y" || !empty($_REQUEST["bxajaxid"]))
{
	$APPLICATION->RestartBuffer();
	?><?=CUtil::PhpToJSObject(array("result" => strToLower($arParams["ACTION"]."ed"), "url" => $url));?><?
	die();
}
LocalRedirect($url);
?>