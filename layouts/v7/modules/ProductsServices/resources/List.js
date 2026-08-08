/**
 * ProductsServices list v2 — aligned checkbox + tên + loại + giá + NCC + đơn vị
 */
(function ($) {
	'use strict';

	var CANONICAL_HEADERS = ['productsservicesname', 'sku', 'product_group', 'item_type', 'price', 'price_tuibao', 'unit'];
	var GLOBAL_SEARCH_FIELDS = ['productsservicesname', 'sku'];
	var GLOBAL_SEARCH_DEBOUNCE_MS = 600;
	var globalSearchTimer = null;
	var lastGlobalSearchPayload = '';
	var liveGlobalSearchQuery = '';
	var globalSearchBound = false;
	var postLoadPatched = false;
	var eventsBound = false;

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

	function ensureListHeadersInput() {
		var $input = $('#listViewContent input[name="list_headers"]').first();
		if ($input.length) {
			$input.val(JSON.stringify(CANONICAL_HEADERS));
		}
	}

	function patchListAjaxParams() {
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
				}
				return params;
			};
		}
		var origLoad = Vtiger_List_Js.prototype.loadListViewRecords;
		if (typeof origLoad === 'function') {
			Vtiger_List_Js.prototype.loadListViewRecords = function (urlParams) {
				if (isPsSalesList()) {
					urlParams = urlParams || {};
					urlParams.list_headers = JSON.stringify(CANONICAL_HEADERS);
				}
				return origLoad.call(this, urlParams);
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
				setTimeout(afterListLayout, 0);
			}
		};
	}

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
			} else if (field === 'supplier' || $th.hasClass('mk-col-ps-supplier')) {
				$col.css({ width: '16%' });
			} else if (field === 'unit' || $th.hasClass('mk-col-ps-unit')) {
				$col.css({ width: '10%' });
			} else if (field === 'productsservicesname' || $th.hasClass('mk-col-ps-name')) {
				$col.css({ width: '30%' });
			} else {
				$col.css({ width: 'auto' });
			}
			$colgroup.append($col);
		});
		$table.prepend($colgroup);
		/* Force matching inline widths on th + first row td so browser cannot desync */
		syncHeaderBodyWidths($table, $headerCells);
	}

	function syncHeaderBodyWidths($table, $headerCells) {
		var widths = [];
		$headerCells.each(function (i) {
			var $th = $(this);
			var field = fieldFromHeaderTh($th);
			var w;
			if ($th.hasClass('mk-col-control') || $th.find('.listViewEntriesMainCheckBox, .mk-ps-check').length) {
				w = '52px';
			} else if (field === 'sku' || $th.hasClass('mk-col-ps-sku')) {
				w = '12%';
			} else if (field === 'item_type' || $th.hasClass('mk-col-ps-type')) {
				w = '12%';
			} else if (field === 'price' || $th.hasClass('mk-col-ps-price')) {
				w = '14%';
			} else if (field === 'supplier' || $th.hasClass('mk-col-ps-supplier')) {
				w = '16%';
			} else if (field === 'unit' || $th.hasClass('mk-col-ps-unit')) {
				w = '10%';
			} else if (field === 'productsservicesname' || $th.hasClass('mk-col-ps-name')) {
				w = '28%';
			} else {
				w = '';
			}
			widths[i] = w;
			if (w) {
				$th.css({ width: w, minWidth: w === '52px' ? '52px' : '', maxWidth: w === '52px' ? '52px' : '' });
			}
		});
		$table.find('tbody tr.listViewEntries').each(function () {
			$(this)
				.children('td')
				.each(function (i) {
					if (widths[i]) {
						$(this).css({
							width: widths[i],
							minWidth: widths[i] === '52px' ? '52px' : '',
							maxWidth: widths[i] === '52px' ? '52px' : ''
						});
					}
				});
		});
	}

	var HEADER_LABELS = {
		productsservicesname: 'Tên sản phẩm',
		sku: 'SKU',
		product_group: 'Nhóm',
		item_type: 'Loại',
		price: 'Giá',
		price_tuibao: 'Giá Tuibao',
		unit: 'Đơn vị'
	};

	function stripRowActionChrome() {
		$('#listViewContent #listview-table tbody td.listViewRecordActions').each(function () {
			var $td = $(this);
			// Select checkbox only (hình 2) — no QC chip / ⋮ next to select
			$td.find('.quickView, .markStar, .inline-save, .more, .dropdown-menu, .mk-ps-qc-chip, .js-mk-ps-needs-qc').remove();
		});
		$('#listViewContent #listview-table thead .listColumnFilter').closest('div').css({
			display: 'none'
		});
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
						'</svg>' +
					'</span>'
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
		$table.find('> thead, thead tr.listViewContentHeader, thead tr.listViewContentHeader th').css({
			display: '',
			visibility: '',
			height: '',
			opacity: '',
			position: ''
		});
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

	function autoLoadTotalCount() {
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (!listInstance || typeof listInstance.totalNumOfRecords !== 'function') {
			return;
		}
		var $total = $('#listViewContent .totalNumberOfRecords').first();
		if (!$total.length || $total.find('.mk-so-total-count, .mk-ps-total-count').length) {
			return;
		}
		listInstance.totalNumOfRecords($total);
	}

	function normalizeTypeKey(text) {
		var t = String(text || '').toLowerCase();
		if (t.indexOf('service') >= 0 || t.indexOf('dịch vụ') >= 0) {
			return 'service';
		}
		if (t.indexOf('product') >= 0 || t.indexOf('sản phẩm') >= 0) {
			return 'product';
		}
		return 'other';
	}

	function enhanceTypePills(context) {
		$(context)
			.find('td[data-name="item_type"] .value')
			.each(function () {
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
					if (text.toLowerCase() === 'product') {
						text = 'Sản phẩm';
					}
				} else if (key === 'service') {
					cls += ' mk-ps-type-pill--service';
					if (text.toLowerCase() === 'service') {
						text = 'Dịch vụ';
					}
				}
				$value.empty().append($('<span>', { class: cls, text: text }));
			});
	}

	function enhanceUnitCells(context) {
		$(context)
			.find('td[data-name="unit"] .value')
			.each(function () {
				var $value = $(this);
				if ($value.find('.mk-ps-unit-chip').length) {
					return;
				}
				var text = $.trim($value.text());
				if (!text || text === '-') {
					return;
				}
				$value.empty().append($('<span>', { class: 'mk-ps-unit-chip', text: text }));
			});
	}

	function fixListScrollContainer() {
		var $tc = $('#listViewContent #table-content');
		if (!$tc.length) {
			return;
		}
		$tc.css({ width: '100%', overflowX: 'auto', overflowY: 'auto', height: 'auto', maxHeight: '' });
	}

	function mirrorToolbarClasses() {
		$('#listview-actions').addClass('mk-ps-filter-row');
	}

	function localizeToolbar() {
		$('.mk-ps-page-numbers__prefix, .mk-so-page-numbers__prefix').text('Hiển thị ');
		$('.mk-ps-page-numbers__suffix, .mk-so-page-numbers__suffix').text(' mặt hàng');
	}

	function enhancePaginationChrome() {
		var $scope = $('#listViewContent');
		if (!$scope.length) {
			$scope = $(document);
		}
		$scope.find('.mk-so-page-btn--prev .mk-so-page-btn__label').text('Trước');
		$scope.find('.mk-so-page-btn--next .mk-so-page-btn__label').text('Sau');
		$scope.find('.mk-so-page-current__label').text('Trang');
		$scope.find('#PreviousPageButton').attr({ title: 'Trang trước', 'aria-label': 'Trang trước' });
		$scope.find('#NextPageButton').attr({ title: 'Trang sau', 'aria-label': 'Trang sau' });
		$scope.find('#PageJump').attr({ title: 'Nhảy tới trang', 'aria-label': 'Nhảy tới trang' });

		var pageNum = $.trim($scope.find('#pageNumber').val() || $scope.find('.mk-so-page-current__num').first().text() || '1');
		$scope.find('.mk-so-page-current__num').text(pageNum);
		$scope.find('.mk-so-pagejump-cur').text(pageNum);

		var totalText = $.trim($scope.find('#totalPageCount').first().text());
		if (totalText) {
			$scope.find('.mk-so-pagejump-total').text(totalText);
		}
	}

	/** Keep dropdown total label in sync when Vtiger fills #totalPageCount. */
	function watchPageCountSync() {
		if (watchPageCountSync.bound) {
			return;
		}
		watchPageCountSync.bound = true;
		var sync = function () {
			var $total = $('#listViewContent #totalPageCount').first();
			if (!$total.length) {
				return;
			}
			var t = $.trim($total.text());
			if (t) {
				$('#listViewContent .mk-so-pagejump-total').text(t);
			}
		};
		$(document)
			.on('DOMSubtreeModified.mkPsPager', '#listViewContent #totalPageCount', sync)
			.on('click.mkPsPagerJump', '#PageJump', function () {
				setTimeout(sync, 50);
				setTimeout(sync, 400);
			});
		// MutationObserver is preferred over DOMSubtreeModified where available
		if (typeof MutationObserver === 'function') {
			$(document).off('DOMSubtreeModified.mkPsPager');
			var start = function () {
				var el = document.querySelector('#listViewContent #totalPageCount');
				if (!el || el.__mkPsPagerObs) {
					return;
				}
				el.__mkPsPagerObs = true;
				new MutationObserver(sync).observe(el, { characterData: true, childList: true, subtree: true });
			};
			start();
			setTimeout(start, 500);
			setTimeout(start, 1500);
		}
	}

	function injectGlobalQuickSearch() {
		var $start = $('#listview-actions .mk-ps-filter-row__start, #listview-actions .mk-so-filter-row__start').first();
		if (!$start.length || $start.find('#mk-ps-global-search').length) {
			return;
		}
		$start.prepend(
			'<div class="mk-ps-global-search" role="search">' +
				'<span class="mk-ps-global-search__ic"><i class="fa fa-search"></i></span>' +
				'<input id="mk-ps-global-search" type="search" placeholder="Tìm tên hàng, SKU…" autocomplete="off" />' +
				'<button type="button" id="mk-ps-global-search-clear" aria-label="Xóa" hidden><i class="fa fa-times"></i></button>' +
			'</div>'
		);
	}

	function buildGlobalSearchParams(query) {
		var conditions = [];
		var i;
		for (i = 0; i < GLOBAL_SEARCH_FIELDS.length; i++) {
			conditions.push([GLOBAL_SEARCH_FIELDS[i], 'c', query]);
		}
		return conditions.length === 1 ? [conditions] : [[], conditions];
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
		listInstance.loadListViewRecords({ page: '1', search_params: payload, nolistcache: '1' });
	}

	function bindGlobalQuickSearchEvents() {
		if (globalSearchBound) {
			return;
		}
		globalSearchBound = true;
		$(document)
			.on('input.mkPsGlobalSearch', '#mk-ps-global-search', function () {
				liveGlobalSearchQuery = $.trim($(this).val());
				$('#mk-ps-global-search-clear').prop('hidden', !liveGlobalSearchQuery);
				if (globalSearchTimer) {
					clearTimeout(globalSearchTimer);
				}
				globalSearchTimer = setTimeout(function () {
					runGlobalQuickSearch();
				}, GLOBAL_SEARCH_DEBOUNCE_MS);
			})
			.on('click.mkPsGlobalSearchClear', '#mk-ps-global-search-clear', function (e) {
				e.preventDefault();
				liveGlobalSearchQuery = '';
				lastGlobalSearchPayload = '';
				$('#mk-ps-global-search').val('');
				$('#mk-ps-global-search-clear').prop('hidden', true);
				runGlobalQuickSearch('');
			});
	}

	var bulkEventsBound = false;

	function selectedCheckboxCount() {
		return $('#listViewContent #listview-table tbody input.listViewEntriesCheckBox:checked').length;
	}

	function clearBulkSelection() {
		var $table = $('#listViewContent #listview-table');
		$table.find('input.listViewEntriesCheckBox, input.listViewEntriesMainCheckBox').prop('checked', false);
		$table.find('tbody tr.listViewEntries').removeClass('mk-ps-row-selected');
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (listInstance && listInstance.clearList) {
			try {
				listInstance.clearList();
			} catch (e) {
				/* ignore */
			}
		}
		renderBulkBar();
	}

	function syncSelectedRowState() {
		$('#listViewContent #listview-table tbody tr.listViewEntries').each(function () {
			var $row = $(this);
			var on = $row.find('input.listViewEntriesCheckBox').is(':checked');
			$row.toggleClass('mk-ps-row-selected', on);
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
					'<span class="mk-ps-bulk-badge" aria-hidden="true">' +
						'<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">' +
							'<path d="M2 5.2L4.1 7.2L8 2.8" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>' +
						'</svg>' +
					'</span>' +
					'<span class="mk-ps-bulk-bar__count"><strong>' + n + '</strong> selected</span>' +
				'</div>' +
				'<div class="mk-ps-bulk-bar__actions">' +
					'<button type="button" class="mk-ps-bulk-btn" data-ps-bulk="export">' +
						'<span class="mk-ps-bulk-btn__ic" aria-hidden="true">' +
							'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
								'<path d="M12 3v12m0 0l4-4m-4 4l-4-4M5 21h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>' +
							'</svg>' +
						'</span><span>Export</span>' +
					'</button>' +
					'<button type="button" class="mk-ps-bulk-btn mk-ps-bulk-btn--danger" data-ps-bulk="delete">' +
						'<span class="mk-ps-bulk-btn__ic" aria-hidden="true">' +
							'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
								'<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>' +
							'</svg>' +
						'</span><span>Xóa</span>' +
					'</button>' +
				'</div>' +
				'<button type="button" class="mk-ps-bulk-clear" data-ps-bulk="clear">Clear</button>' +
			'</div>'
		);
	}

	function runBulkExport() {
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		if (!listInstance) {
			return;
		}
		var exportUrl = 'index.php?module=ProductsServices&view=Export';
		if (typeof Vtiger_List_Js.triggerExportAction === 'function') {
			Vtiger_List_Js.triggerExportAction(exportUrl);
			return;
		}
		if (typeof listInstance.performExportAction === 'function') {
			listInstance.performExportAction(exportUrl);
		}
	}

	function runBulkDelete() {
		var n = selectedCheckboxCount();
		if (!n) {
			return;
		}
		var listInstance = Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
		var deleteUrl = 'index.php?module=ProductsServices&action=MassDelete';
		if (listInstance && typeof listInstance.performMassDeleteRecords === 'function') {
			listInstance.performMassDeleteRecords(deleteUrl);
			return;
		}
		if (typeof Vtiger_List_Js.massDeleteRecords === 'function') {
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
				'#listViewContent #listview-table input.listViewEntriesCheckBox, #listViewContent #listview-table input.listViewEntriesMainCheckBox',
				function () {
					setTimeout(renderBulkBar, 0);
				}
			)
			.on('click.mkPsBulkBtn', '#mk-ps-bulk [data-ps-bulk]', function (e) {
				e.preventDefault();
				e.stopPropagation();
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

	function relocatePagination() {
		var $scope = $('#listViewContent');
		if (!$scope.length) {
			return;
		}
		var $footers = $scope.find('.mk-ps-filter-row__footer, .mk-so-filter-row__footer');
		if (!$footers.length) {
			return;
		}
		// Prefer the live controls inside toolbar, then move once below the table.
		var $footer = $scope.find('#listview-actions .mk-ps-filter-row__footer, #listview-actions .mk-so-filter-row__footer').first();
		if (!$footer.length) {
			$footer = $footers.first();
		}
		$footers.not($footer).remove();

		var $anchor = $scope.find('#table-content').first();
		if (!$anchor.length) {
			$anchor = $scope.find('#listview-table').first();
		}
		if (!$anchor.length) {
			return;
		}
		if ($anchor.next('.mk-ps-filter-row__footer, .mk-so-filter-row__footer')[0] === $footer[0]) {
			return;
		}
		$footer.detach().insertAfter($anchor);
	}

	function setReadyState() {
		document.body.classList.remove('mk-ps-ui-loading');
		document.body.classList.add('mk-ps-ui-ready', 'mk-ps-list-v2');
		document.documentElement.classList.add('mk-ps-ui-ready', 'mk-ps-list-ready', 'mk-ps-list-v2');
	}

	function bindNeedsQcToggle() {
		if (typeof jQuery === 'undefined') {
			return;
		}
		var $ = jQuery;
		$(document)
			.off('click.mkPsNeedsQc', '.js-mk-ps-needs-qc')
			.on('click.mkPsNeedsQc', '.js-mk-ps-needs-qc', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $btn = $(this);
				if ($btn.data('mkSaving')) {
					return;
				}
				var recordId = parseInt($btn.attr('data-id'), 10) || 0;
				if (!recordId) {
					return;
				}
				var current = $btn.attr('data-needs-qc') === '1' ? 1 : 0;
				var next = current ? 0 : 1;
				var labelOn = 'Đang bật: Cần QC';
				var labelOff = 'Cần QC';
				$btn.data('mkSaving', true);
				var finishUi = function (on) {
					$btn.attr('data-needs-qc', on ? '1' : '0');
					$btn.attr('aria-pressed', on ? 'true' : 'false');
					$btn.toggleClass('is-on', !!on);
					$btn.find('.js-mk-ps-needs-qc-label').text(on ? labelOn : labelOff);
					$btn.data('mkSaving', false);
				};
				var params = {
					module: 'ProductsServices',
					action: 'SaveAjax',
					record: recordId,
					field: 'needs_qc',
					value: next
				};
				if (typeof app !== 'undefined' && app.request && app.request.post) {
					app.request.post({ data: params }).then(function (err) {
						if (err) {
							$btn.data('mkSaving', false);
							if (typeof app !== 'undefined' && app.helper && app.helper.showErrorNotification) {
								app.helper.showErrorNotification({ message: err.message || String(err) });
							}
							return;
						}
						finishUi(!!next);
					});
					return;
				}
				$.post('index.php', params)
					.done(function () {
						finishUi(!!next);
					})
					.fail(function () {
						$btn.data('mkSaving', false);
					});
			});
	}

	function afterListLayout() {
		if (!isPsSalesList()) {
			return;
		}
		try {
			document.body.classList.add('mk-ps-list-v2');
			document.documentElement.classList.add('mk-ps-list-v2');
			ensureListHeadersInput();
			destroyFloatTheadArtifacts();
			mirrorToolbarClasses();
			localizeToolbar();
			injectGlobalQuickSearch();
			bindGlobalQuickSearchEvents();
			bindBulkSelectionEvents();
			bindNeedsQcToggle();
			relocatePagination();
			enhancePaginationChrome();
			watchPageCountSync();
			$('#listViewContent #listview-table').addClass('mk-ps-table mk-ps-table-v2');
			assignColumnClasses();
			stripRowActionChrome();
			enhanceCircularChecks();
			ensureHeaderTitles();
			applyAlignedColgroup();
			enhanceTypePills(document);
			enhanceUnitCells(document);
			fixListScrollContainer();
			autoLoadTotalCount();
			/* After total-count AJAX fills #totalPageCount */
			setTimeout(enhancePaginationChrome, 600);
			setTimeout(enhancePaginationChrome, 1600);
			renderBulkBar();
		} catch (err) {
			if (window.console && console.warn) {
				console.warn('[ProductsServices List] layout error', err);
			}
		} finally {
			setReadyState();
		}
	}

	function init() {
		if (!isPsSalesList()) {
			return;
		}
		document.body.classList.add('mk-ps-ui-loading', 'mk-ps-list-v2');
		document.documentElement.classList.add('mk-ps-list-v2');
		patchDisableFloatThead();
		patchListAjaxParams();
		patchPostLoadListViewRecords();
		ensureListHeadersInput();

		if (!eventsBound && typeof app !== 'undefined' && app.event && app.event.on) {
			eventsBound = true;
			app.event.on('post.listViewFilter.click', function () {
				setTimeout(afterListLayout, 80);
			});
			app.event.on('post.listViewSort.click', function () {
				setTimeout(afterListLayout, 80);
			});
		}

		var runLayout = function () {
			afterListLayout();
		};
		if (window.requestAnimationFrame) {
			requestAnimationFrame(runLayout);
		} else {
			setTimeout(runLayout, 0);
		}
		/* Safety: never leave list hidden if a prior step threw */
		setTimeout(setReadyState, 1800);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
