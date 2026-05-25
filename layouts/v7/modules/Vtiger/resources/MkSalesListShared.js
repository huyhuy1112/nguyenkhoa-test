/**
 * SALES app list + MANAGEMENT ProjectTask list — shared toolbar footer, floatThead off, AJAX shell.
 */
(function ($) {
	'use strict';

	var placeListContentsPatched = false;
	var floatTheadPatched = false;
	var postLoadPatched = false;
	var showPagingPatched = false;

	function isSalesAppList() {
		var b = document.body;
		if (!b || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SALES') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('view') === 'List' && params.get('app') === 'SALES';
	}

	function isManagementProjectTaskList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'ProjectTask' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		if ((b.getAttribute('data-app') || '').toUpperCase() === 'MANAGEMENT') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'ProjectTask' && params.get('view') === 'List' && params.get('app') === 'MANAGEMENT';
	}

	function isManagementProjectList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Project' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		if ((b.getAttribute('data-app') || '').toUpperCase() === 'MANAGEMENT') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'Project' && params.get('view') === 'List' && params.get('app') === 'MANAGEMENT';
	}

	function isManagementDocumentsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Documents' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		if ((b.getAttribute('data-app') || '').toUpperCase() === 'MANAGEMENT') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'Documents' && params.get('view') === 'List' && params.get('app') === 'MANAGEMENT';
	}

	function isMkEnhancedList() {
		return isSalesAppList() || isManagementProjectTaskList() || isManagementProjectList() || isManagementDocumentsList();
	}

	function notifyMkMgmtListUpdated() {
		if (isManagementProjectTaskList()) {
			$(document).trigger('mkProjectTaskListPostLoad');
		}
		if (isManagementProjectList()) {
			$(document).trigger('mkProjectListPostLoad');
		}
		if (isManagementDocumentsList()) {
			$(document).trigger('mkDocumentsListPostLoad');
		}
	}

	function getListViewContainer() {
		return $('#listViewContent');
	}

	function relocatePaginationFooter() {
		if (!isMkEnhancedList()) {
			return;
		}
		var $lv = getListViewContainer();
		if (!$lv.length) {
			return;
		}
		var $card = $lv.find('.mk-so-table-card').first();
		var $scope = $card.length ? $card : $lv;
		var $table = $scope.find('#table-content').first();
		var $footer = $scope.find('.mk-so-filter-row__footer').first();
		if (!$table.length || !$footer.length) {
			return;
		}
		if ($table[0].nextElementSibling === $footer[0]) {
			return;
		}
		$footer.detach();
		$table.after($footer);
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
		$lv.find('#table-content, .table-container').find('.ps__rail-x, .ps__rail-y, .ps__thumb-x, .ps__thumb-y').remove();
		$lv.find('.floatThead-wrapper, .floatThead-container').css({ width: '', overflow: '' });
	}

	function syncHiddenFieldsFromFragment($incoming, $scope) {
		var names = [
			'pageNumber', 'pageLimit', 'orderBy', 'sortOrder', 'list_headers', 'totalCount', 'noOfEntries',
			'pageStartRange', 'pageEndRange', 'previousPageExist', 'nextPageExist', 'viewname', 'cvid',
			'currentSearchParams', 'currentTagParams', 'noFilterCache'
		];
		var i;
		for (i = 0; i < names.length; i++) {
			var $src = $incoming.find('[name="' + names[i] + '"]').first();
			var $dst = $scope.find('[name="' + names[i] + '"]').first();
			if ($src.length && $dst.length) {
				$dst.val($src.val());
			}
		}
	}

	function getIncomingRoot($incoming) {
		var $page = $incoming.find('.mk-so-page.mk-so-list-sales-root').first();
		if ($page.length) {
			return $page;
		}
		$page = $incoming.find('.mk-so-page.mk-projecttask-list-mgmt-root').first();
		if ($page.length) {
			return $page;
		}
		$page = $incoming.find('.mk-so-page.mk-project-list-mgmt-root').first();
		if ($page.length) {
			return $page;
		}
		return $incoming.find('.col-sm-12').first().length ? $incoming.find('.col-sm-12').first() : $incoming;
	}

	function getListPageRoot($lv) {
		var $page = $lv.find('.mk-so-page.mk-so-list-sales-root').first();
		if ($page.length) {
			return $page;
		}
		$page = $lv.find('.mk-so-page.mk-projecttask-list-mgmt-root').first();
		if ($page.length) {
			return $page;
		}
		return $lv.find('.mk-so-page.mk-project-list-mgmt-root').first();
	}

	function swapListBodyInShell(contents) {
		var $lv = getListViewContainer();
		var $page = getListPageRoot($lv);
		if (!$page.length) {
			return false;
		}
		var $incoming = $('<div>').html(contents);
		var $source = getIncomingRoot($incoming);
		if (!$source.length) {
			return false;
		}
		syncHiddenFieldsFromFragment($source, $lv);
		var $card = $page.find('.mk-so-table-card').first();
		var $newTableContent = $source.find('#table-content').first();
		if (!$newTableContent.length || !$card.length) {
			return false;
		}
		$card.find('#table-content').replaceWith($newTableContent.clone(true, true));
		var $newActions = $source.find('#listview-actions').first();
		var $oldActions = $page.find('#listview-actions').first();
		if ($newActions.length && $oldActions.length) {
			$oldActions.replaceWith($newActions.clone(true, true));
		}
		relocatePaginationFooter();
		return true;
	}

	/**
	 * Auto-fetch total record count (replaces click on "of ?").
	 */
	function autoLoadTotalRecordCount() {
		if (!isSalesAppList() || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (!listInstance || typeof listInstance.totalNumOfRecords !== 'function') {
			return;
		}
		var $lv = getListViewContainer();
		if (!$lv.length) {
			return;
		}
		var entries = parseInt($lv.find('#noOfEntries').val(), 10);
		if (!entries || entries <= 0) {
			return;
		}
		var $totalEl = $lv.find('.totalNumberOfRecords').first();
		if (!$totalEl.length) {
			return;
		}
		if ($totalEl.hasClass('hide') && $totalEl.find('.mk-so-total-count').length) {
			return;
		}
		if (!$totalEl.find('.showTotalCountIcon').length && $totalEl.find('.mk-so-total-count').length) {
			return;
		}
		listInstance.totalNumOfRecords($totalEl);
	}

	function applyCommonUi() {
		if (!isMkEnhancedList()) {
			return;
		}
		destroyFloatTheadArtifacts();
		relocatePaginationFooter();
		autoLoadTotalRecordCount();
		notifyMkMgmtListUpdated();
	}

	/**
	 * Keep "Showing 1 to N" + "of TOTAL" + suffix (stock showPagingInfo merges into one span).
	 */
	function patchShowPagingInfo() {
		if (showPagingPatched || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		showPagingPatched = true;
		var originalShowPagingInfo = Vtiger_List_Js.prototype.showPagingInfo;
		Vtiger_List_Js.prototype.showPagingInfo = function () {
			var listViewContainer = this.getListViewContainer();
			if (!isMkEnhancedList() || !listViewContainer.find('.mk-so-page-numbers').length) {
				return originalShowPagingInfo.call(this);
			}
			var pageStartRange = jQuery('#pageStartRange', listViewContainer).val();
			var pageEndRange = jQuery('#pageEndRange', listViewContainer).val();
			var totalCount = jQuery('#totalCount', listViewContainer).val();
			var listViewEntriesCount = parseInt(jQuery('#noOfEntries', listViewContainer).val(), 10);
			var $totalSpan = listViewContainer.find('.mk-so-page-numbers .totalNumberOfRecords').first();

			if (listViewEntriesCount) {
				listViewContainer.find('.pageNumbersText').html(
					pageStartRange + ' ' + app.vtranslate('to') + ' ' + pageEndRange
				);
				if (totalCount && String(totalCount).trim() !== '' && String(totalCount) !== '0') {
					$totalSpan.removeClass('hide').html(
						'&nbsp;' + app.vtranslate('of') + ' <span class="mk-so-total-count">' +
						app.helper.purifyContent(totalCount) + '</span>'
					);
				} else {
					$totalSpan.removeClass('hide');
				}
			} else {
				listViewContainer.find('.pageNumbersText').html('<span>&nbsp;</span>');
				$totalSpan.addClass('hide');
			}
		};
	}

	function patchVtigerFloatingThead() {
		if (floatTheadPatched || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		floatTheadPatched = true;
		var originalFloat = Vtiger_List_Js.prototype.registerFloatingThead;
		var originalReflow = Vtiger_List_Js.prototype.reflowList;
		Vtiger_List_Js.prototype.registerFloatingThead = function () {
			if (isMkEnhancedList()) {
				applyCommonUi();
				return;
			}
			originalFloat.call(this);
		};
		Vtiger_List_Js.prototype.reflowList = function () {
			if (isMkEnhancedList()) {
				applyCommonUi();
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
			if (isMkEnhancedList() && swapListBodyInShell(contents)) {
				applyCommonUi();
				if (isSalesAppList() && typeof window.applySalesOrderListUi === 'function') {
					window.applySalesOrderListUi();
				}
				return;
			}
			originalPlace.call(this, contents);
			if (isMkEnhancedList()) {
				applyCommonUi();
				if (isSalesAppList() && typeof window.applySalesOrderListUi === 'function') {
					window.applySalesOrderListUi();
				}
			}
		};
	}

	function patchPostLoadListViewRecords() {
		if (postLoadPatched || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		postLoadPatched = true;
		var originalPostLoad = Vtiger_List_Js.prototype.postLoadListViewRecords;
		Vtiger_List_Js.prototype.postLoadListViewRecords = function (res) {
			originalPostLoad.call(this, res);
			if (isMkEnhancedList()) {
				setTimeout(applyCommonUi, 0);
			}
		};
	}

	function bindToolbarEvents() {
		var root = getListViewContainer();
		if (!root.length) {
			return;
		}
		$(document).off('click.mkSalesList', '.mk-so-trigger-columns').on('click.mkSalesList', '.mk-so-trigger-columns', function (e) {
			e.preventDefault();
			root.find('.listColumnFilter').first().trigger('click');
		});
		$(document).off('click.mkSalesList', '.mk-so-filter-trigger-search').on('click.mkSalesList', '.mk-so-filter-trigger-search', function (e) {
			e.preventDefault();
			if (isManagementProjectTaskList() || isManagementProjectList()) {
				return;
			}
			root.toggleClass('mk-so-search-open');
		});
	}

	function scheduleApply() {
		var delays = [0, 50, 150, 400, 800];
		var i;
		for (i = 0; i < delays.length; i++) {
			setTimeout(applyCommonUi, delays[i]);
		}
		$(window).off('load.mkSalesList').on('load.mkSalesList', applyCommonUi);
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

	function init() {
		if (!isMkEnhancedList()) {
			return;
		}
		whenVtigerListReady(function () {
			patchShowPagingInfo();
			patchVtigerFloatingThead();
			patchPlaceListContents();
			patchPostLoadListViewRecords();
			if (isSalesAppList()) {
				bindToolbarEvents();
			}
			scheduleApply();
			if (typeof app !== 'undefined' && app.event && app.event.on) {
				app.event.on('post.listViewFilter.click', applyCommonUi);
				app.event.on('post.listViewSort.click', applyCommonUi);
			}
		});
	}

	window.MkSalesListShared = {
		isSalesAppList: isSalesAppList,
		isManagementProjectTaskList: isManagementProjectTaskList,
		isManagementProjectList: isManagementProjectList,
		isManagementDocumentsList: isManagementDocumentsList,
		isMkEnhancedList: isMkEnhancedList,
		applyCommonUi: applyCommonUi,
		relocatePaginationFooter: relocatePaginationFooter,
		autoLoadTotalRecordCount: autoLoadTotalRecordCount
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
