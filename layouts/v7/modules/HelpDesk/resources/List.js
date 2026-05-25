/* global jQuery */
(function ($) {
	'use strict';

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
	}

	$(function () {
		initHelpDeskList();
	});
})(jQuery);
