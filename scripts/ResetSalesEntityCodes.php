<?php
/**
 * Reset entity numbering for Order (Potentials), Contact, Organization (Accounts).
 * Formats: KH00001, LH00001, TC00001 — next new records use these prefixes.
 *
 * Usage (from CRM root): php scripts/ResetSalesEntityCodes.php
 */
chdir(dirname(__DIR__));

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/MkEntityNumbering.php';

function println($msg) {
	echo $msg . PHP_EOL;
}

global $adb;

println('Resetting sales entity codes (Potentials, Contacts, Accounts)...');

$results = MkEntityNumbering::resetAll();

foreach ($results as $module => $ok) {
	$cfg = MkEntityNumbering::$PADDED_MODULES[$module];
	$sample = MkEntityNumbering::formatNumber($cfg['prefix'], $cfg['start'], $cfg['width']);
	println(($ok ? 'OK' : 'SKIP') . "  {$module}: next code = {$sample}");
}

$res = $adb->pquery(
	'SELECT semodule, prefix, start_id, cur_id, active FROM vtiger_modentity_num WHERE semodule IN (?,?,?) ORDER BY semodule, active DESC',
	array('Potentials', 'Contacts', 'Accounts')
);

println('');
println('Current vtiger_modentity_num rows:');
while ($row = $adb->fetchByAssoc($res)) {
	println(sprintf(
		'  %s | prefix=%s | start=%s | cur=%s | active=%s',
		$row['semodule'],
		$row['prefix'],
		$row['start_id'],
		$row['cur_id'],
		$row['active']
	));
}

println('');
println('Done. Existing records keep old codes; new records use the formats above.');
