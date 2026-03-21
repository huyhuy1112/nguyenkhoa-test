<?php
/*+***********************************************************************************
 * Add Opportunity link field to ProjectTask.
 *
 * Goal:
 * - Add field: opportunity_id (label: Opportunity)
 * - Type: uitype 10 reference to Potentials
 * - Keep existing "Related to" (projectid) unchanged
 * - Safe to run multiple times (idempotent)
 *
 * Run:
 *   php -f modules/ProjectTask/scripts/AddOpportunityField.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$moduleName = 'ProjectTask';
$referenceModule = 'Potentials'; // CRM module name used for Opportunity

$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

$block = Vtiger_Block::getInstance('LBL_PROJECT_TASK_INFORMATION', $module);
if (!$block) {
	// Fallback: use first available block
	$blocks = Vtiger_Block::getAllForModule($module);
	if (!empty($blocks)) {
		$block = $blocks[0];
	}
}
if (!$block) {
	echo "ERROR: Cannot resolve a target block for $moduleName.\n";
	exit(1);
}

$field = Vtiger_Field::getInstance('opportunity_id', $module);
if (!$field) {
	$field = new Vtiger_Field();
	$field->name = 'opportunity_id';
	$field->label = 'Opportunity';
	$field->uitype = 10;
	$field->column = 'opportunity_id';
	$field->columntype = 'INT(11)';
	$field->typeofdata = 'V~O'; // optional -> can be cleared
	$block->addField($field);
	echo "Created field: opportunity_id\n";
} else {
	echo "Field already exists: opportunity_id\n";
}

// Ensure reference relation includes Potentials
if ($field) {
	$field->setRelatedModules(array($referenceModule));
	echo "Reference module set: $referenceModule\n";
}

// Place near existing Related to field (projectid) in the same block.
try {
	$db = PearDatabase::getInstance();
	$relatedToField = Vtiger_Field::getInstance('projectid', $module);
	if ($relatedToField) {
		$relatedFieldId = (int)$relatedToField->id;
		$relatedMetaRes = $db->pquery('SELECT block, sequence FROM vtiger_field WHERE fieldid=?', array($relatedFieldId));
		$blockId = (int)$db->query_result($relatedMetaRes, 0, 'block');
		$projectSeq = (int)$relatedToField->sequence;
		$targetSeq = $projectSeq + 1;
		$fieldId = (int)$field->id;

		$currentRes = $db->pquery('SELECT sequence, block FROM vtiger_field WHERE fieldid=?', array($fieldId));
		$currentSeq = (int)$db->query_result($currentRes, 0, 'sequence');
		$currentBlock = (int)$db->query_result($currentRes, 0, 'block');

		if ($currentBlock !== $blockId || $currentSeq !== $targetSeq) {
			// Shift down fields in target block to make room.
			$db->pquery(
				'UPDATE vtiger_field SET sequence = sequence + 1 WHERE tabid=? AND block=? AND sequence>=? AND fieldid<>?',
				array((int)$module->id, $blockId, $targetSeq, $fieldId)
			);
			$db->pquery('UPDATE vtiger_field SET block=?, sequence=? WHERE fieldid=?', array($blockId, $targetSeq, $fieldId));
		}
		echo "Sequence adjusted near projectid (target: $targetSeq)\n";
	}
} catch (Exception $e) {
	echo "Note: sequence update skipped (" . $e->getMessage() . ")\n";
}

echo "Done.\n";

