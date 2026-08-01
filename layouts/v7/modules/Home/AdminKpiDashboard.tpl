{* Admin KPI Dashboard — Stage 1–5 *}
{strip}
<div class="dashboard-page-root mk-admin-kpi-page" id="mkAdminKpiRoot" data-module="{$MODULE_NAME}">
	<div class="mk-dashboard-shell mk-admin-kpi-shell">
		<header class="mk-admin-kpi-header">
			<div class="mk-admin-kpi-kicker">Nguyên Khoa</div>
			<h1 class="mk-admin-kpi-title">Bảng điều khiển quản trị</h1>
			<p class="mk-admin-kpi-sub">Tổng quan chỉ số — bấm thẻ → xem tóm tắt → bấm số để mở danh sách</p>
		</header>

		{* Stage 5 — Alerts *}
		<div class="mk-admin-kpi-alerts" id="mkAdminKpiAlerts">
			<span class="mk-admin-kpi-alert mk-admin-kpi-alert--muted">⚠ … KH tiềm năng chưa gọi</span>
			<span class="mk-admin-kpi-alert mk-admin-kpi-alert--muted">⚠ … Đơn hàng đang nháp</span>
			<span class="mk-admin-kpi-alert mk-admin-kpi-alert--muted">⚠ … Báo giá đang nháp</span>
			<span class="mk-admin-kpi-alert mk-admin-kpi-alert--muted">⚠ … KH nhượng quyền chưa nghe máy</span>
		</div>
		<section class="mk-admin-kpi-drill mk-admin-kpi-alert-drill" id="mkAdminKpiAlertDrill" hidden aria-live="polite"></section>

		{* Stage 1 — KPI cards *}
		<div class="mk-admin-kpi-grid" role="list">
			<button type="button" class="mk-admin-kpi-card is-active" data-section="customers" role="listitem">
				<span class="mk-admin-kpi-card-label">Tổng Khách hàng</span>
				<span class="mk-admin-kpi-card-value" data-key="customers">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="leads" role="listitem">
				<span class="mk-admin-kpi-card-label">KH tiềm năng mới hôm nay</span>
				<span class="mk-admin-kpi-card-value" data-key="leads_today">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="revenue" role="listitem">
				<span class="mk-admin-kpi-card-label">Doanh thu tháng</span>
				<span class="mk-admin-kpi-card-value" data-key="revenue_month">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="quotes" role="listitem">
				<span class="mk-admin-kpi-card-label">Báo giá đang chờ</span>
				<span class="mk-admin-kpi-card-value" data-key="quotes_pending">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="orders" role="listitem">
				<span class="mk-admin-kpi-card-label">Đơn hàng đang xử lý</span>
				<span class="mk-admin-kpi-card-value" data-key="orders_processing">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="franchise" role="listitem">
				<span class="mk-admin-kpi-card-label">Hợp đồng nhượng quyền</span>
				<span class="mk-admin-kpi-card-value" data-key="franchise_contracts">—</span>
			</button>
		</div>

		{* Stage 1 — detail panel *}
		<section class="mk-admin-kpi-detail" id="mkAdminKpiDetail" aria-live="polite">
			<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
		</section>

		<section class="mk-admin-kpi-drill" id="mkAdminKpiDrill" hidden aria-live="polite"></section>

		<div class="mk-admin-kpi-stages">
			{* Stage 2 — Funnel *}
			<section class="mk-admin-kpi-panel" id="mkAdminKpiFunnel">
				<h2 class="mk-admin-kpi-panel-title">Phễu bán hàng</h2>
				<div class="mk-admin-kpi-funnel" id="mkAdminKpiFunnelBody">
					<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
				</div>
			</section>

			{* Stage 3 — Revenue chart *}
			<section class="mk-admin-kpi-panel" id="mkAdminKpiChart">
				<div class="mk-admin-kpi-panel-head">
					<h2 class="mk-admin-kpi-panel-title">Biểu đồ doanh thu</h2>
					<div class="mk-admin-kpi-chart-filters" id="mkAdminKpiChartFilters">
						<button type="button" class="mk-admin-kpi-mode-btn is-active" data-chart-group="month">Theo tháng</button>
						<button type="button" class="mk-admin-kpi-mode-btn" data-chart-group="quarter">Theo quý</button>
						<button type="button" class="mk-admin-kpi-mode-btn" data-chart-group="year">Theo năm</button>
						<button type="button" class="mk-admin-kpi-mode-btn" data-chart-dimension="sale">Theo NV bán hàng</button>
						<button type="button" class="mk-admin-kpi-mode-btn" data-chart-dimension="product">Theo sản phẩm</button>
						<button type="button" class="mk-admin-kpi-mode-btn" data-chart-dimension="region">Theo khu vực</button>
					</div>
				</div>
				<p class="mk-admin-kpi-chart-total" id="mkAdminKpiChartTotal"></p>
				<div class="mk-admin-kpi-chart-bars" id="mkAdminKpiChartBody">
					<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
				</div>
				<section class="mk-admin-kpi-drill mk-admin-kpi-chart-drill" id="mkAdminKpiChartDrill" hidden aria-live="polite"></section>
			</section>

			{* Stage 4 — Performance *}
			<section class="mk-admin-kpi-panel" id="mkAdminKpiPerf">
				<h2 class="mk-admin-kpi-panel-title">Hiệu suất nhân viên</h2>
				<div class="mk-admin-kpi-perf-grid" id="mkAdminKpiPerfBody">
					<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
				</div>
			</section>
		</div>
	</div>
</div>
</main>
		</div>
</div>
{/strip}
