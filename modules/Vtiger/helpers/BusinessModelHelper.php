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
		return in_array($v, self::labels(), true) ? $v : '';
	}
}
