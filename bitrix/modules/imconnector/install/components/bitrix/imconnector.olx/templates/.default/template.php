<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}
use \Bitrix\Main\UI\Extension,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\ImConnector\Connector;

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
/** $arResult["CONNECTION_STATUS"]; */
/** $arResult["REGISTER_STATUS"]; */
/** $arResult["ERROR_STATUS"]; */
/** $arResult["SAVE_STATUS"]; */

Loc::loadMessages(__FILE__);

if ($arParams['INDIVIDUAL_USE'] !== 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	Extension::load('ui.buttons');
	Extension::load('ui.hint');
	Connector::initIconCss();
}

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);
?>

<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
	<?=bitrix_sessid_post()?>
</form>
<?php
if (empty($arResult['PAGE'])) //case when not first open
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<?php
				if ($arResult['STATUS']) //case when connection completed
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_CHANGE_ANY_TIME')?>
					</div>
					<div class="ui-btn-container">
						<button class="ui-btn ui-btn-light-border"
								onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
						</button>
					</div>
					<?php
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=$arResult['NAME']?>
					</div>
					<?php
					if ($arResult['ACTIVE_STATUS']) //case when connection in process
					{
						?>
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SETTING_IS_NOT_COMPLETED')?>
						</div>
						<div class="ui-btn-container">
							<a href="<?=$arResult['URL']['SIMPLE_FORM']?>" class="ui-btn ui-btn-primary show-preloader-button">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CONTINUE_WITH_THE_SETUP')?>
							</a>
							<button class="ui-btn ui-btn-light-border"
									onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
							</button>
						</div>
						<?php
					}
					else
					{
						?>
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_CONNECT_STEP', [
								'#ID#' => 'redirect=detail&code=11405312',
							])?>
						</div>
						<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" class="ui-btn-container">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
							<?=bitrix_sessid_post()?>
							<button class="ui-btn ui-btn-light-border"
									type="submit"
									name="<?=$arResult['CONNECTOR']?>_active"
									value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
							</button>
						</form>
						<?php
					}
					?>
					<?php
				}
				?>

			</div>
		</div>
	</div>
	<?php
	include 'messages.php';

	if ($arResult['STATUS'])
	{
		include 'info.php';
	}
	else
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<?include 'connection-help.php';?>
			</div>
		</div>
		<?php
	}
}
else
{
	?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<?php
				if (empty($arResult['FORM']['INFO_CONNECTION']))
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_CONNECT_TITLE')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_CONNECT_STEP', [
							'#ID#' => 'redirect=detail&code=11405312',
						])?>
					</div>
					<?php
				}
				else
				{
					?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_FINAL_FORM_DESCRIPTION')?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<?include 'messages.php'?>
	<?if ($arResult['STATUS'])
	{
		include 'info.php';
	}
	else
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<div class="imconnector-field-main-title">
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_AUTHORIZATION') ?>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-box-content">
						<?= Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_LOG_IN_OAUTH') ?>
					</div>
				</div>
				<?php
				if ($arResult['FORM']['USER']['URI'] !== '')
				{
					?>
					<div class="imconnector-field-social-connector">
						<div class="connector-icon ui-icon ui-icon-service-<?= $iconCode ?> imconnector-field-social-connector-icon">
							<i></i></div>
						<div class="ui-btn ui-btn-light-border" id="olx-oauth-button">
							<?= Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_AUTHORIZE') ?>
						</div>
						<select name="zone" id="olx-zone" class="imconnector-olx-select-zone imconnector-field-control-select">
							<option value="pl">PL</option>
							<option value="ua">UA</option>
						</select>
					</div>
					<?
				}
				?>
			</div>
		</div>
		<?php
	}
}
?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var oauthLinks = <?=CUtil::PhpToJSObject($arResult['FORM']['USER']['URI'])?>;
			var defaultZone = '<?=$arResult['OLX_CURRENT_ZONE']?>';
			var zoneUrl = oauthLinks[defaultZone];
			var select = document.getElementById('olx-zone');
			var oauthButton = document.getElementById('olx-oauth-button');

			if (select && oauthButton)
			{
				select.addEventListener('change', function() {
					zoneUrl = oauthLinks[this.value];
				}, false);

				oauthButton.addEventListener('click', function() {
					BX.util.popup(zoneUrl, 1200, 900);
				}, false);
			}
			if (select)
			{
				var selectOptions = select.childNodes;

				for (let i = 0; i < selectOptions.length; i++)
				{
					if (selectOptions[i].value === defaultZone)
					{
						selectOptions[i].selected = true;
					}
				}
			}
		}
	);
</script>
