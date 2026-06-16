/**
 * MARKETING app list — shared toolbar footer (mk-so-filter-row__footer below table), floatThead off, AJAX shell.
 */
(function ($) {
	'use strict';

	var placeListContentsPatched = false;
	var floatTheadPatched = false;
	var postLoadPatched = false;
	var showPagingPatched = false;
	var layoutToggleBound = false;

	function isMarketingAppList() {
		var b = document.body;
		if (!b || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'MARKETING') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('view') === 'List' && params.get('app') === 'MARKETING';
	}

	function getListViewContainer() {
		return $('#listViewContent');
	}

	function relocatePaginationFooter() {
		if (!isMarketingAppList()) {
			return;
		}
		var $lv = getListViewContainer();
		if (!$lv.length) {
			return;
		}
		var $card = $lv.find('.mk-so-table-card').first();
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
			$footer = $scope.find('.mk-so-filter-row__footer').first();
		}
		if (!$footer.length) {
			return;
		}
		$footer = $footer.detach();
		$scope.find('.mk-so-filter-row__footer').remove();
		if ($inner.length && ($inner.is('.col-sm-12') || $inner.is('.col-xs-12'))) {
			$inner.append($footer);
			return;
		}
		$table.after($footer);
	}

	function bindPageJumpDropdownFix() {
		$(document)
			.off('click.mkMktPageJump mousedown.mkMktPageJump', '#PageJumpDropDown, #PageJumpDropDown *')
			.on('click.mkMktPageJump mousedown.mkMktPageJump', '#PageJumpDropDown, #PageJumpDropDown *', function (e) {
				e.stopPropagation();
			});
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
		return $incoming.find('.col-sm-12').first().length ? $incoming.find('.col-sm-12').first() : $incoming;
	}

	function swapListBodyInShell(contents) {
		var $lv = getListViewContainer();
		var $page = $lv.find('.mk-so-page.mk-so-list-sales-root').first();
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
		return true;
	}

	/**
	 * Auto-fetch total record count (replaces click on "of ?").
	 */
	function autoLoadTotalRecordCount() {
		if (!isMarketingAppList() || typeof Vtiger_List_Js === 'undefined') {
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

	function getListModuleName() {
		var b = document.body;
		return (b && b.getAttribute('data-module')) || 'Vtiger';
	}

	function getLayoutStorageKey() {
		return 'mk_sales_list_layout_' + getListModuleName();
	}

	function getSavedLayoutMode() {
		try {
			var saved = window.localStorage.getItem(getLayoutStorageKey());
			if (saved === 'grid' || saved === 'list') {
				return saved;
			}
		} catch (e) {
			/* ignore */
		}
		return 'list';
	}

	function applyLayoutMode(mode) {
		if (!isMarketingAppList()) {
			return;
		}
		var isGrid = mode === 'grid';
		var $lv = getListViewContainer();
		$lv.toggleClass('mk-so-is-view-grid', isGrid);
		document.body.classList.toggle('mk-so-is-view-grid', isGrid);
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
		if (!isMarketingAppList() || layoutToggleBound) {
			return;
		}
		layoutToggleBound = true;
		$(document).on('click.mkMarketingListLayout', '.mk-so-toggle-layout--list', function (e) {
			if (!isMarketingAppList()) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			applyLayoutMode('list');
			applyCommonUi();
		});
		$(document).on('click.mkMarketingListLayout', '.mk-so-toggle-layout--grid', function (e) {
			if (!isMarketingAppList()) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			applyLayoutMode('grid');
			applyCommonUi();
		});
		applyLayoutMode(getSavedLayoutMode());
	}

	function applyCommonUi() {
		if (!isMarketingAppList()) {
			return;
		}
		ensureMarketingListTableUi();
		destroyFloatTheadArtifacts();
		relocatePaginationFooter();
		autoLoadTotalRecordCount();
		applyLayoutMode(getSavedLayoutMode());
	}

	function notifyMarketingModuleListUi() {
		if (typeof window.mkMarketingListAfterAjax === 'function') {
			window.mkMarketingListAfterAjax();
		}
		if (typeof window.applyCampaignsListUi === 'function') {
			window.applyCampaignsListUi();
		}
		if (typeof window.applyPlansListUi === 'function') {
			window.applyPlansListUi();
		}
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
			if (!isMarketingAppList() || !listViewContainer.find('.mk-so-page-numbers').length) {
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
			if (isMarketingAppList()) {
				applyCommonUi();
				return;
			}
			originalFloat.call(this);
		};
		Vtiger_List_Js.prototype.reflowList = function () {
			if (isMarketingAppList()) {
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
			if (isMarketingAppList() && swapListBodyInShell(contents)) {
				applyCommonUi();
				notifyMarketingModuleListUi();
				return;
			}
			originalPlace.call(this, contents);
			if (isMarketingAppList()) {
				applyCommonUi();
				notifyMarketingModuleListUi();
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
			if (isMarketingAppList()) {
				setTimeout(applyCommonUi, 0);
			}
		};
	}

	function bindToolbarEvents() {
		var root = getListViewContainer();
		if (!root.length) {
			return;
		}
		$(document).off('click.mkMarketingList', '.mk-so-trigger-columns').on('click.mkMarketingList', '.mk-so-trigger-columns', function (e) {
			e.preventDefault();
			root.find('.listColumnFilter').first().trigger('click');
		});
		$(document).off('click.mkMarketingList', '.mk-so-filter-trigger-search').on('click.mkMarketingList', '.mk-so-filter-trigger-search', function (e) {
			e.preventDefault();
			ensureMarketingListTableUi();
			var $row = root.find('tr.searchRow.listViewSearchContainer').first();
			if ($row.length && $row[0].scrollIntoView) {
				$row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			}
		});
	}

	/* ========== MARKETING list table standard (Opportunities reference) ========== */
	var marketingTableHooksPatched = false;
	var marketingTableEventsBound = false;
	var autoSearchTimer = null;

	function getMarketingTableRoot() {
		return getListViewContainer();
	}

	function ensureSearchRowVisible() {
		if (!isMarketingAppList()) {
			return;
		}
		var $root = getMarketingTableRoot();
		$root.addClass('mk-so-search-open mk-marketing-list-table-ready');
		$root.addClass('mk-contact-search-open mk-org-search-open mk-camp-search-open mk-plan-search-open');
	}

	function syncSearchFieldMeta() {
		if (typeof uimeta === 'undefined' || !uimeta.field || !uimeta.field.get) {
			return;
		}
		getMarketingTableRoot().find('tr.searchRow .listSearchContributor[name]').each(function () {
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
		var $root = getMarketingTableRoot();
		$root.find('tr.searchRow .select2_input_element').each(function () {
			$(this).attr('tabindex', '-1').attr('aria-hidden', 'true');
		});
		$root.find('tr.searchRow .select2_search_div').css({ width: '100%', maxWidth: '100%', position: 'relative' });
		$root.find('tr.searchRow .select2-container').css({ width: '100%', maxWidth: '100%' });
	}

	function reinitSearchRow() {
		var $row = getMarketingTableRoot().find('tr.searchRow').first();
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
		var listViewPageDiv = getMarketingTableRoot();
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

	function runMarketingListSearch() {
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

	function scheduleAutoSearch() {
		if (!isMarketingAppList()) {
			return;
		}
		if (autoSearchTimer) {
			clearTimeout(autoSearchTimer);
		}
		autoSearchTimer = setTimeout(function () {
			autoSearchTimer = null;
			runMarketingListSearch();
		}, 160);
	}

	function assignControlColumnClasses() {
		var $table = getMarketingTableRoot().find('#listview-table');
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
		getMarketingTableRoot().find('tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			$row.toggleClass(
				'mk-sales-row-selected mk-opp-row-selected',
				$row.find('.listViewEntriesCheckBox:checked').length > 0
			);
		});
	}

	function bindMarketingListTableEvents() {
		if (!isMarketingAppList() || marketingTableEventsBound) {
			return;
		}
		marketingTableEventsBound = true;
		var root = getMarketingTableRoot();
		root.off('keydown.mkMarketingListSearch').on('keydown.mkMarketingListSearch', 'tr.searchRow input.listSearchContributor', function (ev) {
			if (ev.key === 'Enter') {
				ev.preventDefault();
				runMarketingListSearch();
			}
		});
		root
			.off('change.mkMarketingAutoSearch select2-selecting.mkMarketingAutoSearch select2-removed.mkMarketingAutoSearch')
			.on('change.mkMarketingAutoSearch', 'tr.searchRow select.listSearchContributor', function () {
				if ($(this).hasClass('select2_input_element')) {
					return;
				}
				scheduleAutoSearch();
			})
			.on('select2-selecting.mkMarketingAutoSearch select2-removed.mkMarketingAutoSearch', 'tr.searchRow .listSearchContributor.select2', function () {
				scheduleAutoSearch();
			})
			.on('datepicker-change.mkMarketingAutoSearch', 'tr.searchRow .dateField', function () {
				scheduleAutoSearch();
			});
		root.off('change.mkMarketingRowCheck', '.listViewEntriesCheckBox').on('change.mkMarketingRowCheck', '.listViewEntriesCheckBox', syncRowSelectedClass);
		root.off('change.mkMarketingMainCheck', '.listViewEntriesMainCheckBox').on('change.mkMarketingMainCheck', '.listViewEntriesMainCheckBox', syncRowSelectedClass);
	}

	function patchMarketingListTableHooks() {
		if (!isMarketingAppList() || marketingTableHooksPatched || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		if (Vtiger_List_Js.prototype.__mkMarketingListTableHooks) {
			marketingTableHooksPatched = true;
			return;
		}
		var proto = Vtiger_List_Js.prototype;
		var origGetSearch = proto.getListSearchParams;
		proto.getListSearchParams = function (includeStarFilters) {
			if (isMarketingAppList()) {
				return getListSearchParamsSafe(this, includeStarFilters);
			}
			return origGetSearch.apply(this, arguments);
		};
		var origLoad = proto.loadListViewRecords;
		proto.loadListViewRecords = function (urlParams) {
			if (isMarketingAppList()) {
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
		proto.__mkMarketingListTableHooks = true;
		marketingTableHooksPatched = true;
	}

	function ensureMarketingListTableUi() {
		if (!isMarketingAppList()) {
			return;
		}
		ensureSearchRowVisible();
		reinitSearchRow();
		assignControlColumnClasses();
		syncRowSelectedClass();
		bindMarketingListTableEvents();
	}

	window.mkMarketingListAfterAjax = function () {
		if (!isMarketingAppList()) {
			return;
		}
		ensureMarketingListTableUi();
	};

	function scheduleApply() {
		var delays = [0, 50, 150, 400, 800];
		var i;
		for (i = 0; i < delays.length; i++) {
			setTimeout(applyCommonUi, delays[i]);
		}
		$(window).off('load.mkMarketingList').on('load.mkMarketingList', applyCommonUi);
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
		if (!isMarketingAppList()) {
			return;
		}
		whenVtigerListReady(function () {
			patchShowPagingInfo();
			patchVtigerFloatingThead();
			patchPlaceListContents();
			patchPostLoadListViewRecords();
			patchMarketingListTableHooks();
			bindToolbarEvents();
			bindMarketingListTableEvents();
			bindViewLayoutToggle();
			bindPageJumpDropdownFix();
			ensureMarketingListTableUi();
			scheduleApply();
			if (typeof app !== 'undefined' && app.event && app.event.on) {
				app.event.on('post.listViewFilter.click', applyCommonUi);
				app.event.on('post.listViewSort.click', applyCommonUi);
			}
		});
	}

	window.MkMarketingListShared = {
		isMarketingAppList: isMarketingAppList,
		applyCommonUi: applyCommonUi,
		applyLayoutMode: applyLayoutMode,
		getSavedLayoutMode: getSavedLayoutMode,
		bindViewLayoutToggle: bindViewLayoutToggle,
		relocatePaginationFooter: relocatePaginationFooter,
		autoLoadTotalRecordCount: autoLoadTotalRecordCount,
		ensureMarketingListTableUi: ensureMarketingListTableUi,
		runMarketingListSearch: runMarketingListSearch
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
