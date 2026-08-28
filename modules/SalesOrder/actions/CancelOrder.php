<?php
/*+***********************************************************************************
 * Cancel Sales Order: cancel linked goods issues (restock if deducted) → SO Cancelled.
 *************************************************************************************/

class SalesOrder_CancelOrder_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}
		if (!Users_Privileges_Model::isPermitted('SalesOrder', 'DetailView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		if (!Users_Privileges_Model::isPermitted('SalesOrder', 'EditView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		$response = new Vtiger_Response();

		try {
			if (getSalesEntityType($recordId) !== 'SalesOrder') {
				throw new Exception('Bản ghi không phải đơn hàng.');
			}

			$soModel = Vtiger_Record_Model::getInstanceById($recordId, 'SalesOrder');
			if (!$soModel || $soModel->getModuleName() !== 'SalesOrder') {
				throw new Exception('Không tìm thấy đơn hàng.');
			}

			$status = trim((string) $soModel->get('sostatus'));
			$blocked = array(
				'shipped', 'Delivered', 'Đã giao', 'Hoàn thành',
				'Cancelled', 'Đã hủy', 'Đã huỷ',
			);
			if (in_array($status, $blocked, true)) {
				if (in_array($status, array('Cancelled', 'Đã hủy', 'Đã huỷ'), true)) {
					throw new Exception('Đơn hàng đã được huỷ trước đó.');
				}
				throw new Exception('Không huỷ được đơn đã giao hàng.');
			}

			$db = PearDatabase::getInstance();
			$rs = $db->pquery(
				'SELECT issueid, code, warehouse_id, status
				 FROM vtiger_goodsissue
				 WHERE salesorder_id = ? AND deleted = 0
				 ORDER BY issueid ASC',
				array($recordId)
			);

			$cancelledIssues = array();
			$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();

			if ($rs && $db->num_rows($rs) > 0) {
				require_once 'modules/Warehouse/models/WhMgmtService.php';
				$rows = array();
				$num = $db->num_rows($rs);
				for ($i = 0; $i < $num; $i++) {
					$rows[] = array(
						'issueid' => (int) $db->query_result($rs, $i, 'issueid'),
						'code' => trim((string) $db->query_result($rs, $i, 'code')),
						'warehouse_id' => trim((string) $db->query_result($rs, $i, 'warehouse_id')),
						'status' => strtolower(trim((string) $db->query_result($rs, $i, 'status'))),
					);
				}
				foreach ($rows as $row) {
					if ($row['status'] === 'shipped' || $row['status'] === 'completed') {
						throw new Exception('Không huỷ được: phiếu xuất ' . $row['code'] . ' đã giao hàng.');
					}
				}
				foreach ($rows as $row) {
					if ($row['status'] === 'cancelled' || $row['status'] === 'canceled') {
						$cancelledIssues[] = $row['code'];
						continue;
					}
					if ($row['code'] === '' || $row['warehouse_id'] === '') {
						throw new Exception('Phiếu xuất kho thiếu mã hoặc kho.');
					}
					Warehouse_WhMgmtService::cancelIssueByCode(
						$row['warehouse_id'],
						$row['code'],
						$userId,
						'Huỷ đơn từ Đơn hàng #' . $recordId
					);
					$cancelledIssues[] = $row['code'];
				}
			}

			// Ensure SO status is Cancelled (sync may already have set it).
			$this->setSalesOrderCancelled($db, $recordId, $soModel);

			$response->setResult(array(
				'success' => true,
				'salesorderid' => $recordId,
				'salesorder_no' => $soModel->get('salesorder_no'),
				'sostatus' => 'Cancelled',
				'cancelled_issues' => $cancelledIssues,
				'message' => empty($cancelledIssues)
					? 'Đã huỷ đơn hàng.'
					: 'Đã huỷ đơn hàng và hoàn kho (nếu đã trừ tồn). Phiếu: ' . implode(', ', $cancelledIssues),
			));
		} catch (Exception $e) {
			$response->setResult(array(
				'success' => false,
				'message' => $e->getMessage() ? $e->getMessage() : 'Không huỷ được đơn hàng.',
			));
		}

		$response->emit();
	}

	/**
	 * @param PearDatabase $db
	 * @param int $recordId
	 * @param Vtiger_Record_Model $soModel
	 */
	protected function setSalesOrderCancelled(PearDatabase $db, $recordId, $soModel) {
		$recordId = (int) $recordId;
		$soStatus = 'Cancelled';
		$current = '';
		$rs = $db->pquery(
			'SELECT sostatus FROM vtiger_salesorder WHERE salesorderid = ? LIMIT 1',
			array($recordId)
		);
		if ($rs && $db->num_rows($rs) > 0) {
			$current = trim((string) $db->query_result($rs, 0, 'sostatus'));
		}
		if ($current === $soStatus) {
			return;
		}
		$db->pquery(
			'UPDATE vtiger_salesorder SET sostatus = ? WHERE salesorderid = ?',
			array($soStatus, $recordId)
		);
		if ($soModel) {
			$soModel->set('sostatus', $soStatus);
		}
		try {
			$accountName = '';
			$total = 0;
			$infoRs = $db->pquery(
				'SELECT so.total, COALESCE(acc.accountname, \'\') AS accountname
				 FROM vtiger_salesorder so
				 LEFT JOIN vtiger_account acc ON acc.accountid = so.accountid
				 WHERE so.salesorderid = ? LIMIT 1',
				array($recordId)
			);
			if ($infoRs && $db->num_rows($infoRs) > 0) {
				$accountName = (string) $db->query_result($infoRs, 0, 'accountname');
				$total = (float) $db->query_result($infoRs, 0, 'total');
			}
			$historyId = (int) $db->getUniqueID('vtiger_sostatushistory');
			$db->pquery(
				'INSERT INTO vtiger_sostatushistory(historyid, salesorderid, accountname, total, sostatus, lastmodified)
				 VALUES(?,?,?,?,?,?)',
				array($historyId, $recordId, $accountName, $total, $soStatus, date('Y-m-d H:i:s'))
			);
		} catch (Exception $ignore) {
			// History optional.
		}
	}
}
