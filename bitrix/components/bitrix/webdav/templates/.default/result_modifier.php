<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arResult["MENU_VARIABLES"] = array();
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/result_modifier.php")));
__IncludeLang($file);

if ($this->__page !== "menu"):
?>
<script type="text/javascript">
if (typeof(phpVars) != "object")
	phpVars = {};
if (!phpVars.titlePrefix)
	phpVars.titlePrefix = '<?=CUtil::JSEscape(COption::GetOptionString("main", "site_name", $_SERVER["SERVER_NAME"]))?> - ';
if (!phpVars.messLoading)
	phpVars.messLoading = '<?=CUtil::JSEscape(GetMessage("WD_LOADING"))?>';
if (!phpVars.ADMIN_THEME_ID)
	phpVars.ADMIN_THEME_ID = '.default';
if (!phpVars.bitrix_sessid)
	phpVars.bitrix_sessid = '<?=bitrix_sessid()?>';
if (!phpVars.cookiePrefix)
	phpVars.cookiePrefix = '<?=CUtil::JSEscape(COption::GetOptionString("main", "cookie_name", "BITRIX_SM"))?>';
if (typeof oObjectWD != "object")
	var oObjectWD = {};
</script>
<?
endif;

if ($this->__page == "section_edit_simple"): 
	$this->__component->__page_webdav_template = $this->__page;
	$this->__component->__template_is_buffering = false; 
elseif (in_array($this->__page, array("element_upload", "disk_element_upload", "webdav_bizproc_log", "webdav_task", "webdav_task_list"))): 
	$this->__component->__page_webdav_template = $this->__page;
	$this->__component->__page_webdav_chain_items = count($APPLICATION->arAdditionalChain); 
	$sTempatePage = $this->__page;
	$sTempateFile = $this->__file;
	$this->__component->IncludeComponentTemplate("menu");
	$this->__page = $sTempatePage;
	$this->__file = $sTempateFile;
elseif (!(in_array($this->__page, array("menu", "section_edit_simple", "disk_section_edit_simple")) || 
	$this->__page == "webdav_bizproc_workflow_edit" && $_REQUEST["export_template"] = "Y" && check_bitrix_sessid())):
	ob_start();
	$this->__component->__page_webdav_template = $this->__page; 
	$this->__component->__page_webdav_chain_items = count($APPLICATION->arAdditionalChain); 
	$this->__component->__template_is_buffering = true; 
endif;
?>
