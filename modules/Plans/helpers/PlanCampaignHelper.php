<?php
/*+**********************************************************************************
 * Shared plan ↔ campaign row loading for Plans detail + GetSchedule AJAX.
 *************************************************************************************/

class PlanCampaignHelper {

	public static function getCampaignTableName(PearDatabase $adb) {
		$primary = 'vtiger_campaign';
		$t1 = $adb->pquery("SHOW TABLES LIKE ?", array($primary));
		if ($t1 && $adb->num_rows($t1) > 0) {
			return $primary;
		}
		return 'vtiger_campaigns';
	}

	public static function hasColumn(PearDatabase $adb, $table, $column) {
		$r = $adb->pquery("SHOW COLUMNS FROM {$table} LIKE ?", array($column));
		return ($r && $adb->num_rows($r) > 0);
	}

	public static function parseMoney($value) {
		if ($value === null) {
			return 0.0;
		}
		$value = str_replace(',', '', (string)$value);
		return (float)$value;
	}

	/**
	 * @return array<int, array<string, mixed>>|null null if query failed
	 */
	public static function fetchPlanCampaignRows(PearDatabase $adb, $planId) {
		$planId = (int)$planId;
		if ($planId <= 0) {
			return array();
		}

		$campaignTable = self::getCampaignTableName($adb);
		$hasActual = self::hasColumn($adb, $campaignTable, 'actualcost');
		$hasBudget = self::hasColumn($adb, $campaignTable, 'budgetcost');
		$hasExpected = self::hasColumn($adb, $campaignTable, 'expectedrevenue');

		if ($hasActual && $hasBudget) {
			$costExpr = "COALESCE(NULLIF(c.actualcost,0), NULLIF(c.budgetcost,0), 0)";
		} elseif ($hasActual) {
			$costExpr = "COALESCE(c.actualcost, 0)";
		} elseif ($hasBudget) {
			$costExpr = "COALESCE(c.budgetcost, 0)";
		} else {
			$costExpr = "0";
		}
		$revExpr = $hasExpected ? "COALESCE(NULLIF(c.expectedrevenue,0), 0)" : "0";

		$sql = "SELECT
				pc.id,
				pc.campaign_id,
				pc.start_date,
				pc.end_date,
				pc.status,
				pc.createdtime,
				c.campaignname,
				ce.description AS description,
				{$costExpr} AS effective_cost,
				{$revExpr} AS effective_revenue
			 FROM vtiger_plan_campaigns pc
			 INNER JOIN {$campaignTable} c ON c.campaignid = pc.campaign_id
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = c.campaignid
			 WHERE ce.deleted = 0 AND pc.plan_id = ?
			 ORDER BY pc.start_date ASC, pc.id ASC";

		$res = $adb->pquery($sql, array($planId));
		if ($res === false) {
			return null;
		}
		$rows = array();
		if ($res) {
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$row = $adb->fetchByAssoc($res, $i);
				$start = trim((string)$row['start_date']);
				$end = trim((string)$row['end_date']);
				$cost = self::parseMoney(isset($row['effective_cost']) ? $row['effective_cost'] : 0);
				$revenue = self::parseMoney(isset($row['effective_revenue']) ? $row['effective_revenue'] : 0);
				$profit = $revenue - $cost;
				$roi = $cost > 0.0000001 ? (($revenue - $cost) / $cost) * 100.0 : 0.0;

				$rows[] = array(
					'id' => (int)$row['id'],
					'campaign_id' => (int)$row['campaign_id'],
					'campaignname' => (string)$row['campaignname'],
					'start_date' => $start,
					'end_date' => $end,
					'status' => (string)$row['status'],
					'description' => (string)$row['description'],
					'link' => 'index.php?module=Campaigns&view=Detail&record=' . (int)$row['campaign_id'],
					'cost' => $cost,
					'revenue' => $revenue,
					'profit' => $profit,
					'roi' => $roi,
				);
			}
		}
		return $rows;
	}
}
