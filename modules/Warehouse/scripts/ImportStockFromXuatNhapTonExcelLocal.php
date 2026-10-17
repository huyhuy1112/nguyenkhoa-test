<?php
/*+***********************************************************************************
 * Rename WH-001 → "Kho trung tâm" + import tồn từ báo cáo Xuất–Nhập–Tồn.
 *
 * Map:
 *   Excel Mã hàng     → SKU (khớp vtiger_productsservices.sku)
 *   Excel Tồn cuối kì → quantity
 *   Catalog name/price → product_name / last_price
 *   HSD                → để trống (kho tự chỉnh sau)
 *   Kho đích           → WH-001 (Kho trung tâm)
 *
 * Dry-run (mặc định):
 *   php -f modules/Warehouse/scripts/ImportStockFromXuatNhapTonExcelLocal.php
 * Execute:
 *   php -f modules/Warehouse/scripts/ImportStockFromXuatNhapTonExcelLocal.php -- --execute
 * Optional file:
 *   php -f ... -- --execute --file=/path/to/file.xlsx
 * Optional wipe all WH-001 stock before import:
 *   php -f ... -- --execute --wipe
 *************************************************************************************/

chdir(dirname(__DIR__, 3));

$host = getenv('MK_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('MK_DB_PORT') ?: 3307);
$user = getenv('MK_DB_USER') ?: 'root';
$pass = getenv('MK_DB_PASS');
if ($pass === false || $pass === null || $pass === '') {
	require_once 'config.inc.php';
	$pass = isset($dbconfig['db_password']) ? $dbconfig['db_password'] : '';
	if (isset($dbconfig['db_name'])) {
		$dbname = $dbconfig['db_name'];
	}
}
$dbname = isset($dbname) ? $dbname : (getenv('MK_DB_NAME') ?: 'TDB1');

$execute = in_array('--execute', $argv, true);
$wipe = in_array('--wipe', $argv, true);
$warehouseCode = 'WH-001';
$warehouseName = 'Kho trung tâm';
$defaultLot = '—';
$file = '';
foreach ($argv as $arg) {
	if (strpos($arg, '--file=') === 0) {
		$file = substr($arg, 7);
	}
}
if ($file === '') {
	$candidates = array(
		'input/BaoCaoXuatNhapTonChiTiet_KV05102026-150608-395.xlsx',
		dirname(__DIR__, 3) . '/input/BaoCaoXuatNhapTonChiTiet_KV05102026-150608-395.xlsx',
	);
	foreach ($candidates as $c) {
		if (is_file($c)) {
			$file = $c;
			break;
		}
	}
}

echo "=== Import stock from Xuat-Nhap-Ton Excel (local mysqli) ===\n";
echo 'Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN') . ($wipe ? ' +WIPE WH-001' : '') . "\n";
echo "MySQL: $host:$port / $dbname as $user\n";
echo "Warehouse: $warehouseCode → $warehouseName\n";
echo 'Excel: ' . ($file !== '' ? $file : '(missing)') . "\n";

if ($file === '' || !is_file($file)) {
	fwrite(STDERR, "ERROR: Excel file not found\n");
	exit(1);
}

$mysqli = @new mysqli($host, $user, $pass, $dbname, $port);
if ($mysqli->connect_errno) {
	foreach (array(3307, 3306) as $tryPort) {
		if ($tryPort === $port) {
			continue;
		}
		$m2 = @new mysqli($host, $user, $pass, $dbname, $tryPort);
		if (!$m2->connect_errno) {
			$mysqli = $m2;
			$port = $tryPort;
			echo "  connected via fallback port $port\n";
			break;
		}
	}
}
if ($mysqli->connect_errno) {
	fwrite(STDERR, "ERROR connect: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n");
	exit(1);
}
$mysqli->set_charset('utf8mb4');

function wh_xlsx_assoc_rows($path) {
	$zip = new ZipArchive();
	if ($zip->open($path) !== true) {
		throw new Exception('Cannot open xlsx: ' . $path);
	}
	$shared = array();
	if (($idx = $zip->locateName('xl/sharedStrings.xml')) !== false) {
		$xml = simplexml_load_string($zip->getFromIndex($idx));
		// Use local-name(): nested r/t runs often break prefixed xpath on sharedStrings.
		foreach ($xml->xpath('//*[local-name()="si"]') as $si) {
			$s = '';
			foreach ($si->xpath('.//*[local-name()="t"]') as $t) {
				$s .= (string) $t;
			}
			$shared[] = $s;
		}
	}
	$wb = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
	$wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
	$wb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
	$sheets = $wb->xpath('//m:sheets/m:sheet');
	if (!$sheets) {
		$zip->close();
		throw new Exception('No sheets');
	}
	$rid = (string) $sheets[0]->attributes('r', true)->id;
	$rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
	$target = '';
	foreach ($rels->Relationship as $rel) {
		if ((string) $rel['Id'] === $rid) {
			$target = (string) $rel['Target'];
			break;
		}
	}
	$target = ltrim($target, '/');
	if (strpos($target, 'xl/') !== 0) {
		$target = 'xl/' . $target;
	}
	if ($zip->locateName($target) === false) {
		for ($i = 1; $i <= 5; $i++) {
			$cand = "xl/worksheets/sheet{$i}.xml";
			if ($zip->locateName($cand) !== false) {
				$target = $cand;
				break;
			}
		}
	}
	$sheetXml = $zip->getFromName($target);
	$zip->close();
	if ($sheetXml === false) {
		throw new Exception('Worksheet missing: ' . $target);
	}
	$sx = simplexml_load_string($sheetXml);
	$sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
	$grid = array();
	$maxRow = 0;
	$maxCol = 0;
	foreach ($sx->xpath('//m:c') as $c) {
		$ref = (string) $c['r'];
		if ($ref === '' || !preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
			continue;
		}
		$col = 0;
		foreach (str_split($m[1]) as $ch) {
			$col = $col * 26 + (ord($ch) - 64);
		}
		$row = (int) $m[2];
		$maxRow = max($maxRow, $row);
		$maxCol = max($maxCol, $col);
		$t = (string) $c['t'];
		$vNode = $c->v;
		$val = '';
		if ($vNode !== null) {
			$raw = (string) $vNode;
			if ($t === 's') {
				$i = (int) $raw;
				$val = isset($shared[$i]) ? $shared[$i] : $raw;
			} else {
				$val = $raw;
			}
		}
		$grid[$row][$col] = $val;
	}
	$headers = array();
	for ($c = 1; $c <= $maxCol; $c++) {
		$headers[$c] = isset($grid[1][$c]) ? trim((string) $grid[1][$c]) : '';
	}
	$out = array();
	for ($r = 2; $r <= $maxRow; $r++) {
		$assoc = array();
		$any = false;
		for ($c = 1; $c <= $maxCol; $c++) {
			$h = $headers[$c];
			if ($h === '') {
				continue;
			}
			$v = isset($grid[$r][$c]) ? trim((string) $grid[$r][$c]) : '';
			if ($v !== '') {
				$any = true;
			}
			$assoc[$h] = $v;
		}
		if ($any) {
			$out[] = $assoc;
		}
	}
	return $out;
}

function wh_next_stock_id(mysqli $mysqli) {
	$mysqli->query('CREATE TABLE IF NOT EXISTS vtiger_warehouse_stock_seq (id INT NOT NULL) ENGINE=InnoDB');
	$rs = $mysqli->query('SELECT id FROM vtiger_warehouse_stock_seq LIMIT 1');
	if (!$rs || $rs->num_rows === 0) {
		$max = 0;
		$rm = $mysqli->query('SELECT MAX(stockid) AS m FROM vtiger_warehouse_stock');
		if ($rm && ($row = $rm->fetch_assoc())) {
			$max = (int) $row['m'];
		}
		$mysqli->query('INSERT INTO vtiger_warehouse_stock_seq (id) VALUES (' . max(1, $max) . ')');
	}
	$mysqli->begin_transaction();
	$rs = $mysqli->query('SELECT id FROM vtiger_warehouse_stock_seq FOR UPDATE');
	$row = $rs->fetch_assoc();
	$next = (int) $row['id'] + 1;
	$mysqli->query('UPDATE vtiger_warehouse_stock_seq SET id = ' . $next);
	$mysqli->commit();
	return $next;
}

function wh_decode_name($s) {
	$s = html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	// second pass for double-encoded entities like &amp;iacute;
	$s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	return trim($s);
}

function wh_num($v) {
	$v = trim((string) $v);
	if ($v === '') {
		return 0.0;
	}
	$v = str_replace(array(',', ' '), '', $v);
	return (float) $v;
}

try {
	$rows = wh_xlsx_assoc_rows($file);
} catch (Exception $e) {
	fwrite(STDERR, 'ERROR parse excel: ' . $e->getMessage() . "\n");
	exit(1);
}
echo 'Excel data rows: ' . count($rows) . "\n";

// Rename warehouse (and denormalized warehouse_name on stock)
$whRs = $mysqli->query("SELECT code, name FROM vtiger_warehouse WHERE code = '" . $mysqli->real_escape_string($warehouseCode) . "' AND deleted = 0 LIMIT 1");
if (!$whRs || $whRs->num_rows === 0) {
	fwrite(STDERR, "ERROR: warehouse $warehouseCode not found\n");
	exit(1);
}
$whRow = $whRs->fetch_assoc();
$oldName = wh_decode_name($whRow['name']);
echo "Rename: \"$oldName\" → \"$warehouseName\"\n";
if ($execute) {
	$escName = $mysqli->real_escape_string($warehouseName);
	$escCode = $mysqli->real_escape_string($warehouseCode);
	$now = date('Y-m-d H:i:s');
	$mysqli->query("UPDATE vtiger_warehouse SET name = '$escName', type = 'central', updatedtime = '$now' WHERE code = '$escCode' AND deleted = 0");
	$mysqli->query("UPDATE vtiger_warehouse_stock SET warehouse_name = '$escName' WHERE warehouse_id = '$escCode'");
	echo "  renamed OK\n";
}

if ($wipe && $execute) {
	$escCode = $mysqli->real_escape_string($warehouseCode);
	$mysqli->query("DELETE FROM vtiger_warehouse_stock WHERE warehouse_id = '$escCode'");
	echo "  wiped WH-001 stock\n";
} elseif ($wipe) {
	echo "  [dry] would wipe all stock on $warehouseCode\n";
}

// Catalog by SKU
$catalog = array();
$crs = $mysqli->query(
	"SELECT ps.productsservicesid AS id, ps.sku, ps.productsservicesname AS name, IFNULL(ps.price, 0) AS price
	 FROM vtiger_productsservices ps
	 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
	 WHERE IFNULL(ps.sku, '') <> ''"
);
while ($crs && ($row = $crs->fetch_assoc())) {
	$sku = trim((string) $row['sku']);
	if ($sku === '') {
		continue;
	}
	$catalog[$sku] = array(
		'id' => (int) $row['id'],
		'name' => wh_decode_name($row['name']),
		'price' => (float) $row['price'],
	);
}
echo 'Catalog SKUs: ' . count($catalog) . "\n";

$stats = array(
	'excel' => 0,
	'matched' => 0,
	'missing_sku' => 0,
	'updated' => 0,
	'inserted' => 0,
	'skipped_empty_sku' => 0,
);
$missing = array();
$samples = array();

$now = date('Y-m-d H:i:s');
$escWh = $mysqli->real_escape_string($warehouseCode);
$escWhName = $mysqli->real_escape_string($warehouseName);

foreach ($rows as $row) {
	$stats['excel']++;
	$sku = isset($row['Mã hàng']) ? trim((string) $row['Mã hàng']) : '';
	if ($sku === '') {
		$stats['skipped_empty_sku']++;
		continue;
	}
	$qty = wh_num(isset($row['Tồn cuối kì']) ? $row['Tồn cuối kì'] : '0');
	$excelName = isset($row['Tên hàng']) ? trim((string) $row['Tên hàng']) : '';

	if (!isset($catalog[$sku])) {
		$stats['missing_sku']++;
		if (count($missing) < 30) {
			$missing[] = $sku . ($excelName !== '' ? " ($excelName)" : '');
		}
		continue;
	}
	$stats['matched']++;
	$pid = $catalog[$sku]['id'];
	$name = $catalog[$sku]['name'] !== '' ? $catalog[$sku]['name'] : $excelName;
	$price = $catalog[$sku]['price'];
	$key = $warehouseCode . '|' . $sku . '|' . $defaultLot;

	if (count($samples) < 8) {
		$samples[] = "$sku | qty=$qty | price=$price | $name";
	}

	if (!$execute) {
		continue;
	}

	$escSku = $mysqli->real_escape_string($sku);
	$escKey = $mysqli->real_escape_string($key);
	$escName = $mysqli->real_escape_string($name);

	// Remove other lots for same SKU on this warehouse so cutover is 1 row / SKU
	$mysqli->query(
		"DELETE FROM vtiger_warehouse_stock
		 WHERE warehouse_id = '$escWh'
		   AND product_key <> '$escKey'
		   AND (
				product_key LIKE '" . $mysqli->real_escape_string($warehouseCode . '|' . $sku . '|') . "%'
				OR productid = $pid
		   )"
	);

	$find = $mysqli->query("SELECT stockid FROM vtiger_warehouse_stock WHERE product_key = '$escKey' LIMIT 1");
	if ($find && $find->num_rows > 0) {
		$sid = (int) $find->fetch_assoc()['stockid'];
		$mysqli->query(
			"UPDATE vtiger_warehouse_stock
			 SET productid = $pid,
			     product_name = '$escName',
			     quantity = $qty,
			     last_price = $price,
			     warehouse_name = '$escWhName',
			     expired_date = NULL,
			     updatedtime = '$now'
			 WHERE stockid = $sid"
		);
		$stats['updated']++;
	} else {
		$sid = wh_next_stock_id($mysqli);
		$code = 'STK-' . str_pad((string) $sid, 4, '0', STR_PAD_LEFT);
		$escCode = $mysqli->real_escape_string($code);
		$mysqli->query(
			"INSERT INTO vtiger_warehouse_stock
			 (stockid, code, product_key, productid, product_name, quantity, last_price,
			  warehouse_id, warehouse_name, shrinkage_qty, expired_date, createdtime, updatedtime)
			 VALUES ($sid, '$escCode', '$escKey', $pid, '$escName', $qty, $price,
			         '$escWh', '$escWhName', 0, NULL, '$now', '$now')"
		);
		$stats['inserted']++;
	}
}

echo "\n--- Summary ---\n";
foreach ($stats as $k => $v) {
	echo "  $k: $v\n";
}
if ($samples) {
	echo "\nSamples:\n";
	foreach ($samples as $s) {
		echo "  - $s\n";
	}
}
if ($missing) {
	echo "\nMissing SKU in catalog (first " . count($missing) . "):\n";
	foreach ($missing as $m) {
		echo "  - $m\n";
	}
}
if (!$execute) {
	echo "\nDry-run only. Re-run with --execute to apply.\n";
} else {
	echo "\nDone.\n";
}

$mysqli->close();
?>
