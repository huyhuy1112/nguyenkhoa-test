<?php
/**
 * One-time helper script to add Activities (Calendar) into SUPPORT app menu.
 *
 * Usage (from vtiger root):
 *   php add_support_activities_to_menu.php
 */

chdir(__DIR__);

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/VtlibUtils.php';

global $adb;

function info($msg) { echo $msg . PHP_EOL; }
function warn($msg) { echo "WARN: $msg" . PHP_EOL; }
function fail($msg) { echo "FAIL: $msg" . PHP_EOL; exit(1); }

// 1) Lấy appid của SUPPORT
$appRes = $adb->pquery(
    "SELECT appid FROM vtiger_app WHERE name = ? OR label = ? LIMIT 1",
    ['SUPPORT', 'SUPPORT']
);
if (!$appRes || $adb->num_rows($appRes) === 0) {
    fail("Không tìm thấy app SUPPORT trong vtiger_app.");
}
$appid = (int)$adb->query_result($appRes, 0, 'appid');
info("SUPPORT appid = $appid");

// 2) Lấy tabid của module Activities (Calendar)
$tabRes = $adb->pquery(
    "SELECT tabid, name FROM vtiger_tab WHERE name IN ('Calendar','Events','Activity') ORDER BY presence ASC LIMIT 1",
    []
);
if (!$tabRes || $adb->num_rows($tabRes) === 0) {
    fail("Không tìm thấy module Calendar/Activities trong vtiger_tab.");
}
$tabid = (int)$adb->query_result($tabRes, 0, 'tabid');
$tabName = $adb->query_result($tabRes, 0, 'name');
info("Activities tabid = $tabid (name=$tabName)");

// 3) Kiểm tra đã có trong vtiger_app2tab chưa
$chkRes = $adb->pquery(
    "SELECT 1 FROM vtiger_app2tab WHERE appid = ? AND tabid = ?",
    [$appid, $tabid]
);
if ($chkRes && $adb->num_rows($chkRes) > 0) {
    info("Đã có Activities trong app SUPPORT, không cần thêm.");
    exit(0);
}

// 4) Lấy sequence mới
$seqRes = $adb->pquery(
    "SELECT COALESCE(MAX(sequence),0)+1 AS nextseq FROM vtiger_app2tab WHERE appid = ?",
    [$appid]
);
$seq = ($seqRes && $adb->num_rows($seqRes) > 0)
    ? (int)$adb->query_result($seqRes, 0, 'nextseq')
    : 1;

// 5) Thêm vào vtiger_app2tab
$adb->pquery(
    "INSERT INTO vtiger_app2tab (appid, tabid, sequence) VALUES (?, ?, ?)",
    [$appid, $tabid, $seq]
);

info("Đã thêm Activities ($tabName) vào SUPPORT menu với sequence = $seq.");
exit(0);

