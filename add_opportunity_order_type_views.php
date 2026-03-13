<?php
/*+***********************************************************************************
 * Create 3 custom views for Opportunities (Orders) based on order_type:
 *  - All Orders
 *  - Internal Orders
 *  - Project Orders
 * and register HEADERSCRIPT to load password-protection JS.
 *************************************************************************************/

chdir(__DIR__);

require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$moduleName = 'Potentials';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

$orderTypeField = Vtiger_Field::getInstance('order_type', $module);
if (!$orderTypeField) {
	echo "ERROR: Field 'order_type' not found. Run add_opportunity_order_type_field.php first.\n";
	exit(1);
}

/**
 * Helper: create custom view if not exists.
 */
function ensureFilter($module, $name, $isDefault = false, $orderTypeComparator = null, $orderTypeValue = null) {
	global $adb;

	$existing = Vtiger_Filter::getInstance($name, $module);
	if ($existing) {
		echo "Custom view '$name' already exists (cvid={$existing->id}).\n";
		return $existing;
	}

	$filter = new Vtiger_Filter();
	$filter->name       = $name;
	$filter->isdefault  = $isDefault ? 1 : 0;
	$filter->isfeatured = 0;
	$filter->status     = 0;
	$filter->entitytype = $module->name;
	$filter->description = $name;
	$module->addFilter($filter);
	echo "Created custom view '$name' (cvid={$filter->id}).\n";

	// Columns: show some basic fields plus order_type
	$basicFields = array('potentialname', 'sales_stage', 'amount', 'closingdate', 'assigned_user_id');
	foreach ($basicFields as $fname) {
		$f = Vtiger_Field::getInstance($fname, $module);
		if ($f) $filter->addField($f);
	}
	$orderTypeField = Vtiger_Field::getInstance('order_type', $module);
	if ($orderTypeField) $filter->addField($orderTypeField);

	// Condition on order_type if requested
	if ($orderTypeComparator && $orderTypeValue !== null && $orderTypeField) {
		$field = $orderTypeField;
		$columnName = $field->table . ':' . $field->column . ':' . $field->name . ':' . $module->name;

		$adb->pquery(
			'INSERT INTO vtiger_cvadvfilter (cvid,columnindex,columnname,comparator,value,groupid,column_condition)
             VALUES (?,?,?,?,?,?,?)',
			array(
				$filter->id,
				1,
				$columnName,
				$orderTypeComparator,   // e = equals
				$orderTypeValue,
				1,
				''                      // no extra condition
			)
		);
		echo "  Added condition order_type {$orderTypeComparator} '{$orderTypeValue}' to '$name'.\n";
	}

	return $filter;
}

// 1. All Orders (no filter)
$allOrdersFilter      = ensureFilter($module, 'All Orders', false, null, null);
// 2. Internal Orders (order_type = Internal Order)
$internalOrdersFilter = ensureFilter($module, 'Internal Orders', false, 'e', 'Internal Order');
// 3. Project Orders (order_type = Project Order)
$projectOrdersFilter  = ensureFilter($module, 'Project Orders', false, 'e', 'Project Order');

// 4. Register HEADERSCRIPT link to load password-protection JS on Opportunities list view
$linkLabel = 'OrderTypeInternalProtection';
$linkUrl   = 'layouts/v7/modules/Potentials/resources/OrderTypeInternalProtection.js';

// Use vtlib API to avoid duplicates
$existingLinks = $module->getLinksForExport();
$hasHeaderScript = false;
if (is_array($existingLinks)) {
	foreach ($existingLinks as $l) {
		if ($l->linktype === 'HEADERSCRIPT' && $l->linkurl === $linkUrl) {
			$hasHeaderScript = true;
			break;
		}
	}
}

if (!$hasHeaderScript) {
	$module->addLink('HEADERSCRIPT', $linkLabel, $linkUrl);
	echo "Registered HEADERSCRIPT link for Opportunities: $linkUrl\n";
} else {
	echo "HEADERSCRIPT link for Opportunities already registered: $linkUrl\n";
}

echo "== Done ==\n";

