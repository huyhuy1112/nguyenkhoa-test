<?php
/**
 * RESET LOCAL CHANGES - Reset files to match git repository
 * 
 * File này sẽ reset các file về trạng thái từ git để có thể pull code
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/MISSING_FILES_FIX/reset_local_changes.php
 *   3. File sẽ reset các file về trạng thái từ git
 *   4. Sau đó pull code: git pull origin main
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>RESET LOCAL CHANGES</h1>";
echo "<hr>";

$root_dir = '/home/nhtdbus8/supertestcrm.tdbsolution.com';

// Files that have local changes (from error message)
$files_to_reset = [
    'include/PopulateComboValues.php',
    'include/utils/UserInfoUtil.php',
    'include/utils/export.php',
];

echo "<h2>Resetting files to match git repository</h2>";

$reset_count = 0;
$errors = [];

foreach ($files_to_reset as $file) {
    $filepath = $root_dir . '/' . $file;
    
    if (!file_exists($filepath)) {
        echo "<p style='color:orange'><strong>⚠️ File not found: $file</strong></p>";
        continue;
    }
    
    // Check if backup exists
    $backup_file = $filepath . '.backup';
    if (file_exists($backup_file)) {
        // Restore from backup (original state before our fixes)
        $backup_content = file_get_contents($backup_file);
        if ($backup_content !== false) {
            if (file_put_contents($filepath, $backup_content)) {
                chmod($filepath, 0644);
                echo "<p style='color:green'><strong>✅ Reset from backup: $file</strong></p>";
                $reset_count++;
            } else {
                $errors[] = "Cannot write: $file";
            }
        } else {
            $errors[] = "Cannot read backup: $file";
        }
    } else {
        // Try to reset using git (if git is available)
        $git_cmd = "cd $root_dir && git checkout HEAD -- $file 2>&1";
        $output = shell_exec($git_cmd);
        
        if ($output !== null) {
            echo "<p style='color:green'><strong>✅ Reset via git: $file</strong></p>";
            $reset_count++;
        } else {
            echo "<p style='color:orange'><strong>⚠️ No backup found and git not available: $file</strong></p>";
            echo "<p>You may need to manually reset this file or delete it and pull from git.</p>";
        }
    }
}

echo "<hr>";

if (!empty($errors)) {
    echo "<h3 style='color:red'>Errors:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

echo "<h2>✅ RESET COMPLETE</h2>";
echo "<p><strong>Files reset: $reset_count</strong></p>";

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Go to cPanel → Terminal (or SSH)</li>";
echo "<li>Run: <code>cd $root_dir</code></li>";
echo "<li>Run: <code>git pull origin main</code></li>";
echo "<li>If still error, run: <code>git reset --hard origin/main</code> (WARNING: This will discard ALL local changes)</li>";
echo "</ol>";

echo "<hr>";
echo "<h2>Alternative: Manual Reset via cPanel Terminal</h2>";
echo "<p>If the above doesn't work, run these commands in cPanel Terminal:</p>";
echo "<pre>";
echo "cd $root_dir\n";
echo "git stash\n";
echo "git pull origin main\n";
echo "</pre>";
echo "<p>Or to force reset:</p>";
echo "<pre>";
echo "cd $root_dir\n";
echo "git fetch origin\n";
echo "git reset --hard origin/main\n";
echo "</pre>";

echo "<hr>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong!</p>";
?>

