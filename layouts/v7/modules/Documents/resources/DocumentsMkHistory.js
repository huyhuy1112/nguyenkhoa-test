/**
 * Documents History — search, filters, relative time (UI only)
 */
(function ($) {
	'use strict';

	function isHistoryPage() {
		var b = document.body;
		return (
			b &&
			b.getAttribute('data-module') === 'Documents' &&
			b.getAttribute('data-view') === 'History' &&
			b.getAttribute('data-app') === 'MANAGEMENT'
		);
	}

	function parseVtigerDate(str) {
		if (!str) {
			return null;
		}
		var m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
		if (m) {
			return new Date(
				parseInt(m[1], 10),
				parseInt(m[2], 10) - 1,
				parseInt(m[3], 10),
				parseInt(m[4], 10),
				parseInt(m[5], 10),
				parseInt(m[6], 10)
			);
		}
		var d = new Date(str);
		return isNaN(d.getTime()) ? null : d;
	}

	function relativeTime(date) {
		if (!date) {
			return '';
		}
		var sec = Math.floor((Date.now() - date.getTime()) / 1000);
		if (sec < 60) {
			return 'Just now';
		}
		if (sec < 3600) {
			return Math.floor(sec / 60) + ' min ago';
		}
		if (sec < 86400) {
			return Math.floor(sec / 3600) + ' hr ago';
		}
		if (sec < 604800) {
			return Math.floor(sec / 86400) + ' days ago';
		}
		return date.toLocaleDateString();
	}

	function enhanceTimestamps() {
		$('.mk-doc-history-time').each(function () {
			var $t = $(this);
			var raw = $t.attr('datetime') || $t.data('timestamp') || $t.text();
			var date = parseVtigerDate(raw);
			if (!date) {
				return;
			}
			if (!$t.find('.mk-doc-history-time__rel').length) {
				$t.append(
					$('<span class="mk-doc-history-time__rel"></span>').text(relativeTime(date))
				);
			}
			$t.attr('title', raw);
		});
	}

	function applyFilters() {
		var q = ($('#mkDocHistorySearch').val() || '').toLowerCase().trim();
		var action = $('.mk-doc-history-filter.is-active').data('filter') || 'all';
		var visible = 0;
		$('#mkDocHistoryTable .mk-doc-history-row').each(function () {
			var $row = $(this);
			var searchBlob = String($row.data('search') || $row.attr('data-search') || '').toLowerCase();
			var rowAction = String($row.data('action') || '').toLowerCase();
			var matchQ = !q || searchBlob.indexOf(q) !== -1;
			var matchA =
				action === 'all' ||
				rowAction === action ||
				rowAction.indexOf(action) === 0;
			var show = matchQ && matchA;
			$row.toggleClass('is-hidden', !show);
			if (show) {
				visible++;
			}
		});
		$('#mkDocHistoryNoMatch').prop('hidden', visible > 0 || !$('#mkDocHistoryTable').length);
	}

	function bindUi() {
		$('#mkDocHistorySearch').on('input', applyFilters);
		$('.mk-doc-history-filter').on('click', function () {
			$('.mk-doc-history-filter').removeClass('is-active');
			$(this).addClass('is-active');
			applyFilters();
		});
	}

	function init() {
		if (!isHistoryPage()) {
			return;
		}
		enhanceTimestamps();
		bindUi();
		applyFilters();
	}

	$(init);
})(jQuery);
