<?php
/**
 * Import warehouse stock from Kiot/excel "Xuat-Nhap-Ton chi tiet" report.
 *
 * Map:
 *   Mã hàng       → catalog sku
 *   Tồn cuối kì   → quantity
 *   name / price  → ProductsServices when the SKU already exists
 *   SKU chưa có   → tạo Hàng hoá từ Mã hàng + Tên hàng rồi ghi tồn
 *   HSD           → left null
 */
class Warehouse_StockExcelImport_Helper {

	const DEFAULT_LOT = '—';

	/**
	 * @param string $path
	 * @return array[]
	 */
	public static function parseAssocRows($path) {
		if (!is_file($path)) {
			throw new Exception('File Excel không tồn tại.');
		}
		$zip = new ZipArchive();
		if ($zip->open($path) !== true) {
			throw new Exception('Không mở được file Excel (.xlsx).');
		}
		$shared = array();
		if (($idx = $zip->locateName('xl/sharedStrings.xml')) !== false) {
			$xml = simplexml_load_string($zip->getFromIndex($idx));
			foreach ($xml->xpath('//*[local-name()="si"]') as $si) {
				$s = '';
				foreach ($si->xpath('.//*[local-name()="t"]') as $t) {
					$s .= (string) $t;
				}
				$shared[] = $s;
			}
		}
		$target = 'xl/worksheets/sheet1.xml';
		if ($zip->locateName($target) === false) {
			for ($i = 1; $i <= 5; $i++) {
				$cand = "xl/worksheets/sheet{$i}.xml";
				if ($zip->locateName($cand) !== false) {
					$target = $cand;
					break;
				}
			}
		}
		$sheetXml = $zip->getFromName($target);
		$zip->close();
		if ($sheetXml === false) {
			throw new Exception('Không đọc được sheet Excel.');
		}
		$sx = simplexml_load_string($sheetXml);
		$sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
		$grid = array();
		$maxRow = 0;
		$maxCol = 0;
		foreach ($sx->xpath('//m:c') as $c) {
			$ref = (string) $c['r'];
			if ($ref === '' || !preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
				continue;
			}
			$col = 0;
			foreach (str_split($m[1]) as $ch) {
				$col = $col * 26 + (ord($ch) - 64);
			}
			$row = (int) $m[2];
			$maxRow = max($maxRow, $row);
			$maxCol = max($maxCol, $col);
			$t = (string) $c['t'];
			$raw = (string) $c->v;
			if ($t === 's') {
				$i = (int) $raw;
				$val = isset($shared[$i]) ? $shared[$i] : $raw;
			} else {
				$val = $raw;
			}
			$grid[$row][$col] = $val;
		}
		$headers = array();
		for ($c = 1; $c <= $maxCol; $c++) {
			$headers[$c] = isset($grid[1][$c]) ? trim((string) $grid[1][$c]) : '';
		}
		$out = array();
		for ($r = 2; $r <= $maxRow; $r++) {
			$assoc = array();
			$any = false;
			for ($c = 1; $c <= $maxCol; $c++) {
				$h = $headers[$c];
				if ($h === '') {
					continue;
				}
				$v = isset($grid[$r][$c]) ? trim((string) $grid[$r][$c]) : '';
				if ($v !== '') {
					$any = true;
				}
				$assoc[$h] = $v;
			}
			if ($any) {
				$out[] = $assoc;
			}
		}
		return $out;
	}

	protected static function num($v) {
		$v = trim((string) $v);
		if ($v === '') {
			return 0.0;
		}
		$v = str_replace(array(',', ' '), '', $v);
		return (float) $v;
	}

	/**
	 * Tạo Hàng hoá khi Excel có SKU mà catalog chưa có.
	 *
	 * @param string $sku
	 * @param string $name
	 * @param int $userId
	 * @return array{id:int,name:string,price:float}
	 */
	protected static function createCatalogProduct($sku, $name, $userId) {
		$sku = trim((string) $sku);
		$name = trim((string) $name);
		if ($name === '') {
			$name = $sku;
		}
		$ownerId = (int) $userId > 0 ? (int) $userId : 1;
		$record = Vtiger_Record_Model::getCleanInstance('ProductsServices');
		$record->set('mode', '');
		$record->set('productsservicesname', $name);
		$record->set('sku', $sku);
		$record->set('item_type', 'Product');
		$record->set('price', 0);
		$record->set('assigned_user_id', $ownerId);
		$record->save();
		$id = (int) $record->getId();
		if ($id <= 0) {
			throw new Exception('Không tạo được hàng hoá.');
		}
		return array(
			'id' => $id,
			'name' => $name,
			'price' => 0.0,
		);
	}

	protected static function decodeName($s) {
		$s = html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		return trim($s);
	}

	/**
	 * @param PearDatabase $db
	 * @param string $warehouseCode
	 * @param string $filePath
	 * @param bool $wipe
	 * @param int $userId
	 * @return array
	 */
	public static function import(PearDatabase $db, $warehouseCode, $filePath, $wipe = true, $userId = 0) {
		$warehouseCode = trim((string) $warehouseCode);
		if ($warehouseCode === '') {
			throw new Exception('Thiếu mã kho.');
		}
		$whRs = $db->pquery(
			'SELECT code, name FROM vtiger_warehouse WHERE code = ? AND deleted = 0 LIMIT 1',
			array($warehouseCode)
		);
		if (!$whRs || $db->num_rows($whRs) === 0) {
			throw new Exception('Không tìm thấy kho: ' . $warehouseCode);
		}
		$whName = self::decodeName($db->query_result($whRs, 0, 'name'));

		$rows = self::parseAssocRows($filePath);
		if (empty($rows)) {
			throw new Exception('File Excel không có dòng dữ liệu.');
		}

		$catalog = array();
		$crs = $db->pquery(
			'SELECT ps.productsservicesid AS id, ps.sku, ps.productsservicesname AS name, IFNULL(ps.price, 0) AS price
			 FROM vtiger_productsservices ps
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
			 WHERE IFNULL(ps.sku, \'\') <> \'\'',
			array()
		);
		while ($crs && ($row = $db->fetchByAssoc($crs))) {
			$sku = trim((string) $row['sku']);
			if ($sku === '') {
				continue;
			}
			$catalog[$sku] = array(
				'id' => (int) $row['id'],
				'name' => self::decodeName($row['name']),
				'price' => (float) $row['price'],
			);
		}

		if ($wipe) {
			$db->pquery('DELETE FROM vtiger_warehouse_stock WHERE warehouse_id = ?', array($warehouseCode));
		}

		$stats = array(
			'excel' => 0,
			'matched' => 0,
			'created_sku' => 0,
			'missing_sku' => 0,
			'updated' => 0,
			'inserted' => 0,
			'skipped_empty_sku' => 0,
		);
		$missing = array();
		$now = date('Y-m-d H:i:s');
		$lot = self::DEFAULT_LOT;

		foreach ($rows as $row) {
			$stats['excel']++;
			$sku = isset($row['Mã hàng']) ? trim((string) $row['Mã hàng']) : '';
			if ($sku === '') {
				$stats['skipped_empty_sku']++;
				continue;
			}
			$qty = self::num(isset($row['Tồn cuối kì']) ? $row['Tồn cuối kì'] : '0');
			$excelName = isset($row['Tên hàng']) ? trim((string) $row['Tên hàng']) : '';

			if (!isset($catalog[$sku])) {
				try {
					$catalog[$sku] = self::createCatalogProduct($sku, $excelName, $userId);
					$stats['created_sku']++;
				} catch (Exception $ex) {
					$stats['missing_sku']++;
					if (count($missing) < 40) {
						$missing[] = $sku . ($excelName !== '' ? ' (' . $excelName . ')' : '') . ': ' . $ex->getMessage();
					}
					continue;
				}
			}

			$stats['matched']++;
			$pid = $catalog[$sku]['id'];
			$name = $catalog[$sku]['name'] !== '' ? $catalog[$sku]['name'] : $excelName;
			$price = $catalog[$sku]['price'];
			$key = $warehouseCode . '|' . $sku . '|' . $lot;

			if (!$wipe) {
				$db->pquery(
					'DELETE FROM vtiger_warehouse_stock
					 WHERE warehouse_id = ? AND product_key <> ?
					   AND (product_key LIKE ? OR productid = ?)',
					array($warehouseCode, $key, $warehouseCode . '|' . $sku . '|%', $pid)
				);
			}

			$find = $db->pquery(
				'SELECT stockid FROM vtiger_warehouse_stock WHERE product_key = ? LIMIT 1',
				array($key)
			);
			if ($find && $db->num_rows($find) > 0) {
				$sid = (int) $db->query_result($find, 0, 'stockid');
				$db->pquery(
					'UPDATE vtiger_warehouse_stock
					 SET productid = ?, product_name = ?, quantity = ?, last_price = ?,
					     warehouse_name = ?, expired_date = NULL, updatedtime = ?, updatedby = ?
					 WHERE stockid = ?',
					array($pid, $name, $qty, $price, $whName, $now, (int) $userId, $sid)
				);
				$stats['updated']++;
			} else {
				$sid = (int) $db->getUniqueID('vtiger_warehouse_stock');
				$code = 'STK-' . str_pad((string) $sid, 4, '0', STR_PAD_LEFT);
				$db->pquery(
					'INSERT INTO vtiger_warehouse_stock
					 (stockid, code, product_key, productid, product_name, quantity, last_price,
					  warehouse_id, warehouse_name, shrinkage_qty, expired_date, createdtime, updatedtime, updatedby)
					 VALUES (?,?,?,?,?,?,?,?,?,0,NULL,?,?,?)',
					array(
						$sid,
						$code,
						$key,
						$pid,
						$name,
						$qty,
						$price,
						$warehouseCode,
						$whName,
						$now,
						$now,
						(int) $userId,
					)
				);
				$stats['inserted']++;
			}
		}

		return array(
			'stats' => $stats,
			'missing' => $missing,
			'warehouse' => $warehouseCode,
			'warehouse_name' => $whName,
			'wipe' => (bool) $wipe,
		);
	}
}
