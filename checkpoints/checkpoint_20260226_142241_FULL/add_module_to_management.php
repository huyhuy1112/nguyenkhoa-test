<?php
/**
 * Thêm một hoặc nhiều module vào menu MANAGEMENT (Schedule, Teams, ...).
 *
 * Cách dùng:
 *   php add_module_to_management.php Calendar Teams
 *   php add_module_to_management.php Reports Documents Project ProjectTask
 *
 * Logic:
 * - Sidebar Vtiger 7 lấy danh sách module theo app từ bảng vtiger_app2tab (appname, tabid, sequence, visible).
 * - App MANAGEMENT phải có trong MenuStructure::getAppMenuList() (đã thêm trong MenuStructure.php).
 * - Script này chỉ thêm/cập nhật vtiger_app2tab cho appname = 'MANAGEMENT'.
 *
 * Sau khi chạy: Clear cache (Settings → Configuration → Clear Cache) rồi logout/login.
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';

$adb = PearDatabase::getInstance();
$appName = 'MANAGEMENT';

// Lấy danh sách module từ tham số dòng lệnh
$modulesToAdd = array_slice($argv, 1);
if (empty($modulesToAdd)) {
	echo "Cách dùng: php add_module_to_management.php <ModuleName> [ModuleName2 ...]\n";
	echo "Ví dụ:    php add_module_to_management.php Calendar Teams Reports\n";
	exit(1);
}

echo "==========================================\n";
echo "Thêm module vào menu MANAGEMENT\n";
echo "==========================================\n\n";

$moduleTabIds = array();
foreach ($modulesToAdd as $moduleName) {
	$moduleName = trim($moduleName);
	if ($moduleName === '') continue;
	$result = $adb->pquery("SELECT tabid, tablabel FROM vtiger_tab WHERE name = ?", array($moduleName));
	if ($adb->num_rows($result) > 0) {
		$tabid = $adb->query_result($result, 0, 'tabid');
		$tablabel = $adb->query_result($result, 0, 'tablabel');
		$moduleTabIds[$moduleName] = array('tabid' => $tabid, 'tablabel' => $tablabel);
		echo "  ✓ $moduleName: tabid=$tabid\n";
	} else {
		echo "  ✗ $moduleName: không tìm thấy trong vtiger_tab (bỏ qua)\n";
	}
}

if (empty($moduleTabIds)) {
	die("Không có module nào hợp lệ để thêm.\n");
}

// (Tùy chọn) Gỡ module khỏi app khác, chỉ giữ trong MANAGEMENT
$tabids = array_column($moduleTabIds, 'tabid');
$placeholders = implode(',', array_fill(0, count($tabids), '?'));
$adb->pquery(
	"DELETE FROM vtiger_app2tab WHERE tabid IN ($placeholders) AND appname != ?",
	array_merge($tabids, array($appName))
);
echo "\nĐã gỡ các module này khỏi app khác (chỉ còn trong MANAGEMENT).\n";

// Thêm vào MANAGEMENT với sequence tăng dần
$seqResult = $adb->pquery("SELECT MAX(sequence) AS max_seq FROM vtiger_app2tab WHERE appname = ?", array($appName));
$nextSeq = ($adb->num_rows($seqResult) > 0 && $adb->query_result($seqResult, 0, 'max_seq') !== null)
	? (int) $adb->query_result($seqResult, 0, 'max_seq') + 1 : 1;

foreach ($moduleTabIds as $moduleName => $info) {
	$tabid = $info['tabid'];
	$check = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", array($appName, $tabid));
	if ($adb->num_rows($check) == 0) {
		$adb->pquery(
			"INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
			array($appName, $tabid, $nextSeq, 1)
		);
		echo "  ✓ Đã thêm $moduleName vào MANAGEMENT (sequence: $nextSeq)\n";
	} else {
		$adb->pquery(
			"UPDATE vtiger_app2tab SET sequence = ?, visible = 1 WHERE appname = ? AND tabid = ?",
			array($nextSeq, $appName, $tabid)
		);
		echo "  ✓ Đã cập nhật $moduleName trong MANAGEMENT (sequence: $nextSeq)\n";
	}
	$nextSeq++;
}

echo "\nXong. Tiếp theo: Settings → Configuration → Clear Cache, rồi logout/login.\n";
