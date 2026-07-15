<?php
/**
 * Shared ProductsServices form layout: hide + reorder fields for Detail/Edit.
 * Keep prior edits; only hide agreed-away fields (warranty / related / images).
 */
class ProductsServices_FormLayout_Helper {

	public static $hiddenFields = array(
		'warranty',
		'related_projects',
		'used_projects',
		'retail_price',
		'bulk_price',
	);

	public static $hiddenBlocks = array(
		'LBL_PROJECT_HISTORY',
	);

	/** Preferred order within each block (others keep relative order after). */
	public static $preferredOrder = array(
		'sku',
		'productsservicesname',
		'item_type',
		'unit',
		'price',
		'wholesale_price',
		'specification',
		'assigned_user_id',
	);

	public static function apply(array $values) {
		// Promote key fields into the main details block with Name.
		$moved = array();
		$promoteNames = array('sku', 'specification', 'unit', 'price', 'wholesale_price');
		foreach ($values as $blockLabel => $blockFields) {
			if (!is_array($blockFields)) {
				continue;
			}
			foreach ($blockFields as $fieldName => $fieldModel) {
				if (in_array($fieldName, $promoteNames, true)) {
					$moved[$fieldName] = array('block' => $blockLabel, 'model' => $fieldModel);
				}
			}
		}

		$nameBlock = null;
		foreach ($values as $blockLabel => $blockFields) {
			if (is_array($blockFields) && isset($blockFields['productsservicesname'])) {
				$nameBlock = $blockLabel;
				break;
			}
		}

		if ($nameBlock !== null) {
			foreach ($promoteNames as $promote) {
				if (!isset($moved[$promote])) {
					continue;
				}
				$from = $moved[$promote]['block'];
				if ($from === $nameBlock) {
					continue;
				}
				unset($values[$from][$promote]);
				if (!isset($values[$nameBlock]) || !is_array($values[$nameBlock])) {
					$values[$nameBlock] = array();
				}
				$values[$nameBlock][$promote] = $moved[$promote]['model'];
			}
		}

		$out = array();
		foreach ($values as $blockLabel => $blockFields) {
			if (in_array($blockLabel, self::$hiddenBlocks, true)) {
				continue;
			}
			if (!is_array($blockFields)) {
				$out[$blockLabel] = $blockFields;
				continue;
			}

			$filtered = array();
			foreach ($blockFields as $fieldName => $fieldModel) {
				if (in_array($fieldName, self::$hiddenFields, true)) {
					continue;
				}
				$filtered[$fieldName] = $fieldModel;
			}

			$ordered = array();
			foreach (self::$preferredOrder as $fieldName) {
				if (isset($filtered[$fieldName])) {
					$ordered[$fieldName] = $filtered[$fieldName];
					unset($filtered[$fieldName]);
				}
			}
			foreach ($filtered as $fieldName => $fieldModel) {
				$ordered[$fieldName] = $fieldModel;
			}

			if (empty($ordered)) {
				continue;
			}

			$out[$blockLabel] = $ordered;
		}
		return $out;
	}
}
