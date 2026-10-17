<?php
/*+**********************************************************************************
 * Customer codes: KL_ / MIUTEA_ (Contacts), TUIBAO_ (Accounts).
 ***********************************************************************************/

class MkCustomerCode {

	protected static function decodeText($raw) {
		$raw = (string) $raw;
		if (function_exists('decode_html')) {
			return trim(decode_html($raw));
		}
		return trim($raw);
	}

	public static function foldSlug($name) {
		$name = self::decodeText($name);
		if ($name === '') {
			return 'kh';
		}
		$name = mb_strtolower($name, 'UTF-8');
		$name = str_replace(array('đ', 'Đ'), array('d', 'd'), $name);
		if (function_exists('transliterator_transliterate')) {
			$name = transliterator_transliterate('Any-Latin; Latin-ASCII', $name);
		}
		$name = preg_replace('/[^a-z0-9]+/', '_', $name);
		$name = trim($name, '_');
		if ($name === '') {
			return 'kh';
		}
		if (strlen($name) > 48) {
			$name = substr($name, 0, 48);
			$name = rtrim($name, '_');
		}
		return $name;
	}

	public static function phoneLast4($phone) {
		$digits = preg_replace('/\D+/', '', (string) $phone);
		if (strlen($digits) >= 4) {
			return substr($digits, -4);
		}
		if ($digits !== '') {
			return str_pad($digits, 4, '0', STR_PAD_LEFT);
		}
		return '0000';
	}

	/**
	 * Contacts: keep Excel code only when it starts with MIUTEA_ (any case); else KL_{name}_{last4}.
	 */
	public static function contactCode($excelCode, $displayName, $phone) {
		$excelCode = trim(decode_html((string) $excelCode));
		if ($excelCode !== '' && preg_match('/^MIUTEA_/i', $excelCode)) {
			return $excelCode;
		}
		$slug = self::foldSlug($displayName);
		$last4 = self::phoneLast4($phone);
		return 'KL_' . $slug . '_' . $last4;
	}

	/** Accounts (chủ quán): TUIBAO_{name}_{last4}. */
	public static function accountCode($displayName, $phone) {
		$slug = self::foldSlug($displayName);
		$last4 = self::phoneLast4($phone);
		return 'TUIBAO_' . $slug . '_' . $last4;
	}
}
