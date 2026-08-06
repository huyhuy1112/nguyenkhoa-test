<?php
/*+***********************************************************************************
 * Add ProductsServices.needs_qc (Cần QC) checkbox for product-level warehouse QC routing.
 * Run from vtiger root: php -f modules/ProductsServices/scripts/AddNeedsQcField.php
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

$fieldName = 'needs_qc';
$existing = Vtiger_Field::getInstance($fieldName, $module);
if ($existing) {
	echo "exists: $fieldName\n";
	exit(0);
}

$block = Vtiger_Block::getInstance('LBL_PRODUCT_INFORMATION', $module);
if (!$block) {
	$block = Vtiger_Block::getInstance('LBL_PRODUCTS_SERVICES_INFORMATION', $module);
}
if (!$block) {
	echo "ERROR: No suitable block for $moduleName.\n";
	exit(1);
}

$field = new Vtiger_Field();
$field->name = $fieldName;
$field->label = 'Needs QC';
$field->uitype = 56;
$field->column = $fieldName;
$field->columntype = 'TINYINT(1) DEFAULT 0';
$field->typeofdata = 'C~O';
$field->defaultvalue = '0';
$block->addField($field);

echo "+ $fieldName (uitype 56) in block {$block->label}\n";
echo "=== Done ===\n";
