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
		var $waitingLink = $('[data-mk-gi-tab="waiting_print"]').first();
		var $outboundLink = $('[data-mk-gi-tab="outbound"]').first();
		var $outboundPane = $('#mkGiOutboundPane');
		var $qcPane = $('#mkGiQcPane');
		if (!$outboundPane.length || !$qcPane.length) {
			return;
		}

		var activeTab = 'outbound';
		if (window.location.search.indexOf('tab=waiting_print') >= 0) {
			activeTab = 'waiting_print';
		}

		function setTab(tab) {
			var $links = $qcLink.closest('.mk-gi-topnav').find('a[data-mk-gi-tab]');
			$links.removeClass('is-active').removeAttr('aria-current');
			if (tab === 'qc') {
				$qcLink.addClass('is-active').attr('aria-current', 'page');
				$outboundPane.addClass('hide');
				$qcPane.removeClass('hide');
			} else {
				if (tab === 'waiting_print' && $waitingLink.length) {
					$waitingLink.addClass('is-active').attr('aria-current', 'page');
				} else if ($outboundLink.length) {
					$outboundLink.addClass('is-active').attr('aria-current', 'page');
				}
				$qcPane.addClass('hide');
				$outboundPane.removeClass('hide');
			}
		}

		setTab(activeTab);

		$qcLink.on('click', function (e) {
			e.preventDefault();
			setTab('qc');
		});
	}

	$(function () {
		initGoodsIssueList();
	});
})(jQuery);
