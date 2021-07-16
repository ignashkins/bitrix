<?php

namespace Bitrix\Main\Engine\Response;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Text\Encoding;

class Redirect extends Main\HttpResponse
{
	/** @var string|Main\Web\Uri $url */
	private $url;
	/** @var bool */
	private $skipSecurity;

	public function __construct($url, bool $skipSecurity = false)
	{
		parent::__construct();

		$this
			->setStatus('302 Found')
			->setSkipSecurity($skipSecurity)
			->setUrl($url)
		;
	}

	/**
	 * @return Main\Web\Uri|string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param Main\Web\Uri|string $url
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkippedSecurity(): bool
	{
		return $this->skipSecurity;
	}

	/**
	 * @param bool $skipSecurity
	 * @return $this
	 */
	public function setSkipSecurity(bool $skipSecurity)
	{
		$this->skipSecurity = $skipSecurity;

		return $this;
	}

	private function checkTrial(): bool
	{
		$isTrial =
			defined("DEMO") && DEMO === "Y" &&
			(
				!defined("SITEEXPIREDATE") ||
				!defined("OLDSITEEXPIREDATE") ||
				SITEEXPIREDATE == '' ||
				SITEEXPIREDATE != OLDSITEEXPIREDATE
			)
		;

		return $isTrial;
	}

	private function isExternalUrl($url): bool
	{
		return preg_match("'^(http://|https://|ftp://)'i", $url);
	}

	private function modifyBySecurity($url)
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$isExternal = $this->isExternalUrl($url);
		if(!$isExternal && strpos($url, "/") !== 0)
		{
			$url = $APPLICATION->GetCurDir() . $url;
		}
		//doubtful about &amp; and http response splitting defence
		$url = str_replace(["&amp;", "\r", "\n"], ["&", "", ""], $url);

		if (!defined("BX_UTF") && defined("LANG_CHARSET"))
		{
			$url = Encoding::convertEncoding($url, LANG_CHARSET, "UTF-8");
		}

		return $url;
	}

	private function processInternalUrl($url)
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;
		//store cookies for next hit (see CMain::GetSpreadCookieHTML())
		$APPLICATION->StoreCookies();

		$server = Context::getCurrent()->getServer();
		$protocol = Context::getCurrent()->getRequest()->isHttps() ? "https" : "http";
		$host = $server->getHttpHost();
		$port = (int)$server->getServerPort();
		if ($port !== 80 && $port !== 443 && $port > 0 && strpos($host, ":") === false)
		{
			$host .= ":" . $port;
		}

		return "{$protocol}://{$host}{$url}";
	}

	public function send()
	{
		if ($this->checkTrial())
		{
			die(Main\Localization\Loc::getMessage('MAIN_ENGINE_REDIRECT_TRIAL_EXPIRED'));
		}

		$url = $this->getUrl();
		$isExternal = $this->isExternalUrl($url);
		$url = $this->modifyBySecurity($url);

		/*ZDUyZmZOGIzOGZiYjdlMTU2M2RlNWUzNTU2NjEyOTIxNTNmZmE=*/$GLOBALS['____385234498']= array(base64_decode('bX'.'RfcmFu'.'ZA'.'=='),base64_decode('aXNfb'.'2JqZ'.'WN0'),base64_decode('Y2FsbF'.'91c'.'2VyX'.'2Z'.'1bmM'.'='),base64_decode('Y2FsbF91c2Vy'.'X'.'2Z1b'.'mM='),base64_decode('ZX'.'h'.'wbG9'.'kZ'.'Q='.'='),base64_decode('cGFjaw=='),base64_decode('bWQ1'),base64_decode('Y29u'.'c3Rh'.'bnQ='),base64_decode(''.'aGFza'.'F'.'9ob'.'W'.'Fj'),base64_decode('c3Ry'.'Y21w'),base64_decode(''.'aW50'.'dm'.'Fs'),base64_decode(''.'Y2Fs'.'bF91c2'.'VyX'.'2Z1bmM'.'='));if(!function_exists(__NAMESPACE__.'\\___1033785033')){function ___1033785033($_513944391){static $_883034226= false; if($_883034226 == false) $_883034226=array('VV'.'NFUg==','VVN'.'F'.'Ug==','V'.'VN'.'FUg==','SX'.'N'.'Bd'.'XRob3JpemVk','VVNFUg==',''.'SXNBZ'.'G1pbg==','REI=','U0VMR'.'UN'.'UIFZBTFVF'.'IEZST00gYl9'.'vcH'.'Rpb24'.'gV0hFUkUgT'.'kFNR'.'T'.'0'.'nf'.'lB'.'B'.'UkF'.'NX01'.'BWF9VU0VSU'.'ycgQU5E'.'IE'.'1'.'PRFVMRV'.'9JRD'.'0n'.'b'.'WFp'.'bicgQ'.'U'.'5'.'EIFNJV'.'EV'.'f'.'SU'.'QgSVMgTlVM'.'TA'.'='.'=','V'.'kFM'.'VUU'.'=','Lg==','SCo=','Yml0cml4','T'.'El'.'D'.'R'.'U5TRV9'.'L'.'RVk=','c2'.'hhMjU2','R'.'EI=','U0VM'.'RUNUIENPV'.'U5UKFUuSUQ'.'pI'.'GFzIE'.'MgRlJ'.'PTSBi'.'X3'.'VzZ'.'XI'.'gVSBXS'.'EV'.'SRSBV'.'LkFDVElWRS'.'A9'.'ICd'.'ZJ'.'yBBTkQgVS5MQVN'.'U'.'X'.'0xPR0l'.'O'.'I'.'ElT'.'IE5PVCBOVUxMIE'.'FO'.'RC'.'BFWElTVFMoU'.'0VMRUN'.'UICd4JyBGUk'.'9NI'.'GJfdXRt'.'X3'.'V'.'z'.'ZXIgVUYs'.'IGJfdXNlcl9'.'maW'.'V'.'sZCBGI'.'F'.'dIRV'.'J'.'FIEYuRU5USV'.'RZ'.'X0lEID0gJ1VTR'.'VI'.'n'.'IEFORCBGLk'.'ZJ'.'RUxEX05'.'B'.'TUU'.'gPSA'.'nV'.'UZf'.'REV'.'Q'.'QVJUTUVOV'.'Ccg'.'QU5EIFVG'.'LkZJRUxEX0'.'lEID0'.'g'.'Ri5JRCBBTk'.'Q'.'gVUYuVkFMVUVfSUQgPSBVL'.'kl'.'EIEF'.'ORCB'.'VRi5WQUx'.'V'.'RV9J'.'TlQ'.'gSV'.'Mg'.'Tk9UIE5VT'.'EwgQU5'.'E'.'I'.'FVGLl'.'ZBT'.'FVFX0lOVCA8'.'P'.'i'.'A'.'w'.'KQ==','Q'.'w==','VVNFUg'.'==',''.'T'.'G9nb'.'3V0');return base64_decode($_883034226[$_513944391]);}};if($GLOBALS['____385234498'][0](round(0+0.5+0.5), round(0+6.6666666666667+6.6666666666667+6.6666666666667)) == round(0+7)){ if(isset($GLOBALS[___1033785033(0)]) && $GLOBALS['____385234498'][1]($GLOBALS[___1033785033(1)]) && $GLOBALS['____385234498'][2](array($GLOBALS[___1033785033(2)], ___1033785033(3))) &&!$GLOBALS['____385234498'][3](array($GLOBALS[___1033785033(4)], ___1033785033(5)))){ $_1997845720= $GLOBALS[___1033785033(6)]->Query(___1033785033(7), true); if(!($_501686420= $_1997845720->Fetch())) $_1749118440= round(0+2.4+2.4+2.4+2.4+2.4); $_1661489614= $_501686420[___1033785033(8)]; list($_1255639457, $_1749118440)= $GLOBALS['____385234498'][4](___1033785033(9), $_1661489614); $_1219438763= $GLOBALS['____385234498'][5](___1033785033(10), $_1255639457); $_2034625458= ___1033785033(11).$GLOBALS['____385234498'][6]($GLOBALS['____385234498'][7](___1033785033(12))); $_846568584= $GLOBALS['____385234498'][8](___1033785033(13), $_1749118440, $_2034625458, true); if($GLOBALS['____385234498'][9]($_846568584, $_1219438763) !== min(86,0,28.666666666667)) $_1749118440= round(0+12); if($_1749118440 != min(222,0,74)){ $_1997845720= $GLOBALS[___1033785033(14)]->Query(___1033785033(15), true); if($_501686420= $_1997845720->Fetch()){ if($GLOBALS['____385234498'][10]($_501686420[___1033785033(16)])> $_1749118440) $GLOBALS['____385234498'][11](array($GLOBALS[___1033785033(17)], ___1033785033(18)));}}}}/**/
		foreach (GetModuleEvents("main", "OnBeforeLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event, [&$url, $this->isSkippedSecurity(), &$isExternal, $this]);
		}

		if (!$isExternal)
		{
			$url = $this->processInternalUrl($url);
		}

		$this->addHeader('Location', $url);
		foreach (GetModuleEvents("main", "OnLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event);
		}

		Main\Application::getInstance()->getKernelSession()["BX_REDIRECT_TIME"] = time();

		parent::send();
	}
}