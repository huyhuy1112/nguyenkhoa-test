<?php
/**
 * ProductsServices detail/edit form tweaks:
 * - SKU sequence above product name
 * - Hide warranty + related_projects
 * - Align fieldlabel keys with language packs
 *
 * Run: php -f modules/ProductsServices/scripts/UpdateDetailFormLayout.php
 */
chdir(dirname(__DIR__, 3));

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$moduleName = 'ProductsServices';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	fwrite(STDERR, "ERROR: Module $moduleName not found\n");
	exit(1);
}

$adb = PearDatabase::getInstance();
$tabId = (int) $module->id;
echo "=== Update ProductsServices form layout (tabid={$tabId}) ===\n";

function ps_setPresence($adb, $tabId, $fieldName, $presence) {
	$adb->pquery(
		'UPDATE vtiger_field SET presence = ? WHERE tabid = ? AND fieldname = ?',
		array((int) $presence, (int) $tabId, $fieldName)
	);
	echo "  presence[{$fieldName}] = {$presence}\n";
}

function ps_setLabel($adb, $tabId, $fieldName, $label) {
	$adb->pquery(
		'UPDATE vtiger_field SET fieldlabel = ? WHERE tabid = ? AND fieldname = ?',
		array($label, (int) $tabId, $fieldName)
	);
	echo "  label[{$fieldName}] = {$label}\n";
}

function ps_getBlockId($adb, $tabId, $fieldName) {
	$res = $adb->pquery(
		'SELECT block FROM vtiger_field WHERE tabid = ? AND fieldname = ?',
		array((int) $tabId, $fieldName)
	);
	if ($res && $adb->num_rows($res)) {
		return (int) $adb->query_result($res, 0, 'block');
	}
	return 0;
}

function ps_setSequence($adb, $tabId, $fieldName, $sequence) {
	$adb->pquery(
		'UPDATE vtiger_field SET sequence = ? WHERE tabid = ? AND fieldname = ?',
		array((int) $sequence, (int) $tabId, $fieldName)
	);
	echo "  sequence[{$fieldName}] = {$sequence}\n";
}

// Labels (language packs map these keys)
ps_setLabel($adb, $tabId, 'productsservicesname', 'Name');
ps_setLabel($adb, $tabId, 'sku', 'SKU');
ps_setLabel($adb, $tabId, 'specification', 'Specification');

// Hide fields from Detail/Edit (presence 1 = hidden)
ps_setPresence($adb, $tabId, 'warranty', 1);
ps_setPresence($adb, $tabId, 'related_projects', 1);
ps_setPresence($adb, $tabId, 'used_projects', 1);
ps_setLabel($adb, $tabId, 'unit', 'Unit');
ps_setLabel($adb, $tabId, 'item_type', 'Type');

// Put SKU first in the same block as Name when possible
$nameBlock = ps_getBlockId($adb, $tabId, 'productsservicesname');
$skuBlock = ps_getBlockId($adb, $tabId, 'sku');
if ($nameBlock > 0 && $skuBlock > 0 && $nameBlock !== $skuBlock) {
	$adb->pquery(
		'UPDATE vtiger_field SET block = ? WHERE tabid = ? AND fieldname = ?',
		array($nameBlock, $tabId, 'sku')
	);
	echo "  moved sku -> same block as productsservicesname ({$nameBlock})\n";
}

$preferredOrder = array(
	'sku' => 1,
	'productsservicesname' => 2,
	'item_type' => 3,
	'unit' => 4,
	'price' => 5,
	'wholesale_price' => 6,
	'specification' => 7,
	'assigned_user_id' => 8,
);

$blockId = ps_getBlockId($adb, $tabId, 'productsservicesname');
if ($blockId > 0) {
	// Re-number all fields in the block, putting preferred ones first.
	$res = $adb->pquery(
		'SELECT fieldname FROM vtiger_field WHERE tabid = ? AND block = ? ORDER BY sequence ASC, fieldid ASC',
		array($tabId, $blockId)
	);
	$seq = 10;
	$others = array();
	if ($res) {
		$rows = $adb->num_rows($res);
		for ($i = 0; $i < $rows; $i++) {
			$fn = $adb->query_result($res, $i, 'fieldname');
			if (!isset($preferredOrder[$fn])) {
				$others[] = $fn;
			}
		}
	}
	foreach ($preferredOrder as $fn => $wanted) {
		$f = Vtiger_Field::getInstance($fn, $module);
		if ($f) {
			ps_setSequence($adb, $tabId, $fn, $wanted);
		}
	}
	foreach ($others as $fn) {
		ps_setSequence($adb, $tabId, $fn, $seq);
		$seq++;
	}
}

echo "=== Done ===\n";
