/* global jQuery */
(function ($) {
	'use strict';

	var STORAGE_KEY = 'mk_hd_ticket_list_layout';

	function initHelpDeskList() {
		var $body = $(document.body);
		if ($body.attr('data-module') !== 'HelpDesk' || $body.attr('data-view') !== 'List') {
			return;
		}
		$body.addClass('mk-hd-list-ready');

		var $table = $('#mkHdTicketTable');
		if ($table.length && typeof $.fn.floatThead === 'function' && $table.data('floatThead-attached')) {
			try {
				$table.floatThead('destroy');
			} catch (e) { /* ignore */ }
		}

		bindViewToggle();
		applyViewMode(getSavedViewMode());
	}

	function getSavedViewMode() {
		try {
			return window.localStorage.getItem(STORAGE_KEY) === 'grid' ? 'grid' : 'list';
		} catch (e) {
			return 'list';
		}
	}

	function applyViewMode(mode) {
		var isGrid = mode === 'grid';
		$(document.body).toggleClass('mk-hd-is-view-grid', isGrid);
		$('.mk-hd-view-toggle__btn[data-mk-hd-view="grid"]')
			.toggleClass('is-active', isGrid)
			.attr('aria-pressed', isGrid ? 'true' : 'false');
		$('.mk-hd-view-toggle__btn[data-mk-hd-view="list"]')
			.toggleClass('is-active', !isGrid)
			.attr('aria-pressed', !isGrid ? 'true' : 'false');
		try {
			window.localStorage.setItem(STORAGE_KEY, isGrid ? 'grid' : 'list');
		} catch (e2) { /* ignore */ }
	}

	function bindViewToggle() {
		if (bindViewToggle.__bound) {
			return;
		}
		bindViewToggle.__bound = true;
		$(document).on('click.mkHdListView', '.mk-hd-view-toggle__btn[data-mk-hd-view]', function (e) {
			e.preventDefault();
			var mode = $(this).attr('data-mk-hd-view');
			if (mode === 'grid' || mode === 'list') {
				applyViewMode(mode);
			}
		});
	}

	$(function () {
		initHelpDeskList();
	});
})(jQuery);
