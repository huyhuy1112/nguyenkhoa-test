<?php
/**
 * Create placeholder modules: Evaluate and Plans
 * These are lightweight modules for Marketing menu only
 * No database tables, no business logic
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/VtlibUtils.php';

$adb = PearDatabase::getInstance();

// Get MARKETING parent tab ID
$marketingResult = $adb->pquery("SELECT parenttabid FROM vtiger_parenttab WHERE parenttab_label = ?", array('MARKETING'));
if ($adb->num_rows($marketingResult) == 0) {
    die("ERROR: MARKETING parent tab not found!\n");
}
$marketingParentTabId = $adb->query_result($marketingResult, 0, 'parenttabid');
echo "MARKETING parent tab ID: $marketingParentTabId\n";

// Function to create a placeholder module
function createPlaceholderModule($moduleName, $moduleLabel, $iconClass, $parentTabId) {
    global $adb;
    
    echo "\n=== Creating module: $moduleName ===\n";
    
    // Check if module already exists
    $checkResult = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = ?", array($moduleName));
    if ($adb->num_rows($checkResult) > 0) {
        $tabid = $adb->query_result($checkResult, 0, 'tabid');
        echo "Module $moduleName already exists with tabid: $tabid\n";
        echo "Skipping creation. Use uninstall script to remove first.\n";
        return $tabid;
    }
    
    // Get next available tabid
    $maxTabResult = $adb->pquery("SELECT MAX(tabid) as max_tabid FROM vtiger_tab", array());
    $maxTabId = $adb->query_result($maxTabResult, 0, 'max_tabid');
    $newTabId = $maxTabId + 1;
    
    // Get next sequence
    $maxSeqResult = $adb->pquery("SELECT MAX(tabsequence) as max_seq FROM vtiger_tab WHERE tabsequence > 0", array());
    $maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
    $newSeq = $maxSeq + 1;
    
    // Insert into vtiger_tab
    // isentitytype = 0 (no database tables)
    // parent = 'MARKETING'
    $adb->pquery("INSERT INTO vtiger_tab (tabid, name, presence, tabsequence, tablabel, modifiedby, modifiedtime, customized, ownedby, version, parent, isentitytype) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array(
            $newTabId,
            $moduleName,
            0, // presence: 0 = visible
            $newSeq,
            $moduleLabel,
            null,
            null,
            1, // customized: 1 = custom module
            0, // ownedby
            '1.0', // version
            'MARKETING', // parent
            0  // isentitytype: 0 = no entity (no database tables)
        )
    );
    
    echo "✓ Inserted into vtiger_tab (tabid: $newTabId)\n";
    
    // Link to MARKETING parent tab
    $maxRelSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_parenttabrel WHERE parenttabid = ?", array($parentTabId));
    $maxRelSeq = $adb->query_result($maxRelSeqResult, 0, 'max_seq');
    $newRelSeq = ($maxRelSeq ? $maxRelSeq + 1 : 1);
    
    $adb->pquery("INSERT INTO vtiger_parenttabrel (parenttabid, tabid, sequence) VALUES (?, ?, ?)",
        array($parentTabId, $newTabId, $newRelSeq));
    
    echo "✓ Linked to MARKETING parent tab (sequence: $newRelSeq)\n";
    
    // Create module directory and files
    $moduleDir = "modules/$moduleName";
    if (!is_dir($moduleDir)) {
        mkdir($moduleDir, 0755, true);
        echo "✓ Created module directory: $moduleDir\n";
    }
    
    // Create minimal module PHP file
    $moduleFile = "$moduleDir/$moduleName.php";
    $moduleContent = <<<PHP
<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Placeholder module: $moduleLabel
 * This is a lightweight module for menu structure only.
 * No database tables, no business logic.
 */
class $moduleName extends CRMEntity {
    var \$db, \$log;
    var \$column_fields = Array();
    
    /** Indicator if this is a custom module */
    var \$IsCustomModule = true;
    
    /**
     * This module is NOT an entity type (no database tables)
     */
    var \$isentitytype = false;
    
    function __construct() {
        global \$log;
        \$this->log = \$log;
        \$this->db = PearDatabase::getInstance();
    }
    
    /**
     * Invoked when special actions are performed on the module.
     */
    function vtlib_handler(\$modulename, \$event_type) {
        // Placeholder module - no special handling needed
    }
}
?>
PHP;
    
    file_put_contents($moduleFile, $moduleContent);
    echo "✓ Created module file: $moduleFile\n";
    
    // Create manifest.xml
    $manifestFile = "$moduleDir/manifest.xml";
    $manifestContent = <<<XML
<?xml version='1.0'?>
<module>
    <name>$moduleName</name>
    <label>$moduleLabel</label>
    <parent>MARKETING</parent>
    <type>extension</type>
    <version>1.0</version>
    <dependencies>
        <vtiger_version>8.0.0</vtiger_version>
    </dependencies>
</module>
XML;
    
    file_put_contents($manifestFile, $manifestContent);
    echo "✓ Created manifest: $manifestFile\n";
    
    // Create models directory with minimal Module.php
    $modelsDir = "$moduleDir/models";
    if (!is_dir($modelsDir)) {
        mkdir($modelsDir, 0755, true);
    }
    
    $modelFile = "$modelsDir/Module.php";
    $modelContent = <<<PHP
<?php
class {$moduleName}_Module_Model extends Vtiger_Module_Model {
    
    /**
     * Get module icon class
     */
    public function getModuleIcon() {
        return 'fa {$iconClass}';
    }
}
?>
PHP;
    
    file_put_contents($modelFile, $modelContent);
    echo "✓ Created model: $modelFile\n";
    
    echo "✓ Module $moduleName created successfully!\n";
    
    return $newTabId;
}

// Create Evaluate module
$evaluateTabId = createPlaceholderModule('Evaluate', 'Evaluate', 'fa-bar-chart', $marketingParentTabId);

// Create Plans module
$plansTabId = createPlaceholderModule('Plans', 'Plans', 'fa-calendar', $marketingParentTabId);

echo "\n=== Summary ===\n";
echo "Evaluate module created with tabid: $evaluateTabId\n";
echo "Plans module created with tabid: $plansTabId\n";
echo "\nNext steps:\n";
echo "1. Clear cache: rm -rf cache/* templates_c/*\n";
echo "2. Reload Vtiger UI\n";
echo "3. Go to Settings > Menu Editor\n";
echo "4. Drag Evaluate and Plans into MARKETING category\n";
echo "\nDone!\n";


