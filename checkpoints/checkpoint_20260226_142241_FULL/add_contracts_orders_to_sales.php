<?php
/**
 * Add Contracts and SalesOrder (Orders) modules to SALES menu
 * Runtime menubar binding only - no template hacks
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';

$adb = PearDatabase::getInstance();

echo "=== Adding Contracts and Orders to SALES Menu ===\n\n";

// Step 1: Identify tabids
echo "Step 1: Identifying module tabids...\n";
$modulesResult = $adb->pquery("SELECT tabid, name, presence, isentitytype, customized FROM vtiger_tab WHERE name IN (?, ?)",
    array('ServiceContracts', 'SalesOrder'));

$moduleInfo = array();
while ($row = $adb->fetchByAssoc($modulesResult)) {
    $moduleInfo[$row['name']] = $row;
    echo "  {$row['name']}: tabid={$row['tabid']}, presence={$row['presence']}, isentitytype={$row['isentitytype']}, customized={$row['customized']}\n";
}

// Check if modules exist
if (empty($moduleInfo['ServiceContracts']) && empty($moduleInfo['SalesOrder'])) {
    die("ERROR: Neither ServiceContracts nor SalesOrder found!\n");
}

// Note: Vtiger uses 'ServiceContracts' for Contracts module
$contractsTabId = isset($moduleInfo['ServiceContracts']) ? $moduleInfo['ServiceContracts']['tabid'] : null;
$salesOrderTabId = isset($moduleInfo['SalesOrder']) ? $moduleInfo['SalesOrder']['tabid'] : null;

if (!$contractsTabId && !$salesOrderTabId) {
    die("ERROR: No modules found to add!\n");
}

// Step 2: Get SALES parent tab ID
echo "\nStep 2: Getting SALES parent tab ID...\n";
$salesParentResult = $adb->pquery("SELECT parenttabid FROM vtiger_parenttab WHERE parenttab_label = ?", array('SALES'));
if ($adb->num_rows($salesParentResult) == 0) {
    die("ERROR: SALES parent tab not found!\n");
}
$salesParentTabId = $adb->query_result($salesParentResult, 0, 'parenttabid');
echo "  SALES parent tab ID: $salesParentTabId\n";

// Step 3: Bind modules to SALES app
echo "\nStep 3: Binding modules to SALES app (vtiger_app2tab)...\n";

// Get max sequence for SALES
$maxSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array('SALES'));
$maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
$nextSeq = ($maxSeq ? $maxSeq + 1 : 1);

if ($contractsTabId) {
    // Check if already exists
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", 
        array('SALES', $contractsTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array('SALES', $contractsTabId, $nextSeq, 1));
        echo "  ✓ ServiceContracts -> SALES (sequence: $nextSeq)\n";
        $nextSeq++;
    } else {
        echo "  - ServiceContracts -> SALES mapping already exists\n";
    }
}

if ($salesOrderTabId) {
    // Check if already exists
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", 
        array('SALES', $salesOrderTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array('SALES', $salesOrderTabId, $nextSeq, 1));
        echo "  ✓ SalesOrder -> SALES (sequence: $nextSeq)\n";
    } else {
        echo "  - SalesOrder -> SALES mapping already exists\n";
    }
}

// Step 4: Ensure modules are visible
echo "\nStep 4: Ensuring modules are visible...\n";

if ($contractsTabId) {
    $row = $moduleInfo['ServiceContracts'];
    $needsUpdate = false;
    $updateFields = array();
    
    if ($row['presence'] != 0) {
        $needsUpdate = true;
        $updateFields[] = "presence = 0";
    }
    
    if ($needsUpdate) {
        $updateSql = "UPDATE vtiger_tab SET " . implode(", ", $updateFields) . " WHERE tabid = ?";
        $adb->pquery($updateSql, array($contractsTabId));
        echo "  ✓ Updated ServiceContracts flags\n";
    } else {
        echo "  ✓ ServiceContracts flags are correct\n";
    }
}

if ($salesOrderTabId) {
    $row = $moduleInfo['SalesOrder'];
    $needsUpdate = false;
    $updateFields = array();
    
    if ($row['presence'] != 0) {
        $needsUpdate = true;
        $updateFields[] = "presence = 0";
    }
    
    if ($needsUpdate) {
        $updateSql = "UPDATE vtiger_tab SET " . implode(", ", $updateFields) . " WHERE tabid = ?";
        $adb->pquery($updateSql, array($salesOrderTabId));
        echo "  ✓ Updated SalesOrder flags\n";
    } else {
        echo "  ✓ SalesOrder flags are correct\n";
    }
}

// Step 5: Parent tab relation (for Menu Editor consistency)
echo "\nStep 5: Adding parent tab relations...\n";

if ($contractsTabId) {
    // Get max sequence for SALES parent tab
    $maxRelSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_parenttabrel WHERE parenttabid = ?", 
        array($salesParentTabId));
    $maxRelSeq = $adb->query_result($maxRelSeqResult, 0, 'max_seq');
    $nextRelSeq = ($maxRelSeq ? $maxRelSeq + 1 : 1);
    
    // Check if already exists
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_parenttabrel WHERE parenttabid = ? AND tabid = ?", 
        array($salesParentTabId, $contractsTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_parenttabrel (parenttabid, tabid, sequence) VALUES (?, ?, ?)",
            array($salesParentTabId, $contractsTabId, $nextRelSeq));
        echo "  ✓ ServiceContracts -> SALES parent tab (sequence: $nextRelSeq)\n";
        $nextRelSeq++;
    } else {
        echo "  - ServiceContracts -> SALES parent tab relation already exists\n";
    }
}

if ($salesOrderTabId) {
    // Get max sequence for SALES parent tab
    $maxRelSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_parenttabrel WHERE parenttabid = ?", 
        array($salesParentTabId));
    $maxRelSeq = $adb->query_result($maxRelSeqResult, 0, 'max_seq');
    $nextRelSeq = ($maxRelSeq ? $maxRelSeq + 1 : 1);
    
    // Check if already exists
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_parenttabrel WHERE parenttabid = ? AND tabid = ?", 
        array($salesParentTabId, $salesOrderTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_parenttabrel (parenttabid, tabid, sequence) VALUES (?, ?, ?)",
            array($salesParentTabId, $salesOrderTabId, $nextRelSeq));
        echo "  ✓ SalesOrder -> SALES parent tab (sequence: $nextRelSeq)\n";
    } else {
        echo "  - SalesOrder -> SALES parent tab relation already exists\n";
    }
}

// Verification
echo "\n=== Verification ===\n";

// Check app2tab mappings
echo "\nApp-to-Tab Mappings:\n";
$appResult = $adb->pquery("SELECT appname, tabid, sequence, visible FROM vtiger_app2tab WHERE appname = 'SALES' AND tabid IN (?, ?) ORDER BY sequence",
    array($contractsTabId ?: 0, $salesOrderTabId ?: 0));

$found = 0;
while ($row = $adb->fetchByAssoc($appResult)) {
    $moduleName = ($row['tabid'] == $contractsTabId) ? 'ServiceContracts' : 'SalesOrder';
    echo "  ✓ $moduleName -> {$row['appname']} (sequence: {$row['sequence']}, visible: {$row['visible']})\n";
    $found++;
}

// Check parenttabrel
echo "\nParent Tab Relations:\n";
$parentResult = $adb->pquery("SELECT p.parenttab_label, ptr.tabid, ptr.sequence 
    FROM vtiger_parenttabrel ptr 
    INNER JOIN vtiger_parenttab p ON p.parenttabid = ptr.parenttabid 
    WHERE ptr.parenttabid = ? AND ptr.tabid IN (?, ?) ORDER BY ptr.sequence",
    array($salesParentTabId, $contractsTabId ?: 0, $salesOrderTabId ?: 0));

$found = 0;
while ($row = $adb->fetchByAssoc($parentResult)) {
    $moduleName = ($row['tabid'] == $contractsTabId) ? 'ServiceContracts' : 'SalesOrder';
    echo "  ✓ $moduleName -> {$row['parenttab_label']} (sequence: {$row['sequence']})\n";
    $found++;
}

echo "\n=== Fix Complete ===\n";
echo "\nNext steps:\n";
echo "1. Clear cache: rm -rf cache/* templates_c/* cache/menu/*\n";
echo "2. Logout and login again\n";
echo "3. Check SALES menu - Contracts and Orders should appear\n";


