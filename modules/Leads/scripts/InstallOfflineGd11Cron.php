<?php
/**
 * Đăng ký cron GD 1.1 Step2 + Step4 (15 phút) vào vtiger_cron_task.
 * Usage: php modules/Leads/scripts/InstallOfflineGd11Cron.php
 *
 * Yêu cầu: vtigercron.php chạy định kỳ (docker cron / crontab hệ thống).
 */
$root = dirname(dirname(dirname(__DIR__)));
chdir($root);
require_once $root . '/config.inc.php';
require_once $root . '/include/utils/utils.php';
require_once $root . '/includes/Loader.php';
require_once $root . '/modules/Leads/models/OfflineGd11Service.php';
require_once $root . '/modules/Leads/models/OfflineGd11Step2Service.php';
require_once $root . '/modules/Leads/models/OfflineGd11Step4Service.php';

$admin = Users::getActiveAdminUser();
if ($admin) {
	global $current_user;
	$current_user = $admin;
	vglobal('current_user', $admin);
}

Leads_OfflineGd11Service::installSchema();
Leads_OfflineGd11Step2Service::installSchema();
Leads_OfflineGd11Step4Service::installSchema();
Leads_OfflineGd11Service::registerReminderCrons();

echo "OK: OfflineGd11Step2Reminders + OfflineGd11Step4Reminders (900s) registered.\n";
echo "Chạy thử:\n";
echo "  php modules/Leads/scripts/ProcessOfflineGd11Step2Reminders.php\n";
echo "  php modules/Leads/scripts/ProcessOfflineGd11Step4Reminders.php\n";
