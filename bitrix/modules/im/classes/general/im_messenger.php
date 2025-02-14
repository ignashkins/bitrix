<?
IncludeModuleLangFile(__FILE__);

class CIMMessenger
{
	private $user_id = 0;
	private static $enableMessageCheck = 1;

	const MESSAGE_LIMIT = 20000;

	function __construct($user_id = false)
	{
		global $USER;
		$this->user_id = intval($user_id);
		if ($this->user_id == 0)
			$this->user_id = intval($USER->GetID());
	}

	/*
		$arParams keys:
		---------------
		MESSAGE_TYPE: P - private chat, G - group chat, S - notification
		TO_USER_ID
		FROM_USER_ID
		MESSAGE
		AUTHOR_ID
		EMAIL_TEMPLATE
		NOTIFY_TYPE: 1 - confirm, 2 - notify single from, 4 - notify single
		NOTIFY_MODULE: module id sender (ex: xmpp, main, etc)
		NOTIFY_EVENT: module event id for search (ex, IM_GROUP_INVITE)
		NOTIFY_TITLE: notify title to send email
		NOTIFY_BUTTONS: array of buttons - available with NOTIFY_TYPE = 1
			Array(
				Array('TITLE' => 'OK', 'VALUE' => 'Y', 'TYPE' => 'accept', 'URL' => '/test.php?CONFIRM=Y'),
				Array('TITLE' => 'Cancel', 'VALUE' => 'N', 'TYPE' => 'cancel', 'URL' => '/test.php?CONFIRM=N'),
			)
		NOTIFY_TAG: field for group in JS notification and search in table
		NOTIFY_SUB_TAG: second TAG for search in table
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (isset($arFields['DIALOG_ID']) && !empty($arFields['DIALOG_ID']))
		{
			if (\Bitrix\Im\Common::isChatId($arFields['DIALOG_ID']))
			{
				$arFields['TO_CHAT_ID'] = mb_substr($arFields['DIALOG_ID'], 4);
				if (!isset($arFields['MESSAGE_TYPE']))
				{
					$arFields['MESSAGE_TYPE'] = IM_MESSAGE_CHAT;
				}
			}
			else
			{
				$arFields['TO_USER_ID'] = intval($arFields['DIALOG_ID']);
				$arFields['MESSAGE_TYPE'] = IM_MESSAGE_PRIVATE;
			}
		}

		if (isset($arFields['TITLE']) && !isset($arFields['NOTIFY_TITLE']))
			$arFields['NOTIFY_TITLE'] = mb_substr($arFields['TITLE'], 0, 255);

		if (isset($arFields['NOTIFY_MESSAGE']) && !isset($arFields['MESSAGE']))
			$arFields['MESSAGE'] = $arFields['NOTIFY_MESSAGE'];

		if (isset($arFields['NOTIFY_MESSAGE_OUT']) && !isset($arFields['MESSAGE_OUT']))
			$arFields['MESSAGE_OUT'] = $arFields['NOTIFY_MESSAGE_OUT'];

		if (isset($arFields['MESSAGE']))
		{
			$arFields['MESSAGE'] = trim(str_replace(Array('[BR]', '[br]'), "\n", $arFields['MESSAGE']));
			if (mb_strlen($arFields['MESSAGE']) > self::MESSAGE_LIMIT + 6)
			{
				$arFields['MESSAGE'] = mb_substr($arFields['MESSAGE'], 0, self::MESSAGE_LIMIT).' (...)';
			}
		}

		$arFields['MESSAGE_OUT'] = isset($arFields['MESSAGE_OUT'])? trim($arFields['MESSAGE_OUT']): "";

		$arFields['URL_PREVIEW'] = isset($arFields['URL_PREVIEW']) && $arFields['URL_PREVIEW'] == 'N'? 'N': 'Y';

		$bConvert = false;
		if (isset($arFields['CONVERT']) && $arFields['CONVERT'] == 'Y')
			$bConvert = true;

		if (!isset($arFields['PARAMS']) || !is_array($arFields['PARAMS']))
		{
			$arFields['PARAMS'] = Array();
		}
		if (!isset($arFields['EXTRA_PARAMS']))
		{
			$arFields['EXTRA_PARAMS'] = Array();
		}

		$incrementCounter = true;
		/*
		if (isset($arFields['INCREMENT_COUNTER']) && $arFields['INCREMENT_COUNTER'] != 'Y')
		{
			if ($arFields['INCREMENT_COUNTER'] == 'N')
			{
				$incrementCounter = Array();
				$arFields['PARAMS']['NOTIFY'] = 'N';
			}
			else if (is_array($arFields['INCREMENT_COUNTER']))
			{
				$incrementCounter = array_values($arFields['INCREMENT_COUNTER']);
				$arFields['PARAMS']['NOTIFY'] = empty($incrementCounter)? 'N': $incrementCounter;
			}
		}
		*/

		if (!isset($arFields['MESSAGE_TYPE']))
			$arFields['MESSAGE_TYPE'] = "";

		if (!isset($arFields['NOTIFY_MODULE']))
			$arFields['NOTIFY_MODULE'] = 'im';

		if (!isset($arFields['NOTIFY_EVENT']))
			$arFields['NOTIFY_EVENT'] = 'default';

		if (isset($arFields['ATTACH']) || isset($arFields['PARAMS']['ATTACH']))
		{
			$attach = isset($arFields['ATTACH'])? $arFields['ATTACH']: $arFields['PARAMS']['ATTACH'];
			if (is_object($attach))
			{
				$arFields['PARAMS']['ATTACH'] = Array($attach);
			}
			else if (is_array($attach))
			{
				$arFields['PARAMS']['ATTACH'] = $attach;
			}
			else
			{
				$arFields['PARAMS']['ATTACH'] = Array();
			}
		}
		if (isset($arFields['FILES']))
		{
			if (is_array($arFields['FILES']))
			{
				$arFields['PARAMS']['FILE_ID'] = $arFields['FILES'];
			}
			else
			{
				$arFields['PARAMS']['FILE_ID'] = Array();
			}
		}
		if (isset($arFields['KEYBOARD']) || isset($arFields['PARAMS']['KEYBOARD']))
		{
			$keyboard = isset($arFields['KEYBOARD'])? $arFields['KEYBOARD']: $arFields['PARAMS']['KEYBOARD'];
			if (is_object($keyboard))
			{
				$arFields['PARAMS']['KEYBOARD'] = $keyboard;
			}
			else
			{
				$arFields['PARAMS']['KEYBOARD'] = Array();
			}
		}
		if (isset($arFields['MENU']) || isset($arFields['PARAMS']['MENU']))
		{
			$menu = isset($arFields['MENU'])? $arFields['MENU']: $arFields['PARAMS']['MENU'];
			if (is_object($menu))
			{
				$arFields['PARAMS']['MENU'] = $menu;
			}
			else
			{
				$arFields['PARAMS']['MENU'] = Array();
			}
		}

		if (isset($arFields['FOR_USER_ID'])) // TODO create this feature in future
		{
			$arFields['PARAMS']['FOR_USER_ID'] = $arFields['FOR_USER_ID'];
		}

		$arFields['RECENT_ADD'] = isset($arFields['RECENT_ADD']) && $arFields['RECENT_ADD'] == 'N'? 'N': 'Y';
		$arFields['SKIP_COMMAND'] = isset($arFields['SKIP_COMMAND']) && $arFields['SKIP_COMMAND'] == 'Y'? 'Y': 'N';
		$arFields['SKIP_CONNECTOR'] = isset($arFields['SKIP_CONNECTOR']) && $arFields['SKIP_CONNECTOR'] == 'Y'? 'Y': 'N';
		$arFields['IMPORTANT_CONNECTOR'] = isset($arFields['IMPORTANT_CONNECTOR']) && $arFields['IMPORTANT_CONNECTOR'] == 'Y'? 'Y': 'N';
		$arFields['SILENT_CONNECTOR'] = isset($arFields['SILENT_CONNECTOR']) && $arFields['SILENT_CONNECTOR'] == 'Y'? 'Y': 'N';
		if ($arFields['SILENT_CONNECTOR'] == 'Y')
		{
			$arFields['PARAMS']['CLASS'] = "bx-messenger-content-item-system";
		}

		$arFields['URL_ATTACH'] = Array();
		if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_SYSTEM)
		{
			if (!isset($arFields['NOTIFY_TYPE']) && intval($arFields['FROM_USER_ID']) > 0)
				$arFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
			else if (!isset($arFields['NOTIFY_TYPE']))
				$arFields['NOTIFY_TYPE'] = IM_NOTIFY_SYSTEM;

			if (isset($arFields['NOTIFY_ANSWER']) && $arFields['NOTIFY_ANSWER'] == 'Y')
				$arFields['PARAMS']['CAN_ANSWER'] = 'Y';

			/*
			$urlPrepare = self::PrepareUrl($arFields['MESSAGE']);
			if ($urlPrepare['RESULT'])
			{
				if (empty($arFields['MESSAGE_OUT']))
				{
					$arFields['MESSAGE_OUT'] = $arFields['MESSAGE'];
				}
				$arFields['MESSAGE'] = $urlPrepare['MESSAGE'];
				$arFields['PARAMS']['ATTACH'] = array_merge($arFields['PARAMS']['ATTACH'], $urlPrepare['ATTACH']);
			}
			*/
		}
		else if ($arFields['URL_PREVIEW'] == 'Y')
		{
			$link = new CIMMessageLink();
			$urlPrepare = $link->prepareInsert($arFields['MESSAGE']);
			if ($urlPrepare['RESULT'])
			{
				if (empty($arFields['MESSAGE_OUT']))
				{
					$arFields['MESSAGE_OUT'] = $arFields['MESSAGE'];
				}
				$arFields['MESSAGE'] = $urlPrepare['MESSAGE'];

				if (isset($arFields['PARAMS']['URL_ID']))
				{
					$arFields['PARAMS']['URL_ID'] = array_merge($arFields['PARAMS']['URL_ID'], $urlPrepare['URL_ID']);
				}
				else
				{
					$arFields['PARAMS']['URL_ID'] = $urlPrepare['URL_ID'];
				}
				$arFields['URL_ATTACH'] = $urlPrepare['ATTACH'];

				if ($urlPrepare['MESSAGE_IS_LINK'])
				{
					$arFields['PARAMS']['URL_ONLY'] = 'Y';
				}
			}
		}

		if (isset($arFields['NOTIFY_EMAIL_TEMPLATE']) && !isset($arFields['EMAIL_TEMPLATE']))
			$arFields['EMAIL_TEMPLATE'] = $arFields['NOTIFY_EMAIL_TEMPLATE'];

		if (!isset($arFields['AUTHOR_ID']))
			$arFields['AUTHOR_ID'] = intval($arFields['FROM_USER_ID']);

		foreach(GetModuleEvents("im", "OnBeforeMessageNotifyAdd", true) as $arEvent)
		{
			$result = ExecuteModuleEventEx($arEvent, array(&$arFields));
			if($result===false || isset($result['result']) && $result['result'] === false)
			{
				$reason = self::GetReasonForMessageSendError($arFields['MESSAGE_TYPE'], $result['reason']);
				$GLOBALS["APPLICATION"]->ThrowException($reason, "ERROR_FROM_OTHER_MODULE");
				return false;
			}
		}
		if (!self::CheckFields($arFields))
		{
			return false;
		}

		if ($arFields['MESSAGE_TYPE'] != IM_MESSAGE_SYSTEM)
		{
			if ($arFields['URL_PREVIEW'] == 'Y')
			{
				$results = \Bitrix\Im\Text::getDateConverterParams($arFields['MESSAGE']);
				foreach ($results as $result)
				{
					$arFields['PARAMS']['DATE_TEXT'][] = $result->getText();
					$arFields['PARAMS']['DATE_TS'][] = $result->getDate()->getTimestamp();
				}
			}

			if (\Bitrix\Im\Text::isOnlyEmoji($arFields['MESSAGE']))
			{
				$arFields['PARAMS']['LARGE_FONT'] = 'Y';
			}
		}


		if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$isSelfChat = false;
			if (isset($arFields['TO_CHAT_ID']))
			{
				$chatId = $arFields['TO_CHAT_ID'];
				$relations = CIMChat::GetRelationById($chatId);

				$arFields['TO_USER_ID'] = $arFields['FROM_USER_ID'];
				foreach ($relations as $rel)
				{
					if (
						$arFields['TO_USER_ID']
						&& $rel['USER_ID'] == $arFields['FROM_USER_ID']
					)
					{
						continue;
					}

					$arFields['TO_USER_ID'] = $rel['USER_ID'];
				}
				if ($arFields['FROM_USER_ID'] == $arFields['TO_USER_ID'])
				{
					$isSelfChat = true;
				}

				if (!IsModuleInstalled('intranet'))
				{
					if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage('IM_ERROR_MESSAGE_PRIVACY_SELF'), "ERROR_FROM_PRIVACY_SELF");
						return false;
					}
					else if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE, $arFields['TO_USER_ID']) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage('IM_ERROR_MESSAGE_PRIVACY'), "ERROR_FROM_PRIVACY");
						return false;
					}
				}
			}
			else
			{
				$arFields['FROM_USER_ID'] = intval($arFields['FROM_USER_ID']);
				$arFields['TO_USER_ID'] = intval($arFields['TO_USER_ID']);

				if (!IsModuleInstalled('intranet'))
				{
					if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage('IM_ERROR_MESSAGE_PRIVACY_SELF'), "ERROR_FROM_PRIVACY_SELF");
						return false;
					}
					else if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE, $arFields['TO_USER_ID']) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage('IM_ERROR_MESSAGE_PRIVACY'), "ERROR_FROM_PRIVACY");
						return false;
					}
				}
				$chatId = CIMMessage::GetChatId($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']);
				if ($arFields['FROM_USER_ID'] == $arFields['TO_USER_ID'])
				{
					$isSelfChat = true;
				}
			}

			if ($chatId > 0)
			{
				$chatData = \Bitrix\Im\Model\ChatTable::getById($chatId)->fetch();
				$prevMessageId = intval($chatData['PREV_MESSAGE_ID']);

				$arFields['CHAT_ID'] = $chatId;
				$arFields = self::UploadFileFromText($arFields);

				$arParams = Array();
				$arParams['CHAT_ID'] = $chatId;
				$arParams['AUTHOR_ID'] = intval($arFields['AUTHOR_ID']);
				$arParams['MESSAGE'] = $arFields['MESSAGE'];
				$arParams['MESSAGE_OUT'] = $arFields['MESSAGE_OUT'];
				$arParams['NOTIFY_MODULE'] = $arFields['NOTIFY_MODULE'];
				$arParams['NOTIFY_EVENT'] = $arFields['SYSTEM'] == 'Y'? 'private_system': 'private';

				if (isset($arFields['IMPORT_ID']))
					$arParams['IMPORT_ID'] = intval($arFields['IMPORT_ID']);

				if (isset($arFields['MESSAGE_DATE']))
				{
					$arParams['DATE_CREATE'] = new Bitrix\Main\Type\DateTime($arFields['MESSAGE_DATE']);
				}
				$arFiles = Array();
				$arFields['FILES'] = Array();
				if (isset($arFields['PARAMS']['FILE_ID']))
				{
					foreach ($arFields['PARAMS']['FILE_ID'] as $fileId)
					{
						$arFiles[$fileId] = $fileId;
					}
				}
				$arFields['FILES'] = CIMDisk::GetFiles($chatId, $arFiles, false);

				$messageFiles = self::GetFormatFilesMessageOut($arFields['FILES']);
				if ($messageFiles <> '')
				{
					$arParams['MESSAGE_OUT'] = $arParams['MESSAGE_OUT'] <> ''? $arParams['MESSAGE_OUT']."\n".$messageFiles: $messageFiles;
					$arFields['MESSAGE_OUT'] = $arParams['MESSAGE_OUT'];
				}

				$result = \Bitrix\Im\Model\MessageTable::add($arParams);
				$messageID = intval($result->getId());
				if ($messageID <= 0)
					return false;

				\Bitrix\Im\Model\ChatTable::update($chatId, Array(
					'MESSAGE_COUNT' => new \Bitrix\Main\DB\SqlExpression('?# + 1', 'MESSAGE_COUNT'),
					'PREV_MESSAGE_ID' => new \Bitrix\Main\DB\SqlExpression('?#', 'LAST_MESSAGE_ID'),
					'LAST_MESSAGE_ID' => $messageID,
					'LAST_MESSAGE_STATUS' => IM_MESSAGE_STATUS_RECEIVED
				));

				if ($chatData['PARENT_MID'])
				{
					CIMMessageParam::set($chatData['PARENT_MID'], Array(
						'CHAT_MESSAGE' => $chatData['MESSAGE_COUNT']+1,
						'CHAT_LAST_DATE' => new \Bitrix\Main\Type\DateTime()
					));
					CIMMessageParam::SendPull($chatData['PARENT_MID'], Array('CHAT_MESSAGE', 'CHAT_LAST_DATE'));
				}

				if (empty($arFields['PARAMS']))
				{
					CIMMessageParam::UpdateTimestamp($messageID, $arParams['CHAT_ID']);
				}
				else
				{
					CIMMessageParam::Set($messageID, $arFields['PARAMS']);
				}

				if (!empty($arFields['URL_ATTACH']))
				{
					if (isset($arFields['PARAMS']['ATTACH']))
					{
						$arFields['PARAMS']['ATTACH'] = array_merge($arFields['PARAMS']['ATTACH'], $arFields['URL_ATTACH']);
					}
					else
					{
						$arFields['PARAMS']['ATTACH'] = $arFields['URL_ATTACH'];
					}
				}

				$relations = CIMChat::GetRelationById($chatId);
				foreach ($relations as $relation)
				{
					if (\Bitrix\Im\User::getInstance($relation['USER_ID'])->isBot())
					{
						// bot
					}
					else if (!\Bitrix\Im\User::getInstance($relation['USER_ID'])->isActive())
					{
						continue;
					}

					if (isset($arFields['RECENT_SKIP_AUTHOR']) && $relation['USER_ID'] == $arParams['AUTHOR_ID'])
					{
						continue;
					}

					$addToRecent = true;
					if ($incrementCounter !== true && !in_array($relation['USER_ID'], $incrementCounter))
					{
						$addToRecent = \CIMContactList::InRecent($relation['USER_ID'], $arFields['MESSAGE_TYPE'], $relation['CHAT_ID']);
					}
					if ($addToRecent)
					{
						CIMContactList::SetRecent(Array(
							'ENTITY_ID' => $relation['USER_ID'] == $arFields['TO_USER_ID']? $arFields['FROM_USER_ID']: $arFields['TO_USER_ID'],
							'MESSAGE_ID' => $messageID,
							'CHAT_TYPE' => IM_MESSAGE_PRIVATE,
							'CHAT_ID' => $relation['CHAT_ID'],
							'RELATION_ID' => $relation['ID'],
							'USER_ID' => $relation['USER_ID']
						));
					}
				}

				if (!$bConvert)
				{
					$pullIncluded = CModule::IncludeModule("pull");
					$pullServerActive = $pullIncluded && CPullOptions::GetNginxStatus();
					$relations = \Bitrix\Im\Chat::getRelation($chatId, Array(
						'REAL_COUNTERS' => 'Y',
						'USER_DATA' => 'Y',
					));
					foreach ($relations as $id => $relation)
					{
						if (\Bitrix\Im\User::getInstance($relation["USER_ID"])->isBot())
						{
							// bot
						}
						else if ($relation['USER_DATA']['ACTIVE'] == 'N')
						{
							continue;
						}

						//self chat OR current user is author + no unread messages before
						if ($isSelfChat || ($relation["USER_ID"] == $arFields["FROM_USER_ID"] && $relation["PREVIOUS_COUNTER"] === 0))
						{
							$relations[$id]['COUNTER'] = 0;
							$relationUpdate = array(
								"STATUS" => IM_STATUS_READ,
								"MESSAGE_STATUS" => IM_MESSAGE_STATUS_RECEIVED,
								"UNREAD_ID" => $messageID,
								"COUNTER" => 0,
								"LAST_ID" => $messageID,
								"LAST_SEND_ID" => $messageID,
								"LAST_READ" => new Bitrix\Main\Type\DateTime(),
							);
							if (!$pullServerActive)
							{
								unset($relationUpdate['STATUS']);
								unset($relationUpdate['LAST_ID']);
							}
							\Bitrix\Im\Model\RelationTable::update($relation["ID"], $relationUpdate);
						}
						else
						{
							$updateRelation = array(
								"STATUS" => IM_STATUS_UNREAD,
								"MESSAGE_STATUS" => IM_MESSAGE_STATUS_RECEIVED,
								"UNREAD_ID" => $messageID,
								"COUNTER" => $relation['COUNTER'],
							);
							if ($relation["UNREAD_ID"])
							{
								unset($updateRelation['UNREAD_ID']);
							}
							if ($incrementCounter !== true && !in_array($relation['USER_ID'], $incrementCounter))
							{
								unset($updateRelation['COUNTER']);
							}

							\Bitrix\Im\Model\RelationTable::update($relation["ID"], $updateRelation);
						}

						\Bitrix\Im\Counter::clearCache($relation['USER_ID']);
					}

					if (CModule::IncludeModule("pull"))
					{
						$arParams['FROM_USER_ID'] = $arFields['FROM_USER_ID'];
						$arParams['TO_USER_ID'] = $arFields['TO_USER_ID'];

						$pullMessage = Array(
							'module_id' => 'im',
							'command' => 'message',
							'params' => CIMMessage::GetFormatMessage(Array(
								'ID' => $messageID,
								'TEMPLATE_ID' => $arFields['TEMPLATE_ID'],
								'FILE_TEMPLATE_ID' => $arFields['FILE_TEMPLATE_ID'],
								'PREV_ID' => $prevMessageId,
								'CHAT_ID' => $chatId,
								'TO_USER_ID' => $arParams['TO_USER_ID'],
								'FROM_USER_ID' => $arParams['FROM_USER_ID'],
								'SYSTEM' => $arFields['SYSTEM'] == 'Y'? 'Y': 'N',
								'MESSAGE' => $arParams['MESSAGE'],
								'DATE_CREATE' => time(),
								'PARAMS' => self::PrepareParamsForPull($arFields['PARAMS']),
								'FILES' => $arFields['FILES'],
								'NOTIFY' => $incrementCounter
							)),
							'extra' => \Bitrix\Im\Common::getPullExtra()
						);

						$pullMessageTo = $pullMessage;
						$pullMessageTo['params']['dialogId'] = $arParams['FROM_USER_ID'];

						$pullMessageFrom = $pullMessage;
						$pullMessageFrom['params']['dialogId'] = $arParams['TO_USER_ID'];

						$pullMessageFrom['params']['counter'] = $relations[$arParams['FROM_USER_ID']]['COUNTER'];
						\Bitrix\Pull\Event::add($arParams['FROM_USER_ID'], $pullMessageFrom);

						if ($arParams['FROM_USER_ID'] != $arParams['TO_USER_ID'])
						{
							$pullMessageTo['params']['counter'] = $relations[$arParams['TO_USER_ID']]['COUNTER'];
							\Bitrix\Pull\Event::add($arParams['TO_USER_ID'], $pullMessageTo);

							$pullMessageTo['params']['message']['text_push'] = $arParams['MESSAGE'];
							$pullMessageTo = self::PreparePushForPrivate($pullMessageTo);
							$pullMessageFrom = self::PreparePushForPrivate($pullMessageFrom);

							if ($arFields['PUSH'] != 'N')
							{
								if (isset($arFields['MESSAGE_PUSH']))
								{
									$pullMessageTo['push']['message'] = $arFields['MESSAGE_PUSH'];
									$pullMessageTo['push']['advanced_params']['senderMessage'] = $arFields['MESSAGE_PUSH'];
									$pullMessageFrom['push']['message'] = $arFields['MESSAGE_PUSH'];
									$pullMessageFrom['push']['advanced_params']['senderMessage'] = $arFields['MESSAGE_PUSH'];
								}

								$pullMessageTo['push']['advanced_params']['counter'] = $relations[$arParams['TO_USER_ID']]['COUNTER'];
								\Bitrix\Pull\Push::add($arParams['TO_USER_ID'], $pullMessageTo);

								$pullMessageFrom['push']['advanced_params']['counter'] = $relations[$arParams['FROM_USER_ID']]['COUNTER'];
								\Bitrix\Pull\Push::add($arParams['FROM_USER_ID'], array_merge_recursive($pullMessageFrom, ['push' => [
									'skip_users' => [$arParams['FROM_USER_ID']],
									'advanced_params' => [
										"notificationsToCancel" => ['IM_MESS'],
									],
									'send_immediately' => 'Y',
								]]));
							}
						}
					}

					foreach(GetModuleEvents("im", "OnAfterMessagesAdd", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, array(intval($messageID), $arFields));

					$arFields['COMMAND_CONTEXT'] = 'TEXTAREA';
					$result = \Bitrix\Im\Command::onCommandAdd(intval($messageID), $arFields);
					if (!$result)
					{
						\Bitrix\Im\Bot::onMessageAdd(intval($messageID), $arFields);
					}
				}

				\Bitrix\Im\Model\MessageTable::indexRecord($messageID);

				return $messageID;
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
				return false;
			}
		}
		else if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_CHAT || $arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN || $arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN_LINE)
		{
			$arFields['SKIP_USER_CHECK'] = isset($arFields['SKIP_USER_CHECK']) && $arFields['SKIP_USER_CHECK'] == 'Y'? 'Y': 'N';
			$arFields['FROM_USER_ID'] = intval($arFields['FROM_USER_ID']);
			$chatId = 0;
			$systemMessage = false;
			if (isset($arFields['SYSTEM']) && $arFields['SYSTEM'] == 'Y')
			{
				$strSql = "
					SELECT
						C.ID CHAT_ID,
						C.PARENT_ID CHAT_PARENT_ID,
						C.PARENT_MID CHAT_PARENT_MID,
						C.TITLE CHAT_TITLE,
						C.AUTHOR_ID CHAT_AUTHOR_ID,
						C.TYPE CHAT_TYPE,
						C.AVATAR CHAT_AVATAR,
						C.COLOR CHAT_COLOR,
						C.ENTITY_TYPE CHAT_ENTITY_TYPE,
						C.ENTITY_ID CHAT_ENTITY_ID,
						C.ENTITY_DATA_1 CHAT_ENTITY_DATA_1,
						C.ENTITY_DATA_2 CHAT_ENTITY_DATA_2,
						C.ENTITY_DATA_3 CHAT_ENTITY_DATA_3,
						C.EXTRANET CHAT_EXTRANET,
						C.PREV_MESSAGE_ID CHAT_PREV_MESSAGE_ID,
						'1' RID,
						'Y' IS_MANAGER
					FROM b_im_chat C
					WHERE C.ID = ".intval($arFields['TO_CHAT_ID'])."
				";
				$systemMessage = true;
			}
			else
			{
				$strSql = "
					SELECT
						C.ID CHAT_ID,
						C.PARENT_ID CHAT_PARENT_ID,
						C.PARENT_MID CHAT_PARENT_MID,
						C.TITLE CHAT_TITLE,
						C.AUTHOR_ID CHAT_AUTHOR_ID,
						C.TYPE CHAT_TYPE,
						C.AVATAR CHAT_AVATAR,
						C.COLOR CHAT_COLOR,
						C.ENTITY_TYPE CHAT_ENTITY_TYPE,
						C.ENTITY_ID CHAT_ENTITY_ID,
						C.ENTITY_DATA_1 CHAT_ENTITY_DATA_1,
						C.ENTITY_DATA_2 CHAT_ENTITY_DATA_2,
						C.ENTITY_DATA_3 CHAT_ENTITY_DATA_3,
						C.EXTRANET CHAT_EXTRANET,
						C.PREV_MESSAGE_ID CHAT_PREV_MESSAGE_ID,
						R.USER_ID RID,
						R.MANAGER IS_MANAGER
					FROM b_im_chat C
					LEFT JOIN b_im_relation R ON R.CHAT_ID = C.ID AND R.USER_ID = ".$arFields['FROM_USER_ID']."
					WHERE C.ID = ".intval($arFields['TO_CHAT_ID'])."
				";
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				$chatId = intval($arRes['CHAT_ID']);
				$chatTitle = htmlspecialcharsbx(\Bitrix\Im\Text::decodeEmoji($arRes['CHAT_TITLE']));
				$chatAuthorId = intval($arRes['CHAT_AUTHOR_ID']);
				$chatParentId = intval($arRes['CHAT_PARENT_ID']);
				$chatParentMid = intval($arRes['CHAT_PARENT_MID']);
				$chatExtranet = $arRes['CHAT_EXTRANET'] == 'Y';
				$arRes['CHAT_TYPE'] = trim($arRes['CHAT_TYPE']);
				$arFields['MESSAGE_TYPE'] = $arRes['CHAT_TYPE'];
				$prevMessageId = intval($arRes['CHAT_PREV_MESSAGE_ID']);
				$importantPush = $arRes['CHAT_ENTITY_TYPE'] == 'ANNOUNCEMENT';

				if ($arFields['SKIP_USER_CHECK'] == 'N')
				{
					if ($arRes['CHAT_ENTITY_TYPE'] == 'ANNOUNCEMENT' && $arRes['IS_MANAGER'] !== 'Y')
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_GROUP_CANCELED"), "CANCELED");
						return false;
					}

					if ($arRes['CHAT_TYPE'] == IM_MESSAGE_OPEN)
					{
						if (!CIMMessenger::CheckEnableOpenChat())
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_GROUP_CANCELED"), "CANCELED");
							return false;
						}
						else if (intval($arRes['RID']) <= 0)
						{
							if (\Bitrix\Im\User::getInstance($arFields['FROM_USER_ID'])->isExtranet())
							{
								$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_GROUP_CANCELED"), "CANCELED");
								return false;
							}
							else
							{
								$chat = new CIMChat(0);
								$chat->AddUser($chatId, $arFields['FROM_USER_ID']);
							}
						}
					}
					else if (intval($arRes['RID']) <= 0)
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_GROUP_CANCELED"), "CANCELED");
						return false;
					}
				}
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_GROUP_CANCELED"), "CANCELED");
				return false;
			}

			if ($chatId > 0)
			{
				foreach(GetModuleEvents("im", "OnBeforeChatMessageAdd", true) as $arEvent)
				{
					$result = ExecuteModuleEventEx($arEvent, array($arFields, $arRes));
					if($result===false || isset($result['result']) && $result['result'] === false)
					{
						$reason = self::GetReasonForMessageSendError($arFields['MESSAGE_TYPE'], $result['reason']);
						$GLOBALS["APPLICATION"]->ThrowException($reason, "ERROR_FROM_OTHER_MODULE");
						return false;
					}
					if (isset($result['fields']))
					{
						$arFields = $result['fields'];
					}
				}

				$chatId = intval($arRes['CHAT_ID']);
				$arFields['CHAT_ID'] = $chatId;
				$arFields = self::UploadFileFromText($arFields);

				$arParams = Array();
				$arParams['CHAT_ID'] = $chatId;
				$arParams['AUTHOR_ID'] = $systemMessage? 0: intval($arFields['AUTHOR_ID']);
				$arParams['MESSAGE'] = $arFields['MESSAGE'];
				$arParams['MESSAGE_OUT'] = $arFields['MESSAGE_OUT'];
				$arParams['NOTIFY_MODULE'] = 'im';
				$arParams['NOTIFY_EVENT'] = 'group';

				if (isset($arFields['MESSAGE_DATE']))
				{
					$arParams['DATE_CREATE'] = new Bitrix\Main\Type\DateTime($arFields['MESSAGE_DATE']);
				}

				$arFiles = Array();
				$arFields['FILES'] = Array();

				if (isset($arFields['PARAMS']['FILE_ID']))
				{
					foreach ($arFields['PARAMS']['FILE_ID'] as $fileId)
					{
						$arFiles[$fileId] = $fileId;
					}
				}
				$arFields['FILES'] = CIMDisk::GetFiles($chatId, $arFiles, false);
				$messageFiles = self::GetFormatFilesMessageOut($arFields['FILES']);
				if ($messageFiles <> '')
				{
					$arParams['MESSAGE_OUT'] = $arParams['MESSAGE_OUT'] <> ''? $arParams['MESSAGE_OUT']."\n".$messageFiles: $messageFiles;
					$arFields['MESSAGE_OUT'] = $arParams['MESSAGE_OUT'];
				}

				$result = \Bitrix\Im\Model\MessageTable::add($arParams);
				$messageID = intval($result->getId());
				if ($messageID <= 0)
					return false;

				\Bitrix\Im\Model\ChatTable::update($chatId, Array(
					'MESSAGE_COUNT' => new \Bitrix\Main\DB\SqlExpression('?# + 1', 'MESSAGE_COUNT'),
					'PREV_MESSAGE_ID' => new \Bitrix\Main\DB\SqlExpression('?#', 'LAST_MESSAGE_ID'),
					'LAST_MESSAGE_ID' => $messageID,
					'LAST_MESSAGE_STATUS' => IM_MESSAGE_STATUS_RECEIVED
				));

				if ($chatParentMid)
				{
					$chatData = \Bitrix\Im\Model\ChatTable::getById($chatId)->fetch();
					CIMMessageParam::set($chatParentMid, Array(
						'CHAT_MESSAGE' => $chatData['MESSAGE_COUNT'],
						'CHAT_LAST_DATE' => new \Bitrix\Main\Type\DateTime()
					));
					CIMMessageParam::SendPull($chatParentMid, Array('CHAT_MESSAGE', 'CHAT_LAST_DATE'));
				}

				if (empty($arFields['PARAMS']))
				{
					CIMMessageParam::UpdateTimestamp($messageID, $arParams['CHAT_ID']);
				}
				else
				{
					CIMMessageParam::Set($messageID, $arFields['PARAMS']);
				}

				if (!empty($arFields['URL_ATTACH']))
				{
					if (isset($arFields['PARAMS']['ATTACH']))
					{
						$arFields['PARAMS']['ATTACH'] = array_merge($arFields['PARAMS']['ATTACH'], $arFields['URL_ATTACH']);
					}
					else
					{
						$arFields['PARAMS']['ATTACH'] = $arFields['URL_ATTACH'];
					}
				}

				$arParams['FROM_USER_ID'] = $arFields['FROM_USER_ID'];
				$arParams['TO_CHAT_ID'] = $arFields['TO_CHAT_ID'];

				$arBotInChat = Array();
				$relations = \Bitrix\Im\Chat::getRelation($chatId, Array(
					'REAL_COUNTERS' => 'Y',
					'USER_DATA' => 'Y',
					'SKIP_CONNECTOR' => $arRes['CHAT_ENTITY_TYPE'] == 'LINES'? 'Y': 'N'
				));

				$pullIncluded = CModule::IncludeModule("pull");
				$pullServerActive = $pullIncluded && CPullOptions::GetNginxStatus();
				$events = array();

				$skippedRelations = Array();

				$pushUserSkip = Array();
				$pushUserSend = Array();

				foreach ($relations as $id => $relation)
				{
					if ($arFields['RECENT_ADD'] != 'Y')
					{
						$skippedRelations[$id] = true;
						continue;
					}
					if ($relation['USER_DATA']["EXTERNAL_AUTH_ID"] == \Bitrix\Im\Bot::EXTERNAL_AUTH_ID)
					{
						$arBotInChat[$relation["USER_ID"]] = $relation["USER_ID"];
					}
					else if ($relation['USER_DATA']['ACTIVE'] == 'N')
					{
						$skippedRelations[$id] = true;
						continue;
					}

					$sessionId = 0;
					if ($arRes['CHAT_ENTITY_TYPE'] == "LINES")
					{
						if ($relation['USER_DATA']["EXTERNAL_AUTH_ID"] == 'imconnector')
						{
							$skippedRelations[$id] = true;
							continue;
						}
						if ($arRes['CHAT_ENTITY_DATA_1'])
						{
							$fieldData = explode("|", $arRes['CHAT_ENTITY_DATA_1']);
							$sessionId = intval($fieldData[5]);
						}
					}

					$addToRecent = true;
					if ($incrementCounter !== true && !in_array($relation['USER_ID'], $incrementCounter))
					{
						$addToRecent = \CIMContactList::InRecent($relation['USER_ID'], $arFields['MESSAGE_TYPE'], $relation['CHAT_ID']);
					}

					if ($addToRecent)
					{
						CIMContactList::SetRecent(Array(
							'ENTITY_ID' => $chatId,
							'MESSAGE_ID' => $messageID,
							'CHAT_TYPE' => $arFields['MESSAGE_TYPE'],
							'USER_ID' => $relation['USER_ID'],
							'CHAT_ID' => $relation['CHAT_ID'],
							'RELATION_ID' => $relation['ID'],
							'SESSION_ID' => $sessionId,
						));
					}

					if ($relation["USER_ID"] == $arFields["FROM_USER_ID"] && $relation["PREVIOUS_COUNTER"] === 0)
					{
						$relations[$id]['COUNTER'] = $relation['COUNTER'] = 0;

						$relationUpdate = array(
							"STATUS" => IM_STATUS_READ,
							"MESSAGE_STATUS" => IM_MESSAGE_STATUS_RECEIVED,
							"COUNTER" => 0,
							"UNREAD_ID" => $messageID,
							"LAST_ID" => $messageID,
							"LAST_SEND_ID" => $messageID,
							"LAST_READ" => new Bitrix\Main\Type\DateTime(),
						);
						if (!$pullServerActive)
						{
							unset($relationUpdate['STATUS']);
							unset($relationUpdate['LAST_ID']);
						}
						\Bitrix\Im\Model\RelationTable::update($relation["ID"], $relationUpdate);
					}
					else
					{
						$updateRelation = array(
							"STATUS" => IM_STATUS_UNREAD,
							"UNREAD_ID" => $messageID,
							"MESSAGE_STATUS" => IM_MESSAGE_STATUS_RECEIVED,
							"COUNTER" => $relation['COUNTER'],
						);
						if ($relation["UNREAD_ID"])
						{
							unset($updateRelation['UNREAD_ID']);
						}
						if ($incrementCounter !== true && !in_array($relation['USER_ID'], $incrementCounter))
						{
							unset($updateRelation['COUNTER']);
						}
						\Bitrix\Im\Model\RelationTable::update($relation["ID"], $updateRelation);
					}

					\Bitrix\Im\Counter::clearCache($relation['USER_ID']);

					if (!$pullIncluded)
					{
						continue;
					}

					$sendPush = true;
					if ($relation['USER_ID'] == $arParams['FROM_USER_ID'])
					{
						CPushManager::DeleteFromQueueBySubTag($arParams['FROM_USER_ID'], 'IM_MESS');
						$sendPush = false;
					}
					else if ($relation['NOTIFY_BLOCK'] == 'Y' && !$importantPush)
					{
						$pushUserSkip[] = $relation['USER_ID'];
						$pushUserSend[] = $relation['USER_ID'];
					}
					else if ($arFields['PUSH'] == 'N')
					{
						$sendPush = false;
					}
					else
					{
						$pushUserSend[] = $relation['USER_ID'];
					}
				}

				$pullMessage = Array(
					'module_id' => 'im',
					'command' => 'messageChat',
					'params' => CIMMessage::GetFormatMessage(Array(
						'ID' => $messageID,
						'TEMPLATE_ID' => $arFields['TEMPLATE_ID'],
						'FILE_TEMPLATE_ID' => $arFields['FILE_TEMPLATE_ID'],
						'PREV_ID' => $prevMessageId,
						'CHAT_ID' => $chatId,
						'TO_CHAT_ID' => $arParams['TO_CHAT_ID'],
						'FROM_USER_ID' => $arParams['FROM_USER_ID'],
						'MESSAGE' => $arParams['MESSAGE'],
						'SYSTEM' => $arFields['SYSTEM'] == 'Y'? 'Y': 'N',
						'DATE_CREATE' => time(),
						'PARAMS' => self::PrepareParamsForPull($arFields['PARAMS']),
						'FILES' => $arFields['FILES'],
						'EXTRA_PARAMS' => $arFields['EXTRA_PARAMS'],
						'COUNTER' => -1,
						'NOTIFY' => $incrementCounter
					)),
					'extra' => \Bitrix\Im\Common::getPullExtra()
				);

				foreach ($relations as $id => $relation)
				{
					if ($skippedRelations[$id])
					{
						continue;
					}
					$events[$relation['USER_ID']] = $pullMessage;
					$events[$relation['USER_ID']]['params']['counter'] = $incrementCounter !== true && !in_array($relation['USER_ID'], $incrementCounter)? $relation['PREVIOUS_COUNTER']: $relation['COUNTER'];
					$events[$relation['USER_ID']]['groupId'] = 'im_chat_'.$chatId.'_'.$messageID.'_'.$events[$relation['USER_ID']]['params']['counter'];
				}

				if ($arFields['SYSTEM'] != 'Y')
				{
					self::SendMention(Array(
						'CHAT_ID' => $chatId,
						'CHAT_TITLE' => $chatTitle,
						'CHAT_RELATION' => $relations,
						'CHAT_TYPE' => $arFields['MESSAGE_TYPE'],
						'CHAT_ENTITY_TYPE' => $arRes['CHAT_ENTITY_TYPE'],
						'CHAT_COLOR' => $arRes['CHAT_COLOR'],
						'MESSAGE' => $arParams['MESSAGE'],
						'FILES' => $arFields['FILES'],
						'FROM_USER_ID' => $arParams['FROM_USER_ID'],
					));
				}

				if ($pullIncluded)
				{
					if ($arRes['CHAT_TYPE'] == IM_MESSAGE_OPEN || $arRes['CHAT_TYPE'] == IM_MESSAGE_OPEN_LINE)
					{
						$watchPullMessage = $pullMessage;
						$watchPullMessage['params']['message']['params']['NOTIFY'] = 'N';
						CPullWatch::AddToStack('IM_PUBLIC_'.$chatId, $watchPullMessage);
					}

					$groups = self::GetEventByCounterGroup($events);
					foreach ($groups as $group)
					{
						\Bitrix\Pull\Event::add($group['users'], $group['event']);

						$userList = array_intersect($pushUserSend, $group['users']);
						if (!empty($userList))
						{
							$pushParams = $group['event'];

							$pushParams['params']['message']['text_push'] = $arParams['MESSAGE'];
							$pushParams = self::PreparePushForChat($pushParams);

							if ($importantPush)
							{
								$pushParams['push']['important'] = 'Y';
							}

							$pushParams['skip_users'] = $pushUserSkip;

							if (isset($arFields['MESSAGE_PUSH']))
							{
								$pushParams['push']['message'] = $arFields['MESSAGE_PUSH'];
								$pushParams['push']['advanced_params']['senderMessage'] = $arFields['MESSAGE_PUSH'];
							}
							$pushParams['push']['advanced_params']['counter'] = $group['event']['params']['counter'];

							\Bitrix\Pull\Push::add($userList, $pushParams);
						}
					}
				}

				$arFields['CHAT_AUTHOR_ID'] = $chatAuthorId;
				$arFields['CHAT_ENTITY_TYPE'] = $arRes['CHAT_ENTITY_TYPE'];
				$arFields['CHAT_ENTITY_ID'] = $arRes['CHAT_ENTITY_ID'];
				$arFields['CHAT_ENTITY_DATA_1'] = $arRes['CHAT_ENTITY_DATA_1'];
				$arFields['CHAT_ENTITY_DATA_2'] = $arRes['CHAT_ENTITY_DATA_2'];
				$arFields['CHAT_ENTITY_DATA_3'] = $arRes['CHAT_ENTITY_DATA_3'];
				$arFields['BOT_IN_CHAT'] = $arBotInChat;

				foreach(GetModuleEvents("im", "OnAfterMessagesAdd", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array(intval($messageID), $arFields));

				$arFields['COMMAND_CONTEXT'] = 'TEXTAREA';
				$result = \Bitrix\Im\Command::onCommandAdd(intval($messageID), $arFields);
				if (!$result)
				{
					\Bitrix\Im\Bot::onMessageAdd(intval($messageID), $arFields);
				}

				\Bitrix\Im\Model\MessageTable::indexRecord($messageID);

				return $messageID;
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
				return false;
			}

		}
		else if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_SYSTEM)
		{
			$arFields['TO_USER_ID'] = intval($arFields['TO_USER_ID']);

			$blockedExternalAuthId = \Bitrix\Im\Common::getExternalAuthId(['replica']);
			$orm = \Bitrix\Main\UserTable::getById($arFields['TO_USER_ID']);
			$userData = $orm->fetch();
			if (
				!$userData
				|| $userData['ACTIVE'] == 'N'
				|| in_array($userData['EXTERNAL_AUTH_ID'], $blockedExternalAuthId, true)
			)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "TO_USER_ID");
				return false;
			}

			$strSql = "
				SELECT ID CHAT_ID
				FROM b_im_chat
				WHERE AUTHOR_ID = ".$arFields['TO_USER_ID']." AND TYPE = '".IM_MESSAGE_SYSTEM."'
				ORDER BY ID ASC
			";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				$chatId = intval($arRes['CHAT_ID']);
			}
			else
			{
				$result = \Bitrix\Im\Model\ChatTable::add(Array('TYPE' => IM_MESSAGE_SYSTEM, 'AUTHOR_ID' => $arFields['TO_USER_ID']));
				$chatId = $result->getId();
				if ($chatId <= 0)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
					return false;
				}

				\Bitrix\Im\Model\RelationTable::add(array(
					"CHAT_ID" => $chatId,
					"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
					"USER_ID" => intval($arFields['TO_USER_ID']),
					"STATUS" => ($bConvert? 2: 0),
				));
			}

			if ($chatId > 0)
			{
				$arParams = Array();
				$arParams['CHAT_ID'] = $chatId;
				$arParams['AUTHOR_ID'] = intval($arFields['AUTHOR_ID']);
				$arParams['MESSAGE'] = $arFields['MESSAGE'];
				$arParams['MESSAGE_OUT'] = $arFields['MESSAGE_OUT'];
				$arParams['NOTIFY_TYPE'] = intval($arFields['NOTIFY_TYPE']);
				$arParams['NOTIFY_MODULE'] = $arFields['NOTIFY_MODULE'];
				$arParams['NOTIFY_EVENT'] = $arFields['NOTIFY_EVENT'];

				//if (mb_strlen($arParams['MESSAGE']) <= 0 && mb_strlen($arParams['MESSAGE_OUT']) <= 0)
				//	return false;

				$skipAdd = false;
				$skipFlash = false;

				if ($arParams['NOTIFY_TYPE'] != IM_NOTIFY_CONFIRM)
				{
					$skipAdd = !CIMSettings::GetNotifyAccess($arFields["TO_USER_ID"], $arFields["NOTIFY_MODULE"], $arFields["NOTIFY_EVENT"], CIMSettings::CLIENT_SITE);
					$skipFlash = $skipAdd;
				}

				if (!$skipAdd && $arFields['NOTIFY_ONLY_FLASH'] == 'Y')
				{
					$skipAdd = true;
				}

				if ($skipAdd)
				{
					$arParams['NOTIFY_READ'] = 'Y';
				}

				if (isset($arFields['IMPORT_ID']))
					$arParams['IMPORT_ID'] = intval($arFields['IMPORT_ID']);

				if (isset($arFields['MESSAGE_DATE']))
				{
					$arParams['DATE_CREATE'] = new Bitrix\Main\Type\DateTime($arFields['MESSAGE_DATE']);
				}

				if (isset($arFields['EMAIL_TEMPLATE']) && trim($arFields['EMAIL_TEMPLATE']) <> '')
					$arParams['EMAIL_TEMPLATE'] = trim($arFields['EMAIL_TEMPLATE']);

				$arParams['NOTIFY_TAG'] = isset($arFields['NOTIFY_TAG'])? $arFields['NOTIFY_TAG']: '';

				if (!empty($arParams['NOTIFY_TAG']))
				{
					$lastMessages = Bitrix\Im\Model\MessageTable::getList([
						'select' => ['ID', 'AUTHOR_ID'],
						'filter' => [
							'=NOTIFY_TAG' => $arParams['NOTIFY_TAG'],
							'=CHAT_ID' => $arParams['CHAT_ID'],
						]
					])->fetchAll();

					// If we have other notifications with the same tag,
					// we need to get USERS from the old notifications
					// then merge it with AUTHOR_ID or create new USERS array with AUTHOR_ID
					// then delete old notifications.
					if (count($lastMessages) > 0)
					{
						foreach ($lastMessages as $lastMessage)
						{
							$lastMessage['AUTHOR_ID'] = (int)$lastMessage['AUTHOR_ID'];
							$lastMessageParams = \CIMMessageParam::Get($lastMessage['ID']);

							if (empty($lastMessageParams['USERS']) && (int)$arFields['FROM_USER_ID'] !== $lastMessage['AUTHOR_ID'])
							{
								$arFields['PARAMS']['USERS'] = [$lastMessage['AUTHOR_ID']];
							}
							else
							{
								$lastMessageParams['USERS'][] = $lastMessage['AUTHOR_ID'];
								$arFields['PARAMS']['USERS'] = array_unique($lastMessageParams['USERS']);
								$index = array_search((int)$arFields['FROM_USER_ID'], $arFields['PARAMS']['USERS'], true);
								if ($index !== false)
								{
									array_splice($arFields['PARAMS']['USERS'], $index, 1);
								}
							}

							CIMNotify::Delete($lastMessage['ID']);
						}
					}
				}

				$arParams['NOTIFY_SUB_TAG'] = isset($arFields['NOTIFY_SUB_TAG'])? $arFields['NOTIFY_SUB_TAG']: '';

				if (isset($arFields['NOTIFY_TITLE']) && trim($arFields['NOTIFY_TITLE']) <> '')
					$arParams['NOTIFY_TITLE'] = trim($arFields['NOTIFY_TITLE']);

				if ($arParams['NOTIFY_TYPE'] == IM_NOTIFY_CONFIRM)
				{
					if (isset($arFields['NOTIFY_BUTTONS']))
					{
						foreach ($arFields['NOTIFY_BUTTONS'] as $key => $arButtons)
						{
							if (is_array($arButtons))
							{
								if (isset($arButtons['TITLE']) && $arButtons['TITLE'] <> ''
								&& isset($arButtons['VALUE']) && $arButtons['VALUE'] <> ''
								&& isset($arButtons['TYPE']) && $arButtons['TYPE'] <> '')
								{
									$arButtons['TITLE'] = htmlspecialcharsbx($arButtons['TITLE']);
									$arButtons['VALUE'] = htmlspecialcharsbx($arButtons['VALUE']);
									$arButtons['TYPE'] = htmlspecialcharsbx($arButtons['TYPE']);
									$arFields['NOTIFY_BUTTONS'][$key] = $arButtons;
								}
								else
									unset($arFields['NOTIFY_BUTTONS'][$key]);
							}
							else
								unset($arFields['NOTIFY_BUTTONS'][$key]);
						}
					}
					else
					{
						$arFields['NOTIFY_BUTTONS'] = Array(
							Array('TITLE' => GetMessage('IM_ERROR_BUTTON_ACCEPT'), 'VALUE' => 'Y', 'TYPE' => 'accept'),
							Array('TITLE' => GetMessage('IM_ERROR_BUTTON_CANCEL'), 'VALUE' => 'N', 'TYPE' => 'cancel'),
						);
					}
					$arParams['NOTIFY_BUTTONS'] = serialize($arFields["NOTIFY_BUTTONS"]);

					if (isset($arParams['NOTIFY_TAG']) && $arParams['NOTIFY_TAG'] <> '')
						CIMNotify::DeleteByTag($arParams['NOTIFY_TAG']);
				}

				if ($skipAdd)
				{
					$messageID = time();
				}
				else
				{
					$result = \Bitrix\Im\Model\MessageTable::add($arParams);
					$messageID = intval($result->getId());
					if ($messageID <= 0)
						return false;

					if (empty($arFields['PARAMS']))
					{
						CIMMessageParam::UpdateTimestamp($messageID, $arParams['CHAT_ID']);
					}
					else
					{
						CIMMessageParam::Set($messageID, $arFields['PARAMS']);
					}

					$counter = \CIMNotify::GetCounter($chatId);
					if ($counter > 100)
					{
						$counter += 1;
					}
					else
					{
						$counter = \CIMNotify::GetRealCounter($chatId);
					}

					$DB->Query("
						UPDATE b_im_relation 
						SET STATUS = '".IM_STATUS_UNREAD."', COUNTER = {$counter}
						WHERE USER_ID = ".intval($arFields['TO_USER_ID'])." AND MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND CHAT_ID = ".$chatId
					);

					\Bitrix\Im\Counter::clearCache($arFields['TO_USER_ID']);

					if (!empty($arFields['PARAMS']))
						CIMMessageParam::Set($messageID, $arFields['PARAMS']);

					$messageCount = \Bitrix\Im\Model\MessageTable::getList(
						[
							'select' => ['CNT'],
							'filter' => ['=CHAT_ID' => $chatId],
							'runtime' => [
								new \Bitrix\Main\ORM\Fields\ExpressionField('CNT', 'COUNT(*)')
							]
						]
					)->fetch();

					\Bitrix\Im\Model\ChatTable::update($chatId, Array(
						'MESSAGE_COUNT' => $messageCount['CNT'],
						'PREV_MESSAGE_ID' => new \Bitrix\Main\DB\SqlExpression('?#', 'LAST_MESSAGE_ID'),
						'LAST_MESSAGE_ID' => $messageID,
						'LAST_MESSAGE_STATUS' => IM_MESSAGE_STATUS_RECEIVED
					));

					$relations = CIMChat::GetRelationById($chatId);
					foreach ($relations as $relation)
					{
						CIMContactList::SetRecent([
							'ENTITY_ID' => $relation['USER_ID'],
							'MESSAGE_ID' => $messageID,
							'CHAT_TYPE' => IM_MESSAGE_SYSTEM,
							'CHAT_ID' => $relation['CHAT_ID'],
							'RELATION_ID' => $relation['ID'],
							'USER_ID' => $relation['USER_ID']
						]);
					}

					CIMMessenger::SpeedFileDelete($arFields['TO_USER_ID'], IM_SPEED_NOTIFY);
				}

				foreach(GetModuleEvents("im", "OnAfterNotifyAdd", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array(intval($messageID), $arFields));

				if (CModule::IncludeModule("pull"))
				{
					\Bitrix\Pull\Push::add($arFields['TO_USER_ID'], Array(
						'module_id' => $arParams['NOTIFY_MODULE'],
						'push' => Array(
							'type' => $arFields['NOTIFY_EVENT'],
							'message' => $arFields['PUSH_MESSAGE'],
							'params' => isset($arFields['PUSH_PARAMS'])? $arFields['PUSH_PARAMS']: '',
							'advanced_params' => isset($arFields['PUSH_PARAMS']) && isset($arFields['PUSH_PARAMS']['ADVANCED_PARAMS']) ? $arFields['PUSH_PARAMS']['ADVANCED_PARAMS']: array(),
							'important' => isset($arFields['PUSH_IMPORTANT']) && $arFields['PUSH_IMPORTANT'] === 'Y' ? 'Y': 'N',
							'tag' => $arParams['NOTIFY_TAG'],
							'sub_tag' => $arParams['NOTIFY_SUB_TAG'],
							'app_id' => isset($arParams['PUSH_APP_ID'])? $arParams['PUSH_APP_ID']: '',
						)
					));

					if (!$skipFlash)
					{
						$params = CIMNotify::GetFormatNotify(
							[
								'TEMPLATE_ID' => $arFields['TEMPLATE_ID'],
								'ID' => $messageID,
								'DATE_CREATE' => time(),
								'FROM_USER_ID' => (int)$arFields['FROM_USER_ID'],
								'MESSAGE' => $arParams['MESSAGE'],
								'PARAMS' => self::PrepareParamsForPull($arFields['PARAMS']),
								'NOTIFY_ONLY_FLASH' => $skipAdd,
								'NOTIFY_LINK' => $arFields['NOTIFY_LINK'],
								'NOTIFY_MODULE' => $arParams['NOTIFY_MODULE'],
								'NOTIFY_EVENT' => $arParams['NOTIFY_EVENT'],
								'NOTIFY_TAG' => $arParams['NOTIFY_TAG'],
								'NOTIFY_TYPE' => $arParams['NOTIFY_TYPE'],
								'NOTIFY_BUTTONS' => $arParams['NOTIFY_BUTTONS'] ?? serialize([]),
								'NOTIFY_TITLE' => $arParams['NOTIFY_TITLE'] ?? '',
								'SKIP_ADD' => $skipAdd? 'Y': 'N',
								'COUNTER' => $counter,
							]
						);

						\Bitrix\Pull\Event::add($arFields['TO_USER_ID'], Array(
							'module_id' => 'im',
							'command' => 'notifyAdd',
							'params' => $params,
							'extra' => \Bitrix\Im\Common::getPullExtra()
						));
					}
				}

				if (!$skipAdd)
				{
					\Bitrix\Im\Model\MessageTable::indexRecord($messageID);
				}

				return $messageID;
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
				return false;
			}
		}
		else
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_TYPE"), "MESSAGE_TYPE");
			return false;
		}
	}

	public static function IsMobileRequest()
	{
		return isset($_REQUEST['MOBILE']) && $_REQUEST['MOBILE'] == 'Y' || defined('BX_MOBILE');
	}

	public static function CheckPossibilityUpdateMessage($type, $id, $userId = null)
	{
		$id = intval($id);
		if ($id <= 0)
			return false;

		$enableCheck = self::IsEnabledMessageCheck();
		if ($enableCheck)
		{
			global $USER;
			$userId = is_null($userId)? $USER->GetId(): intval($userId);
			if ($userId <= 0)
				return false;
		}

		$result = false;
		if (CModule::IncludeModule('pull') && CPullOptions::GetNginxStatus())
		{
			$message = self::GetById($id);
			$skipUserCheck = false;
			if ($message !== false)
			{
				if
				(
					$type == IM_CHECK_DELETE
					&& (
						$message['AUTHOR_ID'] == 0 && $message['CHAT_ENTITY_TYPE'] === 'LINES'
						|| !\Bitrix\Im\User::getInstance($message['AUTHOR_ID'])->isActive() && !\Bitrix\Im\User::getInstance($message['AUTHOR_ID'])->isExtranet()
						|| \Bitrix\Im\User::getInstance($message['AUTHOR_ID'])->isConnector()
						|| $message['CHAT_ID'] == CIMChat::GetGeneralChatId()
					)
					&& self::IsAdmin()
				)
				{
					$skipUserCheck = true;
				}
				else if (\Bitrix\Im\User::getInstance($userId)->isBot() && ($message['AUTHOR_ID'] == 0 || $message['PARAMS']['IS_DELETED'] == 'Y'))
				{}
				else if ($message['PARAMS']['IS_DELETED'] == 'Y' || $type != IM_CHECK_DELETE && $message['DATE_CREATE']+259200 < time())
				{
					return false;
				}
			}
			else
			{
				return false;
			}

			if ($enableCheck)
			{
				if ($message['CHAT_ENTITY_TYPE'] == 'LINES' && CModule::IncludeModule('imopenlines'))
				{
					list($connectorType) = explode("|", $message['CHAT_ENTITY_ID']);
					if (\Bitrix\Im\User::getInstance($userId)->isBot())
					{
						if ($message['AUTHOR_ID'] != $userId)
						{
							$relation = \CIMMessenger::GetRelationById($id);
							if (!isset($relation[$userId]))
							{
								return false;
							}
						}
					}
					else if ($message['AUTHOR_ID'] == $userId)
					{
						if (!($type == IM_CHECK_UPDATE && in_array($connectorType, \Bitrix\ImOpenlines\Connector::getListCanUpdateOwnMessage()))
							&& !($type == IM_CHECK_DELETE && in_array($connectorType, \Bitrix\ImOpenlines\Connector::getListCanDeleteOwnMessage())))
						{
							return false;
						}
					}
					else
					{
						if ($type == IM_CHECK_UPDATE || !$skipUserCheck && !in_array($connectorType, \Bitrix\ImOpenlines\Connector::getListCanDeleteMessage()))
						{
							return false;
						}
					}
				}
				else if (!$skipUserCheck && $message['AUTHOR_ID'] != $userId)
				{
					return false;
				}
			}

			$result = $message;
		}

		return $result;
	}

	public static function Update($id, $text, $urlPreview = true, $editFlag = true, $userId = null, $byEvent = false)
	{
		if (mb_strlen($text) > self::MESSAGE_LIMIT + 6)
		{
			$text = mb_substr($text, 0, self::MESSAGE_LIMIT).' (...)';
		}

		$updateFlags = Array(
			'ID' => $id,
			'TEXT' => $text,
			'URL_PREVIEW' => $urlPreview,
			'EDIT_FLAG' => $editFlag,
			'USER_ID' => $userId,
			'BY_EVENT' => $byEvent,
		);

		$text = trim(str_replace(Array('[BR]', '[br]'), "\n", $text));
		if ($text == '')
		{
			return self::Delete($id, $userId, false, $byEvent);
		}

		$message = self::CheckPossibilityUpdateMessage(IM_CHECK_UPDATE, $id, $userId);
		if (!$message)
			return false;


		$dateText = Array();
		$dateTs = Array();

		if ($urlPreview)
		{
			$results = \Bitrix\Im\Text::getDateConverterParams($text);
			foreach ($results as $result)
			{
				$dateText[] = $result->getText();
				$dateTs[] = $result->getDate()->getTimestamp();
			}
		}

		$arUpdate = Array('MESSAGE' => $text, 'MESSAGE_OUT' => '');
		$urlId = Array();
		$urlOnly = false;
		if ($urlPreview)
		{
			$link = new CIMMessageLink();
			$urlPrepare = $link->prepareInsert($text);
			if ($urlPrepare['RESULT'])
			{
				$arUpdate['MESSAGE_OUT'] = $text;
				$arUpdate['MESSAGE'] = $urlPrepare['MESSAGE'];
				$urlId = $urlPrepare['URL_ID'];
				if ($urlPrepare['MESSAGE_IS_LINK'])
				{
					$urlOnly = true;
				}
			}
		}

		\Bitrix\Im\Model\MessageTable::update($message['ID'], $arUpdate);

		$isOnlyEmoji = \Bitrix\Im\Text::isOnlyEmoji($arUpdate['MESSAGE']);

		CIMMessageParam::Set($message['ID'], Array('IS_EDITED' => $editFlag?'Y':'N', 'URL_ID' => $urlId, 'URL_ONLY' => $urlOnly?'Y':'N', 'LARGE_FONT' => $isOnlyEmoji?'Y':'N', 'DATE_TEXT' => $dateText, 'DATE_TS' => $dateTs));

		$arFields = $message;
		$arFields['MESSAGE'] = $text;
		$arFields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();

		$pullMessage = \Bitrix\Im\Text::parse($arFields['MESSAGE']);

		$relations = CIMMessenger::GetRelationById($message['ID']);

		$arPullMessage = Array(
			'id' => (int)$arFields['ID'],
			'type' => $arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE? 'private': 'chat',
			'text' => $pullMessage,
			'textOriginal' => $arFields['MESSAGE'],
		);
		$arBotInChat = Array();

		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$arFields['FROM_USER_ID'] = $arFields['AUTHOR_ID'];
			$arFields['TO_USER_ID'] = $arFields['AUTHOR_ID'];
			foreach ($relations as $rel)
			{
				if ($rel['USER_ID'] != $arFields['AUTHOR_ID'])
				{
					$arFields['TO_USER_ID'] = $rel['USER_ID'];
				}
			}

			$arPullMessage['fromUserId'] = (int)$arFields['FROM_USER_ID'];
			$arPullMessage['toUserId'] = (int)$arFields['TO_USER_ID'];
			$arPullMessage['senderId'] = (int)$arFields['FROM_USER_ID'];
			$arPullMessage['chatId'] = (int)$arFields['CHAT_ID'];
		}
		else
		{
			$arPullMessage['chatId'] = (int)$arFields['CHAT_ID'];
			$arPullMessage['senderId'] = (int)$arFields['AUTHOR_ID'];

			foreach ($relations as $relation)
			{
				if ($message['CHAT_ENTITY_TYPE'] == 'LINES')
				{
					if ($relation["EXTERNAL_AUTH_ID"] == 'imconnector')
					{
						unset($relations[$relation["USER_ID"]]);
						continue;
					}
				}
				if ($relation["EXTERNAL_AUTH_ID"] == \Bitrix\Im\Bot::EXTERNAL_AUTH_ID)
				{
					$arBotInChat[$relation["USER_ID"]] = $relation["USER_ID"];
					unset($relations[$relation["USER_ID"]]);
					continue;
				}
			}
		}

		$arMessages[$message['ID']] = Array();

		$params = CIMMessageParam::Get(Array($message['ID']), false);
		$arMessages[$message['ID']]['params'] = $params[$message['ID']];

		$arDefault = CIMMessageParam::GetDefault();
		if (!isset($arMessages[$message['ID']]['params']['IS_EDITED']))
		{
			$arMessages[$message['ID']]['params']['IS_EDITED'] = $arDefault['IS_EDITED'];
		}
		if (!isset($arMessages[$message['ID']]['params']['URL_ID']))
		{
			$arMessages[$message['ID']]['params']['URL_ID'] = $arDefault['URL_ID'];
		}
		if (!isset($arMessages[$message['ID']]['params']['ATTACH']))
		{
			$arMessages[$message['ID']]['params']['ATTACH'] = $arDefault['ATTACH'];
		}
		if (!isset($arMessages[$message['ID']]['params']['DATE_TEXT']))
		{
			$arMessages[$message['ID']]['params']['DATE_TEXT'] = $arDefault['DATE_TEXT'];
		}
		if (!isset($arMessages[$message['ID']]['params']['DATE_TS']))
		{
			$arMessages[$message['ID']]['params']['DATE_TS'] = $arDefault['DATE_TS'];
		}

		$arMessages = CIMMessageLink::prepareShow($arMessages, $params);
		$arPullMessage['params'] = CIMMessenger::PrepareParamsForPull($arMessages[$message['ID']]['params']);

		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$arPullMessage['dialogId'] = (int)$arFields['FROM_USER_ID'];
			$arPullMessage['fromUserId'] = (int)$arFields['FROM_USER_ID'];
			$arPullMessage['toUserId'] = (int)$arFields['TO_USER_ID'];

			\Bitrix\Pull\Event::add($arPullMessage['toUserId'], Array(
				'module_id' => 'im',
				'command' => 'messageUpdate',
				'params' => $arPullMessage,
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));

			$arPullMessage['dialogId'] = (int)$arFields['TO_USER_ID'];
			$arPullMessage['fromUserId'] = (int)$arFields['TO_USER_ID'];
			$arPullMessage['toUserId'] = (int)$arFields['FROM_USER_ID'];

			\Bitrix\Pull\Event::add($arPullMessage['toUserId'], Array(
				'module_id' => 'im',
				'command' => 'messageUpdate',
				'params' => $arPullMessage,
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}
		else
		{
			$arPullMessage['dialogId'] = 'chat'.$arPullMessage['chatId'];

			\Bitrix\Pull\Event::add(array_keys($relations), Array(
				'module_id' => 'im',
				'command' => 'messageUpdate',
				'params' => $arPullMessage,
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}

		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_OPEN || $message['MESSAGE_TYPE'] == IM_MESSAGE_OPEN_LINE)
		{
			CPullWatch::AddToStack('IM_PUBLIC_'.$message['CHAT_ID'], Array(
				'module_id' => 'im',
				'command' => 'messageUpdate',
				'params' => $arPullMessage,
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}
		if ($message['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE)
		{
			$arFields['BOT_IN_CHAT'] = $arBotInChat;
		}

		\Bitrix\Main\Application::getConnection()->query(
			"UPDATE b_im_recent SET DATE_UPDATE = NOW() WHERE ITEM_MID = ".intval($id)
		);

		foreach(GetModuleEvents("im", "OnAfterMessagesUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(intval($id), $arFields, $updateFlags));

		\Bitrix\Im\Bot::onMessageUpdate(intval($id), $arFields);

		\Bitrix\Im\Model\MessageTable::indexRecord($id);

		return true;
	}

	public static function Share($id, $type, $date = '')
	{
		$chat = new CIMShare();

		if (\Bitrix\Im\User::getInstance($chat->user_id)->isExtranet())
		{
			return false;
		}

		if ($type == 'CHAT')
		{
			$result = $chat->Chat($id);
		}
		else if ($type == 'TASK')
		{
			$result = $chat->Task($id, $date);
		}
		else if ($type == 'POST')
		{
			$result = $chat->Post($id);
		}
		else if ($type == 'CALEND')
		{
			$result = $chat->Calendar($id, $date);
		}

		return $result;
	}

	public static function Delete($id, $userId = null, $completeDelete = false, $byEvent = false)
	{
		$deleteFlags = Array(
			'ID' => $id,
			'USER_ID' => $userId,
			'COMPLETE_DELETE' => $completeDelete,
			'BY_EVENT' => $byEvent
		);

		$message = self::CheckPossibilityUpdateMessage(IM_CHECK_DELETE, $id, $userId);
		if (!$message)
			return false;

		$deleteFlags['COMPLETE_DELETE'] = $completeDelete = $message['CHAT_ID'] == CIMChat::GetGeneralChatId() && self::IsAdmin()? true: $completeDelete;

		$params = CIMMessageParam::Get($message['ID']);
		if (!empty($params['FILE_ID']))
		{
			foreach ($params['FILE_ID'] as $fileId)
			{
				CIMDisk::DeleteFile($message['CHAT_ID'], $fileId);
			}
		}

		$date = FormatDate("FULL", $message['DATE_CREATE']+CTimeZone::GetOffset());
		if (!$completeDelete)
		{
			\Bitrix\Im\Model\MessageTable::update($message['ID'], array(
				"MESSAGE" => GetMessage('IM_MESSAGE_DELETED'),
				"MESSAGE_OUT" => GetMessage('IM_MESSAGE_DELETED_OUT', Array('#DATE#' => $date)),
			));
			CIMMessageParam::Set($message['ID'], Array('IS_DELETED' => 'Y', 'URL_ID' => Array(), 'FILE_ID' => Array(), 'KEYBOARD' => 'N', 'ATTACH' => Array()));
		}

		$arFields = $message;
		$arFields['MESSAGE'] = GetMessage('IM_MESSAGE_DELETED_OUT', Array('#DATE#' => $date));
		$arFields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();

		$relations = CIMMessenger::GetRelationById($message['ID']);
		$arPullMessage = Array(
			'id' => (int)$arFields['ID'],
			'type' => $arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE? 'private': 'chat',
			'text' => GetMessage('IM_MESSAGE_DELETED'),
			'params' => Array('IS_DELETED' => 'Y', 'URL_ID' => Array(), 'FILE_ID' => Array(), 'KEYBOARD' => 'N', 'ATTACH' => Array())
		);
		$arBotInChat = Array();
		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$arFields['FROM_USER_ID'] = $arFields['AUTHOR_ID'];
			$arFields['TO_USER_ID'] = $arFields['AUTHOR_ID'];
			foreach ($relations as $rel)
			{
				if ($rel['USER_ID'] != $arFields['AUTHOR_ID'])
					$arFields['TO_USER_ID'] = $rel['USER_ID'];
			}

			$arPullMessage['fromUserId'] = (int)$arFields['FROM_USER_ID'];
			$arPullMessage['toUserId'] = (int)$arFields['TO_USER_ID'];
			$arPullMessage['senderId'] = (int)$arFields['TO_USER_ID'];
			$arPullMessage['chatId'] = (int)$arFields['CHAT_ID'];
		}
		else
		{
			$arPullMessage['chatId'] = (int)$arFields['CHAT_ID'];
			$arPullMessage['senderId'] = (int)$arFields['AUTHOR_ID'];

			foreach ($relations as $relation)
			{
				if ($message['CHAT_ENTITY_TYPE'] == 'LINES')
				{
					if ($relation["EXTERNAL_AUTH_ID"] == 'imconnector')
					{
						unset($relations[$relation["USER_ID"]]);
						continue;
					}
				}
				if ($relation["EXTERNAL_AUTH_ID"] == \Bitrix\Im\Bot::EXTERNAL_AUTH_ID)
				{
					$arBotInChat[$relation["USER_ID"]] = $relation["USER_ID"];
					unset($relations[$relation["USER_ID"]]);
					continue;
				}
			}
		}

		if ($message['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE)
		{
			$arFields['BOT_IN_CHAT'] = $arBotInChat;
		}

		\Bitrix\Im\Bot::onMessageDelete(intval($id), $arFields);

		if ($completeDelete)
		{
			\Bitrix\Im\Model\ChatTable::update($message['CHAT_ID'], Array(
				'MESSAGE_COUNT' => new \Bitrix\Main\DB\SqlExpression('?# - 1', 'MESSAGE_COUNT'),
			));

			if ($message['CHAT_PARENT_MID'])
			{
				$chatData = \Bitrix\Im\Model\ChatTable::getById($message['CHAT_ID'])->fetch();
				CIMMessageParam::set($chatData['PARENT_MID'], Array(
					'CHAT_MESSAGE' => $chatData['MESSAGE_COUNT'],
					'CHAT_LAST_DATE' => new \Bitrix\Main\Type\DateTime()
				));
				CIMMessageParam::SendPull($chatData['PARENT_MID'], Array('CHAT_MESSAGE', 'CHAT_LAST_DATE'));
			}

			$completeDelete = true;
			CIMMessageParam::DeleteAll($message['ID']);
			\Bitrix\Im\Model\MessageTable::delete($message['ID']);

			$relationCounters = \Bitrix\Im\Chat::getRelation($message['CHAT_ID'], Array(
				'SELECT' => Array('ID', 'USER_ID'),
				'REAL_COUNTERS' => 'Y',
				'USER_DATA' => 'Y',
				'SKIP_RELATION_WITH_UNMODIFIED_COUNTERS' => 'Y'
			));
			foreach ($relationCounters as $relation)
			{
				if (
					$relation['USER_DATA']["EXTERNAL_AUTH_ID"] == \Bitrix\Im\Bot::EXTERNAL_AUTH_ID
					|| $relation['USER_DATA']['ACTIVE'] == 'N'
				)
				{
					continue;
				}
				\Bitrix\Im\Model\RelationTable::update($relation['ID'], Array('COUNTER' => $relation['COUNTER']));
				\Bitrix\Im\Counter::clearCache($relation['USER_ID']);
			}

			$result = \Bitrix\Im\Model\RecentTable::getList(Array('filter' => Array('=ITEM_MID' => $message['ID'])))->fetchAll();
			if (!empty($result))
			{
				$message = \Bitrix\Im\Model\MessageTable::getList(Array(
					'filter' => Array('=CHAT_ID' => $message['CHAT_ID']),
					'limit' => 1,
					'order' => Array('ID' => 'DESC')
				))->fetch();
				if ($message)
				{
					foreach ($result as $recent)
					{
						\Bitrix\Im\Model\RecentTable::update(Array(
							'USER_ID' => $recent['USER_ID'],
							'ITEM_TYPE' => $recent['ITEM_TYPE'],
							'ITEM_ID' => $recent['ITEM_ID'],
						), Array('ITEM_MID' => $message['ID']));

						if ($recent['ITEM_TYPE'] == IM_MESSAGE_PRIVATE)
							CIMMessenger::SpeedFileDelete($recent['USER_ID'], IM_SPEED_GROUP);
						else
							CIMMessenger::SpeedFileDelete($recent['USER_ID'], IM_SPEED_MESSAGE);
					}
				}
			}
		}

		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$arPullMessage['dialogId'] = (int)$arFields['FROM_USER_ID'];
			$arPullMessage['chatId'] = (int)$message['CHAT_ID'];
			$arPullMessage['fromUserId'] = (int)$arFields['FROM_USER_ID'];
			$arPullMessage['toUserId'] = (int)$arFields['TO_USER_ID'];

			\Bitrix\Pull\Event::add($arPullMessage['toUserId'], Array(
				'module_id' => 'im',
				'command' => ($completeDelete? 'messageDeleteComplete': 'messageDelete'),
				'params' => $arPullMessage,
				'push' => $completeDelete? Array('badge' => 'Y'): Array(),
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));

			$arPullMessage['dialogId'] = (int)$arFields['TO_USER_ID'];
			$arPullMessage['fromUserId'] = (int)$arFields['TO_USER_ID'];
			$arPullMessage['toUserId'] = (int)$arFields['FROM_USER_ID'];

			\Bitrix\Pull\Event::add($arPullMessage['toUserId'], Array(
				'module_id' => 'im',
				'command' => ($completeDelete? 'messageDeleteComplete': 'messageDelete'),
				'params' => $arPullMessage,
				'push' => $completeDelete? Array('badge' => 'Y'): Array(),
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}
		else
		{
			$arPullMessage['dialogId'] = 'chat'.$arPullMessage['chatId'];
			$arPullMessage['chatId'] = (int)$message['CHAT_ID'];

			\Bitrix\Pull\Event::add(array_keys($relations), Array(
				'module_id' => 'im',
				'command' => ($completeDelete? 'messageDeleteComplete': 'messageDelete'),
				'params' => $arPullMessage,
				'push' => $completeDelete? Array('badge' => 'Y'): Array(),
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}

		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_OPEN || $message['MESSAGE_TYPE'] == IM_MESSAGE_OPEN_LINE)
		{
			CPullWatch::AddToStack('IM_PUBLIC_'.$message['CHAT_ID'], Array(
				'module_id' => 'im',
				'command' => ($completeDelete? 'messageDeleteComplete': 'messageDelete'),
				'params' => $arPullMessage,
				'push' => $completeDelete? Array('badge' => 'Y'): Array(),
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}

		$indexEnabled = \Bitrix\Main\Config\Option::get('im', 'message_history_index');

		if ($indexEnabled)
		{
			\Bitrix\Im\Model\MessageIndexTable::delete($id);
		}

		foreach(GetModuleEvents("im", "OnAfterMessagesDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(intval($id), $arFields, $deleteFlags));

		return true;
	}

	public static function LinesSessionVote($dialogId, $messageId, $action, $userId = null)
	{
		global $USER;
		$userId = is_null($userId)? $USER->GetId(): intval($userId);
		if ($userId <= 0)
			return false;

		if ($dialogId == '')
			return false;

		if (!\Bitrix\Im\User::getInstance($userId)->isConnector() && !\Bitrix\Im\User::getInstance($dialogId)->isBot())
			return false;

		$messageId = intval($messageId);
		$action = $action == 'dislike'? 'dislike': 'like';

		$message = self::GetById($messageId);
		if (!$message || intval($message['PARAMS']['IMOL_VOTE']) <= 0)
			return false;
		if ($message['DATE_CREATE']+86400 < time())
			return false;
		$relations = CIMMessenger::GetRelationById($messageId);

		$result = \Bitrix\Im\Model\ChatTable::getList(Array(
			'filter'=>Array(
				'=ID' => $message['CHAT_ID']
			)
		));
		$chat = $result->fetch();
		if (!isset($relations[$userId]))
			return false;

		CIMMessageParam::Set($messageId, Array('IMOL_VOTE' => $action));
		CIMMessageParam::SendPull($messageId, Array('IMOL_VOTE'));

		if ($chat['ENTITY_TYPE'] == 'LIVECHAT')
		{
			CIMMessageParam::Set($message['PARAMS']['CONNECTOR_MID'][0], Array('IMOL_VOTE' => $action));
			CIMMessageParam::SendPull($message['PARAMS']['CONNECTOR_MID'][0], Array('IMOL_VOTE'));

			if (CModule::IncludeModule('imopenlines'))
			{
				\Bitrix\ImOpenlines\Session::voteAsUser(intval($message['PARAMS']['IMOL_VOTE']), $action);
			}
		}
		else
		{
			$chat = Array();
			$relations = Array();
		}

		foreach(GetModuleEvents("im", "OnSessionVote", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(array(
				'DIALOG_ID' => $dialogId,
				'MESSAGE_ID' => $messageId,
				'SESSION_ID' => $message['PARAMS']['IMOL_VOTE'],
				'MESSAGE' => $message,
				'ACTION' => $action,
				'CHAT' => $chat,
				'RELATION' => $relations,
				'USER_ID' => $userId
			)));
		}

		return true;
	}

	public static function Like($id, $action = 'auto', $userId = null, $byEvent = false)
	{
		if (!CModule::IncludeModule('pull'))
			return false;

		global $USER;
		$userId = is_null($userId)? $USER->GetId(): intval($userId);
		if ($userId <= 0)
			return false;

		$action = in_array($action, Array('plus', 'minus'))? $action: 'auto';

		$message = self::GetById($id);
		if (!$message)
			return false;

		$relations = CIMMessenger::GetRelationById($id);

		$result = \Bitrix\Im\Model\ChatTable::getList(Array(
			'filter'=>Array(
				'=ID' => $message['CHAT_ID']
			)
		));
		$chat = $result->fetch();
		if ($chat['ENTITY_TYPE'] != 'LIVECHAT')
		{
			if (!isset($relations[$userId]))
				return false;
		}

		if (!$byEvent && $chat['ENTITY_TYPE'] == 'LINES')
		{
			list($connectorType, $lineId, $chatId) = explode("|", $chat['ENTITY_ID']);
			if ($connectorType == "livechat")
			{
				foreach($message['PARAMS']['CONNECTOR_MID'] as $mid)
				{
					self::Like($mid, $action, $userId, true);
				}
			}
		}
		else if (!$byEvent && $chat['ENTITY_TYPE'] == 'LIVECHAT')
		{
			foreach($message['PARAMS']['CONNECTOR_MID'] as $mid)
			{
				self::Like($mid, $action, $userId, true);
			}
		}

		$isLike = false;
		if (isset($message['PARAMS']['LIKE']))
		{
			$isLike = in_array($userId, $message['PARAMS']['LIKE']);
		}

		if ($isLike && $action == 'plus')
		{
			return false;
		}
		else if (!$isLike && $action == 'minus')
		{
			return false;
		}

		$isLike = true;
		if (isset($message['PARAMS']['LIKE']))
		{
			$like = $message['PARAMS']['LIKE'];
			$selfLike = array_search($userId, $like);
			if ($selfLike !== false)
			{
				$isLike = false;
				unset($like[$selfLike]);
			}
			else
			{
				$like[] = $userId;
			}
		}
		else
		{
			$like = Array($userId);
		}

		sort($like);
		CIMMessageParam::Set($id, Array('LIKE' => $like));

		if ($message['AUTHOR_ID'] > 0 && $message['AUTHOR_ID'] != $userId && $isLike && $chat['ENTITY_TYPE'] != 'LIVECHAT')
		{
			$message['MESSAGE'] = self::PrepareParamsForPush($message);

			$isChat = $chat && $chat['TITLE'] <> '';

			$dot = mb_strlen($message['MESSAGE']) >= 200? '...': '';
			$message['MESSAGE'] = mb_substr($message['MESSAGE'], 0, 199).$dot;
			$message['MESSAGE'] = $message['MESSAGE'] <> ''? $message['MESSAGE']: '-';

			$arMessageFields = array(
				"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
				"TO_USER_ID" => $message['AUTHOR_ID'],
				"FROM_USER_ID" => $userId,
				"NOTIFY_TYPE" => IM_NOTIFY_FROM,
				"NOTIFY_MODULE" => "im",
				"NOTIFY_EVENT" => "like",
				"NOTIFY_TAG" => "RATING|IM|".($isChat? 'G':'P')."|".($isChat? $chat['ID']: $userId)."|".$id,
				"NOTIFY_MESSAGE" => GetMessage($isChat? 'IM_MESSAGE_LIKE': 'IM_MESSAGE_LIKE_PRIVATE', Array(
					'#MESSAGE#' => $message['MESSAGE'],
					'#TITLE#' => $isChat? '[CHAT='.$chat['ID'].']'.$chat['TITLE'].'[/CHAT]': $chat['TITLE']
				)),
				"NOTIFY_MESSAGE_OUT" => GetMessage($isChat? 'IM_MESSAGE_LIKE': 'IM_MESSAGE_LIKE_PRIVATE', Array(
					'#MESSAGE#' => $message['MESSAGE'],
					'#TITLE#' => $chat['TITLE']
				)),
			);
			CIMNotify::Add($arMessageFields);
		}

		$pushUsers = $like;
		$pushUsers[] = $message['AUTHOR_ID'];
		$arPullMessage = Array(
			'id' => (int)$id,
			'chatId' => (int)$chat['ID'],
			'senderId' => (int)$userId,
			'users' => $like
		);

		if ($chat['ENTITY_TYPE'] == 'LINES')
		{
			foreach ($relations as $rel)
			{
				if ($rel["EXTERNAL_AUTH_ID"] == 'imconnector')
				{
					unset($relations[$rel["USER_ID"]]);
				}
			}
		}

		\Bitrix\Pull\Event::add(array_keys($relations), Array(
			'module_id' => 'im',
			'command' => 'messageLike',
			'params' => $arPullMessage,
			'extra' => \Bitrix\Im\Common::getPullExtra()
		));

		if ($chat['TYPE'] == IM_MESSAGE_OPEN || $chat['TYPE'] == IM_MESSAGE_OPEN_LINE)
		{
			CPullWatch::AddToStack('IM_PUBLIC_'.$chat['ID'], Array(
				'module_id' => 'im',
				'command' => 'messageLike',
				'params' => $arPullMessage,
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}

		if ($chat['TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$dialogId = $userId;
			foreach ($relations as $rel)
			{
				if ($rel['USER_ID'] != $userId)
				{
					$dialogId = $rel['USER_ID'];
				}
			}
		}
		else
		{
			$dialogId = 'chat'.$chat['ID'];
		}

		foreach(GetModuleEvents("im", "OnAfterMessagesLike", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(array(
				'DIALOG_ID' => $dialogId,
				'CHAT' => $chat,
				'MESSAGE' => $message,
				'ACTION' => $action,
				'USER_ID' => $userId,
				'BY_EVENT' => $byEvent
			)));
		}

		return $like;
	}

	public static function ExecCommand($id, $botId, $command, $commandParams = '', $userId = null, $byEvent = false)
	{
		global $USER;
		$userId = is_null($userId)? $USER->GetId(): intval($userId);
		if ($userId <= 0)
			return false;

		$messageId = intval($id);

		$orm = \Bitrix\Im\Model\MessageTable::getById($messageId);
		$message = $orm->fetch();

		if(!$message)
		{
			return false;
		}

		$orm = \Bitrix\Im\Model\ChatTable::getById($message['CHAT_ID']);
		$chat = $orm->fetch();
		if (!$chat)
		{
			return false;
		}

		$relations = \CIMChat::GetRelationById($message['CHAT_ID']);
		if (!isset($relations[$userId]))
		{
			return false;
		}

		$entityType = $chat['ENTITY_TYPE'];
		$entityId = $chat['ENTITY_ID'];

		if ($chat['TYPE'] != IM_MESSAGE_PRIVATE)
		{
			$chatId = $message['CHAT_ID'];
			$messageType = $chat['TYPE'];

			if ($chat['ENTITY_TYPE'] === 'LIVECHAT') // TODO finalize it
			{
				list($lineId, $userId) = explode("|", $chat['ENTITY_ID']);

				$entityType = 'LINES';
				$entityId = 'livechat|'.$lineId.'|'.$message['CHAT_ID'].'|'.$userId;

				$chatLines = \Bitrix\Im\Model\ChatTable::getList(Array(
					'select' => ['ID'],
					'filter' => [
						'=ENTITY_TYPE' => $entityType,
						'=ENTITY_ID' => $entityId,
					]
				))->fetch();
				if (!$chatLines)
				{
					return false;
				}

				$chatId = $chatLines['ID'];
				$messageType = $chatLines['TYPE'];
			}

			$messageFields = Array(
				"FROM_USER_ID" => $userId,
				"TO_CHAT_ID" => $chatId,
				"MESSAGE"  => '/'.$command.' '.$commandParams,
			);
		}
		else
		{
			$messageFields = Array(
				"FROM_USER_ID" => $userId,
				"TO_USER_ID" => intval($botId),
				"MESSAGE"  => '/'.$_POST['COMMAND'].' '.$_POST['COMMAND_PARAMS'],
			);

			$messageType = $relations[$userId]['MESSAGE_TYPE'];
		}

		$messageFields['MESSAGE_TYPE'] = $messageType;
		$messageFields['CHAT_ENTITY_TYPE'] = $entityType;
		$messageFields['CHAT_ENTITY_ID'] = $entityId;
		$messageFields['AUTHOR_ID'] = $userId;
		$messageFields['COMMAND_CONTEXT'] = 'KEYBOARD';

		\Bitrix\Im\Command::onCommandAdd($messageId, $messageFields);

		return true;
	}

	public static function UrlAttachDelete($id, $attachId = false, $userId = null)
	{
		if (!CModule::IncludeModule('pull'))
			return false;

		global $USER;
		$userId = is_null($userId)? $USER->GetId(): intval($userId);
		if ($userId <= 0)
			return false;

		$relations = CIMMessenger::GetRelationById($id);
		if (!isset($relations[$userId]))
			return false;

		$newUrlId = Array();
		if ($attachId)
		{
			$urlId = CIMMessageParam::Get($id, 'URL_ID');
			foreach ($urlId as $value)
			{
				if ($value != $attachId)
				{
					$newUrlId[] = $value;
				}
			}
		}

		CIMMessageParam::Set($id, Array('URL_ID' => $newUrlId, 'URL_ONLY' => empty($newUrlId)? 'N': 'Y'));
		CIMMessageParam::SendPull($id, Array('URL_ID', 'ATTACH', 'URL_ONLY'));

		return true;
	}

	private static function CheckFields($arFields)
	{
		$aMsg = array();
		if(!is_set($arFields, "MESSAGE_TYPE") || !in_array($arFields["MESSAGE_TYPE"], Array(IM_MESSAGE_PRIVATE, IM_MESSAGE_CHAT, IM_MESSAGE_OPEN, IM_MESSAGE_SYSTEM, IM_MESSAGE_OPEN_LINE)))
		{
			$aMsg[] = array("id"=>"MESSAGE_TYPE", "text"=> GetMessage("IM_ERROR_MESSAGE_TYPE"));
		}
		else
		{
			if(in_array($arFields["MESSAGE_TYPE"], Array(IM_MESSAGE_CHAT, IM_MESSAGE_OPEN, IM_MESSAGE_OPEN_LINE)) && !isset($arFields['SYSTEM']) && (intval($arFields["TO_CHAT_ID"]) <= 0 && intval($arFields["FROM_USER_ID"]) <= 0))
				$aMsg[] = array("id"=>"TO_CHAT_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_TO_FROM"));

			if($arFields["MESSAGE_TYPE"] == IM_MESSAGE_PRIVATE && !((intval($arFields["TO_USER_ID"]) > 0 || intval($arFields["TO_CHAT_ID"]) > 0) && intval($arFields["FROM_USER_ID"]) > 0))
				$aMsg[] = array("id"=>"FROM_USER_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_TO_FROM"));

			if (is_set($arFields, "MESSAGE_DATE") && (!$GLOBALS['DB']->IsDate($arFields["MESSAGE_DATE"], false, LANG, "FULL")))
				$aMsg[] = array("id"=>"MESSAGE_DATE", "text"=> GetMessage("IM_ERROR_MESSAGE_DATE"));

			if(in_array($arFields["MESSAGE_TYPE"], Array(IM_MESSAGE_PRIVATE, IM_MESSAGE_SYSTEM)) && !(intval($arFields["TO_USER_ID"]) > 0 || intval($arFields["TO_CHAT_ID"]) > 0))
				$aMsg[] = array("id"=>"TO_USER_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_TO"));

			if(is_set($arFields, "MESSAGE") && trim($arFields["MESSAGE"]) == '')
				$aMsg[] = array("id"=>"MESSAGE", "text"=> GetMessage("IM_ERROR_MESSAGE_TEXT"));
			else if(!is_set($arFields, "MESSAGE") && $arFields["MESSAGE_TYPE"] == IM_MESSAGE_SYSTEM && empty($arFields['PARAMS']['ATTACH']))
				$aMsg[] = array("id"=>"MESSAGE", "text"=> GetMessage("IM_ERROR_MESSAGE_TEXT"));
			else if(!is_set($arFields, "MESSAGE") && empty($arFields['PARAMS']['ATTACH']) && empty($arFields['PARAMS']['FILE_ID']))
				$aMsg[] = array("id"=>"MESSAGE", "text"=> GetMessage("IM_ERROR_MESSAGE_TEXT"));

			if($arFields["MESSAGE_TYPE"] == IM_MESSAGE_PRIVATE && is_set($arFields, "AUTHOR_ID") && intval($arFields["AUTHOR_ID"]) <= 0)
				$aMsg[] = array("id"=>"AUTHOR_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_AUTHOR"));

			if(is_set($arFields, "IMPORT_ID") && intval($arFields["IMPORT_ID"]) <= 0)
				$aMsg[] = array("id"=>"IMPORT_ID", "text"=> GetMessage("IM_ERROR_IMPORT_ID"));

			if ($arFields["MESSAGE_TYPE"] == IM_MESSAGE_SYSTEM)
			{
				if(is_set($arFields, "NOTIFY_MODULE") && trim($arFields["NOTIFY_MODULE"]) == '')
					$aMsg[] = array("id"=>"NOTIFY_MODULE", "text"=> GetMessage("IM_ERROR_NOTIFY_MODULE"));

				if(is_set($arFields, "NOTIFY_EVENT") && trim($arFields["NOTIFY_EVENT"]) == '')
					$aMsg[] = array("id"=>"NOTIFY_EVENT", "text"=> GetMessage("IM_ERROR_NOTIFY_EVENT"));

				if(is_set($arFields, "NOTIFY_TYPE") && !in_array($arFields["NOTIFY_TYPE"], Array(IM_NOTIFY_CONFIRM, IM_NOTIFY_SYSTEM, IM_NOTIFY_FROM)))
					$aMsg[] = array("id"=>"NOTIFY_TYPE", "text"=> GetMessage("IM_ERROR_NOTIFY_TYPE"));

				if(is_set($arFields, "NOTIFY_TYPE") && $arFields["NOTIFY_TYPE"] == IM_NOTIFY_CONFIRM)
				{
					if(is_set($arFields, "NOTIFY_BUTTONS") && !is_array($arFields["NOTIFY_BUTTONS"]))
						$aMsg[] = array("id"=>"NOTIFY_BUTTONS", "text"=> GetMessage("IM_ERROR_NOTIFY_BUTTON"));
				}
				else if(is_set($arFields, "NOTIFY_TYPE") && $arFields["NOTIFY_TYPE"] == IM_NOTIFY_FROM)
				{
					if(!is_set($arFields, "FROM_USER_ID") || intval($arFields["FROM_USER_ID"]) <= 0)
						$aMsg[] = array("id"=>"FROM_USER_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_FROM"));
				}
			}
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function GetById($ID, $params = Array())
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "
			SELECT
				DISTINCT M.*,
				".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
				C.TYPE MESSAGE_TYPE,
				C.AUTHOR_ID CHAT_AUTHOR_ID,
				C.ENTITY_TYPE CHAT_ENTITY_TYPE,
				C.ENTITY_ID CHAT_ENTITY_ID,
				C.PARENT_ID CHAT_PARENT_ID,
				C.PARENT_MID CHAT_PARENT_MID,
				C.ENTITY_DATA_1 CHAT_ENTITY_DATA_1,
				C.ENTITY_DATA_2 CHAT_ENTITY_DATA_2,
				C.ENTITY_DATA_3 CHAT_ENTITY_DATA_3
			FROM b_im_message M
			LEFT JOIN b_im_chat C ON M.CHAT_ID = C.ID
			WHERE M.ID = ".$ID;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$param = CIMMessageParam::Get($arRes['ID']);
			$arRes['PARAMS'] = $param? $param: Array();
		}
		if ($arRes && $params['WITH_FILES'] == 'Y')
		{
			$arFiles = Array();
			if (isset($arRes['PARAMS']['FILE_ID']))
			{
				foreach ($arRes['PARAMS']['FILE_ID'] as $fileId)
				{
					$arFiles[$fileId] = $fileId;
				}
			}
			$arRes['FILES'] = CIMDisk::GetFiles($arRes['CHAT_ID'], $arFiles, false);
		}

		return $arRes;
	}

	public static function GetRelationById($ID)
	{
		global $DB;

		$ID = intval($ID);
		$arResult = Array();

		$strSql = "
			SELECT
				R.USER_ID, U.EXTERNAL_AUTH_ID, M.CHAT_ID
			FROM b_im_message M
			LEFT JOIN b_im_relation R ON M.CHAT_ID = R.CHAT_ID
			LEFT JOIN b_user U ON U.ID = R.USER_ID
			WHERE M.ID = ".$ID;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
			$arResult[$arRes['USER_ID']] = $arRes;

		return $arResult;
	}

	public static function CheckXmppStatusOnline()
	{
		If (IsModuleInstalled('xmpp'))
		{
			$LastActivityDate = CUserOptions::GetOption('xmpp', 'LastActivityDate');
			if (intval($LastActivityDate)+60 > time())
				return true;
		}
		return false;
	}

	public static function CheckEnableOpenChat()
	{
		return COption::GetOptionString('im', 'open_chat_enable');
	}

	public static function CheckNetwork()
	{
		return COption::GetOptionString('bitrix24', 'network', 'N') == 'Y';
	}

	public static function CheckNetwork2()
	{
		if (!CModule::IncludeModule('socialservices'))
			return false;

		$network = new \Bitrix\Socialservices\Network();
		return $network->isEnabled();
	}

	public static function CheckInstallDesktop()
	{
		$status = CIMStatus::GetStatus($userId);
		if ($status['DESKTOP_LAST_DATE'])
			return true;
		else
			return false;
	}

	public static function EnableInVersion($version)
	{
		$version = intval($version);
		$currentVersion = intval(CUserOptions::GetOption('im', 'DesktopVersionApi', 0));

		return $currentVersion >= $version;
	}

	public static function SetDesktopVersion($version)
	{
		global $USER;

		$version = intval($version);
		$userId = intval($USER->GetId());
		if ($userId <= 0)
			return false;

		$currentVersion = self::GetDesktopVersion();
		if ($currentVersion !== $version)
		{
			CUserOptions::SetOption('im', 'DesktopVersionApi', $version, false, $userId);
		}

		return $version;
	}

	public static function GetDesktopVersion()
	{
		$version = CUserOptions::GetOption('im', 'DesktopVersionApi', 0);

		return (int)$version;
	}

	public static function SetDesktopLastActivityDate($timestamp, $deviceType = IM_DESKTOP_WINDOWS, $userId = false)
	{
		if ($timestamp instanceof \Bitrix\Main\Type\DateTime)
		{
			$timestamp = $timestamp->getTimestamp();
		}
		else
		{
			$timestamp = (int)$timestamp;
		}

		CIMStatus::Set($userId, Array('DESKTOP_LAST_DATE' => \Bitrix\Main\Type\DateTime::createFromTimestamp($timestamp)));

		if ($deviceType === IM_DESKTOP_MAC)
		{
			$lastTimestamp = (int)CUserOptions::GetOption('im', 'MacLastActivityDate', -1, $userId);
			if ($lastTimestamp+86400*30 < time())
			{
				CUserOptions::SetOption('im', 'MacLastActivityDate', $timestamp, false, $userId);
			}
		}
		else
		{
			$lastTimestamp = (int)CUserOptions::GetOption('im', 'WindowsLastActivityDate', -1, $userId);
			if ($lastTimestamp+86400*30 < time())
			{
				CUserOptions::SetOption('im', 'WindowsLastActivityDate', $timestamp, false, $userId);
			}
		}
	}

	public static function CheckPhoneStatus()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant') || !\Bitrix\Main\Loader::includeModule('pull'))
			return false;

		return CPullOptions::GetNginxStatus() && (!IsModuleInstalled('extranet') || CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser());
	}

	public static function CanUserCallCrmNumber()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		return $userPermissions->canPerform(
			\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL,
			\Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM,
			\Bitrix\Voximplant\Security\Permissions::PERMISSION_CALL_CRM
		);
	}

	public static function CanUserCallUserNumber()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		return $userPermissions->canPerform(
			\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL,
			\Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM,
			\Bitrix\Voximplant\Security\Permissions::PERMISSION_CALL_USERS
		);
	}

	public static function CanUserCallAnyNumber()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		return $userPermissions->canPerform(
			\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL,
			\Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM,
			\Bitrix\Voximplant\Security\Permissions::PERMISSION_ANY
		);
	}

	public static function CanUserPerformCalls()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		return $userPermissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL, \Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM);
	}

	public static function CanInterceptCall()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		return \Bitrix\Voximplant\Limits::canInterceptCall();
	}


	public static function GetCallCardRestApps()
	{
		if(!\Bitrix\Main\Loader::includeModule('rest'))
			return array();

		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return array();

		$result = array();
		if(!defined('Bitrix\Voximplant\Rest\Helper::PLACEMENT_CALL_CARD'))
			return array();

		$placementId = Bitrix\Voximplant\Rest\Helper::PLACEMENT_CALL_CARD;
		$cursor = \Bitrix\Rest\PlacementTable::getHandlersList($placementId);
		foreach($cursor as $row)
		{
			$result[] = array(
				'id' => $row['ID'],
				'name' => $row['TITLE'] ?: $row['APP_NAME']
			);
		}
		return $result;
	}

	public static function GetTelephonyAvailableLines($userId = null)
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return array();

		if(is_null($userId))
			$userId = self::GetCurrentUserId();

		return \CVoxImplantUser::getAllowedLines($userId);
	}

	public static function GetDefaultTelephonyLine($userId = null)
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return array();

		if(is_null($userId))
			$userId = self::GetCurrentUserId();

		return CVoxImplantUser::getUserOutgoingLine($userId);
	}

	public static function CheckDesktopStatusOnline($userId = null)
	{
		global $USER;
		if (is_null($userId))
		{
			$userId = $USER->GetId();
		}

		$userId = intval($userId);
		if ($userId <= 0)
		{
			return false;
		}

		$maxOnlineTime = 120;
		if (CModule::IncludeModule('pull') && CPullOptions::GetNginxStatus())
		{
			$maxOnlineTime = self::GetSessionLifeTime();
		}

		$status = CIMStatus::GetStatus($userId);
		if (
			$status['DESKTOP_LAST_DATE'] instanceof \Bitrix\Main\Type\DateTime
			&& $status['DESKTOP_LAST_DATE']->getTimestamp()+$maxOnlineTime+60 > time()
		)
		{
			return true;
		}

		return false;
	}

	public static function GetDesktopStatusOnline($userId = null)
	{
		global $USER;
		if (is_null($userId))
		{
			$userId = $USER->GetId();
		}

		$userId = intval($userId);
		if ($userId <= 0)
		{
			return 0;
		}

		$status = CIMStatus::GetStatus($userId);
		if ($status['DESKTOP_LAST_DATE'] instanceof \Bitrix\Main\Type\DateTime)
		{
			return $status['DESKTOP_LAST_DATE']->getTimestamp();
		}

		return 0;
	}

	public static function SetDesktopStatusOnline($userId = null, $cache = true)
	{
		global $USER;

		if (is_null($userId))
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		if ($cache && $userId == $USER->GetId())
		{
			if (
				isset(\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_DESKTOP_ACTIVITY'])
				&& intval(\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_DESKTOP_ACTIVITY'])+300 > time()
			)
			{
				return false;
			}

			\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_DESKTOP_ACTIVITY'] = time();
		}

		$userAgent = \Bitrix\Main\Context::getCurrent()->getRequest()->getUserAgent();
		if (mb_strpos(mb_strtolower($userAgent), "windows") !== false)
		{
			$deviceType = IM_DESKTOP_WINDOWS;
		}
		elseif (mb_strpos(mb_strtolower($userAgent), "macintosh") !== false)
		{
			$deviceType = IM_DESKTOP_MAC;
		}

		$time = time();
		\CIMMessenger::SetDesktopLastActivityDate($time, $deviceType, $userId);

		if (CModule::IncludeModule("pull"))
		{
			if ($cache && $userId == $USER->GetId())
			{
				if (
					isset(\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_DESKTOP_ACTIVITY_PULL'])
					&& intval(\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_DESKTOP_ACTIVITY_PULL'])+3600 > $time
				)
				{
					return false;
				}

				\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_DESKTOP_ACTIVITY_PULL'] = $time;
			}

			\Bitrix\Pull\Event::add($userId, Array(
				'module_id' => 'im',
				'expiry' => 3600,
				'command' => 'desktopOnline',
				'params' => Array(),
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}

		return $time;
	}

	public static function SetDesktopStatusOffline($userId = null)
	{
		global $USER;

		if (is_null($userId))
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		unset(\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_DESKTOP_ACTIVITY']);
		unset(\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_DESKTOP_ACTIVITY_PULL']);

		CIMStatus::Set($userId, Array('DESKTOP_LAST_DATE' => null));

		if (CModule::IncludeModule("pull"))
		{
			\Bitrix\Pull\Event::add($userId, Array(
				'module_id' => 'im',
				'expiry' => 3600,
				'command' => 'desktopOffline',
				'params' => Array(),
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}

		return true;
	}

	public static function GetSettings($userId = false)
	{
		$arSettings = CIMSettings::Get($userId);
		return $arSettings['settings'];
	}

	public static function GetFormatFilesMessageOut($files)
	{
		if (!is_array($files) || count($files) <= 0)
			return false;

		$messageFiles = '';
		$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));
		foreach ($files as $fileId => $fileData)
		{
			if ($fileData['status'] == 'done')
			{
				$fileElement = $fileData['name'].' ('.CFile::FormatSize($fileData['size']).")\n".
								GetMessage('IM_MESSAGE_FILE_DOWN').' '.$serverName.$fileData['urlDownload']."\n";
				$messageFiles = $messageFiles <> ''? $messageFiles."\n".$fileElement: $fileElement;
			}
		}

		return $messageFiles;
	}

	public static function GetSessionLifeTime()
	{
		global $USER;

		$sessTimeout = CUser::GetSecondsForLimitOnline();
		if ($USER instanceof CUser)
		{
			$arPolicy = $USER->GetSecurityPolicy();
			if($arPolicy["SESSION_TIMEOUT"] > 0)
			{
				$sessTimeout = min($arPolicy["SESSION_TIMEOUT"]*60, $sessTimeout);
			}
		}

		$sessTimeout = intval($sessTimeout);
		if ($sessTimeout <= 120)
		{
			$sessTimeout = 100;
		}

		return intval($sessTimeout);
	}

	public static function GetUnreadCounter($userId)
	{
		$count = 0;
		$userId = intval($userId);
		if ($userId <= 0)
			return $count;

		global $DB;

		$strSql ="
			SELECT M.ID, M.NOTIFY_TYPE, M.NOTIFY_TAG
			FROM b_im_message M
			INNER JOIN b_im_relation R1 ON M.ID > R1.LAST_ID AND M.CHAT_ID = R1.CHAT_ID AND R1.USER_ID != M.AUTHOR_ID
			WHERE R1.USER_ID = ".$userId."  AND R1.STATUS < ".IM_STATUS_READ."
		";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arGroupNotify = Array();
		while ($arRes = $dbRes->Fetch())
		{
			if ($arRes['NOTIFY_TYPE'] == 2 && $arRes['NOTIFY_TAG'] != '')
			{
				if (!isset($arGroupNotify[$arRes['NOTIFY_TAG']]))
				{
					$arGroupNotify[$arRes['NOTIFY_TAG']] = true;
					$count++;
				}
			}
			else
				$count++;
		}

		return $count;
	}

	public static function GetMessageCounter($userId, $arMessages = Array())
	{
		$count = 0;
		if (isset($arMessages['message']))
		{
			foreach ($arMessages['message'] as $value)
				$count += isset($value['counter'])? $value['counter']: 1;
		}
		else
		{
			$count = CIMMessenger::SpeedFileGet($userId, IM_SPEED_MESSAGE);
		}

		return intval($count);
	}

	private static function GetReasonForMessageSendError($type = IM_MESSAGE_PRIVATE, $reason = '')
	{
		if (!empty($reason))
		{
			$CBXSanitizer = new CBXSanitizer;
			$CBXSanitizer->AddTags(array(
				'a' => array('href','style', 'target'),
				'b' => array(), 'u' => array(),
				'i' => array(), 'br' => array(),
				'span' => array('style'),
			));
			$reason = $CBXSanitizer->SanitizeHtml($reason);
		}
		else
		{
			if ($type == IM_MESSAGE_PRIVATE)
			{
				$reason = GetMessage("IM_ERROR_MESSAGE_CANCELED");
			}
			else if ($type == IM_MESSAGE_SYSTEM)
			{
				$reason = GetMessage("IM_ERROR_NOTIFY_CANCELED");
			}
			else
			{
				$reason = GetMessage("IM_ERROR_GROUP_CANCELED");
			}
		}

		return $reason;
	}

	public static function SpeedFileCreate($userID, $value, $type = IM_SPEED_MESSAGE)
	{
		global $CACHE_MANAGER;

		$userID = intval($userID);
		if ($userID <= 0 || !in_array($type, Array(IM_SPEED_NOTIFY, IM_SPEED_MESSAGE, IM_SPEED_GROUP)))
			return false;

		$CACHE_MANAGER->Clean("im_csf_v2_".$type.'_'.$userID);
		$CACHE_MANAGER->Read(86400*30, "im_csf_v2_".$type.'_'.$userID);
		$CACHE_MANAGER->Set("im_csf_v2_".$type.'_'.$userID, $value);
	}

	public static function SpeedFileDelete($userID, $type = IM_SPEED_MESSAGE)
	{
		global $CACHE_MANAGER;

		$userID = intval($userID);
		if ($userID <= 0 || !in_array($type, Array(IM_SPEED_NOTIFY, IM_SPEED_MESSAGE, IM_SPEED_GROUP)))
			return false;

		$CACHE_MANAGER->Clean("im_csf_v2_".$type.'_'.$userID);
	}

	public static function SpeedFileExists($userID, $type = IM_SPEED_MESSAGE)
	{
		global $CACHE_MANAGER;

		$userID = intval($userID);
		if ($userID <= 0 || !in_array($type, Array(IM_SPEED_NOTIFY, IM_SPEED_MESSAGE, IM_SPEED_GROUP)))
			return false;

		$result = $CACHE_MANAGER->Read(86400*30, "im_csf_v2_".$type.'_'.$userID);
		if ($result)
			$result = $CACHE_MANAGER->Get("im_csf_v2_".$type.'_'.$userID) === false? false: true;

		return $result;
	}

	public static function SpeedFileGet($userID, $type = IM_SPEED_MESSAGE)
	{
		global $CACHE_MANAGER;

		$userID = intval($userID);
		if ($userID <= 0 || !in_array($type, Array(IM_SPEED_NOTIFY, IM_SPEED_MESSAGE, IM_SPEED_GROUP)))
			return false;

		$CACHE_MANAGER->Read(86400*30, "im_csf_v2_".$type.'_'.$userID);
		return $CACHE_MANAGER->Get("im_csf_v2_".$type.'_'.$userID);
	}

	public static function GetTemplateJS($arParams, $arTemplate)
	{
		global $USER;

		$ppStatus = 'false';
		$ppServerStatus = 'false';
		$updateStateInterval = 'auto';
		if (CModule::IncludeModule("pull"))
		{
			$ppStatus = CPullOptions::ModuleEnable()? 'true': 'false';
			$ppServerStatus = CPullOptions::GetNginxStatus()? 'true': 'false';

			$updateStateInterval = CPullOptions::GetNginxStatus()? self::GetSessionLifeTime(): 80;
			if ($updateStateInterval > 100)
			{
				if ($updateStateInterval > 3600)
					$updateStateInterval = 3600;

				if (in_array($arTemplate["CONTEXT"], Array("POPUP-FULLSCREEN", "MESSENGER")))
					$updateStateInterval = $updateStateInterval-60;
				else
					$updateStateInterval = intval($updateStateInterval/2)-10;
			}
		}

		$diskStatus = CIMDisk::Enabled();
		$diskExternalLinkStatus = CIMDisk::EnabledExternalLink();

		$phoneCanPerformCalls = false;
		$phoneDeviceActive = false;
		$phoneCanCallUserNumber = false;
		$phoneEnabled = false;
		$chatExtendShowHistory = \COption::GetOptionInt('im', 'chat_extend_show_history');
		$contactListLoad = \COption::GetOptionInt('im', 'contact_list_load');
		$contactListBirthday = \COption::GetOptionString('im', 'contact_list_birthday');
		$isFullTextEnabled = \Bitrix\Im\Model\MessageIndexTable::getEntity()->fullTextIndexEnabled("SEARCH_CONTENT");
		$fullTextMinSizeToken = \Bitrix\Main\ORM\Query\Filter\Helper::getMinTokenSize();
		$phoneCanInterceptCall = self::CanInterceptCall();

		if ($arTemplate['INIT'] == 'Y')
		{
			$phoneEnabled = self::CheckPhoneStatus();
			if ($phoneEnabled && CModule::IncludeModule('voximplant'))
			{
				$phoneCanPerformCalls = self::CanUserPerformCalls();
				$phoneDeviceActive = CVoxImplantUser::GetPhoneActive($USER->GetId());
				$phoneCanCallUserNumber = self::CanUserCallUserNumber();
			}
		}

		$counters = \Bitrix\Im\Counter::get(null, ['JSON' => 'Y']);
		$counters['type']['mail'] = (int)$arResult["MAIL_COUNTER"];

		$crmPath = Array();
		$olConfig = Array();
		$businessUsers = false;

		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$crmPath['LEAD'] = \Bitrix\Im\Integration\Crm\Common::getLink('LEAD');
			$crmPath['CONTACT'] = \Bitrix\Im\Integration\Crm\Common::getLink('CONTACT');
			$crmPath['COMPANY'] = \Bitrix\Im\Integration\Crm\Common::getLink('COMPANY');
			$crmPath['DEAL'] = \Bitrix\Im\Integration\Crm\Common::getLink('DEAL');
		}

		if (\Bitrix\Main\Loader::includeModule('imopenlines'))
		{
			$olConfig['canDeleteMessage'] = str_replace('.', '_', \Bitrix\Imopenlines\Connector::getListCanDeleteMessage());
			$olConfig['canDeleteOwnMessage'] = str_replace('.', '_', \Bitrix\Imopenlines\Connector::getListCanDeleteOwnMessage());
			$olConfig['canUpdateOwnMessage'] = str_replace('.', '_', \Bitrix\Imopenlines\Connector::getListCanUpdateOwnMessage());

			$olConfig['queue'] = Array();
			foreach (\Bitrix\ImOpenLines\Config::getQueueList($USER->GetID()) as $config)
			{
				$olConfig['queue'][] = array_change_key_case($config, CASE_LOWER);
			}

			$olConfig['canUseVoteHead'] = \Bitrix\Im\Integration\Imopenlines\Limit::canUseVoteHead();
			$olConfig['canJoinChatUser'] = \Bitrix\Im\Integration\Imopenlines\Limit::canJoinChatUser();
			$olConfig['canTransferToLine'] = \Bitrix\Im\Integration\Imopenlines\Limit::canTransferToLine();
		}

		$bitrix24blocked = false;
		$bitrix24Enabled = false;
		$bitrixPaid = true;
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			$bitrixPaid = CBitrix24::IsLicensePaid();
			if (CIMMessenger::IsBitrix24UserRestricted())
			{
				$bitrix24blocked = \Bitrix\Bitrix24\Limits\User::getUserRestrictedHelperCode();
			}
		}

		$userBirthday = \Bitrix\Im\Integration\Intranet\User::getBirthdayForToday();

		$pathToIm = isset($arTemplate['PATH_TO_IM']) ? $arTemplate['PATH_TO_IM'] : '';
		$pathToCall = isset($arTemplate['PATH_TO_CALL']) ? $arTemplate['PATH_TO_CALL'] : '';
		$pathToFile = isset($arTemplate['PATH_TO_FILE']) ? $arTemplate['PATH_TO_FILE'] : '';
		$pathToLf = isset($arTemplate['PATH_TO_LF']) ? $arTemplate['PATH_TO_LF'] : '/';
		$pathToDisk = Array(
			'localFile' => \CIMDisk::GetLocalDiskFilePath(),
		);

		$userColor = isset($arTemplate['CONTACT_LIST']['users'][$USER->GetID()]['color']) ? $arTemplate['CONTACT_LIST']['users'][$USER->GetID()]['color']: '';

		$isOperator = \Bitrix\Im\Integration\Imopenlines\User::isOperator();

		$recentLastUpdate = (new \Bitrix\Main\Type\DateTime())->format(\DateTimeInterface::RFC3339);
		$recent = \Bitrix\Im\Recent::getList(null, [
			'SKIP_NOTIFICATION' => 'Y',
			'SKIP_OPENLINES' => ($isOperator? 'Y': 'N'),
			'JSON' => 'Y'
		]);

		$sJS = "
			BX.ready(function() {
				BXIM = new BX.IM(BX('bx-notifier-panel'), {
					'init': ".($arTemplate['INIT'] == 'Y'? 'true': 'false').",
					'context': '".$arTemplate["CONTEXT"]."',
					'design': '".$arTemplate["DESIGN"]."',
					'colors': ".(\Bitrix\Im\Color::isEnabled()? \Bitrix\Im\Common::objectEncode(\Bitrix\Im\Color::getSafeColorNames()): 'false').",
					'colorsHex': ".\Bitrix\Im\Common::objectEncode(\Bitrix\Im\Color::getSafeColors()).",
					'chatCounters': ".\Bitrix\Im\Common::objectEncode($counters, true).",
					'counters': ".(empty($arTemplate['COUNTERS'])? '{}': \Bitrix\Im\Common::objectEncode($arTemplate['COUNTERS'])).",
					'ppStatus': ".$ppStatus.",
					'ppServerStatus': ".$ppServerStatus.",
					'updateStateInterval': '".$updateStateInterval."',
					'openChatEnable': ".(CIMMessenger::CheckEnableOpenChat()? 'true': 'false').",
					'xmppStatus': ".(CIMMessenger::CheckXmppStatusOnline()? 'true': 'false').",
					'isAdmin': ".(self::IsAdmin()? 'true': 'false').",
					'canInvite': ".(\Bitrix\Im\Integration\Intranet\User::canInvite()? 'true': 'false').",
					'isLinesOperator': ".($isOperator? 'true': 'false').",
					'isUtfMode': ".(\Bitrix\Main\Application::getInstance()->isUtfMode()? 'true': 'false').",
					'bitrixNetwork': ".(CIMMessenger::CheckNetwork()? 'true': 'false').",
					'bitrixNetwork2': ".(CIMMessenger::CheckNetwork2()? 'true': 'false').",
					'bitrix24': ".($bitrix24Enabled? 'true': 'false').",
					'bitrix24blocked': ".($bitrix24blocked? $bitrix24blocked: 'false').",
					'bitrix24net': ".(IsModuleInstalled('b24network')? 'true': 'false').",
					'bitrixPaid': ".($bitrixPaid? 'true': 'false').",
					'bitrixIntranet': ".(IsModuleInstalled('intranet')? 'true': 'false').",
					'bitrixXmpp': ".(IsModuleInstalled('xmpp')? 'true': 'false').",
					'bitrixMobile': ".(IsModuleInstalled('mobile')? 'true': 'false').",
					'bitrixOpenLines': ".(IsModuleInstalled('imopenlines')? 'true': 'false').",
					'desktop': ".$arTemplate["DESKTOP"].",
					'desktopStatus': ".(CIMMessenger::CheckDesktopStatusOnline()? 'true': 'false').",
					'desktopVersion': ".CIMMessenger::GetDesktopVersion().",
					'desktopLinkOpen': ".$arTemplate["DESKTOP_LINK_OPEN"].",
					'language': '".LANGUAGE_ID."',
					'loggerConfig': ".\Bitrix\Im\Common::objectEncode(\Bitrix\Im\Settings::getLoggerConfig(), true).",
					'broadcastingEnabled': ".\Bitrix\Im\Common::objectEncode(\Bitrix\Im\Settings::isBroadcastingEnabled(), true).",
					'tooltipShowed': ".\Bitrix\Im\Common::objectEncode(CUserOptions::GetOption('im', 'tooltipShowed', array())).",
					'limit': ".(empty($arTemplate['LIMIT'])? 'false': \Bitrix\Im\Common::objectEncode($arTemplate["LIMIT"])).",
					'promo': ".(empty($arTemplate['PROMO'])? '[]': \Bitrix\Im\Common::objectEncode($arTemplate["PROMO"])).",
					'bot': ".(empty($arTemplate['BOT'])? '{}': \Bitrix\Im\Common::objectEncode($arTemplate["BOT"])).",
					'textareaIcon': ".(empty($arTemplate['TEXTAREA_ICON'])? '{}': \Bitrix\Im\Common::objectEncode($arTemplate["TEXTAREA_ICON"])).",
					'command': ".(empty($arTemplate['COMMAND'])? '[]': \Bitrix\Im\Common::objectEncode($arTemplate["COMMAND"])).",

					'smile': ".\Bitrix\Im\Common::objectEncode($arTemplate["SMILE"]).",
					'smileSet': ".\Bitrix\Im\Common::objectEncode($arTemplate["SMILE_SET"]).",
					'settings': ".\Bitrix\Im\Common::objectEncode($arTemplate['SETTINGS']).",
					'settingsNotifyBlocked': ".(empty($arTemplate['SETTINGS_NOTIFY_BLOCKED'])? '{}': \Bitrix\Im\Common::objectEncode($arTemplate['SETTINGS_NOTIFY_BLOCKED'])).",

					'recent': ".\Bitrix\Im\Common::objectEncode($recent, true).",
					'recentLastUpdate': '".$recentLastUpdate."',
					'businessUsers': ".($businessUsers === false? 'false': (empty($businessUsers)? '{}': \Bitrix\Im\Common::objectEncode($businessUsers))).",
					'userChatOptions': ".\Bitrix\Im\Common::objectEncode(CIMChat::GetChatOptions()).",
					'historyOptions' : ".\Bitrix\Im\Common::objectEncode(['fullTextEnabled' => $isFullTextEnabled, 'ftMinSizeToken' => $fullTextMinSizeToken]).",
					'openMessenger' : ".(isset($_REQUEST['IM_DIALOG'])? "'".CUtil::JSEscape(htmlspecialcharsbx($_REQUEST['IM_DIALOG']))."'": 'false').",
					'openHistory' : ".(isset($_REQUEST['IM_HISTORY'])? "'".CUtil::JSEscape(htmlspecialcharsbx($_REQUEST['IM_HISTORY']))."'": 'false').",
					'openNotify' : ".(isset($_GET['IM_NOTIFY']) && $_GET['IM_NOTIFY'] == 'Y'? 'true': 'false').",
					'openSettings' : ".(isset($_GET['IM_SETTINGS'])? $_GET['IM_SETTINGS'] == 'Y'? "'true'": "'".CUtil::JSEscape(htmlspecialcharsbx($_GET['IM_SETTINGS']))."'": 'false').",
					'externalRecentList' : '".(isset($arTemplate['EXTERNAL_RECENT_LIST'])?$arTemplate['EXTERNAL_RECENT_LIST']: '')."',

					'generalChatId': ".CIMChat::GetGeneralChatId().",
					'canSendMessageGeneralChat': ".(CIMChat::CanSendMessageToGeneralChat($USER->GetID())? 'true': 'false').",
					'debug': ".(defined('IM_DEBUG')? 'true': 'false').",
					'next': ".(defined('IM_NEXT')? 'true': 'false').",
					'userId': ".$USER->GetID().",
					'userEmail': '".CUtil::JSEscape($USER->GetEmail())."',
					'userColor': '".\Bitrix\Im\Color::getCode($userColor)."',
					'userGender': '".\Bitrix\Im\User::getInstance()->getGender()."',
					'userExtranet': ".(\Bitrix\Im\User::getInstance()->isExtranet()? 'true': 'false').",
					'user': ".($arTemplate['CURRENT_USER']? \Bitrix\Im\Common::objectEncode($arTemplate['CURRENT_USER']): '{}').",
					'userBirthday': ".(!empty($userBirthday)? \Bitrix\Im\Common::objectEncode($userBirthday): '[]').",
					'webrtc': {
						'turnServer' : '".CUtil::JSEscape($arTemplate['TURN_SERVER'])."', 
						'turnServerFirefox' : '".CUtil::JSEscape($arTemplate['TURN_SERVER_FIREFOX'])."', 
						'turnServerLogin' : '".CUtil::JSEscape($arTemplate['TURN_SERVER_LOGIN'])."', 
						'turnServerPassword' : '".CUtil::JSEscape($arTemplate['TURN_SERVER_PASSWORD'])."', 
						'mobileSupport': false, 
						'phoneEnabled': ".($phoneEnabled? 'true': 'false').", 
						'phoneDeviceActive': '".($phoneDeviceActive? 'Y': 'N')."', 
						'phoneCanPerformCalls': '".($phoneCanPerformCalls? 'Y': 'N')."', 
						'phoneCanCallUserNumber': '".($phoneCanCallUserNumber? 'Y': 'N')."', 
						'phoneCanInterceptCall': ".($phoneCanInterceptCall? 'true': 'false').", 
						'phoneCallCardRestApps': ".\Bitrix\Im\Common::objectEncode(self::GetCallCardRestApps()).",
						'phoneDefaultLineId': '".self::GetDefaultTelephonyLine()."',
						'availableLines': ".\Bitrix\Im\Common::objectEncode(self::GetTelephonyAvailableLines()).",
						'formatRecordDate': '".\Bitrix\Main\Context::getCurrent()->getCulture()->getShortDateFormat()."'						
					},
					'openlines': ".\Bitrix\Im\Common::objectEncode($olConfig).",
					'options': {'contactListLoad' : ".($contactListLoad? 'true': 'false').", 'contactListBirthday' : '".$contactListBirthday."', 'chatExtendShowHistory' : ".($chatExtendShowHistory? 'true': 'false').", 'frameMode': ".($_REQUEST['IFRAME'] == 'Y'? 'true': 'false').", 'frameType': '".($_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER'? 'SIDE_SLIDER': 'NONE')."', 'showRecent': ".($_REQUEST['IM_RECENT'] == 'N'? 'false': 'true').", 'showMenu': ".($_REQUEST['IM_MENU'] == 'N'? 'false': 'true')."},
					'disk': {'enable' : ".($diskStatus? 'true': 'false').", 'external' : ".($diskExternalLinkStatus? 'true': 'false')."},
					'zoomStatus': {'active' : ".(\Bitrix\Im\Call\Integration\Zoom::isActive()? 'true': 'false').", 'enabled' : ".(\Bitrix\Im\Call\Integration\Zoom::isAvailable()? 'true': 'false').", 'connected' : ".(\Bitrix\Im\Call\Integration\Zoom::isConnected($USER->GetID())? 'true': 'false')."},
					'path' : {'lf' : '".CUtil::JSEscape($pathToLf)."', 'profile' : '".CUtil::JSEscape($arTemplate['PATH_TO_USER_PROFILE'])."', 'profileTemplate' : '".CUtil::JSEscape($arTemplate['PATH_TO_USER_PROFILE_TEMPLATE'])."', 'mail' : '".CUtil::JSEscape($arTemplate['PATH_TO_USER_MAIL'])."', 'im': '".CUtil::JSEscape($pathToIm)."', 'call': '".CUtil::JSEscape($pathToCall)."', 'file': '".CUtil::JSEscape($pathToFile)."', 'crm' : ".\Bitrix\Im\Common::objectEncode($crmPath).", 'disk' : ".\Bitrix\Im\Common::objectEncode($pathToDisk)."}
				});
			});
		";

		return $sJS;
	}

	public static function GetMobileDialogTemplateJS($arParams, $arTemplate)
	{
		global $USER;

		$ppStatus = false;
		$ppServerStatus = false;
		$updateStateInterval = 'auto';
		if (CModule::IncludeModule("pull"))
		{
			$ppStatus = (bool)CPullOptions::ModuleEnable();
			$ppServerStatus = (bool)CPullOptions::GetNginxStatus();
			$updateStateInterval = CPullOptions::GetNginxStatus()? self::GetSessionLifeTime(): 80;
			if ($updateStateInterval > 100)
			{
				if ($updateStateInterval > 3600)
					$updateStateInterval = 3600;

				$updateStateInterval = $updateStateInterval-60;
			}
		}

		$diskStatus = CIMDisk::Enabled();
		$diskExternalLinkStatus = CIMDisk::EnabledExternalLink();

		$chatExtendShowHistory = \COption::GetOptionInt('im', 'chat_extend_show_history');

		$phoneEnabled = self::CheckPhoneStatus() && CModule::IncludeModule('mobileapp') && \Bitrix\MobileApp\Mobile::getInstance()->isWebRtcSupported();

		$olConfig = Array();
		$crmPath = Array();
		$businessUsers = false;

		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$crmPath['LEAD'] = \Bitrix\Im\Integration\Crm\Common::getLink('LEAD');
			$crmPath['CONTACT'] = \Bitrix\Im\Integration\Crm\Common::getLink('CONTACT');
			$crmPath['COMPANY'] = \Bitrix\Im\Integration\Crm\Common::getLink('COMPANY');
			$crmPath['DEAL'] = \Bitrix\Im\Integration\Crm\Common::getLink('DEAL');
		}

		if (CModule::IncludeModule('imopenlines'))
		{
			$olConfig['canDeleteMessage'] = str_replace('.', '_', \Bitrix\Imopenlines\Connector::getListCanDeleteMessage());
			$olConfig['canDeleteOwnMessage'] = str_replace('.', '_', \Bitrix\Imopenlines\Connector::getListCanDeleteOwnMessage());
			$olConfig['canUpdateOwnMessage'] = str_replace('.', '_', \Bitrix\Imopenlines\Connector::getListCanUpdateOwnMessage());
			$olConfig['queue'] = Array();
			foreach (\Bitrix\ImOpenLines\Config::getQueueList($USER->GetID()) as $config)
			{
				$olConfig['queue'][] = array_change_key_case($config, CASE_LOWER);
			}
		}

		$mobileAction = isset($arTemplate["ACTION"])? $arTemplate["ACTION"]: 'none';
		$mobileCallMethod = isset($arTemplate["CALL_METHOD"])? $arTemplate["CALL_METHOD"]: 'device';

		$initConfig = [
			'mobileAction' => $mobileAction,
			'mobileCallMethod' => $mobileCallMethod,
			'colors' => \Bitrix\Im\Color::isEnabled()? \Bitrix\Im\Color::getSafeColorNames(): false,
			'chatCounters' => [
				'notify' => intval($counters['TYPE']['NOTIFY']),
				'dialog' => intval($counters['TYPE']['DIALOG']),
				'chat' => intval($counters['TYPE']['CHAT']),
				'lines' => intval($counters['TYPE']['LINES']),
				'mail' => intval($arTemplate["MAIL_COUNTER"])
			],
			'counters' => empty($arTemplate['COUNTERS'])? false: $arTemplate['COUNTERS'],
			'ppStatus' => $ppStatus,
			'ppServerStatus' => $ppServerStatus,
			'updateStateInterval' => $updateStateInterval,
			'openChatEnable' => (bool)CIMMessenger::CheckEnableOpenChat(),
			'xmppStatus' => (bool)CIMMessenger::CheckXmppStatusOnline(),
			'isAdmin' => (bool)self::IsAdmin(),
			'bitrixNetwork' => (bool)CIMMessenger::CheckNetwork(),
			'bitrixNetwork2' => (bool)CIMMessenger::CheckNetwork2(),
			'bitrix24' => (bool)IsModuleInstalled('bitrix24'),
			'bitrix24net' => (bool)IsModuleInstalled('b24network'),
			'bitrixIntranet' => (bool)IsModuleInstalled('intranet'),
			'bitrixXmpp' => (bool)IsModuleInstalled('xmpp'),
			'bitrixMobile' => (bool)IsModuleInstalled('mobile'),
			'bitrixOpenLines' => (bool)IsModuleInstalled('imopenlines'),
			'desktopStatus' => (bool)CIMMessenger::CheckDesktopStatusOnline(),
			'desktopVersion' => (int)CIMMessenger::GetDesktopVersion(),
			'language' => LANGUAGE_ID,

			'bot' => empty($arTemplate['BOT'])? false: $arTemplate["BOT"],
			'command' => empty($arTemplate['COMMAND'])? false: $arTemplate["COMMAND"],
			'textareaIcon' => empty($arTemplate['TEXTAREA_ICON'])? false: $arTemplate["TEXTAREA_ICON"],

			'smile' => empty($arTemplate['SMILE'])? false: $arTemplate["SMILE"],
			'smileSet' => empty($arTemplate['SMILE_SET'])? false: $arTemplate["SMILE_SET"],
			'settings' => empty($arTemplate['SETTINGS'])? false: $arTemplate['SETTINGS'],
			'settingsNotifyBlocked' => empty($arTemplate['SETTINGS_NOTIFY_BLOCKED'])? false: $arTemplate['SETTINGS_NOTIFY_BLOCKED'],

			'recent' => false,
			'businessUsers' => $businessUsers === false? false: (empty($businessUsers)? null: $businessUsers),
			'userChatOptions' => CIMChat::GetChatOptions(),
			'history' => false,
			'openMessenger' => false,
			'openHistory' => false,
			'openNotify' => false,
			'openSettings' => false,

			'generalChatId' => CIMChat::GetGeneralChatId(),
			'canSendMessageGeneralChat' => (bool)CIMChat::CanSendMessageToGeneralChat($USER->GetID()),
			'userId' => $USER->GetID(),
			'userEmail' => $USER->GetEmail(),
			'userColor' => \Bitrix\Im\Color::getCode(\Bitrix\Im\User::getInstance()->getColor()),
			'userGender' => \Bitrix\Im\User::getInstance()->getGender(),
			'userExtranet' => (bool)\Bitrix\Im\User::getInstance()->isExtranet(),
			'webrtc' => [
				'turnServer' => empty($arTemplate['TURN_SERVER'])? '': $arTemplate['TURN_SERVER'],
				'turnServerLogin' => empty($arTemplate['TURN_SERVER_LOGIN'])? '': $arTemplate['TURN_SERVER_LOGIN'],
				'turnServerPassword' => empty($arTemplate['TURN_SERVER_PASSWORD'])? '': $arTemplate['TURN_SERVER_PASSWORD'],
				'mobileSupport' => (bool)$arTemplate['WEBRTC_MOBILE_SUPPORT'],
				'phoneEnabled' => (bool)$phoneEnabled,
			],
			'openlines' => $olConfig,
			'options' => [
				'chatExtendShowHistory' => (bool)$chatExtendShowHistory
			],
			'disk' => [
				'enable' => (bool)$diskStatus,
				'external' => (bool)$diskExternalLinkStatus
			],
			'path' => [
				'lf' => empty($arTemplate['PATH_TO_LF'])? '/': $arTemplate['PATH_TO_LF'],
				'profile' => empty($arTemplate['PATH_TO_USER_PROFILE'])? '': $arTemplate['PATH_TO_USER_PROFILE'],
				'profileTemplate' => empty($arTemplate['PATH_TO_USER_PROFILE_TEMPLATE'])? '': $arTemplate['PATH_TO_USER_PROFILE_TEMPLATE'],
				'mail' => empty($arTemplate['PATH_TO_USER_MAIL'])? '': $arTemplate['PATH_TO_USER_MAIL'],
				'crm' => $crmPath
			]
		];

		return $initConfig;
	}

	public static function StartWriting($dialogId, $userId = false, $userName = "", $byEvent = false, $linesSilentMode = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
		{
			global $USER;
			$userId = intval($USER->GetID());
		}

		if (mb_substr($dialogId, 0, 4) == 'chat')
		{
		}
		else if ($dialogId == $userId)
		{
			return false;
		}
		else
		{
			$dialogId = intval($dialogId);
		}
		if (!$userName)
		{
			$userName = \Bitrix\Im\User::getInstance($userId)->getFullName();
		}

		if ($userId > 0 && $dialogId <> '' && CModule::IncludeModule("pull"))
		{
			$chat = Array();
			$relation = Array();
			if (mb_substr($dialogId, 0, 4) == 'chat')
			{
				$orm = \Bitrix\Im\Model\ChatTable::getById(mb_substr($dialogId, 4));
				$chat = $orm->fetch();

				$arRelation = CIMChat::GetRelationById(mb_substr($dialogId, 4));
				$relation = $arRelation;

				if ($chat['ENTITY_TYPE'] != 'LIVECHAT' && !isset($arRelation[$userId]))
				{
					return false;
				}

				unset($arRelation[$userId]);

				$pullMessage = Array(
					'module_id' => 'im',
					'command' => 'startWriting',
					'expiry' => 60,
					'params' => Array(
						'dialogId' => $dialogId,
						'userId' => $userId,
						'userName' => $userName
					),
					'extra' => \Bitrix\Im\Common::getPullExtra()
				);

				$chatId = $chat['ID'];
				$entityType = $chat['ENTITY_TYPE'];
				$entityId = $chat['ENTITY_ID'];
				if ($chat['ENTITY_TYPE'] == 'LINES')
				{
					foreach ($arRelation as $rel)
					{
						if ($rel["EXTERNAL_AUTH_ID"] == 'imconnector')
						{
							unset($arRelation[$rel["USER_ID"]]);
						}
					}
				}
				\Bitrix\Pull\Event::add(array_keys($arRelation), $pullMessage);

				if ($chat['TYPE'] == IM_MESSAGE_OPEN || $chat['TYPE'] == IM_MESSAGE_OPEN_LINE)
				{
					CPullWatch::AddToStack('IM_PUBLIC_'.$chatId, $pullMessage);
				}
			}
			else if (intval($dialogId) > 0)
			{
				\Bitrix\Pull\Event::add($dialogId, Array(
					'module_id' => 'im',
					'command' => 'startWriting',
					'expiry' => 60,
					'params' => Array(
						'dialogId' => $userId,
						'userId' => $userId,
						'userName' => $userName
					),
					'extra' => \Bitrix\Im\Common::getPullExtra()
				));
			}

			foreach(GetModuleEvents("im", "OnStartWriting", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array(array(
					'DIALOG_ID' => $dialogId,
					'CHAT' => $chat,
					'RELATION' => $relation,
					'USER_ID' => $userId,
					'USER_NAME' => $userName,
					'BY_EVENT' => $byEvent,
					'LINES_SILENT_MODE' => $linesSilentMode
				)));
			}

			return true;
		}
		return false;
	}

	public static function PrepareSmiles()
	{
		return CSmileGallery::getSmilesWithSets();
	}

	private static function UploadFileFromText($params)
	{
		$params['CHAT_ID'] = intval($params['CHAT_ID']);
		if (empty($params['MESSAGE']) || $params['CHAT_ID'] <= 0)
		{
			return $params;
		}

		if (preg_match_all("/\[DISK=([0-9]+)\]/i", $params['MESSAGE'], $matches))
		{
			$fileFound = false;
			foreach ($matches[1] as $fileId)
			{
				$newFile = CIMDisk::SaveFromLocalDisk($params['CHAT_ID'], $fileId);
				if ($newFile)
				{
					$fileFound = true;
					$params['PARAMS']['FILE_ID'][] = $newFile->getId();
				}
			}
			if ($fileFound)
			{
				$params['MESSAGE'] = preg_replace("/\[DISK\=([0-9]+)\]/i", "", $params['MESSAGE']);
			}
		}

		return $params;
	}

	public static function SendMention($params)
	{
		$params['CHAT_ID'] = intval($params['CHAT_ID']);
		if (!isset($params['MESSAGE']) || $params['CHAT_ID'] <= 0)
		{
			return false;
		}

		$userName = \Bitrix\Im\User::getInstance($params['FROM_USER_ID'])->getFullName(false);
		$userGender = \Bitrix\Im\User::getInstance($params['FROM_USER_ID'])->getGender();

		if (!$userName)
		{
			return false;
		}

		$orm = \Bitrix\Im\Model\ChatTable::getById($params['CHAT_ID']);
		$chat = $orm->fetch();
		if (!$chat)
		{
			return false;
		}

		$params['CHAT_TITLE'] = $chat['TITLE'];
		$params['CHAT_TYPE'] = trim($chat['TYPE']);
		$params['CHAT_COLOR'] = trim($chat['COLOR']);
		$params['CHAT_ENTITY_TYPE'] = trim($chat['CHAT_ENTITY_TYPE']);
		$params['CHAT_AVATAR'] = intval($chat['AVATAR']);

		if (!in_array($params['CHAT_TYPE'], Array(IM_MESSAGE_OPEN, IM_MESSAGE_CHAT, IM_MESSAGE_OPEN_LINE)))
		{
			return false;
		}

		if (!isset($params['CHAT_RELATION']))
		{
			$params['CHAT_RELATION'] = CIMChat::GetRelationById($params['CHAT_ID']);
		}

		$forUsers = Array();
		if (preg_match_all("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", $params['MESSAGE'], $matches))
		{
			if ($params['CHAT_TYPE'] == IM_MESSAGE_OPEN)
			{
				foreach($matches[1] as $userId)
				{
					if (!CIMSettings::GetNotifyAccess($userId, 'im', 'mention', CIMSettings::CLIENT_SITE))
					{
						continue;
					}

					if (
						!isset($params['CHAT_RELATION'][$userId])
						|| $params['CHAT_RELATION'][$userId]['NOTIFY_BLOCK'] == 'Y'
					)
					{
						$forUsers[$userId] = $userId;
					}
				}
			}
			else
			{
				foreach($matches[1] as $userId)
				{
					if (!CIMSettings::GetNotifyAccess($userId, 'im', 'mention', CIMSettings::CLIENT_SITE))
					{
						continue;
					}

					if (
						isset($params['CHAT_RELATION'][$userId])
						&& $params['CHAT_RELATION'][$userId]['NOTIFY_BLOCK'] == 'Y'
					)
					{
						$forUsers[$userId] = $userId;
					}
				}
			}
		}

		$chatTitle = mb_substr(htmlspecialcharsback($params['CHAT_TITLE']), 0, 32);
		$notifyMail = GetMessage('IM_MESSAGE_MENTION_'.($userGender=='F'?'F':'M'), Array('#TITLE#' => $chatTitle));
		$notifyText = GetMessage('IM_MESSAGE_MENTION_'.($userGender=='F'?'F':'M'), Array('#TITLE#' => '[CHAT='.$params['CHAT_ID'].']'.$chatTitle.'[/CHAT]'));
		$pushText = GetMessage('IM_MESSAGE_MENTION_PUSH_2_'.($userGender=='F'?'F':'M'), Array('#USER#' => $userName, '#TITLE#' => $chatTitle)).': '.self::PrepareParamsForPush(Array('MESSAGE' => $params['MESSAGE'], 'FILES' => $params['FILES']));

		if ($pushText <> '')
		{
			foreach ($forUsers as $userId)
			{
				if ($params['FROM_USER_ID'] == $userId)
					continue;

				$arMessageFields = array(
					"TO_USER_ID" => $userId,
					"FROM_USER_ID" => $params['FROM_USER_ID'],
					"NOTIFY_TYPE" => IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "im",
					"NOTIFY_EVENT" => "mention",
					"NOTIFY_TAG" => 'IM|MENTION|'.$params['CHAT_ID'],
					"NOTIFY_SUB_TAG" => 'IM_MESS_'.$params['CHAT_ID'].'_'.$userId,
					"NOTIFY_MESSAGE" => $notifyText,
					"NOTIFY_MESSAGE_OUT" => $notifyMail,
				);
				CIMNotify::Add($arMessageFields);

				\Bitrix\Pull\Push::add($userId, self::PreparePushForMentionInChat(Array(
					'CHAT_ID' => $params['CHAT_ID'],
					'CHAT_TITLE' => $params['CHAT_TITLE'],
					'CHAT_TYPE' => $params['CHAT_TYPE'],
					'CHAT_AVATAR' => $params['CHAT_AVATAR'],
					'CHAT_ENTITY_TYPE' => $params['CHAT_ENTITY_TYPE'],
					'FROM_USER_ID' => $params['FROM_USER_ID'],
					'MESSAGE' => $pushText,
				)));
			}
		}

		return true;
	}

	public static function PrepareParamsForPull($params)
	{
		if (!is_array($params))
		{
			return $params;
		}

		foreach ($params as $key => $value)
		{
			if ($key == 'ATTACH')
			{
				if (is_object($value) && $value instanceof CIMMessageParamAttach)
				{
					$params[$key] = CIMMessageParamAttach::PrepareAttach($value->GetArray());
				}
				else
				{
					foreach ($value as $key2 => $value2)
					{
						if (is_object($value2) && $value2 instanceof CIMMessageParamAttach)
						{
							$params[$key][$key2] = CIMMessageParamAttach::PrepareAttach($value2->GetArray());
						}
					}
				}
			}
			elseif ($key == 'KEYBOARD')
			{
				if (is_object($value) && $value instanceof \Bitrix\Im\Bot\Keyboard)
				{
					$params[$key] = $value->getArray();
				}
			}
			elseif ($key == 'MENU')
			{
				if (is_object($value) && $value instanceof \Bitrix\Im\Bot\ContextMenu)
				{
					$params[$key] = $value->getArray();
				}
			}
			elseif ($key == 'AVATAR' && intval($value) > 0)
			{
				$params[$key] = CIMChat::GetAvatarImage($value, 200, false);
			}

		}

		return $params;
	}

	public static function PreparePushForMentionInChat($params)
	{
		$params['CHAT_ID'] = intval($params['CHAT_ID']);
		if ($params['CHAT_ID'] <= 0)
		{
			return false;
		}

		$params['CHAT_TITLE'] = mb_substr(htmlspecialcharsback($params['CHAT_TITLE']), 0, 32);

		$pushText = $params['MESSAGE'];

		$chatType = \Bitrix\Im\Chat::getType($params);

		$avatarUser = \Bitrix\Im\User::getInstance($params['FROM_USER_ID'])->getAvatar();
		if ($avatarUser && mb_strpos($avatarUser, 'http') !== 0)
		{
			$avatarUser = \Bitrix\Im\Common::getPublicDomain().$avatarUser;
		}

		$avatarChat = \CIMChat::GetAvatarImage($params['CHAT_AVATAR'], 200, false);
		if ($avatarChat && mb_strpos($avatarChat, 'http') !== 0)
		{
			$avatarChat = \Bitrix\Im\Common::getPublicDomain().$avatarChat;
		}

		$result = Array();
		$result['module_id'] = 'im';
		$result['push']['params'] = Array(
			'TAG' => 'IM_CHAT_'.$params['CHAT_ID'],
			'CHAT_TYPE' => $params['CHAT_TYPE'],
			'CATEGORY' => 'ANSWER',
			'URL' => SITE_DIR.'mobile/ajax.php?mobile_action=im_answer',
			'PARAMS' => Array(
				'RECIPIENT_ID' => 'chat'.$params['CHAT_ID']
			)
		);
		$result['push']['type'] = $params['PUSH_TYPE']? $params['PUSH_TYPE']: ($params['CHAT_TYPE'] == IM_MESSAGE_OPEN? 'openChat': 'chat');
		$result['push']['tag'] = 'IM_CHAT_'.$params['CHAT_ID'];
		$result['push']['sub_tag'] = 'IM_MESS';
		$result['push']['app_id'] = 'Bitrix24';
		$result['push']['message'] = $pushText;
		$result['push']['advanced_params'] = array(
			"group"=> $chatType == 'lines'? 'im_lines_message': 'im_message',
			"avatarUrl"=> $avatarChat? $avatarChat: $avatarUser,
			"senderName" => (string)$params['CHAT_TITLE'],
			"senderMessage" => $pushText,
		);

		return $result;
	}

	public static function PreparePushForChat($params)
	{
		$pushText = self::PrepareMessageForPush($params['params']);
		unset($params['params']['message']['text_push']);

		$chatTitle = mb_substr(htmlspecialcharsback($params['params']['chat'][$params['params']['chatId']]['name']), 0, 32);
		$chatType = $params['params']['chat'][$params['params']['chatId']]['type'];
		$chatAvatar = $params['params']['chat'][$params['params']['chatId']]['avatar'];
		$chatTypeLetter = $params['params']['chat'][$params['params']['chatId']]['message_type'];


		if ($params['params']['system'] == 'Y' || $params['params']['message']['senderId'] <= 0)
		{
			$avatarUser = '';
			$userName = '';
		}
		else
		{
			$userName = \Bitrix\Im\User::getInstance($params['params']['message']['senderId'])->getFullName(false);
			$avatarUser = \Bitrix\Im\User::getInstance($params['params']['message']['senderId'])->getAvatar();
			if ($avatarUser && mb_strpos($avatarUser, 'http') !== 0)
			{
				$avatarUser = \Bitrix\Im\Common::getPublicDomain().$avatarUser;
			}
		}

		if ($params['params']['users'][$params['params']['message']['senderId']])
		{
			$params['params']['users'] = Array(
				$params['params']['message']['senderId'] => $params['params']['users'][$params['params']['message']['senderId']]
			);
		}
		else
		{
			$params['params']['users'] = Array();
		}

		if ($chatAvatar == '/bitrix/js/im/images/blank.gif')
		{
			$chatAvatar = '';
		}
		else if ($chatAvatar && mb_strpos($chatAvatar, 'http') !== 0)
		{
			$chatAvatar = \Bitrix\Im\Common::getPublicDomain().$chatAvatar;
		}

		unset($params['extra']);

		array_walk_recursive($params, function(&$item, $key)
		{
			if (is_null($item))
			{
				$item = false;
			}
			else if ($item instanceof \Bitrix\Main\Type\DateTime)
			{
				$item = date('c', $item->getTimestamp());
			}
		});

		$result = Array();
		$result['module_id'] = 'im';
		$result['push']['type'] = ($chatType === 'open'? 'openChat': $chatType);
		$result['push']['tag'] = 'IM_CHAT_'.intval($params['params']['chatId']);
		$result['push']['sub_tag'] = 'IM_MESS';
		$result['push']['app_id'] = 'Bitrix24';
		$result['push']['message'] = ($userName? $userName.': ': '').$pushText;
		$result['push']['advanced_params'] = array(
			"group"=> $chatType == 'lines'? 'im_lines_message': 'im_message',
			"avatarUrl"=> $chatAvatar? $chatAvatar: $avatarUser,
			"senderName" => $chatTitle,
			"senderMessage" => ($userName? $userName.': ': '').$pushText,
			"senderCut" => mb_strlen($userName? $userName.': ' : ''),
			"data" => self::PrepareEventForPush($params['command'], $params['params'])
		);
		$result['push']['params'] = Array(
			'TAG' => 'IM_CHAT_'.$params['params']['chatId'],
			'CHAT_TYPE' => $chatTypeLetter? $chatTypeLetter: 'C',
			'CATEGORY' => 'ANSWER',
			'URL' => SITE_DIR.'mobile/ajax.php?mobile_action=im_answer',
			'PARAMS' => Array(
				'RECIPIENT_ID' => 'chat'.$params['params']['chatId']
			),
		);

		return $result;
	}

	public static function PreparePushForPrivate($params)
	{
		$pushText = self::PrepareMessageForPush($params['params']);
		unset($params['params']['message']['text_push']);

		if ($params['params']['system'] == 'Y')
		{
			$userName = '';
			$avatarUser = '';
		}
		else
		{
			$userName = \Bitrix\Im\User::getInstance($params['params']['message']['senderId'])->getFullName(false);
			$avatarUser = \Bitrix\Im\User::getInstance($params['params']['message']['senderId'])->getAvatar();
			if ($avatarUser && mb_strpos($avatarUser, 'http') !== 0)
			{
				$avatarUser = \Bitrix\Im\Common::getPublicDomain().$avatarUser;
			}
		}

		if ($params['params']['users'][$params['params']['message']['senderId']])
		{
			$params['params']['users'] = Array(
				$params['params']['message']['senderId'] => $params['params']['users'][$params['params']['message']['senderId']]
			);
		}
		else
		{
			$params['params']['users'] = Array();
		}

		unset($params['extra']);

		array_walk_recursive($params, function(&$item, $key)
		{
			if (is_null($item))
			{
				$item = false;
			}
			else if ($item instanceof \Bitrix\Main\Type\DateTime)
			{
				$item = date('c', $item->getTimestamp());
			}
		});

		$result = Array();
		$result['module_id'] = 'im';
		$result['push']['type'] = 'message';
		$result['push']['tag'] = 'IM_MESS_'.intval($params['params']['message']['senderId']);
		$result['push']['sub_tag'] = 'IM_MESS';
		$result['push']['app_id'] = 'Bitrix24';
		$result['push']['message'] = $pushText;
		$result['push']['advanced_params'] = array(
			"group"=> 'im_message',
			"avatarUrl"=> $avatarUser,
			"senderName" => $userName,
			"senderMessage" => $pushText,
			"data" => self::PrepareEventForPush($params['command'], $params['params']),
		);
		$result['push']['params'] = Array(
			'TAG' => 'IM_MESS_'.$params['params']['message']['senderId'],
			'CATEGORY' => 'ANSWER',
			'URL' => SITE_DIR.'mobile/ajax.php?mobile_action=im_answer',
			'PARAMS' => Array(
				'RECIPIENT_ID' => (int)$params['params']['message']['senderId']
			),
		);

		return $result;
	}

	public static function PrepareParamsForPush($params)
	{
		if (!isset($params['MESSAGE']))
		{
			$params['MESSAGE'] = '';
		}
		$params['MESSAGE'] = trim($params['MESSAGE']);

		$pushFiles = '';
		if (isset($params['FILES']) && count($params['FILES']) > 0)
		{
			foreach ($params['FILES'] as $file)
			{
				$pushFiles .= " [".GetMessage('IM_MESSAGE_FILE').": ".$file['name']."]";
			}
			$params['MESSAGE'] .= $pushFiles;
		}

		$hasAttach = mb_strpos($params['MESSAGE'], '[ATTACH=') !== false;

		$params['MESSAGE'] = preg_replace("/\[s\].*?\[\/s\]/i", "-", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[[bui]\](.*?)\[\/[bui]\]/i", "$1", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\\[url\\](.*?)\\[\\/url\\]/i".BX_UTF_PCRE_MODIFIER, "$1", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\\[url\\s*=\\s*((?:[^\\[\\]]++|\\[ (?: (?>[^\\[\\]]+) | (?:\\1) )* \\])+)\\s*\\](.*?)\\[\\/url\\]/ixs".BX_UTF_PCRE_MODIFIER, "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[CHAT=([0-9]{1,})\](.*?)\[\/CHAT\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[SEND(?:=(.+?))?\](.+?)?\[\/SEND\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[PUT(?:=(.+?))?\](.+?)?\[\/PUT\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[CALL(?:=(.+?))?\](.+?)?\[\/CALL\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[ATTACH=([0-9]{1,})\]/i", " [".GetMessage('IM_MESSAGE_ATTACH')."] ", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace_callback("/\[ICON\=([^\]]*)\]/i", Array("CIMMessenger", "PrepareMessageForPushIconCallBack"), $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace('#\-{54}.+?\-{54}#s', " [".GetMessage('IM_QUOTE')."] ", str_replace(array("#BR#"), Array(" "), $params['MESSAGE']));

		if (!$pushFiles && !$hasAttach && $params['ATTACH'])
		{
			$params['MESSAGE'] .= " [".GetMessage('IM_MESSAGE_ATTACH')."]";
		}

		return $params['MESSAGE'];
	}

	public static function PrepareMessageForPush($message)
	{
		if ($message['message']['text_push'])
		{
			$message['message']['text'] = $message['message']['text_push'];
		}

		$pushFiles = '';
		if (isset($message['files']) && count($message['files']) > 0)
		{
			$file = array_values($message['files'])[0];

			if ($file['type'] === 'image')
			{
				$fileName = GetMessage('IM_MESSAGE_IMAGE');
			}
			else if ($file['type'] === 'audio')
			{
				$fileName = GetMessage('IM_MESSAGE_AUDIO');
			}
			else if ($file['type'] === 'video')
			{
				$fileName = GetMessage('IM_MESSAGE_VIDEO');
			}
			else
			{
				$fileName = GetMessage('IM_MESSAGE_FILE').": ".$file['name'];
			}

			$message['message']['text'] .= $fileName;
		}

		$hasAttach = mb_strpos($message['message']['text'], '[ATTACH=') !== false;

		$message['message']['text'] = preg_replace("/\[s\].*?\[\/s\]/i", "-", $message['message']['text']);
		$message['message']['text'] = preg_replace("/\[[bui]\](.*?)\[\/[bui]\]/i", "$1", $message['message']['text']);
		$message['message']['text'] = preg_replace("/\\[url\\](.*?)\\[\\/url\\]/i".BX_UTF_PCRE_MODIFIER, "$1", $message['message']['text']);
		$message['message']['text'] = preg_replace("/\\[url\\s*=\\s*((?:[^\\[\\]]++|\\[ (?: (?>[^\\[\\]]+) | (?:\\1) )* \\])+)\\s*\\](.*?)\\[\\/url\\]/ixs".BX_UTF_PCRE_MODIFIER, "$2", $message['message']['text']);
		$message['message']['text'] = preg_replace_callback("/\[USER=([0-9]{1,})\]\[\/USER\]/i", Array('\Bitrix\Im\Text', 'modifyShortUserTag'), $message['message']['text']);
		$message['message']['text'] = preg_replace("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", "$2", $message['message']['text']);
		$message['message']['text'] = preg_replace("/\[CHAT=([0-9]{1,})\](.*?)\[\/CHAT\]/i", "$2", $message['message']['text']);
		$message['message']['text'] = preg_replace("/\[SEND(?:=(.+?))?\](.+?)?\[\/SEND\]/i", "$2", $message['message']['text']);
		$message['message']['text'] = preg_replace("/\[PUT(?:=(.+?))?\](.+?)?\[\/PUT\]/i", "$2", $message['message']['text']);
		$message['message']['text'] = preg_replace("/\[CALL(?:=(.+?))?\](.+?)?\[\/CALL\]/i", "$2", $message['message']['text']);
		$message['message']['text'] = preg_replace("/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/i", "$2", $message['message']['text']);
		$message['message']['text'] = preg_replace("/\[ATTACH=([0-9]{1,})\]/i", " [".GetMessage('IM_MESSAGE_ATTACH')."] ", $message['message']['text']);
		$message['message']['text'] = preg_replace_callback("/\[ICON\=([^\]]*)\]/i", Array("CIMMessenger", "PrepareMessageForPushIconCallBack"), $message['message']['text']);
		$message['message']['text'] = preg_replace('#\-{54}.+?\-{54}#s', " [".GetMessage('IM_QUOTE')."] ", str_replace(array("#BR#"), Array(" "), $message['message']['text']));

		if (!$pushFiles && !$hasAttach && isset($message['message']['params']['ATTACH']))
		{
			$message['message']['text'] .= " [".GetMessage('IM_MESSAGE_ATTACH')."]";
		}

		return $message['message']['text'];
	}

	public static function PrepareEventForPush($command, $event)
	{
		$result = [
			'cmd' => (string)$command,
			'chatId' => (int)$event['chatId'],
			'dialogId' => (string)$event['dialogId'],
			'counter' => (int)$event['counter'],
		];

		if ($event['notify'] !== true)
		{
			$result['notify'] = $event['notify'];
		}

		if (!empty($event['chat'][$event['chatId']]))
		{
			$eventChat = $event['chat'][$event['chatId']];

			$chat = [
				'id' => (int)$eventChat['id'],
				'name' => (string)$eventChat['name'],
				'owner' => (int)$eventChat['owner'],
				'color' => (string)$eventChat['color'],
				'type' => (string)$eventChat['type'],
				'date_create' => (string)$eventChat['date_create'],
			];

			if (
				!empty($eventChat['avatar'])
				&& $eventChat['avatar'] !== '/bitrix/js/im/images/blank.gif'
			)
			{
				$chat['avatar'] = $eventChat['avatar'];
			}
			if ($eventChat['call'])
			{
				$chat['call'] = (string)$eventChat['call'];
			}
			if ($eventChat['call_number'])
			{
				$chat['call_number'] = (string)$eventChat['call_number'];
			}
			if ($eventChat['entity_data_1'])
			{
				$chat['entity_data_1'] = (string)$eventChat['entity_data_1'];
			}
			if ($eventChat['entity_data_2'])
			{
				$chat['entity_data_2'] = (string)$eventChat['entity_data_2'];
			}
			if ($eventChat['entity_data_3'])
			{
				$chat['entity_data_3'] = (string)$eventChat['entity_data_3'];
			}
			if ($eventChat['entity_id'])
			{
				$chat['entity_id'] = (string)$eventChat['entity_id'];
			}
			if ($eventChat['entity_type'])
			{
				$chat['entity_type'] = (string)$eventChat['entity_type'];
			}
			if ($eventChat['extranet'])
			{
				$chat['extranet'] = true;
			}

			$result['chat'] = $chat;
		}

		if (!empty($event['lines']))
		{
			$result['lines'] = $event['lines'];
		}

		if (!empty($event['users'][$event['message']['senderId']]))
		{
			$eventUser = $event['users'][$event['message']['senderId']];

			$user = [
				'id' => (int)$eventUser['id'],
				'name' => (string)$eventUser['name'],
				'first_name' => (string)$eventUser['first_name'],
				'last_name' => (string)$eventUser['last_name'],
				'color' => (string)$eventUser['color'],
			];

			if (
				!empty($eventUser['avatar'])
				&& $eventUser['avatar'] !== '/bitrix/js/im/images/blank.gif'
			)
			{
				$user['avatar'] = (string)$eventUser['avatar'];
			}

			if ($eventUser['absent'])
			{
				$user['absent'] = true;
			}
			if (!$eventUser['active'])
			{
				$user['active'] = $eventUser['active'];
			}
			if ($eventUser['bot'])
			{
				$user['bot'] = true;
			}
			if ($eventUser['extranet'])
			{
				$user['extranet'] = true;
			}
			if ($eventUser['network'])
			{
				$user['network'] = true;
			}
			if ($eventUser['birthday'])
			{
				$user['birthday'] = $eventUser['birthday'];
			}
			if ($eventUser['connector'])
			{
				$user['connector'] = true;
			}
			if ($eventUser['external_auth_id'] !== 'default')
			{
				$user['external_auth_id'] = $eventUser['external_auth_id'];
			}
			if ($eventUser['gender'] === 'F')
			{
				$user['gender'] = 'F';
			}
			if ($eventUser['work_position'])
			{
				$user['work_position'] = (string)$eventUser['work_position'];
			}

			$result['users'] = $user;
		}

		if (!empty($event['files']))
		{
			foreach ($event['files'] as $key => $value)
			{
				$file = [
					'id' => (int)$value['id'],
					'extension' => (string)$value['extension'],
					'name' => (string)$value['name'],
					'size' => (int)$value['size'],
					'type' => (string)$value['type'],
					'image' => $value['image'],
					'size' => $value['size'],
					'urlDownload' => (new \Bitrix\Main\Web\Uri($value['urlDownload']))->deleteParams(['fileName'])->getUri(),
					'urlPreview' => (new \Bitrix\Main\Web\Uri($value['urlPreview']))->deleteParams(['fileName'])->getUri(),
					'urlShow' => (new \Bitrix\Main\Web\Uri($value['urlShow']))->deleteParams(['fileName'])->getUri(),
				];
				if ($value['image'])
				{
					$file['image'] = $value['image'];
				}
				if ($value['progress'] !== 100)
				{
					$file['progress'] = (int)$value['progress'];
				}
				if ($value['status'] !== 'done')
				{
					$file['status'] = $value['status'];
				}

				$result['files'][$key] = $file;
			}
		}

		if (!empty($event['message']))
		{
			$eventMessage = $event['message'];

			$message = [
				'id' => (int)$eventMessage['id'],
				'date' => (string)$eventMessage['date'],
				'params' => $eventMessage['params'],
				'prevId' => (int)$eventMessage['prevId'],
				'senderId' => (int)$eventMessage['senderId'],
			];

			if ($eventMessage['system'] === 'Y')
			{
				$message['system'] = 'Y';
			}
			if ($eventMessage['textOriginal'] && mb_strlen($eventMessage['textOriginal']) < 500)
			{
				$message['textOriginal'] = (string)$eventMessage['textOriginal'];
			}

			$result['message'] = $message;
		}

		$indexToNameMap = [
			"chat" => 1,
			"chatId" => 2,
			"counter" => 3,
			"dialogId" => 4,
			"files" => 5,
			"message" => 6,
			"users" => 8,
			"name" => 9,
			"avatar" => 10,
			"color" => 11,
			"notify" => 12,
			"type" => 13,
			"extranet" => 14,

			"date_create" => 20,
			"owner" => 21,
			"entity_id" => 23,
			"entity_type" => 24,
			"entity_data_1" => 203,
			"entity_data_2" => 204,
			"entity_data_3" => 205,
			"call" => 201,
			"call_number" => 202,
			"manager_list" => 209,
			"mute_list" => 210,

			"first_name" => 40,
			"last_name" => 41,
			"gender" => 42,
			"work_position" => 43,
			"active" => 400,
			"birthday" => 401,
			"bot" => 402,
			"connector" => 403,
			"external_auth_id" => 404,
			"network" => 406,


			"textOriginal" => 60,
			"date" => 61,
			"prevId" => 62,
			"params" => 63,
			"senderId" => 64,
			"system" => 601,

			"extension" => 80,
			"image" => 81,
			"progress" => 82,
			"size" => 83,
			"status" => 84,
			"urlDownload" => 85,
			"urlPreview" => 86,
			"urlShow" => 87,
			"width" => 88,
			"height" => 89,
		];

		return self::PrepareEventForPushChangeKeys($result, $indexToNameMap);
	}

	private static function PrepareEventForPushChangeKeys($object, $map)
	{
		$result = [];

		foreach($object as $key => $value)
		{
			$index = isset($map[$key])? $map[$key]: $key;
			if (is_null($value))
			{
				$value = "";
			}
			if (is_array($value))
			{
				$result[$index] = self::PrepareEventForPushChangeKeys($value, $map);
			}
			else
			{
				$result[$index] = $value;
			}
		}

		return $result;
	}

	public static function PrepareMessageForPushIconCallBack($params)
	{
		$text = $params[1];

		$title = GetMessage('IM_MESSAGE_ICON');

		preg_match('/title\=(.*[^\s\]])/i', $text, $match);
		if ($match)
		{
			$title = $match[1];
			if (mb_strpos($title, 'width=') !== false)
			{
				$title = mb_substr($title, 0, mb_strpos($title, 'width='));
			}
			if (mb_strpos($title, 'height=') !== false)
			{
				$title = mb_substr($title, 0, mb_strpos($title, 'height='));
			}
			if (mb_strpos($title, 'size=') !== false)
			{
				$title = mb_substr($title, 0, mb_strpos($title, 'size='));
			}
			$title = trim($title);
		}

		return '('.$title.')';
	}

	/* TMP FUNCTION */
	public static function GetCachePath($id)
	{
		return \Bitrix\Im\Common::getCacheUserPostfix($id);
	}

	function GetSonetCode($user_id, $site_id = SITE_ID)
	{
		global $DB;
		$result = array();
		$user_id = intval($user_id);

		if($user_id > 0 && IsModuleInstalled('socialnetwork'))
		{
			$strSQL = "
				SELECT CODE, SUM(CNT) CNT
				FROM b_sonet_log_counter
				WHERE USER_ID = ".$user_id."
				AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')
				GROUP BY CODE
			";
			$dbRes = $DB->Query($strSQL, true, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes = $dbRes->Fetch())
				$result[$arRes["CODE"]] = $arRes["CNT"];
		}

		return $result;
	}

	public static function EnableMessageCheck()
	{
		self::$enableMessageCheck++;
		return true;
	}
	public static function DisableMessageCheck()
	{
		self::$enableMessageCheck--;
		return true;
	}

	public static function IsEnabledMessageCheck()
	{
		return self::$enableMessageCheck > 0;
	}

	public static function IsBitrix24UserRestricted()
	{
		global $USER;

		return (
			\Bitrix\Main\Loader::includeModule('bitrix24')
			&& \Bitrix\Bitrix24\Limits\User::isUserRestricted($USER->GetId())
			&& !\Bitrix\Bitrix24\Limits\User::isMoreHitsAvailable($USER->GetId())
		);
	}

	public static function IsMysqlDb()
	{
		global $DB;
		return $DB->type == 'MYSQL';
	}

	public static function IsAdmin()
	{
		global $USER;
		return $USER->IsAdmin() || CModule::IncludeModule('bitrix24') && CBitrix24::IsPortalAdmin($USER->GetId());
	}

	public static function GetCurrentUserId()
	{
		global $USER;
		return $USER->GetID();
	}

	private static function GetEventByCounterGroup($events, $maxUserInGroup = 100)
	{
		$groups = Array();
		foreach ($events as $userId => $event)
		{
			$eventCode = $event['groupId'];
			if (!isset($groups[$eventCode]))
			{
				$groups[$eventCode]['event'] = $event;
			}
			$groups[$eventCode]['users'][] = $userId;
			$groups[$eventCode]['count'] = count($groups[$eventCode]['users']);
		}

		\Bitrix\Main\Type\Collection::sortByColumn($groups, Array('count' => SORT_DESC));

		$count = 0;
		$finalGroup = Array();
		foreach ($groups as $eventCode => $event)
		{
			if ($count >= $maxUserInGroup)
			{
				if (isset($finalGroup['other']))
				{
					$finalGroup['other']['users'] = array_unique(array_merge($finalGroup['other']['users'], $event['users']));
				}
				else
				{
					$finalGroup['other'] = $event;
					$finalGroup['other']['event']['params']['counter'] = 100;
				}
			}
			else
			{
				$finalGroup[$eventCode] = $event;
			}
			$count++;
		}

		\Bitrix\Main\Type\Collection::sortByColumn($finalGroup, Array('count' => SORT_ASC));

		return $finalGroup;
	}
}