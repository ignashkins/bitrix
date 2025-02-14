<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$groupId = $request->get('groupId');
$afterCreate = '#SITE_DIR#kb/group/wiki/#site_show#/view/#landing_edit#/';

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:landing.binding.group',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'TYPE' => 'GROUP',
			'GROUP_ID' => $groupId,
			'PATH_AFTER_CREATE' => $afterCreate
		],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y'
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
