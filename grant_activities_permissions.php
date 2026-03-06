<?php
/**
 * One-time script: cấp quyền full cho module Activities (tabid = 59) cho mọi profile
 * và bật module hiển thị.
 *
 * Usage (từ root vtiger):
 *   php grant_activities_permissions.php
 */

chdir(__DIR__);

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/VtlibUtils.php';

global $adb;

function info($msg) { echo $msg . PHP_EOL; }
function fail($msg) { echo "FAIL: $msg" . PHP_EOL; exit(1); }

// Tabid của Activities (bạn đã tạo)
$tabid = 59;

// Bật module hiển thị
$adb->pquery("UPDATE vtiger_tab SET presence = 0, tablabel = 'Activities', isentitytype = 1 WHERE tabid = ?", [$tabid]);

// Lấy danh sách profile
$profilesRes = $adb->pquery("SELECT profileid FROM vtiger_profile", []);
if (!$profilesRes || $adb->num_rows($profilesRes) === 0) {
    fail("Không tìm thấy profile.");
}

while ($row = $adb->fetchByAssoc($profilesRes)) {
    $profileId = (int)$row['profileid'];

    // vtiger_profile2tab: 0 = allowed, 1 = denied
    $chk = $adb->pquery("SELECT 1 FROM vtiger_profile2tab WHERE profileid = ? AND tabid = ?", [$profileId, $tabid]);
    if ($chk && $adb->num_rows($chk) > 0) {
        $adb->pquery("UPDATE vtiger_profile2tab SET permissions = 0 WHERE profileid = ? AND tabid = ?", [$profileId, $tabid]);
    } else {
        $adb->pquery("INSERT INTO vtiger_profile2tab (profileid, tabid, permissions) VALUES (?, ?, 0)", [$profileId, $tabid]);
    }

    // vtiger_profile2standardpermissions: 0 = allowed, 1 = denied
    // actionid: 1=DetailView, 2=EditView, 3=CreateView, 4=Delete
    foreach ([1,2,3,4] as $actionId) {
        $chk2 = $adb->pquery(
            "SELECT 1 FROM vtiger_profile2standardpermissions WHERE profileid=? AND tabid=? AND operation=?",
            [$profileId, $tabid, $actionId]
        );
        if ($chk2 && $adb->num_rows($chk2) > 0) {
            $adb->pquery(
                "UPDATE vtiger_profile2standardpermissions SET permissions = 0 WHERE profileid=? AND tabid=? AND operation=?",
                [$profileId, $tabid, $actionId]
            );
        } else {
            $adb->pquery(
                "INSERT INTO vtiger_profile2standardpermissions (profileid, tabid, operation, permissions) VALUES (?, ?, ?, 0)",
                [$profileId, $tabid, $actionId]
            );
        }
    }
}

info("Đã cấp quyền Full cho Activities (tabid=$tabid) cho tất cả profiles và bật module hiển thị.");
info("Nếu vẫn chưa thấy menu, xóa cache: cache/tabdata.php và user_privileges/menu_*.php rồi F5.");

