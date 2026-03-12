<?php
/**
 * Add "Increase Occurrence" button to SupportFAQ DetailView using vtiger_links.
 *
 * Run:
 *   docker exec vtiger_web php /var/www/html/add_supportfaq_increase_occurrence_button.php
 */

chdir(__DIR__);

require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

echo "=== Add SupportFAQ Increase Occurrence button ===\n";

$module = Vtiger_Module::getInstance('SupportFAQ');
if (!$module) {
	echo "ERROR: SupportFAQ module not found.\n";
	exit(1);
}

$label = 'Increase Occurrence';
$url = 'index.php?module=SupportFAQ&action=IncreaseOccurrence&record=$RECORD$';

global $adb;
$res = $adb->pquery(
	'SELECT 1 FROM vtiger_links WHERE tabid=? AND linktype=? AND linklabel=? LIMIT 1',
	array($module->id, 'DETAILVIEWBASIC', $label)
);
if ($res && $adb->num_rows($res) > 0) {
	echo "Button already exists. Nothing to do.\n";
	exit(0);
}

$module->addLink('DETAILVIEWBASIC', $label, $url);

@array_map('unlink', glob(__DIR__ . '/cache/*') ?: array());
@array_map('unlink', glob(__DIR__ . '/test/templates_c/v7/*') ?: array());

echo "=== Done ===\n";

