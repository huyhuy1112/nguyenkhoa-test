<?php
/*+***********************************************************************************
 * Register ProductsServices Edit.js as HEADERSCRIPT for Edit view.
 * Run from vtiger root: php -f modules/ProductsServices/scripts/RegisterEditJs.php
 *************************************************************************************/
chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$module = Vtiger_Module::getInstance('ProductsServices');
if (!$module) {
	echo "ERROR: Module ProductsServices not found.\n";
	exit(1);
}

$linkUrl = 'layouts/v7/modules/ProductsServices/resources/Edit.js';
$links = $module->getLinksForExport();
$exists = false;
if (is_array($links)) {
	foreach ($links as $l) {
		if (isset($l->linktype) && $l->linktype === 'HEADERSCRIPT' && isset($l->linkurl) && $l->linkurl === $linkUrl) {
			$exists = true;
			break;
		}
	}
}
if ($exists) {
	echo "Edit.js already registered.\n";
	exit(0);
}
$module->addLink('HEADERSCRIPT', 'ProductsServicesEdit', $linkUrl);
echo "Registered HEADERSCRIPT: $linkUrl\n";
