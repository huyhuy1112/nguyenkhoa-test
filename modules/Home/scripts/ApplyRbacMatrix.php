<?php
/*+***********************************************************************************
 * Apply RBAC matrix: 5 Profiles + 5 Roles (keep Vtiger RBAC tables).
 *
 * Run inside app container:
 *   docker exec vtiger_web php -f modules/Home/scripts/ApplyRbacMatrix.php
 *
 * Idempotent: safe to re-run. Does not drop existing Roles/Profiles.
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'vtlib/Vtiger/Profile.php';
require_once 'modules/Users/CreateUserPrivilegeFile.php';
require_once 'modules/Home/helpers/RbacMatrix.php';

global $adb;

echo "=== Apply RBAC Matrix ===\n";

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
 * Matrix module levels per persona profile.
 * full | view | crm_no_delete_lead | none
 * Unlisted entity modules are denied on non-Admin profiles after clone wipe.
 */
$MATRIX = array(
	'NK Admin' => array(
		'description' => 'Ma trận: Admin — full nghiệp vụ (Settings Users vẫn cần is_admin)',
		'viewall' => false,
		'editall' => false,
		'clone_full' => true, // keep Administrator clone, then force-fill inventory CRUD
		'modules' => array(
			'Dashboard' => 'full',
			'Warehouse' => 'full',
			'GoodsIssue' => 'full',
			'GoodsReceipt' => 'full',
			'Products' => 'full',
			'Services' => 'full',
			'ProductsServices' => 'full',
			'Leads' => 'full',
			'Potentials' => 'full',
			'Contacts' => 'full',
			'Accounts' => 'full',
			'SalesOrder' => 'full',
			'Reports' => 'full',
			'Teams' => 'full',
			'Events' => 'full',
			'ModComments' => 'full',
		),
	),
	'NK Supervisor' => array(
		'description' => 'Ma trận: Supervisor — CRM+Đơn+SP+xem tồn; không NXK',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'full',
			'Potentials' => 'full',
			'Contacts' => 'full',
			'Accounts' => 'full',
			'SalesOrder' => 'full',
			'Quotes' => 'full',
			'Calendar' => 'full',
			'Events' => 'full',
			'Documents' => 'full',
			'Products' => 'full',
			'Services' => 'full',
			'ProductsServices' => 'full',
			'Warehouse' => 'view',
			'GoodsIssue' => 'none',
			'GoodsReceipt' => 'none',
			'Reports' => 'full',
			'Invoice' => 'view',
			'HelpDesk' => 'full',
			'Emails' => 'full',
			'Teams' => 'full',
			'ModComments' => 'full',
		),
	),
	'NK Sale' => array(
		'description' => 'Ma trận: Sale — CRM trừ xóa Lead; Đơn; xem tồn; không SP',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'crm_no_delete_lead',
			'Potentials' => 'full',
			'Contacts' => 'full',
			'Accounts' => 'full',
			'SalesOrder' => 'full',
			'Quotes' => 'full',
			'Calendar' => 'full',
			'Events' => 'full',
			'Documents' => 'full',
			'Products' => 'none',
			'Services' => 'none',
			'ProductsServices' => 'none',
			'Warehouse' => 'view',
			'GoodsIssue' => 'none',
			'GoodsReceipt' => 'none',
			'Reports' => 'view',
			'Invoice' => 'view',
			'HelpDesk' => 'full',
			'Emails' => 'full',
			'Teams' => 'full',
			'ModComments' => 'full',
		),
	),
	'NK Ke toan' => array(
		'description' => 'Ma tran: Ke toan — Dashboard + ton + SP; khong CRM/Don',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'none',
			'Potentials' => 'none',
			'Contacts' => 'none',
			'Accounts' => 'none',
			'SalesOrder' => 'none',
			'Quotes' => 'none',
			'Calendar' => 'view',
			'Events' => 'view',
			'Documents' => 'view',
			'Products' => 'full',
			'Services' => 'full',
			'ProductsServices' => 'full',
			'Warehouse' => 'view',
			'GoodsIssue' => 'none',
			'GoodsReceipt' => 'none',
			'Reports' => 'view',
			'Invoice' => 'view',
			'HelpDesk' => 'none',
			'ModComments' => 'view',
		),
	),
	'NK Kho' => array(
		'description' => 'Ma trận: Kho — xem Đơn + full tồn/NXK + SP',
		'viewall' => false,
		'editall' => false,
		'modules' => array(
			'Dashboard' => 'view',
			'Leads' => 'none',
			'Potentials' => 'none',
			'Contacts' => 'none',
			'Accounts' => 'none',
			'SalesOrder' => 'view',
			'Quotes' => 'none',
			'Calendar' => 'view',
			'Events' => 'view',
			'Documents' => 'view',
			'Products' => 'full',
			'Services' => 'full',
			'ProductsServices' => 'full',
			'Warehouse' => 'full',
			'GoodsIssue' => 'full',
			'GoodsReceipt' => 'full',
			'Reports' => 'view',
			'Invoice' => 'none',
			'HelpDesk' => 'none',
			'Vendors' => 'view',
			'PurchaseOrder' => 'view',
			'ModComments' => 'view',
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
	// 0 = permitted (ON), 1 = not permitted (OFF)
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
		$allow[$op] = 1; // deny by default
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
	// none → all deny (already 1)

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

	// ConvertLead utility
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
	// Always keep Home accessible if present
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
	global $adb;
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
	// save() does not set roleid on the model — reload by name
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

// ---------------------------------------------------------------------------
// 1) Profiles
// ---------------------------------------------------------------------------
echo "\n-- Profiles --\n";
// Rename legacy accented profile name → ASCII (avoids Vtiger &aacute; mangling)
$legacyProf = $adb->pquery('SELECT profileid FROM vtiger_profile WHERE profilename = ?', array('NK Kế toán'));
if ($adb->num_rows($legacyProf)) {
	$lp = (int) $adb->query_result($legacyProf, 0, 'profileid');
	$existsAscii = rbac_get_profile_id_by_name('NK Ke toan');
	if (!$existsAscii) {
		$adb->pquery('UPDATE vtiger_profile SET profilename=? WHERE profileid=?', array('NK Ke toan', $lp));
		echo "  Renamed profile NK Kế toán → NK Ke toan (#$lp)\n";
	}
}

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
// 2) Roles (under CEO)
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

// Align legacy default roles with matrix names (idempotent)
rbac_rename_role_if('Vice President', 'Admin');
rbac_rename_role_if('Sales Manager', 'Supervisor');
rbac_rename_role_if('Sales Person', 'Sale');

$roleIds = array();
$roleIds['Admin'] = rbac_ensure_role('Admin', $ceoId, $profileIds['NK Admin']);
$roleIds['Supervisor'] = rbac_ensure_role('Supervisor', $roleIds['Admin'], $profileIds['NK Supervisor']);
$roleIds['Sale'] = rbac_ensure_role('Sale', $roleIds['Supervisor'], $profileIds['NK Sale']);
// ASCII role name avoids Vtiger HTML-entity mangling of Vietnamese
$roleIds['Ke toan'] = rbac_ensure_role('Ke toan', $roleIds['Admin'], $profileIds['NK Ke toan']);
$roleIds['Kho'] = rbac_ensure_role('Kho', $roleIds['Admin'], $profileIds['NK Kho']);

// Fix legacy HTML-encoded accountant role name from earlier runs (do not create duplicate)
$broken = $adb->pquery(
	"SELECT roleid, rolename FROM vtiger_role WHERE (rolename LIKE ? OR rolename LIKE ?) AND rolename <> ?",
	array('%aacute%', '%amp;aacute%', 'Ke toan')
);
$canonicalKeToan = Settings_Roles_Record_Model::getInstanceByName('Ke toan');
for ($i = 0; $i < $adb->num_rows($broken); $i++) {
	$rid = $adb->query_result($broken, $i, 'roleid');
	if ($canonicalKeToan && $canonicalKeToan->getId() !== $rid) {
		// Prefer keeping existing Ke toan; drop broken duplicate if unused
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

// CEO keeps Administrator profile (executive)
$adb->pquery('DELETE FROM vtiger_role2profile WHERE roleid=?', array($ceoId));
$adb->pquery('INSERT INTO vtiger_role2profile(roleid, profileid) VALUES (?,?)', array($ceoId, $SOURCE_PROFILE_ID));
echo "  CEO → Administrator profile (#$SOURCE_PROFILE_ID)\n";

// ---------------------------------------------------------------------------
// 3) Demo users for Kế toán / Kho if missing (optional, inactive credentials)
// ---------------------------------------------------------------------------
echo "\n-- Sample user assignment --\n";
function rbac_ensure_demo_user($userName, $firstName, $roleId) {
	global $adb;
	$r = $adb->pquery('SELECT id FROM vtiger_users WHERE user_name=? AND deleted=0', array($userName));
	if ($adb->num_rows($r)) {
		$userId = (int) $adb->query_result($r, 0, 'id');
		$adb->pquery('UPDATE vtiger_user2role SET roleid=? WHERE userid=?', array($roleId, $userId));
		echo "  Linked existing user $userName (#$userId) → $roleId\n";
		return $userId;
	}
	echo "  No user '$userName' — skip create (assign manually in Settings → Users)\n";
	return 0;
}

rbac_ensure_demo_user('ketoan', 'Ke toan', $roleIds['Ke toan']);
rbac_ensure_demo_user('kho', 'Kho', $roleIds['Kho']);
rbac_ensure_demo_user('sale', 'Sale', $roleIds['Sale']);
rbac_ensure_demo_user('supervisor', 'Supervisor', $roleIds['Supervisor']);

// Map spare Organization (H1) users onto empty matrix roles for smoke/demo
function rbac_assign_if_on_role($userId, $fromRoleId, $toRoleId, $label) {
	global $adb;
	$r = $adb->pquery('SELECT roleid FROM vtiger_user2role WHERE userid=?', array($userId));
	if (!$adb->num_rows($r)) {
		return;
	}
	$current = $adb->query_result($r, 0, 'roleid');
	if ($current !== $fromRoleId) {
		echo "  Skip user #$userId (role $current, want $fromRoleId)\n";
		return;
	}
	$adb->pquery('UPDATE vtiger_user2role SET roleid=? WHERE userid=?', array($toRoleId, $userId));
	echo "  Assigned user #$userId → $label ($toRoleId)\n";
}
rbac_assign_if_on_role(22, 'H1', $roleIds['Ke toan'], 'Ke toan');
rbac_assign_if_on_role(23, 'H1', $roleIds['Kho'], 'Kho');

// Ensure users already on renamed roles get privilege rebuild (role id unchanged on rename)
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
echo "Gán user vào role Admin/Supervisor/Sale/Kế toán/Kho trong Settings nếu chưa có.\n";
