<?php

namespace Sale\Handlers\Delivery\YandexTaxi\ClaimBuilder;

use Bitrix\Location\Entity\Address\Converter\StringConverter;
use Bitrix\Location\Service\FormatService;
use Bitrix\Location\Entity;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sale\Delivery\ExtraServices\Manager;
use Bitrix\Sale\Delivery\Services\OrderPropsDictionary;
use Bitrix\Sale\Property;
use Bitrix\Sale\Shipment;
use Bitrix\Sale\ShipmentItem;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\Address;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\Claim;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\Contact;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\RoutePoint;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\RoutePoints;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\ShippingItem;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\TransportClassification;
use Sale\Handlers\Delivery\YandexTaxi\Api\Tariffs\Repository;
use Sale\Handlers\Delivery\YandexTaxi\Common\OrderEntitiesCodeDictionary;
use Sale\Handlers\Delivery\YandexTaxi\Common\ReferralSourceBuilder;
use Sale\Handlers\Delivery\YandexTaxi\Common\ShipmentDataExtractor;
use Bitrix\Main\PhoneNumber;

/**
 * Class ClaimBuilder
 * @package Sale\Handlers\Delivery\YandexTaxi\ClaimBuilder
 * @internal
 */
final class ClaimBuilder
{
	public const NEED_CONTACT_TO_EVENT_CODE = 'OnDeliveryYandexTaxiNeedContactTo';

	/** @var ShipmentDataExtractor */
	protected $extractor;

	/** @var Repository */
	protected $tariffsRepository;

	/** @var ReferralSourceBuilder */
	protected $referralSourceBuilder;

	/** @var Result */
	protected $result;

	/**
	 * ClaimBuilder constructor.
	 * @param ShipmentDataExtractor $extractor
	 * @param Repository $tariffsRepository
	 * @param ReferralSourceBuilder $referralSourceBuilder
	 */
	public function __construct(
		ShipmentDataExtractor $extractor,
		Repository $tariffsRepository,
		ReferralSourceBuilder $referralSourceBuilder
	)
	{
		$this->extractor = $extractor;
		$this->tariffsRepository = $tariffsRepository;
		$this->referralSourceBuilder = $referralSourceBuilder;
	}

	/**
	 * @param Shipment $shipment
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public function build(Shipment $shipment): Result
	{
		$this->result = new Result();

		$claim = new Claim();

		/**
		 * Contacts
		 */
		$contactFrom = $this->buildContactFrom($shipment);
		if (is_null($contactFrom))
		{
			return $this->result;
		}

		/**
		 * Building Contact To
		 */
		$event = new Event('sale', static::NEED_CONTACT_TO_EVENT_CODE, ['SHIPMENT' => $shipment]);
		$event->send();

		$eventResults = $event->getResults();

		if (is_array($eventResults) && !empty($eventResults))
		{
			foreach ($eventResults as &$eventResult)
			{
				if ($eventResult->getType() == EventResult::ERROR)
				{
					$this->result->addError(new Error($eventResult->getParameters()));
				}
				elseif ($eventResult->getType() == EventResult::SUCCESS)
				{
					/** @var Contact $contactTo */
					$contactTo = $eventResult->getParameters();
				}
			}
		}
		if (!$this->result->isSuccess())
		{
			return $this->result;
		}

		/**
		 * Addresses
		 */
		$addressFromResult = $this->buildAddressFrom($shipment);
		if (!$addressFromResult->isSuccess())
		{
			return $this->result->addErrors($addressFromResult->getErrors());
		}
		/** @var Address $addressFrom */
		$addressFrom = $addressFromResult->getData()['ADDRESS'];

		$addressToResult = $this->buildAddressTo($shipment);
		if (!$addressToResult->isSuccess())
		{
			return $this->result->addErrors($addressToResult->getErrors());
		}
		/** @var Address $addressFrom */
		$addressTo = $addressToResult->getData()['ADDRESS'];

		/**
		 * General comment
		 */
		$commentForDriver = $this->getPropertyValue(
			$shipment,
			OrderEntitiesCodeDictionary::COMMENT_FOR_DRIVER_PROPERTY_CODE
		);
		if ($commentForDriver)
		{
			$claim->setComment($commentForDriver);
		}

		$buildClientReqResult = $this->buildClientRequirements($shipment);
		if (!$buildClientReqResult->isSuccess())
		{
			return $this->result->addErrors($buildClientReqResult->getErrors());
		}
		$clientRequirements = $buildClientReqResult->getData()['REQUIREMENTS'];

		/**
		 * Door Delivery
		 */
		if (!$this->isDoorDeliveryRequired($shipment))
		{
			$claim->setSkipDoorToDoor(true);
		}

		$claim
			->setEmergencyContact($contactFrom)
			->setClientRequirements($clientRequirements)
			->setRoutePoints(
				(new RoutePoints())
					->setSource(
						(new RoutePoint())
							->setContact($contactFrom)
							->setAddress($addressFrom)
							->setSkipConfirmation(true)
					)
					->setDestination(
						(new RoutePoint())
							->setContact($contactTo)
							->setAddress($addressTo)
							->setSkipConfirmation(true)
					)
			)
			->setReferralSource(
				$this->referralSourceBuilder->getReferralSourceValue()
			);

		$shippingItemCollection = $this->getShippingItemCollection($shipment);
		$validationResult = $shippingItemCollection->isValid();
		if (!$validationResult->isSuccess())
		{
			return $this->result->addErrors($validationResult->getErrors());
		}

		foreach ($shippingItemCollection as $shippingItem)
		{
			$claim->addItem($shippingItem);
		}

		return $this->result->setData(['RESULT' => $claim]);
	}

	/**
	 * @param Shipment $shipment
	 * @return ShippingItemCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public function getShippingItemCollection(Shipment $shipment): ShippingItemCollection
	{
		$result = new ShippingItemCollection();

		/** @var ShipmentItem $shipmentItem */
		foreach($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();
			if(!$basketItem)
			{
				continue;
			}

			$result->addItem(
				(new ShippingItem())
					->setTitle((string)$basketItem->getField('NAME'))
					->setCostValue((string)((float)$basketItem->getPriceWithVat() * $shipmentItem->getQuantity()))
					->setCostCurrency((string)$basketItem->getCurrency())
					->setQuantity((int)ceil($shipmentItem->getQuantity()))
			);
		}

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getExtraServiceValues(Shipment $shipment): array
	{
		$result = [];

		$extraServicesValues = is_array($shipment->getExtraServices()) ? $shipment->getExtraServices() : [];

		$services = Manager::getExtraServicesList($shipment->getDeliveryId());
		foreach ($services as $serviceId => $serviceItem)
		{
			$initValue = isset($serviceItem['INIT_VALUE']) ? $serviceItem['INIT_VALUE'] : null;

			$value = isset($extraServicesValues[$serviceId]) ? $extraServicesValues[$serviceId] : $initValue;

			$result[$serviceItem['CODE']] = [
				'VALUE' => $value,
				'SERVICE' => $serviceItem,
			];
		}

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isDoorDeliveryRequired(Shipment $shipment)
	{
		$extraServiceValues = $this->getExtraServiceValues($shipment);

		foreach ($extraServiceValues as $code => $item)
		{
			if ($code != OrderEntitiesCodeDictionary::DOOR_DELIVERY_EXTRA_SERVICE_CODE)
			{
				continue;
			}

			return ($item['VALUE'] === 'Y');
		}

		return false;
	}

	/**
	 * @param Shipment $shipment
	 * @return Result
	 * @throws \Bitrix\Main\SystemException
	 */
	public function buildClientRequirements(Shipment $shipment): Result
	{
		$requirements = new TransportClassification();
		$result = new Result();

		$deliveryService = $shipment->getDelivery();
		if (!$deliveryService)
		{
			return $result->addError(
				new Error(Loc::getMessage('SALE_YANDEX_TAXI_DELIVERY_SERVICE_NOT_FOUND'))
			);
		}

		$deliveryServiceConfig = $deliveryService->getConfig();
		if (!isset($deliveryServiceConfig['MAIN']['ITEMS']['PROFILE_TYPE']['VALUE']))
		{
			return $result->addError(
				new Error(Loc::getMessage('SALE_YANDEX_TAXI_TARIFF_IS_NOT_SPECIFIED'))
			);
		}
		$tariffCode = $deliveryServiceConfig['MAIN']['ITEMS']['PROFILE_TYPE']['VALUE'];

		$tariff = null;
		$availableTariffs = $this->tariffsRepository->getTariffs();
		foreach ($availableTariffs as $availableTariff)
		{
			if ($availableTariff['name'] === $tariffCode)
			{
				$tariff = $availableTariff;
				break;
			}
		}

		if (is_null($tariff))
		{
			return $result->addError(
				new Error(Loc::getMessage('SALE_YANDEX_TAXI_TARIFF_HAS_NOT_BEEN_FOUND'))
			);
		}
		$requirements->setTaxiClass($tariffCode);

		$extraServiceValues = $this->getExtraServiceValues($shipment);
		$options = [];
		foreach ($tariff['supported_requirements'] as $supportedRequirement)
		{
			if ($supportedRequirement['type'] === 'multi_select')
			{
				foreach ($supportedRequirement['options'] as $srOption)
				{
					if (isset($extraServiceValues[$srOption['value']])
						&& $extraServiceValues[$srOption['value']]['VALUE'] === 'Y'
					)
					{
						if (!is_array($options[$supportedRequirement['name']]))
						{
							$options[$supportedRequirement['name']] = [];
						}
						$options[$supportedRequirement['name']][] = $srOption['value'];
					}
				}
			}
			elseif ($supportedRequirement['type'] === 'select')
			{
				if (isset($extraServiceValues[$supportedRequirement['name']])
					&& !empty($extraServiceValues[$supportedRequirement['name']]['VALUE'])
				)
				{
					foreach ($supportedRequirement['options'] as $srOption)
					{
						/**
						 * Non-strict comparison is required
						 */
						if ($srOption['value'] == $extraServiceValues[$supportedRequirement['name']]['VALUE'])
						{
							$options[$supportedRequirement['name']] = $srOption['value'];
							break;
						}
					}
				}
			}
		}
		$requirements->setOptions($options);

		$result->setData(['REQUIREMENTS' => $requirements]);

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return Contact|null
	 */
	protected function buildContactFrom(Shipment $shipment)
	{
		$responsibleUser = $this->extractor->getResponsibleUser($shipment);

		if (!$responsibleUser)
		{
			$this->result->addError(new Error(Loc::getMessage('SALE_YANDEX_TAXI_RESPONSIBLE_NOT_SPECIFIED')));
			return null;
		}

		$responsibleUserName = $this->getResponsibleUserName($responsibleUser);
		$responsibleUserEmail = $this->getResponsibleUserEmail($responsibleUser);
		$responsibleUserPhone = $this->getResponsibleUserPhone($responsibleUser);

		/**
		 * Validate responsible person contact information
		 */
		if (!$responsibleUserName)
		{
			$this->result->addError(new Error(Loc::getMessage('SALE_YANDEX_TAXI_RESPONSIBLE_FULL_NAME_NOT_SPECIFIED')));
			return null;
		}
		if (!$responsibleUserPhone)
		{
			$this->result->addError(new Error(Loc::getMessage('SALE_YANDEX_TAXI_RESPONSIBLE_PHONE_NOT_SPECIFIED')));
			return null;
		}

		$oResponsibleUserPhone = PhoneNumber\Parser::getInstance()->parse($responsibleUserPhone);
		if (!$oResponsibleUserPhone->isValid())
		{
			$this->result->addError(
				new Error(
					sprintf(
						'%s: %s',
						Loc::getMessage('SALE_YANDEX_TAXI_RESPONSIBLE_PHONE_NOT_VALID'),
						(string)$oResponsibleUserPhone->format()
					)
				)
			);
			return null;
		}

		if (!$responsibleUserEmail)
		{
			$this->result->addError(new Error(Loc::getMessage('SALE_YANDEX_TAXI_RESPONSIBLE_EMAIL_NOT_SPECIFIED')));
			return null;
		}

		return (new Contact())
			->setName($responsibleUserName)
			->setPhone(
				PhoneNumber\Formatter::format($oResponsibleUserPhone, PhoneNumber\Format::E164)
			)
			->setEmail($responsibleUserEmail);
	}

	/**
	 * @param array $responsibleUser
	 * @return string
	 */
	private function getResponsibleUserName(array $responsibleUser)
	{
		return trim(sprintf('%s %s', $responsibleUser['NAME'], $responsibleUser['LAST_NAME']));
	}

	/**
	 * @param array $responsibleUser
	 * @return string|null
	 */
	private function getResponsibleUserEmail(array $responsibleUser)
	{
		return isset($responsibleUser['EMAIL']) ? (string)$responsibleUser['EMAIL'] : null;
	}

	/**
	 * @param array $responsibleUser
	 * @return string|null
	 */
	private function getResponsibleUserPhone(array $responsibleUser)
	{
		if (isset($responsibleUser['WORK_PHONE']) && !empty($responsibleUser['WORK_PHONE']))
		{
			return (string)$responsibleUser['WORK_PHONE'];
		}
		elseif (isset($responsibleUser['PERSONAL_MOBILE']) && !empty($responsibleUser['PERSONAL_MOBILE']))
		{
			return (string)$responsibleUser['PERSONAL_MOBILE'];
		}
		elseif (isset($responsibleUser['PERSONAL_PHONE']) && !empty($responsibleUser['PERSONAL_PHONE']))
		{
			return (string)$responsibleUser['PERSONAL_PHONE'];
		}

		return null;
	}

	/**
	 * @param Shipment $shipment
	 * @return Result
	 */
	public function buildAddressFrom(Shipment $shipment): Result
	{
		return $this->buildAddress(
			$shipment,
			OrderPropsDictionary::ADDRESS_FROM_PROPERTY_CODE
		);
	}

	/**
	 * @param Shipment $shipment
	 * @return Result
	 */
	public function buildAddressTo(Shipment $shipment): Result
	{
		return $this->buildAddress(
			$shipment,
			OrderPropsDictionary::ADDRESS_TO_PROPERTY_CODE
		);
	}

	/**
	 * @param Shipment $shipment
	 * @param string $propertyCode
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function buildAddress(Shipment $shipment, string $propertyCode)
	{
		$result = new Result();

		if (!Loader::includeModule('location'))
		{
			return $result->addError(
				new Error(Loc::getMessage('SALE_YANDEX_TAXI_LOCATION_MODULE_REQUIRED'))
			);
		}

		$addressArray = $this->getPropertyValue($shipment, $propertyCode);
		if (!is_array($addressArray) || empty($addressArray))
		{
			return $result->addError(
				new Error(
					Loc::getMessage(
						'SALE_YANDEX_TAXI_FIELD_VALUE_NOT_SPECIFIED',
						[
							'#FIELD_NAME#' => $this->getPropertyName($shipment, $propertyCode)
						]
					)
				)
			);
		}

		$address = Entity\Address::fromArray($addressArray);

		return $result->setData(
			[
				'ADDRESS' => (new Address())
					->setFullName(
						$address->toString(
							FormatService::getInstance()->findDefault(LANGUAGE_ID),
							StringConverter::STRATEGY_TYPE_FIELD_SORT,
							StringConverter::CONTENT_TYPE_TEXT
						)
					)
					->setCoordinates([(float)$address->getLongitude(), (float)$address->getLatitude()])
			]
		);
	}

	/**
	 * @param Shipment $shipment
	 * @param string $propertyCode
	 * @return array|string|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getPropertyValue(Shipment $shipment, string $propertyCode)
	{
		$propertyValueObject = $this->getPropertyValueObject($shipment, $propertyCode);
		if (!$propertyValueObject)
		{
			return null;
		}

		return $propertyValueObject->getValue();
	}

	/**
	 * @param Shipment $shipment
	 * @param string $propertyCode
	 * @return mixed|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getPropertyName(Shipment $shipment, string $propertyCode)
	{
		$propertyValue = $this->getPropertyValueObject($shipment, $propertyCode);
		if (!$propertyValue)
		{
			return null;
		}

		/** @var Property $property */
		$property = $propertyValue->getPropertyObject();

		return $property->getName();
	}

	/**
	 * @param Shipment $shipment
	 * @param string $propertyCode
	 * @return \Bitrix\Sale\PropertyValue|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getPropertyValueObject(Shipment $shipment, string $propertyCode)
	{
		$propertyValueCollection = $shipment->getPropertyCollection();

		/** @var \Bitrix\Sale\PropertyValue $propertyValue */
		foreach ($propertyValueCollection as $propertyValue)
		{
			/** @var \Bitrix\Sale\Property $property */
			$property = $propertyValue->getPropertyObject();

			if ($property->getField('CODE') !== $propertyCode)
			{
				continue;
			}

			return $propertyValue;
		}

		return null;
	}
}
