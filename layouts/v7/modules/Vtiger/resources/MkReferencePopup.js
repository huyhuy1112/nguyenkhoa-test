/**
 * B-ACE reference lookup popup — server AJAX search, glass UI, native scroll.
 */
(function ($) {
	'use strict';

	var serverSearchTimer = null;
	var clientFilterTimer = null;
	var inflightSearchId = 0;
	var patched = false;
	var eventsBound = false;
	var savedSearchQuery = '';

	function getModal() {
		return $('#popupModal');
	}

	function getPageContainer() {
		return $('#popupPageContainer');
	}

	function isRefPopup() {
		return getPageContainer().length > 0;
	}

	function getDataRows() {
		return getPageContainer().find('.listViewEntriesTable tr.listViewEntries');
	}

	function rowTextForFilter(row) {
		var $row = $(row);
		var parts = [];
		$row.children('td').each(function () {
			var t = $(this).text();
			if (t) {
				parts.push(t);
			}
		});
		return (parts.join(' ') || '').toLowerCase().replace(/\s+/g, ' ').trim();
	}

	function applyClientFilter() {
		if (!isRefPopup()) {
			return;
		}
		var q = getSearchQuery().toLowerCase();
		var $rows = getDataRows();
		if (!q) {
			$rows.removeClass('mk-ref-row-hidden');
			getPageContainer().removeClass('mk-ref-client-filter-active');
			return;
		}
		getPageContainer().addClass('mk-ref-client-filter-active');
		$rows.each(function () {
			var match = rowTextForFilter(this).indexOf(q) >= 0;
			$(this).toggleClass('mk-ref-row-hidden', !match);
		});
	}

	function scheduleClientFilter() {
		if (clientFilterTimer) {
			clearTimeout(clientFilterTimer);
		}
		clientFilterTimer = setTimeout(function () {
			clientFilterTimer = null;
			applyClientFilter();
		}, 50);
	}

	function getSearchQuery() {
		var $input = $('#mk-ref-global-search');
		return $input.length ? $.trim($input.val()) : savedSearchQuery;
	}

	function setSearchQuery(val) {
		savedSearchQuery = val || '';
		var $input = $('#mk-ref-global-search');
		if ($input.length) {
			$input.val(savedSearchQuery);
		}
		$('#mk-ref-global-search-clear').prop('hidden', !savedSearchQuery);
	}

	function getPopupInstance() {
		if (typeof Vtiger_Popup_Js === 'undefined' || !isRefPopup()) {
			return null;
		}
		var moduleName = getPageContainer().find('#module').val();
		return Vtiger_Popup_Js.getInstance(moduleName);
	}

	function syncSearchFieldMeta() {
		if (typeof popup_uimeta === 'undefined' || !popup_uimeta.field || !popup_uimeta.field.get) {
			return;
		}
		getPageContainer().find('tr.searchRow .listSearchContributor[name]').each(function () {
			var $el = $(this);
			if ($el.data('fieldinfo')) {
				return;
			}
			var fn = $el.attr('name');
			if (!fn) {
				return;
			}
			var fi = popup_uimeta.field.get(fn);
			if (fi) {
				$el.data('fieldinfo', fi);
			}
		});
	}

	function getPrimarySearchFieldInput() {
		var preferred = [
			'potentialname',
			'accountname',
			'contactname',
			'productname',
			'subject',
			'lastname',
			'vendorname',
			'campaignname'
		];
		var $inputs = getPageContainer().find('tr.searchRow input.listSearchContributor[name]');
		var $fallback = null;
		var i;
		for (i = 0; i < preferred.length; i++) {
			var $match = $inputs.filter('[name="' + preferred[i] + '"]');
			if ($match.length) {
				return $match.first();
			}
		}
		$inputs.each(function () {
			var $el = $(this);
			if ($el.hasClass('select2_input_element')) {
				return;
			}
			$fallback = $el;
			return false;
		});
		return $fallback || $();
	}

	function applyQueryToHiddenRow(query) {
		clearHiddenSearchRow();
		var q = $.trim(query);
		if (!q) {
			return;
		}
		var $primary = getPrimarySearchFieldInput();
		if ($primary.length) {
			$primary.val(q);
			return;
		}
		var $first = getPageContainer().find('tr.searchRow input.listSearchContributor[name]').first();
		if ($first.length) {
			$first.val(q);
		}
	}

	function clearHiddenSearchRow() {
		getPageContainer().find('tr.searchRow .listSearchContributor').each(function () {
			var $el = $(this);
			if ($el.hasClass('select2_input_element')) {
				return;
			}
			$el.val('');
		});
	}

	function destroyCustomScroll() {
		var $div = getPageContainer().find('.popupEntriesDiv');
		if (!$div.length) {
			return;
		}
		try {
			if ($div.mCustomScrollbar) {
				$div.mCustomScrollbar('destroy');
			}
		} catch (e1) {
			/* ignore */
		}
		$div.removeClass('mCustomScrollbar _mCS_1 mCS_no_scrollbar');
		// Keep native horizontal / vertical scroll after destroying mCustomScrollbar
		$div.css({
			height: '',
			maxHeight: 'min(62vh, 580px)',
			overflowX: 'auto',
			overflowY: 'auto',
			webkitOverflowScrolling: 'touch'
		});
		getPageContainer()
			.find('.popupEntriesTableContainer')
			.css({ overflowX: 'auto', overflowY: 'visible' });
	}

	function markBodyOpen(open) {
		$('body').toggleClass('mk-ref-popup-open', !!open);
	}

	function setSearchLoading(on) {
		getPageContainer().toggleClass('mk-ref-search-loading', !!on);
		$('#mk-ref-global-search-go').prop('disabled', !!on);
	}

	function runPopupServerSearch() {
		var popup = getPopupInstance();
		if (!popup || !isRefPopup()) {
			return;
		}
		var reqId = ++inflightSearchId;
		var query = getSearchQuery();
		setSearchLoading(true);
		clearHiddenSearchRow();
		if (query) {
			applyQueryToHiddenRow(query);
		}
		popup.searchHandler().then(
			function () {
				if (reqId !== inflightSearchId) {
					return;
				}
				if (typeof popup.writeSelectedIds === 'function') {
					popup.writeSelectedIds([]);
				}
				getPageContainer().find('#pageNumber').val(1);
				getPageContainer().find('#pageToJump').val(1);
				if (typeof popup.updatePagination === 'function') {
					popup.updatePagination();
				}
				enhance({ preserveSearch: true });
				applyClientFilter();
				setSearchLoading(false);
			},
			function () {
				if (reqId === inflightSearchId) {
					setSearchLoading(false);
				}
			}
		);
	}

	function scheduleServerSearch() {
		if (serverSearchTimer) {
			clearTimeout(serverSearchTimer);
		}
		serverSearchTimer = setTimeout(function () {
			serverSearchTimer = null;
			runPopupServerSearch();
		}, 550);
	}

	function injectToolbar() {
		var $container = getPageContainer();
		if (!$container.length || $container.find('.mk-ref-popup-toolbar').length) {
			return;
		}
		var title = getModal().find('.modal-header h4').first().text().trim();
		var hint = title ? 'Select ' + title : 'Select record';
		var html =
			'<div class="mk-ref-popup-toolbar" role="search">' +
			'<div class="mk-ref-popup-toolbar__left">' +
			'<span class="mk-ref-popup-toolbar__hint">' + $('<div>').text(hint).html() + '</span>' +
			'</div>' +
			'<div class="mk-ref-popup-toolbar__right">' +
			'<div class="mk-ref-global-search">' +
			'<span class="mk-ref-global-search__ic" aria-hidden="true"><i class="fa fa-search"></i></span>' +
			'<input id="mk-ref-global-search" class="mk-ref-global-search__input" type="search" placeholder="Search all fields…" autocomplete="off" />' +
			'<button type="button" class="mk-ref-global-search__clear" id="mk-ref-global-search-clear" aria-label="Clear" hidden>' +
			'<i class="fa fa-times"></i></button>' +
			'<button type="button" class="mk-ref-global-search__go" id="mk-ref-global-search-go" aria-label="Search">' +
			'<i class="fa fa-search"></i><span>Search</span></button>' +
			'</div>' +
			'</div>' +
			'</div>';
		$container.prepend(html);
		setSearchQuery(savedSearchQuery);
	}

	function revealModal() {
		setSearchLoading(false);
		getModal().removeClass('mk-ref-popup-booting').addClass('mk-ref-popup-visible');
	}

	function enhance(options) {
		options = options || {};
		var $modal = getModal();
		var $container = getPageContainer();
		if (!$modal.length || !$container.length) {
			return;
		}

		$modal.addClass('mk-ref-popup');
		markBodyOpen(true);
		destroyCustomScroll();
		injectToolbar();
		syncSearchFieldMeta();
		$container.addClass('mk-ref-popup-enabled');

		if (options.preserveSearch) {
			setSearchQuery(savedSearchQuery);
		} else {
			getDataRows().removeClass('mk-ref-row-hidden');
		}

		reshapePotentialsPopup();
		applyClientFilter();
		revealModal();
	}

	function parseRowInfo($row) {
		var raw = $row.attr('data-info');
		if (!raw) {
			return {};
		}
		try {
			return typeof raw === 'string' ? JSON.parse(raw) : raw || {};
		} catch (e) {
			return {};
		}
	}

	function cellText($td) {
		return $.trim(($td && $td.text ? $td.text() : '') || '')
			.replace(/\s+/g, ' ')
			.replace(/^[\s\-–—]+$/, '');
	}

	function isBlankDisplay(text) {
		var t = $.trim(text || '');
		return !t || t === '--' || t === '—' || t === '-';
	}

	/**
	 * Potentials picker: đúng thứ tự cột
	 * [ ] | Tên khách hàng | Số điện thoại | Trạng thái
	 */
	function reshapePotentialsPopup() {
		var $container = getPageContainer();
		if (!$container.length || $container.find('#module').val() !== 'Potentials') {
			$container.removeClass('mk-ref-potentials-popup');
			return;
		}
		$container.addClass('mk-ref-potentials-popup');
		var $table = $container.find('.listViewEntriesTable').first();
		if (!$table.length) {
			return;
		}

		var colIndex = {};
		$table.find('thead tr.listViewHeaders th').each(function (idx) {
			var name = $(this).find('[data-columnname]').attr('data-columnname') || '';
			if (name) {
				colIndex[name] = idx;
			}
		});

		function setHeaderLabel(fieldName, label) {
			var idx = colIndex[fieldName];
			if (typeof idx !== 'number') {
				return;
			}
			var $th = $table.find('thead tr.listViewHeaders th').eq(idx);
			var $a = $th.find('a.listViewContentHeaderValues').first();
			if ($a.length) {
				var $icon = $a.find('i').first().detach();
				$a.empty();
				if ($icon.length) {
					$a.append($icon).append(document.createTextNode(' '));
				}
				$a.append(document.createTextNode(label + ' '));
			} else {
				$th.text(label);
			}
		}

		// Ẩn mọi cột thừa ngoài potentialname / amount / sales_stage
		$table.find('thead tr.listViewHeaders th').each(function (idx) {
			if (idx === 0) {
				return;
			}
			var name = $(this).find('[data-columnname]').attr('data-columnname') || '';
			if (name !== 'potentialname' && name !== 'amount' && name !== 'sales_stage') {
				$table.find('tr').each(function () {
					$(this).children('th, td').eq(idx).addClass('mk-ref-col-hidden');
				});
			}
		});

		setHeaderLabel('potentialname', 'Tên khách hàng');
		setHeaderLabel('amount', 'Số điện thoại');
		setHeaderLabel('sales_stage', 'Trạng thái');

		$table.find('tr.listViewEntries').each(function () {
			var $row = $(this);
			var info = parseRowInfo($row);
			var $tds = $row.children('td');
			var nameIdx = colIndex.potentialname;
			var amountIdx = colIndex.amount;

			var customer = info.mk_customer_name || '';
			if (isBlankDisplay(customer) && typeof nameIdx === 'number') {
				customer = cellText($tds.eq(nameIdx));
			}
			if (typeof nameIdx === 'number' && !isBlankDisplay(customer)) {
				var $nameTd = $tds.eq(nameIdx);
				var $link = $nameTd.find('a').first();
				if ($link.length) {
					$link.text(customer);
				} else {
					$nameTd.text(customer);
				}
				$nameTd.attr('title', customer);
			}

			var phone = info.mk_phone || '';
			if (phone && window.MkPhoneFormat && typeof window.MkPhoneFormat.format === 'function') {
				phone = window.MkPhoneFormat.format(phone) || phone;
			}
			if (typeof amountIdx === 'number') {
				var $amountTd = $tds.eq(amountIdx);
				$amountTd
					.removeClass('currency')
					.text(phone || '—')
					.attr('title', phone || '')
					.toggleClass('mk-ref-phone', !!phone);
			}
		});
	}

	function patchVtigerPopup() {
		if (patched || typeof Vtiger_Popup_Js === 'undefined') {
			return;
		}
		var proto = Vtiger_Popup_Js.prototype;
		if (proto._mkRefPopupPatched) {
			patched = true;
			return;
		}
		proto._mkRefPopupPatched = true;

		var origGetPageRecords = proto.getPageRecords;
		proto.getPageRecords = function (params) {
			return origGetPageRecords.call(this, params).then(function (data) {
				enhance({ preserveSearch: true });
				return data;
			});
		};

		proto.registerPostPopupLoadEvents = function () {
			destroyCustomScroll();
		};

		patched = true;
	}

	function patchShowPopup() {
		if (!window.app || !app.helper || app.helper._mkRefShowPopupPatched) {
			return;
		}
		app.helper._mkRefShowPopupPatched = true;
		var original = app.helper.showPopup;
		app.helper.showPopup = function (content, params) {
			savedSearchQuery = '';
			var result = original.call(this, content, params);
			var $modal = $('#popupModal');
			if ($modal.find('#popupPageContainer').length) {
				$modal.addClass('mk-ref-popup mk-ref-popup-booting');
				markBodyOpen(true);
				$modal.off('hidden.bs.modal.mkRefPopup').on('hidden.bs.modal.mkRefPopup', function () {
					markBodyOpen(false);
					savedSearchQuery = '';
					$modal.removeClass('mk-ref-popup-visible');
				});
				patchVtigerPopup();
				setTimeout(enhance, 0);
			}
			return result;
		};

		var origHide = app.helper.hidePopup;
		app.helper.hidePopup = function () {
			markBodyOpen(false);
			savedSearchQuery = '';
			return origHide.call(this);
		};
	}

	function bindEvents() {
		if (eventsBound) {
			return;
		}
		eventsBound = true;

		$(document)
			.off('click.mkRefPopupRow', '#popupModal .listViewEntries')
			.on('click.mkRefPopupRow', '#popupModal .listViewEntries', function (e) {
				if ($(e.target).closest('input.entryCheckBox, a, button').length) {
					return;
				}
				var popup = getPopupInstance();
				if (!popup) {
					return;
				}
				if (popup.isMultiSelectMode && popup.isMultiSelectMode()) {
					var $cb = $(this).find('input.entryCheckBox').first();
					if ($cb.length) {
						e.preventDefault();
						e.stopPropagation();
						$cb.trigger('click');
					}
					return;
				}
				if (typeof popup.getListViewEntries === 'function') {
					popup.getListViewEntries(e);
				}
			})
			.off('input.mkRefPopup', '#mk-ref-global-search')
			.on('input.mkRefPopup', '#mk-ref-global-search', function () {
				savedSearchQuery = $.trim($(this).val());
				$('#mk-ref-global-search-clear').prop('hidden', !savedSearchQuery);
				scheduleClientFilter();
				if (savedSearchQuery.length >= 2) {
					scheduleServerSearch();
				} else if (!savedSearchQuery) {
					if (serverSearchTimer) {
						clearTimeout(serverSearchTimer);
						serverSearchTimer = null;
					}
					runPopupServerSearch();
				}
			})
			.off('keydown.mkRefPopup', '#mk-ref-global-search')
			.on('keydown.mkRefPopup', '#mk-ref-global-search', function (ev) {
				if (ev.key === 'Enter') {
					ev.preventDefault();
					if (serverSearchTimer) {
						clearTimeout(serverSearchTimer);
						serverSearchTimer = null;
					}
					savedSearchQuery = $.trim($(this).val());
					runPopupServerSearch();
				} else if (ev.key === 'Escape') {
					ev.preventDefault();
					savedSearchQuery = '';
					setSearchQuery('');
					getDataRows().removeClass('mk-ref-row-hidden');
					runPopupServerSearch();
				}
			})
			.off('click.mkRefPopupGo', '#mk-ref-global-search-go')
			.on('click.mkRefPopupGo', '#mk-ref-global-search-go', function (e) {
				e.preventDefault();
				if (serverSearchTimer) {
					clearTimeout(serverSearchTimer);
					serverSearchTimer = null;
				}
				savedSearchQuery = $.trim($('#mk-ref-global-search').val());
				runPopupServerSearch();
			})
			.off('click.mkRefPopupClear', '#mk-ref-global-search-clear')
			.on('click.mkRefPopupClear', '#mk-ref-global-search-clear', function (e) {
				e.preventDefault();
				if (serverSearchTimer) {
					clearTimeout(serverSearchTimer);
					serverSearchTimer = null;
				}
				savedSearchQuery = '';
				setSearchQuery('');
				runPopupServerSearch();
				$('#mk-ref-global-search').focus();
			});

		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.Popup.Load', function () {
				patchVtigerPopup();
				setTimeout(enhance, 0);
			});
		}
	}

	function whenReady(callback) {
		var attempts = 0;
		function tick() {
			if (typeof app !== 'undefined' && app.helper && typeof Vtiger_Popup_Js !== 'undefined') {
				callback();
				return;
			}
			attempts += 1;
			if (attempts < 200) {
				setTimeout(tick, 25);
			}
		}
		tick();
	}

	function init() {
		bindEvents();
		whenReady(function () {
			patchShowPopup();
			patchVtigerPopup();
		});
	}

	window.MkReferencePopup = {
		enhance: enhance,
		runSearch: runPopupServerSearch,
		destroyCustomScroll: destroyCustomScroll
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(jQuery);
