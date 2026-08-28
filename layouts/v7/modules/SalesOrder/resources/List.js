/**
 * SalesOrder list (SALES): enhance native Vtiger DOM; AJAX only swaps table body (keeps shell).
 */
(function ($) {
	'use strict';

	var STATUS_FIELD_CANDIDATES = ['sostatus', 'salesorder_status', 'invoicestatus', 'status'];
	var placeListContentsPatched = false;

	function listConfig() {
		return window.__mkSoSalesListConfig || {};
	}

	function statusCandidates() {
		var cfg = listConfig();
		return cfg.statusFieldCandidates || STATUS_FIELD_CANDIDATES;
	}

	function isSalesOrderSalesList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'SalesOrder' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SALES') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'SalesOrder' && params.get('view') === 'List' && params.get('app') === 'SALES';
	}

	function getListViewContainer() {
		return $('#listViewContent');
	}

	function getPrimaryTable() {
		var $lv = getListViewContainer();
		var $table = $lv.find('.mk-so-table-card #listview-table').first();
		if ($table.length) {
			return $table;
		}
		return $lv.find('#listview-table').first();
	}

	function isQuoteColumnFieldName(fieldName) {
		if (!fieldName) {
			return false;
		}
		return String(fieldName).toLowerCase().indexOf('quote') >= 0;
	}

	function resolveStatusField($table) {
		var cfg = listConfig();
		var preferred = cfg.preferredStatusField;
		var candidates = statusCandidates();
		var i;

		if (preferred && $table.find('thead a[data-columnname="' + preferred + '"], td[data-name="' + preferred + '"]').length) {
			return preferred;
		}
		for (i = 0; i < candidates.length; i++) {
			if ($table.find('thead a[data-columnname="' + candidates[i] + '"], td[data-name="' + candidates[i] + '"]').length) {
				return candidates[i];
			}
		}
		return null;
	}

	function hideQuoteColumns($table) {
		$table.find('thead a[data-columnname]').each(function () {
			var name = $(this).attr('data-columnname');
			if (isQuoteColumnFieldName(name)) {
				$(this).closest('th').addClass('mk-so-list-col-hidden');
			}
		});
		$table.find('tr.searchRow th').each(function () {
			var $th = $(this);
			var name = $th.attr('data-columnname');
			if (!name) {
				var $input = $th.find('input, select').first();
				name = $input.length ? $input.attr('name') : '';
				if (name) {
					name = String(name).replace(/\[\]$/, '');
				}
			}
			if (isQuoteColumnFieldName(name)) {
				$th.addClass('mk-so-list-col-hidden');
			}
		});
		$table.find('tbody td[data-name]').each(function () {
			if (isQuoteColumnFieldName($(this).attr('data-name'))) {
				$(this).addClass('mk-so-list-col-hidden');
			}
		});
	}

	function decodeHtmlEntities(text) {
		var value = String(text || '');
		if (!value) {
			return '';
		}
		var el = document.createElement('textarea');
		var prev = null;
		var guard = 0;
		while (value !== prev && guard < 6) {
			prev = value;
			if (!/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(value)) {
				break;
			}
			el.innerHTML = value;
			value = el.value;
			guard += 1;
		}
		return value;
	}

	var TEXT_DECODE_FIELDS = ['accountname', 'account_id', 'subject', 'contact_id'];

	function fixEncodedTextCells($table) {
		TEXT_DECODE_FIELDS.forEach(function (fieldName) {
			$table.find('tbody td[data-name="' + fieldName + '"]').each(function () {
				var $td = $(this);
				if ($td.hasClass('mk-so-list-col-hidden')) {
					return;
				}
				var $targets = $td.find('.value, a.listViewContentHeaderValues, a');
				if (!$targets.length) {
					$targets = $td;
				}
				$targets.each(function () {
					var $node = $(this);
					if ($node.data('mkDecoded')) {
						return;
					}
					var raw = $.trim($node.text());
					if (!raw || !/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(raw)) {
						return;
					}
					var decoded = decodeHtmlEntities(raw);
					if (decoded !== raw) {
						$node.text(decoded);
						$node.data('mkDecoded', 1);
					}
				});
			});
		});
	}

	function normalizeStatusTone(text) {
		var t = String(text || '').toLowerCase();
		if (!t || t === '--') {
			return 'neutral';
		}
		if (t.indexOf('cancel') >= 0 || t.indexOf('reject') >= 0 || t.indexOf('failed') >= 0) {
			return 'danger';
		}
		if (t.indexOf('deliver') >= 0 || t.indexOf('complete') >= 0 || t.indexOf('approved') >= 0 || t.indexOf('paid') >= 0) {
			return 'success';
		}
		if (t.indexOf('pending') >= 0 || t.indexOf('hold') >= 0 || t.indexOf('draft') >= 0 || t.indexOf('created') >= 0) {
			return 'warning';
		}
		return 'neutral';
	}

	function enhanceStatusPills($table, statusField) {
		$table.find('tbody td[data-name="' + statusField + '"]').each(function () {
			var $td = $(this);
			if ($td.hasClass('mk-so-list-col-hidden')) {
				return;
			}
			$td.addClass('mk-so-status-cell');
			var $value = $td.find('.value').first();
			if (!$value.length || $value.find('.mk-so-status-pill').length) {
				return;
			}
			if ($value.find('.picklist-color').length) {
				$value.find('.picklist-color').addClass('mk-so-status-pill mk-so-status-pill--' + normalizeStatusTone($value.text()));
				return;
			}
			var text = $.trim($value.text()) || '--';
			$value.empty().append($('<span>', {
				'class': 'mk-so-status-pill mk-so-status-pill--' + normalizeStatusTone(text),
				text: text
			}));
		});
	}

	function applyTableClasses($table) {
		$table.addClass('mk-so-table mk-so-table-layout');
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

	/**
	 * AJAX: chỉ thay table + toolbar trong shell hiện có — không thay header / không nhồi thêm mk-so-page.
	 */
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

	function dedupeListDom() {
		var $lv = getListViewContainer();
		if (!$lv.length) {
			return;
		}

		$lv.find('.mk-so-page.mk-so-list-sales-root').slice(1).remove();
		$lv.find('.mk-so-header').slice(1).remove();

		var $tables = $lv.find('#listview-table');
		if ($tables.length > 1) {
			var $keep = getPrimaryTable();
			$tables.each(function () {
				if (this !== $keep[0]) {
					$(this).closest('#table-content').remove();
				}
			});
		}

		$lv.find('.essentials-toggle, .module-action-bar').addClass('mk-so-hide-legacy');
		destroyFloatTheadArtifacts();
	}

	/**
	 * Vtiger gọi floatThead sau ~10ms khi load — gây header clone / layout như 2 list.
	 * SALES: bỏ floatThead, chỉ enhance DOM của mình.
	 */
	function patchVtigerFloatingThead() {
		if (typeof Vtiger_List_Js === 'undefined') {
			return;
		}

		if (Vtiger_List_Js.prototype.__mkSoFloatTheadPatched) {
			return;
		}
		Vtiger_List_Js.prototype.__mkSoFloatTheadPatched = true;

		var originalFloat = Vtiger_List_Js.prototype.registerFloatingThead;
		var originalReflow = Vtiger_List_Js.prototype.reflowList;

		Vtiger_List_Js.prototype.registerFloatingThead = function () {
			if (isSalesOrderSalesList()) {
				destroyFloatTheadArtifacts();
				applyListEnhancements();
				return;
			}
			originalFloat.call(this);
		};

		Vtiger_List_Js.prototype.reflowList = function () {
			if (isSalesOrderSalesList()) {
				destroyFloatTheadArtifacts();
				applyListEnhancements();
				return;
			}
			originalReflow.call(this);
		};
	}

	function scheduleInitialEnhancements() {
		var delays = [0, 50, 150, 400];
		var i;
		for (i = 0; i < delays.length; i++) {
			setTimeout(applyListEnhancements, delays[i]);
		}
		$(window).off('load.mkSoList').on('load.mkSoList', applyListEnhancements);
	}

	function collectHeaderFields($table) {
		var fields = [];
		if (!$table || !$table.length) {
			return fields;
		}
		$table.find('thead tr.listViewContentHeader th a[data-columnname]').each(function () {
			var name = $(this).attr('data-columnname');
			if (name && fields.indexOf(name) < 0) {
				fields.push(name);
			}
		});
		if (!fields.length) {
			$table.find('thead th a[data-columnname]').each(function () {
				var name = $(this).attr('data-columnname');
				if (name && fields.indexOf(name) < 0) {
					fields.push(name);
				}
			});
		}
		if (!fields.length) {
			$table.find('thead th[data-columnname]').each(function () {
				var name = $(this).attr('data-columnname');
				if (name && fields.indexOf(name) < 0) {
					fields.push(name);
				}
			});
		}
		return fields;
	}

	function collectVisibleHeaderTexts($table) {
		var texts = [];
		if (!$table || !$table.length) {
			return texts;
		}
		$table.find('thead tr.listViewContentHeader th').each(function () {
			var $th = $(this);
			if ($th.hasClass('mk-so-list-col-hidden')) {
				return;
			}
			var label = $.trim($th.find('a.listViewContentHeaderValues, a.noSorting').first().text());
			if (!label) {
				label = $.trim($th.text());
			}
			label = label.replace(/\s+/g, ' ');
			if (label && texts.indexOf(label) < 0) {
				texts.push(label);
			}
		});
		return texts;
	}

	function applyListEnhancements() {
		if (!isSalesOrderSalesList()) {
			return;
		}

		if (window.MkSalesListShared && typeof window.MkSalesListShared.relocatePaginationFooter === 'function') {
			window.MkSalesListShared.relocatePaginationFooter();
		}
		if (typeof window.mkSalesListAfterAjax === 'function') {
			window.mkSalesListAfterAjax();
		}

		dedupeListDom();

		var $table = getPrimaryTable();
		if (!$table.length) {
			document.documentElement.classList.add('mk-sales-list-ready');
			if (window.MkSalesListShared && typeof window.MkSalesListShared.revealSalesListUi === 'function') {
				window.MkSalesListShared.revealSalesListUi();
			}
			return;
		}

		applyTableClasses($table);
		fixEncodedTextCells($table);

		var statusField = resolveStatusField($table);
		if (statusField) {
			enhanceStatusPills($table, statusField);
			var headerFields = collectHeaderFields($table);
			var hasQuoteInHeaders = headerFields.some(function (name) {
				return isQuoteColumnFieldName(name);
			});
			if (hasQuoteInHeaders) {
				hideQuoteColumns($table);
			}
		}
		document.documentElement.classList.add('mk-sales-list-ready');
		if (window.MkSalesListShared && typeof window.MkSalesListShared.revealSalesListUi === 'function') {
			window.MkSalesListShared.revealSalesListUi();
		}
	}

	function patchPlaceListContents() {
		if (placeListContentsPatched || typeof Vtiger_List_Js === 'undefined') {
			return;
		}
		placeListContentsPatched = true;
		var originalPlace = Vtiger_List_Js.prototype.placeListContents;
		Vtiger_List_Js.prototype.placeListContents = function (contents) {
			if (isSalesOrderSalesList() && swapListBodyInShell(contents)) {
				applyListEnhancements();
				return;
			}
			originalPlace.call(this, contents);
			if (isSalesOrderSalesList()) {
				applyListEnhancements();
			}
		};
	}

	function initDebugHelpers() {
		window.__debugSOList = function () {
			var $table = getPrimaryTable();
			var headerFields = collectHeaderFields($table);
			var bodyFields = [];
			if ($table.length) {
				$table.find('tbody tr.listViewEntries').first().find('td[data-name]').each(function () {
					var name = $(this).attr('data-name');
					if (name && bodyFields.indexOf(name) < 0) {
						bodyFields.push(name);
					}
				});
			}
			var listHeadersRaw = document.querySelector('#listViewContent input[name="list_headers"]');
			var listHeadersParsed = null;
			if (listHeadersRaw && listHeadersRaw.value) {
				try {
					listHeadersParsed = JSON.parse(listHeadersRaw.value);
				} catch (e) {
					listHeadersParsed = listHeadersRaw.value;
				}
			}
			var candidates = statusCandidates();
			var statusCandidatesPresent = candidates.filter(function (name) {
				return headerFields.indexOf(name) >= 0 || bodyFields.indexOf(name) >= 0;
			});
			var quoteCandidatesPresent = headerFields.concat(bodyFields).filter(function (name, idx, arr) {
				return isQuoteColumnFieldName(name) && arr.indexOf(name) === idx;
			});
			return {
				tableExists: $table.length > 0,
				headerFields: headerFields,
				bodyFields: bodyFields,
				listHeadersParsed: listHeadersParsed,
				statusCandidatesPresent: statusCandidatesPresent,
				quoteCandidatesPresent: quoteCandidatesPresent,
				visibleHeaderTexts: collectVisibleHeaderTexts($table),
				hasShell: !!document.querySelector('#listViewContent .mk-so-page')
			};
		};
	}

	function bindListEvents() {
		if (typeof app === 'undefined' || !app.event || !app.event.on) {
			return;
		}
		app.event.on('post.listViewFilter.click', applyListEnhancements);
		app.event.on('post.listViewSort.click', applyListEnhancements);
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
		if (!isSalesOrderSalesList()) {
			return;
		}

		var root = getListViewContainer();
		if (!root.length) {
			return;
		}

		$(document).off('click.mkSoList', '.mk-so-trigger-columns').on('click.mkSoList', '.mk-so-trigger-columns', function (e) {
			e.preventDefault();
			root.find('.listColumnFilter').first().trigger('click');
		});

		/* Filter icon: MkSalesListShared scrolls to filter row (no toggle hide) */

		patchPlaceListContents();
		bindListEvents();
		initDebugHelpers();
		scheduleInitialEnhancements();
	}

	window.applySalesOrderListUi = applyListEnhancements;

	function boot() {
		if (!isSalesOrderSalesList()) {
			return;
		}
		whenVtigerListReady(function () {
			patchVtigerFloatingThead();
			init();
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})(jQuery);
