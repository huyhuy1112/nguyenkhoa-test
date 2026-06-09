<?php
/**
 * Idempotent: ensure Quotes has a related list to ProductsServices
 * with ADD + SELECT (standard get_related_list). Safe to run multiple times.
 *
 * Usage (from CRM root): php modules/Quotes/scripts/EnsureProductsServicesRelation.php
 */
chdir(dirname(__DIR__, 3));

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

function println($msg) {
	echo $msg . PHP_EOL;
}

$quotes = Vtiger_Module::getInstance('Quotes');
$ps = Vtiger_Module::getInstance('ProductsServices');
if (!$quotes || !$ps) {
	println('SKIP: Quotes or ProductsServices module not found.');
	exit(0);
}

global $adb;
$tabId = $quotes->getId();
$relatedTabId = $ps->getId();
$targetLabel = 'Product And Service';
$targetName = 'get_related_list';
$targetActions = 'ADD,SELECT';

$res = $adb->pquery(
	'SELECT relation_id, tabid, related_tabid, name, label, actions FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? LIMIT 1',
	array($tabId, $relatedTabId)
);

if ($res && $adb->num_rows($res) > 0) {
	$relationId = (int) $adb->query_result($res, 0, 'relation_id');
	$currentName = (string) $adb->query_result($res, 0, 'name');
	$currentLabel = (string) $adb->query_result($res, 0, 'label');
	$currentActions = (string) $adb->query_result($res, 0, 'actions');

	$needUpdate = false;
	if (trim($currentName) !== $targetName) {
		$needUpdate = true;
	}
	$actionsUpper = strtoupper(str_replace(' ', '', $currentActions));
	if (strpos($actionsUpper, 'SELECT') === false) {
		$needUpdate = true;
	}
	if (trim($currentLabel) !== $targetLabel) {
		$needUpdate = true;
	}

	if ($needUpdate) {
		$adb->pquery(
			'UPDATE vtiger_relatedlists SET name = ?, actions = ?, label = ? WHERE relation_id = ?',
			array($targetName, $targetActions, $targetLabel, $relationId)
		);
		println('OK: Updated vtiger_relatedlists relation_id=' . $relationId . ' for Quotes → ProductsServices.');
	} else {
		println('OK: vtiger_relatedlists relation already matches requirements (relation_id=' . $relationId . ').');
	}
} else {
	try {
		$quotes->setRelatedList($ps, $targetLabel, array('ADD', 'SELECT'), $targetName);
		println('OK: Added related list Quotes → ProductsServices.');
	} catch (Exception $e) {
		println('ERROR: ' . $e->getMessage());
		exit(1);
	}
}

exit(0);
