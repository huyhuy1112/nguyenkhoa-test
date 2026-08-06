/**
 * Nguyen Khoa CRM — VND (₫) symbol + Vietnamese number grouping (7.000.000).
 */
(function ($) {
	'use strict';

	var VND_SYMBOL = '\u20AB';
	var GROUPING_SEPARATOR = '.';
	var DECIMAL_SEPARATOR = ',';
	var GROUPING_PATTERN = '123,456,789';

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

	function parse(value) {
		if (value === null || value === undefined || value === '') {
			return 0;
		}
		if (typeof value === 'number') {
			return isFinite(value) ? value : 0;
		}
		var s = String(value).replace(/[^\d.,\-]/g, '').trim();
		if (!s) {
			return 0;
		}
		var neg = s.charAt(0) === '-';
		if (neg) {
			s = s.slice(1);
		}
		// VN grouping: 7.000.000 or 7.000.000,5
		if (/^\d{1,3}(\.\d{3})+(,\d+)?$/.test(s)) {
			s = s.replace(/\./g, '').replace(',', '.');
		} else if (s.indexOf(',') >= 0 && s.indexOf('.') >= 0) {
			if (s.lastIndexOf(',') > s.lastIndexOf('.')) {
				s = s.replace(/\./g, '').replace(',', '.');
			} else {
				s = s.replace(/,/g, '');
			}
		} else if (s.indexOf(',') >= 0) {
			s = s.replace(/\./g, '').replace(',', '.');
		} else if (/^\d{1,3}(\.\d{3})+$/.test(s)) {
			s = s.replace(/\./g, '');
		}
		var n = parseFloat(s);
		if (isNaN(n)) {
			return 0;
		}
		return neg ? -n : n;
	}

	function format(value, options) {
		options = options || {};
		var n = typeof value === 'number' ? value : parse(value);
		if (!isFinite(n)) {
			n = 0;
		}
		var decimals = options.decimals;
		if (decimals === undefined || decimals === null) {
			decimals = (typeof app !== 'undefined' && app.getNumberOfDecimals)
				? parseInt(app.getNumberOfDecimals(), 10)
				: 0;
		}
		if (isNaN(decimals) || decimals < 0) {
			decimals = 0;
		}
		var truncateZeros = options.truncateZeros;
		if (truncateZeros === undefined) {
			truncateZeros = (typeof _USERMETA !== 'undefined' && String(_USERMETA.truncateTrailingZeros) === '1');
		}
		var neg = n < 0;
		n = Math.abs(n);
		var fixed = n.toFixed(decimals);
		var parts = fixed.split('.');
		var whole = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, GROUPING_SEPARATOR);
		var out = whole;
		if (decimals > 0) {
			var frac = parts[1] || '';
			if (!(truncateZeros && parseInt(frac, 10) === 0)) {
				out += DECIMAL_SEPARATOR + frac;
			}
		}
		if (options.withSymbol) {
			out = VND_SYMBOL + out;
		}
		return neg ? '-' + out : out;
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
			_USERMETA.currencyGroupingPattern = GROUPING_PATTERN;
		}
		var $body = $('body');
		if ($body.length) {
			$body.attr('data-user-groupingseparator', GROUPING_SEPARATOR);
			$body.attr('data-user-decimalseparator', DECIMAL_SEPARATOR);
			$body.data('user-groupingseparator', GROUPING_SEPARATOR);
			$body.data('user-decimalseparator', DECIMAL_SEPARATOR);
		}
	}

	function patchAppSeparators() {
		if (typeof app === 'undefined') {
			return;
		}
		app.getGroupingSeparator = function () {
			return GROUPING_SEPARATOR;
		};
		app.getDecimalSeparator = function () {
			return DECIMAL_SEPARATOR;
		};
		if (!app.__mkVnCurrencyPatched && typeof app.convertCurrencyToUserFormat === 'function') {
			var originalConvert = app.convertCurrencyToUserFormat;
			app.convertCurrencyToUserFormat = function (value, appendCurrencySymbol) {
				patchUserMeta();
				return originalConvert.call(app, value, appendCurrencySymbol);
			};
			app.__mkVnCurrencyPatched = true;
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

	function patchInventoryEdit() {
		if (typeof Inventory_Edit_Js === 'undefined') {
			return;
		}
		var proto = Inventory_Edit_Js.prototype;
		if (proto.__mkVnMoneyPatched) {
			return;
		}

		function displayAmount(ctx, raw) {
			var decimals = ctx.numOfCurrencyDecimals;
			if (decimals === undefined || decimals === null || isNaN(decimals)) {
				decimals = (typeof app !== 'undefined' && app.getNumberOfDecimals)
					? parseInt(app.getNumberOfDecimals(), 10) : 0;
			}
			return format(raw, { decimals: decimals });
		}

		proto.formatListPrice = function (lineItemRow, listPriceValue) {
			lineItemRow.find('.listPrice').val(displayAmount(this, listPriceValue));
			return this;
		};

		proto.getListPriceValue = function (lineItemRow) {
			return parse(lineItemRow.find('.listPrice').val());
		};

		proto.setListPriceValue = function (lineItemRow, listPriceValue) {
			lineItemRow.find('.listPrice').val(displayAmount(this, listPriceValue));
			return this;
		};

		proto.setLineItemTotal = function (lineItemRow, lineItemTotalValue) {
			lineItemRow.find('.productTotal').text(displayAmount(this, lineItemTotalValue));
			return this;
		};

		proto.getLineItemTotal = function (lineItemRow) {
			var lineItemTotal = this.getLineItemTotalElement(lineItemRow).text();
			return lineItemTotal ? parse(lineItemTotal) : 0;
		};

		proto.setDiscountTotal = function (lineItemRow, discountValue) {
			jQuery('.discountTotal', lineItemRow).text(displayAmount(this, discountValue));
			return this;
		};

		proto.getDiscountTotal = function (lineItemRow) {
			var element = jQuery('.discountTotal', lineItemRow);
			return element.length > 0 ? parse(element.text()) : 0;
		};

		proto.setTotalAfterDiscount = function (lineItemRow, totalAfterDiscountValue) {
			lineItemRow.find('.totalAfterDiscount').text(displayAmount(this, totalAfterDiscountValue));
			return this;
		};

		proto.getTotalAfterDiscount = function (lineItemRow) {
			var element = lineItemRow.find('.totalAfterDiscount');
			if (element.length > 0) {
				return parse(element.text());
			}
			return this.getLineItemTotal(lineItemRow);
		};

		proto.calculateLineItemTotal = function (lineItemRow) {
			var quantity = this.getQuantityValue(lineItemRow);
			var listPrice = this.getListPriceValue(lineItemRow);
			var lineItemTotal = parseFloat(quantity) * parseFloat(listPrice);
			this.setLineItemTotal(lineItemRow, lineItemTotal);
		};

		proto.calculateDiscountForLineItem = function (lineItemRow) {
			var discountContianer = lineItemRow.find('div.discountUI');
			var element = discountContianer.find('input.discounts').filter(':checked');
			var discountType = element.data('discountType');
			var discountRow = element.closest('tr');

			jQuery('input.discount_type', discountContianer).val(discountType);
			var rowPercentageField = jQuery('input.discount_percentage', discountContianer);
			var rowAmountField = jQuery('input.discount_amount', discountContianer);

			rowPercentageField.addClass('hide');
			rowAmountField.addClass('hide');

			var discountValue = parse(discountRow.find('.discountVal').val());
			if (isNaN(discountValue) || discountValue < 0) {
				discountValue = 0;
			}
			var productTotal = this.getLineItemTotal(lineItemRow);
			var lineItemDiscount = '(' + discountValue + ')';
			if (discountType == Inventory_Edit_Js.percentageDiscountType) {
				lineItemDiscount = '(' + discountValue + '%)';
				rowPercentageField.removeClass('hide').focus();
				discountValue = (productTotal * discountValue) / 100;
			} else if (discountType == Inventory_Edit_Js.directAmountDiscountType) {
				rowAmountField.removeClass('hide').focus();
			}
			jQuery('.itemDiscount', lineItemRow).text(lineItemDiscount);
			jQuery('.productTotalVal', lineItemRow).text(displayAmount(this, productTotal));
			this.setDiscountTotal(lineItemRow, discountValue).calculateTotalAfterDiscount(lineItemRow);
		};

		proto.calculateTotalAfterDiscount = function (lineItemRow) {
			var productTotal = this.getLineItemTotal(lineItemRow);
			var discountTotal = this.getDiscountTotal(lineItemRow);
			var totalAfterDiscount = productTotal - discountTotal;
			this.setTotalAfterDiscount(lineItemRow, totalAfterDiscount);
			var purchaseCost = parse(lineItemRow.find('.purchaseCost').text());
			var margin = totalAfterDiscount - purchaseCost;
			margin = Math.round(margin * 100) / 100;
			this.setMarginValue(lineItemRow, margin);
		};

		var moneyTextMethods = [
			['setNetPrice', true],
			['setNetTotal', false],
			['setGrandTotal', false],
			['setGroupTaxTotal', false]
		];
		moneyTextMethods.forEach(function (pair) {
			var name = pair[0];
			var hasRow = pair[1];
			var original = proto[name];
			if (!original) {
				return;
			}
			proto[name] = hasRow
				? function (lineItemRow, value) {
					return original.call(this, lineItemRow, displayAmount(this, value));
				}
				: function (value) {
					return original.call(this, displayAmount(this, value));
				};
		});

		['getNetPrice', 'getNetTotal', 'getGrandTotal', 'getGroupTaxTotal'].forEach(function (name) {
			var original = proto[name];
			if (!original) {
				return;
			}
			proto[name] = function (lineItemRow) {
				var result = original.call(this, lineItemRow);
				if (typeof result === 'string') {
					return parse(result);
				}
				return result;
			};
		});

		proto.__mkVnMoneyPatched = true;
	}

	function init() {
		patchUserMeta();
		patchAppSeparators();
		applyToDom();
		patchCurrencyFieldJs();
		patchInventoryEdit();
		bindGlobalGroupedInputs();
	}

	/**
	 * While typing: 300000 → 300.000 (VN thousand dots).
	 * Keeps caret stable enough for successive digit entry.
	 */
	function bindGroupedInput(el, options) {
		if (!el || el.nodeType !== 1) {
			return;
		}
		var $el = $(el);
		if ($el.data('mkGroupedBound')) {
			return;
		}
		// Skip non-text inputs
		var type = String($el.attr('type') || 'text').toLowerCase();
		if (type && type !== 'text' && type !== 'search' && type !== 'tel') {
			return;
		}
		options = options || {};
		var decimals = options.decimals;
		if (decimals === undefined || decimals === null) {
			decimals = 0;
		}
		$el.data('mkGroupedBound', true);
		$el.attr('inputmode', 'decimal');
		$el.addClass('mk-currency-grouped-input');

		$el.on('input.mkCurrencyGroup', function () {
			var input = this;
			var raw = String(input.value || '');
			// Allow empty / intermediate decimal
			if (raw === '' || raw === '-' || raw === DECIMAL_SEPARATOR) {
				return;
			}
			// Only group pure-ish digit strings (and dots already from us)
			var digitsOnly = raw.replace(/[^\d]/g, '');
			if (!digitsOnly) {
				return;
			}
			// Cap length for safety
			if (digitsOnly.length > 15) {
				digitsOnly = digitsOnly.slice(0, 15);
			}
			var n = parseInt(digitsOnly, 10);
			if (!isFinite(n)) {
				return;
			}
			var formatted = format(n, { decimals: decimals, truncateZeros: true });
			if (formatted === raw) {
				return;
			}
			// Caret at end is stable for sequential typing
			input.value = formatted;
			try {
				var end = formatted.length;
				input.setSelectionRange(end, end);
			} catch (ignore) { /* ignore */ }
		});

		$el.on('blur.mkCurrencyGroup', function () {
			var raw = String(this.value || '').trim();
			if (!raw) {
				return;
			}
			var n = parse(raw);
			if (!isFinite(n)) {
				return;
			}
			this.value = format(n, { decimals: decimals, truncateZeros: true });
		});
	}

	function isLikelyAmountField(el) {
		if (!el || !el.getAttribute) {
			return false;
		}
		var $el = $(el);
		if ($el.is(':disabled, [readonly], [type="hidden"], [type="checkbox"], [type="radio"], [type="file"], [type="date"], [type="password"]')) {
			return false;
		}
		if ($el.hasClass('mk-currency-grouped-input') || $el.data('mkGroupedBound')) {
			return true;
		}
		if ($el.attr('data-mk-vn-number') === '1' || $el.hasClass('mk-number') || $el.hasClass('currencyField')) {
			return true;
		}
		// List search row currency / price columns
		if ($el.hasClass('listSearchContributor')) {
			var name = String($el.attr('name') || $el.attr('data-fieldname') || $el.attr('data-field-name') || '').toLowerCase();
			if (/(price|amount|total|value|cost|grand|hdnGrandTotal|subtotal|discount|tax|qty_price|unit_price)/.test(name)) {
				return true;
			}
			var $th = $el.closest('th, td');
			var col = String($th.attr('data-fieldname') || $th.attr('data-columnname') || $th.find('[data-fieldname]').attr('data-fieldname') || '').toLowerCase();
			if (/(price|amount|total|value|cost|grand)/.test(col)) {
				return true;
			}
			// uitype currency often has adjacent symbol addon
			if ($el.closest('.input-group').find('.input-group-addon, .currencySymbol').length) {
				return true;
			}
		}
		// Edit/create money fields
		if ($el.closest('.mk-ps-compact-field--money, .currencyField, [data-field-type="currency"], .fieldValue.currency').length) {
			return true;
		}
		if ($el.is('input.listPrice, input.discount_amount, input.discountVal, input.purchaseCost, input.taxPercentage')) {
			return true;
		}
		var fname = String($el.attr('name') || '').toLowerCase();
		if (/^(price|wholesale_price|retail_price|bulk_price|amount|expectedrevenue|hdngrandtotal)/.test(fname)) {
			return true;
		}
		if (/price_lt_|price_gte_/.test(fname)) {
			return true;
		}
		return false;
	}

	function bindGlobalGroupedInputs(root) {
		var $scope = root ? $(root) : $(document);
		$scope.find('input').each(function () {
			if (isLikelyAmountField(this)) {
				bindGroupedInput(this, { decimals: 0 });
			}
		});
	}

	// Capture-phase for dynamic search rows after ajax
	$(document).on('focusin.mkCurrencyGroup', 'input', function () {
		if (isLikelyAmountField(this) && !$(this).data('mkGroupedBound')) {
			bindGroupedInput(this, { decimals: 0 });
		}
	});

	// Before list search submit: strip grouping so params are pure numbers
	$(document).on('click.mkCurrencyGroup', '.listSearchSubmitButton, button[data-trigger="listSearch"]', function () {
		$('.listSearchContributor').each(function () {
			if (!isLikelyAmountField(this) && !$(this).data('mkGroupedBound')) {
				return;
			}
			var v = String(this.value || '').trim();
			if (!v) {
				return;
			}
			var n = parse(v);
			if (isFinite(n)) {
				// Keep formatted display; also store raw on data for consumers that parse via MkCurrency
				$(this).attr('data-mk-raw-value', String(n));
			}
		});
	});

	$(function () {
		init();

		$(document).ajaxComplete(function () {
			init();
		});

		$(document).on('pjax:end pjax:success', function () {
			init();
		});
	});

	window.MkCurrency = {
		VND_SYMBOL: VND_SYMBOL,
		GROUPING_SEPARATOR: GROUPING_SEPARATOR,
		DECIMAL_SEPARATOR: DECIMAL_SEPARATOR,
		normalize: normalizeSymbol,
		parse: parse,
		format: format,
		applyToDom: applyToDom,
		bindGroupedInput: bindGroupedInput,
		bindGlobalGroupedInputs: bindGlobalGroupedInputs
	};
})(jQuery);
