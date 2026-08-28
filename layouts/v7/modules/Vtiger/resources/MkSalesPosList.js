/**
 * Shared POS list toolbar (search + columns) for Sales modules.
 * Modules opt in via window.__mkSalesPosListConfig = { searchInput, searchClear, searchFields, columnsTrigger }.
 */
(function ($) {
	'use strict';

	var searchTimer = null;
	var liveQuery = '';
	var lastPayload = '';

	function cfg() {
		return window.__mkSalesPosListConfig || {};
	}

	function isPosEnabled() {
		return !!document.querySelector('#listViewContent .mk-so-pos-list-enabled, .mk-so-pos-page');
	}

	function buildSearchParams(query) {
		query = (query || '').toString().trim();
		if (!query.length) {
			return [];
		}
		var fields = cfg().searchFields || [];
		var conditions = [];
		for (var i = 0; i < fields.length; i++) {
			conditions.push([fields[i], 'c', query]);
		}
		return [[], conditions];
	}

	function runQuickSearch(query) {
		query = query != null ? String(query).trim() : liveQuery;
		liveQuery = query;
		var quickParams = buildSearchParams(query);
		var payload = JSON.stringify(quickParams);
		if (payload === lastPayload) {
			return;
		}
		lastPayload = payload;
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (!listInstance || !listInstance.loadListViewRecords) {
			return;
		}
		listInstance.filterClick = false;
		listInstance.loadListViewRecords({
			page: 1,
			search_params: payload,
			nolistcache: 1
		});
	}

	function bindToolbar() {
		var c = cfg();
		var inputSel = c.searchInput || '#mk-pos-search';
		var clearSel = c.searchClear || '#mk-pos-search-clear';
		var columnsSel = c.columnsTrigger || '.mk-pos-trigger-columns';
		var $root = $(document);

		$root
			.off('input.mkSalesPosSearch', inputSel)
			.on('input.mkSalesPosSearch', inputSel, function () {
				var val = $.trim($(this).val() || '');
				$(clearSel).prop('hidden', !val);
				clearTimeout(searchTimer);
				searchTimer = setTimeout(function () {
					runQuickSearch(val);
				}, 280);
			})
			.off('keydown.mkSalesPosSearch', inputSel)
			.on('keydown.mkSalesPosSearch', inputSel, function (ev) {
				if (ev.keyCode === 13) {
					ev.preventDefault();
					clearTimeout(searchTimer);
					runQuickSearch($.trim($(this).val() || ''));
				} else if (ev.keyCode === 27) {
					ev.preventDefault();
					$(this).val('');
					$(clearSel).prop('hidden', true);
					clearTimeout(searchTimer);
					runQuickSearch('');
				}
			})
			.off('click.mkSalesPosSearchClear', clearSel)
			.on('click.mkSalesPosSearchClear', clearSel, function (e) {
				e.preventDefault();
				$(inputSel).val('').trigger('input').focus();
			})
			.off('click.mkSalesPosColumns', columnsSel)
			.on('click.mkSalesPosColumns', columnsSel, function (e) {
				e.preventDefault();
				var $filter = $('#listViewContent .listColumnFilter').first();
				if ($filter.length) {
					$filter.trigger('click');
				}
			});
	}

	function apply() {
		if (!isPosEnabled()) {
			return;
		}
		bindToolbar();
		var c = cfg();
		var inputSel = c.searchInput || '#mk-pos-search';
		var clearSel = c.searchClear || '#mk-pos-search-clear';
		if (liveQuery) {
			$(inputSel).val(liveQuery);
			$(clearSel).prop('hidden', !liveQuery);
		}
	}

	$(document).ready(function () {
		apply();
	});

	app.event.on('post.listViewFilter.click', function () {
		setTimeout(apply, 0);
	});
	app.event.on('post.listViewReloadComplete', function () {
		setTimeout(apply, 0);
	});

	window.MkSalesPosList = {
		apply: apply,
		runQuickSearch: runQuickSearch
	};
})(jQuery);
