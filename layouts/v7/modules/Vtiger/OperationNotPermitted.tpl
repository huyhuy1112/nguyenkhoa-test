{* Modern access-denied page — replaces legacy table + browser alert *}
{strip}
{assign var=MK_DENY_MSG value=''}
{if !empty($ERROR_MESSAGE)}
	{assign var=MK_DENY_MSG value=$ERROR_MESSAGE}
{elseif !empty($MESSAGE)}
	{assign var=MK_DENY_MSG value=vtranslate($MESSAGE)}
{else}
	{assign var=MK_DENY_MSG value=vtranslate('LBL_PERMISSION_DENIED')}
{/if}
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<style>
.mk-access-deny {
	--mk-ink: #13241c;
	--mk-muted: #5a6f64;
	--mk-line: rgba(19, 36, 28, 0.1);
	--mk-accent: #0d8f55;
	--mk-accent-deep: #0a6b40;
	--mk-warn: #c45c26;
	--mk-soft: #eef6f1;
	--mk-card: rgba(255, 255, 255, 0.92);
	box-sizing: border-box;
	min-height: 72vh;
	margin: 0;
	padding: 48px 20px 64px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif;
	color: var(--mk-ink);
	background:
		radial-gradient(900px 420px at 12% -10%, rgba(13, 143, 85, 0.16), transparent 55%),
		radial-gradient(700px 380px at 100% 0%, rgba(196, 92, 38, 0.1), transparent 50%),
		linear-gradient(165deg, #f7fbf8 0%, #eef3f0 42%, #f5f2ec 100%);
	position: relative;
	overflow: hidden;
}
.mk-access-deny *,
.mk-access-deny *::before,
.mk-access-deny *::after { box-sizing: border-box; }
.mk-access-deny__grid {
	position: absolute;
	inset: 0;
	background-image:
		linear-gradient(rgba(19, 36, 28, 0.035) 1px, transparent 1px),
		linear-gradient(90deg, rgba(19, 36, 28, 0.035) 1px, transparent 1px);
	background-size: 48px 48px;
	mask-image: radial-gradient(ellipse at center, #000 20%, transparent 75%);
	pointer-events: none;
}
.mk-access-deny__card {
	position: relative;
	z-index: 1;
	width: min(520px, 100%);
	background: var(--mk-card);
	backdrop-filter: blur(14px);
	-webkit-backdrop-filter: blur(14px);
	border: 1px solid var(--mk-line);
	border-radius: 24px;
	padding: 40px 36px 32px;
	box-shadow:
		0 1px 0 rgba(255, 255, 255, 0.8) inset,
		0 24px 48px rgba(19, 36, 28, 0.08),
		0 8px 16px rgba(19, 36, 28, 0.04);
	text-align: center;
	animation: mkAccessIn 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes mkAccessIn {
	from { opacity: 0; transform: translateY(18px) scale(0.98); }
	to { opacity: 1; transform: none; }
}
.mk-access-deny__icon {
	width: 88px;
	height: 88px;
	margin: 0 auto 22px;
	border-radius: 28px;
	display: grid;
	place-items: center;
	background: linear-gradient(145deg, #fff 0%, var(--mk-soft) 100%);
	border: 1px solid rgba(13, 143, 85, 0.18);
	box-shadow: 0 12px 28px rgba(13, 143, 85, 0.12);
	animation: mkAccessIcon 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.12s both;
}
@keyframes mkAccessIcon {
	from { opacity: 0; transform: scale(0.86); }
	to { opacity: 1; transform: none; }
}
.mk-access-deny__icon svg {
	width: 40px;
	height: 40px;
	color: var(--mk-accent-deep);
}
.mk-access-deny__kicker {
	margin: 0 0 8px;
	font-size: 11px;
	font-weight: 700;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--mk-accent);
}
.mk-access-deny__title {
	margin: 0 0 12px;
	font-size: clamp(22px, 4vw, 28px);
	font-weight: 800;
	letter-spacing: -0.03em;
	line-height: 1.2;
	color: var(--mk-ink);
}
.mk-access-deny__msg {
	margin: 0 auto 8px;
	max-width: 38ch;
	font-size: 15px;
	line-height: 1.55;
	font-weight: 500;
	color: var(--mk-ink);
}
.mk-access-deny__hint {
	margin: 0 auto 28px;
	max-width: 40ch;
	font-size: 13.5px;
	line-height: 1.55;
	color: var(--mk-muted);
}
.mk-access-deny__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	justify-content: center;
}
.mk-access-deny__btn {
	appearance: none;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	min-height: 44px;
	padding: 0 18px;
	border-radius: 12px;
	font: inherit;
	font-size: 14px;
	font-weight: 600;
	text-decoration: none;
	cursor: pointer;
	border: 1px solid transparent;
	transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease, border-color 0.15s ease;
}
.mk-access-deny__btn:hover {
	transform: translateY(-1px);
	text-decoration: none;
}
.mk-access-deny__btn:active { transform: translateY(0); }
.mk-access-deny__btn--primary {
	background: linear-gradient(180deg, #12a561 0%, var(--mk-accent-deep) 100%);
	color: #fff !important;
	box-shadow: 0 10px 22px rgba(13, 143, 85, 0.28);
}
.mk-access-deny__btn--primary:hover {
	box-shadow: 0 14px 28px rgba(13, 143, 85, 0.34);
	color: #fff !important;
}
.mk-access-deny__btn--ghost {
	background: #fff;
	color: var(--mk-ink) !important;
	border-color: var(--mk-line);
}
.mk-access-deny__btn--ghost:hover {
	border-color: rgba(13, 143, 85, 0.35);
	background: var(--mk-soft);
	color: var(--mk-ink) !important;
}
.mk-access-deny__meta {
	margin-top: 26px;
	padding-top: 18px;
	border-top: 1px solid var(--mk-line);
	font-size: 12px;
	color: var(--mk-muted);
}
@media (max-width: 520px) {
	.mk-access-deny { padding: 28px 14px 40px; min-height: 64vh; }
	.mk-access-deny__card { padding: 32px 22px 26px; border-radius: 20px; }
	.mk-access-deny__actions { flex-direction: column; }
	.mk-access-deny__btn { width: 100%; }
}
</style>

<div class="mk-access-deny" role="alert" aria-live="polite">
	<div class="mk-access-deny__grid" aria-hidden="true"></div>
	<div class="mk-access-deny__card">
		<div class="mk-access-deny__icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M8 10V8a4 4 0 1 1 8 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
				<rect x="5" y="10" width="14" height="11" rx="3" stroke="currentColor" stroke-width="1.8"/>
				<path d="M12 14.2v2.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
			</svg>
		</div>
		<p class="mk-access-deny__kicker">Nguyên Khoa CRM</p>
		<h1 class="mk-access-deny__title">Không có quyền truy cập</h1>
		<p class="mk-access-deny__msg">{$MK_DENY_MSG|escape:'html'}</p>
		<p class="mk-access-deny__hint">
			Tài khoản của bạn chưa được cấp quyền cho thao tác này.
			Nếu cần truy cập, hãy liên hệ quản trị viên để cập nhật Role / Profile.
		</p>
		<div class="mk-access-deny__actions">
			<button type="button" class="mk-access-deny__btn mk-access-deny__btn--ghost" id="mkAccessDenyBack">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
				Quay lại
			</button>
			<a class="mk-access-deny__btn mk-access-deny__btn--primary" href="index.php?module=Home&amp;view=MainPage&amp;app=MANAGEMENT">
				Về trang chính
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</a>
		</div>
		<div class="mk-access-deny__meta">Mã: ACCESS_DENIED · Phân quyền theo Role &amp; Profile</div>
	</div>
</div>
<script type="text/javascript">
(function () {
	var btn = document.getElementById('mkAccessDenyBack');
	if (!btn) return;
	btn.addEventListener('click', function () {
		if (window.history && window.history.length > 1) {
			window.history.back();
			return;
		}
		window.location.href = 'index.php?module=Home&view=MainPage&app=MANAGEMENT';
	});
})();
</script>
{/strip}
