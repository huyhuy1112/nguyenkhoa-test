/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger.Class(
  "Vtiger_DashBoard_Js",
  {
    gridster: false,

    //static property which will store the instance of dashboard
    currentInstance: false,
    dashboardTabsLimit: 10,

    getWidgetsListMenu: function () {
      var $menu = jQuery(
        ".dashBoardContainer .widgetsList, .mk-dash-toolbar-add-wrap .widgetsList"
      ).first();
      return $menu.length ? $menu : jQuery(".widgetsList").first();
    },

    hideSelectableWidgetMenuItem: function (name, linkId) {
      if (!name) {
        return;
      }
      var $menu = Vtiger_DashBoard_Js.getWidgetsListMenu();
      $menu.find('a[data-name="' + name + '"]').each(function () {
        var $anchor = jQuery(this);
        var itemLinkId = $anchor.data("linkid");
        var $li = $anchor.closest("li");
        if (
          linkId === undefined ||
          linkId === null ||
          linkId === "" ||
          String(itemLinkId) === String(linkId)
        ) {
          $li.hide();
        }
      });
    },

    showSelectableWidgetMenuItem: function (name, linkId) {
      if (!name) {
        return;
      }
      var $menu = Vtiger_DashBoard_Js.getWidgetsListMenu();
      $menu.find('a[data-name="' + name + '"]').each(function () {
        var $anchor = jQuery(this);
        var itemLinkId = $anchor.data("linkid");
        var $li = $anchor.closest("li");
        if (
          linkId === undefined ||
          linkId === null ||
          linkId === "" ||
          String(itemLinkId) === String(linkId)
        ) {
          $li.show();
        }
      });
    },

    addWidget: function (element, url) {
      var element = jQuery(element);
      var linkId = element.data("linkid");
      var name = element.data("name");

      var activeTabId = Vtiger_DashBoard_Js.currentInstance.getActiveTabId();
      if (
        jQuery("#tab_" + activeTabId + " li.dashboardWidget#" + linkId).length
      ) {
        Vtiger_DashBoard_Js.hideSelectableWidgetMenuItem(name, linkId);
        return;
      }
      Vtiger_DashBoard_Js.hideSelectableWidgetMenuItem(name, linkId);
      var widgetContainer = jQuery(
        '<li class="new dashboardWidget loadcompleted" id="' +
          linkId +
          '" data-name="' +
          name +
          '" data-mode="open"></li>'
      );
      widgetContainer.data("url", url);
      var width = parseInt(element.data("width"), 10) || 1;
      var height = parseInt(element.data("height"), 10) || 1;
      Vtiger_DashBoard_Js.currentInstance.mkAddWidgetAtTop(
        widgetContainer,
        width,
        height
      );
      var inst = Vtiger_DashBoard_Js.currentInstance;
      inst.loadWidget(widgetContainer);
      inst.mkRefitDashboardGrid(widgetContainer);
    },

    addMiniListWidget: function (element, url) {
      // 1. Show popup window for selection (module, filter, fields)
      // 2. Compute the dynamic mini-list widget url
      // 3. Add widget with URL to the page.

      element = jQuery(element);

      app.request
        .post({ url: "index.php?module=Home&view=MiniListWizard&step=step1" })
        .then(function (err, res) {
          var callback = function (data) {
            var wizardContainer = jQuery(data);
            var form = jQuery("form", wizardContainer);

            var moduleNameSelectDOM = jQuery(
              'select[name="module"]',
              wizardContainer
            );
            var filteridSelectDOM = jQuery(
              'select[name="filterid"]',
              wizardContainer
            );
            var fieldsSelectDOM = jQuery(
              'select[name="fields"]',
              wizardContainer
            );

            var moduleNameSelect2 = vtUtils.showSelect2ElementView(
              moduleNameSelectDOM,
              {
                placeholder: app.vtranslate("JS_SELECT_MODULE"),
              }
            );
            var filteridSelect2 = vtUtils.showSelect2ElementView(
              filteridSelectDOM,
              {
                placeholder: app.vtranslate(
                  "JS_PLEASE_SELECT_ATLEAST_ONE_OPTION"
                ),
              }
            );
            var fieldsSelect2 = vtUtils.showSelect2ElementView(
              fieldsSelectDOM,
              {
                placeholder: app.vtranslate(
                  "JS_PLEASE_SELECT_ATLEAST_ONE_OPTION"
                ),
                closeOnSelect: true,
                maximumSelectionSize: 2,
              }
            );
            var footer = jQuery(".modal-footer", wizardContainer);

            filteridSelectDOM.closest("tr").hide();
            fieldsSelectDOM.closest("tr").hide();
            footer.hide();

            moduleNameSelect2.change(function () {
              if (!moduleNameSelect2.val()) return;

              var moduleNameSelect2Params = {
                module: "Home",
                view: "MiniListWizard",
                step: "step2",
                selectedModule: moduleNameSelect2.val(),
              };

              app.request
                .post({ data: moduleNameSelect2Params })
                .then(function (err, res) {
                  filteridSelectDOM.empty().html(res).trigger("change");
                  filteridSelect2.closest("tr").show();
                  fieldsSelect2.closest("tr").hide();
                  footer.hide();
                });
            });
            filteridSelect2.change(function () {
              if (!filteridSelect2.val()) return;

              var selectedModule = moduleNameSelect2.val();
              var filteridSelect2Params = {
                module: "Home",
                view: "MiniListWizard",
                step: "step3",
                selectedModule: selectedModule,
                filterid: filteridSelect2.val(),
              };

              app.request
                .post({ data: filteridSelect2Params })
                .then(function (err, res) {
                  fieldsSelectDOM.empty().html(res).trigger("change");
                  var translatedModuleNames = JSON.parse(
                    jQuery("#minilistWizardContainer")
                      .find("#translatedModuleNames")
                      .val()
                  );
                  var fieldsLabelText = app.vtranslate(
                    "JS_EDIT_FIELDS",
                    translatedModuleNames[selectedModule],
                    translatedModuleNames[selectedModule]
                  );
                  fieldsSelect2
                    .closest("tr")
                    .find(".fieldLabel label")
                    .text(fieldsLabelText);
                  fieldsSelect2.closest("tr").show();
                });
            });
            fieldsSelect2.change(function () {
              if (!fieldsSelect2.val()) {
                footer.hide();
              } else {
                footer.show();
              }
            });

            form.submit(function (e) {
              e.preventDefault();
              //To disable savebutton after one submit to prevent multiple submits
              jQuery("[name='saveButton']").attr("disabled", "disabled");
              var selectedModule = moduleNameSelect2.val();
              var selectedFilterId = filteridSelect2.val();
              var selectedFields = fieldsSelect2.val();
              if (typeof selectedFields != "object")
                selectedFields = [selectedFields];

              // TODO mandatory field validation

              finializeAdd(selectedModule, selectedFilterId, selectedFields);
            });
          };
          app.helper.showModal(res, { cb: callback });
        });

      function finializeAdd(moduleName, filterid, fields) {
        var data = {
          module: moduleName,
        };
        if (typeof fields != "object") fields = [fields];
        data["fields"] = fields;

        url += "&filterid=" + filterid + "&data=" + JSON.stringify(data);
        var linkId = element.data("linkid");
        var name = element.data("name");
        var widgetContainer = jQuery(
          '<li class="new dashboardWidget loadcompleted" id="' +
            linkId +
            "-" +
            filterid +
            '" data-name="' +
            name +
            '" data-mode="open"></li>'
        );
        widgetContainer.data("url", url);
        var width = parseInt(element.data("width"), 10) || 1;
        var height = parseInt(element.data("height"), 10) || 1;
        Vtiger_DashBoard_Js.currentInstance.mkAddWidgetAtTop(
        widgetContainer,
        width,
        height
      );
        var inst = Vtiger_DashBoard_Js.currentInstance;
        inst.loadWidget(widgetContainer);
        inst.mkRefitDashboardGrid(widgetContainer);
        Vtiger_DashBoard_Js.hideSelectableWidgetMenuItem(name);
        app.helper.hideModal();
      }
    },

    addNoteBookWidget: function (element, url) {
      // 1. Show popup window for selection (module, filter, fields)
      // 2. Compute the dynamic mini-list widget url
      // 3. Add widget with URL to the page.

      element = jQuery(element);

      app.request
        .get({ url: "index.php?module=Home&view=AddNotePad" })
        .then(function (err, res) {
          var callback = function (data) {
            var wizardContainer = jQuery(data);
            var form = jQuery("form", wizardContainer);
            var params = {
              submitHandler: function (form) {
                //To prevent multiple click on save
                var form = jQuery(form);
                jQuery("[name='saveButton']").attr("disabled", "disabled");
                var notePadName = form.find('[name="notePadName"]').val();
                var notePadContent = form.find('[name="notePadContent"]').val();
                var linkId = element.data("linkid");
                var noteBookParams = {
                  module: app.getModuleName(),
                  action: "NoteBook",
                  mode: "NoteBookCreate",
                  notePadName: notePadName,
                  notePadContent: notePadContent,
                  linkId: linkId,
                  tab: jQuery(".tab-pane.active").data("tabid"),
                };
                app.request
                  .post({ data: noteBookParams })
                  .then(function (err, data) {
                    if (data) {
                      var widgetId = data.widgetId;
                      app.helper.hideModal();

                      url += "&widgetid=" + widgetId;

                      var name = element.data("name");
                      var widgetContainer = jQuery(
                        '<li class="new dashboardWidget loadcompleted" id="' +
                          linkId +
                          "-" +
                          widgetId +
                          '" data-name="' +
                          name +
                          '" data-mode="open"></li>'
                      );
                      widgetContainer.data("url", url);
                      var width = element.data("width");
                      var height = element.data("height");
                      Vtiger_DashBoard_Js.currentInstance.mkAddWidgetAtTop(
                        widgetContainer,
                        width,
                        height
                      );
                      var inst = Vtiger_DashBoard_Js.currentInstance;
                      inst.loadWidget(widgetContainer);
                      inst.mkRefitDashboardGrid(widgetContainer);
                      Vtiger_DashBoard_Js.hideSelectableWidgetMenuItem(name);
                    }
                  });
                return false;
              },
            };
            form.vtValidate(params);
          };
          app.helper.showModal(res, { cb: callback });
        });
    },
  },
  {
    container: false,
    instancesCache: {},

    init: function () {
      Vtiger_DashBoard_Js.currentInstance = this;
      this.addComponents();
    },

    addComponents: function () {
      this.addComponent("Vtiger_Index_Js");
    },

    getDashboardContainer: function () {
      return jQuery(".dashBoardContainer");
    },

    getContainer: function (tabid) {
      if (typeof tabid == "undefined") {
        tabid = this.getActiveTabId();
      }
      return jQuery(".gridster_" + tabid).find("ul");
    },

    getWidgetInstance: function (widgetContainer) {
      var id = widgetContainer.attr("id");
      if (!(id in this.instancesCache)) {
        var widgetName = widgetContainer.data("name");
        if (widgetName === "ChartReportWidget") {
          widgetName += "_" + id;
        }
        var moduleName = app.getModuleName();
        var potentialsWidgets = {
          FunnelAmount: true,
          GroupedBySalesPerson: true,
          PipelinedAmountPerSalesPerson: true,
        };
        if (potentialsWidgets[widgetName]) {
          moduleName = "Potentials";
        }
        this.instancesCache[id] = Vtiger_Widget_Js.getInstance(
          widgetContainer,
          widgetName,
          moduleName
        );
      } else {
        this.instancesCache[id].init(widgetContainer);
      }
      return this.instancesCache[id];
    },

    getActiveTabId: function () {
      return jQuery(".tab-pane.active").data("tabid");
    },

    getActiveTabName: function () {
      return jQuery(".tab-pane.active").data("tabname");
    },

    getgridColumns: function () {
      var _device_width = $(window).innerWidth();
      var gridWidth = _device_width;

      if (_device_width < 480) {
        gridWidth = 1;
      } else if (_device_width >= 480 && _device_width < 768) {
        gridWidth = 1;
      } else if (_device_width >= 768 && _device_width < 992) {
        gridWidth = 2;
      } else if (_device_width >= 992 && _device_width < 1440) {
        gridWidth = 3;
      } else {
        gridWidth = 4;
      }
      return gridWidth;
    },

    /**
     * Calculate unified dashboard scale factor based on widget size
     * Returns scale between 0.75 (small) and 1.25 (large)
     * Based on widget AREA for proportional scaling
     */
    getDashboardScale: function (widgetWidth, widgetHeight) {
      var baseArea = 500 * 300; // Base widget size (1 column x 1 row)
      var widgetArea = widgetWidth * widgetHeight;
      var areaRatio = widgetArea / baseArea;

      // Use logarithmic scaling for smooth, proportional growth
      // Formula: scale = 0.75 + (log(areaRatio) / log(4)) * 0.5
      // This means: 0.25x area = 0.75 scale, 1x area = 1.0 scale, 4x area = 1.25 scale
      var scale =
        0.82 +
        (Math.log(Math.max(0.25, Math.min(4, areaRatio))) / Math.log(4)) * 0.28;

      // Tighter clamp — avoids oversized chart typography vs Figma
      scale = Math.max(0.88, Math.min(1.06, scale));

      return scale;
    },

    /**
     * Apply unified scaling to all chart elements
     * Uses single scale factor for proportional, harmonious scaling
     */
    updateWidgetFontSizes: function (widget) {
      var thisInstance = this;

      // Calculate widget dimensions
      var widgetWidth = widget.width();
      var widgetHeight = widget.height();

      // Check if widget has chart
      var chartContainer = widget.find(".widgetChartContainer");
      if (chartContainer.length === 0) {
        return; // Text-only widgets skip scaling
      }

      // Debounce flag to prevent updates during active resize
      var isResizing = widget.data("is-resizing") || false;

      /**
       * Unified scaling function - applies ONE scale factor to everything
       * Ensures all elements scale proportionally and harmoniously
       */
      var applyUnifiedScaling = function (force) {
        // Skip if currently resizing (unless forced)
        if (isResizing && !force) {
          return;
        }

        // Recalculate widget size (may have changed during resize)
        var currentWidth = widget.width();
        var currentHeight = widget.height();

        // Get unified scale factor (0.75 → 1.25 based on widget area)
        var scale = thisInstance.getDashboardScale(currentWidth, currentHeight);

        // Base font sizes (at scale 1.0) - defines visual hierarchy
        var baseSizes = {
          title: 15,
          axisLabel: 12,
          tickLabel: 11,
          categoryLabel: 10,
          numberLabel: 10,
          legend: 11,
        };

        // Apply scale to all base sizes
        var sizes = {};
        for (var key in baseSizes) {
          sizes[key] = Math.round(baseSizes[key] * scale);
        }

        // Legend spacing (proportional to legend font size)
        var legendLineHeight = Math.round((sizes.legend + 4) * scale) + "px";
        var legendPadding =
          Math.round(2 * scale) + "px " + Math.round(4 * scale) + "px";

        /**
         * Helper: Apply font size to element with !important override
         */
        var applyFontSize = function (
          element,
          fontSize,
          fontWeight,
          lineHeight,
          color
        ) {
          if (element.tagName === "CANVAS" || element.offsetParent === null) {
            return;
          }

          // Clean existing font-related styles
          element.style.cssText = element.style.cssText.replace(
            /font-size[^;]*;?/gi,
            ""
          );
          if (fontWeight) {
            element.style.cssText = element.style.cssText.replace(
              /font-weight[^;]*;?/gi,
              ""
            );
          }
          if (lineHeight) {
            element.style.cssText = element.style.cssText.replace(
              /line-height[^;]*;?/gi,
              ""
            );
          }
          if (color) {
            element.style.cssText = element.style.cssText.replace(
              /color[^;]*;?/gi,
              ""
            );
          }

          // Apply new styles
          element.style.fontSize = fontSize + "px";
          element.style.setProperty("font-size", fontSize + "px", "important");

          if (fontWeight) {
            element.style.fontWeight = fontWeight;
            element.style.setProperty("font-weight", fontWeight, "important");
          }
          if (lineHeight) {
            element.style.lineHeight = lineHeight;
            element.style.setProperty("line-height", lineHeight, "important");
          }
          if (color) {
            element.style.color = color;
            element.style.setProperty("color", color, "important");
          }

          // Also set as attribute for persistence
          if (element.setAttribute) {
            var styleParts = ["font-size: " + fontSize + "px !important"];
            if (fontWeight)
              styleParts.push("font-weight: " + fontWeight + " !important");
            if (lineHeight)
              styleParts.push("line-height: " + lineHeight + " !important");
            if (color) styleParts.push("color: " + color + " !important");

            var currentStyle = element.getAttribute("style") || "";
            currentStyle = currentStyle.replace(/font-size[^;]*;?/gi, "");
            if (fontWeight)
              currentStyle = currentStyle.replace(/font-weight[^;]*;?/gi, "");
            if (lineHeight)
              currentStyle = currentStyle.replace(/line-height[^;]*;?/gi, "");
            if (color)
              currentStyle = currentStyle.replace(/color[^;]*;?/gi, "");

            element.setAttribute(
              "style",
              currentStyle + "; " + styleParts.join("; ")
            );
          }
        };

        // 1. Y-axis labels (like "Value") - bold, prominent
        chartContainer
          .find(
            ".jqplot-yaxis-label, .jqplot-yaxis-label-text, .jqplot-yaxis, div.jqplot-yaxis-label, span.jqplot-yaxis-label"
          )
          .each(function () {
            applyFontSize(this, sizes.axisLabel, "bold");
          });

        // 2. X-axis labels (axis title if present) - smaller, less prominent
        var xAxisLabelSize = Math.round(sizes.axisLabel * 0.6);
        chartContainer
          .find(".jqplot-xaxis-label, .jqplot-xaxis-label-text")
          .each(function () {
            applyFontSize(this, xAxisLabelSize, "normal", "1.1");
          });

        // 3. Tick labels (numbers on axes) and category labels
        chartContainer
          .find(
            ".jqplot-yaxis-tick, .jqplot-yaxis-tick-label, .jqplot-yaxis-tick text, .jqplot-yaxis-tick svg text, .jqplot-xaxis-tick, .jqplot-xaxis-tick-label, .jqplot-xaxis-tick text, .jqplot-xaxis-tick svg text"
          )
          .each(function () {
            var element = this;
            var textContent = (
              element.textContent ||
              element.innerHTML ||
              ""
            ).trim();

            // Check if it's a number (numeric tick) or category name
            if (/^\d+\.?\d*$/.test(textContent)) {
              // Numeric tick label
              applyFontSize(element, sizes.tickLabel);
            } else {
              // Category label (e.g. "sad dang", "huy Administrator") - smaller, muted
              applyFontSize(
                element,
                sizes.categoryLabel,
                "normal",
                null,
                "#64748B"
              );

              // For SVG text elements
              if (element.tagName === "text" && element.setAttribute) {
                element.setAttribute("font-size", sizes.categoryLabel);
                element.setAttribute("font-weight", "normal");
                element.setAttribute("fill", "#64748B");
              }
            }
          });

        // 4. Numbers on bars/points (data labels) — subtle SaaS style
        chartContainer
          .find(
            ".jqplot-point-label, .jqplot-data-label, .jqplot-bar-label, .jqplot-series-label"
          )
          .each(function () {
            var element = this;
            var textContent = (
              element.textContent ||
              element.innerHTML ||
              ""
            ).trim();

            // Only update if it's a number
            if (/^\d+\.?\d*$/.test(textContent)) {
              applyFontSize(
                element,
                sizes.numberLabel,
                "500",
                null,
                "#94A3B8"
              );
              element.style.textAlign = "center";
              element.style.display = "flex";
              element.style.alignItems = "center";
              element.style.justifyContent = "center";
            }
          });

        // 5. Legend scaling - compact, proportional, max 25% width
        var legendCount = 0;
        var maxLegendWidth = Math.round(currentWidth * 0.25); // Max 25% of widget width

        // Target legend table structure (jqPlot uses table)
        chartContainer.find(".jqplot-table-legend").each(function () {
          var table = this;

          // Compact table structure
          table.style.height = "auto";
          table.style.setProperty("height", "auto", "important");
          table.style.margin = "0";
          table.style.setProperty("margin", "0", "important");
          table.style.maxWidth = maxLegendWidth + "px";
          table.style.setProperty(
            "max-width",
            maxLegendWidth + "px",
            "important"
          );
          table.style.width = "auto";
          table.style.setProperty("width", "auto", "important");

          // Compact table rows
          jQuery(table)
            .find("tr")
            .each(function () {
              var tr = this;
              tr.style.height = "auto";
              tr.style.setProperty("height", "auto", "important");
              tr.style.lineHeight = legendLineHeight;
              tr.style.setProperty(
                "line-height",
                legendLineHeight,
                "important"
              );
              tr.style.padding = "0";
              tr.style.setProperty("padding", "0", "important");
              tr.style.margin = "0";
              tr.style.setProperty("margin", "0", "important");
            });

          // Compact table cells
          jQuery(table)
            .find("td")
            .each(function () {
              var td = this;
              td.style.height = "auto";
              td.style.setProperty("height", "auto", "important");
              td.style.lineHeight = legendLineHeight;
              td.style.setProperty(
                "line-height",
                legendLineHeight,
                "important"
              );
              td.style.padding = legendPadding;
              td.style.setProperty("padding", legendPadding, "important");
              td.style.margin = "0";
              td.style.setProperty("margin", "0", "important");
            });

          // Legend label text - scaled, wrapped, compact
          jQuery(table)
            .find(".jqplot-table-legend-label")
            .each(function () {
              var element = this;
              if (
                element.tagName !== "CANVAS" &&
                element.offsetParent !== null
              ) {
                applyFontSize(
                  element,
                  sizes.legend,
                  "normal",
                  legendLineHeight
                );

                // Allow text wrapping for long labels
                element.style.whiteSpace = "normal";
                element.style.wordWrap = "break-word";
                element.style.maxWidth = maxLegendWidth - 40 + "px"; // Leave space for swatch

                if (element.setAttribute) {
                  var currentStyle = element.getAttribute("style") || "";
                  currentStyle = currentStyle.replace(
                    /white-space[^;]*;?/gi,
                    ""
                  );
                  currentStyle = currentStyle.replace(/word-wrap[^;]*;?/gi, "");
                  currentStyle = currentStyle.replace(/max-width[^;]*;?/gi, "");
                  element.setAttribute(
                    "style",
                    currentStyle +
                      "; white-space: normal; word-wrap: break-word; max-width: " +
                      (maxLegendWidth - 40) +
                      "px;"
                  );
                }

                legendCount++;
              }
            });
        });

        // Also update other legend selectors for compatibility
        chartContainer
          .find(
            ".jqplot-legend-label, .jqplot-legend-label-text, .jqplot-legend-swatch-label"
          )
          .each(function () {
            var element = this;
            if (element.tagName !== "CANVAS" && element.offsetParent !== null) {
              applyFontSize(element, sizes.legend, "normal", legendLineHeight);
              if (legendCount === 0) {
                legendCount++;
              }
            }
          });

        // 6. SVG text elements - scaled proportionally
        chartContainer.find("svg text, svg tspan").each(function () {
          var element = this;
          var textContent = (
            element.textContent ||
            element.innerHTML ||
            ""
          ).trim();

          if (/^\d+\.?\d*$/.test(textContent)) {
            // Numbers in SVG - medium weight, muted
            element.setAttribute("font-size", sizes.numberLabel);
            element.style.fontSize = sizes.numberLabel + "px";
            element.style.fontWeight = "500";
            element.style.fill = "#94A3B8";
            element.setAttribute("font-weight", "500");
            element.setAttribute("fill", "#94A3B8");
            element.setAttribute("text-anchor", "middle");
            element.setAttribute("dominant-baseline", "middle");
          } else {
            // Other text in SVG - use tick label size
            element.setAttribute("font-size", sizes.tickLabel);
            element.style.fontSize = sizes.tickLabel + "px";
          }
        });

      };

      // Debounced update function - only runs after resize stops
      var debouncedUpdate = function () {
        var existingTimer = widget.data("debounce-timer");
        if (existingTimer) {
          clearTimeout(existingTimer);
        }
        var newTimer = setTimeout(function () {
          isResizing = false;
          widget.data("is-resizing", false);
          widget.data("debounce-timer", null);
          applyUnifiedScaling(true); // Force update after resize
        }, 300); // Wait 300ms after last resize event
        widget.data("debounce-timer", newTimer);
      };

      // Initial update after chart renders
      setTimeout(function () {
        applyUnifiedScaling(true);
      }, 100);

      // Update after chart fully renders
      setTimeout(function () {
        applyUnifiedScaling(true);
      }, 500);
      setTimeout(function () {
        applyUnifiedScaling(true);
      }, 1000);

      // Set up interval (only when not resizing)
      var intervalId = widget.data("font-update-interval");
      if (intervalId) {
        clearInterval(intervalId);
      }
      var interval = setInterval(function () {
        if (!isResizing) {
          applyUnifiedScaling(false);
        }
      }, 2000);
      widget.data("font-update-interval", interval);

      // Store debounced function for resize events
      widget.data("debounced-font-update", debouncedUpdate);

      // Try to hook into jqPlot postDrawHooks if available
      try {
        var plotElement = chartContainer.find(".jqplot-target").first();
        if (plotElement.length > 0 && plotElement.data("jqplot")) {
          var plot = plotElement.data("jqplot");
          if (plot && plot.postDrawHooks) {
            plot.postDrawHooks.push(function () {
              setTimeout(function () {
                applyUnifiedScaling(true);
              }, 50);
            });
          }
        }
      } catch (e) {
        // If postDrawHooks not available, continue with setTimeout approach
      }
    },

    saveWidgetSize: function (widget) {
      var dashboardTabId = widget.closest(".tab-pane.active").data("tabid");
      var widgetSize = {
        sizex: widget.attr("data-sizex"),
        sizey: widget.attr("data-sizey"),
      };
      if (widgetSize.sizex && widgetSize.sizey) {
        var params = {
          module: "Vtiger",
          action: "SaveWidgetSize",
          id: widget.attr("id"),
          size: widgetSize,
          tabid: dashboardTabId,
        };
        app.request.post({ data: params }).then(function (err, data) {});
      }
    },

    getWaitingForResizeCompleteMsg: function () {
      return (
        '<div class="wait_resizing_msg"><p class="text-info">' +
        app.vtranslate("JS_WIDGET_RESIZING_WAIT_MSG") +
        "</p></div>"
      );
    },

    registerGridster: function () {
      var thisInstance = this;
      var widgetMargin = 10;
      var activeTabId = this.getActiveTabId();
      var activeGridster = jQuery(".gridster_" + activeTabId);
      var items = activeGridster.find("ul li");
      items.detach();

      // Constructing the grid based on window width
      var cols = this.getgridColumns();
      var packResult = thisInstance.mkPackDashboardWidgets(items, cols);
      var minRowsNeeded = Math.min(Math.max(packResult.maxRow || 2, 2), 24);
      $(".mainContainer").css("min-width", "500px");
      var rightGutter = 28;
      var $dashContents = activeGridster.closest(".dashBoardTabContents");
      var shellW =
        ($dashContents.length && $dashContents.width()) ||
        jQuery(".mk-dash-widgets-zone").width() ||
        jQuery(".mk-dash-main").width() ||
        jQuery(".mk-app-shell").width() ||
        jQuery(window).width();
      shellW = Math.max(shellW - rightGutter, 320);
      var col_width =
        Math.floor((shellW - 30) / cols) - 2 * widgetMargin;

      Vtiger_DashBoard_Js.gridster = this.getContainer()
        .gridster({
          widget_margins: [widgetMargin, widgetMargin],
          widget_base_dimensions: [col_width, 300],
          min_cols: cols,
          max_cols: cols,
          min_rows: minRowsNeeded,
          resize: {
            enabled: true,
            start: function (e, ui, widget) {
              var widgetContent = widget.find(".dashboardWidgetContent");
              widgetContent.before(
                thisInstance.getWaitingForResizeCompleteMsg()
              );
              widgetContent.addClass("hide");
              // Mark as resizing to prevent font updates during resize
              widget.data("is-resizing", true);
              // Clear any pending font updates
              var existingTimer = widget.data("debounce-timer");
              if (existingTimer) {
                clearTimeout(existingTimer);
                widget.data("debounce-timer", null);
              }
            },
            stop: function (e, ui, widget) {
              var widgetContent = widget.find(".dashboardWidgetContent");
              widgetContent.prev(".wait_resizing_msg").remove();
              widgetContent.removeClass("hide");

              var widgetName = widget.data("name");
              /**
               * we are setting default height in DashBoardWidgetContents.tpl
               * need to overwrite based on resized widget height
               */
              var widgetChartContainer = widget.find(".widgetChartContainer");
              if (widgetChartContainer.length > 0) {
                widgetChartContainer.html("");
              }
              var widgetInst = Vtiger_Widget_Js.getInstance(widget, widgetName);
              thisInstance.mkSizeDashboardChartArea(widget, widgetName);

              widget.trigger(Vtiger_Widget_Js.widgetPostResizeEvent);

              // Update font sizes AFTER chart is rendered (with multiple delays to catch all renders)
              setTimeout(function () {
                thisInstance.updateWidgetFontSizes(widget);
              }, 100);
              setTimeout(function () {
                thisInstance.updateWidgetFontSizes(widget);
              }, 500);
              setTimeout(function () {
                thisInstance.updateWidgetFontSizes(widget);
              }, 1000);

              thisInstance.saveWidgetSize(widget);
              thisInstance.mkFitGridsterCanvas(widget.closest('[class^="gridster_"]'));
            },
          },
          draggable: {
            stop: function (event, ui) {
              thisInstance.flushDashboardPositionsSync();
              thisInstance.mkFitGridsterCanvas(activeGridster);
            },
          },
        })
        .data("gridster");

      items.sort(function (a, b) {
        var widgetA = jQuery(a);
        var widgetB = jQuery(b);
        var rowA = parseInt(widgetA.attr("data-row"));
        var rowB = parseInt(widgetB.attr("data-row"));
        var colA = parseInt(widgetA.attr("data-col"));
        var colB = parseInt(widgetB.attr("data-col"));

        if (rowA === rowB && colA === colB) {
          return 0;
        }

        if (rowA > rowB || (rowA === rowB && colA > colB)) {
          return 1;
        }
        return -1;
      });
      jQuery.each(items, function (i, e) {
        var item = $(this);
        var columns =
          parseInt(item.attr("data-sizex")) > cols
            ? cols
            : parseInt(item.attr("data-sizex"));
        var rows = parseInt(item.attr("data-sizey"));
        var col = parseInt(item.attr("data-col"), 10) || 1;
        var row = parseInt(item.attr("data-row"), 10) || 1;
        Vtiger_DashBoard_Js.gridster.add_widget(
          item,
          columns,
          rows,
          col,
          row
        );
      });
      thisInstance.flushDashboardPositionsSync();
      thisInstance.mkFitGridsterCanvas(activeGridster);
      thisInstance.mkEnsureSinglePageScroll();
      thisInstance.syncAddWidgetsMenu();
      setTimeout(function () {
        thisInstance.mkFitGridsterCanvas(activeGridster);
        thisInstance.mkEnsureSinglePageScroll();
        thisInstance.syncAddWidgetsMenu();
        if (thisInstance.mkIsHomeDashboardPage()) {
          window.scrollTo(0, 0);
        }
      }, 400);
      setTimeout(function () {
        thisInstance.mkFitGridsterCanvas(activeGridster);
      }, 1200);
    },

    mkGridCellsFree: function (matrix, row, col, sizeX, sizeY, maxCols) {
      var r;
      var c;
      for (r = row; r < row + sizeY; r++) {
        for (c = col; c < col + sizeX; c++) {
          if (c < 1 || c > maxCols || matrix[r + "-" + c]) {
            return false;
          }
        }
      }
      return true;
    },

    mkGridOccupy: function (matrix, row, col, sizeX, sizeY) {
      var r;
      var c;
      for (r = row; r < row + sizeY; r++) {
        for (c = col; c < col + sizeX; c++) {
          matrix[r + "-" + c] = true;
        }
      }
    },

    mkFindTopSlotFromMatrix: function (matrix, sizeX, sizeY, maxCols) {
      var r;
      var c;
      for (r = 1; r < 40; r++) {
        for (c = 1; c <= maxCols - sizeX + 1; c++) {
          if (this.mkGridCellsFree(matrix, r, c, sizeX, sizeY, maxCols)) {
            return { row: r, col: c };
          }
        }
      }
      return { row: 1, col: 1 };
    },

    mkPackDashboardWidgets: function (items, maxCols) {
      var result = { maxRow: 0, changed: false };
      if (!items || !items.length) {
        return result;
      }
      var list = [];
      items.each(function () {
        list.push(jQuery(this));
      });
      list.sort(function (a, b) {
        var rowA = parseInt(a.attr("data-row"), 10) || 1;
        var rowB = parseInt(b.attr("data-row"), 10) || 1;
        if (rowA !== rowB) {
          return rowA - rowB;
        }
        return (parseInt(a.attr("data-col"), 10) || 1) - (parseInt(b.attr("data-col"), 10) || 1);
      });
      var matrix = {};
      var i;
      for (i = 0; i < list.length; i++) {
        var w = list[i];
        var sizeX = Math.min(
          maxCols,
          Math.max(1, parseInt(w.attr("data-sizex"), 10) || 1)
        );
        var sizeY = Math.max(1, parseInt(w.attr("data-sizey"), 10) || 1);
        var prevCol = parseInt(w.attr("data-col"), 10) || 1;
        var prevRow = parseInt(w.attr("data-row"), 10) || 1;
        var slot = this.mkFindTopSlotFromMatrix(matrix, sizeX, sizeY, maxCols);
        if (slot.col !== prevCol || slot.row !== prevRow) {
          result.changed = true;
        }
        w.attr({ "data-col": slot.col, "data-row": slot.row });
        this.mkGridOccupy(matrix, slot.row, slot.col, sizeX, sizeY);
        result.maxRow = Math.max(result.maxRow, slot.row + sizeY - 1);
      }
      return result;
    },

    mkPrepareWidgetSlots: function (items, maxCols) {
      return this.mkPackDashboardWidgets(items, maxCols).changed;
    },

    mkLayoutNeedsCompact: function (items, maxCols) {
      if (!this.mkIsDashboardView() || !items || !items.length) {
        return false;
      }
      var list = [];
      items.each(function () {
        list.push(jQuery(this));
      });
      var i;
      for (i = 0; i < list.length; i++) {
        if (list[i].attr("data-position") === "false") {
          return true;
        }
      }
      return this.mkLayoutHasVerticalGaps(list, maxCols);
    },

    mkLayoutHasVerticalGaps: function (list, maxCols) {
      var savedMaxRow = 0;
      var i;
      for (i = 0; i < list.length; i++) {
        var w = list[i];
        var row = parseInt(w.attr("data-row"), 10) || 1;
        var sizeY = Math.max(1, parseInt(w.attr("data-sizey"), 10) || 1);
        savedMaxRow = Math.max(savedMaxRow, row + sizeY - 1);
      }
      var compactMaxRow = this.mkEstimateCompactMaxRow(list, maxCols);
      return savedMaxRow > compactMaxRow;
    },

    mkEstimateCompactMaxRow: function (list, maxCols) {
      var sorted = list.slice().sort(function (a, b) {
        var rowA = parseInt(a.attr("data-row"), 10) || 1;
        var rowB = parseInt(b.attr("data-row"), 10) || 1;
        if (rowA !== rowB) {
          return rowA - rowB;
        }
        return (parseInt(a.attr("data-col"), 10) || 1) - (parseInt(b.attr("data-col"), 10) || 1);
      });
      var matrix = {};
      var maxRow = 0;
      var i;
      for (i = 0; i < sorted.length; i++) {
        var w = sorted[i];
        var sizeX = Math.min(
          maxCols,
          Math.max(1, parseInt(w.attr("data-sizex"), 10) || 1)
        );
        var sizeY = Math.max(1, parseInt(w.attr("data-sizey"), 10) || 1);
        var placed = false;
        var r;
        var c;
        for (r = 1; r < 40 && !placed; r++) {
          for (c = 1; c <= maxCols - sizeX + 1 && !placed; c++) {
            if (this.mkGridCellsFree(matrix, r, c, sizeX, sizeY, maxCols)) {
              this.mkGridOccupy(matrix, r, c, sizeX, sizeY);
              maxRow = Math.max(maxRow, r + sizeY - 1);
              placed = true;
            }
          }
        }
      }
      return maxRow;
    },

    mkNormalizeWidgetRows: function (items, maxCols) {
      if (!this.mkIsDashboardView() || !items || !items.length) {
        return;
      }
      var list = [];
      items.each(function () {
        list.push(jQuery(this));
      });
      list.sort(function (a, b) {
        var rowA = parseInt(a.attr("data-row"), 10) || 1;
        var rowB = parseInt(b.attr("data-row"), 10) || 1;
        if (rowA !== rowB) {
          return rowA - rowB;
        }
        return (parseInt(a.attr("data-col"), 10) || 1) - (parseInt(b.attr("data-col"), 10) || 1);
      });
      var matrix = {};
      var i;
      for (i = 0; i < list.length; i++) {
        var w = list[i];
        var sizeX = Math.min(
          maxCols,
          Math.max(1, parseInt(w.attr("data-sizex"), 10) || 1)
        );
        var sizeY = Math.max(1, parseInt(w.attr("data-sizey"), 10) || 1);
        var placed = false;
        var r;
        var c;
        for (r = 1; r < 40 && !placed; r++) {
          for (c = 1; c <= maxCols - sizeX + 1 && !placed; c++) {
            if (this.mkGridCellsFree(matrix, r, c, sizeX, sizeY, maxCols)) {
              w.attr({ "data-row": r, "data-col": c });
              this.mkGridOccupy(matrix, r, c, sizeX, sizeY);
              placed = true;
            }
          }
        }
      }
    },

    mkBuildOccupancyMatrix: function (maxCols, $widgets) {
      var matrix = {};
      var thisInstance = this;
      if (!$widgets || !$widgets.length) {
        return matrix;
      }
      $widgets.each(function () {
        var $w = jQuery(this);
        var row = parseInt($w.attr("data-row"), 10) || 1;
        var col = parseInt($w.attr("data-col"), 10) || 1;
        var sizeX = Math.min(
          maxCols,
          Math.max(1, parseInt($w.attr("data-sizex"), 10) || 1)
        );
        var sizeY = Math.max(1, parseInt($w.attr("data-sizey"), 10) || 1);
        thisInstance.mkGridOccupy(matrix, row, col, sizeX, sizeY);
      });
      return matrix;
    },

    mkFindTopSlot: function (sizeX, sizeY, maxCols, $widgets) {
      var matrix = this.mkBuildOccupancyMatrix(maxCols, $widgets);
      var r;
      var c;
      for (r = 1; r < 40; r++) {
        for (c = 1; c <= maxCols - sizeX + 1; c++) {
          if (this.mkGridCellsFree(matrix, r, c, sizeX, sizeY, maxCols)) {
            return { row: r, col: c };
          }
        }
      }
      return { row: 1, col: 1 };
    },

    mkAddWidgetAtTop: function (widgetContainer, width, height) {
      var gridster = Vtiger_DashBoard_Js.gridster;
      if (!gridster) {
        return;
      }
      var cols = this.getgridColumns();
      width = Math.min(cols, Math.max(1, parseInt(width, 10) || 1));
      height = Math.max(1, parseInt(height, 10) || 1);
      var slot = this.mkFindTopSlot(
        width,
        height,
        cols,
        gridster.$widgets
      );
      gridster.add_widget(
        widgetContainer,
        width,
        height,
        slot.col,
        slot.row
      );
    },

    mkCompactGridsterUp: function (gridster) {
      if (!this.mkIsDashboardView() || !gridster || !gridster.$widgets) {
        return false;
      }
      if (!gridster.$widgets.length) {
        return false;
      }
      var changed = false;
      var pass;
      for (pass = 0; pass < 16; pass++) {
        var moved = false;
        gridster.$widgets.each(function () {
          var $w = jQuery(this);
          var before = parseInt($w.attr("data-row"), 10) || 1;
          gridster.move_widget_up($w);
          if ((parseInt($w.attr("data-row"), 10) || 1) < before) {
            moved = true;
            changed = true;
          }
        });
        if (!moved) {
          break;
        }
      }
      return changed;
    },

    mkFitGridsterCanvas: function (gridsterEl) {
      var $gs = gridsterEl && gridsterEl.length ? gridsterEl : this.getContainer();
      if (!$gs || !$gs.length) {
        return;
      }
      var maxBottom = 0;
      $gs.find("li.dashboardWidget").each(function () {
        var $li = jQuery(this);
        var top = parseFloat($li.css("top")) || 0;
        var h = $li.outerHeight(true) || 0;
        maxBottom = Math.max(maxBottom, top + h);
      });
      var $ul = $gs.find("> ul").first();
      if (maxBottom < 80) {
        $gs.removeClass("mk-gridster-fitted");
        $gs.css({ minHeight: "", height: "", maxHeight: "" });
        $ul.css({ minHeight: "", height: "", maxHeight: "" });
        return;
      }
      var canvasH = Math.ceil(maxBottom + 32) + "px";
      $gs.addClass("mk-gridster-fitted");
      $gs.css({ minHeight: canvasH, height: "", maxHeight: "" });
      $ul.css({ minHeight: canvasH, height: "", maxHeight: "" });
    },

    mkRefitDashboardGrid: function (widgetContainer) {
      var thisInstance = this;
      var run = function () {
        var $gs = widgetContainer
          ? widgetContainer.closest('[class^="gridster_"]')
          : thisInstance.getContainer();
        thisInstance.mkFitGridsterCanvas($gs);
        thisInstance.flushDashboardPositionsSync();
      };
      run();
      setTimeout(run, 120);
      setTimeout(run, 500);
    },

    savePositions: function (widgets) {
      var widgetRowColPositions = {};
      var index;
      var len = widgets.length;
      for (index = 0; index < len; ++index) {
        var widget = jQuery(widgets[index]);
        widgetRowColPositions[widget.attr("id")] = JSON.stringify({
          row: widget.attr("data-row"),
          col: widget.attr("data-col"),
        });
      }
      if (!len) {
        return jQuery.Deferred().resolve().promise();
      }
      var params = {
        module: "Vtiger",
        action: "SaveWidgetPositions",
        positionsmap: widgetRowColPositions,
      };
      return app.request.post({ data: params });
    },

    flushDashboardPositionsSync: function () {
      if (!this.mkIsDashboardView()) {
        return;
      }
      var widgets = this.getDashboardWidgets();
      if (!widgets.length) {
        return;
      }
      var widgetRowColPositions = {};
      widgets.each(function () {
        var widget = jQuery(this);
        var id = widget.attr("id");
        if (!id) {
          return;
        }
        widgetRowColPositions[id] = JSON.stringify({
          row: widget.attr("data-row"),
          col: widget.attr("data-col"),
        });
      });
      if (jQuery.isEmptyObject(widgetRowColPositions)) {
        return;
      }
      try {
        var payload = {
          module: "Vtiger",
          action: "SaveWidgetPositions",
          positionsmap: widgetRowColPositions,
        };
        if (
          typeof csrfMagicName !== "undefined" &&
          typeof csrfMagicToken !== "undefined"
        ) {
          payload[csrfMagicName] = csrfMagicToken;
        }
        jQuery.ajax({
          url: "index.php",
          type: "POST",
          async: false,
          data: payload,
        });
      } catch (flushErr) {
        /* ignore navigation race */
      }
    },

    registerDashboardPersistence: function () {
      var thisInstance = this;
      if (thisInstance.mkDashboardPersistenceBound) {
        return;
      }
      thisInstance.mkDashboardPersistenceBound = true;

      jQuery(document).on("click.mkDashPersist", "a[href]", function () {
        if (!thisInstance.mkIsDashboardView()) {
          return;
        }
        var href = jQuery(this).attr("href") || "";
        if (
          href.indexOf("javascript:") === 0 ||
          href === "#" ||
          href.indexOf("view=DashBoard") !== -1
        ) {
          return;
        }
        thisInstance.flushDashboardPositionsSync();
      });

      jQuery(window).on("beforeunload.mkDashPersist pagehide.mkDashPersist", function () {
        thisInstance.flushDashboardPositionsSync();
      });

      var dashBoardContainer = this.getDashboardContainer();
      dashBoardContainer.on("show.bs.tab", ".dashboardTab", function () {
        thisInstance.flushDashboardPositionsSync();
      });
    },

    saveDashboardLayout: function () {
      this.mkFitGridsterCanvas(this.getContainer());
      this.flushDashboardPositionsSync();
      app.helper.showSuccessNotification({
        message: app.vtranslate("LBL_DASHBOARD_LAYOUT_SAVED"),
      });
      return jQuery.Deferred().resolve().promise();
    },

    getDashboardWidgets: function () {
      return jQuery(".dashboardWidget", jQuery(".tab-pane.active"));
    },

    syncAddWidgetsMenu: function () {
      var tabId = this.getActiveTabId();
      var $menu = Vtiger_DashBoard_Js.getWidgetsListMenu();
      if (!$menu.length) {
        return;
      }
      $menu.find("li").not(".divider").show();
      jQuery("#tab_" + tabId + " li.dashboardWidget").each(function () {
        var $w = jQuery(this);
        var name = $w.data("name");
        var id = $w.attr("id");
        if (!name) {
          return;
        }
        if (
          name === "MiniList" ||
          name === "Notebook" ||
          name === "ChartReportWidget"
        ) {
          if (name === "ChartReportWidget" && id) {
            var reportKey = String(id).split("-")[0];
            $menu.find('a[data-name="ChartReportWidget"]').each(function () {
              var $a = jQuery(this);
              if (String($a.data("linkid")) === reportKey) {
                $a.closest("li").hide();
              }
            });
          } else {
            Vtiger_DashBoard_Js.hideSelectableWidgetMenuItem(name);
          }
        } else if (id) {
          Vtiger_DashBoard_Js.hideSelectableWidgetMenuItem(name, id);
        } else {
          Vtiger_DashBoard_Js.hideSelectableWidgetMenuItem(name);
        }
      });
    },

    executeWidgetInlineScripts: function (widgetContainer) {
      jQuery(widgetContainer)
        .find("script")
        .each(function () {
          if (this.src) {
            return;
          }
          var code = this.textContent || this.innerHTML || "";
          if (!jQuery.trim(code)) {
            return;
          }
          if (typeof jQuery.globalEval === "function") {
            jQuery.globalEval(code);
          } else {
            window.eval(code);
          }
        });
      if (typeof window.mkRegisterDashboardWidgets === "function") {
        window.mkRegisterDashboardWidgets();
      }
    },

    mkIsChartDashboardWidget: function (widgetName) {
      return {
        FunnelAmount: true,
        GroupedBySalesPerson: true,
        PipelinedAmountPerSalesPerson: true,
        ChartReportWidget: true,
      }[widgetName || ""];
    },

    mkFinalizeDashboardWidget: function (widgetContainer) {
      var id = widgetContainer.attr("id");
      if (id && this.instancesCache[id]) {
        delete this.instancesCache[id];
      }
      if (typeof window.mkRegisterDashboardWidgets === "function") {
        window.mkRegisterDashboardWidgets();
      }
      return this.getWidgetInstance(widgetContainer);
    },

    mkShowChartLoadError: function (widgetContainer) {
      widgetContainer
        .find(".widgetChartContainer, [name=chartcontent]")
        .first()
        .html(
          '<div class="mk-chart-render-error" style="text-align:center;padding:48px 16px;color:#64748b;">' +
            app.vtranslate("JS_NO_DATA_AVAILABLE") +
            "</div>"
        );
    },

    mkSizeDashboardChartArea: function (widgetContainer, widgetName) {
      if (widgetContainer.closest(".fullscreenview").length) {
        return;
      }
      var $chart = widgetContainer.find(".widgetChartContainer").first();
      if (!$chart.length) {
        return;
      }
      var isMultibar =
        widgetName === "GroupedBySalesPerson" ||
        widgetName === "PipelinedAmountPerSalesPerson";
      var shellH = widgetContainer.height() || 280;
      var headerH =
        widgetContainer.find(".dashboardWidgetHeader").outerHeight(true) || 0;
      var footerH = 38;
      var xLabelsH = isMultibar ? 34 : 0;
      var legendH = isMultibar ? 40 : 0;
      var gap = 10;
      var chartHeight = Math.max(
        130,
        shellH - headerH - footerH - xLabelsH - legendH - gap
      );
      $chart.css({
        height: chartHeight,
        minHeight: chartHeight,
        maxHeight: chartHeight,
        flex: "0 0 auto",
        width: "100%",
      });
      var plot = $chart.data("jqplot");
      if (plot && typeof plot.replot === "function") {
        try {
          plot.replot({ resetAxes: false });
        } catch (e) { /* ignore */ }
      }
    },

    mkIsHomeDashboardPage: function () {
      return (
        jQuery("body").attr("data-view") === "DashBoard" &&
        jQuery("body").attr("data-module") === "Home"
      );
    },

    mkIsDashboardView: function () {
      return jQuery("body").attr("data-view") === "DashBoard";
    },

    mkEnsureSinglePageScroll: function () {
      if (!this.mkIsHomeDashboardPage()) {
        return;
      }
      var selectors =
        "#page, #mk-dash-split-root, .mk-app-shell, .mk-dash-main, .dashBoardContainer, .dashBoardTabContents, .tab-content";
      jQuery(selectors).each(function () {
        var $el = jQuery(this);
        if (
          typeof $el.mCustomScrollbar === "function" &&
          ($el.hasClass("mCustomScrollbar") || $el.find("> .mCSB_container").length)
        ) {
          try {
            $el.mCustomScrollbar("destroy");
          } catch (e) { /* ignore */ }
        }
        $el.css({
          overflow: "",
          overflowY: "",
          overflowX: "",
          height: "",
          maxHeight: "",
        });
      });
      jQuery("body").css({ overflowY: "visible", height: "auto" });
      jQuery("html").css({ overflowY: "scroll", height: "auto" });
      jQuery('[class^="gridster_"]').each(function () {
        var $gs = jQuery(this);
        var $ul = $gs.children("ul").first();
        var maxBottom = 0;
        $gs.find("li.dashboardWidget").each(function () {
          var $li = jQuery(this);
          maxBottom = Math.max(
            maxBottom,
            (parseFloat($li.css("top")) || 0) + ($li.outerHeight(true) || 0)
          );
        });
        if (maxBottom > 80) {
          var h = Math.ceil(maxBottom + 32) + "px";
          $gs.addClass("mk-gridster-fitted");
          $gs.css({ minHeight: h, height: "", maxHeight: "" });
          $ul.css({ minHeight: h, height: "", maxHeight: "" });
        }
      });
    },

    mkDestroyWidgetInnerScroll: function (widgetContainer) {
      var $content = widgetContainer.find(".dashboardWidgetContent").first();
      if (!$content.length) {
        return;
      }
      if (
        typeof $content.mCustomScrollbar === "function" &&
        $content.closest(".mCustomScrollbar").length
      ) {
        try {
          $content.mCustomScrollbar("destroy");
        } catch (e) { /* ignore */ }
      }
      $content.parent(".slimScrollDiv").children().unwrap();
      $content.css({ height: "", maxHeight: "", overflow: "" });
    },

    mkSyncMultibarUserLabels: function (widgetContainer) {
      var $col = widgetContainer.find(".mk-chart-col").first();
      var $xl = $col.find(".mk-dash-chart-xlabels").first();
      if (!$xl.length) {
        return;
      }
      if ($xl.find(".mk-dash-chart-xlabel").length > 0) {
        $xl.css({ display: "flex", visibility: "visible", opacity: 1 });
        return;
      }
      var raw = widgetContainer.find(".widgetData").first();
      if (!raw.length) {
        return;
      }
      var data;
      try {
        data = JSON.parse(
          raw.is('script[type="application/json"]')
            ? $.trim(raw.text())
            : $.trim(raw.val())
        );
      } catch (e) {
        return;
      }
      if (!data || !data.length) {
        return;
      }
      var users = [];
      var i;
      for (i = 0; i < data.length; i++) {
        if (
          data[i].last_name &&
          $.inArray(data[i].last_name, users) === -1
        ) {
          users.push(data[i].last_name);
        }
      }
      $xl.empty().css("display", "flex");
      for (i = 0; i < users.length; i++) {
        jQuery("<span class=\"mk-dash-chart-xlabel\"></span>")
          .text(users[i])
          .attr("title", users[i])
          .appendTo($xl);
      }
    },

    mkPaintDashboardChartWidget: function (
      widgetContainer,
      widgetInstance,
      widgetName
    ) {
      var thisInstance = this;
      if (!widgetInstance) {
        return;
      }
      var attempt = 0;
      var paint = function () {
        attempt += 1;
        var $chart = widgetContainer.find(".widgetChartContainer").first();
        var width =
          ($chart.length && $chart.width()) || widgetContainer.width();
        if (width < 40 && attempt < 25) {
          setTimeout(paint, 50);
          return;
        }
        if ($chart.length) {
          thisInstance.mkSizeDashboardChartArea(widgetContainer, widgetName);
          if (
            widgetName === "GroupedBySalesPerson" ||
            widgetName === "PipelinedAmountPerSalesPerson"
          ) {
            $chart.addClass("mk-chart-multibar");
          }
        }
        try {
          widgetInstance.postLoadWidget();
        } catch (error) {
          if (window.console && console.error) {
            console.error(
              "[Dashboard] chart render failed",
              error,
              widgetName
            );
          }
        }
        setTimeout(function () {
          thisInstance.mkSizeDashboardChartArea(widgetContainer, widgetName);
          thisInstance.mkSyncMultibarUserLabels(widgetContainer);
          thisInstance.updateWidgetFontSizes(widgetContainer);
        }, 100);
        setTimeout(function () {
          thisInstance.mkSizeDashboardChartArea(widgetContainer, widgetName);
          thisInstance.mkSyncMultibarUserLabels(widgetContainer);
          thisInstance.updateWidgetFontSizes(widgetContainer);
        }, 500);
      };
      setTimeout(paint, 0);
    },

    mkClearDashboardWidgetShell: function (widgetContainer) {
      var id = widgetContainer.attr("id");
      if (id && this.instancesCache && this.instancesCache[id]) {
        delete this.instancesCache[id];
      }
      widgetContainer.removeData("mk-widget-loading");
      widgetContainer.children().remove();
    },

    mkShowWidgetLoadError: function (widgetContainer) {
      if (widgetContainer.find(".dashboardWidgetHeader").length) {
        return;
      }
      var widgetLabel = widgetContainer.data("name") || "Widget";
      widgetContainer.prepend(
        '<div class="dashboardWidgetHeader"><div class="title"><div class="dashboardTitle"><b>' +
          app.vtranslate(widgetLabel, "Vtiger") +
          '</b></div></div></div><div class="dashboardWidgetContent"><div class="noDataMsg" style="padding:40px 16px;text-align:center;color:#64748b;">' +
          app.vtranslate("JS_NO_DATA_AVAILABLE") +
          "</div></div>"
      );
    },

    loadWidgets: function () {
      var thisInstance = this;
      if (typeof window.mkRegisterDashboardWidgets === "function") {
        window.mkRegisterDashboardWidgets();
      }
      var widgetList = thisInstance.getDashboardWidgets();
      widgetList.each(function (index, widgetContainerELement) {
        var $widget = jQuery(widgetContainerELement);
        if (
          $widget.hasClass("loadcompleted") &&
          $widget.find(".dashboardWidgetHeader, .dashboardWidgetContent").length
        ) {
          return;
        }
        $widget.removeClass("loadcompleted");
        thisInstance.loadWidget($widget);
      });
    },

    isScrolledIntoView: function (elem) {
      var viewportWidth = jQuery(window).width(),
        viewportHeight = jQuery(window).height(),
        documentScrollTop = jQuery(document).scrollTop(),
        documentScrollLeft = jQuery(document).scrollLeft(),
        minTop = documentScrollTop,
        maxTop = documentScrollTop + viewportHeight,
        minLeft = documentScrollLeft,
        maxLeft = documentScrollLeft + viewportWidth,
        $targetElement = jQuery(elem),
        elementOffset = $targetElement.offset();
      if (
        elementOffset.top + $targetElement.outerHeight() > minTop &&
        elementOffset.top < maxTop &&
        elementOffset.left + $targetElement.outerWidth() > minLeft &&
        elementOffset.left < maxLeft
      ) {
        return true;
      } else {
        return false;
      }
    },

    loadWidget: function (widgetContainer) {
      var thisInstance = this;
      var urlParams = widgetContainer.data("url");
      var mode = widgetContainer.data("mode");
      if (!urlParams) {
        return;
      }
      if (widgetContainer.data("mk-widget-loading")) {
        return;
      }
      widgetContainer.data("mk-widget-loading", true);

      var activeTabId = this.getActiveTabId();
      urlParams += "&tab=" + activeTabId;
      app.helper.showProgress();
      if (mode == "open") {
        app.request.post({ url: urlParams }).then(function (err, data) {
          var widgetName = widgetContainer.data("name");
          app.helper.hideProgress();
          widgetContainer.removeData("mk-widget-loading");
          if (err) {
            if (window.console && console.error) {
              console.error("[Dashboard] widget load failed", err, widgetName);
            }
            thisInstance.mkShowWidgetLoadError(widgetContainer);
            return;
          }
          if (data == null || (typeof data === "string" && !jQuery.trim(data))) {
            if (window.console && console.error) {
              console.error("[Dashboard] widget empty response", widgetName, data);
            }
            thisInstance.mkShowWidgetLoadError(widgetContainer);
            return;
          }
          if (typeof data === "object") {
            if (window.console && console.error) {
              console.error("[Dashboard] widget JSON error response", widgetName, data);
            }
            thisInstance.mkShowWidgetLoadError(widgetContainer);
            return;
          }
          thisInstance.mkClearDashboardWidgetShell(widgetContainer);
          widgetContainer.prepend(data);
          thisInstance.executeWidgetInlineScripts(widgetContainer);
          vtUtils.applyFieldElementsView(widgetContainer);

          var widgetChartContainer = widgetContainer.find(
            ".widgetChartContainer"
          );
          if (widgetChartContainer.length > 0) {
            thisInstance.mkSizeDashboardChartArea(
              widgetContainer,
              widgetContainer.data("name")
            );
          }

          var widgetInstance =
            thisInstance.mkFinalizeDashboardWidget(widgetContainer);
          thisInstance.mkDestroyWidgetInnerScroll(widgetContainer);
          thisInstance.mkPaintDashboardChartWidget(
            widgetContainer,
            widgetInstance,
            widgetName
          );
          widgetContainer.addClass("loadcompleted");
          thisInstance.mkRefitDashboardGrid(widgetContainer);
        });
      }
    },

    registerRefreshWidget: function () {
      var thisInstance = this;
      this.getContainer().on("click", 'a[name="drefresh"]', function (e) {
        var element = $(e.currentTarget);
        var parent = element.closest("li");
        var widgetInstnace = thisInstance.getWidgetInstance(parent);
        widgetInstnace.refreshWidget();
        return;
      });
    },

    removeWidget: function () {
      this.getContainer().on("click", 'li a[name="dclose"]', function (e) {
        var element = $(e.currentTarget);
        var listItem = jQuery(element).parents("li");
        var width = listItem.attr("data-sizex");
        var height = listItem.attr("data-sizey");

        var url = element.data("url");
        var parent = element.closest(".dashBoardWidgetFooter").parent();
        var widgetName = parent.data("name");
        var widgetTitle = parent.find(".dashboardTitle").attr("title");
        var activeTabId = element.closest(".tab-pane").data("tabid");

        var message = app.vtranslate(
          "JS_ARE_YOU_SURE_TO_DELETE_WIDGET",
          widgetTitle
        );
        app.helper
          .showConfirmationBox({ message: message, htmlSupportEnable: false })
          .then(function (e) {
            app.helper.showProgress();
            app.request.post({ url: url }).then(function (err, response) {
              if (err == null) {
                var nonReversableWidgets = [
                  "MiniList",
                  "Notebook",
                  "ChartReportWidget",
                ];

                parent.fadeOut("slow", function () {
                  Vtiger_DashBoard_Js.gridster.remove_widget(parent);
                  parent.remove();
                  if (Vtiger_DashBoard_Js.currentInstance) {
                    Vtiger_DashBoard_Js.currentInstance.mkRefitDashboardGrid();
                  }
                });
                if (jQuery.inArray(widgetName, nonReversableWidgets) == -1) {
                  var $menu = Vtiger_DashBoard_Js.getWidgetsListMenu();
                  var $existing = $menu.find(
                    'a[data-linkid="' + response.linkid + '"]'
                  );
                  if ($existing.length) {
                    Vtiger_DashBoard_Js.showSelectableWidgetMenuItem(
                      response.name,
                      response.linkid
                    );
                  } else {
                    var data =
                      "<li><a onclick=\"Vtiger_DashBoard_Js.addWidget(this, '" +
                      response.url +
                      '\')" href="javascript:void(0);"';
                    data +=
                      " data-width=" +
                      width +
                      " data-height=" +
                      height +
                      " data-linkid=" +
                      response.linkid +
                      " data-name=" +
                      response.name +
                      ">" +
                      response.title +
                      "</a></li>";
                    var divider = $menu.find(".divider");
                    if (divider.length) {
                      jQuery(data).insertBefore(divider);
                    } else {
                      jQuery(data).appendTo($menu);
                    }
                  }
                }
              }
              app.helper.hideProgress();
            });
          });
      });
    },

    registerLazyLoadWidgets: function () {
      var thisInstance = this;
      jQuery(window).bind("scroll", function () {
        var widgetList = jQuery(".dashboardWidget").not(".loadcompleted");
        if (!widgetList[0]) {
          // We shouldn't unbind as we might have widgets in another tab
          //jQuery(window).unbind('scroll');
        }
        widgetList.each(function (index, widgetContainerELement) {
          if (thisInstance.isScrolledIntoView(widgetContainerELement)) {
            thisInstance.loadWidget(jQuery(widgetContainerELement));
          }
        });
      });
    },

    mkExtractWidgetDataJson: function (widgetContainer) {
      var $script = widgetContainer.find(
        'script.widgetData[type="application/json"]'
      );
      if ($script.length) {
        return jQuery.trim($script.text());
      }
      var $input = widgetContainer.find("input.widgetData");
      if ($input.length) {
        return jQuery.trim($input.val());
      }
      return "";
    },

    mkGetFullscreenChartModule: function (widgetName) {
      if (
        widgetName === "GroupedBySalesPerson" ||
        widgetName === "PipelinedAmountPerSalesPerson" ||
        widgetName === "FunnelAmount"
      ) {
        return "Potentials";
      }
      return app.getModuleName();
    },

    mkPaintFullscreenChartWidget: function (modalWidgetContainer, widgetName) {
      var moduleName = this.mkGetFullscreenChartModule(widgetName);
      var widgetInstance = Vtiger_Widget_Js.getInstance(
        modalWidgetContainer,
        widgetName,
        moduleName
      );
      modalWidgetContainer
        .find(".mk-chart-col")
        .first()
        .css({ paddingLeft: "24px", paddingRight: "12px", overflow: "visible" });
      var $chart = modalWidgetContainer.find(".widgetChartContainer").first();
      $chart.css({
        minHeight: "380px",
        height: "calc(65vh - 140px)",
        width: "100%",
        flex: "1 1 auto",
        overflow: "visible",
        boxSizing: "border-box",
      });
      this.mkPaintDashboardChartWidget(
        modalWidgetContainer,
        widgetInstance,
        widgetName
      );
      var self = this;
      setTimeout(function () {
        self.mkSyncMultibarUserLabels(modalWidgetContainer);
        var plot = $chart.data("jqplot");
        if (plot && typeof plot.replot === "function") {
          try {
            plot.replot({ resetAxes: true });
          } catch (e) { /* ignore */ }
        }
      }, 400);
      setTimeout(function () {
        var plot2 = $chart.data("jqplot");
        if (plot2 && typeof plot2.replot === "function") {
          try {
            plot2.replot({ resetAxes: true });
          } catch (e2) { /* ignore */ }
        }
      }, 900);
    },

    registerWidgetFullScreenView: function () {
      var thisInstance = this;
      this.getContainer().on(
        "click",
        'a[name="widgetFullScreen"]',
        function (e) {
          var currentTarget = jQuery(e.currentTarget);
          var widgetContainer = currentTarget.closest("li");
          var widgetName = widgetContainer.data("name");
          var widgetTitle = widgetContainer.find(".dashboardTitle").text();
          var widgetDataJson =
            thisInstance.mkExtractWidgetDataJson(widgetContainer);
          var chartType = "";
          if (widgetContainer.find('input[name="charttype"]').length) {
            chartType = widgetContainer.find('input[name="charttype"]').val();
          }
          var clickThrough = 0;
          if (widgetContainer.find('input[name="clickthrough"]').length) {
            clickThrough = widgetContainer
              .find('input[name="clickthrough"]')
              .val();
          }
          var isMultibar =
            widgetName === "GroupedBySalesPerson" ||
            widgetName === "PipelinedAmountPerSalesPerson";
          var xlabelsHtml = widgetContainer
            .find(".mk-dash-chart-xlabels")
            .first()
            .html();
          var legendClone = widgetContainer
            .find(".mk-dash-chart-legend")
            .first()
            .clone();
          var chartStageClass =
            "widgetChartContainer mk-chart-stage" +
            (isMultibar ? " mk-chart-multibar" : "");

          var fullscreenview =
            '<div class="fullscreencontents modal-dialog modal-lg">\n\
									<div class="modal-content mk-dash-fullscreen-modal">\n\
									<div class="modal-header backgroundColor">\n\
										<div class="clearfix">\n\
											<div class="pull-right">\n\
												<button data-dismiss="modal" class="close" title="' +
            app.vtranslate("JS_CLOSE") +
            '"><span aria-hidden="true" class="fa fa-close"></span></button>\n\
											</div>\n\
											<h4 class="pull-left">' +
            widgetTitle +
            '</h4>\n\
										</div>\n\
									</div>\n\
									<div class="modal-body mk-dash-fullscreen-body" style="overflow:auto;">\n\
										<ul style="list-style:none;margin:0;padding:0;"><li id="fullscreenpreview" class="dashboardWidget fullscreenview" data-name="' +
            widgetName +
            '">\n\
											<div class="dashboardWidgetContent mk-fullscreen-widget-content" data-displaymode="fullscreen">';

          if (chartType !== "") {
            fullscreenview +=
              '<input type="hidden" value="' +
              chartType +
              '" name="charttype">\n\
												<input type="hidden" value="' +
              clickThrough +
              '" name="clickthrough">\n\
												<div id="chartDiv" name="chartcontent" class="widgetChartContainer" style="width:100%;min-height:420px;height:55vh;"></div>\n\
												<input class="widgetData" type="hidden" name="data" value="">';
          } else {
            fullscreenview +=
              '<script type="application/json" class="widgetData"></script>\n\
												<div class="row mk-dashboard-chart-row" style="margin:0;">\n\
													<div class="col-xs-12 mk-chart-col">\n\
														<div class="' +
              chartStageClass +
              '" name="chartcontent"></div>\n\
														<div class="mk-dash-chart-xlabels">' +
              (xlabelsHtml || "") +
              '</div>\n\
													</div>\n\
												</div>';
          }
          fullscreenview += "</div></li></ul></div></div></div>";

          var callback = function (modalData) {
            var element = jQuery(modalData);
            var modal = jQuery(".myModal", element);
            modal.parent().css({
              top: "30px",
              left: "30px",
              right: "30px",
              bottom: "30px",
            });
            modal.css("height", "100%");
            var modalWidgetContainer = element.find(".fullscreenview").first();
            if (!modalWidgetContainer.length) {
              modalWidgetContainer = jQuery(".fullscreenview").first();
            }

            if (chartType !== "") {
              modalWidgetContainer
                .find("input.widgetData")
                .val(widgetDataJson);
              var chartClassName = chartType.toCamelCase();
              var chartClass = window["Report_" + chartClassName + "_Js"];
              if (typeof chartClass === "function") {
                chartClass(
                  "Vtiger_ChartReportWidget_Widget_Js",
                  {},
                  {
                    init: function () {
                      this._super(modalWidgetContainer);
                    },
                  }
                );
              }
              var reportInstance = Vtiger_Widget_Js.getInstance(
                modalWidgetContainer,
                widgetName
              );
              modalWidgetContainer.trigger(
                Vtiger_Widget_Js.widgetPostLoadEvent
              );
              if (reportInstance && reportInstance.postLoadWidget) {
                reportInstance.postLoadWidget();
              }
            } else {
              modalWidgetContainer
                .find('script.widgetData[type="application/json"]')
                .text(widgetDataJson);
              if (legendClone.length) {
                modalWidgetContainer
                  .find(".mk-chart-col")
                  .first()
                  .append(legendClone);
              }
              var paintFullscreen = function () {
                thisInstance.mkPaintFullscreenChartWidget(
                  modalWidgetContainer,
                  widgetName
                );
              };
              modal.one("shown.bs.modal", function () {
                setTimeout(paintFullscreen, 80);
              });
              setTimeout(paintFullscreen, 200);
            }
          };
          app.helper.showModal(fullscreenview, { cb: callback });
        }
      );
    },

    registerFilterInitiater: function () {
      var container = this.getContainer();
      container.on("click", 'a[name="dfilter"]', function (e) {
        var widgetContainer = jQuery(e.currentTarget).closest(
          ".dashboardWidget"
        );
        var filterContainer = widgetContainer.find(".filterContainer");
        var dashboardWidgetFooter = jQuery(
          ".dashBoardWidgetFooter",
          widgetContainer
        );

        widgetContainer.toggleClass("dashboardFilterExpanded");
        filterContainer.slideToggle(500);

        var callbackFunction = function () {
          widgetContainer.toggleClass("dashboardFilterExpanded");
          filterContainer.slideToggle(500);
        };
        //adding clickoutside event on the dashboardWidgetHeader
        var helper = new Vtiger_Helper_Js();
        helper.addClickOutSideEvent(dashboardWidgetFooter, callbackFunction);

        return false;
      });
    },

    registerDeleteDashboardTab: function () {
      var self = this;
      var dashBoardContainer = this.getDashboardContainer();
      dashBoardContainer.off("click", ".deleteTab");
      dashBoardContainer.on("click", ".deleteTab", function (e) {
        // To prevent tab click event
        e.preventDefault();
        e.stopPropagation();

        var currentTarget = jQuery(e.currentTarget);
        var tab = currentTarget.closest(".dashboardTab");

        var tabId = tab.data("tabid");
        var tabName = tab.data("tabname");
        var message = app.vtranslate(
          "JS_ARE_YOU_SURE_TO_DELETE_DASHBOARDTAB",
          tabName
        );
        app.helper
          .showConfirmationBox({ message: message, htmlSupportEnable: false })
          .then(function (e) {
            app.helper.showProgress();
            var data = {
              module: "Vtiger",
              action: "DashBoardTab",
              mode: "deleteTab",
              tabid: tabId,
            };

            app.request.post({ data: data }).then(function (err, data) {
              app.helper.hideProgress();
              if (err == null) {
                jQuery('li[data-tabid="' + tabId + '"]').remove();
                jQuery(".tab-content #tab_" + tabId).remove();

                if (jQuery(".dashboardTab.active").length <= 0) {
                  // click the first tab if none of the tabs are active
                  var firstTab = jQuery(".dashboardTab").get(0);
                  jQuery(firstTab).find("a").click();
                }

                app.helper.showSuccessNotification({ message: "" });
                if (
                  jQuery(".dashboardTab").length <
                  Vtiger_DashBoard_Js.dashboardTabsLimit
                ) {
                  var element = dashBoardContainer.find("li.disabled");
                  self.removeQtip(element);
                }
              } else {
                app.helper.showErrorNotification({ message: err });
              }
            });
          });
      });
    },

    registerAddDashboardTab: function () {
      var self = this;
      var dashBoardContainer = this.getDashboardContainer();
      dashBoardContainer.off("click", ".addNewDashBoard");
      dashBoardContainer.on("click", ".addNewDashBoard", function (e) {
        if (
          jQuery(".dashboardTab").length >=
          Vtiger_DashBoard_Js.dashboardTabsLimit
        ) {
          app.helper.showErrorNotification({
            message: app.vtranslate("JS_TABS_LIMIT_EXCEEDED"),
          });
          return;
        }
        var currentElement = jQuery(e.currentTarget);
        var data = {
          module: "Home",
          view: "DashBoardTab",
          mode: "showDashBoardAddTabForm",
        };

        app.request.post({ data: data }).then(function (err, res) {
          if (err === null) {
            var cb = function (data) {
              var form = jQuery(data).find("#AddDashBoardTab");
              var params = {
                submitHandler: function (form) {
                  var labelEle = jQuery(form).find('[name="tabName"]');
                  var tabName = labelEle.val().trim();
                  if (tabName.length > 50) {
                    vtUtils.showValidationMessage(
                      labelEle,
                      app.vtranslate("JS_TAB_LABEL_EXCEEDS_CHARS", 50),
                      {
                        position: {
                          my: "bottom left",
                          at: "top left",
                          container: jQuery(form),
                        },
                      }
                    );
                    return false;
                  } else {
                    vtUtils.hideValidationMessage(labelEle);
                  }

                  var params = jQuery(form).serializeFormData();
                  params["tabName"] = params["tabName"].trim();
                  app.request.post({ data: params }).then(function (err, data) {
                    app.helper.hideModal();
                    if (err) {
                      app.helper.showErrorNotification({ message: err });
                    } else {
                      var tabid = data["tabid"];
                      var tabname = data["tabname"];
                      var tabEle =
                        '<li class="dashboardTab" data-tabid="' +
                        tabid +
                        '" data-tabname="' +
                        tabname +
                        '">';
                      tabEle +=
                        '<a data-toggle="tab" href="#tab_' +
                        tabid +
                        '">\n\
														<div>\n\
															<span class="name textOverflowEllipsis" style="width:10%">\n\
															<strong></strong>\n\
															</span>\n\
															<span class="editTabName hide"><input type="text" name="tabName"></span>\n\
															<i class="fa fa-close deleteTab"></i>\n\
															<i class="fa fa-bars moveTab hide"></i>\n\
														</div>\n\
														</a>';
                      tabEle += "</li>";

                      var tabContentEle =
                        '<div id="tab_' +
                        tabid +
                        '" class="tab-pane fade" data-tabid="' +
                        tabid +
                        '"></div>';

                      dashBoardContainer
                        .find(".mk-dashboard-tab-insert-anchor")
                        .before(tabEle);
                      dashBoardContainer
                        .find(".mk-dashboard-tab-insert-anchor")
                        .prev()
                        .find(".name > strong")
                        .text(tabname);
                      dashBoardContainer
                        .find(".tab-content")
                        .append(tabContentEle);

                      // selecting added tab
                      var currentTab = jQuery('li[data-tabid="' + tabid + '"]');
                      currentTab.find("a").click();
                      if (
                        jQuery(".dashboardTab").length >=
                        Vtiger_DashBoard_Js.dashboardTabsLimit
                      ) {
                        jQuery("#newDashBoardLi").addClass("disabled");
                        self.registerQtipMessage();
                      }
                    }
                  });
                },
              };
              form.vtValidate(params);
            };
            app.helper.showModal(res, { cb: cb });
          }
        });
      });
    },
    removeQtip: function (element) {
      jQuery(element).qtip("destroy");
      element.removeClass("disabled");
    },

    registerQtipMessage: function () {
      var dashBoardContainer = this.getDashboardContainer();
      var element = dashBoardContainer.find("li.disabled");
      var title = app.vtranslate("JS_TABS_LIMIT_EXCEEDED");
      jQuery(element).qtip({
        content: title,
        hide: {
          event: "click mouseleave",
        },
        position: {
          my: "bottom center",
          at: "top left",
          adjust: {
            x: 30,
            y: 10,
          },
        },
        style: {
          classes: "qtip-dark",
        },
      });
    },
    registerDashBoardTabRename: function () {
      var container = this.getContainer();
      var dashBoardContainer = jQuery(container).closest(".dashBoardContainer");

      dashBoardContainer.on("dblclick", ".dashboardTab", function (e) {
        e.preventDefault();
        e.stopPropagation();

        var currentTarget = jQuery(e.currentTarget);
        if (jQuery(".editTabName:visible").length > 0) {
          return;
        }
        var nameEle = currentTarget.find(".name");
        var oldName = nameEle.attr("value");
        var editEle = currentTarget.find(".editTabName");

        // Lock renaming default dashboard for user (which otherwise would be recreated)
        if (oldName == "My Dashboard") {
          return;
        }

        nameEle.addClass("hide");
        editEle.removeClass("hide");
        editEle.find("input").val(oldName);

        currentTarget.on("clickoutside", function (e) {
          var newName = editEle.find("input").val();
          var tabId = currentTarget.data("tabid");

          if (newName.trim() == "") {
            vtUtils.showValidationMessage(
              editEle,
              app.vtranslate("JS_TAB_NAME_SHOULD_NOT_BE_EMPTY"),
              {
                position: {
                  my: "top left",
                  at: "bottom left",
                  container: editEle.closest(".dashboardTab"),
                },
              }
            );
            return false;
          }
          vtUtils.hideValidationMessage(editEle);

          if (newName.length > 50) {
            vtUtils.showValidationMessage(
              editEle,
              app.vtranslate("JS_TAB_LABEL_EXCEEDS_CHARS", 50),
              {
                position: {
                  my: "bottom left",
                  at: "top left",
                  container: jQuery(".module-action-content"),
                },
              }
            );
            return false;
          } else {
            vtUtils.hideValidationMessage(editEle);
          }
          currentTarget.off("clickoutside");
          if (newName != oldName) {
            var data = {
              module: "Vtiger",
              action: "DashBoardTab",
              mode: "renameTab",
              tabid: tabId,
              tabname: newName,
            };
            currentTarget.find(".name > strong").text(newName);
            app.helper.showProgress();
            app.request.post({ data: data }).then(function (err, data) {
              app.helper.hideProgress();
              if (err == null) {
                app.helper.showSuccessNotification({ message: "" });
                currentTarget.data("tabname", newName);
              } else {
                app.helper.showErrorNotification({ message: err });
                currentTarget.find(".name > strong").text(oldName);
              }
            });
          }
          nameEle.attr("value", newName);

          editEle.addClass("hide");
          nameEle.removeClass("hide");
        });
      });
    },

    registerDashBoardTabClick: function () {
      var thisInstance = this;
      var container = this.getContainer();
      var dashBoardContainer = jQuery(container).closest(".dashBoardContainer");

      dashBoardContainer.on("shown.bs.tab", ".dashboardTab", function (e) {
        var currentTarget = jQuery(e.currentTarget);
        var tabid = currentTarget.data("tabid");
        app.changeURL("index.php?module=Home&view=DashBoard&tabid=" + tabid);

        // If tab is already loaded earlier then we shouldn't reload tab and register gridster
        if (
          typeof jQuery("#tab_" + tabid)
            .find(".dashBoardTabContainer")
            .val() !== "undefined"
        ) {
          // We should overwrite gridster with current tab which is clicked

          var widgetMargin = 10;
          var cols = thisInstance.getgridColumns();
          $(".mainContainer").css("min-width", "500px");
          var col_width =
            cols === 1
              ? Math.floor(($(".mainContainer").width() - 41) / cols) -
                2 * widgetMargin
              : Math.floor(($(window).width() - 41) / cols) - 2 * widgetMargin;

          Vtiger_DashBoard_Js.gridster = thisInstance
            .getContainer(tabid)
            .gridster({
              // Need to set the base dimensions to eliminate widgets overlapping
              widget_base_dimensions: [col_width, 300],
            })
            .data("gridster");

          thisInstance.syncAddWidgetsMenu();
          return;
        }
        var data = {
          module: "Home",
          view: "DashBoardTab",
          mode: "getTabContents",
          tabid: tabid,
        };

        app.request.post({ data: data }).then(function (err, data) {
          if (err === null) {
            var dashBoardModuleName = jQuery("#tab_" + tabid, ".tab-content")
              .html(data)
              .find('[name="dashBoardModuleName"]')
              .val();
            if (
              typeof dashBoardModuleName != "undefined" &&
              dashBoardModuleName.length > 0
            ) {
              var dashBoardInstanceClassName = app.getModuleSpecificViewClass(
                app.view(),
                dashBoardModuleName
              );
              if (dashBoardInstanceClassName != null) {
                var dashBoardInstance = new window[
                  dashBoardInstanceClassName
                ]();
              }
            }
            app.event.trigger("post.DashBoardTab.load", dashBoardInstance);
          }
        });
      });
    },

    registerRearrangeTabsEvent: function () {
      var thisInstance = this;
      var dashBoardContainer = this.getDashboardContainer();

      // on click of Rearrange button
      dashBoardContainer.on(
        "click",
        "ul.moreDashBoards .reArrangeTabs",
        function (e) {
          var currentEle = jQuery(e.currentTarget);
          dashBoardContainer.find(".dashBoardDropDown").addClass("hide");

          var sortableContainer = dashBoardContainer.find(".tabContainer");
          var sortableEle = sortableContainer.find(".sortable");

          currentEle.addClass("hide");
          dashBoardContainer.find(".deleteTab").addClass("hide");
          dashBoardContainer.find(".moveTab").removeClass("hide");

          sortableEle.sortable({
            containment: sortableContainer,
            stop: function () {},
          });
        }
      );

      // Save widget layout and/or dashboard tab order
      dashBoardContainer.find(".updateSequence").on("click", function (e) {
        var currEle = jQuery(e.currentTarget);
        var sortableEle = dashBoardContainer
          .find(".tabContainer")
          .find(".sortable");
        var isTabRearrange = sortableEle.hasClass("ui-sortable");

        var finishTabRearrange = function () {
          dashBoardContainer.find(".moveTab").addClass("hide");
          dashBoardContainer.find(".reArrangeTabs").removeClass("hide");
          dashBoardContainer.find(".deleteTab").removeClass("hide");
          dashBoardContainer.find(".dashBoardDropDown").removeClass("hide");
          if (sortableEle.hasClass("ui-sortable")) {
            sortableEle.sortable("destroy");
          }
        };

        thisInstance.saveDashboardLayout().then(
          function () {
            if (!isTabRearrange) {
              return;
            }
            var reArrangedList = {};
            jQuery(".sortable li.dashboardTab").each(function (i, el) {
              var tabEl = jQuery(el);
              var tabid = tabEl.data("tabid");
              if (tabid) {
                reArrangedList[tabid] = i + 1;
              }
            });
            var data = {
              module: "Vtiger",
              action: "DashBoardTab",
              mode: "updateTabSequence",
              sequence: JSON.stringify(reArrangedList),
            };
            app.request.post({ data: data }).then(function (err) {
              if (err == null) {
                finishTabRearrange();
                app.helper.showSuccessNotification({
                  message: app.vtranslate("LBL_DASHBOARD_TAB_ORDER_SAVED"),
                });
              } else {
                app.helper.showErrorNotification({ message: err });
              }
            });
          },
          function () {}
        );
      });
    },

    registerEvents: function () {
      var thisInstance = this;
      this.registerLazyLoadWidgets();
      this.registerAddDashboardTab();
      this.registerDashBoardTabClick();
      this.registerDashBoardTabRename();
      this.registerDeleteDashboardTab();
      this.registerRearrangeTabsEvent();
      this.registerDashboardPersistence();
      this.registerQtipMessage();
      app.event.off("post.DashBoardTab.load");
      app.event.on(
        "post.DashBoardTab.load",
        function (event, dashBoardInstance) {
          var instance = thisInstance;
          if (typeof dashBoardInstance != "undefined") {
            instance = dashBoardInstance;
            instance.registerEvents();
          }
          instance.registerGridster();
          instance.loadWidgets();
          instance.syncAddWidgetsMenu();
          instance.registerRefreshWidget();
          instance.removeWidget();
          instance.registerWidgetFullScreenView();
          instance.registerFilterInitiater();
        }
      );
      app.event.trigger("post.DashBoardTab.load");
      thisInstance.mkEnsureSinglePageScroll();
      jQuery(window).on("resize.mkDashSingleScroll", function () {
        thisInstance.mkEnsureSinglePageScroll();
      });
    },
  }
);
