<?php
/**
 * Chạy tay đồng bộ tiến độ Edubit cho Contacts đã cấp tài khoản.
 * Usage: php modules/Leads/scripts/ProcessOnlineGd12EdubitProgress.php
 */
$root = dirname(dirname(dirname(__DIR__)));
chdir($root);
require_once $root . '/config.inc.php';
require_once $root . '/include/utils/utils.php';
require_once $root . '/includes/Loader.php';
require_once $root . '/modules/Leads/models/OnlineGd12Service.php';

$admin = Users::getActiveAdminUser();
if ($admin) {
	global $current_user;
	$current_user = $admin;
	vglobal('current_user', $admin);
}

Leads_OnlineGd12Service::installSchema();
$out = Leads_OnlineGd12Service::syncEdubitProgressForAllContacts(
	300,
	!empty($admin->id) ? (int) $admin->id : null
);
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
