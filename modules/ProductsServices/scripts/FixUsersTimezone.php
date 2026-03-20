<?php
/*+***********************************************************************************
 * Fix all users' timezone to match server default (Asia/Ho_Chi_Minh).
 *
 * Run from vtiger root:
 *   php -f modules/ProductsServices/scripts/FixUsersTimezone.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // modules/ProductsServices/scripts -> vtiger root

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';

$tz = 'Asia/Ho_Chi_Minh';

/** @var PearDatabase $adb */
$adb = PearDatabase::getInstance();
$adb->pquery('UPDATE vtiger_users SET time_zone = ?', array($tz));

echo "Updated vtiger_users.time_zone to {$tz}\n";

