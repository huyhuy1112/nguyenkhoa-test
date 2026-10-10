/* Hàng hoá — nhập bậc chiết khấu KL / Miutea. Giá Miutea nằm trên từng hàng. */
(function ($) {
	'use strict';

	var LADDERS = [
		{ key: 'kl_value', title: 'Khách lẻ — theo giá trị đơn', unit: 'Từ (đồng)', hint: 'Tổng tiền hàng đạt mốc này thì áp % tương ứng. Báo giá KL so với bậc theo số lượng và lấy cách khách trả ít hơn.' },
		{ key: 'kl_qty', title: 'Khách lẻ — theo số lượng', unit: 'Từ (sản phẩm)', hint: 'Tổng số lượng trên đơn đạt mốc này thì áp % tương ứng.' },
		{ key: 'miutea_value', title: 'Miutea — theo giá trị đơn', unit: 'Từ (đồng)', hint: 'Miutea chỉ dùng bậc theo giá trị. Giá bán nhập ở cột Giá Miutea trên từng hàng hoá.' }
	];

	function parseNum(raw) {
		var s = String(raw || '').trim();
		if (!s) {
			return 0;
		}
		if (s.indexOf(',') >= 0 && s.indexOf('.') >= 0) {
			s = s.replace(/\./g, '').replace(',', '.');
		} else if (/^\d{1,3}(\.\d{3})+$/.test(s)) {
			s = s.replace(/\./g, '');
		} else {
			s = s.replace(/\s/g, '').replace(',', '.');
		}
		var n = parseFloat(s);
		return isNaN(n) ? 0 : n;
	}

	function ensureDialog() {
		var $box = $('#mkPsPriceSetup');
		if ($box.length) {
			return $box;
		}
		var ladders = '';
		for (var i = 0; i < LADDERS.length; i++) {
			var item = LADDERS[i];
			ladders +=
				'<section class="mk-ps-setup__ladder" data-ladder="' + item.key + '">' +
				'<h3>' + item.title + '</h3>' +
				'<p class="mk-ps-setup__hint">' + item.hint + '</p>' +
				'<table><thead><tr><th>' + item.unit + '</th><th>% chiết khấu</th><th></th></tr></thead>' +
				'<tbody id="mkPsLadder_' + item.key + '"></tbody></table>' +
				'<button type="button" class="mk-ps-setup__add" data-add="' + item.key + '">Thêm bậc</button>' +
				'</section>';
		}
		$box = $(
			'<div id="mkPsPriceSetup" class="mk-ps-setup" hidden>' +
			'<div class="mk-ps-setup__backdrop" data-close="1"></div>' +
			'<div class="mk-ps-setup__panel" role="dialog" aria-modal="true" aria-labelledby="mkPsSetupTitle">' +
			'<header class="mk-ps-setup__head">' +
			'<h2 id="mkPsSetupTitle">Bảng giá và chiết khấu</h2>' +
			'<button type="button" class="mk-ps-setup__x" data-close="1" aria-label="Đóng">×</button>' +
			'</header>' +
			'<div class="mk-ps-setup__body">' +
			'<p class="mk-ps-setup__lead">Nhập bậc chiết khấu tại đây. Giá Tuibao và Giá Miutea nhập trên từng hàng hoá. Tuibao không dùng bậc tự động — nhân viên nhập chiết khấu trên dòng báo giá.</p>' +
			ladders +
			'<p class="mk-ps-setup__error" id="mkPsSetupError" hidden></p>' +
			'</div>' +
			'<footer class="mk-ps-setup__foot">' +
			'<button type="button" class="mk-ps-setup__btn" data-close="1">Đóng</button>' +
			'<button type="button" class="mk-ps-setup__btn mk-ps-setup__btn--ok" id="mkPsSetupSave">Lưu bậc chiết khấu</button>' +
			'</footer></div></div>'
		);
		$('body').append($box);
		$box.on('click', '[data-close]', function () {
			closeDialog();
		});
		$box.on('click', '[data-add]', function () {
			appendRow($(this).attr('data-add'), { min: '', percent: '' });
		});
		$box.on('click', '[data-remove]', function () {
			$(this).closest('tr').remove();
		});
		$('#mkPsSetupSave').on('click', savePolicy);
		$(document).on('keydown.mkPsSetup', function (e) {
			if (e.key === 'Escape' && !$box.prop('hidden')) {
				closeDialog();
			}
		});
		return $box;
	}

	function rowHtml(row) {
		var min = row && row.min !== undefined && row.min !== null ? row.min : '';
		var pct = row && row.percent !== undefined && row.percent !== null ? row.percent : '';
		return (
			'<tr>' +
			'<td><input class="mk-ps-tier-min" type="text" inputmode="decimal" value="' + escapeAttr(min) + '" /></td>' +
			'<td><input class="mk-ps-tier-pct" type="text" inputmode="decimal" value="' + escapeAttr(pct) + '" /></td>' +
			'<td><button type="button" class="mk-ps-setup__remove" data-remove="1">Xoá</button></td>' +
			'</tr>'
		);
	}

	function escapeAttr(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/</g, '&lt;');
	}

	function appendRow(key, row) {
		$('#mkPsLadder_' + key).append(rowHtml(row));
	}

	function paint(policy) {
		policy = policy || {};
		var canEdit = String(policy.can_edit) === '1' || policy.can_edit === 1 || policy.can_edit === true;
		for (var i = 0; i < LADDERS.length; i++) {
			var key = LADDERS[i].key;
			var rows = policy[key] || [];
			var $body = $('#mkPsLadder_' + key);
			$body.empty();
			if (!rows.length) {
				appendRow(key, { min: 0, percent: 0 });
			} else {
				for (var r = 0; r < rows.length; r++) {
					appendRow(key, rows[r]);
				}
			}
		}
		$('#mkPsPriceSetup').find('input, [data-add], [data-remove]').prop('disabled', !canEdit);
		$('#mkPsSetupSave').prop('hidden', !canEdit);
		$('#mkPsSetupError').prop('hidden', true).text('');
	}

	function collect() {
		var policy = {};
		for (var i = 0; i < LADDERS.length; i++) {
			var key = LADDERS[i].key;
			policy[key] = [];
			$('#mkPsLadder_' + key).find('tr').each(function () {
				policy[key].push({
					min: parseNum($(this).find('.mk-ps-tier-min').val()),
					percent: parseNum($(this).find('.mk-ps-tier-pct').val())
				});
			});
		}
		return policy;
	}

	function showError(message) {
		$('#mkPsSetupError').text(message || 'Không lưu được.').prop('hidden', false);
	}

	function openDialog() {
		ensureDialog().prop('hidden', false);
		$('#mkPsSetupError').prop('hidden', true).text('');
		if (!(window.app && app.request && app.request.post)) {
			showError('Không gọi được CRM.');
			return;
		}
		app.request.post({
			data: { module: 'ProductsServices', action: 'PriceSetup', mode: 'get' }
		}).then(function (err, res) {
			if (err) {
				showError(typeof err === 'string' ? err : 'Không tải được bậc chiết khấu.');
				return;
			}
			paint(res || {});
		}, function () {
			showError('Không tải được bậc chiết khấu.');
		});
	}

	function closeDialog() {
		$('#mkPsPriceSetup').prop('hidden', true);
	}

	function savePolicy() {
		var $btn = $('#mkPsSetupSave');
		$btn.prop('disabled', true);
		app.request.post({
			data: {
				module: 'ProductsServices',
				action: 'PriceSetup',
				mode: 'save',
				policy: JSON.stringify(collect())
			}
		}).then(function (err, res) {
			$btn.prop('disabled', false);
			if (err) {
				showError(typeof err === 'string' ? err : 'Không lưu được.');
				return;
			}
			paint(res || {});
			$('#mkPsSetupError').text('Đã lưu. Báo giá mới sẽ dùng các bậc này.').prop('hidden', false);
		}, function (err) {
			$btn.prop('disabled', false);
			showError(typeof err === 'string' ? err : 'Không lưu được.');
		});
	}

	function boot() {
		var $btn = $('#mkPsPriceSetupBtn');
		if (!$btn.length || $btn.data('mkBound')) {
			return;
		}
		$btn.data('mkBound', 1);
		$btn.on('click', function (e) {
			e.preventDefault();
			openDialog();
		});
	}

	$(boot);
	document.addEventListener('DOMContentLoaded', boot);
})(jQuery);
