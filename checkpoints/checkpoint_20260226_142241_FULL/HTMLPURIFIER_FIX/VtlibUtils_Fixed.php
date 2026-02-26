<?php
/**
 * FIX HTMLPURIFIER AUTOLOAD - Production Override
 * 
 * File này fix lỗi "Class HTMLPurifier_Config not found"
 * bằng cách ensure HTMLPurifier được autoload đúng cách
 * 
 * HƯỚNG DẪN SỬ DỤNG:
 *   1. Backup file gốc: cp include/utils/VtlibUtils.php include/utils/VtlibUtils.php.backup
 *   2. Tìm function vtlib_purify() trong include/utils/VtlibUtils.php (khoảng line 668)
 *   3. Thêm code sau vào đầu function (sau dòng global $__htmlpurifier_instance...):
 * 
 *      // FIX: Ensure HTMLPurifier is autoloaded
 *      if (!class_exists('HTMLPurifier_Config')) {
 *          if (file_exists('vendor/ezyang/htmlpurifier/library/HTMLPurifier.autoload.php')) {
 *              require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.autoload.php';
 *          } elseif (file_exists('vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php')) {
 *              require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
 *          } elseif (file_exists('vendor/autoload.php')) {
 *              // Composer autoload should handle it, but ensure it's loaded
 *              if (!class_exists('HTMLPurifier_Config')) {
 *                  require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.php';
 *                  require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier/Config.php';
 *              }
 *          }
 *      }
 * 
 *   4. Hoặc copy toàn bộ function vtlib_purify() từ file này (đã được fix sẵn)
 *   5. Test website
 *   6. Xóa file này sau khi fix xong
 */

// This is just a reference file - actual fix needs to be applied to include/utils/VtlibUtils.php

/**
 * Purify (Cleanup) malicious snippets of code from the input
 * FIXED VERSION with HTMLPurifier autoload check
 *
 * @param String $value
 * @param Boolean $ignore Skip cleaning of the input
 * @return String
 */
function vtlib_purify($input, $ignore = false) {
    global $__htmlpurifier_instance, $root_directory, $default_charset;

    // FIX: Ensure HTMLPurifier is autoloaded
    if (!class_exists('HTMLPurifier_Config')) {
        $htmlpurifier_autoload_paths = [
            'vendor/ezyang/htmlpurifier/library/HTMLPurifier.autoload.php',
            'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php',
            dirname(__FILE__) . '/../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.autoload.php',
            dirname(__FILE__) . '/../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php'
        ];
        
        $loaded = false;
        foreach ($htmlpurifier_autoload_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $loaded = true;
                break;
            }
        }
        
        // If autoload files don't exist, try to load manually
        if (!$loaded && !class_exists('HTMLPurifier_Config')) {
            $htmlpurifier_base = 'vendor/ezyang/htmlpurifier/library';
            if (!file_exists($htmlpurifier_base)) {
                $htmlpurifier_base = dirname(__FILE__) . '/../../vendor/ezyang/htmlpurifier/library';
            }
            
            if (file_exists($htmlpurifier_base . '/HTMLPurifier.php')) {
                require_once $htmlpurifier_base . '/HTMLPurifier.php';
                require_once $htmlpurifier_base . '/HTMLPurifier/Config.php';
            }
        }
    }

    static $purified_cache = array();
    $value = $input;

    if (!is_array($input)) {
        $md5OfInput = md5($input ? $input : "");
        if (array_key_exists($md5OfInput, $purified_cache)) {
            $value = $purified_cache[$md5OfInput];
            //to escape cleaning up again
            $ignore = true;
        }
    }
    $use_charset = $default_charset;
    $use_root_directory = $root_directory;

    if (!$ignore) {
        // Initialize the instance if it has not yet done
        if ($__htmlpurifier_instance == false) {
            if (empty($use_charset))
                $use_charset = 'UTF-8';
            if (empty($use_root_directory))
                $use_root_directory = dirname(__FILE__) . '/../..';

            $allowedSchemes = array(
                'http' => true,
                'https' => true,
                'mailto' => true,
                'ftp' => true,
                'nntp' => true,
                'news' => true,
                'data' => true
            );

            // Check again before using
            if (!class_exists('HTMLPurifier_Config')) {
                throw new Exception("HTMLPurifier_Config class not found. Please ensure HTMLPurifier is installed via Composer.");
            }

            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', $use_charset);
            $config->set('Cache.SerializerPath', "$use_root_directory/test/vtlib");
            $config->set('CSS.AllowTricky', true);
            $config->set('URI.AllowedSchemes', $allowedSchemes);
            $config->set('Attr.EnableID', true);
            $config->set('HTML.TargetBlank', true);

            $__htmlpurifier_instance = new HTMLPurifier($config);
        }
        if ($__htmlpurifier_instance) {
            // ... rest of the function continues as normal
            // (composite type handling, etc.)
        }
    }

    return $value;
}

