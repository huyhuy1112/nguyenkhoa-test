<?php
/**
 * APPLY FIX MANUALLY - Direct fix application
 * 
 * File này sẽ apply fix trực tiếp vào VtlibUtils.php
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/HTMLPURIFIER_FIX/apply_fix_manually.php
 *   3. File sẽ apply fix
 *   4. Test website
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>APPLY HTMLPURIFIER FIX MANUALLY</h1>";
echo "<hr>";

$vtlib_path = '/home/nhtdbus8/supertestcrm.tdbsolution.com/include/utils/VtlibUtils.php';
$htmlpurifier_base = '/home/nhtdbus8/supertestcrm.tdbsolution.com/vendor/ezyang/htmlpurifier/library';

// Check files
if (!file_exists($vtlib_path)) {
    die("<p style='color:red'><strong>✗ VtlibUtils.php NOT FOUND: $vtlib_path</strong></p>");
}

if (!file_exists($htmlpurifier_base . '/HTMLPurifier.php')) {
    die("<p style='color:red'><strong>✗ HTMLPurifier NOT FOUND: $htmlpurifier_base</strong></p>");
}

echo "<p style='color:green'><strong>✅ Files found</strong></p>";

// Read file
$content = file_get_contents($vtlib_path);
if ($content === false) {
    die("<p style='color:red'><strong>✗ Cannot read file</strong></p>");
}

// Check if already fixed
if (strpos($content, '// FIX: Ensure HTMLPurifier is autoloaded') !== false) {
    echo "<p style='color:orange'><strong>⚠️ Fix already applied!</strong></p>";
    echo "<p>If it's not working, the fix may need adjustment.</p>";
    exit;
}

// Create backup
$backup_path = dirname($vtlib_path) . '/VtlibUtils.php.backup';
if (!file_exists($backup_path)) {
    file_put_contents($backup_path, $content);
    chmod($backup_path, 0644);
    echo "<p style='color:green'><strong>✅ Backup created</strong></p>";
}

// Find function and insertion point
$function_pattern = '/function\s+vtlib_purify\s*\([^)]*\)\s*\{/';
if (!preg_match($function_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
    die("<p style='color:red'><strong>✗ Cannot find function vtlib_purify()</strong></p>");
}

$function_start = $matches[0][1];
$function_start_pos = $function_start + strlen($matches[0][0]);

// Find after "global" statements, before "static"
$insert_pos = $function_start_pos;
$next_200 = substr($content, $insert_pos, 200);
$static_pos = strpos($next_200, "\n    static");
if ($static_pos !== false) {
    $insert_pos += $static_pos + 1;
} else {
    // Find first newline after function
    $newline_pos = strpos($content, "\n", $function_start_pos);
    if ($newline_pos !== false) {
        $insert_pos = $newline_pos + 1;
    }
}

// Prepare fix code
$fix_code = "\n    // FIX: Ensure HTMLPurifier is autoloaded\n";
$fix_code .= "    if (!class_exists('HTMLPurifier_Config')) {\n";
$fix_code .= "        // Try multiple paths\n";
$fix_code .= "        \$htmlpurifier_paths = [\n";
$fix_code .= "            '" . addslashes($htmlpurifier_base) . "',\n";
$fix_code .= "            \$use_root_directory . '/vendor/ezyang/htmlpurifier/library',\n";
$fix_code .= "            dirname(__FILE__) . '/../../vendor/ezyang/htmlpurifier/library',\n";
$fix_code .= "            'vendor/ezyang/htmlpurifier/library'\n";
$fix_code .= "        ];\n";
$fix_code .= "        \n";
$fix_code .= "        \$htmlpurifier_base_found = null;\n";
$fix_code .= "        foreach (\$htmlpurifier_paths as \$path) {\n";
$fix_code .= "            if (file_exists(\$path . '/HTMLPurifier.php')) {\n";
$fix_code .= "                \$htmlpurifier_base_found = \$path;\n";
$fix_code .= "                break;\n";
$fix_code .= "            }\n";
$fix_code .= "        }\n";
$fix_code .= "        \n";
$fix_code .= "        if (\$htmlpurifier_base_found) {\n";
$fix_code .= "            if (file_exists(\$htmlpurifier_base_found . '/HTMLPurifier.autoload.php')) {\n";
$fix_code .= "                require_once \$htmlpurifier_base_found . '/HTMLPurifier.autoload.php';\n";
$fix_code .= "            } elseif (file_exists(\$htmlpurifier_base_found . '/HTMLPurifier.auto.php')) {\n";
$fix_code .= "                require_once \$htmlpurifier_base_found . '/HTMLPurifier.auto.php';\n";
$fix_code .= "            } else {\n";
$fix_code .= "                require_once \$htmlpurifier_base_found . '/HTMLPurifier.php';\n";
$fix_code .= "                require_once \$htmlpurifier_base_found . '/HTMLPurifier/Config.php';\n";
$fix_code .= "                require_once \$htmlpurifier_base_found . '/HTMLPurifier/Bootstrap.php';\n";
$fix_code .= "            }\n";
$fix_code .= "        } else {\n";
$fix_code .= "            // Last resort: Composer autoload\n";
$fix_code .= "            if (file_exists('vendor/autoload.php')) {\n";
$fix_code .= "                require_once 'vendor/autoload.php';\n";
$fix_code .= "            }\n";
$fix_code .= "        }\n";
$fix_code .= "        \n";
$fix_code .= "        if (!class_exists('HTMLPurifier_Config')) {\n";
$fix_code .= "            throw new Exception('HTMLPurifier_Config not found. Checked: ' . implode(', ', \$htmlpurifier_paths));\n";
$fix_code .= "        }\n";
$fix_code .= "    }\n";

// Insert fix
$new_content = substr_replace($content, $fix_code, $insert_pos, 0);

// Write file
if (file_put_contents($vtlib_path, $new_content)) {
    chmod($vtlib_path, 0644);
    echo "<p style='color:green'><strong>✅ Fix applied successfully!</strong></p>";
    echo "<p>Fix inserted at position: $insert_pos</p>";
} else {
    die("<p style='color:red'><strong>✗ Cannot write file</strong></p>");
}

echo "<hr>";
echo "<h2>✅ FIX APPLIED</h2>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test website: <a href='../index.php' target='_blank'>index.php</a></li>";
echo "<li>Or test: <a href='test_after_fix.php' target='_blank'>test_after_fix.php</a></li>";
echo "<li>If it works, delete this file</li>";
echo "</ol>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong!</p>";
?>

