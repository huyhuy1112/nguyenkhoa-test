<?php
/**
 * GD 1.1 Bước 4 — CSKH sau lớp / no-show (OA + Calendar).
 * Cron gợi ý (mỗi 15–30 phút): php modules/Leads/scripts/ProcessOfflineGd11Step4Reminders.php
 */
$crmRoot = dirname(dirname(dirname(__DIR__)));
chdir($crmRoot);
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
require_once 'modules/Leads/models/OfflineGd11Step4Service.php';

$admin = Users::getActiveAdminUser();
if ($admin) {
	global $current_user;
	$current_user = $admin;
	vglobal('current_user', $admin);
}

Leads_OfflineGd11Step4Service::installSchema();
$result = Leads_OfflineGd11Step4Service::processReminders(100);
echo 'Offline GD1.1 Step4 reminders: sent=' . $result['sent']
	. ' skipped=' . $result['skipped']
	. ' errors=' . $result['errors'] . "\n";
