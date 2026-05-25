/**
 * ProductsServices list (Sales app): scroll, pagination, type pills, name/price/assignee styling.
 * Scope: body[data-module="ProductsServices"][data-view="List"][data-app="SALES"]
 */
(function ($) {
	'use strict';

	var SEARCH_PLACEHOLDERS = {
		productsservicesname: 'Name',
		item_type: 'Type',
		price: 'Price',
		assigned_user_id: 'Assigned To'
	};

	var TYPE_FIELD_CANDIDATES = ['item_type', 'type', 'product_type'];

	var CREATOR_SELECTORS = [
		'td[data-name="assigned_user_id"] .value',
		'td[data-name="created_user_id"] .value',
		'td[data-name="smcreatorid"] .value'
	];

	function debugLog() {
		if (window.MK_PS_DEBUG) {
			console.log.apply(console, arguments);
		}
	}

	function isPsSalesList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'ProductsServices' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SALES') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'ProductsServices' && params.get('view') === 'List' && params.get('app') === 'SALES';
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
		if (!isPsSalesList()) {
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
		if (!isPsSalesList()) {
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
		if (!isPsSalesList()) {
			return;
		}
		$('#listViewContent #listview-table').addClass('mk-ps-table');
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

	function normalizeTypeKey(text) {
		var t = String(text || '').toLowerCase();
		if (t.indexOf('service') >= 0) {
			return 'service';
		}
		if (t.indexOf('product') >= 0) {
			return 'product';
		}
		return 'other';
	}

	function enhanceTypePills(context) {
		var i;
		for (i = 0; i < TYPE_FIELD_CANDIDATES.length; i++) {
			$(context).find('td.listViewEntryValue[data-name="' + TYPE_FIELD_CANDIDATES[i] + '"]').addClass('mk-ps-type-cell');
		}
		$(context).find('td.mk-ps-type-cell .value, td[data-name="item_type"] .value, td[data-name="type"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-ps-type-pill').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var key = normalizeTypeKey(text);
			var cls = 'mk-ps-type-pill';
			if (key === 'product') {
				cls += ' mk-ps-type-pill--product';
			} else if (key === 'service') {
				cls += ' mk-ps-type-pill--service';
			}
			$value.empty().append($('<span>', { 'class': cls, text: text }));
		});
	}

	function enhancePriceCells(context) {
		$(context).find('td[data-name="price"]').addClass('mk-ps-price-cell');
	}

	function enhanceCreatedBy(context) {
		$(context).find(CREATOR_SELECTORS.join(',')).each(function () {
			var $value = $(this);
			if ($value.find('.mk-ps-avatar').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var initials = initialsFromName(text);
			$value.addClass('mk-ps-has-creator').empty().append(
				$('<span>', { 'class': 'mk-ps-avatar', text: initials }),
				$('<span>', { 'class': 'mk-ps-avatar__label', text: text })
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
		if (!isPsSalesList()) {
			return;
		}
		document.body.classList.remove('mk-ps-ui-loading');
		document.body.classList.add('mk-ps-ui-ready');
		document.documentElement.classList.add('mk-ps-ui-ready');
	}

	var eventsBound = false;

	function afterListLayout() {
		if (!isPsSalesList()) {
			return;
		}
		relocatePagination();
		markTable();
		enhanceTypePills(document);
		enhancePriceCells(document);
		enhanceCreatedBy(document);
		applySearchPlaceholders(document);
		fixListScrollContainer();
		setReadyState();
		debugLog('mk-ps list layout applied');
	}

	function init() {
		if (!isPsSalesList()) {
			return;
		}
		document.body.classList.add('mk-ps-ui-loading');

		var root = $('#listViewContent');
		if (!root.length) {
			setReadyState();
			return;
		}

		if (!eventsBound && typeof app !== 'undefined' && app.event && app.event.on) {
			eventsBound = true;
			app.event.on('post.listViewFilter.click', function () {
				setTimeout(afterListLayout, 200);
			});
			app.event.on('Vtiger.Post.MenuToggle', function () {
				setTimeout(fixListScrollContainer, 80);
			});
		}

		var resizeTimer;
		$(window).on('resize.mkPsList', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				if (isPsSalesList()) {
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
