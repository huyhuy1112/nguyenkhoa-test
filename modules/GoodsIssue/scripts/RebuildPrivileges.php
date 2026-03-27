<?php
/**
 * Rebuild vtiger privilege cache files safely.
 *
 * Generates:
 *  - user_privileges/user_privileges_<id>.php
 *  - user_privileges/sharing_privileges_<id>.php
 *
 * Usage:
 *   php modules/GoodsIssue/scripts/RebuildPrivileges.php --all --targets=both [--clear-cache]
 *   php modules/GoodsIssue/scripts/RebuildPrivileges.php --missing-only --targets=main [--clear-cache]
 *
 * Notes:
 * - `--targets` controls where to write privilege files.
 * - vtiger often uses `/test/templates_c/` as Smarty compiled cache dir; that does NOT necessarily mean the app root is `/test`.
 */

$argvBootstrapAdmin = in_array('--bootstrap-admin', $argv, true);
$argvAdminUserId = 1;
$argvAdminUserName = 'admin';
foreach ($argv as $arg) {
	if (strpos($arg, '--admin-user-id=') === 0) {
		$argvAdminUserId = (int) substr($arg, strlen('--admin-user-id='));
	}
	if (strpos($arg, '--admin-username=') === 0) {
		$argvAdminUserName = substr($arg, strlen('--admin-username='));
	}
}

// ---- parse args ----
$argvAll = in_array('--all', $argv, true);
$argvMissingOnly = in_array('--missing-only', $argv, true);
$clearCache = in_array('--clear-cache', $argv, true);

$targets = 'both';
foreach ($argv as $arg) {
	if (strpos($arg, '--targets=') === 0) {
		$targets = substr($arg, strlen('--targets='));
		break;
	}
}

$modeAll = ($argvMissingOnly ? false : true);
if ($argvAll) {
	$modeAll = true;
}

// ---- locate config.inc.php ----
$scriptDir = __DIR__;
$searchDir = $scriptDir;
$configPath = null;
for ($i = 0; $i < 12; $i++) {
	$candidate = $searchDir . DIRECTORY_SEPARATOR . 'config.inc.php';
	if (is_file($candidate)) {
		$configPath = $candidate;
		break;
	}
	$parent = dirname($searchDir);
	if ($parent === $searchDir) {
		break;
	}
	$searchDir = $parent;
}

if (empty($configPath)) {
	throw new Exception("Cannot find config.inc.php by walking up from scripts directory");
}

$vtigerRoot = dirname($configPath);
chdir($vtigerRoot);
require_once $configPath;

global $adb, $root_directory;
if (empty($root_directory)) {
	$root_directory = rtrim($vtigerRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

// Normalize root directory strings to trailing slash.
$mainRoot = rtrim($root_directory, "/\\") . DIRECTORY_SEPARATOR;

// Candidate roots where privilege files might live.
$targetRoots = array();
if ($targets === 'main' || $targets === 'both') {
	$targetRoots[] = $mainRoot;
}

$testRoot = rtrim($mainRoot, "/\\") . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR;
if (($targets === 'test' || $targets === 'both') && is_dir($testRoot)) {
	$targetRoots[] = $testRoot;
}

$targetRoots = array_values(array_unique($targetRoots));
if (empty($targetRoots)) {
	throw new Exception("No target roots found for privilege rebuild (targets={$targets})");
}

echo "Detected vtiger root: {$vtigerRoot}\n";
echo "Detected config root_directory: {$mainRoot}\n";
echo "Privilege target roots: " . implode(', ', $targetRoots) . "\n";
echo "Mode: " . ($modeAll ? 'all' : 'missing-only') . "\n";

// ---- bootstrap admin only (no DB needed) ----
if ($argvBootstrapAdmin) {
	$adminId = $argvAdminUserId;
	$adminName = $argvAdminUserName;
	echo "Bootstrapping admin privilege files only for user id={$adminId}\n";

	foreach ($targetRoots as $targetRoot) {
		@mkdir($targetRoot . 'user_privileges', 0775, true);
		@chmod($targetRoot . 'user_privileges', 0775);

		$userPrivilegesPath = $targetRoot . 'user_privileges/user_privileges_' . $adminId . '.php';
		$userSharingPath = $targetRoot . 'user_privileges/sharing_privileges_' . $adminId . '.php';

		// AccessControl reads `$is_admin` and `$user_info` only for login bootstrap.
		$userPrivilegesContent = "<?php\n";
		$userPrivilegesContent .= "\$is_admin = true;\n";
		$userPrivilegesContent .= "\$user_info = array(\n";
		$userPrivilegesContent .= "  'id' => '" . $adminId . "',\n";
		$userPrivilegesContent .= "  'is_admin' => 'on',\n";
		$userPrivilegesContent .= "  'user_name' => '" . addslashes($adminName) . "',\n";
		$userPrivilegesContent .= "  'userlabel' => '" . addslashes($adminName) . "',\n";
		$userPrivilegesContent .= ");\n";
		$userPrivilegesContent .= "?>\n";

		// For admin, vtiger's generator writes an almost-empty sharing file.
		$userSharingContent = "<?php\n?>\n";

		if (!is_file($userPrivilegesPath)) {
			file_put_contents($userPrivilegesPath, $userPrivilegesContent);
		}
		if (!is_file($userSharingPath)) {
			file_put_contents($userSharingPath, $userSharingContent);
		}

		echo " - ensured: {$userPrivilegesPath}\n";
		echo " - ensured: {$userSharingPath}\n";
	}

	if ($clearCache) {
		foreach ($targetRoots as $tRoot) {
			$paths = array(
				$tRoot . 'cache/menu/*',
				$tRoot . 'cache/templates_c/*',
				$tRoot . 'templates_c/*',
				$tRoot . 'test' . DIRECTORY_SEPARATOR . 'templates_c/*',
			);
			foreach ($paths as $p) {
				$files = glob($p);
				if (!empty($files)) {
					foreach ($files as $f) {
						@unlink($f);
					}
				}
			}
		}
	}

	echo "Done (bootstrap-admin).\n";
	exit(0);
}

// ---- load privilege generator (DB required) ----
require_once 'include/utils/utils.php';
require_once 'include/utils/UserInfoUtil.php';
require_once 'modules/Users/CreateUserPrivilegeFile.php';

// ---- query active users ----
$res = $adb->pquery(
	"SELECT id FROM vtiger_users WHERE deleted = 0",
	array()
);
$count = $adb->num_rows($res);

$regenFiles = array();
$checkedUsers = 0;

// Store root_directory original and restore at end.
$originalRootDir = $root_directory;
$originalCwd = getcwd();

// ---- pre-check admin files existence ----
foreach ($targetRoots as $tRoot) {
	$u = $tRoot . 'user_privileges/user_privileges_1.php';
	$s = $tRoot . 'user_privileges/sharing_privileges_1.php';
	$uOk = is_file($u);
	$sOk = is_file($s);
	echo "Admin files under {$tRoot}: user_privileges=" . ($uOk ? 'yes' : 'no') . ", sharing_privileges=" . ($sOk ? 'yes' : 'no') . "\n";
}

// ---- rebuild ----
foreach ($targetRoots as $targetRoot) {
	echo "Rebuilding privileges under target: {$targetRoot}\n";

	$dirsToEnsure = array(
		$targetRoot . 'user_privileges',
		$targetRoot . 'cache',
		$targetRoot . 'templates_c',
		$targetRoot . 'test' . DIRECTORY_SEPARATOR . 'templates_c',
	);

	foreach ($dirsToEnsure as $d) {
		if (!is_dir($d)) {
			@mkdir($d, 0775, true);
		}
		if (!is_writable($d)) {
			throw new Exception("Directory not writable: {$d}");
		}
	}

	// Switch for generator that writes relative to global $root_directory and current cwd.
	$root_directory = $targetRoot;
	chdir($targetRoot);

	for ($i = 0; $i < $count; $i++) {
		$userId = (int) $adb->query_result($res, $i, 'id');
		$checkedUsers++;

		$userPrivilegesPath = $targetRoot . 'user_privileges/user_privileges_' . $userId . '.php';
		$userSharingPath = $targetRoot . 'user_privileges/sharing_privileges_' . $userId . '.php';

		$needsRebuild = $modeAll || !is_file($userPrivilegesPath) || !is_file($userSharingPath);
		if (!$needsRebuild) {
			continue;
		}

		createUserPrivilegesfile($userId);
		createUserSharingPrivilegesfile($userId);

		$regenFiles[] = $userPrivilegesPath;
		$regenFiles[] = $userSharingPath;

		echo " - rebuilt for user {$userId}\n";
	}
}

// Restore.
$root_directory = $originalRootDir;
chdir($originalCwd);

// Final admin validation (at least one target root must be healthy).
$adminOkAny = false;
foreach ($targetRoots as $tRoot) {
	$u = $tRoot . 'user_privileges/user_privileges_1.php';
	$s = $tRoot . 'user_privileges/sharing_privileges_1.php';
	if (is_file($u) && is_file($s)) {
		$adminOkAny = true;
		break;
	}
}
if (!$adminOkAny) {
	throw new Exception("Recovery failed: missing admin privilege files under all target roots");
}

// ---- clear safe caches (only after rebuild succeeded) ----
if ($clearCache) {
	foreach ($targetRoots as $tRoot) {
		$paths = array(
			$tRoot . 'cache/menu/*',
			$tRoot . 'cache/templates_c/*',
			$tRoot . 'templates_c/*',
			$tRoot . 'test' . DIRECTORY_SEPARATOR . 'templates_c/*',
		);
		foreach ($paths as $p) {
			$files = glob($p);
			if (!empty($files)) {
				foreach ($files as $f) {
					@unlink($f);
				}
			}
		}
	}

	if (class_exists('Vtiger_Cache')) {
		@Vtiger_Cache::flushModuleCache();
	}
}

echo "Done.\n";
echo "Users checked: {$checkedUsers}\n";

// Print the exact file patterns regenerated for auditing.
$regenFiles = array_values(array_unique($regenFiles));
if (!empty($regenFiles)) {
	echo "Regenerated files (exact):\n";
	foreach ($regenFiles as $p) {
		echo " - {$p}\n";
	}
}

echo "Rerun command (recommended):\n";
echo "php modules/GoodsIssue/scripts/RebuildPrivileges.php --missing-only --targets={$targets} --clear-cache\n";

?>

