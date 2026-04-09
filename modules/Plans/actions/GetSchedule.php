<?php
/*+**********************************************************************************
 * Get schedule data for a Plan (JSON).
 *************************************************************************************/

require_once dirname(__FILE__) . '/../helpers/PlanCampaignHelper.php';

class Plans_GetSchedule_Action extends Vtiger_Action_Controller {
	public function checkPermission(Vtiger_Request $request) {
		// rely on core access control
	}

	public function process(Vtiger_Request $request) {
		global $adb;

		$planId = (int)$request->get('plan_id');
		error_log('[Plans][GetSchedule] received plan_id=' . $planId);
		if ($planId <= 0) {
			$response = new Vtiger_Response();
			$response->setResult(array('success' => false, 'error' => 'Invalid plan_id', 'rows' => array(), 'count' => 0));
			$response->emit();
			return;
		}

		// Ensure table exists
		$t = $adb->pquery("SHOW TABLES LIKE ?", array('vtiger_plan_campaigns'));
		if (!$t || $adb->num_rows($t) === 0) {
			$response = new Vtiger_Response();
			$response->setResult(array('success' => false, 'error' => 'Missing table vtiger_plan_campaigns. Run InstallPlanRedesign.php', 'rows' => array(), 'count' => 0));
			$response->emit();
			return;
		}

		$rows = PlanCampaignHelper::fetchPlanCampaignRows($adb, $planId);
		if ($rows === null) {
			$msg = 'Query failed';
			if (isset($adb->database) && method_exists($adb->database, 'ErrorMsg')) {
				$errMsg = (string)$adb->database->ErrorMsg();
				if ($errMsg !== '') {
					$msg .= ' | DB: ' . $errMsg;
				}
			}
			$response = new Vtiger_Response();
			$response->setResult(array('success' => false, 'error' => $msg, 'rows' => array(), 'count' => 0));
			$response->emit();
			return;
		}

		error_log('[Plans][GetSchedule] rowCount=' . count($rows));
		$response = new Vtiger_Response();
		$response->setResult(array('success' => true, 'rows' => $rows, 'count' => count($rows)));
		$response->emit();
	}
}

