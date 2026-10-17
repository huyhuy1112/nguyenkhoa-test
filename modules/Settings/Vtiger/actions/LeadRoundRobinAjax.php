<?php
/*+***********************************************************************************
 * Settings → Round Robin Lead (AJAX).
 *************************************************************************************/

require_once 'modules/Leads/models/RoundRobinService.php';

class Settings_Vtiger_LeadRoundRobinAjax_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if (!$currentUserModel || !$currentUserModel->isAdminUser()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->get('mode');
		$response = new Vtiger_Response();
		try {
			if ($mode === 'resetCursor') {
				Leads_RoundRobinService::resetCursor();
				$response->setResult(array(
					'success' => true,
					'snapshot' => Leads_RoundRobinService::getPoolSnapshot(),
				));
			} else {
				$response->setError(400, 'Unknown mode');
			}
		} catch (Exception $e) {
			$response->setError($e->getCode() ? $e->getCode() : 500, $e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
