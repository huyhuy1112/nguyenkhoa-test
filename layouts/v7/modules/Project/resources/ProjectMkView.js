/* global jQuery, app */
/**
 * Project List — MANAGEMENT app (Figma / ProjectTask pattern).
 */
(function ($) {
	'use strict';

	function isManagementProjectList() {
		var body = document.body;
		if (!body) {
			return false;
		}
		return (
			body.getAttribute('data-module') === 'Project' &&
			body.getAttribute('data-view') === 'List' &&
			body.getAttribute('data-app') === 'MANAGEMENT'
		);
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
		if (pct > 0) {
			return 'calculating';
		}
		return 'default';
	}

	function renderProgressBars() {
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var $cell = $row.find('td[data-name="progress"] .value').first();
			if (!$cell.length || $cell.find('.mk-project-progress').length) {
				return;
			}
			var raw = $.trim($cell.text());
			var pct = parseProgress(raw);
			if (pct === null) {
				return;
			}
			var variant = progressVariant(pct);
			var label = raw.indexOf('%') >= 0 ? raw : pct + '%';
			$cell.html(
				'<div class="mk-project-progress mk-project-progress--' +
					variant +
					'">' +
					'<div class="mk-project-progress__fill" style="width:' +
					pct +
					'%"></div>' +
					'<span class="mk-project-progress__label">' +
					$('<div/>').text(label).html() +
					'</span></div>'
			);
		});
	}

	function renderStatusPills() {
		$('#listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var $cell = $row.find('td[data-name="projectstatus"] .value').first();
			if (!$cell.length || $cell.find('.mk-project-status-pill').length) {
				return;
			}
			var raw = $.trim($cell.text());
			if (!raw) {
				return;
			}
			$cell.html(
				'<span class="mk-project-status-pill">' + $('<div/>').text(raw).html() + '</span>'
			);
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

	function postRender() {
		if (!isManagementProjectList()) {
			return;
		}
		fixListGridTogglePlacement();
		syncCheckedRowHighlight();
		assignColumnClasses();
		normalizeSearchFilters();
		renderProgressBars();
		renderStatusPills();
		renderAssigneeAvatars();
		enhanceProjectNameLinks();
	}

	function bindListHooks() {
		if (!isManagementProjectList()) {
			return;
		}
		$(document).on('mkProjectListPostLoad', function () {
			setTimeout(postRender, 0);
			setTimeout(postRender, 120);
		});
		if (!window.app) {
			return;
		}
		app.event.on('post.listViewFilter.click', function () {
			setTimeout(postRender, 0);
			setTimeout(postRender, 120);
		});
		app.event.on('post.listViewInlineSearch.click', function () {
			setTimeout(postRender, 0);
			setTimeout(postRender, 120);
		});
	}

	$(function () {
		if (!isManagementProjectList()) {
			return;
		}
		document.documentElement.classList.add('mk-project-list-management');
		relocatePaginationBelowTable();
		postRender();
		setTimeout(function () {
			relocatePaginationBelowTable();
			postRender();
		}, 0);
		setTimeout(function () {
			relocatePaginationBelowTable();
			postRender();
		}, 250);
		setTimeout(function () {
			relocatePaginationBelowTable();
			postRender();
		}, 800);
		bindListHooks();
		bindCheckboxRowHighlight();
		$(document).on('mkProjectListPostLoad', relocatePaginationBelowTable);
		$(document).on('mkProjectListPostLoad', syncCheckedRowHighlight);
	});
})();
