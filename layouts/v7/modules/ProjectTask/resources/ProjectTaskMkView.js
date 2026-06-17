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

	var GRID_FIELD_LABELS = {
		projecttaskname: 'Tên nhiệm vụ',
		projectid: 'Dự án',
		projecttaskpriority: 'Ưu tiên',
		projecttaskprogress: 'Tiến độ',
		startdate: 'Ngày bắt đầu',
		enddate: 'Ngày kết thúc',
		assigned_user_id: 'Phụ trách'
	};

	function isGridView() {
		return (
			$('#listViewContent').hasClass('mk-so-is-view-grid') ||
			document.body.classList.contains('mk-so-is-view-grid')
		);
	}

	function priorityKeyFromText(text) {
		var t = String(text || '')
			.toLowerCase()
			.trim();
		if (!t) {
			return 'default';
		}
		if (t.indexOf('cao') >= 0 || t.indexOf('high') >= 0) {
			return 'high';
		}
		if (t.indexOf('trung') >= 0 || t.indexOf('medium') >= 0 || t.indexOf('normal') >= 0) {
			return 'medium';
		}
		if (t.indexOf('thấp') >= 0 || t.indexOf('low') >= 0) {
			return 'low';
		}
		return 'default';
	}

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
			if (!$cell.length) {
				return;
			}
			if ($cell.find('.mk-projecttask-priority-pill').length) {
				return;
			}
			var raw = $.trim($cell.text());
			if (!raw) {
				return;
			}
			var key = priorityKeyFromText(raw);
			$cell.html(
				'<span class="mk-projecttask-priority-pill mk-projecttask-priority-pill--' +
					key +
					'">' +
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
		var grid = isGridView();
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var $cell = $row.find('td[data-name="projecttaskprogress"] .value').first();
			if (!$cell.length) {
				return;
			}
			var rawText = $.trim($cell.text());
			var $existing = $cell.find('.mk-projecttask-progress, .mk-projecttask-progress-grid').first();
			if ($existing.length) {
				var existingGrid = $existing.hasClass('mk-projecttask-progress-grid');
				if (existingGrid === grid) {
					return;
				}
				$cell.empty();
			}
			var info = parseProgress(rawText);
			var label = info.label || (info.pct > 0 ? info.pct + '%' : '');
			if (grid) {
				$cell.html(
					'<div class="mk-projecttask-progress-grid mk-projecttask-progress-grid--' +
						info.tone +
						'">' +
						'<div class="mk-projecttask-progress-grid__track">' +
						'<div class="mk-projecttask-progress-grid__fill" style="width:' +
						info.pct +
						'%"></div></div>' +
						(label
							? '<span class="mk-projecttask-progress-grid__label">' +
								$('<span/>').text(label).html() +
								'</span>'
							: '') +
						'</div>'
				);
				return;
			}
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

	function applyGridFieldLabels() {
		var $table = $('#listview-table');
		if (!$table.length) {
			return;
		}
		var labels = $.extend({}, GRID_FIELD_LABELS);
		$table.find('thead tr.listViewContentHeader th').each(function () {
			var $th = $(this);
			var field = fieldFromHeaderTh($th);
			if (!field) {
				return;
			}
			var headerText = $.trim($th.find('a.listViewContentHeaderValues').text());
			if (headerText) {
				labels[field] = headerText;
			}
		});
		$table.find('tbody td.listViewEntryValue[data-name]').each(function () {
			var $td = $(this);
			var name = $td.data('name');
			if (name && labels[name]) {
				$td.attr('data-field-label', labels[name]);
			}
		});
	}

	function hideEmptyRelatedInGrid() {
		if (!isGridView()) {
			return;
		}
		$('#listview-table tbody tr.listViewEntries td[data-name="projectid"]').each(function () {
			var $td = $(this);
			var text = $.trim($td.find('.value').text());
			$td.toggleClass('mk-projecttask-grid-empty-related', !text);
		});
	}

	function syncGridModeClass() {
		var isGrid = isGridView();
		document.documentElement.classList.toggle('mk-projecttask-grid-active', isGrid);
		$('#listViewContent').toggleClass('mk-projecttask-grid-active', isGrid);
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

	function ensureProgressSearchPlaceholder() {
		var $table = $('#listview-table');
		if (!$table.length) {
			return;
		}
		$table
			.find(
				'thead tr.searchRow th.mk-col-progress, thead tr.searchRow th:has([name="projecttaskprogress"])'
			)
			.each(function () {
				var $th = $(this);
				if ($th.find('input, select, .select2-container, .mk-search-no-filter').length) {
					return;
				}
				$th.append(
					'<input type="text" class="mk-search-no-filter" disabled readonly tabindex="-1" value="" aria-hidden="true" />'
				);
			});
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
		syncGridModeClass();
		assignColumnClasses();
		applyGridFieldLabels();
		normalizeSearchFilters();
		ensureProgressSearchPlaceholder();
		initDateSearchPickers();
		renderPriorityPills();
		renderProgressBars();
		renderAssigneeAvatars();
		enhanceTaskNameLinks();
		hideEmptyRelatedInGrid();
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
		$(document).on('click.mkProjectTaskLayoutEnhance', '.mk-so-toggle-layout', function () {
			setTimeout(postRender, 60);
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
