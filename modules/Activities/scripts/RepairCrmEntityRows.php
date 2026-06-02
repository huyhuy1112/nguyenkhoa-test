<?php
/**
 * Repair missing vtiger_crmentity / vtiger_activitiescf rows for Activities.
 *
 * Idempotent: safe to run multiple times.
 *
 * Usage:
 *   php modules/Activities/scripts/RepairCrmEntityRows.php
 */

chdir(dirname(__FILE__) . '/../../..');

require_once 'config.inc.php';
require_once 'include/utils/utils.php';

$adb = PearDatabase::getInstance();

function ensureEntitynameForActivities(PearDatabase $adb): void {
	$res = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name='Activities'", []);
	if (!$res || $adb->num_rows($res) === 0) {
		echo "ERROR: vtiger_tab missing for Activities\n";
		return;
	}
	$tabid = (int)$adb->query_result($res, 0, 'tabid');

	// Prefer 'title' if it exists; otherwise fallback to 'content'
	$hasTitle = false;
	$colRes = $adb->pquery("SHOW COLUMNS FROM vtiger_activities LIKE 'title'", []);
	if ($colRes && $adb->num_rows($colRes) > 0) {
		$hasTitle = true;
	}
	$fieldname = $hasTitle ? 'title' : 'content';

	$adb->pquery(
		"REPLACE INTO vtiger_entityname (tabid, modulename, tablename, fieldname, entityidfield)
		 VALUES (?, 'Activities', 'vtiger_activities', ?, 'activityid')",
		[$tabid, $fieldname]
	);
}

ensureEntitynameForActivities($adb);

// Figure out the actual CF PK column name (expected: activitiesid)
$cfIdColumn = null;
$cfCols = $adb->pquery("SHOW COLUMNS FROM vtiger_activitiescf", []);
if ($cfCols) {
	for ($i = 0; $i < $adb->num_rows($cfCols); $i++) {
		$field = $adb->query_result($cfCols, $i, 'Field');
		$key = $adb->query_result($cfCols, $i, 'Key');
		if ($key === 'PRI') {
			$cfIdColumn = $field;
			break;
		}
	}
}
if (!$cfIdColumn) {
	$cfIdColumn = 'activitiesid';
}

$missingCrmEntity = [];
$fixedSetype = [];
$fixedDeleted = [];
$createdCf = [];

// Activities rows that don't have vtiger_crmentity
$res = $adb->pquery(
	"SELECT a.activityid,
	        a.assigned_user_id,
	        a.createdtime, a.modifiedtime,
	        a.content, a.title, a.activity_type
	   FROM vtiger_activities a
	  WHERE NOT EXISTS (SELECT 1 FROM vtiger_crmentity ce WHERE ce.crmid = a.activityid)",
	[]
);

if ($res) {
	for ($i = 0; $i < $adb->num_rows($res); $i++) {
		$activityid = (int)$adb->query_result($res, $i, 'activityid');
		$assigned = (int)$adb->query_result($res, $i, 'assigned_user_id');
		$ownerId = $assigned > 0 ? $assigned : 1;

		$created = $adb->query_result($res, $i, 'createdtime');
		$modified = $adb->query_result($res, $i, 'modifiedtime');
		$created = $created ?: date('Y-m-d H:i:s');
		$modified = $modified ?: $created;

		$content = (string)$adb->query_result($res, $i, 'content');
		$title = (string)$adb->query_result($res, $i, 'title');
		$type = (string)$adb->query_result($res, $i, 'activity_type');
		$label = $title ?: ($content ?: ($type ?: ('Activity ' . $activityid)));

		$adb->pquery(
			"INSERT INTO vtiger_crmentity
			 (crmid, smcreatorid, smownerid, setype, description, createdtime, modifiedtime, presence, deleted, label)
			 VALUES (?, ?, ?, 'Activities', ?, ?, ?, 1, 0, ?)",
			[$activityid, $ownerId, $ownerId, $content, $created, $modified, $label]
		);
		$missingCrmEntity[] = $activityid;
	}
}

// Fix wrong setype/deleted on existing crmentity rows for activities
$res = $adb->pquery(
	"SELECT ce.crmid, ce.setype, ce.deleted
	   FROM vtiger_crmentity ce
	   JOIN vtiger_activities a ON a.activityid = ce.crmid
	  WHERE (ce.setype <> 'Activities' OR ce.deleted <> 0)",
	[]
);
if ($res) {
	for ($i = 0; $i < $adb->num_rows($res); $i++) {
		$crmid = (int)$adb->query_result($res, $i, 'crmid');
		$setype = (string)$adb->query_result($res, $i, 'setype');
		$deleted = (int)$adb->query_result($res, $i, 'deleted');

		if ($setype !== 'Activities') {
			$adb->pquery("UPDATE vtiger_crmentity SET setype='Activities' WHERE crmid=?", [$crmid]);
			$fixedSetype[] = $crmid;
		}
		if ($deleted !== 0) {
			$adb->pquery("UPDATE vtiger_crmentity SET deleted=0 WHERE crmid=?", [$crmid]);
			$fixedDeleted[] = $crmid;
		}
	}
}

// Ensure CF row exists for each activity
$res = $adb->pquery(
	"SELECT a.activityid
	   FROM vtiger_activities a
	  WHERE NOT EXISTS (
	        SELECT 1 FROM vtiger_activitiescf cf
	         WHERE cf.$cfIdColumn = a.activityid
	  )",
	[]
);
if ($res) {
	for ($i = 0; $i < $adb->num_rows($res); $i++) {
		$activityid = (int)$adb->query_result($res, $i, 'activityid');
		$adb->pquery("INSERT INTO vtiger_activitiescf ($cfIdColumn) VALUES (?)", [$activityid]);
		$createdCf[] = $activityid;
	}
}

echo "Repair done.\n";
echo "Inserted vtiger_crmentity: " . (count($missingCrmEntity) ? implode(',', $missingCrmEntity) : '(none)') . "\n";
echo "Fixed setype: " . (count($fixedSetype) ? implode(',', $fixedSetype) : '(none)') . "\n";
echo "Fixed deleted: " . (count($fixedDeleted) ? implode(',', $fixedDeleted) : '(none)') . "\n";
echo "Inserted vtiger_activitiescf: " . (count($createdCf) ? implode(',', $createdCf) : '(none)') . "\n";

