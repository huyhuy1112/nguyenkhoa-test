<?php
/*+***********************************************************************************
 * Rebuild all user_privileges and sharing_privileges files from database.
 * Use this when you see errors like:
 *   Failed opening required 'user_privileges/user_privileges_1.php'
 *************************************************************************************/

chdir(__DIR__);

require_once 'include/utils/utils.php';
require_once 'include/utils/UserInfoUtil.php';
require_once 'modules/Users/CreateUserPrivilegeFile.php';

global $adb;

echo "== Rebuild user privilege files ==\n";

$res = $adb->pquery("SELECT id, user_name FROM vtiger_users WHERE deleted = 0", []);
$count = $adb->num_rows($res);
echo "Found {$count} active users\n";

for ($i = 0; $i < $count; $i++) {
    $userId = $adb->query_result($res, $i, 'id');
    $userName = $adb->query_result($res, $i, 'user_name');
    echo "Rebuilding privileges for user {$userId} ({$userName})...\n";
    createUserPrivilegesfile($userId);
    createUserSharingPrivilegesfile($userId);
}

echo "Done.\n";

