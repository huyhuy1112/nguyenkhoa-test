<?php
/**
 * Mass-duplicate Sales Orders (header + line items), new records start as Phiếu tạm (Created).
 * Doc no uses the next module sequence (SO34, SO35, …) — never "{source} (copy)".
 */
class SalesOrder_MassDuplicate_Action extends Vtiger_Mass_Action {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'CreateView');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted('SalesOrder', 'CreateView')) {
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
			}
		}

		$response = new Vtiger_Response();
		$response->setResult(array(
			'success' => count($created) > 0,
			'created' => $created,
			'created_count' => count($created),
			'errors' => $errors,
			'message' => count($created) > 0
				? ('Đã nhân bản ' . count($created) . ' đơn hàng.')
				: 'Không nhân bản được đơn hàng nào.',
		));
		$response->emit();
	}

	/**
	 * @param int $sourceId
	 * @return int
	 */
	protected function duplicateSalesOrder($sourceId) {
		$sourceId = (int) $sourceId;
		$source = Inventory_Record_Model::getInstanceById($sourceId, 'SalesOrder');
		if (!$source) {
			throw new Exception('Không tìm thấy đơn hàng #' . $sourceId);
		}

		$sourceSubject = trim((string) $source->get('subject'));

		$newModel = Vtiger_Record_Model::getCleanInstance('SalesOrder');
		// Avoid setRecordFieldValues for money/rate — those go through currency parse on save.
		$copyFields = array(
			'subject', 'account_id', 'contact_id', 'potential_id', 'quote_id', 'currency_id',
			'description', 'terms_conditions', 'duedate', 'customerno', 'enable_recurring',
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

		$newModel->set('mode', '');
		$newModel->set('id', '');
		$newModel->set('record', '');
		$newModel->set('salesorder_no', '');
		$newModel->set('sostatus', 'Created');
		$newModel->set('received', 0);
		$newModel->set('balance', 0);

		$rate = $this->sanitizeConversionRate($source->get('conversion_rate'));
		$newModel->set('conversion_rate', $rate);
		// Do not write money via model save (uitype 72 + VN separators). SQL copy after save.
		foreach (array(
			'hdnGrandTotal', 'hdnSubTotal', 'total', 'subtotal', 'pre_tax_total',
			'discount_amount', 'discount_percent', 's_h_amount', 'adjustment',
			'received', 'balance', 'txtAdjustment', 'shipping_handling_charge',
		) as $moneyField) {
			$newModel->set($moneyField, 0);
		}

		// Keep a clean subject (strip any legacy "(copy)" from older duplicates).
		if ($sourceSubject !== '') {
			$cleanSubject = trim(preg_replace('/\s*\(copy(?:\s+\d+)?\)\s*$/i', '', $sourceSubject));
			if ($cleanSubject !== '') {
				$newModel->set('subject', $cleanSubject);
			}
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
				foreach (array('total', 'subtotal', 'pre_tax_total', 'discount_amount', 's_h_amount', 'adjustment', 'salesorder_no') as $fieldName) {
					if ($fieldName === 'salesorder_no') {
						$entity->column_fields[$fieldName] = '';
					} else {
						$entity->column_fields[$fieldName] = 0;
					}
				}
			}
		}
		// SaveAjax path skips inventory line rewrite; also clear any leftover line request keys.
		$_REQUEST['totalProductCount'] = 0;
		$_REQUEST['action'] = 'SaveAjax';
		$_REQUEST['module'] = 'SalesOrder';
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
		$this->copySalesOrderTotals($sourceId, $newId);
		$this->ensureTotalsFromLines($newId, $sourceId);
		$this->fixInflatedMoney($sourceId, $newId);
		$this->ensureNextDocNumber($newId);

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
	protected function fixInflatedMoney($sourceId, $newId) {
		$db = PearDatabase::getInstance();
		$src = $db->pquery('SELECT total, subtotal, conversion_rate FROM vtiger_salesorder WHERE salesorderid = ?', array($sourceId));
		$dst = $db->pquery('SELECT total, subtotal, conversion_rate FROM vtiger_salesorder WHERE salesorderid = ?', array($newId));
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
			$this->copySalesOrderTotals($sourceId, $newId);
		} else {
			$db->pquery(
				'UPDATE vtiger_salesorder SET conversion_rate = ? WHERE salesorderid = ?',
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

	protected function copySalesOrderTotals($sourceId, $newId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT subtotal, total, taxtype, discount_percent, discount_amount, s_h_amount, adjustment, pre_tax_total,
			        currency_id, conversion_rate, accountid, contactid, potentialid, quoteid
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

		// Source often stores total == subtotal; rebuild grand with VAT so copy list matches panel.
		if ($subtotal > 0 && $total <= ($subtotal + 1)) {
			$discount = $discountAmount;
			if ($discount <= 0 && $discountPercent > 0) {
				$discount = $subtotal * $discountPercent / 100;
			}
			$tax = round(($subtotal - $discount) * 0.08);
			$total = $subtotal - $discount + $tax + $shipping + $adjustment;
		}
		if ($preTax <= 0) {
			$preTax = $subtotal;
		}

		$db->pquery(
			'UPDATE vtiger_salesorder SET
				subtotal = ?, total = ?, taxtype = ?, discount_percent = ?, discount_amount = ?,
				s_h_amount = ?, adjustment = ?, pre_tax_total = ?,
				currency_id = ?, conversion_rate = ?,
				received = 0, balance = ?,
				sostatus = ?,
				accountid = IF(? > 0, ?, accountid),
				contactid = IF(? > 0, ?, contactid),
				potentialid = IF(? > 0, ?, potentialid),
				quoteid = IF(? > 0, ?, quoteid)
			 WHERE salesorderid = ?',
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
				$total,
				'Created',
				(int) $db->query_result($rs, 0, 'accountid'), (int) $db->query_result($rs, 0, 'accountid'),
				(int) $db->query_result($rs, 0, 'contactid'), (int) $db->query_result($rs, 0, 'contactid'),
				(int) $db->query_result($rs, 0, 'potentialid'), (int) $db->query_result($rs, 0, 'potentialid'),
				(int) $db->query_result($rs, 0, 'quoteid'), (int) $db->query_result($rs, 0, 'quoteid'),
				$newId,
			)
		);
	}

	/**
	 * If header totals are still 0 / missing tax after copy, rebuild from lines + source tax.
	 */
	protected function ensureTotalsFromLines($newId, $sourceId) {
		$db = PearDatabase::getInstance();
		$cur = $db->pquery('SELECT subtotal, total FROM vtiger_salesorder WHERE salesorderid = ?', array($newId));
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

		// Prefer exact source grand total when it already includes tax (total > subtotal).
		// If source total ≈ subtotal, fall through and apply VAT — otherwise copy keeps "tiền hàng" as tổng cộng.
		if ($srcTotal > 0 && $srcSub > 0 && abs($lineSub - $srcSub) < 1 && $srcTotal > ($srcSub + 1)) {
			$db->pquery(
				'UPDATE vtiger_salesorder SET subtotal = ?, pre_tax_total = ?, total = ?, balance = ?,
					discount_amount = ?, s_h_amount = ?, adjustment = ?
				 WHERE salesorderid = ?',
				array($lineSub, $lineSub, $srcTotal, $srcTotal, $discount, $shipping, $adjustment, $newId)
			);
			return;
		}

		$tax = 0.0;
		if ($srcSub > 0 && $srcTotal > ($srcSub - $discount)) {
			$tax = $srcTotal - ($srcSub - $discount) - $shipping - $adjustment;
		}
		if ($tax < 0) {
			$tax = 0;
		}
		if ($tax <= 0 && $srcSub > 0 && $srcTotal > 0) {
			$tax = max(0, $srcTotal - $srcSub + $discount - $shipping - $adjustment);
		}
		if ($tax <= 0) {
			$tax = round(($lineSub - $discount) * 0.08);
		}

		$newTotal = $lineSub - $discount + $tax + $shipping + $adjustment;
		// Keep source total when it already looks correct (has tax on top of lines).
		if ($srcTotal > $lineSub && $total >= $srcTotal && abs($subtotal - $lineSub) < 1) {
			return;
		}
		if ($subtotal > 0 && $total > $lineSub && abs($subtotal - $lineSub) < 1 && abs($total - $newTotal) < 1) {
			return;
		}

		$db->pquery(
			'UPDATE vtiger_salesorder SET subtotal = ?, pre_tax_total = ?, total = ?, balance = ?,
				discount_amount = ?, s_h_amount = ?, adjustment = ?
			 WHERE salesorderid = ?',
			array($lineSub, $lineSub, $newTotal, $newTotal, $discount, $shipping, $adjustment, $newId)
		);
	}

	/**
	 * Keep the auto-incremented salesorder_no from save (SO34, …).
	 * Only fill if save left it blank / still looks like a legacy "(copy)" label.
	 */
	protected function ensureNextDocNumber($newId) {
		$newId = (int) $newId;
		if ($newId <= 0) {
			return;
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT salesorder_no FROM vtiger_salesorder WHERE salesorderid = ? LIMIT 1',
			array($newId)
		);
		$current = ($rs && $db->num_rows($rs) > 0)
			? trim((string) $db->query_result($rs, 0, 'salesorder_no'))
			: '';
		if ($current !== '' && !preg_match('/\s*\(copy(?:\s+\d+)?\)\s*$/i', $current)) {
			$db->pquery('UPDATE vtiger_crmentity SET label = ? WHERE crmid = ?', array($current, $newId));
			return;
		}

		$focus = CRMEntity::getInstance('SalesOrder');
		if ($focus && method_exists($focus, 'setModuleSeqNumber')) {
			require_once 'include/utils/MkEntityNumbering.php';
			MkEntityNumbering::ensureModuleSequence('SalesOrder');
			$seq = $focus->setModuleSeqNumber('increment', 'SalesOrder');
			if (is_string($seq) && $seq !== '') {
				$db->pquery('UPDATE vtiger_salesorder SET salesorder_no = ? WHERE salesorderid = ?', array($seq, $newId));
				$db->pquery('UPDATE vtiger_crmentity SET label = ? WHERE crmid = ?', array($seq, $newId));
			}
		}
	}

	protected function docNumberExists($docNo, $excludeId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT salesorderid FROM vtiger_salesorder
			 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_salesorder.salesorderid
			 WHERE vtiger_crmentity.deleted = 0 AND salesorder_no = ? AND salesorderid <> ? LIMIT 1',
			array($docNo, (int) $excludeId)
		);
		return $rs && $db->num_rows($rs) > 0;
	}
}
