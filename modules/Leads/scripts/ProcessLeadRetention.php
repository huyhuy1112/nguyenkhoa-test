<?php
/**
 * Chạy tay retention Leads (Ngưng CSKH → trash → purge).
 * Usage: php modules/Leads/scripts/ProcessLeadRetention.php
 */
$crmRoot = dirname(dirname(dirname(__DIR__)));
chdir($crmRoot);
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
require_once 'modules/Leads/models/ModernService.php';

$admin = Users::getActiveAdminUser();
if ($admin) {
	global $current_user;
	$current_user = $admin;
	vglobal('current_user', $admin);
}

Leads_ModernService::ensureRetentionColumns();
Leads_ModernService::registerRetentionCron();
$result = Leads_ModernService::processRetentionLifecycle(200);
echo "Lead retention: trashed={$result['trashed']} purged={$result['purged']} backfilled={$result['backfilled']}\n";
