<?php
/*+**********************************************************************************
 * Remove Campaign from Plan (delete row in vtiger_plan_campaigns).
 *************************************************************************************/

class Plans_DeleteCampaign_Action extends Vtiger_Action_Controller {
	public function checkPermission(Vtiger_Request $request) {
		// rely on core access control
	}

	public function process(Vtiger_Request $request) {
		global $adb;

		$planId = (int)$request->get('plan_id');
		$rowId = (int)$request->get('id');
		if ($planId <= 0 || $rowId <= 0) {
			$response = new Vtiger_Response();
			$response->setResult(array('success' => false, 'error' => 'Invalid plan_id or id'));
			$response->emit();
			return;
		}

		$del = $adb->pquery("DELETE FROM vtiger_plan_campaigns WHERE id = ? AND plan_id = ?", array($rowId, $planId));

		$response = new Vtiger_Response();
		if ($del === false) {
			$msg = 'Delete failed';
			if (isset($adb->database) && method_exists($adb->database, 'ErrorMsg')) {
				$errMsg = (string)$adb->database->ErrorMsg();
				if ($errMsg !== '') $msg .= ' | DB: ' . $errMsg;
			}
			$response->setResult(array('success' => false, 'error' => $msg));
		} else {
			$response->setResult(array('success' => true));
		}
		$response->emit();
	}
}

