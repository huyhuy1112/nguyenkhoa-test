/**
 * Shared POS list row-expand dropdown for Sales modules (Accounts / ServiceContracts).
 * Opt-in: window.__mkSalesPosInlineConfig = { module, loadingText, errorText }
 */
(function ($) {
	'use strict';

	var expandedId = '';
	var loading = false;

	function cfg() {
		return window.__mkSalesPosInlineConfig || {};
	}

	function isPosEnabled() {
		return !!document.querySelector('#listViewContent .mk-so-pos-list-enabled, .mk-so-pos-page');
	}

	function moduleName() {
		return cfg().module || (document.body && document.body.getAttribute('data-module')) || '';
	}

	function getColspan($table) {
		var n = $table.find('thead tr.listViewContentHeader th').length;
		return n > 0 ? n : 8;
	}

	function collapse($table) {
		$table.find('tr.mk-so-inline-detail-row').remove();
		$table.find('tr.listViewEntries.mk-so-row-expanded').removeClass('mk-so-row-expanded');
		expandedId = '';
		loading = false;
	}

	function initPanel($detailRow) {
		var $panel = $detailRow.find('.mk-so-inline-detail');
		$panel.on('click', '.mk-so-inline-detail__view-full-btn', function (e) {
			e.preventDefault();
			var url = $panel.attr('data-detail-url');
			if (url) {
				window.location.href = url;
			}
		});
		$panel.on('click', '.mk-so-inline-detail__edit-btn', function (e) {
			e.preventDefault();
			var url = $panel.attr('data-edit-url');
			if (url) {
				window.location.href = url;
			}
		});
	}

	function loadDetail(recordId, $row, $table) {
		var c = cfg();
		var colspan = getColspan($table);
		var $detailRow = $(
			'<tr class="mk-so-inline-detail-row">' +
				'<td colspan="' + colspan + '">' +
					'<div class="mk-so-inline-detail mk-so-inline-detail--loading">' +
						'<span class="mk-so-inline-detail__spinner" aria-hidden="true"></span>' +
						'<span>' + (c.loadingText || 'Đang tải chi tiết...') + '</span>' +
					'</div>' +
				'</td>' +
			'</tr>'
		);
		$row.after($detailRow);

		$.ajax({
			url: 'index.php',
			type: 'GET',
			dataType: 'html',
			data: {
				module: moduleName(),
				view: 'Detail',
				mode: 'showListInlineDetail',
				record: recordId,
				app: (document.body.getAttribute('data-app') || 'SALES')
			}
		}).done(function (html) {
			if (expandedId !== String(recordId)) {
				return;
			}
			$detailRow.find('td').html(html);
			initPanel($detailRow);
		}).fail(function () {
			if (expandedId !== String(recordId)) {
				return;
			}
			$detailRow.find('td').html(
				'<div class="mk-so-inline-detail mk-so-inline-detail--error">' +
					(c.errorText || 'Không tải được chi tiết.') +
					' <a href="' + ($row.data('recordurl') || '#') + '">Mở trang chi tiết</a>.' +
				'</div>'
			);
		}).always(function () {
			loading = false;
		});
	}

	function toggle(recordId, $row) {
		var $table = $row.closest('#listview-table');
		if (!$table.length) {
			return;
		}
		recordId = String(recordId || '');
		if (!recordId) {
			return;
		}
		if (expandedId === recordId) {
			collapse($table);
			return;
		}
		if (loading) {
			return;
		}
		collapse($table);
		expandedId = recordId;
		loading = true;
		$row.addClass('mk-so-row-expanded');
		loadDetail(recordId, $row, $table);
	}

	function isInteraction(target) {
		return !!(target.closest && target.closest(
			'.mk-so-inline-detail, .mk-so-inline-detail-row, .mk-so-pos-star-btn, .mk-so-pos-control-td, a, button, input, select, textarea, .dropdown-menu'
		));
	}

	function onClick(e) {
		if (!isPosEnabled()) {
			return;
		}
		var mod = moduleName();
		var bodyMod = document.body && document.body.getAttribute('data-module');
		if (mod && bodyMod && mod !== bodyMod) {
			return;
		}
		var target = e.target;
		if (!target || !target.closest || isInteraction(target)) {
			return;
		}
		var row = target.closest('#listview-table tr.listViewEntries');
		if (!row) {
			return;
		}
		if (window.getSelection && String(window.getSelection()).trim().length > 0) {
			return;
		}
		if ($(row).hasClass('edited')) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		if (typeof e.stopImmediatePropagation === 'function') {
			e.stopImmediatePropagation();
		}
		toggle(String($(row).data('id') || ''), $(row));
	}

	function bind() {
		if (document.documentElement.getAttribute('data-mk-pos-inline-bound')) {
			return;
		}
		document.documentElement.setAttribute('data-mk-pos-inline-bound', '1');
		document.addEventListener('click', onClick, true);
	}

	$(document).ready(bind);
	window.MkSalesPosInline = { bind: bind, collapse: collapse, toggle: toggle };
})(jQuery);
