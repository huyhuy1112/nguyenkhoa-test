<?php
/*+***********************************************************************************
 * Fix ProductsServices.delivery_time field to be DateTime (uitype 70).
 *
 * - If field exists:
 *     - Change uitype to 70
 *     - Change typeofdata to DT~O
 *     - Ensure presence = 0 and displaytype = 1
 *     - Ensure it is in block LBL_DELIVERY
 *     - Alter DB column to DATETIME
 * - If field does not exist:
 *     - Create field in block LBL_DELIVERY with:
 *         name: delivery_time
 *         label: Delivery Time
 *         uitype: 70
 *         columntype: DATETIME
 *         typeofdata: DT~O
 *
 * Run from vtiger root:
 *   php -f modules/ProductsServices/scripts/FixDeliveryDatetime.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // modules/ProductsServices/scripts -> vtiger root

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$moduleName = 'ProductsServices';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

echo "=== Fix delivery_time to DateTime (ProductsServices) ===\n";

/**
 * Get or create LBL_DELIVERY block.
 */
function ps_getOrCreateDeliveryBlock(Vtiger_Module $module) {
	$block = Vtiger_Block::getInstance('LBL_DELIVERY', $module);
	if ($block) return $block;
	$block = new Vtiger_Block();
	$block->label = 'LBL_DELIVERY';
	$module->addBlock($block);
	echo "  + Block created: LBL_DELIVERY\n";
	return $block;
}

$deliveryBlock = ps_getOrCreateDeliveryBlock($module);

$field = Vtiger_Field::getInstance('delivery_time', $module);

if ($field) {
	echo "Field delivery_time exists. Updating to DateTime...\n";

	$field->uitype = 70;        // Date & Time
	$field->typeofdata = 'DT~O';
	$field->displaytype = 1;    // show in Edit & Detail
	$field->presence = 0;       // active
	$field->block = $deliveryBlock;
	$field->save();

	// Ensure DB column is DATETIME
	try {
		global $adb;
		$table = $field->table;
		$column = $field->column;
		if (!empty($table) && !empty($column)) {
			$adb->pquery("ALTER TABLE $table MODIFY COLUMN $column DATETIME", array());
			echo "  - Column type updated to DATETIME on $table.$column\n";
		}
	} catch (Exception $e) {
		echo "  ! Could not alter column type: " . $e->getMessage() . "\n";
	}
} else {
	echo "Field delivery_time does not exist. Creating as DateTime...\n";

	$field = new Vtiger_Field();
	$field->name = 'delivery_time';
	$field->label = 'Delivery Time';
	$field->uitype = 70;
	$field->column = 'delivery_time';
	$field->columntype = 'DATETIME';
	$field->typeofdata = 'DT~O';
	$field->displaytype = 1;
	$field->presence = 0;

	$deliveryBlock->addField($field);

	echo "  + Field created: delivery_time (DateTime) in LBL_DELIVERY\n";
}

echo "=== Done (FixDeliveryDatetime) ===\n";

