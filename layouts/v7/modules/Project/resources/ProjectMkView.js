/* global jQuery, app */
/**
 * Project List — MANAGEMENT app (Figma / ProjectTask pattern).
 */
(function ($) {
	'use strict';

	function isManagementProjectList() {
		var body = document.body;
		if (!body || body.getAttribute('data-module') !== 'Project' || body.getAttribute('data-view') !== 'List') {
			return false;
		}
		if ((body.getAttribute('data-app') || '').toUpperCase() === 'MANAGEMENT') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'Project' && params.get('view') === 'List' && params.get('app') === 'MANAGEMENT';
	}

	var COL_CLASS_BY_FIELD = {
		projectname: 'mk-col-name',
		linktoaccountscontacts: 'mk-col-related',
		startdate: 'mk-col-start-date',
		targetenddate: 'mk-col-end-date',
		progress: 'mk-col-progress',
		projectstatus: 'mk-col-status',
		assigned_user_id: 'mk-col-assigned'
	};

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
			$(this)
				.children('td')
				.each(function () {
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

	var GRID_FIELD_LABELS = {
		projectname: 'Tên dự án',
		linktoaccountscontacts: 'Liên quan tới',
		startdate: 'Ngày bắt đầu',
		targetenddate: 'Ngày kết thúc dự kiến',
		progress: 'Mức độ hoàn thành',
		projectstatus: 'Trạng thái',
		assigned_user_id: 'Phụ trách'
	};

	function slugify(text) {
		return String(text || '')
			.toLowerCase()
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '');
	}

	function statusKeyFromText(text) {
		var t = String(text || '')
			.toLowerCase()
			.trim();
		if (!t) {
			return 'default';
		}
		if (t.indexOf('đang thực hiện') >= 0 || t.indexOf('in progress') >= 0) {
			return 'in-progress';
		}
		if (t.indexOf('tạm dừng') >= 0 || t.indexOf('on hold') >= 0) {
			return 'on-hold';
		}
		if (t.indexOf('chờ') >= 0 && t.indexOf('phản hồi') >= 0) {
			return 'waiting';
		}
		if (t.indexOf('kickoff') >= 0 || t.indexOf('initiated') >= 0 || t.indexOf('đã kickoff') >= 0) {
			return 'initiated';
		}
		if (t.indexOf('dự kiến') >= 0 || t.indexOf('prospect') >= 0) {
			return 'prospecting';
		}
		if (t.indexOf('bàn giao') >= 0 || t.indexOf('delivered') >= 0) {
			return 'delivered';
		}
		if (t.indexOf('hoàn thành') >= 0 || t.indexOf('completed') >= 0) {
			return 'completed';
		}
		if (t.indexOf('archived') >= 0) {
			return 'archived';
		}
		return slugify(t) || 'default';
	}

	function parseProgress(raw) {
		var s = String(raw || '').trim();
		if (!s) {
			return null;
		}
		var m = s.match(/(\d+(?:\.\d+)?)/);
		if (!m) {
			return null;
		}
		var n = parseFloat(m[1]);
		if (isNaN(n)) {
			return null;
		}
		return Math.max(0, Math.min(100, n));
	}

	function progressVariant(pct) {
		if (pct >= 100) {
			return 'done';
		}
		if (pct >= 71) {
			return 'high';
		}
		if (pct >= 31) {
			return 'mid';
		}
		if (pct > 0) {
			return 'low';
		}
		return 'empty';
	}

	function getProgressFromCell($cell, $td) {
		var raw = $.trim($cell.text());
		if (!raw && $td && $td.length) {
			raw = $.trim(String($td.data('rawvalue') || ''));
		}
		var pct = parseProgress(raw);
		if (pct === null) {
			return null;
		}
		var label = raw.indexOf('%') >= 0 ? raw : pct + '%';
		return { pct: pct, label: label, variant: progressVariant(pct) };
	}

	function renderProgressBars() {
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var $td = $row.find('td[data-name="progress"]').first();
			var $cell = $td.find('.value').first();
			if (!$cell.length || $cell.find('.mk-project-progress').length) {
				return;
			}
			var info = getProgressFromCell($cell, $td);
			if (!info) {
				return;
			}
			$cell.html(
				'<div class="mk-project-progress mk-project-progress--' +
					info.variant +
					'">' +
					'<div class="mk-project-progress__track">' +
					'<div class="mk-project-progress__fill" style="width:' +
					info.pct +
					'%"></div></div>' +
					'<span class="mk-project-progress__label">' +
					$('<div/>').text(info.label).html() +
					'</span></div>'
			);
		});
	}

	function renderStatusPills() {
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var $td = $row.find('td[data-name="projectstatus"]').first();
			var $cell = $td.find('.value').first();
			if (!$cell.length || $cell.find('.mk-project-status-pill').length) {
				return;
			}
			var raw = $.trim($cell.text());
			if (!raw && $td.length) {
				raw = $.trim(String($td.data('rawvalue') || ''));
			}
			if (!raw) {
				return;
			}
			var key = statusKeyFromText(raw);
			var isGrid = $('#listViewContent').hasClass('mk-so-is-view-grid');
			if (isGrid) {
				$cell.html(
					'<span class="mk-project-status-pill mk-project-status-pill--' +
						key +
						'">' +
						'<span class="mk-project-status-pill__dot" aria-hidden="true"></span>' +
						'<span class="mk-project-status-pill__text">' +
						$('<div/>').text(raw).html() +
						'</span></span>'
				);
				return;
			}
			$td.addClass('mk-project-status-cell');
			$cell.html(
				'<span class="mk-project-status-pill mk-project-status-pill--' +
					key +
					'">' +
					'<span class="mk-project-status-pill__dot" aria-hidden="true"></span>' +
					'<span class="mk-project-status-pill__text">' +
					$('<div/>').text(raw).html() +
					'</span></span>'
			);
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

	function renderAssigneeAvatars() {
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var $cell = $row.find('td[data-name="assigned_user_id"] .value').first();
			if (!$cell.length || $cell.find('.mk-project-assignee').length) {
				return;
			}
			var name = $.trim($cell.text());
			if (!name) {
				return;
			}
			$cell.html(
				'<span class="mk-project-assignee">' +
					'<span class="mk-project-assignee__avatar">' +
					$('<div/>').text(initialsFromName(name)).html() +
					'</span>' +
					'<span class="mk-project-assignee__name">' +
					$('<div/>').text(name).html() +
					'</span></span>'
			);
		});
	}

	function enhanceProjectNameLinks() {
		$('#listview-table td[data-name="projectname"] a').addClass('mk-project-name-link');
	}

	function hideEmptyRelatedInGrid() {
		if (!$('#listViewContent').hasClass('mk-so-is-view-grid')) {
			return;
		}
		$('#listview-table tbody tr.listViewEntries td[data-name="linktoaccountscontacts"]').each(function () {
			var $td = $(this);
			var text = $.trim($td.find('.value').text());
			$td.toggleClass('mk-project-grid-empty-related', !text);
		});
	}

	function normalizeSearchFilters() {
		var $table = $('#listview-table');
		if (!$table.length) {
			return;
		}
		$table.find('thead tr.searchRow .select2-container').each(function () {
			$(this).css({ width: '100%', maxWidth: '100%' });
		});
		$table.find('thead tr.searchRow .select2_search_div').css({ width: '100%', maxWidth: '100%' });
	}

	function ensureProgressSearchPlaceholder() {
		var $table = $('#listview-table');
		if (!$table.length) {
			return;
		}
		$table.find('thead tr.searchRow th.mk-col-progress, thead tr.searchRow th:has([name="progress"])').each(function () {
			var $th = $(this);
			if ($th.find('input, select, .select2-container, .mk-search-no-filter').length) {
				return;
			}
			$th.append(
				'<input type="text" class="mk-search-no-filter" disabled readonly tabindex="-1" value="" aria-hidden="true" />'
			);
		});
	}

	function relocatePaginationBelowTable() {
		if (window.MkSalesListShared && typeof window.MkSalesListShared.relocatePaginationFooter === 'function') {
			window.MkSalesListShared.relocatePaginationFooter();
		}
	}

	function syncCheckedRowHighlight() {
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var checked = $row.find('.listViewEntriesCheckBox').prop('checked');
			$row.toggleClass('mk-project-row-selected', !!checked);
		});
	}

	function bindCheckboxRowHighlight() {
		$(document)
			.off('change.mkProjectRowSelect', '#listview-table .listViewEntriesCheckBox')
			.on('change.mkProjectRowSelect', '#listview-table .listViewEntriesCheckBox', function () {
				var $row = $(this).closest('tr.listViewEntries');
				$row.toggleClass('mk-project-row-selected', this.checked);
			});
		$(document)
			.off('change.mkProjectRowSelectAll', '#listview-table .listViewEntriesMainCheckBox')
			.on('change.mkProjectRowSelectAll', '#listview-table .listViewEntriesMainCheckBox', function () {
				setTimeout(syncCheckedRowHighlight, 0);
			});
	}

	function fixListGridTogglePlacement() {
		var $toolbarStart = $('#listview-actions .mk-so-filter-row__start').first();
		if (!$toolbarStart.length) {
			return;
		}
		var $toolbarToggle = $toolbarStart.find('.mk-so-toolbar-toggles').first();
		$('#listview-table .table-actions .mk-so-toolbar-toggles').each(function () {
			var $stray = $(this);
			if (!$toolbarToggle.length) {
				$toolbarToggle = $stray.detach();
				$toolbarStart.prepend($toolbarToggle);
			} else {
				$stray.remove();
			}
		});
	}

	function initDateSearchPickers() {
		if (window.MkSalesListShared && typeof MkSalesListShared.initManagementDateSearchPickers === 'function') {
			MkSalesListShared.initManagementDateSearchPickers();
		}
	}

	window.mkProjectListInitDatePickers = initDateSearchPickers;

	function syncGridModeClass() {
		var isGrid =
			$('#listViewContent').hasClass('mk-so-is-view-grid') ||
			document.body.classList.contains('mk-so-is-view-grid');
		document.documentElement.classList.toggle('mk-project-grid-active', isGrid);
		$('#listViewContent').toggleClass('mk-project-grid-active', isGrid);
	}

	function postRender() {
		if (!isManagementProjectList()) {
			return;
		}
		syncGridModeClass();
		fixListGridTogglePlacement();
		syncCheckedRowHighlight();
		assignColumnClasses();
		applyGridFieldLabels();
		normalizeSearchFilters();
		ensureProgressSearchPlaceholder();
		initDateSearchPickers();
		renderProgressBars();
		renderStatusPills();
		renderAssigneeAvatars();
		enhanceProjectNameLinks();
		hideEmptyRelatedInGrid();
	}

	function bindListHooks() {
		if (!isManagementProjectList()) {
			return;
		}
		$(document).on('mkProjectListPostLoad', function () {
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
		$(document).on('click.mkProjectLayoutEnhance', '.mk-so-toggle-layout', function () {
			setTimeout(postRender, 60);
		});
	}

	$(function () {
		if (!isManagementProjectList()) {
			return;
		}
		document.documentElement.classList.add('mk-project-list-management');
		bindCheckboxRowHighlight();
		bindListHooks();
		relocatePaginationBelowTable();
		postRender();
		setTimeout(postRender, 150);
		$(document).on('mkProjectListPostLoad', relocatePaginationBelowTable);
		$(document).on('mkProjectListPostLoad', syncCheckedRowHighlight);
		$(document).on('mkProjectListPostLoad', function () {
			setTimeout(postRender, 0);
		});
	});
})();
