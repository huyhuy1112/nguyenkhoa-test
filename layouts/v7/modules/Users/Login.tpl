{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Users/views/Login.php *}

{strip}
	<style>
		/* Login page only (template-scoped inline styles) */
		html, body {
			height: 100%;
			overflow: hidden; /* fixed-screen: no page scroll */
		}
		html:has(body[data-view="Login"]) {
			background-color: #061220;
		}
		body[data-view="Login"] {
			margin: 0 !important;
			padding-top: 0 !important;
			min-height: 100vh;
			background-color: #061220 !important;
			background-image: url(layouts/v7/resources/Images/login-bace-tech-bg.png) !important;
			background-position: center center !important;
			background-size: cover !important;
			background-repeat: no-repeat !important;
			background-attachment: fixed !important;
		}
		html[data-theme="dark"] body[data-view="Login"] {
			background-color: #061220 !important;
			background-image: url(layouts/v7/resources/Images/login-bace-tech-bg.png) !important;
			background-position: center center !important;
			background-size: cover !important;
			background-repeat: no-repeat !important;
			background-attachment: fixed !important;
		}
		body[data-view="Login"] #page {
			margin: 0 !important;
			padding-top: 0 !important;
			background: transparent !important;
			min-height: 100vh !important;
			height: auto !important;
		}
		body[data-view="Login"] .app-nav,
		body[data-view="Login"] nav.navbar,
		body[data-view="Login"] .app-fixed-navbar {
			display: none !important;
			height: 0 !important;
			min-height: 0 !important;
			padding: 0 !important;
			margin: 0 !important;
			border: 0 !important;
		}
		body {
			margin: 0;
			min-height: 100vh;
			background: url(layouts/v7/resources/Images/login-bace-tech-bg.png) center/cover no-repeat fixed;
		}
		body::before {
			content: "";
			position: fixed;
			inset: 0;
			background:
				radial-gradient(900px 600px at 28% 25%, rgba(34,211,238,0.20), transparent 60%),
				radial-gradient(800px 500px at 72% 28%, rgba(250,204,21,0.12), transparent 55%),
				linear-gradient(135deg, rgba(2,6,23,0.28) 0%, rgba(2,6,23,0.38) 100%);
			pointer-events: none;
			z-index: 0;
			animation: baceBgDrift 16s ease-in-out infinite alternate;
		}
		@keyframes baceBgDrift {
			from { filter: saturate(1.06) contrast(1.03); }
			to { filter: saturate(1.18) contrast(1.05); }
		}

		.loginPageContainer {
			position: relative;
			z-index: 1;
			height: 100vh;
			min-height: 100vh !important;
			margin-top: 0 !important;
			padding-top: 0 !important;
			display: flex;
			align-items: center;
			justify-content: flex-start;
			padding-left: clamp(5rem, 9vw, 10rem);
			padding-right: clamp(2rem, 5vw, 5rem);
			overflow: hidden;
		}

		/* Hide footer on login only to prevent scroll */
		.app-footer, .footer, .mk-app-footer { display: none !important; }

		/* Subtle aurora glow behind cards */
		.loginPageContainer::before,
		.loginPageContainer::after {
			content: "";
			position: fixed;
			inset: -7.5rem;
			pointer-events: none;
			z-index: 0;
			opacity: 0.9;
			filter: blur(2.5rem);
			mix-blend-mode: screen;
			animation: baceAurora 18s ease-in-out infinite alternate;
		}
		.loginPageContainer::before {
			background: radial-gradient(860px 520px at 34% 42%, rgba(34,211,238,0.34), transparent 64%);
		}
		.loginPageContainer::after {
			background: radial-gradient(860px 520px at 70% 44%, rgba(250,204,21,0.22), transparent 66%);
			animation-delay: 0.6s;
			opacity: 0.75;
		}

		/* Floating light accents (very lightweight) */
		body::after {
			content: "";
			position: fixed;
			inset: 0;
			pointer-events: none;
			z-index: 0;
			background:
				radial-gradient(0.625rem 0.625rem at 18% 22%, rgba(34,211,238,0.55), transparent 70%),
				radial-gradient(0.75rem 0.75rem at 82% 28%, rgba(250,204,21,0.45), transparent 72%),
				radial-gradient(0.5625rem 0.5625rem at 66% 78%, rgba(59,130,246,0.40), transparent 72%),
				linear-gradient(115deg, transparent 0%, rgba(255,255,255,0.06) 48%, transparent 58%);
			opacity: 0.55;
			filter: blur(0.2px);
			animation: baceFloat 22s ease-in-out infinite alternate;
		}
		@keyframes baceFloat {
			from { transform: translate3d(0,0,0); }
			to { transform: translate3d(1.125rem,-0.875rem,0); }
		}
		@keyframes baceAurora {
			from { transform: translate3d(0,0,0) scale(1); }
			to { transform: translate3d(1.25rem,-0.625rem,0) scale(1.02); }
		}

		.bace-shell {
			position: relative;
			display: grid;
			grid-template-columns: 31rem 36rem;
			column-gap: 2.25rem;
			width: 69.25rem;
			max-width: 90vw;
			margin: 0;
			align-items: start;
			justify-content: start;
			max-height: calc(100vh - 3.5rem);
			transform: translate(34rem, -4.5rem);
		}
		@media (max-width: 1100px) {
			.loginPageContainer {
				justify-content: center;
				padding: 1rem;
			}
			.bace-shell {
				grid-template-columns: 1fr;
				width: min(30rem, 92vw);
				max-width: 92vw;
				margin: 0 auto;
				transform: none;
				max-height: none;
			}
			.bace-card--login { width: 100%; }
			.bace-info-slider,
			.bace-mid-accent { display: none; }
		}

		/* NOTE: avoid animating transforms on layout containers */

		/* Glass cards */
		.bace-card {
			border-radius: 1.5rem;
			background: rgba(255, 255, 255, 0.14);
			border: 1px solid rgba(255, 255, 255, 0.22);
			box-shadow: 0 1.625rem 5rem rgba(0, 0, 0, 0.45);
			backdrop-filter: blur(0.75rem);
			-webkit-backdrop-filter: blur(0.75rem);
			overflow: hidden;
			animation: baceFadeUp 520ms ease both;
		}
		.bace-card--login {
			width: 31rem;
			padding: 1.6rem 1.6rem 1.25rem 1.6rem;
			background: rgba(255, 255, 255, 0.22); /* brighter for readability */
		}
		.bace-login-card {
			align-self: start;
			margin-top: 0 !important;
		}

		/* Info slider: positioned via grid (no fixed left/top) */
		.bace-info-slider {
			position: relative;
			z-index: 1;
			align-self: start;
			width: 36rem;
			height: 13rem;
			margin-top: 0 !important;
			display: flex;
			align-items: center;
			padding: 1.85rem;
			transform: translateY(-0.75rem) !important;
			border-radius: 1.125rem;
			background: rgba(255,255,255,0.14); /* less muddy */
			border: 1px solid rgba(255,255,255,0.20);
			backdrop-filter: blur(0.75rem);
			-webkit-backdrop-filter: blur(0.75rem);
			box-shadow: 0 0.75rem 2.5rem rgba(0,0,0,0.30);
			overflow: hidden;
		}

		.bace-login-card,
		.bace-info-slider {
			margin-top: 0 !important;
			transform: none !important;
			align-self: start;
		}
		.bace-slide {
			position: absolute;
			left: clamp(1.25rem, 1.8vw, 2rem);
			right: clamp(1.25rem, 1.8vw, 2rem);
			top: clamp(1.25rem, 1.8vw, 2rem);
			bottom: 2.25rem; /* leave room for dots */
			opacity: 0;
			transform: translateY(0.625rem);
			transition: all 0.4s ease;
		}
		.bace-slide.active {
			opacity: 1;
			transform: translateY(0);
		}
		.bace-slide h3 {
			margin: 0 0 6px 0;
			font-size: 1.85rem;
			line-height: 1.15;
			font-weight: 950;
			letter-spacing: -0.03em;
			color: #ffd54a;
			text-shadow: 0 0.125rem 0.5rem rgba(0,0,0,0.35);
		}
		.bace-slide p {
			margin: 0;
			font-size: 1.25rem;
			line-height: 1.55;
			max-width: 32rem;
			color: rgba(255,255,255,0.92);
			text-shadow: 0 0.125rem 0.5rem rgba(0,0,0,0.32);
		}
		.bace-info-dots {
			position: absolute;
			left: 1.25rem;
			right: 1.25rem;
			bottom: 0.75rem;
			display: flex;
			gap: 0.375rem;
			justify-content: center;
		}
		.bace-info-dot {
			width: 0.375rem;
			height: 0.375rem;
			border-radius: 999px;
			background: rgba(255,255,255,0.22);
		}
		.bace-info-dot.is-active { background: rgba(34,211,238,0.75); box-shadow: 0 0 0 0.1875rem rgba(34,211,238,0.18); }

		@keyframes baceFadeUp {
			from { opacity: 0; filter: blur(0.25rem); }
			to { opacity: 1; filter: blur(0); }
		}

		/* Logo tile */
		.bace-logo-tile {
			display: grid;
			place-items: center;
			background: transparent;
			border: 0;
			box-shadow: none;
			padding: 6px 0 10px 0;
		}
		.user-logo {
			width: min(15rem, 78%);
			max-width: 15rem;
			height: auto;
			display: block;
		}

		/* Subtle middle accents to reduce empty feel (no laptop cover) */
		.bace-mid-accent {
			position: absolute;
			left: calc(31rem + 1.125rem);
			top: 1.25rem;
			width: 0.125rem;
			height: 16rem;
			border-radius: 99rem;
			background: linear-gradient(
				180deg,
				rgba(34,211,238,0),
				rgba(34,211,238,0.60),
				rgba(250,204,21,0.22),
				rgba(34,211,238,0)
			);
			box-shadow: 0 0 0.75rem rgba(34,211,238,0.25);
			opacity: 0.75;
			pointer-events: none;
			z-index: 3;
		}

		.bace-h1 {
			margin: 14px 0 6px 0;
			font-size: 1.65rem;
			font-weight: 900;
			letter-spacing: -0.03em;
			color: rgba(255, 255, 255, 0.97);
		}
		.bace-sub {
			margin: 0 0 16px 0;
			color: rgba(226, 232, 240, 0.84);
			font-size: 1.15rem;
			font-weight: 600;
			line-height: 1.5;
		}

		/* Messages */
		.failureMessage, .successMessage {
			display: block;
			text-align: left;
			padding: 10px 12px;
			margin: 12px 0 0 0;
			border-radius: 14px;
			font-weight: 700;
		}
		.failureMessage { color: #fecaca; background: rgba(239, 68, 68, 0.10); border: 1px solid rgba(239, 68, 68, 0.22); }
		.successMessage { color: #bbf7d0; background: rgba(34, 197, 94, 0.10); border: 1px solid rgba(34, 197, 94, 0.22); }

		/* Inputs */
		.group { position: relative; margin: 14px 0 12px 0; }
		.bace-field {
			display: grid;
			grid-template-columns: 4.1rem 1fr !important;
			align-items: center;
			gap: 0.85rem !important;
			padding: 1.55rem 1.5rem !important;
			min-height: 5.4rem !important;
			border-radius: 1.55rem !important;
			border: 1px solid rgba(255, 255, 255, 0.18);
			background: rgba(2, 6, 23, 0.22) !important;
			overflow: hidden;
			transition: transform 160ms ease, border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
		}
		.bace-field:focus-within {
			border-color: rgba(34, 211, 238, 0.45);
			box-shadow: 0 0 0 6px rgba(34, 211, 238, 0.12);
			background: rgba(2, 6, 23, 0.28);
		}
		.bace-ico {
			width: 3.25rem !important;
			height: 3.25rem !important;
			border-radius: 1.1rem !important;
			font-size: 1.25rem !important;
			display: grid;
			place-items: center;
			background: rgba(255, 255, 255, 0.10);
			border: 1px solid rgba(255, 255, 255, 0.12);
			color: rgba(226, 232, 240, 0.92);
		}
		input, select {
			width: 100%;
			border: 0;
			outline: 0;
			background: transparent !important;
			color: #ffffff !important;
			font-size: 1.08rem;
			line-height: 1.4;
			min-height: 1.6rem;
			padding: 0;
			margin: 0;
			-webkit-appearance: none;
		}
		.bace-field input {
			height: 2.55rem !important;
			min-height: 2.55rem !important;
			font-size: 1.38rem !important;
			line-height: 2.55rem !important;
			color: #ffffff !important;
			background: transparent !important;
			padding: 0 !important;
		}
		.bace-field input::placeholder {
			font-size: 1.25rem !important;
			color: rgba(226, 232, 240, 0.72) !important;
		}

		.bace-field input,
		.bace-field input:focus,
		.bace-field input:active {
			background: transparent !important;
			color: #ffffff !important;
			box-shadow: none !important;
			-webkit-box-shadow: none !important;
			caret-color: #ffffff !important;
		}
		.bace-field input:-webkit-autofill,
		.bace-field input:-webkit-autofill:hover,
		.bace-field input:-webkit-autofill:focus,
		.bace-field input:-webkit-autofill:active {
			-webkit-text-fill-color: #ffffff !important;
			caret-color: #ffffff !important;
			background: transparent !important;
			box-shadow: 0 0 0 100rem rgba(42, 79, 104, 0.96) inset !important;
			-webkit-box-shadow: 0 0 0 100rem rgba(42, 79, 104, 0.96) inset !important;
			transition: background-color 9999s ease-in-out 0s !important;
		}
		input::placeholder { color: rgba(226, 232, 240, 0.55); }
		/* Keep old label markup but hide (we use placeholders + icons) */
		.group label, .bar { display: none !important; }

		/* Skin select */
		.bace-skin { margin-top: 6px; }
		.bace-skin select {
			padding: 10px 12px;
			border-radius: 14px;
			border: 1px solid rgba(255, 255, 255, 0.16);
			background: rgba(2, 6, 23, 0.22);
		}

		/* Button */
		.button {
			width: 100%;
			border: 0;
			border-radius: 1.5rem !important;
			padding: 1.35rem 1.3rem !important;
			font-size: 1.25rem !important;
			font-weight: 900;
			letter-spacing: 0.02em;
			color: #0b1220;
			cursor: pointer;
			background: linear-gradient(135deg, #facc15 0%, #22d3ee 100%);
			box-shadow: 0 18px 45px rgba(34, 211, 238, 0.18), 0 14px 40px rgba(250, 204, 21, 0.14);
			transition: transform 160ms ease, box-shadow 160ms ease, filter 160ms ease;
			overflow: hidden;
		}
		.button::before {
			content: "";
			position: absolute;
			inset: 0;
			transform: translateX(-120%);
			background: linear-gradient(120deg, transparent 0%, rgba(255,255,255,0.35) 35%, transparent 70%);
			transition: transform 520ms ease;
			pointer-events: none;
		}
		.button:hover {
			transform: translateY(-1px);
			filter: brightness(1.02);
			box-shadow: 0 22px 60px rgba(34, 211, 238, 0.22), 0 18px 55px rgba(250, 204, 21, 0.16);
		}
		.button:hover::before { transform: translateX(120%); }
		.button:active { transform: translateY(0); }

		.buttonBlue {
			animation: baceBtnGlow 2.8s ease-in-out infinite;
		}
		@keyframes baceBtnGlow {
			0%, 100% { box-shadow: 0 18px 45px rgba(34, 211, 238, 0.22), 0 14px 40px rgba(250, 204, 21, 0.18); }
			50% { box-shadow: 0 22px 60px rgba(34, 211, 238, 0.30), 0 18px 55px rgba(250, 204, 21, 0.22); }
		}

		/* Links */
		.bace-links {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-top: 10px;
			font-size: 13px;
		}
		.forgotPasswordLink {
			color: rgba(226, 232, 240, 0.85) !important;
			text-decoration: none !important;
			cursor: pointer;
		}
		.forgotPasswordLink:hover { color: #ffffff !important; text-decoration: underline !important; }

		/* Brand panel */
		.bace-kicker {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 6px 10px;
			border-radius: 999px;
			background: rgba(255, 255, 255, 0.08);
			border: 1px solid rgba(255, 255, 255, 0.14);
			color: rgba(226, 232, 240, 0.92);
			font-weight: 800;
			font-size: 12px;
			letter-spacing: 0.08em;
			text-transform: uppercase;
		}
		.bace-brand-title {
			margin: 14px 0 10px 0;
			font-size: 30px;
			line-height: 1.12;
			letter-spacing: -0.04em;
			font-weight: 950;
			color: rgba(255, 255, 255, 0.96);
		}
		.bace-brand-title .is-gold { color: #facc15; }
		.bace-brand-title .is-cyan { color: #22d3ee; }
		.bace-brand-sub {
			margin: 0 0 16px 0;
			color: rgba(226, 232, 240, 0.80);
			font-size: 14px;
			line-height: 1.55;
			max-width: 56ch;
		}
		.bace-values {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 12px;
			margin-top: 18px;
		}
		@media (max-width: 1100px) {
			.loginPageContainer { justify-content: center; }
			.bace-login-card { margin-top: 0; }
			.bace-info-slider { display: none; }
			.bace-mid-accent { display: none; }
		}
		.bace-value {
			border-radius: 18px;
			padding: 14px 14px;
			background: rgba(255, 255, 255, 0.09);
			border: 1px solid rgba(255, 255, 255, 0.16);
			transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease;
		}
		.bace-value:hover {
			transform: translateY(-2px);
			box-shadow: 0 16px 40px rgba(0,0,0,0.18);
			background: rgba(255,255,255,0.11);
		}
		.bace-value h4 {
			margin: 0 0 6px 0;
			font-size: 14px;
			font-weight: 950;
			letter-spacing: -0.02em;
			color: #facc15;
		}
		.bace-value p {
			margin: 0;
			font-size: 12.5px;
			line-height: 1.45;
			color: rgba(226, 232, 240, 0.78);
		}
		/* Ultra-short screens: allow internal scroll within login card only */
		@media (max-height: 640px) {
			html, body { overflow: hidden; }
			.loginPageContainer { align-items: flex-start; padding-top: 0.875rem; }
			.bace-card--login { max-height: calc(100vh - 28px); overflow: auto; }
		}

		/* Optional visual proof (enable temporarily if needed)
		.bace-login-card { outline: 0.125rem solid rgba(250,204,21,0.8); }
		.bace-info-slider { outline: 0.125rem solid rgba(34,211,238,0.8); }
		*/
	</style>

	<span class="app-nav"></span>
	<div class="container-fluid loginPageContainer">
		{* bace-info-slider moved inside .bace-shell for responsive layout *}
		<div class="bace-shell">
			<div class="bace-mid-accent" aria-hidden="true"></div>
			<div class="bace-card bace-card--login bace-login-card">
				<div class="bace-logo-tile">
					<img class="img-responsive user-logo" src="layouts/v7/skins/images/bace-login-logo-transparent.png?v=20260429-2" alt="B-ACE / TDB Solution">
				</div>

				<div class="bace-h1">Welcome back</div>
				<div class="bace-sub">Sign in to <strong>B-ACE</strong>.</div>

				<div>
					<span class="{if !$ERROR}hide{/if} failureMessage" id="validationMessage">{$MESSAGE}</span>
					<span class="{if !$MAIL_STATUS}hide{/if} successMessage">{$MESSAGE}</span>
				</div>

				<div id="loginFormDiv">
					<form class="form-horizontal" method="POST" action="index.php">
						<input type="hidden" name="module" value="Users"/>
						<input type="hidden" name="action" value="Login"/>

						<div class="group">
							<div class="bace-field">
								<span class="bace-ico"><i class="fa fa-user" aria-hidden="true"></i></span>
								<input id="username" type="text" name="username" placeholder="Username" autocomplete="username">
							</div>
							<span class="bar"></span>
							<label>Username</label>
						</div>

						<div class="group">
							<div class="bace-field">
								<span class="bace-ico"><i class="fa fa-lock" aria-hidden="true"></i></span>
								<input id="password" type="password" name="password" placeholder="Password" autocomplete="current-password">
							</div>
							<span class="bar"></span>
							<label>Password</label>
						</div>

						{assign var="CUSTOM_SKINS" value=Vtiger_Theme::getAllSkins()}
						{if !empty($CUSTOM_SKINS)}
						<div class="bace-skin">
							<select id="skin" name="skin" placeholder="Skin" style="text-transform: capitalize;">
								<option value="">Default Skin</option>
								{foreach item=CUSTOM_SKIN from=$CUSTOM_SKINS}
								<option value="{$CUSTOM_SKIN}">{$CUSTOM_SKIN}</option>
								{/foreach}
							</select>
						</div>
						{/if}

						<div class="group" style="margin-top: 14px;">
							<button type="submit" class="button buttonBlue">Sign in</button>
							<div class="bace-links">
								<a class="forgotPasswordLink">Forgot password?</a>
								<span style="color: rgba(226, 232, 240, 0.55); font-size: 12px;">B-ACE • TDB Solution</span>
							</div>
						</div>
					</form>
				</div>

				<div id="forgotPasswordDiv" class="hide">
					<form class="form-horizontal" action="forgotPassword.php" method="POST">
						<div class="group">
							<div class="bace-field">
								<span class="bace-ico"><i class="fa fa-user" aria-hidden="true"></i></span>
								<input id="fusername" type="text" name="username" placeholder="Username" autocomplete="username">
							</div>
							<span class="bar"></span>
							<label>Username</label>
						</div>

						<div class="group">
							<div class="bace-field">
								<span class="bace-ico"><i class="fa fa-envelope" aria-hidden="true"></i></span>
								<input id="email" type="email" name="emailId" placeholder="Email" autocomplete="email">
							</div>
							<span class="bar"></span>
							<label>Email</label>
						</div>

						<div class="group" style="margin-top: 14px;">
							<button type="submit" class="button buttonBlue forgot-submit-btn">Submit</button>
							<div class="bace-links">
								<span style="color: rgba(226, 232, 240, 0.75); font-size: 12.5px;">Please enter details and submit</span>
								<a class="forgotPasswordLink pull-right">Back</a>
							</div>
						</div>
					</form>
			</div>
		</div>

			{* Grid column 2: info slider (moved from absolute top-level for responsive layout) *}
			<div class="bace-info-slider" aria-label="B-ACE info slider">
				<div class="bace-slide active">
					<h3>TDB Solution</h3>
					<p>TDB Solution sáng tạo và đổi mới, chúng tôi mang đến khách hàng sự hài lòng nhờ đồng hành và cung cấp dịch vụ chất lượng cao.</p>
		</div>
				<div class="bace-slide">
					<h3>Tận tâm</h3>
					<p>Luôn đặt trải nghiệm và sự hài lòng của khách hàng lên hàng đầu.</p>
												</div>
				<div class="bace-slide">
					<h3>Tiên phong</h3>
					<p>Đổi mới liên tục, chuẩn hóa quy trình, nâng hiệu suất vận hành.</p>
													</div>
				<div class="bace-slide">
					<h3>Đồng hành</h3>
					<p>Phát triển bền vững cùng doanh nghiệp bằng giải pháp thực tế.</p>
													</div>
				<div class="bace-info-dots" aria-hidden="true">
					<span class="bace-info-dot is-active"></span>
					<span class="bace-info-dot"></span>
					<span class="bace-info-dot"></span>
					<span class="bace-info-dot"></span>
				</div>
			</div>

		</div>

		<script>
			jQuery(document).ready(function () {
				var validationMessage = jQuery('#validationMessage');
				var forgotPasswordDiv = jQuery('#forgotPasswordDiv');

				var loginFormDiv = jQuery('#loginFormDiv');
				loginFormDiv.find('#password').focus();

				// Top-right info slider (UI-only)
				try {
					var $slider = jQuery('.bace-info-slider');
					var $slides = $slider.find('.bace-slide');
					var $dots = $slider.find('.bace-info-dot');
					var idx = 0;
					var tickMs = 2500;
					var timer = null;

					var show = function (i) {
						idx = i;
						$slides.removeClass('active').eq(idx).addClass('active');
						$dots.removeClass('is-active').eq(idx).addClass('is-active');
					};
					var next = function () {
						if (!$slides.length) return;
						show((idx + 1) % $slides.length);
					};
					if ($slides.length > 1) {
						timer = setInterval(next, tickMs);
						$slider.on('mouseenter', function(){ if (timer) { clearInterval(timer); timer = null; } });
						$slider.on('mouseleave', function(){ if (!timer) timer = setInterval(next, tickMs); });
					}
				} catch (eSlider) {}

				loginFormDiv.find('a.forgotPasswordLink').click(function () {
					loginFormDiv.toggleClass('hide');
					forgotPasswordDiv.toggleClass('hide');
					validationMessage.addClass('hide');
				});

				forgotPasswordDiv.find('a.forgotPasswordLink').click(function () {
					loginFormDiv.toggleClass('hide');
					forgotPasswordDiv.toggleClass('hide');
					validationMessage.addClass('hide');
				});

				loginFormDiv.find('button').on('click', function () {
					var username = loginFormDiv.find('#username').val();
					var password = jQuery('#password').val();
					var result = true;
					var errorMessage = '';
					if (username === '') {
						errorMessage = 'Please enter valid username';
						result = false;
					} else if (password === '') {
						errorMessage = 'Please enter valid password';
						result = false;
					}
					if (errorMessage) {
						validationMessage.removeClass('hide').text(errorMessage);
					}
					return result;
				});

				forgotPasswordDiv.find('button').on('click', function () {
					var username = jQuery('#forgotPasswordDiv #fusername').val();
					var email = jQuery('#email').val();

					var email1 = email.replace(/^\s+/, '').replace(/\s+$/, '');
					var emailFilter = /^[^@]+@[^@.]+\.[^@]*\w\w$/;
					var illegalChars = /[\(\)\<\>\,\;\:\\\"\[\]]/;

					var result = true;
					var errorMessage = '';
					if (username === '') {
						errorMessage = 'Please enter valid username';
						result = false;
					} else if (!emailFilter.test(email1) || email == '') {
						errorMessage = 'Please enter valid email address';
						result = false;
					} else if (email.match(illegalChars)) {
						errorMessage = 'The email address contains illegal characters.';
						result = false;
					}
					if (errorMessage) {
						validationMessage.removeClass('hide').text(errorMessage);
					}
					return result;
				});
				jQuery('input').blur(function (e) {
					var currentElement = jQuery(e.currentTarget);
					if (currentElement.val()) {
						currentElement.addClass('used');
					} else {
						currentElement.removeClass('used');
					}
				});

				var ripples = jQuery('.ripples');
				ripples.on('click.Ripples', function (e) {
					jQuery(e.currentTarget).addClass('is-active');
				});

				ripples.on('animationend webkitAnimationEnd mozAnimationEnd oanimationend MSAnimationEnd', function (e) {
					jQuery(e.currentTarget).removeClass('is-active');
				});
				loginFormDiv.find('#username').focus();

				// Login page: marketing/news panel removed, so no slider/scroll init.
			});
		</script>
		</div>
	{/strip}
