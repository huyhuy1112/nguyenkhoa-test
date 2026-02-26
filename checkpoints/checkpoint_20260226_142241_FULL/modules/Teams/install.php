<?php
/*+**********************************************************************************
 * Teams Module Installation Script
 * 
 * This script:
 * 1. Creates database tables
 * 2. Registers module in vtiger_tab
 * 3. Links to Management app menu
 * 4. Auto-grants permissions
 * 5. Clears cache
 */

// Change to project root
chdir(dirname(dirname(dirname(__FILE__))));

require_once('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/utils/CommonUtils.php');

$db = PearDatabase::getInstance();

echo "=== Teams Module Installation ===\n\n";

// PART 1: Create database tables
echo "Step 1: Creating database tables...\n";
$schemaFile = dirname(__FILE__) . '/schema.sql';
if (file_exists($schemaFile)) {
	$schema = file_get_contents($schemaFile);
	$statements = array_filter(array_map('trim', explode(';', $schema)));
	foreach ($statements as $statement) {
		if (!empty($statement) && stripos($statement, 'CREATE TABLE') !== false) {
			if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
				$tableName = $matches[1];
				$checkResult = $db->pquery("SHOW TABLES LIKE ?", array($tableName));
				if ($db->num_rows($checkResult) == 0) {
					$db->query($statement);
					echo "  ✓ Created table: $tableName\n";
				} else {
					echo "  ✓ Table already exists: $tableName\n";
				}
			}
		}
	}
}

// PART 2: Register module
echo "\nStep 2: Registering module...\n";
$checkTab = $db->pquery("SELECT tabid FROM vtiger_tab WHERE name = ?", array('Teams'));
if ($db->num_rows($checkTab) == 0) {
	$nextTabId = 55;
	$db->pquery(
		"INSERT INTO vtiger_tab (tabid, name, tablabel, parent, tabsequence, presence, modifiedtime, isentitytype, ownedby) 
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
		array($nextTabId, 'Teams', 'Teams', 'Management', 0, 0, time(), 1, 0)
	);
	echo "  ✓ Module registered (tabid: $nextTabId)\n";
	$tabId = $nextTabId;
} else {
	$tabId = $db->query_result($checkTab, 0, 'tabid');
	echo "  ✓ Module already registered (tabid: $tabId)\n";
}

// PART 3: Link to Management app
echo "\nStep 3: Linking to Management app...\n";
$checkApp = $db->pquery("SELECT * FROM vtiger_app2tab WHERE tabid = ? AND appname = ?", array($tabId, 'Management'));
if ($db->num_rows($checkApp) == 0) {
	$seqResult = $db->pquery("SELECT MAX(sequence) as maxseq FROM vtiger_app2tab WHERE appname = ?", array('Management'));
	$nextSeq = ($db->num_rows($seqResult) > 0) ? intval($db->query_result($seqResult, 0, 'maxseq')) + 1 : 1;
	$db->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
		array('Management', $tabId, $nextSeq, 1));
	echo "  ✓ Linked to Management app (sequence: $nextSeq)\n";
} else {
	echo "  ✓ Already linked to Management app\n";
}

// PART 4: Grant permissions
echo "\nStep 4: Granting permissions...\n";
$profiles = $db->pquery("SELECT profileid FROM vtiger_profile", array());
$operations = array(0, 1, 2, 3, 4, 7); // Save, EditView, Delete, index, DetailView, CreateView
$granted = 0;
while ($profile = $db->fetchByAssoc($profiles)) {
	$profileId = $profile['profileid'];
	
	// vtiger_profile2tab
	$check = $db->pquery("SELECT * FROM vtiger_profile2tab WHERE profileid = ? AND tabid = ?", array($profileId, $tabId));
	if ($db->num_rows($check) == 0) {
		$db->pquery("INSERT INTO vtiger_profile2tab (profileid, tabid, permissions) VALUES (?, ?, ?)",
			array($profileId, $tabId, 0));
	}
	
	// vtiger_profile2standardpermissions
	foreach ($operations as $op) {
		$check = $db->pquery("SELECT * FROM vtiger_profile2standardpermissions WHERE profileid = ? AND tabid = ? AND operation = ?",
			array($profileId, $tabId, $op));
		if ($db->num_rows($check) == 0) {
			$db->pquery("INSERT INTO vtiger_profile2standardpermissions (profileid, tabid, operation, permissions) VALUES (?, ?, ?, ?)",
				array($profileId, $tabId, $op, 0));
		}
	}
	$granted++;
}
echo "  ✓ Permissions granted for $granted profiles\n";

// PART 5: Clear cache
echo "\nStep 5: Clearing cache...\n";
$dirs = array('cache', 'storage/cache', 'templates_c');
foreach ($dirs as $dir) {
	if (is_dir($dir)) {
		$files = glob($dir . '/*');
		foreach ($files as $file) {
			if (is_file($file)) @unlink($file);
		}
	}
}
echo "  ✓ Cache cleared\n";

echo "\n=== Installation Complete! ===\n";
echo "Module tabid: $tabId\n";
echo "Menu: Management → Teams\n";
echo "Route: index.php?module=Teams&view=List&app=Management\n";
