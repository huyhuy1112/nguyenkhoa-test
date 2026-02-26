# FIX HTMLPURIFIER AUTOLOAD ERROR

## 🔴 LỖI

```
Fatal error: Class "HTMLPurifier_Config" not found
File: include/utils/VtlibUtils.php
Line: 703
```

## 📋 NGUYÊN NHÂN

HTMLPurifier library không được autoload đúng cách khi `vtlib_purify()` được gọi. Composer autoload có thể chưa load HTMLPurifier classes khi function này được execute.

## ✅ GIẢI PHÁP

### CÁCH 1: Dùng fix_htmlpurifier_autoload.php (Khuyến nghị - Tự động)

#### Bước 1: Pull code

```bash
cd /home/nhtdbus8/supertestcrm.tdbsolution.com
git pull origin main
```

#### Bước 2: Truy cập fix script

```
https://supertestcrm.tdbsolution.com/HTMLPURIFIER_FIX/fix_htmlpurifier_autoload.php
```

File sẽ:
- Tự động backup `include/utils/VtlibUtils.php`
- Thêm HTMLPurifier autoload check vào function `vtlib_purify()`
- Fix file

#### Bước 3: Test website

- Truy cập website
- Không còn white screen
- Không còn lỗi HTMLPurifier

#### Bước 4: Xóa fix file

```bash
rm HTMLPURIFIER_FIX/fix_htmlpurifier_autoload.php
```

---

### CÁCH 2: Fix thủ công

#### Bước 1: Backup file

```bash
cp include/utils/VtlibUtils.php include/utils/VtlibUtils.php.backup
```

#### Bước 2: Sửa file

Mở `include/utils/VtlibUtils.php`, tìm function `vtlib_purify()` (khoảng line 668).

Thêm code sau vào **đầu function**, sau dòng `global $__htmlpurifier_instance...`:

```php
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
```

#### Bước 3: Thêm check trước khi sử dụng

Tìm dòng:
```php
$config = HTMLPurifier_Config::createDefault();
```

Thêm **trước** dòng đó:
```php
// Check again before using
if (!class_exists('HTMLPurifier_Config')) {
    throw new Exception("HTMLPurifier_Config class not found. Please ensure HTMLPurifier is installed via Composer.");
}
```

#### Bước 4: Test website

- Truy cập website
- Không còn white screen

---

## 🔍 KIỂM TRA

Sau khi fix, kiểm tra:

1. **File đã được fix:**
   ```bash
   grep "FIX: Ensure HTMLPurifier" include/utils/VtlibUtils.php
   ```
   → Phải có output

2. **HTMLPurifier tồn tại:**
   ```bash
   ls -la vendor/ezyang/htmlpurifier/library/HTMLPurifier.php
   ```
   → Phải có file

3. **Test website:**
   - Truy cập website
   - Không còn white screen
   - Không còn lỗi HTMLPurifier

---

## ⚠️ LƯU Ý

1. **Backup trước khi fix**
   - Luôn backup file gốc
   - Có thể restore nếu có vấn đề

2. **Xóa fix files**
   - Xóa `fix_htmlpurifier_autoload.php` sau khi fix xong
   - Giữ `VtlibUtils.php.backup` để phòng hờ

3. **Composer dependencies**
   - Đảm bảo `composer install` đã chạy
   - HTMLPurifier phải có trong `vendor/`

---

## ✅ KẾT QUẢ

Sau khi fix:
- ✅ HTMLPurifier được autoload đúng cách
- ✅ `vtlib_purify()` hoạt động bình thường
- ✅ Website không còn white screen
- ✅ Không còn lỗi "Class HTMLPurifier_Config not found"

