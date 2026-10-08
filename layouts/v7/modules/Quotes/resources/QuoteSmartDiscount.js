/* Quote smart discount — KL compares value vs quantity, Miutea value only, Tuibao manual. */
(function ($) {
	'use strict';

	var VALUE_TIERS = [
		{ min: 50000000, percent: 12 },
		{ min: 20000000, percent: 8 },
		{ min: 10000000, percent: 5 },
		{ min: 5000000, percent: 2 },
		{ min: 0, percent: 0 }
	];
	var QTY_TIERS = [
		{ min: 500, percent: 15 },
		{ min: 200, percent: 10 },
		{ min: 100, percent: 7 },
		{ min: 50, percent: 5 },
		{ min: 20, percent: 3 },
		{ min: 0, percent: 0 }
	];

	var applying = false;
	var timer = null;
	var resolved = { code: '', segment: '' };

	function parseMoney(raw) {
		var s = String(raw || '').trim();
		if (!s) {
			return 0;
		}
		if (s.indexOf(',') >= 0 && s.indexOf('.') >= 0) {
			s = s.replace(/\./g, '').replace(',', '.');
		} else if (/^\d{1,3}(\.\d{3})+$/.test(s)) {
			s = s.replace(/\./g, '');
		} else {
			s = s.replace(/[^\d.-]/g, '');
		}
		var n = parseFloat(s);
		return isNaN(n) ? 0 : n;
	}

	function money(n) {
		n = Math.round(Number(n) || 0);
		return n.toLocaleString('vi-VN');
	}

	function findTier(value, tiers) {
		if (!(value > 0)) {
			return null;
		}
		var sorted = tiers.slice().sort(function (a, b) {
			return b.min - a.min;
		});
		for (var i = 0; i < sorted.length; i++) {
			if (value >= sorted[i].min) {
				return sorted[i];
			}
		}
		return null;
	}

	function offer(subtotal, tier) {
		var percent = !tier || subtotal <= 0 ? 0 : tier.percent;
		var saving = Math.round((subtotal * percent) / 100);
		return { percent: percent, saving: saving };
	}

	function compare(subtotal, qty) {
		var byValue = offer(subtotal, findTier(subtotal, VALUE_TIERS));
		var byQty = offer(subtotal, findTier(qty, QTY_TIERS));
		if (byValue.saving <= 0 && byQty.saving <= 0) {
			return {
				chosen: 'none',
				percent: 0,
				saving: 0,
				difference: 0,
				reason: subtotal <= 0 ? 'Đơn hàng chưa có sản phẩm hợp lệ.' : 'Chưa đạt bậc chiết khấu nào.',
				byValue: byValue,
				byQuantity: byQty
			};
		}
		var pickValue = byValue.saving >= byQty.saving;
		var win = pickValue ? byValue : byQty;
		var lose = pickValue ? byQty : byValue;
		var difference = win.saving - lose.saving;
		var reason;
		if (difference === 0) {
			reason = 'Hai cách cùng tiết kiệm ' + money(win.saving) + ' đ, mặc định chọn theo giá trị.';
		} else if (pickValue) {
			reason = 'Chiết khấu theo giá trị (' + byValue.percent + '%) tiết kiệm hơn ' + money(difference) + ' đ so với theo số lượng (' + byQty.percent + '%).';
		} else {
			reason = 'Chiết khấu theo số lượng (' + byQty.percent + '%) tiết kiệm hơn ' + money(difference) + ' đ so với theo giá trị (' + byValue.percent + '%).';
		}
		return {
			chosen: pickValue ? 'value' : 'quantity',
			percent: win.percent,
			saving: win.saving,
			difference: difference,
			reason: reason,
			byValue: byValue,
			byQuantity: byQty
		};
	}

	function readLines() {
		var subtotal = 0;
		var qty = 0;
		var vat = 0;
		var sawVat = false;
		$('#lineItemTab tr.lineItemRow').each(function () {
			var $row = $(this);
			var q = parseMoney($row.find('input.qty, .qty').first().val());
			var price = parseMoney($row.find('input.listPrice').first().val());
			if (q > 0 && price >= 0) {
				subtotal += q * price;
				qty += q;
			}
			if (!sawVat) {
				var tax = parseFloat($row.find('.taxPercentage').first().val());
				if (!isNaN(tax) && tax > 0) {
					vat = tax;
					sawVat = true;
				}
			}
		});
		return { subtotal: subtotal, qty: qty, vat: vat };
	}

	function segmentFromCode(code, channel) {
		code = String(code || '').toUpperCase();
		if (code.indexOf('MIUTEA') === 0) {
			return 'miutea';
		}
		if (code.indexOf('KL') === 0) {
			return 'kl';
		}
		if (code.indexOf('TUIBAO') === 0 || channel === 'tuibao') {
			return 'tuibao';
		}
		return channel === 'tuibao' ? 'tuibao' : '';
	}

	function currentChannel() {
		if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.getPriceChannel === 'function') {
			return window.MkInventoryOdooEdit.getPriceChannel();
		}
		return window.MK_PRICE_CHANNEL === 'tuibao' ? 'tuibao' : 'retail';
	}

	function ensurePanel() {
		var rail = document.getElementById('mkQtQuoteRail');
		if (!rail || document.getElementById('mkQtSmartDiscount')) {
			return;
		}
		var box = document.createElement('section');
		box.id = 'mkQtSmartDiscount';
		box.className = 'mk-qt-smart';
		box.innerHTML =
			'<h2 class="mk-qt-smart__title">Chiết khấu theo nhóm khách</h2>' +
			'<p class="mk-qt-smart__sub" id="mkQtSmartSub">Chọn khách hàng để áp quy tắc.</p>' +
			'<div class="mk-qt-smart__cards" id="mkQtSmartCards"></div>' +
			'<p class="mk-qt-smart__reason" id="mkQtSmartReason"></p>' +
			'<dl class="mk-qt-smart__sum" id="mkQtSmartSum"></dl>';
		rail.insertBefore(box, rail.firstChild);
	}

	function card(title, percent, saving, on) {
		return (
			'<article class="mk-qt-smart__card' + (on ? ' is-on' : '') + '">' +
			'<div class="mk-qt-smart__card-h"><span>' + title + '</span>' +
			(on ? '<em>Đang chọn</em>' : '') +
			'</div>' +
			'<strong>' + percent + '%</strong>' +
			'<span>Tiết kiệm ' + money(saving) + ' đ</span>' +
			'</article>'
		);
	}

	function paint(state) {
		ensurePanel();
		var sub = document.getElementById('mkQtSmartSub');
		var cards = document.getElementById('mkQtSmartCards');
		var reason = document.getElementById('mkQtSmartReason');
		var sum = document.getElementById('mkQtSmartSum');
		if (!sub || !cards || !reason || !sum) {
			return;
		}
		var lines = state.lines;
		var after = Math.max(0, lines.subtotal - state.saving);
		var vatAmt = Math.round((after * lines.vat) / 100);
		var total = after + vatAmt;
		sub.textContent = state.subtitle;
		cards.innerHTML = state.cardsHtml;
		reason.textContent = state.reason;
		sum.innerHTML =
			'<div><dt>Tạm tính</dt><dd>' + money(lines.subtotal) + ' đ</dd></div>' +
			'<div><dt>Chiết khấu</dt><dd>- ' + money(state.saving) + ' đ</dd></div>' +
			'<div><dt>Sau chiết khấu</dt><dd>' + money(after) + ' đ</dd></div>' +
			'<div><dt>VAT ' + lines.vat + '%</dt><dd>' + money(vatAmt) + ' đ</dd></div>' +
			'<div class="is-total"><dt>Tổng thanh toán</dt><dd>' + money(total) + ' đ</dd></div>';
	}

	function applyPercent(percent) {
		if (applying) {
			return;
		}
		applying = true;
		var changed = false;
		try {
			$('#lineItemTab tr.lineItemRow').each(function () {
				var $row = $(this);
				var q = parseMoney($row.find('input.qty, .qty').first().val());
				if (!(q > 0)) {
					return;
				}
				var current = parseMoney($row.find('.discount_percentage').first().val());
				if (current === percent && String($row.data('mkDiscountPct')) === String(percent)) {
					return;
				}
				changed = true;
				var qtyName = String($row.find('input.qty, .qty').first().attr('name') || '');
				var rowMatch = qtyName.match(/(\d+)$/);
				var rowNo = rowMatch ? rowMatch[1] : '';
				$row.data('mkDiscountMode', 'percentage');
				$row.data('mkDiscUserSet', true);
				$row.data('mkDiscountPct', percent);
				var $pct = $row.find('.discount_percentage').first();
				if (!$pct.length && rowNo) {
					$pct = $('<input type="hidden" class="discount_percentage discountVal" />')
						.attr('id', 'discount_percentage' + rowNo)
						.attr('name', 'discount_percentage' + rowNo);
					$row.append($pct);
				}
				$pct.val(String(percent));
				var $type = $row.find('input.discount_type, .discount_type').first();
				if (!$type.length && rowNo) {
					$type = $('<input type="hidden" class="discount_type" />')
						.attr('id', 'discount_type' + rowNo)
						.attr('name', 'discount_type' + rowNo);
					$row.append($type);
				}
				$type.val(percent > 0 ? 'percentage' : 'zero');
				$row.find('input.discounts[data-discount-type="percentage"]').prop('checked', percent > 0);
				$row.find('input.discounts[data-discount-type="zero"]').prop('checked', percent <= 0);
				$row.find('input.discounts[data-discount-type="amount"]').prop('checked', false);
				var $custom = $row.find('.mk-inv-discount-custom, .mk-inv-discount-pct').first();
				if ($custom.length && document.activeElement !== $custom[0]) {
					$custom.val(String(percent)).trigger('change');
				}
			});
			if (changed && window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.refreshTotals === 'function') {
				window.MkInventoryOdooEdit.refreshTotals($('#EditView'));
			}
		} finally {
			applying = false;
		}
	}

	function refresh() {
		if (applying) {
			return;
		}
		ensurePanel();
		var $display = $('[name="contact_id_display"]').first();
		var code = String($display.data('mkCustomerCode') || resolved.code || '');
		var channel = currentChannel();
		var segment = segmentFromCode(code, channel) || resolved.segment;
		var lines = readLines();
		var both = compare(lines.subtotal, lines.qty);
		var state = { lines: lines, saving: 0, reason: '', subtitle: '', cardsHtml: '' };

		if (!segment) {
			state.subtitle = 'Chọn khách KL, Miutea hoặc Tuibao để áp chiết khấu.';
			state.reason = 'Chưa có nhóm khách.';
			state.cardsHtml = '';
			paint(state);
			return;
		}

		if (segment === 'tuibao') {
			state.subtitle = 'Tuibao — một bảng giá, chiết khấu nhập tay trên từng dòng.';
			state.reason = 'Không tự so sánh bậc. Đơn giá lấy giá Tuibao.';
			state.cardsHtml = '<article class="mk-qt-smart__card is-on"><div class="mk-qt-smart__card-h"><span>Chiết khấu tay</span><em>Tuibao</em></div><strong>—</strong><span>Nhân viên nhập trên dòng hàng</span></article>';
			state.saving = 0;
			paint(state);
			return;
		}

		if (segment === 'miutea') {
			var only = both.byValue;
			state.saving = only.saving;
			state.subtitle = 'Miutea — một bảng giá, chỉ chiết khấu theo tổng giá trị đơn.';
			state.reason = only.saving > 0
				? 'Áp ' + only.percent + '% theo tổng giá trị ' + money(lines.subtotal) + ' đ.'
				: (lines.subtotal <= 0 ? 'Đơn hàng chưa có sản phẩm hợp lệ.' : 'Chưa đạt bậc chiết khấu nào.');
			state.cardsHtml = card('Theo giá trị đơn', only.percent, only.saving, true);
			paint(state);
			applyPercent(only.percent);
			return;
		}

		state.saving = both.saving;
		state.subtitle = 'KL — so chiết khấu theo số lượng và theo giá trị, lấy mức tiết kiệm hơn.';
		state.reason = both.reason;
		state.cardsHtml =
			card('Theo giá trị đơn', both.byValue.percent, both.byValue.saving, both.chosen === 'value') +
			card('Theo số lượng', both.byQuantity.percent, both.byQuantity.saving, both.chosen === 'quantity');
		paint(state);
		applyPercent(both.percent);
	}

	function schedule() {
		clearTimeout(timer);
		timer = setTimeout(refresh, 180);
	}

	function loadCode() {
		var contactId = parseInt($('[name="contact_id"]').val(), 10) || 0;
		var accountId = parseInt($('[name="account_id"]').val(), 10) || 0;
		var scId = parseInt($('[name="mk_servicecontract_id"]').val(), 10) || parseInt($('[name="servicecontract_id"]').val(), 10) || 0;
		if (!(window.app && app.request && app.request.post)) {
			schedule();
			return;
		}
		if (contactId <= 0 && accountId <= 0 && scId <= 0) {
			schedule();
			return;
		}
		app.request.post({
			data: {
				module: 'Quotes',
				action: 'CustomerCode',
				contact_id: contactId,
				account_id: accountId,
				servicecontract_id: scId
			}
		}).then(function (err, res) {
			if (!err && res) {
				resolved.code = res.customer_code || '';
				resolved.segment = res.segment || '';
				$('[name="contact_id_display"]').first().data('mkCustomerCode', resolved.code);
			}
			refresh();
		}, function () {
			refresh();
		});
	}

	$(function () {
		if (!$('#mkQtCreateWorkspace').length && !$('#EditView').length) {
			return;
		}
		ensurePanel();
		$(document).on('mkQuoteDiscountRefresh', function () {
			resolved = { code: '', segment: '' };
			loadCode();
		});
		$(document).on('change.mkSmartDisc input.mkSmartDisc', '#lineItemTab .qty, #lineItemTab .listPrice, #lineItemTab .taxPercentage', function () {
			if (applying) {
				return;
			}
			schedule();
		});
		setTimeout(loadCode, 600);
	});
})(jQuery);
