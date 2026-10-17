<?php
/*+***********************************************************************************
 * Import Kiot "Chi tiết đặt hàng" into Quotes as Phiếu tạm.
 * Each Mã đặt hàng becomes one quote. Does not create sales orders or products.
 *************************************************************************************/

require_once 'modules/SalesOrder/helpers/KiotSimpleImport.php';
require_once 'modules/Quotes/helpers/QuoteBaService.php';

class Quotes_KiotQuoteImport_Helper {

	public static function importUploadedFile(array $fileInfo, $userId = 0) {
		@set_time_limit(0);
		@ini_set('memory_limit', '512M');

		$path = isset($fileInfo['tmp_name']) ? (string) $fileInfo['tmp_name'] : '';
		$origName = isset($fileInfo['name']) ? (string) $fileInfo['name'] : '';
		if ($path === '' || !is_file($path)) {
			throw new Exception('Chưa chọn file Excel (.xlsx).');
		}
		if (!preg_match('/\.xlsx$/i', $origName) && !preg_match('/\.xlsx$/i', $path)) {
			throw new Exception('Chỉ hỗ trợ file .xlsx (chi tiết đặt hàng).');
		}

		$userId = (int) $userId;
		if ($userId <= 0) {
			global $current_user;
			$userId = isset($current_user->id) ? (int) $current_user->id : 1;
		}

		$parsed = SalesOrder_KiotSimpleImport_Helper::parseOrders($path);
		$orders = $parsed['orders'];
		if (empty($orders)) {
			throw new Exception('Không đọc được phiếu tạm. File cần cột Mã đặt hàng và Mã hàng.');
		}

		$db = PearDatabase::getInstance();
		$accounts = self::loadCodeMap($db, 'account');
		$contacts = self::loadCodeMap($db, 'contact');
		$existing = self::loadExistingQuotes($db);
		$catalog = self::loadCatalog($db);
		$quoteCols = self::columnSet($db, 'vtiger_quotes');
		$ceCols = self::columnSet($db, 'vtiger_crmentity');
		$lineCols = self::columnSet($db, 'vtiger_inventoryproductrel');
		$hasCf = self::tableExists($db, 'vtiger_quotescf');
		$draftStage = Quotes_QuoteBaService_Helper::resolveDraftQuoteStage();

		$stats = array(
			'excel_rows' => (int) $parsed['rows'],
			'orders' => count($orders),
			'created' => 0,
			'updated' => 0,
			'lines' => 0,
			'unlinked_customer' => 0,
			'unmatched_sku' => 0,
			'skipped' => (int) $parsed['skipped'],
			'errors' => 0,
		);
		$errorSamples = array();
		$skuSamples = array();

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
					$sku = trim((string) $line['sku']);
					$product = self::findProduct($catalog, $sku);
					if ($product === null) {
						$stats['unmatched_sku']++;
						if ($sku !== '' && count($skuSamples) < 8 && !in_array($sku, $skuSamples, true)) {
							$skuSamples[] = $sku;
						}
						continue;
					}
					$line['productId'] = (int) $product['id'];
					if ($line['name'] === '' && $product['name'] !== '') {
						$line['name'] = $product['name'];
					}
					$lineRows[] = $line;
				}
				if (empty($lineRows)) {
					$stats['skipped']++;
					continue;
				}
				$order['lines'] = $lineRows;
				$order['quotestage'] = ($order['status'] === 'Cancelled') ? 'Rejected' : $draftStage;
				if (isset($existing[$code])) {
					self::updateQuote($db, (int) $existing[$code]['id'], $order, $customer, $userId, $quoteCols, $ceCols, $lineCols);
					$stats['updated']++;
				} else {
					$newId = self::createQuote($db, $order, $customer, $userId, $quoteCols, $ceCols, $lineCols, $hasCf);
					$existing[$code] = array('id' => $newId, 'deleted' => 0);
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
		$stats['sku_samples'] = $skuSamples;
		$stats['message'] = self::buildResultMessage($stats);
		$stats['success'] = ($stats['created'] + $stats['updated']) > 0;
		return $stats;
	}

	public static function buildResultMessage(array $stats) {
		$msg = sprintf(
			"Import báo giá xong.\n- Dòng Excel: %d\n- Phiếu đã gộp theo mã: %d\n- Tạo mới: %d\n- Cập nhật: %d\n- Dòng hàng: %d\n- Phiếu chưa gắn được khách: %d\n- Dòng không khớp mã hàng: %d\n- Bỏ qua: %d\n- Lỗi: %d\nKhông tạo đơn hàng và không tạo hàng hoá mới.",
			(int) $stats['excel_rows'],
			(int) $stats['orders'],
			(int) $stats['created'],
			(int) $stats['updated'],
			(int) $stats['lines'],
			(int) $stats['unlinked_customer'],
			(int) $stats['unmatched_sku'],
			(int) $stats['skipped'],
			(int) $stats['errors']
		);
		if (!empty($stats['sku_samples'])) {
			$msg .= "\nMã hàng chưa có: " . implode(', ', $stats['sku_samples']);
		}
		if (!empty($stats['error_samples'])) {
			$msg .= "\n" . implode("\n", $stats['error_samples']);
		}
		return $msg;
	}

	protected static function createQuote(PearDatabase $db, array $order, array $customer, $userId, array $quoteCols, array $ceCols, array $lineCols, $hasCf) {
		$id = (int) $db->getUniqueID('vtiger_crmentity');
		if ($id <= 0) {
			throw new Exception('Không cấp được mã báo giá.');
		}
		$now = date('Y-m-d H:i:s');
		$created = $order['when'] !== '' ? $order['when'] : $now;
		$subject = self::clip($order['customerName'] !== '' ? $order['customerName'] : $order['code'], 100);
		$description = self::descriptionOf($order);

		self::insertRow($db, 'vtiger_crmentity', array(
			'crmid' => $id,
			'smcreatorid' => (int) $userId,
			'smownerid' => (int) $userId,
			'modifiedby' => (int) $userId,
			'setype' => 'Quotes',
			'description' => $description,
			'createdtime' => $created,
			'modifiedtime' => $now,
			'label' => $subject,
			'deleted' => 0,
		), $ceCols);
		self::insertRow($db, 'vtiger_quotes', self::quoteValues($id, $order, $customer, $subject), $quoteCols);
		self::writeAddress($db, $id, $order['address'], false);
		if ($hasCf) {
			try {
				$db->pquery('INSERT INTO vtiger_quotescf (quoteid) VALUES (?)', array($id));
			} catch (Exception $ignoreCf) {
			}
		}
		self::writeLines($db, $id, $order['lines'], $lineCols);
		return $id;
	}

	protected static function updateQuote(PearDatabase $db, $id, array $order, array $customer, $userId, array $quoteCols, array $ceCols, array $lineCols) {
		$id = (int) $id;
		$now = date('Y-m-d H:i:s');
		$subject = self::clip($order['customerName'] !== '' ? $order['customerName'] : $order['code'], 100);
		$ce = array(
			'description' => self::descriptionOf($order),
			'modifiedtime' => $now,
			'modifiedby' => (int) $userId,
			'label' => $subject,
			'deleted' => 0,
			'setype' => 'Quotes',
		);
		if ($order['when'] !== '') {
			$ce['createdtime'] = $order['when'];
		}
		self::updateRow($db, 'vtiger_crmentity', $ce, $ceCols, 'crmid', $id);
		$values = self::quoteValues($id, $order, $customer, $subject);
		unset($values['quoteid']);
		self::updateRow($db, 'vtiger_quotes', $values, $quoteCols, 'quoteid', $id);
		self::writeAddress($db, $id, $order['address'], true);
		$db->pquery('DELETE FROM vtiger_inventoryproductrel WHERE id = ?', array($id));
		self::writeLines($db, $id, $order['lines'], $lineCols);
	}

	protected static function quoteValues($id, array $order, array $customer, $subject) {
		$grand = $order['grand'] > 0 ? $order['grand'] : max(0, $order['subtotal'] - $order['discount']);
		$accountId = ($customer['kind'] === 'account' && $customer['id'] > 0) ? (int) $customer['id'] : null;
		$contactId = ($customer['kind'] === 'contact' && $customer['id'] > 0) ? (int) $customer['id'] : null;
		$till = $order['when'] !== '' ? substr($order['when'], 0, 10) : date('Y-m-d');
		return array(
			'quoteid' => (int) $id,
			'subject' => $subject,
			'quote_no' => self::clip($order['code'], 100),
			'quotestage' => $order['quotestage'],
			'validtill' => $till,
			'contactid' => $contactId,
			'accountid' => $accountId,
			'subtotal' => $order['subtotal'],
			'total' => $grand,
			'pre_tax_total' => $grand,
			'taxtype' => 'group',
			'discount_percent' => null,
			'discount_amount' => $order['discount'],
			's_h_amount' => 0,
			'adjustment' => 0,
			'currency_id' => 1,
			'conversion_rate' => 1,
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
			$rs = $db->pquery('SELECT quotebilladdressid FROM vtiger_quotesbillads WHERE quotebilladdressid = ?', array($id));
			if ($rs && $db->num_rows($rs) > 0) {
				$db->pquery('UPDATE vtiger_quotesbillads SET bill_street = ? WHERE quotebilladdressid = ?', array($street, $id));
			} else {
				$db->pquery('INSERT INTO vtiger_quotesbillads (quotebilladdressid, bill_street) VALUES (?, ?)', array($id, $street));
			}
			$rs2 = $db->pquery('SELECT quoteshipaddressid FROM vtiger_quotesshipads WHERE quoteshipaddressid = ?', array($id));
			if ($rs2 && $db->num_rows($rs2) > 0) {
				$db->pquery('UPDATE vtiger_quotesshipads SET ship_street = ? WHERE quoteshipaddressid = ?', array($street, $id));
			} else {
				$db->pquery('INSERT INTO vtiger_quotesshipads (quoteshipaddressid, ship_street) VALUES (?, ?)', array($id, $street));
			}
			return;
		}
		$db->pquery('INSERT INTO vtiger_quotesbillads (quotebilladdressid, bill_street) VALUES (?, ?)', array($id, $street));
		$db->pquery('INSERT INTO vtiger_quotesshipads (quoteshipaddressid, ship_street) VALUES (?, ?)', array($id, $street));
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

	protected static function findProduct(array $catalog, $sku) {
		$sku = trim((string) $sku);
		if ($sku === '') {
			return null;
		}
		if (isset($catalog[$sku])) {
			return $catalog[$sku];
		}
		$key = self::fold($sku);
		if ($key !== '' && isset($catalog['fold:' . $key])) {
			return $catalog['fold:' . $key];
		}
		return null;
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
		while ($rs && ($row = $db->fetchByAssoc($rs, -1, false))) {
			$sku = trim(html_entity_decode((string) $row['sku'], ENT_QUOTES, 'UTF-8'));
			if ($sku === '') {
				continue;
			}
			$item = array(
				'id' => (int) $row['id'],
				'name' => trim(html_entity_decode((string) $row['name'], ENT_QUOTES, 'UTF-8')),
			);
			$catalog[$sku] = $item;
			$fold = self::fold($sku);
			if ($fold !== '' && !isset($catalog['fold:' . $fold])) {
				$catalog['fold:' . $fold] = $item;
			}
		}
		return $catalog;
	}

	protected static function loadExistingQuotes(PearDatabase $db) {
		$map = array();
		$rs = $db->pquery(
			'SELECT q.quoteid, q.quote_no, ce.deleted
			 FROM vtiger_quotes q
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid
			 WHERE IFNULL(q.quote_no, \'\') <> \'\'',
			array()
		);
		while ($rs && ($row = $db->fetchByAssoc($rs, -1, false))) {
			$code = trim(html_entity_decode((string) $row['quote_no'], ENT_QUOTES, 'UTF-8'));
			if ($code === '') {
				continue;
			}
			$deleted = (int) $row['deleted'];
			if (!isset($map[$code]) || ($map[$code]['deleted'] === 1 && $deleted === 0)) {
				$map[$code] = array('id' => (int) $row['quoteid'], 'deleted' => $deleted);
			}
		}
		return $map;
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
		while ($rs && ($row = $db->fetchByAssoc($rs, -1, false))) {
			$code = trim(html_entity_decode((string) $row['code'], ENT_QUOTES, 'UTF-8'));
			if ($code === '') {
				continue;
			}
			$map[self::fold($code)] = (int) $row['id'];
		}
		return $map;
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
