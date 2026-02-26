<?php
/**
 * FIX HTMLPURIFIER AUTOLOAD - Automatic Fix Script
 * 
 * File này sẽ tự động fix lỗi "Class HTMLPurifier_Config not found"
 * bằng cách thêm HTMLPurifier autoload check vào VtlibUtils.php
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting (cùng cấp với index.php)
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/HTMLPURIFIER_FIX/fix_htmlpurifier_autoload.php
 *   3. File sẽ tự động backup và fix VtlibUtils.php
 *   4. Test website
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>FIX HTMLPURIFIER AUTOLOAD</h1>";
echo "<hr>";

// Get root directory (parent of HTMLPURIFIER_FIX)
$script_dir = dirname(__FILE__);
$root_dir = dirname($script_dir);

// Try multiple paths to find VtlibUtils.php
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
    echo "<p style='color:red'><strong>✗ Cannot find include/utils/VtlibUtils.php</strong></p>";
    echo "<p><strong>Tried paths:</strong></p>";
    echo "<ul>";
    foreach ($possible_paths as $path) {
        $exists = file_exists($path) ? "✅ EXISTS" : "✗ NOT FOUND";
        echo "<li>$path - $exists</li>";
    }
    echo "</ul>";
    echo "<p><strong>Current script directory:</strong> $script_dir</p>";
    echo "<p><strong>Root directory (parent):</strong> $root_dir</p>";
    die();
}

$backup_path = dirname($vtlib_utils_path) . '/VtlibUtils.php.backup';

// Check if file exists
if (!file_exists($vtlib_utils_path)) {
    die("<p style='color:red'><strong>✗ File not found: $vtlib_utils_path</strong></p>");
}

// Read original file
$content = file_get_contents($vtlib_utils_path);
if ($content === false) {
    die("<p style='color:red'><strong>✗ Cannot read file: $vtlib_utils_path</strong></p>");
}

// Check if already fixed
if (strpos($content, '// FIX: Ensure HTMLPurifier is autoloaded') !== false) {
    echo "<p style='color:orange'><strong>⚠️ File already fixed!</strong></p>";
    echo "<p>Remove this check if you want to re-apply the fix.</p>";
    exit;
}

// Create backup
if (!file_exists($backup_path)) {
    if (file_put_contents($backup_path, $content)) {
        chmod($backup_path, 0644);
        echo "<p style='color:green'><strong>✅ Backup created: $backup_path</strong></p>";
    } else {
        echo "<p style='color:orange'><strong>⚠️ Cannot create backup (may already exist)</strong></p>";
    }
} else {
    echo "<p style='color:orange'><strong>⚠️ Backup already exists: $backup_path</strong></p>";
}

// Find the function vtlib_purify
$function_pattern = '/function\s+vtlib_purify\s*\([^)]*\)\s*\{/';
if (!preg_match($function_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
    die("<p style='color:red'><strong>✗ Cannot find function vtlib_purify()</strong></p>");
}

$function_start = $matches[0][1];
$function_start_pos = $function_start + strlen($matches[0][0]);

// Find the first line after function declaration (should be global statement)
$next_line_start = strpos($content, "\n", $function_start_pos);
if ($next_line_start === false) {
    $next_line_start = strlen($content);
}

// Find the end of global statements (usually 2-3 lines)
$insert_pos = $next_line_start + 1;
$lines_to_check = substr($content, $insert_pos, 200);
$global_end = strpos($lines_to_check, "\n    static");
if ($global_end === false) {
    $global_end = strpos($lines_to_check, "\n    \$");
}
if ($global_end !== false) {
    $insert_pos += $global_end + 1;
}

// Prepare fix code - Use $root_directory for correct path resolution
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

// Remove the old check - it's now in the fix_code above

// Write fixed file
if (file_put_contents($vtlib_utils_path, $new_content)) {
    chmod($vtlib_utils_path, 0644);
    echo "<p style='color:green'><strong>✅ File fixed successfully: $vtlib_utils_path</strong></p>";
    echo "<p>Fix applied: HTMLPurifier autoload check added to vtlib_purify() function</p>";
} else {
    die("<p style='color:red'><strong>✗ Cannot write file: $vtlib_utils_path</strong></p>");
}

echo "<hr>";
echo "<h2>✅ FIX COMPLETE</h2>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test website: <a href='../index.php' target='_blank'>index.php</a></li>";
echo "<li>If it works, delete this fix file</li>";
echo "<li>If it doesn't work, restore backup: <code>cp $backup_path $vtlib_utils_path</code></li>";
echo "</ol>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong để bảo mật!</p>";
?>

