<?php
/*+***********************************************************************************
 * Cancel a quote (Phiếu tạm → Huỷ). The quote stays on the list.
 *************************************************************************************/

class Quotes_CancelQuote_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}
		if (!Users_Privileges_Model::isPermitted('Quotes', 'EditView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
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
			if (!$quoteModel) {
				throw new Exception('Không tìm thấy báo giá.');
			}
			$stage = (string) $quoteModel->get('quotestage');
			if (Quotes_QuoteBaService_Helper::isCancelledQuoteStage($stage)) {
				throw new Exception('Báo giá này đã huỷ.');
			}
			if (Quotes_QuoteBaService_Helper::hasActiveSalesOrderForQuote($quoteId)) {
				throw new Exception('Báo giá đã chuyển thành đơn hàng. Dùng Thu hồi thanh toán trên đơn hàng.');
			}
			$db = PearDatabase::getInstance();
			$db->pquery(
				'UPDATE vtiger_quotes SET quotestage = ? WHERE quoteid = ?',
				array('Rejected', $quoteId)
			);
			$response->setResult(array(
				'success' => true,
				'quoteid' => $quoteId,
				'quotestage' => 'Huỷ',
				'message' => 'Đã huỷ báo giá.',
			));
		} catch (Exception $e) {
			$response->setResult(array(
				'success' => false,
				'message' => $e->getMessage() ? $e->getMessage() : 'Không huỷ được báo giá.',
			));
		}
		$response->emit();
	}
}
