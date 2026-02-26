<?php
/**
 * CHECK MISSING FILES - Check and provide missing files
 * 
 * File này sẽ kiểm tra file nào bị thiếu và cung cấp file đó
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/MISSING_FILES_FIX/check_missing_files.php
 *   3. Xem file nào thiếu và download/upload
 *   4. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>CHECK MISSING FILES</h1>";
echo "<hr>";

$root_dir = '/home/nhtdbus8/supertestcrm.tdbsolution.com';

$required_files = [
    'includes/runtime/Cache.php' => 'Critical - Cache class',
    'includes/runtime/cache/Connector.php' => 'Critical - Cache connector',
    'includes/runtime/cache/Connectors.php' => 'Critical - Cache connectors',
    'includes/runtime/Configs.php' => 'Important - Runtime configs',
];

echo "<h2>Checking Required Files</h2>";

$missing_files = [];
foreach ($required_files as $file => $description) {
    $full_path = $root_dir . '/' . $file;
    if (file_exists($full_path)) {
        echo "<p style='color:green'><strong>✅ $file</strong> - $description</p>";
    } else {
        echo "<p style='color:red'><strong>✗ $file</strong> - $description - MISSING</p>";
        $missing_files[] = $file;
    }
}

echo "<hr>";

if (!empty($missing_files)) {
    echo "<h2 style='color:red'>⚠️ MISSING FILES DETECTED</h2>";
    
    echo "<h3>Solution: Upload files via cPanel File Manager</h3>";
    echo "<ol>";
    echo "<li>Go to cPanel → File Manager</li>";
    echo "<li>Navigate to the directory for each missing file</li>";
    echo "<li>Create the file and paste the content below</li>";
    echo "</ol>";
    
    foreach ($missing_files as $file) {
        echo "<hr>";
        echo "<h3>File: $file</h3>";
        
        // Read file from local if exists
        $local_file = __DIR__ . '/../' . $file;
        if (file_exists($local_file)) {
            $content = file_get_contents($local_file);
            echo "<p><strong>File content:</strong></p>";
            echo "<textarea style='width:100%;height:400px;font-family:monospace;font-size:12px;'>";
            echo htmlspecialchars($content);
            echo "</textarea>";
            echo "<p><strong>Instructions:</strong></p>";
            echo "<ol>";
            echo "<li>Copy the content above</li>";
            echo "<li>Go to cPanel → File Manager</li>";
            echo "<li>Navigate to: <code>" . dirname($file) . "</code></li>";
            echo "<li>Create new file: <code>" . basename($file) . "</code></li>";
            echo "<li>Paste the content and save</li>";
            echo "<li>Set permissions to 644</li>";
            echo "</ol>";
        } else {
            echo "<p style='color:red'><strong>✗ Cannot read local file: $local_file</strong></p>";
            echo "<p>You need to upload this file manually from your local source code.</p>";
        }
    }
} else {
    echo "<h2 style='color:green'>✅ All required files exist!</h2>";
}

echo "<hr>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong!</p>";
?>

