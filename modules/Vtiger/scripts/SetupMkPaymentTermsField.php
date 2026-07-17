<?php
/*+***********************************************************************************
 * Payment terms picklist for Quotes + SalesOrder (Odoo-style).
 * Run: php -f modules/Vtiger/scripts/SetupMkPaymentTermsField.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$picklistValues = array(
	'Thanh toán ngay',
	'15 ngày',
	'21 ngày',
	'30 ngày',
	'45 ngày',
	'Cuối tháng kế tiếp',
	'10 ngày sau ngày cuối tháng kế tiếp',
	'30% trả ngay, còn lại trả trong 60 ngày',
);

$paymentMethodValues = array(
	'Tiền mặt',
	'Chuyển khoản',
	'Thẻ',
	'Ví',
);

function ensureBlock(Vtiger_Module $module, $label) {
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

function addPaymentMethodField(Vtiger_Module $module, Vtiger_Block $block, array $picklistValues) {
	$name = 'mk_payment_method';
	$f = Vtiger_Field::getInstance($name, $module);
	if ($f) {
		echo "  Field exists on {$module->name}: $name\n";
		return $f;
	}
	$f = new Vtiger_Field();
	$f->name = $name;
	$f->label = 'Payment Method';
	$f->uitype = 15;
	$f->column = $name;
	$f->columntype = 'VARCHAR(64)';
	$f->typeofdata = 'V~O';
	$f->displaytype = 1;
	$block->addField($f);
	$f->setPicklistValues($picklistValues);
	echo "  Added field on {$module->name}: $name\n";
	return $f;
}

function addPaymentTermsField(Vtiger_Module $module, Vtiger_Block $block, array $picklistValues) {
	$name = 'mk_payment_terms';
	$f = Vtiger_Field::getInstance($name, $module);
	if ($f) {
		echo "  Field exists on {$module->name}: $name\n";
		return $f;
	}
	$f = new Vtiger_Field();
	$f->name = $name;
	$f->label = 'Payment Terms';
	$f->uitype = 15;
	$f->column = $name;
	$f->columntype = 'VARCHAR(128)';
	$f->typeofdata = 'V~O';
	$f->displaytype = 1;
	$block->addField($f);
	$f->setPicklistValues($picklistValues);
	echo "  Added field on {$module->name}: $name\n";
	return $f;
}

echo "=== mk_payment_terms setup ===\n";

$quotes = Vtiger_Module::getInstance('Quotes');
if ($quotes) {
	$quotesBlock = ensureBlock($quotes, 'LBL_QUOTE_INFORMATION');
	addPaymentTermsField($quotes, $quotesBlock, $picklistValues);
	addPaymentMethodField($quotes, $quotesBlock, $paymentMethodValues);
} else {
	echo "WARN: Quotes module not found\n";
}

$salesOrder = Vtiger_Module::getInstance('SalesOrder');
if ($salesOrder) {
	$soBlock = ensureBlock($salesOrder, 'LBL_SO_INFORMATION');
	addPaymentTermsField($salesOrder, $soBlock, $picklistValues);
	addPaymentMethodField($salesOrder, $soBlock, $paymentMethodValues);
} else {
	echo "WARN: SalesOrder module not found\n";
}

echo "=== Done ===\n";
