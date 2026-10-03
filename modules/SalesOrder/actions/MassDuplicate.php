<?php
/**
 * Mass-duplicate Sales Orders (header + line items), new records start as Phiếu tạm (Created).
 * Doc no uses the next module sequence (SO34, SO35, …) — never "{source} (copy)".
 */
class SalesOrder_MassDuplicate_Action extends Vtiger_Mass_Action {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted('SalesOrder', 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		if (!Users_Privileges_Model::isPermitted('Quotes', 'CreateView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		$selectedIds = $request->get('selected_ids');
		if (is_string($selectedIds)) {
			$decoded = json_decode($selectedIds, true);
			if (is_array($decoded)) {
				$selectedIds = $decoded;
			} elseif ($selectedIds !== '' && strtolower($selectedIds) !== 'all') {
				$selectedIds = array_filter(array_map('trim', explode(',', $selectedIds)));
			}
		}

		$recordIds = array();
		if (is_array($selectedIds) && !empty($selectedIds) && !(count($selectedIds) === 1 && strtolower((string) $selectedIds[0]) === 'all')) {
			$recordIds = array_values(array_unique(array_filter(array_map('intval', $selectedIds))));
		} else {
			$fromRequest = $this->getRecordsListFromRequest($request);
			if (is_array($fromRequest)) {
				$recordIds = $fromRequest;
			}
		}
		$created = array();
		$errors = array();

		foreach ($recordIds as $recordId) {
			$recordId = (int) $recordId;
			if ($recordId <= 0) {
				continue;
			}
			if (!Users_Privileges_Model::isPermitted('SalesOrder', 'DetailView', $recordId)) {
				$errors[] = 'Không có quyền đọc đơn hàng #' . $recordId;
				continue;
			}
			try {
				$newId = $this->duplicateSalesOrder($recordId);
				if ($newId > 0) {
					$created[] = $newId;
				} else {
					$errors[] = 'Không nhân bản được đơn hàng #' . $recordId;
				}
			} catch (Exception $e) {
				$errors[] = $e->getMessage() ? $e->getMessage() : ('Lỗi nhân bản #' . $recordId);
			} catch (Error $e) {
				$errors[] = $e->getMessage() ? $e->getMessage() : ('Lỗi nhân bản #' . $recordId);
			}
		}

		$response = new Vtiger_Response();
		$response->setResult(array(
			'success' => count($created) > 0,
			'created' => $created,
			'created_count' => count($created),
			'target_module' => 'Quotes',
			'errors' => $errors,
			'message' => count($created) > 0
				? ('Đã tạo ' . count($created) . ' Báo giá từ Đơn hàng.')
				: 'Không tạo được Báo giá nào.',
		));
		$response->emit();
	}

	/**
	 * Create a new Quote from this Sales Order (independent copy).
	 * @param int $sourceId
	 * @return int new Quote crmid
	 */
	protected function duplicateSalesOrder($sourceId) {
		$sourceId = (int) $sourceId;
		$source = Inventory_Record_Model::getInstanceById($sourceId, 'SalesOrder');
		if (!$source) {
			throw new Exception('Không tìm thấy đơn hàng #' . $sourceId);
		}

		if (!Users_Privileges_Model::isPermitted('Quotes', 'CreateView')) {
			throw new Exception('Không có quyền tạo Báo giá.');
		}

		$sourceSubject = trim((string) $source->get('subject'));

		require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
		$accountId = (int) Vtiger_MkSalesCustomerName_Helper::extractRawIdPublic($source, array('account_id', 'accountid'));
		$contactId = (int) Vtiger_MkSalesCustomerName_Helper::extractRawIdPublic($source, array('contact_id', 'contactid'));
		$potentialId = (int) Vtiger_MkSalesCustomerName_Helper::extractRawIdPublic($source, array('potential_id', 'potentialid'));
		$customerLabel = Vtiger_MkSalesCustomerName_Helper::resolveListStyleName($source);

		$newModel = Vtiger_Record_Model::getCleanInstance('Quotes');
		$copyFields = array(
			'subject', 'currency_id',
			'description', 'terms_conditions',
			'bill_street', 'bill_city', 'bill_state', 'bill_code', 'bill_country', 'bill_pobox',
			'ship_street', 'ship_city', 'ship_state', 'ship_code', 'ship_country', 'ship_pobox',
			'assigned_user_id', 'taxtype',
		);
		foreach ($copyFields as $fieldName) {
			$value = $source->get($fieldName);
			if ($value !== null && $value !== '') {
				$newModel->set($fieldName, $value);
			}
		}
		if ($accountId > 0) {
			$newModel->set('account_id', $accountId);
		}
		if ($contactId > 0) {
			$newModel->set('contact_id', $contactId);
		}
		if ($potentialId > 0) {
			$newModel->set('potential_id', $potentialId);
		}

		$newModel->set('mode', '');
		$newModel->set('id', '');
		$newModel->set('record', '');
		$newModel->set('quote_no', '');
		$draftStage = 'Created';
		$baHelper = 'modules/Quotes/helpers/QuoteBaService.php';
		if (file_exists($baHelper)) {
			require_once $baHelper;
			if (class_exists('Quotes_QuoteBaService_Helper') && method_exists('Quotes_QuoteBaService_Helper', 'resolveDraftQuoteStage')) {
				$draftStage = Quotes_QuoteBaService_Helper::resolveDraftQuoteStage();
			}
		}
		$newModel->set('quotestage', $draftStage);
		$newModel->set('validtill', date('Y-m-d', strtotime('+30 days')));

		$rate = $this->sanitizeConversionRate($source->get('conversion_rate'));
		$newModel->set('conversion_rate', $rate);
		foreach (array(
			'hdnGrandTotal', 'hdnSubTotal', 'total', 'subtotal', 'pre_tax_total',
			'discount_amount', 'discount_percent', 's_h_amount', 'adjustment',
			'txtAdjustment', 'shipping_handling_charge',
		) as $moneyField) {
			$newModel->set($moneyField, 0);
		}

		if ($sourceSubject !== '') {
			$cleanSubject = trim(preg_replace('/\s*\(copy(?:\s+\d+)?\)\s*$/i', '', $sourceSubject));
			if ($cleanSubject !== '') {
				$newModel->set('subject', $cleanSubject);
			}
		}
		if (trim((string) $newModel->get('subject')) === '') {
			$soNo = trim((string) $source->get('salesorder_no'));
			if ($customerLabel !== '' && $customerLabel !== '--' && $customerLabel !== '—') {
				$fallbackSubject = $customerLabel;
			} else {
				$fallbackSubject = $soNo !== '' ? ('Báo giá từ ' . $soNo) : ('Báo giá từ ĐH #' . $sourceId);
			}
			$newModel->set('subject', $fallbackSubject);
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		if ($newModel->get('assigned_user_id') === '' || $newModel->get('assigned_user_id') === null) {
			$newModel->set('assigned_user_id', $currentUser->getId());
		}

		$entity = method_exists($newModel, 'getEntity') ? $newModel->getEntity() : null;
		if ($entity) {
			$entity->isLineItemUpdate = false;
			if (isset($entity->column_fields) && is_array($entity->column_fields)) {
				$entity->column_fields['conversion_rate'] = $rate;
				$entity->column_fields['subject'] = (string) $newModel->get('subject');
				if ($accountId > 0) {
					$entity->column_fields['account_id'] = $accountId;
					$entity->column_fields['accountid'] = $accountId;
				}
				if ($contactId > 0) {
					$entity->column_fields['contact_id'] = $contactId;
					$entity->column_fields['contactid'] = $contactId;
				}
				if ($potentialId > 0) {
					$entity->column_fields['potential_id'] = $potentialId;
					$entity->column_fields['potentialid'] = $potentialId;
				}
				foreach (array('total', 'subtotal', 'pre_tax_total', 'discount_amount', 's_h_amount', 'adjustment', 'quote_no') as $fieldName) {
					if ($fieldName === 'quote_no') {
						$entity->column_fields[$fieldName] = '';
					} else {
						$entity->column_fields[$fieldName] = 0;
					}
				}
			}
		}
		$requestBackup = $_REQUEST;
		$_REQUEST['totalProductCount'] = 0;
		$_REQUEST['action'] = 'SaveAjax';
		$_REQUEST['module'] = 'Quotes';
		foreach (array_keys($_REQUEST) as $reqKey) {
			if (preg_match('/^(listPrice|qty|hdnProductId|productName|comment|purchaseCost|margin|discount)\d+$/', $reqKey)) {
				unset($_REQUEST[$reqKey]);
			}
		}

		try {
			$newModel->save();
		} finally {
			$_REQUEST = $requestBackup;
		}
		$newId = (int) $newModel->getId();
		if ($newId <= 0) {
			throw new Exception('Không lưu được Báo giá.');
		}

		$this->copyInventoryLines($sourceId, $newId);
		try {
			$this->syncLinePricesFromSource($sourceId, $newId);
			$this->repairZeroLinePrices($newId, $sourceId);
			$this->copyQuoteTotalsFromSalesOrder($sourceId, $newId);
			$this->ensureQuoteTotalsFromLines($newId, $sourceId);
			$this->fixQuoteInflatedMoney($sourceId, $newId);
			$this->ensureQuoteDocNumber($newId);
			$this->persistQuoteIdentity($newId, $source, $accountId, $contactId, $potentialId);
			$this->clearFranchiseQuoteLink($newId);
			$this->touchQuoteModifiedTime($newId);
		} catch (Exception $e) {
			$this->ensureQuoteDocNumber($newId);
			$this->persistQuoteIdentity($newId, $source, $accountId, $contactId, $potentialId);
			$this->clearFranchiseQuoteLink($newId);
			$this->touchQuoteModifiedTime($newId);
		} catch (Error $e) {
			$this->ensureQuoteDocNumber($newId);
			$this->persistQuoteIdentity($newId, $source, $accountId, $contactId, $potentialId);
			$this->clearFranchiseQuoteLink($newId);
			$this->touchQuoteModifiedTime($newId);
		}
		$this->assertQuoteHasLines($newId, $sourceId);

		return $newId;
	}

	/**
	 * conversion_rate "1.00000000" must stay ~1 — never become 100000000 via VN dot-stripping.
	 */
	protected function sanitizeConversionRate($raw) {
		if (is_string($raw)) {
			$raw = trim($raw);
		}
		if ($raw === null || $raw === '') {
			return 1.0;
		}
		// Plain DB decimal / numeric.
		if (is_numeric($raw) && (is_int($raw) || is_float($raw) || (is_string($raw) && substr_count($raw, '.') <= 1 && strpos($raw, ',') === false))) {
			$rate = (float) $raw;
		} else {
			$rate = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $raw));
		}
		if ($rate <= 0) {
			return 1.0;
		}
		// Detect classic strip-dot corruption: 1.00000000 → 100000000 (power of 10, huge).
		if ($rate >= 1000) {
			$log = log10($rate);
			if (abs($log - round($log)) < 0.0001) {
				return 1.0;
			}
		}
		return $rate;
	}

	protected function toPlainMoney($raw) {
		if (is_int($raw) || is_float($raw)) {
			return (float) $raw;
		}
		$s = trim((string) $raw);
		if ($s === '') {
			return 0.0;
		}
		// VN thousands with multiple dots: 7.560.000
		if (substr_count($s, '.') >= 2) {
			$s = str_replace('.', '', $s);
			$s = str_replace(',', '.', $s);
			return (float) preg_replace('/[^\d.\-]/', '', $s);
		}
		// VN with decimal comma: 1.234,56 or 1234,5
		if (strpos($s, ',') !== false) {
			$s = str_replace('.', '', $s);
			$s = str_replace(',', '.', $s);
			return (float) preg_replace('/[^\d.\-]/', '', $s);
		}
		// DB decimals must stay as-is: "1.000" = 1.0, NOT 1000.
		if (is_numeric($s)) {
			return (float) $s;
		}
		return (float) preg_replace('/[^\d.\-]/', '', $s);
	}

	protected function copyInventoryLines($sourceId, $newId) {
		$db = PearDatabase::getInstance();
		$db->pquery('DELETE FROM vtiger_inventoryproductrel WHERE id = ?', array($newId));
		$db->pquery('DELETE FROM vtiger_inventorysubproductrel WHERE id = ?', array($newId));
		$db->pquery('DELETE FROM vtiger_inventorychargesrel WHERE recordid = ?', array($newId));

		$colsRs = $db->pquery('SHOW COLUMNS FROM vtiger_inventoryproductrel', array());
		if (!$colsRs || $db->num_rows($colsRs) <= 0) {
			return;
		}
		$columns = array();
		while ($colRow = $db->fetchByAssoc($colsRs, -1, false)) {
			$field = isset($colRow['field']) ? $colRow['field'] : (isset($colRow['Field']) ? $colRow['Field'] : '');
			$field = strtolower(trim((string) $field));
			if ($field === '' || $field === 'lineitem_id') {
				continue;
			}
			$columns[] = $field;
		}
		if (empty($columns) || !in_array('id', $columns, true)) {
			return;
		}

		$selectParts = array();
		foreach ($columns as $col) {
			if ($col === 'id') {
				$selectParts[] = '?';
			} else {
				$selectParts[] = '`' . $col . '`';
			}
		}
		$sql = 'INSERT INTO vtiger_inventoryproductrel (`' . implode('`,`', $columns) . '`) '
			. 'SELECT ' . implode(',', $selectParts) . ' FROM vtiger_inventoryproductrel WHERE id = ? ORDER BY sequence_no ASC';
		$db->pquery($sql, array($newId, $sourceId));

		// Sub-products
		$subRs = $db->pquery('SELECT * FROM vtiger_inventorysubproductrel WHERE id = ?', array($sourceId));
		if ($subRs && $db->num_rows($subRs) > 0) {
			while ($sub = $db->fetchByAssoc($subRs, -1, false)) {
				$db->pquery(
					'INSERT INTO vtiger_inventorysubproductrel (id, sequence_no, productid, quantity) VALUES (?,?,?,?)',
					array(
						$newId,
						(int) ($sub['sequence_no'] ?? 0),
						(int) ($sub['productid'] ?? 0),
						(float) ($sub['quantity'] ?? 1),
					)
				);
			}
		}
	}

	/**
	 * Force line money to match source by sequence (belt-and-suspenders after INSERT…SELECT).
	 */
	protected function syncLinePricesFromSource($sourceId, $newId) {
		$db = PearDatabase::getInstance();
		$src = $db->pquery(
			'SELECT sequence_no, quantity, listprice FROM vtiger_inventoryproductrel WHERE id = ? ORDER BY sequence_no ASC',
			array($sourceId)
		);
		if (!$src || $db->num_rows($src) <= 0) {
			return;
		}
		for ($i = 0; $i < $db->num_rows($src); $i++) {
			$seq = (int) $db->query_result($src, $i, 'sequence_no');
			// Quantity is NOT money — never run VN thousand heuristics (1.000 must stay 1).
			$qty = (float) $db->query_result($src, $i, 'quantity');
			$price = $this->toPlainMoney($db->query_result($src, $i, 'listprice'));
			if ($qty <= 0) {
				$qty = 1.0;
			}
			$db->pquery(
				'UPDATE vtiger_inventoryproductrel SET quantity = ?, listprice = ? WHERE id = ? AND sequence_no = ?',
				array($qty, $price, $newId, $seq)
			);
		}
	}

	/**
	 * If duplicate money was inflated (~1e8 from VN dot-strip), restore from source.
	 */
	protected function fixQuoteInflatedMoney($sourceId, $newId) {
		$db = PearDatabase::getInstance();
		$src = $db->pquery('SELECT total, subtotal, conversion_rate FROM vtiger_salesorder WHERE salesorderid = ?', array($sourceId));
		$dst = $db->pquery('SELECT total, subtotal, conversion_rate FROM vtiger_quotes WHERE quoteid = ?', array($newId));
		if (!$src || !$dst || $db->num_rows($src) <= 0 || $db->num_rows($dst) <= 0) {
			return;
		}
		$srcTotal = $this->toPlainMoney($db->query_result($src, 0, 'total'));
		$dstTotal = $this->toPlainMoney($db->query_result($dst, 0, 'total'));
		$rate = $this->sanitizeConversionRate($db->query_result($dst, 0, 'conversion_rate'));

		$inflated = ($srcTotal > 0 && $dstTotal > ($srcTotal * 50));
		if (!$inflated) {
			$lineSrc = $db->pquery(
				'SELECT COALESCE(SUM(quantity * listprice), 0) AS s FROM vtiger_inventoryproductrel WHERE id = ?',
				array($sourceId)
			);
			$lineDst = $db->pquery(
				'SELECT COALESCE(SUM(quantity * listprice), 0) AS s FROM vtiger_inventoryproductrel WHERE id = ?',
				array($newId)
			);
			$ls = $lineSrc ? $this->toPlainMoney($db->query_result($lineSrc, 0, 's')) : 0;
			$ld = $lineDst ? $this->toPlainMoney($db->query_result($lineDst, 0, 's')) : 0;
			$inflated = ($ls > 0 && $ld > ($ls * 50));
		}

		if ($inflated) {
			$this->syncLinePricesFromSource($sourceId, $newId);
			$this->copyQuoteTotalsFromSalesOrder($sourceId, $newId);
		} else {
			$db->pquery(
				'UPDATE vtiger_quotes SET conversion_rate = ? WHERE quoteid = ?',
				array($rate, $newId)
			);
		}
	}

	/**
	 * When source lines were saved with listprice=0, fill from product/service unit price
	 * or distribute source header subtotal.
	 */
	protected function repairZeroLinePrices($newId, $sourceId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT lineitem_id, productid, quantity, listprice FROM vtiger_inventoryproductrel WHERE id = ? ORDER BY sequence_no ASC',
			array($newId)
		);
		if (!$rs || $db->num_rows($rs) <= 0) {
			return;
		}

		$headerSub = 0.0;
		$hdr = $db->pquery('SELECT subtotal, pre_tax_total FROM vtiger_salesorder WHERE salesorderid = ?', array($sourceId));
		if ($hdr && $db->num_rows($hdr) > 0) {
			$headerSub = $this->toPlainMoney($db->query_result($hdr, 0, 'subtotal'));
			if ($headerSub <= 0) {
				$headerSub = $this->toPlainMoney($db->query_result($hdr, 0, 'pre_tax_total'));
			}
		}

		$rows = array();
		$zeroCount = 0;
		for ($i = 0; $i < $db->num_rows($rs); $i++) {
			$lineId = (int) $db->query_result($rs, $i, 'lineitem_id');
			$productId = (int) $db->query_result($rs, $i, 'productid');
			$qty = $this->toPlainMoney($db->query_result($rs, $i, 'quantity'));
			$price = $this->toPlainMoney($db->query_result($rs, $i, 'listprice'));
			if ($qty <= 0) {
				$qty = 1.0;
			}
			$needsFix = ($price <= 0);
			if ($needsFix) {
				$zeroCount++;
				$unit = $this->resolveProductUnitPrice($productId);
				if ($unit > 0) {
					$price = $unit;
				}
			}
			$rows[] = array(
				'lineitem_id' => $lineId,
				'qty' => $qty,
				'price' => $price,
				'needs_fix' => $needsFix,
			);
		}

		if ($zeroCount <= 0) {
			return;
		}

		if ($headerSub > 0) {
			$stillZeroQty = 0.0;
			foreach ($rows as $row) {
				if ($row['price'] <= 0) {
					$stillZeroQty += $row['qty'];
				}
			}
			if ($stillZeroQty > 0) {
				$sharePrice = $headerSub / $stillZeroQty;
				foreach ($rows as &$row) {
					if ($row['price'] <= 0) {
						$row['price'] = $sharePrice;
						$row['needs_fix'] = true;
					}
				}
				unset($row);
			}
		}

		foreach ($rows as $row) {
			if (!empty($row['needs_fix']) && $row['price'] > 0) {
				$db->pquery(
					'UPDATE vtiger_inventoryproductrel SET listprice = ? WHERE lineitem_id = ?',
					array($row['price'], $row['lineitem_id'])
				);
			}
		}
	}

	protected function resolveProductUnitPrice($productId) {
		$productId = (int) $productId;
		if ($productId <= 0) {
			return 0.0;
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery('SELECT unit_price FROM vtiger_products WHERE productid = ?', array($productId));
		if ($rs && $db->num_rows($rs) > 0) {
			$price = (float) $db->query_result($rs, 0, 'unit_price');
			if ($price > 0) {
				return $price;
			}
		}
		$rs = $db->pquery('SELECT unit_price FROM vtiger_service WHERE serviceid = ?', array($productId));
		if ($rs && $db->num_rows($rs) > 0) {
			return (float) $db->query_result($rs, 0, 'unit_price');
		}
		return 0.0;
	}

	/**
	 * Copy header totals from source Sales Order into the new Quote record.
	 */
	protected function copyQuoteTotalsFromSalesOrder($sourceId, $newId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT subtotal, total, taxtype, discount_percent, discount_amount, s_h_amount, adjustment, pre_tax_total,
			        currency_id, conversion_rate, accountid, contactid, potentialid
			 FROM vtiger_salesorder WHERE salesorderid = ?',
			array($sourceId)
		);
		if (!$rs || $db->num_rows($rs) <= 0) {
			return;
		}
		$subtotal = $this->toPlainMoney($db->query_result($rs, 0, 'subtotal'));
		$total = $this->toPlainMoney($db->query_result($rs, 0, 'total'));
		$preTax = $this->toPlainMoney($db->query_result($rs, 0, 'pre_tax_total'));
		$discountAmount = $this->toPlainMoney($db->query_result($rs, 0, 'discount_amount'));
		$discountPercent = $this->toPlainMoney($db->query_result($rs, 0, 'discount_percent'));
		$shipping = $this->toPlainMoney($db->query_result($rs, 0, 's_h_amount'));
		$adjustment = $this->toPlainMoney($db->query_result($rs, 0, 'adjustment'));
		$rate = $this->sanitizeConversionRate($db->query_result($rs, 0, 'conversion_rate'));

		if ($subtotal > 0 && $total <= ($subtotal + 1)) {
			$discount = $discountAmount;
			if ($discount <= 0 && $discountPercent > 0) {
				$discount = $subtotal * $discountPercent / 100;
			}
			$total = $subtotal - $discount + $shipping + $adjustment;
		}
		if ($preTax <= 0) {
			$preTax = $subtotal;
		}

		$db->pquery(
			'UPDATE vtiger_quotes SET
				subtotal = ?, total = ?, taxtype = ?, discount_percent = ?, discount_amount = ?,
				s_h_amount = ?, adjustment = ?, pre_tax_total = ?,
				currency_id = ?, conversion_rate = ?,
				accountid = IF(? > 0, ?, accountid),
				contactid = IF(? > 0, ?, contactid),
				potentialid = IF(? > 0, ?, potentialid)
			 WHERE quoteid = ?',
			array(
				$subtotal,
				$total,
				$db->query_result($rs, 0, 'taxtype'),
				$discountPercent,
				$discountAmount,
				$shipping,
				$adjustment,
				$preTax > 0 ? $preTax : $subtotal,
				(int) $db->query_result($rs, 0, 'currency_id'),
				$rate,
				(int) $db->query_result($rs, 0, 'accountid'), (int) $db->query_result($rs, 0, 'accountid'),
				(int) $db->query_result($rs, 0, 'contactid'), (int) $db->query_result($rs, 0, 'contactid'),
				(int) $db->query_result($rs, 0, 'potentialid'), (int) $db->query_result($rs, 0, 'potentialid'),
				$newId,
			)
		);
	}

	/**
	 * Rebuild Quote header totals from line items when copy left zeros.
	 */
	protected function ensureQuoteTotalsFromLines($newId, $sourceId) {
		$db = PearDatabase::getInstance();
		$cur = $db->pquery('SELECT subtotal, total FROM vtiger_quotes WHERE quoteid = ?', array($newId));
		$subtotal = $cur ? $this->toPlainMoney($db->query_result($cur, 0, 'subtotal')) : 0;
		$total = $cur ? $this->toPlainMoney($db->query_result($cur, 0, 'total')) : 0;

		$lineRs = $db->pquery(
			'SELECT COALESCE(SUM(quantity * listprice), 0) AS line_subtotal FROM vtiger_inventoryproductrel WHERE id = ?',
			array($newId)
		);
		$lineSub = $lineRs ? $this->toPlainMoney($db->query_result($lineRs, 0, 'line_subtotal')) : 0;
		if ($lineSub <= 0) {
			return;
		}

		$src = $db->pquery(
			'SELECT subtotal, total, discount_amount, discount_percent, s_h_amount, adjustment FROM vtiger_salesorder WHERE salesorderid = ?',
			array($sourceId)
		);
		$srcSub = $src ? $this->toPlainMoney($db->query_result($src, 0, 'subtotal')) : 0;
		$srcTotal = $src ? $this->toPlainMoney($db->query_result($src, 0, 'total')) : 0;
		$discount = $src ? $this->toPlainMoney($db->query_result($src, 0, 'discount_amount')) : 0;
		$discountPct = $src ? $this->toPlainMoney($db->query_result($src, 0, 'discount_percent')) : 0;
		$shipping = $src ? $this->toPlainMoney($db->query_result($src, 0, 's_h_amount')) : 0;
		$adjustment = $src ? $this->toPlainMoney($db->query_result($src, 0, 'adjustment')) : 0;
		if ($discount <= 0 && $discountPct > 0) {
			$discount = $lineSub * $discountPct / 100;
		}

		if ($srcTotal > 0 && $srcSub > 0 && abs($lineSub - $srcSub) < 1) {
			$db->pquery(
				'UPDATE vtiger_quotes SET subtotal = ?, pre_tax_total = ?, total = ?,
					discount_amount = ?, s_h_amount = ?, adjustment = ?
				 WHERE quoteid = ?',
				array($lineSub, $lineSub, $srcTotal, $discount, $shipping, $adjustment, $newId)
			);
			return;
		}

		$newTotal = $lineSub - $discount + $shipping + $adjustment;
		if ($subtotal > 0 && $total > 0 && abs($subtotal - $lineSub) < 1 && abs($total - $newTotal) < 1) {
			return;
		}

		$db->pquery(
			'UPDATE vtiger_quotes SET subtotal = ?, pre_tax_total = ?, total = ?,
				discount_amount = ?, s_h_amount = ?, adjustment = ?
			 WHERE quoteid = ?',
			array($lineSub, $lineSub, $newTotal, $discount, $shipping, $adjustment, $newId)
		);
	}

	/**
	 * Assign next quote_no sequence (BGxx) — never "{source} (copy)".
	 */
	protected function ensureQuoteDocNumber($newId) {
		$newId = (int) $newId;
		if ($newId <= 0) {
			return;
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT quote_no FROM vtiger_quotes WHERE quoteid = ? LIMIT 1',
			array($newId)
		);
		$current = ($rs && $db->num_rows($rs) > 0)
			? trim((string) $db->query_result($rs, 0, 'quote_no'))
			: '';
		if ($current !== '' && !preg_match('/\s*\(copy(?:\s+\d+)?\)\s*$/i', $current)) {
			$db->pquery('UPDATE vtiger_crmentity SET label = ? WHERE crmid = ?', array($current, $newId));
			return;
		}

		$focus = CRMEntity::getInstance('Quotes');
		if ($focus && method_exists($focus, 'setModuleSeqNumber')) {
			require_once 'include/utils/MkEntityNumbering.php';
			MkEntityNumbering::ensureModuleSequence('Quotes');
			$seq = $focus->setModuleSeqNumber('increment', 'Quotes');
			if (is_string($seq) && $seq !== '') {
				$db->pquery('UPDATE vtiger_quotes SET quote_no = ? WHERE quoteid = ?', array($seq, $newId));
				$db->pquery('UPDATE vtiger_crmentity SET label = ? WHERE crmid = ?', array($seq, $newId));
			}
		}
	}

	/**
	 * Duplicate from SO is always a retail quote (not franchise / Tuibao).
	 */
	protected function clearFranchiseQuoteLink($newId) {
		$newId = (int) $newId;
		if ($newId <= 0) {
			return;
		}
		$db = PearDatabase::getInstance();
		$cols = $db->pquery('SHOW COLUMNS FROM vtiger_quotes LIKE ?', array('mk_servicecontract_id'));
		if ($cols && $db->num_rows($cols) > 0) {
			$db->pquery(
				'UPDATE vtiger_quotes SET mk_servicecontract_id = NULL WHERE quoteid = ?',
				array($newId)
			);
		}
	}

	/**
	 * Bump modifiedtime so new quote appears at top when sorted by quote_no / modifiedtime.
	 */
	protected function touchQuoteModifiedTime($newId) {
		$newId = (int) $newId;
		if ($newId <= 0) {
			return;
		}
		$db = PearDatabase::getInstance();
		$now = date('Y-m-d H:i:s');
		$db->pquery(
			'UPDATE vtiger_crmentity SET modifiedtime = ? WHERE crmid = ?',
			array($now, $newId)
		);
	}

	/**
	 * Re-assert subject + account/contact/potential after save (entity may wipe refs).
	 *
	 * @param int $newId
	 * @param Vtiger_Record_Model $source
	 * @param int $accountId
	 * @param int $contactId
	 * @param int $potentialId
	 */
	protected function persistQuoteIdentity($newId, $source, $accountId, $contactId, $potentialId) {
		$newId = (int) $newId;
		if ($newId <= 0) {
			return;
		}
		$db = PearDatabase::getInstance();
		$subject = trim((string) $source->get('subject'));
		if ($subject === '') {
			require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
			$customerLabel = Vtiger_MkSalesCustomerName_Helper::resolveListStyleName($source);
			$soNo = trim((string) $source->get('salesorder_no'));
			if ($customerLabel !== '' && $customerLabel !== '--' && $customerLabel !== '—') {
				$subject = $customerLabel;
			} else {
				$subject = $soNo !== '' ? ('Báo giá từ ' . $soNo) : ('Báo giá từ ĐH #' . (int) $source->getId());
			}
		} else {
			$subject = trim(preg_replace('/\s*\(copy(?:\s+\d+)?\)\s*$/i', '', $subject));
		}

		$cur = $db->pquery('SELECT subject, accountid, contactid, potentialid FROM vtiger_quotes WHERE quoteid = ?', array($newId));
		$curSubject = ($cur && $db->num_rows($cur) > 0) ? trim((string) $db->query_result($cur, 0, 'subject')) : '';
		$curAccount = ($cur && $db->num_rows($cur) > 0) ? (int) $db->query_result($cur, 0, 'accountid') : 0;
		$curContact = ($cur && $db->num_rows($cur) > 0) ? (int) $db->query_result($cur, 0, 'contactid') : 0;
		$curPotential = ($cur && $db->num_rows($cur) > 0) ? (int) $db->query_result($cur, 0, 'potentialid') : 0;

		if ($curSubject === '' && $subject !== '') {
			$db->pquery('UPDATE vtiger_quotes SET subject = ? WHERE quoteid = ?', array($subject, $newId));
		}
		if ($curAccount <= 0 && $accountId > 0) {
			$db->pquery('UPDATE vtiger_quotes SET accountid = ? WHERE quoteid = ?', array($accountId, $newId));
		}
		if ($curContact <= 0 && $contactId > 0) {
			$db->pquery('UPDATE vtiger_quotes SET contactid = ? WHERE quoteid = ?', array($contactId, $newId));
		}
		if ($curPotential <= 0 && $potentialId > 0) {
			$db->pquery('UPDATE vtiger_quotes SET potentialid = ? WHERE quoteid = ?', array($potentialId, $newId));
		}

		// Optional BA customer fields if present on Quotes.
		foreach (array(
			'mk_customer_phone' => array('mk_customer_phone', 'phone'),
			'mk_customer_email' => array('mk_customer_email', 'email'),
			'mk_client_company' => array('mk_client_company'),
		) as $quoteCol => $sourceKeys) {
			$cols = $db->pquery('SHOW COLUMNS FROM vtiger_quotes LIKE ?', array($quoteCol));
			if (!$cols || $db->num_rows($cols) <= 0) {
				continue;
			}
			$val = '';
			foreach ($sourceKeys as $sk) {
				$raw = trim((string) $source->get($sk));
				if ($raw !== '' && $raw !== '--' && $raw !== '—') {
					$val = $raw;
					break;
				}
			}
			if ($val === '') {
				continue;
			}
			$db->pquery(
				'UPDATE vtiger_quotes SET `' . $quoteCol . '` = IF(`' . $quoteCol . '` IS NULL OR `' . $quoteCol . '` = \'\', ?, `' . $quoteCol . '`) WHERE quoteid = ?',
				array($val, $newId)
			);
		}
	}

	/**
	 * Fail loudly if line copy left an empty quote (avoids blank Detail / missing list row money).
	 *
	 * @param int $newId
	 * @param int $sourceId
	 */
	protected function assertQuoteHasLines($newId, $sourceId) {
		$newId = (int) $newId;
		$sourceId = (int) $sourceId;
		if ($newId <= 0) {
			return;
		}
		$db = PearDatabase::getInstance();
		$src = $db->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_inventoryproductrel WHERE id = ?',
			array($sourceId)
		);
		$srcCount = ($src && $db->num_rows($src) > 0) ? (int) $db->query_result($src, 0, 'c') : 0;
		if ($srcCount <= 0) {
			return;
		}
		$dst = $db->pquery(
			'SELECT COUNT(*) AS c FROM vtiger_inventoryproductrel WHERE id = ?',
			array($newId)
		);
		$dstCount = ($dst && $db->num_rows($dst) > 0) ? (int) $db->query_result($dst, 0, 'c') : 0;
		if ($dstCount <= 0) {
			// Retry copy once before failing.
			$this->copyInventoryLines($sourceId, $newId);
			$this->syncLinePricesFromSource($sourceId, $newId);
			$dst = $db->pquery(
				'SELECT COUNT(*) AS c FROM vtiger_inventoryproductrel WHERE id = ?',
				array($newId)
			);
			$dstCount = ($dst && $db->num_rows($dst) > 0) ? (int) $db->query_result($dst, 0, 'c') : 0;
		}
		if ($dstCount <= 0) {
			throw new Exception('Báo giá #' . $newId . ' không có dòng hàng sau khi nhân bản từ ĐH #' . $sourceId);
		}
	}
}
