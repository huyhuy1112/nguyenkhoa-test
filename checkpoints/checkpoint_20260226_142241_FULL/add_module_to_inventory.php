<?php
/**
 * Thêm một hoặc nhiều module vào menu INVENTORY.
 *
 * Cách dùng:
 *   php add_module_to_inventory.php Products Services
 *   php add_module_to_inventory.php Invoice PurchaseOrder SalesOrder
 *
 * Logic:
 * - Sidebar Vtiger 7 lấy danh sách module theo app từ bảng vtiger_app2tab (appname, tabid, sequence, visible).
 * - App INVENTORY đã có trong MenuStructure::getAppMenuList().
 * - Script này chỉ thêm/cập nhật vtiger_app2tab cho appname = 'INVENTORY'.
 *
 * Các module thường có trong INVENTORY:
 * - Products, Services, Pricebooks, Vendors
 * - Invoice, PurchaseOrder, SalesOrder
 * - Quotes (có thể có trong cả SALES và INVENTORY)
 *
 * Sau khi chạy: Clear cache (Settings → Configuration → Clear Cache) rồi logout/login.
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';

$adb = PearDatabase::getInstance();
$appName = 'INVENTORY';

// Lấy danh sách module từ tham số dòng lệnh
$modulesToAdd = array_slice($argv, 1);
if (empty($modulesToAdd)) {
	echo "Cách dùng: php add_module_to_inventory.php <ModuleName> [ModuleName2 ...]\n";
	echo "Ví dụ:    php add_module_to_inventory.php Products Services Invoice\n";
	echo "\nCác module thường có trong INVENTORY:\n";
	echo "  - Products, Services, Pricebooks, Vendors\n";
	echo "  - Invoice, PurchaseOrder, SalesOrder\n";
	exit(1);
}

echo "==========================================\n";
echo "Thêm module vào menu INVENTORY\n";
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
		echo "  ✓ $moduleName: tabid=$tabid, label='$tablabel'\n";
	} else {
		echo "  ✗ $moduleName: không tìm thấy trong vtiger_tab (bỏ qua)\n";
	}
}

if (empty($moduleTabIds)) {
	die("Không có module nào hợp lệ để thêm.\n");
}

// Lưu ý: Không tự động xóa module khỏi app khác (vì một số module có thể nằm trong nhiều app)
// Ví dụ: Products có thể có trong cả SALES và INVENTORY
echo "\nLưu ý: Module có thể nằm trong nhiều app (ví dụ: Products trong cả SALES và INVENTORY).\n";
echo "Script này chỉ thêm vào INVENTORY, không xóa khỏi app khác.\n\n";

// Thêm vào INVENTORY với sequence tăng dần
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
		echo "  ✓ Đã thêm $moduleName vào INVENTORY (sequence: $nextSeq)\n";
	} else {
		$adb->pquery(
			"UPDATE vtiger_app2tab SET sequence = ?, visible = 1 WHERE appname = ? AND tabid = ?",
			array($nextSeq, $appName, $tabid)
		);
		echo "  ✓ Đã cập nhật $moduleName trong INVENTORY (sequence: $nextSeq)\n";
	}
	$nextSeq++;
}

echo "\nXong. Tiếp theo: Settings → Configuration → Clear Cache, rồi logout/login.\n";
