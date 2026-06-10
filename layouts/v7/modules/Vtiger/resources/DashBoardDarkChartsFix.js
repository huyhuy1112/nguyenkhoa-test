/**
 * Dashboard chart theme sync — dark plot in dark mode only; restore white plot in light mode.
 */
(function ($) {
  "use strict";

  var DARK_GRID = {
    drawGridlines: true,
    gridLineColor: "rgba(255,255,255,0.1)",
    borderColor: "rgba(255,255,255,0.12)",
    borderWidth: 0.5,
    background: "#0D1117",
    shadow: false,
  };

  var LIGHT_GRID = {
    drawGridlines: true,
    gridLineColor: "#f1f5f9",
    borderColor: "#e2e8f0",
    borderWidth: 0.5,
    background: "#ffffff",
    shadow: false,
  };

  var DARK_SERIES = [
    "#60A5FA",
    "#FBBF24",
    "#34D399",
    "#A78BFA",
    "#FDBB2C",
    "#38BDF8",
    "#818CF8",
    "#CBD5E1",
  ];

  var LIGHT_SERIES = [
    "#2563EB",
    "#F59E0B",
    "#10B981",
    "#8B5CF6",
    "#FDBB2C",
    "#0EA5E9",
    "#6366F1",
    "#64748B",
  ];

  var DARK_TICK = "#E2E8F0";
  var LIGHT_TICK = "#64748B";

  function isDark() {
    return (
      document.documentElement &&
      document.documentElement.getAttribute("data-theme") === "dark"
    );
  }

  function normBg(bg) {
    return String(bg || "")
      .toLowerCase()
      .replace(/\s/g, "");
  }

  function isLightGrid(bg) {
    var s = normBg(bg);
    return (
      !s ||
      s === "#ffffff" ||
      s === "#fff" ||
      s === "white" ||
      s === "rgb(255,255,255)" ||
      s.indexOf("255,255,255") !== -1
    );
  }

  function isDarkGrid(bg) {
    var s = normBg(bg);
    return (
      s === "#0d1117" ||
      s === "#12161f" ||
      s.indexOf("23,27,36") !== -1 ||
      s.indexOf("13,17,23") !== -1
    );
  }

  function getPlot($chart) {
    var $target = $chart.find(".jqplot-target").first();
    if (!$target.length) {
      $target = $chart.filter(".jqplot-target");
    }
    return $target.length ? $target.data("jqplot") : null;
  }

  function fixChartLayers($chart) {
    if (!$chart || !$chart.length) return;
    $chart
      .find("canvas.jqplot-series-canvas, canvas.jqplot-event-canvas")
      .css("background", "transparent");
    $chart.find(".jqplot-target").css("background", "transparent");
  }

  function syncLegendSwatches($chart, palette) {
    $chart
      .closest(".mk-chart-col, .dashboardWidgetContent")
      .find(".mk-dash-chart-legend-swatch")
      .each(function (i) {
        $(this).css("background-color", palette[i % palette.length]);
      });
  }

  function patchAxisTicks(plot, tickColor) {
    if (!plot || !plot.axes) return;
    ["xaxis", "yaxis", "y2axis", "x2axis"].forEach(function (name) {
      var ax = plot.axes[name];
      if (!ax) return;
      ax.tickOptions = ax.tickOptions || {};
      ax.tickOptions.textColor = tickColor;
      if (ax.labelOptions) {
        ax.labelOptions.textColor = tickColor;
      }
    });
  }

  function repaintChart($chart, grid, series, tickColor) {
    var plot = getPlot($chart);
    if (!plot || !plot.grid) {
      fixChartLayers($chart);
      return false;
    }

    plot.grid.background = grid.background;
    plot.grid.gridLineColor = grid.gridLineColor;
    plot.grid.borderColor = grid.borderColor;
    plot.grid.drawGridlines = grid.drawGridlines;
    plot.grid.shadow = grid.shadow;
    plot.seriesColors = series.slice();
    patchAxisTicks(plot, tickColor);

    try {
      plot.replot({ resetAxes: false });
      syncLegendSwatches($chart, series);
      fixChartLayers($chart);
      return true;
    } catch (e) {
      if (window.console && console.warn) {
        console.warn("[MkDashChartThemeFix] replot failed", e);
      }
      fixChartLayers($chart);
      return false;
    }
  }

  function syncChartTheme($chart) {
    var plot = getPlot($chart);
    if (!plot || !plot.grid) {
      fixChartLayers($chart);
      return;
    }

    var bg = plot.grid.background;
    if (isDark()) {
      if (!isLightGrid(bg)) {
        fixChartLayers($chart);
        return;
      }
      repaintChart($chart, DARK_GRID, DARK_SERIES, DARK_TICK);
    } else {
      if (!isDarkGrid(bg)) {
        fixChartLayers($chart);
        return;
      }
      repaintChart($chart, LIGHT_GRID, LIGHT_SERIES, LIGHT_TICK);
    }
  }

  function fixWidget($widget) {
    $widget.find(".widgetChartContainer").each(function () {
      syncChartTheme($(this));
    });
  }

  function fixAll() {
    if ($("body").attr("data-view") !== "DashBoard") return;
    $("body[data-view='DashBoard'] li.dashboardWidget.loadcompleted").each(function () {
      fixWidget($(this));
    });
  }

  function watchWidgets() {
    var root = document.querySelector(".dashBoardTabContents");
    if (!root || typeof MutationObserver === "undefined") return;
    var obs = new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        if (m.type !== "attributes" || m.attributeName !== "class") return;
        var el = m.target;
        if (
          el.classList &&
          el.classList.contains("dashboardWidget") &&
          el.classList.contains("loadcompleted")
        ) {
          setTimeout(function () {
            fixWidget($(el));
          }, 200);
        }
      });
    });
    root.querySelectorAll("li.dashboardWidget").forEach(function (li) {
      obs.observe(li, { attributes: true, attributeFilter: ["class"] });
    });
  }

  $(document).ready(function () {
    watchWidgets();
    [400, 1200, 2500, 5000].forEach(function (ms) {
      setTimeout(fixAll, ms);
    });
  });

  $(document).ajaxComplete(function () {
    setTimeout(fixAll, 400);
  });

  if (window.MkTheme && typeof window.MkTheme.setTheme === "function") {
    var origSet = window.MkTheme.setTheme;
    window.MkTheme.setTheme = function () {
      origSet.apply(this, arguments);
      setTimeout(fixAll, 300);
      setTimeout(fixAll, 900);
    };
  }

  $(document).on("mk:theme-change", function () {
    setTimeout(fixAll, 300);
    setTimeout(fixAll, 900);
  });

  window.MkDashDarkChartsFix = { fixAll: fixAll, fixWidget: fixWidget };
})(jQuery);
