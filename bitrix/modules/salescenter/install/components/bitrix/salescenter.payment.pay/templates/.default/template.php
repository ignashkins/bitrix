<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\UI\Extension;

Extension::load(["popup", "loader", "documentpreview", "sidepanel", "ui.fonts.ruble"]);
Main\Page\Asset::getInstance()->addJs("/bitrix/js/salescenter/payment-pay/script-es5.js");

$payment = $arResult['PAYMENT'];
$currentPaySystem = $payment['PAY_SYSTEM_INFO'];
$currentPaySystemName = (mb_strlen($currentPaySystem['NAME']) > 20)
	? mb_substr($currentPaySystem['NAME'], 0, 17).'...'
	: $currentPaySystem['NAME'];
$additionalContainerClasses = ($arParams['INCLUDED_IN_ORDER_TEMPLATE'] === 'Y')
	? 'order-payment-sibling-container'
	: (($arParams['TEMPLATE_MODE'] === 'darkmode') ? 'bx-dark' : '');
$messages = Loc::loadLanguageFile(__FILE__);

if (!empty($arResult["errorMessage"]))
{
	if (!is_array($arResult["errorMessage"]))
	{
		?>
		<div class="page-description"><?= $arResult["errorMessage"] ?></div>
		<?php
	}
	else
	{
		foreach ($arResult["errorMessage"] as $errorMessage)
		{
			?>
			<div class="page-description"><?= $errorMessage ?></div>
			<?php
		}
	}
}
elseif ($payment['PAID'] === 'Y')
{
	$title = Loc::getMessage('SPP_PAID_TITLE', [
		'#ACCOUNT_NUMBER#' => htmlspecialcharsbx($payment['ACCOUNT_NUMBER']),
		'#DATE_INSERT#' => $payment['DATE_BILL_FORMATTED'],
	]);
	?>
	<div class="order-payment-container <?= $additionalContainerClasses ?>">
		<div class="order-payment-title"><?= $title ?></div>
		<div class="order-payment-inner d-flex align-items-center justify-content-between">
			<div class="order-payment-operator">
				<?php if ($currentPaySystem['LOGOTIP']): ?>
					<img src="<?= $currentPaySystem['LOGOTIP'] ?>" alt="">
				<?php else: ?>
					<div class="order-payment-pay-system-name"><?= $currentPaySystemName ?></div>
				<?php endif ?>
			</div>
			<div class="order-payment-status d-flex align-items-center">
				<div class="order-payment-status-ok"></div>
				<div><?= Loc::getMessage('SPP_PAID') ?></div>
			</div>
			<div class="order-payment-price"><?= Loc::getMessage('SPP_SUM', ['#SUM#' => $payment['FORMATTED_SUM']]) ?></div>
		</div>
		<?php
		if ($arResult['CHECK'])
		{
			?>
			<hr>
			<?php
			$culture = Main\Context::getCurrent()->getCulture();
			foreach ($arResult['CHECK'] as $check)
			{
				if ($check['STATUS'] === 'Y' && $check['LINK'])
				{
					$checkTitle = Loc::getMessage("SPP_CHECK_TITLE", [
						'#CHECK_ID#' => $check['ID'],
						'#DATE_CREATE#' => \FormatDate($culture->getLongDateFormat(), $check['DATE_CREATE']->getTimestamp()),
					]);
					?>
					<div class="mb-2"><a href="<?= $check['LINK'] ?>" target="_blank" class="check-link"><?= $checkTitle ?></a></div>
					<?php
				}
				elseif ($check['STATUS'] === 'P')
				{
					$checkPrintTitle = Loc::getMessage("SPP_CHECK_PRINT_TITLE", [
						'#CHECK_ID#' => $check['ID'],
						'#DATE_CREATE#' => \FormatDate($culture->getLongDateFormat(), $check['DATE_CREATE']->getTimestamp()),
					]);
					?>
					<div class="mb-2 check-print"><?= $checkPrintTitle ?></div>
					<?php
				}
			}
		}
		?>
	</div>
	<?php
}
else
{
	$id = str_shuffle(mb_substr($arResult['SIGNED_PARAMS'], 0, 10));
	$wrapperId = "payment_container_$id";
	$submitButtonClass = "landing-block-node-button";
	$userConsentEventName = 'bx-spp-submit';
	$title = Loc::getMessage('SPP_PAID_TITLE', [
		'#ACCOUNT_NUMBER#' => htmlspecialcharsbx($payment['ACCOUNT_NUMBER']),
		'#DATE_INSERT#' => $payment['DATE_BILL_FORMATTED'],
	]);
	if ($arParams['ALLOW_SELECT_PAY_SYSTEM'] !== 'Y')
	{
		?>
		<div class="order-payment-container order-payment-sibling-container <?= $additionalContainerClasses ?> mb-4" id="<?= $wrapperId ?>">
			<div class="order-payment-title"><?= $title ?></div>
			<div class="order-payment-inner d-flex align-items-center justify-content-between">
				<div class="order-payment-operator">
					<?php if ($currentPaySystem['LOGOTIP']): ?>
						<img src="<?= $currentPaySystem['LOGOTIP'] ?>" alt="">
					<?php else: ?>
						<div class="order-payment-pay-system-name"><?= $currentPaySystemName ?></div>
					<?php endif ?>
				</div>

				<div class="order-payment-price"><?= Loc::getMessage('SPP_SUM',
						['#SUM#' => $payment['FORMATTED_SUM']]) ?></div>
			</div>
			<hr>
			<?php
			if ($arResult['USER_CONSENT'] === 'Y')
			{
				$APPLICATION->IncludeComponent(
					'bitrix:main.userconsent.request',
					'',
					array(
						'ID' => $arResult['USER_CONSENT_ID'],
						'IS_CHECKED' => ($arResult['USER_CONSENT_IS_CHECKED'] === 'Y') ? 'Y' : 'N',
						'IS_LOADED' => 'N',
						'AUTO_SAVE' => 'N',
						'SUBMIT_EVENT_NAME' => $userConsentEventName,
						'REPLACE' => array(
							'button_caption' => Loc::getMessage('SPP_PAY_BUTTON'),
						),
					)
				);
			}
			?>
			<div class="order-payment-buttons-container">
				<button class="<?= $submitButtonClass ?> text-uppercase btn btn-xl pr-7 pl-7 u-btn-primary g-font-weight-700 g-font-size-12 g-rounded-50">
					<?= Loc::getMessage('SPP_PAY_BUTTON'); ?>
				</button>
			</div>
		</div>
		<?php
		$settings = array(
			"paySystemId" => $currentPaySystem['ID'],
			"paySystemData" => [$currentPaySystem],
			"containerId" => $wrapperId,
			"consentEventName" => $userConsentEventName,
			"isAllowedSubmitting" => ($arResult['USER_CONSENT'] === 'Y' && $arResult['USER_CONSENT_IS_CHECKED']),
			"submitButtonSelector" => "button.$submitButtonClass",
			"url" => CUtil::JSEscape($this->__component->GetPath() . '/ajax.php'),
			"signedParameters" => $this->getComponent()->getSignedParameters(),
		);
		$settings = CUtil::PhpToJSObject($settings);
		?>
		<script>
			BX.message(<?=CUtil::PhpToJSObject($messages)?>);
			BX.ready(function ()
			{
				BX.addCustomEvent(window, 'onChangePaySystems', BX.delegate(function () {
					window.location.reload();
				}));
				BX.SalesCenter.Component.PaymentPayInner.create("<?=$id?>", <?=$settings?>);
			});
		</script>
		<?php
	}
	elseif (!empty($arResult['PAYSYSTEMS_LIST']))
	{
		$paySystemListClassName = "order-payment-method-list";
		$paySystemDescriptionClassName = "order-payment-method-description";
		$title = Loc::getMessage('SPP_SELECT_PAYMENT_TITLE_NEW_NEW');
		if ($arParams['VIEW_MODE'] === 'Y')
		{
			$additionalContainerClasses .= " order-payment-view-mode";
			$title = '';
		}
		?>
		<div class="page-section order-payment-method-container <?= $additionalContainerClasses ?>" id="<?= $wrapperId ?>">
			<?php if ($title !== ''): ?>
				<div class="page-section-title"><?= $title ?></div>
			<?php endif; ?>
			<div class="<?= $paySystemListClassName ?>"></div>
			<?php
			if ($arParams['VIEW_MODE'] === 'Y')
			{
				?>
				<div class="<?= $paySystemDescriptionClassName ?>"></div>
				<?php
			}
			if ($arParams['VIEW_MODE'] !== 'Y')
			{
				if ($arResult['USER_CONSENT'] === 'Y')
				{
					$APPLICATION->IncludeComponent(
						'bitrix:main.userconsent.request',
						'',
						array(
							'ID' => $arResult['USER_CONSENT_ID'],
							'IS_CHECKED' => ($arResult['USER_CONSENT_IS_CHECKED'] === 'Y') ? 'Y' : 'N',
							'IS_LOADED' => 'N',
							'AUTO_SAVE' => 'N',
							'SUBMIT_EVENT_NAME' => $userConsentEventName,
							'REPLACE' => array(
								'button_caption' => Loc::getMessage('SPP_PAY_BUTTON'),
							),
						)
					);
				}
			}
			?>
		</div>

		<?php
		$first = current($arResult['PAYSYSTEMS_LIST']);
		$settings = array(
			"paySystemId" => (int)$currentPaySystem['PAY_SYSTEM_ID'] > 0 ? (int)$currentPaySystem['PAY_SYSTEM_ID'] : (int)$first['ID'],
			"paySystemData" => $arResult['PAYSYSTEMS_LIST'],
			"containerId" => $wrapperId,
			"viewMode" => ($arParams['VIEW_MODE'] === 'Y'),
			"consentEventName" => $userConsentEventName,
			"isAllowedSubmitting" => ($arResult['USER_CONSENT'] === 'Y' && $arResult['USER_CONSENT_IS_CHECKED'] === 'Y'),
			"paySystemBlockSelector" => ".$paySystemListClassName",
			"descriptionBlockSelector" => ".$paySystemDescriptionClassName",
			"submitButtonSelector" => "button.$submitButtonClass",
			"url" => CUtil::JSEscape($this->__component->GetPath() . '/ajax.php'),
			"allowPaymentRedirect" => $arParams['ALLOW_PAYMENT_REDIRECT'] === "Y",
			"signedParameters" => $this->getComponent()->getSignedParameters(),
			"returnUrl" => CUtil::JSEscape($arResult["RETURN_URL"]),
		);
		$settings = CUtil::PhpToJSObject($settings);
		?>
		<script>
			BX.message(<?=CUtil::PhpToJSObject($messages)?>);
			BX.ready(function ()
			{
				BX.addCustomEvent(window, 'onChangePaySystems', BX.delegate(function () {
					window.location.reload();
				}));

				BX.SalesCenter.Component.PaymentPayList.create("<?=$id?>", <?=$settings?>);
			});
		</script>
		<?php
	}
}

