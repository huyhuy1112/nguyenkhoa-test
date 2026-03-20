<?php
/*+***********************************************************************************
 * FixCampaignLayout.php
 *
 * - Ensure Campaign blocks order:
 *   1) LBL_CAMPAIGN_INFORMATION
 *   2) LBL_EXPECTATIONS_AND_ACTUALS
 *   3) LBL_DESCRIPTION_INFORMATION (Description Details)
 *   4) LBL_CAMPAIGN_PHASES
 *
 * - Ensure fields are assigned to correct blocks.
 *
 * Run from vtiger root:
 *   php -f modules/Campaigns/scripts/FixCampaignLayout.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // modules/Campaigns/scripts -> vtiger root

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'include/database/PearDatabase.php';

echo "=== Fix Campaign layout ===\n";

$moduleName = 'Campaigns';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

/** @var PearDatabase $adb */
$adb = PearDatabase::getInstance();
$tabId = $module->id;

function fcl_getBlock($label, Vtiger_Module $module) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) {
		echo "Block exists: $label (id={$block->id})\n";
	}
	return $block;
}

// ----------------------------------------------------------------------
// 1) Ensure blocks exist and order
// ----------------------------------------------------------------------

$detailsBlock = fcl_getBlock('LBL_CAMPAIGN_INFORMATION', $module);
$expectBlock  = fcl_getBlock('LBL_EXPECTATIONS_AND_ACTUALS', $module);
$descBlock    = fcl_getBlock('LBL_DESCRIPTION_INFORMATION', $module);
$phasesBlock  = fcl_getBlock('LBL_CAMPAIGN_PHASES', $module);

// If description block doesn't exist, create it
if (!$descBlock) {
	$descBlock = new Vtiger_Block();
	$descBlock->label = 'LBL_DESCRIPTION_INFORMATION';
	$module->addBlock($descBlock);
	echo "Block created: LBL_DESCRIPTION_INFORMATION (Description Details)\n";
}

// If phases block doesn't exist, create it
if (!$phasesBlock) {
	$phasesBlock = new Vtiger_Block();
	$phasesBlock->label = 'LBL_CAMPAIGN_PHASES';
	$module->addBlock($phasesBlock);
	echo "Block created: LBL_CAMPAIGN_PHASES\n";
}

// Set block sequences in desired order (keep others after)
$desiredOrder = array(
	'LBL_CAMPAIGN_INFORMATION',
	'LBL_EXPECTATIONS_AND_ACTUALS',
	'LBL_DESCRIPTION_INFORMATION',
	'LBL_CAMPAIGN_PHASES',
);

echo "\n-- Fix block sequence --\n";
$seq = 1;
foreach ($desiredOrder as $label) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) {
		$adb->pquery(
			'UPDATE vtiger_blocks SET sequence = ? WHERE blockid = ?',
			array($seq, $block->id)
		);
		echo "  Block {$label} => sequence {$seq}\n";
		$seq++;
	} else {
		echo "  WARN: Block {$label} not found while setting sequence.\n";
	}
}

// ----------------------------------------------------------------------
// 2) Ensure fields are in correct blocks
// ----------------------------------------------------------------------

function fcl_moveFieldList(Vtiger_Module $module, Vtiger_Block $block, array $fieldNames) {
	foreach ($fieldNames as $name) {
		$field = Vtiger_Field::getInstance($name, $module);
		if (!$field) {
			echo "  WARN: Field {$name} not found (skip)\n";
			continue;
		}
		if ($field->block && (int)$field->block->id === (int)$block->id) {
			echo "  Field {$name} already in {$block->label}\n";
			continue;
		}
		$field->block = $block;
		$field->save();
		echo "  Field {$name} moved to {$block->label}\n";
	}
}

echo "\n-- Fix field positions: Campaign Details --\n";
$detailsFields = array(
	'campaignname',
	'campaignstatus',
	'campaigntype',
	'product_id',
	'targetaudience',
	'sponsor',
	'numsent',
	'targetsize',
	'start_date',
	'assigned_user_id',
);
if ($detailsBlock) {
	fcl_moveFieldList($module, $detailsBlock, $detailsFields);
}

echo "\n-- Fix field positions: Expectations & Actuals --\n";
$expectFields = array(
	'budgetcost',
	'actualcost',
	'expectedresponse',
	'expectedrevenue',
	'expectedsalescount',
	'expectedresponsecount',
	'expectedroi',
	'actualsalescount',
	'actualresponsecount',
	'actualroi',
	'closingdate',
	'actual_end_date',
);
if ($expectBlock) {
	fcl_moveFieldList($module, $expectBlock, $expectFields);
}

echo "\n-- Verify Campaign Phases fields in vtiger_campaignscf --\n";
if ($phasesBlock) {
	for ($i = 1; $i <= 5; $i++) {
		foreach (array('expected', 'actual', 'comment') as $suffix) {
			$name = "phase{$i}_{$suffix}";
			$field = Vtiger_Field::getInstance($name, $module);
			if (!$field) {
				echo "  WARN: Phase field {$name} not found\n";
				continue;
			}
			// Ensure table is vtiger_campaignscf
			if ($field->table !== 'vtiger_campaignscf') {
				$field->table = 'vtiger_campaignscf';
				$field->save();
				echo "  Field {$name}: table set to vtiger_campaignscf\n";
			}
			// Ensure in Campaign Phases block
			if (!$field->block || (int)$field->block->id !== (int)$phasesBlock->id) {
				$field->block = $phasesBlock;
				$field->save();
				echo "  Field {$name} moved to LBL_CAMPAIGN_PHASES\n";
			}
		}
	}
}

echo "\n=== Done: FixCampaignLayout ===\n";

