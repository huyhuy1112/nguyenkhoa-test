<?php
/*+***********************************************************************************
 * Install/Upgrade Plans "Campaign Manager" redesign.
 *
 * - Creates vtiger_plan_campaigns (plan campaigns schedule table)
 * - Adds vtiger_plans.plan_code column (if missing)
 * - Migrates existing links from vtiger_plans_campaign_rel into vtiger_plan_campaigns
 *
 * Run from vtiger root:
 *   php -f modules/Plans/scripts/InstallPlanRedesign.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';

global $adb;

echo "=== Install Plans Redesign ===\n";

// 1) Ensure plan_code column exists
$col = $adb->pquery("SHOW COLUMNS FROM vtiger_plans LIKE 'plan_code'", array());
if (!$col || $adb->num_rows($col) == 0) {
	$adb->pquery("ALTER TABLE vtiger_plans ADD COLUMN plan_code VARCHAR(50) DEFAULT NULL", array());
	echo "Added vtiger_plans.plan_code\n";
}

// 2) Create new schedule table
$adb->pquery("CREATE TABLE IF NOT EXISTS vtiger_plan_campaigns (
	id INT(11) NOT NULL AUTO_INCREMENT,
	plan_id INT(11) NOT NULL,
	campaign_id INT(11) NOT NULL,
	start_date DATE DEFAULT NULL,
	end_date DATE DEFAULT NULL,
	status VARCHAR(100) DEFAULT NULL,
	createdtime DATETIME DEFAULT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY uniq_plan_campaign (plan_id, campaign_id),
	KEY idx_plan (plan_id),
	KEY idx_campaign (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;", array());
echo "Table ensured: vtiger_plan_campaigns\n";

// 3) Migrate from old relation table if present
$t = $adb->pquery("SHOW TABLES LIKE ?", array('vtiger_plans_campaign_rel'));
if ($t && $adb->num_rows($t) > 0) {
	$campaignTable = 'vtiger_campaign';
	$ct = $adb->pquery("SHOW TABLES LIKE ?", array('vtiger_campaigns'));
	if ($ct && $adb->num_rows($ct) > 0) {
		$campaignTable = 'vtiger_campaigns';
	}

	$res = $adb->pquery(
		"SELECT r.planid, r.campaignid
		 FROM vtiger_plans_campaign_rel r",
		array()
	);
	$migrated = 0;
	for ($i = 0; $i < $adb->num_rows($res); $i++) {
		$planId = (int)$adb->query_result($res, $i, 'planid');
		$cid = (int)$adb->query_result($res, $i, 'campaignid');

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
			if (empty($end)) {
				$end = $adb->query_result($c, 0, 'closingdate');
			}
			if (empty($start)) {
				$start = $end;
			}
			$status = $adb->query_result($c, 0, 'campaignstatus');
		}

		$chk = $adb->pquery(
			"SELECT 1 FROM vtiger_plan_campaigns WHERE plan_id = ? AND campaign_id = ?",
			array($planId, $cid)
		);
		if ($chk && $adb->num_rows($chk) > 0) {
			continue;
		}
		$adb->pquery(
			"INSERT INTO vtiger_plan_campaigns(plan_id, campaign_id, start_date, end_date, status, createdtime)
			 VALUES(?,?,?,?,?,NOW())",
			array($planId, $cid, $start, $end, $status)
		);
		$migrated++;
	}
	echo "Migrated rows from vtiger_plans_campaign_rel: $migrated\n";
}

echo "=== Done ===\n";

