<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\Session\Legacy\HealerEarlySessionStart;

require_once(mb_substr(__FILE__, 0, mb_strlen(__FILE__) - mb_strlen("/include.php"))."/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/start.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_io.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_file.php");

$application = \Bitrix\Main\Application::getInstance();
$application->initializeExtendedKernel(array(
	"get" => $_GET,
	"post" => $_POST,
	"files" => $_FILES,
	"cookie" => $_COOKIE,
	"server" => $_SERVER,
	"env" => $_ENV
));

//define global application object
$GLOBALS["APPLICATION"] = new CMain;

if(defined("SITE_ID"))
	define("LANG", SITE_ID);

if(defined("LANG"))
{
	if(defined("ADMIN_SECTION") && ADMIN_SECTION===true)
		$db_lang = CLangAdmin::GetByID(LANG);
	else
		$db_lang = CLang::GetByID(LANG);

	$arLang = $db_lang->Fetch();

	if(!$arLang)
	{
		throw new \Bitrix\Main\SystemException("Incorrect site: ".LANG.".");
	}
}
else
{
	$arLang = $GLOBALS["APPLICATION"]->GetLang();
	define("LANG", $arLang["LID"]);
}

if($arLang["CULTURE_ID"] == '')
{
	throw new \Bitrix\Main\SystemException("Culture not found, or there are no active sites or languages.");
}

$lang = $arLang["LID"];
if (!defined("SITE_ID"))
	define("SITE_ID", $arLang["LID"]);
define("SITE_DIR", $arLang["DIR"]);
define("SITE_SERVER_NAME", $arLang["SERVER_NAME"]);
define("SITE_CHARSET", $arLang["CHARSET"]);
define("FORMAT_DATE", $arLang["FORMAT_DATE"]);
define("FORMAT_DATETIME", $arLang["FORMAT_DATETIME"]);
define("LANG_DIR", $arLang["DIR"]);
define("LANG_CHARSET", $arLang["CHARSET"]);
define("LANG_ADMIN_LID", $arLang["LANGUAGE_ID"]);
define("LANGUAGE_ID", $arLang["LANGUAGE_ID"]);

$culture = \Bitrix\Main\Localization\CultureTable::getByPrimary($arLang["CULTURE_ID"], ["cache" => ["ttl" => CACHED_b_lang]])->fetchObject();

$context = $application->getContext();
$context->setLanguage(LANGUAGE_ID);
$context->setCulture($culture);

$request = $context->getRequest();
if (!$request->isAdminSection())
{
	$context->setSite(SITE_ID);
}

$application->start();

$GLOBALS["APPLICATION"]->reinitPath();

if (!defined("POST_FORM_ACTION_URI"))
{
	define("POST_FORM_ACTION_URI", htmlspecialcharsbx(GetRequestUri()));
}

$GLOBALS["MESS"] = array();
$GLOBALS["ALL_LANG_FILES"] = array();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/tools.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/database.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/main.php");
IncludeModuleLangFile(__FILE__);

error_reporting(COption::GetOptionInt("main", "error_reporting", E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE) & ~E_STRICT & ~E_DEPRECATED);

if(!defined("BX_COMP_MANAGED_CACHE") && COption::GetOptionString("main", "component_managed_cache_on", "Y") <> "N")
{
	define("BX_COMP_MANAGED_CACHE", true);
}

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/filter_tools.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/ajax_tools.php");

/*ZDUyZmZOTYyNjZlZTA2NzIwZWE0OTg1NDljMGVjNjczMDRhMDY=*/$GLOBALS['_____1938639391']= array(base64_decode(''.'R2V0TW9kdW'.'xl'.'RXZlbn'.'Rz'),base64_decode('R'.'Xh'.'lY3V'.'0ZU'.'1'.'v'.'ZH'.'VsZ'.'UV2'.'Z'.'W50RXg='));$GLOBALS['____1772201832']= array(base64_decode('ZGV'.'maW5l'),base64_decode('c3'.'RybGVu'),base64_decode('Y'.'mF'.'zZTY0X2RlY'.'29k'.'ZQ=='),base64_decode('d'.'W5zZ'.'XJp'.'YWx'.'pemU='),base64_decode('aXNfYXJ'.'yYXk='),base64_decode('Y29'.'1bnQ='),base64_decode(''.'aW'.'5'.'fY'.'XJyYXk='),base64_decode('c2V'.'yaWFsaX'.'pl'),base64_decode(''.'YmFz'.'ZTY'.'0X2Vu'.'Y'.'29kZQ=='),base64_decode(''.'c3R'.'y'.'bG'.'Vu'),base64_decode('YXJyYXlfa2V5X2V4'.'aXN0cw=='),base64_decode('YXJyYXlf'.'a2'.'V'.'5X2V4a'.'XN0cw=='),base64_decode('b'.'Wt0a'.'W1l'),base64_decode('ZGF'.'0Z'.'Q='.'='),base64_decode('ZGF0Z'.'Q'.'=='),base64_decode('Y'.'XJ'.'yYX'.'lfa2'.'V5X2V4'.'a'.'XN'.'0'.'cw=='),base64_decode('c3RybG'.'Vu'),base64_decode('YXJyYX'.'l'.'fa'.'2V5X'.'2V4aXN0cw='.'='),base64_decode('c3RybGVu'),base64_decode(''.'YXJyYXlfa2V5X2V4aXN0cw=='),base64_decode('YXJ'.'yYX'.'l'.'fa2'.'V5'.'X2V4aXN0cw='.'='),base64_decode('bWt'.'0aW1l'),base64_decode('ZG'.'F0ZQ=='),base64_decode('ZGF0ZQ=='),base64_decode('bWV0'.'a'.'G9'.'kX2V4aX'.'N'.'0c'.'w=='),base64_decode('Y2FsbF9'.'1'.'c2VyX'.'2Z1b'.'mNfY'.'XJyYXk'.'='),base64_decode(''.'c'.'3RybGV'.'u'),base64_decode(''.'Y'.'XJ'.'yY'.'Xlfa2V5X'.'2V4aXN'.'0'.'cw=='),base64_decode('YXJyY'.'Xlf'.'a2V5X2'.'V'.'4aX'.'N0'.'cw'.'=='),base64_decode('c'.'2VyaWFsa'.'X'.'p'.'l'),base64_decode('YmFzZT'.'Y'.'0'.'X'.'2'.'V'.'uY2'.'9kZQ=='),base64_decode('c'.'3Ry'.'b'.'GVu'),base64_decode('YX'.'JyYX'.'lf'.'a'.'2'.'V5'.'X2V4aXN'.'0cw='.'='),base64_decode('YXJyYXlfa'.'2V5X2V4aXN0'.'cw=='),base64_decode('YXJ'.'y'.'YXlfa2V5'.'X2V'.'4aXN0cw='.'='),base64_decode(''.'a'.'X'.'NfYXJy'.'Y'.'Xk='),base64_decode('Y'.'XJ'.'yYXlfa2'.'V'.'5X'.'2V4aXN0cw='.'='),base64_decode('c2VyaWFsaXpl'),base64_decode(''.'YmFzZTY0X2'.'Vu'.'Y'.'29kZQ=='),base64_decode('YXJy'.'YX'.'lfa2'.'V'.'5X2V4aXN0cw='.'='),base64_decode('YX'.'JyYXlf'.'a'.'2V5X2V4aXN0cw=='),base64_decode('c2'.'VyaWFsaXpl'),base64_decode('Ym'.'FzZTY0X2'.'VuY2'.'9'.'kZQ=='),base64_decode('aXNfYX'.'J'.'yYXk='),base64_decode(''.'aXNfYXJyYX'.'k='),base64_decode('aW5fY'.'XJyYXk='),base64_decode('YX'.'JyYXlfa2V5X2V4aXN0cw'.'=='),base64_decode('a'.'W5fYXJ'.'yY'.'Xk='),base64_decode('bWt0aW1l'),base64_decode('ZGF'.'0ZQ=='),base64_decode('ZGF0ZQ=='),base64_decode(''.'Z'.'GF'.'0ZQ=='),base64_decode('bW'.'t'.'0aW1l'),base64_decode('Z'.'GF'.'0ZQ=='),base64_decode(''.'ZGF0'.'ZQ='.'='),base64_decode('aW5fYXJy'.'YXk'.'='),base64_decode(''.'YXJ'.'yY'.'Xlfa2V5X'.'2V4aXN0cw'.'=='),base64_decode('Y'.'X'.'JyYXlf'.'a2V'.'5X2V'.'4aXN'.'0cw=='),base64_decode('c2VyaW'.'F'.'saX'.'pl'),base64_decode('Y'.'mFzZTY0X2Vu'.'Y29kZQ'.'=='),base64_decode('YXJ'.'y'.'YX'.'l'.'fa2V5X2V4'.'a'.'XN0cw=='),base64_decode('aW'.'50d'.'mFs'),base64_decode('dGltZQ=='),base64_decode('YXJy'.'YXlfa2'.'V5X2V4aXN0'.'c'.'w=='),base64_decode(''.'Zmls'.'ZV'.'9leG'.'l'.'z'.'dHM='),base64_decode('c3RyX3'.'JlcGx'.'hY2'.'U='),base64_decode('Y2xhc3NfZXhpc3Rz'),base64_decode(''.'ZGVma'.'W5l'));if(!function_exists(__NAMESPACE__.'\\___222624854')){function ___222624854($_1721037750){static $_363539088= false; if($_363539088 == false) $_363539088=array('SU5UUkFO'.'R'.'VRfRURJV'.'E'.'lPTg==','WQ==','bWFpbg==','f'.'mNwZl9tYXBf'.'d'.'mFsdWU=','','ZQ==','Zg==','ZQ==','Rg==','W'.'A==','Zg==','b'.'WFpbg==','fmNwZ'.'l9'.'tYX'.'BfdmFsd'.'W'.'U=','UG9ydGFs','Rg'.'==',''.'ZQ==','ZQ'.'==','W'.'A==','R'.'g==','RA='.'=','RA==','bQ='.'=','ZA'.'==','WQ'.'==','Z'.'g'.'==',''.'Zg==','Zg==','Zg='.'=',''.'UG9y'.'d'.'GF'.'s',''.'Rg'.'==',''.'Z'.'Q'.'==','ZQ==',''.'WA'.'==','Rg==',''.'RA'.'==',''.'RA==','bQ='.'=','ZA==','WQ==','bWFpbg==','T24=','U'.'2'.'V0dGlu'.'Z3N'.'DaGFuZ2U=',''.'Zg'.'==','Zg==','Zg='.'=','Zg='.'=',''.'bWF'.'p'.'b'.'g==',''.'fmN'.'wZ'.'l9'.'tYXBfd'.'m'.'F'.'sdWU=',''.'Z'.'Q'.'==','ZQ='.'=','ZQ='.'=','RA='.'=','ZQ='.'=','ZQ'.'==','Zg'.'==',''.'Zg==','Z'.'g==','ZQ==','b'.'WF'.'p'.'bg==','fmNwZl9tYXBf'.'dmFsd'.'WU=','ZQ==','Zg='.'=','Zg==','Z'.'g==',''.'Zg'.'==','bW'.'Fpbg'.'='.'=',''.'fmNwZ'.'l9tYXBfd'.'mFsd'.'WU=','ZQ==','Zg==','UG9'.'ydG'.'Fs','UG'.'9y'.'d'.'GF'.'s','Z'.'Q==','ZQ==','UG9ydGF'.'s','Rg==','WA==','Rg==','R'.'A'.'==',''.'ZQ==','ZQ='.'=','R'.'A'.'='.'=','b'.'Q='.'=','ZA==','WQ='.'=',''.'Z'.'Q==','WA==','ZQ==','Rg'.'==','ZQ==','RA'.'='.'=','Zg'.'==','ZQ='.'=','R'.'A==',''.'Z'.'Q==',''.'b'.'Q==','Z'.'A==','WQ='.'=',''.'Zg==','Zg==','Zg'.'==','Z'.'g==','Zg='.'=','Zg'.'==','Zg==','Zg==',''.'bW'.'F'.'pbg==','fm'.'NwZl9t'.'Y'.'XBfdm'.'FsdWU=','ZQ==','ZQ==','UG'.'9'.'ydGFs','Rg==','WA'.'='.'=',''.'VFlQR'.'Q==','REFURQ==','RkVBV'.'FV'.'SRVM'.'=',''.'RV'.'h'.'QS'.'VJFRA'.'==',''.'VFlQRQ==','RA'.'==','V'.'FJ'.'ZX0'.'R'.'B'.'WV'.'NfQ09VTlQ=','R'.'E'.'FU'.'RQ==','VFJZX0'.'RBWVNf'.'Q09V'.'T'.'lQ'.'=','RVhQSVJFRA'.'='.'=','RkVBVFVSRVM=','Zg='.'=','Zg'.'='.'=','RE9D'.'VU'.'1FTl'.'RfU'.'k9'.'PVA==','L2'.'Jpd'.'H'.'J'.'p'.'eC9tb2R1bGVzLw==','L2'.'luc3RhbGw'.'vaW5'.'kZXgucGhw',''.'Lg==','Xw==',''.'c2Vhc'.'mNo','Tg'.'==','','','Q'.'UNUSV'.'ZF','WQ==','c29jaWF'.'sbmV'.'0d'.'29ya'.'w==','YW'.'xs'.'b3dfZnJpZW'.'xkcw==','WQ==','SUQ=','c29jaWF'.'sbmV0d29y'.'aw='.'=',''.'Y'.'Wxs'.'b3dfZnJ'.'pZWxkcw==','SU'.'Q=','c29jaWFsbmV0d'.'29y'.'aw'.'==','YWxsb'.'3'.'d'.'fZ'.'n'.'JpZWx'.'kcw'.'==',''.'Tg==','','','QUN'.'USVZF','WQ==','c'.'29jaWF'.'sbmV0d29yaw==',''.'YWxsb3'.'dfbW'.'ljcm9ib'.'G9nX3Vz'.'Z'.'XI=','WQ==','S'.'UQ=',''.'c29'.'j'.'aW'.'FsbmV'.'0d29yaw==','YWxs'.'b'.'3dfbWljc'.'m9ibG'.'9nX3V'.'zZ'.'XI=','S'.'UQ=',''.'c2'.'9'.'jaWFsbmV0d29'.'ya'.'w==','YWxsb'.'3dfbW'.'l'.'jcm9i'.'bG9'.'nX3Vz'.'ZXI'.'=',''.'c29'.'j'.'aWF'.'sb'.'mV0d2'.'9ya'.'w==','YW'.'xsb3'.'dfbW'.'lj'.'cm9ibG'.'9'.'n'.'X'.'2dyb3'.'Vw','WQ==','SUQ=','c29'.'jaW'.'FsbmV0d29yaw==','Y'.'Wxs'.'b3'.'dfbWljcm9ibG9'.'nX2'.'dyb'.'3Vw','SUQ=','c'.'29ja'.'WFsbmV0d'.'29yaw==','YWxs'.'b3df'.'bWljcm9ibG9nX2dyb3Vw','Tg'.'==','','','Q'.'UNUSVZF',''.'WQ'.'==','c29'.'ja'.'WFsbm'.'V0d2'.'9'.'yaw='.'=','YW'.'xsb3'.'d'.'fZ'.'ml'.'sZ'.'XN'.'fdXNlcg==','WQ==','SU'.'Q=','c29jaWF'.'sbm'.'V0d2'.'9ya'.'w'.'==','YWxs'.'b3dfZml'.'sZXNfd'.'XNlcg==','SU'.'Q=','c29jaWFsbm'.'V0d29yaw==',''.'YWxsb3dfZmls'.'Z'.'XNfdXNl'.'cg'.'==','Tg==','','','Q'.'UNUSVZF','WQ'.'==','c'.'29jaWFs'.'b'.'mV0d29yaw==','YWx'.'sb3dfYmx'.'vZ19'.'1c2Vy',''.'W'.'Q==',''.'SUQ=','c29j'.'aWFsbmV0d'.'29'.'yaw'.'='.'=','YW'.'xsb3dfYmxvZ191c2'.'Vy',''.'SU'.'Q'.'=','c29jaWFsbmV0d29'.'yaw'.'==','YW'.'xsb3d'.'fYmx'.'vZ19'.'1c'.'2V'.'y','Tg==','','','QUNUSVZ'.'F','WQ='.'=',''.'c2'.'9jaWFsbmV0d29yaw==',''.'YWxsb3dfcGhvdG9f'.'dXNlcg='.'=','WQ==','SUQ'.'=','c'.'29j'.'aWFs'.'bmV0d29yaw==','YWxsb3dfc'.'Gh'.'v'.'d'.'G'.'9fdXNl'.'cg==','SUQ=',''.'c29jaWFsb'.'mV0d29y'.'aw='.'=',''.'Y'.'Wxsb3'.'d'.'fcGhvdG9'.'fdX'.'Nlc'.'g==',''.'Tg'.'==','','','Q'.'UNUSV'.'ZF','W'.'Q==','c29jaWFsbm'.'V'.'0'.'d'.'29yaw='.'=','YWxs'.'b3dfZm9y'.'d'.'W1'.'fd'.'XNlcg==','W'.'Q==','SU'.'Q=','c29j'.'aWFsbmV0d'.'29yaw'.'==','YW'.'x'.'sb3d'.'fZm9'.'y'.'dW'.'1f'.'dXNlcg==',''.'SU'.'Q=','c29ja'.'W'.'Fsb'.'mV0d29y'.'aw==',''.'Y'.'Wxsb3'.'dfZm9ydW1fdXNl'.'cg='.'=','Tg='.'=','','','QU'.'NUS'.'VZF','WQ==',''.'c29j'.'aWFsbmV0'.'d2'.'9yaw==','YWxsb3d'.'f'.'dGFza3'.'NfdXNl'.'cg==','WQ'.'='.'=','SUQ=','c29'.'jaWFsbmV0'.'d29y'.'aw==',''.'Y'.'W'.'xsb3dfdGFza3NfdXNlcg==',''.'SUQ=','c29'.'ja'.'WFsbmV'.'0d29y'.'aw='.'=','YWxsb3df'.'dGFza3'.'NfdXNlcg==','c2'.'9'.'jaWFs'.'b'.'mV0d29'.'yaw==','YW'.'xsb3'.'dfdGFza3N'.'fZ3JvdXA=','WQ==','SUQ=','c29jaWFsb'.'mV0d29yaw==','YWxs'.'b'.'3dfd'.'G'.'Fza3NfZ3Jv'.'d'.'XA=','SUQ'.'=',''.'c29jaWFsbmV0d'.'29yaw='.'=','YWxsb3df'.'dGFz'.'a3Nf'.'Z3J'.'vdX'.'A=','d'.'GF'.'za3M=','Tg==','','','QUN'.'USVZF','WQ==','c'.'29jaWFsbm'.'V0d29ya'.'w==','YWxsb3dfY'.'2'.'FsZ'.'W5kYXJfdXNlcg==',''.'WQ==',''.'SU'.'Q=','c2'.'9jaWFsbmV0d'.'29'.'yaw==','YWxsb3dfY2'.'Fs'.'ZW5k'.'YX'.'J'.'fdXNlcg==','S'.'U'.'Q'.'=','c2'.'9jaWFsbmV0d29'.'ya'.'w'.'==','YWxsb3dfY2F'.'sZW5kY'.'X'.'JfdXNl'.'cg='.'=','c29j'.'a'.'WFsbmV0d'.'29yaw==',''.'YWxs'.'b3d'.'fY2FsZW5kYXJfZ3J'.'v'.'d'.'XA=','W'.'Q'.'='.'=','SUQ=','c2'.'9'.'j'.'a'.'W'.'Fs'.'b'.'mV0d'.'29yaw'.'==','YWxs'.'b3dfY'.'2FsZW'.'5'.'kYXJfZ'.'3JvdXA=',''.'SUQ=',''.'c29jaWFsbmV0d29yaw==','YWxsb'.'3dfY2FsZW5kYXJfZ3JvdXA=','QUNUSVZF','WQ==','Tg==',''.'ZXh0cmFuZX'.'Q'.'=','aWJ'.'sb2Nr','T'.'25'.'BZnRlckl'.'Cb'.'G9ja0V'.'s'.'ZW1lb'.'nR'.'VcGRhdGU=',''.'aW50cmF'.'uZXQ'.'=','Q0'.'l'.'udHJhbmV0'.'RXZlbn'.'RIY'.'W5'.'kbG'.'Vycw==','U1B'.'S'.'Z'.'Wdpc3Rlc'.'lVw'.'ZGF'.'0ZWRJ'.'dGV'.'t','Q0'.'lud'.'H'.'Jh'.'bmV0U2'.'hhcmV'.'w'.'b2'.'ludDo6QWdlbnR'.'MaXN0cygp'.'O'.'w==','aW50cmF'.'uZXQ=','T'.'g'.'==','Q0lud'.'HJhbmV'.'0U2h'.'hcm'.'Vwb'.'2'.'lud'.'Do6Q'.'Wdlb'.'nRRdW'.'V'.'1ZSgpOw'.'==','aW50'.'c'.'mFuZXQ=','Tg='.'=','Q0ludHJhbmV'.'0U2'.'hhc'.'mVwb2l'.'udD'.'o'.'6'.'QWdlbn'.'RVcGR'.'hdG'.'U'.'o'.'KTs=','aW50cmF'.'uZXQ=','T'.'g==','aWJsb2Nr','T25BZnR'.'l'.'ckl'.'CbG9'.'ja0'.'VsZW'.'1l'.'bn'.'RBZ'.'GQ=',''.'a'.'W'.'5'.'0cmFuZ'.'XQ=','Q0lu'.'dHJ'.'hbmV'.'0'.'RXZlbn'.'RIYW5kbG'.'V'.'y'.'c'.'w'.'='.'=',''.'U1BSZW'.'d'.'p'.'c3RlclV'.'wZG'.'F'.'0ZWRJ'.'dGVt','aWJsb2'.'N'.'r','T25BZnRlcklCbG9'.'ja'.'0VsZW1l'.'b'.'nRVcGR'.'h'.'dGU=',''.'aW50cmFu'.'Z'.'X'.'Q=','Q0lu'.'d'.'HJhbmV'.'0'.'RXZlbnR'.'IY'.'W'.'5kb'.'GVycw==',''.'U1B'.'SZ'.'Wdpc3R'.'l'.'clVw'.'ZGF0Z'.'WR'.'JdGV'.'t',''.'Q'.'0ludHJhbm'.'V0U'.'2h'.'hc'.'mV'.'wb2lu'.'dDo'.'6QWdl'.'bn'.'R'.'M'.'aXN0cy'.'gpOw==','a'.'W5'.'0cm'.'FuZXQ=','Q0ludHJ'.'hbmV0U2hh'.'cm'.'Vwb2'.'l'.'u'.'dD'.'o6'.'QWdlbnR'.'RdWV'.'1ZSgpOw='.'=','aW50cm'.'FuZXQ=','Q'.'0'.'l'.'udHJhbmV0U2'.'hh'.'cmVwb2ludDo6Q'.'WdlbnRVc'.'G'.'R'.'hdG'.'UoKTs=',''.'a'.'W50cm'.'FuZXQ=','Y'.'3'.'Jt','b'.'WFpb'.'g==','T25CZWZvcmVQcm9'.'s'.'b2c=','bWFpbg==',''.'Q1dpemFyZFNvbFBhbmVsS'.'W'.'50cm'.'FuZXQ=','U2hvd1Bhb'.'mVs',''.'L2'.'1'.'vZH'.'VsZXMvaW'.'5'.'0'.'cmF'.'uZX'.'Q'.'v'.'cGF'.'uZWxf'.'Yn'.'V0dG'.'9uLnBocA==','RU5DT0'.'RF','WQ==');return base64_decode($_363539088[$_1721037750]);}};$GLOBALS['____1772201832'][0](___222624854(0), ___222624854(1));class CBXFeatures{ private static $_2114246095= 30; private static $_492361414= array( "Portal" => array( "CompanyCalendar", "CompanyPhoto", "CompanyVideo", "CompanyCareer", "StaffChanges", "StaffAbsence", "CommonDocuments", "MeetingRoomBookingSystem", "Wiki", "Learning", "Vote", "WebLink", "Subscribe", "Friends", "PersonalFiles", "PersonalBlog", "PersonalPhoto", "PersonalForum", "Blog", "Forum", "Gallery", "Board", "MicroBlog", "WebMessenger",), "Communications" => array( "Tasks", "Calendar", "Workgroups", "Jabber", "VideoConference", "Extranet", "SMTP", "Requests", "DAV", "intranet_sharepoint", "timeman", "Idea", "Meeting", "EventList", "Salary", "XDImport",), "Enterprise" => array( "BizProc", "Lists", "Support", "Analytics", "crm", "Controller", "LdapUnlimitedUsers",), "Holding" => array( "Cluster", "MultiSites",),); private static $_343614719= false; private static $_1145777662= false; private static function __1848346580(){ if(self::$_343614719 == false){ self::$_343614719= array(); foreach(self::$_492361414 as $_436602692 => $_1557976702){ foreach($_1557976702 as $_80673935) self::$_343614719[$_80673935]= $_436602692;}} if(self::$_1145777662 == false){ self::$_1145777662= array(); $_969182878= COption::GetOptionString(___222624854(2), ___222624854(3), ___222624854(4)); if($GLOBALS['____1772201832'][1]($_969182878)>(129*2-258)){ $_969182878= $GLOBALS['____1772201832'][2]($_969182878); self::$_1145777662= $GLOBALS['____1772201832'][3]($_969182878); if(!$GLOBALS['____1772201832'][4](self::$_1145777662)) self::$_1145777662= array();} if($GLOBALS['____1772201832'][5](self::$_1145777662) <=(1116/2-558)) self::$_1145777662= array(___222624854(5) => array(), ___222624854(6) => array());}} public static function InitiateEditionsSettings($_88386896){ self::__1848346580(); $_2123657414= array(); foreach(self::$_492361414 as $_436602692 => $_1557976702){ $_1437152469= $GLOBALS['____1772201832'][6]($_436602692, $_88386896); self::$_1145777662[___222624854(7)][$_436602692]=($_1437152469? array(___222624854(8)): array(___222624854(9))); foreach($_1557976702 as $_80673935){ self::$_1145777662[___222624854(10)][$_80673935]= $_1437152469; if(!$_1437152469) $_2123657414[]= array($_80673935, false);}} $_311405049= $GLOBALS['____1772201832'][7](self::$_1145777662); $_311405049= $GLOBALS['____1772201832'][8]($_311405049); COption::SetOptionString(___222624854(11), ___222624854(12), $_311405049); foreach($_2123657414 as $_899048796) self::__660819525($_899048796[(229*2-458)], $_899048796[round(0+0.25+0.25+0.25+0.25)]);} public static function IsFeatureEnabled($_80673935){ if($GLOBALS['____1772201832'][9]($_80673935) <= 0) return true; self::__1848346580(); if(!$GLOBALS['____1772201832'][10]($_80673935, self::$_343614719)) return true; if(self::$_343614719[$_80673935] == ___222624854(13)) $_179144333= array(___222624854(14)); elseif($GLOBALS['____1772201832'][11](self::$_343614719[$_80673935], self::$_1145777662[___222624854(15)])) $_179144333= self::$_1145777662[___222624854(16)][self::$_343614719[$_80673935]]; else $_179144333= array(___222624854(17)); if($_179144333[(232*2-464)] != ___222624854(18) && $_179144333[min(182,0,60.666666666667)] != ___222624854(19)){ return false;} elseif($_179144333[(170*2-340)] == ___222624854(20)){ if($_179144333[round(0+0.33333333333333+0.33333333333333+0.33333333333333)]< $GLOBALS['____1772201832'][12](min(218,0,72.666666666667),(1148/2-574),(764-2*382), Date(___222624854(21)), $GLOBALS['____1772201832'][13](___222624854(22))- self::$_2114246095, $GLOBALS['____1772201832'][14](___222624854(23)))){ if(!isset($_179144333[round(0+1+1)]) ||!$_179144333[round(0+0.66666666666667+0.66666666666667+0.66666666666667)]) self::__2043341823(self::$_343614719[$_80673935]); return false;}} return!$GLOBALS['____1772201832'][15]($_80673935, self::$_1145777662[___222624854(24)]) || self::$_1145777662[___222624854(25)][$_80673935];} public static function IsFeatureInstalled($_80673935){ if($GLOBALS['____1772201832'][16]($_80673935) <= 0) return true; self::__1848346580(); return($GLOBALS['____1772201832'][17]($_80673935, self::$_1145777662[___222624854(26)]) && self::$_1145777662[___222624854(27)][$_80673935]);} public static function IsFeatureEditable($_80673935){ if($GLOBALS['____1772201832'][18]($_80673935) <= 0) return true; self::__1848346580(); if(!$GLOBALS['____1772201832'][19]($_80673935, self::$_343614719)) return true; if(self::$_343614719[$_80673935] == ___222624854(28)) $_179144333= array(___222624854(29)); elseif($GLOBALS['____1772201832'][20](self::$_343614719[$_80673935], self::$_1145777662[___222624854(30)])) $_179144333= self::$_1145777662[___222624854(31)][self::$_343614719[$_80673935]]; else $_179144333= array(___222624854(32)); if($_179144333[min(210,0,70)] != ___222624854(33) && $_179144333[(850-2*425)] != ___222624854(34)){ return false;} elseif($_179144333[min(116,0,38.666666666667)] == ___222624854(35)){ if($_179144333[round(0+0.5+0.5)]< $GLOBALS['____1772201832'][21]((1496/2-748),(228*2-456),(133*2-266), Date(___222624854(36)), $GLOBALS['____1772201832'][22](___222624854(37))- self::$_2114246095, $GLOBALS['____1772201832'][23](___222624854(38)))){ if(!isset($_179144333[round(0+0.5+0.5+0.5+0.5)]) ||!$_179144333[round(0+1+1)]) self::__2043341823(self::$_343614719[$_80673935]); return false;}} return true;} private static function __660819525($_80673935, $_2124838038){ if($GLOBALS['____1772201832'][24]("CBXFeatures", "On".$_80673935."SettingsChange")) $GLOBALS['____1772201832'][25](array("CBXFeatures", "On".$_80673935."SettingsChange"), array($_80673935, $_2124838038)); $_265277242= $GLOBALS['_____1938639391'][0](___222624854(39), ___222624854(40).$_80673935.___222624854(41)); while($_20302915= $_265277242->Fetch()) $GLOBALS['_____1938639391'][1]($_20302915, array($_80673935, $_2124838038));} public static function SetFeatureEnabled($_80673935, $_2124838038= true, $_568425031= true){ if($GLOBALS['____1772201832'][26]($_80673935) <= 0) return; if(!self::IsFeatureEditable($_80673935)) $_2124838038= false; $_2124838038=($_2124838038? true: false); self::__1848346580(); $_804471886=(!$GLOBALS['____1772201832'][27]($_80673935, self::$_1145777662[___222624854(42)]) && $_2124838038 || $GLOBALS['____1772201832'][28]($_80673935, self::$_1145777662[___222624854(43)]) && $_2124838038 != self::$_1145777662[___222624854(44)][$_80673935]); self::$_1145777662[___222624854(45)][$_80673935]= $_2124838038; $_311405049= $GLOBALS['____1772201832'][29](self::$_1145777662); $_311405049= $GLOBALS['____1772201832'][30]($_311405049); COption::SetOptionString(___222624854(46), ___222624854(47), $_311405049); if($_804471886 && $_568425031) self::__660819525($_80673935, $_2124838038);} private static function __2043341823($_436602692){ if($GLOBALS['____1772201832'][31]($_436602692) <= 0 || $_436602692 == "Portal") return; self::__1848346580(); if(!$GLOBALS['____1772201832'][32]($_436602692, self::$_1145777662[___222624854(48)]) || $GLOBALS['____1772201832'][33]($_436602692, self::$_1145777662[___222624854(49)]) && self::$_1145777662[___222624854(50)][$_436602692][(1220/2-610)] != ___222624854(51)) return; if(isset(self::$_1145777662[___222624854(52)][$_436602692][round(0+1+1)]) && self::$_1145777662[___222624854(53)][$_436602692][round(0+0.4+0.4+0.4+0.4+0.4)]) return; $_2123657414= array(); if($GLOBALS['____1772201832'][34]($_436602692, self::$_492361414) && $GLOBALS['____1772201832'][35](self::$_492361414[$_436602692])){ foreach(self::$_492361414[$_436602692] as $_80673935){ if($GLOBALS['____1772201832'][36]($_80673935, self::$_1145777662[___222624854(54)]) && self::$_1145777662[___222624854(55)][$_80673935]){ self::$_1145777662[___222624854(56)][$_80673935]= false; $_2123657414[]= array($_80673935, false);}} self::$_1145777662[___222624854(57)][$_436602692][round(0+2)]= true;} $_311405049= $GLOBALS['____1772201832'][37](self::$_1145777662); $_311405049= $GLOBALS['____1772201832'][38]($_311405049); COption::SetOptionString(___222624854(58), ___222624854(59), $_311405049); foreach($_2123657414 as $_899048796) self::__660819525($_899048796[(230*2-460)], $_899048796[round(0+0.2+0.2+0.2+0.2+0.2)]);} public static function ModifyFeaturesSettings($_88386896, $_1557976702){ self::__1848346580(); foreach($_88386896 as $_436602692 => $_603608746) self::$_1145777662[___222624854(60)][$_436602692]= $_603608746; $_2123657414= array(); foreach($_1557976702 as $_80673935 => $_2124838038){ if(!$GLOBALS['____1772201832'][39]($_80673935, self::$_1145777662[___222624854(61)]) && $_2124838038 || $GLOBALS['____1772201832'][40]($_80673935, self::$_1145777662[___222624854(62)]) && $_2124838038 != self::$_1145777662[___222624854(63)][$_80673935]) $_2123657414[]= array($_80673935, $_2124838038); self::$_1145777662[___222624854(64)][$_80673935]= $_2124838038;} $_311405049= $GLOBALS['____1772201832'][41](self::$_1145777662); $_311405049= $GLOBALS['____1772201832'][42]($_311405049); COption::SetOptionString(___222624854(65), ___222624854(66), $_311405049); self::$_1145777662= false; foreach($_2123657414 as $_899048796) self::__660819525($_899048796[(182*2-364)], $_899048796[round(0+0.25+0.25+0.25+0.25)]);} public static function SaveFeaturesSettings($_1368890432, $_629721685){ self::__1848346580(); $_2071829115= array(___222624854(67) => array(), ___222624854(68) => array()); if(!$GLOBALS['____1772201832'][43]($_1368890432)) $_1368890432= array(); if(!$GLOBALS['____1772201832'][44]($_629721685)) $_629721685= array(); if(!$GLOBALS['____1772201832'][45](___222624854(69), $_1368890432)) $_1368890432[]= ___222624854(70); foreach(self::$_492361414 as $_436602692 => $_1557976702){ if($GLOBALS['____1772201832'][46]($_436602692, self::$_1145777662[___222624854(71)])) $_1691676804= self::$_1145777662[___222624854(72)][$_436602692]; else $_1691676804=($_436602692 == ___222624854(73))? array(___222624854(74)): array(___222624854(75)); if($_1691676804[(960-2*480)] == ___222624854(76) || $_1691676804[(904-2*452)] == ___222624854(77)){ $_2071829115[___222624854(78)][$_436602692]= $_1691676804;} else{ if($GLOBALS['____1772201832'][47]($_436602692, $_1368890432)) $_2071829115[___222624854(79)][$_436602692]= array(___222624854(80), $GLOBALS['____1772201832'][48]((1344/2-672),(780-2*390),(142*2-284), $GLOBALS['____1772201832'][49](___222624854(81)), $GLOBALS['____1772201832'][50](___222624854(82)), $GLOBALS['____1772201832'][51](___222624854(83)))); else $_2071829115[___222624854(84)][$_436602692]= array(___222624854(85));}} $_2123657414= array(); foreach(self::$_343614719 as $_80673935 => $_436602692){ if($_2071829115[___222624854(86)][$_436602692][(226*2-452)] != ___222624854(87) && $_2071829115[___222624854(88)][$_436602692][(1260/2-630)] != ___222624854(89)){ $_2071829115[___222624854(90)][$_80673935]= false;} else{ if($_2071829115[___222624854(91)][$_436602692][(250*2-500)] == ___222624854(92) && $_2071829115[___222624854(93)][$_436602692][round(0+1)]< $GLOBALS['____1772201832'][52](min(14,0,4.6666666666667),(812-2*406),(165*2-330), Date(___222624854(94)), $GLOBALS['____1772201832'][53](___222624854(95))- self::$_2114246095, $GLOBALS['____1772201832'][54](___222624854(96)))) $_2071829115[___222624854(97)][$_80673935]= false; else $_2071829115[___222624854(98)][$_80673935]= $GLOBALS['____1772201832'][55]($_80673935, $_629721685); if(!$GLOBALS['____1772201832'][56]($_80673935, self::$_1145777662[___222624854(99)]) && $_2071829115[___222624854(100)][$_80673935] || $GLOBALS['____1772201832'][57]($_80673935, self::$_1145777662[___222624854(101)]) && $_2071829115[___222624854(102)][$_80673935] != self::$_1145777662[___222624854(103)][$_80673935]) $_2123657414[]= array($_80673935, $_2071829115[___222624854(104)][$_80673935]);}} $_311405049= $GLOBALS['____1772201832'][58]($_2071829115); $_311405049= $GLOBALS['____1772201832'][59]($_311405049); COption::SetOptionString(___222624854(105), ___222624854(106), $_311405049); self::$_1145777662= false; foreach($_2123657414 as $_899048796) self::__660819525($_899048796[min(74,0,24.666666666667)], $_899048796[round(0+1)]);} public static function GetFeaturesList(){ self::__1848346580(); $_1524636505= array(); foreach(self::$_492361414 as $_436602692 => $_1557976702){ if($GLOBALS['____1772201832'][60]($_436602692, self::$_1145777662[___222624854(107)])) $_1691676804= self::$_1145777662[___222624854(108)][$_436602692]; else $_1691676804=($_436602692 == ___222624854(109))? array(___222624854(110)): array(___222624854(111)); $_1524636505[$_436602692]= array( ___222624854(112) => $_1691676804[min(46,0,15.333333333333)], ___222624854(113) => $_1691676804[round(0+0.5+0.5)], ___222624854(114) => array(),); $_1524636505[$_436602692][___222624854(115)]= false; if($_1524636505[$_436602692][___222624854(116)] == ___222624854(117)){ $_1524636505[$_436602692][___222624854(118)]= $GLOBALS['____1772201832'][61](($GLOBALS['____1772201832'][62]()- $_1524636505[$_436602692][___222624854(119)])/ round(0+28800+28800+28800)); if($_1524636505[$_436602692][___222624854(120)]> self::$_2114246095) $_1524636505[$_436602692][___222624854(121)]= true;} foreach($_1557976702 as $_80673935) $_1524636505[$_436602692][___222624854(122)][$_80673935]=(!$GLOBALS['____1772201832'][63]($_80673935, self::$_1145777662[___222624854(123)]) || self::$_1145777662[___222624854(124)][$_80673935]);} return $_1524636505;} private static function __461213863($_1166967634, $_993080613){ if(IsModuleInstalled($_1166967634) == $_993080613) return true; $_1025371956= $_SERVER[___222624854(125)].___222624854(126).$_1166967634.___222624854(127); if(!$GLOBALS['____1772201832'][64]($_1025371956)) return false; include_once($_1025371956); $_770163966= $GLOBALS['____1772201832'][65](___222624854(128), ___222624854(129), $_1166967634); if(!$GLOBALS['____1772201832'][66]($_770163966)) return false; $_360139702= new $_770163966; if($_993080613){ if(!$_360139702->InstallDB()) return false; $_360139702->InstallEvents(); if(!$_360139702->InstallFiles()) return false;} else{ if(CModule::IncludeModule(___222624854(130))) CSearch::DeleteIndex($_1166967634); UnRegisterModule($_1166967634);} return true;} protected static function OnRequestsSettingsChange($_80673935, $_2124838038){ self::__461213863("form", $_2124838038);} protected static function OnLearningSettingsChange($_80673935, $_2124838038){ self::__461213863("learning", $_2124838038);} protected static function OnJabberSettingsChange($_80673935, $_2124838038){ self::__461213863("xmpp", $_2124838038);} protected static function OnVideoConferenceSettingsChange($_80673935, $_2124838038){ self::__461213863("video", $_2124838038);} protected static function OnBizProcSettingsChange($_80673935, $_2124838038){ self::__461213863("bizprocdesigner", $_2124838038);} protected static function OnListsSettingsChange($_80673935, $_2124838038){ self::__461213863("lists", $_2124838038);} protected static function OnWikiSettingsChange($_80673935, $_2124838038){ self::__461213863("wiki", $_2124838038);} protected static function OnSupportSettingsChange($_80673935, $_2124838038){ self::__461213863("support", $_2124838038);} protected static function OnControllerSettingsChange($_80673935, $_2124838038){ self::__461213863("controller", $_2124838038);} protected static function OnAnalyticsSettingsChange($_80673935, $_2124838038){ self::__461213863("statistic", $_2124838038);} protected static function OnVoteSettingsChange($_80673935, $_2124838038){ self::__461213863("vote", $_2124838038);} protected static function OnFriendsSettingsChange($_80673935, $_2124838038){ if($_2124838038) $_673986744= "Y"; else $_673986744= ___222624854(131); $_854304672= CSite::GetList(($_1437152469= ___222624854(132)),($_218680045= ___222624854(133)), array(___222624854(134) => ___222624854(135))); while($_809782135= $_854304672->Fetch()){ if(COption::GetOptionString(___222624854(136), ___222624854(137), ___222624854(138), $_809782135[___222624854(139)]) != $_673986744){ COption::SetOptionString(___222624854(140), ___222624854(141), $_673986744, false, $_809782135[___222624854(142)]); COption::SetOptionString(___222624854(143), ___222624854(144), $_673986744);}}} protected static function OnMicroBlogSettingsChange($_80673935, $_2124838038){ if($_2124838038) $_673986744= "Y"; else $_673986744= ___222624854(145); $_854304672= CSite::GetList(($_1437152469= ___222624854(146)),($_218680045= ___222624854(147)), array(___222624854(148) => ___222624854(149))); while($_809782135= $_854304672->Fetch()){ if(COption::GetOptionString(___222624854(150), ___222624854(151), ___222624854(152), $_809782135[___222624854(153)]) != $_673986744){ COption::SetOptionString(___222624854(154), ___222624854(155), $_673986744, false, $_809782135[___222624854(156)]); COption::SetOptionString(___222624854(157), ___222624854(158), $_673986744);} if(COption::GetOptionString(___222624854(159), ___222624854(160), ___222624854(161), $_809782135[___222624854(162)]) != $_673986744){ COption::SetOptionString(___222624854(163), ___222624854(164), $_673986744, false, $_809782135[___222624854(165)]); COption::SetOptionString(___222624854(166), ___222624854(167), $_673986744);}}} protected static function OnPersonalFilesSettingsChange($_80673935, $_2124838038){ if($_2124838038) $_673986744= "Y"; else $_673986744= ___222624854(168); $_854304672= CSite::GetList(($_1437152469= ___222624854(169)),($_218680045= ___222624854(170)), array(___222624854(171) => ___222624854(172))); while($_809782135= $_854304672->Fetch()){ if(COption::GetOptionString(___222624854(173), ___222624854(174), ___222624854(175), $_809782135[___222624854(176)]) != $_673986744){ COption::SetOptionString(___222624854(177), ___222624854(178), $_673986744, false, $_809782135[___222624854(179)]); COption::SetOptionString(___222624854(180), ___222624854(181), $_673986744);}}} protected static function OnPersonalBlogSettingsChange($_80673935, $_2124838038){ if($_2124838038) $_673986744= "Y"; else $_673986744= ___222624854(182); $_854304672= CSite::GetList(($_1437152469= ___222624854(183)),($_218680045= ___222624854(184)), array(___222624854(185) => ___222624854(186))); while($_809782135= $_854304672->Fetch()){ if(COption::GetOptionString(___222624854(187), ___222624854(188), ___222624854(189), $_809782135[___222624854(190)]) != $_673986744){ COption::SetOptionString(___222624854(191), ___222624854(192), $_673986744, false, $_809782135[___222624854(193)]); COption::SetOptionString(___222624854(194), ___222624854(195), $_673986744);}}} protected static function OnPersonalPhotoSettingsChange($_80673935, $_2124838038){ if($_2124838038) $_673986744= "Y"; else $_673986744= ___222624854(196); $_854304672= CSite::GetList(($_1437152469= ___222624854(197)),($_218680045= ___222624854(198)), array(___222624854(199) => ___222624854(200))); while($_809782135= $_854304672->Fetch()){ if(COption::GetOptionString(___222624854(201), ___222624854(202), ___222624854(203), $_809782135[___222624854(204)]) != $_673986744){ COption::SetOptionString(___222624854(205), ___222624854(206), $_673986744, false, $_809782135[___222624854(207)]); COption::SetOptionString(___222624854(208), ___222624854(209), $_673986744);}}} protected static function OnPersonalForumSettingsChange($_80673935, $_2124838038){ if($_2124838038) $_673986744= "Y"; else $_673986744= ___222624854(210); $_854304672= CSite::GetList(($_1437152469= ___222624854(211)),($_218680045= ___222624854(212)), array(___222624854(213) => ___222624854(214))); while($_809782135= $_854304672->Fetch()){ if(COption::GetOptionString(___222624854(215), ___222624854(216), ___222624854(217), $_809782135[___222624854(218)]) != $_673986744){ COption::SetOptionString(___222624854(219), ___222624854(220), $_673986744, false, $_809782135[___222624854(221)]); COption::SetOptionString(___222624854(222), ___222624854(223), $_673986744);}}} protected static function OnTasksSettingsChange($_80673935, $_2124838038){ if($_2124838038) $_673986744= "Y"; else $_673986744= ___222624854(224); $_854304672= CSite::GetList(($_1437152469= ___222624854(225)),($_218680045= ___222624854(226)), array(___222624854(227) => ___222624854(228))); while($_809782135= $_854304672->Fetch()){ if(COption::GetOptionString(___222624854(229), ___222624854(230), ___222624854(231), $_809782135[___222624854(232)]) != $_673986744){ COption::SetOptionString(___222624854(233), ___222624854(234), $_673986744, false, $_809782135[___222624854(235)]); COption::SetOptionString(___222624854(236), ___222624854(237), $_673986744);} if(COption::GetOptionString(___222624854(238), ___222624854(239), ___222624854(240), $_809782135[___222624854(241)]) != $_673986744){ COption::SetOptionString(___222624854(242), ___222624854(243), $_673986744, false, $_809782135[___222624854(244)]); COption::SetOptionString(___222624854(245), ___222624854(246), $_673986744);}} self::__461213863(___222624854(247), $_2124838038);} protected static function OnCalendarSettingsChange($_80673935, $_2124838038){ if($_2124838038) $_673986744= "Y"; else $_673986744= ___222624854(248); $_854304672= CSite::GetList(($_1437152469= ___222624854(249)),($_218680045= ___222624854(250)), array(___222624854(251) => ___222624854(252))); while($_809782135= $_854304672->Fetch()){ if(COption::GetOptionString(___222624854(253), ___222624854(254), ___222624854(255), $_809782135[___222624854(256)]) != $_673986744){ COption::SetOptionString(___222624854(257), ___222624854(258), $_673986744, false, $_809782135[___222624854(259)]); COption::SetOptionString(___222624854(260), ___222624854(261), $_673986744);} if(COption::GetOptionString(___222624854(262), ___222624854(263), ___222624854(264), $_809782135[___222624854(265)]) != $_673986744){ COption::SetOptionString(___222624854(266), ___222624854(267), $_673986744, false, $_809782135[___222624854(268)]); COption::SetOptionString(___222624854(269), ___222624854(270), $_673986744);}}} protected static function OnSMTPSettingsChange($_80673935, $_2124838038){ self::__461213863("mail", $_2124838038);} protected static function OnExtranetSettingsChange($_80673935, $_2124838038){ $_1025090953= COption::GetOptionString("extranet", "extranet_site", ""); if($_1025090953){ $_1176607012= new CSite; $_1176607012->Update($_1025090953, array(___222624854(271) =>($_2124838038? ___222624854(272): ___222624854(273))));} self::__461213863(___222624854(274), $_2124838038);} protected static function OnDAVSettingsChange($_80673935, $_2124838038){ self::__461213863("dav", $_2124838038);} protected static function OntimemanSettingsChange($_80673935, $_2124838038){ self::__461213863("timeman", $_2124838038);} protected static function Onintranet_sharepointSettingsChange($_80673935, $_2124838038){ if($_2124838038){ RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", "intranet", "CIntranetEventHandlers", "SPRegisterUpdatedItem"); RegisterModuleDependences(___222624854(275), ___222624854(276), ___222624854(277), ___222624854(278), ___222624854(279)); CAgent::AddAgent(___222624854(280), ___222624854(281), ___222624854(282), round(0+500)); CAgent::AddAgent(___222624854(283), ___222624854(284), ___222624854(285), round(0+300)); CAgent::AddAgent(___222624854(286), ___222624854(287), ___222624854(288), round(0+3600));} else{ UnRegisterModuleDependences(___222624854(289), ___222624854(290), ___222624854(291), ___222624854(292), ___222624854(293)); UnRegisterModuleDependences(___222624854(294), ___222624854(295), ___222624854(296), ___222624854(297), ___222624854(298)); CAgent::RemoveAgent(___222624854(299), ___222624854(300)); CAgent::RemoveAgent(___222624854(301), ___222624854(302)); CAgent::RemoveAgent(___222624854(303), ___222624854(304));}} protected static function OncrmSettingsChange($_80673935, $_2124838038){ if($_2124838038) COption::SetOptionString("crm", "form_features", "Y"); self::__461213863(___222624854(305), $_2124838038);} protected static function OnClusterSettingsChange($_80673935, $_2124838038){ self::__461213863("cluster", $_2124838038);} protected static function OnMultiSitesSettingsChange($_80673935, $_2124838038){ if($_2124838038) RegisterModuleDependences("main", "OnBeforeProlog", "main", "CWizardSolPanelIntranet", "ShowPanel", 100, "/modules/intranet/panel_button.php"); else UnRegisterModuleDependences(___222624854(306), ___222624854(307), ___222624854(308), ___222624854(309), ___222624854(310), ___222624854(311));} protected static function OnIdeaSettingsChange($_80673935, $_2124838038){ self::__461213863("idea", $_2124838038);} protected static function OnMeetingSettingsChange($_80673935, $_2124838038){ self::__461213863("meeting", $_2124838038);} protected static function OnXDImportSettingsChange($_80673935, $_2124838038){ self::__461213863("xdimport", $_2124838038);}} $GLOBALS['____1772201832'][67](___222624854(312), ___222624854(313));/**/			//Do not remove this

//component 2.0 template engines
$GLOBALS["arCustomTemplateEngines"] = array();

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/urlrewriter.php");

/**
 * Defined in dbconn.php
 * @param string $DBType
 */

require_once(__DIR__.'/autoload.php');
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$DBType."/agent.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/user.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$DBType."/event.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/menu.php");
AddEventHandler("main", "OnAfterEpilog", array("\\Bitrix\\Main\\Data\\ManagedCache", "finalize"));
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$DBType."/usertype.php");

if(file_exists(($_fname = $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/update_db_updater.php")))
{
	$US_HOST_PROCESS_MAIN = False;
	include($_fname);
}

if(file_exists(($_fname = $_SERVER["DOCUMENT_ROOT"]."/bitrix/init.php")))
	include_once($_fname);

if(($_fname = getLocalPath("php_interface/init.php", BX_PERSONAL_ROOT)) !== false)
	include_once($_SERVER["DOCUMENT_ROOT"].$_fname);

if(($_fname = getLocalPath("php_interface/".SITE_ID."/init.php", BX_PERSONAL_ROOT)) !== false)
	include_once($_SERVER["DOCUMENT_ROOT"].$_fname);

if(!defined("BX_FILE_PERMISSIONS"))
	define("BX_FILE_PERMISSIONS", 0644);
if(!defined("BX_DIR_PERMISSIONS"))
	define("BX_DIR_PERMISSIONS", 0755);

//global var, is used somewhere
$GLOBALS["sDocPath"] = $GLOBALS["APPLICATION"]->GetCurPage();

if((!(defined("STATISTIC_ONLY") && STATISTIC_ONLY && mb_substr($GLOBALS["APPLICATION"]->GetCurPage(), 0, mb_strlen(BX_ROOT."/admin/")) != BX_ROOT."/admin/")) && COption::GetOptionString("main", "include_charset", "Y")=="Y" && LANG_CHARSET <> '')
	header("Content-Type: text/html; charset=".LANG_CHARSET);

if(COption::GetOptionString("main", "set_p3p_header", "Y")=="Y")
	header("P3P: policyref=\"/bitrix/p3p.xml\", CP=\"NON DSP COR CUR ADM DEV PSA PSD OUR UNR BUS UNI COM NAV INT DEM STA\"");

header("X-Powered-CMS: Bitrix Site Manager (".(LICENSE_KEY == "DEMO"? "DEMO" : md5("BITRIX".LICENSE_KEY."LICENCE")).")");
if (COption::GetOptionString("main", "update_devsrv", "") == "Y")
	header("X-DevSrv-CMS: Bitrix");

define("BX_CRONTAB_SUPPORT", defined("BX_CRONTAB"));

//agents
if(COption::GetOptionString("main", "check_agents", "Y") == "Y")
{
	$application->addBackgroundJob(["CAgent", "CheckAgents"], [], \Bitrix\Main\Application::JOB_PRIORITY_LOW);
}

//send email events
if(COption::GetOptionString("main", "check_events", "Y") !== "N")
{
	$application->addBackgroundJob(["CEvent", "CheckEvents"], [], \Bitrix\Main\Application::JOB_PRIORITY_LOW-1);
}

$healerOfEarlySessionStart = new HealerEarlySessionStart();
$healerOfEarlySessionStart->process($application->getKernelSession());

$kernelSession = $application->getKernelSession();
$kernelSession->start();
$application->getSessionLocalStorageManager()->setUniqueId($kernelSession->getId());

foreach (GetModuleEvents("main", "OnPageStart", true) as $arEvent)
	ExecuteModuleEventEx($arEvent);

//define global user object
$GLOBALS["USER"] = new CUser;

//session control from group policy
$arPolicy = $GLOBALS["USER"]->GetSecurityPolicy();
$currTime = time();
if(
	(
		//IP address changed
		$kernelSession['SESS_IP']
		&& $arPolicy["SESSION_IP_MASK"] <> ''
		&& (
			(ip2long($arPolicy["SESSION_IP_MASK"]) & ip2long($kernelSession['SESS_IP']))
			!=
			(ip2long($arPolicy["SESSION_IP_MASK"]) & ip2long($_SERVER['REMOTE_ADDR']))
		)
	)
	||
	(
		//session timeout
		$arPolicy["SESSION_TIMEOUT"]>0
		&& $kernelSession['SESS_TIME']>0
		&& $currTime-$arPolicy["SESSION_TIMEOUT"]*60 > $kernelSession['SESS_TIME']
	)
	||
	(
		//signed session
		isset($kernelSession["BX_SESSION_SIGN"])
		&& $kernelSession["BX_SESSION_SIGN"] <> bitrix_sess_sign()
	)
	||
	(
		//session manually expired, e.g. in $User->LoginHitByHash
		isSessionExpired()
	)
)
{
	$compositeSessionManager = $application->getCompositeSessionManager();
	$compositeSessionManager->destroy();

	$application->getSession()->setId(md5(uniqid(rand(), true)));
	$compositeSessionManager->start();

	$GLOBALS["USER"] = new CUser;
}
$kernelSession['SESS_IP'] = $_SERVER['REMOTE_ADDR'];
if (empty($kernelSession['SESS_TIME']))
{
	$kernelSession['SESS_TIME'] = $currTime;
}
elseif (($currTime - $kernelSession['SESS_TIME']) > 60)
{
	$kernelSession['SESS_TIME'] = $currTime;
}
if(!isset($kernelSession["BX_SESSION_SIGN"]))
	$kernelSession["BX_SESSION_SIGN"] = bitrix_sess_sign();

//session control from security module
if(
	(COption::GetOptionString("main", "use_session_id_ttl", "N") == "Y")
	&& (COption::GetOptionInt("main", "session_id_ttl", 0) > 0)
	&& !defined("BX_SESSION_ID_CHANGE")
)
{
	if(!isset($kernelSession['SESS_ID_TIME']))
	{
		$kernelSession['SESS_ID_TIME'] = $currTime;
	}
	elseif(($kernelSession['SESS_ID_TIME'] + COption::GetOptionInt("main", "session_id_ttl")) < $kernelSession['SESS_TIME'])
	{
		$compositeSessionManager = $application->getCompositeSessionManager();
		$compositeSessionManager->regenerateId();

		$kernelSession['SESS_ID_TIME'] = $currTime;
	}
}

define("BX_STARTED", true);

if (isset($kernelSession['BX_ADMIN_LOAD_AUTH']))
{
	define('ADMIN_SECTION_LOAD_AUTH', 1);
	unset($kernelSession['BX_ADMIN_LOAD_AUTH']);
}

if(!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS!==true)
{
	$doLogout = isset($_REQUEST["logout"]) && (strtolower($_REQUEST["logout"]) == "yes");

	if($doLogout && $GLOBALS["USER"]->IsAuthorized())
	{
		$secureLogout = (\Bitrix\Main\Config\Option::get("main", "secure_logout", "N") == "Y");

		if(!$secureLogout || check_bitrix_sessid())
		{
			$GLOBALS["USER"]->Logout();
			LocalRedirect($GLOBALS["APPLICATION"]->GetCurPageParam('', array('logout', 'sessid')));
		}
	}

	// authorize by cookies
	if(!$GLOBALS["USER"]->IsAuthorized())
	{
		$GLOBALS["USER"]->LoginByCookies();
	}

	$arAuthResult = false;

	//http basic and digest authorization
	if(($httpAuth = $GLOBALS["USER"]->LoginByHttpAuth()) !== null)
	{
		$arAuthResult = $httpAuth;
		$GLOBALS["APPLICATION"]->SetAuthResult($arAuthResult);
	}

	//Authorize user from authorization html form
	//Only POST is accepted
	if(isset($_POST["AUTH_FORM"]) && $_POST["AUTH_FORM"] <> '')
	{
		$bRsaError = false;
		if(COption::GetOptionString('main', 'use_encrypted_auth', 'N') == 'Y')
		{
			//possible encrypted user password
			$sec = new CRsaSecurity();
			if(($arKeys = $sec->LoadKeys()))
			{
				$sec->SetKeys($arKeys);
				$errno = $sec->AcceptFromForm(['USER_PASSWORD', 'USER_CONFIRM_PASSWORD', 'USER_CURRENT_PASSWORD']);
				if($errno == CRsaSecurity::ERROR_SESS_CHECK)
					$arAuthResult = array("MESSAGE"=>GetMessage("main_include_decode_pass_sess"), "TYPE"=>"ERROR");
				elseif($errno < 0)
					$arAuthResult = array("MESSAGE"=>GetMessage("main_include_decode_pass_err", array("#ERRCODE#"=>$errno)), "TYPE"=>"ERROR");

				if($errno < 0)
					$bRsaError = true;
			}
		}

		if($bRsaError == false)
		{
			if(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
				$USER_LID = SITE_ID;
			else
				$USER_LID = false;

			if($_POST["TYPE"] == "AUTH")
			{
				$arAuthResult = $GLOBALS["USER"]->Login($_POST["USER_LOGIN"], $_POST["USER_PASSWORD"], $_POST["USER_REMEMBER"]);
			}
			elseif($_POST["TYPE"] == "OTP")
			{
				$arAuthResult = $GLOBALS["USER"]->LoginByOtp($_POST["USER_OTP"], $_POST["OTP_REMEMBER"], $_POST["captcha_word"], $_POST["captcha_sid"]);
			}
			elseif($_POST["TYPE"] == "SEND_PWD")
			{
				$arAuthResult = CUser::SendPassword($_POST["USER_LOGIN"], $_POST["USER_EMAIL"], $USER_LID, $_POST["captcha_word"], $_POST["captcha_sid"], $_POST["USER_PHONE_NUMBER"]);
			}
			elseif($_POST["TYPE"] == "CHANGE_PWD")
			{
				$arAuthResult = $GLOBALS["USER"]->ChangePassword($_POST["USER_LOGIN"], $_POST["USER_CHECKWORD"], $_POST["USER_PASSWORD"], $_POST["USER_CONFIRM_PASSWORD"], $USER_LID, $_POST["captcha_word"], $_POST["captcha_sid"], true, $_POST["USER_PHONE_NUMBER"], $_POST["USER_CURRENT_PASSWORD"]);
			}
			elseif(COption::GetOptionString("main", "new_user_registration", "N") == "Y" && $_POST["TYPE"] == "REGISTRATION" && (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true))
			{
				$arAuthResult = $GLOBALS["USER"]->Register($_POST["USER_LOGIN"], $_POST["USER_NAME"], $_POST["USER_LAST_NAME"], $_POST["USER_PASSWORD"], $_POST["USER_CONFIRM_PASSWORD"], $_POST["USER_EMAIL"], $USER_LID, $_POST["captcha_word"], $_POST["captcha_sid"], false, $_POST["USER_PHONE_NUMBER"]);
			}

			if($_POST["TYPE"] == "AUTH" || $_POST["TYPE"] == "OTP")
			{
				//special login form in the control panel
				if($arAuthResult === true && defined('ADMIN_SECTION') && ADMIN_SECTION === true)
				{
					//store cookies for next hit (see CMain::GetSpreadCookieHTML())
					$GLOBALS["APPLICATION"]->StoreCookies();
					$kernelSession['BX_ADMIN_LOAD_AUTH'] = true;

					CMain::FinalActions('<script type="text/javascript">window.onload=function(){(window.BX || window.parent.BX).AUTHAGENT.setAuthResult(false);};</script>');
					die();
				}
			}
		}
		$GLOBALS["APPLICATION"]->SetAuthResult($arAuthResult);
	}
	elseif(!$GLOBALS["USER"]->IsAuthorized())
	{
		//Authorize by unique URL
		$GLOBALS["USER"]->LoginHitByHash();
	}
}

//logout or re-authorize the user if something importand has changed
$GLOBALS["USER"]->CheckAuthActions();

//magic short URI
if(defined("BX_CHECK_SHORT_URI") && BX_CHECK_SHORT_URI && CBXShortUri::CheckUri())
{
	//local redirect inside
	die();
}

//application password scope control
if(($applicationID = $GLOBALS["USER"]->GetParam("APPLICATION_ID")) !== null)
{
	$appManager = \Bitrix\Main\Authentication\ApplicationManager::getInstance();
	if($appManager->checkScope($applicationID) !== true)
	{
		$event = new \Bitrix\Main\Event("main", "onApplicationScopeError", Array('APPLICATION_ID' => $applicationID));
		$event->send();

		CHTTP::SetStatus("403 Forbidden");
		die();
	}
}

//define the site template
if(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
{
	$siteTemplate = "";
	if(is_string($_REQUEST["bitrix_preview_site_template"]) && $_REQUEST["bitrix_preview_site_template"] <> "" && $GLOBALS["USER"]->CanDoOperation('view_other_settings'))
	{
		//preview of site template
		$signer = new Bitrix\Main\Security\Sign\Signer();
		try
		{
			//protected by a sign
			$requestTemplate = $signer->unsign($_REQUEST["bitrix_preview_site_template"], "template_preview".bitrix_sessid());

			$aTemplates = CSiteTemplate::GetByID($requestTemplate);
			if($template = $aTemplates->Fetch())
			{
				$siteTemplate = $template["ID"];

				//preview of unsaved template
				if(isset($_GET['bx_template_preview_mode']) && $_GET['bx_template_preview_mode'] == 'Y' && $GLOBALS["USER"]->CanDoOperation('edit_other_settings'))
				{
					define("SITE_TEMPLATE_PREVIEW_MODE", true);
				}
			}
		}
		catch(\Bitrix\Main\Security\Sign\BadSignatureException $e)
		{
		}
	}
	if($siteTemplate == "")
	{
		$siteTemplate = CSite::GetCurTemplate();
	}
	define("SITE_TEMPLATE_ID", $siteTemplate);
	define("SITE_TEMPLATE_PATH", getLocalPath('templates/'.SITE_TEMPLATE_ID, BX_PERSONAL_ROOT));
}

//magic parameters: show page creation time
if(isset($_GET["show_page_exec_time"]))
{
	if($_GET["show_page_exec_time"]=="Y" || $_GET["show_page_exec_time"]=="N")
		$kernelSession["SESS_SHOW_TIME_EXEC"] = $_GET["show_page_exec_time"];
}

//magic parameters: show included file processing time
if(isset($_GET["show_include_exec_time"]))
{
	if($_GET["show_include_exec_time"]=="Y" || $_GET["show_include_exec_time"]=="N")
		$kernelSession["SESS_SHOW_INCLUDE_TIME_EXEC"] = $_GET["show_include_exec_time"];
}

//magic parameters: show include areas
if(isset($_GET["bitrix_include_areas"]) && $_GET["bitrix_include_areas"] <> "")
	$GLOBALS["APPLICATION"]->SetShowIncludeAreas($_GET["bitrix_include_areas"]=="Y");

//magic sound
if($GLOBALS["USER"]->IsAuthorized())
{
	$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
	if(!isset($_COOKIE[$cookie_prefix.'_SOUND_LOGIN_PLAYED']))
		$GLOBALS["APPLICATION"]->set_cookie('SOUND_LOGIN_PLAYED', 'Y', 0);
}

//magic cache
\Bitrix\Main\Composite\Engine::shouldBeEnabled();

foreach(GetModuleEvents("main", "OnBeforeProlog", true) as $arEvent)
	ExecuteModuleEventEx($arEvent);

if((!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS!==true) && (!defined("NOT_CHECK_FILE_PERMISSIONS") || NOT_CHECK_FILE_PERMISSIONS!==true))
{
	$real_path = $request->getScriptFile();

	if(!$GLOBALS["USER"]->CanDoFileOperation('fm_view_file', array(SITE_ID, $real_path)) || (defined("NEED_AUTH") && NEED_AUTH && !$GLOBALS["USER"]->IsAuthorized()))
	{
		/** @noinspection PhpUndefinedVariableInspection */
		if($GLOBALS["USER"]->IsAuthorized() && $arAuthResult["MESSAGE"] == '')
		{
			$arAuthResult = array("MESSAGE"=>GetMessage("ACCESS_DENIED").' '.GetMessage("ACCESS_DENIED_FILE", array("#FILE#"=>$real_path)), "TYPE"=>"ERROR");

			if(COption::GetOptionString("main", "event_log_permissions_fail", "N") === "Y")
			{
				CEventLog::Log("SECURITY", "USER_PERMISSIONS_FAIL", "main", $GLOBALS["USER"]->GetID(), $real_path);
			}
		}

		if(defined("ADMIN_SECTION") && ADMIN_SECTION==true)
		{
			if ($_REQUEST["mode"]=="list" || $_REQUEST["mode"]=="settings")
			{
				echo "<script>top.location='".$GLOBALS["APPLICATION"]->GetCurPage()."?".DeleteParam(array("mode"))."';</script>";
				die();
			}
			elseif ($_REQUEST["mode"]=="frame")
			{
				echo "<script type=\"text/javascript\">
					var w = (opener? opener.window:parent.window);
					w.location.href='".$GLOBALS["APPLICATION"]->GetCurPage()."?".DeleteParam(array("mode"))."';
				</script>";
				die();
			}
			elseif(defined("MOBILE_APP_ADMIN") && MOBILE_APP_ADMIN==true)
			{
				echo json_encode(Array("status"=>"failed"));
				die();
			}
		}

		/** @noinspection PhpUndefinedVariableInspection */
		$GLOBALS["APPLICATION"]->AuthForm($arAuthResult);
	}
}

/*ZDUyZmZMjg4MzVhMGFhNjdjYTcyOTgxYjIwODc0ZTFlNmZlNjc=*/$GLOBALS['____632486020']= array(base64_decode('bXRf'.'c'.'mFuZA=='),base64_decode(''.'Z'.'XhwbG9k'.'ZQ=='),base64_decode('cGFjaw=='),base64_decode('bWQ'.'1'),base64_decode('Y29uc3'.'Rh'.'bnQ='),base64_decode('a'.'GFza'.'F9obWFj'),base64_decode(''.'c'.'3RyY2'.'1w'),base64_decode('aXNfb2J'.'qZWN0'),base64_decode('Y'.'2FsbF91c2VyX2'.'Z'.'1bmM'.'='),base64_decode(''.'Y2'.'FsbF91c2V'.'yX2'.'Z'.'1bmM='),base64_decode('Y2F'.'sbF9'.'1'.'c'.'2VyX2Z1bmM='),base64_decode('Y2FsbF'.'9'.'1c2Vy'.'X2'.'Z1bmM='),base64_decode('Y'.'2FsbF'.'91c'.'2VyX2'.'Z'.'1b'.'mM='));if(!function_exists(__NAMESPACE__.'\\___1874156318')){function ___1874156318($_395214751){static $_2110411071= false; if($_2110411071 == false) $_2110411071=array('R'.'EI=','U0VMRUNU'.'IFZBTFVF'.'IEZS'.'T00gYl9vcH'.'Rpb24gV0hFUkUg'.'TkFN'.'RT0'.'nflBBUkFNX0'.'1BWF'.'9V'.'U'.'0V'.'S'.'U'.'ycgQU5EIE1PR'.'FVM'.'RV9JR'.'D'.'0nbWFp'.'bic'.'gQ'.'U'.'5EI'.'F'.'NJVEVfS'.'UQg'.'SVMgTlVM'.'TA'.'==',''.'V'.'k'.'FM'.'VUU=','Lg'.'==',''.'S'.'Co=','Yml'.'0cml4',''.'TElDRU5TRV9'.'LRVk'.'=','c2'.'hh'.'Mj'.'U2','VVNFUg==','VVNFUg==','V'.'VNFU'.'g==','SXNBdX'.'Ro'.'b3JpemVk','VVNFUg==','SXNBZG1pbg==','QVBQTEl'.'DQVRJT'.'04'.'=','Um'.'V'.'zdGFy'.'dEJ1ZmZl'.'cg==','TG9'.'jYWx'.'SZWRpcmVjd'.'A==','L2xpY2Vuc2VfcmVzdHJp'.'Y3'.'Rpb24'.'uc'.'G'.'h'.'w',''.'X'.'EJpdHJpeFx'.'NYW'.'luXENvb'.'mZp'.'Z'.'1xP'.'cHR'.'pb24'.'6OnN'.'ldA'.'==','bWF'.'p'.'bg'.'==','UE'.'F'.'SQU1f'.'TU'.'FYX'.'1'.'V'.'TR'.'V'.'JT');return base64_decode($_2110411071[$_395214751]);}};if($GLOBALS['____632486020'][0](round(0+0.5+0.5), round(0+4+4+4+4+4)) == round(0+7)){ $_2053667843= $GLOBALS[___1874156318(0)]->Query(___1874156318(1), true); if($_857739409= $_2053667843->Fetch()){ $_2136737904= $_857739409[___1874156318(2)]; list($_192771449, $_504670837)= $GLOBALS['____632486020'][1](___1874156318(3), $_2136737904); $_782827228= $GLOBALS['____632486020'][2](___1874156318(4), $_192771449); $_1000815902= ___1874156318(5).$GLOBALS['____632486020'][3]($GLOBALS['____632486020'][4](___1874156318(6))); $_592518658= $GLOBALS['____632486020'][5](___1874156318(7), $_504670837, $_1000815902, true); if($GLOBALS['____632486020'][6]($_592518658, $_782827228) !==(830-2*415)){ if(isset($GLOBALS[___1874156318(8)]) && $GLOBALS['____632486020'][7]($GLOBALS[___1874156318(9)]) && $GLOBALS['____632486020'][8](array($GLOBALS[___1874156318(10)], ___1874156318(11))) &&!$GLOBALS['____632486020'][9](array($GLOBALS[___1874156318(12)], ___1874156318(13)))){ $GLOBALS['____632486020'][10](array($GLOBALS[___1874156318(14)], ___1874156318(15))); $GLOBALS['____632486020'][11](___1874156318(16), ___1874156318(17), true);}}} else{ $GLOBALS['____632486020'][12](___1874156318(18), ___1874156318(19), ___1874156318(20), round(0+4+4+4));}}/**/       //Do not remove this

