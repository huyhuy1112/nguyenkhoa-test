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

	function escText(s) {
		return escapeHtml(decodeEntities(s));
	}

	function formatSkuLabel(sku) {
		var s = decodeEntities(sku || '').trim();
		if (!s || /^PS-\d+$/i.test(s)) {
			return '—';
		}
		return s;
	}

	function showError(msg) {
		var text = String(msg || 'Đã xảy ra lỗi');
		if (typeof window !== 'undefined' && window.app && app.helper && app.helper.showErrorNotification) {
			app.helper.showErrorNotification({ message: text });
			return;
		}
		window.alert(text);
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

	function fmtDateTime(iso) {
		if (!iso) return '—';
		try {
			var d = new Date(iso);
			if (isNaN(d.getTime())) {
				d = new Date(String(iso).replace(' ', 'T'));
			}
			if (isNaN(d.getTime())) {
				return '—';
			}
			return d.toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' });
		} catch (e) {
			return '—';
		}
	}

	function readQcNoteFromDialog(actionEl) {
		var root = (actionEl && actionEl.closest && actionEl.closest('#mkWhProtoDialog')) || qs('#mkWhProtoDialog');
		if (!root) return '';
		var cached = root.getAttribute('data-mk-qc-note-cache');
		if (cached) return String(cached).trim();
		var noteEl = root.querySelector('[data-mk-qc-note="1"]');
		return noteEl ? String(noteEl.value || '').trim() : '';
	}

	function bindQcNoteInput(root) {
		if (!root) return;
		var noteEl = root.querySelector('[data-mk-qc-note="1"]');
		if (!noteEl || noteEl.getAttribute('data-mk-qc-bound') === '1') return;
		noteEl.setAttribute('data-mk-qc-bound', '1');
		var sync = function () {
			root.setAttribute('data-mk-qc-note-cache', String(noteEl.value || ''));
		};
		noteEl.addEventListener('input', sync);
		noteEl.addEventListener('change', sync);
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

	function getDestinationWarehouseOptions() {
		var currentId = getWhId();
		var opts = [{ value: '', label: '— Chọn kho đích —' }];
		(S.getState().warehouses || []).forEach(function (w) {
			if (!w || !w.id || w.id === currentId) return;
			if (w.status && String(w.status).toLowerCase() !== 'active') return;
			var label = decodeEntities(w.name || w.id);
			if (w.address) label += ' — ' + decodeEntities(w.address);
			opts.push({ value: String(w.id), label: label });
		});
		return opts;
	}

	function resolveWarehouseLabel(whId) {
		var w = (S.getState().warehouses || []).find(function (x) { return x && String(x.id) === String(whId); });
		return w ? decodeEntities(w.name || w.id) : String(whId || '');
	}

	/** Normalize date strings for <input type="date"> (YYYY-MM-DD). */
	function toDateInputValue(v) {
		var s = String(v || '').trim();
		if (!s || s === '—' || s === '0000-00-00') return '';
		var iso = s.match(/^(\d{4}-\d{2}-\d{2})/);
		if (iso) return iso[1];
		var dmy = s.match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/);
		if (dmy) {
			var dd = ('0' + dmy[1]).slice(-2);
			var mm = ('0' + dmy[2]).slice(-2);
			return dmy[3] + '-' + mm + '-' + dd;
		}
		var t = Date.parse(s);
		if (!isNaN(t)) {
			var d = new Date(t);
			var y = d.getFullYear();
			var m = ('0' + (d.getMonth() + 1)).slice(-2);
			var day = ('0' + d.getDate()).slice(-2);
			return y + '-' + m + '-' + day;
		}
		return '';
	}

	// UI roles: qc | manager (stock/keeper legacy → manager ops)
	function getRole() {
		var sel = qs('#mkWhDetailRole');
		var val = sel ? sel.value : 'manager';
		if (val === 'stock' || val === 'keeper') return 'manager';
		return val || 'manager';
	}

	function setRoleUI(roleKey) {
		var sel = qs('#mkWhDetailRole');
		if (!sel) return;
		var uiVal = (roleKey === 'keeper' || roleKey === 'stock') ? 'manager' : roleKey;
		if (uiVal !== 'qc' && uiVal !== 'manager') uiVal = 'manager';
		if (sel.value !== uiVal) sel.value = uiVal;
	}

	/** Ops formerly done by Thủ kho — now owned by Quản lý kho. */
	function isWarehouseOps(role) {
		return role === 'manager' || role === 'keeper' || role === 'stock';
	}

	function roleActorName(role) {
		if (role === 'qc') return 'QC Minh';
		return 'QL Tuấn';
	}

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
		waiting_print: { label: 'Chờ in phiếu', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-wait' },
		picking: { label: 'Đang soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-pick' },
		packed: { label: 'Đã soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-packed' },
		shipped: { label: 'Đã giao', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		rejected: { label: 'Từ chối', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
		// legacy aliases
		pending_approval: { label: 'Chờ in phiếu', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-wait' },
		approved: { label: 'Đã soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-packed' },
	};

	var OUTBOUND_TYPES = {
		internal: {
			label: 'Xuất nội bộ (test)',
			short: 'Xuất nội bộ',
			pillCls: 'mk-wh-proto-pill--out-internal',
			customerLabel: 'Bộ phận / mục đích',
			soLabel: 'Mã tham chiếu',
			soPlaceholder: 'IT-TEST-...',
		},
		transfer: {
			label: 'Xuất chuyển kho',
			short: 'Chuyển kho',
			pillCls: 'mk-wh-proto-pill--out-transfer',
			customerLabel: 'Kho đích',
			soLabel: 'Mã chuyển kho',
			soPlaceholder: 'TRF-...',
		},
		scrap: {
			label: 'Xuất huỷ',
			short: 'Xuất huỷ',
			pillCls: 'mk-wh-proto-pill--out-scrap',
			customerLabel: 'Lý do huỷ',
			soLabel: 'Mã phiếu huỷ',
			soPlaceholder: 'SCR-...',
		},
		sale: {
			label: 'Xuất bán (từ invoice)',
			short: 'Xuất bán',
			pillCls: 'mk-wh-proto-pill--out-sale',
			customerLabel: 'Khách hàng',
			soLabel: 'Mã invoice / SO',
			soPlaceholder: 'INV-2026-...',
		},
	};

	var OUTBOUND_TYPE_PICKER = ['internal', 'transfer', 'scrap'];

	function getOutboundTypeMeta(type) {
		return OUTBOUND_TYPES[type] || OUTBOUND_TYPES.internal;
	}

	function outboundTypePill(type) {
		var meta = getOutboundTypeMeta(type);
		var cls = meta.pillCls || 'mk-wh-proto-pill--out-internal';
		return '<span class="mk-wh-proto-pill ' + escapeHtml(cls) + '">' + escapeHtml(meta.short) + '</span>';
	}

	function issueStatusPill(status) {
		var st = ISSUE_STATUS[status] || ISSUE_STATUS.draft;
		return '<span class="' + escapeHtml(st.cls) + '">' + escapeHtml(st.label) + '</span>';
	}

	function findStockLot(whId, sku, lot) {
		var d = S.ensureData(whId);
		var wantSku = decodeEntities(sku || '').trim();
		var wantLot = decodeEntities(lot || '').replace(/^\s*lô\s+/i, '').trim();
		return (d.stock || []).find(function (s) {
			var sSku = decodeEntities(s.sku || '').trim();
			var sLot = decodeEntities(s.lot || '').replace(/^\s*lô\s+/i, '').trim();
			if (wantLot && sLot !== wantLot) return false;
			if (wantSku && sSku && sSku !== wantSku) return false;
			return !!(wantLot || (wantSku && sSku === wantSku));
		});
	}

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
		if (title) title.textContent = decodeEntities(w.name);
		if (desc) desc.textContent = decodeEntities(w.code) + ' · ' + decodeEntities(w.address || '—') + ' · QL: ' + decodeEntities(w.manager || '—');
		return true;
	}

	function updateRoleBanner() {
		var role = getRole();
		var active = qs('.mk-wh-proto-tab.is-active');
		var tabKey = active ? active.getAttribute('data-tab') : 'inbound';
		var btn = qs('#mkWhProtoCreateBtn');
		var canCreate = isWarehouseOps(role) && (tabKey === 'inbound' || tabKey === 'outbound');
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
		var pendingApprove = (d.issues || []).filter(function (i) {
			return i.status === 'waiting_print' || i.status === 'pending_approval' || i.status === 'picking' || i.status === 'packed' || i.status === 'approved';
		}).length;
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
				'<td>' + escapeHtml(decodeEntities(r.supplier)) + '</td>' +
				'<td>' + escapeHtml(decodeEntities(r.poRef)) + '</td>' +
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
				'<td>' + escText(r.supplier) + '</td>' +
				'<td>' + escText(it.name || '—') + (formatSkuLabel(it.sku) !== '—' ? ' <span class="mk-wh-proto-muted">(' + escText(formatSkuLabel(it.sku)) + ')</span>' : '') + '</td>' +
				'<td>' + escText(it.lot || '—') + '</td>' +
				'<td>' +
					(it.mfg ? '<span class="mk-wh-proto-muted">NSX: ' + escText(it.mfg) + '</span><br/>' : '') +
					'HSD: ' + escText(it.expiry || '—') +
				'</td>' +
				'<td>' + escText(it.qty || '—') + '</td>' +
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
		if (summary) {
			if (!rows.length && inStock.length) {
				summary.textContent = 'Không có mặt hàng phù hợp bộ lọc (đang có ' + inStock.length + ' dòng tồn).';
			} else if (!inStock.length) {
				summary.textContent = 'Chưa có tồn kho.';
			} else {
				summary.textContent = 'Hiển thị ' + rows.length + ' / ' + inStock.length + ' mặt hàng';
			}
		}
		tbody.innerHTML = rows.map(function (s) {
			var days = daysUntil(s.expiry);
			var expLabel = days < 0 ? 'Quá hạn' : 'Còn ' + days + ' ngày';
			var hsdCls = 'mk-wh-proto-hsd' + (days < 0 ? ' mk-wh-proto-hsd--expired' : (days < 90 ? ' mk-wh-proto-hsd--soon' : ''));
			var qtyCls = (Number(s.qty) || 0) < 50 ? ' mk-wh-proto-qty--low' : '';
			return '<tr>' +
				'<td><strong>' + escText(formatSkuLabel(s.sku)) + '</strong></td>' +
				'<td>' + escText(s.name) + '</td>' +
				'<td>' + escText(s.lot) + '</td>' +
				'<td class="' + hsdCls + '">' + escText(s.expiry || '—') + ' <span class="mk-wh-proto-muted">(' + escText(expLabel) + ')</span></td>' +
				'<td class="mk-wh-proto-td-right">' + escText(fmtPrice(s.price)) + '</td>' +
				'<td class="mk-wh-proto-td-right">' + escText(s.location || '—') + '</td>' +
				'<td class="mk-wh-proto-td-right' + qtyCls + '"><strong>' + escText(s.qty) + '</strong></td>' +
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
				'<td><strong>' + escText(i.id) + '</strong></td>' +
				'<td>' + outboundTypePill(i.outboundType || 'internal') + '</td>' +
				'<td>' + escText(i.customer) + '</td>' +
				'<td>' + escText(i.soRef || '—') + '</td>' +
				'<td>' + escapeHtml(fmtDateTime(i.createdAt)) + '</td>' +
				'<td>' + issueStatusPill(i.status) + '</td>' +
				'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-action="outbound-detail" data-id="' + escText(i.id) + '">Chi tiết</button></td>' +
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

	function refreshWarehouseUi() {
		renderAll();
		if (typeof window.requestAnimationFrame === 'function') {
			window.requestAnimationFrame(renderAll);
		} else {
			setTimeout(renderAll, 0);
		}
	}

	function reopenIssueDialog(whId, issueId, res) {
		var d = (res && res.data) ? res.data : S.ensureData(whId);
		var issue = (d.issues || []).find(function (x) { return x.id === issueId; });
		if (!issue) return;
		var dlg = issueDialog(issue);
		openDialog(dlg.title, dlg.meta, dlg.body);
	}

	function reopenReceiptDialog(whId, receiptId, res) {
		var d = (res && res.data) ? res.data : S.ensureData(whId);
		var receipt = (d.receipts || []).find(function (x) { return x.id === receiptId; });
		if (!receipt) return;
		var dlg = receiptDialog(receipt);
		openDialog(dlg.title, dlg.meta, dlg.body);
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
		dialog.removeAttribute('data-mk-qc-note-cache');
		bindQcNoteInput(dialog);
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
		return '<span class="mk-wh-proto-tag mk-wh-proto-tag--green">Quản lý kho</span>';
	}

	function getReceiptQcInfo(r) {
		var qc = r.qc || {};
		var result = String(qc.result || '');
		var note = String(qc.note || '').trim();
		var by = String(qc.by || '').trim();
		var at = String(qc.at || '').trim();
		(r.timeline || []).forEach(function (ev) {
			if (!ev || ev.role !== 'qc') {
				return;
			}
			if (!note && ev.note) {
				note = String(ev.note || '').trim();
			}
			if (!by && ev.by) {
				by = String(ev.by || '').trim();
			}
			if (!at && ev.at) {
				at = String(ev.at || '').trim();
			}
			if (!result) {
				var action = String(ev.action || '').toLowerCase();
				if (action.indexOf('không đạt') >= 0 || action.indexOf('khong dat') >= 0) {
					result = 'fail';
				} else if (action.indexOf('đạt') >= 0 || action.indexOf('dat') >= 0) {
					result = 'pass';
				}
			}
		});
		if (!result) {
			if (r.status === 'qc_passed') result = 'pass';
			if (r.status === 'qc_failed') result = 'fail';
		}
		return { result: result, note: note, by: by, at: at };
	}

	function renderQcResultPanel(qcInfo) {
		if (!qcInfo || !qcInfo.result) {
			return '';
		}
		var label = qcInfo.result === 'pass' ? 'Đạt' : 'Không đạt';
		var cls = qcInfo.result === 'pass' ? 'mk-wh-proto-pill--ok' : 'mk-wh-proto-pill--warn';
		return '<div class="mk-wh-proto-qc-result" style="margin-bottom:14px;padding:12px 14px;border:1px solid rgba(15,23,42,0.08);border-radius:12px;background:#f8fafc;">' +
			'<div class="mk-wh-proto-dialog-section-title" style="margin-bottom:8px;">Kết quả QC</div>' +
			'<div style="margin-bottom:8px;"><span class="mk-wh-proto-pill ' + escapeHtml(cls) + '">' + escapeHtml(label) + '</span></div>' +
			(qcInfo.note
				? ('<div class="mk-wh-proto-muted" style="margin-bottom:4px;">Ghi nhận của QC:</div>' +
					'<div class="mk-wh-proto-quote">"' + escText(qcInfo.note) + '"</div>')
				: '<div class="mk-wh-proto-muted">QC chưa ghi nhận chi tiết.</div>') +
			((qcInfo.by || qcInfo.at)
				? ('<div class="mk-wh-proto-muted" style="margin-top:8px;">' +
					escText(qcInfo.by || 'QC') +
					(qcInfo.at && fmtDateTime(qcInfo.at) !== '—' ? ' · ' + escText(fmtDateTime(qcInfo.at)) : '') +
					'</div>')
				: '') +
			'</div>';
	}

	function receiptDialog(r) {
		var st = RECEIPT_STATUS[r.status] || { label: r.status, cls: 'mk-wh-proto-pill' };
		var qcInfo = getReceiptQcInfo(r);
		var linesHtml = (r.lines || []).map(function (l) {
			var qcPill = l.qcResult === 'pass'
				? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đạt</span>'
				: l.qcResult === 'fail'
					? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Không đạt</span>'
					: (r.status === 'pending_qc'
						? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Chờ QC</span>'
						: (r.status === 'qc_passed'
							? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đạt</span>'
							: (r.status === 'qc_failed'
								? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Không đạt</span>'
								: '—')));
			return '<tr><td><strong>' + escText(l.name || '') + '</strong>' + (formatSkuLabel(l.sku) !== '—' ? '<div class="mk-wh-proto-muted">' + escText(formatSkuLabel(l.sku)) + '</div>' : '') + '</td>' +
				'<td>' + escText(l.lot || '') + '<br/><span class="mk-wh-proto-muted">HSD: ' + escText(l.expiry || '—') + '</span></td>' +
				'<td>' + escText(l.qty) + '</td><td>' + escText(l.location || '—') + '</td><td>' + qcPill + '</td></tr>';
		}).join('');

		var timelineHtml =
			'<div class="mk-wh-proto-timeline">' +
			((r.timeline || []).map(function (ev) {
				return '<div class="mk-wh-proto-timeline-item">' +
					'<strong>' + escText(ev.action || '—') + '</strong>' +
					roleBadge(ev.role) +
					'<div class="mk-wh-proto-muted">' + escText((ev.by || '—') + ' · ' + fmtDateTime(ev.at)) + '</div>' +
					(ev.note ? '<div class="mk-wh-proto-quote">"' + escText(ev.note) + '"</div>' : '') +
				'</div>';
			}).join('')) +
			'</div>';

		var role = getRole();
		var actions = '';
		if (r.status === 'draft' && isWarehouseOps(role)) {
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
		if (r.status === 'approved' && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="store" data-id="' + escapeHtml(r.id) + '">Nhập kho</button>' +
				'</div>';
		}

		return {
			title: 'Phiếu nhập ' + r.id,
			meta: 'NCC: ' + escText(r.supplier) + ' · PO: ' + escText(r.poRef),
			body:
				'<div style="margin-bottom:10px;"><span class="' + escapeHtml(st.cls || 'mk-wh-proto-pill') + '">' + escapeHtml(st.label) + '</span></div>' +
				renderQcResultPanel(qcInfo) +
				'<div class="mk-wh-proto-dialog-grid">' +
					'<div>' +
						'<div class="mk-wh-proto-dialog-section-title">Chi tiết hàng hóa</div>' +
						'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô / HSD</th><th>SL</th><th>Vị trí</th><th>QC</th></tr></thead><tbody>' +
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
			return '<tr><td><strong>' + escText(l.name) + '</strong>' + (formatSkuLabel(l.sku) !== '—' ? '<div class="mk-wh-proto-muted">' + escText(formatSkuLabel(l.sku)) + '</div>' : '') + '</td>' +
				'<td>' + escText(l.lot) + '</td><td>' + escText(l.qty) + '</td></tr>';
		}).join('');
		var timelineHtml =
			'<div class="mk-wh-proto-timeline">' +
			((issue.timeline || []).map(function (ev) {
				return '<div class="mk-wh-proto-timeline-item">' +
					'<strong>' + escText(ev.action || '—') + '</strong>' +
					roleBadge(ev.role) +
					'<div class="mk-wh-proto-muted">' + escText((ev.by || '—') + ' · ' + fmtDateTime(ev.at)) + '</div>' +
					(ev.note ? '<div class="mk-wh-proto-quote">"' + escText(ev.note) + '"</div>' : '') +
				'</div>';
			}).join('')) +
			'</div>';

		var role = getRole();
		var actions = '';
		if (issue.status === 'draft' && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-submit" data-id="' + escapeHtml(issue.id) + '">Chờ in phiếu</button>' +
				'</div>';
		}
		if ((issue.status === 'waiting_print' || issue.status === 'pending_approval') && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-start-pick" data-id="' + escapeHtml(issue.id) + '">Bắt đầu soạn</button>' +
				'</div>';
		}
		if (issue.status === 'picking' && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-finish-pick" data-id="' + escapeHtml(issue.id) + '">Hoàn tất soạn hàng</button>' +
				'</div>';
		}
		if ((issue.status === 'packed' || issue.status === 'approved') && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-ship" data-id="' + escapeHtml(issue.id) + '">Xác nhận đã giao</button>' +
				'</div>';
		}

		return {
			title: 'Phiếu xuất ' + issue.id,
			meta: outboundTypePill(issue.outboundType || 'internal') + ' · ' +
				escText(getOutboundTypeMeta(issue.outboundType).customerLabel) + ': ' + escText(issue.customer) +
				' · ' + escText(getOutboundTypeMeta(issue.outboundType).soLabel) + ': ' + escText(issue.soRef || '—'),
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
		list.push({ at: S.nowISO(), by: roleActorName(role), role: role === 'qc' ? 'qc' : 'manager', action: action, note: note || undefined });
	}

	function formatLocationNote(lines) {
		var locations = [];
		(lines || []).forEach(function (l) {
			var loc = (l.location || '').trim();
			if (loc && locations.indexOf(loc) < 0) {
				locations.push(loc);
			}
		});
		if (!locations.length) {
			return undefined;
		}
		return 'Vị trí: ' + locations.join(', ');
	}

	function addStockFromReceiptLines(whId, lines) {
		var d = S.ensureData(whId);
		var stock = (d.stock || []).slice();
		(lines || []).forEach(function (l) {
			var passed = l.passedQty != null ? l.passedQty : l.qty;
			if (!passed || passed <= 0) return;
			var idx = stock.findIndex(function (s) { return s.sku === l.sku && s.lot === l.lot; });
			if (idx >= 0) {
				var patch = {
					qty: (Number(stock[idx].qty) || 0) + passed,
					expiry: stock[idx].expiry || l.expiry,
					mfg: stock[idx].mfg || l.mfg || '',
					name: stock[idx].name || l.name,
				};
				if ((l.location || '').trim()) {
					patch.location = l.location.trim();
				}
				stock[idx] = Object.assign({}, stock[idx], patch);
			} else {
				stock.push({
					sku: l.sku,
					name: l.name,
					lot: l.lot,
					mfg: l.mfg || '',
					expiry: l.expiry || '—',
					qty: passed,
					location: (l.location || '').trim() || '—',
					price: l.price || 0,
				});
			}
		});
		S.warehouseDataActions.setStock(whId, stock);
	}

	function deductStockFromIssueLines(whId, lines) {
		var d = S.ensureData(whId);
		var stock = (d.stock || []).slice();
		(lines || []).forEach(function (l) {
			var qtyLeft = Number(l.qty) || 0;
			if (qtyLeft <= 0) return;
			var lot = String(l.lot || '');
			var sku = String(l.sku || '');
			var name = String(l.name || '');
			var candidates = stock.map(function (s, idx) { return { s: s, idx: idx }; }).filter(function (row) {
				if ((Number(row.s.qty) || 0) <= 0) return false;
				if (sku && lot) return row.s.sku === sku && row.s.lot === lot;
				if (sku) return row.s.sku === sku;
				if (name) return String(row.s.name || '') === name;
				return false;
			});
			candidates.forEach(function (row) {
				if (qtyLeft <= 0) return;
				var avail = Number(row.s.qty) || 0;
				var deduct = Math.min(avail, qtyLeft);
				if (deduct <= 0) return;
				stock[row.idx] = Object.assign({}, stock[row.idx], { qty: avail - deduct });
				qtyLeft -= deduct;
			});
		});
		S.warehouseDataActions.setStock(whId, stock);
	}

	/* ===== Create receipt/issue modal (Prototype UI) ===== */
	function closeModal() {
		var modal = qs('#mkWhProtoModal');
		if (!modal) return;
		qsa('[data-mk-line-product="1"], [name="lotKey"]', modal).forEach(destroyProductSelect2);
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
	}

	function modalSchema(tabKey, outboundType) {
		if (tabKey === 'outbound-type') {
			return {
				tabKey: 'outbound-type',
				title: 'Tạo phiếu xuất kho',
				submitLabel: 'Tiếp theo',
				fields: [
					{ type: 'hint', full: true, text: 'Chọn loại xuất kho. Bước tiếp theo bạn điền chi tiết phiếu xuất.' },
					{
						name: 'outboundType',
						label: 'Xuất kho loại nào?',
						type: 'select',
						required: true,
						full: true,
						options: OUTBOUND_TYPE_PICKER.map(function (key, idx) {
							return { value: key, label: OUTBOUND_TYPES[key].label, selected: idx === 0 };
						}),
					},
				],
			};
		}
		if (tabKey === 'inbound') {
			return {
				tabKey: 'inbound',
				title: 'Tạo phiếu nhập kho',
				submitLabel: 'Tạo phiếu',
				fields: [
					{ name: 'supplier', label: 'Nhà cung cấp', required: true, placeholder: 'VD: CTY Dược Hậu Giang' },
					{ name: 'po', label: 'Mã PO', required: true, placeholder: 'VD: PO-2026-0155' },
					{ type: 'lines', label: 'Danh sách hàng nhập', full: true },
					{
						type: 'checkbox',
						name: 'sendQc',
						label: 'Gửi QC',
						checked: true,
						full: true,
						hint: 'Bỏ chọn nếu hàng không cần QC — nhập thẳng tồn kho',
					},
				],
			};
		}
		var outMeta = getOutboundTypeMeta(outboundType || 'internal');
		var customerField = (outboundType === 'transfer')
			? {
				name: 'customer',
				label: outMeta.customerLabel,
				required: true,
				type: 'select',
				options: getDestinationWarehouseOptions(),
			}
			: { name: 'customer', label: outMeta.customerLabel, required: true, placeholder: '' };
		return {
			tabKey: 'outbound',
			outboundType: outboundType || 'internal',
			title: 'Tạo phiếu xuất — ' + outMeta.short,
			submitLabel: 'Tạo phiếu',
			fields: [
				customerField,
				{ name: 'so', label: outMeta.soLabel, required: false, placeholder: outMeta.soPlaceholder },
				{ type: 'lines', mode: 'outbound', label: 'Danh sách hàng xuất', full: true },
			],
		};
	}

	function renderModalFields(fields) {
		return (fields || []).map(function (f) {
			var full = f.full ? ' mk-wh-proto-field--full' : '';
			var req = f.required ? ' *' : '';
			if (f.type === 'hint') {
				return '<p class="mk-wh-proto-form-hint' + full + '">' + escapeHtml(f.text || '') + '</p>';
			}
			if (f.type === 'lines') {
				var isOutboundLines = f.mode === 'outbound';
				var linesCls = 'mk-wh-proto-lines mk-wh-proto-lines--catalog' + (isOutboundLines ? ' mk-wh-proto-lines--outbound' : '');
				var linesTtl = isOutboundLines
					? 'Chọn lô từ tồn kho — gõ để tìm, SKU tự động điền'
					: 'Chọn sản phẩm từ Products &amp; Services';
				var locTh = isOutboundLines ? '' : '<th class="mk-wh-proto-col-loc">Vị trí</th>';
				return '<div class="mk-wh-proto-field mk-wh-proto-field--full"><label>' + escapeHtml(f.label || 'Danh sách hàng') + '</label>' +
					'<div class="' + linesCls + '" data-mk-lines="1" data-mk-lines-mode="' + (isOutboundLines ? 'outbound' : 'inbound') + '">' +
					'<div class="mk-wh-proto-lines__head"><span class="mk-wh-proto-lines__ttl">' + linesTtl + '</span>' +
					'<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost mk-wh-proto-lines__add" data-mk-lines-add="1">+ Thêm dòng</button></div>' +
					'<div class="mk-wh-proto-lines__tableWrap"><table class="mk-wh-proto-lines__table" role="table"><thead><tr>' +
					'<th class="mk-wh-proto-col-name">Tên hàng *</th><th class="mk-wh-proto-col-sku">SKU</th>' +
					'<th class="mk-wh-proto-col-lot">Lô *</th><th class="mk-wh-proto-col-qty">SL *</th>' +
					'<th class="mk-wh-proto-col-date">NSX</th><th class="mk-wh-proto-col-date">HSD</th>' +
					locTh + '<th style="width:44px;"></th>' +
					'</tr></thead><tbody data-mk-lines-body="1"></tbody></table></div></div></div>';
			}
			if (f.type === 'checkbox') {
				return '<div class="mk-wh-proto-field mk-wh-proto-field--check' + full + '"><label class="mk-wh-proto-check">' +
					'<span class="mk-wh-proto-check__box"><input class="mk-wh-proto-check__input" type="checkbox" name="' + escapeHtml(f.name) + '" value="1"' +
					(f.checked ? ' checked="checked"' : '') + ' />' +
					'<span class="mk-wh-proto-check__visual" aria-hidden="true"><svg class="mk-wh-proto-check__icon" width="12" height="12" viewBox="0 0 12 12" fill="none">' +
					'<path d="M2.2 6.1 4.8 8.7 9.8 3.3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span></span>' +
					'<span class="mk-wh-proto-check__text"><span class="mk-wh-proto-check__label">' + escapeHtml(f.label || '') + '</span>' +
					(f.hint ? '<span class="mk-wh-proto-check__hint">' + escapeHtml(f.hint) + '</span>' : '') +
					'</span></label></div>';
			}
			var input;
			if (f.type === 'select') {
				input = '<select name="' + escapeHtml(f.name) + '"' + (f.required ? ' required' : '') + '>' +
					(f.options || []).map(function (o) {
						return '<option value="' + escapeHtml(o.value) + '"' + (o.selected ? ' selected="selected"' : '') + '>' + escapeHtml(o.label) + '</option>';
					}).join('') + '</select>';
			} else {
				input = '<input type="' + escapeHtml(f.type || 'text') + '" name="' + escapeHtml(f.name) + '"' +
					(f.required ? ' required' : '') + ' placeholder="' + escapeHtml(f.placeholder || '') + '" />';
			}
			return '<div class="mk-wh-proto-field' + full + '"><label>' + escapeHtml(f.label) + req + '</label>' + input + '</div>';
		}).join('');
	}

	function guessSkuFromName(name, idx) {
		var clean = String(name || '').trim();
		if (!clean) return 'SKU-' + String(idx).padStart(2, '0');
		var words = clean.split(/\s+/).filter(Boolean);
		var initials = words.slice(0, 2).map(function (w) { return (w[0] || '').toUpperCase(); }).join('');
		var digits = (clean.match(/\d+/) || [])[0] || '';
		return (initials || 'SP') + (digits ? '-' + digits : '') + '-' + String(idx).padStart(2, '0');
	}

	function getStockProductCatalog(whId) {
		if (typeof window !== 'undefined' && window.MK_WH_PRODUCT_CATALOG && window.MK_WH_PRODUCT_CATALOG.length) {
			return window.MK_WH_PRODUCT_CATALOG.slice();
		}
		var d = S.ensureData(whId);
		var seen = {};
		var list = [];
		(d.stock || []).forEach(function (s) {
			if (!s.sku || seen[s.sku]) return;
			seen[s.sku] = true;
			list.push({ id: 0, sku: s.sku, name: s.name || s.sku, price: s.price || 0 });
		});
		list.sort(function (a, b) {
			return String(a.name).localeCompare(String(b.name), 'vi');
		});
		return list;
	}

	function productSelectHtml(catalog, selectedId) {
		var opts = '<option value="">— Tìm / chọn sản phẩm —</option>' +
			catalog.map(function (p) {
				var id = String(p.id || '');
				var name = decodeEntities(p.name || '');
				var sku = decodeEntities(p.sku || '');
				var sel = id && id === String(selectedId) ? ' selected="selected"' : '';
				var label = name + (sku ? ' · ' + sku : ' (chưa có SKU)');
				return '<option value="' + escapeHtml(id) + '" data-sku="' + escapeHtml(sku) +
					'" data-name="' + escapeHtml(name) + '" data-price="' + escapeHtml(String(p.price || 0)) + '"' + sel + '>' +
					escapeHtml(label) + '</option>';
			}).join('');
		return '<select class="mk-wh-proto-product-select" data-mk-line-product="1">' + opts + '</select>';
	}

	function getJq() {
		return typeof window !== 'undefined' && window.jQuery ? window.jQuery : null;
	}

	function destroyProductSelect2(el) {
		var $ = getJq();
		if (!$ || !el) return;
		var $el = $(el);
		if (!$el.data('mkSelect2Applied')) return;
		try {
			$el.off('.mkWhProduct');
			if ($.fn.select2) $el.select2('destroy');
		} catch (e) { /* ignore */ }
		$el.removeData('mkSelect2Applied');
	}

	function applyProductSelect2(el) {
		applySearchableSelect2(el, '— Tìm / chọn sản phẩm —', 'mk-wh-proto-product-select-s2');
	}

	function applySearchableSelect2(el, placeholder, containerClass) {
		var $ = getJq();
		if (!$ || !el || !$.fn.select2) return;
		var $el = $(el);
		if ($el.data('mkSelect2Applied')) return;
		$el.data('mkSelect2Applied', true);
		try {
			$el.select2({
				placeholder: placeholder || '— Chọn —',
				allowClear: !!el.getAttribute('data-mk-line-product'),
				width: '100%',
				minimumResultsForSearch: 0,
				minimumInputLength: 0,
				formatNoMatches: function () { return 'Không tìm thấy'; },
				formatSearching: function () { return 'Đang tìm...'; },
				dropdownCssClass: 'mk-wh-proto-s2-drop'
			});
			var inst = $el.data('select2');
			if (inst && inst.container && containerClass) {
				inst.container.addClass(containerClass);
			}
			$el.on('open.mkWhProduct select2-open.mkWhProduct', function () {
				var instance = $el.data('select2');
				if (instance && instance.dropdown) {
					instance.dropdown.css('z-index', 1000002);
				}
			});
		} catch (e) { /* plain select fallback */ }
	}

	function syncLineSkuFromSelect(selectEl) {
		if (!selectEl) return;
		var row = selectEl.closest('[data-mk-line="1"]');
		if (!row) return;
		var skuEl = row.querySelector('[data-mk-line-sku="1"]');
		if (!skuEl) return;
		var opt = selectEl.options[selectEl.selectedIndex];
		skuEl.value = (opt && opt.getAttribute('data-sku')) || '';
		if (!skuEl.value) {
			skuEl.value = '';
			skuEl.placeholder = 'Chưa có SKU';
		} else {
			skuEl.placeholder = 'SKU';
		}
	}

	function bindInboundLineRows(form) {
		var bodyEl = form.querySelector('[data-mk-lines-body="1"]');
		if (!bodyEl) return;
		var whId = getWhId();
		var catalog = whId ? getStockProductCatalog(whId) : [];

		function addRow(preset) {
			var p = preset || {};
			var productId = p.product_id || p.productId || '';
			var skuVal = p.sku || '';
			bodyEl.insertAdjacentHTML('beforeend',
				'<tr class="mk-wh-proto-lines__row" data-mk-line="1">' +
				'<td>' + productSelectHtml(catalog, productId) + '</td>' +
				'<td class="mk-wh-proto-col-sku"><input type="text" data-mk-line-sku="1" value="' + escapeHtml(skuVal) + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" placeholder="SKU" /></td>' +
				'<td class="mk-wh-proto-col-lot"><input type="text" data-mk-line-lot="1" value="' + escapeHtml(p.lot || '') + '" placeholder="LOT-2605A" /></td>' +
				'<td class="mk-wh-proto-col-qty"><input type="number" min="1" step="1" data-mk-line-qty="1" value="' + escapeHtml(p.qty != null ? p.qty : '') + '" placeholder="100" /></td>' +
				'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-mfg="1" value="' + escapeHtml(p.mfg || '') + '" /></td>' +
				'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-exp="1" value="' + escapeHtml(p.expiry || '') + '" /></td>' +
				'<td class="mk-wh-proto-col-loc"><input type="text" data-mk-line-location="1" value="' + escapeHtml(p.location || '') + '" placeholder="Kệ 420" /></td>' +
				'<td><button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost mk-wh-proto-lines__del" data-mk-lines-del="1" title="Xóa dòng">×</button></td>' +
				'</tr>');
			var rows = bodyEl.querySelectorAll('[data-mk-line="1"]');
			var newRow = rows[rows.length - 1];
			var productSel = newRow ? newRow.querySelector('[data-mk-line-product="1"]') : null;
			if (productSel) applyProductSelect2(productSel);
		}

		var linesHead = form.querySelector('.mk-wh-proto-lines__ttl');
		var addBtn = form.querySelector('[data-mk-lines-add="1"]');
		if (!catalog.length) {
			if (linesHead) {
				linesHead.innerHTML = '<span class="mk-wh-proto-lines__warn">Chưa có sản phẩm trong Products &amp; Services — hãy tạo sản phẩm trước.</span>';
			}
			if (addBtn) addBtn.disabled = true;
		} else {
			if (linesHead) linesHead.textContent = 'Chọn sản phẩm từ Products & Services — gõ để tìm, SKU tự động điền';
			if (!bodyEl.children.length) {
				addRow();
			}
			if (addBtn) {
				addBtn.disabled = false;
				addBtn.onclick = function () { addRow(); };
			}
		}

		form.onchange = null;
		var $ = getJq();
		if ($) {
			$(form).off('change.mkWhLineProduct', '[data-mk-line-product="1"]')
				.on('change.mkWhLineProduct', '[data-mk-line-product="1"]', function () {
					syncLineSkuFromSelect(this);
				});
		} else {
			form.onchange = function (e) {
				var t = e.target;
				if (!t || !(t.getAttribute && t.getAttribute('data-mk-line-product') === '1')) return;
				syncLineSkuFromSelect(t);
			};
		}
	}

	function getOutboundStockLots(whId) {
		var d = whId ? S.ensureData(whId) : { stock: [] };
		return (d.stock || [])
			.filter(function (s) { return (Number(s.qty) || 0) > 0; })
			.slice()
			.sort(function (a, b) {
				var an = String(a.name || '');
				var bn = String(b.name || '');
				if (an !== bn) return an.localeCompare(bn, 'vi');
				return String(a.lot || '').localeCompare(String(b.lot || ''), 'vi');
			});
	}

	function outboundLotSelectHtml(lots, selectedKey) {
		var opts = '<option value="">— Tìm / chọn sản phẩm —</option>' +
			lots.map(function (s) {
				var name = decodeEntities(s.name || s.sku || '');
				var sku = decodeEntities(s.sku || '');
				var lot = decodeEntities(s.lot || '').replace(/^\s*lô\s+/i, '').trim();
				var key = String(sku || '') + '|' + String(lot || '');
				var qty = Number(s.qty) || 0;
				var mfg = toDateInputValue(decodeEntities(s.mfg || ''));
				var expiry = toDateInputValue(decodeEntities(s.expiry || s.exp || ''));
				var sel = key && key === String(selectedKey || '') ? ' selected="selected"' : '';
				var label = name + (lot ? ' · Lô ' + lot : '') + ' · còn ' + qty;
				return '<option value="' + escapeHtml(key) + '" data-sku="' + escapeHtml(sku) +
					'" data-name="' + escapeHtml(name) + '" data-lot="' + escapeHtml(lot) +
					'" data-qty="' + escapeHtml(String(qty)) +
					'" data-mfg="' + escapeHtml(mfg) + '" data-expiry="' + escapeHtml(expiry) + '"' + sel + '>' +
					escapeHtml(label) + '</option>';
			}).join('');
		return '<select class="mk-wh-proto-product-select" data-mk-line-lotkey="1">' + opts + '</select>';
	}

	function syncOutboundLineFromLot(selectEl) {
		if (!selectEl) return;
		var row = selectEl.closest('[data-mk-line="1"]');
		if (!row) return;
		var opt = selectEl.options[selectEl.selectedIndex];
		var skuEl = row.querySelector('[data-mk-line-sku="1"]');
		var lotEl = row.querySelector('[data-mk-line-lot="1"]');
		var qtyEl = row.querySelector('[data-mk-line-qty="1"]');
		var mfgEl = row.querySelector('[data-mk-line-mfg="1"]');
		var expEl = row.querySelector('[data-mk-line-exp="1"]');
		if (!opt || !selectEl.value) {
			if (skuEl) { skuEl.value = ''; skuEl.placeholder = 'SKU'; }
			if (lotEl) lotEl.value = '';
			if (mfgEl) { mfgEl.value = ''; mfgEl.readOnly = true; }
			if (expEl) { expEl.value = ''; expEl.readOnly = true; }
			if (qtyEl) qtyEl.removeAttribute('max');
			return;
		}
		var lotKey = String(selectEl.value || '');
		var parts = lotKey.split('|');
		var skuKey = parts[0] || '';
		var lotKeyPart = parts.slice(1).join('|');
		var stockLot = findStockLot(getWhId(), skuKey, lotKeyPart);
		if (!stockLot && lotKeyPart) {
			// Match when sku in option key is empty/auto but stock row has resolved sku.
			stockLot = (getOutboundStockLots(getWhId()) || []).find(function (s) {
				return String(s.lot || '') === lotKeyPart || decodeEntities(s.lot || '') === decodeEntities(lotKeyPart);
			});
		}
		var sku = decodeEntities((stockLot && stockLot.sku) || opt.getAttribute('data-sku') || '');
		var lot = decodeEntities((stockLot && stockLot.lot) || opt.getAttribute('data-lot') || lotKeyPart || '');
		lot = lot.replace(/^\s*lô\s+/i, '').trim();
		var avail = stockLot ? (Number(stockLot.qty) || 0) : (parseInt(opt.getAttribute('data-qty') || '0', 10) || 0);
		var mfg = toDateInputValue(decodeEntities((stockLot && stockLot.mfg) || opt.getAttribute('data-mfg') || ''));
		var expiry = toDateInputValue(decodeEntities((stockLot && (stockLot.expiry || stockLot.exp)) || opt.getAttribute('data-expiry') || ''));
		if (skuEl) {
			skuEl.value = sku;
			skuEl.placeholder = sku ? 'SKU' : 'Chưa có SKU';
		}
		if (lotEl) lotEl.value = lot;
		if (mfgEl) {
			mfgEl.value = mfg;
			mfgEl.readOnly = !!mfg;
			mfgEl.classList.toggle('mk-wh-proto-sku-readonly', !!mfg);
			mfgEl.tabIndex = mfg ? -1 : 0;
		}
		if (expEl) {
			expEl.value = expiry;
			expEl.readOnly = !!expiry;
			expEl.classList.toggle('mk-wh-proto-sku-readonly', !!expiry);
			expEl.tabIndex = expiry ? -1 : 0;
		}
		if (qtyEl) {
			if (avail > 0) {
				qtyEl.setAttribute('max', String(avail));
				qtyEl.placeholder = String(Math.min(100, avail));
			} else {
				qtyEl.removeAttribute('max');
			}
		}
	}

	function bindOutboundLineRows(form) {
		var bodyEl = form.querySelector('[data-mk-lines-body="1"]');
		if (!bodyEl) return;
		var whId = getWhId();
		var lots = getOutboundStockLots(whId);
		var linesHead = form.querySelector('.mk-wh-proto-lines__ttl');
		var addBtn = form.querySelector('[data-mk-lines-add="1"]');

		function addRow(preset) {
			var p = preset || {};
			var selectedKey = p.lotKey || ((p.sku && p.lot) ? (p.sku + '|' + p.lot) : '');
			bodyEl.insertAdjacentHTML('beforeend',
				'<tr class="mk-wh-proto-lines__row" data-mk-line="1">' +
				'<td>' + outboundLotSelectHtml(lots, selectedKey) + '</td>' +
				'<td class="mk-wh-proto-col-sku"><input type="text" data-mk-line-sku="1" value="' + escapeHtml(p.sku || '') + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" placeholder="SKU" /></td>' +
				'<td class="mk-wh-proto-col-lot"><input type="text" data-mk-line-lot="1" value="' + escapeHtml(p.lot || '') + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" placeholder="LOT-2605A" /></td>' +
				'<td class="mk-wh-proto-col-qty"><input type="number" min="1" step="1" data-mk-line-qty="1" value="' + escapeHtml(p.qty != null ? p.qty : '') + '" placeholder="100" /></td>' +
				'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-mfg="1" value="' + escapeHtml(p.mfg || '') + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" /></td>' +
				'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-exp="1" value="' + escapeHtml(p.expiry || '') + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" /></td>' +
				'<td><button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost mk-wh-proto-lines__del" data-mk-lines-del="1" title="Xóa dòng">×</button></td>' +
				'</tr>');
			var rows = bodyEl.querySelectorAll('[data-mk-line="1"]');
			var newRow = rows[rows.length - 1];
			var lotSel = newRow ? newRow.querySelector('[data-mk-line-lotkey="1"]') : null;
			if (lotSel) {
				applySearchableSelect2(lotSel, '— Tìm / chọn sản phẩm —', 'mk-wh-proto-product-select-s2');
				if (selectedKey) syncOutboundLineFromLot(lotSel);
			}
		}

		if (!lots.length) {
			if (linesHead) {
				linesHead.innerHTML = '<span class="mk-wh-proto-lines__warn">Chưa có tồn kho — hãy nhập kho trước để xuất.</span>';
			}
			if (addBtn) addBtn.disabled = true;
		} else {
			if (linesHead) linesHead.textContent = 'Chọn lô từ tồn kho — gõ để tìm, SKU / Lô / NSX / HSD tự động điền';
			if (!bodyEl.children.length) {
				addRow();
			}
			if (addBtn) {
				addBtn.disabled = false;
				addBtn.onclick = function () { addRow(); };
			}
		}

		var $ = getJq();
		if ($) {
			$(form).off('change.mkWhOutLot', '[data-mk-line-lotkey="1"]')
				.on('change.mkWhOutLot', '[data-mk-line-lotkey="1"]', function () {
					syncOutboundLineFromLot(this);
				});
		} else {
			form.addEventListener('change', function (e) {
				var t = e.target;
				if (!t || !(t.getAttribute && t.getAttribute('data-mk-line-lotkey') === '1')) return;
				syncOutboundLineFromLot(t);
			});
		}
	}

	function openModal(opts) {
		var modal = qs('#mkWhProtoModal');
		var title = qs('#mkWhProtoModalTitle');
		var fields = qs('#mkWhProtoFormFields');
		var submit = qs('#mkWhProtoSubmitBtn');
		var form = qs('#mkWhProtoModalForm');
		if (!modal || !title || !fields || !form) return;

		var dialog = modal.querySelector('.mk-wh-proto-modal__dialog');
		if (dialog) {
			dialog.classList.toggle('mk-wh-proto-modal__dialog--compact',
				opts.tabKey === 'inbound' || opts.tabKey === 'outbound-type' || opts.tabKey === 'outbound');
			dialog.classList.toggle('mk-wh-proto-modal__dialog--lux',
				opts.tabKey === 'inbound' || opts.tabKey === 'outbound-type' || opts.tabKey === 'outbound');
		}

		title.textContent = opts.title || 'Tạo phiếu';
		if (submit) submit.textContent = opts.submitLabel || 'Tạo phiếu';
		fields.innerHTML = renderModalFields(opts.fields || []);

		if (opts.tabKey === 'inbound') {
			bindInboundLineRows(form);
		} else if (opts.tabKey === 'outbound') {
			bindOutboundLineRows(form);
		}

		var sendQcCheckbox = form.querySelector('[name="sendQc"]');
		function syncInboundSubmitLabel() {
			if (!submit || !sendQcCheckbox || opts.tabKey !== 'inbound') return;
			submit.textContent = sendQcCheckbox.checked ? (opts.submitLabel || 'Tạo phiếu') : 'Tạo & nhập kho';
		}
		if (sendQcCheckbox) {
			sendQcCheckbox.addEventListener('change', syncInboundSubmitLabel);
			syncInboundSubmitLabel();
		}

		form.onsubmit = function (e) {
			e.preventDefault();
			var whId = getWhId();
			if (!whId) return;
			var fd = new FormData(form);
			var tabKey = opts.tabKey || 'inbound';
			var d = S.ensureData(whId);

			if (tabKey === 'outbound-type') {
				var pickedType = String(fd.get('outboundType') || '');
				if (!pickedType || OUTBOUND_TYPE_PICKER.indexOf(pickedType) < 0) return;
				openModal(modalSchema('outbound', pickedType));
				return;
			}

			if (tabKey === 'outbound') {
				var customerRaw = String(fd.get('customer') || '').trim();
				var soRef = String(fd.get('so') || '').trim() || '—';
				var outboundType = opts.outboundType || 'internal';
				var customer = customerRaw;
				var toWarehouseId = '';
				if (outboundType === 'transfer') {
					toWarehouseId = customerRaw;
					customer = resolveWarehouseLabel(customerRaw);
					if (!toWarehouseId || !customer) {
						showError('Vui lòng chọn kho đích.');
						return;
					}
				} else if (!customer) {
					showError('Vui lòng nhập ' + (getOutboundTypeMeta(outboundType).customerLabel || 'thông tin bắt buộc') + '.');
					return;
				}
				var outRows = Array.prototype.slice.call(form.querySelectorAll('[data-mk-line="1"]'));
				var outLines = [];
				var outError = '';
				outRows.forEach(function (row) {
					var lotSel = row.querySelector('[data-mk-line-lotkey="1"]');
					var lotKey = lotSel ? String(lotSel.value || '') : '';
					var qtyOut = row.querySelector('[data-mk-line-qty="1"]')
						? (parseInt(row.querySelector('[data-mk-line-qty="1"]').value, 10) || 0)
						: 0;
					if (!lotKey && !qtyOut) return;
					if (!lotKey || qtyOut <= 0) {
						outError = 'Mỗi dòng xuất cần chọn lô hàng và số lượng hợp lệ.';
						return;
					}
					var parts = lotKey.split('|');
					var sku = parts[0];
					var lot = parts.slice(1).join('|');
					var stockLot = findStockLot(whId, sku, lot);
					if (!stockLot) {
						outError = 'Lô hàng không còn trong tồn kho.';
						return;
					}
					var avail = Number(stockLot.qty) || 0;
					if (qtyOut > avail) {
						outError = 'Số lượng xuất vượt tồn (còn ' + avail + ') cho ' + (stockLot.name || sku) + ' · ' + lot + '.';
						return;
					}
					var mfgVal = row.querySelector('[data-mk-line-mfg="1"]')
						? row.querySelector('[data-mk-line-mfg="1"]').value
						: '';
					var expVal = row.querySelector('[data-mk-line-exp="1"]')
						? row.querySelector('[data-mk-line-exp="1"]').value
						: '';
					outLines.push({
						sku: stockLot.sku,
						name: stockLot.name,
						lot: stockLot.lot,
						qty: qtyOut,
						mfg: toDateInputValue(mfgVal || stockLot.mfg || ''),
						expiry: toDateInputValue(expVal || stockLot.expiry || stockLot.exp || '') || '—',
					});
				});
				if (outError) {
					showError(outError);
					return;
				}
				if (!outLines.length) {
					showError('Vui lòng thêm ít nhất một dòng hàng xuất.');
					return;
				}
				var typeMeta = getOutboundTypeMeta(outboundType);
				var id = nextId('GIN', d.issues || []);
				var now = S.nowISO();
				var issue = {
					id: id,
					outboundType: outboundType,
					customer: customer,
					toWarehouseId: toWarehouseId || undefined,
					soRef: soRef,
					status: 'waiting_print',
					createdAt: now,
					createdBy: 'QL Tuấn',
					lines: outLines,
					timeline: [],
				};
				addTimeline(issue.timeline, 'Tạo phiếu xuất — ' + typeMeta.short, 'manager');
				var issues = (d.issues || []).slice();
				issues.unshift(issue);
				S.warehouseDataActions.setIssues(whId, issues);
				closeModal();
				var dlg = issueDialog(issue);
				openDialog(dlg.title, dlg.meta, dlg.body);
				return;
			}

			var supplier = String(fd.get('supplier') || '').trim();
			var poRef = String(fd.get('po') || '').trim();
			var sendQc = fd.has('sendQc');
			if (!supplier || !poRef) {
				showError('Vui lòng nhập đầy đủ Nhà cung cấp và Mã PO.');
				return;
			}

			var rows = Array.prototype.slice.call(form.querySelectorAll('[data-mk-line="1"]'));
			var lines = [];
			var missingSku = false;
			rows.forEach(function (row) {
				var productSel = row.querySelector('[data-mk-line-product="1"]');
				var productId = productSel ? (parseInt(productSel.value, 10) || 0) : 0;
				var sku = '';
				var name = '';
				var price = 0;
				if (productSel && productSel.selectedIndex > 0) {
					var opt = productSel.options[productSel.selectedIndex];
					sku = decodeEntities((opt && opt.getAttribute('data-sku')) || '');
					name = decodeEntities((opt && opt.getAttribute('data-name')) || '');
					price = parseFloat((opt && opt.getAttribute('data-price')) || 0) || 0;
				}
				var lot = row.querySelector('[data-mk-line-lot="1"]') ? row.querySelector('[data-mk-line-lot="1"]').value.trim() : '';
				var qty = row.querySelector('[data-mk-line-qty="1"]') ? (parseInt(row.querySelector('[data-mk-line-qty="1"]').value, 10) || 0) : 0;
				var mfg = row.querySelector('[data-mk-line-mfg="1"]') ? row.querySelector('[data-mk-line-mfg="1"]').value : '';
				var expiry = row.querySelector('[data-mk-line-exp="1"]') ? row.querySelector('[data-mk-line-exp="1"]').value : '';
				var location = row.querySelector('[data-mk-line-location="1"]') ? row.querySelector('[data-mk-line-location="1"]').value.trim() : '';
				if (!productId && !lot && !qty) return;
				if (!productId || !name || !lot || qty <= 0) return;
				if (productId && !sku) {
					missingSku = true;
					return;
				}
				lines.push({ product_id: productId, sku: sku, name: name, lot: lot, mfg: mfg || '', expiry: expiry || '—', qty: qty, price: price, location: location });
			});
			if (missingSku) {
				showError('Một hoặc nhiều sản phẩm chưa có SKU. Hãy cập nhật SKU trong Products & Services trước khi nhập kho.');
				return;
			}
			if (!lines.length) {
				showError('Vui lòng chọn sản phẩm và nhập Lô + Số lượng cho ít nhất một dòng.');
				return;
			}

			var receiptPayload = {
				supplier: supplier,
				poRef: poRef,
				sendQc: sendQc,
				lines: lines,
			};

			if (S.useDb && S.useDb()) {
				if (submit) submit.disabled = true;
				S.warehouseDataActions.saveReceipt(whId, receiptPayload).then(function (res) {
					closeModal();
					refreshWarehouseUi();
					var saved = res && res.data && res.data.receipts && res.data.receipts[0];
					if (saved) {
						var rDlg = receiptDialog(saved);
						openDialog(rDlg.title, rDlg.meta, rDlg.body);
					}
				}).fail(function (err) {
					var msg = typeof err === 'string' ? err : (err && err.message) || (err && err.error && err.error.message) || (err && err.error) || 'Không lưu được phiếu nhập. Vui lòng thử lại.';
					showError(msg);
				}).always(function () {
					if (submit) submit.disabled = false;
				});
				return;
			}

			var nowIn = S.nowISO();
			var rid = nextId('GRN', d.receipts || []);
			var receipt = {
				id: rid,
				supplier: supplier,
				poRef: poRef,
				createdAt: nowIn,
				createdBy: 'QL Tuấn',
				status: sendQc ? 'pending_qc' : 'stored',
				lines: lines,
				timeline: [],
			};
			addTimeline(receipt.timeline, 'Tạo phiếu nhập', 'manager');
			if (sendQc) addTimeline(receipt.timeline, 'Gửi QC kiểm tra', 'manager');
			else addTimeline(receipt.timeline, 'Nhập thẳng tồn kho', 'manager', 'Không gửi QC — cộng tồn ngay');

			var receipts = (d.receipts || []).slice();
			receipts.unshift(receipt);
			S.warehouseDataActions.setReceipts(whId, receipts);
			if (!sendQc) addStockFromReceiptLines(whId, lines);

			closeModal();
			var rDlg = receiptDialog(receipt);
			openDialog(rDlg.title, rDlg.meta, rDlg.body);
		};

		form.onclick = function (e) {
			var t = e.target;
			if (t && t.getAttribute && t.getAttribute('data-mk-lines-del') === '1') {
				e.preventDefault();
				var row = t.closest('[data-mk-line="1"]');
				var bodyEl = form.querySelector('[data-mk-lines-body="1"]');
				if (row && bodyEl && bodyEl.children.length > 1) {
					var productSel = row.querySelector('[data-mk-line-product="1"]');
					var lotKeySel = row.querySelector('[data-mk-line-lotkey="1"]');
					destroyProductSelect2(productSel || lotKeySel);
					row.remove();
				}
			}
		};

		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
	}

	function openCreateInbound() {
		openModal(modalSchema('inbound'));
	}

	function openOutboundTypePicker() {
		openModal(modalSchema('outbound-type'));
	}

	function boot() {
		if (!qs('#mkWhPrototypeRoot')) return;
		S.hydrate();
		if (!renderHeader()) return;

		// Role select uses Prototype keys: qc / stock / manager
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
			} else if (tabKey === 'outbound') {
				openOutboundTypePicker();
			}
		});

		var modal = qs('#mkWhProtoModal');
		if (modal) {
			modal.addEventListener('click', function (e) {
				var target = e.target;
				if (target && target.getAttribute && target.getAttribute('data-mk-close') === '1') {
					closeModal();
				}
			});
		}

		document.addEventListener('click', function (e) {
			var t = e.target;
			if (!t) return;

			// Support clicks on inner <span>/<svg> inside buttons.
			var actionEl = (t.closest && t.closest('[data-mk-action]')) || null;
			var closeEl = (t.closest && t.closest('[data-mk-dialog-close="1"]')) || null;
			if (closeEl) {
				closeDialog();
				return;
			}
			if (!actionEl || !actionEl.getAttribute) return;
			var action = actionEl.getAttribute('data-mk-action');
			var id = actionEl.getAttribute('data-id');
			if (!action) return;

			var whId = getWhId();
			if (!whId) return;
			var d = S.ensureData(whId);

			if (action === 'inbound-detail' && id) {
				if (S.useDb && S.useDb() && S.warehouseDataActions && typeof S.warehouseDataActions.refresh === 'function') {
					S.warehouseDataActions.refresh(whId).always(function () {
						var d0 = S.ensureData(whId);
						var r0 = (d0.receipts || []).find(function (x) { return x.id === id; });
						if (!r0) return;
						var dialog0 = receiptDialog(r0);
						openDialog(dialog0.title, dialog0.meta, dialog0.body);
					});
					return;
				}
				var r = (d.receipts || []).find(function (x) { return x.id === id; });
				if (!r) return;
				var dialog = receiptDialog(r);
				openDialog(dialog.title, dialog.meta, dialog.body);
				return;
			}
			if (action === 'qc-record' && id) {
				if (S.useDb && S.useDb() && S.warehouseDataActions && typeof S.warehouseDataActions.refresh === 'function') {
					S.warehouseDataActions.refresh(whId).always(function () {
						var d1 = S.ensureData(whId);
						var r1 = (d1.receipts || []).find(function (x) { return x.id === id; });
						if (!r1) return;
						var dialog1 = receiptDialog(r1);
						openDialog(dialog1.title, dialog1.meta, dialog1.body);
					});
					return;
				}
				var r2 = (d.receipts || []).find(function (x) { return x.id === id; });
				if (!r2) return;
				var dialog2 = receiptDialog(r2);
				openDialog(dialog2.title, dialog2.meta, dialog2.body);
				return;
			}
			if (action === 'outbound-detail' && id) {
				if (S.useDb && S.useDb() && S.warehouseDataActions && typeof S.warehouseDataActions.refresh === 'function') {
					S.warehouseDataActions.refresh(whId).always(function () {
						var d2 = S.ensureData(whId);
						var issue2 = (d2.issues || []).find(function (x) { return x.id === id; });
						if (!issue2) return;
						var dlg2 = issueDialog(issue2);
						openDialog(dlg2.title, dlg2.meta, dlg2.body);
					});
					return;
				}
				var issue = (d.issues || []).find(function (x) { return x.id === id; });
				if (!issue) return;
				var dlg = issueDialog(issue);
				openDialog(dlg.title, dlg.meta, dlg.body);
				return;
			}

			// Receipt actions
			if (id && (action === 'send-qc' || action === 'qc-pass' || action === 'qc-fail' || action === 'mgr-approve' || action === 'store')) {
				var role = getRole();
				if (S.useDb && S.useDb()) {
					var note = '';
					if (action === 'qc-pass' || action === 'qc-fail') {
						note = readQcNoteFromDialog(actionEl);
					}
					S.warehouseDataActions
						.receiptAction(whId, id, action, role, note)
						.then(function (res) {
							closeDialog();
							refreshWarehouseUi();
							reopenReceiptDialog(whId, id, res);
						})
						.fail(function (err) {
							showError((err && err.message) || 'Không cập nhật được phiếu nhập.');
						});
					return;
				}
				patchReceipt(id, function (r) {
					if (action === 'send-qc') {
						r.status = 'pending_qc';
						addTimeline(r.timeline, 'Gửi QC kiểm tra', role);
					} else if (action === 'qc-pass') {
						var note = readQcNoteFromDialog(actionEl);
						r.status = 'qc_passed';
						r.qc = { result: 'pass', note: note, at: S.nowISO(), by: 'QC Minh' };
						r.lines = (r.lines || []).map(function (l) { return Object.assign({}, l, { qcResult: 'pass', passedQty: l.qty }); });
						addTimeline(r.timeline, 'QC đạt', role, note || undefined);
					} else if (action === 'qc-fail') {
						var note2 = readQcNoteFromDialog(actionEl);
						r.status = 'qc_failed';
						r.qc = { result: 'fail', note: note2, at: S.nowISO(), by: 'QC Minh' };
						r.lines = (r.lines || []).map(function (l) { return Object.assign({}, l, { qcResult: 'fail', passedQty: 0 }); });
						addTimeline(r.timeline, 'QC không đạt', role, note2 || undefined);
					} else if (action === 'mgr-approve') {
						r.status = 'approved';
						addTimeline(r.timeline, 'Duyệt phiếu', role);
					} else if (action === 'store') {
						addStockFromReceiptLines(whId, r.lines || []);
						r.status = 'stored';
						addTimeline(r.timeline, 'Đã nhập kho', role, formatLocationNote(r.lines));
					}
					return r;
				});
				refreshWarehouseUi();
				reopenReceiptDialog(whId, id);
				return;
			}

			// Issue actions
			if (id && (action === 'issue-submit' || action === 'issue-start-pick' || action === 'issue-finish-pick' || action === 'issue-ship' || action === 'issue-approve' || action === 'issue-reject')) {
				var role2 = getRole();
				if (S.useDb && S.useDb()) {
					var reasonDb = '';
					if (action === 'issue-reject') {
						var rsDb = qs('[data-mk-reject-reason="1"]');
						reasonDb = rsDb ? String(rsDb.value || '').trim() : '';
					}
					S.warehouseDataActions
						.issueAction(whId, id, action, role2, reasonDb)
						.then(function (res) {
							closeDialog();
							refreshWarehouseUi();
							reopenIssueDialog(whId, id, res);
						})
						.fail(function (err) {
							showError((err && err.message) || 'Không cập nhật được phiếu xuất.');
						});
					return;
				}
				patchIssue(id, function (i) {
					if (action === 'issue-submit') {
						i.status = 'waiting_print';
						addTimeline(i.timeline, 'Chờ in phiếu', role2);
					} else if (action === 'issue-start-pick' || action === 'issue-approve') {
						i.status = 'picking';
						addTimeline(i.timeline, 'Bắt đầu soạn hàng', role2);
					} else if (action === 'issue-finish-pick') {
						deductStockFromIssueLines(whId, i.lines || []);
						i.status = 'packed';
						addTimeline(i.timeline, 'Đã soạn hàng', role2);
					} else if (action === 'issue-reject') {
						var rs = qs('[data-mk-reject-reason="1"]');
						var reason = rs ? String(rs.value || '').trim() : '';
						i.status = 'rejected';
						addTimeline(i.timeline, 'Từ chối phiếu', role2, reason || 'Không nêu lý do');
					} else if (action === 'issue-ship') {
						i.status = 'shipped';
						addTimeline(i.timeline, 'Đã giao hàng', role2);
					}
					return i;
				});
				refreshWarehouseUi();
				reopenIssueDialog(whId, id);
				return;
			}
		});

		S.subscribe(function () {
			renderHeader();
			updateRoleBanner();
			renderAll();
		});

		var initialTab = 'inbound';
		try {
			var params = new URLSearchParams(window.location.search || '');
			var tabParam = String(params.get('tab') || '').toLowerCase();
			if (tabParam === 'outbound' || tabParam === 'xuatkho' || tabParam === 'stock' || tabParam === 'qc' || tabParam === 'inbound') {
				initialTab = tabParam === 'xuatkho' ? 'outbound' : tabParam;
			} else if (window.location.hash) {
				var hash = String(window.location.hash || '').replace(/^#/, '').toLowerCase();
				if (hash === 'outbound' || hash === 'xuatkho') {
					initialTab = 'outbound';
				}
			}
		} catch (ignore) { /* ignore */ }
		setActiveTab(initialTab);
		renderAll();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

