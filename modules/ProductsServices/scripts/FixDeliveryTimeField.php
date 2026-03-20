<?php
/*+***********************************************************************************
 * Fix ProductsServices.delivery_time field to be a proper DateTime field.
 *
 * - Uses vtlib to adjust field metadata
 * - Uses PearDatabase to alter DB column type
 * - Safe to run multiple times (idempotent)
 *
 * Run from vtiger root:
 *   php -f modules/ProductsServices/scripts/FixDeliveryTimeField.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // modules/ProductsServices/scripts -> vtiger root

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

echo "=== Fix delivery_time field ===\n";

$moduleName = 'ProductsServices';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module not found: {$moduleName}\n";
	exit(1);
}
echo "Module loaded: {$moduleName}\n";

$field = Vtiger_Field::getInstance('delivery_time', $module);
if (!$field) {
	echo "ERROR: Field not found: delivery_time\n";
	echo "Nothing to update.\n";
	echo "=== Done ===\n";
	exit(0);
}

echo "Field found: delivery_time\n";

// STEP 4 – Update field metadata to DateTime
$field->uitype      = 70;
$field->typeofdata  = 'DT~O';
$field->displaytype = 1;
$field->presence    = 0;
$field->save();

echo "Updated field metadata\n";

// STEP 5 – Update database column type (vtiger_productsservices.delivery_time) to DATETIME
try {
	/** @var PearDatabase $adb */
	$adb = PearDatabase::getInstance();

	$table  = 'vtiger_productsservices';
	$column = 'delivery_time';

	// Using MODIFY is idempotent if the column is already DATETIME
	$adb->pquery("ALTER TABLE {$table} MODIFY {$column} DATETIME", array());
	echo "Updated DB column to DATETIME\n";
} catch (Exception $e) {
	echo "WARNING: Failed to alter column type: " . $e->getMessage() . "\n";
}

echo "=== Done ===\n";

