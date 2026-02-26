<?php
/**
 * Simple script to make Opportunity Name optional
 * Run: docker exec vtiger_web php make_potentialname_optional_simple.php
 */

// Load Vtiger configuration
chdir(__DIR__);
require_once 'config.inc.php';

global $adb;

echo "Making Opportunity Name optional...\n\n";

try {
    // Get Potentials tabid
    $tabResult = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = 'Potentials'", array());
    if ($adb->num_rows($tabResult) == 0) {
        die("ERROR: Potentials module not found!\n");
    }
    $tabid = $adb->query_result($tabResult, 0, 'tabid');
    echo "✓ Potentials tabid: $tabid\n";
    
    // Get current field info
    $fieldResult = $adb->pquery(
        "SELECT fieldid, typeofdata FROM vtiger_field WHERE fieldname = 'potentialname' AND tabid = ?",
        array($tabid)
    );
    
    if ($adb->num_rows($fieldResult) == 0) {
        die("ERROR: potentialname field not found!\n");
    }
    
    $fieldid = $adb->query_result($fieldResult, 0, 'fieldid');
    $currentTypeofdata = $adb->query_result($fieldResult, 0, 'typeofdata');
    
    echo "✓ Current typeofdata: $currentTypeofdata\n";
    
    // Check if already optional
    if (strpos($currentTypeofdata, '~O') !== false && strpos($currentTypeofdata, '~M') === false) {
        echo "✓ Field is already optional. No changes needed.\n";
        exit(0);
    }
    
    // Update to optional
    $newTypeofdata = str_replace('~M', '~O', $currentTypeofdata);
    
    $updateResult = $adb->pquery(
        "UPDATE vtiger_field SET typeofdata = ? WHERE fieldid = ?",
        array($newTypeofdata, $fieldid)
    );
    
    if ($updateResult) {
        echo "✓ Updated typeofdata to: $newTypeofdata\n";
        echo "\n✅ SUCCESS: Opportunity Name is now optional!\n";
        echo "\nNext steps:\n";
        echo "1. Clear cache: rm -rf cache/* templates_c/*\n";
        echo "2. Test creating Opportunity without Opportunity Name\n";
    } else {
        die("ERROR: Failed to update field\n");
    }
    
} catch (Exception $e) {
    die("ERROR: " . $e->getMessage() . "\n");
}


