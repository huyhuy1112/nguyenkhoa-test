<?php
/**
 * Activities menu — verify and fix (Steps 1–6).
 * Run once from Vtiger root: php activities_menu_verify_and_fix.php
 *
 * - Verifies Activities module and tabid
 * - Detects menu system (parenttabrel vs app2tab)
 * - Adds Activities to Support menu if missing (no duplicates)
 * - Clears cache
 */

chdir(__DIR__);
require_once 'config.inc.php';
require_once 'include/utils/utils.php';

global $adb;

$ACTIVITIES_TABID = null;
$report = [];

// --- STEP 1: Verify Activities in vtiger_tab ---
$tabRes = $adb->pquery(
    "SELECT tabid, name, tablabel, presence FROM vtiger_tab WHERE name = 'Activities'",
    []
);
if (!$tabRes || $adb->num_rows($tabRes) === 0) {
    echo "ERROR: Activities module not found in vtiger_tab. Register the module first.\n";
    exit(1);
}
$row = $adb->fetchByAssoc($tabRes);
$ACTIVITIES_TABID = (int)$row['tabid'];
$report['tabid'] = $ACTIVITIES_TABID;
$report['tablabel'] = $row['tablabel'];
$report['presence'] = $row['presence'];
echo "STEP 1 — Activities module: tabid={$ACTIVITIES_TABID}, tablabel={$row['tablabel']}, presence={$row['presence']}\n";

$moduleDir = __DIR__ . '/modules/Activities';
$viewsDir = $moduleDir . '/views';
$report['module_folder_exists'] = is_dir($moduleDir);
$report['views_folder_exists'] = is_dir($viewsDir);
echo "  modules/Activities exists: " . ($report['module_folder_exists'] ? 'yes' : 'no') . "\n";
echo "  modules/Activities/views exists: " . ($report['views_folder_exists'] ? 'yes' : 'no') . "\n";

// --- STEP 2: Duplicate view classes (already fixed if .legacy exist) ---
$rootDetail = file_exists($moduleDir . '/Detail.php');
$rootList = file_exists($moduleDir . '/List.php');
$legacyDetail = file_exists($moduleDir . '/Detail.legacy.php');
$legacyList = file_exists($moduleDir . '/List.legacy.php');
$report['root_Detail.php'] = $rootDetail;
$report['root_List.php'] = $rootList;
$report['Detail.legacy.php'] = $legacyDetail;
$report['List.legacy.php'] = $legacyList;
echo "STEP 2 — Duplicate views: root Detail.php=" . ($rootDetail ? 'yes' : 'no') . ", List.php=" . ($rootList ? 'yes' : 'no');
echo "; Detail.legacy=" . ($legacyDetail ? 'yes' : 'no') . ", List.legacy=" . ($legacyList ? 'yes' : 'no') . "\n";
if ($rootDetail || $rootList) {
    echo "  WARNING: Rename modules/Activities/Detail.php and List.php to .legacy.php to avoid class conflict. Keep only views/Detail.php and views/List.php.\n";
}

// --- STEP 3: Menu system detection ---
$hasApp = false;
$hasParentTab = false;
try {
    $r = $adb->pquery("SELECT 1 FROM vtiger_app LIMIT 1", []);
    $hasApp = $r && $adb->num_rows($r) > 0;
} catch (Exception $e) {}
try {
    $r = $adb->pquery("SELECT 1 FROM vtiger_parenttab LIMIT 1", []);
    $hasParentTab = $r && $adb->num_rows($r) > 0;
} catch (Exception $e) {}

$report['menu_system'] = 'unknown';
$report['in_support_parenttabrel'] = false;
$report['in_support_app2tab'] = false;

if ($hasParentTab) {
    $ptr = $adb->pquery(
        "SELECT ptr.parenttabid, ptr.sequence, p.parenttablabel FROM vtiger_parenttabrel ptr INNER JOIN vtiger_parenttab p ON p.parenttabid = ptr.parenttabid WHERE ptr.tabid = ?",
        [$ACTIVITIES_TABID]
    );
    $report['parenttabrel_entries'] = [];
    while ($ptr && ($r = $adb->fetchByAssoc($ptr))) {
        $report['parenttabrel_entries'][] = $r;
        if (strtoupper($r['parenttablabel'] ?? '') === 'SUPPORT') {
            $report['in_support_parenttabrel'] = true;
        }
    }
    $report['menu_system'] = 'vtiger_parenttab + vtiger_parenttabrel';
    echo "STEP 3 — Menu: vtiger_parenttabrel. Activities entries: " . count($report['parenttabrel_entries']) . "\n";
    if (!empty($report['parenttabrel_entries'])) {
        foreach ($report['parenttabrel_entries'] as $e) {
            echo "  parenttabid={$e['parenttabid']}, parenttablabel={$e['parenttablabel']}, sequence={$e['sequence']}\n";
        }
    }
    echo "  Activities in Support menu (parenttabrel): " . ($report['in_support_parenttabrel'] ? 'YES' : 'NO') . "\n";
}

if ($hasApp) {
    $col = 'appid';
    try {
        $adb->pquery("SELECT appid FROM vtiger_app LIMIT 1", []);
    } catch (Exception $e) {
        $col = 'name';
    }
    $app2 = $adb->pquery(
        "SELECT a2t.*, a.name AS app_name FROM vtiger_app2tab a2t JOIN vtiger_app a ON a.appid = a2t.appid WHERE a2t.tabid = ?",
        [$ACTIVITIES_TABID]
    );
    $report['app2tab_entries'] = [];
    while ($app2 && ($r = $adb->fetchByAssoc($app2))) {
        $report['app2tab_entries'][] = $r;
        if (strtoupper($r['app_name'] ?? '') === 'SUPPORT') {
            $report['in_support_app2tab'] = true;
        }
    }
    if ($report['menu_system'] === 'unknown') {
        $report['menu_system'] = 'vtiger_app + vtiger_app2tab';
    }
    echo "  vtiger_app2tab. Activities entries: " . count($report['app2tab_entries'] ?? []) . "\n";
    echo "  Activities in Support menu (app2tab): " . ($report['in_support_app2tab'] ? 'YES' : 'NO') . "\n";
}

// --- STEP 4: Add to Support menu if missing ---
$added = false;

$supportId = null;
if ($hasParentTab && !$report['in_support_parenttabrel']) {
    $cols = $adb->pquery("SHOW COLUMNS FROM vtiger_parenttab", []);
    $labelCol = 'parenttablabel';
    if ($cols && $adb->num_rows($cols) > 0) {
        while ($c = $adb->fetchByAssoc($cols)) {
            if (stripos($c['Field'], 'label') !== false) {
                $labelCol = $c['Field'];
                break;
            }
        }
    }
    $sup = $adb->pquery("SELECT parenttabid FROM vtiger_parenttab WHERE UPPER(`{$labelCol}`) = 'SUPPORT'", []);
    if ($sup && $adb->num_rows($sup) > 0) {
        $supportId = (int)$adb->query_result($sup, 0, 'parenttabid');
        $seqRes = $adb->pquery("SELECT COALESCE(MAX(sequence),0)+1 AS seq FROM vtiger_parenttabrel WHERE parenttabid = ?", [$supportId]);
        $seq = $seqRes ? (int)$adb->query_result($seqRes, 0, 'seq') : 1;
        $adb->pquery("INSERT INTO vtiger_parenttabrel (parenttabid, tabid, sequence) VALUES (?, ?, ?)", [$supportId, $ACTIVITIES_TABID, $seq]);
        $added = true;
        echo "STEP 4 — Added Activities to Support menu (parenttabid={$supportId}, sequence={$seq}).\n";
    } else {
        echo "STEP 4 — Support parent tab not found. Cannot add.\n";
    }
}

if ($hasApp && !$report['in_support_app2tab'] && !$added) {
    $appRes = $adb->pquery("SELECT appid FROM vtiger_app WHERE UPPER(name) = 'SUPPORT' LIMIT 1", []);
    if ($appRes && $adb->num_rows($appRes) > 0) {
        $appId = (int)$adb->query_result($appRes, 0, 'appid');
        $seqRes = $adb->pquery("SELECT COALESCE(MAX(sequence),0)+1 AS seq FROM vtiger_app2tab WHERE appid = ?", [$appId]);
        $seq = $seqRes ? (int)$adb->query_result($seqRes, 0, 'seq') : 1;
        $adb->pquery("INSERT INTO vtiger_app2tab (appid, tabid, sequence) VALUES (?, ?, ?)", [$appId, $ACTIVITIES_TABID, $seq]);
        $added = true;
        echo "STEP 4 — Added Activities to Support menu (appid={$appId}, sequence={$seq}).\n";
    }
}

if (!$added && ($report['in_support_parenttabrel'] || $report['in_support_app2tab'])) {
    echo "STEP 4 — Activities already in Support menu. No duplicate inserted.\n";
}

// --- STEP 5: Menu order (report only) ---
echo "STEP 5 — Support menu order (from DB):\n";
if ($hasParentTab) {
    $supId = $supportId ?? null;
    if ($supId === null) {
        $s = $adb->pquery("SELECT parenttabid FROM vtiger_parenttab WHERE UPPER(parenttablabel)= 'SUPPORT' OR UPPER(parenttab_label)='SUPPORT' LIMIT 1", []);
        if ($s && $adb->num_rows($s) > 0) {
            $supId = (int)$adb->query_result($s, 0, 'parenttabid');
        }
    }
    if ($supId !== null) {
        $order = $adb->pquery(
            "SELECT ptr.sequence, t.name, t.tablabel FROM vtiger_parenttabrel ptr JOIN vtiger_tab t ON t.tabid = ptr.tabid WHERE ptr.parenttabid = ? ORDER BY ptr.sequence",
            [$supId]
        );
        $pos = 0;
        while ($order && ($o = $adb->fetchByAssoc($order))) {
            $pos++;
            echo "  {$pos}. {$o['tablabel']} ({$o['name']}) sequence={$o['sequence']}\n";
        }
    }
}

// --- STEP 6: Clear cache ---
$cacheFiles = ['cache/tabdata.php', 'user_privileges/menu_0.php', 'user_privileges/menu_1.php'];
foreach ($cacheFiles as $f) {
    if (file_exists($f)) {
        @unlink($f);
        echo "STEP 6 — Cleared: $f\n";
    }
}
echo "STEP 6 — Cache cleared. Logout and login to see Activities in Support sidebar.\n";

// --- Final report ---
echo "\n--- REPORT ---\n";
echo "Activities tabid: " . $report['tabid'] . "\n";
echo "Menu system: " . $report['menu_system'] . "\n";
echo "In Support menu: " . ($report['in_support_parenttabrel'] || $report['in_support_app2tab'] || $added ? 'YES' : 'NO') . "\n";
echo "Files: modules/Activities (Detail.legacy, List.legacy, views/Detail, views/List, views/Edit, actions/Save, Activities.php, models/Record)\n";
echo "Templates: layouts/v7/modules/Activities (List.tpl, Detail.tpl, DetailView.tpl, ListView.tpl)\n";
echo "Done.\n";
