<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("webdav")):
    ShowError(GetMessage("WD_WD_MODULE_IS_NOT_INSTALLED")); 
    return 0;
elseif (!CModule::IncludeModule("iblock")):
    ShowError(GetMessage("WD_IB_MODULE_IS_NOT_INSTALLED")); 
    return 0;
elseif (!CModule::IncludeModule("socialnetwork")):
    ShowError(GetMessage("WD_SN_MODULE_IS_NOT_INSTALLED")); 
    return 0;
endif;


global $USER;

$arDefaultUrlTemplates404 = array(
    "USER_FILE_PATH" => 'company/personal/user/#USER_ID#/files/lib/#PATH#',
    "GROUP_FILE_PATH" => 'workgroups/group/#GROUP_ID#/files/#PATH#',
);

$modes = array(
    'group' => GetMessage('WD_GROUP'),
    'private' =>GetMessage('WD_PRIVATE'),
    'user' =>GetMessage('WD_USER'),
    'root' => ''
);

$arParams['SEF_MODE'] = $arParams['SEF_MODE']=='N'?'N':'Y';
$arParams['CACHE_TIME'] = intval($arParams['CACHE_TIME']);
$arParams["IBLOCK_USER_ID"] = intval($arParams["IBLOCK_USER_ID"]);
$arParams["IBLOCK_GROUP_ID"] = intval($arParams["IBLOCK_GROUP_ID"]);
if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
    $arParams["NAME_TEMPLATE"] = GetMessage('WD_NAME_TEMPLATE_DEFAULT');
$cachePath = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName."/");
$arParams["EXPAND_ALL"] = "N";

$keys = array_keys($arParams["IBLOCK_OTHER_IDS"]);
foreach ($keys as $key)
{
    $id = intval($arParams["IBLOCK_OTHER_IDS"][$key]);
    if ($id > 0 && $id != $arParams['IBLOCK_USER_ID'] && $id != $arParams['IBLOCK_GROUP_ID'])
    {
        $arParams["IBLOCK_OTHER_IDS"][$key] = $id;
        $dbRes = CIBlock::GetByID($id);
        if ($dbRes && $arRes = $dbRes->Fetch())
        {
            $path = $arRes['LIST_PAGE_URL'];
            if (SubStr($path,0,1) != '/') $path = '/'.$path;
            if (SubStr($path,-1,1) != '/') $path .= '/';
            $path .= '#PATH#';
            if (SITE_ID == $arRes['LID'])
            {
                $arDefaultUrlTemplates404 = array('i'.$id => $path) + $arDefaultUrlTemplates404;
                $modes = array($id => $arRes['NAME']) + $modes;
            } else {
                unset($arParams["IBLOCK_OTHER_IDS"][$key]);
            }
        } else {
            unset($arParams["IBLOCK_OTHER_IDS"][$key]);
        }
    } else {
        unset($arParams["IBLOCK_OTHER_IDS"][$key]);
    }
}

$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
unset($arUrlTemplates['SEF_FOLDER']);
$arParams = array_merge($arParams, $arUrlTemplates);


$currentUserID = $USER->GetID();

if (SubStr($arParams["GROUP_FILE_PATH"],0,1) != '/') $arParams["GROUP_FILE_PATH"] = '/'.$arParams["GROUP_FILE_PATH"];
if (SubStr($arParams["USER_FILE_PATH"],0,1) != '/') $arParams["USER_FILE_PATH"] = '/'.$arParams["USER_FILE_PATH"];

$rIBGroup = CIBlock::GetByID($arParams["IBLOCK_GROUP_ID"]);
if (!($arIBGroup = $rIBGroup->Fetch()) && CBXFeatures::IsFeatureEnabled("Workgroups"))
{
    ShowError(GetMessage("WD_IB_GROUP_IS_NOT_FOUND")); 
    return 0;
}

$rIBUser = CIBlock::GetByID($arParams["IBLOCK_USER_ID"]);
if (!($arIBUser = $rIBUser->Fetch()))
{
    ShowError(GetMessage("WD_IB_USER_IS_NOT_FOUND")); 
    return 0;
}

$file = trim(preg_replace("'[\\\\/]+'", "/", ($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/socialnetwork_group/lang/".LANGUAGE_ID."/include/webdav.php")));
if (file_exists($file))
    __IncludeLang($file);
else
{
    CHTTP::SetStatus('404 Not Found');
    ShowError(GetMessage("WD_SOCNET_LANG_NOT_FOUND")); 
    return 0;
}

$arVariables = array();
$sPath = false;
$mode = false;

if ($arParams["SEF_MODE"] === "Y")
{
    $requestURL = $APPLICATION->GetCurPage(); 
    $arParams["SEF_FOLDER"] = str_replace("\\", "/", $arParams["SEF_FOLDER"]);
    if ($arParams["SEF_FOLDER"] != "/")
        $arParams["SEF_FOLDER"] = "/".Trim($arParams["SEF_FOLDER"], "/ \t\n\r\0\x0B")."/";
    if (!preg_match("'/$'", $requestURL)) $currentPageUrl = $currentPageUrl.'/';
    $currentPageUrl = SubStr($requestURL, StrLen($arParams["SEF_FOLDER"])); 
    if ($currentPageUrl == false) $currentPageUrl = '/';
} else {
    ShowError(GetMessage("WD_NOT_SEF_MODE")); 
    return 0;
}


if (!preg_match("'^/'", $currentPageUrl)) $currentPageUrl = '/'.$currentPageUrl;
if (!preg_match("'/$'", $currentPageUrl)) $currentPageUrl = $currentPageUrl.'/';


foreach ($modes as $modeName=>$path)
{
    if (preg_match_all("'/{$path}(.*)'", $currentPageUrl, $arValues))
    {
        $mode = $modeName;
        $localPath = $arValues[1][0];
        break;
    }
}

if (isset($_SERVER['HTTP_DESTINATION']))
{
    $_SERVER['HTTP_DESTINATION'] = CWebDavBase::_udecode($_SERVER['HTTP_DESTINATION']);
    $pu = parse_url($_SERVER['HTTP_DESTINATION']);
    $pu['path'] = substr($pu['path'],  strlen($arParams['SEF_FOLDER']));
    foreach ($modes as $modeName=>$path)
    {
        if (preg_match_all("'/{$path}(.*)'", $pu['path'], $arValues))
        {
            $destPath = $arValues[1][0];
            break;
        }
    }
}

$rootPath = CWebDavBase::_udecode($arParams['SEF_FOLDER'].$modes[$mode]);
$fullPath = $rootPath.$localPath;
if ($currentPageUrl != '/') $currentPageUrl = rtrim($currentPageUrl, '/');


if (!function_exists('_getIBlockItemsCount'))
{
     function _getIBlockItemsCount($ib, $section)
     {
         $res = CIBlockElement::GetList( array(), Array("IBLOCK_ID"=>intval($ib), "SECTION_ID" => intval($section), "INCLUDE_SUBSECTIONS" => "Y", "ACTIVE" => "Y"));
         if ($res) 
         {
             $res->NavStart();
             return $res->NavRecordCount;
         } else return 0;
     }
}

if (!function_exists('_getName'))
{
    function _getName($path)
    {
        static $dav;
        if (empty($dav))
            $dav = (CWebDavBase::IsDavHeaders('check_all')?'D':'W');
        if ($dav == 'W')
        {
            $arPath = explode('/', trim($path, '/'));
            return $arPath[sizeof($arPath)-1];
        } else 
            return $path;
    }
}

if (!function_exists("_wd_aggregator_sort"))
{
    function _wd_aggregator_sort($res1, $res2)
    {
        return ($res1['NAME'] < $res2['NAME'] ? -1 : 1); 
    }
}

if (!function_exists("__wd_check_uf_use_bp_property"))
{
	function __wd_check_uf_use_bp_property($iblock_id)
	{
		$iblock_id = intval($iblock_id); 
		$db_res = CUserTypeEntity::GetList(array($by=>$order), array("ENTITY_ID" => "IBLOCK_".$iblock_id."_SECTION", "FIELD_NAME" => "UF_USE_BP"));
		if (!$db_res || !($res = $db_res->GetNext()))
		{
			$arFields = Array(
				"ENTITY_ID" => "IBLOCK_".$iblock_id."_SECTION",
				"FIELD_NAME" => "UF_USE_BP",
				"USER_TYPE_ID" => "string",
				"MULTIPLE" => "N",
				"MANDATORY" => "N", 
				"SETTINGS" => array("DEFAULT_VALUE" => "Y"));
			$arFieldName = array();
			$rsLanguage = CLanguage::GetList($by, $order, array());
			while($arLanguage = $rsLanguage->Fetch()):
				$dir = str_replace(array("\\", "//"), "/", dirname(__FILE__)); 
				$dirs = explode("/", $dir); 
				array_pop($dirs); 
				$file = trim(implode("/", $dirs)."/lang/".$arLanguage["LID"]."/include/webdav_settings.php");
				$tmp_mess = __IncludeLang($file, true);
				$arFieldName[$arLanguage["LID"]] = (empty($tmp_mess["SONET_UF_USE_BP"]) ? "Use Business Process" : $tmp_mess["SONET_UF_USE_BP"]);
			endwhile;
			$arFields["EDIT_FORM_LABEL"] = $arFieldName;
			$obUserField  = new CUserTypeEntity;
			$obUserField->Add($arFields);
			$GLOBALS["USER_FIELD_MANAGER"]->arFieldsCache = array();
		}
	}
}

if (!function_exists('_getPath'))
{
    function _getPath($path, $sef_folder)
    {
        static $dav;
        if (empty($dav))
            $dav = (CWebDavBase::IsDavHeaders('check_all')?'D':'W');
        if ($dav == 'W')
        {
            $spath = substr($path,  strlen($sef_folder)-1);
            if (empty($spath)) $spath .= '/';
            return $spath;
        } else 
            return $path;
    }
}


if (!function_exists('_uencode'))
{
    function _uencode($t)
    {
        if (SITE_CHARSET != "UTF-8")
        {
            global $APPLICATION;
            $t = $APPLICATION->ConvertCharset($t, SITE_CHARSET, "UTF-8");
        }
        return $t;
    }
}


if (!function_exists('ParseFolderTreeData'))
{
    function ParseFolderTreeData($obTree, $pathPrefix, $addDepth = 0, $addLinks = false)
    {
        if (sizeof($obTree) == 0) return array();
        $folderTree = array();
        $saveFields = array('NAME', 'PATH', 'DEPTH_LEVEL', 'TIMESTAMP_X', 'MODIFIED_BY');
        $obKeys = array_keys($obTree);
        foreach ($obKeys as $obKey)
        {
            $obFields = array_keys($obTree[$obKey]);
            foreach ($obFields as $obField)
            {
                if (array_search($obField, $saveFields) === false)
                    unset($obTree[$obKey][$obField]);
            }
            $obTree[$obKey]['DEPTH_LEVEL'] += $addDepth;
            $obTree[$obKey]['PATH'] = $pathPrefix . $obTree[$obKey]['PATH'];
            $obTree[$obKey]['NAME'] = $pathPrefix . $obTree[$obKey]['NAME'];
            if (!preg_match("'/$'", $obTree[$obKey]['PATH'])) $obTree[$obKey]['PATH'] = $obTree[$obKey]['PATH'].'/';
            $folderTree[] = $obTree[$obKey];
        }
        return $folderTree;
    }
}

if (!function_exists('MakeDavRedirect')) 
{
    function MakeDavRedirect($ob, $currentPageUrl, $baseURL, $path, $is_root = false)
    {
        global $APPLICATION, $USER;
        if ($ob->IsDavHeaders('check_all') || array_search($_SERVER['REQUEST_METHOD'], array('DELETE')) !== false)
        {
            if (!$USER->IsAuthorized())
            {
                $APPLICATION->RestartBuffer();
                CHTTP::SetStatus('401 Unauthorized');
                header('WWW-Authenticate: Basic realm="BitrixWebDav"');
                header('Content-length: 0');
                die();
            }
            if (!$ob->CheckRights($_SERVER['REQUEST_METHOD']))
            {
                $ob->SetStatus('403 Forbidden');
                ShowError(GetMessage("WD_DAV_INSUFFICIENT_RIGHTS")); 
                die();
            }
            elseif (!$ob->IsMethodAllow($_SERVER['REQUEST_METHOD']))
            {
                CHTTP::SetStatus('405 Method not allowed');
                header('Allow: ' . join(',', array_keys($ob->allow)));
                ShowError(GetMessage("WD_DAV_UNSUPORTED_METHOD")); 
                die();
            }
            else  
            {
                $APPLICATION->RestartBuffer();
                if (isset($_SERVER['HTTP_DESTINATION']))
                {
                    $_SERVER['HTTP_DESTINATION'] = urldecode($_SERVER['HTTP_DESTINATION']);
                    $pu = parse_url($_SERVER['HTTP_DESTINATION']);
                    $ob->SetBaseURL($baseURL);
                    if (strpos($pu['path'], $baseURL) === false)
                    {
                        CHTTP::SetStatus('405 Method not allowed');
                        header('Allow: ' . join(',', array_keys($ob->allow)));
                        ShowError(GetMessage("WD_DAV_UNSUPORTED_METHOD")); 
                        die();
                    }
                } else {
                    $ob->SetBaseURL(_uencode($baseURL));
                }
                $ob->SetPath($path);
                $fn = 'base_' . $_SERVER['REQUEST_METHOD'];
                call_user_func(array(&$ob, $fn));
                die();
            }
        } else {
            $ob->SetBaseURL(_uencode($baseURL));
            $ob->SetPath(_uencode(rtrim($path, '/')));
            if ($is_root) return;
            $ob->IsDir();
            if ($ob->arParams['is_file'] )
            {
                $APPLICATION->RestartBuffer();
                $ob->base_GET();
                die();
            } else {
                LocalRedirect($currentPageUrl);
            }
        }
    }
}

$folderTree = array();
$depth = 0;
// OTHER SHARES
// ******************************************************
if ($mode == 'root')
{
    foreach($arParams['IBLOCK_OTHER_IDS'] as $id)
    {
        if (isset($modes[$id]))
        {
            $path = $arParams['SEF_FOLDER'].$modes[$id].'';
            $folderTree[] = array('NAME' => _getName($path), 'PATH' => _getPath($path, $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => $depth, 'MODE' => 'remote');
        }
    }
}

if (intval($mode) > 0)
{
    // for copy/move methods
    if (isset($_SERVER['HTTP_DESTINATION']))
    {
        $arDestPath = explode('/', trim($destPath, '/'));
        if (empty($arDestPath[0])) unset($arDestPath[0]);
        if (sizeof($arDestPath) > 0)
        {
            $destName = $arDestPath[0];
        }
    }

    $obOther = new CWebDavIblock($mode, _uencode($localPath), $arParams);
    if (!empty($obOther->arError))
    {
        ShowError($obOther->arError['text']);
        return false; 
    }
    MakeDavRedirect($obOther, str_replace('#PATH#', '', $arUrlTemplates['i'.$mode]), $rootPath, $localPath);
}

if (CBXFeatures::IsFeatureEnabled("Workgroups"))
{
    // WORKGROUPS 
    // ****************************************************
    if (($mode == 'root' || $mode == 'group') && $USER->IsAuthorized())
    {
        $path = $arParams['SEF_FOLDER'].$modes['group'].'';
        $folderTree[] = array('NAME' => _getName($path), 'PATH' => _getPath($path, $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => $depth, 'MODE' => 'local');
    }

    if ($mode == 'group')
    {
        // get all workgroup sections with files
        $CACHE_ID = SITE_ID . '|' . $requestURL . '|' . $currentUserID . '|' . (CWebDavBase::IsDavHeaders('check_all')?'D':'W') . '|GROUPS';
        $groupCache = new CPHPCache;
        if ($groupCache->InitCache($arParams["CACHE_TIME"], $CACHE_ID, $cachePath))
        {
            $vars = $groupCache->GetVars();
            $arGroupSectionIDs = $vars['GROUP_SECTION_IDS'];
            $currentUserGroups = $vars['CURRENT_USER_GROUPS'];
        } 
        else 
        {
            $arFilter = array(
                "IBLOCK_ID" => $arParams["IBLOCK_GROUP_ID"],
                "SECTION_ID" => 0,
            );
            $arGroupSections = array();
            $arGroupSectionIDs = array();
            $dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'SOCNET_GROUP_ID'));
            while ($arGroupSection = $dbSection->Fetch())
            {
                $arGroupSections[$arGroupSection['ID']] = $arGroupSection;
                $arGroupSectionIDs[$arGroupSection['ID']] = $arGroupSection['SOCNET_GROUP_ID'];
            }
            // get all user workgroups 
            $currentUserGroups=array();
            $userGroupIDs = array();
            $db_res = CSocNetUserToGroup::GetList(
                array("GROUP_NAME" => "ASC"),
                array( "USER_ID" => $currentUserID,),
                false,
                false,
                array("GROUP_ID", "GROUP_NAME", "GROUP_ACTIVE", "ROLE")
            );
            while ($res = $db_res->GetNext())
            {
                $currentUserGroups[$res["GROUP_ID"]] = $res;
                $userGroupIDs[] = $res["GROUP_ID"];
            }
            // intersect result - user groups which has files
            $arGroupSectionIDs = array_intersect($arGroupSectionIDs, $userGroupIDs);
            if ($groupCache->StartDataCache())
                $groupCache->EndDataCache(array('GROUP_SECTION_IDS' => $arGroupSectionIDs, 'CURRENT_USER_GROUPS' => $currentUserGroups));
        } 
        unset($groupCache);

        $arLocalPath = explode('/', trim($localPath, '/'));
        if (empty($arLocalPath[0])) unset($arLocalPath[0]);
        if (sizeof($arLocalPath) > 0)
        {
            $groupName = GetMessage('SONET_GROUP_PREFIX').$arLocalPath[0];
            $arFilter = array(
                "IBLOCK_ID" => $arParams["IBLOCK_GROUP_ID"],
                "NAME" => $groupName,
            );
            $dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID', 'SOCNET_GROUP_ID'));
            if ($arGroupSection = $dbSection->Fetch())
            {
                $sectionID = $arGroupSection['ID'];
                $arVariables['GROUP_ID'] = $arGroupSection['SOCNET_GROUP_ID'];
            } else {
                CHTTP::SetStatus('404 Not Found');
                ShowError(GetMessage("WD_GROUP_SECTION_FILES_NOT_FOUND")); 
                return 0;
            }

            // for copy/move methods
            if (isset($_SERVER['HTTP_DESTINATION']))
            {
                $arDestPath = explode('/', trim($destPath, '/'));
                if (empty($arDestPath[0])) unset($arDestPath[0]);
                if (sizeof($arDestPath) > 0)
                {
                    $destName = $arDestPath[0];

                    $_SERVER['HTTP_DESTINATION'] = str_replace($destName, GetMessage('SONET_GROUP_PREFIX').$destName , $_SERVER['HTTP_DESTINATION']);
                    $arLocalPath[0] = GetMessage('SONET_GROUP_PREFIX').$destName;
                }
            }

            $groupPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'group', $arVariables['GROUP_ID'], $currentUserID, $arParams['IBLOCK_GROUP_ID']);
            foreach (array('PERMISSION', 'CHECK_CREATOR') as $propName)
                $arParams[$propName] = $groupPerms[$propName];
            $object = 'group';
            $arParams["DOCUMENT_TYPE"] = array("webdav", "CIBlockDocumentWebdavSocnet", "iblock_".$arParams['IBLOCK_GROUP_ID']."_group_".intVal($arVariables['GROUP_ID'])); 
            $obGroup = new CWebDavIblock($arParams['IBLOCK_GROUP_ID'], $localPath, $arParams);
            $obGroup->SetRootSection($sectionID); 
            $currentPageUrl = str_replace(array('#GROUP_ID#', '#PATH#'), array($arVariables['GROUP_ID'], ''), $arParams["GROUP_FILE_PATH"]);
            MakeDavRedirect($obGroup, $currentPageUrl, $rootPath.'/'.$arLocalPath[0], '/'.implode('/', array_slice($arLocalPath, 1)) . '/');
        } else {
            // group list
            $groupTree = array();
            $CACHE_ID = SITE_ID . '|' . $requestURL . '|' . $currentUserID . '|' .(CWebDavBase::IsDavHeaders('check_all')?'D':'W') .'|GROUPSECTIONS';
            $groupCache = new CPHPCache;
            if ($groupCache->InitCache($arParams["CACHE_TIME"], $CACHE_ID, $cachePath))
            {
                $vars = $groupCache->GetVars();
                $groupTree = $vars['GROUP_TREE'];
            } 
            else 
            {
                foreach ($arGroupSectionIDs as $sectionID=>$groupID)
                {
                    if ($currentUserGroups[$groupID]["GROUP_ACTIVE"] != 'Y') continue;
                    if (!CSocNetFeatures::IsActiveFeature( SONET_ENTITY_GROUP, $groupID, "files")) continue;
                    $groupPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'group', $groupID, $currentUserID, $arParams['IBLOCK_GROUP_ID']);
                    if ($groupPerms["PERMISSION"] < "R") continue;
                    $path = $currentUserGroups[$groupID]['GROUP_NAME'];
                    $path = $rootPath . '/' . $path;
                    $groupTree[] = array('NAME' => _getName($path), 'PATH' => _getPath($path, $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => 1, 'MODE' => 'remote');
                }
                if ($groupCache->StartDataCache())
                    $groupCache->EndDataCache(array('GROUP_TREE' => $groupTree));
            }
            unset($groupCache);
            usort($groupTree, "_wd_aggregator_sort");
            $folderTree = array_merge($folderTree, $groupTree);
        }
    }
}

// PERSONAL DOCS
// ****************************************************
if ($mode == 'private' || $mode == 'root')
{
    if (CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $currentUserID, "files")) 
    {
        $path = $arParams['SEF_FOLDER'].$modes['private'].'';       
        $folderTree[] = array('NAME' => _getName($path), 'PATH' => _getPath($path, $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => 0, 'MODE' => 'remote');
    }
}
if ($mode == 'private' || $arParams["EXPAND_ALL"] == "Y")
{
    if (CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $currentUserID, "files")) 
    {
        $ownerPerms = CIBlockWebdavSocnet::GetUserMaxPermission( "user", $currentUserID, $currentUserID, $arParams['IBLOCK_USER_ID']);
        if ($ownerPerms >= "R")
        {
            $arLocalPath = explode('/', trim($localPath, '/'));
            if (empty($arLocalPath[0])) unset($arLocalPath[0]);
            $arFilter = array(
                "IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
                "SOCNET_GROUP_ID" => false, 
                "SECTION_ID" => 0,
                "CREATED_BY" => $currentUserID
            );

            $db_res = CIBlockSection::GetList(array(), $arFilter);
            if ($db_res && $res = $db_res->Fetch())
            {
                $sectionID = $res['ID'];
                $obGroup = new CWebDavIblock($arParams['IBLOCK_USER_ID'], $localPath, $arParams);
                $obGroup->SetRootSection($sectionID); 
                $currentPageUrl = str_replace(array('#USER_ID#', '#PATH#'), array($currentUserID, ''), $arParams["USER_FILE_PATH"]);
                foreach (array('PERMISSION', 'CHECK_CREATOR') as $propName)
                    $arParams[$propName] = $ownerPerms[$propName];
                $arParams["DOCUMENT_TYPE"] = array("webdav", "CIBlockDocumentWebdavSocnet", "iblock_".$arParams['IBLOCK_USER_ID']."_user_".intVal($currentUserID)); 
                MakeDavRedirect($obGroup, $currentPageUrl, $rootPath, $localPath);
            } 
            elseif ($ownerPerms < "W")
            {
                CHTTP::SetStatus('404 Not Found');
                ShowError(GetMessage("WD_USER_SECTION_FILES_NOT_FOUND"));
                return 0;
            }
            else
            {
                __wd_check_uf_use_bp_property($arParams["IBLOCK_USER_ID"]); 

                $arFields = Array(
                    "IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
                    "ACTIVE" => "Y",
                    "SOCNET_GROUP_ID" => false, 
                    "IBLOCK_SECTION_ID" => 0, 
                    "UF_USE_BP" => "N");

                $arFields["NAME"] = trim($USER->GetLastName()." ".$USER->GetFirstName());
                $arFields["NAME"] = trim(!empty($arFields["NAME"]) ? $arFields["NAME"] : $USER->GetLogin());
                $GLOBALS["UF_USE_BP"] = $arFields["UF_USE_BP"];
                $GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$arParams["IBLOCK_ID"]."_SECTION", $arFields);
                $bs = new CIBlockSection;
                if (!$bs->Add($arFields))
                {
                    CHTTP::SetStatus('404 Not Found');
                    $arParams["ERROR_MESSAGE"] = $bs->LAST_ERROR;
                    return 0;
                }
                WDClearComponentCache(array(
                    "webdav.element.edit", 
                    "webdav.element.hist", 
                    "webdav.element.upload", 
                    "webdav.element.view", 
                    "webdav.menu",
                    "webdav.section.edit", 
                    "webdav.section.list"));
                BXClearCache(true, $ob->CACHE_PATH);
                BXClearCache(true, $cachePath);
                $APPLICATION->RestartBuffer();
                LocalRedirect($_SERVER['REQUEST_URI']);
                die();
            }
        }
    }
}

// SCAN USERS
// ****************************************************
if ($mode == 'root' || $mode == 'user')
{
    $arFilter = array(
        "IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
        "SOCNET_GROUP_ID" => false, 
        "SECTION_ID" => 0,
    );
    if (CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $currentUserID, "files") && CIBlockSection::GetCount($arFilter) > 0) 
    {
        $path = $arParams['SEF_FOLDER'].$modes['user'].'';       
        $folderTree[] = array('NAME' => _getName($path), 'PATH' => _getPath($path, $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => 0, 'MODE' => 'local');
    }
}
if ($mode == 'user')
{
    $arLocalPath = explode('/', trim($localPath, '/'));
    if (empty($arLocalPath[0])) unset($arLocalPath[0]);
    if (sizeof($arLocalPath) > 0)
    {
        $userName = $arLocalPath[0];
        $userFilter = array();
        if (strpos($userName, '(') !== false)
        {
            $userFilter = array('LOGIN_EQUAL' => trim($userName, '()')); 
        } else {
            $userFilter = array('NAME' => $userName);
        }

        $dbUser = CUser::GetList($by, $order, $userFilter);
        if (($dbUser !== false) && $arUser = $dbUser->Fetch())
        {
            $userID = $arUser['ID'];
            $userLogin = $arUser['LOGIN'];
        } else {
            CHTTP::SetStatus('404 Not Found');
            ShowError(GetMessage("WD_USER_NOT_FOUND")); 
            return 0;
        }

        $arFilter = array(
            "IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
            "SOCNET_GROUP_ID" => false, 
            "SECTION_ID" => 0,
            "CREATED_BY" => $userID,
        );

        $dbSection = CIBlockSection::GetList(array(), $arFilter, false, array('ID'));
        if ($arUserSection = $dbSection->Fetch())
        {
            $sectionID = $arUserSection['ID'];
        } else {
            CHTTP::SetStatus('404 Not Found');
            ShowError(GetMessage("WD_USER_SECTION_FILES_NOT_FOUND")); 
            return 0;
        }
 
        // for copy/move methods
        if (isset($_SERVER['HTTP_DESTINATION']))
        {
            $arDestPath = explode('/', trim($destPath, '/'));
            if (empty($arDestPath[0])) unset($arDestPath[0]);
            if (sizeof($arDestPath) > 0)
            {
                $destName = $arDestPath[0];

                $destFilter = array();
                if (strpos($destName, '(') !== false)
                {
                    $destFilter = array('LOGIN_EQUAL' => trim($destName, '()')); 
                } else {
                    $destFilter = array('NAME' => $destName);
                }

                $dbUser = CUser::GetList($by, $order, $destFilter);
                if (($dbUser !== false) && $arUser = $dbUser->Fetch())
                {
                    $destID = $arUser['ID'];
                    $destLogin = $arUser['LOGIN'];
                } else {
                    ShowError(GetMessage("WD_USER_NOT_FOUND")); 
                    return 0;
                }
                $_SERVER['HTTP_DESTINATION'] = str_replace($destName, $destLogin, $_SERVER['HTTP_DESTINATION']);
                $arLocalPath[0] = $destLogin;
            }
        }

        $userPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'user', $userID, $currentUserID, $arParams['IBLOCK_USER_ID']); 
        foreach (array('PERMISSION', 'CHECK_CREATOR') as $propName)
            $arParams[$propName] = $userPerms[$propName];
        $arParams["DOCUMENT_TYPE"] = array("webdav", "CIBlockDocumentWebdavSocnet", "iblock_".$arParams['IBLOCK_USER_ID']."_user_".intVal($userID)); 
        $obGroup = new CWebDavIblock($arParams['IBLOCK_USER_ID'],  $localPath, $arParams);
        $obGroup->SetRootSection($sectionID); 
        $currentPageUrl = str_replace(array('#USER_ID#', '#PATH#'), array($userID, ''), $arParams["USER_FILE_PATH"]);
        MakeDavRedirect($obGroup, $currentPageUrl, $rootPath.'/'.$arLocalPath[0], '/'. implode('/', array_slice($arLocalPath, 1)) . '/');
    } else {
        // user list
        $userTree = array();
        $CACHE_ID = SITE_ID . '|' . $requestURL . '|' . $currentUserID . '|' . (CWebDavBase::IsDavHeaders('check_all')?'D':'W') .'|USERLIST';
        $userCache = new CPHPCache;
        if ($userCache->InitCache($arParams["CACHE_TIME"], $CACHE_ID, $cachePath))
        {
            $vars = $userCache->GetVars();
            $userTree = $vars['USER_TREE'];
        } 
        else 
        {
            $arFilter = array(
                "IBLOCK_ID" => $arParams["IBLOCK_USER_ID"],
                "SOCNET_GROUP_ID" => false, 
                "SECTION_ID" => 0,
            );
            $dbSection = CIBlockSection::GetList(array(), $arFilter);
            while ($arSection = $dbSection->Fetch())
            {
                $userID = $arSection['CREATED_BY'];
                if (!CSocNetFeatures::IsActiveFeature( SONET_ENTITY_USER, $userID, "files")) continue;
                $userPerms = CIBlockWebdavSocnet::GetUserMaxPermission( 'user', $userID, $currentUserID, $arParams['IBLOCK_USER_ID']);
                if ($userPerms["PERMISSION"] < "R")
                    continue;
                $dbUser = CUser::GetByID($userID);
                if ($arUser = $dbUser->Fetch())
                {
                    if ($arUser['ACTIVE'] != 'Y') continue;
                }
                if (_getIBlockItemsCount($arParams["IBLOCK_USER_ID"], $arSection["ID"]) <= 0) continue;

                $tpl = preg_replace(array("/#NOBR#/","/#\/NOBR#/"), array("",""), $arParams['NAME_TEMPLATE']);
                $name = CUser::FormatName($tpl, $arUser, false, false);

                if ($name == ' ' || $name !== htmlspecialchars($name) || (empty($arUser['NAME']) && empty($arUser['SECOND_NAME'])) )
                    $name = '('.$arUser['LOGIN'].')';
                $path = $rootPath . '/' . $name;
                $userTree[] = array('NAME' => _getName($path), 'PATH' => _getPath($path, $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => 1, 'MODE'=>'remote');
            } 
            if ($userCache->StartDataCache())
                $userCache->EndDataCache(array('USER_TREE' => $userTree));
        }
        unset($userCache);
        usort($userTree, "_wd_aggregator_sort");
        $folderTree = array_merge($folderTree, $userTree);
    }
}


$folderTree = array_merge(
    array(array('NAME'=>GetMessage('WD_ROOT'), 'PATH' => _getPath($arParams['SEF_FOLDER'], $arParams['SEF_FOLDER']), 'DEPTH_LEVEL' => -1)),
    $folderTree);
?>

<?
$ob = new CWebDavVirtual($folderTree, '/', $arParams);

MakeDavRedirect($ob, $currentPageUrl, $baseURL, $rootPath.'', true);

$arResult['OBJECT'] = $ob;
$arResult['STRUCTURE'] = $folderTree;
$this->IncludeComponentTemplate();
?>
