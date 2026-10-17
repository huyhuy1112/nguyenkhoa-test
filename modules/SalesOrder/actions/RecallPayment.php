<?php
/*+***********************************************************************************
 * Thu hồi thanh toán: đơn Đã xác nhận biến mất, báo giá gốc quay lại Phiếu tạm.
 *************************************************************************************/

class SalesOrder_RecallPayment_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}
		if (!Users_Privileges_Model::isPermitted('SalesOrder', 'Delete', $recordId)) {
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
			$db = PearDatabase::getInstance();
			$rs = $db->pquery(
				'SELECT so.sostatus, so.quoteid FROM vtiger_salesorder so WHERE so.salesorderid = ?',
				array($recordId)
			);
			if (!$rs || $db->num_rows($rs) <= 0) {
				throw new Exception('Không tìm thấy đơn hàng.');
			}
			$status = trim((string) $db->query_result($rs, 0, 'sostatus'));
			$quoteId = (int) $db->query_result($rs, 0, 'quoteid');
			$finished = array(
				'Delivered', 'Hoàn thành', 'Đã hoàn thành', 'Đang sử dụng', 'shipped', 'Đã giao',
				'waiting_print', 'picking', 'packed', 'Chờ soạn', 'Đang soạn', 'Đã soạn',
			);
			if (in_array($status, $finished, true)) {
				throw new Exception('Đơn đã chuyển xuống xuất kho, không thu hồi thanh toán được.');
			}
			require_once 'modules/GoodsIssue/helpers/CreateFromSalesOrder.php';
			if (GoodsIssue_CreateFromSalesOrder_Helper::findBySalesOrderId($recordId) > 0) {
				throw new Exception('Đơn đã có phiếu xuất kho, không thu hồi thanh toán được.');
			}
			if ($quoteId <= 0) {
				throw new Exception('Đơn này không gắn báo giá để đưa về Phiếu tạm.');
			}
			$quoteRs = $db->pquery(
				'SELECT crmid FROM vtiger_crmentity WHERE crmid = ? AND setype = ?',
				array($quoteId, 'Quotes')
			);
			if (!$quoteRs || $db->num_rows($quoteRs) <= 0) {
				throw new Exception('Không tìm thấy báo giá gốc.');
			}
			require_once 'modules/Quotes/helpers/QuoteBaService.php';
			$stage = Quotes_QuoteBaService_Helper::resolveConfirmedQuoteStage();
			$db->pquery(
				'UPDATE vtiger_crmentity SET deleted = 0 WHERE crmid = ? AND setype = ?',
				array($quoteId, 'Quotes')
			);
			$db->pquery(
				'UPDATE vtiger_quotes SET quotestage = ? WHERE quoteid = ?',
				array($stage, $quoteId)
			);
			$db->pquery(
				'UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ? AND setype = ?',
				array($recordId, 'SalesOrder')
			);
			$response->setResult(array(
				'success' => true,
				'salesorderid' => $recordId,
				'quoteid' => $quoteId,
				'list_url' => 'index.php?module=Quotes&view=List&app=SALES',
				'message' => 'Đã thu hồi thanh toán. Báo giá quay lại trạng thái Phiếu tạm.',
			));
		} catch (Exception $e) {
			$response->setResult(array(
				'success' => false,
				'message' => $e->getMessage() ? $e->getMessage() : 'Không thu hồi được thanh toán.',
			));
		}
		$response->emit();
	}
}
