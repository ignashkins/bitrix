<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/timeline.bundle.css',
	'js' => 'dist/timeline.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'timeman.timeformatter',
		'ui.vue.components.hint',
		'ui.vue',
	],
	'skip_core' => true,
];