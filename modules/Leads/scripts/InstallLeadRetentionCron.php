<?php
/**
 * Đăng ký cron LeadRetentionLifecycle (86400s) vào vtiger_cron_task.
 * Usage: php modules/Leads/scripts/InstallLeadRetentionCron.php
 *
 * Yêu cầu: vtigercron.php chạy định kỳ.
 * Chạy thử: php modules/Leads/scripts/ProcessLeadRetention.php
 */
$root = dirname(dirname(dirname(__DIR__)));
chdir($root);
require_once $root . '/config.inc.php';
require_once $root . '/include/utils/utils.php';
require_once $root . '/includes/Loader.php';
require_once $root . '/modules/Leads/models/ModernService.php';

$admin = Users::getActiveAdminUser();
if ($admin) {
	global $current_user;
	$current_user = $admin;
	vglobal('current_user', $admin);
}

Leads_ModernService::ensureRetentionColumns();
Leads_ModernService::registerRetentionCron();

echo "OK: LeadRetentionLifecycle (86400s) registered.\n";
echo "Kiểm tra: Settings → Automation → Lịch trình tự động (Cron Tasks).\n";
echo "Chạy thử:\n";
echo "  php modules/Leads/scripts/ProcessLeadRetention.php\n";
