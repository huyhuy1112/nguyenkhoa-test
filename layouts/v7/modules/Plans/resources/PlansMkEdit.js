(function ($) {
	'use strict';

	function isScoped() {
		var $b = $('body');
		var modOk = ($b.attr('data-module') || '') === 'Plans';
		var viewOk = ($b.attr('data-view') || '') === 'Edit';
		var appOk = ($b.attr('data-app') || '') === 'MARKETING' || (new URLSearchParams(window.location.search).get('app') || '').toUpperCase() === 'MARKETING';
		return modOk && viewOk && appOk && $('#mkPlanXSaveTop').length && $('#mkPlanXNativeHost').length;
	}

	function clickNativeSave() {
		// Prefer vtiger's save button / submit pipeline.
		var $form = $('form[name="EditView"]');
		if (!$form.length) $form = $('form#EditView');
		if ($form.length) {
			$form.trigger('submit');
			return;
		}
		// Fallback: click any visible save button.
		var $btn = $('button[type="submit"], input[type="submit"]').filter(':visible').first();
		if ($btn.length) $btn.trigger('click');
	}

	function normTxt(s) {
		return String(s || '').replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
	}

	function isPlaceholderOptionText(s) {
		var t = normTxt(s).toLowerCase();
		return !t || t === 'select an option' || t === '-- select --' || t === '--select--' || t === 'select an option…' || t === 'select an option...';
	}

	function fmtDateLoose(s) {
		var t = normTxt(s);
		if (!t) return '—';
		// Try ISO first (YYYY-MM-DD)
		if (/^\d{4}-\d{2}-\d{2}/.test(t)) {
			var d = new Date(t + 'T00:00:00');
			if (!isNaN(d.getTime())) {
				return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' });
			}
		}
		return t;
	}

	function parseDateLoose(s) {
		var t = normTxt(s);
		if (!t) return null;
		// Convert common dd/mm/yyyy or mm/dd/yyyy heuristically: just rely on Date parsing fallback.
		var d = new Date(t);
		return isNaN(d.getTime()) ? null : d;
	}

	function computeDuration(startStr, endStr) {
		var sd = parseDateLoose(startStr);
		var ed = parseDateLoose(endStr);
		if (!sd || !ed) return '—';
		var diffMs = ed.getTime() - sd.getTime();
		var diffDays = Math.round(diffMs / (24 * 3600 * 1000));
		if (!isFinite(diffDays)) return '—';
		if (diffDays < 0) diffDays = 0;
		if (diffDays === 0) return '0 days';
		return diffDays + ' days';
	}

	function statusMetaFromText(statusRaw) {
		var s = normTxt(statusRaw).toLowerCase();

		// Draft = gray
		if (s === 'draft' || s.indexOf('draft') >= 0 || s.indexOf('pending') >= 0) {
			return { key: 'draft', label: statusRaw || 'Draft', dot: '#94a3b8', icon: 'fa fa-file-text-o', badgeCls: 'mk-planx-status--draft', percent: 0, stage: 0 };
		}
		// Planning = blue
		if (s.indexOf('plan') >= 0 || s.indexOf('planning') >= 0 || s === 'planning') {
			return { key: 'planning', label: statusRaw || 'Planning', dot: '#3b82f6', icon: 'fa fa-calendar', badgeCls: 'mk-planx-status--planning', percent: 25, stage: 0 };
		}
		// In Progress = orange
		if (s.indexOf('progress') >= 0 || s.indexOf('active') >= 0 || s.indexOf('in progress') >= 0) {
			return { key: 'in_progress', label: statusRaw || 'In Progress', dot: '#f59e0b', icon: 'fa fa-hourglass-half', badgeCls: 'mk-planx-status--inprogress', percent: 60, stage: 1 };
		}
		// Completed = green/yellow
		if (s.indexOf('complete') >= 0 || s.indexOf('completed') >= 0) {
			return { key: 'completed', label: statusRaw || 'Completed', dot: '#22c55e', icon: 'fa fa-check-circle', badgeCls: 'mk-planx-status--completed', percent: 100, stage: 2 };
		}
		// Cancelled/Cancelled = red
		if (s.indexOf('cancel') >= 0 || s.indexOf('inactive') >= 0) {
			return { key: 'cancelled', label: statusRaw || 'Cancelled', dot: '#ef4444', icon: 'fa fa-times-circle', badgeCls: 'mk-planx-status--cancelled', percent: 0, stage: 1 };
		}

		return { key: 'unknown', label: statusRaw || 'Status', dot: '#94a3b8', icon: 'fa fa-flag', badgeCls: 'mk-planx-status--unknown', percent: 0, stage: 0 };
	}

	function $nativeHost() {
		return $('#mkPlanXNativeHost');
	}

	function $form() {
		var $f = $nativeHost().find('form#EditView, form[name="edit"], form[name="EditView"]').first();
		if ($f.length) return $f;
		return $('form#EditView, form[name="edit"], form[name="EditView"]').first();
	}

	function pickField(nameCandidates) {
		for (var i = 0; i < (nameCandidates || []).length; i++) {
			var n = nameCandidates[i];
			var $el = $form().find('[name="' + n + '"]').first();
			if (!$el.length) $el = $('[name="' + n + '"]').first();
			if (!$el.length) {
				$el = $form().find('[data-fieldname="' + n + '"] input, [data-fieldname="' + n + '"] select, [data-fieldname="' + n + '"] textarea').first();
			}
			if ($el.length) return $el;
		}
		return $();
	}

	function pickFieldByLabel(labelRegexes) {
		var regs = (labelRegexes || []).filter(Boolean).map(function (r) {
			return r instanceof RegExp ? r : new RegExp(String(r), 'i');
		});
		if (!regs.length) return $();

		var $labels = $form().find('td.fieldLabel, .fieldLabel');
		var found = $();
		$labels.each(function () {
			var $l = $(this);
			var txt = normTxt($l.text());
			for (var i = 0; i < regs.length; i++) {
				if (regs[i].test(txt)) {
					var $val = $l.nextAll('td.fieldValue').first();
					if (!$val.length) $val = $l.closest('tr').find('td.fieldValue').first();
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
		var $labelTd = $td.prevAll('td.fieldLabel').first();
		var txt = normTxt($labelTd.text());
		txt = txt.replace(/\s*\*+\s*$/, '').replace(/\s*:\s*$/, '');
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

		if ($fieldEl.data('mkPlanxMoved')) {
			var $wrap0 = $fieldEl.closest('.mk-planx-field');
			if ($wrap0.length) {
				$mount.append($wrap0);
			}
			return;
		}

		var label = opts.label || getFieldLabelText($fieldEl) || opts.fallbackLabel || 'Field';
		var $valueTd = $fieldEl.closest('td.fieldValue');
		if (!$valueTd.length) $valueTd = $fieldEl.closest('td');

		var $wrap = $('<div class="mk-planx-field" />').toggleClass('mk-planx-field--full', !!opts.full);
		var $lab = $('<div class="mk-planx-field__label" />').text(label);
		if (isRequired($fieldEl)) $lab.append($('<span class="mk-planx-req">*</span>'));
		var $ctl = $('<div class="mk-planx-field__control" />');
		$wrap.append($lab).append($ctl);

		var $children = $valueTd.contents();
		$ctl.append($children);
		$mount.append($wrap);

		try { $valueTd.empty(); } catch (e) {}
		$fieldEl.data('mkPlanxMoved', true);
	}

	function buildInformationLayoutOnce() {
		var $grid = $('#mkPlanXInfoGrid');
		var $full = $('#mkPlanXInfoFull');
		if (!$grid.length || !$full.length) return;
		if ($grid.data('mkPlanxBuilt')) return;
		$grid.data('mkPlanxBuilt', true);

		// Field names based on module entity (planname, plan_status, start_date, end_date, description)
		var $name = pickSmart(['planname', 'plan_name'], [/plan\s*name/i]);
		var $status = pickSmart(['plan_status', 'status'], [/^status$/i, /status/i]);
		var $start = pickSmart(['start_date'], [/start\s*date/i]);
		var $end = pickSmart(['end_date'], [/end\s*date/i]);
		var $desc = pickSmart(['description'], [/description/i]);

		moveFieldInto($name, $grid, { fallbackLabel: 'Plan Name' });
		moveFieldInto($status, $grid, { fallbackLabel: 'Status' });
		moveFieldInto($start, $grid, { fallbackLabel: 'Start Date' });
		moveFieldInto($end, $grid, { fallbackLabel: 'End Date' });
		moveFieldInto($desc, $full, { fallbackLabel: 'Description', full: true });

		// Hide the native table layout now that fields are moved.
		$nativeHost().addClass('mk-planx-native--hidden');
	}

	function updateStatusUI($native) {
		var $statusControl = pickSmart(['plan_status', 'status'], [/^status$/i, /status/i]);
		if (!$statusControl.length) return;

		var rawText = $statusControl.find('option:selected').text();
		var meta = statusMetaFromText(rawText);

		var $badge = $('[data-mk-planx-status-badge]');
		var $summaryStatus = $('[data-mk-planx-summary-status]');
		if ($badge.length) {
			$badge
				.removeClass()
				.addClass('mk-planx-status__badgeInner')
				.addClass(meta.badgeCls)
				.html(
					'<i class="' + meta.icon + '" aria-hidden="true"></i>' +
					'<span class="mk-planx-status__badgeLabel"></span>'
				);
			$badge.find('.mk-planx-status__badgeLabel').text(meta.label);
		}

		if ($summaryStatus.length) {
			$summaryStatus
				.removeClass()
				.addClass('mk-planx-chip mk-planx-chip--status mk-planx-chip--fromStatus ' + meta.badgeCls)
				.find('.mk-planx-chip__dot')
				.css('background', meta.dot);
			$summaryStatus.find('.mk-planx-chip__label').text(meta.label);

			// Optional icon injection (if icon element not present).
			if (!$summaryStatus.find('i').length) {
				$summaryStatus.prepend('<i class="' + meta.icon + ' mk-planx-chip__statusIc" aria-hidden="true"></i>');
			}
		}

		// Update select look: add status class to body so CSS can theme the select2.
		$('body').removeClass('mk-planx-status--draft mk-planx-status--planning mk-planx-status--inprogress mk-planx-status--completed mk-planx-status--cancelled mk-planx-status--unknown')
			.addClass(meta.badgeCls);

		return meta;
	}

	function updateSummaryAndTimeline($native) {
		var $statusField = pickSmart(['plan_status', 'status'], [/^status$/i, /status/i]);
		var statusRaw = $statusField.length ? $statusField.find('option:selected').text() : '';
		var meta = statusMetaFromText(statusRaw);

		// Update badge/chip/theme first so timeline uses the same mapping.
		var meta2 = updateStatusUI($native);
		if (meta2) meta = meta2;

		var $startInput = pickSmart(['start_date'], [/start\s*date/i]);
		var $endInput = pickSmart(['end_date'], [/end\s*date/i]);
		var startStr = $startInput.length ? $startInput.val() : '';
		var endStr = $endInput.length ? $endInput.val() : '';

		var startFmt = fmtDateLoose(startStr);
		var endFmt = fmtDateLoose(endStr);
		var dur = computeDuration(startStr, endStr);

		$('[data-mk-planx-timeline-start-date]').text(startFmt);
		$('[data-mk-planx-timeline-end-date]').text(endFmt);
		$('[data-mk-planx-timeline-progress]').text(meta.percent + '%');

		$('[data-mk-planx-summary-duration]').find('.mk-planx-chip__value').first().text(dur);
		$('[data-mk-planx-summary-start]').find('.mk-planx-chip__value').first().text(startFmt);
		$('[data-mk-planx-summary-end]').find('.mk-planx-chip__value').first().text(endFmt);

		var $fill = $('[data-mk-planx-timeline-fill]');
		if ($fill.length) $fill.css('width', meta.percent + '%');

		// Mark milestone dots
		var $milestones = $('[data-mk-planx-timeline] [data-mk-planx-timeline-dot]');
		$milestones.each(function () {
			var $tm = $(this);
			var dot = $tm.attr('data-mk-planx-timeline-dot');
			$tm.removeClass('mk-planx-tm--done mk-planx-tm--current');

			if (dot === 'start') {
				$tm.addClass(meta.stage >= 1 ? 'mk-planx-tm--done' : 'mk-planx-tm--current');
			} else if (dot === 'mid') {
				if (meta.stage === 1) $tm.addClass('mk-planx-tm--current');
				else if (meta.stage >= 2) $tm.addClass('mk-planx-tm--done');
			} else if (dot === 'end') {
				if (meta.stage >= 2) $tm.addClass('mk-planx-tm--done');
			}
		});

		var $act = $('[data-mk-planx-activity]');
		if ($act.length) {
			var $statusItem = $act.find('.mk-planx-activityItem__title').filter(function () {
				return normTxt($(this).text()).toLowerCase().indexOf('status') >= 0;
			}).closest('.mk-planx-activityItem');
			if ($statusItem.length) {
				$statusItem.find('.mk-planx-activityItem__text').first().text('Set to ' + (meta.label || 'Status'));
			}
		}
	}

	function cleanupSelectPlaceholder($native) {
		// Hide select2 placeholder text if present.
		$native.find('select').each(function () {
			var $sel = $(this);
			var $opt = $sel.find('option:selected').first();
			if (!$opt.length) return;
			if (!isPlaceholderOptionText($opt.text())) return;

			var $s2 = $sel.nextAll('.select2-container').first();
			if ($s2.length) {
				var $choice = $s2.find('.select2-chosen, .select2-search-choice, .select2-choice > span').first();
				if ($choice.length) $choice.text('');
			}
		});
	}

	function cleanupEmptyRows($native) {
		$native.find('tr').each(function () {
			var $tr = $(this);
			if ($tr.find('input,select,textarea,.select2-container').length) return;
			var txt = normTxt($tr.text());
			if (!txt) $tr.remove();
		});
	}

	function setSelectByText($sel, targets) {
		if (!$sel.length || !$sel.is('select')) return false;
		var t = (targets || []).map(function (x) { return normTxt(x).toLowerCase(); }).filter(Boolean);
		if (!t.length) return false;
		var foundVal = null;
		$sel.find('option').each(function () {
			var $o = $(this);
			var ot = normTxt($o.text()).toLowerCase();
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

	function buildStatusBadgesOnce() {
		var $wrap = $('[data-mk-planx-status-badges]');
		if (!$wrap.length) return;
		if ($wrap.data('mkPlanxBuilt')) return;
		$wrap.data('mkPlanxBuilt', true);

		var $sel = pickSmart(['plan_status', 'status'], [/^status$/i, /status/i]);
		if (!$sel.length) return;

		var known = [
			{ label: 'Draft', cls: 'mk-planx-sbadge--draft', icon: 'fa fa-file-text-o' },
			{ label: 'Planning', cls: 'mk-planx-sbadge--planning', icon: 'fa fa-calendar' },
			{ label: 'In Progress', cls: 'mk-planx-sbadge--inprogress', icon: 'fa fa-hourglass-half' },
			{ label: 'Completed', cls: 'mk-planx-sbadge--completed', icon: 'fa fa-check-circle' },
			{ label: 'Cancelled', cls: 'mk-planx-sbadge--cancelled', icon: 'fa fa-times-circle' },
		];

		for (var i = 0; i < known.length; i++) {
			(function (k) {
				var $b = $('<button type="button" class="mk-planx-sbadge" />')
					.addClass(k.cls)
					.attr('data-value', k.label)
					.append('<i class="' + k.icon + '" aria-hidden="true"></i>')
					.append('<span class="mk-planx-sbadge__txt"></span>');
				$b.find('.mk-planx-sbadge__txt').text(k.label);
				$b.on('click', function () {
					setSelectByText($sel, [k.label]);
				});
				$wrap.append($b);
			})(known[i]);
		}

		// Sync active state
		var sync = function () {
			var cur = normTxt($sel.find('option:selected').text());
			$wrap.find('.mk-planx-sbadge').each(function () {
				var $b = $(this);
				$b.toggleClass('is-active', normTxt($b.attr('data-value')) === cur);
			});
		};
		$sel.on('change', sync);
		sync();
	}

	function buildActivityPlaceholder() {
		var $list = $('[data-mk-planx-activity]');
		if (!$list.length) return;
		$list.empty();
		var items = [
			{ ic: 'fa fa-plus-circle', title: 'Status Changes', body: '—' },
			{ ic: 'fa fa-user', title: 'Created By', body: '—' },
			{ ic: 'fa fa-clock-o', title: 'Last Modified', body: '—' },
		];

		for (var i = 0; i < items.length; i++) {
			var it = items[i];
			var $row = $('<div class="mk-planx-activityItem" />');
			$row.append('<div class="mk-planx-activityItem__ic" aria-hidden="true"><i class="' + it.ic + '"></i></div>');
			var $body = $('<div class="mk-planx-activityItem__body" />');
			$body.append('<div class="mk-planx-activityItem__title">' + it.title + '</div>');
			$body.append('<div class="mk-planx-activityItem__text">' + it.body + '</div>');
			$row.append($body);
			$list.append($row);
		}
	}

	$(function () {
		if (!isScoped()) return;

		$(document).off('click.mkPlanXSave', '#mkPlanXSaveTop').on('click.mkPlanXSave', '#mkPlanXSaveTop', function (e) {
			e.preventDefault();
			clickNativeSave();
		});

		var $native = $('#mkPlanXNativeHost');
		buildInformationLayoutOnce();

		// Run once more after select2 widgets initialize (placeholder/empty cells may change).
		setTimeout(function () {
			cleanupEmptyRows($nativeHost());
			cleanupSelectPlaceholder($nativeHost());
			updateSummaryAndTimeline($native);
		}, 60);

		setTimeout(function () {
			cleanupEmptyRows($nativeHost());
			cleanupSelectPlaceholder($nativeHost());
			updateSummaryAndTimeline($native);
		}, 420);

		// Status change listener to keep badge/timeline in sync.
		var $statusSelect = pickSmart(['plan_status', 'status'], [/^status$/i, /status/i]);
		if ($statusSelect.length && !$statusSelect.data('mkPlanxWired')) {
			$statusSelect.data('mkPlanxWired', true);
			$statusSelect.on('change', function () {
				updateSummaryAndTimeline($native);
			});
		}

		buildStatusBadgesOnce();
		buildActivityPlaceholder();
	});
})(jQuery);

