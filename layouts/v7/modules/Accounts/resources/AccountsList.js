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

	var MK_COL_CLASS_NAMES =
		'mk-col-control mk-col-org-name mk-col-company-code mk-col-website mk-col-phone mk-col-email mk-col-address mk-col-assigned mk-col-industry mk-col-type';

	var COL_CLASS_BY_FIELD = {
		accountname: 'mk-col-org-name',
		account_no: 'mk-col-company-code',
		cf_855: 'mk-col-company-code',
		website: 'mk-col-website',
		phone: 'mk-col-phone',
		otherphone: 'mk-col-phone',
		email1: 'mk-col-email',
		email2: 'mk-col-email',
		bill_street: 'mk-col-address',
		ship_street: 'mk-col-address',
		assigned_user_id: 'mk-col-assigned',
		industry: 'mk-col-industry',
		accounttype: 'mk-col-type'
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

	function fixEncodedNameCells(context) {
		$(context)
			.find('td[data-name="accountname"]')
			.each(function () {
				var $td = $(this);
				if ($td.attr('data-mk-decoded') === '1') {
					return;
				}
				var $link = $td.find('a').first();
				var $target = $link.length ? $link : $td.find('.value').first();
				if (!$target.length) {
					return;
				}
				var text = $.trim($target.text());
				if (!text || text.indexOf('&') < 0) {
					return;
				}
				var decoded = decodeHtmlEntities(text);
				if (decoded !== text) {
					$target.text(decoded);
					$td.attr('data-mk-decoded', '1');
				}
			});
	}

	function syncRowSelectedClass() {
		$('#listViewContent tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			$row.toggleClass('mk-org-row-selected', $row.find('.listViewEntriesCheckBox:checked').length > 0);
		});
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

	function refreshAccountsTableUi() {
		if (!isModernAccountsList()) {
			return;
		}
		document.documentElement.classList.add('mk-accounts-list-ready');
		document.documentElement.classList.add('mk-sales-list-ready');
		if (window.MkSalesListShared && typeof window.MkSalesListShared.revealSalesListUi === 'function') {
			window.MkSalesListShared.revealSalesListUi();
		}
		markOrgTable();
		assignColumnClasses();
		fixEncodedNameCells(document);
		syncRowSelectedClass();
		fixListScrollContainer();
		relocateOrgPagination();
	}

	function afterListLayout() {
		if (!isModernAccountsList()) {
			return;
		}
		if (isSalesStyleAccountsList() && typeof window.mkSalesListAfterAjax === 'function') {
			window.mkSalesListAfterAjax();
			return;
		}
		if (isMarketingAccountsList() && typeof window.mkMarketingListAfterAjax === 'function') {
			window.mkMarketingListAfterAjax();
			return;
		}
		refreshAccountsTableUi();
	}

	function installAccountsSalesAjaxHook() {
		if (window.__mkAccountsListAjaxHooked) {
			return;
		}
		window.__mkAccountsListAjaxHooked = true;
		var sharedAfterAjax = window.mkSalesListAfterAjax;
		window.mkSalesListAfterAjax = function (options) {
			if (typeof sharedAfterAjax === 'function') {
				sharedAfterAjax(options);
			}
			if (isModernAccountsList()) {
				refreshAccountsTableUi();
			}
		};
	}

	function installAccountsMarketingAjaxHook() {
		if (window.__mkAccountsMktAjaxHooked) {
			return;
		}
		window.__mkAccountsMktAjaxHooked = true;
		var marketingAfterAjax = window.mkMarketingListAfterAjax;
		window.mkMarketingListAfterAjax = function () {
			if (typeof marketingAfterAjax === 'function') {
				marketingAfterAjax();
			}
			if (isModernAccountsList()) {
				refreshAccountsTableUi();
			}
		};
	}

	window.mkAccountsListAfterAjax = refreshAccountsTableUi;

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

		installAccountsSalesAjaxHook();
		installAccountsMarketingAjaxHook();

		$(document).on('click.mkOrgList', '.mk-so-trigger-columns, .mk-org-trigger-columns', function (e) {
			e.preventDefault();
			var col = root.find('.listColumnFilter').first();
			if (col.length) {
				col.trigger('click');
			}
		});

		$(document).on('change.mkOrgList', '#listViewContent .listViewEntriesCheckBox, #listViewContent .listViewEntriesMainCheckBox', function () {
			syncRowSelectedClass();
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
