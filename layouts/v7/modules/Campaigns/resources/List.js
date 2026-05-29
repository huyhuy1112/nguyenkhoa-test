/**
 * Campaigns list (Marketing): scroll, pagination, search placeholders, status/type pills, avatars.
 * Scope: body[data-module="Campaigns"][data-view="List"][data-app="MARKETING"]
 */
(function ($) {
	'use strict';

	var HIDDEN_LIST_COLUMNS = ['expectedrevenue'];

	var SEARCH_PLACEHOLDERS = {
		campaignname: 'Campaign name',
		campaigntype: 'Type',
		campaignstatus: 'Status',
		closingdate: 'Close date',
		assigned_user_id: 'Assigned to',
		created_user_id: 'Created by',
		smcreatorid: 'Created by'
	};

	function isCampaignsMarketingList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Campaigns' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'MARKETING') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'Campaigns' && params.get('view') === 'List' && params.get('app') === 'MARKETING';
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
		if (!isCampaignsMarketingList()) {
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
		if (!isCampaignsMarketingList()) {
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

	function isHiddenColumn(fieldName) {
		if (!fieldName) {
			return false;
		}
		return HIDDEN_LIST_COLUMNS.indexOf(String(fieldName).toLowerCase()) >= 0;
	}

	function hideListColumns($table) {
		if (!$table || !$table.length) {
			return;
		}
		$table.find('thead a[data-columnname]').each(function () {
			var name = $(this).attr('data-columnname');
			if (isHiddenColumn(name)) {
				$(this).closest('th').addClass('mk-camp-list-col-hidden');
			}
		});
		$table.find('tr.searchRow th').each(function () {
			var $th = $(this);
			var name = $th.attr('data-columnname');
			if (!name) {
				var $input = $th.find('input, select').first();
				name = $input.length ? $input.attr('name') : '';
				if (name) {
					name = String(name).replace(/\[\]$/, '');
				}
			}
			if (isHiddenColumn(name)) {
				$th.addClass('mk-camp-list-col-hidden');
			}
		});
		$table.find('tbody td[data-name]').each(function () {
			if (isHiddenColumn($(this).attr('data-name'))) {
				$(this).addClass('mk-camp-list-col-hidden');
			}
		});
	}

	function applyColumnClasses($table) {
		if (!$table || !$table.length) {
			return;
		}
		$table.find('thead tr.listViewContentHeader th').first().addClass('mk-camp-col-control');
		$table.find('thead tr.searchRow th').first().addClass('mk-camp-col-control');
		$table.find('tbody td.listViewRecordActions').addClass('mk-camp-col-control');
		$table.find('thead a[data-columnname="campaignname"]').closest('th').addClass('mk-camp-col-name');
		$table.find('tbody td[data-name="campaignname"]').addClass('mk-camp-col-name');
		$table.find('tr.searchRow th').each(function () {
			var $th = $(this);
			var name = $th.attr('data-columnname');
			if (!name) {
				var $input = $th.find('input, select').first();
				name = $input.length ? $input.attr('name') : '';
				if (name) {
					name = String(name).replace(/\[\]$/, '');
				}
			}
			if (name === 'campaignname') {
				$th.addClass('mk-camp-col-name');
			}
		});
	}

	function markTable() {
		if (!isCampaignsMarketingList()) {
			return;
		}
		var $table = $('#listViewContent #listview-table');
		$table.addClass('mk-camp-table mk-camp-table-layout');
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
		var selectors = [
			'td[data-name="assigned_user_id"] .value',
			'td[data-name="created_user_id"] .value',
			'td[data-name="smcreatorid"] .value'
		];
		$(context).find(selectors.join(',')).each(function () {
			var $value = $(this);
			if ($value.find('.mk-camp-avatar').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var initials = initialsFromName(text);
			$value.addClass('mk-camp-has-creator').empty().append(
				$('<span>', { 'class': 'mk-camp-avatar', text: initials }),
				$('<span>', { 'class': 'mk-camp-avatar__label', text: text })
			);
		});
	}

	function enhanceTypePills(context) {
		$(context).find('td[data-name="campaigntype"]').each(function () {
			var $td = $(this);
			if ($td.hasClass('mk-camp-list-col-hidden')) {
				return;
			}
			$td.css({ overflow: 'visible' });
			var $value = $td.find('.value').first();
			if (!$value.length) {
				return;
			}
			if ($value.find('.mk-camp-type-pill').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			$value.empty().append(
				$('<span>', { 'class': 'mk-camp-type-pill', text: text })
			);
		});
	}

	function enhanceStatusPills(context) {
		$(context).find('td[data-name="campaignstatus"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-camp-status-pill').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var key = slugify(text);
			$value.empty().append(
				$('<span>', { 'class': 'mk-camp-status-pill mk-camp-status-pill--' + key }).append(
					$('<span>', { 'class': 'mk-camp-status-pill__dot', 'aria-hidden': 'true' }),
					$('<span>', { 'class': 'mk-camp-status-pill__text', text: text })
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
		if (!isCampaignsMarketingList()) {
			return;
		}
		document.body.classList.remove('mk-campaigns-ui-loading');
		document.body.classList.add('mk-campaigns-ui-ready');
		document.documentElement.classList.add('mk-campaigns-ui-ready');
	}

	function afterListLayout() {
		if (!isCampaignsMarketingList()) {
			return;
		}
		relocatePagination();
		markTable();
		enhanceTypePills(document);
		enhanceStatusPills(document);
		enhanceCreatedBy(document);
		applySearchPlaceholders(document);
		fixListScrollContainer();
		setReadyState();
	}

	window.applyCampaignsListUi = afterListLayout;

	function init() {
		if (!isCampaignsMarketingList()) {
			return;
		}
		document.body.classList.add('mk-campaigns-ui-loading');

		var root = $('#listViewContent');
		if (!root.length) {
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
		$(window).on('resize.mkCampList', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				if (isCampaignsMarketingList()) {
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
