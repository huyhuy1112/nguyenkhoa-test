<?php
/*+***********************************************************************************
 * One-shot: set ProductsServices Custom View columns to v2 list layout.
 * Run: php modules/ProductsServices/scripts/UpdateListViewColumnsV2.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // scripts -> module -> modules -> vtiger root

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'includes/Loader.php';
vimport('includes.runtime.EntryPoint');

$moduleName = 'ProductsServices';
$tabId = getTabid($moduleName);
if (!$tabId) {
	fwrite(STDERR, "ProductsServices tab not found\n");
	exit(1);
}

$columns = array(
	array('productsservicesname', 'ProductsServices_Name', 'V'),
	array('item_type', 'ProductsServices_Type', 'V'),
	array('price', 'ProductsServices_Price', 'N'),
	array('supplier', 'ProductsServices_Supplier', 'V'),
	array('unit', 'ProductsServices_Unit', 'V'),
);

$db = PearDatabase::getInstance();
$result = $db->pquery(
	'SELECT cvid, viewname FROM vtiger_customview WHERE entitytype = ?',
	array($moduleName)
);
if (!$result) {
	fwrite(STDERR, "No custom views\n");
	exit(1);
}

while ($row = $db->fetchByAssoc($result)) {
	$cvId = (int) $row['cvid'];
	$db->pquery('DELETE FROM vtiger_cvcolumnlist WHERE cvid = ?', array($cvId));
	$idx = 0;
	foreach ($columns as $col) {
		list($field, $label, $type) = $col;
		$columnName = 'vtiger_productsservices:' . $field . ':' . $field . ':' . $label . ':' . $type;
		$db->pquery(
			'INSERT INTO vtiger_cvcolumnlist (cvid, columnindex, columnname) VALUES (?, ?, ?)',
			array($cvId, $idx, $columnName)
		);
		$idx++;
	}
	echo "Updated CV {$row['viewname']} (id={$cvId})\n";
}

echo "Done.\n";
