<?php
/*+***********************************************************************************
 * Register Potentials HEADERSCRIPT for OrderCategoryFilter.js
 * Safe to run multiple times.
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // modules/Potentials/scripts -> vtiger root

require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$module = Vtiger_Module::getInstance('Potentials');
if (!$module) {
	echo "ERROR: Module Potentials not found.\n";
	exit(1);
}

$linkType  = 'HEADERSCRIPT';
$linkLabel = 'OrderCategoryFilter';
$linkUrl   = 'layouts/v7/modules/Potentials/resources/OrderCategoryFilter.js';

// Check existing
$links  = $module->getLinksForExport();
$exists = false;
if (is_array($links)) {
	foreach ($links as $l) {
		if ($l->linktype === $linkType && $l->linkurl === $linkUrl) {
			$exists = true;
			break;
		}
	}
}

if ($exists) {
	echo "Link already exists: $linkUrl\n";
	exit(0);
}

$module->addLink($linkType, $linkLabel, $linkUrl);
echo "Registered $linkType link: $linkUrl\n";

