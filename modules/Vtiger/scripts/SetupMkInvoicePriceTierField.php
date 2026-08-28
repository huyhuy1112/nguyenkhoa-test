<?php
/*+***********************************************************************************
 * Invoice price-tier picklist for Quotes + SalesOrder.
 * Run from the vtiger root:
 *   php -f modules/Vtiger/scripts/SetupMkInvoicePriceTierField.php
 * Safe to run multiple times.
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$picklistValues = array(
	'auto',
	'lt_1m',
	'gte_1m',
	'gte_3m',
	'gte_5m',
	'gte_7m',
);

function mkInvoiceTierEnsureBlock(Vtiger_Module $module, $label) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) {
		return $block;
	}
	$block = new Vtiger_Block();
	$block->label = $label;
	$module->addBlock($block);
	echo "  Added block: $label\n";
	return $block;
}

function mkInvoiceTierEnsureField(Vtiger_Module $module, Vtiger_Block $block, array $picklistValues) {
	$name = 'mk_invoice_price_tier';
	$field = Vtiger_Field::getInstance($name, $module);
	if ($field) {
		echo "  Field exists on {$module->name}: $name\n";
		return $field;
	}

	$field = new Vtiger_Field();
	$field->name = $name;
	$field->label = 'Invoice Price Tier';
	$field->uitype = 15;
	$field->column = $name;
	$field->columntype = 'VARCHAR(32)';
	$field->typeofdata = 'V~O';
	$field->displaytype = 1;
	$block->addField($field);
	$field->setPicklistValues($picklistValues);
	echo "  Added field on {$module->name}: $name\n";
	return $field;
}

echo "=== mk_invoice_price_tier setup ===\n";

$modules = array(
	'Quotes' => 'LBL_QUOTE_INFORMATION',
	'SalesOrder' => 'LBL_SO_INFORMATION',
);

foreach ($modules as $moduleName => $blockLabel) {
	$module = Vtiger_Module::getInstance($moduleName);
	if (!$module) {
		echo "WARN: $moduleName module not found\n";
		continue;
	}
	$block = mkInvoiceTierEnsureBlock($module, $blockLabel);
	mkInvoiceTierEnsureField($module, $block, $picklistValues);
}

echo "=== Done ===\n";
