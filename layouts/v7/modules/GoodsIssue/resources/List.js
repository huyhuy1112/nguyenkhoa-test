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

		var $qcLink = $('[data-mk-gi-tab="qc"]').first();
		var $outboundPane = $('#mkGiOutboundPane');
		var $qcPane = $('#mkGiQcPane');
		if (!$qcLink.length || !$outboundPane.length || !$qcPane.length) {
			return;
		}

		function setTab(tab) {
			var $links = $qcLink.closest('.mk-gi-topnav').find('a');
			$links.removeClass('is-active').removeAttr('aria-current');
			if (tab === 'qc') {
				$qcLink.addClass('is-active').attr('aria-current', 'page');
				$outboundPane.addClass('hide');
				$qcPane.removeClass('hide');
			} else {
				// Default: keep Outbound active (third link)
				var $outboundLink = $links.filter(function () {
					return ($(this).text() || '').trim().toLowerCase() === 'outbound';
				}).first();
				if ($outboundLink.length) {
					$outboundLink.addClass('is-active').attr('aria-current', 'page');
				}
				$qcPane.addClass('hide');
				$outboundPane.removeClass('hide');
			}
		}

		$qcLink.on('click', function (e) {
			e.preventDefault();
			setTab('qc');
		});
	}

	$(function () {
		initGoodsIssueList();
	});
})(jQuery);
