/**
 * Invoice list (TOOLS / SUPPORT): patch floatThead + AJAX shell before registerEvents (Opportunities pattern).
 */
(function ($) {
	'use strict';

	var placeListContentsPatched = false;

	function listConfig() {
		return window.__mkInvSupportListConfig || {};
	}

	function isInvoiceMkList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Invoice' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SUPPORT' || appName === 'TOOLS') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		var app = params.get('app');
		return params.get('module') === 'Invoice' && params.get('view') === 'List' && (app === 'SUPPORT' || app === 'TOOLS');
	}

	function getListViewContainer() {
		return $('#listViewContent');
	}

	function getListPageRoot($lv) {
		return $lv.find('.mk-so-page.mk-opportunity-page').first();
	}

	function destroyFloatTheadArtifacts() {
		var $lv = getListViewContainer();
		if (!$lv.length) {
			return;
		}
		$lv.find('.floatThead-container').remove();
		if ($.fn.floatThead) {
			$lv.find('#listview-table').each(function () {
				try {
					$(this).floatThead('destroy');
				} catch (e) {
					/* ignore */
				}
			});
		}
		$lv.find('#table-content.table-container').css({
			position: '',
			height: 'auto',
			maxHeight: '',
			width: '100%',
			overflowX: 'auto',
			overflowY: 'visible'
		});
		if ($.fn.perfectScrollbar) {
			try {
				$lv.find('#table-content').perfectScrollbar('destroy');
			} catch (e2) {
				/* ignore */
			}
		}
		$lv.find('#scroller_wrapper.bottom-fixed-scroll').hide();
	}

	function applyListUi() {
		if (!isInvoiceMkList()) {
			return;
		}
		var $lv = getListViewContainer();
		$lv.addClass('mk-so-search-open mk-sales-list-table-ready mk-inv-list-ready');
		destroyFloatTheadArtifacts();
		if (window.MkSalesListShared && typeof window.MkSalesListShared.applyCommonUi === 'function') {
			window.MkSalesListShared.applyCommonUi();
		}
		if (window.MkSalesListShared && typeof window.MkSalesListShared.ensureSalesListTableUi === 'function') {
			window.MkSalesListShared.ensureSalesListTableUi();
		}
		if (window.MkSalesListShared && typeof window.MkSalesListShared.relocatePaginationFooter === 'function') {
			window.MkSalesListShared.relocatePaginationFooter();
		}
		if (window.MkSalesListShared && typeof window.MkSalesListShared.patchSalesListTableHooks === 'function') {
			window.MkSalesListShared.patchSalesListTableHooks();
		}
		if (typeof window.mkSalesListAfterAjax === 'function') {
			window.mkSalesListAfterAjax();
		}
	}

	function swapListBodyInShell(contents) {
		var $lv = getListViewContainer();
		var $page = getListPageRoot($lv);
		if (!$page.length) {
			return false;
		}
		var $incoming = $('<div>').html(contents);
		var $source = $incoming.find('.mk-so-page.mk-opportunity-page').first();
		if (!$source.length) {
			$source = $incoming.find('.mk-so-page').first();
		}
		if (!$source.length) {
			$source = $incoming.find('.col-sm-12').first();
		}
		if (!$source.length) {
			$source = $incoming;
		}
		var $card = $page.find('.mk-so-table-card').first();
		var $newTableContent = $source.find('#table-content').first();
		if (!$newTableContent.length || !$card.length) {
			return false;
		}
		$card.find('#table-content').nextAll('.mk-so-filter-row__footer').remove();
		$card.find('#table-content').replaceWith($newTableContent.clone(true, true));
		var $newActions = $source.find('#listview-actions').first();
		var $oldActions = $page.find('#listview-actions').first();
		if ($newActions.length && $oldActions.length) {
			$oldActions.replaceWith($newActions.clone(true, true));
		}
		if (window.MkSalesListShared && typeof window.MkSalesListShared.syncToolbarFromFragment === 'function') {
			window.MkSalesListShared.syncToolbarFromFragment($source, $lv);
		}
		return true;
	}

	function patchVtigerFloatingThead() {
		if (typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		if (Vtiger_List_Js.prototype.__mkInvSupportFloatPatched) {
			return;
		}
		Vtiger_List_Js.prototype.__mkInvSupportFloatPatched = true;

		var originalFloat = Vtiger_List_Js.prototype.registerFloatingThead;
		var originalReflow = Vtiger_List_Js.prototype.reflowList;

		Vtiger_List_Js.prototype.registerFloatingThead = function () {
			if (isInvoiceMkList()) {
				applyListUi();
				return;
			}
			originalFloat.call(this);
		};

		Vtiger_List_Js.prototype.reflowList = function () {
			if (isInvoiceMkList()) {
				applyListUi();
				return;
			}
			originalReflow.call(this);
		};
	}

	function patchPlaceListContents() {
		if (placeListContentsPatched || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		placeListContentsPatched = true;
		var originalPlace = Vtiger_List_Js.prototype.placeListContents;
		Vtiger_List_Js.prototype.placeListContents = function (contents) {
			if (isInvoiceMkList() && swapListBodyInShell(contents)) {
				applyListUi();
				return;
			}
			if (isInvoiceMkList()) {
				var $lv = getListViewContainer();
				var $incoming = $('<div>').html(contents);
				var $page = getListPageRoot($lv);
				var $card = $page.find('.mk-so-table-card').first();
				var $col = $card.find('> .col-sm-12').first();
				var $sourceCol = $incoming.find('.col-sm-12').first();
				if ($col.length && $sourceCol.length) {
					$col.html($sourceCol.html());
					applyListUi();
					return;
				}
				try {
					if (typeof app !== 'undefined' && app.helper && app.helper.hideProgress) {
						app.helper.hideProgress();
					}
				} catch (eHide) {
					/* ignore */
				}
				return;
			}
			originalPlace.call(this, contents);
			if (isInvoiceMkList()) {
				applyListUi();
			}
		};
	}

	function scheduleApply() {
		var delays = [0, 50, 150, 400];
		var i;
		for (i = 0; i < delays.length; i++) {
			setTimeout(applyListUi, delays[i]);
		}
		$(window).off('load.mkInvSupportList').on('load.mkInvSupportList', applyListUi);
	}

	function whenVtigerListReady(callback) {
		var attempts = 0;
		function tick() {
			if (typeof Vtiger_List_Js !== 'undefined') {
				callback();
				return;
			}
			attempts += 1;
			if (attempts < 120) {
				setTimeout(tick, 25);
			}
		}
		tick();
	}

	function boot() {
		if (!isInvoiceMkList()) {
			return;
		}
		whenVtigerListReady(function () {
			patchVtigerFloatingThead();
			patchPlaceListContents();
			scheduleApply();
		});
	}

	window.applyInvoiceMkListUi = applyListUi;
	window.applyInvoiceSupportListUi = applyListUi;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(jQuery);
