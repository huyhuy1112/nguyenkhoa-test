<?php
class GoodsIssue_Save_Action extends Vtiger_Action_Controller {
	protected $typeCacheByProductId = array();
	protected $allowedExtensions = array('jpg','jpeg','png','webp','pdf','doc','docx','xls','xlsx','csv','txt');

	public function requiresPermission(\Vtiger_Request $request) { return array(); }
	public function checkPermission($request) { return true; }
	public function validateRequest(Vtiger_Request $request) { return; }

	protected function normalizeTypeLabel($value) {
		$v = strtolower(trim((string) $value));
		if ($v === '') return null;
		if (in_array($v, array('hardware','product','products'), true)) return 'Hardware';
		if ($v === 'software') return 'Software';
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
			if (empty($type)) $type = 'Other';
		}
		$this->typeCacheByProductId[$productId] = $type;
		return $type;
	}

	protected function parseItems(Vtiger_Request $request) {
		$items = array();
		$names = $request->get('item_product_name');
		$ids = $request->get('item_productid');
		$productKeys = $request->get('item_product_key');
		$qtys = $request->get('item_quantity');
		$prices = $request->get('item_unit_price');
		$notes = $request->get('item_line_note');
		$types = $request->get('item_product_type');
		$discounts = $request->get('item_discount');
		$descriptions = null;
		if (isset($_POST['description']) && is_array($_POST['description'])) {
			$descriptions = $_POST['description'];
		} else {
			$descriptions = $request->get('description');
		}
		if (!is_array($descriptions)) {
			$descriptions = array();
		}
		// Prefer raw POST array: multipart Save must capture serial_number[] reliably (Request::get can miss array shape).
		$serials = null;
		if (isset($_POST['serial_number']) && is_array($_POST['serial_number'])) {
			$serials = $_POST['serial_number'];
		} else {
			$serials = $request->get('serial_number');
			if (!is_array($serials)) {
				$serials = $request->get('item_serial');
			}
		}
		if (is_array($serials)) {
			foreach ($serials as $si => $sv) {
				if (is_string($sv)) {
					$serials[$si] = trim(vtlib_purify($sv));
				}
			}
		}

		if (!is_array($names) && !is_array($ids)) {
			return array();
		}
		$count = is_array($names) ? count($names) : count($ids);
		for ($i = 0; $i < $count; $i++) {
			$productId = (int) (is_array($ids) && isset($ids[$i]) ? $ids[$i] : 0);
			$name = trim((string) (is_array($names) && isset($names[$i]) ? $names[$i] : ''));
			$qty = (float) (is_array($qtys) && isset($qtys[$i]) ? $qtys[$i] : 0);
			$price = (float) (is_array($prices) && isset($prices[$i]) ? $prices[$i] : 0);
			$note = (string) (is_array($notes) && isset($notes[$i]) ? $notes[$i] : '');
			$lineDesc = (string) (isset($descriptions[$i]) ? $descriptions[$i] : '');
			$discount = (float) (is_array($discounts) && isset($discounts[$i]) ? $discounts[$i] : 0);
			if ($discount < 0) $discount = 0;
			if ($discount > 100) $discount = 100;
			$serial = (string) (is_array($serials) && isset($serials[$i]) ? $serials[$i] : '');
			$productKey = trim((string) (is_array($productKeys) && isset($productKeys[$i]) ? $productKeys[$i] : ''));
			$rawType = (string) (is_array($types) && isset($types[$i]) ? $types[$i] : '');

			// Ignore invalid lines
			if ($qty <= 0) continue;
			if ($productId <= 0 && $name === '') continue;

			$note = vtlib_purify($note);
			$lineDesc = vtlib_purify($lineDesc);
			$items[] = array(
				'productid' => $productId > 0 ? $productId : null,
				'product_name' => $name,
				'product_key' => $productKey,
				'product_type' => $this->resolveProductType($productId, $rawType),
				'quantity' => $qty,
				'unit_price' => $price,
				'discount_percent' => $discount,
				'serial_number' => $serial,
				'description' => $lineDesc,
				'line_note' => $note,
			);
		}
		return $items;
	}

	protected function itemKey(array $item) {
		if (!empty($item['product_key'])) {
			return trim((string) $item['product_key']);
		}
		require_once 'modules/Warehouse/helpers/InventoryCrossNavHelper.php';
		return Inventory_CrossNav_Helper::stockStyleItemKey($item);
	}

	protected function aggregateByProductKey(array $items) {
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
				);
			}
			$agg[$key]['quantity'] += (float) $item['quantity'];
		}
		return $agg;
	}

	protected function loadOldItems(PearDatabase $db, $issueId) {
		$rs = $db->pquery(
			"SELECT productid, product_name, product_type, quantity, unit_price, line_note, description
			 FROM vtiger_goodsissue_items
			 WHERE issueid = ?",
			array($issueId)
		);
		$items = array();
		while ($row = $db->fetchByAssoc($rs)) {
			$items[] = array(
				'productid' => !empty($row['productid']) ? (int) $row['productid'] : null,
				'product_name' => (string) $row['product_name'],
				'product_type' => (string) $row['product_type'],
				'quantity' => (float) $row['quantity'],
				'unit_price' => (float) $row['unit_price'],
				'discount_percent' => isset($row['discount_percent']) ? (float) $row['discount_percent'] : 0.0,
				'serial_number' => isset($row['serial_number']) ? (string) $row['serial_number'] : '',
				'description' => isset($row['description']) ? (string) $row['description'] : '',
				'line_note' => (string) $row['line_note'],
			);
		}
		return $items;
	}

	protected function loadStockRowsByKeys(PearDatabase $db, array $keys) {
		require_once 'modules/Warehouse/helpers/StockHelper.php';
		$keys = array_values(array_unique(array_filter($keys)));
		if (empty($keys)) {
			return array();
		}
		$map = array();
		foreach ($keys as $requestedKey) {
			$row = Warehouse_Stock_Helper::findStockRowByProductKey($db, $requestedKey);
			if ($row !== null) {
				$map[(string) $requestedKey] = $row;
			}
		}
		return $map;
	}

	protected function redirectEdit($issueId, array $params) {
		$q = array('module=GoodsIssue', 'view=Edit', 'app=INVENTORY');
		if ($issueId > 0) $q[] = 'record=' . (int) $issueId;
		foreach ($params as $k => $v) {
			$q[] = rawurlencode((string) $k) . '=' . rawurlencode((string) $v);
		}
		header('Location: index.php?' . implode('&', $q));
		exit;
	}

	public function process(Vtiger_Request $request) {
		require_once 'modules/GoodsIssue/helpers/WorkflowSetup.php';
		GoodsIssue_WorkflowSetup_Helper::runAll();

		$db = PearDatabase::getInstance();
		$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
		$now = date('Y-m-d H:i:s');

		$issueId = (int) $request->get('record');
		$subject = trim((string) $request->get('subject'));
		$issuedDate = trim((string) $request->get('issued_date'));
		if ($issuedDate === '') {
			$issuedDate = date('Y-m-d');
		}
		// System-controlled issuer: never trust request payload.
		$issuedBy = '';
		try {
			$issuedBy = (string) Users_Record_Model::getCurrentUserModel()->get('user_name');
		} catch (Throwable $e) {
			$issuedBy = '';
		}
		$issuedBy = trim($issuedBy);
		$destination = trim((string) $request->get('destination'));
		$storageLocation = trim((string) $request->get('storage_location'));
		$note = (string) $request->get('note');
		$newItems = $this->parseItems($request);

		if ($subject === '' || empty($newItems)) {
			$this->redirectEdit($issueId, array('validation' => 1));
		}

		$oldItems = array();
		if ($issueId > 0) {
			$oldItems = $this->loadOldItems($db, $issueId);
		}

		$oldAgg = $this->aggregateByProductKey($oldItems);
		$newAgg = $this->aggregateByProductKey($newItems);
		$keys = array_unique(array_merge(array_keys($oldAgg), array_keys($newAgg)));

		$restoreByKey = array();
		$deductByKey = array();
		foreach ($keys as $k) {
			$oldQty = isset($oldAgg[$k]) ? (float) $oldAgg[$k]['quantity'] : 0.0;
			$newQty = isset($newAgg[$k]) ? (float) $newAgg[$k]['quantity'] : 0.0;
			$delta = $newQty - $oldQty;
			if (abs($delta) < 0.00000001) continue;
			if ($delta > 0) $deductByKey[$k] = (float) $delta;
			if ($delta < 0) $restoreByKey[$k] = (float) (-$delta);
		}

		$stockMap = $this->loadStockRowsByKeys($db, $keys);

		// Validate all required stock rows exist.
		foreach ($keys as $k) {
			if (!isset($stockMap[$k])) {
				// If there is any movement (restore or deduct), block cleanly.
				if (!empty($restoreByKey[$k]) || !empty($deductByKey[$k])) {
					$this->redirectEdit($issueId, array('error_stock_missing' => 1, 'missing_key' => $k));
				}
			}
		}

		// Validate deductions against available stock AFTER considering restores for same key.
		foreach ($deductByKey as $k => $deductQty) {
			$row = $stockMap[$k];
			$q = (float) $row['quantity'];
			$s = isset($row['shrinkage_qty']) ? (float) $row['shrinkage_qty'] : 0.0;
			$restoreQty = isset($restoreByKey[$k]) ? (float) $restoreByKey[$k] : 0.0;
			$available = ($q + $restoreQty) - $s;
			if ($available < 0) $available = 0;
			if ($deductQty - $available > 0.00000001) {
				$this->redirectEdit($issueId, array('out_of_stock' => 1, 'stock_key' => $k));
			}
		}

		$db->startTransaction();
		try {
			// Generate outbound code on create (or backfill if missing on edit).
			$dayKey = str_replace('-', '', (string) $issuedDate); // YYYYMMDD
			$prefix = 'OUT-' . $dayKey . '-';
			$codeToSet = null;
			if ($issueId <= 0) {
				$rsMax = $db->pquery(
					"SELECT MAX(CAST(SUBSTRING(code, 14) AS UNSIGNED)) AS max_seq
					 FROM vtiger_goodsissue
					 WHERE issued_date = ? AND code LIKE ?",
					array($issuedDate, $prefix . '%')
				);
				$maxSeq = (int) $db->query_result($rsMax, 0, 'max_seq');
				$nextSeq = $maxSeq + 1;
				$codeToSet = 'OUT-' . $dayKey . '-' . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
			} else {
				$rsExisting = $db->pquery(
					"SELECT code FROM vtiger_goodsissue WHERE issueid = ? LIMIT 1",
					array($issueId)
				);
				$existingCode = '';
				if ($db->num_rows($rsExisting) > 0) {
					$existingCode = trim((string) $db->query_result($rsExisting, 0, 'code'));
				}
				if ($existingCode !== '') {
					$codeToSet = $existingCode;
				} else {
					$rsMax = $db->pquery(
						"SELECT MAX(CAST(SUBSTRING(code, 14) AS UNSIGNED)) AS max_seq
						 FROM vtiger_goodsissue
						 WHERE issued_date = ? AND code LIKE ?",
						array($issuedDate, $prefix . '%')
					);
					$maxSeq = (int) $db->query_result($rsMax, 0, 'max_seq');
					$nextSeq = $maxSeq + 1;
					$codeToSet = 'OUT-' . $dayKey . '-' . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
				}
			}

			// Apply restores first.
			foreach ($restoreByKey as $k => $restoreQty) {
				$row = $stockMap[$k];
				$stockId = (int) $row['stockid'];
				$db->pquery(
					"UPDATE vtiger_warehouse_stock
					 SET quantity = quantity + ?, updatedby = ?, updatedtime = ?
					 WHERE stockid = ?",
					array($restoreQty, $userId, $now, $stockId)
				);
			}

			// Apply deductions.
			foreach ($deductByKey as $k => $deductQty) {
				$row = $stockMap[$k];
				$stockId = (int) $row['stockid'];
				$db->pquery(
					"UPDATE vtiger_warehouse_stock
					 SET quantity = quantity - ?, updatedby = ?, updatedtime = ?
					 WHERE stockid = ?",
					array($deductQty, $userId, $now, $stockId)
				);
			}

			// Upsert header
			if ($issueId <= 0) {
				$issueId = (int) $db->getUniqueID('vtiger_goodsissue');
				$db->pquery(
					"INSERT INTO vtiger_goodsissue(issueid, code, subject, issued_by, issued_date, destination, storage_location, note, createdby, updatedby, createdtime, updatedtime, deleted)
					 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,0)",
					array($issueId, $codeToSet, $subject, $issuedBy, $issuedDate, $destination, $storageLocation, $note, $userId, $userId, $now, $now)
				);
			} else {
				$db->pquery(
					"UPDATE vtiger_goodsissue
					 SET code = ?, subject = ?, issued_by = ?, issued_date = ?, destination = ?, storage_location = ?, note = ?, updatedby = ?, updatedtime = ?, deleted = 0
					 WHERE issueid = ?",
					array($codeToSet, $subject, $issuedBy, $issuedDate, $destination, $storageLocation, $note, $userId, $now, $issueId)
				);
				$db->pquery("DELETE FROM vtiger_goodsissue_items WHERE issueid = ?", array($issueId));
			}

			// Insert items
			foreach ($newItems as $it) {
				$itemId = (int) $db->getUniqueID('vtiger_goodsissue_items');
				$db->pquery(
					"INSERT INTO vtiger_goodsissue_items(itemid, issueid, productid, product_name, product_type, quantity, unit_price, discount_percent, serial_number, description, line_note)
					 VALUES(?,?,?,?,?,?,?,?,?,?,?)",
					array(
						$itemId,
						$issueId,
						!empty($it['productid']) ? (int) $it['productid'] : null,
						(string) $it['product_name'],
						(string) $it['product_type'],
						(float) $it['quantity'],
						(float) $it['unit_price'],
						isset($it['discount_percent']) ? (float) $it['discount_percent'] : 0.0,
						isset($it['serial_number']) ? (string) $it['serial_number'] : '',
						isset($it['description']) ? (string) $it['description'] : '',
						(string) $it['line_note'],
					)
				);
			}

			$db->completeTransaction();

			// Attachments are additive and should not affect stock movement.
			try {
				$this->saveUploadedAttachments($db, $issueId, $userId, $now);
			} catch (Throwable $e) {
				// Swallow upload errors to avoid breaking the core save flow.
			}
		} catch (Throwable $e) {
			$db->rollbackTransaction();
			$this->redirectEdit($issueId, array('error' => 'save_failed'));
		}

		header('Location: index.php?module=GoodsIssue&view=Detail&record=' . (int) $issueId . '&app=INVENTORY');
		exit;
	}

	protected function saveUploadedAttachments(PearDatabase $db, $issueId, $userId, $now) {
		if ((int)$issueId <= 0) return;
		if (!isset($_FILES['attachments']) || !is_array($_FILES['attachments']['name'])) return;

		$names = $_FILES['attachments']['name'];
		$tmpNames = $_FILES['attachments']['tmp_name'];
		$errors = $_FILES['attachments']['error'];
		$types = $_FILES['attachments']['type'];
		$sizes = $_FILES['attachments']['size'];

		$baseDir = 'storage/goodsissue/' . date('Y') . '/' . date('m') . '/';
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

			if ($origName === '' || $errorCode === UPLOAD_ERR_NO_FILE) continue;
			if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($tmpPath)) continue;

			$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
			if (!in_array($ext, $this->allowedExtensions, true)) continue;

			$safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($origName));
			$storedName = uniqid('gi_', true) . '_' . $safeBase;
			$targetPath = $baseDir . $storedName;
			if (!@move_uploaded_file($tmpPath, $targetPath)) continue;

			$attachmentId = (int) $db->getUniqueID('vtiger_goodsissue_attachments');
			$db->pquery(
				"INSERT INTO vtiger_goodsissue_attachments
				 (attachmentid, issueid, filename, stored_name, filepath, filetype, filesize, createdby, createdtime, deleted)
				 VALUES (?,?,?,?,?,?,?,?,?,0)",
				array($attachmentId, (int)$issueId, $origName, $storedName, $baseDir, $fileType, $fileSize, $userId, $now)
			);
		}
	}
}

?>

