<?php
/*+**********************************************************************************
 * Get schedule data for a Plan (JSON).
 *************************************************************************************/

class Plans_GetSchedule_Action extends Vtiger_Action_Controller {
	public function checkPermission(Vtiger_Request $request) {
		// rely on core access control
	}

	protected function getCampaignTableName() {
		global $adb;
		// Requirement: use vtiger_campaign if it exists, otherwise vtiger_campaigns
		$primary = 'vtiger_campaign';
		$t1 = $adb->pquery("SHOW TABLES LIKE ?", array($primary));
		if ($t1 && $adb->num_rows($t1) > 0) {
			return $primary;
		}
		return 'vtiger_campaigns';
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

		$campaignTable = $this->getCampaignTableName();
		error_log('[Plans][GetSchedule] campaignTable=' . $campaignTable);

		// Requirement: pc.plan_id is the only plan filter.
		// Keep crmentity join only for deleted filter (no extra conditions).
		$sql = "SELECT
			pc.id,
			pc.plan_id,
			pc.campaign_id,
			pc.start_date,
			pc.end_date,
			pc.status,
			c.campaignname,
			ce.description AS description
		 FROM vtiger_plan_campaigns pc
		 INNER JOIN {$campaignTable} c ON c.campaignid = pc.campaign_id
		 INNER JOIN vtiger_crmentity ce ON ce.crmid = c.campaignid
		 WHERE pc.plan_id = ?
		   AND ce.deleted = 0
		 ORDER BY pc.start_date ASC, pc.id ASC";
		error_log('[Plans][GetSchedule] sql=' . $sql);

		$res = $adb->pquery($sql, array($planId));
		if ($res === false) {
			$msg = 'Query failed';
			if (isset($adb->database) && method_exists($adb->database, 'ErrorMsg')) {
				$errMsg = (string)$adb->database->ErrorMsg();
				if ($errMsg !== '') $msg .= ' | DB: ' . $errMsg;
			}
			$response = new Vtiger_Response();
			$response->setResult(array('success' => false, 'error' => $msg, 'rows' => array(), 'count' => 0));
			$response->emit();
			return;
		}

		$rows = array();
		$dbRowCount = (int)$adb->num_rows($res);
		for ($i = 0; $i < $dbRowCount; $i++) {
			$r = $adb->fetchByAssoc($res, $i);
			$start = trim((string)$r['start_date']);
			$end = trim((string)$r['end_date']);
			$rows[] = array(
				'id' => (int)$r['id'],
				'campaign_id' => (int)$r['campaign_id'],
				'campaignname' => (string)$r['campaignname'],
				'start_date' => $start,
				'end_date' => $end,
				'status' => (string)$r['status'],
				'description' => (string)$r['description'],
				'link' => 'index.php?module=Campaigns&view=Detail&record=' . (int)$r['campaign_id'],
			);
		}

		error_log('[Plans][GetSchedule] rowCount=' . count($rows) . ' dbRowCount=' . $dbRowCount);
		$response = new Vtiger_Response();
		$response->setResult(array('success' => true, 'rows' => $rows, 'count' => count($rows)));
		$response->emit();
	}
}

