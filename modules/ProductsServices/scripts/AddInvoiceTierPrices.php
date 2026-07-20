<?php
/*+***********************************************************************************
 * Add invoice-tier prices on ProductsServices (Hàng hoá) for sales policy.
 * Tiers match customer price list: <1tr, ≥1tr, ≥3tr, ≥5tr, ≥7tr.
 *
 * Run from vtiger root:
 *   php -f modules/ProductsServices/scripts/AddInvoiceTierPrices.php
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

function ps_invoice_getOrCreateBlock(Vtiger_Module $module, $label) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) {
		return $block;
	}
	$block = new Vtiger_Block();
	$block->label = $label;
	$module->addBlock($block);
	echo "  block created: $label\n";
	return $block;
}

function ps_invoice_addCurrencyField(Vtiger_Module $module, Vtiger_Block $block, $name, $label) {
	$f = Vtiger_Field::getInstance($name, $module);
	if ($f) {
		echo "  exists: $name\n";
		// Ensure visible on Edit/Detail.
		try {
			$f->presence = 0;
			$f->displaytype = 1;
			$f->save();
		} catch (Exception $e) {
			/* ignore */
		}
		return $f;
	}
	$f = new Vtiger_Field();
	$f->name = $name;
	$f->label = $label;
	$f->uitype = 71;
	$f->column = $name;
	$f->columntype = 'DECIMAL(25,8)';
	$f->typeofdata = 'N~O';
	$f->displaytype = 1;
	$f->presence = 0;
	$block->addField($f);
	echo "  + $name ($label)\n";
	return $f;
}

echo "=== Add invoice-tier prices on ProductsServices ===\n";

$block = ps_invoice_getOrCreateBlock($module, 'LBL_INVOICE_PRICE_LIST');

$fields = array(
	'price_lt_1m' => 'Price Under 1M',
	'price_gte_1m' => 'Price From 1M',
	'price_gte_3m' => 'Price From 3M',
	'price_gte_5m' => 'Price From 5M',
	'price_gte_7m' => 'Price From 7M',
);

foreach ($fields as $name => $label) {
	ps_invoice_addCurrencyField($module, $block, $name, $label);
}

echo "=== Done ===\n";
echo "Next: open Hàng hoá Edit/Create and fill Giá theo mức hóa đơn.\n";
