<?php
/**
 * FIX HTMLPURIFIER FINAL - Ultimate fix with absolute path detection
 * 
 * File này sẽ fix lỗi HTMLPurifier bằng cách tìm đường dẫn tuyệt đối
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/HTMLPURIFIER_FIX/fix_htmlpurifier_final.php
 *   3. File sẽ tự động fix
 *   4. Test website
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>FIX HTMLPURIFIER FINAL</h1>";
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

echo "<p><strong>Found VtlibUtils.php:</strong> $vtlib_utils_path</p>";

// Find HTMLPurifier
$htmlpurifier_paths = [
    $root_dir . '/vendor/ezyang/htmlpurifier/library',
    dirname($root_dir) . '/vendor/ezyang/htmlpurifier/library',
    dirname($vtlib_utils_path) . '/../../vendor/ezyang/htmlpurifier/library',
    'vendor/ezyang/htmlpurifier/library',
    '../vendor/ezyang/htmlpurifier/library',
    '../../vendor/ezyang/htmlpurifier/library'
];

$htmlpurifier_base = null;
foreach ($htmlpurifier_paths as $path) {
    if (file_exists($path . '/HTMLPurifier.php')) {
        $htmlpurifier_base = $path;
        break;
    }
}

if (!$htmlpurifier_base) {
    echo "<p style='color:red'><strong>✗ HTMLPurifier NOT FOUND!</strong></p>";
    echo "<p><strong>Tried paths:</strong></p>";
    echo "<ul>";
    foreach ($htmlpurifier_paths as $path) {
        $exists = file_exists($path . '/HTMLPurifier.php') ? "✅ EXISTS" : "✗ NOT FOUND";
        echo "<li>$path - $exists</li>";
    }
    echo "</ul>";
    echo "<p><strong>You may need to run:</strong> <code>composer install</code></p>";
    die();
}

echo "<p style='color:green'><strong>✅ HTMLPurifier found: $htmlpurifier_base</strong></p>";

// Convert to absolute path
$htmlpurifier_base_absolute = realpath($htmlpurifier_base);
if ($htmlpurifier_base_absolute) {
    $htmlpurifier_base = $htmlpurifier_base_absolute;
}

echo "<p><strong>Absolute path:</strong> $htmlpurifier_base</p>";

// Calculate relative path from VtlibUtils.php to HTMLPurifier
$vtlib_dir = dirname($vtlib_utils_path);
$relative_path = str_replace(realpath($vtlib_dir . '/../..'), '', $htmlpurifier_base);
if ($relative_path && $relative_path[0] !== '/') {
    $relative_path = '/' . $relative_path;
}

echo "<hr>";

// Read VtlibUtils.php
$content = file_get_contents($vtlib_utils_path);
if ($content === false) {
    die("<p style='color:red'><strong>✗ Cannot read file</strong></p>");
}

// Remove old fix if exists
$content = preg_replace('/\s*\/\/ FIX: Ensure HTMLPurifier is autoloaded.*?}\s*/s', '', $content);

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
    $next_line = strpos($content, "\n", $function_start_pos);
    if ($next_line !== false) {
        $insert_pos = $next_line + 1;
    }
}

// Prepare fix code with absolute path
$fix_code = "\n    // FIX: Ensure HTMLPurifier is autoloaded\n";
$fix_code .= "    if (!class_exists('HTMLPurifier_Config')) {\n";
$fix_code .= "        // Try multiple paths to find HTMLPurifier\n";
$fix_code .= "        \$htmlpurifier_paths = [\n";
$fix_code .= "            \$use_root_directory . '/vendor/ezyang/htmlpurifier/library',\n";
$fix_code .= "            dirname(__FILE__) . '/../../vendor/ezyang/htmlpurifier/library',\n";
$fix_code .= "            '" . addslashes($htmlpurifier_base) . "',\n";
$fix_code .= "            '" . addslashes($htmlpurifier_base_absolute) . "',\n";
$fix_code .= "            'vendor/ezyang/htmlpurifier/library',\n";
$fix_code .= "            '../vendor/ezyang/htmlpurifier/library',\n";
$fix_code .= "            '../../vendor/ezyang/htmlpurifier/library'\n";
$fix_code .= "        ];\n";
$fix_code .= "        \n";
$fix_code .= "        \$htmlpurifier_base = null;\n";
$fix_code .= "        foreach (\$htmlpurifier_paths as \$path) {\n";
$fix_code .= "            if (file_exists(\$path . '/HTMLPurifier.php')) {\n";
$fix_code .= "                \$htmlpurifier_base = \$path;\n";
$fix_code .= "                break;\n";
$fix_code .= "            }\n";
$fix_code .= "        }\n";
$fix_code .= "        \n";
$fix_code .= "        if (\$htmlpurifier_base) {\n";
$fix_code .= "            // Try autoload file first\n";
$fix_code .= "            if (file_exists(\$htmlpurifier_base . '/HTMLPurifier.autoload.php')) {\n";
$fix_code .= "                require_once \$htmlpurifier_base . '/HTMLPurifier.autoload.php';\n";
$fix_code .= "            } elseif (file_exists(\$htmlpurifier_base . '/HTMLPurifier.auto.php')) {\n";
$fix_code .= "                require_once \$htmlpurifier_base . '/HTMLPurifier.auto.php';\n";
$fix_code .= "            } elseif (file_exists(\$htmlpurifier_base . '/HTMLPurifier.php')) {\n";
$fix_code .= "                // Load manually\n";
$fix_code .= "                require_once \$htmlpurifier_base . '/HTMLPurifier.php';\n";
$fix_code .= "                require_once \$htmlpurifier_base . '/HTMLPurifier/Config.php';\n";
$fix_code .= "                require_once \$htmlpurifier_base . '/HTMLPurifier/Bootstrap.php';\n";
$fix_code .= "            }\n";
$fix_code .= "        }\n";
$fix_code .= "        \n";
$fix_code .= "        // Final check\n";
$fix_code .= "        if (!class_exists('HTMLPurifier_Config')) {\n";
$fix_code .= "            // Last resort: try to load via Composer autoload\n";
$fix_code .= "            if (file_exists('vendor/autoload.php')) {\n";
$fix_code .= "                require_once 'vendor/autoload.php';\n";
$fix_code .= "            } elseif (file_exists(\$use_root_directory . '/vendor/autoload.php')) {\n";
$fix_code .= "                require_once \$use_root_directory . '/vendor/autoload.php';\n";
$fix_code .= "            }\n";
$fix_code .= "            \n";
$fix_code .= "            if (!class_exists('HTMLPurifier_Config')) {\n";
$fix_code .= "                throw new Exception('HTMLPurifier_Config class not found. HTMLPurifier base: ' . (\$htmlpurifier_base ?: 'NOT FOUND'));\n";
$fix_code .= "            }\n";
$fix_code .= "        }\n";
$fix_code .= "    }\n";

// Insert fix code
$new_content = substr_replace($content, $fix_code, $insert_pos, 0);

// Create backup
$backup_path = dirname($vtlib_utils_path) . '/VtlibUtils.php.backup';
if (!file_exists($backup_path)) {
    file_put_contents($backup_path, $content);
    chmod($backup_path, 0644);
    echo "<p style='color:green'><strong>✅ Backup created</strong></p>";
}

// Write fixed file
if (file_put_contents($vtlib_utils_path, $new_content)) {
    chmod($vtlib_utils_path, 0644);
    echo "<p style='color:green'><strong>✅ Fix applied successfully</strong></p>";
} else {
    die("<p style='color:red'><strong>✗ Cannot write file</strong></p>");
}

echo "<hr>";
echo "<h2>✅ FIX COMPLETE</h2>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test website: <a href='../index.php' target='_blank'>index.php</a></li>";
echo "<li>If it works, delete this fix file</li>";
echo "</ol>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong để bảo mật!</p>";
?>

