<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if ($_REQUEST["save_upload"] != "Y" || $_SERVER['REQUEST_METHOD'] != "POST"):
	return true;
endif;

$this->IncludeComponentLang("action_file.php");

$arError = array();
$arFile = array();
$result = array();

array_walk($_REQUEST, '__UnEscape');
array_walk($_FILES, '__UnEscape');
if (!empty($_FILES)):
	__CorrectFileName($_FILES);
endif;

if (empty($_POST))
{
	$arError["bad_post"] = array(
		"id" => "bad_post",
		"text" => str_replace("#SIZE#", intVal(ini_get('post_max_size')), GetMessage("WD_ERROR_BAD_POST")));
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
elseif (empty($_FILES) || empty($_FILES["SourceFile_1"]) || empty($_FILES["SourceFile_1"]["name"]))
{
	$arError["empty_files"] = array(
		"id" => "empty_files",
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
else
{
/************** Create file array ***********************************/
	$i = 1; 
	$arFile = $_FILES["SourceFile_1"];
	$arFile["error"] = intVal($arFile["error"]);
	
	
	$arElement = array();
	$arFileError = array();
	$name = $arFile["name"];

	if ($arFile["error"] > 0)
	{
		if ($arFile["error"] == 1)
		{
			$arFileError[] = array(
				"id" => "max_file_size",
				"text" => str_replace(array("#NAME#", "#SIZE#"), array($arFile["name"], intVal(ini_get('upload_max_filesize'))),
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
	
	$number = intVal($_REQUEST["PackageIndex"]) * intVal(!empty($_REQUEST["FilesPerOnePackageCount"]) ? $_REQUEST["FilesPerOnePackageCount"] : $arParams["UPLOAD_MAX_FILE"]) + $i;
	if (!empty($_REQUEST["Title_".$number]))
	{
		$name = $_REQUEST["Title_".$number];
		if (!(strPos($name, ".") > 0))
		{
			$f = pathinfo($arFile["name"]);
			$name = $_REQUEST["Title_".$number].".".$f["extension"];
		}
	}
	if ($arParams["REPLACE_SYMBOLS"] == "Y")
		$name = $ob->CorrectName($name);

	$path = $ob->real_path_full."/".$ob->_path."/".$name; 
	$path = str_replace(array("///", "//"), "/", $path); 
	
	if (!$ob->CheckName($name))
	{
		$arFileError[] = array(
			"id" => "bad_element_name",
			"text" => str_replace(
				array("#NAME#", "#SIZE#"),
				array($arFile["name"], $arParams["UPLOAD_MAX_FILESIZE"]),
				GetMessage("WD_ERROR_BAD_ELEMENT_NAME")));
	}
	elseif ($arParams["UPLOAD_MAX_FILESIZE_BYTE"] > 0 && $arFile["size"] > $arParams["UPLOAD_MAX_FILESIZE_BYTE"])
	{
		$arFileError[] = array(
			"id" => "max_file_size",
			"text" => str_replace(array("#NAME#", "#SIZE#"), array($arFile["name"], $arParams["UPLOAD_MAX_FILESIZE"]),
				GetMessage("WD_ERROR_UPLOAD_MAX_FILE_SIZE")));
	}
	elseif (is_dir($path))
	{
		$arFileError[] = array(
			"id" => "double_name_section",
			"text" => str_replace("#NAME#", $arFile["name"], GetMessage("WD_ERROR_DOUBLE_NAME_SECTION")));
	}
	elseif ($_REQUEST["overview"] != "Y" && is_file($path))
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
	else
	{
		$options = array(
			"path" => str_replace(array("///", "//"), "/", "/".$ob->_path."/".$name), 
			"fopen" => "N"); 
		if (!$ob->PUT($options))
		{
			$err = $GLOBALS["APPLICATION"]->GetException(); 
			$arFileError[] = array(
				"id" => "bad_permission",
				"text" => (is_object($err) ? $err->GetString() : "File has forbidden extention."));
		}
		elseif (!@copy($arFile["tmp_name"], $path))
		{
			$arFileError[] = array(
				"id" => "bad_upload",
				"text" => GetMessage("WD_ERROR_UPLOAD_FILE_NOT_LOAD"));
		}
		elseif (method_exists($ob, 'put_commit'))
		{
			$ob->put_commit($options); 
		}
	}
	
	if (!empty($arFileError))
		$result = array("status" => "error", "error" => $arFileError);
	else 
		$result = array("status" => "success");
}
/************** Answer **********************************************/
/************** Saved data *****************************************/
$sTmpId = md5(serialize(array("PackageGuid " => $_REQUEST["PackageGuid"], "sessid" => bitrix_sessid()))).".tmp";
$sTmpPath = $arParams["PATH_TO_TMP"].$sTmpId;
$arSavedData = array("fatal_errors" => array(), "files" => array());
if ($_REQUEST["AJAX_CALL"] == "Y" && file_exists($sTmpPath)):
	$arSavedData = file_get_contents($sTmpPath);
    if (is_string($arSavedData) && CheckSerializedData($arSavedData))
    {
        $arSavedData = unserialize($arSavedData);
    } else {
        $arSavedData = array();
    }
	$arSavedData["fatal_errors"] = (is_array($arSavedData["fatal_errors"]) ? $arSavedData["fatal_errors"] : array()); 
	$arSavedData["files"] = (is_array($arSavedData["files"]) ? $arSavedData["files"] : array()); 
endif;
/************** Saved data/*****************************************/
$uploader = $arSavedData = array(
	"fatal_errors" => array_merge($arSavedData["fatal_errors"], $arError), 
	"files" => array_merge($arSavedData["files"], array($arFile["name"] => $result)), 
	"section_id" => $ob->_path);

$arResult["RETURN_DATA"] = $uploader;
if ($_REQUEST["FORMAT_ANSWER"] != "return" && $_REQUEST["AJAX_CALL"] == "Y")
{
	$arSavedData["time"] = time();
	if ($handle = fopen($sTmpPath, "wb+")):
		$written = fwrite($handle, serialize($arSavedData));
		fclose($handle);
	endif;

	$APPLICATION->RestartBuffer();
	if ($_REQUEST["CONVERT"] == "Y")
		array_walk($uploader, '__Escape');
	?><?=CUtil::PhpToJSObject($uploader);?><?
	die();
}
$arError = $arError + $arFileError; 
if (!empty($arError))
{
	$bVarsFromForm = true;
	$e = new CAdminException($arError);
	$GLOBALS["APPLICATION"]->ThrowException($e);
	$res = $GLOBALS["APPLICATION"]->GetException();
	$arResult["ERROR_MESSAGE"] .= $res->GetString();
	return false;
}
return true;
?>
