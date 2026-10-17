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
	var MIUTEA_VALUE_TIERS = VALUE_TIERS.slice();

	function formatTierLabel(tier, higherMin, kind) {
		var pct = tier.percent;
		if (kind === 'qty') {
			if (higherMin == null) {
				return 'Từ ' + formatQty(tier.min) + ' sản phẩm trở lên → CK ' + pct + '%';
			}
			return 'Từ ' + formatQty(tier.min) + ' đến dưới ' + formatQty(higherMin) + ' sản phẩm → CK ' + pct + '%';
		}
		var from = money(tier.min);
		if (higherMin == null) {
			return 'Từ ' + from + ' đ trở lên → CK ' + pct + '%';
		}
		return 'Từ ' + from + ' đ đến dưới ' + money(higherMin) + ' đ → CK ' + pct + '%';
	}

	function applyTierList(raw, kind) {
		var rows = (raw || []).map(function (row) {
			return {
				min: Number(row.min) || 0,
				percent: Number(row.percent) || 0
			};
		}).sort(function (a, b) {
			return b.min - a.min;
		});
		if (!rows.length) {
			return null;
		}
		return rows.map(function (tier, index) {
			var higher = index > 0 ? rows[index - 1].min : null;
			return {
				min: tier.min,
				percent: tier.percent,
				label: formatTierLabel(tier, higher, kind)
			};
		});
	}

	function loadDiscountPolicy() {
		if (!(window.app && app.request && app.request.post)) {
			return;
		}
		app.request.post({
			data: { module: 'ProductsServices', action: 'PriceSetup', mode: 'get' }
		}).then(function (err, res) {
			if (err || !res) {
				return;
			}
			var valueTiers = applyTierList(res.kl_value, 'money');
			var qtyTiers = applyTierList(res.kl_qty, 'qty');
			var miuteaTiers = applyTierList(res.miutea_value, 'money');
			if (valueTiers) {
				VALUE_TIERS = valueTiers;
			}
			if (qtyTiers) {
				QTY_TIERS = qtyTiers;
			}
			if (miuteaTiers) {
				MIUTEA_VALUE_TIERS = miuteaTiers;
			}
			refresh();
		});
	}

	var applying = false;
	var timer = null;
	var resolved = { code: '', segment: '' };
	var lastPaint = null;

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
		if (channel === 'miutea') {
			return 'miutea';
		}
		return '';
	}

	function currentChannel() {
		if (window.MkInventoryOdooEdit && typeof window.MkInventoryOdooEdit.getPriceChannel === 'function') {
			return window.MkInventoryOdooEdit.getPriceChannel();
		}
		if (window.MK_PRICE_CHANNEL === 'tuibao' || window.MK_PRICE_CHANNEL === 'miutea') {
			return window.MK_PRICE_CHANNEL;
		}
		return 'retail';
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
				'<div class="mk-qt-smart__order" id="mkQtSmartOrder">' +
					'<label class="mk-qt-smart__order-label" for="mkQtOrderDiscountPreset">Chiết khấu tổng đơn</label>' +
					'<div class="mk-qt-smart__order-row">' +
						'<select id="mkQtOrderDiscountPreset" aria-label="Chọn phần trăm chiết khấu tổng đơn">' +
							'<option value="0">0%</option>' +
							'<option value="5">5%</option>' +
							'<option value="8">8%</option>' +
							'<option value="10">10%</option>' +
							'<option value="15">15%</option>' +
							'<option value="20">20%</option>' +
							'<option value="custom">Nhập %</option>' +
						'</select>' +
						'<input type="text" inputmode="decimal" id="mkQtOrderDiscountValue" autocomplete="off" placeholder="Nhập % khác" />' +
					'</div>' +
				'</div>' +
				'<dl class="mk-qt-smart__sum" id="mkQtSmartSum"></dl>';
		}
		if (box && !document.getElementById('mkQtSmartOrder')) {
			var order = document.createElement('div');
			order.id = 'mkQtSmartOrder';
			order.className = 'mk-qt-smart__order';
			order.innerHTML =
				'<label class="mk-qt-smart__order-label" for="mkQtOrderDiscountPreset">Chiết khấu tổng đơn</label>' +
				'<div class="mk-qt-smart__order-row">' +
					'<select id="mkQtOrderDiscountPreset" aria-label="Chọn phần trăm chiết khấu tổng đơn">' +
						'<option value="0">0%</option>' +
						'<option value="5">5%</option>' +
						'<option value="8">8%</option>' +
						'<option value="10">10%</option>' +
						'<option value="15">15%</option>' +
						'<option value="20">20%</option>' +
						'<option value="custom">Nhập %</option>' +
					'</select>' +
					'<input type="text" inputmode="decimal" id="mkQtOrderDiscountValue" autocomplete="off" placeholder="Nhập % khác" />' +
				'</div>';
			var sumNode = box.querySelector('#mkQtSmartSum');
			if (sumNode) {
				box.insertBefore(order, sumNode);
			} else {
				box.appendChild(order);
			}
		}
		syncOrderInputsFromForm();
		if (rail && box.parentNode !== rail) {
			rail.insertBefore(box, rail.firstChild);
		} else if (rail && rail.firstElementChild !== box) {
			rail.insertBefore(box, rail.firstChild);
		}
		return box;
	}

	function htmlText(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
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
			'<div class="mk-qt-smart__row"><span>Bậc áp dụng</span><b>' + htmlText(offer.label) + '</b></div>' +
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

	var ORDER_PRESETS = ['0', '5', '8', '10', '15', '20'];

	function orderPercentFromInput() {
		var valueEl = document.getElementById('mkQtOrderDiscountValue');
		if (!valueEl || String(valueEl.value).trim() === '') {
			return null;
		}
		var value = parseMoney(valueEl.value);
		if (value < 0) {
			value = 0;
		}
		if (value > 100) {
			value = 100;
		}
		return value;
	}

	function readOrderDiscount() {
		var value = orderPercentFromInput();
		return { mode: 'percentage', value: value == null ? 0 : value };
	}

	function syncPreset(percent) {
		var sel = document.getElementById('mkQtOrderDiscountPreset');
		if (!sel) {
			return;
		}
		var key = String(percent);
		if (ORDER_PRESETS.indexOf(key) >= 0) {
			sel.value = key;
			return;
		}
		sel.value = percent > 0 ? 'custom' : '0';
	}

	function orderDiscountMoney(base) {
		var discount = readOrderDiscount();
		base = Math.max(0, Number(base) || 0);
		if (!(discount.value > 0) || !(base > 0)) {
			return 0;
		}
		return Math.min(base, Math.round((base * discount.value) / 100));
	}

	function syncOrderInputsFromForm() {
		var valueEl = document.getElementById('mkQtOrderDiscountValue');
		var presetEl = document.getElementById('mkQtOrderDiscountPreset');
		if (!valueEl || !presetEl || valueEl.getAttribute('data-mk-synced') === '1') {
			return;
		}
		var percent = null;
		var saw = false;
		var mixed = false;
		$('#lineItemTab tr.lineItemRow').each(function () {
			var q = parseMoney($(this).find('input.qty, .qty').first().val());
			if (!(q > 0)) {
				return;
			}
			var linePercent = parseMoney($(this).find('.discount_percentage').first().val());
			if (!saw) {
				percent = linePercent;
				saw = true;
			} else if (linePercent !== percent) {
				mixed = true;
			}
		});
		if ((!saw || mixed) && String($('#discount_type_final').val() || '') === 'percentage') {
			percent = parseMoney($('#discount_percentage_final').val());
			saw = percent > 0;
			mixed = false;
		}
		if (saw && !mixed && percent > 0) {
			valueEl.value = String(percent);
			syncPreset(percent);
		}
		valueEl.setAttribute('data-mk-synced', '1');
	}

	function clearFinalDiscount() {
		var type = String($('#discount_type_final').val() || 'zero');
		var pct = parseMoney($('#discount_percentage_final').val());
		var amt = parseMoney($('#discount_amount_final').val());
		if ((type === 'zero' || type === '') && !(pct > 0) && !(amt > 0)) {
			return;
		}
		var $ui = $('#finalDiscountUI');
		if (!$ui.length) {
			return;
		}
		$ui.find('input.finalDiscounts').prop('checked', false);
		$ui.find('input.finalDiscounts[data-discount-type="zero"]').prop('checked', true);
		$('#discount_type_final').val('zero');
		$('#discount_percentage_final').val('');
		$('#discount_amount_final').val('');
		var inst = (typeof Quotes_Edit_Js !== 'undefined' && Quotes_Edit_Js.getInstance) ? Quotes_Edit_Js.getInstance() : null;
		if (!inst || typeof inst.finalDiscountChangeActions !== 'function') {
			return;
		}
		if (!inst.finalDiscountUIEle || !inst.finalDiscountUIEle.length) {
			inst.finalDiscountUIEle = $ui;
		}
		inst.finalDiscountChangeActions();
	}

	function applyOrderDiscount() {
		var discount = readOrderDiscount();
		applyPercent(discount.value);
		if (discount.value > 0) {
			clearFinalDiscount();
		}
	}

	function paint(state) {
		ensurePanel();
		lastPaint = state;
		var sub = document.getElementById('mkQtSmartSub');
		var cards = document.getElementById('mkQtSmartCards');
		var decision = document.getElementById('mkQtSmartDecision');
		var sum = document.getElementById('mkQtSmartSum');
		if (!sub || !cards || !decision || !sum) {
			return;
		}
		var lines = state.lines;
		var base = Math.max(0, lines.subtotal - state.saving);
		var orderOff = orderDiscountMoney(base);
		var after = Math.max(0, base - orderOff);
		sub.textContent = state.subtitle;
		cards.innerHTML = state.cardsHtml;
		decision.innerHTML = state.decisionHtml || '';
		decision.className = 'mk-qt-smart__decision' + (state.decisionHtml ? ' is-on' : '');
		sum.innerHTML =
			'<div><dt>Tạm tính</dt><dd>' + money(lines.subtotal) + ' đ</dd></div>' +
			'<div><dt>Chiết khấu nguyên liệu</dt><dd class="is-off">- ' + money(state.saving) + ' đ</dd></div>' +
			'<div><dt>Chiết khấu tổng đơn</dt><dd class="is-off">- ' + money(orderOff) + ' đ</dd></div>' +
			'<div class="is-total"><dt>Sau chiết khấu</dt><dd>' + money(after) + ' đ</dd></div>';
	}

	function tierLabel(tierKey) {
		var labels = {
			lt_1m: 'Giá dưới 1 triệu',
			gte_1m: 'Giá trên 1 triệu',
			gte_3m: 'Giá trên 3 triệu',
			gte_5m: 'Giá trên 5 triệu',
			gte_7m: 'Giá trên 7 triệu'
		};
		return labels[tierKey] || 'Giá dưới 1 triệu';
	}

	function retailQuoteFacts(lines) {
		var engine = window.MkInventoryOdooEdit;
		var $form = $('#EditView');
		var invoiceTier = engine && engine.currentRetailInvoiceTier ? engine.currentRetailInvoiceTier($form) : 'lt_1m';
		var listBase = 0;
		var invoiceSaving = 0;
		var qtySaving = 0;
		var qtyLabels = [];
		var sawQtyRules = false;
		$('#lineItemTab tr.lineItemRow').each(function () {
			var $row = $(this);
			var q = parseMoney($row.find('input.qty, .qty').first().val());
			if (!(q > 0)) {
				return;
			}
			var $opt = $row.find('.mk-inv-product-select option:selected').first();
			var meta = engine && engine.getProductMetaForRow ? engine.getProductMetaForRow($row) : {
				price_lt_1m: $opt.attr('data-price-lt-1m'),
				price_gte_1m: $opt.attr('data-price-gte-1m'),
				price_gte_3m: $opt.attr('data-price-gte-3m'),
				price_gte_5m: $opt.attr('data-price-gte-5m'),
				price_gte_7m: $opt.attr('data-price-gte-7m'),
				price: $opt.attr('data-price'),
				unit: $opt.attr('data-unit') || '',
				kl_qty_rules: $opt.attr('data-kl-qty-rules')
			};
			var lineUnit = String($row.find('.mk-inv-unit-select').first().val() || '').trim();
			if (lineUnit) {
				meta.unit = lineUnit;
			}
			var base = parseMoney(meta.price_lt_1m);
			if (!(base > 0)) {
				base = parseMoney(meta.price);
			}
			var unit = parseMoney($row.find('input.listPrice').first().val());
			if (!(base > 0)) {
				base = unit;
			}
			var invoicePrice = base;
			var qtyPrice = 0;
			if (engine && engine.priceForTier) {
				var fromInvoice = engine.priceForTier(meta, invoiceTier);
				if (fromInvoice > 0) {
					invoicePrice = fromInvoice;
				}
				if (engine.qtyTierForLine) {
					var qtyTier = engine.qtyTierForLine(meta, q);
					qtyPrice = qtyTier ? engine.priceForTier(meta, qtyTier) : 0;
				}
				if (engine.describeQtyTier) {
					var step = engine.describeQtyTier(meta, q);
					if (step) {
						sawQtyRules = true;
						if (step !== 'Chưa tới mốc số lượng' && qtyLabels.indexOf(step) < 0) {
							qtyLabels.push(step);
						}
					}
				}
			}
			var qtyPick = qtyPrice > 0 && qtyPrice < invoicePrice ? qtyPrice : invoicePrice;
			listBase += q * base;
			invoiceSaving += Math.max(0, base - invoicePrice) * q;
			qtySaving += Math.max(0, base - qtyPick) * q;
		});
		if (!(listBase > 0)) {
			listBase = lines.subtotal;
		}
		var chosenSaving = Math.max(invoiceSaving, qtySaving);
		var why = 'Giá dưới 1 triệu là giá gốc. ';
		if (qtySaving > invoiceSaving) {
			why += 'Một số món đủ mốc số lượng nên lấy cột giá thấp hơn cột theo tổng đơn. Đã giảm ' + money(chosenSaving) + ' đ so với giá dưới 1 triệu.';
		} else if (chosenSaving > 0) {
			why += 'Đang dùng ' + tierLabel(invoiceTier) + ' theo tổng đơn. Đã giảm ' + money(chosenSaving) + ' đ so với giá dưới 1 triệu.';
		} else {
			why += 'Đơn chưa chạm mốc nào, hoặc các món này không giảm khi tổng đơn và số lượng tăng.';
		}
		return {
			listBase: listBase,
			invoiceSaving: invoiceSaving,
			qtySaving: qtySaving,
			chosenSaving: chosenSaving,
			invoiceLabel: tierLabel(invoiceTier),
			qtyLabel: qtyLabels.length ? qtyLabels.join('; ') : (sawQtyRules ? 'Chưa tới mốc số lượng' : 'Không có mốc số lượng'),
			why: why
		};
	}

	function rowSkipsDiscount($row) {
		var $opt = $row.find('.mk-inv-product-select option:selected').first();
		return String($opt.attr('data-mk-no-discount') || '') === '1';
	}

	function applyPercent(percent, opts) {
		opts = opts || {};
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
				var linePercent = percent;
				if (opts.respectNoDiscount && rowSkipsDiscount($row)) {
					linePercent = 0;
				}
				var current = parseMoney($row.find('.discount_percentage').first().val());
				if (current === linePercent && String($row.data('mkDiscountPct')) === String(linePercent)) {
					return;
				}
				changed = true;
				var qtyName = String($row.find('input.qty, .qty').first().attr('name') || '');
				var rowMatch = qtyName.match(/(\d+)$/);
				var rowNo = rowMatch ? rowMatch[1] : '';
				$row.data('mkDiscountMode', 'percentage');
				$row.data('mkDiscUserSet', true);
				$row.data('mkDiscountPct', linePercent);
				var $pct = $row.find('.discount_percentage').first();
				if (!$pct.length && rowNo) {
					$pct = $('<input type="hidden" class="discount_percentage discountVal" />')
						.attr('id', 'discount_percentage' + rowNo)
						.attr('name', 'discount_percentage' + rowNo);
					$row.append($pct);
				}
				$pct.val(String(linePercent));
				var $type = $row.find('input.discount_type, .discount_type').first();
				if (!$type.length && rowNo) {
					$type = $('<input type="hidden" class="discount_type" />')
						.attr('id', 'discount_type' + rowNo)
						.attr('name', 'discount_type' + rowNo);
					$row.append($type);
				}
				$type.val(linePercent > 0 ? 'percentage' : 'zero');
				$row.find('input.discounts[data-discount-type="percentage"]').prop('checked', linePercent > 0);
				$row.find('input.discounts[data-discount-type="zero"]').prop('checked', linePercent <= 0);
				$row.find('input.discounts[data-discount-type="amount"]').prop('checked', false);
				var $custom = $row.find('.mk-inv-discount-custom, .mk-inv-discount-pct').first();
				if ($custom.length && document.activeElement !== $custom[0]) {
					$custom.val(String(linePercent)).trigger('change');
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

		if (segment === 'kl') {
			var retail = retailQuoteFacts(lines);
			state.lines = { subtotal: retail.listBase, qty: lines.qty, vat: lines.vat };
			state.saving = retail.chosenSaving;
			state.subtitle = 'Khách lẻ — so giá theo tổng đơn với giá theo số lượng từng món, lấy giá thấp hơn.';
			state.cardsHtml =
				card('value', state.lines, { label: retail.invoiceLabel, saving: retail.invoiceSaving, percent: 0 }, retail.invoiceSaving >= retail.qtySaving) +
				card('quantity', state.lines, { label: retail.qtyLabel, saving: retail.qtySaving, percent: 0 }, retail.qtySaving > retail.invoiceSaving);
			state.decisionHtml = '<p class="mk-qt-smart__why">' + retail.why + '</p>';
			paint(state);
			var chosen = orderPercentFromInput();
			applyPercent(chosen == null ? 0 : chosen);
			if (chosen != null && chosen > 0) {
				clearFinalDiscount();
			}
			return;
		}

		if (segment === 'miutea') {
			state.subtitle = 'Miutea — một bảng giá, chiết khấu nhập tay trên từng dòng.';
			state.decisionHtml = '<p class="mk-qt-smart__why">Không tự trừ phần trăm. Đơn giá lấy Giá Miutea. Nhân viên nhập chiết khấu trên dòng hàng.</p>';
			paint(state);
			return;
		}

		state.saving = both.saving;
		state.subtitle = 'So hai cách trên đơn này, chọn cách khách trả ít hơn.';
		state.cardsHtml =
			card('value', lines, both.byValue, both.chosen === 'value') +
			card('quantity', lines, both.byQuantity, both.chosen === 'quantity');
		state.decisionHtml = decisionHtml(both);
		paint(state);
		var chosenPercent = orderPercentFromInput();
		applyPercent(chosenPercent == null ? both.percent : chosenPercent);
		if (chosenPercent != null && chosenPercent > 0) {
			clearFinalDiscount();
		}
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
				if (res.segment === 'miutea' && currentChannel() !== 'tuibao' && currentChannel() !== 'miutea') {
					var inv = window.MkInventoryOdooEdit;
					if (inv && typeof inv.setPriceChannel === 'function') {
						inv.setPriceChannel('miutea', { clearScPrefill: true });
					} else {
						window.MK_PRICE_CHANNEL = 'miutea';
					}
					if (inv && typeof inv.applyInvoiceTierPricing === 'function') {
						inv.applyInvoiceTierPricing($('#EditView'), {});
					}
				}
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

	var booted = false;
	function boot() {
		if (booted) {
			return;
		}
		if (!$('#mkQtCreateWorkspace').length && !$('#EditView').length) {
			return;
		}
		booted = true;
		ensurePanel();
		$(document).on('change.mkOrderDisc', '#mkQtOrderDiscountPreset', function () {
			var preset = String(this.value || '0');
			var valueEl = document.getElementById('mkQtOrderDiscountValue');
			if (valueEl && preset !== 'custom') {
				valueEl.value = preset === '0' ? '' : preset;
			}
			if (valueEl) {
				valueEl.setAttribute('data-mk-synced', '1');
				if (preset === 'custom') {
					valueEl.focus();
				}
			}
			applyOrderDiscount();
			if (lastPaint) {
				paint(lastPaint);
			}
		});
		$(document).on('input.mkOrderDisc change.mkOrderDisc', '#mkQtOrderDiscountValue', function () {
			this.setAttribute('data-mk-synced', '1');
			syncPreset(orderPercentFromInput() || 0);
			applyOrderDiscount();
			if (lastPaint) {
				paint(lastPaint);
			}
		});
		loadDiscountPolicy();
		refresh();
		$(document).on('mkQuoteDiscountRefresh', function () {
			resolved = { code: '', segment: '' };
			loadCode();
		});
		$(document).on('change.mkSmartDisc input.mkSmartDisc focusout.mkSmartDisc', '#lineItemTab .qty, #lineItemTab .listPrice, #lineItemTab .taxPercentage, #lineItemTab .mk-inv-product-select', function () {
			if (applying) {
				return;
			}
			schedule();
			setTimeout(schedule, 320);
		});
		$(document).on('click.mkSmartDisc', '#addProductsServices, .mk-inv-add-line-btn', function () {
			setTimeout(schedule, 120);
			setTimeout(schedule, 480);
		});
		var pins = 0;
		var pinTimer = setInterval(function () {
			pins += 1;
			ensurePanel();
			if (pins === 1 || pins === 4 || pins === 8) {
				refresh();
				loadCode();
			}
			if (pins >= 16) {
				clearInterval(pinTimer);
			}
		}, 300);
		setTimeout(loadCode, 600);
	}

	$(boot);
	document.addEventListener('DOMContentLoaded', boot);
	window.addEventListener('load', function () {
		setTimeout(boot, 50);
	});
})(jQuery);
