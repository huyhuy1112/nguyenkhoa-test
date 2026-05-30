/* global jQuery */
(function ($) {
	'use strict';

	function initActivitiesList() {
		var $body = $(document.body);
		if ($body.attr('data-module') !== 'Activities' || $body.attr('data-view') !== 'List') {
			return;
		}
		$body.addClass('mk-act-list-ready');

		var $table = $('#mkActActivityTable');
		if ($table.length && typeof $.fn.floatThead === 'function' && $table.data('floatThead-attached')) {
			try {
				$table.floatThead('destroy');
			} catch (e) { /* ignore */ }
		}
	}

	$(function () {
		initActivitiesList();
	});
})(jQuery);
