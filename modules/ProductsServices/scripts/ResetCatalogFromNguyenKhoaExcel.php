<?php
/*+***********************************************************************************
 * Soft-delete all Hàng hoá (ProductsServices) then seed from:
 *   sheet 1. Tong NL + sheet 4. CCDC - May moc
 * Excel: Danh muc SP Nguyen Khoa.xlsx (vtiger root or --file=)
 *
 * Dry-run (default):
 *   php -f modules/ProductsServices/scripts/ResetCatalogFromNguyenKhoaExcel.php
 * Execute:
 *   php -f modules/ProductsServices/scripts/ResetCatalogFromNguyenKhoaExcel.php -- --execute
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';

// Local CLI override: host "db" only works inside Docker network.
// From host machine use: MK_DB_HOST=127.0.0.1 MK_DB_PORT=3307 php -f ...
if (getenv('MK_DB_HOST')) {
	global $dbconfig;
	$dbconfig['db_server'] = getenv('MK_DB_HOST');
	$port = getenv('MK_DB_PORT') ? getenv('MK_DB_PORT') : '3306';
	$dbconfig['db_port'] = (strpos($port, ':') === 0) ? $port : (':' . $port);
	$dbconfig['db_hostname'] = $dbconfig['db_server'] . $dbconfig['db_port'];
	echo "DB override: {$dbconfig['db_hostname']}\n";
}

require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
require_once 'modules/ProductsServices/helpers/NguyenKhoaExcelCatalog.php';

$execute = in_array('--execute', $argv, true);
$file = '';
foreach ($argv as $arg) {
	if (strpos($arg, '--file=') === 0) {
		$file = substr($arg, 7);
	}
}
if ($file === '') {
	$candidates = array(
		'Danh muc SP Nguyen Khoa.xlsx',
		'./Danh muc SP Nguyen Khoa.xlsx',
		dirname(__DIR__, 3) . '/Danh muc SP Nguyen Khoa.xlsx',
	);
	foreach ($candidates as $c) {
		if (is_file($c)) {
			$file = $c;
			break;
		}
	}
}

echo "=== Reset ProductsServices catalog from Nguyen Khoa Excel ===\n";
echo "Mode: " . ($execute ? 'EXECUTE' : 'DRY-RUN') . "\n";
echo "File: " . ($file !== '' ? $file : '(missing)') . "\n";

if ($file === '' || !is_file($file)) {
	echo "ERROR: Excel file not found.\n";
	exit(1);
}

$db = PearDatabase::getInstance();
nkResetEnsureColumn($db, 'vtiger_productsservices', 'product_group', 'VARCHAR(255) DEFAULT NULL');
nkResetEnsureColumn($db, 'vtiger_productsservices', 'price_tuibao', 'DECIMAL(25,8) DEFAULT NULL');
foreach (array('price_lt_1m', 'price_gte_1m', 'price_gte_3m', 'price_gte_5m', 'price_gte_7m') as $col) {
	nkResetEnsureColumn($db, 'vtiger_productsservices', $col, 'DECIMAL(25,8) DEFAULT NULL');
}

$parsed = ProductsServices_NguyenKhoaExcelCatalog_Helper::parse($file);
foreach ($parsed['errors'] as $err) {
	echo "  ! $err\n";
}
$products = $parsed['products'];
$nl = 0;
$ccdc = 0;
foreach ($products as $p) {
	if ($p['source'] === 'nl') {
		$nl++;
	} else {
		$ccdc++;
	}
}
echo "Parsed: " . count($products) . " products (NL=$nl, CCDC=$ccdc)\n";

$active = $db->pquery(
	"SELECT COUNT(*) AS c FROM vtiger_productsservices ps
	 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0",
	array()
);
$activeCount = $active ? (int) $db->query_result($active, 0, 'c') : 0;
echo "Active ProductsServices now: $activeCount\n";
echo "Would soft-delete: $activeCount\n";
echo "Would create: " . count($products) . "\n";

if (!$execute) {
	echo "\nSample (first 5):\n";
	foreach (array_slice($products, 0, 5) as $p) {
		echo "  [{$p['source']}] {$p['sku']} | {$p['product_group']} | {$p['name']} | price={$p['price']} tuibao={$p['price_tuibao']}\n";
	}
	echo "\nRe-run with --execute to apply.\n";
	exit(0);
}

$db->pquery(
	"UPDATE vtiger_crmentity ce
	 INNER JOIN vtiger_productsservices ps ON ps.productsservicesid = ce.crmid
	 SET ce.deleted = 1, ce.modifiedtime = ?
	 WHERE ce.deleted = 0 AND ce.setype = ?",
	array(date('Y-m-d H:i:s'), 'ProductsServices')
);
echo "Soft-deleted existing ProductsServices.\n";

$ownerId = 1;
$uRes = $db->pquery('SELECT id FROM vtiger_users WHERE status = ? ORDER BY id ASC LIMIT 1', array('Active'));
if ($uRes && $db->num_rows($uRes)) {
	$ownerId = (int) $db->query_result($uRes, 0, 'id');
	if ($ownerId <= 0) {
		$ownerId = 1;
	}
}

$created = 0;
$failed = 0;
foreach ($products as $p) {
	try {
		$id = nkResetCreateProduct($db, $p, $ownerId);
		if ($id > 0) {
			$created++;
			if ($created <= 3 || $created % 25 === 0) {
				echo "  + #$id {$p['sku']}\n";
			}
		} else {
			$failed++;
			echo "  FAIL {$p['sku']}\n";
		}
	} catch (Exception $e) {
		$failed++;
		echo "  FAIL {$p['sku']}: " . $e->getMessage() . "\n";
	}
}

echo "=== Done: created=$created failed=$failed ===\n";

function nkResetEnsureColumn(PearDatabase $db, $table, $column, $ddl) {
	$check = $db->pquery("SHOW COLUMNS FROM `$table` LIKE ?", array($column));
	if ($check && $db->num_rows($check) > 0) {
		return;
	}
	$db->pquery("ALTER TABLE `$table` ADD COLUMN `$column` $ddl", array());
	echo "  ALTER $table.$column\n";
}

/**
 * @param PearDatabase $db
 * @param array $p
 * @param int $ownerId
 * @return int
 */
function nkResetCreateProduct(PearDatabase $db, array $p, $ownerId) {
	$seq = $db->pquery('SELECT id FROM vtiger_crmentity_seq', array());
	$crmId = 0;
	if ($seq && $db->num_rows($seq)) {
		$crmId = (int) $db->query_result($seq, 0, 'id') + 1;
		$db->pquery('UPDATE vtiger_crmentity_seq SET id = ?', array($crmId));
	} else {
		$max = $db->pquery('SELECT MAX(crmid) AS m FROM vtiger_crmentity', array());
		$crmId = $max ? ((int) $db->query_result($max, 0, 'm') + 1) : 1;
	}

	$now = date('Y-m-d H:i:s');
	$name = $p['name'];
	$db->pquery(
		"INSERT INTO vtiger_crmentity
		 (crmid, smcreatorid, smownerid, modifiedby, setype, description, createdtime, modifiedtime, presence, deleted, label)
		 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
		array($crmId, $ownerId, $ownerId, $ownerId, 'ProductsServices', '', $now, $now, 1, 0, $name)
	);

	$cols = array(
		'productsservicesid' => $crmId,
		'productsservicesname' => $name,
		'item_type' => $p['item_type'],
		'sku' => $p['sku'],
		'unit' => $p['unit'],
		'price' => $p['price'],
		'product_group' => $p['product_group'],
		'price_lt_1m' => $p['price_lt_1m'],
		'price_gte_1m' => $p['price_gte_1m'],
		'price_gte_3m' => $p['price_gte_3m'],
		'price_gte_5m' => $p['price_gte_5m'],
		'price_gte_7m' => $p['price_gte_7m'],
		'price_tuibao' => $p['price_tuibao'],
	);
	$existing = array();
	$show = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices', array());
	while ($show && ($row = $db->fetchByAssoc($show))) {
		$existing[strtolower($row['field'])] = true;
	}
	$use = array();
	$vals = array();
	$ph = array();
	foreach ($cols as $c => $v) {
		if (isset($existing[strtolower($c)])) {
			$use[] = '`' . $c . '`';
			$vals[] = $v;
			$ph[] = '?';
		}
	}
	$sql = 'INSERT INTO vtiger_productsservices (' . implode(',', $use) . ') VALUES (' . implode(',', $ph) . ')';
	$db->pquery($sql, $vals);

	$cfCheck = $db->pquery('SHOW TABLES LIKE ?', array('vtiger_productsservicescf'));
	if ($cfCheck && $db->num_rows($cfCheck) > 0) {
		$db->pquery(
			'INSERT INTO vtiger_productsservicescf (productsservicesid) VALUES (?)',
			array($crmId)
		);
	}

	return $crmId;
}
