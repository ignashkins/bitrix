<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$GLOBALS['APPLICATION']->RestartBuffer();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/template.php")));
__IncludeLang($file);
$popupWindow = new CJSPopup('', '');


$popupWindow->ShowTitlebar(str_replace("#NAME#", $arResult["SECTION"]["NAME"], 
	($arParams["ACTION"] == "EDIT" ? GetMessage("WD_EDIT_SECTION") : (
		$arParams["ACTION"] == "ADD" ? GetMessage("WD_ADD_SECTION") : GetMessage("WD_DROP_SECTION")))));
/*$popupWindow->StartDescription($sTheme);
?><p><?=str_replace("#NAME#", $arResult["SECTION"]["NAME"], 
	($arParams["ACTION"] == "EDIT" ? GetMessage("WD_EDIT_SECTION") : (
		$arParams["ACTION"] == "ADD" ? GetMessage("WD_ADD_SECTION") : GetMessage("WD_DROP_SECTION"))))?></p><?
$popupWindow->EndDescription();
*/
if (!empty($arResult["ERROR_MESSAGE"]))
	$popupWindow->ShowValidationError($arResult["ERROR_MESSAGE"]);

$popupWindow->StartContent();
?>
<?=bitrix_sessid_post()?>
<input type="hidden" name="SECTION_ID" value="<?=$arParams["SECTION_ID"]?>" />
<input type="hidden" name="IBLOCK_SECTION_ID" value="<?=$arResult["SECTION"]["IBLOCK_SECTION_ID"]?>" />
<input type="hidden" name="edit_section" value="Y" />
<input type="hidden" name="ACTION" value="<?=$arParams["ACTION"]?>" />
<input type="hidden" name="ACTIVE" value="Y" />
<input type="hidden" name="popupWindow" value="Y" />

<?

if ($arParams["ACTION"] == "DROP"):
?>
	<?=str_replace("#NAME#", $arResult["SECTION"]["NAME"], GetMessage("WD_DROP_CONFIRM"))?>
<?
else:
?>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="30%">
			<span class="required starrequired">*</span><?=GetMessage("WD_NAME")?>:
		</td>
		<td width="70%">
			<input type="text" class="text" name="NAME" value="<?=$arResult["SECTION"]["NAME"]?>" style="width:90%;" />
		</td>
	</tr>
</table>
<?
endif;

$popupWindow->EndContent();
$popupWindow->ShowStandardButtons();
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>