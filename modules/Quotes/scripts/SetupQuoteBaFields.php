<?php
/*+***********************************************************************************
 * BA-aligned Quotes custom fields + organization bank columns.
 * Run: php -f modules/Quotes/scripts/SetupQuoteBaFields.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'modules/Quotes/helpers/QuoteBaService.php';

$module = Vtiger_Module::getInstance('Quotes');
if (!$module) {
	echo "ERROR: Quotes module not found.\n";
	exit(1);
}

function ensureBlock(Vtiger_Module $module, $label, $sequence = null) {
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

function addFieldIfMissing(Vtiger_Module $module, Vtiger_Block $block, $name, $label, $uitype, $columntype, $typeofdata, $displaytype = 1) {
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
	$f->displaytype = $displaytype;
	$block->addField($f);
	echo "  Added field: $name\n";
	return $f;
}

echo "=== Quotes BA setup ===\n";

Quotes_QuoteBaService_Helper::ensureOrganizationBankColumns();
echo "  Organization bank columns OK\n";

$blockQuote = ensureBlock($module, 'LBL_QUOTE_INFORMATION');
$blockDesc = ensureBlock($module, 'LBL_DESCRIPTION_INFORMATION');
$blockVat = ensureBlock($module, 'LBL_MK_QUOTE_VAT');

addFieldIfMissing($module, $blockQuote, 'mk_quote_date', 'Quote Date', 5, 'DATE', 'D~O');
addFieldIfMissing($module, $blockQuote, 'mk_customer_phone', 'Customer Phone', 11, 'VARCHAR(50)', 'V~O');
addFieldIfMissing($module, $blockQuote, 'mk_customer_email', 'Customer Email', 13, 'VARCHAR(100)', 'E~O');
addFieldIfMissing($module, $blockQuote, 'mk_client_company', 'Client Company', 1, 'VARCHAR(255)', 'V~O');

addFieldIfMissing($module, $blockDesc, 'mk_product_info', 'Product Information', 19, 'TEXT', 'V~O');

addFieldIfMissing($module, $blockVat, 'mk_vat_percent', 'VAT Percent', 7, 'DECIMAL(5,2)', 'N~O');
addFieldIfMissing($module, $blockVat, 'mk_vat_amount', 'VAT Amount', 71, 'DECIMAL(25,2)', 'N~O', 2);
addFieldIfMissing($module, $blockVat, 'mk_amount_in_words', 'Amount In Words', 1, 'VARCHAR(512)', 'V~O', 2);

echo "=== Done ===\n";
