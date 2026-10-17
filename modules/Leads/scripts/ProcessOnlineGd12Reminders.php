<?php
/**
 * GD 1.2 D0 reminders: CRM → Zalo OA (KB-02a/b/c).
 * Cron gợi ý: mỗi giờ (vtiger) hoặc mỗi ngày (crontab).
 * Đăng ký vtiger cron: php modules/Leads/scripts/InstallOnlineGd12Cron.php
 */
$crmRoot = dirname(dirname(dirname(__DIR__)));
chdir($crmRoot);
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
require_once 'modules/Leads/models/OnlineGd12Service.php';

$admin = Users::getActiveAdminUser();
if ($admin) {
	global $current_user;
	$current_user = $admin;
	vglobal('current_user', $admin);
}

Leads_OnlineGd12Service::installSchema();
$result = Leads_OnlineGd12Service::processD0Reminders(100);
echo "Online GD1.2 D0 reminders: sent={$result['sent']} stopped={$result['stopped']}\n";
