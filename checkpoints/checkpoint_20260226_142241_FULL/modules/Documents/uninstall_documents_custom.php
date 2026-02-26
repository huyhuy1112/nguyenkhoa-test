<?php
/**
 * Gỡ toàn bộ custom Documents: bảng DB, khỏi menu, script.
 * Chạy: php modules/Documents/uninstall_documents_custom.php
 */
$crmRoot = dirname(dirname(__DIR__));
chdir($crmRoot);
require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';

$adb = PearDatabase::getInstance();

echo "=== GỠ CUSTOM DOCUMENTS ===\n\n";

// 1. Xóa Documents khỏi menu MANAGEMENT
echo "1. Xóa Documents khỏi menu MANAGEMENT...\n";
$docResult = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = ?", array('Documents'));
if ($adb->num_rows($docResult) > 0) {
    $docTabId = $adb->query_result($docResult, 0, 'tabid');
    $adb->pquery("DELETE FROM vtiger_app2tab WHERE appname = 'MANAGEMENT' AND tabid = ?", array($docTabId));
    echo "   ✓ Đã xóa Documents (tabid=$docTabId) khỏi MANAGEMENT.\n";
} else {
    echo "   - Documents tab không tìm thấy.\n";
}

// 2. Drop bảng custom
echo "\n2. Xóa bảng database custom...\n";
$tables = array('vtiger_documentfolder_sharing', 'vtiger_document_history');
foreach ($tables as $tbl) {
    $check = $adb->pquery("SHOW TABLES LIKE ?", array($tbl));
    if ($adb->num_rows($check) > 0) {
        $adb->pquery("DROP TABLE `$tbl`", array());
        echo "   ✓ Đã DROP bảng $tbl\n";
    } else {
        echo "   - Bảng $tbl không tồn tại.\n";
    }
}

echo "\n3. Các file script custom đã xóa trước đó.\n";

echo "\n=== XONG ===\n";
echo "Tiếp theo: Clear cache, logout, login lại.\n";
echo "Document sẽ không còn trong menu MANAGEMENT.\n";
