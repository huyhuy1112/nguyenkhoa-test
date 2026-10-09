<?php
/*+***********************************************************************************
 * One-step Sales Order import from KiotViet "Lịch sử đơn hàng".
 * Each Excel row is a product line. Rows that share Mã đặt hàng become one order.
 * Does not deduct warehouse stock and does not push MISA.
 *************************************************************************************/

class SalesOrder_KiotSimpleImport_Helper {

	public static function importUploadedFile(array $fileInfo, $userId = 0) {
		@set_time_limit(0);
		@ini_set('memory_limit', '512M');

		$path = isset($fileInfo['tmp_name']) ? (string) $fileInfo['tmp_name'] : '';
		$origName = isset($fileInfo['name']) ? (string) $fileInfo['name'] : '';
		if ($path === '' || !is_file($path)) {
			throw new Exception('Chưa chọn file Excel (.xlsx).');
		}
		if (!preg_match('/\.xlsx$/i', $origName) && !preg_match('/\.xlsx$/i', $path)) {
			throw new Exception('Chỉ hỗ trợ file .xlsx (Lịch sử đơn hàng).');
		}

		$userId = (int) $userId;
		if ($userId <= 0) {
			global $current_user;
			$userId = isset($current_user->id) ? (int) $current_user->id : 1;
		}

		$parsed = self::parseOrders($path);
		$orders = $parsed['orders'];
		if (empty($orders)) {
			throw new Exception('Không đọc được đơn hàng. File cần cột Mã đặt hàng và Mã hàng.');
		}

		$db = PearDatabase::getInstance();
		$accounts = self::loadCodeMap($db, 'account');
		$contacts = self::loadCodeMap($db, 'contact');
		$existing = self::loadExistingOrders($db);
		$catalog = self::loadCatalog($db);
		$soCols = self::columnSet($db, 'vtiger_salesorder');
		$ceCols = self::columnSet($db, 'vtiger_crmentity');
		$lineCols = self::columnSet($db, 'vtiger_inventoryproductrel');
		$hasCf = self::tableExists($db, 'vtiger_salesordercf');

		$stats = array(
			'excel_rows' => (int) $parsed['rows'],
			'orders' => count($orders),
			'created' => 0,
			'updated' => 0,
			'lines' => 0,
			'created_sku' => 0,
			'unlinked_customer' => 0,
			'skipped' => (int) $parsed['skipped'],
			'errors' => 0,
		);
		$errorSamples = array();

		global $VTIGER_BULK_SAVE_MODE;
		$prevBulk = isset($VTIGER_BULK_SAVE_MODE) ? $VTIGER_BULK_SAVE_MODE : false;
		$VTIGER_BULK_SAVE_MODE = true;

		foreach ($orders as $code => $order) {
			try {
				$customer = self::resolveCustomer($order['customerCode'], $accounts, $contacts);
				if ($customer['id'] <= 0) {
					$stats['unlinked_customer']++;
				}
				$lineRows = array();
				foreach ($order['lines'] as $line) {
					$sku = $line['sku'];
					if ($sku === '') {
						continue;
					}
					if (!isset($catalog[$sku])) {
						$catalog[$sku] = self::createCatalogProduct($sku, $line['name'], $line['price'], $userId);
						$stats['created_sku']++;
					}
					$line['productId'] = (int) $catalog[$sku]['id'];
					if ($line['name'] === '' && $catalog[$sku]['name'] !== '') {
						$line['name'] = $catalog[$sku]['name'];
					}
					$lineRows[] = $line;
				}
				if (empty($lineRows)) {
					$stats['skipped']++;
					continue;
				}
				$order['lines'] = $lineRows;
				if (isset($existing[$code])) {
					self::updateOrder($db, (int) $existing[$code], $order, $customer, $userId, $soCols, $ceCols, $lineCols);
					$stats['updated']++;
				} else {
					$newId = self::createOrder($db, $order, $customer, $userId, $soCols, $ceCols, $lineCols, $hasCf);
					$existing[$code] = $newId;
					$stats['created']++;
				}
				$stats['lines'] += count($lineRows);
			} catch (Exception $ex) {
				$stats['errors']++;
				if (count($errorSamples) < 8) {
					$errorSamples[] = $code . ': ' . $ex->getMessage();
				}
			}
		}

		$VTIGER_BULK_SAVE_MODE = $prevBulk;
		$stats['error_samples'] = $errorSamples;
		$stats['message'] = self::buildResultMessage($stats);
		$stats['success'] = ($stats['created'] + $stats['updated']) > 0;
		return $stats;
	}

	public static function buildResultMessage(array $stats) {
		$msg = sprintf(
			"Import đơn hàng xong.\n- Dòng Excel: %d\n- Đơn đã gộp theo mã: %d\n- Tạo mới: %d\n- Cập nhật: %d\n- Dòng sản phẩm: %d\n- Tạo hàng hoá mới: %d\n- Đơn chưa gắn được khách: %d\n- Bỏ qua: %d\n- Lỗi: %d",
			(int) $stats['excel_rows'],
			(int) $stats['orders'],
			(int) $stats['created'],
			(int) $stats['updated'],
			(int) $stats['lines'],
			(int) $stats['created_sku'],
			(int) $stats['unlinked_customer'],
			(int) $stats['skipped'],
			(int) $stats['errors']
		);
		if (!empty($stats['error_samples'])) {
			$msg .= "\n\nLỗi mẫu:\n- " . implode("\n- ", $stats['error_samples']);
		}
		return $msg;
	}

	/**
	 * @return array{orders: array, rows: int, skipped: int}
	 */
	public static function parseOrders($path) {
		if (!is_file($path)) {
			throw new Exception('File Excel không tồn tại.');
		}
		$zip = new ZipArchive();
		if ($zip->open($path) !== true) {
			throw new Exception('Không mở được file Excel (.xlsx).');
		}
		$shared = self::readSharedStrings($zip);
		$sheetName = self::resolveDetailSheet($zip);
		$sheetXml = $zip->getFromName($sheetName);
		$zip->close();
		if ($sheetXml === false || $sheetXml === '') {
			throw new Exception('Không đọc được sheet chi tiết đơn hàng.');
		}
		$tmp = tempnam(sys_get_temp_dir(), 'mkso');
		if ($tmp === false || file_put_contents($tmp, $sheetXml) === false) {
			throw new Exception('Không lưu được sheet để đọc.');
		}
		unset($sheetXml);

		$reader = new XMLReader();
		if (!$reader->open($tmp)) {
			@unlink($tmp);
			throw new Exception('Không đọc được nội dung Excel.');
		}

		$headers = array();
		$colMap = array();
		$orders = array();
		$rowNum = 0;
		$currentRow = 0;
		$current = array();
		$excelRows = 0;
		$skipped = 0;

		while ($reader->read()) {
			if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'c') {
				continue;
			}
			$ref = (string) $reader->getAttribute('r');
			if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
				continue;
			}
			$col = self::colIndex($m[1]);
			$r = (int) $m[2];
			$type = (string) $reader->getAttribute('t');
			$value = self::readCellValue($reader, $type, $shared);
			if ($r !== $currentRow) {
				if ($currentRow === 1) {
					$colMap = self::mapHeaders($current);
				} elseif ($currentRow > 1) {
					$excelRows++;
					$added = self::consumeRow($orders, $current, $colMap);
					if (!$added) {
						$skipped++;
					}
				}
				$currentRow = $r;
				$current = array();
				$rowNum = $r;
			}
			$current[$col] = $value;
		}
		if ($currentRow === 1) {
			$colMap = self::mapHeaders($current);
		} elseif ($currentRow > 1) {
			$excelRows++;
			if (!self::consumeRow($orders, $current, $colMap)) {
				$skipped++;
			}
		}
		$reader->close();
		@unlink($tmp);
		if (empty($colMap['code']) || empty($colMap['sku'])) {
			throw new Exception('Không thấy cột Mã đặt hàng hoặc Mã hàng trên sheet chi tiết.');
		}
		return array(
			'orders' => $orders,
			'rows' => $excelRows,
			'skipped' => $skipped,
		);
	}

	protected static function consumeRow(array &$orders, array $cells, array $colMap) {
		if (empty($colMap['code'])) {
			return false;
		}
		$code = self::cell($cells, $colMap, 'code');
		$sku = self::cell($cells, $colMap, 'sku');
		$name = self::cell($cells, $colMap, 'productName');
		if ($code === '' || ($sku === '' && $name === '')) {
			return false;
		}
		if (!isset($orders[$code])) {
			$orders[$code] = array(
				'code' => $code,
				'invoice' => '',
				'when' => '',
				'customerCode' => '',
				'customerName' => '',
				'phone' => '',
				'address' => '',
				'note' => '',
				'subtotal' => 0.0,
				'discount' => 0.0,
				'grand' => 0.0,
				'paid' => 0.0,
				'status' => 'Created',
				'lines' => array(),
			);
		}
		$order = &$orders[$code];
		self::fillIfEmpty($order, 'invoice', self::cell($cells, $colMap, 'invoice'));
		self::fillIfEmpty($order, 'when', self::excelDateTime(self::cell($cells, $colMap, 'createdAt')));
		if ($order['when'] === '') {
			self::fillIfEmpty($order, 'when', self::excelDateTime(self::cell($cells, $colMap, 'when')));
		}
		self::fillIfEmpty($order, 'customerCode', self::cell($cells, $colMap, 'customerCode'));
		self::fillIfEmpty($order, 'customerName', self::cell($cells, $colMap, 'customerName'));
		self::fillIfEmpty($order, 'phone', self::cell($cells, $colMap, 'phone'));
		self::fillIfEmpty($order, 'address', self::cell($cells, $colMap, 'address'));
		self::fillIfEmpty($order, 'note', self::cell($cells, $colMap, 'note'));
		if ($order['subtotal'] <= 0) {
			$order['subtotal'] = self::num(self::cell($cells, $colMap, 'subtotal'));
		}
		if ($order['discount'] <= 0) {
			$order['discount'] = self::num(self::cell($cells, $colMap, 'discount'));
		}
		if ($order['grand'] <= 0) {
			$order['grand'] = self::num(self::cell($cells, $colMap, 'grand'));
		}
		if ($order['paid'] <= 0) {
			$order['paid'] = self::num(self::cell($cells, $colMap, 'paid'));
		}
		$statusRaw = self::cell($cells, $colMap, 'status');
		if ($statusRaw !== '' && $order['status'] === 'Created') {
			$order['status'] = self::mapStatus($statusRaw);
		}
		$price = self::num(self::cell($cells, $colMap, 'salePrice'));
		if ($price <= 0) {
			$price = self::num(self::cell($cells, $colMap, 'listPrice'));
		}
		$order['lines'][] = array(
			'sku' => $sku !== '' ? $sku : $name,
			'name' => $name,
			'qty' => self::num(self::cell($cells, $colMap, 'qty')),
			'price' => $price,
			'discountPercent' => self::num(self::cell($cells, $colMap, 'discountPercent')),
			'discountAmount' => self::num(self::cell($cells, $colMap, 'discountAmount')),
			'unit' => self::cell($cells, $colMap, 'unit'),
			'lineNote' => self::cell($cells, $colMap, 'lineNote'),
		);
		unset($order);
		return true;
	}

	protected static function fillIfEmpty(array &$order, $key, $value) {
		$value = trim((string) $value);
		if ($value !== '' && trim((string) $order[$key]) === '') {
			$order[$key] = $value;
		}
	}

	protected static function mapHeaders(array $cells) {
		$map = array();
		foreach ($cells as $col => $label) {
			$raw = trim((string) $label);
			if ($raw === '') {
				continue;
			}
			$key = self::fold($raw);
			if (strpos($raw, '%') !== false) {
				$key = 'giam_gia_phan_tram';
			}
			$field = '';
			if ($key === 'ma_dat_hang' || $key === 'ma_don_hang') {
				$field = 'code';
			} elseif ($key === 'ma_hoa_don') {
				$field = 'invoice';
			} elseif ($key === 'thoi_gian_tao') {
				$field = 'createdAt';
			} elseif ($key === 'thoi_gian') {
				$field = 'when';
			} elseif ($key === 'ma_khach_hang') {
				$field = 'customerCode';
			} elseif ($key === 'ten_khach_hang') {
				$field = 'customerName';
			} elseif ($key === 'dien_thoai') {
				$field = 'phone';
			} elseif ($key === 'dia_chi_khach_hang') {
				$field = 'address';
			} elseif ($key === 'ghi_chu') {
				$field = 'note';
			} elseif ($key === 'ghi_chu_hang_hoa') {
				$field = 'lineNote';
			} elseif ($key === 'tong_tien_hang') {
				$field = 'subtotal';
			} elseif ($key === 'giam_gia_phieu_dat') {
				$field = 'discount';
			} elseif ($key === 'khach_can_tra') {
				$field = 'grand';
			} elseif ($key === 'khach_da_tra') {
				$field = 'paid';
			} elseif ($key === 'trang_thai') {
				$field = 'status';
			} elseif ($key === 'ma_hang') {
				$field = 'sku';
			} elseif ($key === 'ten_hang') {
				$field = 'productName';
			} elseif ($key === 'so_luong') {
				$field = 'qty';
			} elseif ($key === 'don_gia') {
				$field = 'listPrice';
			} elseif ($key === 'gia_ban') {
				$field = 'salePrice';
			} elseif ($key === 'giam_gia_phan_tram') {
				$field = 'discountPercent';
			} elseif ($key === 'giam_gia') {
				$field = 'discountAmount';
			} elseif ($key === 'dvt') {
				$field = 'unit';
			}
			if ($field !== '' && !isset($map[$field])) {
				$map[$field] = (int) $col;
			}
		}
		return $map;
	}

	protected static function cell(array $cells, array $colMap, $field) {
		if (!isset($colMap[$field])) {
			return '';
		}
		$col = $colMap[$field];
		return isset($cells[$col]) ? trim((string) $cells[$col]) : '';
	}

	protected static function mapStatus($raw) {
		$s = self::fold($raw);
		if ($s === 'hoan_thanh' || $s === 'da_giao') {
			return 'Delivered';
		}
		if ($s === 'da_xac_nhan' || $s === 'da_duyet') {
			return 'Approved';
		}
		if ($s === 'da_huy') {
			return 'Cancelled';
		}
		return 'Created';
	}

	protected static function resolveCustomer($code, array $accounts, array $contacts) {
		$code = trim((string) $code);
		$out = array('kind' => '', 'id' => 0);
		if ($code === '') {
			return $out;
		}
		$upper = strtoupper($code);
		$isAccount = (strpos($upper, 'TUIBAO') === 0 || strpos($upper, 'MIUTEA') === 0);
		$isContact = (strpos($upper, 'KL_') === 0 || strpos($upper, 'KL-') === 0);
		$key = self::fold($code);
		if ($isAccount || !$isContact) {
			if (isset($accounts[$key])) {
				return array('kind' => 'account', 'id' => (int) $accounts[$key]);
			}
		}
		if ($isContact || !$isAccount) {
			if (isset($contacts[$key])) {
				return array('kind' => 'contact', 'id' => (int) $contacts[$key]);
			}
		}
		return $out;
	}

	protected static function loadCodeMap(PearDatabase $db, $kind) {
		$map = array();
		if ($kind === 'account') {
			$sql = 'SELECT a.accountid AS id, a.account_no AS code
				FROM vtiger_account a
				INNER JOIN vtiger_crmentity ce ON ce.crmid = a.accountid AND ce.deleted = 0
				WHERE IFNULL(a.account_no, \'\') <> \'\'';
		} else {
			$sql = 'SELECT c.contactid AS id, c.contact_no AS code
				FROM vtiger_contactdetails c
				INNER JOIN vtiger_crmentity ce ON ce.crmid = c.contactid AND ce.deleted = 0
				WHERE IFNULL(c.contact_no, \'\') <> \'\'';
		}
		$rs = $db->pquery($sql, array());
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$code = trim((string) $row['code']);
			if ($code === '') {
				continue;
			}
			$map[self::fold($code)] = (int) $row['id'];
		}
		return $map;
	}

	protected static function loadExistingOrders(PearDatabase $db) {
		$map = array();
		$rs = $db->pquery(
			'SELECT so.salesorderid, so.salesorder_no
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
			 WHERE IFNULL(so.salesorder_no, \'\') <> \'\'',
			array()
		);
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$code = trim((string) $row['salesorder_no']);
			if ($code !== '') {
				$map[$code] = (int) $row['salesorderid'];
			}
		}
		return $map;
	}

	protected static function loadCatalog(PearDatabase $db) {
		$catalog = array();
		$rs = $db->pquery(
			'SELECT ps.productsservicesid AS id, ps.sku, ps.productsservicesname AS name
			 FROM vtiger_productsservices ps
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
			 WHERE IFNULL(ps.sku, \'\') <> \'\'',
			array()
		);
		while ($rs && ($row = $db->fetchByAssoc($rs))) {
			$sku = trim(html_entity_decode((string) $row['sku'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			if ($sku === '') {
				continue;
			}
			$catalog[$sku] = array(
				'id' => (int) $row['id'],
				'name' => trim(html_entity_decode((string) $row['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
			);
		}
		return $catalog;
	}

	protected static function createCatalogProduct($sku, $name, $price, $userId) {
		$sku = trim((string) $sku);
		$name = trim((string) $name);
		if ($name === '') {
			$name = $sku;
		}
		$record = Vtiger_Record_Model::getCleanInstance('ProductsServices');
		$record->set('mode', '');
		$record->set('productsservicesname', $name);
		$record->set('sku', $sku);
		$record->set('item_type', 'Product');
		$record->set('price', $price > 0 ? $price : 0);
		$record->set('assigned_user_id', (int) $userId > 0 ? (int) $userId : 1);
		$record->save();
		$id = (int) $record->getId();
		if ($id <= 0) {
			throw new Exception('Không tạo được hàng hoá ' . $sku);
		}
		return array('id' => $id, 'name' => $name);
	}

	protected static function createOrder(PearDatabase $db, array $order, array $customer, $userId, array $soCols, array $ceCols, array $lineCols, $hasCf) {
		$id = (int) $db->getUniqueID('vtiger_crmentity');
		if ($id <= 0) {
			throw new Exception('Không cấp được mã đơn.');
		}
		$now = date('Y-m-d H:i:s');
		$created = $order['when'] !== '' ? $order['when'] : $now;
		$subject = self::clip($order['customerName'] !== '' ? $order['customerName'] : $order['code'], 100);
		$description = self::descriptionOf($order);

		$ce = array(
			'crmid' => $id,
			'smcreatorid' => (int) $userId,
			'smownerid' => (int) $userId,
			'modifiedby' => (int) $userId,
			'setype' => 'SalesOrder',
			'description' => $description,
			'createdtime' => $created,
			'modifiedtime' => $now,
			'label' => $subject,
			'deleted' => 0,
		);
		self::insertRow($db, 'vtiger_crmentity', $ce, $ceCols);

		$so = self::salesOrderValues($id, $order, $customer, $subject);
		self::insertRow($db, 'vtiger_salesorder', $so, $soCols);
		self::writeAddress($db, $id, $order['address'], false);
		if ($hasCf) {
			try {
				$db->pquery('INSERT INTO vtiger_salesordercf (salesorderid) VALUES (?)', array($id));
			} catch (Exception $ignoreCf) {
				// custom table may require extra columns; the order header still stands
			}
		}
		self::writeLines($db, $id, $order['lines'], $lineCols);
		return $id;
	}

	protected static function updateOrder(PearDatabase $db, $id, array $order, array $customer, $userId, array $soCols, array $ceCols, array $lineCols) {
		$id = (int) $id;
		$now = date('Y-m-d H:i:s');
		$subject = self::clip($order['customerName'] !== '' ? $order['customerName'] : $order['code'], 100);
		$description = self::descriptionOf($order);
		$ce = array(
			'description' => $description,
			'modifiedtime' => $now,
			'modifiedby' => (int) $userId,
			'label' => $subject,
		);
		if ($order['when'] !== '') {
			$ce['createdtime'] = $order['when'];
		}
		self::updateRow($db, 'vtiger_crmentity', $ce, $ceCols, 'crmid', $id);

		$so = self::salesOrderValues($id, $order, $customer, $subject);
		unset($so['salesorderid']);
		self::updateRow($db, 'vtiger_salesorder', $so, $soCols, 'salesorderid', $id);
		self::writeAddress($db, $id, $order['address'], true);
		$db->pquery('DELETE FROM vtiger_inventoryproductrel WHERE id = ?', array($id));
		self::writeLines($db, $id, $order['lines'], $lineCols);
	}

	protected static function salesOrderValues($id, array $order, array $customer, $subject) {
		$grand = $order['grand'] > 0 ? $order['grand'] : max(0, $order['subtotal'] - $order['discount']);
		$accountId = ($customer['kind'] === 'account' && $customer['id'] > 0) ? (int) $customer['id'] : null;
		$contactId = ($customer['kind'] === 'contact' && $customer['id'] > 0) ? (int) $customer['id'] : null;
		$due = $order['when'] !== '' ? substr($order['when'], 0, 10) : null;
		return array(
			'salesorderid' => (int) $id,
			'subject' => $subject,
			'customerno' => self::clip($order['customerCode'], 100),
			'salesorder_no' => self::clip($order['code'], 100),
			'contactid' => $contactId,
			'accountid' => $accountId,
			'duedate' => $due,
			'total' => $grand,
			'subtotal' => $order['subtotal'],
			'pre_tax_total' => $grand,
			'taxtype' => 'group',
			'discount_percent' => null,
			'discount_amount' => $order['discount'],
			's_h_amount' => 0,
			'adjustment' => 0,
			'sostatus' => $order['status'],
			'currency_id' => 1,
			'conversion_rate' => 1,
			'enable_recurring' => 0,
			'received' => $order['paid'],
			'paid' => $order['paid'],
			'mk_customer_paid' => $order['paid'],
			'terms_conditions' => self::descriptionOf($order),
		);
	}

	protected static function descriptionOf(array $order) {
		$parts = array();
		if ($order['invoice'] !== '') {
			$parts[] = 'Mã hóa đơn: ' . $order['invoice'];
		}
		if ($order['note'] !== '') {
			$parts[] = $order['note'];
		}
		return implode("\n", $parts);
	}

	protected static function writeAddress(PearDatabase $db, $id, $address, $update) {
		$street = self::clip($address, 250);
		if ($update) {
			$rs = $db->pquery('SELECT sobilladdressid FROM vtiger_sobillads WHERE sobilladdressid = ?', array($id));
			if ($rs && $db->num_rows($rs) > 0) {
				$db->pquery('UPDATE vtiger_sobillads SET bill_street = ? WHERE sobilladdressid = ?', array($street, $id));
			} else {
				$db->pquery('INSERT INTO vtiger_sobillads (sobilladdressid, bill_street) VALUES (?, ?)', array($id, $street));
			}
			$rs2 = $db->pquery('SELECT soshipaddressid FROM vtiger_soshipads WHERE soshipaddressid = ?', array($id));
			if ($rs2 && $db->num_rows($rs2) > 0) {
				$db->pquery('UPDATE vtiger_soshipads SET ship_street = ? WHERE soshipaddressid = ?', array($street, $id));
			} else {
				$db->pquery('INSERT INTO vtiger_soshipads (soshipaddressid, ship_street) VALUES (?, ?)', array($id, $street));
			}
			return;
		}
		$db->pquery('INSERT INTO vtiger_sobillads (sobilladdressid, bill_street) VALUES (?, ?)', array($id, $street));
		$db->pquery('INSERT INTO vtiger_soshipads (soshipaddressid, ship_street) VALUES (?, ?)', array($id, $street));
	}

	protected static function writeLines(PearDatabase $db, $id, array $lines, array $lineCols) {
		$seq = 1;
		foreach ($lines as $line) {
			$productId = (int) $line['productId'];
			if ($productId <= 0) {
				continue;
			}
			$comment = $line['unit'];
			if ($line['lineNote'] !== '') {
				$comment = trim($comment . ' ' . $line['lineNote']);
			}
			$row = array(
				'id' => (int) $id,
				'productid' => $productId,
				'sequence_no' => $seq,
				'quantity' => $line['qty'] > 0 ? $line['qty'] : 0,
				'listprice' => $line['price'],
				'comment' => self::clip($comment, 100),
				'description' => self::clip($line['name'], 250),
				'discount_percent' => null,
				'discount_amount' => null,
			);
			if ($line['discountPercent'] > 0) {
				$row['discount_percent'] = $line['discountPercent'];
			} elseif ($line['discountAmount'] > 0) {
				$row['discount_amount'] = $line['discountAmount'];
			}
			self::insertRow($db, 'vtiger_inventoryproductrel', $row, $lineCols);
			$seq++;
		}
	}

	protected static function insertRow(PearDatabase $db, $table, array $values, array $cols) {
		$fields = array();
		$holders = array();
		$params = array();
		foreach ($values as $name => $value) {
			if (empty($cols[strtolower($name)])) {
				continue;
			}
			$fields[] = '`' . $name . '`';
			$holders[] = '?';
			$params[] = $value;
		}
		if (empty($fields)) {
			throw new Exception('Không ghi được ' . $table);
		}
		$db->pquery(
			'INSERT INTO ' . $table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $holders) . ')',
			$params
		);
	}

	protected static function updateRow(PearDatabase $db, $table, array $values, array $cols, $idCol, $id) {
		$sets = array();
		$params = array();
		foreach ($values as $name => $value) {
			if (empty($cols[strtolower($name)])) {
				continue;
			}
			$sets[] = '`' . $name . '` = ?';
			$params[] = $value;
		}
		if (empty($sets)) {
			return;
		}
		$params[] = $id;
		$db->pquery(
			'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE `' . $idCol . '` = ?',
			$params
		);
	}

	protected static function columnSet(PearDatabase $db, $table) {
		$set = array();
		$names = $db->getColumnNames($table);
		if (!is_array($names)) {
			return $set;
		}
		foreach ($names as $name) {
			$set[strtolower((string) $name)] = true;
		}
		return $set;
	}

	protected static function tableExists(PearDatabase $db, $table) {
		$rs = $db->pquery('SHOW TABLES LIKE ?', array($table));
		return $rs && $db->num_rows($rs) > 0;
	}

	protected static function readSharedStrings(ZipArchive $zip) {
		$shared = array();
		$idx = $zip->locateName('xl/sharedStrings.xml');
		if ($idx === false) {
			return $shared;
		}
		$xml = $zip->getFromIndex($idx);
		if ($xml === false) {
			return $shared;
		}
		$sx = @simplexml_load_string($xml);
		if (!$sx) {
			return $shared;
		}
		$items = $sx->xpath('//*[local-name()="si"]');
		if (!$items) {
			return $shared;
		}
		foreach ($items as $si) {
			$s = '';
			$texts = $si->xpath('.//*[local-name()="t"]');
			if ($texts) {
				foreach ($texts as $t) {
					$s .= (string) $t;
				}
			}
			$shared[] = $s;
		}
		return $shared;
	}

	protected static function resolveDetailSheet(ZipArchive $zip) {
		$best = '';
		$bestSize = -1;
		for ($i = 1; $i <= 8; $i++) {
			$name = 'xl/worksheets/sheet' . $i . '.xml';
			$stat = $zip->statName($name);
			if ($stat && (int) $stat['size'] > $bestSize) {
				$bestSize = (int) $stat['size'];
				$best = $name;
			}
		}
		if ($best === '') {
			throw new Exception('File Excel không có sheet.');
		}
		return $best;
	}

	protected static function readCellValue(XMLReader $reader, $type, array $shared) {
		$value = '';
		if ($reader->isEmptyElement) {
			return $value;
		}
		$depth = $reader->depth;
		while ($reader->read()) {
			if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->depth === $depth) {
				break;
			}
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'v') {
				$value = $reader->readString();
			} elseif ($type === 'inlineStr' && $reader->nodeType === XMLReader::ELEMENT && $reader->localName === 't') {
				$value .= $reader->readString();
			}
		}
		if ($type === 's') {
			$i = (int) $value;
			return isset($shared[$i]) ? $shared[$i] : '';
		}
		return $value;
	}

	protected static function colIndex($letters) {
		$n = 0;
		$len = strlen($letters);
		for ($i = 0; $i < $len; $i++) {
			$n = $n * 26 + (ord($letters[$i]) - 64);
		}
		return $n;
	}

	protected static function excelDateTime($raw) {
		$raw = trim((string) $raw);
		if ($raw === '' || !is_numeric($raw)) {
			return '';
		}
		$serial = (float) $raw;
		if ($serial < 20000 || $serial > 80000) {
			return '';
		}
		$days = (int) floor($serial);
		$seconds = (int) round(($serial - $days) * 86400);
		if ($seconds >= 86400) {
			$days++;
			$seconds -= 86400;
		}
		$dt = DateTime::createFromFormat('Y-m-d', '1899-12-30');
		if (!$dt) {
			return '';
		}
		$dt->modify('+' . $days . ' days');
		$dt->modify('+' . $seconds . ' seconds');
		return $dt->format('Y-m-d H:i:s');
	}

	protected static function num($v) {
		$v = trim((string) $v);
		if ($v === '') {
			return 0.0;
		}
		$v = str_replace(array(',', ' '), '', $v);
		return (float) $v;
	}

	protected static function clip($s, $len) {
		$s = trim((string) $s);
		if (function_exists('mb_substr')) {
			return mb_substr($s, 0, $len, 'UTF-8');
		}
		return substr($s, 0, $len);
	}

	protected static function fold($s) {
		$s = mb_strtolower(trim((string) $s), 'UTF-8');
		$s = strtr($s, array(
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
		));
		$s = preg_replace('/[^a-z0-9]+/', '_', $s);
		return trim($s, '_');
	}
}
