<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/monitor-group.bundle.css',
	'js' => 'dist/monitor-group.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vuex',
		'ui.vue',
		'timeman.mixin',
		'timeman.const',
		'ui.vue.components.hint',
		'ui.vue.portal',
		'main.popup',
		'ui.dialogs.messagebox',
		'ui.icons',
	],
	'skip_core' => true,
];