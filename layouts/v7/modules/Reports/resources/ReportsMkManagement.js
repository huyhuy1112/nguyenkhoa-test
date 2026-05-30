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

	function initDemoCharts() {
		if (typeof Chart === 'undefined') {
			return;
		}
		var colors = chartThemeColors();
		var mktCtx = document.getElementById('mgmt-mkt-chart');
		if (mktCtx) {
			new Chart(mktCtx, {
				type: 'line',
				data: {
					labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'],
					datasets: [{
						label: 'MKT Sale (demo)',
						data: [10, 22, 18, 30, 26, 35],
						borderColor: '#fdbb2c',
						backgroundColor: 'rgba(253, 187, 44, 0.25)',
						tension: 0.3,
						fill: true,
						pointRadius: 3
					}]
				},
				options: {
					responsive: true,
					plugins: { legend: { display: false } },
					scales: {
						x: {
							ticks: { color: colors.tick },
							grid: { color: colors.grid }
						},
						y: {
							ticks: { color: colors.tick },
							grid: { color: colors.grid }
						}
					}
				}
			});
		}

		var kpiCtx = document.getElementById('mgmt-kpi-chart');
		if (kpiCtx) {
			new Chart(kpiCtx, {
				type: 'doughnut',
				data: {
					labels: ['Hoàn thành', 'Đang làm', 'Chưa bắt đầu'],
					datasets: [{
						data: [60, 25, 15],
						backgroundColor: ['#fdbb2c', '#45627d', isDarkTheme() ? '#475569' : '#cbd5e1']
					}]
				},
				options: {
					responsive: true,
					plugins: {
						legend: {
							position: 'bottom',
							labels: { color: colors.legend }
						}
					}
				}
			});
		}
	}

	function waitForProjectTaskModal() {
		if (typeof jQuery === 'undefined') {
			setTimeout(waitForProjectTaskModal, 400);
			return;
		}
		if (typeof ProjectTask_List_Js === 'undefined') {
			setTimeout(waitForProjectTaskModal, 400);
			return;
		}
		var taskHelper = new ProjectTask_List_Js();
		jQuery(document).on('click', '.report-task-link', function(e) {
			e.preventDefault();
			var id = jQuery(this).data('taskid');
			if (!id) return;
			taskHelper.openTaskModal(id, null);
		});
	}

	function bindManagementUi() {
		if (typeof jQuery === 'undefined') {
			return;
		}
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
			var form = jQuery('#mgmt-report-filter-form');
			var fmt = jQuery('input[name="mgmt_export_format"]:checked').val() || 'excel';
			var dateFrom = (form.find('[name="date_from"]').val() || '').trim();
			var dateTo = (form.find('[name="date_to"]').val() || '').trim();
			var ownerId = (form.find('[name="owner_id"]').val() || '').trim();
			var reportType = (form.find('[name="report_type"]').val() || 'all').trim();
			var url = 'index.php?module=Reports&action=ManagementExport&format=' + encodeURIComponent(fmt) +
				'&date_from=' + encodeURIComponent(dateFrom) +
				'&date_to=' + encodeURIComponent(dateTo) +
				'&owner_id=' + encodeURIComponent(ownerId) +
				'&report_type=' + encodeURIComponent(reportType);
			jQuery('#mgmtExportModal').modal('hide');
			window.location.href = url;
		});
	}

	function boot() {
		waitForProjectTaskModal();
		bindManagementUi();
		if (typeof Chart !== 'undefined') {
			initDemoCharts();
		} else {
			var tries = 0;
			var t = setInterval(function() {
				tries++;
				if (typeof Chart !== 'undefined') {
					clearInterval(t);
					initDemoCharts();
				} else if (tries > 25) {
					clearInterval(t);
				}
			}, 200);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
