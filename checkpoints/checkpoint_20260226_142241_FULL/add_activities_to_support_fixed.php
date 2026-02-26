<?php
/**
 * Thêm Activities (Calendar) vào menu SUPPORT và đổi label thành "Activities".
 * 
 * Lưu ý: Vtiger chỉ có 1 label cho mỗi module (tablabel trong vtiger_tab).
 * Nếu đổi label Calendar thành "Activities", nó sẽ áp dụng cho TẤT CẢ app.
 * Nếu muốn MANAGEMENT vẫn hiển thị "Schedule", cần dùng translation.
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';

$adb = PearDatabase::getInstance();

echo "==========================================\n";
echo "Thêm Activities vào menu SUPPORT\n";
echo "==========================================\n\n";

// Bước 1: Lấy Calendar module
$result = $adb->pquery("SELECT tabid, tablabel FROM vtiger_tab WHERE name = ?", array('Calendar'));
if ($adb->num_rows($result) == 0) {
    die("ERROR: Module Calendar không tìm thấy trong vtiger_tab.\n");
}
$tabid = $adb->query_result($result, 0, 'tabid');
$currentLabel = $adb->query_result($result, 0, 'tablabel');
echo "Calendar module:\n";
echo "  tabid: $tabid\n";
echo "  label hiện tại: '$currentLabel'\n\n";

$appName = 'SUPPORT';

// Bước 2: Kiểm tra Calendar đã có trong SUPPORT chưa
$check = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", array($appName, $tabid));
$alreadyInSupport = ($adb->num_rows($check) > 0);

if (!$alreadyInSupport) {
    // Bước 3: Thêm Calendar vào SUPPORT
    $seqResult = $adb->pquery("SELECT MAX(sequence) AS max_seq FROM vtiger_app2tab WHERE appname = ?", array($appName));
    $nextSeq = ($adb->num_rows($seqResult) > 0 && $adb->query_result($seqResult, 0, 'max_seq') !== null)
        ? (int) $adb->query_result($seqResult, 0, 'max_seq') + 1 : 1;
    
    $adb->pquery(
        "INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
        array($appName, $tabid, $nextSeq, 1)
    );
    echo "✓ Đã thêm Calendar vào menu SUPPORT (sequence: $nextSeq).\n\n";
} else {
    echo "Calendar đã có trong SUPPORT.\n\n";
}

// Bước 4: Đổi label Calendar thành "Activities" (nếu chưa phải)
// Lưu ý: Vtiger chỉ có 1 label cho mỗi module, nên đổi label sẽ ảnh hưởng tất cả app
if ($currentLabel !== 'Activities') {
    echo "Đổi label Calendar từ '$currentLabel' thành 'Activities'...\n";
    echo "  ⚠ Lưu ý: Label này sẽ áp dụng cho Calendar trong TẤT CẢ app.\n";
    echo "  Nếu MANAGEMENT đang hiển thị 'Schedule', sau khi đổi sẽ thành 'Activities'.\n\n";
    
    $adb->pquery("UPDATE vtiger_tab SET tablabel = ? WHERE tabid = ?", array('Activities', $tabid));
    echo "✓ Đã đổi label Calendar thành 'Activities'.\n\n";
    
    // Nếu muốn MANAGEMENT vẫn hiển thị "Schedule", cần thêm translation trong Calendar module
    // Hoặc có thể tạo custom logic trong template (phức tạp hơn)
} else {
    echo "Label Calendar đã là 'Activities'.\n\n";
}

echo "Xong. Tiếp theo:\n";
echo "  1. Settings → Configuration → Clear Cache\n";
echo "  2. Logout và login lại\n";
echo "  3. Kiểm tra menu SUPPORT - sẽ thấy 'Activities'\n";
