<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\CustomerType;
use Bitrix\Crm\MessageSender\ICanSendMessage;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\MessageService;

/**
 * Class SmsManager
 * @package Bitrix\Crm\Integration
 * @internal
 */
class SmsManager implements ICanSendMessage
{
	static $canUse = null;

	/**
	 * @return bool
	 */
	public static function canUse()
	{
		if (static::$canUse === null)
		{
			static::$canUse = Main\Loader::includeModule('messageservice');
		}
		return static::$canUse;
	}

	/**
	 * @inheritDoc
	 */
	public static function getSenderCode(): string
	{
		return 'sms_provider';
	}

	/**
	 * @inheritDoc
	 */
	public static function isAvailable(): bool
	{
		return static::canUse();
	}

	/**
	 * @inheritDoc
	 */
	public static function isConnected(): bool
	{
		if (static::canUse())
		{
			return MessageService\Sender\SmsManager::getUsableSender() !== null;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function getConnectUrl(): ?string
	{
		if (!static::canUse())
		{
			return null;
		}

		return (new Main\Web\Uri(
			getLocalPath(
				'components' . \CComponentEngine::makeComponentPath('bitrix:salescenter.smsprovider.panel') . '/slider.php'
			)
		))->getLocator();
	}

	/**
	 * @inheritDoc
	 */
	public static function getUsageErrors(): array
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public static function canSendMessage()
	{
		return static::isConnected();
	}

	/**
	 * @return array Simple list of senders, array(id => name)
	 */
	public static function getSenderSelectList()
	{
		$list = array();
		if (static::canUse())
		{
			$list = MessageService\Sender\SmsManager::getSenderInfoList();
		}
		return $list;
	}

	/**
	 * @param string $senderId Sender id.
	 * @return array Simple list of sender From aliases
	 */
	public static function getSenderFromList($senderId)
	{
		$list = array();
		if (static::canUse())
		{
			$sender = MessageService\Sender\SmsManager::getSenderById($senderId);
			if ($sender)
			{
				$list = $sender->getFromList();
			}
		}
		return $list;
	}

	/**
	 * @param bool $getFromList
	 * @return array Senders information.
	 */
	public static function getSenderInfoList($getFromList = false)
	{
		$info = array();
		if (static::canUse())
		{
			foreach (MessageService\Sender\SmsManager::getSenders() as $sender)
			{
				$senderInfo = array(
					'id' => $sender->getId(),
					'isConfigurable' => $sender->isConfigurable(),
					'name' => $sender->getName(),
					'shortName' => $sender->getShortName(),
					'canUse' => $sender->canUse(),
					'isDemo' => $sender->isConfigurable() ? $sender->isDemo() : null,
					'manageUrl' => $sender->isConfigurable() ?
						'/crm/configs/sms/?sender='.$sender->getId() : ''
				);

				if ($getFromList)
				{
					$senderInfo['fromList'] = static::getSenderFromList($sender->getId());
				}

				$info[] = $senderInfo;
			}
		}

		return $info;
	}

	public static function getSenderShortName($senderId)
	{
		$name = '';
		if (static::canUse())
		{
			$sender = MessageService\Sender\SmsManager::getSenderById($senderId);
			if ($sender)
			{
				$name = $sender->getShortName();
			}
		}
		return $name;
	}

	public static function getSenderFromName($senderId, $from)
	{
		$name = '';
		if (static::canUse())
		{
			$sender = MessageService\Sender\SmsManager::getSenderById($senderId);
			if ($sender)
			{
				$fromList = $sender->getFromList();
				foreach ($fromList as $fromItem)
				{
					if ($fromItem['id'] === $from)
					{
						$name = $fromItem['name'];
						break;
					}
				}
			}
		}
		return $name;
	}

	/**
	 * @param array $messageFields
	 * @return Main\Entity\AddResult|false
	 */
	public static function sendMessage(array $messageFields)
	{
		if (static::canUse())
		{
			return MessageService\Sender\SmsManager::sendMessage($messageFields);
		}
		return false;
	}

	/**
	 * @return array
	 */
	public static function getMessageStatusDescriptions()
	{
		if (static::canUse())
		{
			return MessageService\MessageStatus::getDescriptions();
		}
		return array();
	}

	public static function getMessageStatusSemantics()
	{
		if (static::canUse())
		{
			return MessageService\MessageStatus::getSemantics();
		}
		return array();
	}

	/**
	 * @param int $id
	 * @return string
	 */
	public static function getMessageStatus($id)
	{
		if (static::canUse())
		{
			$result = MessageService\Sender\SmsManager::getMessageStatus($id);
			if ($result->isSuccess())
			{
				return $result->getStatusText();
			}
		}
		return '';
	}

	/**
	 * @param int $statusId
	 * @return bool
	 */
	public static function isMessageErrorStatus($statusId)
	{
		if (static::canUse())
		{
			return (int)$statusId === MessageService\MessageStatus::ERROR;
		}
		return false;
	}

	/**
	 * @param int $messageId
	 * @return array|false
	 */
	public static function getMessageFields($messageId)
	{
		if (static::canUse())
		{
			return MessageService\Message::getFieldsById($messageId);
		}
		return false;
	}

	/**
	 * @return string
	 */
	public static function getManageUrl()
	{
		return '/crm/configs/sms/';
	}

	/**
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return array
	 */
	public static function getEditorConfig($entityTypeId, $entityId)
	{
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;
		$result = array(
			'canUse' => static::canUse(),
			'canSendMessage' => static::canSendMessage(),
			'manageUrl' => static::getManageUrl(),
			'senders' => static::getSenderInfoList(true),
			'defaults' => static::getEditorDefaults(),
			'communications' => array()
		);

		if ($entityId > 0)
		{
			$result['communications'] = static::getEntityPhoneCommunications($entityTypeId, $entityId);
		}

		return $result;
	}

	/**
	 * @param array $defaults
	 */
	public static function setEditorDefaults(array $defaults)
	{
		$config = array(
			'senderId' => isset($defaults['senderId']) ? (string)$defaults['senderId'] : null,
			'from' => isset($defaults['from']) ? (string)$defaults['from'] : null
		);
		\CUserOptions::SetOption('crm', 'sms_manager_editor', $config);
	}

	/**
	 * @return array
	 */
	public static function getEditorDefaults()
	{
		return (array)\CUserOptions::GetOption('crm', 'sms_manager_editor', self::getEditorCommon());
	}

	/**
	 * Sets default parameters for all users
	 * @param array $defaults
	 * @return void
	 */
	public static function setEditorDefaultsCommon(array $defaults)
	{
		$config = array(
			'senderId' => isset($defaults['senderId']) ? (string)$defaults['senderId'] : null,
			'from' => isset($defaults['from']) ? (string)$defaults['from'] : null
		);
		\Bitrix\Main\Config\Option::set('crm', 'sms_manager_editor', serialize($config));
	}

	/**
	 * @return array
	 */
	public static function getEditorCommon()
	{
		return (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'sms_manager_editor', serialize(array('senderId' => null, 'from' => null))), ['allowed_classes' => false]);
	}

	/**
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return array
	 */
	public static function getEntityPhoneCommunications($entityTypeId, $entityId)
	{
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;

		$communications = array();

		if (in_array($entityTypeId, array(\CCrmOwnerType::Lead, \CCrmOwnerType::Contact, \CCrmOwnerType::Company), true))
		{
			$communications[] = static::prepareEntityCommunications($entityTypeId, $entityId);
		}
		elseif ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$entity = \CCrmDeal::getById($entityId);
			if (!empty($entity))
			{
				$dealContactIds = \Bitrix\Crm\Binding\DealContactTable::getDealContactIds($entityId);
				if (!empty($dealContactIds))
				{
					foreach ($dealContactIds as $contactId)
					{
						$communications[] = static::prepareEntityCommunications(\CCrmOwnerType::Contact, $contactId);
					}
				}

				$dealCompanyId = isset($entity['COMPANY_ID']) ? (int)$entity['COMPANY_ID'] : 0;
				if ($dealCompanyId > 0)
				{
					$communications[] = static::prepareEntityCommunications(\CCrmOwnerType::Company, $dealCompanyId);
				}
			}
		}
		elseif ($entityTypeId === \CCrmOwnerType::Order)
		{
			$dbRes = \Bitrix\Crm\Order\ContactCompanyCollection::getList(array(
				'select' => array('ENTITY_ID', 'ENTITY_TYPE_ID'),
				'filter' => array(
					'=ORDER_ID' => $entityId,
					'IS_PRIMARY' => 'Y'
				)
			));
			while ($entity = $dbRes->fetch())
			{
				if ((int)$entity['ENTITY_TYPE_ID'] === \CCrmOwnerType::Contact)
				{
					$communications[] = static::prepareEntityCommunications(
						\CCrmOwnerType::Contact,
						$entity['ENTITY_ID']
					);
				}
				elseif ((int)$entity['ENTITY_TYPE_ID'] === \CCrmOwnerType::Company)
				{
					$communications[] = static::prepareEntityCommunications(
						\CCrmOwnerType::Company,
						$entity['ENTITY_ID']
					);
				}
			}
		}
		else
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if($factory && $factory->isClientEnabled())
			{
				$item = $factory->getItem($entityId);
				if($item)
				{
					foreach($item->getContactBindings() as $binding)
					{
						$contactId = EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $binding);
						if($contactId > 0)
						{
							$communications[] = static::prepareEntityCommunications(
								\CCrmOwnerType::Contact,
								$contactId
							);
						}
					}
					$companyId = $item->getCompanyId();
					if($companyId > 0)
					{
						$communications[] = static::prepareEntityCommunications(
							\CCrmOwnerType::Company,
							$companyId
						);
					}
				}
			}
		}

		$communications = array_filter($communications);

		if (
			$entityTypeId === \CCrmOwnerType::Lead
			&& empty($communications)
			&& \CCrmLead::GetCustomerType($entityId) === CustomerType::RETURNING
		)
		{
			$entity = \CCrmLead::getById($entityId);
			if (!empty($entity))
			{
				if ($entity['CONTACT_ID'] > 0)
				{
					$communications[] = static::prepareEntityCommunications(\CCrmOwnerType::Contact, $entity['CONTACT_ID']);
				}
				if ($entity['COMPANY_ID'] > 0)
				{
					$communications[] = static::prepareEntityCommunications(\CCrmOwnerType::Company, $entity['COMPANY_ID']);
				}
			}
		}

		return array_filter($communications);
	}

	/**
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return array|null
	 */
	private static function prepareEntityCommunications($entityTypeId, $entityId)
	{
		$caption = \CCrmOwnerType::GetCaption($entityTypeId, $entityId);
		if (!$caption)
		{
			return null;
		}

		$typeName = \CCrmOwnerType::ResolveName($entityTypeId);
		$result = array(
			'entityTypeId' => $entityTypeId,
			'entityTypeName' => $typeName,
			'entityId' => $entityId,
			'caption' => $caption,
			'phones' => array()
		);

		$iterator = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $typeName,
				'ELEMENT_ID' => $entityId,
				'TYPE_ID' => \CCrmFieldMulti::PHONE
			)
		);

		while ($row = $iterator->fetch())
		{
			if (empty($row['VALUE']))
				continue;

			$result['phones'][] = array(
				'value' => $row['VALUE'],
				'valueFormatted' => Main\PhoneNumber\Parser::getInstance()->parse($row['VALUE'])->format(),
				'type' => $row['VALUE_TYPE']
			);
		}

		return count($result['phones']) > 0 ? $result : null;
	}

	/**
	 * @inheritDoc
	 */
	public static function makeMessageFields(array $options, array $commonOptions): array
	{
		$sender = (isset($options['SENDER_ID']))
			? MessageService\Sender\SmsManager::getSenderById($options['SENDER_ID'])
			: MessageService\Sender\SmsManager::getUsableSender();

		return [
			'SENDER_ID' => $sender->getId(),
			'MESSAGE_FROM' => $sender->getFirstFromList(),
			'MESSAGE_BODY' => $options['MESSAGE_BODY'],
			'AUTHOR_ID' => $commonOptions['USER_ID'],
			'MESSAGE_TO' => $commonOptions['PHONE_NUMBER'],
			'MESSAGE_HEADERS' => [
				'module_id' => 'crm',
				'bindings' => $commonOptions['ADDITIONAL_FIELDS']['BINDINGS'] ?? [],
			],
			'ADDITIONAL_FIELDS' => array_merge(
				$commonOptions['ADDITIONAL_FIELDS'],
				[
					'ACTIVITY_PROVIDER_TYPE_ID' => $options['ACTIVITY_PROVIDER_TYPE_ID'] ?? null,
					'ACTIVITY_AUTHOR_ID' => $commonOptions['USER_ID'],
					'ACTIVITY_DESCRIPTION' => $options['MESSAGE_BODY'],
					'MESSAGE_TO' => $commonOptions['PHONE_NUMBER'],
					'SENDER_ID' => $sender->getId(),
				]
			),
		];
	}
}
