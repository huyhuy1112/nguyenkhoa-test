/**
 * Plans list (Marketing): scroll, pagination, search placeholders, status pills, avatars.
 * Scope: body[data-module="Plans"][data-view="List"][data-app="MARKETING"]
 */
(function ($) {
	'use strict';

	var HIDDEN_LIST_COLUMNS = [];

	var SEARCH_PLACEHOLDERS = {
		planname: 'Plan name',
		plan_status: 'Status',
		start_date: 'Start date',
		end_date: 'End date',
		assigned_user_id: 'Assigned to',
		created_user_id: 'Created by',
		smcreatorid: 'Created by'
	};

	function isPlansMarketingList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Plans' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'MARKETING') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'Plans' && params.get('view') === 'List' && params.get('app') === 'MARKETING';
	}

	function slugify(text) {
		return (text || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
	}

	function destroyPerfectScrollbar($tc) {
		if (!$tc || !$tc.length) {
			return;
		}
		try {
			if ($.fn.perfectScrollbar) {
				$tc.perfectScrollbar('destroy');
			}
		} catch (e) {
			/* ignore */
		}
		$tc.removeClass('ps ps--active-x ps--active-y ps--scrolling-x ps--scrolling-y');
		$tc.find('.ps__rail-x, .ps__rail-y, .ps__thumb-x, .ps__thumb-y').remove();
	}

	function fixListScrollContainer() {
		if (!isPlansMarketingList()) {
			return;
		}
		var $tc = $('#listViewContent #table-content');
		if (!$tc.length) {
			return;
		}
		destroyPerfectScrollbar($tc);
		$tc.css({
			position: 'relative',
			width: '100%',
			height: 'auto',
			maxHeight: '',
			overflowX: 'auto',
			overflowY: 'auto',
			WebkitOverflowScrolling: 'touch',
			pointerEvents: 'auto'
		});
		$('#listViewContent #scroller_wrapper.bottom-fixed-scroll, #listViewContent .bottom-fixed-scroll').css({
			display: 'none',
			height: 0,
			margin: 0,
			padding: 0,
			border: 'none',
			overflow: 'hidden',
			pointerEvents: 'none',
			position: 'absolute',
			left: '-9999px',
			width: 0
		});
		var $table = $('#listViewContent #listview-table');
		if ($table.length && $.fn.floatThead) {
			try {
				$table.floatThead('destroy');
			} catch (e2) {
				/* not initialized */
			}
		}
	}

	function relocatePagination() {
		if (!isPlansMarketingList()) {
			return;
		}
		var footer = document.querySelector('#listViewContent .mk-so-filter-row__footer');
		var table = document.getElementById('table-content');
		if (!footer || !table || !table.parentNode) {
			return;
		}
		if (table.nextSibling === footer) {
			return;
		}
		table.parentNode.insertBefore(footer, table.nextSibling);
	}

	function hideListColumns($table) {
		if (!$table || !$table.length || !HIDDEN_LIST_COLUMNS.length) {
			return;
		}
		$table.find('thead a[data-columnname]').each(function () {
			var name = $(this).attr('data-columnname');
			if (HIDDEN_LIST_COLUMNS.indexOf(String(name).toLowerCase()) >= 0) {
				$(this).closest('th').addClass('mk-plan-list-col-hidden');
			}
		});
	}

	function applyColumnClasses($table) {
		if (!$table || !$table.length) {
			return;
		}
		$table.find('thead tr.listViewContentHeader th').first().addClass('mk-plan-col-control');
		$table.find('thead tr.searchRow th').first().addClass('mk-plan-col-control');
		$table.find('tbody td.listViewRecordActions').addClass('mk-plan-col-control');
		$table.find('thead a[data-columnname="planname"]').closest('th').addClass('mk-plan-col-name');
		$table.find('tbody td[data-name="planname"]').addClass('mk-plan-col-name');
	}

	function markTable() {
		if (!isPlansMarketingList()) {
			return;
		}
		var $table = $('#listViewContent #listview-table');
		$table.addClass('mk-plan-table mk-plan-table-layout');
		hideListColumns($table);
		applyColumnClasses($table);
	}

	function initialsFromName(name) {
		var parts = (name || '').trim().split(/\s+/).filter(Boolean);
		if (!parts.length) {
			return '?';
		}
		if (parts.length === 1) {
			return parts[0].substring(0, 2).toUpperCase();
		}
		return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
	}

	function enhanceCreatedBy(context) {
		$(context).find([
			'td[data-name="assigned_user_id"] .value',
			'td[data-name="created_user_id"] .value',
			'td[data-name="smcreatorid"] .value'
		].join(',')).each(function () {
			var $value = $(this);
			if ($value.find('.mk-plan-avatar').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			$value.addClass('mk-plan-has-creator').empty().append(
				$('<span>', { 'class': 'mk-plan-avatar', text: initialsFromName(text) }),
				$('<span>', { 'class': 'mk-plan-avatar__label', text: text })
			);
		});
	}

	function enhanceStatusPills(context) {
		$(context).find('td[data-name="plan_status"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-plan-status-pill').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var key = slugify(text);
			$value.empty().append(
				$('<span>', { 'class': 'mk-plan-status-pill mk-plan-status-pill--' + key }).append(
					$('<span>', { 'class': 'mk-plan-status-pill__dot', 'aria-hidden': 'true' }),
					$('<span>', { 'class': 'mk-plan-status-pill__text', text: text })
				)
			);
		});
	}

	function applySearchPlaceholders(context) {
		$(context).find('tr.searchRow th').each(function () {
			var $th = $(this);
			var name = $th.attr('data-columnname') || $th.data('columnname');
			if (!name) {
				var $input = $th.find('input.listSearchContributor, input[type="text"]').first();
				if ($input.length) {
					name = $input.attr('name') || $input.data('columnname');
					if (name) {
						name = String(name).replace(/\[\]$/, '');
					}
				}
			}
			if (!name || !SEARCH_PLACEHOLDERS[name]) {
				return;
			}
			$th.find('input.listSearchContributor, input[type="text"]').each(function () {
				var $inp = $(this);
				if (!$inp.attr('placeholder')) {
					$inp.attr('placeholder', SEARCH_PLACEHOLDERS[name]);
				}
			});
		});
	}

	function setReadyState() {
		if (!isPlansMarketingList()) {
			return;
		}
		document.body.classList.remove('mk-plans-ui-loading');
		document.body.classList.add('mk-plans-ui-ready');
		document.documentElement.classList.add('mk-plans-ui-ready');
	}

	function afterListLayout() {
		if (!isPlansMarketingList()) {
			return;
		}
		relocatePagination();
		markTable();
		enhanceStatusPills(document);
		enhanceCreatedBy(document);
		applySearchPlaceholders(document);
		fixListScrollContainer();
		setReadyState();
	}

	window.applyPlansListUi = afterListLayout;

	function init() {
		if (!isPlansMarketingList()) {
			return;
		}
		document.body.classList.add('mk-plans-ui-loading');

		if (!$('#listViewContent').length) {
			setReadyState();
			return;
		}

		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.listViewFilter.click', function () {
				setTimeout(afterListLayout, 200);
			});
			app.event.on('Vtiger.Post.MenuToggle', function () {
				setTimeout(fixListScrollContainer, 80);
			});
		}

		var resizeTimer;
		$(window).on('resize.mkPlanList', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				if (isPlansMarketingList()) {
					fixListScrollContainer();
				}
			}, 150);
		});

		setTimeout(afterListLayout, 200);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
