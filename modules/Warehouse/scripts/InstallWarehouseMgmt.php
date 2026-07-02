<?php
/**
 * CLI installer for warehouse management schema + seed data.
 *
 * Run from vtiger root:
 *   php -f modules/Warehouse/scripts/InstallWarehouseMgmt.php
 */
chdir(dirname(__DIR__, 3));
require_once 'vendor/autoload.php';
require_once 'config.php';
include_once 'vtlib/Vtiger/Cron.php';
vimport('includes.runtime.EntryPoint');
require_once 'modules/Users/Users.php';
require_once 'modules/Warehouse/models/WhMgmtService.php';

global $current_user;
$current_user = Users::getActiveAdminUser();

echo "=== Install Warehouse Management ===\n";
Warehouse_WhMgmtService::seedAll();
$state = Warehouse_WhMgmtService::getFullState();
echo 'Warehouses: ' . count($state['warehouses']) . "\n";
echo "=== Done ===\n";

?>
