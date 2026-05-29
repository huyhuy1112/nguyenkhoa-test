(function () {
	'use strict';

	/**
	 * ModernDashboard jqPlot theme layer (scoped).
	 *
	 * Goals:
	 * - keep jqPlot as the renderer, but modernize visuals via option overrides
	 * - reusable theme for any jqPlot charts rendered inside ModernDashboard
	 * - zero expensive rendering loops; only touches options at init time
	 */
	function isModernDashboardScope(el) {
		try {
			var $body = jQuery('body[data-view="ModernDashboard"]');
			if ($body.length === 0) return false;
			if (!el) return true;
			return jQuery(el).closest('.mk-modern-dashboard-root').length > 0;
		} catch (e) {
			return false;
		}
	}

	function deepMerge(target, source) {
		if (!source) return target;
		for (var key in source) {
			if (!Object.prototype.hasOwnProperty.call(source, key)) continue;
			var sv = source[key];
			var tv = target[key];
			if (sv && typeof sv === 'object' && !Array.isArray(sv)) {
				target[key] = deepMerge(tv && typeof tv === 'object' && !Array.isArray(tv) ? tv : {}, sv);
			} else if (typeof tv === 'undefined') {
				target[key] = sv;
			}
		}
		return target;
	}

	function forceAxisLabelAnglesToZero(options) {
		if (!options || !options.axes) return;
		['xaxis', 'x2axis', 'yaxis', 'y2axis'].forEach(function (axisKey) {
			var axis = options.axes[axisKey];
			if (!axis || !axis.tickOptions) return;
			if (typeof axis.tickOptions.angle !== 'undefined' && axis.tickOptions.angle < 0) {
				axis.tickOptions.angle = 0;
			}
		});
	}

	function normalizeBarOptions(options) {
		if (!options) return;
		if (!options.seriesDefaults) options.seriesDefaults = {};
		if (!options.seriesDefaults.rendererOptions) options.seriesDefaults.rendererOptions = {};

		// Better bar proportions (thicker bars, less "spindly" look)
		if (typeof options.seriesDefaults.rendererOptions.barWidth === 'undefined') {
			options.seriesDefaults.rendererOptions.barWidth = 18;
		}
		if (typeof options.seriesDefaults.rendererOptions.barPadding === 'undefined') {
			options.seriesDefaults.rendererOptions.barPadding = 6;
		}
		if (typeof options.seriesDefaults.rendererOptions.barMargin === 'undefined') {
			options.seriesDefaults.rendererOptions.barMargin = 10;
		}
		// Remove "old school" shadow look
		if (typeof options.seriesDefaults.shadow === 'undefined') options.seriesDefaults.shadow = false;
		if (typeof options.seriesDefaults.shadowAngle === 'undefined') options.seriesDefaults.shadowAngle = 0;
		if (typeof options.seriesDefaults.shadowDepth === 'undefined') options.seriesDefaults.shadowDepth = 0;
	}

	function normalizePieDonutOptions(options) {
		if (!options) return;
		if (!options.seriesDefaults) options.seriesDefaults = {};
		if (!options.seriesDefaults.rendererOptions) options.seriesDefaults.rendererOptions = {};

		// jqPlot PieRenderer supports these options
		var ro = options.seriesDefaults.rendererOptions;
		if (typeof ro.shadow === 'undefined') ro.shadow = false;
		if (typeof ro.sliceMargin === 'undefined') ro.sliceMargin = 2;
		if (typeof ro.padding === 'undefined') ro.padding = 10;
		if (typeof ro.startAngle === 'undefined') ro.startAngle = -90;
		if (typeof ro.highlightMouseOver === 'undefined') ro.highlightMouseOver = true;

		// DonutRenderer enhancements (if used)
		if (typeof ro.innerDiameter === 'undefined') ro.innerDiameter = 70;
		if (typeof ro.ringMargin === 'undefined') ro.ringMargin = 4;
	}

	function getModernThemeOptions() {
		// Brand-ish modern palette (muted but premium)
		var seriesColors = [
			'#F4A300', // amber
			'#1F4B64', // deep teal
			'#4F46E5', // indigo
			'#10B981', // emerald
			'#EF4444', // red
			'#8B5CF6', // violet
			'#06B6D4', // cyan
		];

		return {
			seriesColors: seriesColors,
			grid: {
				background: 'transparent',
				borderColor: 'rgba(15,23,42,0.05)',
				borderWidth: 1.0,
				shadow: false,
				gridLineColor: 'rgba(15,23,42,0.045)',
				gridLineWidth: 1.0,
				drawBorder: false,
			},
			axesDefaults: {
				pad: 1.02,
				drawMajorGridlines: true,
				drawMinorGridlines: false,
				drawMajorTickMarks: false,
				borderWidth: 0,
				borderColor: 'transparent',
				tickOptions: {
					textColor: '#94A3B8',
					fontSize: '11px',
					fontFamily: 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
					formatString: '%d',
					mark: 'outside',
					showMark: false,
				},
				labelOptions: {
					textColor: '#64748B',
					fontSize: '11px',
					fontFamily: 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
				},
			},
			legend: {
				show: true,
				placement: 'outsideGrid',
				location: 'e',
				showSwatches: true,
				rendererOptions: {
					numberRows: 1,
				},
				fontSize: '11px',
				textColor: '#334155',
			},
			highlighter: {
				show: true,
				sizeAdjust: 6,
				tooltipLocation: 'n',
				tooltipOffset: 10,
				fadeTooltip: true,
				fadeSpeed: 120,
				bringSeriesToFront: true,
				useAxesFormatters: true,
				showMarker: true,
			},
			cursor: {
				show: false,
			},
			seriesDefaults: {
				shadow: false,
				lineWidth: 2.5,
				lineJoin: 'round',
				lineCap: 'round',
				markerOptions: {
					size: 7,
					style: 'filledCircle',
					shadow: false,
				},
				rendererOptions: {
					smooth: true,
				},
			},
		};
	}

	function formatCompactNumber(value) {
		var n = Number(value);
		if (!isFinite(n)) return String(value);
		var abs = Math.abs(n);
		if (abs >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'B';
		if (abs >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
		if (abs >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
		return String(Math.round(n));
	}

	function formatWithCommas(value) {
		var n = Number(value);
		if (!isFinite(n)) return String(value);
		return n.toLocaleString(undefined, { maximumFractionDigits: 0 });
	}

	function applyThemeToOptions(rawOptions) {
		var options = rawOptions || {};

		// Apply defaults only where not explicitly set by vtiger widgets
		var themed = deepMerge(jQuery.extend(true, {}, options), getModernThemeOptions());

		// Make tick labels horizontal where possible (vtiger hard-codes negative angles)
		forceAxisLabelAnglesToZero(themed);

		// Type-aware adjustments (best-effort)
		var rendererName = null;
		try {
			rendererName =
				(themed.seriesDefaults && themed.seriesDefaults.renderer && themed.seriesDefaults.renderer.name) ||
				(themed.series && themed.series[0] && themed.series[0].renderer && themed.series[0].renderer.name) ||
				null;
		} catch (e) {
			rendererName = null;
		}

		if (rendererName && /BarRenderer/i.test(rendererName)) {
			normalizeBarOptions(themed);
		}
		if (rendererName && (/PieRenderer/i.test(rendererName) || /DonutRenderer/i.test(rendererName))) {
			normalizePieDonutOptions(themed);
		}

		// Improve spacing inside plot area
		if (!themed.gridPadding) {
			themed.gridPadding = { top: 18, right: 18, bottom: 28, left: 44 };
		}

		// Axis-specific tweaks (modern spacing)
		if (!themed.axes) themed.axes = {};
		themed.axes.xaxis = themed.axes.xaxis || {};
		themed.axes.yaxis = themed.axes.yaxis || {};
		if (!themed.axes.xaxis.tickOptions) themed.axes.xaxis.tickOptions = {};
		if (!themed.axes.yaxis.tickOptions) themed.axes.yaxis.tickOptions = {};

		// Keep x ticks horizontal, tighter
		if (typeof themed.axes.xaxis.tickOptions.angle === 'undefined') themed.axes.xaxis.tickOptions.angle = 0;
		if (typeof themed.axes.xaxis.tickOptions.fontSize === 'undefined') themed.axes.xaxis.tickOptions.fontSize = '11px';

		// y-axis: compact formatting by default (helps reduce clutter)
		if (typeof themed.axes.yaxis.tickOptions.formatter === 'undefined') {
			themed.axes.yaxis.tickOptions.formatter = function (format, val) {
				return formatCompactNumber(val);
			};
		}

		// Prefer EnhancedLegendRenderer when available for better legend layout
		if (jQuery.jqplot && jQuery.jqplot.EnhancedLegendRenderer) {
			if (!themed.legend) themed.legend = {};
			if (typeof themed.legend.renderer === 'undefined') {
				themed.legend.renderer = jQuery.jqplot.EnhancedLegendRenderer;
			}
		}

		// Modern tooltip container class (styled via CSS)
		if (themed.highlighter) {
			themed.highlighter.tooltipClass = 'mk-modern-jqplot-tooltip';
			if (typeof themed.highlighter.tooltipContentEditor === 'undefined') {
				themed.highlighter.tooltipContentEditor = function (str, seriesIndex, pointIndex, plot) {
					try {
						var v = plot && plot.data && plot.data[seriesIndex] && plot.data[seriesIndex][pointIndex];
						var y = Array.isArray(v) ? v[1] : null;
						if (y === null || typeof y === 'undefined') return str;
						return '<div class="mk-modern-jqplot-tooltip__row"><span class="mk-modern-jqplot-tooltip__label">Value</span><span class="mk-modern-jqplot-tooltip__value">' + formatWithCommas(y) + '</span></div>';
					} catch (e) {
						return str;
					}
				};
			}
		}

		// Bar charts: slightly softer highlight + no shadows
		if (rendererName && /BarRenderer/i.test(rendererName)) {
			if (!themed.seriesDefaults) themed.seriesDefaults = {};
			if (!themed.seriesDefaults.rendererOptions) themed.seriesDefaults.rendererOptions = {};
			themed.seriesDefaults.rendererOptions.highlightMouseOver = true;
			themed.seriesDefaults.rendererOptions.shadow = false;
			themed.seriesDefaults.shadow = false;
		}

		// Line charts: nicer markers and no cursor
		if (!rendererName || /Line|Bezier/i.test(rendererName)) {
			themed.cursor = themed.cursor || {};
			themed.cursor.show = false;
		}

		return themed;
	}

	function installJqPlotWrapper() {
		if (!jQuery || !jQuery.fn || !jQuery.fn.jqplot) return false;
		if (jQuery.fn.jqplot.__mkModernWrapped) return;

		var original = jQuery.fn.jqplot;
		var wrapped = function (data, options) {
			try {
				if (isModernDashboardScope(this)) {
					options = applyThemeToOptions(options);
					if (window && window.console && window.console.debug) {
						window.console.debug('[ModernDashboardCharts] themed jqPlot options applied', {
							el: this && this[0],
							renderer:
								options &&
								options.seriesDefaults &&
								options.seriesDefaults.renderer &&
								(options.seriesDefaults.renderer.name || options.seriesDefaults.renderer),
						});
					}
				}
			} catch (e) {
				// fail open — never break chart rendering
			}
			return original.call(this, data, options);
		};

		wrapped.__mkModernWrapped = true;
		jQuery.fn.jqplot = wrapped;
		if (window && window.console && window.console.info) {
			window.console.info('[ModernDashboardCharts] jqPlot wrapper installed');
		}
		return true;
	}

	jQuery(function () {
		if (!isModernDashboardScope()) return;

		// Enable loud debug styling via query param: ?mkdebugCharts=1
		try {
			if (window.location && /(?:\\?|&)mkdebugCharts=1(?:&|$)/.test(window.location.search || '')) {
				jQuery('body').addClass('mk-debug-charts');
				if (window.console && window.console.warn) {
					window.console.warn('[ModernDashboardCharts] mkdebugCharts=1 enabled');
				}
			}
		} catch (e) {
			// ignore
		}

		// jqPlot is sometimes loaded after our script (or charts are rendered via AJAX).
		// Try to install immediately, then retry on common async hooks with light throttling.
		var installed = installJqPlotWrapper();
		var maxRetries = 20;
		var retries = 0;
		var retryTimer = null;

		function scheduleRetry(reason) {
			if (installed) return;
			if (retryTimer) return;
			if (retries >= maxRetries) return;
			retryTimer = window.setTimeout(function () {
				retryTimer = null;
				retries++;
				installed = installJqPlotWrapper();
				if (!installed && window.console && window.console.debug) {
					window.console.debug('[ModernDashboardCharts] retry install (not ready)', { reason: reason, retries: retries });
				}
			}, 150);
		}

		// Quick initial retries for late-loaded jqPlot
		if (!installed) {
			scheduleRetry('initial');
			window.setTimeout(function () { scheduleRetry('t+500'); }, 500);
			window.setTimeout(function () { scheduleRetry('t+1000'); }, 1000);
			window.setTimeout(function () { scheduleRetry('t+2000'); }, 2000);
		}

		// AJAX re-renders (dashboard widgets, PJAX) may load jqPlot or rebuild DOM.
		jQuery(document).on('ajaxComplete mk.pjax.complete', function () {
			scheduleRetry('ajaxComplete');
		});
	});
})();

