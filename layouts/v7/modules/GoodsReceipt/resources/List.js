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

		var $qcLink = $('[data-mk-gr-tab="qc"]').first();
		var $inboundPane = $('#mkGrInboundPane');
		var $qcPane = $('#mkGrQcPane');
		if (!$qcLink.length || !$inboundPane.length || !$qcPane.length) {
			return;
		}

		function setTab(tab) {
			var $links = $qcLink.closest('.mk-gi-topnav').find('a');
			$links.removeClass('is-active').removeAttr('aria-current');
			if (tab === 'qc') {
				$qcLink.addClass('is-active').attr('aria-current', 'page');
				$inboundPane.addClass('hide');
				$qcPane.removeClass('hide');
			} else {
				// Default: keep Inbound active (first link)
				var $inboundLink = $links.filter(function () {
					return ($(this).text() || '').trim().toLowerCase() === 'inbound';
				}).first();
				if ($inboundLink.length) {
					$inboundLink.addClass('is-active').attr('aria-current', 'page');
				}
				$qcPane.addClass('hide');
				$inboundPane.removeClass('hide');
			}
		}

		$qcLink.on('click', function (e) {
			e.preventDefault();
			setTab('qc');
		});
	}

	$(function () {
		initGoodsReceiptList();
	});
})(jQuery);
