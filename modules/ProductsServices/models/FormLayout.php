<?php
/**
 * Shared ProductsServices form layout: hide + reorder fields for Detail/Edit.
 * Keep prior edits; hide legacy price fields. Image upload (used_projects) is visible.
 */
class ProductsServices_FormLayout_Helper {

	public static $hiddenFields = array(
		'warranty',
		'related_projects',
		'retail_price',
		'bulk_price',
		'price',
		'wholesale_price',
	);

	public static $hiddenBlocks = array(
	);

	/** Preferred order within each block (others keep relative order after). Image is appended last. */
	public static $preferredOrder = array(
		'sku',
		'productsservicesname',
		'item_type',
		'product_group',
		'unit',
		'needs_qc',
		'expiry_warn_days',
		'price_tuibao',
		'price_lt_1m',
		'price_gte_1m',
		'price_gte_3m',
		'price_gte_5m',
		'price_gte_7m',
		'specification',
		'assigned_user_id',
	);

	public static function apply(array $values) {
		self::ensureImageFieldVisible();

		// Ensure image field is not presence-hidden from older setup scripts.
		foreach ($values as $blockLabel => $blockFields) {
			if (!is_array($blockFields) || !isset($blockFields['used_projects'])) {
				continue;
			}
			$imgField = $blockFields['used_projects'];
			if (is_object($imgField) && method_exists($imgField, 'set')) {
				$imgField->set('presence', 0);
				$imgField->set('displaytype', 1);
			}
		}

		// Promote key fields into the main details block with Name.
		$moved = array();
		$promoteNames = array(
			'sku', 'specification', 'unit', 'needs_qc', 'expiry_warn_days',
			'product_group', 'price_tuibao',
			'price_lt_1m', 'price_gte_1m', 'price_gte_3m', 'price_gte_5m', 'price_gte_7m',
		);
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

		return self::appendImageBlockLast($out);
	}

	/**
	 * Keep product photo as its own last block (Create/Edit), not mixed into SKU/Name.
	 */
	protected static function appendImageBlockLast(array $out) {
		$imageField = null;
		foreach ($out as $blockLabel => $blockFields) {
			if (!is_array($blockFields) || !isset($blockFields['used_projects'])) {
				continue;
			}
			$imageField = $blockFields['used_projects'];
			unset($out[$blockLabel]['used_projects']);
			if (empty($out[$blockLabel])) {
				unset($out[$blockLabel]);
			}
		}
		if ($imageField && is_object($imageField) && method_exists($imageField, 'set')) {
			$imageField->set('label', 'Hình ảnh');
			$imageField->set('presence', 0);
			$imageField->set('displaytype', 1);
		}
		if ($imageField) {
			unset($out['LBL_PROJECT_HISTORY']);
			$out['LBL_PROJECT_HISTORY'] = array('used_projects' => $imageField);
		}
		return $out;
	}

	/**
	 * Older setup scripts set used_projects presence=1 (hidden). Unhide for BA image upload.
	 */
	public static function ensureImageFieldVisible() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		try {
			$db = PearDatabase::getInstance();
			$tabRes = $db->pquery(
				"SELECT tabid FROM vtiger_tab WHERE name = ? LIMIT 1",
				array('ProductsServices')
			);
			if (!$tabRes || !$db->num_rows($tabRes)) {
				return;
			}
			$tabId = (int) $db->query_result($tabRes, 0, 'tabid');
			$db->pquery(
				"UPDATE vtiger_field
				 SET presence = 0, displaytype = 1, uitype = 69, typeofdata = 'V~O', fieldlabel = ?
				 WHERE tabid = ? AND fieldname = ?",
				array('Hình ảnh', $tabId, 'used_projects')
			);
		} catch (Exception $e) {
			/* ignore */
		}
	}
}
