<?php
/**
 * ENABLE ERRORS AND TEST - See actual errors causing white screen
 * 
 * File này sẽ enable errors và test index.php để xem lỗi thực sự
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/HTMLPURIFIER_FIX/enable_errors_and_test.php
 *   3. Xem errors và fix
 *   4. XÓA file này sau khi fix xong
 */

// Enable ALL errors
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('max_execution_time', '60');

echo "<h1>ENABLE ERRORS AND TEST INDEX.PHP</h1>";
echo "<hr>";

// Step 1: Create index_test.php with errors enabled
echo "<h2>Step 1: Creating index_test.php with errors enabled</h2>";

$index_path = '/home/nhtdbus8/supertestcrm.tdbsolution.com/index.php';
if (!file_exists($index_path)) {
    // Try other paths
    $possible_paths = [
        'index.php',
        '../index.php',
        '../../index.php'
    ];
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $index_path = realpath($path);
            break;
        }
    }
}

if (!file_exists($index_path)) {
    die("<p style='color:red'><strong>✗ index.php NOT FOUND</strong></p>");
}

echo "<p style='color:green'><strong>✅ Found index.php: $index_path</strong></p>";

// Read index.php
$index_content = file_get_contents($index_path);
if ($index_content === false) {
    die("<p style='color:red'><strong>✗ Cannot read index.php</strong></p>");
}

// Create test version with errors enabled
$test_content = "<?php\n";
$test_content .= "// Enable all errors\n";
$test_content .= "error_reporting(E_ALL);\n";
$test_content .= "ini_set('display_errors', '1');\n";
$test_content .= "ini_set('display_startup_errors', '1');\n";
$test_content .= "ini_set('log_errors', '1');\n";
$test_content .= "ini_set('max_execution_time', '60');\n\n";
$test_content .= "// Original index.php content\n";
$test_content .= substr($index_content, 5); // Remove <?php from original

$test_path = dirname($index_path) . '/index_test.php';
if (file_put_contents($test_path, $test_content)) {
    chmod($test_path, 0644);
    echo "<p style='color:green'><strong>✅ Created index_test.php</strong></p>";
    echo "<p>Now test: <a href='../index_test.php' target='_blank'>index_test.php</a></p>";
} else {
    echo "<p style='color:red'><strong>✗ Cannot create index_test.php</strong></p>";
    echo "<p>You can manually add these lines to the top of index.php (after <?php):</p>";
    echo "<pre>";
    echo "error_reporting(E_ALL);\n";
    echo "ini_set('display_errors', '1');\n";
    echo "ini_set('display_startup_errors', '1');\n";
    echo "</pre>";
}

echo "<hr>";

// Step 2: Test loading components
echo "<h2>Step 2: Testing Component Loading</h2>";

// Get root directory
$script_dir = dirname(__FILE__);
$root_dir = dirname($script_dir);

// Find files with multiple paths
$vendor_autoload_paths = [
    '/home/nhtdbus8/supertestcrm.tdbsolution.com/vendor/autoload.php',
    $root_dir . '/vendor/autoload.php',
    dirname($root_dir) . '/vendor/autoload.php',
    'vendor/autoload.php',
    '../vendor/autoload.php',
    '../../vendor/autoload.php'
];

$vendor_autoload = null;
foreach ($vendor_autoload_paths as $path) {
    if (file_exists($path)) {
        $vendor_autoload = $path;
        break;
    }
}

$config_paths = [
    '/home/nhtdbus8/supertestcrm.tdbsolution.com/config.php',
    $root_dir . '/config.php',
    'config.php',
    '../config.php'
];

$config_php = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        $config_php = $path;
        break;
    }
}

$config_inc_paths = [
    '/home/nhtdbus8/supertestcrm.tdbsolution.com/config.inc.php',
    $root_dir . '/config.inc.php',
    'config.inc.php',
    '../config.inc.php'
];

$config_inc = null;
foreach ($config_inc_paths as $path) {
    if (file_exists($path)) {
        $config_inc = $path;
        break;
    }
}

try {
    if (!$vendor_autoload) {
        throw new Exception("vendor/autoload.php NOT FOUND. Tried: " . implode(', ', $vendor_autoload_paths));
    }
    require_once $vendor_autoload;
    echo "<p style='color:green'><strong>✅ vendor/autoload.php loaded: $vendor_autoload</strong></p>";
    
    if ($config_php) {
        include_once $config_php;
        echo "<p style='color:green'><strong>✅ config.php loaded: $config_php</strong></p>";
    } else {
        echo "<p style='color:orange'><strong>⚠️ config.php NOT FOUND</strong></p>";
    }
    
    if ($config_inc) {
        include_once $config_inc;
        echo "<p style='color:green'><strong>✅ config.inc.php loaded: $config_inc</strong></p>";
    } else {
        echo "<p style='color:orange'><strong>⚠️ config.inc.php NOT FOUND</strong></p>";
    }
    
    $relation_paths = [
        $root_dir . '/include/Webservices/Relation.php',
        'include/Webservices/Relation.php',
        '../include/Webservices/Relation.php'
    ];
    $relation_php = null;
    foreach ($relation_paths as $path) {
        if (file_exists($path)) {
            $relation_php = $path;
            break;
        }
    }
    if ($relation_php) {
        include_once $relation_php;
        echo "<p style='color:green'><strong>✅ Relation.php loaded</strong></p>";
    } else {
        echo "<p style='color:orange'><strong>⚠️ Relation.php NOT FOUND</strong></p>";
    }
    
    $module_paths = [
        $root_dir . '/vtlib/Vtiger/Module.php',
        'vtlib/Vtiger/Module.php',
        '../vtlib/Vtiger/Module.php'
    ];
    $module_php = null;
    foreach ($module_paths as $path) {
        if (file_exists($path)) {
            $module_php = $path;
            break;
        }
    }
    if ($module_php) {
        include_once $module_php;
        echo "<p style='color:green'><strong>✅ Module.php loaded</strong></p>";
    } else {
        echo "<p style='color:orange'><strong>⚠️ Module.php NOT FOUND</strong></p>";
    }
    
    $webui_paths = [
        $root_dir . '/includes/main/WebUI.php',
        'includes/main/WebUI.php',
        '../includes/main/WebUI.php'
    ];
    $webui_php = null;
    foreach ($webui_paths as $path) {
        if (file_exists($path)) {
            $webui_php = $path;
            break;
        }
    }
    if ($webui_php) {
        include_once $webui_php;
        echo "<p style='color:green'><strong>✅ WebUI.php loaded</strong></p>";
    } else {
        echo "<p style='color:orange'><strong>⚠️ WebUI.php NOT FOUND</strong></p>";
    }
    
    // Test HTMLPurifier
    if (class_exists('HTMLPurifier_Config')) {
        echo "<p style='color:green'><strong>✅ HTMLPurifier_Config class exists</strong></p>";
    } else {
        echo "<p style='color:red'><strong>✗ HTMLPurifier_Config class NOT found</strong></p>";
        
        // Try to load it
        $htmlpurifier_base = '/home/nhtdbus8/supertestcrm.tdbsolution.com/vendor/ezyang/htmlpurifier/library';
        if (file_exists($htmlpurifier_base . '/HTMLPurifier.includes.php')) {
            require_once $htmlpurifier_base . '/HTMLPurifier.includes.php';
            echo "<p style='color:green'><strong>✅ Loaded via HTMLPurifier.includes.php</strong></p>";
        } elseif (file_exists($htmlpurifier_base . '/HTMLPurifier.autoload.php')) {
            if (file_exists($htmlpurifier_base . '/HTMLPurifier/Bootstrap.php')) {
                require_once $htmlpurifier_base . '/HTMLPurifier/Bootstrap.php';
            }
            require_once $htmlpurifier_base . '/HTMLPurifier.autoload.php';
            echo "<p style='color:green'><strong>✅ Loaded via HTMLPurifier.autoload.php</strong></p>";
        }
        
        if (class_exists('HTMLPurifier_Config')) {
            echo "<p style='color:green'><strong>✅ HTMLPurifier_Config loaded successfully!</strong></p>";
        } else {
            echo "<p style='color:red'><strong>✗ HTMLPurifier_Config still NOT found</strong></p>";
        }
    }
    
    // Test vtlib_purify
    if (function_exists('vtlib_purify')) {
        echo "<p style='color:green'><strong>✅ vtlib_purify() function exists</strong></p>";
        
        // Try to call it
        try {
            $test_result = vtlib_purify('<script>test</script>');
            echo "<p style='color:green'><strong>✅ vtlib_purify() works!</strong></p>";
        } catch (Error $e) {
            echo "<p style='color:red'><strong>✗ ERROR in vtlib_purify():</strong></p>";
            echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        } catch (Exception $e) {
            echo "<p style='color:red'><strong>✗ EXCEPTION in vtlib_purify():</strong></p>";
            echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'><strong>✗ vtlib_purify() function NOT found</strong></p>";
    }
    
    // Test WebUI
    try {
        $webUI = new Vtiger_WebUI();
        echo "<p style='color:green'><strong>✅ Vtiger_WebUI created</strong></p>";
    } catch (Error $e) {
        echo "<p style='color:red'><strong>✗ ERROR creating Vtiger_WebUI:</strong></p>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
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

// Step 3: Check error log
echo "<h2>Step 3: Recent PHP Errors</h2>";
$error_log_paths = [
    '/home/nhtdbus8/supertestcrm.tdbsolution.com/error_log',
    'error_log',
    'php_errors.log',
    ini_get('error_log')
];

foreach ($error_log_paths as $log_path) {
    if ($log_path && file_exists($log_path)) {
        echo "<h3>Error log: $log_path</h3>";
        $errors = file_get_contents($log_path);
        $lines = explode("\n", $errors);
        $recent = array_slice($lines, -30);
        echo "<pre>" . htmlspecialchars(implode("\n", $recent)) . "</pre>";
        break;
    }
}

echo "<hr>";
echo "<h2>✅ TEST COMPLETE</h2>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test <a href='../index_test.php' target='_blank'>index_test.php</a> to see actual errors</li>";
echo "<li>Fix errors based on the output above</li>";
echo "<li>Delete index_test.php and this file after fixing</li>";
echo "</ol>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong!</p>";
?>

