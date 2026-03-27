<?php
/**
 * Phase A: Ensure GoodsIssue module access/visibility metadata (DB-only).
 *
 * Safety:
 * - DOES NOT touch user_privileges/* or sharing_privileges/*
 * - DOES NOT clear privilege caches
 * - Optional safe cache clear only: cache/menu/*, cache/templates_c/*
 *
 * Usage:
 *   php modules/GoodsIssue/scripts/EnsureAccess.php --mode=report
 *   php modules/GoodsIssue/scripts/EnsureAccess.php --mode=apply
 *   php modules/GoodsIssue/scripts/EnsureAccess.php --mode=apply --clear-safe-cache
 */

chdir(dirname(__DIR__, 3)); // vtiger root

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';

require_once 'include/utils/utils.php';

global $adb;
$adb = PearDatabase::getInstance();

$moduleName = 'GoodsIssue';
$appName = 'INVENTORY';
$tabLabel = 'Outbound';
$mode = 'report';
$clearSafeCache = false;

foreach ($argv as $arg) {
	if (strpos($arg, '--module=') === 0) {
		$moduleName = substr($arg, strlen('--module='));
	}
	if (strpos($arg, '--app=') === 0) {
		$appName = substr($arg, strlen('--app='));
	}
	if (strpos($arg, '--tablabel=') === 0) {
		$tabLabel = substr($arg, strlen('--tablabel='));
	}
	if (strpos($arg, '--mode=') === 0) {
		$mode = substr($arg, strlen('--mode='));
	}
	if ($arg === '--clear-safe-cache') {
		$clearSafeCache = true;
	}
}

function pgetTabId($adb, $moduleName) {
	$res = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = ? LIMIT 1", array($moduleName));
	return (int) $adb->query_result($res, 0, 'tabid');
}

function pgetNextTabId($adb) {
	$res = $adb->pquery("SELECT MAX(tabid) AS max_tabid FROM vtiger_tab", array());
	$maxTabId = (int) $adb->query_result($res, 0, 'max_tabid');
	return $maxTabId + 1;
}

function pgetNextTabSequence($adb) {
	$res = $adb->pquery("SELECT MAX(tabsequence) AS max_seq FROM vtiger_tab WHERE tabsequence > 0", array());
	$maxSeq = (int) $adb->query_result($res, 0, 'max_seq');
	return ($maxSeq ? $maxSeq + 1 : 1);
}

function pgetNextApp2TabSequence($adb, $appName) {
	$res = $adb->pquery("SELECT MAX(sequence) AS max_seq FROM vtiger_app2tab WHERE appname = ?", array($appName));
	$maxSeq = (int) $adb->query_result($res, 0, 'max_seq');
	return ($maxSeq ? $maxSeq + 1 : 1);
}

function ensureTabRow($adb, $moduleName, $tabLabel, $parentApp, &$tabInsertedOrUpdated) {
	$tabIdRes = $adb->pquery("SELECT tabid, presence, tabsequence FROM vtiger_tab WHERE name = ? LIMIT 1", array($moduleName));
	if ($adb->num_rows($tabIdRes) <= 0) {
		$newTabId = pgetNextTabId($adb, $moduleName);
		$newSeq = pgetNextTabSequence($adb);
		$adb->pquery(
			"INSERT INTO vtiger_tab (tabid, name, presence, tabsequence, tablabel, modifiedby, modifiedtime, customized, ownedby, version, parent, isentitytype)
			 VALUES (?, ?, 0, ?, ?, 0, NOW(), 1, 0, 1.0, ?, 0)",
			array($newTabId, $moduleName, $newSeq, $tabLabel, $parentApp)
		);
		$tabInsertedOrUpdated[] = "vtiger_tab INSERT tabid={$newTabId}, name={$moduleName}, tablabel={$tabLabel}";
		return (int) $newTabId;
	}

	$tabId = (int) $adb->query_result($tabIdRes, 0, 'tabid');
	$presence = (int) $adb->query_result($tabIdRes, 0, 'presence');
	$adb->pquery("UPDATE vtiger_tab SET presence = 0, tablabel = ? WHERE name = ?", array($tabLabel, $moduleName));
	$tabInsertedOrUpdated[] = "vtiger_tab UPDATE tabid={$tabId}, presence->0, tablabel->{$tabLabel}";
	return $tabId;
}

function ensureApp2TabRow($adb, $appName, $tabId, &$changes) {
	$check = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ? LIMIT 1", array($appName, $tabId));
	if ($adb->num_rows($check) > 0) {
		$changes[] = "vtiger_app2tab already exists for app={$appName}, tabid={$tabId}";
		return;
	}

	$newSeq = pgetNextApp2TabSequence($adb, $appName);
	$adb->pquery(
		"INSERT INTO vtiger_app2tab (appname, tabid, sequence) VALUES (?, ?, ?)",
		array($appName, $tabId, $newSeq)
	);
	$changes[] = "vtiger_app2tab INSERT app={$appName}, tabid={$tabId}, sequence={$newSeq}";
}

function ensureProfilePermissions($adb, $tabId, &$changes) {
	$profiles = $adb->pquery("SELECT profileid FROM vtiger_profile", array());
	$profileCount = 0;

	while ($profile = $adb->fetchByAssoc($profiles)) {
		$profileId = (int) $profile['profileid'];
		$profileCount++;

		$check = $adb->pquery(
			"SELECT 1 FROM vtiger_profile2tab WHERE profileid = ? AND tabid = ? LIMIT 1",
			array($profileId, $tabId)
		);
		if ($adb->num_rows($check) > 0) {
			$adb->pquery(
				"UPDATE vtiger_profile2tab SET permissions = 0 WHERE profileid = ? AND tabid = ?",
				array($profileId, $tabId)
			);
			$changes[] = "vtiger_profile2tab UPDATE profileid={$profileId}, tabid={$tabId}, permissions=0";
		} else {
			$adb->pquery(
				"INSERT INTO vtiger_profile2tab (profileid, tabid, permissions) VALUES (?, ?, 0)",
				array($profileId, $tabId)
			);
			$changes[] = "vtiger_profile2tab INSERT profileid={$profileId}, tabid={$tabId}, permissions=0";
		}

		foreach (array(0, 1, 2, 3) as $op) {
			$checkStd = $adb->pquery(
				"SELECT 1 FROM vtiger_profile2standardpermissions WHERE profileid = ? AND tabid = ? AND operation = ? LIMIT 1",
				array($profileId, $tabId, $op)
			);
			if ($adb->num_rows($checkStd) > 0) {
				$adb->pquery(
					"UPDATE vtiger_profile2standardpermissions SET permissions = 0 WHERE profileid = ? AND tabid = ? AND operation = ?",
					array($profileId, $tabId, $op)
				);
			} else {
				$adb->pquery(
					"INSERT INTO vtiger_profile2standardpermissions (profileid, tabid, operation, permissions) VALUES (?, ?, ?, 0)",
					array($profileId, $tabId, $op)
				);
			}
		}
	}

	$changes[] = "Profile permissions processed for {$profileCount} profiles (profile2standardpermissions ops 0..3 permissions=0).";
}

$tabInsertedOrUpdated = array();
$changes = array();

echo "=== GoodsIssue Phase A EnsureAccess ===\n";
echo "module={$moduleName}, app={$appName}, tablabel={$tabLabel}, mode={$mode}\n";

// report existing states
$tabRes = $adb->pquery(
	"SELECT tabid, presence, tablabel, tabsequence FROM vtiger_tab WHERE name = ? LIMIT 1",
	array($moduleName)
);
$tabExists = ($adb->num_rows($tabRes) > 0);
$tabId = $tabExists ? (int) $adb->query_result($tabRes, 0, 'tabid') : 0;

echo "[DB] vtiger_tab: " . ($tabExists ? "FOUND tabid={$tabId}" : "MISSING") . "\n";

if ($tabExists) {
	$checkApp2Tab = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ? LIMIT 1", array($appName, $tabId));
	echo "[DB] vtiger_app2tab for app={$appName}: " . ($adb->num_rows($checkApp2Tab) > 0 ? "FOUND" : "MISSING") . "\n";

	$checkProfile2Tab = $adb->pquery("SELECT COUNT(*) AS cnt FROM vtiger_profile2tab WHERE tabid = ?", array($tabId));
	$cntProfile2Tab = (int) $adb->query_result($checkProfile2Tab, 0, 'cnt');
	echo "[DB] vtiger_profile2tab rows for tabid={$tabId}: {$cntProfile2Tab}\n";
}

if (strtolower($mode) === 'apply') {
	if (!$tabExists) {
		$tabId = ensureTabRow($adb, $moduleName, $tabLabel, $appName, $tabInsertedOrUpdated);
	} else {
		// Even if exists, ensure active/presence and label.
		$tabId = (int) $adb->query_result($tabRes, 0, 'tabid');
		$tabInsertedOrUpdated[] = "vtiger_tab exists (tabid={$tabId}); ensured in ensureTabRow flow next.";
		$adb->pquery("UPDATE vtiger_tab SET presence = 0, tablabel = ? WHERE name = ?", array($tabLabel, $moduleName));
	}

	// app binding
	ensureApp2TabRow($adb, $appName, $tabId, $changes);

	// permissions for all profiles
	ensureProfilePermissions($adb, $tabId, $changes);

	// safe cache optional
	if ($clearSafeCache) {
		// cache/menu and cache/templates_c only
		global $root_directory;
		$root_directory = rtrim($root_directory, "/\\") . DIRECTORY_SEPARATOR;
		$paths = array(
			$root_directory . 'cache/menu/*',
			$root_directory . 'cache/templates_c/*'
		);
		foreach ($paths as $p) {
			$files = glob($p);
			if (!empty($files)) {
				foreach ($files as $f) {
					@unlink($f);
				}
			}
		}
	}

	echo "=== APPLY DONE ===\n";
	foreach ($tabInsertedOrUpdated as $c) {
		echo "[CHANGE] {$c}\n";
	}
	foreach ($changes as $c) {
		echo "[CHANGE] {$c}\n";
	}
} else {
	echo "=== REPORT DONE ===\n";
	echo "Run with --mode=apply to insert/fix missing rows.\n";
}

?>

