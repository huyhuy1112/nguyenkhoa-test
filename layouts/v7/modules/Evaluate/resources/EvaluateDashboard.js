(function () {
	'use strict';

	function toNumber(v) {
		if (v === null || typeof v === 'undefined') return 0;
		if (typeof v === 'number') return isNaN(v) ? 0 : v;
		var s = String(v).trim();
		if (!s) return 0;
		s = s.replace(/[^\d\.,\-]/g, '');
		if (s.indexOf(',') !== -1 && s.indexOf('.') !== -1) {
			s = s.replace(/,/g, '');
		} else if (s.indexOf(',') !== -1 && s.indexOf('.') === -1) {
			s = s.replace(/,/g, '.');
		}
		var n = parseFloat(s);
		return isNaN(n) ? 0 : n;
	}

	function buildDatasets(data) {
		var labels = [];
		var costs = [];
		var revenues = [];
		var rois = [];

		if (data && Array.isArray(data.rows) && data.rows.length) {
			data.rows.forEach(function (row) {
				labels.push(String(row.campaignname || ''));
				var cost = toNumber(row.actualcost != null ? row.actualcost : (row.cost != null ? row.cost : 0));
				var revenue = toNumber(row.expectedrevenue != null ? row.expectedrevenue : (row.revenue != null ? row.revenue : 0));
				costs.push(cost);
				revenues.push(revenue);
				var roi = 0;
				if (cost > 0) roi = ((revenue - cost) / cost) * 100;
				if (row.roi != null) roi = toNumber(row.roi);
				rois.push(roi);
			});
			return { labels: labels, costs: costs, revenues: revenues, rois: rois };
		}

		labels = (data && Array.isArray(data.campaigns)) ? data.campaigns.slice() : [];
		var rawCosts = (data && Array.isArray(data.costs)) ? data.costs : (data && typeof data.costs !== 'undefined' ? [data.costs] : []);
		var rawRevenues = (data && Array.isArray(data.revenues)) ? data.revenues : (data && typeof data.revenues !== 'undefined' ? [data.revenues] : []);
		var rawRois = (data && Array.isArray(data.rois)) ? data.rois : (data && typeof data.rois !== 'undefined' ? [data.rois] : []);

		costs = rawCosts.map(toNumber);
		revenues = rawRevenues.map(toNumber);
		rois = rawRois.map(toNumber);

		var n = labels.length;
		for (var i = costs.length; i < n; i++) costs.push(0);
		for (var j = revenues.length; j < n; j++) revenues.push(0);
		for (var k = rois.length; k < n; k++) rois.push(0);
		if (costs.length > n) costs = costs.slice(0, n);
		if (revenues.length > n) revenues = revenues.slice(0, n);
		if (rois.length > n) rois = rois.slice(0, n);

		return { labels: labels, costs: costs, revenues: revenues, rois: rois };
	}

	var _charts = {};
	function destroyChart(key) {
		if (_charts[key]) {
			try { _charts[key].destroy(); } catch (e) {}
			_charts[key] = null;
		}
	}

	function barColorForRoi(roi) {
		if (roi > 0.0001) return { bg: 'rgba(34,197,94,0.45)', border: 'rgba(22,163,74,0.95)' };
		if (roi < -0.0001) return { bg: 'rgba(239,68,68,0.4)', border: 'rgba(220,38,38,0.95)' };
		return { bg: 'rgba(148,163,184,0.35)', border: 'rgba(100,116,139,0.9)' };
	}

	function renderCharts(data) {
		if (typeof Chart === 'undefined') return;

		var ds = buildDatasets(data || {});
		if (!ds.labels || !ds.labels.length) {
			return;
		}

		var labels = ds.labels;
		var costs = ds.costs;
		var revenues = ds.revenues;
		var rois = ds.rois;

		var cr = document.getElementById('evalCostRevenueChart');
		if (cr) {
			destroyChart('costRevenue');
			_charts.costRevenue = new Chart(cr, {
				type: 'bar',
				data: {
					labels: labels,
					datasets: [
						{ label: 'Cost', data: costs, backgroundColor: 'rgba(239,68,68,0.32)', borderColor: 'rgba(220,38,38,0.85)', borderWidth: 1 },
						{ label: 'Revenue', data: revenues, backgroundColor: 'rgba(34,197,94,0.32)', borderColor: 'rgba(22,163,74,0.85)', borderWidth: 1 }
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { position: 'bottom' } },
					scales: { x: { ticks: { autoSkip: true, maxRotation: 0, font: { size: 10 } } }, y: { beginAtZero: true } }
				}
			});
		}

		var rc = document.getElementById('evalRoiChart');
		if (rc) {
			destroyChart('roi');
			_charts.roi = new Chart(rc, {
				type: 'bar',
				data: {
					labels: labels,
					datasets: [
						{ label: 'ROI (%)', data: rois, backgroundColor: 'rgba(59,130,246,0.35)', borderColor: 'rgba(37,99,235,0.9)', borderWidth: 1 }
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { position: 'bottom' } },
					scales: { x: { ticks: { autoSkip: true, maxRotation: 0, font: { size: 10 } } }, y: { beginAtZero: true } }
				}
			});
		}

		// Horizontal ROI ranking (replaces weak Monthly Trend line chart)
		var rankCanvas = document.getElementById('evalRoiRankingChart');
		if (rankCanvas) {
			destroyChart('ranking');
			var rLabels = (data && Array.isArray(data.rankingLabels)) ? data.rankingLabels : [];
			var rRois = (data && Array.isArray(data.rankingRois)) ? data.rankingRois.map(toNumber) : [];
			if (rLabels.length === 0 && labels.length) {
				var combined = labels.map(function (lab, i) {
					return { lab: lab, roi: rois[i] || 0 };
				});
				combined.sort(function (a, b) { return b.roi - a.roi; });
				combined = combined.slice(0, 20);
				rLabels = combined.map(function (x) { return x.lab; });
				rRois = combined.map(function (x) { return x.roi; });
			}
			if (rLabels.length === 0) {
				return;
			}
			var bg = rRois.map(function (r) { return barColorForRoi(r).bg; });
			var borders = rRois.map(function (r) { return barColorForRoi(r).border; });
			_charts.ranking = new Chart(rankCanvas, {
				type: 'bar',
				data: {
					labels: rLabels,
					datasets: [{
						label: 'ROI %',
						data: rRois,
						backgroundColor: bg,
						borderColor: borders,
						borderWidth: 1
					}]
				},
				options: {
					indexAxis: 'y',
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							callbacks: {
								label: function (ctx) {
									var v = ctx.parsed.x;
									return 'ROI: ' + (typeof v === 'number' ? v.toFixed(2) : v) + '%';
								}
							}
						}
					},
					scales: {
						x: { beginAtZero: true, ticks: { callback: function (v) { return v + '%'; } } },
						y: { ticks: { font: { size: 10 }, autoSkip: false } }
					}
				}
			});
		}
	}

	function init() {
		var el = document.getElementById('EvaluateDashboardData');
		if (!el) return;
		var raw = (el.textContent || el.innerText || '').trim();
		var data = {};
		try {
			data = JSON.parse(raw || '{}');
		} catch (e) {
			return;
		}
		renderCharts(data);
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();
