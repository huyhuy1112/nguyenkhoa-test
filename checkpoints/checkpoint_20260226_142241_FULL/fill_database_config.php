<?php
/**
 * Script to automatically fill database credentials in config.inc.php
 * 
 * USAGE:
 * 1. Upload this file to your hosting root directory
 * 2. Edit the database credentials below
 * 3. Access: https://yourdomain.com/fill_database_config.php
 * 4. The script will update config.inc.php automatically
 * 5. DELETE this file after use for security!
 */

// ⚠️ EDIT THESE VALUES WITH YOUR DATABASE CREDENTIALS
$DB_USERNAME = 'nhtdbus8_supertestcrm';
$DB_PASSWORD = '987456321852huy';
$DB_NAME = 'nhtdbus8_supertestcrm';
$DB_SERVER = 'localhost';
$DB_PORT = ''; // Leave empty for default port

// ============================================
// DO NOT EDIT BELOW THIS LINE
// ============================================

$configFile = dirname(__FILE__) . '/config.inc.php';

if (!file_exists($configFile)) {
    die("❌ ERROR: config.inc.php not found at: $configFile");
}

echo "<h2>🔧 Auto-Fill Database Configuration</h2>";
echo "<hr>";

// Read current config
$configContent = file_get_contents($configFile);

// Check if already filled
if (strpos($configContent, "db_username'] = '$DB_USERNAME'") !== false) {
    echo "<p style='color:green;'>✅ Database credentials already filled!</p>";
    echo "<p><a href='index.php'>Go to Vtiger</a></p>";
    exit;
}

// Replace empty database credentials
$patterns = [
    "/\$dbconfig\['db_server'\]\s*=\s*'[^']*';/",
    "/\$dbconfig\['db_port'\]\s*=\s*'[^']*';/",
    "/\$dbconfig\['db_username'\]\s*=\s*'[^']*';/",
    "/\$dbconfig\['db_password'\]\s*=\s*'[^']*';/",
    "/\$dbconfig\['db_name'\]\s*=\s*'[^']*';/",
];

$replacements = [
    "\$dbconfig['db_server'] = '$DB_SERVER';",
    "\$dbconfig['db_port'] = '$DB_PORT';",
    "\$dbconfig['db_username'] = '$DB_USERNAME';",
    "\$dbconfig['db_password'] = '$DB_PASSWORD';",
    "\$dbconfig['db_name'] = '$DB_NAME';",
];

$newConfigContent = preg_replace($patterns, $replacements, $configContent);

// Backup original config
$backupFile = $configFile . '.backup.' . date('YmdHis');
if (copy($configFile, $backupFile)) {
    echo "<p>✅ Backup created: " . basename($backupFile) . "</p>";
}

// Write new config
if (file_put_contents($configFile, $newConfigContent)) {
    echo "<p style='color:green;'>✅ Database credentials filled successfully!</p>";
    echo "<p><strong>Database Server:</strong> $DB_SERVER</p>";
    echo "<p><strong>Database Name:</strong> $DB_NAME</p>";
    echo "<p><strong>Database User:</strong> $DB_USERNAME</p>";
    echo "<hr>";
    echo "<p><a href='index.php' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🚀 Go to Vtiger</a></p>";
    echo "<hr>";
    echo "<p style='color:red;'><strong>⚠️ SECURITY WARNING:</strong> Delete this file (fill_database_config.php) after use!</p>";
} else {
    echo "<p style='color:red;'>❌ ERROR: Could not write to config.inc.php</p>";
    echo "<p>Please check file permissions (should be 644 or 755)</p>";
}

