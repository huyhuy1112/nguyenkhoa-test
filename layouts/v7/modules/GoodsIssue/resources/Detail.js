/* global jQuery */
(function ($) {
	'use strict';

	function initGoodsIssueDetail() {
		var $body = $(document.body);
		if ($body.attr('data-module') !== 'GoodsIssue' || $body.attr('data-view') !== 'Detail') {
			return;
		}
		if ($body.attr('data-app') !== 'INVENTORY') {
			return;
		}
		$body.addClass('mk-go-detail-ready');
	}

	$(function () {
		initGoodsIssueDetail();
	});
})(jQuery);
