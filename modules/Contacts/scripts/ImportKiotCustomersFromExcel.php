<?php
/*+***********************************************************************************
 * Import Contacts from KiotViet-style Excel (Khách lẻ / Miutea).
 *
 * - contact_no = Excel "Mã khách hàng" as-is (KL_… / MIUTEA_…)
 * - tags: ca_nhan|cong_ty + khach_le|miutea
 * - skips: Chi nhánh tạo, Facebook
 *
 * Dry-run:
 *   php -f modules/Contacts/scripts/ImportKiotCustomersFromExcel.php
 * Execute:
 *   php -f modules/Contacts/scripts/ImportKiotCustomersFromExcel.php -- --execute
 * One file only:
 *   php -f modules/Contacts/scripts/ImportKiotCustomersFromExcel.php -- --execute --file=khach_le
 *   php -f modules/Contacts/scripts/ImportKiotCustomersFromExcel.php -- --execute --file=miutea
 *************************************************************************************/

chdir(dirname(__DIR__, 3));

require_once 'vendor/autoload.php';
require_once 'config.php';

// CLI outside Docker: host "db" is not resolvable on Mac — use published port
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
require_once 'modules/Contacts/models/ModernService.php';
require_once 'modules/Contacts/helpers/ContactTagCatalog.php';
require_once 'include/utils/MkEntityNumbering.php';

global $current_user, $adb, $VTIGER_BULK_SAVE_MODE;
$current_user = Users::getActiveAdminUser();
$adb = PearDatabase::getInstance();

$execute = in_array('--execute', $argv, true);
$fileFilter = '';
foreach ($argv as $arg) {
	if (strpos($arg, '--file=') === 0) {
		$fileFilter = strtolower(substr($arg, 7));
	}
}

// Switch prefixes for future auto-numbers (does not rewrite existing LH/KH records)
MkEntityNumbering::ensureModuleSequence('Contacts');
MkEntityNumbering::ensureModuleSequence('Accounts');

$sources = array(
	'khach_le' => array(
		'path' => 'input/Khách Hàng Khách Lẻ.xlsx',
		'segment_tag' => 'khach_le',
		'label' => 'Khách lẻ',
	),
	'miutea' => array(
		'path' => 'input/Khách hàng Miutea.xlsx',
		'segment_tag' => 'miutea',
		'label' => 'Miutea',
	),
);

echo "=== Import Kiot Contacts (Khách lẻ / Miutea) ===\n";
echo 'Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN') . "\n";
echo 'Next Contact auto-number: ' . MkEntityNumbering::previewNextNumber('Contacts') . "\n";
echo 'Next Account auto-number: ' . MkEntityNumbering::previewNextNumber('Accounts') . "\n\n";

$totals = array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0);

foreach ($sources as $key => $src) {
	if ($fileFilter !== '' && $fileFilter !== $key) {
		continue;
	}
	$path = $src['path'];
	if (!is_file($path)) {
		echo "MISSING: {$path}\n";
		continue;
	}
	echo "--- {$src['label']}: {$path} ---\n";
	try {
		$rows = nk_xlsx_assoc_rows($path);
	} catch (Exception $e) {
		echo 'ERROR read: ' . $e->getMessage() . "\n";
		$totals['errors']++;
		continue;
	}
	echo 'Rows: ' . count($rows) . "\n";

	$prevBulk = isset($VTIGER_BULK_SAVE_MODE) ? $VTIGER_BULK_SAVE_MODE : false;
	$VTIGER_BULK_SAVE_MODE = true;

	foreach ($rows as $idx => $row) {
		$sheetRow = $idx + 2;
		try {
			$payload = nk_map_kiot_row($row, $src['segment_tag']);
			if ($payload['lastname'] === '') {
				$totals['skipped']++;
				continue;
			}
			if ($payload['contact_no'] === '' && $payload['phone'] === '') {
				echo "  row {$sheetRow}: skip (no mã / SĐT)\n";
				$totals['skipped']++;
				continue;
			}

			$existingId = nk_find_contact_id($payload['contact_no'], $payload['phone']);
			if (!$execute) {
				$action = $existingId > 0 ? 'would-update' : 'would-create';
				echo "  row {$sheetRow}: {$action} {$payload['contact_no']} | {$payload['lastname']} | {$payload['phone']} | tags=" . implode(',', $payload['tags']) . "\n";
				if ($existingId > 0) {
					$totals['updated']++;
				} else {
					$totals['imported']++;
				}
				continue;
			}

			if ($existingId > 0) {
				nk_update_contact($existingId, $payload);
				$totals['updated']++;
				echo "  row {$sheetRow}: updated #{$existingId} {$payload['contact_no']}\n";
			} else {
				$id = nk_create_contact($payload);
				$totals['imported']++;
				echo "  row {$sheetRow}: created #{$id} {$payload['contact_no']}\n";
			}
		} catch (Exception $ex) {
			$totals['errors']++;
			echo "  row {$sheetRow}: ERROR " . $ex->getMessage() . "\n";
		}
	}

	$VTIGER_BULK_SAVE_MODE = $prevBulk;
	echo "\n";
}

echo "=== Summary ===\n";
echo 'imported/created: ' . $totals['imported'] . "\n";
echo 'updated: ' . $totals['updated'] . "\n";
echo 'skipped: ' . $totals['skipped'] . "\n";
echo 'errors: ' . $totals['errors'] . "\n";
echo "Done.\n";

// ---------------------------------------------------------------------------

function nk_fold($s) {
	$s = mb_strtolower(trim(decode_html((string) $s)), 'UTF-8');
	$s = str_replace(array('đ', 'Đ'), array('d', 'd'), $s);
	if (function_exists('transliterator_transliterate')) {
		$s = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
	}
	$s = preg_replace('/[^a-z0-9]+/', '_', $s);
	return trim($s, '_');
}

function nk_cell(array $row, array $aliases) {
	foreach ($row as $k => $v) {
		$fk = nk_fold($k);
		foreach ($aliases as $a) {
			if ($fk === $a) {
				return trim((string) $v);
			}
		}
	}
	return '';
}

function nk_normalize_phone($raw) {
	$phone = preg_replace('/\D+/', '', (string) $raw);
	if (strlen($phone) === 9 && preg_match('/^[3-9]/', $phone)) {
		$phone = '0' . $phone;
	}
	if (strlen($phone) > 11) {
		$phone = substr($phone, -10);
	}
	return $phone;
}

function nk_normalize_birthday($raw) {
	$raw = trim((string) $raw);
	if ($raw === '') {
		return '';
	}
	if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
		return substr($raw, 0, 10);
	}
	if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $raw, $m)) {
		return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
	}
	// Excel serial date
	if (is_numeric($raw) && (float) $raw > 20000 && (float) $raw < 80000) {
		$unix = ((int) $raw - 25569) * 86400;
		return gmdate('Y-m-d', $unix);
	}
	return '';
}

function nk_map_customer_type($raw) {
	$f = nk_fold($raw);
	if ($f === 'cong_ty' || $f === 'company' || $f === 'doanh_nghiep' || strpos($f, 'cong_ty') !== false) {
		return 'cong_ty';
	}
	if ($f === 'ca_nhan' || $f === 'individual' || $f === '' || strpos($f, 'ca_nhan') !== false) {
		return 'ca_nhan';
	}
	return 'ca_nhan';
}

function nk_map_kiot_row(array $row, $segmentTag) {
	$code = nk_cell($row, array('ma_khach_hang', 'ma_kh', 'customer_code', 'contact_no'));
	$name = nk_cell($row, array('ten_khach_hang', 'ten', 'ho_ten', 'customer_name', 'name'));
	$phone = nk_normalize_phone(nk_cell($row, array('dien_thoai', 'sdt', 'phone', 'mobile', 'so_dien_thoai')));
	$address = nk_cell($row, array('dia_chi', 'address', 'mailingstreet'));
	$region = nk_cell($row, array('khu_vuc_giao_hang', 'khu_vuc', 'khu_vuc_tinh_thanh', 'tinh_thanh', 'mailingcity'));
	$ward = nk_cell($row, array('phuong_xa', 'ward'));
	$email = nk_cell($row, array('email', 'e_mail', 'mail'));
	$birthday = nk_normalize_birthday(nk_cell($row, array('ngay_sinh', 'birthday', 'birthdate')));
	$gender = nk_cell($row, array('gioi_tinh', 'gender'));
	$note = nk_cell($row, array('ghi_chu', 'note', 'notes', 'description'));
	$typeTag = nk_map_customer_type(nk_cell($row, array('loai_khach', 'loai_khach_hang', 'customer_type')));

	$fullAddress = $address;
	if ($ward !== '') {
		$fullAddress = trim($fullAddress . ($fullAddress !== '' ? ', ' : '') . $ward);
	}

	$descParts = array();
	if ($note !== '') {
		$descParts[] = $note;
	}
	if ($gender !== '') {
		$descParts[] = 'Giới tính: ' . $gender;
	}
	$descParts[] = 'Nguồn import: Excel Kiot (' . $segmentTag . ')';

	$tags = array($typeTag, $segmentTag);

	return array(
		'contact_no' => $code,
		'lastname' => $name !== '' ? $name : ($code !== '' ? $code : ('KH ' . $phone)),
		'firstname' => '',
		'phone' => $phone,
		'mobile' => $phone,
		'email' => $email,
		'mailingstreet' => $fullAddress,
		'mailingcity' => $region,
		'birthday' => $birthday,
		'description' => implode(' | ', $descParts),
		'tags' => $tags,
	);
}

function nk_find_contact_id($contactNo, $phone) {
	global $adb;
	$contactNo = trim((string) $contactNo);
	$phone = trim((string) $phone);
	if ($contactNo !== '') {
		$res = $adb->pquery(
			'SELECT cd.contactid FROM vtiger_contactdetails cd
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
			 WHERE cd.contact_no = ? LIMIT 1',
			array($contactNo)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'contactid');
		}
	}
	if ($phone !== '') {
		$res = $adb->pquery(
			'SELECT cd.contactid FROM vtiger_contactdetails cd
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
			 WHERE REPLACE(REPLACE(REPLACE(cd.phone," ",""),"-",""),".","") = ?
			    OR REPLACE(REPLACE(REPLACE(cd.mobile," ",""),"-",""),".","") = ?
			 LIMIT 1',
			array($phone, $phone)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'contactid');
		}
	}
	return 0;
}

function nk_create_contact(array $payload) {
	global $current_user;
	$record = Vtiger_Record_Model::getCleanInstance('Contacts');
	$record->set('mode', '');
	$record->set('lastname', $payload['lastname']);
	$record->set('firstname', $payload['firstname']);
	$record->set('contact_no', $payload['contact_no']);
	if ($payload['phone'] !== '') {
		$record->set('phone', $payload['phone']);
		$record->set('mobile', $payload['mobile']);
	}
	if ($payload['email'] !== '') {
		$record->set('email', $payload['email']);
	}
	if ($payload['mailingstreet'] !== '') {
		$record->set('mailingstreet', $payload['mailingstreet']);
	}
	if ($payload['mailingcity'] !== '') {
		$record->set('mailingcity', $payload['mailingcity']);
	}
	if ($payload['birthday'] !== '') {
		$record->set('birthday', $payload['birthday']);
	}
	$record->set('description', $payload['description']);
	$record->set('assigned_user_id', (int) $current_user->id);
	$record->save();
	$id = (int) $record->getId();
	if ($id > 0 && !empty($payload['tags'])) {
		Contacts_ModernService::saveTags($id, $payload['tags'], (int) $current_user->id);
	}
	// Ensure contact_no stuck (uitype 4 + bulk mode)
	if ($id > 0 && $payload['contact_no'] !== '') {
		global $adb;
		$adb->pquery(
			'UPDATE vtiger_contactdetails SET contact_no = ? WHERE contactid = ?',
			array($payload['contact_no'], $id)
		);
	}
	return $id;
}

function nk_update_contact($contactId, array $payload) {
	global $current_user, $adb;
	$record = Vtiger_Record_Model::getInstanceById($contactId, 'Contacts');
	$record->set('mode', 'edit');
	$record->set('lastname', $payload['lastname']);
	if ($payload['phone'] !== '') {
		$record->set('phone', $payload['phone']);
		$record->set('mobile', $payload['mobile']);
	}
	if ($payload['email'] !== '') {
		$record->set('email', $payload['email']);
	}
	if ($payload['mailingstreet'] !== '') {
		$record->set('mailingstreet', $payload['mailingstreet']);
	}
	if ($payload['mailingcity'] !== '') {
		$record->set('mailingcity', $payload['mailingcity']);
	}
	if ($payload['birthday'] !== '') {
		$record->set('birthday', $payload['birthday']);
	}
	if ($payload['description'] !== '') {
		$record->set('description', $payload['description']);
	}
	if ($payload['contact_no'] !== '') {
		$record->set('contact_no', $payload['contact_no']);
	}
	$record->save();
	if ($payload['contact_no'] !== '') {
		$adb->pquery(
			'UPDATE vtiger_contactdetails SET contact_no = ? WHERE contactid = ?',
			array($payload['contact_no'], $contactId)
		);
	}
	if (!empty($payload['tags'])) {
		// Merge with existing tags (keep tiến trình / NL care tags)
		$existing = array();
		$res = $adb->pquery(
			"SELECT t.tag FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.object_id = ? AND fo.module = 'Contacts'",
			array($contactId)
		);
		if ($res) {
			for ($i = 0; $i < $adb->num_rows($res); $i++) {
				$existing[] = decode_html((string) $adb->query_result($res, $i, 'tag'));
			}
		}
		$merged = array_values(array_unique(array_merge($existing, $payload['tags'])));
		Contacts_ModernService::saveTags($contactId, $merged, (int) $current_user->id);
	}
}

/**
 * Minimal .xlsx → array of assoc rows (first sheet, header row 1).
 * @return array[]
 */
function nk_xlsx_assoc_rows($path) {
	$zip = new ZipArchive();
	if ($zip->open($path) !== true) {
		throw new Exception('Cannot open xlsx: ' . $path);
	}
	$shared = array();
	if (($idx = $zip->locateName('xl/sharedStrings.xml')) !== false) {
		$xml = simplexml_load_string($zip->getFromIndex($idx));
		$xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
		foreach ($xml->xpath('//m:si') as $si) {
			$texts = $si->xpath('.//m:t');
			$s = '';
			if ($texts) {
				foreach ($texts as $t) {
					$s .= (string) $t;
				}
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
		// Some exports use sheet2.xml only
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
		if ($ref === '') {
			continue;
		}
		if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
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
	if ($maxRow < 2 || $maxCol < 1) {
		return array();
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
