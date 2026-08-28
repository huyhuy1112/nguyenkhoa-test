<?php
/*+***********************************************************************************
 * Extend ProductsServices: add blocks PRODUCT INFORMATION, SERVICE INFORMATION,
 * PRICING, DELIVERY, PROJECT PORTFOLIO with all fields.
 * Run from vtiger root: php -f modules/ProductsServices/scripts/ExtendProductsServicesFields.php
 * Safe to run multiple times.
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$moduleName = 'ProductsServices';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

function addField(Vtiger_Module $module, Vtiger_Block $block, $name, $label, $uitype, $columntype, $typeofdata, $picklistValues = null) {
	$f = Vtiger_Field::getInstance($name, $module);
	if ($f) {
		echo "  exists: $name\n";
		return $f;
	}
	$f = new Vtiger_Field();
	$f->name = $name;
	$f->label = $label;
	$f->uitype = $uitype;
	$f->column = $name;
	$f->columntype = $columntype;
	$f->typeofdata = $typeofdata;
	$block->addField($f);
	if ($picklistValues !== null) {
		$f->setPicklistValues($picklistValues);
	}
	echo "  + $name\n";
	return $f;
}

function getOrCreateBlock(Vtiger_Module $module, $label) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) return $block;
	$block = new Vtiger_Block();
	$block->label = $label;
	$module->addBlock($block);
	echo "  block created: $label\n";
	return $block;
}

echo "=== Extend ProductsServices fields ===\n";

// BLOCK 1: PRODUCT INFORMATION
$b1 = getOrCreateBlock($module, 'LBL_PRODUCT_INFORMATION');
addField($module, $b1, 'sku', 'SKU', 1, 'VARCHAR(100)', 'V~O');
addField($module, $b1, 'brand', 'Brand', 1, 'VARCHAR(255)', 'V~O');
addField($module, $b1, 'model', 'Model', 1, 'VARCHAR(255)', 'V~O');
addField($module, $b1, 'unit', 'Unit', 15, 'VARCHAR(100)', 'V~O', array('pcs', 'set', 'kg', 'box'));
addField($module, $b1, 'stock', 'Stock', 7, 'INT(11)', 'N~O');
addField($module, $b1, 'supplier', 'Supplier', 1, 'VARCHAR(255)', 'V~O');
addField($module, $b1, 'warranty', 'Warranty', 1, 'VARCHAR(255)', 'V~O');
addField($module, $b1, 'specification', 'Specification', 19, 'TEXT', 'V~O');

// BLOCK 2: SERVICE INFORMATION
$b2 = getOrCreateBlock($module, 'LBL_SERVICE_INFORMATION');
addField($module, $b2, 'service_category', 'Service Category', 15, 'VARCHAR(200)', 'V~O', array('Installation', 'Maintenance', 'Consulting'));
addField($module, $b2, 'service_duration', 'Service Duration', 1, 'VARCHAR(100)', 'V~O');
addField($module, $b2, 'service_level', 'Service Level', 15, 'VARCHAR(100)', 'V~O', array('Basic', 'Standard', 'Premium'));
addField($module, $b2, 'description', 'Description', 19, 'TEXT', 'V~O');

// BLOCK 3: PRICING
$b3 = getOrCreateBlock($module, 'LBL_PRICING');
addField($module, $b3, 'retail_price', 'Retail Price', 71, 'DECIMAL(25,8)', 'N~O');
addField($module, $b3, 'wholesale_price', 'Wholesale Price', 71, 'DECIMAL(25,8)', 'N~O');
addField($module, $b3, 'bulk_price', 'Bulk Price', 71, 'DECIMAL(25,8)', 'N~O');
addField($module, $b3, 'minimum_qty_wholesale', 'Minimum Qty Wholesale', 7, 'INT(11)', 'N~O');
addField($module, $b3, 'minimum_qty_bulk', 'Minimum Qty Bulk', 7, 'INT(11)', 'N~O');

// BLOCK 4: DELIVERY
$b4 = getOrCreateBlock($module, 'LBL_DELIVERY');
addField($module, $b4, 'delivery_time', 'Delivery Time', 1, 'VARCHAR(255)', 'V~O');

// BLOCK 5: PROJECT HISTORY (used_projects = list of projects where product/service was used)
$b5 = getOrCreateBlock($module, 'LBL_PROJECT_HISTORY');
addField($module, $b5, 'used_projects', 'Used In Projects', 19, 'TEXT', 'V~O');

// Relation label: ensure Project shows ProductsServices as "Used In Projects"
try {
	global $adb;
	$projectTabId = (int) Vtiger_Module::getInstance('Project')->id;
	$psTabId     = (int) $module->id;
	$adb->pquery(
		"UPDATE vtiger_relatedlists SET label = ? WHERE tabid = ? AND related_tabid = ?",
		array('Used In Projects', $projectTabId, $psTabId)
	);
	echo "Relation label: Project -> ProductsServices = 'Used In Projects'.\n";
} catch (Exception $e) {
	echo "Note: " . $e->getMessage() . "\n";
}

echo "=== Done ===\n";
