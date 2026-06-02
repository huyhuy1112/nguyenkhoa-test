<?php
/**
 * Put Leads at the top of the SALES app menu (above Opportunities).
 * Run once: php modules/Leads/scripts/PutLeadsFirstInSalesMenu.php
 */

require_once __DIR__ . '/../../../config.inc.php';
require_once __DIR__ . '/../../../include/database/PearDatabase.php';
require_once __DIR__ . '/../../../include/utils/utils.php';

$adb = PearDatabase::getInstance();
$appName = 'SALES';

$leadsTabId = getTabid('Leads');
if (empty($leadsTabId)) {
	echo "ERROR: Leads module not found.\n";
	exit(1);
}

$result = $adb->pquery(
	'SELECT tabid, sequence FROM vtiger_app2tab WHERE appname = ? AND visible = 1 ORDER BY sequence ASC',
	array($appName)
);
$rows = array();
while ($row = $adb->fetchByAssoc($result)) {
	$rows[] = $row;
}

if (empty($rows)) {
	echo "ERROR: No visible modules in SALES app.\n";
	exit(1);
}

$orderedTabIds = array((int) $leadsTabId);
foreach ($rows as $row) {
	$tabId = (int) $row['tabid'];
	if ($tabId === (int) $leadsTabId) {
		continue;
	}
	$orderedTabIds[] = $tabId;
}

$seq = 1;
foreach ($orderedTabIds as $tabId) {
	$adb->pquery(
		'UPDATE vtiger_app2tab SET sequence = ? WHERE appname = ? AND tabid = ?',
		array($seq, $appName, $tabId)
	);
	$moduleName = getTabModuleName($tabId);
	echo sprintf("  sequence %d -> %s (tabid %d)\n", $seq, $moduleName, $tabId);
	$seq++;
}

if (class_exists('Vtiger_Cache')) {
	Vtiger_Cache::flush();
}

echo "\nDone. Leads is now first in SALES menu. Log out/in or hard refresh if needed.\n";
