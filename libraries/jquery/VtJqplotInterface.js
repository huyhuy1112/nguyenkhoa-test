/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

var vtJqPlotInterface = function() {

    this.legendPlacement = 'outsideGrid';

    this.dashboardChartColors = [
        '#2563EB', '#F59E0B', '#10B981', '#8B5CF6',
        '#FDBB2C', '#0EA5E9', '#6366F1', '#64748B'
    ];

    this.getDashboardLegendBottomPad = function(labelCount) {
        var n = labelCount || 0;
        var cols = n > 6 ? 4 : n > 4 ? 3 : 2;
        var rows = Math.max(1, Math.ceil(n / cols));
        return 24 + rows * 22;
    };

    /** Bottom grid padding for category axis (salesperson names on multibar) */
    this.getDashboardXAxisBottomPad = function(ticks, tickAngle) {
        var maxLen = 0;
        var i;
        var label;
        if (!ticks || !ticks.length) {
            return 40;
        }
        for (i = 0; i < ticks.length; i++) {
            label = ticks[i];
            if (typeof label !== 'string' && label != null && label[1] != null) {
                label = label[1];
            }
            if (label && label.length > maxLen) {
                maxLen = label.length;
            }
        }
        var angle = tickAngle || 0;
        if (angle === 0) {
            return Math.min(120, Math.max(52, 28 + Math.ceil(maxLen * 7.5)));
        }
        if (angle > -35) {
            return Math.min(96, Math.max(48, 32 + Math.ceil(maxLen * 5)));
        }
        return Math.min(110, Math.max(56, 36 + Math.ceil(maxLen * 4)));
    };

    /** HTML legend below chart — jqPlot table legend often overlaps bars on dashboard */
    this.mountDashboardLegend = function(labels) {
        var $root = this.element;
        var $host = $root.closest('.mk-chart-col');
        if (!$host.length) {
            $host = $root.parent();
        }
        $host.find('.mk-dash-chart-legend').remove();
        $root.find('.mk-dash-chart-legend').remove();
        $root.find('table.jqplot-table-legend').remove();
        if (!labels || !labels.length) {
            return;
        }
        var colors = this.dashboardChartColors;
        var $wrap = jQuery('<div class="mk-dash-chart-legend"></div>');
        var i;
        for (i = 0; i < labels.length; i++) {
            var text = labels[i] != null ? String(labels[i]) : '';
            var c = colors[i % colors.length];
            var $item = jQuery(
                '<span class="mk-dash-chart-legend-item"></span>'
            );
            jQuery('<span class="mk-dash-chart-legend-swatch"></span>')
                .css('background-color', c)
                .appendTo($item);
            jQuery('<span class="mk-dash-chart-legend-label"></span>')
                .text(text)
                .appendTo($item);
            $wrap.append($item);
        }
        $host.append($wrap);
    };

    /** Salesperson names in the gap between plot and stage legend (readable, not clipped) */
    this.mountDashboardXLabels = function(ticks) {
        var $root = this.element;
        var $host = $root.closest('.mk-chart-col');
        if (!$host.length) {
            $host = $root.parent();
        }
        var $wrap = $host.find('.mk-dash-chart-xlabels').first();
        if (!$wrap.length) {
            $wrap = jQuery('<div class="mk-dash-chart-xlabels" aria-label="Salesperson"></div>');
            $root.after($wrap);
        }
        if ($wrap.find('.mk-dash-chart-xlabel').length > 0) {
            $wrap.css({ display: 'flex', visibility: 'visible', opacity: 1 });
            return;
        }
        $wrap.empty();
        if (!ticks || !ticks.length) {
            $wrap.hide();
            return;
        }
        $wrap.css('display', 'flex').attr('role', 'list');
        var i;
        var text;
        for (i = 0; i < ticks.length; i++) {
            text = ticks[i];
            if (typeof text !== 'string' && text != null && text[1] != null) {
                text = text[1];
            }
            text = text != null ? String(text) : '';
            jQuery('<span class="mk-dash-chart-xlabel" role="listitem"></span>')
                .text(text)
                .attr('title', text)
                .appendTo($wrap);
        }
    };

    this.isFullscreenChart = function() {
        return (
            this.isDashboardChart() &&
            this.element.closest('.fullscreenview').length > 0
        );
    };

    /** Left grid padding so Y-axis tick numbers are not clipped (wider in fullscreen modal) */
    this.getDashboardYAxisLeftPad = function(yMaxValue) {
        var fs = this.isFullscreenChart();
        var base = fs ? 64 : 14;
        var n = Math.ceil(Math.abs(parseFloat(yMaxValue) || 0));
        var digits = n > 0 ? String(n).length : 1;
        var pad = base + Math.max(0, digits - 2) * 10;
        if (fs) {
            pad = Math.max(pad, 72);
        }
        return pad;
    };

    this.getDashboardPlotShell = function(labelCount, htmlLegend) {
        var bottom = htmlLegend ? 14 : this.getDashboardLegendBottomPad(labelCount);
        var left = this.getDashboardYAxisLeftPad(0);
        var right = this.isFullscreenChart() ? 24 : 14;
        if (this.isFullscreenChart()) {
            left = Math.max(left, 72);
        }
        return {
            gridPadding: { top: 12, right: right, left: left, bottom: bottom },
            grid: {
                drawGridlines: true,
                gridLineColor: '#f1f5f9',
                borderColor: '#e2e8f0',
                borderWidth: 0.5,
                background: '#ffffff',
                shadow: false
            },
            seriesColors: this.dashboardChartColors.slice()
        };
    };

    this.isDashboardChart = function() {
        if (!document.body || this.element.closest('.dashboardWidget, .fullscreenview').length === 0) {
            return false;
        }
        var view = document.body.getAttribute('data-view');
        return view === 'DashBoard' || view === 'ModernDashboard' || this.element.hasClass('mk-chart-stage');
    };

    // Calculate font size based on widget size (capped on dashboard widgets)
    this.getFontSize = function() {
        var widget = this.element.closest('.dashboardWidget');
        if (widget.length === 0) {
            return 11;
        }
        if (this.isDashboardChart()) {
            return 11;
        }
        var widgetWidth = widget.width();
        var widgetHeight = widget.height();
        var baseWidth = 400;
        var baseHeight = 250;
        var widthScale = widgetWidth / baseWidth;
        var heightScale = widgetHeight / baseHeight;
        var scale = Math.max(widthScale, heightScale);
        scale = Math.max(0.7, Math.min(5.0, scale));
        var baseFontSize = 14;
        return baseFontSize * scale;
    };

    this.getDashboardTickAngle = function(ticks) {
        if (!ticks || !ticks.length) {
            return -45;
        }
        if (ticks.length <= 4) {
            var maxLenFew = 0;
            var ti;
            var tl;
            for (ti = 0; ti < ticks.length; ti++) {
                tl = ticks[ti];
                if (typeof tl !== 'string' && tl != null && tl[1] != null) {
                    tl = tl[1];
                }
                if (tl && tl.length > maxLenFew) {
                    maxLenFew = tl.length;
                }
            }
            if (maxLenFew > 16) {
                return -28;
            }
            if (maxLenFew > 10) {
                return -22;
            }
            return 0;
        }
        var maxLen = 0;
        var i;
        for (i = 0; i < ticks.length; i++) {
            var label = ticks[i];
            if (typeof label !== 'string' && label != null && label[1] != null) {
                label = label[1];
            }
            if (label && label.length > maxLen) {
                maxLen = label.length;
            }
        }
        return maxLen > 14 ? -25 : 0;
    };

    this.renderPie = function() {
        var fontSize = this.getFontSize();
        var dataLabelSize = fontSize * 1.4;
        this.element.jqplot([this.data['chartData']], {
            seriesDefaults:{
                renderer:jQuery.jqplot.PieRenderer,
                rendererOptions: {
                    showDataLabels: true,
                    dataLabels: 'value'
                },
                pointLabels: {
                    show: true,
                    fontSize: dataLabelSize + 'px',
                    formatString: '%d'
                }
            },
            legend: {
                show: true,
                location: 'e',
                fontSize: fontSize + 'px'
            },
            title : this.data['title']
        });
    }

    this.renderBar = function() {
        var fontSize = this.getFontSize();
        var dash = this.isDashboardChart();
        var dataLabelSize = dash ? 10 : fontSize * 1.4;
        var axisLabelSize = dash ? 11 : fontSize * 1.1;
        var tickAngle = dash ? this.getDashboardTickAngle(this.data['labels']) : -45;
        var dataLabels = this.data['data_labels'] || [];
        var plotOpts = {
            title: dash ? '' : this.data['title'],
            animate: !$.jqplot.use_excanvas,
            seriesDefaults:{
                renderer:jQuery.jqplot.BarRenderer,
                rendererOptions: {
                    showDataLabels: true,
                    dataLabels: 'value',
                    barDirection : 'vertical'
                },
                pointLabels: {
                    show: true,
                    edgeTolerance: -15,
                    fontSize: dataLabelSize + 'px',
                    formatString: '%d'
                }
            },
             axes: {
                xaxis: {
                      tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                      renderer: jQuery.jqplot.CategoryAxisRenderer,
                      ticks: this.data['labels'],
                      tickOptions: {
                        angle: tickAngle,
                        fontSize: fontSize + 'px'
                      },
                      labelOptions: {
                        fontSize: axisLabelSize + 'px'
                      }
                },
                yaxis: {
                    min:0,
                    max: this.data['yMaxValue'],
                    tickOptions: {
                        formatString: '%d',
                        fontSize: fontSize + 'px'
                    },
                    labelOptions: {
                        fontSize: axisLabelSize + 'px'
                    },
                    pad : 1.2
                }
            },
            legend: dash
                ? {
                    show: true,
                    placement: 'outsideGrid',
                    location: 's',
                    labels: dataLabels,
                    fontSize: fontSize + 'px',
                    showLabels: true,
                    showSwatch: true,
                    xoffset: 0,
                    yoffset: 6
                }
                : {
                    show: dataLabels.length > 0,
                    location: 'e',
                    placement: this.legendPlacement,
                    showLabels: dataLabels.length > 0,
                    showSwatch: dataLabels.length > 0,
                    labels: dataLabels,
                    fontSize: fontSize + 'px'
                }
        };
        if (dash) {
            this._dashLegendLabels = dataLabels;
            var shell = this.getDashboardPlotShell(dataLabels.length, false);
            shell.gridPadding.left = this.getDashboardYAxisLeftPad(
                this.data['yMaxValue']
            );
            plotOpts.gridPadding = shell.gridPadding;
            plotOpts.grid = shell.grid;
            plotOpts.seriesColors = shell.seriesColors;
        } else {
            this._dashLegendLabels = null;
        }
        this.destroyPriorPlot();
        this.element.jqplot(this.data['chartData'], plotOpts);
    }

    this.destroyPriorPlot = function() {
        try {
            var plot = this.element.data('jqplot');
            if (plot && typeof plot.destroy === 'function') {
                plot.destroy();
            }
        } catch (e) { /* ignore */ }
        this.element.empty();
    };

    this.renderFunnel = function() {
        var labels = new Array();
        var dataInfo = JSON.parse(this.data);
        for(var i=0; i<dataInfo.length; i++) {
            labels[i] = dataInfo[i][2];
            dataInfo[i][1] = parseFloat(dataInfo[i][1]);
        }

		/* Transform data friendly to Funnel renderer */
        var tmpdataInfo = [];
        for (var k in dataInfo) {
            tmpdataInfo.push(Object.values(dataInfo[k]));
        }
        dataInfo = tmpdataInfo;
        /* End */

        this.element.jqplot([dataInfo],  {
            seriesDefaults: {
                renderer:jQuery.jqplot.FunnelRenderer,
                rendererOptions:{
                    sectionMargin: 12,
                    widthRatio: 0.1,
                    showDataLabels:true,
                    dataLabelThreshold: 0,
                    dataLabels: 'value'
                }
            },
            legend: {
                show: true,
                location: 'ne',
                placement: this.legendPlacement,
                labels:labels,
                xoffset:20
            }
        });
    }

    this.renderMultibar = function() {
        var chartData = this.data.data;
        var ticks = this.data.ticks;
        var labels = this.data.labels;
        var fontSize = this.getFontSize();
        var dash = this.isDashboardChart();
        var dataLabelSize = dash ? 10 : fontSize * 1.4;
        var axisLabelSize = dash ? 11 : fontSize * 1.1;
        var tickAngle = dash ? 0 : -45;
        var plotOpts = {
            stackSeries: true,
            captureRightClick: true,
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                rendererOptions: {
                    barMargin: 10,
                    highlightMouseDown: true,
                    highlightMouseOver : true
            },
                pointLabels: {
                    show: !dash,
                    hideZeros: true,
                    fontSize: dataLabelSize + 'px',
                    formatString: '%d'
                }
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                    tickOptions: dash
                        ? {
                            showLabel: false,
                            showMark: true,
                            angle: 0,
                            fontSize: fontSize + 'px'
                        }
                        : {
                            angle: tickAngle,
                            fontSize: fontSize + 'px'
                        },
                    labelOptions: {
                        fontSize: axisLabelSize + 'px'
                    },
                    ticks: ticks
                },
                yaxis: {
                    padMin: 0,
                    min:0,
                    tickOptions: {
                        fontSize: fontSize + 'px'
                    },
                    labelOptions: {
                        fontSize: axisLabelSize + 'px'
                    }
                }
            },
            legend: dash
                ? {
                    show: false
                }
                : {
                    show: true,
                    location: 'e',
                    placement: this.legendPlacement,
                    labels: labels,
                    fontSize: fontSize + 'px'
                }
        };
        if (dash) {
            this._dashLegendLabels = labels;
            this._dashXLabels = ticks;
            this.element.addClass('mk-chart-multibar');
            var stackMax = 0;
            var si;
            var pi;
            for (si = 0; si < chartData.length; si++) {
                var colSum = 0;
                for (pi = 0; pi < chartData[si].length; pi++) {
                    colSum += parseFloat(chartData[si][pi]) || 0;
                }
                stackMax = Math.max(stackMax, colSum);
            }
            var shellMb = this.getDashboardPlotShell(0, true);
            shellMb.gridPadding.bottom = 10;
            shellMb.gridPadding.left = this.getDashboardYAxisLeftPad(stackMax);
            if (this.isFullscreenChart()) {
                shellMb.gridPadding.left = Math.max(shellMb.gridPadding.left, 76);
            }
            plotOpts.gridPadding = shellMb.gridPadding;
            plotOpts.grid = shellMb.grid;
            plotOpts.seriesColors = shellMb.seriesColors;
            if (plotOpts.seriesDefaults.rendererOptions) {
                plotOpts.seriesDefaults.rendererOptions.barMargin = 14;
                plotOpts.seriesDefaults.rendererOptions.barPadding = 8;
            }
        } else {
            this._dashXLabels = null;
            this._dashLegendLabels = null;
            this.element.removeClass('mk-chart-multibar');
        }
        this.destroyPriorPlot();
        this.element.jqplot(chartData, plotOpts);
        if (dash && ticks && ticks.length) {
            this.mountDashboardXLabels(ticks);
            if (labels && labels.length) {
                this.mountDashboardLegend(labels);
            }
        }
    }

    this.syncDashboardMultibarLayout = function() {
        var labels = this._dashLegendLabels;
        var ticks = this._dashXLabels || (this.data && this.data.ticks);
        var plot = this.element.data('jqplot');
        if (!plot) {
            return;
        }
        if (ticks && ticks.length) {
            this.mountDashboardXLabels(ticks);
        }
        if (labels && labels.length) {
            this.mountDashboardLegend(labels);
        }
    }

    this.renderHorizontalbar = function() {
        this.element.jqplot(this.data['chartData'], {
            title: this.data['title'],
            animate: !$.jqplot.use_excanvas,
            seriesDefaults: {
                renderer:$.jqplot.BarRenderer,
                showDataLabels: true,
                pointLabels: { show: true, location: 'e', edgeTolerance: -15 },
                shadowAngle: 135,
                rendererOptions: {
                    barDirection: 'horizontal'
                }
            },
            axes: {
                yaxis: {
                    tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                    renderer: jQuery.jqplot.CategoryAxisRenderer,
                    ticks: this.data['labels'],
                    tickOptions: {
                      angle: -45
                    }
                }
            },
            legend: {
                show: true,
                location: 'e',
                placement: this.legendPlacement,
                showSwatch : true,
                showLabels : true,
                labels:this.data['data_labels']
            }
        });
    }

    this.renderLine = function() {
        this.element.jqplot(this.data['chartData'], {
            title: this.data['title'],
            legend:{
                show:true,
                labels:this.data['data_labels'],
                location:'ne',
                showSwatch : true,
                showLabels : true,
                placement  : this.legendPlacement,
            },
            seriesDefaults: {
                pointLabels: {
                    show: true
                }
            },
            axes: {
                xaxis: {
                    min:0,
                    pad: 1,
                    tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks:this.data['labels'],
                    tickOptions: {
                        formatString: '%b %#d',
                        angle: -30
                    }
                }
            },
            cursor: {
                show: true
            }
        });
    }

    this.renderColumn = function() {      
        var chartData = [];
        var ticks = this.data.categories;
        var labels = [];
        for(var i = 0; i < this.data.chartData.length ; i++){
            labels.push(this.data.chartData[i].name);
            chartData.push(this.data.chartData[i].data);
        }
        this.element.jqplot( chartData, {
            stackSeries: false,
            captureRightClick: true,
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                rendererOptions: {
                    barMargin: 10,
                    highlightMouseDown: true,
                    highlightMouseOver : true
            },
                pointLabels: {show: true,hideZeros: true}
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                    tickOptions: {
                        angle: -45
                    },
                    ticks: ticks
                },
                yaxis: {
                    padMin: 0,
                    min:0
                }
            },
            legend: {
                show: true,
                location: 'e',
                placement: this.legendPlacement,
                labels:labels
            }
        });
    }

    this.registerClick = function() {
        var thisInstance = this;
        this.element.off('jqplotDataClick.vtchart');
        this.element.on('jqplotDataClick.vtchart', function(ev, seriesIndex, pointIndex) {
            var url;
            switch (thisInstance.options.renderer) {
                case 'funnel':
                    if (
                        thisInstance.options.links &&
                        thisInstance.options.links[pointIndex]
                    ) {
                        url = thisInstance.options.links[pointIndex].links;
                    }
                    break;
                case 'multibar':
                    if (
                        thisInstance.options.links &&
                        thisInstance.options.links[seriesIndex] &&
                        thisInstance.options.links[seriesIndex][pointIndex]
                    ) {
                        url = thisInstance.options.links[seriesIndex][pointIndex];
                    }
                    break;
                default:
                    if (
                        typeof thisInstance.options.links !== 'undefined' &&
                        thisInstance.options.links[pointIndex]
                    ) {
                        url = thisInstance.options.links[pointIndex];
                    }
                    break;
            }
            if (url) {
                thisInstance.triggerClick({ url: url });
            }
        });
    };

    this.postRendering = function() {
        var thisInstance = this;
        this.element.on("jqplotDataMouseOver", function(evt, seriesIndex, pointIndex, neighbor) {
            $('.jqplot-event-canvas').css( 'cursor', 'pointer' );
        });
        this.element.on("jqplotDataUnhighlight", function(evt, seriesIndex, pointIndex, neighbor) {
            $('.jqplot-event-canvas').css( 'cursor', 'auto' );
        });
        this.registerClick();
        if (thisInstance.options.renderer === 'multibar' && thisInstance._dashXLabels) {
            thisInstance.syncDashboardMultibarLayout();
            setTimeout(function () {
                thisInstance.syncDashboardMultibarLayout();
            }, 50);
            setTimeout(function () {
                thisInstance.syncDashboardMultibarLayout();
            }, 350);
        } else if (thisInstance._dashLegendLabels && thisInstance._dashLegendLabels.length) {
            var labelsOnly = thisInstance._dashLegendLabels;
            thisInstance.mountDashboardLegend(labelsOnly);
            setTimeout(function () {
                thisInstance.mountDashboardLegend(labelsOnly);
            }, 350);
        }
    }

    this.init = function(element,data,options) {
        this.element = element;
        this.data = data;
        this.options = options;

        switch(this.options.renderer) {
            case 'pie' : this.renderPie();break;   
            case 'bar' : this.renderBar();break;
            case 'funnel' : this.renderFunnel();break;
            case 'multibar' : this.renderMultibar();break;
            case 'horizontalbar' : this.renderHorizontalbar();break;
            case 'linechart' : this.renderLine();break;
            case 'column' : this.renderColumn();break;
            default : console.log('jqplot renderer not supported');
        }

        this.postRendering();
    }
}
