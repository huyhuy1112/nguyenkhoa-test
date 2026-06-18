/**
 * Campaigns enterprise redesign — ONLY content area.
 * Builds hero/KPIs/cards/timeline/accordion and moves existing vtiger fields into them.
 */
(function ($) {
	'use strict';

	function isScoped() {
		return (
			$('body').data('module') === 'Campaigns' &&
			$('body').data('view') === 'Edit' &&
			$('body').data('app') === 'MARKETING' &&
			$('#mkCampEnterpriseRoot').length &&
			$('#mkCampXNativeHost').length
		);
	}

	function $form() {
		var $f = $('#mkCampEnterpriseRoot').children('form#EditView, form[name="edit"]').first();
		if ($f.length) {
			return $f;
		}
		var $f2 = $('#mkCampXNativeHost').find('form#EditView, form[name="edit"]').first();
		if ($f2.length) {
			return $f2;
		}
		return $('form#EditView, form[name="edit"]').first();
	}

	function pickField(nameCandidates) {
		for (var i = 0; i < nameCandidates.length; i++) {
			var n = nameCandidates[i];
			var $el = $form().find('[name="' + n + '"]').first();
			if (!$el.length) {
				// Fallback search (in case widgets are moved or nested differently)
				$el = $('[name="' + n + '"]').first();
			}
			if (!$el.length) {
				// Vtiger often stores fieldname on containers
				$el = $form().find('[data-fieldname="' + n + '"] input, [data-fieldname="' + n + '"] select, [data-fieldname="' + n + '"] textarea').first();
			}
			if ($el.length) return $el;
		}
		return $();
	}

	function pickFieldByLabel(labelRegexes) {
		var $f = $form();
		if (!$f.length) return $();
		var regs = (labelRegexes || []).filter(Boolean).map(function (r) {
			return r instanceof RegExp ? r : new RegExp(String(r), 'i');
		});
		if (!regs.length) return $();

		var $labels = $f.find('td.fieldLabel, .fieldLabel');
		var found = $();
		$labels.each(function () {
			var $l = $(this);
			var txt = ($l.text() || '').replace(/\s+/g, ' ').trim();
			for (var i = 0; i < regs.length; i++) {
				if (regs[i].test(txt)) {
					// Most vtiger edit layouts place the value td right after label td
					var $val = $l.nextAll('td.fieldValue').first();
					if (!$val.length) {
						// Sometimes value is a sibling in the same row but not immediate next
						$val = $l.closest('tr').find('td.fieldValue').first();
					}
					var $refWrap = $val.find('.referencefield-wrapper').first();
					if ($refWrap.length) {
						var $src = $refWrap.find('input.sourceField').first();
						if ($src.length) {
							found = $src;
							return false;
						}
					}
					var $el = $val.find('input.sourceField, input[name], select[name], textarea[name]').not('[name="popupReferenceModule"]').first();
					if ($el.length) {
						found = $el;
						return false;
					}
				}
			}
		});
		return found;
	}

	function pickSmart(names, labelRegexes) {
		var $el = pickField(names || []);
		if ($el.length) return $el;
		return pickFieldByLabel(labelRegexes || []);
	}

	function getFieldLabelText($fieldEl) {
		if (!$fieldEl.length) return '';
		var $td = $fieldEl.closest('td');
		if (!$td.length) return '';
		var $labelTd = $td.prevAll('td.fieldLabel').first();
		if (!$labelTd.length) {
			// Sometimes label is two cells back in vtiger tables
			$labelTd = $td.prevAll('td').first();
		}
		var txt = $.trim($labelTd.text() || '');
		txt = txt.replace(/\s*\*+\s*$/, '');
		txt = txt.replace(/\s*:\s*$/, '');
		return txt;
	}

	function isRequired($fieldEl) {
		var $td = $fieldEl.closest('td');
		var $labelTd = $td.prevAll('td.fieldLabel').first();
		return $labelTd.find('.redColor, .mandatoryField').length > 0 || /\*/.test($labelTd.text() || '');
	}

	function ensureEnterpriseInsideForm() {
		var $root = $('#mkCampEnterpriseRoot');
		if (!$root.length) {
			return false;
		}

		var $native = $('#mkCampXNativeHost');
		var $editForm = $form();
		if (!$editForm.length) {
			return false;
		}

		// Lift the form out of the hidden native shelf so enterprise UI stays visible.
		// IMPORTANT: mkCampXNativeHost contains the form (EditView.tpl), so never append
		// mkCampXNativeHost back into the form (would create self-nesting).
		var formWasInsideNative = $native.length && $native.has($editForm).length > 0;
		if ($editForm.closest('.mk-campx-native').length) {
			var $bc = $root.children('nav.mk-campx-breadcrumb').first();
			if ($bc.length) {
				$editForm.insertAfter($bc);
			} else {
				$root.prepend($editForm);
			}
		}

		// Keep hero / KPI / section cards inside the form (for submit) but outside the hidden shelf.
		$root.children('.mk-campx-hero, .mk-campx-kpis, section').appendTo($editForm);

		// If the native host used to contain the form, it is now effectively empty.
		// Keep it in DOM (hidden) so any selectors depending on it don't break.
		if (formWasInsideNative) {
			try {
				$native.empty().append('<div class="mk-campx-native-sentinel" aria-hidden="true"></div>');
			} catch (e0) {}
		}

		return true;
	}

	function moveFieldInto($fieldEl, $mount, opts) {
		opts = opts || {};
		if (!$fieldEl.length || !$mount.length) return;

		var $refWrap = $fieldEl.closest('.referencefield-wrapper');
		if ($refWrap.length && !$fieldEl.hasClass('referencefield-wrapper')) {
			$fieldEl = $refWrap.find('input.sourceField').first().length
				? $refWrap.find('input.sourceField').first()
				: $fieldEl;
		}

		if ($fieldEl.data('mkCampxMoved')) {
			var $wrap0 = $fieldEl.closest('.mk-campx-field, .mk-campx-stat__control');
			if ($wrap0.length) {
				$mount.append($wrap0);
			}
			return;
		}

		var $valueTd = $fieldEl.closest('td.fieldValue');
		if (!$valueTd.length) {
			$valueTd = $fieldEl.closest('td');
		}
		if (!$valueTd.length) {
			$valueTd = $fieldEl.closest('.fieldValue');
		}

		var $moveBlock = $refWrap.length ? $refWrap : null;

		if (opts.compact) {
			var $ctlCompact = $mount;
			if (!$ctlCompact.hasClass('mk-campx-stat__control')) {
				$ctlCompact = $('<div class="mk-campx-stat__control fieldValue" />');
				$mount.append($ctlCompact);
			} else {
				$ctlCompact.addClass('fieldValue');
			}
			if ($moveBlock && $moveBlock.length) {
				$ctlCompact.append($moveBlock);
			} else {
				$ctlCompact.append($valueTd.contents());
			}
			try {
				$valueTd.empty();
			} catch (e0) {}
			$fieldEl.data('mkCampxMoved', true);
			return;
		}

		var label = opts.label || getFieldLabelText($fieldEl) || opts.fallbackLabel || '';
		var $wrap = $('<div class="mk-campx-field" />');
		var $lab = $('<div class="mk-campx-field__label" />').text(label || 'Field');
		if (isRequired($fieldEl)) {
			$lab.append($('<span class="mk-campx-req">*</span>'));
		}
		var $ctl = $('<div class="mk-campx-field__control fieldValue" />');
		$wrap.append($lab).append($ctl);
		if ($moveBlock && $moveBlock.length) {
			$ctl.append($moveBlock);
		} else {
			$ctl.append($valueTd.contents());
		}
		$mount.append($wrap);
		try {
			$valueTd.empty();
		} catch (e) {}
		$fieldEl.data('mkCampxMoved', true);
	}

	function reinitFieldWidgets() {
		var $editForm = $form();
		var $scope = $('#mkCampEnterpriseRoot');
		if (!$scope.length) {
			return;
		}
		var $bindContainer = $editForm.length ? $editForm : $(document);

		$scope.find('input.autoComplete').each(function () {
			var $inp = $(this);
			if ($inp.hasClass('ui-autocomplete-input')) {
				try {
					$inp.autocomplete('destroy');
				} catch (e1) {}
			}
		});

		if (window.Vtiger_Edit_Js) {
			var editJs = Vtiger_Edit_Js.getInstance();
			if (editJs) {
				editJs.registerAutoCompleteFields($bindContainer);
				editJs.referenceModulePopupRegisterEvent($bindContainer);
				editJs.registerReferenceCreate($bindContainer);
				editJs.registerClearReferenceSelectionEvent($bindContainer);
			}
		}

		registerMultiReferenceFields();
		registerSingleReferenceFields();

		if (window.vtUtils) {
			if (vtUtils.applyFieldElementsView) {
				vtUtils.applyFieldElementsView($scope);
			}
			$scope.find('select.select2').each(function () {
				var $s = $(this);
				if ($s.data('select2')) {
					try {
						$s.select2('destroy');
					} catch (e2) {}
				}
			});
			if (vtUtils.showSelect2ElementView) {
				vtUtils.showSelect2ElementView($scope.find('select.select2'));
			}
		}
	}

	function isMultiReferenceField($src) {
		if (!$src || !$src.length) {
			return false;
		}
		return $src.data('multiple') === true || String($src.attr('data-multiple')).toLowerCase() === 'true';
	}

	function getReferenceDisplayEl(fieldName) {
		var $d = $form().find('[name="' + fieldName + '_display"]');
		if (!$d.length) {
			$d = pickField([fieldName + '_display']);
		}
		return $d;
	}

	function getReferenceSearchModule($wrap) {
		var mod = $wrap.find('input[name="popupReferenceModule"]').val();
		return mod || 'ProductsServices';
	}

	function syncMultiRefHidden(fieldName) {
		var $disp = getReferenceDisplayEl(fieldName);
		var $src = pickField([fieldName]);
		if (!$disp.length || !$src.length || !$disp.data('select2')) {
			return;
		}
		var data = $disp.select2('data') || [];
		var ids = [];
		for (var i = 0; i < data.length; i++) {
			if (data[i] && data[i].id) {
				ids.push(String(data[i].id));
			}
		}
		$src.val(ids.join(','));
		if (ids.length) {
			$src.closest('.referencefield-wrapper').addClass('selected');
		} else {
			$src.closest('.referencefield-wrapper').removeClass('selected');
		}
	}

	function initMultiReferenceSelect2(fieldName, searchModule) {
		var $disp = getReferenceDisplayEl(fieldName);
		var $src = pickField([fieldName]);
		if (!$disp.length || !$src.length) {
			return;
		}
		if ($disp.data('mkCampxMultiRefInit')) {
			return;
		}

		if ($disp.hasClass('ui-autocomplete-input')) {
			try {
				$disp.autocomplete('destroy');
			} catch (ignoreAc) {}
		}
		if ($disp.data('select2')) {
			try {
				$disp.select2('destroy');
			} catch (ignoreS2) {}
		}

		$disp.removeAttr('readonly').removeAttr('disabled');

		$disp.select2({
			minimumInputLength: 3,
			multiple: true,
			width: '100%',
			ajax: {
				url: 'index.php',
				dataType: 'json',
				quietMillis: 300,
				data: function (term) {
					return {
						module: searchModule,
						action: 'BasicAjax',
						search_module: searchModule,
						search_value: term
					};
				},
				results: function (data) {
					var rows = data.result || data.results || [];
					var out = [];
					for (var i = 0; i < rows.length; i++) {
						var r = rows[i];
						out.push({
							id: String(r.id || r.record || ''),
							text: r.label || r.name || String(r.id || '')
						});
					}
					return { results: out };
				},
				transport: function (params) {
					return $.ajax(params);
				}
			},
			dropdownCss: { 'z-index': 10001 }
		});

		$disp.on('change.mkCampxMultiRef', function () {
			syncMultiRefHidden(fieldName);
		});

		$disp.data('mkCampxMultiRefInit', true);
		fillMultiReferenceFromSaved(fieldName);
	}

	function fillMultiReferenceFromSaved(fieldName) {
		var $src = pickField([fieldName]);
		var $disp = getReferenceDisplayEl(fieldName);
		var $wrap = $src.closest('.referencefield-wrapper');
		if (!$disp.length || !$disp.data('select2')) {
			return;
		}

		var preset = [];
		var $info = $wrap.find('input[type="hidden"][name*="related"]');
		if ($info.length) {
			var saved = $info.data('value');
			if (saved && typeof saved === 'object') {
				for (var id in saved) {
					if (!Object.prototype.hasOwnProperty.call(saved, id)) {
						continue;
					}
					var row = saved[id];
					preset.push({
						id: String(id),
						text: (row && (row.name || row.text)) || String(id)
					});
				}
			}
		}

		if (!preset.length) {
			var rawIds = String($src.val() || '').split(',').map(function (x) {
				return $.trim(x);
			}).filter(Boolean);
			var rawNames = String($disp.val() || '').split(',').map(function (x) {
				return $.trim(x);
			}).filter(Boolean);
			for (var i = 0; i < rawIds.length; i++) {
				preset.push({
					id: rawIds[i],
					text: rawNames[i] || rawIds[i]
				});
			}
		}

		if (preset.length) {
			$disp.select2('data', preset);
			syncMultiRefHidden(fieldName);
		}
	}

	function addMultiReferenceItems(fieldName, result) {
		var items = (result && result.data) ? result.data : [];
		if (!items.length) {
			return;
		}
		var $src = pickField([fieldName]);
		var $wrap = $src.closest('.referencefield-wrapper');
		var searchModule = getReferenceSearchModule($wrap);
		initMultiReferenceSelect2(fieldName, searchModule);

		var $disp = getReferenceDisplayEl(fieldName);
		if (!$disp.data('select2')) {
			return;
		}

		var current = $disp.select2('data') || [];
		var byId = {};
		var i;
		for (i = 0; i < current.length; i++) {
			if (current[i] && current[i].id) {
				byId[String(current[i].id)] = current[i];
			}
		}
		for (i = 0; i < items.length; i++) {
			var it = items[i];
			if (!it || !it.id) {
				continue;
			}
			byId[String(it.id)] = {
				id: String(it.id),
				text: it.name || it.text || String(it.id)
			};
		}
		var merged = [];
		for (var k in byId) {
			if (Object.prototype.hasOwnProperty.call(byId, k)) {
				merged.push(byId[k]);
			}
		}
		$disp.select2('data', merged);
		syncMultiRefHidden(fieldName);
	}

	function registerSingleReferenceFields() {
		var $f = $form();
		if (!$f.length || !window.Vtiger_Edit_Js) {
			return;
		}

		$f.find('input.sourceField').each(function () {
			var $src = $(this);
			if (isMultiReferenceField($src)) {
				return;
			}
			var fieldName = $src.attr('name');
			if (!fieldName || fieldName === 'popupReferenceModule') {
				return;
			}

			var $wrap = $src.closest('.referencefield-wrapper');
			var $disp = getReferenceDisplayEl(fieldName);
			if ($disp.length && $disp.prop('disabled')) {
				$disp.removeAttr('disabled');
			}

			$src.off(Vtiger_Edit_Js.referenceSelectionEvent + '.mkCampx');
			$src.on(Vtiger_Edit_Js.referenceSelectionEvent + '.mkCampx', function (e, data) {
				if (!data) {
					return;
				}
				var id = data.record || data.id || $src.val();
				var name = data.selectedName || data.name || '';
				if (id) {
					$src.val(id);
				}
				if ($disp.length && name) {
					$disp.val(name).attr('readonly', 'readonly').removeAttr('disabled');
				}
				if ($wrap.length) {
					$wrap.addClass('selected');
					$wrap.find('.clearReferenceSelection').removeClass('hide');
				}
			});
		});
	}

	function registerMultiReferenceFields() {
		var $f = $form();
		if (!$f.length) {
			return;
		}

		$f.find('input.sourceField').each(function () {
			var $src = $(this);
			if (!isMultiReferenceField($src)) {
				return;
			}
			var fieldName = $src.attr('name');
			if (!fieldName) {
				return;
			}
			var $wrap = $src.closest('.referencefield-wrapper');
			var searchModule = getReferenceSearchModule($wrap);
			initMultiReferenceSelect2(fieldName, searchModule);

			$src.off('Vtiger.MultiReference.Selection.mkCampx');
			if (window.Vtiger_Edit_Js && Vtiger_Edit_Js.refrenceMultiSelectionEvent) {
				$src.on(Vtiger_Edit_Js.refrenceMultiSelectionEvent + '.mkCampx', function (e, result) {
					addMultiReferenceItems(fieldName, result);
				});
			}
		});
	}

	function fmt(val) {
		var s = String(val == null ? '' : val).trim();
		if (!s) return '—';
		// keep % as-is
		if (/%$/.test(s)) return s;
		// number format
		var n = parseFloat(s.replace(/[, ]/g, ''));
		if (!isNaN(n) && isFinite(n)) {
			try {
				return n.toLocaleString(undefined, { maximumFractionDigits: 2 });
			} catch (e) {
				return String(n);
			}
		}
		return s;
	}

	function statusToBadge(statusRaw) {
		var s = (statusRaw || '').toString().toLowerCase();
		if (s.indexOf('planning') >= 0) return { cls: 'mk-campx-badge--blue', label: statusRaw || 'Planning' };
		if (s.indexOf('active') >= 0) return { cls: 'mk-campx-badge--green', label: statusRaw || 'Active' };
		if (s.indexOf('complete') >= 0) return { cls: 'mk-campx-badge--gray', label: statusRaw || 'Completed' };
		if (s.indexOf('cancel') >= 0) return { cls: 'mk-campx-badge--red', label: statusRaw || 'Cancelled' };
		// fallback
		return { cls: 'mk-campx-badge--gray', label: statusRaw || 'Status' };
	}

	function isPlaceholderOptionText(txt) {
		var s = (txt || '').toString().trim().toLowerCase();
		return s === '' || s === 'select an option' || s === '-- select --' || s === '--select--';
	}

	function setSelectByText($sel, targets) {
		if (!$sel.length || !$sel.is('select')) return false;
		var t = (targets || []).map(function (x) { return String(x || '').trim().toLowerCase(); }).filter(Boolean);
		if (!t.length) return false;
		var foundVal = null;
		$sel.find('option').each(function () {
			var $o = $(this);
			var ot = ($.trim($o.text() || '')).toLowerCase();
			for (var i = 0; i < t.length; i++) {
				if (ot === t[i]) {
					foundVal = $o.attr('value');
					return false;
				}
			}
		});
		if (foundVal == null) return false;
		$sel.val(foundVal).trigger('change');
		return true;
	}

	function buildTimeline(currentIdx) {
		var steps = ['Planning', 'Launch', 'Execution', 'Optimization', 'Completed'];
		var $t = $('#mkCampXTimeline').empty();
		for (var i = 0; i < steps.length; i++) {
			var state = i < currentIdx ? 'done' : i === currentIdx ? 'current' : 'todo';
			var $s = $('<div class="mk-campx-step" />').addClass(state === 'done' ? 'mk-campx-step--done' : state === 'current' ? 'mk-campx-step--current' : '');
			$s.append('<div class="mk-campx-step__dot" aria-hidden="true"></div>');
			$s.append($('<div class="mk-campx-step__label" />').text(steps[i]));
			$s.attr('role', 'button').attr('tabindex', '0').attr('data-step', String(i)).css('cursor', 'pointer');
			$t.append($s);
		}
	}

	function inferTimelineIndex(statusRaw) {
		var s = (statusRaw || '').toString().toLowerCase();
		if (s.indexOf('planning') >= 0) return 0;
		if (s.indexOf('launch') >= 0) return 1;
		if (s.indexOf('active') >= 0 || s.indexOf('execution') >= 0) return 2;
		if (s.indexOf('optimiz') >= 0) return 3;
		if (s.indexOf('complete') >= 0) return 4;
		if (s.indexOf('cancel') >= 0) return 2;
		return 0;
	}

	function ensurePhaseField($grid, phase, suffix, label, isTextarea) {
		if (!$grid.length) return;
		var name = 'phase' + phase + '_' + suffix;
		if ($grid.find('[name="' + name + '"]').length) {
			return;
		}
		var $existing = pickField([name]);
		if ($existing.length) {
			moveFieldInto($existing, $grid, { fallbackLabel: label });
			return;
		}
		// Fallback when vtiger has not rendered phase 4/5 fields yet.
		var $wrap = $('<div class="mk-campx-field" />');
		$wrap.append($('<div class="mk-campx-field__label" />').text(label));
		var $ctl = $('<div class="mk-campx-field__control fieldValue" />');
		var $inp;
		if (isTextarea) {
			$inp = $('<textarea class="inputElement" rows="3" />').attr('name', name);
		} else {
			$inp = $('<input type="text" class="inputElement" />').attr('name', name);
		}
		$ctl.append($inp);
		$wrap.append($ctl);
		$grid.append($wrap);
	}

	function ensurePhaseAccordion(p, openByDefault) {
		var $root = $('#mkCampXPhases');
		if (!$root.length) return;
		var $acc = $root.find('.mk-campx-acc[data-phase="' + p + '"]');
		if (!$acc.length) {
			$acc = $('<div class="mk-campx-acc" />')
				.attr('data-phase', p)
				.attr('data-open', openByDefault ? '1' : '0');
			var $btn = $('<button type="button" class="mk-campx-acc__btn" />');
			$btn.append($('<div class="mk-campx-acc__ttl" />').text('Phase ' + p));
			$btn.append($('<div class="mk-campx-acc__chev" aria-hidden="true"><i class="fa fa-chevron-down"></i></div>'));
			var $panel = $('<div class="mk-campx-acc__panel" />');
			var $grid = $('<div class="mk-campx-accGrid" />');
			$panel.append($grid);
			$acc.append($btn).append($panel);
			$root.append($acc);

			$btn.on('click', function () {
				var open = $acc.attr('data-open') === '1';
				$acc.attr('data-open', open ? '0' : '1');
			});
		}

		var $grid = $acc.find('.mk-campx-accGrid');
		ensurePhaseField($grid, p, 'expected', 'Expected', false);
		ensurePhaseField($grid, p, 'actual', 'Actual', false);
		ensurePhaseField($grid, p, 'comment', 'Comment', true);
		$acc.attr('data-open', openByDefault ? '1' : '0');
	}

	function updatePhaseToolbar(count) {
		$('.js-campaign-add-phase').prop('disabled', count >= 5);
		$('.js-campaign-remove-phase').prop('disabled', count <= 2);
		var $hint = $('.js-campaign-phase-hint');
		if ($hint.length) {
			$hint.text('Active phases: ' + count + ' (max 5)');
		}
	}

	function getPhaseCount() {
		var $pc = pickField(['campaign_phase_count']);
		var count = 2;
		if ($pc.length) {
			var pv = parseInt($pc.val(), 10);
			if (!isNaN(pv) && pv >= 2 && pv <= 5) {
				count = pv;
			}
		}
		return count;
	}

	function setPhaseCount(count) {
		if (count < 2) count = 2;
		if (count > 5) count = 5;
		var $pc = pickField(['campaign_phase_count']);
		if (!$pc.length) {
			var $f = $form();
			if ($f.length) {
				$pc = $('<input type="hidden" name="campaign_phase_count" value="' + count + '" />');
				$f.append($pc);
			}
		}
		if ($pc.length) {
			$pc.val(String(count));
		}
		return count;
	}

	function mountAttachmentsUi() {
		var $collab = $('#mkCampXCollab');
		if (!$collab.length) {
			return;
		}

		var $box = $('#campaign-files-edit-box');
		if ($box.length) {
			if (!$collab.find('#campaign-files-edit-box').length) {
				$collab.append($box);
			}
			return;
		}

		var $str = $('#campaign-files-edit-strings');
		var help = $str.length ? ($str.data('help') || '') : '';
		if (!help) {
			help = 'Attach files (JPG, PNG, DOC, DOCX, XLS, XLSX). Max total ~50 MB. Click Save to upload.';
		}

		$box = $('<div id="campaign-files-edit-box" class="campaign-edit-files mk-campx-attach" />');
		$box.append($('<div class="mk-campx-field__label mk-campx-attach__title" />').text('Attachments'));
		if (help) {
			$box.append($('<p class="mk-campx-attach__help" />').text(help));
		}

		var $drop = $('<label class="mk-campx-attach__drop" />');
		var $input = $(
			'<input type="file" name="campaign_files[]" multiple="multiple" ' +
				'accept=".jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,image/jpeg,image/png,' +
				'application/msword,application/vnd.ms-excel,' +
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document,' +
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" />'
		);
		$drop.append('<span class="mk-campx-attach__icon" aria-hidden="true"><i class="fa fa-cloud-upload"></i></span>');
		$drop.append('<span class="mk-campx-attach__hint">Choose images or Excel/Word files</span>');
		$drop.append('<span class="mk-campx-attach__sub">JPG, PNG, DOC, DOCX, XLS, XLSX</span>');
		$drop.append($input);

		var $list = $('<ul class="mk-campx-attach__list list-unstyled" />');
		$box.append($drop).append($list);
		$collab.append($box);

		$input.on('change.mkCampxAttach', function () {
			$list.empty();
			var files = this.files || [];
			for (var i = 0; i < files.length; i++) {
				$list.append($('<li class="mk-campx-attach__file" />').text(files[i].name));
			}
		});
	}

	function syncPhasesUi() {
		var count = getPhaseCount();
		var $root = $('#mkCampXPhases');
		if (!$root.length) return;

		for (var p = 1; p <= count; p++) {
			ensurePhaseAccordion(p, p === count);
		}

		$root.find('.mk-campx-acc').each(function () {
			var pp = parseInt($(this).attr('data-phase') || '0', 10);
			if (pp > count) {
				$(this).hide();
			} else {
				$(this).show();
			}
		});

		updatePhaseToolbar(count);
	}

	function getUploadLimitBytes() {
		// vtiger config (config.inc.php upload_maxsize), capped by typical PHP post_max_size after docker fix
		var limit = 50 * 1024 * 1024;
		var $hint = $('#mkCampEnterpriseRoot').data('mk-upload-limit-bytes');
		if ($hint) {
			var n = parseInt($hint, 10);
			if (!isNaN(n) && n > 0) {
				limit = n;
			}
		}
		return limit;
	}

	function getSelectedAttachmentBytes() {
		var total = 0;
		try {
			$('#campaign-files-edit-box input[type="file"][name="campaign_files[]"]').each(function () {
				if (!this.files) {
					return;
				}
				for (var i = 0; i < this.files.length; i++) {
					total += this.files[i].size || 0;
				}
			});
		} catch (ignore) {}
		return total;
	}

	function formatBytes(bytes) {
		if (bytes >= 1024 * 1024) {
			return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
		}
		return Math.ceil(bytes / 1024) + ' KB';
	}

	function isCreateMode() {
		return $('#mkCampEnterpriseRoot').attr('data-mk-campx-create') === '1';
	}

	function focusFieldForKpi(key) {
		var labelMap = {
			budgetcost: [/budget\s*cost/i, 'budgetcost'],
			expectedrevenue: [/expected\s*revenue/i, 'expectedrevenue'],
			expectedroi: [/expected\s*roi/i, 'expectedroi'],
			targetsize: [/target\s*size/i, 'targetsize'],
		};
		var names = labelMap[key] || [];
		var regexes = names.filter(function (x) { return x instanceof RegExp; });
		var fieldNames = names.filter(function (x) { return typeof x === 'string'; });
		var $f = pickSmart(fieldNames, regexes);
		if (!$f.length) return;
		var $wrap = $f.closest('.mk-campx-field');
		if ($wrap.length) {
			$('html, body').animate({ scrollTop: $wrap.offset().top - 120 }, 280);
			$wrap.addClass('mk-campx-field--pulse');
			setTimeout(function () { $wrap.removeClass('mk-campx-field--pulse'); }, 900);
		}
		try {
			if ($f.is(':visible')) {
				$f.trigger('focus');
			} else {
				$f.closest('.select2-container').prev('select').select2('open');
			}
		} catch (e1) {
			$f.trigger('focus');
		}
	}

	function bindLiveSync() {
		if ($('body').data('mkCampxLiveBound')) return;
		$('body').data('mkCampxLiveBound', true);

		var $f = $form();
		if (!$f.length) return;

		$f.on('input.mkCampxLive change.mkCampxLive', 'input, select, textarea', function () {
			updateHeaderKpisAndTimeline();
		});

		$(document)
			.off('click.mkCampxKpi', '.mk-campx-kpi[data-kpi]')
			.on('click.mkCampxKpi', '.mk-campx-kpi[data-kpi]', function () {
				focusFieldForKpi($(this).data('kpi'));
			})
			.off('keydown.mkCampxKpi', '.mk-campx-kpi[data-kpi]')
			.on('keydown.mkCampxKpi', '.mk-campx-kpi[data-kpi]', function (ev) {
				if (ev.key === 'Enter' || ev.key === ' ') {
					ev.preventDefault();
					focusFieldForKpi($(this).data('kpi'));
				}
			});
	}

	function updateHeaderKpisAndTimeline() {
		if (!isScoped()) return;

		// Remove legacy panels that can overlay content (lists/tags)
		$('#mkCampXNativeHost, body')
			.find('#modnavigator, #sidebar-essentials, .sidebarContainer, .leftPanel, #leftPanel, .sideBarContents')
			.remove();

		// Bind Save (use native vtiger save button)
		$('#mkCampXSave')
			.off('click.mkCampX')
			.on('click.mkCampX', function (e) {
				e.preventDefault();
				var $f = $form();
				var attachBytes = getSelectedAttachmentBytes();
				var limitBytes = getUploadLimitBytes();
				if (attachBytes > limitBytes) {
					alert(
						'Total attachment size (' + formatBytes(attachBytes) + ') exceeds the limit (' +
						formatBytes(limitBytes) + '). Please remove some files or use smaller files.'
					);
					return;
				}
				// If attachments are selected, force a real multipart form submit (Ajax saves won't include files).
				var hasFiles = attachBytes > 0;

				if (hasFiles && $f.length) {
					try {
						$f.attr('enctype', 'multipart/form-data');
						$f.attr('encoding', 'multipart/form-data');
						$f[0].submit();
						return;
					} catch (e2) {
						// fallthrough to normal save
					}
				}

				var $save = $f.find('.saveButton').first();
				if ($save.length) $save.trigger('click');
				else $f.trigger('submit');
			});

		// HERO fields
		var $name = pickSmart(['campaignname', 'campaign_name', 'name'], [/campaign\s*name/i]);
		var $type = pickSmart(['campaigntype', 'campaign_type'], [/campaign\s*type/i]);
		var $owner = pickSmart(['assigned_user_id', 'smownerid'], [/assigned\s*to/i]);
		var $status = pickSmart(['campaignstatus', 'campaign_status'], [/campaign\s*status/i, /^status$/i]);

		var nameVal = $name.length ? ($name.val() || '').toString().trim() : '';
		if (nameVal) {
			$('[data-mk-campx-name]').text(nameVal);
		} else if (isCreateMode()) {
			$('[data-mk-campx-name]').text('Create Campaign');
		} else {
			$('[data-mk-campx-name]').text('Campaign');
		}

		var typeVal = $type.length ? ($type.find('option:selected').text() || $type.val() || '').toString().trim() : '';
		if (isPlaceholderOptionText(typeVal)) {
			$('[data-mk-campx-type]').hide();
		} else {
			$('[data-mk-campx-type]').show().text(typeVal);
		}

		var ownerVal = '';
		if ($owner.length) {
			if ($owner.is('select')) ownerVal = ($owner.find('option:selected').text() || '').trim();
			else ownerVal = ($owner.val() || '').toString().trim();
		}
		$('[data-mk-campx-owner]').text(ownerVal ? ('Assigned: ' + ownerVal) : 'Assigned user');

		var statusVal = $status.length ? ($status.find('option:selected').text() || $status.val() || '').toString().trim() : '';
		var $badge = $('[data-mk-campx-status]');
		if (isPlaceholderOptionText(statusVal)) {
			$badge.hide();
		} else {
			var b = statusToBadge(statusVal);
			$badge.show();
			$badge.removeClass('mk-campx-badge--blue mk-campx-badge--green mk-campx-badge--gray mk-campx-badge--red');
			$badge.addClass(b.cls).text(b.label);
		}

		// KPI values
		$('.mk-campx-kpi').each(function () {
			var key = $(this).data('kpi');
			var labelMap = {
				budgetcost: [/budget\s*cost/i],
				expectedrevenue: [/expected\s*revenue/i],
				expectedroi: [/expected\s*roi/i],
				targetsize: [/target\s*size/i],
			};
			var $f = pickSmart([key], labelMap[key] || []);
			var v = $f.length ? ($f.val() || '') : '';
			var hasVal = String(v).trim().length > 0;
			$(this).find('[data-kpi-value]').text(fmt(v));
			$(this).find('[data-kpi-trend]').text(hasVal ? 'Live preview' : 'Enter value below');
			$(this).toggleClass('mk-campx-kpi--live', hasVal);
		});

		// Campaign progress section removed by request (no timeline interactions).
	}

	var BUILD_TOKEN = '20260618_lux12';

	function countMountedFields() {
		return $('#mkCampXInfoGrid .mk-campx-field').length
			+ $('#mkCampXExpectedStats .mk-campx-stat').length
			+ $('#mkCampXActualStats .mk-campx-stat').length;
	}

	function buildOnce() {
		var $root = $('#mkCampEnterpriseRoot');
		if (!$root.length) {
			return;
		}

		ensureEnterpriseInsideForm();

		var mounted = countMountedFields();
		var needsRebuild = $root.data('mkCampxBuildToken') !== BUILD_TOKEN || mounted === 0;
		if ($root.data('mkCampxBuilt') && !needsRebuild) {
			return;
		}

		// Campaign information grid (Section 3)
		var $info = $('#mkCampXInfoGrid');
		$info.empty();
		[
			'campaignname',
			'assigned_user_id',
			'campaignstatus',
			'campaigntype',
			'productsservices_id',
			'targetaudience',
			'closingdate',
			'sponsor',
			'targetsize',
			'numsent',
			'startdate',
			'plan'
		].forEach(function (n) {
			// Support alt names
			var cand = [n];
			var labels = [];
			if (n === 'campaignname') labels = [/campaign\s*name/i];
			if (n === 'assigned_user_id') labels = [/assigned\s*to/i];
			if (n === 'campaignstatus') labels = [/campaign\s*status/i, /^status$/i];
			if (n === 'campaigntype') labels = [/campaign\s*type/i, /^type$/i];
			if (n === 'productsservices_id') labels = [/products?\s*&\s*services/i, /products?\s*and\s*services/i];
			if (n === 'targetaudience') labels = [/target\s*audience/i];
			if (n === 'closingdate') labels = [/expected\s*close\s*date/i, /closing\s*date/i];
			if (n === 'sponsor') labels = [/sponsor/i];
			if (n === 'targetsize') labels = [/target\s*size/i];
			if (n === 'numsent') labels = [/num\s*sent/i];
			if (n === 'startdate') labels = [/start\s*date/i];
			if (n === 'plan') labels = [/plan/i];
			if (n === 'productsservices_id') cand = ['productsservices_id', 'products_id', 'products_services', 'productservice'];
			if (n === 'plan') cand = ['plan', 'planid', 'plan_id'];
			if (n === 'closingdate') cand = ['closingdate', 'expectedclosedate', 'closing_date'];
			if (n === 'startdate') cand = ['startdate', 'start_date'];
			if (n === 'assigned_user_id') cand = ['assigned_user_id', 'smownerid'];
			var $f = pickSmart(cand, labels);
			if ($f.length) moveFieldInto($f, $info);
		});

		// Expected stats (Section 4 left)
		var $exp = $('#mkCampXExpectedStats');
		$exp.empty();
		['expectedresponse', 'expectedrevenue', 'expectedsalescount', 'expectedresponsecount', 'expectedroi'].forEach(function (n) {
			var expLabels = {
				expectedresponse: [/expected\s*response/i],
				expectedrevenue: [/expected\s*revenue/i],
				expectedsalescount: [/expected\s*sales\s*count/i],
				expectedresponsecount: [/expected\s*response\s*count/i],
				expectedroi: [/expected\s*roi/i],
			};
			var $f = pickSmart([n], expLabels[n] || []);
			if (!$f.length) return;
			var $stat = $('<div class="mk-campx-stat" />');
			$stat.append($('<div class="mk-campx-stat__label" />').text(getFieldLabelText($f) || n));
			var $m = $('<div class="mk-campx-stat__control" />');
			moveFieldInto($f, $m, { compact: true });
			$stat.append($m);
			$exp.append($stat);
		});

		// Actual stats (Section 4 right)
		var $act = $('#mkCampXActualStats');
		$act.empty();
		['actualcost', 'actualsalescount', 'actualresponsecount', 'actualroi', 'actualenddate'].forEach(function (n) {
			var cand = [n];
			if (n === 'actualenddate') cand = ['actualenddate', 'actual_end_date'];
			var actLabels = {
				actualcost: [/actual\s*cost/i],
				actualsalescount: [/actual\s*sales\s*count/i],
				actualresponsecount: [/actual\s*response\s*count/i],
				actualroi: [/actual\s*roi/i],
				actualenddate: [/actual\s*end\s*date/i],
			};
			var $f = pickSmart(cand, actLabels[n] || []);
			if (!$f.length) return;
			var $stat = $('<div class="mk-campx-stat" />');
			$stat.append($('<div class="mk-campx-stat__label" />').text(getFieldLabelText($f) || n));
			var $m = $('<div class="mk-campx-stat__control" />');
			moveFieldInto($f, $m, { compact: true });
			$stat.append($m);
			$act.append($stat);
		});

		// Collaboration (Section 6) — Description + campaign files box (injected by core Campaigns Edit.js)
		var $collab = $('#mkCampXCollab');
		$collab.empty();
		var $desc = pickSmart(['description'], [/description/i, /notes/i]);
		if ($desc.length) {
			var $left = $('<div class="mk-campx-field" />');
			$left.append($('<div class="mk-campx-field__label" />').text(getFieldLabelText($desc) || 'Description'));
			var $ctl = $('<div class="mk-campx-field__control fieldValue" />');
			$left.append($ctl);
			var $td = $desc.closest('td.fieldValue');
			if (!$td.length) {
				$td = $desc.closest('td');
			}
			$ctl.append($td.contents());
			try {
				$td.empty();
			} catch (eDesc) {}
			$desc.data('mkCampxMoved', true);
			$collab.append($left);
		}

		mountAttachmentsUi();

		// Phases accordion (Section 7)
		var phaseCount = getPhaseCount();
		for (var p = 1; p <= phaseCount; p++) {
			ensurePhaseAccordion(p, p === phaseCount);
		}
		updatePhaseToolbar(phaseCount);

		// Sync accordion after add/remove (enterprise toolbar only — core handlers skipped)
		$(document)
			.off('click.mkCampXPhaseToolbar')
			.on('click.mkCampXPhaseToolbar', '.js-campaign-add-phase, .js-campaign-remove-phase', function (e) {
				e.preventDefault();
				e.stopImmediatePropagation();
				var count = getPhaseCount();
				if ($(this).hasClass('js-campaign-add-phase')) {
					if (count < 5) count += 1;
				} else if (count > 2) {
					clearPhaseFields(count);
					count -= 1;
				}
				setPhaseCount(count);
				syncPhasesUi();
				if (typeof window.Campaigns_Edit_Js !== 'undefined' && typeof Campaigns_Edit_Js.getInstance === 'function') {
					try {
						var inst = Campaigns_Edit_Js.getInstance();
						if (inst && typeof inst._applyPhaseRowsVisibility === 'function') {
							inst._applyPhaseRowsVisibility(count);
						}
					} catch (ignorePhase) {}
				}
			});

		// Hide remaining legacy blocks after moving fields (best-effort)
		try {
			$form().find('.fieldBlockContainer[data-block]').hide();
		} catch (e) {}

		$root.data('mkCampxBuilt', true);
		$root.data('mkCampxBuildToken', BUILD_TOKEN);
		reinitFieldWidgets();
	}

	function render() {
		if (!isScoped()) return;
		ensureEnterpriseInsideForm();
		updateHeaderKpisAndTimeline();
		buildOnce();
		ensureCampaignsModuleHooks();

		setTimeout(function () {
			reinitFieldWidgets();
			mountAttachmentsUi();
			syncPhasesUi();
			$(window).trigger('resize');
		}, 200);
	}

	function clearPhaseFields(p) {
		var suffixes = ['expected', 'actual', 'comment', 'start_date', 'end_date'];
		for (var i = 0; i < suffixes.length; i++) {
			var $el = pickField(['phase' + p + '_' + suffixes[i]]);
			if ($el.length) {
				$el.val('');
			}
		}
	}

	function ensureCampaignsModuleHooks() {
		if (typeof window.Campaigns_Edit_Js === 'undefined' || typeof Campaigns_Edit_Js.getInstance !== 'function') {
			mountAttachmentsUi();
			return;
		}
		try {
			var inst = Campaigns_Edit_Js.getInstance();
			if (inst && typeof inst.registerPhaseSlots === 'function') {
				inst.registerPhaseSlots();
			}
			if (inst && typeof inst.registerCampaignDescriptionFiles === 'function') {
				inst.registerCampaignDescriptionFiles();
			}
			if (inst && typeof inst.adjustPhaseLayout === 'function') {
				inst.adjustPhaseLayout();
			}
		} catch (ignore) {}
		mountAttachmentsUi();
		syncPhasesUi();
	}

	function init() {
		if (!isScoped()) return;
		bindLiveSync();
		render();
		ensureCampaignsModuleHooks();
		setTimeout(render, 300);
		setTimeout(function () {
			render();
			ensureCampaignsModuleHooks();
		}, 900);
		$(document).ajaxComplete(function (_e, _xhr, settings) {
			if (!isScoped()) {
				return;
			}
			var reqData = settings && settings.data;
			if (typeof reqData === 'string' && /(^|&)view=Popup(&|$)/.test(reqData)) {
				return;
			}
			if ($('#popupModal').length) {
				return;
			}
			setTimeout(render, 150);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})($);

