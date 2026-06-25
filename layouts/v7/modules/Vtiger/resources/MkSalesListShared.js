/**
 * SALES app list + MANAGEMENT ProjectTask list — shared toolbar footer, floatThead off, AJAX shell.
 */
(function ($) {
	'use strict';

	if (document.documentElement) {
		document.documentElement.classList.add('mk-sales-list-guard');
	}

	function revealSalesListUi() {
		var b = document.body;
		if (!b || b.getAttribute('data-view') !== 'List') {
			return;
		}
		var app = (b.getAttribute('data-app') || '').toUpperCase();
		if (app !== 'SALES' && app !== 'SUPPORT') {
			var params = new URLSearchParams(window.location.search || '');
			app = (params.get('app') || '').toUpperCase();
			if (app !== 'SALES' && app !== 'SUPPORT') {
				return;
			}
		}
		if (!document.getElementById('listViewContent')) {
			return;
		}
		document.documentElement.classList.add('mk-sales-list-ready');
	}

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

	function isSalesShellList() {
		if (!isSalesAppList()) {
			return false;
		}
		return getListPageRoot(getListViewContainer()).length > 0;
	}

	function isManagementShellList() {
		if (!isManagementProjectTaskList() && !isManagementProjectList() && !isManagementDocumentsList()) {
			return false;
		}
		return getListPageRoot(getListViewContainer()).length > 0;
	}

	function isMkShellList() {
		return isSalesShellList() || isManagementShellList();
	}

	var MK_TABLE_CARD_SEL =
		'.mk-so-table-card, .mk-opportunity-table-card, .mk-qt-table-card, .mk-contact-table-card, ' +
		'.mk-org-table-card, .mk-sc-table-card, .mk-ps-table-card, .mk-projecttask-table-card, .mk-project-table-card';

	function isSalesStyleTableList() {
		return (
			isSalesAppList() ||
			isSupportAppList() ||
			isInvoiceMkList() ||
			isSalesOrderToolsList() ||
			isRecycleBinToolsList() ||
			isManagementProjectTaskList() ||
			isManagementProjectList() ||
			isManagementDocumentsList()
		);
	}

	function needsSalesListSearchHooks() {
		return isSalesStyleTableList();
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
		notifyMkMgmtListUpdated();
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
		var $card = $lv.find(MK_TABLE_CARD_SEL).first();
		var $scope = $card.length ? $card : $lv;
		var $table = $scope.find('#table-content').first();
		if (!$table.length) {
			return;
		}
		var $inner = $table.parent();
		var $footer = $scope.find('#listview-actions .mk-so-filter-row__footer').first();
		if (!$footer.length) {
			$footer = $scope.find('#table-content + .mk-so-filter-row__footer').first();
		}
		if (!$footer.length) {
			$footer = dedupePaginationFooters($scope);
			if ($footer.length && $footer.parent().length) {
				$footer = $footer.detach();
			}
		} else {
			$footer = $footer.detach();
		}
		if (!$footer.length) {
			return;
		}
		$scope.find('.mk-so-filter-row__footer').remove();
		/* Append last inside card column — after #scroller_wrapper (top:-19px in custom.css overlaps footer if placed before it) */
		if ($inner.length && ($inner.is('.col-sm-12') || $inner.is('.col-xs-12'))) {
			$inner.append($footer);
			return;
		}
		$table.after($footer);
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
		values = mapSearchRowValuesToColumns(values);
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

	function captureAllSearchRowValues($lv) {
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
			if (typeof val === 'object') {
				val = val == null ? '' : val.join(',');
			}
			values[name] = val == null ? '' : String(val);
		});
		return values;
	}

	function isSearchRowFocused() {
		var el = document.activeElement;
		if (!el || el === document.body) {
			return false;
		}
		return $(el).closest('#listViewContent tr.searchRow').length > 0;
	}

	function shouldPreserveSearchRow(options) {
		if (options && options.preserveSearchRow) {
			return true;
		}
		return isSearchRowFocused();
	}

	function captureSearchFocusState($lv) {
		var state = {
			values: captureAllSearchRowValues($lv),
			focusName: null,
			selectionStart: null,
			selectionEnd: null
		};
		var el = document.activeElement;
		if (!el || !$(el).closest('#listViewContent tr.searchRow').length) {
			return state;
		}
		var name = el.getAttribute && el.getAttribute('name');
		if (name) {
			state.focusName = name;
			if (typeof el.selectionStart === 'number') {
				state.selectionStart = el.selectionStart;
				state.selectionEnd = el.selectionEnd;
			}
		}
		return state;
	}

	function restoreSearchFocusState($lv, state) {
		if (!state || !state.values) {
			return;
		}
		restoreSearchRowValues($lv, state.values);
		if (!state.focusName) {
			return;
		}
		var $input = $lv.find(
			'tr.searchRow input.listSearchContributor[name="' + state.focusName + '"]'
		).not('.select2_input_element').first();
		if (!$input.length) {
			return;
		}
		var inputEl = $input[0];
		try {
			inputEl.focus();
			if (typeof state.selectionStart === 'number' && inputEl.setSelectionRange) {
				inputEl.setSelectionRange(state.selectionStart, state.selectionEnd);
			}
		} catch (focusErr) {
			/* ignore */
		}
	}

	function applySalesShellListBodyOnly($lv, $source, $page) {
		var $card = $page.find(MK_TABLE_CARD_SEL).first();
		var $oldTable = $lv.find('#listview-table').first();
		var $newTable = $source.find('#listview-table').first();
		if (!$oldTable.length || !$newTable.length) {
			return false;
		}
		var $newBody = $newTable.find('> tbody').first();
		var $oldBody = $oldTable.find('> tbody').first();
		if ($newBody.length && $oldBody.length) {
			$oldBody.replaceWith($newBody.clone(true, true));
		}
		var $newFoot = $newTable.find('> tfoot').first();
		var $oldFoot = $oldTable.find('> tfoot').first();
		if ($newFoot.length && $oldFoot.length) {
			$oldFoot.replaceWith($newFoot.clone(true, true));
		} else if ($newFoot.length && !$oldFoot.length) {
			$oldTable.append($newFoot.clone(true, true));
		} else if (!$newFoot.length && $oldFoot.length) {
			$oldFoot.remove();
		}
		syncHiddenFieldsFromFragment($source, $lv);
		syncToolbarFromFragment($source, $lv);
		if ($card.length) {
			$card.find('#table-content').nextAll('.mk-so-filter-row__footer').remove();
			var $newFooter = $source.find('.mk-so-filter-row__footer').first();
			var $oldFooter = $card.find('.mk-so-filter-row__footer').first();
			if ($newFooter.length && $oldFooter.length) {
				$oldFooter.replaceWith($newFooter.clone(true, true));
			} else if ($newFooter.length && !$oldFooter.length) {
				$card.append($newFooter.clone(true, true));
			}
		}
		relocatePaginationFooter();
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (listInstance && listInstance.showPagingInfo) {
			listInstance.showPagingInfo();
		}
		return true;
	}

	function finalizeSalesListSearchUi(uiOpts, focusState) {
		applyCommonUi(uiOpts || {});
		if (focusState) {
			restoreSearchFocusState(getListViewContainer(), focusState);
		}
		if (isSalesAppList() && typeof window.applySalesOrderListUi === 'function') {
			window.applySalesOrderListUi();
		}
	}

	function syncHiddenFieldsFromFragment($incoming, $lv) {
		var $scope = isMkShellList() || isPotentialsSalesList() ? getListDataScope($lv) : $lv;
		if (!$scope.length) {
			$scope = $lv;
		}
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
	 * SALES shell lists: always replace the full table card from PJAX HTML.
	 * Partial #table-content swaps can leave stale tbody rows for text filters.
	 */
	function applySalesShellListContents(contents, options) {
		options = options || {};
		if (!isMkShellList()) {
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
		if (shouldPreserveSearchRow(options) && applySalesShellListBodyOnly($lv, $source, $page)) {
			return true;
		}
		var $card = $page.find(MK_TABLE_CARD_SEL).first();
		var $newCard = $source.find(MK_TABLE_CARD_SEL).first();
		syncHiddenFieldsFromFragment($source, $lv);
		if ($card.length && $newCard.length) {
			$card.html($newCard.html());
			syncToolbarFromFragment($source, $lv);
			relocatePaginationFooter();
			return true;
		}
		return salesShellSwapFallback($incoming, $lv);
	}

	function applyPotentialsListContents(contents) {
		return applySalesShellListContents(contents);
	}

	function salesShellSwapFallback($incoming, $lv) {
		var $source = getIncomingRoot($incoming);
		if (!$source.length) {
			return false;
		}
		var $page = getListPageRoot($lv);
		if (!$page.length) {
			return false;
		}
		var $card = $page.find(MK_TABLE_CARD_SEL).first();
		var $newCol = $source.find(
			'.mk-so-table-card > .col-sm-12, .mk-opportunity-table-card > .col-sm-12, .mk-qt-table-card > .col-sm-12, ' +
				'.mk-contact-table-card > .col-sm-12, .mk-org-table-card > .col-sm-12, .mk-sc-table-card > .col-sm-12, ' +
				'.mk-ps-table-card > .col-sm-12, .mk-projecttask-table-card > .col-sm-12, .mk-project-table-card > .col-sm-12'
		).first();
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
		var $newCard = $source.find(MK_TABLE_CARD_SEL).first();
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
		var $card = $page.find(MK_TABLE_CARD_SEL).first();
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
		if (!shouldRelocatePaginationFooter() || typeof Vtiger_List_Js === 'undefined') {
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

	function applyCommonUi(options) {
		options = options || {};
		if (!shouldRelocatePaginationFooter()) {
			return;
		}
		if (isSalesStyleTableList()) {
			ensureSalesListTableUi(options);
		}
		destroyFloatTheadArtifacts();
		var $lv = getListViewContainer();
		var $card = $lv.find(MK_TABLE_CARD_SEL).first();
		if ($card.length) {
			dedupePaginationFooters($card);
		}
		relocatePaginationFooter();
		autoLoadTotalRecordCount();
		if (supportsLayoutToggle()) {
			applyLayoutMode(getSavedLayoutMode());
		}
		notifyMkMgmtListUpdated();
		revealSalesListUi();
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
			/* SALES shell: full table-card replace (never partial #table-content swap). */
			if (isMkShellList()) {
				var searchOpts = shouldPreserveSearchRow({}) ? { preserveSearchRow: true } : {};
				var focusState = searchOpts.preserveSearchRow
					? captureSearchFocusState(getListViewContainer())
					: null;
				if (applySalesShellListContents(contents, searchOpts)) {
					finalizeSalesListSearchUi(
						searchOpts.preserveSearchRow ? { skipSearchReinit: true } : {},
						focusState
					);
					hideProgressSafe();
					if (
						isPotentialsSalesList() &&
						!searchOpts.preserveSearchRow &&
						typeof window.mkPotentialsListAfterAjax === 'function'
					) {
						window.mkPotentialsListAfterAjax();
					}
					return;
				}
				/* Mass delete / paging: shell swap can fail on unexpected PJAX markup — never leave blank list. */
				originalPlace.call(this, contents);
				finalizeSalesListSearchUi(
					searchOpts.preserveSearchRow ? { skipSearchReinit: true } : {},
					focusState
				);
				hideProgressSafe();
				if (
					isPotentialsSalesList() &&
					typeof window.mkPotentialsListAfterAjax === 'function'
				) {
					window.mkPotentialsListAfterAjax();
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

	function bindPageJumpDropdownFix() {
		/* Keep menu in DOM (no body portal) — stop Bootstrap document-click from closing while interacting */
		$(document)
			.off('click.mkSalesPageJump mousedown.mkSalesPageJump', '#PageJumpDropDown, #PageJumpDropDown *')
			.on('click.mkSalesPageJump mousedown.mkSalesPageJump', '#PageJumpDropDown, #PageJumpDropDown *', function (e) {
				e.stopPropagation();
			});
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
	var salesShellPostLoadPatched = false;
	var autoSearchTimer = null;
	var inflightSalesSearchId = 0;
	var pendingSalesSearchRowState = null;
	var globalQuickSearchBound = false;
	var globalQuickSearchTimer = null;

	function normalizeSearchFieldNameForColumn(fieldName) {
		if (!fieldName) {
			return fieldName;
		}
		var matchName = String(fieldName);
		if (matchName.length > 2 && matchName.charAt(0) === '(' && matchName.charAt(matchName.length - 1) === ')') {
			matchName = matchName.slice(1, -1);
		}
		var match = matchName.match(/^(\w+) ; \((\w+)\) (\w+)$/);
		if (match) {
			return match[1];
		}
		return fieldName;
	}

	function mapSearchRowValuesToColumns(values) {
		var mapped = {};
		var name;
		if (!values) {
			return mapped;
		}
		for (name in values) {
			if (!Object.prototype.hasOwnProperty.call(values, name)) {
				continue;
			}
			var columnName = normalizeSearchFieldNameForColumn(name);
			if (values[name] != null && String(values[name]).length) {
				mapped[columnName] = values[name];
			}
		}
		return mapped;
	}

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

	/** Reference list columns: search related module name field (not crmentity.label). */
	var REFERENCE_NAME_FIELD_BY_MODULE = {
		Potentials: 'potentialname',
		Accounts: 'accountname',
		Contacts: 'lastname',
		Leads: 'lastname',
		Quotes: 'subject',
		SalesOrder: 'subject',
		Invoice: 'subject',
		Products: 'productname',
		Services: 'servicename',
		Vendors: 'vendorname',
		Project: 'projectname',
		HelpDesk: 'ticket_title',
		ProjectTask: 'projecttaskname'
	};

	/** SALES list columns — uimeta often omits type/referencemodules for reference fields. */
	var REFERENCE_FIELD_FALLBACK = {
		account_id: { module: 'Accounts', nameField: 'accountname' },
		related_to: { module: 'Accounts', nameField: 'accountname' },
		projectid: { module: 'Project', nameField: 'projectname' },
		parent_id: { module: 'Accounts', nameField: 'accountname' },
		contact_id: { module: 'Contacts', nameField: 'lastname' },
		potential_id: { module: 'Potentials', nameField: 'potentialname' },
		quote_id: { module: 'Quotes', nameField: 'subject' },
		salesorder_id: { module: 'SalesOrder', nameField: 'subject' },
		vendor_id: { module: 'Vendors', nameField: 'vendorname' }
	};

	function resolveReferenceSearchFieldName(fieldName, fieldInfo) {
		if (!fieldName) {
			return fieldName;
		}
		if (fieldName.indexOf(' ; (') !== -1) {
			return fieldName;
		}
		var fallback = REFERENCE_FIELD_FALLBACK[fieldName];
		if (fallback) {
			return fieldName + ' ; (' + fallback.module + ') ' + fallback.nameField;
		}
		if (!fieldInfo || fieldInfo.type !== 'reference') {
			return fieldName;
		}
		var modules = fieldInfo.referencemodules || fieldInfo.reference_module || fieldInfo.referenceModule;
		if (!modules) {
			return fieldName;
		}
		if (!Array.isArray(modules)) {
			modules = String(modules).split(',');
		}
		var i;
		for (i = 0; i < modules.length; i++) {
			var mod = String(modules[i]).trim();
			var nameField = REFERENCE_NAME_FIELD_BY_MODULE[mod];
			if (nameField) {
				return fieldName + ' ; (' + mod + ') ' + nameField;
			}
		}
		return fieldName;
	}

	function hasDatePickerPlugin() {
		return !!(window.jQuery && $.fn && typeof $.fn.datepicker === 'function');
	}

	function destroyMkMgmtDatePicker($input) {
		if (!$input || !$input.length) {
			return;
		}
		var drp = $input.data('dateRangePicker');
		if (drp && typeof drp.destroy === 'function') {
			try {
				drp.destroy();
			} catch (destroyErr) {
				/* ignore */
			}
		}
		$input.removeData('dateRangePicker');
		if ($input.data('datepicker')) {
			try {
				$input.datepicker('remove');
			} catch (removeErr) {
				/* ignore */
			}
		}
		$input.removeData('mkMgmtDatePickerReady');
	}

	function getMkDatePickerLang() {
		var lang = $('body').data('language');
		if (lang && String(lang).length >= 2) {
			return String(lang).substring(0, 2);
		}
		return 'en';
	}

	function calcMkDatePickerPosition($input) {
		var $anchor = $input.closest('.mk-date-search-group');
		if (!$anchor.length) {
			$anchor = $input;
		}
		var rect = $anchor[0].getBoundingClientRect();
		var pickerW = 280;
		var pickerH = 280;
		var gap = 6;
		var top = rect.bottom + gap;
		if (top + pickerH > window.innerHeight - 8) {
			top = Math.max(8, rect.top - pickerH - gap);
		}
		var left = rect.left;
		if (left + pickerW > window.innerWidth - 8) {
			left = Math.max(8, window.innerWidth - pickerW - 8);
		}
		return { top: top, left: left };
	}

	function pinMkDatePickerVisible($input) {
		var dp = $input && $input.data('datepicker');
		var $picker = dp && dp.picker ? dp.picker : $();
		if (!$picker.length) {
			$picker = $('body > .datepicker.datepicker-dropdown:visible, body > .datepicker.dropdown-menu:visible').last();
		}
		if (!$picker.length || !$input || !$input.length) {
			return;
		}
		var pos = calcMkDatePickerPosition($input);
		$picker.css({
			position: 'fixed',
			top: pos.top + 'px',
			left: pos.left + 'px',
			right: 'auto',
			bottom: 'auto',
			zIndex: 200000,
			display: 'block',
			visibility: 'visible',
			opacity: 1,
			pointerEvents: 'auto',
			transition: 'none',
			animation: 'none'
		});
		$('body').addClass('mk-mgmt-date-picker-open');
	}

	function showMkMgmtDatePicker($input) {
		if (!$input || !$input.length || !hasDatePickerPlugin()) {
			return;
		}
		if (!$input.data('mkMgmtDatePickerReady')) {
			initMkMgmtSingleDatePicker($input);
		}
		var dp = $input.data('datepicker');
		if (dp && dp.picker && dp.picker.is(':visible')) {
			pinMkDatePickerVisible($input);
			return;
		}
		try {
			$input.datepicker('show');
			pinMkDatePickerVisible($input);
		} catch (showErr) {
			/* ignore */
		}
	}

	function bindMgmtDatePickerDelegation() {
		if (bindMgmtDatePickerDelegation._bound) {
			return;
		}
		bindMgmtDatePickerDelegation._bound = true;
		$(document)
			.off('click.mkMgmtDateSearch', '#listview-table .mk-date-search-input, #listview-table .mk-date-search-trigger')
			.on('click.mkMgmtDateSearch', '#listview-table .mk-date-search-input, #listview-table .mk-date-search-trigger', function (e) {
				if (!isManagementProjectList() && !isManagementProjectTaskList()) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				var $input = $(this).is('input.dateField')
					? $(this)
					: $(this).closest('.mk-date-search-group').find('input.dateField').first();
				showMkMgmtDatePicker($input);
			});
	}

	function initMkMgmtSingleDatePicker($input) {
		if (!$input || !$input.length || !hasDatePickerPlugin()) {
			return;
		}
		if ($input.data('mkMgmtDatePickerReady') && $input.data('datepicker')) {
			return;
		}
		destroyMkMgmtDatePicker($input);
		$input.removeAttr('data-calendar-type');
		$input.addClass('ignore-ui-registration mk-date-search-input mk-date-single-picker');
		$input.attr('readonly', 'readonly');
		$input.attr('autocomplete', 'off');

		var fmt =
			$input.data('dateFormat') ||
			(window.app && app.getDateFormat ? app.getDateFormat() : 'dd-mm-yyyy');
		var lang = getMkDatePickerLang();
		var pickerOpts = {
			autoclose: true,
			todayBtn: 'linked',
			todayHighlight: true,
			clearBtn: true,
			format: fmt,
			orientation: 'bottom auto',
			container: 'body',
			enableOnReadonly: true
		};
		if (lang) {
			pickerOpts.language = lang;
		}
		try {
			$input.datepicker(pickerOpts);
		} catch (initErr) {
			try {
				delete pickerOpts.language;
				$input.datepicker(pickerOpts);
			} catch (fallbackErr) {
				return;
			}
		}

		var dp = $input.data('datepicker');
		if (dp && typeof dp.place === 'function' && !dp.__mkMgmtPlacePatched) {
			dp.place = function () {
				return this;
			};
			dp.__mkMgmtPlacePatched = true;
		}

		$input
			.off('.mkMgmtDateSearch')
			.on('keydown.mkMgmtDateSearch paste.mkMgmtDateSearch', function (e) {
				e.preventDefault();
			})
			.on('show.mkMgmtDateSearch', function () {
				pinMkDatePickerVisible($input);
			})
			.on('hide.mkMgmtDateSearch', function () {
				$('body').removeClass('mk-mgmt-date-picker-open');
			})
			.on('changeDate.mkMgmtDateSearch clearDate.mkMgmtDateSearch', function () {
				var $th = $input.closest('th');
				var value = $.trim($input.val());
				if (value) {
					$th.find('.operatorValue').val('e');
				} else {
					$th.find('.operatorValue').val('');
				}
				scheduleAutoSearch();
			});
		$input.data('mkMgmtDatePickerReady', true);
	}

	function initManagementDateSearchPickers() {
		if (!isManagementProjectList() && !isManagementProjectTaskList()) {
			return;
		}
		if (!hasDatePickerPlugin()) {
			return;
		}
		bindMgmtDatePickerDelegation();
		var $row = $('#listview-table thead tr.searchRow');
		if (!$row.length) {
			return;
		}
		var $inputs = $row.find('input.dateField');
		if (!$inputs.length) {
			return;
		}

		$inputs.each(function () {
			var $input = $(this);
			if (!$input.closest('.mk-date-search-group').length) {
				$input.wrap('<div class="input-group inputElement mk-date-search-group"></div>');
				$input.after(
					'<span class="input-group-addon mk-date-search-trigger" role="button" tabindex="-1">' +
						'<i class="fa fa-calendar"></i>' +
						'</span>'
				);
			}
		});

		$inputs.each(function () {
			var $input = $(this);
			if ($input.data('datepicker') && !$input.data('mkMgmtDatePickerReady')) {
				destroyMkMgmtDatePicker($input);
			}
			if ($input.data('mkMgmtDatePickerReady') && $input.data('datepicker')) {
				return;
			}
			initMkMgmtSingleDatePicker($input);
		});
	}

	window.mkProjectListInitDatePickers = initManagementDateSearchPickers;
	window.mkProjectTaskListInitDatePickers = initManagementDateSearchPickers;

	window.__mkMgmtDatePickerAudit = function () {
		var $inputs = $('#listview-table thead tr.searchRow input.dateField');
		return {
			module: (document.body && document.body.getAttribute('data-module')) || '',
			hasPlugin: hasDatePickerPlugin(),
			inputCount: $inputs.length,
			readyCount: $inputs.filter(function () {
				return !!$(this).data('mkMgmtDatePickerReady');
			}).length,
			visiblePickers: $('.datepicker:visible').length,
			pickers: $('.datepicker')
				.map(function () {
					var $p = $(this);
					return {
						display: $p.css('display'),
						visibility: $p.css('visibility'),
						opacity: $p.css('opacity'),
						zIndex: $p.css('z-index'),
						top: $p.css('top'),
						left: $p.css('left'),
						rect: this.getBoundingClientRect
							? this.getBoundingClientRect()
							: null
					};
				})
				.get()
		};
	};

	function reinitSearchRow() {
		var $row = getSalesTableRoot().find('tr.searchRow').first();
		if ($row.length && window.vtUtils && vtUtils.applyFieldElementsView) {
			try {
				if (isManagementProjectList() || isManagementProjectTaskList()) {
					vtUtils.showSelect2ElementView($row.find('select.select2'));
					vtUtils.registerEventForTimeFields($row.find('.timepicker-default'));
				} else {
					vtUtils.applyFieldElementsView($row);
				}
			} catch (e) {
				/* ignore */
			}
		}
		syncSearchFieldMeta();
		fixSearchRowSelect2();
		initManagementDateSearchPickers();
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
			searchParams.push([
				resolveReferenceSearchFieldName(fieldName, fieldInfo),
				searchOperator,
				searchValue
			]);
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
				var rowFieldInfo = (typeof uimeta !== 'undefined' && uimeta.field && uimeta.field.get)
					? uimeta.field.get(row.fieldName)
					: null;
				searchParams.push([
					resolveReferenceSearchFieldName(row.fieldName, rowFieldInfo || { type: 'string' }),
					row.comparator,
					row.searchValue
				]);
			}
		}
		var listSearchParams = searchParams.length > 0 ? [searchParams] : [];
		if (includeStarFilters && listInstance && listInstance.addStarSearchParams) {
			listSearchParams = listInstance.addStarSearchParams(listSearchParams);
		}
		return listSearchParams;
	}

	function hideProgressSafe() {
		try {
			if (typeof vtUtils !== 'undefined' && vtUtils.removeMask) {
				vtUtils.removeMask();
			}
			if (typeof app !== 'undefined' && app.helper && app.helper.hideProgress) {
				app.helper.hideProgress();
			}
		} catch (e) {
			/* ignore */
		}
	}

	function applySalesShellListResponse(html, requestId, options) {
		options = options || {};
		if (requestId !== inflightSalesSearchId) {
			return false;
		}
		var preserve = shouldPreserveSearchRow(options);
		var focusState = null;
		if (preserve) {
			focusState = pendingSalesSearchRowState || captureSearchFocusState(getListViewContainer());
			pendingSalesSearchRowState = null;
		}
		var applied = applySalesShellListContents(html, options);
		if (applied) {
			finalizeSalesListSearchUi(preserve ? { skipSearchReinit: true } : {}, focusState);
			hideProgressSafe();
			if (
				preserve &&
				document.body.getAttribute('data-module') === 'Quotes' &&
				window.__mkQuotesListUi &&
				typeof window.__mkQuotesListUi.refreshListRowsOnly === 'function'
			) {
				window.__mkQuotesListUi.refreshListRowsOnly();
			}
		}
		return applied;
	}

	function syncUrlSearchParams(searchParams) {
		if (!window.history || !window.history.replaceState) {
			return;
		}
		try {
			var url = new URL(window.location.href);
			if (!searchParams || !searchParams.length || !searchParams[0] || !searchParams[0].length) {
				url.searchParams.delete('search_params');
			} else {
				url.searchParams.set('search_params', JSON.stringify(searchParams));
			}
			url.searchParams.set('page', '1');
			window.history.replaceState({}, '', url.toString());
		} catch (urlErr) {
			/* ignore */
		}
	}

	function runSalesListSearch(options) {
		options = options || {};
		ensureSearchRowVisible();
		syncSearchFieldMeta();
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (!listInstance || !listInstance.loadListViewRecords) {
			return;
		}
		var requestId = ++inflightSalesSearchId;
		listInstance.filterClick = false;
		if (options.preserveSearchRow) {
			pendingSalesSearchRowState = captureSearchFocusState(getListViewContainer());
		} else {
			pendingSalesSearchRowState = null;
		}
		var searchParams = getListSearchParamsSafe(listInstance, false);
		if (!searchParams.length || !searchParams[0] || !searchParams[0].length) {
			getSalesTableRoot().find('#currentSearchParams').val('');
			searchParams = [];
		}
		syncUrlSearchParams(searchParams);
		if (!options.silent) {
			try {
				if (typeof app !== 'undefined' && app.helper && app.helper.showProgress) {
					app.helper.showProgress();
				}
			} catch (progressErr) {
				/* ignore */
			}
		}
		listInstance
			.loadListViewRecords({
				page: '1',
				search_params: JSON.stringify(searchParams),
				nolistcache: '1'
			})
			.done(function (html) {
				if (isMkShellList()) {
					if (!applySalesShellListResponse(html, requestId, options)) {
						hideProgressSafe();
					}
				} else {
					hideProgressSafe();
				}
			})
			.always(function () {
				if (requestId !== inflightSalesSearchId) {
					return;
				}
				pendingSalesSearchRowState = null;
				hideProgressSafe();
				inflightSalesSearchId = 0;
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
			runSalesListSearch({ silent: true, preserveSearchRow: true });
		}, 480);
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
		var listViewPageDiv = getListViewContainer();
		listViewPageDiv.off('click', '[data-trigger="listSearch"]');
		listViewPageDiv.off('click', '[data-trigger="clearListSearch"]');
		listViewPageDiv.off('keyup', '.listSearchContributor');
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
				if (autoSearchTimer) {
					clearTimeout(autoSearchTimer);
					autoSearchTimer = null;
				}
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

	function patchSalesShellPostLoad() {
		if (isPotentialsSalesList() || salesShellPostLoadPatched || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		if (Vtiger_List_Js.prototype.__mkSalesShellPostLoad) {
			salesShellPostLoadPatched = true;
			return;
		}
		var proto = Vtiger_List_Js.prototype;
		var origPostLoad = proto.postLoadListViewRecords;
		proto.postLoadListViewRecords = function (res) {
			if (!isMkShellList()) {
				return origPostLoad.apply(this, arguments);
			}
			if (inflightSalesSearchId > 0) {
				/* DOM swap handled by applySalesShellListResponse after search completes. */
				return;
			}
			var renderId = inflightSalesSearchId;
			var self = this;
			var origPlace = self.placeListContents;
			self.placeListContents = function (contents) {
				if (renderId !== inflightSalesSearchId) {
					hideProgressSafe();
					return;
				}
				return origPlace.call(self, contents);
			};
			origPostLoad.call(self, res);
			self.placeListContents = origPlace;
		};
		proto.__mkSalesShellPostLoad = true;
		salesShellPostLoadPatched = true;
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
		if (!proto.__mkSalesListSearchSafe) {
			var origGetSearch = proto.getListSearchParams;
			proto.getListSearchParams = function (includeStarFilters) {
				if (!needsSalesListSearchHooks()) {
					return origGetSearch.apply(this, arguments);
				}
				return getListSearchParamsSafe(this, includeStarFilters);
			};
			proto.__mkSalesListSearchSafe = true;
		}
		if (!proto.__mkSalesLoadListHooks) {
			var origLoad = proto.loadListViewRecords;
			proto.loadListViewRecords = function (urlParams) {
				if (needsSalesListSearchHooks() && !isPotentialsSalesList()) {
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
			proto.__mkSalesLoadListHooks = true;
		}
		Vtiger_List_Js.prototype.__mkSalesListTableHooks = true;
		salesTableHooksPatched = true;
	}

	function ensureSalesListTableUi(options) {
		options = options || {};
		if (!isSalesStyleTableList()) {
			return;
		}
		ensureSearchRowVisible();
		if (!options.skipSearchReinit) {
			reinitSearchRow();
		}
		assignControlColumnClasses();
		syncRowSelectedClass();
		syncSearchButtonState();
		bindSalesListTableEvents();
		ensureGlobalQuickSearch();
	}

	window.mkSalesListAfterAjax = function (options) {
		if (!isSalesStyleTableList()) {
			return;
		}
		ensureSalesListTableUi(options || {});
	};

	/* ====================================================================== */
	/* Global quick search (Leads-style single search input)                   */
	/* ====================================================================== */
	function shouldUseGlobalQuickSearch() {
		// Leads list is a bespoke UI (LeadsMkList.js) — don't inject here.
		var mod = (document.body && document.body.getAttribute('data-module')) || '';
		if (String(mod).toLowerCase() === 'leads') {
			return false;
		}
		return isSalesStyleTableList();
	}

	function getGlobalQuickSearchQuery() {
		var $input = $('#mk-so-global-search');
		return $input.length ? $.trim($input.val()) : '';
	}

	function normalizeText(s) {
		return String(s || '')
			.toLowerCase()
			.replace(/\s+/g, ' ')
			.trim();
	}

	function rowTextForFilter(rowEl) {
		if (!rowEl) {
			return '';
		}
		var t = rowEl.getAttribute('data-mk-rowtext');
		if (t != null) {
			return t;
		}
		// Exclude action/control cell to avoid noisy icons.
		var parts = [];
		$(rowEl)
			.children('td')
			.not('.listViewRecordActions, .mk-col-control')
			.each(function () {
				var text = $(this).text();
				if (text) {
					parts.push(text);
				}
			});
		t = normalizeText(parts.join(' '));
		rowEl.setAttribute('data-mk-rowtext', t);
		return t;
	}

	function clearRowTextCache() {
		getSalesTableRoot()
			.find('#listview-table tbody tr.listViewEntries[data-mk-rowtext]')
			.removeAttr('data-mk-rowtext');
	}

	function applyGlobalQuickSearchFilter() {
		var $root = getSalesTableRoot();
		if (!$root.length) {
			return;
		}
		var q = normalizeText(getGlobalQuickSearchQuery());
		var $rows = $root.find('#listview-table tbody tr.listViewEntries');
		if (!q) {
			$rows.show();
			$root.removeClass('mk-so-global-search-active');
			return;
		}
		$root.addClass('mk-so-global-search-active');
		$rows.each(function () {
			var hit = rowTextForFilter(this).indexOf(q) >= 0;
			$(this).toggle(hit);
		});
	}

	function scheduleGlobalQuickSearch() {
		if (globalQuickSearchTimer) {
			clearTimeout(globalQuickSearchTimer);
		}
		// Very small debounce to feel instant but avoid layout thrash.
		globalQuickSearchTimer = setTimeout(function () {
			globalQuickSearchTimer = null;
			applyGlobalQuickSearchFilter();
		}, 40);
	}

	function injectGlobalQuickSearchUi() {
		var $root = getSalesTableRoot();
		if (!$root.length) {
			return;
		}
		var $bar = $root.find('#listview-actions .mk-so-filter-row__start').first();
		if (!$bar.length) {
			// Fallback: place inside actions container.
			$bar = $root.find('#listview-actions').first();
		}
		if (!$bar.length || $bar.find('#mk-so-global-search').length) {
			return;
		}

		var html =
			'<div class="mk-so-global-search" role="search">' +
			'<span class="mk-so-global-search__ic" aria-hidden="true"><i class="fa fa-search"></i></span>' +
			'<input id="mk-so-global-search" class="mk-so-global-search__input" type="search" placeholder="Search…" autocomplete="off" />' +
			'<button type="button" class="mk-so-global-search__clear" id="mk-so-global-search-clear" aria-label="Clear" hidden>' +
			'<i class="fa fa-times"></i></button>' +
			'</div>';
		$bar.prepend(html);
	}

	function bindGlobalQuickSearchEvents() {
		if (globalQuickSearchBound) {
			return;
		}
		globalQuickSearchBound = true;
		$(document)
			.off('input.mkSoGlobalSearch', '#mk-so-global-search')
			.on('input.mkSoGlobalSearch', '#mk-so-global-search', function () {
				var val = $.trim($(this).val());
				$('#mk-so-global-search-clear').prop('hidden', !val);
				scheduleGlobalQuickSearch();
			})
			.off('keydown.mkSoGlobalSearch', '#mk-so-global-search')
			.on('keydown.mkSoGlobalSearch', '#mk-so-global-search', function (ev) {
				if (ev.key === 'Escape') {
					ev.preventDefault();
					$(this).val('');
					$('#mk-so-global-search-clear').prop('hidden', true);
					clearRowTextCache();
					applyGlobalQuickSearchFilter();
				}
			});
		$(document)
			.off('click.mkSoGlobalSearchClear', '#mk-so-global-search-clear')
			.on('click.mkSoGlobalSearchClear', '#mk-so-global-search-clear', function (e) {
				e.preventDefault();
				$('#mk-so-global-search').val('').trigger('input').focus();
				clearRowTextCache();
				applyGlobalQuickSearchFilter();
			});
	}

	function ensureGlobalQuickSearch() {
		if (!shouldUseGlobalQuickSearch()) {
			return;
		}
		var $root = getSalesTableRoot();
		if (!$root.length) {
			return;
		}
		$root.addClass('mk-so-global-search-enabled');
		injectGlobalQuickSearchUi();
		bindGlobalQuickSearchEvents();
		// After PJAX swaps, rows are new → clear cache and reapply current query.
		clearRowTextCache();
		applyGlobalQuickSearchFilter();
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
					patchSalesShellPostLoad();
				}
				bindSalesListTableEvents();
				ensureSalesListTableUi();
			}
			bindViewLayoutToggle();
			bindPageJumpDropdownFix();
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
		isManagementShellList: isManagementShellList,
		isMkShellList: isMkShellList,
		isManagementProjectTaskList: isManagementProjectTaskList,
		isManagementProjectList: isManagementProjectList,
		isManagementDocumentsList: isManagementDocumentsList,
		isMkEnhancedList: isMkEnhancedList,
		supportsLayoutToggle: supportsLayoutToggle,
		applyCommonUi: applyCommonUi,
		revealSalesListUi: revealSalesListUi,
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
		applySalesShellListContents: applySalesShellListContents,
		applyPotentialsListContents: applyPotentialsListContents,
		isSearchRowFocused: isSearchRowFocused,
		captureSearchFocusState: captureSearchFocusState,
		restoreSearchFocusState: restoreSearchFocusState,
		resolveReferenceSearchFieldName: resolveReferenceSearchFieldName,
		initManagementDateSearchPickers: initManagementDateSearchPickers,
		showMkMgmtDatePicker: showMkMgmtDatePicker
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
