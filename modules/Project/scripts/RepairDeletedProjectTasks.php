<?php
/*+**********************************************************************************
 * Repair ProjectTask rows whose parent Project is already soft-deleted but tasks are not.
 * Idempotent: only updates vtiger_crmentity where deleted = 0 for ProjectTask.
 *
 * Run from CRM root (does not run automatically):
 *   php -f modules/Project/scripts/RepairDeletedProjectTasks.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';

global $adb, $current_user;
if (empty($adb)) {
	$adb = PearDatabase::getInstance();
}

$modifiedBy = (isset($current_user) && !empty($current_user->id)) ? (int) $current_user->id : 0;
$dateVar = date('Y-m-d H:i:s');
$modifiedTime = $adb->formatDate($dateVar, true);

$sql = "SELECT pt.projecttaskid AS crmid
	FROM vtiger_projecttask pt
	INNER JOIN vtiger_crmentity tce ON tce.crmid = pt.projecttaskid AND tce.deleted = 0 AND tce.setype = 'ProjectTask'
	INNER JOIN vtiger_crmentity pce ON pce.crmid = pt.projectid AND pce.setype = 'Project' AND pce.deleted = 1";

$result = $adb->pquery($sql, array());
if (!$result) {
	echo "ERROR: query failed.\n";
	exit(1);
}

$rows = $adb->num_rows($result);
$ids = array();
for ($i = 0; $i < $rows; $i++) {
	$ids[] = (int) $adb->query_result($result, $i, 'crmid');
}

if (php7_count($ids) === 0) {
	echo "No orphan ProjectTask records found (tasks with deleted=0 under deleted=1 Project).\n";
	exit(0);
}

echo 'Soft-deleting ProjectTask crmid(s): ' . implode(', ', $ids) . "\n";

$chunks = array_chunk($ids, 500);
foreach ($chunks as $chunk) {
	$placeholders = implode(',', array_fill(0, php7_count($chunk), '?'));
	$params = array_merge(array($modifiedTime, $modifiedBy), $chunk);
	$adb->pquery(
		"UPDATE vtiger_crmentity SET deleted = 1, modifiedtime = ?, modifiedby = ?
			WHERE crmid IN ($placeholders) AND setype = 'ProjectTask' AND deleted = 0",
		$params
	);
}

echo 'Done. Updated ' . php7_count($ids) . " record(s).\n";
