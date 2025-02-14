<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Connector;

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

const HELP_DESK_INFO_CONNECT_ID = '13406062';
const HELP_DESK_NO_SPECIFIC_PAGE = '10443976';
const HELP_DESK_CONVERT_TO_BUSINESS_HELP = '10443962';

if ($arParams['INDIVIDUAL_USE'] !== 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	Extension::load([
		'ui.buttons',
		'ui.hint',
		'popup'
	]);
	Connector::initIconCss();
}

$helpDeskLinkStart =
	'<a href="javascript:void(0)" onclick=\'top.BX.Helper.show("redirect=detail&code='
	. HELP_DESK_NO_SPECIFIC_PAGE
	. '");event.preventDefault();\'>';
$helpDeskLinkEnd = '</a>';

$helpDeskLinkNoPage = str_replace(
	['#A#', '#A_END#'],
	[$helpDeskLinkStart, $helpDeskLinkEnd],
	Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_NO_SPECIFIC_PAGE')
);

$helpDeskLinkConvertToBusinessHelp =
	'<a class="imconnector-field-social-list-item-link" href="javascript:void(0)" onclick=\'top.BX.Helper.show("redirect=detail&code='
	. HELP_DESK_CONVERT_TO_BUSINESS_HELP
	. '");event.preventDefault();\'>'
	. Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONVERT_TO_BUSINESS_HELP')
	. '</a>';

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);
?>
	<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
		<?=bitrix_sessid_post()?>
	</form>
<?
if(
	empty($arResult['PAGE'])
	&& $arResult['ACTIVE_STATUS']
)
{
	if ($arResult['STATUS'])
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section imconnector-field-section-social">
				<div class="imconnector-field-box">
					<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CHANGE_ANY_TIME')?>
					</div>
					<div class="ui-btn-container">
						<a href="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>" class="ui-btn ui-btn-primary show-preloader-button">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CHANGE_SETTING')?>
						</a>
						<button class="ui-btn ui-btn-light-border"
								onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?include 'messages.php'?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<div class="imconnector-field-main-title">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INFO')?>
				</div>
				<div class="imconnector-field-box">
					<?
					if (!empty($arResult['FORM']['USER']['INFO']['URL']))
					{
						?>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_USER')?>
							</div>
							<a href="<?=$arResult['FORM']['USER']['INFO']['URL']?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=$arResult['FORM']['USER']['INFO']['NAME']?>
							</a>
							<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
								  data-text="<?=CUtil::JSEscape($arResult['FORM']['USER']['INFO']['URL'])?>"></span>
						</div>
						<?
					}
					?>
					<?
					if (!empty($arResult['FORM']['PAGE']['URL']))
					{
						?>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECTED_PAGE')?>
							</div>
							<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
							<span class="imconnector-field-box-entity-link">
							<?else:?>
							<a href="<?=$arResult['FORM']['PAGE']['INSTAGRAM']['URL'] ?>"
								target="_blank"
							   class="imconnector-field-box-entity-link">
							<?endif;?>
								<?=$arResult['FORM']['PAGE']['INSTAGRAM']['NAME'] ?> <?if(!empty($arResult['FORM']['PAGE']['INSTAGRAM']['MEDIA_COUNT'])):?> (<?=$arResult['FORM']['PAGE']['INSTAGRAM']['MEDIA_COUNT']?> <?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_MEDIA') ?>)<?endif;?>
							<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
							</span>
							<?else:?>
							</a>
							<?endif;?>
						</div>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PREFIX_NAMING_PAGE') ?></div>

							<a href="<?=$arResult['FORM']['PAGE']['URL']?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=$arResult['FORM']['PAGE']['NAME']?>
							</a>
							<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
								<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
									  data-text="<?=CUtil::JSEscape($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])?>"></span>
							<?endif;?>
						</div>
						<?
					}
					?>
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
					<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-main-subtitle">
						<?=$arResult['NAME']?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SETTING_IS_NOT_COMPLETED')?>
					</div>
					<div class="ui-btn-container">
						<a href="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>"
						   class="ui-btn ui-btn-primary show-preloader-button">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CONTINUE_WITH_THE_SETUP')?>
						</a>
						<button class="ui-btn ui-btn-light-border"
								onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?include 'messages.php'?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<?include 'connection-help.php';?>
			</div>
		</div>
		<?
	}
}
else
{
	if (empty($arResult['FORM']['USER']['INFO'])) //start case with clear connections
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section imconnector-field-section-social">
				<div class="imconnector-field-box">
					<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TITLE_NEW') ?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_DESCRIPTION_NEW') ?>
					</div>
					<?if(!$arResult['ACTIVE_STATUS']):?>
						<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" id="<?=$arResult['CONNECTOR']?>_form_active">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active" value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
							<?if(!empty($arResult['AGREEMENT_TERMS'])):?>
								<input type="hidden" name="<?=$arResult['CONNECTOR']?>_agreement_terms" value="true">
							<?endif;?>
							<?=bitrix_sessid_post()?>
							<button class="ui-btn ui-btn-light-border"
									id="<?=$arResult['CONNECTOR']?>_active_terms"
									type="button"
									value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
							</button>
						</form>
					<?endif;?>
				</div>
			</div>
		</div>
		<?include 'messages.php'?>
		<?
		if($arResult['ACTIVE_STATUS']) //case before auth to fb
		{
			?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section">
					<div class="imconnector-field-main-title">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_AUTHORIZATION')?>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_LOG_IN_UNDER_AN_ADMINISTRATOR_ACCOUNT_PAGE')?>
						</div>
					</div>
					<?
					if ($arResult['FORM']['USER']['URI'] != '')
					{
						?>
						<div class="imconnector-field-social-connector">
							<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-connector-icon"><i></i></div>
							<div class="ui-btn ui-btn-light-border"
								 onclick="BX.util.popup('<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['USER']['URI']))?>', 700, 850)">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_AUTHORIZE')?>
							</div>
						</div>
						<?
					}
					?>
				</div>
			</div>
			<?
		}
		if(!$arResult['ACTIVE_STATUS'])
		{    //case before start connecting to fb
			?>
			<script>
				(function() {
					var buttonActive = document.getElementById("<?=$arResult['CONNECTOR']?>_active_terms");
					var formActive = document.getElementById("<?=$arResult['CONNECTOR']?>_form_active");
					<?if(!empty($arResult['AGREEMENT_TERMS'])):?>
					var popupTerms = new BX.PopupWindow({
						titleBar: "<?=CUtil::JSescape($arResult['AGREEMENT_TERMS']['NAME'])?>",
						content: "<?=CUtil::JSescape($arResult['AGREEMENT_TERMS']['HTML'])?>",
						closeIcon: true,
						width: 600,
						height: 700,
						buttons: [
							new BX.PopupWindowButton({
								text : "<?=CUtil::JSescape(
										Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_AGREEMENT_TERMS_AGREE')
								)?>",
								className : 'popup-window-button-accept',
								events : {
									click: function() {
										formActive.submit();
									}
								}
							})
						]
					});
					buttonActive.addEventListener("click", function() {
						popupTerms.show();
					});
					<?else:?>
					buttonActive.addEventListener("click", function() {
						formActive.submit();
					});
					<?endif;?>
				})();
			</script>
			<?
		}
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<?include 'connection-help.php';?>
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
					<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECTED')?>
					</div>
					<div class="imconnector-field-social-card">
						<div class="imconnector-field-social-card-info">
							<div class="imconnector-field-social-icon"></div>
							<?if(empty($arResult['FORM']['USER']['INFO']['URL'])):?>
								<span class="imconnector-field-social-name">
							<?else:?>
								<a href="<?=$arResult['FORM']['USER']['INFO']['URL']?>"
									target="_blank"
									 class="imconnector-field-social-name">
							<?endif;?>
								<?=$arResult['FORM']['USER']['INFO']['NAME']?>
							<?if(empty($arResult['FORM']['USER']['INFO']['URL'])):?>
								</span>
							<?else:?>
								</a>
							<?endif;?>
						</div>
						<div class="ui-btn ui-btn-sm ui-btn-light-border imconnector-field-social-card-button"
							 onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?include 'messages.php'?>
		<?
		if (empty($arResult['FORM']['PAGES']))  //case user haven't got any groups.
		{
			?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section">
					<div class="imconnector-field-main-title">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECT_FACEBOOK_PAGE')?>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_THERE_IS_NO_PAGE_WHERE_THE_ADMINISTRATOR')?>
						</div>
					</div>
					<div class="imconnector-field-social-connector">
						<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-connector-icon"><i></i></div>
						<a href="https://www.facebook.com/pages/create/"
						   class="ui-btn ui-btn-light-border"
						   target="_blank">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CREATE_A_PAGE')?>
						</a>
					</div>
				</div>
			</div>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section">
					<?include 'connection-help.php';?>
				</div>
			</div>
			<?
		}
		else
		{
			if (empty($arResult['FORM']['PAGE'])) //case user haven't choose active group yet
			{
				?>
				<div class="imconnector-field-container">
					<div class="imconnector-field-section imconnector-field-section-social-list imconnector-field-section-social-list-fbinstagram">
						<div class="imconnector-field-main-title">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_SELECT_THE_PAGE')?>
						</div>
						<div class="imconnector-field-social-list">
							<?
							foreach ($arResult['FORM']['PAGES'] as $page)
							{
								if (empty($page['ACTIVE']))
								{
									?>
									<div class="imconnector-field-social-list-item">
										<div class="imconnector-field-social-list-inner">
											<div class="imconnector-field-social-icon imconnector-field-social-list-icon"<?if(!empty($page['INFO']['INSTAGRAM']['PROFILE_PICTURE_URL'])):?> style='background: url("<?=$page['INFO']['INSTAGRAM']['PROFILE_PICTURE_URL']?>"); background-size: cover'<?endif;?>></div>
											<div class="imconnector-field-social-list-info">
												<div class="imconnector-field-social-list-info-inner">
													<?if(empty($page['INFO']['INSTAGRAM']['URL'])):?>
													<span class="imconnector-field-social-name">
													<?else:?>
													<a href="<?=$page['INFO']['INSTAGRAM']['URL']?>"
														target="_blank"
														class="imconnector-field-social-name">
													<?endif;?>
														<?=$page['INFO']['INSTAGRAM']['NAME'] ?> <?if(!empty($page['INFO']['INSTAGRAM']['MEDIA_COUNT'])):?> (<?=$page['INFO']['INSTAGRAM']['MEDIA_COUNT'];?> <?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_MEDIA')?>)<?endif;?>
													<?if(empty($page['INFO']['INSTAGRAM']['URL'])):?>
														</span>
													<?else:?>
														</a>
													<?endif;?>

													<span class="imconnector-field-social-name imconnector-field-social-name-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PREFIX_NAMING_PAGE')?></span>

													<?if(empty($page['INFO']['URL'])):?>
														<span class="imconnector-field-social-name">
													<?else:?>
														<div class="imconnector-field-social-name-icon ui-icon ui-icon-service-instagram"><i></i></div>
														<a href="<?=$page['INFO']['URL']?>"
															target="_blank"
															class="imconnector-field-social-name imconnector-field-social-name-url">
													<?endif;?>
													<?=$page['INFO']['NAME']?>
													<?if(empty($page['INFO']['URL'])):?>
														</span>
													<?else:?>
														</a>
													<?endif;?>
												</div>
												<?php
												if ($page['INFO']['INSTAGRAM']['BUSINESS'] !== 'N')
												{?>
													<span class="imconnector-field-social-account imconnector-field-social-account-business">
													<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_BUSINESS_ACCOUNT')?>
												</span>
													<?php
												}
												else
												{?>
													<span class="imconnector-field-social-account">
													<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PERSONAL_ACCOUNT')?>
												</span>
													<?php
												}
												?>
											</div>
										</div>
										<?php
										if ($page['INFO']['INSTAGRAM']['BUSINESS'] !== 'N')
										{
											?>
											<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post">
												<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
												<input type="hidden" name="page_id" value="<?=$page['INFO']['ID']?>">
												<?=bitrix_sessid_post(); ?>
												<button type="submit"
														name="<?=$arResult['CONNECTOR']?>_authorization_page"
														class="ui-btn ui-btn-sm ui-btn-light-border"
														value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
													<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
												</button>
											</form>
											<?php
										}
										else
										{?>
											<?=$helpDeskLinkConvertToBusinessHelp?>
										<?}?>
									</div>
									<?php
								}
							}
							?>
							<div class="imconnector-field-box-subtitle">
								<?=$helpDeskLinkNoPage?>
							</div>
						</div>
					</div>
				</div>
				<div class="imconnector-field-container">
					<div class="imconnector-field-section">
						<?include 'connection-help.php';?>
					</div>
				</div>
				<?
			}
			else
			{
				?>
				<div class="imconnector-field-container">
					<div class="imconnector-field-section imconnector-field-section-social-list-fbinstagram">
						<div class="imconnector-field-main-title imconnector-field-main-title-no-border">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECTED_PAGE')?>
						</div>

						<div class="imconnector-field-social-card">
							<div class="imconnector-field-social-card-info">
								<div<?if(!empty($arResult['FORM']['PAGE']['INSTAGRAM']['PROFILE_PICTURE_URL'])):?> class="imconnector-field-social-icon imconnector-field-social-list-icon" style='background: url("<?=$arResult['FORM']['PAGE']['INSTAGRAM']['PROFILE_PICTURE_URL']?>"); background-size: cover'<?else:?> class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-icon"<?endif;?>><i></i></div>

								<div class="imconnector-field-social-list-info">
									<div class="imconnector-field-social-list-info-inner">
										<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
										<span class="imconnector-field-social-name">
										<?else:?>
										<a href="<?=$arResult['FORM']['PAGE']['INSTAGRAM']['URL']?>"
											target="_blank"
											class="imconnector-field-social-name">
										<?endif;?>
										<?=$arResult['FORM']['PAGE']['INSTAGRAM']['NAME']?> <?if(!empty($arResult['FORM']['PAGE']['INSTAGRAM']['MEDIA_COUNT'])):?> (<?=$arResult['FORM']['PAGE']['INSTAGRAM']['MEDIA_COUNT'];?> <?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_MEDIA')?>)<?endif;?>
										<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
										</span>
										<?else:?>
										</a>
										<?endif;?>

										<span class="imconnector-field-social-name imconnector-field-social-name-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PREFIX_NAMING_PAGE')?></span>

										<?if(empty($arResult['FORM']['PAGE']['URL'])):?>
										<span class="imconnector-field-social-name">
										<?else:?>
										<div class="imconnector-field-social-name-icon ui-icon ui-icon-service-instagram"><i></i></div>
										<a href="<?=$arResult['FORM']['PAGE']['URL']?>"
											target="_blank"
											class="imconnector-field-social-name">
										<?endif;?>
										<?=$arResult['FORM']['PAGE']['NAME']?>
										<?if(empty($arResult['FORM']['PAGE']['URL'])):?>
										</span>
										<?else:?>
										</a>
										<?endif;?>
									</div>
									<span class="imconnector-field-social-account imconnector-field-social-account-business">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_BUSINESS_ACCOUNT')?>
								</span>
								</div>

							</div>
							<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post">
								<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
								<input type="hidden" name="page_id" value="<?=$arResult['FORM']['PAGE']['ID']?>">
								<?=bitrix_sessid_post()?>
								<button class="ui-btn ui-btn-sm ui-btn-light-border imconnector-field-social-card-button"
										name="<?=$arResult['CONNECTOR']?>_del_page"
										type="submit"
										value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_DEL_REFERENCE')?>">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_DEL_REFERENCE')?>
								</button>
							</form>
						</div>

						<?
						if (count($arResult['FORM']['PAGES']) > 1)
						{
							?>
							<div class="imconnector-field-dropdown-button" id="toggle-list">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_OTHER_PAGES')?>
							</div>

							<div class="imconnector-field-box imconnector-field-social-list-modifier imconnector-field-box-hidden"
								 id="hidden-list">
								<div class="imconnector-field-main-title">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_OTHER_PAGES')?>
								</div>
								<div class="imconnector-field-social-list">
									<?
									foreach ($arResult['FORM']['PAGES'] as $page)
									{
										if (empty($page['ACTIVE']))
										{
											?>
											<div class="imconnector-field-social-list-item">
												<div class="imconnector-field-social-list-inner">
													<div class="imconnector-field-social-icon imconnector-field-social-list-icon"<?if(!empty($page['INFO']['INSTAGRAM']['PROFILE_PICTURE_URL'])):?> style='background: url('<?=$page['INFO']['INSTAGRAM']['PROFILE_PICTURE_URL']?>'); background-size: cover'<?endif;?>></div>

													<div class="imconnector-field-social-list-info">
														<div class="imconnector-field-social-list-info-inner">
															<?if(empty($page['INFO']['INSTAGRAM']['URL'])):?>
															<span class="imconnector-field-social-name">
															<?else:?>
															<a href="<?=$page['INFO']['INSTAGRAM']['URL']?>"
																target="_blank"
																class="imconnector-field-social-name">
															<?endif;?>
															<?=$page['INFO']['INSTAGRAM']['NAME']?> <?if(!empty($page['INFO']['INSTAGRAM']['MEDIA_COUNT'])):?> (<?=$page['INFO']['INSTAGRAM']['MEDIA_COUNT']?> <?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_MEDIA')?>)<?endif;?>
															<?if(empty($page['INFO']['INSTAGRAM']['URL'])):?>
															</span>
															<?else:?>
															</a>
															<?endif;?>

															<span class="imconnector-field-social-name imconnector-field-social-name-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PREFIX_NAMING_PAGE')?></span>

															<?if(empty($page['INFO']['URL'])):?>
															<span class="imconnector-field-social-name">
															<?else:?>
															<div class="imconnector-field-social-name-icon ui-icon ui-icon-service-instagram"><i></i></div>
															<a href="<?=$page['INFO']['URL']?>"
																target="_blank"
																class="imconnector-field-social-name">
															<?endif;?>
															<?=$page['INFO']['NAME']?>
															<?if(empty($page['INFO']['URL'])):?>
															</span>
															<?else:?>
															</a>
															<?endif;?>
														</div>
														<?php
														if ($page['INFO']['INSTAGRAM']['BUSINESS'] !== 'N')
														{?>
															<span class="imconnector-field-social-account imconnector-field-social-account-business">
																<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_BUSINESS_ACCOUNT')?>
															</span>
															<?php
														}
														else
														{?>
															<span class="imconnector-field-social-account">
																<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PERSONAL_ACCOUNT')?>
															</span>
															<?php
														}
														?>
													</div>

												</div>
												<?php
												if ($page['INFO']['INSTAGRAM']['BUSINESS'] !== 'N')
												{?>
													<form action="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>" method="post">
														<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form"
															   value="true">
														<input type="hidden" name="page_id"
															   value="<?=$page['INFO']['ID']?>">
														<?=bitrix_sessid_post()?>
														<button type="submit"
																name="<?=$arResult['CONNECTOR']?>_authorization_page"
																class="ui-btn ui-btn-sm ui-btn-light-border"
																value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CHANGE_PAGE')?>">
															<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CHANGE_PAGE')?>
														</button>
													</form>
												<?php
												}
												else
												{?>
													<?=$helpDeskLinkConvertToBusinessHelp?>
												<?}?>
											</div>
											<?php
										}
									}
									?>
									<div class="imconnector-field-box-subtitle">
										<?=$helpDeskLinkNoPage?>
									</div>
								</div>
							</div>
							<?
						}
						?>
					</div>
				</div>
				<?
			}
		}
	}
}
?>

