<?php
/*+***********************************************************************************
 * One-step ProductsServices import into the current catalog columns:
 * SKU, tên, đơn vị, nhóm, giá theo bậc hóa đơn, Giá Tuibao, Giá Miutea.
 * Also accepts the Nguyên Khoa catalog workbook (sheet Tổng NL / CCDC).
 *************************************************************************************/

require_once 'modules/Accounts/helpers/TuibaoKiotImport.php';
require_once 'modules/ProductsServices/helpers/PriceSetup.php';
require_once 'modules/ProductsServices/helpers/NguyenKhoaExcelCatalog.php';

class ProductsServices_CatalogSimpleImport_Helper {

	public static function importUploadedFile(array $fileInfo, $userId = 0) {
		$path = isset($fileInfo['tmp_name']) ? (string) $fileInfo['tmp_name'] : '';
		$origName = isset($fileInfo['name']) ? (string) $fileInfo['name'] : '';
		if ($path === '' || !is_file($path)) {
			throw new Exception('Chưa chọn file Excel (.xlsx).');
		}
		if (!preg_match('/\.xlsx$/i', $origName) && !preg_match('/\.xlsx$/i', $path)) {
			throw new Exception('Chỉ hỗ trợ file .xlsx.');
		}
		ProductsServices_PriceSetup_Helper::ensure();
		if ($userId <= 0) {
			global $current_user;
			$userId = isset($current_user->id) ? (int) $current_user->id : 1;
		}

		$products = self::productsFromFile($path);
		if (empty($products)) {
			throw new Exception('File không có dòng hàng hoá. Cần cột SKU và Tên, hoặc file danh mục Nguyên Khoa.');
		}

		$stats = array(
			'imported' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors' => 0,
			'total' => count($products),
		);
		$errorSamples = array();

		global $VTIGER_BULK_SAVE_MODE;
		$prevBulk = isset($VTIGER_BULK_SAVE_MODE) ? $VTIGER_BULK_SAVE_MODE : false;
		$VTIGER_BULK_SAVE_MODE = true;

		foreach ($products as $product) {
			try {
				if ($product['sku'] === '' || $product['name'] === '') {
					$stats['skipped']++;
					continue;
				}
				$existingId = self::findProductId($product['sku']);
				self::saveProduct($existingId, $product, $userId);
				if ($existingId > 0) {
					$stats['updated']++;
				} else {
					$stats['imported']++;
				}
			} catch (Exception $ex) {
				$stats['errors']++;
				if (count($errorSamples) < 8) {
					$errorSamples[] = $product['sku'] . ': ' . $ex->getMessage();
				}
			}
		}

		$VTIGER_BULK_SAVE_MODE = $prevBulk;
		$stats['success'] = ($stats['imported'] + $stats['updated']) > 0;
		$stats['message'] = self::buildMessage($stats, $errorSamples);
		return $stats;
	}

	protected static function productsFromFile($path) {
		$rows = Accounts_TuibaoKiotImport_Helper::xlsxAssocRows($path);
		$mapped = self::mapHeaderRows($rows);
		if (!empty($mapped)) {
			return $mapped;
		}
		$parsed = ProductsServices_NguyenKhoaExcelCatalog_Helper::parse($path);
		$out = array();
		foreach ($parsed['products'] as $product) {
			$out[] = self::normalizeProduct($product);
		}
		return $out;
	}

	protected static function mapHeaderRows(array $rows) {
		if (empty($rows)) {
			return array();
		}
		$index = array();
		foreach (array_keys($rows[0]) as $header) {
			$index[self::fold($header)] = $header;
		}
		$skuKey = self::matchKey($index, array('sku', 'ma_hang', 'ma_sku', 'ma_san_pham', 'ma_sp'));
		$nameKey = self::matchKey($index, array('ten', 'ten_hang', 'ten_san_pham', 'ten_sp', 'productsservicesname', 'ten_hang_hoa'));
		if ($skuKey === '' || $nameKey === '') {
			return array();
		}
		$out = array();
		foreach ($rows as $row) {
			$product = self::normalizeProduct(array(
				'sku' => isset($row[$skuKey]) ? $row[$skuKey] : '',
				'name' => isset($row[$nameKey]) ? $row[$nameKey] : '',
				'unit' => self::valueOf($row, $index, array('don_vi', 'dvt', 'unit')),
				'product_group' => self::valueOf($row, $index, array('nhom', 'nhom_hang', 'product_group')),
				'price' => self::money(self::valueOf($row, $index, array('gia', 'gia_ban', 'don_gia', 'price'))),
				'price_lt_1m' => self::money(self::valueOf($row, $index, array('gia_duoi_1_trieu', 'gia_nho_hon_1_trieu', 'price_lt_1m', 'gia_1'))),
				'price_gte_1m' => self::money(self::valueOf($row, $index, array('gia_tu_1_trieu', 'price_gte_1m', 'gia_1m'))),
				'price_gte_3m' => self::money(self::valueOf($row, $index, array('gia_tu_3_trieu', 'price_gte_3m', 'gia_3m'))),
				'price_gte_5m' => self::money(self::valueOf($row, $index, array('gia_tu_5_trieu', 'price_gte_5m', 'gia_5m'))),
				'price_gte_7m' => self::money(self::valueOf($row, $index, array('gia_tu_7_trieu', 'price_gte_7m', 'gia_7m'))),
				'price_tuibao' => self::money(self::valueOf($row, $index, array('gia_tuibao', 'gia_tui_bao', 'price_tuibao'))),
				'price_miutea' => self::money(self::valueOf($row, $index, array('gia_miutea', 'price_miutea'))),
			));
			if ($product['sku'] !== '' || $product['name'] !== '') {
				$out[] = $product;
			}
		}
		return $out;
	}

	protected static function normalizeProduct(array $product) {
		$price = self::num(isset($product['price']) ? $product['price'] : null);
		$tiers = array(
			'price_lt_1m' => self::num(isset($product['price_lt_1m']) ? $product['price_lt_1m'] : null),
			'price_gte_1m' => self::num(isset($product['price_gte_1m']) ? $product['price_gte_1m'] : null),
			'price_gte_3m' => self::num(isset($product['price_gte_3m']) ? $product['price_gte_3m'] : null),
			'price_gte_5m' => self::num(isset($product['price_gte_5m']) ? $product['price_gte_5m'] : null),
			'price_gte_7m' => self::num(isset($product['price_gte_7m']) ? $product['price_gte_7m'] : null),
		);
		if ($tiers['price_lt_1m'] === null && $price !== null) {
			$tiers['price_lt_1m'] = $price;
		}
		$base = $tiers['price_lt_1m'] !== null ? $tiers['price_lt_1m'] : ($price !== null ? $price : 0);
		foreach ($tiers as $key => $value) {
			if ($value === null) {
				$tiers[$key] = $base;
			}
		}
		$tuibao = self::num(isset($product['price_tuibao']) ? $product['price_tuibao'] : null);
		$miutea = self::num(isset($product['price_miutea']) ? $product['price_miutea'] : null);
		return array(
			'sku' => trim((string) (isset($product['sku']) ? $product['sku'] : '')),
			'name' => trim((string) (isset($product['name']) ? $product['name'] : '')),
			'unit' => trim((string) (isset($product['unit']) ? $product['unit'] : '')),
			'product_group' => trim((string) (isset($product['product_group']) ? $product['product_group'] : '')),
			'price' => $base,
			'price_lt_1m' => $tiers['price_lt_1m'],
			'price_gte_1m' => $tiers['price_gte_1m'],
			'price_gte_3m' => $tiers['price_gte_3m'],
			'price_gte_5m' => $tiers['price_gte_5m'],
			'price_gte_7m' => $tiers['price_gte_7m'],
			'price_tuibao' => $tuibao !== null ? $tuibao : $base,
			'price_miutea' => $miutea,
		);
	}

	protected static function saveProduct($productId, array $product, $userId) {
		if ($productId > 0) {
			$record = Vtiger_Record_Model::getInstanceById($productId, 'ProductsServices');
			$record->set('mode', 'edit');
		} else {
			$record = Vtiger_Record_Model::getCleanInstance('ProductsServices');
			$record->set('mode', '');
			$record->set('assigned_user_id', (int) $userId);
			$record->set('item_type', 'Product');
		}
		$record->set('productsservicesname', $product['name']);
		$record->set('sku', $product['sku']);
		if ($product['unit'] !== '') {
			$record->set('unit', $product['unit']);
		}
		if ($product['product_group'] !== '') {
			$record->set('product_group', $product['product_group']);
		}
		foreach (array(
			'price', 'price_lt_1m', 'price_gte_1m', 'price_gte_3m', 'price_gte_5m', 'price_gte_7m', 'price_tuibao',
		) as $field) {
			$record->set($field, $product[$field]);
		}
		if ($product['price_miutea'] !== null) {
			$record->set('price_miutea', $product['price_miutea']);
		}
		$record->save();
		if ((int) $record->getId() <= 0) {
			throw new Exception('Không lưu được ' . $product['sku']);
		}
	}

	protected static function findProductId($sku) {
		global $adb;
		$res = $adb->pquery(
			'SELECT ps.productsservicesid FROM vtiger_productsservices ps
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
			 WHERE ps.sku = ? LIMIT 1',
			array($sku)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'productsservicesid');
		}
		return 0;
	}

	protected static function buildMessage(array $stats, array $errorSamples) {
		$imported = (int) $stats['imported'];
		$updated = (int) $stats['updated'];
		$total = (int) $stats['total'];
		if ($imported + $updated <= 0) {
			$msg = 'Import hàng hoá thất bại: 0/' . $total . ' dòng được ghi.';
		} else {
			$msg = 'Import hàng hoá xong: tạo ' . $imported . ', cập nhật ' . $updated . ' / ' . $total . ' dòng.';
		}
		if ((int) $stats['skipped'] > 0) {
			$msg .= ' Bỏ qua ' . (int) $stats['skipped'] . ' dòng thiếu SKU hoặc tên.';
		}
		if ((int) $stats['errors'] > 0) {
			$msg .= ' Lỗi ' . (int) $stats['errors'] . ' dòng.';
		}
		if (!empty($errorSamples)) {
			$msg .= "\n" . implode("\n", $errorSamples);
		}
		return $msg;
	}

	protected static function matchKey(array $index, array $aliases) {
		foreach ($aliases as $alias) {
			if (isset($index[$alias])) {
				return $index[$alias];
			}
		}
		return '';
	}

	protected static function valueOf(array $row, array $index, array $aliases) {
		$key = self::matchKey($index, $aliases);
		if ($key === '' || !isset($row[$key])) {
			return '';
		}
		return trim((string) $row[$key]);
	}

	protected static function money($raw) {
		$raw = trim((string) $raw);
		if ($raw === '') {
			return null;
		}
		$raw = str_replace(array(' ', '₫', 'đ'), '', $raw);
		if (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw)) {
			$raw = str_replace('.', '', $raw);
		} elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $raw)) {
			$raw = str_replace(',', '', $raw);
		} else {
			$raw = str_replace(',', '.', $raw);
		}
		if (!is_numeric($raw)) {
			return null;
		}
		return (float) $raw;
	}

	protected static function num($value) {
		if ($value === null || $value === '') {
			return null;
		}
		if (is_numeric($value)) {
			return (float) $value;
		}
		return self::money($value);
	}

	protected static function stripVietnamese($value) {
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
		return strtr($value, $map);
	}

	protected static function fold($value) {
		$value = (string) $value;
		if (function_exists('decode_html')) {
			$value = decode_html($value);
		}
		$value = mb_strtolower(trim($value), 'UTF-8');
		$value = self::stripVietnamese($value);
		$value = preg_replace('/[^a-z0-9]+/', '_', $value);
		return trim($value, '_');
	}
}
