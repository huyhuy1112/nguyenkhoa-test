(function ($) {
	'use strict';

	var TONES = ['blue', 'emerald', 'amber', 'cyan', 'violet', 'rose'];
	var PERIODS = [
		['this_month', 'Tháng này'],
		['prev_month', 'Tháng trước'],
		['this_quarter', 'Quý này'],
		['prev_quarter', 'Quý trước'],
		['this_year', 'Năm này'],
		['prev_year', 'Năm trước']
	];

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
		box.__mkBoards = boards;
		var keep = box.__mkRole;
		box.__mkRole = (keep && boards[keep]) ? keep : (keys.indexOf('ceo') >= 0 ? 'ceo' : keys[0]);
		renderRole(box);
	}

	function renderRole(box) {
		var boards = box.__mkBoards || {};
		var keys = Object.keys(boards);
		var active = boards[box.__mkRole] ? box.__mkRole : keys[0];
		box.__mkRole = active;
		var board = boards[active] || {};
		var html = '';
		var period = box.__mkPeriod || 'this_month';
		html += '<div class="mk-role-switch mk-report-period" role="tablist">';
		PERIODS.forEach(function (item) {
			html += '<button type="button" data-mk-period="' + esc(item[0]) + '"'
				+ (item[0] === period ? ' class="is-on"' : '')
				+ '>' + esc(item[1]) + '</button>';
		});
		html += '</div>';
		if (keys.length > 1) {
			html += '<div class="mk-role-switch" role="tablist">';
			keys.forEach(function (key) {
				var title = (boards[key] && boards[key].title) || key;
				html += '<button type="button" role="tab" data-mk-role="' + esc(key) + '"'
					+ (key === active ? ' class="is-on" aria-selected="true"' : ' aria-selected="false"')
					+ '>' + esc(title) + '</button>';
			});
			html += '</div>';
		}
		box.__mkGoods = board.goods || null;
		box.__mkSheet = board.sheet || null;
		html += '<h3 class="mk-admin-kpi-subtitle">' + esc(board.title || active) + '</h3>';
		if ((board.cards || []).length) {
			html += '<div class="mk-admin-kpi-grid" role="list">';
			board.cards.forEach(function (card, index) {
				html += cardHtml(card, index);
			});
			html += '</div>';
		}
		if (board.goods) {
			html += goodsHtml(board.goods);
		}
		if (board.sheet) {
			html += sheetHtml(board.sheet, box.__mkMonth || '');
		}
		box.innerHTML = html;
		bindRoleSwitch(box);
		bindPeriod(box);
		bindGoods(box);
		bindSheet(box);
	}

	function bindPeriod(box) {
		var sw = box.querySelector('.mk-report-period');
		if (!sw || sw.getAttribute('data-bound')) return;
		sw.setAttribute('data-bound', '1');
		sw.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('[data-mk-period]') : null;
			if (!btn) return;
			var next = btn.getAttribute('data-mk-period');
			if (!next || next === box.__mkPeriod) return;
			box.__mkPeriod = next;
			load(next);
		});
	}

	function bindRoleSwitch(box) {
		var roleBtn = box.querySelector('[data-mk-role]');
		var sw = roleBtn ? roleBtn.parentNode : null;
		if (!sw) return;
		sw.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('[data-mk-role]') : null;
			if (!btn) return;
			var next = btn.getAttribute('data-mk-role');
			if (!next || next === box.__mkRole) return;
			box.__mkRole = next;
			renderRole(box);
		});
	}

	function fmtQty(n) {
		var v = Number(n) || 0;
		return v.toLocaleString('vi-VN', { maximumFractionDigits: v % 1 ? 1 : 0 });
	}

	function fmtMoney(n) {
		return Math.round(Number(n) || 0).toLocaleString('vi-VN');
	}

	function goodsTable(headers, rows, mapRow) {
		var html = '<table class="mk-goods-table"><thead><tr>';
		headers.forEach(function (h) {
			html += '<th' + (h.num ? ' class="is-num"' : '') + '>' + esc(h.label) + '</th>';
		});
		html += '</tr></thead><tbody>';
		if (!rows.length) {
			html += '<tr><td colspan="' + headers.length + '" class="mk-goods-empty">Chưa có số trong khoảng này.</td></tr>';
		} else {
			rows.forEach(function (row) {
				html += '<tr>' + mapRow(row) + '</tr>';
			});
		}
		html += '</tbody></table>';
		return html;
	}

	function goodsHtml(goods) {
		var stock = goods.stock || {};
		var alerts = goods.alerts || {};
		var html = '<section class="mk-goods-report" data-mk-goods="1">';
		html += '<h3 class="mk-admin-kpi-subtitle">Báo cáo hàng hóa</h3>';
		html += '<div class="mk-goods-summary">';
		html += summaryCard('Số mặt hàng còn tồn', fmtQty(stock.sku_count), 'Hàng đang có số lượng lớn hơn 0');
		html += summaryCard('Tổng kho', fmtQty(stock.qty), 'Tổng số lượng đang lưu');
		html += summaryCard('Giá trị tồn kho', fmtMoney(stock.value), 'Số lượng nhân giá nhập hoặc giá bán');
		html += '</div>';
		html += '<div class="mk-goods-alerts">';
		html += alertCard('Hết hàng hôm nay', alerts.out_today, 'rose');
		html += alertCard('Hết trong 7 ngày', alerts.out_7, 'amber');
		html += alertCard('Hết trong 30 ngày', alerts.out_30, 'slate');
		html += alertCard('Không bán được', alerts.unsold, 'rose');
		html += alertCard('Tồn cao hơn mức bán', alerts.overstock, 'violet');
		html += '</div>';
		html += '<p class="mk-goods-note">Cảnh báo tồn kho lấy lượng bán 30 ngày gần nhất. Doanh thu, lượng bán, bán chạy và bán chậm theo kỳ đang chọn phía trên.</p>';

		html += '<div class="mk-goods-block">';
		html += '<div class="mk-goods-block__head"><h4>Doanh thu và lượng bán</h4>';
		html += '<div class="mk-goods-switch" data-mk-goods-switch="period">';
		html += '<button type="button" class="is-on" data-mk-goods-period="day">Ngày</button>';
		html += '<button type="button" data-mk-goods-period="week">Tuần</button>';
		html += '<button type="button" data-mk-goods-period="month">Tháng</button>';
		html += '</div></div>';
		html += '<div data-mk-goods-period-table="1"></div>';
		html += '</div>';

		html += '<div class="mk-goods-split">';
		html += '<div class="mk-goods-block"><h4>Nhóm hàng theo tồn</h4>';
		html += goodsTable(
			[{ label: 'Nhóm' }, { label: 'Tồn kho', num: true }, { label: 'Giá trị', num: true }],
			goods.groups || [],
			function (row) {
				return '<td>' + esc(row.name) + '</td><td class="is-num">' + fmtQty(row.qty) + '</td><td class="is-num">' + fmtMoney(row.value) + '</td>';
			}
		);
		html += '</div>';
		html += '<div class="mk-goods-block"><div class="mk-goods-block__head"><h4>Hàng theo tồn</h4>';
		html += '<div class="mk-goods-switch" data-mk-goods-switch="stock">';
		html += '<button type="button" class="is-on" data-mk-goods-stock="qty">Theo số lượng</button>';
		html += '<button type="button" data-mk-goods-stock="value">Theo giá trị</button>';
		html += '</div></div><div data-mk-goods-stock-table="1"></div></div>';
		html += '</div>';

		html += '<div class="mk-goods-block">';
		html += '<div class="mk-goods-block__head"><h4>Bán chạy và bán chậm · 30 ngày</h4>';
		html += '<div class="mk-goods-switch" data-mk-goods-switch="rank">';
		html += '<button type="button" class="is-on" data-mk-goods-rank="fast">Bán chạy</button>';
		html += '<button type="button" data-mk-goods-rank="slow">Bán chậm</button>';
		html += '</div></div>';
		html += '<div data-mk-goods-rank-table="1"></div>';
		if (!goods.has_returns) {
			html += '<p class="mk-goods-note">Chưa có phiếu trả hàng, nên báo cáo không có cột số lượng trả.</p>';
		}
		if (!goods.has_cost) {
			html += '<p class="mk-goods-note">Chưa có giá vốn trên hàng hóa, nên chưa tính lợi nhuận gộp.</p>';
		}
		html += '</div></section>';
		return html;
	}

	function summaryCard(label, value, hint) {
		return '<div class="mk-goods-summary__card"><span>' + esc(label) + '</span><strong>' + esc(value) + '</strong><em>' + esc(hint) + '</em></div>';
	}

	function alertCard(label, value, tone) {
		return '<div class="mk-goods-alert" data-tone="' + esc(tone) + '"><span>' + esc(label) + '</span><strong>' + fmtQty(value) + '</strong></div>';
	}

	function periodRows(goods, grain) {
		var rows = (goods.periods && goods.periods[grain]) || [];
		return goodsTable(
			[{ label: grain === 'day' ? 'Ngày' : (grain === 'week' ? 'Tuần' : 'Tháng') }, { label: 'Lượng bán', num: true }, { label: 'Doanh thu', num: true }],
			rows,
			function (row) {
				return '<td>' + esc(row.label) + '</td><td class="is-num">' + fmtQty(row.qty) + '</td><td class="is-num">' + fmtMoney(row.revenue) + '</td>';
			}
		);
	}

	function stockRows(goods, mode) {
		var rows = mode === 'value' ? (goods.stock_top_value || []) : (goods.stock_top_qty || []);
		return goodsTable(
			[{ label: 'Tên hàng' }, { label: 'Tồn kho', num: true }, { label: 'Giá trị', num: true }],
			rows,
			function (row) {
				var name = row.name + (row.sku ? ' · ' + row.sku : '');
				return '<td>' + esc(name) + '</td><td class="is-num">' + fmtQty(row.qty) + '</td><td class="is-num">' + fmtMoney(row.value) + '</td>';
			}
		);
	}

	function rankRows(goods, mode) {
		var rows = mode === 'slow' ? (goods.slow || []) : (goods.fast || []);
		var headers = [{ label: 'Tên hàng' }, { label: 'Số lượng bán', num: true }, { label: 'Doanh thu', num: true }];
		if (goods.has_cost) {
			headers.push({ label: 'Lợi nhuận gộp', num: true });
		}
		return goodsTable(headers, rows, function (row) {
			var name = row.name + (row.sku ? ' · ' + row.sku : '');
			var html = '<td>' + esc(name) + '</td><td class="is-num">' + fmtQty(row.qty) + '</td><td class="is-num">' + fmtMoney(row.revenue) + '</td>';
			if (goods.has_cost) {
				html += '<td class="is-num">' + fmtMoney(row.profit) + '</td>';
			}
			return html;
		});
	}

	function bindGoods(box) {
		var root = box.querySelector('[data-mk-goods="1"]');
		if (!root || !box.__mkGoods) return;
		var goods = box.__mkGoods;
		var period = 'day';
		var stockMode = 'qty';
		var rank = 'fast';
		var periodBox = root.querySelector('[data-mk-goods-period-table="1"]');
		var stockBox = root.querySelector('[data-mk-goods-stock-table="1"]');
		var rankBox = root.querySelector('[data-mk-goods-rank-table="1"]');
		function paintTables() {
			if (periodBox) periodBox.innerHTML = periodRows(goods, period);
			if (stockBox) stockBox.innerHTML = stockRows(goods, stockMode);
			if (rankBox) rankBox.innerHTML = rankRows(goods, rank);
		}
		paintTables();
		root.addEventListener('click', function (e) {
			var periodBtn = e.target.closest ? e.target.closest('[data-mk-goods-period]') : null;
			var stockBtn = e.target.closest ? e.target.closest('[data-mk-goods-stock]') : null;
			var rankBtn = e.target.closest ? e.target.closest('[data-mk-goods-rank]') : null;
			var btn = periodBtn || stockBtn || rankBtn;
			if (!btn) return;
			var group = btn.parentNode;
			if (group) {
				Array.prototype.forEach.call(group.querySelectorAll('button'), function (el) {
					el.classList.remove('is-on');
				});
			}
			btn.classList.add('is-on');
			if (periodBtn) period = periodBtn.getAttribute('data-mk-goods-period');
			if (stockBtn) stockMode = stockBtn.getAttribute('data-mk-goods-stock');
			if (rankBtn) rank = rankBtn.getAttribute('data-mk-goods-rank');
			paintTables();
		});
	}

	function fmtPct(n) {
		if (n === null || n === undefined || n === '') return '—';
		var v = Number(n);
		if (!isFinite(v)) return '—';
		return (v * 100).toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
	}

	function numCell(n) {
		var v = Number(n) || 0;
		return v ? fmtQty(v) : '0';
	}

	function moneyCell(n) {
		if (n === null || n === undefined || n === '') return '—';
		return fmtMoney(n) + ' đ';
	}

	function sheetTable(headers, body) {
		var html = '<div class="mk-sheet-scroll"><table class="mk-sheet-table"><thead><tr>';
		headers.forEach(function (h) {
			html += '<th>' + esc(h) + '</th>';
		});
		html += '</tr></thead><tbody>' + body + '</tbody></table></div>';
		return html;
	}

	function monthOf(sheet, key) {
		var months = (sheet && sheet.months) || [];
		var i;
		for (i = 0; i < months.length; i++) {
			if (months[i].key === key) return months[i];
		}
		return months.length ? months[months.length - 1] : null;
	}

	function sheetHtml(sheet, monthKey) {
		if (sheet.error && !(sheet.months || []).length) {
			return '<p class="mk-sheet-note">' + esc(sheet.error) + '</p>';
		}
		var month = monthOf(sheet, monthKey);
		var html = '<section class="mk-sheet-report">';
		html += '<p class="mk-sheet-note">Số liệu đếm từ dữ liệu CRM. Ô tiền quảng cáo nhập tay mỗi ngày. Ngân sách tuần là tổng tiền từ thứ Hai đến Chủ nhật. Dòng tổng đã cộng VAT 10%.</p>';
		html += '<div class="mk-sheet-months">';
		(sheet.months || []).forEach(function (item) {
			html += '<button type="button" data-mk-sheet-month="' + esc(item.key) + '"'
				+ (month && item.key === month.key ? ' class="is-on"' : '')
				+ '>' + esc(item.label) + '</button>';
		});
		html += '</div>';
		html += dailyHtml(month);
		html += classHtml(sheet.classes || []);
		html += perf990Html(sheet.perf990 || {});
		html += perfFreeHtml(sheet.perfFree || {});
		html += statusHtml(sheet.status || {});
		html += '</section>';
		return html;
	}

	function dailyHtml(month) {
		var days = (month && month.days) || [];
		var html = '<h3 class="mk-admin-kpi-subtitle">Theo dõi Data Marketing theo ngày'
			+ (month ? ' · ' + esc(month.label) : '') + '</h3>';
		var body = '';
		var sum = { total: 0, nkhoa: 0, tiktok: 0, kv1: 0, kv2: 0, kv3: 0, unknown: 0, consulting: 0, unreachable: 0, khong_hoc: 0, online: 0, spend: 0 };
		if (!days.length) {
			body = '<tr><td colspan="14" class="mk-sheet-empty">Chưa có ngày trong tháng này.</td></tr>';
		}
		days.forEach(function (row) {
			['total', 'nkhoa', 'tiktok', 'kv1', 'kv2', 'kv3', 'unknown', 'consulting', 'unreachable', 'khong_hoc', 'online', 'spend'].forEach(function (key) {
				sum[key] += Number(row[key]) || 0;
			});
			body += '<tr' + (Number(row.total) > 0 ? ' class="is-hot"' : '') + '>'
				+ '<td>' + esc(row.label) + '</td>'
				+ '<td class="is-num">' + numCell(row.total) + '</td>'
				+ '<td class="is-num">' + numCell(row.nkhoa) + '</td>'
				+ '<td class="is-num">' + numCell(row.tiktok) + '</td>'
				+ '<td class="is-num">' + numCell(row.kv1) + '</td>'
				+ '<td class="is-num">' + numCell(row.kv2) + '</td>'
				+ '<td class="is-num">' + numCell(row.kv3) + '</td>'
				+ '<td class="is-num">' + numCell(row.unknown) + '</td>'
				+ '<td class="is-num">' + numCell(row.consulting) + '</td>'
				+ '<td class="is-num">' + numCell(row.unreachable) + '</td>'
				+ '<td class="is-num">' + numCell(row.khong_hoc) + '</td>'
				+ '<td class="is-num">' + numCell(row.online) + '</td>'
				+ '<td class="is-num"><input class="mk-sheet-spend" type="text" inputmode="numeric" data-mk-spend-date="'
				+ esc(row.date) + '" value="' + esc(String(Math.round(Number(row.spend) || 0))) + '" /></td>'
				+ '<td class="is-num">' + moneyCell(row.cost_data) + '</td>'
				+ '</tr>';
		});
		if (days.length) {
			body += '<tr class="is-total"><td>Tổng</td>'
				+ '<td class="is-num">' + numCell(sum.total) + '</td>'
				+ '<td class="is-num">' + numCell(sum.nkhoa) + '</td>'
				+ '<td class="is-num">' + numCell(sum.tiktok) + '</td>'
				+ '<td class="is-num">' + numCell(sum.kv1) + '</td>'
				+ '<td class="is-num">' + numCell(sum.kv2) + '</td>'
				+ '<td class="is-num">' + numCell(sum.kv3) + '</td>'
				+ '<td class="is-num">' + numCell(sum.unknown) + '</td>'
				+ '<td class="is-num">' + numCell(sum.consulting) + '</td>'
				+ '<td class="is-num">' + numCell(sum.unreachable) + '</td>'
				+ '<td class="is-num">' + numCell(sum.khong_hoc) + '</td>'
				+ '<td class="is-num">' + numCell(sum.online) + '</td>'
				+ '<td class="is-num">' + moneyCell(sum.spend) + '</td>'
				+ '<td class="is-num">' + (sum.total > 0 ? moneyCell(sum.spend / sum.total) : '—') + '</td></tr>';
		}
		return html + sheetTable([
			'Ngày', 'Tổng data', 'N.Khoa', 'TikTok', 'KV1', 'KV2', 'KV3', 'Không rõ',
			'Đang tư vấn / hẹn / KNM', 'Liên hệ không được', 'Không học', 'Lớp online',
			'Tiền quảng cáo', 'Chi phí / data'
		], body);
	}

	function classHtml(rows) {
		var html = '<h3 class="mk-admin-kpi-subtitle">Funnel theo ngày học</h3>';
		var body = '';
		if (!rows.length) {
			body = '<tr><td colspan="22" class="mk-sheet-empty">Chưa có ngày học trong CRM.</td></tr>';
		}
		rows.forEach(function (row) {
			body += '<tr><td>' + esc(row.month) + '</td><td>' + esc(row.weekday) + '</td><td>' + esc(row.date) + '</td>'
				+ '<td class="is-num">' + numCell(row.total) + '</td>'
				+ '<td class="is-num">' + numCell(row.appointments) + '</td>'
				+ '<td class="is-num">' + numCell(row.reschedule) + '</td>'
				+ '<td class="is-num">' + numCell(row.khong_hoc) + '</td>'
				+ '<td class="is-num">' + numCell(row.confirmed) + '</td>'
				+ '<td class="is-num">' + numCell(row.show) + '</td>'
				+ '<td class="is-num">' + fmtPct(row.pct_potential) + '</td>'
				+ '<td class="is-num">' + fmtPct(row.pct_show) + '</td>'
				+ '<td class="is-num">' + fmtPct(row.pct_confirm) + '</td>'
				+ '<td class="is-num">' + fmtPct(row.pct_actual) + '</td>'
				+ '<td class="is-num">' + numCell(row.pcth) + '</td>'
				+ '<td class="is-num">' + numCell(row.pcth_mq) + '</td>'
				+ '<td class="is-num">' + numCell(row.mq) + '</td>'
				+ '<td class="is-num">' + fmtPct(row.pct_close) + '</td>'
				+ '<td class="is-num">' + numCell(row.knm) + '</td>'
				+ '<td class="is-num">' + numCell(row.potential) + '</td>'
				+ '<td class="is-num">' + numCell(row.unsure) + '</td>'
				+ '<td class="is-num">' + numCell(row.invite) + '</td>'
				+ '<td class="is-num">' + numCell(row.stop) + '</td>'
				+ '<td class="is-num">' + numCell(row.franchise) + '</td>'
				+ '<td class="is-num">' + numCell(row.material) + '</td></tr>';
		});
		return html + sheetTable([
			'Tháng', 'Thứ', 'Ngày học', 'Tổng data', 'Số hẹn', 'Dời lịch', 'Không học', 'Xác nhận', 'Thực tế',
			'% tiềm năng', '% tham gia/hẹn', '% xác nhận/hẹn', '% thực/xác nhận', 'PCTH', 'PCTH+MQ', 'MQ', 'Tỷ lệ chốt',
			'KNM', 'Tiềm năng', 'Phân vân', 'Mời lại', 'Ngừng', 'Nhượng quyền', 'Nguyên liệu'
		], body);
	}

	function perf990Html(block) {
		var weeks = block.weeks || [];
		var sum = block.summary || {};
		var html = '<h3 class="mk-admin-kpi-subtitle">Lớp học 990K</h3>';
		var body = '';
		weeks.forEach(function (row) {
			body += '<tr><td>' + esc(row.label) + '</td><td>' + esc(row.range) + '</td>'
				+ '<td class="is-num">' + moneyCell(row.budget) + '</td>'
				+ '<td class="is-num">' + numCell(row.data) + '</td>'
				+ '<td class="is-num">' + moneyCell(row.cost_data) + '</td>'
				+ '<td class="is-num">' + numCell(row.qualified) + '</td>'
				+ '<td class="is-num">' + moneyCell(row.cost_qualified) + '</td>'
				+ '<td class="is-num">' + numCell(row.paid) + '</td>'
				+ '<td class="is-num">' + fmtPct(row.paid_rate) + '</td>'
				+ '<td class="is-num">' + numCell(row.kv1) + '</td>'
				+ '<td class="is-num">' + numCell(row.kv2) + '</td>'
				+ '<td class="is-num">' + numCell(row.kv3) + '</td>'
				+ '<td class="is-num">' + numCell(row.unknown) + '</td></tr>';
		});
		body += '<tr class="is-total"><td>Tổng ngân sách QC (đã VAT)</td><td></td>'
			+ '<td class="is-num">' + moneyCell(sum.budget) + '</td>'
			+ '<td class="is-num">' + numCell(sum.data) + '</td>'
			+ '<td class="is-num">' + moneyCell(sum.cost_data) + '</td>'
			+ '<td class="is-num">' + numCell(sum.qualified) + '</td>'
			+ '<td class="is-num">' + moneyCell(sum.cost_qualified) + '</td>'
			+ '<td class="is-num">' + numCell(sum.paid) + '</td>'
			+ '<td class="is-num">' + fmtPct(sum.paid_rate) + '</td>'
			+ '<td colspan="4"></td></tr>';
		html += sheetTable([
			'Tuần', 'Khoảng ngày', 'Ngân sách', 'Tổng data', 'Chi phí / data', 'Lead chuẩn', 'Chi phí / lead chuẩn',
			'Đã CK', 'Tỷ lệ / lead chuẩn', 'KV1', 'KV2', 'KV3', 'Không rõ'
		], body);
		html += '<p class="mk-sheet-note">Mục tiêu chi phí mỗi lead chuẩn: dưới ' + moneyCell(sum.target_qualified)
			+ '. Thực tế: ' + moneyCell(sum.cost_qualified)
			+ '. Chi phí mỗi lead mua khóa: ' + moneyCell(sum.cost_per_paid)
			+ '. Doanh thu ước tính (đã CK × 990.000): ' + moneyCell(sum.revenue) + '.</p>';
		return html;
	}

	function perfFreeHtml(block) {
		var weeks = block.weeks || [];
		var sum = block.summary || {};
		var html = '<h3 class="mk-admin-kpi-subtitle">Lớp học miễn phí</h3>';
		var body = '';
		if (!weeks.length) {
			body = '<tr><td colspan="5" class="mk-sheet-empty">Chưa có tuần lớp miễn phí.</td></tr>';
		}
		weeks.forEach(function (row) {
			body += '<tr><td>' + esc(row.label) + '</td><td>' + esc(row.date) + '</td>'
				+ '<td class="is-num">' + numCell(row.appointments) + '</td>'
				+ '<td class="is-num">' + numCell(row.show) + '</td>'
				+ '<td class="is-num">' + numCell(row.close) + '</td></tr>';
		});
		html += sheetTable(['Tuần', 'Ngày học', 'Số hẹn', 'Tham gia thực tế', 'PCTH / MQ'], body);
		html += '<p class="mk-sheet-note">Ngân sách là cùng số tiền quảng cáo đã nhập theo ngày, đã cộng VAT: ' + moneyCell(sum.budget)
			+ '. Chi phí mỗi lead chuẩn: ' + moneyCell(sum.cost_qualified)
			+ ' (mục tiêu dưới ' + moneyCell(sum.target_qualified) + ').'
			+ ' Chi phí mỗi khách tới lớp: ' + moneyCell(sum.cost_show)
			+ '. Mục tiêu chi phí khách tới lớp: ' + moneyCell(sum.target_show) + '.</p>';
		return html;
	}

	function statusHtml(status) {
		var dates = status.dates || [];
		var rows = status.rows || [];
		if (!dates.length || !rows.length) return '';
		var html = '<h3 class="mk-admin-kpi-subtitle">Tình trạng theo ngày học</h3>';
		var headers = ['Tình trạng'].concat(dates);
		var body = '';
		rows.forEach(function (row) {
			body += '<tr><td>' + esc(row.label) + '</td>';
			(row.cells || []).forEach(function (cell) {
				body += '<td class="is-num">' + numCell(cell) + '</td>';
			});
			body += '</tr>';
		});
		return html + sheetTable(headers, body);
	}

	function csrfToken() {
		if (typeof csrfMagicToken !== 'undefined') return csrfMagicToken;
		var node = document.querySelector('input[name="__vtrftk"]');
		return node ? node.value : '';
	}

	function bindSheet(box) {
		var root = box.querySelector('.mk-sheet-report');
		if (!root || root.__mkBound) return;
		root.__mkBound = true;
		root.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('[data-mk-sheet-month]') : null;
			if (!btn) return;
			box.__mkMonth = btn.getAttribute('data-mk-sheet-month');
			renderRole(box);
		});
		root.addEventListener('change', function (e) {
			var input = e.target.closest ? e.target.closest('.mk-sheet-spend') : null;
			if (!input) return;
			saveSpend(box, input);
		});
	}

	function saveSpend(box, input) {
		var date = input.getAttribute('data-mk-spend-date');
		if (!date) return;
		input.disabled = true;
		$.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data: {
				module: 'Home',
				action: 'AdminKpiApi',
				mode: 'save_ad_spend',
				spend_date: date,
				amount: input.value,
				report_period: box.__mkPeriod || 'this_month',
				__vtrftk: csrfToken()
			}
		}).done(function (res) {
			if (res && res.result && typeof res.result === 'object') res = res.result;
			if (!res || res.success === false || !res.sheet) {
				input.disabled = false;
				return;
			}
			if (box.__mkBoards && box.__mkBoards.marketing) {
				box.__mkBoards.marketing.sheet = res.sheet;
			}
			renderRole(box);
		}).fail(function () {
			input.disabled = false;
		});
	}

	function fail(message) {
		var box = document.getElementById('mkRoleReportsBody');
		if (!box) return;
		box.innerHTML = '<div class="mk-admin-kpi-detail-error">' + esc(message || 'Không tải được báo cáo theo vai.') + '</div>';
	}

	function load(period) {
		var box = document.getElementById('mkRoleReportsBody');
		if (!box) return;
		if (period) box.__mkPeriod = period;
		$.ajax({
			url: 'index.php',
			type: 'GET',
			dataType: 'json',
			cache: false,
			data: {
				module: 'Home',
				action: 'AdminKpiApi',
				mode: 'role_boards',
				report_period: box.__mkPeriod || 'this_month'
			}
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
