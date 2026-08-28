<?php
/**
 * CLI: install sheet-import schema + register 60s cron.
 * Usage: php modules/Leads/scripts/InstallSheetImport.php
 */
$root = dirname(dirname(dirname(__DIR__)));
chdir($root);
require_once $root . '/config.inc.php';
require_once $root . '/include/utils/utils.php';
require_once $root . '/includes/Loader.php';
require_once $root . '/modules/Leads/models/SheetImportService.php';

$adb = PearDatabase::getInstance();
Leads_SheetImportService::installSchema($adb);
Leads_SheetImportService::registerCron();
echo "OK: bace_lead_sheet_* schema + LeadsSheetPoll cron (60s).\n";
