<?php
/*+***********************************************************************************
 * Add fields, blocks and layout enhancements for Campaigns module.
 *
 * - Add start_date field to Campaign Details block
 * - Move closingdate to Expectations & Actuals block
 * - Add actual_end_date field
 * - Create Campaign Phases block with phase1..phase5 expected/actual/comment fields
 *
 * Run from vtiger root:
 *   php -f modules/Campaigns/scripts/AddCampaignEnhancements.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // modules/Campaigns/scripts -> vtiger root

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'include/database/PearDatabase.php';

echo "=== Campaigns enhancements installer ===\n";

$moduleName = 'Campaigns';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

$adb = PearDatabase::getInstance();

/**
 * Helper: get or create block by label for module.
 */
function ce_getOrCreateBlock(Vtiger_Module $module, $label) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) {
		echo "Block exists: $label\n";
		return $block;
	}
	$block = new Vtiger_Block();
	$block->label = $label;
	$module->addBlock($block);
	echo "Block created: $label\n";
	return $block;
}

/**
 * Helper: ensure a field exists in a specific table/column.
 * If it already exists, just return it.
 */
function ce_addFieldIfMissing(
	Vtiger_Module $module,
	Vtiger_Block $block,
	$table,
	$column,
	$name,
	$label,
	$uitype,
	$typeofdata
) {
	$field = Vtiger_Field::getInstance($name, $module);
	if ($field) {
		echo "  Field exists: $name\n";
		return $field;
	}

	$field = new Vtiger_Field();
	$field->name       = $name;
	$field->label      = $label;
	$field->table      = $table;
	$field->column     = $column;
	$field->uitype     = $uitype;
	$field->typeofdata = $typeofdata;
	$field->presence   = 0; // active
	$field->displaytype = 1;

	$block->addField($field);
	echo "  Field created: $name (table=$table, column=$column, uitype=$uitype, typeofdata=$typeofdata)\n";

	return $field;
}

/**
 * Helper: move an existing field to another block.
 */
function ce_moveFieldToBlock(Vtiger_Module $module, $fieldName, Vtiger_Block $targetBlock) {
	$field = Vtiger_Field::getInstance($fieldName, $module);
	if (!$field) {
		echo "  Field not found (cannot move): $fieldName\n";
		return;
	}
	if ($field->block && $field->block->id == $targetBlock->id) {
		echo "  Field $fieldName already in target block {$targetBlock->label}\n";
		return;
	}
	$field->block = $targetBlock;
	$field->save();
	echo "  Field $fieldName moved to block {$targetBlock->label}\n";
}

// -------------------------------------------------------------------------
// 1) Campaign Details Block: add start_date
// -------------------------------------------------------------------------

echo "\n-- Step 1: Campaign Details block (start_date) --\n";

// Core vtiger label for main Campaign block
$detailsBlock = ce_getOrCreateBlock($module, 'LBL_CAMPAIGN_INFORMATION');

ce_addFieldIfMissing(
	$module,
	$detailsBlock,
	'vtiger_campaign',
	'start_date',
	'start_date',
	'Start Date',
	5,      // date
	'D~M'   // mandatory date
);

// -------------------------------------------------------------------------
// 2) Expectations & Actuals Block: move closingdate, add actual_end_date
// -------------------------------------------------------------------------

echo "\n-- Step 2: Expectations & Actuals block --\n";

$expectBlock = ce_getOrCreateBlock($module, 'LBL_EXPECTATIONS_AND_ACTUALS');

// Move existing closingdate field to Expectations & Actuals block
ce_moveFieldToBlock($module, 'closingdate', $expectBlock);

// Add actual_end_date if missing
ce_addFieldIfMissing(
	$module,
	$expectBlock,
	'vtiger_campaign',
	'actual_end_date',
	'actual_end_date',
	'Actual End Date',
	5,      // date
	'D~O'   // optional date
);

// -------------------------------------------------------------------------
// 3) Campaign Phases block & fields (in vtiger_campaignscf)
// -------------------------------------------------------------------------

echo "\n-- Step 3: Campaign Phases block & CF fields --\n";

$phasesBlock = ce_getOrCreateBlock($module, 'LBL_CAMPAIGN_PHASES');

// For custom fields we will store in vtiger_campaignscf with column names cf_<fieldname>
$cfTable = 'vtiger_campaignscf';

for ($i = 1; $i <= 5; $i++) {
	echo " Phase $i\n";

	$expectedName   = "phase{$i}_expected";
	$expectedColumn = "cf_{$expectedName}";
	$actualName     = "phase{$i}_actual";
	$actualColumn   = "cf_{$actualName}";
	$commentName    = "phase{$i}_comment";
	$commentColumn  = "cf_{$commentName}";

	// Expected (number)
	ce_addFieldIfMissing(
		$module,
		$phasesBlock,
		$cfTable,
		$expectedColumn,
		$expectedName,
		"Phase {$i} Expected",
		7,       // number
		'N~O'
	);

	// Actual (number)
	ce_addFieldIfMissing(
		$module,
		$phasesBlock,
		$cfTable,
		$actualColumn,
		$actualName,
		"Phase {$i} Actual",
		7,
		'N~O'
	);

	// Comment (textarea)
	ce_addFieldIfMissing(
		$module,
		$phasesBlock,
		$cfTable,
		$commentColumn,
		$commentName,
		'Comment',
		19,      // textarea
		'V~O'
	);
}

echo "\n=== Done: Campaigns enhancements installed. ===\n";

