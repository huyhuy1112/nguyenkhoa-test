/**
 * Warehouse Management UI — Danh sách kho, Dashboard, Chi tiết kho.
 * Uses MkWarehouseStore (localStorage). No backend.
 */
(function ($) {
	'use strict';

	var S = window.MkWarehouseStore;
	if (!S) return;

	function qs(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}

	function escapeHtml(s) {
		return String(s || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function decodeEntities(s) {
		var text = String(s || '');
		if (!text) {
			return '';
		}
		var el = document.createElement('textarea');
		var prev = null;
		var guard = 0;
		while (text !== prev && guard < 6) {
			prev = text;
			if (!/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(text)) {
				break;
			}
			el.innerHTML = text;
			text = el.value;
			guard += 1;
		}
		return text;
	}

	function escText(s) {
		return escapeHtml(decodeEntities(s));
	}

	function fmtDate(iso) {
		if (!iso) return '—';
		try {
			return new Date(iso).toLocaleDateString('vi-VN');
		} catch (e) {
			return iso;
		}
	}

	function fmtDateTime(iso) {
		if (!iso) return '—';
		try {
			return new Date(iso).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' });
		} catch (e) {
			return iso;
		}
	}

	var ICON = {
		warehouse: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" stroke="currentColor" stroke-width="1.6"/><path d="M3.3 7.7 12 12l8.7-4.3" stroke="currentColor" stroke-width="1.6"/><path d="M12 22V12" stroke="currentColor" stroke-width="1.6"/></svg>',
		mapPin: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg>',
		arrowRight: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="m13 6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		pencil: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20h9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
		archive: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7h18v3H3z" stroke="currentColor" stroke-width="1.6"/><path d="M5 10v9h14v-9" stroke="currentColor" stroke-width="1.6"/><path d="M10 14h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
		trash: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M9 7V5h6v2" stroke="currentColor" stroke-width="1.6"/><path d="M7 7l1 12h8l1-12" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
		plus: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		boxes: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 3 7.5 12 12l9-4.5L12 3Z" stroke="currentColor" stroke-width="1.6"/><path d="M3 12.5 12 17l9-4.5" stroke="currentColor" stroke-width="1.6"/><path d="M3 17.5 12 22l9-4.5" stroke="currentColor" stroke-width="1.6"/></svg>',
		clock: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
		file: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8l4 4v12H8z" stroke="currentColor" stroke-width="1.6"/><path d="M16 4v4h4" stroke="currentColor" stroke-width="1.6"/></svg>',
		alert: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M10.3 4.2 2.7 18a2 2 0 0 0 1.8 3h15a2 2 0 0 0 1.8-3L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6"/></svg>',
	};

	function statusBadge(status) {
		var map = {
			active: 'mk-wh-mgmt-badge--active',
			inactive: 'mk-wh-mgmt-badge--inactive',
			archived: 'mk-wh-mgmt-badge--archived',
		};
		var cls = 'mk-wh-mgmt-badge ' + (map[status] || 'mk-wh-mgmt-badge--inactive');
		return '<span class="' + cls + '">' + escapeHtml(S.STATUS_LABEL[status] || status) + '</span>';
	}

	function statusPill(status) {
		return statusBadge(status);
	}

	function detailUrl(id) {
		return 'index.php?module=Warehouse&view=WhDetail&whId=' + encodeURIComponent(id) + '&app=INVENTORY';
	}

	/* ========== LIST PAGE ========== */
	function renderList() {
		var grid = qs('#mkWhMgmtCardGrid');
		var tbody = qs('#mkWhMgmtTableBody');
		if (!grid || !tbody) return;

		var state = S.getState();
		var htmlCards = '';
		var htmlRows = '';

		state.warehouses.forEach(function (w) {
			var skus = S.skuCountOf(w.id);
			var stock = S.totalStockOf(w.id);
			htmlCards +=
				'<article class="mk-wh-mgmt-card">' +
				'<div class="mk-wh-mgmt-card__top">' +
				'<div class="mk-wh-mgmt-card__identity">' +
				'<div class="mk-wh-mgmt-card__code">' + escText(w.code) + '</div>' +
				'<div class="mk-wh-mgmt-card__title-row">' +
				'<span class="mk-wh-mgmt-card__icon">' + ICON.warehouse + '</span>' +
				'<span class="mk-wh-mgmt-card__name">' + escText(w.name) + '</span></div></div>' +
				statusBadge(w.status) +
				'</div>' +
				'<div class="mk-wh-mgmt-card__meta">' +
				'<div class="mk-wh-mgmt-card__meta-row"><span>' + escapeHtml(S.TYPE_LABEL[w.type] || w.type) + '</span></div>' +
				'<div class="mk-wh-mgmt-card__meta-row">' + ICON.mapPin + '<span>' + escText(w.address || '—') + '</span></div>' +
				'<div class="mk-wh-mgmt-card__meta-row"><span>QL: ' + escText(w.manager || '—') + '</span></div>' +
				'</div>' +
				'<div class="mk-wh-mgmt-card__stats">' +
				'<div><span class="mk-wh-mgmt-card__stat-label">SKU</span><span class="mk-wh-mgmt-card__stat-value">' + skus + '</span></div>' +
				'<div><span class="mk-wh-mgmt-card__stat-label">Tồn</span><span class="mk-wh-mgmt-card__stat-value">' + stock.toLocaleString('vi-VN') + '</span></div>' +
				'</div>' +
				'<div class="mk-wh-mgmt-card__actions">' +
				'<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--enter" href="' + detailUrl(w.id) + '"><span>Vào kho</span><span class="mk-wh-mgmt-btn__chev" aria-hidden="true">→</span></a>' +
				'<div class="mk-wh-mgmt-card__actions-secondary">' +
				'<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline mk-wh-mgmt-btn--icon" title="Sửa" data-mk-wh-edit="' + escapeHtml(w.id) + '">' + ICON.pencil + '</button>' +
				'<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline mk-wh-mgmt-btn--icon" title="Lưu trữ" data-mk-wh-archive="' + escapeHtml(w.id) + '">' + ICON.archive + '</button>' +
				'<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline mk-wh-mgmt-btn--icon mk-wh-mgmt-btn--danger" title="Xóa" data-mk-wh-delete="' + escapeHtml(w.id) + '">' + ICON.trash + '</button>' +
				'</div></div></article>';

			htmlRows +=
				'<tr>' +
				'<td><span class="mk-wh-mgmt-chip">' + escText(w.code) + '</span></td>' +
				'<td><strong>' + escText(w.name) + '</strong></td>' +
				'<td>' + escapeHtml(S.TYPE_LABEL[w.type] || w.type) + '</td>' +
				'<td class="mk-wh-mgmt-muted">' + escText(w.address || '—') + '</td>' +
				'<td>' + escText(w.manager || '—') + '</td>' +
				'<td class="mk-wh-mgmt-td-right">' + skus + '</td>' +
				'<td class="mk-wh-mgmt-td-right"><strong>' + stock.toLocaleString('vi-VN') + '</strong></td>' +
				'<td>' + statusBadge(w.status) + '</td>' +
				'<td class="mk-wh-mgmt-muted">' + fmtDate(w.createdAt) + '</td>' +
				'<td class="mk-wh-mgmt-td-right">' +
				'<a class="mk-wh-mgmt-link" href="' + detailUrl(w.id) + '">Vào</a>' +
				'</td></tr>';
		});

		grid.innerHTML = htmlCards || '<p class="mk-wh-mgmt-empty">Chưa có kho nào.</p>';
		tbody.innerHTML = htmlRows || '<tr><td colspan="10" class="mk-wh-mgmt-empty">Chưa có kho nào.</td></tr>';
	}

	function bindListEvents() {
		var root = qs('#mkWhMgmtRoot');
		var modal = qs('#mkWhMgmtFormModal');
		var form = qs('#mkWhMgmtForm');
		var createBtn = qs('#mkWhMgmtCreateBtn');

		if (!root) return;

		function openModal(editId) {
			if (!modal) return;
			var title = qs('#mkWhMgmtFormTitle');
			var submit = qs('#mkWhMgmtFormSubmit');
			var editInput = qs('#mkWhMgmtEditId');
			if (editId) {
				var w = S.getState().warehouses.find(function (x) { return x.id === editId; });
				if (!w) return;
				if (title) title.textContent = 'Sửa kho';
				if (submit) submit.textContent = 'Lưu';
				if (editInput) editInput.value = w.id;
				qs('#mkWhMgmtCode').value = decodeEntities(w.code);
				qs('#mkWhMgmtName').value = decodeEntities(w.name);
				qs('#mkWhMgmtType').value = w.type;
				qs('#mkWhMgmtStatus').value = w.status;
				qs('#mkWhMgmtAddress').value = decodeEntities(w.address || '');
				qs('#mkWhMgmtManager').value = decodeEntities(w.manager || '');
			} else {
				if (title) title.textContent = 'Tạo kho mới';
				if (submit) submit.textContent = 'Tạo';
				if (editInput) editInput.value = '';
				if (form) form.reset();
				qs('#mkWhMgmtType').value = 'branch';
				qs('#mkWhMgmtStatus').value = 'active';
			}
			modal.classList.remove('hide');
		}

		function closeModal() {
			if (modal) modal.classList.add('hide');
		}

		if (createBtn) {
			createBtn.addEventListener('click', function () { openModal(null); });
		}

		root.addEventListener('click', function (e) {
			var t = e.target;
			if (t.getAttribute && t.getAttribute('data-mk-wh-close') === '1') {
				closeModal();
				return;
			}
			var editId = t.getAttribute && t.getAttribute('data-mk-wh-edit');
			if (editId) { e.preventDefault(); openModal(editId); return; }
			var archiveId = t.getAttribute && t.getAttribute('data-mk-wh-archive');
			if (archiveId) {
				e.preventDefault();
				S.warehouseActions.archive(archiveId);
				renderList();
				return;
			}
			var deleteId = t.getAttribute && t.getAttribute('data-mk-wh-delete');
			if (deleteId) {
				e.preventDefault();
				var w = S.getState().warehouses.find(function (x) { return x.id === deleteId; });
				if (w && window.confirm('Xóa ' + decodeEntities(w.name) + '?')) {
					S.warehouseActions.remove(deleteId);
					renderList();
				}
			}
		});

		if (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				var code = qs('#mkWhMgmtCode').value.trim();
				var name = qs('#mkWhMgmtName').value.trim();
				if (!code || !name) return;
				var payload = {
					code: code,
					name: name,
					type: qs('#mkWhMgmtType').value,
					status: qs('#mkWhMgmtStatus').value,
					address: qs('#mkWhMgmtAddress').value.trim(),
					manager: qs('#mkWhMgmtManager').value.trim(),
				};
				var editId = qs('#mkWhMgmtEditId').value;
				if (editId) {
					S.warehouseActions.update(editId, payload);
				} else {
					S.warehouseActions.create(payload);
				}
				closeModal();
				renderList();
			});
		}
	}

	/* ========== DASHBOARD PAGE ========== */
	function renderDashboard() {
		var kpiEl = qs('#mkWhDashKpis');
		var tbody = qs('#mkWhDashTableBody');
		if (!kpiEl || !tbody) return;

		var summary = S.computeSummary();
		kpiEl.innerHTML =
			kpiCard(ICON.warehouse, 'Tổng số kho', summary.perWh.length, false) +
			kpiCard(ICON.boxes, 'Tổng tồn kho', summary.totalStock.toLocaleString('vi-VN'), false) +
			kpiCard(ICON.clock, 'Chờ QC', summary.pendingQC, false) +
			kpiCard(ICON.file, 'Xuất chờ duyệt', summary.pendingExport, false) +
			kpiCard(ICON.alert, 'Lô sắp hết hạn', summary.expiring, summary.expiring > 0);

		var rows = '';
		summary.perWh.forEach(function (item) {
			var w = item.w;
			rows +=
				'<tr>' +
				'<td><div class="mk-wh-mgmt-card__name">' + escText(w.name) + '</div>' +
				'<div class="mk-wh-mgmt-card__code">' + escText(w.code) + '</div></td>' +
				'<td>' + escText(w.manager || '—') + '</td>' +
				'<td class="mk-wh-mgmt-td-right">' + item.skus + '</td>' +
				'<td class="mk-wh-mgmt-td-right"><strong>' + item.stock.toLocaleString('vi-VN') + '</strong></td>' +
				'<td class="mk-wh-mgmt-td-right">' + item.pQC + '</td>' +
				'<td class="mk-wh-mgmt-td-right">' + item.pEx + '</td>' +
				'<td class="mk-wh-mgmt-td-right' + (item.exp > 0 ? ' mk-wh-mgmt-warn' : '') + '">' + item.exp + '</td>' +
				'<td>' + statusBadge(w.status) + '</td>' +
				'<td class="mk-wh-mgmt-td-right"><a class="mk-wh-mgmt-link" href="' + detailUrl(w.id) + '">Vào kho →</a></td>' +
				'</tr>';
		});
		tbody.innerHTML = rows || '<tr><td colspan="9" class="mk-wh-mgmt-empty">Chưa có dữ liệu.</td></tr>';
	}

	function kpiCard(iconSvg, label, value, warn) {
		return '<article class="mk-wh-mgmt-kpi' + (warn ? ' mk-wh-mgmt-kpi--danger' : '') + '">' +
			'<div class="mk-wh-mgmt-kpi__label">' + iconSvg + '<span>' + escapeHtml(label) + '</span></div>' +
			'<div class="mk-wh-mgmt-kpi__value">' + escapeHtml(String(value)) + '</div></article>';
	}

	/* ========== DETAIL PAGE ========== */
	var RECEIPT_STATUS = {
		draft: { label: 'Nháp', cls: 'mk-wh-proto-pill' },
		pending_qc: { label: 'Chờ QC', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
		qc_passed: { label: 'QC đạt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		qc_failed: { label: 'QC không đạt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--danger' },
		approved: { label: 'Đã duyệt', cls: 'mk-wh-proto-pill' },
		stored: { label: 'Đã nhập kho', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
	};

	var ISSUE_STATUS = {
		draft: { label: 'Nháp', cls: 'mk-wh-proto-pill' },
		waiting_print: { label: 'Chờ in phiếu', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-wait' },
		picking: { label: 'Đang soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-pick' },
		packed: { label: 'Đã soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-packed' },
		shipped: { label: 'Đã giao', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		rejected: { label: 'Từ chối', cls: 'mk-wh-proto-pill mk-wh-proto-pill--danger' },
		pending_approval: { label: 'Chờ in phiếu', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-wait' },
		approved: { label: 'Đã soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-packed' },
	};

	var ROLES = {
		qc: { label: 'QC', user: 'QC Minh', perms: 'Ghi nhận kết quả QC (Đạt/Không đạt) • Ghi chú kiểm tra' },
		manager: {
			label: 'Quản lý kho',
			user: 'QL Tuấn',
			perms: 'Tạo phiếu nhập/xuất • Gửi QC • Nhập kho • Soạn & giao hàng • Duyệt phiếu sau QC • Xem tồn kho',
		},
	};

	function getWhId() {
		var root = qs('#mkWhDetailRoot');
		return root ? (root.getAttribute('data-wh-id') || '') : '';
	}

	function getRole() {
		var sel = qs('#mkWhDetailRole');
		var val = sel ? sel.value : 'manager';
		if (val === 'stock' || val === 'keeper') return 'manager';
		return val || 'manager';
	}

	function isWarehouseOps(role) {
		return role === 'manager' || role === 'keeper' || role === 'stock';
	}

	function getWarehouse() {
		var id = getWhId();
		return S.getState().warehouses.find(function (w) { return w.id === id; });
	}

	function renderDetailHeader() {
		var w = getWarehouse();
		var title = qs('#mkWhDetailTitle');
		var desc = qs('#mkWhDetailDesc');
		if (!w) {
			if (title) title.textContent = 'Không tìm thấy kho';
			if (desc) desc.textContent = 'Kho không tồn tại hoặc đã bị xóa.';
			return false;
		}
		if (title) title.textContent = decodeEntities(w.name);
		if (desc) desc.textContent = decodeEntities(w.code) + ' · ' + decodeEntities(w.address || '—') + ' · QL: ' + decodeEntities(w.manager || '—');
		return true;
	}

	function renderDetailKpis() {
		var w = getWarehouse();
		if (!w) return;
		var d = S.ensureData(w.id);
		var pendingQC = (d.receipts || []).filter(function (r) { return r.status === 'pending_qc'; }).length;
		var pendingAp = (d.issues || []).filter(function (i) {
			return i.status !== 'shipped' && i.status !== 'rejected';
		}).length;
		var skus = {};
		(d.stock || []).forEach(function (s) { skus[s.sku] = true; });
		var expiring = (d.stock || []).filter(function (s) {
			var days = (new Date(s.expiry).getTime() - Date.now()) / 86400000;
			return days < 90 && s.qty > 0;
		}).length;

		// WhDetail (Prototype layout) KPI IDs
		var k1 = qs('#mkWhKpiPendingQc');
		var k2 = qs('#mkWhKpiPendingApprove');
		var k3 = qs('#mkWhKpiSku');
		var k4 = qs('#mkWhKpiExpiring');
		if (k1) k1.textContent = String(pendingQC);
		if (k2) k2.textContent = String(pendingAp);
		if (k3) k3.textContent = String(Object.keys(skus).length);
		if (k4) k4.textContent = String(expiring);

		// Backward compat (old WhDetail KPI container)
		var el = qs('#mkWhDetailKpis');
		if (el) {
			el.innerHTML =
				kpiCard(ICON.clock, 'Phiếu chờ QC', pendingQC, false) +
				kpiCard(ICON.file, 'Phiếu xuất chờ duyệt', pendingAp, false) +
				kpiCard(ICON.boxes, 'SKU đang lưu kho', Object.keys(skus).length, false) +
				kpiCard(ICON.alert, 'Lô sắp hết hạn (<90 ngày)', expiring, expiring > 0);
		}
	}

	function renderDetailPerms() {
		/* Permission summary rows removed from WhDetail UI */
	}

	function daysUntil(expiry) {
		try {
			return Math.round((new Date(expiry).getTime() - Date.now()) / 86400000);
		} catch (e) {
			return 999999;
		}
	}

	function renderWhDetailProtoTab(tab) {
		var w = getWarehouse();
		if (!w) return;
		var d = S.ensureData(w.id);

		var title = qs('#mkWhProtoStageTitle');
		var btn = qs('#mkWhProtoCreateBtn');

		var paneInbound = qs('#mkWhProtoPaneInbound');
		var paneQc = qs('#mkWhProtoPaneQc');
		var paneStock = qs('#mkWhProtoPaneStock');
		var paneOutbound = qs('#mkWhProtoPaneOutbound');

		if (title) {
			title.textContent =
				tab === 'qc' ? 'Hàng đợi QC'
				: tab === 'stock' ? 'Tồn kho'
				: tab === 'outbound' ? 'Danh sách phiếu xuất'
				: 'Danh sách phiếu nhập';
		}

		if (btn) {
			var canCreate = isWarehouseOps(getRole()) && (tab === 'inbound' || tab === 'outbound');
			btn.classList.toggle('hide', !canCreate);
			btn.textContent = tab === 'outbound' ? 'Tạo phiếu xuất' : 'Tạo phiếu nhập';
		}

		if (paneInbound) paneInbound.classList.toggle('hide', tab !== 'inbound');
		if (paneQc) paneQc.classList.toggle('hide', tab !== 'qc');
		if (paneStock) paneStock.classList.toggle('hide', tab !== 'stock');
		if (paneOutbound) paneOutbound.classList.toggle('hide', tab !== 'outbound');

		// Inbound table
		var inTbody = qs('#mkWhProtoInboundTbody');
		if (tab === 'inbound' && inTbody) {
			inTbody.innerHTML = (d.receipts || []).map(function (r) {
				var st = RECEIPT_STATUS[r.status] || { label: r.status, cls: 'mk-wh-proto-pill' };
				return '<tr>' +
					'<td><strong>' + escapeHtml(r.id) + '</strong></td>' +
					'<td>' + escapeHtml(r.supplier) + '</td>' +
					'<td>' + escapeHtml(r.poRef) + '</td>' +
					'<td>' + escapeHtml(fmtDateTime(r.createdAt)) + '</td>' +
					'<td><span class="' + st.cls + '">' + escapeHtml(st.label) + '</span></td>' +
					'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-open-receipt="' + escapeHtml(r.id) + '">Mở</button></td>' +
				'</tr>';
			}).join('');
		}

		// QC table
		var qcTbody = qs('#mkWhProtoQcTbody');
		if (tab === 'qc' && qcTbody) {
			var rows = [];
			(d.receipts || []).filter(function (r) { return r.status === 'pending_qc'; }).forEach(function (r) {
				(r.lines || []).forEach(function (l) {
					rows.push(
						'<tr>' +
							'<td><strong>' + escapeHtml(r.id) + '</strong></td>' +
							'<td>' + escapeHtml(r.supplier) + '</td>' +
							'<td>' + escapeHtml(l.name) + ' <span class="mk-wh-proto-muted">(' + escapeHtml(l.sku) + ')</span></td>' +
							'<td>' + escapeHtml(l.lot) + '</td>' +
							'<td>HSD: ' + escapeHtml(l.expiry || '—') + '</td>' +
							'<td>' + escapeHtml(l.qty) + '</td>' +
							'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-open-receipt="' + escapeHtml(r.id) + '"' +
								(getRole() !== 'qc' ? ' disabled' : '') +
							'>Ghi nhận QC</button></td>' +
						'</tr>'
					);
				});
			});
			qcTbody.innerHTML = rows.join('');
		}

		// Stock table + filters
		var stTbody = qs('#mkWhProtoStockTbody');
		if (tab === 'stock' && stTbody) {
			var hsd = qs('#mkWhProtoFilterHsd') ? qs('#mkWhProtoFilterHsd').value : 'all';
			var name = qs('#mkWhProtoFilterName') ? qs('#mkWhProtoFilterName').value : 'az';
			var list = (d.stock || []).slice();
			list = list.filter(function (s) {
				if ((Number(s.qty) || 0) <= 0) return false;
				var days = daysUntil(s.expiry);
				if (hsd === 'soon') return days >= 0 && days < 90;
				if (hsd === 'valid') return days >= 0;
				if (hsd === 'expired') return days < 0;
				return true;
			});
			list.sort(function (a, b) {
				var an = String(a.name || '').toLowerCase();
				var bn = String(b.name || '').toLowerCase();
				if (an < bn) return name === 'za' ? 1 : -1;
				if (an > bn) return name === 'za' ? -1 : 1;
				return 0;
			});
			var summary = qs('#mkWhProtoFilterSummary');
			if (summary) summary.textContent = list.length ? ('Hiển thị ' + list.length + ' dòng tồn') : 'Chưa có tồn kho phù hợp bộ lọc.';
			stTbody.innerHTML = list.map(function (s) {
				var days = daysUntil(s.expiry);
				var expLabel = days < 0 ? 'Quá hạn' : ('Còn ' + days + ' ngày');
				var hsdCls = 'mk-wh-proto-hsd' + (days < 0 ? ' mk-wh-proto-hsd--expired' : (days < 90 ? ' mk-wh-proto-hsd--soon' : ''));
				var qtyCls = (Number(s.qty) || 0) < 50 ? ' mk-wh-proto-qty--low' : '';
				return '<tr>' +
					'<td><strong>' + escapeHtml(s.sku) + '</strong></td>' +
					'<td>' + escapeHtml(s.name) + '</td>' +
					'<td>' + escapeHtml(s.lot) + '</td>' +
					'<td class="' + hsdCls + '">' + escapeHtml(s.expiry || '—') + ' <span class="mk-wh-proto-muted">(' + escapeHtml(expLabel) + ')</span></td>' +
					'<td class="mk-wh-proto-td-right">' + escapeHtml(s.location || '—') + '</td>' +
					'<td class="mk-wh-proto-td-right' + qtyCls + '"><strong>' + escapeHtml(s.qty) + '</strong></td>' +
				'</tr>';
			}).join('');
		}

		// Outbound table
		var outTbody = qs('#mkWhProtoOutboundTbody');
		if (tab === 'outbound' && outTbody) {
			outTbody.innerHTML = (d.issues || []).map(function (i) {
				var st = ISSUE_STATUS[i.status] || { label: i.status, cls: 'mk-wh-proto-pill' };
				return '<tr>' +
					'<td><strong>' + escapeHtml(i.id) + '</strong></td>' +
					'<td>' + escapeHtml(i.customer) + '</td>' +
					'<td>' + escapeHtml(i.soRef || '—') + '</td>' +
					'<td>' + escapeHtml(fmtDateTime(i.createdAt)) + '</td>' +
					'<td><span class="' + st.cls + '">' + escapeHtml(st.label) + '</span></td>' +
					'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-open-issue="' + escapeHtml(i.id) + '">Chi tiết</button></td>' +
				'</tr>';
			}).join('');
		}
	}

	function renderDetailPane(tab) {
		// If WhDetail uses Prototype markup, render into Prototype blocks instead
		if (qs('#mkWhProtoPaneInbound') && qs('#mkWhProtoStageTitle')) {
			renderWhDetailProtoTab(tab);
			return;
		}

		var pane = qs('#mkWhDetailPane');
		var stageTitle = qs('#mkWhDetailStageTitle');
		var createBtn = qs('#mkWhDetailCreateBtn');
		var w = getWarehouse();
		if (!pane || !w) return;

		var d = S.ensureData(w.id);
		var html = '';

		if (tab === 'inbound') {
			if (stageTitle) stageTitle.textContent = 'Danh sách phiếu nhập kho';
			if (createBtn) { createBtn.textContent = 'Tạo phiếu nhập'; createBtn.classList.toggle('hide', !isWarehouseOps(getRole())); }
			html = tableWrap(['Mã phiếu', 'NCC', 'PO', 'Số dòng', 'Ngày tạo', 'Trạng thái', 'Thao tác'],
				(d.receipts || []).map(function (r) {
					var st = RECEIPT_STATUS[r.status] || { label: r.status, cls: 'mk-wh-proto-pill' };
					return '<tr data-mk-receipt="' + escapeHtml(r.id) + '">' +
						'<td><span class="mk-gi-chip">' + escapeHtml(r.id) + '</span></td>' +
						'<td>' + escapeHtml(r.supplier) + '</td>' +
						'<td class="mk-wh-mgmt-muted">' + escapeHtml(r.poRef) + '</td>' +
						'<td>' + (r.lines ? r.lines.length : 0) + '</td>' +
						'<td class="mk-wh-mgmt-muted">' + fmtDateTime(r.createdAt) + '</td>' +
						'<td><span class="' + st.cls + '">' + escapeHtml(st.label) + '</span></td>' +
						'<td class="mk-wh-proto-td-right"><button type="button" class="mk-wh-proto-link" data-mk-open-receipt="' + escapeHtml(r.id) + '">Mở</button></td></tr>';
				}));
		} else if (tab === 'qc') {
			if (stageTitle) stageTitle.textContent = 'Hàng đợi QC';
			if (createBtn) createBtn.classList.add('hide');
			var qcRows = [];
			(d.receipts || []).filter(function (r) { return r.status === 'pending_qc'; }).forEach(function (r) {
				(r.lines || []).forEach(function (l) {
					qcRows.push('<tr><td>' + escapeHtml(r.id) + '</td><td>' + escapeHtml(r.supplier) + '</td>' +
						'<td>' + escapeHtml(l.name) + ' (' + escapeHtml(l.sku) + ')</td><td>' + escapeHtml(l.lot) + '</td>' +
						'<td>' + escapeHtml(l.expiry) + '</td><td>' + l.qty + '</td>' +
						'<td class="mk-wh-proto-td-right"><button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost" data-mk-open-receipt="' + escapeHtml(r.id) + '"' +
						(getRole() !== 'qc' ? ' disabled' : '') + '>Ghi nhận QC</button></td></tr>');
				});
			});
			html = qcRows.length
				? tableWrap(['Mã phiếu', 'NCC', 'Mặt hàng', 'Lô', 'HSD', 'SL', 'QC'], qcRows)
				: '<p class="mk-wh-mgmt-empty">Không có phiếu nào chờ QC.</p>';
		} else if (tab === 'stock') {
			if (stageTitle) stageTitle.textContent = 'Tồn kho theo SKU / Lô / Hạn dùng';
			if (createBtn) createBtn.classList.add('hide');
			html = tableWrap(['SKU', 'Tên hàng', 'Lô', 'HSD', 'Vị trí', 'Tồn'],
				(d.stock || []).map(function (s) {
					var days = Math.round((new Date(s.expiry).getTime() - Date.now()) / 86400000);
					var expCls = days < 90 ? ' mk-wh-mgmt-warn' : '';
					return '<tr><td class="mk-wh-mgmt-muted">' + escapeHtml(s.sku) + '</td><td>' + escapeHtml(s.name) + '</td>' +
						'<td>' + escapeHtml(s.lot) + '</td><td class="' + expCls.trim() + '">' + escapeHtml(s.expiry) +
						' <span class="mk-wh-mgmt-muted">(' + days + 'd)</span></td><td>' + escapeHtml(s.location) + '</td>' +
						'<td class="mk-wh-proto-td-right"><strong>' + s.qty + '</strong></td></tr>';
				}));
		} else if (tab === 'outbound') {
			if (stageTitle) stageTitle.textContent = 'Danh sách phiếu xuất kho';
			if (createBtn) { createBtn.textContent = 'Tạo phiếu xuất'; createBtn.classList.toggle('hide', !isWarehouseOps(getRole())); }
			html = tableWrap(['Mã phiếu', 'Khách hàng', 'SO', 'Số dòng', 'Ngày tạo', 'Trạng thái', 'Thao tác'],
				(d.issues || []).map(function (i) {
					var st = ISSUE_STATUS[i.status] || { label: i.status, cls: 'mk-wh-proto-pill' };
					return '<tr><td><span class="mk-gi-chip">' + escapeHtml(i.id) + '</span></td>' +
						'<td>' + escapeHtml(i.customer) + '</td><td class="mk-wh-mgmt-muted">' + escapeHtml(i.soRef) + '</td>' +
						'<td>' + (i.lines ? i.lines.length : 0) + '</td><td class="mk-wh-mgmt-muted">' + fmtDateTime(i.createdAt) + '</td>' +
						'<td><span class="' + st.cls + '">' + escapeHtml(st.label) + '</span></td>' +
						'<td class="mk-wh-proto-td-right"><button type="button" class="mk-wh-proto-link" data-mk-open-issue="' + escapeHtml(i.id) + '">Mở</button></td></tr>';
				}));
		}

		pane.innerHTML = html;
	}

	function tableWrap(headers, rows) {
		var th = headers.map(function (h) {
			var right = h === 'Tồn' || h === 'SL' || h === 'Thao tác' || h === 'QC';
			return '<th' + (right ? ' class="mk-wh-proto-td-right"' : '') + '>' + escapeHtml(h) + '</th>';
		}).join('');
		var body = rows.length ? rows.join('') : '<tr><td colspan="' + headers.length + '" class="mk-wh-mgmt-empty">Chưa có dữ liệu.</td></tr>';
		return '<div class="mk-wh-proto-table-wrap"><table class="mk-wh-proto-table"><thead><tr>' + th + '</tr></thead><tbody>' + body + '</tbody></table></div>';
	}

	function bindDetailEvents() {
		var root = qs('#mkWhMgmtRoot');
		var tabs = qs('#mkWhDetailTabs');
		var roleSel = qs('#mkWhDetailRole');
		var activeTab = 'inbound';

		if (!root) return;

		function refresh() {
			if (!renderDetailHeader()) return;
			renderDetailKpis();
			renderDetailPerms();
			renderDetailPane(activeTab);
		}

		if (tabs) {
			tabs.addEventListener('click', function (e) {
				var btn = e.target.closest('[data-tab]');
				if (!btn) return;
				activeTab = btn.getAttribute('data-tab');
				qsa('.mk-wh-proto-tab', tabs).forEach(function (b) {
					b.classList.toggle('is-active', b === btn);
				});
				renderDetailPane(activeTab);
			});
		}

		if (roleSel) {
			roleSel.addEventListener('change', refresh);
		}

		root.addEventListener('click', function (e) {
			var rid = e.target.getAttribute && e.target.getAttribute('data-mk-open-receipt');
			if (rid) { e.preventDefault(); openReceiptModal(rid); return; }
			var iid = e.target.getAttribute && e.target.getAttribute('data-mk-open-issue');
			if (iid) { e.preventDefault(); openIssueModal(iid); }
			if (e.target && e.target.id === 'mkWhProtoFilterReset') {
				var h = qs('#mkWhProtoFilterHsd');
				var n = qs('#mkWhProtoFilterName');
				if (h) h.value = 'all';
				if (n) n.value = 'az';
				renderDetailPane('stock');
			}
			if (e.target && e.target.id === 'mkWhProtoCreateBtn') {
				e.preventDefault();
				// Keep existing create receipt modal (multi-lines) for inbound; outbound can be added later
				if (activeTab === 'inbound') {
					openCreateReceiptModal();
				}
			}
		});

		root.addEventListener('change', function (e) {
			if (!e.target) return;
			if (activeTab !== 'stock') return;
			if (e.target.id === 'mkWhProtoFilterHsd' || e.target.id === 'mkWhProtoFilterName') {
				renderDetailPane('stock');
			}
		});

		S.subscribe(refresh);
		refresh();
	}

	function openReceiptModal(id) {
		var w = getWarehouse();
		if (!w) return;
		var d = S.ensureData(w.id);
		var r = (d.receipts || []).find(function (x) { return x.id === id; });
		if (!r) return;
	var dialog = qs('#mkWhProtoDialog');
	var body = qs('#mkWhProtoDialogBody');
	var meta = qs('#mkWhProtoDialogMeta');
	var title = qs('#mkWhProtoDialogTitle');
	if (!dialog || !body || !meta || !title) return;
	if (title) title.textContent = 'Phiếu nhập ' + r.id;
		var st = RECEIPT_STATUS[r.status] || { label: r.status };
		var lines = (r.lines || []).map(function (l) {
			return '<tr><td>' + escapeHtml(l.name) + '<br><span class="mk-wh-mgmt-muted">' + escapeHtml(l.sku) + '</span></td>' +
				'<td>' + escapeHtml(l.lot) + '<br>' + escapeHtml(l.expiry) + '</td><td class="mk-wh-proto-td-right">' + l.qty + '</td>' +
				'<td>' + (l.qcResult ? escapeHtml(l.qcResult) : '—') + '</td></tr>';
		}).join('');
	var timeline = (r.timeline || []).map(function (t) {
		var roleLabel = t.role === 'qc' ? 'QC' : 'Quản lý kho';
		return '<div class="mk-wh-proto-timeline-item">' +
			'<strong>' + escapeHtml(t.action || '—') + '</strong>' +
			'<span class="mk-wh-proto-tag mk-wh-proto-tag--blue">' + escapeHtml(roleLabel) + '</span>' +
			'<div class="mk-wh-proto-muted">' + escapeHtml((t.by || '—') + ' · ' + fmtDateTime(t.at)) + '</div>' +
			(t.note ? '<div class="mk-wh-proto-quote">"' + escapeHtml(t.note) + '"</div>' : '') +
		'</div>';
	}).join('');
	meta.innerHTML = 'NCC: ' + escapeHtml(r.supplier) + ' · PO: ' + escapeHtml(r.poRef);
	body.innerHTML =
		'<div style="margin-bottom:10px;"><span class="' + (st.cls || 'mk-wh-proto-pill') + '">' + escapeHtml(st.label) + '</span></div>' +
		'<div class="mk-wh-proto-dialog-grid">' +
			'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Chi tiết hàng hóa</div>' +
				'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô / HSD</th><th>SL</th><th>QC</th></tr></thead><tbody>' +
				lines +
				'</tbody></table>' +
			'</div>' +
			'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
				(timeline ? ('<div class="mk-wh-proto-timeline">' + timeline + '</div>') : '<p class="mk-wh-mgmt-empty">Chưa có timeline.</p>') +
			'</div>' +
		'</div>';
	dialog.classList.add('is-open');
	dialog.setAttribute('aria-hidden', 'false');
	}

	function openIssueModal(id) {
		var w = getWarehouse();
		if (!w) return;
		var d = S.ensureData(w.id);
		var issue = (d.issues || []).find(function (x) { return x.id === id; });
		if (!issue) return;
	var dialog = qs('#mkWhProtoDialog');
	var body = qs('#mkWhProtoDialogBody');
	var meta = qs('#mkWhProtoDialogMeta');
	var title = qs('#mkWhProtoDialogTitle');
	if (!dialog || !body || !meta || !title) return;
	if (title) title.textContent = 'Phiếu xuất ' + issue.id;
		var lines = (issue.lines || []).map(function (l) {
			return '<tr><td>' + escapeHtml(l.name) + '</td><td>' + escapeHtml(l.lot) + '</td><td class="mk-wh-proto-td-right">' + l.qty + '</td></tr>';
		}).join('');
	var timeline = (issue.timeline || []).map(function (t) {
		var roleLabel = t.role === 'qc' ? 'QC' : 'Quản lý kho';
		return '<div class="mk-wh-proto-timeline-item">' +
			'<strong>' + escapeHtml(t.action || '—') + '</strong>' +
			'<span class="mk-wh-proto-tag mk-wh-proto-tag--blue">' + escapeHtml(roleLabel) + '</span>' +
			'<div class="mk-wh-proto-muted">' + escapeHtml((t.by || '—') + ' · ' + fmtDateTime(t.at)) + '</div>' +
			(t.note ? '<div class="mk-wh-proto-quote">"' + escapeHtml(t.note) + '"</div>' : '') +
		'</div>';
	}).join('');
	meta.innerHTML = 'KH: ' + escapeHtml(issue.customer) + ' · SO: ' + escapeHtml(issue.soRef || '—');
	body.innerHTML =
		'<div class="mk-wh-proto-dialog-section-title">Chi tiết xuất hàng</div>' +
		'<table class="mk-wh-proto-dialog-table"><thead><tr><th>Hàng hóa</th><th>Lô</th><th>SL xuất</th></tr></thead><tbody>' +
		lines +
		'</tbody></table>' +
		'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
		(timeline ? ('<div class="mk-wh-proto-timeline">' + timeline + '</div>') : '<p class="mk-wh-mgmt-empty">Chưa có timeline.</p>');
	dialog.classList.add('is-open');
	dialog.setAttribute('aria-hidden', 'false');
	}

	function closeDetailModal() {
	var modal = qs('#mkWhDetailModal');
	if (modal) modal.classList.add('hide');
	var dialog = qs('#mkWhProtoDialog');
	if (dialog) {
		dialog.classList.remove('is-open');
		dialog.setAttribute('aria-hidden', 'true');
	}
	}

	function patchReceipt(id, fn) {
		var w = getWarehouse();
		if (!w) return;
		var d = S.ensureData(w.id);
		var receipts = (d.receipts || []).map(function (r) {
			return r.id === id ? fn(Object.assign({}, r, { lines: (r.lines || []).slice(), timeline: (r.timeline || []).slice() })) : r;
		});
		S.warehouseDataActions.setReceipts(w.id, receipts);
	}

	function patchIssue(id, fn) {
		var w = getWarehouse();
		if (!w) return;
		var d = S.ensureData(w.id);
		var issues = (d.issues || []).map(function (i) {
			return i.id === id ? fn(Object.assign({}, i, { lines: (i.lines || []).slice(), timeline: (i.timeline || []).slice() })) : i;
		});
		S.warehouseDataActions.setIssues(w.id, issues);
	}

	function addTimeline(list, action, role) {
		var me = ROLES[role] || ROLES.manager;
		list.push({ at: S.nowISO(), by: me.user, role: role, action: action });
	}

	function bindReceiptActions() {
		var root = qs('#mkWhMgmtRoot');
		if (!root) return;
		root.addEventListener('click', function (e) {
			var action = e.target.getAttribute && e.target.getAttribute('data-mk-rc-action');
			var id = e.target.getAttribute && e.target.getAttribute('data-mk-rc-id');
			if (!action || !id) return;
			e.preventDefault();
			var role = getRole();
			if (action === 'send_qc') {
				patchReceipt(id, function (r) {
					r.status = 'pending_qc';
					addTimeline(r.timeline, 'Gửi QC kiểm tra', role);
					return r;
				});
			} else if (action === 'qc_pass') {
				patchReceipt(id, function (r) {
					r.status = 'qc_passed';
					r.lines = r.lines.map(function (l) {
						return Object.assign({}, l, { qcResult: 'pass', passedQty: l.qty });
					});
					addTimeline(r.timeline, 'QC đạt', role);
					return r;
				});
			} else if (action === 'qc_fail') {
				patchReceipt(id, function (r) {
					r.status = 'qc_failed';
					r.lines = r.lines.map(function (l) {
						return Object.assign({}, l, { qcResult: 'fail', passedQty: 0 });
					});
					addTimeline(r.timeline, 'QC không đạt', role);
					return r;
				});
			} else if (action === 'approve') {
				patchReceipt(id, function (r) {
					r.status = 'approved';
					addTimeline(r.timeline, 'Duyệt phiếu nhập', role);
					return r;
				});
			} else if (action === 'store') {
				var w = getWarehouse();
				patchReceipt(id, function (r) {
					var d = S.ensureData(w.id);
					var stock = (d.stock || []).slice();
					r.lines.forEach(function (l) {
						var passed = l.passedQty != null ? l.passedQty : l.qty;
						if (passed <= 0) return;
						var idx = stock.findIndex(function (s) { return s.sku === l.sku && s.lot === l.lot; });
						if (idx >= 0) stock[idx] = Object.assign({}, stock[idx], { qty: stock[idx].qty + passed });
						else stock.push({ sku: l.sku, name: l.name, lot: l.lot, expiry: l.expiry, qty: passed, location: 'A-NEW' });
					});
					S.warehouseDataActions.setStock(w.id, stock);
					r.status = 'stored';
					addTimeline(r.timeline, 'Đã nhập kho thực tế', role);
					return r;
				});
			}
			closeDetailModal();
		});
	}

	function bindIssueActions() {
		var root = qs('#mkWhMgmtRoot');
		if (!root) return;
		root.addEventListener('click', function (e) {
			var action = e.target.getAttribute && e.target.getAttribute('data-mk-is-action');
			var id = e.target.getAttribute && e.target.getAttribute('data-mk-is-id');
			if (!action || !id) return;
			e.preventDefault();
			var role = getRole();
			if (action === 'submit') {
				patchIssue(id, function (i) {
					i.status = 'pending_approval';
					addTimeline(i.timeline, 'Gửi duyệt phiếu xuất', role);
					return i;
				});
			} else if (action === 'approve') {
				patchIssue(id, function (i) {
					i.status = 'approved';
					addTimeline(i.timeline, 'Duyệt phiếu xuất', role);
					return i;
				});
			} else if (action === 'reject') {
				patchIssue(id, function (i) {
					i.status = 'rejected';
					addTimeline(i.timeline, 'Từ chối phiếu', role);
					return i;
				});
			} else if (action === 'ship') {
				var w = getWarehouse();
				patchIssue(id, function (i) {
					var d = S.ensureData(w.id);
					var stock = (d.stock || []).slice();
					i.lines.forEach(function (l) {
						var idx = stock.findIndex(function (s) { return s.sku === l.sku && s.lot === l.lot; });
						if (idx >= 0) stock[idx] = Object.assign({}, stock[idx], { qty: Math.max(0, stock[idx].qty - l.qty) });
					});
					S.warehouseDataActions.setStock(w.id, stock);
					i.status = 'shipped';
					addTimeline(i.timeline, 'Soạn hàng & giao thành công', role);
					return i;
				});
			}
			closeDetailModal();
		});
	}

	function qsa(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

	var TRANSFER_STATUS_CLS = {
		pending: 'mk-wh-mgmt-badge--pending',
		approved: 'mk-wh-mgmt-badge--approved',
		in_transit: 'mk-wh-mgmt-badge--transit',
		completed: 'mk-wh-mgmt-badge--done',
		cancelled: 'mk-wh-mgmt-badge--cancel',
	};

	function whName(id) {
		var w = S.getState().warehouses.find(function (x) { return x.id === id; });
		return w ? w.name : id;
	}

	/* ========== TRANSFER PAGE ========== */
	function renderTransfers() {
		var tbody = qs('#mkWhTransferTableBody');
		if (!tbody) return;
		var state = S.getState();
		if (!state.transfers.length) {
			tbody.innerHTML = '<tr><td colspan="9" class="mk-wh-mgmt-empty">Chưa có phiếu chuyển nào.</td></tr>';
			return;
		}
		tbody.innerHTML = state.transfers.map(function (t) {
			var stCls = 'mk-wh-mgmt-badge ' + (TRANSFER_STATUS_CLS[t.status] || 'mk-wh-mgmt-badge--inactive');
			var stLabel = S.TRANSFER_STATUS_LABEL[t.status] || t.status;
			var actions = '';
			if (t.status === 'pending') {
				actions =
					'<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline mk-wh-mgmt-btn--sm" data-mk-trf-action="approve" data-mk-trf-id="' + escapeHtml(t.id) + '">Duyệt</button> ' +
					'<button type="button" class="mk-wh-mgmt-link" data-mk-trf-action="cancel" data-mk-trf-id="' + escapeHtml(t.id) + '">Hủy</button>';
			} else if (t.status === 'approved') {
				actions = '<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline mk-wh-mgmt-btn--sm" data-mk-trf-action="ship" data-mk-trf-id="' + escapeHtml(t.id) + '">Xuất kho nguồn</button>';
			} else if (t.status === 'in_transit') {
				actions = '<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--primary mk-wh-mgmt-btn--sm" data-mk-trf-action="complete" data-mk-trf-id="' + escapeHtml(t.id) + '">Hoàn tất</button>';
			}
			return '<tr>' +
				'<td><span class="mk-wh-mgmt-chip">' + escapeHtml(t.id) + '</span></td>' +
				'<td>' + escapeHtml(whName(t.fromWarehouseId)) + '</td>' +
				'<td class="mk-wh-mgmt-arrow" aria-hidden="true">→</td>' +
				'<td>' + escapeHtml(whName(t.toWarehouseId)) + '</td>' +
				'<td><div><strong>' + escapeHtml(t.name) + '</strong></div><div class="mk-wh-mgmt-muted">' + escapeHtml(t.sku) + ' · ' + escapeHtml(t.lot) + '</div></td>' +
				'<td class="mk-wh-mgmt-td-right"><strong>' + t.qty + '</strong></td>' +
				'<td class="mk-wh-mgmt-muted">' + escapeHtml(t.reason || '—') + '</td>' +
				'<td><span class="' + stCls + '">' + escapeHtml(stLabel) + '</span></td>' +
				'<td class="mk-wh-mgmt-td-right">' + actions + '</td></tr>';
		}).join('');
	}

	function bindTransferEvents() {
		var root = qs('#mkWhMgmtRoot');
		var modal = qs('#mkWhTransferFormModal');
		var form = qs('#mkWhTransferForm');
		var createBtn = qs('#mkWhTransferCreateBtn');
		var fromSel = qs('#mkWhTrfFrom');
		var toSel = qs('#mkWhTrfTo');
		var lotSel = qs('#mkWhTrfLot');

		if (!root) return;

		function fillWarehouseSelects() {
			var wh = S.getState().warehouses;
			if (fromSel) {
				fromSel.innerHTML = '<option value="">Chọn kho nguồn</option>' +
					wh.map(function (w) { return '<option value="' + escapeHtml(w.id) + '">' + escText(w.name) + '</option>'; }).join('');
			}
			if (toSel) {
				toSel.innerHTML = '<option value="">Chọn kho đích</option>' +
					wh.map(function (w) { return '<option value="' + escapeHtml(w.id) + '">' + escText(w.name) + '</option>'; }).join('');
			}
		}

		function fillLotSelect(fromId) {
			if (!lotSel) return;
			if (!fromId) {
				lotSel.disabled = true;
				lotSel.innerHTML = '<option value="">Chọn kho nguồn trước</option>';
				return;
			}
			var stock = (S.ensureData(fromId).stock || []).filter(function (s) { return s.qty > 0; });
			lotSel.disabled = false;
			lotSel.innerHTML = '<option value="">Chọn lô từ kho nguồn</option>' +
				stock.map(function (s) {
					var key = s.sku + '|' + s.lot;
					return '<option value="' + escapeHtml(key) + '">' + escapeHtml(s.name) + ' · ' + escapeHtml(s.lot) + ' · còn ' + s.qty + '</option>';
				}).join('');
		}

		function openModal() {
			fillWarehouseSelects();
			fillLotSelect('');
			if (form) form.reset();
			if (modal) modal.classList.remove('hide');
		}

		function closeModal() {
			if (modal) modal.classList.add('hide');
		}

		if (createBtn) createBtn.addEventListener('click', openModal);

		if (fromSel) {
			fromSel.addEventListener('change', function () {
				fillLotSelect(fromSel.value);
				if (toSel && toSel.value === fromSel.value) toSel.value = '';
			});
		}

		root.addEventListener('click', function (e) {
			if (e.target.getAttribute && e.target.getAttribute('data-mk-wh-trf-close') === '1') {
				closeModal();
				return;
			}
			var action = e.target.getAttribute && e.target.getAttribute('data-mk-trf-action');
			var id = e.target.getAttribute && e.target.getAttribute('data-mk-trf-id');
			if (!action || !id) return;
			e.preventDefault();
			if (action === 'approve') S.transferActions.approve(id, 'QL Tuấn');
			else if (action === 'cancel') S.transferActions.cancel(id);
			else if (action === 'ship') S.transferActions.ship(id);
			else if (action === 'complete') S.transferActions.complete(id);
			renderTransfers();
		});

		if (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				var from = fromSel ? fromSel.value : '';
				var to = toSel ? toSel.value : '';
				var lotKey = lotSel ? lotSel.value : '';
				var qty = parseInt(qs('#mkWhTrfQty').value, 10) || 0;
				var reason = qs('#mkWhTrfReason') ? qs('#mkWhTrfReason').value.trim() : '';
				if (!from || !to || from === to || !lotKey || qty <= 0) return;
				var parts = lotKey.split('|');
				var sku = parts[0];
				var lot = parts[1];
				var stock = S.ensureData(from).stock || [];
				var s = stock.find(function (x) { return x.sku === sku && x.lot === lot; });
				if (!s || qty > s.qty) return;
				S.transferActions.create({
					fromWarehouseId: from,
					toWarehouseId: to,
					sku: s.sku,
					name: s.name,
					lot: s.lot,
					qty: qty,
					reason: reason || '—',
					requestedBy: 'QL Tuấn',
				});
				closeModal();
				renderTransfers();
			});
		}
	}

	function init() {
		S.hydrate();
		var root = qs('#mkWhMgmtRoot');
		if (!root) return;
		var view = root.getAttribute('data-mk-wh-view') || '';

		root.addEventListener('click', function (e) {
			if (e.target.getAttribute && e.target.getAttribute('data-mk-wh-detail-close') === '1') {
				closeDetailModal();
			}
		});

		if (view === 'WhList') {
			renderList();
			bindListEvents();
			S.subscribe(renderList);
		} else if (view === 'WhDashboard') {
			renderDashboard();
			S.subscribe(renderDashboard);
		} else if (view === 'WhDetail') {
			bindDetailEvents();
			bindReceiptActions();
			bindIssueActions();
		} else if (view === 'WhTransfer') {
			renderTransfers();
			bindTransferEvents();
			S.subscribe(renderTransfers);
		}
	}

	$(function () {
		init();
	});
})(jQuery);
