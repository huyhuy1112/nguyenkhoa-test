(function ($) {
	'use strict';

	var TONES = ['blue', 'emerald', 'amber', 'cyan', 'violet', 'rose'];

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
		});
	}

	function cardHtml(card, index) {
		var missing = card.missing || card.value === 'Chưa đủ dữ liệu';
		var tone = card.tone || TONES[index % TONES.length];
		return '<div class="mk-admin-kpi-card mk-admin-kpi-card--static' + (missing ? ' is-missing' : '') + '" data-tone="' + esc(tone) + '" role="listitem">'
			+ '<span class="mk-admin-kpi-card-label">' + esc(card.label) + '</span>'
			+ (missing
				? '<span class="mk-admin-kpi-card-empty">Chưa đủ dữ liệu</span>'
				: '<span class="mk-admin-kpi-card-value">' + esc(card.value) + '</span>')
			+ (card.hint ? '<span class="mk-admin-kpi-card-label">' + esc(card.hint) + '</span>' : '')
			+ '</div>';
	}

	function paint(boards) {
		var box = document.getElementById('mkRoleReportsBody');
		if (!box) return;
		var keys = boards ? Object.keys(boards) : [];
		if (!keys.length) {
			box.innerHTML = '<div class="mk-admin-kpi-detail-loading">Chưa có báo cáo cho vai này.</div>';
			return;
		}
		var html = '';
		keys.forEach(function (key) {
			var board = boards[key] || {};
			html += '<h3 class="mk-admin-kpi-subtitle">' + esc(board.title || key) + '</h3>';
			html += '<div class="mk-admin-kpi-grid" role="list">';
			(board.cards || []).forEach(function (card, index) {
				html += cardHtml(card, index);
			});
			html += '</div>';
		});
		box.innerHTML = html;
	}

	function fail(message) {
		var box = document.getElementById('mkRoleReportsBody');
		if (!box) return;
		box.innerHTML = '<div class="mk-admin-kpi-detail-error">' + esc(message || 'Không tải được báo cáo theo vai.') + '</div>';
	}

	function load() {
		if (!document.getElementById('mkRoleReportsBody')) return;
		$.ajax({
			url: 'index.php',
			type: 'GET',
			dataType: 'json',
			cache: false,
			data: { module: 'Home', action: 'AdminKpiApi', mode: 'role_boards' }
		}).done(function (res) {
			if (res && res.result && typeof res.result === 'object') {
				res = res.result;
			}
			if (!res || res.success === false) {
				var message = 'Không tải được báo cáo theo vai.';
				if (res && res.error) {
					message = typeof res.error === 'string' ? res.error : (res.error.message || message);
				}
				fail(message);
				return;
			}
			paint(res.boards || {});
		}).fail(function () {
			fail('Không tải được báo cáo theo vai.');
		});
	}

	$(load);
})(jQuery);
