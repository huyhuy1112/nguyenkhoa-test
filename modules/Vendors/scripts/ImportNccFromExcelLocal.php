<?php
/*+***********************************************************************************
 * Import Nhà cung cấp từ Excel kiểu KiotViet (input/Danh Sách NCC.xlsx).
 *
 * Cột: Mã nhà cung cấp, Tên nhà cung cấp, Email, Điện thoại, Địa chỉ,
 *      Khu vực, Phường/Xã, Mã số thuế, Ghi chú, Nhóm nhà cung cấp, Công ty…
 * MST lưu trong description dạng "MST: …" để map MISA sau.
 *
 * Dry-run:
 *   php -f modules/Vendors/scripts/ImportNccFromExcelLocal.php
 * Execute:
 *   php -f modules/Vendors/scripts/ImportNccFromExcelLocal.php -- --execute
 *************************************************************************************/

chdir(dirname(__DIR__, 3));

require_once 'vendor/autoload.php';
require_once 'config.php';

if (isset($dbconfig) && is_array($dbconfig)) {
	$host = isset($dbconfig['db_server']) ? (string) $dbconfig['db_server'] : '';
	if ($host === 'db' || $host === 'mysql') {
		$dbconfig['db_server'] = '127.0.0.1';
		$dbconfig['db_port'] = ':3307';
		$dbconfig['db_hostname'] = '127.0.0.1:3307';
	}
}

include_once 'vtlib/Vtiger/Cron.php';
vimport('includes.runtime.EntryPoint');
require_once 'modules/Users/Users.php';

global $current_user, $adb;
$current_user = Users::getActiveAdminUser();
$adb = PearDatabase::getInstance();

$execute = in_array('--execute', $argv, true);
$path = 'input/Danh Sách NCC.xlsx';
foreach ($argv as $arg) {
	if (strpos($arg, '--file=') === 0) {
		$path = substr($arg, 7);
	}
}

echo "=== Import NCC → Vendors ===\n";
echo 'Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN') . "\n";
echo 'File: ' . $path . "\n\n";

if (!is_readable($path)) {
	fwrite(STDERR, "Không đọc được file: {$path}\n");
	exit(1);
}

if (!class_exists('ZipArchive')) {
	fwrite(STDERR, "Cần ZipArchive để đọc xlsx.\n");
	exit(1);
}

function mk_ncc_xlsx_rows($path) {
	$z = new ZipArchive();
	if ($z->open($path) !== true) {
		throw new Exception('Không mở được xlsx');
	}
	$ss = array();
	$shared = $z->getFromName('xl/sharedStrings.xml');
	if ($shared) {
		$xml = simplexml_load_string($shared);
		foreach ($xml->si as $si) {
			$t = '';
			if (isset($si->t)) {
				$t = (string) $si->t;
			} else {
				foreach ($si->r as $r) {
					$t .= (string) $r->t;
				}
			}
			$ss[] = $t;
		}
	}
	$sheet = $z->getFromName('xl/worksheets/sheet1.xml');
	$sx = simplexml_load_string($sheet);
	$sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
	$rows = $sx->xpath('//m:sheetData/m:row');
	$out = array();
	foreach ($rows as $row) {
		$cells = array();
		foreach ($row->c as $c) {
			$ref = (string) $c['r'];
			$col = preg_replace('/\d+/', '', $ref);
			$v = isset($c->v) ? (string) $c->v : '';
			$t = (string) $c['t'];
			if ($t === 's' && isset($ss[(int) $v])) {
				$v = $ss[(int) $v];
			}
			$cells[$col] = $v;
		}
		$out[] = $cells;
	}
	$z->close();
	return $out;
}

function mk_ncc_norm_header($h) {
	$h = mb_strtolower(trim((string) $h), 'UTF-8');
	$h = str_replace(array('đ', 'Đ'), array('d', 'd'), $h);
	return $h;
}

function mk_ncc_find_vendor_by_code(PearDatabase $db, $code) {
	$code = trim((string) $code);
	if ($code === '') {
		return 0;
	}
	$rs = $db->pquery(
		'SELECT v.vendorid FROM vtiger_vendor v
		 INNER JOIN vtiger_crmentity ce ON ce.crmid = v.vendorid AND ce.deleted = 0
		 WHERE v.vendor_no = ? LIMIT 1',
		array($code)
	);
	return ($rs && $db->num_rows($rs) > 0) ? (int) $db->query_result($rs, 0, 'vendorid') : 0;
}

function mk_ncc_find_vendor_by_name(PearDatabase $db, $name) {
	$name = trim((string) $name);
	if ($name === '') {
		return 0;
	}
	$rs = $db->pquery(
		'SELECT v.vendorid FROM vtiger_vendor v
		 INNER JOIN vtiger_crmentity ce ON ce.crmid = v.vendorid AND ce.deleted = 0
		 WHERE v.vendorname = ? LIMIT 1',
		array($name)
	);
	return ($rs && $db->num_rows($rs) > 0) ? (int) $db->query_result($rs, 0, 'vendorid') : 0;
}

try {
	$raw = mk_ncc_xlsx_rows($path);
} catch (Exception $e) {
	fwrite(STDERR, $e->getMessage() . "\n");
	exit(1);
}

if (count($raw) < 2) {
	fwrite(STDERR, "File trống.\n");
	exit(1);
}

$header = $raw[0];
$map = array();
foreach ($header as $col => $label) {
	$key = mk_ncc_norm_header($label);
	if (strpos($key, 'ma nha cung') !== false || $key === 'ma ncc') {
		$map['code'] = $col;
	} elseif (strpos($key, 'ten nha cung') !== false || $key === 'ten ncc') {
		$map['name'] = $col;
	} elseif ($key === 'email') {
		$map['email'] = $col;
	} elseif (strpos($key, 'dien thoai') !== false || $key === 'sdt') {
		$map['phone'] = $col;
	} elseif (strpos($key, 'dia chi') !== false) {
		$map['address'] = $col;
	} elseif (strpos($key, 'khu vuc') !== false) {
		$map['region'] = $col;
	} elseif (strpos($key, 'phuong') !== false || strpos($key, 'xa') !== false) {
		$map['ward'] = $col;
	} elseif (strpos($key, 'ma so thue') !== false || $key === 'mst') {
		$map['tax'] = $col;
	} elseif (strpos($key, 'ghi chu') !== false) {
		$map['note'] = $col;
	} elseif (strpos($key, 'nhom') !== false) {
		$map['group'] = $col;
	} elseif ($key === 'cong ty') {
		$map['company'] = $col;
	}
}

if (empty($map['name'])) {
	fwrite(STDERR, "Không tìm thấy cột Tên nhà cung cấp.\n");
	exit(1);
}

$stats = array('create' => 0, 'update' => 0, 'skip' => 0, 'error' => 0);

for ($i = 1; $i < count($raw); $i++) {
	$row = $raw[$i];
	$get = function ($k) use ($row, $map) {
		if (empty($map[$k])) {
			return '';
		}
		$col = $map[$k];
		return isset($row[$col]) ? trim((string) $row[$col]) : '';
	};
	$name = $get('name');
	$code = $get('code');
	if ($name === '' && $code === '') {
		$stats['skip']++;
		continue;
	}
	if ($name === '') {
		$name = 'NCC ' . ($code !== '' ? $code : ('#' . $i));
	}
	$phone = $get('phone');
	$email = $get('email');
	$address = $get('address');
	$region = $get('region');
	$ward = $get('ward');
	$tax = $get('tax');
	$note = $get('note');
	$group = $get('group');
	$company = $get('company');
	$city = trim($ward . ($ward && $region ? ', ' : '') . $region);
	$descParts = array();
	if ($tax !== '') {
		$descParts[] = 'MST: ' . $tax;
	}
	if ($company !== '') {
		$descParts[] = 'Công ty: ' . $company;
	}
	if ($note !== '') {
		$descParts[] = $note;
	}
	$description = implode("\n", $descParts);

	$existingId = $code !== '' ? mk_ncc_find_vendor_by_code($adb, $code) : 0;
	if ($existingId <= 0) {
		$existingId = mk_ncc_find_vendor_by_name($adb, $name);
	}

	$action = $existingId > 0 ? 'UPDATE' : 'CREATE';
	echo sprintf(
		"[%s] %s | %s | %s | MST=%s\n",
		$action,
		$code !== '' ? $code : '(no-code)',
		$name,
		$phone,
		$tax !== '' ? $tax : '-'
	);

	if (!$execute) {
		if ($existingId > 0) {
			$stats['update']++;
		} else {
			$stats['create']++;
		}
		continue;
	}

	try {
		if ($existingId > 0) {
			$record = Vtiger_Record_Model::getInstanceById($existingId, 'Vendors');
			$stats['update']++;
		} else {
			$record = Vtiger_Record_Model::getCleanInstance('Vendors');
			$stats['create']++;
		}
		$record->set('mode', $existingId > 0 ? 'edit' : '');
		$record->set('vendorname', $name);
		if ($code !== '') {
			$record->set('vendor_no', $code);
		}
		$record->set('phone', $phone);
		$record->set('email', $email);
		$record->set('street', $address);
		$record->set('city', $city);
		$record->set('category', $group);
		$record->set('description', $description);
		$record->set('assigned_user_id', $current_user->id);
		$record->save();
	} catch (Exception $e) {
		$stats['error']++;
		echo '  ERROR: ' . $e->getMessage() . "\n";
	}
}

echo "\n--- Summary ---\n";
echo 'create: ' . $stats['create'] . "\n";
echo 'update: ' . $stats['update'] . "\n";
echo 'skip:   ' . $stats['skip'] . "\n";
echo 'error:  ' . $stats['error'] . "\n";
if (!$execute) {
	echo "\nChạy lại với --execute để ghi vào CRM.\n";
}
