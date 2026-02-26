<?php
/**
 * Script to make Opportunity Name (potentialname) field optional (non-mandatory)
 * 
 * This script:
 * 1. Updates vtiger_field.typeofdata from V~M to V~O
 * 2. Removes potentialname from mandatory_fields array in Potentials.php
 * 3. Clears cache
 */

require_once 'config.inc.php';
global $adb, $log;

echo "========================================\n";
echo "Making Opportunity Name Optional\n";
echo "========================================\n\n";

try {
    // Step 1: Get Potentials tabid
    $tabResult = $adb->pquery(
        "SELECT tabid FROM vtiger_tab WHERE name = 'Potentials'",
        array()
    );
    
    if ($adb->num_rows($tabResult) == 0) {
        die("ERROR: Potentials module not found!\n");
    }
    
    $tabid = $adb->query_result($tabResult, 0, 'tabid');
    echo "✓ Found Potentials module (tabid: $tabid)\n";
    
    // Step 2: Find potentialname field
    $fieldResult = $adb->pquery(
        "SELECT fieldid, fieldname, columnname, typeofdata, presence 
         FROM vtiger_field 
         WHERE fieldname = 'potentialname' AND tabid = ?",
        array($tabid)
    );
    
    if ($adb->num_rows($fieldResult) == 0) {
        die("ERROR: potentialname field not found!\n");
    }
    
    $fieldRow = $adb->fetchByAssoc($fieldResult);
    $fieldid = $fieldRow['fieldid'];
    $currentTypeofdata = $fieldRow['typeofdata'];
    
    echo "✓ Found potentialname field (fieldid: $fieldid)\n";
    echo "  Current typeofdata: $currentTypeofdata\n";
    
    // Step 3: Check if already optional
    if (strpos($currentTypeofdata, '~O') !== false) {
        echo "\n⚠ WARNING: Field is already optional (typeofdata contains ~O)\n";
        echo "  No changes needed.\n";
        exit(0);
    }
    
    // Step 4: Update typeofdata from V~M to V~O (or keep type, just change mandatory)
    $newTypeofdata = str_replace('~M', '~O', $currentTypeofdata);
    
    // If it doesn't have ~M, try to add ~O
    if ($newTypeofdata == $currentTypeofdata) {
        // Check format: might be "V~M~N" or just "V~M"
        $parts = explode('~', $currentTypeofdata);
        if (count($parts) >= 2) {
            $parts[1] = 'O'; // Change mandatory to optional
            $newTypeofdata = implode('~', $parts);
        } else {
            $newTypeofdata = $currentTypeofdata . '~O';
        }
    }
    
    echo "  New typeofdata: $newTypeofdata\n";
    
    // Step 5: Update database
    $updateResult = $adb->pquery(
        "UPDATE vtiger_field SET typeofdata = ? WHERE fieldid = ?",
        array($newTypeofdata, $fieldid)
    );
    
    if ($updateResult) {
        echo "✓ Updated vtiger_field.typeofdata\n";
    } else {
        die("ERROR: Failed to update vtiger_field\n");
    }
    
    // Step 6: Update Potentials.php to remove potentialname from mandatory_fields
    $potentialsFile = 'modules/Potentials/Potentials.php';
    if (file_exists($potentialsFile)) {
        $fileContent = file_get_contents($potentialsFile);
        
        // Find and replace mandatory_fields array
        // Pattern: var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'potentialname');
        $pattern = "/var\s+\$mandatory_fields\s*=\s*Array\s*\([^)]*\)\s*;/";
        
        if (preg_match($pattern, $fileContent, $matches)) {
            $oldLine = $matches[0];
            
            // Remove 'potentialname' from the array
            $newLine = preg_replace(
                "/['\"]potentialname['\"]\s*,?\s*/",
                "",
                $oldLine
            );
            
            // Clean up extra commas
            $newLine = preg_replace("/,\s*,/", ",", $newLine);
            $newLine = preg_replace("/Array\s*\(\s*,/", "Array(", $newLine);
            $newLine = preg_replace("/,\s*\)/", ")", $newLine);
            
            $fileContent = str_replace($oldLine, $newLine, $fileContent);
            
            if (file_put_contents($potentialsFile, $fileContent)) {
                echo "✓ Updated modules/Potentials/Potentials.php\n";
                echo "  Removed 'potentialname' from \$mandatory_fields array\n";
            } else {
                echo "⚠ WARNING: Could not write to Potentials.php (check permissions)\n";
            }
        } else {
            echo "⚠ WARNING: Could not find \$mandatory_fields in Potentials.php\n";
        }
    } else {
        echo "⚠ WARNING: Potentials.php not found\n";
    }
    
    // Step 7: Clear cache
    $cacheDirs = array('cache', 'templates_c');
    foreach ($cacheDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            echo "✓ Cleared $dir/\n";
        }
    }
    
    echo "\n========================================\n";
    echo "✅ SUCCESS: Opportunity Name is now optional\n";
    echo "========================================\n";
    echo "\nNext steps:\n";
    echo "1. Clear browser cache\n";
    echo "2. Test creating Opportunity without filling Opportunity Name\n";
    echo "3. Verify that save works without validation error\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}


