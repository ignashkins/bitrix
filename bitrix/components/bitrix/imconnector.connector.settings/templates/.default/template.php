<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Main\UI;

use \Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Connector;

use \Bitrix\ImOpenLines,
	\Bitrix\Imopenlines\Limit,
	\Bitrix\ImOpenlines\Security;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
/** $arResult['CONNECTION_STATUS']; */
/** $arResult['REGISTER_STATUS']; */
/** $arResult['ERROR_STATUS']; */
/** $arResult['SAVE_STATUS']; */

Loc::loadMessages(__FILE__);

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/imconnector.settings/templates/.default/template.php');

if (Loader::includeModule('bitrix24'))
{
	$APPLICATION->IncludeComponent('bitrix:ui.info.helper', '', []);
	CBitrix24::initLicenseInfoPopupJS();
}

$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
UI\Extension::load([
		'ui.buttons',
		'ui.hint',

		'ui.entity-selector',
		'ui.forms'
]
);
\CJSCore::Init('loader');
Connector::initIconCss();
?>
<?
if (empty($arResult['RELOAD']) && empty($arResult['URL_RELOAD']))
{
	if (!empty($arResult['ACTIVE_LINE']))
	{
		?>
		<?
		$APPLICATION->IncludeComponent(
			$arResult['COMPONENT'],
			'',
			[
				'LINE' => $arResult['ACTIVE_LINE']['ID'],
				'CONNECTOR' => $arResult['ID'],
				'AJAX_MODE' =>  'N',
				'AJAX_OPTION_ADDITIONAL' => '',
				'AJAX_OPTION_HISTORY' => 'N',
				'AJAX_OPTION_JUMP' => 'Y',
				'AJAX_OPTION_STYLE' => 'Y',
				'INDIVIDUAL_USE' => 'Y'
			]
		); ?>
		<?= $arResult['LANG_JS_SETTING']; ?>
		<?
		$status = Status::getInstance($arResult['ID'], $arResult['ACTIVE_LINE']['ID'])->isStatus();
		Status::cleanCache($arResult['ID'], $arResult['ACTIVE_LINE']['ID']);
		if ($status || count($arResult['LIST_LINE']) > 1)
		{
			?>
			<div class="imconnector-field-container" id="bx-connector-user-list">
				<div class="imconnector-field-section">
					<div class="imconnector-field-main-title">
						<?= Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CONFIGURE_CHANNEL') ?>
					</div>

					<?
					if ($arResult['SHOW_LIST_LINES'])
					{
						?>
						<div class="imconnector-field-box">
							<div class="imconnector-field-box-subtitle">
								<?= Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_OPEN_LINE') ?>
							</div>
							<div class="imconnector-field-control-box">
								<?
								if (count($arResult['LIST_LINE']) > 0)
								{
									if (!empty($arResult['PATH_TO_ADD_LINE']))
									{
										$arResult['LIST_LINE'][] = [
											'ID' => 0,
											'URL' => CUtil::JSEscape($arResult['PATH_TO_CONNECTOR_LINE_ADAPTED']),
											'NAME' => Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CREATE_OPEN_LINE'),
											'NEW' => 'Y',
											'DELIMITER_BEFORE' => true
										];
									}
									$limitInfoHelper ='';
									if(Loader::includeModule('imopenlines'))
									{
										$limitInfoHelper = Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_OL_NUMBER;
									}
									?>
									<script>
										BX.ready(function () {
											BX.message({
												IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_LIMIT_INFO_HELPER: '<?= $limitInfoHelper ?>',
												IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_TITLE: '<?=GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_TITLE')?>',
												IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_DESCRIPTION: '<?=GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_DESCRIPTION')?>',
												IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_BUTTON_ACTIVE: '<?=GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_BUTTON_ACTIVE')?>',
												IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_BUTTON_NO: '<?=GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_BUTTON_NO')?>',
											});
											BX.ImConnectorLinesConfigEdit.initConfigMenu({
												element: 'imconnector-lines-list',
												bindElement: 'imconnector-lines-list',
												items: <?=CUtil::PhpToJSObject($arResult['LIST_LINE'])?>,
												iframe: <?=CUtil::PhpToJSObject($arParams['IFRAME'])?>
											});
										});
									</script>
									<div class="imconnector-field-control-input imconnector-field-control-select"
										 id="imconnector-lines-list"><?=htmlspecialcharsbx($arResult['ACTIVE_LINE']['NAME'])?></div>
									<?
									if (!empty($arResult['ACTIVE_LINE']['URL_EDIT']))
									{
										?>
										<button onclick="BX.SidePanel.Instance.open(
												'<?= CUtil::JSEscape($arResult['ACTIVE_LINE']['URL_EDIT']) ?>',
												{width: 996, loader: '/bitrix/components/bitrix/imopenlines.lines.edit/templates/.default/images/imopenlines-view.svg', allowChangeHistory: false, cacheable: false})"
												class="ui-btn ui-btn-link imopenlines-settings-button">
											<?= Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CONFIGURE') ?>
										</button>
										<?
									}
								}
								else
								{
									?>
									<button onclick="BX.ImConnectorLinesConfigEdit.createLineAction('<?= CUtil::JSEscape($arResult['PATH_TO_CONNECTOR_LINE_ADAPTED']) ?>', <?=CUtil::PhpToJSObject($arParams['IFRAME'])?>)"
											class="ui-btn ui-btn-link imopenlines-settings-button">
										<?= Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CREATE_OPEN_LINE') ?>
									</button>
									<?
								}
								?>
							</div>
						</div>
						<?
					}
					?>
					<div class="tel-set-destination-container" id="users_for_queue"></div>
					<script type="text/javascript">
						BX.ready(function () {
							BX.message({
								LM_ADD: '<?=GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_LM_ADD')?>',
								'LM_QUEUE_DESCRIPTION': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_QUEUE_DESCRIPTION')?>',
								'LM_QUEUE_TITLE': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_QUEUE')?>',
								'LM_HEAD_DEPARTMENT_EXCLUDED_QUEUE': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_HEAD_DEPARTMENT_EXCLUDED_QUEUE')?>'
							});

							BX.ImConnectorLinesConfigEdit.initDestination(
								BX('users_for_queue'),
								<?=CUtil::PhpToJSObject($arResult['QUEUE'])?>
							);
						});
					</script>

					<?
					if (Security\Helper::isSettingsMenuEnabled())
					{
						?>
						<div class="imconnector-field-box imconnector-field-user-box box-rights">
							<div class="imconnector-field-box-subtitle box-rights">
								<?= Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_PERMISSIONS') ?>
							</div>
							<a href="javascript:void(0)"
							   onclick="BX.SidePanel.Instance.open('<?=ImOpenLines\Common::getPublicFolder() . 'permissions.php'?>', {allowChangeHistory: false, cacheable: false})"
							   class="bx-destination-add imconnector-field-link-grey">
								<?= Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CONFIGURE') ?>
							</a>
						</div>
						<?
					}
					?>
				</div>
			</div>
			<?
			if (count($arResult['LIST_LINE']) > 0)
			{
				?>
				<script>
					BX.message({
						IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_ERROR_ACTION: '<?= GetMessageJS('IIMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_ERROR_ACTION') ?>',
						IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CLOSE: '<?= GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CLOSE') ?>',
						IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_QUEUE: '<?= GetMessageJS('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_QUEUE')?>'
					});
				</script>
				<?
			}

		}
	}
	elseif (empty($arResult['ACTIVE_LINE']) && !empty($arResult['PATH_TO_ADD_LINE']))
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section imconnector-field-section-social">
				<div class="imconnector-field-box">
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_NO_OPEN_LINE'); ?>
					<a class="imconnector-field-box-link" onclick="BX.ImConnectorLinesConfigEdit.createLineAction('<?= CUtil::JSEscape($arResult['PATH_TO_CONNECTOR_LINE_ADAPTED']) ?>', <?=CUtil::PhpToJSObject($arParams['IFRAME'])?>)" target="_top">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CREATE_OPEN_LINE')?>
					</a>
				</div>
			</div>
		</div>
		<?
	}
	else
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section imconnector-field-section-social">
				<div class="imconnector-field-box">
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_NO_OPEN_LINE_AND_NOT_ADD_OPEN_LINE'); ?>
				</div>
			</div>
		</div>
		<?
	}
}
elseif (!empty($arResult['URL_RELOAD']))
{
	?>
	<html>
	<body>
	<script>
		window.reloadAjaxImconnector = function (urlReload)
		{
			if(
				parent &&
				parent.window &&
				parent.window.opener
			)
			{
				if(parent.window.opener.location)
				{
					parent.window.opener.location.href = urlReload; //parent.window.opener construction is used for both frame and page mode as universal
				}
				parent.window.opener.addPreloader();
			}
			window.close();
		};
		reloadAjaxImconnector(<?=CUtil::PhpToJSObject($arResult['URL_RELOAD'])?>);
	</script>
	</body>
	</html>
	<?
}
else
{
	?>
	<html>
	<body>
	<script>
		window.reloadAjaxImconnector = function (urlReload, idReload)
		{
			if(
				parent &&
				parent.window &&
				parent.window.opener.BX &&
				parent.window.opener.BX.ajax
			)
			{
				parent.window.opener.BX.ajax.insertToNode(urlReload, idReload);
			}
			window.close();
		};
		reloadAjaxImconnector(<?=CUtil::PhpToJSObject($arResult['URL_RELOAD'])?>, <?=CUtil::PhpToJSObject('comp_' . $arResult['RELOAD'])?>);
	</script>
	</body>
	</html>
	<?
}
?>
