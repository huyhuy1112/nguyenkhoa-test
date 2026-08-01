<?php
/*+***********************************************************************************
 * Smoke-test RBAC matrix against Profile permissions (no login UI).
 *   docker exec vtiger_web php -f modules/Home/scripts/SmokeTestRbacMatrix.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'modules/Home/helpers/RbacMatrix.php';

global $adb;

$EXPECT = array(
	'NK Admin' => array(
		'Leads' => array('index' => 1, 'CreateView' => 1, 'EditView' => 1, 'Delete' => 1, 'DetailView' => 1),
		'SalesOrder' => array('index' => 1, 'CreateView' => 1, 'EditView' => 1),
		'Warehouse' => array('index' => 1, 'CreateView' => 1, 'EditView' => 1),
		'GoodsIssue' => array('CreateView' => 1),
		'Products' => array('index' => 1, 'CreateView' => 1),
	),
	'NK Supervisor' => array(
		'Leads' => array('index' => 1, 'CreateView' => 1, 'EditView' => 1, 'Delete' => 1, 'DetailView' => 1),
		'SalesOrder' => array('index' => 1, 'CreateView' => 1, 'EditView' => 1),
		'Warehouse' => array('index' => 1, 'DetailView' => 1, 'CreateView' => 0, 'EditView' => 0, 'Delete' => 0),
		'GoodsIssue' => array('index' => 0, 'CreateView' => 0),
		'GoodsReceipt' => array('CreateView' => 0),
		'Products' => array('index' => 1, 'CreateView' => 1),
	),
	'NK Sale' => array(
		'Leads' => array('index' => 1, 'CreateView' => 1, 'EditView' => 1, 'Delete' => 0, 'DetailView' => 1),
		'SalesOrder' => array('index' => 1, 'CreateView' => 1, 'EditView' => 1),
		'Warehouse' => array('index' => 1, 'CreateView' => 0),
		'GoodsIssue' => array('CreateView' => 0),
		'Products' => array('index' => 0, 'CreateView' => 0),
	),
	'NK Ke toan' => array(
		'Leads' => array('index' => 0, 'CreateView' => 0),
		'SalesOrder' => array('index' => 0, 'CreateView' => 0),
		'Warehouse' => array('index' => 1, 'CreateView' => 0),
		'GoodsIssue' => array('CreateView' => 0),
		'Products' => array('index' => 1, 'CreateView' => 1),
	),
	'NK Kho' => array(
		'Leads' => array('index' => 0),
		'SalesOrder' => array('index' => 1, 'CreateView' => 0, 'EditView' => 0, 'DetailView' => 1),
		'Warehouse' => array('index' => 1, 'CreateView' => 1, 'EditView' => 1),
		'GoodsIssue' => array('index' => 1, 'CreateView' => 1),
		'GoodsReceipt' => array('CreateView' => 1),
		'Products' => array('index' => 1, 'CreateView' => 1),
	),
);

$actionMap = array();
$r = $adb->pquery('SELECT actionid, actionname FROM vtiger_actionmapping WHERE actionname IN (?,?,?,?,?,?)',
	array('Save', 'EditView', 'Delete', 'index', 'DetailView', 'CreateView'));
for ($i = 0; $i < $adb->num_rows($r); $i++) {
	// Prefer first mapping for standard names (some actionids collide with aliases)
	$name = $adb->query_result($r, $i, 'actionname');
	$id = (int) $adb->query_result($r, $i, 'actionid');
	if (!isset($actionMap[$name])) {
		$actionMap[$name] = $id;
	}
}

function profile_id($name) {
	global $adb;
	$r = $adb->pquery('SELECT profileid FROM vtiger_profile WHERE profilename=?', array($name));
	return $adb->num_rows($r) ? (int) $adb->query_result($r, 0, 'profileid') : 0;
}

function is_allowed_action($profileId, $tabId, $operation) {
	global $adb;
	$tab = $adb->pquery('SELECT permissions FROM vtiger_profile2tab WHERE profileid=? AND tabid=?', array($profileId, $tabId));
	if (!$adb->num_rows($tab) || (int) $adb->query_result($tab, 0, 'permissions') === 1) {
		return false;
	}
	$std = $adb->pquery(
		'SELECT permissions FROM vtiger_profile2standardpermissions WHERE profileid=? AND tabid=? AND operation=?',
		array($profileId, $tabId, $operation)
	);
	if (!$adb->num_rows($std)) {
		return false;
	}
	return (int) $adb->query_result($std, 0, 'permissions') === 0;
}

echo "=== Smoke Test RBAC Matrix ===\n";
$fail = 0;
$pass = 0;

foreach ($EXPECT as $profileName => $mods) {
	$pid = profile_id($profileName);
	if (!$pid) {
		echo "FAIL profile missing: $profileName\n";
		$fail++;
		continue;
	}
	echo "\n[$profileName #$pid]\n";
	foreach ($mods as $module => $actions) {
		$tabId = getTabid($module);
		if (!$tabId) {
			echo "  SKIP $module (no tab)\n";
			continue;
		}
		foreach ($actions as $actionName => $expectAllow) {
			$op = isset($actionMap[$actionName]) ? $actionMap[$actionName] : -1;
			$actual = is_allowed_action($pid, $tabId, $op);
			$ok = ($actual && $expectAllow) || (!$actual && !$expectAllow);
			if ($ok) {
				$pass++;
				echo "  OK  $module.$actionName => " . ($actual ? 'allow' : 'deny') . "\n";
			} else {
				$fail++;
				echo "  FAIL $module.$actionName expected " . ($expectAllow ? 'allow' : 'deny') .
					' got ' . ($actual ? 'allow' : 'deny') . "\n";
			}
		}
	}
}

// ConvertLead for Sale / Supervisor
echo "\n[ConvertLead utility]\n";
foreach (array('NK Supervisor' => 0, 'NK Sale' => 0, 'NK Ke toan' => 1, 'NK Kho' => 1) as $pn => $expectDeny) {
	$pid = profile_id($pn);
	$tabId = getTabid('Leads');
	$u = $adb->pquery('SELECT permission FROM vtiger_profile2utility WHERE profileid=? AND tabid=? AND activityid=9', array($pid, $tabId));
	$perm = $adb->num_rows($u) ? (int) $adb->query_result($u, 0, 'permission') : 1;
	$ok = ($perm === $expectDeny);
	if ($ok) {
		$pass++;
		echo "  OK  $pn ConvertLead permission=$perm\n";
	} else {
		$fail++;
		echo "  FAIL $pn ConvertLead expected $expectDeny got $perm\n";
	}
}

// Roles present
echo "\n[Roles]\n";
foreach (array('Admin', 'Supervisor', 'Sale', 'Ke toan', 'Kho', 'CEO') as $rn) {
	$rr = $adb->pquery('SELECT roleid FROM vtiger_role WHERE rolename=?', array($rn));
	if ($adb->num_rows($rr)) {
		$pass++;
		echo "  OK  role $rn = " . $adb->query_result($rr, 0, 'roleid') . "\n";
	} else {
		$fail++;
		echo "  FAIL role missing: $rn\n";
	}
}

// Persona resolver
echo "\n[Persona resolver]\n";
$cases = array(
	'Admin' => 'admin',
	'Supervisor' => 'supervisor',
	'Sale' => 'sale',
	'Ke toan' => 'accountant',
	'Kế toán' => 'accountant',
	'Kho' => 'warehouse',
	'CEO' => 'ceo',
	'Sales Person' => 'sale',
);
foreach ($cases as $role => $expect) {
	$got = Home_RbacMatrix_Helper::personaFromRoleName($role);
	if ($got === $expect) {
		$pass++;
		echo "  OK  $role => $got\n";
	} else {
		$fail++;
		echo "  FAIL $role => $got (expected $expect)\n";
	}
}

echo "\n=== Result: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);
