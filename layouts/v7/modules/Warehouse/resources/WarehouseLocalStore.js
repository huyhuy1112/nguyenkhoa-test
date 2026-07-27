/**
 * Multi-warehouse store — DB-backed (MK_WH_DB_STATE) with localStorage fallback.
 * Key: bace_multi_warehouse_v2
 */
(function (global) {
	'use strict';

	var KEY = 'bace_multi_warehouse_v2';
	var useDb = false;

	var SEED_WH = [
		{ id: 'WH-001', code: 'WH-001', name: 'Kho Hồ Chí Minh', type: 'central', address: 'Q.7, TP.HCM', manager: 'QL Tuấn', status: 'active', createdAt: '2026-01-15T08:00:00Z' },
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
			stock: [
				{ sku: 'MED-001', name: 'Paracetamol 500mg', lot: 'LOT-2605A', mfg: '2026-05-01', expiry: '2027-05-01', qty: 800, location: 'A1-02', price: 25000 },
				{ sku: 'MED-002', name: 'Amoxicillin 250mg', lot: 'LOT-2604B', mfg: '2026-02-15', expiry: '2026-08-15', qty: 120, location: 'B2-01', price: 45000 },
				{ sku: 'MED-003', name: 'Vitamin C 1000mg', lot: 'LOT-2603C', mfg: '2025-12-01', expiry: '2026-06-01', qty: 45, location: 'C1-03', price: 120000 },
			],
		},
		'WH-002': {
			receipts: [],
			issues: [],
			stock: [
				{ sku: 'MED-001', name: 'Paracetamol 500mg', lot: 'LOT-HN01', mfg: '2026-04-01', expiry: '2027-04-01', qty: 300, location: 'B1-01' },
				{ sku: 'MED-003', name: 'Vitamin C 1000mg', lot: 'LOT-HN02', mfg: '2026-09-01', expiry: '2027-09-01', qty: 220, location: 'B1-02' },
			],
		},
		'WH-003': {
			receipts: [],
			issues: [],
			stock: [
				{ sku: 'MED-002', name: 'Amoxicillin 250mg', lot: 'LOT-BD01', mfg: '2026-06-01', expiry: '2026-12-01', qty: 180, location: 'C1-01' },
			],
		},
	};

	var state = { warehouses: SEED_WH.slice(), transfers: [], data: JSON.parse(JSON.stringify(SEED_DATA)) };
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
		return state.data[id] || { receipts: [], issues: [], stock: [] };
	}

	function patchData(id, fn) {
		set(function (s) {
			var d = ensureData(id);
			var next = fn(Object.assign({}, d, {
				receipts: (d.receipts || []).slice(),
				issues: (d.issues || []).slice(),
				stock: (d.stock || []).slice(),
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
				data[id] = { receipts: [], issues: [], stock: [] };
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
				if (!window.confirm('Xóa kho này?')) return;
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
	};

	function computeSummary() {
		var totalStock = 0;
		var pendingQC = 0;
		var pendingExport = 0;
		var expiring = 0;
		var perWh = state.warehouses.map(function (w) {
			var d = ensureData(w.id);
			var stock = (d.stock || []).reduce(function (s, x) { return s + x.qty; }, 0);
			var skus = {};
			(d.stock || []).forEach(function (x) { skus[x.sku] = true; });
			var pQC = (d.receipts || []).filter(function (r) { return r.status === 'pending_qc'; }).length;
			var pEx = (d.issues || []).filter(function (i) {
				return i.status !== 'shipped' && i.status !== 'rejected' && i.status !== 'cancelled';
			}).length;
			var exp = (d.stock || []).filter(function (s) {
				var days = (new Date(s.expiry).getTime() - Date.now()) / 86400000;
				return days < 90 && s.qty > 0;
			}).length;
			totalStock += stock;
			pendingQC += pQC;
			pendingExport += pEx;
			expiring += exp;
			return { w: w, stock: stock, skus: Object.keys(skus).length, pQC: pQC, pEx: pEx, exp: exp };
		});
		return { perWh: perWh, totalStock: totalStock, pendingQC: pendingQC, pendingExport: pendingExport, expiring: expiring };
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
		transferActions: transferActions,
		computeSummary: computeSummary,
		totalStockOf: totalStockOf,
		skuCountOf: skuCountOf,
		ensureData: ensureData,
		nowISO: nowISO,
	};
})(typeof window !== 'undefined' ? window : this);
