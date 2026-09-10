<?php
/**
 * Đăng ký cron GD 1.2 Online D0 (nhắc chưa điền form) vào vtiger_cron_task.
 * Usage: php modules/Leads/scripts/InstallOnlineGd12Cron.php
 *
 * Yêu cầu: vtigercron.php chạy định kỳ (cPanel cron / docker).
 * Thử tay: php modules/Leads/scripts/ProcessOnlineGd12Reminders.php
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
Leads_OnlineGd12Service::registerD0ReminderCron();

echo "OK: OnlineGd12D0Reminders (3600s) registered.\n";
echo "Kiểm tra: Settings → Automation → Lịch trình tự động (Cron Tasks).\n";
echo "Chạy thử:\n";
echo "  php modules/Leads/scripts/ProcessOnlineGd12Reminders.php\n";
