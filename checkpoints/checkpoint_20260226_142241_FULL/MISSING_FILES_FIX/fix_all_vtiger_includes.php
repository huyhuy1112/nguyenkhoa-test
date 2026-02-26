<?php
/**
 * FIX ALL VTIGER INCLUDES - Fix all include paths in vtlib/Vtiger/
 * 
 * File này sẽ sửa tất cả các include path trong vtlib/Vtiger/
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/MISSING_FILES_FIX/fix_all_vtiger_includes.php
 *   3. File sẽ tự động sửa tất cả các include path
 *   4. Test website
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>FIX ALL VTIGER INCLUDES</h1>";
echo "<hr>";

$root_dir = '/home/nhtdbus8/supertestcrm.tdbsolution.com';
$vtiger_dir = $root_dir . '/vtlib/Vtiger';

if (!is_dir($vtiger_dir)) {
    die("<p style='color:red'><strong>✗ vtlib/Vtiger directory NOT FOUND</strong></p>");
}

// Get all PHP files
$files = glob($vtiger_dir . '/*.php');
if (empty($files)) {
    die("<p style='color:red'><strong>✗ No PHP files found in vtlib/Vtiger/</strong></p>");
}

echo "<p><strong>Found " . count($files) . " PHP files</strong></p>";

$fixed_count = 0;
$errors = [];

foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    
    if ($content === false) {
        $errors[] = "Cannot read: $filename";
        continue;
    }
    
    $original_content = $content;
    $file_fixed = false;
    
    // Pattern 1: include_once('vtlib/Vtiger/...')
    if (preg_match_all("/include_once\(['\"]vtlib\/Vtiger\/([^'\"]+)['\"]\)/", $content, $matches)) {
        foreach ($matches[0] as $index => $match) {
            $target_file = $matches[1][$index];
            $replacement = "include_once(dirname(__FILE__) . '/$target_file')";
            $content = str_replace($match, $replacement, $content);
            $file_fixed = true;
        }
    }
    
    // Pattern 2: require_once('vtlib/Vtiger/...')
    if (preg_match_all("/require_once\(['\"]vtlib\/Vtiger\/([^'\"]+)['\"]\)/", $content, $matches)) {
        foreach ($matches[0] as $index => $match) {
            $target_file = $matches[1][$index];
            $replacement = "require_once(dirname(__FILE__) . '/$target_file')";
            $content = str_replace($match, $replacement, $content);
            $file_fixed = true;
        }
    }
    
    // Pattern 3: include_once('vtlib/Vtiger/...') without parentheses
    if (preg_match_all("/include_once\s+['\"]vtlib\/Vtiger\/([^'\"]+)['\"]/", $content, $matches)) {
        foreach ($matches[0] as $index => $match) {
            $target_file = $matches[1][$index];
            $replacement = "include_once(dirname(__FILE__) . '/$target_file')";
            $content = str_replace($match, $replacement, $content);
            $file_fixed = true;
        }
    }
    
    // Pattern 4: require_once('vtlib/Vtiger/...') without parentheses
    if (preg_match_all("/require_once\s+['\"]vtlib\/Vtiger\/([^'\"]+)['\"]/", $content, $matches)) {
        foreach ($matches[0] as $index => $match) {
            $target_file = $matches[1][$index];
            $replacement = "require_once(dirname(__FILE__) . '/$target_file')";
            $content = str_replace($match, $replacement, $content);
            $file_fixed = true;
        }
    }
    
    if ($file_fixed && $content !== $original_content) {
        // Create backup
        $backup_file = $file . '.backup';
        if (!file_exists($backup_file)) {
            file_put_contents($backup_file, $original_content);
        }
        
        // Write fixed file
        if (file_put_contents($file, $content)) {
            chmod($file, 0644);
            echo "<p style='color:green'><strong>✅ Fixed: $filename</strong></p>";
            $fixed_count++;
        } else {
            $errors[] = "Cannot write: $filename";
        }
    }
}

echo "<hr>";
echo "<h2>✅ FIX COMPLETE</h2>";
echo "<p><strong>Fixed files: $fixed_count</strong></p>";

if (!empty($errors)) {
    echo "<h3 style='color:red'>Errors:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test website: <a href='../index.php' target='_blank'>index.php</a></li>";
echo "<li>Or test: <a href='../index_test.php' target='_blank'>index_test.php</a></li>";
echo "<li>If it works, delete this file</li>";
echo "</ol>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong!</p>";
?>

