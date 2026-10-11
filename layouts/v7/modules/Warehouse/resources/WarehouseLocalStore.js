/**
 * Multi-warehouse store — DB-backed (MK_WH_DB_STATE) with localStorage fallback.
 * Key: bace_multi_warehouse_v2
 */
(function (global) {
	'use strict';

	function mkWhConfirm(message) {
		var styleId = 'mkWhConfirmStyle';
		if (!document.getElementById(styleId)) {
			var style = document.createElement('style');
			style.id = styleId;
			style.textContent = '#mkWhConfirm{position:fixed;inset:0;z-index:10050;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.45);padding:24px;}#mkWhConfirm .mk-wh-confirm__card{width:min(440px,100%);background:#fff;border-radius:16px;box-shadow:0 20px 50px rgba(15,23,42,.18);padding:22px 22px 18px;color:#111827;}#mkWhConfirm h3{margin:0 0 8px;font-size:18px;font-weight:700;color:#14532d;}#mkWhConfirm p{margin:0 0 8px;font-size:14px;line-height:1.45;color:#374151;}#mkWhConfirm .mk-wh-confirm__actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px;}#mkWhConfirm button{border-radius:999px;padding:8px 16px;font-size:14px;font-weight:600;cursor:pointer;border:1px solid transparent;}#mkWhConfirm .mk-wh-confirm__cancel{background:#fff;border-color:#d1d5db;color:#374151;}#mkWhConfirm .mk-wh-confirm__ok{background:#15803d;color:#fff;}';
			document.head.appendChild(style);
		}
		return new Promise(function (resolve) {
			var root = document.createElement('div');
			root.id = 'mkWhConfirm';
			var lines = String(message || '').split(/\n+/).map(function (line) {
				return line.trim();
			}).filter(Boolean);
			var title = lines.shift() || 'Xác nhận';
			var body = lines.map(function (line) {
				return '<p>' + line.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
			}).join('');
			function esc(s) {
				return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
			}
			root.innerHTML = '<div class="mk-wh-confirm__card" role="dialog" aria-modal="true"><h3>' + esc(title) + '</h3>' + body + '<div class="mk-wh-confirm__actions"><button type="button" class="mk-wh-confirm__cancel">Huỷ</button><button type="button" class="mk-wh-confirm__ok">OK</button></div></div>';
			function close(ok) {
				if (root.parentNode) root.parentNode.removeChild(root);
				resolve(!!ok);
			}
			root.addEventListener('click', function (e) {
				if (e.target === root) close(false);
			});
			root.querySelector('.mk-wh-confirm__cancel').addEventListener('click', function () { close(false); });
			root.querySelector('.mk-wh-confirm__ok').addEventListener('click', function () { close(true); });
			document.body.appendChild(root);
			root.querySelector('.mk-wh-confirm__ok').focus();
		});
	}
	global.MkWhConfirm = mkWhConfirm;

	var KEY = 'bace_multi_warehouse_v2';
	var useDb = false;

	var SEED_WH = [
		{ id: 'WH-001', code: 'WH-001', name: 'Kho trung tâm', type: 'central', address: 'Q.7, TP.HCM', manager: 'QL Tuấn', status: 'active', createdAt: '2026-01-15T08:00:00Z' },
		{ id: 'WH-002', code: 'WH-002', name: 'Kho Hà Nội', type: 'branch', address: 'Long Biên, Hà Nội', manager: 'QL Nam', status: 'active', createdAt: '2026-02-20T08:00:00Z' },
		{ id: 'WH-003', code: 'WH-003', name: 'Kho Bình Dương', type: 'branch', address: 'Thuận An, Bình Dương', manager: 'QL Hùng', status: 'active', createdAt: '2026-03-10T08:00:00Z' },
	];

	var SEED_DATA = {
		'WH-001': {
			receipts: [
				{
					id: 'GRN-0001', supplier: 'CTY Dược Hậu Giang', poRef: 'PO-2026-0142',
					createdAt: '2026-05-28T08:30:00Z', createdBy: 'Thủ kho Hà', status: 'stored',
					lines: [
						{ sku: 'MED-001', name: 'Paracetamol 500mg', lot: 'LOT-2605A', expiry: '2027-05-01', qty: 1000, qcResult: 'pass', passedQty: 1000 },
						{ sku: 'MED-002', name: 'Amoxicillin 250mg', lot: 'LOT-2605B', expiry: '2027-03-15', qty: 500, qcResult: 'pass', passedQty: 500 },
					],
					timeline: [
						{ at: '2026-05-28T08:30:00Z', by: 'Thủ kho Hà', role: 'keeper', action: 'Tạo phiếu nhập' },
						{ at: '2026-05-28T09:15:00Z', by: 'QC Minh', role: 'qc', action: 'QC đạt' },
						{ at: '2026-05-28T10:00:00Z', by: 'QL Tuấn', role: 'manager', action: 'Duyệt phiếu' },
						{ at: '2026-05-28T10:30:00Z', by: 'Thủ kho Hà', role: 'keeper', action: 'Đã nhập kho' },
					],
				},
				{
					id: 'GRN-0002', supplier: 'Vinamilk Logistics', poRef: 'PO-2026-0151',
					createdAt: '2026-06-01T07:20:00Z', createdBy: 'Thủ kho Hà', status: 'pending_qc',
					lines: [{ sku: 'FMC-010', name: 'Sữa tươi 1L', lot: 'LOT-0106', mfg: '2026-06-01', expiry: '2026-08-30', qty: 300 }],
					timeline: [
						{ at: '2026-06-01T07:20:00Z', by: 'Thủ kho Hà', role: 'keeper', action: 'Tạo phiếu nhập' },
						{ at: '2026-06-01T07:45:00Z', by: 'Thủ kho Hà', role: 'keeper', action: 'Gửi QC kiểm tra' },
					],
				},
			],
			issues: [
				{
					id: 'GIN-0001', outboundType: 'internal', customer: 'Bệnh viện Bạch Mai', soRef: 'SO-2026-0088',
					createdAt: '2026-05-30T14:00:00Z', createdBy: 'Thủ kho Hà', status: 'shipped',
					lines: [{ sku: 'MED-001', name: 'Paracetamol 500mg', lot: 'LOT-2605A', qty: 200 }],
					timeline: [
						{ at: '2026-05-30T14:00:00Z', by: 'Thủ kho Hà', role: 'keeper', action: 'Tạo phiếu xuất — Xuất nội bộ' },
						{ at: '2026-05-30T15:00:00Z', by: 'QL Tuấn', role: 'manager', action: 'Duyệt phiếu' },
						{ at: '2026-05-30T15:30:00Z', by: 'Thủ kho Hà', role: 'keeper', action: 'Soạn hàng & giao' },
					],
				},
				{
					id: 'GIN-0002', outboundType: 'transfer', customer: 'Nhà thuốc Quận 1', soRef: 'SO-2026-0101',
					createdAt: '2026-05-30T15:10:00Z', createdBy: 'Thủ kho Hà', status: 'pending_approval',
					lines: [{ sku: 'MED-002', name: 'Amoxicillin 250mg', lot: 'LOT-2604B', qty: 50 }],
					timeline: [
						{ at: '2026-05-30T15:10:00Z', by: 'Thủ kho Hà', role: 'keeper', action: 'Gửi duyệt' },
					],
				},
			],
			returns: [],
			stock: [
				{ sku: 'MED-001', name: 'Paracetamol 500mg', lot: 'LOT-2605A', mfg: '2026-05-01', expiry: '2027-05-01', qty: 800, location: 'A1-02', price: 25000 },
				{ sku: 'MED-002', name: 'Amoxicillin 250mg', lot: 'LOT-2604B', mfg: '2026-02-15', expiry: '2026-08-15', qty: 120, location: 'B2-01', price: 45000 },
				{ sku: 'MED-003', name: 'Vitamin C 1000mg', lot: 'LOT-2603C', mfg: '2025-12-01', expiry: '2026-06-01', qty: 45, location: 'C1-03', price: 120000 },
			],
		},
		'WH-002': {
			receipts: [],
			issues: [],
			returns: [],
			stock: [
				{ sku: 'MED-001', name: 'Paracetamol 500mg', lot: 'LOT-HN01', mfg: '2026-04-01', expiry: '2027-04-01', qty: 300, location: 'B1-01' },
				{ sku: 'MED-003', name: 'Vitamin C 1000mg', lot: 'LOT-HN02', mfg: '2026-09-01', expiry: '2027-09-01', qty: 220, location: 'B1-02' },
			],
		},
		'WH-003': {
			receipts: [],
			issues: [],
			returns: [],
			stock: [
				{ sku: 'MED-002', name: 'Amoxicillin 250mg', lot: 'LOT-BD01', mfg: '2026-06-01', expiry: '2026-12-01', qty: 180, location: 'C1-01' },
			],
		},
	};

	var DEFAULT_SETTINGS = {
		wh_allow_negative_stock: 1,
		wh_expiry_warn_days: 90,
		wh_slow_window_days: 30,
		wh_doi_threshold: 60,
		wh_dsi_threshold: 30,
		wh_age_max: 90,
		wh_risk_w1: 0.35,
		wh_risk_w2: 0.3,
		wh_risk_w3: 0.2,
		wh_risk_w4: 0.15,
	};
	var STOCKOUT_SOON_DAYS = 14;
	var state = { warehouses: SEED_WH.slice(), transfers: [], data: JSON.parse(JSON.stringify(SEED_DATA)), settings: Object.assign({}, DEFAULT_SETTINGS) };
	var hydrated = false;
	var listeners = [];

	function nowISO() {
		return new Date().toISOString();
	}

	function emit() {
		listeners.forEach(function (cb) {
			try { cb(); } catch (e) { /* ignore */ }
		});
	}

	function persist() {
		if (useDb || typeof window === 'undefined' || !hydrated) return;
		try {
			localStorage.setItem(KEY, JSON.stringify(state));
		} catch (e) { /* ignore */ }
	}

	function unwrapApiResponse(res) {
		if (!res || typeof res !== 'object') {
			return res;
		}
		// Vtiger HTTP response: { success: true, result: { success, data, ... } }
		if (res.result && typeof res.result === 'object') {
			return Object.assign({ success: true }, res.result);
		}
		return res;
	}

	function apiPost(data) {
		var def = $.Deferred();
		var reqData = Object.assign({ module: 'Warehouse', action: 'WhMgmtApi' }, data);
		var onOk = function (res) {
			if (!res || res.success === false || res.error) {
				var msg = (res && res.error) ? res.error : 'Yêu cầu thất bại';
				if (msg && typeof msg === 'object' && msg.message) msg = msg.message;
				def.reject({ message: String(msg || 'Yêu cầu thất bại') });
				return;
			}
			def.resolve(unwrapApiResponse(res));
		};
		var onErr = function (err) {
			var msg = err;
			if (msg && typeof msg === 'object') {
				msg = msg.message || msg.statusText || msg.responseText;
			}
			def.reject({ message: String(msg || 'Không kết nối được máy chủ.') });
		};

		// Prefer Vtiger request helper when available; otherwise fallback to plain AJAX.
		if (typeof window !== 'undefined' && window.app && app.request && app.request.post) {
			app.request.post({ data: reqData }).then(function (err, res) {
				if (err) {
					onErr(err);
					return;
				}
				onOk(res);
			});
			return def.promise();
		}

		$.ajax({
			url: 'index.php',
			method: 'POST',
			dataType: 'json',
			data: reqData,
		}).done(onOk).fail(onErr);
		return def.promise();
	}

	function reloadPage() {
		if (typeof window !== 'undefined') {
			window.location.reload();
		}
	}

	function set(updater) {
		state = updater(state);
		persist();
		emit();
	}

	function ensureData(id) {
		return state.data[id] || { receipts: [], issues: [], stock: [], returns: [] };
	}

	function patchData(id, fn) {
		set(function (s) {
			var d = ensureData(id);
			var next = fn(Object.assign({}, d, {
				receipts: (d.receipts || []).slice(),
				issues: (d.issues || []).slice(),
				stock: (d.stock || []).slice(),
				returns: (d.returns || []).slice(),
			}));
			var data = Object.assign({}, s.data);
			data[id] = next;
			return Object.assign({}, s, { data: data });
		});
	}

	function hydrate() {
		if (hydrated || typeof window === 'undefined') return;
		hydrated = true;
		if (global.MK_WH_DB_STATE && global.MK_WH_DB_STATE.warehouses) {
			useDb = true;
			state = {
				warehouses: global.MK_WH_DB_STATE.warehouses || [],
				transfers: global.MK_WH_DB_STATE.transfers || [],
				data: global.MK_WH_DB_STATE.data || {},
				settings: Object.assign({}, DEFAULT_SETTINGS, global.MK_WH_DB_STATE.settings || {}),
				canStockFillAdmin: global.MK_WH_DB_STATE.canStockFillAdmin ? 1 : 0,
			};
			emit();
			return;
		}
		try {
			var raw = localStorage.getItem(KEY);
			if (raw) {
				var parsed = JSON.parse(raw);
				state = {
					warehouses: parsed.warehouses || SEED_WH.slice(),
					transfers: parsed.transfers || [],
					data: Object.assign({}, JSON.parse(JSON.stringify(SEED_DATA)), parsed.data || {}),
					settings: Object.assign({}, DEFAULT_SETTINGS, parsed.settings || {}),
				};
				emit();
			}
		} catch (e) { /* ignore */ }
	}

	function subscribe(cb) {
		listeners.push(cb);
		return function () {
			listeners = listeners.filter(function (x) { return x !== cb; });
		};
	}

	function getState() {
		return state;
	}

	var TYPE_LABEL = {
		central: 'Kho trung tâm',
		branch: 'Kho chi nhánh',
		transit: 'Kho trung chuyển',
		cold: 'Kho lạnh',
	};

	var STATUS_LABEL = {
		active: 'Hoạt động',
		inactive: 'Tạm dừng',
		archived: 'Lưu trữ',
	};

	var warehouseActions = {
		create: function (input) {
			if (useDb) {
				apiPost({ mode: 'save', payload: JSON.stringify(input) }).then(reloadPage);
				return null;
			}
			var id = 'WH-' + String(state.warehouses.length + 1).padStart(3, '0');
			var w = Object.assign({}, input, { id: id, status: input.status || 'active', createdAt: nowISO() });
			set(function (s) {
				var data = Object.assign({}, s.data);
				data[id] = { receipts: [], issues: [], stock: [], returns: [] };
				return Object.assign({}, s, { warehouses: s.warehouses.concat([w]), data: data });
			});
			return w;
		},
		update: function (id, patch) {
			if (useDb) {
				var w = state.warehouses.find(function (x) { return x.id === id; });
				var payload = Object.assign({}, w || {}, patch, { id: id });
				apiPost({ mode: 'save', id: id, payload: JSON.stringify(payload) }).then(reloadPage);
				return;
			}
			set(function (s) {
				return Object.assign({}, s, {
					warehouses: s.warehouses.map(function (w) {
						return w.id === id ? Object.assign({}, w, patch) : w;
					}),
				});
			});
		},
		remove: function (id) {
			if (useDb) {
				apiPost({ mode: 'delete', id: id }).then(reloadPage);
				return;
			}
			set(function (s) {
				var data = Object.assign({}, s.data);
				delete data[id];
				return Object.assign({}, s, {
					warehouses: s.warehouses.filter(function (w) { return w.id !== id; }),
					data: data,
				});
			});
		},
		archive: function (id) {
			if (useDb) {
				apiPost({ mode: 'archive', id: id }).then(reloadPage);
				return;
			}
			warehouseActions.update(id, { status: 'archived' });
		},
	};

	var warehouseDataActions = {
		get: function (id) { return ensureData(id); },
		setReceipts: function (id, receipts) { patchData(id, function (d) { return Object.assign({}, d, { receipts: receipts }); }); },
		setIssues: function (id, issues) { patchData(id, function (d) { return Object.assign({}, d, { issues: issues }); }); },
		setStock: function (id, stock) { patchData(id, function (d) { return Object.assign({}, d, { stock: stock }); }); },
		refresh: function (whId) {
			if (!useDb) {
				return $.Deferred().resolve({ success: true, data: ensureData(whId) }).promise();
			}
			var def = $.Deferred();
			apiPost({ mode: 'get', id: whId }).then(function (res) {
				if (res && res.canStockFillAdmin != null) {
					state.canStockFillAdmin = res.canStockFillAdmin ? 1 : 0;
				}
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) { def.reject(err); });
			return def.promise();
		},
		saveReceipt: function (whId, receipt) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			apiPost({
				mode: 'save_receipt',
				whId: whId,
				payload: JSON.stringify(receipt),
			}).then(function (res) {
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) {
				def.reject(err);
			});
			return def.promise();
		},
		saveIssue: function (whId, issue) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			apiPost({
				mode: 'save_issue',
				whId: whId,
				payload: JSON.stringify(issue || {}),
			}).then(function (res) {
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) {
				def.reject(err);
			});
			return def.promise();
		},
		receiptAction: function (whId, code, actionKey, role, note, targetStatus) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			var qcNote = note || '';
			apiPost({
				mode: 'receipt_action',
				whId: whId,
				code: code,
				actionKey: actionKey,
				role: role || '',
				qcNote: qcNote,
				note: qcNote,
				targetStatus: targetStatus || '',
				payload: JSON.stringify({ qcNote: qcNote, targetStatus: targetStatus || '' }),
			}).then(function (res) {
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) { def.reject(err); });
			return def.promise();
		},
		issueAction: function (whId, code, actionKey, role, note, targetStatus) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			apiPost({
				mode: 'issue_action',
				whId: whId,
				code: code,
				actionKey: actionKey,
				role: role || '',
				note: note || '',
				targetStatus: targetStatus || '',
			}).then(function (res) {
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) { def.reject(err); });
			return def.promise();
		},
		uploadQcImage: function (whId, code, file, role) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			var fd = new FormData();
			fd.append('module', 'Warehouse');
			fd.append('action', 'WhMgmtApi');
			fd.append('mode', 'qc_upload_image');
			fd.append('whId', whId);
			fd.append('code', code);
			fd.append('role', role || '');
			fd.append('qcImage', file);
			$.ajax({
				url: 'index.php',
				method: 'POST',
				data: fd,
				processData: false,
				contentType: false,
				dataType: 'json',
			}).done(function (res) {
				var out = unwrapApiResponse(res);
				if (!out || out.success === false || out.error) {
					def.reject({ message: String((out && out.error) || 'Upload thất bại') });
					return;
				}
				if (out.data) {
					patchData(whId, function () { return out.data; });
				}
				def.resolve(out);
			}).fail(function (xhr) {
				var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Upload thất bại';
				def.reject({ message: String(msg) });
			});
			return def.promise();
		},
		importStockExcel: function (whId, file, wipe) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			var fd = new FormData();
			fd.append('module', 'Warehouse');
			fd.append('action', 'WhMgmtApi');
			fd.append('mode', 'import_stock_excel');
			fd.append('whId', whId);
			fd.append('wipe', wipe ? '1' : '0');
			fd.append('stockExcel', file);
			try {
				var csrf = '';
				if (typeof jQuery !== 'undefined' && jQuery('[name="__vtrftk"]').length) {
					csrf = jQuery('[name="__vtrftk"]').val() || '';
				} else if (typeof csrfMagicToken !== 'undefined') {
					csrf = csrfMagicToken;
				}
				if (csrf) {
					fd.append('__vtrftk', csrf);
				}
			} catch (eCsrf) { /* ignore */ }
			$.ajax({
				url: 'index.php',
				method: 'POST',
				data: fd,
				processData: false,
				contentType: false,
				dataType: 'json',
			}).done(function (res) {
				var out = unwrapApiResponse(res);
				if (!out || out.success === false || out.error) {
					def.reject({ message: String((out && out.error) || 'Import thất bại') });
					return;
				}
				if (out.data) {
					patchData(whId, function () { return out.data; });
				}
				def.resolve(out);
			}).fail(function (xhr) {
				var msg = 'Import thất bại';
				if (xhr && xhr.responseJSON) {
					if (xhr.responseJSON.error) msg = xhr.responseJSON.error;
					else if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
				}
				def.reject({ message: String(msg) });
			});
			return def.promise();
		},
		deleteQcImage: function (whId, code, imageId) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			apiPost({
				mode: 'qc_delete_image',
				whId: whId,
				code: code,
				imageId: imageId,
			}).then(function (res) {
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) { def.reject(err); });
			return def.promise();
		},
		setStockFill: function (whId, open) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			apiPost({
				mode: 'set_stock_fill',
				whId: whId,
				open: open ? '1' : '0',
			}).then(function (res) {
				if (res && res.canStockFillAdmin != null) {
					state.canStockFillAdmin = res.canStockFillAdmin ? 1 : 0;
				}
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) { def.reject(err); });
			return def.promise();
		},
		saveStockFill: function (whId, stockKey, fields) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			apiPost({
				mode: 'save_stock_fill',
				whId: whId,
				stockKey: stockKey,
				expiry: fields && fields.expiry ? fields.expiry : '',
				location: fields && fields.location ? fields.location : '',
				lot: fields && fields.lot ? fields.lot : '',
			}).then(function (res) {
				if (res && res.canStockFillAdmin != null) {
					state.canStockFillAdmin = res.canStockFillAdmin ? 1 : 0;
				}
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) { def.reject(err); });
			return def.promise();
		},
		updateQcRecord: function (whId, code, role, note) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			apiPost({
				mode: 'qc_update',
				whId: whId,
				code: code,
				role: role || '',
				qcNote: note || '',
				note: note || '',
				payload: JSON.stringify({ qcNote: note || '' }),
			}).then(function (res) {
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) { def.reject(err); });
			return def.promise();
		},
	};

	function getExpiryWarnDays() {
		var n = Number(state.settings && state.settings.wh_expiry_warn_days);
		if (!isFinite(n) || n <= 0) return 90;
		if (n > 730) return 730;
		return n;
	}

	function expiryWarnDaysFor(stockRow) {
		var p = Number(stockRow && stockRow.expiryWarnDays);
		if (isFinite(p) && p > 0) return p;
		return getExpiryWarnDays();
	}

	function isExpiringSoon(stockRow) {
		if (!stockRow || !stockRow.expiry || (Number(stockRow.qty) || 0) === 0) return false;
		var days = (new Date(stockRow.expiry).getTime() - Date.now()) / 86400000;
		return days < expiryWarnDaysFor(stockRow);
	}

	function stockoutDays(stockRow) {
		if (!stockRow || stockRow.daysToStockout === null || stockRow.daysToStockout === undefined || stockRow.daysToStockout === '') {
			return null;
		}
		var n = Number(stockRow.daysToStockout);
		return isFinite(n) ? n : null;
	}

	function isStockoutSoon(stockRow) {
		var days = stockoutDays(stockRow);
		return days !== null && days <= STOCKOUT_SOON_DAYS && (Number(stockRow.qty) || 0) !== 0;
	}

	function stockoutLabel(stockRow) {
		var days = stockoutDays(stockRow);
		if (days === null) return 'Không đủ dữ liệu';
		var rounded = Math.max(0, Math.round(days));
		if (days <= STOCKOUT_SOON_DAYS) return 'Sắp hết · còn ~' + rounded + ' ngày';
		return 'Còn ~' + rounded + ' ngày';
	}

	var returnActions = {
		searchSources: function (q, whId) {
			if (!useDb) {
				return $.Deferred().resolve({ success: true, issues: [], sources: [] }).promise();
			}
			return apiPost({ mode: 'search_return_sources', q: q || '', whId: whId || '' });
		},
		save: function (whId, payload) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			apiPost({
				mode: 'save_return',
				whId: whId,
				payload: JSON.stringify(payload || {}),
			}).then(function (res) {
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) { def.reject(err); });
			return def.promise();
		},
		confirm: function (whId, code) {
			return returnActions.action(whId, code, 'confirm');
		},
		cancel: function (whId, code) {
			return returnActions.action(whId, code, 'cancel');
		},
		action: function (whId, code, actionKey) {
			if (!useDb) {
				return $.Deferred().reject({ message: 'Chế độ lưu database chưa sẵn sàng.' }).promise();
			}
			var def = $.Deferred();
			apiPost({
				mode: 'return_action',
				whId: whId,
				code: code,
				actionKey: actionKey,
			}).then(function (res) {
				if (res && res.data) {
					patchData(whId, function () { return res.data; });
				}
				def.resolve(res);
			}).fail(function (err) { def.reject(err); });
			return def.promise();
		},
	};

	function computeSummary() {
		var totalStock = 0;
		var pendingQC = 0;
		var pendingExport = 0;
		var expiring = 0;
		var stockoutSoon = 0;
		var perWh = state.warehouses.map(function (w) {
			var d = ensureData(w.id);
			var stock = (d.stock || []).reduce(function (s, x) { return s + x.qty; }, 0);
			var skus = {};
			(d.stock || []).forEach(function (x) { skus[x.sku] = true; });
			var pQC = (d.receipts || []).filter(function (r) { return r.status === 'pending_qc'; }).length;
			var pEx = (d.issues || []).filter(function (i) {
				return i.status !== 'shipped' && i.status !== 'rejected' && i.status !== 'cancelled';
			}).length;
			var exp = (d.stock || []).filter(isExpiringSoon).length;
			var so = (d.stock || []).filter(isStockoutSoon).length;
			totalStock += stock;
			pendingQC += pQC;
			pendingExport += pEx;
			expiring += exp;
			stockoutSoon += so;
			return { w: w, stock: stock, skus: Object.keys(skus).length, pQC: pQC, pEx: pEx, exp: exp, stockoutSoon: so };
		});
		return { perWh: perWh, totalStock: totalStock, pendingQC: pendingQC, pendingExport: pendingExport, expiring: expiring, stockoutSoon: stockoutSoon };
	}

	function totalStockOf(id) {
		return (ensureData(id).stock || []).reduce(function (sum, s) { return sum + s.qty; }, 0);
	}

	function skuCountOf(id) {
		var set = {};
		(ensureData(id).stock || []).forEach(function (s) { set[s.sku] = true; });
		return Object.keys(set).length;
	}

	var TRANSFER_STATUS_LABEL = {
		pending: 'Chờ duyệt',
		approved: 'Đã duyệt',
		in_transit: 'Đang vận chuyển',
		completed: 'Hoàn tất',
		cancelled: 'Đã hủy',
	};

	var transferActions = {
		create: function (input) {
			var id = 'TRF-' + String(state.transfers.length + 1).padStart(4, '0');
			var t = Object.assign({}, input, { id: id, status: 'pending', createdAt: nowISO() });
			set(function (s) {
				return Object.assign({}, s, { transfers: [t].concat(s.transfers) });
			});
			return t;
		},
		approve: function (id, approvedBy) {
			set(function (s) {
				return Object.assign({}, s, {
					transfers: s.transfers.map(function (t) {
						return t.id === id ? Object.assign({}, t, { status: 'approved', approvedBy: approvedBy || 'QL Tuấn' }) : t;
					}),
				});
			});
		},
		ship: function (id) {
			set(function (s) {
				return Object.assign({}, s, {
					transfers: s.transfers.map(function (t) {
						return t.id === id ? Object.assign({}, t, { status: 'in_transit' }) : t;
					}),
				});
			});
		},
		complete: function (id) {
			var t = state.transfers.find(function (x) { return x.id === id; });
			if (!t) return;
			patchData(t.fromWarehouseId, function (d) {
				return Object.assign({}, d, {
					stock: d.stock.map(function (s) {
						return s.sku === t.sku && s.lot === t.lot
							? Object.assign({}, s, { qty: Math.max(0, s.qty - t.qty) })
							: s;
					}),
				});
			});
			patchData(t.toWarehouseId, function (d) {
				var idx = d.stock.findIndex(function (s) { return s.sku === t.sku && s.lot === t.lot; });
				if (idx >= 0) {
					var next = d.stock.slice();
					next[idx] = Object.assign({}, next[idx], { qty: next[idx].qty + t.qty });
					return Object.assign({}, d, { stock: next });
				}
				return Object.assign({}, d, {
					stock: d.stock.concat([{
						sku: t.sku, name: t.name, lot: t.lot, expiry: '—', qty: t.qty, location: 'NEW',
					}]),
				});
			});
			set(function (s) {
				return Object.assign({}, s, {
					transfers: s.transfers.map(function (x) {
						return x.id === id ? Object.assign({}, x, { status: 'completed' }) : x;
					}),
				});
			});
		},
		cancel: function (id) {
			set(function (s) {
				return Object.assign({}, s, {
					transfers: s.transfers.map(function (t) {
						return t.id === id ? Object.assign({}, t, { status: 'cancelled' }) : t;
					}),
				});
			});
		},
	};

	global.MkWarehouseStore = {
		KEY: KEY,
		useDb: function () { return useDb; },
		TYPE_LABEL: TYPE_LABEL,
		STATUS_LABEL: STATUS_LABEL,
		TRANSFER_STATUS_LABEL: TRANSFER_STATUS_LABEL,
		hydrate: hydrate,
		subscribe: subscribe,
		getState: getState,
		warehouseActions: warehouseActions,
		warehouseDataActions: warehouseDataActions,
		returnActions: returnActions,
		transferActions: transferActions,
		computeSummary: computeSummary,
		getExpiryWarnDays: getExpiryWarnDays,
		expiryWarnDaysFor: expiryWarnDaysFor,
		isExpiringSoon: isExpiringSoon,
		isStockoutSoon: isStockoutSoon,
		stockoutLabel: stockoutLabel,
		STOCKOUT_SOON_DAYS: STOCKOUT_SOON_DAYS,
		totalStockOf: totalStockOf,
		skuCountOf: skuCountOf,
		ensureData: ensureData,
		nowISO: nowISO,
	};
})(typeof window !== 'undefined' ? window : this);
