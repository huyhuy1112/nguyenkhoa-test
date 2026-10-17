<?php
/*+***********************************************************************************
 * Transfer an invoice's linked sales order to MISA. Orders themselves cannot.
 *************************************************************************************/

class Invoice_TransferToMisa_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}
		if (!Users_Privileges_Model::isPermitted('Invoice', 'DetailView', $recordId)) {
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
			if (getSalesEntityType($recordId) !== 'Invoice') {
				throw new Exception('Bản ghi không phải hóa đơn.');
			}
			$invoice = Vtiger_Record_Model::getInstanceById($recordId, 'Invoice');
			if (!$invoice || $invoice->getModuleName() !== 'Invoice') {
				throw new Exception('Không tìm thấy hóa đơn.');
			}
			$soId = (int) $invoice->get('salesorder_id');
			if ($soId <= 0) {
				throw new Exception('Hóa đơn này không gắn đơn hàng để chuyển MISA.');
			}
			$soModel = Vtiger_Record_Model::getInstanceById($soId, 'SalesOrder');
			if (!$soModel || $soModel->getModuleName() !== 'SalesOrder') {
				throw new Exception('Không tìm thấy đơn hàng của hóa đơn.');
			}

			require_once 'modules/Vtiger/helpers/NkApiConnection.php';
			$result = NkApiConnection::adapter('misa')->transfer($soModel);
			if (!is_array($result)) {
				$result = array();
			}
			if (!empty($result['error'])) {
				throw new Exception((string) $result['error']);
			}

			$message = !empty($result['message'])
				? (string) $result['message']
				: 'Đã chuyển hóa đơn sang MISA. Số tiền đã trừ VAT nếu đơn đang tính VAT.';

			$response->setResult(array(
				'success' => true,
				'invoiceid' => $recordId,
				'salesorderid' => $soId,
				'message' => $message,
				'misa_status' => isset($result['misa_status']) ? (string) $result['misa_status'] : 'Chờ kế toán',
				'misa_refno' => isset($result['misa_refno']) ? (string) $result['misa_refno'] : '',
				'misa_state' => isset($result['misa_state']) ? (string) $result['misa_state'] : 'pending',
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}

		$response->emit();
	}
}
