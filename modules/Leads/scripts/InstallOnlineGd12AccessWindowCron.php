<?php
/**
 * Đăng ký cron GD 1.2 hạn 10 ngày vào vtiger_cron_task.
 * Usage: php modules/Leads/scripts/InstallOnlineGd12AccessWindowCron.php
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
Leads_OnlineGd12Service::registerAccessWindowCron();

echo "OK: OnlineGd12AccessWindow (3600s) registered.\n";
echo "Chạy thử: php modules/Leads/scripts/ProcessOnlineGd12AccessWindow.php\n";
