/**
 * Quotes list (SALES): scroll/port, quote stage pill, creator chips, stable toolbar (no MutationObserver).
 * Scope: body[data-module="Quotes"][data-view="List"][data-app="SALES"]
 */
(function ($) {
	'use strict';

	var SEARCH_PLACEHOLDERS = {
		subject: 'Subject',
		quotestage: 'Quote stage',
		account_id: 'Organization',
		potential_id: 'Opportunity',
		contact_id: 'Contact',
		total: 'Total',
		hdnGrandTotal: 'Total',
		assigned_user_id: 'Created by',
		created_user_id: 'Created by',
		smcreatorid: 'Created by'
	};

	var CREATOR_SELECTORS = [
		'td[data-name="created_user_id"] .value',
		'td[data-name="smcreatorid"] .value',
		'td[data-name="assigned_user_id"] .value'
	];

	function isQuotesSalesList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Quotes' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SALES') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'Quotes' && params.get('view') === 'List' && params.get('app') === 'SALES';
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
		if (!isQuotesSalesList()) {
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
		if (!isQuotesSalesList()) {
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

	function markTable() {
		if (!isQuotesSalesList()) {
			return;
		}
		$('#listViewContent #listview-table').addClass('mk-qt-table');
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

	function enhanceQuoteStage(context) {
		$(context).find('td[data-name="quotestage"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-qt-stage-pill').length) {
				return;
			}
			var $link = $value.find('a').first();
			var text = $.trim($link.length ? $link.text() : $value.text());
			if (!text) {
				return;
			}
			if ($link.length) {
				$link.addClass('mk-qt-stage-pill');
				return;
			}
			$value.empty().append($('<span>', { class: 'mk-qt-stage-pill', text: text }));
		});
	}

	function enhanceCreatedBy(context) {
		$(context).find(CREATOR_SELECTORS.join(',')).each(function () {
			var $value = $(this);
			if ($value.find('.mk-qt-avatar').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var initials = initialsFromName(text);
			$value.addClass('mk-qt-has-creator').empty().append(
				$('<span>', { class: 'mk-qt-avatar', text: initials }),
				$('<span>', { class: 'mk-qt-avatar__label', text: text })
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
		if (!isQuotesSalesList()) {
			return;
		}
		document.body.classList.remove('mk-quotes-list-ui-loading');
		document.body.classList.add('mk-quotes-list-ui-ready');
		document.documentElement.classList.add('mk-quotes-list-ui-ready');
	}

	function getSavedLayoutMode() {
		try {
			return window.localStorage.getItem(LAYOUT_STORAGE_KEY) === 'grid' ? 'grid' : 'list';
		} catch (e) {
			return 'list';
		}
	}

	function applyLayoutMode(mode) {
		if (!isQuotesSalesList()) {
			return;
		}
		var isGrid = mode === 'grid';
		var $lv = $('#listViewContent');
		$lv.toggleClass('mk-qt-is-view-grid', isGrid);
		document.body.classList.toggle('mk-qt-is-view-grid', isGrid);

		var $listBtn = $('.mk-so-toggle-layout--list');
		var $gridBtn = $('.mk-so-toggle-layout--grid');
		$listBtn.toggleClass('is-active', !isGrid).attr('aria-pressed', !isGrid ? 'true' : 'false');
		$gridBtn.toggleClass('is-active', isGrid).attr('aria-pressed', isGrid ? 'true' : 'false');

		try {
			window.localStorage.setItem(LAYOUT_STORAGE_KEY, isGrid ? 'grid' : 'list');
		} catch (e2) {
			/* ignore */
		}
	}

	function bindViewLayoutToggle() {
		$(document).off('click.mkQtLayout', '.mk-so-toggle-layout--list, .mk-so-toggle-layout--grid');
		$(document).on('click.mkQtLayout', '.mk-so-toggle-layout--list', function (e) {
			if (!isQuotesSalesList()) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			applyLayoutMode('list');
			fixListScrollContainer();
		});
		$(document).on('click.mkQtLayout', '.mk-so-toggle-layout--grid', function (e) {
			if (!isQuotesSalesList()) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			applyLayoutMode('grid');
			fixListScrollContainer();
		});
		applyLayoutMode(getSavedLayoutMode());
	}

	function afterListLayout() {
		if (!isQuotesSalesList()) {
			return;
		}
		relocatePagination();
		markTable();
		enhanceQuoteStage(document);
		enhanceCreatedBy(document);
		applySearchPlaceholders(document);
		syncQuotesLayoutMode();
		fixListScrollContainer();
		setReadyState();
	}

	function init() {
		if (!isQuotesSalesList()) {
			return;
		}
		document.body.classList.add('mk-quotes-list-ui-loading');

		var root = $('#listViewContent');
		if (!root.length) {
			setReadyState();
			return;
		}

		$(document).on('click.mkQtList', '.mk-qt-trigger-columns', function (e) {
			e.preventDefault();
			var col = root.find('.listColumnFilter').first();
			if (col.length) {
				col.trigger('click');
			}
		});

		$(document).on('click.mkQtList', '.mk-qt-filter-trigger-search', function (e) {
			e.preventDefault();
			root.toggleClass('mk-qt-search-open');
		});

		if (window.MkSalesListShared && window.MkSalesListShared.bindViewLayoutToggle) {
			window.MkSalesListShared.bindViewLayoutToggle();
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
		$(window).on('resize.mkQuotesSalesList', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				if (isQuotesSalesList()) {
					fixListScrollContainer();
				}
			}, 150);
		});

		setTimeout(afterListLayout, 200);
	}

	window.__mkQuotesListUi = {
		isQuotesSalesList: isQuotesSalesList,
		afterListLayout: afterListLayout,
		bindViewLayoutToggle: bindViewLayoutToggle,
		applyLayoutMode: applyLayoutMode
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);

Inventory_List_Js('Quotes_List_Js', {}, {
	postLoadListViewRecords: function () {
		this._super();
		if (window.__mkQuotesListUi && window.__mkQuotesListUi.afterListLayout) {
			setTimeout(window.__mkQuotesListUi.afterListLayout, 100);
		}
	}
});
