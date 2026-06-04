/**
 * Dashboard widget class registry.
 * Inline <script> in widget HTML does not run after jQuery.prepend (AJAX load),
 * so chart widgets must be registered here before loadWidget().
 */
(function ($) {
	'use strict';

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
		var count = [];
		var allLinks = [];
		for (j in stages) {
			if (!Object.prototype.hasOwnProperty.call(stages, j)) {
				continue;
			}
			var salesStageCount = [];
			var links = [];
			for (i in users) {
				if (!Object.prototype.hasOwnProperty.call(users, i)) {
					continue;
				}
				var salesCount = 0;
				var link;
				for (k in data) {
					if (!Object.prototype.hasOwnProperty.call(data, k)) {
						continue;
					}
					var userData = data[k];
					if (userData.sales_stage === stages[j] && userData.last_name === users[i]) {
						salesCount =
							typeof mkParseDashboardChartNumber === 'function'
								? mkParseDashboardChartNumber(userData[valueField])
								: parseFloat(userData[valueField]) || 0;
						link = userData.links;
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
