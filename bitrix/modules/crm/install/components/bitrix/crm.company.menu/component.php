<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 * @var \CUserTypeManager $USER_FIELD_MANAGER
 * @var \CBitrixComponent $this
 * @var array $arParams
 * @var array $arResult
 */

use Bitrix\Main\Localization\Loc;

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_DETAILS'] = CrmCheckPath('PATH_TO_COMPANY_DETAILS', $arParams['PATH_TO_COMPANY_DETAILS'], $APPLICATION->GetCurPage().'?company_id=#company_id#&details');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_COMPANY_IMPORT'] = CrmCheckPath('PATH_TO_COMPANY_IMPORT', $arParams['PATH_TO_COMPANY_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arParams['PATH_TO_COMPANY_PORTRAIT'] = CrmCheckPath('PATH_TO_COMPANY_PORTRAIT', $arParams['PATH_TO_COMPANY_PORTRAIT'], $APPLICATION->GetCurPage().'?portrait');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_MIGRATION'] = SITE_DIR."marketplace/category/migration/";
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

$arResult['MYCOMPANY_MODE'] = (isset($arParams['MYCOMPANY_MODE']) && $arParams['MYCOMPANY_MODE'] === 'Y') ? 'Y' : 'N';
$isMyCompanyMode = ($arResult['MYCOMPANY_MODE'] === 'Y');

if(!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_company_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = CCrmCompany::CheckReadPermission(0, $CrmPerms);
	$bExport = CCrmCompany::CheckExportPermission($CrmPerms);
	$bImport = CCrmCompany::CheckImportPermission($CrmPerms);
	$bAdd    = CCrmCompany::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmCompany::CheckUpdatePermission(0, $CrmPerms);
	$bDelete = false;

	$bDedupe = $bRead && $bWrite && CCrmCompany::CheckDeletePermission(0, $CrmPerms);
}
else
{
	$bExport = false;
	$bImport = false;
	$bDedupe = false;

	$bRead   = CCrmCompany::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = CCrmCompany::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmCompany::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = CCrmCompany::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

$isSliderEnabled = \CCrmOwnerType::IsSliderEnabled(\CCrmOwnerType::Company);

//Skip COPY menu in slider mode
if($arParams['TYPE'] == 'copy' && $isSliderEnabled)
{
	return false;
}

if($arParams['TYPE'] === 'details')
{
	if($arParams['ELEMENT_ID'] <= 0)
	{
		return false;
	}

	$scripts = isset($arParams['~SCRIPTS']) && is_array($arParams['~SCRIPTS']) ? $arParams['~SCRIPTS'] : array();

	//region APPLICATION PLACEMENT
	$placementGroupInfos = \Bitrix\Crm\Integration\Rest\AppPlacementManager::getHandlerInfos(
		\Bitrix\Crm\Integration\Rest\AppPlacement::COMPANY_DETAIL_TOOLBAR
	);
	foreach($placementGroupInfos as $placementGroupName => $placementInfos)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'rest-app-toolbar',
			'NAME' => $placementGroupName,
			'DATA' => array(
				'OWNER_INFO' => isset($arParams['OWNER_INFO']) ? $arParams['OWNER_INFO'] : array(),
				'PLACEMENT' => \Bitrix\Crm\Integration\Rest\AppPlacement::COMPANY_DETAIL_TOOLBAR,
				'APP_INFOS' => $placementInfos
			)
		);
	}
	//endregion

	if (!empty($arParams['BIZPROC_STARTER_DATA']))
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'bizproc-starter-button',
			'DATA' => $arParams['BIZPROC_STARTER_DATA']
		);
	}

	//Force start new bar after first button
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

	if($bWrite && !$isMyCompanyMode)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'crm-communication-panel',
			'DATA' => array(
				'ENABLE_CALL' => \Bitrix\Main\ModuleManager::isModuleInstalled('calendar'),
				'OWNER_INFO' => isset($arParams['OWNER_INFO']) ? $arParams['OWNER_INFO'] : array(),
				'MULTIFIELDS' => isset($arParams['MULTIFIELD_DATA']) ? $arParams['MULTIFIELD_DATA'] : array()
			)
		);
	}

	if($bAdd)
	{
		$copyUrl = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_COMPANY_DETAILS'],
				array('company_id' => $arParams['ELEMENT_ID'])
			),
			array('copy' => 1)
		);
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_COPY'),
			'TITLE' => GetMessage('COMPANY_COPY_TITLE'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($copyUrl)."')",
			'ICON' => 'btn-copy'
		);
	}

	if($bDelete && isset($scripts['DELETE']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_DELETE'),
			'TITLE' => GetMessage('COMPANY_DELETE_TITLE'),
			'ONCLICK' => $scripts['DELETE'],
			'ICON' => 'btn-delete'
		);
	}

	if(\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable())
	{
		$arResult['BUTTONS'][] = [
			'TEXT' => GetMessage('DOCUMENT_BUTTON_TEXT'),
			'TITLE' => GetMessage('DOCUMENT_BUTTON_TITLE'),
			'TYPE' => 'crm-document-button',
			'PARAMS' => \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->getDocumentButtonParameters(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Company::class, $arParams['ELEMENT_ID']),
		];
	}

	$this->IncludeComponentTemplate();
	return;
}

if($arParams['TYPE'] === 'list')
{
	if($bAdd)
	{
		$createUrl = CComponentEngine::MakePathFromTemplate(
			$arParams[$isSliderEnabled ? 'PATH_TO_COMPANY_DETAILS' : 'PATH_TO_COMPANY_EDIT'],
			array('company_id' => 0)
		);

		if ($isMyCompanyMode)
		{
			$createUrl = CHTTP::urlAddParams($createUrl, array('mycompany' => 'y'));
		}

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_ADD'),
			'TITLE' => GetMessage('COMPANY_ADD_TITLE'),
			'LINK' => $createUrl,
			'HIGHLIGHT' => true
		);
		unset($link);
	}

	if (!$isMyCompanyMode && $bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_IMPORT'),
			'TITLE' => GetMessage('COMPANY_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_IMPORT'], array()),
			'ICON' => 'btn-import'
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_MIGRATION'),
			'TITLE' => GetMessage('COMPANY_MIGRATION_TITLE'),
			'LINK' => $arParams['PATH_TO_MIGRATION'],
			'ICON' => 'btn-migration'
		);
	}

	if ($bExport)
	{
		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		$entityType = \CCrmOwnerType::CompanyName;
		$stExportId = 'EXPORT_'.$entityType;
		$componentName = 'bitrix:crm.company.list';

		$componentParams = array(
			'COMPANY_COUNT' => '20',
			'PATH_TO_COMPANY_LIST' => $arParams['PATH_TO_COMPANY_LIST'],
			'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
			'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
			'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
			'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
			'MYCOMPANY_MODE' => ($isMyCompanyMode ? 'Y' : 'N'),
			'NAVIGATION_CONTEXT_ID' => $entityType
		);
		if (isset($_REQUEST['WG']) && mb_strtoupper($_REQUEST['WG']) === 'Y')
		{
			$widgetDataFilter = \Bitrix\Crm\Widget\Data\Company\DataSource::extractDetailsPageUrlParams($_REQUEST);
			if (!empty($widgetDataFilter))
			{
				$componentParams['WIDGET_DATA_FILTER'] = $widgetDataFilter;
			}
		}

		$arResult['EXPORT_CSV_PARAMS'] = [
			'id' => $stExportId. '_CSV',
			'controller' => 'bitrix:crm.api.export',
			'queue' => [
				[
					'action' => 'dispatcher',
				],
			],
			'params' => [
				'SITE_ID' => SITE_ID,
				'ENTITY_TYPE' => $entityType,
				'EXPORT_TYPE' => 'csv',
				'COMPONENT_NAME' => $componentName,
				'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters(
					$componentName,
					$componentParams
				),
			],
			'optionsFields' => array(
				'REQUISITE_MULTILINE' => array(
					'name' => 'REQUISITE_MULTILINE',
					'type' => 'checkbox',
					'title' => Loc::getMessage('COMPANY_STEXPORT_OPTION_REQUISITE_MULTILINE'),
					'value' => 'N'
				),
				'EXPORT_ALL_FIELDS' => array(
					'name' => 'EXPORT_ALL_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('COMPANY_STEXPORT_OPTION_EXPORT_ALL_FIELDS'),
					'value' => 'N'
				),
			),
			'messages' => array(
				'DialogTitle' => Loc::getMessage('COMPANY_EXPORT_CSV_TITLE'),
				'DialogSummary' => Loc::getMessage('COMPANY_STEXPORT_SUMMARY'),
			),
			'dialogMaxWidth' => 650,
		];

		// clone params for excel export
		$arResult['EXPORT_EXCEL_PARAMS'] = $arResult['EXPORT_CSV_PARAMS'];
		$arResult['EXPORT_EXCEL_PARAMS']['id'] = $stExportId. '_EXCEL';
		$arResult['EXPORT_EXCEL_PARAMS']['params']['EXPORT_TYPE'] = 'excel';
		$arResult['EXPORT_EXCEL_PARAMS']['messages']['DialogTitle'] = Loc::getMessage('COMPANY_EXPORT_EXCEL_TITLE');



		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('COMPANY_EXPORT_CSV_TITLE'),
			'TEXT' => Loc::getMessage('COMPANY_EXPORT_CSV'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_CSV').showDialog()",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('COMPANY_EXPORT_EXCEL_TITLE'),
			'TEXT' => Loc::getMessage('COMPANY_EXPORT_EXCEL'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_EXCEL').showDialog()",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);


		unset($entityType, $stExportId, $randomSequence, $stExportManagerId);
	}

	if (!$isMyCompanyMode && $bDedupe)
	{
		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getDuplicateControlRestriction();
		if($restriction->hasPermission())
		{
			$dedupePath = CComponentEngine::MakePathFromTemplate(
				\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isDedupeWizardEnabled()
					? $arParams['PATH_TO_COMPANY_DEDUPEWIZARD']
					: $arParams['PATH_TO_COMPANY_DEDUPE']
			);

			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('COMPANY_DEDUPE'),
				'TITLE' => GetMessage('COMPANY_DEDUPE_TITLE'),
				'ONCLICK' => 'BX.Crm.Page.openSlider("'.$dedupePath.'", {cacheable: false})'
			);
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('COMPANY_DEDUPE_AUTOSEARCH'),
				'TITLE' => GetMessage('COMPANY_DEDUPE_AUTOSEARCH'),
				'ONCLICK' => 'BX.Crm.DedupeAutosearch.getDefault("COMPANY").showSettings();BX.PopupMenu.getCurrentMenu().close();'
			);
			$arResult['BUTTONS'][] = array(
				'HTML' => GetMessage('COMPANY_DEDUPE_HELP').' <span class="ui-hint"><span class="ui-hint-icon"></span></span>',
				'TITLE' => GetMessage('COMPANY_DEDUPE_HELP'),
				'ONCLICK' => 'BX.Helper.show("redirect=detail&code=10649014")'
			);
		}
		else
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('COMPANY_DEDUPE'),
				'TITLE' => GetMessage('COMPANY_DEDUPE_TITLE'),
				'ONCLICK' => $restriction->prepareInfoHelperScript(),
				'MENU_ICON' => 'grid-lock'
			);
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('COMPANY_DEDUPE_AUTOSEARCH'),
				'TITLE' => GetMessage('COMPANY_DEDUPE_AUTOSEARCH'),
				'ONCLICK' => $restriction->prepareInfoHelperScript(),
				'MENU_ICON' => 'grid-lock'
			);
			$arResult['BUTTONS'][] = array(
				'HTML' => GetMessage('COMPANY_DEDUPE_HELP').' <span class="ui-hint"><span class="ui-hint-icon"></span></span>',
				'TITLE' => GetMessage('COMPANY_DEDUPE_HELP'),
				'ONCLICK' => 'BX.Helper.show("redirect=detail&code=10649014")'
			);
		}
		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		if(
			\Bitrix\Main\Loader::includeModule('rest')
			&& is_callable('\Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl')
			&& ($bAdd || $bWrite)
		)
		{
			$url = \Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl('crm_company', 'setting_list');
			$arResult['BUTTONS'][] = [
				'TEXT' => GetMessage('COMPANY_VERTICAL_CRM'),
				'TITLE' => GetMessage('COMPANY_VERTICAL_CRM_TITLE'),
				'ONCLICK' => 'BX.SidePanel.Instance.open(\''.$url.'\');'
			];
		}
	}

	if(count($arResult['BUTTONS']) > 1)
	{
		//Force start new bar after first button
		array_splice($arResult['BUTTONS'], 1, 0, array(array('NEWBAR' => true)));
	}

	$this->IncludeComponentTemplate();
	return;
}

if (!$isMyCompanyMode
	&& ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'portrait')
	&& !empty($arParams['ELEMENT_ID'])
	&& $bWrite
)
{
	$plannerButton = \Bitrix\Crm\Activity\Planner::getToolbarButton($arParams['ELEMENT_ID'], CCrmOwnerType::Company);
	if($plannerButton)
	{
		CJSCore::Init(array('crm_activity_planner'));
		$arResult['BUTTONS'][] = $plannerButton;
	}
}

if (!$isMyCompanyMode && ($arParams['TYPE'] == 'show') && $bRead && $arParams['ELEMENT_ID'] > 0)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_COMPANY_PORTRAIT'),
		'TITLE' => GetMessage('CRM_COMPANY_PORTRAIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_PORTRAIT'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-portrait',
	);

	$subscrTypes = CCrmSonetSubscription::GetRegistationTypes(
		CCrmOwnerType::Company,
		$arParams['ELEMENT_ID'],
		$currentUserID
	);

	$isResponsible = in_array(CCrmSonetSubscriptionType::Responsibility, $subscrTypes, true);
	if(!$isResponsible)
	{
		$subscriptionID = 'company_sl_subscribe';
		$arResult['SONET_SUBSCRIBE'] = array(
			'ID' => $subscriptionID,
			'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#bitrix/components/bitrix/crm.company.edit/ajax.php?site_id=#SITE#&sessid=#SID#',
				array('SID' => bitrix_sessid())
			),
			'ACTION_NAME' => 'ENABLE_SONET_SUBSCRIPTION',
			'RELOAD' => true
		);

		$isObserver = in_array(CCrmSonetSubscriptionType::Observation, $subscrTypes, true);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_unsubscribe',
			'TEXT' => GetMessage('CRM_COMPANY_SL_UNSUBSCRIBE'),
			'TITLE' => GetMessage('CRM_COMPANY_SL_UNSUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].unsubscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_unsubscribe', false); tb.setButtonVisible('sl_subscribe', true); })",
			'ICON' => 'btn-nofollow',
			'VISIBLE' => $isObserver
		);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_subscribe',
			'TEXT' => GetMessage('CRM_COMPANY_SL_SUBSCRIBE'),
			'TITLE' => GetMessage('CRM_COMPANY_SL_SUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].subscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_subscribe', false); tb.setButtonVisible('sl_unsubscribe', true); })",
			'ICON' => 'btn-follow',
			'VISIBLE' => !$isObserver
		);
	}
}

if ($arParams['TYPE'] == 'show' && $bWrite && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_EDIT'),
		'TITLE' => GetMessage('COMPANY_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-edit'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'portrait') && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_SHOW'),
		'TITLE' => GetMessage('COMPANY_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_COPY'),
		'TITLE' => GetMessage('COMPANY_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && $arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
else if ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
else if ($qty >= 3)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if ($bAdd && $arParams['TYPE'] != 'list' && $arParams['TYPE'] !== 'portrait')
{
	$createUrl = CComponentEngine::MakePathFromTemplate(
		$arParams[$isSliderEnabled ? 'PATH_TO_COMPANY_DETAILS' : 'PATH_TO_COMPANY_EDIT'],
		array('company_id' => 0)
	);

	if ($isMyCompanyMode)
	{
		$createUrl = CHTTP::urlAddParams($createUrl, array('mycompany' => 'y'));
	}
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_ADD'),
		'TITLE' => GetMessage('COMPANY_ADD_TITLE'),
		'LINK' => $createUrl,
		'TARGET' => '_blank',
		'ICON' => 'btn-new'
	);
}

if ($arParams['TYPE'] == 'show')
{
	if (!$isMyCompanyMode && CCrmDeal::CheckCreatePermission($CrmPerms))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_ADD_DEAL'),
			'TITLE' => GetMessage('COMPANY_ADD_DEAL_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'], array('deal_id' => 0)),
				array('company_id' => $arParams['ELEMENT_ID'])
			),
			'ICONCLASS' => 'btn-add-deal'
		);
	}

	if (!$isMyCompanyMode && !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD'))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_ADD_CONTACT'),
			'TITLE' => GetMessage('COMPANY_ADD_CONTACT_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'], array('contact_id' => 0)),
				array(
					'company_id' => $arParams['ELEMENT_ID'],
					'backurl' => urlencode($APPLICATION->GetCurPage())
				)
			),
			'ICONCLASS' => 'btn-add-contact'
		);
	}
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_DELETE'),
		'TITLE' => GetMessage('COMPANY_DELETE_TITLE'),
		'LINK' => "javascript:company_delete('".GetMessage('COMPANY_DELETE_DLG_TITLE')."', '".GetMessage('COMPANY_DELETE_DLG_MESSAGE')."', '".GetMessage('COMPANY_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();

?>
