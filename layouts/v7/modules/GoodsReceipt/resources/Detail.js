/* global jQuery */
(function ($) {
	'use strict';

	function initGoodsReceiptDetail() {
		var $body = $(document.body);
		if ($body.attr('data-module') !== 'GoodsReceipt' || $body.attr('data-view') !== 'Detail') {
			return;
		}
		if ($body.attr('data-app') !== 'INVENTORY') {
			return;
		}
		$body.addClass('mk-gr-detail-ready');
	}

	$(function () {
		initGoodsReceiptDetail();
	});
})(jQuery);
