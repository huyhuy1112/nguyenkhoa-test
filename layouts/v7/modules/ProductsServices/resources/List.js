/**
 * ProductsServices list — BA / KiotViet-style content (filters + catalog table).
 * Scope: body[data-module="ProductsServices"][data-view="List"] app SALES|INVENTORY
 */
(function ($) {
	'use strict';

	var CANONICAL_HEADERS = ['sku', 'productsservicesname', 'price_lt_1m', 'price_tuibao'];
	var PAGE_SIZE = 20;
	var SEARCH_DEBOUNCE_MS = 180;
	var COL_COUNT = 10;
	var FILTER_PANEL_STORAGE_KEY = 'mk_ps_filters_open_v1';

	var catalogItems = [];
	var catalogLoaded = false;
	var catalogLoading = false;
	var warehouseOptions = [];
	var groupOptions = [];
	var searchQuery = '';
	var searchTimer = null;
	var searchEventsBound = false;
	var filterEventsBound = false;
	var starEventsBound = false;
	var pageIndex = 1;
	var postLoadPatched = false;
	var eventsBound = false;
	var bulkEventsBound = false;
	var filtersOpen = readFiltersOpenPref();

	var filters = {
		groups: {},
		stock: 'all',
		warehouse: '',
		created: 'all',
		createdFrom: '',
		createdTo: ''
	};

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
		return params.get('module') === 'ProductsServices' && params.get('view') === 'List';
	}

	function getPsAppName() {
		var b = document.body;
		var appName = (b && b.getAttribute('data-app')) || '';
		if (!appName) {
			try {
				appName = new URLSearchParams(window.location.search || '').get('app') || 'INVENTORY';
			} catch (e) {
				appName = 'INVENTORY';
			}
		}
		return appName || 'INVENTORY';
	}

	function ensureListHeadersInput() {
		var $input = $('#listViewContent input[name="list_headers"]').first();
		if ($input.length) {
			$input.val(JSON.stringify(CANONICAL_HEADERS));
		}
	}

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	function formatMoney(n) {
		if (n === null || n === undefined || n === '') {
			return '—';
		}
		n = Math.round(Number(n) || 0);
		var neg = n < 0;
		var abs = Math.abs(n);
		var body = String(abs).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
		return (neg ? '-' : '') + body;
	}

	function formatQty(n) {
		n = Number(n) || 0;
		if (Math.abs(n - Math.round(n)) < 0.001) {
			return String(Math.round(n));
		}
		return String(Math.round(n * 100) / 100);
	}

	function formatCreated(raw) {
		var s = String(raw || '').trim();
		if (!s) {
			return '—';
		}
		// "YYYY-MM-DD HH:MM:SS" → "DD/MM/YYYY HH:MM"
		var m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
		if (m) {
			return m[3] + '/' + m[2] + '/' + m[1] + (m[4] ? ' ' + m[4] + ':' + m[5] : '');
		}
		return s;
	}

	function parseCreatedMs(raw) {
		var s = String(raw || '').trim();
		if (!s) {
			return 0;
		}
		var t = Date.parse(s.replace(' ', 'T'));
		return isNaN(t) ? 0 : t;
	}

	function startOfDay(d) {
		return new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
	}

	function withCsrf(params) {
		params = params || {};
		try {
			if (typeof window.csrfMagicToken !== 'undefined' && window.csrfMagicName) {
				params[window.csrfMagicName] = window.csrfMagicToken;
			}
		} catch (e) {
			/* ignore */
		}
		return params;
	}

	function fetchCatalog() {
		if (catalogLoaded || catalogLoading) {
			return $.Deferred().resolve(catalogItems).promise();
		}
		catalogLoading = true;
		var deferred = $.Deferred();
		var params = withCsrf({
			module: 'ProductsServices',
			action: 'GetCatalog'
		});
		var done = function (payload) {
			catalogLoading = false;
			var items = [];
			if (payload && payload.items && $.isArray(payload.items)) {
				items = payload.items;
			} else if (payload && payload.result && payload.result.items) {
				items = payload.result.items;
			}
			catalogItems = items || [];
			warehouseOptions =
				(payload && payload.warehouses) ||
				(payload && payload.result && payload.result.warehouses) ||
				[];
			groupOptions =
				(payload && payload.groups) ||
				(payload && payload.result && payload.result.groups) ||
				[];
			if (!groupOptions.length) {
				var seen = {};
				catalogItems.forEach(function (it) {
					var g = String(it.product_group || '').trim();
					if (g && !seen[g]) {
						seen[g] = true;
						groupOptions.push(g);
					}
				});
				groupOptions.sort();
			}
			catalogLoaded = true;
			deferred.resolve(catalogItems);
		};
		var fail = function () {
			catalogLoading = false;
			deferred.reject();
		};
		if (typeof app !== 'undefined' && app.request && app.request.get) {
			app.request.get({ data: params }).then(function (err, res) {
				if (err) {
					fail();
					return;
				}
				done(res);
			});
		} else {
			$.ajax({ url: 'index.php', type: 'GET', data: params, dataType: 'json' })
				.done(function (res) {
					if (res && res.success === false) {
						fail();
						return;
					}
					done(res && res.result ? res.result : res);
				})
				.fail(fail);
		}
		return deferred.promise();
	}

	function itemStock(it) {
		var wh = filters.warehouse;
		if (wh && it.stock_by_wh && typeof it.stock_by_wh === 'object') {
			if (Object.prototype.hasOwnProperty.call(it.stock_by_wh, wh)) {
				return Number(it.stock_by_wh[wh]) || 0;
			}
			return 0;
		}
		return Number(it.stock) || 0;
	}

	function readFiltersOpenPref() {
		try {
			var v = window.localStorage.getItem(FILTER_PANEL_STORAGE_KEY);
			if (v === '1') {
				return true;
			}
			if (v === '0') {
				return false;
			}
		} catch (e) {
			/* ignore */
		}
		// Default closed — open when user needs to filter.
		return false;
	}

	function writeFiltersOpenPref(open) {
		try {
			window.localStorage.setItem(FILTER_PANEL_STORAGE_KEY, open ? '1' : '0');
		} catch (e) {
			/* ignore */
		}
	}

	function activeGroupFilters() {
		return Object.keys(filters.groups).filter(function (k) {
			return filters.groups[k];
		});
	}

	function activePanelFilterCount() {
		var n = activeGroupFilters().length;
		if (filters.stock !== 'all') {
			n += 1;
		}
		if (filters.warehouse) {
			n += 1;
		}
		if (filters.created !== 'all') {
			n += 1;
		}
		return n;
	}

	function hasActiveFilters() {
		if (searchQuery) {
			return true;
		}
		return activePanelFilterCount() > 0;
	}

	function applyFiltersPanelState() {
		var $layout = $('.mk-ps-ba-layout').first();
		var $aside = $('#mk-ps-filters');
		var $btn = $('#mk-ps-filters-toggle');
		if ($layout.length) {
			$layout.toggleClass('mk-ps-filters-collapsed', !filtersOpen);
			$layout.toggleClass('mk-ps-filters-open', filtersOpen);
		}
		if ($aside.length) {
			$aside.attr('aria-hidden', filtersOpen ? 'false' : 'true');
			$aside.removeAttr('hidden');
		}
		if ($btn.length) {
			$btn.attr('aria-expanded', filtersOpen ? 'true' : 'false');
			$btn.toggleClass('is-active', filtersOpen);
			$btn.find('.mk-ps-filters-toggle__label').text(filtersOpen ? 'Ẩn lọc' : 'Bộ lọc');
		}
		updateFilterToggleBadge();
	}

	function updateFilterToggleBadge() {
		var n = activePanelFilterCount();
		var $badge = $('#mk-ps-filters-toggle-count');
		if (!$badge.length) {
			return;
		}
		if (n > 0) {
			$badge.text(String(n)).removeAttr('hidden');
		} else {
			$badge.attr('hidden', 'hidden').text('0');
		}
	}

	function setFiltersOpen(open) {
		filtersOpen = !!open;
		writeFiltersOpenPref(filtersOpen);
		applyFiltersPanelState();
	}

	function filteredItems() {
		var q = String(searchQuery || '')
			.trim()
			.toLowerCase();
		var groups = activeGroupFilters();
		var now = new Date();
		var todayStart = startOfDay(now);
		var weekStart = todayStart - now.getDay() * 86400000;
		var monthStart = new Date(now.getFullYear(), now.getMonth(), 1).getTime();

		return catalogItems.filter(function (it) {
			if (q) {
				var name = String(it.name || '').toLowerCase();
				var sku = String(it.sku || '').toLowerCase();
				var group = String(it.product_group || '').toLowerCase();
				if (name.indexOf(q) < 0 && sku.indexOf(q) < 0 && group.indexOf(q) < 0) {
					return false;
				}
			}
			if (groups.length) {
				var g = String(it.product_group || '').trim();
				if (groups.indexOf(g) < 0) {
					return false;
				}
			}
			var stock = itemStock(it);
			if (filters.stock === 'in' && !(stock > 0)) {
				return false;
			}
			if (filters.stock === 'out' && !(stock <= 0)) {
				return false;
			}
			if (filters.stock === 'below_order' && !(stock < (Number(it.qty_so) || 0))) {
				return false;
			}
			if (filters.created !== 'all') {
				var ms = parseCreatedMs(it.createdtime);
				if (!ms) {
					return false;
				}
				if (filters.created === 'today' && ms < todayStart) {
					return false;
				}
				if (filters.created === 'week' && ms < weekStart) {
					return false;
				}
				if (filters.created === 'month' && ms < monthStart) {
					return false;
				}
				if (filters.created === 'range') {
					if (filters.createdFrom) {
						var fromMs = Date.parse(filters.createdFrom + 'T00:00:00');
						if (!isNaN(fromMs) && ms < fromMs) {
							return false;
						}
					}
					if (filters.createdTo) {
						var toMs = Date.parse(filters.createdTo + 'T23:59:59');
						if (!isNaN(toMs) && ms > toMs) {
							return false;
						}
					}
				}
			}
			return true;
		});
	}

	function detailUrl(id) {
		return (
			'index.php?module=ProductsServices&view=Detail&record=' +
			encodeURIComponent(id) +
			'&app=' +
			encodeURIComponent(getPsAppName())
		);
	}

	function ensureBaThead() {
		var $table = $('#listViewContent #listview-table');
		if (!$table.length) {
			return;
		}
		var $thead = $table.find('thead').first();
		if (!$thead.length) {
			$table.prepend('<thead></thead>');
			$thead = $table.find('thead').first();
		}
		$thead.html(
			'<tr class="listViewContentHeader mk-ps-ba-thead">' +
				'<th class="mk-col-control" scope="col">' +
				'<label class="mk-ps-check">' +
				'<input type="checkbox" class="listViewEntriesMainCheckBox mk-ps-check__input" aria-label="Chọn tất cả" />' +
				'<span class="mk-ps-check__ui" aria-hidden="true">' +
				'<svg class="mk-ps-check__tick" width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">' +
				'<path d="M1.75 5.2L4.05 7.35L8.25 2.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>' +
				'</svg></span></label></th>' +
				'<th class="mk-col-ps-star" scope="col" title="Yêu thích">★</th>' +
				'<th class="mk-col-ps-sku" scope="col">Mã hàng</th>' +
				'<th class="mk-col-ps-name" scope="col">Tên hàng</th>' +
				'<th class="mk-col-ps-price" scope="col">Giá bán</th>' +
				'<th class="mk-col-ps-tuibao" scope="col">Giá Tuibao</th>' +
				'<th class="mk-col-ps-stock" scope="col">Tồn kho</th>' +
				'<th class="mk-col-ps-order" scope="col">Khách đặt</th>' +
				'<th class="mk-col-ps-created" scope="col">Thời gian tạo</th>' +
				'<th class="mk-col-ps-stockout" scope="col">Dự kiến hết hàng</th>' +
				'</tr>'
		);
		$table.find('colgroup').remove();
		$table.prepend(
			'<colgroup>' +
				'<col style="width:48px" />' +
				'<col style="width:44px" />' +
				'<col style="width:110px" />' +
				'<col style="width:28%" />' +
				'<col style="width:110px" />' +
				'<col style="width:110px" />' +
				'<col style="width:90px" />' +
				'<col style="width:90px" />' +
				'<col style="width:140px" />' +
				'<col style="width:120px" />' +
				'</colgroup>'
		);
	}

	function buildRowHtml(it) {
		var id = it.id;
		var name = String(it.name || '').trim() || '—';
		var stock = itemStock(it);
		var qtySo = Number(it.qty_so) || 0;
		var thumb = String(it.image_url || it.imageUrl || '').trim();
		var thumbHtml = thumb
			? '<img class="mk-ps-thumb" src="' +
			  esc(thumb) +
			  '" alt="" loading="lazy" onerror="this.onerror=null;var s=document.createElement(\'span\');s.className=\'mk-ps-thumb mk-ps-thumb--empty\';s.setAttribute(\'aria-hidden\',\'true\');this.parentNode.replaceChild(s,this);" />'
			: '<span class="mk-ps-thumb mk-ps-thumb--empty" aria-hidden="true"></span>';
		var stockCls = stock > 0 ? 'mk-ps-num mk-ps-num--ok' : 'mk-ps-num mk-ps-num--out';
		var orderCls = qtySo > 0 ? 'mk-ps-num mk-ps-num--order' : 'mk-ps-num';
		var isStarred = !!(Number(it.starred) || it.starred === true || it.starred === '1');
		var starTitle = isStarred ? 'Bỏ theo dõi' : 'Theo dõi';
		var starHtml =
			'<button type="button" class="mk-ps-star' +
			(isStarred ? ' active' : '') +
			'" data-starred="' +
			(isStarred ? '1' : '0') +
			'" title="' +
			esc(starTitle) +
			'" aria-label="' +
			esc(starTitle) +
			'" aria-pressed="' +
			(isStarred ? 'true' : 'false') +
			'">' +
			(isStarred ? '★' : '☆') +
			'</button>';

		return (
			'<tr class="listViewEntries mk-ps-client-row" data-id="' +
			esc(id) +
			'" data-recordurl="' +
			esc(detailUrl(id)) +
			'">' +
			'<td class="listViewRecordActions mk-col-control">' +
			'<label class="mk-ps-check">' +
			'<input type="checkbox" class="listViewEntriesCheckBox mk-ps-check__input" value="' +
			esc(id) +
			'" />' +
			'<span class="mk-ps-check__ui" aria-hidden="true">' +
			'<svg class="mk-ps-check__tick" width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">' +
			'<path d="M1.75 5.2L4.05 7.35L8.25 2.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>' +
			'</svg></span></label></td>' +
			'<td class="mk-col-ps-star" data-name="starred">' +
			starHtml +
			'</td>' +
			'<td class="listViewEntryValue mk-col-ps-sku" data-name="sku"><span class="fieldValue"><span class="value">' +
			esc(it.sku || '—') +
			'</span></span></td>' +
			'<td class="listViewEntryValue mk-col-ps-name" data-name="productsservicesname">' +
			'<div class="mk-ps-name-cell">' +
			thumbHtml +
			'<span class="mk-ps-name-text" title="' +
			esc(name) +
			'">' +
			esc(name) +
			'</span></div></td>' +
			'<td class="listViewEntryValue mk-col-ps-price" data-name="price_lt_1m"><span class="value">' +
			esc(formatMoney(it.price_lt_1m != null ? it.price_lt_1m : it.price)) +
			'</span></td>' +
			'<td class="listViewEntryValue mk-col-ps-tuibao" data-name="price_tuibao"><span class="value">' +
			esc(formatMoney(it.price_tuibao)) +
			'</span></td>' +
			'<td class="listViewEntryValue mk-col-ps-stock"><span class="' +
			stockCls +
			'">' +
			esc(formatQty(stock)) +
			'</span></td>' +
			'<td class="listViewEntryValue mk-col-ps-order"><span class="' +
			orderCls +
			'">' +
			esc(formatQty(qtySo)) +
			'</span></td>' +
			'<td class="listViewEntryValue mk-col-ps-created"><span class="value">' +
			esc(formatCreated(it.createdtime)) +
			'</span></td>' +
			'<td class="listViewEntryValue mk-col-ps-stockout"><span class="mk-ps-muted">---</span></td>' +
			'</tr>'
		);
	}

	function hideVtigerPaging() {
		var $scope = $('#listViewContent');
		$scope
			.find(
				'.mk-ps-filter-row__footer, .mk-so-filter-row__footer, #listViewPageJumpDropDown, ' +
					'#PreviousPageButton, #NextPageButton, #PageJump, .pageNumbers, .mk-ps-page-numbers, .mk-so-page-numbers'
			)
			.addClass('mk-ps-vtiger-paging-hidden');
		$scope.find('#listview-actions .mk-so-filter-row__right, #listview-actions .mk-ps-filter-row__right').addClass('mk-ps-vtiger-paging-hidden');
	}

	function ensureClientPaginationHost() {
		var $card = $('.mk-ps-table-card').first();
		if (!$card.length) {
			$card = $('#listViewContent').first();
		}
		var $pag = $('#mk-ps-client-pagination');
		if (!$pag.length) {
			$card.append('<div id="mk-ps-client-pagination" class="mk-ps-client-pagination" role="navigation" aria-label="Phân trang"></div>');
			$pag = $('#mk-ps-client-pagination');
		}
		return $pag;
	}

	function renderClientPagination(filteredCount) {
		var $pag = ensureClientPaginationHost();
		var totalPages = Math.max(1, Math.ceil(filteredCount / PAGE_SIZE) || 1);
		if (pageIndex > totalPages) {
			pageIndex = totalPages;
		}
		if (pageIndex < 1) {
			pageIndex = 1;
		}
		var from = filteredCount ? (pageIndex - 1) * PAGE_SIZE + 1 : 0;
		var to = Math.min(pageIndex * PAGE_SIZE, filteredCount);
		$pag.html(
			'<span class="mk-ps-client-pagination__info">Hiển thị ' +
				from +
				'–' +
				to +
				' / ' +
				filteredCount +
				' mặt hàng</span>' +
				'<div class="mk-ps-client-pagination__btns">' +
				'<button type="button" class="mk-ps-page-btn" id="mk-ps-prev"' +
				(pageIndex <= 1 ? ' disabled' : '') +
				'>Trước</button>' +
				'<span class="mk-ps-page-num">' +
				pageIndex +
				' / ' +
				totalPages +
				'</span>' +
				'<button type="button" class="mk-ps-page-btn" id="mk-ps-next"' +
				(pageIndex >= totalPages ? ' disabled' : '') +
				'>Sau</button></div>'
		);
	}

	function renderTotalsBar(rows) {
		var $card = $('.mk-ps-table-card').first();
		if (!$card.length) {
			return;
		}
		var $bar = $('#mk-ps-totals');
		if (!$bar.length) {
			$card.prepend('<div id="mk-ps-totals" class="mk-ps-totals" aria-live="polite"></div>');
			$bar = $('#mk-ps-totals');
		}
		var stockSum = 0;
		var orderSum = 0;
		rows.forEach(function (it) {
			stockSum += itemStock(it);
			orderSum += Number(it.qty_so) || 0;
		});
		$bar.html(
			'<span>Tổng tồn: <strong>' +
				esc(formatQty(stockSum)) +
				'</strong></span>' +
				'<span>Tổng khách đặt: <strong>' +
				esc(formatQty(orderSum)) +
				'</strong></span>'
		);
	}

	function renderCatalogPage() {
		var $table = $('#listViewContent #listview-table');
		if (!$table.length) {
			return;
		}
		ensureBaThead();
		var rows = filteredItems();
		var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE) || 1);
		if (pageIndex > totalPages) {
			pageIndex = totalPages;
		}
		var start = (pageIndex - 1) * PAGE_SIZE;
		var pageRows = rows.slice(start, start + PAGE_SIZE);
		var $tbody = $table.find('tbody').first();
		if (!$tbody.length) {
			$table.append('<tbody></tbody>');
			$tbody = $table.find('tbody').first();
		}
		$tbody.find('tr.searchRow').remove();
		if (!pageRows.length) {
			$tbody.html(
				'<tr class="mk-ps-empty-row"><td colspan="' +
					COL_COUNT +
					'" class="mk-ps-empty-cell">Không tìm thấy mặt hàng phù hợp.</td></tr>'
			);
		} else {
			$tbody.html(pageRows.map(buildRowHtml).join(''));
		}
		hideVtigerPaging();
		renderClientPagination(rows.length);
		renderTotalsBar(rows);
		enhanceCircularChecks();
		renderBulkBar();
		updateFilterResetVisibility();
	}

	function applySearch(query) {
		searchQuery = String(query || '').trim();
		pageIndex = 1;
		$('#mk-ps-search-clear').prop('hidden', !searchQuery);
		if (!catalogLoaded) {
			fetchCatalog().done(function () {
				renderFilters();
				renderCatalogPage();
			});
			return;
		}
		renderCatalogPage();
	}

	function filtersToggleHtml() {
		return (
			'<button type="button" class="mk-ps-filters-toggle" id="mk-ps-filters-toggle" ' +
			'aria-controls="mk-ps-filters" aria-expanded="' +
			(filtersOpen ? 'true' : 'false') +
			'">' +
			'<span class="mk-ps-filters-toggle__ic" aria-hidden="true"><i class="fa fa-filter"></i></span>' +
			'<span class="mk-ps-filters-toggle__label">' +
			(filtersOpen ? 'Ẩn lọc' : 'Bộ lọc') +
			'</span>' +
			'<span class="mk-ps-filters-toggle__count" id="mk-ps-filters-toggle-count" hidden>0</span>' +
			'</button>'
		);
	}

	function ensureSearchBar() {
		$('#listview-actions')
			.find(
				'#mk-so-global-search, .mk-so-global-search, .mk-so-filter-row__search, ' +
					'.mk-ps-global-search, #mk-ps-global-search'
			)
			.each(function () {
				var $w = $(this).closest('.mk-so-global-search, .mk-so-filter-row__search, .mk-ps-global-search, div');
				if ($w.length) {
					$w.remove();
				} else {
					$(this).remove();
				}
			});

		var $start = $('#listview-actions .mk-ps-filter-row__start, #listview-actions .mk-so-filter-row__start').first();
		if (!$start.length) {
			$start = $('#listview-actions').first();
		}
		if (!$start.length) {
			return;
		}

		var $tools = $start.find('.mk-ps-toolbar-tools').first();
		if (!$tools.length) {
			$tools = $('<div class="mk-ps-toolbar-tools"></div>');
			$start.prepend($tools);
		}

		if (!$('#mk-ps-search').length) {
			$tools.append(
				'<div class="mk-ps-search" role="search">' +
					'<span class="mk-ps-search__ic" aria-hidden="true"><i class="fa fa-search"></i></span>' +
					'<input id="mk-ps-search" class="mk-ps-search__input" type="search" ' +
					'placeholder="Theo mã hoặc tên hàng…" autocomplete="off" spellcheck="false" />' +
					'<button type="button" class="mk-ps-search__clear" id="mk-ps-search-clear" aria-label="Xóa tìm kiếm" hidden>' +
					'<i class="fa fa-times" aria-hidden="true"></i>' +
					'</button></div>'
			);
		} else if (!$tools.find('#mk-ps-search').length) {
			var $bar = $('#mk-ps-search').closest('.mk-ps-search');
			if ($bar.length) {
				$tools.prepend($bar);
			}
		}

		if (!$('#mk-ps-filters-toggle').length) {
			$tools.append(filtersToggleHtml());
		} else if (!$tools.find('#mk-ps-filters-toggle').length) {
			$tools.append($('#mk-ps-filters-toggle'));
		}

		$('#mk-ps-search').val(searchQuery);
		$('#mk-ps-search-clear').prop('hidden', !searchQuery);
		applyFiltersPanelState();
	}

	function radioGroup(name, options, current) {
		return options
			.map(function (opt) {
				var id = name + '_' + opt.value;
				return (
					'<label class="mk-ps-filter-opt" for="' +
					esc(id) +
					'">' +
					'<input type="radio" name="' +
					esc(name) +
					'" id="' +
					esc(id) +
					'" value="' +
					esc(opt.value) +
					'"' +
					(current === opt.value ? ' checked' : '') +
					' />' +
					'<span>' +
					esc(opt.label) +
					'</span></label>'
				);
			})
			.join('');
	}

	function renderFilters() {
		var $body = $('#mk-ps-filters-body');
		if (!$body.length) {
			return;
		}
		var groupChecks = groupOptions
			.map(function (g) {
				var id = 'mk_ps_g_' + encodeURIComponent(g).replace(/%/g, '_');
				return (
					'<label class="mk-ps-filter-opt" for="' +
					esc(id) +
					'">' +
					'<input type="checkbox" class="mk-ps-filter-group" id="' +
					esc(id) +
					'" value="' +
					esc(g) +
					'"' +
					(filters.groups[g] ? ' checked' : '') +
					' />' +
					'<span>' +
					esc(g) +
					'</span></label>'
				);
			})
			.join('');
		if (!groupChecks) {
			groupChecks = '<div class="mk-ps-filter-empty">Chưa có nhóm</div>';
		}

		var whOpts = [{ value: '', label: 'Tất cả' }].concat(
			(warehouseOptions || []).map(function (w) {
				return {
					value: String(w.id || w.code || ''),
					label: String(w.name || w.code || w.id || '')
				};
			})
		);

		$body.html(
			'<section class="mk-ps-filter-section" data-section="group">' +
				'<h3 class="mk-ps-filter-section__title">Nhóm hàng</h3>' +
				'<div class="mk-ps-filter-section__list mk-ps-filter-section__list--scroll">' +
				groupChecks +
				'</div></section>' +
				'<section class="mk-ps-filter-section" data-section="stock">' +
				'<h3 class="mk-ps-filter-section__title">Tồn kho</h3>' +
				'<div class="mk-ps-filter-section__list">' +
				radioGroup(
					'mk_ps_stock',
					[
						{ value: 'all', label: 'Tất cả' },
						{ value: 'in', label: 'Còn hàng trong kho' },
						{ value: 'out', label: 'Hết hàng trong kho' },
						{ value: 'below_order', label: 'Tồn dưới khách đặt' }
					],
					filters.stock
				) +
				'</div></section>' +
				'<section class="mk-ps-filter-section" data-section="warehouse">' +
				'<h3 class="mk-ps-filter-section__title">Kho hàng</h3>' +
				'<div class="mk-ps-filter-section__list">' +
				radioGroup('mk_ps_wh', whOpts, filters.warehouse || '') +
				'</div></section>' +
				'<section class="mk-ps-filter-section" data-section="stockout">' +
				'<h3 class="mk-ps-filter-section__title">Dự kiến hết hàng</h3>' +
				'<div class="mk-ps-filter-empty">Sẽ bổ sung theo công thức BA</div></section>' +
				'<section class="mk-ps-filter-section" data-section="created">' +
				'<h3 class="mk-ps-filter-section__title">Thời gian tạo</h3>' +
				'<div class="mk-ps-filter-section__list">' +
				radioGroup(
					'mk_ps_created',
					[
						{ value: 'all', label: 'Toàn thời gian' },
						{ value: 'today', label: 'Hôm nay' },
						{ value: 'week', label: 'Tuần này' },
						{ value: 'month', label: 'Tháng này' },
						{ value: 'range', label: 'Tuỳ chọn' }
					],
					filters.created
				) +
				'<div class="mk-ps-filter-range"' +
				(filters.created === 'range' ? '' : ' hidden') +
				'>' +
				'<label>Từ <input type="date" id="mk-ps-created-from" value="' +
				esc(filters.createdFrom) +
				'" /></label>' +
				'<label>Đến <input type="date" id="mk-ps-created-to" value="' +
				esc(filters.createdTo) +
				'" /></label></div></div></section>'
		);
		updateFilterResetVisibility();
	}

	function updateFilterResetVisibility() {
		$('#mk-ps-filters-reset').prop('hidden', activePanelFilterCount() === 0);
		updateFilterToggleBadge();
	}

	function resetFilters() {
		filters = {
			groups: {},
			stock: 'all',
			warehouse: '',
			created: 'all',
			createdFrom: '',
			createdTo: ''
		};
		searchQuery = '';
		$('#mk-ps-search').val('');
		pageIndex = 1;
		renderFilters();
		renderCatalogPage();
	}

	function setStarUi($btn, starred) {
		var on = !!starred;
		$btn.removeClass('fa fa-star fa-star-o markStar');
		$btn.toggleClass('active', on);
		$btn.attr('data-starred', on ? '1' : '0');
		$btn.attr('aria-pressed', on ? 'true' : 'false');
		$btn.attr('title', on ? 'Bỏ theo dõi' : 'Theo dõi');
		$btn.attr('aria-label', on ? 'Bỏ theo dõi' : 'Theo dõi');
		$btn.text(on ? '★' : '☆');
	}

	function getListViewContainer() {
		return $('#listViewContent');
	}

	function unregisterVtigerRowNavigation($container) {
		if (!$container || !$container.length) {
			return;
		}
		$container.off('click', '.listViewEntries');
		$container.off('click', '.listViewEntries a');
		$container.off('click', '.markStar');
		$container.find('.listViewEntries a').each(function () {
			var $link = $(this);
			var timer = $link.data('timer');
			if (timer) {
				clearTimeout(timer);
				$link.removeData('timer');
			}
		});
	}

	function saveStarToggle($btn) {
		if ($btn.hasClass('processing')) {
			return;
		}
		var $row = $btn.closest('tr.listViewEntries');
		var recordId = $row.data('id') || $row.attr('data-id');
		if (!recordId) {
			return;
		}
		var next = !$btn.hasClass('active');
		$btn.addClass('processing');
		setStarUi($btn, next);
		updateCatalogStar(recordId, next);

		var params = withCsrf({
			module: 'ProductsServices',
			action: 'SaveStar',
			record: recordId,
			value: next ? 1 : 0,
			_timeStampNoChangeMode: true
		});

		function fail() {
			setStarUi($btn, !next);
			updateCatalogStar(recordId, !next);
			$btn.removeClass('processing');
			if (window.app && app.helper && app.helper.showErrorNotification) {
				app.helper.showErrorNotification({ message: 'Không lưu được theo dõi.' });
			}
		}

		function ok() {
			$btn.removeClass('processing');
			if (window.app && app.helper && app.helper.showSuccessNotification) {
				app.helper.showSuccessNotification({
					message: next ? 'Đã theo dõi hàng hoá.' : 'Đã bỏ theo dõi.'
				});
			}
		}

		if (window.app && app.request && typeof app.request.post === 'function') {
			app.request.post({ data: params }).then(function (err, data) {
				if (err || data === false) {
					fail();
					return;
				}
				ok();
			});
		} else {
			$.post('index.php', params)
				.done(function () {
					ok();
				})
				.fail(fail);
		}
	}

	function onStarClickCapture(e) {
		if (!isPsSalesList()) {
			return;
		}
		var target = e.target;
		if (!target || !target.closest) {
			return;
		}
		var btn = target.closest('#listViewContent #listview-table .mk-ps-star');
		if (!btn) {
			var td = target.closest('#listViewContent #listview-table td.mk-col-ps-star');
			if (td) {
				btn = td.querySelector('.mk-ps-star');
			}
		}
		if (!btn) {
			return;
		}

		e.preventDefault();
		e.stopPropagation();
		if (typeof e.stopImmediatePropagation === 'function') {
			e.stopImmediatePropagation();
		}
		saveStarToggle($(btn));
	}

	function updateCatalogStar(recordId, starred) {
		var id = Number(recordId) || 0;
		if (!id) {
			return;
		}
		for (var i = 0; i < catalogItems.length; i++) {
			if (Number(catalogItems[i].id) === id) {
				catalogItems[i].starred = starred ? 1 : 0;
				break;
			}
		}
	}

	function bindStarEvents() {
		if (starEventsBound || document.documentElement.getAttribute('data-mk-ps-star-bound')) {
			return;
		}
		starEventsBound = true;
		document.documentElement.setAttribute('data-mk-ps-star-bound', '1');
		document.addEventListener('click', onStarClickCapture, true);
	}

	function bindFilterEvents() {
		if (filterEventsBound) {
			return;
		}
		filterEventsBound = true;
		$(document)
			.on('change.mkPsFilterGroup', '.mk-ps-filter-group', function () {
				var g = $(this).val();
				if ($(this).is(':checked')) {
					filters.groups[g] = true;
				} else {
					delete filters.groups[g];
				}
				pageIndex = 1;
				renderCatalogPage();
			})
			.on('change.mkPsFilterStock', 'input[name="mk_ps_stock"]', function () {
				filters.stock = $(this).val() || 'all';
				pageIndex = 1;
				renderCatalogPage();
			})
			.on('change.mkPsFilterWh', 'input[name="mk_ps_wh"]', function () {
				filters.warehouse = $(this).val() || '';
				pageIndex = 1;
				renderCatalogPage();
			})
			.on('change.mkPsFilterCreated', 'input[name="mk_ps_created"]', function () {
				filters.created = $(this).val() || 'all';
				$('.mk-ps-filter-range').prop('hidden', filters.created !== 'range');
				pageIndex = 1;
				renderCatalogPage();
			})
			.on('change.mkPsFilterRange', '#mk-ps-created-from, #mk-ps-created-to', function () {
				filters.createdFrom = $('#mk-ps-created-from').val() || '';
				filters.createdTo = $('#mk-ps-created-to').val() || '';
				filters.created = 'range';
				pageIndex = 1;
				renderCatalogPage();
			})
			.on('click.mkPsFilterReset', '#mk-ps-filters-reset', function (e) {
				e.preventDefault();
				resetFilters();
			})
			.on('click.mkPsFilterToggle', '#mk-ps-filters-toggle', function (e) {
				e.preventDefault();
				setFiltersOpen(!filtersOpen);
			});
	}

	function bindSearchEvents() {
		if (searchEventsBound) {
			return;
		}
		searchEventsBound = true;
		$(document)
			.on('input.mkPsClientSearch', '#mk-ps-search', function () {
				var q = $.trim($(this).val());
				$('#mk-ps-search-clear').prop('hidden', !q);
				if (searchTimer) {
					clearTimeout(searchTimer);
				}
				searchTimer = setTimeout(function () {
					applySearch(q);
				}, SEARCH_DEBOUNCE_MS);
			})
			.on('keydown.mkPsClientSearch', '#mk-ps-search', function (e) {
				if (e.key === 'Enter' || e.which === 13) {
					e.preventDefault();
					if (searchTimer) {
						clearTimeout(searchTimer);
						searchTimer = null;
					}
					applySearch($.trim($(this).val()));
				}
			})
			.on('click.mkPsClientSearchClear', '#mk-ps-search-clear', function (e) {
				e.preventDefault();
				$('#mk-ps-search').val('');
				applySearch('');
				$('#mk-ps-search').focus();
			})
			.on('click.mkPsClientPrev', '#mk-ps-prev', function (e) {
				e.preventDefault();
				if (pageIndex > 1) {
					pageIndex -= 1;
					renderCatalogPage();
				}
			})
			.on('click.mkPsClientNext', '#mk-ps-next', function (e) {
				e.preventDefault();
				var totalPages = Math.max(1, Math.ceil(filteredItems().length / PAGE_SIZE) || 1);
				if (pageIndex < totalPages) {
					pageIndex += 1;
					renderCatalogPage();
				}
			})
			.on(
				'click.mkPsClientRow',
				'#listViewContent #listview-table tbody tr.listViewEntries td:not(.listViewRecordActions):not(.mk-col-ps-star)',
				function (e) {
					if ($(e.target).closest('a,button,input,label,.mk-ps-check,.mk-ps-star').length) {
						return;
					}
					var url = $(this).closest('tr').attr('data-recordurl');
					if (url) {
						window.location.href = url;
					}
				}
			);
	}

	function bootClientCatalog() {
		ensureSearchBar();
		bindSearchEvents();
		bindFilterEvents();
		bindStarEvents();
		hideVtigerPaging();
		fetchCatalog()
			.done(function () {
				renderFilters();
				renderCatalogPage();
				setReadyState();
			})
			.fail(function () {
				ensureClientPaginationHost().html(
					'<span class="mk-ps-client-pagination__info">Không tải được danh mục hàng hoá.</span>'
				);
				setReadyState();
			});
	}

	function stripRowActionChrome() {
		$('#listViewContent #listview-table tbody td.listViewRecordActions').each(function () {
			$(this)
				.find('.quickView, .markStar, .inline-save, .more, .dropdown-menu, .mk-ps-qc-chip, .js-mk-ps-needs-qc')
				.remove();
		});
		$('#listViewContent #listview-table thead .listColumnFilter').closest('div').css({ display: 'none' });
		$('#listViewContent tr.searchRow.listViewSearchContainer').hide();
	}

	function enhanceCircularChecks() {
		$('#listViewContent #listview-table')
			.find('input.listViewEntriesMainCheckBox, input.listViewEntriesCheckBox')
			.each(function () {
				var $input = $(this);
				if ($input.closest('.mk-ps-check').length) {
					return;
				}
				$input.addClass('mk-ps-check__input');
				var $ui = $(
					'<span class="mk-ps-check__ui" aria-hidden="true">' +
						'<svg class="mk-ps-check__tick" width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">' +
						'<path d="M1.75 5.2L4.05 7.35L8.25 2.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>' +
						'</svg></span>'
				);
				$input.after($ui);
				$input.add($ui).wrapAll('<label class="mk-ps-check"></label>');
			});
	}

	function destroyFloatTheadArtifacts() {
		var $table = $('#listViewContent #listview-table');
		if (!$table.length) {
			return;
		}
		try {
			if ($.fn.floatThead && $table.data('floatThead-attached')) {
				$table.floatThead('destroy');
			}
		} catch (e) {
			/* ignore */
		}
		$('#listViewContent .floatThead-container').remove();
		var $wrap = $table.closest('.floatThead-wrapper');
		if ($wrap.length && $wrap[0] !== $table.parent()[0]) {
			try {
				$table.unwrap();
			} catch (e2) {
				/* ignore */
			}
		}
	}

	function patchRowClickForStar() {
		if (typeof Vtiger_List_Js === 'undefined' || Vtiger_List_Js.prototype.__mkPsRowClickPatched) {
			return;
		}
		Vtiger_List_Js.prototype.__mkPsRowClickPatched = true;
		var origRegisterRowClick = Vtiger_List_Js.prototype.registerRowClickEvent;
		if (typeof origRegisterRowClick !== 'function') {
			return;
		}
		Vtiger_List_Js.prototype.registerRowClickEvent = function () {
			if (!isPsSalesList()) {
				return origRegisterRowClick.apply(this, arguments);
			}
			var listViewContentDiv = this.getListViewContainer();
			unregisterVtigerRowNavigation(listViewContentDiv);
			listViewContentDiv.on('click', '.listViewEntries a', function (e) {
				var currentAElement = jQuery(e.currentTarget);
				var href = currentAElement.attr('href');
				var target = jQuery(e.target);
				if (!target.hasClass('js-reference-display-value')) {
					if (!currentAElement.data('timer') && typeof href !== 'undefined') {
						currentAElement.data(
							'timer',
							setTimeout(function () {
								window.location = href;
							}, 500)
						);
					}
					e.preventDefault();
				}
				e.stopPropagation();
			});
			// Row → detail: List.js mkPsClientRow handles td clicks; skip core tr handler (star column conflict).
		};
	}

	function patchRegisterStarToggle() {
		if (typeof Vtiger_List_Js === 'undefined' || Vtiger_List_Js.prototype.__mkPsStarTogglePatched) {
			return;
		}
		Vtiger_List_Js.prototype.__mkPsStarTogglePatched = true;
		var origRegisterStarToggle = Vtiger_List_Js.prototype.registerStarToggle;
		if (typeof origRegisterStarToggle !== 'function') {
			return;
		}
		Vtiger_List_Js.prototype.registerStarToggle = function () {
			if (isPsSalesList()) {
				return;
			}
			return origRegisterStarToggle.apply(this, arguments);
		};
	}

	function patchRegisterEventsForStar() {
		if (typeof Vtiger_List_Js === 'undefined' || Vtiger_List_Js.prototype.__mkPsRegisterEventsPatched) {
			return;
		}
		Vtiger_List_Js.prototype.__mkPsRegisterEventsPatched = true;
		var origRegisterEvents = Vtiger_List_Js.prototype.registerEvents;
		if (typeof origRegisterEvents !== 'function') {
			return;
		}
		Vtiger_List_Js.prototype.registerEvents = function () {
			var result = origRegisterEvents.apply(this, arguments);
			if (isPsSalesList()) {
				unregisterVtigerRowNavigation(this.getListViewContainer());
			}
			return result;
		};
	}

	function patchDisableFloatThead() {
		if (typeof Vtiger_List_Js === 'undefined' || Vtiger_List_Js.prototype.__mkPsFloatPatched) {
			return;
		}
		Vtiger_List_Js.prototype.__mkPsFloatPatched = true;
		var origFloat = Vtiger_List_Js.prototype.registerFloatingThead;
		var origReflow = Vtiger_List_Js.prototype.reflowList;
		Vtiger_List_Js.prototype.registerFloatingThead = function () {
			if (isPsSalesList()) {
				destroyFloatTheadArtifacts();
				return;
			}
			return origFloat.apply(this, arguments);
		};
		if (typeof origReflow === 'function') {
			Vtiger_List_Js.prototype.reflowList = function () {
				if (isPsSalesList()) {
					destroyFloatTheadArtifacts();
					return;
				}
				return origReflow.apply(this, arguments);
			};
		}
	}

	function patchListHeadersOnly() {
		if (typeof Vtiger_List_Js === 'undefined' || Vtiger_List_Js.prototype.__mkPsListV2Patched) {
			return;
		}
		Vtiger_List_Js.prototype.__mkPsListV2Patched = true;
		var origParams = Vtiger_List_Js.prototype.getDefaultParams;
		if (typeof origParams === 'function') {
			Vtiger_List_Js.prototype.getDefaultParams = function () {
				var params = origParams.apply(this, arguments);
				if (isPsSalesList()) {
					ensureListHeadersInput();
					params.list_headers = JSON.stringify(CANONICAL_HEADERS);
					delete params.search_params;
				}
				return params;
			};
		}
		var origLoad = Vtiger_List_Js.prototype.loadListViewRecords;
		if (typeof origLoad === 'function') {
			Vtiger_List_Js.prototype.loadListViewRecords = function () {
				if (isPsSalesList() && catalogLoaded) {
					renderCatalogPage();
					return $.Deferred().resolve('').promise();
				}
				return origLoad.apply(this, arguments);
			};
		}
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
				setTimeout(function () {
					afterListLayout();
					if (catalogLoaded) {
						renderCatalogPage();
					}
				}, 0);
			}
		};
	}

	function selectedCheckboxCount() {
		return $('#listViewContent #listview-table tbody input.listViewEntriesCheckBox:checked').length;
	}

	function clearBulkSelection() {
		var $table = $('#listViewContent #listview-table');
		$table.find('input.listViewEntriesCheckBox, input.listViewEntriesMainCheckBox').prop('checked', false);
		$table.find('tbody tr.listViewEntries').removeClass('mk-ps-row-selected');
		renderBulkBar();
	}

	function syncSelectedRowState() {
		$('#listViewContent #listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			$row.toggleClass('mk-ps-row-selected', $row.find('input.listViewEntriesCheckBox').is(':checked'));
		});
	}

	function renderBulkBar() {
		var $bar = $('#mk-ps-bulk');
		if (!$bar.length) {
			var $card = $('.mk-ps-table-card').first();
			if ($card.length) {
				$card.prepend('<div id="mk-ps-bulk" class="mk-ps-bulk-bar" hidden></div>');
				$bar = $('#mk-ps-bulk');
			}
		}
		if (!$bar.length) {
			return;
		}
		syncSelectedRowState();
		var n = selectedCheckboxCount();
		var $actions = $('#listview-actions');
		if (!n) {
			$bar.attr('hidden', true).empty();
			$actions.removeClass('mk-ps-bulk-active');
			document.body.classList.remove('mk-ps-has-bulk');
			return;
		}
		document.body.classList.add('mk-ps-has-bulk');
		$actions.addClass('mk-ps-bulk-active');
		$bar.removeAttr('hidden').html(
			'<div class="mk-ps-bulk-bar__inner">' +
				'<div class="mk-ps-bulk-bar__left">' +
				'<span class="mk-ps-bulk-badge" aria-hidden="true"><svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5.2L4.1 7.2L8 2.8" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' +
				'<span class="mk-ps-bulk-bar__count"><strong>' +
				n +
				'</strong> đã chọn</span></div>' +
				'<div class="mk-ps-bulk-bar__actions">' +
				'<button type="button" class="mk-ps-bulk-btn" data-ps-bulk="export"><span>Xuất file</span></button>' +
				'<button type="button" class="mk-ps-bulk-btn mk-ps-bulk-btn--danger" data-ps-bulk="delete"><span>Xóa</span></button>' +
				'</div>' +
				'<button type="button" class="mk-ps-bulk-clear" data-ps-bulk="clear">Bỏ chọn</button></div>'
		);
	}

	function runBulkExport() {
		var exportUrl = 'index.php?module=ProductsServices&view=Export';
		if (typeof Vtiger_List_Js !== 'undefined' && typeof Vtiger_List_Js.triggerExportAction === 'function') {
			Vtiger_List_Js.triggerExportAction(exportUrl);
		}
	}

	function runBulkDelete() {
		if (!selectedCheckboxCount()) {
			return;
		}
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		var deleteUrl = 'index.php?module=ProductsServices&action=MassDelete';
		if (listInstance && typeof listInstance.performMassDeleteRecords === 'function') {
			listInstance.performMassDeleteRecords(deleteUrl);
			return;
		}
		if (typeof Vtiger_List_Js !== 'undefined' && typeof Vtiger_List_Js.massDeleteRecords === 'function') {
			Vtiger_List_Js.massDeleteRecords(deleteUrl);
		}
	}

	function bindBulkSelectionEvents() {
		if (bulkEventsBound) {
			return;
		}
		bulkEventsBound = true;
		$(document)
			.on(
				'change.mkPsBulk',
				'#listViewContent #listview-table input.listViewEntriesCheckBox',
				function () {
					setTimeout(renderBulkBar, 0);
				}
			)
			.on('change.mkPsBulkMain', '#listViewContent #listview-table input.listViewEntriesMainCheckBox', function () {
				var on = $(this).is(':checked');
				$('#listViewContent #listview-table tbody input.listViewEntriesCheckBox').prop('checked', on);
				setTimeout(renderBulkBar, 0);
			})
			.on('click.mkPsBulkBtn', '#mk-ps-bulk [data-ps-bulk]', function (e) {
				e.preventDefault();
				var action = $(this).attr('data-ps-bulk');
				if (action === 'clear') {
					clearBulkSelection();
				} else if (action === 'export') {
					runBulkExport();
				} else if (action === 'delete') {
					runBulkDelete();
				}
			});
	}

	function setReadyState() {
		document.body.classList.remove('mk-ps-ui-loading');
		document.body.classList.add('mk-ps-ui-ready', 'mk-ps-list-v2', 'mk-ps-ba-list');
		document.documentElement.classList.add('mk-ps-ui-ready', 'mk-ps-list-ready', 'mk-ps-list-v2', 'mk-ps-ba-list');
	}

	function afterListLayout() {
		if (!isPsSalesList()) {
			return;
		}
		try {
			document.body.classList.add('mk-ps-list-v2', 'mk-ps-ba-list');
			document.documentElement.classList.add('mk-ps-list-v2', 'mk-ps-ba-list');
			destroyFloatTheadArtifacts();
			mirrorToolbarClasses();
			ensureSearchBar();
			bindSearchEvents();
			bindFilterEvents();
			bindStarEvents();
			bindBulkSelectionEvents();
			$('#listViewContent #listview-table').addClass('mk-ps-table mk-ps-table-v2 mk-ps-ba-table');
			stripRowActionChrome();
			ensureBaThead();
			enhanceCircularChecks();
			hideVtigerPaging();
			renderBulkBar();
			unregisterVtigerRowNavigation(getListViewContainer());
		} catch (err) {
			if (window.console && console.warn) {
				console.warn('[ProductsServices List] layout error', err);
			}
		}
	}

	function mirrorToolbarClasses() {
		$('#listview-actions').addClass('mk-ps-filter-row');
	}

	function init() {
		if (!isPsSalesList()) {
			return;
		}
		if (window.__mkPsListInitDone) {
			return;
		}
		window.__mkPsListInitDone = true;
		document.body.classList.add('mk-ps-ui-loading', 'mk-ps-list-v2', 'mk-ps-ba-list');
		document.documentElement.classList.add('mk-ps-list-v2', 'mk-ps-ba-list');
		patchDisableFloatThead();
		patchRowClickForStar();
		patchRegisterStarToggle();
		patchRegisterEventsForStar();
		patchListHeadersOnly();
		patchPostLoadListViewRecords();
		ensureListHeadersInput();

		if (!eventsBound && typeof app !== 'undefined' && app.event && app.event.on) {
			eventsBound = true;
			app.event.on('post.listViewFilter.click', function () {
				setTimeout(afterListLayout, 80);
			});
		}

		var run = function () {
			afterListLayout();
			bootClientCatalog();
		};
		if (window.requestAnimationFrame) {
			requestAnimationFrame(run);
		} else {
			setTimeout(run, 0);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
