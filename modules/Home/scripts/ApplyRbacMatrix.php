<?php
/*+***********************************************************************************
 * Apply RBAC theo phòng ban (Excel khách Nguyễn Khoa).
 *
 * Profiles (Hồ sơ) — chỗ phân quyền chính:
 *   NK BGD           — CEO / Expert / Assistant (full)
 *   NK Sale Manager  — Kinh doanh trưởng
 *   NK Sale          — Sale
 *   NK KTT           — Kế toán trưởng
 *   NK Ke toan       — Kế toán
 *   NK Cung ung      — Trưởng cung ứng
 *   NK Kho           — Quản lý kho
 *
 * Run:
 *   docker exec vtiger_web php -f modules/Home/scripts/ApplyRbacMatrix.php
 * Idempotent. Không tạo user mới.
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'vtlib/Vtiger/Profile.php';
require_once 'modules/Users/CreateUserPrivilegeFile.php';
require_once 'modules/Home/helpers/RbacMatrix.php';

global $adb;

echo "=== Apply RBAC Matrix (phòng ban) ===\n";

$SOURCE_PROFILE_ID = 1; // Administrator — template clone

$ACTION = array(
	'Save' => 0,
	'EditView' => 1,
	'Delete' => 2,
	'index' => 3,
	'DetailView' => 4,
	'CreateView' => 7,
);
$UTIL_CONVERT_LEAD = 9;

/**
 * Module levels: full | view | crm_no_delete_lead | none
 * Module không liệt kê → deny (trừ clone_full).
 */
$MATRIX = array(
	'NK BGD' => array(
		'description' => 'BGĐ (CEO/Expert/Assistant) — xem & thao tác toàn hệ thống',
		'viewall' => true,
		'editall' => true,
		'clone_full' => true,
		'modules' => array(
			'Dashboard' => 'full',
			'Leads' => 'full',
			'Potentials' => 'full',
			'Contacts' => 'full',
			'Accounts' => 'full',
			'Quotes' => 'full',
			'SalesOrder' => 'full',
			'Invoice' => 'full',
			'Products' => 'full',
			'Services' => 'full',
			'ProductsServices' => 'full',
			'Warehouse' => 'full',
			'GoodsIssue' => 'full',
			'GoodsReceipt' => 'full',
			'Vendors' => 'full',
			'PurchaseOrder' => 'full',
			'HelpDesk' => 'full',
			'Calendar' => 'full',
			'Events' => 'full',
			'Documents' => 'full',
			'Reports' => 'full',
			'Emails' => 'full',
			'Teams' => 'full',
			'ModComments' => 'full',
		),
	),
	'NK Sale Manager' => array(
		'description' => 'Kinh doanh (TPKD) — menu Bán hàng full; Kho chỉ xem, không NXK',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'full',
			'Potentials' => 'full',
			'Contacts' => 'full',
			'Accounts' => 'full',
			'Quotes' => 'full',
			'SalesOrder' => 'full',
			'Invoice' => 'view',
			'Products' => 'full',
			'Services' => 'full',
			'ProductsServices' => 'full',
			'Warehouse' => 'view',
			'GoodsIssue' => 'none',
			'GoodsReceipt' => 'none',
			'Vendors' => 'none',
			'PurchaseOrder' => 'none',
			'HelpDesk' => 'full',
			'Calendar' => 'full',
			'Events' => 'full',
			'Documents' => 'full',
			'Reports' => 'full',
			'Emails' => 'full',
			'Teams' => 'full',
			'ModComments' => 'full',
		),
	),
	'NK Sale' => array(
		'description' => 'Kinh doanh (Sale) — Bán hàng; Kho chỉ xem; không NXK',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'crm_no_delete_lead',
			'Potentials' => 'full',
			'Contacts' => 'full',
			'Accounts' => 'full',
			'Quotes' => 'full',
			'SalesOrder' => 'full',
			'Invoice' => 'view',
			'Products' => 'view',
			'Services' => 'view',
			'ProductsServices' => 'view',
			'Warehouse' => 'view',
			'GoodsIssue' => 'none',
			'GoodsReceipt' => 'none',
			'Vendors' => 'none',
			'PurchaseOrder' => 'none',
			'HelpDesk' => 'full',
			'Calendar' => 'full',
			'Events' => 'full',
			'Documents' => 'full',
			'Reports' => 'view',
			'Emails' => 'full',
			'Teams' => 'full',
			'ModComments' => 'full',
		),
	),
	'NK KTT' => array(
		'description' => 'Kế toán trưởng — Hóa đơn/báo cáo; không bán hàng tạo mới; không NXK',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'none',
			'Potentials' => 'view',
			'Contacts' => 'view',
			'Accounts' => 'view',
			'Quotes' => 'view',
			'SalesOrder' => 'view',
			'Invoice' => 'full',
			'Products' => 'view',
			'Services' => 'view',
			'ProductsServices' => 'view',
			'Warehouse' => 'view',
			'GoodsIssue' => 'none',
			'GoodsReceipt' => 'none',
			'Vendors' => 'view',
			'PurchaseOrder' => 'view',
			'HelpDesk' => 'view',
			'Calendar' => 'full',
			'Events' => 'full',
			'Documents' => 'full',
			'Reports' => 'full',
			'Emails' => 'full',
			'Teams' => 'full',
			'ModComments' => 'full',
		),
	),
	'NK Ke toan' => array(
		'description' => 'Kế toán — Hóa đơn CRUD; không menu bán hàng tạo; không NXK',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'none',
			'Potentials' => 'none',
			'Contacts' => 'view',
			'Accounts' => 'view',
			'Quotes' => 'view',
			'SalesOrder' => 'view',
			'Invoice' => 'full',
			'Products' => 'view',
			'Services' => 'view',
			'ProductsServices' => 'view',
			'Warehouse' => 'view',
			'GoodsIssue' => 'none',
			'GoodsReceipt' => 'none',
			'Vendors' => 'view',
			'PurchaseOrder' => 'view',
			'HelpDesk' => 'view',
			'Calendar' => 'full',
			'Events' => 'full',
			'Documents' => 'full',
			'Reports' => 'view',
			'Emails' => 'view',
			'Teams' => 'full',
			'ModComments' => 'full',
		),
	),
	'NK Cung ung' => array(
		'description' => 'Cung ứng — Kho/NCC/PO full; không menu Bán hàng',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'none',
			'Potentials' => 'none',
			'Contacts' => 'none',
			'Accounts' => 'none',
			'Quotes' => 'none',
			'SalesOrder' => 'view',
			'Invoice' => 'none',
			'Products' => 'full',
			'Services' => 'full',
			'ProductsServices' => 'full',
			'Warehouse' => 'full',
			'GoodsIssue' => 'full',
			'GoodsReceipt' => 'full',
			'Vendors' => 'full',
			'PurchaseOrder' => 'full',
			'HelpDesk' => 'full',
			'Calendar' => 'full',
			'Events' => 'full',
			'Documents' => 'full',
			'Reports' => 'view',
			'Emails' => 'view',
			'Teams' => 'full',
			'ModComments' => 'full',
		),
	),
	'NK Kho' => array(
		'description' => 'Kho vận — chỉ Kho/NXK/SP; không menu Bán hàng',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'none',
			'Potentials' => 'none',
			'Contacts' => 'none',
			'Accounts' => 'none',
			'Quotes' => 'none',
			'SalesOrder' => 'none',
			'Invoice' => 'none',
			'Products' => 'full',
			'Services' => 'full',
			'ProductsServices' => 'full',
			'Warehouse' => 'full',
			'GoodsIssue' => 'full',
			'GoodsReceipt' => 'full',
			'Vendors' => 'view',
			'PurchaseOrder' => 'view',
			'HelpDesk' => 'full',
			'Calendar' => 'full',
			'Events' => 'full',
			'Documents' => 'full',
			'Reports' => 'view',
			'Emails' => 'view',
			'Teams' => 'full',
			'ModComments' => 'full',
		),
	),
);

function rbac_get_profile_id_by_name($name) {
	global $adb;
	$r = $adb->pquery('SELECT profileid FROM vtiger_profile WHERE profilename = ?', array($name));
	if ($adb->num_rows($r)) {
		return (int) $adb->query_result($r, 0, 'profileid');
	}
	return 0;
}

function rbac_clone_profile($sourceId, $name, $description) {
	global $adb;
	$profileId = rbac_get_profile_id_by_name($name);
	if ($profileId) {
		echo "  Profile exists: $name (#$profileId) — refresh from Administrator clone\n";
		$adb->pquery('UPDATE vtiger_profile SET description=? WHERE profileid=?', array($description, $profileId));
		$adb->pquery('DELETE FROM vtiger_profile2tab WHERE profileid=?', array($profileId));
		$adb->pquery('DELETE FROM vtiger_profile2standardpermissions WHERE profileid=?', array($profileId));
		$adb->pquery('DELETE FROM vtiger_profile2utility WHERE profileid=?', array($profileId));
		$adb->pquery('DELETE FROM vtiger_profile2field WHERE profileid=?', array($profileId));
		$adb->pquery('DELETE FROM vtiger_profile2globalpermissions WHERE profileid=?', array($profileId));
	} else {
		$profileId = (int) $adb->getUniqueId('vtiger_profile');
		$adb->pquery(
			'INSERT INTO vtiger_profile(profileid, profilename, description, directly_related_to_role) VALUES (?,?,?,?)',
			array($profileId, $name, $description, 0)
		);
		echo "  Created profile: $name (#$profileId)\n";
	}

	$adb->pquery(
		"INSERT INTO vtiger_profile2tab (profileid, tabid, permissions)
		 SELECT ?, tabid, permissions FROM vtiger_profile2tab WHERE profileid = ?",
		array($profileId, $sourceId)
	);
	$adb->pquery(
		"INSERT INTO vtiger_profile2standardpermissions (profileid, tabid, operation, permissions)
		 SELECT ?, tabid, operation, permissions FROM vtiger_profile2standardpermissions WHERE profileid = ?",
		array($profileId, $sourceId)
	);
	$adb->pquery(
		"INSERT INTO vtiger_profile2utility (profileid, tabid, activityid, permission)
		 SELECT ?, tabid, activityid, permission FROM vtiger_profile2utility WHERE profileid = ?",
		array($profileId, $sourceId)
	);
	$adb->pquery(
		"INSERT INTO vtiger_profile2field (profileid, tabid, fieldid, visible, readonly)
		 SELECT ?, tabid, fieldid, visible, readonly FROM vtiger_profile2field WHERE profileid = ?",
		array($profileId, $sourceId)
	);

	return $profileId;
}

function rbac_set_global($profileId, $viewall, $editall) {
	global $adb;
	$adb->pquery('DELETE FROM vtiger_profile2globalpermissions WHERE profileid=?', array($profileId));
	$viewVal = $viewall ? 0 : 1;
	$editVal = $editall ? 0 : 1;
	$adb->pquery(
		'INSERT INTO vtiger_profile2globalpermissions(profileid, globalactionid, globalactionpermission) VALUES (?,?,?)',
		array($profileId, 1, $viewVal)
	);
	$adb->pquery(
		'INSERT INTO vtiger_profile2globalpermissions(profileid, globalactionid, globalactionpermission) VALUES (?,?,?)',
		array($profileId, 2, $editVal)
	);
}

function rbac_tab_id($moduleName) {
	$id = getTabid($moduleName);
	return $id ? (int) $id : 0;
}

function rbac_set_module_level($profileId, $moduleName, $level, $ACTION, $UTIL_CONVERT_LEAD) {
	global $adb;
	$tabId = rbac_tab_id($moduleName);
	if (!$tabId) {
		echo "    skip missing module: $moduleName\n";
		return;
	}

	$allowTab = ($level !== 'none') ? 0 : 1;
	$chk = $adb->pquery('SELECT 1 FROM vtiger_profile2tab WHERE profileid=? AND tabid=?', array($profileId, $tabId));
	if ($adb->num_rows($chk) == 0) {
		$adb->pquery('INSERT INTO vtiger_profile2tab(profileid, tabid, permissions) VALUES (?,?,?)', array($profileId, $tabId, $allowTab));
	} else {
		$adb->pquery('UPDATE vtiger_profile2tab SET permissions=? WHERE profileid=? AND tabid=?', array($allowTab, $profileId, $tabId));
	}

	$allow = array();
	foreach ($ACTION as $name => $op) {
		$allow[$op] = 1;
	}

	if ($level === 'full' || $level === 'crm_no_delete_lead') {
		foreach ($ACTION as $op) {
			$allow[$op] = 0;
		}
		if ($level === 'crm_no_delete_lead') {
			$allow[$ACTION['Delete']] = 1;
		}
	} elseif ($level === 'view') {
		$allow[$ACTION['index']] = 0;
		$allow[$ACTION['DetailView']] = 0;
	}

	foreach ($allow as $operation => $perm) {
		$chk = $adb->pquery(
			'SELECT 1 FROM vtiger_profile2standardpermissions WHERE profileid=? AND tabid=? AND operation=?',
			array($profileId, $tabId, $operation)
		);
		if ($adb->num_rows($chk) == 0) {
			$adb->pquery(
				'INSERT INTO vtiger_profile2standardpermissions(profileid, tabid, operation, permissions) VALUES (?,?,?,?)',
				array($profileId, $tabId, $operation, $perm)
			);
		} else {
			$adb->pquery(
				'UPDATE vtiger_profile2standardpermissions SET permissions=? WHERE profileid=? AND tabid=? AND operation=?',
				array($perm, $profileId, $tabId, $operation)
			);
		}
	}

	if ($moduleName === 'Leads') {
		$convertPerm = ($level === 'full' || $level === 'crm_no_delete_lead') ? 0 : 1;
		$chk = $adb->pquery(
			'SELECT 1 FROM vtiger_profile2utility WHERE profileid=? AND tabid=? AND activityid=?',
			array($profileId, $tabId, $UTIL_CONVERT_LEAD)
		);
		if ($adb->num_rows($chk) == 0) {
			$adb->pquery(
				'INSERT INTO vtiger_profile2utility(profileid, tabid, activityid, permission) VALUES (?,?,?,?)',
				array($profileId, $tabId, $UTIL_CONVERT_LEAD, $convertPerm)
			);
		} else {
			$adb->pquery(
				'UPDATE vtiger_profile2utility SET permission=? WHERE profileid=? AND tabid=? AND activityid=?',
				array($convertPerm, $profileId, $tabId, $UTIL_CONVERT_LEAD)
			);
		}
	}
}

function rbac_deny_unlisted_modules($profileId, $listedModules, $ACTION) {
	global $adb;
	$listedTabIds = array();
	foreach ($listedModules as $mod => $_level) {
		$tid = rbac_tab_id($mod);
		if ($tid) {
			$listedTabIds[$tid] = true;
		}
	}
	$homeId = rbac_tab_id('Home');
	if ($homeId) {
		$listedTabIds[$homeId] = true;
	}

	$res = $adb->pquery('SELECT tabid FROM vtiger_profile2tab WHERE profileid=?', array($profileId));
	$rows = $adb->num_rows($res);
	for ($i = 0; $i < $rows; $i++) {
		$tabId = (int) $adb->query_result($res, $i, 'tabid');
		if (isset($listedTabIds[$tabId])) {
			continue;
		}
		$adb->pquery('UPDATE vtiger_profile2tab SET permissions=1 WHERE profileid=? AND tabid=?', array($profileId, $tabId));
		$adb->pquery(
			'UPDATE vtiger_profile2standardpermissions SET permissions=1 WHERE profileid=? AND tabid=?',
			array($profileId, $tabId)
		);
		$adb->pquery(
			'UPDATE vtiger_profile2utility SET permission=1 WHERE profileid=? AND tabid=?',
			array($profileId, $tabId)
		);
	}
}

function rbac_ensure_role($roleName, $parentRoleId, $profileId) {
	$parent = Settings_Roles_Record_Model::getInstanceById($parentRoleId);
	if (!$parent) {
		throw new Exception("Parent role $parentRoleId not found");
	}

	$existing = Settings_Roles_Record_Model::getInstanceByName($roleName);
	if ($existing) {
		$roleId = $existing->getId();
		$existing->set('profileIds', array($profileId));
		$existing->set('allowassignedrecordsto', $existing->get('allowassignedrecordsto') ?: 1);
		$existing->save();
		echo "  Role exists: $roleName ($roleId) — profile linked #$profileId\n";
		return $roleId;
	}

	$role = new Settings_Roles_Record_Model();
	$role->set('rolename', $roleName);
	$role->set('profileIds', array($profileId));
	$role->set('allowassignedrecordsto', 1);
	$parent->addChildRole($role);
	$created = Settings_Roles_Record_Model::getInstanceByName($roleName);
	$roleId = $created ? $created->getId() : '';
	echo "  Created role: $roleName ($roleId) under $parentRoleId → profile #$profileId\n";
	return $roleId;
}

function rbac_rename_role_if($fromName, $toName) {
	global $adb;
	$from = Settings_Roles_Record_Model::getInstanceByName($fromName);
	if (!$from) {
		return false;
	}
	$to = Settings_Roles_Record_Model::getInstanceByName($toName);
	if ($to && $to->getId() !== $from->getId()) {
		echo "  Skip rename '$fromName' → '$toName' (target already exists as {$to->getId()})\n";
		return false;
	}
	$adb->pquery('UPDATE vtiger_role SET rolename=? WHERE roleid=?', array($toName, $from->getId()));
	echo "  Renamed role: $fromName → $toName ({$from->getId()})\n";
	return true;
}

function rbac_rename_profile_if($fromName, $toName) {
	global $adb;
	$fromId = rbac_get_profile_id_by_name($fromName);
	if (!$fromId) {
		return false;
	}
	$toId = rbac_get_profile_id_by_name($toName);
	if ($toId && $toId !== $fromId) {
		echo "  Skip rename profile '$fromName' → '$toName' (target exists #$toId)\n";
		return false;
	}
	$adb->pquery('UPDATE vtiger_profile SET profilename=? WHERE profileid=?', array($toName, $fromId));
	echo "  Renamed profile: $fromName → $toName (#$fromId)\n";
	return true;
}

function rbac_hide_sharing_access_menu() {
	global $adb;
	// Vtiger: active=0 hiển thị, active=1 ẩn
	$r = $adb->pquery(
		"SELECT fieldid, name, active FROM vtiger_settings_field WHERE name = ? OR linkto LIKE ?",
		array('LBL_SHARING_ACCESS', '%module=SharingAccess%')
	);
	if (!$adb->num_rows($r)) {
		echo "  SharingAccess settings field not found — skip hide\n";
		return;
	}
	for ($i = 0; $i < $adb->num_rows($r); $i++) {
		$fieldId = (int) $adb->query_result($r, $i, 'fieldid');
		$name = $adb->query_result($r, $i, 'name');
		$adb->pquery('UPDATE vtiger_settings_field SET active = 1 WHERE fieldid = ?', array($fieldId));
		echo "  Hidden settings menu: $name (#$fieldId)\n";
	}
}

// ---------------------------------------------------------------------------
// 0) Legacy renames → tên mới
// ---------------------------------------------------------------------------
echo "\n-- Legacy rename --\n";
rbac_rename_profile_if('NK Admin', 'NK BGD');
rbac_rename_profile_if('NK Supervisor', 'NK Sale Manager');
rbac_rename_profile_if('NK Kế toán', 'NK Ke toan');

rbac_rename_role_if('Vice President', 'Expert');
rbac_rename_role_if('Admin', 'Expert');
rbac_rename_role_if('Sales Manager', 'Sale Manager');
rbac_rename_role_if('Supervisor', 'Sale Manager');
rbac_rename_role_if('Sales Person', 'Sale');

// ---------------------------------------------------------------------------
// 1) Profiles
// ---------------------------------------------------------------------------
echo "\n-- Profiles --\n";
$profileIds = array();
foreach ($MATRIX as $profileName => $cfg) {
	$pid = rbac_clone_profile($SOURCE_PROFILE_ID, $profileName, $cfg['description']);
	rbac_set_global($pid, !empty($cfg['viewall']), !empty($cfg['editall']));
	if (empty($cfg['clone_full'])) {
		rbac_deny_unlisted_modules($pid, $cfg['modules'], $ACTION);
	}
	foreach ($cfg['modules'] as $mod => $level) {
		rbac_set_module_level($pid, $mod, $level, $ACTION, $UTIL_CONVERT_LEAD);
	}
	$profileIds[$profileName] = $pid;
	echo "  Applied matrix on $profileName\n";
}

// ---------------------------------------------------------------------------
// 2) Roles (cây phòng ban dưới CEO)
// ---------------------------------------------------------------------------
echo "\n-- Roles --\n";
$ceo = Settings_Roles_Record_Model::getInstanceByName('CEO');
if (!$ceo) {
	$ceo = Settings_Roles_Record_Model::getInstanceById('H2');
}
if (!$ceo) {
	fwrite(STDERR, "ERROR: CEO role not found\n");
	exit(1);
}
$ceoId = $ceo->getId();
echo "  Parent CEO: $ceoId\n";

/**
 * Ensure role sits under expected parent (idempotent).
 * Reads/writes vtiger_role directly to avoid Settings_Roles_Record_Model cache.
 */
function rbac_ensure_parent($roleId, $parentRoleId, $label) {
	global $adb;

	$parentRow = $adb->pquery(
		'SELECT parentrole, depth FROM vtiger_role WHERE roleid=?',
		array($parentRoleId)
	);
	$roleRow = $adb->pquery(
		'SELECT parentrole, depth FROM vtiger_role WHERE roleid=?',
		array($roleId)
	);
	if (!$adb->num_rows($parentRow) || !$adb->num_rows($roleRow)) {
		echo "  Skip reparent $label — role/parent missing\n";
		return;
	}

	$parentString = $adb->query_result($parentRow, 0, 'parentrole');
	$parentDepth = (int) $adb->query_result($parentRow, 0, 'depth');
	$current = $adb->query_result($roleRow, 0, 'parentrole');
	$currentDepth = (int) $adb->query_result($roleRow, 0, 'depth');
	$expected = $parentString . '::' . $roleId;

	if ($current === $expected) {
		echo "  Parent OK: $label under $parentRoleId\n";
		return;
	}
	if (strpos($parentString, '::' . $roleId) !== false || strpos($parentString, $roleId . '::') === 0) {
		echo "  Skip reparent $label — would create cycle\n";
		return;
	}

	$oldPrefix = $current;
	$newDepth = $parentDepth + 1;
	$depthDiff = $newDepth - $currentDepth;

	$adb->pquery(
		'UPDATE vtiger_role SET parentrole=?, depth=? WHERE roleid=?',
		array($expected, $newDepth, $roleId)
	);

	$kids = $adb->pquery(
		'SELECT roleid, parentrole, depth FROM vtiger_role WHERE parentrole LIKE ? AND roleid <> ?',
		array($oldPrefix . '::%', $roleId)
	);
	for ($i = 0; $i < $adb->num_rows($kids); $i++) {
		$kidId = $adb->query_result($kids, $i, 'roleid');
		$kidParent = $adb->query_result($kids, $i, 'parentrole');
		$kidDepth = (int) $adb->query_result($kids, $i, 'depth') + $depthDiff;
		$newKidParent = $expected . substr($kidParent, strlen($oldPrefix));
		$adb->pquery(
			'UPDATE vtiger_role SET parentrole=?, depth=? WHERE roleid=?',
			array($newKidParent, $kidDepth, $kidId)
		);
	}
	echo "  Moved $label ($roleId) → under $parentRoleId ($expected)\n";
}

$roleIds = array();
// BGĐ
$roleIds['Expert'] = rbac_ensure_role('Expert', $ceoId, $profileIds['NK BGD']);
$roleIds['Assistant'] = rbac_ensure_role('Assistant', $ceoId, $profileIds['NK BGD']);
// Kinh doanh
$roleIds['Sale Manager'] = rbac_ensure_role('Sale Manager', $ceoId, $profileIds['NK Sale Manager']);
$roleIds['Sale'] = rbac_ensure_role('Sale', $roleIds['Sale Manager'], $profileIds['NK Sale']);
// Kế toán
$roleIds['KTT'] = rbac_ensure_role('KTT', $ceoId, $profileIds['NK KTT']);
$roleIds['Ke toan'] = rbac_ensure_role('Ke toan', $roleIds['KTT'], $profileIds['NK Ke toan']);
// Cung ứng / Kho
$roleIds['Cung ung'] = rbac_ensure_role('Cung ung', $ceoId, $profileIds['NK Cung ung']);
$roleIds['Kho'] = rbac_ensure_role('Kho', $roleIds['Cung ung'], $profileIds['NK Kho']);

echo "\n-- Fix role hierarchy --\n";
rbac_ensure_parent($roleIds['Expert'], $ceoId, 'Expert');
rbac_ensure_parent($roleIds['Assistant'], $ceoId, 'Assistant');
rbac_ensure_parent($roleIds['Sale Manager'], $ceoId, 'Sale Manager');
rbac_ensure_parent($roleIds['Sale'], $roleIds['Sale Manager'], 'Sale');
rbac_ensure_parent($roleIds['KTT'], $ceoId, 'KTT');
rbac_ensure_parent($roleIds['Ke toan'], $roleIds['KTT'], 'Ke toan');
rbac_ensure_parent($roleIds['Cung ung'], $ceoId, 'Cung ung');
rbac_ensure_parent($roleIds['Kho'], $roleIds['Cung ung'], 'Kho');

// Fix legacy HTML-encoded accountant role name
$broken = $adb->pquery(
	"SELECT roleid, rolename FROM vtiger_role WHERE (rolename LIKE ? OR rolename LIKE ?) AND rolename <> ?",
	array('%aacute%', '%amp;aacute%', 'Ke toan')
);
$canonicalKeToan = Settings_Roles_Record_Model::getInstanceByName('Ke toan');
for ($i = 0; $i < $adb->num_rows($broken); $i++) {
	$rid = $adb->query_result($broken, $i, 'roleid');
	if ($canonicalKeToan && $canonicalKeToan->getId() !== $rid) {
		$usersOnBroken = $adb->pquery('SELECT 1 FROM vtiger_user2role WHERE roleid=? LIMIT 1', array($rid));
		if ($adb->num_rows($usersOnBroken) == 0) {
			$adb->pquery('DELETE FROM vtiger_role2profile WHERE roleid=?', array($rid));
			$adb->pquery('DELETE FROM vtiger_role2picklist WHERE roleid=?', array($rid));
			$adb->pquery('DELETE FROM vtiger_role WHERE roleid=?', array($rid));
			echo "  Removed unused encoded duplicate role $rid\n";
		} else {
			$adb->pquery('UPDATE vtiger_role SET rolename=? WHERE roleid=?', array('Ke toan (legacy)', $rid));
			echo "  Renamed encoded role $rid (has users) → Ke toan (legacy)\n";
		}
	} else {
		$adb->pquery('UPDATE vtiger_role SET rolename=? WHERE roleid=?', array('Ke toan', $rid));
		echo "  Fixed encoded role $rid → Ke toan\n";
		$roleIds['Ke toan'] = $rid;
	}
}

// CEO + Administrator = full (executive)
$adb->pquery('DELETE FROM vtiger_role2profile WHERE roleid=?', array($ceoId));
$adb->pquery('INSERT INTO vtiger_role2profile(roleid, profileid) VALUES (?,?)', array($ceoId, $SOURCE_PROFILE_ID));
echo "  CEO → Administrator profile (#$SOURCE_PROFILE_ID)\n";

// ---------------------------------------------------------------------------
// 3) Ẩn menu Quyền truy cập (SharingAccess) — phân quyền chính = Hồ sơ
// ---------------------------------------------------------------------------
echo "\n-- Hide SharingAccess menu --\n";
rbac_hide_sharing_access_menu();

// ---------------------------------------------------------------------------
// 4) Rebuild privileges (không tạo user)
// ---------------------------------------------------------------------------
echo "\n-- Rebuild user_privileges --\n";
$userRes = $adb->pquery('SELECT id, user_name FROM vtiger_users WHERE deleted = 0', array());
for ($u = 0; $u < $adb->num_rows($userRes); $u++) {
	$userId = $adb->query_result($userRes, $u, 'id');
	$userName = $adb->query_result($userRes, $u, 'user_name');
	createUserPrivilegesfile($userId);
	createUserSharingPrivilegesfile($userId);
	echo "  Rebuilt #$userId ($userName)\n";
}

echo "\n=== Done ===\n";
echo "Roles: " . json_encode($roleIds, JSON_UNESCAPED_UNICODE) . "\n";
echo "Profiles: " . json_encode($profileIds, JSON_UNESCAPED_UNICODE) . "\n";
echo "Chưa tạo user. Gán user vào role trong Settings → Người sử dụng khi sẵn sàng.\n";
echo "Phân quyền module: Cài đặt → Hồ sơ (NK BGD / Sale Manager / Sale / KTT / Ke toan / Cung ung / Kho).\n";
