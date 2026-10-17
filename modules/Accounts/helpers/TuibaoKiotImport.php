<?php
/*+***********************************************************************************
 * Import KiotViet "Khách hàng Tui Bao.xlsx" into Accounts (danh sách chủ quán).
 * Keeps Excel mã TUIBAO_… as account_no. Does not use intl/transliterator.
 *************************************************************************************/

require_once 'modules/Accounts/helpers/FranchiseContractService.php';
require_once 'include/utils/MkCustomerCode.php';

class Accounts_TuibaoKiotImport_Helper {

	/**
	 * @param array $fileInfo $_FILES item
	 * @param int $userId
	 * @return array|null null when the file is not a Tuibao customer sheet
	 */
	public static function tryImportUpload(array $fileInfo, $userId = 0) {
		$path = isset($fileInfo['tmp_name']) ? (string) $fileInfo['tmp_name'] : '';
		$origName = isset($fileInfo['name']) ? (string) $fileInfo['name'] : '';
		if ($path === '' || !is_file($path)) {
			return null;
		}
		if (!preg_match('/\.xlsx$/i', $origName) && !preg_match('/\.xlsx$/i', $path)) {
			return null;
		}
		$rows = self::xlsxAssocRows($path);
		if (!self::isTuibaoSheet($rows, $origName)) {
			return null;
		}
		return self::importRows($rows, (int) $userId);
	}

	public static function isTuibaoSheet(array $rows, $fileName) {
		$fold = self::fold($fileName);
		if (strpos($fold, 'tuibao') !== false || strpos($fold, 'tui_bao') !== false) {
			return true;
		}
		$sample = isset($rows[0]) ? $rows[0] : array();
		$code = self::cell($sample, array('ma_khach_hang', 'ma_kh', 'customer_code', 'account_no'));
		return stripos(trim($code), 'TUIBAO') === 0;
	}

	public static function importRows(array $rows, $userId) {
		Accounts_FranchiseContractService_Helper::ensureFranchiseFields();
		if ($userId <= 0) {
			global $current_user;
			$userId = isset($current_user->id) ? (int) $current_user->id : 1;
		}

		$stats = array(
			'imported' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors' => 0,
			'total' => count($rows),
		);
		$errorSamples = array();

		global $VTIGER_BULK_SAVE_MODE;
		$prevBulk = isset($VTIGER_BULK_SAVE_MODE) ? $VTIGER_BULK_SAVE_MODE : false;
		$VTIGER_BULK_SAVE_MODE = true;

		foreach ($rows as $idx => $row) {
			$sheetRow = $idx + 2;
			try {
				$payload = self::mapRow($row);
				if ($payload === null) {
					$stats['skipped']++;
					continue;
				}
				$existingId = self::findAccountId($payload['account_no'], $payload['phone']);
				if ($existingId > 0) {
					self::saveAccount($existingId, $payload, $userId, true);
					$stats['updated']++;
				} else {
					self::saveAccount(0, $payload, $userId, false);
					$stats['imported']++;
				}
			} catch (Exception $ex) {
				$stats['errors']++;
				if (count($errorSamples) < 8) {
					$errorSamples[] = 'Dòng ' . $sheetRow . ': ' . $ex->getMessage();
				}
			}
		}

		$VTIGER_BULK_SAVE_MODE = $prevBulk;
		$stats['error_samples'] = $errorSamples;
		$stats['success'] = ($stats['imported'] + $stats['updated']) > 0;
		$stats['message'] = self::buildMessage($stats);
		return $stats;
	}

	public static function buildMessage(array $stats) {
		$imported = (int) $stats['imported'];
		$updated = (int) $stats['updated'];
		$skipped = (int) $stats['skipped'];
		$errors = (int) $stats['errors'];
		$total = (int) $stats['total'];
		if ($imported + $updated <= 0) {
			$msg = 'Import Túi Bao thất bại: 0/' . $total . ' chủ quán được ghi.';
		} else {
			$msg = 'Import Túi Bao xong: tạo ' . $imported . ', cập nhật ' . $updated . ' / ' . $total . ' dòng.';
		}
		if ($skipped > 0) {
			$msg .= ' Bỏ qua ' . $skipped . ' dòng trống.';
		}
		if ($errors > 0) {
			$msg .= ' Lỗi ' . $errors . ' dòng.';
		}
		if (!empty($stats['error_samples'])) {
			$msg .= "\n" . implode("\n", $stats['error_samples']);
		}
		return $msg;
	}

	public static function mapRow(array $row) {
		$code = self::cell($row, array('ma_khach_hang', 'ma_kh', 'customer_code', 'account_no'));
		$name = self::cell($row, array('ten_khach_hang', 'ten', 'ho_ten', 'accountname', 'company'));
		$phone = self::normalizePhone(self::cell($row, array('dien_thoai', 'sdt', 'phone', 'mobile', 'so_dien_thoai')));
		$address = self::cell($row, array('dia_chi', 'address', 'bill_street'));
		$region = self::cell($row, array('khu_vuc_giao_hang', 'khu_vuc', 'tinh_thanh'));
		$ward = self::cell($row, array('phuong_xa', 'ward'));
		$email = self::cell($row, array('email', 'e_mail', 'mail'));
		$note = self::cell($row, array('ghi_chu', 'note', 'notes', 'description'));
		$gender = self::cell($row, array('gioi_tinh', 'gender'));
		$invoiceBuyer = self::cell($row, array('ten_nguoi_mua_xuat_hd', 'ten_nguoi_mua'));
		$invoiceCompany = self::cell($row, array('ten_cong_ty_xuat_hd', 'ten_cong_ty'));
		$invoiceCccd = self::cell($row, array('so_cccd_cmnd_xuat_hd', 'cccd', 'cmnd'));

		if ($name === '' && $code === '' && $phone === '') {
			return null;
		}

		$fullAddress = $address;
		if ($ward !== '') {
			$fullAddress = trim($fullAddress . ($fullAddress !== '' ? ', ' : '') . $ward);
		}
		if ($region !== '') {
			$fullAddress = trim($fullAddress . ($fullAddress !== '' ? ', ' : '') . $region);
		}

		if ($invoiceCompany !== '' && $name === '') {
			$name = $invoiceCompany;
		}
		$displayName = $name !== '' ? $name : ($code !== '' ? $code : ('Tuibao ' . $phone));
		$accountNo = $code;
		if (!preg_match('/^TUIBAO/i', $accountNo)) {
			$accountNo = MkCustomerCode::accountCode($displayName, $phone);
		}

		$descParts = array('Nguồn import: Excel Kiot Tuibao');
		if ($note !== '') {
			$descParts[] = $note;
		}
		if ($gender !== '') {
			$descParts[] = 'Giới tính: ' . $gender;
		}

		return array(
			'account_no' => $accountNo,
			'accountname' => $displayName,
			'phone' => $phone,
			'email1' => $email,
			'bill_street' => $fullAddress,
			'tb_store_address' => $fullAddress,
			'tb_party_b_name' => $invoiceBuyer !== '' ? $invoiceBuyer : $displayName,
			'tb_party_b_phone' => $phone,
			'tb_party_b_email' => $email,
			'tb_party_b_cccd' => $invoiceCccd,
			'tb_party_b_contact_addr' => $fullAddress,
			'description' => implode(' | ', $descParts),
		);
	}

	protected static function saveAccount($accountId, array $payload, $userId, $isUpdate) {
		global $adb;
		if ($isUpdate) {
			$record = Vtiger_Record_Model::getInstanceById($accountId, 'Accounts');
			$record->set('mode', 'edit');
		} else {
			$record = Vtiger_Record_Model::getCleanInstance('Accounts');
			$record->set('mode', '');
			$record->set('assigned_user_id', (int) $userId);
		}
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
		$record->set('description', $payload['description']);
		foreach (array(
			'tb_store_address', 'tb_party_b_name', 'tb_party_b_phone', 'tb_party_b_email',
			'tb_party_b_cccd', 'tb_party_b_contact_addr',
		) as $field) {
			if (!empty($payload[$field])) {
				$record->set($field, $payload[$field]);
			}
		}
		$record->save();
		$id = (int) $record->getId();
		if ($id > 0 && $payload['account_no'] !== '') {
			$adb->pquery(
				'UPDATE vtiger_account SET account_no = ? WHERE accountid = ?',
				array($payload['account_no'], $id)
			);
		}
		if ($id <= 0) {
			throw new Exception('Không lưu được chủ quán ' . $payload['accountname']);
		}
	}

	protected static function findAccountId($accountNo, $phone) {
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
				 WHERE REPLACE(REPLACE(REPLACE(IFNULL(a.phone,\'\'), \' \', \'\'), \'-\', \'\'), \'.\', \'\') = ?
				 LIMIT 1',
				array($phone)
			);
			if ($res && $adb->num_rows($res) > 0) {
				return (int) $adb->query_result($res, 0, 'accountid');
			}
		}
		return 0;
	}

	public static function xlsxAssocRows($path) {
		$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
		$zip = new ZipArchive();
		if ($zip->open($path) !== true) {
			throw new Exception('Không mở được file Excel.');
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
					$shared[] = $s;
				}
			}
		}
		$target = 'xl/worksheets/sheet1.xml';
		if ($zip->locateName($target) === false) {
			for ($i = 1; $i <= 8; $i++) {
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
			throw new Exception('File Excel không có sheet.');
		}
		$sx = simplexml_load_string($sheetXml);
		$grid = array();
		$maxRow = 0;
		$maxCol = 0;
		$sheetData = $sx ? $sx->children($ns)->sheetData : null;
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
					$rowNum = (int) $m[2];
					$maxRow = max($maxRow, $rowNum);
					$maxCol = max($maxCol, $col);
					$t = $attrs && isset($attrs['t']) ? (string) $attrs['t'] : '';
					$vChildren = $c->children($ns);
					$raw = isset($vChildren->v) ? (string) $vChildren->v : '';
					if ($t === 's') {
						$i = (int) $raw;
						$val = isset($shared[$i]) ? $shared[$i] : $raw;
					} else {
						$val = $raw;
					}
					$grid[$rowNum][$col] = $val;
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

	protected static function normalizePhone($raw) {
		$phone = preg_replace('/\D+/', '', (string) $raw);
		if (strlen($phone) === 9 && preg_match('/^[3-9]/', $phone)) {
			$phone = '0' . $phone;
		}
		if (strlen($phone) > 11) {
			$phone = substr($phone, 0, 11);
		}
		return $phone;
	}

	protected static function cell(array $row, array $aliases) {
		foreach ($row as $key => $value) {
			$folded = self::fold($key);
			foreach ($aliases as $alias) {
				if ($folded === $alias) {
					return trim((string) $value);
				}
			}
		}
		return '';
	}

	protected static function fold($value) {
		$value = (string) $value;
		if (function_exists('decode_html')) {
			$value = decode_html($value);
		}
		$value = mb_strtolower(trim($value), 'UTF-8');
		$value = self::stripVietnamese($value);
		$value = preg_replace('/[^a-z0-9]+/', '_', $value);
		return trim($value, '_');
	}

	protected static function stripVietnamese($value) {
		static $map = null;
		if ($map === null) {
			$map = array(
				'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
				'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
				'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
				'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
				'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
				'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
				'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
				'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
				'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
				'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
				'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
				'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
				'đ' => 'd',
			);
		}
		return strtr($value, $map);
	}
}
