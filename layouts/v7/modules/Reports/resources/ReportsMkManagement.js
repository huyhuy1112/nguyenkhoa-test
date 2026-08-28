/* Reports Management — filters, export, demo charts */
(function() {
	'use strict';

	function isDarkTheme() {
		return document.documentElement.getAttribute('data-theme') === 'dark';
	}

	function chartThemeColors() {
		if (isDarkTheme()) {
			return {
				tick: '#cbd5e1',
				grid: 'rgba(255, 255, 255, 0.08)',
				legend: '#cbd5e1'
			};
		}
		return {
			tick: '#64748b',
			grid: 'rgba(15, 23, 42, 0.08)',
			legend: '#475569'
		};
	}

	function parseJsonEl(id) {
		try {
			var el = document.getElementById(id);
			if (!el || !el.textContent) return null;
			var raw = el.textContent.replace(/^\s+|\s+$/g, '');
			if (!raw || raw === 'null') return null;
			return JSON.parse(raw);
		} catch (e) {
			return null;
		}
	}

	function setHint(id, msg) {
		var el = document.getElementById(id);
		if (el) el.textContent = msg || '';
	}

	function ensureChartJs(cb) {
		if (typeof window.Chart !== 'undefined') {
			cb();
			return;
		}
		var existing = document.querySelector('script[data-mk-chartjs="1"]');
		if (existing) {
			var tries = 0;
			var t = setInterval(function () {
				tries++;
				if (typeof window.Chart !== 'undefined') {
					clearInterval(t);
					cb();
				} else if (tries > 80) {
					clearInterval(t);
					cb();
				}
			}, 100);
			return;
		}
		var s = document.createElement('script');
		s.src = 'layouts/v7/modules/Reports/resources/vendor/chart.umd.min.js';
		s.async = true;
		s.setAttribute('data-mk-chartjs', '1');
		s.onload = function () { cb(); };
		s.onerror = function () {
			var s2 = document.createElement('script');
			s2.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
			s2.async = true;
			s2.setAttribute('data-mk-chartjs', '1');
			s2.onload = function () { cb(); };
			s2.onerror = function () { cb(); };
			document.head.appendChild(s2);
		};
		document.head.appendChild(s);
	}

	function initDemoCharts() {
		if (typeof window.Chart === 'undefined') {
			setHint('mgmt-mkt-combined-hint', 'Không tải được thư viện biểu đồ.');
			setHint('mgmt-mkt-class-hint', 'Không tải được thư viện biểu đồ.');
			setHint('mgmt-mkt-month-hint', 'Không tải được thư viện biểu đồ.');
			return;
		}
		if (window.__mkMktChartsReady) {
			return;
		}
		window.__mkMktChartsReady = true;

		var daily = parseJsonEl('mgmt-mkt-chart-data') || [];
		var monthly = parseJsonEl('mgmt-mkt-monthly-data') || [];
		var classDays = parseJsonEl('mgmt-mkt-class-data') || [];
		var tick = '#64748b';
		var grid = 'rgba(15, 23, 42, 0.06)';

		// --- Bảng 1: 1 biểu đồ tổng hợp ---
		var combinedCtx = document.getElementById('mgmt-mkt-combined-chart');
		if (combinedCtx) {
			var rows = (daily || []).filter(function (r) { return Number(r.total_leads) > 0; });
			if (!rows.length) {
				rows = (daily || []).slice(0, 31);
			}
			if (!rows.length) {
				setHint('mgmt-mkt-combined-hint', 'Chưa có dữ liệu ngày để vẽ biểu đồ.');
			} else {
				setHint('mgmt-mkt-combined-hint', '');
				new Chart(combinedCtx, {
					type: 'bar',
					data: {
						labels: rows.map(function (r) { return r.label || r.date; }),
						datasets: [
							{
								type: 'line',
								label: 'Tổng Data MKT',
								data: rows.map(function (r) { return Number(r.total_leads) || 0; }),
								borderColor: '#0B6E4F',
								backgroundColor: 'rgba(8, 160, 69, 0.12)',
								fill: true,
								tension: 0.3,
								pointRadius: 3,
								borderWidth: 2,
								yAxisID: 'y',
								order: 1
							},
							{
								type: 'bar',
								label: 'N.Khoa',
								data: rows.map(function (r) { return Number(r.n_khoa) || 0; }),
								backgroundColor: 'rgba(52, 211, 153, 0.75)',
								borderRadius: 4,
								yAxisID: 'y',
								order: 2
							},
							{
								type: 'bar',
								label: 'TikTok',
								data: rows.map(function (r) { return Number(r.tiktok) || 0; }),
								backgroundColor: 'rgba(249, 168, 212, 0.85)',
								borderRadius: 4,
								yAxisID: 'y',
								order: 2
							},
							{
								type: 'line',
								label: 'KV1',
								data: rows.map(function (r) { return Number(r.kv1) || 0; }),
								borderColor: '#16a34a',
								backgroundColor: 'transparent',
								tension: 0.3,
								pointRadius: 2,
								borderWidth: 2,
								yAxisID: 'y',
								order: 1
							},
							{
								type: 'line',
								label: 'KV2',
								data: rows.map(function (r) { return Number(r.kv2) || 0; }),
								borderColor: '#e11d48',
								backgroundColor: 'transparent',
								tension: 0.3,
								pointRadius: 2,
								borderWidth: 2,
								yAxisID: 'y',
								order: 1
							},
							{
								type: 'line',
								label: 'KV3',
								data: rows.map(function (r) { return Number(r.kv3) || 0; }),
								borderColor: '#2563eb',
								backgroundColor: 'transparent',
								tension: 0.3,
								pointRadius: 2,
								borderWidth: 2,
								yAxisID: 'y',
								order: 1
							}
						]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						interaction: { mode: 'index', intersect: false },
						plugins: {
							legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }
						},
						scales: {
							x: {
								ticks: { color: tick, maxRotation: 40, maxTicksLimit: 16 },
								grid: { display: false }
							},
							y: {
								beginAtZero: true,
								ticks: { color: tick, precision: 0 },
								grid: { color: grid }
							}
						}
					}
				});
			}
		}

		// --- Bảng 2 ---
		var classCtx = document.getElementById('mgmt-mkt-class-chart');
		if (classCtx) {
			var classRows = (classDays || []).filter(function (r) { return !r.is_summary; });
			if (!classRows.length) {
				setHint('mgmt-mkt-class-hint', 'Chưa có ngày học / lịch hẹn — biểu đồ sẽ hiện khi có dữ liệu bảng 2.');
				// still draw empty axes so khung không trắng hoàn toàn
				new Chart(classCtx, {
					type: 'bar',
					data: {
						labels: ['—'],
						datasets: [
							{ label: 'Hẹn', data: [0], backgroundColor: 'rgba(147, 197, 253, 0.8)', borderRadius: 6 },
							{ label: 'Show', data: [0], backgroundColor: 'rgba(253, 230, 138, 0.9)', borderRadius: 6 },
							{ label: 'Chốt', data: [0], backgroundColor: 'rgba(252, 165, 165, 0.9)', borderRadius: 6 }
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
			} else {
				setHint('mgmt-mkt-class-hint', '');
				new Chart(classCtx, {
					type: 'bar',
					data: {
						labels: classRows.map(function (r) { return r.label; }),
						datasets: [
							{
								label: 'Hẹn',
								data: classRows.map(function (r) { return Number(r.appointments) || 0; }),
								backgroundColor: 'rgba(147, 197, 253, 0.85)',
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
							y: { beginAtZero: true, ticks: { color: tick, precision: 0 }, grid: { color: grid } }
						}
					}
				});
			}
		}

		// --- Tổng kết tháng ---
		var monthCtx = document.getElementById('mgmt-mkt-month-chart');
		if (monthCtx) {
			if (!monthly.length) {
				setHint('mgmt-mkt-month-hint', 'Chưa có dữ liệu theo tháng.');
			} else {
				setHint('mgmt-mkt-month-hint', '');
				new Chart(monthCtx, {
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
			}
		}
	}

	function waitForProjectTaskModal() {
		return;
	}

	function bindManagementUi() {
		if (typeof jQuery === 'undefined') {
			return;
		}
		if (window.__mkMgmtUiBound) {
			return;
		}
		window.__mkMgmtUiBound = true;
		jQuery(document).on('change', '.js-mgmt-select-project-all', function() {
			var checked = jQuery(this).is(':checked');
			jQuery('.js-mgmt-select-project').prop('checked', checked);
		});
		jQuery(document).on('change', '.js-mgmt-select-task-all', function() {
			var checked = jQuery(this).is(':checked');
			jQuery('.js-mgmt-select-task').prop('checked', checked);
		});
		jQuery(document).on('click', '.js-mgmt-open-export', function(e) {
			e.preventDefault();
			jQuery('#mgmtExportModal').modal('show');
		});
		jQuery(document).on('click', '.js-mgmt-save-config', function(e) {
			e.preventDefault();
			var form = jQuery('#mgmt-report-filter-form');
			var nameInput = form.find('input[name="save_config_name"]');
			if (!jQuery.trim(nameInput.val())) {
				alert('Vui lòng nhập tên cấu hình báo cáo.');
				return;
			}
			form.find('input[name="save_config"]').val('1');
			form.find('input[name="update_config"]').val('0');
			form.find('input[name="delete_config"]').val('0');
			form.find('input[name="config_id"]').val('');
			form.find('input[name="selected_config_id"]').val('');
			form.submit();
		});
		jQuery(document).on('click', '.js-mgmt-update-config', function(e) {
			e.preventDefault();
			var form = jQuery('#mgmt-report-filter-form');
			var selectedId = (form.find('input[name="selected_config_id"]').val() || '').trim();
			var nameInput = form.find('input[name="save_config_name"]');
			if (!selectedId) {
				alert('Vui lòng chọn một cấu hình đã lưu để cập nhật.');
				return;
			}
			if (!jQuery.trim(nameInput.val())) {
				alert('Vui lòng nhập tên cấu hình báo cáo.');
				return;
			}
			form.find('input[name="save_config"]').val('0');
			form.find('input[name="update_config"]').val('1');
			form.find('input[name="delete_config"]').val('0');
			form.find('input[name="config_id"]').val(selectedId);
			form.submit();
		});
		jQuery(document).on('click', '.js-mgmt-delete-config', function(e) {
			e.preventDefault();
			var form = jQuery('#mgmt-report-filter-form');
			var selectedId = (form.find('input[name="selected_config_id"]').val() || '').trim();
			if (!selectedId) {
				alert('Vui lòng chọn một cấu hình đã lưu để xóa.');
				return;
			}
			if (!confirm('Bạn có chắc muốn xóa cấu hình này?')) return;
			form.find('input[name="save_config"]').val('0');
			form.find('input[name="update_config"]').val('0');
			form.find('input[name="delete_config"]').val('1');
			form.find('input[name="config_id"]').val(selectedId);
			form.submit();
		});
		jQuery('#mgmt-saved-config-select').on('change', function() {
			var opt = jQuery(this).find('option:selected');
			var json = opt.data('filters') || '';
			var cfgId = opt.val() || '';
			var cfgName = opt.data('name') || opt.text() || '';
			var form = jQuery('#mgmt-report-filter-form');
			form.find('input[name="selected_config_id"]').val(cfgId);
			form.find('input[name="config_id"]').val(cfgId);
			form.find('input[name="save_config_name"]').val(cfgName);
			if (!json) return;
			try {
				var f = JSON.parse(json);
			} catch (err) {
				return;
			}
			if (f.date_from !== undefined) form.find('[name="date_from"]').val(f.date_from || '');
			if (f.date_to !== undefined) form.find('[name="date_to"]').val(f.date_to || '');
			if (f.owner_id !== undefined) form.find('[name="owner_id"]').val(f.owner_id || '');
			if (f.report_type !== undefined) form.find('[name="report_type"]').val(f.report_type || 'all');
			form.find('input[name="export_format"]').val('');
			form.find('input[name="save_config"]').val('0');
			form.find('input[name="update_config"]').val('0');
			form.find('input[name="delete_config"]').val('0');
			form.find('input[name="do_export"]').val('0');
			form.find('input[name="export_project_ids"]').val('');
			form.find('input[name="export_task_ids"]').val('');
			form.submit();
		});
		jQuery(document).on('click', '.js-mgmt-export-confirm', function(e) {
			e.preventDefault();
			if (window.__mkMgmtExportBusy) {
				return;
			}
			window.__mkMgmtExportBusy = true;
			var form = jQuery('#mgmt-report-filter-form');
			var fmt = jQuery('input[name="mgmt_export_format"]:checked').val() || 'excel';
			var dateFrom = (form.find('[name="date_from"]').val() || '').trim();
			var dateTo = (form.find('[name="date_to"]').val() || '').trim();
			var ownerId = (form.find('[name="owner_id"]').val() || '').trim();
			var reportType = (form.find('[name="report_type"]').val() || 'all').trim();
			var projectIds = [];
			var taskIds = [];
			jQuery('.js-mgmt-select-project:checked').each(function() {
				projectIds.push(jQuery(this).val());
			});
			jQuery('.js-mgmt-select-task:checked').each(function() {
				taskIds.push(jQuery(this).val());
			});
			var params = {
				module: 'Reports',
				action: 'ManagementExport',
				format: fmt,
				date_from: dateFrom,
				date_to: dateTo,
				owner_id: ownerId,
				report_type: reportType,
				app: 'MANAGEMENT'
			};
			if (projectIds.length) {
				params.export_project_ids = projectIds.join(',');
			}
			if (taskIds.length) {
				params.export_task_ids = taskIds.join(',');
			}
			var url = 'index.php?' + jQuery.param(params);
			var extMap = { excel: 'xlsx', csv: 'csv', pdf: 'pdf' };
			var filename = 'management_report.' + (extMap[fmt] || 'xlsx');
			jQuery('#mgmtExportModal').modal('hide');
			var resetExportBusy = function() {
				window.__mkMgmtExportBusy = false;
			};
			var downloadViaFetch = function() {
				return fetch(url, { credentials: 'same-origin' })
					.then(function(resp) {
						if (!resp.ok) {
							throw new Error('export_failed');
						}
						var contentType = (resp.headers.get('Content-Type') || '').toLowerCase();
						if (contentType.indexOf('text/html') !== -1 || contentType.indexOf('application/json') !== -1) {
							throw new Error('export_failed');
						}
						var disposition = resp.headers.get('Content-Disposition') || '';
						var match = disposition.match(/filename=\"?([^\";]+)\"?/i);
						if (match && match[1]) {
							filename = match[1];
						}
						return resp.blob();
					})
					.then(function(blob) {
						if (!blob || !blob.size) {
							throw new Error('export_failed');
						}
						var objectUrl = window.URL.createObjectURL(blob);
						var link = document.createElement('a');
						link.href = objectUrl;
						link.download = filename;
						document.body.appendChild(link);
						link.click();
						link.remove();
						window.URL.revokeObjectURL(objectUrl);
					});
			};
			if (typeof fetch === 'function') {
				downloadViaFetch()
					.catch(function() {
						window.location.href = url;
					})
					.finally(resetExportBusy);
			} else {
				window.location.href = url;
				setTimeout(resetExportBusy, 1500);
			}
		});
	}

	function boot() {
		waitForProjectTaskModal();
		bindManagementUi();
		// Biểu đồ do ReportsMkCharts.js (cuối Management.tpl) phụ trách — tránh double-init.
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
