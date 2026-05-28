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
		var $f = $('#mkCampXNativeHost').find('form#EditView, form[name="edit"]').first();
		if ($f.length) {
			return $f;
		}
		// Fallback: vtiger sometimes renders the form outside the native host wrapper
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
					var $el = $val.find('input[name], select[name], textarea[name], input, select, textarea').first();
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

	function moveFieldInto($fieldEl, $mount, opts) {
		opts = opts || {};
		if (!$fieldEl.length || !$mount.length) return;
		if ($fieldEl.data('mkCampxMoved')) {
			// Field already moved earlier — relocate its enterprise wrapper into the new mount.
			var $wrap0 = $fieldEl.closest('.mk-campx-field');
			if ($wrap0.length) {
				$mount.append($wrap0);
			}
			return;
		}
		var label = opts.label || getFieldLabelText($fieldEl) || opts.fallbackLabel || '';

		var $valueTd = $fieldEl.closest('td.fieldValue');
		if (!$valueTd.length) $valueTd = $fieldEl.closest('td');

		var $wrap = $('<div class="mk-campx-field" />');
		var $lab = $('<div class="mk-campx-field__label" />').text(label || 'Field');
		if (isRequired($fieldEl)) {
			$lab.append($('<span class="mk-campx-req">*</span>'));
		}
		var $ctl = $('<div class="mk-campx-field__control" />');
		$wrap.append($lab).append($ctl);

		// Move everything inside the value td to preserve reference widgets, hidden inputs, etc.
		var $children = $valueTd.contents();
		$ctl.append($children);
		$mount.append($wrap);

		// Clean the old table cells to avoid stray layout
		try {
			$valueTd.empty();
		} catch (e) {}

		$fieldEl.data('mkCampxMoved', true);
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

	function ensurePhaseAccordion(p, openByDefault) {
		var $root = $('#mkCampXPhases');
		if (!$root.length) return;
		var $existing = $root.find('.mk-campx-acc[data-phase="' + p + '"]');
		if ($existing.length) {
			return;
		}

		var $acc = $('<div class="mk-campx-acc" />')
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

		// Only move fields for this specific phase.
		moveFieldInto(pickField(['phase' + p + '_expected']), $grid, { fallbackLabel: 'Expected' });
		moveFieldInto(pickField(['phase' + p + '_actual']), $grid, { fallbackLabel: 'Actual' });
		moveFieldInto(pickField(['phase' + p + '_comment']), $grid, { fallbackLabel: 'Comment' });
	}

	function syncPhasesUi() {
		var $pc = pickField(['campaign_phase_count']);
		var count = 2;
		if ($pc.length) {
			var pv = parseInt($pc.val(), 10);
			if (!isNaN(pv) && pv >= 2 && pv <= 5) count = pv;
		}
		var $root = $('#mkCampXPhases');
		if (!$root.length) return;

		// Append missing phases only; never clear existing DOM.
		for (var p = 1; p <= count; p++) {
			ensurePhaseAccordion(p, p === count);
		}

		// Hide phases above count (core remove does this too; we mirror in UI)
		$root.find('.mk-campx-acc').each(function () {
			var pp = parseInt($(this).attr('data-phase') || '0', 10);
			if (pp > count) {
				$(this).hide();
			} else {
				$(this).show();
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
				var $save = $form().find('.saveButton').first();
				if ($save.length) {
					$save.trigger('click');
				} else {
					$form().trigger('submit');
				}
			});

		// HERO fields
		var $name = pickSmart(['campaignname', 'campaign_name', 'name'], [/campaign\s*name/i]);
		var $type = pickSmart(['campaigntype', 'campaign_type'], [/campaign\s*type/i]);
		var $owner = pickSmart(['assigned_user_id', 'smownerid'], [/assigned\s*to/i]);
		var $status = pickSmart(['campaignstatus', 'campaign_status'], [/campaign\s*status/i, /^status$/i]);

		var nameVal = $name.length ? ($name.val() || '').toString().trim() : '';
		$('[data-mk-campx-name]').text(nameVal || 'Campaign');

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
			$(this).find('[data-kpi-value]').text(fmt(v));
			$(this).find('[data-kpi-trend]').text(v ? 'Updated' : '—');
		});

		// Campaign progress section removed by request (no timeline interactions).
	}

	function buildOnce() {
		var $root = $('#mkCampEnterpriseRoot');
		if (!$root.length || $root.data('mkCampxBuilt')) {
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
			'products_id',
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
			if (n === 'products_id') labels = [/products?\s*&\s*services/i, /products?\s*and\s*services/i];
			if (n === 'targetaudience') labels = [/target\s*audience/i];
			if (n === 'closingdate') labels = [/expected\s*close\s*date/i, /closing\s*date/i];
			if (n === 'sponsor') labels = [/sponsor/i];
			if (n === 'targetsize') labels = [/target\s*size/i];
			if (n === 'numsent') labels = [/num\s*sent/i];
			if (n === 'startdate') labels = [/start\s*date/i];
			if (n === 'plan') labels = [/plan/i];
			if (n === 'products_id') cand = ['products_id', 'products_services', 'productservice'];
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
			var $m = $('<div />');
			moveFieldInto($f, $m, { label: getFieldLabelText($f) || n });
			// unwrap inner field control for compact mini-stat
			$stat.append($m.find('.mk-campx-field__control').children());
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
			var $m = $('<div />');
			moveFieldInto($f, $m, { label: getFieldLabelText($f) || n });
			$stat.append($m.find('.mk-campx-field__control').children());
			$act.append($stat);
		});

		// Collaboration (Section 6) — Description + campaign files box (injected by core Campaigns Edit.js)
		var $collab = $('#mkCampXCollab');
		$collab.empty();
		var $desc = pickSmart(['description'], [/description/i, /notes/i]);
		if ($desc.length) {
			var $left = $('<div class="mk-campx-field" />');
			$left.append($('<div class="mk-campx-field__label" />').text(getFieldLabelText($desc) || 'Description'));
			var $ctl = $('<div class="mk-campx-field__control" />');
			$left.append($ctl);
			var $td = $desc.closest('td');
			$ctl.append($td.contents());
			$collab.append($left);
		}

		// Phases accordion (Section 7)
		var phaseCount = 2;
		var $pc = pickField(['campaign_phase_count']);
		if ($pc.length) {
			var pv = parseInt($pc.val(), 10);
			if (!isNaN(pv) && pv >= 2 && pv <= 5) phaseCount = pv;
		}
		for (var p = 1; p <= phaseCount; p++) {
			ensurePhaseAccordion(p, p === 1);
		}

		// Sync accordion after core add/remove changes count (core doesn't trigger change event)
		$(document)
			.off('click.mkCampXPhaseToolbar')
			.on('click.mkCampXPhaseToolbar', '.js-campaign-add-phase, .js-campaign-remove-phase', function () {
				setTimeout(syncPhasesUi, 120);
			});

		// Hide remaining legacy blocks after moving fields (best-effort)
		try {
			$form().find('.fieldBlockContainer[data-block]').hide();
		} catch (e) {}

		$root.data('mkCampxBuilt', true);
	}

	function render() {
		if (!isScoped()) return;
		updateHeaderKpisAndTimeline();
		buildOnce();

		// Ensure select2 widgets recalc width after DOM moves
		setTimeout(function () {
			$(window).trigger('resize');
		}, 200);
	}

	function init() {
		if (!isScoped()) return;
		render();
		setTimeout(render, 300);
		setTimeout(render, 900);
		$(document).ajaxComplete(function () {
			if (isScoped()) setTimeout(render, 150);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})($);

