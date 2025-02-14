<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/**
 * @var array $arResult
 */

$publicMode = (isset($arParams['PUBLIC_MODE']) && $arParams['PUBLIC_MODE'] === true);
$fieldName = HtmlFilter::encode($arResult['userField']['FIELD_NAME']);
?>

<select
	name="<?= $arResult['fieldName'] ?>"
	id="<?= $fieldName ?>_select"
	class="mobile-grid-data-select"
	<?= ($arResult['userField']['MULTIPLE'] === 'Y' ? ' multiple' : '') ?>
>
	<?php
	foreach($arResult['valueCodes'] as $value)
	{
		$value = HtmlFilter::encode($value);
		$ar = explode('_', $value);
		?>
		<option
			value="<?= $value ?>"
			selected="selected"
			data-category="<?= mb_strtolower(ElementType::getLongEntityType($ar[0])) ?>"
		><?= $value ?></option>
		<?php
	}
	?>
</select>

<div>
	<?php
	foreach($arResult['value'] as $entityType => $arEntity)
	{
		if (empty($arEntity['items']))
		{
			continue;
		}

		if($arParams['PREFIX'])
		{
			?>
			<span class="mobile-grid-data-span mobile-grid-crm-element-category-title">
				<?= $arEntity['title'] ?>:
			</span>
			<?php
		}
		foreach($arEntity['items'] as $entityId => $entity)
		{
			?>
			<span class="mobile-grid-data-span">
				<?php
				if($publicMode)
				{
					print $entity['ENTITY_TITLE'];
				}
				else
				{
					// @todo remove after support dynamic entity in mobile
					$typeId = \CCrmOwnerType::ResolveID($entityType);
					if(!\CCrmOwnerType::isPossibleDynamicTypeId($typeId))
					{
					?>
					<a
						href="<?= $entity['ENTITY_LINK'] ?>"
						data-id="<?= ($entity['ENTITY_TYPE_ID_WITH_ENTITY_ID'] ?? $entityId) ?>"
					>
						<?= $entity['ENTITY_TITLE'] ?>
					</a>
					<?php
					}
					else
					{
						print $entity['ENTITY_TITLE'];
					}
				}
				?>
			</span>
			<?php
		}
	}
	?>
</div>

<a
	class="mobile-grid-button mobile-grid-button-select"
	href="javascript:void(0)"
	id="<?= $fieldName ?>"
>
	<?= Loc::getMessage('CRM_ELEMENT_BUTTON_SELECT') ?>
</a>

<script>
	<?php
		$nodes = [$fieldName];
		$messages = [];
		foreach ($arResult['value'] as $entityTypeName => $entityType)
		{
			$messages['CRM_ENTITY_TYPE_'.$entityTypeName] = $entityType['title'];
		}
	?>

	BX.message(<?= CUtil::phpToJSObject($messages) ?>);

	BX.ready(function ()
	{
		new BX.Mobile.Field.ElementCrm(
			<?=CUtil::PhpToJSObject([
				'name' => 'BX.Mobile.Field.ElementCrm',
				'nodes' => $nodes,
				'restrictedMode' => true,
				'formId' => $arParams['additionalParameters']['formId'],
				'gridId' => $arParams['additionalParameters']['gridId'],
				'useOnChangeEvent' => true,
				'availableTypes' => $arResult['availableTypes']
			])?>
		);
	});
</script>