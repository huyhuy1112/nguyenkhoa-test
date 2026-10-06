<?php
/*+***********************************************************************************
 * One-step Contacts import from KiotViet Excel (Khách lẻ / Miutea).
 *************************************************************************************/

require_once 'modules/Contacts/models/ModernService.php';
require_once 'modules/Contacts/helpers/ContactTagCatalog.php';
require_once 'include/utils/MkCustomerCode.php';

class Contacts_KiotSimpleImport_Helper {

	public static function importUploadedFile(array $fileInfo, $userId = 0) {
		$path = isset($fileInfo['tmp_name']) ? (string) $fileInfo['tmp_name'] : '';
		$origName = isset($fileInfo['name']) ? (string) $fileInfo['name'] : '';
		if ($path === '' || !is_uploaded_file($path)) {
			// allow local path for tests
			if ($path === '' || !is_file($path)) {
				throw new Exception('Chưa chọn file Excel (.xlsx).');
			}
		}
		$nameLower = mb_strtolower($origName, 'UTF-8');
		if (!preg_match('/\.xlsx$/i', $nameLower) && !preg_match('/\.xlsx$/i', $path)) {
			throw new Exception('Chỉ hỗ trợ file .xlsx (Khách lẻ / Miutea).');
		}

		$rows = self::xlsxAssocRows($path);
		if (empty($rows)) {
			throw new Exception('File không có dòng dữ liệu (cần header + ít nhất 1 dòng).');
		}

		$segment = self::detectSegmentTag($rows, $origName);
		$userId = (int) $userId;
		if ($userId <= 0) {
			global $current_user;
			$userId = isset($current_user->id) ? (int) $current_user->id : 0;
		}

		$stats = array(
			'imported' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors' => 0,
			'total' => count($rows),
			'segment' => $segment,
		);
		$errorSamples = array();

		global $VTIGER_BULK_SAVE_MODE;
		$prevBulk = isset($VTIGER_BULK_SAVE_MODE) ? $VTIGER_BULK_SAVE_MODE : false;
		$VTIGER_BULK_SAVE_MODE = true;

		foreach ($rows as $idx => $row) {
			$sheetRow = $idx + 2;
			try {
				$payload = self::mapKiotRow($row, $segment);
				if ($payload['lastname'] === '') {
					$stats['skipped']++;
					continue;
				}
				if ($payload['contact_no'] === '' && $payload['phone'] === '') {
					$stats['skipped']++;
					continue;
				}
				$existingId = self::findContactId($payload['contact_no'], $payload['phone']);
				if ($existingId > 0) {
					self::updateContact($existingId, $payload, $userId);
					$stats['updated']++;
				} else {
					self::createContact($payload, $userId);
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
		$stats['message'] = self::buildResultMessage($stats);
		$stats['success'] = ($stats['imported'] + $stats['updated']) > 0;
		return $stats;
	}

	public static function buildResultMessage(array $stats) {
		$msg = sprintf(
			'Import Contacts xong.\n- Dòng Excel: %d\n- Tạo mới: %d\n- Cập nhật: %d\n- Bỏ qua: %d\n- Lỗi: %d\n- Nhóm: %s',
			(int) $stats['total'],
			(int) $stats['imported'],
			(int) $stats['updated'],
			(int) $stats['skipped'],
			(int) $stats['errors'],
			$stats['segment'] === 'miutea' ? 'Miutea' : 'Khách lẻ'
		);
		if (!empty($stats['error_samples'])) {
			$msg .= "\n\nLỗi mẫu:\n- " . implode("\n- ", $stats['error_samples']);
		}
		return $msg;
	}

	protected static function detectSegmentTag(array $rows, $fileName) {
		$fileFold = self::fold($fileName);
		if (strpos($fileFold, 'miutea') !== false) {
			return 'miutea';
		}
		if (strpos($fileFold, 'khach_le') !== false || strpos($fileFold, 'khachle') !== false) {
			return 'khach_le';
		}
		$sample = isset($rows[0]) ? $rows[0] : array();
		$group = self::cell($sample, array('nhom_khach_hang', 'nhom_kh', 'customer_group', 'group'));
		$g = self::fold($group);
		if (strpos($g, 'miutea') !== false) {
			return 'miutea';
		}
		$code = self::cell($sample, array('ma_khach_hang', 'ma_kh', 'customer_code', 'contact_no'));
		$c = self::fold($code);
		if (strpos($c, 'miutea') === 0) {
			return 'miutea';
		}
		return 'khach_le';
	}

	protected static function fold($s) {
		$s = (string) $s;
		if (function_exists('decode_html')) {
			$s = decode_html($s);
		}
		$s = mb_strtolower(trim($s), 'UTF-8');
		$s = str_replace(array('đ', 'Đ'), array('d', 'd'), $s);
		if (function_exists('transliterator_transliterate')) {
			$s = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
		}
		$s = preg_replace('/[^a-z0-9]+/', '_', $s);
		return trim($s, '_');
	}

	protected static function cell(array $row, array $aliases) {
		foreach ($row as $k => $v) {
			$fk = self::fold($k);
			foreach ($aliases as $a) {
				if ($fk === $a) {
					return trim((string) $v);
				}
			}
		}
		return '';
	}

	protected static function normalizePhone($raw) {
		$phone = preg_replace('/\D+/', '', (string) $raw);
		if (strlen($phone) === 9 && preg_match('/^[3-9]/', $phone)) {
			$phone = '0' . $phone;
		}
		if (strlen($phone) > 11) {
			$phone = substr($phone, -10);
		}
		return $phone;
	}

	protected static function normalizeBirthday($raw) {
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
		if (is_numeric($raw) && (float) $raw > 20000 && (float) $raw < 80000) {
			$unix = ((int) $raw - 25569) * 86400;
			return gmdate('Y-m-d', $unix);
		}
		return '';
	}

	protected static function mapCustomerType($raw) {
		$f = self::fold($raw);
		if ($f === 'cong_ty' || $f === 'company' || $f === 'doanh_nghiep' || strpos($f, 'cong_ty') !== false) {
			return 'cong_ty';
		}
		return 'ca_nhan';
	}

	public static function mapKiotRow(array $row, $segmentTag) {
		$code = self::cell($row, array('ma_khach_hang', 'ma_kh', 'customer_code', 'contact_no'));
		$name = self::cell($row, array('ten_khach_hang', 'ten', 'ho_ten', 'customer_name', 'name'));
		$phone = self::normalizePhone(self::cell($row, array('dien_thoai', 'sdt', 'phone', 'mobile', 'so_dien_thoai')));
		$address = self::cell($row, array('dia_chi', 'address', 'mailingstreet'));
		$region = self::cell($row, array('khu_vuc_giao_hang', 'khu_vuc', 'khu_vuc_tinh_thanh', 'tinh_thanh', 'mailingcity'));
		$ward = self::cell($row, array('phuong_xa', 'ward'));
		$email = self::cell($row, array('email', 'e_mail', 'mail'));
		$birthday = self::normalizeBirthday(self::cell($row, array('ngay_sinh', 'birthday', 'birthdate')));
		$gender = self::cell($row, array('gioi_tinh', 'gender'));
		$note = self::cell($row, array('ghi_chu', 'note', 'notes', 'description'));
		$typeTag = self::mapCustomerType(self::cell($row, array('loai_khach', 'loai_khach_hang', 'customer_type')));

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

		$displayName = $name !== '' ? $name : ($code !== '' ? $code : ('KH ' . $phone));
		$contactNo = MkCustomerCode::contactCode($code, $displayName, $phone);

		return array(
			'contact_no' => $contactNo,
			'lastname' => $name !== '' ? $name : ($code !== '' ? $code : ('KH ' . $phone)),
			'firstname' => '',
			'phone' => $phone,
			'mobile' => $phone,
			'email' => $email,
			'mailingstreet' => $fullAddress,
			'mailingcity' => $region,
			'birthday' => $birthday,
			'description' => implode(' | ', $descParts),
			'tags' => array($typeTag, $segmentTag),
		);
	}

	protected static function findContactId($contactNo, $phone) {
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

	protected static function createContact(array $payload, $userId) {
		global $adb;
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
		$record->set('assigned_user_id', $userId > 0 ? $userId : 1);
		$record->save();
		$id = (int) $record->getId();
		if ($id > 0 && !empty($payload['tags'])) {
			Contacts_ModernService::saveTags($id, $payload['tags'], $userId > 0 ? $userId : 1);
		}
		if ($id > 0 && $payload['contact_no'] !== '') {
			$adb->pquery(
				'UPDATE vtiger_contactdetails SET contact_no = ? WHERE contactid = ?',
				array($payload['contact_no'], $id)
			);
		}
		return $id;
	}

	protected static function updateContact($contactId, array $payload, $userId) {
		global $adb;
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
			Contacts_ModernService::saveTags($contactId, $merged, $userId > 0 ? $userId : 1);
		}
	}

	/**
	 * Pick the worksheet that has Kiot headers (may not be sheet1).
	 */
	public static function xlsxAssocRows($path) {
		$zip = new ZipArchive();
		if ($zip->open($path) !== true) {
			throw new Exception('Không mở được file Excel.');
		}
		$shared = array();
		if (($idx = $zip->locateName('xl/sharedStrings.xml')) !== false) {
			$xml = @simplexml_load_string($zip->getFromIndex($idx));
			if ($xml) {
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
		}

		$candidates = array();
		for ($i = 1; $i <= 8; $i++) {
			$cand = "xl/worksheets/sheet{$i}.xml";
			if ($zip->locateName($cand) !== false) {
				$candidates[] = $cand;
			}
		}
		if (empty($candidates)) {
			$zip->close();
			throw new Exception('File Excel không có sheet.');
		}

		$best = array();
		$bestScore = -1;
		foreach ($candidates as $target) {
			$sheetXml = $zip->getFromName($target);
			if ($sheetXml === false) {
				continue;
			}
			$rows = self::parseSheetXml($sheetXml, $shared);
			$score = count($rows);
			if (!empty($rows[0])) {
				$headerBlob = self::fold(implode(' ', array_keys($rows[0])));
				if (strpos($headerBlob, 'ma_khach_hang') !== false || strpos($headerBlob, 'ten_khach_hang') !== false) {
					$score += 10000;
				}
			}
			if ($score > $bestScore) {
				$bestScore = $score;
				$best = $rows;
			}
		}
		$zip->close();
		return $best;
	}

	protected static function parseSheetXml($sheetXml, array $shared) {
		$sx = @simplexml_load_string($sheetXml);
		if (!$sx) {
			return array();
		}
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
}
