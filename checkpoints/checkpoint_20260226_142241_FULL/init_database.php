<?php
/**
 * Script to initialize Vtiger database structure from DatabaseSchema.xml
 * This will create all required tables for Vtiger to function
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load composer dependencies first
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// Load Vtiger configuration
require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';

echo "🚀 Starting Vtiger Database Initialization...\n\n";

// Initialize database connection
global $adb;
$adb = PearDatabase::getInstance();

try {
    // Test connection by simple query
    $test_result = $adb->query("SELECT 1");
    if ($test_result) {
        echo "✅ Database connection: OK\n";
        echo "📊 Database: " . $adb->dbName . "\n\n";
    } else {
        echo "❌ Database connection failed\n";
        exit(1);
    }
    
    // Check if tables already exist
    $result = $adb->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = '" . $adb->dbName . "'");
    $table_count = 0;
    if ($result && $adb->num_rows($result) > 0) {
        $row = $adb->raw_query_result_rowdata($result, 0);
        $table_count = isset($row['cnt']) ? $row['cnt'] : 0;
    }
    
    if ($table_count > 0) {
        echo "⚠️  Warning: Database already has $table_count tables.\n";
        echo "   This script will create missing tables only.\n\n";
    }
    
    echo "📋 Creating tables from DatabaseSchema.xml...\n";
    echo "   This may take several minutes...\n\n";
    
    // Create tables from schema
    $schemaFile = 'schema/DatabaseSchema.xml';
    if (!file_exists($schemaFile)) {
        echo "❌ Schema file not found: $schemaFile\n";
        exit(1);
    }
    
    $result = $adb->createTables($schemaFile);
    
    if ($result) {
        echo "\n❌ Error occurred during table creation. Check logs above.\n";
        exit(1);
    }
    
    echo "\n✅ Tables created successfully!\n\n";
    
    // Check final table count
    $result = $adb->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = '" . $adb->dbName . "'");
    $final_count = 0;
    if ($result && $adb->num_rows($result) > 0) {
        $row = $adb->raw_query_result_rowdata($result, 0);
        $final_count = isset($row['cnt']) ? $row['cnt'] : 0;
    }
    
    echo "📊 Total tables in database: $final_count\n";
    echo "\n🎉 Database initialization completed!\n";
    echo "   You can now access Vtiger at http://localhost:8080\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

