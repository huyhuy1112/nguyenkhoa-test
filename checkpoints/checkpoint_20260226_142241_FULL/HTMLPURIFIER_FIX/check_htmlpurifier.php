<?php
/**
 * CHECK HTMLPURIFIER - Check if HTMLPurifier is properly installed
 * 
 * File này sẽ kiểm tra xem HTMLPurifier có đầy đủ không
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/HTMLPURIFIER_FIX/check_htmlpurifier.php
 *   3. Xem kết quả và fix theo hướng dẫn
 *   4. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>CHECK HTMLPURIFIER INSTALLATION</h1>";
echo "<hr>";

$htmlpurifier_base = '/home/nhtdbus8/supertestcrm.tdbsolution.com/vendor/ezyang/htmlpurifier/library';

echo "<h2>Step 1: Check Required Files</h2>";

$required_files = [
    'HTMLPurifier.php' => 'Main class',
    'HTMLPurifier/Config.php' => 'Config class (REQUIRED)',
    'HTMLPurifier/Bootstrap.php' => 'Bootstrap class',
    'HTMLPurifier.autoload.php' => 'Autoload file',
    'HTMLPurifier.auto.php' => 'Auto file'
];

$missing_files = [];
foreach ($required_files as $file => $description) {
    $path = $htmlpurifier_base . '/' . $file;
    if (file_exists($path)) {
        echo "<p style='color:green'><strong>✅ $file</strong> - $description</p>";
    } else {
        echo "<p style='color:red'><strong>✗ $file</strong> - $description - NOT FOUND</p>";
        $missing_files[] = $file;
    }
}

echo "<hr>";

if (!empty($missing_files)) {
    echo "<h2 style='color:red'>⚠️ MISSING FILES DETECTED</h2>";
    echo "<p><strong>Missing files:</strong></p>";
    echo "<ul>";
    foreach ($missing_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
    
    echo "<h3>SOLUTION:</h3>";
    echo "<p><strong>Option 1: Reinstall via Composer (Recommended)</strong></p>";
    echo "<pre>";
    echo "cd /home/nhtdbus8/supertestcrm.tdbsolution.com\n";
    echo "composer install --no-dev\n";
    echo "</pre>";
    
    echo "<p><strong>Option 2: Check if vendor/ directory is complete</strong></p>";
    echo "<pre>";
    echo "ls -la vendor/ezyang/htmlpurifier/library/HTMLPurifier/\n";
    echo "</pre>";
    
    echo "<p><strong>Option 3: Manually download missing files</strong></p>";
    echo "<p>If Config.php is missing, you may need to reinstall HTMLPurifier completely.</p>";
    
    die();
}

echo "<h2>Step 2: Check Directory Structure</h2>";
$htmlpurifier_dir = $htmlpurifier_base . '/HTMLPurifier';
if (is_dir($htmlpurifier_dir)) {
    echo "<p style='color:green'><strong>✅ HTMLPurifier/ directory exists</strong></p>";
    
    $files = scandir($htmlpurifier_dir);
    $php_files = array_filter($files, function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'php';
    });
    
    echo "<p><strong>Files in HTMLPurifier/ directory:</strong></p>";
    echo "<ul>";
    foreach ($php_files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
    
    if (!in_array('Config.php', $php_files)) {
        echo "<p style='color:red'><strong>✗ Config.php NOT in directory!</strong></p>";
    }
} else {
    echo "<p style='color:red'><strong>✗ HTMLPurifier/ directory NOT FOUND</strong></p>";
}

echo "<hr>";

echo "<h2>Step 3: Test Loading Order</h2>";

// Try loading in correct order
try {
    // Load Bootstrap first
    if (file_exists($htmlpurifier_base . '/HTMLPurifier/Bootstrap.php')) {
        require_once $htmlpurifier_base . '/HTMLPurifier/Bootstrap.php';
        echo "<p style='color:green'><strong>✅ Bootstrap.php loaded</strong></p>";
    }
    
    // Then load main class
    if (file_exists($htmlpurifier_base . '/HTMLPurifier.php')) {
        require_once $htmlpurifier_base . '/HTMLPurifier.php';
        echo "<p style='color:green'><strong>✅ HTMLPurifier.php loaded</strong></p>";
    }
    
    // Then load Config
    if (file_exists($htmlpurifier_base . '/HTMLPurifier/Config.php')) {
        require_once $htmlpurifier_base . '/HTMLPurifier/Config.php';
        echo "<p style='color:green'><strong>✅ Config.php loaded</strong></p>";
    } else {
        throw new Exception("Config.php NOT FOUND - HTMLPurifier installation is incomplete!");
    }
    
    // Check classes
    if (class_exists('HTMLPurifier_Bootstrap')) {
        echo "<p style='color:green'><strong>✅ HTMLPurifier_Bootstrap class exists</strong></p>";
    } else {
        echo "<p style='color:red'><strong>✗ HTMLPurifier_Bootstrap class NOT found</strong></p>";
    }
    
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
    
} catch (Exception $e) {
    echo "<p style='color:red'><strong>✗ ERROR: " . $e->getMessage() . "</strong></p>";
}

echo "<hr>";
echo "<h2>✅ CHECK COMPLETE</h2>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi check xong!</p>";
?>

