/* global jQuery */
(function ($) {
	'use strict';

	function initWarehouseList() {
		var $body = $(document.body);
		if ($body.attr('data-module') !== 'Warehouse' || $body.attr('data-view') !== 'List') {
			return;
		}
		if ($body.attr('data-app') !== 'INVENTORY') {
			return;
		}
		$body.addClass('mk-wh-list-ready');
	}

	$(function () {
		initWarehouseList();
	});
})(jQuery);
