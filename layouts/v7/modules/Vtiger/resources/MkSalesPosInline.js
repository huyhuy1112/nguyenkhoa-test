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
		$panel.find('.mk-so-inline-detail__notes-input').prop('readonly', !isEdit);
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
			var $focus = $panel.find('.mk-so-inline-detail__next-action-input');
			if (!$focus.length) {
				$focus = $panel.find('.mk-so-inline-detail__notes-input[name="description"]');
			}
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
				if (!tags.length) {
					$list.html('<span class="mk-so-inline-detail__tags-empty">Chưa có tag</span>');
					return;
				}
				var ref = window.PotentialsLovableRef || window.LeadsLovableRef;
				var html = tags.map(function (raw) {
					var key = String(raw || "").trim();
					var label = key;
					var cls = "mk-tag";
					if (ref && ref.normalizeTag) {
						key = ref.normalizeTag(raw) || key;
					} else {
						key = slugifyInlineTag(raw) || key;
					}
					if (ref && ref.tagMeta) {
						var meta = ref.tagMeta(key) || ref.tagMeta(raw);
						label = meta.label || label;
						if (meta.cls) cls = meta.cls;
					} else if (key) {
						cls = "mk-tag mk-tag--" + String(key).replace(/_/g, "-");
					}
					return '<span class="' + cls + '" data-tag="' + $('<div/>').text(key).html() + '" title="' + $('<div/>').text(String(raw)).html() + '">' +
						$('<div/>').text(String(label)).html() + '</span>';
				}).join('');
				$list.html(html);
			}
			function applyOppConfirmToList(recordId, confirmKey) {
				if (window.PotentialsLocalStore && typeof window.PotentialsLocalStore.setConfirmTag === 'function') {
					window.PotentialsLocalStore.setConfirmTag(recordId, confirmKey);
				}
				try {
					document.dispatchEvent(new CustomEvent('mk-opps-confirm-updated', {
						detail: { id: String(recordId), confirm: confirmKey || '' }
					}));
				} catch (e) { /* IE */ }
				var $row = $('tr.mk-leads-row[data-crmid="' + recordId + '"], tr.mk-leads-row[data-id="' + recordId + '"]').first();
				if (!$row.length) return;
				var $td = $row.children('td.mk-leads-td').eq(10);
				if (!$td.length) return;
				if (!confirmKey) {
					$td.html('<span class="mk-leads-muted">—</span>');
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
			}
			function finishSave(response) {
				if (response) {
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
					});
					if (response.description && response.description.value !== undefined) {
						$panel.find('.mk-so-inline-detail__notes-input[name="description"]').val(response.description.value);
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
					if (ref && ref.tagMeta) {
						label = (ref.tagMeta(key).label) || key;
					} else if (window.Vtiger === undefined) {
						/* keep key */
					}
					var known = {
						facebook: 'Facebook', tiktok: 'TikTok', website: 'Website', zalo: 'Zalo', other: 'Khác',
						individual: 'Cá nhân', company: 'Công ty', co_quan: 'Có quán', chuan_bi_mo: 'Chuẩn bị mở', gia_dinh: 'Gia đình',
						mua_lan_dau: 'Mua lần đầu', mua_lai: 'Mua lại', khong_mua: 'Không mua', ngung_mua: 'Ngừng mua',
						vang: 'Vàng', bac: 'Bạc', dong: 'Đồng'
					};
					if (known[key]) label = known[key];
					return '<span class="mk-tag" data-tag="' + $('<div/>').text(key).html() + '">' +
						$('<div/>').text(label).html() + '</span>';
				}
				// 0 check, 1 created, 2 lead, 3 phone, 4 area, 5 source, 6 customer, 7 tier
				if (Object.prototype.hasOwnProperty.call(cats, 'source') && $tds.length > 5) {
					$tds.eq(5).html(cellHtml(cats.source || ''));
				}
				if (Object.prototype.hasOwnProperty.call(cats, 'customer') && $tds.length > 6) {
					$tds.eq(6).html(cellHtml(cats.customer || ''));
				}
				if (Object.prototype.hasOwnProperty.call(cats, 'tier') && $tds.length > 7) {
					$tds.eq(7).html(cellHtml(cats.tier || ''));
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
				if ($nextInput.length && mod === 'Leads') {
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
				var $src = $panel.find(':input[name="mk_source"]');
				var $cust = $panel.find(':input[name="mk_customer"]');
				var $stage = $panel.find(':input[name="mk_stage"]');
				var $tier = $panel.find(':input[name="mk_tier"]');
				if (mod === 'Leads' && ($src.length || $cust.length || $stage.length || $tier.length)) {
					chain = chain.then(function (err) {
						if (err) return $.Deferred().resolve(err, null).promise();
						return postRequest({
							module: 'Leads',
							action: 'ModernApi',
							mode: 'save_inline_category_tags',
							record: recordId,
							mk_source: $src.length ? ($src.val() || '') : '',
							mk_customer: $cust.length ? ($cust.val() || '') : '',
							mk_stage: $stage.length ? ($stage.val() || '') : '',
							mk_tier: $tier.length ? ($tier.val() || '') : ''
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
						applyOppConfirmToList(recordId, confirmKey);
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
			'.mk-so-inline-detail, .mk-so-inline-detail-row, .mk-so-pos-star-btn, .mk-so-pos-control-td, .mk-leads-td--check, a, button, input, select, textarea, .dropdown-menu, label.mk-leads-check'
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
