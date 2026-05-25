/* global jQuery */
(function ($) {
	'use strict';

	function initGoodsIssueList() {
		var $body = $(document.body);
		if ($body.attr('data-module') !== 'GoodsIssue' || $body.attr('data-view') !== 'List') {
			return;
		}
		if ($body.attr('data-app') !== 'INVENTORY') {
			return;
		}
		$body.addClass('mk-gi-list-ready');
	}

	$(function () {
		initGoodsIssueList();
	});
})(jQuery);
