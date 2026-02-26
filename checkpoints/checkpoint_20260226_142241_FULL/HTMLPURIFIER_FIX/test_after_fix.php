<?php
/**
 * TEST AFTER FIX - Check if HTMLPurifier fix works
 * 
 * File này sẽ test xem fix có hoạt động không
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/HTMLPURIFIER_FIX/test_after_fix.php
 *   3. Xem kết quả
 *   4. XÓA file này sau khi test xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<h1>TEST HTMLPURIFIER FIX</h1>";
echo "<hr>";

// Step 1: Check if VtlibUtils.php exists and has fix
echo "<h2>Step 1: Check VtlibUtils.php</h2>";

// Get root directory
$script_dir = dirname(__FILE__);
$root_dir = dirname($script_dir);

// Try multiple paths
$possible_paths = [
    '/home/nhtdbus8/supertestcrm.tdbsolution.com/include/utils/VtlibUtils.php',
    $root_dir . '/include/utils/VtlibUtils.php',
    dirname($root_dir) . '/include/utils/VtlibUtils.php',
    'include/utils/VtlibUtils.php',
    '../include/utils/VtlibUtils.php',
    '../../include/utils/VtlibUtils.php'
];

$vtlib_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $vtlib_path = $path;
        break;
    }
}

if (!$vtlib_path) {
    echo "<p style='color:red'><strong>✗ VtlibUtils.php NOT FOUND</strong></p>";
    echo "<p><strong>Tried paths:</strong></p>";
    echo "<ul>";
    foreach ($possible_paths as $path) {
        $exists = file_exists($path) ? "✅ EXISTS" : "✗ NOT FOUND";
        echo "<li>$path - $exists</li>";
    }
    echo "</ul>";
    die();
}

echo "<p style='color:green'><strong>✅ VtlibUtils.php found: $vtlib_path</strong></p>";

$content = file_get_contents($vtlib_path);
if (strpos($content, '// FIX: Ensure HTMLPurifier is autoloaded') !== false) {
    echo "<p style='color:green'><strong>✅ Fix code found in VtlibUtils.php</strong></p>";
} else {
    echo "<p style='color:red'><strong>✗ Fix code NOT found in VtlibUtils.php</strong></p>";
    echo "<p>You may need to run: <a href='apply_fix_manually.php'>apply_fix_manually.php</a></p>";
}

echo "<hr>";

// Step 2: Check HTMLPurifier files
echo "<h2>Step 2: Check HTMLPurifier Files</h2>";

// Try multiple paths for HTMLPurifier
$htmlpurifier_paths = [
    '/home/nhtdbus8/supertestcrm.tdbsolution.com/vendor/ezyang/htmlpurifier/library',
    $root_dir . '/vendor/ezyang/htmlpurifier/library',
    dirname($root_dir) . '/vendor/ezyang/htmlpurifier/library',
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
    echo "<p style='color:red'><strong>✗ HTMLPurifier NOT FOUND</strong></p>";
    echo "<p><strong>Tried paths:</strong></p>";
    echo "<ul>";
    foreach ($htmlpurifier_paths as $path) {
        $exists = file_exists($path . '/HTMLPurifier.php') ? "✅ EXISTS" : "✗ NOT FOUND";
        echo "<li>$path - $exists</li>";
    }
    echo "</ul>";
    die();
}

echo "<p style='color:green'><strong>✅ HTMLPurifier found: $htmlpurifier_base</strong></p>";
$files_to_check = [
    'HTMLPurifier.php',
    'HTMLPurifier/Config.php',
    'HTMLPurifier/Bootstrap.php',
    'HTMLPurifier.autoload.php',
    'HTMLPurifier.auto.php'
];

foreach ($files_to_check as $file) {
    $path = $htmlpurifier_base . '/' . $file;
    if (file_exists($path)) {
        echo "<p style='color:green'><strong>✅ $file exists</strong></p>";
    } else {
        echo "<p style='color:orange'><strong>⚠️ $file NOT found</strong></p>";
    }
}

echo "<hr>";

// Step 3: Test loading HTMLPurifier
echo "<h2>Step 3: Test Loading HTMLPurifier</h2>";

// Try autoload first
if (file_exists($htmlpurifier_base . '/HTMLPurifier.autoload.php')) {
    try {
        require_once $htmlpurifier_base . '/HTMLPurifier.autoload.php';
        echo "<p style='color:green'><strong>✅ HTMLPurifier.autoload.php loaded</strong></p>";
    } catch (Exception $e) {
        echo "<p style='color:red'><strong>✗ Error loading autoload: " . $e->getMessage() . "</strong></p>";
    }
} elseif (file_exists($htmlpurifier_base . '/HTMLPurifier.auto.php')) {
    try {
        require_once $htmlpurifier_base . '/HTMLPurifier.auto.php';
        echo "<p style='color:green'><strong>✅ HTMLPurifier.auto.php loaded</strong></p>";
    } catch (Exception $e) {
        echo "<p style='color:red'><strong>✗ Error loading auto: " . $e->getMessage() . "</strong></p>";
    }
} else {
    // Load manually
    try {
        require_once $htmlpurifier_base . '/HTMLPurifier.php';
        require_once $htmlpurifier_base . '/HTMLPurifier/Config.php';
        require_once $htmlpurifier_base . '/HTMLPurifier/Bootstrap.php';
        echo "<p style='color:green'><strong>✅ HTMLPurifier loaded manually</strong></p>";
    } catch (Exception $e) {
        echo "<p style='color:red'><strong>✗ Error loading manually: " . $e->getMessage() . "</strong></p>";
    }
}

// Check if class exists
if (class_exists('HTMLPurifier_Config')) {
    echo "<p style='color:green'><strong>✅ HTMLPurifier_Config class exists</strong></p>";
} else {
    echo "<p style='color:red'><strong>✗ HTMLPurifier_Config class NOT found</strong></p>";
}

if (class_exists('HTMLPurifier')) {
    echo "<p style='color:green'><strong>✅ HTMLPurifier class exists</strong></p>";
} else {
    echo "<p style='color:red'><strong>✗ HTMLPurifier class NOT found</strong></p>";
}

echo "<hr>";

// Step 4: Test vtlib_purify function
echo "<h2>Step 4: Test vtlib_purify() Function</h2>";

// Load required files
if (file_exists('config.php')) {
    include_once 'config.php';
    echo "<p style='color:green'><strong>✅ config.php loaded</strong></p>";
}

if (file_exists('config.inc.php')) {
    include_once 'config.inc.php';
    echo "<p style='color:green'><strong>✅ config.inc.php loaded</strong></p>";
}

// Use the path we found earlier
if ($vtlib_path && file_exists($vtlib_path)) {
    require_once $vtlib_path;
    echo "<p style='color:green'><strong>✅ VtlibUtils.php loaded</strong></p>";
} else {
    die("<p style='color:red'><strong>✗ Cannot load VtlibUtils.php</strong></p>");
}

// Test function
try {
    $test_input = '<script>alert("test")</script>';
    $result = vtlib_purify($test_input);
    echo "<p style='color:green'><strong>✅ vtlib_purify() works!</strong></p>";
    echo "<p><strong>Input:</strong> " . htmlspecialchars($test_input) . "</p>";
    echo "<p><strong>Output:</strong> " . htmlspecialchars($result) . "</p>";
} catch (Error $e) {
    echo "<p style='color:red'><strong>✗ FATAL ERROR in vtlib_purify():</strong></p>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'><strong>✗ EXCEPTION in vtlib_purify():</strong></p>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";

// Step 5: Test loading index.php components
echo "<h2>Step 5: Test Loading index.php Components</h2>";

try {
    if (!file_exists("vendor/autoload.php")) {
        throw new Exception("vendor/autoload.php NOT FOUND");
    }
    require_once 'vendor/autoload.php';
    echo "<p style='color:green'><strong>✅ vendor/autoload.php loaded</strong></p>";
    
    include_once 'include/Webservices/Relation.php';
    echo "<p style='color:green'><strong>✅ Relation.php loaded</strong></p>";
    
    include_once 'vtlib/Vtiger/Module.php';
    echo "<p style='color:green'><strong>✅ Module.php loaded</strong></p>";
    
    include_once 'includes/main/WebUI.php';
    echo "<p style='color:green'><strong>✅ WebUI.php loaded</strong></p>";
    
    echo "<p style='color:green'><strong>✅ All components loaded successfully!</strong></p>";
    
} catch (Error $e) {
    echo "<p style='color:red'><strong>✗ FATAL ERROR:</strong></p>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'><strong>✗ EXCEPTION:</strong></p>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<h2>✅ TEST COMPLETE</h2>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi test xong!</p>";
?>

