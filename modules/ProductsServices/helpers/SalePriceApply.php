<?php
/**
 * Ghi một lần bảng giá khách lẻ tháng này (sheet Giá bán Sale NL_150926)
 * vào năm cột giá trên từng hàng hoá. Khớp theo tên. Không đụng giá Tuibao / Miutea.
 * Lần sau khách sửa giá trên Hàng hoá thì không ghi đè.
 */
class ProductsServices_SalePriceApply_Helper {

	const APPLY_KEY = 'sale_nl_150926';

	public static function ensureApplied() {
		static $ran = false;
		if ($ran) {
			return;
		}
		$ran = true;
		try {
			$db = PearDatabase::getInstance();
			self::ensurePriceColumns($db);
			self::ensureExtraColumns($db);
			self::ensureLogTable($db);
			self::applyOnce();
			self::applyQtyOnce();
			self::applyQtyPackOnce();
			self::applyQtyNameOnce();
			self::applyUnitsOnce();
			self::applyChainOnce();
		} catch (Exception $e) {
			// Báo giá vẫn mở được nếu ghi giá lỗi.
		}
	}

	protected static function applyOnce() {
		$db = PearDatabase::getInstance();
		self::ensurePriceColumns($db);
		self::ensureLogTable($db);
		$done = $db->pquery(
			'SELECT apply_key FROM vtiger_mk_sale_price_apply WHERE apply_key = ? LIMIT 1',
			array(self::APPLY_KEY)
		);
		if ($done && $db->num_rows($done) > 0) {
			return;
		}

		$sheet = self::loadSheet();
		if (!$sheet) {
			return;
		}
		$products = self::loadProducts($db);
		if (!$products) {
			return;
		}

		$byExact = array();
		$byLoose = array();
		foreach ($sheet as $index => $row) {
			$exact = self::fold($row['name']);
			$loose = self::loose($row['name']);
			if ($exact !== '') {
				$byExact[$exact][] = $index;
			}
			if ($loose !== '') {
				$byLoose[$loose][] = $index;
			}
		}

		$looseProductCount = array();
		foreach ($products as $product) {
			$loose = self::loose($product['name']);
			if ($loose === '') {
				continue;
			}
			if (!isset($looseProductCount[$loose])) {
				$looseProductCount[$loose] = 0;
			}
			$looseProductCount[$loose]++;
		}

		$usedSheet = array();
		$updates = array();
		foreach ($products as $product) {
			$exact = self::fold($product['name']);
			if ($exact !== '' && isset($byExact[$exact]) && count($byExact[$exact]) === 1) {
				$index = $byExact[$exact][0];
				$updates[] = array($product['id'], $sheet[$index]);
				$usedSheet[$index] = true;
			}
		}

		$matchedIds = array();
		foreach ($updates as $item) {
			$matchedIds[$item[0]] = true;
		}
		foreach ($products as $product) {
			if (isset($matchedIds[$product['id']])) {
				continue;
			}
			$loose = self::loose($product['name']);
			if ($loose === '' || empty($byLoose[$loose]) || count($byLoose[$loose]) !== 1) {
				continue;
			}
			if (empty($looseProductCount[$loose]) || $looseProductCount[$loose] !== 1) {
				continue;
			}
			$index = $byLoose[$loose][0];
			if (!empty($usedSheet[$index])) {
				continue;
			}
			$updates[] = array($product['id'], $sheet[$index]);
			$usedSheet[$index] = true;
			$matchedIds[$product['id']] = true;
		}

		if (!$updates) {
			return;
		}

		foreach ($updates as $item) {
			$row = $item[1];
			$db->pquery(
				'UPDATE vtiger_productsservices
				 SET price = ?, price_lt_1m = ?, price_gte_1m = ?, price_gte_3m = ?, price_gte_5m = ?, price_gte_7m = ?
				 WHERE productsservicesid = ?',
				array(
					$row['price_lt_1m'],
					$row['price_lt_1m'],
					$row['price_gte_1m'],
					$row['price_gte_3m'],
					$row['price_gte_5m'],
					$row['price_gte_7m'],
					$item[0],
				)
			);
		}

		$unmatched = array();
		foreach ($sheet as $index => $row) {
			if (empty($usedSheet[$index])) {
				$unmatched[] = $row['name'];
			}
		}
		$note = implode("\n", $unmatched);
		if (function_exists('mb_substr')) {
			$note = mb_substr($note, 0, 4000, 'UTF-8');
		} else {
			$note = substr($note, 0, 4000);
		}
		$db->pquery(
			'INSERT IGNORE INTO vtiger_mk_sale_price_apply (apply_key, updated_rows, unmatched_count, unmatched_names, applied_at)
			 VALUES (?, ?, ?, ?, ?)',
			array(
				self::APPLY_KEY,
				count($updates),
				count($unmatched),
				$note,
				date('Y-m-d H:i:s'),
			)
		);
	}

	protected static function applyQtyOnce() {
		$db = PearDatabase::getInstance();
		if (self::alreadyApplied($db, 'sale_nl_qty_150926')) {
			return;
		}
		$sheet = self::loadNamedJson('sale_nl_150926_qty.json');
		$products = self::loadProducts($db);
		$matched = self::matchRows($sheet, $products);
		if (!$matched['updates']) {
			return;
		}
		foreach ($matched['updates'] as $item) {
			$row = $item[1];
			$payload = json_encode(array(
				'sell_unit' => isset($row['sell_unit']) ? $row['sell_unit'] : '',
				'per_thung' => isset($row['per_thung']) ? (int) $row['per_thung'] : 0,
				'rules' => isset($row['rules']) && is_array($row['rules']) ? $row['rules'] : array(),
			), JSON_UNESCAPED_UNICODE);
			$db->pquery(
				'UPDATE vtiger_productsservices SET kl_qty_rules = ? WHERE productsservicesid = ?',
				array($payload, $item[0])
			);
		}
		self::markApplied($db, 'sale_nl_qty_150926', $matched, $sheet);
	}

	protected static function applyQtyPackOnce() {
		$db = PearDatabase::getInstance();
		if (self::alreadyApplied($db, 'sale_nl_qty_pack2')) {
			return;
		}
		$sheet = self::loadNamedJson('sale_nl_150926_qty.json');
		$products = self::loadProducts($db);
		$matched = self::matchRows($sheet, $products);
		if (!$matched['updates']) {
			return;
		}
		foreach ($matched['updates'] as $item) {
			$row = $item[1];
			$payload = json_encode(array(
				'sell_unit' => isset($row['sell_unit']) ? $row['sell_unit'] : '',
				'per_thung' => isset($row['per_thung']) ? (int) $row['per_thung'] : 0,
				'rules' => isset($row['rules']) && is_array($row['rules']) ? $row['rules'] : array(),
			), JSON_UNESCAPED_UNICODE);
			$db->pquery(
				'UPDATE vtiger_productsservices SET kl_qty_rules = ? WHERE productsservicesid = ?',
				array($payload, $item[0])
			);
		}
		self::markApplied($db, 'sale_nl_qty_pack2', $matched, $sheet);
	}

	protected static function applyQtyNameOnce() {
		self::rewriteQtyRules('sale_nl_qty_name3');
	}

	protected static function rewriteQtyRules($key) {
		$db = PearDatabase::getInstance();
		if (self::alreadyApplied($db, $key)) {
			return;
		}
		$sheet = self::loadNamedJson('sale_nl_150926_qty.json');
		$products = self::loadProducts($db);
		$matched = self::matchRows($sheet, $products);
		if (!$matched['updates']) {
			return;
		}
		foreach ($matched['updates'] as $item) {
			$row = $item[1];
			$payload = json_encode(array(
				'sell_unit' => isset($row['sell_unit']) ? $row['sell_unit'] : '',
				'per_thung' => isset($row['per_thung']) ? (int) $row['per_thung'] : 0,
				'rules' => isset($row['rules']) && is_array($row['rules']) ? $row['rules'] : array(),
			), JSON_UNESCAPED_UNICODE);
			$db->pquery(
				'UPDATE vtiger_productsservices SET kl_qty_rules = ? WHERE productsservicesid = ?',
				array($payload, $item[0])
			);
		}
		self::markApplied($db, $key, $matched, $sheet);
	}

	protected static function applyUnitsOnce() {
		$db = PearDatabase::getInstance();
		if (self::alreadyApplied($db, 'sale_nl_units_150926')) {
			return;
		}
		$check = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array('unit'));
		if (!$check || $db->num_rows($check) < 1) {
			return;
		}
		$sheet = self::loadNamedJson('sale_nl_units.json');
		$products = self::loadProducts($db);
		$matched = self::matchRows($sheet, $products);
		if (!$matched['updates']) {
			return;
		}
		foreach ($matched['updates'] as $item) {
			$unit = trim((string) (isset($item[1]['unit']) ? $item[1]['unit'] : ''));
			if ($unit === '') {
				continue;
			}
			$db->pquery(
				'UPDATE vtiger_productsservices SET unit = ? WHERE productsservicesid = ?',
				array($unit, $item[0])
			);
		}
		self::markApplied($db, 'sale_nl_units_150926', $matched, $sheet);
	}

	protected static function applyChainOnce() {
		$db = PearDatabase::getInstance();
		if (self::alreadyApplied($db, 'chain_tb_mt_t926')) {
			return;
		}
		$sheet = self::loadNamedJson('chain_tb_mt_t9_26.json');
		$products = self::loadProducts($db);
		$matched = self::matchRows($sheet, $products);
		if (!$matched['updates']) {
			return;
		}
		foreach ($matched['updates'] as $item) {
			$row = $item[1];
			$price = self::money(isset($row['price']) ? $row['price'] : 0);
			$noDiscount = !empty($row['no_discount']) ? 1 : 0;
			$db->pquery(
				'UPDATE vtiger_productsservices
				 SET price_tuibao = ?, price_miutea = ?, mk_no_discount = ?
				 WHERE productsservicesid = ?',
				array($price, $price, $noDiscount, $item[0])
			);
		}
		self::markApplied($db, 'chain_tb_mt_t926', $matched, $sheet);
	}

	protected static function alreadyApplied(PearDatabase $db, $key) {
		$done = $db->pquery(
			'SELECT apply_key FROM vtiger_mk_sale_price_apply WHERE apply_key = ? LIMIT 1',
			array($key)
		);
		return $done && $db->num_rows($done) > 0;
	}

	protected static function markApplied(PearDatabase $db, $key, array $matched, array $sheet) {
		$unmatched = array();
		foreach ($sheet as $index => $row) {
			if (empty($matched['used'][$index])) {
				$unmatched[] = $row['name'];
			}
		}
		$note = implode("\n", $unmatched);
		if (function_exists('mb_substr')) {
			$note = mb_substr($note, 0, 4000, 'UTF-8');
		} else {
			$note = substr($note, 0, 4000);
		}
		$db->pquery(
			'INSERT IGNORE INTO vtiger_mk_sale_price_apply (apply_key, updated_rows, unmatched_count, unmatched_names, applied_at)
			 VALUES (?, ?, ?, ?, ?)',
			array($key, count($matched['updates']), count($unmatched), $note, date('Y-m-d H:i:s'))
		);
	}

	/**
	 * @param array $sheet rows with name
	 * @param array $products
	 * @return array{updates:array,used:array}
	 */
	protected static function matchRows(array $sheet, array $products) {
		$byExact = array();
		$byLoose = array();
		foreach ($sheet as $index => $row) {
			if (empty($row['name'])) {
				continue;
			}
			$exact = self::fold($row['name']);
			$loose = self::loose($row['name']);
			if ($exact !== '') {
				$byExact[$exact][] = $index;
			}
			if ($loose !== '') {
				$byLoose[$loose][] = $index;
			}
		}
		$used = array();
		$updates = array();
		$matchedIds = array();
		foreach ($products as $product) {
			$exact = self::fold($product['name']);
			if ($exact !== '' && isset($byExact[$exact]) && count($byExact[$exact]) === 1) {
				$index = $byExact[$exact][0];
				$updates[] = array($product['id'], $sheet[$index]);
				$used[$index] = true;
				$matchedIds[$product['id']] = true;
			}
		}
		foreach ($products as $product) {
			if (isset($matchedIds[$product['id']])) {
				continue;
			}
			$loose = self::loose($product['name']);
			if ($loose === '' || empty($byLoose[$loose]) || count($byLoose[$loose]) !== 1) {
				continue;
			}
			$index = $byLoose[$loose][0];
			$updates[] = array($product['id'], $sheet[$index]);
			$used[$index] = true;
			$matchedIds[$product['id']] = true;
		}
		return array('updates' => $updates, 'used' => $used);
	}

	protected static function loadNamedJson($fileName) {
		$path = dirname(__FILE__) . '/data/' . $fileName;
		if (!is_file($path)) {
			return array();
		}
		$data = json_decode(file_get_contents($path), true);
		if (!is_array($data)) {
			return array();
		}
		$out = array();
		foreach ($data as $row) {
			if (!is_array($row) || empty($row['name'])) {
				continue;
			}
			$row['name'] = trim((string) $row['name']);
			$out[] = $row;
		}
		return $out;
	}

	protected static function loadSheet() {
		$path = dirname(__FILE__) . '/data/sale_nl_150926.json';
		if (!is_file($path)) {
			return array();
		}
		$raw = file_get_contents($path);
		$data = json_decode($raw, true);
		if (!is_array($data)) {
			return array();
		}
		$out = array();
		foreach ($data as $row) {
			if (!is_array($row) || empty($row['name'])) {
				continue;
			}
			$out[] = array(
				'name' => trim((string) $row['name']),
				'price_lt_1m' => self::money(isset($row['price_lt_1m']) ? $row['price_lt_1m'] : 0),
				'price_gte_1m' => self::money(isset($row['price_gte_1m']) ? $row['price_gte_1m'] : 0),
				'price_gte_3m' => self::money(isset($row['price_gte_3m']) ? $row['price_gte_3m'] : 0),
				'price_gte_5m' => self::money(isset($row['price_gte_5m']) ? $row['price_gte_5m'] : 0),
				'price_gte_7m' => self::money(isset($row['price_gte_7m']) ? $row['price_gte_7m'] : 0),
			);
		}
		return $out;
	}

	protected static function loadProducts(PearDatabase $db) {
		$rs = $db->pquery(
			'SELECT ps.productsservicesid AS id, ps.productsservicesname AS name
			 FROM vtiger_productsservices ps
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0',
			array()
		);
		$out = array();
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$name = trim((string) $row['name']);
			if (function_exists('decode_html')) {
				$name = trim(decode_html($name));
			}
			if ($name === '') {
				continue;
			}
			$out[] = array(
				'id' => (int) $row['id'],
				'name' => $name,
			);
		}
		return $out;
	}

	protected static function ensureExtraColumns(PearDatabase $db) {
		$check = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array('kl_qty_rules'));
		if (!$check || $db->num_rows($check) < 1) {
			$db->pquery('ALTER TABLE vtiger_productsservices ADD COLUMN kl_qty_rules TEXT NULL', array());
		}
		$check = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array('mk_no_discount'));
		if (!$check || $db->num_rows($check) < 1) {
			$db->pquery(
				'ALTER TABLE vtiger_productsservices ADD COLUMN mk_no_discount TINYINT(1) NOT NULL DEFAULT 0',
				array()
			);
		}
		foreach (array('price_tuibao', 'price_miutea') as $col) {
			$check = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array($col));
			if ($check && $db->num_rows($check) > 0) {
				continue;
			}
			$db->pquery(
				"ALTER TABLE vtiger_productsservices ADD COLUMN `$col` DECIMAL(25,8) DEFAULT NULL",
				array()
			);
		}
	}

	protected static function ensurePriceColumns(PearDatabase $db) {
		foreach (array('price_lt_1m', 'price_gte_1m', 'price_gte_3m', 'price_gte_5m', 'price_gte_7m') as $col) {
			$check = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array($col));
			if ($check && $db->num_rows($check) > 0) {
				continue;
			}
			$db->pquery(
				"ALTER TABLE vtiger_productsservices ADD COLUMN `$col` DECIMAL(25,8) DEFAULT NULL",
				array()
			);
		}
	}

	protected static function ensureLogTable(PearDatabase $db) {
		$db->pquery(
			'CREATE TABLE IF NOT EXISTS vtiger_mk_sale_price_apply (
				apply_key VARCHAR(64) NOT NULL,
				updated_rows INT NOT NULL DEFAULT 0,
				unmatched_count INT NOT NULL DEFAULT 0,
				unmatched_names TEXT NULL,
				applied_at DATETIME NOT NULL,
				PRIMARY KEY (apply_key)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
			array()
		);
	}

	protected static function money($value) {
		return (float) $value;
	}

	protected static function loose($value) {
		$value = preg_replace('/\([^)]*\)/u', ' ', (string) $value);
		return self::fold($value);
	}

	protected static function fold($value) {
		$value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
		if (function_exists('decode_html')) {
			$value = decode_html($value);
		}
		$value = mb_strtolower(trim($value), 'UTF-8');
		$value = strtr($value, self::vietnameseMap());
		$value = preg_replace('/[^a-z0-9]+/', '', $value);
		return (string) $value;
	}

	protected static function vietnameseMap() {
		static $map = null;
		if ($map === null) {
			$map = array(
				'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
				'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
				'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
				'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
				'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
				'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
				'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
				'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
				'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
				'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
				'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
				'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
				'đ' => 'd',
			);
		}
		return $map;
	}
}
