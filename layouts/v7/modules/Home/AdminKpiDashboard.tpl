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
			<h3 class="mk-admin-kpi-subtitle">Kết quả kinh doanh</h3>
			<div class="mk-admin-kpi-grid" id="mkAdminKpiBiz" role="list">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<h3 class="mk-admin-kpi-subtitle">Kết quả khóa học</h3>
			<div class="mk-admin-kpi-grid" id="mkAdminKpiCourse" role="list">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
		</section>

		<div class="mk-admin-kpi-stagebar">
			<div class="mk-admin-kpi-stagebar__tabs" id="mkAdminKpiStagePick">
				<button type="button" class="mk-admin-kpi-mode-btn is-active" data-stage-panel="offline">Offline</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-panel="online">Online</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-panel="gd14">990k</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-panel="pcth">PCTH</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-panel="mqbb">MQBB</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-panel="combo">Combo</button>
				<button type="button" class="mk-admin-kpi-mode-btn" data-stage-panel="nl">Nguyên liệu</button>
			</div>
			<div class="mk-admin-kpi-stagebar__period" id="mkAdminKpiStagePeriod">
				<div class="mk-admin-kpi-period-modes">
					<button type="button" class="mk-admin-kpi-mode-btn is-active" data-stage-period="month">Tháng</button>
					<button type="button" class="mk-admin-kpi-mode-btn" data-stage-period="quarter">Quý</button>
					<button type="button" class="mk-admin-kpi-mode-btn" data-stage-period="year">Năm</button>
				</div>
				<div class="mk-admin-kpi-period-nav" role="group" aria-label="Chọn kỳ">
					<button type="button" class="mk-admin-kpi-period-nav__btn" data-stage-nav="prev" title="Kỳ trước" aria-label="Kỳ trước">‹</button>
					<span class="mk-admin-kpi-period-nav__label" id="mkAdminKpiPeriodLabel">Tháng hiện tại</span>
					<button type="button" class="mk-admin-kpi-period-nav__btn" data-stage-nav="next" title="Kỳ sau" aria-label="Kỳ sau" disabled>›</button>
				</div>
			</div>
		</div>
		<div class="mk-admin-kpi-stage-stack">

		{* Offline GD 1.1 *}
		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--offline mk-admin-kpi-panel--hud" id="mkAdminKpiOffline" data-stage-theme="offline">
			<div class="mk-admin-kpi-hud" aria-hidden="true">
				<span class="mk-admin-kpi-hud__orb"></span>
				<span class="mk-admin-kpi-hud__grid"></span>
				<span class="mk-admin-kpi-hud__icon"></span>
			</div>
			<div class="mk-admin-kpi-panel-head">
				<div class="mk-admin-kpi-panel-heading">
					<span class="mk-admin-kpi-panel-badge">GD 1.1</span>
					<h2 class="mk-admin-kpi-panel-title">Offline miễn phí</h2>
				</div>
				<span class="mk-admin-kpi-pill" id="mkAdminKpiOfflineRate">Tỷ lệ tham gia: —</span>
			</div>
			<div class="mk-admin-kpi-offline" id="mkAdminKpiOfflineBody">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<section class="mk-admin-kpi-drill" id="mkAdminKpiOfflineDrill" hidden aria-live="polite"></section>
		</section>

		{* Online GD 1.2 *}
		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--online mk-admin-kpi-panel--hud" id="mkAdminKpiOnline" hidden data-stage-theme="online">
			<div class="mk-admin-kpi-hud" aria-hidden="true">
				<span class="mk-admin-kpi-hud__orb"></span>
				<span class="mk-admin-kpi-hud__grid"></span>
				<span class="mk-admin-kpi-hud__icon">{* Online *}</span>
			</div>
			<div class="mk-admin-kpi-panel-head">
				<div class="mk-admin-kpi-panel-heading">
					<span class="mk-admin-kpi-panel-badge">GD 1.2</span>
					<h2 class="mk-admin-kpi-panel-title">Online Zalo OA</h2>
				</div>
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

		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--gd14 mk-admin-kpi-panel--hud" id="mkAdminKpiGd14" hidden data-stage-theme="gd14">
			<div class="mk-admin-kpi-hud" aria-hidden="true">
				<span class="mk-admin-kpi-hud__orb"></span>
				<span class="mk-admin-kpi-hud__grid"></span>
				<span class="mk-admin-kpi-hud__icon">{* 990k *}</span>
			</div>
			<div class="mk-admin-kpi-panel-head">
				<div class="mk-admin-kpi-panel-heading">
					<span class="mk-admin-kpi-panel-badge">GD 1.4</span>
					<h2 class="mk-admin-kpi-panel-title">Lớp 990k</h2>
				</div>
				<span class="mk-admin-kpi-pill" id="mkAdminKpiGd14Period">Tháng này · SỐ TẠM</span>
			</div>
			<div class="mk-admin-kpi-offline" id="mkAdminKpiGd14Body">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<section class="mk-admin-kpi-drill" id="mkAdminKpiGd14Drill" hidden aria-live="polite"></section>
		</section>

		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--pcth mk-admin-kpi-panel--hud" id="mkAdminKpiPcth" hidden data-stage-theme="pcth">
			<div class="mk-admin-kpi-hud" aria-hidden="true">
				<span class="mk-admin-kpi-hud__orb"></span>
				<span class="mk-admin-kpi-hud__grid"></span>
				<span class="mk-admin-kpi-hud__icon">{* PCTH *}</span>
			</div>
			<div class="mk-admin-kpi-panel-head">
				<div class="mk-admin-kpi-panel-heading">
					<span class="mk-admin-kpi-panel-badge">PCTH</span>
					<h2 class="mk-admin-kpi-panel-title">Pha chế tổng hợp</h2>
				</div>
				<span class="mk-admin-kpi-pill" id="mkAdminKpiPcthPeriod">Tháng này · SỐ TẠM</span>
			</div>
			<div class="mk-admin-kpi-offline" id="mkAdminKpiPcthBody">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<section class="mk-admin-kpi-drill" id="mkAdminKpiPcthDrill" hidden aria-live="polite"></section>
		</section>

		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--mqbb mk-admin-kpi-panel--hud" id="mkAdminKpiMqbb" hidden data-stage-theme="mqbb">
			<div class="mk-admin-kpi-hud" aria-hidden="true">
				<span class="mk-admin-kpi-hud__orb"></span>
				<span class="mk-admin-kpi-hud__grid"></span>
				<span class="mk-admin-kpi-hud__icon">{* MQBB *}</span>
			</div>
			<div class="mk-admin-kpi-panel-head">
				<div class="mk-admin-kpi-panel-heading">
					<span class="mk-admin-kpi-panel-badge">MQBB</span>
					<h2 class="mk-admin-kpi-panel-title">Mở quán bài bản</h2>
				</div>
				<span class="mk-admin-kpi-pill" id="mkAdminKpiMqbbPeriod">Tháng này · SỐ TẠM</span>
			</div>
			<div class="mk-admin-kpi-offline" id="mkAdminKpiMqbbBody">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<section class="mk-admin-kpi-drill" id="mkAdminKpiMqbbDrill" hidden aria-live="polite"></section>
		</section>

		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--combo mk-admin-kpi-panel--hud" id="mkAdminKpiCombo" hidden data-stage-theme="combo">
			<div class="mk-admin-kpi-hud" aria-hidden="true">
				<span class="mk-admin-kpi-hud__orb"></span>
				<span class="mk-admin-kpi-hud__grid"></span>
				<span class="mk-admin-kpi-hud__icon">{* Combo *}</span>
			</div>
			<div class="mk-admin-kpi-panel-head">
				<div class="mk-admin-kpi-panel-heading">
					<span class="mk-admin-kpi-panel-badge">Combo</span>
					<h2 class="mk-admin-kpi-panel-title">Combo mở quán</h2>
				</div>
				<span class="mk-admin-kpi-pill" id="mkAdminKpiComboPeriod">Tháng này · SỐ TẠM</span>
			</div>
			<div class="mk-admin-kpi-offline" id="mkAdminKpiComboBody">
				<div class="mk-admin-kpi-detail-loading">Đang tải…</div>
			</div>
			<section class="mk-admin-kpi-drill" id="mkAdminKpiComboDrill" hidden aria-live="polite"></section>
		</section>

		<section class="mk-admin-kpi-panel mk-admin-kpi-panel--nl mk-admin-kpi-panel--hud" id="mkAdminKpiNl" hidden data-stage-theme="nl">
			<div class="mk-admin-kpi-hud" aria-hidden="true">
				<span class="mk-admin-kpi-hud__orb"></span>
				<span class="mk-admin-kpi-hud__grid"></span>
				<span class="mk-admin-kpi-hud__icon">{* NL *}</span>
			</div>
			<div class="mk-admin-kpi-panel-head">
				<div class="mk-admin-kpi-panel-heading">
					<span class="mk-admin-kpi-panel-badge">NL</span>
					<h2 class="mk-admin-kpi-panel-title">Nguyên liệu</h2>
				</div>
				<a class="mk-admin-kpi-pill" href="index.php?module=HelpDesk&view=MaterialAlerts&app=SUPPORT">Mở danh sách việc</a>
			</div>
			<div class="mk-admin-kpi-offline" id="mkAdminKpiNlBody"><div class="mk-admin-kpi-detail-loading">Đang tải…</div></div>
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
