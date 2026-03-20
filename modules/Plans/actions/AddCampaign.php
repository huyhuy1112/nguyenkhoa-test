<?php
/*+**********************************************************************************
 * Add Campaign(s) to Plan without page reload.
 *************************************************************************************/

class Plans_AddCampaign_Action extends Vtiger_Action_Controller {
	public function checkPermission(Vtiger_Request $request) {
		// rely on core access control
	}

	protected function getCampaignTableName(array $campaignIds = array()) {
		global $adb;
		$primary = 'vtiger_campaign';
		$alt = 'vtiger_campaigns';

		$hasAlt = false;
		$ct = $adb->pquery("SHOW TABLES LIKE ?", array($alt));
		if ($ct && $adb->num_rows($ct) > 0) {
			$hasAlt = true;
		}

		if (!$hasAlt) {
			return $primary;
		}

		// If both exist, prefer the one that actually contains the selected IDs.
		$campaignIds = array_values(array_filter(array_map('intval', $campaignIds)));
		if (count($campaignIds) === 0) {
			return $primary;
		}

		$testId = (int)$campaignIds[0];
		$chkAlt = $adb->pquery("SELECT 1 FROM {$alt} WHERE campaignid = ? LIMIT 1", array($testId));
		if ($chkAlt && $adb->num_rows($chkAlt) > 0) {
			return $alt;
		}
		$chkPrimary = $adb->pquery("SELECT 1 FROM {$primary} WHERE campaignid = ? LIMIT 1", array($testId));
		if ($chkPrimary && $adb->num_rows($chkPrimary) > 0) {
			return $primary;
		}

		// Fallback to primary (most vtiger builds use vtiger_campaign)
		return $primary;
	}

	public function process(Vtiger_Request $request) {
		global $adb;

		$planId = (int)$request->get('plan_id');
		$campaignIds = $request->get('campaign_ids');
		error_log('[Plans][AddCampaign] received plan_id=' . $planId . ' campaign_ids_raw=' . json_encode($campaignIds, JSON_UNESCAPED_UNICODE));
		if (!is_array($campaignIds)) {
			$campaignIds = ($campaignIds ? array($campaignIds) : array());
		}

		$campaignIds = array_values(array_filter(array_map(function ($x) {
			$v = (int)$x;
			return $v > 0 ? $v : null;
		}, $campaignIds)));
		error_log('[Plans][AddCampaign] normalized campaign_ids=' . json_encode($campaignIds, JSON_UNESCAPED_UNICODE));

		// Ensure table exists
		$t = $adb->pquery("SHOW TABLES LIKE ?", array('vtiger_plan_campaigns'));
		if (!$t || $adb->num_rows($t) === 0) {
			$response = new Vtiger_Response();
			$payload = array('success' => false, 'inserted' => 0, 'skipped' => 0, 'error' => 'Missing table vtiger_plan_campaigns. Run InstallPlanRedesign.php');
			error_log('[Plans][AddCampaign] response=' . json_encode($payload, JSON_UNESCAPED_UNICODE));
			$response->setResult($payload);
			$response->emit();
			return;
		}

		if ($planId <= 0) {
			$response = new Vtiger_Response();
			$payload = array('success' => false, 'inserted' => 0, 'skipped' => 0, 'error' => 'Invalid plan_id');
			error_log('[Plans][AddCampaign] response=' . json_encode($payload, JSON_UNESCAPED_UNICODE));
			$response->setResult($payload);
			$response->emit();
			return;
		}
		if (count($campaignIds) === 0) {
			$response = new Vtiger_Response();
			$payload = array('success' => false, 'inserted' => 0, 'skipped' => 0, 'error' => 'No campaign_ids provided');
			error_log('[Plans][AddCampaign] response=' . json_encode($payload, JSON_UNESCAPED_UNICODE));
			$response->setResult($payload);
			$response->emit();
			return;
		}

		$campaignTable = $this->getCampaignTableName($campaignIds);
		error_log('[Plans][AddCampaign] campaignTable=' . $campaignTable);

		$inserted = 0;
		$skipped = 0;
		$firstError = '';

		foreach ($campaignIds as $cid) {
			$cid = (int)$cid;
			if ($cid <= 0) continue;

			// Pull dates/status from Campaign as defaults
			$c = $adb->pquery(
				"SELECT start_date, actual_end_date, closingdate, campaignstatus
				 FROM {$campaignTable} WHERE campaignid = ?",
				array($cid)
			);
			$start = null;
			$end = null;
			$status = null;
			if ($c && $adb->num_rows($c) > 0) {
				$start = $adb->query_result($c, 0, 'start_date');
				$end = $adb->query_result($c, 0, 'actual_end_date');
				if (empty($end)) $end = $adb->query_result($c, 0, 'closingdate');
				if (empty($start)) $start = $end;
				$status = $adb->query_result($c, 0, 'campaignstatus');
			}

			// Insert if not exists
			$chk = $adb->pquery(
				"SELECT id FROM vtiger_plan_campaigns WHERE plan_id = ? AND campaign_id = ?",
				array($planId, $cid)
			);
			if ($chk && $adb->num_rows($chk) > 0) {
				$skipped++;
				error_log('[Plans][AddCampaign] skip existing plan_id=' . $planId . ' campaign_id=' . $cid);
				continue;
			}

			error_log('[Plans][AddCampaign] insert plan_id=' . $planId . ' campaign_id=' . $cid . ' start=' . (string)$start . ' end=' . (string)$end . ' status=' . (string)$status);
			$ins = $adb->pquery(
				"INSERT INTO vtiger_plan_campaigns(plan_id, campaign_id, start_date, end_date, status, createdtime)
				 VALUES(?,?,?,?,?,NOW())",
				array($planId, $cid, $start, $end, $status)
			);
			if ($ins === false) {
				$firstError = "Insert failed for campaign_id={$cid}";
				break;
			} else {
				$inserted++;
			}

			// Defensive one-time verification per insert
			$vr = $adb->pquery(
				"SELECT COUNT(*) AS c FROM vtiger_plan_campaigns WHERE plan_id = ? AND campaign_id = ?",
				array($planId, $cid)
			);
			$vc = ($vr && $adb->num_rows($vr) > 0) ? (int)$adb->query_result($vr, 0, 'c') : 0;
			error_log('[Plans][AddCampaign] verify plan_id=' . $planId . ' campaign_id=' . $cid . ' count=' . $vc);
		}

		// Post-insert verification for this plan
		$checkPlan = $adb->pquery(
			"SELECT id, plan_id, campaign_id FROM vtiger_plan_campaigns WHERE plan_id = ? ORDER BY id DESC",
			array($planId)
		);
		$planRows = $checkPlan ? $adb->num_rows($checkPlan) : 0;
		error_log('[Plans][AddCampaign] verify plan_id=' . $planId . ' rows_in_vtiger_plan_campaigns=' . (int)$planRows);

		$response = new Vtiger_Response();
		if ($firstError !== '') {
			$msg = $firstError;
			if (isset($adb->database) && method_exists($adb->database, 'ErrorMsg')) {
				$errMsg = (string)$adb->database->ErrorMsg();
				if ($errMsg !== '') $msg .= ' | DB: ' . $errMsg;
			}
			$payload = array('success' => false, 'inserted' => $inserted, 'skipped' => $skipped, 'error' => $msg);
			error_log('[Plans][AddCampaign] response=' . json_encode($payload, JSON_UNESCAPED_UNICODE));
			$response->setResult($payload);
		} else {
			$payload = array('success' => true, 'inserted' => $inserted, 'skipped' => $skipped);
			error_log('[Plans][AddCampaign] response=' . json_encode($payload, JSON_UNESCAPED_UNICODE));
			$response->setResult($payload);
		}
		$response->emit();
	}
}

