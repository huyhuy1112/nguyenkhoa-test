<?php
/**
 * Add Activities, Schedule, and Rules modules to SUPPORT menu
 * Menu-level binding only - no template edits
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/VtlibUtils.php';

$adb = PearDatabase::getInstance();

echo "=== Adding Activities, Schedule, and Rules to SUPPORT Menu ===\n\n";

// Step 1: Check existing modules
echo "Step 1: Checking existing modules...\n";
$modulesResult = $adb->pquery("SELECT tabid, name, presence, isentitytype, customized FROM vtiger_tab WHERE name IN (?, ?, ?, ?)",
    array('Calendar', 'Schedule', 'Rules', 'Activities'));

$moduleInfo = array();
while ($row = $adb->fetchByAssoc($modulesResult)) {
    $moduleInfo[$row['name']] = $row;
    echo "  {$row['name']}: tabid={$row['tabid']}, presence={$row['presence']}, isentitytype={$row['isentitytype']}\n";
}

// Activities maps to Calendar
$activitiesTabId = isset($moduleInfo['Calendar']) ? $moduleInfo['Calendar']['tabid'] : null;
$scheduleTabId = isset($moduleInfo['Schedule']) ? $moduleInfo['Schedule']['tabid'] : null;
$rulesTabId = isset($moduleInfo['Rules']) ? $moduleInfo['Rules']['tabid'] : null;

if (!$activitiesTabId) {
    die("ERROR: Calendar module (Activities) not found!\n");
}

echo "\n  Activities (Calendar): tabid=$activitiesTabId\n";
echo "  Schedule: " . ($scheduleTabId ? "tabid=$scheduleTabId" : "NOT FOUND - will create") . "\n";
echo "  Rules: " . ($rulesTabId ? "tabid=$rulesTabId" : "NOT FOUND - will create") . "\n";

// Step 2: Get SUPPORT parent tab ID
echo "\nStep 2: Getting SUPPORT parent tab ID...\n";
$supportParentResult = $adb->pquery("SELECT parenttabid FROM vtiger_parenttab WHERE parenttab_label = ?", array('SUPPORT'));
if ($adb->num_rows($supportParentResult) == 0) {
    die("ERROR: SUPPORT parent tab not found!\n");
}
$supportParentTabId = $adb->query_result($supportParentResult, 0, 'parenttabid');
echo "  SUPPORT parent tab ID: $supportParentTabId\n";

// Step 3: Bind Activities (Calendar) to SUPPORT
echo "\nStep 3: Binding Activities (Calendar) to SUPPORT...\n";

// Get max sequence for SUPPORT
$maxSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array('SUPPORT'));
$maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
$nextSeq = ($maxSeq ? $maxSeq + 1 : 1);

// Check if already exists
$checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", 
    array('SUPPORT', $activitiesTabId));

if ($adb->num_rows($checkResult) == 0) {
    $adb->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
        array('SUPPORT', $activitiesTabId, $nextSeq, 1));
    echo "  ✓ Activities (Calendar) -> SUPPORT (sequence: $nextSeq)\n";
    $nextSeq++;
} else {
    echo "  - Activities (Calendar) -> SUPPORT mapping already exists\n";
}

// Ensure Calendar is visible
if (isset($moduleInfo['Calendar']) && $moduleInfo['Calendar']['presence'] != 0) {
    $adb->pquery("UPDATE vtiger_tab SET presence = 0 WHERE tabid = ?", array($activitiesTabId));
    echo "  ✓ Updated Calendar visibility (presence = 0)\n";
}

// Step 4: Create placeholder modules if missing
function createPlaceholderModule($moduleName, $moduleLabel, $iconClass, $parentTabId) {
    global $adb;
    
    echo "\n  Creating placeholder module: $moduleName...\n";
    
    // Get next available tabid
    $maxTabResult = $adb->pquery("SELECT MAX(tabid) as max_tabid FROM vtiger_tab", array());
    $maxTabId = $adb->query_result($maxTabResult, 0, 'max_tabid');
    $newTabId = $maxTabId + 1;
    
    // Get next sequence
    $maxSeqResult = $adb->pquery("SELECT MAX(tabsequence) as max_seq FROM vtiger_tab WHERE tabsequence > 0", array());
    $maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
    $newSeq = $maxSeq + 1;
    
    // Insert into vtiger_tab
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
            'SUPPORT', // parent
            0  // isentitytype: 0 = no database tables
        )
    );
    
    echo "    ✓ Inserted into vtiger_tab (tabid: $newTabId)\n";
    
    // Link to SUPPORT parent tab
    $maxRelSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_parenttabrel WHERE parenttabid = ?", array($parentTabId));
    $maxRelSeq = $adb->query_result($maxRelSeqResult, 0, 'max_seq');
    $newRelSeq = ($maxRelSeq ? $maxRelSeq + 1 : 1);
    
    $adb->pquery("INSERT INTO vtiger_parenttabrel (parenttabid, tabid, sequence) VALUES (?, ?, ?)",
        array($parentTabId, $newTabId, $newRelSeq));
    
    echo "    ✓ Linked to SUPPORT parent tab (sequence: $newRelSeq)\n";
    
    // Create module directory and files
    $moduleDir = "modules/$moduleName";
    if (!is_dir($moduleDir)) {
        mkdir($moduleDir, 0755, true);
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
    echo "    ✓ Created module file: $moduleFile\n";
    
    // Create manifest.xml
    $manifestFile = "$moduleDir/manifest.xml";
    $manifestContent = <<<XML
<?xml version='1.0'?>
<module>
    <name>$moduleName</name>
    <label>$moduleLabel</label>
    <parent>SUPPORT</parent>
    <type>extension</type>
    <version>1.0</version>
    <dependencies>
        <vtiger_version>8.0.0</vtiger_version>
    </dependencies>
</module>
XML;
    
    file_put_contents($manifestFile, $manifestContent);
    echo "    ✓ Created manifest: $manifestFile\n";
    
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
     * Get module icon HTML
     */
    public function getModuleIcon() {
        \$title = vtranslate('$moduleLabel', '$moduleName');
        return "<i class='fa {$iconClass}' title='\$title'></i>";
    }
}
?>
PHP;
    
    file_put_contents($modelFile, $modelContent);
    echo "    ✓ Created model: $modelFile\n";
    
    // Create views directory
    $viewsDir = "$moduleDir/views";
    if (!is_dir($viewsDir)) {
        mkdir($viewsDir, 0755, true);
    }
    
    $viewFile = "$viewsDir/List.php";
    $viewContent = <<<PHP
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
 * Placeholder module List View
 * Minimal implementation for UI rendering only
 */
class {$moduleName}_List_View extends Vtiger_List_View {
    
    // Use parent implementation - no custom logic needed
}
?>
PHP;
    
    file_put_contents($viewFile, $viewContent);
    echo "    ✓ Created view: $viewFile\n";
    
    // Create layout template
    $layoutDir = "layouts/v7/modules/$moduleName";
    if (!is_dir($layoutDir)) {
        mkdir($layoutDir, 0755, true);
    }
    
    $templateFile = "$layoutDir/ListViewContents.tpl";
    $templateContent = <<<TPL
{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h3>$moduleLabel</h3>
            <p>This is a placeholder module for future use.</p>
        </div>
    </div>
</div>
TPL;
    
    file_put_contents($templateFile, $templateContent);
    echo "    ✓ Created template: $templateFile\n";
    
    echo "    ✓ Module $moduleName created successfully!\n";
    
    return $newTabId;
}

// Create Schedule if missing
if (!$scheduleTabId) {
    echo "\nStep 4: Creating Schedule placeholder module...\n";
    $scheduleTabId = createPlaceholderModule('Schedule', 'Schedule', 'fa-calendar-check-o', $supportParentTabId);
} else {
    echo "\nStep 4: Schedule module already exists (tabid: $scheduleTabId)\n";
}

// Create Rules if missing
if (!$rulesTabId) {
    echo "\nStep 5: Creating Rules placeholder module...\n";
    $rulesTabId = createPlaceholderModule('Rules', 'Rules', 'fa-gavel', $supportParentTabId);
} else {
    echo "\nStep 5: Rules module already exists (tabid: $rulesTabId)\n";
}

// Step 6: Bind Schedule & Rules to SUPPORT app
echo "\nStep 6: Binding Schedule & Rules to SUPPORT app...\n";

// Get current max sequence
$maxSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array('SUPPORT'));
$maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
$nextSeq = ($maxSeq ? $maxSeq + 1 : 1);

if ($scheduleTabId) {
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", 
        array('SUPPORT', $scheduleTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array('SUPPORT', $scheduleTabId, $nextSeq, 1));
        echo "  ✓ Schedule -> SUPPORT (sequence: $nextSeq)\n";
        $nextSeq++;
    } else {
        echo "  - Schedule -> SUPPORT mapping already exists\n";
    }
}

if ($rulesTabId) {
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", 
        array('SUPPORT', $rulesTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array('SUPPORT', $rulesTabId, $nextSeq, 1));
        echo "  ✓ Rules -> SUPPORT (sequence: $nextSeq)\n";
    } else {
        echo "  - Rules -> SUPPORT mapping already exists\n";
    }
}

// Step 7: Parent tab relations (if needed)
echo "\nStep 7: Verifying parent tab relations...\n";

if ($scheduleTabId) {
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_parenttabrel WHERE parenttabid = ? AND tabid = ?", 
        array($supportParentTabId, $scheduleTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $maxRelSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_parenttabrel WHERE parenttabid = ?", 
            array($supportParentTabId));
        $maxRelSeq = $adb->query_result($maxRelSeqResult, 0, 'max_seq');
        $newRelSeq = ($maxRelSeq ? $maxRelSeq + 1 : 1);
        
        $adb->pquery("INSERT INTO vtiger_parenttabrel (parenttabid, tabid, sequence) VALUES (?, ?, ?)",
            array($supportParentTabId, $scheduleTabId, $newRelSeq));
        echo "  ✓ Schedule -> SUPPORT parent tab (sequence: $newRelSeq)\n";
    } else {
        echo "  - Schedule -> SUPPORT parent tab relation already exists\n";
    }
}

if ($rulesTabId) {
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_parenttabrel WHERE parenttabid = ? AND tabid = ?", 
        array($supportParentTabId, $rulesTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $maxRelSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_parenttabrel WHERE parenttabid = ?", 
            array($supportParentTabId));
        $maxRelSeq = $adb->query_result($maxRelSeqResult, 0, 'max_seq');
        $newRelSeq = ($maxRelSeq ? $maxRelSeq + 1 : 1);
        
        $adb->pquery("INSERT INTO vtiger_parenttabrel (parenttabid, tabid, sequence) VALUES (?, ?, ?)",
            array($supportParentTabId, $rulesTabId, $newRelSeq));
        echo "  ✓ Rules -> SUPPORT parent tab (sequence: $newRelSeq)\n";
    } else {
        echo "  - Rules -> SUPPORT parent tab relation already exists\n";
    }
}

// Verification
echo "\n=== Verification ===\n";

echo "\nApp-to-Tab Mappings for SUPPORT:\n";
$appResult = $adb->pquery("SELECT appname, tabid, sequence, visible FROM vtiger_app2tab WHERE appname = 'SUPPORT' AND tabid IN (?, ?, ?) ORDER BY sequence",
    array($activitiesTabId, $scheduleTabId ?: 0, $rulesTabId ?: 0));

$moduleNames = array(
    $activitiesTabId => 'Activities (Calendar)',
    $scheduleTabId => 'Schedule',
    $rulesTabId => 'Rules'
);

while ($row = $adb->fetchByAssoc($appResult)) {
    $moduleName = $moduleNames[$row['tabid']] ?? "Unknown";
    echo "  ✓ $moduleName -> {$row['appname']} (sequence: {$row['sequence']}, visible: {$row['visible']})\n";
}

echo "\nParent Tab Relations:\n";
$parentResult = $adb->pquery("SELECT p.parenttab_label, ptr.tabid, ptr.sequence 
    FROM vtiger_parenttabrel ptr 
    INNER JOIN vtiger_parenttab p ON p.parenttabid = ptr.parenttabid 
    WHERE ptr.parenttabid = ? AND ptr.tabid IN (?, ?, ?) ORDER BY ptr.sequence",
    array($supportParentTabId, $activitiesTabId, $scheduleTabId ?: 0, $rulesTabId ?: 0));

while ($row = $adb->fetchByAssoc($parentResult)) {
    $moduleName = $moduleNames[$row['tabid']] ?? "Unknown";
    echo "  ✓ $moduleName -> {$row['parenttab_label']} (sequence: {$row['sequence']})\n";
}

echo "\n=== Fix Complete ===\n";
echo "\nNext steps:\n";
echo "1. Clear cache: rm -rf cache/* templates_c/* cache/menu/*\n";
echo "2. Logout and login again\n";
echo "3. Check SUPPORT menu - Activities, Schedule, and Rules should appear\n";


