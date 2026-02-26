<?php
/**
 * FIX INCLUDE DIRECTORY PATHS - Fix all include/ paths in include/ directory
 * 
 * File này sẽ sửa tất cả các include path trong thư mục include/
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/MISSING_FILES_FIX/fix_include_directory_paths.php
 *   3. File sẽ tự động sửa tất cả các include path trong include/
 *   4. Test website
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>FIX INCLUDE DIRECTORY PATHS</h1>";
echo "<hr>";

$root_dir = '/home/nhtdbus8/supertestcrm.tdbsolution.com';
$include_dir = $root_dir . '/include';

if (!is_dir($include_dir)) {
    die("<p style='color:red'><strong>✗ include/ directory NOT FOUND</strong></p>");
}

echo "<h2>Processing include/ directory</h2>";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($include_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$total_fixed = 0;
$files_fixed = [];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filepath = $file->getPathname();
        $content = file_get_contents($filepath);
        
        if ($content === false) {
            continue;
        }
        
        $original_content = $content;
        $file_fixed = false;
        
        // Calculate relative depth from include/ root
        $relative_path = str_replace($include_dir . '/', '', $filepath);
        $depth = substr_count($relative_path, '/');
        
        // Build path prefix (../../ for depth 1, ../../../../ for depth 2, etc.)
        $path_prefix = str_repeat('../', $depth);
        
        // Pattern 1: require_once('include/...
        if (preg_match_all("/require_once\(['\"]include\/([^'\"]+)['\"]\)/", $content, $matches)) {
            foreach ($matches[0] as $index => $match) {
                $target_file = $matches[1][$index];
                $replacement = "require_once(dirname(__FILE__) . '/$path_prefix$target_file')";
                $content = str_replace($match, $replacement, $content);
                $file_fixed = true;
            }
        }
        
        // Pattern 2: require_once("include/...
        if (preg_match_all('/require_once\(["\']include\/([^"\']+)["\']\)/', $content, $matches)) {
            foreach ($matches[0] as $index => $match) {
                $target_file = $matches[1][$index];
                $replacement = "require_once(dirname(__FILE__) . '/$path_prefix$target_file')";
                $content = str_replace($match, $replacement, $content);
                $file_fixed = true;
            }
        }
        
        // Pattern 3: include_once('include/...
        if (preg_match_all("/include_once\(['\"]include\/([^'\"]+)['\"]\)/", $content, $matches)) {
            foreach ($matches[0] as $index => $match) {
                $target_file = $matches[1][$index];
                $replacement = "include_once(dirname(__FILE__) . '/$path_prefix$target_file')";
                $content = str_replace($match, $replacement, $content);
                $file_fixed = true;
            }
        }
        
        // Pattern 4: include_once("include/...
        if (preg_match_all('/include_once\(["\']include\/([^"\']+)["\']\)/', $content, $matches)) {
            foreach ($matches[0] as $index => $match) {
                $target_file = $matches[1][$index];
                $replacement = "include_once(dirname(__FILE__) . '/$path_prefix$target_file')";
                $content = str_replace($match, $replacement, $content);
                $file_fixed = true;
            }
        }
        
        if ($file_fixed && $content !== $original_content) {
            // Create backup
            $backup_file = $filepath . '.backup';
            if (!file_exists($backup_file)) {
                file_put_contents($backup_file, $original_content);
            }
            
            // Write fixed file
            if (file_put_contents($filepath, $content)) {
                chmod($filepath, 0644);
                $relative_path_display = str_replace($root_dir . '/', '', $filepath);
                echo "<p style='color:green'><strong>✅ Fixed: $relative_path_display</strong></p>";
                $files_fixed[] = $relative_path_display;
                $total_fixed++;
            }
        }
    }
}

echo "<hr>";
echo "<h2>✅ FIX COMPLETE</h2>";
echo "<p><strong>Total files fixed: $total_fixed</strong></p>";

if (!empty($files_fixed)) {
    echo "<h3>Fixed files (first 20):</h3>";
    echo "<ul>";
    foreach (array_slice($files_fixed, 0, 20) as $file) {
        echo "<li>$file</li>";
    }
    if (count($files_fixed) > 20) {
        echo "<li>... and " . (count($files_fixed) - 20) . " more files</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test website: <a href='../index.php' target='_blank'>index.php</a></li>";
echo "<li>Or test: <a href='../index_test.php' target='_blank'>index_test.php</a></li>";
echo "<li>If it works, delete this file</li>";
echo "</ol>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong!</p>";
?>

