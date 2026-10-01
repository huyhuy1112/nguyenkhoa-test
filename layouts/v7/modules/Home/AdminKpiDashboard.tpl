{* Admin KPI Dashboard — redesigned shell + Offline GD1.1 *}
{strip}
<div class="dashboard-page-root mk-admin-kpi-page" id="mkAdminKpiRoot" data-module="{$MODULE_NAME}">
	<div class="mk-dashboard-shell mk-admin-kpi-shell">
		<header class="mk-admin-kpi-header">
			<div class="mk-admin-kpi-kicker">Nguyên Khoa</div>
			<h1 class="mk-admin-kpi-title">Bảng điều khiển quản trị</h1>
			<p class="mk-admin-kpi-sub">Tổng quan chỉ số — bấm thẻ → xem tóm tắt → bấm số để mở danh sách</p>
		</header>

		<div class="mk-admin-kpi-alerts" id="mkAdminKpiAlerts">
			<span class="mk-admin-kpi-alert mk-admin-kpi-alert--muted">⚠ … KH tiềm năng chưa gọi</span>
			<span class="mk-admin-kpi-alert mk-admin-kpi-alert--muted">⚠ … Đơn hàng đang nháp</span>
			<span class="mk-admin-kpi-alert mk-admin-kpi-alert--muted">⚠ … Báo giá đang nháp</span>
			<span class="mk-admin-kpi-alert mk-admin-kpi-alert--muted">⚠ … KH nhượng quyền chưa nghe máy</span>
		</div>
		<section class="mk-admin-kpi-drill mk-admin-kpi-alert-drill" id="mkAdminKpiAlertDrill" hidden aria-live="polite"></section>

		<div class="mk-admin-kpi-grid" role="list">
			<button type="button" class="mk-admin-kpi-card is-active" data-section="customers" data-tone="emerald" role="listitem">
				<span class="mk-admin-kpi-card-label">Tổng Khách hàng</span>
				<span class="mk-admin-kpi-card-value" data-key="customers">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="leads" data-tone="blue" role="listitem">
				<span class="mk-admin-kpi-card-label">KH tiềm năng mới hôm nay</span>
				<span class="mk-admin-kpi-card-value" data-key="leads_today">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="revenue" data-tone="violet" role="listitem">
				<span class="mk-admin-kpi-card-label">Doanh thu tháng</span>
				<span class="mk-admin-kpi-card-value" data-key="revenue_month">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="quotes" data-tone="amber" role="listitem">
				<span class="mk-admin-kpi-card-label">Báo giá đang chờ</span>
				<span class="mk-admin-kpi-card-value" data-key="quotes_pending">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="orders" data-tone="cyan" role="listitem">
				<span class="mk-admin-kpi-card-label">Đơn hàng đang xử lý</span>
				<span class="mk-admin-kpi-card-value" data-key="orders_processing">—</span>
			</button>
			<button type="button" class="mk-admin-kpi-card" data-section="franchise" data-tone="rose" role="listitem">
				<span class="mk-admin-kpi-card-label">Hợp đồng nhượng quyền</span>
				<span class="mk-admin-kpi-card-value" data-key="franchise_contracts">—</span>
			</button>
		</div>

		<section class="mk-admin-kpi-detail" id="mkAdminKpiDetail" aria-live="polite">
			<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
		</section>

		<section class="mk-admin-kpi-drill" id="mkAdminKpiDrill" hidden aria-live="polite"></section>

		<section class="mk-admin-kpi-panel" id="mkAdminKpiCompany">
			<div class="mk-admin-kpi-panel-head">
				<h2 class="mk-admin-kpi-panel-title">Báo cáo chung</h2>
				<span class="mk-admin-kpi-pill">Tháng này</span>
			</div>
			<h3 class="mk-admin-kpi-panel-title">Kết quả kinh doanh</h3>
			<div class="mk-admin-kpi-grid" id="mkAdminKpiBiz" role="list">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<h3 class="mk-admin-kpi-panel-title">Kết quả khóa học</h3>
			<div class="mk-admin-kpi-grid" id="mkAdminKpiCourse" role="list">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<h3 class="mk-admin-kpi-panel-title">Mở báo cáo chi tiết</h3>
			<div class="mk-admin-kpi-stagebar__tabs">
				<button type="button" class="mk-admin-kpi-card" data-stage-panel="offline" data-tone="emerald"><span class="mk-admin-kpi-card-label">Offline miễn phí</span></button>
				<button type="button" class="mk-admin-kpi-card" data-stage-panel="online" data-tone="blue"><span class="mk-admin-kpi-card-label">Online</span></button>
				<button type="button" class="mk-admin-kpi-card" data-stage-panel="gd14" data-tone="violet"><span class="mk-admin-kpi-card-label">990k</span></button>
				<button type="button" class="mk-admin-kpi-card" data-stage-panel="nl" data-tone="amber"><span class="mk-admin-kpi-card-label">Nguyên liệu</span></button>
			</div>
		</section>

		<div class="mk-admin-kpi-stagebar">
			<div class="mk-admin-kpi-stagebar__tabs" id="mkAdminKpiStagePick">
				<button type="button" class="mk-admin-kpi-mode-btn is-active" data-stage-panel="offline">Offline</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-panel="online">Online</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-panel="gd14">990k</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-panel="nl">Nguyên liệu</button>
			</div>
			<div class="mk-admin-kpi-stagebar__period" id="mkAdminKpiStagePeriod">
				<button type="button" class="mk-admin-kpi-mode-btn is-active" data-stage-period="month">Tháng</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-period="quarter">Quý</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-period="year">Năm</button>
			</div>
		</div>
		<div class="mk-admin-kpi-stage-stack">

		{* Offline GD 1.1 *}
		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--offline" id="mkAdminKpiOffline">
			<div class="mk-admin-kpi-panel-head">
				<h2 class="mk-admin-kpi-panel-title">Offline miễn phí (GD 1.1)</h2>
				<span class="mk-admin-kpi-pill" id="mkAdminKpiOfflineRate">Tỷ lệ tham gia: —</span>
			</div>
			<div class="mk-admin-kpi-offline" id="mkAdminKpiOfflineBody">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<section class="mk-admin-kpi-drill" id="mkAdminKpiOfflineDrill" hidden aria-live="polite"></section>
		</section>

		{* Online GD 1.2 *}
		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--online" id="mkAdminKpiOnline" hidden>
			<div class="mk-admin-kpi-panel-head">
				<h2 class="mk-admin-kpi-panel-title">Online Zalo OA (GD 1.2)</h2>
				<div class="mk-admin-kpi-pills">
					<span class="mk-admin-kpi-pill" id="mkAdminKpiOnlineFormRate">Điền form: —</span>
					<span class="mk-admin-kpi-pill mk-admin-kpi-pill--cyan" id="mkAdminKpiOnlineQualifyRate">Đủ ĐK: —</span>
				</div>
			</div>
			<div class="mk-admin-kpi-offline" id="mkAdminKpiOnlineBody">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<section class="mk-admin-kpi-drill" id="mkAdminKpiOnlineDrill" hidden aria-live="polite"></section>
		</section>

		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--gd14" id="mkAdminKpiGd14" hidden>
			<div class="mk-admin-kpi-panel-head">
				<h2 class="mk-admin-kpi-panel-title">Lớp 990k (GD 1.4)</h2>
				<span class="mk-admin-kpi-pill" id="mkAdminKpiGd14Period">Tháng này · SỐ TẠM</span>
			</div>
			<div class="mk-admin-kpi-offline" id="mkAdminKpiGd14Body">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<section class="mk-admin-kpi-drill" id="mkAdminKpiGd14Drill" hidden aria-live="polite"></section>
		</section>

		<section class="mk-admin-kpi-panel" id="mkAdminKpiNl" hidden>
			<div class="mk-admin-kpi-panel-head">
				<h2 class="mk-admin-kpi-panel-title">Nguyên liệu</h2>
				<a class="mk-admin-kpi-pill" href="index.php?module=HelpDesk&view=MaterialAlerts&app=SUPPORT">Mở danh sách việc</a>
			</div>
			<div id="mkAdminKpiNlBody"><div class="mk-admin-kpi-detail-loading">Đang tải…</div></div>
		</section>
		</div>

		<div class="mk-admin-kpi-stages">
			<section class="mk-admin-kpi-panel" id="mkAdminKpiFunnel">
				<h2 class="mk-admin-kpi-panel-title">Phễu bán hàng</h2>
				<div class="mk-admin-kpi-funnel" id="mkAdminKpiFunnelBody">
					<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
				</div>
			</section>

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
