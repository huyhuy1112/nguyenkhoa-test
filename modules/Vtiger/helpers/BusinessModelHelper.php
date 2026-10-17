<?php
/**
 * Dedicated picklist: Mô hình kinh doanh (Leads / Contacts / Potentials).
 * Stored as the Vietnamese label, not a tag key.
 */
class Vtiger_BusinessModel_Helper {

	public static function labels() {
		return array(
			'Thuê hoặc có sẵn mặt bằng',
			'Mở vỉa hè, bán online',
			'TS Topping',
			'Xe đẩy',
			'Cà phê máy lạnh',
			'Cà phê sân vườn',
			'TS Pha máy',
			'Cà phê không gian mở',
			'Cửa hàng',
			'Online',
		);
	}

	public static function normalize($value) {
		$v = trim((string) $value);
		if ($v === '') {
			return '';
		}
		if (function_exists('decode_html')) {
			$v = trim(decode_html($v));
		}
		if (in_array($v, self::labels(), true)) {
			return $v;
		}
		return self::fromFormAnswer($v);
	}

	/**
	 * Map Form Câu 2 (mã A–B mới, hoặc A–G legacy) → nhãn dropdown Leads.
	 */
	public static function fromFormAnswer($raw) {
		$v = trim((string) $raw);
		if ($v === '') {
			return '';
		}
		if (function_exists('decode_html')) {
			$v = trim(decode_html($v));
		}
		if (in_array($v, self::labels(), true)) {
			return $v;
		}
		$code = strtoupper(substr(ltrim($v), 0, 1));
		$rest = substr(ltrim($v), 1);
		if (preg_match('/^[A-G]$/i', $code) && ($rest === '' || preg_match('/^[\s.\-–—:).]/u', $rest))) {
			// GD1.1 mới: A = mặt bằng, B = vỉa hè/online
			if ($code === 'A' && (stripos($v, 'mặt bằng') !== false || stripos($v, 'mat bang') !== false || strlen(trim($rest)) <= 1)) {
				// Ambiguous short "A" — prefer new meaning when only letter
				if (strlen(trim($v)) <= 2) {
					return 'Thuê hoặc có sẵn mặt bằng';
				}
			}
			$mapNew = array(
				'A' => 'Thuê hoặc có sẵn mặt bằng',
				'B' => 'Mở vỉa hè, bán online',
			);
			// If explicit new labels in text
			$f = self::fold($v);
			if (strpos($f, 'via he') !== false || strpos($f, 'ban online') !== false) {
				return 'Mở vỉa hè, bán online';
			}
			if (strpos($f, 'mat bang') !== false || strpos($f, 'thue') !== false) {
				return 'Thuê hoặc có sẵn mặt bằng';
			}
			if (isset($mapNew[$code]) && strlen(trim($v)) <= 2) {
				return $mapNew[$code];
			}
			$mapLegacy = array(
				'A' => 'Xe đẩy',
				'B' => 'TS Topping',
				'C' => 'TS Pha máy',
				'D' => 'Cà phê máy lạnh',
				'E' => 'Cà phê sân vườn',
				'F' => 'Cà phê không gian mở',
				'G' => '',
			);
			// Legacy detailed answers → collapse to 2 buckets for new GD1.1
			if ($code === 'A' || $code === 'G') {
				return $code === 'A' ? 'Mở vỉa hè, bán online' : '';
			}
			if (in_array($code, array('B', 'C', 'D', 'E', 'F'), true)) {
				return 'Thuê hoặc có sẵn mặt bằng';
			}
			return isset($mapLegacy[$code]) ? $mapLegacy[$code] : '';
		}
		$f = self::fold($v);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'gia dinh') !== false || strpos($f, 'so thich') !== false || strpos($f, 'pha che cho gia') !== false) {
			return '';
		}
		if (strpos($f, 'via he') !== false || (strpos($f, 'online') !== false && strpos($f, 'mat bang') === false) || strpos($f, 'xe day') !== false) {
			return 'Mở vỉa hè, bán online';
		}
		if (strpos($f, 'mat bang') !== false || strpos($f, 'thue') !== false) {
			return 'Thuê hoặc có sẵn mặt bằng';
		}
		if (strpos($f, 'xe day') !== false) {
			return 'Xe đẩy';
		}
		if (strpos($f, 'cua hang') !== false) {
			return 'Cửa hàng';
		}
		if ($f === 'online') {
			return 'Online';
		}
		if (strpos($f, 'topping') !== false) {
			return 'TS Topping';
		}
		if (strpos($f, 'pha may') !== false) {
			return 'TS Pha máy';
		}
		if (strpos($f, 'san vuon') !== false) {
			return 'Cà phê sân vườn';
		}
		if (strpos($f, 'khong gian mo') !== false) {
			return 'Cà phê không gian mở';
		}
		if (strpos($f, 'may lanh') !== false || $f === 'ca phe may' || strpos($f, 'ca phe may') !== false) {
			return 'Cà phê máy lạnh';
		}
		return '';
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
}
