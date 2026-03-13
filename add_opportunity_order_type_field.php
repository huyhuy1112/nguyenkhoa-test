<?php
/*+***********************************************************************************
 * Add "Order Type" picklist field to Opportunities (Potentials).
 * - Không sửa core files.
 * - Chỉ dùng vtlib.
 *************************************************************************************/

chdir(__DIR__);

require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$moduleName = 'Potentials'; // Opportunities
$blockLabel = 'Order Information';

echo "== Start: add Order Type field to $moduleName ==\n";

$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

/** 1. Ensure block exists */
$block = Vtiger_Block::getInstance($blockLabel, $module);
if ($block) {
	echo "Block '$blockLabel' already exists (blockid={$block->id}).\n";
} else {
	$block = new Vtiger_Block();
	$block->label = $blockLabel;
	$module->addBlock($block);
	echo "Created block '$blockLabel' (blockid={$block->id}).\n";
}

/** 2. Ensure field order_type exists */
$fieldName = 'order_type';
$field = Vtiger_Field::getInstance($fieldName, $module);

if ($field) {
	echo "Field '$fieldName' already exists (fieldid={$field->id}).\n";
} else {
	$field = new Vtiger_Field();
	$field->name        = $fieldName;
	$field->label       = 'Order Type';
	$field->uitype      = 15;                // Picklist
	$field->column      = $fieldName;
	$field->columntype  = 'varchar(200)';
	$field->typeofdata  = 'V~O';            // optional
	$field->displaytype = 1;
	$field->presence    = 0;
	$field->quickcreate = 1;
	$field->masseditable = 1;
	$field->sequence    = 0;
	$field->defaultvalue = '';

	$block->addField($field);
	echo "Created field '$fieldName' in '$blockLabel' (fieldid={$field->id}).\n";

	// Picklist values
	$picklistValues = array('Internal Order', 'Project Order');
	global $adb;
	foreach ($picklistValues as $sort => $val) {
		$val = trim($val);
		if ($val === '') continue;
		$adb->pquery(
			'INSERT INTO vtiger_'.$fieldName.' ('.$fieldName.',presence,sortorderid) VALUES (?,?,?)',
			array($val, 1, $sort + 1)
		);
	}
	echo "Added picklist values for '$fieldName'.\n";
}

/** 3. Ensure field appears in default List View (All) */
$filter = Vtiger_Filter::getInstance('All', $module);
if ($filter) {
	$filter->addField($field)->save();
	echo "Added '$fieldName' to default list view 'All'.\n";
} else {
	echo "WARNING: could not find filter 'All' for $moduleName.\n";
}

echo "== Done ==\n";

