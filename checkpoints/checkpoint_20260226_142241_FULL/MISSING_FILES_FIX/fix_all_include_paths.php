<?php
/**
 * FIX ALL INCLUDE PATHS - Fix all relative include paths in project
 * 
 * File này sẽ sửa tất cả các include path tương đối trong project
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/MISSING_FILES_FIX/fix_all_include_paths.php
 *   3. File sẽ tự động sửa tất cả các include path
 *   4. Test website
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>FIX ALL INCLUDE PATHS</h1>";
echo "<hr>";

$root_dir = '/home/nhtdbus8/supertestcrm.tdbsolution.com';

// Directories to fix
$directories = [
    $root_dir . '/includes',
    $root_dir . '/vtlib',
    $root_dir . '/include',
];

$patterns = [
    // Pattern 1: require_once 'include/...
    [
        'pattern' => "/require_once\s+['\"]include\/([^'\"]+)['\"]/",
        'replacement' => "require_once dirname(__FILE__) . '/../../include/$1'",
        'description' => "include/ paths"
    ],
    // Pattern 2: include_once 'include/...
    [
        'pattern' => "/include_once\s+['\"]include\/([^'\"]+)['\"]/",
        'replacement' => "include_once(dirname(__FILE__) . '/../../include/$1')",
        'description' => "include/ paths"
    ],
    // Pattern 3: require 'include/...
    [
        'pattern' => "/require\s+['\"]include\/([^'\"]+)['\"]/",
        'replacement' => "require dirname(__FILE__) . '/../../include/$1'",
        'description' => "include/ paths"
    ],
    // Pattern 4: include 'include/...
    [
        'pattern' => "/include\s+['\"]include\/([^'\"]+)['\"]/",
        'replacement' => "include(dirname(__FILE__) . '/../../include/$1')",
        'description' => "include/ paths"
    ],
];

$total_fixed = 0;
$files_fixed = [];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "<p style='color:orange'><strong>⚠️ Directory not found: $dir</strong></p>";
        continue;
    }
    
    echo "<h2>Processing: $dir</h2>";
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filepath = $file->getPathname();
            $content = file_get_contents($filepath);
            
            if ($content === false) {
                continue;
            }
            
            $original_content = $content;
            $file_fixed = false;
            
            foreach ($patterns as $pattern_info) {
                if (preg_match($pattern_info['pattern'], $content)) {
                    $content = preg_replace($pattern_info['pattern'], $pattern_info['replacement'], $content);
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
                    $relative_path = str_replace($root_dir . '/', '', $filepath);
                    echo "<p style='color:green'><strong>✅ Fixed: $relative_path</strong></p>";
                    $files_fixed[] = $relative_path;
                    $total_fixed++;
                }
            }
        }
    }
}

echo "<hr>";
echo "<h2>✅ FIX COMPLETE</h2>";
echo "<p><strong>Total files fixed: $total_fixed</strong></p>";

if (!empty($files_fixed)) {
    echo "<h3>Fixed files:</h3>";
    echo "<ul>";
    foreach ($files_fixed as $file) {
        echo "<li>$file</li>";
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

