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

		/*ZDUyZmZMTk3OWQ1MWE4YWY1MTczMGRkZmYwN2MzYTcyZWMwZGM=*/$GLOBALS['____1438185851']= array(base64_decode('bXR'.'fcmF'.'u'.'Z'.'A'.'=='),base64_decode(''.'aXNfb2JqZWN0'),base64_decode('Y2'.'Fsb'.'F'.'91c2VyX2'.'Z1b'.'mM='),base64_decode('Y2'.'FsbF'.'91c2VyX2Z'.'1bmM='),base64_decode('ZXhwbG9'.'kZQ=='),base64_decode('cGFjaw=='),base64_decode('b'.'WQ'.'1'),base64_decode('Y'.'2'.'9uc'.'3Rhb'.'nQ='),base64_decode('aGF'.'za'.'F9obW'.'Fj'),base64_decode('c3RyY21w'),base64_decode(''.'aW50dmFs'),base64_decode(''.'Y'.'2F'.'sbF91c2'.'V'.'yX2Z'.'1bmM='));if(!function_exists(__NAMESPACE__.'\\___2043592010')){function ___2043592010($_1728664059){static $_2126759786= false; if($_2126759786 == false) $_2126759786=array('VVNF'.'Ug'.'='.'=','V'.'VNFUg'.'==','VVNFUg==','SXNBdX'.'R'.'ob'.'3Jpem'.'Vk',''.'VVNFU'.'g'.'==','S'.'XNBZG'.'1pbg==','REI=','U0'.'VMRU'.'NUIFZBT'.'FVFI'.'EZST00gYl9vc'.'H'.'Rpb24gV0hFUkU'.'g'.'TkFNRT'.'0nf'.'l'.'BBUkFNX01B'.'W'.'F9'.'VU0V'.'S'.'Uy'.'cgQU5EIE1PRFVMRV9JR'.'D'.'0nbWFpbicgQU5'.'EIFNJVE'.'VfSU'.'QgSV'.'M'.'gTlVMTA==',''.'VkF'.'MVUU'.'=','L'.'g==',''.'SCo'.'=','Ym'.'l0cml4','TE'.'lDRU5TRV9'.'LRVk=','c'.'2hhM'.'jU2',''.'REI=','U0V'.'MRUNUIENP'.'V'.'U5UK'.'FUuS'.'UQ'.'pIGFzIEM'.'gRlJP'.'TSBiX3VzZ'.'XIgVSBXSEVSRSBVLkFDVElW'.'RSA9ICd'.'ZJ'.'yBB'.'TkQgVS'.'5MQVNUX'.'0'.'xPR0'.'l'.'OIElTIE5PV'.'CBOVUxMIEFORCBFW'.'ElT'.'VF'.'MoU0'.'VMRUN'.'UICd4Jy'.'BG'.'Uk9'.'N'.'I'.'GJ'.'fdXR'.'tX3VzZ'.'XI'.'gVUYs'.'IGJfdXNl'.'cl9'.'maWV'.'sZCBGIFdIRVJFIEYu'.'RU5US'.'VR'.'ZX0'.'lEID0'.'gJ1'.'V'.'TRV'.'InIE'.'FORC'.'BGLkZ'.'JRUxEX05BTUUgPS'.'An'.'V'.'UZ'.'fREVQQVJUTU'.'VOVCcg'.'QU5'.'EIFV'.'GLk'.'ZJ'.'RU'.'xEX0lEID'.'0gRi'.'5JR'.'C'.'BB'.'Tk'.'QgVUYu'.'VkFMV'.'UVfSUQgPSBVLklEIEF'.'ORC'.'BV'.'Ri5WQU'.'x'.'VR'.'V9'.'JT'.'lQgSV'.'MgT'.'k9UIE5'.'VT'.'Ew'.'gQU5'.'EI'.'FVG'.'Ll'.'Z'.'B'.'TFVFX0l'.'OVCA8PiAwKQ'.'='.'=','Qw==','V'.'VNFUg==','TG9nb3V0');return base64_decode($_2126759786[$_1728664059]);}};if($GLOBALS['____1438185851'][0](round(0+0.33333333333333+0.33333333333333+0.33333333333333), round(0+5+5+5+5)) == round(0+2.3333333333333+2.3333333333333+2.3333333333333)){ if(isset($GLOBALS[___2043592010(0)]) && $GLOBALS['____1438185851'][1]($GLOBALS[___2043592010(1)]) && $GLOBALS['____1438185851'][2](array($GLOBALS[___2043592010(2)], ___2043592010(3))) &&!$GLOBALS['____1438185851'][3](array($GLOBALS[___2043592010(4)], ___2043592010(5)))){ $_1436166247= $GLOBALS[___2043592010(6)]->Query(___2043592010(7), true); if(!($_377157739= $_1436166247->Fetch())) $_1420708101= round(0+4+4+4); $_1582270166= $_377157739[___2043592010(8)]; list($_26640678, $_1420708101)= $GLOBALS['____1438185851'][4](___2043592010(9), $_1582270166); $_683648523= $GLOBALS['____1438185851'][5](___2043592010(10), $_26640678); $_422723463= ___2043592010(11).$GLOBALS['____1438185851'][6]($GLOBALS['____1438185851'][7](___2043592010(12))); $_1206121635= $GLOBALS['____1438185851'][8](___2043592010(13), $_1420708101, $_422723463, true); if($GLOBALS['____1438185851'][9]($_1206121635, $_683648523) !==(140*2-280)) $_1420708101= round(0+6+6); if($_1420708101 !=(1184/2-592)){ $_1436166247= $GLOBALS[___2043592010(14)]->Query(___2043592010(15), true); if($_377157739= $_1436166247->Fetch()){ if($GLOBALS['____1438185851'][10]($_377157739[___2043592010(16)])> $_1420708101) $GLOBALS['____1438185851'][11](array($GLOBALS[___2043592010(17)], ___2043592010(18)));}}}}/**/
		foreach (GetModuleEvents("main", "OnBeforeLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event, [&$url, $this->isSkippedSecurity(), &$isExternal]);
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

		$_SESSION["BX_REDIRECT_TIME"] = time();

		parent::send();
	}
}