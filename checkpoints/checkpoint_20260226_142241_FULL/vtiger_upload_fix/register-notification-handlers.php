<?php
/**
 * Force register Project and ProjectTask notification handlers
 * This script ensures handlers are registered in vtiger_eventhandlers table
 */

require_once 'config.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/events/include.inc';
require_once 'modules/Vtiger/helpers/Logger.php';

global $adb;
$adb = PearDatabase::getInstance();

$log = Logger::getLogger('NotificationHandlers');
$log->fatal('[NOTI_DEBUG] Starting handler registration...');

$em = new VTEventsManager($adb);

// Register ProjectHandler
$log->fatal('[NOTI_DEBUG] Registering ProjectHandler...');
$em->registerHandler('vtiger.entity.aftersave.final', 'modules/Project/ProjectHandler.php', 'ProjectHandler');
$log->fatal('[NOTI_DEBUG] ProjectHandler registered');

// Register ProjectTaskHandler
$log->fatal('[NOTI_DEBUG] Registering ProjectTaskHandler...');
$em->registerHandler('vtiger.entity.aftersave.final', 'modules/ProjectTask/ProjectTaskHandler.php', 'ProjectTaskHandler');
$log->fatal('[NOTI_DEBUG] ProjectTaskHandler registered');

// Verify registration
$result = $adb->pquery("SELECT * FROM vtiger_eventhandlers WHERE handler_class IN ('ProjectHandler', 'ProjectTaskHandler') AND is_active = 1", array());
$count = $adb->num_rows($result);
$log->fatal('[NOTI_DEBUG] Verification: Found ' . $count . ' active handlers');

if ($count >= 2) {
    $log->fatal('[NOTI_DEBUG] SUCCESS: Both handlers are registered and active');
    echo "SUCCESS: Handlers registered\n";
} else {
    $log->fatal('[NOTI_DEBUG] WARNING: Expected 2 handlers, found ' . $count);
    echo "WARNING: Only $count handlers found\n";
}

