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
$res = $adb->pquery(
	'SELECT relation_id FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? LIMIT 1',
	array($tabId, $relatedTabId)
);
if ($res && $adb->num_rows($res) > 0) {
	println('OK: Relation Potentials → ProductsServices already exists.');
	exit(0);
}

$label = 'Products & Services';
try {
	$potentials->setRelatedList($ps, $label, array('ADD', 'SELECT'), 'get_related_list');
	println('OK: Added related list Potentials → ProductsServices.');
} catch (Exception $e) {
	println('ERROR: ' . $e->getMessage());
	exit(1);
}

exit(0);
