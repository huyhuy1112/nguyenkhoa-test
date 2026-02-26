<?php
/**
 * FIX HTMLPURIFIER WITHOUT TERMINAL - Fix without using terminal/SSH
 * 
 * File này sẽ hướng dẫn và tự động fix HTMLPurifier mà không cần terminal
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/HTMLPURIFIER_FIX/fix_without_terminal.php
 *   3. Làm theo hướng dẫn
 *   4. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>FIX HTMLPURIFIER WITHOUT TERMINAL</h1>";
echo "<hr>";

$htmlpurifier_base = '/home/nhtdbus8/supertestcrm.tdbsolution.com/vendor/ezyang/htmlpurifier/library';
$vtlib_path = '/home/nhtdbus8/supertestcrm.tdbsolution.com/include/utils/VtlibUtils.php';

echo "<h2>Step 1: Check HTMLPurifier Files</h2>";

$required_files = [
    'HTMLPurifier.php' => true,
    'HTMLPurifier/Bootstrap.php' => true,
    'HTMLPurifier/Config.php' => false, // Critical
    'HTMLPurifier.autoload.php' => true,
    'HTMLPurifier.auto.php' => true
];

$missing_critical = [];
$missing_optional = [];

foreach ($required_files as $file => $critical) {
    $path = $htmlpurifier_base . '/' . $file;
    if (file_exists($path)) {
        echo "<p style='color:green'><strong>✅ $file</strong></p>";
    } else {
        if ($critical) {
            echo "<p style='color:red'><strong>✗ $file - MISSING (Critical)</strong></p>";
            $missing_critical[] = $file;
        } else {
            echo "<p style='color:orange'><strong>⚠️ $file - MISSING (Optional but recommended)</strong></p>";
            $missing_optional[] = $file;
        }
    }
}

echo "<hr>";

// If Config.php is missing, try to find it or create workaround
if (in_array('HTMLPurifier/Config.php', $missing_critical) || !file_exists($htmlpurifier_base . '/HTMLPurifier/Config.php')) {
    echo "<h2 style='color:red'>⚠️ CRITICAL: HTMLPurifier/Config.php MISSING</h2>";
    
    echo "<h3>Solution 1: Check if file exists in different location</h3>";
    
    // Try to find Config.php in different locations
    $search_paths = [
        $htmlpurifier_base . '/HTMLPurifier/Config.php',
        dirname($htmlpurifier_base) . '/HTMLPurifier/Config.php',
        '/home/nhtdbus8/supertestcrm.tdbsolution.com/vendor/ezyang/htmlpurifier/library/HTMLPurifier/Config.php',
        'vendor/ezyang/htmlpurifier/library/HTMLPurifier/Config.php'
    ];
    
    $found = false;
    foreach ($search_paths as $path) {
        if (file_exists($path)) {
            echo "<p style='color:green'><strong>✅ Found Config.php at: $path</strong></p>";
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo "<h3>Solution 2: Upload Config.php via cPanel File Manager</h3>";
        echo "<p><strong>Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Go to cPanel → File Manager</li>";
        echo "<li>Navigate to: <code>vendor/ezyang/htmlpurifier/library/HTMLPurifier/</code></li>";
        echo "<li>Check if <code>Config.php</code> exists</li>";
        echo "<li>If not, you need to download HTMLPurifier and extract Config.php</li>";
        echo "<li>Or re-upload the entire vendor/ directory</li>";
        echo "</ol>";
        
        echo "<h3>Solution 3: Use Workaround (Load without Config.php)</h3>";
        echo "<p>We can try to load HTMLPurifier using autoload files which may work without Config.php initially.</p>";
        
        // Try workaround: load via autoload
        echo "<h4>Testing Workaround...</h4>";
        
        try {
            // Try loading via includes file
            if (file_exists($htmlpurifier_base . '/HTMLPurifier.includes.php')) {
                require_once $htmlpurifier_base . '/HTMLPurifier.includes.php';
                echo "<p style='color:green'><strong>✅ Loaded via HTMLPurifier.includes.php</strong></p>";
            } elseif (file_exists($htmlpurifier_base . '/HTMLPurifier.autoload.php')) {
                // Try to load Bootstrap first
                if (file_exists($htmlpurifier_base . '/HTMLPurifier/Bootstrap.php')) {
                    require_once $htmlpurifier_base . '/HTMLPurifier/Bootstrap.php';
                }
                require_once $htmlpurifier_base . '/HTMLPurifier.autoload.php';
                echo "<p style='color:green'><strong>✅ Loaded via HTMLPurifier.autoload.php</strong></p>";
            }
            
            if (class_exists('HTMLPurifier_Config')) {
                echo "<p style='color:green'><strong>✅ HTMLPurifier_Config class loaded successfully!</strong></p>";
                echo "<p>You can proceed with the fix.</p>";
            } else {
                throw new Exception("HTMLPurifier_Config still not found");
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red'><strong>✗ Workaround failed: " . $e->getMessage() . "</strong></p>";
            echo "<p><strong>You MUST upload Config.php file manually via cPanel File Manager.</strong></p>";
        }
    }
}

echo "<hr>";

// If we can load HTMLPurifier, proceed with fix
if (class_exists('HTMLPurifier_Config') || file_exists($htmlpurifier_base . '/HTMLPurifier/Config.php')) {
    echo "<h2>Step 2: Apply Fix to VtlibUtils.php</h2>";
    
    if (!file_exists($vtlib_path)) {
        die("<p style='color:red'><strong>✗ VtlibUtils.php NOT FOUND</strong></p>");
    }
    
    $content = file_get_contents($vtlib_path);
    if ($content === false) {
        die("<p style='color:red'><strong>✗ Cannot read VtlibUtils.php</strong></p>");
    }
    
    // Check if already fixed
    if (strpos($content, '// FIX: Ensure HTMLPurifier is autoloaded') !== false) {
        echo "<p style='color:orange'><strong>⚠️ Fix already applied</strong></p>";
        echo "<p>If it's not working, we'll update it with better fix...</p>";
        // Remove old fix
        $content = preg_replace('/\s*\/\/ FIX: Ensure HTMLPurifier is autoloaded.*?}\s*/s', '', $content);
    }
    
    // Create backup
    $backup_path = dirname($vtlib_path) . '/VtlibUtils.php.backup';
    if (!file_exists($backup_path)) {
        file_put_contents($backup_path, $content);
        chmod($backup_path, 0644);
        echo "<p style='color:green'><strong>✅ Backup created</strong></p>";
    }
    
    // Find function
    $function_pattern = '/function\s+vtlib_purify\s*\([^)]*\)\s*\{/';
    if (!preg_match($function_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        die("<p style='color:red'><strong>✗ Cannot find function vtlib_purify()</strong></p>");
    }
    
    $function_start = $matches[0][1];
    $function_start_pos = $function_start + strlen($matches[0][0]);
    
    // Find insertion point
    $insert_pos = $function_start_pos;
    $next_200 = substr($content, $insert_pos, 200);
    $static_pos = strpos($next_200, "\n    static");
    if ($static_pos !== false) {
        $insert_pos += $static_pos + 1;
    } else {
        $newline_pos = strpos($content, "\n", $function_start_pos);
        if ($newline_pos !== false) {
            $insert_pos = $newline_pos + 1;
        }
    }
    
    // Prepare fix code - use includes file if Config.php missing
    $fix_code = "\n    // FIX: Ensure HTMLPurifier is autoloaded (works even if Config.php missing)\n";
    $fix_code .= "    if (!class_exists('HTMLPurifier_Config')) {\n";
    $fix_code .= "        \$htmlpurifier_paths = [\n";
    $fix_code .= "            '" . addslashes($htmlpurifier_base) . "',\n";
    $fix_code .= "            \$use_root_directory . '/vendor/ezyang/htmlpurifier/library',\n";
    $fix_code .= "            dirname(__FILE__) . '/../../vendor/ezyang/htmlpurifier/library',\n";
    $fix_code .= "            'vendor/ezyang/htmlpurifier/library'\n";
    $fix_code .= "        ];\n";
    $fix_code .= "        \n";
    $fix_code .= "        \$htmlpurifier_base_found = null;\n";
    $fix_code .= "        foreach (\$htmlpurifier_paths as \$path) {\n";
    $fix_code .= "            if (file_exists(\$path . '/HTMLPurifier.php')) {\n";
    $fix_code .= "                \$htmlpurifier_base_found = \$path;\n";
    $fix_code .= "                break;\n";
    $fix_code .= "            }\n";
    $fix_code .= "        }\n";
    $fix_code .= "        \n";
    $fix_code .= "        if (\$htmlpurifier_base_found) {\n";
    $fix_code .= "            // Try HTMLPurifier.includes.php first (loads all files)\n";
    $fix_code .= "            if (file_exists(\$htmlpurifier_base_found . '/HTMLPurifier.includes.php')) {\n";
    $fix_code .= "                require_once \$htmlpurifier_base_found . '/HTMLPurifier.includes.php';\n";
    $fix_code .= "            } else {\n";
    $fix_code .= "                // Load Bootstrap first\n";
    $fix_code .= "                if (file_exists(\$htmlpurifier_base_found . '/HTMLPurifier/Bootstrap.php')) {\n";
    $fix_code .= "                    require_once \$htmlpurifier_base_found . '/HTMLPurifier/Bootstrap.php';\n";
    $fix_code .= "                }\n";
    $fix_code .= "                \n";
    $fix_code .= "                // Load main class\n";
    $fix_code .= "                if (file_exists(\$htmlpurifier_base_found . '/HTMLPurifier.php')) {\n";
    $fix_code .= "                    require_once \$htmlpurifier_base_found . '/HTMLPurifier.php';\n";
    $fix_code .= "                }\n";
    $fix_code .= "                \n";
    $fix_code .= "                // Load Config if exists\n";
    $fix_code .= "                if (file_exists(\$htmlpurifier_base_found . '/HTMLPurifier/Config.php')) {\n";
    $fix_code .= "                    require_once \$htmlpurifier_base_found . '/HTMLPurifier/Config.php';\n";
    $fix_code .= "                }\n";
    $fix_code .= "                \n";
    $fix_code .= "                // Try autoload files\n";
    $fix_code .= "                if (!class_exists('HTMLPurifier_Config')) {\n";
    $fix_code .= "                    if (file_exists(\$htmlpurifier_base_found . '/HTMLPurifier.autoload.php')) {\n";
    $fix_code .= "                        require_once \$htmlpurifier_base_found . '/HTMLPurifier.autoload.php';\n";
    $fix_code .= "                    } elseif (file_exists(\$htmlpurifier_base_found . '/HTMLPurifier.auto.php')) {\n";
    $fix_code .= "                        require_once \$htmlpurifier_base_found . '/HTMLPurifier.auto.php';\n";
    $fix_code .= "                    }\n";
    $fix_code .= "                }\n";
    $fix_code .= "            }\n";
    $fix_code .= "        } else {\n";
    $fix_code .= "            // Last resort: Composer autoload\n";
    $fix_code .= "            if (file_exists('vendor/autoload.php')) {\n";
    $fix_code .= "                require_once 'vendor/autoload.php';\n";
    $fix_code .= "            }\n";
    $fix_code .= "        }\n";
    $fix_code .= "        \n";
    $fix_code .= "        // Final check\n";
    $fix_code .= "        if (!class_exists('HTMLPurifier_Config')) {\n";
    $fix_code .= "            throw new Exception('HTMLPurifier_Config not found. Please ensure HTMLPurifier is properly installed. Missing file: ' . \$htmlpurifier_base_found . '/HTMLPurifier/Config.php');\n";
    $fix_code .= "        }\n";
    $fix_code .= "    }\n";
    
    // Insert fix
    $new_content = substr_replace($content, $fix_code, $insert_pos, 0);
    
    // Write file
    if (file_put_contents($vtlib_path, $new_content)) {
        chmod($vtlib_path, 0644);
        echo "<p style='color:green'><strong>✅ Fix applied successfully!</strong></p>";
    } else {
        die("<p style='color:red'><strong>✗ Cannot write file</strong></p>");
    }
    
    echo "<hr>";
    echo "<h2>✅ FIX APPLIED</h2>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test website: <a href='../index.php' target='_blank'>index.php</a></li>";
    echo "<li>If still error, you need to upload Config.php via cPanel File Manager</li>";
    echo "</ol>";
    
} else {
    echo "<h2 style='color:red'>⚠️ CANNOT PROCEED</h2>";
    echo "<p><strong>HTMLPurifier/Config.php is missing and cannot be loaded.</strong></p>";
    echo "<p><strong>You MUST upload this file via cPanel File Manager:</strong></p>";
    echo "<ol>";
    echo "<li>Go to cPanel → File Manager</li>";
    echo "<li>Navigate to: <code>vendor/ezyang/htmlpurifier/library/HTMLPurifier/</code></li>";
    echo "<li>Upload <code>Config.php</code> file</li>";
    echo "<li>Or re-upload the entire <code>vendor/ezyang/htmlpurifier/</code> directory</li>";
    echo "</ol>";
    echo "<p><strong>Alternative:</strong> Download HTMLPurifier from <a href='https://github.com/ezyang/htmlpurifier' target='_blank'>GitHub</a> and extract Config.php</p>";
}

echo "<hr>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong!</p>";
?>

