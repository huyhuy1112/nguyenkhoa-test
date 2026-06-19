<?php
/*+***********************************************************************************
 * Phase 2 extensions — SalesOrder.lead_id reference field.
 *
 * Run from vtiger root:
 *   php -f modules/Leads/scripts/InstallLeadsPhase2.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'vendor/autoload.php';
require_once 'config.php';
include_once 'vtlib/Vtiger/Cron.php';
vimport('includes.runtime.EntryPoint');
require_once 'vtlib/Vtiger/Module.php';
require_once 'modules/Leads/models/ModernService.php';

echo "=== Install Leads Phase 2 extensions ===\n";

Leads_ModernService::installSchema(PearDatabase::getInstance());
echo "Profile schema ensured.\n";

$soModule = Vtiger_Module::getInstance('SalesOrder');
if (!$soModule) {
	echo "ERROR: SalesOrder module not found.\n";
	exit(1);
}

$block = Vtiger_Block::getInstance('LBL_SO_INFORMATION', $soModule);
if (!$block) {
	$block = Vtiger_Block::getInstance('LBL_SALESORDER_INFORMATION', $soModule);
}
if (!$block) {
	$blocks = Vtiger_Block::getAllForModule($soModule);
	$block = !empty($blocks) ? $blocks[0] : null;
}
if (!$block) {
	echo "ERROR: No block found for SalesOrder.\n";
	exit(1);
}

$field = Vtiger_Field::getInstance('lead_id', $soModule);
if ($field) {
	echo "Field lead_id already exists on SalesOrder.\n";
} else {
	$field = new Vtiger_Field();
	$field->name = 'lead_id';
	$field->label = 'Lead';
	$field->table = 'vtiger_salesordercf';
	$field->column = 'lead_id';
	$field->uitype = 10;
	$field->typeofdata = 'V~O';
	$field->displaytype = 1;
	$field->masseditable = 1;
	$block->addField($field);
	$field->setRelatedModules(array('Leads'));
	echo "Field lead_id created on SalesOrder.\n";
}

$adb = PearDatabase::getInstance();
$colRes = $adb->pquery("SHOW COLUMNS FROM vtiger_salesordercf LIKE 'lead_id'", array());
if (!$colRes || $adb->num_rows($colRes) < 1) {
	$adb->pquery("ALTER TABLE vtiger_salesordercf ADD COLUMN lead_id INT(19) DEFAULT NULL", array());
	echo "Column vtiger_salesordercf.lead_id added.\n";
}

echo "=== Done ===\n";
