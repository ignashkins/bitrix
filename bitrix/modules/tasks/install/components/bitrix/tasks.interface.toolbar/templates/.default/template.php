<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Access;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass? $bodyClass.' ' : '').'pagetitle-toolbar-field-view tasks-pagetitle-view');

Extension::load([
	'ui.buttons',
	'ui.counter',
	'ui.fonts.opensans',
	'ui.hint',
]);

$isMyTasks = $arResult['USER_ID'] === $arResult['OWNER_ID'];
$showViewMode = $arParams['SHOW_VIEW_MODE'] == 'Y';
$isSprintMode = $arParams['SPRINT_SELECTED'] == 'Y';
$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";
$taskLimitExceeded = $arResult['TASK_LIMIT_EXCEEDED'];

if ($isBitrix24Template)
{
	$this->SetViewTarget("below_pagetitle");
}
?>

<div class="task-interface-toolbar">
<?php if ($showViewMode && !($arParams['PROJECT_VIEW'] === 'Y' && !$arParams['GROUP_ID'])):?>
<div class="task-interface-toolbar--item --visible">
    <div class="tasks-view-switcher">
        <?php
			// temporary we show agile parts only by option
			$optionName = 'agile_enabled_group_'.$arParams['GROUP_ID'];
			$showSprint = Option::get('tasks', $optionName, 'N') === 'Y';
			$template = ($arParams['GROUP_ID'] > 0 ? 'PATH_TO_GROUP_TASKS' : 'PATH_TO_USER_TASKS');
			$link = CComponentEngine::makePathFromTemplate($template, [
				'user_id' => $arParams['OWNER_ID'],
				'group_id' => $arParams['GROUP_ID'],
			]);

			foreach ($arResult['VIEW_LIST'] as $viewKey => $view)
			{
				$hideSprint = ($viewKey === 'VIEW_MODE_SPRINT' && !$showSprint);
				$isOfKanbanType = in_array($viewKey, ['VIEW_MODE_KANBAN', 'VIEW_MODE_SPRINT']);

				// kanban and sprint only for group
				if ($hideSprint || (int)$arParams['GROUP_ID'] <= 0 && $isOfKanbanType)
				{
					continue;
				}

				$active = array_key_exists('SELECTED', $view) && $view['SELECTED'] === 'Y';
				$state = \Bitrix\Tasks\Ui\Filter\Task::getListStateInstance()->getState();
				$url = '?F_STATE=sV'.CTaskListState::encodeState($view['ID']);
				if ($_REQUEST['IFRAME'])
				{
					$url .= '&IFRAME='.($_REQUEST['IFRAME'] == 'Y' ? 'Y' : 'N');
				}

				?><a class="tasks-view-switcher--item<?=($active ? ' tasks-view-switcher--item --active' : '')?>"
					 href="<?=$url?>" id="tasks_<?= mb_strtolower($viewKey)?>">
					<?=$view['SHORT_TITLE']?>
				</a><?php
			}
	?></div>
</div>
<?php endif?>

<?php if (!$isBitrix24Template):?>
	<div class="tasks-interface-toolbar-container">
<?php endif ?>
	<?php
	if ($arResult['SHOW_COUNTERS'])
	{
		$APPLICATION->IncludeComponent(
			'bitrix:tasks.interface.counters',
			'',
			[
				'USER_ID' => (int)$arResult['OWNER_ID'],
				'GROUP_ID' => (int)$arResult['GROUP_ID'],
				'ROLE' => $arResult['ROLE'],
				'GRID_ID' => $arParams['GRID_ID'],
				'COUNTERS' => ($arParams['COUNTERS'] ? : []),
				'FILTER_FIELD' => $arParams['FILTER_FIELD'],
			],
			$component
		);
	}
	?>

	<div class="task-interface-toolbar--item --without-bg --align-right">
		<div class="task-interface-toolbar--item--scope">
	<?php
	if (
		$isMyTasks
		&& $arResult['SHOW_COUNTERS']
		&& Factory::canUseAutomation()
		&& Access\TaskAccessController::can($arParams['USER_ID'], Access\ActionDictionary::ACTION_TASK_ROBOT_EDIT)
	)
	{
		$groupId = (int)$arParams['GROUP_ID'];
		$projectId = ($showViewMode ? $groupId : 'this.getAttribute(\'data-project-id\')');

		$showLimitSlider = $taskLimitExceeded && !Factory::canUseAutomation();
		$openLimitSliderAction = "BX.UI.InfoHelper.show('limit_tasks_robots')";
		$openRobotSliderAction = "BX.SidePanel.Instance.open('/bitrix/components/bitrix/tasks.automation/slider.php?site_id='+BX.message('SITE_ID')+'&amp;project_id='+{$projectId});";

		$lockClass = ($showLimitSlider ? 'ui-btn-icon-lock' : '');
		$onClick = ($showLimitSlider ? $openLimitSliderAction : $openRobotSliderAction);
		?>
		<button class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round <?=$lockClass?> task-interface-btn-toolbar --robots --small"
			<?=($showViewMode ? '' : "data-project-id='{$groupId}'")?> onclick="<?=$onClick?>">
			<?=GetMessage('TASKS_SWITCHER_ITEM_ROBOTS')?>
		</button>
		<?php
	}

	if (\Bitrix\Main\Loader::includeModule('intranet') && !$isSprintMode)
	{
		$context = $arParams['GROUP_ID']
			? ['GROUP_ID' => $arParams['GROUP_ID']]
			: ['USER_ID' => $arParams['OWNER_ID']];
		$menuCode = $arParams['GROUP_ID'] ? 'group' : 'user';
		$APPLICATION->includeComponent(
			'bitrix:intranet.binding.menu',
			'',
			array(
				'SECTION_CODE' => 'tasks_switcher',
				'MENU_CODE' => $menuCode,
				'CONTEXT' => $context
			)
		);
	}
	?>
		</div>
	</div>

<?php
if (!$isBitrix24Template):?>
	</div>
<?php endif?>

</div>

<?php
if ($isBitrix24Template)
{
    $this->EndViewTarget();
}
?>

<div style="<?=$state['VIEW_SELECTED']['CODENAME'] == 'VIEW_MODE_GANTT' ? 'margin:-15px -15px 15px  -15px' : ''?>">
    <?php
		echo Stepper::getHtml(
			['tasks' => 'Bitrix\Tasks\Update\FullTasksIndexer'],
			GetMessage('TASKS_FULL_TASK_INDEXING_TITLE')
		);
		echo Stepper::getHtml(
			['tasks' => 'Bitrix\Tasks\Update\TemplateCheckListConverter'],
			GetMessage('TASKS_TEMPLATE_CHECKLIST_CONVERTING_TITLE')
		);
		echo Stepper::getHtml(
			['tasks' => 'Bitrix\Tasks\Update\TaskCheckListConverter'],
			GetMessage('TASKS_TASK_CHECKLIST_CONVERTING_TITLE')
		);
		echo Stepper::getHtml([
			'tasks' => [
				'Bitrix\Tasks\Update\LivefeedIndexTask',
				'Bitrix\Tasks\Update\TasksFilterConverter',
			]
		]);
	?>

	<?php if ((\Bitrix\Tasks\Internals\Counter\Queue\Queue::getInstance())->isInQueue((int) $arParams['USER_ID'])): ?>
		<?php \CJSCore::Init(array('update_stepper')); ?>
		<div class="main-stepper-block">
			<div class="main-stepper main-stepper-show" >
				<div class="main-stepper-info"><?= GetMessage('TASKS_FULL_TASK_INDEXING_TITLE'); ?></div>
				<div class="main-stepper-inner">
					<div class="main-stepper-bar">
						<div class="main-stepper-bar-line" style="width:0%;"></div>
					</div>
					<div class="main-stepper-error-text"></div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
</div>
<?php
if ($arResult['SHOW_COUNTERS'])
{
	$arResult['HELPER']->initializeExtension();
}
if ($arResult['SPOTLIGHT_SIMPLE_COUNTERS'])
{
	\CJSCore::init('spotlight');
}
?>
<script>
	BX.ready(function()
	{
		BX.message({
			_VIEW_TYPE: '<?=$state['VIEW_SELECTED']['CODENAME']?>'
		});

		var robotsBtn = document.querySelector('button[data-project-id]');
		if (robotsBtn)
		{
			BX.addCustomEvent(window, 'BX.Kanban.ChangeGroup', function(newId) {
				robotsBtn.setAttribute('data-project-id', newId);
			});
		}

		<?if ($arResult['SPOTLIGHT_SIMPLE_COUNTERS']):?>
			var targetElement = BX('tasksSimpleCounters');
			if (targetElement)
			{
				var spotlight = new BX.SpotLight({
					id: 'tasks_simple_counters',
					targetElement: targetElement,
					content: '<?= \CUtil::jsEscape(GetMessage('TASKS_TEMPLATE_SPOTLIGHT_SIMPLE_COUNTERS'))?>',
					targetVertex: 'middle-left',
					left: 24,
					autoSave: true,
					lightMode: true
				});
				spotlight.show();
				spotlight.getPopup().getButtons()[0].setName('<?=GetMessage('TASKS_TEMPLATE_SPOTLIGHT_SIMPLE_COUNTERS_BUTTON')?>');
				BX.addCustomEvent(spotlight, 'spotLightOk', function() {
					if (top.BX.Helper)
					{
						top.BX.Helper.show(`redirect=detail&code=11330068`);
					}
				});
			}
		<?php endif;?>

		<?php if ($arResult['SHOW_COUNTERS']):?>
			BX.UI.Hint.init(BX('tasksCommentsReadAll'));
		<?php endif;?>

		var toolbarCounters = document.querySelector('.task-interface-toolbar');
		if(toolbarCounters)
		{
			var toolbarCountersItems = toolbarCounters.querySelectorAll('.task-interface-toolbar--item');
			var toolbarCountersRobots = toolbarCounters.querySelector('.--align-right');
			if(toolbarCountersRobots)
			{
				toolbarCountersRobots.classList.add('task-interface-toolbar--item--' + toolbarCountersItems.length);
			}
		}
	});
</script>