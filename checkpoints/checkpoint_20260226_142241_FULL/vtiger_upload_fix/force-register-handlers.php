<?php
/**
 * Force register Project and ProjectTask notification handlers
 * Simple version that directly inserts into database
 */

require_once 'config.php';
require_once 'include/database/PearDatabase.php';

global $adb;
$adb = PearDatabase::getInstance();

echo "Registering handlers...\n";

// Check if handlers already exist
$check = $adb->pquery("SELECT eventhandler_id FROM vtiger_eventhandlers WHERE handler_class = 'ProjectHandler' AND event_name = 'vtiger.entity.aftersave.final'", array());
if ($adb->num_rows($check) == 0) {
    $handlerId = $adb->getUniqueID('vtiger_eventhandlers');
    $adb->pquery("INSERT INTO vtiger_eventhandlers (eventhandler_id, event_name, handler_path, handler_class, cond, is_active, dependent_on) VALUES (?, 'vtiger.entity.aftersave.final', 'modules/Project/ProjectHandler.php', 'ProjectHandler', '', 1, '[]')", array($handlerId));
    echo "ProjectHandler registered (ID: $handlerId)\n";
} else {
    echo "ProjectHandler already exists\n";
}

$check2 = $adb->pquery("SELECT eventhandler_id FROM vtiger_eventhandlers WHERE handler_class = 'ProjectTaskHandler' AND event_name = 'vtiger.entity.aftersave.final'", array());
if ($adb->num_rows($check2) == 0) {
    $handlerId2 = $adb->getUniqueID('vtiger_eventhandlers');
    $adb->pquery("INSERT INTO vtiger_eventhandlers (eventhandler_id, event_name, handler_path, handler_class, cond, is_active, dependent_on) VALUES (?, 'vtiger.entity.aftersave.final', 'modules/ProjectTask/ProjectTaskHandler.php', 'ProjectTaskHandler', '', 1, '[]')", array($handlerId2));
    echo "ProjectTaskHandler registered (ID: $handlerId2)\n";
} else {
    echo "ProjectTaskHandler already exists\n";
}

// Verify
$result = $adb->pquery("SELECT eventhandler_id, handler_class, is_active FROM vtiger_eventhandlers WHERE handler_class IN ('ProjectHandler', 'ProjectTaskHandler')", array());
$count = $adb->num_rows($result);
echo "Total handlers found: $count\n";

while ($row = $adb->fetchByAssoc($result)) {
    echo "  - " . $row['handler_class'] . " (ID: " . $row['eventhandler_id'] . ", Active: " . ($row['is_active'] ? 'Yes' : 'No') . ")\n";
}

echo "Done.\n";

