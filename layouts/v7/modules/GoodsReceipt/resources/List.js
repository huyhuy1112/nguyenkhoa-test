/* global jQuery */
(function ($) {
	'use strict';

	function initGoodsReceiptList() {
		var $body = $(document.body);
		if ($body.attr('data-module') !== 'GoodsReceipt' || $body.attr('data-view') !== 'List') {
			return;
		}
		if ($body.attr('data-app') !== 'INVENTORY') {
			return;
		}
		$body.addClass('mk-gr-list-ready');
	}

	$(function () {
		initGoodsReceiptList();
	});
})(jQuery);
