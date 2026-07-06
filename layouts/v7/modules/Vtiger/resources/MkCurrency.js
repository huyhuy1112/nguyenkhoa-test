/**
 * Nguyen Khoa CRM — system-wide VND (₫) currency symbol branding.
 */
(function ($) {
	'use strict';

	var VND_SYMBOL = '\u20AB';

	function isDollarSymbol(text) {
		var t = (text || '').trim();
		if (!t) {
			return false;
		}
		var upper = t.toUpperCase();
		return t === '$' || upper === 'USD' || upper === 'US$' || upper === 'U$D';
	}

	function normalizeSymbol(symbol) {
		if (symbol === null || symbol === undefined) {
			return VND_SYMBOL;
		}
		var decoded = $('<textarea/>').html(String(symbol)).text().trim();
		if (!decoded || isDollarSymbol(decoded)) {
			return VND_SYMBOL;
		}
		return decoded;
	}

	var CURRENCY_SELECTORS = [
		'.input-group-addon',
		'.currencySymbol',
		'#baseCurrencySymbol',
		'#basic-addon1',
		'span.currencyName .currencySymbol'
	].join(',');

	function applyToDom(root) {
		var $scope = root ? $(root) : $(document);
		$scope.find(CURRENCY_SELECTORS).each(function () {
			var $el = $(this);
			var text = ($el.text() || '').trim();
			if (isDollarSymbol(text)) {
				$el.text(VND_SYMBOL);
			}
		});
	}

	function patchUserMeta() {
		if (typeof _USERMETA !== 'undefined' && _USERMETA) {
			_USERMETA.currency = normalizeSymbol(_USERMETA.currency);
		}
	}

	function patchCurrencyFieldJs() {
		if (typeof Vtiger_Currency_Field_Js === 'undefined') {
			return;
		}
		var proto = Vtiger_Currency_Field_Js.prototype;
		if (proto.__mkVndPatched) {
			return;
		}
		var original = proto.getCurrencySymbol;
		proto.getCurrencySymbol = function () {
			var symbol = original ? original.call(this) : '';
			if (!symbol && typeof _USERMETA !== 'undefined') {
				symbol = _USERMETA.currency;
			}
			return normalizeSymbol(symbol);
		};
		proto.__mkVndPatched = true;
	}

	function init() {
		patchUserMeta();
		applyToDom();
		patchCurrencyFieldJs();
	}

	$(function () {
		init();

		$(document).ajaxComplete(function () {
			patchUserMeta();
			applyToDom();
			patchCurrencyFieldJs();
		});

		$(document).on('pjax:end pjax:success', function () {
			patchUserMeta();
			applyToDom();
			patchCurrencyFieldJs();
		});
	});

	window.MkCurrency = {
		VND_SYMBOL: VND_SYMBOL,
		normalize: normalizeSymbol,
		applyToDom: applyToDom
	};
})(jQuery);
