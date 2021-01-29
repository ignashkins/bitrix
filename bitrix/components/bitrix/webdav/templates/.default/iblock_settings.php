<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/iblock_settings.php")));
__IncludeLang($file);
$iblock_id = intval($_REQUEST["IBLOCK_ID"]); 
$popupWindow = new CJSPopup('', '');
if (!CModule::IncludeModule("iblock") || !CModule::IncludeModule("webdav"))
	return false; 
elseif ($iblock_id <= 0)
	$popupWindow->ShowError(GetMessage("WD_IBLOCK_ID_EMPTY"));


$permission = CIBlock::GetPermission($iblock_id);
$arIBlock = CIBlock::GetArrayByID($iblock_id);

if ($permission < "X")
	$popupWindow->ShowError(GetMessage("WD_ACCESS_DENIED")); 
$bWorkflow = CModule::IncludeModule("workflow"); 
$bBizproc = CModule::IncludeModule("bizproc"); 

/********************************************************************
				Actions
********************************************************************/
//$GLOBALS["APPLICATION"]->SetFileAccessPermission($_REQUEST["library_FOLDER"], $_REQUEST["library_FOLDER_PERMISSION"]);
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_REQUEST["GROUP"]))
{
	CUtil::JSPostUnescape();
	if (!check_bitrix_sessid())
	{
		$strWarning = GetMessage("WD_ERROR_BAD_SESSID"); 
	}
	else
	{
		$arFields = Array(
			"WORKFLOW" => ($_REQUEST["WF_TYPE"] == "WF"? "Y": "N"),
			"BIZPROC" => ($_REQUEST["WF_TYPE"] == "BP"? "Y": "N"));
		
		$ib = new CIBlock();
		$res = $ib->Update($iblock_id, $arFields);

		
		if (is_array($_REQUEST["GROUP_ADD"]) && !empty($_REQUEST["GROUP_ADD"]))
		{
			foreach ($_REQUEST["GROUP_ADD"] as $key => $group_id)
			{
				$_REQUEST["GROUP"][$group_id] = $_REQUEST["GROUP_ADD_PERMISSION"][$key]; 
			}
		}
		CIBlock::SetPermission($iblock_id, $_REQUEST["GROUP"]);
		WDClearComponentCache(array(
			"webdav.element.edit", 
			"webdav.element.hist", 
			"webdav.element.upload", 
			"webdav.element.view", 
			"webdav.menu",
			"webdav.section.edit", 
			"webdav.section.list"));
		$popupWindow->Close($bReload = true, $_REQUEST["back_url"]);
		die();
	}
}
/********************************************************************
				/Actions
********************************************************************/
//HTML output
$popupWindow->ShowTitlebar($arIBlock["NAME"]);
if (isset($strWarning) && $strWarning != "")
	$popupWindow->ShowValidationError($strWarning);
/*$popupWindow->StartDescription("bx-access-folder");
$popupWindow->EndDescription();
*/
$popupWindow->StartContent();
?>

<?
	$arIBlockForm = $arIBlock; 
	if ($bVarsFromForm)
	{
		foreach ($arIBlockForm as $key => $val)
		{
			if (array_key_exists($key, $_REQUEST))
				$arIBlockForm[$key] = $_REQUEST[$key]; 
		}
		$arIBlockForm["WORKFLOW"] = ($_REQUEST["WF_TYPE"] == "WF" ? "Y" : "N");
		$arIBlockForm["BIZPROC"] = ($_REQUEST["WF_TYPE"] == "BP" ? "Y" : "N");
	}
?>
<?=bitrix_sessid_post()?>
<input type="hidden" name="Update" value="Y" />
<input type="hidden" name="IBLOCK_ID" value="<?=$iblock_id?>" />
<?if (!empty($_REQUEST["back_url"])): ?>
<input type="hidden" name="back_url" value="<?=htmlspecialchars($_REQUEST["back_url"])?>" />
<?endif;
?>
<table cellpadding="0" cellspacing="0" border="0" class="edit-table" id="edit2_edit_table" width="100%">
<?
	if ($bWorkflow || $bBizproc)
	{
?>
	<tr class="section">
		<td colspan="2" align="center"><b><?=GetMessage("WD_TAB1_TITLE")?></b></td>
	</tr>
<?

		if ($bWorkflow && $bBizproc):
?>
	<tr>
		<td width="50%" align="right"><?=GetMessage("IB_E_WF_TYPE")?>:</td>
		<td width="50%">
			<select name="WF_TYPE">
				<option value="N"><?=GetMessage("IB_E_WF_TYPE_NONE")?></option>
				<option value="WF" <?=($arIBlockForm["WORKFLOW"] == "Y" ? 'selected="selected"' : "")?>><?=GetMessage("IB_E_WF_TYPE_WORKFLOW")?></option>
				<option value="BP" <?=($arIBlockForm["BIZPROC"] == "Y" ? 'selected="selected"' : "")?>><?echo GetMessage("IB_E_WF_TYPE_BIZPROC")?></option>
			</select>
		</td>
	</tr>
<?
		elseif ($bWorkflow && !$bBizproc):
?>
	<tr>
		<td width="50%" align="right"><label for="WF_TYPE"><?=GetMessage("IB_E_WORKFLOW")?>:</label></td>
		<td width="50%">
			<input type="checkbox" id="WF_TYPE" name="WF_TYPE" value="WF"<?=($arIBlockForm["WORKFLOW"] == "Y" ? 'checked="checked"' : "")?> />
			<label for="WF_TYPE"><?=GetMessage("IB_E_WF_TYPE_LABEL")?></label>
		</td>
	</tr>
<?
		elseif ($bBizproc && !$bWorkflow):
?>
	<tr>
		<td width="50%" align="right"><label for="WF_TYPE"><?=GetMessage("IB_E_BIZPROC")?>:</label></td>
		<td width="50%">
			<input type="checkbox" id="WF_TYPE" name="WF_TYPE" value="BP"<?=($arIBlockForm["BIZPROC"] == "Y" ? 'checked="checked"' : "")?> />
			<label for="WF_TYPE"><?=GetMessage("IB_E_WF_TYPE_LABEL")?></label>
		</td>
	</tr>
<?
		endif; 
	}
	
	$arResult["GROUPS"] = array(); 
	$db_res = CGroup::GetList($by="sort", $order="asc", Array("ID"=>"~2"));
	if ($db_res && $res = $db_res->Fetch())
	{
		do 
		{
			$arResult["GROUPS"][$res["ID"]] = $res;
		} while ($res = $db_res->Fetch()); 
	}
	
	$arResult["PERMISSIONS_TITLE"] = Array(
		"D" => GetMessage("IB_E_ACCESS_D"),
		"R" => GetMessage("IB_E_ACCESS_R"),
		"W" => GetMessage("IB_E_ACCESS_W"),
		"X" => GetMessage("IB_E_ACCESS_X")); 
			
	if ($arIBlock["WORKFLOW"] == "Y") :
		$arResult["PERMISSIONS_TITLE"] = Array(
			"D" => GetMessage("IB_E_ACCESS_D"),
			"R" => GetMessage("IB_E_ACCESS_R"),
			"U" => GetMessage("IB_E_ACCESS_U"),
			"W" => GetMessage("IB_E_ACCESS_W"),
			"X" => GetMessage("IB_E_ACCESS_X"));
	elseif ($arIBlock["BIZPROC"] == "Y") :
		$arResult["PERMISSIONS_TITLE"] = Array(
			"D" => GetMessage("IB_E_ACCESS_D"),
			"R" => GetMessage("IB_E_ACCESS_R"),
			"U" => GetMessage("IB_E_ACCESS_U2"),
			"W" => GetMessage("IB_E_ACCESS_W"),
			"X" => GetMessage("IB_E_ACCESS_X"));
	endif;
	$arResult["PERMISSIONS_GROUP"] = CIBlock::GetGroupPermissions($iblock_id); 
	if (!array_key_exists(1, $arResult["PERMISSIONS_GROUP"]))
		$arResult["PERMISSIONS_GROUP"][1] = "X";
	
	$_REQUEST["GROUP"] = (is_array($_REQUEST["GROUP"]) ? $_REQUEST["GROUP"] : array()); 
	$selected = (array_key_exists($_REQUEST["GROUP"][2], $arResult["PERMISSIONS_TITLE"]) ? $_REQUEST["GROUP"][2] : $arResult["PERMISSIONS_GROUP"][2]); 
	$arData = array("GROUP2" => '<select name="GROUP[2]" id="GROUP_2_" onclick="if(__obj){__obj.perms_select=\'\';}">'); 
	foreach ($arResult["PERMISSIONS_TITLE"] as $key => $val)
		$arData["GROUP2"] .= '<option value="'.$key.'"'.($selected == $key ? ' selected="selected"' : '').'>'.htmlspecialcharsex($val).'</option>';
	$arData["GROUP2"] .= '</ select>';
	
?>
	<tr class="section">
		<td colspan="2" align="center"><b><?=GetMessage("WD_TAB2_TITLE")?></b></td>
	</tr>
<?
/*
?>	<tr class="heading">
		<td colspan="2"><?=GetMessage("IB_E_DEFAULT_ACCESS_TITLE")?></td>
	</tr>
<?
*/
?>
	<tr>
		<td width="50%" align="right"><?=GetMessage("IB_E_EVERYONE")?>:</td>
		<td width="50%"><?=$arData["GROUP2"]?></td>
	</tr>
<?
/*
?>	<tr class="heading">
		<td colspan="2"><?=GetMessage("IB_E_GROUP_ACCESS_TITLE")?></td>
	</tr>
<?
*/
?>
<?

$artmp = array("" => GetMessage("IB_E_DEFAULT_ACCESS")) + $arResult["PERMISSIONS_TITLE"]; 
$arResult["GROUPS_TITLE"] = array(); 
foreach ($arResult["GROUPS"] as $key => $val)
{
	$arResult["GROUPS_TITLE"][$key] = $val["NAME"]; 
	$selected = (!empty($_REQUEST["GROUP"][$key]) ? $_REQUEST["GROUP"][$key] : $arResult["PERMISSIONS_GROUP"][$key]); 
	$selected = (array_key_exists($selected, $arResult["PERMISSIONS_TITLE"]) ? $selected : ""); 
	$tmp = '<select name="GROUP['.$key.']">'; 
	foreach ($artmp as $k => $v)
		$tmp .= '<option value="'.$k.'"'.($selected == $k ? ' selected="selected"' : '').'>'.htmlspecialcharsex($v).'</option>';
	$tmp .= '</ select>';
	$arData["GROUP".$key] = $tmp; 
	if (!empty($selected))
	{
?>
	<tr>
		<td align="right"><?=$val["NAME"]?>:</td>
		<td><div class="wd-rights-delete" onclick="__obj.dropgroup(this);"></div><?=$arData["GROUP".$key]?></td>
	</tr>
<?	
	}
}
?>
	<tr>
		<td colspan="2" align="center"><a href="#" onclick="if (window['__obj'] != null){__obj.addgroup();} return false;"><?=GetMessage("WD_ADD_GROUP")?></a></td>
	</tr>
</table>
<div class="buttons">
<input type="hidden" name="save" value="Y" />

<script>
function __wd_create_rights(groups, perms)
{
	 this.groups_select = ''; 
	 this.perms_select = ''; 
	 this.groups = groups; 
	 this.perms = perms; 
	 this.init = function()
	 {
	 	if (!this.groups_select || this.groups_select == "")
	 	{
	 		this.groups_select = "";
			for (var ii in this.groups)
				this.groups_select += '<option title="' + this.groups[ii] + '" value="' + ii + '">' + this.groups[ii] + '</option>'; 
			this.groups_select = '<select class="wd-rights-groups" name="GROUP_ADD[]">' + this.groups_select + '</select>'; 
	 	}
		if (!this.perms_select || this.perms_select == "")
		{
			var selected = document.getElementById("GROUP_2_").value; 
			var sselected = "";
			for (var ii in this.perms)
			{
				this.perms_select += '<option value="' + ii + '"' + sselected + '>' + this.perms[ii] + '</option>'; 
				sselected = (selected == ii ? ' selected="selected"' : ''); 
			}
			this.perms_select = '<select class="wd-rights-permissions" name="GROUP_ADD_PERMISSION[]">' + this.perms_select + '</select>'; 
		}
	 }
	 this.addgroup = function()
	 {
	 	this.init();
	 	var table = document.getElementById('edit2_edit_table'); 
	 	
		var tableRow = table.insertRow(table.rows.length - 1);
		tableRow.style.verticalAlign = 'top'; 
	
		var groupTD = tableRow.insertCell(0);
		var permTD = tableRow.insertCell(1);
		
		groupTD.innerHTML = this.groups_select; 
		permTD.innerHTML = this.perms_select + '<div class="wd-rights-delete" onclick="__obj.dropgroup(this);"></div>'; 
	 	return false; 
	 }
	 this.dropgroup = function(el)
	 {
	 	el.parentNode.parentNode.parentNode.removeChild(el.parentNode.parentNode);
	 	return false; 
	 }
}
__obj = new __wd_create_rights(<?=CUtil::PhpToJSObject($arResult["GROUPS_TITLE"])?>, <?=CUtil::PhpToJSObject($arResult["PERMISSIONS_TITLE"])?>); 
</script>
<input type="hidden" name="save" value="Y" />
<?
$popupWindow->EndContent();
$popupWindow->ShowStandardButtons();
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>