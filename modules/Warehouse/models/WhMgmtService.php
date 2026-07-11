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
		if (!Warehouse_WorkflowSetup_Helper::isInstalled($db)) {
			self::seedAll($db);
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

		foreach (Warehouse_WhMgmtSeedData::stockByWarehouse() as $whCode => $rows) {
			$wh = self::findWarehouseRowByCode($db, $whCode);
			if (!$wh) {
				continue;
			}
			$whName = (string) $wh['name'];
			foreach ($rows as $item) {
				$key = self::stockProductKey($whCode, $item['sku'], $item['lot']);
				$exists = $db->pquery(
					'SELECT stockid FROM vtiger_warehouse_stock WHERE product_key = ? LIMIT 1',
					array($key)
				);
				if ($exists && $db->num_rows($exists) > 0) {
					continue;
				}
				$stockId = (int) $db->getUniqueID('vtiger_warehouse_stock');
				$code = 'STK-' . str_pad((string) $stockId, 4, '0', STR_PAD_LEFT);
				$now = date('Y-m-d H:i:s');
				$db->pquery(
					'INSERT INTO vtiger_warehouse_stock
					 (stockid, code, product_key, productid, product_name, quantity, last_price,
					  warehouse_id, warehouse_name, expired_date, createdtime, updatedtime)
					 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
					array(
						$stockId,
						$code,
						$key,
						0,
						$item['name'],
						(float) $item['qty'],
						(float) (isset($item['price']) ? $item['price'] : 0),
						$whCode,
						$whName,
						isset($item['expiry']) ? $item['expiry'] : null,
						$now,
						$now,
					)
				);
			}
		}
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
		return (bool) preg_match('/^PS-\d+$/i', trim((string) $sku));
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
		);
	}

	protected static function decodeMeta($json) {
		if ($json === null || $json === '') {
			return array();
		}
		$decoded = json_decode((string) $json, true);
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
		return 'Thủ kho Hà';
	}

	protected static function pushTimeline(array &$meta, $action, $role, $note = '') {
		if (!isset($meta['timeline']) || !is_array($meta['timeline'])) {
			$meta['timeline'] = array();
		}
		$entry = array(
			'at' => self::nowIso(),
			'by' => self::roleDisplayName($role),
			'role' => strtolower(trim((string) $role)) !== '' ? strtolower(trim((string) $role)) : 'keeper',
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
			'SELECT productid, product_name, quantity, serial_number, expired_date, line_note, storage_location
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
		$rs = $db->pquery(
			'SELECT product_name, quantity, serial_number, expired_date, line_note, storage_location
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
			$line = array(
				'sku' => $sku,
				'name' => self::decodeDisplayTextDeep((string) $row['product_name']),
				'lot' => (string) (isset($row['serial_number']) ? $row['serial_number'] : ''),
				'expiry' => (string) (isset($row['expired_date']) ? $row['expired_date'] : ''),
				'qty' => (float) $row['quantity'],
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

	public static function applyReceiptAction($warehouseCode, $receiptCode, $actionKey, $role, $note = '', $userId = 0) {
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
			$meta['qc'] = array(
				'result' => 'pass',
				'note' => $note,
				'at' => self::nowIso(),
				'by' => self::roleDisplayName($role),
			);
			self::pushTimeline($meta, 'QC đạt', 'qc', $note);
			$goodsReceiptNote = $note;
		} else if ($actionKey === 'qc-fail') {
			$newStatus = 'qc_failed';
			$note = trim((string) $note);
			$meta['qc'] = array(
				'result' => 'fail',
				'note' => $note,
				'at' => self::nowIso(),
				'by' => self::roleDisplayName($role),
			);
			self::pushTimeline($meta, 'QC không đạt', 'qc', $note);
			$goodsReceiptNote = $note;
		} else if ($actionKey === 'mgr-approve') {
			$newStatus = 'approved';
			self::pushTimeline($meta, 'Duyệt phiếu', $role, '');
		} else if ($actionKey === 'store') {
			$newStatus = 'stored';
			$items = self::loadReceiptItemsRaw($db, $receiptId);
			foreach ($items as $it) {
				$name = (string) (isset($it['product_name']) ? $it['product_name'] : '');
				$lot = (string) (isset($it['serial_number']) ? $it['serial_number'] : '');
				$sku = (string) (isset($it['line_note']) ? $it['line_note'] : '');
				$productId = (int) (isset($it['productid']) ? $it['productid'] : 0);
				$qty = (float) (isset($it['quantity']) ? $it['quantity'] : 0);
				$expiry = isset($it['expired_date']) && $it['expired_date'] !== '' ? $it['expired_date'] : null;
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
					'expiry' => $expiry,
					'price' => $price,
					'location' => $location,
				), $userId);
			}
			$locationNote = self::formatReceiptLocationNote($items);
			self::pushTimeline($meta, 'Đã nhập kho', $role, $locationNote);
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
			$name = trim((string) (isset($it['product_name']) ? $it['product_name'] : ''));
			$lot = trim((string) (isset($it['serial_number']) ? $it['serial_number'] : ''));
			$sku = trim((string) (isset($it['line_note']) ? $it['line_note'] : ''));
			$productId = (int) (isset($it['productid']) ? $it['productid'] : 0);
			if ($sku === '' && $productId > 0) {
				$sku = self::resolveProductSku($db, $productId);
			}

			if ($sku === '' || $lot === '') {
				// Fall through to productId / name matching when SKU or lot is missing.
			} else {
				$key = self::stockProductKey($warehouseCode, $sku, $lot);
				$rs = $db->pquery(
					'SELECT stockid, quantity FROM vtiger_warehouse_stock WHERE product_key = ? LIMIT 1',
					array($key)
				);
				if ($rs && $db->num_rows($rs) > 0) {
					$stockId = (int) $db->query_result($rs, 0, 'stockid');
					$current = (float) $db->query_result($rs, 0, 'quantity');
					$deduct = min($current, $qtyNeeded);
					if ($deduct > 0) {
						$db->pquery(
							'UPDATE vtiger_warehouse_stock SET quantity = ?, updatedby = ?, updatedtime = ? WHERE stockid = ?',
							array(max(0, $current - $deduct), (int) $userId, $now, $stockId)
						);
						$qtyNeeded -= $deduct;
					}
				}
				continue;
			}

			$rows = array();
			if ($productId > 0) {
				$rs = $db->pquery(
					'SELECT stockid, quantity FROM vtiger_warehouse_stock
					 WHERE warehouse_id = ? AND productid = ? AND quantity > 0
					 ORDER BY expired_date ASC, stockid ASC',
					array($warehouseCode, $productId)
				);
				while ($row = $db->fetchByAssoc($rs)) {
					$rows[] = $row;
				}
			}
			if (empty($rows) && $name !== '') {
				$decodedName = Warehouse_Stock_Helper::decodeDisplayText($name);
				$rs = $db->pquery(
					'SELECT stockid, quantity, product_name FROM vtiger_warehouse_stock
					 WHERE warehouse_id = ? AND quantity > 0
					 ORDER BY expired_date ASC, stockid ASC',
					array($warehouseCode)
				);
				while ($row = $db->fetchByAssoc($rs)) {
					$rowName = Warehouse_Stock_Helper::decodeDisplayText(isset($row['product_name']) ? $row['product_name'] : '');
					if (mb_strtolower($rowName) === mb_strtolower($decodedName)) {
						$rows[] = $row;
					}
				}
			}
			foreach ($rows as $row) {
				if ($qtyNeeded <= 0) {
					break;
				}
				$stockId = (int) $row['stockid'];
				$current = (float) $row['quantity'];
				$deduct = min($current, $qtyNeeded);
				if ($deduct <= 0) {
					continue;
				}
				$db->pquery(
					'UPDATE vtiger_warehouse_stock SET quantity = ?, updatedby = ?, updatedtime = ? WHERE stockid = ?',
					array(max(0, $current - $deduct), (int) $userId, $now, $stockId)
				);
				$qtyNeeded -= $deduct;
			}
		}
		$meta['stockDeducted'] = true;
	}

	public static function applyIssueAction($warehouseCode, $issueCode, $actionKey, $role, $note = '', $userId = 0) {
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
				throw new Exception('Phiếu không ở trạng thái chờ in.');
			}
			$newDbStatus = 'picking';
			self::pushTimeline($meta, 'Bắt đầu soạn hàng', $role, '');
		} else if ($actionKey === 'issue-finish-pick') {
			if ($dbStatus !== 'picking') {
				throw new Exception('Phiếu không ở trạng thái đang soạn.');
			}
			$newDbStatus = 'packed';
			self::deductStockForIssue($db, $warehouseCode, $issueId, $userId, $meta);
			self::pushTimeline($meta, 'Đã soạn hàng', $role, '');
		} else if ($actionKey === 'issue-ship') {
			if (!in_array($dbStatus, array('packed', 'approved'), true)) {
				throw new Exception('Phiếu chưa hoàn tất soạn hàng.');
			}
			$newDbStatus = 'shipped';
			self::pushTimeline($meta, 'Đã giao hàng', $role, '');
		} else if ($actionKey === 'issue-submit') {
			$newDbStatus = 'waiting_print';
			self::pushTimeline($meta, 'Chờ in phiếu', $role, '');
		} else if ($actionKey === 'issue-approve') {
			$newDbStatus = 'picking';
			self::pushTimeline($meta, 'Bắt đầu soạn hàng', $role, '');
		} else if ($actionKey === 'issue-reject') {
			$newDbStatus = 'rejected';
			self::pushTimeline($meta, 'Từ chối phiếu', $role, (string) ($note !== '' ? $note : 'Không nêu lý do'));
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
				'createdAt' => $created !== '' ? gmdate('c', strtotime($created)) : gmdate('c'),
				'createdBy' => (string) (isset($meta['createdBy']) ? $meta['createdBy'] : ''),
				'status' => $uiStatus,
				'lines' => $items,
				'timeline' => isset($meta['timeline']) && is_array($meta['timeline']) ? $meta['timeline'] : array(),
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
			'SELECT product_name, quantity, serial_number, line_note
			 FROM vtiger_goodsissue_items
			 WHERE issueid = ?
			 ORDER BY itemid ASC',
			array($issueId)
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$sku = trim((string) (isset($row['line_note']) ? $row['line_note'] : ''));
			$sku = self::formatDisplaySku($sku !== '' ? $sku : self::guessSkuFromName($row['product_name']));
			$out[] = array(
				'sku' => $sku,
				'name' => self::decodeDisplayTextDeep((string) $row['product_name']),
				'lot' => (string) (isset($row['serial_number']) ? $row['serial_number'] : ''),
				'qty' => (float) $row['quantity'],
			);
		}
		return $out;
	}

	protected static function loadStock(PearDatabase $db, $warehouseCode) {
		$rs = $db->pquery(
			'SELECT product_key, product_name, quantity, last_price, expired_date, warehouse_name, storage_location
			 FROM vtiger_warehouse_stock
			 WHERE warehouse_id = ?
			 ORDER BY stockid ASC',
			array($warehouseCode)
		);
		$out = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$key = (string) $row['product_key'];
			$parts = explode('|', $key);
			$sku = count($parts) >= 2 ? $parts[1] : self::guessSkuFromName($row['product_name']);
			$sku = self::formatDisplaySku($sku);
			$lot = count($parts) >= 3 ? $parts[2] : '';
			$out[] = array(
				'sku' => $sku,
				'name' => self::decodeDisplayTextDeep((string) $row['product_name']),
				'lot' => $lot,
				'expiry' => (string) (isset($row['expired_date']) ? $row['expired_date'] : ''),
				'qty' => (float) $row['quantity'],
				'location' => self::decodeDisplayTextDeep((string) (isset($row['storage_location']) ? $row['storage_location'] : '')),
				'price' => (float) (isset($row['last_price']) ? $row['last_price'] : 0),
			);
		}
		return $out;
	}

	protected static function guessSkuFromName($name) {
		$name = trim((string) $name);
		if ($name === '') {
			return 'SKU';
		}
		return 'SKU-' . substr(md5(mb_strtolower($name)), 0, 6);
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
		$rs = $db->pquery(
			'SELECT ps.productsservicesid, ps.productsservicesname, ps.price, ps.item_type, ps.sku
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
			$out[] = array(
				'id' => $id,
				'name' => $name,
				'price' => (float) (isset($row['price']) ? $row['price'] : 0),
				'type' => (string) (isset($row['item_type']) ? $row['item_type'] : ''),
				'sku' => $sku,
			);
		}
		return $out;
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
	 * Persist inbound receipt + optional stock (when not sent to QC).
	 *
	 * @param array $payload supplier, poRef, sendQc, lines[{product_id,sku,name,lot,qty,mfg,expiry}]
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
		$sendQc = !empty($payload['sendQc']);
		$lines = isset($payload['lines']) && is_array($payload['lines']) ? $payload['lines'] : array();

		if ($supplier === '' || $poRef === '' || empty($lines)) {
			throw new Exception('Thiếu thông tin phiếu nhập.');
		}

		$now = date('Y-m-d H:i:s');
		$code = self::nextGrnCode($db);
		$status = $sendQc ? 'pending_qc' : 'stored';
		$timeline = array(
			array('at' => gmdate('c'), 'by' => 'Thủ kho', 'role' => 'keeper', 'action' => 'Tạo phiếu nhập'),
		);
		if ($sendQc) {
			$timeline[] = array('at' => gmdate('c'), 'by' => 'Thủ kho', 'role' => 'keeper', 'action' => 'Gửi QC kiểm tra');
		} else {
			$timeline[] = array('at' => gmdate('c'), 'by' => 'Thủ kho', 'role' => 'keeper', 'action' => 'Nhập thẳng tồn kho');
		}

		$meta = json_encode(array(
			'poRef' => $poRef,
			'createdBy' => 'Thủ kho',
			'timeline' => $timeline,
		), JSON_UNESCAPED_UNICODE);

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
				$meta,
				$userId,
				$userId,
				$now,
				$now,
			)
		);

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
			if ($name === '' || $lot === '' || $qty <= 0) {
				continue;
			}
			if ($productId > 0 && $sku === '') {
				throw new Exception('Sản phẩm "' . $name . '" chưa có SKU. Hãy cập nhật SKU trong Products & Services trước khi nhập kho.');
			}

			$itemId = (int) $db->getUniqueID('vtiger_goodsreceipt_items');
			$db->pquery(
				'INSERT INTO vtiger_goodsreceipt_items
				 (itemid, receiptid, productid, product_name, quantity, serial_number, expired_date, line_note, storage_location)
				 VALUES (?,?,?,?,?,?,?,?,?)',
				array($itemId, $receiptId, $productId, $name, $qty, $lot, $expiry, $sku, $location !== '' ? $location : null)
			);

			if (!$sendQc) {
				self::applyInboundStockLine($db, $warehouseCode, $whName, array(
					'product_id' => $productId,
					'sku' => $sku,
					'name' => $name,
					'lot' => $lot,
					'qty' => $qty,
					'expiry' => $expiry,
					'price' => isset($line['price']) ? (float) $line['price'] : 0,
					'location' => $location,
				), $userId);
			}
		}

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
					$line['expiry'],
					$line['expiry'],
					$line['expiry'],
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
			  warehouse_id, warehouse_name, expired_date, storage_location, createdtime, updatedtime, updatedby)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
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
				$line['expiry'],
				$location !== '' ? $location : null,
				$now,
				$now,
				$userId,
			)
		);
	}
}

?>
