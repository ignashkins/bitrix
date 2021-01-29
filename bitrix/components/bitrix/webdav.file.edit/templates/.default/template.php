<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$name = 
	'<div class="element-name">'.
		'<div class="element-icon ic'.substr($arResult["ELEMENT"]["EXTENTION"], 1).'"></div>'.
		'<a href="'.$arResult["ELEMENT"]["URL"]["THIS"].'" target="_blank" title="'.GetMessage("WD_DOWNLOAD_ELEMENT").'">'.
			$arResult["ELEMENT"]["NAME"].
		'</a> '. 
	'</div>'; 

if (in_array($arResult["ELEMENT"]["EXTENTION"], array(".doc", ".docx", ".xls", ".xlsx", ".rtf", ".ppt", ".pptx"))): 
	$name .= '<a href="'.$arResult["ELEMENT"]["URL"]["THIS"].'" class="element-properties element-edit-office"'.
		' onclick="return EditDocWithProgID(\''.CUtil::addslashes($arResult["ELEMENT"]["URL"]["~THIS"]).'\');" title="'.GetMessage("WD_EDIT_MSOFFICE").'"'.
	'><span></span></a>'; 
endif; 


?><?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
		"FORM_ID" => $arParams["FORM_ID"],
		"TABS" => array(
			array(
				"id" => "tab1", 
				"name" => GetMessage("WD_DOCUMENT"), 
				"title" => GetMessage("WD_DOCUMENT_ALT"), 
				"fields" => array(
					array("id" => "TITLE", "name" => GetMessage("WD_FILE"), "type" => "label", "value" => $name), 
					array("id" => "FILE_SIZE", "name" => GetMessage("WD_FILE_SIZE"), "type" => "label", 
						"value" => $arResult["ELEMENT"]["FILE_SIZE"].'<span class="wd-item-controls element_download">'.
							'<a target="_blank" href="'.$arResult["ELEMENT"]["URL"]["THIS"].'">'.GetMessage("WD_DOWNLOAD_FILE").'</a></span>'), 
					array("id" => "MODIFIED", "name" => GetMessage("WD_FILE_MODIFIED"), "type" => "label", 
						"value" => $arResult["ELEMENT"]["TIMESTAMP_X"]), 
					array("id" => "NAME", "name" => GetMessage("WD_NAME"), "type" => "custom", "required" => true, 
						"value" => '<input type="text" name="NAME" value="'.$arResult["ELEMENT"]["NAME"].'" />'), 
					array("id" => "FILE", "name" => GetMessage("WD_FILE_REPLACE"), "type" => "custom", 
						"value" => '<input type="file" name="FILE" value="" />')
			)
		)),
		"BUTTONS" => array(
			"back_url" => "", 
			"custom_html" => 
				'<input type="hidden" name="ELEMENT_ID" value="'.$arResult["ELEMENT"]["ID"].'" />'.
				'<input type="hidden" name="edit" value="Y" />'.
				'<input type="hidden" name="ACTION" value="EDIT" />'
		)
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);
?>
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