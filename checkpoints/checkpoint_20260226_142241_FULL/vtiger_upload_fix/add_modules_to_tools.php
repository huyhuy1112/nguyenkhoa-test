<?php
/**
 * Add Invoices, Orders, History, and Document Templates to TOOLS menu
 * Database-level menu binding only - no template edits
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/VtlibUtils.php';

$adb = PearDatabase::getInstance();

echo "=== Adding Modules to TOOLS Menu ===\n\n";

// Step 1: Discover existing modules
echo "Step 1: Discovering existing modules...\n";
$modulesResult = $adb->pquery("SELECT tabid, name, presence, isentitytype, customized FROM vtiger_tab WHERE name IN (?, ?, ?, ?, ?, ?, ?)",
    array('Invoice', 'Invoices', 'SalesOrder', 'Documents', 'DocumentTemplates', 'History', 'Orders'));

$moduleInfo = array();
while ($row = $adb->fetchByAssoc($modulesResult)) {
    $moduleInfo[$row['name']] = $row;
    echo "  {$row['name']}: tabid={$row['tabid']}, presence={$row['presence']}, isentitytype={$row['isentitytype']}\n";
}

// Identify modules
$invoiceTabId = isset($moduleInfo['Invoice']) ? $moduleInfo['Invoice']['tabid'] : 
                (isset($moduleInfo['Invoices']) ? $moduleInfo['Invoices']['tabid'] : null);
$invoiceName = isset($moduleInfo['Invoice']) ? 'Invoice' : 
               (isset($moduleInfo['Invoices']) ? 'Invoices' : null);

$ordersTabId = isset($moduleInfo['SalesOrder']) ? $moduleInfo['SalesOrder']['tabid'] : null;
$documentsTabId = isset($moduleInfo['Documents']) ? $moduleInfo['Documents']['tabid'] : null;
$historyTabId = isset($moduleInfo['History']) ? $moduleInfo['History']['tabid'] : null;

echo "\n  Invoice/Invoices: " . ($invoiceTabId ? "tabid=$invoiceTabId ($invoiceName)" : "NOT FOUND") . "\n";
echo "  Orders (SalesOrder): " . ($ordersTabId ? "tabid=$ordersTabId" : "NOT FOUND") . "\n";
echo "  Documents: " . ($documentsTabId ? "tabid=$documentsTabId" : "NOT FOUND") . "\n";
echo "  History: " . ($historyTabId ? "tabid=$historyTabId" : "NOT FOUND - will create") . "\n";

// Step 2: Get TOOLS parent tab ID
echo "\nStep 2: Getting TOOLS parent tab ID...\n";
$toolsParentResult = $adb->pquery("SELECT parenttabid FROM vtiger_parenttab WHERE parenttab_label = ?", array('TOOLS'));
if ($adb->num_rows($toolsParentResult) == 0) {
    die("ERROR: TOOLS parent tab not found!\n");
}
$toolsParentTabId = $adb->query_result($toolsParentResult, 0, 'parenttabid');
echo "  TOOLS parent tab ID: $toolsParentTabId\n";

// Function to create placeholder module
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
            'TOOLS', // parent
            0  // isentitytype: 0 = no database tables
        )
    );
    
    echo "    ✓ Inserted into vtiger_tab (tabid: $newTabId)\n";
    
    // Link to TOOLS parent tab
    $maxRelSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_parenttabrel WHERE parenttabid = ?", array($parentTabId));
    $maxRelSeq = $adb->query_result($maxRelSeqResult, 0, 'max_seq');
    $newRelSeq = ($maxRelSeq ? $maxRelSeq + 1 : 1);
    
    $adb->pquery("INSERT INTO vtiger_parenttabrel (parenttabid, tabid, sequence) VALUES (?, ?, ?)",
        array($parentTabId, $newTabId, $newRelSeq));
    
    echo "    ✓ Linked to TOOLS parent tab (sequence: $newRelSeq)\n";
    
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
    <parent>TOOLS</parent>
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

// Step 3: Bind Invoices to TOOLS
echo "\nStep 3: Binding Invoices to TOOLS...\n";

if ($invoiceTabId) {
    // Get max sequence for TOOLS
    $maxSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array('TOOLS'));
    $maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
    $nextSeq = ($maxSeq ? $maxSeq + 1 : 1);
    
    // Check if already exists
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", 
        array('TOOLS', $invoiceTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array('TOOLS', $invoiceTabId, $nextSeq, 1));
        echo "  ✓ Invoices ($invoiceName) -> TOOLS (sequence: $nextSeq)\n";
        $nextSeq++;
    } else {
        echo "  - Invoices ($invoiceName) -> TOOLS mapping already exists\n";
    }
    
    // Ensure visible
    if (isset($moduleInfo[$invoiceName]) && $moduleInfo[$invoiceName]['presence'] != 0) {
        $adb->pquery("UPDATE vtiger_tab SET presence = 0 WHERE tabid = ?", array($invoiceTabId));
        echo "  ✓ Updated Invoices visibility (presence = 0)\n";
    }
} else {
    echo "  ✗ Invoice/Invoices module not found - skipping\n";
}

// Step 4: Bind Orders (SalesOrder) to TOOLS
echo "\nStep 4: Binding Orders (SalesOrder) to TOOLS...\n";

if ($ordersTabId) {
    // Get current max sequence
    $maxSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array('TOOLS'));
    $maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
    $nextSeq = ($maxSeq ? $maxSeq + 1 : 1);
    
    // Check if already exists
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", 
        array('TOOLS', $ordersTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array('TOOLS', $ordersTabId, $nextSeq, 1));
        echo "  ✓ Orders (SalesOrder) -> TOOLS (sequence: $nextSeq)\n";
        $nextSeq++;
    } else {
        echo "  - Orders (SalesOrder) -> TOOLS mapping already exists\n";
    }
    
    // Ensure visible
    if (isset($moduleInfo['SalesOrder']) && $moduleInfo['SalesOrder']['presence'] != 0) {
        $adb->pquery("UPDATE vtiger_tab SET presence = 0 WHERE tabid = ?", array($ordersTabId));
        echo "  ✓ Updated Orders visibility (presence = 0)\n";
    }
} else {
    echo "  ✗ SalesOrder module not found - skipping\n";
}

// Step 5: Bind Documents (Document Templates) to TOOLS
echo "\nStep 5: Binding Documents (Document Templates) to TOOLS...\n";

if ($documentsTabId) {
    // Get current max sequence
    $maxSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array('TOOLS'));
    $maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
    $nextSeq = ($maxSeq ? $maxSeq + 1 : 1);
    
    // Check if already exists
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", 
        array('TOOLS', $documentsTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array('TOOLS', $documentsTabId, $nextSeq, 1));
        echo "  ✓ Documents (Document Templates) -> TOOLS (sequence: $nextSeq)\n";
        $nextSeq++;
    } else {
        echo "  - Documents -> TOOLS mapping already exists\n";
    }
    
    // Ensure visible
    if (isset($moduleInfo['Documents']) && $moduleInfo['Documents']['presence'] != 0) {
        $adb->pquery("UPDATE vtiger_tab SET presence = 0 WHERE tabid = ?", array($documentsTabId));
        echo "  ✓ Updated Documents visibility (presence = 0)\n";
    }
} else {
    echo "  ✗ Documents module not found - skipping\n";
}

// Step 6: Create History placeholder if missing
if (!$historyTabId) {
    echo "\nStep 6: Creating History placeholder module...\n";
    $historyTabId = createPlaceholderModule('History', 'History', 'fa-history', $toolsParentTabId);
} else {
    echo "\nStep 6: History module already exists (tabid: $historyTabId)\n";
}

// Step 7: Bind History to TOOLS
echo "\nStep 7: Binding History to TOOLS...\n";

if ($historyTabId) {
    // Get current max sequence
    $maxSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_app2tab WHERE appname = ?", array('TOOLS'));
    $maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
    $nextSeq = ($maxSeq ? $maxSeq + 1 : 1);
    
    // Check if already exists
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", 
        array('TOOLS', $historyTabId));
    
    if ($adb->num_rows($checkResult) == 0) {
        $adb->pquery("INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array('TOOLS', $historyTabId, $nextSeq, 1));
        echo "  ✓ History -> TOOLS (sequence: $nextSeq)\n";
    } else {
        echo "  - History -> TOOLS mapping already exists\n";
    }
}

// Step 8: Parent tab relations
echo "\nStep 8: Verifying parent tab relations...\n";

$modulesToLink = array();
if ($invoiceTabId) $modulesToLink[] = array('tabid' => $invoiceTabId, 'name' => $invoiceName);
if ($ordersTabId) $modulesToLink[] = array('tabid' => $ordersTabId, 'name' => 'SalesOrder');
if ($documentsTabId) $modulesToLink[] = array('tabid' => $documentsTabId, 'name' => 'Documents');
if ($historyTabId) $modulesToLink[] = array('tabid' => $historyTabId, 'name' => 'History');

foreach ($modulesToLink as $module) {
    $checkResult = $adb->pquery("SELECT 1 FROM vtiger_parenttabrel WHERE parenttabid = ? AND tabid = ?", 
        array($toolsParentTabId, $module['tabid']));
    
    if ($adb->num_rows($checkResult) == 0) {
        $maxRelSeqResult = $adb->pquery("SELECT MAX(sequence) as max_seq FROM vtiger_parenttabrel WHERE parenttabid = ?", 
            array($toolsParentTabId));
        $maxRelSeq = $adb->query_result($maxRelSeqResult, 0, 'max_seq');
        $newRelSeq = ($maxRelSeq ? $maxRelSeq + 1 : 1);
        
        $adb->pquery("INSERT INTO vtiger_parenttabrel (parenttabid, tabid, sequence) VALUES (?, ?, ?)",
            array($toolsParentTabId, $module['tabid'], $newRelSeq));
        echo "  ✓ {$module['name']} -> TOOLS parent tab (sequence: $newRelSeq)\n";
    } else {
        echo "  - {$module['name']} -> TOOLS parent tab relation already exists\n";
    }
}

// Verification
echo "\n=== Verification ===\n";

echo "\nApp-to-Tab Mappings for TOOLS:\n";
$tabIds = array_filter(array($invoiceTabId, $ordersTabId, $documentsTabId, $historyTabId));
if (!empty($tabIds)) {
    $placeholders = implode(',', array_fill(0, count($tabIds), '?'));
    $appResult = $adb->pquery("SELECT appname, tabid, sequence, visible FROM vtiger_app2tab WHERE appname = 'TOOLS' AND tabid IN ($placeholders) ORDER BY sequence",
        $tabIds);
    
    $moduleNames = array(
        $invoiceTabId => $invoiceName ?: 'Invoice',
        $ordersTabId => 'SalesOrder',
        $documentsTabId => 'Documents',
        $historyTabId => 'History'
    );
    
    while ($row = $adb->fetchByAssoc($appResult)) {
        $moduleName = $moduleNames[$row['tabid']] ?? "Unknown";
        echo "  ✓ $moduleName -> {$row['appname']} (sequence: {$row['sequence']}, visible: {$row['visible']})\n";
    }
}

echo "\nParent Tab Relations:\n";
if (!empty($tabIds)) {
    $placeholders = implode(',', array_fill(0, count($tabIds), '?'));
    $parentResult = $adb->pquery("SELECT p.parenttab_label, ptr.tabid, ptr.sequence 
        FROM vtiger_parenttabrel ptr 
        INNER JOIN vtiger_parenttab p ON p.parenttabid = ptr.parenttabid 
        WHERE ptr.parenttabid = ? AND ptr.tabid IN ($placeholders) ORDER BY ptr.sequence",
        array_merge(array($toolsParentTabId), $tabIds));
    
    $moduleNames = array(
        $invoiceTabId => $invoiceName ?: 'Invoice',
        $ordersTabId => 'SalesOrder',
        $documentsTabId => 'Documents',
        $historyTabId => 'History'
    );
    
    while ($row = $adb->fetchByAssoc($parentResult)) {
        $moduleName = $moduleNames[$row['tabid']] ?? "Unknown";
        echo "  ✓ $moduleName -> {$row['parenttab_label']} (sequence: {$row['sequence']})\n";
    }
}

echo "\n=== Fix Complete ===\n";
echo "\nNext steps:\n";
echo "1. Clear cache: rm -rf cache/* templates_c/* cache/menu/*\n";
echo "2. Logout and login again\n";
echo "3. Check TOOLS menu - Invoices, Orders, Document Templates, and History should appear\n";

