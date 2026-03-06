<?php
/**
 * One-time script to add "support_level" field to Contacts module.
 *
 * Usage (from vtiger root):
 *   php add_support_level_to_contacts.php
 */

chdir(__DIR__);

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/VtlibUtils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'vtlib/Vtiger/Block.php';
require_once 'vtlib/Vtiger/Field.php';

echo "=== Adding support_level to Contacts ===\n";

$module = Vtiger_Module::getInstance('Contacts');
if (!$module) {
	echo "Contacts module not found.\n";
	exit;
}

// Prefer main Contact Information block
$block = Vtiger_Block::getInstance('LBL_CONTACT_INFORMATION', $module);
if (!$block) {
	$blocks = $module->getBlocks();
	$block  = reset($blocks);
}

if (!$block) {
	echo "No block found for Contacts.\n";
	exit;
}

$existingField = Vtiger_Field::getInstance('support_level', $module);
if ($existingField) {
	echo "Field support_level already exists on Contacts. Nothing to do.\n";
	exit;
}

$field = new Vtiger_Field();
$field->name        = 'support_level';
$field->label       = 'Support Level';
$field->table       = 'vtiger_contactdetails';
$field->column      = 'support_level';
$field->columntype  = 'VARCHAR(10)';
$field->uitype      = 15; // picklist
$field->typeofdata  = 'V~O';
$field->displaytype = 1;
$field->presence    = 2;
$field->masseditable = 1;
$field->defaultvalue = '2'; // Level 2 default

$block->addField($field);
$field->setPicklistValues(['1', '2', '3']);

echo "Field support_level added to Contacts with values [1,2,3], default = 2.\n";

