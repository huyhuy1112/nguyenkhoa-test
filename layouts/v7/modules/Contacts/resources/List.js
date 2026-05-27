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

		var footer = document.querySelector('#listViewContent .mk-so-filter-row__footer');
		var table = document.getElementById('table-content');
		var paginationNeedsMove = footer && table && table.nextSibling !== footer;

		var tableUnmarked = !$('#listview-table').hasClass('mk-contact-table');

		return needsTitle || needsCreator || paginationNeedsMove || tableUnmarked;
	}

	function applyUi() {
		if (!isSalesContactsList()) {
			return;
		}
		if (applyInProgress) {
			return;
		}
		if (!hasWorkToApply()) {
			return;
		}
		applyInProgress = true;
		try {
			markContactTable();
			enhanceTitlePills(document);
			enhanceCreatedBy(document);
			relocatePagination();
			fixListScrollContainer();
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
			root.toggleClass('mk-so-search-open mk-contact-search-open');
		});
		$(document).on('click.mkContactsListUi', '#listViewContent #NextPageButton, #listViewContent #PreviousPageButton, #listViewContent #pageToJumpSubmit', function () {
			scheduleApply();
		});
		$(document).on('click.mkContactsListUi', '#listViewContent .listViewContentHeaderValues, #listViewContent [data-trigger="listSearch"], #listViewContent [data-trigger="clearListSearch"]', function () {
			scheduleApply();
		});
	}

	function init() {
		if (!isSalesContactsList()) {
			return;
		}
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
