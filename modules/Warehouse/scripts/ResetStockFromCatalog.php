<?php
/**
 * Wipe all warehouse stock and reseed from ProductsServices (qty 10 / item on WH-001).
 *
 * Usage (from CRM root, inside web container):
 *   php modules/Warehouse/scripts/ResetStockFromCatalog.php
 */
$root = dirname(dirname(dirname(__DIR__)));
chdir($root);
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'modules/Warehouse/models/WhMgmtService.php';

$result = Warehouse_WhMgmtService::resetStockFromCatalog(null, 'WH-001', 10, true);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
