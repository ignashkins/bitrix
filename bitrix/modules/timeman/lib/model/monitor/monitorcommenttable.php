<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\TextField;

Loc::loadMessages(__FILE__);

/**
 * Class MonitorCommentTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_LOG_ID int mandatory
 * <li> USER_ID int mandatory
 * <li> COMMENT text optional
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 **/

class MonitorCommentTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_comment';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('MONITOR_COMMENT_ENTITY_ID_FIELD')
				]
			),
			new IntegerField(
				'USER_LOG_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_COMMENT_ENTITY_USER_LOG_ID_FIELD')
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_COMMENT_ENTITY_USER_ID_FIELD')
				]
			),
			new TextField(
				'COMMENT',
				[
					'title' => Loc::getMessage('MONITOR_COMMENT_ENTITY_COMMENT_FIELD')
				]
			),
		];
	}
}