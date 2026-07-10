<?php
/**
 * Mass-duplicate Sales Orders (header + line items), new records start as Phiếu tạm (Created).
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

		$newModel = Vtiger_Record_Model::getCleanInstance('SalesOrder');
		if (method_exists($newModel, 'setRecordFieldValues')) {
			$newModel->setRecordFieldValues($source);
		}

		$newModel->set('mode', '');
		$newModel->set('id', '');
		$newModel->set('record', '');
		$newModel->set('salesorder_no', '');
		$newModel->set('sostatus', 'Created');
		$newModel->set('received', 0);
		$newModel->set('balance', '');

		$copyFields = array(
			'subject', 'account_id', 'contact_id', 'potential_id', 'quote_id', 'currency_id', 'conversion_rate',
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

		$subject = trim((string) $newModel->get('subject'));
		if ($subject !== '') {
			$newModel->set('subject', $subject . ' (copy)');
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
		}
		$_REQUEST['totalProductCount'] = 0;
		$_REQUEST['action'] = 'Save';
		$_REQUEST['module'] = 'SalesOrder';

		$newModel->save();
		$newId = (int) $newModel->getId();
		if ($newId <= 0) {
			throw new Exception('Không lưu được bản nhân bản.');
		}

		$this->copyInventoryLines($sourceId, $newId);
		$this->copySalesOrderTotals($sourceId, $newId);

		return $newId;
	}

	protected function copyInventoryLines($sourceId, $newId) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery('SELECT * FROM vtiger_inventoryproductrel WHERE id = ? ORDER BY sequence_no ASC', array($sourceId));
		if (!$rs || $db->num_rows($rs) <= 0) {
			return;
		}
		while ($row = $db->fetchByAssoc($rs)) {
			unset($row['lineitem_id']);
			$row['id'] = $newId;
			$columns = array_keys($row);
			$placeholders = array();
			$values = array();
			foreach ($columns as $col) {
				$placeholders[] = '?';
				$values[] = $row[$col];
			}
			$db->pquery(
				'INSERT INTO vtiger_inventoryproductrel (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')',
				$values
			);
		}
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
		$total = $db->query_result($rs, 0, 'total');
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
				$db->query_result($rs, 0, 'subtotal'),
				$total,
				$db->query_result($rs, 0, 'taxtype'),
				$db->query_result($rs, 0, 'discount_percent'),
				$db->query_result($rs, 0, 'discount_amount'),
				$db->query_result($rs, 0, 's_h_amount'),
				$db->query_result($rs, 0, 'adjustment'),
				$db->query_result($rs, 0, 'pre_tax_total'),
				$db->query_result($rs, 0, 'currency_id'),
				$db->query_result($rs, 0, 'conversion_rate'),
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
}
