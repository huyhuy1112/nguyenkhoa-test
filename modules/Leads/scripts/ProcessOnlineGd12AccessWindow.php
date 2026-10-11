<?php
/**
 * Chạy tay: cập nhật tag Sắp hết hạn / Hết hạn theo hạn 10 ngày.
 * Usage: php modules/Leads/scripts/ProcessOnlineGd12AccessWindow.php
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
$out = Leads_OnlineGd12Service::processAccessWindowTags(500);
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
