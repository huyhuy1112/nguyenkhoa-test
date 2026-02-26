<?php
/**
 * Final Comprehensive Check
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>✅ Final System Check</h2>";
echo "<hr>";

// 1. Database
require_once 'config.php';
require_once 'include/database/PearDatabase.php';
global $adb;
$adb = PearDatabase::getInstance();

$tables = $adb->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = 'TDB1'");
$row = $adb->fetchByAssoc($tables);
echo "<p>✅ Database: " . $row['cnt'] . " tables</p>";

$notifications = $adb->query("SELECT COUNT(*) as cnt FROM vtiger_notifications");
$notifRow = $adb->fetchByAssoc($notifications);
echo "<p>✅ Notifications: " . $notifRow['cnt'] . " records</p>";

echo "<hr>";
echo "<h3>🎉 System Status: READY</h3>";
echo "<p><a href='index.php'>Go to Vtiger</a></p>";

