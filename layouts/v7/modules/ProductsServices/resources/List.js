/**
 * ProductsServices list — Leads-style client search (no Vtiger search_params / URL churn).
 */
(function ($) {
	'use strict';

	var CANONICAL_HEADERS = ['productsservicesname', 'sku', 'product_group', 'item_type', 'price', 'price_tuibao', 'unit'];
	var PAGE_SIZE = 20;
	var SEARCH_DEBOUNCE_MS = 180;

	var catalogItems = [];
	var catalogLoaded = false;
	var catalogLoading = false;
	var searchQuery = '';
	var searchTimer = null;
	var searchEventsBound = false;
	var pageIndex = 1;
	var postLoadPatched = false;
	var eventsBound = false;
	var bulkEventsBound = false;

	var COL_CLASS_BY_FIELD = {
		productsservicesname: 'mk-col-ps-name',
		sku: 'mk-col-ps-sku',
		product_group: 'mk-col-ps-group',
		item_type: 'mk-col-ps-type',
		price: 'mk-col-ps-price',
		price_tuibao: 'mk-col-ps-tuibao',
		unit: 'mk-col-ps-unit'
	};

	var MK_COL_CLASS_NAMES =
		'mk-col-control mk-col-ps-name mk-col-ps-sku mk-col-ps-group mk-col-ps-type mk-col-ps-price mk-col-ps-tuibao mk-col-ps-unit';

	var HEADER_LABELS = {
		productsservicesname: 'Tên sản phẩm',
		sku: 'SKU',
		product_group: 'Nhóm',
		item_type: 'Loại',
		price: 'Giá',
		price_tuibao: 'Giá Tuibao',
		unit: 'Đơn vị'
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
		n = Math.round(Number(n) || 0);
		var neg = n < 0;
		var abs = Math.abs(n);
		var body = String(abs).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
		return (neg ? '-' : '') + 'đ' + body;
	}

	function typeLabel(raw) {
		var t = String(raw || '').toLowerCase();
		if (t.indexOf('service') >= 0 || t.indexOf('dịch vụ') >= 0) {
			return { key: 'service', text: 'Dịch vụ' };
		}
		if (t.indexOf('product') >= 0 || t.indexOf('sản phẩm') >= 0 || t === '') {
			return { key: 'product', text: 'Sản phẩm' };
		}
		return { key: 'other', text: String(raw || '—') };
	}

	/* ------------------------------------------------------------------ */
	/* Catalog (Leads-style)                                              */
	/* ------------------------------------------------------------------ */

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

	function filteredItems() {
		var q = String(searchQuery || '')
			.trim()
			.toLowerCase();
		if (!q) {
			return catalogItems.slice();
		}
		return catalogItems.filter(function (it) {
			var name = String(it.name || '').toLowerCase();
			var sku = String(it.sku || '').toLowerCase();
			var group = String(it.product_group || '').toLowerCase();
			return name.indexOf(q) >= 0 || sku.indexOf(q) >= 0 || group.indexOf(q) >= 0;
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

	function buildRowHtml(it) {
		var id = it.id;
		var typ = typeLabel(it.item_type);
		var typeCls = 'mk-ps-type-pill';
		if (typ.key === 'product') {
			typeCls += ' mk-ps-type-pill--product';
		} else if (typ.key === 'service') {
			typeCls += ' mk-ps-type-pill--service';
		}
		var unit = String(it.unit || '').trim();
		var unitHtml = unit
			? '<span class="mk-ps-unit-chip">' + esc(unit) + '</span>'
			: '—';
		var name = String(it.name || '').trim() || '—';
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
			'<td class="listViewEntryValue mk-col-ps-name" data-name="productsservicesname" data-field-type="string">' +
			'<span class="fieldValue"><span class="value"><span class="mk-marquee" title="' +
			esc(name) +
			'">' +
			esc(name) +
			'</span></span></span></td>' +
			'<td class="listViewEntryValue mk-col-ps-sku" data-name="sku"><span class="fieldValue"><span class="value">' +
			esc(it.sku || '—') +
			'</span></span></td>' +
			'<td class="listViewEntryValue mk-col-ps-group" data-name="product_group"><span class="fieldValue"><span class="value">' +
			esc(it.product_group || '—') +
			'</span></span></td>' +
			'<td class="listViewEntryValue mk-col-ps-type" data-name="item_type"><span class="fieldValue"><span class="value">' +
			'<span class="' +
			typeCls +
			'">' +
			esc(typ.text) +
			'</span></span></span></td>' +
			'<td class="listViewEntryValue mk-col-ps-price" data-name="price"><span class="fieldValue"><span class="value">' +
			esc(formatMoney(it.price)) +
			'</span></span></td>' +
			'<td class="listViewEntryValue mk-col-ps-tuibao" data-name="price_tuibao"><span class="fieldValue"><span class="value">' +
			esc(formatMoney(it.price_tuibao)) +
			'</span></span></td>' +
			'<td class="listViewEntryValue mk-col-ps-unit" data-name="unit"><span class="fieldValue"><span class="value">' +
			unitHtml +
			'</span></span></td>' +
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

	function renderCatalogPage() {
		var $table = $('#listViewContent #listview-table');
		if (!$table.length) {
			return;
		}
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
		// Drop Vtiger search row clones that may linger in tbody
		$tbody.find('tr.searchRow').remove();
		if (!pageRows.length) {
			$tbody.html(
				'<tr class="mk-ps-empty-row"><td colspan="8" class="mk-ps-empty-cell">Không tìm thấy mặt hàng phù hợp.</td></tr>'
			);
		} else {
			$tbody.html(pageRows.map(buildRowHtml).join(''));
		}
		hideVtigerPaging();
		renderClientPagination(rows.length);
		assignColumnClasses();
		applyAlignedColgroup();
		enhanceCircularChecks();
		renderBulkBar();
	}

	function applySearch(query) {
		searchQuery = String(query || '').trim();
		pageIndex = 1;
		$('#mk-ps-search-clear').prop('hidden', !searchQuery);
		if (!catalogLoaded) {
			fetchCatalog().done(function () {
				renderCatalogPage();
			});
			return;
		}
		renderCatalogPage();
	}

	function ensureSearchBar() {
		// Strip Shared / Vtiger search widgets
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
		if ($('#mk-ps-search').length) {
			if (!$start.find('#mk-ps-search').length) {
				var $bar = $('#mk-ps-search').closest('.mk-ps-search');
				if ($bar.length) {
					$start.prepend($bar);
				}
			}
			$('#mk-ps-search').val(searchQuery);
			$('#mk-ps-search-clear').prop('hidden', !searchQuery);
			return;
		}
		$start.prepend(
			'<div class="mk-ps-search" role="search">' +
				'<span class="mk-ps-search__ic" aria-hidden="true"><i class="fa fa-search"></i></span>' +
				'<input id="mk-ps-search" class="mk-ps-search__input" type="search" ' +
				'placeholder="Tìm theo tên hoặc SKU…" autocomplete="off" spellcheck="false" />' +
				'<button type="button" class="mk-ps-search__clear" id="mk-ps-search-clear" aria-label="Xóa tìm kiếm" hidden>' +
				'<i class="fa fa-times" aria-hidden="true"></i>' +
				'</button></div>'
		);
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
			.on('click.mkPsClientRow', '#listViewContent #listview-table tbody tr.listViewEntries td:not(.listViewRecordActions)', function (e) {
				if ($(e.target).closest('a,button,input,label,.mk-ps-check').length) {
					return;
				}
				var url = $(this).closest('tr').attr('data-recordurl');
				if (url) {
					window.location.href = url;
				}
			});
	}

	function bootClientCatalog() {
		ensureSearchBar();
		bindSearchEvents();
		hideVtigerPaging();
		fetchCatalog()
			.done(function () {
				renderCatalogPage();
			})
			.fail(function () {
				ensureClientPaginationHost().html(
					'<span class="mk-ps-client-pagination__info">Không tải được danh mục hàng hoá.</span>'
				);
			});
	}

	/* ------------------------------------------------------------------ */
	/* Layout helpers (existing table chrome)                             */
	/* ------------------------------------------------------------------ */

	function fieldFromHeaderTh($th) {
		var $a = $th.find('a.listViewContentHeaderValues').first();
		return $a.length ? $a.data('columnname') || $a.attr('data-columnname') || '' : '';
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
			if ($th.find('.listViewEntriesMainCheckBox, .table-actions, .mk-ps-check').length) {
				$th.addClass('mk-col-control');
			}
		});
		$table.find('tbody tr.listViewEntries').each(function () {
			$(this)
				.children('td')
				.each(function () {
					var $td = $(this);
					var field = $td.data('name') || $td.attr('data-name');
					if (field && COL_CLASS_BY_FIELD[field]) {
						$td.addClass(COL_CLASS_BY_FIELD[field]);
					}
					if ($td.hasClass('listViewRecordActions')) {
						$td.addClass('mk-col-control');
					}
				});
		});
	}

	function applyAlignedColgroup() {
		var $table = $('#listViewContent #listview-table');
		if (!$table.length) {
			return;
		}
		var $headerCells = $table.find('thead tr.listViewContentHeader th');
		$table.find('colgroup').remove();
		var $colgroup = $('<colgroup>');
		$headerCells.each(function () {
			var $th = $(this);
			var field = fieldFromHeaderTh($th);
			var $col = $('<col>');
			if ($th.hasClass('mk-col-control') || $th.find('.listViewEntriesMainCheckBox, .mk-ps-check').length) {
				$col.css({ width: '52px' });
			} else if (field === 'sku' || $th.hasClass('mk-col-ps-sku')) {
				$col.css({ width: '12%' });
			} else if (field === 'item_type' || $th.hasClass('mk-col-ps-type')) {
				$col.css({ width: '12%' });
			} else if (field === 'price' || $th.hasClass('mk-col-ps-price')) {
				$col.css({ width: '14%' });
			} else if (field === 'unit' || $th.hasClass('mk-col-ps-unit')) {
				$col.css({ width: '10%' });
			} else if (field === 'productsservicesname' || $th.hasClass('mk-col-ps-name')) {
				$col.css({ width: '28%' });
			} else {
				$col.css({ width: 'auto' });
			}
			$colgroup.append($col);
		});
		$table.prepend($colgroup);
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

	function ensureHeaderTitles() {
		$('#listViewContent #listview-table thead tr.listViewContentHeader th').each(function () {
			var $th = $(this);
			var $a = $th.find('a.listViewContentHeaderValues').first();
			if (!$a.length) {
				return;
			}
			var field = $a.data('columnname') || $a.attr('data-columnname') || '';
			var label = HEADER_LABELS[field];
			if (!label) {
				return;
			}
			var $icons = $a.children('i').detach();
			$a.empty();
			if ($icons.length) {
				$a.append($icons);
			}
			$a.append(document.createTextNode('\u00a0' + label + '\u00a0'));
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
		// Block stock list reload from wiping client catalog (Next/Prev/searchVtiger)
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
				'</strong> selected</span></div>' +
				'<div class="mk-ps-bulk-bar__actions">' +
				'<button type="button" class="mk-ps-bulk-btn" data-ps-bulk="export"><span>Export</span></button>' +
				'<button type="button" class="mk-ps-bulk-btn mk-ps-bulk-btn--danger" data-ps-bulk="delete"><span>Xóa</span></button>' +
				'</div>' +
				'<button type="button" class="mk-ps-bulk-clear" data-ps-bulk="clear">Clear</button></div>'
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
		document.body.classList.add('mk-ps-ui-ready', 'mk-ps-list-v2');
		document.documentElement.classList.add('mk-ps-ui-ready', 'mk-ps-list-ready', 'mk-ps-list-v2');
	}

	function afterListLayout() {
		if (!isPsSalesList()) {
			return;
		}
		try {
			document.body.classList.add('mk-ps-list-v2');
			document.documentElement.classList.add('mk-ps-list-v2');
			destroyFloatTheadArtifacts();
			mirrorToolbarClasses();
			ensureSearchBar();
			bindSearchEvents();
			bindBulkSelectionEvents();
			$('#listViewContent #listview-table').addClass('mk-ps-table mk-ps-table-v2');
			assignColumnClasses();
			stripRowActionChrome();
			enhanceCircularChecks();
			ensureHeaderTitles();
			applyAlignedColgroup();
			hideVtigerPaging();
			renderBulkBar();
		} catch (err) {
			if (window.console && console.warn) {
				console.warn('[ProductsServices List] layout error', err);
			}
		} finally {
			setReadyState();
		}
	}

	function mirrorToolbarClasses() {
		$('#listview-actions').addClass('mk-ps-filter-row');
	}

	function init() {
		if (!isPsSalesList()) {
			return;
		}
		document.body.classList.add('mk-ps-ui-loading', 'mk-ps-list-v2');
		document.documentElement.classList.add('mk-ps-list-v2');
		patchDisableFloatThead();
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
		setTimeout(setReadyState, 1800);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
