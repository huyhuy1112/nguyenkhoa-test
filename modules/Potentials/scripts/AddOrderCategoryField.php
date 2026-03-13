<?php
/*+***********************************************************************************
 * Add "Order Category" picklist field to Opportunities (Potentials).
 * - Label: Order Category
 * - Name:  order_category
 * - Table: vtiger_potentialscf
 * - Column: order_category
 * - Type: picklist (Internal, Project)
 * - Mandatory: Yes (V~M)
 *
 * Safe to run multiple times.
 *************************************************************************************/

// Go to vtiger root from modules/Potentials/scripts
// __DIR__ = modules/Potentials/scripts
// dirname(__DIR__, 3) = vtiger root
chdir(dirname(__DIR__, 3));

require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

global $adb;

$moduleName  = 'Potentials';
$tableName   = 'vtiger_potentialscf';
$columnName  = 'order_category';
$fieldName   = 'order_category';
$fieldLabel  = 'Order Category';
$picklistValues = array('Internal', 'Project');

echo "== AddOrderCategoryField for module $moduleName ==\n";

// 1) Ensure column exists in vtiger_potentialscf
$columnExists = false;
$result = $adb->pquery(
	"SHOW COLUMNS FROM $tableName LIKE ?",
	array($columnName)
);
if ($result && $adb->num_rows($result) > 0) {
	$columnExists = true;
	echo "Column $tableName.$columnName already exists.\n";
} else {
	$adb->pquery(
		"ALTER TABLE $tableName ADD COLUMN $columnName VARCHAR(100)",
		array()
	);
	echo "Added column $tableName.$columnName.\n";
}

// 2) Ensure field exists in vtiger_field for Potentials
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

$field = Vtiger_Field::getInstance($fieldName, $module);

if ($field) {
	echo "Field '$fieldName' already exists in $moduleName (fieldid={$field->id}).\n";
} else {
	$blockLabel = 'LBL_OPPORTUNITY_INFORMATION';
	$block = Vtiger_Block::getInstance($blockLabel, $module);
	if (!$block) {
		echo "ERROR: Block $blockLabel not found in $moduleName.\n";
		exit(1);
	}

	$field = new Vtiger_Field();
	$field->name       = $fieldName;
	$field->label      = $fieldLabel;
	$field->uitype     = 15;              // Picklist
	$field->column     = $columnName;
	$field->tablename  = $tableName;
	$field->columntype = 'VARCHAR(100)';
	$field->typeofdata = 'V~M';           // Mandatory
	$field->displaytype = 1;
	$field->presence    = 0;
	$field->quickcreate = 1;
	$field->masseditable = 1;

	$block->addField($field);
	echo "Created field '$fieldName' in block $blockLabel.\n";

	// 3) Create picklist definition and values
	$field->setPicklistValues($picklistValues);
	echo "Assigned picklist values to '$fieldName'.\n";
}

// Extra: ensure picklist table has values even if field already existed
if ($field) {
	$picklistTable = 'vtiger_' . $fieldName;
	// Create table if not present (some very old installs)
	$tblRes = $adb->pquery(
		"SHOW TABLES LIKE ?",
		array($picklistTable)
	);
	if (!$tblRes || $adb->num_rows($tblRes) === 0) {
		$adb->pquery(
			"CREATE TABLE $picklistTable (
				$fieldName varchar(200) DEFAULT NULL,
				presence int(11) DEFAULT '1',
				picklist_valueid int(11) NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (picklist_valueid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			array()
		);
		echo "Created picklist table $picklistTable.\n";
	}

	// Insert missing picklist values (idempotent)
	foreach ($picklistValues as $val) {
		$val = trim($val);
		if ($val === '') continue;
		$check = $adb->pquery(
			"SELECT 1 FROM $picklistTable WHERE $fieldName = ?",
			array($val)
		);
		if (!$check || $adb->num_rows($check) === 0) {
			$adb->pquery(
				"INSERT INTO $picklistTable ($fieldName, presence) VALUES (?,1)",
				array($val)
			);
			echo "Inserted picklist value '$val' into $picklistTable.\n";
		}
	}
}

// Ensure column appears in default List View (All) for Potentials
try {
	$allFilter = Vtiger_Filter::getInstance('All', $module);
	if ($allFilter && $field) {
		$allFilter->addField($field)->save();
		echo "Added '$fieldName' to default list view 'All'.\n";
	}
} catch (Exception $e) {
	// ignore
}

echo "== Done AddOrderCategoryField ==\n";

