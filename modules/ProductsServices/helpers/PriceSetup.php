<?php
/*+***********************************************************************************
 * Hàng hoá: cột Giá Miutea + bậc chiết khấu KL / Miutea (nhập tay, không đồng bộ Sheet).
 *************************************************************************************/

class ProductsServices_PriceSetup_Helper {

	public static function ensure() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$db = PearDatabase::getInstance();
		self::ensurePriceColumn($db);
		self::ensurePriceField($db);
		self::ensureTierTable($db);
	}

	public static function canEdit() {
		return Users_Privileges_Model::isPermitted('ProductsServices', 'EditView');
	}

	/**
	 * @return array{kl_value:array,kl_qty:array,miutea_value:array,can_edit:bool}
	 */
	public static function getPolicy($allowSeed = true) {
		self::ensure();
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT segment, basis, min_value, percent
			 FROM vtiger_mk_discount_tier
			 ORDER BY segment ASC, basis ASC, min_value DESC',
			array()
		);
		$grouped = array(
			'kl_value' => array(),
			'kl_qty' => array(),
			'miutea_value' => array(),
		);
		$hasRow = false;
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$hasRow = true;
			$key = self::policyKey($row['segment'], $row['basis']);
			if (!isset($grouped[$key])) {
				continue;
			}
			$grouped[$key][] = array(
				'min' => (float) $row['min_value'],
				'percent' => (float) $row['percent'],
			);
		}
		if (!$hasRow) {
			if (!$allowSeed) {
				$grouped = self::defaultPolicy();
				$grouped['can_edit'] = self::canEdit() ? 1 : 0;
				return $grouped;
			}
			self::seedDefaults($db);
			return self::getPolicy(false);
		}
		$grouped['can_edit'] = self::canEdit() ? 1 : 0;
		return $grouped;
	}

	/**
	 * @param array $input
	 * @return array
	 */
	public static function savePolicy(array $input) {
		if (!self::canEdit()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		self::ensure();
		$clean = array(
			'kl_value' => self::cleanTiers(isset($input['kl_value']) ? $input['kl_value'] : array()),
			'kl_qty' => self::cleanTiers(isset($input['kl_qty']) ? $input['kl_qty'] : array()),
			'miutea_value' => self::cleanTiers(isset($input['miutea_value']) ? $input['miutea_value'] : array()),
		);
		foreach ($clean as $key => $rows) {
			if (!$rows) {
				$clean[$key] = array(array('min' => 0, 'percent' => 0));
			}
		}
		$db = PearDatabase::getInstance();
		$db->pquery('DELETE FROM vtiger_mk_discount_tier', array());
		foreach ($clean as $key => $rows) {
			$parts = explode('_', $key, 2);
			$segment = $parts[0];
			$basis = $parts[1];
			foreach ($rows as $row) {
				$db->pquery(
					'INSERT INTO vtiger_mk_discount_tier (segment, basis, min_value, percent) VALUES (?, ?, ?, ?)',
					array($segment, $basis, $row['min'], $row['percent'])
				);
			}
		}
		return self::getPolicy();
	}

	public static function defaultPolicy() {
		return array(
			'kl_value' => array(
				array('min' => 50000000, 'percent' => 12),
				array('min' => 20000000, 'percent' => 8),
				array('min' => 10000000, 'percent' => 5),
				array('min' => 5000000, 'percent' => 2),
				array('min' => 0, 'percent' => 0),
			),
			'kl_qty' => array(
				array('min' => 500, 'percent' => 15),
				array('min' => 200, 'percent' => 10),
				array('min' => 100, 'percent' => 7),
				array('min' => 50, 'percent' => 5),
				array('min' => 20, 'percent' => 3),
				array('min' => 0, 'percent' => 0),
			),
			'miutea_value' => array(
				array('min' => 50000000, 'percent' => 12),
				array('min' => 20000000, 'percent' => 8),
				array('min' => 10000000, 'percent' => 5),
				array('min' => 5000000, 'percent' => 2),
				array('min' => 0, 'percent' => 0),
			),
		);
	}

	protected static function policyKey($segment, $basis) {
		$segment = strtolower(trim((string) $segment));
		$basis = strtolower(trim((string) $basis));
		if ($segment === 'kl' && $basis === 'value') {
			return 'kl_value';
		}
		if ($segment === 'kl' && $basis === 'qty') {
			return 'kl_qty';
		}
		if ($segment === 'miutea' && $basis === 'value') {
			return 'miutea_value';
		}
		return '';
	}

	protected static function cleanTiers($rows) {
		if (!is_array($rows)) {
			return array();
		}
		$out = array();
		$seen = array();
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$min = isset($row['min']) ? $row['min'] : 0;
			$percent = isset($row['percent']) ? $row['percent'] : 0;
			if (is_string($min)) {
				$min = str_replace(array(' ', '.'), '', $min);
				$min = str_replace(',', '.', $min);
			}
			if (is_string($percent)) {
				$percent = str_replace(',', '.', trim($percent));
			}
			$min = (float) $min;
			$percent = (float) $percent;
			if ($min < 0 || $percent < 0 || $percent > 100) {
				throw new Exception('Mốc phải từ 0 trở lên và % chiết khấu từ 0 đến 100.');
			}
			$minKey = (string) (int) round($min);
			if (isset($seen[$minKey])) {
				throw new Exception('Mỗi bậc trong cùng một bảng phải có mốc "Từ" khác nhau.');
			}
			$seen[$minKey] = true;
			$out[] = array(
				'min' => round($min, 2),
				'percent' => round($percent, 2),
			);
			if (count($out) > 20) {
				throw new Exception('Mỗi bảng chỉ tối đa 20 bậc.');
			}
		}
		usort($out, function ($a, $b) {
			if ($a['min'] == $b['min']) {
				return 0;
			}
			return ($a['min'] < $b['min']) ? 1 : -1;
		});
		return $out;
	}

	protected static function seedDefaults(PearDatabase $db) {
		$defaults = self::defaultPolicy();
		foreach ($defaults as $key => $rows) {
			$parts = explode('_', $key, 2);
			foreach ($rows as $row) {
				$db->pquery(
					'INSERT INTO vtiger_mk_discount_tier (segment, basis, min_value, percent) VALUES (?, ?, ?, ?)',
					array($parts[0], $parts[1], $row['min'], $row['percent'])
				);
			}
		}
	}

	protected static function ensureTierTable(PearDatabase $db) {
		$rs = $db->pquery('SHOW TABLES LIKE ?', array('vtiger_mk_discount_tier'));
		if ($rs && $db->num_rows($rs) > 0) {
			return;
		}
		$db->pquery(
			'CREATE TABLE vtiger_mk_discount_tier (
				segment VARCHAR(16) NOT NULL,
				basis VARCHAR(16) NOT NULL,
				min_value DECIMAL(25,2) NOT NULL DEFAULT 0,
				percent DECIMAL(8,2) NOT NULL DEFAULT 0,
				PRIMARY KEY (segment, basis, min_value)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			array()
		);
	}

	protected static function ensurePriceColumn(PearDatabase $db) {
		$rs = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array('price_miutea'));
		if ($rs && $db->num_rows($rs) > 0) {
			return;
		}
		$db->pquery(
			'ALTER TABLE vtiger_productsservices ADD COLUMN price_miutea DECIMAL(25,8) DEFAULT NULL',
			array()
		);
	}

	protected static function ensurePriceField(PearDatabase $db) {
		$tabRs = $db->pquery('SELECT tabid FROM vtiger_tab WHERE name = ? LIMIT 1', array('ProductsServices'));
		if (!$tabRs || !$db->num_rows($tabRs)) {
			return;
		}
		$tabId = (int) $db->query_result($tabRs, 0, 'tabid');
		$fieldRs = $db->pquery(
			'SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = ? LIMIT 1',
			array($tabId, 'price_miutea')
		);
		if ($fieldRs && $db->num_rows($fieldRs) > 0) {
			$db->pquery(
				"UPDATE vtiger_field SET presence = 0, displaytype = 1, fieldlabel = ? WHERE tabid = ? AND fieldname = ?",
				array('Giá Miutea', $tabId, 'price_miutea')
			);
			return;
		}
		require_once 'vtlib/Vtiger/Module.php';
		$module = Vtiger_Module::getInstance('ProductsServices');
		if (!$module) {
			return;
		}
		$block = Vtiger_Block::getInstance('LBL_INVOICE_PRICE_LIST', $module);
		if (!$block) {
			$block = Vtiger_Block::getInstance('LBL_PRODUCT_INFORMATION', $module);
		}
		if (!$block) {
			return;
		}
		$field = new Vtiger_Field();
		$field->name = 'price_miutea';
		$field->label = 'Giá Miutea';
		$field->uitype = 71;
		$field->column = 'price_miutea';
		$field->columntype = 'DECIMAL(25,8)';
		$field->typeofdata = 'N~O';
		$field->displaytype = 1;
		$field->presence = 0;
		$block->addField($field);
		if (class_exists('Vtiger_Cache')) {
			Vtiger_Cache::flushModuleCache('ProductsServices');
		}
	}
}
