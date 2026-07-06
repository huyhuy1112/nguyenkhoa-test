<?php
/**
 * Nguyen Khoa CRM — normalize currency display to VND (₫) instead of $ / USD.
 */
class MkCurrencyBranding {

	const VND_SYMBOL = '₫';

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

	protected static function isDollarSymbol($symbol) {
		$upper = strtoupper(trim((string) $symbol));
		return in_array($upper, array('$', 'USD', 'US$', 'U$D'), true)
			|| $symbol === '&#36;'
			|| $symbol === '&dollar;';
	}
}
