/**
 * Nhập hàng + Nhà cung cấp — IA/UI kiểu Kiot.
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
		return Math.round(Number(n) || 0).toLocaleString('vi-VN');
	}

	function unwrap(res) {
		if (!res || typeof res !== 'object') return res || {};
		if (res.result && typeof res.result === 'object') {
			return Object.assign({ success: true }, res.result);
		}
		return res;
	}

	function apiPost(data) {
		var req = Object.assign({ module: 'Warehouse', action: 'WhMgmtApi' }, data || {});
		return new Promise(function (resolve, reject) {
			if (typeof app === 'undefined' || !app.request || !app.request.post) {
				reject('Thiếu app.request');
				return;
			}
			app.request.post({ data: req }).then(function (err, res) {
				if (err) {
					var em = err && typeof err === 'object' ? (err.message || err.statusText) : err;
					reject(String(em || 'Lỗi mạng'));
					return;
				}
				var body = unwrap(res);
				if (body && (body.success === false || body.error)) {
					var msg = body.error || body.message || 'Lỗi API';
					if (msg && typeof msg === 'object') msg = msg.message || JSON.stringify(msg);
					reject(String(msg));
					return;
				}
				resolve(body || {});
			});
		});
	}

	function statusLabel(st) {
		var s = String(st || '').toLowerCase();
		if (s === 'draft') return 'Phiếu tạm';
		if (s === 'pending_qc') return 'Chờ QC';
		if (s === 'stored') return 'Đã nhập hàng';
		return st || '—';
	}

	function paintMisaChip(chip, misa) {
		if (!chip) return;
		misa = misa || {};
		chip.hidden = !misa.label;
		var chipText = 'MISA: ' + (misa.label || '');
		if (misa.refno) chipText += ' · ' + misa.refno;
		if (misa.note) chipText += ' · ' + misa.note;
		chip.textContent = chipText;
		chip.className = 'mk-purchase-misa' + (misa.state ? (' is-' + misa.state) : '');
	}

	function openPurchaseDialog(opts) {
		opts = opts || {};
		return new Promise(function (resolve) {
			var old = document.getElementById('mkPurchaseMisaDialog');
			if (old && old.parentNode) old.parentNode.removeChild(old);
			var root = document.createElement('div');
			root.id = 'mkPurchaseMisaDialog';
			root.className = 'mk-purchase-dialog' + (opts.tone ? (' is-' + opts.tone) : '');
			root.innerHTML =
				'<div class="mk-purchase-dialog__card" role="dialog" aria-modal="true">' +
				'<h3></h3><p class="mk-purchase-dialog__text"></p>' +
				(opts.hint ? '<p class="mk-purchase-dialog__hint"></p>' : '') +
				'<div class="mk-purchase-dialog__actions">' +
				(opts.cancelLabel ? '<button type="button" class="mk-purchase-dialog__cancel"></button>' : '') +
				'<button type="button" class="mk-purchase-dialog__ok"></button>' +
				'</div></div>';
			root.querySelector('h3').textContent = opts.title || '';
			root.querySelector('.mk-purchase-dialog__text').textContent = opts.text || '';
			if (opts.hint) root.querySelector('.mk-purchase-dialog__hint').textContent = opts.hint;
			var okBtn = root.querySelector('.mk-purchase-dialog__ok');
			okBtn.textContent = opts.okLabel || 'Đóng';
			var cancelBtn = root.querySelector('.mk-purchase-dialog__cancel');
			if (cancelBtn) cancelBtn.textContent = opts.cancelLabel;
			function close(ok) {
				document.removeEventListener('keydown', onKey);
				if (root.parentNode) root.parentNode.removeChild(root);
				resolve(!!ok);
			}
			function onKey(e) {
				if (e.key === 'Escape') close(false);
			}
			root.addEventListener('click', function (e) {
				if (e.target === root) close(false);
			});
			if (cancelBtn) cancelBtn.addEventListener('click', function () { close(false); });
			okBtn.addEventListener('click', function () { close(true); });
			document.addEventListener('keydown', onKey);
			document.body.appendChild(root);
			okBtn.focus();
		});
	}

	function showToast(el, text, isErr) {
		if (!el) return;
		if (!text) { el.hidden = true; el.textContent = ''; return; }
		el.hidden = false;
		el.textContent = text;
		el.className = 'mk-purchase-toast ' + (isErr ? 'is-error' : 'is-ok');
		clearTimeout(el._t);
		el._t = setTimeout(function () { el.hidden = true; }, 4500);
	}

	function detailUrl(code, wh) {
		return 'index.php?module=Warehouse&view=PurchaseDetail&app=INVENTORY&code=' +
			encodeURIComponent(code || '') + '&whId=' + encodeURIComponent(wh || '');
	}

	/** Render chi tiết phiếu vào một bộ DOM (trang full hoặc panel phải). */
	function paintPurchaseReceipt(ids, r, opts) {
		opts = opts || {};
		var code = r.code || opts.code || '—';
		var created = r.createdAt ? new Date(r.createdAt).toLocaleString('vi-VN') : '—';
		if (ids.code) ids.code.textContent = code;
		if (ids.status) {
			ids.status.textContent = statusLabel(r.status);
			ids.status.className = 'mk-purchase-badge mk-purchase-badge--' + String(r.status || '');
		}
		if (ids.meta) {
			ids.meta.textContent =
				created + (r.vendorCode ? ' · ' + r.vendorCode : '') + (r.supplier ? ' · ' + r.supplier : '');
		}
		if (ids.wh) {
			ids.wh.innerHTML = 'Kho: <strong>' + esc(r.warehouse || '—') + '</strong>';
		}
		if (ids.info) {
			ids.info.innerHTML =
				'<div><span>Người tạo</span><strong>' + esc(r.createdBy || '—') + '</strong></div>' +
				'<div><span>Ngày nhập</span><strong>' + esc(created) + '</strong></div>' +
				'<div><span>Nhà cung cấp</span><strong>' + esc(r.supplier || '—') + '</strong></div>' +
				'<div><span>Tham chiếu</span><strong>' + esc(r.poRef || '—') + '</strong></div>';
		}
		var lines = r.lines || [];
		if (ids.lines) {
			if (!lines.length) {
				ids.lines.innerHTML = '<tr><td colspan="5">Không có dòng hàng.</td></tr>';
			} else {
				ids.lines.innerHTML = lines.map(function (ln) {
					var amt = (Number(ln.qty) || 0) * (Number(ln.unit_price) || 0);
					return '<tr><td>' + esc(ln.sku || '—') + '</td><td>' + esc(ln.name || '—') + '</td>' +
						'<td class="is-num">' + esc(ln.qty) + '</td><td class="is-num">' + money(ln.unit_price || 0) + '</td>' +
						'<td class="is-num">' + money(amt) + '</td></tr>';
				}).join('');
			}
		}
		if (ids.note) {
			ids.note.textContent =
				(r.paymentNote || r.note) ? ('Ghi chú: ' + (r.paymentNote || r.note)) : 'Không có ghi chú';
		}
		if (ids.totals) {
			ids.totals.innerHTML =
				'<div class="row"><span>Số lượng mặt hàng</span><strong>' + esc(r.lineCount || 0) + '</strong></div>' +
				'<div class="row"><span>Tổng tiền hàng</span><strong>' + money(r.amount || 0) + '</strong></div>' +
				'<div class="row"><span>Giảm giá</span><strong>' + money(r.discount || 0) + '</strong></div>' +
				'<div class="row total"><span>Tổng cộng</span><strong>' + money(Math.max(0, (r.amount || 0) - (r.discount || 0))) + '</strong></div>' +
				'<div class="row"><span>Tiền đã trả NCC</span><strong>' + money(r.paidAmount || 0) + '</strong></div>' +
				'<div class="row"><span>Cần trả NCC</span><strong>' + money(r.dueAmount || 0) + '</strong></div>';
		}
		if (ids.actions) {
			ids.actions.innerHTML = '';
			var misa = r.misa || {};
			var chip = document.createElement('div');
			paintMisaChip(chip, misa);
			ids.actions.appendChild(chip);
			var misaBtn = document.createElement('button');
			misaBtn.type = 'button';
			misaBtn.className = 'mk-kiot-btn mk-kiot-btn--ghost';
			misaBtn.textContent = 'Chuyển qua MISA';
			misaBtn.addEventListener('click', function () {
				if (misa.state === 'published' || misa.refno) {
					openPurchaseDialog({
						title: 'Đơn mua hàng',
						text: misa.refno
							? ('Phiếu đã vào Đơn mua hàng. ' + misa.refno)
							: 'Phiếu này đã vào Đơn mua hàng trên MISA.',
						okLabel: 'Đóng',
						tone: 'ok'
					});
					return;
				}
				openPurchaseDialog({
					title: 'Chuyển qua MISA',
					text: 'Gửi phiếu này sang MISA thành Đơn mua hàng?',
					hint: 'Phiếu sẽ vào AMIS → Mua hàng → Đơn mua hàng.',
					cancelLabel: 'Huỷ',
					okLabel: 'Gửi'
				}).then(function (ok) {
					if (!ok) return;
					misaBtn.disabled = true;
					apiPost({ mode: 'transfer_purchase_misa', code: r.code, whId: r.warehouse || '' }).then(function (res) {
						misaBtn.disabled = false;
						var message = (res && res.message) || 'Đã gửi phiếu sang MISA thành Đơn mua hàng. Trạng thái CRM: Chờ kế toán.';
						misa = {
							label: (res && res.misa_status) || 'Chờ kế toán',
							refno: (res && res.misa_refno) || '',
							state: (res && res.misa_state) || 'pending',
							note: ''
						};
						r.misa = misa;
						paintMisaChip(chip, misa);
						openPurchaseDialog({
							title: 'Đã gửi Đơn mua hàng',
							text: message,
							okLabel: 'Đóng',
							tone: 'ok'
						});
					}).catch(function (err) {
						misaBtn.disabled = false;
						var message = String(err || 'Không gửi được phiếu sang MISA.');
						misa = {
							label: 'Chưa gửi được',
							refno: '',
							state: 'rejected',
							note: message
						};
						r.misa = misa;
						paintMisaChip(chip, misa);
						openPurchaseDialog({
							title: 'Chưa gửi được Đơn mua hàng',
							text: message,
							okLabel: 'Đóng',
							tone: 'error'
						});
					});
				});
			});
			ids.actions.appendChild(misaBtn);
			if (String(r.status) === 'draft') {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'mk-kiot-btn mk-kiot-btn--primary';
				btn.textContent = 'Hoàn thành nhập hàng';
				btn.addEventListener('click', function () {
					btn.disabled = true;
					apiPost({ mode: 'complete_purchase', whId: r.warehouse, code: r.code }).then(function () {
						if (typeof opts.onComplete === 'function') opts.onComplete(r);
						else window.location.reload();
					}).catch(function (err) {
						btn.disabled = false;
						showToast(ids.msg, String(err), true);
					});
				});
				ids.actions.appendChild(btn);
			}
			if (opts.showBackLink) {
				var back = document.createElement('a');
				back.className = 'mk-kiot-btn mk-kiot-btn--ghost';
				back.href = 'index.php?module=Warehouse&view=PurchaseHistory&app=INVENTORY';
				back.textContent = 'Danh sách nhập hàng';
				ids.actions.appendChild(back);
			}
		}
	}

	/* ---------- Create (giữ logic) ---------- */
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

		function setMsg(text, isErr) { showToast(msg, text, !!isErr); }

		function emptyLinesHtml() {
			return '<tr class="mk-purchase-empty-row"><td colspan="7"><div class="mk-purchase-empty">' +
				'<div class="mk-purchase-empty__icon" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>' +
				'<p class="mk-purchase-empty__title">Chưa có dòng hàng</p>' +
				'<p class="mk-purchase-empty__sub">Gõ mã hoặc tên sản phẩm rồi chọn để thêm.</p></div></td></tr>';
		}

		function loadWarehouses() {
			var list = ((window.MK_WH_DB_STATE || {}).warehouses) || [];
			whSelect.innerHTML = '';
			list.filter(function (w) { return String(w.status || 'active') !== 'archived'; }).forEach(function (w) {
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
			if (lineCountEl) lineCountEl.textContent = lines.length + ' mặt hàng';
			if (!lines.length) { body.innerHTML = emptyLinesHtml(); updateTotals(); return; }
			body.innerHTML = lines.map(function (ln, idx) {
				var amount = (Number(ln.qty) || 0) * (Number(ln.price) || 0);
				return '<tr data-idx="' + idx + '">' +
					'<td><span class="mk-purchase-code">' + esc(ln.sku || '—') + '</span></td>' +
					'<td>' + esc(ln.name) + '</td>' +
					'<td><input class="mk-purchase-cell" data-f="lot" value="' + esc(ln.lot || '') + '" placeholder="LOT-…" /></td>' +
					'<td class="is-num"><input class="mk-purchase-cell mk-purchase-cell--num" data-f="qty" type="number" min="0" step="1" value="' + esc(ln.qty) + '" /></td>' +
					'<td class="is-num"><input class="mk-purchase-cell mk-purchase-cell--num" data-f="price" type="number" min="0" step="1000" value="' + esc(ln.price) + '" /></td>' +
					'<td class="is-num">' + money(amount) + '</td>' +
					'<td class="is-action"><button type="button" class="mk-purchase-remove" data-idx="' + idx + '">×</button></td></tr>';
			}).join('');
			updateTotals();
		}

		function updateTotals() {
			var sub = 0;
			lines.forEach(function (ln) { sub += (Number(ln.qty) || 0) * (Number(ln.price) || 0); });
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
				if (amtCell) amtCell.textContent = money((Number(lines[idx].qty) || 0) * (Number(lines[idx].price) || 0));
			}
		});
		body.addEventListener('click', function (e) {
			var btn = e.target.closest('.mk-purchase-remove');
			if (!btn) return;
			lines.splice(Number(btn.getAttribute('data-idx')), 1);
			renderLines();
		});
		document.getElementById('mkPurchaseDiscount').addEventListener('input', updateTotals);

		function searchVendors(q) {
			apiPost({ mode: 'search_vendors', q: q }).then(function (res) {
				var vendors = (res && res.vendors) || [];
				if (!vendors.length) { vendorSuggest.hidden = true; return; }
				vendorSuggest.innerHTML = vendors.map(function (v) {
					return '<button type="button" class="mk-purchase-suggest__item" data-id="' + esc(v.id) + '" data-name="' + esc(v.name) + '">' +
						'<strong>' + esc(v.name) + '</strong><span>' + esc(v.code || '') + (v.phone ? ' · ' + esc(v.phone) : '') + '</span></button>';
				}).join('');
				vendorSuggest.hidden = false;
			}).catch(function () { vendorSuggest.hidden = true; });
		}

		vendorQ.addEventListener('input', function () {
			vendorId.value = '';
			clearTimeout(vendorTimer);
			var q = vendorQ.value.trim();
			if (q.length < 1) { vendorSuggest.hidden = true; return; }
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
				var name = String(p.name || '').toLowerCase();
				var sku = String(p.sku || '').toLowerCase();
				return !q || name.indexOf(q) >= 0 || sku.indexOf(q) >= 0;
			}).slice(0, 12);
			if (!hits.length) { productSuggest.hidden = true; return; }
			productSuggest.innerHTML = hits.map(function (p) {
				return '<button type="button" class="mk-purchase-suggest__item" data-id="' + esc(p.id) + '" data-name="' + esc(p.name) + '" data-sku="' + esc(p.sku || '') + '" data-price="' + esc(p.price || 0) + '">' +
					'<strong>' + esc(p.name) + '</strong><span>' + esc(p.sku || '') + (p.price ? ' · ' + money(p.price) : '') + '</span></button>';
			}).join('');
			productSuggest.hidden = false;
		}
		productQ.addEventListener('input', function () { showProductSuggest(productQ.value); });
		productQ.addEventListener('focus', function () { showProductSuggest(productQ.value); });

		function addProductFromBtn(btn) {
			var id = Number(btn.getAttribute('data-id') || 0);
			var name = btn.getAttribute('data-name') || '';
			if (!name) return;
			var existing = lines.find(function (ln) { return ln.product_id === id && id > 0; });
			if (existing) existing.qty = (Number(existing.qty) || 0) + 1;
			else lines.push({ product_id: id, sku: btn.getAttribute('data-sku') || '', name: name, lot: '', qty: 1, price: Number(btn.getAttribute('data-price') || 0) });
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
			if (first) addProductFromBtn(first);
			else { showProductSuggest(productQ.value); setMsg('Chọn một sản phẩm từ gợi ý.', true); }
		});

		function submit(asDraft) {
			if (!canWrite) { setMsg('Bạn không có quyền tạo phiếu.', true); return; }
			try {
				var wh = (whSelect.value || '').trim();
				var name = (supplier.value || vendorQ.value || '').trim();
				if (!wh) throw new Error('Chọn kho nhận.');
				if (!name) throw new Error('Chọn hoặc nhập nhà cung cấp.');
				var clean = lines.filter(function (ln) { return (Number(ln.qty) || 0) > 0 && ln.name; }).map(function (ln) {
					return { product_id: ln.product_id || 0, sku: ln.sku || '', name: ln.name, lot: ln.lot || '', qty: Number(ln.qty) || 0, price: Number(ln.price) || 0 };
				});
				if (!clean.length) throw new Error('Thêm ít nhất một dòng hàng.');
				var payload = {
					warehouse: wh, whId: wh, supplier: name,
					vendorId: Number(vendorId.value || 0) || 0,
					poRef: (document.getElementById('mkPurchasePoRef').value || '').trim(),
					discount: Number(document.getElementById('mkPurchaseDiscount').value) || 0,
					paidAmount: Number(document.getElementById('mkPurchasePaid').value) || 0,
					paymentNote: (document.getElementById('mkPurchasePayNote').value || '').trim(),
					asDraft: !!asDraft, lines: clean
				};
				setMsg(asDraft ? 'Đang lưu tạm…' : 'Đang hoàn thành…');
				apiPost({ mode: 'save_purchase', whId: wh, payload: JSON.stringify(payload) }).then(function (res) {
					var code = (res && res.code) || '';
					window.location.href = detailUrl(code, wh);
				}).catch(function (err) { setMsg(String(err), true); });
			} catch (e) {
				setMsg(e.message || String(e), true);
			}
		}
		document.getElementById('mkPurchaseDraftBtn').addEventListener('click', function () { submit(true); });
		document.getElementById('mkPurchaseCompleteBtn').addEventListener('click', function () { submit(false); });
		document.addEventListener('click', function (e) {
			if (!vendorSuggest.contains(e.target) && e.target !== vendorQ) vendorSuggest.hidden = true;
			if (!productSuggest.contains(e.target) && e.target !== productQ) productSuggest.hidden = true;
		});
		loadWarehouses();
		renderLines();
	}

	/* ---------- List Nhập hàng + panel phải ---------- */
	function initHistory() {
		var root = document.getElementById('mkPurchaseHistoryRoot');
		if (!root) return;
		var layout = document.getElementById('mkPurchaseHistLayout');
		var body = document.getElementById('mkPurchaseHistBody');
		var msg = document.getElementById('mkPurchaseHistMsg');
		var qInput = document.getElementById('mkPurchaseHistQ');
		var whSelect = document.getElementById('mkPurchaseHistWh');
		var dueSum = document.getElementById('mkPurchaseHistDueSum');
		var panel = document.getElementById('mkPurchaseSidePanel');
		var allRows = [];
		var selectedCode = null;
		var loadSeq = 0;
		var timer = null;

		var panelIds = {
			code: document.getElementById('mkPurchasePanelCode'),
			status: document.getElementById('mkPurchasePanelStatus'),
			meta: document.getElementById('mkPurchasePanelMeta'),
			wh: document.getElementById('mkPurchasePanelWh'),
			info: document.getElementById('mkPurchasePanelInfo'),
			lines: document.getElementById('mkPurchasePanelLines'),
			note: document.getElementById('mkPurchasePanelNote'),
			totals: document.getElementById('mkPurchasePanelTotals'),
			actions: document.getElementById('mkPurchasePanelActions'),
			msg: msg
		};

		function fillWh() {
			var list = ((window.MK_WH_DB_STATE || {}).warehouses) || [];
			list.forEach(function (w) {
				var opt = document.createElement('option');
				opt.value = w.id || w.code;
				opt.textContent = (w.code || w.id) + ' — ' + (w.name || '');
				whSelect.appendChild(opt);
			});
		}

		function selectedStatuses() {
			var boxes = root.querySelectorAll('#mkPurchaseHistFilters input[data-status]');
			var set = {};
			Array.prototype.forEach.call(boxes, function (b) {
				if (b.checked) set[b.getAttribute('data-status')] = true;
			});
			return set;
		}

		function closePanel() {
			selectedCode = null;
			if (panel) panel.hidden = true;
			if (layout) layout.classList.remove('is-panel-open');
			root.classList.remove('is-panel-open');
			Array.prototype.forEach.call(body.querySelectorAll('tr.is-selected'), function (tr) {
				tr.classList.remove('is-selected');
			});
		}

		function markSelected(code) {
			Array.prototype.forEach.call(body.querySelectorAll('tr[data-code]'), function (tr) {
				if (tr.getAttribute('data-code') === code) tr.classList.add('is-selected');
				else tr.classList.remove('is-selected');
			});
		}

		function openPanel(code, wh) {
			if (!panel || !code) return;
			if (selectedCode === code && !panel.hidden) {
				closePanel();
				return;
			}
			selectedCode = code;
			panel.hidden = false;
			if (layout) layout.classList.add('is-panel-open');
			root.classList.add('is-panel-open');
			markSelected(code);

			panelIds.code.textContent = code;
			panelIds.status.textContent = '…';
			panelIds.status.className = 'mk-purchase-badge';
			panelIds.meta.textContent = 'Đang tải chi tiết…';
			panelIds.wh.innerHTML = '';
			panelIds.info.innerHTML = '';
			panelIds.lines.innerHTML = '<tr><td colspan="5">Đang tải…</td></tr>';
			panelIds.note.textContent = '';
			panelIds.totals.innerHTML = '';
			panelIds.actions.innerHTML = '';

			var seq = ++loadSeq;
			apiPost({ mode: 'get_purchase', code: code, whId: wh || '' }).then(function (res) {
				if (seq !== loadSeq || selectedCode !== code) return;
				var r = (res && res.receipt) || {};
				paintPurchaseReceipt(panelIds, r, {
					code: code,
					onComplete: function () {
						showToast(msg, 'Đã hoàn thành nhập hàng.', false);
						closePanel();
						load();
					}
				});
			}).catch(function (err) {
				if (seq !== loadSeq) return;
				panelIds.lines.innerHTML = '<tr><td colspan="5">Không tải được chi tiết.</td></tr>';
				showToast(msg, String(err), true);
			});
		}

		function render() {
			var st = selectedStatuses();
			var wh = (whSelect.value || '').trim();
			var rows = allRows.filter(function (r) {
				if (wh && String(r.warehouse) !== wh) return false;
				return !!st[String(r.status || '')];
			});
			var due = 0;
			rows.forEach(function (r) { due += Number(r.dueAmount) || 0; });
			if (dueSum) dueSum.textContent = money(due);
			if (!rows.length) {
				body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:#5b7268">Chưa có phiếu nhập hàng.</td></tr>';
				return;
			}
			body.innerHTML = rows.map(function (r) {
				var stLabel = statusLabel(r.status);
				var created = r.createdAt ? new Date(r.createdAt).toLocaleString('vi-VN') : '—';
				var sel = selectedCode && selectedCode === r.code ? ' is-selected' : '';
				return '<tr class="mk-kiot-purchase-row' + sel + '" data-code="' + esc(r.code) + '" data-wh="' + esc(r.warehouse) + '">' +
					'<td><button type="button" class="mk-kiot-code-link">' + esc(r.code) + '</button></td>' +
					'<td>' + esc(r.poRef || '—') + '</td>' +
					'<td>' + esc(created) + '</td>' +
					'<td>' + esc(r.supplier || '—') + '</td>' +
					'<td>' + esc(r.warehouse || '—') + '</td>' +
					'<td class="is-num">' + money(r.dueAmount || 0) + '</td>' +
					'<td><span class="mk-purchase-badge mk-purchase-badge--' + esc(r.status) + '">' + esc(stLabel) + '</span></td></tr>';
			}).join('');
		}

		function load() {
			body.innerHTML = '<tr><td colspan="7">Đang tải…</td></tr>';
			apiPost({ mode: 'list_purchases', q: (qInput.value || '').trim(), status: 'all' }).then(function (res) {
				allRows = (res && res.receipts) || [];
				render();
			}).catch(function (err) {
				body.innerHTML = '<tr><td colspan="7">Không tải được danh sách.</td></tr>';
				showToast(msg, String(err), true);
			});
		}

		body.addEventListener('click', function (e) {
			var tr = e.target.closest('tr[data-code]');
			if (!tr) return;
			openPanel(tr.getAttribute('data-code'), tr.getAttribute('data-wh'));
		});
		var closeBtn = document.getElementById('mkPurchasePanelClose');
		if (closeBtn) closeBtn.addEventListener('click', closePanel);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && selectedCode) closePanel();
		});
		root.querySelectorAll('#mkPurchaseHistFilters input[data-status]').forEach(function (b) {
			b.addEventListener('change', render);
		});
		whSelect.addEventListener('change', render);
		qInput.addEventListener('input', function () {
			clearTimeout(timer);
			timer = setTimeout(load, 280);
		});
		fillWh();
		load();
	}

	/* ---------- Detail (trang full — fallback / deep-link) ---------- */
	function initDetail() {
		var root = document.getElementById('mkPurchaseDetailRoot');
		if (!root) return;
		var code = root.getAttribute('data-code') || '';
		var wh = root.getAttribute('data-wh') || '';
		var msg = document.getElementById('mkPurchaseDetailMsg');
		var ids = {
			code: document.getElementById('mkPurchaseDetailCode'),
			status: document.getElementById('mkPurchaseDetailStatus'),
			meta: document.getElementById('mkPurchaseDetailMeta'),
			wh: document.getElementById('mkPurchaseDetailWh'),
			info: document.getElementById('mkPurchaseDetailInfo'),
			lines: document.getElementById('mkPurchaseDetailLines'),
			note: document.getElementById('mkPurchaseDetailNote'),
			totals: document.getElementById('mkPurchaseDetailTotals'),
			actions: document.getElementById('mkPurchaseDetailActions'),
			msg: msg
		};

		apiPost({ mode: 'get_purchase', code: code, whId: wh }).then(function (res) {
			paintPurchaseReceipt(ids, (res && res.receipt) || {}, { code: code, showBackLink: true });
		}).catch(function (err) {
			showToast(msg, String(err), true);
		});
	}

	/* ---------- Vendors ---------- */
	function initVendors() {
		var root = document.getElementById('mkVendorListRoot');
		if (!root) return;
		var body = document.getElementById('mkVendorListBody');
		var qInput = document.getElementById('mkVendorListQ');
		var groupSel = document.getElementById('mkVendorGroupFilter');
		var dueSum = document.getElementById('mkVendorDueSum');
		var totalSum = document.getElementById('mkVendorTotalSum');
		var all = [];
		var openId = null;
		var timer = null;

		function groupsFrom(rows) {
			var map = {};
			rows.forEach(function (v) {
				var g = String(v.category || '').trim();
				if (g) map[g] = true;
			});
			var cur = groupSel.value;
			groupSel.innerHTML = '<option value="">Tất cả nhóm</option>';
			Object.keys(map).sort().forEach(function (g) {
				var opt = document.createElement('option');
				opt.value = g;
				opt.textContent = g;
				groupSel.appendChild(opt);
			});
			groupSel.value = cur;
		}

		function expandHtml(v) {
			return '<tr class="mk-kiot-expand" data-expand="' + esc(v.id) + '"><td colspan="6"><div class="mk-kiot-expand-panel">' +
				'<div class="mk-kiot-expand-tabs">' +
				'<button type="button" class="mk-kiot-expand-tab is-active">Thông tin</button>' +
				'<button type="button" class="mk-kiot-expand-tab" disabled title="Sắp có">Lịch sử nhập/trả hàng</button>' +
				'<button type="button" class="mk-kiot-expand-tab" disabled title="Sắp có">Nợ cần trả nhà cung cấp</button>' +
				'</div>' +
				'<h3 class="mk-kiot-expand-title">' + esc(v.name) + '<code>' + esc(v.code || '') + '</code></h3>' +
				'<p class="mk-kiot-expand-meta">Người tạo: ' + esc(v.createdBy || '—') +
				' | Ngày tạo: ' + esc(v.createdAt ? new Date(v.createdAt).toLocaleDateString('vi-VN') : '—') +
				' | Nhóm: ' + esc(v.category || 'Chưa có') + '</p>' +
				'<div class="mk-kiot-expand-grid">' +
				'<div><span>Điện thoại</span><strong>' + esc(v.phone || 'Chưa có') + '</strong></div>' +
				'<div><span>Email</span><strong>' + esc(v.email || 'Chưa có') + '</strong></div>' +
				'<div class="full"><span>Địa chỉ</span><strong>' + esc(v.address || 'Chưa có') + '</strong></div>' +
				'<div><span>MST</span><strong>' + esc(v.tax || 'Chưa có') + '</strong></div>' +
				'<div><span>Phiếu nhập</span><strong>' + esc(v.receiptCount || 0) + '</strong></div>' +
				'</div>' +
				'<div class="mk-kiot-expand-actions">' +
				'<a class="mk-kiot-btn mk-kiot-btn--ghost" href="index.php?module=Vendors&view=Detail&record=' + esc(v.id) + '&app=INVENTORY">Xem Vtiger</a>' +
				'<a class="mk-kiot-btn mk-kiot-btn--primary" href="index.php?module=Vendors&view=Edit&record=' + esc(v.id) + '&app=INVENTORY">Chỉnh sửa</a>' +
				'</div></div></td></tr>';
		}

		function render() {
			var q = (qInput.value || '').trim().toLowerCase();
			var g = (groupSel.value || '').trim();
			var rows = all.filter(function (v) {
				if (g && String(v.category || '') !== g) return false;
				if (!q) return true;
				return String(v.name || '').toLowerCase().indexOf(q) >= 0 ||
					String(v.code || '').toLowerCase().indexOf(q) >= 0 ||
					String(v.phone || '').toLowerCase().indexOf(q) >= 0 ||
					String(v.email || '').toLowerCase().indexOf(q) >= 0;
			});
			var due = 0, tot = 0;
			rows.forEach(function (v) { due += Number(v.dueAmount) || 0; tot += Number(v.totalPurchase) || 0; });
			if (dueSum) dueSum.textContent = money(due);
			if (totalSum) totalSum.textContent = money(tot);
			if (!rows.length) {
				body.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:36px;color:#5b7268">Không có nhà cung cấp. Tạo mới hoặc import NCC.</td></tr>';
				return;
			}
			var html = '';
			rows.forEach(function (v) {
				html += '<tr class="mk-kiot-vendor-row' + (openId === v.id ? ' is-open' : '') + '" data-id="' + esc(v.id) + '">' +
					'<td><span class="mk-purchase-code">' + esc(v.code || '—') + '</span></td>' +
					'<td>' + esc(v.name) + '</td>' +
					'<td>' + esc(v.phone || '—') + '</td>' +
					'<td>' + esc(v.email || '—') + '</td>' +
					'<td class="is-num">' + money(v.dueAmount || 0) + '</td>' +
					'<td class="is-num">' + money(v.totalPurchase || 0) + '</td></tr>';
				if (openId === v.id) html += expandHtml(v);
			});
			body.innerHTML = html;
		}

		function load() {
			body.innerHTML = '<tr><td colspan="6">Đang tải…</td></tr>';
			apiPost({ mode: 'list_vendors', q: '' }).then(function (res) {
				all = (res && res.vendors) || [];
				groupsFrom(all);
				render();
			}).catch(function () {
				body.innerHTML = '<tr><td colspan="6">Không tải được danh sách NCC.</td></tr>';
			});
		}

		body.addEventListener('click', function (e) {
			if (e.target.closest('a') || e.target.closest('.mk-kiot-expand-panel')) return;
			var tr = e.target.closest('tr.mk-kiot-vendor-row');
			if (!tr) return;
			var id = Number(tr.getAttribute('data-id'));
			openId = openId === id ? null : id;
			render();
		});
		qInput.addEventListener('input', function () {
			clearTimeout(timer);
			timer = setTimeout(render, 200);
		});
		groupSel.addEventListener('change', render);
		load();
	}

	document.addEventListener('DOMContentLoaded', function () {
		initCreate();
		initHistory();
		initDetail();
		initVendors();
	});
})();
