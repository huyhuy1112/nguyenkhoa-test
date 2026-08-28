<?php
/**
 * Mass-duplicate Quotes (header + line items), new records start as Nháp.
 */
class Quotes_MassDuplicate_Action extends Vtiger_Mass_Action {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'CreateView');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
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

		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		$draftStage = Quotes_QuoteBaService_Helper::resolveDraftQuoteStage();

		foreach ($recordIds as $recordId) {
			$recordId = (int) $recordId;
			if ($recordId <= 0) {
				continue;
			}
			if (!Users_Privileges_Model::isPermitted('Quotes', 'DetailView', $recordId)) {
				$errors[] = 'Không có quyền đọc báo giá #' . $recordId;
				continue;
			}
			try {
				$newId = $this->duplicateQuote($recordId, $draftStage);
				if ($newId > 0) {
					$created[] = $newId;
				} else {
					$errors[] = 'Không nhân bản được báo giá #' . $recordId;
				}
			} catch (Exception $e) {
				$errors[] = $e->getMessage() ? $e->getMessage() : ('Lỗi nhân bản #' . $recordId);
			}
		}

		$response = new Vtiger_Response();
		$response->setResult(array(
			'success' => count($created) > 0,
			'created' => $created,
			'created_count' => count($created),
			'errors' => $errors,
			'message' => count($created) > 0
				? ('Đã nhân bản ' . count($created) . ' báo giá.')
				: 'Không nhân bản được báo giá nào.',
		));
		$response->emit();
	}

	/**
	 * @param int $sourceId
	 * @param string $draftStage
	 * @return int
	 */
	protected function duplicateQuote($sourceId, $draftStage) {
		$sourceId = (int) $sourceId;
		$source = Inventory_Record_Model::getInstanceById($sourceId, 'Quotes');
		if (!$source) {
			throw new Exception('Không tìm thấy báo giá #' . $sourceId);
		}

		$sourceNo = trim((string) $source->get('quote_no'));
		$sourceSubject = trim((string) $source->get('subject'));

		$newModel = Vtiger_Record_Model::getCleanInstance('Quotes');

		$newModel->set('mode', '');
		$newModel->set('id', '');
		$newModel->set('record', '');
		$newModel->set('quote_no', '');
		$newModel->set('quotestage', $draftStage);

		$copyFields = array(
			'subject', 'account_id', 'contact_id', 'potential_id', 'currency_id',
			'description', 'terms_conditions', 'validtill',
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
			$newModel->set('subject', preg_replace('/\s*\(copy(?:\s+\d+)?\)\s*$/i', '', $sourceSubject) . ' (copy)');
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		if ($newModel->get('assigned_user_id') === '' || $newModel->get('assigned_user_id') === null) {
			$newModel->set('assigned_user_id', $currentUser->getId());
		}
		if ((int) $newModel->get('contact_id') <= 0 && (int) $newModel->get('potential_id') > 0) {
			require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
			$contactId = Vtiger_MkSalesCustomerName_Helper::resolveContactIdFromPotentialId((int) $newModel->get('potential_id'));
			if ($contactId > 0) {
				$newModel->set('contact_id', $contactId);
			}
		}

		$entity = method_exists($newModel, 'getEntity') ? $newModel->getEntity() : null;
		if ($entity) {
			$entity->isLineItemUpdate = false;
			if (isset($entity->column_fields) && is_array($entity->column_fields)) {
				$entity->column_fields['conversion_rate'] = $rate;
				foreach (array('total', 'subtotal', 'pre_tax_total', 'discount_amount', 's_h_amount', 'adjustment') as $moneyField) {
					$entity->column_fields[$moneyField] = 0;
				}
			}
		}
		$_REQUEST['totalProductCount'] = 0;
		$_REQUEST['action'] = 'SaveAjax';
		$_REQUEST['module'] = 'Quotes';
		foreach (array_keys($_REQUEST) as $reqKey) {
			if (preg_match('/^(listPrice|qty|hdnProductId|productName|comment|purchaseCost|margin|discount)\d+$/', $reqKey)) {
				unset($_REQUEST[$reqKey]);
			}
		}

		$newModel->save();
		$newId = (int) $newModel->getId();
		if ($newId <= 0) {
			throw new Exception('Không lưu được bản nhân bản.');
		}

		$this->copyInventoryLines($sourceId, $newId);
		$this->syncLinePricesFromSource($sourceId, $newId);
		$this->repairZeroLinePrices($newId, $sourceId);
		$this->copyQuoteTotals($sourceId, $newId);
		$this->ensureTotalsFromLines($newId, $sourceId);
		$this->fixInflatedMoney($sourceId, $newId);
		$this->applyCopiedDocNumber($newId, $sourceNo);

		return $newId;
	}

	protected function sanitizeConversionRate($raw) {
		if (is_string($raw)) {
			$raw = trim($raw);
		}
		if ($raw === null || $raw === '') {
			return 1.0;
		}
		if (is_numeric($raw) && (is_int($raw) || is_float($raw) || (is_string($raw) && substr_count($raw, '.') <= 1 && strpos($raw, ',') === false))) {
			$rate = (float) $raw;
		} else {
			$rate = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $raw));
		}
		if ($rate <= 0) {
			return 1.0;
		}
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
		if (substr_count($s, '.') >= 2) {
			$s = str_replace('.', '', $s);
			$s = str_replace(',', '.', $s);
			return (float) preg_replace('/[^\d.\-]/', '', $s);
		}
		if (strpos($s, ',') !== false) {
			$s = str_replace('.', '', $s);
			$s = str_replace(',', '.', $s);
			return (float) preg_replace('/[^\d.\-]/', '', $s);
		}
		if (is_numeric($s)) {
			return (float) $s;
		}
		return (float) preg_replace('/[^\d.\-]/', '', $s);
	}

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

	protected function fixInflatedMoney($sourceId, $newId) {
		$db = PearDatabase::getInstance();
		$src = $db->pquery('SELECT total, conversion_rate FROM vtiger_quotes WHERE quoteid = ?', array($sourceId));
		$dst = $db->pquery('SELECT total, conversion_rate FROM vtiger_quotes WHERE quoteid = ?', array($newId));
		if (!$src || !$dst || $db->num_rows($src) <= 0 || $db->num_rows($dst) <= 0) {
			return;
		}
		$srcTotal = $this->toPlainMoney($db->query_result($src, 0, 'total'));
		$dstTotal = $this->toPlainMoney($db->query_result($dst, 0, 'total'));
		$rate = $this->sanitizeConversionRate($db->query_result($dst, 0, 'conversion_rate'));
		if ($srcTotal > 0 && $dstTotal > ($srcTotal * 50)) {
			$this->syncLinePricesFromSource($sourceId, $newId);
			$this->copyQuoteTotals($sourceId, $newId);
		} else {
			$db->pquery('UPDATE vtiger_quotes SET conversion_rate = ? WHERE quoteid = ?', array($rate, $newId));
		}
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
			$selectParts[] = ($col === 'id') ? '?' : ('`' . $col . '`');
		}
		$sql = 'INSERT INTO vtiger_inventoryproductrel (`' . implode('`,`', $columns) . '`) '
			. 'SELECT ' . implode(',', $selectParts) . ' FROM vtiger_inventoryproductrel WHERE id = ? ORDER BY sequence_no ASC';
		$db->pquery($sql, array($newId, $sourceId));
	}

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
		$hdr = $db->pquery('SELECT subtotal, pre_tax_total FROM vtiger_quotes WHERE quoteid = ?', array($sourceId));
		if ($hdr && $db->num_rows($hdr) > 0) {
			$headerSub = (float) $db->query_result($hdr, 0, 'subtotal');
			if ($headerSub <= 0) {
				$headerSub = (float) $db->query_result($hdr, 0, 'pre_tax_total');
			}
		}
		$rows = array();
		for ($i = 0; $i < $db->num_rows($rs); $i++) {
			$lineId = (int) $db->query_result($rs, $i, 'lineitem_id');
			$productId = (int) $db->query_result($rs, $i, 'productid');
			$qty = (float) $db->query_result($rs, $i, 'quantity');
			$price = (float) $db->query_result($rs, $i, 'listprice');
			if ($qty <= 0) {
				$qty = 1.0;
			}
			if ($price <= 0) {
				$prs = $db->pquery('SELECT unit_price FROM vtiger_products WHERE productid = ?', array($productId));
				if ($prs && $db->num_rows($prs) > 0) {
					$price = (float) $db->query_result($prs, 0, 'unit_price');
				}
				if ($price <= 0) {
					$srs = $db->pquery('SELECT unit_price FROM vtiger_service WHERE serviceid = ?', array($productId));
					if ($srs && $db->num_rows($srs) > 0) {
						$price = (float) $db->query_result($srs, 0, 'unit_price');
					}
				}
			}
			$rows[] = array('lineitem_id' => $lineId, 'qty' => $qty, 'price' => $price);
		}
		$stillZeroQty = 0.0;
		foreach ($rows as $row) {
			if ($row['price'] <= 0) {
				$stillZeroQty += $row['qty'];
			}
		}
		if ($stillZeroQty > 0 && $headerSub > 0) {
			$sharePrice = $headerSub / $stillZeroQty;
			foreach ($rows as &$row) {
				if ($row['price'] <= 0) {
					$row['price'] = $sharePrice;
				}
			}
			unset($row);
		}
		foreach ($rows as $row) {
			if ($row['price'] > 0) {
				$db->pquery(
					'UPDATE vtiger_inventoryproductrel SET listprice = ? WHERE lineitem_id = ?',
					array($row['price'], $row['lineitem_id'])
				);
			}
		}
	}

	protected function copyQuoteTotals($sourceId, $newId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT subtotal, total, taxtype, discount_percent, discount_amount, s_h_amount, adjustment, pre_tax_total,
			        currency_id, conversion_rate, accountid, contactid, potentialid
			 FROM vtiger_quotes WHERE quoteid = ?',
			array($sourceId)
		);
		if (!$rs || $db->num_rows($rs) <= 0) {
			return;
		}
		$subtotal = $this->toPlainMoney($db->query_result($rs, 0, 'subtotal'));
		$total = $this->toPlainMoney($db->query_result($rs, 0, 'total'));
		$preTax = $this->toPlainMoney($db->query_result($rs, 0, 'pre_tax_total'));
		$rate = $this->sanitizeConversionRate($db->query_result($rs, 0, 'conversion_rate'));
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
				(float) $db->query_result($rs, 0, 'discount_percent'),
				(float) $db->query_result($rs, 0, 'discount_amount'),
				(float) $db->query_result($rs, 0, 's_h_amount'),
				(float) $db->query_result($rs, 0, 'adjustment'),
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

	protected function ensureTotalsFromLines($newId, $sourceId) {
		$db = PearDatabase::getInstance();
		$cur = $db->pquery('SELECT subtotal, total FROM vtiger_quotes WHERE quoteid = ?', array($newId));
		$subtotal = $cur ? (float) $db->query_result($cur, 0, 'subtotal') : 0;
		$total = $cur ? (float) $db->query_result($cur, 0, 'total') : 0;
		$lineRs = $db->pquery(
			'SELECT COALESCE(SUM(quantity * listprice), 0) AS line_subtotal FROM vtiger_inventoryproductrel WHERE id = ?',
			array($newId)
		);
		$lineSub = $lineRs ? (float) $db->query_result($lineRs, 0, 'line_subtotal') : 0;
		if ($lineSub <= 0) {
			return;
		}
		if ($subtotal > 0 && $total > 0 && abs($subtotal - $lineSub) < 1) {
			return;
		}
		$src = $db->pquery(
			'SELECT subtotal, total, discount_amount, discount_percent, s_h_amount, adjustment FROM vtiger_quotes WHERE quoteid = ?',
			array($sourceId)
		);
		$srcSub = $src ? (float) $db->query_result($src, 0, 'subtotal') : 0;
		$srcTotal = $src ? (float) $db->query_result($src, 0, 'total') : 0;
		$discount = $src ? (float) $db->query_result($src, 0, 'discount_amount') : 0;
		$discountPct = $src ? (float) $db->query_result($src, 0, 'discount_percent') : 0;
		$shipping = $src ? (float) $db->query_result($src, 0, 's_h_amount') : 0;
		$adjustment = $src ? (float) $db->query_result($src, 0, 'adjustment') : 0;
		if ($discount <= 0 && $discountPct > 0) {
			$discount = $lineSub * $discountPct / 100;
		}
		$tax = 0.0;
		if ($srcSub > 0 && $srcTotal > ($srcSub - $discount)) {
			$tax = $srcTotal - ($srcSub - $discount) - $shipping - $adjustment;
		}
		if ($tax < 0) {
			$tax = 0;
		}
		if ($tax <= 0) {
			$tax = round(($lineSub - $discount) * 0.08);
		}
		$newTotal = $lineSub - $discount + $tax + $shipping + $adjustment;
		$db->pquery(
			'UPDATE vtiger_quotes SET subtotal = ?, pre_tax_total = ?, total = ?,
				discount_amount = ?, s_h_amount = ?, adjustment = ?
			 WHERE quoteid = ?',
			array($lineSub, $lineSub, $newTotal, $discount, $shipping, $adjustment, $newId)
		);
	}

	protected function applyCopiedDocNumber($newId, $sourceNo) {
		$sourceNo = trim((string) $sourceNo);
		if ($sourceNo === '') {
			return;
		}
		$base = preg_replace('/\s*\(copy(?:\s+\d+)?\)\s*$/i', '', $sourceNo);
		$candidate = $base . ' (copy)';
		$db = PearDatabase::getInstance();
		$n = 2;
		while ($this->docNumberExists($candidate, $newId)) {
			$candidate = $base . ' (copy ' . $n . ')';
			$n++;
			if ($n > 50) {
				$candidate = $base . ' (copy ' . $newId . ')';
				break;
			}
		}
		$db->pquery('UPDATE vtiger_quotes SET quote_no = ? WHERE quoteid = ?', array($candidate, $newId));
		$db->pquery('UPDATE vtiger_crmentity SET label = ? WHERE crmid = ?', array($candidate, $newId));
	}

	protected function docNumberExists($docNo, $excludeId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT quoteid FROM vtiger_quotes
			 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_quotes.quoteid
			 WHERE vtiger_crmentity.deleted = 0 AND quote_no = ? AND quoteid <> ? LIMIT 1',
			array($docNo, (int) $excludeId)
		);
		return $rs && $db->num_rows($rs) > 0;
	}
}
