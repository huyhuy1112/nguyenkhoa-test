<?php
/*+***********************************************************************************
 * Import Tuibao franchise Accounts from KiotViet Excel.
 *
 * - account_no = Excel "Mã khách hàng" as-is (TUIBAO_…)
 * - skips: Chi nhánh tạo, Facebook, Nhóm khách hàng
 * - Future CRM creates use prefix Tuibao00001…
 *
 * Dry-run:
 *   php modules/Accounts/scripts/ImportTuibaoFromExcel.php
 * Execute:
 *   php modules/Accounts/scripts/ImportTuibaoFromExcel.php --execute
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
require_once 'modules/Accounts/helpers/FranchiseContractService.php';
require_once 'include/utils/MkEntityNumbering.php';

global $current_user, $adb, $VTIGER_BULK_SAVE_MODE;
$current_user = Users::getActiveAdminUser();
$adb = PearDatabase::getInstance();

$execute = in_array('--execute', $argv, true);
$path = 'input/Khách hàng Tui Bao.xlsx';

MkEntityNumbering::ensureModuleSequence('Accounts');
Accounts_FranchiseContractService_Helper::ensureFranchiseFields();

echo "=== Import Tuibao Accounts ===\n";
echo 'Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN') . "\n";
echo 'Next Account auto-number: ' . MkEntityNumbering::previewNextNumber('Accounts') . "\n\n";

if (!is_file($path)) {
	fwrite(STDERR, "MISSING: {$path}\n");
	exit(1);
}

$rows = tb_xlsx_assoc_rows($path);
echo "File: {$path}\nRows: " . count($rows) . "\n\n";

$totals = array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0);
$prevBulk = isset($VTIGER_BULK_SAVE_MODE) ? $VTIGER_BULK_SAVE_MODE : false;
$VTIGER_BULK_SAVE_MODE = true;

foreach ($rows as $idx => $row) {
	$sheetRow = $idx + 2;
	try {
		$payload = tb_map_row($row);
		if ($payload['accountname'] === '') {
			$totals['skipped']++;
			continue;
		}
		if ($payload['account_no'] === '' && $payload['phone'] === '') {
			echo "  row {$sheetRow}: skip (no mã / SĐT)\n";
			$totals['skipped']++;
			continue;
		}

		$existingId = tb_find_account_id($payload['account_no'], $payload['phone']);
		if (!$execute) {
			$action = $existingId > 0 ? 'would-update' : 'would-create';
			echo "  row {$sheetRow}: {$action} {$payload['account_no']} | {$payload['accountname']} | {$payload['phone']}\n";
			if ($existingId > 0) {
				$totals['updated']++;
			} else {
				$totals['imported']++;
			}
			continue;
		}

		if ($existingId > 0) {
			tb_update_account($existingId, $payload);
			$totals['updated']++;
			echo "  row {$sheetRow}: updated #{$existingId} {$payload['account_no']}\n";
		} else {
			$id = tb_create_account($payload);
			$totals['imported']++;
			echo "  row {$sheetRow}: created #{$id} {$payload['account_no']}\n";
		}
	} catch (Exception $ex) {
		$totals['errors']++;
		echo "  row {$sheetRow}: ERROR " . $ex->getMessage() . "\n";
	}
}

$VTIGER_BULK_SAVE_MODE = $prevBulk;

echo "\n=== Summary ===\n";
echo 'imported/created: ' . $totals['imported'] . "\n";
echo 'updated: ' . $totals['updated'] . "\n";
echo 'skipped: ' . $totals['skipped'] . "\n";
echo 'errors: ' . $totals['errors'] . "\n";
echo "Done.\n";

// ---------------------------------------------------------------------------

function tb_fold($s) {
	$s = mb_strtolower(trim(decode_html((string) $s)), 'UTF-8');
	$s = str_replace(array('đ', 'Đ'), array('d', 'd'), $s);
	if (function_exists('transliterator_transliterate')) {
		$s = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
	}
	$s = preg_replace('/[^a-z0-9]+/', '_', $s);
	return trim($s, '_');
}

function tb_cell(array $row, array $aliases) {
	foreach ($row as $k => $v) {
		$fk = tb_fold($k);
		foreach ($aliases as $a) {
			if ($fk === $a) {
				return trim((string) $v);
			}
		}
	}
	return '';
}

function tb_normalize_phone($raw) {
	$phone = preg_replace('/\D+/', '', (string) $raw);
	if (strlen($phone) === 9 && preg_match('/^[3-9]/', $phone)) {
		$phone = '0' . $phone;
	}
	if (strlen($phone) > 11) {
		$phone = substr($phone, -10);
	}
	return $phone;
}

function tb_map_row(array $row) {
	$code = tb_cell($row, array('ma_khach_hang', 'ma_kh', 'customer_code', 'account_no'));
	$name = tb_cell($row, array('ten_khach_hang', 'ten', 'ho_ten', 'accountname', 'company'));
	$phone = tb_normalize_phone(tb_cell($row, array('dien_thoai', 'sdt', 'phone', 'mobile', 'so_dien_thoai')));
	$address = tb_cell($row, array('dia_chi', 'address', 'bill_street'));
	$region = tb_cell($row, array('khu_vuc_giao_hang', 'khu_vuc', 'tinh_thanh'));
	$ward = tb_cell($row, array('phuong_xa', 'ward'));
	$email = tb_cell($row, array('email', 'e_mail', 'mail'));
	$note = tb_cell($row, array('ghi_chu', 'note', 'notes', 'description'));
	$gender = tb_cell($row, array('gioi_tinh', 'gender'));
	$invoiceBuyer = tb_cell($row, array('ten_nguoi_mua_xuat_hd', 'ten_nguoi_mua'));
	$invoiceCompany = tb_cell($row, array('ten_cong_ty_xuat_hd', 'ten_cong_ty'));
	$invoiceCccd = tb_cell($row, array('so_cccd_cmnd_xuat_hd', 'cccd', 'cmnd'));

	$fullAddress = $address;
	if ($ward !== '') {
		$fullAddress = trim($fullAddress . ($fullAddress !== '' ? ', ' : '') . $ward);
	}
	if ($region !== '') {
		$fullAddress = trim($fullAddress . ($fullAddress !== '' ? ', ' : '') . $region);
	}

	$partyName = $invoiceBuyer !== '' ? $invoiceBuyer : $name;
	if ($invoiceCompany !== '' && $name === '') {
		$name = $invoiceCompany;
	}

	$descParts = array('Nguồn import: Excel Kiot Tuibao');
	if ($note !== '') {
		$descParts[] = $note;
	}
	if ($gender !== '') {
		$descParts[] = 'Giới tính: ' . $gender;
	}

	return array(
		'account_no' => $code,
		'accountname' => $name !== '' ? $name : ($code !== '' ? $code : ('Tuibao ' . $phone)),
		'phone' => $phone,
		'email1' => $email,
		'bill_street' => $fullAddress,
		'tb_store_address' => $fullAddress,
		'tb_party_b_name' => $partyName,
		'tb_party_b_phone' => $phone,
		'tb_party_b_email' => $email,
		'tb_party_b_cccd' => $invoiceCccd,
		'tb_party_b_contact_addr' => $fullAddress,
		'description' => implode(' | ', $descParts),
	);
}

function tb_find_account_id($accountNo, $phone) {
	global $adb;
	$accountNo = trim((string) $accountNo);
	$phone = trim((string) $phone);
	if ($accountNo !== '') {
		$res = $adb->pquery(
			'SELECT a.accountid FROM vtiger_account a
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = a.accountid AND ce.deleted = 0
			 WHERE a.account_no = ? LIMIT 1',
			array($accountNo)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'accountid');
		}
	}
	if ($phone !== '') {
		$res = $adb->pquery(
			'SELECT a.accountid FROM vtiger_account a
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = a.accountid AND ce.deleted = 0
			 LEFT JOIN vtiger_accountscf cf ON cf.accountid = a.accountid
			 WHERE REPLACE(REPLACE(REPLACE(IFNULL(a.phone,\'\'), \' \', \'\'), \'-\', \'\'), \'.\', \'\') = ?
			    OR REPLACE(REPLACE(REPLACE(IFNULL(cf.tb_party_b_phone,\'\'), \' \', \'\'), \'-\', \'\'), \'.\', \'\') = ?
			 LIMIT 1',
			array($phone, $phone)
		);
		if ($res && $adb->num_rows($res) > 0) {
			return (int) $adb->query_result($res, 0, 'accountid');
		}
	}
	return 0;
}

function tb_apply_fields(Vtiger_Record_Model $record, array $payload) {
	$record->set('accountname', $payload['accountname']);
	if ($payload['account_no'] !== '') {
		$record->set('account_no', $payload['account_no']);
	}
	if ($payload['phone'] !== '') {
		$record->set('phone', $payload['phone']);
	}
	if ($payload['email1'] !== '') {
		$record->set('email1', $payload['email1']);
	}
	if ($payload['bill_street'] !== '') {
		$record->set('bill_street', $payload['bill_street']);
	}
	foreach (array(
		'tb_store_address', 'tb_party_b_name', 'tb_party_b_phone', 'tb_party_b_email',
		'tb_party_b_cccd', 'tb_party_b_contact_addr',
	) as $f) {
		if (!empty($payload[$f])) {
			$record->set($f, $payload[$f]);
		}
	}
	$record->set('description', $payload['description']);
}

function tb_create_account(array $payload) {
	global $current_user, $adb;
	Accounts_FranchiseContractService_Helper::ensureFranchiseFields();
	$record = Vtiger_Record_Model::getCleanInstance('Accounts');
	$record->set('mode', '');
	tb_apply_fields($record, $payload);
	$record->set('assigned_user_id', (int) $current_user->id);
	$record->save();
	$id = (int) $record->getId();
	if ($id > 0 && $payload['account_no'] !== '') {
		$adb->pquery(
			'UPDATE vtiger_account SET account_no = ? WHERE accountid = ?',
			array($payload['account_no'], $id)
		);
	}
	return $id;
}

function tb_update_account($accountId, array $payload) {
	global $adb;
	$record = Vtiger_Record_Model::getInstanceById($accountId, 'Accounts');
	$record->set('mode', 'edit');
	tb_apply_fields($record, $payload);
	$record->save();
	if ($payload['account_no'] !== '') {
		$adb->pquery(
			'UPDATE vtiger_account SET account_no = ? WHERE accountid = ?',
			array($payload['account_no'], $accountId)
		);
	}
}

function tb_xlsx_assoc_rows($path) {
	$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
	$zip = new ZipArchive();
	if ($zip->open($path) !== true) {
		throw new Exception('Cannot open xlsx: ' . $path);
	}
	$shared = array();
	$ssXml = $zip->getFromName('xl/sharedStrings.xml');
	if ($ssXml !== false) {
		$xml = simplexml_load_string($ssXml);
		if ($xml !== false) {
			foreach ($xml->children($ns)->si as $si) {
				$s = '';
				foreach ($si->children($ns)->t as $t) {
					$s .= (string) $t;
				}
				foreach ($si->children($ns)->r as $r) {
					foreach ($r->children($ns)->t as $t) {
						$s .= (string) $t;
					}
				}
				if ($s === '') {
					foreach ($si->xpath('.//*[local-name()="t"]') as $t) {
						$s .= (string) $t;
					}
				}
				$shared[] = $s;
			}
		}
	}

	$wb = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
	$rid = '';
	$sheets = $wb->children($ns)->sheets;
	if ($sheets) {
		foreach ($sheets->children($ns)->sheet as $sh) {
			$attrs = $sh->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
			$rid = $attrs ? (string) $attrs->id : '';
			break;
		}
	}
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
	$grid = array();
	$maxRow = 0;
	$maxCol = 0;
	$sheetData = $sx->children($ns)->sheetData;
	if ($sheetData) {
		foreach ($sheetData->children($ns)->row as $rowEl) {
			foreach ($rowEl->children($ns)->c as $c) {
				$attrs = $c->attributes();
				$ref = $attrs && isset($attrs['r']) ? (string) $attrs['r'] : '';
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
				$t = $attrs && isset($attrs['t']) ? (string) $attrs['t'] : '';
				$vChildren = $c->children($ns);
				$raw = isset($vChildren->v) ? (string) $vChildren->v : '';
				if ($t === 's') {
					$i = (int) $raw;
					$val = isset($shared[$i]) ? $shared[$i] : $raw;
				} elseif ($t === 'inlineStr') {
					$val = '';
					if (isset($vChildren->is)) {
						foreach ($vChildren->is->children($ns)->t as $tNode) {
							$val .= (string) $tNode;
						}
					}
				} else {
					$val = $raw;
				}
				$grid[$row][$col] = $val;
			}
		}
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
