<?php
/*+***********************************************************************************
 * Add technical, pricing, delivery and service fields to ProductsServices.
 * Run from vtiger root: php -f modules/ProductsServices/scripts/AddProductsServicesFields.php
 * Safe to run multiple times (idempotent).
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$moduleName = 'ProductsServices';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found. Run CreateProductsServicesModule first.\n";
	exit(1);
}

function addFieldIfMissing(Vtiger_Module $module, Vtiger_Block $block, $name, $label, $uitype, $columntype, $typeofdata, $picklistValues = null) {
	$f = Vtiger_Field::getInstance($name, $module);
	if ($f) {
		echo "  Field exists: $name\n";
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
	echo "  Added field: $name\n";
	return $f;
}

echo "=== Add ProductsServices fields and blocks ===\n";

// Block 1: Information (existing)
$blockInfo = Vtiger_Block::getInstance('LBL_PRODUCTS_SERVICES_INFORMATION', $module);
if (!$blockInfo) {
	$blockInfo = new Vtiger_Block();
	$blockInfo->label = 'LBL_PRODUCTS_SERVICES_INFORMATION';
	$module->addBlock($blockInfo);
}

// Product-only fields (in info block)
addFieldIfMissing($module, $blockInfo, 'sku', 'SKU', 1, 'VARCHAR(100)', 'V~O');
addFieldIfMissing($module, $blockInfo, 'brand', 'Brand', 1, 'VARCHAR(255)', 'V~O');
addFieldIfMissing($module, $blockInfo, 'model', 'Model', 1, 'VARCHAR(255)', 'V~O');
addFieldIfMissing($module, $blockInfo, 'unit', 'Unit', 15, 'VARCHAR(100)', 'V~O', array('Piece', 'Box', 'Set', 'Kg', 'Liter', 'Hour', 'Day', 'Month'));
addFieldIfMissing($module, $blockInfo, 'stock', 'Stock', 7, 'DECIMAL(25,2)', 'N~O'); // number
addFieldIfMissing($module, $blockInfo, 'supplier', 'Supplier', 1, 'VARCHAR(255)', 'V~O');

// Service-only fields
addFieldIfMissing($module, $blockInfo, 'service_duration', 'Service Duration', 1, 'VARCHAR(100)', 'V~O');
addFieldIfMissing($module, $blockInfo, 'service_category', 'Service Category', 15, 'VARCHAR(200)', 'V~O', array('Installation', 'Maintenance', 'Consulting', 'Training', 'Support'));
addFieldIfMissing($module, $blockInfo, 'service_level', 'Service Level', 15, 'VARCHAR(100)', 'V~O', array('Basic', 'Standard', 'Premium'));
addFieldIfMissing($module, $blockInfo, 'description', 'Description', 19, 'TEXT', 'V~O'); // textarea

// Block 2: Pricing
$blockPricing = Vtiger_Block::getInstance('LBL_PRICING', $module);
if (!$blockPricing) {
	$blockPricing = new Vtiger_Block();
	$blockPricing->label = 'LBL_PRICING';
	$module->addBlock($blockPricing);
}
addFieldIfMissing($module, $blockPricing, 'retail_price', 'Retail Price', 71, 'DECIMAL(25,8)', 'N~O');
addFieldIfMissing($module, $blockPricing, 'bulk_price', 'Bulk Price', 71, 'DECIMAL(25,8)', 'N~O');
// wholesale_price already exists - ensure it's in this block if not already
$wholesale = Vtiger_Field::getInstance('wholesale_price', $module);
if ($wholesale && $blockPricing->id) {
	// optional: move to pricing block via update in vtiger_blocks (blockid) - skip for simplicity
}

// Block 3: Delivery
$blockDelivery = Vtiger_Block::getInstance('LBL_DELIVERY', $module);
if (!$blockDelivery) {
	$blockDelivery = new Vtiger_Block();
	$blockDelivery->label = 'LBL_DELIVERY';
	$module->addBlock($blockDelivery);
}
addFieldIfMissing($module, $blockDelivery, 'delivery_time', 'Delivery Time', 1, 'VARCHAR(255)', 'V~O');

echo "=== Done ===\n";
