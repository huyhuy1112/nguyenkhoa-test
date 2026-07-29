/**
 * Shared POS list row-expand dropdown for Sales CRM modules
 * (Accounts / ServiceContracts / Leads / Potentials / Contacts).
 *
 * Opt-in:
 *   window.__mkSalesPosInlineConfig = {
 *     module, loadingText, errorText,
 *     tableSelector, rowSelector, colspan, enabledSelector,
 *     resolveRecordId(rowEl) // optional
 *   }
 */
(function ($) {
	'use strict';

	var expandedId = '';
	var loading = false;

	function cfg() {
		return window.__mkSalesPosInlineConfig || {};
	}

	function isPosEnabled() {
		var c = cfg();
		if (c.enabledSelector) {
			return !!document.querySelector(c.enabledSelector);
		}
		return !!document.querySelector(
			'#listViewContent .mk-so-pos-list-enabled, .mk-so-pos-page, [data-mk-leads-list], [data-mk-opps-list], [data-mk-opportunity-list], [data-mk-contacts-list], [data-mk-accounts-list], [data-mk-ps-list]'
		);
	}

	function moduleName() {
		return cfg().module || (document.body && document.body.getAttribute('data-module')) || '';
	}

	function tableSelector() {
		return cfg().tableSelector || '#listview-table';
	}

	function rowSelector() {
		return cfg().rowSelector || 'tr.listViewEntries';
	}

	function getColspan($table) {
		// Prefer live thead count so config never drifts when columns change.
		var $ths = $table.find('thead tr:first th');
		if ($ths.length > 0) {
			return $ths.length;
		}
		var c = cfg();
		if (c.colspan) {
			return Number(c.colspan) || 8;
		}
		return 8;
	}

	function resolveRecordId(rowEl) {
		var c = cfg();
		if (typeof c.resolveRecordId === 'function') {
			return String(c.resolveRecordId(rowEl) || '');
		}
		var $row = $(rowEl);
		var crmid = String($row.attr('data-crmid') || $row.data('crmid') || '').trim();
		if (crmid && /^\d+$/.test(crmid)) {
			return crmid;
		}
		var id = String($row.data('id') || $row.attr('data-id') || '').trim();
		if (id && /^\d+$/.test(id)) {
			return id;
		}
		return '';
	}

	function collapse($table) {
		$table.find('tr.mk-so-inline-detail-row').remove();
		$table.find('tr.mk-so-row-expanded, tr.mk-leads-row.mk-so-row-expanded').removeClass('mk-so-row-expanded');
		expandedId = '';
		loading = false;
	}

	function isAlwaysEdit($panel) {
		return String($panel.attr('data-always-edit') || '') === '1';
	}

	function setEditMode($panel, enable) {
		if (isAlwaysEdit($panel)) {
			enable = true;
		}
		var isEdit = !!enable;
		$panel.toggleClass('is-edit-mode', isEdit);
		$panel.find('.mk-so-inline-detail__edit-toggle').attr('aria-pressed', isEdit ? 'true' : 'false');
		$panel.find('.mk-so-inline-detail__notes-input').not('[disabled], .mk-so-inline-detail__next-action-input[readonly]').prop('readonly', !isEdit);
		$panel.find('.mk-so-inline-detail__next-action.is-locked .mk-so-inline-detail__next-action-input')
			.prop('readonly', true)
			.prop('disabled', true);
		if (isEdit && typeof vtUtils !== 'undefined' && vtUtils.applyFieldElementsView) {
			vtUtils.applyFieldElementsView(
				$panel.find('.mk-so-inline-detail__field-edit .dateField').closest('.mk-so-inline-detail__field-edit')
			);
		}
	}

	function captureSnapshot($panel) {
		var snapshot = {
			fields: {},
			description: $panel.find('.mk-so-inline-detail__notes-input[name="description"]').val() || '',
			next_action: $panel.find('.mk-so-inline-detail__next-action-input').val() || ''
		};
		$panel.find('.mk-so-inline-detail__field-edit :input').each(function () {
			var name = $(this).attr('name');
			if (name) snapshot.fields[name] = $(this).val();
		});
		return snapshot;
	}

	function restoreSnapshot($panel, snapshot) {
		if (!snapshot) return;
		$.each(snapshot.fields || {}, function (name, value) {
			$panel.find('.mk-so-inline-detail__field-edit :input[name="' + name + '"]').val(value);
		});
		$panel.find('.mk-so-inline-detail__notes-input[name="description"]').val(snapshot.description || '');
		$panel.find('.mk-so-inline-detail__next-action-input').val(snapshot.next_action || '');
	}

	function updateViewValues($panel) {
		$panel.find('.mk-so-inline-detail__field[data-editable="1"]').each(function () {
			var $field = $(this);
			var $input = $field.find('.mk-so-inline-detail__field-edit :input').first();
			var $view = $field.find('.mk-so-inline-detail__field-view');
			if (!$input.length || !$view.length) return;
			if ($input.is('select')) {
				$view.text($input.find('option:selected').text() || '—');
			} else {
				$view.text($input.val() || '—');
			}
		});
	}

	function closeInlinePrintPreview() {
		var $modal = $('#mk-crm-inline-print-preview');
		$modal.removeClass('is-open').attr('aria-hidden', 'true');
		$modal.find('iframe').attr('src', 'about:blank');
		$('body').removeClass('mk-so-inline-print-open');
	}

	function ensureInlinePrintPreviewModal() {
		var $modal = $('#mk-crm-inline-print-preview');
		if ($modal.length) {
			return $modal;
		}
		$modal = $(
			'<div id="mk-crm-inline-print-preview" class="mk-so-inline-print-preview" aria-hidden="true">' +
				'<div class="mk-so-inline-print-preview__dialog" role="dialog" aria-labelledby="mk-crm-inline-print-title">' +
					'<div class="mk-so-inline-print-preview__head">' +
						'<h3 id="mk-crm-inline-print-title">Hợp đồng nhượng quyền TUI BAO</h3>' +
						'<button type="button" class="mk-so-inline-print-preview__close" aria-label="Đóng">&times;</button>' +
					'</div>' +
					'<div class="mk-so-inline-print-preview__body">' +
						'<iframe class="mk-so-inline-print-preview__frame" title="Xem trước PDF hợp đồng"></iframe>' +
					'</div>' +
					'<div class="mk-so-inline-print-preview__foot">' +
						'<button type="button" class="mk-so-inline-print-preview__cancel">Đóng</button>' +
						'<button type="button" class="mk-so-inline-print-preview__print"><i class="fa fa-print" aria-hidden="true"></i> In ngay</button>' +
						'<button type="button" class="mk-so-inline-print-preview__download"><i class="fa fa-download" aria-hidden="true"></i> Tải PDF</button>' +
					'</div>' +
				'</div>' +
			'</div>'
		);
		$('body').append($modal);
		$modal.on('click', '.mk-so-inline-print-preview__close, .mk-so-inline-print-preview__cancel', function (e) {
			e.preventDefault();
			closeInlinePrintPreview();
		});
		$modal.on('click', function (e) {
			if ($(e.target).is('#mk-crm-inline-print-preview')) {
				closeInlinePrintPreview();
			}
		});
		$modal.on('click', '.mk-so-inline-print-preview__print', function (e) {
			e.preventDefault();
			var $iframe = $('#mk-crm-inline-print-preview iframe');
			if (!$iframe.length) return;
			try {
				var frameWindow = $iframe[0].contentWindow;
				if (frameWindow) {
					frameWindow.focus();
					frameWindow.print();
				}
			} catch (err) {
				var src = $iframe.attr('src');
				if (src && src !== 'about:blank') {
					window.open(src, '_blank');
				}
			}
		});
		$modal.on('click', '.mk-so-inline-print-preview__download', function (e) {
			e.preventDefault();
			var $panel = $modal.data('mkPrintPanel');
			var downloadUrl =
				($panel && ($panel.data('print-download-url') || $panel.find('.mk-so-inline-detail__print-btn').data('print-download-url'))) || '';
			if (downloadUrl) {
				window.open(downloadUrl, '_blank');
			}
			closeInlinePrintPreview();
		});
		return $modal;
	}

	function openInlinePrintPreview($panel, recordId) {
		var mod = String($panel.data('module') || moduleName());
		var printUrl =
			$panel.data('print-url') ||
			$panel.find('.mk-so-inline-detail__print-btn').data('print-url');
		if (!printUrl) {
			printUrl =
				'index.php?module=' +
				encodeURIComponent(mod) +
				'&action=ExportFranchisePDF&record=' +
				encodeURIComponent(recordId) +
				'&preview=1';
		}
		var $modal = ensureInlinePrintPreviewModal();
		$modal.data('mkPrintPanel', $panel);
		$modal.data('mkPrintRecordId', recordId);
		$modal.find('iframe').attr('src', printUrl);
		$modal.addClass('is-open').attr('aria-hidden', 'false');
		$('body').addClass('mk-so-inline-print-open');
	}

	function initLeadTagPicker($panel) {
		if (!$panel || !$panel.length) return;
		if (String($panel.attr('data-editable-tags') || $panel.find('[data-editable-tags]').attr('data-editable-tags') || '') !== '1'
			&& !$panel.find('.mk-so-inline-detail__tags[data-editable-tags="1"]').length) {
			return;
		}
		var $wrap = $panel.find('.mk-so-inline-detail__tags[data-editable-tags="1"]');
		if (!$wrap.length || $wrap.data('mkTagPickerInit')) return;
		$wrap.data('mkTagPickerInit', true);
		var mod = String($panel.data('module') || moduleName());
		var ref = window.LeadsLovableRef;
		if (mod === 'Potentials' && window.PotentialsLovableRef) ref = window.PotentialsLovableRef;
		else if (mod === 'Contacts' && window.ContactsLovableRef) ref = window.ContactsLovableRef;
		var catalog = ref && ref.getCreateTagCatalog ? ref.getCreateTagCatalog() : [];
		var $picker = $wrap.find('[data-role="tag-picker"]');
		var $list = $wrap.find('[data-role="selected-tags"]');
		if (!$picker.length) return;

		function selectedKeys() {
			var keys = [];
			$list.find('.mk-tag[data-tag], [data-tag]').each(function () {
				var k = $(this).attr('data-tag');
				if (ref && ref.normalizeTag) k = ref.normalizeTag(k);
				else if (ref && ref.normalizeTagKey) k = ref.normalizeTagKey(k);
				if (k && keys.indexOf(k) < 0) keys.push(k);
			});
			return keys;
		}

		function catalogKeySet() {
			var set = {};
			if (ref && ref.getCreateTagKeys) {
				ref.getCreateTagKeys().forEach(function (k) { set[k] = true; });
			}
			return set;
		}

		function renderSelected(keys) {
			if (!keys.length) {
				$list.html('<span class="mk-so-inline-detail__tags-empty">Chưa có tag</span>');
				return;
			}
			$list.html(keys.map(function (key) {
				var label = key;
				if (ref && ref.labelForTag) label = ref.labelForTag(key);
				else if (ref && ref.tagMeta) label = (ref.tagMeta(key).label) || key;
				return '<span class="mk-tag" data-tag="' + $('<div/>').text(key).html() + '" title="' +
					$('<div/>').text(label).html() + '">' + $('<div/>').text(label).html() + '</span>';
			}).join(''));
		}

		function renderPicker() {
			var selected = {};
			selectedKeys().forEach(function (k) { selected[k] = true; });
			var html = catalog.map(function (g) {
				var chips = (g.tags || []).map(function (item) {
					var on = !!selected[item.key];
					return '<button type="button" class="mk-so-inline-tag-chip' + (on ? ' is-on' : '') +
						'" data-tag="' + $('<div/>').text(item.key).html() + '" aria-pressed="' + (on ? 'true' : 'false') + '">' +
						$('<div/>').text(item.label).html() + '</button>';
				}).join('');
				return '<div class="mk-so-inline-tag-group"><div class="mk-so-inline-tag-group__title">' +
					$('<div/>').text(g.label).html() + '</div><div class="mk-so-inline-tag-group__chips">' + chips + '</div></div>';
			}).join('');
			$picker.html(html || '<span class="mk-so-inline-detail__tags-empty">Không có danh mục tag</span>');
		}

		renderPicker();
		$picker.on('click', '.mk-so-inline-tag-chip', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $chip = $(this);
			var key = $chip.attr('data-tag');
			var keys = selectedKeys();
			var inCatalog = catalogKeySet();
			var groupKeys = [];
			$chip.closest('.mk-so-inline-tag-group').find('.mk-so-inline-tag-chip').each(function () {
				var gk = $(this).attr('data-tag');
				if (gk) groupKeys.push(gk);
			});
			var idx = keys.indexOf(key);
			if ($chip.hasClass('is-on')) {
				if (idx >= 0) keys.splice(idx, 1);
			} else {
				// Single-select per group: clear other tags in the same group.
				keys = keys.filter(function (k) {
					return groupKeys.indexOf(k) < 0;
				});
				keys.push(key);
			}
			// Keep custom/non-catalog tags that were already on the lead.
			Object.keys(inCatalog).forEach(function () {});
			renderSelected(keys);
			renderPicker();
		});
		$panel.data('mkGetEditableTags', selectedKeys);
	}

	function initPanel($detailRow) {
		var $panel = $detailRow.find('.mk-so-inline-detail');
		if (!$panel.length || $panel.data('mkPosInlineInit')) {
			return;
		}
		$panel.data('mkPosInlineInit', true);
		var recordId = String($panel.data('record-id') || '');
		var mod = String($panel.data('module') || moduleName());
		var snapshot = captureSnapshot($panel);
		setEditMode($panel, true);
		initLeadTagPicker($panel);

		$panel.on('click', '.mk-so-inline-detail__view-full-btn', function (e) {
			e.preventDefault();
			var url = $panel.attr('data-detail-url');
			if (url) window.location.href = url;
		});
		$panel.on('click', '.mk-so-inline-detail__edit-btn', function (e) {
			e.preventDefault();
			var url = $panel.attr('data-edit-url');
			if (url) window.location.href = url;
		});
		$panel.on('click', '.mk-so-inline-detail__print-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if (!recordId) return;
			openInlinePrintPreview($panel, recordId);
		});
		$panel.on('click', '.mk-so-inline-detail__edit-toggle', function (e) {
			e.preventDefault();
			e.stopPropagation();
			setEditMode($panel, true);
			var $focus = $panel.find('.mk-so-inline-detail__notes-input[name="description"]');
			$focus.focus();
		});
		$panel.on('click', '.mk-so-inline-detail__cancel-edit', function (e) {
			e.preventDefault();
			e.stopPropagation();
			restoreSnapshot($panel, snapshot);
			setEditMode($panel, true);
		});

		function renderClassReg($wrap, summary) {
			if (!$wrap.length || !summary) return;
			$wrap.find('.mk-so-inline-detail__class-reg-hint').text(summary.hint || '');
			var $list = $wrap.find('.mk-so-inline-detail__class-reg-list');
			var logs = summary.logs || [];
			if (!logs.length) {
				$list.html('<li class="mk-so-inline-detail__class-reg-empty">Chưa có lần đăng ký nào</li>');
			} else {
				$list.html(logs.map(function (log) {
					var badge = log.badge || ('Lần ' + (log.n || ''));
					var retakeClass = log.is_retake ? ' is-retake' : '';
					var btn = log.show_retake_btn
						? '<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__class-reg-retake-btn" title="Học lại">' +
							'<i class="fa fa-refresh" aria-hidden="true"></i><span>Học lại</span></button>'
						: '';
					return '<li class="mk-so-inline-detail__class-reg-item' + retakeClass + '" data-id="' + (log.id || '') + '">' +
						'<span class="mk-so-inline-detail__class-reg-n' + retakeClass + '">' + $('<div/>').text(badge).html() + '</span>' +
						'<span class="mk-so-inline-detail__class-reg-text">' + $('<div/>').text(log.label || '').html() + '</span>' +
						btn +
						'</li>';
				}).join(''));
			}
			var $add = $wrap.find('.mk-so-inline-detail__class-reg-add');
			if (summary.can_add === false) {
				$add.hide();
			} else {
				$add.show();
			}
			$wrap.data('mkClassRegSummary', summary);
			// Sync Thời gian Đăng Ký field (lần 1)
			if (summary.first_on_label) {
				var $dk = $panel.find('.mk-so-inline-detail__field[data-field-name="thoigian_dangky"]');
				$dk.find('.mk-so-inline-detail__field-view').text(summary.first_on_label);
				$dk.find('.mk-so-inline-detail__field-edit :input').val(summary.first_on_label);
			}
		}

		$panel.on('click', '.mk-so-inline-detail__class-reg-add-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if (!recordId || mod !== 'Contacts') return;
			var $wrap = $panel.find('.mk-so-inline-detail__class-reg');
			var $input = $wrap.find('.mk-so-inline-detail__class-reg-date');
			var dateVal = String($input.val() || '').trim();
			if (!dateVal) {
				window.alert('Vui lòng nhập ngày đăng ký (dd/mm/yyyy).');
				$input.focus();
				return;
			}
			var $btn = $(this);
			$btn.prop('disabled', true);
			var postData = {
				module: 'Contacts',
				action: 'ModernApi',
				mode: 'class_reg_add',
				record: recordId,
				registered_on: dateVal,
				entry_kind: 'register'
			};
			function done(err, res) {
				$btn.prop('disabled', false);
				if (err || !res || res.success === false) {
					var msg = (err && err.message) || (res && res.error) || 'Không thêm được đăng ký.';
					if (typeof msg === 'object' && msg.message) msg = msg.message;
					if (typeof app !== 'undefined' && app.helper && app.helper.showErrorNotification) {
						app.helper.showErrorNotification({ message: String(msg) });
					} else {
						window.alert(String(msg));
					}
					return;
				}
				var summary = res.class_reg || (res.result && res.result.class_reg) || null;
				if (summary) renderClassReg($wrap, summary);
				$input.val('');
				if (typeof app !== 'undefined' && app.helper && app.helper.showSuccessNotification) {
					app.helper.showSuccessNotification({ message: 'Đã ghi nhận đăng ký học.' });
				}
			}
			if (typeof app !== 'undefined' && app.request && app.request.post) {
				app.request.post({ data: postData }).then(function (err, res) {
					done(err, res);
				});
			} else {
				$.ajax({ url: 'index.php', type: 'POST', dataType: 'json', data: postData })
					.done(function (r) { done(null, r && r.result ? r.result : r); })
					.fail(function () { done({ message: 'Không kết nối được máy chủ.' }, null); });
			}
		});

		$panel.on('click', '.mk-so-inline-detail__class-reg-retake-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if (!recordId || mod !== 'Contacts') return;
			var $wrap = $panel.find('.mk-so-inline-detail__class-reg');
			var summary = $wrap.data('mkClassRegSummary') || {};
			var min = summary.retake_date_min || '';
			var max = summary.retake_date_max || '';
			var hint = 'Nhập ngày Học lại (dd/mm/yyyy)';
			if (min || max) {
				hint += ' — sau ' + (min || '...') + (max ? ', đến ' + max : '');
			}
			var dateVal = window.prompt(hint, '');
			if (dateVal == null) return;
			dateVal = String(dateVal || '').trim();
			if (!dateVal) {
				window.alert('Vui lòng nhập ngày học lại.');
				return;
			}
			var $btn = $(this);
			$btn.prop('disabled', true);
			var postData = {
				module: 'Contacts',
				action: 'ModernApi',
				mode: 'class_reg_add',
				record: recordId,
				registered_on: dateVal,
				entry_kind: 'retake'
			};
			function doneRetake(err, res) {
				$btn.prop('disabled', false);
				if (err || !res || res.success === false) {
					var msg = (err && err.message) || (res && res.error) || 'Không ghi được học lại.';
					if (typeof msg === 'object' && msg.message) msg = msg.message;
					if (typeof app !== 'undefined' && app.helper && app.helper.showErrorNotification) {
						app.helper.showErrorNotification({ message: String(msg) });
					} else {
						window.alert(String(msg));
					}
					return;
				}
				var summary2 = res.class_reg || (res.result && res.result.class_reg) || null;
				if (summary2) renderClassReg($wrap, summary2);
				if (typeof app !== 'undefined' && app.helper && app.helper.showSuccessNotification) {
					app.helper.showSuccessNotification({ message: 'Đã ghi nhận Học lại lần 1.' });
				}
			}
			if (typeof app !== 'undefined' && app.request && app.request.post) {
				app.request.post({ data: postData }).then(function (err, res) {
					doneRetake(err, res);
				});
			} else {
				$.ajax({ url: 'index.php', type: 'POST', dataType: 'json', data: postData })
					.done(function (r) { doneRetake(null, r && r.result ? r.result : r); })
					.fail(function () { doneRetake({ message: 'Không kết nối được máy chủ.' }, null); });
			}
		});

		$panel.on('click', '.mk-so-inline-detail__save-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if (!recordId || !mod) return;
			var $saveBtn = $(this);
			$saveBtn.prop('disabled', true);
			var postData = {
				record: recordId,
				module: mod,
				action: 'SaveAjax',
				description: $panel.find('.mk-so-inline-detail__notes-input[name="description"]').val() || ''
			};
			$panel.find('.mk-so-inline-detail__field-edit :input').each(function () {
				var name = $(this).attr('name');
				if (!name || name.indexOf('mk_') === 0) return;
				if (name) postData[name] = $(this).val();
			});
			function postRequest(data) {
				return (typeof app !== 'undefined' && app.request && app.request.post)
					? app.request.post({ data: data })
					: $.Deferred(function (d) {
						$.ajax({ url: 'index.php', type: 'POST', dataType: 'json', data: data })
							.done(function (r) { d.resolve(null, r && r.result ? r.result : r); })
							.fail(function () { d.resolve({ message: 'Save failed' }, null); });
					}).promise();
			}
			function showSaveError(err) {
				var message = (err && err.message) || 'Không lưu được.';
				if (typeof app !== 'undefined' && app.helper && app.helper.showErrorNotification) {
					app.helper.showErrorNotification({ message: message });
				} else {
					window.alert(message);
				}
			}
			// Khách hàng nhượng quyền: lưu trạng thái/list fields qua ModernApi (không dùng stock contract_status).
			if (mod === 'ServiceContracts') {
				var inlinePayload = {
					franchise_status: postData.franchise_status || '',
					contact_status: postData.contact_status || '',
					referrer: postData.referrer || '',
					interaction_1: postData.interaction_1 || '',
					interaction_2: postData.interaction_2 || '',
					interaction_3: postData.interaction_3 || '',
					interaction_materials: postData.interaction_materials || '',
					assigned_user_id: postData.assigned_user_id || '',
					description: postData.description || ''
				};
				postRequest({
					module: 'ServiceContracts',
					action: 'ModernApi',
					mode: 'save_inline',
					record: recordId,
					payload: JSON.stringify(inlinePayload)
				}).then(function (err, res) {
					$saveBtn.prop('disabled', false);
					if (err || !res || res.success === false) {
						showSaveError(err || { message: (res && (res.error || res.message)) || 'Không lưu được.' });
						return;
					}
					var c = (res && res.contract) || {};
					function syncView(name, val) {
						$panel.find('.mk-so-inline-detail__field[data-field-name="' + name + '"] .mk-so-inline-detail__field-view')
							.text(val || '—');
					}
					syncView('franchise_status', c.franchise_status);
					syncView('contact_status', c.contact_status);
					syncView('referrer', c.referrer);
					syncView('interaction_1', c.interaction_1);
					syncView('interaction_2', c.interaction_2);
					syncView('interaction_3', c.interaction_3);
					syncView('interaction_materials', c.interaction_materials);
					try {
						document.dispatchEvent(new CustomEvent('mk-sc-inline-saved', {
							detail: { id: String(recordId), contract: c }
						}));
					} catch (evErr) { /* IE */ }
					if (typeof app !== 'undefined' && app.helper && app.helper.showSuccessNotification) {
						app.helper.showSuccessNotification({ message: 'Đã lưu.' });
					}
				});
				return;
			}
			function slugifyInlineTag(raw) {
				var s = String(raw || "").trim().toLowerCase();
				if (!s) return "";
				if (s.charAt(0) === "#") s = s.slice(1);
				try {
					s = s.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
				} catch (e) { /* IE */ }
				s = s
					.replace(/đ/g, "d")
					.replace(/[^a-z0-9]+/g, "_")
					.replace(/^_+|_+$/g, "")
					.replace(/_+/g, "_");
				if (s === "gold") return "vang";
				if (s === "silver") return "bac";
				if (s === "bronze") return "dong";
				if (s === "ca_nhan") return "individual";
				return s;
			}
			function refreshInlineTags($panelEl, tags) {
				if (!$.isArray(tags)) return;
				var $list = $panelEl.find('.mk-so-inline-detail__tags-list');
				if (!$list.length) return;
				var panelMod = String(($panelEl && $panelEl.data && $panelEl.data('module')) || moduleName() || '');
				var hideTier = panelMod === 'Leads';
				var visible = tags.filter(function (raw) {
					if (!hideTier) return true;
					var k = slugifyInlineTag(raw);
					return k !== 'vang' && k !== 'bac' && k !== 'dong';
				});
				if (!visible.length) {
					$list.html('<span class="mk-so-inline-detail__tags-empty">Chưa có tag</span>');
					return;
				}
				var ref = window.PotentialsLovableRef || window.LeadsLovableRef;
				var html = visible.map(function (raw) {
					var key = String(raw || "").trim();
					var label = key;
					var cls = "mk-tag";
					if (ref && ref.normalizeTag) {
						key = ref.normalizeTag(raw) || key;
					} else {
						key = slugifyInlineTag(raw) || key;
					}
					if (ref && ref.labelForTag) {
						label = ref.labelForTag(key, label);
					} else if (ref && ref.tagMeta) {
						var meta = ref.tagMeta(key) || ref.tagMeta(raw);
						label = meta.label || label;
						if (meta.cls) cls = meta.cls;
					} else if (key) {
						cls = "mk-tag mk-tag--" + String(key).replace(/_/g, "-");
					}
					if (/^goi_lan_(\d+)$/.test(key) || /^\d{1,2}$/.test(key)) {
						var nCall = parseInt(RegExp.$1 || key, 10);
						key = 'goi_lan_' + nCall;
						label = 'Gọi lần ' + nCall;
					}
					if (/^kv([123])$/.test(key)) {
						label = 'Khu vực ' + RegExp.$1;
					}
					return '<span class="' + cls + '" data-tag="' + $('<div/>').text(key).html() + '" title="' + $('<div/>').text(String(raw)).html() + '">' +
						$('<div/>').text(String(label)).html() + '</span>';
				}).join('');
				$list.html(html);
			}
			function applyOppConfirmToList(recordId, confirmKey, confirmedAt) {
				if (window.PotentialsLocalStore && typeof window.PotentialsLocalStore.setConfirmTag === 'function') {
					window.PotentialsLocalStore.setConfirmTag(recordId, confirmKey, confirmedAt || '');
				}
				try {
					document.dispatchEvent(new CustomEvent('mk-opps-confirm-updated', {
						detail: {
							id: String(recordId),
							confirm: confirmKey || '',
							confirmed_at: confirmedAt || ''
						}
					}));
				} catch (e) { /* IE */ }
				var $row = $('tr.mk-leads-row[data-crmid="' + recordId + '"], tr.mk-leads-row[data-id="' + recordId + '"]').first();
				if (!$row.length) return;
				var $td = $row.children('td[data-col="confirm"]');
				var $join = $row.children('td[data-col="confirmed_at"]');
				if (!$td.length) return;
				if (!confirmKey) {
					$td.html('<span class="mk-leads-muted">—</span>');
					if ($join.length) $join.html('<span class="mk-leads-muted">—</span>');
					return;
				}
				var ref = window.PotentialsLovableRef;
				var label = confirmKey;
				var key = confirmKey;
				if (ref && ref.normalizeTag) {
					key = ref.normalizeTag(confirmKey) || confirmKey;
				}
				if (ref && ref.tagMeta) {
					var meta = ref.tagMeta(confirmKey);
					label = meta.label || label;
				} else if (confirmKey === 'xac_nhan_tham_gia') {
					label = 'Xác nhận tham gia';
				} else if (confirmKey === 'khong_xac_nhan_tham_gia') {
					label = 'Không tham gia';
				}
				$td.html(
					'<span class="mk-tag" data-tag="' +
					$('<div/>').text(key).html() +
					'">' +
					$('<div/>').text(label).html() +
					'</span>'
				);
				if ($join.length) {
					if (confirmKey === 'xac_nhan_tham_gia' && confirmedAt) {
						var d = new Date(confirmedAt);
						var labelAt = confirmedAt;
						if (!isNaN(d.getTime())) {
							function pad(n) { return n < 10 ? '0' + n : String(n); }
							labelAt =
								pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() +
								' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
						}
						$join.text(labelAt);
					} else {
						$join.html('<span class="mk-leads-muted">—</span>');
					}
				}
			}
			function finishSave(response) {
				var scrollHost = document.querySelector('.mk-leads-table-scroll') || document.querySelector('.mk-dash-main');
				var scrollTop = scrollHost ? scrollHost.scrollTop : 0;
				var winScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
				if (response) {
					var listPatch = {};
					$panel.find('.mk-so-inline-detail__field-edit :input').each(function () {
						var name = $(this).attr('name');
						if (!name || name.indexOf('mk_') === 0) return;
						if (!response[name]) return;
						var displayValue = response[name].display_value;
						if (displayValue !== undefined && displayValue !== null) {
							$panel
								.find('.mk-so-inline-detail__field[data-field-name="' + name + '"] .mk-so-inline-detail__field-view')
								.text($('<div/>').html(displayValue).text());
						}
						if (mod === 'Leads' && (name === 'phone' || name === 'mk_address')) {
							listPatch[name === 'mk_address' ? 'address' : name] = $(this).val() || '';
						}
						if (mod === 'Contacts' && name === 'mailingstreet') {
							listPatch.address = $(this).val() || '';
						}
						if (mod === 'Leads' && name === 'mk_region') {
							listPatch.region = $(this).val() || '';
						}
					});
					if (mod === 'Leads' && Object.keys(listPatch).length) {
						applyLeadListFieldUpdate(recordId, listPatch);
					}
					if (mod === 'Contacts' && Object.keys(listPatch).length) {
						try {
							document.dispatchEvent(new CustomEvent('mk-contacts-list-field-updated', {
								detail: { id: String(recordId), patch: listPatch }
							}));
						} catch (e) { /* IE */ }
					}
					if (response.description && response.description.value !== undefined) {
						$panel.find('.mk-so-inline-detail__notes-input[name="description"]').val(response.description.value);
					}
					var noteVal = $panel.find('.mk-so-inline-detail__notes-input[name="description"]').val() || '';
					if (response.description && response.description.value !== undefined) {
						noteVal = response.description.value;
					}
					var $row = $('tr.mk-leads-row[data-crmid="' + recordId + '"], tr.mk-leads-row[data-id="' + recordId + '"]').first();
					if ($row.length) {
						var $notesTd = $row.children('td[data-col="notes"]');
						if ($notesTd.length) {
							var short = String(noteVal || '').trim();
							if (!short) {
								$notesTd.html('<span class="mk-leads-muted">—</span>');
							} else {
								var shown = short.length > 80 ? short.slice(0, 80) + '…' : short;
								$notesTd.html(
									'<span class="mk-leads-notes-cell" title="' +
									$('<div/>').text(short).html() +
									'">' +
									$('<div/>').text(shown).html() +
									'</span>'
								);
							}
						}
					}
					if (mod === 'Potentials' && window.PotentialsLocalStore && PotentialsLocalStore.patchOpportunity) {
						PotentialsLocalStore.patchOpportunity(recordId, { notes: noteVal });
					}
					if (mod === 'Leads' && window.LeadsLocalStore && typeof window.LeadsLocalStore.patchLead === 'function') {
						window.LeadsLocalStore.patchLead(recordId, { notes: noteVal });
					}
					if (mod === 'Contacts' && window.ContactsLocalStore && typeof window.ContactsLocalStore.patchContact === 'function') {
						window.ContactsLocalStore.patchContact(recordId, { notes: noteVal });
					}
				} else {
					updateViewValues($panel);
				}
				updateViewValues($panel);
				snapshot = captureSnapshot($panel);
				setEditMode($panel, true);
				if (typeof app !== 'undefined' && app.helper && app.helper.showSuccessNotification) {
					app.helper.showSuccessNotification({
						message: app.vtranslate ? app.vtranslate('JS_RECORD_UPDATED') : 'Đã lưu thay đổi.'
					});
				}
				window.setTimeout(function () {
					if (scrollHost) scrollHost.scrollTop = scrollTop;
					window.scrollTo(0, winScroll);
				}, 0);
			}
			function applyLeadListFieldUpdate(recordId, patch) {
				if (!patch) return;
				try {
					document.dispatchEvent(new CustomEvent('mk-leads-list-field-updated', {
						detail: { id: String(recordId), patch: patch }
					}));
				} catch (e) { /* IE */ }
				// Full row re-render is handled by list event; keep light DOM sync for phone/address.
				var $row = $('tr.mk-leads-row[data-crmid="' + recordId + '"], tr.mk-leads-row[data-id="' + recordId + '"]').first();
				if (!$row.length) return;
				var $tds = $row.children('td.mk-leads-td');
				if (Object.prototype.hasOwnProperty.call(patch, 'phone') && $tds.length > 3) {
					var phone = patch.phone || '';
					$tds.eq(3).html(
						phone
							? '<button type="button" class="mk-leads-inline-edit" data-field="phone" data-lead-id="' + $('<div/>').text(String(recordId)).html() + '" title="Nhấn để sửa">' + $('<div/>').text(phone).html() + '</button>'
							: '<button type="button" class="mk-leads-inline-edit" data-field="phone" data-lead-id="' + $('<div/>').text(String(recordId)).html() + '" title="Nhấn để sửa"><span class="mk-leads-muted">Nhập SĐT</span></button>'
					);
				}
				if (Object.prototype.hasOwnProperty.call(patch, 'address') && $tds.length > 5) {
					var address = patch.address || '';
					$tds.eq(5).html(
						address
							? '<button type="button" class="mk-leads-inline-edit" data-field="address" data-lead-id="' + $('<div/>').text(String(recordId)).html() + '" title="Nhấn để sửa">' + $('<div/>').text(address).html() + '</button>'
							: '<button type="button" class="mk-leads-inline-edit" data-field="address" data-lead-id="' + $('<div/>').text(String(recordId)).html() + '" title="Nhấn để sửa"><span class="mk-leads-muted">Nhập địa chỉ</span></button>'
					);
				}
			}
			function applyLeadCategoriesToList(recId, cats) {
				if (!cats) return;
				var $row = $('tr.mk-leads-row[data-crmid="' + recId + '"], tr.mk-leads-row[data-id="' + recId + '"]').first();
				if (!$row.length) return;
				var $tds = $row.children('td.mk-leads-td');
				var ref = window.LeadsLovableRef;
				function cellHtml(key) {
					if (!key) {
						return '<span class="mk-leads-muted">—</span>';
					}
					var label = key;
					if (ref && ref.labelForTag) {
						label = ref.labelForTag(key);
					} else if (ref && ref.tagMeta) {
						label = (ref.tagMeta(key).label) || key;
					}
					if (/^goi_lan_(\d+)$/.test(String(key)) || /^\d{1,2}$/.test(String(key))) {
						var n = parseInt(RegExp.$1 || key, 10);
						label = 'Gọi lần ' + n;
						key = 'goi_lan_' + n;
					}
					return '<span class="mk-tag" data-tag="' + $('<div/>').text(key).html() + '" title="' + $('<div/>').text(label).html() + '">' +
						$('<div/>').text(label).html() + '</span>';
				}
				// 0 check, 1 created, 2 lead, 3 phone, 4 region, 5 address, 6 source, 7 customer, 8 owner
				if (Object.prototype.hasOwnProperty.call(cats, 'source') && $tds.length > 6) {
					$tds.eq(6).html(cellHtml(cats.source || ''));
				}
				if (Object.prototype.hasOwnProperty.call(cats, 'customer') && $tds.length > 7) {
					$tds.eq(7).html(cellHtml(cats.customer || ''));
				}
				try {
					document.dispatchEvent(new CustomEvent('mk-leads-category-updated', {
						detail: { id: String(recId), categories: cats }
					}));
				} catch (e) { /* IE */ }
			}
			function saveExtras(response) {
				var chain = $.Deferred().resolve(null, null).promise();
				var leadCategoryRes = null;
				var $nextInput = $panel.find('.mk-so-inline-detail__next-action-input');
				var nextLocked = $panel.find('.mk-so-inline-detail__next-action.is-locked').length > 0
					|| ($nextInput.length && ($nextInput.prop('readonly') || $nextInput.prop('disabled')));
				if ($nextInput.length && mod === 'Leads' && !nextLocked) {
					chain = chain.then(function (err) {
						if (err) return $.Deferred().resolve(err, null).promise();
						return postRequest({
							module: 'Leads',
							action: 'ModernApi',
							mode: 'save_next_action',
							record: recordId,
							next_action: $nextInput.val() || ''
						});
					});
				}
				var getTags = $panel.data('mkGetEditableTags');
				var $region = $panel.find(':input[name="mk_region"]');
				var $address = $panel.find(':input[name="mk_address"]');
				if (mod === 'Leads' && (typeof getTags === 'function' || $region.length || $address.length)) {
					chain = chain.then(function (err) {
						if (err) return $.Deferred().resolve(err, null).promise();
						var tags = typeof getTags === 'function' ? getTags().slice() : [];
						var regionKey = $region.length ? String($region.val() || '').toLowerCase() : '';
						tags = tags.filter(function (t) {
							var k = slugifyInlineTag(t);
							return k !== 'vang' && k !== 'bac' && k !== 'dong' && !/^kv[123]$/i.test(String(t || ''));
						});
						if (regionKey && /^kv[123]$/.test(regionKey)) {
							tags.push(regionKey);
						}
						var addressVal = $address.length ? ($address.val() || '') : '';
						var districtLabel = regionKey ? ('Khu vực ' + regionKey.replace('kv', '')) : '';
						var payload = {
							tags: tags,
							district: districtLabel,
							address: addressVal,
							area: districtLabel && addressVal ? (districtLabel + ', ' + addressVal) : (districtLabel || addressVal)
						};
						return postRequest({
							module: 'Leads',
							action: 'ModernApi',
							mode: 'save',
							record: recordId,
							payload: JSON.stringify(payload)
						}).then(function (eTags, rTags) {
							if (!eTags && rTags && rTags.lead) {
								if (rTags.lead.tags) {
									refreshInlineTags($panel, rTags.lead.tags);
								}
								applyLeadListFieldUpdate(recordId, {
									address: addressVal,
									region: regionKey,
									tags: (rTags.lead && rTags.lead.tags) || tags
								});
								try {
									document.dispatchEvent(new CustomEvent('mk-leads-list-field-updated', {
										detail: {
											id: String(recordId),
											patch: {
												tags: (rTags.lead && rTags.lead.tags) || tags,
												address: addressVal,
												district: districtLabel,
												area: payload.area
											}
										}
									}));
								} catch (e) { /* IE */ }
							}
							return $.Deferred().resolve(eTags, rTags).promise();
						});
					});
				}
				var $src = $panel.find(':input[name="mk_source"]');
				var $cust = $panel.find(':input[name="mk_customer"]');
				if (mod === 'Leads' && ($src.length || $cust.length)) {
					chain = chain.then(function (err) {
						if (err) return $.Deferred().resolve(err, null).promise();
						return postRequest({
							module: 'Leads',
							action: 'ModernApi',
							mode: 'save_inline_category_tags',
							record: recordId,
							mk_source: $src.length ? ($src.val() || '') : '',
							mk_customer: $cust.length ? ($cust.val() || '') : ''
						}).then(function (e2, r2) {
							if (!e2) leadCategoryRes = r2;
							return $.Deferred().resolve(e2, r2).promise();
						});
					});
				}
				var $confirmInput = $panel.find('.mk-so-inline-detail__field[data-field-name="mk_confirm_tag"] :input[name="mk_confirm_tag"]');
				if ($confirmInput.length && mod === 'Potentials') {
					chain = chain.then(function (err) {
						if (err) return $.Deferred().resolve(err, null).promise();
						return postRequest({
							module: 'Potentials',
							action: 'ModernApi',
							mode: 'save_confirm_tag',
							record: recordId,
							confirm: $confirmInput.val() || ''
						});
					});
				}
				var $oppRegion = $panel.find(':input[name="mk_region"]');
				var $oppAddress = $panel.find(':input[name="mk_address"]');
				if (mod === 'Potentials' && ($oppRegion.length || $oppAddress.length)) {
					chain = chain.then(function (err, prevRes) {
						if (err) return $.Deferred().resolve(err, null).promise();
						return postRequest({
							module: 'Potentials',
							action: 'ModernApi',
							mode: 'save_inline_location',
							record: recordId,
							mk_region: $oppRegion.length ? ($oppRegion.val() || '') : '',
							mk_address: $oppAddress.length ? ($oppAddress.val() || '') : ''
						}).then(function (eLoc, rLoc) {
							if (!eLoc && rLoc) {
								if (window.PotentialsLocalStore && PotentialsLocalStore.patchOpportunity) {
									PotentialsLocalStore.patchOpportunity(recordId, {
										address: rLoc.address || '',
										district: rLoc.district || '',
										tags: rLoc.tags || undefined
									});
								}
								var $row = $('tr.mk-leads-row[data-crmid="' + recordId + '"], tr.mk-leads-row[data-id="' + recordId + '"]').first();
								if ($row.length) {
									var regionLabel = '';
									var rk = String(($oppRegion.val() || '')).toLowerCase();
									if (/^kv([123])$/.test(rk)) regionLabel = 'Khu vực ' + RegExp.$1;
									$row.children('td[data-col="region"]').html(
										regionLabel
											? $('<div/>').text(regionLabel).html()
											: '<span class="mk-leads-muted">—</span>'
									);
									var addr = $oppAddress.length ? String($oppAddress.val() || '').trim() : '';
									$row.children('td[data-col="address"]').html(
										addr
											? $('<div/>').text(addr).html()
											: '<span class="mk-leads-muted">—</span>'
									);
								}
								if (rLoc.tags) refreshInlineTags($panel, rLoc.tags);
							}
							return $.Deferred().resolve(eLoc || null, prevRes || rLoc).promise();
						});
					});
				}
				var getEditableTags = $panel.data('mkGetEditableTags');
				if ((mod === 'Potentials' || mod === 'Contacts') && typeof getEditableTags === 'function') {
					chain = chain.then(function (err) {
						if (err) return $.Deferred().resolve(err, null).promise();
						var tags = getEditableTags().slice();
						return postRequest({
							module: mod,
							action: 'ModernApi',
							mode: 'save_tags',
							record: recordId,
							tags: JSON.stringify(tags)
						}).then(function (eTags, rTags) {
							if (!eTags && rTags && rTags.tags) {
								refreshInlineTags($panel, rTags.tags);
								if (mod === 'Potentials' && window.PotentialsLocalStore && PotentialsLocalStore.patchOpportunity) {
									PotentialsLocalStore.patchOpportunity(recordId, { tags: rTags.tags });
								}
								if (mod === 'Contacts' && window.ContactsLocalStore && ContactsLocalStore.patchContact) {
									ContactsLocalStore.patchContact(recordId, { tags: rTags.tags });
								}
								try {
									document.dispatchEvent(new CustomEvent('mk-record-tags-updated', {
										detail: { module: mod, id: String(recordId), tags: rTags.tags }
									}));
								} catch (e) { /* IE */ }
							}
							return $.Deferred().resolve(eTags, rTags).promise();
						});
					});
				}
				$.when(chain).then(function (extraErr, extraRes) {
					$saveBtn.prop('disabled', false);
					if (extraErr) {
						showSaveError(extraErr);
						return;
					}
					if (mod === 'Potentials' && $confirmInput.length) {
						var confirmKey = $confirmInput.val() || '';
						if (extraRes && extraRes.confirm !== undefined) {
							confirmKey = extraRes.confirm || '';
						}
						var confirmedAt =
							(extraRes && (extraRes.confirmed_at || extraRes.confirmed_at_label)) || '';
						applyOppConfirmToList(recordId, confirmKey, confirmedAt);
						if (extraRes && extraRes.tags) {
							refreshInlineTags($panel, extraRes.tags);
						}
					}
					if (mod === 'Leads' && leadCategoryRes) {
						if (leadCategoryRes.tags) {
							refreshInlineTags($panel, leadCategoryRes.tags);
						}
						if (leadCategoryRes.categories) {
							applyLeadCategoriesToList(recordId, leadCategoryRes.categories);
						}
					}
					finishSave(response);
				});
			}
			$.when(postRequest(postData)).then(function (err, response) {
				if (err) {
					$saveBtn.prop('disabled', false);
					showSaveError(err);
					return;
				}
				saveExtras(response);
			});
		});
	}

	function loadDetail(recordId, $row, $table) {
		var c = cfg();
		var colspan = getColspan($table);
		var $detailRow = $(
			'<tr class="mk-so-inline-detail-row">' +
				'<td colspan="' + colspan + '">' +
					'<div class="mk-so-inline-detail mk-so-inline-detail--loading">' +
						'<span class="mk-so-inline-detail__spinner" aria-hidden="true"></span>' +
						'<span>' + (c.loadingText || 'Đang tải chi tiết...') + '</span>' +
					'</div>' +
				'</td>' +
			'</tr>'
		);
		$row.after($detailRow);

		$.ajax({
			url: 'index.php',
			type: 'GET',
			dataType: 'html',
			data: {
				module: moduleName(),
				view: 'Detail',
				mode: 'showListInlineDetail',
				record: recordId,
				app: (document.body.getAttribute('data-app') || 'SALES')
			}
		}).done(function (html) {
			if (expandedId !== String(recordId)) {
				return;
			}
			$detailRow.find('td').html(html);
			initPanel($detailRow);
		}).fail(function () {
			if (expandedId !== String(recordId)) {
				return;
			}
			$detailRow.find('td').html(
				'<div class="mk-so-inline-detail mk-so-inline-detail--error">' +
					(c.errorText || 'Không tải được chi tiết.') +
					' <a href="' + ($row.data('recordurl') || '#') + '">Mở trang chi tiết</a>.' +
				'</div>'
			);
		}).always(function () {
			loading = false;
		});
	}

	function toggle(recordId, $row) {
		var $table = $row.closest(tableSelector());
		if (!$table.length) {
			$table = $row.closest('table');
		}
		if (!$table.length) {
			return;
		}
		recordId = String(recordId || '');
		if (!recordId) {
			return;
		}
		if (expandedId === recordId) {
			collapse($table);
			return;
		}
		if (loading) {
			return;
		}
		collapse($table);
		expandedId = recordId;
		loading = true;
		$row.addClass('mk-so-row-expanded');
		loadDetail(recordId, $row, $table);
	}

	function isInteraction(target) {
		return !!(target.closest && target.closest(
			'.mk-so-inline-detail, .mk-so-inline-detail-row, .mk-so-pos-star-btn, .mk-so-pos-control-td, .mk-leads-td--check, .mk-leads-inline-edit, .mk-leads-inline-input, .mk-leads-region-select, .mk-leads-tags-edit, #mk-leads-tag-popover, a, button, input, select, textarea, .dropdown-menu, label.mk-leads-check'
		));
	}

	function onClick(e) {
		if (!isPosEnabled()) {
			return;
		}
		var mod = moduleName();
		var bodyMod = document.body && document.body.getAttribute('data-module');
		if (mod && bodyMod && mod !== bodyMod) {
			return;
		}
		var target = e.target;
		if (!target || !target.closest || isInteraction(target)) {
			return;
		}
		var row = target.closest(tableSelector() + ' ' + rowSelector());
		if (!row) {
			row = target.closest(rowSelector());
		}
		if (!row) {
			return;
		}
		if (window.getSelection && String(window.getSelection()).trim().length > 0) {
			return;
		}
		if ($(row).hasClass('edited')) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		if (typeof e.stopImmediatePropagation === 'function') {
			e.stopImmediatePropagation();
		}
		toggle(resolveRecordId(row), $(row));
	}

	function bind() {
		if (document.documentElement.getAttribute('data-mk-pos-inline-bound')) {
			return;
		}
		document.documentElement.setAttribute('data-mk-pos-inline-bound', '1');
		document.addEventListener('click', onClick, true);
	}

	$(document).ready(bind);
	window.MkSalesPosInline = { bind: bind, collapse: collapse, toggle: toggle };
})(jQuery);
