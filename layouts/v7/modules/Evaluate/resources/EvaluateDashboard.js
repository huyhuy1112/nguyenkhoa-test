(function () {
	'use strict';

	function toNumber(v) {
		if (v === null || typeof v === 'undefined') return 0;
		if (typeof v === 'number') return isNaN(v) ? 0 : v;
		var s = String(v).trim();
		if (!s) return 0;
		// allow "1,234,567" and "1.234.567" and "1,234.56"
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
		// Supports:
		// - data.rows: [{campaignname, actualcost|cost, expectedrevenue|revenue, roi?}, ...]
		// - data.campaigns/costs/revenues/rois: already-built arrays
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
		// Some backends may return scalar instead of array when only 1 item
		var rawCosts = (data && Array.isArray(data.costs)) ? data.costs : (data && typeof data.costs !== 'undefined' ? [data.costs] : []);
		var rawRevenues = (data && Array.isArray(data.revenues)) ? data.revenues : (data && typeof data.revenues !== 'undefined' ? [data.revenues] : []);
		var rawRois = (data && Array.isArray(data.rois)) ? data.rois : (data && typeof data.rois !== 'undefined' ? [data.rois] : []);

		costs = rawCosts.map(toNumber);
		revenues = rawRevenues.map(toNumber);
		rois = rawRois.map(toNumber);

		// normalize lengths so Chart.js always gets N values
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

	function renderCharts(data) {
		if (typeof Chart === 'undefined') return;

		var ds = buildDatasets(data || {});
		if (!ds.labels || !ds.labels.length) {
			console.warn('[Evaluate] No campaign data returned from backend.');
			return;
		}

		var labels = ds.labels;
		var costs = ds.costs;
		var revenues = ds.revenues;
		var rois = ds.rois;

		console.log('[Evaluate] dataset:', data);
		console.log('[Evaluate] labels:', labels);
		console.log('[Evaluate] costs:', costs);
		console.log('[Evaluate] revenues:', revenues);

		var cr = document.getElementById('evalCostRevenueChart');
		if (cr) {
			destroyChart('costRevenue');
			_charts.costRevenue = new Chart(cr, {
				type: 'bar',
				data: {
					labels: labels,
					datasets: [
						{ label: 'Cost', data: costs, backgroundColor: 'rgba(239,68,68,0.35)', borderColor: 'rgba(239,68,68,0.9)', borderWidth: 1 },
						{ label: 'Revenue', data: revenues, backgroundColor: 'rgba(34,197,94,0.35)', borderColor: 'rgba(34,197,94,0.9)', borderWidth: 1 }
					]
				},
				options: {
					responsive: true,
					plugins: { legend: { position: 'bottom' } },
					scales: { x: { ticks: { autoSkip: true, maxRotation: 0 } } }
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
						{ label: 'ROI (%)', data: rois, backgroundColor: 'rgba(59,130,246,0.35)', borderColor: 'rgba(59,130,246,0.9)', borderWidth: 1 }
					]
				},
				options: {
					responsive: true,
					plugins: { legend: { position: 'bottom' } },
					scales: { x: { ticks: { autoSkip: true, maxRotation: 0 } } }
				}
			});
		}

		// Monthly Trend chart (simple index-based for now)
		var mc = document.getElementById('evalMonthlyChart');
		if (mc) {
			var mLabels = (data.months && data.months.length) ? data.months : labels;
			var mCost = (data.monthlyCosts && data.monthlyCosts.length) ? data.monthlyCosts : costs;
			var mRevenue = (data.monthlyRevenues && data.monthlyRevenues.length) ? data.monthlyRevenues : revenues;
			destroyChart('monthly');
			_charts.monthly = new Chart(mc, {
				type: 'line',
				data: {
					labels: mLabels,
					datasets: [
						{ label: 'Cost', data: mCost, borderColor: 'rgba(239,68,68,0.9)', backgroundColor: 'rgba(239,68,68,0.15)', tension: 0.25, fill: true },
						{ label: 'Revenue', data: mRevenue, borderColor: 'rgba(34,197,94,0.9)', backgroundColor: 'rgba(34,197,94,0.15)', tension: 0.25, fill: true }
					]
				},
				options: {
					responsive: true,
					plugins: { legend: { position: 'bottom' } }
				}
			});
		}
	}

	function init() {
		var el = document.getElementById('EvaluateDashboardData');
		if (!el) {
			console.warn('[Evaluate] EvaluateDashboardData script tag not found');
			return;
		}
		var raw = (el.textContent || el.innerText || '').trim();
		var data = {};
		try {
			data = JSON.parse(raw || '{}');
		} catch (e) {
			console.error('[Evaluate] JSON parse error', e);
			return;
		}
		renderCharts(data);
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();

