<?php
/*+***********************************************************************************
 * Seed / reset Hàng hoá from Excel using direct mysqli (no Docker hostname "db").
 *
 * Defaults host to 127.0.0.1:3307 (docker-compose port map). Override as needed.
 *
 * Dry-run:
 *   php -f modules/ProductsServices/scripts/ResetCatalogFromNguyenKhoaExcelLocal.php
 * Execute:
 *   php -f modules/ProductsServices/scripts/ResetCatalogFromNguyenKhoaExcelLocal.php -- --execute
 *************************************************************************************/

chdir(dirname(__DIR__, 3));

$host = getenv('MK_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('MK_DB_PORT') ?: 3307);
$user = getenv('MK_DB_USER') ?: 'root';
$pass = getenv('MK_DB_PASS');
if ($pass === false || $pass === null || $pass === '') {
	// Match config.inc.php password when env not set
	require_once 'config.inc.php';
	$pass = isset($dbconfig['db_password']) ? $dbconfig['db_password'] : '';
	if (isset($dbconfig['db_name'])) {
		$dbname = $dbconfig['db_name'];
	}
}
$dbname = isset($dbname) ? $dbname : (getenv('MK_DB_NAME') ?: 'TDB1');
$execute = in_array('--execute', $argv, true);

require_once 'modules/ProductsServices/helpers/NguyenKhoaExcelCatalog.php';

function colExists(mysqli $db, $table, $col) {
	$c = $db->real_escape_string($col);
	$t = $db->real_escape_string($table);
	$rs = $db->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
	return $rs && $rs->num_rows > 0;
}

function ensureCol(mysqli $db, $table, $col, $ddl) {
	if (colExists($db, $table, $col)) {
		return;
	}
	$db->query("ALTER TABLE `$table` ADD COLUMN `$col` $ddl");
	echo "  ALTER $table.$col\n";
}

echo "=== Reset catalog LOCAL (mysqli) ===\n";
echo "Mode: " . ($execute ? 'EXECUTE' : 'DRY-RUN') . "\n";
echo "MySQL: $host:$port / $dbname as $user\n";

$mysqli = @new mysqli($host, $user, $pass, $dbname, $port);
if ($mysqli->connect_errno) {
	fwrite(STDERR, "ERROR connect: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n");
	fwrite(STDERR, "Tip: Docker map is 3307. Workbench use 127.0.0.1:3307 password from config.inc.php\n");
	exit(1);
}
$mysqli->set_charset('utf8mb4');

ensureCol($mysqli, 'vtiger_productsservices', 'product_group', 'VARCHAR(255) DEFAULT NULL');
ensureCol($mysqli, 'vtiger_productsservices', 'price_tuibao', 'DECIMAL(25,8) DEFAULT NULL');
foreach (array('price_lt_1m', 'price_gte_1m', 'price_gte_3m', 'price_gte_5m', 'price_gte_7m') as $c) {
	ensureCol($mysqli, 'vtiger_productsservices', $c, 'DECIMAL(25,8) DEFAULT NULL');
}

$file = '';
foreach (array('Danh muc SP Nguyen Khoa.xlsx', dirname(__DIR__, 3) . '/Danh muc SP Nguyen Khoa.xlsx') as $c) {
	if (is_file($c)) {
		$file = $c;
		break;
	}
}
if ($file === '') {
	fwrite(STDERR, "ERROR: Excel not found\n");
	exit(1);
}

$parsed = ProductsServices_NguyenKhoaExcelCatalog_Helper::parse($file);
foreach ($parsed['errors'] as $e) {
	echo "  ! $e\n";
}
$products = $parsed['products'];
$nl = 0;
$cc = 0;
foreach ($products as $p) {
	if ($p['source'] === 'nl') {
		$nl++;
	} else {
		$cc++;
	}
}
echo "Parsed: " . count($products) . " (NL=$nl CCDC=$cc)\n";

$active = 0;
$rs = $mysqli->query(
	"SELECT COUNT(*) c FROM vtiger_productsservices ps
	 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0"
);
if ($rs) {
	$row = $rs->fetch_assoc();
	$active = (int) $row['c'];
}
echo "Active now: $active\n";
echo "Would soft-delete: $active , create: " . count($products) . "\n";

// Sync picklist table for product_group if exists
function ensurePicklistGroups(mysqli $db, array $products) {
	$groups = array();
	foreach ($products as $p) {
		$g = trim((string) $p['product_group']);
		if ($g !== '') {
			$groups[$g] = true;
		}
	}
	$names = array_keys($groups);
	sort($names, SORT_STRING);

	// Register field as picklist uitype 15 if field exists
	$db->query(
		"UPDATE vtiger_field f
		 INNER JOIN vtiger_tab t ON t.tabid = f.tabid
		 SET f.uitype = 15, f.typeofdata = 'V~O', f.fieldlabel = 'Nhóm'
		 WHERE t.name = 'ProductsServices' AND f.fieldname = 'product_group'"
	);

	// Create picklist table if missing (vtiger naming)
	$check = $db->query("SHOW TABLES LIKE 'vtiger_product_group'");
	if (!$check || $check->num_rows === 0) {
		$db->query(
			"CREATE TABLE vtiger_product_group (
				product_groupid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				product_group VARCHAR(200) NOT NULL,
				presence INT NOT NULL DEFAULT 1,
				picklist_valueid INT NOT NULL DEFAULT 0,
				sortorderid INT DEFAULT 0,
				color VARCHAR(10) DEFAULT NULL,
				UNIQUE KEY product_group (product_group)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8"
		);
		echo "  created vtiger_product_group\n";
	}
	$seqCheck = $db->query("SHOW TABLES LIKE 'vtiger_product_group_seq'");
	if (!$seqCheck || $seqCheck->num_rows === 0) {
		$db->query('CREATE TABLE vtiger_product_group_seq (id INT NOT NULL) ENGINE=InnoDB');
		$db->query('INSERT INTO vtiger_product_group_seq VALUES (1)');
	}

	// picklistvalues_seq for value ids
	$needValueId = true;
	$pv = $db->query("SHOW TABLES LIKE 'vtiger_picklistvalues_seq'");
	if (!$pv || $pv->num_rows === 0) {
		$needValueId = false;
	}

	$sort = 1;
	foreach ($names as $g) {
		$esc = $db->real_escape_string($g);
		$ex = $db->query("SELECT product_groupid FROM vtiger_product_group WHERE product_group = '$esc' LIMIT 1");
		if ($ex && $ex->num_rows > 0) {
			$sort++;
			continue;
		}
		$valueId = 0;
		if ($needValueId) {
			$sr = $db->query('SELECT id FROM vtiger_picklistvalues_seq');
			if ($sr && $row = $sr->fetch_assoc()) {
				$valueId = (int) $row['id'] + 1;
				$db->query('UPDATE vtiger_picklistvalues_seq SET id = ' . $valueId);
			}
		}
		$db->query(
			"INSERT INTO vtiger_product_group (product_group, presence, picklist_valueid, sortorderid)
			 VALUES ('$esc', 1, $valueId, $sort)"
		);
		// role-picklist mapping: often all roles via role2picklist
		if ($valueId > 0) {
			$roles = $db->query('SELECT roleid FROM vtiger_role');
			while ($roles && ($role = $roles->fetch_assoc())) {
				$rid = $db->real_escape_string($role['roleid']);
				$db->query(
					"INSERT IGNORE INTO vtiger_role2picklist (roleid, picklistvalueid, picklistid, sortid)
					 SELECT '$rid', $valueId, picklistid, $sort FROM vtiger_picklist WHERE name = 'product_group' LIMIT 1"
				);
			}
		}
		$sort++;
	}

	// Ensure picklist meta row
	$pl = $db->query("SELECT picklistid FROM vtiger_picklist WHERE name = 'product_group' LIMIT 1");
	if (!$pl || $pl->num_rows === 0) {
		$db->query("INSERT INTO vtiger_picklist (name) VALUES ('product_group')");
		echo "  registered vtiger_picklist.product_group\n";
	}
	echo "  picklist groups ready: " . count($names) . "\n";
}

if (!$execute) {
	echo "\nSample:\n";
	foreach (array_slice($products, 0, 5) as $p) {
		echo "  [{$p['source']}] {$p['sku']} | {$p['product_group']} | {$p['name']} | {$p['price']} / tb {$p['price_tuibao']}\n";
	}
	echo "\nRe-run with --execute to apply.\n";
	exit(0);
}

// Soft-delete
$now = date('Y-m-d H:i:s');
$mysqli->query(
	"UPDATE vtiger_crmentity ce
	 INNER JOIN vtiger_productsservices ps ON ps.productsservicesid = ce.crmid
	 SET ce.deleted = 1, ce.modifiedtime = '" . $mysqli->real_escape_string($now) . "'
	 WHERE ce.deleted = 0 AND ce.setype = 'ProductsServices'"
);
echo "Soft-deleted: " . $mysqli->affected_rows . "\n";

ensurePicklistGroups($mysqli, $products);

// owner
$ownerId = 1;
$u = $mysqli->query("SELECT id FROM vtiger_users WHERE status = 'Active' ORDER BY id ASC LIMIT 1");
if ($u && $row = $u->fetch_assoc()) {
	$ownerId = (int) $row['id'];
}

// ensure seq
$mysqli->query('CREATE TABLE IF NOT EXISTS vtiger_crmentity_seq (id INT NOT NULL)');
$seq = $mysqli->query('SELECT id FROM vtiger_crmentity_seq');
if (!$seq || $seq->num_rows === 0) {
	$max = $mysqli->query('SELECT MAX(crmid) m FROM vtiger_crmentity');
	$m = 1;
	if ($max && $r = $max->fetch_assoc()) {
		$m = max(1, (int) $r['m']);
	}
	$mysqli->query('INSERT INTO vtiger_crmentity_seq (id) VALUES (' . $m . ')');
}

$colsHave = array();
$sh = $mysqli->query('SHOW COLUMNS FROM vtiger_productsservices');
while ($sh && ($c = $sh->fetch_assoc())) {
	$colsHave[strtolower($c['Field'])] = true;
}
$hasCf = false;
$t = $mysqli->query("SHOW TABLES LIKE 'vtiger_productsservicescf'");
if ($t && $t->num_rows > 0) {
	$hasCf = true;
}

$created = 0;
$failed = 0;
foreach ($products as $p) {
	$mysqli->begin_transaction();
	try {
		$seqR = $mysqli->query('SELECT id FROM vtiger_crmentity_seq FOR UPDATE');
		$crmId = 1;
		if ($seqR && $r = $seqR->fetch_assoc()) {
			$crmId = (int) $r['id'] + 1;
		}
		$mysqli->query('UPDATE vtiger_crmentity_seq SET id = ' . $crmId);

		$name = $p['name'];
		$escName = $mysqli->real_escape_string($name);
		$escNow = $mysqli->real_escape_string($now);
		$ok = $mysqli->query(
			"INSERT INTO vtiger_crmentity
			 (crmid, smcreatorid, smownerid, modifiedby, setype, description, createdtime, modifiedtime, presence, deleted, label)
			 VALUES ($crmId, $ownerId, $ownerId, $ownerId, 'ProductsServices', '', '$escNow', '$escNow', 1, 0, '$escName')"
		);
		if (!$ok) {
			throw new Exception($mysqli->error);
		}

		$moneyKeys = array(
			'price', 'price_lt_1m', 'price_gte_1m', 'price_gte_3m', 'price_gte_5m', 'price_gte_7m', 'price_tuibao',
		);
		$data = array(
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
		$cols = array();
		$vals = array();
		foreach ($data as $k => $v) {
			if (!isset($colsHave[strtolower($k)])) {
				continue;
			}
			$cols[] = '`' . $k . '`';
			if ($v === null || $v === '') {
				$vals[] = 'NULL';
			} elseif ($k === 'productsservicesid' || in_array($k, $moneyKeys, true)) {
				$vals[] = is_numeric($v) ? (0 + $v) : 0;
			} else {
				$vals[] = "'" . $mysqli->real_escape_string((string) $v) . "'";
			}
		}
		$sql = 'INSERT INTO vtiger_productsservices (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')';
		if (!$mysqli->query($sql)) {
			throw new Exception($mysqli->error);
		}
		if ($hasCf) {
			$mysqli->query("INSERT INTO vtiger_productsservicescf (productsservicesid) VALUES ($crmId)");
		}
		$mysqli->commit();
		$created++;
		if ($created <= 3 || $created % 40 === 0) {
			echo "  + #$crmId {$p['sku']}\n";
		}
	} catch (Exception $e) {
		$mysqli->rollback();
		$failed++;
		echo "  FAIL {$p['sku']}: " . $e->getMessage() . "\n";
	}
}

echo "=== Done created=$created failed=$failed ===\n";
$rs = $mysqli->query(
	"SELECT COUNT(*) c FROM vtiger_productsservices ps
	 INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0"
);
$row = $rs->fetch_assoc();
echo "Active after: " . $row['c'] . "\n";
