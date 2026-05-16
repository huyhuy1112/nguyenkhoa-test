/**
 * Accounts Organizations list (Sales): filter row wiring, native table scroll,
 * pagination footer placement, floatThead reflow after removing perfectScrollbar.
 * Scope: body[data-module="Accounts"][data-view="List"][data-app="SALES"]
 */
(function ($) {
	'use strict';

	function isAccountsSalesList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Accounts' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		return appName === 'SALES';
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

	/**
	 * Stock List.js uses perfectScrollbar on #table-content — custom rails often sit under
	 * .bottom-fixed-scroll (top:-19px) and do not drag like a native bar. Use one native scroller.
	 */
	function fixListScrollContainer() {
		if (!isAccountsSalesList()) {
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

		var $scroller = $('#listViewContent #scroller_wrapper.bottom-fixed-scroll, #listViewContent .bottom-fixed-scroll');
		$scroller.css({
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
				/* not initialized or already destroyed */
			}
		}
	}

	/** Figma: pagination bar below table; DOM hooks unchanged (same nodes, new parent). */
	function relocateOrgPagination() {
		if (!isAccountsSalesList()) {
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

	function markOrgTable() {
		if (!isAccountsSalesList()) {
			return;
		}
		$('#listViewContent #listview-table').addClass('mk-org-table');
	}

	function afterListLayout() {
		if (!isAccountsSalesList()) {
			return;
		}
		relocateOrgPagination();
		markOrgTable();
		fixListScrollContainer();
	}

	function init() {
		if (!isAccountsSalesList()) {
			return;
		}
		var root = $('#listViewContent');
		if (!root.length) {
			return;
		}

		$(document).on('click.mkOrgList', '.mk-org-trigger-columns', function (e) {
			e.preventDefault();
			var col = root.find('.listColumnFilter').first();
			if (col.length) {
				col.trigger('click');
			}
		});

		$(document).on('click.mkOrgList', '.mk-org-filter-trigger-search', function (e) {
			e.preventDefault();
			root.toggleClass('mk-org-search-open');
		});

		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.listViewFilter.click', function () {
				setTimeout(afterListLayout, 200);
			});
			app.event.on('Vtiger.Post.MenuToggle', function () {
				setTimeout(fixListScrollContainer, 80);
			});
		}

		var resizeTimer;
		$(window).on('resize.mkOrgAccountsList', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				if (isAccountsSalesList()) {
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
