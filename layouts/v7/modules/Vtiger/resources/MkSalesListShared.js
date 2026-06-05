/**
 * SALES app list + MANAGEMENT ProjectTask list — shared toolbar footer, floatThead off, AJAX shell.
 */
(function ($) {
	'use strict';

	var placeListContentsPatched = false;
	var floatTheadPatched = false;
	var postLoadPatched = false;
	var showPagingPatched = false;
	var layoutToggleBound = false;

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

	function isSupportAppList() {
		var b = document.body;
		if (!b || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SUPPORT') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('view') === 'List' && params.get('app') === 'SUPPORT';
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

	function isSalesOrderToolsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'SalesOrder' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'TOOLS') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'SalesOrder' && params.get('view') === 'List' && params.get('app') === 'TOOLS';
	}

	function isRecycleBinToolsList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'RecycleBin' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'TOOLS') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'RecycleBin' && params.get('view') === 'List' && params.get('app') === 'TOOLS';
	}

	function shouldRelocatePaginationFooter() {
		return isMkEnhancedList() || isSupportAppList() || isInvoiceMkList() || isSalesOrderToolsList() || isRecycleBinToolsList();
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

	function isPotentialsSalesList() {
		var b = document.body;
		return b && b.getAttribute('data-module') === 'Potentials' && isSalesAppList();
	}

	function isSalesStyleTableList() {
		return isSalesAppList() || isSupportAppList() || isInvoiceMkList() || isSalesOrderToolsList() || isRecycleBinToolsList();
	}

	function needsSalesListSearchHooks() {
		return isSalesAppList() || isSupportAppList() || isInvoiceMkList() || isSalesOrderToolsList() || isRecycleBinToolsList();
	}

	function shouldBootMkSalesListShared() {
		return isMkEnhancedList() || isSupportAppList() || isInvoiceMkList() || isSalesOrderToolsList() || isRecycleBinToolsList();
	}

	function isMkEnhancedList() {
		return isSalesAppList() || isManagementProjectTaskList() || isManagementProjectList() || isManagementDocumentsList();
	}

	function supportsLayoutToggle() {
		return isSalesAppList() || isSupportAppList() || isManagementProjectList() || isManagementProjectTaskList();
	}

	function getListModuleName() {
		var b = document.body;
		return (b && b.getAttribute('data-module')) || 'Vtiger';
	}

	function getLayoutStorageKey() {
		return 'mk_sales_list_layout_' + getListModuleName();
	}

	function getSavedLayoutMode() {
		try {
			var key = getLayoutStorageKey();
			var saved = window.localStorage.getItem(key);
			if (saved === 'grid' || saved === 'list') {
				return saved;
			}
			if (getListModuleName() === 'Quotes') {
				var legacy = window.localStorage.getItem('mk_quotes_sales_list_layout');
				if (legacy === 'grid' || legacy === 'list') {
					return legacy;
				}
			}
		} catch (e) {
			/* ignore */
		}
		return 'list';
	}

	function applyLayoutMode(mode) {
		if (!supportsLayoutToggle()) {
			return;
		}
		var isGrid = mode === 'grid';
		var $lv = getListViewContainer();
		$lv.toggleClass('mk-so-is-view-grid', isGrid);
		document.body.classList.toggle('mk-so-is-view-grid', isGrid);
		/* Quotes module CSS still keys off mk-qt-is-view-grid */
		$lv.toggleClass('mk-qt-is-view-grid', isGrid);
		document.body.classList.toggle('mk-qt-is-view-grid', isGrid);

		var $listBtn = $('.mk-so-toggle-layout--list');
		var $gridBtn = $('.mk-so-toggle-layout--grid');
		$listBtn.prop('disabled', false).toggleClass('is-active', !isGrid).attr('aria-pressed', !isGrid ? 'true' : 'false');
		$gridBtn.prop('disabled', false).toggleClass('is-active', isGrid).attr('aria-pressed', isGrid ? 'true' : 'false');

		try {
			window.localStorage.setItem(getLayoutStorageKey(), isGrid ? 'grid' : 'list');
		} catch (e2) {
			/* ignore */
		}
	}

	function bindViewLayoutToggle() {
		if (!supportsLayoutToggle() || layoutToggleBound) {
			return;
		}
		layoutToggleBound = true;
		$(document).on('click.mkSalesListLayout', '.mk-so-toggle-layout--list', function (e) {
			if (!supportsLayoutToggle()) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			applyLayoutMode('list');
			applyCommonUi();
		});
		$(document).on('click.mkSalesListLayout', '.mk-so-toggle-layout--grid', function (e) {
			if (!supportsLayoutToggle()) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			applyLayoutMode('grid');
			applyCommonUi();
		});
		applyLayoutMode(getSavedLayoutMode());
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

	/**
	 * AJAX refresh can leave multiple .mk-so-filter-row__footer nodes (one moved below table,
	 * another inside fresh #listview-actions). Collapse to a single footer element.
	 */
	function dedupePaginationFooters($scope) {
		var $footers = $scope.find('.mk-so-filter-row__footer');
		if (!$footers.length) {
			return $();
		}
		if ($footers.length === 1) {
			return $footers.first();
		}
		var $keep = $scope.find('#listview-actions .mk-so-filter-row__footer').last();
		if (!$keep.length) {
			$keep = $footers.last();
		}
		$keep = $keep.detach();
		$footers.remove();
		return $keep;
	}

	function relocatePaginationFooter() {
		if (!shouldRelocatePaginationFooter()) {
			return;
		}
		var $lv = getListViewContainer();
		if (!$lv.length) {
			return;
		}
		var $card = $lv.find('.mk-so-table-card, .mk-org-table-card, .mk-contact-table-card').first();
		var $scope = $card.length ? $card : $lv;
		var $table = $scope.find('#table-content').first();
		if (!$table.length) {
			return;
		}
		/* Orphan footers left below table from a previous relocate */
		$table.nextAll('.mk-so-filter-row__footer').remove();
		var $footer = dedupePaginationFooters($scope);
		if (!$footer.length) {
			$footer = $scope.find('#listview-actions .mk-so-filter-row__footer').first().detach();
		}
		if (!$footer.length) {
			return;
		}
		$scope.find('.mk-so-filter-row__footer').not($footer).remove();
		if ($table[0].nextElementSibling !== $footer[0]) {
			$table.after($footer);
		}
	}

	/** Update toolbar counts + pagination controls without replacing #listview-actions (Potentials). */
	function syncToolbarFromFragment($source, $lv) {
		var $page = getListPageRoot($lv);
		var $scope = $page.length ? $page : $lv;
		var $srcActions = $source.find('#listview-actions').first();
		var $dstActions = $scope.find('#listview-actions').first();
		if ($srcActions.length && $dstActions.length) {
			var $srcNums = $srcActions.find('.pageNumbersText').first();
			var $dstNums = $dstActions.find('.pageNumbersText').first();
			if ($srcNums.length && $dstNums.length) {
				$dstNums.html($srcNums.html());
			}
			var $srcTotal = $srcActions.find('.totalNumberOfRecords').first();
			var $dstTotal = $dstActions.find('.totalNumberOfRecords').first();
			if ($srcTotal.length && $dstTotal.length) {
				$dstTotal.attr('class', $srcTotal.attr('class'));
				$dstTotal.attr('title', $srcTotal.attr('title'));
				$dstTotal.html($srcTotal.html());
			}
		}
		var $srcFooter = $source.find('#listview-actions .mk-so-filter-row__footer').first();
		if (!$srcFooter.length) {
			$srcFooter = $source.find('.mk-so-filter-row__footer').first();
		}
		var $dstFooter = $scope.find('.mk-so-filter-row__footer').first();
		if ($srcFooter.length && $dstFooter.length) {
			$dstFooter.html($srcFooter.html());
		}
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (listInstance && listInstance.showPagingInfo) {
			listInstance.showPagingInfo();
		}
		if (listInstance && listInstance.registerPostLoadListViewActions) {
			listInstance.registerPostLoadListViewActions();
		}
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

	function getListDataScope($lv) {
		var $page = getListPageRoot($lv);
		if (!$page.length) {
			return $lv;
		}
		var $col = $page.find('.mk-so-table-card > .col-sm-12').first();
		return $col.length ? $col : $page;
	}

	function captureSearchRowValues($lv) {
		var values = {};
		$lv.find('tr.searchRow .listSearchContributor[name]').each(function () {
			var $el = $(this);
			if ($el.hasClass('select2_input_element') || $el.is('div')) {
				return;
			}
			var name = $el.attr('name');
			if (!name) {
				return;
			}
			var val = $el.val();
			if (val != null && String(val).trim() !== '') {
				values[name] = val;
			}
		});
		return values;
	}

	function restoreSearchRowValues($lv, values) {
		var name;
		if (!values) {
			return;
		}
		for (name in values) {
			if (!Object.prototype.hasOwnProperty.call(values, name)) {
				continue;
			}
			var $targets = $lv.find(
				'tr.searchRow select.listSearchContributor[name="' + name + '"], ' +
					'tr.searchRow input.listSearchContributor[name="' + name + '"]'
			).not('.select2_input_element');
			if (!$targets.length) {
				continue;
			}
			$targets.each(function () {
				var $el = $(this);
				$el.val(values[name]);
				if ($el.hasClass('select2') && $el.data('select2')) {
					try {
						$el.select2('val', values[name]);
					} catch (eSel) {
						/* ignore */
					}
				}
			});
		}
	}

	function syncHiddenFieldsFromFragment($incoming, $lv) {
		var $scope = isPotentialsSalesList() ? getListDataScope($lv) : $lv;
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
		var $page = $incoming.find('.mk-so-page.mk-opportunity-page').first();
		if ($page.length) {
			return $page;
		}
		$page = $incoming.find('.mk-so-page.mk-so-list-sales-root').first();
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
		var $page = $lv.find('.mk-so-page.mk-opportunity-page').first();
		if ($page.length) {
			return $page;
		}
		$page = $lv.find('.mk-so-page.mk-so-list-sales-root').first();
		if ($page.length) {
			return $page;
		}
		$page = $lv.find('.mk-so-page.mk-projecttask-list-mgmt-root').first();
		if ($page.length) {
			return $page;
		}
		return $lv.find('.mk-so-page.mk-project-list-mgmt-root').first();
	}

	/**
	 * Potentials (SALES): always replace the full table card from PJAX HTML.
	 * Partial #table-content swaps can leave stale tbody rows for text filters.
	 */
	function applyPotentialsListContents(contents) {
		if (!isPotentialsSalesList()) {
			return false;
		}
		var $lv = getListViewContainer();
		if (!$lv.length) {
			return false;
		}
		var $incoming = $('<div>').html(contents);
		var $source = getIncomingRoot($incoming);
		if (!$source.length) {
			return false;
		}
		var $page = getListPageRoot($lv);
		if (!$page.length) {
			return false;
		}
		var $card = $page.find('.mk-so-table-card, .mk-opportunity-table-card').first();
		var $newCard = $source.find('.mk-so-table-card, .mk-opportunity-table-card').first();
		syncHiddenFieldsFromFragment($source, $lv);
		if ($card.length && $newCard.length) {
			$card.html($newCard.html());
			syncToolbarFromFragment($source, $lv);
			relocatePaginationFooter();
			return true;
		}
		return potentialsSwapFallback($incoming, $lv);
	}

	function potentialsSwapFallback($incoming, $lv) {
		var $source = getIncomingRoot($incoming);
		if (!$source.length) {
			return false;
		}
		var $page = getListPageRoot($lv);
		if (!$page.length) {
			return false;
		}
		var $card = $page.find('.mk-so-table-card, .mk-opportunity-table-card').first();
		var $newCol = $source.find('.mk-so-table-card > .col-sm-12, .mk-opportunity-table-card > .col-sm-12').first();
		if (!$newCol.length) {
			$newCol = $source.find('.col-sm-12').first();
		}
		var $oldCol = $card.find('> .col-sm-12, > .col-xs-12').first();
		if ($newCol.length && $oldCol.length) {
			$card.find('#table-content').nextAll('.mk-so-filter-row__footer').remove();
			$oldCol.replaceWith($newCol.clone(true, true));
			syncHiddenFieldsFromFragment($source, $lv);
			syncToolbarFromFragment($source, $lv);
			relocatePaginationFooter();
			return true;
		}
		var $newTable = $incoming.find('#listview-table').first();
		var $oldTable = $lv.find('#listview-table').first();
		if ($newTable.length && $oldTable.length) {
			$oldTable.replaceWith($newTable.clone(true, true));
			syncHiddenFieldsFromFragment($source, $lv);
			syncToolbarFromFragment($source, $lv);
			relocatePaginationFooter();
			return true;
		}

		// Last-resort: replace the card content (keep sidebar/topbar shell).
		// This prevents the "3 dots then nothing changes" state when markup differs unexpectedly.
		var $newCard = $source.find('.mk-so-table-card, .mk-opportunity-table-card').first();
		if ($card.length && $newCard.length) {
			$card.html($newCard.html());
			syncHiddenFieldsFromFragment($source, $lv);
			syncToolbarFromFragment($source, $lv);
			relocatePaginationFooter();
			return true;
		}
		return false;
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
		var $card = $page.find('.mk-so-table-card, .mk-opportunity-table-card').first();
		var $newTableContent = $source.find('#table-content').first();
		if (!$newTableContent.length || !$card.length) {
			return false;
		}
		var potentialsOnly = isPotentialsSalesList();
		/* Drop orphan footers before table swap (stacked pagination bars). */
		$card.find('#table-content').nextAll('.mk-so-filter-row__footer').remove();
		$card.find('#table-content').replaceWith($newTableContent.clone(true, true));
		if (potentialsOnly) {
			syncToolbarFromFragment($source, $lv);
		} else {
			var $newActions = $source.find('#listview-actions').first();
			var $oldActions = $page.find('#listview-actions').first();
			if ($newActions.length && $oldActions.length) {
				$oldActions.replaceWith($newActions.clone(true, true));
			}
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
		if (!shouldRelocatePaginationFooter()) {
			return;
		}
		if (isSalesStyleTableList()) {
			ensureSalesListTableUi();
		}
		destroyFloatTheadArtifacts();
		var $lv = getListViewContainer();
		var $card = $lv.find('.mk-so-table-card').first();
		if ($card.length) {
			dedupePaginationFooters($card);
		}
		relocatePaginationFooter();
		autoLoadTotalRecordCount();
		if (supportsLayoutToggle()) {
			applyLayoutMode(getSavedLayoutMode());
		}
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
			if (!shouldRelocatePaginationFooter() || !listViewContainer.find('.mk-so-page-numbers').length) {
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
			if (isMkEnhancedList() || isSupportAppList()) {
				applyCommonUi();
				return;
			}
			originalFloat.call(this);
		};
		Vtiger_List_Js.prototype.reflowList = function () {
			if (isMkEnhancedList() || isSupportAppList()) {
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
			/* Potentials: never container.html() the whole shell (causes white page). */
			if (isPotentialsSalesList()) {
				if (applyPotentialsListContents(contents)) {
					applyCommonUi();
					if (typeof window.mkPotentialsListAfterAjax === 'function') {
						window.mkPotentialsListAfterAjax();
					}
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
			if (isMkEnhancedList() && swapListBodyInShell(contents)) {
				applyCommonUi();
				if (isSalesAppList()) {
					ensureSalesListTableUi();
				}
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
			if (shouldBootMkSalesListShared()) {
				setTimeout(function () {
					applyCommonUi();
					if (isPotentialsSalesList() && typeof window.mkPotentialsListAfterAjax === 'function') {
						window.mkPotentialsListAfterAjax();
					}
				}, 0);
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
			if (isPotentialsSalesList()) {
				return;
			}
			if (isSalesStyleTableList()) {
				ensureSalesListTableUi();
				var $row = root.find('tr.searchRow.listViewSearchContainer').first();
				if ($row.length && $row[0].scrollIntoView) {
					$row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
				return;
			}
			root.toggleClass('mk-so-search-open');
		});
	}

	/* ========== SALES list table standard (Opportunities reference) ========== */
	var salesTableHooksPatched = false;
	var salesTableEventsBound = false;
	var autoSearchTimer = null;

	function getSalesTableRoot() {
		return getListViewContainer();
	}

	function ensureSearchRowVisible() {
		if (!isSalesStyleTableList()) {
			return;
		}
		var $root = getSalesTableRoot();
		$root.addClass('mk-so-search-open mk-sales-list-table-ready');
		$root.addClass('mk-qt-search-open mk-sc-search-open mk-ps-search-open mk-contact-search-open mk-org-search-open');
	}

	function syncSearchFieldMeta() {
		if (typeof uimeta === 'undefined' || !uimeta.field || !uimeta.field.get) {
			return;
		}
		getSalesTableRoot().find('tr.searchRow .listSearchContributor[name]').each(function () {
			var $el = $(this);
			if ($el.data('fieldinfo')) {
				return;
			}
			var fn = $el.attr('name');
			if (!fn) {
				return;
			}
			var fi = uimeta.field.get(fn);
			if (fi) {
				$el.data('fieldinfo', fi);
			}
		});
	}

	function fixSearchRowSelect2() {
		var $root = getSalesTableRoot();
		$root.find('tr.searchRow .select2_input_element').each(function () {
			$(this).attr('tabindex', '-1').attr('aria-hidden', 'true');
		});
		$root.find('tr.searchRow .select2_search_div').css({ width: '100%', maxWidth: '100%', position: 'relative' });
		$root.find('tr.searchRow .select2-container').css({ width: '100%', maxWidth: '100%' });
	}

	function reinitSearchRow() {
		var $row = getSalesTableRoot().find('tr.searchRow').first();
		if ($row.length && window.vtUtils && vtUtils.applyFieldElementsView) {
			try {
				vtUtils.applyFieldElementsView($row);
			} catch (e) {
				/* ignore */
			}
		}
		syncSearchFieldMeta();
		fixSearchRowSelect2();
	}

	function getListSearchParamsSafe(listInstance, includeStarFilters) {
		if (typeof includeStarFilters === 'undefined') {
			includeStarFilters = true;
		}
		if (listInstance) {
			listInstance.filterClick = false;
		}
		var listViewPageDiv = getSalesTableRoot();
		var listViewTable = listViewPageDiv.find('tr.searchRow.listViewSearchContainer').first();
		if (!listViewTable.length) {
			listViewTable = listViewPageDiv.find('tr.searchRow').first();
		}
		var searchParams = [];
		var currentSearchParams = null;
		var rawCurrent = listViewPageDiv.find('#currentSearchParams').val();
		if (rawCurrent) {
			try {
				currentSearchParams = JSON.parse(rawCurrent);
			} catch (parseErr) {
				currentSearchParams = null;
			}
		}
		listViewTable.find('.listSearchContributor').each(function () {
			var searchContributorElement = $(this);
			if (searchContributorElement.hasClass('select2_input_element') || searchContributorElement.is('div')) {
				return;
			}
			var fieldName = searchContributorElement.attr('name');
			if (!fieldName) {
				return;
			}
			var fieldInfo = (typeof uimeta !== 'undefined' && uimeta.field && uimeta.field.get)
				? uimeta.field.get(fieldName)
				: undefined;
			if (typeof fieldInfo === 'undefined') {
				fieldInfo = searchContributorElement.data('fieldinfo');
			}
			if (!fieldInfo || typeof fieldInfo !== 'object') {
				fieldInfo = { type: 'string' };
			}
			if (currentSearchParams && currentSearchParams[fieldName]) {
				delete currentSearchParams[fieldName];
			}
			if (currentSearchParams && currentSearchParams.starred) {
				delete currentSearchParams.starred;
			}
			var searchValue = searchContributorElement.val();
			if (typeof searchValue === 'object') {
				searchValue = searchValue == null ? '' : searchValue.join(',');
			}
			searchValue = (searchValue || '').toString().trim();
			if (!searchValue.length) {
				if (currentSearchParams && currentSearchParams[fieldName]) {
					delete currentSearchParams[fieldName];
				}
				return;
			}
			var searchOperator = 'c';
			var fieldType = fieldInfo.type || 'string';
			if (fieldType === 'date' || fieldType === 'datetime') {
				searchOperator = 'bw';
			} else if (
				fieldType === 'percentage' || fieldType === 'double' || fieldType === 'integer' ||
				fieldType === 'currency' || fieldType === 'number' || fieldType === 'boolean' ||
				fieldType === 'picklist'
			) {
				searchOperator = 'e';
			}
			var storedOperator = searchContributorElement.closest('th').find('.operatorValue').val();
			if (storedOperator) {
				searchOperator = storedOperator;
			}
			searchParams.push([fieldName, searchOperator, searchValue]);
		});
		if (currentSearchParams) {
			var i;
			for (i in currentSearchParams) {
				if (!Object.prototype.hasOwnProperty.call(currentSearchParams, i)) {
					continue;
				}
				var row = currentSearchParams[i];
				if (!row || !row.fieldName) {
					continue;
				}
				searchParams.push([row.fieldName, row.comparator, row.searchValue]);
			}
		}
		var listSearchParams = searchParams.length > 0 ? [searchParams] : [];
		if (includeStarFilters && listInstance && listInstance.addStarSearchParams) {
			listSearchParams = listInstance.addStarSearchParams(listSearchParams);
		}
		return listSearchParams;
	}

	function runSalesListSearch() {
		ensureSearchRowVisible();
		syncSearchFieldMeta();
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (!listInstance || !listInstance.loadListViewRecords) {
			return;
		}
		listInstance.filterClick = false;
		listInstance.loadListViewRecords({
			page: '1',
			search_params: JSON.stringify(getListSearchParamsSafe(listInstance, false))
		});
	}

	function hasActiveSearchFilters() {
		var hasValue = false;
		getSalesTableRoot().find('tr.searchRow .listSearchContributor').each(function () {
			var $el = $(this);
			if ($el.hasClass('select2_input_element') || $el.is('div')) {
				return;
			}
			var searchValue = $el.val();
			if (typeof searchValue === 'object') {
				searchValue = searchValue == null ? '' : searchValue.join(',');
			}
			if ((searchValue || '').toString().trim().length) {
				hasValue = true;
			}
		});
		return hasValue;
	}

	function syncSearchButtonState() {
		if (!isSalesStyleTableList()) {
			return;
		}
		var $root = getSalesTableRoot();
		var $search = $root.find('tr.searchRow [data-trigger="listSearch"]');
		var $clear = $root.find('tr.searchRow [data-trigger="clearListSearch"]');
		if (!$search.length) {
			return;
		}
		if (hasActiveSearchFilters()) {
			$search.addClass('hide');
			$clear.removeClass('hide');
		} else {
			$search.removeClass('hide');
			$clear.addClass('hide');
		}
	}

	function scheduleAutoSearch() {
		if (!isSalesStyleTableList()) {
			return;
		}
		syncSearchButtonState();
		if (autoSearchTimer) {
			clearTimeout(autoSearchTimer);
		}
		autoSearchTimer = setTimeout(function () {
			autoSearchTimer = null;
			runSalesListSearch();
		}, 160);
	}

	function assignControlColumnClasses() {
		var $table = getSalesTableRoot().find('#listview-table');
		if (!$table.length) {
			return;
		}
		$table.find('thead tr.listViewContentHeader th').each(function () {
			if ($(this).find('.table-actions').length) {
				$(this).addClass('mk-col-control');
			}
		});
		$table.find('thead tr.searchRow th').each(function () {
			if ($(this).hasClass('inline-search-btn') || $(this).find('.table-actions').length) {
				$(this).addClass('mk-col-control');
			}
		});
		$table.find('tbody td.listViewRecordActions').addClass('mk-col-control');
	}

	function syncRowSelectedClass() {
		getSalesTableRoot().find('tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			$row.toggleClass(
				'mk-sales-row-selected mk-opp-row-selected',
				$row.find('.listViewEntriesCheckBox:checked').length > 0
			);
		});
	}

	function bindSalesListTableEvents() {
		// Potentials list has its own search logic in `modules/Potentials/resources/List.js`.
		// Prevent this shared file from binding click/real-time handlers there (would block search).
		if (isPotentialsSalesList()) {
			return;
		}
		if (!isSalesStyleTableList() || salesTableEventsBound) {
			return;
		}
		salesTableEventsBound = true;
		var root = getSalesTableRoot();
		root.off('keydown.mkSalesListSearch').on('keydown.mkSalesListSearch', 'tr.searchRow input.listSearchContributor', function (ev) {
			if (ev.key === 'Enter') {
				ev.preventDefault();
				runSalesListSearch();
			}
		});
		root
			.off('input.mkSalesAutoSearch')
			.on('input.mkSalesAutoSearch', 'tr.searchRow input.listSearchContributor:not(.select2_input_element)', function () {
				scheduleAutoSearch();
			});
		root
			.off('change.mkSalesAutoSearch select2-selecting.mkSalesAutoSearch select2-removed.mkSalesAutoSearch')
			.on('change.mkSalesAutoSearch', 'tr.searchRow select.listSearchContributor', function () {
				if ($(this).hasClass('select2_input_element')) {
					return;
				}
				scheduleAutoSearch();
			})
			.on('select2-selecting.mkSalesAutoSearch select2-removed.mkSalesAutoSearch', 'tr.searchRow .listSearchContributor.select2', function () {
				scheduleAutoSearch();
			})
			.on('datepicker-change.mkSalesAutoSearch', 'tr.searchRow .dateField', function () {
				scheduleAutoSearch();
			});
		root
			.off('click.mkSalesListSearchBtn', 'tr.searchRow [data-trigger="listSearch"]')
			.on('click.mkSalesListSearchBtn', 'tr.searchRow [data-trigger="listSearch"]', function (e) {
				e.preventDefault();
				e.stopImmediatePropagation();
				var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
				if (listInstance) {
					listInstance.filterClick = false;
				}
				syncSearchFieldMeta();
				runSalesListSearch();
			});
		root
			.off('click.mkSalesListClearBtn', 'tr.searchRow [data-trigger="clearListSearch"]')
			.on('click.mkSalesListClearBtn', 'tr.searchRow [data-trigger="clearListSearch"]', function (e) {
				e.preventDefault();
				e.stopImmediatePropagation();
				root.find('tr.searchRow .listSearchContributor').each(function () {
					var $el = $(this);
					if ($el.hasClass('select2_input_element') || $el.is('div')) {
						return;
					}
					if ($el.is('input')) {
						$el.val('');
					} else if ($el.is('select')) {
						if ($el.hasClass('select2') && $el.select2) {
							$el.select2('val', '');
						}
						$el.val('');
					}
				});
				root.find('#currentSearchParams').val('');
				syncSearchButtonState();
				runSalesListSearch();
			});
		root.off('change.mkSalesRowCheck', '.listViewEntriesCheckBox').on('change.mkSalesRowCheck', '.listViewEntriesCheckBox', syncRowSelectedClass);
		root.off('change.mkSalesMainCheck', '.listViewEntriesMainCheckBox').on('change.mkSalesMainCheck', '.listViewEntriesMainCheckBox', syncRowSelectedClass);
	}

	function patchSalesListTableHooks() {
		// Potentials list has its own search logic in `modules/Potentials/resources/List.js`.
		// Prevent this shared file from overriding getListSearchParams/loadListViewRecords there.
		if (isPotentialsSalesList()) {
			return;
		}
		if (!needsSalesListSearchHooks() || salesTableHooksPatched || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		if (Vtiger_List_Js.prototype.__mkSalesListTableHooks) {
			salesTableHooksPatched = true;
			return;
		}
		var proto = Vtiger_List_Js.prototype;
		if (!proto.__mkOppListHooks) {
			var origGetSearch = proto.getListSearchParams;
			proto.getListSearchParams = function (includeStarFilters) {
				if (!needsSalesListSearchHooks()) {
					return origGetSearch.apply(this, arguments);
				}
				return getListSearchParamsSafe(this, includeStarFilters);
			};
			var origLoad = proto.loadListViewRecords;
			proto.loadListViewRecords = function (urlParams) {
				if (needsSalesListSearchHooks()) {
					this.filterClick = false;
					if (typeof urlParams === 'undefined') {
						urlParams = {};
					}
					if (typeof urlParams.search_params === 'undefined') {
						urlParams.search_params = JSON.stringify(getListSearchParamsSafe(this, false));
					}
				}
				return origLoad.apply(this, arguments);
			};
		}
		Vtiger_List_Js.prototype.__mkSalesListTableHooks = true;
		salesTableHooksPatched = true;
	}

	function ensureSalesListTableUi() {
		if (!isSalesStyleTableList()) {
			return;
		}
		ensureSearchRowVisible();
		reinitSearchRow();
		assignControlColumnClasses();
		syncRowSelectedClass();
		syncSearchButtonState();
		bindSalesListTableEvents();
	}

	window.mkSalesListAfterAjax = function () {
		if (!isSalesStyleTableList()) {
			return;
		}
		ensureSalesListTableUi();
	};

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
		if (!shouldBootMkSalesListShared()) {
			return;
		}
		whenVtigerListReady(function () {
			if (isMkEnhancedList()) {
				patchShowPagingInfo();
				patchPlaceListContents();
				patchPostLoadListViewRecords();
			}
			patchVtigerFloatingThead();
			if (isSalesStyleTableList()) {
				bindToolbarEvents();
				if (needsSalesListSearchHooks()) {
					patchSalesListTableHooks();
				}
				bindSalesListTableEvents();
				ensureSalesListTableUi();
			}
			bindViewLayoutToggle();
			scheduleApply();
			if (typeof app !== 'undefined' && app.event && app.event.on) {
				app.event.on('post.listViewFilter.click', applyCommonUi);
				app.event.on('post.listViewSort.click', applyCommonUi);
			}
		});
	}

	window.MkSalesListShared = {
		isSalesAppList: isSalesAppList,
		isSupportAppList: isSupportAppList,
		isInvoiceMkList: isInvoiceMkList,
		isSalesOrderToolsList: isSalesOrderToolsList,
		isRecycleBinToolsList: isRecycleBinToolsList,
		isSalesStyleTableList: isSalesStyleTableList,
		isPotentialsSalesList: isPotentialsSalesList,
		isManagementProjectTaskList: isManagementProjectTaskList,
		isManagementProjectList: isManagementProjectList,
		isManagementDocumentsList: isManagementDocumentsList,
		isMkEnhancedList: isMkEnhancedList,
		supportsLayoutToggle: supportsLayoutToggle,
		applyCommonUi: applyCommonUi,
		applyLayoutMode: applyLayoutMode,
		getSavedLayoutMode: getSavedLayoutMode,
		bindViewLayoutToggle: bindViewLayoutToggle,
		relocatePaginationFooter: relocatePaginationFooter,
		syncToolbarFromFragment: syncToolbarFromFragment,
		dedupePaginationFooters: dedupePaginationFooters,
		autoLoadTotalRecordCount: autoLoadTotalRecordCount,
		ensureSalesListTableUi: ensureSalesListTableUi,
		runSalesListSearch: runSalesListSearch,
		syncSearchButtonState: syncSearchButtonState,
		scheduleAutoSearch: scheduleAutoSearch,
		patchSalesListTableHooks: patchSalesListTableHooks,
		bindSalesListTableEvents: bindSalesListTableEvents,
		applyPotentialsListContents: applyPotentialsListContents
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
