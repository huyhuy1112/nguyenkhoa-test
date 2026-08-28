{* Admin KPI — access denied (aligned with OperationNotPermitted modern UI) *}
{strip}
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<style>
.mk-kpi-deny {
	--mk-ink: #13241c;
	--mk-muted: #5a6f64;
	--mk-line: rgba(19, 36, 28, 0.1);
	--mk-accent: #0d8f55;
	--mk-accent-deep: #0a6b40;
	--mk-soft: #eef6f1;
	font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif;
	padding: 28px 16px 48px;
}
.mk-kpi-deny__card {
	max-width: 560px;
	margin: 24px auto;
	padding: 36px 32px 28px;
	border-radius: 24px;
	background: rgba(255,255,255,0.94);
	border: 1px solid var(--mk-line);
	box-shadow: 0 24px 48px rgba(19, 36, 28, 0.07);
	text-align: center;
	animation: mkKpiDenyIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes mkKpiDenyIn {
	from { opacity: 0; transform: translateY(14px); }
	to { opacity: 1; transform: none; }
}
.mk-kpi-deny__icon {
	width: 76px; height: 76px; margin: 0 auto 18px; border-radius: 24px;
	display: grid; place-items: center;
	background: linear-gradient(145deg, #fff, var(--mk-soft));
	border: 1px solid rgba(13, 143, 85, 0.18);
	color: var(--mk-accent-deep);
}
.mk-kpi-deny__kicker {
	font-size: 11px; font-weight: 700; letter-spacing: 0.14em;
	text-transform: uppercase; color: var(--mk-accent); margin: 0 0 8px;
}
.mk-kpi-deny__title {
	margin: 0 0 10px; font-size: 24px; font-weight: 800; letter-spacing: -0.03em; color: var(--mk-ink);
}
.mk-kpi-deny__text {
	margin: 0 auto 22px; max-width: 42ch; color: var(--mk-muted); font-size: 14px; line-height: 1.55;
}
.mk-kpi-deny__btn {
	display: inline-flex; align-items: center; gap: 8px;
	min-height: 44px; padding: 0 18px; border-radius: 12px;
	background: linear-gradient(180deg, #12a561, var(--mk-accent-deep));
	color: #fff !important; font-weight: 600; font-size: 14px; text-decoration: none;
	box-shadow: 0 10px 22px rgba(13, 143, 85, 0.28);
}
.mk-kpi-deny__btn:hover { color: #fff !important; text-decoration: none; transform: translateY(-1px); }
</style>
<div class="dashboard-page-root mk-admin-kpi-page mk-kpi-deny">
	<div class="mk-kpi-deny__card" role="alert">
		<div class="mk-kpi-deny__icon" aria-hidden="true">
			<svg width="36" height="36" viewBox="0 0 24 24" fill="none"><path d="M8 10V8a4 4 0 1 1 8 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="5" y="10" width="14" height="11" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M12 14.2v2.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
		</div>
		<p class="mk-kpi-deny__kicker">Nguyên Khoa CRM</p>
		<h1 class="mk-kpi-deny__title">Không có quyền xem bảng điều khiển</h1>
		<p class="mk-kpi-deny__text">
			Tài khoản hiện tại chưa được cấp quyền Dashboard KPI.
			Vui lòng liên hệ quản trị viên nếu bạn cần truy cập khu vực này.
		</p>
		<a class="mk-kpi-deny__btn" href="index.php?module=Home&amp;view=MainPage&amp;app=MANAGEMENT">Về trang chính</a>
	</div>
</div>
</main>
		</div>
</div>
{/strip}
