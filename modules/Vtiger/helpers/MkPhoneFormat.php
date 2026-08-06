<?php
/**
 * Global phone display helper — VN form "xxxx xxx xxx" (10 digits).
 * Store raw digits/spaces in DB; format only for display.
 */
class Vtiger_MkPhoneFormat_Helper {

	/**
	 * Strip to digits only.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function digitsOnly($value) {
		return preg_replace('/\D+/', '', (string) ($value === null ? '' : $value));
	}

	/**
	 * Format for UI display. Prefer 10-digit VN: 4-3-3 (e.g. 0906 345 551).
	 * Empty / dash placeholders pass through unchanged.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function formatDisplay($value) {
		if ($value === null) {
			return '';
		}
		$str = (string) $value;
		if (function_exists('decode_html')) {
			$raw = trim(decode_html($str));
		} else {
			$raw = trim(html_entity_decode($str, ENT_QUOTES, 'UTF-8'));
		}
		if ($raw === '' || $raw === '—' || $raw === '-' || $raw === 'N/A' || $raw === 'n/a') {
			return $raw;
		}

		$digits = self::digitsOnly($raw);
		if ($digits === '') {
			return $raw;
		}

		// Normalize common country-code forms to local 0xxxxxxxxx
		if (strlen($digits) === 11 && strpos($digits, '84') === 0) {
			$digits = '0' . substr($digits, 2);
		} elseif (strlen($digits) === 12 && strpos($digits, '840') === 0) {
			$digits = '0' . substr($digits, 3);
		}

		if (strlen($digits) === 10) {
			return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7, 3);
		}
		if (strlen($digits) === 9) {
			return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3);
		}

		// Other lengths: return digits without inventing spacing for long strings
		return $digits;
	}
}
