/**
 * WhDetail Prototype Controller — UI + logic giống Prototype kho,
 * nhưng state lấy từ MkWarehouseStore (localStorage) theo warehouse_id.
 */
(function () {
	'use strict';

	var S = window.MkWarehouseStore;
	if (!S) return;

	function qs(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}

	function qsa(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

	function escapeHtml(s) {
		return String(s || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function fmtDateTime(iso) {
		if (!iso) return '—';
		try {
			return new Date(iso).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' });
		} catch (e) {
			return iso;
		}
	}

	function daysUntil(exp) {
		if (!exp) return 999999;
		try {
			return Math.round((new Date(exp).getTime() - Date.now()) / 86400000);
		} catch (e) {
			return 999999;
		}
	}

	function fmtPrice(n) {
		var v = Number(n);
		if (!isFinite(v) || v <= 0) return '—';
		return v.toLocaleString('vi-VN') + ' ₫';
	}

	function getWhId() {
		var root = qs('#mkWhDetailRoot');
		return root ? (root.getAttribute('data-wh-id') || '') : '';
	}

	function getWarehouse() {
		var id = getWhId();
		return (S.getState().warehouses || []).find(function (w) { return w.id === id; });
	}

	// UI roles use Prototype keys: qc | stock | manager
	// Store logic uses keeper | qc | manager, so map stock <-> keeper.
	function getRole() {
		var sel = qs('#mkWhDetailRole');
		var val = sel ? sel.value : 'keeper';
		if (val === 'stock') return 'keeper';
		return val;
	}

	function setRoleUI(roleKey) {
		var sel = qs('#mkWhDetailRole');
		if (!sel) return;
		var uiVal = roleKey === 'keeper' ? 'stock' : roleKey;
		if (sel.value !== uiVal) sel.value = uiVal;
	}

	var ROLE_UI = {
		keeper: { badge: 'Thủ kho', hint: 'Quyền: Tạo/sửa phiếu nhập • Gửi QC • Soạn & giao hàng • Tạo phiếu xuất', perms: 'Tạo/sửa phiếu nhập • Gửi QC • Soạn & giao hàng • Tạo phiếu xuất' },
		qc: { badge: 'QC', hint: 'Quyền: Chỉnh sửa kết quả QC (đạt/không đạt) • Ghi chú kiểm tra', perms: 'Ghi nhận kết quả QC (Đạt/Không đạt) • Ghi chú kiểm tra' },
		manager: { badge: 'Quản lý kho', hint: 'Quyền: Duyệt phiếu nhập sau QC • Duyệt/Từ chối phiếu xuất • Xem toàn bộ tồn kho', perms: 'Duyệt phiếu nhập sau QC • Duyệt/Từ chối phiếu xuất • Xem toàn bộ tồn kho' },
	};

	var RECEIPT_STATUS = {
		draft: { label: 'Nháp', cls: 'mk-wh-proto-pill mk-wh-proto-pill--draft' },
		pending_qc: { label: 'Chờ QC', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
		qc_passed: { label: 'QC đạt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		qc_failed: { label: 'QC không đạt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
		approved: { label: 'Đã duyệt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		stored: { label: 'Đã nhập kho', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
	};

	var ISSUE_STATUS = {
		draft: { label: 'Nháp', cls: 'mk-wh-proto-pill mk-wh-proto-pill--draft' },
		pending_approval: { label: 'Chờ duyệt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
		approved: { label: 'Đã duyệt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		shipped: { label: 'Đã giao', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		rejected: { label: 'Từ chối', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
	};

	function nextId(prefix, existing) {
		var max = 0;
		(existing || []).forEach(function (x) {
			var id = String(x && (x.id || x.code) ? (x.id || x.code) : '');
			var m = new RegExp('^' + prefix + '-(\\d+)$').exec(id);
			if (m && m[1]) {
				var n = parseInt(m[1], 10);
				if (!isNaN(n) && n > max) max = n;
			}
		});
		return prefix + '-' + String(max + 1).padStart(4, '0');
	}

	function renderHeader() {
		var w = getWarehouse();
		var title = qs('#mkWhDetailTitle');
		var desc = qs('#mkWhDetailDesc');
		if (!w) {
			if (title) title.textContent = 'Không tìm thấy kho';
			if (desc) desc.textContent = 'Kho không tồn tại hoặc đã bị xóa.';
			return false;
		}
		if (title) title.textContent = w.name;
		if (desc) desc.textContent = w.code + ' · ' + (w.address || '—') + ' · QL: ' + (w.manager || '—');
		return true;
	}

	function updateRoleBanner() {
		var role = getRole();
		var meta = ROLE_UI[role] || ROLE_UI.keeper;
		var badge = qs('#mkWhProtoRoleBadge');
		var hint = qs('#mkWhProtoRoleHint');
		var permRole = qs('#mkWhProtoPermRole');
		var permItems = qs('#mkWhProtoPermItems');
		if (badge) badge.textContent = meta.badge;
		if (hint) hint.textContent = meta.hint;
		if (permRole) permRole.textContent = meta.badge;
		if (permItems) permItems.textContent = meta.perms;

		var active = qs('.mk-wh-proto-tab.is-active');
		var tabKey = active ? active.getAttribute('data-tab') : 'inbound';
		var btn = qs('#mkWhProtoCreateBtn');
		var canCreate = role === 'keeper' && (tabKey === 'inbound' || tabKey === 'outbound');
		if (btn) {
			btn.classList.toggle('hide', !canCreate);
			btn.disabled = !canCreate;
			btn.textContent = tabKey === 'outbound' ? 'Tạo phiếu xuất' : 'Tạo phiếu nhập';
		}
	}

	function updateKpis() {
		var id = getWhId();
		if (!id) return;
		var d = S.ensureData(id);
		var pendingQc = (d.receipts || []).filter(function (r) { return r.status === 'pending_qc'; }).length;
		var pendingApprove = (d.issues || []).filter(function (i) { return i.status === 'pending_approval'; }).length;
		var skuSet = {};
		(d.stock || []).forEach(function (s) { if ((Number(s.qty) || 0) > 0) skuSet[s.sku] = true; });
		var expiring = (d.stock || []).filter(function (s) { return (Number(s.qty) || 0) > 0 && daysUntil(s.expiry) < 90; }).length;
		var k1 = qs('#mkWhKpiPendingQc');
		var k2 = qs('#mkWhKpiPendingApprove');
		var k3 = qs('#mkWhKpiSku');
		var k4 = qs('#mkWhKpiExpiring');
		if (k1) k1.textContent = String(pendingQc);
		if (k2) k2.textContent = String(pendingApprove);
		if (k3) k3.textContent = String(Object.keys(skuSet).length);
		if (k4) k4.textContent = String(expiring);
	}

	function setActiveTab(key) {
		qsa('.mk-wh-proto-tab').forEach(function (b) {
			b.classList.toggle('is-active', b.getAttribute('data-tab') === key);
		});
		var title = qs('#mkWhProtoStageTitle');
		if (title) {
			title.textContent =
				key === 'qc' ? 'Hàng đợi QC' :
				key === 'stock' ? 'Tồn kho' :
				key === 'outbound' ? 'Danh sách phiếu xuất' :
				'Danh sách phiếu nhập';
		}
		['#mkWhProtoPaneInbound', '#mkWhProtoPaneQc', '#mkWhProtoPaneStock', '#mkWhProtoPaneOutbound'].forEach(function (sel) {
			var el = qs(sel);
			if (!el) return;
			el.classList.toggle('hide', sel !== (key === 'qc' ? '#mkWhProtoPaneQc' : key === 'stock' ? '#mkWhProtoPaneStock' : key === 'outbound' ? '#mkWhProtoPaneOutbound' : '#mkWhProtoPaneInbound'));
		});
		updateRoleBanner();
		renderAll();
	}

	function renderInbounds() {
		var id = getWhId();
		var tbody = qs('#mkWhProtoInboundTbody');
		if (!id || !tbody) return;
		var d = S.ensureData(id);
		tbody.innerHTML = (d.receipts || []).map(function (r) {
			var st = RECEIPT_STATUS[r.status] || { label: r.status, cls: 'mk-wh-proto-pill' };
			return '<tr>' +
				'<td><strong>' + escapeHtml(r.id) + '</strong></td>' +
				'<td>' + escapeHtml(r.supplier) + '</td>' +
				'<td>' + escapeHtml(r.poRef) + '</td>' +
				'<td>' + escapeHtml(fmtDateTime(r.createdAt)) + '</td>' +
				'<td><span class="' + escapeHtml(st.cls) + '">' + escapeHtml(st.label) + '</span></td>' +
				'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-action="inbound-detail" data-id="' + escapeHtml(r.id) + '">Mở</button></td>' +
			'</tr>';
		}).join('');
	}

	function renderQcQueue() {
		var id = getWhId();
		var tbody = qs('#mkWhProtoQcTbody');
		if (!id || !tbody) return;
		var d = S.ensureData(id);
		var rows = [];
		(d.receipts || []).filter(function (r) { return r.status === 'pending_qc'; }).forEach(function (r) {
			var it = (r.lines && r.lines[0]) || {};
			rows.push('<tr>' +
				'<td><strong>' + escapeHtml(r.id) + '</strong></td>' +
				'<td>' + escapeHtml(r.supplier) + '</td>' +
				'<td>' + escapeHtml(it.name || '—') + ' <span class="mk-wh-proto-muted">(' + escapeHtml(it.sku || '') + ')</span></td>' +
				'<td>' + escapeHtml(it.lot || '—') + '</td>' +
				'<td>HSD: ' + escapeHtml(it.expiry || '—') + '</td>' +
				'<td>' + escapeHtml(it.qty || '—') + '</td>' +
				'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-action="qc-record" data-id="' + escapeHtml(r.id) + '"' +
					(getRole() !== 'qc' ? ' disabled' : '') +
				'>Ghi nhận QC</button></td>' +
			'</tr>');
		});
		tbody.innerHTML = rows.join('');
	}

	function applyStockFilters(rows) {
		var hsdEl = qs('#mkWhProtoFilterHsd');
		var nameEl = qs('#mkWhProtoFilterName');
		var priceEl = qs('#mkWhProtoFilterPrice');
		var filters = {
			hsd: hsdEl ? hsdEl.value : 'all',
			name: nameEl ? nameEl.value : 'az',
			price: priceEl ? priceEl.value : 'all',
		};
		var list = (rows || []).filter(function (s) {
			if ((Number(s.qty) || 0) <= 0) return false;
			var days = daysUntil(s.expiry);
			if (filters.hsd === 'soon') return days >= 0 && days < 90;
			if (filters.hsd === 'valid') return days >= 0;
			if (filters.hsd === 'expired') return days < 0;
			return true;
		});
		list.sort(function (a, b) {
			if (filters.price === 'asc' || filters.price === 'desc') {
				var pa = Number(a.price) || 0;
				var pb = Number(b.price) || 0;
				if (pa !== pb) return filters.price === 'asc' ? pa - pb : pb - pa;
			}
			var an = String(a.name || '').toLocaleLowerCase('vi');
			var bn = String(b.name || '').toLocaleLowerCase('vi');
			var cmp = an.localeCompare(bn, 'vi');
			return filters.name === 'za' ? -cmp : cmp;
		});
		return { rows: list, filters: filters };
	}

	function renderStock() {
		var id = getWhId();
		var tbody = qs('#mkWhProtoStockTbody');
		if (!id || !tbody) return;
		var d = S.ensureData(id);
		var inStock = (d.stock || []).slice();
		var result = applyStockFilters(inStock);
		var rows = result.rows;
		var summary = qs('#mkWhProtoFilterSummary');
		if (summary) summary.textContent = rows.length ? ('Hiển thị ' + rows.length + ' dòng tồn') : 'Chưa có tồn kho phù hợp bộ lọc.';
		tbody.innerHTML = rows.map(function (s) {
			var days = daysUntil(s.expiry);
			var expLabel = days < 0 ? 'Quá hạn' : 'Còn ' + days + ' ngày';
			var hsdCls = 'mk-wh-proto-hsd' + (days < 0 ? ' mk-wh-proto-hsd--expired' : (days < 90 ? ' mk-wh-proto-hsd--soon' : ''));
			var qtyCls = (Number(s.qty) || 0) < 50 ? ' mk-wh-proto-qty--low' : '';
			return '<tr>' +
				'<td><strong>' + escapeHtml(s.sku) + '</strong></td>' +
				'<td>' + escapeHtml(s.name) + '</td>' +
				'<td>' + escapeHtml(s.lot) + '</td>' +
				'<td class="' + hsdCls + '">' + escapeHtml(s.expiry || '—') + ' <span class="mk-wh-proto-muted">(' + escapeHtml(expLabel) + ')</span></td>' +
				'<td class="mk-wh-proto-td-right">' + escapeHtml(fmtPrice(s.price)) + '</td>' +
				'<td class="mk-wh-proto-td-right">' + escapeHtml(s.location || '—') + '</td>' +
				'<td class="mk-wh-proto-td-right' + qtyCls + '"><strong>' + escapeHtml(s.qty) + '</strong></td>' +
			'</tr>';
		}).join('');
	}

	function renderOutbound() {
		var id = getWhId();
		var tbody = qs('#mkWhProtoOutboundTbody');
		if (!id || !tbody) return;
		var d = S.ensureData(id);
		tbody.innerHTML = (d.issues || []).map(function (i) {
			var st = ISSUE_STATUS[i.status] || { label: i.status, cls: 'mk-wh-proto-pill' };
			return '<tr>' +
				'<td><strong>' + escapeHtml(i.id) + '</strong></td>' +
				'<td>' + escapeHtml(i.customer) + '</td>' +
				'<td>' + escapeHtml(i.soRef || '—') + '</td>' +
				'<td>' + escapeHtml(fmtDateTime(i.createdAt)) + '</td>' +
				'<td><span class="' + escapeHtml(st.cls) + '">' + escapeHtml(st.label) + '</span></td>' +
				'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-action="outbound-detail" data-id="' + escapeHtml(i.id) + '">Chi tiết</button></td>' +
			'</tr>';
		}).join('');
	}

	function renderAll() {
		renderInbounds();
		renderQcQueue();
		renderStock();
		renderOutbound();
		updateKpis();
	}

	/* ===== Dialog (timeline) ===== */
	function openDialog(titleText, metaHtml, bodyHtml) {
		var dialog = qs('#mkWhProtoDialog');
		var title = qs('#mkWhProtoDialogTitle');
		var meta = qs('#mkWhProtoDialogMeta');
		var body = qs('#mkWhProtoDialogBody');
		if (!dialog || !title || !meta || !body) return;
		title.textContent = titleText || 'Phiếu';
		meta.innerHTML = metaHtml || '';
		body.innerHTML = bodyHtml || '';
		dialog.classList.add('is-open');
		dialog.setAttribute('aria-hidden', 'false');
	}

	function closeDialog() {
		var dialog = qs('#mkWhProtoDialog');
		if (!dialog) return;
		dialog.classList.remove('is-open');
		dialog.setAttribute('aria-hidden', 'true');
	}

	function roleBadge(roleKey) {
		if (roleKey === 'qc') return '<span class="mk-wh-proto-tag mk-wh-proto-tag--qc">QC</span>';
		if (roleKey === 'manager') return '<span class="mk-wh-proto-tag mk-wh-proto-tag--green">Quản lý kho</span>';
		return '<span class="mk-wh-proto-tag mk-wh-proto-tag--blue">Thủ kho</span>';
	}

	function receiptDialog(r) {
		var st = RECEIPT_STATUS[r.status] || { label: r.status, cls: 'mk-wh-proto-pill' };
		var linesHtml = (r.lines || []).map(function (l) {
			var qcPill = l.qcResult === 'pass'
				? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đạt</span>'
				: l.qcResult === 'fail'
					? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Không đạt</span>'
					: '—';
			return '<tr><td><strong>' + escapeHtml(l.name || '') + '</strong><div class="mk-wh-proto-muted">' + escapeHtml(l.sku || '') + '</div></td>' +
				'<td>' + escapeHtml(l.lot || '') + '<br/><span class="mk-wh-proto-muted">HSD: ' + escapeHtml(l.expiry || '—') + '</span></td>' +
				'<td>' + escapeHtml(l.qty) + '</td><td>' + qcPill + '</td></tr>';
		}).join('');

		var timelineHtml =
			'<div class="mk-wh-proto-timeline">' +
			((r.timeline || []).map(function (ev) {
				return '<div class="mk-wh-proto-timeline-item">' +
					'<strong>' + escapeHtml(ev.action || '—') + '</strong>' +
					roleBadge(ev.role) +
					'<div class="mk-wh-proto-muted">' + escapeHtml((ev.by || '—') + ' · ' + fmtDateTime(ev.at)) + '</div>' +
					(ev.note ? '<div class="mk-wh-proto-quote">"' + escapeHtml(ev.note) + '"</div>' : '') +
				'</div>';
			}).join('')) +
			'</div>';

		var role = getRole();
		var actions = '';
		if (r.status === 'draft' && role === 'keeper') {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="send-qc" data-id="' + escapeHtml(r.id) + '">Gửi QC</button>' +
				'</div>';
		}
		if (r.status === 'pending_qc' && role === 'qc') {
			actions = '<div class="mk-wh-proto-dialog-section-title" style="margin-top:12px;">Ghi nhận kết quả QC</div>' +
				'<textarea class="mk-wh-proto-textarea" data-mk-qc-note="1" placeholder="Ghi chú kiểm tra (cảm quan, chứng từ, bao bì...)"></textarea>' +
				'<div class="mk-wh-proto-cta-row">' +
				'<button class="mk-wh-proto-cta mk-wh-proto-cta--pass" type="button" data-mk-action="qc-pass" data-id="' + escapeHtml(r.id) + '"><span>✔</span> Đạt</button>' +
				'<button class="mk-wh-proto-cta mk-wh-proto-cta--fail" type="button" data-mk-action="qc-fail" data-id="' + escapeHtml(r.id) + '"><span>✕</span> Không đạt</button>' +
				'</div>';
		}
		if (r.status === 'qc_passed' && role === 'manager') {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="mgr-approve" data-id="' + escapeHtml(r.id) + '">Duyệt phiếu</button>' +
				'</div>';
		}
		if (r.status === 'approved' && role === 'keeper') {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="store" data-id="' + escapeHtml(r.id) + '">Nhập kho</button>' +
				'</div>';
		}

		return {
			title: 'Phiếu nhập ' + r.id,
			meta: 'NCC: ' + escapeHtml(r.supplier) + ' · PO: ' + escapeHtml(r.poRef),
			body:
				'<div style="margin-bottom:10px;"><span class="' + escapeHtml(st.cls || 'mk-wh-proto-pill') + '">' + escapeHtml(st.label) + '</span></div>' +
				'<div class="mk-wh-proto-dialog-grid">' +
					'<div>' +
						'<div class="mk-wh-proto-dialog-section-title">Chi tiết hàng hóa</div>' +
						'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô / HSD</th><th>SL</th><th>QC</th></tr></thead><tbody>' +
						linesHtml +
						'</tbody></table>' +
						actions +
					'</div>' +
					'<div>' +
						'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
						timelineHtml +
					'</div>' +
				'</div>',
		};
	}

	function issueDialog(issue) {
		var st = ISSUE_STATUS[issue.status] || { label: issue.status, cls: 'mk-wh-proto-pill' };
		var linesHtml = (issue.lines || []).map(function (l) {
			return '<tr><td><strong>' + escapeHtml(l.name) + '</strong><div class="mk-wh-proto-muted">' + escapeHtml(l.sku) + '</div></td>' +
				'<td>' + escapeHtml(l.lot) + '</td><td>' + escapeHtml(l.qty) + '</td></tr>';
		}).join('');
		var timelineHtml =
			'<div class="mk-wh-proto-timeline">' +
			((issue.timeline || []).map(function (ev) {
				return '<div class="mk-wh-proto-timeline-item">' +
					'<strong>' + escapeHtml(ev.action || '—') + '</strong>' +
					roleBadge(ev.role) +
					'<div class="mk-wh-proto-muted">' + escapeHtml((ev.by || '—') + ' · ' + fmtDateTime(ev.at)) + '</div>' +
					(ev.note ? '<div class="mk-wh-proto-quote">"' + escapeHtml(ev.note) + '"</div>' : '') +
				'</div>';
			}).join('')) +
			'</div>';

		var role = getRole();
		var actions = '';
		if (issue.status === 'draft' && role === 'keeper') {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-submit" data-id="' + escapeHtml(issue.id) + '">Gửi duyệt</button>' +
				'</div>';
		}
		if (issue.status === 'pending_approval' && role === 'manager') {
			actions = '<div class="mk-wh-proto-dialog-section-title" style="margin-top:12px;">Lý do (nếu từ chối)</div>' +
				'<textarea class="mk-wh-proto-textarea" data-mk-reject-reason="1" placeholder="VD: vượt hạn mức tín dụng"></textarea>' +
				'<div class="mk-wh-proto-cta-row" style="margin-top:10px;">' +
				'<button class="mk-wh-proto-cta mk-wh-proto-cta--fail" type="button" data-mk-action="issue-reject" data-id="' + escapeHtml(issue.id) + '"><span>✕</span> Từ chối</button>' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-approve" data-id="' + escapeHtml(issue.id) + '">Duyệt phiếu</button>' +
				'</div>';
		}
		if (issue.status === 'approved' && role === 'keeper') {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-ship" data-id="' + escapeHtml(issue.id) + '">Soạn &amp; giao hàng</button>' +
				'</div>';
		}

		return {
			title: 'Phiếu xuất ' + issue.id,
			meta: 'KH: ' + escapeHtml(issue.customer) + ' · SO: ' + escapeHtml(issue.soRef || '—'),
			body:
				'<div style="margin-bottom:10px;"><span class="' + escapeHtml(st.cls || 'mk-wh-proto-pill') + '">' + escapeHtml(st.label) + '</span></div>' +
				'<div class="mk-wh-proto-dialog-grid">' +
					'<div>' +
						'<div class="mk-wh-proto-dialog-section-title">Chi tiết xuất hàng</div>' +
						'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô</th><th>SL xuất</th></tr></thead><tbody>' +
						linesHtml +
						'</tbody></table>' +
						actions +
					'</div>' +
					'<div>' +
						'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
						timelineHtml +
					'</div>' +
				'</div>',
		};
	}

	function patchReceipt(id, fn) {
		var whId = getWhId();
		if (!whId) return;
		var d = S.ensureData(whId);
		var next = (d.receipts || []).map(function (r) {
			return r.id === id ? fn(Object.assign({}, r, { lines: (r.lines || []).slice(), timeline: (r.timeline || []).slice() })) : r;
		});
		S.warehouseDataActions.setReceipts(whId, next);
	}

	function patchIssue(id, fn) {
		var whId = getWhId();
		if (!whId) return;
		var d = S.ensureData(whId);
		var next = (d.issues || []).map(function (i) {
			return i.id === id ? fn(Object.assign({}, i, { lines: (i.lines || []).slice(), timeline: (i.timeline || []).slice() })) : i;
		});
		S.warehouseDataActions.setIssues(whId, next);
	}

	function addTimeline(list, action, role, note) {
		list.push({ at: S.nowISO(), by: role === 'qc' ? 'QC Minh' : role === 'manager' ? 'QL Tuấn' : 'Thủ kho Hà', role: role, action: action, note: note || undefined });
	}

	function addStockFromReceiptLines(whId, lines) {
		var d = S.ensureData(whId);
		var stock = (d.stock || []).slice();
		(lines || []).forEach(function (l) {
			var passed = l.passedQty != null ? l.passedQty : l.qty;
			if (!passed || passed <= 0) return;
			var idx = stock.findIndex(function (s) { return s.sku === l.sku && s.lot === l.lot; });
			if (idx >= 0) {
				stock[idx] = Object.assign({}, stock[idx], {
					qty: (Number(stock[idx].qty) || 0) + passed,
					expiry: stock[idx].expiry || l.expiry,
					name: stock[idx].name || l.name,
				});
			} else {
				stock.push({
					sku: l.sku,
					name: l.name,
					lot: l.lot,
					expiry: l.expiry || '—',
					qty: passed,
					location: 'A1-02',
					price: 0,
				});
			}
		});
		S.warehouseDataActions.setStock(whId, stock);
	}

	function deductStockFromIssueLines(whId, lines) {
		var d = S.ensureData(whId);
		var stock = (d.stock || []).slice();
		(lines || []).forEach(function (l) {
			var idx = stock.findIndex(function (s) { return s.sku === l.sku && s.lot === l.lot; });
			if (idx >= 0) {
				stock[idx] = Object.assign({}, stock[idx], {
					qty: Math.max(0, (Number(stock[idx].qty) || 0) - (Number(l.qty) || 0)),
				});
			}
		});
		S.warehouseDataActions.setStock(whId, stock);
	}

	/* ===== Create receipt/issue modal: reuse existing multi-line modal for inbound for now ===== */
	function openCreateInbound() {
		var modal = qs('#mkWhProtoModal');
		var title = qs('#mkWhProtoModalTitle');
		var fields = qs('#mkWhProtoFormFields');
		var submit = qs('#mkWhProtoSubmitBtn');
		var form = qs('#mkWhProtoModalForm');
		if (!modal || !title || !fields || !form) return;

		var dialog = modal.querySelector('.mk-wh-proto-modal__dialog');
		if (dialog) dialog.classList.add('mk-wh-proto-modal__dialog--compact');

		title.textContent = 'Tạo phiếu nhập kho';
		if (submit) submit.textContent = 'Tạo phiếu';

		fields.innerHTML =
			'<div class="mk-wh-proto-field"><label>Nhà cung cấp *</label><input type="text" name="supplier" required placeholder="VD: CTY Dược Hậu Giang" /></div>' +
			'<div class="mk-wh-proto-field"><label>Mã PO *</label><input type="text" name="po" required placeholder="VD: PO-2026-0155" /></div>' +
			'<div class="mk-wh-proto-field mk-wh-proto-field--full">' +
				'<label>Danh sách hàng nhập</label>' +
				'<div class="mk-wh-proto-lines" data-mk-lines="1">' +
					'<div class="mk-wh-proto-lines__head">' +
						'<span class="mk-wh-proto-lines__ttl">Thêm nhiều dòng hàng trong 1 phiếu</span>' +
						'<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost mk-wh-proto-lines__add" data-mk-lines-add="1">+ Thêm dòng</button>' +
					'</div>' +
					'<div class="mk-wh-proto-lines__tableWrap">' +
						'<table class="mk-wh-proto-lines__table" role="table">' +
							'<thead><tr>' +
								'<th style="min-width:220px;">Tên hàng *</th>' +
								'<th style="min-width:130px;">SKU</th>' +
								'<th>Lô *</th>' +
								'<th class="mk-wh-proto-td-right">SL *</th>' +
								'<th>NSX</th>' +
								'<th>HSD</th>' +
								'<th style="width:44px;"></th>' +
							'</tr></thead>' +
							'<tbody data-mk-lines-body="1"></tbody>' +
						'</table>' +
					'</div>' +
				'</div>' +
			'</div>' +
			'<div class="mk-wh-proto-field mk-wh-proto-field--check mk-wh-proto-field--full">' +
				'<label class="mk-wh-proto-check">' +
					'<span class="mk-wh-proto-check__box">' +
						'<input class="mk-wh-proto-check__input" type="checkbox" name="sendQc" value="1" checked="checked" />' +
						'<span class="mk-wh-proto-check__visual" aria-hidden="true">' +
							'<svg class="mk-wh-proto-check__icon" width="12" height="12" viewBox="0 0 12 12" fill="none">' +
								'<path d="M2.2 6.1 4.8 8.7 9.8 3.3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>' +
							'</svg>' +
						'</span>' +
					'</span>' +
					'<span class="mk-wh-proto-check__text">' +
						'<span class="mk-wh-proto-check__label">Gửi QC</span>' +
						'<span class="mk-wh-proto-check__hint">Bỏ chọn nếu hàng không cần QC — nhập thẳng tồn kho</span>' +
					'</span>' +
				'</label>' +
			'</div>';

		var bodyEl = form.querySelector('[data-mk-lines-body="1"]');

		function guessSkuFromName(name, idx) {
			var clean = String(name || '').trim();
			if (!clean) return 'SKU-' + String(idx).padStart(2, '0');
			var words = clean.split(/\s+/).filter(Boolean);
			var initials = words.slice(0, 2).map(function (w) { return (w[0] || '').toUpperCase(); }).join('');
			var digits = (clean.match(/\d+/) || [])[0] || '';
			var base = initials || 'SP';
			return base + (digits ? '-' + digits : '') + '-' + String(idx).padStart(2, '0');
		}

		function addRow(preset) {
			if (!bodyEl) return;
			var p = preset || {};
			var idx = bodyEl.children.length + 1;
			var skuVal = p.sku || guessSkuFromName(p.name || '', idx);
			var tr =
				'<tr class="mk-wh-proto-lines__row" data-mk-line="1">' +
					'<td><input type="text" data-mk-line-name="1" value="' + escapeHtml(p.name || '') + '" required placeholder="VD: Paracetamol 500mg" /></td>' +
					'<td><input type="text" data-mk-line-sku="1" value="' + escapeHtml(skuVal) + '" readonly /></td>' +
					'<td><input type="text" data-mk-line-lot="1" value="' + escapeHtml(p.lot || '') + '" required /></td>' +
					'<td><input type="number" min="1" step="1" data-mk-line-qty="1" value="' + escapeHtml(p.qty != null ? p.qty : '') + '" required /></td>' +
					'<td><input type="date" data-mk-line-mfg="1" value="' + escapeHtml(p.mfg || '') + '" /></td>' +
					'<td><input type="date" data-mk-line-exp="1" value="' + escapeHtml(p.expiry || '') + '" /></td>' +
					'<td><button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost" data-mk-lines-del="1" title="Xóa dòng">×</button></td>' +
				'</tr>';
			bodyEl.insertAdjacentHTML('beforeend', tr);
		}
		if (bodyEl && !bodyEl.children.length) {
			addRow();
			addRow();
		}

		var addBtn = form.querySelector('[data-mk-lines-add="1"]');
		if (addBtn) addBtn.onclick = function () { addRow(); };

		function closeModal() {
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
		}

		modal.onclick = function (e) {
			var t = e.target;
			if (t && t.getAttribute && t.getAttribute('data-mk-close') === '1') closeModal();
		};

		form.onclick = function (e) {
			var t = e.target;
			if (t && t.getAttribute && t.getAttribute('data-mk-lines-del') === '1') {
				e.preventDefault();
				var row = t.closest('[data-mk-line="1"]');
				if (row && bodyEl && bodyEl.children.length > 1) row.remove();
			}
		};

		form.oninput = function (e) {
			var t = e.target;
			if (!t || !(t.getAttribute && t.getAttribute('data-mk-line-name') === '1')) return;
			var row = t.closest('[data-mk-line="1"]');
			if (!row) return;
			var skuEl = row.querySelector('[data-mk-line-sku="1"]');
			if (!skuEl) return;
			var idx = Array.prototype.indexOf.call(bodyEl.children, row) + 1;
			skuEl.value = guessSkuFromName(t.value, idx);
		};

		form.onsubmit = function (e) {
			e.preventDefault();
			var whId = getWhId();
			if (!whId) return;
			var d = S.ensureData(whId);

			var supplier = String((form.querySelector('[name="supplier"]') || {}).value || '').trim();
			var poRef = String((form.querySelector('[name="po"]') || {}).value || '').trim();
			var sendQc = !!(form.querySelector('[name="sendQc"]') && form.querySelector('[name="sendQc"]').checked);
			if (!supplier || !poRef) return;

			var rows = Array.prototype.slice.call(form.querySelectorAll('[data-mk-line="1"]'));
			var lines = [];
			rows.forEach(function (row) {
				var sku = row.querySelector('[data-mk-line-sku="1"]') ? row.querySelector('[data-mk-line-sku="1"]').value.trim() : '';
				var name = row.querySelector('[data-mk-line-name="1"]') ? row.querySelector('[data-mk-line-name="1"]').value.trim() : '';
				var lot = row.querySelector('[data-mk-line-lot="1"]') ? row.querySelector('[data-mk-line-lot="1"]').value.trim() : '';
				var qty = row.querySelector('[data-mk-line-qty="1"]') ? (parseInt(row.querySelector('[data-mk-line-qty="1"]').value, 10) || 0) : 0;
				var mfg = row.querySelector('[data-mk-line-mfg="1"]') ? row.querySelector('[data-mk-line-mfg="1"]').value : '';
				var expiry = row.querySelector('[data-mk-line-exp="1"]') ? row.querySelector('[data-mk-line-exp="1"]').value : '';
				if (!name || !lot || qty <= 0) return;
				lines.push({ sku: sku || guessSkuFromName(name, lines.length + 1), name: name, lot: lot, mfg: mfg || '', expiry: expiry || '—', qty: qty });
			});
			if (!lines.length) return;

			var now = S.nowISO();
			var id = nextId('GRN', (d.receipts || []));
			var receipt = {
				id: id,
				supplier: supplier,
				poRef: poRef,
				createdAt: now,
				createdBy: 'Thủ kho Hà',
				status: sendQc ? 'pending_qc' : 'stored',
				lines: lines,
				timeline: [],
			};
			addTimeline(receipt.timeline, 'Tạo phiếu nhập', 'keeper');
			if (sendQc) addTimeline(receipt.timeline, 'Gửi QC kiểm tra', 'keeper');
			else addTimeline(receipt.timeline, 'Nhập thẳng tồn kho', 'keeper', 'Không gửi QC — cộng tồn ngay');

			var receipts = (d.receipts || []).slice();
			receipts.unshift(receipt);
			S.warehouseDataActions.setReceipts(whId, receipts);

			if (!sendQc) addStockFromReceiptLines(whId, lines);

			closeModal();
		};

		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
	}

	function boot() {
		if (!qs('#mkWhPrototypeRoot')) return;
		S.hydrate();
		if (!renderHeader()) return;

		// Ensure role select uses Prototype UI options (qc/stock/manager)
		// If current value is keeper, map to stock for UI.
		setRoleUI(getRole());
		updateRoleBanner();

		qsa('.mk-wh-proto-tab').forEach(function (b) {
			b.addEventListener('click', function () {
				setActiveTab(b.getAttribute('data-tab'));
			});
		});

		var roleSel = qs('#mkWhDetailRole');
		if (roleSel) roleSel.addEventListener('change', function () {
			updateRoleBanner();
			renderAll();
		});

		var resetBtn = qs('#mkWhProtoFilterReset');
		if (resetBtn) resetBtn.addEventListener('click', function () {
			var h = qs('#mkWhProtoFilterHsd');
			var n = qs('#mkWhProtoFilterName');
			var p = qs('#mkWhProtoFilterPrice');
			if (h) h.value = 'all';
			if (n) n.value = 'az';
			if (p) p.value = 'all';
			renderStock();
		});
		['#mkWhProtoFilterHsd', '#mkWhProtoFilterName', '#mkWhProtoFilterPrice'].forEach(function (sel) {
			var el = qs(sel);
			if (!el) return;
			el.addEventListener('change', renderStock);
		});

		var createBtn = qs('#mkWhProtoCreateBtn');
		if (createBtn) createBtn.addEventListener('click', function () {
			if (createBtn.disabled || createBtn.classList.contains('hide')) return;
			var active = qs('.mk-wh-proto-tab.is-active');
			var tabKey = active ? active.getAttribute('data-tab') : 'inbound';
			if (tabKey === 'inbound') {
				openCreateInbound();
			}
		});

		document.addEventListener('click', function (e) {
			var t = e.target;
			if (!t) return;
			if (t.getAttribute && t.getAttribute('data-mk-dialog-close') === '1') {
				closeDialog();
				return;
			}
			while (t && t !== document.body && !(t.getAttribute && t.getAttribute('data-mk-action'))) {
				t = t.parentElement;
			}
			if (!t || !t.getAttribute) return;
			var action = t.getAttribute('data-mk-action');
			var id = t.getAttribute('data-id');
			if (!action) return;

			var whId = getWhId();
			if (!whId) return;
			var d = S.ensureData(whId);

			if (action === 'inbound-detail' && id) {
				var r = (d.receipts || []).find(function (x) { return x.id === id; });
				if (!r) return;
				var dialog = receiptDialog(r);
				openDialog(dialog.title, dialog.meta, dialog.body);
				return;
			}
			if (action === 'qc-record' && id) {
				var r2 = (d.receipts || []).find(function (x) { return x.id === id; });
				if (!r2) return;
				var dialog2 = receiptDialog(r2);
				openDialog(dialog2.title, dialog2.meta, dialog2.body);
				return;
			}
			if (action === 'outbound-detail' && id) {
				var issue = (d.issues || []).find(function (x) { return x.id === id; });
				if (!issue) return;
				var dlg = issueDialog(issue);
				openDialog(dlg.title, dlg.meta, dlg.body);
				return;
			}

			// Receipt actions
			if (id && (action === 'send-qc' || action === 'qc-pass' || action === 'qc-fail' || action === 'mgr-approve' || action === 'store')) {
				var role = getRole();
				patchReceipt(id, function (r) {
					if (action === 'send-qc') {
						r.status = 'pending_qc';
						addTimeline(r.timeline, 'Gửi QC kiểm tra', role);
					} else if (action === 'qc-pass') {
						var noteEl = qs('[data-mk-qc-note="1"]');
						var note = noteEl ? String(noteEl.value || '').trim() : '';
						r.status = 'qc_passed';
						r.lines = (r.lines || []).map(function (l) { return Object.assign({}, l, { qcResult: 'pass', passedQty: l.qty }); });
						addTimeline(r.timeline, 'QC đạt', role, note || undefined);
					} else if (action === 'qc-fail') {
						var noteEl2 = qs('[data-mk-qc-note="1"]');
						var note2 = noteEl2 ? String(noteEl2.value || '').trim() : '';
						r.status = 'qc_failed';
						r.lines = (r.lines || []).map(function (l) { return Object.assign({}, l, { qcResult: 'fail', passedQty: 0 }); });
						addTimeline(r.timeline, 'QC không đạt', role, note2 || undefined);
					} else if (action === 'mgr-approve') {
						r.status = 'approved';
						addTimeline(r.timeline, 'Duyệt phiếu', role);
					} else if (action === 'store') {
						addStockFromReceiptLines(whId, r.lines || []);
						r.status = 'stored';
						addTimeline(r.timeline, 'Đã nhập kho', role, 'Vị trí: A1-02');
					}
					return r;
				});
				closeDialog();
				return;
			}

			// Issue actions
			if (id && (action === 'issue-submit' || action === 'issue-approve' || action === 'issue-reject' || action === 'issue-ship')) {
				var role2 = getRole();
				patchIssue(id, function (i) {
					if (action === 'issue-submit') {
						i.status = 'pending_approval';
						addTimeline(i.timeline, 'Gửi duyệt', role2);
					} else if (action === 'issue-approve') {
						i.status = 'approved';
						addTimeline(i.timeline, 'Duyệt phiếu', role2);
					} else if (action === 'issue-reject') {
						var rs = qs('[data-mk-reject-reason="1"]');
						var reason = rs ? String(rs.value || '').trim() : '';
						i.status = 'rejected';
						addTimeline(i.timeline, 'Từ chối phiếu', role2, reason || 'Không nêu lý do');
					} else if (action === 'issue-ship') {
						deductStockFromIssueLines(whId, i.lines || []);
						i.status = 'shipped';
						addTimeline(i.timeline, 'Soạn hàng & giao', role2);
					}
					return i;
				});
				closeDialog();
				return;
			}
		});

		S.subscribe(function () {
			renderHeader();
			updateRoleBanner();
			renderAll();
		});

		setActiveTab('inbound');
		renderAll();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

