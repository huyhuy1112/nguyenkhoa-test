<?php
/**
 * Debug Script cho Localhost
 * 
 * Chạy file này để xem lỗi cụ thể: http://localhost:8080/debug_localhost.php
 */

// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<h2>🔍 Debug Localhost</h2>";
echo "<hr>";

// 1. Kiểm tra PHP
echo "<h3>1. PHP Version</h3>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>PHP SAPI: " . php_sapi_name() . "</p>";
echo "<hr>";

// 2. Kiểm tra config.inc.php
echo "<h3>2. Kiểm tra config.inc.php</h3>";
if (file_exists('config.inc.php')) {
    echo "<p style='color:green;'>✅ config.inc.php tồn tại</p>";
    
    // Đọc config
    include('config.inc.php');
    
    echo "<p><strong>Database Server:</strong> " . (isset($dbconfig['db_server']) ? $dbconfig['db_server'] : 'NOT SET') . "</p>";
    echo "<p><strong>Database Port:</strong> " . (isset($dbconfig['db_port']) ? $dbconfig['db_port'] : 'NOT SET') . "</p>";
    echo "<p><strong>Database Username:</strong> " . (isset($dbconfig['db_username']) ? $dbconfig['db_username'] : 'NOT SET') . "</p>";
    echo "<p><strong>Database Name:</strong> " . (isset($dbconfig['db_name']) ? $dbconfig['db_name'] : 'NOT SET') . "</p>";
    echo "<p><strong>Site URL:</strong> " . (isset($site_URL) ? $site_URL : 'NOT SET') . "</p>";
    echo "<p><strong>Root Directory:</strong> " . (isset($root_directory) ? $root_directory : 'NOT SET') . "</p>";
} else {
    echo "<p style='color:red;'>❌ config.inc.php KHÔNG tồn tại</p>";
}
echo "<hr>";

// 3. Kiểm tra kết nối database
echo "<h3>3. Kiểm tra kết nối Database</h3>";
if (isset($dbconfig)) {
    $db_host = $dbconfig['db_server'];
    $db_port = str_replace(':', '', $dbconfig['db_port']);
    $db_user = $dbconfig['db_username'];
    $db_pass = $dbconfig['db_password'];
    $db_name = $dbconfig['db_name'];
    
    $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port ?: 3306);
    
    if ($conn) {
        echo "<p style='color:green;'>✅ Kết nối database thành công</p>";
        echo "<p><strong>Database:</strong> $db_name</p>";
        
        // Kiểm tra bảng
        $result = mysqli_query($conn, "SHOW TABLES");
        $table_count = mysqli_num_rows($result);
        echo "<p><strong>Số bảng:</strong> $table_count</p>";
        
        mysqli_close($conn);
    } else {
        echo "<p style='color:red;'>❌ Lỗi kết nối database: " . mysqli_connect_error() . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Không có thông tin database config</p>";
}
echo "<hr>";

// 4. Kiểm tra vendor/autoload.php
echo "<h3>4. Kiểm tra vendor/autoload.php</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color:green;'>✅ vendor/autoload.php tồn tại</p>";
    
    try {
        require_once 'vendor/autoload.php';
        echo "<p style='color:green;'>✅ vendor/autoload.php load thành công</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Lỗi load vendor/autoload.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ vendor/autoload.php KHÔNG tồn tại</p>";
}
echo "<hr>";

// 5. Kiểm tra index.php
echo "<h3>5. Kiểm tra index.php</h3>";
if (file_exists('index.php')) {
    echo "<p style='color:green;'>✅ index.php tồn tại</p>";
    
    // Kiểm tra syntax
    $syntax_check = shell_exec("php -l index.php 2>&1");
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "<p style='color:green;'>✅ index.php không có lỗi syntax</p>";
    } else {
        echo "<p style='color:red;'>❌ index.php có lỗi syntax:</p>";
        echo "<pre>$syntax_check</pre>";
    }
} else {
    echo "<p style='color:red;'>❌ index.php KHÔNG tồn tại</p>";
}
echo "<hr>";

// 6. Test load WebUI
echo "<h3>6. Test load WebUI</h3>";
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
    }
    
    if (file_exists('include/utils/utils.php')) {
        require_once 'include/utils/utils.php';
        echo "<p style='color:green;'>✅ include/utils/utils.php load thành công</p>";
    } else {
        echo "<p style='color:red;'>❌ include/utils/utils.php KHÔNG tồn tại</p>";
    }
    
    if (file_exists('includes/main/WebUI.php')) {
        require_once 'includes/main/WebUI.php';
        echo "<p style='color:green;'>✅ includes/main/WebUI.php load thành công</p>";
    } else {
        echo "<p style='color:red;'>❌ includes/main/WebUI.php KHÔNG tồn tại</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<p style='color:red;'>❌ Fatal Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// 7. Kiểm tra cache
echo "<h3>7. Kiểm tra Cache</h3>";
$cache_dirs = ['cache', 'storage/cache', 'templates_c'];
foreach ($cache_dirs as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? '✅ Writable' : '❌ Not Writable';
        echo "<p>$dir: $writable</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ $dir: Không tồn tại</p>";
    }
}
echo "<hr>";

// 8. Kiểm tra permissions
echo "<h3>8. Kiểm tra Permissions</h3>";
$important_files = ['index.php', 'config.inc.php', 'config.php', 'vendor/autoload.php'];
foreach ($important_files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $readable = is_readable($file) ? '✅' : '❌';
        echo "<p>$file: Permissions = $perms, Readable = $readable</p>";
    }
}
echo "<hr>";

// 9. Test chạy index.php
echo "<h3>9. Test chạy index.php (bắt đầu output buffer)</h3>";
ob_start();
try {
    if (file_exists('index.php')) {
        // Capture output
        include 'index.php';
        $output = ob_get_contents();
        ob_end_clean();
        
        if (empty($output)) {
            echo "<p style='color:red;'>❌ index.php không có output (màn hình trắng)</p>";
            echo "<p>Kiểm tra error log hoặc xem phần lỗi ở trên</p>";
        } else {
            echo "<p style='color:green;'>✅ index.php có output</p>";
            echo "<p><strong>Output length:</strong> " . strlen($output) . " bytes</p>";
            echo "<p><strong>First 500 chars:</strong></p>";
            echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
        }
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color:red;'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
} catch (Error $e) {
    ob_end_clean();
    echo "<p style='color:red;'>❌ Fatal Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

echo "<h3>✅ Debug Hoàn Tất</h3>";
echo "<p>Xem các lỗi ở trên để biết vấn đề cụ thể</p>";
echo "<p style='color:red;'><strong>⚠️ XÓA FILE NÀY sau khi fix xong!</strong></p>";

