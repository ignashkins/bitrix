<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
	$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/components/bitrix/webdav/templates/.default/script.js"></script>', true);
endif;
CAjax::Init(); 
CUtil::InitJSCore(array(/*'ajax', */'window')); 
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/utils.js"></script>', true);
/********************************************************************
				Input params
********************************************************************/
$arParams["USE_SEARCH"] = ($arParams["USE_SEARCH"] == "Y" && IsModuleInstalled("search") ? "Y" : "N");
$arParams["SHOW_WEBDAV"] = ($arParams["SHOW_WEBDAV"] == "N" ? "N" : "Y");
$res = strtolower($_SERVER["HTTP_USER_AGENT"]); 
$bIsIE = (strpos($res, "opera") === false && strpos($res, "msie") !== false); 
/********************************************************************
				/Input params
********************************************************************/
$arButtons = array(); 
if (strpos($arParams["PAGE_NAME"], "WEBDAV_BIZPROC_WORKFLOW") !== false)
{
	if ($arParams["USE_BIZPROC"] == "Y" && $arParams["PERMISSION"] >= "W" && IsModuleInstalled("bizprocdesigner"))
	{
		$arButtons[] = array(
			"TEXT" => GetMessage("BPATT_HELP1"),
			"TITLE" => GetMessage("BPATT_HELP1_TEXT"),
			"LINK" => $arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"].(strpos($arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"], "?") === false ? "?" : "&").
				"init=statemachine",
			"ICON" => "btn-list"); 
		$arButtons[] = array(
			"TEXT" => GetMessage("BPATT_HELP2"),
			"TITLE" => GetMessage("BPATT_HELP2_TEXT"),
			"LINK" => $arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"].(strpos($arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"], "?") === false ? "?" : ""),
			"ICON" => "btn-list"); 
	}
}
elseif ($arParams["PAGE_NAME"] == "SECTIONS")
{
	if ($arParams["PERMISSION"] >= "U" && !($arParams["OBJECT"]->workflow == 'workflow' && !$arParams["OBJECT"]->permission_wf_edit))
	{
		if ($arParams["SHOW_WEBDAV"] == "Y" && $bIsIE)
		{
			$arButtons[] = array(
				"TEXT" => GetMessage("WD_ELEMENT_ADD"),
				"TITLE" => GetMessage("WD_ELEMENT_ADD_ALT"),
				"LINK" => "javascript:WDAddElement('".CUtil::JSEscape($arResult["URL"]["ELEMENT"]["ADD"])."');",
				"ICON" => "btn-new element-add"); 
		}
		
		$arButtons[] = array(
			"TEXT" => GetMessage("WD_UPLOAD"),
			"TITLE" => ($arParams["SECTION_ID"] > 0 ? GetMessage("WD_UPLOAD_ALT") : GetMessage("WD_UPLOAD_ROOT_ALT")),
			"LINK" => "javascript:BX.reload('".CUtil::JSEscape($arResult["URL"]["ELEMENT"]["UPLOAD"])."');",
			"ICON" => "btn-new element-upload"); 

		if ($arParams["SHOW_CREATE_LINK"] != "N" && $arParams["PERMISSION"] >= "W" && $arParams["CHECK_CREATOR"] != "Y")
		{
			$arButtons[] = array(
				"TEXT" => GetMessage("WD_SECTION_ADD"),
				"TITLE" => GetMessage("WD_SECTION_ADD_ALT"),
				"LINK" => "javascript:".$APPLICATION->GetPopupLink(
					Array(
						"URL"=> WDAddPageParams(
							$arResult["URL"]["SECTION"]["~POPUP_ADD"], 
							array("use_light_view" => "Y"), 
							false),
						"PARAMS" => Array("width" => 450, "height" => 200)
					)
				), 
				"ICON" => "btn-new section-add"
			); 
		}
	}
	if ($arResult["USER"]["SHOW"]["SUBSCRIBE"] == "Y")
	{
		if ($arResult["USER"]["SUBSCRIBE"]["FORUM"] == "Y")
		{
			$arButtons[] = array(
				"TEXT" => GetMessage("WD_UNSUBSCRIBE"),
				"TITLE" => GetMessage("WD_UNSUBSCRIBE_FROM_FORUM"),
				"LINK" => "javascript:jsUtils.Redirect({}, '".CUtil::JSEscape($arResult["URL"]["UNSUBSCRIBE"])."');",
				"ICON" => "btn-delete unsubscribe"); 
		}
		else
		{
			$arButtons[] = array(
				"TEXT" => GetMessage("WD_SUBSCRIBE"),
				"TITLE" => GetMessage("WD_SUBSCRIBE_TO_FORUM"),
				"LINK" => $arResult["URL"]["SUBSCRIBE"],
				"ICON" => "btn-new subscribe"); 
		}
	}
	if ($arParams["SHOW_WEBDAV"] == "Y" && $bIsIE)
	{
		$arButtons[] = array(
			"TEXT" => GetMessage("WD_MAPING"),
			"TITLE" => GetMessage("WD_MAPING_ALT"),
			"LINK" => "javascript:WDMappingDrive('".CUtil::JSEscape(str_replace(":443", "", $arParams["BASE_URL"]))."');",
			"ICON" => "btn-list mapping"); 
	}
	$arButtons[] = array(
		"TEXT" => GetMessage("WD_HELP"),
		"TITLE" => GetMessage("WD_HELP_ALT"),
		"LINK" => $arResult["URL"]["HELP"],
		"ICON" => "btn-list help"); 

	if (($arParams["USE_BIZPROC"] == "Y" && $arParams["PERMISSION"] > "U" && $arParams["CHECK_CREATOR"] != "Y") || 
		($this->__component->__parent && is_array($this->__component->__parent->arResult["arButtons"])))
	{
		$arButtons[] = array("NEWBAR" => true); 
	}
	if ($arParams["USE_BIZPROC"] == "Y" && $arParams["PERMISSION"] > "U" && $arParams["CHECK_CREATOR"] != "Y")
	{
		$arButtons[] = array(
			"TEXT" => GetMessage("WD_BP"),
			"TITLE" => GetMessage("WD_BP"),
			"LINK" => $arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_ADMIN"],
			"ICON" => "btn-list bizproc"); 
	}
}

if (empty($arButtons))
{
	$arButtons[] = array(
		"TEXT" => GetMessage("WD_GO_BACK"),
		"TITLE" => GetMessage("WD_GO_BACK_ALT"),
		"LINK" => $arResult["URL"]["SECTION"]["UP"],
		"ICON" => "btn-list sections"); 
}

if ($this->__component->__parent && is_array($this->__component->__parent->arResult["arButtons"]))
{
	foreach ($this->__component->__parent->arResult["arButtons"] as $arButton)
	{
		$arButtons[] = $arButton; 
	}
}

if ($arParams["SHOW_WEBDAV"] == "Y"):
?>
<script>
if (document.attachEvent && navigator.userAgent.toLowerCase().indexOf('opera') == -1)
{
	if (document.getElementById('wd_create_in_ie'))
		document.getElementById('wd_create_in_ie').style.display = '';
	if (document.getElementById('wd_create_in_ie_separator'))
		document.getElementById('wd_create_in_ie_separator').style.display = '';
	if (document.getElementById('wd_map_in_ie'))
		document.getElementById('wd_map_in_ie').style.display = '';
	if (document.getElementById('wd_map_in_ie_separator'))
		document.getElementById('wd_map_in_ie_separator').style.display = '';
}
function WDMappingDrive(path)
{
	if (!jsUtils.IsIE())
	{
		return false;
	}
	if (!path || path.length <= 0)
	{
		alert('<?=GetMessage("WD_EMPTY_PATH")?>');
		return false;
	}

	var sizer = false;
	var text = '';
	var src = "";
	sizer = window.open("",'',"height=600,width=800,top=0,left=0");

	text = '<HTML><BODY>' +
			'<SPAN ID="oWebFolder" style="BEHAVIOR:url(#default#httpFolder)">' +
				'<?=CUtil::JSEscape(str_replace("#BASE_URL#", str_replace(":443", "", $arParams["BASE_URL"]), GetMessage("WD_HELP_TEXT")))?>' +
			'</SPAN>' +
		'<script>' +
			'var res = oWebFolder.navigate(\'' + path + '\');' +
		'<' + '/' + 'script' + '>' +
		'</BODY></HTML>';
	sizer.document.write(text);
}
</script>
<?
endif;
?>
<script>
if (typeof oText != "object")
	var oText = {};
oText['error_create_1'] = '<?=CUtil::JSEscape(GetMessage("WD_ERROR_1"))?>';
oText['error_create_2'] = '<?=CUtil::JSEscape(GetMessage("WD_ERROR_2"))?>';
oText['message01'] = '<?=CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRM"))?>';
</script><?

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS" => $arButtons
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);
?>