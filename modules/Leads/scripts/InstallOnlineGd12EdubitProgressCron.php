<?php
/**
 * Đăng ký cron đồng bộ tiến độ Edubit mỗi giờ vào vtiger_cron_task.
 * Usage: php modules/Leads/scripts/InstallOnlineGd12EdubitProgressCron.php
 *
 * Yêu cầu: vtigercron.php của hệ thống phải được server gọi định kỳ.
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
Leads_OnlineGd12Service::registerEdubitProgressCron();

echo "OK: OnlineGd12EdubitProgress (3600s) registered.\n";
echo "Kiểm tra: Settings → Automation → Lịch trình tự động (Cron Tasks).\n";
echo "Chạy thử: php modules/Leads/scripts/ProcessOnlineGd12EdubitProgress.php\n";
