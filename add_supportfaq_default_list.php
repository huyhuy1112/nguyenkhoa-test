<?php
/**
 * Create default List View (Custom View) for SupportFAQ.
 *
 * Run:
 *   docker exec vtiger_web php /var/www/html/add_supportfaq_default_list.php
 */
chdir(__DIR__);

require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'vtlib/Vtiger/Filter.php';
require_once 'vtlib/Vtiger/Field.php';

global $adb;

echo "=== Creating default list for SupportFAQ ===\n";

$module = Vtiger_Module::getInstance('SupportFAQ');
if (!$module) {
	echo "ERROR: SupportFAQ module not found.\n";
	exit(1);
}

// If a filter already exists, do nothing.
$res = $adb->pquery("SELECT cvid FROM vtiger_customview WHERE entitytype=? LIMIT 1", array('SupportFAQ'));
if ($res && $adb->num_rows($res) > 0) {
	$cvid = (int)$adb->query_result($res, 0, 'cvid');
	echo "SupportFAQ custom view already exists (cvid=$cvid). Nothing to do.\n";
	exit(0);
}

$filter = new Vtiger_Filter();
$filter->name = 'All';
$filter->isdefault = true;
$filter->inmetrics = false;
$filter->status = 0; // Default
$module->addFilter($filter);

echo "Created filter cvid={$filter->id}\n";

$fieldsByName = array();
$fields = Vtiger_Field::getAllForModule($module);
foreach ($fields as $f) {
	$fieldsByName[$f->name] = $f;
}

// Column order
$colNames = array('question', 'occurrence_count', 'related_ticket_id', 'assigned_user_id', 'createdtime');
$idx = 0;
foreach ($colNames as $name) {
	if (!isset($fieldsByName[$name])) {
		echo "WARN: missing field '$name' in vtiger_field, skipping column.\n";
		continue;
	}
	$filter->addField($fieldsByName[$name], $idx);
	$idx++;
}

// Clear caches commonly affecting list view
@array_map('unlink', glob(__DIR__ . '/user_privileges/menu_*.php') ?: array());
@array_map('unlink', glob(__DIR__ . '/cache/*') ?: array());

echo "=== Done ===\n";

