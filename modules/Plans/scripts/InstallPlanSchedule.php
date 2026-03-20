<?php
/*+***********************************************************************************
 * Install schedule/linking support for Plans:
 * - Create relation table vtiger_plans_campaign_rel
 * - Ensure Plan has a default Custom View filter (if missing)
 *
 * Run:
 *   php -f modules/Plans/scripts/InstallPlanSchedule.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';

global $adb;

echo "=== Install Plans Schedule ===\n";

// Relation table: Plan -> Campaigns
$adb->pquery("CREATE TABLE IF NOT EXISTS vtiger_plans_campaign_rel (
	planid INT(11) NOT NULL,
	campaignid INT(11) NOT NULL,
	PRIMARY KEY(planid, campaignid),
	KEY idx_campaignid (campaignid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;", array());

echo "Table ensured: vtiger_plans_campaign_rel\n";

// Ensure custom view exists for Plans to avoid ListView fatal
$res = $adb->pquery("SELECT cvid FROM vtiger_customview WHERE entitytype = ? ORDER BY setdefault DESC, cvid ASC", array('Plans'));
if (!$res || $adb->num_rows($res) == 0) {
	$cvid = (int)$adb->getUniqueID('vtiger_customview');
	$adb->pquery(
		"INSERT INTO vtiger_customview(cvid, viewname, setdefault, setmetrics, entitytype, status, userid)
		 VALUES(?,?,?,?,?,?,?)",
		array($cvid, 'All', 1, 0, 'Plans', 0, 1)
	);

	$columns = array(
		'vtiger_crmentity:smownerid:assigned_user_id:Assigned To:V',
		'vtiger_plans:planname:planname:Plan Name:V',
		'vtiger_plans:plan_status:plan_status:Status:V',
		'vtiger_plans:start_date:start_date:Start Date:D',
		'vtiger_plans:end_date:end_date:End Date:D',
	);
	foreach ($columns as $idx => $col) {
		$adb->pquery("INSERT INTO vtiger_cvcolumnlist(cvid, columnindex, columnname) VALUES(?,?,?)", array($cvid, $idx, $col));
	}
	echo "Inserted default custom view for Plans.\n";
}

echo "=== Done ===\n";

