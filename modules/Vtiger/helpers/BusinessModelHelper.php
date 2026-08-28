<?php
/**
 * Dedicated picklist: Mô hình kinh doanh (Leads / Contacts / Potentials).
 * Stored as the Vietnamese label, not a tag key.
 */
class Vtiger_BusinessModel_Helper {

	public static function labels() {
		return array(
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
	 * Map Form Câu 2 (mã A–G hoặc câu chữ dài) → nhãn dropdown Leads.
	 * G (gia đình / sở thích) không phải mô hình quán → trả về rỗng.
	 */
	public static function fromFormAnswer($raw) {
		$v = trim((string) $raw);
		if ($v === '') {
			return '';
		}
		if (function_exists('decode_html')) {
			$v = trim(decode_html($v));
		}
		$code = strtoupper(substr(ltrim($v), 0, 1));
		$rest = substr(ltrim($v), 1);
		if (preg_match('/^[A-G]$/i', $code) && ($rest === '' || preg_match('/^[\s.\-–—:).]/u', $rest))) {
			$map = array(
				'A' => 'Xe đẩy',
				'B' => 'TS Topping',
				'C' => 'TS Pha máy',
				'D' => 'Cà phê máy lạnh',
				'E' => 'Cà phê sân vườn',
				'F' => 'Cà phê không gian mở',
				'G' => '',
			);
			return isset($map[$code]) ? $map[$code] : '';
		}
		$f = self::fold($v);
		if ($f === '') {
			return '';
		}
		if (strpos($f, 'gia dinh') !== false || strpos($f, 'so thich') !== false || strpos($f, 'pha che cho gia') !== false) {
			return '';
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
