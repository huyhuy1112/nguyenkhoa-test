<?php
/**
 * CLI runner: mark overdue SLA entries for HelpDesk Support Rules Engine.
 *
 * Usage:
 *   php modules/HelpDesk/cron/MarkSlaOverdue.php
 */

chdir(dirname(__FILE__) . '/../../..');

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'modules/HelpDesk/models/SupportRulesService.php';

$service = HelpDesk_SupportRulesService::getInstance();
$updated = $service->markOverdue();

echo '[' . date('Y-m-d H:i:s') . '] ticket_sla updated to overdue: ' . (int)$updated . PHP_EOL;

