<?php
/*+***********************************************************************************
 * Add internal-order workflow fields to SalesOrder (idempotent).
 *
 * Run:
 *   php -f modules/SalesOrder/scripts/AddInternalOrderFields.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$moduleName = 'SalesOrder';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

$block = Vtiger_Block::getInstance('LBL_SO_INFORMATION', $module);
if (!$block) {
	$blocks = Vtiger_Block::getAllForModule($module);
	$block = !empty($blocks) ? $blocks[0] : null;
}
if (!$block) {
	echo "ERROR: Cannot resolve target block for $moduleName.\n";
	exit(1);
}

/**
 * Create field if not exists, safe for rerun.
 */
function ensureField(Vtiger_Module $module, Vtiger_Block $block, $name, $label, $uitype, $column, $columntype, $typeofdata, $picklistValues = array(), $relatedModules = array()) {
	$field = Vtiger_Field::getInstance($name, $module);
	if (!$field) {
		$field = new Vtiger_Field();
		$field->name = $name;
		$field->label = $label;
		$field->uitype = $uitype;
		$field->column = $column;
		$field->columntype = $columntype;
		$field->typeofdata = $typeofdata;
		$block->addField($field);
		echo "Created field: $name\n";
	} else {
		echo "Field already exists: $name\n";
	}

	if ($field && !empty($picklistValues) && (int) $field->uitype === 15) {
		$field->setPicklistValues($picklistValues);
	}
	if ($field && !empty($relatedModules) && (int) $field->uitype === 10) {
		$field->setRelatedModules($relatedModules);
	}
	return $field;
}

ensureField(
	$module,
	$block,
	'team_group',
	'Team Group',
	15,
	'team_group',
	'VARCHAR(100)',
	'V~O',
	array('MKT', 'Sale', 'Support', 'Other')
);

ensureField(
	$module,
	$block,
	'purpose',
	'Purpose',
	19,
	'purpose',
	'TEXT',
	'V~O'
);

ensureField(
	$module,
	$block,
	'internal_cost',
	'Cost',
	71,
	'internal_cost',
	'DECIMAL(25,8)',
	'N~O'
);

ensureField(
	$module,
	$block,
	'needed_time',
	'Needed Time',
	5,
	'needed_time',
	'DATE',
	'D~O'
);

ensureField(
	$module,
	$block,
	'internal_order_status',
	'Status',
	15,
	'internal_order_status',
	'VARCHAR(100)',
	'V~O',
	array('Pending', 'Approved', 'Rejected')
);

ensureField(
	$module,
	$block,
	'created_user_id',
	'Ordered By',
	53,
	'created_user_id',
	'INT(19)',
	'V~O'
);

ensureField(
	$module,
	$block,
	'approved_by',
	'Approved By',
	53,
	'approved_by',
	'INT(19)',
	'V~O'
);

ensureField(
	$module,
	$block,
	'approval_note',
	'Approval Note',
	19,
	'approval_note',
	'TEXT',
	'V~O'
);

echo "Done.\n";
