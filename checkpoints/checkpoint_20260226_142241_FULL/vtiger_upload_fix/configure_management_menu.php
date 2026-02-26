<?php
/**
 * Configure Management App Menu
 * 
 * Assigns modules to "Management" app menu:
 * - Calendar (display: Schedule)
 * - Reports (display: Report)
 * - Documents (display: Document)
 * - Users (display: Team)
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';

$adb = PearDatabase::getInstance();

echo "==========================================\n";
echo "Configure Management App Menu\n";
echo "==========================================\n\n";

// Step 1: Get module tabids
echo "Step 1: Fetching module tabids...\n";

$modules = array(
    'Calendar' => array('label' => 'Schedule', 'display' => 'Schedule'),
    'Reports' => array('label' => 'Report', 'display' => 'Report'),
    'Documents' => array('label' => 'Document', 'display' => 'Document'),
    'Users' => array('label' => 'Team', 'display' => 'Team')
);

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
        echo "  ✗ $moduleName: NOT FOUND in vtiger_tab\n";
        die("ERROR: Module $moduleName not found!\n");
    }
}

echo "\n";

// Step 2: Check if PROJECTS app exists
echo "Step 2: Checking PROJECTS app (displayed as 'Management')...\n";

$appName = 'PROJECTS'; // CRITICAL: Use 'PROJECTS' appname (already renamed to 'Management' in UI)
$checkApp = $adb->pquery("SELECT DISTINCT appname FROM vtiger_app2tab WHERE appname = ? LIMIT 1", array($appName));

if ($adb->num_rows($checkApp) == 0) {
    echo "  ⚠️ PROJECTS app not found. Will be created when first module is added.\n";
} else {
    echo "  ✓ PROJECTS app exists (displayed as 'Management' in sidebar)\n";
}

echo "\n";

// Step 3: Get current mappings for these modules
echo "Step 3: Checking current app mappings...\n";

$tabids = array_column($moduleTabIds, 'tabid');
$placeholders = str_repeat('?,', count($tabids) - 1) . '?';
$currentMappings = $adb->pquery(
    "SELECT tabid, appname, sequence FROM vtiger_app2tab WHERE tabid IN ($placeholders) ORDER BY tabid, appname",
    $tabids
);

$existingMappings = array();
while ($row = $adb->fetchByAssoc($currentMappings)) {
    $existingMappings[$row['tabid']][] = $row['appname'];
    $moduleName = array_search($row['tabid'], array_column($moduleTabIds, 'tabid'));
    echo "  - tabid {$row['tabid']} ($moduleName): currently in '{$row['appname']}' (sequence: {$row['sequence']})\n";
}

echo "\n";

// Step 4: Remove modules from previous apps (if needed)
echo "Step 4: Removing modules from previous apps...\n";

foreach ($moduleTabIds as $moduleName => $info) {
    $tabid = $info['tabid'];
    if (isset($existingMappings[$tabid])) {
        foreach ($existingMappings[$tabid] as $oldApp) {
            if ($oldApp !== $appName) {
                $adb->pquery("DELETE FROM vtiger_app2tab WHERE tabid = ? AND appname = ?", array($tabid, $oldApp));
                echo "  ✓ Removed $moduleName from '$oldApp'\n";
            }
        }
    }
}

echo "\n";

// Step 5: Get max sequence for Management app
echo "Step 5: Calculating sequence numbers...\n";

$maxSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array($appName));
$maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
$nextSeq = ($maxSeq ? $maxSeq + 1 : 1);

echo "  Current max sequence: " . ($maxSeq ? $maxSeq : 0) . "\n";
echo "  Starting sequence: $nextSeq\n\n";

// Step 6: Insert/Update mappings to Management app
echo "Step 6: Mapping modules to Management app...\n";

$sequence = $nextSeq;
foreach ($moduleTabIds as $moduleName => $info) {
    $tabid = $info['tabid'];
    
    // Check if mapping already exists
    $checkResult = $adb->pquery(
        "SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?",
        array($appName, $tabid)
    );
    
    if ($adb->num_rows($checkResult) == 0) {
        // Insert new mapping
        $adb->pquery(
            "INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array($appName, $tabid, $sequence, 1)
        );
        echo "  ✓ Added $moduleName -> PROJECTS (sequence: $sequence)\n";
    } else {
        // Update existing mapping
        $adb->pquery(
            "UPDATE vtiger_app2tab SET sequence = ?, visible = ? WHERE appname = ? AND tabid = ?",
            array($sequence, 1, $appName, $tabid)
        );
        echo "  ✓ Updated $moduleName -> PROJECTS (sequence: $sequence)\n";
    }
    
    $sequence++;
}

echo "\n";

// Step 7: Update display labels (if supported via vtiger_tab.tablabel)
echo "Step 7: Updating display labels...\n";

foreach ($moduleTabIds as $moduleName => $info) {
    $tabid = $info['tabid'];
    $newLabel = $info['display'];
    $currentLabel = $info['tablabel'];
    
    if ($currentLabel !== $newLabel) {
        $adb->pquery("UPDATE vtiger_tab SET tablabel = ? WHERE tabid = ?", array($newLabel, $tabid));
        echo "  ✓ Updated $moduleName label: '$currentLabel' -> '$newLabel'\n";
    } else {
        echo "  - $moduleName label already correct: '$currentLabel'\n";
    }
}

echo "\n";

// Step 8: Verify final structure
echo "Step 8: Verifying final menu structure...\n";

$verifyResult = $adb->pquery(
    "SELECT t.name, t.tablabel, a.appname, a.sequence, a.visible 
     FROM vtiger_app2tab a 
     INNER JOIN vtiger_tab t ON t.tabid = a.tabid 
     WHERE a.appname = ? AND a.tabid IN ($placeholders)
     ORDER BY a.sequence",
    array_merge(array($appName), $tabids)
);

echo "\nManagement Menu Structure:\n";
echo str_repeat("-", 60) . "\n";
printf("%-15s %-15s %-10s %-10s\n", "Module", "Display Label", "Sequence", "Visible");
echo str_repeat("-", 60) . "\n";

$found = 0;
while ($row = $adb->fetchByAssoc($verifyResult)) {
    printf("%-15s %-15s %-10s %-10s\n", 
        $row['name'], 
        $row['tablabel'], 
        $row['sequence'], 
        $row['visible'] ? 'Yes' : 'No'
    );
    $found++;
}

echo str_repeat("-", 60) . "\n";

if ($found == count($moduleTabIds)) {
    echo "\n✓ All " . count($moduleTabIds) . " modules successfully mapped to PROJECTS app (displayed as 'Management')\n";
} else {
    echo "\n✗ Warning: Expected " . count($moduleTabIds) . " modules, found $found\n";
}

echo "\n";

// Step 9: Clear cache instructions
echo "Step 9: Cache clearing instructions...\n";
echo "  Run the following commands to clear cache:\n";
echo "    rm -rf cache/*\n";
echo "    rm -rf storage/cache/*\n";
echo "    rm -rf templates_c/*\n";
echo "\n  Or via Vtiger Admin:\n";
echo "    Settings → Configuration → Clear Cache\n";
echo "\n";

echo "==========================================\n";
echo "✓ Configuration Complete!\n";
echo "==========================================\n\n";
echo "Next Steps:\n";
echo "1. Clear cache (see above)\n";
echo "2. Logout and login to Vtiger CRM\n";
echo "3. Check sidebar menu - 'Management' (PROJECTS app) should show:\n";
echo "   - Schedule (Calendar)\n";
echo "   - Report (Reports)\n";
echo "   - Document (Documents)\n";
echo "   - Team (Users)\n";
echo "\n";

