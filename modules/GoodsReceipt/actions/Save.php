<?php
class GoodsReceipt_Save_Action extends Vtiger_Action_Controller {
	protected $allowedExtensions = array('jpg','jpeg','png','webp','pdf','doc','docx','xls','xlsx','csv','txt');
	protected $typeCacheByProductId = array();
	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }
	public function validateRequest(Vtiger_Request $request) { return; }

	protected function parseItems(Vtiger_Request $request) {
		$items = array();
		$names = $request->get('item_product_name');
		$ids = $request->get('item_productid');
		$qtys = $request->get('item_quantity');
		$prices = $request->get('item_unit_price');
		$notes = $request->get('item_line_note');
		$descs = $request->get('description');
		$types = $request->get('item_product_type');
		$serials = $request->get('item_serial');

		if (!is_array($names)) {
			return array();
		}
		$count = count($names);
		for ($i = 0; $i < $count; $i++) {
			$name = trim((string) $names[$i]);
			$qty = (float) (isset($qtys[$i]) ? $qtys[$i] : 0);
			$price = (float) (isset($prices[$i]) ? $prices[$i] : 0);
			$productId = (int) (isset($ids[$i]) ? $ids[$i] : 0);
			$note = isset($notes[$i]) ? (string) $notes[$i] : '';
			$desc = is_array($descs) && isset($descs[$i]) ? (string) $descs[$i] : '';
			$rawType = is_array($types) && isset($types[$i]) ? (string) $types[$i] : '';
			$serial = is_array($serials) && isset($serials[$i]) ? (string) $serials[$i] : '';
			if ($name === '' || $qty <= 0) {
				continue;
			}
			$note = vtlib_purify($note);
			$desc = vtlib_purify($desc);
			$items[] = array(
				'productid' => $productId > 0 ? $productId : null,
				'product_name' => $name,
				'product_type' => $this->resolveProductType($productId, $rawType),
				'quantity' => $qty,
				'unit_price' => $price,
				'description' => $desc,
				'line_note' => $note,
				'serial_number' => $serial,
			);
		}
		return $items;
	}

	protected function itemKey(array $item) {
		if (!empty($item['productid'])) {
			return 'P:' . (int) $item['productid'];
		}
		return 'N:' . mb_strtolower(trim((string) $item['product_name']));
	}

	protected function aggregateByProduct(array $items) {
		$agg = array();
		foreach ($items as $item) {
			$key = $this->itemKey($item);
			if (!isset($agg[$key])) {
				$agg[$key] = array(
					'product_key' => $key,
					'productid' => !empty($item['productid']) ? (int) $item['productid'] : null,
					'product_name' => (string) $item['product_name'],
					'product_type' => isset($item['product_type']) ? (string) $item['product_type'] : null,
					'quantity' => 0.0,
					'last_price' => 0.0,
				);
			}
			$agg[$key]['quantity'] += (float) $item['quantity'];
			$agg[$key]['last_price'] = (float) $item['unit_price'];
			if (!empty($item['product_type'])) {
				$agg[$key]['product_type'] = (string) $item['product_type'];
			}
		}
		return $agg;
	}

	/**
	 * Validation guard for stock-calculation input only.
	 * Keeps save flow stable by silently skipping invalid stock lines.
	 */
	protected function sanitizeStockItems(array $items) {
		$valid = array();
		foreach ($items as $item) {
			$qty = isset($item['quantity']) ? (float) $item['quantity'] : 0.0;
			$name = trim((string) (isset($item['product_name']) ? $item['product_name'] : ''));
			if ($qty <= 0 || $name === '') {
				// Soft warning only; do not break save flow.
				error_log('[GoodsReceipt] Skip invalid stock line (qty<=0 or empty name)');
				continue;
			}
			$valid[] = $item;
		}
		return $valid;
	}

	protected function normalizeTypeLabel($value) {
		$v = strtolower(trim((string) $value));
		if ($v === '') return null;
		if (in_array($v, array('hardware','product','products'), true)) return 'Hardware';
		if (in_array($v, array('software'), true)) return 'Software';
		if (in_array($v, array('service','services'), true)) return 'Service';
		return 'Other';
	}

	protected function resolveProductType($productId, $rawType) {
		$normalized = $this->normalizeTypeLabel($rawType);
		if (!empty($normalized)) {
			return $normalized;
		}
		$productId = (int) $productId;
		if ($productId <= 0) {
			return 'Other';
		}
		if (isset($this->typeCacheByProductId[$productId])) {
			return $this->typeCacheByProductId[$productId];
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			"SELECT item_type FROM vtiger_productsservices WHERE productsservicesid = ?",
			array($productId)
		);
		$type = 'Other';
		if ($db->num_rows($rs) > 0) {
			$type = $this->normalizeTypeLabel($db->query_result($rs, 0, 'item_type'));
			if (empty($type)) {
				$type = 'Other';
			}
		}
		$this->typeCacheByProductId[$productId] = $type;
		return $type;
	}

	protected function generateInboundCode(PearDatabase $db, $receivedDate) {
		$day = trim((string) $receivedDate);
		if ($day === '') {
			$day = date('Y-m-d');
		}
		$dayKey = str_replace('-', '', $day);
		$prefix = 'INB-' . $dayKey . '-';
		$rs = $db->pquery(
			"SELECT MAX(CAST(SUBSTRING(code, 14) AS UNSIGNED)) AS max_seq
			 FROM vtiger_goodsreceipt
			 WHERE code LIKE ?",
			array($prefix . '%')
		);
		$maxSeq = 0;
		if ($db->num_rows($rs) > 0) {
			$maxSeq = (int) $db->query_result($rs, 0, 'max_seq');
		}
		$nextSeq = $maxSeq + 1;
		return 'INB-' . $dayKey . '-' . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
	}

	protected function ensureInboundCode(PearDatabase $db, $recordId, $receivedDate) {
		$rs = $db->pquery("SELECT code, received_date FROM vtiger_goodsreceipt WHERE receiptid = ? LIMIT 1", array((int) $recordId));
		if ($db->num_rows($rs) <= 0) {
			return '';
		}
		$existing = trim((string) $db->query_result($rs, 0, 'code'));
		if ($existing !== '') {
			return $existing;
		}
		$day = trim((string) $receivedDate);
		if ($day === '') {
			$day = trim((string) $db->query_result($rs, 0, 'received_date'));
		}
		$code = $this->generateInboundCode($db, $day);
		$db->pquery(
			"UPDATE vtiger_goodsreceipt SET code = ? WHERE receiptid = ? AND COALESCE(TRIM(code), '') = ''",
			array($code, (int) $recordId)
		);
		return $code;
	}

	protected function generateStorageCode(PearDatabase $db) {
		$rs = $db->pquery(
			"SELECT MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)) AS max_seq
			 FROM vtiger_warehouse_stock
			 WHERE code LIKE 'STK-%'",
			array()
		);
		$maxSeq = 0;
		if ($db->num_rows($rs) > 0) {
			$maxSeq = (int) $db->query_result($rs, 0, 'max_seq');
		}
		$nextSeq = $maxSeq + 1;
		return 'STK-' . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
	}

	public function applyStockDelta(PearDatabase $db, array $oldItems, array $newItems, $userId, $now, $storageLocation = '', $inboundNote = '') {
		$oldItems = $this->sanitizeStockItems($oldItems);
		$newItems = $this->sanitizeStockItems($newItems);
		$oldAgg = $this->aggregateByProduct($oldItems);
		$newAgg = $this->aggregateByProduct($newItems);
		$keys = array_unique(array_merge(array_keys($oldAgg), array_keys($newAgg)));
		$location = trim((string) $storageLocation);
		$note = trim((string) $inboundNote);

		foreach ($keys as $key) {
			$oldQty = isset($oldAgg[$key]) ? (float) $oldAgg[$key]['quantity'] : 0.0;
			$newQty = isset($newAgg[$key]) ? (float) $newAgg[$key]['quantity'] : 0.0;
			$delta = $newQty - $oldQty;
			if (abs($delta) < 0.00000001) {
				continue;
			}

			$stockRow = isset($newAgg[$key]) ? $newAgg[$key] : $oldAgg[$key];
			$productKey = trim((string) (isset($stockRow['product_key']) ? $stockRow['product_key'] : $key));
			$rowQty = isset($stockRow['quantity']) ? (float) $stockRow['quantity'] : 0.0;
			if ($productKey === '' || $rowQty <= 0) {
				error_log('[GoodsReceipt] Skip stock upsert (empty key or qty<=0)');
				continue;
			}
			$check = $db->pquery("SELECT stockid, quantity FROM vtiger_warehouse_stock WHERE product_key = ?", array($productKey));
			if ($db->num_rows($check) > 0) {
				$stockId = (int) $db->query_result($check, 0, 'stockid');
				$currentQty = (float) $db->query_result($check, 0, 'quantity');
				$nextQty = $currentQty + $delta;
				if ($nextQty < 0) $nextQty = 0;
				$db->pquery(
					"UPDATE vtiger_warehouse_stock
					 SET quantity = ?, product_name = ?, product_type = ?, last_price = ?,
					 	 storage_location = CASE WHEN ? <> '' THEN ? ELSE storage_location END,
					 	 inbound_note = CASE WHEN ? <> '' THEN ? ELSE inbound_note END,
					 	 updatedby = ?, updatedtime = ?
					 WHERE stockid = ?",
					array(
						$nextQty,
						$stockRow['product_name'],
						isset($stockRow['product_type']) ? $stockRow['product_type'] : null,
						(float) $stockRow['last_price'],
						$location,
						$location,
						$note,
						$note,
						$userId,
						$now,
						$stockId
					)
				);
			} else {
				// Re-check before insert to avoid duplicate rows when same key appears.
				$checkAgain = $db->pquery("SELECT stockid, quantity FROM vtiger_warehouse_stock WHERE product_key = ?", array($productKey));
				if ($db->num_rows($checkAgain) > 0) {
					$stockId = (int) $db->query_result($checkAgain, 0, 'stockid');
					$currentQty = (float) $db->query_result($checkAgain, 0, 'quantity');
					$nextQty = $currentQty + $delta;
					if ($nextQty < 0) $nextQty = 0;
					$db->pquery(
						"UPDATE vtiger_warehouse_stock
						 SET quantity = ?, product_name = ?, product_type = ?, last_price = ?,
						 	 storage_location = CASE WHEN ? <> '' THEN ? ELSE storage_location END,
						 	 inbound_note = CASE WHEN ? <> '' THEN ? ELSE inbound_note END,
						 	 updatedby = ?, updatedtime = ?
						 WHERE stockid = ?",
						array(
							$nextQty,
							$stockRow['product_name'],
							isset($stockRow['product_type']) ? $stockRow['product_type'] : null,
							(float) $stockRow['last_price'],
							$location,
							$location,
							$note,
							$note,
							$userId,
							$now,
							$stockId
						)
					);
				} else {
					$stockId = (int) $db->getUniqueID('vtiger_warehouse_stock');
					$qty = $delta > 0 ? $delta : 0;
					$code = $this->generateStorageCode($db);
					if ($qty <= 0) {
						error_log('[GoodsReceipt] Skip stock insert (delta qty<=0)');
						continue;
					}
					$db->pquery(
						"INSERT INTO vtiger_warehouse_stock(stockid, code, product_key, productid, product_name, product_type, quantity, last_price, storage_location, inbound_note, createdtime, updatedtime, updatedby)
						 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)",
						array(
							$stockId,
							$code,
							$productKey,
							$stockRow['productid'],
							$stockRow['product_name'],
							isset($stockRow['product_type']) ? $stockRow['product_type'] : null,
							$qty,
							(float) $stockRow['last_price'],
							$location,
							$note,
							$now,
							$now,
							$userId
						)
					);
				}
			}
		}
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';
		GoodsReceipt_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		$now = date('Y-m-d H:i:s');
		$recordId = (int) $request->get('record');
		$subject = trim((string) $request->get('subject'));
		$sourceName = trim((string) $request->get('source_name'));
		$receivedDate = trim((string) $request->get('received_date'));
		$storageLocation = trim((string) $request->get('storage_location'));
		$note = (string) $request->get('note');
		$items = $this->parseItems($request);

		if ($subject === '' || empty($items)) {
			header('Location: index.php?module=GoodsReceipt&view=Edit&app=INVENTORY&record=' . $recordId . '&validation=1');
			exit;
		}

		$oldItems = array();
		if ($recordId > 0) {
			$rsOld = $db->pquery("SELECT * FROM vtiger_goodsreceipt_items WHERE receiptid = ?", array($recordId));
			while ($r = $db->fetchByAssoc($rsOld)) {
				$oldItems[] = $r;
			}
		}

		if ($recordId > 0) {
			$db->pquery(
				"UPDATE vtiger_goodsreceipt SET subject=?, source_name=?, received_date=?, storage_location=?, note=?, updatedby=?, updatedtime=? WHERE receiptid=?",
				array($subject, $sourceName, $receivedDate, $storageLocation, $note, $userId, $now, $recordId)
			);
			$this->ensureInboundCode($db, $recordId, $receivedDate);
			$db->pquery("DELETE FROM vtiger_goodsreceipt_items WHERE receiptid = ?", array($recordId));
		} else {
			$recordId = (int) $db->getUniqueID('vtiger_goodsreceipt');
			$code = $this->generateInboundCode($db, $receivedDate);
			$db->pquery(
				"INSERT INTO vtiger_goodsreceipt(receiptid, code, subject, source_name, received_date, storage_location, note, createdby, updatedby, createdtime, updatedtime, deleted)
				 VALUES(?,?,?,?,?,?,?,?,?,?,?,0)",
				array($recordId, $code, $subject, $sourceName, $receivedDate, $storageLocation, $note, $userId, $userId, $now, $now)
			);
		}

		foreach ($items as $item) {
			$itemId = (int) $db->getUniqueID('vtiger_goodsreceipt_items');
			$db->pquery(
				"INSERT INTO vtiger_goodsreceipt_items(itemid, receiptid, productid, product_name, product_type, quantity, unit_price, description, line_note, serial_number)
				 VALUES(?,?,?,?,?,?,?,?,?,?)",
				array(
					$itemId,
					$recordId,
					$item['productid'],
					$item['product_name'],
					$item['product_type'],
					$item['quantity'],
					$item['unit_price'],
					isset($item['description']) ? $item['description'] : '',
					$item['line_note'],
					isset($item['serial_number']) ? $item['serial_number'] : ''
				)
			);
		}

		$this->applyStockDelta(
			$db,
			$this->sanitizeStockItems($oldItems),
			$this->sanitizeStockItems($items),
			$userId,
			$now,
			$storageLocation,
			$note
		);
		$this->saveUploadedAttachments($db, $recordId, $userId, $now);
		header('Location: index.php?module=GoodsReceipt&view=Detail&record=' . $recordId . '&app=INVENTORY');
		exit;
	}

	protected function saveUploadedAttachments(PearDatabase $db, $recordId, $userId, $now) {
		if (!isset($_FILES['attachments']) || !is_array($_FILES['attachments']['name'])) {
			return;
		}

		$names = $_FILES['attachments']['name'];
		$tmpNames = $_FILES['attachments']['tmp_name'];
		$errors = $_FILES['attachments']['error'];
		$types = $_FILES['attachments']['type'];
		$sizes = $_FILES['attachments']['size'];

		$baseDir = 'storage/goodsreceipt/' . date('Y') . '/' . date('m') . '/';
		if (!is_dir($baseDir)) {
			@mkdir($baseDir, 0775, true);
		}

		$count = count($names);
		for ($i = 0; $i < $count; $i++) {
			$origName = isset($names[$i]) ? (string) $names[$i] : '';
			$tmpPath = isset($tmpNames[$i]) ? (string) $tmpNames[$i] : '';
			$errorCode = isset($errors[$i]) ? (int) $errors[$i] : UPLOAD_ERR_NO_FILE;
			$fileType = isset($types[$i]) ? (string) $types[$i] : '';
			$fileSize = isset($sizes[$i]) ? (int) $sizes[$i] : 0;

			if ($origName === '' || $errorCode === UPLOAD_ERR_NO_FILE) {
				continue;
			}
			if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($tmpPath)) {
				continue;
			}

			$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
			if (!in_array($ext, $this->allowedExtensions, true)) {
				continue;
			}

			$safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($origName));
			$storedName = uniqid('gr_', true) . '_' . $safeBase;
			$targetPath = $baseDir . $storedName;
			if (!@move_uploaded_file($tmpPath, $targetPath)) {
				continue;
			}

			$attachmentId = (int) $db->getUniqueID('vtiger_goodsreceipt_attachments');
			$db->pquery(
				"INSERT INTO vtiger_goodsreceipt_attachments
				 (attachmentid, receiptid, filename, stored_name, filepath, filetype, filesize, createdby, createdtime, deleted)
				 VALUES (?,?,?,?,?,?,?,?,?,0)",
				array($attachmentId, $recordId, $origName, $storedName, $baseDir, $fileType, $fileSize, $userId, $now)
			);
		}
	}
}

