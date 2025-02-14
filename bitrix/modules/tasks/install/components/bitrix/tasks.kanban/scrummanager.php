<?php

namespace Bitrix\Tasks\Component\Kanban;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Scrum\Utility\StoryPoints;

class ScrumManager
{
	private $groupId;

	public function __construct(int $groupId)
	{
		$this->groupId = (int)$groupId;
	}

	public function isScrumProject(): bool
	{
		$group = Workgroup::getById($this->groupId);

		return ($group && $group->isScrumProject());
	}

	public function getScrumTaskResponsible(int $defaultUserId): int
	{
		$responsibleId = $defaultUserId;

		$group = Workgroup::getById($this->groupId);
		if ($group)
		{
			$scrumTaskResponsible = $group->getScrumTaskResponsible();
			$responsibleId = ($scrumTaskResponsible == 'A' ? $defaultUserId : $group->getScrumMaster());
		}

		return $responsibleId;
	}

	public function groupBySubTasks(TaskRegistry $taskRegistry, array $items, array $columns): array
	{
		$parentTasks = [];

		list($updatedItems, $subTasksItems, $updatedColumns) = $this->extractSubTasksItems($items, $columns);
		list($updatedItems, $updatedColumns) = $this->extractParentTasksItems(
			$updatedItems,
			$subTasksItems,
			$updatedColumns
		);

		foreach ($columns as &$column)
		{
			$column['total'] = 0;
		}

		foreach ($subTasksItems as $item)
		{
			if (!array_key_exists($item['parentId'], $parentTasks))
			{
				$parentTaskData = $taskRegistry->get($item['parentId']);
				$parentTasks[$item['parentId']] = [
					'id' => $parentTaskData['ID'],
					'name' => $parentTaskData['TITLE'],
					'completed' => ($parentTaskData['STATUS'] == \CTasks::STATE_COMPLETED ? 'Y' : 'N'),
					'storyPoints' => $this->getStoryPoints($parentTaskData['ID'], $items, $subTasksItems),
				];
				$parentTasks[$item['parentId']]['columns'] = $columns;
				$parentTasks[$item['parentId']]['items'] = [];
			}

			$parentTasks[$item['parentId']]['columns'] = $this->addToColumnTotal(
				$parentTasks[$item['parentId']]['columns'],
				$item
			);
			$parentTasks[$item['parentId']]['items'][] = $item;
		}

		return [$updatedItems, $updatedColumns, $parentTasks];
	}

	private function extractSubTasksItems(array $inputItems, array $columns): array
	{
		$items = [];
		$subTasksItems = [];

		foreach ($inputItems as $key => $item)
		{
			if ((int)$item['parentId'])
			{
				$subTasksItems[] = $item;
				$columns = $this->subtractFromColumnTotal($columns, $item);
			}
			else
			{
				$items[] = $item;
			}
		}

		return [$items, $subTasksItems, $columns];
	}

	private function extractParentTasksItems(array $inputItems, array $subTasksItems, array $columns): array
	{
		$items = [];

		foreach ($inputItems as $key => $item)
		{
			if (array_search($item['id'], array_column($subTasksItems, 'parentId')) === false)
			{
				$items[] = $item;
			}
			else
			{
				$columns = $this->subtractFromColumnTotal($columns, $item);
			}
		}

		return [$items, $columns];
	}

	private function addToColumnTotal(array $columns, array $item): array
	{
		foreach ($columns as &$column)
		{
			if ($column['id'] === $item['columnId'])
			{
				$column['total'] = ((int)$column['total'] + 1);
			}
		}

		return $columns;
	}

	private function subtractFromColumnTotal(array $columns, array $item): array
	{
		foreach ($columns as &$column)
		{
			if ($column['id'] === $item['columnId'])
			{
				$column['total'] = ((int)$column['total'] - 1);
			}
		}

		return $columns;
	}

	private function getStoryPoints(int $parentItemId, array $items, array $subTaskItems): string
	{
		$itemsStoryPoints = [];

		foreach ($subTaskItems as $subTaskItem)
		{
			if ($subTaskItem['parentId'] == $parentItemId)
			{
				$itemsStoryPoints[] = $subTaskItem['data']['storyPoints'];
			}
		}

		$storyPointsService = new StoryPoints();

		$sum = $storyPointsService->calculateSumStoryPoints($itemsStoryPoints);

		return $sum === 0.0 ? '' : (string)$sum;
	}
}