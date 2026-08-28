<?php
/**
 * Warehouse management — DB service (mirrors MkWarehouseStore shape).
 */
require_once 'modules/Warehouse/data/WhMgmtSeedData.php';
require_once 'modules/Warehouse/helpers/WorkflowSetup.php';
require_once 'modules/Warehouse/helpers/StockHelper.php';

class Warehouse_WhMgmtService {

	public static function ensureInstalled() {
		$db = PearDatabase::getInstance();
		Warehouse_WorkflowSetup_Helper::runAll();
		self::ensureProductSkuField();
		self::ensureProductNeedsQcField();
		self::ensureProductExpiryWarnDaysField();
		require_once 'modules/Warehouse/helpers/ReturnHelper.php';
		Warehouse_Return_Helper::ensureSchema($db);
		if (!Warehouse_WorkflowSetup_Helper::isInstalled($db)) {
			self::seedAll($db);
		}
		self::backfillStockMfgDates($db);
	}

	/**
	 * Fill mfg_date on existing seed/demo stock rows that only have expiry.
	 */
	protected static function backfillStockMfgDates(PearDatabase $db) {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		foreach (Warehouse_WhMgmtSeedData::stockByWarehouse() as $whCode => $rows) {
			foreach ($rows as $item) {
				if (empty($item['mfg'])) {
					continue;
				}
				$key = self::stockProductKey($whCode, $item['sku'], $item['lot']);
				$db->pquery(
					'UPDATE vtiger_warehouse_stock
					 SET mfg_date = ?
					 WHERE product_key = ? AND (mfg_date IS NULL OR mfg_date = \'\')',
					array($item['mfg'], $key)
				);
			}
		}
	}

	/**
	 * Ensure Products & Services has an SKU column for warehouse linking.
	 */
	public static function ensureProductSkuField() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		require_once 'vtlib/Vtiger/Module.php';
		$module = Vtiger_Module::getInstance('ProductsServices');
		if (!$module) {
			return;
		}
		if (Vtiger_Field::getInstance('sku', $module)) {
			return;
		}
		$block = Vtiger_Block::getInstance('LBL_PRODUCTS_SERVICES_INFORMATION', $module);
		if (!$block) {
			$block = Vtiger_Block::getInstance('LBL_PRODUCT_INFORMATION', $module);
		}
		if (!$block) {
			return;
		}
		$field = new Vtiger_Field();
		$field->name = 'sku';
		$field->label = 'SKU';
		$field->uitype = 1;
		$field->column = 'sku';
		$field->columntype = 'VARCHAR(100)';
		$field->typeofdata = 'V~O';
		$block->addField($field);
	}

	/**
	 * Ensure product-level "Cần QC" checkbox exists for inbound routing.
	 */
	public static function ensureProductNeedsQcField() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		require_once 'vtlib/Vtiger/Module.php';
		$module = Vtiger_Module::getInstance('ProductsServices');
		if (!$module) {
			return;
		}
		if (Vtiger_Field::getInstance('needs_qc', $module)) {
			return;
		}
		$block = Vtiger_Block::getInstance('LBL_PRODUCT_INFORMATION', $module);
		if (!$block) {
			$block = Vtiger_Block::getInstance('LBL_PRODUCTS_SERVICES_INFORMATION', $module);
		}
		if (!$block) {
			return;
		}
		$field = new Vtiger_Field();
		$field->name = 'needs_qc';
		$field->label = 'Needs QC';
		$field->uitype = 56;
		$field->column = 'needs_qc';
		$field->columntype = 'TINYINT(1) DEFAULT 0';
		$field->typeofdata = 'C~O';
		$field->defaultvalue = '0';
		$block->addField($field);
		self::resetNeedsQcColumnCache();
	}

	/**
	 * Per-product expiry warning window (days). Empty/0 = use system default.
	 */
	public static function ensureProductExpiryWarnDaysField() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		require_once 'vtlib/Vtiger/Module.php';
		$module = Vtiger_Module::getInstance('ProductsServices');
		if (!$module) {
			return;
		}
		if (Vtiger_Field::getInstance('expiry_warn_days', $module)) {
			return;
		}
		$block = Vtiger_Block::getInstance('LBL_PRODUCT_INFORMATION', $module);
		if (!$block) {
			$block = Vtiger_Block::getInstance('LBL_PRODUCTS_SERVICES_INFORMATION', $module);
		}
		if (!$block) {
			return;
		}
		$field = new Vtiger_Field();
		$field->name = 'expiry_warn_days';
		$field->label = 'Expiry warn days';
		$field->uitype = 7;
		$field->column = 'expiry_warn_days';
		$field->columntype = 'INT(11) DEFAULT NULL';
		$field->typeofdata = 'I~O';
		$block->addField($field);
		self::$expiryWarnDaysColumnExists = null;
	}

	public static function publicDecode($value) {
		return self::decodeDisplayTextDeep($value);
	}

	public static function publicApplyInboundStockLine(PearDatabase $db, $whCode, $whName, array $line, $userId) {
		if (empty($line['sku']) && !empty($line['product_id'])) {
			$line['sku'] = self::resolveProductSku($db, (int) $line['product_id']);
		}
		if (empty($line['sku'])) {
			$line['sku'] = self::guessSkuFromName(isset($line['name']) ? $line['name'] : '');
		}
		if (!isset($line['product_id'])) {
			$line['product_id'] = 0;
		}
		if (!isset($line['price'])) {
			$line['price'] = 0;
		}
		self::applyInboundStockLine($db, $whCode, $whName, $line, $userId);
	}

	/**
	 * Whether catalog column needs_qc is available on vtiger_productsservices.
	 */
	protected static function resetNeedsQcColumnCache() {
		// Uses static $has in hasNeedsQcColumn via re-query when null; force recheck by sentinel.
		self::$needsQcColumnExists = null;
	}

	/** @var bool|null */
	protected static $needsQcColumnExists = null;

	/** @var bool|null */
	protected static $expiryWarnDaysColumnExists = null;

	protected static function hasNeedsQcColumn(PearDatabase $db = null) {
		if (self::$needsQcColumnExists !== null) {
			return self::$needsQcColumnExists;
		}
		if ($db === null) {
			$db = PearDatabase::getInstance();
		}
		try {
			$rs = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array('needs_qc'));
			self::$needsQcColumnExists = ($rs && $db->num_rows($rs) > 0);
		} catch (Exception $e) {
			self::$needsQcColumnExists = false;
		}
		return self::$needsQcColumnExists;
	}

	protected static function hasExpiryWarnDaysColumn(PearDatabase $db = null) {
		if (self::$expiryWarnDaysColumnExists !== null) {
			return self::$expiryWarnDaysColumnExists;
		}
		if ($db === null) {
			$db = PearDatabase::getInstance();
		}
		try {
			$rs = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array('expiry_warn_days'));
			self::$expiryWarnDaysColumnExists = ($rs && $db->num_rows($rs) > 0);
		} catch (Exception $e) {
			self::$expiryWarnDaysColumnExists = false;
		}
		return self::$expiryWarnDaysColumnExists;
	}

	/**
	 * Read product-level Cần QC flag (false when missing product / column / value).
	 */
	public static function productNeedsQc(PearDatabase $db, $productId) {
		$productId = (int) $productId;
		if ($productId <= 0 || !self::hasNeedsQcColumn($db)) {
			return false;
		}
		$rs = $db->pquery(
			'SELECT needs_qc FROM vtiger_productsservices WHERE productsservicesid = ? LIMIT 1',
			array($productId)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return false;
		}
		$raw = $db->query_result($rs, 0, 'needs_qc');
		return ($raw === 1 || $raw === '1' || $raw === true || $raw === 'on');
	}

	public static function seedAll(PearDatabase $db = null) {
		if ($db === null) {
			$db = PearDatabase::getInstance();
		}
		Warehouse_WorkflowSetup_Helper::runAll();
		self::seedWarehouses($db);
		self::seedStock($db);
		self::seedDemoDocuments($db);
	}

	protected static function seedWarehouses(PearDatabase $db) {
		foreach (Warehouse_WhMgmtSeedData::warehouses() as $row) {
			$exists = $db->pquery(
				'SELECT warehouseid FROM vtiger_warehouse WHERE code = ? AND deleted = 0 LIMIT 1',
				array($row['code'])
			);
			if ($exists && $db->num_rows($exists) > 0) {
				continue;
			}
			$id = (int) $db->getUniqueID('vtiger_warehouse');
			$db->pquery(
				'INSERT INTO vtiger_warehouse
				 (warehouseid, code, name, type, address, manager, status, createdtime, updatedtime, deleted)
				 VALUES (?,?,?,?,?,?,?,?,?,0)',
				array(
					$id,
					$row['code'],
					$row['name'],
					$row['type'],
					$row['address'],
					$row['manager'],
					$row['status'],
					$row['created_at'],
					$row['created_at'],
				)
			);
		}
	}

	protected static function stockProductKey($warehouseCode, $sku, $lot) {
		return $warehouseCode . '|' . $sku . '|' . $lot;
	}

	protected static function seedStock(PearDatabase $db) {
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$rs = $db->pquery('SELECT COUNT(*) AS c FROM vtiger_warehouse_stock', array());
		$existing = ($rs && $db->num_rows($rs) > 0) ? (int) $db->query_result($rs, 0, 'c') : 0;
		if ($existing > 0) {
			return;
		}
		self::resetStockFromCatalog($db, 'WH-001', 10, false);
	}

	/**
	 * Wipe warehouse stock and reseed from live ProductsServices catalog.
	 * SKU + price from catalog; lot / NSX / HSD are demo-friendly defaults; qty fixed per item.
	 *
	 * @param bool $wipeAll when true, DELETE all rows first (full reset)
	 * @return array{deleted:int,inserted:int,warehouse:string,qty:float}
	 */
	public static function resetStockFromCatalog(
		PearDatabase $db = null,
		$warehouseCode = 'WH-001',
		$qtyPerItem = 10,
		$wipeAll = true
	) {
		if ($db === null) {
			$db = PearDatabase::getInstance();
		}
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();
		Warehouse_WorkflowSetup_Helper::runAll();
		self::seedWarehouses($db);

		$warehouseCode = trim((string) $warehouseCode);
		if ($warehouseCode === '') {
			$warehouseCode = 'WH-001';
		}
		$qtyPerItem = (float) $qtyPerItem;
		if ($qtyPerItem < 0) {
			$qtyPerItem = 0;
		}

		$deleted = 0;
		if ($wipeAll) {
			$cntRs = $db->pquery('SELECT COUNT(*) AS c FROM vtiger_warehouse_stock', array());
			$deleted = ($cntRs && $db->num_rows($cntRs) > 0) ? (int) $db->query_result($cntRs, 0, 'c') : 0;
			$db->pquery('DELETE FROM vtiger_warehouse_stock', array());
		}

		$wh = self::findWarehouseRowByCode($db, $warehouseCode);
		$whName = $wh ? (string) $wh['name'] : $warehouseCode;

		$rs = $db->pquery(
			'SELECT ps.productsservicesid AS id, ps.productsservicesname AS name,
			        IFNULL(ps.sku, \'\') AS sku, IFNULL(ps.price, 0) AS price
			 FROM vtiger_productsservices ps
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
			 ORDER BY ps.productsservicesname ASC, ps.productsservicesid ASC',
			array()
		);

		$inserted = 0;
		$now = date('Y-m-d H:i:s');
		$mfg = '2026-01-15';
		$expiry = '2027-12-31';
		$idx = 0;
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$idx++;
			$productId = (int) $row['id'];
			$name = decode_html(trim((string) $row['name']));
			$sku = trim(decode_html((string) $row['sku']));
			$price = (float) $row['price'];
			$lot = 'LOT-TEST-' . str_pad((string) $idx, 3, '0', STR_PAD_LEFT);
			// Lot is unique per row so blank catalog SKU still yields a unique product_key.
			$key = self::stockProductKey($warehouseCode, $sku, $lot);

			$stockId = (int) $db->getUniqueID('vtiger_warehouse_stock');
			$code = 'STK-' . str_pad((string) $stockId, 4, '0', STR_PAD_LEFT);
			$loc = 'A' . ((($idx - 1) % 9) + 1) . '-' . str_pad((string) ((($idx - 1) % 20) + 1), 2, '0', STR_PAD_LEFT);

			$db->pquery(
				'INSERT INTO vtiger_warehouse_stock
				 (stockid, code, product_key, productid, product_name, quantity, last_price,
				  warehouse_id, warehouse_name, storage_location, expired_date, mfg_date,
				  shrinkage_qty, createdtime, updatedtime)
				 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?,?)',
				array(
					$stockId,
					$code,
					$key,
					$productId,
					$name !== '' ? $name : ($sku !== '' ? $sku : ('PS-' . $productId)),
					$qtyPerItem,
					$price,
					$warehouseCode,
					$whName,
					$loc,
					$expiry,
					$mfg,
					$now,
					$now,
				)
			);
			$inserted++;
		}

		return array(
			'deleted' => $deleted,
			'inserted' => $inserted,
			'warehouse' => $warehouseCode,
			'qty' => $qtyPerItem,
		);
	}

	protected static function seedDemoDocuments(PearDatabase $db) {
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();

		$whCode = 'WH-001';
		if (!self::findWarehouseRowByCode($db, $whCode)) {
			return;
		}

		self::seedReceiptIfMissing($db, array(
			'code' => 'GRN-0001',
			'warehouse_id' => $whCode,
			'source_name' => 'CTY Dược Hậu Giang',
			'status' => 'stored',
			'createdtime' => '2026-05-28 08:30:00',
			'mk_meta_json' => json_encode(array(
				'poRef' => 'PO-2026-0142',
				'createdBy' => 'Thủ kho Hà',
				'timeline' => array(
					array('at' => '2026-05-28T08:30:00Z', 'by' => 'Thủ kho Hà', 'role' => 'keeper', 'action' => 'Tạo phiếu nhập'),
					array('at' => '2026-05-28T09:15:00Z', 'by' => 'QC Minh', 'role' => 'qc', 'action' => 'QC đạt'),
					array('at' => '2026-05-28T10:00:00Z', 'by' => 'QL Tuấn', 'role' => 'manager', 'action' => 'Duyệt phiếu'),
					array('at' => '2026-05-28T10:30:00Z', 'by' => 'Thủ kho Hà', 'role' => 'keeper', 'action' => 'Đã nhập kho'),
				),
			), JSON_UNESCAPED_UNICODE),
			'lines' => array(
				array('product_name' => 'Paracetamol 500mg', 'quantity' => 1000, 'serial_number' => 'LOT-2605A', 'expired_date' => '2027-05-01', 'line_note' => 'MED-001'),
				array('product_name' => 'Amoxicillin 250mg', 'quantity' => 500, 'serial_number' => 'LOT-2605B', 'expired_date' => '2027-03-15', 'line_note' => 'MED-002'),
			),
		));

		self::seedReceiptIfMissing($db, array(
			'code' => 'GRN-0002',
			'warehouse_id' => $whCode,
			'source_name' => 'Vinamilk Logistics',
			'status' => 'pending_qc',
			'createdtime' => '2026-06-01 07:20:00',
			'mk_meta_json' => json_encode(array(
				'poRef' => 'PO-2026-0151',
				'createdBy' => 'Thủ kho Hà',
				'timeline' => array(
					array('at' => '2026-06-01T07:20:00Z', 'by' => 'Thủ kho Hà', 'role' => 'keeper', 'action' => 'Tạo phiếu nhập'),
					array('at' => '2026-06-01T07:45:00Z', 'by' => 'Thủ kho Hà', 'role' => 'keeper', 'action' => 'Gửi QC kiểm tra'),
				),
			), JSON_UNESCAPED_UNICODE),
			'lines' => array(
				array('product_name' => 'Sữa tươi 1L', 'quantity' => 300, 'serial_number' => 'LOT-0106', 'expired_date' => '2026-08-30', 'line_note' => 'FMC-010'),
			),
		));

		self::seedIssueIfMissing($db, array(
			'code' => 'GIN-0001',
			'warehouse_id' => $whCode,
			'destination' => 'Bệnh viện Bạch Mai',
			'status' => 'shipped',
			'createdtime' => '2026-05-30 14:00:00',
			'mk_meta_json' => json_encode(array(
				'outboundType' => 'internal',
				'soRef' => 'SO-2026-0088',
				'createdBy' => 'Thủ kho Hà',
				'timeline' => array(
					array('at' => '2026-05-30T14:00:00Z', 'by' => 'Thủ kho Hà', 'role' => 'keeper', 'action' => 'Tạo phiếu xuất — Xuất nội bộ'),
					array('at' => '2026-05-30T15:00:00Z', 'by' => 'QL Tuấn', 'role' => 'manager', 'action' => 'Duyệt phiếu'),
					array('at' => '2026-05-30T15:30:00Z', 'by' => 'Thủ kho Hà', 'role' => 'keeper', 'action' => 'Soạn hàng & giao'),
				),
			), JSON_UNESCAPED_UNICODE),
			'lines' => array(
				array('product_name' => 'Paracetamol 500mg', 'quantity' => 200, 'serial_number' => 'LOT-2605A', 'line_note' => 'MED-001'),
			),
		));

		self::seedIssueIfMissing($db, array(
			'code' => 'GIN-0002',
			'warehouse_id' => $whCode,
			'destination' => 'Nhà thuốc Quận 1',
			'status' => 'waiting_print',
			'createdtime' => '2026-05-30 15:10:00',
			'mk_meta_json' => json_encode(array(
				'outboundType' => 'transfer',
				'soRef' => 'SO-2026-0101',
				'createdBy' => 'Thủ kho Hà',
				'timeline' => array(
					array('at' => '2026-05-30T15:10:00Z', 'by' => 'Thủ kho Hà', 'role' => 'keeper', 'action' => 'Gửi duyệt'),
				),
			), JSON_UNESCAPED_UNICODE),
			'lines' => array(
				array('product_name' => 'Amoxicillin 250mg', 'quantity' => 50, 'serial_number' => 'LOT-2604B', 'line_note' => 'MED-002'),
			),
		));
	}

	protected static function seedReceiptIfMissing(PearDatabase $db, array $spec) {
		$code = (string) $spec['code'];
		$exists = $db->pquery(
			'SELECT receiptid FROM vtiger_goodsreceipt WHERE code = ? AND deleted = 0 LIMIT 1',
			array($code)
		);
		if ($exists && $db->num_rows($exists) > 0) {
			return;
		}
		$receiptId = (int) $db->getUniqueID('vtiger_goodsreceipt');
		$now = isset($spec['createdtime']) ? $spec['createdtime'] : date('Y-m-d H:i:s');
		$db->pquery(
			'INSERT INTO vtiger_goodsreceipt
			 (receiptid, code, subject, source_name, received_date, status, warehouse_id, mk_meta_json,
			  createdtime, updatedtime, deleted)
			 VALUES (?,?,?,?,?,?,?,?,?,?,0)',
			array(
				$receiptId,
				$code,
				'Phiếu nhập ' . $code,
				$spec['source_name'],
				substr($now, 0, 10),
				$spec['status'],
				$spec['warehouse_id'],
				isset($spec['mk_meta_json']) ? $spec['mk_meta_json'] : null,
				$now,
				$now,
			)
		);
		foreach ($spec['lines'] as $line) {
			$itemId = (int) $db->getUniqueID('vtiger_goodsreceipt_items');
			$db->pquery(
				'INSERT INTO vtiger_goodsreceipt_items
				 (itemid, receiptid, productid, product_name, quantity, serial_number, expired_date, line_note)
				 VALUES (?,?,?,?,?,?,?,?)',
				array(
					$itemId,
					$receiptId,
					0,
					$line['product_name'],
					(float) $line['quantity'],
					isset($line['serial_number']) ? $line['serial_number'] : '',
					isset($line['expired_date']) ? $line['expired_date'] : null,
					isset($line['line_note']) ? $line['line_note'] : '',
				)
			);
		}
	}

	protected static function seedIssueIfMissing(PearDatabase $db, array $spec) {
		$code = (string) $spec['code'];
		$exists = $db->pquery(
			'SELECT issueid FROM vtiger_goodsissue WHERE code = ? AND deleted = 0 LIMIT 1',
			array($code)
		);
		if ($exists && $db->num_rows($exists) > 0) {
			return;
		}
		$issueId = (int) $db->getUniqueID('vtiger_goodsissue');
		$now = isset($spec['createdtime']) ? $spec['createdtime'] : date('Y-m-d H:i:s');
		$db->pquery(
			'INSERT INTO vtiger_goodsissue
			 (issueid, code, subject, destination, issued_date, status, warehouse_id, mk_meta_json,
			  createdtime, updatedtime, deleted)
			 VALUES (?,?,?,?,?,?,?,?,?,?,0)',
			array(
				$issueId,
				$code,
				'Phiếu xuất ' . $code,
				$spec['destination'],
				substr($now, 0, 10),
				$spec['status'],
				$spec['warehouse_id'],
				isset($spec['mk_meta_json']) ? $spec['mk_meta_json'] : null,
				$now,
				$now,
			)
		);
		foreach ($spec['lines'] as $line) {
			$itemId = (int) $db->getUniqueID('vtiger_goodsissue_items');
			$db->pquery(
				'INSERT INTO vtiger_goodsissue_items
				 (itemid, issueid, productid, product_name, quantity, serial_number, line_note)
				 VALUES (?,?,?,?,?,?,?)',
				array(
					$itemId,
					$issueId,
					0,
					$line['product_name'],
					(float) $line['quantity'],
					isset($line['serial_number']) ? $line['serial_number'] : '',
					isset($line['line_note']) ? $line['line_note'] : '',
				)
			);
		}
	}

	public static function getFullState() {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();

		$warehouses = self::listWarehouses($db);
		$data = array();
		foreach ($warehouses as $w) {
			$data[$w['id']] = self::getWarehouseData($db, $w['id']);
		}

		return array(
			'warehouses' => $warehouses,
			'transfers' => self::listTransfers($db),
			'data' => $data,
			'settings' => self::publicSettings(),
		);
	}

	public static function publicSettings() {
		require_once 'modules/Warehouse/helpers/SettingsHelper.php';
		return array(
			'wh_allow_negative_stock' => Warehouse_Settings_Helper::allowNegativeStock() ? 1 : 0,
			'wh_expiry_warn_days' => Warehouse_Settings_Helper::expiryWarnDays(),
		);
	}

	public static function listWarehouses(PearDatabase $db = null) {
		if ($db === null) {
			$db = PearDatabase::getInstance();
		}
		$rs = $db->pquery(
			'SELECT warehouseid, code, name, type, address, manager, status, createdtime
			 FROM vtiger_warehouse
			 WHERE deleted = 0
			 ORDER BY warehouseid ASC',
			array()
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$out[] = self::mapWarehouseRow($row);
		}
		return $out;
	}

	protected static function mapWarehouseRow(array $row) {
		$created = isset($row['createdtime']) ? $row['createdtime'] : '';
		$iso = $created !== '' ? gmdate('c', strtotime($created)) : gmdate('c');
		return array(
			'id' => (string) $row['code'],
			'code' => self::decodeDisplayTextDeep((string) $row['code']),
			'name' => self::decodeDisplayTextDeep((string) $row['name']),
			'type' => (string) $row['type'],
			'address' => self::decodeDisplayTextDeep((string) (isset($row['address']) ? $row['address'] : '')),
			'manager' => self::decodeDisplayTextDeep((string) (isset($row['manager']) ? $row['manager'] : '')),
			'status' => (string) (isset($row['status']) ? $row['status'] : 'active'),
			'createdAt' => $iso,
		);
	}

	protected static function isAutoSku($sku) {
		$sku = trim((string) $sku);
		if ($sku === '' || strcasecmp($sku, 'UNK') === 0) {
			return true;
		}
		// PS-123 catalog placeholder, invents from name (SKU-xxxxxx hex), PID- fallback
		return (bool) preg_match('/^(PS-\d+|SKU-[0-9a-f]{4,12}|PID-\d+)$/i', $sku);
	}

	protected static function strLowerUtf8($value) {
		$value = (string) $value;
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($value, 'UTF-8');
		}
		return strtolower($value);
	}

	/**
	 * Raw WH|sku|lot parse — does NOT invent SKU-md5 from product name.
	 * @return array{0:string,1:string} [sku, lot]
	 */
	protected static function skuLotFromProductKey($key) {
		$key = trim((string) $key);
		$sku = '';
		$lot = '';
		if ($key !== '' && strpos($key, '|') !== false) {
			$parts = explode('|', $key);
			$sku = isset($parts[1]) ? trim((string) $parts[1]) : '';
			$lot = isset($parts[2]) ? trim((string) $parts[2]) : '';
		}
		if (self::isAutoSku($sku)) {
			$sku = '';
		}
		return array($sku, $lot);
	}

	protected static function formatDisplaySku($sku) {
		$sku = trim((string) $sku);
		if ($sku === '' || self::isAutoSku($sku)) {
			return '';
		}
		return $sku;
	}

	public static function resolveProductSku(PearDatabase $db, $productId) {
		$productId = (int) $productId;
		if ($productId <= 0) {
			return '';
		}
		self::ensureProductSkuField();
		$rs = $db->pquery(
			'SELECT sku FROM vtiger_productsservices WHERE productsservicesid = ? LIMIT 1',
			array($productId)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return '';
		}
		return trim((string) $db->query_result($rs, 0, 'sku'));
	}

	public static function getWarehouseData(PearDatabase $db, $warehouseCode) {
		$warehouseCode = trim((string) $warehouseCode);
		return array(
			'receipts' => self::loadReceipts($db, $warehouseCode),
			'issues' => self::loadIssues($db, $warehouseCode),
			'stock' => self::loadStock($db, $warehouseCode),
			'returns' => self::loadReturns($warehouseCode),
		);
	}

	protected static function loadReturns($warehouseCode) {
		require_once 'modules/Warehouse/helpers/ReturnHelper.php';
		return Warehouse_Return_Helper::listByWarehouse($warehouseCode);
	}

	protected static function decodeMeta($json) {
		if ($json === null || $json === '') {
			return array();
		}
		$raw = trim((string) $json);
		if ($raw === '') {
			return array();
		}
		// PearDatabase::fetchByAssoc() applies to_html() by default, so JSON quotes
		// become &quot; and json_decode fails — outboundType then falls back to "internal".
		if (strpos($raw, '&') !== false) {
			$raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded) && function_exists('from_html')) {
			$decoded = json_decode(from_html((string) $json), true);
		}
		return is_array($decoded) ? $decoded : array();
	}

	protected static function decodeDisplayTextDeep($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}
		$prev = null;
		$guard = 0;
		while ($value !== $prev && $guard < 5) {
			$prev = $value;
			$value = Warehouse_Stock_Helper::decodeDisplayText($value);
			$guard += 1;
		}
		return $value;
	}

	protected static function encodeMeta(array $meta) {
		return json_encode($meta, JSON_UNESCAPED_UNICODE);
	}

	protected static function nowIso() {
		return gmdate('c');
	}

	protected static function nowSql() {
		return date('Y-m-d H:i:s');
	}

	protected static function roleDisplayName($role) {
		$r = strtolower(trim((string) $role));
		if ($r === 'qc') return 'QC Minh';
		if ($r === 'manager') return 'QL Tuấn';
		return 'QL Tuấn';
	}

	protected static function pushTimeline(array &$meta, $action, $role, $note = '') {
		if (!isset($meta['timeline']) || !is_array($meta['timeline'])) {
			$meta['timeline'] = array();
		}
		$entry = array(
			'at' => self::nowIso(),
			'by' => self::roleDisplayName($role),
			'role' => strtolower(trim((string) $role)) !== '' ? strtolower(trim((string) $role)) : 'manager',
			'action' => (string) $action,
		);
		if (trim((string) $note) !== '') {
			$entry['note'] = trim((string) $note);
		}
		$meta['timeline'][] = $entry;
	}

	protected static function formatReceiptLocationNote(array $items) {
		$locations = array();
		foreach ($items as $item) {
			$location = trim((string) (isset($item['storage_location']) ? $item['storage_location'] : ''));
			if ($location !== '' && !in_array($location, $locations, true)) {
				$locations[] = $location;
			}
		}
		if (empty($locations)) {
			return '';
		}
		return 'Vị trí: ' . implode(', ', $locations);
	}

	protected static function enrichQcTimelineFromMeta(array &$meta) {
		if (!isset($meta['qc']) || !is_array($meta['qc'])) {
			return;
		}
		$qc = $meta['qc'];
		$res = isset($qc['result']) ? strtolower(trim((string) $qc['result'])) : '';
		if ($res !== 'pass' && $res !== 'fail') {
			return;
		}
		$note = trim((string) (isset($qc['note']) ? $qc['note'] : ''));
		$action = $res === 'fail' ? 'QC không đạt' : 'QC đạt';
		if (!isset($meta['timeline']) || !is_array($meta['timeline'])) {
			$meta['timeline'] = array();
		}
		$hasQc = false;
		foreach ($meta['timeline'] as &$ev) {
			if (!is_array($ev) || (isset($ev['role']) ? $ev['role'] : '') !== 'qc') {
				continue;
			}
			$hasQc = true;
			if ($note !== '' && trim((string) (isset($ev['note']) ? $ev['note'] : '')) === '') {
				$ev['note'] = $note;
			}
			if (isset($qc['images']) && is_array($qc['images']) && empty($ev['images'])) {
				$ev['images'] = self::normalizeQcImagesList($qc['images']);
			}
			if (trim((string) (isset($ev['action']) ? $ev['action'] : '')) === '') {
				$ev['action'] = $action;
			}
		}
		unset($ev);
		if ($hasQc) {
			return;
		}
		$at = isset($qc['at']) ? trim((string) $qc['at']) : '';
		if ($at === '') {
			$at = self::nowIso();
		}
		$meta['timeline'][] = array(
			'at' => $at,
			'by' => isset($qc['by']) && trim((string) $qc['by']) !== '' ? (string) $qc['by'] : 'QC',
			'role' => 'qc',
			'action' => $action,
			'note' => $note,
		);
		if (isset($qc['images']) && is_array($qc['images']) && count($qc['images']) > 0) {
			self::syncQcImagesToLatestTimeline($meta);
		}
	}

	protected static function syncQcMetaFromStorage(array &$meta, $status, $goodsReceiptNote = '') {
		$status = strtolower(trim((string) $status));
		if ($status !== 'qc_passed' && $status !== 'qc_failed') {
			return;
		}
		$grNote = trim((string) $goodsReceiptNote);
		if (!isset($meta['qc']) || !is_array($meta['qc'])) {
			$meta['qc'] = array();
		}
		if (empty($meta['qc']['result'])) {
			$meta['qc']['result'] = $status === 'qc_failed' ? 'fail' : 'pass';
		}
		if ($grNote !== '' && trim((string) (isset($meta['qc']['note']) ? $meta['qc']['note'] : '')) === '') {
			$meta['qc']['note'] = $grNote;
		}
		if (trim((string) (isset($meta['qc']['note']) ? $meta['qc']['note'] : '')) === '' && isset($meta['timeline']) && is_array($meta['timeline'])) {
			foreach (array_reverse($meta['timeline']) as $ev) {
				if (!is_array($ev)) {
					continue;
				}
				$role = isset($ev['role']) ? strtolower(trim((string) $ev['role'])) : '';
				$action = isset($ev['action']) ? (string) $ev['action'] : '';
				if ($role !== 'qc' && stripos($action, 'QC') !== 0) {
					continue;
				}
				$evNote = trim((string) (isset($ev['note']) ? $ev['note'] : ''));
				if ($evNote !== '') {
					$meta['qc']['note'] = $evNote;
				}
				if (empty($meta['qc']['by']) && !empty($ev['by'])) {
					$meta['qc']['by'] = (string) $ev['by'];
				}
				if (empty($meta['qc']['at']) && !empty($ev['at'])) {
					$meta['qc']['at'] = (string) $ev['at'];
				}
				break;
			}
		}
		if (empty($meta['qc']['at'])) {
			$meta['qc']['at'] = self::nowIso();
		}
		if (empty($meta['qc']['by'])) {
			$meta['qc']['by'] = 'QC';
		}
	}

	protected static function normalizeReceiptTimeline(array &$meta, $createdTimeSql, $status) {
		$status = strtolower(trim((string) $status));
		$createdIso = $createdTimeSql !== '' ? gmdate('c', strtotime($createdTimeSql)) : self::nowIso();

		if (!isset($meta['timeline']) || !is_array($meta['timeline'])) {
			$meta['timeline'] = array();
		}
		if (count($meta['timeline']) === 0) {
			$createdBy = isset($meta['createdBy']) ? trim((string) $meta['createdBy']) : '';
			$meta['timeline'][] = array(
				'at' => $createdIso,
				'by' => $createdBy !== '' ? $createdBy : 'Hệ thống',
				'role' => 'keeper',
				'action' => 'Tạo phiếu nhập',
			);

			if ($status === 'pending_qc') {
				$meta['timeline'][] = array('at' => $createdIso, 'by' => $createdBy !== '' ? $createdBy : 'Thủ kho', 'role' => 'keeper', 'action' => 'Gửi QC kiểm tra');
			}

			if ($status === 'approved' || $status === 'stored') {
				$meta['timeline'][] = array('at' => self::nowIso(), 'by' => 'QL', 'role' => 'manager', 'action' => 'Duyệt phiếu');
			}
			if ($status === 'stored') {
				$meta['timeline'][] = array('at' => self::nowIso(), 'by' => 'Thủ kho', 'role' => 'keeper', 'action' => 'Đã nhập kho');
			}
		}

		self::enrichQcTimelineFromMeta($meta);
	}

	protected static function findReceiptRowByCode(PearDatabase $db, $code, $whId) {
		$rs = $db->pquery(
			'SELECT receiptid, status, mk_meta_json FROM vtiger_goodsreceipt WHERE deleted = 0 AND code = ? AND warehouse_id = ? LIMIT 1',
			array((string) $code, (string) $whId)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return null;
		}
		return $db->fetchByAssoc($rs);
	}

	protected static function findIssueRowByCode(PearDatabase $db, $code, $whId) {
		$rs = $db->pquery(
			'SELECT issueid, status, mk_meta_json FROM vtiger_goodsissue WHERE deleted = 0 AND code = ? AND warehouse_id = ? LIMIT 1',
			array((string) $code, (string) $whId)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return null;
		}
		return $db->fetchByAssoc($rs);
	}

	protected static function loadReceiptItemsRaw(PearDatabase $db, $receiptId) {
		$rs = $db->pquery(
			'SELECT itemid, productid, product_name, quantity, serial_number, expired_date, mfg_date, line_note, storage_location
			 FROM vtiger_goodsreceipt_items
			 WHERE receiptid = ?
			 ORDER BY itemid ASC',
			array((int) $receiptId)
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$out[] = $row;
		}
		return $out;
	}

	protected static function loadIssueItemsRaw(PearDatabase $db, $issueId) {
		$rs = $db->pquery(
			'SELECT productid, product_name, quantity, serial_number, line_note
			 FROM vtiger_goodsissue_items
			 WHERE issueid = ?
			 ORDER BY itemid ASC',
			array((int) $issueId)
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$out[] = $row;
		}
		return $out;
	}

	protected static function loadReceipts(PearDatabase $db, $warehouseCode) {
		$rs = $db->pquery(
			'SELECT receiptid, code, source_name, status, createdtime, mk_meta_json, note
			 FROM vtiger_goodsreceipt
			 WHERE deleted = 0 AND warehouse_id = ?
			 ORDER BY createdtime DESC, receiptid DESC',
			array($warehouseCode)
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');
			$created = isset($row['createdtime']) ? (string) $row['createdtime'] : '';
			$dbStatus = (string) (isset($row['status']) ? $row['status'] : 'stored');
			self::syncQcMetaFromStorage($meta, $dbStatus, isset($row['note']) ? $row['note'] : '');
			self::normalizeReceiptTimeline($meta, $created, $dbStatus);
			self::hydrateQcImageUrls($meta, $warehouseCode, (string) $row['code']);
			$items = self::loadReceiptItems($db, (int) $row['receiptid'], $meta);
			$out[] = array(
				'id' => (string) $row['code'],
				'supplier' => (string) (isset($row['source_name']) ? $row['source_name'] : ''),
				'poRef' => (string) (isset($meta['poRef']) ? $meta['poRef'] : ''),
				'createdAt' => $created !== '' ? gmdate('c', strtotime($created)) : gmdate('c'),
				'createdBy' => (string) (isset($meta['createdBy']) ? $meta['createdBy'] : ''),
				'status' => $dbStatus !== '' ? $dbStatus : 'stored',
				'lines' => $items,
				'timeline' => isset($meta['timeline']) && is_array($meta['timeline']) ? $meta['timeline'] : array(),
				'qc' => isset($meta['qc']) && is_array($meta['qc']) ? $meta['qc'] : array(),
			);
		}
		return $out;
	}

	protected static function loadReceiptItems(PearDatabase $db, $receiptId, array $meta = array()) {
		$hasUnitPrice = false;
		$hasProductType = false;
		try {
			$colRs = $db->getColumnNames('vtiger_goodsreceipt_items');
			if (is_array($colRs)) {
				foreach ($colRs as $c) {
					$lc = strtolower((string) $c);
					if ($lc === 'unit_price') {
						$hasUnitPrice = true;
					}
					if ($lc === 'product_type') {
						$hasProductType = true;
					}
				}
			}
		} catch (Exception $e) {
			// ignore
		}
		$cols = 'productid, product_name, quantity, serial_number, expired_date, mfg_date, line_note, storage_location';
		if ($hasUnitPrice) {
			$cols .= ', unit_price';
		}
		if ($hasProductType) {
			$cols .= ', product_type';
		}
		$rs = $db->pquery(
			'SELECT ' . $cols . '
			 FROM vtiger_goodsreceipt_items
			 WHERE receiptid = ?
			 ORDER BY itemid ASC',
			array($receiptId)
		);
		$qc = isset($meta['qc']) && is_array($meta['qc']) ? $meta['qc'] : array();
		$qcResult = isset($qc['result']) ? (string) $qc['result'] : '';
		$qcResult = $qcResult === 'pass' || $qcResult === 'fail' ? $qcResult : '';
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$sku = trim((string) (isset($row['line_note']) ? $row['line_note'] : ''));
			$sku = self::formatDisplaySku($sku !== '' ? $sku : self::guessSkuFromName($row['product_name']));
			$name = self::decodeDisplayTextDeep((string) $row['product_name']);
			$productId = (int) (isset($row['productid']) ? $row['productid'] : 0);
			$line = array(
				'sku' => $sku,
				'name' => $name,
				'lot' => (string) (isset($row['serial_number']) ? $row['serial_number'] : ''),
				'mfg' => self::normalizeDateValue(isset($row['mfg_date']) ? $row['mfg_date'] : ''),
				'expiry' => self::normalizeDateValue(isset($row['expired_date']) ? $row['expired_date'] : ''),
				'qty' => (float) $row['quantity'],
				'unit_price' => $hasUnitPrice ? (float) (isset($row['unit_price']) ? $row['unit_price'] : 0) : 0,
				'unit' => self::lookupProductUnit($db, $productId, $sku, $name),
				'location' => self::decodeDisplayTextDeep((string) (isset($row['storage_location']) ? $row['storage_location'] : '')),
			);
			if ($qcResult !== '') {
				$line['qcResult'] = $qcResult;
				$line['passedQty'] = $qcResult === 'pass' ? (float) $row['quantity'] : 0;
			}
			$out[] = $line;
		}
		return $out;
	}

	public static function applyReceiptAction($warehouseCode, $receiptCode, $actionKey, $role, $note = '', $userId = 0, $targetStatus = '') {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$warehouseCode = trim((string) $warehouseCode);
		$receiptCode = trim((string) $receiptCode);
		$actionKey = trim((string) $actionKey);
		if ($warehouseCode === '' || $receiptCode === '' || $actionKey === '') {
			throw new Exception('Thiếu thông tin thao tác phiếu nhập.');
		}

		$wh = self::findWarehouseRowByCode($db, $warehouseCode);
		if (!$wh) {
			throw new Exception('Không tìm thấy kho.');
		}
		$whName = (string) $wh['name'];

		$row = self::findReceiptRowByCode($db, $receiptCode, $warehouseCode);
		if (!$row) {
			throw new Exception('Không tìm thấy phiếu nhập.');
		}
		$receiptId = (int) $row['receiptid'];
		$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');

		$status = isset($row['status']) ? (string) $row['status'] : '';
		$newStatus = $status;
		$goodsReceiptNote = null;

		if ($actionKey === 'send-qc') {
			$newStatus = 'pending_qc';
			self::pushTimeline($meta, 'Gửi QC kiểm tra', $role, '');
		} else if ($actionKey === 'qc-pass') {
			$newStatus = 'qc_passed';
			$note = trim((string) $note);
			self::ensureQcMetaBucket($meta);
			$existingImages = self::normalizeQcImagesList(isset($meta['qc']['images']) ? $meta['qc']['images'] : array());
			$meta['qc'] = array(
				'result' => 'pass',
				'note' => $note,
				'at' => self::nowIso(),
				'by' => self::roleDisplayName($role),
				'images' => $existingImages,
			);
			self::pushTimeline($meta, 'QC đạt', 'qc', $note);
			self::syncQcImagesToLatestTimeline($meta);
			$goodsReceiptNote = $note;
		} else if ($actionKey === 'qc-fail') {
			$newStatus = 'qc_failed';
			$note = trim((string) $note);
			self::ensureQcMetaBucket($meta);
			$existingImages = self::normalizeQcImagesList(isset($meta['qc']['images']) ? $meta['qc']['images'] : array());
			$meta['qc'] = array(
				'result' => 'fail',
				'note' => $note,
				'at' => self::nowIso(),
				'by' => self::roleDisplayName($role),
				'images' => $existingImages,
			);
			self::pushTimeline($meta, 'QC không đạt', 'qc', $note);
			self::syncQcImagesToLatestTimeline($meta);
			$goodsReceiptNote = $note;
		} else if ($actionKey === 'mgr-approve') {
			$newStatus = 'approved';
			self::pushTimeline($meta, 'Duyệt phiếu', $role, '');
		} else if ($actionKey === 'store') {
			$newStatus = 'stored';
			$items = self::loadReceiptItemsRaw($db, $receiptId);
			$stockedItemIds = array();
			if (isset($meta['stockedItemIds']) && is_array($meta['stockedItemIds'])) {
				foreach ($meta['stockedItemIds'] as $sid => $flag) {
					if ($flag) {
						$stockedItemIds[(string) $sid] = 1;
					}
				}
			}
			// Legacy receipts (no stockedItemIds): if previously full-store or create-without-qc flags.
			$hasStockedSnapshot = isset($meta['stockedItemIds']) && is_array($meta['stockedItemIds']);
			$legacyAlreadyStored = !empty($meta['stockStored']);

			foreach ($items as $it) {
				$itemId = (string) (isset($it['itemid']) ? $it['itemid'] : '');
				if ($itemId !== '' && isset($stockedItemIds[$itemId])) {
					continue; // already stocked at create (non-QC lines)
				}
				// Legacy: if whole receipt was already marked stockStored without per-line map, skip all.
				if (!$hasStockedSnapshot && $legacyAlreadyStored) {
					continue;
				}
				// If no meta snapshot and not marked stocked yet, apply all lines (old pure-QC path).
				$name = (string) (isset($it['product_name']) ? $it['product_name'] : '');
				$lot = (string) (isset($it['serial_number']) ? $it['serial_number'] : '');
				$sku = (string) (isset($it['line_note']) ? $it['line_note'] : '');
				$productId = (int) (isset($it['productid']) ? $it['productid'] : 0);
				$qty = (float) (isset($it['quantity']) ? $it['quantity'] : 0);
				$expiry = isset($it['expired_date']) && $it['expired_date'] !== '' ? $it['expired_date'] : null;
				$mfg = isset($it['mfg_date']) && $it['mfg_date'] !== '' ? $it['mfg_date'] : null;
				$location = trim((string) (isset($it['storage_location']) ? $it['storage_location'] : ''));
				if ($name === '' || $lot === '' || $qty <= 0) {
					continue;
				}
				if ($sku === '' && $productId > 0) {
					$sku = self::resolveProductSku($db, $productId);
				}
				$price = 0;
				if ($productId > 0) {
					$prod = self::findProductById($db, $productId);
					if ($prod && isset($prod['price'])) {
						$price = (float) $prod['price'];
					}
				}
				self::applyInboundStockLine($db, $warehouseCode, $whName, array(
					'product_id' => $productId,
					'sku' => $sku,
					'name' => $name,
					'lot' => $lot,
					'qty' => $qty,
					'mfg' => $mfg,
					'expiry' => $expiry,
					'price' => $price,
					'location' => $location,
				), $userId);
				if ($itemId !== '') {
					$stockedItemIds[$itemId] = 1;
				}
			}
			$locationNote = self::formatReceiptLocationNote($items);
			$meta['stockedItemIds'] = $stockedItemIds;
			$meta['stockStored'] = true;
			self::pushTimeline($meta, 'Đã nhập kho', $role, $locationNote);
		} else if ($actionKey === 'receipt-revert') {
			$newStatus = self::revertReceiptStatus(
				$db,
				$warehouseCode,
				$whName,
				$receiptId,
				$status,
				$targetStatus,
				$role,
				$userId,
				$meta
			);
		} else {
			throw new Exception('Unsupported receipt action: ' . $actionKey);
		}

		$now = self::nowSql();
		if ($goodsReceiptNote !== null) {
			$db->pquery(
				'UPDATE vtiger_goodsreceipt SET status = ?, mk_meta_json = ?, note = ?, updatedby = ?, updatedtime = ? WHERE receiptid = ?',
				array($newStatus, self::encodeMeta($meta), (string) $goodsReceiptNote, (int) $userId, $now, $receiptId)
			);
		} else {
			$db->pquery(
				'UPDATE vtiger_goodsreceipt SET status = ?, mk_meta_json = ?, updatedby = ?, updatedtime = ? WHERE receiptid = ?',
				array($newStatus, self::encodeMeta($meta), (int) $userId, $now, $receiptId)
			);
		}

		return array(
			'code' => $receiptCode,
			'warehouse' => $warehouseCode,
			'data' => self::getWarehouseData($db, $warehouseCode),
		);
	}

	protected static function deductStockForIssue(PearDatabase $db, $warehouseCode, $issueId, $userId, array &$meta) {
		if (!empty($meta['stockDeducted'])) {
			return;
		}
		$items = self::loadIssueItemsRaw($db, $issueId);
		$now = self::nowSql();
		foreach ($items as $it) {
			$qtyNeeded = (float) (isset($it['quantity']) ? $it['quantity'] : 0);
			if ($qtyNeeded <= 0) {
				continue;
			}
			$name = self::decodeDisplayTextDeep(isset($it['product_name']) ? $it['product_name'] : '');
			$lot = self::decodeDisplayTextDeep(isset($it['serial_number']) ? $it['serial_number'] : '');
			$sku = self::decodeDisplayTextDeep(isset($it['line_note']) ? $it['line_note'] : '');
			$productId = (int) (isset($it['productid']) ? $it['productid'] : 0);
			if ($sku === '' && $productId > 0) {
				$sku = self::resolveProductSku($db, $productId);
			}
			self::deductQtyFromWarehouseStock(
				$db,
				$warehouseCode,
				$qtyNeeded,
				$sku,
				$lot,
				$name,
				$productId,
				$userId,
				$now,
				true
			);
		}
		$meta['stockDeducted'] = true;
		$meta['stockDeductedAt'] = gmdate('c');
	}

	/**
	 * Public: deduct stock for a goods issue (idempotent via mk_meta_json.stockDeducted).
	 * Used when SO confirms → GI waiting_print (Chờ soạn).
	 *
	 * @param int $issueId
	 * @param string $warehouseCode
	 * @param int $userId
	 * @return bool true when deduction ran (or already deducted)
	 */
	public static function deductStockForGoodsIssue($issueId, $warehouseCode, $userId = 0) {
		$issueId = (int) $issueId;
		$warehouseCode = trim((string) $warehouseCode);
		if ($issueId <= 0 || $warehouseCode === '') {
			return false;
		}
		self::ensureInstalled();
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT mk_meta_json FROM vtiger_goodsissue WHERE issueid = ? AND deleted = 0 LIMIT 1',
			array($issueId)
		);
		if (!$rs || $db->num_rows($rs) <= 0) {
			return false;
		}
		$meta = self::decodeMeta($db->query_result($rs, 0, 'mk_meta_json'));
		if (empty($userId)) {
			$user = Users_Record_Model::getCurrentUserModel();
			$userId = $user ? (int) $user->getId() : 0;
		}
		self::deductStockForIssue($db, $warehouseCode, $issueId, (int) $userId, $meta);
		$db->pquery(
			'UPDATE vtiger_goodsissue SET mk_meta_json = ?, updatedby = ?, updatedtime = ? WHERE issueid = ?',
			array(json_encode($meta, JSON_UNESCAPED_UNICODE), (int) $userId, self::nowSql(), $issueId)
		);
		return true;
	}

	/**
	 * Public cancel helper for SalesOrder / external callers.
	 *
	 * @param string $warehouseCode
	 * @param string $issueCode
	 * @param int $userId
	 * @param string $note
	 * @return array
	 */
	public static function cancelIssueByCode($warehouseCode, $issueCode, $userId = 0, $note = '') {
		if (empty($userId)) {
			$user = Users_Record_Model::getCurrentUserModel();
			$userId = $user ? (int) $user->getId() : 0;
		}
		$role = 'manager';
		try {
			$userModel = Users_Record_Model::getCurrentUserModel();
			if ($userModel) {
				$role = 'manager';
			}
		} catch (Exception $e) {
			/* keep default */
		}
		return self::applyIssueAction(
			$warehouseCode,
			$issueCode,
			'issue-cancel',
			$role,
			$note !== '' ? $note : 'Huỷ đơn từ Sales Order',
			(int) $userId
		);
	}

	/**
	 * @param bool $doUpdate when false, only simulate availability (no DB writes)
	 * @return float remaining unmet qty
	 */
	protected static function deductQtyFromWarehouseStock(
		PearDatabase $db,
		$warehouseCode,
		$qtyNeeded,
		$sku,
		$lot,
		$name,
		$productId,
		$userId,
		$now,
		$doUpdate
	) {
		require_once 'modules/Warehouse/helpers/SettingsHelper.php';
		$allowNegative = Warehouse_Settings_Helper::allowNegativeStock();

		$qtyNeeded = (float) $qtyNeeded;
		$sku = trim((string) $sku);
		$lot = trim((string) $lot);
		$name = trim((string) $name);
		$productId = (int) $productId;
		if (self::isAutoSku($sku)) {
			$sku = '';
		}
		if ($productId <= 0 && $name !== '') {
			$productId = self::resolveProductIdByName($db, $name);
		}
		if ($sku === '' && $productId > 0) {
			$sku = self::resolveProductSku($db, $productId);
			if (self::isAutoSku($sku)) {
				$sku = '';
			}
		}
		// Normalize display names like "Áo (Cái)" so FEFO match works.
		$nameCore = self::normalizeStockProductName($name);

		$virtualQty = array();

		$takeFromRow = function ($stockId, $current, $forceFull = false) use (
			&$qtyNeeded,
			&$virtualQty,
			$doUpdate,
			$db,
			$userId,
			$now,
			$allowNegative
		) {
			$stockId = (int) $stockId;
			if (!$doUpdate) {
				if (!array_key_exists($stockId, $virtualQty)) {
					$virtualQty[$stockId] = (float) $current;
				}
				$current = $virtualQty[$stockId];
			}
			if ($allowNegative && $forceFull) {
				$deduct = (float) $qtyNeeded;
			} else {
				$deduct = min((float) $current, $qtyNeeded);
				if ($deduct <= 0 && !$allowNegative) {
					return;
				}
				if ($deduct <= 0 && $allowNegative) {
					$deduct = (float) $qtyNeeded;
				}
			}
			if ($deduct <= 0) {
				return;
			}
			$newQty = (float) $current - $deduct;
			if (!$allowNegative) {
				$newQty = max(0, $newQty);
			}
			if ($doUpdate) {
				$db->pquery(
					'UPDATE vtiger_warehouse_stock SET quantity = ?, updatedby = ?, updatedtime = ? WHERE stockid = ?',
					array($newQty, (int) $userId, $now, $stockId)
				);
			} else {
				$virtualQty[$stockId] = $newQty;
			}
			$qtyNeeded -= $deduct;
		};

		$scanTake = function ($sql, $params, $forceFullAll = false) use (
			$db,
			&$qtyNeeded,
			$takeFromRow,
			$allowNegative,
			$lot
		) {
			if ($qtyNeeded <= 0.00000001) {
				return;
			}
			$rs = $db->pquery($sql, $params);
			while ($rs && ($row = $db->fetchByAssoc($rs))) {
				if ($qtyNeeded <= 0.00000001) {
					break;
				}
				// Optional lot filter when caller passed a concrete lot.
				if ($lot !== '' && $lot !== '—') {
					$parsed = self::parseStockIdentity($db, $row);
					$rowLot = (string) (isset($parsed['lot']) ? $parsed['lot'] : '');
					if ($rowLot !== '' && strcasecmp($rowLot, $lot) !== 0) {
						continue;
					}
				}
				// Prefer depleting positive rows fully first; force remaining only on last pass.
				$force = $forceFullAll && $allowNegative;
				$takeFromRow((int) $row['stockid'], (float) $row['quantity'], $force);
			}
		};

		// 1) productid — real catalog lines (highest priority)
		if ($productId > 0) {
			$scanTake(
				'SELECT stockid, quantity, product_key FROM vtiger_warehouse_stock
				 WHERE warehouse_id = ? AND productid = ?
				 ORDER BY (quantity > 0) DESC, expired_date ASC, stockid ASC',
				array($warehouseCode, $productId),
				false
			);
			// Remaining under allow-negative: go fully negative on best productid row.
			if ($qtyNeeded > 0.00000001 && $allowNegative) {
				$scanTake(
					'SELECT stockid, quantity, product_key FROM vtiger_warehouse_stock
					 WHERE warehouse_id = ? AND productid = ?
					 ORDER BY (quantity < 0) ASC, stockid ASC
					 LIMIT 1',
					array($warehouseCode, $productId),
					true
				);
			}
		}

		// 2) SKU — all lots (do not require lot to match first key)
		if ($qtyNeeded > 0.00000001 && $sku !== '') {
			// Exact key first when lot known
			if ($lot !== '' && $lot !== '—') {
				$key = self::stockProductKey($warehouseCode, $sku, $lot);
				$rs = $db->pquery(
					'SELECT stockid, quantity FROM vtiger_warehouse_stock WHERE product_key = ? LIMIT 1',
					array($key)
				);
				if ($rs && $db->num_rows($rs) > 0) {
					$takeFromRow(
						(int) $db->query_result($rs, 0, 'stockid'),
						(float) $db->query_result($rs, 0, 'quantity'),
						$allowNegative
					);
				}
			}
			if ($qtyNeeded > 0.00000001) {
				$scanTake(
					'SELECT stockid, quantity, product_key FROM vtiger_warehouse_stock
					 WHERE warehouse_id = ? AND (
						product_key LIKE ? OR product_key LIKE ? OR product_key = ?
					 )
					 ORDER BY (quantity > 0) DESC, expired_date ASC, stockid ASC',
					array(
						$warehouseCode,
						'%|' . $sku . '|%',
						$warehouseCode . '|' . $sku . '|%',
						self::stockProductKey($warehouseCode, $sku, $lot !== '' ? $lot : '—'),
					),
					false
				);
			}
			// Parse sku from product_key columns when LIKE unreliable (never invent SKU-md5)
			if ($qtyNeeded > 0.00000001) {
				$rs = $db->pquery(
					'SELECT stockid, quantity, product_key FROM vtiger_warehouse_stock
					 WHERE warehouse_id = ?
					 ORDER BY (quantity > 0) DESC, expired_date ASC, stockid ASC',
					array($warehouseCode)
				);
				while ($rs && ($row = $db->fetchByAssoc($rs))) {
					if ($qtyNeeded <= 0.00000001) {
						break;
					}
					list($rowSku) = self::skuLotFromProductKey(isset($row['product_key']) ? $row['product_key'] : '');
					if ($rowSku === '' || strcasecmp($rowSku, $sku) !== 0) {
						continue;
					}
					$takeFromRow((int) $row['stockid'], (float) $row['quantity'], false);
				}
			}
			if ($qtyNeeded > 0.00000001 && $allowNegative) {
				// Push remainder into first SKU-matching row (or create key with real SKU).
				$rs = $db->pquery(
					'SELECT stockid, quantity, product_key FROM vtiger_warehouse_stock WHERE warehouse_id = ?',
					array($warehouseCode)
				);
				$hit = null;
				while ($rs && ($row = $db->fetchByAssoc($rs))) {
					list($rowSku) = self::skuLotFromProductKey(isset($row['product_key']) ? $row['product_key'] : '');
					if ($rowSku !== '' && strcasecmp($rowSku, $sku) === 0) {
						$hit = $row;
						break;
					}
				}
				if ($hit) {
					$takeFromRow((int) $hit['stockid'], (float) $hit['quantity'], true);
				}
			}
		}

		// 3) Name match (normalized / fuzzy — prevents ghost SKU-md5 rows)
		if ($qtyNeeded > 0.00000001 && $nameCore !== '') {
			$rs = $db->pquery(
				'SELECT stockid, quantity, product_key, productid, product_name FROM vtiger_warehouse_stock
				 WHERE warehouse_id = ?
				 ORDER BY (quantity > 0) DESC, expired_date ASC, stockid ASC',
				array($warehouseCode)
			);
			while ($rs && ($row = $db->fetchByAssoc($rs))) {
				if ($qtyNeeded <= 0.00000001) {
					break;
				}
				$rowName = self::decodeDisplayTextDeep(isset($row['product_name']) ? $row['product_name'] : '');
				if (!self::stockNamesMatch($nameCore, $rowName)) {
					continue;
				}
				$takeFromRow((int) $row['stockid'], (float) $row['quantity'], false);
			}
			if ($qtyNeeded > 0.00000001 && $allowNegative) {
				// Re-scan for force full on same name
				$rs = $db->pquery(
					'SELECT stockid, quantity, product_name FROM vtiger_warehouse_stock WHERE warehouse_id = ?',
					array($warehouseCode)
				);
				while ($rs && ($row = $db->fetchByAssoc($rs))) {
					$rowName = self::decodeDisplayTextDeep(isset($row['product_name']) ? $row['product_name'] : '');
					if (self::stockNamesMatch($nameCore, $rowName)) {
						$takeFromRow((int) $row['stockid'], (float) $row['quantity'], true);
						break;
					}
				}
			}
		}

		// 4) Last resort: create/update negative on REAL sku — never invent SKU-md5 when productid/sku known
		if ($doUpdate && $allowNegative && $qtyNeeded > 0.00000001) {
			self::createNegativeStockRow($db, $warehouseCode, $sku, $lot, $name, $productId, $qtyNeeded, $userId, $now);
			$qtyNeeded = 0;
		}

		if ($doUpdate && !$allowNegative && $qtyNeeded > 0.00000001) {
			$label = $name !== '' ? $name : ($sku !== '' ? $sku : 'hàng');
			if ($lot !== '') {
				$label .= ' · Lô ' . $lot;
			}
			throw new Exception('Không đủ tồn kho để xuất: ' . $label . ' (thiếu ' . rtrim(rtrim(number_format($qtyNeeded, 3, '.', ''), '0'), '.') . ').');
		}
		return max(0, $qtyNeeded);
	}

	/**
	 * Strip unit suffixes / noise so "Áo Thun (Cái)" matches stock "Áo Thun (Cái)".
	 */
	protected static function normalizeStockProductName($name) {
		$name = self::decodeDisplayTextDeep($name);
		$name = preg_replace('/\s+/u', ' ', trim((string) $name));
		// Drop trailing unit markers: (Cái), (Bao), ...
		$name = preg_replace('/\s*\([^)]{0,40}\)\s*$/u', '', $name);
		$name = preg_replace('/\s+/u', ' ', trim((string) $name));
		return $name;
	}

	/**
	 * Flexible product name equality for stock lines vs issue lines.
	 */
	protected static function stockNamesMatch($a, $b) {
		$a = self::normalizeStockProductName($a);
		$b = self::normalizeStockProductName($b);
		if ($a === '' || $b === '') {
			return false;
		}
		$la = self::strLowerUtf8($a);
		$lb = self::strLowerUtf8($b);
		if ($la === $lb) {
			return true;
		}
		// Containment when both reasonably long (e.g. line omit "Tuibao size XL")
		$lenA = function_exists('mb_strlen') ? mb_strlen($la, 'UTF-8') : strlen($la);
		$lenB = function_exists('mb_strlen') ? mb_strlen($lb, 'UTF-8') : strlen($lb);
		if ($lenA >= 8 && $lenB >= 8) {
			if (strpos($la, $lb) !== false || strpos($lb, $la) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Resolve ProductsServices id by name when outbound line has missing/wrong productid.
	 */
	protected static function resolveProductIdByName(PearDatabase $db, $name) {
		$name = self::normalizeStockProductName($name);
		if ($name === '') {
			return 0;
		}
		try {
			$rs = $db->pquery(
				'SELECT productsservicesid FROM vtiger_productsservices
				 WHERE productsservicesname = ? OR productsservicesname LIKE ?
				 ORDER BY (productsservicesname = ?) DESC, productsservicesid ASC
				 LIMIT 2',
				array($name, $name . ' (%', $name)
			);
			if ($rs && $db->num_rows($rs) === 1) {
				return (int) $db->query_result($rs, 0, 'productsservicesid');
			}
			if ($rs && $db->num_rows($rs) > 1) {
				// Prefer exact
				return (int) $db->query_result($rs, 0, 'productsservicesid');
			}
			$like = '%' . $name . '%';
			$rs2 = $db->pquery(
				'SELECT productsservicesid FROM vtiger_productsservices
				 WHERE productsservicesname LIKE ?
				 ORDER BY CHAR_LENGTH(productsservicesname) ASC
				 LIMIT 2',
				array($like)
			);
			if ($rs2 && $db->num_rows($rs2) === 1) {
				return (int) $db->query_result($rs2, 0, 'productsservicesid');
			}
		} catch (Exception $e) {
			return 0;
		}
		return 0;
	}

	/**
	 * Create / update a stock identity to hold a negative quantity (allow-negative mode).
	 * Never invent SKU-md5 when we know productid/catalog SKU — merge into existing rows first.
	 */
	protected static function createNegativeStockRow(
		PearDatabase $db,
		$warehouseCode,
		$sku,
		$lot,
		$name,
		$productId,
		$qtyNeeded,
		$userId,
		$now
	) {
		$sku = trim((string) $sku);
		$lot = trim((string) $lot);
		$name = trim((string) $name);
		$productId = (int) $productId;
		$qtyNeeded = (float) $qtyNeeded;
		if ($lot === '') {
			$lot = '—';
		}
		if (self::isAutoSku($sku)) {
			$sku = '';
		}
		if ($productId <= 0 && $name !== '') {
			$productId = self::resolveProductIdByName($db, $name);
		}
		if ($sku === '' && $productId > 0) {
			$sku = self::resolveProductSku($db, $productId);
			if (self::isAutoSku($sku)) {
				$sku = '';
			}
		}

		// A) Merge into any existing productid row
		if ($productId > 0) {
			$rs = $db->pquery(
				'SELECT stockid, quantity FROM vtiger_warehouse_stock
				 WHERE warehouse_id = ? AND productid = ?
				 ORDER BY (quantity > 0) DESC, stockid ASC LIMIT 1',
				array($warehouseCode, $productId)
			);
			if ($rs && $db->num_rows($rs) > 0) {
				$stockId = (int) $db->query_result($rs, 0, 'stockid');
				$current = (float) $db->query_result($rs, 0, 'quantity');
				$db->pquery(
					'UPDATE vtiger_warehouse_stock SET quantity = ?, updatedby = ?, updatedtime = ? WHERE stockid = ?',
					array($current - $qtyNeeded, (int) $userId, $now, $stockId)
				);
				return;
			}
		}

		// B) Merge into any row with matching SKU (raw product_key only)
		if ($sku !== '') {
			$rs = $db->pquery(
				'SELECT stockid, quantity, product_key FROM vtiger_warehouse_stock WHERE warehouse_id = ?',
				array($warehouseCode)
			);
			while ($rs && ($row = $db->fetchByAssoc($rs))) {
				list($rowSku) = self::skuLotFromProductKey(isset($row['product_key']) ? $row['product_key'] : '');
				if ($rowSku !== '' && strcasecmp($rowSku, $sku) === 0) {
					$stockId = (int) $row['stockid'];
					$current = (float) $row['quantity'];
					$db->pquery(
						'UPDATE vtiger_warehouse_stock SET quantity = ?, updatedby = ?, updatedtime = ? WHERE stockid = ?',
						array($current - $qtyNeeded, (int) $userId, $now, $stockId)
					);
					return;
				}
			}
		}

		// B2) Merge by product name (stops creating twin "SKU-md5" ghost rows)
		if ($name !== '') {
			$rs = $db->pquery(
				'SELECT stockid, quantity, product_name FROM vtiger_warehouse_stock WHERE warehouse_id = ?
				 ORDER BY (quantity > 0) DESC, stockid ASC',
				array($warehouseCode)
			);
			while ($rs && ($row = $db->fetchByAssoc($rs))) {
				$rowName = self::decodeDisplayTextDeep(isset($row['product_name']) ? $row['product_name'] : '');
				if (!self::stockNamesMatch($name, $rowName)) {
					continue;
				}
				$stockId = (int) $row['stockid'];
				$current = (float) $row['quantity'];
				$db->pquery(
					'UPDATE vtiger_warehouse_stock SET quantity = ?, updatedby = ?, updatedtime = ? WHERE stockid = ?',
					array($current - $qtyNeeded, (int) $userId, $now, $stockId)
				);
				return;
			}
		}

		// C) Prefer real catalog identity — avoid inventing SKU-md5 when we know pid
		if ($sku === '') {
			if ($productId > 0) {
				$sku = 'PID-' . $productId;
			} else {
				// Absolute last resort only
				$sku = $name !== '' ? self::guessSkuFromName($name) : 'UNK';
			}
		}

		$wh = self::findWarehouseRowByCode($db, $warehouseCode);
		$whName = $wh ? self::decodeDisplayTextDeep((string) $wh['name']) : $warehouseCode;
		$key = self::stockProductKey($warehouseCode, $sku, $lot);
		$rs = $db->pquery(
			'SELECT stockid, quantity FROM vtiger_warehouse_stock WHERE product_key = ? LIMIT 1',
			array($key)
		);
		if ($rs && $db->num_rows($rs) > 0) {
			$stockId = (int) $db->query_result($rs, 0, 'stockid');
			$current = (float) $db->query_result($rs, 0, 'quantity');
			$db->pquery(
				'UPDATE vtiger_warehouse_stock SET quantity = ?, updatedby = ?, updatedtime = ? WHERE stockid = ?',
				array($current - $qtyNeeded, (int) $userId, $now, $stockId)
			);
			return;
		}
		$stockId = (int) $db->getUniqueID('vtiger_warehouse_stock');
		$db->pquery(
			'INSERT INTO vtiger_warehouse_stock(
				stockid, product_key, productid, product_name, warehouse_id, warehouse_name,
				quantity, shrinkage_qty, last_price, createdby, updatedby, createdtime, updatedtime
			) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
			array(
				$stockId,
				$key,
				$productId > 0 ? $productId : null,
				$name !== '' ? $name : $sku,
				$warehouseCode,
				$whName,
				0 - $qtyNeeded,
				0,
				0,
				(int) $userId,
				(int) $userId,
				$now,
				$now,
			)
		);
	}

	protected static function assertOutboundStockAvailable(PearDatabase $db, $warehouseCode, array $lines) {
		require_once 'modules/Warehouse/helpers/SettingsHelper.php';
		if (Warehouse_Settings_Helper::allowNegativeStock()) {
			return;
		}
		foreach ($lines as $line) {
			$qty = (float) (isset($line['qty']) ? $line['qty'] : 0);
			if ($qty <= 0) {
				continue;
			}
			$name = trim((string) (isset($line['name']) ? $line['name'] : ''));
			$sku = trim((string) (isset($line['sku']) ? $line['sku'] : ''));
			$lot = trim((string) (isset($line['lot']) ? $line['lot'] : ''));
			$productId = (int) (isset($line['product_id']) ? $line['product_id'] : 0);
			$short = self::deductQtyFromWarehouseStock(
				$db,
				$warehouseCode,
				$qty,
				$sku,
				$lot,
				$name,
				$productId,
				0,
				self::nowSql(),
				false
			);
			if ($short > 0.00000001) {
				$label = $name !== '' ? $name : ($sku !== '' ? $sku : 'hàng');
				if ($lot !== '') {
					$label .= ' · Lô ' . $lot;
				}
				throw new Exception('Không đủ tồn kho để xuất: ' . $label . ' (thiếu ' . rtrim(rtrim(number_format($short, 3, '.', ''), '0'), '.') . ').');
			}
		}
	}

	/**
	 * Cộng tồn kho đích khi xuất chuyển kho (một lần / phiếu).
	 */
	protected static function creditStockForTransferIssue(PearDatabase $db, $toWarehouseId, $issueId, $userId, array &$meta, array $fallbackLines = array()) {
		$toWarehouseId = trim((string) $toWarehouseId);
		if ($toWarehouseId === '' || !empty($meta['stockCreditedTo'])) {
			return;
		}
		$toWh = self::findWarehouseRowByCode($db, $toWarehouseId);
		if (!$toWh) {
			throw new Exception('Không tìm thấy kho đích để nhập chuyển kho.');
		}
		$toName = self::decodeDisplayTextDeep((string) $toWh['name']);
		$lines = array();
		$raw = self::loadIssueItemsRaw($db, $issueId);
		if (!empty($raw)) {
			foreach ($raw as $it) {
				$lines[] = array(
					'sku' => self::decodeDisplayTextDeep(isset($it['line_note']) ? $it['line_note'] : ''),
					'name' => self::decodeDisplayTextDeep(isset($it['product_name']) ? $it['product_name'] : ''),
					'lot' => self::decodeDisplayTextDeep(isset($it['serial_number']) ? $it['serial_number'] : ''),
					'qty' => (float) (isset($it['quantity']) ? $it['quantity'] : 0),
					'product_id' => (int) (isset($it['productid']) ? $it['productid'] : 0),
					'price' => 0,
					'mfg' => '',
					'expiry' => '',
					'location' => '',
				);
			}
		} else {
			foreach ($fallbackLines as $line) {
				$lines[] = array(
					'sku' => trim((string) (isset($line['sku']) ? $line['sku'] : '')),
					'name' => trim((string) (isset($line['name']) ? $line['name'] : '')),
					'lot' => trim((string) (isset($line['lot']) ? $line['lot'] : '')),
					'qty' => (float) (isset($line['qty']) ? $line['qty'] : 0),
					'product_id' => (int) (isset($line['product_id']) ? $line['product_id'] : 0),
					'price' => (float) (isset($line['price']) ? $line['price'] : (isset($line['unit_price']) ? $line['unit_price'] : 0)),
					'mfg' => isset($line['mfg']) ? $line['mfg'] : '',
					'expiry' => isset($line['expiry']) ? $line['expiry'] : '',
					'location' => '',
				);
			}
		}
		foreach ($lines as $line) {
			if ($line['qty'] <= 0 || $line['name'] === '') {
				continue;
			}
			if ($line['sku'] === '') {
				$line['sku'] = self::guessSkuFromName($line['name']);
			}
			if ($line['lot'] === '') {
				$line['lot'] = '—';
			}
			self::applyInboundStockLine($db, $toWarehouseId, $toName, $line, $userId);
		}
		$meta['stockCreditedTo'] = $toWarehouseId;
	}

	/**
	 * Hoàn tồn khi huỷ phiếu xuất (đã trừ nguồn / đã cộng đích chuyển kho).
	 * Mirror deduct keys: productid first, then resolveProductSku — never invent SKU-only keys.
	 */
	protected static function restoreStockForCancelledIssue(PearDatabase $db, $warehouseCode, $issueId, $userId, array &$meta) {
		$wh = self::findWarehouseRowByCode($db, $warehouseCode);
		$whName = $wh ? self::decodeDisplayTextDeep((string) $wh['name']) : $warehouseCode;

		// Đảo chuyển kho đích trước (trừ lại hàng đã cộng).
		$creditedTo = trim((string) (isset($meta['stockCreditedTo']) ? $meta['stockCreditedTo'] : ''));
		if ($creditedTo !== '') {
			$raw = self::loadIssueItemsRaw($db, $issueId);
			$now = self::nowSql();
			foreach ($raw as $it) {
				$qty = (float) (isset($it['quantity']) ? $it['quantity'] : 0);
				if ($qty <= 0) {
					continue;
				}
				$sku = self::decodeDisplayTextDeep(isset($it['line_note']) ? $it['line_note'] : '');
				$lot = self::decodeDisplayTextDeep(isset($it['serial_number']) ? $it['serial_number'] : '');
				$name = self::decodeDisplayTextDeep(isset($it['product_name']) ? $it['product_name'] : '');
				$productId = (int) (isset($it['productid']) ? $it['productid'] : 0);
				if ($sku === '' && $productId > 0) {
					$sku = self::resolveProductSku($db, $productId);
				}
				self::deductQtyFromWarehouseStock(
					$db,
					$creditedTo,
					$qty,
					$sku,
					$lot,
					$name,
					$productId,
					$userId,
					$now,
					true
				);
			}
			$meta['stockCreditedTo'] = '';
		}

		// Only restore when stock was actually deducted (flag or timestamp).
		if (empty($meta['stockDeducted']) && empty($meta['stockDeductedAt'])) {
			return;
		}

		$raw = self::loadIssueItemsRaw($db, $issueId);
		foreach ($raw as $it) {
			$qty = (float) (isset($it['quantity']) ? $it['quantity'] : 0);
			if ($qty <= 0) {
				continue;
			}
			$productId = (int) (isset($it['productid']) ? $it['productid'] : 0);
			$sku = self::decodeDisplayTextDeep(isset($it['line_note']) ? $it['line_note'] : '');
			$name = self::decodeDisplayTextDeep(isset($it['product_name']) ? $it['product_name'] : '');
			$lot = self::decodeDisplayTextDeep(isset($it['serial_number']) ? $it['serial_number'] : '');
			if ($sku === '' && $productId > 0) {
				$sku = self::resolveProductSku($db, $productId);
			}
			if ($name === '' && $productId > 0) {
				try {
					$nrs = $db->pquery(
						'SELECT productsservicesname FROM vtiger_productsservices WHERE productsservicesid = ? LIMIT 1',
						array($productId)
					);
					if ($nrs && $db->num_rows($nrs) > 0) {
						$name = self::decodeDisplayTextDeep($db->query_result($nrs, 0, 'productsservicesname'));
					}
				} catch (Exception $e) {
					// optional
				}
			}
			if ($productId > 0) {
				self::restoreQtyToWarehouseStockByProduct(
					$db,
					$warehouseCode,
					$whName,
					$qty,
					$productId,
					$sku,
					$lot,
					$name,
					$userId
				);
				continue;
			}
			if ($name === '' && $sku === '') {
				continue;
			}
			if ($sku === '' && $name !== '') {
				$sku = self::guessSkuFromName($name);
			}
			if ($lot === '') {
				$lot = '—';
			}
			self::applyInboundStockLine(
				$db,
				$warehouseCode,
				$whName,
				array(
					'sku' => $sku,
					'name' => $name !== '' ? $name : $sku,
					'lot' => $lot,
					'qty' => $qty,
					'product_id' => 0,
					'price' => 0,
					'mfg' => '',
					'expiry' => '',
					'location' => '',
				),
				$userId
			);
		}
		$meta['stockDeducted'] = false;
		unset($meta['stockDeductedAt']);
	}

	/**
	 * Add qty back to existing warehouse stock row(s) matched by productid (same as deduct).
	 */
	protected static function restoreQtyToWarehouseStockByProduct(
		PearDatabase $db,
		$warehouseCode,
		$whName,
		$qty,
		$productId,
		$sku,
		$lot,
		$name,
		$userId
	) {
		$qty = (float) $qty;
		$productId = (int) $productId;
		if ($qty <= 0 || $productId <= 0) {
			return;
		}
		$remaining = $qty;
		$now = self::nowSql();
		$lot = trim((string) $lot);

		$rs = $db->pquery(
			'SELECT stockid, quantity FROM vtiger_warehouse_stock
			 WHERE warehouse_id = ? AND productid = ?
			 ORDER BY (quantity < 0) DESC, stockid ASC',
			array($warehouseCode, $productId)
		);
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			if ($remaining <= 0.00000001) {
				break;
			}
			$stockId = (int) $row['stockid'];
			$current = (float) $row['quantity'];
			$add = $remaining;
			$db->pquery(
				'UPDATE vtiger_warehouse_stock SET quantity = ?, updatedby = ?, updatedtime = ? WHERE stockid = ?',
				array($current + $add, (int) $userId, $now, $stockId)
			);
			$remaining -= $add;
		}
		if ($remaining <= 0.00000001) {
			return;
		}
		if ($sku === '') {
			$sku = self::resolveProductSku($db, $productId);
		}
		if ($sku === '' && $name !== '') {
			$sku = self::guessSkuFromName($name);
		}
		if ($sku === '') {
			$sku = 'PID-' . $productId;
		}
		if ($lot === '') {
			$lot = '—';
		}
		self::applyInboundStockLine(
			$db,
			$warehouseCode,
			$whName,
			array(
				'sku' => $sku,
				'name' => $name !== '' ? $name : $sku,
				'lot' => $lot,
				'qty' => $remaining,
				'product_id' => $productId,
				'price' => 0,
				'mfg' => '',
				'expiry' => '',
				'location' => '',
			),
			$userId
		);
	}

	/**
	 * Chuẩn hoá status xuất về bước trên path (happy path).
	 */
	protected static function normalizeIssuePathStatus($status) {
		$status = trim((string) $status);
		if ($status === 'pending_approval' || $status === 'draft') {
			return 'waiting_print';
		}
		if ($status === 'approved') {
			return 'packed';
		}
		return $status;
	}

	protected static function issuePathOrder() {
		return array('waiting_print', 'picking', 'packed', 'shipped');
	}

	protected static function issuePathLabel($status) {
		$map = array(
			'waiting_print' => 'Chờ soạn',
			'picking' => 'Đang soạn',
			'packed' => 'Đã soạn',
			'shipped' => 'Đã giao',
		);
		$norm = self::normalizeIssuePathStatus($status);
		return isset($map[$norm]) ? $map[$norm] : $norm;
	}

	/**
	 * Quay lại bước trước trên path xuất. Hoàn tồn nếu lùi qua mốc đã trừ kho (packed).
	 * @return string new db status
	 */
	protected static function revertIssueStatus(
		PearDatabase $db,
		$warehouseCode,
		$issueId,
		$fromStatus,
		$targetStatus,
		$role,
		$userId,
		array &$meta
	) {
		$order = self::issuePathOrder();
		$from = self::normalizeIssuePathStatus($fromStatus);
		$to = self::normalizeIssuePathStatus($targetStatus);
		$fromIdx = array_search($from, $order, true);
		$toIdx = array_search($to, $order, true);
		if ($fromIdx === false) {
			throw new Exception('Không thể quay lại từ trạng thái hiện tại (' . self::issuePathLabel($fromStatus) . ').');
		}
		if ($toIdx === false) {
			throw new Exception('Bước đích không hợp lệ.');
		}
		if ($toIdx >= $fromIdx) {
			throw new Exception('Chỉ được quay lại bước trước đó.');
		}
		$packedIdx = array_search('packed', $order, true);
		// Đã trừ tồn từ lúc "Đã soạn" — lùi về trước packed thì hoàn tồn.
		if ($packedIdx !== false && $fromIdx >= $packedIdx && $toIdx < $packedIdx) {
			self::restoreStockForCancelledIssue($db, $warehouseCode, $issueId, $userId, $meta);
		}
		self::pushTimeline(
			$meta,
			'Quay lại: ' . self::issuePathLabel($to),
			$role,
			'Từ ' . self::issuePathLabel($from)
		);
		return $to;
	}

	protected static function receiptPathOrder() {
		return array('draft', 'pending_qc', 'qc_passed', 'approved', 'stored');
	}

	protected static function normalizeReceiptPathStatus($status) {
		$status = trim((string) $status);
		if ($status === 'qc_failed') {
			return 'pending_qc';
		}
		return $status;
	}

	protected static function receiptPathLabel($status) {
		$map = array(
			'draft' => 'Nháp',
			'pending_qc' => 'Chờ QC',
			'qc_passed' => 'QC đạt',
			'qc_failed' => 'QC không đạt',
			'approved' => 'Đã duyệt',
			'stored' => 'Đã nhập kho',
		);
		return isset($map[$status]) ? $map[$status] : $status;
	}

	/**
	 * Hoàn tồn đã cộng khi nhập kho (store).
	 */
	protected static function reverseStoredReceiptStock(
		PearDatabase $db,
		$warehouseCode,
		$receiptId,
		$userId,
		array &$meta
	) {
		if (empty($meta['stockStored'])) {
			// Vẫn thử reverse nếu đang ở stored (phiếu cũ chưa gắn flag).
			// Caller quyết định dựa trên status.
		}
		$items = self::loadReceiptItemsRaw($db, $receiptId);
		$now = self::nowSql();
		foreach ($items as $it) {
			$qty = (float) (isset($it['quantity']) ? $it['quantity'] : 0);
			if ($qty <= 0) {
				continue;
			}
			$name = self::decodeDisplayTextDeep(isset($it['product_name']) ? $it['product_name'] : '');
			$lot = self::decodeDisplayTextDeep(isset($it['serial_number']) ? $it['serial_number'] : '');
			$sku = self::decodeDisplayTextDeep(isset($it['line_note']) ? $it['line_note'] : '');
			$productId = (int) (isset($it['productid']) ? $it['productid'] : 0);
			if ($sku === '' && $productId > 0) {
				$sku = self::resolveProductSku($db, $productId);
			}
			self::deductQtyFromWarehouseStock(
				$db,
				$warehouseCode,
				$qty,
				$sku,
				$lot,
				$name,
				$productId,
				$userId,
				$now,
				true
			);
		}
		$meta['stockStored'] = false;
	}

	/**
	 * Quay lại bước trước trên path nhập.
	 * @return string new status
	 */
	protected static function revertReceiptStatus(
		PearDatabase $db,
		$warehouseCode,
		$whName,
		$receiptId,
		$fromStatus,
		$targetStatus,
		$role,
		$userId,
		array &$meta
	) {
		$order = self::receiptPathOrder();
		$fromRaw = trim((string) $fromStatus);
		$to = trim((string) $targetStatus);
		$fromPath = self::normalizeReceiptPathStatus($fromRaw);
		$toPath = self::normalizeReceiptPathStatus($to);

		// Cho phép qc_failed → pending_qc / draft
		if ($fromRaw === 'qc_failed') {
			if (!in_array($to, array('pending_qc', 'draft'), true)) {
				throw new Exception('Từ QC không đạt chỉ quay về Chờ QC hoặc Nháp.');
			}
			if ($to === 'pending_qc') {
				unset($meta['qc']);
			}
			self::pushTimeline(
				$meta,
				'Quay lại: ' . self::receiptPathLabel($to),
				$role,
				'Từ QC không đạt'
			);
			return $to;
		}

		$fromIdx = array_search($fromPath, $order, true);
		$toIdx = array_search($toPath, $order, true);
		if ($fromIdx === false || $toIdx === false) {
			throw new Exception('Không thể quay lại từ/đến trạng thái này.');
		}
		if ($toIdx >= $fromIdx) {
			throw new Exception('Chỉ được quay lại bước trước đó.');
		}
		$storedIdx = array_search('stored', $order, true);
		if ($storedIdx !== false && $fromIdx >= $storedIdx && $toIdx < $storedIdx) {
			self::reverseStoredReceiptStock($db, $warehouseCode, $receiptId, $userId, $meta);
		}
		if ($toIdx <= array_search('pending_qc', $order, true)) {
			unset($meta['qc']);
		}
		self::pushTimeline(
			$meta,
			'Quay lại: ' . self::receiptPathLabel($to),
			$role,
			'Từ ' . self::receiptPathLabel($fromRaw)
		);
		return $to;
	}

	public static function applyIssueAction($warehouseCode, $issueCode, $actionKey, $role, $note = '', $userId = 0, $targetStatus = '') {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$warehouseCode = trim((string) $warehouseCode);
		$issueCode = trim((string) $issueCode);
		$actionKey = trim((string) $actionKey);
		if ($warehouseCode === '' || $issueCode === '' || $actionKey === '') {
			throw new Exception('Thiếu thông tin thao tác phiếu xuất.');
		}

		$wh = self::findWarehouseRowByCode($db, $warehouseCode);
		if (!$wh) {
			throw new Exception('Không tìm thấy kho.');
		}
		$whName = (string) $wh['name'];

		$row = self::findIssueRowByCode($db, $issueCode, $warehouseCode);
		if (!$row) {
			throw new Exception('Không tìm thấy phiếu xuất.');
		}
		$issueId = (int) $row['issueid'];
		$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');
		$dbStatus = isset($row['status']) ? (string) $row['status'] : '';
		$newDbStatus = $dbStatus;

		if ($actionKey === 'issue-start-pick') {
			if (!in_array($dbStatus, array('waiting_print', 'draft', 'pending_approval'), true)) {
				throw new Exception('Phiếu không ở trạng thái chờ soạn.');
			}
			$newDbStatus = 'picking';
			self::pushTimeline($meta, 'Bắt đầu soạn hàng', $role, '');
		} else if ($actionKey === 'issue-finish-pick') {
			if ($dbStatus !== 'picking') {
				throw new Exception('Phiếu không ở trạng thái đang soạn.');
			}
			$newDbStatus = 'packed';
			// Deduct only if not already deducted at waiting_print (Chờ soạn).
			// Legacy slips created before this change still deduct here.
			if (empty($meta['stockDeducted'])) {
				self::deductStockForIssue($db, $warehouseCode, $issueId, $userId, $meta);
			}
			$ot = trim((string) (isset($meta['outboundType']) ? $meta['outboundType'] : ''));
			$toId = trim((string) (isset($meta['toWarehouseId']) ? $meta['toWarehouseId'] : ''));
			if ($ot === 'transfer' && $toId !== '') {
				self::creditStockForTransferIssue($db, $toId, $issueId, $userId, $meta);
			}
			self::pushTimeline($meta, 'Đã soạn hàng', $role, '');
		} else if ($actionKey === 'issue-ship') {
			if (!in_array($dbStatus, array('packed', 'approved'), true)) {
				throw new Exception('Phiếu chưa hoàn tất soạn hàng.');
			}
			$newDbStatus = 'shipped';
			self::pushTimeline($meta, 'Đã giao hàng', $role, '');
		} else if ($actionKey === 'issue-submit') {
			$newDbStatus = 'waiting_print';
			self::pushTimeline($meta, 'Chờ soạn', $role, '');
		} else if ($actionKey === 'issue-approve') {
			$newDbStatus = 'picking';
			self::pushTimeline($meta, 'Bắt đầu soạn hàng', $role, '');
		} else if ($actionKey === 'issue-reject') {
			$newDbStatus = 'rejected';
			self::pushTimeline($meta, 'Từ chối phiếu', $role, (string) ($note !== '' ? $note : 'Không nêu lý do'));
		} else if ($actionKey === 'issue-cancel') {
			$cancellable = array('waiting_print', 'draft', 'pending_approval', 'picking', 'packed', 'approved');
			if (!in_array($dbStatus, $cancellable, true)) {
				throw new Exception('Chỉ huỷ được phiếu ở trạng thái Chờ soạn, Đang soạn hoặc Đã soạn.');
			}
			self::restoreStockForCancelledIssue($db, $warehouseCode, $issueId, $userId, $meta);
			$newDbStatus = 'cancelled';
			self::pushTimeline($meta, 'Huỷ phiếu xuất', $role, (string) ($note !== '' ? $note : 'Huỷ xuất kho'));
		} else if ($actionKey === 'issue-revert') {
			$newDbStatus = self::revertIssueStatus(
				$db,
				$warehouseCode,
				$issueId,
				$dbStatus,
				$targetStatus,
				$role,
				$userId,
				$meta
			);
		} else {
			throw new Exception('Unsupported issue action: ' . $actionKey);
		}

		$now = self::nowSql();
		$db->pquery(
			'UPDATE vtiger_goodsissue SET status = ?, mk_meta_json = ?, updatedtime = ? WHERE issueid = ?',
			array($newDbStatus, self::encodeMeta($meta), $now, $issueId)
		);

		// Xuất bán: SO status follows GI status (waiting_print / picking / packed / shipped / rejected).
		try {
			require_once 'modules/GoodsIssue/helpers/SyncSalesOrderStatus.php';
			GoodsIssue_SyncSalesOrderStatus_Helper::syncFromIssueId($issueId, $newDbStatus);
		} catch (Exception $ignore) {
			// Do not block warehouse workflow if SO sync fails.
		}

		return array(
			'code' => $issueCode,
			'warehouse' => $warehouseCode,
			'data' => self::getWarehouseData($db, $warehouseCode),
		);
	}

	protected static function loadSalesOrderOutboundContext(PearDatabase $db, $salesOrderId) {
		$salesOrderId = (int) $salesOrderId;
		if ($salesOrderId <= 0) {
			return array('organization' => '', 'contact' => '', 'soRef' => '');
		}
		$rs = $db->pquery(
			'SELECT so.salesorder_no, so.subject, so.contactid, so.potentialid,
			        COALESCE(acc.accountname, \'\') AS organization,
			        TRIM(CONCAT(IFNULL(cd.firstname, \'\'), \' \', IFNULL(cd.lastname, \'\'))) AS contact_name
			 FROM vtiger_salesorder so
			 LEFT JOIN vtiger_account acc ON acc.accountid = so.accountid
			 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
			 WHERE so.salesorderid = ? LIMIT 1',
			array($salesOrderId)
		);
		if (!$rs || $db->num_rows($rs) <= 0) {
			return array('organization' => '', 'contact' => '', 'soRef' => '');
		}
		$organization = self::decodeDisplayTextDeep($db->query_result($rs, 0, 'organization'));
		$contact = self::decodeDisplayTextDeep($db->query_result($rs, 0, 'contact_name'));
		$contactId = (int) $db->query_result($rs, 0, 'contactid');
		$potentialId = (int) $db->query_result($rs, 0, 'potentialid');
		if ($contact === '' && $contactId <= 0 && $potentialId > 0) {
			require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
			$potContactId = Vtiger_MkSalesCustomerName_Helper::resolveContactIdFromPotentialId($potentialId);
			if ($potContactId > 0) {
				$contact = Vtiger_MkSalesCustomerName_Helper::readContactNameById($potContactId);
			}
		}
		$soRef = self::decodeDisplayTextDeep($db->query_result($rs, 0, 'salesorder_no'));
		if ($soRef === '') {
			$soRef = self::decodeDisplayTextDeep($db->query_result($rs, 0, 'subject'));
		}
		return array('organization' => $organization, 'contact' => $contact, 'soRef' => $soRef);
	}

	protected static function loadIssues(PearDatabase $db, $warehouseCode) {
		$rs = $db->pquery(
			'SELECT issueid, code, destination, status, createdtime, mk_meta_json, salesorder_id
			 FROM vtiger_goodsissue
			 WHERE deleted = 0 AND warehouse_id = ?
			 ORDER BY createdtime DESC, issueid DESC',
			array($warehouseCode)
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');
			$salesOrderId = (int) (isset($row['salesorder_id']) ? $row['salesorder_id'] : 0);
			$outboundType = (string) (isset($meta['outboundType']) ? $meta['outboundType'] : '');
			$toWarehouseIdMeta = (string) (isset($meta['toWarehouseId']) ? $meta['toWarehouseId'] : '');
			$customer = self::decodeDisplayTextDeep(isset($row['destination']) ? $row['destination'] : '');
			$soRef = self::decodeDisplayTextDeep(isset($meta['soRef']) ? $meta['soRef'] : '');
			if ($salesOrderId > 0) {
				if ($outboundType === '' || $outboundType === 'internal') {
					$outboundType = 'sale';
				}
				$soCtx = self::loadSalesOrderOutboundContext($db, $salesOrderId);
				// Prefer contact (Người liên hệ) for KHÁCH HÀNG column.
				if ($soCtx['contact'] !== '') {
					$customer = $soCtx['contact'];
				} elseif ($customer === '' && $soCtx['organization'] !== '') {
					$customer = $soCtx['organization'];
				}
				if ($soRef === '' && $soCtx['soRef'] !== '') {
					$soRef = $soCtx['soRef'];
				}
			}
			if ($toWarehouseIdMeta !== '' && ($outboundType === '' || $outboundType === 'internal')) {
				$outboundType = 'transfer';
			}
			if ($outboundType === '') {
				$outboundType = 'internal';
			}
			$items = self::loadIssueItems($db, (int) $row['issueid']);
			$created = isset($row['createdtime']) ? $row['createdtime'] : '';
			$dbStatus = (string) (isset($row['status']) ? $row['status'] : '');
			$uiStatus = self::mapIssueStatusToUi($dbStatus);
			$out[] = array(
				'id' => (string) $row['code'],
				'outboundType' => $outboundType,
				'customer' => $customer,
				'soRef' => $soRef,
				'toWarehouseId' => $toWarehouseIdMeta,
				'exportTypeLabel' => self::decodeDisplayTextDeep((string) (isset($meta['exportTypeLabel']) ? $meta['exportTypeLabel'] : '')),
				'notes' => self::decodeDisplayTextDeep((string) (isset($meta['notes']) ? $meta['notes'] : '')),
				'createdAt' => $created !== '' ? gmdate('c', strtotime($created)) : gmdate('c'),
				'createdBy' => (string) (isset($meta['createdBy']) ? $meta['createdBy'] : ''),
				'status' => $uiStatus,
				'lines' => $items,
				'timeline' => isset($meta['timeline']) && is_array($meta['timeline']) ? $meta['timeline'] : array(),
				'stockDeducted' => !empty($meta['stockDeducted']),
				'stockCreditedTo' => (string) (isset($meta['stockCreditedTo']) ? $meta['stockCreditedTo'] : ''),
			);
		}
		return $out;
	}

	protected static function mapIssueStatusToUi($dbStatus) {
		$s = strtolower(trim((string) $dbStatus));
		if ($s === 'waiting_print') {
			return 'waiting_print';
		}
		if ($s === 'picking') {
			return 'picking';
		}
		if ($s === 'packed' || $s === 'prepared') {
			return 'packed';
		}
		if ($s === 'completed' || $s === 'shipped') {
			return 'shipped';
		}
		if ($s === 'rejected') {
			return 'rejected';
		}
		if ($s === 'cancelled' || $s === 'canceled' || $s === 'cancelled_by_user') {
			return 'cancelled';
		}
		if ($s === 'pending_approval' || $s === 'draft') {
			return 'waiting_print';
		}
		if ($s === 'approved') {
			return 'packed';
		}
		return $s !== '' ? $s : 'waiting_print';
	}

	protected static function loadIssueItems(PearDatabase $db, $issueId) {
		$rs = $db->pquery(
			'SELECT productid, product_name, quantity, serial_number, line_note
			 FROM vtiger_goodsissue_items
			 WHERE issueid = ?
			 ORDER BY itemid ASC',
			array($issueId)
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$sku = trim((string) (isset($row['line_note']) ? $row['line_note'] : ''));
			$sku = self::formatDisplaySku($sku !== '' ? $sku : self::guessSkuFromName($row['product_name']));
			$name = self::decodeDisplayTextDeep((string) $row['product_name']);
			$productId = (int) (isset($row['productid']) ? $row['productid'] : 0);
			$out[] = array(
				'sku' => $sku,
				'name' => $name,
				'lot' => (string) (isset($row['serial_number']) ? $row['serial_number'] : ''),
				'qty' => (float) $row['quantity'],
				'unit' => self::lookupProductUnit($db, $productId, $sku, $name),
			);
		}
		return $out;
	}

	protected static function loadStock(PearDatabase $db, $warehouseCode) {
		$rs = $db->pquery(
			'SELECT product_key, productid, product_name, quantity, last_price, expired_date, mfg_date, warehouse_name, storage_location
			 FROM vtiger_warehouse_stock
			 WHERE warehouse_id = ?
			 ORDER BY stockid ASC',
			array($warehouseCode)
		);
		$out = array();
		$productIds = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$parsed = self::parseStockIdentity($db, $row);
			$productId = (int) $parsed['productId'];
			if ($productId > 0) {
				$productIds[$productId] = $productId;
			}
			$out[] = array(
				'sku' => $parsed['sku'],
				'name' => $parsed['name'],
				'lot' => $parsed['lot'],
				'mfg' => $parsed['mfg'],
				'expiry' => $parsed['expiry'],
				'qty' => (float) $row['quantity'],
				'location' => self::decodeDisplayTextDeep((string) (isset($row['storage_location']) ? $row['storage_location'] : '')),
				'price' => (float) (isset($row['last_price']) ? $row['last_price'] : 0),
				'productId' => $productId,
				'expiryWarnDays' => 0,
				'avgDailySales' => 0.0,
				'daysToStockout' => null,
			);
		}

		$warnMap = self::productExpiryWarnDaysMap($db, array_values($productIds));
		$velocity = self::salesVelocityMap($db);
		$onHandByKey = array();
		foreach ($out as $item) {
			$key = self::stockVelocityKey($item['productId'], $item['sku']);
			if (!isset($onHandByKey[$key])) {
				$onHandByKey[$key] = 0.0;
			}
			$onHandByKey[$key] += (float) $item['qty'];
		}

		foreach ($out as $i => $item) {
			$pid = (int) $item['productId'];
			if ($pid > 0 && isset($warnMap[$pid])) {
				$out[$i]['expiryWarnDays'] = (int) $warnMap[$pid];
			}
			$avg = 0.0;
			if ($pid > 0 && isset($velocity['byId'][$pid])) {
				$avg = (float) $velocity['byId'][$pid];
			} else {
				$skuKey = strtolower(trim((string) $item['sku']));
				if ($skuKey !== '' && isset($velocity['bySku'][$skuKey])) {
					$avg = (float) $velocity['bySku'][$skuKey];
				}
			}
			$out[$i]['avgDailySales'] = $avg;
			$groupKey = self::stockVelocityKey($pid, $item['sku']);
			$onHand = isset($onHandByKey[$groupKey]) ? (float) $onHandByKey[$groupKey] : (float) $item['qty'];
			if ($avg > 0) {
				$out[$i]['daysToStockout'] = round($onHand / $avg, 1);
			} else {
				$out[$i]['daysToStockout'] = null;
			}
		}
		return $out;
	}

	protected static function stockVelocityKey($productId, $sku) {
		$productId = (int) $productId;
		if ($productId > 0) {
			return 'id:' . $productId;
		}
		$sku = strtolower(trim((string) $sku));
		return $sku !== '' ? ('sku:' . $sku) : 'none';
	}

	/**
	 * Avg daily sales over last 30 days from confirmed/shipped SOs (not draft/cancelled).
	 * @return array{byId: array<int,float>, bySku: array<string,float>}
	 */
	protected static function salesVelocityMap(PearDatabase $db) {
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		$cache = array('byId' => array(), 'bySku' => array());
		$since = date('Y-m-d H:i:s', strtotime('-30 days'));
		try {
			$rs = $db->pquery(
				'SELECT ip.productid, ps.sku, SUM(ip.quantity) AS qty_sold
				 FROM vtiger_inventoryproductrel ip
				 INNER JOIN vtiger_salesorder so ON so.salesorderid = ip.id
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 LEFT JOIN vtiger_productsservices ps ON ps.productsservicesid = ip.productid
				 WHERE ce.createdtime >= ?
				   AND LOWER(IFNULL(so.sostatus, \'\')) NOT IN (\'cancelled\', \'created\', \'draft\', \'nháp\', \'nhap\')
				 GROUP BY ip.productid, ps.sku',
				array($since)
			);
		} catch (Exception $e) {
			return $cache;
		}
		while ($row = $db->fetchByAssoc($rs)) {
			$qty = (float) (isset($row['qty_sold']) ? $row['qty_sold'] : 0);
			$avg = $qty / 30.0;
			$pid = (int) (isset($row['productid']) ? $row['productid'] : 0);
			if ($pid > 0) {
				if (!isset($cache['byId'][$pid])) {
					$cache['byId'][$pid] = 0.0;
				}
				$cache['byId'][$pid] += $avg;
			}
			$sku = strtolower(trim((string) (isset($row['sku']) ? $row['sku'] : '')));
			if ($sku !== '') {
				if (!isset($cache['bySku'][$sku])) {
					$cache['bySku'][$sku] = 0.0;
				}
				$cache['bySku'][$sku] += $avg;
			}
		}
		return $cache;
	}

	/**
	 * @param int[] $productIds
	 * @return array<int,int>
	 */
	protected static function productExpiryWarnDaysMap(PearDatabase $db, array $productIds) {
		$out = array();
		if (empty($productIds) || !self::hasExpiryWarnDaysColumn($db)) {
			return $out;
		}
		$ids = array();
		foreach ($productIds as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$ids[$id] = $id;
			}
		}
		if (empty($ids)) {
			return $out;
		}
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$rs = $db->pquery(
			'SELECT productsservicesid, expiry_warn_days FROM vtiger_productsservices
			 WHERE productsservicesid IN (' . $placeholders . ')',
			array_values($ids)
		);
		while ($row = $db->fetchByAssoc($rs)) {
			$out[(int) $row['productsservicesid']] = (int) (isset($row['expiry_warn_days']) ? $row['expiry_warn_days'] : 0);
		}
		return $out;
	}

	/**
	 * Resolve sku/lot/mfg/expiry for both WhMgmt (WH|sku|lot) and GoodsReceipt (P:/N:…:S:…:E:…) keys.
	 */
	protected static function parseStockIdentity(PearDatabase $db, array $row) {
		$key = trim((string) (isset($row['product_key']) ? $row['product_key'] : ''));
		$name = self::decodeDisplayTextDeep((string) (isset($row['product_name']) ? $row['product_name'] : ''));
		$productId = (int) (isset($row['productid']) ? $row['productid'] : 0);
		$sku = '';
		$lot = '';

		if (strpos($key, '|') !== false) {
			$parts = explode('|', $key);
			$sku = isset($parts[1]) ? trim((string) $parts[1]) : '';
			$lot = isset($parts[2]) ? trim((string) $parts[2]) : '';
		}

		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$parsed = Warehouse_Stock_Helper::parseProductKey($key);
		if ($productId <= 0 && !empty($parsed['product_id'])) {
			$productId = (int) $parsed['product_id'];
		}
		if ($lot === '' && preg_match('/:S:([^:]+)/u', $key, $sm)) {
			$lot = self::decodeDisplayTextDeep($sm[1]);
		} else {
			$lot = self::decodeDisplayTextDeep($lot);
		}

		if (($sku === '' || self::isAutoSku($sku)) && $productId > 0) {
			$sku = self::resolveProductSku($db, $productId);
		}
		if ($sku === '' || self::isAutoSku($sku)) {
			$sku = self::guessSkuFromName($name);
		}
		$sku = self::formatDisplaySku($sku);

		$mfg = self::normalizeDateValue(isset($row['mfg_date']) ? $row['mfg_date'] : '');
		$expiry = self::normalizeDateValue(isset($row['expired_date']) ? $row['expired_date'] : '');
		if ($expiry === '') {
			$expiry = self::normalizeDateValue(Warehouse_Stock_Helper::extractExpiryFromProductKey($key));
		}

		if ($mfg === '' || $expiry === '') {
			$fromInbound = self::lookupInboundLotDates($db, $productId, $name, $lot);
			if ($mfg === '' && !empty($fromInbound['mfg'])) {
				$mfg = $fromInbound['mfg'];
			}
			if ($expiry === '' && !empty($fromInbound['expiry'])) {
				$expiry = $fromInbound['expiry'];
			}
		}

		return array(
			'sku' => $sku,
			'name' => $name,
			'lot' => $lot,
			'mfg' => $mfg,
			'expiry' => $expiry,
			'productId' => $productId,
		);
	}

	/**
	 * Best-effort NSX/HSD from goods receipt lines matching this stock lot.
	 */
	protected static function lookupInboundLotDates(PearDatabase $db, $productId, $productName, $lot) {
		static $haveMfg = null;
		$productId = (int) $productId;
		$nameKey = self::strLowerUtf8(trim(self::decodeDisplayTextDeep($productName)));
		$lot = trim(self::decodeDisplayTextDeep($lot));
		$out = array('mfg' => '', 'expiry' => '');

		$params = array();
		$where = array('1=1');
		if ($lot !== '') {
			$where[] = 'TRIM(gri.serial_number) <> \'\' AND LOWER(TRIM(gri.serial_number)) = ?';
			$params[] = self::strLowerUtf8($lot);
		}
		if ($productId > 0) {
			$where[] = 'gri.productid = ?';
			$params[] = $productId;
		} elseif ($nameKey !== '') {
			$where[] = '(gri.productid IS NULL OR gri.productid = 0) AND LOWER(TRIM(gri.product_name)) = ?';
			$params[] = $nameKey;
		} elseif ($lot === '') {
			return $out;
		}

		if ($haveMfg === null) {
			$haveMfg = false;
			try {
				$colRs = $db->getColumnNames('vtiger_goodsreceipt_items');
				if (is_array($colRs)) {
					foreach ($colRs as $c) {
						if (strtolower($c) === 'mfg_date') {
							$haveMfg = true;
							break;
						}
					}
				}
			} catch (Exception $e) {
				$haveMfg = false;
			}
		}

		$cols = 'gri.expired_date';
		if ($haveMfg) {
			$cols .= ', gri.mfg_date';
		}

		$rs = $db->pquery(
			'SELECT ' . $cols . '
			 FROM vtiger_goodsreceipt_items gri
			 INNER JOIN vtiger_goodsreceipt gr ON gr.receiptid = gri.receiptid AND gr.deleted = 0
			 WHERE ' . implode(' AND ', $where) . '
			 ORDER BY gri.itemid DESC
			 LIMIT 1',
			$params
		);
		if ($rs && $db->num_rows($rs) > 0) {
			$row = $db->fetchByAssoc($rs);
			$out['expiry'] = self::normalizeDateValue(isset($row['expired_date']) ? $row['expired_date'] : '');
			if ($haveMfg) {
				$out['mfg'] = self::normalizeDateValue(isset($row['mfg_date']) ? $row['mfg_date'] : '');
			}
		}
		return $out;
	}

	/**
	 * Normalize DB/date strings to YYYY-MM-DD for <input type="date">.
	 */
	protected static function normalizeDateValue($value) {
		$s = trim((string) $value);
		if ($s === '' || $s === '—' || $s === '0000-00-00') {
			return '';
		}
		if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $s, $m)) {
			return $m[1];
		}
		$ts = strtotime($s);
		if ($ts) {
			return date('Y-m-d', $ts);
		}
		return '';
	}

	protected static function guessSkuFromName($name) {
		$name = trim((string) $name);
		if ($name === '') {
			return 'SKU';
		}
		return 'SKU-' . substr(md5(self::strLowerUtf8($name)), 0, 6);
	}

	protected static function listTransfers(PearDatabase $db) {
		$rs = $db->pquery(
			'SELECT transferid, code, from_warehouse_id, to_warehouse_id, sku, product_name, lot,
			        qty, status, approved_by, createdtime
			 FROM vtiger_warehouse_transfer
			 WHERE deleted = 0
			 ORDER BY createdtime DESC, transferid DESC',
			array()
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$created = isset($row['createdtime']) ? $row['createdtime'] : '';
			$out[] = array(
				'id' => (string) $row['code'],
				'fromWarehouseId' => (string) $row['from_warehouse_id'],
				'toWarehouseId' => (string) $row['to_warehouse_id'],
				'sku' => (string) (isset($row['sku']) ? $row['sku'] : ''),
				'name' => (string) (isset($row['product_name']) ? $row['product_name'] : ''),
				'lot' => (string) (isset($row['lot']) ? $row['lot'] : ''),
				'qty' => (float) $row['qty'],
				'status' => (string) $row['status'],
				'approvedBy' => (string) (isset($row['approved_by']) ? $row['approved_by'] : ''),
				'createdAt' => $created !== '' ? gmdate('c', strtotime($created)) : gmdate('c'),
			);
		}
		return $out;
	}

	protected static function findWarehouseRowByCode(PearDatabase $db, $code) {
		$rs = $db->pquery(
			'SELECT * FROM vtiger_warehouse WHERE code = ? AND deleted = 0 LIMIT 1',
			array($code)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return null;
		}
		return $db->fetchByAssoc($rs);
	}

	protected static function nextWarehouseCode(PearDatabase $db) {
		$rs = $db->pquery(
			"SELECT MAX(CAST(SUBSTRING(code, 4) AS UNSIGNED)) AS max_seq
			 FROM vtiger_warehouse
			 WHERE code LIKE 'WH-%'",
			array()
		);
		$max = 0;
		if ($rs && $db->num_rows($rs) > 0) {
			$max = (int) $db->query_result($rs, 0, 'max_seq');
		}
		return 'WH-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
	}

	public static function saveWarehouse(array $input, $code = null) {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		global $current_user;
		$userId = isset($current_user->id) ? (int) $current_user->id : 0;
		$now = date('Y-m-d H:i:s');

		$name = trim((string) (isset($input['name']) ? $input['name'] : ''));
		if ($name === '') {
			throw new Exception('Tên kho không được để trống.');
		}

		$type = trim((string) (isset($input['type']) ? $input['type'] : 'branch'));
		$address = trim((string) (isset($input['address']) ? $input['address'] : ''));
		$manager = trim((string) (isset($input['manager']) ? $input['manager'] : ''));
		$status = trim((string) (isset($input['status']) ? $input['status'] : 'active'));

		if ($code !== null && $code !== '') {
			$row = self::findWarehouseRowByCode($db, $code);
			if (!$row) {
				throw new Exception('Không tìm thấy kho.');
			}
			$db->pquery(
				'UPDATE vtiger_warehouse
				 SET name = ?, type = ?, address = ?, manager = ?, status = ?, updatedby = ?, updatedtime = ?
				 WHERE code = ? AND deleted = 0',
				array($name, $type, $address, $manager, $status, $userId, $now, $code)
			);
			$updated = self::findWarehouseRowByCode($db, $code);
			return self::mapWarehouseRow($updated);
		}

		$newCode = trim((string) (isset($input['code']) ? $input['code'] : ''));
		if ($newCode === '') {
			$newCode = self::nextWarehouseCode($db);
		} else {
			$dup = self::findWarehouseRowByCode($db, $newCode);
			if ($dup) {
				throw new Exception('Mã kho đã tồn tại.');
			}
		}
		$id = (int) $db->getUniqueID('vtiger_warehouse');
		$db->pquery(
			'INSERT INTO vtiger_warehouse
			 (warehouseid, code, name, type, address, manager, status, createdby, updatedby, createdtime, updatedtime, deleted)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,0)',
			array($id, $newCode, $name, $type, $address, $manager, $status, $userId, $userId, $now, $now)
		);
		$row = self::findWarehouseRowByCode($db, $newCode);
		return self::mapWarehouseRow($row);
	}

	public static function deleteWarehouse($code) {
		$db = PearDatabase::getInstance();
		$code = trim((string) $code);
		if ($code === '') {
			throw new Exception('Mã kho không hợp lệ.');
		}
		$db->pquery(
			'UPDATE vtiger_warehouse SET deleted = 1, updatedtime = ? WHERE code = ?',
			array(date('Y-m-d H:i:s'), $code)
		);
		return true;
	}

	public static function archiveWarehouse($code) {
		$db = PearDatabase::getInstance();
		$row = self::findWarehouseRowByCode($db, $code);
		if (!$row) {
			throw new Exception('Không tìm thấy kho.');
		}
		return self::saveWarehouse(array(
			'name' => $row['name'],
			'type' => $row['type'],
			'address' => isset($row['address']) ? $row['address'] : '',
			'manager' => isset($row['manager']) ? $row['manager'] : '',
			'status' => 'archived',
		), $code);
	}

	/**
	 * Products & Services catalog for inbound receipts and SO line pickers.
	 */
	public static function listProductCatalog(PearDatabase $db = null) {
		if ($db === null) {
			$db = PearDatabase::getInstance();
		}
		self::ensureProductNeedsQcField();
		self::ensureProductExpiryWarnDaysField();
		$needsQcSelect = self::hasNeedsQcColumn($db) ? ', ps.needs_qc' : '';
		$expirySelect = self::hasExpiryWarnDaysColumn($db) ? ', ps.expiry_warn_days' : '';
		$rs = $db->pquery(
			'SELECT ps.productsservicesid, ps.productsservicesname, ps.price, ps.item_type, ps.sku, ps.unit' . $needsQcSelect . $expirySelect . '
			 FROM vtiger_productsservices ps
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
			 ORDER BY ps.productsservicesname ASC
			 LIMIT 1000',
			array()
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$id = (int) $row['productsservicesid'];
			$name = trim((string) $row['productsservicesname']);
			if ($name !== '') {
				require_once 'modules/Warehouse/helpers/StockHelper.php';
				$name = Warehouse_Stock_Helper::decodeDisplayText($name);
			}
			if ($name === '') {
				continue;
			}
			$sku = self::formatDisplaySku(trim((string) (isset($row['sku']) ? $row['sku'] : '')));
			$unit = self::decodeDisplayTextDeep(trim((string) (isset($row['unit']) ? $row['unit'] : '')));
			$needsQcRaw = isset($row['needs_qc']) ? $row['needs_qc'] : 0;
			$needsQc = ($needsQcRaw === 1 || $needsQcRaw === '1' || $needsQcRaw === true || $needsQcRaw === 'on');
			$expiryWarnDays = (int) (isset($row['expiry_warn_days']) ? $row['expiry_warn_days'] : 0);
			if ($expiryWarnDays < 0) {
				$expiryWarnDays = 0;
			}
			$out[] = array(
				'id' => $id,
				'name' => $name,
				'price' => (float) (isset($row['price']) ? $row['price'] : 0),
				'type' => (string) (isset($row['item_type']) ? $row['item_type'] : ''),
				'sku' => $sku,
				'unit' => $unit,
				'needsQc' => $needsQc,
				'expiryWarnDays' => $expiryWarnDays,
			);
		}
		return $out;
	}

	/**
	 * Resolve Đơn vị tính from Products & Services by id / sku / name.
	 */
	public static function lookupProductUnit(PearDatabase $db, $productId = 0, $sku = '', $name = '') {
		$productId = (int) $productId;
		$sku = self::formatDisplaySku(trim((string) $sku));
		$name = trim((string) $name);
		$prs = null;
		if ($productId > 0) {
			$prs = $db->pquery(
				'SELECT ps.unit FROM vtiger_productsservices ps
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
				 WHERE ps.productsservicesid = ? LIMIT 1',
				array($productId)
			);
		}
		if ((!$prs || $db->num_rows($prs) < 1) && $sku !== '' && !self::isAutoSku($sku)) {
			$prs = $db->pquery(
				'SELECT ps.unit FROM vtiger_productsservices ps
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
				 WHERE ps.sku = ? LIMIT 1',
				array($sku)
			);
		}
		if ((!$prs || $db->num_rows($prs) < 1) && $name !== '') {
			$prs = $db->pquery(
				'SELECT ps.unit FROM vtiger_productsservices ps
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
				 WHERE ps.productsservicesname = ? LIMIT 1',
				array($name)
			);
		}
		if (!$prs || $db->num_rows($prs) < 1) {
			return '';
		}
		return self::decodeDisplayTextDeep(trim((string) $db->query_result($prs, 0, 'unit')));
	}

	protected static function nextGrnCode(PearDatabase $db) {
		$rs = $db->pquery(
			"SELECT MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)) AS max_seq
			 FROM vtiger_goodsreceipt
			 WHERE code LIKE 'GRN-%' AND deleted = 0",
			array()
		);
		$max = 0;
		if ($rs && $db->num_rows($rs) > 0) {
			$max = (int) $db->query_result($rs, 0, 'max_seq');
		}
		return 'GRN-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
	}

	/**
	 * Persist inbound receipt; QC routing is product-level (needs_qc on Hàng hoá).
	 *
	 * Lines with needs_qc go to pending_qc (no stock yet).
	 * Lines without needs_qc apply stock immediately.
	 * Mixed: receipt stays pending_qc until QC lines complete; store must not double-stock.
	 *
	 * @param array $payload supplier, poRef, lines[{product_id,sku,name,lot,qty,mfg,expiry}]
	 */
	public static function saveInboundReceipt($warehouseCode, array $payload, $userId = 0) {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$warehouseCode = trim((string) $warehouseCode);
		$wh = self::findWarehouseRowByCode($db, $warehouseCode);
		if (!$wh) {
			throw new Exception('Không tìm thấy kho.');
		}
		$whName = (string) $wh['name'];

		$supplier = trim((string) (isset($payload['supplier']) ? $payload['supplier'] : ''));
		$poRef = trim((string) (isset($payload['poRef']) ? $payload['poRef'] : ''));
		$lines = isset($payload['lines']) && is_array($payload['lines']) ? $payload['lines'] : array();

		if ($supplier === '' || $poRef === '' || empty($lines)) {
			throw new Exception('Thiếu thông tin phiếu nhập.');
		}

		$now = date('Y-m-d H:i:s');
		$code = self::nextGrnCode($db);

		// Pre-resolve lines + product QC flags before insert (skip invalids).
		$resolved = array();
		$qcLineCount = 0;
		$directLineCount = 0;
		foreach ($lines as $line) {
			$productId = (int) (isset($line['product_id']) ? $line['product_id'] : 0);
			$name = trim((string) (isset($line['name']) ? $line['name'] : ''));
			if ($productId > 0 && $name === '') {
				$prod = self::findProductById($db, $productId);
				if ($prod) {
					$name = (string) $prod['name'];
				}
			}
			$sku = trim((string) (isset($line['sku']) ? $line['sku'] : ''));
			if (($sku === '' || self::isAutoSku($sku)) && $productId > 0) {
				$sku = self::resolveProductSku($db, $productId);
			}
			$lot = trim((string) (isset($line['lot']) ? $line['lot'] : ''));
			$qty = (float) (isset($line['qty']) ? $line['qty'] : 0);
			$location = trim((string) (isset($line['location']) ? $line['location'] : ''));
			$expiry = isset($line['expiry']) && $line['expiry'] !== '—' ? $line['expiry'] : null;
			$mfg = isset($line['mfg']) && $line['mfg'] !== '' && $line['mfg'] !== '—' ? $line['mfg'] : null;
			if ($name === '' || $lot === '' || $qty <= 0) {
				continue;
			}
			if ($productId > 0 && $sku === '') {
				throw new Exception('Sản phẩm "' . $name . '" chưa có SKU. Hãy cập nhật SKU trong Products & Services trước khi nhập kho.');
			}
			$lineNeedsQc = self::productNeedsQc($db, $productId);
			if ($lineNeedsQc) {
				$qcLineCount++;
			} else {
				$directLineCount++;
			}
			$resolved[] = array(
				'product_id' => $productId,
				'name' => $name,
				'sku' => $sku,
				'lot' => $lot,
				'qty' => $qty,
				'location' => $location,
				'expiry' => $expiry,
				'mfg' => $mfg,
				'price' => isset($line['price']) ? (float) $line['price'] : 0,
				'needs_qc' => $lineNeedsQc,
			);
		}
		if (empty($resolved)) {
			throw new Exception('Thiếu thông tin phiếu nhập.');
		}

		$anyQc = $qcLineCount > 0;
		$status = $anyQc ? 'pending_qc' : 'stored';
		$timeline = array(
			array('at' => gmdate('c'), 'by' => 'Thủ kho', 'role' => 'keeper', 'action' => 'Tạo phiếu nhập'),
		);
		if ($anyQc) {
			$note = $directLineCount > 0
				? ($qcLineCount . ' dòng QC / ' . $directLineCount . ' dòng nhập thẳng')
				: ($qcLineCount . ' dòng QC theo hàng hoá');
			$timeline[] = array(
				'at' => gmdate('c'),
				'by' => 'Thủ kho',
				'role' => 'keeper',
				'action' => 'Gửi QC (theo hàng hoá)',
				'note' => $note,
			);
			if ($directLineCount > 0) {
				$timeline[] = array(
					'at' => gmdate('c'),
					'by' => 'Thủ kho',
					'role' => 'keeper',
					'action' => 'Nhập thẳng tồn kho (dòng không QC)',
					'note' => $directLineCount . ' dòng',
				);
			}
		} else {
			$timeline[] = array(
				'at' => gmdate('c'),
				'by' => 'Thủ kho',
				'role' => 'keeper',
				'action' => 'Nhập thẳng tồn kho',
			);
		}

		$lineNeedsQcMeta = array();
		$stockedItemIds = array();

		$receiptId = (int) $db->getUniqueID('vtiger_goodsreceipt');
		$db->pquery(
			'INSERT INTO vtiger_goodsreceipt
			 (receiptid, code, subject, source_name, received_date, status, warehouse_id, mk_meta_json,
			  createdby, updatedby, createdtime, updatedtime, deleted)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0)',
			array(
				$receiptId,
				$code,
				'Phiếu nhập ' . $code,
				$supplier,
				substr($now, 0, 10),
				$status,
				$warehouseCode,
				'{}',
				$userId,
				$userId,
				$now,
				$now,
			)
		);

		foreach ($resolved as $line) {
			$itemId = (int) $db->getUniqueID('vtiger_goodsreceipt_items');
			$lineNeedsQc = !empty($line['needs_qc']);
			$lineNeedsQcMeta[(string) $itemId] = $lineNeedsQc ? 1 : 0;

			$db->pquery(
				'INSERT INTO vtiger_goodsreceipt_items
				 (itemid, receiptid, productid, product_name, quantity, serial_number, expired_date, mfg_date, line_note, storage_location)
				 VALUES (?,?,?,?,?,?,?,?,?,?)',
				array(
					$itemId,
					$receiptId,
					$line['product_id'],
					$line['name'],
					$line['qty'],
					$line['lot'],
					$line['expiry'],
					$line['mfg'],
					$line['sku'],
					$line['location'] !== '' ? $line['location'] : null,
				)
			);

			if (!$lineNeedsQc) {
				self::applyInboundStockLine($db, $warehouseCode, $whName, array(
					'product_id' => $line['product_id'],
					'sku' => $line['sku'],
					'name' => $line['name'],
					'lot' => $line['lot'],
					'qty' => $line['qty'],
					'mfg' => $line['mfg'],
					'expiry' => $line['expiry'],
					'price' => $line['price'],
					'location' => $line['location'],
				), $userId);
				$stockedItemIds[(string) $itemId] = 1;
			}
		}

		$metaArr = array(
			'poRef' => $poRef,
			'createdBy' => 'Thủ kho',
			'timeline' => $timeline,
			'lineNeedsQc' => $lineNeedsQcMeta,
			'stockedItemIds' => $stockedItemIds,
		);
		if (!$anyQc) {
			$metaArr['stockStored'] = true;
		}
		$db->pquery(
			'UPDATE vtiger_goodsreceipt SET mk_meta_json = ?, updatedtime = ? WHERE receiptid = ?',
			array(json_encode($metaArr, JSON_UNESCAPED_UNICODE), $now, $receiptId)
		);

		return array(
			'code' => $code,
			'warehouse' => $warehouseCode,
			'data' => self::getWarehouseData($db, $warehouseCode),
		);
	}

	protected static function findProductById(PearDatabase $db, $productId) {
		self::ensureProductSkuField();
		$rs = $db->pquery(
			'SELECT ps.productsservicesid, ps.productsservicesname AS name, ps.price, ps.sku
			 FROM vtiger_productsservices ps
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
			 WHERE ps.productsservicesid = ?
			 LIMIT 1',
			array((int) $productId)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			return null;
		}
		$row = $db->fetchByAssoc($rs);
		if ($row && isset($row['name'])) {
			$row['name'] = self::decodeDisplayTextDeep($row['name']);
		}
		if ($row) {
			$row['sku'] = self::formatDisplaySku(isset($row['sku']) ? $row['sku'] : '');
		}
		return $row;
	}

	protected static function applyInboundStockLine(PearDatabase $db, $whCode, $whName, array $line, $userId) {
		$sku = (string) $line['sku'];
		$lot = (string) $line['lot'];
		$key = self::stockProductKey($whCode, $sku, $lot);
		$qty = (float) $line['qty'];
		$location = trim((string) (isset($line['location']) ? $line['location'] : ''));
		$mfg = isset($line['mfg']) && $line['mfg'] !== '' && $line['mfg'] !== '—' ? $line['mfg'] : null;
		$expiry = isset($line['expiry']) && $line['expiry'] !== '' && $line['expiry'] !== '—' ? $line['expiry'] : null;
		$now = date('Y-m-d H:i:s');

		$rs = $db->pquery(
			'SELECT stockid, quantity FROM vtiger_warehouse_stock WHERE product_key = ? LIMIT 1',
			array($key)
		);
		if ($rs && $db->num_rows($rs) > 0) {
			$stockId = (int) $db->query_result($rs, 0, 'stockid');
			$current = (float) $db->query_result($rs, 0, 'quantity');
			$db->pquery(
				'UPDATE vtiger_warehouse_stock
				 SET quantity = ?, product_name = ?, productid = ?, warehouse_id = ?, warehouse_name = ?,
				     last_price = CASE WHEN ? > 0 THEN ? ELSE last_price END,
				     expired_date = CASE WHEN ? IS NOT NULL AND ? <> \'\' THEN ? ELSE expired_date END,
				     mfg_date = CASE WHEN ? IS NOT NULL AND ? <> \'\' THEN ? ELSE mfg_date END,
				     storage_location = CASE WHEN ? <> \'\' THEN ? ELSE storage_location END,
				     updatedby = ?, updatedtime = ?
				 WHERE stockid = ?',
				array(
					$current + $qty,
					$line['name'],
					(int) $line['product_id'],
					$whCode,
					$whName,
					(float) $line['price'],
					(float) $line['price'],
					$expiry,
					$expiry,
					$expiry,
					$mfg,
					$mfg,
					$mfg,
					$location,
					$location,
					$userId,
					$now,
					$stockId,
				)
			);
			return;
		}

		$stockId = (int) $db->getUniqueID('vtiger_warehouse_stock');
		$code = 'STK-' . str_pad((string) $stockId, 4, '0', STR_PAD_LEFT);
		$db->pquery(
			'INSERT INTO vtiger_warehouse_stock
			 (stockid, code, product_key, productid, product_name, quantity, last_price,
			  warehouse_id, warehouse_name, expired_date, mfg_date, storage_location, createdtime, updatedtime, updatedby)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
			array(
				$stockId,
				$code,
				$key,
				(int) $line['product_id'],
				$line['name'],
				$qty,
				(float) $line['price'],
				$whCode,
				$whName,
				$expiry,
				$mfg,
				$location !== '' ? $location : null,
				$now,
				$now,
				$userId,
			)
		);
	}

	/**
	 * Payload for PHIẾU NHẬP KHO print/PDF (Mẫu số 01 - VT).
	 *
	 * @return array{receipt:array,warehouse:array,company:string}
	 */
	public static function getInboundReceiptPrintPayload($warehouseCode, $receiptCode) {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		$warehouseCode = trim((string) $warehouseCode);
		$receiptCode = trim((string) $receiptCode);
		if ($warehouseCode === '' || $receiptCode === '') {
			throw new Exception('Thiếu mã kho hoặc mã phiếu nhập.');
		}
		$wh = self::findWarehouseRowByCode($db, $warehouseCode);
		if (!$wh) {
			throw new Exception('Không tìm thấy kho.');
		}
		$rs = $db->pquery(
			'SELECT receiptid, code, source_name, status, createdtime, mk_meta_json, note
			 FROM vtiger_goodsreceipt
			 WHERE deleted = 0 AND warehouse_id = ? AND code = ?
			 LIMIT 1',
			array($warehouseCode, $receiptCode)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			throw new Exception('Không tìm thấy phiếu nhập.');
		}
		$row = $db->fetchByAssoc($rs);
		$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');
		$created = isset($row['createdtime']) ? (string) $row['createdtime'] : '';
		$dbStatus = (string) (isset($row['status']) ? $row['status'] : 'stored');
		self::syncQcMetaFromStorage($meta, $dbStatus, isset($row['note']) ? $row['note'] : '');
		self::normalizeReceiptTimeline($meta, $created, $dbStatus);
		$items = self::loadReceiptItems($db, (int) $row['receiptid'], $meta);
		foreach ($items as &$item) {
			if (empty($item['unit'])) {
				$item['unit'] = self::lookupProductUnit(
					$db,
					isset($item['product_id']) ? (int) $item['product_id'] : 0,
					isset($item['sku']) ? $item['sku'] : '',
					isset($item['name']) ? $item['name'] : ''
				);
			}
			if ((float) $item['unit_price'] > 0) {
				continue;
			}
			$sku = trim((string) (isset($item['sku']) ? $item['sku'] : ''));
			$name = trim((string) (isset($item['name']) ? $item['name'] : ''));
			$price = 0.0;
			if ($sku !== '') {
				$prs = $db->pquery(
					'SELECT ps.price, ps.unit FROM vtiger_productsservices ps
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
					 WHERE ps.sku = ? LIMIT 1',
					array($sku)
				);
				if ($prs && $db->num_rows($prs) > 0) {
					$price = (float) $db->query_result($prs, 0, 'price');
					if (empty($item['unit'])) {
						$item['unit'] = self::decodeDisplayTextDeep(trim((string) $db->query_result($prs, 0, 'unit')));
					}
				}
			}
			if ($price <= 0 && $name !== '') {
				$prs = $db->pquery(
					'SELECT ps.price, ps.unit FROM vtiger_productsservices ps
					 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
					 WHERE ps.productsservicesname = ? LIMIT 1',
					array($name)
				);
				if ($prs && $db->num_rows($prs) > 0) {
					$price = (float) $db->query_result($prs, 0, 'price');
					if (empty($item['unit'])) {
						$item['unit'] = self::decodeDisplayTextDeep(trim((string) $db->query_result($prs, 0, 'unit')));
					}
				}
			}
			if ($price > 0) {
				$item['unit_price'] = $price;
			}
		}
		unset($item);

		$company = 'Nguyên Khoa';
		try {
			require_once 'modules/Quotes/helpers/QuoteExcelExport.php';
			if (class_exists('Quotes_QuoteExcelExport_Helper') && method_exists('Quotes_QuoteExcelExport_Helper', 'nkCompanyName')) {
				$company = Quotes_QuoteExcelExport_Helper::nkCompanyName();
			}
		} catch (Exception $e) {
			// keep default
		}

		return array(
			'receipt' => array(
				'id' => (string) $row['code'],
				'supplier' => self::decodeDisplayTextDeep((string) (isset($row['source_name']) ? $row['source_name'] : '')),
				'poRef' => self::decodeDisplayTextDeep((string) (isset($meta['poRef']) ? $meta['poRef'] : '')),
				'createdAt' => $created,
				'createdBy' => self::decodeDisplayTextDeep((string) (isset($meta['createdBy']) ? $meta['createdBy'] : '')),
				'status' => $dbStatus !== '' ? $dbStatus : 'stored',
				'lines' => $items,
				'note' => self::decodeDisplayTextDeep((string) (isset($row['note']) ? $row['note'] : '')),
			),
			'warehouse' => array(
				'code' => (string) (isset($wh['code']) ? $wh['code'] : $warehouseCode),
				'name' => self::decodeDisplayTextDeep((string) (isset($wh['name']) ? $wh['name'] : '')),
				'address' => self::decodeDisplayTextDeep((string) (isset($wh['address']) ? $wh['address'] : '')),
				'manager' => self::decodeDisplayTextDeep((string) (isset($wh['manager']) ? $wh['manager'] : '')),
			),
			'company' => $company,
		);
	}

	protected static function nextGinCode(PearDatabase $db) {
		$rs = $db->pquery(
			"SELECT MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)) AS max_seq
			 FROM vtiger_goodsissue
			 WHERE code LIKE 'GIN-%' AND deleted = 0",
			array()
		);
		$max = 0;
		if ($rs && $db->num_rows($rs) > 0) {
			$max = (int) $db->query_result($rs, 0, 'max_seq');
		}
		return 'GIN-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
	}

	/**
	 * Persist outbound issue (xuất nội bộ / chuyển kho / huỷ) for print + list.
	 * Upserts by code when payload.id is provided and already exists.
	 *
	 * @param array $payload outboundType, customer, soRef, toWarehouseId, createdBy, status, notes, lines[]
	 */
	public static function saveOutboundIssue($warehouseCode, array $payload, $userId = 0) {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		if (class_exists('GoodsIssue_WorkflowSetup_Helper') && method_exists('GoodsIssue_WorkflowSetup_Helper', 'runAll')) {
			GoodsIssue_WorkflowSetup_Helper::runAll();
		}

		$warehouseCode = trim((string) $warehouseCode);
		$wh = self::findWarehouseRowByCode($db, $warehouseCode);
		if (!$wh) {
			throw new Exception('Không tìm thấy kho.');
		}

		$outboundType = trim((string) (isset($payload['outboundType']) ? $payload['outboundType'] : 'internal'));
		if ($outboundType === '') {
			$outboundType = 'internal';
		}
		$customer = trim((string) (isset($payload['customer']) ? $payload['customer'] : ''));
		$soRef = trim((string) (isset($payload['soRef']) ? $payload['soRef'] : ''));
		$toWarehouseId = trim((string) (isset($payload['toWarehouseId']) ? $payload['toWarehouseId'] : ''));
		// Kho đích có mã → luôn là xuất chuyển kho (tránh mất type khi meta bị encode).
		if ($toWarehouseId !== '' && ($outboundType === '' || $outboundType === 'internal')) {
			$outboundType = 'transfer';
		}
		$createdBy = trim((string) (isset($payload['createdBy']) ? $payload['createdBy'] : 'Thủ kho'));
		if ($createdBy === '') {
			$createdBy = 'Thủ kho';
		}
		$status = trim((string) (isset($payload['status']) ? $payload['status'] : 'waiting_print'));
		if ($status === '') {
			$status = 'waiting_print';
		}
		$notes = trim((string) (isset($payload['notes']) ? $payload['notes'] : ''));
		if ($notes === '' && isset($payload['reason'])) {
			$notes = trim((string) $payload['reason']);
		}
		$exportTypeLabel = trim((string) (isset($payload['exportTypeLabel']) ? $payload['exportTypeLabel'] : ''));
		$lines = isset($payload['lines']) && is_array($payload['lines']) ? $payload['lines'] : array();
		if (empty($lines)) {
			throw new Exception('Thiếu dòng hàng phiếu xuất.');
		}
		if ($outboundType === 'transfer' && $toWarehouseId === '' && $customer === '') {
			throw new Exception('Vui lòng chọn kho đích.');
		}
		if ($outboundType !== 'transfer' && $customer === '') {
			throw new Exception('Thiếu thông tin bộ phận / mục đích xuất.');
		}
		if ($outboundType === 'transfer' && $customer === '' && $toWarehouseId !== '') {
			$toWh = self::findWarehouseRowByCode($db, $toWarehouseId);
			$customer = $toWh ? self::decodeDisplayTextDeep((string) $toWh['name']) : $toWarehouseId;
		}

		$now = date('Y-m-d H:i:s');
		$requestedCode = trim((string) (isset($payload['id']) ? $payload['id'] : (isset($payload['code']) ? $payload['code'] : '')));
		$existingId = 0;
		$existingMeta = array();
		if ($requestedCode !== '') {
			$ex = $db->pquery(
				'SELECT issueid, mk_meta_json FROM vtiger_goodsissue WHERE deleted = 0 AND code = ? LIMIT 1',
				array($requestedCode)
			);
			if ($ex && $db->num_rows($ex) > 0) {
				$existingId = (int) $db->query_result($ex, 0, 'issueid');
				$existingMeta = self::decodeMeta($db->query_result($ex, 0, 'mk_meta_json'));
			}
		}

		$timeline = array(
			array(
				'at' => gmdate('c'),
				'by' => $createdBy,
				'role' => 'manager',
				'action' => 'Tạo phiếu xuất — ' . ($outboundType === 'transfer' ? 'Chuyển kho' : ($outboundType === 'scrap' ? 'Xuất huỷ' : 'Xuất nội bộ')),
			),
		);
		if (isset($payload['timeline']) && is_array($payload['timeline']) && !empty($payload['timeline'])) {
			$timeline = $payload['timeline'];
		}

		$alreadyDeducted = !empty($existingMeta['stockDeducted']);
		$alreadyCredited = trim((string) (isset($existingMeta['stockCreditedTo']) ? $existingMeta['stockCreditedTo'] : ''));

		if (!$alreadyDeducted) {
			self::assertOutboundStockAvailable($db, $warehouseCode, $lines);
		}

		$meta = array(
			'outboundType' => $outboundType,
			'soRef' => $soRef,
			'createdBy' => $createdBy,
			'toWarehouseId' => $toWarehouseId,
			'notes' => $notes,
			'exportTypeLabel' => $exportTypeLabel,
			'timeline' => $timeline,
			'stockDeducted' => $alreadyDeducted,
			'stockCreditedTo' => $alreadyCredited,
		);
		$metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);

		if ($existingId > 0) {
			$db->pquery(
				'UPDATE vtiger_goodsissue
				 SET destination = ?, status = ?, warehouse_id = ?, mk_meta_json = ?, updatedtime = ?, updatedby = ?
				 WHERE issueid = ?',
				array($customer, $status, $warehouseCode, $metaJson, $now, (int) $userId, $existingId)
			);
			$db->pquery('DELETE FROM vtiger_goodsissue_items WHERE issueid = ?', array($existingId));
			$issueId = $existingId;
			$code = $requestedCode;
		} else {
			$code = $requestedCode !== '' ? $requestedCode : self::nextGinCode($db);
			// Avoid unique collision if client-generated code already taken elsewhere.
			$clash = $db->pquery(
				'SELECT issueid FROM vtiger_goodsissue WHERE deleted = 0 AND code = ? LIMIT 1',
				array($code)
			);
			if ($clash && $db->num_rows($clash) > 0) {
				$code = self::nextGinCode($db);
			}
			$issueId = (int) $db->getUniqueID('vtiger_goodsissue');
			$db->pquery(
				'INSERT INTO vtiger_goodsissue
				 (issueid, code, subject, destination, issued_date, status, warehouse_id, mk_meta_json,
				  createdby, updatedby, createdtime, updatedtime, deleted)
				 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0)',
				array(
					$issueId,
					$code,
					'Phiếu xuất ' . $code,
					$customer,
					substr($now, 0, 10),
					$status,
					$warehouseCode,
					$metaJson,
					(int) $userId,
					(int) $userId,
					$now,
					$now,
				)
			);
		}

		foreach ($lines as $line) {
			$name = trim((string) (isset($line['name']) ? $line['name'] : ''));
			$sku = trim((string) (isset($line['sku']) ? $line['sku'] : ''));
			$lot = trim((string) (isset($line['lot']) ? $line['lot'] : ''));
			$qty = (float) (isset($line['qty']) ? $line['qty'] : 0);
			$price = (float) (isset($line['price']) ? $line['price'] : (isset($line['unit_price']) ? $line['unit_price'] : 0));
			$productId = (int) (isset($line['product_id']) ? $line['product_id'] : 0);
			if ($name === '' || $qty <= 0) {
				continue;
			}
			$itemId = (int) $db->getUniqueID('vtiger_goodsissue_items');
			// Prefer columns that always exist; unit_price when available.
			$hasUnitPrice = false;
			try {
				$colRs = $db->pquery('SHOW COLUMNS FROM vtiger_goodsissue_items LIKE ?', array('unit_price'));
				$hasUnitPrice = $colRs && $db->num_rows($colRs) > 0;
			} catch (Exception $e) {
				$hasUnitPrice = false;
			}
			if ($hasUnitPrice) {
				$db->pquery(
					'INSERT INTO vtiger_goodsissue_items
					 (itemid, issueid, productid, product_name, quantity, serial_number, line_note, unit_price)
					 VALUES (?,?,?,?,?,?,?,?)',
					array($itemId, $issueId, $productId, $name, $qty, $lot, $sku, $price)
				);
			} else {
				$db->pquery(
					'INSERT INTO vtiger_goodsissue_items
					 (itemid, issueid, productid, product_name, quantity, serial_number, line_note)
					 VALUES (?,?,?,?,?,?,?)',
					array($itemId, $issueId, $productId, $name, $qty, $lot, $sku)
				);
			}
		}

		// Trừ tồn ngay khi tạo/lưu phiếu xuất (idempotent qua stockDeducted).
		self::deductStockForIssue($db, $warehouseCode, $issueId, $userId, $meta);
		if ($outboundType === 'transfer' && $toWarehouseId !== '') {
			self::creditStockForTransferIssue($db, $toWarehouseId, $issueId, $userId, $meta, $lines);
		}
		$db->pquery(
			'UPDATE vtiger_goodsissue SET mk_meta_json = ?, updatedtime = ? WHERE issueid = ?',
			array(json_encode($meta, JSON_UNESCAPED_UNICODE), $now, $issueId)
		);

		return array(
			'code' => $code,
			'issue' => array(
				'id' => $code,
				'outboundType' => $outboundType,
				'customer' => $customer,
				'toWarehouseId' => $toWarehouseId,
				'soRef' => $soRef,
				'exportTypeLabel' => $exportTypeLabel,
				'notes' => $notes,
				'status' => $status,
				'createdAt' => gmdate('c', strtotime($now)),
				'createdBy' => $createdBy,
				'lines' => $lines,
				'timeline' => $timeline,
				'stockDeducted' => !empty($meta['stockDeducted']),
			),
			'data' => self::getWarehouseData($db, $warehouseCode),
		);
	}

	/**
	 * Payload in PHIẾU XUẤT KHO (02-VT) — HTML preview / PDF.
	 */
	public static function getOutboundIssuePrintPayload($warehouseCode, $issueCode) {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		$warehouseCode = trim((string) $warehouseCode);
		$issueCode = trim((string) $issueCode);
		if ($warehouseCode === '' || $issueCode === '') {
			throw new Exception('Thiếu mã kho hoặc mã phiếu xuất.');
		}
		$wh = self::findWarehouseRowByCode($db, $warehouseCode);
		if (!$wh) {
			throw new Exception('Không tìm thấy kho.');
		}
		$rs = $db->pquery(
			'SELECT issueid, code, destination, status, createdtime, mk_meta_json, salesorder_id, warehouse_id
			 FROM vtiger_goodsissue
			 WHERE deleted = 0 AND warehouse_id = ? AND code = ?
			 LIMIT 1',
			array($warehouseCode, $issueCode)
		);
		if (!$rs || $db->num_rows($rs) < 1) {
			// Fallback: phiếu có thể gắn warehouse_id khác / tạo trước khi đổi mã kho.
			$rs = $db->pquery(
				'SELECT issueid, code, destination, status, createdtime, mk_meta_json, salesorder_id, warehouse_id
				 FROM vtiger_goodsissue
				 WHERE deleted = 0 AND code = ?
				 ORDER BY issueid DESC
				 LIMIT 1',
				array($issueCode)
			);
		}
		if (!$rs || $db->num_rows($rs) < 1) {
			throw new Exception('Không tìm thấy phiếu xuất.');
		}
		$row = $db->fetchByAssoc($rs);
		$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');
		$created = isset($row['createdtime']) ? (string) $row['createdtime'] : '';
		$salesOrderId = (int) (isset($row['salesorder_id']) ? $row['salesorder_id'] : 0);
		$outboundType = (string) (isset($meta['outboundType']) ? $meta['outboundType'] : '');
		$customer = self::decodeDisplayTextDeep((string) (isset($row['destination']) ? $row['destination'] : ''));
		$soRef = self::decodeDisplayTextDeep((string) (isset($meta['soRef']) ? $meta['soRef'] : ''));
		$receiverAddress = self::decodeDisplayTextDeep((string) (isset($meta['receiverAddress']) ? $meta['receiverAddress'] : ''));
		if ($receiverAddress === '' && isset($meta['address'])) {
			$receiverAddress = self::decodeDisplayTextDeep((string) $meta['address']);
		}
		if ($salesOrderId > 0) {
			if ($outboundType === '' || $outboundType === 'internal') {
				$outboundType = 'sale';
			}
			$soCtx = self::loadSalesOrderOutboundContext($db, $salesOrderId);
			if ($soCtx['contact'] !== '') {
				$customer = $soCtx['contact'];
			} elseif ($customer === '' && $soCtx['organization'] !== '') {
				$customer = $soCtx['organization'];
			}
			if ($soRef === '' && $soCtx['soRef'] !== '') {
				$soRef = $soCtx['soRef'];
			}
			if ($receiverAddress === '' && $soCtx['organization'] !== '' && $soCtx['organization'] !== $customer) {
				$receiverAddress = $soCtx['organization'];
			}
		}
		$toWarehouseIdMeta = (string) (isset($meta['toWarehouseId']) ? $meta['toWarehouseId'] : '');
		if ($toWarehouseIdMeta !== '' && ($outboundType === '' || $outboundType === 'internal')) {
			$outboundType = 'transfer';
		}
		if ($outboundType === '') {
			$outboundType = 'internal';
		}

		$reason = self::decodeDisplayTextDeep((string) (isset($meta['reason']) ? $meta['reason'] : ''));
		if ($reason === '' && isset($meta['lyDoXuat'])) {
			$reason = self::decodeDisplayTextDeep((string) $meta['lyDoXuat']);
		}
		if ($reason === '') {
			if ($outboundType === 'sale') {
				$reason = $soRef !== '' ? ('Xuất bán theo đơn hàng ' . $soRef) : 'Xuất bán hàng';
			} elseif ($outboundType === 'scrap') {
				$reason = $soRef !== '' ? ('Xuất huỷ theo phiếu ' . $soRef) : 'Xuất huỷ hàng';
			} elseif ($outboundType === 'transfer') {
				$reason = 'Xuất điều chuyển kho';
			} else {
				$reason = 'Xuất kho nội bộ';
			}
		}

		$items = self::loadIssueItems($db, (int) $row['issueid']);
		foreach ($items as &$item) {
			$sku = trim((string) (isset($item['sku']) ? $item['sku'] : ''));
			$name = trim((string) (isset($item['name']) ? $item['name'] : ''));
			$price = (float) (isset($item['unit_price']) ? $item['unit_price'] : 0);
			$unit = trim((string) (isset($item['unit']) ? $item['unit'] : ''));
			if ($price <= 0) {
				$prs = null;
				if ($sku !== '') {
					$prs = $db->pquery(
						'SELECT ps.price, ps.unit FROM vtiger_productsservices ps
						 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
						 WHERE ps.sku = ? LIMIT 1',
						array($sku)
					);
				}
				if ((!$prs || $db->num_rows($prs) < 1) && $name !== '') {
					$prs = $db->pquery(
						'SELECT ps.price, ps.unit FROM vtiger_productsservices ps
						 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
						 WHERE ps.productsservicesname = ? LIMIT 1',
						array($name)
					);
				}
				if ($prs && $db->num_rows($prs) > 0) {
					$price = (float) $db->query_result($prs, 0, 'price');
					if ($unit === '') {
						$unit = self::decodeDisplayTextDeep(trim((string) $db->query_result($prs, 0, 'unit')));
					}
				}
			}
			if ($price > 0) {
				$item['unit_price'] = $price;
			}
			if ($unit === '') {
				$unit = self::lookupProductUnit($db, 0, $sku, $name);
			}
			if ($unit !== '') {
				$item['unit'] = $unit;
			}
			if (!isset($item['qty_request']) || (float) $item['qty_request'] <= 0) {
				$item['qty_request'] = (float) (isset($item['qty']) ? $item['qty'] : 0);
			}
		}
		unset($item);

		$company = 'Nguyên Khoa';
		$companyAddress = '';
		$companyPhone = '';
		try {
			require_once 'modules/Quotes/helpers/QuoteExcelExport.php';
			if (class_exists('Quotes_QuoteExcelExport_Helper')) {
				if (method_exists('Quotes_QuoteExcelExport_Helper', 'nkCompanyName')) {
					$company = Quotes_QuoteExcelExport_Helper::nkCompanyName();
				}
				if (method_exists('Quotes_QuoteExcelExport_Helper', 'nkAddress')) {
					$companyAddress = Quotes_QuoteExcelExport_Helper::nkAddress();
				}
				if (method_exists('Quotes_QuoteExcelExport_Helper', 'nkPhone')) {
					$companyPhone = Quotes_QuoteExcelExport_Helper::nkPhone();
				}
			}
		} catch (Exception $e) {
			// keep default
		}

		$notes = self::decodeDisplayTextDeep((string) (isset($meta['notes']) ? $meta['notes'] : ''));
		if ($notes === '' && isset($meta['note'])) {
			$notes = self::decodeDisplayTextDeep((string) $meta['note']);
		}
		$exportTypeLabel = self::decodeDisplayTextDeep((string) (isset($meta['exportTypeLabel']) ? $meta['exportTypeLabel'] : ''));
		if ($exportTypeLabel === '') {
			if ($outboundType === 'transfer') {
				$exportTypeLabel = 'Xuất chuyển kho';
			} elseif ($outboundType === 'scrap') {
				$exportTypeLabel = 'Xuất huỷ';
			} elseif ($outboundType === 'sale') {
				$exportTypeLabel = 'Xuất bán hàng';
			} else {
				$exportTypeLabel = 'Xuất dùng nội bộ';
			}
		}

		$toWarehouse = array('code' => '', 'name' => '');
		$toWarehouseId = trim((string) (isset($meta['toWarehouseId']) ? $meta['toWarehouseId'] : ''));
		if ($toWarehouseId !== '') {
			$toWh = self::findWarehouseRowByCode($db, $toWarehouseId);
			if ($toWh) {
				$toWarehouse = array(
					'code' => (string) (isset($toWh['code']) ? $toWh['code'] : $toWarehouseId),
					'name' => self::decodeDisplayTextDeep((string) (isset($toWh['name']) ? $toWh['name'] : '')),
				);
			} else {
				$toWarehouse = array('code' => $toWarehouseId, 'name' => $customer);
			}
		} elseif ($outboundType === 'transfer' && $customer !== '') {
			$toWarehouse = array('code' => '', 'name' => $customer);
		}

		foreach ($items as &$item) {
			if (!isset($item['note']) || trim((string) $item['note']) === '') {
				$lot = trim((string) (isset($item['lot']) ? $item['lot'] : ''));
				$item['note'] = $lot !== '' ? ('Lô ' . $lot) : '';
			}
		}
		unset($item);

		// Ghi chú in phiếu: chỉ dùng notes người dùng nhập, không lấy reason mặc định.
		$printNotes = $notes;

		return array(
			'issue' => array(
				'id' => (string) $row['code'],
				'salesorderId' => $salesOrderId,
				'receiver' => $customer,
				'receiverAddress' => $receiverAddress,
				'receiverPerson' => self::decodeDisplayTextDeep((string) (isset($meta['receiverPerson']) ? $meta['receiverPerson'] : '')),
				'reason' => $reason,
				'notes' => $printNotes,
				'receiveNotes' => self::decodeDisplayTextDeep((string) (isset($meta['receiveNotes']) ? $meta['receiveNotes'] : '')),
				'exportTypeLabel' => $exportTypeLabel,
				'soRef' => $soRef,
				'outboundType' => $outboundType,
				'createdAt' => $created,
				'createdBy' => self::decodeDisplayTextDeep((string) (isset($meta['createdBy']) ? $meta['createdBy'] : '')),
				'status' => (string) (isset($row['status']) ? $row['status'] : ''),
				'lines' => $items,
			),
			'warehouse' => array(
				'code' => (string) (isset($wh['code']) ? $wh['code'] : $warehouseCode),
				'name' => self::decodeDisplayTextDeep((string) (isset($wh['name']) ? $wh['name'] : '')),
				'address' => self::decodeDisplayTextDeep((string) (isset($wh['address']) ? $wh['address'] : '')),
				'manager' => self::decodeDisplayTextDeep((string) (isset($wh['manager']) ? $wh['manager'] : '')),
			),
			'toWarehouse' => $toWarehouse,
			'branch' => 'Chi nhánh trung tâm',
			'toBranch' => 'Chi nhánh trung tâm',
			'company' => $company,
			'companyAddress' => $companyAddress,
			'companyPhone' => $companyPhone,
		);
	}

	const QC_MAX_IMAGES = 10;
	const QC_MAX_IMAGE_BYTES = 5242880;
	const QC_ALLOWED_IMAGE_EXT = array('jpg', 'jpeg', 'png', 'webp', 'gif');

	public static function buildQcImagePublicUrl($warehouseCode, $receiptCode, $imageId) {
		return 'index.php?module=Warehouse&action=WhQcImage&whId=' . rawurlencode((string) $warehouseCode)
			. '&code=' . rawurlencode((string) $receiptCode)
			. '&imageId=' . rawurlencode((string) $imageId);
	}

	protected static function qcImagesBaseDir($warehouseCode, $receiptCode) {
		$safeWh = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $warehouseCode);
		$safeCode = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $receiptCode);
		return 'storage/mk_qc/' . $safeWh . '/' . $safeCode . '/';
	}

	protected static function normalizeQcImagesList(array $images) {
		$out = array();
		foreach ($images as $img) {
			if (!is_array($img)) {
				continue;
			}
			$id = trim((string) (isset($img['id']) ? $img['id'] : ''));
			if ($id === '') {
				continue;
			}
			$out[] = array(
				'id' => $id,
				'name' => (string) (isset($img['name']) ? $img['name'] : ''),
				'url' => (string) (isset($img['url']) ? $img['url'] : ''),
				'mime' => (string) (isset($img['mime']) ? $img['mime'] : ''),
				'size' => (int) (isset($img['size']) ? $img['size'] : 0),
				'at' => (string) (isset($img['at']) ? $img['at'] : ''),
				'by' => (string) (isset($img['by']) ? $img['by'] : ''),
			);
		}
		return $out;
	}

	protected static function ensureQcMetaBucket(array &$meta) {
		if (!isset($meta['qc']) || !is_array($meta['qc'])) {
			$meta['qc'] = array();
		}
		if (!isset($meta['qc']['images']) || !is_array($meta['qc']['images'])) {
			$meta['qc']['images'] = array();
		}
	}

	protected static function syncQcImagesToLatestTimeline(array &$meta) {
		self::ensureQcMetaBucket($meta);
		$images = self::normalizeQcImagesList($meta['qc']['images']);
		if (!isset($meta['timeline']) || !is_array($meta['timeline'])) {
			return;
		}
		for ($i = count($meta['timeline']) - 1; $i >= 0; $i--) {
			if (!is_array($meta['timeline'][$i])) {
				continue;
			}
			if ((isset($meta['timeline'][$i]['role']) ? $meta['timeline'][$i]['role'] : '') !== 'qc') {
				continue;
			}
			$meta['timeline'][$i]['images'] = $images;
			return;
		}
	}

	protected static function syncQcNoteToLatestTimeline(array &$meta, $note) {
		$note = trim((string) $note);
		if ($note === '' || !isset($meta['timeline']) || !is_array($meta['timeline'])) {
			return;
		}
		for ($i = count($meta['timeline']) - 1; $i >= 0; $i--) {
			if (!is_array($meta['timeline'][$i])) {
				continue;
			}
			if ((isset($meta['timeline'][$i]['role']) ? $meta['timeline'][$i]['role'] : '') !== 'qc') {
				continue;
			}
			$meta['timeline'][$i]['note'] = $note;
			return;
		}
	}

	protected static function assertReceiptQcEditable($status) {
		$status = strtolower(trim((string) $status));
		if (!in_array($status, array('pending_qc', 'qc_passed', 'qc_failed'), true)) {
			throw new Exception('Phiếu nhập không ở trạng thái cho phép ghi nhận QC.');
		}
	}

	protected static function currentUserDisplayName() {
		global $current_user;
		if (!empty($current_user) && !empty($current_user->user_name)) {
			$first = trim((string) ($current_user->first_name ?? ''));
			$last = trim((string) ($current_user->last_name ?? ''));
			$full = trim($first . ' ' . $last);
			if ($full !== '') {
				return $full;
			}
			return (string) $current_user->user_name;
		}
		return 'QC';
	}

	protected static function hydrateQcImageUrls(array &$meta, $warehouseCode, $receiptCode) {
		self::ensureQcMetaBucket($meta);
		$list = array();
		foreach ($meta['qc']['images'] as $img) {
			if (!is_array($img)) {
				continue;
			}
			$id = trim((string) (isset($img['id']) ? $img['id'] : ''));
			if ($id === '') {
				continue;
			}
			if (empty($img['url'])) {
				$img['url'] = self::buildQcImagePublicUrl($warehouseCode, $receiptCode, $id);
			}
			$list[] = $img;
		}
		$meta['qc']['images'] = self::normalizeQcImagesList($list);
	}

	public static function resolveQcImageFile($warehouseCode, $receiptCode, $imageId) {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		$warehouseCode = trim((string) $warehouseCode);
		$receiptCode = trim((string) $receiptCode);
		$imageId = trim((string) $imageId);
		if ($warehouseCode === '' || $receiptCode === '' || $imageId === '') {
			return null;
		}
		if (!preg_match('/^[a-f0-9]{16,64}$/i', $imageId)) {
			return null;
		}
		$row = self::findReceiptRowByCode($db, $receiptCode, $warehouseCode);
		if (!$row) {
			return null;
		}
		$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');
		self::ensureQcMetaBucket($meta);
		$found = null;
		foreach ($meta['qc']['images'] as $img) {
			if (!is_array($img)) {
				continue;
			}
			if ((isset($img['id']) ? (string) $img['id'] : '') === $imageId) {
				$found = $img;
				break;
			}
		}
		if (!$found) {
			return null;
		}
		$base = self::qcImagesBaseDir($warehouseCode, $receiptCode);
		$ext = strtolower(pathinfo((string) (isset($found['name']) ? $found['name'] : ''), PATHINFO_EXTENSION));
		if ($ext === '' || !in_array($ext, self::QC_ALLOWED_IMAGE_EXT, true)) {
			$ext = 'jpg';
		}
		$path = $base . $imageId . '.' . $ext;
		if (!is_readable($path)) {
			foreach (self::QC_ALLOWED_IMAGE_EXT as $tryExt) {
				$try = $base . $imageId . '.' . $tryExt;
				if (is_readable($try)) {
					$path = $try;
					break;
				}
			}
		}
		return array(
			'path' => $path,
			'mime' => (string) (isset($found['mime']) ? $found['mime'] : 'image/jpeg'),
		);
	}

	public static function uploadQcImage($warehouseCode, $receiptCode, array $fileInfo, $userId = 0, $role = 'qc') {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		$warehouseCode = trim((string) $warehouseCode);
		$receiptCode = trim((string) $receiptCode);
		if ($warehouseCode === '' || $receiptCode === '') {
			throw new Exception('Thiếu thông tin phiếu nhập.');
		}
		$row = self::findReceiptRowByCode($db, $receiptCode, $warehouseCode);
		if (!$row) {
			throw new Exception('Không tìm thấy phiếu nhập.');
		}
		$status = (string) (isset($row['status']) ? $row['status'] : '');
		self::assertReceiptQcEditable($status);

		$origName = trim((string) (isset($fileInfo['name']) ? $fileInfo['name'] : ''));
		$tmpPath = trim((string) (isset($fileInfo['tmp_name']) ? $fileInfo['tmp_name'] : ''));
		$errorCode = (int) (isset($fileInfo['error']) ? $fileInfo['error'] : UPLOAD_ERR_NO_FILE);
		$fileSize = (int) (isset($fileInfo['size']) ? $fileInfo['size'] : 0);
		$fileType = trim((string) (isset($fileInfo['type']) ? $fileInfo['type'] : ''));

		if ($origName === '' || $errorCode === UPLOAD_ERR_NO_FILE) {
			throw new Exception('Không có file ảnh.');
		}
		if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($tmpPath)) {
			throw new Exception('Upload ảnh thất bại.');
		}
		if ($fileSize <= 0 || $fileSize > self::QC_MAX_IMAGE_BYTES) {
			throw new Exception('Ảnh vượt quá 5MB hoặc file rỗng.');
		}

		$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
		if (!in_array($ext, self::QC_ALLOWED_IMAGE_EXT, true)) {
			throw new Exception('Chỉ hỗ trợ ảnh JPG, PNG, WEBP, GIF.');
		}

		$receiptId = (int) $row['receiptid'];
		$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');
		self::ensureQcMetaBucket($meta);
		$images = self::normalizeQcImagesList($meta['qc']['images']);
		if (count($images) >= self::QC_MAX_IMAGES) {
			throw new Exception('Đã đạt tối đa ' . self::QC_MAX_IMAGES . ' ảnh cho phiếu này.');
		}

		$imageId = bin2hex(random_bytes(12));
		$baseDir = self::qcImagesBaseDir($warehouseCode, $receiptCode);
		if (!is_dir($baseDir)) {
			@mkdir($baseDir, 0775, true);
		}
		$targetPath = $baseDir . $imageId . '.' . $ext;
		if (!@move_uploaded_file($tmpPath, $targetPath)) {
			throw new Exception('Không lưu được ảnh lên máy chủ.');
		}

		$by = self::currentUserDisplayName();
		if ($role !== '') {
			$roleName = self::roleDisplayName($role);
			if ($roleName !== 'QL Tuấn') {
				$by = $roleName;
			}
		}
		$entry = array(
			'id' => $imageId,
			'name' => $origName,
			'url' => self::buildQcImagePublicUrl($warehouseCode, $receiptCode, $imageId),
			'mime' => $fileType !== '' ? $fileType : 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext),
			'size' => $fileSize,
			'at' => self::nowIso(),
			'by' => $by,
		);
		$images[] = $entry;
		$meta['qc']['images'] = $images;
		self::syncQcImagesToLatestTimeline($meta);

		$db->pquery(
			'UPDATE vtiger_goodsreceipt SET mk_meta_json = ?, updatedby = ?, updatedtime = ? WHERE receiptid = ?',
			array(self::encodeMeta($meta), (int) $userId, self::nowSql(), $receiptId)
		);

		return array(
			'image' => $entry,
			'code' => $receiptCode,
			'warehouse' => $warehouseCode,
			'data' => self::getWarehouseData($db, $warehouseCode),
		);
	}

	public static function deleteQcImage($warehouseCode, $receiptCode, $imageId, $userId = 0) {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		$warehouseCode = trim((string) $warehouseCode);
		$receiptCode = trim((string) $receiptCode);
		$imageId = trim((string) $imageId);
		if ($warehouseCode === '' || $receiptCode === '' || $imageId === '') {
			throw new Exception('Thiếu thông tin xóa ảnh.');
		}
		$row = self::findReceiptRowByCode($db, $receiptCode, $warehouseCode);
		if (!$row) {
			throw new Exception('Không tìm thấy phiếu nhập.');
		}
		self::assertReceiptQcEditable((string) (isset($row['status']) ? $row['status'] : ''));

		$receiptId = (int) $row['receiptid'];
		$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');
		self::ensureQcMetaBucket($meta);
		$images = self::normalizeQcImagesList($meta['qc']['images']);
		$kept = array();
		$removed = null;
		foreach ($images as $img) {
			if ($img['id'] === $imageId) {
				$removed = $img;
				continue;
			}
			$kept[] = $img;
		}
		if (!$removed) {
			throw new Exception('Không tìm thấy ảnh.');
		}
		$fileInfo = self::resolveQcImageFile($warehouseCode, $receiptCode, $imageId);
		if ($fileInfo && !empty($fileInfo['path']) && is_file($fileInfo['path'])) {
			@unlink($fileInfo['path']);
		}

		$meta['qc']['images'] = $kept;
		self::syncQcImagesToLatestTimeline($meta);
		$db->pquery(
			'UPDATE vtiger_goodsreceipt SET mk_meta_json = ?, updatedby = ?, updatedtime = ? WHERE receiptid = ?',
			array(self::encodeMeta($meta), (int) $userId, self::nowSql(), $receiptId)
		);

		return array(
			'code' => $receiptCode,
			'warehouse' => $warehouseCode,
			'data' => self::getWarehouseData($db, $warehouseCode),
		);
	}

	public static function updateQcRecord($warehouseCode, $receiptCode, $note, $userId = 0, $role = 'qc') {
		$db = PearDatabase::getInstance();
		self::ensureInstalled();
		$warehouseCode = trim((string) $warehouseCode);
		$receiptCode = trim((string) $receiptCode);
		$note = trim((string) $note);
		if ($warehouseCode === '' || $receiptCode === '') {
			throw new Exception('Thiếu thông tin phiếu nhập.');
		}
		$row = self::findReceiptRowByCode($db, $receiptCode, $warehouseCode);
		if (!$row) {
			throw new Exception('Không tìm thấy phiếu nhập.');
		}
		$status = (string) (isset($row['status']) ? $row['status'] : '');
		self::assertReceiptQcEditable($status);

		$receiptId = (int) $row['receiptid'];
		$meta = self::decodeMeta(isset($row['mk_meta_json']) ? $row['mk_meta_json'] : '');
		self::ensureQcMetaBucket($meta);
		if (!empty($meta['qc']['result'])) {
			$meta['qc']['note'] = $note;
		} else {
			$meta['qc']['note'] = $note;
		}
		$meta['qc']['updatedAt'] = self::nowIso();
		$meta['qc']['updatedBy'] = self::currentUserDisplayName();
		self::syncQcNoteToLatestTimeline($meta, $note);
		self::syncQcImagesToLatestTimeline($meta);

		$db->pquery(
			'UPDATE vtiger_goodsreceipt SET mk_meta_json = ?, note = ?, updatedby = ?, updatedtime = ? WHERE receiptid = ?',
			array(self::encodeMeta($meta), $note, (int) $userId, self::nowSql(), $receiptId)
		);

		return array(
			'code' => $receiptCode,
			'warehouse' => $warehouseCode,
			'data' => self::getWarehouseData($db, $warehouseCode),
		);
	}
}

?>
