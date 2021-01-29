<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
CAjax::Init();
IncludeAJAX();
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/utils.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/components/bitrix/webdav/templates/.default/script.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/components/bitrix/search.tags.input/templates/.default/script.js"></script>');
if ($arParams["USE_BIZPROC"] == "Y"): 
	$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/bizproc/bizproc.js"></script>');
endif;
$GLOBALS['APPLICATION']->AddHeadString('<link href="/bitrix/components/bitrix/search.tags.input/templates/.default/style.css" type="text/css" rel="stylesheet" />');
if (!function_exists("getIndexImageUploaderOnPage"))
{
	function getIndexImageUploaderOnPage()
	{
		static $iIndexOnPage = 0;
		$iIndexOnPage++;
		return $iIndexOnPage;
	}
}
/*************************************************************************
	Processing of received parameters
*************************************************************************/
// Browtjer info
$str = strToLower($_SERVER['HTTP_USER_AGENT']);
$Browser = array(
	"isOpera" => (strpos($str, "opera") !== false));
$Browser["isIE"] = (!$Browser["isOpera"] && strpos($str, "msie") !== false);
$Browser["isWinIE"] = ($Browser["isIE"] && strpos($str, "win") !== false);

// User settings 
$arUserSettings = array("view_mode" => strToLower($_REQUEST["view_mode"]));
if ($GLOBALS["USER"]->IsAuthorized())
{
	if (!empty($_REQUEST["change_view_mode_data"]))
	{
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav.element.upload/user_settings.php");
	}
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".strToLower($GLOBALS["DB"]->type)."/favorites.php");
	$arUserSettings = @unserialize(CUserOptions::GetOption("webdav", "upload_settings", ""));
	$arUserSettings = (is_array($arUserSettings) ? $arUserSettings : array());
}
$arParams["TEMPLATES"] = array("applet", "classic", "form");
$arParams["TEMPLATES_DATA"] = array(
	"applet" => array("name" => GetMessage("WD_IU_APPLET"), "title" => GetMessage("WD_IU_APPLET_TITLE")), 
	"classic" => array("name" => GetMessage("WD_IU_CLASSIC"), "title" => GetMessage("WD_IU_CLASSIC_TITLE")), 
	"form" => array("name" => GetMessage("WD_IU_FORM"), "title" => GetMessage("WD_IU_FORM_TITLE")));
$arParams["VIEW_MODE"] = (in_array($arUserSettings["view_mode"], $arParams["TEMPLATES"]) ? $arUserSettings["view_mode"] : "classic");
$arParams["VIEW_MODE"] = ($Browser["isOpera"] ? "form" : $arParams["VIEW_MODE"]);
$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
$arParams["INDEX_ON_PAGE"] = getIndexImageUploaderOnPage();
$arParams["SHOW_TAGS"] = "Y";
$arParams["NOTE"] = trim($arParams["NOTE"]);
/*************************************************************************
	/Processing of received parameters
*************************************************************************/
include(str_replace(array("\\", "//"), "/", dirname(__FILE__)."/script.php"));
?>
<div id="webdav_error_<?=$arParams["INDEX_ON_PAGE"]?>" class="error required starrequired">
<?
if (!empty($arResult["ERROR_MESSAGE"])):
	ShowError($arResult["ERROR_MESSAGE"]);
endif;
?>
</div>

<noscript>
	<span class="starrequired"><?=GetMessage("IU_ATTENTION")?></span>
	<?=GetMessage("IU_DISABLED_JAVASCRIPT")?>
	<div class="empty-clear"></div>
</noscript>

<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr>
	<td id="upload_comments">
		<span class="comments">
			<?=str_replace("#FILE_SIZE#", $arParams["UPLOAD_MAX_FILESIZE"], GetMessage("WD_ATTENTION_FILESIZE"))?>
			<?
			if (!empty($arParams["NOTE"])):
			?><?=$arParams["~NOTE"]?><?
			endif;
			?>
		</span>
	</td>
<?
if (!$Browser["isOpera"]):
?>
	<td id="description_views">
		<form action="<?=$APPLICATION->GetCurPageParam("", array("change_view_mode_data", "save", "view_mode", "sessid"))?>" method="GET" class="wd-form">
			<?=bitrix_sessid_post()?>
			<input type="hidden" name="user_id" value="<?=intVal($GLOBALS["USER"]->GetID())?>" />
			<input type="hidden" name="save" value="view_mode" />
			<?=GetMessage("WD_VIEW")?>: 
			<select name="view_mode" onclick="if ('<?=$arParams["VIEW_MODE"]?>' != this.value && window.ChangeModeUploader){ChangeModeUploader(this)};">
<?
	foreach ($arParams["TEMPLATES"] as $key)
	{
?>
				<option value="<?=$key?>" <?=($arParams["VIEW_MODE"] == $key ? "selected='selected'" : "")?> title="<?=$arParams["TEMPLATES_DATA"][$key]["title"]?>"><?
					?><?=$arParams["TEMPLATES_DATA"][$key]["name"]?></option>
<?
	}
?>
			</select>
			<noscript>
				<input type="submit" name="change_view_mode_data" value=">" />
			</noscript>
		</form>
	</td>
<?
endif;
?>
	</tr>
</table>
<script>
oParams['<?=$arParams["INDEX_ON_PAGE"]?>'] = {
	'object' : null,
	'thumbnail' : null, 
	'index' : '<?=$arParams["INDEX_ON_PAGE"]?>', 
	'mode' : '<?=$arParams["VIEW_MODE"]?>', 
	'inited' : false, 
	'type' : 'none', 
	'url' : {
		'this' : '<?=CUtil::JSUrlEscape($arResult["~SECTION_LINK"])?>', 
		'section' : '<?=CUtil::JSUrlEscape($arResult["~SECTION_EMPTY_LINK"])?>', 
		'form' : '<?=str_replace("//", "/", $_SERVER["HTTP_HOST"]."/".POST_FORM_ACTION_URI)?>'}
	}; 
</script>

<div class="image-uploader-objects">
<?
if ($arParams["VIEW_MODE"] == "applet"):
?>
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="image-uploader-table image-upload-applet">
	<tr class="top">
		<td class="left"><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr class="buttons buttons-top">
		<td class="left">
			<div class="iu-uploader-buttons">
				<div class="iu-uploader-button">
					<a href="#AddFolders" onclick="if (oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['inited'])<?
						?>{getImageUploader('ImageUploader<?=$arParams["INDEX_ON_PAGE"]?>').AddFolders();} return false;"><?
						?><div><span><?=GetMessage("AddFolders")?></span></div></a></div>
				<div class="iu-uploader-button">
					<a href="#AddFiles" onclick="if (oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['inited'])<?
						?>{getImageUploader('ImageUploader<?=$arParams["INDEX_ON_PAGE"]?>').AddFiles();} return false;"><?
						?><div><span><?=GetMessage("AddFiles")?></span></div></a></div>
				<div class="empty-clear"></div>
			</div>
		</td>
		<td class="right">
			<div class="iu-uploader-containers">
				<div class="iu-button-removeall"><?
					?><a href="#RemoveFiles" onclick="return false;" <?
						?>onmousedown="if (oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['inited'])<?
							?>{getImageUploader('ImageUploader<?=$arParams["INDEX_ON_PAGE"]?>').RemoveAllFromUploadList();}"><?
						?><span><span><?=GetMessage("RemoveAllFromUploadList")?></span></span>
					</a>
				</div>
				<div class="iu-uploader-container iu-uploader-filecount">
					<div>
						<label for="iu_count_to_upload_<?=$arParams["INDEX_ON_PAGE"]?>"><?=GetMessage("Files")?>: </label>
						<span id="iu_count_to_upload_<?=$arParams["INDEX_ON_PAGE"]?>"><?=GetMessage("NoFiles")?></span> 
					</div>
				</div>
				<div class="empty-clear"></div>
			</div>
		</td>
	</tr>
	<tr class="separator">
		<td class="left"><div class="hr"></div></td>
		<td class="right"></td>
	</tr>
	<tr class="object">
		<td class="left iu-uploader-uploader" id="uploader_<?=$arParams["INDEX_ON_PAGE"]?>">
			<div class="iu-uploader-containers iu-uploader-uploader-loadwindow">
				<div id="iu_error_upload_uploader<?=$arParams["INDEX_ON_PAGE"]?>" class="errortext">
					<noscript><?=GetMessage("IU_DISABLED_JAVASCRIPT")?></noscript>
				</div>
				<div id="waitwindow" class="waitwindow"><?=GetMessage("WD_LOADING")?></div>
			</div>
		</td>
		<td class="right iu-uploader-thumbnail">
		<form id="iu_upload_applet_form_<?=$arParams["INDEX_ON_PAGE"]?>" class="wd-form">
			<div class="iu-uploader-fields">
				<div class="iu-uploader-field iu-uploader-field-image">
					<div id="thumbnail_<?=$arParams["INDEX_ON_PAGE"]?>"></div>
				</div>
				<div class="iu-uploader-field iu-uploader-field-title">
					<label for="Title<?=$arParams["INDEX_ON_PAGE"]?>"><?=GetMessage("Title")?></label>
					<input name="Title" id="Title<?=$arParams["INDEX_ON_PAGE"]?>" class="Title" type="text" />
				</div>
<?
	if ($arParams["SHOW_TAGS"] == "Y"):
?>
				<div class="iu-uploader-field iu-uploader-field-tags">
					<label for="Tag<?=$arParams["INDEX_ON_PAGE"]?>"><?=GetMessage("Tags")?></label>
					<input name="Tag" id="Tag<?=$arParams["INDEX_ON_PAGE"]?>" class="Tag" type="text" <?
		if (IsModuleInstalled("search")): 
					?>onfocus="SendTags(this);" <?
		endif;
					?>/>
				</div>
<?
	endif;
?>
				<div class="iu-uploader-field iu-uploader-field-description">
					<label for="Description<?=$arParams["INDEX_ON_PAGE"]?>"><?=GetMessage("Description")?></label>
					<textarea name="Description" id="Description<?=$arParams["INDEX_ON_PAGE"]?>" class="Description"></textarea>
				</div>
			</div>
		</form>
	</td></tr>
	<tr class="separator">
		<td class="left"><div class="hr"></div></td>
		<td class="right"></td>
	</tr>
</table>
<?
elseif ($arParams["VIEW_MODE"] == "classic"):
?>
<div id="uploader_<?=$arParams["INDEX_ON_PAGE"]?>">
	<div class="iu-uploader-containers iu-uploader-uploader-loadwindow">
		<div id="iu_error_upload_uploader<?=$arParams["INDEX_ON_PAGE"]?>" class="errortext">
			<noscript><?=GetMessage("IU_DISABLED_JAVASCRIPT")?></noscript>
		</div>
		<div id="waitwindow" class="waitwindow"><?=GetMessage("WD_LOADING")?></div>
	</div>
</div>
<?
else:
?>
<div id="file_object_div_<?=$arParams["INDEX_ON_PAGE"]?>" class="image-upload-form-files"></div>
<div class="empty-clear"></div>
<?
endif;
?>
</div>

<form id="iu_upload_form_<?=$arParams["INDEX_ON_PAGE"]?>" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data" class="wd-form">
	<input type="hidden" name="save_upload" id="save_upload" value="Y" />
	<input type="hidden" name="sessid" id="sessid" value="<?=bitrix_sessid()?>" />
	<input type="hidden" name="FileCount" value="<?=$arParams["UPLOAD_MAX_FILE"]?>" />
	<input type="hidden" name="PackageGuid" value="<?=time()?>" />
	<input type="hidden" name="SECTION_ID" value="<?=$arParams["SECTION_ID"]?>" />
	<input type="hidden" name="ACTION" value="wd_upload_save" />
	
	<noscript id="file_object_noscript_<?=$arParams["INDEX_ON_PAGE"]?>">
	<input type="hidden" name="redirect" value="Y" />
	<div class="image-upload-form-files">
<?
	for ($ii = 1; $ii <= $arParams["UPLOAD_MAX_FILE"]; $ii++):
?>
	<div class="image-upload-form-file">
		<div class="wd-t"><div class="wd-r"><div class="wd-b"><div class="wd-l"><div class="wd-c">
			<div class="wd-title"><div class="wd-tr"><div class="wd-br"><div class="wd-bl"><div class="wd-tl">
				<div class="wd-title-header"><?=$ii?></div>
			</div></div></div></div></div>
		
		<div class="form">
			<div class="iu-uploader-field iu-uploader-field-file">
				<label for="File_<?=$arParams["INDEX_ON_PAGE"]?>_<?=$ii?>"><?=GetMessage("File")?></label>
				<input type="file" name="SourceFile_<?=$ii?>" id="File_<?=$arParams["INDEX_ON_PAGE"]?>_<?=$ii?>" value="" />
			</div>
			
			<div class="iu-uploader-field iu-uploader-field-title">
				<label for="Title_<?=$arParams["INDEX_ON_PAGE"]?>_<?=$ii?>"><?=GetMessage("Title")?></label>
				<input type="text" name="Title_<?=$ii?>" id="Title_<?=$arParams["INDEX_ON_PAGE"]?>_<?=$ii?>" value="" />
			</div>
<?
	if ($arParams["SHOW_TAGS"] == "Y"):
?>
			<div class="iu-uploader-field iu-uploader-field-tags">
				<label for="Tag_<?=$arParams["INDEX_ON_PAGE"]?>_<?=$ii?>"><?=GetMessage("Tags")?></label>
				<input name="Tag_<?=$ii?>" id="Tag_<?=$arParams["INDEX_ON_PAGE"]?>_<?=$ii?>" type="text" />
			</div>
<?
	endif;
?>
			<div class="iu-uploader-field iu-uploader-field-description">
				<label for="Description_<?=$arParams["INDEX_ON_PAGE"]?>_<?=$ii?>"><?=GetMessage("Description")?></label>
				<textarea name="Description_<?=$ii?>" id="Description_<?=$arParams["INDEX_ON_PAGE"]?>_<?=$ii?>"></textarea>
			</div>
		</div>
		</div></div></div></div></div>
	</div>
<?
	endfor;
?>
	</div>
	<div class="empty-clear"></div>
	</noscript>

<div class="image-uploader-settings">
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="image-uploader-table image-upload-table-bottom-<?=$arParams["VIEW_MODE"]?>">
	<tr class="top">
		<td class="left"><div class="empty"></div></td>
		<td class="middle"><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr class="buttons-bottom">
		<td class="left"><div class="empty"></div></td>
		<td class="middle">
<?
include(str_replace(array("\\", "//"), "/", dirname(__FILE__)."/footer.php"));
?>
			<div class="iu-uploader-buttons">
				<div class="iu-uploader-button">
					<a href="#SendFiles" id="Send_<?=$arParams["INDEX_ON_PAGE"]?>" class="nonactive" onclick="return false;"><?
						?><div><span><?=GetMessage("Send")?></span></div>
					</a>
				</div>
				<noscript>
					<div class="iu-uploader-button-noscript">
						<input type="submit" value="<?=GetMessage("Send")?>" />
					</div>
				</noscript>
				<div class="empty-clear"></div>
			</div>
		</td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr class="bottom">
		<td class="left"><div class="empty"></div></td>
		<td class="middle"><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
</table>
</div>
</form>

<script>
if (typeof oText != "object")
	oText = {};
oText["SourceFile"] = "<?=CUtil::JSEscape(GetMessage("File"))?>";
oText["Title"] = "<?=CUtil::JSEscape(GetMessage("Title"))?>";
oText["Tags"] = "<?=CUtil::JSEscape(GetMessage("Tags"))?>";
oText["Description"] = "<?=CUtil::JSEscape(GetMessage("Description"))?>";
oText["NoFiles"] = "<?=CUtil::JSEscape(GetMessage("NoFiles"))?>";
oText["ErrorNoData"] = "<?=CUtil::JSEscape(str_replace("#POST_MAX_SIZE#", intVal(ini_get('post_max_size')), GetMessage("ErrorNoData")))?>";
oText["Error_2"] = "<?=CUtil::addslashes(GetMessage("WD_ATTENTION2_1"))?>";
oText["Error_21"] = "<?=CUtil::addslashes(GetMessage("WD_ATTENTION2_2"))?>";
setTimeout(to_init, 100);
</script>
<noscript>
<style>
div.image-uploader-objects, div.iu-uploader-button, a#ControlsAppletForm {
	display:none;}
</style>
</noscript>