<?php
/**
 * TDB display helpers.
 *
 * IMPORTANT: Use for plain text display only (names/titles/labels).
 * Do NOT use for rich HTML fields (descriptions, comments, etc.).
 */

if (!function_exists('tdb_decode_display_text')) {
	function tdb_decode_display_text($value): string {
		// Plain-text display only (names/titles/labels).
		// Do NOT use this for rich HTML fields.
		if ($value === null) return '';

		$value = (string) $value;
		if ($value === '') return '';

		// Only decode when it looks like it contains HTML entities.
		// This avoids touching strings like "R&D" or "AT&T".
		if (strpos($value, '&') === false) return $value;
		if (!preg_match('/&(#\\d+|#x[0-9a-fA-F]+|[A-Za-z][A-Za-z0-9]+);/', $value)) {
			return $value;
		}

		$charset = 'UTF-8';
		if (function_exists('vglobal')) {
			$charset = vglobal('default_charset') ?: 'UTF-8';
		}
		return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, $charset);
	}
}

