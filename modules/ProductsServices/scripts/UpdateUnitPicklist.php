<?php
/**
 * Replace ProductsServices.unit picklist with: cái, hộp, set, bộ
 * Maps legacy values (pcs/box/kg/...) on existing records.
 *
 * Run from vtiger root (Docker):
 *   php -f modules/ProductsServices/scripts/UpdateUnitPicklist.php
 */
chdir(dirname(__DIR__, 3));

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'vtlib/Vtiger/Field.php';

$moduleName = 'ProductsServices';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	fwrite(STDERR, "ERROR: Module $moduleName not found\n");
	exit(1);
}

$field = Vtiger_Field::getInstance('unit', $module);
if (!$field) {
	fwrite(STDERR, "ERROR: Field unit not found\n");
	exit(1);
}

$adb = PearDatabase::getInstance();
$newValues = array('cái', 'hộp', 'set', 'bộ');
$mapLegacy = array(
	'pcs' => 'cái',
	'Piece' => 'cái',
	'piece' => 'cái',
	'box' => 'hộp',
	'Box' => 'hộp',
	'kg' => 'cái',
	'Kg' => 'cái',
	'Set' => 'set',
	'Liter' => 'cái',
	'Hour' => 'cái',
	'Day' => 'cái',
	'Month' => 'cái',
);

echo "=== Update unit picklist ===\n";

// Ensure new values exist (setPicklistValues skips duplicates safely in vtlib)
$field->setPicklistValues($newValues);
echo "  + ensured picklist values: " . implode(', ', $newValues) . "\n";

// Remap existing productsservices.unit values
foreach ($mapLegacy as $from => $to) {
	$adb->pquery(
		'UPDATE vtiger_productsservices SET unit = ? WHERE unit = ?',
		array($to, $from)
	);
	echo "  mapped unit '{$from}' -> '{$to}'\n";
}

ps_setLabelSafe($adb, (int) $module->id, 'unit', 'Unit');
ps_setLabelSafe($adb, (int) $module->id, 'item_type', 'Type');
ps_setPresenceSafe($adb, (int) $module->id, 'used_projects', 1);
ps_setPresenceSafe($adb, (int) $module->id, 'warranty', 1);
ps_setPresenceSafe($adb, (int) $module->id, 'related_projects', 1);

echo "Done.\n";

function ps_setLabelSafe($adb, $tabId, $fieldName, $label) {
	$adb->pquery(
		'UPDATE vtiger_field SET fieldlabel = ? WHERE tabid = ? AND fieldname = ?',
		array($label, $tabId, $fieldName)
	);
	echo "  label[{$fieldName}] = {$label}\n";
}

function ps_setPresenceSafe($adb, $tabId, $fieldName, $presence) {
	$adb->pquery(
		'UPDATE vtiger_field SET presence = ? WHERE tabid = ? AND fieldname = ?',
		array((int) $presence, $tabId, $fieldName)
	);
	echo "  presence[{$fieldName}] = {$presence}\n";
}
