<?php
/**
 * Idempotent: ensure Opportunities (Potentials) has a related list to ProductsServices
 * with ADD + SELECT (standard get_related_list). Safe to run multiple times.
 *
 * Usage (from CRM root): php modules/Potentials/scripts/EnsureProductsServicesRelation.php
 */
chdir(dirname(__DIR__, 3));

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

function println($msg) {
	echo $msg . PHP_EOL;
}

$potentials = Vtiger_Module::getInstance('Potentials');
$ps = Vtiger_Module::getInstance('ProductsServices');
if (!$potentials || !$ps) {
	println('SKIP: Potentials or ProductsServices module not found.');
	exit(0);
}

global $adb;
$tabId = $potentials->getId();
$relatedTabId = $ps->getId();
$targetRelationId = 177;
$targetLabel = 'Product And Service';
$targetName = 'get_related_list';
$targetActions = 'ADD,SELECT';

// Prefer fixing the known relation_id if present; otherwise fall back to module-tab match.
$res = $adb->pquery(
	'SELECT relation_id, tabid, related_tabid, name, label, actions FROM vtiger_relatedlists WHERE relation_id = ? LIMIT 1',
	array($targetRelationId)
);
if (!$res || $adb->num_rows($res) === 0) {
	$res = $adb->pquery(
		'SELECT relation_id, tabid, related_tabid, name, label, actions FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? LIMIT 1',
		array($tabId, $relatedTabId)
	);
}

if ($res && $adb->num_rows($res) > 0) {
	$relationId = (int) $adb->query_result($res, 0, 'relation_id');
	$currentName = (string) $adb->query_result($res, 0, 'name');
	$currentLabel = (string) $adb->query_result($res, 0, 'label');
	$currentActions = (string) $adb->query_result($res, 0, 'actions');
	$currentTabId = (int) $adb->query_result($res, 0, 'tabid');
	$currentRelatedTabId = (int) $adb->query_result($res, 0, 'related_tabid');

	$needUpdate = false;
	if ($currentTabId !== (int) $tabId || $currentRelatedTabId !== (int) $relatedTabId) {
		$needUpdate = true;
	}
	if (trim($currentName) !== $targetName) {
		$needUpdate = true;
	}
	// vtiger stores actions as comma list e.g. "ADD,SELECT"
	$actionsUpper = strtoupper(str_replace(' ', '', $currentActions));
	if (strpos($actionsUpper, 'SELECT') === false) {
		$needUpdate = true;
	}
	if (trim($currentLabel) !== $targetLabel) {
		$needUpdate = true;
	}

	if ($needUpdate) {
		try {
			$adb->pquery(
				'UPDATE vtiger_relatedlists SET tabid = ?, related_tabid = ?, name = ?, actions = ?, label = ? WHERE relation_id = ?',
				array($tabId, $relatedTabId, $targetName, $targetActions, $targetLabel, $relationId)
			);
			println('OK: Updated vtiger_relatedlists relation_id=' . $relationId . ' for Potentials → ProductsServices.');
		} catch (Exception $e) {
			println('ERROR: Failed updating relation_id=' . $relationId . ' :: ' . $e->getMessage());
			exit(1);
		}
	} else {
		println('OK: vtiger_relatedlists relation already matches requirements (relation_id=' . $relationId . ').');
	}
} else {
	try {
		$potentials->setRelatedList($ps, $targetLabel, array('ADD', 'SELECT'), $targetName);
		println('OK: Added related list Potentials → ProductsServices.');
	} catch (Exception $e) {
		println('ERROR: ' . $e->getMessage());
		exit(1);
	}
}

// Always print the final row (as requested).
$finalRes = $adb->pquery(
	'SELECT relation_id, tabid, related_tabid, name, label, actions, sequence FROM vtiger_relatedlists WHERE relation_id = ? LIMIT 1',
	array($targetRelationId)
);
if ($finalRes && $adb->num_rows($finalRes) > 0) {
	$finalRow = $adb->fetch_array($finalRes);
	println('FINAL vtiger_relatedlists row (relation_id=177): ' . json_encode($finalRow));
} else {
	$finalRes = $adb->pquery(
		'SELECT relation_id, tabid, related_tabid, name, label, actions, sequence FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? LIMIT 1',
		array($tabId, $relatedTabId)
	);
	if ($finalRes && $adb->num_rows($finalRes) > 0) {
		$finalRow = $adb->fetch_array($finalRes);
		println('FINAL vtiger_relatedlists row (by tabid match): ' . json_encode($finalRow));
	} else {
		println('WARN: Unable to find final vtiger_relatedlists row after operation.');
	}
}

exit(0);
