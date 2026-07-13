/**
 * MKT SALE charts — chạy sau khi Chart.js + JSON đã có trong trang.
 * Không phụ thuộc thứ tự header scripts.
 */
(function () {
	'use strict';

	function parseJsonEl(id) {
		try {
			var el = document.getElementById(id);
			if (!el) return null;
			var raw = (el.textContent || '').replace(/^\s+|\s+$/g, '');
			if (!raw || raw === 'null') return null;
			return JSON.parse(raw);
		} catch (e) {
			return null;
		}
	}

	function hint(id, msg) {
		var el = document.getElementById(id);
		if (el) el.textContent = msg || '';
	}

	function paint(canvasId, config) {
		var canvas = document.getElementById(canvasId);
		if (!canvas || typeof Chart === 'undefined') return null;
		var parent = canvas.parentNode;
		if (parent && parent.clientHeight < 40) {
			parent.style.height = '240px';
		}
		try {
			return new Chart(canvas.getContext('2d'), config);
		} catch (err) {
			hint(canvasId.replace('mgmt-mkt-', 'mgmt-mkt-').replace('-chart', '-hint'), 'Lỗi: ' + (err.message || 'draw'));
			return null;
		}
	}

	function render() {
		if (typeof Chart === 'undefined') {
			hint('mgmt-mkt-daily-hint', 'Không tải được Chart.js');
			hint('mgmt-mkt-month-hint', 'Không tải được Chart.js');
			hint('mgmt-mkt-class-hint', 'Không tải được Chart.js');
			return;
		}
		if (window.__mkMktInlineChartsDone) return;
		window.__mkMktInlineChartsDone = true;

		var daily = parseJsonEl('mgmt-mkt-chart-data') || [];
		var monthly = parseJsonEl('mgmt-mkt-monthly-data') || [];
		var totals = parseJsonEl('mgmt-mkt-totals-data') || {};
		var kpi = parseJsonEl('mgmt-mkt-kpi-data') || {};
		var classDays = parseJsonEl('mgmt-mkt-class-data') || [];
		if (!totals || !Object.keys(totals).length) {
			totals = (kpi && kpi.totals) ? kpi.totals : {};
		}

		var tick = '#64748b';
		var grid = 'rgba(15, 23, 42, 0.07)';
		// Hiện đủ ngày trong khoảng lọc (không chỉ ngày có data)
		var rows = Array.isArray(daily) ? daily.slice() : [];
		rows.sort(function (a, b) {
			return String(a.date || '').localeCompare(String(b.date || ''));
		});

		// 1) Xu hướng theo ngày
		if (rows.length) {
			hint('mgmt-mkt-daily-hint', '');
			paint('mgmt-mkt-daily-chart', {
				type: 'line',
				data: {
					labels: rows.map(function (r) { return r.label || r.date; }),
					datasets: [
						{
							label: 'Tổng Data MKT',
							data: rows.map(function (r) { return Number(r.total_leads) || 0; }),
							borderColor: '#0B6E4F',
							backgroundColor: 'rgba(8, 160, 69, 0.15)',
							fill: true,
							tension: 0.35,
							pointRadius: 2,
							borderWidth: 2.5
						},
						{
							label: 'N.Khoa',
							data: rows.map(function (r) { return Number(r.n_khoa) || 0; }),
							borderColor: '#0284c7',
							backgroundColor: 'transparent',
							tension: 0.35,
							pointRadius: 2,
							borderWidth: 2
						},
						{
							label: 'TikTok',
							data: rows.map(function (r) { return Number(r.tiktok) || 0; }),
							borderColor: '#db2777',
							backgroundColor: 'transparent',
							tension: 0.35,
							pointRadius: 2,
							borderWidth: 2
						}
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
					scales: {
						x: { ticks: { color: tick, maxRotation: 45, autoSkip: true, maxTicksLimit: 16 }, grid: { display: false } },
						y: { beginAtZero: true, ticks: { color: tick, precision: 0 }, grid: { color: grid } }
					}
				}
			});
		} else {
			hint('mgmt-mkt-daily-hint', 'Chưa có dữ liệu ngày.');
		}

		// 2) Nguồn
		var nk = Number(totals.n_khoa) || 0;
		var tt = Number(totals.tiktok) || 0;
		var other = Math.max(0, (Number(totals.total_leads) || 0) - nk - tt);
		paint('mgmt-mkt-source-chart', {
			type: 'doughnut',
			data: {
				labels: ['N.Khoa', 'TikTok', 'Khác'],
				datasets: [{
					data: [nk, tt, other],
					backgroundColor: ['#34d399', '#f9a8d4', '#cbd5e1'],
					borderWidth: 2,
					borderColor: '#fff',
					hoverOffset: 8
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '58%',
				plugins: {
					legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }
				}
			}
		});
		hint('mgmt-mkt-source-hint', (nk + tt + other) ? '' : 'Chưa phân nguồn — gắn tag Facebook/TikTok trên Lead.');

		// 3) KV
		paint('mgmt-mkt-region-chart', {
			type: 'doughnut',
			data: {
				labels: ['KV1', 'KV2', 'KV3', 'K. rõ'],
				datasets: [{
					data: [
						Number(totals.kv1) || 0,
						Number(totals.kv2) || 0,
						Number(totals.kv3) || 0,
						Number(totals.region_unknown) || 0
					],
					backgroundColor: ['#86efac', '#fda4af', '#93c5fd', '#e2e8f0'],
					borderWidth: 2,
					borderColor: '#fff',
					hoverOffset: 8
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '58%',
				plugins: {
					legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }
				}
			}
		});

		// 4) Bảng 2 funnel
		var classRows = (classDays || []).filter(function (r) { return !r.is_summary; });
		if (!classRows.length) {
			hint('mgmt-mkt-class-hint', 'Chưa có ngày học — sẽ hiện khi có lịch / tag lớp.');
			classRows = [{ label: '—', appointments: 0, show: 0, closed: 0 }];
		} else {
			hint('mgmt-mkt-class-hint', '');
		}
		paint('mgmt-mkt-class-chart', {
			type: 'bar',
			data: {
				labels: classRows.map(function (r) { return r.label; }),
				datasets: [
					{
						label: 'Hẹn',
						data: classRows.map(function (r) { return Number(r.appointments) || 0; }),
						backgroundColor: 'rgba(147, 197, 253, 0.9)',
						borderRadius: 6
					},
					{
						label: 'Show',
						data: classRows.map(function (r) { return Number(r.show) || 0; }),
						backgroundColor: 'rgba(253, 230, 138, 0.95)',
						borderRadius: 6
					},
					{
						label: 'Chốt',
						data: classRows.map(function (r) { return Number(r.closed) || 0; }),
						backgroundColor: 'rgba(252, 165, 165, 0.95)',
						borderRadius: 6
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
				scales: {
					x: { ticks: { color: tick }, grid: { display: false } },
					y: { beginAtZero: true, suggestedMax: 5, ticks: { color: tick, precision: 0 }, grid: { color: grid } }
				}
			}
		});

		// 5) Tháng
		if (monthly.length) {
			hint('mgmt-mkt-month-hint', '');
			paint('mgmt-mkt-month-chart', {
				type: 'bar',
				data: {
					labels: monthly.map(function (r) { return r.label || r.month; }),
					datasets: [
						{
							type: 'bar',
							label: 'Lead/MKT',
							data: monthly.map(function (r) { return Number(r.total_leads) || 0; }),
							backgroundColor: 'rgba(52, 211, 153, 0.75)',
							borderRadius: 5,
							order: 2
						},
						{
							type: 'bar',
							label: 'Đặt lịch',
							data: monthly.map(function (r) { return Number(r.appointments) || 0; }),
							backgroundColor: 'rgba(147, 197, 253, 0.85)',
							borderRadius: 5,
							order: 2
						},
						{
							type: 'line',
							label: 'Show',
							data: monthly.map(function (r) { return Number(r.show) || 0; }),
							borderColor: '#d97706',
							backgroundColor: 'transparent',
							tension: 0.3,
							pointRadius: 3,
							borderWidth: 2,
							order: 1
						},
						{
							type: 'line',
							label: 'Đã chốt',
							data: monthly.map(function (r) { return Number(r.closed) || 0; }),
							borderColor: '#dc2626',
							backgroundColor: 'transparent',
							tension: 0.3,
							pointRadius: 3,
							borderWidth: 2,
							order: 1
						}
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
					scales: {
						x: { ticks: { color: tick }, grid: { display: false } },
						y: { beginAtZero: true, ticks: { color: tick, precision: 0 }, grid: { color: grid } }
					}
				}
			});
		} else {
			hint('mgmt-mkt-month-hint', 'Chưa có dữ liệu tháng.');
		}
	}

	function boot() {
		var tries = 0;
		function attempt() {
			tries++;
			if (typeof Chart !== 'undefined') {
				render();
				return;
			}
			if (tries < 40) {
				setTimeout(attempt, 100);
			} else {
				hint('mgmt-mkt-daily-hint', 'Không tải được Chart.js — hard refresh (Cmd+Shift+R).');
			}
		}
		attempt();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
