{strip}
<div class="container-fluid management-report-wrap">
	<div class="mgmt-report-header">
		<h2 class="mgmt-report-title">Reports overview</h2>
	</div>

	<div class="mgmt-report-filters">
		<form method="get" class="form-inline" id="mgmt-report-filter-form">
			<input type="hidden" name="module" value="Reports" />
			<input type="hidden" name="view" value="Management" />
			<input type="hidden" name="app" value="MANAGEMENT" />
			<input type="hidden" name="export_format" value="" />
			<input type="hidden" name="save_config" value="0" />
			<input type="hidden" name="do_export" value="0" />
			<input type="hidden" name="export_project_ids" value="" />
			<input type="hidden" name="export_task_ids" value="" />
			<input type="hidden" name="export_project_ids" value="" />
			<input type="hidden" name="export_task_ids" value="" />

			<div class="filter-group">
				<label>Từ ngày</label>
				<input type="date" name="date_from" value="{$REPORT_FILTERS.date_from|escape:'html'}" class="form-control input-sm" />
			</div>
			<div class="filter-group">
				<label>Đến ngày</label>
				<input type="date" name="date_to" value="{$REPORT_FILTERS.date_to|escape:'html'}" class="form-control input-sm" />
			</div>

			<div class="filter-group">
				<label>Người phụ trách</label>
				<select name="owner_id" class="form-control input-sm">
					<option value="">— Tất cả —</option>
					{foreach from=$REPORT_OWNERS key=OID item=ONAME}
						<option value="{$OID|escape:'html'}"{if $REPORT_FILTERS.owner_id eq $OID} selected="selected"{/if}>{$ONAME|escape:'html'}</option>
					{/foreach}
				</select>
			</div>

			<div class="filter-group">
				<label>Cấu hình đã lưu</label>
				<select class="form-control input-sm" id="mgmt-saved-config-select">
					<option value="">— Chọn cấu hình —</option>
					{foreach from=$REPORT_SAVED_CONFIGS item=CFG}
						<option value="{$CFG.id}" data-filters="{$CFG.filters_json|escape:'html'}">{$CFG.name|escape:'html'}</option>
					{/foreach}
				</select>
			</div>

			<div class="filter-group">
				<label>Loại báo cáo</label>
				<select name="report_type" class="form-control input-sm">
					<option value="all"{if $REPORT_FILTERS.report_type eq 'all'} selected="selected"{/if}>Tất cả</option>
					<option value="project"{if $REPORT_FILTERS.report_type eq 'project'} selected="selected"{/if}>Project</option>
					<option value="task"{if $REPORT_FILTERS.report_type eq 'task'} selected="selected"{/if}>Task</option>
				</select>
			</div>

			<div class="filter-group">
				<label>Lưu cấu hình mới</label>
				<input type="text" name="save_config_name" value="" class="form-control input-sm" placeholder="Tên cấu hình..." />
			</div>

			<div class="filter-group filter-actions">
				<button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Lọc</button>
				<a href="index.php?module=Reports&view=Management&app=MANAGEMENT" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> Reset</a>
				<button type="button" class="btn btn-default btn-sm js-mgmt-save-config"><i class="fa fa-save"></i> Lưu cấu hình</button>
				<button type="button" class="btn btn-success btn-sm js-mgmt-open-export"><i class="fa fa-download"></i> Export…</button>
			</div>
		</form>
	</div>

	<div class="mgmt-report-body">
		<div class="row">
			<div class="col-sm-6">
				<div class="mgmt-report-section-header">Project Report</div>
				<div class="table-responsive">
					<table class="table table-bordered table-striped mgmt-report-table">
						<thead>
							<tr>
								<th style="width: 3%;" class="mgmt-select-col"><input type="checkbox" class="js-mgmt-select-project-all" /></th>
								<th style="width: 25%;">Project</th>
								<th>Start</th>
								<th>End</th>
								<th>Assigned To</th>
								<th>Tasks</th>
								<th>Done</th>
								<th>In Progress</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							{if $REPORT_PROJECT_ROWS|@count gt 0}
								{foreach from=$REPORT_PROJECT_ROWS item=row}
									<tr>
										<td class="mgmt-select-cell"><input type="checkbox" class="js-mgmt-select-project" value="{$row.id}" /></td>
										<td><a href="{$row.url}" target="_blank" class="text-primary">{$row.title|escape:'html'}</a></td>
										<td>{$row.start|escape:'html'}</td>
										<td>{$row.end|escape:'html'}</td>
										<td>{$row.owner nofilter}</td>
										<td>{$row.task_count}</td>
										<td>{$row.task_done}</td>
										<td>{$row.task_in_progress}</td>
										<td>{$row.status|escape:'html'}</td>
									</tr>
								{/foreach}
							{else}
								<tr><td colspan="9" class="text-muted text-center">Chưa có dữ liệu Project phù hợp bộ lọc.</td></tr>
							{/if}
						</tbody>
					</table>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="mgmt-report-section-header">Task Report</div>
				<div class="table-responsive">
					<table class="table table-bordered table-striped mgmt-report-table">
						<thead>
							<tr>
								<th style="width: 3%;" class="mgmt-select-col"><input type="checkbox" class="js-mgmt-select-task-all" /></th>
								<th style="width: 42%;">Task</th>
								<th>Due date</th>
								<th>Assigned To</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							{if $REPORT_TASK_ROWS|@count gt 0}
								{foreach from=$REPORT_TASK_ROWS item=row}
									<tr>
										<td class="mgmt-select-cell"><input type="checkbox" class="js-mgmt-select-task" value="{$row.id}" /></td>
										<td><a href="javascript:void(0)" class="text-primary report-task-link" data-taskid="{$row.id}">{$row.title|escape:'html'}</a></td>
										<td>{$row.due|escape:'html'}</td>
										<td>{$row.owner nofilter}</td>
										<td>{$row.status|escape:'html'}</td>
									</tr>
								{/foreach}
							{else}
								<tr><td colspan="5" class="text-muted text-center">Chưa có dữ liệu Task phù hợp bộ lọc.</td></tr>
							{/if}
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="row mgmt-report-row-second">
			<div class="col-sm-6">
				<div class="mgmt-report-section-header">MKT SALE</div>
				<div class="mgmt-report-empty">
					<canvas id="mgmt-mkt-chart" height="160"></canvas>
					<div class="text-muted small">Ví dụ biểu đồ Marketing (demo data). Khi có dữ liệu thật sẽ nối nguồn Sales/Marketing.</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="mgmt-report-section-header">KPI Report</div>
				<div class="mgmt-report-empty">
					<canvas id="mgmt-kpi-chart" height="160"></canvas>
					<div class="text-muted small">Ví dụ biểu đồ KPI (demo data). Sau này sẽ build theo bộ KPI chuẩn.</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
(function waitForProjectTaskModal() {
	if (typeof jQuery === 'undefined') {
		setTimeout(waitForProjectTaskModal, 400);
		return;
	}
	if (typeof ProjectTask_List_Js === 'undefined') {
		// Đợi JS của ProjectTask (List.js) load xong rồi mới bind click
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
})();

// Export handler + lưu cấu hình
jQuery(function() {
	// Tick tất cả / bỏ chọn tất cả cho Project
	jQuery(document).on('change', '.js-mgmt-select-project-all', function() {
		var checked = jQuery(this).is(':checked');
		jQuery('.js-mgmt-select-project').prop('checked', checked);
	});
	// Tick tất cả / bỏ chọn tất cả cho Task
	jQuery(document).on('change', '.js-mgmt-select-task-all', function() {
		var checked = jQuery(this).is(':checked');
		jQuery('.js-mgmt-select-task').prop('checked', checked);
	});

	// Mở modal export (không tự tick ô chọn)
	jQuery(document).on('click', '.js-mgmt-open-export', function(e) {
		e.preventDefault();
		jQuery('#mgmtExportModal').modal('show');
	});

	// Lưu cấu hình
	jQuery(document).on('click', '.js-mgmt-save-config', function(e) {
		e.preventDefault();
		var form = jQuery('#mgmt-report-filter-form');
		var nameInput = form.find('input[name="save_config_name"]');
		if (!jQuery.trim(nameInput.val())) {
			alert('Vui lòng nhập tên cấu hình báo cáo.');
			return;
		}
		form.find('input[name="save_config"]').val('1');
		form.submit();
	});

	// Chọn cấu hình đã lưu
	jQuery('#mgmt-saved-config-select').on('change', function() {
		var opt = jQuery(this).find('option:selected');
		var json = opt.data('filters') || '';
		if (!json) return;
		try {
			var f = JSON.parse(json);
		} catch (e) {
			return;
		}
		var form = jQuery('#mgmt-report-filter-form');
		if (f.date_from !== undefined) form.find('[name="date_from"]').val(f.date_from || '');
		if (f.date_to !== undefined) form.find('[name="date_to"]').val(f.date_to || '');
		if (f.owner_id !== undefined) form.find('[name="owner_id"]').val(f.owner_id || '');
		if (f.report_type !== undefined) form.find('[name="report_type"]').val(f.report_type || 'all');
		form.find('input[name="export_format"]').val('');
		form.find('input[name="save_config"]').val('0');
		form.find('input[name="do_export"]').val('0');
		form.find('input[name="export_project_ids"]').val('');
		form.find('input[name="export_task_ids"]').val('');
		form.submit();
	});

	// Xác nhận Export: dùng filter hiện tại + Loại báo cáo (report_type) ở ngoài form
	jQuery(document).on('click', '.js-mgmt-export-confirm', function(e) {
		e.preventDefault();
		var form = jQuery('#mgmt-report-filter-form');
		var fmt = jQuery('input[name="mgmt_export_format"]:checked').val() || 'excel';
		var dateFrom = (form.find('[name="date_from"]').val() || '').trim();
		var dateTo   = (form.find('[name="date_to"]').val() || '').trim();
		var ownerId  = (form.find('[name="owner_id"]').val() || '').trim();
		var reportType = (form.find('[name="report_type"]').val() || 'all').trim();
		var url = 'index.php?module=Reports&action=ManagementExport&format=' + encodeURIComponent(fmt) +
			'&date_from=' + encodeURIComponent(dateFrom) +
			'&date_to=' + encodeURIComponent(dateTo) +
			'&owner_id=' + encodeURIComponent(ownerId) +
			'&report_type=' + encodeURIComponent(reportType);
		jQuery('#mgmtExportModal').modal('hide');
		window.location.href = url;
	});

	// Charts demo cho MKT & KPI (demo data)
	if (typeof Chart !== 'undefined') {
		var mktCtx = document.getElementById('mgmt-mkt-chart');
		if (mktCtx) {
			new Chart(mktCtx, {
				type: 'line',
				data: {
					labels: ['T1','T2','T3','T4','T5','T6'],
					datasets: [{
						label: 'MKT Sale (demo)',
						data: [10, 22, 18, 30, 26, 35],
						borderColor: 'rgba(59,130,246,1)',
						backgroundColor: 'rgba(59,130,246,0.25)',
						tension: 0.3,
						fill: true,
						pointRadius: 3
					}]
				},
				options: {
					responsive: true,
					plugins: { legend: { display: false } }
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
						backgroundColor: [
							'rgba(34,197,94,0.9)',
							'rgba(59,130,246,0.9)',
							'rgba(148,163,184,0.9)'
						]
					}]
				},
				options: {
					responsive: true,
					plugins: { legend: { position: 'bottom' } }
				}
			});
		}
	}
});
</script>

{* Modal Export: hỏi định dạng + loại báo cáo *}
<div class="modal fade" id="mgmtExportModal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Export báo cáo</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label>Định dạng file</label><br/>
					<label class="radio-inline">
						<input type="radio" name="mgmt_export_format" value="excel" checked="checked" /> Excel
					</label>
					<label class="radio-inline">
						<input type="radio" name="mgmt_export_format" value="csv" /> CSV
					</label>
					<label class="radio-inline">
						<input type="radio" name="mgmt_export_format" value="pdf" /> PDF
					</label>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
				<button type="button" class="btn btn-success js-mgmt-export-confirm"><i class="fa fa-download"></i> Export</button>
			</div>
		</div>
	</div>
</div>
{/strip}
