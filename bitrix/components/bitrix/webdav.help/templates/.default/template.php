<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/".LANGUAGE_ID."/template2.php");
if (false && !file_exists($path))
{
	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/en/template2.php");
}
@include_once($path);
?>