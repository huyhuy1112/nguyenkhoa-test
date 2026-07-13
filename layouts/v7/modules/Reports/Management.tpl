{strip}
<div class="mk-reports-mgmt-root management-report-wrap">
	<header class="mk-reports-mgmt-header">
		<div class="mk-reports-mgmt-breadcrumb" aria-label="Breadcrumb">
			<span>{vtranslate('LBL_MANAGEMENT', 'Vtiger')}</span>
			<span class="mk-reports-mgmt-breadcrumb__sep">&gt;</span>
			<span>{vtranslate('Reports', 'Reports')}</span>
		</div>
		<h1 class="mk-reports-mgmt-title">MKT SALE — Báo cáo vận hành</h1>
	</header>

	<div class="mk-reports-mgmt-filters mgmt-report-filters">
		<form method="get" class="form-inline" id="mgmt-report-filter-form">
			<input type="hidden" name="module" value="Reports" />
			<input type="hidden" name="view" value="Management" />
			<input type="hidden" name="app" value="MANAGEMENT" />
			<input type="hidden" name="report_type" value="mkt" />
			<input type="hidden" name="export_format" value="" />
			<input type="hidden" name="do_export" value="0" />
			<input type="hidden" name="export_project_ids" value="" />
			<input type="hidden" name="export_task_ids" value="" />

			<section class="mk-mkt-filterbar" aria-label="Bộ lọc báo cáo">
				<div class="mk-mkt-filterbar__glow" aria-hidden="true"></div>
				<div class="mk-mkt-filterbar__inner">
					<div class="mk-mkt-filterbar__brand">
						<span class="mk-mkt-filterbar__mark" aria-hidden="true"></span>
						<div class="mk-mkt-filterbar__brand-text">
							<span class="mk-mkt-filterbar__kicker">Bộ lọc</span>
							<strong class="mk-mkt-filterbar__heading">Khoảng báo cáo</strong>
						</div>
					</div>
					<div class="mk-mkt-filterbar__fields">
						<div class="mk-mkt-field">
							<label for="mgmt-date-from">Từ ngày</label>
							<input id="mgmt-date-from" type="date" name="date_from" value="{$REPORT_FILTERS.date_from|escape:'html'}" class="mk-mkt-input" />
						</div>
						<div class="mk-mkt-field mk-mkt-field--sep" aria-hidden="true">
							<span class="mk-mkt-filterbar__dash"></span>
						</div>
						<div class="mk-mkt-field">
							<label for="mgmt-date-to">Đến ngày</label>
							<input id="mgmt-date-to" type="date" name="date_to" value="{$REPORT_FILTERS.date_to|escape:'html'}" class="mk-mkt-input" />
						</div>
						<div class="mk-mkt-field mk-mkt-field--owner">
							<label for="mgmt-owner-id">Người phụ trách</label>
							<select id="mgmt-owner-id" name="owner_id" class="mk-mkt-input">
								<option value="">— Tất cả —</option>
								{foreach from=$REPORT_OWNERS key=OID item=ONAME}
									<option value="{$OID|escape:'html'}"{if $REPORT_FILTERS.owner_id eq $OID} selected="selected"{/if}>{$ONAME|escape:'html'}</option>
								{/foreach}
							</select>
						</div>
					</div>
					<div class="mk-mkt-filterbar__actions">
						<button type="submit" class="mk-mkt-btn mk-mkt-btn--primary">
							<i class="fa fa-check"></i><span>Áp dụng</span>
						</button>
						<a href="index.php?module=Reports&view=Management&app=MANAGEMENT&report_type=mkt" class="mk-mkt-btn mk-mkt-btn--ghost">
							<i class="fa fa-undo"></i><span>Reset</span>
						</a>
						<button type="button" class="mk-mkt-btn mk-mkt-btn--export js-mgmt-open-export">
							<i class="fa fa-download"></i><span>Export</span>
						</button>
					</div>
				</div>
			</section>
		</form>
	</div>

	{assign var=MKT value=$REPORT_MKT_SALE}
	{assign var=MKT_TOT value=$MKT.daily_total}
	{assign var=MKT_SUM value=$MKT.summary}
	{assign var=STATUS_MX value=$MKT.status_matrix}

	<div class="mk-reports-mgmt-body mgmt-report-body">
		<section class="mk-mkt-enterprise" aria-label="MKT SALE báo cáo">

			{* ===== KPI strip ===== *}
			<div class="mk-mkt-kpi-strip" aria-label="Chỉ số nhanh">
				<article class="mk-mkt-kpi-card mk-mkt-kpi-card--lead">
					<span class="mk-mkt-kpi-card__label">Tổng Data MKT</span>
					<strong class="mk-mkt-kpi-card__value">{$MKT_TOT.total_leads|default:0}</strong>
					<span class="mk-mkt-kpi-card__hint">Lead trong khoảng lọc</span>
				</article>
				<article class="mk-mkt-kpi-card mk-mkt-kpi-card--nk">
					<span class="mk-mkt-kpi-card__label">N.Khoa</span>
					<strong class="mk-mkt-kpi-card__value">{$MKT_TOT.n_khoa|default:0}</strong>
					<span class="mk-mkt-kpi-card__hint">Facebook / Web / Zalo…</span>
				</article>
				<article class="mk-mkt-kpi-card mk-mkt-kpi-card--tt">
					<span class="mk-mkt-kpi-card__label">TikTok</span>
					<strong class="mk-mkt-kpi-card__value">{$MKT_TOT.tiktok|default:0}</strong>
					<span class="mk-mkt-kpi-card__hint">Nguồn TikTok</span>
				</article>
				<article class="mk-mkt-kpi-card mk-mkt-kpi-card--kv">
					<span class="mk-mkt-kpi-card__label">KV1 · KV2 · KV3</span>
					<strong class="mk-mkt-kpi-card__value">{$MKT_TOT.kv1|default:0} · {$MKT_TOT.kv2|default:0} · {$MKT_TOT.kv3|default:0}</strong>
					<span class="mk-mkt-kpi-card__hint">Theo tag khu vực</span>
				</article>
				<article class="mk-mkt-kpi-card mk-mkt-kpi-card--conv">
					<span class="mk-mkt-kpi-card__label">% Chốt / hẹn</span>
					<strong class="mk-mkt-kpi-card__value">{$MKT_SUM.pct_close_total|default:0}%</strong>
					<span class="mk-mkt-kpi-card__hint">Từ funnel ngày học</span>
				</article>
			</div>

			{* ===== BẢNG 1 ===== *}
			<div class="mk-reports-mgmt-panel mk-mkt-panel mk-mkt-panel--hero">
				<div class="mk-mkt-panel__head">
					<div>
						<div class="mk-mkt-badge">Bảng 1</div>
						<h2 class="mk-mkt-panel__title">Theo dõi Data Marketing theo ngày</h2>
					</div>
				</div>

				<div class="table-responsive mk-mkt-scroll mk-mkt-scroll--daily">
					<table class="table table-bordered mgmt-report-table mk-mkt-sheet mk-mkt-sheet--daily">
						<thead>
							<tr>
								<th class="mk-mkt-sticky-col">Ngày</th>
								<th>Tổng Data Marketing</th>
								<th>N.Khoa</th>
								<th>TikTok</th>
								<th class="mk-mkt-th--kv1">KV1</th>
								<th class="mk-mkt-th--kv2">KV2</th>
								<th class="mk-mkt-th--kv3">KV3</th>
								<th>K. rõ KV</th>
								<th class="mk-mkt-th--wrap">Đang tư vấn / Hẹn lịch / KNM / Gọi lại</th>
								<th class="mk-mkt-th--wrap">Liên hệ mãi không được</th>
								<th class="mk-mkt-th--wrap">Không học / Thuê bao / Trùng số</th>
								<th>Lớp online</th>
							</tr>
						</thead>
						<tbody>
							{if $MKT.daily|@count gt 0}
								{foreach from=$MKT.daily item=row}
									<tr class="{if $row.total_leads gt 0}mk-mkt-row--hot{else}mk-mkt-row--empty{/if}">
										<td class="mk-mkt-date mk-mkt-sticky-col">{$row.label|escape:'html'}</td>
										<td class="mk-mkt-cell-total">{if $row.total_leads gt 0}<span class="mk-mkt-pill">{$row.total_leads}</span>{else}0{/if}</td>
										<td>{$row.n_khoa}</td>
										<td>{$row.tiktok}</td>
										<td>{$row.kv1}</td>
										<td>{$row.kv2}</td>
										<td>{$row.kv3}</td>
										<td>{$row.region_unknown}</td>
										<td>{$row.consulting}</td>
										<td>{$row.unreachable}</td>
										<td>{$row.invalid}</td>
										<td>{$row.online_class}</td>
									</tr>
								{/foreach}
								{if $MKT_TOT}
								<tr class="mk-mkt-total">
									<td class="mk-mkt-sticky-col"><strong>{$MKT_TOT.label|escape:'html'}</strong></td>
									<td><strong>{$MKT_TOT.total_leads|default:0}</strong></td>
									<td><strong>{$MKT_TOT.n_khoa|default:0}</strong></td>
									<td><strong>{$MKT_TOT.tiktok|default:0}</strong></td>
									<td><strong>{$MKT_TOT.kv1|default:0}</strong></td>
									<td><strong>{$MKT_TOT.kv2|default:0}</strong></td>
									<td><strong>{$MKT_TOT.kv3|default:0}</strong></td>
									<td><strong>{$MKT_TOT.region_unknown|default:0}</strong></td>
									<td><strong>{$MKT_TOT.consulting|default:0}</strong></td>
									<td><strong>{$MKT_TOT.unreachable|default:0}</strong></td>
									<td><strong>{$MKT_TOT.invalid|default:0}</strong></td>
									<td><strong>{$MKT_TOT.online_class|default:0}</strong></td>
								</tr>
								{/if}
							{else}
								<tr><td colspan="12" class="text-muted text-center">Chưa có Lead trong khoảng ngày đã chọn.</td></tr>
							{/if}
						</tbody>
					</table>
				</div>

				<div class="mk-mkt-chart-grid" style="margin-top:14px;">
					<div class="mk-mkt-chart-card">
						<div class="mk-mkt-chart-card__title">Xu hướng Data theo ngày</div>
						<div class="mk-mkt-chart-wrap mk-mkt-chart-wrap--tall"><canvas id="mgmt-mkt-daily-chart" width="600" height="280"></canvas></div>
						<p class="mk-mkt-chart-hint" id="mgmt-mkt-daily-hint"></p>
					</div>
					<div class="mk-mkt-chart-card">
						<div class="mk-mkt-chart-card__title">Nguồn Lead</div>
						<div class="mk-mkt-chart-wrap"><canvas id="mgmt-mkt-source-chart" width="400" height="260"></canvas></div>
						<p class="mk-mkt-chart-hint" id="mgmt-mkt-source-hint"></p>
					</div>
					<div class="mk-mkt-chart-card">
						<div class="mk-mkt-chart-card__title">Khu vực (KV)</div>
						<div class="mk-mkt-chart-wrap"><canvas id="mgmt-mkt-region-chart" width="400" height="260"></canvas></div>
						<p class="mk-mkt-chart-hint" id="mgmt-mkt-region-hint"></p>
					</div>
				</div>
			</div>

			{* ===== BẢNG 2 ===== *}
			<div class="mk-reports-mgmt-panel mk-mkt-panel">
				<div class="mk-mkt-panel__head">
					<div>
						<div class="mk-mkt-badge mk-mkt-badge--amber">Bảng 2</div>
						<h2 class="mk-mkt-panel__title">Funnel theo ngày học</h2>
					</div>
				</div>
				<div class="table-responsive mk-mkt-scroll">
					<table class="table table-bordered mgmt-report-table mk-mkt-sheet mk-mkt-sheet--class">
						<thead>
							<tr>
								<th>Tháng</th>
								<th>Thứ</th>
								<th>Ngày học</th>
								<th>Tổng Data Marketing</th>
								<th>Số lượng hẹn</th>
								<th>Dời lịch</th>
								<th>Không học</th>
								<th>Xác nhận tham gia</th>
								<th>Thực tế tham gia</th>
								<th class="mk-mkt-th-pct">% Khách tiềm năng</th>
								<th class="mk-mkt-th-pct mk-mkt-th--accent">% Tham gia / hẹn</th>
								<th class="mk-mkt-th-pct">% Xác nhận / hẹn</th>
								<th class="mk-mkt-th-pct">% Thực tế / XN</th>
								<th>PCTH</th>
								<th>PCTH+MQ</th>
								<th>MQ</th>
								<th class="mk-mkt-th-pct">Tỷ lệ khách chốt</th>
								<th class="mk-mkt-th-pct">Tổng %</th>
								<th class="mk-mkt-th--reason">KNM / Bận</th>
								<th class="mk-mkt-th--reason">Phân vân</th>
								<th class="mk-mkt-th--reason">Mời lại</th>
								<th class="mk-mkt-th--reason">Ngóng chờ</th>
								<th class="mk-mkt-th--reason">Chưa XĐ</th>
								<th class="mk-mkt-th--reason">Học chỗ khác</th>
								<th class="mk-mkt-th--reason">QT nhượng quyền</th>
								<th class="mk-mkt-th--reason">QT nguyên liệu</th>
							</tr>
						</thead>
						<tbody>
							{if $MKT.class_days|@count gt 0}
								{foreach from=$MKT.class_days item=row}
									<tr{if $row.is_summary} class="mk-mkt-row--summary"{elseif $row.appointments gt 0} class="mk-mkt-row--hot"{/if}>
										<td>{$row.month_label|escape:'html'}</td>
										<td>{$row.weekday|escape:'html'}</td>
										<td class="mk-mkt-date">{$row.label|escape:'html'}</td>
										<td>{$row.total_leads}</td>
										<td>{$row.appointments}</td>
										<td>{$row.reschedule}</td>
										<td>{$row.khong_hoc}</td>
										<td>{$row.confirmed}</td>
										<td>{$row.show}</td>
										<td class="mk-mkt-kpi-cell">{$row.pct_potential}%</td>
										<td class="mk-mkt-kpi-cell mk-mkt-kpi-cell--accent">{$row.pct_show_appt}%</td>
										<td class="mk-mkt-kpi-cell">{$row.pct_confirm_appt}%</td>
										<td class="mk-mkt-kpi-cell">{$row.pct_show_confirm}%</td>
										<td>{$row.pcth}</td>
										<td>{$row.pcth_mq}</td>
										<td>{$row.mq}</td>
										<td class="mk-mkt-kpi-cell">{$row.pct_close_appt}%</td>
										<td class="mk-mkt-kpi-cell">{$row.pct_close_total}%</td>
										<td>{$row.knm_ban}</td>
										<td>{$row.phan_van}</td>
										<td>{$row.moi_lai}</td>
										<td>{$row.ngong_cho}</td>
										<td>{$row.chua_xac_dinh}</td>
										<td>{$row.hoc_cho_khac}</td>
										<td>{$row.quan_tam_nq}</td>
										<td>{$row.quan_tam_nl}</td>
									</tr>
								{/foreach}
							{else}
								<tr><td colspan="26" class="mk-mkt-empty-hint">Chưa có ngày học / lịch hẹn gắn Lead hoặc Opportunity trong khoảng lọc.</td></tr>
							{/if}
						</tbody>
					</table>
				</div>
				<div class="mk-mkt-chart-card" style="margin-top:14px;">
					<div class="mk-mkt-chart-card__title">Hẹn · Show · Chốt theo ngày học</div>
					<div class="mk-mkt-chart-wrap"><canvas id="mgmt-mkt-class-chart"></canvas></div>
					<p class="mk-mkt-chart-hint" id="mgmt-mkt-class-hint"></p>
				</div>
			</div>

			{* ===== BẢNG 3 ===== *}
			<div class="mk-reports-mgmt-panel mk-mkt-panel">
				<div class="mk-mkt-panel__head">
					<div>
						<div class="mk-mkt-badge mk-mkt-badge--mint">Bảng 3</div>
						<h2 class="mk-mkt-panel__title">Tình trạng theo ngày học</h2>
					</div>
				</div>
				<div class="table-responsive mk-mkt-scroll">
					<table class="table table-bordered mgmt-report-table mk-mkt-sheet mk-mkt-sheet--status">
						<thead>
							<tr>
								<th class="mk-mkt-status-label-th">Tình trạng</th>
								{if $STATUS_MX.columns|@count gt 0}
									{foreach from=$STATUS_MX.columns item=col}
										<th class="mk-mkt-status-date-th">{$col.label|escape:'html'}</th>
									{/foreach}
								{else}
									<th class="mk-mkt-status-date-th">Chưa có ngày học</th>
								{/if}
							</tr>
						</thead>
						<tbody>
							{if $STATUS_MX.columns|@count gt 0 && $STATUS_MX.rows|@count gt 0}
								{foreach from=$STATUS_MX.rows item=srow}
									<tr class="{if $srow.highlight eq 'yellow'}mk-mkt-row--yellow{elseif $srow.highlight eq 'green'}mk-mkt-row--green{/if}">
										<td class="mk-mkt-funnel-label mk-mkt-status-label-td">{$srow.label|escape:'html'}</td>
										{foreach from=$srow.cells item=cell}
											<td>{if $cell neq 0}{$cell}{else}<span class="mk-mkt-zero">0</span>{/if}</td>
										{/foreach}
									</tr>
								{/foreach}
							{else}
								{foreach from=$STATUS_MX.rows item=srow}
									<tr class="{if $srow.highlight eq 'yellow'}mk-mkt-row--yellow{elseif $srow.highlight eq 'green'}mk-mkt-row--green{/if}">
										<td class="mk-mkt-funnel-label mk-mkt-status-label-td">{$srow.label|escape:'html'}</td>
										<td class="mk-mkt-zero">—</td>
									</tr>
								{foreachelse}
									<tr><td colspan="2" class="mk-mkt-empty-hint">Chưa có dữ liệu tình trạng theo ngày học.</td></tr>
								{/foreach}
							{/if}
						</tbody>
					</table>
				</div>
			</div>

			{* ===== TỔNG KẾT THEO THÁNG ===== *}
			<div class="mk-reports-mgmt-panel mk-mkt-panel mk-mkt-panel--month">
				<div class="mk-mkt-panel__head">
					<div>
						<div class="mk-mkt-badge mk-mkt-badge--slate">Tổng kết</div>
						<h2 class="mk-mkt-panel__title">Tổng kết theo tháng (12 tháng gần nhất)</h2>
					</div>
				</div>
				<div class="table-responsive mk-mkt-scroll mk-mkt-scroll--month">
					<table class="table table-bordered mgmt-report-table mk-mkt-sheet mk-mkt-sheet--month">
						<thead>
							<tr>
								<th>Tháng</th>
								<th>Tổng Data MKT</th>
								<th>Đã liên hệ</th>
								<th>Đặt lịch</th>
								<th>Show</th>
								<th>Xác nhận</th>
								<th>Đã chốt</th>
								<th class="mk-mkt-th-pct">% Show/hẹn</th>
								<th class="mk-mkt-th-pct">% XN/hẹn</th>
								<th class="mk-mkt-th-pct">% Chốt/hẹn</th>
								<th class="mk-mkt-th-pct">% Chốt/Lead</th>
							</tr>
						</thead>
						<tbody>
							{if $MKT.monthly|@count gt 0}
								{foreach from=$MKT.monthly item=row}
									<tr{if $row.total_leads gt 0 || $row.appointments gt 0} class="mk-mkt-row--hot"{/if}>
										<td class="mk-mkt-date">{$row.label|escape:'html'}</td>
										<td>{$row.total_leads}</td>
										<td>{$row.contacted}</td>
										<td>{$row.appointments}</td>
										<td>{$row.show}</td>
										<td>{$row.confirmed}</td>
										<td>{$row.closed}</td>
										<td class="mk-mkt-kpi-cell">{$row.pct_show_appt}%</td>
										<td class="mk-mkt-kpi-cell">{$row.pct_confirm_appt}%</td>
										<td class="mk-mkt-kpi-cell">{$row.pct_close_appt}%</td>
										<td class="mk-mkt-kpi-cell">{$row.pct_close_lead}%</td>
									</tr>
								{/foreach}
							{else}
								<tr><td colspan="11" class="mk-mkt-empty-hint">Chưa có dữ liệu theo tháng.</td></tr>
							{/if}
						</tbody>
					</table>
				</div>
				<div class="mk-mkt-chart-card" style="margin-top:14px;">
					<div class="mk-mkt-chart-card__title">Tiến độ 12 tháng</div>
					<div class="mk-mkt-chart-wrap mk-mkt-chart-wrap--tall"><canvas id="mgmt-mkt-month-chart"></canvas></div>
					<p class="mk-mkt-chart-hint" id="mgmt-mkt-month-hint"></p>
				</div>
			</div>

			<script type="application/json" id="mgmt-mkt-chart-data">{$REPORT_MKT_CHART_JSON nofilter}</script>
			<script type="application/json" id="mgmt-mkt-monthly-data">{$REPORT_MKT_MONTHLY_JSON nofilter}</script>
			<script type="application/json" id="mgmt-mkt-kpi-data">{$REPORT_MKT_KPI_JSON nofilter}</script>
			<script type="application/json" id="mgmt-mkt-totals-data">{$REPORT_MKT_TOTALS_JSON nofilter}</script>
			<script type="application/json" id="mgmt-mkt-class-data">{$REPORT_MKT_CLASS_JSON nofilter}</script>
		</section>
	</div>
</div>

{* Chart.js local — load ngay trước khi vẽ, không phụ thuộc header *}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Reports/resources/vendor/chart.umd.min.js')}?mk_v=20260713_chart1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Reports/resources/ReportsMkCharts.js')}?mk_v=20260713_mktui8"></script>

<div class="modal fade" id="mgmtExportModal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Export báo cáo MKT SALE</h4>
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
