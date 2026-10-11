<?php
/**
 * Switch entity numbering: Contacts → KH, Accounts → NQ.
 * Usage: php scripts/ResetSalesEntityCodes.php
 */
chdir(dirname(__DIR__));

require_once 'vendor/autoload.php';
require_once 'config.php';
if (isset($dbconfig) && is_array($dbconfig)) {
	$host = isset($dbconfig['db_server']) ? (string) $dbconfig['db_server'] : '';
	if ($host === 'db' || $host === 'mysql') {
		$dbconfig['db_server'] = '127.0.0.1';
		$dbconfig['db_port'] = ':3307';
		$dbconfig['db_hostname'] = '127.0.0.1:3307';
	}
}
include_once 'vtlib/Vtiger/Cron.php';
vimport('includes.runtime.EntryPoint');
require_once 'include/utils/MkEntityNumbering.php';

function println($msg) {
	echo $msg . PHP_EOL;
}

global $adb;

println('Switching sales entity codes (Contacts→KH, Accounts→Tuibao)...');

foreach (array('Contacts', 'Accounts') as $module) {
	$ok = MkEntityNumbering::ensureModuleSequence($module);
	$cfg = MkEntityNumbering::$PADDED_MODULES[$module];
	$preview = MkEntityNumbering::previewNextNumber($module);
	println(($ok ? 'OK' : 'SKIP') . "  {$module}: prefix={$cfg['prefix']} next={$preview}");
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
println('Done. Existing LH/old-KH/NQ records keep old codes; new Contacts=KH…, new Accounts=Tuibao…');
