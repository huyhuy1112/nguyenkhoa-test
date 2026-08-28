<?php
/**
 * Nguyen Khoa CRM — VND symbol + Vietnamese number grouping (7.000.000).
 */
class MkCurrencyBranding {

	const VND_SYMBOL = '₫';
	const GROUPING_PATTERN = '123,456,789';
	const GROUPING_SEPARATOR = '.';
	const DECIMAL_SEPARATOR = ',';

	public static function normalizeSymbol($symbol) {
		if ($symbol === null) {
			return self::VND_SYMBOL;
		}
		$symbol = trim(html_entity_decode((string) $symbol, ENT_QUOTES, 'UTF-8'));
		if ($symbol === '' || self::isDollarSymbol($symbol)) {
			return self::VND_SYMBOL;
		}
		return $symbol;
	}

	/**
	 * Force VN thousand/decimal separators on a user object (display + parse).
	 * @param object|null $user
	 */
	public static function applyVnNumberFormat($user) {
		if (empty($user) || !is_object($user)) {
			return;
		}
		$user->currency_grouping_pattern = self::GROUPING_PATTERN;
		$user->currency_grouping_separator = self::GROUPING_SEPARATOR;
		$user->currency_decimal_separator = self::DECIMAL_SEPARATOR;
		if (isset($user->column_fields) && is_array($user->column_fields)) {
			$user->column_fields['currency_grouping_pattern'] = self::GROUPING_PATTERN;
			$user->column_fields['currency_grouping_separator'] = self::GROUPING_SEPARATOR;
			$user->column_fields['currency_decimal_separator'] = self::DECIMAL_SEPARATOR;
		}
	}

	protected static function isDollarSymbol($symbol) {
		$upper = strtoupper(trim((string) $symbol));
		return in_array($upper, array('$', 'USD', 'US$', 'U$D'), true)
			|| $symbol === '&#36;'
			|| $symbol === '&dollar;';
	}
}
