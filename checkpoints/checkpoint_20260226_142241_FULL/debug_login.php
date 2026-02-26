<?php
/**
 * Debug Script cho Login Issue
 * 
 * Chạy: http://localhost:8080/debug_login.php
 */

// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<h2>🔍 Debug Login Issue</h2>";
echo "<hr>";

// 1. Kiểm tra session
echo "<h3>1. Kiểm tra Session</h3>";
session_start();
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Active" : "❌ Not Active") . "</p>";
echo "<p><strong>Session Data:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "<hr>";

// 2. Kiểm tra config
echo "<h3>2. Kiểm tra Config</h3>";
if (file_exists('config.inc.php')) {
    include('config.inc.php');
    echo "<p style='color:green;'>✅ config.inc.php loaded</p>";
    echo "<p><strong>Database:</strong> " . (isset($dbconfig['db_name']) ? $dbconfig['db_name'] : 'NOT SET') . "</p>";
    echo "<p><strong>Site URL:</strong> " . (isset($site_URL) ? $site_URL : 'NOT SET') . "</p>";
} else {
    echo "<p style='color:red;'>❌ config.inc.php NOT FOUND</p>";
}
echo "<hr>";

// 3. Kiểm tra database connection
echo "<h3>3. Kiểm tra Database Connection</h3>";
if (isset($dbconfig)) {
    $conn = @mysqli_connect(
        $dbconfig['db_server'], 
        $dbconfig['db_username'], 
        $dbconfig['db_password'], 
        $dbconfig['db_name'],
        str_replace(':', '', $dbconfig['db_port']) ?: 3306
    );
    
    if ($conn) {
        echo "<p style='color:green;'>✅ Database connected</p>";
        
        // Kiểm tra bảng users
        $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM vtiger_users");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "<p><strong>Users in database:</strong> " . $row['cnt'] . "</p>";
        }
        
        mysqli_close($conn);
    } else {
        echo "<p style='color:red;'>❌ Database connection failed: " . mysqli_connect_error() . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Database config not found</p>";
}
echo "<hr>";

// 4. Test load Vtiger
echo "<h3>4. Test Load Vtiger Components</h3>";
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        echo "<p style='color:green;'>✅ vendor/autoload.php loaded</p>";
    }
    
    if (file_exists('include/utils/utils.php')) {
        require_once 'include/utils/utils.php';
        echo "<p style='color:green;'>✅ utils.php loaded</p>";
    }
    
    if (file_exists('includes/main/WebUI.php')) {
        require_once 'includes/main/WebUI.php';
        echo "<p style='color:green;'>✅ WebUI.php loaded</p>";
    }
    
    // Test WebUI instance
    $webUI = new Vtiger_WebUI();
    echo "<p style='color:green;'>✅ WebUI instance created</p>";
    
    // Check isInstalled
    $isInstalled = $webUI->isInstalled();
    echo "<p><strong>Is Installed:</strong> " . ($isInstalled ? "✅ Yes" : "❌ No") . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p style='color:red;'>❌ Fatal Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// 5. Kiểm tra login process
echo "<h3>5. Test Login Process</h3>";
if (isset($_POST['username']) && isset($_POST['password'])) {
    echo "<p>🔐 Testing login...</p>";
    
    try {
        // Simulate login
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        echo "<p><strong>Username:</strong> $username</p>";
        echo "<p><strong>Password:</strong> " . str_repeat('*', strlen($password)) . "</p>";
        
        // Check user in database
        if (isset($conn) || isset($dbconfig)) {
            $conn = mysqli_connect(
                $dbconfig['db_server'], 
                $dbconfig['db_username'], 
                $dbconfig['db_password'], 
                $dbconfig['db_name'],
                str_replace(':', '', $dbconfig['db_port']) ?: 3306
            );
            
            if ($conn) {
                $stmt = mysqli_prepare($conn, "SELECT id, user_name, user_password FROM vtiger_users WHERE user_name = ? AND status = 'Active'");
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    echo "<p style='color:green;'>✅ User found in database</p>";
                    echo "<p><strong>User ID:</strong> " . $row['id'] . "</p>";
                    
                    // Check password (Vtiger uses MD5)
                    $hashed_password = md5($password);
                    if ($row['user_password'] === $hashed_password) {
                        echo "<p style='color:green;'>✅ Password correct</p>";
                    } else {
                        echo "<p style='color:red;'>❌ Password incorrect</p>";
                    }
                } else {
                    echo "<p style='color:red;'>❌ User not found or inactive</p>";
                }
                
                mysqli_close($conn);
            }
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>📝 Enter credentials to test login:</p>";
    echo "<form method='POST'>";
    echo "<p>Username: <input type='text' name='username' value='admin'></p>";
    echo "<p>Password: <input type='password' name='password'></p>";
    echo "<p><button type='submit'>Test Login</button></p>";
    echo "</form>";
}
echo "<hr>";

// 6. Kiểm tra output buffering
echo "<h3>6. Kiểm tra Output Buffering</h3>";
echo "<p><strong>Output Buffering Level:</strong> " . ob_get_level() . "</p>";
echo "<p><strong>Output Buffering Status:</strong> " . (ob_get_level() > 0 ? "✅ Active" : "❌ Not Active") . "</p>";
echo "<hr>";

// 7. Kiểm tra PHP errors
echo "<h3>7. Kiểm tra PHP Errors</h3>";
$error_log = ini_get('error_log');
echo "<p><strong>Error Log:</strong> " . ($error_log ? $error_log : 'Default') . "</p>";
if (file_exists('error_log')) {
    echo "<p><strong>Local error_log exists:</strong> ✅</p>";
    echo "<p><strong>Last 5 errors:</strong></p>";
    echo "<pre>" . htmlspecialchars(shell_exec("tail -5 error_log 2>&1")) . "</pre>";
} else {
    echo "<p><strong>Local error_log:</strong> ❌ Not found</p>";
}
echo "<hr>";

echo "<h3>✅ Debug Complete</h3>";
echo "<p>Xem các thông tin ở trên để biết vấn đề cụ thể</p>";
echo "<p style='color:red;'><strong>⚠️ XÓA FILE NÀY sau khi fix xong!</strong></p>";

