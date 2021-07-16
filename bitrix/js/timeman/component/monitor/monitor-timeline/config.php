<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/monitor-timeline.bundle.css',
	'js' => 'dist/monitor-timeline.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vuex',
		'timeman.mixin',
		'timeman.const',
		'ui.vue',
	],
	'skip_core' => true,
];