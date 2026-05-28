/* global jQuery */
(function ($) {
	'use strict';

	function debounce(fn, wait) {
		var t = null;
		return function () {
			var ctx = this;
			var args = arguments;
			clearTimeout(t);
			t = setTimeout(function () {
				fn.apply(ctx, args);
			}, wait);
		};
	}

	function normalize(s) {
		try {
			return String(s || '')
				.toLowerCase()
				.normalize('NFD')
				.replace(/[\u0300-\u036f]/g, '')
				.trim();
		} catch (e) {
			return String(s || '').toLowerCase().trim();
		}
	}

	function updateCount(visible, total) {
		var el = document.getElementById('mkWhStorageCount');
		if (!el) return;
		el.innerHTML =
			'Showing <strong>1</strong> to <strong>' +
			String(visible) +
			'</strong> of <strong>' +
			String(total) +
			'</strong> storage' +
			(total === 1 ? '' : 's');
	}

	function bindRealtimeSearchFilter() {
		var $form = $('.mk-wh-filter-bar, .inv-filter-bar').first();
		if (!$form.length) return;
		if ($form.data('mkWhRealtimeBound')) return;
		$form.data('mkWhRealtimeBound', true);

		var $search = $form.find('input[name="search"]').first();
		var $table = $('#mkWhStorageTable');
		if (!$search.length || !$table.length) return;

		var $rows = $table.find('tbody tr').filter(function () {
			// exclude empty-state row
			return $(this).find('td').length > 1;
		});
		var total = $rows.length;

		// Cache each row searchable text once (fast typing)
		$rows.each(function () {
			var $tr = $(this);
			var txt = normalize($tr.text());
			$tr.attr('data-mk-wh-search', txt);
		});

		function applyFilter() {
			var q = normalize($search.val());
			var visible = 0;
			$rows.each(function () {
				var $tr = $(this);
				var hay = $tr.attr('data-mk-wh-search') || '';
				var ok = !q || hay.indexOf(q) >= 0;
				$tr.toggle(ok);
				if (ok) visible++;
			});
			updateCount(visible, total);
		}

		var applyDebounced = debounce(applyFilter, 120);

		// Filter locally while typing. No reload, so it feels like Leads.
		$search.on('input', function () {
			applyDebounced();
		});

		// Prevent Enter from submitting (still keep Filters button for server-side filtering)
		$search.on('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
			}
		});

		applyFilter();
	}

	function initWarehouseList() {
		var $body = $(document.body);
		if ($body.attr('data-module') !== 'Warehouse' || $body.attr('data-view') !== 'List') {
			return;
		}
		if ($body.attr('data-app') !== 'INVENTORY') {
			return;
		}
		$body.addClass('mk-wh-list-ready');
		bindRealtimeSearchFilter();
	}

	$(function () {
		initWarehouseList();
	});
})(jQuery);
