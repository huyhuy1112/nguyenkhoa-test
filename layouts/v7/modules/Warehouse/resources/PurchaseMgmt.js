/**
 * Mua hàng CRM — Phiếu MH + Lịch sử (UI hiện đại).
 */
(function () {
	'use strict';

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function money(n) {
		var v = Math.round(Number(n) || 0);
		return v.toLocaleString('vi-VN');
	}

	function unwrap(res) {
		if (!res || typeof res !== 'object') {
			return res || {};
		}
		if (res.result && typeof res.result === 'object') {
			return Object.assign({ success: true }, res.result);
		}
		return res;
	}

	function apiPost(data) {
		var req = Object.assign({ module: 'Warehouse', action: 'WhMgmtApi' }, data || {});
		return new Promise(function (resolve, reject) {
			if (typeof app !== 'undefined' && app.request && app.request.post) {
				app.request.post({ data: req }).then(function (err, res) {
					if (err) {
						var em = err;
						if (em && typeof em === 'object') {
							em = em.message || em.statusText || JSON.stringify(em);
						}
						reject(String(em || 'Lỗi mạng'));
						return;
					}
					var body = unwrap(res);
					if (body && (body.success === false || body.error)) {
						var msg = body.error || body.message || 'Lỗi API';
						if (msg && typeof msg === 'object' && msg.message) {
							msg = msg.message;
						}
						reject(String(msg));
						return;
					}
					resolve(body || {});
				});
				return;
			}
			reject('Thiếu app.request');
		});
	}

	function statusLabel(st) {
		var s = String(st || '').toLowerCase();
		if (s === 'draft') return 'Lưu tạm';
		if (s === 'pending_qc') return 'Chờ QC';
		if (s === 'stored') return 'Đã nhập kho';
		return st || '—';
	}

	function showToast(el, text, isErr) {
		if (!el) return;
		if (!text) {
			el.hidden = true;
			el.textContent = '';
			return;
		}
		el.hidden = false;
		el.textContent = text;
		el.className = 'mk-purchase-toast ' + (isErr ? 'is-error' : 'is-ok');
		clearTimeout(el._mkTimer);
		el._mkTimer = setTimeout(function () {
			el.hidden = true;
		}, 4500);
	}

	function emptyLinesHtml() {
		return '<tr class="mk-purchase-empty-row"><td colspan="7">' +
			'<div class="mk-purchase-empty">' +
			'<div class="mk-purchase-empty__icon" aria-hidden="true">' +
			'<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>' +
			'</div>' +
			'<p class="mk-purchase-empty__title">Chưa có dòng hàng</p>' +
			'<p class="mk-purchase-empty__sub">Gõ mã hoặc tên sản phẩm ở ô tìm kiếm rồi chọn để thêm.</p>' +
			'</div></td></tr>';
	}

	/* ---------- Create ---------- */
	function initCreate() {
		var root = document.getElementById('mkPurchaseCreateRoot');
		if (!root) return;

		var lines = [];
		var catalog = Array.isArray(window.MK_WH_PRODUCT_CATALOG) ? window.MK_WH_PRODUCT_CATALOG : [];
		var whSelect = document.getElementById('mkPurchaseWh');
		var vendorQ = document.getElementById('mkPurchaseVendorQ');
		var vendorId = document.getElementById('mkPurchaseVendorId');
		var supplier = document.getElementById('mkPurchaseSupplier');
		var vendorSuggest = document.getElementById('mkPurchaseVendorSuggest');
		var productQ = document.getElementById('mkPurchaseProductQ');
		var productSuggest = document.getElementById('mkPurchaseProductSuggest');
		var body = document.getElementById('mkPurchaseLinesBody');
		var msg = document.getElementById('mkPurchaseMsg');
		var lineCountEl = document.getElementById('mkPurchaseLineCount');
		var vendorTimer = null;
		var canWrite = String(root.getAttribute('data-can-write') || '0') === '1';

		function setMsg(text, isErr) {
			showToast(msg, text, !!isErr);
		}

		function loadWarehouses() {
			var state = window.MK_WH_DB_STATE || {};
			var list = Array.isArray(state.warehouses) ? state.warehouses : [];
			whSelect.innerHTML = '';
			list.filter(function (w) {
				return String(w.status || 'active') !== 'archived';
			}).forEach(function (w) {
				var opt = document.createElement('option');
				opt.value = w.id || w.code;
				opt.textContent = (w.code || w.id) + ' — ' + (w.name || '');
				whSelect.appendChild(opt);
			});
			if (!whSelect.options.length) {
				var empty = document.createElement('option');
				empty.value = '';
				empty.textContent = 'Chưa có kho — tạo kho trước';
				whSelect.appendChild(empty);
			}
		}

		function renderLines() {
			if (lineCountEl) {
				lineCountEl.textContent = lines.length + ' mặt hàng';
			}
			if (!lines.length) {
				body.innerHTML = emptyLinesHtml();
				updateTotals();
				return;
			}
			body.innerHTML = lines.map(function (ln, idx) {
				var amount = (Number(ln.qty) || 0) * (Number(ln.price) || 0);
				return '<tr data-idx="' + idx + '">' +
					'<td><span class="mk-purchase-code">' + esc(ln.sku || '—') + '</span></td>' +
					'<td>' + esc(ln.name) + '</td>' +
					'<td><input class="mk-purchase-cell" data-f="lot" value="' + esc(ln.lot || '') + '" placeholder="LOT-…" /></td>' +
					'<td class="is-num"><input class="mk-purchase-cell mk-purchase-cell--num" data-f="qty" type="number" min="0" step="1" value="' + esc(ln.qty) + '" /></td>' +
					'<td class="is-num"><input class="mk-purchase-cell mk-purchase-cell--num" data-f="price" type="number" min="0" step="1000" value="' + esc(ln.price) + '" /></td>' +
					'<td class="is-num">' + money(amount) + '</td>' +
					'<td class="is-action"><button type="button" class="mk-purchase-remove" data-idx="' + idx + '" title="Xóa">×</button></td>' +
					'</tr>';
			}).join('');
			updateTotals();
		}

		function updateTotals() {
			var sub = 0;
			lines.forEach(function (ln) {
				sub += (Number(ln.qty) || 0) * (Number(ln.price) || 0);
			});
			var disc = Number(document.getElementById('mkPurchaseDiscount').value) || 0;
			document.getElementById('mkPurchaseSubtotal').textContent = money(sub);
			document.getElementById('mkPurchaseDiscountLbl').textContent = money(disc);
			document.getElementById('mkPurchaseDue').textContent = money(Math.max(0, sub - disc));
		}

		body.addEventListener('input', function (e) {
			var t = e.target;
			if (!t.classList.contains('mk-purchase-cell')) return;
			var tr = t.closest('tr');
			var idx = tr ? Number(tr.getAttribute('data-idx')) : -1;
			if (idx < 0 || !lines[idx]) return;
			var f = t.getAttribute('data-f');
			if (f === 'lot') lines[idx].lot = t.value;
			if (f === 'qty') lines[idx].qty = Number(t.value) || 0;
			if (f === 'price') lines[idx].price = Number(t.value) || 0;
			updateTotals();
			if (f === 'qty' || f === 'price') {
				var amtCell = tr.querySelectorAll('td')[5];
				if (amtCell) {
					amtCell.textContent = money((Number(lines[idx].qty) || 0) * (Number(lines[idx].price) || 0));
				}
			}
		});

		body.addEventListener('click', function (e) {
			var btn = e.target.closest('.mk-purchase-remove');
			if (!btn) return;
			var idx = Number(btn.getAttribute('data-idx'));
			lines.splice(idx, 1);
			renderLines();
		});

		document.getElementById('mkPurchaseDiscount').addEventListener('input', updateTotals);

		function searchVendors(q) {
			apiPost({ mode: 'search_vendors', q: q }).then(function (res) {
				var vendors = (res && res.vendors) || [];
				if (!vendors.length) {
					vendorSuggest.hidden = true;
					vendorSuggest.innerHTML = '';
					return;
				}
				vendorSuggest.innerHTML = vendors.map(function (v) {
					return '<button type="button" class="mk-purchase-suggest__item" data-id="' + esc(v.id) + '" data-name="' + esc(v.name) + '">' +
						'<strong>' + esc(v.name) + '</strong>' +
						'<span>' + esc(v.code || '') + (v.phone ? ' · ' + esc(v.phone) : '') + '</span>' +
						'</button>';
				}).join('');
				vendorSuggest.hidden = false;
			}).catch(function () {
				vendorSuggest.hidden = true;
			});
		}

		vendorQ.addEventListener('input', function () {
			vendorId.value = '';
			clearTimeout(vendorTimer);
			var q = vendorQ.value.trim();
			if (q.length < 1) {
				vendorSuggest.hidden = true;
				return;
			}
			vendorTimer = setTimeout(function () { searchVendors(q); }, 250);
		});

		vendorSuggest.addEventListener('click', function (e) {
			var btn = e.target.closest('.mk-purchase-suggest__item');
			if (!btn) return;
			vendorId.value = btn.getAttribute('data-id') || '';
			supplier.value = btn.getAttribute('data-name') || '';
			vendorQ.value = btn.getAttribute('data-name') || '';
			vendorSuggest.hidden = true;
		});

		function showProductSuggest(q) {
			q = String(q || '').toLowerCase().trim();
			var hits = catalog.filter(function (p) {
				var name = String(p.name || p.productsservicesname || '').toLowerCase();
				var sku = String(p.sku || p.code || '').toLowerCase();
				return !q || name.indexOf(q) >= 0 || sku.indexOf(q) >= 0;
			}).slice(0, 12);
			if (!hits.length) {
				productSuggest.hidden = true;
				productSuggest.innerHTML = '';
				return;
			}
			productSuggest.innerHTML = hits.map(function (p) {
				var id = p.id || p.productsservicesid || p.product_id || 0;
				var name = p.name || p.productsservicesname || '';
				var sku = p.sku || p.code || '';
				var price = p.price || p.unit_price || 0;
				return '<button type="button" class="mk-purchase-suggest__item" data-id="' + esc(id) + '" data-name="' + esc(name) + '" data-sku="' + esc(sku) + '" data-price="' + esc(price) + '">' +
					'<strong>' + esc(name) + '</strong><span>' + esc(sku) + (price ? ' · ' + money(price) : '') + '</span></button>';
			}).join('');
			productSuggest.hidden = false;
		}

		productQ.addEventListener('input', function () {
			showProductSuggest(productQ.value);
		});
		productQ.addEventListener('focus', function () {
			showProductSuggest(productQ.value);
		});

		function addProductFromBtn(btn) {
			var id = Number(btn.getAttribute('data-id') || 0);
			var name = btn.getAttribute('data-name') || '';
			var sku = btn.getAttribute('data-sku') || '';
			var price = Number(btn.getAttribute('data-price') || 0);
			if (!name) return;
			var existing = lines.find(function (ln) { return ln.product_id === id && id > 0; });
			if (existing) {
				existing.qty = (Number(existing.qty) || 0) + 1;
			} else {
				lines.push({
					product_id: id,
					sku: sku,
					name: name,
					lot: '',
					qty: 1,
					price: price
				});
			}
			productQ.value = '';
			productSuggest.hidden = true;
			renderLines();
		}

		productSuggest.addEventListener('click', function (e) {
			var btn = e.target.closest('.mk-purchase-suggest__item');
			if (btn) addProductFromBtn(btn);
		});

		document.getElementById('mkPurchaseAddProductBtn').addEventListener('click', function () {
			var first = productSuggest.querySelector('.mk-purchase-suggest__item');
			if (first) {
				addProductFromBtn(first);
				return;
			}
			showProductSuggest(productQ.value);
			setMsg('Chọn một sản phẩm từ gợi ý.', true);
		});

		function buildPayload(asDraft) {
			var wh = (whSelect.value || '').trim();
			var name = (supplier.value || vendorQ.value || '').trim();
			if (!wh) throw new Error('Chọn kho nhận.');
			if (!name) throw new Error('Chọn hoặc nhập nhà cung cấp.');
			if (!lines.length) throw new Error('Thêm ít nhất một dòng hàng.');
			var clean = lines.filter(function (ln) {
				return (Number(ln.qty) || 0) > 0 && String(ln.name || '').trim() !== '';
			}).map(function (ln) {
				return {
					product_id: ln.product_id || 0,
					sku: ln.sku || '',
					name: ln.name,
					lot: ln.lot || '',
					qty: Number(ln.qty) || 0,
					price: Number(ln.price) || 0
				};
			});
			if (!clean.length) throw new Error('Số lượng dòng hàng phải > 0.');
			return {
				warehouse: wh,
				whId: wh,
				supplier: name,
				vendorId: Number(vendorId.value || 0) || 0,
				poRef: (document.getElementById('mkPurchasePoRef').value || '').trim(),
				discount: Number(document.getElementById('mkPurchaseDiscount').value) || 0,
				paidAmount: Number(document.getElementById('mkPurchasePaid').value) || 0,
				paymentNote: (document.getElementById('mkPurchasePayNote').value || '').trim(),
				asDraft: !!asDraft,
				lines: clean
			};
		}

		function submit(asDraft) {
			if (!canWrite) {
				setMsg('Bạn không có quyền tạo phiếu.', true);
				return;
			}
			var payload;
			try {
				payload = buildPayload(asDraft);
			} catch (e) {
				setMsg(e.message || String(e), true);
				return;
			}
			setMsg(asDraft ? 'Đang lưu tạm…' : 'Đang hoàn thành…');
			apiPost({
				mode: 'save_purchase',
				whId: payload.whId,
				payload: JSON.stringify(payload)
			}).then(function (res) {
				var code = (res && res.code) || '';
				var st = (res && res.status) || '';
				setMsg(
					(asDraft ? 'Đã lưu tạm ' : 'Đã hoàn thành ') + code +
					(st ? ' · ' + statusLabel(st) : '')
				);
				lines = [];
				renderLines();
				document.getElementById('mkPurchasePoRef').value = '';
			}).catch(function (err) {
				setMsg(typeof err === 'string' ? err : (err.message || 'Không lưu được phiếu.'), true);
			});
		}

		document.getElementById('mkPurchaseDraftBtn').addEventListener('click', function () { submit(true); });
		document.getElementById('mkPurchaseCompleteBtn').addEventListener('click', function () { submit(false); });

		document.addEventListener('click', function (e) {
			if (!vendorSuggest.contains(e.target) && e.target !== vendorQ) {
				vendorSuggest.hidden = true;
			}
			if (!productSuggest.contains(e.target) && e.target !== productQ) {
				productSuggest.hidden = true;
			}
		});

		loadWarehouses();
		renderLines();
	}

	/* ---------- History ---------- */
	function initHistory() {
		var root = document.getElementById('mkPurchaseHistoryRoot');
		if (!root) return;
		var body = document.getElementById('mkPurchaseHistBody');
		var msg = document.getElementById('mkPurchaseHistMsg');
		var qInput = document.getElementById('mkPurchaseHistQ');
		var kpiRoot = document.getElementById('mkPurchaseHistKpi');
		var status = 'all';
		var timer = null;
		var allRows = [];

		function setMsg(text, isErr) {
			showToast(msg, text, !!isErr);
		}

		function updateKpi(rows) {
			if (!kpiRoot) return;
			var counts = { all: rows.length, draft: 0, pending_qc: 0, stored: 0 };
			rows.forEach(function (r) {
				var st = String(r.status || '');
				if (counts[st] != null) counts[st] += 1;
			});
			Array.prototype.forEach.call(kpiRoot.querySelectorAll('[data-kpi]'), function (el) {
				var key = el.getAttribute('data-kpi');
				el.textContent = String(counts[key] != null ? counts[key] : 0);
			});
		}

		function render(rows) {
			if (!rows.length) {
				body.innerHTML = '<tr class="mk-purchase-empty-row"><td colspan="9">' +
					'<div class="mk-purchase-empty">' +
					'<p class="mk-purchase-empty__title">Chưa có phiếu mua hàng</p>' +
					'<p class="mk-purchase-empty__sub">Tạo phiếu MH mới để bắt đầu nhập hàng vào kho.</p>' +
					'</div></td></tr>';
				return;
			}
			body.innerHTML = rows.map(function (r) {
				var st = String(r.status || '');
				var actions = '<a class="mk-purchase-link" href="index.php?module=Warehouse&amp;view=WhDetail&amp;app=INVENTORY&amp;whId=' + encodeURIComponent(r.warehouse || '') + '">Mở kho</a>';
				if (st === 'draft') {
					actions += ' <button type="button" class="mk-purchase-btn mk-purchase-btn--primary mk-purchase-complete-btn" data-code="' + esc(r.code) + '" data-wh="' + esc(r.warehouse) + '">Hoàn thành</button>';
				}
				var created = r.createdAt ? new Date(r.createdAt).toLocaleString('vi-VN') : '—';
				return '<tr>' +
					'<td><span class="mk-purchase-code">' + esc(r.code) + '</span></td>' +
					'<td>' + esc(r.poRef || '—') + '</td>' +
					'<td>' + esc(r.supplier || '—') + '</td>' +
					'<td>' + esc(r.warehouse || '—') + '</td>' +
					'<td><span class="mk-purchase-badge mk-purchase-badge--' + esc(st) + '">' + esc(statusLabel(st)) + '</span></td>' +
					'<td class="is-num">' + esc(r.lineCount || 0) + '</td>' +
					'<td class="is-num">' + money(r.amount || 0) + '</td>' +
					'<td>' + esc(created) + '</td>' +
					'<td class="is-action">' + actions + '</td>' +
					'</tr>';
			}).join('');
		}

		function load() {
			body.innerHTML = '<tr class="mk-purchase-empty-row"><td colspan="9"><div class="mk-purchase-empty"><p class="mk-purchase-empty__title">Đang tải…</p></div></td></tr>';
			apiPost({
				mode: 'list_purchases',
				q: (qInput.value || '').trim(),
				status: 'all'
			}).then(function (res) {
				allRows = (res && res.receipts) || [];
				updateKpi(allRows);
				var filtered = allRows;
				if (status !== 'all') {
					filtered = allRows.filter(function (r) {
						return String(r.status || '') === status;
					});
				}
				render(filtered);
			}).catch(function (err) {
				body.innerHTML = '<tr class="mk-purchase-empty-row"><td colspan="9"><div class="mk-purchase-empty"><p class="mk-purchase-empty__title">Không tải được danh sách</p></div></td></tr>';
				setMsg(typeof err === 'string' ? err : 'Lỗi tải lịch sử.', true);
			});
		}

		document.getElementById('mkPurchaseHistFilters').addEventListener('click', function (e) {
			var btn = e.target.closest('[data-status]');
			if (!btn) return;
			Array.prototype.forEach.call(document.querySelectorAll('#mkPurchaseHistFilters .mk-purchase-filter'), function (b) {
				b.classList.toggle('is-active', b === btn);
			});
			status = btn.getAttribute('data-status') || 'all';
			var filtered = allRows;
			if (status !== 'all') {
				filtered = allRows.filter(function (r) {
					return String(r.status || '') === status;
				});
			}
			if (!allRows.length) {
				load();
				return;
			}
			render(filtered);
		});

		qInput.addEventListener('input', function () {
			clearTimeout(timer);
			timer = setTimeout(load, 300);
		});

		body.addEventListener('click', function (e) {
			var btn = e.target.closest('.mk-purchase-complete-btn');
			if (!btn) return;
			var code = btn.getAttribute('data-code');
			var wh = btn.getAttribute('data-wh');
			btn.disabled = true;
			setMsg('Đang hoàn thành ' + code + '…');
			apiPost({
				mode: 'complete_purchase',
				whId: wh,
				code: code
			}).then(function () {
				setMsg('Đã hoàn thành ' + code + ' — tồn kho đã cập nhật.');
				load();
			}).catch(function (err) {
				btn.disabled = false;
				setMsg(typeof err === 'string' ? err : 'Không hoàn thành được.', true);
			});
		});

		load();
	}

	document.addEventListener('DOMContentLoaded', function () {
		initCreate();
		initHistory();
	});
})();
