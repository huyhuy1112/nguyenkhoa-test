<?php
/*+***********************************************************************************
 * Import Excel bộ tiêu chuẩn câu hỏi (GD11 / GD14 / GD12) → CRM question banks.
 * File mẫu: "GD* - Tieu chuan*.xlsx" (sheets 02_Bo_* + 03_Quy_tac).
 *************************************************************************************/

require_once 'modules/Leads/models/SalesVerifyService.php';

class Leads_QuestionBankExcelImportService {

	/**
	 * @param string $filePath uploaded tmp path (.xlsx)
	 * @param string $forcedType ''|screening|gd14|gd12
	 * @param bool $apply write to DB
	 * @return array
	 */
	public static function importFromUpload($filePath, $forcedType = '', $apply = true) {
		$filePath = (string) $filePath;
		if ($filePath === '' || !is_file($filePath)) {
			throw new Exception('Không tìm thấy file Excel.');
		}
		$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
		if ($ext !== 'xlsx') {
			throw new Exception('Chỉ hỗ trợ file .xlsx (bộ tiêu chuẩn giai đoạn).');
		}
		$wb = self::openWorkbook($filePath);
		$type = $forcedType !== '' ? strtolower(trim($forcedType)) : self::detectBankType($wb);
		if (!in_array($type, array('screening', 'gd14', 'gd12'), true)) {
			throw new Exception('Không nhận diện được loại file. Cần sheet 02_Bo_3_cau_hoi / 02_Bo_4_cau_hoi và (tuỳ chọn) 03_Quy_tac / 04_Ma_tran_*.');
		}

		if ($type === 'screening') {
			$bank = self::parseScreeningBank($wb);
			$out = array(
				'success' => true,
				'bank_type' => 'screening',
				'label' => 'GD11 — Sàng lọc 3 câu',
				'questions_count' => count($bank['questions']),
				'levels_count' => count($bank['levels']),
				'preview' => $bank,
			);
			if ($apply) {
				$saved = Leads_SalesVerifyService::saveScreeningBank($bank);
				$out['applied'] = true;
				$out['screening_bank'] = $saved;
				$out['message'] = 'Đã nạp bộ sàng lọc GD11 vào CRM (' . count($saved['questions']) . ' câu, '
					. count($saved['levels']) . ' mức).';
			} else {
				$out['applied'] = false;
				$out['message'] = 'Đã đọc file GD11 (chưa lưu).';
			}
			return $out;
		}

		if ($type === 'gd14') {
			$bank = self::parseGd14Bank($wb);
			$out = array(
				'success' => true,
				'bank_type' => 'gd14',
				'label' => 'GD14 — Câu hỏi 990k',
				'questions_count' => count($bank['questions']),
				'results_count' => count($bank['results']),
				'preview' => $bank,
			);
			if ($apply) {
				$saved = Leads_SalesVerifyService::saveGd14QuestionBank($bank);
				$out['applied'] = true;
				$out['gd14_questions'] = $saved;
				$out['message'] = 'Đã nạp bộ câu 990k (GD14) vào CRM (' . count($saved['questions']) . ' câu, '
					. count($saved['results']) . ' dòng kết quả).';
			} else {
				$out['applied'] = false;
				$out['message'] = 'Đã đọc file GD14 (chưa lưu).';
			}
			return $out;
		}

		// GD12 — đọc preview; logic chấm Online vẫn trong OnlineGd12Service (không dump ma trận 700).
		$questions = self::parseGenericQuestions($wb, true);
		$out = array(
			'success' => true,
			'bank_type' => 'gd12',
			'label' => 'GD12 — Bộ 4 câu Online',
			'questions_count' => count($questions),
			'preview' => array('questions' => $questions),
			'applied' => false,
			'message' => 'Đã đọc bộ 4 câu GD12 (' . count($questions) . ' câu). '
				. 'CRM Online hiện chấm theo công thức trong code (không nạp nguyên ma trận 700). '
				. 'Dùng file này để đối chiếu / cập nhật tay nếu cần.',
		);
		return $out;
	}

	protected static function detectBankType(array $wb) {
		$names = array();
		foreach ($wb['sheets'] as $sh) {
			$names[] = self::fold(isset($sh['name']) ? $sh['name'] : '');
		}
		$joined = implode(' | ', $names);
		if (strpos($joined, 'ma_tran_80') !== false || strpos($joined, 'ma tran 80') !== false) {
			return 'gd14';
		}
		if (strpos($joined, 'ma_tran_36') !== false || strpos($joined, 'ma tran 36') !== false) {
			return 'screening';
		}
		if (strpos($joined, 'ma_tran_700') !== false || strpos($joined, 'bo_4_cau') !== false || strpos($joined, 'bo 4 cau') !== false) {
			return 'gd12';
		}
		// Fallback: inspect guide / question sheet title
		foreach ($wb['sheets'] as $sh) {
			$rows = self::sheetRows($sh, $wb['shared']);
			$title = isset($rows[1][1]) ? self::fold($rows[1][1]) : '';
			if (strpos($title, '990') !== false || strpos($title, 'giai doan 1.4') !== false || strpos($title, 'chan combo') !== false) {
				return 'gd14';
			}
			if (strpos($title, 'sang loc') !== false || strpos($title, 'giai doan 1.1') !== false || strpos($title, 'phan nhom') !== false) {
				return 'screening';
			}
			if (strpos($title, 'bon cau') !== false || strpos($title, '4 cau') !== false || strpos($title, 'giai doan 1.2') !== false) {
				return 'gd12';
			}
		}
		// Sheet name Bo_3 + uppercase codes A/B → screening; lowercase a/b → gd14
		$bo = self::findSheet($wb, array('02_bo_3', 'bo_3_cau', 'bo 3 cau'));
		if ($bo) {
			$rows = self::sheetRows($bo, $wb['shared']);
			foreach ($rows as $cells) {
				$code = isset($cells[1]) ? trim((string) $cells[1]) : '';
				if ($code === 'a' || $code === 'b') {
					return 'gd14';
				}
				if ($code === 'A' || $code === 'B') {
					return 'screening';
				}
			}
			return 'screening';
		}
		if (self::findSheet($wb, array('02_bo_4', 'bo_4_cau', 'bo 4 cau'))) {
			return 'gd12';
		}
		return '';
	}

	protected static function parseScreeningBank(array $wb) {
		$questions = self::parseGenericQuestions($wb, false);
		if (count($questions) < 3) {
			throw new Exception('File GD11 thiếu bộ 3 câu (sheet 02_Bo_3_cau_hoi).');
		}
		// Normalize ids c1..c3
		$mapped = array();
		foreach (array_values($questions) as $i => $q) {
			$id = 'c' . ($i + 1);
			$mapped[] = array(
				'id' => $id,
				'label' => $q['label'],
				'required' => true,
				'active' => true,
				'core' => $i < 3,
				'options' => $q['options'],
			);
		}
		$levels = self::parseScreeningLevels($wb);
		if (empty($levels)) {
			$levels = Leads_SalesVerifyService::defaultScreeningBank();
			$levels = $levels['levels'];
		}
		return array('questions' => $mapped, 'levels' => $levels);
	}

	protected static function parseScreeningLevels(array $wb) {
		$sh = self::findSheet($wb, array('03_quy_tac', 'quy_tac', 'quy tac'));
		if (!$sh) {
			return array();
		}
		$rows = self::sheetRows($sh, $wb['shared']);
		$levels = array();
		$prio = 10;
		foreach ($rows as $r => $cells) {
			// Sheet 03: A=Bước B=Điều kiện C=Kết luận D=Mức độ tiềm năng
			$b = isset($cells[2]) ? trim((string) $cells[2]) : '';
			$c = isset($cells[3]) ? trim((string) $cells[3]) : '';
			$d = isset($cells[4]) ? trim((string) $cells[4]) : '';
			if ($b === '' || $d === '') {
				continue;
			}
			$fb = self::fold($b);
			$fc = self::fold($c);
			$fd = self::fold($d);
			if ($fb === 'dieu kien' || $fb === 'buoc' || $fd === 'muc do tiem nang' || strpos($fb, 'truong hop') !== false) {
				continue;
			}
			$code = '';
			$label = $d;
			$when = array();
			// Prefer cột D (mức độ) — tránh "dưới 500" trong điều kiện bị nhầm Siêu
			if ($fd === 'khong du dieu kien' || strpos($fd, 'khong du') !== false
				|| strpos($fc, 'khong du') !== false || (strpos($fb, 'loai') !== false && strpos($fd, 'binh') === false)) {
				$code = 'khong_du_dk';
				$label = 'Không đủ điều kiện';
				$when = array(array('q' => 'c1', 'op' => 'eq', 'value' => 'C'));
			} elseif (strpos($fd, 'sieu') !== false) {
				$code = 'sieu_tiem_nang';
				$label = 'Siêu tiềm năng';
				$when = array(
					array('q' => 'c1', 'op' => 'in', 'value' => 'A,B'),
					array('q' => 'c3', 'op' => 'eq', 'value' => 'F'),
				);
			} elseif (strpos($fd, 'tiem nang') !== false) {
				$code = 'tiem_nang';
				$label = 'Tiềm năng';
				$when = array(
					array('q' => 'c1', 'op' => 'in', 'value' => 'A,B'),
					array('q' => 'c2', 'op' => 'eq', 'value' => 'A'),
				);
			} elseif (strpos($fd, 'binh thuong') !== false || strpos($fb, 'con lai') !== false) {
				$code = 'binh_thuong';
				$label = 'Bình thường';
				$when = array(array('q' => 'c1', 'op' => 'in', 'value' => 'A,B'));
			} else {
				continue;
			}
			$exists = false;
			foreach ($levels as $lv) {
				if ($lv['code'] === $code) {
					$exists = true;
					break;
				}
			}
			if ($exists) {
				continue;
			}
			$levels[] = array(
				'code' => $code,
				'label' => $label,
				'priority' => $prio,
				'when' => $when,
			);
			$prio += 10;
		}
		return $levels;
	}

	protected static function parseGd14Bank(array $wb) {
		$questions = self::parseGenericQuestions($wb, false);
		if (count($questions) < 3) {
			throw new Exception('File GD14 thiếu bộ 3 câu (sheet 02_Bo_3_cau_hoi).');
		}
		$mapped = array();
		foreach (array_values($questions) as $i => $q) {
			$opts = array();
			foreach ($q['options'] as $opt) {
				$opts[] = array(
					'code' => strtolower($opt['code']),
					'label' => $opt['label'],
				);
			}
			$mapped[] = array(
				'id' => 'c' . ($i + 1),
				'label' => $q['label'],
				'options' => $opts,
			);
		}
		$results = self::parseGd14Results($wb, $questions);
		if (empty($results)) {
			$def = Leads_SalesVerifyService::defaultGd14QuestionBank();
			$results = $def['results'];
		}
		return array('questions' => $mapped, 'results' => $results);
	}

	protected static function parseGd14Results(array $wb, array $questions) {
		$results = array();
		$prio = array('chan_moi' => 10, 'loi_tu_van' => 10, 'mau_thuan' => 10);

		// From question options: column meta if parsed
		foreach ($questions as $qi => $q) {
			$qid = 'c' . ($qi + 1);
			foreach ($q['options'] as $opt) {
				$code = strtolower($opt['code']);
				if (!empty($opt['block'])) {
					$results[] = array(
						'group' => 'chan_moi',
						'label' => 'Không mời Combo và Mở quán bài bản — ' . $opt['block'],
						'priority' => $prio['chan_moi'],
						'when' => array(array('q' => $qid, 'op' => 'eq', 'value' => $code)),
					);
					$prio['chan_moi'] += 10;
				}
				if (!empty($opt['variant'])) {
					$results[] = array(
						'group' => 'loi_tu_van',
						'label' => 'Lời tư vấn: ' . $opt['variant'],
						'priority' => $prio['loi_tu_van'],
						'when' => array(array('q' => $qid, 'op' => 'eq', 'value' => $code)),
					);
					$prio['loi_tu_van'] += 10;
				}
			}
		}

		// Quy tac sheet — explicit block conditions
		$sh = self::findSheet($wb, array('03_quy_tac', 'quy_tac'));
		if ($sh) {
			$rows = self::sheetRows($sh, $wb['shared']);
			foreach ($rows as $cells) {
				$cond = isset($cells[3]) ? trim((string) $cells[3]) : ''; // Căn cứ
				$label = isset($cells[2]) ? trim((string) $cells[2]) : '';
				if ($cond === '' || $label === '') {
					continue;
				}
				$when = self::parseGd14ConditionText($cond);
				if (empty($when)) {
					continue;
				}
				$results[] = array(
					'group' => 'chan_moi',
					'label' => 'Không mời Combo và Mở quán bài bản — ' . $label,
					'priority' => $prio['chan_moi'],
					'when' => $when,
				);
				$prio['chan_moi'] += 10;
			}
		}

		// Conflict rules (C1 hobby vs C2 not hobby)
		$results[] = array(
			'group' => 'mau_thuan',
			'label' => 'Hỏi lại câu 1 và câu 2 vì một bên là sở thích, bên kia không phải',
			'priority' => 10,
			'when' => array(
				array('q' => 'c1', 'op' => 'eq', 'value' => 'd'),
				array('q' => 'c2', 'op' => 'neq', 'value' => 'e'),
			),
		);
		$results[] = array(
			'group' => 'mau_thuan',
			'label' => 'Hỏi lại câu 1 và câu 2 vì một bên là sở thích, bên kia không phải',
			'priority' => 20,
			'when' => array(
				array('q' => 'c1', 'op' => 'neq', 'value' => 'd'),
				array('q' => 'c2', 'op' => 'eq', 'value' => 'e'),
			),
		);

		return $results;
	}

	/**
	 * Parse "Câu 2 = a" / "Câu 1 = d, hoặc Câu 2 = e"
	 */
	protected static function parseGd14ConditionText($text) {
		$text = trim((string) $text);
		if ($text === '') {
			return array();
		}
		// Only handle single equality for quay tac rows (compound OR split separately)
		if (preg_match_all('/c[aâ]u\s*(\d)\s*=\s*([a-z])/iu', $text, $m, PREG_SET_ORDER)) {
			$when = array();
			foreach ($m as $match) {
				$when[] = array(
					'q' => 'c' . $match[1],
					'op' => 'eq',
					'value' => strtolower($match[2]),
				);
			}
			// "hoặc" → for chan_moi we emit separate rules; here if multiple with hoặc, use first only
			// Caller creates one row per condition line typically "Câu 2 = a"
			if (count($when) === 1) {
				return $when;
			}
			if (preg_match('/ho[aặ]c/iu', $text) && count($when) >= 2) {
				// Return first; second handled if another row exists. Also add as 'in' if same q.
				$byQ = array();
				foreach ($when as $w) {
					$byQ[$w['q']][] = $w['value'];
				}
				$out = array();
				foreach ($byQ as $q => $vals) {
					$vals = array_values(array_unique($vals));
					if (count($vals) === 1) {
						$out[] = array('q' => $q, 'op' => 'eq', 'value' => $vals[0]);
					} else {
						$out[] = array('q' => $q, 'op' => 'in', 'value' => implode(',', $vals));
					}
				}
				// OR across different questions can't be one when-AND group — pick first clause only
				if (count($byQ) > 1) {
					$first = $when[0];
					return array($first);
				}
				return $out;
			}
			return $when;
		}
		return array();
	}

	/**
	 * @param array $wb
	 * @param bool $allowFour
	 * @return array list of {label, options:[{code,label,block?,variant?}]}
	 */
	protected static function parseGenericQuestions(array $wb, $allowFour) {
		$sh = self::findSheet($wb, array('02_bo_3', '02_bo_4', 'bo_3_cau', 'bo_4_cau', 'bo 3 cau', 'bo 4 cau'));
		if (!$sh) {
			throw new Exception('Không tìm thấy sheet bộ câu hỏi (02_Bo_*_cau_hoi).');
		}
		$rows = self::sheetRows($sh, $wb['shared']);
		$questions = array();
		$current = null;
		$maxQ = $allowFour ? 4 : 3;

		foreach ($rows as $r => $cells) {
			$a = isset($cells[1]) ? trim((string) $cells[1]) : '';
			$b = isset($cells[2]) ? trim((string) $cells[2]) : '';
			$d = isset($cells[4]) ? trim((string) $cells[4]) : '';
			$e = isset($cells[5]) ? trim((string) $cells[5]) : '';
			$fa = self::fold($a);

			if (preg_match('/^cau\s*(\d)/u', $fa, $m) || preg_match('/^câu\s*(\d)/u', mb_strtolower($a, 'UTF-8'), $m2)) {
				if ($current && !empty($current['options'])) {
					$questions[] = $current;
				}
				$num = isset($m[1]) ? (int) $m[1] : (isset($m2[1]) ? (int) $m2[1] : 0);
				if ($num > $maxQ) {
					$current = null;
					continue;
				}
				$label = $a;
				// Strip "CÂU N." / "CÂU N —" prefix
				$label = preg_replace('/^CÂU\s*\d+\s*[.\—\-–:]+\s*/u', '', $label);
				$label = preg_replace('/^Cau\s*\d+\s*[.\—\-–:]+\s*/iu', '', $label);
				$label = trim($label);
				$current = array(
					'label' => $label !== '' ? $label : ('Câu ' . $num),
					'options' => array(),
				);
				continue;
			}

			if (!$current) {
				continue;
			}
			// Option row: code in col A
			if (preg_match('/^[A-Za-z]$/', $a) && $b !== '') {
				$opt = array(
					'code' => $a,
					'label' => $b,
				);
				if ($d !== '' && self::fold($d) !== 'chan combo / mo quan' && self::fold($d) !== 'chan') {
					// block reason (GD14)
					if (self::fold($d) !== '' && self::fold($d) !== 'noi dung dap an') {
						$opt['block'] = $d;
					}
				}
				if ($e !== '' && self::fold($e) !== 'bien the loi tu van' && self::fold($e) !== 'ghi chu') {
					$opt['variant'] = $e;
				}
				// Clear block if header-like
				if (!empty($opt['block']) && (self::fold($opt['block']) === 'nhom khach hang' || self::fold($opt['block']) === 'mo hinh kinh doanh')) {
					unset($opt['block']);
				}
				$current['options'][] = $opt;
			}
		}
		if ($current && !empty($current['options'])) {
			$questions[] = $current;
		}
		return $questions;
	}

	protected static function findSheet(array $wb, array $needles) {
		foreach ($wb['sheets'] as $sh) {
			$n = self::fold(isset($sh['name']) ? $sh['name'] : '');
			foreach ($needles as $needle) {
				if (strpos($n, self::fold($needle)) !== false) {
					return $sh;
				}
			}
		}
		return null;
	}

	protected static function fold($s) {
		$s = trim(mb_strtolower((string) $s, 'UTF-8'));
		$map = array(
			'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
			'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
			'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
			'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
			'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
			'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
			'đ'=>'d',
		);
		$s = strtr($s, $map);
		$s = preg_replace('/[^a-z0-9]+/u', ' ', $s);
		return trim(preg_replace('/\s+/', ' ', $s));
	}

	protected static function openWorkbook($path) {
		$zip = new ZipArchive();
		if ($zip->open($path) !== true) {
			throw new Exception('Không mở được file xlsx.');
		}
		$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
		$shared = array();
		$ssXml = $zip->getFromName('xl/sharedStrings.xml');
		if ($ssXml !== false) {
			$sx = @simplexml_load_string($ssXml);
			if ($sx) {
				$sx->registerXPathNamespace('m', $ns);
				foreach ($sx->xpath('//m:si') as $si) {
					$shared[] = self::xmlText($si);
				}
			}
		}
		$wbXml = $zip->getFromName('xl/workbook.xml');
		$relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
		if ($wbXml === false || $relsXml === false) {
			$zip->close();
			throw new Exception('File xlsx không hợp lệ.');
		}
		$wb = simplexml_load_string($wbXml);
		$rels = simplexml_load_string($relsXml);
		$wb->registerXPathNamespace('m', $ns);
		$ridMap = array();
		foreach ($rels->Relationship as $rel) {
			$ridMap[(string) $rel['Id']] = (string) $rel['Target'];
		}
		$sheets = array();
		foreach ($wb->xpath('//m:sheets/m:sheet') as $s) {
			$name = (string) $s['name'];
			$rid = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
			if ($rid === '') {
				$rid = (string) $s['id'];
			}
			$target = isset($ridMap[$rid]) ? $ridMap[$rid] : '';
			if ($target === '') {
				continue;
			}
			// OOXML may use absolute package paths: "/xl/worksheets/sheet1.xml"
			$target = ltrim(str_replace('\\', '/', $target), '/');
			if (strpos($target, 'xl/') !== 0) {
				$target = 'xl/' . $target;
			}
			$xml = $zip->getFromName($target);
			$sheets[] = array('name' => $name, 'xml' => $xml !== false ? $xml : '');
		}
		$zip->close();
		return array('shared' => $shared, 'sheets' => $sheets);
	}

	protected static function xmlText($node) {
		$buf = '';
		if (!$node instanceof SimpleXMLElement) {
			return '';
		}
		foreach ($node->xpath('.//*[local-name()="t"]') as $t) {
			$buf .= (string) $t;
		}
		if ($buf === '' && isset($node->t)) {
			$buf = (string) $node->t;
		}
		return $buf;
	}

	/**
	 * @return array<int,array<int,string>> row => 1-based col => value
	 */
	protected static function sheetRows(array $sheet, array $shared) {
		$xml = isset($sheet['xml']) ? $sheet['xml'] : '';
		if ($xml === '') {
			return array();
		}
		$sx = @simplexml_load_string($xml);
		if (!$sx) {
			return array();
		}
		$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
		$sx->registerXPathNamespace('m', $ns);
		$out = array();
		// Use children($ns) for cells — nested xpath('./m:c') fails (undefined ns).
		// Attrs on namespaced children: use attributes(), not $c['r'].
		foreach ($sx->xpath('//m:sheetData/m:row') as $row) {
			$rowAttrs = $row->attributes();
			$r = $rowAttrs && isset($rowAttrs['r']) ? (int) $rowAttrs['r'] : (int) $row['r'];
			if ($r <= 0) {
				continue;
			}
			$cells = $row->children($ns)->c;
			if (!$cells) {
				continue;
			}
			foreach ($cells as $c) {
				$attrs = $c->attributes();
				$ref = $attrs && isset($attrs['r']) ? (string) $attrs['r'] : '';
				if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
					continue;
				}
				$col = self::colToIndex($m[1]);
				$t = $attrs && isset($attrs['t']) ? (string) $attrs['t'] : '';
				$val = '';
				if ($t === 'inlineStr') {
					$val = self::xmlText($c);
				} elseif ($t === 's') {
					$v = isset($c->v) ? (string) $c->v : '';
					if ($v !== '' && isset($shared[(int) $v])) {
						$val = $shared[(int) $v];
					}
				} else {
					$val = isset($c->v) ? (string) $c->v : '';
					if ($val === '') {
						$val = self::xmlText($c);
					}
				}
				if ($val === '') {
					continue;
				}
				$out[$r][$col] = $val;
			}
		}
		ksort($out);
		return $out;
	}

	protected static function colToIndex($letters) {
		$n = 0;
		$letters = strtoupper($letters);
		$len = strlen($letters);
		for ($i = 0; $i < $len; $i++) {
			$n = $n * 26 + (ord($letters[$i]) - 64);
		}
		return $n;
	}
}
