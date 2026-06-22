/**
 * Accounts Organizations list (Sales): filter row wiring, native table scroll,
 * pagination footer placement, floatThead reflow after removing perfectScrollbar.
 * Scope: body[data-module="Accounts"][data-view="List"] in SALES, MARKETING, or SUPPORT
 */
(function ($) {
	'use strict';

	function isModernAccountsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Accounts' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SALES' || appName === 'MARKETING' || appName === 'SUPPORT') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		var app = params.get('app');
		return params.get('module') === 'Accounts' && params.get('view') === 'List' &&
			(app === 'SALES' || app === 'MARKETING' || app === 'SUPPORT');
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
		if (!isModernAccountsList()) {
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
		if (!isModernAccountsList()) {
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
		if (!isModernAccountsList()) {
			return;
		}
		$('#listViewContent #listview-table').addClass('mk-org-table');
	}

	function isMarketingAccountsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Accounts' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'MARKETING') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'Accounts' && params.get('view') === 'List' && params.get('app') === 'MARKETING';
	}

	function isSalesStyleAccountsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Accounts' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SALES' || appName === 'SUPPORT') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		var app = params.get('app');
		return params.get('module') === 'Accounts' && params.get('view') === 'List' &&
			(app === 'SALES' || app === 'SUPPORT');
	}

	function afterListLayout() {
		if (!isModernAccountsList()) {
			return;
		}
		markOrgTable();
		fixListScrollContainer();
		if (isSalesStyleAccountsList() && typeof window.mkSalesListAfterAjax === 'function') {
			window.mkSalesListAfterAjax();
		}
		if (isMarketingAccountsList() && typeof window.mkMarketingListAfterAjax === 'function') {
			window.mkMarketingListAfterAjax();
		}
		relocateOrgPagination();
	}

	function hasActiveConfirmModal() {
		return $('.bootbox.modal.in, .bootbox.modal.show, .myModal.in').length > 0;
	}

	function cleanupStuckOverlays() {
		if (hasActiveConfirmModal()) {
			return;
		}
		var $overlay = $('#overlayPageContent');
		if (!$overlay.length) {
			return;
		}
		var dataHtml = $.trim($overlay.find('.data').html() || '');
		var hasImport = $overlay.find('.data .mk-import-modern').length > 0;
		if ($overlay.hasClass('in') && !dataHtml) {
			$overlay.removeClass('in mk-import-overlay-open').attr('aria-hidden', 'true')
				.css({ display: '', visibility: '', opacity: '' });
			$('body').removeClass('modal-open mk-import-page mk-list-confirm-open');
			if (!$('.bootbox.modal, .myModal').length) {
				$('.modal-backdrop').remove();
			}
		} else if ($overlay.hasClass('in') && !hasImport && !dataHtml) {
			$overlay.removeClass('in mk-import-overlay-open');
			$('body').removeClass('mk-import-page');
		}
	}

	function ensureModalStacking() {
		var hasBootbox = $('.bootbox.modal.in, .bootbox.modal.show').length > 0;
		$('body').toggleClass('mk-list-confirm-open', hasBootbox);
		$('.bootbox.modal.in, .bootbox.modal.show').css('z-index', 110020);
		$('.modal-backdrop.in').each(function () {
			var $bd = $(this);
			if (!$bd.data('mk-import-backdrop') && hasBootbox) {
				$bd.css('z-index', 110000);
			}
		});
	}

	function init() {
		if (!isModernAccountsList()) {
			return;
		}
		var root = $('#listViewContent');
		if (!root.length) {
			return;
		}

		$(document).on('click.mkOrgList', '.mk-so-trigger-columns, .mk-org-trigger-columns', function (e) {
			e.preventDefault();
			var col = root.find('.listColumnFilter').first();
			if (col.length) {
				col.trigger('click');
			}
		});

		$(document).on('click.mkOrgList', '.mk-so-filter-trigger-search, .mk-org-filter-trigger-search', function (e) {
			e.preventDefault();
			if (isSalesStyleAccountsList()) {
				if (typeof window.mkSalesListAfterAjax === 'function') {
					window.mkSalesListAfterAjax();
				}
				var $row = root.find('tr.searchRow.listViewSearchContainer').first();
				if ($row.length && $row[0].scrollIntoView) {
					$row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
				return;
			}
			if (isMarketingAccountsList()) {
				if (typeof window.mkMarketingListAfterAjax === 'function') {
					window.mkMarketingListAfterAjax();
				}
				var $mktRow = root.find('tr.searchRow.listViewSearchContainer').first();
				if ($mktRow.length && $mktRow[0].scrollIntoView) {
					$mktRow[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
				return;
			}
			root.toggleClass('mk-so-search-open mk-org-search-open');
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
				if (isModernAccountsList()) {
					fixListScrollContainer();
				}
			}, 150);
		});

		setTimeout(afterListLayout, 200);
		cleanupStuckOverlays();
		setTimeout(cleanupStuckOverlays, 500);

		$(document).on('click.mkOrgModalFix', '[data-trigger="listDelete"], .listViewMassActions a, .listViewMassActions button', function () {
			setTimeout(ensureModalStacking, 0);
			setTimeout(ensureModalStacking, 50);
			setTimeout(ensureModalStacking, 300);
		});

		$(document).on('shown.bs.modal.mkOrgList hidden.bs.modal.mkOrgList', '.bootbox.modal', function () {
			ensureModalStacking();
		});
		$(document).on('hidden.bs.modal.mkOrgList', '.bootbox.modal', function () {
			if (!hasActiveConfirmModal()) {
				$('body').removeClass('mk-list-confirm-open');
			}
		});

		var modalObserver = window.MutationObserver ? new MutationObserver(function () {
			if ($('.bootbox.modal, .modal-backdrop.in').length) {
				ensureModalStacking();
			}
		}) : null;
		if (modalObserver) {
			modalObserver.observe(document.body, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
