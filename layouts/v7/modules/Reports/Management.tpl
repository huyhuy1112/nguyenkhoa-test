{strip}
<div class="mk-reports-mgmt-root management-report-wrap">
	<header class="mk-reports-mgmt-header">
		<div class="mk-reports-mgmt-breadcrumb" aria-label="Breadcrumb">
			<span>{vtranslate('LBL_MANAGEMENT', 'Vtiger')}</span>
			<span class="mk-reports-mgmt-breadcrumb__sep">&gt;</span>
			<span>{vtranslate('Reports', 'Reports')}</span>
		</div>
		<h1 class="mk-reports-mgmt-title">Reports overview</h1>
		<p class="mk-reports-mgmt-subtitle">Filter projects and tasks, save configurations, and export summaries for management decisions.</p>
	</header>

	<div class="mk-reports-mgmt-filters mgmt-report-filters">
		<form method="get" class="form-inline" id="mgmt-report-filter-form">
			<input type="hidden" name="module" value="Reports" />
			<input type="hidden" name="view" value="Management" />
			<input type="hidden" name="app" value="MANAGEMENT" />
			<input type="hidden" name="export_format" value="" />
			<input type="hidden" name="save_config" value="0" />
			<input type="hidden" name="update_config" value="0" />
			<input type="hidden" name="delete_config" value="0" />
			<input type="hidden" name="config_id" value="" />
			<input type="hidden" name="selected_config_id" value="{$ACTIVE_CONFIG_ID|escape:'html'}" />
			<input type="hidden" name="do_export" value="0" />
			<input type="hidden" name="export_project_ids" value="" />
			<input type="hidden" name="export_task_ids" value="" />

			<div class="mgmt-top-grid">
				{* A) Bộ lọc báo cáo *}
				<section class="mgmt-card">
					<h3 class="mgmt-card-title"><i class="fa fa-sliders"></i> Bộ lọc báo cáo</h3>
					<p class="mgmt-card-subtitle">Chọn phạm vi thời gian, người phụ trách và loại báo cáo để xem dữ liệu phù hợp.</p>
					<div class="mgmt-form-grid">
						<div class="mgmt-field">
							<label>Từ ngày</label>
							<input type="date" name="date_from" value="{$REPORT_FILTERS.date_from|escape:'html'}" class="form-control input-sm" />
						</div>
						<div class="mgmt-field">
							<label>Đến ngày</label>
							<input type="date" name="date_to" value="{$REPORT_FILTERS.date_to|escape:'html'}" class="form-control input-sm" />
						</div>
						<div class="mgmt-field">
							<label>Người phụ trách</label>
							<select name="owner_id" class="form-control input-sm">
								<option value="">— Tất cả —</option>
								{foreach from=$REPORT_OWNERS key=OID item=ONAME}
									<option value="{$OID|escape:'html'}"{if $REPORT_FILTERS.owner_id eq $OID} selected="selected"{/if}>{$ONAME|escape:'html'}</option>
								{/foreach}
							</select>
						</div>
						<div class="mgmt-field">
							<label>Loại báo cáo</label>
							<select name="report_type" class="form-control input-sm">
								<option value="all"{if $REPORT_FILTERS.report_type eq 'all'} selected="selected"{/if}>Tất cả</option>
								<option value="project"{if $REPORT_FILTERS.report_type eq 'project'} selected="selected"{/if}>Project</option>
								<option value="task"{if $REPORT_FILTERS.report_type eq 'task'} selected="selected"{/if}>Task</option>
							</select>
						</div>
						<div class="mgmt-actions" style="grid-column: 1 / -1;">
							<button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Lọc</button>
							<a href="index.php?module=Reports&view=Management&app=MANAGEMENT" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> Reset</a>
						</div>
					</div>
				</section>

				{* B) Cấu hình đã lưu *}
				<section class="mgmt-card">
					<h3 class="mgmt-card-title"><i class="fa fa-bookmark"></i> Cấu hình đã lưu</h3>
					<p class="mgmt-card-subtitle">Lưu bộ lọc để dùng lại nhanh. Bạn có thể cập nhật hoặc xóa cấu hình hiện tại.</p>
					<div class="mgmt-form-grid" style="grid-template-columns: 1fr 1fr;">
						<div class="mgmt-field" style="grid-column: 1 / -1;">
							<label>Cấu hình đã lưu</label>
							<select class="form-control input-sm" id="mgmt-saved-config-select">
								<option value="">— Chọn cấu hình —</option>
								{foreach from=$REPORT_SAVED_CONFIGS item=CFG}
									<option value="{$CFG.id}"
											data-filters="{$CFG.filters_json|escape:'html'}"
											data-name="{$CFG.name|escape:'html'}"
											{if $ACTIVE_CONFIG_ID eq $CFG.id}selected="selected"{/if}>
										{$CFG.name|escape:'html'}
									</option>
								{/foreach}
							</select>
						</div>
						<div class="mgmt-field" style="grid-column: 1 / -1;">
							<label>Tên cấu hình</label>
							<input type="text" name="save_config_name" value="" class="form-control input-sm" placeholder="Tên cấu hình..." />
						</div>
						<div class="mgmt-actions" style="grid-column: 1 / -1;">
							{* keep JS selectors stable *}
							<button type="button" class="btn btn-default btn-sm js-mgmt-save-config"><i class="fa fa-save"></i> Lưu mới</button>
							<button type="button" class="btn btn-primary btn-sm js-mgmt-update-config"><i class="fa fa-pencil"></i> Cập nhật</button>
							<button type="button" class="btn btn-danger btn-sm js-mgmt-delete-config"><i class="fa fa-trash"></i> Xóa</button>
						</div>
					</div>
				</section>

				{* C) Xuất báo cáo *}
				<section class="mgmt-card">
					<div class="mgmt-export-right">
						<div>
							<h3 class="mgmt-card-title"><i class="fa fa-download"></i> Xuất báo cáo</h3>
							<p class="mgmt-card-subtitle">Xuất dữ liệu theo bộ lọc hiện tại.</p>
						</div>
						<div class="mgmt-actions" style="justify-content:flex-end;">
							<button type="button" class="btn btn-success btn-sm js-mgmt-open-export"><i class="fa fa-download"></i> Export…</button>
						</div>
					</div>
				</section>
			</div>
		</form>
	</div>

	<div class="mk-reports-mgmt-body mgmt-report-body">
		<div class="mk-reports-mgmt-tables">
			<div class="mk-reports-mgmt-panel">
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
			<div class="mk-reports-mgmt-panel">
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

		<div class="mk-reports-mgmt-charts mgmt-report-row-second">
			<div class="mk-reports-mgmt-panel">
				<div class="mgmt-report-section-header">MKT SALE</div>
				<div class="mgmt-report-empty">
					<canvas id="mgmt-mkt-chart" height="160"></canvas>
					<div class="text-muted small">Ví dụ biểu đồ Marketing (demo data). Khi có dữ liệu thật sẽ nối nguồn Sales/Marketing.</div>
				</div>
			</div>
			<div class="mk-reports-mgmt-panel">
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
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Reports/resources/ReportsMkManagement.js')}&mk_v=20260529_mgmt_dark"></script>

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
