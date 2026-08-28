<?php
/*+***********************************************************************************
 * Transfer Sales Order to MISA accounting (UI hook + extension point).
 *************************************************************************************/

class SalesOrder_TransferToMisa_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}
		if (!Users_Privileges_Model::isPermitted('SalesOrder', 'DetailView', $recordId)) {
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

			$orderNo = trim((string) $soModel->get('salesorder_no'));
			if ($orderNo === '') {
				$orderNo = '#' . $recordId;
			}

			/**
			 * Hook for real MISA adapter (Settings → Tích hợp hệ thống).
			 * Senior implements NkApi_Misa_Adapter::transfer() — do not change this UI hook.
			 */
			require_once 'modules/Vtiger/helpers/NkApiConnection.php';
			$result = NkApiConnection::adapter('misa')->transfer($soModel);
			if (!is_array($result)) {
				$result = array();
			}
			if (!empty($result['error'])) {
				throw new Exception((string) $result['error']);
			}

			// Only mark synced after adapter succeeds.
			$moduleModel = $soModel->getModule();
			foreach (array('mk_misa_synced', 'misa_synced', 'cf_misa_status') as $candidate) {
				$fieldModel = Vtiger_Field_Model::getInstance($candidate, $moduleModel);
				if ($fieldModel && $fieldModel->isEditable()) {
					$soModel->set($candidate, '1');
					$soModel->set('mode', 'edit');
					$soModel->save();
					break;
				}
			}

			$message = !empty($result['message'])
				? (string) $result['message']
				: ('Đã chuyển đơn ' . $orderNo . ' đến kế toán MISA.');

			$response->setResult(array(
				'success' => true,
				'salesorderid' => $recordId,
				'salesorder_no' => $orderNo,
				'message' => $message,
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}

		$response->emit();
	}
}
