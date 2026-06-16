/* global jQuery, app */
/**
 * ProjectTask List — MANAGEMENT app (Figma layout).
 * Priority pills, progress bars, assignee avatars, column classes.
 */
(function ($) {
	'use strict';

	function isManagementProjectTaskList() {
		var body = document.body;
		if (!body) {
			return false;
		}
		return (
			body.getAttribute('data-module') === 'ProjectTask' &&
			body.getAttribute('data-view') === 'List' &&
			body.getAttribute('data-app') === 'MANAGEMENT'
		);
	}

	var COL_CLASS_BY_FIELD = {
		projecttaskname: 'mk-col-taskname',
		projectid: 'mk-col-related',
		projecttaskpriority: 'mk-col-priority',
		projecttaskprogress: 'mk-col-progress',
		startdate: 'mk-col-start-date',
		enddate: 'mk-col-end-date',
		assigned_user_id: 'mk-col-assigned'
	};

	function initialsFromName(name) {
		var parts = String(name || '')
			.trim()
			.split(/\s+/)
			.filter(Boolean);
		if (!parts.length) {
			return '?';
		}
		if (parts.length === 1) {
			return parts[0].substring(0, 2).toUpperCase();
		}
		return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
	}

	function fieldFromHeaderTh($th) {
		var field = $th.data('columnname') || $th.data('fieldname') || $th.data('name');
		if (!field) {
			var $a = $th.find('a.listViewContentHeaderValues').first();
			if ($a.length) {
				field = $a.data('columnname') || $a.data('fieldname');
			}
		}
		return field;
	}

	function assignColumnClasses() {
		var $table = $('#listview-table');
		if (!$table.length) {
			return;
		}
		var $headerCells = $table.find('thead tr.listViewContentHeader th');
		$table.find('thead tr.listViewContentHeader th').each(function () {
			var $th = $(this);
			var field = fieldFromHeaderTh($th);
			if (field && COL_CLASS_BY_FIELD[field]) {
				$th.addClass(COL_CLASS_BY_FIELD[field]);
			}
			if ($th.hasClass('listViewRecordActions') || $th.find('.table-actions').length) {
				$th.addClass('mk-col-control');
			}
		});
		$table.find('thead tr.searchRow th').each(function (idx) {
			var $th = $(this);
			if ($th.hasClass('inline-search-btn') || $th.find('.table-actions').length) {
				$th.addClass('mk-col-control');
				return;
			}
			var field = $th.find('.listSearchContributor[name]').first().attr('name');
			if (!field && $headerCells.eq(idx).length) {
				field = fieldFromHeaderTh($headerCells.eq(idx));
			}
			if (field && COL_CLASS_BY_FIELD[field]) {
				$th.addClass(COL_CLASS_BY_FIELD[field]);
			}
		});
		$table.find('tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			$row.children('td').each(function () {
				var $td = $(this);
				var field = $td.data('name');
				if (field && COL_CLASS_BY_FIELD[field]) {
					$td.addClass(COL_CLASS_BY_FIELD[field]);
				}
				if ($td.hasClass('listViewRecordActions')) {
					$td.addClass('mk-col-control');
				}
			});
		});
	}

	function renderPriorityPills() {
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var $cell = $row.find('td[data-name="projecttaskpriority"] .value').first();
			if (!$cell.length || $cell.find('.mk-projecttask-priority-pill').length) {
				return;
			}
			var raw = $.trim($cell.text());
			if (!raw) {
				return;
			}
			$cell.html(
				'<span class="mk-projecttask-priority-pill">' +
					$('<div/>').text(raw).html() +
					'</span>'
			);
		});
	}

	function parseProgress(raw) {
		var text = $.trim(raw);
		var pct = 0;
		var m = text.match(/(\d+)\s*%?/);
		if (m) {
			pct = parseInt(m[1], 10);
		} else if (/^\d+$/.test(text)) {
			pct = parseInt(text, 10);
		}
		var upper = text.toUpperCase();
		var isDone = upper.indexOf('DONE') >= 0 || pct >= 100;
		var isCalc = upper.indexOf('CALCULATING') >= 0;
		var tone = 'default';
		if (isDone) {
			tone = 'done';
		} else if (isCalc) {
			tone = 'calculating';
		}
		var label = '';
		if (pct > 0) {
			label = pct + '%';
			if (upper.indexOf('DONE') >= 0) {
				label += ' DONE';
			} else if (upper.indexOf('CALCULATING') >= 0) {
				label += ' CALCULATING';
			}
		}
		return { pct: Math.min(100, Math.max(0, pct)), label: label, tone: tone };
	}

	function renderProgressBars() {
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var $cell = $row.find('td[data-name="projecttaskprogress"] .value').first();
			if (!$cell.length || $cell.find('.mk-projecttask-progress').length) {
				return;
			}
			var info = parseProgress($cell.text());
			var label = info.label || (info.pct > 0 ? info.pct + '%' : '');
			$cell.html(
				'<div class="mk-projecttask-progress mk-projecttask-progress--' +
					info.tone +
					'">' +
					'<div class="mk-projecttask-progress__fill" style="width:' +
					info.pct +
					'%"></div>' +
					(label
						? '<span class="mk-projecttask-progress__label">' +
							$('<span/>').text(label).html() +
							'</span>'
						: '') +
					'</div>'
			);
		});
	}

	function renderAssigneeAvatars() {
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var $cell = $row.find('td[data-name="assigned_user_id"] .value').first();
			if (!$cell.length || $cell.find('.mk-projecttask-assignee').length) {
				return;
			}
			var name = $.trim($cell.text());
			if (!name) {
				return;
			}
			var ini = initialsFromName(name);
			$cell.html(
				'<span class="mk-projecttask-assignee">' +
					'<span class="mk-projecttask-assignee__avatar" aria-hidden="true">' +
					$('<span/>').text(ini).html() +
					'</span>' +
					'<span class="mk-projecttask-assignee__name">' +
					$('<span/>').text(name).html() +
					'</span>' +
					'</span>'
			);
		});
	}

	function enhanceTaskNameLinks() {
		$('#listview-table td[data-name="projecttaskname"] a').each(function () {
			$(this).addClass('mk-projecttask-name-link');
		});
	}

	function normalizeSearchFilters() {
		var $table = $('#listview-table');
		if (!$table.length) {
			return;
		}
		$table.find('thead tr.searchRow .select2-container').each(function () {
			var $el = $(this);
			$el.css({ width: '100%', maxWidth: '100%' });
		});
		$table.find('thead tr.searchRow .select2_search_div').css({ width: '100%', maxWidth: '100%' });
	}

	function initDateSearchPickers() {
		if (window.MkSalesListShared && typeof MkSalesListShared.initManagementDateSearchPickers === 'function') {
			MkSalesListShared.initManagementDateSearchPickers();
		}
	}

	window.mkProjectTaskListInitDatePickers = initDateSearchPickers;

	function postRender() {
		if (!isManagementProjectTaskList()) {
			return;
		}
		assignColumnClasses();
		normalizeSearchFilters();
		initDateSearchPickers();
		renderPriorityPills();
		renderProgressBars();
		renderAssigneeAvatars();
		enhanceTaskNameLinks();
	}

	function bindListHooks() {
		if (!isManagementProjectTaskList()) {
			return;
		}
		$(document).on('mkProjectTaskListPostLoad', function () {
			if (window.mkSalesListAfterAjax) {
				window.mkSalesListAfterAjax({ skipSearchReinit: true });
			}
			setTimeout(postRender, 0);
		});
		if (!window.app) {
			return;
		}
		app.event.on('post.listViewFilter.click', function () {
			setTimeout(postRender, 0);
		});
		app.event.on('post.listViewInlineSearch.click', function () {
			setTimeout(postRender, 0);
		});
	}

	function relocatePaginationBelowTable() {
		if (window.MkSalesListShared && typeof window.MkSalesListShared.relocatePaginationFooter === 'function') {
			window.MkSalesListShared.relocatePaginationFooter();
		}
	}

	$(function () {
		if (!isManagementProjectTaskList()) {
			return;
		}
		document.documentElement.classList.add('mk-projecttask-list-management');
		bindListHooks();
		relocatePaginationBelowTable();
		postRender();
		setTimeout(postRender, 150);
		$(document).on('mkProjectTaskListPostLoad', relocatePaginationBelowTable);
		$(document).on('mkProjectTaskListPostLoad', function () {
			setTimeout(postRender, 0);
		});
	});

	window.__mkProjectTaskAudit = function () {
		if (!isManagementProjectTaskList()) {
			return { ok: false, reason: 'not-management-projecttask-list' };
		}
		var $table = $('#listview-table');
		var headers = [];
		$table.find('thead tr.listViewContentHeader th').each(function (i) {
			var $th = $(this);
			headers.push({
				i: i,
				field: $th.data('columnname') || $th.data('fieldname'),
				classes: this.className,
				width: $th.getBoundingClientRect().width
			});
		});
		return { ok: true, headers: headers, tableWidth: $table.width() };
	};
})(jQuery);
