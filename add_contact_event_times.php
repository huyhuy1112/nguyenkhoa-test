<?php
/**
 * One-time: 3 datetime fields on Contacts (Khách hàng) per BA Excel.
 *   - thoigian_dangky   → Thời gian Đăng Ký
 *   - thoigian_pcth     → Thời gian tham gia PCTH
 *   - thoigian_mqbb     → Thời gian tham gia MQBB
 *
 * Usage (from vtiger root):
 *   php add_contact_event_times.php
 */

chdir(__DIR__);

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/VtlibUtils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'vtlib/Vtiger/Block.php';
require_once 'vtlib/Vtiger/Field.php';

echo "=== Adding Contact event time fields ===\n";

try {
$module = Vtiger_Module::getInstance('Contacts');
if (!$module) {
	echo "Contacts module not found.\n";
	exit(1);
}
echo "Module OK, tabid=" . $module->id . "\n";

$block = Vtiger_Block::getInstance('LBL_CONTACT_INFORMATION', $module);
if (!$block) {
	$blocks = $module->getBlocks();
	$block = reset($blocks);
}
if (!$block) {
	echo "No block found for Contacts.\n";
	exit(1);
}
echo "Block OK\n";

$fields = array(
	array(
		'name' => 'thoigian_dangky',
		'label' => 'Thời gian Đăng Ký',
	),
	array(
		'name' => 'thoigian_pcth',
		'label' => 'Thời gian tham gia PCTH',
	),
	array(
		'name' => 'thoigian_mqbb',
		'label' => 'Thời gian tham gia MQBB',
	),
);

foreach ($fields as $spec) {
	$existing = Vtiger_Field::getInstance($spec['name'], $module);
	if ($existing) {
		echo "Field {$spec['name']} already exists — skip.\n";
		continue;
	}
	$field = new Vtiger_Field();
	$field->name = $spec['name'];
	$field->label = $spec['label'];
	$field->table = 'vtiger_contactscf';
	$field->column = $spec['name'];
	$field->columntype = 'DATETIME';
	$field->uitype = 70; // DateTime
	$field->typeofdata = 'DT~O';
	$field->displaytype = 1;
	$field->presence = 2;
	$field->masseditable = 1;
	$block->addField($field);
	echo "Added field {$spec['name']} ({$spec['label']}).\n";
}

echo "Done.\n";
} catch (Throwable $e) {
	echo "ERROR: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
	exit(1);
}
