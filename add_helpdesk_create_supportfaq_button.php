<?php
/**
 * Add "Create FAQ" button to HelpDesk DetailView (DETAILVIEWBASIC).
 *
 * Button redirects to SupportFAQ Create view via HelpDesk action CreateFAQ,
 * which passes prefilled fields as GET params.
 *
 * Run:
 *   docker exec vtiger_web php /var/www/html/add_helpdesk_create_supportfaq_button.php
 */

chdir(__DIR__);

require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

echo "=== Add HelpDesk Create FAQ button ===\n";

$module = Vtiger_Module::getInstance('HelpDesk');
if (!$module) {
	echo "ERROR: HelpDesk module not found.\n";
	exit(1);
}

$label = 'Create FAQ';
$url = 'index.php?module=HelpDesk&action=CreateFAQ&record=$RECORD$';

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

