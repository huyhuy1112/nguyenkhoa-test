<?php
/*+***********************************************************************************
 * Lịch sử từng lần khách trả trên đúng đơn đang mở.
 *************************************************************************************/

class SalesOrder_PaymentHistory_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}
		if (!Users_Privileges_Model::isPermitted('SalesOrder', 'DetailView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		$response = new Vtiger_Response();
		try {
			if (getSalesEntityType($recordId) !== 'SalesOrder') {
				throw new Exception('Bản ghi không phải đơn hàng.');
			}
			require_once 'modules/SalesOrder/helpers/PaymentLedger.php';
			$history = SalesOrder_PaymentLedger_Helper::listForOrder($recordId);
			$response->setResult(array(
				'success' => true,
				'order_no' => $history['order_no'],
				'total' => isset($history['total_display']) ? $history['total_display'] : '',
				'paid' => isset($history['paid_display']) ? $history['paid_display'] : '',
				'rows' => $history['rows'],
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}
}
