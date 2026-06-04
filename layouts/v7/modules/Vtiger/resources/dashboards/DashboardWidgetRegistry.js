/**
 * Dashboard widget class registry.
 * Inline <script> in widget HTML does not run after jQuery.prepend (AJAX load),
 * so chart widgets must be registered here before loadWidget().
 */
(function ($) {
	'use strict';

	function stageSortKey(data, stageLabel) {
		var minSort = 9999;
		var k;
		for (k = 0; k < data.length; k++) {
			if (data[k].sales_stage === stageLabel && data[k].stage_sort != null) {
				var s = parseInt(data[k].stage_sort, 10);
				if (!isNaN(s) && s < minSort) {
					minSort = s;
				}
			}
		}
		return minSort;
	}

	function buildSalesPersonChartData(widgetInstance, valueField) {
		var data = widgetInstance.readWidgetData();
		if (!data || !data.length) {
			return { data: [], ticks: [], labels: [], links: [] };
		}
		var users = [];
		var stages = [];
		var i;
		var j;
		var k;
		for (i = 0; i < data.length; i++) {
			if ($.inArray(data[i].last_name, users) === -1) {
				users.push(data[i].last_name);
			}
			if ($.inArray(data[i].sales_stage, stages) === -1) {
				stages.push(data[i].sales_stage);
			}
		}
		stages.sort(function (a, b) {
			return stageSortKey(data, a) - stageSortKey(data, b);
		});
		var count = [];
		var allLinks = [];
		for (j = 0; j < stages.length; j++) {
			var salesStageCount = [];
			var links = [];
			for (i = 0; i < users.length; i++) {
				var salesCount = 0;
				var link = null;
				for (k = 0; k < data.length; k++) {
					var userData = data[k];
					if (userData.sales_stage === stages[j] && userData.last_name === users[i]) {
						salesCount =
							typeof mkParseDashboardChartNumber === 'function'
								? mkParseDashboardChartNumber(userData[valueField])
								: parseFloat(userData[valueField]) || 0;
						link = userData.links || null;
						break;
					}
				}
				links.push(link);
				salesStageCount.push(salesCount);
			}
			allLinks.push(links);
			count.push(salesStageCount);
		}
		return {
			data: count,
			ticks: users,
			labels: stages,
			links: allLinks,
		};
	}

	function registerDashboardWidgets() {
		if (typeof Vtiger_Barchat_Widget_Js === 'undefined') {
			return false;
		}

		if (!window.Vtiger_FunnelAmount_Widget_Js) {
			Vtiger_Barchat_Widget_Js('Vtiger_FunnelAmount_Widget_Js', {}, {});
		}

		if (typeof Vtiger_MultiBarchat_Widget_Js !== 'undefined') {
			if (!window.Vtiger_GroupedBySalesPerson_Widget_Js) {
				Vtiger_MultiBarchat_Widget_Js('Vtiger_GroupedBySalesPerson_Widget_Js', {}, {
					getCharRelatedData: function () {
						return buildSalesPersonChartData(this, 'count');
					},
				});
			}
			if (!window.Vtiger_PipelinedAmountPerSalesPerson_Widget_Js) {
				Vtiger_MultiBarchat_Widget_Js('Vtiger_PipelinedAmountPerSalesPerson_Widget_Js', {}, {
					getCharRelatedData: function () {
						return buildSalesPersonChartData(this, 'amount');
					},
				});
			}
		}
		return true;
	}

	$(function () {
		registerDashboardWidgets();
	});

	window.mkRegisterDashboardWidgets = registerDashboardWidgets;
})(jQuery);
