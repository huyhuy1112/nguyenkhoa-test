/**
 * Quotes BA form behavior — company profile, defaults, VAT/words, customer sync.
 * Loaded with QuoteMkEdit.js; does not alter dashboard shell layout.
 */
(function ($, global) {
	'use strict';

	var baConfig = null;

	function cfg() {
		return baConfig || global.__MK_QUOTE_BA_CONFIG || {};
	}

	function parseMoney(val) {
		if (val === null || val === undefined || val === '') {
			return 0;
		}
		if (typeof val === 'number') {
			return val;
		}
		var s = String(val).replace(/[^\d.,-]/g, '');
		s = s.replace(/\./g, '').replace(',', '.');
		var n = parseFloat(s);
		return isNaN(n) ? 0 : n;
	}

	function formatMoney(n) {
		if (!isFinite(n)) {
			return '';
		}
		return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

	function amountInWordsVi(amount) {
		amount = Math.round(parseMoney(amount));
		if (amount === 0) {
			return 'Không đồng';
		}
		if (amount < 0) {
			return 'Âm ' + amountInWordsVi(Math.abs(amount));
		}
		var units = ['', 'nghìn', 'triệu', 'tỷ'];
		var words = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];

		function readTriple(num) {
			var hundreds = Math.floor(num / 100);
			var tens = Math.floor((num % 100) / 10);
			var ones = num % 10;
			var parts = [];
			if (hundreds > 0) {
				parts.push(words[hundreds] + ' trăm');
			}
			if (tens > 1) {
				parts.push(words[tens] + ' mươi');
				if (ones === 1) {
					parts.push('mốt');
				} else if (ones === 5) {
					parts.push('lăm');
				} else if (ones > 0) {
					parts.push(words[ones]);
				}
			} else if (tens === 1) {
				parts.push('mười');
				if (ones === 5) {
					parts.push('lăm');
				} else if (ones > 0) {
					parts.push(words[ones]);
				}
			} else if (ones > 0) {
				if (hundreds > 0) {
					parts.push('lẻ');
				}
				parts.push(ones === 5 && hundreds > 0 ? 'lăm' : words[ones]);
			}
			return parts.join(' ').trim();
		}

		var chunks = [];
		var unitIndex = 0;
		while (amount > 0) {
			var chunk = amount % 1000;
			if (chunk > 0) {
				var text = readTriple(chunk);
				if (units[unitIndex]) {
					text += ' ' + units[unitIndex];
				}
				chunks.unshift(text.trim());
			}
			amount = Math.floor(amount / 1000);
			unitIndex++;
		}
		var out = chunks.join(' ').replace(/\s+/g, ' ').trim();
		return out.charAt(0).toUpperCase() + out.slice(1) + ' đồng';
	}

	function $field($form, name) {
		return $form.find('[name="' + name + '"]').first();
	}

	function setFieldVal($form, name, value) {
		var $f = $field($form, name);
		if ($f.length) {
			$f.val(decodeHtmlText(value)).trigger('change');
		}
	}

	function decodeHtmlText(value) {
		if (value === null || value === undefined) {
			return '';
		}
		var text = String(value);
		if (!text) {
			return '';
		}
		if (typeof app !== 'undefined' && app.htmlDecode) {
			return app.htmlDecode(text);
		}
		var ta = document.createElement('textarea');
		ta.innerHTML = text;
		return ta.value;
	}

	function readFieldVal($form, name) {
		var $f = $field($form, name);
		return $f.length ? $.trim($f.val()) : '';
	}

	function getQuoteRail() {
		return $('#mkQtQuoteRail, #mkSoOrderRail').first();
	}

	function injectCompanyReadonly($form) {
		var company = cfg().company || {};
		var $rail = getQuoteRail();
		if (!$rail.length || $rail.find('.mk-qt-company-ro').length) {
			return;
		}
		// Force NK brand name in UI (BA request)
		company = $.extend({}, company, { company_name: 'Nguyên Khoa' });
		var rows = [
			['Tên công ty', company.company_name],
			['Mã số thuế', company.tax_code],
			['Website', company.website],
			['Ngân hàng', company.bank_name],
			['Số tài khoản', company.bank_account],
			['Chủ tài khoản', company.account_holder]
		];
		var cells = rows
			.map(function (row) {
				var val = row[1] ? decodeHtmlText(String(row[1])) : '—';
				return (
					'<div class="mk-qt-company-ro__item"><span class="mk-qt-company-ro__label">' +
					row[0] +
					'</span><span class="mk-qt-company-ro__value">' +
					$('<div>').text(val).html() +
					'</span></div>'
				);
			})
			.join('');
		var $panel = $(
			'<div class="mk-qt-company-ro" aria-label="Thông tin công ty (từ cấu hình hệ thống)">' +
				'<p class="mk-qt-company-ro__title">Thông tin công ty <span class="mk-qt-company-ro__hint">(tự động từ cấu hình — không nhập lại)</span></p>' +
				'<div class="mk-qt-company-ro__grid">' +
				cells +
				'</div></div>'
		);
		// Move company panel to rail under summary card
		var $card = $('<div class="mk-qt-rail-card mk-qt-rail-card--company"></div>');
		$card.append($panel);
		var $summary = $rail.find('.mk-qt-rail-card--summary').first();
		if ($summary.length) {
			$summary.after($card);
		} else {
			$rail.append($card);
		}
	}

	function injectAddressEditorToRail($form) {
		var $rail = getQuoteRail();
		if (!$rail.length || $rail.find('.mk-qt-address-rail').length) {
			return;
		}
		var isSo = $rail.attr('id') === 'mkSoOrderRail';
		var prefix = isSo ? 'mkSo' : 'mkQt';
		var $bill = $form.find('[name="bill_street"]').first();
		var $ship = $form.find('[name="ship_street"]').first();
		if (!$bill.length || !$ship.length) {
			return;
		}

		var $card = $('<div class="mk-qt-rail-card mk-qt-rail-card--address mk-qt-address-rail"></div>');
		$card.append('<div class="mk-qt-rail-card__head"><span class="mk-qt-rail-card__icon" aria-hidden="true"><i class="fa fa-map-marker"></i></span><h2 class="mk-qt-rail-card__title">Địa chỉ</h2></div>');
		$card.append(
			'<div class="mk-qt-addr-grid">' +
				'<div class="mk-qt-addr-col"><label class="mk-qt-addr-label" for="' + prefix + 'BillStreetRail">Địa chỉ</label><textarea id="' + prefix + 'BillStreetRail" class="mk-qt-addr-ta" rows="4"></textarea></div>' +
				'<div class="mk-qt-addr-col"><div class="mk-qt-addr-row"><label class="mk-qt-addr-label" for="' + prefix + 'ShipStreetRail">Địa chỉ vận chuyển</label><label class="mk-qt-addr-copy"><input type="checkbox" id="' + prefix + 'AddrSame" /> Giống địa chỉ lập hoá đơn</label></div><textarea id="' + prefix + 'ShipStreetRail" class="mk-qt-addr-ta" rows="4"></textarea></div>' +
			'</div>'
		);

		var $summary = $rail.find('.mk-qt-rail-card--company').first();
		if ($summary.length) {
			$summary.after($card);
		} else {
			$rail.append($card);
		}

		var $billRail = $('#' + prefix + 'BillStreetRail');
		var $shipRail = $('#' + prefix + 'ShipStreetRail');
		var $same = $('#' + prefix + 'AddrSame');

		$billRail.val($bill.val() || '');
		$shipRail.val($ship.val() || '');

		$billRail.on('input', function () {
			$bill.val($(this).val());
			if ($same.is(':checked')) {
				$shipRail.val($(this).val());
				$ship.val($(this).val());
			}
		});
		$shipRail.on('input', function () {
			$ship.val($(this).val());
		});
		$same.on('change', function () {
			if ($(this).is(':checked')) {
				$shipRail.val($billRail.val());
				$ship.val($billRail.val());
			}
		});

		// Hide the original address block in main form (avoid duplicate)
		$form.find('.fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"]').addClass('mk-qt-hide-legacy');
	}

	function initBaDefaults($form) {
		var c = cfg();
		var vat = readFieldVal($form, 'mk_vat_percent');
		if (!vat) {
			setFieldVal($form, 'mk_vat_percent', String(c.vat_percent_default || 8));
		}
	}

	function markReadonlyComputed($form) {
		$field($form, 'mk_vat_amount').prop('readonly', true);
		$field($form, 'mk_amount_in_words').prop('readonly', true);
		$field($form, 'quote_no').prop('readonly', true);
	}

	function syncVatAndWords($form) {
		var subtotal = parseMoney($form.find('#netTotal').text() || $form.find('[name="hdnSubTotal"]').val());
		if (subtotal <= 0) {
			subtotal = parseMoney($form.find('#grandTotal').text() || $form.find('[name="hdnGrandTotal"]').val());
		}
		var vatPercent = parseMoney(readFieldVal($form, 'mk_vat_percent')) || parseMoney(cfg().vat_percent_default) || 8;
		var vatAmount = Math.round(subtotal * vatPercent / 100);
		var grand = subtotal + vatAmount;
		setFieldVal($form, 'mk_vat_amount', formatMoney(vatAmount));
		setFieldVal($form, 'mk_amount_in_words', amountInWordsVi(grand));
	}

	function fetchRecordDetails(module, recordId) {
		var d = $.Deferred();
		if (!recordId) {
			d.resolve({});
			return d.promise();
		}
		$.getJSON('index.php', {
			module: 'Vtiger',
			action: 'GetData',
			source_module: module,
			record: recordId
		})
			.done(function (res) {
				d.resolve((res && res.result && res.result.data) || {});
			})
			.fail(function () {
				d.resolve({});
			});
		return d.promise();
	}

	function syncCustomerFromReferences($form) {
		var accountId = readFieldVal($form, 'account_id');
		var contactId = readFieldVal($form, 'contact_id');
		if (!accountId && !contactId) {
			return;
		}
		$.when(fetchRecordDetails('Accounts', accountId), fetchRecordDetails('Contacts', contactId)).done(function (
			account,
			contact
		) {
			if (account.accountname && !readFieldVal($form, 'mk_client_company')) {
				setFieldVal($form, 'mk_client_company', account.accountname);
			}
			var phone = contact.mobile || contact.phone || account.phone || '';
			var email = contact.email || contact.email1 || account.email1 || account.email2 || account.email || '';
			if (phone && !readFieldVal($form, 'mk_customer_phone')) {
				setFieldVal($form, 'mk_customer_phone', phone);
			}
			if (email && !readFieldVal($form, 'mk_customer_email')) {
				setFieldVal($form, 'mk_customer_email', email);
			}
		});
	}

	function observeTotals($form, callback) {
		var target = $form.find('#lineItemResult, #grandTotal, #netTotal').get(0);
		if (!target || typeof MutationObserver === 'undefined') {
			return;
		}
		var obs = new MutationObserver(function () {
			callback();
		});
		obs.observe(target, { childList: true, subtree: true, characterData: true });
	}

	function init($form) {
		if (!$form || !$form.length) {
			return;
		}
		if ($form.data('mkQtBaReady')) {
			syncVatAndWords($form);
			return;
		}
		$form.data('mkQtBaReady', true);

		var boot = function () {
			injectCompanyReadonly($form);
			injectAddressEditorToRail($form);
			initBaDefaults($form);
			markReadonlyComputed($form);
			syncVatAndWords($form);
		};

		if (global.__MK_QUOTE_BA_CONFIG) {
			baConfig = global.__MK_QUOTE_BA_CONFIG;
			boot();
		} else {
			$.getJSON('index.php', { module: 'Quotes', action: 'GetBaContext' }).done(function (res) {
				baConfig = res.result || res || {};
				boot();
			});
		}

		$form.on('change.mkQtBa', '[name="account_id"], [name="contact_id"]', function () {
			syncCustomerFromReferences($form);
		});
		$form.on('change.mkQtBa input.mkQtBa', '[name="mk_vat_percent"]', function () {
			syncVatAndWords($form);
		});

		observeTotals($form, function () {
			syncVatAndWords($form);
		});
	}

	global.MkQuoteBa = {
		init: init,
		syncVatAndWords: syncVatAndWords,
		amountInWordsVi: amountInWordsVi,
		cfg: cfg
	};
})(jQuery, window);
