/**
 * Warehouse Prototype — UI demo (RAM: window.__mkWhProtoState, mất khi reload).
 * Tham chiếu state machine: https://github.com/tangchanhungit/b-ace/tree/main/src/routes/warehouse-prototype.tsx
 *
 * Nhập kho (Receipt): draft → pending_qc → qc_passed|qc_failed → approved → stored (+ cộng tồn)
 * Hoặc: không cần QC → stored ngay (+ cộng tồn)
 * Xuất kho (Issue): draft → pending_approval → approved → shipped | rejected (trừ tồn khi shipped)
 * Tồn kho (StockLot): sku+lot, qty, expiry, location — cập nhật khi stored / shipped
 */
(function () {
	'use strict';

	function qs(sel) {
		return document.querySelector(sel);
	}

	function qsa(sel) {
		return Array.prototype.slice.call(document.querySelectorAll(sel));
	}

	function fmtNow() {
		var d = new Date();
		var dd = String(d.getDate());
		var mm = String(d.getMonth() + 1);
		var yy = String(d.getFullYear()).slice(-2);
		var hh = String(d.getHours()).padStart(2, '0');
		var mi = String(d.getMinutes()).padStart(2, '0');
		return hh + ':' + mi + ' ' + dd + '/' + mm + '/' + yy;
	}

	function escapeHtml(s) {
		return String(s || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function daysUntil(exp) {
		if (!exp) return 999;
		var t = new Date(exp).getTime() - Date.now();
		return Math.round(t / 86400000);
	}

	function fmtPrice(n) {
		var v = Number(n);
		if (!isFinite(v)) return '—';
		return v.toLocaleString('vi-VN') + ' ₫';
	}

	function getStockFilterState() {
		var hsdEl = qs('#mkWhProtoFilterHsd');
		var nameEl = qs('#mkWhProtoFilterName');
		var priceEl = qs('#mkWhProtoFilterPrice');
		return {
			hsd: hsdEl ? hsdEl.value : 'all',
			name: nameEl ? nameEl.value : 'az',
			price: priceEl ? priceEl.value : 'all',
		};
	}

	function resetStockFilters() {
		var hsdEl = qs('#mkWhProtoFilterHsd');
		var nameEl = qs('#mkWhProtoFilterName');
		var priceEl = qs('#mkWhProtoFilterPrice');
		if (hsdEl) hsdEl.value = 'all';
		if (nameEl) nameEl.value = 'az';
		if (priceEl) priceEl.value = 'all';
	}

	function matchesHsdFilter(expiry, hsdFilter) {
		var days = daysUntil(expiry);
		if (hsdFilter === 'soon') return days >= 0 && days < 90;
		if (hsdFilter === 'valid') return days >= 0;
		if (hsdFilter === 'expired') return days < 0;
		return true;
	}

	function applyStockFilters(rows) {
		var filters = getStockFilterState();
		var list = (rows || []).filter(function (s) {
			return (Number(s.qty) || 0) > 0 && matchesHsdFilter(s.expiry, filters.hsd);
		});

		list.sort(function (a, b) {
			if (filters.price === 'asc' || filters.price === 'desc') {
				var pa = Number(a.price) || 0;
				var pb = Number(b.price) || 0;
				if (pa !== pb) return filters.price === 'asc' ? pa - pb : pb - pa;
			}
			var na = String(a.name || '').toLocaleLowerCase('vi');
			var nb = String(b.name || '').toLocaleLowerCase('vi');
			var cmp = na.localeCompare(nb, 'vi');
			return filters.name === 'za' ? -cmp : cmp;
		});

		return { rows: list, filters: filters };
	}

	function addTimeline(list, ev) {
		if (!list) return;
		list.push(ev);
	}

	function getState() {
		if (!window.__mkWhProtoState) {
			window.__mkWhProtoState = {
				nextInboundSeq: 8634,
				nextOutboundSeq: 3,
				receipts: [
					{
						code: 'GRN-0001',
						supplier: 'CTY Dược Hậu Giang',
						po: 'PO-2026-0142',
						createdAt: '15:30 28/5/26',
						status: 'stored', // draft | pending_qc | qc_passed | qc_failed | approved | stored
						items: [
							{ name: 'Paracetamol 500mg', sku: 'MED-001', lot: 'LOT-2605A', mfg: '2025-05-01', exp: '2027-05-01', qty: 1000, qc: 'pass' },
							{ name: 'Amoxicillin 250mg', sku: 'MED-002', lot: 'LOT-2605B', mfg: '2025-03-01', exp: '2027-03-15', qty: 500, qc: 'pass' },
						],
						timeline: [
							{ at: '15:30 28/5/26', by: 'Thủ kho Hà', role: 'keeper', action: 'Tạo phiếu nhập' },
							{ at: '16:15 28/5/26', by: 'QC Minh', role: 'qc', action: 'QC đạt', note: 'Đầy đủ chứng từ, đúng quy cách' },
							{ at: '17:00 28/5/26', by: 'QL Tuấn', role: 'manager', action: 'Duyệt phiếu' },
							{ at: '17:30 28/5/26', by: 'Thủ kho Hà', role: 'keeper', action: 'Đã nhập kho', note: 'Vị trí: A1-02' },
						],
					},
					{
						code: 'GRN-0002',
						supplier: 'Vinamilk Logistics',
						po: 'PO-2026-0151',
						createdAt: '14:20 1/6/26',
						status: 'pending_qc',
						items: [{ name: 'Sữa tươi 1L', sku: 'FMC-010', lot: 'LOT-0106', mfg: '2026-06-01', exp: '2026-08-30', qty: 300, qc: 'none' }],
						timeline: [
							{ at: '14:20 1/6/26', by: 'Thủ kho Hà', role: 'keeper', action: 'Tạo phiếu nhập' },
							{ at: '14:45 1/6/26', by: 'Thủ kho Hà', role: 'keeper', action: 'Gửi QC kiểm tra' },
						],
					},
					{
						code: 'GRN-0003',
						supplier: 'Nhà cung cấp ABC',
						po: 'PO-2026-0155',
						createdAt: '16:00 2/6/26',
						status: 'draft',
						items: [{ name: 'Aspirin 100mg', sku: 'MED-004', lot: 'LOT-2504X', mfg: '2026-04-01', exp: '2026-07-10', qty: 150, qc: 'none' }],
						timeline: [{ at: '16:00 2/6/26', by: 'Thủ kho Hà', role: 'keeper', action: 'Tạo phiếu nhập (nháp)' }],
					},
				],
				issues: [
					{
						code: 'GIN-0001',
						outboundType: 'sale',
						customer: 'Bệnh viện Bạch Mai',
						so: 'SO-2026-0088',
						status: 'shipped',
						createdAt: '21:00 30/5/26',
						createdBy: 'Thủ kho Hà',
						items: [{ sku: 'MED-001', name: 'Paracetamol 500mg', lot: 'LOT-2605A', qty: 200 }],
						timeline: [
							{ at: '21:00 30/5/26', by: 'Thủ kho Hà', role: 'keeper', action: 'Tạo phiếu xuất' },
							{ at: '21:30 30/5/26', by: 'QL Tuấn', role: 'manager', action: 'Duyệt phiếu' },
							{ at: '22:00 30/5/26', by: 'Thủ kho Hà', role: 'keeper', action: 'Soạn hàng & giao' },
						],
					},
					{
						code: 'GIN-0002',
						outboundType: 'sale',
						customer: 'Nhà thuốc Quận 1',
						so: 'SO-2026-0101',
						status: 'pending_approval',
						createdAt: '22:10 30/5/26',
						createdBy: 'Thủ kho Hà',
						items: [{ sku: 'MED-002', name: 'Amoxicillin 250mg', lot: 'LOT-2604B', qty: 50 }],
						timeline: [{ at: '22:10 30/5/26', by: 'Thủ kho Hà', role: 'keeper', action: 'Gửi duyệt' }],
					},
				],
				stock: [
					{ sku: 'MED-001', name: 'Paracetamol 500mg', lot: 'LOT-2605A', expiry: '2027-05-01', qty: 800, location: 'A1-02', price: 25000 },
					{ sku: 'MED-002', name: 'Amoxicillin 250mg', lot: 'LOT-2604B', expiry: '2026-08-15', qty: 120, location: 'B2-01', price: 45000 },
					{ sku: 'MED-003', name: 'Vitamin C 1000mg', lot: 'LOT-2603C', expiry: '2026-06-01', qty: 45, location: 'C1-03', price: 120000 },
				],
			};
		}
		return window.__mkWhProtoState;
	}

	var ISSUE_STATUS = {
		draft: { label: 'Nháp', cls: 'draft' },
		pending_approval: { label: 'Chờ duyệt', cls: 'warn' },
		approved: { label: 'Đã duyệt', cls: 'ok' },
		picking: { label: 'Đang soạn', cls: 'blue' },
		shipped: { label: 'Đã giao', cls: 'ok' },
		rejected: { label: 'Từ chối', cls: 'warn' },
	};

	var OUTBOUND_TYPES = {
		sale: {
			label: 'Xuất bán (từ invoice)',
			short: 'Xuất bán',
			customerLabel: 'Khách hàng',
			soLabel: 'Mã invoice / SO',
			soPlaceholder: 'INV-2026-...',
		},
		internal: {
			label: 'Xuất nội bộ (test)',
			short: 'Xuất nội bộ',
			customerLabel: 'Bộ phận / mục đích',
			soLabel: 'Mã tham chiếu',
			soPlaceholder: 'IT-TEST-...',
		},
		transfer: {
			label: 'Xuất chuyển kho',
			short: 'Chuyển kho',
			customerLabel: 'Kho đích',
			soLabel: 'Mã chuyển kho',
			soPlaceholder: 'TRF-...',
		},
		scrap: {
			label: 'Xuất huỷ',
			short: 'Xuất huỷ',
			customerLabel: 'Lý do huỷ',
			soLabel: 'Mã phiếu huỷ',
			soPlaceholder: 'SCR-...',
		},
	};

	function getOutboundTypeMeta(type) {
		return OUTBOUND_TYPES[type] || OUTBOUND_TYPES.internal;
	}

	function outboundTypePill(type) {
		var meta = getOutboundTypeMeta(type);
		return '<span class="mk-wh-proto-pill mk-wh-proto-pill--blue">' + escapeHtml(meta.short) + '</span>';
	}

	function findStockLot(sku, lot) {
		return (getState().stock || []).filter(function (s) {
			return s.sku === sku && s.lot === lot;
		})[0];
	}

	function getStockProductCatalog() {
		var seen = {};
		var list = [];
		(getState().stock || []).forEach(function (s) {
			if (!s.sku || seen[s.sku]) return;
			if ((Number(s.qty) || 0) <= 0) return;
			seen[s.sku] = true;
			list.push({ sku: s.sku, name: s.name || s.sku });
		});
		list.sort(function (a, b) {
			return String(a.name).localeCompare(String(b.name), 'vi');
		});
		return list;
	}

	function productSelectHtml(catalog, selectedSku) {
		var opts = '<option value="">— Chọn sản phẩm từ tồn kho —</option>' +
			catalog.map(function (p) {
				var sel = p.sku === selectedSku ? ' selected="selected"' : '';
				return '<option value="' + escapeHtml(p.sku) + '" data-name="' + escapeHtml(p.name) + '"' + sel + '>' +
					escapeHtml(p.name) + ' · ' + escapeHtml(p.sku) + '</option>';
			}).join('');
		return '<select class="mk-wh-proto-product-select" data-mk-line-product="1" required>' + opts + '</select>';
	}

	function receiptNeedsQc(rec) {
		if (!rec) return true;
		if (rec.needsQc === false) return false;
		return !(rec.items || []).some(function (it) {
			return it.needsQc === false || it.qc === 'skip';
		});
	}

	function addStockFromReceipt(rec) {
		var st = getState();
		st.stock = st.stock || [];
		(rec.items || []).forEach(function (it) {
			if (it.qc === 'fail') return;
			var existing = findStockLot(it.sku, it.lot);
			if (existing) {
				existing.qty += Number(it.qty) || 0;
			} else {
				st.stock.push({
					sku: it.sku,
					name: it.name,
					lot: it.lot,
					expiry: it.exp || it.expiry,
					qty: Number(it.qty) || 0,
					location: 'A1-02',
					price: Number(it.price) || 0,
				});
			}
		});
	}

	function deductStockFromIssue(issue) {
		(issue.items || []).forEach(function (line) {
			var lot = findStockLot(line.sku, line.lot);
			if (lot) lot.qty = Math.max(0, (Number(lot.qty) || 0) - (Number(line.qty) || 0));
		});
	}

	function updateKpis() {
		var st = getState();
		var pendingQc = (st.receipts || []).filter(function (r) {
			return r.status === 'pending_qc';
		}).length;
		var pendingApproval = (st.issues || []).filter(function (i) {
			return i.status === 'pending_approval';
		}).length;
		var stock = st.stock || [];
		var skuSet = {};
		stock.forEach(function (s) {
			if (s.qty > 0) skuSet[s.sku] = true;
		});
		var expiringSoon = stock.filter(function (s) {
			return s.qty > 0 && daysUntil(s.expiry) < 90;
		}).length;
		var kpiQc = qs('#mkWhKpiPendingQc');
		var kpiAppr = qs('#mkWhKpiPendingApprove');
		var kpiSku = qs('#mkWhKpiSku');
		var kpiExp = qs('#mkWhKpiExpiring');
		if (kpiQc) kpiQc.textContent = String(pendingQc);
		if (kpiAppr) kpiAppr.textContent = String(pendingApproval);
		if (kpiSku) kpiSku.textContent = String(Object.keys(skuSet).length);
		if (kpiExp) kpiExp.textContent = String(expiringSoon);
	}

	function renderInbounds() {
		var st = getState();
		var tbody = qs('#mkWhProtoInboundTbody');
		if (!tbody) return;
		tbody.innerHTML = st.receipts
			.map(function (r) {
				var pill =
					r.status === 'stored'
						? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đã nhập kho</span>' +
							(!receiptNeedsQc(r) ? ' <span class="mk-wh-proto-pill mk-wh-proto-pill--blue">Không QC</span>' : '')
						: r.status === 'pending_qc'
							? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Chờ QC</span>'
							: r.status === 'qc_passed'
								? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">QC đạt</span>'
								: r.status === 'qc_failed'
									? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">QC không đạt</span>'
									: r.status === 'approved'
										? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đã duyệt</span>'
										: '<span class="mk-wh-proto-pill mk-wh-proto-pill--draft">Nháp</span>';
				return (
					'<tr>' +
					'<td><strong>' +
					escapeHtml(r.code) +
					'</strong></td>' +
					'<td>' +
					escapeHtml(r.supplier) +
					'</td>' +
					'<td>' +
					escapeHtml(r.po) +
					'</td>' +
					'<td>' +
					escapeHtml(r.createdAt) +
					'</td>' +
					'<td>' +
					pill +
					'</td>' +
					'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-action="inbound-detail" data-code="' +
					escapeHtml(r.code) +
					'">Mở</button></td>' +
					'</tr>'
				);
			})
			.join('');
	}

	function renderQcQueue() {
		var st = getState();
		var tbody = qs('#mkWhProtoQcTbody');
		if (!tbody) return;
		var rows = st.receipts.filter(function (r) {
			return r.status === 'pending_qc';
		});
		tbody.innerHTML = rows
			.map(function (r) {
				var it = (r.items && r.items[0]) || {};
				return (
					'<tr>' +
					'<td><strong>' +
					escapeHtml(r.code) +
					'</strong></td>' +
					'<td>' +
					escapeHtml(r.supplier) +
					'</td>' +
					'<td>' +
					escapeHtml(it.name || '—') +
					' <span class="mk-wh-proto-muted">(' +
					escapeHtml(it.sku || '') +
					')</span></td>' +
					'<td>' +
					escapeHtml(it.lot || '—') +
					'</td>' +
					'<td>' +
					(it.mfg ? '<span class="mk-wh-proto-muted">NSX: ' + escapeHtml(it.mfg) + '</span><br/>' : '') +
					'HSD: ' +
					escapeHtml(it.exp || '—') +
					'</td>' +
					'<td>' +
					escapeHtml(it.qty || '—') +
					'</td>' +
					'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-action="qc-record" data-code="' +
					escapeHtml(r.code) +
					'">Ghi nhận QC</button></td>' +
					'</tr>'
				);
			})
			.join('');
		updateKpis();
	}

	function issueStatusPill(status) {
		var s = ISSUE_STATUS[status] || ISSUE_STATUS.draft;
		var cls =
			s.cls === 'ok'
				? 'mk-wh-proto-pill--ok'
				: s.cls === 'warn'
					? 'mk-wh-proto-pill--warn'
					: s.cls === 'blue'
						? 'mk-wh-proto-pill--blue'
						: s.cls === 'draft'
							? 'mk-wh-proto-pill--draft'
							: 'mk-wh-proto-pill--muted';
		return '<span class="mk-wh-proto-pill ' + cls + '">' + escapeHtml(s.label) + '</span>';
	}

	function renderOutbound() {
		var st = getState();
		var tbody = qs('#mkWhProtoOutboundTbody');
		if (!tbody) return;
		tbody.innerHTML = (st.issues || [])
			.map(function (i) {
				var it = (i.items && i.items[0]) || {};
				return (
					'<tr>' +
					'<td><strong>' +
					escapeHtml(i.code) +
					'</strong></td>' +
					'<td>' +
					outboundTypePill(i.outboundType || 'internal') +
					'</td>' +
					'<td>' +
					escapeHtml(i.customer) +
					'</td>' +
					'<td>' +
					escapeHtml(i.so) +
					'</td>' +
					'<td>' +
					escapeHtml(i.createdAt || '—') +
					'</td>' +
					'<td>' +
					issueStatusPill(i.status) +
					'</td>' +
					'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-action="outbound-detail" data-code="' +
					escapeHtml(i.code) +
					'">Chi tiết</button></td>' +
					'</tr>'
				);
			})
			.join('');
		updateKpis();
	}

	function renderStock() {
		var st = getState();
		var tbody = qs('#mkWhProtoStockTbody');
		if (!tbody) return;
		var inStock = (st.stock || []).filter(function (s) {
			return (Number(s.qty) || 0) > 0;
		});
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
		tbody.innerHTML = rows
			.map(function (s) {
				var days = daysUntil(s.expiry);
				var expLabel = days < 0 ? 'Quá hạn' : 'Còn ' + days + ' ngày';
				var hsdCls = 'mk-wh-proto-hsd';
				if (days < 0) {
					hsdCls += ' mk-wh-proto-hsd--expired';
				} else if (days < 90) {
					hsdCls += ' mk-wh-proto-hsd--soon';
				}
				var qtyCls = (Number(s.qty) || 0) < 50 ? ' mk-wh-proto-qty--low' : '';
				return (
					'<tr>' +
					'<td><strong>' +
					escapeHtml(s.sku) +
					'</strong></td>' +
					'<td>' +
					escapeHtml(s.name) +
					'</td>' +
					'<td>' +
					escapeHtml(s.lot) +
					'</td>' +
					'<td class="' +
					hsdCls +
					'">' +
					escapeHtml(s.expiry || '—') +
					' <span class="mk-wh-proto-muted">(' +
					escapeHtml(expLabel) +
					')</span></td>' +
					'<td class="mk-wh-proto-td-right">' +
					escapeHtml(fmtPrice(s.price)) +
					'</td>' +
					'<td class="mk-wh-proto-td-right">' +
					escapeHtml(s.location || '—') +
					'</td>' +
					'<td class="mk-wh-proto-td-right' +
					qtyCls +
					'"><strong>' +
					escapeHtml(s.qty) +
					'</strong></td>' +
					'</tr>'
				);
			})
			.join('');
		updateKpis();
	}

	function bindStockFilters() {
		['#mkWhProtoFilterHsd', '#mkWhProtoFilterName', '#mkWhProtoFilterPrice'].forEach(function (sel) {
			var el = qs(sel);
			if (!el) return;
			el.addEventListener('change', renderStock);
		});
		var resetBtn = qs('#mkWhProtoFilterReset');
		if (resetBtn) {
			resetBtn.addEventListener('click', function () {
				resetStockFilters();
				renderStock();
			});
		}
	}

	function renderAll() {
		renderInbounds();
		renderQcQueue();
		renderOutbound();
		renderStock();
	}

	function setActiveTab(key) {
		qsa('.mk-wh-proto-tab').forEach(function (b) {
			b.classList.toggle('is-active', b.getAttribute('data-tab') === key);
		});

		var title = qs('#mkWhProtoStageTitle');
		var btn = qs('#mkWhProtoCreateBtn');
		if (!title || !btn) return;

		var map = {
			inbound: { title: 'Danh sách phiếu nhập', btn: 'Tạo phiếu nhập', pane: '#mkWhProtoPaneInbound' },
			qc: { title: 'Hàng đợi QC', pane: '#mkWhProtoPaneQc', hideCreate: true },
			stock: { title: 'Tồn kho', pane: '#mkWhProtoPaneStock', hideCreate: true },
			outbound: { title: 'Danh sách phiếu xuất', btn: 'Tạo phiếu xuất', pane: '#mkWhProtoPaneOutbound' },
		};
		var meta = map[key] || map.inbound;
		title.textContent = meta.title;
		if (meta.hideCreate) {
			btn.classList.add('hide');
		} else {
			btn.classList.remove('hide');
			btn.textContent = meta.btn;
		}

		['#mkWhProtoPaneInbound', '#mkWhProtoPaneQc', '#mkWhProtoPaneStock', '#mkWhProtoPaneOutbound'].forEach(function (sel) {
			var el = qs(sel);
			if (!el) return;
			el.classList.toggle('hide', sel !== meta.pane);
		});
	}

	function setRole(role) {
		var badge = qs('#mkWhProtoRoleBadge');
		var hint = qs('#mkWhProtoRoleHint');
		var select = qs('#mkWhProtoRole');
		var btn = qs('#mkWhProtoCreateBtn');
		var permRole = qs('#mkWhProtoPermRole');
		var permItems = qs('#mkWhProtoPermItems');
		if (select && select.value !== role) select.value = role;

		var map = {
			qc: {
				badge: 'QC',
				hint: 'Quyền: Chỉnh sửa kết quả QC (Đạt/Không đạt) • Ghi chú kiểm tra',
				perms: 'Ghi nhận kết quả QC (Đạt/Không đạt) • Ghi chú kiểm tra',
			},
			stock: {
				badge: 'Thủ kho',
				hint: 'Quyền: Tạo/sửa phiếu nhập • Gửi QC • Soạn & giao hàng • Tạo phiếu xuất',
				perms: 'Tạo/sửa phiếu nhập • Gửi QC • Soạn & giao hàng • Tạo phiếu xuất',
			},
			manager: {
				badge: 'Quản lý kho',
				hint: 'Quyền: Duyệt phiếu xuất • Xem báo cáo tồn kho',
				perms: 'Duyệt phiếu xuất • Xem báo cáo tồn kho',
			},
		};
		var meta = map[role] || map.qc;
		if (badge) badge.textContent = meta.badge;
		if (hint) hint.textContent = meta.hint;
		if (permRole) permRole.textContent = meta.badge;
		if (permItems) permItems.textContent = meta.perms;

		// Chỉ thủ kho tạo phiếu nhập / xuất. Tab QC & tồn kho không có nút tạo (không có “phiếu QC”).
		var activeTab = 'inbound';
		var active = qs('.mk-wh-proto-tab.is-active');
		if (active) activeTab = active.getAttribute('data-tab') || 'inbound';
		var canCreate =
			role === 'stock' && (activeTab === 'inbound' || activeTab === 'outbound');
		if (!canCreate) {
			if (btn) {
				btn.classList.add('hide');
				btn.disabled = true;
				btn.classList.remove('is-disabled');
				btn.title =
					role !== 'stock'
						? 'Chỉ thủ kho được tạo phiếu nhập / xuất (UI demo).'
						: 'Tab này không tạo phiếu mới (UI demo).';
			}
			return;
		}
		if (btn) {
			btn.classList.remove('hide', 'is-disabled');
			btn.disabled = false;
			btn.title = '';
			btn.textContent = activeTab === 'outbound' ? 'Tạo phiếu xuất' : 'Tạo phiếu nhập';
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
			dialog.classList.toggle(
				'mk-wh-proto-modal__dialog--compact',
				opts.tabKey === 'inbound' || opts.tabKey === 'outbound-type' || opts.tabKey === 'outbound'
			);
			dialog.classList.toggle(
				'mk-wh-proto-modal__dialog--lux',
				opts.tabKey === 'inbound' || opts.tabKey === 'outbound-type' || opts.tabKey === 'outbound'
			);
		}

		title.textContent = opts.title || 'Tạo phiếu';
		if (submit) submit.textContent = opts.submitLabel || 'Tạo phiếu';

		fields.innerHTML = (opts.fields || [])
			.map(function (f) {
				var full = f.full ? ' mk-wh-proto-field--full' : '';
				var req = f.required ? ' *' : '';
				if (f.type === 'section') {
					return '<div class="mk-wh-proto-form-section' + full + '">' + escapeHtml(f.label || '') + '</div>';
				}
				if (f.type === 'hint') {
					return '<p class="mk-wh-proto-form-hint' + full + '">' + escapeHtml(f.text || '') + '</p>';
				}
				if (f.type === 'lines') {
					return (
						'<div class="mk-wh-proto-field mk-wh-proto-field--full">' +
						'<label>' +
						escapeHtml(f.label || 'Danh sách hàng') +
						'</label>' +
						'<div class="mk-wh-proto-lines mk-wh-proto-lines--catalog" data-mk-lines="1">' +
						'<div class="mk-wh-proto-lines__head">' +
						'<span class="mk-wh-proto-lines__ttl">Chọn sản phẩm từ tồn kho — SKU tự động điền</span>' +
						'<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost mk-wh-proto-lines__add" data-mk-lines-add="1">+ Thêm dòng</button>' +
						'</div>' +
						'<div class="mk-wh-proto-lines__tableWrap">' +
						'<table class="mk-wh-proto-lines__table" role="table">' +
						'<thead><tr>' +
						'<th class="mk-wh-proto-col-name">Tên hàng *</th>' +
						'<th class="mk-wh-proto-col-sku">SKU</th>' +
						'<th class="mk-wh-proto-col-lot">Lô *</th>' +
						'<th class="mk-wh-proto-col-qty">SL *</th>' +
						'<th class="mk-wh-proto-col-date">NSX</th>' +
						'<th class="mk-wh-proto-col-date">HSD</th>' +
						'<th style="width:44px;"></th>' +
						'</tr></thead>' +
						'<tbody data-mk-lines-body="1"></tbody>' +
						'</table>' +
						'</div>' +
						'</div>' +
						'</div>'
					);
				}
				if (f.type === 'checkbox') {
					return (
						'<div class="mk-wh-proto-field mk-wh-proto-field--check' +
						full +
						'">' +
						'<label class="mk-wh-proto-check">' +
						'<span class="mk-wh-proto-check__box">' +
						'<input class="mk-wh-proto-check__input" type="checkbox" name="' +
						escapeHtml(f.name) +
						'" value="1"' +
						(f.checked ? ' checked="checked"' : '') +
						' />' +
						'<span class="mk-wh-proto-check__visual" aria-hidden="true">' +
						'<svg class="mk-wh-proto-check__icon" width="12" height="12" viewBox="0 0 12 12" fill="none">' +
						'<path d="M2.2 6.1 4.8 8.7 9.8 3.3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>' +
						'</svg></span></span>' +
						'<span class="mk-wh-proto-check__text">' +
						'<span class="mk-wh-proto-check__label">' +
						escapeHtml(f.label || '') +
						'</span>' +
						(f.hint ? '<span class="mk-wh-proto-check__hint">' + escapeHtml(f.hint) + '</span>' : '') +
						'</span></label></div>'
					);
				}
				var input;
				if (f.type === 'select') {
					input =
						'<select name="' +
						f.name +
						'" ' +
						(f.required ? 'required' : '') +
						'>' +
						(f.options || [])
							.map(function (o) {
								var selected = o.selected ? ' selected="selected"' : '';
								return '<option value="' + escapeHtml(o.value) + '"' + selected + '>' + escapeHtml(o.label) + '</option>';
							})
							.join('') +
						'</select>';
				} else if (f.type === 'textarea') {
					input =
						'<textarea name="' +
						f.name +
						'" rows="3" ' +
						(f.required ? 'required' : '') +
						' placeholder="' +
						(f.placeholder || '') +
						'"></textarea>';
				} else {
					input =
						'<input type="' +
						(f.type || 'text') +
						'" name="' +
						f.name +
						'" ' +
						(f.required ? 'required' : '') +
						' placeholder="' +
						(f.placeholder || '') +
						'" />';
				}
				return '<div class="mk-wh-proto-field' + full + '"><label>' + f.label + req + '</label>' + input + '</div>';
			})
			.join('');

		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');

		// Inbound: initialize multi-line rows (product from stock catalog)
		if ((opts.tabKey || '') === 'inbound') {
			var bodyEl = form.querySelector('[data-mk-lines-body="1"]');
			var catalog = getStockProductCatalog();
			var linesHead = form.querySelector('.mk-wh-proto-lines__ttl');
			var addBtnInbound = form.querySelector('[data-mk-lines-add="1"]');

			function addRow(preset) {
				if (!bodyEl) return;
				var p = preset || {};
				var skuVal = p.sku || '';
				var tr =
					'<tr class="mk-wh-proto-lines__row" data-mk-line="1">' +
					'<td>' + productSelectHtml(catalog, skuVal) + '</td>' +
					'<td class="mk-wh-proto-col-sku"><input type="text" data-mk-line-sku="1" value="' + escapeHtml(skuVal) + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" placeholder="SKU" /></td>' +
					'<td class="mk-wh-proto-col-lot"><input type="text" data-mk-line-lot="1" value="' + escapeHtml(p.lot || '') + '" required placeholder="LOT-2605A" /></td>' +
					'<td class="mk-wh-proto-col-qty"><input type="number" min="1" step="1" data-mk-line-qty="1" value="' + escapeHtml(p.qty != null ? p.qty : '') + '" required placeholder="100" /></td>' +
					'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-mfg="1" value="' + escapeHtml(p.mfg || '') + '" /></td>' +
					'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-exp="1" value="' + escapeHtml(p.exp || '') + '" /></td>' +
					'<td><button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost mk-wh-proto-lines__del" data-mk-lines-del="1" title="Xóa dòng">×</button></td>' +
					'</tr>';
				bodyEl.insertAdjacentHTML('beforeend', tr);
			}

			if (!catalog.length) {
				if (linesHead) {
					linesHead.innerHTML = '<span class="mk-wh-proto-lines__warn">Chưa có sản phẩm trong tồn kho — cần nhập kho trước để chọn tên hàng.</span>';
				}
				if (addBtnInbound) addBtnInbound.disabled = true;
			} else {
				if (linesHead) linesHead.textContent = 'Chọn sản phẩm từ tồn kho — SKU tự động điền';
				if (bodyEl && !bodyEl.children.length) {
					addRow();
					addRow();
				}
				if (addBtnInbound) {
					addBtnInbound.disabled = false;
					addBtnInbound.onclick = function () { addRow(); };
				}
			}

			form.addEventListener('change', function (ev) {
				var t = ev.target;
				if (!t || t.getAttribute('data-mk-line-product') !== '1') return;
				var row = t.closest('[data-mk-line="1"]');
				var skuEl = row && row.querySelector('[data-mk-line-sku="1"]');
				if (skuEl) skuEl.value = t.value || '';
			});

			form.addEventListener(
				'click',
				function (ev) {
					var del = ev.target && ev.target.getAttribute && ev.target.getAttribute('data-mk-lines-del');
					if (del !== '1') return;
					ev.preventDefault();
					var row = ev.target.closest('[data-mk-line="1"]');
					if (row && bodyEl && bodyEl.children.length > 1) {
						row.remove();
					}
				},
				{ once: true }
			);
		}

		var sendQcCheckbox = form.querySelector('[name="sendQc"]');
		function syncInboundSubmitLabel() {
			if (!submit || !sendQcCheckbox || opts.tabKey !== 'inbound') return;
			submit.textContent = sendQcCheckbox.checked ? opts.submitLabel || 'Tạo phiếu' : 'Tạo & nhập kho';
		}
		if (sendQcCheckbox) {
			sendQcCheckbox.addEventListener('change', syncInboundSubmitLabel);
			syncInboundSubmitLabel();
		}

		form.onsubmit = function (e) {
			e.preventDefault();
			var fd = new FormData(form);
			var st = getState();
			var tabKey = opts.tabKey || 'inbound';

			if (tabKey === 'outbound-type') {
				var pickedType = String(fd.get('outboundType') || '');
				if (!pickedType || !OUTBOUND_TYPES[pickedType] || pickedType === 'sale') return;
				var pickerRole = qs('#mkWhProtoRole');
				openModal(modalSchema('outbound', pickerRole ? pickerRole.value : 'stock', pickedType));
				return;
			}

			if (tabKey === 'outbound') {
				var lotKey = String(fd.get('lotKey') || '');
				var parts = lotKey.split('|');
				var sku = parts[0];
				var lot = parts[1];
				var stockLot = findStockLot(sku, lot);
				var qtyOut = Number(fd.get('qty') || 0) || 0;
				if (!stockLot || !qtyOut || qtyOut <= 0) return;
				var outboundType = opts.outboundType || 'internal';
				var typeMeta = getOutboundTypeMeta(outboundType);
				var seqOut = st.nextOutboundSeq++;
				var codeOut = 'GIN-' + String(seqOut).padStart(4, '0');
				var nowOut = fmtNow();
				st.issues = st.issues || [];
				st.issues.unshift({
					code: codeOut,
					outboundType: outboundType,
					customer: String(fd.get('customer') || ''),
					so: String(fd.get('so') || '—'),
					status: 'draft',
					createdAt: nowOut,
					createdBy: 'Thủ kho Hà',
					items: [{ sku: stockLot.sku, name: stockLot.name, lot: stockLot.lot, qty: qtyOut }],
					timeline: [
						{
							at: nowOut,
							by: 'Thủ kho Hà',
							role: 'keeper',
							action: 'Tạo phiếu xuất — ' + typeMeta.short,
						},
					],
				});
				renderOutbound();
				closeModal();
				openDialog(outboundDialog(codeOut));
				return;
			}

			if (tabKey === 'stock') {
				st.stock = st.stock || [];
				st.stock.push({
					sku: String(fd.get('sku') || ''),
					name: String(fd.get('product') || ''),
					lot: String(fd.get('lot') || ''),
					expiry: String(fd.get('exp') || ''),
					qty: Number(fd.get('qty') || 0) || 0,
					location: 'A1-02',
					price: Number(fd.get('price') || 0) || 0,
				});
				renderStock();
				closeModal();
				return;
			}

			var seq = st.nextInboundSeq++;
			var code = 'GRN-' + String(seq);
			var supplier = fd.get('supplier') || 'NCC';
			var po = fd.get('po') || 'PO';
			var sendQc = fd.has('sendQc');
			var skipQc = !sendQc;
			var now = fmtNow();
			var items = [];
			var rows = Array.prototype.slice.call(form.querySelectorAll('[data-mk-line="1"]'));
			rows.forEach(function (row) {
				var productSel = row.querySelector('[data-mk-line-product="1"]');
				var skuIn = productSel ? String(productSel.value || '').trim() : '';
				var nameIn = '';
				if (productSel && productSel.selectedIndex > 0) {
					var opt = productSel.options[productSel.selectedIndex];
					nameIn = (opt && opt.getAttribute('data-name')) || '';
				}
				var lotIn = row.querySelector('[data-mk-line-lot="1"]') ? row.querySelector('[data-mk-line-lot="1"]').value.trim() : '';
				var qtyIn = row.querySelector('[data-mk-line-qty="1"]') ? Number(row.querySelector('[data-mk-line-qty="1"]').value || 0) || 0 : 0;
				var mfg = row.querySelector('[data-mk-line-mfg="1"]') ? row.querySelector('[data-mk-line-mfg="1"]').value : '';
				var exp = row.querySelector('[data-mk-line-exp="1"]') ? row.querySelector('[data-mk-line-exp="1"]').value : '';
				if (!skuIn || !nameIn || !lotIn || qtyIn <= 0) return;
				items.push({
					name: String(nameIn),
					sku: String(skuIn),
					lot: String(lotIn),
					mfg: String(mfg || ''),
					exp: String(exp || ''),
					qty: qtyIn,
					qc: skipQc ? 'skip' : 'none',
					needsQc: sendQc,
				});
			});
			if (!items.length) return;
			var receipt = {
				code: code,
				supplier: String(supplier),
				po: String(po),
				createdAt: now,
				needsQc: sendQc,
				status: skipQc ? 'stored' : 'pending_qc',
				items: items,
				timeline: skipQc
					? [
							{ at: now, by: 'Thủ kho Hà', role: 'keeper', action: 'Tạo phiếu nhập' },
							{
								at: now,
								by: 'Thủ kho Hà',
								role: 'keeper',
								action: 'Nhập thẳng tồn kho',
								note: 'Không gửi QC — cộng tồn ngay',
							},
						]
					: [
							{ at: now, by: 'Thủ kho Hà', role: 'keeper', action: 'Tạo phiếu nhập' },
							{ at: now, by: 'Thủ kho Hà', role: 'keeper', action: 'Gửi QC kiểm tra' },
						],
			};

			st.receipts.unshift(receipt);
			if (skipQc) {
				addStockFromReceipt(receipt);
			}
			renderAll();
			closeModal();
			openDialog(inboundDialog(code));
		};
	}

	function closeModal() {
		var modal = qs('#mkWhProtoModal');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
	}

	function openDialog(opts) {
		var dialog = qs('#mkWhProtoDialog');
		var title = qs('#mkWhProtoDialogTitle');
		var meta = qs('#mkWhProtoDialogMeta');
		var body = qs('#mkWhProtoDialogBody');
		if (!dialog || !title || !meta || !body) return;
		title.textContent = opts.title || 'Phiếu';
		meta.innerHTML = opts.metaHtml || '';
		body.innerHTML = opts.bodyHtml || '';
		dialog.classList.add('is-open');
		dialog.setAttribute('aria-hidden', 'false');
	}

	function closeDialog() {
		var dialog = qs('#mkWhProtoDialog');
		if (!dialog) return;
		dialog.classList.remove('is-open');
		dialog.setAttribute('aria-hidden', 'true');
	}

	function inboundDialog(code) {
		var st = getState();
		var rec = st.receipts.filter(function (r) {
			return r.code === code;
		})[0];
		if (!rec) rec = st.receipts[0];
		var isWait = rec.status === 'pending_qc';
		var isDraft = rec.status === 'draft';
		var isPassed = rec.status === 'qc_passed';
		var isFailed = rec.status === 'qc_failed';
		var isApproved = rec.status === 'approved';
		var isStored = rec.status === 'stored';

		var roleSel = qs('#mkWhProtoRole');
		var role = roleSel ? roleSel.value : 'qc';

		function roleBadge(label, roleKey) {
			if (roleKey === 'qc') return '<span class="mk-wh-proto-tag mk-wh-proto-tag--qc">' + label + '</span>';
			if (roleKey === 'manager') return '<span class="mk-wh-proto-tag mk-wh-proto-tag--green">' + label + '</span>';
			return '<span class="mk-wh-proto-tag mk-wh-proto-tag--blue">' + label + '</span>';
		}

		function statusPill() {
			if (isDraft) return '<span class="mk-wh-proto-pill mk-wh-proto-pill--draft">Nháp</span>';
			if (isWait) return '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Chờ QC</span>';
			if (isPassed) return '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">QC đạt</span>';
			if (isFailed) return '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">QC không đạt</span>';
			if (isApproved) return '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đã duyệt</span>';
			if (isStored) {
				if (!receiptNeedsQc(rec)) {
					return (
						'<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đã nhập kho</span> ' +
						'<span class="mk-wh-proto-pill mk-wh-proto-pill--blue">Không QC</span>'
					);
				}
				return '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đã nhập kho</span>';
			}
			return '';
		}

		var timelineHtml =
			'<div class="mk-wh-proto-timeline">' +
			(rec.timeline || [])
				.map(function (ev) {
					var label = ev.role === 'qc' ? 'QC' : ev.role === 'manager' ? 'Quản lý kho' : 'Thủ kho';
					return (
						'<div class="mk-wh-proto-timeline-item">' +
						'<strong>' +
						escapeHtml(ev.action) +
						'</strong>' +
						roleBadge(label, ev.role) +
						'<div class="mk-wh-proto-muted">' +
						escapeHtml((ev.by || '') + ' · ' + (ev.at || '')) +
						'</div>' +
						(ev.note ? '<div class="mk-wh-proto-quote">"' + escapeHtml(ev.note) + '"</div>' : '') +
						'</div>'
					);
				})
				.join('') +
			'</div>';

		var primaryActions = '';
		if (isDraft && role === 'stock' && receiptNeedsQc(rec)) {
			primaryActions =
				'<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="send-qc" data-code="' +
				escapeHtml(rec.code) +
				'">Gửi QC</button>' +
				'</div>';
		}
		if (isWait && role === 'qc') {
			primaryActions =
				'<div class="mk-wh-proto-dialog-section-title" style="margin-top:12px;">Ghi nhận kết quả QC</div>' +
				'<textarea class="mk-wh-proto-textarea" data-mk-qc-note="1" placeholder="Ghi chú kiểm tra (cảm quan, chứng từ, bao bì...)"></textarea>' +
				'<div class="mk-wh-proto-cta-row">' +
				'<button class="mk-wh-proto-cta mk-wh-proto-cta--pass" type="button" data-mk-action="qc-pass" data-code="' +
				escapeHtml(rec.code) +
				'"><span>✔</span> Đạt</button>' +
				'<button class="mk-wh-proto-cta mk-wh-proto-cta--fail" type="button" data-mk-action="qc-fail" data-code="' +
				escapeHtml(rec.code) +
				'"><span>✕</span> Không đạt</button>' +
				'</div>';
		}
		if (isPassed && role === 'manager') {
			primaryActions =
				'<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="mgr-approve" data-code="' +
				escapeHtml(rec.code) +
				'">Duyệt phiếu</button>' +
				'</div>';
		}
		if (isApproved && role === 'stock') {
			primaryActions =
				'<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="store" data-code="' +
				escapeHtml(rec.code) +
				'">Nhập kho</button>' +
				'</div>';
		}
		return {
			title: 'Phiếu nhập ' + rec.code,
			metaHtml: 'NCC: ' + escapeHtml(rec.supplier) + ' · PO: ' + escapeHtml(rec.po),
			bodyHtml:
				'<div style="margin-bottom:10px;">' + statusPill() + '</div>' +
				'<div class="mk-wh-proto-dialog-grid">' +
				'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Chi tiết hàng hóa</div>' +
				'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô / NSX / HSD</th><th>SL</th><th>QC</th></tr></thead><tbody>' +
				(rec.items || [])
					.map(function (it) {
						var qcPill =
							it.qc === 'pass'
								? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đạt</span>'
								: it.qc === 'fail'
									? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Không đạt</span>'
									: it.qc === 'skip'
										? '<span class="mk-wh-proto-pill mk-wh-proto-pill--blue">Không cần QC</span>'
										: '—';
						return (
							'<tr><td><strong>' +
							escapeHtml(it.name || '') +
							'</strong><div class="mk-wh-proto-muted">' +
							escapeHtml(it.sku || '') +
							'</div></td><td>' +
							escapeHtml(it.lot || '') +
							(it.mfg ? '<br/><span class="mk-wh-proto-muted">NSX: ' + escapeHtml(it.mfg) + '</span>' : '') +
							'<br/><span class="mk-wh-proto-muted">HSD: ' +
							escapeHtml(it.exp || '') +
							'</span></td><td>' +
							escapeHtml(it.qty) +
							'</td><td>' +
							qcPill +
							'</td></tr>'
						);
					})
					.join('') +
				'</tbody></table>' +
				primaryActions +
				'</div>' +
				'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
				timelineHtml +
				'</div>' +
				'</div>',
		};
	}

	function getProtoRole() {
		var roleSel = qs('#mkWhProtoRole');
		return roleSel ? roleSel.value : 'qc';
	}

	function findIssue(code) {
		return (getState().issues || []).filter(function (i) {
			return i.code === code;
		})[0];
	}

	function outboundDialog(code) {
		var issue = findIssue(code);
		if (!issue) {
			return { title: 'Phiếu xuất', metaHtml: '', bodyHtml: '<p class="mk-wh-proto-muted">Không tìm thấy phiếu.</p>' };
		}
		var role = getProtoRole();
		var stockWarn = false;
		var linesHtml = (issue.items || [])
			.map(function (l) {
				var inStock = findStockLot(l.sku, l.lot);
				var onHand = inStock ? Number(inStock.qty) : 0;
				if (!inStock || onHand < Number(l.qty)) stockWarn = true;
				return (
					'<tr><td><strong>' +
					escapeHtml(l.name) +
					'</strong><div class="mk-wh-proto-muted">' +
					escapeHtml(l.sku) +
					'</div></td><td>' +
					escapeHtml(l.lot) +
					'</td><td>' +
					escapeHtml(l.qty) +
					'</td><td>' +
					escapeHtml(onHand) +
					'</td></tr>'
				);
			})
			.join('');

		var timelineHtml =
			'<div class="mk-wh-proto-timeline">' +
			(issue.timeline || [])
				.map(function (ev) {
					var label = ev.role === 'manager' ? 'Quản lý kho' : ev.role === 'qc' ? 'QC' : 'Thủ kho';
					return (
						'<div class="mk-wh-proto-timeline-item"><strong>' +
						escapeHtml(ev.action) +
						'</strong><span class="mk-wh-proto-tag mk-wh-proto-tag--blue">' +
						escapeHtml(label) +
						'</span><div class="mk-wh-proto-muted">' +
						escapeHtml((ev.by || '') + ' · ' + (ev.at || '')) +
						'</div>' +
						(ev.note ? '<div class="mk-wh-proto-quote">"' + escapeHtml(ev.note) + '"</div>' : '') +
						'</div>'
					);
				})
				.join('') +
			'</div>';

		var primaryActions = '';
		if (issue.status === 'draft' && role === 'stock') {
			primaryActions =
				'<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-submit" data-code="' +
				escapeHtml(issue.code) +
				'">Gửi duyệt</button></div>';
		}
		if (issue.status === 'pending_approval' && role === 'manager') {
			primaryActions =
				'<div class="mk-wh-proto-dialog-section-title" style="margin-top:12px;">Lý do (nếu từ chối)</div>' +
				'<textarea class="mk-wh-proto-textarea" data-mk-reject-reason="1" placeholder="VD: vượt hạn mức tín dụng"></textarea>' +
				'<div class="mk-wh-proto-cta-row" style="margin-top:10px;">' +
				'<button class="mk-wh-proto-cta mk-wh-proto-cta--fail" type="button" data-mk-action="issue-reject" data-code="' +
				escapeHtml(issue.code) +
				'"><span>✕</span> Từ chối</button>' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-approve" data-code="' +
				escapeHtml(issue.code) +
				'"' +
				(stockWarn ? ' disabled title="Tồn không đủ"' : '') +
				'>Duyệt phiếu</button></div>';
		}
		if (issue.status === 'approved' && role === 'stock') {
			primaryActions =
				'<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-ship" data-code="' +
				escapeHtml(issue.code) +
				'"' +
				(stockWarn ? ' disabled title="Tồn không đủ"' : '') +
				'>Soạn &amp; giao hàng</button></div>';
		}

		var warnHtml = stockWarn
			? '<div class="mk-wh-proto-banner" style="margin:10px 0;background:#fff7ed;border-color:#fed7aa;">Cảnh báo: tồn kho không đủ cho ít nhất 1 dòng.</div>'
			: '';

		var typeMeta = getOutboundTypeMeta(issue.outboundType || 'internal');
		return {
			title: 'Phiếu xuất ' + issue.code,
			metaHtml:
				outboundTypePill(issue.outboundType || 'internal') +
				' · ' +
				escapeHtml(typeMeta.customerLabel) +
				': ' +
				escapeHtml(issue.customer) +
				' · ' +
				escapeHtml(typeMeta.soLabel) +
				': ' +
				escapeHtml(issue.so),
			bodyHtml:
				'<div style="margin-bottom:10px;">' +
				issueStatusPill(issue.status) +
				'</div>' +
				warnHtml +
				'<div class="mk-wh-proto-dialog-grid">' +
				'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Chi tiết xuất hàng</div>' +
				'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô</th><th>SL xuất</th><th>Tồn hiện tại</th></tr></thead><tbody>' +
				linesHtml +
				'</tbody></table>' +
				primaryActions +
				'</div>' +
				'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
				timelineHtml +
				'</div>' +
				'</div>',
		};
	}

	function qcDialog(code) {
		return inboundDialog(code);
	}

	function modalSchema(tabKey, role, outboundType) {
		// Keep schemas minimal but similar to prototype screenshot (UI-only).
		if (tabKey === 'outbound-type') {
			return {
				tabKey: 'outbound-type',
				title: 'Tạo phiếu xuất kho',
				submitLabel: 'Tiếp theo',
				fields: [
					{
						type: 'hint',
						full: true,
						text: 'Chọn loại xuất kho. Bước tiếp theo bạn điền chi tiết phiếu xuất.',
					},
					{
						name: 'outboundType',
						label: 'Xuất kho loại nào?',
						type: 'select',
						required: true,
						full: true,
						options: [
							{ value: 'internal', label: OUTBOUND_TYPES.internal.label, selected: true },
							{ value: 'transfer', label: OUTBOUND_TYPES.transfer.label },
							{ value: 'scrap', label: OUTBOUND_TYPES.scrap.label },
						],
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
					{ name: 'supplier', label: 'Nhà cung cấp', required: true, placeholder: '' },
					{ name: 'po', label: 'Mã PO', required: true, placeholder: '' },
					{ type: 'lines', name: 'lines', label: 'Danh sách hàng nhập', full: true },
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
		if (tabKey === 'stock') {
			return {
				title: 'Thêm SKU lưu kho',
				submitLabel: 'Thêm SKU',
				fields: [
					{ name: 'sku', label: 'SKU', required: true, placeholder: '' },
					{ name: 'product', label: 'Tên hàng', required: true, placeholder: '' },
					{ name: 'lot', label: 'Lô', required: true, placeholder: '' },
					{ name: 'exp', label: 'HSD', required: true, type: 'date', placeholder: '' },
					{ name: 'qty', label: 'Số lượng', required: true, type: 'number', full: true, placeholder: '' },
				],
			};
		}
		var lotOptions = (getState().stock || [])
			.filter(function (s) {
				return (Number(s.qty) || 0) > 0;
			})
			.map(function (s) {
				return {
					value: s.sku + '|' + s.lot,
					label: s.name + ' · ' + s.lot + ' · còn ' + s.qty,
				};
			});
		if (!lotOptions.length) {
			lotOptions = [{ value: '', label: '(Chưa có tồn — nhập kho trước)' }];
		}
		var outMeta = getOutboundTypeMeta(outboundType || 'internal');
		return {
			tabKey: 'outbound',
			outboundType: outboundType || 'internal',
			title: 'Tạo phiếu xuất — ' + outMeta.short,
			submitLabel: 'Tạo phiếu',
			fields: [
				{
					type: 'hint',
					full: true,
					text: 'Loại: ' + outMeta.label + '. Điền thông tin phiếu xuất bên dưới.',
				},
				{ name: 'customer', label: outMeta.customerLabel, required: true, full: true, placeholder: '' },
				{ name: 'so', label: outMeta.soLabel, full: true, placeholder: outMeta.soPlaceholder },
				{
					name: 'lotKey',
					label: 'Chọn lô hàng',
					type: 'select',
					required: true,
					full: true,
					options: lotOptions,
				},
				{ name: 'qty', label: 'Số lượng', required: true, type: 'number', full: true, placeholder: '' },
			],
		};
	}

	function openOutboundTypePicker() {
		openModal(modalSchema('outbound-type'));
	}

	function boot() {
		if (!qs('#mkWhPrototypeRoot')) return;
		bindStockFilters();
		qsa('.mk-wh-proto-tab').forEach(function (b) {
			b.addEventListener('click', function () {
				setActiveTab(b.getAttribute('data-tab'));
				var roleSel = qs('#mkWhProtoRole');
				setRole(roleSel ? roleSel.value : 'qc');
			});
		});
		var roleSel = qs('#mkWhProtoRole');
		if (roleSel) {
			roleSel.addEventListener('change', function () {
				setRole(roleSel.value);
			});
		}
		var btn = qs('#mkWhProtoCreateBtn');
		if (btn) {
			btn.addEventListener('click', function () {
				if (btn.disabled || btn.classList.contains('hide')) return;
				var active = qs('.mk-wh-proto-tab.is-active');
				var tabKey = active ? active.getAttribute('data-tab') : 'inbound';
				var role = roleSel ? roleSel.value : 'qc';
				if (role !== 'stock' || tabKey === 'qc' || tabKey === 'stock') return;
				if (tabKey === 'outbound') {
					openOutboundTypePicker();
					return;
				}
				var schema = modalSchema(tabKey, role);
				if (schema) openModal(schema);
			});
		}
		var modal = qs('#mkWhProtoModal');
		if (modal) {
			modal.addEventListener('click', function (e) {
				var target = e.target;
				if (target && target.getAttribute && target.getAttribute('data-mk-close') === '1') {
					closeModal();
				}
			});
		}

		// Dialog close + action delegation
		var dialog = qs('#mkWhProtoDialog');
		if (dialog) {
			dialog.addEventListener('click', function (e) {
				var t = e.target;
				if (t && t.getAttribute && t.getAttribute('data-mk-dialog-close') === '1') {
					closeDialog();
				}
			});
		}

		var root = qs('#mkWhPrototypeRoot');
		if (root) {
			root.addEventListener('click', function (e) {
				var t = e.target;
				if (!t) return;
				while (t && t !== root && !(t.getAttribute && t.getAttribute('data-mk-action'))) {
					t = t.parentElement;
				}
				if (!t || t === root) return;
				var action = t.getAttribute('data-mk-action');
				if (!action) return;

				if (action === 'inbound-detail') {
					e.preventDefault();
					openDialog(inboundDialog(t.getAttribute('data-code') || 'GRN-0002'));
					return;
				}
				if (action === 'outbound-detail') {
					e.preventDefault();
					openDialog(outboundDialog(t.getAttribute('data-code') || 'GIN-0001'));
					return;
				}
				if (action === 'qc-record') {
					e.preventDefault();
					openDialog(qcDialog(t.getAttribute('data-code') || 'GRN-0002'));
					return;
				}
				if (action === 'qc-pass' || action === 'qc-fail') {
					e.preventDefault();
					var st = getState();
					var code = t.getAttribute('data-code');
					var rec = st.receipts.filter(function (r) {
						return r.code === code;
					})[0];
					if (rec) {
						var noteEl = qs('[data-mk-qc-note="1"]');
						var note = noteEl ? String(noteEl.value || '') : '';
						rec.status = action === 'qc-pass' ? 'qc_passed' : 'qc_failed';
						(rec.items || []).forEach(function (it) {
							it.qc = action === 'qc-pass' ? 'pass' : 'fail';
						});
						rec.timeline = rec.timeline || [];
						rec.timeline.push({
							at: fmtNow(),
							by: 'QC Minh',
							role: 'qc',
							action: action === 'qc-pass' ? 'QC đạt' : 'QC không đạt',
							note: note || undefined,
						});
					}
					renderInbounds();
					renderQcQueue();
					openDialog(inboundDialog(code));
					return;
				}
				if (action === 'send-qc') {
					e.preventDefault();
					var st = getState();
					var code = t.getAttribute('data-code');
					var rec = st.receipts.filter(function (r) {
						return r.code === code;
					})[0];
					if (rec && receiptNeedsQc(rec)) {
						rec.status = 'pending_qc';
						rec.timeline = rec.timeline || [];
						rec.timeline.push({ at: fmtNow(), by: 'Thủ kho Hà', role: 'keeper', action: 'Gửi QC kiểm tra' });
					}
					renderInbounds();
					renderQcQueue();
					closeDialog();
					setActiveTab('qc');
					return;
				}
				if (action === 'mgr-approve') {
					e.preventDefault();
					var st = getState();
					var code = t.getAttribute('data-code');
					var rec = st.receipts.filter(function (r) {
						return r.code === code;
					})[0];
					if (rec) {
						rec.status = 'approved';
						rec.timeline = rec.timeline || [];
						rec.timeline.push({ at: fmtNow(), by: 'QL Tuấn', role: 'manager', action: 'Duyệt phiếu' });
					}
					renderInbounds();
					renderQcQueue();
					openDialog(inboundDialog(code));
					return;
				}
				if (action === 'store') {
					e.preventDefault();
					var codeStore = t.getAttribute('data-code');
					var recStore = getState().receipts.filter(function (r) {
						return r.code === codeStore;
					})[0];
					if (recStore) {
						recStore.status = 'stored';
						recStore.timeline = recStore.timeline || [];
						recStore.timeline.push({ at: fmtNow(), by: 'Thủ kho Hà', role: 'keeper', action: 'Đã nhập kho', note: 'Vị trí: A1-02' });
						addStockFromReceipt(recStore);
					}
					renderAll();
					openDialog(inboundDialog(codeStore));
					return;
				}
				if (action === 'issue-submit') {
					e.preventDefault();
					var issSub = findIssue(t.getAttribute('data-code'));
					if (issSub) {
						issSub.status = 'pending_approval';
						issSub.timeline = issSub.timeline || [];
						issSub.timeline.push({ at: fmtNow(), by: 'Thủ kho Hà', role: 'keeper', action: 'Gửi duyệt' });
					}
					renderOutbound();
					openDialog(outboundDialog(issSub ? issSub.code : ''));
					return;
				}
				if (action === 'issue-approve') {
					e.preventDefault();
					var issAp = findIssue(t.getAttribute('data-code'));
					if (issAp) {
						issAp.status = 'approved';
						issAp.timeline = issAp.timeline || [];
						issAp.timeline.push({ at: fmtNow(), by: 'QL Tuấn', role: 'manager', action: 'Duyệt phiếu' });
					}
					renderOutbound();
					openDialog(outboundDialog(issAp ? issAp.code : ''));
					return;
				}
				if (action === 'issue-reject') {
					e.preventDefault();
					var issRej = findIssue(t.getAttribute('data-code'));
					if (issRej) {
						var reasonEl = qs('[data-mk-reject-reason="1"]');
						var reason = reasonEl ? String(reasonEl.value || '').trim() : '';
						issRej.status = 'rejected';
						issRej.timeline = issRej.timeline || [];
						issRej.timeline.push({
							at: fmtNow(),
							by: 'QL Tuấn',
							role: 'manager',
							action: 'Từ chối phiếu',
							note: reason || 'Không nêu lý do',
						});
					}
					renderOutbound();
					openDialog(outboundDialog(issRej ? issRej.code : ''));
					return;
				}
				if (action === 'issue-ship') {
					e.preventDefault();
					var issShip = findIssue(t.getAttribute('data-code'));
					if (issShip && issShip.status === 'approved') {
						deductStockFromIssue(issShip);
						issShip.status = 'shipped';
						issShip.timeline = issShip.timeline || [];
						issShip.timeline.push({ at: fmtNow(), by: 'Thủ kho Hà', role: 'keeper', action: 'Soạn hàng & giao' });
					}
					renderAll();
					openDialog(outboundDialog(issShip ? issShip.code : ''));
					return;
				}
			});
		}

		renderAll();
		setActiveTab('inbound');
		setRole(roleSel ? roleSel.value : 'qc');
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

