<?php
/*+***********************************************************************************
 * Fix ProductsServices layout (blocks & fields) using vtlib only.
 *
 * - Ensures blocks:
 *   LBL_PRODUCT_INFORMATION
 *   LBL_SERVICE_INFORMATION
 *   LBL_PRICING
 *   LBL_DELIVERY
 *   LBL_PROJECT_HISTORY
 * - Ensures fields exist and are attached to correct blocks.
 * - If fields already exist, they are MOVED to the correct block.
 * - Base fields remain: productsservicesname, item_type, price, wholesale_price, related_projects.
 * - Sets presence = 0 and displaytype = 1 so fields show in Edit & Detail view.
 *
 * Run from vtiger root:
 *   php -f modules/ProductsServices/scripts/FixProductsServicesLayout.php
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

echo "=== Fix ProductsServices layout ===\n";

/**
 * Ensure block exists and return instance.
 */
function ps_getOrCreateBlock(Vtiger_Module $module, $label) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) return $block;
	$block = new Vtiger_Block();
	$block->label = $label;
	$module->addBlock($block);
	echo "  + Block created: $label\n";
	return $block;
}

/**
 * Ensure field exists and is attached to given block.
 * If it exists, move it to the block and fix visibility.
 * If it does not exist, create it with given metadata.
 */
function ps_ensureFieldInBlock(
	Vtiger_Module $module,
	Vtiger_Block $block,
	$fieldName,
	$fieldLabel,
	$uitype,
	$columntype,
	$typeofdata,
	$picklistValues = null
) {
	$field = Vtiger_Field::getInstance($fieldName, $module);

	if (!$field) {
		$field = new Vtiger_Field();
		$field->name = $fieldName;
		$field->label = $fieldLabel;
		$field->uitype = $uitype;
		$field->column = $fieldName;
		$field->columntype = $columntype;
		$field->typeofdata = $typeofdata;
		$field->presence = 0;
		$field->displaytype = 1;
		$block->addField($field);
		if ($picklistValues !== null) {
			$field->setPicklistValues($picklistValues);
		}
		echo "  + Field created & added: {$fieldName} -> {$block->label}\n";
		return $field;
	}

	// Existing field: move to block and ensure visibility
	$field->block = $block;
	$field->presence = 0;      // active
	$field->displaytype = 1;   // show in Edit & Detail
	$field->save();

	echo "  * Field moved/updated: {$fieldName} -> {$block->label}\n";
	return $field;
}

// ---------------------------------------------------------------------------
// Ensure base fields exist and are visible (do not move them)
// ---------------------------------------------------------------------------
$baseFieldNames = array(
	'productsservicesname',
	'item_type',
	'price',
	'wholesale_price',
	'related_projects',
);

foreach ($baseFieldNames as $baseName) {
	$f = Vtiger_Field::getInstance($baseName, $module);
	if ($f) {
		$f->presence = 0;
		$f->displaytype = 1;
		$f->save();
		echo "Base field visible: {$baseName}\n";
	} else {
		echo "Base field missing (not created by this script): {$baseName}\n";
	}
}

// ---------------------------------------------------------------------------
// Block 1: PRODUCT INFORMATION
// ---------------------------------------------------------------------------
$bProduct = ps_getOrCreateBlock($module, 'LBL_PRODUCT_INFORMATION');
ps_ensureFieldInBlock($module, $bProduct, 'brand', 'Brand', 1, 'VARCHAR(255)', 'V~O');
ps_ensureFieldInBlock($module, $bProduct, 'model', 'Model', 1, 'VARCHAR(255)', 'V~O');
ps_ensureFieldInBlock($module, $bProduct, 'unit', 'Unit', 15, 'VARCHAR(100)', 'V~O', array('pcs', 'set', 'kg', 'box'));
ps_ensureFieldInBlock($module, $bProduct, 'stock', 'Stock', 7, 'INT(11)', 'N~O');
ps_ensureFieldInBlock($module, $bProduct, 'supplier', 'Supplier', 1, 'VARCHAR(255)', 'V~O');
ps_ensureFieldInBlock($module, $bProduct, 'warranty', 'Warranty', 1, 'VARCHAR(255)', 'V~O');
ps_ensureFieldInBlock($module, $bProduct, 'specification', 'Specification', 19, 'TEXT', 'V~O');

// ---------------------------------------------------------------------------
// Block 2: SERVICE INFORMATION
// ---------------------------------------------------------------------------
$bService = ps_getOrCreateBlock($module, 'LBL_SERVICE_INFORMATION');
ps_ensureFieldInBlock($module, $bService, 'service_duration', 'Service Duration', 1, 'VARCHAR(100)', 'V~O');
ps_ensureFieldInBlock($module, $bService, 'service_category', 'Service Category', 15, 'VARCHAR(200)', 'V~O', array('Installation', 'Maintenance', 'Consulting'));
ps_ensureFieldInBlock($module, $bService, 'service_level', 'Service Level', 15, 'VARCHAR(100)', 'V~O', array('Basic', 'Standard', 'Premium'));
ps_ensureFieldInBlock($module, $bService, 'description', 'Description', 19, 'TEXT', 'V~O');

// ---------------------------------------------------------------------------
// Block 3: PRICING
// ---------------------------------------------------------------------------
$bPricing = ps_getOrCreateBlock($module, 'LBL_PRICING');
ps_ensureFieldInBlock($module, $bPricing, 'retail_price', 'Retail Price', 71, 'DECIMAL(25,8)', 'N~O');
ps_ensureFieldInBlock($module, $bPricing, 'wholesale_price', 'Wholesale Price', 71, 'DECIMAL(25,8)', 'N~O');
ps_ensureFieldInBlock($module, $bPricing, 'bulk_price', 'Bulk Price', 71, 'DECIMAL(25,8)', 'N~O');

// ---------------------------------------------------------------------------
// Block 4: DELIVERY
// ---------------------------------------------------------------------------
$bDelivery = ps_getOrCreateBlock($module, 'LBL_DELIVERY');
ps_ensureFieldInBlock($module, $bDelivery, 'delivery_time', 'Delivery Time', 1, 'VARCHAR(255)', 'V~O');

// ---------------------------------------------------------------------------
// Block 5: PROJECT HISTORY
// ---------------------------------------------------------------------------
$bHistory = ps_getOrCreateBlock($module, 'LBL_PROJECT_HISTORY');
ps_ensureFieldInBlock($module, $bHistory, 'used_projects', 'Used In Projects', 19, 'TEXT', 'V~O');

echo "=== Done (FixProductsServicesLayout) ===\n";

