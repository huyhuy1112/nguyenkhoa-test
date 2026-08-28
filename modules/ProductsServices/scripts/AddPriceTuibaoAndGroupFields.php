<?php
/*+***********************************************************************************
 * Ensure price_tuibao + product_group (Nhóm as picklist) on ProductsServices.
 * Safe to run multiple times.
 *
 * From host (Docker MySQL mapped to 3307):
 *   MK_DB_HOST=127.0.0.1 MK_DB_PORT=3307 php -f modules/ProductsServices/scripts/AddPriceTuibaoAndGroupFields.php
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
if (getenv('MK_DB_HOST')) {
	global $dbconfig;
	$dbconfig['db_server'] = getenv('MK_DB_HOST');
	$port = getenv('MK_DB_PORT') ? getenv('MK_DB_PORT') : '3306';
	$dbconfig['db_port'] = (strpos($port, ':') === 0) ? $port : (':' . $port);
	$dbconfig['db_hostname'] = $dbconfig['db_server'] . $dbconfig['db_port'];
	echo "DB override: {$dbconfig['db_hostname']}\n";
}
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'modules/ProductsServices/helpers/NguyenKhoaExcelCatalog.php';

$moduleName = 'ProductsServices';
$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	echo "ERROR: Module $moduleName not found.\n";
	exit(1);
}

function ps_tb_getOrCreateBlock(Vtiger_Module $module, $label) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) {
		return $block;
	}
	$block = new Vtiger_Block();
	$block->label = $label;
	$module->addBlock($block);
	echo "  block created: $label\n";
	return $block;
}

function ps_tb_collectGroupNames() {
	$groups = array();
	$file = '';
	foreach (array(
		'Danh muc SP Nguyen Khoa.xlsx',
		dirname(__DIR__, 3) . '/Danh muc SP Nguyen Khoa.xlsx',
	) as $c) {
		if (is_file($c)) {
			$file = $c;
			break;
		}
	}
	if ($file !== '') {
		$parsed = ProductsServices_NguyenKhoaExcelCatalog_Helper::parse($file);
		foreach ($parsed['products'] as $p) {
			$g = isset($p['product_group']) ? trim((string) $p['product_group']) : '';
			if ($g !== '') {
				$groups[$g] = true;
			}
		}
	}
	// Stable defaults if excel missing
	if (!$groups) {
		foreach (array(
			'Thạch hạt (topping)',
			'Nhóm bột trà sữa',
			'CCDC',
			'MAY',
		) as $g) {
			$groups[$g] = true;
		}
	}
	return array_keys($groups);
}

echo "=== Add/update price_tuibao + product_group (picklist) ===\n";

$infoBlock = ps_tb_getOrCreateBlock($module, 'LBL_PRODUCT_INFORMATION');
$priceBlock = ps_tb_getOrCreateBlock($module, 'LBL_INVOICE_PRICE_LIST');

// price_tuibao currency
$fTb = Vtiger_Field::getInstance('price_tuibao', $module);
if (!$fTb) {
	$fTb = new Vtiger_Field();
	$fTb->name = 'price_tuibao';
	$fTb->label = 'Giá Tuibao';
	$fTb->uitype = 71;
	$fTb->column = 'price_tuibao';
	$fTb->columntype = 'DECIMAL(25,8)';
	$fTb->typeofdata = 'N~O';
	$fTb->displaytype = 1;
	$fTb->presence = 0;
	$priceBlock->addField($fTb);
	echo "  + price_tuibao\n";
} else {
	echo "  exists: price_tuibao\n";
	try {
		$fTb->presence = 0;
		$fTb->displaytype = 1;
		$fTb->save();
	} catch (Exception $e) {
	}
}

// product_group as picklist (uitype 15)
$groupNames = ps_tb_collectGroupNames();
sort($groupNames, SORT_STRING);
echo "  group picklist values: " . count($groupNames) . "\n";

$fG = Vtiger_Field::getInstance('product_group', $module);
if (!$fG) {
	$fG = new Vtiger_Field();
	$fG->name = 'product_group';
	$fG->label = 'Nhóm';
	$fG->uitype = 15;
	$fG->column = 'product_group';
	$fG->columntype = 'VARCHAR(255)';
	$fG->typeofdata = 'V~O';
	$fG->displaytype = 1;
	$fG->presence = 0;
	$infoBlock->addField($fG);
	$fG->setPicklistValues($groupNames);
	echo "  + product_group (picklist)\n";
} else {
	echo "  exists: product_group — convert to picklist if needed\n";
	global $adb;
	// Force picklist meta
	$adb->pquery(
		'UPDATE vtiger_field SET uitype = ?, typeofdata = ?, fieldlabel = ? WHERE fieldid = ?',
		array(15, 'V~O', 'Nhóm', (int) $fG->id)
	);
	try {
		$fG->setPicklistValues($groupNames);
		echo "  setPicklistValues ok\n";
	} catch (Exception $e) {
		echo "  setPicklistValues: " . $e->getMessage() . "\n";
	}
	try {
		$fG->presence = 0;
		$fG->displaytype = 1;
		$fG->save();
	} catch (Exception $e) {
	}
}

// Ensure unit picklist extras (catalog units from Nguyen Khoa Excel)
$unitField = Vtiger_Field::getInstance('unit', $module);
if ($unitField) {
	try {
		require_once 'modules/ProductsServices/helpers/NguyenKhoaExcelCatalog.php';
		$unitField->setPicklistValues(
			ProductsServices_NguyenKhoaExcelCatalog_Helper::preferredUnitPicklist()
		);
		echo "  unit picklist expanded (Cái, Bịch, Lon, Hộp, …)\n";
	} catch (Exception $e) {
		echo "  unit picklist: " . $e->getMessage() . "\n";
	}
}

echo "=== Done ===\n";
