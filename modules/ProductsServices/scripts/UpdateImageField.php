<?php
/*+***********************************************************************************
 * Update ProductsServices used_projects field to image upload (uitype 69).
 * Run from vtiger root:
 *   php modules/ProductsServices/scripts/UpdateImageField.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // modules/ProductsServices/scripts -> vtiger root

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
include_once 'vtlib/Vtiger/Module.php';

$module = Vtiger_Module::getInstance('ProductsServices');

if (!$module) {
	echo "Module ProductsServices not found\n";
	exit(1);
}

$field = Vtiger_Field::getInstance('used_projects', $module);

if ($field) {
	$field->uitype      = 69;    // Image
	$field->typeofdata  = 'V~O';
	$field->displaytype = 1;
	$field->presence    = 0;
	$field->save();

	// Đảm bảo metadata trong vtiger_field cũng được cập nhật (tránh cache cũ)
	$tabid = $module->id;
	$adb   = PearDatabase::getInstance();
	$adb->pquery(
		'UPDATE vtiger_field SET uitype = ?, typeofdata = ?, presence = ?, displaytype = ? WHERE fieldname = ? AND tabid = ?',
		array(69, 'V~O', 0, 1, 'used_projects', $tabid)
	);

	echo "Image upload field ready (uitype=69) for used_projects\n";
} else {
	echo "Field used_projects not found\n";
}

