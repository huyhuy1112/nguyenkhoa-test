<?php
/*+***********************************************************************************
 * Inventory terms & conditions — safe HTML for detail views, plain text for PDF/export.
 *************************************************************************************/

require_once 'modules/Quotes/helpers/QuoteBaService.php';

class Inventory_TermsDisplayHelper {

	public static function htmlToPlainText($html) {
		$html = Quotes_QuoteBaService_Helper::stripSignatureFromTermsHtml((string) $html);
		if (function_exists('decode_html')) {
			$html = decode_html($html);
		}
		$html = preg_replace('/<li[^>]*>/i', "\n• ", $html);
		$html = preg_replace('/<\/li>/i', "\n", $html);
		$html = preg_replace('/<\/(p|div|h[1-6])>/i', "\n", $html);
		$html = preg_replace('/<br\s*\/?>/i', "\n", $html);
		$html = strip_tags($html);
		$html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$html = preg_replace("/\r\n|\r/", "\n", $html);
		$html = preg_replace("/[ \t]+/", ' ', $html);
		$html = preg_replace("/\n{3,}/", "\n\n", $html);
		return trim($html);
	}

	public static function htmlToSafeDetailHtml($html) {
		$html = Quotes_QuoteBaService_Helper::stripSignatureFromTermsHtml((string) $html);
		if (function_exists('decode_html')) {
			$html = decode_html($html);
		}
		if (function_exists('purifyHtmlEventAttributes')) {
			$html = purifyHtmlEventAttributes($html, true);
		}
		return $html;
	}
}
