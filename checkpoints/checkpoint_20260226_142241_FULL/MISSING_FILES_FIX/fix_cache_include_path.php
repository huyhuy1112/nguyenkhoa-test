<?php
/**
 * FIX CACHE INCLUDE PATH - Fix include path for Cache.php
 * 
 * File này sẽ sửa đường dẫn include Cache.php trong Module.php
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/MISSING_FILES_FIX/fix_cache_include_path.php
 *   3. File sẽ sửa đường dẫn include
 *   4. Test website
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>FIX CACHE INCLUDE PATH</h1>";
echo "<hr>";

$root_dir = '/home/nhtdbus8/supertestcrm.tdbsolution.com';
$module_file = $root_dir . '/vtlib/Vtiger/Module.php';
$cache_file = $root_dir . '/includes/runtime/Cache.php';

// Check files
if (!file_exists($module_file)) {
    die("<p style='color:red'><strong>✗ Module.php NOT FOUND: $module_file</strong></p>");
}

if (!file_exists($cache_file)) {
    die("<p style='color:red'><strong>✗ Cache.php NOT FOUND: $cache_file</strong></p>");
}

echo "<p style='color:green'><strong>✅ Both files exist</strong></p>";

// Read Module.php
$content = file_get_contents($module_file);
if ($content === false) {
    die("<p style='color:red'><strong>✗ Cannot read Module.php</strong></p>");
}

// Check current include statement
echo "<h2>Current Include Statement</h2>";
if (preg_match("/(require|include)(_once)?\s+['\"](.*includes\/runtime\/Cache\.php.*)['\"]/", $content, $matches)) {
    echo "<p>Found: <code>" . htmlspecialchars($matches[0]) . "</code></p>";
    echo "<p>Path: <code>" . htmlspecialchars($matches[3]) . "</code></p>";
} else {
    echo "<p style='color:orange'><strong>⚠️ Cannot find include statement for Cache.php</strong></p>";
}

// Create backup
$backup_file = $root_dir . '/vtlib/Vtiger/Module.php.backup';
if (!file_exists($backup_file)) {
    file_put_contents($backup_file, $content);
    chmod($backup_file, 0644);
    echo "<p style='color:green'><strong>✅ Backup created</strong></p>";
}

// Fix include path - use absolute path or correct relative path
// From vtlib/Vtiger/Module.php to includes/runtime/Cache.php
// Relative path should be: ../../includes/runtime/Cache.php

$patterns_to_try = [
    // Try various patterns
    "/(require|include)(_once)?\s+['\"]includes\/runtime\/Cache\.php['\"]/",
    "/(require|include)(_once)?\s+['\"].*includes\/runtime\/Cache\.php['\"]/",
];

$fixed = false;
foreach ($patterns_to_try as $pattern) {
    if (preg_match($pattern, $content)) {
        // Replace with absolute path or correct relative path
        $new_include = "require_once dirname(__FILE__) . '/../../includes/runtime/Cache.php';";
        $content = preg_replace($pattern, $new_include, $content);
        $fixed = true;
        echo "<p style='color:green'><strong>✅ Fixed include path</strong></p>";
        break;
    }
}

// If not found, add it after other includes
if (!$fixed) {
    // Find where to insert (after other includes)
    $insert_pattern = "/(include_once|require_once)\s+['\"].*['\"];/";
    if (preg_match_all($insert_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        // Insert after last include
        $last_match = end($matches[0]);
        $insert_pos = $last_match[1] + strlen($last_match[0]);
        
        // Find end of line
        $newline_pos = strpos($content, "\n", $insert_pos);
        if ($newline_pos !== false) {
            $insert_pos = $newline_pos + 1;
        }
        
        $new_include = "require_once dirname(__FILE__) . '/../../includes/runtime/Cache.php';\n";
        $content = substr_replace($content, $new_include, $insert_pos, 0);
        $fixed = true;
        echo "<p style='color:green'><strong>✅ Added include statement</strong></p>";
    }
}

if (!$fixed) {
    // Last resort: add at line 11 (where error occurs)
    $lines = explode("\n", $content);
    if (count($lines) >= 11) {
        // Insert after line 10 (index 9)
        $new_include = "require_once dirname(__FILE__) . '/../../includes/runtime/Cache.php';";
        array_splice($lines, 10, 0, $new_include);
        $content = implode("\n", $lines);
        $fixed = true;
        echo "<p style='color:green'><strong>✅ Added include at line 11</strong></p>";
    }
}

// Write fixed file
if ($fixed) {
    if (file_put_contents($module_file, $content)) {
        chmod($module_file, 0644);
        echo "<p style='color:green'><strong>✅ Module.php fixed successfully!</strong></p>";
    } else {
        die("<p style='color:red'><strong>✗ Cannot write Module.php</strong></p>");
    }
} else {
    echo "<p style='color:red'><strong>✗ Cannot fix automatically</strong></p>";
    echo "<p>Please manually edit Module.php and add this line (around line 11):</p>";
    echo "<pre>require_once dirname(__FILE__) . '/../../includes/runtime/Cache.php';</pre>";
}

echo "<hr>";
echo "<h2>✅ FIX COMPLETE</h2>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test website: <a href='../index.php' target='_blank'>index.php</a></li>";
echo "<li>Or test: <a href='../index_test.php' target='_blank'>index_test.php</a></li>";
echo "<li>If it works, delete this file</li>";
echo "</ol>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong!</p>";
?>

