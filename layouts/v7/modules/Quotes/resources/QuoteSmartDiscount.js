/* Quote smart discount — KL compares value vs quantity, Miutea value only, Tuibao manual. */
(function ($) {
	'use strict';

	var VALUE_TIERS = [
		{ min: 50000000, percent: 12, label: 'Trên 50 triệu → CK 12%' },
		{ min: 20000000, percent: 8, label: '20 – 50 triệu → CK 8%' },
		{ min: 10000000, percent: 5, label: '10 – 20 triệu → CK 5%' },
		{ min: 5000000, percent: 2, label: '5 – 10 triệu → CK 2%' },
		{ min: 0, percent: 0, label: 'Dưới 5 triệu → CK 0%' }
	];
	var QTY_TIERS = [
		{ min: 500, percent: 15, label: 'Trên 500 sp → CK 15%' },
		{ min: 200, percent: 10, label: '200 – 500 sp → CK 10%' },
		{ min: 100, percent: 7, label: '100 – 200 sp → CK 7%' },
		{ min: 50, percent: 5, label: '50 – 100 sp → CK 5%' },
		{ min: 20, percent: 3, label: '20 – 50 sp → CK 3%' },
		{ min: 0, percent: 0, label: 'Dưới 20 sp → CK 0%' }
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

	function offer(base, tier, emptyLabel) {
		var percent = !tier || base <= 0 ? 0 : tier.percent;
		var saving = Math.round((base * percent) / 100);
		return {
			percent: percent,
			saving: saving,
			label: tier && tier.label ? tier.label : emptyLabel
		};
	}

	function compare(subtotal, qty) {
		var byValue = offer(subtotal, findTier(subtotal, VALUE_TIERS), 'Dưới 5 triệu → CK 0%');
		var byQty = offer(subtotal, findTier(qty, QTY_TIERS), 'Dưới 20 sp → CK 0%');
		var pickValue = byValue.saving >= byQty.saving;
		var win = pickValue ? byValue : byQty;
		var difference = Math.abs(byValue.saving - byQty.saving);
		var chosen = 'none';
		var badge = '';
		var extra = '';
		var why = '';
		if (subtotal <= 0) {
			why = 'Đơn hàng chưa có sản phẩm hợp lệ.';
		} else if (byValue.saving <= 0 && byQty.saving <= 0) {
			why = 'Chưa đạt bậc chiết khấu nào. Đơn này vẫn được so sánh: theo giá trị 0%, theo số lượng 0%.';
		} else if (difference === 0) {
			chosen = 'value';
			badge = 'Đã chọn chiết khấu theo GIÁ TRỊ';
			extra = 'Hai cách cùng tiết kiệm ' + money(win.saving) + ' đ, mặc định chọn theo giá trị.';
			why = 'Vì tổng tiền ' + money(subtotal) + ' đ và tổng ' + formatQty(qty) + ' sp cùng đạt bậc ' + byValue.percent + '%.';
		} else if (pickValue) {
			chosen = 'value';
			badge = 'Đã chọn chiết khấu theo GIÁ TRỊ';
			extra = 'Tiết kiệm thêm ' + money(difference) + ' đ so với cách kia';
			why = 'Vì tổng tiền ' + money(subtotal) + ' đ đạt bậc ' + byValue.percent + '%, cao hơn mức ' + byQty.percent + '% theo số lượng (' + formatQty(qty) + ' sp).';
		} else {
			chosen = 'quantity';
			badge = 'Đã chọn chiết khấu theo SỐ LƯỢNG';
			extra = 'Tiết kiệm thêm ' + money(difference) + ' đ so với cách kia';
			why = 'Vì tổng ' + formatQty(qty) + ' sp đạt bậc ' + byQty.percent + '%, cao hơn mức ' + byValue.percent + '% theo giá trị đơn hàng.';
		}
		return {
			chosen: chosen,
			percent: chosen === 'none' ? 0 : win.percent,
			saving: chosen === 'none' ? 0 : win.saving,
			difference: difference,
			badge: badge,
			extra: extra,
			why: why,
			byValue: byValue,
			byQuantity: byQty
		};
	}

	function formatQty(qty) {
		var n = Math.round(Number(qty) || 0);
		return n.toLocaleString('vi-VN');
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
		var info = document.querySelector('#mkQtQuoteRail .mk-qt-rail-quote-info, .mk-qt-rail-quote-info');
		if (!rail && info && info.parentNode) {
			rail = info.parentNode;
		}
		var box = document.getElementById('mkQtSmartDiscount');
		if (!box) {
			box = document.createElement('section');
			box.id = 'mkQtSmartDiscount';
			box.className = 'mk-qt-smart';
			box.setAttribute('aria-label', 'Chiết khấu theo nhóm khách');
			box.innerHTML =
				'<h2 class="mk-qt-smart__title">Chiết khấu thông minh</h2>' +
				'<p class="mk-qt-smart__sub" id="mkQtSmartSub">Chọn khách KL để so sánh hai cách chiết khấu.</p>' +
				'<div class="mk-qt-smart__cards" id="mkQtSmartCards"></div>' +
				'<div class="mk-qt-smart__decision" id="mkQtSmartDecision"></div>' +
				'<dl class="mk-qt-smart__sum" id="mkQtSmartSum"></dl>';
		}
		if (rail && box.parentNode !== rail) {
			rail.insertBefore(box, rail.firstChild);
		} else if (rail && rail.firstElementChild !== box) {
			rail.insertBefore(box, rail.firstChild);
		}
		return box;
	}

	function card(kind, lines, offer, on) {
		var title = kind === 'value' ? 'Theo GIÁ TRỊ' : 'Theo SỐ LƯỢNG';
		var icon = kind === 'value' ? 'fa-file-text-o' : 'fa-cubes';
		var metricLabel = kind === 'value' ? 'Tổng giá trị đơn' : 'Tổng số lượng';
		var metricValue = kind === 'value'
			? money(lines.subtotal) + ' đ'
			: formatQty(lines.qty) + ' sản phẩm';
		return (
			'<article class="mk-qt-smart__card' + (on ? ' is-on' : '') + '">' +
			'<div class="mk-qt-smart__card-h"><i class="fa ' + icon + '" aria-hidden="true"></i><span>' + title + '</span></div>' +
			'<div class="mk-qt-smart__row"><span>' + metricLabel + '</span><b>' + metricValue + '</b></div>' +
			'<div class="mk-qt-smart__row"><span>Bậc áp dụng</span><b>' + offer.label + '</b></div>' +
			'<div class="mk-qt-smart__row"><span>Tiết kiệm</span><b>' + money(offer.saving) + ' đ</b></div>' +
			'</article>'
		);
	}

	function decisionHtml(result) {
		if (!result || !result.badge) {
			return '<p class="mk-qt-smart__why">' + (result && result.why ? result.why : '') + '</p>';
		}
		var extra = result.extra || '';
		var extraHtml = extra.replace(
			/(Tiết kiệm thêm )([\d.\s]+)( đ)/,
			'$1<strong>$2</strong>$3'
		);
		return (
			'<div class="mk-qt-smart__badge"><i class="fa fa-star" aria-hidden="true"></i> ' + result.badge + '</div>' +
			'<p class="mk-qt-smart__extra">' + extraHtml + '</p>' +
			'<p class="mk-qt-smart__why">' + result.why + '</p>'
		);
	}

	function paint(state) {
		ensurePanel();
		var sub = document.getElementById('mkQtSmartSub');
		var cards = document.getElementById('mkQtSmartCards');
		var decision = document.getElementById('mkQtSmartDecision');
		var sum = document.getElementById('mkQtSmartSum');
		if (!sub || !cards || !decision || !sum) {
			return;
		}
		var lines = state.lines;
		var after = Math.max(0, lines.subtotal - state.saving);
		var vatAmt = Math.round((after * lines.vat) / 100);
		var total = after + vatAmt;
		sub.textContent = state.subtitle;
		cards.innerHTML = state.cardsHtml;
		decision.innerHTML = state.decisionHtml || '';
		decision.className = 'mk-qt-smart__decision' + (state.decisionHtml ? ' is-on' : '');
		sum.innerHTML =
			'<div><dt>Tạm tính</dt><dd>' + money(lines.subtotal) + ' đ</dd></div>' +
			'<div><dt>Chiết khấu</dt><dd class="is-off">- ' + money(state.saving) + ' đ</dd></div>' +
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
		var state = { lines: lines, saving: 0, subtitle: '', cardsHtml: '', decisionHtml: '' };

		if (!segment) {
			state.subtitle = 'Chọn khách KL, Miutea hoặc Tuibao để áp chiết khấu.';
			state.decisionHtml = '<p class="mk-qt-smart__why">Chưa có nhóm khách. Chọn khách mã KL để thấy lý do chọn cách rẻ hơn.</p>';
			paint(state);
			return;
		}

		if (segment === 'tuibao') {
			state.subtitle = 'Tuibao — một bảng giá, chiết khấu nhập tay trên từng dòng.';
			state.decisionHtml = '<p class="mk-qt-smart__why">Không tự so sánh bậc. Đơn giá lấy giá Tuibao. Nhân viên nhập chiết khấu trên dòng hàng.</p>';
			paint(state);
			return;
		}

		if (segment === 'miutea') {
			var only = both.byValue;
			state.saving = only.saving;
			state.subtitle = 'Miutea — chỉ chiết khấu theo tổng giá trị đơn.';
			state.cardsHtml = card('value', lines, only, true);
			state.decisionHtml = '<p class="mk-qt-smart__why">' + (only.saving > 0
				? 'Áp ' + only.percent + '% theo tổng giá trị ' + money(lines.subtotal) + ' đ. Không so với chiết khấu theo số lượng.'
				: (lines.subtotal <= 0 ? 'Đơn hàng chưa có sản phẩm hợp lệ.' : 'Chưa đạt bậc chiết khấu theo giá trị.')) + '</p>';
			paint(state);
			applyPercent(only.percent);
			return;
		}

		state.saving = both.saving;
		state.subtitle = 'So hai cách trên đơn này, chọn cách khách trả ít hơn.';
		state.cardsHtml =
			card('value', lines, both.byValue, both.chosen === 'value') +
			card('quantity', lines, both.byQuantity, both.chosen === 'quantity');
		state.decisionHtml = decisionHtml(both);
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

	window.MkQuoteSmartDiscount = {
		place: ensurePanel,
		refresh: refresh
	};

	$(function () {
		if (!$('#mkQtCreateWorkspace').length && !$('#EditView').length) {
			return;
		}
		ensurePanel();
		refresh();
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
		var pins = 0;
		var pinTimer = setInterval(function () {
			pins += 1;
			ensurePanel();
			if (pins === 1 || pins === 4) {
				refresh();
			}
			if (pins >= 16) {
				clearInterval(pinTimer);
			}
		}, 300);
		setTimeout(loadCode, 600);
	});
})(jQuery);
