/**
 * DashBoard-only jqPlot visual theme (does not modify ModernDashboard).
 * Wraps $.fn.jqplot for charts inside dashboard widgets / fullscreen preview.
 * Does not alter series data, counts, or chart types — presentation only.
 */
(function ($) {
  "use strict";

  /** CRM-style palette: brand gold + refined blues/teals (HubSpot / SF-inspired) */
  var PALETTE_LIGHT = [
    "#2563EB",
    "#F59E0B",
    "#10B981",
    "#8B5CF6",
    "#FDBB2C",
    "#0EA5E9",
    "#6366F1",
    "#EC4899",
    "#64748B",
    "#14B8A6",
  ];

  var PALETTE_DARK = [
    "#60A5FA",
    "#FBBF24",
    "#34D399",
    "#A78BFA",
    "#FDBB2C",
    "#38BDF8",
    "#818CF8",
    "#CBD5E1",
    "#F472B6",
    "#FB7185",
  ];

  function isDarkTheme() {
    return (
      document.documentElement &&
      document.documentElement.getAttribute("data-theme") === "dark"
    );
  }

  function getPalette() {
    return isDarkTheme() ? PALETTE_DARK : PALETTE_LIGHT;
  }

  function getChartColors() {
    if (isDarkTheme()) {
      return {
        gridLine: "rgba(255,255,255,0.1)",
        border: "rgba(255,255,255,0.12)",
        background: "#0D1117",
        axis: "#E2E8F0",
        pointLabel: "#E8EDF5",
      };
    }
    return {
      gridLine: "#F1F5F9",
      border: "#EEF2F7",
      background: "#FFFFFF",
      axis: "#64748B",
      pointLabel: "#94A3B8",
    };
  }

  var MAX_TICK_CHARS = 14;
  var SHORT_TICK_LEN = 8;
  var FEW_CATEGORY_MAX = 4;

  function isDashBoardView() {
    return (
      document.body &&
      document.body.getAttribute("data-view") === "DashBoard"
    );
  }

  function shouldThemeElement($el) {
    if (!$el || !$el.length) return false;
    if (!isDashBoardView()) return false;
    return (
      $el.closest(".dashboardWidget").length > 0 ||
      $el.closest(".fullscreenview").length > 0 ||
      $el.closest(".fullscreencontents").length > 0
    );
  }

  function escapeHtml(s) {
    if (s == null || s === "") return "";
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function tickToString(t) {
    if (t == null) return "";
    if (typeof t === "string") return t;
    if ($.isArray(t)) {
      if (t.length >= 2 && t[1] != null) return String(t[1]);
      return String(t[0]);
    }
    return String(t);
  }

  function shouldTruncateTickLabel(s) {
    if (!s || s.length <= MAX_TICK_CHARS) return false;
    if (/^-?\d+[.,]?\d*$/.test(String(s).trim())) return false;
    return true;
  }

  /**
   * Truncate category tick labels; keep full strings for tooltips (stored on $plotRoot).
   */
  function truncateCategoryTicks(opts, axisKey, $plotRoot) {
    if (!opts.axes || !opts.axes[axisKey]) return;
    var ax = opts.axes[axisKey];
    if (ax.renderer !== $.jqplot.CategoryAxisRenderer) return;
    var ticks = ax.ticks;
    if (!ticks || !$.isArray(ticks) || ticks.length === 0) return;

    var full = [];
    var out = [];
    var i;
    for (i = 0; i < ticks.length; i++) {
      var raw = ticks[i];
      var s = tickToString(raw);
      full.push(s);
      var display = shouldTruncateTickLabel(s)
        ? s.substring(0, MAX_TICK_CHARS - 1) + "\u2026"
        : s;
      if ($.isArray(raw) && raw.length >= 1) {
        var pair = raw.slice();
        if (pair.length >= 2) {
          pair[1] = display;
        } else {
          pair.push(display);
        }
        out.push(pair);
      } else {
        out.push(display);
      }
    }

    var store = $plotRoot.data("mkDashTickFull") || {};
    store[axisKey] = full;
    $plotRoot.data("mkDashTickFull", store);
    ax.ticks = out;
  }

  function normalizeTickFontSize(tickOpts) {
    if (!tickOpts) tickOpts = {};
    tickOpts.fontSize = "10px";
    tickOpts.textColor = tickOpts.textColor || getChartColors().axis;
    return tickOpts;
  }

  /**
   * X-axis: short labels 0°; medium length -25°; very long rely on truncation + 0°.
   */
  function applySmartXTickAngle(opts) {
    if (!opts.axes || !opts.axes.xaxis || !opts.axes.xaxis.ticks) return;
    var ticks = opts.axes.xaxis.ticks;
    var maxLen = 0;
    var i;
    for (i = 0; i < ticks.length; i++) {
      var s = tickToString(ticks[i]);
      if (s.length > maxLen) maxLen = s.length;
    }
    var ax = opts.axes.xaxis;
    ax.tickOptions = $.extend(true, {}, ax.tickOptions);
    ax.tickOptions = normalizeTickFontSize(ax.tickOptions);
    if (ticks.length <= FEW_CATEGORY_MAX) {
      ax.tickOptions.angle = 0;
    } else if (maxLen <= SHORT_TICK_LEN) {
      ax.tickOptions.angle = 0;
    } else if (maxLen > MAX_TICK_CHARS) {
      ax.tickOptions.angle = 0;
    } else {
      ax.tickOptions.angle = -22;
    }
  }

  /** Y / X2: keep horizontal, small type */
  function applySecondaryAxisTickStyles(opts) {
    var names = ["yaxis", "y2axis", "x2axis"];
    if (!opts.axes) opts.axes = {};
    names.forEach(function (name) {
      if (!opts.axes[name]) return;
      var ax = opts.axes[name];
      ax.tickOptions = normalizeTickFontSize($.extend(true, {}, ax.tickOptions));
      var ang = ax.tickOptions.angle;
      if (typeof ang === "number" && Math.abs(ang) >= 20) {
        ax.tickOptions.angle = 0;
      }
      ax.labelOptions = $.extend(
        true,
        { fontSize: "11px", textColor: getChartColors().axis },
        ax.labelOptions || {}
      );
    });
  }

  function styleXaxisLabels(opts) {
    if (!opts.axes || !opts.axes.xaxis) return;
    var ax = opts.axes.xaxis;
    ax.labelOptions = $.extend(
      true,
      { fontSize: "11px", textColor: getChartColors().axis },
      ax.labelOptions || {}
    );
  }

  function themePieLike(opts) {
    var sd = opts.seriesDefaults || {};
    var ro = sd.rendererOptions || {};
    opts.seriesDefaults = $.extend(true, {}, sd, {
      shadow: false,
      rendererOptions: $.extend(true, {}, ro, {
        sliceMargin: 3,
        shadow: false,
        highlightMouseOver: true,
      }),
    });
  }

  function themeBarLike(opts, stacked) {
    var sd = opts.seriesDefaults || {};
    var ro = sd.rendererOptions || {};
    opts.seriesDefaults = $.extend(true, {}, sd, {
      shadow: false,
      rendererOptions: $.extend(true, {}, ro, {
        shadowOffset: 0,
        shadowAlpha: 0,
        shadowDepth: 0,
        barMargin: stacked
          ? Math.min(ro.barMargin != null ? ro.barMargin : 14, 16)
          : Math.min(ro.barMargin != null ? ro.barMargin : 12, 18),
        barPadding: ro.barPadding != null ? ro.barPadding : 8,
        barWidth: ro.barWidth != null ? ro.barWidth : 28,
      }),
      pointLabels: $.extend(
        true,
        {
          show: stacked ? false : sd.pointLabels ? sd.pointLabels.show !== false : true,
          hideZeros: true,
          textColor: isDarkTheme() ? getChartColors().pointLabel : "#334155",
          fontSize: "10px",
          formatString: "%d",
        },
        sd.pointLabels || {}
      ),
    });
  }

  function themeLineLike(opts) {
    opts.seriesDefaults = $.extend(
      true,
      {
        shadow: false,
        lineWidth: 2.25,
        markerOptions: {
          shadow: false,
          size: 7,
          lineWidth: 2,
        },
      },
      opts.seriesDefaults || {}
    );
    if (opts.cursor) {
      opts.cursor = $.extend(true, { show: true, zoom: false }, opts.cursor);
    }
  }

  function isStackedMultibar(opts) {
    return !!(
      opts.stackSeries &&
      opts.seriesDefaults &&
      opts.seriesDefaults.renderer === $.jqplot.BarRenderer
    );
  }

  /** How many legend rows (series) — for bottom padding */
  function countLegendSeries(opts, chartData) {
    if (opts.legend && opts.legend.labels && $.isArray(opts.legend.labels)) {
      return opts.legend.labels.length;
    }
    if ($.isArray(chartData)) {
      return chartData.length;
    }
    return 0;
  }

  function chainHighlighterTooltip(opts, $plotRoot) {
    var hi = $.extend(true, {}, opts.highlighter || {});
    var prev = hi.tooltipContentEditor;
    hi.tooltipContentEditor = function (str, seriesIndex, pointIndex, plot) {
      var inner =
        typeof prev === "function"
          ? prev.call(this, str, seriesIndex, pointIndex, plot)
          : str;
      var store = $plotRoot.data("mkDashTickFull");
      if (!store) return inner;
      var cat =
        store.xaxis &&
        store.xaxis[pointIndex] != null &&
        store.xaxis[pointIndex] !== ""
          ? store.xaxis[pointIndex]
          : null;
      if (!cat) return inner;
      return (
        '<div class="mk-dash-tip">' +
        '<div class="mk-dash-tip-cat">' +
        escapeHtml(cat) +
        "</div>" +
        '<div class="mk-dash-tip-body">' +
        inner +
        "</div></div>"
      );
    };
    opts.highlighter = hi;
  }

  function applyDashboardChartTheme(options, $plotRoot, chartData) {
    var opts = $.extend(true, {}, options || {});
    var colors = getChartColors();
    var palette = getPalette();

    if ($plotRoot && $plotRoot.length) {
      $plotRoot.removeData("mkDashTickFull");
    }

    opts.seriesColors = palette.slice();

    opts.grid = $.extend(
      true,
      {
        drawGridlines: true,
        gridLineColor: colors.gridLine,
        borderColor: colors.border,
        borderWidth: 0.5,
        shadow: false,
        background: colors.background,
      },
      opts.grid || {}
    );

    opts.gridPadding = $.extend(
      true,
      { top: 8, right: 10, bottom: 10, left: 10 },
      opts.gridPadding || {}
    );

    opts.axesDefaults = $.extend(
      true,
      {
        tickOptions: {
          fontSize: "10px",
          textColor: colors.axis,
        },
        labelOptions: {
          fontSize: "11px",
          textColor: colors.axis,
        },
      },
      opts.axesDefaults || {}
    );

    if ($plotRoot && $plotRoot.length) {
      truncateCategoryTicks(opts, "xaxis", $plotRoot);
      truncateCategoryTicks(opts, "yaxis", $plotRoot);
    }

    applySmartXTickAngle(opts);
    styleXaxisLabels(opts);
    applySecondaryAxisTickStyles(opts);

    if (opts.seriesDefaults && opts.seriesDefaults.pointLabels) {
      opts.seriesDefaults.pointLabels = $.extend(
        true,
        { textColor: colors.pointLabel, fontSize: "10px" },
        opts.seriesDefaults.pointLabels
      );
      var plfs = opts.seriesDefaults.pointLabels.fontSize;
      if (typeof plfs === "string" && parseFloat(plfs) > 11) {
        opts.seriesDefaults.pointLabels.fontSize = "10px";
      }
      if (typeof plfs === "number" && plfs > 11) {
        opts.seriesDefaults.pointLabels.fontSize = "10px";
      }
    }

    var Pie = $.jqplot && $.jqplot.PieRenderer;
    var Donut = $.jqplot && $.jqplot.DonutRenderer;
    var Bar = $.jqplot && $.jqplot.BarRenderer;
    var Funnel = $.jqplot && $.jqplot.FunnelRenderer;
    var sd0 = opts.seriesDefaults || {};
    var rend = sd0.renderer;
    var stacked = isStackedMultibar(opts);

    if (Pie && rend === Pie) themePieLike(opts);
    else if (Donut && rend === Donut) themePieLike(opts);
    else if (Funnel && rend === Funnel) {
      opts.seriesDefaults = $.extend(true, { shadow: false }, sd0);
    } else if (Bar && rend === Bar) themeBarLike(opts, stacked);
    else themeLineLike(opts);

    var leg = $.extend(true, {}, opts.legend || {});
    var legCount = countLegendSeries(opts, chartData);
    var showLeg = leg.show !== false && leg.show !== 0;
    var legendCols =
      legCount > 10 ? 6 : legCount > 6 ? 5 : 4;

    leg.border = "none";
    leg.background = "transparent";
    leg.shadow = false;
    leg.placement = "outsideGrid";
    leg.location = "s";
    leg.xoffset = 0;
    leg.yoffset = 6;

    if (showLeg) {
      leg.fontSize = legCount > 12 ? "10px" : "11px";
    } else {
      leg.fontSize = leg.fontSize || "10px";
    }

    if (showLeg) {
      var bottomPad;
      if (legCount > 0) {
        var cols = Math.min(legendCols, Math.max(2, legCount));
        var rows = Math.ceil(legCount / cols);
        rows = Math.max(1, rows);
        bottomPad = 16 + rows * 20;
        bottomPad = Math.min(110, Math.max(44, bottomPad));
      } else {
        bottomPad = 40;
      }
      opts.gridPadding = $.extend(
        true,
        { top: 12, right: 14, left: 14, bottom: bottomPad },
        opts.gridPadding || {}
      );
    } else {
      opts.gridPadding = $.extend(
        true,
        { top: 12, right: 14, left: 14, bottom: 28 },
        opts.gridPadding || {}
      );
    }

    if ($plotRoot && $plotRoot.closest(".fullscreenview").length) {
      opts.gridPadding = opts.gridPadding || {};
      opts.gridPadding.left = Math.max(
        parseInt(opts.gridPadding.left, 10) || 0,
        76
      );
    }

    opts.legend = leg;

    var isLineChart =
      opts.seriesDefaults &&
      opts.seriesDefaults.renderer &&
      $.jqplot &&
      opts.seriesDefaults.renderer === $.jqplot.LineRenderer;

    if (isLineChart) {
      opts.highlighter = $.extend(
        true,
        {
          show: true,
          showMarker: false,
          tooltipAxes: "xy",
          fadeTooltip: true,
          tooltipFadeSpeed: "fast",
          sizeAdjust: 4,
          tooltipOffset: 8,
          useAxesFormatters: true,
          tooltipClass: "mk-dash-jqplot-tooltip",
        },
        opts.highlighter || {}
      );
      if ($plotRoot && $plotRoot.length) {
        chainHighlighterTooltip(opts, $plotRoot);
      }
    } else if (opts.highlighter) {
      opts.highlighter.show = false;
    }

    opts.shadow = opts.shadow != null ? opts.shadow : false;

    return opts;
  }

  /** Set true to re-enable jqPlot theming after charts load reliably */
  var MK_DASH_CHART_THEME_ENABLED = false;

  function installWrapper() {
    if (!MK_DASH_CHART_THEME_ENABLED) {
      return;
    }
    if (!$.fn || !$.fn.jqplot || $.fn.jqplot.__mkDashBoardChartsWrapped) {
      return;
    }
    var original = $.fn.jqplot;
    $.fn.jqplot = function (data, options) {
      var $root = this.first();
      if (!shouldThemeElement($root)) {
        return original.apply(this, arguments);
      }
      var baseOpts = options || {};
      try {
        var themed = applyDashboardChartTheme(baseOpts, $root, data);
        return original.call(this, data, themed);
      } catch (themeErr) {
        if (window.console && console.warn) {
          console.warn("[DashBoardChartsTheme] theme failed, using defaults", themeErr);
        }
        return original.call(this, data, baseOpts);
      }
    };
    $.fn.jqplot.__mkDashBoardChartsWrapped = true;
  }

  $(function () {
    installWrapper();
  });

  $(document).ajaxComplete(function () {
    installWrapper();
  });

  /* Do not reload widgets on theme change — loadWidget prepends HTML and would duplicate headers */
  $(document).on("mk:theme-change", function () {
    installWrapper();
  });
})(jQuery);
