<?php
/**
 * Thêm Activities (module Calendar) vào menu SUPPORT.
 * Sidebar lấy module theo vtiger_app2tab; một module có thể nằm trong nhiều app.
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';

$adb = PearDatabase::getInstance();

echo "==========================================\n";
echo "Thêm Activities vào menu SUPPORT\n";
echo "==========================================\n\n";

// Activities = module Calendar trong vtiger_tab
$result = $adb->pquery("SELECT tabid, tablabel FROM vtiger_tab WHERE name = ?", array('Calendar'));
if ($adb->num_rows($result) == 0) {
    die("ERROR: Module Calendar (Activities) không tìm thấy trong vtiger_tab.\n");
}
$tabid = $adb->query_result($result, 0, 'tabid');
$tablabel = $adb->query_result($result, 0, 'tablabel');
echo "  Calendar (Activities): tabid=$tabid, label='$tablabel'\n\n";

$appName = 'SUPPORT';

// Sequence tiếp theo cho SUPPORT
$seqResult = $adb->pquery("SELECT MAX(sequence) AS max_seq FROM vtiger_app2tab WHERE appname = ?", array($appName));
$nextSeq = ($adb->num_rows($seqResult) > 0 && $adb->query_result($seqResult, 0, 'max_seq') !== null)
    ? (int) $adb->query_result($seqResult, 0, 'max_seq') + 1 : 1;

$check = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", array($appName, $tabid));
if ($adb->num_rows($check) == 0) {
    $adb->pquery(
        "INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
        array($appName, $tabid, $nextSeq, 1)
    );
    echo "  ✓ Đã thêm Activities (Calendar) vào menu SUPPORT (sequence: $nextSeq).\n";
} else {
    $adb->pquery(
        "UPDATE vtiger_app2tab SET sequence = ?, visible = 1 WHERE appname = ? AND tabid = ?",
        array($nextSeq, $appName, $tabid)
    );
    echo "  ✓ Đã cập nhật Activities (Calendar) trong menu SUPPORT (sequence: $nextSeq).\n";
}

echo "\nXong. Tiếp theo: Settings → Configuration → Clear Cache, rồi logout/login.\n";
