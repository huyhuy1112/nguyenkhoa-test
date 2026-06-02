/**
 * Potentials Opportunities list (SALES): scroll fixes, row styling, safe search/toolbar.
 * Scope: body[data-module="Potentials"][data-view="List"][data-app="SALES"]
 */
(function ($) {
	'use strict';

	var MK_BUILD = '20260527_list6';

	function isSalesOpportunityList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'Potentials' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		return (b.getAttribute('data-app') || '').toUpperCase() === 'SALES';
	}

	function getRoot() {
		return $('#listViewContent');
	}

	function normalizeToken(value) {
		return (value || '').toString().toLowerCase().replace(/[^a-z0-9]+/g, '');
	}

	function renderCategoryPills(context) {
		$(context).find('td[data-name="order_category"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-category-pill').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var token = normalizeToken(text);
			var klass = 'mk-cat-default';
			if (token.indexOf('internal') > -1) {
				klass = 'mk-cat-internal';
			} else if (token.indexOf('project') === 0) {
				klass = 'mk-cat-project';
			} else if (token.indexOf('service') === 0) {
				klass = 'mk-cat-service';
			} else if (token.indexOf('consult') === 0) {
				klass = 'mk-cat-consulting';
			}
			$value.empty().append($('<span>', { class: 'mk-category-pill ' + klass, text: text }));
		});
	}

	function renderSalesStages(context) {
		$(context).find('td[data-name="sales_stage"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-stage').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var token = normalizeToken(text);
			var stageClass = 'mk-stage-qualification';
			if (token.indexOf('prospect') > -1) {
				stageClass = 'mk-stage-prospecting';
			} else if (token.indexOf('needs') > -1 || token.indexOf('analysis') > -1) {
				stageClass = 'mk-stage-needsanalysis';
			} else if (token.indexOf('qualif') > -1) {
				stageClass = 'mk-stage-qualification';
			} else if (token.indexOf('proposal') > -1 || token.indexOf('quote') > -1) {
				stageClass = 'mk-stage-proposal';
			} else if (token.indexOf('negotiation') > -1) {
				stageClass = 'mk-stage-negotiation';
			} else if (token.indexOf('closedwon') > -1 || token.indexOf('won') > -1) {
				stageClass = 'mk-stage-closedwon';
			} else if (token.indexOf('closedlost') > -1 || token.indexOf('lost') > -1) {
				stageClass = 'mk-stage-closedlost';
			} else if (token.indexOf('value') > -1 || token.indexOf('proposition') > -1) {
				stageClass = 'mk-stage-valueprop';
			} else if (token.indexOf('decision') > -1 || token.indexOf('maker') > -1) {
				stageClass = 'mk-stage-decision';
			} else if (token.indexOf('perception') > -1) {
				stageClass = 'mk-stage-perception';
			}
			$value.empty().append(
				$('<span>', { class: 'mk-stage ' + stageClass }).append(
					$('<span>', { class: 'dot' }),
					document.createTextNode(text)
				)
			);
		});
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
		if (!isSalesOpportunityList()) {
			return;
		}
		var $tc = getRoot().find('#table-content');
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
			pointerEvents: 'auto',
		});

		getRoot().find('#scroller_wrapper.bottom-fixed-scroll, .bottom-fixed-scroll').css({
			display: 'none',
			height: 0,
			margin: 0,
			padding: 0,
			border: 'none',
			overflow: 'hidden',
			pointerEvents: 'none',
			position: 'absolute',
			left: '-9999px',
			width: 0,
		});

		var $table = getRoot().find('#listview-table');
		if ($table.length && $.fn.floatThead) {
			try {
				$table.floatThead('destroy');
			} catch (e2) {
				/* ignore */
			}
		}
		$table.removeClass('floatThead-table');
		getRoot().find('.floatThead-container').remove();
	}

	function hideProgressSafe() {
		try {
			if (typeof app !== 'undefined' && app.helper && app.helper.hideProgress) {
				app.helper.hideProgress();
			}
		} catch (e) {
			/* ignore */
		}
	}

	function getListInstance() {
		if (typeof app !== 'undefined' && app.controller) {
			var c = app.controller();
			if (c && typeof c.loadListViewRecords === 'function') {
				return c;
			}
		}
		if (window.Vtiger_List_Js && Vtiger_List_Js.getInstance) {
			return Vtiger_List_Js.getInstance();
		}
		return null;
	}

	function syncSearchFieldMeta() {
		if (typeof uimeta === 'undefined' || !uimeta.field || !uimeta.field.get) {
			return;
		}
		getRoot().find('tr.searchRow .listSearchContributor[name]').each(function () {
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

	function reinitSearchRow() {
		var $row = getRoot().find('tr.searchRow').first();
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

	function fixSearchRowSelect2() {
		var $root = getRoot();
		$root.find('tr.searchRow .select2_input_element').each(function () {
			$(this).attr('tabindex', '-1').attr('aria-hidden', 'true');
		});
		$root.find('tr.searchRow .select2_search_div').each(function () {
			$(this).css({ width: '100%', maxWidth: '100%', position: 'relative' });
		});
		$root.find('tr.searchRow .select2-container').css({ width: '100%', maxWidth: '100%' });
	}

	/**
	 * Safe list search params — stock getListSearchParams throws when fieldInfo is missing
	 * (common for reference/custom columns) and returns undefined when filterClick is set.
	 */
	function getListSearchParamsSafe(listInstance, includeStarFilters) {
		if (typeof includeStarFilters === 'undefined') {
			includeStarFilters = true;
		}
		if (listInstance) {
			listInstance.filterClick = false;
		}
		var listViewPageDiv = getRoot();
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
			if (searchContributorElement.hasClass('select2_input_element')) {
				return;
			}
			if (searchContributorElement.is('div')) {
				return;
			}
			var fieldName = searchContributorElement.attr('name');
			if (!fieldName) {
				return;
			}
			var fieldInfo;
			if (typeof uimeta !== 'undefined' && uimeta.field && uimeta.field.get) {
				fieldInfo = uimeta.field.get(fieldName);
			}
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
				fieldType === 'percentage' ||
				fieldType === 'double' ||
				fieldType === 'integer' ||
				fieldType === 'currency' ||
				fieldType === 'number' ||
				fieldType === 'boolean' ||
				fieldType === 'picklist'
			) {
				searchOperator = 'e';
			}
			var storedOperator = searchContributorElement
				.closest('th')
				.find('.operatorValue')
				.val();
			if (storedOperator) {
				searchOperator = storedOperator;
			}
			searchParams.push([fieldName, searchOperator, searchValue]);
		});

		if (currentSearchParams) {
			var i;
			for (i in currentSearchParams) {
				if (!currentSearchParams.hasOwnProperty(i)) {
					continue;
				}
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

	/** PJAX list refresh with explicit search_params (never ListAjax without mode). */
	function runStockListSearch() {
		var root = getRoot();
		root.addClass('mk-opportunity-search-open mk-so-search-open');
		syncSearchFieldMeta();
		var listInstance = getListInstance();
		if (!listInstance || !listInstance.loadListViewRecords) {
			return;
		}
		listInstance.filterClick = false;
		var searchParams = getListSearchParamsSafe(listInstance, false);
		listInstance.loadListViewRecords({
			page: '1',
			search_params: JSON.stringify(searchParams),
		});
	}

	function openColumnPicker() {
		var root = getRoot();
		var listInstance = getListInstance();
		var $col = root.find('#listview-table .listColumnFilter').first();
		if (!$col.length) {
			if (typeof app !== 'undefined' && app.helper && app.helper.showErrorNotification) {
				app.helper.showErrorNotification({
					message: 'Column settings are not available on this view.',
				});
			}
			return;
		}
		if ($col.hasClass('disabled')) {
			if (typeof app !== 'undefined' && app.helper && app.helper.showErrorNotification) {
				app.helper.showErrorNotification({
					message: $col.attr('title') || 'This list cannot be customized.',
				});
			}
			return;
		}
		try {
			if (app.helper.hideModal) {
				app.helper.hideModal();
			}
		} catch (hideModalErr) {
			/* ignore */
		}
		$('.modal-backdrop').remove();
		$('body').removeClass('modal-open');
		if (listInstance && listInstance.getCurrentCvId) {
			$col.trigger('click');
			return;
		}
		$col.trigger('click');
	}

	function patchVtigerListHooks() {
		if (!window.Vtiger_List_Js || !Vtiger_List_Js.prototype || Vtiger_List_Js.prototype.__mkOppListHooks) {
			return !!window.Vtiger_List_Js;
		}
		var proto = Vtiger_List_Js.prototype;

		var origRegister = proto.registerFloatingThead;
		var origReflow = proto.reflowList;
		proto.registerFloatingThead = function () {
			if (isSalesOpportunityList()) {
				fixListScrollContainer();
				return;
			}
			return origRegister.apply(this, arguments);
		};
		proto.reflowList = function () {
			if (isSalesOpportunityList()) {
				fixListScrollContainer();
				return;
			}
			return origReflow.apply(this, arguments);
		};

		var origShowSelectAll = proto.showSelectAll;
		proto.showSelectAll = function () {
			if (isSalesOpportunityList()) {
				return;
			}
			return origShowSelectAll.apply(this, arguments);
		};

		var origGetSearch = proto.getListSearchParams;
		proto.getListSearchParams = function (includeStarFilters) {
			if (!isSalesOpportunityList()) {
				return origGetSearch.apply(this, arguments);
			}
			return getListSearchParamsSafe(this, includeStarFilters);
		};

		var origLoad = proto.loadListViewRecords;
		proto.loadListViewRecords = function (urlParams) {
			if (isSalesOpportunityList()) {
				this.filterClick = false;
				if (typeof urlParams === 'undefined') {
					urlParams = {};
				}
				if (typeof urlParams.search_params === 'undefined') {
					urlParams.search_params = JSON.stringify(
						getListSearchParamsSafe(this, false)
					);
				}
			}
			return origLoad.apply(this, arguments);
		};

		proto.__mkOppListHooks = true;
		return true;
	}

	function enhanceRows(context) {
		if (!isSalesOpportunityList()) {
			return;
		}
		renderCategoryPills(context || document);
		renderSalesStages(context || document);
	}

	function afterListLayout() {
		if (!isSalesOpportunityList()) {
			return;
		}
		fixListScrollContainer();
		reinitSearchRow();
		enhanceRows(document);
		hideProgressSafe();
	}

	function bindListEvents() {
		var root = getRoot();
		if (!root.length) {
			return;
		}

		root.addClass('mk-opportunity-search-open mk-so-search-open');

		/* Enter in filter row → stock Search button */
		root.off('keydown.mkOppListSearch').on('keydown.mkOppListSearch', 'tr.searchRow input.listSearchContributor', function (ev) {
			if (ev.key === 'Enter') {
				ev.preventDefault();
				runStockListSearch();
			}
		});

		/* Green Search: ensure filterClick cleared and params collected before PJAX */
		root
			.off('click.mkOppListSearchBtn', '[data-trigger="listSearch"]')
			.on('click.mkOppListSearchBtn', '[data-trigger="listSearch"]', function (e) {
				if (!isSalesOpportunityList()) {
					return;
				}
				var listInstance = getListInstance();
				if (listInstance) {
					listInstance.filterClick = false;
				}
				syncSearchFieldMeta();
			});

		$(document)
			.off('click.mkOppListColumns', '.mk-opportunity-trigger-columns, .mk-so-trigger-columns')
			.on('click.mkOppListColumns', '.mk-opportunity-trigger-columns, .mk-so-trigger-columns', function (e) {
				if (!isSalesOpportunityList()) {
					return;
				}
				e.preventDefault();
				e.stopImmediatePropagation();
				openColumnPicker();
			});

		$(document)
			.off('click.mkOppListSearchIcon', '.mk-opportunity-filter-trigger-search, .mk-so-filter-trigger-search')
			.on('click.mkOppListSearchIcon', '.mk-opportunity-filter-trigger-search, .mk-so-filter-trigger-search', function (e) {
				if (!isSalesOpportunityList()) {
					return;
				}
				e.preventDefault();
				e.stopImmediatePropagation();
				root.addClass('mk-opportunity-search-open mk-so-search-open');
				var $row = root.find('tr.searchRow.listViewSearchContainer').first();
				var $tc = root.find('#table-content');
				if ($row.length && $tc.length) {
					try {
						var rowTop = $row.position().top;
						$tc.stop(true).animate({ scrollTop: Math.max(0, rowTop + $tc.scrollTop() - 8) }, 180);
					} catch (scrollErr) {
						/* ignore */
					}
				}
				if ($row.length && $row[0].scrollIntoView) {
					$row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
				var $first = root.find('tr.searchRow input.listSearchContributor:visible').filter(function () {
					return !$(this).hasClass('select2_input_element');
				}).first();
				if (!$first.length) {
					$first = root.find('tr.searchRow input.listSearchContributor').not('.select2_input_element').first();
				}
				if ($first.length) {
					setTimeout(function () {
						$first.trigger('focus');
					}, 120);
				}
			});

		$(document)
			.off('click.mkOppListPaging', '#listViewContent #NextPageButton, #listViewContent #PreviousPageButton, #listViewContent #pageToJumpSubmit')
			.on('click.mkOppListPaging', '#listViewContent #NextPageButton, #listViewContent #PreviousPageButton, #listViewContent #pageToJumpSubmit', function () {
				if (!isSalesOpportunityList()) {
					return;
				}
				setTimeout(afterListLayout, 250);
			});

		root.off('change.mkOppRowCheck', '.listViewEntriesCheckBox').on('change.mkOppRowCheck', '.listViewEntriesCheckBox', function () {
			var listInstance = getListInstance();
			if (listInstance && listInstance.registerPostLoadListViewActions) {
				setTimeout(function () {
					listInstance.registerPostLoadListViewActions();
				}, 0);
			}
		});

		root.off('change.mkOppMainCheck', '.listViewEntriesMainCheckBox').on('change.mkOppMainCheck', '.listViewEntriesMainCheckBox', function () {
			hideProgressSafe();
			var listInstance = getListInstance();
			if (listInstance && listInstance.registerPostLoadListViewActions) {
				setTimeout(function () {
					listInstance.registerPostLoadListViewActions();
				}, 50);
			}
		});
	}

	function bindSelect2FilterDrop() {
		$(document).off('select2-open.mkOppListFilter').on('select2-open.mkOppListFilter', function (e) {
			if (!isSalesOpportunityList()) {
				return;
			}
			var $drop = $('#select2-drop');
			if (!$drop.length) {
				return;
			}
			var $container = $(e.target).closest('.select2-container');
			var w = Math.max($container.outerWidth() || 0, 200);
			$drop.css({ minWidth: w + 'px', width: 'auto' });
		});
	}

	function whenReady(callback) {
		var n = 0;
		(function tick() {
			if (window.Vtiger_List_Js && $('#listViewContent').length) {
				callback();
				return;
			}
			n += 1;
			if (n < 120) {
				setTimeout(tick, 25);
			}
		})();
	}

	/** Called from MkSalesListShared after AJAX list refresh */
	window.mkPotentialsListAfterAjax = function () {
		if (!isSalesOpportunityList()) {
			return;
		}
		bindListEvents();
		afterListLayout();
		var listInstance = getListInstance();
		if (listInstance) {
			if (listInstance.registerDynamicListHeaders) {
				listInstance.registerDynamicListHeaders();
			}
			if (listInstance.registerPostLoadListViewActions) {
				listInstance.registerPostLoadListViewActions();
			}
		}
	};

	/** Console: __mkOppTestSearch() — log params sent on next search */
	window.__mkOppTestSearch = function () {
		var li = getListInstance();
		var params = getListSearchParamsSafe(li, false);
		console.log('[Potentials List] search_params', params, JSON.stringify(params));
		return params;
	};

	function init() {
		if (!isSalesOpportunityList()) {
			return;
		}

		whenReady(function () {
			patchVtigerListHooks();
			bindListEvents();
			bindSelect2FilterDrop();
			afterListLayout();

			if (typeof app !== 'undefined' && app.event && app.event.on) {
				app.event.on('post.listViewFilter.click', function (event, searchRow) {
					if (!isSalesOpportunityList()) {
						return;
					}
					setTimeout(function () {
						if (searchRow) {
							reinitSearchRow();
						}
						afterListLayout();
					}, 120);
				});
			}
		});

		$(window).off('resize.mkOpportunityList').on('resize.mkOpportunityList', function () {
			if (isSalesOpportunityList()) {
				fixListScrollContainer();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	window.__mkOppListBuild = MK_BUILD;
})(jQuery);
