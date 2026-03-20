<?php
/*+***********************************************************************************
 * Register ProductsServices in vtiger_ws_entity so the module is recognized
 * and "Permission to perform the operation is denied for name : ProductsServices" is fixed.
 *
 * Run from vtiger root: php -f modules/ProductsServices/scripts/RegisterProductsServicesWebservice.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // modules/ProductsServices/scripts -> vtiger root

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/Webservices/Utils.php';

$moduleName = 'ProductsServices';

echo "Registering $moduleName in vtiger_ws_entity ...\n";

vtws_addDefaultModuleTypeEntity($moduleName);

echo "Done. Reload the ProductsServices list page (clear cache if needed).\n";
