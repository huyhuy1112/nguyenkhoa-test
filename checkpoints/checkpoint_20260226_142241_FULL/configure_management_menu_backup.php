<?php
/**
 * Configure Management App Menu (Backup - từ testcrm, giữ Schedule + Teams)
 *
 * Gán các module vào app "Management":
 * - Calendar → Schedule
 * - Teams → Teams (module Teams, không dùng Users)
 * - Reports → Report
 * - Documents → Document
 * - Project → Projects
 * - ProjectTask → Project Tasks
 *
 * Muốn thêm module khác vào MANAGEMENT: dùng script add_module_to_management.php
 *   php add_module_to_management.php <TênModule> [TênModule2 ...]
 *
 * Tham khảo: https://github.com/huyhuy1112/testcrm (MANAGEMENT_MENU_CONFIGURATION.md)
 * Khác testcrm: Backup dùng module Teams, không dùng Users làm "Team".
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';

$adb = PearDatabase::getInstance();

echo "==========================================\n";
echo "Configure Management App Menu (Backup)\n";
echo "==========================================\n\n";

// Management app: Schedule + Teams + Report + Document + Project + ProjectTask
$modules = array(
	'Calendar'   => array('label' => 'Schedule', 'display' => 'Schedule'),
	'Teams'      => array('label' => 'Teams', 'display' => 'Teams'),
	'Reports'    => array('label' => 'Report', 'display' => 'Report'),
	'Documents'  => array('label' => 'Document', 'display' => 'Document'),
	'Project'    => array('label' => 'Projects', 'display' => 'Projects'),
	'ProjectTask'=> array('label' => 'Project Tasks', 'display' => 'Project Tasks'),
);

// Must match Vtiger_MenuStructure_Model::getAppMenuList() entry
$appName = 'MANAGEMENT';

echo "Step 1: Fetching module tabids...\n";
$moduleTabIds = array();
foreach ($modules as $moduleName => $config) {
	$result = $adb->pquery("SELECT tabid, tablabel FROM vtiger_tab WHERE name = ?", array($moduleName));
	if ($adb->num_rows($result) > 0) {
		$tabid = $adb->query_result($result, 0, 'tabid');
		$tablabel = $adb->query_result($result, 0, 'tablabel');
		$moduleTabIds[$moduleName] = array(
			'tabid' => $tabid,
			'tablabel' => $tablabel,
			'display' => $config['display']
		);
		echo "  ✓ $moduleName: tabid=$tabid, label='$tablabel'\n";
	} else {
		echo "  ✗ $moduleName: NOT FOUND (bỏ qua)\n";
	}
}

if (empty($moduleTabIds)) {
	die("ERROR: No modules found for Management app.\n");
}

echo "\nStep 2: Removing these modules from other apps (except Management)...\n";
$tabids = array_column($moduleTabIds, 'tabid');
$placeholders = implode(',', array_fill(0, count($tabids), '?'));
$adb->pquery(
	"DELETE FROM vtiger_app2tab WHERE tabid IN ($placeholders) AND appname != ?",
	array_merge($tabids, array($appName))
);
echo "  ✓ Done.\n\n";

echo "Step 3: Adding/updating mappings to Management app...\n";
$seqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array($appName));
$nextSeq = ($adb->num_rows($seqResult) > 0 && $adb->query_result($seqResult, 0, 'max_seq') !== null)
	? intval($adb->query_result($seqResult, 0, 'max_seq')) + 1 : 1;

foreach ($moduleTabIds as $moduleName => $info) {
	$tabid = $info['tabid'];
	$check = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", array($appName, $tabid));
	if ($adb->num_rows($check) == 0) {
		$adb->pquery(
			"INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
			array($appName, $tabid, $nextSeq, 1)
		);
		echo "  ✓ Added $moduleName (sequence: $nextSeq)\n";
	} else {
		$adb->pquery(
			"UPDATE vtiger_app2tab SET sequence = ?, visible = 1 WHERE appname = ? AND tabid = ?",
			array($nextSeq, $appName, $tabid)
		);
		echo "  ✓ Updated $moduleName (sequence: $nextSeq)\n";
	}
	$nextSeq++;
}

echo "\nStep 4: Updating display labels...\n";
foreach ($moduleTabIds as $moduleName => $info) {
	$newLabel = $info['display'];
	$currentLabel = $info['tablabel'];
	if ($currentLabel !== $newLabel) {
		$adb->pquery("UPDATE vtiger_tab SET tablabel = ? WHERE tabid = ?", array($newLabel, $info['tabid']));
		echo "  ✓ $moduleName: '$currentLabel' -> '$newLabel'\n";
	} else {
		echo "  - $moduleName: already '$currentLabel'\n";
	}
}

echo "\nStep 5: Verify Management menu...\n";
$tabids = array_column($moduleTabIds, 'tabid');
$placeholders = implode(',', array_fill(0, count($tabids), '?'));
$verify = $adb->pquery(
	"SELECT t.name, t.tablabel, a.sequence FROM vtiger_app2tab a 
	 INNER JOIN vtiger_tab t ON t.tabid = a.tabid 
	 WHERE a.appname = ? AND a.tabid IN ($placeholders) ORDER BY a.sequence",
	array_merge(array($appName), $tabids)
);
echo str_repeat("-", 50) . "\n";
printf("%-15s %-18s %s\n", "Module", "Display", "Sequence");
echo str_repeat("-", 50) . "\n";
while ($row = $adb->fetchByAssoc($verify)) {
	printf("%-15s %-18s %s\n", $row['name'], $row['tablabel'], $row['sequence']);
}
echo str_repeat("-", 50) . "\n";

echo "\n✓ Xong. Management app có: Schedule (Calendar), Teams, Report, Document, Projects, Project Tasks.\n";
echo "  Clear cache: Settings → Configuration → Clear Cache, rồi logout/login.\n\n";
