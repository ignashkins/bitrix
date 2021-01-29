<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!($_REQUEST["save_upload"] == "Y" || ($_SERVER['REQUEST_METHOD'] == "POST" && empty($_POST)))):
	return true;
endif;

$this->IncludeComponentLang("action_iblock.php");

$arError = array();
$arFile = array();
$result = array("FILE" => array(), "FILE_INFO" => array());
array_walk($_REQUEST, '__UnEscape');
array_walk($_FILES, '__UnEscape');
if (!empty($_FILES)):
	__CorrectFileName($_FILES);
endif;

if ($arParams["SECTION_ID"] <= 0 && $arParams["ROOT_SECTION_ID"] > 0)
	$arParams["SECTION_ID"] = $arParams["ROOT_SECTION_ID"];
	
if ($_SERVER['REQUEST_METHOD'] == "POST" && empty($_POST))
{
	$arError["bad_post"] = array(
		"id" => "bad_post",
		"text" => str_replace(
			"#SIZE#", 
			$arParams["UPLOAD_MAX_FILESIZE"]/*intVal(ini_get('post_max_size'))*/, 
			GetMessage("WD_ERROR_BAD_POST")));
	// format answer
	$view_mode = ($_REQUEST["view_mode"] != "form" ? "applet" : "form");
	if ($GLOBALS["USER"]->IsAuthorized())
	{
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".strToLower($GLOBALS["DB"]->type)."/favorites.php");
		$arUserSettings = @unserialize(CUserOptions::GetOption("webdav", "upload_settings", ""));
		$view_mode = $arUserSettings["view_mode"];
	}
	$_REQUEST["AJAX_CALL"] = ($view_mode != "form" ? "Y" : "N");
	if (strpos(strToLower($_SERVER['HTTP_USER_AGENT']), "opera") !== false)
		$_REQUEST["AJAX_CALL"] = "N";
	$_REQUEST["CONVERT"] = $_REQUEST["AJAX_CALL"];
}
elseif (!check_bitrix_sessid())
{
	$arError["bad_sessid"] = array(
		"id" => "bad_sessid",
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
elseif (empty($_FILES))
{
	$arError["empty_files"] = array(
		"id" => "empty_files",
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
elseif ($arParams["USE_WORKFLOW"] == "Y" && empty($arResult["WF_STATUSES"][$_REQUEST["WF_STATUS_ID"]]))
{
	$arError["empty_files"] = array(
		"id" => "bad_status",
		"text" => GetMessage("WD_ERROR_BAD_STATUS"));
}
else
{
/************** Create file array ***********************************/
//		for ($i = 1; $i <= intVal($_REQUEST["FileCount"]); $i++)
	$i = 1;
	{
		$arFile = $_FILES["SourceFile_".$i];
		$arElement = array();
		$arFileError = array();
		$name = $arFile["name"];
		
		$file_res = array();
		
		if (empty($arFile) || empty($arFile["name"]))
			continue;
		$arFile["error"] = intVal($arFile["error"]);
		if ($arFile["error"] > 0)
		{
			if ($arFile["error"] == 1)
			{
				$arFileError[] = array(
					"id" => "max_file_size",
					"text" => str_replace(
						array("#NAME#", "#SIZE#"), 
						array($arFile["name"], $arParams["UPLOAD_MAX_FILESIZE"]/*intVal(ini_get('upload_max_filesize'))*/),
						GetMessage("WD_ERROR_UPLOAD_MAX_FILE_SIZE")));
			}
			elseif ($arFile["error"] == 3)
			{
				$arFileError[] = array(
					"id" => "bad_file",
					"text" => GetMessage("WD_ERROR_UPLOAD_BAD_FILE"));
			}
			else
			{
				$arFileError[] = array(
					"id" => "bad_file",
					"text" => GetMessage("WD_ERROR_UPLOAD_FILE_NOT_LOAD"));
			}
		}
		elseif (!$ob->CheckRights("PUT", true, $arFile["name"]))
		{
			$oError = $GLOBALS["APPLICATION"]->GetException(); 
			$arFileError[] = array(
				"id" => "bad_file",
				"text" => ($oError ? $oError->GetString() : GetMessage("WD_ERROR_UPLOAD_BAD_FILE")));
		}
		
		$number = intVal($_REQUEST["PackageIndex"]) * intVal(!empty($_REQUEST["FilesPerOnePackageCount"]) ? 
			$_REQUEST["FilesPerOnePackageCount"] : $arParams["UPLOAD_MAX_FILE"]) + $i;
		if (!empty($_REQUEST["Title_".$number]))
		{
			$name = $_REQUEST["Title_".$number];
			if (!(strPos($name, ".") > 0))
			{
				$f = pathinfo($arFile["name"]);
				$name = $_REQUEST["Title_".$number].".".$f["extension"];
			}
		}
		$name = CWebDavIblock::CorrectName($name);
/*		elseif (!CWebDavIblock::CheckName($name))
		{
			$arFileError[] = array(
				"id" => "bad_element_name",
				"text" => str_replace(
					array("#NAME#", "#SIZE#"),
					array($arFile["name"], $arParams["UPLOAD_MAX_FILESIZE"]),
					GetMessage("WD_ERROR_BAD_ELEMENT_NAME")));
		}
		else*/
		if ($arParams["UPLOAD_MAX_FILESIZE_BYTE"] > 0 && $arFile["size"] > $arParams["UPLOAD_MAX_FILESIZE_BYTE"])
		{
			$arFileError[] = array(
				"id" => "max_file_size",
				"text" => str_replace(array("#NAME#", "#SIZE#"), array($arFile["name"], $arParams["UPLOAD_MAX_FILESIZE"]),
					GetMessage("WD_ERROR_UPLOAD_MAX_FILE_SIZE")));
		}
		elseif (!$ob->CheckUniqueName($name, $arParams["SECTION_ID"], $res))
		{
			if ($res["object"] == "section")
			{
				$arFileError[] = array(
					"id" => "double_name_section",
					"text" => str_replace("#NAME#", $arFile["name"], GetMessage("WD_ERROR_DOUBLE_NAME_SECTION")));
			}
			else
			{
				$arElement = $res["data"];
				if ($arParams["USE_WORKFLOW"] == "Y" && intVal($res["data"]["WF_PARENT_ELEMENT_ID"]) > 0)
					$arElement["ID"] = $res["data"]["WF_PARENT_ELEMENT_ID"];
				if ($_REQUEST["overview"] != "Y")
				{
					if ($name != $arFile["name"])
						$arFileError[] = array(
							"id" => "double_name_element",
							"text" => str_replace(
								array("#NAME#", "#TITLE#"), 
								array($arFile["name"], $name), GetMessage("WD_ERROR_DOUBLE_NAME_TITLE")));
					else
						$arFileError[] = array(
							"id" => "double_name_element",
							"text" => str_replace("#NAME#", $arFile["name"], GetMessage("WD_ERROR_DOUBLE_NAME_ELEMENT")));
				}
				elseif ($arParams["CHECK_CREATOR"] == "Y" && $arElement["CREATED_BY"] != $GLOBALS['USER']->GetId())
				{
					$arFileError[] = array(
						"id" => "double_name_element",
						"text" => str_replace("#NAME#", $arFile["name"], GetMessage("WD_ERROR_DOUBLE_NAME_ELEMENT_NOT_REWRITE")));
				}
				elseif ($arParams["USE_WORKFLOW"] == "Y" && 
					($res["data"]["WF_STATUS_ID"] > 1 && $arResult["WF_STATUSES_PERMISSION"][$res["data"]["WF_STATUS_ID"]] < 2))
				{
					if ($name != $arFile["name"])
						$arFileError[] = array(
							"id" => "double_name_element",
							"text" => str_replace(
								array("#NAME#", "#TITLE#"), 
								array($arFile["name"], $name), GetMessage("WD_ERROR_DOUBLE_NAME_TITLE")));
					else
						$arFileError[] = array(
							"id" => "double_name_element_and_bad_permission",
							"text" => str_replace("#NAME#", $arFile["name"], GetMessage("WD_ERROR_DOUBLE_NAME_ELEMENT")));
				}
			}
		}
		
		if (empty($arFileError))
		{
			$options = array(
				"new" => empty($arElement), 
				"arFile" => $arFile, 
				"arDocumentStates" => $arDocumentStates,  
				"arUserGroups" => $ob->USER["GROUPS"],
				"FILE_NAME" => $name, 
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"IBLOCK_SECTION_ID" => $arParams["SECTION_ID"],
				"TAGS" => $_REQUEST["Tag_".$number], 
				"PREVIEW_TEXT" => $_REQUEST["Description_".$number]); 

			if (intVal($_POST["WF_STATUS_ID"]) > 0) 
				$options["WF_STATUS_ID"] = $_POST["WF_STATUS_ID"];

			if (!empty($arElement))
				$options["ELEMENT_ID"] = $arElement["ID"]; 
			else
				$options["arUserGroups"][] = "Author"; 

			$GLOBALS["DB"]->StartTransaction();
			
			if (!$ob->put_commit($options))
			{
				$arFileError[] = array(
					"id" => "error_put",
					"text" => $ob->LAST_ERROR);
				$GLOBALS["DB"]->Rollback();
			}
			else
			{
				$GLOBALS["DB"]->Commit();
                $arElement['ID'] = $options['ELEMENT_ID'];
			}
		}
		
		if (empty($arFileError))
		{
			CIBlockElement::RecalcSections($arElement["ID"]);
			$arFields["ID"] = $arElement["ID"];
			if(function_exists('BXIBlockAfterSave'))
				BXIBlockAfterSave($arFields);
			$file_res = array("status" => "success");
			if (!empty($ob->LAST_ERROR))
				$file_res = array("status" => "error", "error" => array(array("id" => "error", "text" => $ob->LAST_ERROR)));
		}
		else 
		{
			$bVarsFromForm = true;
			$file_res = array("status" => "error", "error" => $arFileError);
		}
		// Main info about file
		$result["FILE"][$name] = $file_res;
		// Additional info about file
		$file_res["id"] = $arElement["ID"];
		$file_res["number"] = $number;
		$file_res["title"] = $name;
		$file_res["description"] = $arFields["PREVIEW_TEXT"];
		$result["FILE_INFO"][$arFile["name"]] = $file_res;
	}
}
/************** Answer **********************************************/
$url = (!empty($_REQUEST["wd_upload_apply"]) ? $arResult["URL"]["~UPLOAD"] : $arResult["URL"]["~SECTIONS"]);
$bVarsFromForm = ($bVarsFromForm ? $bVarsFromForm : !empty($arError));
$uploader = array();
if ($_REQUEST["AJAX_CALL"] == "Y")
{
	$cache_id = "image_uploader_".preg_replace("/[^a-z0-9]+/is", "_", $_REQUEST["PackageGuid"]);
	$cache_path = "/bitrix/webdav/image_uploader/";
	if ($cache->InitCache(3600, $cache_id, $cache_path))
	{
		$res = $cache->GetVars();
		if (is_array($res["uploader"]))
			$uploader = $res["uploader"];
	}
}

if (empty($uploader))
	$uploader = array("fatal_errors" => array(), "files" => array(), "section_id" => $arParams["SECTION_ID"]);

$uploader["fatal_errors"] = array_merge($uploader["fatal_errors"], $arError);
$uploader["files"] = array_merge($uploader["files"], $result["FILE"]);

if ($_REQUEST["AJAX_CALL"] == "Y")
{
	$cache->Clean($cache_id, $cache_path);
	$cache->StartDataCache(3600, $cache_id, $cache_path);
	$cache->EndDataCache(array("uploader"=>$uploader));
}
if (empty($_REQUEST["wd_upload_apply"]))
	$uploader["url"] = $url;
$arResult["RETURN_DATA"] = $uploader;

if ($_REQUEST["FORMAT_ANSWER"] != "return")
{
	if ($_REQUEST["AJAX_CALL"] == "Y")
	{
		$APPLICATION->RestartBuffer();
		if ($_REQUEST["CONVERT"] == "Y")
			array_walk($uploader, '__Escape');
		?><?=CUtil::PhpToJSObject($uploader);?><?
		die();
	}
	elseif (!$bVarsFromForm)
	{
		LocalRedirect($url);
	}
}
else 
{
	$arResult["RETURN_DATA"]["current_files"] = $result["FILE_INFO"];
	$arResult["RETURN_DATA"]["url"] = $url;
	if ($_REQUEST["AJAX_CALL"] == "Y" || !$bVarsFromForm)
	{
		return $arResult["RETURN_DATA"];
	}
}

if ($bVarsFromForm)
{
	if (!empty($uploader['fatal_errors']))
		$arResult["ERROR_MESSAGE"] = WDShowError($uploader['fatal_errors']);
	else 
	{
		foreach ($uploader['files'] as $res)
			$arResult["ERROR_MESSAGE"] .= WDShowError($res["error"])."<br />";
	}
}
?>
