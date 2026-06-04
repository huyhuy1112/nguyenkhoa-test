/**
 * DashBoard-only jqPlot visual theme (does not modify ModernDashboard).
 * Wraps $.fn.jqplot for charts inside dashboard widgets / fullscreen preview.
 * Does not alter series data, counts, or chart types — presentation only.
 */
(function ($) {
  "use strict";

  /** Site-aligned palette: slate blue, amber, teal, muted indigo, soft rose (sparingly), neutrals */
  var PALETTE_LIGHT = [
    "#FDBB2C",
    "#40627E",
    "#14B8A6",
    "#6366F1",
    "#1F4B64",
    "#F59E0B",
    "#0F766E",
    "#94A3B8",
    "#475569",
    "#A5B4FC",
  ];

  var PALETTE_DARK = [
    "#FDBB2C",
    "#16D8FF",
    "#22C55E",
    "#6366F1",
    "#F472B6",
    "#F59E0B",
    "#94A3B8",
    "#38BDF8",
    "#A78BFA",
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
        gridLine: "rgba(255,255,255,0.06)",
        border: "rgba(255,255,255,0.08)",
        background: "#171B24",
        axis: "#94A3B8",
        pointLabel: "#94A3B8",
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

  var MAX_TICK_CHARS = 16;
  var SHORT_TICK_LEN = 10;
  var ANGLE_TICK_THRESHOLD = 10;

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
    if (maxLen <= SHORT_TICK_LEN) {
      ax.tickOptions.angle = 0;
    } else if (maxLen > MAX_TICK_CHARS) {
      ax.tickOptions.angle = 0;
    } else {
      ax.tickOptions.angle = -25;
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
          ? Math.min(ro.barMargin != null ? ro.barMargin : 10, 12)
          : Math.min(ro.barMargin != null ? ro.barMargin : 12, 14),
        barPadding: ro.barPadding != null ? ro.barPadding : 6,
        barWidth: ro.barWidth != null ? ro.barWidth : 22,
      }),
      pointLabels: $.extend(
        true,
        {
          show: sd.pointLabels ? sd.pointLabels.show : true,
          textColor: "#94A3B8",
          fontSize: "10px",
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
        tickRenderer:
          $.jqplot && $.jqplot.CanvasAxisTickRenderer
            ? $.jqplot.CanvasAxisTickRenderer
            : undefined,
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

    if (showLeg && $.jqplot && $.jqplot.EnhancedLegendRenderer) {
      leg.renderer = $.jqplot.EnhancedLegendRenderer;
      leg.rendererOptions = $.extend(
        true,
        {
          textColor: "#64748B",
          borderAlpha: 0,
          rowSpacing: "0.12em",
          numberColumns: legendCols,
        },
        leg.rendererOptions || {}
      );
    }

    leg.border = "none";
    leg.background = "transparent";
    leg.shadow = false;
    leg.placement = "outsideGrid";
    leg.location = "sw";
    leg.xoffset = 0;
    leg.yoffset = 0;

    if (showLeg) {
      leg.fontSize = legCount > 12 ? "10px" : "11px";
    } else {
      leg.fontSize = leg.fontSize || "10px";
    }

    if (showLeg) {
      var bottomPad;
      if (legCount > 0) {
        var rows = Math.ceil(legCount / legendCols);
        rows = Math.max(1, rows);
        bottomPad = 12 + rows * 18;
        bottomPad = Math.min(96, Math.max(26, bottomPad));
      } else {
        bottomPad = 36;
      }
      opts.gridPadding = $.extend(true, { bottom: bottomPad }, opts.gridPadding || {});
    }

    opts.legend = leg;

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

    opts.shadow = opts.shadow != null ? opts.shadow : false;

    return opts;
  }

  function installWrapper() {
    if (!$.fn || !$.fn.jqplot || $.fn.jqplot.__mkDashBoardChartsWrapped) {
      return;
    }
    var original = $.fn.jqplot;
    $.fn.jqplot = function (data, options) {
      var $root = this.first();
      if (!shouldThemeElement($root)) {
        return original.apply(this, arguments);
      }
      var themed = applyDashboardChartTheme(options || {}, $root, data);
      return original.call(this, data, themed);
    };
    $.fn.jqplot.__mkDashBoardChartsWrapped = true;
  }

  $(function () {
    installWrapper();
  });

  $(document).ajaxComplete(function () {
    installWrapper();
  });

  $(document).on("mk:theme-change", function () {
    installWrapper();
    $(".dashboardWidget.loadcompleted").each(function () {
      var $w = $(this);
      if (
        $w.data("name") &&
        typeof Vtiger_DashBoard_Js !== "undefined" &&
        Vtiger_DashBoard_Js.currentInstance
      ) {
        try {
          Vtiger_DashBoard_Js.currentInstance.loadWidget($w);
        } catch (e) { /* ignore */ }
      }
    });
  });
})(jQuery);
