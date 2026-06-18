<?php
/*+***********************************************************************************
 * Fix ProductsServices permission: visible to all profiles, allow create/edit/view/delete.
 * Run from vtiger root: php -f modules/ProductsServices/scripts/FixPermissions.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'vtlib/Vtiger/Profile.php';

$moduleName = 'ProductsServices';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

echo "=== Fix Permissions: $moduleName ===\n";

global $adb;

// 1) Register in vtiger_ws_entity (fixes "Permission denied for name : ProductsServices")
require_once 'include/Webservices/Utils.php';
vtws_addDefaultModuleTypeEntity($moduleName);
echo "Registered in vtiger_ws_entity.\n";

// 2) Ensure module is visible (presence = 0)
$adb->pquery("UPDATE vtiger_tab SET presence = 0 WHERE name = ?", array($moduleName));
echo "Module set visible.\n";

// 3) Assign tab to all profiles with full permissions (permissions = 0 means allow)
$tabId = $module->id;
$profileIds = Vtiger_Profile::getAllIds();
foreach ($profileIds as $profileid) {
	$chk = $adb->pquery("SELECT 1 FROM vtiger_profile2tab WHERE profileid = ? AND tabid = ?", array($profileid, $tabId));
	if ($adb->num_rows($chk) == 0) {
		$adb->pquery("INSERT INTO vtiger_profile2tab (profileid, tabid, permissions) VALUES (?,?,?)",
			array($profileid, $tabId, 0));
	} else {
		$adb->pquery("UPDATE vtiger_profile2tab SET permissions = 0 WHERE profileid = ? AND tabid = ?",
			array($profileid, $tabId));
	}
}
echo "Tab assigned to all profiles.\n";

// 4) Standard permissions (Create, Edit, View, Delete, etc.) - 0 = allow
$actionNames = array('Save', 'EditView', 'CreateView', 'Delete', 'index', 'DetailView');
$actionIds = array();
$res = $adb->pquery("SELECT actionid FROM vtiger_actionmapping WHERE actionname IN (" . generateQuestionMarks($actionNames) . ")", $actionNames);
for ($i = 0; $i < $adb->num_rows($res); $i++) {
	$actionIds[] = $adb->query_result($res, $i, 'actionid');
}
foreach ($profileIds as $profileid) {
	foreach ($actionIds as $actionid) {
		$chk = $adb->pquery("SELECT 1 FROM vtiger_profile2standardpermissions WHERE profileid = ? AND tabid = ? AND operation = ?",
			array($profileid, $tabId, $actionid));
		if ($adb->num_rows($chk) == 0) {
			$adb->pquery("INSERT INTO vtiger_profile2standardpermissions (profileid, tabid, operation, permissions) VALUES (?,?,?,?)",
				array($profileid, $tabId, $actionid, 0));
		} else {
			$adb->pquery("UPDATE vtiger_profile2standardpermissions SET permissions = 0 WHERE profileid = ? AND tabid = ? AND operation = ?",
				array($profileid, $tabId, $actionid));
		}
	}
}
echo "Standard permissions (create/edit/view/delete) set for all profiles.\n";

// 5) Rebuild cached user privilege files (isPermitted reads these, not DB directly)
require_once 'modules/Users/CreateUserPrivilegeFile.php';
$userRes = $adb->pquery("SELECT id FROM vtiger_users WHERE deleted = 0", array());
for ($u = 0; $u < $adb->num_rows($userRes); $u++) {
	$userId = $adb->query_result($userRes, $u, 'id');
	createUserPrivilegesfile($userId);
	createUserSharingPrivilegesfile($userId);
}
echo "Rebuilt user_privileges for all active users.\n";

echo "=== Done ===\n";
