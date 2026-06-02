<?php
/**
 * Gỡ Calendar (Schedule) khỏi menu SUPPORT — Schedule chỉ thuộc MANAGEMENT.
 * Chạy: php remove_calendar_from_support_menu.php
 */
require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';

$adb = PearDatabase::getInstance();
$tabRes = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = ?", array('Calendar'));
if (!$adb->num_rows($tabRes)) {
	die("Calendar module not found.\n");
}
$tabid = $adb->query_result($tabRes, 0, 'tabid');
$adb->pquery("DELETE FROM vtiger_app2tab WHERE tabid = ? AND appname = ?", array($tabid, 'SUPPORT'));
echo "Removed Calendar from SUPPORT app menu (tabid=$tabid).\n";
echo "Clear Vtiger cache and reload.\n";
