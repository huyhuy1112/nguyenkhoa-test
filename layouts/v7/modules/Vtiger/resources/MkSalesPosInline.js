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

	function setEditMode($panel, enable) {
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

	function initPanel($detailRow) {
		var $panel = $detailRow.find('.mk-so-inline-detail');
		if (!$panel.length || $panel.data('mkPosInlineInit')) {
			return;
		}
		$panel.data('mkPosInlineInit', true);
		var recordId = String($panel.data('record-id') || '');
		var mod = String($panel.data('module') || moduleName());
		var snapshot = captureSnapshot($panel);

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
			setEditMode($panel, false);
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
					if (ref && ref.normalizeTag) {
						key = ref.normalizeTag(raw);
					}
					if (ref && ref.tagMeta) {
						var meta = ref.tagMeta(raw);
						label = meta.label || label;
					}
					return '<span class="mk-tag" data-tag="' + $('<div/>').text(key).html() + '" title="' + $('<div/>').text(String(raw)).html() + '">' +
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
				setEditMode($panel, false);
				if (typeof app !== 'undefined' && app.helper && app.helper.showSuccessNotification) {
					app.helper.showSuccessNotification({
						message: app.vtranslate ? app.vtranslate('JS_RECORD_UPDATED') : 'Đã lưu thay đổi.'
					});
				}
			}
			function saveExtras(response) {
				var chain = $.Deferred().resolve(null, null).promise();
				var $nextInput = $panel.find('.mk-so-inline-detail__next-action-input');
				if ($nextInput.length && mod === 'Leads') {
					chain = chain.then(function () {
						return postRequest({
							module: 'Leads',
							action: 'ModernApi',
							mode: 'save_next_action',
							record: recordId,
							next_action: $nextInput.val() || ''
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
