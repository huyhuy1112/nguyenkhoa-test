<?php
/*+***********************************************************************************
 * Create 2 custom views for Opportunities (Potentials) based on order_category:
 *  - Internal Orders (order_category = Internal)
 *  - Project Orders  (order_category = Project)
 * Safe to run multiple times.
 *************************************************************************************/

// modules/Potentials/scripts -> vtiger root
chdir(dirname(__DIR__, 3));

require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

global $adb;

$moduleName = 'Potentials';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

$field = Vtiger_Field::getInstance('order_category', $module);
if (!$field) {
	echo "ERROR: Field 'order_category' not found in $moduleName. Run AddOrderCategoryField first.\n";
	exit(1);
}

/**
 * Ensure a custom view exists; create if missing.
 */
function ensureOrderCategoryFilter(Vtiger_Module $module, $name, $categoryValue) {
	global $adb;

	$existing = Vtiger_Filter::getInstance($name, $module);
	if ($existing) {
		echo "Custom view '$name' already exists (cvid={$existing->id}).\n";
		return $existing;
	}

	$filter = new Vtiger_Filter();
	$filter->name        = $name;
	$filter->isdefault   = 0;
	$filter->isfeatured  = 0;
	$filter->status      = 0;
	$filter->entitytype  = $module->name;
	$filter->description = $name;

	$module->addFilter($filter);
	echo "Created custom view '$name' (cvid={$filter->id}).\n";

	// Columns for the view (basic fields + order_category)
	$columns = array('potentialname', 'sales_stage', 'amount', 'closingdate', 'assigned_user_id', 'order_category');
	foreach ($columns as $fname) {
		$f = Vtiger_Field::getInstance($fname, $module);
		if ($f) {
			$filter->addField($f);
		}
	}

	// Condition: order_category = <value>
	$orderCatField = Vtiger_Field::getInstance('order_category', $module);
	if ($orderCatField) {
		$columnName = $orderCatField->table . ':' . $orderCatField->column . ':' . $orderCatField->name . ':' . $module->name;
		$adb->pquery(
			'INSERT INTO vtiger_cvadvfilter (cvid,columnindex,columnname,comparator,value,groupid,column_condition)
             VALUES (?,?,?,?,?,?,?)',
			array(
				$filter->id,
				1,
				$columnName,
				'e',                // equals
				$categoryValue,
				1,
				''                  // no extra AND/OR
			)
		);
		echo "  Added condition order_category = '$categoryValue' to '$name'.\n";
	}

	return $filter;
}

// 1) Internal Orders: order_category = Internal
ensureOrderCategoryFilter($module, 'Internal Orders', 'Internal');

// 2) Project Orders: order_category = Project
ensureOrderCategoryFilter($module, 'Project Orders', 'Project');

echo "== Done AddOrderCategoryFilters ==\n";

