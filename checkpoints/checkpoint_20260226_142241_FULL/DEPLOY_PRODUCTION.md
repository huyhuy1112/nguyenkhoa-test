# HƯỚNG DẪN DEPLOY LÊN PRODUCTION (cPanel)

## 📋 CHUẨN BỊ

### 1. Clone code từ GitHub

```bash
cd /home/nhtdbus8/supertestcrm.tdbsolution.com
git clone https://github.com/huyhuy1112/supertestcrm.git .
# hoặc nếu đã có code:
git pull origin main
```

### 2. Cấu hình Database

Tạo file `config.inc.php` từ template:

```bash
cp config.inc.php.template config.inc.php
# hoặc copy từ vtiger_upload_fix/config.inc.php
```

Sửa `config.inc.php`:

```php
// Database configuration
$dbconfig['db_server'] = 'localhost';
$dbconfig['db_port'] = '';
$dbconfig['db_username'] = 'nhtdbus8_username';  // Thay bằng username thực tế
$dbconfig['db_password'] = 'your_password';      // Thay bằng password thực tế
$dbconfig['db_name'] = 'nhtdbus8_database';      // Thay bằng database name thực tế
$dbconfig['db_type'] = 'mysqli';
$dbconfig['db_status'] = 'true';

// Site URL
$site_URL = 'https://supertestcrm.tdbsolution.com/';

// Root directory
$root_directory = dirname(__FILE__) . '/';

// Production settings
ini_set('display_errors', 'Off');
```

### 3. Set Permissions

```bash
# Set permissions cho directories
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Set permissions cho cache và storage
chmod -R 755 cache/
chmod -R 755 storage/
chmod 644 .htaccess
chmod 644 index.php
```

### 4. Tạo .htaccess

```bash
cp htaccess.txt .htaccess
chmod 644 .htaccess
```

Nội dung `.htaccess`:
```apache
Options -Indexes

# Enable output buffering
php_flag output_buffering On
php_value output_buffering 4096

# Disable display errors
php_flag display_errors Off
```

### 5. Import Database

```bash
# Import database từ backup hoặc SQL file
mysql -u username -p database_name < database.sql
```

### 6. Clear Cache

```bash
rm -rf cache/templates_c/*
rm -rf cache/images/*
rm -rf cache/htmlpurifier/*
```

### 7. Tạo Cache Directories

```bash
mkdir -p cache/htmlpurifier/HTML
chmod -R 755 cache/
```

---

## ✅ CHECKLIST DEPLOY

- [ ] Clone/pull code từ GitHub
- [ ] Tạo và cấu hình `config.inc.php`
- [ ] Set permissions đúng
- [ ] Tạo `.htaccess` từ `htaccess.txt`
- [ ] Import database
- [ ] Clear cache
- [ ] Tạo cache directories
- [ ] Test truy cập website
- [ ] Test login
- [ ] Test các chức năng chính

---

## 🔧 FIX CÁC LỖI THƯỜNG GẶP

### Lỗi 1: HTTP 500 Internal Server Error

**Nguyên nhân:** Config sai hoặc permissions sai

**Fix:**
```bash
# Check config.inc.php
# Check permissions
chmod 644 config.inc.php
chmod 755 cache/
```

### Lỗi 2: Database Connection Error

**Nguyên nhân:** Database config sai

**Fix:**
- Kiểm tra `config.inc.php`
- Kiểm tra database credentials trong cPanel
- Test connection: `mysql -u username -p database_name`

### Lỗi 3: Cache Directory Error

**Nguyên nhân:** Cache directories không tồn tại

**Fix:**
```bash
mkdir -p cache/htmlpurifier/HTML
chmod -R 755 cache/
```

### Lỗi 4: JSON Parse Error

**Nguyên nhân:** Output buffer có content không phải JSON

**Fix:**
- Enable output buffering trong `.htaccess`
- Clear cache
- Check PHP errors

---

## 📝 NOTES

1. **Không push `config.inc.php`** lên GitHub (có trong .gitignore)
2. **Database credentials** phải được set trên production
3. **Permissions** rất quan trọng cho cPanel shared hosting
4. **Cache directories** phải được tạo và set permissions đúng

---

## 🚀 QUICK DEPLOY SCRIPT

```bash
#!/bin/bash
# Quick deploy script for cPanel

cd /home/nhtdbus8/supertestcrm.tdbsolution.com

# Pull latest code
git pull origin main

# Set permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 755 cache/ storage/

# Clear cache
rm -rf cache/templates_c/*
rm -rf cache/images/*

# Create cache directories
mkdir -p cache/htmlpurifier/HTML
chmod -R 755 cache/

echo "Deploy completed!"
```


