<?php
/*+***********************************************************************************
 * Confirm quote → create SalesOrder automatically, then trash the quote.
 *************************************************************************************/

class Quotes_ConfirmSalesOrder_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}
		if (!Users_Privileges_Model::isPermitted('Quotes', 'DetailView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		if (!Users_Privileges_Model::isPermitted('SalesOrder', 'CreateView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$quoteId = (int) $request->get('record');
		$response = new Vtiger_Response();

		try {
			if (getSalesEntityType($quoteId) !== 'Quotes') {
				throw new Exception('Bản ghi không phải báo giá.');
			}

			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			$quoteModel = Vtiger_Record_Model::getInstanceById($quoteId, 'Quotes');
			if (!$quoteModel || $quoteModel->getModuleName() !== 'Quotes') {
				throw new Exception('Không tìm thấy báo giá.');
			}

			$quoteStage = (string) $quoteModel->get('quotestage');
			if (!Quotes_QuoteBaService_Helper::isConfirmedQuoteStage($quoteStage)) {
				throw new Exception('Chỉ báo giá ở trạng thái Báo giá mới được xác nhận đơn hàng.');
			}

			if (method_exists($quoteModel, 'hasLinkedSalesOrder') && $quoteModel->hasLinkedSalesOrder()) {
				$existingId = (int) $quoteModel->getLinkedSalesOrderId();
				// Previous failed confirm may have left an SO header without line items — repair it.
				if ($existingId > 0) {
					if ($this->countInventoryLines($existingId) <= 0) {
						$this->copyInventoryLines($quoteId, $existingId);
					}
					$this->copyCurrencyAndTotals($quoteId, $existingId);
				}
				$this->trashQuote($quoteId, $quoteModel);
				$soModel = Vtiger_Record_Model::getInstanceById($existingId, 'SalesOrder');
				$response->setResult(array(
					'success' => true,
					'salesorderid' => $existingId,
					'salesorder_no' => $soModel ? $soModel->get('salesorder_no') : '',
					'detail_url' => method_exists($quoteModel, 'getLinkedSalesOrderDetailViewUrl')
						? $quoteModel->getLinkedSalesOrderDetailViewUrl()
						: ('index.php?module=SalesOrder&view=Detail&record=' . $existingId . '&app=SALES'),
					'list_url' => 'index.php?module=SalesOrder&view=List&app=SALES',
					'already_exists' => true,
					'quote_trashed' => true,
					'message' => 'Đơn hàng đã tồn tại từ báo giá này.',
				));
				$response->emit();
				return;
			}

			$salesOrderId = $this->createSalesOrderFromQuote($quoteModel);
			if ($salesOrderId <= 0) {
				throw new Exception('Không tạo được đơn hàng từ báo giá.');
			}

			$this->trashQuote($quoteId, $quoteModel);

			$soModel = Vtiger_Record_Model::getInstanceById($salesOrderId, 'SalesOrder');
			$detailUrl = $soModel
				? ($soModel->getDetailViewUrl() . '&app=SALES')
				: ('index.php?module=SalesOrder&view=Detail&record=' . $salesOrderId . '&app=SALES');

			$response->setResult(array(
				'success' => true,
				'salesorderid' => $salesOrderId,
				'salesorder_no' => $soModel ? $soModel->get('salesorder_no') : '',
				'detail_url' => $detailUrl,
				'list_url' => 'index.php?module=SalesOrder&view=List&app=SALES',
				'already_exists' => false,
				'quote_trashed' => true,
				'message' => 'Đã tạo đơn hàng (Phiếu tạm) từ báo giá.',
			));
		} catch (Exception $e) {
			$response->setResult(array(
				'success' => false,
				'message' => $e->getMessage() ? $e->getMessage() : 'Không tạo được đơn hàng từ báo giá.',
			));
		}

		$response->emit();
	}

	/**
	 * Soft-delete quote so it disappears from the Quotes list.
	 * Always force vtiger_crmentity.deleted = 1 (model delete can no-op on permission/hooks).
	 *
	 * @param int $quoteId
	 * @param Vtiger_Record_Model|null $quoteModel
	 */
	protected function trashQuote($quoteId, $quoteModel = null) {
		$quoteId = (int) $quoteId;
		if ($quoteId <= 0) {
			return;
		}
		try {
			if ($quoteModel && Users_Privileges_Model::isPermitted('Quotes', 'Delete', $quoteId)) {
				$quoteModel->delete();
			}
		} catch (Exception $ignore) {
			// fall through to forced soft-delete
		}
		$db = PearDatabase::getInstance();
		$db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ? AND setype = ?', array($quoteId, 'Quotes'));
	}

	/**
	 * @param Vtiger_Record_Model $quoteModel
	 * @return int
	 */
	protected function createSalesOrderFromQuote(Vtiger_Record_Model $quoteModel) {
		$quoteId = (int) $quoteModel->getId();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$salesOrderId = 0;

		try {
			$soModel = Vtiger_Record_Model::getCleanInstance('SalesOrder');
			if (method_exists($soModel, 'setRecordFieldValues')) {
				$soModel->setRecordFieldValues($quoteModel);
			}

			$soModel->set('mode', '');
			$soModel->set('id', '');
			$soModel->set('record', '');
			$soModel->set('quote_id', $quoteId);

			$subject = trim((string) $quoteModel->get('subject'));
			if ($subject === '') {
				$subject = 'Đơn từ báo giá ' . ($quoteModel->get('quote_no') ?: $quoteId);
			}
			$soModel->set('subject', $subject);

			$copyFields = array(
				'account_id', 'contact_id', 'potential_id', 'currency_id', 'conversion_rate',
				'description', 'terms_conditions',
				'bill_street', 'bill_city', 'bill_state', 'bill_code', 'bill_country', 'bill_pobox',
				'ship_street', 'ship_city', 'ship_state', 'ship_code', 'ship_country', 'ship_pobox',
				'assigned_user_id',
			);
			foreach ($copyFields as $fieldName) {
				$value = $quoteModel->get($fieldName);
				if ($value !== null && $value !== '') {
					$soModel->set($fieldName, $value);
				}
			}

			// Force account/contact from quote DB — field visibility can skip them in setRecordFieldValues.
			$db = PearDatabase::getInstance();
			$refRes = $db->pquery(
				'SELECT accountid, contactid, potentialid FROM vtiger_quotes WHERE quoteid = ?',
				array($quoteId)
			);
			if ($refRes && $db->num_rows($refRes) > 0) {
				$accountId = (int) $db->query_result($refRes, 0, 'accountid');
				$contactId = (int) $db->query_result($refRes, 0, 'contactid');
				$potentialId = (int) $db->query_result($refRes, 0, 'potentialid');
				if ($accountId > 0) {
					$soModel->set('account_id', $accountId);
				}
				if ($contactId > 0) {
					$soModel->set('contact_id', $contactId);
				}
				if ($potentialId > 0) {
					$soModel->set('potential_id', $potentialId);
				}
				if ((int) $soModel->get('contact_id') <= 0 && $potentialId > 0) {
					require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
					$potContactId = Vtiger_MkSalesCustomerName_Helper::resolveContactIdFromPotentialId($potentialId);
					if ($potContactId > 0) {
						$soModel->set('contact_id', $potContactId);
					}
				}
			}

			// From quote → always Phiếu tạm; warehouse outbound happens on SO confirm.
			$soModel->set('sostatus', 'Created');
			if ($soModel->get('assigned_user_id') === '' || $soModel->get('assigned_user_id') === null) {
				$soModel->set('assigned_user_id', $currentUser->getId());
			}
			if ($soModel->get('currency_id') === '' || $soModel->get('currency_id') === null) {
				$soModel->set('currency_id', 1);
			}
			if ($soModel->get('conversion_rate') === '' || $soModel->get('conversion_rate') === null) {
				$soModel->set('conversion_rate', 1);
			}
			if ($soModel->get('enable_recurring') === null || $soModel->get('enable_recurring') === '') {
				$soModel->set('enable_recurring', 0);
			}

			// Skip inventory save_module path; we copy line items directly after header save.
			$entity = method_exists($soModel, 'getEntity') ? $soModel->getEntity() : null;
			if ($entity) {
				$entity->isLineItemUpdate = false;
			}
			$_REQUEST['totalProductCount'] = 0;
			$_REQUEST['action'] = 'Save';
			$_REQUEST['module'] = 'SalesOrder';
			$_REQUEST['ajxaction'] = 'DETAILVIEW';

			$soModel->save();
			$salesOrderId = (int) $soModel->getId();
			if ($salesOrderId <= 0) {
				throw new Exception('Lưu đơn hàng thất bại.');
			}

			$this->copyInventoryLines($quoteId, $salesOrderId);
			$this->copyCurrencyAndTotals($quoteId, $salesOrderId);

			if ($this->countInventoryLines($salesOrderId) <= 0) {
				throw new Exception('Sao chép dòng hàng sang đơn hàng thất bại.');
			}

			return $salesOrderId;
		} catch (Exception $e) {
			if ($salesOrderId > 0) {
				$this->trashOrphanSalesOrder($salesOrderId);
			}
			throw $e;
		}
	}

	protected function countInventoryLines($recordId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT COUNT(*) AS cnt FROM vtiger_inventoryproductrel WHERE id = ? AND productid > 0',
			array((int) $recordId)
		);
		return (int) $db->query_result($rs, 0, 'cnt');
	}

	protected function trashOrphanSalesOrder($salesOrderId) {
		$salesOrderId = (int) $salesOrderId;
		if ($salesOrderId <= 0) {
			return;
		}
		try {
			$db = PearDatabase::getInstance();
			$db->pquery('DELETE FROM vtiger_inventoryproductrel WHERE id = ?', array($salesOrderId));
			$db->pquery('DELETE FROM vtiger_inventorysubproductrel WHERE id = ?', array($salesOrderId));
			$db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', array($salesOrderId));
		} catch (Exception $ignore) {
			// best-effort cleanup
		}
	}

	/**
	 * Resolve SHOW COLUMNS field name across ADOdb/mysqli key casings.
	 */
	protected function resolveColumnFieldName($col) {
		if (!is_array($col)) {
			return '';
		}
		foreach (array('Field', 'field', 'FIELD') as $key) {
			if (isset($col[$key]) && $col[$key] !== '') {
				return (string) $col[$key];
			}
		}
		if (isset($col[0]) && $col[0] !== '') {
			return (string) $col[0];
		}
		return '';
	}

	protected function copyInventoryLines($quoteId, $salesOrderId) {
		$db = PearDatabase::getInstance();
		$quoteId = (int) $quoteId;
		$salesOrderId = (int) $salesOrderId;

		$srcRes = $db->pquery(
			'SELECT * FROM vtiger_inventoryproductrel WHERE id = ? ORDER BY sequence_no ASC',
			array($quoteId)
		);
		$lineCount = $db->num_rows($srcRes);
		if ($lineCount <= 0) {
			throw new Exception('Báo giá chưa có hàng hóa để xác nhận đơn.');
		}

		$db->pquery('DELETE FROM vtiger_inventoryproductrel WHERE id = ?', array($salesOrderId));
		$db->pquery('DELETE FROM vtiger_inventorysubproductrel WHERE id = ?', array($salesOrderId));

		$colsRes = $db->pquery('SHOW COLUMNS FROM vtiger_inventoryproductrel', array());
		$columns = array();
		while ($col = $db->fetch_array($colsRes)) {
			$name = $this->resolveColumnFieldName($col);
			if ($name === '' || strcasecmp($name, 'lineitem_id') === 0) {
				continue;
			}
			$columns[] = $name;
		}
		if (empty($columns) || !in_array('id', $columns, true) || !in_array('productid', $columns, true)) {
			throw new Exception('Không đọc được cấu trúc dòng hàng.');
		}

		$copied = 0;
		for ($i = 0; $i < $lineCount; $i++) {
			$row = $db->fetchByAssoc($srcRes, $i);
			if (!is_array($row)) {
				continue;
			}
			// ADOdb may lowercase keys — normalize lookup.
			$normalized = array();
			foreach ($row as $k => $v) {
				$normalized[strtolower((string) $k)] = $v;
			}

			$productId = isset($normalized['productid']) ? (int) $normalized['productid'] : 0;
			if ($productId <= 0) {
				continue;
			}

			$insertCols = array();
			$placeholders = array();
			$params = array();
			foreach ($columns as $colName) {
				$insertCols[] = '`' . $colName . '`';
				$placeholders[] = '?';
				if (strcasecmp($colName, 'id') === 0) {
					$params[] = $salesOrderId;
				} else {
					$key = strtolower($colName);
					$params[] = array_key_exists($key, $normalized) ? $normalized[$key] : null;
				}
			}

			$db->pquery(
				'INSERT INTO vtiger_inventoryproductrel (' . implode(',', $insertCols) . ') VALUES (' . implode(',', $placeholders) . ')',
				$params
			);
			$copied++;
		}

		if ($copied <= 0) {
			throw new Exception('Không sao chép được dòng hàng từ báo giá.');
		}

		$subColsRes = $db->pquery('SHOW COLUMNS FROM vtiger_inventorysubproductrel', array());
		$subColumns = array();
		while ($col = $db->fetch_array($subColsRes)) {
			$name = $this->resolveColumnFieldName($col);
			if ($name !== '') {
				$subColumns[] = $name;
			}
		}
		if (!empty($subColumns) && in_array('id', $subColumns, true)) {
			$subSrc = $db->pquery('SELECT * FROM vtiger_inventorysubproductrel WHERE id = ?', array($quoteId));
			$subCount = $db->num_rows($subSrc);
			for ($i = 0; $i < $subCount; $i++) {
				$row = $db->fetchByAssoc($subSrc, $i);
				if (!is_array($row)) {
					continue;
				}
				$normalized = array();
				foreach ($row as $k => $v) {
					$normalized[strtolower((string) $k)] = $v;
				}
				$insertCols = array();
				$placeholders = array();
				$params = array();
				foreach ($subColumns as $colName) {
					$insertCols[] = '`' . $colName . '`';
					$placeholders[] = '?';
					if (strcasecmp($colName, 'id') === 0) {
						$params[] = $salesOrderId;
					} else {
						$key = strtolower($colName);
						$params[] = array_key_exists($key, $normalized) ? $normalized[$key] : null;
					}
				}
				$db->pquery(
					'INSERT INTO vtiger_inventorysubproductrel (' . implode(',', $insertCols) . ') VALUES (' . implode(',', $placeholders) . ')',
					$params
				);
			}
		}
	}

	/**
	 * Copy totals from quote DB columns (not hdn* form aliases) to avoid scale/format bugs.
	 */
	protected function copyCurrencyAndTotals($quoteId, $salesOrderId) {
		$db = PearDatabase::getInstance();
		$quoteId = (int) $quoteId;
		$salesOrderId = (int) $salesOrderId;

		$rs = $db->pquery(
			'SELECT accountid, contactid, potentialid, currency_id, conversion_rate, subtotal, total, taxtype,
			        discount_percent, discount_amount, s_h_amount, adjustment, pre_tax_total
			 FROM vtiger_quotes WHERE quoteid = ?',
			array($quoteId)
		);
		if (!$rs || $db->num_rows($rs) <= 0) {
			throw new Exception('Không đọc được tổng tiền báo giá.');
		}

		$accountId = (int) $db->query_result($rs, 0, 'accountid');
		$contactId = (int) $db->query_result($rs, 0, 'contactid');
		$potentialId = (int) $db->query_result($rs, 0, 'potentialid');
		if ($contactId <= 0 && $potentialId > 0) {
			require_once 'modules/Vtiger/helpers/MkSalesCustomerName.php';
			$contactId = Vtiger_MkSalesCustomerName_Helper::resolveContactIdFromPotentialId($potentialId);
		}
		$currencyId = $db->query_result($rs, 0, 'currency_id');
		$conversionRate = $db->query_result($rs, 0, 'conversion_rate');
		$subtotal = $db->query_result($rs, 0, 'subtotal');
		$total = $db->query_result($rs, 0, 'total');
		$taxtype = $db->query_result($rs, 0, 'taxtype');
		$discountPercent = $db->query_result($rs, 0, 'discount_percent');
		$discountAmount = $db->query_result($rs, 0, 'discount_amount');
		$shAmount = $db->query_result($rs, 0, 's_h_amount');
		$adjustment = $db->query_result($rs, 0, 'adjustment');
		$preTaxTotal = $db->query_result($rs, 0, 'pre_tax_total');

		$db->pquery(
			'UPDATE vtiger_salesorder SET
				quoteid = ?,
				accountid = IF(? > 0, ?, accountid),
				contactid = IF(? > 0, ?, contactid),
				potentialid = IF(? > 0, ?, potentialid),
				currency_id = ?,
				conversion_rate = ?,
				subtotal = ?,
				total = ?,
				taxtype = ?,
				discount_percent = ?,
				discount_amount = ?,
				s_h_amount = ?,
				adjustment = ?,
				pre_tax_total = ?
			 WHERE salesorderid = ?',
			array(
				$quoteId,
				$accountId, $accountId,
				$contactId, $contactId,
				$potentialId, $potentialId,
				$currencyId ?: 1,
				($conversionRate !== null && $conversionRate !== '') ? $conversionRate : 1,
				$subtotal,
				$total,
				$taxtype ?: 'group',
				$discountPercent,
				$discountAmount,
				$shAmount,
				$adjustment,
				$preTaxTotal,
				$salesOrderId,
			)
		);

		// Copy shipping/charges JSON if present.
		$chargeRes = $db->pquery('SELECT charges FROM vtiger_inventorychargesrel WHERE recordid = ?', array($quoteId));
		if ($chargeRes && $db->num_rows($chargeRes) > 0) {
			$charges = $db->query_result($chargeRes, 0, 'charges');
			$db->pquery('DELETE FROM vtiger_inventorychargesrel WHERE recordid = ?', array($salesOrderId));
			$db->pquery('INSERT INTO vtiger_inventorychargesrel (recordid, charges) VALUES (?, ?)', array($salesOrderId, $charges));
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
