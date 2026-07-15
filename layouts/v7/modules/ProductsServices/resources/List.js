/**
 * ProductsServices list (Sales + Kho/INVENTORY): scroll, pagination, type pills, modern toolbar.
 */
(function ($) {
	'use strict';

	var SEARCH_PLACEHOLDERS = {
		productsservicesname: 'Tên hàng',
		item_type: 'Loại',
		price: 'Giá',
		wholesale_price: 'Giá sỉ',
		warranty: 'Bảo hành',
		assigned_user_id: 'Người phụ trách'
	};

	var GLOBAL_SEARCH_FIELDS = ['productsservicesname'];
	var GLOBAL_SEARCH_DEBOUNCE_MS = 600;
	var globalSearchTimer = null;
	var lastGlobalSearchPayload = '';
	var liveGlobalSearchQuery = '';
	var globalSearchBound = false;
	var postLoadPatched = false;

	var TOOLBAR_MIRROR_SUFFIXES = [
		'filter-row',
		'filter-row__inner',
		'filter-row__start',
		'filter-row__right',
		'filter-row__footer',
		'toolbar-toggles',
		'toolbar-count',
		'page-numbers',
		'page-numbers__prefix',
		'page-numbers__suffix',
		'mass-actions',
		'mass-btn',
		'mass-more',
		'icon-btn',
		'icon-btn__ic',
		'pagination',
		'pagination__btns',
		'page-btn',
		'pagejump-group',
		'ajax-empty-hint--toolbar',
		'selectall-msg',
		'toggle-layout',
		'toggle-layout--list',
		'toggle-layout--grid',
		'trigger-columns',
		'sort-hint',
		'filter-trigger-search',
		'global-search',
		'global-search__ic',
		'global-search__input',
		'global-search__clear'
	];

	function mirrorToolbarClasses(context) {
		var $scope = context ? $(context) : $(document);
		var i;
		for (i = 0; i < TOOLBAR_MIRROR_SUFFIXES.length; i++) {
			$scope.find('.mk-so-' + TOOLBAR_MIRROR_SUFFIXES[i]).addClass('mk-ps-' + TOOLBAR_MIRROR_SUFFIXES[i]);
		}
		$scope.find('#listview-actions.mk-so-filter-row').addClass('mk-ps-filter-row');
	}

	function localizeToolbar() {
		$('.mk-so-page-numbers__prefix, .mk-ps-page-numbers__prefix').text('Hiển thị ');
		$('.mk-so-page-numbers__suffix, .mk-ps-page-numbers__suffix').text(' mặt hàng');
		$('.mk-so-mass-more, .mk-ps-mass-more').each(function () {
			var $btn = $(this);
			if ($btn.text().indexOf('More') >= 0 || $btn.text().indexOf('Xem thêm') < 0) {
				$btn.contents().filter(function () {
					return this.nodeType === 3;
				}).first().replaceWith('Xem thêm');
			}
		});
	}

	function buildGlobalSearchParams(query) {
		query = (query || '').toString().trim();
		if (!query.length) {
			return [];
		}
		var conditions = [];
		var i;
		for (i = 0; i < GLOBAL_SEARCH_FIELDS.length; i++) {
			conditions.push([GLOBAL_SEARCH_FIELDS[i], 'c', query]);
		}
		if (conditions.length === 1) {
			return [conditions];
		}
		return [[], conditions];
	}

	function runGlobalQuickSearch(query) {
		query = query != null ? String(query).trim() : liveGlobalSearchQuery;
		liveGlobalSearchQuery = query;
		var payload = JSON.stringify(buildGlobalSearchParams(query));
		if (payload === lastGlobalSearchPayload) {
			return;
		}
		lastGlobalSearchPayload = payload;
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (!listInstance || !listInstance.loadListViewRecords) {
			return;
		}
		listInstance.filterClick = false;
		$('#listViewContent #currentSearchParams').val('');
		try {
			if (typeof app !== 'undefined' && app.helper && app.helper.showProgress) {
				app.helper.showProgress();
			}
		} catch (progressErr) {
			/* ignore */
		}
		listInstance
			.loadListViewRecords({
				page: '1',
				search_params: payload,
				nolistcache: '1'
			})
			.always(function () {
				try {
					if (typeof app !== 'undefined' && app.helper && app.helper.hideProgress) {
						app.helper.hideProgress();
					}
				} catch (hideErr) {
					/* ignore */
				}
			});
	}

	function scheduleGlobalQuickSearch(immediate) {
		if (globalSearchTimer) {
			clearTimeout(globalSearchTimer);
			globalSearchTimer = null;
		}
		if (immediate) {
			runGlobalQuickSearch();
			return;
		}
		globalSearchTimer = setTimeout(function () {
			globalSearchTimer = null;
			runGlobalQuickSearch();
		}, GLOBAL_SEARCH_DEBOUNCE_MS);
	}

	function injectGlobalQuickSearch() {
		var $root = $('#listViewContent');
		var $start = $('#listview-actions .mk-so-filter-row__start, #listview-actions .mk-ps-filter-row__start').first();
		if (!$start.length || $start.find('#mk-ps-global-search, #mk-so-global-search').length) {
			return;
		}
		var html =
			'<div class="mk-so-global-search mk-ps-global-search" role="search">' +
			'<span class="mk-so-global-search__ic mk-ps-global-search__ic" aria-hidden="true"><i class="fa fa-search"></i></span>' +
			'<input id="mk-ps-global-search" class="mk-so-global-search__input mk-ps-global-search__input" type="search" placeholder="Tìm tên hàng, SKU…" autocomplete="off" />' +
			'<button type="button" class="mk-so-global-search__clear mk-ps-global-search__clear" id="mk-ps-global-search-clear" aria-label="Xóa" hidden>' +
			'<i class="fa fa-times"></i></button>' +
			'</div>';
		$start.prepend(html);
		$root.addClass('mk-ps-global-search-enabled mk-so-global-search-enabled');
	}

	function bindGlobalQuickSearchEvents() {
		if (globalSearchBound) {
			return;
		}
		globalSearchBound = true;
		$(document)
			.off('input.mkPsGlobalSearch', '#mk-ps-global-search')
			.on('input.mkPsGlobalSearch', '#mk-ps-global-search', function () {
				var val = $.trim($(this).val());
				liveGlobalSearchQuery = val;
				$('#mk-ps-global-search-clear').prop('hidden', !val);
				scheduleGlobalQuickSearch();
			})
			.off('keydown.mkPsGlobalSearch', '#mk-ps-global-search')
			.on('keydown.mkPsGlobalSearch', '#mk-ps-global-search', function (ev) {
				if (ev.key === 'Enter') {
					ev.preventDefault();
					scheduleGlobalQuickSearch(true);
					return;
				}
				if (ev.key === 'Escape') {
					ev.preventDefault();
					liveGlobalSearchQuery = '';
					lastGlobalSearchPayload = '';
					$(this).val('');
					$('#mk-ps-global-search-clear').prop('hidden', true);
					scheduleGlobalQuickSearch(true);
				}
			})
			.off('click.mkPsGlobalSearchClear', '#mk-ps-global-search-clear')
			.on('click.mkPsGlobalSearchClear', '#mk-ps-global-search-clear', function (e) {
				e.preventDefault();
				liveGlobalSearchQuery = '';
				lastGlobalSearchPayload = '';
				$('#mk-ps-global-search').val('').trigger('input').focus();
			});
	}

	function bindToolbarEvents() {
		var $root = $('#listViewContent');
		$(document)
			.off('click.mkPsList', '.mk-so-trigger-columns, .mk-ps-trigger-columns')
			.on('click.mkPsList', '.mk-so-trigger-columns, .mk-ps-trigger-columns', function (e) {
				e.preventDefault();
				$root.find('.listColumnFilter').first().trigger('click');
			});
		$(document)
			.off('click.mkPsList', '.mk-so-filter-trigger-search, .mk-ps-filter-trigger-search')
			.on('click.mkPsList', '.mk-so-filter-trigger-search, .mk-ps-filter-trigger-search', function (e) {
				e.preventDefault();
				var $search = $('#mk-ps-global-search');
				if ($search.length) {
					$search.focus();
					return;
				}
				$root.toggleClass('mk-ps-search-open');
			});
	}

	function ensureGlobalQuickSearch() {
		injectGlobalQuickSearch();
		bindGlobalQuickSearchEvents();
		if (liveGlobalSearchQuery) {
			$('#mk-ps-global-search').val(liveGlobalSearchQuery);
			$('#mk-ps-global-search-clear').prop('hidden', !liveGlobalSearchQuery);
		}
	}

	function upgradeToolbar() {
		mirrorToolbarClasses();
		localizeToolbar();
		ensureGlobalQuickSearch();
		bindToolbarEvents();
	}

	function patchPostLoadListViewRecords() {
		if (postLoadPatched || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		postLoadPatched = true;
		var originalPostLoad = Vtiger_List_Js.prototype.postLoadListViewRecords;
		Vtiger_List_Js.prototype.postLoadListViewRecords = function (res) {
			originalPostLoad.call(this, res);
			if (isPsSalesList()) {
				setTimeout(afterListLayout, 0);
			}
		};
	}

	var CREATOR_SELECTORS = [
		'td[data-name="assigned_user_id"] .value',
		'td[data-name="created_user_id"] .value',
		'td[data-name="smcreatorid"] .value'
	];

	var TYPE_FIELD_CANDIDATES = ['item_type', 'type', 'product_type'];

	function debugLog() {
		if (window.MK_PS_DEBUG) {
			console.log.apply(console, arguments);
		}
	}

	function isPsSalesList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'ProductsServices' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'INVENTORY') {
			document.documentElement.classList.add('mk-ps-list-inventory');
		}
		if (appName === 'SALES' || appName === 'INVENTORY') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'ProductsServices' && params.get('view') === 'List' && (params.get('app') === 'SALES' || params.get('app') === 'INVENTORY');
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
		if (!isPsSalesList()) {
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
		$('#listViewContent #scroller_wrapper.bottom-fixed-scroll, #listViewContent .bottom-fixed-scroll').css({
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
				/* not initialized */
			}
		}
	}

	function relocatePagination() {
		if (!isPsSalesList()) {
			return;
		}
		var footer = document.querySelector(
			'#listViewContent .mk-so-filter-row__footer, #listViewContent .mk-ps-filter-row__footer'
		);
		var table = document.getElementById('table-content');
		if (!footer || !table || !table.parentNode) {
			return;
		}
		if (table.nextSibling === footer) {
			return;
		}
		table.parentNode.insertBefore(footer, table.nextSibling);
	}

	function markTable() {
		if (!isPsSalesList()) {
			return;
		}
		$('#listViewContent #listview-table').addClass('mk-ps-table');
	}

	var MK_COL_CLASS_NAMES =
		'mk-col-control mk-col-ps-name mk-col-ps-type mk-col-ps-price mk-col-ps-wholesale mk-col-ps-warranty';

	var COL_CLASS_BY_FIELD = {
		productsservicesname: 'mk-col-ps-name',
		item_type: 'mk-col-ps-type',
		type: 'mk-col-ps-type',
		product_type: 'mk-col-ps-type',
		price: 'mk-col-ps-price',
		wholesale_price: 'mk-col-ps-wholesale',
		warranty: 'mk-col-ps-warranty'
	};

	var COL_WIDTH_BY_CLASS = {
		'mk-col-control': '0',
		'mk-col-ps-name': '220px',
		'mk-col-ps-type': '96px',
		'mk-col-ps-price': '120px',
		'mk-col-ps-wholesale': '168px',
		'mk-col-ps-warranty': '96px'
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

	function widthForHeaderTh($th) {
		var cls;
		for (cls in COL_WIDTH_BY_CLASS) {
			if ($th.hasClass(cls)) {
				return COL_WIDTH_BY_CLASS[cls];
			}
		}
		return '';
	}

	function applyColgroup() {
		var $table = $('#listViewContent #listview-table');
		if (!$table.length) {
			return;
		}
		var $headerCells = $table.find('thead tr.listViewContentHeader th');
		if (!$headerCells.length) {
			return;
		}
		$table.find('colgroup').remove();
		var $colgroup = $('<colgroup>');
		$headerCells.each(function () {
			var $th = $(this);
			var width = widthForHeaderTh($th);
			var $col = $('<col>');
			if (width) {
				$col.attr('style', 'width:' + width);
			}
			$colgroup.append($col);
		});
		$table.prepend($colgroup);
	}

	function initialsFromName(name) {
		var parts = (name || '').trim().split(/\s+/).filter(Boolean);
		if (!parts.length) {
			return '?';
		}
		if (parts.length === 1) {
			return parts[0].substring(0, 2).toUpperCase();
		}
		return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
	}

	function normalizeTypeKey(text) {
		var t = String(text || '').toLowerCase();
		if (t.indexOf('service') >= 0) {
			return 'service';
		}
		if (t.indexOf('product') >= 0) {
			return 'product';
		}
		return 'other';
	}

	function enhanceTypePills(context) {
		var i;
		for (i = 0; i < TYPE_FIELD_CANDIDATES.length; i++) {
			$(context).find('td.listViewEntryValue[data-name="' + TYPE_FIELD_CANDIDATES[i] + '"]').addClass('mk-ps-type-cell');
		}
		$(context).find('td.mk-ps-type-cell .value, td[data-name="item_type"] .value, td[data-name="type"] .value').each(function () {
			var $value = $(this);
			if ($value.find('.mk-ps-type-pill').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var key = normalizeTypeKey(text);
			var cls = 'mk-ps-type-pill';
			if (key === 'product') {
				cls += ' mk-ps-type-pill--product';
			} else if (key === 'service') {
				cls += ' mk-ps-type-pill--service';
			}
			$value.empty().append($('<span>', { 'class': cls, text: text }));
		});
	}

	function enhancePriceCells(context) {
		$(context).find('td[data-name="price"]').addClass('mk-ps-price-cell');
		$(context).find('td[data-name="wholesale_price"]').addClass('mk-col-ps-wholesale');
	}

	function enhanceCreatedBy(context) {
		$(context).find(CREATOR_SELECTORS.join(',')).each(function () {
			var $value = $(this);
			if ($value.find('.mk-ps-avatar').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var initials = initialsFromName(text);
			$value.addClass('mk-ps-has-creator').empty().append(
				$('<span>', { 'class': 'mk-ps-avatar', text: initials }),
				$('<span>', { 'class': 'mk-ps-avatar__label', text: text })
			);
		});
	}

	function applySearchPlaceholders(context) {
		$(context).find('tr.searchRow th').each(function () {
			var $th = $(this);
			var name = $th.attr('data-columnname') || $th.data('columnname');
			if (!name) {
				var $input = $th.find('input.listSearchContributor, input[type="text"]').first();
				if ($input.length) {
					name = $input.attr('name') || $input.data('columnname');
				}
			}
			if (!name || !SEARCH_PLACEHOLDERS[name]) {
				return;
			}
			$th.find('input.listSearchContributor, input[type="text"]').each(function () {
				var $inp = $(this);
				if (!$inp.attr('placeholder')) {
					$inp.attr('placeholder', SEARCH_PLACEHOLDERS[name]);
				}
			});
		});
	}

	function setReadyState() {
		if (!isPsSalesList()) {
			return;
		}
		document.body.classList.remove('mk-ps-ui-loading');
		document.body.classList.add('mk-ps-ui-ready');
		document.documentElement.classList.add('mk-ps-ui-ready');
		document.documentElement.classList.add('mk-ps-list-ready');
	}

	var eventsBound = false;

	function afterListLayout() {
		if (!isPsSalesList()) {
			return;
		}
		upgradeToolbar();
		relocatePagination();
		markTable();
		assignColumnClasses();
		applyColgroup();
		enhanceTypePills(document);
		enhancePriceCells(document);
		enhanceCreatedBy(document);
		applySearchPlaceholders(document);
		fixListScrollContainer();
		setReadyState();
		debugLog('mk-ps list layout applied');
	}

	function init() {
		if (!isPsSalesList()) {
			return;
		}
		document.body.classList.add('mk-ps-ui-loading');
		patchPostLoadListViewRecords();

		var root = $('#listViewContent');
		if (!root.length) {
			setReadyState();
			return;
		}

		if (!eventsBound && typeof app !== 'undefined' && app.event && app.event.on) {
			eventsBound = true;
			app.event.on('post.listViewFilter.click', function () {
				setTimeout(afterListLayout, 200);
			});
			app.event.on('post.listViewSort.click', function () {
				setTimeout(afterListLayout, 200);
			});
			app.event.on('Vtiger.Post.MenuToggle', function () {
				setTimeout(fixListScrollContainer, 80);
			});
		}

		var resizeTimer;
		$(window).on('resize.mkPsList', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				if (isPsSalesList()) {
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
