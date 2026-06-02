<?php
/*+***********************************************************************************
 * Idempotent: add campaign_phase_count, phase1..5 start/end dates, update comment labels.
 * Run from vtiger root: php -f modules/Campaigns/scripts/EnsureCampaignPhaseExtendedFields.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'include/database/PearDatabase.php';

echo "=== Campaigns phase + counter fields (ensure) ===\n";

$moduleName = 'Campaigns';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

$adb = PearDatabase::getInstance();
$tabId = (int) getTabid('Campaigns');

function ecp_getOrCreateBlock(Vtiger_Module $module, $label) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) {
		return $block;
	}
	$block = new Vtiger_Block();
	$block->label = $label;
	$module->addBlock($block);
	echo "Block created: $label\n";
	return $block;
}

function ecp_addFieldIfMissing(
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
	$field->name = $name;
	$field->label = $label;
	$field->table = $table;
	$field->column = $column;
	$field->uitype = $uitype;
	$field->typeofdata = $typeofdata;
	$field->presence = 0;
	$field->displaytype = 1;
	$block->addField($field);
	echo "  Field created: $name\n";
	return $field;
}

$phasesBlock = ecp_getOrCreateBlock($module, 'LBL_CAMPAIGN_PHASES');
$cfTable = 'vtiger_campaignscf';

// 1) Phase slot count (2–5 in UI; stored as integer, default 2 in app when empty)
ecp_addFieldIfMissing(
	$module,
	$phasesBlock,
	$cfTable,
	'cf_campaign_phase_count',
	'campaign_phase_count',
	'Active phases',
	7,
	'I~O'
);

// 2) Per-phase start / end dates
for ($i = 1; $i <= 5; $i++) {
	$startName = "phase{$i}_start_date";
	$endName = "phase{$i}_end_date";
	ecp_addFieldIfMissing(
		$module,
		$phasesBlock,
		$cfTable,
		"cf_{$startName}",
		$startName,
		"Phase {$i} Start",
		5,
		'D~O'
	);
	ecp_addFieldIfMissing(
		$module,
		$phasesBlock,
		$cfTable,
		"cf_{$endName}",
		$endName,
		"Phase {$i} End",
		5,
		'D~O'
	);
}

// 3) Comment labels: show as "Comment" in UI (fieldname still phaseN_comment)
$commentNames = array('phase1_comment', 'phase2_comment', 'phase3_comment', 'phase4_comment', 'phase5_comment');
foreach ($commentNames as $fname) {
	$adb->pquery(
		'UPDATE vtiger_field SET fieldlabel = ? WHERE tabid = ? AND fieldname = ?',
		array('Comment', $tabId, $fname)
	);
}
echo "Updated vtiger_field labels to 'Comment' for phase*_comment (if present).\n";

// 4) Campaigns ↔ Documents (same pattern as Accounts/Potentials: get_attachments — open files from Documents list)
echo "\n-- Campaigns ↔ Documents related list --\n";
$documentsModule = Vtiger_Module::getInstance('Documents');
if (!$documentsModule) {
	echo "Documents module not found — skip relation.\n";
} else {
	$chk = $adb->pquery(
		'SELECT relation_id FROM vtiger_relatedlists WHERE tabid=? AND related_tabid=? AND name=?',
		array($module->id, $documentsModule->id, 'get_attachments')
	);
	if ($chk && $adb->num_rows($chk) > 0) {
		echo "Campaigns → Documents related list already exists.\n";
	} else {
		$module->setRelatedList($documentsModule, 'Documents', array('ADD', 'SELECT'), 'get_attachments');
		echo "Added Campaigns → Documents related list (get_attachments).\n";
	}
}

echo "=== Done ===\n";
