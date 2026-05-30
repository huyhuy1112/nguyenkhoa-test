/**
 * Documents List — lightweight UI (MANAGEMENT). No mock data; vtiger list unchanged.
 */
(function ($, window, document) {
	'use strict';

	var NS = 'MkDocumentsPremium';

	function isManagementDocumentsList() {
		var body = document.body;
		return (
			body &&
			body.getAttribute('data-module') === 'Documents' &&
			body.getAttribute('data-view') === 'List' &&
			body.getAttribute('data-app') === 'MANAGEMENT'
		);
	}

	function checkEmptyState() {
		var $empty = $('#mkDocEmpty');
		var $body = $('#mkDocTableBody');
		if (!$empty.length || !$body.length) {
			return;
		}
		var hasRows =
			$body.find('tr.listViewEntries').length > 0 &&
			!$body.find('.listview-table-norecords').length;
		$empty.prop('hidden', hasRows);
	}

	function updateStats() {
		var count = $('#mkDocTableBody tr.listViewEntries').length;
		if ($('#mkDocTableBody .listview-table-norecords').length) {
			count = 0;
		}
		$('#mkDocStatTotal').text(count > 0 ? count : '0');
	}

	function filterFolders(query) {
		var q = (query || '').toLowerCase().trim();
		var visible = 0;
		$('#mkDocFolderTree .mk-doc-folder').each(function () {
			var name = String($(this).data('folder-name') || '').toLowerCase();
			var show = !q || name.indexOf(q) !== -1;
			$(this).toggle(show);
			if (show) {
				visible++;
			}
		});
		$('#mkDocFolderEmpty').prop('hidden', visible > 0 || !q);
	}

	function bindFolderSearch() {
		$('#mkDocFolderSearch').on('input', function () {
			filterFolders($(this).val());
		});
	}

	function bindViewToggle() {
		$('.mk-doc-view-toggle__btn').on('click', function () {
			var view = $(this).data('mk-doc-view');
			$('.mk-doc-view-toggle__btn').removeClass('is-active');
			$(this).addClass('is-active');
			$('.mk-doc-workspace').toggleClass('is-view-grid', view === 'grid');
		});
	}

	function bindListSearch() {
		$('#mkDocGlobalSearch').on('keydown', function (e) {
			if (e.key !== 'Enter') {
				return;
			}
			var q = $.trim($(this).val());
			var $input = $('.listview-table tr.searchRow input.listSearchContributor').first();
			if ($input.length && q) {
				$input.val(q).trigger('change');
				$('.listview-table tr.searchRow .btn-success').first().trigger('click');
			}
		});
	}

	function bindSelect2FilterDrop() {
		$(document).on('select2-open', function () {
			if (!isManagementDocumentsList()) {
				return;
			}
			var $drop = $('#select2-drop');
			if (!$drop.length) {
				return;
			}
			var $active = $('.select2-container-active').last();
			var minW = 220;
			if ($active.length) {
				minW = Math.max(minW, $active.outerWidth() || 0);
			}
			$drop.css({
				'min-width': minW + 'px',
				width: 'auto',
				'max-width': 'min(420px, 96vw)'
			});
		});
	}

	function bindDropzone() {
		var $zone = $('#mkDocDropzone');
		var $panel = $('.mk-doc-panel');
		if (!$zone.length || !$panel.length) {
			return;
		}
		var depth = 0;
		$panel.on('dragenter dragover', function (e) {
			e.preventDefault();
			depth++;
			$zone.addClass('is-visible').attr('aria-hidden', 'false');
		});
		$panel.on('dragleave drop', function (e) {
			e.preventDefault();
			depth = Math.max(0, depth - 1);
			if (depth === 0) {
				$zone.removeClass('is-visible').attr('aria-hidden', 'true');
			}
		});
		$panel.on('drop', function () {
			depth = 0;
			$zone.removeClass('is-visible').attr('aria-hidden', 'true');
			var $new = $('.doc-btn-new-document').first();
			if ($new.length && $new.attr('href')) {
				window.location.href = $new.attr('href');
			} else if (window.Documents_Index_Js && Documents_Index_Js.uploadTo) {
				Documents_Index_Js.uploadTo('Vtiger');
			}
		});
	}

	function setupTableScroll() {
		var $tc = $('#mkDocTableBody').find('#table-content').first();
		if (!$tc.length) {
			$tc = $('.mk-doc-workspace #table-content').first();
		}
		if (!$tc.length) {
			return;
		}
		$tc.find('.ps__rail-x, .ps__rail-y').remove();
		$('.mk-doc-workspace .floatThead-container').remove();
		if ($.fn.perfectScrollbar) {
			try {
				$tc.perfectScrollbar('destroy');
			} catch (e) {
				/* ignore */
			}
		}
		if ($.fn.floatThead) {
			$tc.find('#listview-table').each(function () {
				try {
					$(this).floatThead('destroy');
				} catch (e2) {
					/* ignore */
				}
			});
		}
		$tc.css({ overflowX: 'auto', overflowY: 'auto', width: '100%' });
		$tc.find('#listview-table').css({ width: 'max-content', minWidth: '100%' });
	}

	function postRender() {
		if (!isManagementDocumentsList()) {
			return;
		}
		setupTableScroll();
		checkEmptyState();
		updateStats();
	}

	function bindAjaxHooks() {
		$(document).ajaxComplete(function () {
			if (!isManagementDocumentsList()) {
				return;
			}
			postRender();
		});
		if (typeof app !== 'undefined' && app.event && app.event.on) {
			app.event.on('post.listViewFilter.click', postRender);
			app.event.on('post.listViewSort.click', postRender);
			app.event.on('post.listViewMassAction.click', postRender);
		}
		$(document).on('mkDocumentsListPostLoad', postRender);
	}

	function init() {
		if (!isManagementDocumentsList() || !$('.mk-doc-workspace').length) {
			return;
		}
		bindFolderSearch();
		bindViewToggle();
		bindListSearch();
		bindSelect2FilterDrop();
		bindDropzone();
		bindAjaxHooks();
		postRender();
	}

	window[NS] = { init: init, postRender: postRender, isManagementDocumentsList: isManagementDocumentsList };

	$(function () {
		init();
	});
})(jQuery, window, document);
