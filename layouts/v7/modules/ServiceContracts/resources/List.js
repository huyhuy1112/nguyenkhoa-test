/**
 * ServiceContracts list (Sales): scroll, pagination relocate, search placeholders, creator chips.
 * Scope: body[data-module="ServiceContracts"][data-view="List"][data-app="SALES"]
 */
(function ($) {
	'use strict';

	var SEARCH_PLACEHOLDERS = {
		subject: 'Subject',
		sc_related_to: 'Organization',
		related_to: 'Organization',
		account_id: 'Organization',
		quote_id: 'Quote name',
		total_units: 'Total',
		total: 'Total',
		assigned_user_id: 'Created by',
		created_user_id: 'Created by',
		smcreatorid: 'Created by'
	};

	function isServiceContractsSalesList() {
		var b = document.body;
		if (!b || b.getAttribute('data-module') !== 'ServiceContracts' || b.getAttribute('data-view') !== 'List') {
			return false;
		}
		var appName = (b.getAttribute('data-app') || '').toUpperCase();
		if (appName === 'SALES') {
			return true;
		}
		var params = new URLSearchParams(window.location.search || '');
		return params.get('module') === 'ServiceContracts' && params.get('view') === 'List' && params.get('app') === 'SALES';
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
		if (!isServiceContractsSalesList()) {
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
		if (!isServiceContractsSalesList()) {
			return;
		}
		var footer = document.querySelector('#listViewContent .mk-so-filter-row__footer');
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
		if (!isServiceContractsSalesList()) {
			return;
		}
		$('#listViewContent #listview-table').addClass('mk-sc-table');
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

	function enhanceCreatedBy(context) {
		var selectors = [
			'td[data-name="assigned_user_id"] .value',
			'td[data-name="created_user_id"] .value',
			'td[data-name="smcreatorid"] .value'
		];
		$(context).find(selectors.join(',')).each(function () {
			var $value = $(this);
			if ($value.find('.mk-sc-avatar').length) {
				return;
			}
			var text = $.trim($value.text());
			if (!text) {
				return;
			}
			var initials = initialsFromName(text);
			$value.addClass('mk-sc-has-creator').empty().append(
				$('<span>', { 'class': 'mk-sc-avatar', text: initials }),
				$('<span>', { 'class': 'mk-sc-avatar__label', text: text })
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
		if (!isServiceContractsSalesList()) {
			return;
		}
		document.body.classList.remove('mk-service-contracts-ui-loading');
		document.body.classList.add('mk-service-contracts-ui-ready');
		document.documentElement.classList.add('mk-service-contracts-ui-ready');
	}

	function afterListLayout() {
		if (!isServiceContractsSalesList()) {
			return;
		}
		relocatePagination();
		markTable();
		enhanceCreatedBy(document);
		applySearchPlaceholders(document);
		fixListScrollContainer();
		setReadyState();
	}

	function init() {
		if (!isServiceContractsSalesList()) {
			return;
		}
		document.body.classList.add('mk-service-contracts-ui-loading');

		var root = $('#listViewContent');
		if (!root.length) {
			setReadyState();
			return;
		}

		$(document).on('click.mkScList', '.mk-sc-trigger-columns', function (e) {
			e.preventDefault();
			var col = root.find('.listColumnFilter').first();
			if (col.length) {
				col.trigger('click');
			}
		});

		$(document).on('click.mkScList', '.mk-sc-filter-trigger-search', function (e) {
			e.preventDefault();
			root.toggleClass('mk-sc-search-open');
		});

		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.listViewFilter.click', function () {
				setTimeout(afterListLayout, 200);
			});
			app.event.on('Vtiger.Post.MenuToggle', function () {
				setTimeout(fixListScrollContainer, 80);
			});
		}

		var resizeTimer;
		$(window).on('resize.mkScList', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				if (isServiceContractsSalesList()) {
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
