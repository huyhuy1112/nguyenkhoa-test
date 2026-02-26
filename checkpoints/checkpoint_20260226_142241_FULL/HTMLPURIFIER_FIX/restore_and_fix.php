<?php
/**
 * RESTORE AND FIX HTMLPURIFIER - Restore backup and apply new fix
 * 
 * File này sẽ restore file gốc từ backup và apply fix mới
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/HTMLPURIFIER_FIX/restore_and_fix.php
 *   3. File sẽ restore và apply fix mới
 *   4. Test website
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>RESTORE AND FIX HTMLPURIFIER</h1>";
echo "<hr>";

// Get root directory
$script_dir = dirname(__FILE__);
$root_dir = dirname($script_dir);

// Find VtlibUtils.php
$possible_paths = [
    $root_dir . '/include/utils/VtlibUtils.php',
    dirname($root_dir) . '/include/utils/VtlibUtils.php',
    'include/utils/VtlibUtils.php',
    '../include/utils/VtlibUtils.php',
    '../../include/utils/VtlibUtils.php'
];

$vtlib_utils_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $vtlib_utils_path = $path;
        break;
    }
}

if (!$vtlib_utils_path) {
    die("<p style='color:red'><strong>✗ Cannot find include/utils/VtlibUtils.php</strong></p>");
}

$backup_path = dirname($vtlib_utils_path) . '/VtlibUtils.php.backup';

echo "<h2>Step 1: Check backup file</h2>";
if (!file_exists($backup_path)) {
    echo "<p style='color:orange'><strong>⚠️ Backup file not found: $backup_path</p>";
    echo "<p>Will create new backup from current file...</p>";
    
    // Create backup from current file
    $current_content = file_get_contents($vtlib_utils_path);
    if ($current_content && file_put_contents($backup_path, $current_content)) {
        chmod($backup_path, 0644);
        echo "<p style='color:green'><strong>✅ Backup created from current file</strong></p>";
    } else {
        die("<p style='color:red'><strong>✗ Cannot create backup</strong></p>");
    }
} else {
    echo "<p style='color:green'><strong>✅ Backup file found: $backup_path</strong></p>";
}

echo "<hr>";

echo "<h2>Step 2: Restore from backup</h2>";
$backup_content = file_get_contents($backup_path);
if ($backup_content === false) {
    die("<p style='color:red'><strong>✗ Cannot read backup file</strong></p>");
}

// Remove old fix if exists
$backup_content = preg_replace('/\s*\/\/ FIX: Ensure HTMLPurifier is autoloaded.*?}\s*/s', '', $backup_content);

if (file_put_contents($vtlib_utils_path, $backup_content)) {
    chmod($vtlib_utils_path, 0644);
    echo "<p style='color:green'><strong>✅ File restored from backup</strong></p>";
} else {
    die("<p style='color:red'><strong>✗ Cannot restore file</strong></p>");
}

echo "<hr>";

echo "<h2>Step 3: Apply new fix</h2>";

// Read restored file
$content = file_get_contents($vtlib_utils_path);
if ($content === false) {
    die("<p style='color:red'><strong>✗ Cannot read file</strong></p>");
}

// Find function vtlib_purify
$function_pattern = '/function\s+vtlib_purify\s*\([^)]*\)\s*\{/';
if (!preg_match($function_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
    die("<p style='color:red'><strong>✗ Cannot find function vtlib_purify()</strong></p>");
}

$function_start = $matches[0][1];
$function_start_pos = $function_start + strlen($matches[0][0]);

// Find insertion point (after global statements, before static)
$insert_pos = $function_start_pos;
$lines_to_check = substr($content, $insert_pos, 300);
$global_end = strpos($lines_to_check, "\n    static");
if ($global_end === false) {
    $global_end = strpos($lines_to_check, "\n    \$");
}
if ($global_end !== false) {
    $insert_pos += $global_end + 1;
} else {
    // Find first line after function declaration
    $next_line = strpos($content, "\n", $function_start_pos);
    if ($next_line !== false) {
        $insert_pos = $next_line + 1;
    }
}

// Prepare new fix code (using $use_root_directory)
$fix_code = "\n    // FIX: Ensure HTMLPurifier is autoloaded\n";
$fix_code .= "    if (!class_exists('HTMLPurifier_Config')) {\n";
$fix_code .= "        // Use root_directory to find HTMLPurifier\n";
$fix_code .= "        \$htmlpurifier_base = \$use_root_directory . '/vendor/ezyang/htmlpurifier/library';\n";
$fix_code .= "        \n";
$fix_code .= "        // Try autoload file first\n";
$fix_code .= "        if (file_exists(\$htmlpurifier_base . '/HTMLPurifier.autoload.php')) {\n";
$fix_code .= "            require_once \$htmlpurifier_base . '/HTMLPurifier.autoload.php';\n";
$fix_code .= "        } elseif (file_exists(\$htmlpurifier_base . '/HTMLPurifier.auto.php')) {\n";
$fix_code .= "            require_once \$htmlpurifier_base . '/HTMLPurifier.auto.php';\n";
$fix_code .= "        } elseif (file_exists(\$htmlpurifier_base . '/HTMLPurifier.php')) {\n";
$fix_code .= "            // Load manually if autoload files don't exist\n";
$fix_code .= "            require_once \$htmlpurifier_base . '/HTMLPurifier.php';\n";
$fix_code .= "            require_once \$htmlpurifier_base . '/HTMLPurifier/Config.php';\n";
$fix_code .= "            require_once \$htmlpurifier_base . '/HTMLPurifier/Bootstrap.php';\n";
$fix_code .= "        } else {\n";
$fix_code .= "            // Fallback: try relative paths\n";
$fix_code .= "            \$fallback_paths = [\n";
$fix_code .= "                dirname(__FILE__) . '/../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.autoload.php',\n";
$fix_code .= "                dirname(__FILE__) . '/../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php',\n";
$fix_code .= "                'vendor/ezyang/htmlpurifier/library/HTMLPurifier.autoload.php',\n";
$fix_code .= "                'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php'\n";
$fix_code .= "            ];\n";
$fix_code .= "            \n";
$fix_code .= "            foreach (\$fallback_paths as \$path) {\n";
$fix_code .= "                if (file_exists(\$path)) {\n";
$fix_code .= "                    require_once \$path;\n";
$fix_code .= "                    break;\n";
$fix_code .= "                }\n";
$fix_code .= "            }\n";
$fix_code .= "        }\n";
$fix_code .= "        \n";
$fix_code .= "        // Final check - if still not loaded, throw exception\n";
$fix_code .= "        if (!class_exists('HTMLPurifier_Config')) {\n";
$fix_code .= "            throw new Exception('HTMLPurifier_Config class not found. Please ensure HTMLPurifier is installed via Composer. Checked: ' . \$htmlpurifier_base);\n";
$fix_code .= "        }\n";
$fix_code .= "    }\n";

// Insert fix code
$new_content = substr_replace($content, $fix_code, $insert_pos, 0);

// Write fixed file
if (file_put_contents($vtlib_utils_path, $new_content)) {
    chmod($vtlib_utils_path, 0644);
    echo "<p style='color:green'><strong>✅ New fix applied successfully</strong></p>";
} else {
    die("<p style='color:red'><strong>✗ Cannot write file</strong></p>");
}

echo "<hr>";

echo "<h2>Step 4: Verify HTMLPurifier exists</h2>";
$htmlpurifier_paths = [
    $root_dir . '/vendor/ezyang/htmlpurifier/library/HTMLPurifier.php',
    dirname($root_dir) . '/vendor/ezyang/htmlpurifier/library/HTMLPurifier.php',
    'vendor/ezyang/htmlpurifier/library/HTMLPurifier.php',
    '../vendor/ezyang/htmlpurifier/library/HTMLPurifier.php'
];

$htmlpurifier_found = false;
foreach ($htmlpurifier_paths as $path) {
    if (file_exists($path)) {
        echo "<p style='color:green'><strong>✅ HTMLPurifier found: $path</strong></p>";
        $htmlpurifier_found = true;
        break;
    }
}

if (!$htmlpurifier_found) {
    echo "<p style='color:red'><strong>✗ HTMLPurifier NOT FOUND in vendor/</strong></p>";
    echo "<p>You may need to run: <code>composer install</code></p>";
}

echo "<hr>";
echo "<h2>✅ RESTORE AND FIX COMPLETE</h2>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test website: <a href='../index.php' target='_blank'>index.php</a></li>";
echo "<li>If it works, delete this fix file</li>";
echo "<li>If it doesn't work, check if HTMLPurifier is installed: <code>composer install</code></li>";
echo "</ol>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong để bảo mật!</p>";
?>

