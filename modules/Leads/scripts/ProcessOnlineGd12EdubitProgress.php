<?php
/**
 * Chạy tay đồng bộ tiến độ Edubit: Contacts + Opp/lead (quà 27312).
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

$userId = !empty($admin->id) ? (int) $admin->id : null;
Leads_OnlineGd12Service::installSchema();
$out = array(
	'contacts' => Leads_OnlineGd12Service::syncEdubitProgressForAllContacts(300, $userId),
	'opportunities' => Leads_OnlineGd12Service::syncEdubitProgressForAllOpportunities(300, $userId),
);
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
