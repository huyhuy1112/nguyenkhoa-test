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
			$rows.show();
			getPageContainer().removeClass('mk-ref-client-filter-active');
			return;
		}
		getPageContainer().addClass('mk-ref-client-filter-active');
		$rows.each(function () {
			$(this).toggle(rowTextForFilter(this).indexOf(q) >= 0);
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

	function getSearchableFieldNames() {
		var names = [];
		getPageContainer().find('tr.searchRow input.listSearchContributor[name]').each(function () {
			var $el = $(this);
			if ($el.hasClass('select2_input_element')) {
				return;
			}
			var name = $el.attr('name');
			if (name && names.indexOf(name) < 0) {
				names.push(name);
			}
		});
		if (!names.length) {
			getPageContainer().find('thead .listViewHeaderValues').each(function () {
				var name = $(this).data('columnname');
				if (name && name !== 'listprice' && names.indexOf(name) < 0) {
					names.push(name);
				}
			});
		}
		return names;
	}

	function buildGlobalOrSearchParams(query) {
		var q = $.trim(query);
		if (!q) {
			return new Array([]);
		}
		var conditions = [];
		var fields = getSearchableFieldNames();
		var i;
		for (i = 0; i < fields.length; i++) {
			conditions.push([fields[i], 'c', q]);
		}
		if (!conditions.length) {
			return new Array([]);
		}
		// Vtiger: group 0 = AND, group 1 = OR between columns — empty group 0 + OR group for global match.
		return [[], conditions];
	}

	function applyQueryToHiddenRow(query) {
		clearHiddenSearchRow();
		var q = $.trim(query);
		if (!q) {
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
		$div.css({ height: '', maxHeight: '', overflow: '' });
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
		if (!query) {
			// Empty query: use stock popup params (full list).
		} else if (!getSearchableFieldNames().length) {
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
			getDataRows().show();
		}

		applyClientFilter();
		revealModal();
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

		var origGetPopupListSearchParams = proto.getPopupListSearchParams;
		proto.getPopupListSearchParams = function () {
			if ($('#popupPageContainer').length && getSearchQuery()) {
				return buildGlobalOrSearchParams(getSearchQuery());
			}
			return origGetPopupListSearchParams.call(this);
		};

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
					getDataRows().show();
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
