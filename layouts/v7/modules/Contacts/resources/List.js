/**
 * Contacts list (Sales): native scroll, pagination relocate, cell enhancements.
 * Scope: body[data-module="Contacts"][data-view="List"][data-app="SALES"]
 */
(function ($) {
	'use strict';

	var applyTimer = null;
	var applyInProgress = false;

	function isSalesContactsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Contacts' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SALES' || appName === 'MARKETING') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		var app = params.get('app');
		return params.get('module') === 'Contacts' && params.get('view') === 'List' &&
			(app === 'SALES' || app === 'MARKETING');
	}

	function isMarketingContactsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Contacts' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'MARKETING') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'Contacts' && params.get('view') === 'List' && params.get('app') === 'MARKETING';
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
		if (!isSalesContactsList()) {
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
		if (!isSalesContactsList()) {
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

	function markContactTable() {
		if (!isSalesContactsList()) {
			return;
		}
		$('#listViewContent #listview-table').addClass('mk-contact-table');
	}

	var MK_COL_CLASS_NAMES =
		'mk-col-control mk-col-contact-first mk-col-contact-last mk-col-contact-title mk-col-contact-org ' +
		'mk-col-contact-email mk-col-contact-phone mk-col-contact-assigned mk-col-contact-address';

	var COL_CLASS_BY_FIELD = {
		firstname: 'mk-col-contact-first',
		lastname: 'mk-col-contact-last',
		title: 'mk-col-contact-title',
		account_id: 'mk-col-contact-org',
		email: 'mk-col-contact-email',
		secondaryemail: 'mk-col-contact-email',
		phone: 'mk-col-contact-phone',
		mobile: 'mk-col-contact-phone',
		homephone: 'mk-col-contact-phone',
		assigned_user_id: 'mk-col-contact-assigned',
		created_user_id: 'mk-col-contact-assigned',
		smcreatorid: 'mk-col-contact-assigned',
		mailingstreet: 'mk-col-contact-address',
		otherstreet: 'mk-col-contact-address',
		mailingcity: 'mk-col-contact-address'
	};

	var COL_WIDTH_BY_CLASS = {
		'mk-col-control': '152px',
		'mk-col-contact-first': '130px',
		'mk-col-contact-last': '168px',
		'mk-col-contact-title': '140px',
		'mk-col-contact-org': '200px',
		'mk-col-contact-email': '200px',
		'mk-col-contact-phone': '140px',
		'mk-col-contact-assigned': '168px',
		'mk-col-contact-address': '180px'
	};

	function fieldFromHeaderTh($th) {
		var $a = $th.find('a.listViewContentHeaderValues').first();
		if ($a.length) {
			return $a.data('columnname') || '';
		}
		return '';
	}

	function assignColumnClasses() {
		var $table = $('#listViewContent #listview-table');
		if (!$table.length) {
			return;
		}
		$table.find('th, td').removeClass(MK_COL_CLASS_NAMES);
		var $headerCells = $table.find('thead tr.listViewContentHeader th');
		$headerCells.each(function () {
			var $th = $(this);
			var field = fieldFromHeaderTh($th);
			if (field && COL_CLASS_BY_FIELD[field]) {
				$th.addClass(COL_CLASS_BY_FIELD[field]);
			}
			if ($th.find('.table-actions').length) {
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

	function widthForHeaderTh($th) {
		var cls;
		for (cls in COL_WIDTH_BY_CLASS) {
			if ($th.hasClass(cls)) {
				return COL_WIDTH_BY_CLASS[cls];
			}
		}
		return '';
	}

	function applyColgroup() {
		var $table = $('#listViewContent #listview-table');
		if (!$table.length) {
			return;
		}
		var $headerCells = $table.find('thead tr.listViewContentHeader th');
		if (!$headerCells.length) {
			return;
		}
		$table.find('colgroup').remove();
		var $colgroup = $('<colgroup>');
		$headerCells.each(function () {
			var $th = $(this);
			var width = widthForHeaderTh($th);
			var $col = $('<col>');
			if (width) {
				$col.attr('style', 'width:' + width);
			}
			$colgroup.append($col);
		});
		$table.prepend($colgroup);
	}

	function refreshContactsTableLayout() {
		if (!isSalesContactsList()) {
			return;
		}
		markContactTable();
		assignColumnClasses();
		applyColgroup();
		relocatePagination();
		fixListScrollContainer();
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

	function decodeHtmlEntities(str) {
		if (!str || str.indexOf('&') < 0) {
			return str;
		}
		var ta = document.createElement('textarea');
		var prev = String(str);
		var i;
		for (i = 0; i < 3; i++) {
			ta.innerHTML = prev;
			var next = ta.value;
			if (next === prev) {
				break;
			}
			prev = next;
		}
		return prev;
	}

	function decodeTextNode($target) {
		if (!$target || !$target.length) {
			return false;
		}
		var text = $.trim($target.text());
		if (!text || text.indexOf('&') < 0) {
			return false;
		}
		var decoded = decodeHtmlEntities(text);
		if (decoded === text) {
			return false;
		}
		$target.text(decoded);
		return true;
	}

	function fixEncodedNameCells(context) {
		var fields = ['firstname', 'lastname', 'account_id'];
		fields.forEach(function (field) {
			$(context)
				.find('td[data-name="' + field + '"]')
				.each(function () {
					var $td = $(this);
					if ($td.attr('data-mk-decoded') === '1') {
						return;
					}
					var $link = $td.find('a').first();
					var changed = false;
					if ($link.length) {
						changed = decodeTextNode($link);
					} else {
						var $value = $td.find('.value').first();
						if ($value.length && !$value.find('a').length) {
							changed = decodeTextNode($value);
						}
					}
					if (changed) {
						$td.attr('data-mk-decoded', '1');
					}
				});
		});
	}

	function enhanceTitlePills(context) {
		$(context).find('td[data-name="title"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-contact-title-pill').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			$value.empty().append($('<span>', { 'class': 'mk-contact-title-pill', text: text }));
		});
	}

	function enhanceCreatedBy(context) {
		var selectors = [
			'td[data-name="created_user_id"] .value',
			'td[data-name="smcreatorid"] .value'
		];
		$(context).find(selectors.join(',')).each(function () {
			var $value = $(this);
			if ($value.find('.mk-contact-avatar').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var initials = initialsFromName(text);
			$value.addClass('mk-contact-has-creator').empty().append(
				$('<span>', { 'class': 'mk-contact-avatar', text: initials }),
				$('<span>', { 'class': 'mk-contact-avatar__label', text: text })
			);
		});
	}

	function hasWorkToApply() {
		if (!isSalesContactsList()) {
			return false;
		}
		if (!$('.mk-contact-page').length) {
			return false;
		}

		var needsTitle = $('td[data-name="title"] .value').filter(function () {
			return $(this).find('.mk-contact-title-pill').length === 0 && $.trim($(this).text()) !== '';
		}).length > 0;

		var needsCreator = $('td[data-name="created_user_id"] .value, td[data-name="smcreatorid"] .value').filter(function () {
			return $(this).find('.mk-contact-avatar').length === 0 && $.trim($(this).text()) !== '';
		}).length > 0;

		var needsDecode = $('td[data-name="firstname"], td[data-name="lastname"], td[data-name="account_id"]').filter(function () {
			return $(this).attr('data-mk-decoded') !== '1' && $.trim($(this).text()).indexOf('&') >= 0;
		}).length > 0;

		var footer = document.querySelector('#listViewContent .mk-so-filter-row__footer');
		var table = document.getElementById('table-content');
		var paginationNeedsMove = footer && table && table.nextSibling !== footer;

		var tableUnmarked = !$('#listview-table').hasClass('mk-contact-table');

		return needsTitle || needsCreator || needsDecode || paginationNeedsMove || tableUnmarked;
	}

	function applyUi() {
		if (!isSalesContactsList()) {
			return;
		}
		if (applyInProgress) {
			return;
		}
		applyInProgress = true;
		try {
			refreshContactsTableLayout();
			if (!hasWorkToApply()) {
				document.documentElement.classList.add('mk-sales-list-ready');
				if (window.MkSalesListShared && typeof window.MkSalesListShared.revealSalesListUi === 'function') {
					window.MkSalesListShared.revealSalesListUi();
				}
				return;
			}
			fixEncodedNameCells(document);
			enhanceTitlePills(document);
			enhanceCreatedBy(document);
			if (isMarketingContactsList() && typeof window.mkMarketingListAfterAjax === 'function') {
				window.mkMarketingListAfterAjax();
			}
			document.documentElement.classList.add('mk-sales-list-ready');
			if (window.MkSalesListShared && typeof window.MkSalesListShared.revealSalesListUi === 'function') {
				window.MkSalesListShared.revealSalesListUi();
			}
		} finally {
			applyInProgress = false;
		}
	}

	function scheduleApply() {
		if (applyTimer) {
			clearTimeout(applyTimer);
		}
		applyTimer = setTimeout(function () {
			applyTimer = null;
			applyUi();
		}, 80);
	}

	function bindSafeEvents() {
		var root = $('#listViewContent');
		if (!root.length) {
			return;
		}

		$(document).off('.mkContactsListUi');
		$(document).on('click.mkContactsListUi', '.mk-so-trigger-columns, .mk-contact-trigger-columns', function (e) {
			e.preventDefault();
			root.find('.listColumnFilter').first().trigger('click');
		});
		$(document).on('click.mkContactsListUi', '.mk-so-filter-trigger-search, .mk-contact-filter-trigger-search', function (e) {
			e.preventDefault();
			if (isMarketingContactsList()) {
				if (typeof window.mkMarketingListAfterAjax === 'function') {
					window.mkMarketingListAfterAjax();
				}
			} else if (typeof window.mkSalesListAfterAjax === 'function') {
				window.mkSalesListAfterAjax();
			}
			var $row = root.find('tr.searchRow.listViewSearchContainer').first();
			if ($row.length && $row[0].scrollIntoView) {
				$row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			}
		});
		$(document).on('click.mkContactsListUi', '#listViewContent #NextPageButton, #listViewContent #PreviousPageButton, #listViewContent #pageToJumpSubmit', function () {
			scheduleApply();
		});
		$(document).on('click.mkContactsListUi', '#listViewContent .listViewContentHeaderValues, #listViewContent [data-trigger="listSearch"], #listViewContent [data-trigger="clearListSearch"]', function () {
			scheduleApply();
		});
	}

	function installContactsSalesAjaxHook() {
		if (window.__mkContactsListAjaxHooked) {
			return;
		}
		window.__mkContactsListAjaxHooked = true;
		var sharedAfterAjax = window.mkSalesListAfterAjax;
		window.mkSalesListAfterAjax = function (options) {
			if (typeof sharedAfterAjax === 'function') {
				sharedAfterAjax(options);
			}
			if (isSalesContactsList()) {
				scheduleApply();
			}
		};
	}

	function init() {
		if (!isSalesContactsList()) {
			return;
		}
		installContactsSalesAjaxHook();
		applyUi();
		bindSafeEvents();

		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.listViewFilter.click', function () {
				scheduleApply();
			});
		}

		var resizeTimer;
		$(window).on('resize.mkContactsList', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				if (isSalesContactsList()) {
					fixListScrollContainer();
				}
			}, 150);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
