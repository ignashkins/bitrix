<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\Session\Legacy\HealerEarlySessionStart;

require_once(__DIR__."/bx_root.php");
require_once(__DIR__."/start.php");

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
define("SITE_DIR", ($arLang["DIR"] ?? ''));
define("SITE_SERVER_NAME", ($arLang["SERVER_NAME"] ?? ''));
define("SITE_CHARSET", $arLang["CHARSET"]);
define("FORMAT_DATE", $arLang["FORMAT_DATE"]);
define("FORMAT_DATETIME", $arLang["FORMAT_DATETIME"]);
define("LANG_DIR", ($arLang["DIR"] ?? ''));
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

$GLOBALS["MESS"] = [];
$GLOBALS["ALL_LANG_FILES"] = [];
IncludeModuleLangFile(__DIR__."/tools.php");
IncludeModuleLangFile(__FILE__);

error_reporting(COption::GetOptionInt("main", "error_reporting", E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE) & ~E_STRICT & ~E_DEPRECATED);

if(!defined("BX_COMP_MANAGED_CACHE") && COption::GetOptionString("main", "component_managed_cache_on", "Y") <> "N")
{
	define("BX_COMP_MANAGED_CACHE", true);
}

// global functions
require_once(__DIR__."/filter_tools.php");

define('BX_AJAX_PARAM_ID', 'bxajaxid');

/*ZDUyZmZMzQ4NDAxMWJlMzYyZTBiZTAxY2MwY2FiOGUzMWU3M2U=*/$GLOBALS['_____861528270']= array(base64_decode('R2V'.'0'.'TW9kd'.'WxlR'.'XZlbnRz'),base64_decode('RXh'.'l'.'Y3V0ZU1vZHVsZ'.'UV2ZW50RXg'.'='));$GLOBALS['____1864780129']= array(base64_decode('ZGVma'.'W5'.'l'),base64_decode(''.'c'.'3R'.'ybGVu'),base64_decode('YmFzZT'.'Y0'.'X2RlY29k'.'ZQ=='),base64_decode('dW5zZXJ'.'pYWxpemU='),base64_decode('aXN'.'fYXJ'.'yYX'.'k='),base64_decode('Y291bn'.'Q='),base64_decode('aW5fYX'.'JyYXk='),base64_decode('c2VyaWF'.'s'.'aX'.'pl'),base64_decode('Ym'.'FzZTY0'.'X2Vu'.'Y29'.'kZQ=='),base64_decode('c3'.'RybGV'.'u'),base64_decode('YX'.'JyYXlfa2V5X2V'.'4a'.'X'.'N0cw=='),base64_decode('YXJyY'.'Xlfa'.'2V'.'5X2V4aXN'.'0cw='.'='),base64_decode('bW'.'t0'.'aW1l'),base64_decode('ZGF0Z'.'Q'.'=='),base64_decode('ZGF'.'0Z'.'Q='.'='),base64_decode('Y'.'XJyYXlfa2V5X2V4aXN0cw=='),base64_decode(''.'c3Ry'.'bGVu'),base64_decode('YXJyYXlfa2V5X2V4'.'aXN0cw=='),base64_decode('c3RybGV'.'u'),base64_decode('YXJyYXlf'.'a2V5'.'X'.'2V4aXN0cw=='),base64_decode('YXJy'.'Y'.'Xl'.'fa2V5X2V4'.'aX'.'N0cw=='),base64_decode(''.'b'.'W'.'t0aW1l'),base64_decode('ZG'.'F0ZQ='.'='),base64_decode('ZGF0ZQ=='),base64_decode('b'.'WV0'.'aG9'.'kX2'.'V4'.'aXN0cw=='),base64_decode('Y2Fs'.'bF91c'.'2V'.'yX2Z1bmNfYX'.'J'.'yYXk'.'='),base64_decode(''.'c3Ry'.'bGVu'),base64_decode('Y'.'XJyY'.'Xlfa'.'2V5X2V4'.'aXN0cw=='),base64_decode('Y'.'XJyY'.'X'.'lfa'.'2V5X2V'.'4aXN0cw=='),base64_decode('c2VyaW'.'Fsa'.'Xpl'),base64_decode('Y'.'m'.'FzZTY0X2'.'V'.'uY29k'.'ZQ'.'=='),base64_decode('c3Ry'.'bGVu'),base64_decode(''.'YXJyYXlfa'.'2V5'.'X2V4aXN0cw'.'=='),base64_decode('YXJyY'.'Xlfa2V5X'.'2V4aX'.'N0c'.'w'.'=='),base64_decode('Y'.'X'.'JyY'.'Xlfa2V5X'.'2V4aX'.'N0cw=='),base64_decode('aXNfYXJy'.'YXk='),base64_decode('YX'.'Jy'.'YXl'.'f'.'a'.'2V5X2V4a'.'XN0cw'.'=='),base64_decode('c2VyaWF'.'s'.'aXpl'),base64_decode('Y'.'mFzZT'.'Y0X2VuY2'.'9kZQ=='),base64_decode('YXJy'.'Y'.'Xlfa2V'.'5X2V4a'.'XN0cw='.'='),base64_decode('YX'.'JyY'.'Xlf'.'a2'.'V5X'.'2'.'V4aX'.'N'.'0cw='.'='),base64_decode('c2VyaWFsaXpl'),base64_decode(''.'YmFzZTY0X2Vu'.'Y2'.'9k'.'ZQ='.'='),base64_decode('aXNf'.'YXJyY'.'Xk='),base64_decode('aXN'.'f'.'Y'.'XJyYXk='),base64_decode('aW5fYXJyYX'.'k='),base64_decode('YXJyYXlfa2V5X2V4aXN'.'0cw=='),base64_decode('aW5fYXJyYXk='),base64_decode(''.'b'.'Wt0a'.'W1l'),base64_decode('ZGF0ZQ'.'='.'='),base64_decode(''.'Z'.'G'.'F0ZQ=='),base64_decode(''.'ZGF0ZQ'.'=='),base64_decode(''.'bWt0a'.'W1'.'l'),base64_decode('ZGF0'.'ZQ='.'='),base64_decode('Z'.'GF0'.'ZQ=='),base64_decode('aW5fYXJy'.'YXk'.'='),base64_decode('YX'.'Jy'.'YXlf'.'a2V5X2V4aXN'.'0cw=='),base64_decode('YXJ'.'yYXlfa2V'.'5'.'X'.'2V4aXN'.'0cw=='),base64_decode('c2'.'V'.'yaWFsaXpl'),base64_decode('Y'.'mFzZ'.'TY0X2VuY29k'.'ZQ=='),base64_decode('YXJyYXlfa2V5X2V4aXN0c'.'w=='),base64_decode('aW50dmF'.'s'),base64_decode('dGltZQ'.'=='),base64_decode('YXJyYXlfa2'.'V5X2V4a'.'X'.'N'.'0cw=='),base64_decode(''.'Zm'.'ls'.'ZV'.'9leGlz'.'dHM='),base64_decode('c3RyX3'.'Jl'.'cGxhY2U'.'='),base64_decode('Y'.'2xhc3NfZX'.'hpc3R'.'z'),base64_decode('ZG'.'VmaW5l'));if(!function_exists(__NAMESPACE__.'\\___1340574425')){function ___1340574425($_187983546){static $_677373536= false; if($_677373536 == false) $_677373536=array('SU5'.'U'.'UkFORVRf'.'RU'.'RJ'.'VElPT'.'g==','WQ==','b'.'WFpbg='.'=','fmNwZl9tYXBfdmFsdWU'.'=','','ZQ='.'=','Zg='.'=','ZQ==','R'.'g==','WA==',''.'Zg==','bWFpbg==','fmNw'.'Zl9'.'tYXB'.'fdmFsdWU=','UG9y'.'d'.'G'.'Fs','Rg==',''.'Z'.'Q'.'='.'=','ZQ'.'==','WA'.'==','Rg==','RA'.'==','RA'.'==',''.'b'.'Q==','ZA==','WQ='.'=','Zg==','Zg==','Zg='.'=',''.'Zg==','UG9'.'yd'.'GFs','R'.'g='.'=','ZQ='.'=','ZQ==','WA'.'==',''.'Rg==','RA==','RA='.'=',''.'bQ==','ZA==',''.'WQ==','b'.'WFpb'.'g==','T24=','U2V0dGlu'.'Z3ND'.'aGFuZ2'.'U'.'=','Zg==','Zg==','Zg==','Zg='.'=',''.'bWFpb'.'g==','fmNw'.'Z'.'l9tYXBfdmF'.'s'.'dWU=','ZQ==','Z'.'Q==',''.'ZQ'.'==','RA'.'==','ZQ==','ZQ==','Zg==',''.'Zg==','Zg='.'=','ZQ==','b'.'W'.'F'.'pbg==','fmNwZl'.'9'.'t'.'YXBfdmFsd'.'WU=','ZQ'.'==',''.'Zg'.'==','Z'.'g'.'='.'=','Z'.'g'.'==','Zg='.'=',''.'b'.'W'.'Fpb'.'g'.'==','fmNwZl'.'9tYXB'.'fdmFs'.'dWU=',''.'ZQ'.'==','Zg==','UG9'.'ydG'.'Fs','UG9y'.'dGF'.'s','ZQ'.'==',''.'ZQ==','UG'.'9ydGFs',''.'Rg='.'=','WA==','Rg==',''.'RA='.'=',''.'ZQ==','ZQ==',''.'RA==','b'.'Q==','ZA==','WQ='.'=','ZQ==','WA==','ZQ==','Rg==','ZQ==','R'.'A'.'==','Zg==','Z'.'Q==','R'.'A==','Z'.'Q==','bQ='.'=','ZA'.'==','WQ==','Z'.'g==','Zg==','Zg==','Zg='.'=','Z'.'g==','Z'.'g==',''.'Zg='.'=','Zg==','bW'.'Fpbg==','fm'.'N'.'wZl9tYXBfdmF'.'sdWU=',''.'ZQ='.'=','ZQ='.'=','UG9ydGFs','Rg==','WA='.'=','VFl'.'Q'.'R'.'Q==',''.'REFURQ==','R'.'kVB'.'VF'.'V'.'S'.'RVM=','RVhQ'.'SVJ'.'FRA==','V'.'Fl'.'QRQ==',''.'RA==','VFJZX0R'.'BWV'.'NfQ0'.'9'.'V'.'TlQ=','REFU'.'RQ==','VFJZX0RBWVNfQ09'.'V'.'T'.'lQ=',''.'RVhQSVJFRA==','RkVBVFVSRVM'.'=','Zg'.'='.'=','Zg'.'==','RE'.'9DVU1'.'FT'.'lRfUk9'.'PVA==',''.'L2JpdHJpeC9tb2R1bG'.'Vz'.'L'.'w='.'=','L2luc3'.'Rh'.'bGw'.'v'.'aW5k'.'ZXgu'.'cG'.'hw','L'.'g'.'==','Xw'.'==','c2V'.'hc'.'m'.'No','T'.'g'.'==','','','QU'.'N'.'USVZ'.'F',''.'WQ='.'=','c'.'29'.'j'.'a'.'W'.'FsbmV0d29'.'yaw==','YWxsb'.'3d'.'fZnJpZWx'.'kcw==','WQ==',''.'SUQ=','c'.'29jaW'.'Fsbm'.'V'.'0d29yaw==','YWxsb'.'3d'.'fZnJpZ'.'Wxkcw'.'='.'=','SUQ=','c2'.'9'.'jaW'.'FsbmV0'.'d'.'29y'.'aw'.'==','YWxsb3df'.'ZnJpZWxkcw==',''.'Tg==','','','QUNUSVZF','WQ==','c'.'29jaW'.'Fsbm'.'V0'.'d29ya'.'w==',''.'YWxsb3'.'df'.'bWl'.'jcm9i'.'b'.'G9nX3V'.'zZXI=','WQ==','SUQ=',''.'c'.'29j'.'aWFs'.'bmV0d'.'29yaw='.'=','YWxs'.'b3dfbWljcm9ibG9'.'n'.'X'.'3VzZXI=','SUQ=','c29jaWFsbmV0d29'.'yaw='.'=','YW'.'xsb3d'.'f'.'b'.'W'.'ljcm9i'.'bG9nX3Vz'.'ZXI=',''.'c2'.'9jaWF'.'sbm'.'V0d29yaw==','Y'.'Wxsb3dfbWl'.'jcm'.'9i'.'bG'.'9nX2dyb3Vw','WQ==','SUQ=',''.'c29jaWFsbmV0d29y'.'a'.'w==','YWxsb3dfbWlj'.'cm'.'9ibG9nX2dy'.'b3Vw',''.'SUQ=',''.'c'.'29jaWFsb'.'mV0d29yaw='.'=','Y'.'Wxsb'.'3dfbWlj'.'cm9ib'.'G9nX2dyb3Vw','Tg==','','','QUN'.'USVZF','WQ'.'==','c29jaWFsbmV0d29yaw'.'==','YWxsb3dfZ'.'mlsZXN'.'fdXNlcg='.'=','WQ==','SU'.'Q=','c'.'29ja'.'WFsbmV0d'.'29yaw==','YWxsb3df'.'Zml'.'sZXNfdXN'.'lcg==','S'.'U'.'Q=','c'.'29'.'jaWFs'.'b'.'mV0d29yaw==','YW'.'xsb3dfZmlsZXNfdXN'.'lcg==','Tg==','','',''.'QUN'.'USV'.'Z'.'F',''.'WQ==','c'.'29jaWFs'.'bmV0d2'.'9ya'.'w'.'==','YWxsb3df'.'YmxvZ191c2Vy','W'.'Q='.'=',''.'SUQ=','c29'.'j'.'a'.'WF'.'sbmV0d29yaw==','YW'.'xsb3dfYmxvZ19'.'1c'.'2Vy','SUQ=',''.'c29j'.'a'.'WFsbmV0d29yaw==','YWxsb3'.'dfY'.'m'.'xvZ1'.'91'.'c2Vy','Tg==','','','QUN'.'USVZF','W'.'Q'.'='.'=','c29ja'.'WFs'.'bmV0d29'.'yaw==','YWxsb3'.'d'.'fcGh'.'vdG'.'9fd'.'XNl'.'cg==','WQ='.'=','SUQ=',''.'c29jaWFs'.'bmV'.'0'.'d29ya'.'w==',''.'YWxsb3'.'dfcGhvdG9fdXNlcg==','S'.'UQ'.'=','c29jaWF'.'sbmV0d2'.'9yaw==','Y'.'Wxsb'.'3dfc'.'GhvdG9'.'fdX'.'Nlcg'.'==','Tg==','','',''.'QUNUSV'.'ZF',''.'WQ==','c29'.'jaWFsbmV0'.'d'.'2'.'9yaw==','YWxsb3d'.'fZm9y'.'dW1fdXNlc'.'g==',''.'WQ==','S'.'UQ=','c29'.'ja'.'WFsbmV0d29ya'.'w='.'=',''.'YWxs'.'b3dfZm9ydW'.'1fdXN'.'lcg==','SUQ=','c29jaWFsbmV0d'.'29ya'.'w==','YWxsb3dfZm9'.'y'.'dW1f'.'dXN'.'lcg==','Tg==','','','QU'.'NUSV'.'ZF','WQ==','c'.'29ja'.'WFsb'.'mV0d29yaw==','Y'.'Wx'.'sb3'.'df'.'dGFza3'.'NfdX'.'Nlcg==',''.'WQ==','SU'.'Q=','c'.'2'.'9j'.'aWFsbmV0d2'.'9'.'y'.'a'.'w='.'=','Y'.'Wxsb3'.'dfdGFza3Nfd'.'X'.'Nlc'.'g='.'=','SUQ=','c29jaWFs'.'bmV0d29yaw==','YWxsb'.'3dfdG'.'Fza3Nfd'.'XNlcg='.'=','c'.'2'.'9ja'.'WF'.'sbmV0d2'.'9ya'.'w==','YW'.'xsb3dfdGFza3NfZ3JvdXA=',''.'WQ'.'==',''.'SUQ=','c29jaWFsbmV0'.'d29yaw='.'=','YWxsb3d'.'fdGF'.'z'.'a3NfZ3JvdXA'.'=','SU'.'Q=',''.'c29ja'.'WFsbmV0d29ya'.'w==','Y'.'W'.'xs'.'b3d'.'fdGFz'.'a3NfZ3JvdXA=','dGFza3M'.'=','Tg'.'='.'=','','','QUNUSVZF','WQ'.'==','c2'.'9jaW'.'FsbmV0d29y'.'a'.'w'.'==','YWx'.'s'.'b3d'.'f'.'Y2FsZW5'.'kYXJfdX'.'Nlc'.'g==',''.'WQ='.'=','SUQ=',''.'c'.'29jaWFsbm'.'V0d29yaw='.'=','YWxsb3df'.'Y2Fs'.'Z'.'W'.'5kY'.'XJfdXNlcg'.'==','SUQ'.'=',''.'c29jaWFsbm'.'V0d'.'29yaw==',''.'YWxsb'.'3dfY2FsZW5kYXJ'.'fdXNlcg'.'='.'=','c29jaWFsbmV0d2'.'9'.'yaw==','YWxsb3d'.'fY2'.'Fs'.'Z'.'W'.'5kYXJfZ3JvdXA=','WQ'.'==','S'.'UQ=','c29'.'jaWFsbmV0d29yaw='.'=','YWxs'.'b3d'.'fY'.'2FsZ'.'W5k'.'YXJfZ3JvdXA'.'=','SU'.'Q=','c29jaWFsbmV0d'.'2'.'9y'.'aw==','YWxsb3dfY2F'.'sZW5kY'.'XJfZ3'.'JvdXA=','QUNUSVZF',''.'W'.'Q==','Tg==',''.'Z'.'Xh0cm'.'FuZX'.'Q'.'=',''.'aW'.'J'.'sb2Nr','T25'.'B'.'ZnRlcklC'.'bG9ja0VsZW1lbnRVc'.'GRhdGU=','aW5'.'0cmFuZXQ=','Q0lu'.'dHJhbmV0RXZlbnR'.'IYW5kbG'.'Vy'.'cw==','U1BSZWdpc3RlclV'.'wZGF'.'0ZWR'.'Jd'.'GV'.'t','Q0lud'.'HJhbmV'.'0U2hhc'.'mVwb2l'.'udDo6QWdlbnRMaX'.'N'.'0cyg'.'pOw==','aW50cmF'.'u'.'Z'.'X'.'Q=','Tg='.'=',''.'Q0l'.'udHJhbmV0U2'.'hhcmVwb'.'2l'.'u'.'dDo6Q'.'Wdl'.'bnRR'.'dWV1ZSgpOw==','aW'.'50'.'cmFuZX'.'Q=',''.'Tg='.'=','Q0'.'ludHJhbmV0U2hh'.'cmVwb2lud'.'Do6QWd'.'lbnRVcGRhdG'.'Uo'.'KTs=','aW50cmFuZ'.'XQ=','Tg'.'==',''.'aWJsb2'.'Nr','T2'.'5BZn'.'Rl'.'cklC'.'bG9ja0VsZW1lbnR'.'BZG'.'Q'.'=','aW50cmFu'.'ZXQ=',''.'Q0'.'lu'.'dH'.'JhbmV0RXZlbnRI'.'YW5kbGVy'.'cw'.'==','U1BSZ'.'Wdpc'.'3R'.'lc'.'lVwZGF0ZW'.'RJdGVt',''.'aWJsb'.'2'.'Nr','T2'.'5BZnRlc'.'klCb'.'G9j'.'a'.'0Vs'.'ZW1lbnRVc'.'GRhdG'.'U'.'=','aW5'.'0cmF'.'uZXQ'.'=',''.'Q0ludHJhbmV0RXZlbnRIYW5k'.'b'.'GVycw==','U1BSZWdpc3RlclVwZGF0'.'ZWRJdGVt','Q0lu'.'dH'.'Jh'.'bmV0U2'.'h'.'hcmV'.'w'.'b2ludDo6QWdlbnRMa'.'XN'.'0cy'.'gpO'.'w==','aW50'.'cmFuZ'.'XQ=','Q0ludHJh'.'bm'.'V0U2h'.'h'.'cmVwb2lu'.'dDo'.'6'.'QWdlb'.'nRRdWV1ZS'.'gp'.'O'.'w==','aW50'.'cm'.'F'.'uZXQ=','Q0lud'.'HJhb'.'mV0U2hh'.'c'.'mVwb2ludD'.'o6Q'.'W'.'d'.'lbnRV'.'cGRhd'.'GUoKTs'.'=','aW5'.'0c'.'mFuZXQ=',''.'Y3Jt','bWFpb'.'g='.'=','T25CZW'.'Z'.'vcmVQcm9s'.'b2c=','bWF'.'pbg==','Q1'.'dpemF'.'yZFNv'.'b'.'FBhbmVsS'.'W50cmF'.'uZXQ=','U'.'2hvd'.'1BhbmVs','L21v'.'ZHV'.'sZXMvaW5'.'0cmFuZ'.'XQvcGF'.'uZ'.'Wx'.'fYnV0'.'d'.'G'.'9uLnBocA==',''.'R'.'U5'.'DT0'.'RF','W'.'Q'.'==');return base64_decode($_677373536[$_187983546]);}};$GLOBALS['____1864780129'][0](___1340574425(0), ___1340574425(1));class CBXFeatures{ private static $_19613891= 30; private static $_1345912999= array( "Portal" => array( "CompanyCalendar", "CompanyPhoto", "CompanyVideo", "CompanyCareer", "StaffChanges", "StaffAbsence", "CommonDocuments", "MeetingRoomBookingSystem", "Wiki", "Learning", "Vote", "WebLink", "Subscribe", "Friends", "PersonalFiles", "PersonalBlog", "PersonalPhoto", "PersonalForum", "Blog", "Forum", "Gallery", "Board", "MicroBlog", "WebMessenger",), "Communications" => array( "Tasks", "Calendar", "Workgroups", "Jabber", "VideoConference", "Extranet", "SMTP", "Requests", "DAV", "intranet_sharepoint", "timeman", "Idea", "Meeting", "EventList", "Salary", "XDImport",), "Enterprise" => array( "BizProc", "Lists", "Support", "Analytics", "crm", "Controller", "LdapUnlimitedUsers",), "Holding" => array( "Cluster", "MultiSites",),); private static $_629575428= false; private static $_176666815= false; private static function __622933032(){ if(self::$_629575428 == false){ self::$_629575428= array(); foreach(self::$_1345912999 as $_114902561 => $_457392809){ foreach($_457392809 as $_865024223) self::$_629575428[$_865024223]= $_114902561;}} if(self::$_176666815 == false){ self::$_176666815= array(); $_82312530= COption::GetOptionString(___1340574425(2), ___1340574425(3), ___1340574425(4)); if($GLOBALS['____1864780129'][1]($_82312530)>(1452/2-726)){ $_82312530= $GLOBALS['____1864780129'][2]($_82312530); self::$_176666815= $GLOBALS['____1864780129'][3]($_82312530); if(!$GLOBALS['____1864780129'][4](self::$_176666815)) self::$_176666815= array();} if($GLOBALS['____1864780129'][5](self::$_176666815) <=(1044/2-522)) self::$_176666815= array(___1340574425(5) => array(), ___1340574425(6) => array());}} public static function InitiateEditionsSettings($_813093055){ self::__622933032(); $_839827443= array(); foreach(self::$_1345912999 as $_114902561 => $_457392809){ $_1564123192= $GLOBALS['____1864780129'][6]($_114902561, $_813093055); self::$_176666815[___1340574425(7)][$_114902561]=($_1564123192? array(___1340574425(8)): array(___1340574425(9))); foreach($_457392809 as $_865024223){ self::$_176666815[___1340574425(10)][$_865024223]= $_1564123192; if(!$_1564123192) $_839827443[]= array($_865024223, false);}} $_1193631916= $GLOBALS['____1864780129'][7](self::$_176666815); $_1193631916= $GLOBALS['____1864780129'][8]($_1193631916); COption::SetOptionString(___1340574425(11), ___1340574425(12), $_1193631916); foreach($_839827443 as $_1192589242) self::__1368477245($_1192589242[(228*2-456)], $_1192589242[round(0+1)]);} public static function IsFeatureEnabled($_865024223){ if($GLOBALS['____1864780129'][9]($_865024223) <= 0) return true; self::__622933032(); if(!$GLOBALS['____1864780129'][10]($_865024223, self::$_629575428)) return true; if(self::$_629575428[$_865024223] == ___1340574425(13)) $_2105306142= array(___1340574425(14)); elseif($GLOBALS['____1864780129'][11](self::$_629575428[$_865024223], self::$_176666815[___1340574425(15)])) $_2105306142= self::$_176666815[___1340574425(16)][self::$_629575428[$_865024223]]; else $_2105306142= array(___1340574425(17)); if($_2105306142[(180*2-360)] != ___1340574425(18) && $_2105306142[(1220/2-610)] != ___1340574425(19)){ return false;} elseif($_2105306142[min(66,0,22)] == ___1340574425(20)){ if($_2105306142[round(0+0.2+0.2+0.2+0.2+0.2)]< $GLOBALS['____1864780129'][12]((794-2*397),(998-2*499),(956-2*478), Date(___1340574425(21)), $GLOBALS['____1864780129'][13](___1340574425(22))- self::$_19613891, $GLOBALS['____1864780129'][14](___1340574425(23)))){ if(!isset($_2105306142[round(0+2)]) ||!$_2105306142[round(0+1+1)]) self::__1049774721(self::$_629575428[$_865024223]); return false;}} return!$GLOBALS['____1864780129'][15]($_865024223, self::$_176666815[___1340574425(24)]) || self::$_176666815[___1340574425(25)][$_865024223];} public static function IsFeatureInstalled($_865024223){ if($GLOBALS['____1864780129'][16]($_865024223) <= 0) return true; self::__622933032(); return($GLOBALS['____1864780129'][17]($_865024223, self::$_176666815[___1340574425(26)]) && self::$_176666815[___1340574425(27)][$_865024223]);} public static function IsFeatureEditable($_865024223){ if($GLOBALS['____1864780129'][18]($_865024223) <= 0) return true; self::__622933032(); if(!$GLOBALS['____1864780129'][19]($_865024223, self::$_629575428)) return true; if(self::$_629575428[$_865024223] == ___1340574425(28)) $_2105306142= array(___1340574425(29)); elseif($GLOBALS['____1864780129'][20](self::$_629575428[$_865024223], self::$_176666815[___1340574425(30)])) $_2105306142= self::$_176666815[___1340574425(31)][self::$_629575428[$_865024223]]; else $_2105306142= array(___1340574425(32)); if($_2105306142[(926-2*463)] != ___1340574425(33) && $_2105306142[(128*2-256)] != ___1340574425(34)){ return false;} elseif($_2105306142[min(200,0,66.666666666667)] == ___1340574425(35)){ if($_2105306142[round(0+0.33333333333333+0.33333333333333+0.33333333333333)]< $GLOBALS['____1864780129'][21]((872-2*436),(149*2-298),(248*2-496), Date(___1340574425(36)), $GLOBALS['____1864780129'][22](___1340574425(37))- self::$_19613891, $GLOBALS['____1864780129'][23](___1340574425(38)))){ if(!isset($_2105306142[round(0+0.4+0.4+0.4+0.4+0.4)]) ||!$_2105306142[round(0+0.4+0.4+0.4+0.4+0.4)]) self::__1049774721(self::$_629575428[$_865024223]); return false;}} return true;} private static function __1368477245($_865024223, $_2122245669){ if($GLOBALS['____1864780129'][24]("CBXFeatures", "On".$_865024223."SettingsChange")) $GLOBALS['____1864780129'][25](array("CBXFeatures", "On".$_865024223."SettingsChange"), array($_865024223, $_2122245669)); $_2060151259= $GLOBALS['_____861528270'][0](___1340574425(39), ___1340574425(40).$_865024223.___1340574425(41)); while($_624862558= $_2060151259->Fetch()) $GLOBALS['_____861528270'][1]($_624862558, array($_865024223, $_2122245669));} public static function SetFeatureEnabled($_865024223, $_2122245669= true, $_172661263= true){ if($GLOBALS['____1864780129'][26]($_865024223) <= 0) return; if(!self::IsFeatureEditable($_865024223)) $_2122245669= false; $_2122245669=($_2122245669? true: false); self::__622933032(); $_1451230797=(!$GLOBALS['____1864780129'][27]($_865024223, self::$_176666815[___1340574425(42)]) && $_2122245669 || $GLOBALS['____1864780129'][28]($_865024223, self::$_176666815[___1340574425(43)]) && $_2122245669 != self::$_176666815[___1340574425(44)][$_865024223]); self::$_176666815[___1340574425(45)][$_865024223]= $_2122245669; $_1193631916= $GLOBALS['____1864780129'][29](self::$_176666815); $_1193631916= $GLOBALS['____1864780129'][30]($_1193631916); COption::SetOptionString(___1340574425(46), ___1340574425(47), $_1193631916); if($_1451230797 && $_172661263) self::__1368477245($_865024223, $_2122245669);} private static function __1049774721($_114902561){ if($GLOBALS['____1864780129'][31]($_114902561) <= 0 || $_114902561 == "Portal") return; self::__622933032(); if(!$GLOBALS['____1864780129'][32]($_114902561, self::$_176666815[___1340574425(48)]) || $GLOBALS['____1864780129'][33]($_114902561, self::$_176666815[___1340574425(49)]) && self::$_176666815[___1340574425(50)][$_114902561][(1208/2-604)] != ___1340574425(51)) return; if(isset(self::$_176666815[___1340574425(52)][$_114902561][round(0+0.66666666666667+0.66666666666667+0.66666666666667)]) && self::$_176666815[___1340574425(53)][$_114902561][round(0+0.66666666666667+0.66666666666667+0.66666666666667)]) return; $_839827443= array(); if($GLOBALS['____1864780129'][34]($_114902561, self::$_1345912999) && $GLOBALS['____1864780129'][35](self::$_1345912999[$_114902561])){ foreach(self::$_1345912999[$_114902561] as $_865024223){ if($GLOBALS['____1864780129'][36]($_865024223, self::$_176666815[___1340574425(54)]) && self::$_176666815[___1340574425(55)][$_865024223]){ self::$_176666815[___1340574425(56)][$_865024223]= false; $_839827443[]= array($_865024223, false);}} self::$_176666815[___1340574425(57)][$_114902561][round(0+0.66666666666667+0.66666666666667+0.66666666666667)]= true;} $_1193631916= $GLOBALS['____1864780129'][37](self::$_176666815); $_1193631916= $GLOBALS['____1864780129'][38]($_1193631916); COption::SetOptionString(___1340574425(58), ___1340574425(59), $_1193631916); foreach($_839827443 as $_1192589242) self::__1368477245($_1192589242[min(136,0,45.333333333333)], $_1192589242[round(0+0.25+0.25+0.25+0.25)]);} public static function ModifyFeaturesSettings($_813093055, $_457392809){ self::__622933032(); foreach($_813093055 as $_114902561 => $_482423557) self::$_176666815[___1340574425(60)][$_114902561]= $_482423557; $_839827443= array(); foreach($_457392809 as $_865024223 => $_2122245669){ if(!$GLOBALS['____1864780129'][39]($_865024223, self::$_176666815[___1340574425(61)]) && $_2122245669 || $GLOBALS['____1864780129'][40]($_865024223, self::$_176666815[___1340574425(62)]) && $_2122245669 != self::$_176666815[___1340574425(63)][$_865024223]) $_839827443[]= array($_865024223, $_2122245669); self::$_176666815[___1340574425(64)][$_865024223]= $_2122245669;} $_1193631916= $GLOBALS['____1864780129'][41](self::$_176666815); $_1193631916= $GLOBALS['____1864780129'][42]($_1193631916); COption::SetOptionString(___1340574425(65), ___1340574425(66), $_1193631916); self::$_176666815= false; foreach($_839827443 as $_1192589242) self::__1368477245($_1192589242[(192*2-384)], $_1192589242[round(0+0.33333333333333+0.33333333333333+0.33333333333333)]);} public static function SaveFeaturesSettings($_868103237, $_381083013){ self::__622933032(); $_1869406495= array(___1340574425(67) => array(), ___1340574425(68) => array()); if(!$GLOBALS['____1864780129'][43]($_868103237)) $_868103237= array(); if(!$GLOBALS['____1864780129'][44]($_381083013)) $_381083013= array(); if(!$GLOBALS['____1864780129'][45](___1340574425(69), $_868103237)) $_868103237[]= ___1340574425(70); foreach(self::$_1345912999 as $_114902561 => $_457392809){ if($GLOBALS['____1864780129'][46]($_114902561, self::$_176666815[___1340574425(71)])) $_1801013543= self::$_176666815[___1340574425(72)][$_114902561]; else $_1801013543=($_114902561 == ___1340574425(73))? array(___1340574425(74)): array(___1340574425(75)); if($_1801013543[min(204,0,68)] == ___1340574425(76) || $_1801013543[min(162,0,54)] == ___1340574425(77)){ $_1869406495[___1340574425(78)][$_114902561]= $_1801013543;} else{ if($GLOBALS['____1864780129'][47]($_114902561, $_868103237)) $_1869406495[___1340574425(79)][$_114902561]= array(___1340574425(80), $GLOBALS['____1864780129'][48]((165*2-330), min(174,0,58),(225*2-450), $GLOBALS['____1864780129'][49](___1340574425(81)), $GLOBALS['____1864780129'][50](___1340574425(82)), $GLOBALS['____1864780129'][51](___1340574425(83)))); else $_1869406495[___1340574425(84)][$_114902561]= array(___1340574425(85));}} $_839827443= array(); foreach(self::$_629575428 as $_865024223 => $_114902561){ if($_1869406495[___1340574425(86)][$_114902561][(944-2*472)] != ___1340574425(87) && $_1869406495[___1340574425(88)][$_114902561][(1384/2-692)] != ___1340574425(89)){ $_1869406495[___1340574425(90)][$_865024223]= false;} else{ if($_1869406495[___1340574425(91)][$_114902561][(810-2*405)] == ___1340574425(92) && $_1869406495[___1340574425(93)][$_114902561][round(0+0.25+0.25+0.25+0.25)]< $GLOBALS['____1864780129'][52]((796-2*398),(1244/2-622),(870-2*435), Date(___1340574425(94)), $GLOBALS['____1864780129'][53](___1340574425(95))- self::$_19613891, $GLOBALS['____1864780129'][54](___1340574425(96)))) $_1869406495[___1340574425(97)][$_865024223]= false; else $_1869406495[___1340574425(98)][$_865024223]= $GLOBALS['____1864780129'][55]($_865024223, $_381083013); if(!$GLOBALS['____1864780129'][56]($_865024223, self::$_176666815[___1340574425(99)]) && $_1869406495[___1340574425(100)][$_865024223] || $GLOBALS['____1864780129'][57]($_865024223, self::$_176666815[___1340574425(101)]) && $_1869406495[___1340574425(102)][$_865024223] != self::$_176666815[___1340574425(103)][$_865024223]) $_839827443[]= array($_865024223, $_1869406495[___1340574425(104)][$_865024223]);}} $_1193631916= $GLOBALS['____1864780129'][58]($_1869406495); $_1193631916= $GLOBALS['____1864780129'][59]($_1193631916); COption::SetOptionString(___1340574425(105), ___1340574425(106), $_1193631916); self::$_176666815= false; foreach($_839827443 as $_1192589242) self::__1368477245($_1192589242[min(126,0,42)], $_1192589242[round(0+0.2+0.2+0.2+0.2+0.2)]);} public static function GetFeaturesList(){ self::__622933032(); $_1651144832= array(); foreach(self::$_1345912999 as $_114902561 => $_457392809){ if($GLOBALS['____1864780129'][60]($_114902561, self::$_176666815[___1340574425(107)])) $_1801013543= self::$_176666815[___1340574425(108)][$_114902561]; else $_1801013543=($_114902561 == ___1340574425(109))? array(___1340574425(110)): array(___1340574425(111)); $_1651144832[$_114902561]= array( ___1340574425(112) => $_1801013543[(1068/2-534)], ___1340574425(113) => $_1801013543[round(0+0.25+0.25+0.25+0.25)], ___1340574425(114) => array(),); $_1651144832[$_114902561][___1340574425(115)]= false; if($_1651144832[$_114902561][___1340574425(116)] == ___1340574425(117)){ $_1651144832[$_114902561][___1340574425(118)]= $GLOBALS['____1864780129'][61](($GLOBALS['____1864780129'][62]()- $_1651144832[$_114902561][___1340574425(119)])/ round(0+21600+21600+21600+21600)); if($_1651144832[$_114902561][___1340574425(120)]> self::$_19613891) $_1651144832[$_114902561][___1340574425(121)]= true;} foreach($_457392809 as $_865024223) $_1651144832[$_114902561][___1340574425(122)][$_865024223]=(!$GLOBALS['____1864780129'][63]($_865024223, self::$_176666815[___1340574425(123)]) || self::$_176666815[___1340574425(124)][$_865024223]);} return $_1651144832;} private static function __705249227($_1842614494, $_1862928747){ if(IsModuleInstalled($_1842614494) == $_1862928747) return true; $_1017120148= $_SERVER[___1340574425(125)].___1340574425(126).$_1842614494.___1340574425(127); if(!$GLOBALS['____1864780129'][64]($_1017120148)) return false; include_once($_1017120148); $_732753495= $GLOBALS['____1864780129'][65](___1340574425(128), ___1340574425(129), $_1842614494); if(!$GLOBALS['____1864780129'][66]($_732753495)) return false; $_1000421099= new $_732753495; if($_1862928747){ if(!$_1000421099->InstallDB()) return false; $_1000421099->InstallEvents(); if(!$_1000421099->InstallFiles()) return false;} else{ if(CModule::IncludeModule(___1340574425(130))) CSearch::DeleteIndex($_1842614494); UnRegisterModule($_1842614494);} return true;} protected static function OnRequestsSettingsChange($_865024223, $_2122245669){ self::__705249227("form", $_2122245669);} protected static function OnLearningSettingsChange($_865024223, $_2122245669){ self::__705249227("learning", $_2122245669);} protected static function OnJabberSettingsChange($_865024223, $_2122245669){ self::__705249227("xmpp", $_2122245669);} protected static function OnVideoConferenceSettingsChange($_865024223, $_2122245669){ self::__705249227("video", $_2122245669);} protected static function OnBizProcSettingsChange($_865024223, $_2122245669){ self::__705249227("bizprocdesigner", $_2122245669);} protected static function OnListsSettingsChange($_865024223, $_2122245669){ self::__705249227("lists", $_2122245669);} protected static function OnWikiSettingsChange($_865024223, $_2122245669){ self::__705249227("wiki", $_2122245669);} protected static function OnSupportSettingsChange($_865024223, $_2122245669){ self::__705249227("support", $_2122245669);} protected static function OnControllerSettingsChange($_865024223, $_2122245669){ self::__705249227("controller", $_2122245669);} protected static function OnAnalyticsSettingsChange($_865024223, $_2122245669){ self::__705249227("statistic", $_2122245669);} protected static function OnVoteSettingsChange($_865024223, $_2122245669){ self::__705249227("vote", $_2122245669);} protected static function OnFriendsSettingsChange($_865024223, $_2122245669){ if($_2122245669) $_47923859= "Y"; else $_47923859= ___1340574425(131); $_1444457271= CSite::GetList(($_1564123192= ___1340574425(132)),($_1045801422= ___1340574425(133)), array(___1340574425(134) => ___1340574425(135))); while($_900145817= $_1444457271->Fetch()){ if(COption::GetOptionString(___1340574425(136), ___1340574425(137), ___1340574425(138), $_900145817[___1340574425(139)]) != $_47923859){ COption::SetOptionString(___1340574425(140), ___1340574425(141), $_47923859, false, $_900145817[___1340574425(142)]); COption::SetOptionString(___1340574425(143), ___1340574425(144), $_47923859);}}} protected static function OnMicroBlogSettingsChange($_865024223, $_2122245669){ if($_2122245669) $_47923859= "Y"; else $_47923859= ___1340574425(145); $_1444457271= CSite::GetList(($_1564123192= ___1340574425(146)),($_1045801422= ___1340574425(147)), array(___1340574425(148) => ___1340574425(149))); while($_900145817= $_1444457271->Fetch()){ if(COption::GetOptionString(___1340574425(150), ___1340574425(151), ___1340574425(152), $_900145817[___1340574425(153)]) != $_47923859){ COption::SetOptionString(___1340574425(154), ___1340574425(155), $_47923859, false, $_900145817[___1340574425(156)]); COption::SetOptionString(___1340574425(157), ___1340574425(158), $_47923859);} if(COption::GetOptionString(___1340574425(159), ___1340574425(160), ___1340574425(161), $_900145817[___1340574425(162)]) != $_47923859){ COption::SetOptionString(___1340574425(163), ___1340574425(164), $_47923859, false, $_900145817[___1340574425(165)]); COption::SetOptionString(___1340574425(166), ___1340574425(167), $_47923859);}}} protected static function OnPersonalFilesSettingsChange($_865024223, $_2122245669){ if($_2122245669) $_47923859= "Y"; else $_47923859= ___1340574425(168); $_1444457271= CSite::GetList(($_1564123192= ___1340574425(169)),($_1045801422= ___1340574425(170)), array(___1340574425(171) => ___1340574425(172))); while($_900145817= $_1444457271->Fetch()){ if(COption::GetOptionString(___1340574425(173), ___1340574425(174), ___1340574425(175), $_900145817[___1340574425(176)]) != $_47923859){ COption::SetOptionString(___1340574425(177), ___1340574425(178), $_47923859, false, $_900145817[___1340574425(179)]); COption::SetOptionString(___1340574425(180), ___1340574425(181), $_47923859);}}} protected static function OnPersonalBlogSettingsChange($_865024223, $_2122245669){ if($_2122245669) $_47923859= "Y"; else $_47923859= ___1340574425(182); $_1444457271= CSite::GetList(($_1564123192= ___1340574425(183)),($_1045801422= ___1340574425(184)), array(___1340574425(185) => ___1340574425(186))); while($_900145817= $_1444457271->Fetch()){ if(COption::GetOptionString(___1340574425(187), ___1340574425(188), ___1340574425(189), $_900145817[___1340574425(190)]) != $_47923859){ COption::SetOptionString(___1340574425(191), ___1340574425(192), $_47923859, false, $_900145817[___1340574425(193)]); COption::SetOptionString(___1340574425(194), ___1340574425(195), $_47923859);}}} protected static function OnPersonalPhotoSettingsChange($_865024223, $_2122245669){ if($_2122245669) $_47923859= "Y"; else $_47923859= ___1340574425(196); $_1444457271= CSite::GetList(($_1564123192= ___1340574425(197)),($_1045801422= ___1340574425(198)), array(___1340574425(199) => ___1340574425(200))); while($_900145817= $_1444457271->Fetch()){ if(COption::GetOptionString(___1340574425(201), ___1340574425(202), ___1340574425(203), $_900145817[___1340574425(204)]) != $_47923859){ COption::SetOptionString(___1340574425(205), ___1340574425(206), $_47923859, false, $_900145817[___1340574425(207)]); COption::SetOptionString(___1340574425(208), ___1340574425(209), $_47923859);}}} protected static function OnPersonalForumSettingsChange($_865024223, $_2122245669){ if($_2122245669) $_47923859= "Y"; else $_47923859= ___1340574425(210); $_1444457271= CSite::GetList(($_1564123192= ___1340574425(211)),($_1045801422= ___1340574425(212)), array(___1340574425(213) => ___1340574425(214))); while($_900145817= $_1444457271->Fetch()){ if(COption::GetOptionString(___1340574425(215), ___1340574425(216), ___1340574425(217), $_900145817[___1340574425(218)]) != $_47923859){ COption::SetOptionString(___1340574425(219), ___1340574425(220), $_47923859, false, $_900145817[___1340574425(221)]); COption::SetOptionString(___1340574425(222), ___1340574425(223), $_47923859);}}} protected static function OnTasksSettingsChange($_865024223, $_2122245669){ if($_2122245669) $_47923859= "Y"; else $_47923859= ___1340574425(224); $_1444457271= CSite::GetList(($_1564123192= ___1340574425(225)),($_1045801422= ___1340574425(226)), array(___1340574425(227) => ___1340574425(228))); while($_900145817= $_1444457271->Fetch()){ if(COption::GetOptionString(___1340574425(229), ___1340574425(230), ___1340574425(231), $_900145817[___1340574425(232)]) != $_47923859){ COption::SetOptionString(___1340574425(233), ___1340574425(234), $_47923859, false, $_900145817[___1340574425(235)]); COption::SetOptionString(___1340574425(236), ___1340574425(237), $_47923859);} if(COption::GetOptionString(___1340574425(238), ___1340574425(239), ___1340574425(240), $_900145817[___1340574425(241)]) != $_47923859){ COption::SetOptionString(___1340574425(242), ___1340574425(243), $_47923859, false, $_900145817[___1340574425(244)]); COption::SetOptionString(___1340574425(245), ___1340574425(246), $_47923859);}} self::__705249227(___1340574425(247), $_2122245669);} protected static function OnCalendarSettingsChange($_865024223, $_2122245669){ if($_2122245669) $_47923859= "Y"; else $_47923859= ___1340574425(248); $_1444457271= CSite::GetList(($_1564123192= ___1340574425(249)),($_1045801422= ___1340574425(250)), array(___1340574425(251) => ___1340574425(252))); while($_900145817= $_1444457271->Fetch()){ if(COption::GetOptionString(___1340574425(253), ___1340574425(254), ___1340574425(255), $_900145817[___1340574425(256)]) != $_47923859){ COption::SetOptionString(___1340574425(257), ___1340574425(258), $_47923859, false, $_900145817[___1340574425(259)]); COption::SetOptionString(___1340574425(260), ___1340574425(261), $_47923859);} if(COption::GetOptionString(___1340574425(262), ___1340574425(263), ___1340574425(264), $_900145817[___1340574425(265)]) != $_47923859){ COption::SetOptionString(___1340574425(266), ___1340574425(267), $_47923859, false, $_900145817[___1340574425(268)]); COption::SetOptionString(___1340574425(269), ___1340574425(270), $_47923859);}}} protected static function OnSMTPSettingsChange($_865024223, $_2122245669){ self::__705249227("mail", $_2122245669);} protected static function OnExtranetSettingsChange($_865024223, $_2122245669){ $_1821244303= COption::GetOptionString("extranet", "extranet_site", ""); if($_1821244303){ $_1187593986= new CSite; $_1187593986->Update($_1821244303, array(___1340574425(271) =>($_2122245669? ___1340574425(272): ___1340574425(273))));} self::__705249227(___1340574425(274), $_2122245669);} protected static function OnDAVSettingsChange($_865024223, $_2122245669){ self::__705249227("dav", $_2122245669);} protected static function OntimemanSettingsChange($_865024223, $_2122245669){ self::__705249227("timeman", $_2122245669);} protected static function Onintranet_sharepointSettingsChange($_865024223, $_2122245669){ if($_2122245669){ RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", "intranet", "CIntranetEventHandlers", "SPRegisterUpdatedItem"); RegisterModuleDependences(___1340574425(275), ___1340574425(276), ___1340574425(277), ___1340574425(278), ___1340574425(279)); CAgent::AddAgent(___1340574425(280), ___1340574425(281), ___1340574425(282), round(0+250+250)); CAgent::AddAgent(___1340574425(283), ___1340574425(284), ___1340574425(285), round(0+75+75+75+75)); CAgent::AddAgent(___1340574425(286), ___1340574425(287), ___1340574425(288), round(0+720+720+720+720+720));} else{ UnRegisterModuleDependences(___1340574425(289), ___1340574425(290), ___1340574425(291), ___1340574425(292), ___1340574425(293)); UnRegisterModuleDependences(___1340574425(294), ___1340574425(295), ___1340574425(296), ___1340574425(297), ___1340574425(298)); CAgent::RemoveAgent(___1340574425(299), ___1340574425(300)); CAgent::RemoveAgent(___1340574425(301), ___1340574425(302)); CAgent::RemoveAgent(___1340574425(303), ___1340574425(304));}} protected static function OncrmSettingsChange($_865024223, $_2122245669){ if($_2122245669) COption::SetOptionString("crm", "form_features", "Y"); self::__705249227(___1340574425(305), $_2122245669);} protected static function OnClusterSettingsChange($_865024223, $_2122245669){ self::__705249227("cluster", $_2122245669);} protected static function OnMultiSitesSettingsChange($_865024223, $_2122245669){ if($_2122245669) RegisterModuleDependences("main", "OnBeforeProlog", "main", "CWizardSolPanelIntranet", "ShowPanel", 100, "/modules/intranet/panel_button.php"); else UnRegisterModuleDependences(___1340574425(306), ___1340574425(307), ___1340574425(308), ___1340574425(309), ___1340574425(310), ___1340574425(311));} protected static function OnIdeaSettingsChange($_865024223, $_2122245669){ self::__705249227("idea", $_2122245669);} protected static function OnMeetingSettingsChange($_865024223, $_2122245669){ self::__705249227("meeting", $_2122245669);} protected static function OnXDImportSettingsChange($_865024223, $_2122245669){ self::__705249227("xdimport", $_2122245669);}} $GLOBALS['____1864780129'][67](___1340574425(312), ___1340574425(313));/**/			//Do not remove this

//component 2.0 template engines
$GLOBALS["arCustomTemplateEngines"] = [];

require_once(__DIR__."/autoload.php");
require_once(__DIR__."/classes/general/menu.php");
require_once(__DIR__."/classes/mysql/usertype.php");

if(file_exists(($_fname = __DIR__."/classes/general/update_db_updater.php")))
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
	$application->addBackgroundJob(['\Bitrix\Main\Mail\EventManager', 'checkEvents'], [], \Bitrix\Main\Application::JOB_PRIORITY_LOW-1);
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

					// die() follows
					CMain::FinalActions('<script type="text/javascript">window.onload=function(){(window.BX || window.parent.BX).AUTHAGENT.setAuthResult(false);};</script>');
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
else
{
	// prevents undefined constants
	define('SITE_TEMPLATE_ID', '.default');
	define('SITE_TEMPLATE_PATH', '/bitrix/templates/.default');
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

/*ZDUyZmZNjg1ZmY1ZjA2YzBlMGU0Zjk3NTZmMjY1MWVkNGQ5ZWM=*/$GLOBALS['____607098497']= array(base64_decode('b'.'XRfc'.'m'.'FuZA=='),base64_decode('ZXhwb'.'G9'.'kZQ'.'='.'='),base64_decode('c'.'GFj'.'aw=='),base64_decode('bWQ1'),base64_decode('Y'.'29uc3Rhb'.'nQ='),base64_decode('aG'.'FzaF9obWFj'),base64_decode('c3RyY21w'),base64_decode('aX'.'Nfb2Jq'.'ZWN0'),base64_decode('Y2Fsb'.'F91c'.'2V'.'yX2Z'.'1bmM='),base64_decode('Y2Fsb'.'F'.'91c'.'2'.'VyX2Z1bmM'.'='),base64_decode(''.'Y2FsbF91c2Vy'.'X2Z1b'.'mM='),base64_decode('Y2FsbF91'.'c2'.'Vy'.'X2Z1b'.'mM='),base64_decode('Y2FsbF91c2VyX'.'2Z'.'1bmM='));if(!function_exists(__NAMESPACE__.'\\___790815505')){function ___790815505($_730430749){static $_1691235159= false; if($_1691235159 == false) $_1691235159=array(''.'REI=','U0'.'VMRU'.'NUIFZBTFVFI'.'EZST00gY'.'l9v'.'cHRpb24g'.'V0hFUkUgTk'.'FNRT'.'0nflBB'.'U'.'k'.'FN'.'X0'.'1BWF9V'.'U0VSU'.'ycgQ'.'U5EIE1P'.'R'.'FVM'.'RV9JRD'.'0n'.'bW'.'Fpb'.'icgQU5E'.'IFNJVEVfSU'.'QgSVM'.'gT'.'lVMTA'.'==',''.'VkF'.'M'.'VUU=','Lg'.'==','S'.'Co=','Yml0cml4',''.'TEl'.'DR'.'U5TRV9LRVk=','c2h'.'hMjU2','V'.'VNFUg==','VVNF'.'Ug==','V'.'V'.'NFUg='.'=','SXNBd'.'XRob3JpemVk','VVNFUg==','SXNBZG1'.'pbg==','QVBQTElD'.'QVRJT0'.'4=','UmV'.'z'.'dGFydE'.'J'.'1'.'ZmZl'.'cg'.'==','TG9jYW'.'xS'.'ZWR'.'p'.'cmVjdA'.'==','L2xpY2Vu'.'c2V'.'fcm'.'Vz'.'dH'.'Jp'.'Y3Rpb24uc'.'Ghw','XEJpdH'.'Jp'.'eFxNYWluX'.'ENvbmZ'.'pZ1xPcHRpb2'.'46On'.'NldA==','bWFpbg==','UEFSQU'.'1fTUFYX'.'1'.'VTRVJT');return base64_decode($_1691235159[$_730430749]);}};if($GLOBALS['____607098497'][0](round(0+1), round(0+6.6666666666667+6.6666666666667+6.6666666666667)) == round(0+1.4+1.4+1.4+1.4+1.4)){ $_827155855= $GLOBALS[___790815505(0)]->Query(___790815505(1), true); if($_587019945= $_827155855->Fetch()){ $_553470159= $_587019945[___790815505(2)]; list($_651348221, $_632123857)= $GLOBALS['____607098497'][1](___790815505(3), $_553470159); $_835751541= $GLOBALS['____607098497'][2](___790815505(4), $_651348221); $_756379579= ___790815505(5).$GLOBALS['____607098497'][3]($GLOBALS['____607098497'][4](___790815505(6))); $_245600482= $GLOBALS['____607098497'][5](___790815505(7), $_632123857, $_756379579, true); if($GLOBALS['____607098497'][6]($_245600482, $_835751541) !==(830-2*415)){ if(isset($GLOBALS[___790815505(8)]) && $GLOBALS['____607098497'][7]($GLOBALS[___790815505(9)]) && $GLOBALS['____607098497'][8](array($GLOBALS[___790815505(10)], ___790815505(11))) &&!$GLOBALS['____607098497'][9](array($GLOBALS[___790815505(12)], ___790815505(13)))){ $GLOBALS['____607098497'][10](array($GLOBALS[___790815505(14)], ___790815505(15))); $GLOBALS['____607098497'][11](___790815505(16), ___790815505(17), true);}}} else{ $GLOBALS['____607098497'][12](___790815505(18), ___790815505(19), ___790815505(20), round(0+12));}}/**/       //Do not remove this

