<?php
/**
 * One-shot: put productsservicesname into every ProductsServices Custom View column list (first data column).
 * Usage (from CRM root): php modules/ProductsServices/scripts/EnsureNameInCustomViews.php
 */
chdir(dirname(__FILE__) . '/../../..');
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';

$adb = PearDatabase::getInstance();
$tabRes = $adb->pquery('SELECT tabid FROM vtiger_tab WHERE name=?', array('ProductsServices'));
if (!$adb->num_rows($tabRes)) {
	fwrite(STDERR, "ProductsServices module not found\n");
	exit(1);
}
$tabId = (int) $adb->query_result($tabRes, 0, 'tabid');

$fieldRes = $adb->pquery(
	'SELECT fieldid, columnname, tablename, fieldname FROM vtiger_field WHERE tabid=? AND fieldname=?',
	array($tabId, 'productsservicesname')
);
if (!$adb->num_rows($fieldRes)) {
	fwrite(STDERR, "Field productsservicesname not found\n");
	exit(1);
}
$columnname = $adb->query_result($fieldRes, 0, 'columnname');
$tablename = $adb->query_result($fieldRes, 0, 'tablename');
$fieldname = $adb->query_result($fieldRes, 0, 'fieldname');
// Cv column format: tablename:columnname:fieldname:Module_Label:type
$cvColValue = $tablename . ':' . $columnname . ':' . $fieldname . ':ProductsServices_Name:V';

$cvRes = $adb->pquery('SELECT cvid, viewname FROM vtiger_customview WHERE entitytype=?', array('ProductsServices'));
$count = $adb->num_rows($cvRes);
echo "Found {$count} custom views for ProductsServices\n";

for ($i = 0; $i < $count; $i++) {
	$cvid = (int) $adb->query_result($cvRes, $i, 'cvid');
	$viewname = $adb->query_result($cvRes, $i, 'viewname');

	$existing = $adb->pquery(
		'SELECT columnindex, columnname FROM vtiger_cvcolumnlist WHERE cvid=? ORDER BY columnindex',
		array($cvid)
	);
	$hasName = false;
	$maxIdx = -1;
	$rows = array();
	$n = $adb->num_rows($existing);
	for ($j = 0; $j < $n; $j++) {
		$col = $adb->query_result($existing, $j, 'columnname');
		$idx = (int) $adb->query_result($existing, $j, 'columnindex');
		$rows[] = array('idx' => $idx, 'col' => $col);
		if ($idx > $maxIdx) {
			$maxIdx = $idx;
		}
		if (strpos($col, ':productsservicesname:') !== false || strpos($col, 'productsservicesname') !== false) {
			$hasName = true;
		}
	}

	if ($hasName) {
		echo "  CV {$cvid} ({$viewname}): already has name\n";
		continue;
	}

	// Shift indexes up, insert name at 0
	$adb->pquery('UPDATE vtiger_cvcolumnlist SET columnindex = columnindex + 1 WHERE cvid=?', array($cvid));
	$adb->pquery(
		'INSERT INTO vtiger_cvcolumnlist (cvid, columnindex, columnname) VALUES (?,?,?)',
		array($cvid, 0, $cvColValue)
	);
	echo "  CV {$cvid} ({$viewname}): INSERTED {$cvColValue} at index 0\n";
}

echo "Done.\n";
