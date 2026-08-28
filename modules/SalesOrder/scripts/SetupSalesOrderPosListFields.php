<?php
/*+***********************************************************************************
 * SalesOrder POS list — ensure received (Khách đã trả) field exists (idempotent).
 *
 * Run: php -f modules/SalesOrder/scripts/SetupSalesOrderPosListFields.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

global $adb;

$module = Vtiger_Module::getInstance('SalesOrder');
if (!$module) {
	echo "ERROR: SalesOrder module not found.\n";
	exit(1);
}

$block = Vtiger_Block::getInstance('LBL_SO_INFORMATION', $module);
if (!$block) {
	$blocks = Vtiger_Block::getAllForModule($module);
	$block = !empty($blocks) ? $blocks[0] : null;
}
if (!$block) {
	echo "ERROR: Cannot resolve block for SalesOrder.\n";
	exit(1);
}

$colRes = $adb->pquery("SHOW COLUMNS FROM vtiger_salesorder LIKE 'received'", array());
if (!$adb->num_rows($colRes)) {
	$adb->pquery('ALTER TABLE vtiger_salesorder ADD COLUMN received DECIMAL(25,8) DEFAULT 0', array());
	echo "Column vtiger_salesorder.received added.\n";
} else {
	echo "Column vtiger_salesorder.received already exists.\n";
}

$field = Vtiger_Field::getInstance('received', $module);
if (!$field) {
	$field = new Vtiger_Field();
	$field->name = 'received';
	$field->label = 'Received';
	$field->table = 'vtiger_salesorder';
	$field->column = 'received';
	$field->columntype = 'DECIMAL(25,8)';
	$field->uitype = 72;
	$field->typeofdata = 'N~O';
	$field->displaytype = 1;
	$field->defaultvalue = 0;
	$block->addField($field);
	echo "Field received created.\n";
} else {
	echo "Field received already exists.\n";
}

echo "Done.\n";
