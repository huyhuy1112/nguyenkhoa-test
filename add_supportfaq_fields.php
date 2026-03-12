<?php
/**
 * Add missing block and fields for SupportFAQ module (metadata repair).
 * Run once: docker exec vtiger_web php /var/www/html/add_supportfaq_fields.php
 * (Or from project root if DB is reachable: php add_supportfaq_fields.php)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

chdir(__DIR__);
// Load DB config (required for $adb)
if (file_exists('config.inc.php')) {
	require_once 'config.inc.php';
}
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'vtlib/Vtiger/Block.php';
require_once 'vtlib/Vtiger/Field.php';

global $adb;

echo "=== Adding SupportFAQ block and fields ===\n";

try {
	$module = Vtiger_Module::getInstance('SupportFAQ');
} catch (Throwable $e) {
	echo "Error getInstance: " . $e->getMessage() . "\n";
	exit(1);
}
if (!$module) {
	echo "ERROR: SupportFAQ module not found.\n";
	exit(1);
}

$tabid = $module->getId();
echo "Tabid: $tabid\n";

$blocks = Vtiger_Block::getAllForModule($module);
echo "Blocks count: " . count($blocks) . "\n";

if (empty($blocks)) {
	echo "Creating new block...\n";
	$block = new Vtiger_Block();
	$block->label = 'LBL_SUPPORTFAQ_INFORMATION';
	$module->addBlock($block);
	$blocks = Vtiger_Block::getAllForModule($module);
}
$block = $blocks[0];
$blockId = $block->id;
echo "Using block id: $blockId\n";

// Fix existing description field if it points to wrong block (e.g. 126 = Faq)
$res = $adb->pquery("SELECT fieldid, block FROM vtiger_field WHERE tabid=? AND fieldname=?", array($tabid, 'description'));
if ($adb->num_rows($res) && (int)$adb->query_result($res, 0, 'block') !== (int)$blockId) {
	$adb->pquery("UPDATE vtiger_field SET block=? WHERE tabid=? AND fieldname=?", array($blockId, $tabid, 'description'));
	echo "Updated description field to block $blockId\n";
}

// Existing field names for this module
$res = $adb->pquery("SELECT fieldname FROM vtiger_field WHERE tabid=?", array($tabid));
$existing = array();
for ($i = 0; $i < $adb->num_rows($res); $i++) {
	$existing[] = $adb->query_result($res, $i, 'fieldname');
}

function addFieldIfMissing($module, $block, $existing, $adb, $def) {
	$name = $def['name'];
	if (in_array($name, $existing)) {
		return false;
	}
	$field = new Vtiger_Field();
	$field->name       = $name;
	$field->label      = $def['label'];
	$field->uitype     = $def['uitype'];
	$field->column     = $def['column'];
	$field->table      = $def['table'];
	$field->typeofdata = $def['typeofdata'];
	$field->readonly   = isset($def['readonly']) ? $def['readonly'] : 0;
	$field->presence   = 2; // 1=mandatory, 2=optional
	if (isset($def['displaytype'])) {
		$field->displaytype = $def['displaytype'];
	}
	if (isset($def['quickcreate'])) {
		$field->quickcreate = $def['quickcreate'];
	}
	$block->addField($field);
	echo "  Added field: $name\n";
	return $field;
}

$fieldsToAdd = array(
	array(
		'name' => 'question',
		'label' => 'Question',
		'uitype' => 2,
		'column' => 'question',
		'table' => 'vtiger_supportfaq',
		'typeofdata' => 'V~M',
	),
	array(
		'name' => 'solution',
		'label' => 'Solution',
		'uitype' => 19,
		'column' => 'solution',
		'table' => 'vtiger_supportfaq',
		'typeofdata' => 'V~O',
	),
	array(
		'name' => 'occurrence_count',
		'label' => 'Occurrence Count',
		'uitype' => 7,
		'column' => 'occurrence_count',
		'table' => 'vtiger_supportfaq',
		'typeofdata' => 'I~O',
	),
	array(
		'name' => 'related_ticket_id',
		'label' => 'Related Ticket',
		'uitype' => 7,
		'column' => 'related_ticket_id',
		'table' => 'vtiger_supportfaq',
		'typeofdata' => 'I~O',
	),
	array(
		'name' => 'assigned_user_id',
		'label' => 'Assigned To',
		'uitype' => 53,
		'column' => 'smownerid',
		'table' => 'vtiger_crmentity',
		'typeofdata' => 'V~M',
	),
	array(
		'name' => 'createdtime',
		'label' => 'Created Time',
		'uitype' => 70,
		'column' => 'createdtime',
		'table' => 'vtiger_crmentity',
		'typeofdata' => 'DT~O',
		'readonly' => 1,
		'displaytype' => 2,
		'quickcreate' => 3,
	),
	array(
		'name' => 'modifiedtime',
		'label' => 'Modified Time',
		'uitype' => 70,
		'column' => 'modifiedtime',
		'table' => 'vtiger_crmentity',
		'typeofdata' => 'DT~O',
		'readonly' => 1,
		'displaytype' => 2,
		'quickcreate' => 3,
	),
);

$questionField = null;
foreach ($fieldsToAdd as $def) {
	$f = addFieldIfMissing($module, $block, $existing, $adb, $def);
	if ($f && $def['name'] === 'question') {
		$questionField = $f;
	}
	if ($f) {
		$existing[] = $def['name'];
	}
}

if ($questionField) {
	$module->setEntityIdentifier($questionField);
	echo "Set entity identifier to question\n";
}

// Ensure entityname row for SupportFAQ (in case setEntityIdentifier was never run)
$chk = $adb->pquery("SELECT 1 FROM vtiger_entityname WHERE tabid=?", array($tabid));
if (!$adb->num_rows($chk)) {
	$adb->pquery(
		"INSERT INTO vtiger_entityname(tabid, modulename, tablename, fieldname, entityidfield, entityidcolumn) VALUES(?,?,?,?,?,?)",
		array($tabid, 'SupportFAQ', 'vtiger_supportfaq', 'question', 'supportfaqid', 'supportfaqid')
	);
	echo "Inserted vtiger_entityname for SupportFAQ\n";
}

// Clear caches
if (class_exists('Vtiger_Cache')) {
	Vtiger_Cache::flushModuleandBlockFieldsCache($module, $blockId);
}
$glob = glob(__DIR__ . '/user_privileges/menu_*.php');
if ($glob) {
	foreach ($glob as $f) {
		@unlink($f);
	}
	echo "Cleared menu cache files\n";
}

echo "=== Done ===\n";
