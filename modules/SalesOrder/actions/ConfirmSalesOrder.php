<?php
/*+***********************************************************************************
 * Confirm Sales Order (Phiếu tạm → Đã xác nhận) and create warehouse outbound slip.
 *************************************************************************************/

class SalesOrder_ConfirmSalesOrder_Action extends Vtiger_Action_Controller {

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
		$warehouseId = trim((string) $request->get('warehouse_id'));
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
			$alreadyConfirmed = ($status === 'Approved' || $status === 'Đã xác nhận' || $status === 'Đã duyệt');

			require_once 'modules/GoodsIssue/helpers/CreateFromSalesOrder.php';
			$existingGi = GoodsIssue_CreateFromSalesOrder_Helper::findBySalesOrderId($recordId);

			if ($alreadyConfirmed && $existingGi > 0) {
				$this->trashLinkedQuote($soModel);
				$whUrl = $this->buildWarehouseOutboundUrlFromIssue($existingGi);
				$response->setResult(array(
					'success' => true,
					'salesorderid' => $recordId,
					'salesorder_no' => $soModel->get('salesorder_no'),
					'sostatus' => 'Approved',
					'goodsissueid' => $existingGi,
					'already_confirmed' => true,
					'message' => 'Đơn hàng đã được xác nhận và đã có phiếu xuất kho.',
					'list_url' => 'index.php?module=SalesOrder&view=List&app=SALES',
					'detail_url' => $soModel->getDetailViewUrl() . '&app=SALES',
					'warehouse_url' => $whUrl,
				));
				$response->emit();
				return;
			}

			if ($warehouseId === '') {
				throw new Exception('Vui lòng chọn kho xuất hàng.');
			}

			require_once 'modules/Warehouse/helpers/WarehouseRegistry.php';
			$warehouse = Warehouse_Registry::findById($warehouseId);
			if (!$warehouse) {
				throw new Exception('Không tìm thấy kho đã chọn.');
			}

			$lines = GoodsIssue_CreateFromSalesOrder_Helper::loadSalesOrderLines($recordId);
			if (empty($lines)) {
				throw new Exception('Đơn hàng chưa có hàng hóa để xuất kho.');
			}

			// Soft stock warning only — waiting_print does not deduct stock yet.
			$stockWarnings = GoodsIssue_CreateFromSalesOrder_Helper::validateWarehouseStock($lines, $warehouse['name']);

			if (!$alreadyConfirmed) {
				$db = PearDatabase::getInstance();
				$db->pquery(
					'UPDATE vtiger_salesorder SET sostatus = ? WHERE salesorderid = ?',
					array('Approved', $recordId)
				);
				$soModel->set('sostatus', 'Approved');
			}

			$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
			$goodsIssueId = $existingGi > 0
				? $existingGi
				: GoodsIssue_CreateFromSalesOrder_Helper::createFromSalesOrder(
					$recordId,
					$warehouse['id'],
					$warehouse['name'],
					$userId,
					false
				);

			if ($goodsIssueId <= 0) {
				throw new Exception('Không tạo được phiếu xuất kho.');
			}

			$message = 'Đã xác nhận đơn hàng và tạo phiếu xuất kho (Chờ in phiếu).';
			if (!empty($stockWarnings)) {
				$message .= ' Lưu ý tồn kho: ' . implode(' ', $stockWarnings);
			}

			// If SO came from a quote, soft-delete that quote so it leaves the Quotes list.
			$this->trashLinkedQuote($soModel);

			$response->setResult(array(
				'success' => true,
				'salesorderid' => $recordId,
				'salesorder_no' => $soModel->get('salesorder_no'),
				'sostatus' => 'Approved',
				'goodsissueid' => $goodsIssueId,
				'warehouse_id' => $warehouse['id'],
				'warehouse_name' => $warehouse['name'],
				'already_confirmed' => false,
				'stock_warnings' => $stockWarnings,
				'message' => $message,
				'list_url' => 'index.php?module=SalesOrder&view=List&app=SALES',
				'detail_url' => $soModel->getDetailViewUrl() . '&app=SALES',
				'warehouse_url' => $this->buildWarehouseOutboundUrl($warehouse['id']),
			));
		} catch (Exception $e) {
			$response->setResult(array(
				'success' => false,
				'message' => $e->getMessage() ? $e->getMessage() : 'Không xác nhận được đơn hàng.',
			));
		}

		$response->emit();
	}

	/**
	 * WhDetail outbound tab for the warehouse that received the slip.
	 *
	 * @param string $warehouseId
	 * @return string
	 */
	protected function buildWarehouseOutboundUrl($warehouseId) {
		$warehouseId = trim((string) $warehouseId);
		if ($warehouseId === '') {
			return 'index.php?module=Warehouse&view=List&app=INVENTORY';
		}
		return 'index.php?module=Warehouse&view=WhDetail&whId=' . urlencode($warehouseId)
			. '&tab=outbound&app=INVENTORY';
	}

	/**
	 * @param int $issueId
	 * @return string
	 */
	protected function buildWarehouseOutboundUrlFromIssue($issueId) {
		$issueId = (int) $issueId;
		if ($issueId <= 0) {
			return 'index.php?module=Warehouse&view=List&app=INVENTORY';
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery('SELECT warehouse_id FROM vtiger_goodsissue WHERE issueid = ? LIMIT 1', array($issueId));
		$whId = '';
		if ($rs && $db->num_rows($rs) > 0) {
			$whId = trim((string) $db->query_result($rs, 0, 'warehouse_id'));
		}
		return $this->buildWarehouseOutboundUrl($whId);
	}

	/**
	 * Soft-delete quote linked to this sales order (if any).
	 *
	 * @param Vtiger_Record_Model $soModel
	 */
	protected function trashLinkedQuote($soModel) {
		if (!$soModel) {
			return;
		}
		$quoteId = (int) $soModel->get('quote_id');
		if ($quoteId <= 0) {
			$db = PearDatabase::getInstance();
			$rs = $db->pquery('SELECT quoteid FROM vtiger_salesorder WHERE salesorderid = ?', array((int) $soModel->getId()));
			if ($rs && $db->num_rows($rs) > 0) {
				$quoteId = (int) $db->query_result($rs, 0, 'quoteid');
			}
		}
		if ($quoteId <= 0 || getSalesEntityType($quoteId) !== 'Quotes') {
			return;
		}
		try {
			if (Users_Privileges_Model::isPermitted('Quotes', 'Delete', $quoteId)) {
				$quoteModel = Vtiger_Record_Model::getInstanceById($quoteId, 'Quotes');
				if ($quoteModel) {
					$quoteModel->delete();
				}
			}
		} catch (Exception $ignore) {
			// fall through
		}
		$db = PearDatabase::getInstance();
		$db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ? AND setype = ?', array($quoteId, 'Quotes'));
	}
}
