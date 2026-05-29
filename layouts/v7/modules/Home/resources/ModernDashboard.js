(function () {
	'use strict';

	/**
	 * Lightweight enhancer for the Modern Dashboard layout.
	 * Currently only wires simple click hooks; heavy logic or data fetching
	 * should continue to use existing Vtiger widgets and controllers.
	 */
	jQuery(function ($) {
		var $root = $('body[data-view="ModernDashboard"]');
		if ($root.length === 0) {
			return;
		}

		function initSalesChart() {
			var $el = $('#mkModernSalesChart');
			if ($el.length === 0) return;
			if (!$.fn || !$.fn.jqplot) return;

			var raw = $('#mkModernSalesChartData').val();
			if (!raw) return;
			var chartData = null;
			try {
				chartData = JSON.parse(raw);
			} catch (e) {
				if (window.console && window.console.error) {
					window.console.error('[ModernDashboard] invalid chart JSON', e);
				}
				return;
			}
			if (!chartData || !chartData.series || !chartData.ticks) return;

			// Render a simple modern line chart (theme wrapper will refine options)
			try {
				$.jqplot('mkModernSalesChart', [chartData.series], {
					seriesDefaults: {
						showMarker: true,
						markerOptions: { size: 6 },
					},
					axes: {
						xaxis: {
							renderer: $.jqplot.CategoryAxisRenderer,
							ticks: chartData.ticks,
							tickOptions: { angle: 0 },
						},
						yaxis: {
							min: 0,
							tickOptions: {
								formatString: '%d',
							},
						},
					},
					highlighter: {
						show: true,
						tooltipAxes: 'y',
						formatString: chartData.currencySymbol ? (chartData.currencySymbol + '%s') : '%s',
					},
					legend: { show: false },
				});

				if (window.console && window.console.info) {
					window.console.info('[ModernDashboard] Sales chart rendered', chartData);
				}
			} catch (e) {
				if (window.console && window.console.error) {
					window.console.error('[ModernDashboard] Sales chart render failed', e);
				}
			}
		}

		// Render after DOM ready; retry after AJAX in case content swaps.
		initSalesChart();
		$(document).on('ajaxComplete mk.pjax.complete', function () {
			initSalesChart();
		});

		// Quick-create shortcut: open the standard global quick-create menu if available.
		$root.on('click', '.mk-modern-btn[data-trigger="quick-create"]', function (e) {
			e.preventDefault();
			var $quickCreate = $('#quickCreateModules .dropdown-toggle, .quickCreateModule .dropdown-toggle').first();
			if ($quickCreate.length) {
				$quickCreate.trigger('click');
			}
		});

		// "New report" shortcut: navigate to Reports module if the user has access.
		$root.on('click', '.mk-modern-btn[data-trigger="new-report"]', function (e) {
			e.preventDefault();
			window.location.href = 'index.php?module=Reports&view=Index';
		});
	});
})();

