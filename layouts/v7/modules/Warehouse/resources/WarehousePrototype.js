/**
 * Warehouse Prototype (Inventory) — UI-only interactions.
 */
(function () {
	'use strict';

	function qs(sel) {
		return document.querySelector(sel);
	}

	function qsa(sel) {
		return Array.prototype.slice.call(document.querySelectorAll(sel));
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
			qc: { title: 'Hàng đợi QC', btn: 'Tạo phiếu QC', pane: '#mkWhProtoPaneQc' },
			stock: { title: 'Tồn kho', btn: 'Thêm SKU', pane: '#mkWhProtoPaneStock' },
			outbound: { title: 'Danh sách phiếu xuất', btn: 'Tạo phiếu xuất', pane: '#mkWhProtoPaneOutbound' },
		};
		var meta = map[key] || map.inbound;
		title.textContent = meta.title;
		btn.textContent = meta.btn;

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
				hint: 'Quyền: Duyệt phiếu xuất • Xem báo cáo tồn kho • Tạo phiếu theo tab',
				perms: 'Duyệt phiếu xuất • Xem báo cáo tồn kho • Tạo phiếu theo tab',
			},
		};
		var meta = map[role] || map.qc;
		if (badge) badge.textContent = meta.badge;
		if (hint) hint.textContent = meta.hint;
		if (permRole) permRole.textContent = meta.badge;
		if (permItems) permItems.textContent = meta.perms;

		// Role-based create availability (UI only)
		// inbound/outbound: stock + manager, qc: qc + manager, stock tab: stock + manager
		var activeTab = 'inbound';
		var active = qs('.mk-wh-proto-tab.is-active');
		if (active) activeTab = active.getAttribute('data-tab') || 'inbound';
		var canCreate =
			(activeTab === 'qc' && (role === 'qc' || role === 'manager')) ||
			(activeTab === 'stock' && (role === 'stock' || role === 'manager')) ||
			((activeTab === 'inbound' || activeTab === 'outbound') && (role === 'stock' || role === 'manager'));
		if (btn) {
			btn.disabled = !canCreate;
			btn.title = canCreate ? '' : 'Vai trò này không có quyền tạo ở tab hiện tại (UI demo).';
			btn.classList.toggle('is-disabled', !canCreate);
		}
	}

	function openModal(opts) {
		var modal = qs('#mkWhProtoModal');
		var title = qs('#mkWhProtoModalTitle');
		var fields = qs('#mkWhProtoFormFields');
		var submit = qs('#mkWhProtoSubmitBtn');
		var form = qs('#mkWhProtoModalForm');
		if (!modal || !title || !fields || !form) return;

		title.textContent = opts.title || 'Tạo phiếu';
		if (submit) submit.textContent = opts.submitLabel || 'Tạo phiếu';

		fields.innerHTML = (opts.fields || [])
			.map(function (f) {
				var full = f.full ? ' mk-wh-proto-field--full' : '';
				var req = f.required ? ' *' : '';
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
								return '<option value="' + o.value + '">' + o.label + '</option>';
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

		form.onsubmit = function (e) {
			e.preventDefault();
			window.alert('UI demo: đã tạo "' + (opts.title || 'phiếu') + '" (backend sẽ làm sau).');
			closeModal();
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
		var isWait = code === 'GRN-0002';
		return {
			title: 'Phiếu nhập ' + code,
			metaHtml: 'NCC: ' + (code === 'GRN-0002' ? 'Vinamilk Logistics' : 'CTY Dược Hậu Giang') + ' · PO: ' + (code === 'GRN-0002' ? 'PO-2026-0151' : 'PO-2026-0142'),
			bodyHtml:
				'<div style="margin-bottom:10px;">' +
				(isWait
					? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Chờ QC</span>'
					: '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đã nhập kho</span>') +
				'</div>' +
				'<div class="mk-wh-proto-dialog-grid">' +
				'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Chi tiết hàng hóa</div>' +
				'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô / HSD</th><th>SL</th><th>QC</th></tr></thead><tbody>' +
				'<tr><td><strong>Paracetamol 500mg</strong><div class="mk-wh-proto-muted">MED-001</div></td><td>LOT-2605A<br/><span class="mk-wh-proto-muted">2027-05-01</span></td><td>1000</td><td><span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đạt</span></td></tr>' +
				'<tr><td><strong>Amoxicillin 250mg</strong><div class="mk-wh-proto-muted">MED-002</div></td><td>LOT-2605B<br/><span class="mk-wh-proto-muted">2027-03-15</span></td><td>500</td><td><span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đạt</span></td></tr>' +
				'</tbody></table>' +
				(isWait
					? '<div class="mk-wh-proto-dialog-section-title" style="margin-top:12px;">Ghi nhận kết quả QC</div>' +
						'<textarea class="mk-wh-proto-textarea" placeholder="Ghi chú kiểm tra (cảm quan, chứng từ, bao bì...)"></textarea>' +
						'<div class="mk-wh-proto-cta-row">' +
						'<button class="mk-wh-proto-cta mk-wh-proto-cta--pass" type="button" data-mk-action="qc-pass" data-code="' +
						code +
						'"><span>✔</span> Đạt</button>' +
						'<button class="mk-wh-proto-cta mk-wh-proto-cta--fail" type="button" data-mk-action="qc-fail" data-code="' +
						code +
						'"><span>✕</span> Không đạt</button>' +
						'</div>'
					: '') +
				'</div>' +
				'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
				'<div class="mk-wh-proto-timeline">' +
				'<div class="mk-wh-proto-timeline-item"><strong>Tạo phiếu nhập</strong><span class="mk-wh-proto-tag mk-wh-proto-tag--blue">Thủ kho</span><div class="mk-wh-proto-muted">Thủ kho Hà · 15:30 28/5/26</div></div>' +
				'<div class="mk-wh-proto-timeline-item"><strong>QC đạt</strong><span class="mk-wh-proto-tag mk-wh-proto-tag--qc">QC</span><div class="mk-wh-proto-muted">QC Minh · 16:15 28/5/26</div><div class="mk-wh-proto-quote">"Đầy đủ chứng từ, đúng quy cách"</div></div>' +
				'<div class="mk-wh-proto-timeline-item"><strong>Duyệt phiếu</strong><span class="mk-wh-proto-tag mk-wh-proto-tag--green">Quản lý kho</span><div class="mk-wh-proto-muted">QL Tuấn · 17:00 28/5/26</div></div>' +
				'<div class="mk-wh-proto-timeline-item"><strong>Đã nhập kho</strong><span class="mk-wh-proto-tag mk-wh-proto-tag--blue">Thủ kho</span><div class="mk-wh-proto-muted">Thủ kho Hà · 17:30 28/5/26</div><div class="mk-wh-proto-quote">"Vị trí: A1-02"</div></div>' +
				'</div>' +
				'</div>' +
				'</div>',
		};
	}

	function outboundDialog(code) {
		var isDelivered = code === 'GIN-0001';
		return {
			title: 'Phiếu xuất ' + code,
			metaHtml: 'Khách hàng: ' + (code === 'GIN-0001' ? 'Bệnh viện Bạch Mai' : 'Nhà thuốc Quận 1') + ' · SO: ' + (code === 'GIN-0001' ? 'SO-2026-0088' : 'SO-2026-0101'),
			bodyHtml:
				'<div style="margin-bottom:10px;">' +
				(isDelivered
					? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đã giao</span>'
					: '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Chờ duyệt</span>') +
				'</div>' +
				'<div class="mk-wh-proto-dialog-grid">' +
				'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Chi tiết xuất hàng</div>' +
				'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô</th><th>SL xuất</th><th>Tồn hiện tại</th></tr></thead><tbody>' +
				'<tr><td><strong>Paracetamol 500mg</strong><div class="mk-wh-proto-muted">MED-001</div></td><td>LOT-2605A</td><td>200</td><td>800</td></tr>' +
				'</tbody></table>' +
				'</div>' +
				'<div>' +
				'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
				'<div class="mk-wh-proto-timeline">' +
				'<div class="mk-wh-proto-timeline-item"><strong>Tạo phiếu xuất</strong><span class="mk-wh-proto-tag mk-wh-proto-tag--blue">Thủ kho</span><div class="mk-wh-proto-muted">Thủ kho Hà · 21:00 30/5/26</div></div>' +
				'<div class="mk-wh-proto-timeline-item"><strong>Duyệt phiếu</strong><span class="mk-wh-proto-tag mk-wh-proto-tag--green">Quản lý kho</span><div class="mk-wh-proto-muted">QL Tuấn · 21:30 30/5/26</div></div>' +
				'<div class="mk-wh-proto-timeline-item"><strong>Soạn hàng &amp; giao</strong><span class="mk-wh-proto-tag mk-wh-proto-tag--blue">Thủ kho</span><div class="mk-wh-proto-muted">Thủ kho Hà · 22:00 30/5/26</div></div>' +
				'</div>' +
				'</div>' +
				'</div>',
		};
	}

	function qcDialog(code) {
		return inboundDialog(code);
	}

	function modalSchema(tabKey, role) {
		// Keep schemas minimal but similar to prototype screenshot (UI-only).
		if (tabKey === 'inbound') {
			return {
				title: 'Tạo phiếu nhập kho',
				submitLabel: 'Tạo phiếu',
				fields: [
					{ name: 'supplier', label: 'Nhà cung cấp', required: true, full: true, placeholder: '' },
					{ name: 'po', label: 'Mã PO', required: true, full: true, placeholder: '' },
					{ name: 'sku', label: 'SKU', required: true, placeholder: '' },
					{ name: 'product', label: 'Tên hàng', required: true, placeholder: '' },
					{ name: 'lot', label: 'Lô', required: true, placeholder: '' },
					{ name: 'exp', label: 'HSD', required: true, type: 'date', placeholder: '' },
					{ name: 'qty', label: 'Số lượng', required: true, type: 'number', full: true, placeholder: '' },
				],
			};
		}
		if (tabKey === 'qc') {
			return {
				title: 'Tạo phiếu QC',
				submitLabel: 'Tạo phiếu',
				fields: [
					{ name: 'ref', label: 'Mã phiếu nhập', required: true, full: true, placeholder: 'GRN-0002' },
					{
						name: 'result',
						label: 'Kết quả',
						type: 'select',
						required: true,
						full: true,
						options: [
							{ value: 'pass', label: 'Đạt' },
							{ value: 'fail', label: 'Không đạt' },
						],
					},
					{ name: 'note', label: 'Ghi chú', type: 'textarea', full: true, placeholder: 'Ghi chú kiểm tra…' },
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
		return {
			title: 'Tạo phiếu xuất kho',
			submitLabel: 'Tạo phiếu',
			fields: [
				{ name: 'customer', label: 'Đơn vị nhận', required: true, full: true, placeholder: '' },
				{ name: 'ref', label: 'Tham chiếu', full: true, placeholder: 'SO-...' },
				{ name: 'sku', label: 'SKU', required: true, placeholder: '' },
				{ name: 'lot', label: 'Lô', required: true, placeholder: '' },
				{ name: 'qty', label: 'Số lượng', required: true, type: 'number', placeholder: '' },
				{ name: 'note', label: 'Ghi chú', type: 'textarea', full: true, placeholder: '' },
			],
		};
	}

	function boot() {
		if (!qs('#mkWhPrototypeRoot')) return;
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
				if (btn.disabled) return;
				var active = qs('.mk-wh-proto-tab.is-active');
				var tabKey = active ? active.getAttribute('data-tab') : 'inbound';
				var role = roleSel ? roleSel.value : 'qc';
				openModal(modalSchema(tabKey, role));
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
				var action = t.getAttribute && t.getAttribute('data-mk-action');
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
					window.alert('UI demo: đã ghi nhận QC "' + (action === 'qc-pass' ? 'Đạt' : 'Không đạt') + '" cho ' + (t.getAttribute('data-code') || 'phiếu') + '.');
					closeDialog();
					return;
				}
			});
		}

		setActiveTab('inbound');
		setRole(roleSel ? roleSel.value : 'qc');
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

