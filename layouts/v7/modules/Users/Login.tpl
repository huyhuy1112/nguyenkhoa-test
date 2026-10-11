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
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"/>
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

	<style>
		/* ── Nguyên Khoa split login ── */
		.material-symbols-outlined {
			font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
			line-height: 1;
			vertical-align: middle;
		}

		html, body {
			height: 100%;
			margin: 0;
		}

		html:has(body[data-view="Login"]),
		body[data-view="Login"],
		html[data-theme="dark"] body[data-view="Login"] {
			background: #f8f9ff !important;
		}

		body[data-view="Login"] {
			margin: 0 !important;
			padding-top: 0 !important;
			min-height: 100vh;
			font-family: 'Inter', system-ui, sans-serif;
			color: #0b1c30;
			-webkit-font-smoothing: antialiased;
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
		body[data-view="Login"] .app-fixed-navbar,
		.app-footer, .footer, .mk-app-footer {
			display: none !important;
			height: 0 !important;
			min-height: 0 !important;
			padding: 0 !important;
			margin: 0 !important;
			border: 0 !important;
		}

		.loginPageContainer.nk-split-login {
			width: 100% !important;
			max-width: 100% !important;
			padding: 0 !important;
			margin: 0 !important;
			min-height: 100vh;
			height: 100vh;
			overflow: hidden;
		}

		.nk-split-main {
			display: flex;
			flex-direction: row;
			min-height: 100vh;
			width: 100%;
			overflow: hidden;
		}

		/* ── Left brand panel ── */
		.nk-split-brand {
			flex: 1 1 0;
			position: relative;
			display: none;
			align-items: center;
			justify-content: center;
			padding: 32px;
			background-color: #00341a;
			overflow: hidden;
		}
		.nk-split-brand::before {
			content: "";
			position: absolute;
			inset: 0;
			z-index: 0;
			pointer-events: none;
			background-color: #00341a;
			background-image: url(layouts/v7/resources/Images/nguyenkhoa-login-bg.png?v=20260703c);
			background-size: 100% auto;
			background-position: center center;
			background-repeat: no-repeat;
		}

		@media (min-width: 768px) {
			.nk-split-brand { display: flex; }
		}

		.nk-split-brand__inner {
			position: relative;
			z-index: 3;
			width: 100%;
			max-width: 58rem;
			display: flex;
			flex-direction: column;
			align-items: center;
			text-align: center;
		}

		.nk-split-brand__logo {
			margin-bottom: 20px;
			transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
		}
		.nk-split-brand__logo:hover { transform: scale(1.05); }
		.nk-split-brand__logo img {
			height: clamp(9.5rem, 15vh, 12rem);
			width: auto;
			object-fit: contain;
			filter: drop-shadow(0 20px 40px rgba(0,0,0,0.35));
		}

		.nk-split-brand__photo-wrap {
			position: relative;
			width: min(100%, 42rem, 62vw);
			max-width: 42rem;
			margin-bottom: 7rem;
			line-height: 0;
		}
		.nk-split-brand__photo-glow {
			position: absolute;
			inset: -28px -16px;
			border-radius: 28px;
			background: rgba(107, 254, 156, 0.2);
			filter: blur(52px);
			transition: background 0.2s ease;
			animation: nkGlowBreath 5.5s ease-in-out infinite;
		}
		.nk-split-brand__photo-wrap:hover .nk-split-brand__photo-glow {
			background: rgba(107, 254, 156, 0.3);
		}
		.nk-split-brand__photo-figure {
			position: relative;
			display: block;
			width: 100%;
		}
		.nk-split-brand__slideshow {
			position: relative;
			width: 100%;
		}
		.nk-split-brand__slideshow-sizer {
			display: block;
			width: 100%;
			height: auto;
			max-height: min(78vh, 760px);
			visibility: hidden;
			pointer-events: none;
		}
		.nk-split-brand__slide {
			position: absolute;
			inset: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			opacity: 0;
			visibility: hidden;
			transition: opacity 0.5s ease, visibility 0.5s ease;
			z-index: 0;
		}
		.nk-split-brand__slide.is-active {
			opacity: 1;
			visibility: visible;
			z-index: 1;
		}
		.nk-split-brand__slide img {
			display: block;
			width: 100%;
			height: 100%;
			max-width: 100%;
			max-height: 100%;
			object-fit: contain;
			object-position: center center;
			filter: drop-shadow(0 28px 56px rgba(0, 0, 0, 0.42));
			transition: transform 0.2s ease;
			transform: scale(1);
		}
		.nk-split-brand__slide.is-active img {
			animation: nkKenBurns 5.2s ease-out forwards;
		}
		/* Ảnh 1: căn giữa dọc (file có khoảng trống dưới nên trông hơi lệch lên) */
		.nk-split-brand__slide:first-of-type img {
			object-position: center 54%;
		}
		.nk-split-brand__photo-wrap:hover .nk-split-brand__slide.is-active img {
			/* Ken Burns đang chạy — hover chỉ tăng nhẹ shadow, không đè scale */
		}

		.nk-split-brand__copy {
			color: #fff;
			margin-top: 2rem !important;
			padding-top: 0.5rem;
		}
		.nk-split-brand__headline {
			margin: 0 0 16px;
			font-family: 'Manrope', sans-serif;
			font-size: clamp(2rem, 3.6vw, 2.75rem);
			font-weight: 700;
			line-height: 1.12;
			letter-spacing: -0.02em;
		}
		.nk-split-brand__headline span { color: #6bfe9c; }
		.nk-split-brand__desc {
			margin: 0 auto;
			max-width: 36rem;
			font-size: 1.125rem;
			line-height: 1.55;
			color: rgba(255,255,255,0.72);
		}

		.nk-split-brand__decor {
			position: absolute;
			inset: 0;
			pointer-events: none;
			z-index: 1;
		}
		.nk-split-brand__decor::before {
			content: "";
			position: absolute;
			top: 5rem;
			left: 5rem;
			width: 8rem;
			height: 8rem;
			border-radius: 50%;
			background: rgba(255,255,255,0.05);
			filter: blur(48px);
			animation: nkBlobFloatA 14s ease-in-out infinite;
		}
		.nk-split-brand__decor::after {
			content: "";
			position: absolute;
			bottom: 5rem;
			right: 5rem;
			width: 16rem;
			height: 16rem;
			border-radius: 50%;
			background: rgba(107, 254, 156, 0.05);
			filter: blur(100px);
			animation: nkBlobFloatB 18s ease-in-out infinite;
		}
		.nk-split-brand__blob {
			position: absolute;
			border-radius: 50%;
			pointer-events: none;
			filter: blur(64px);
			opacity: 0.55;
		}
		.nk-split-brand__blob--a {
			top: 18%;
			right: 12%;
			width: 11rem;
			height: 11rem;
			background: rgba(107, 254, 156, 0.18);
			animation: nkBlobFloatA 16s ease-in-out infinite reverse;
		}
		.nk-split-brand__blob--b {
			bottom: 22%;
			left: 8%;
			width: 14rem;
			height: 14rem;
			background: rgba(255, 255, 255, 0.08);
			animation: nkBlobFloatB 20s ease-in-out infinite;
		}

		/* ── Right form panel ── */
		.nk-split-form-panel {
			flex: 0 0 100%;
			width: 100%;
			display: flex;
			flex-direction: column;
			justify-content: center;
			padding: 32px 24px;
			background: #ffffff;
			overflow-y: auto;
			z-index: 10;
		}

		@media (min-width: 768px) {
			.nk-split-form-panel {
				flex: 0 0 500px;
				width: 500px;
				padding: 32px 24px;
			}
		}
		@media (min-width: 1024px) {
			.nk-split-form-panel {
				flex: 0 0 600px;
				width: 600px;
			}
		}

		.nk-split-form-inner {
			width: 100%;
			max-width: 380px;
			margin: 0 auto;
		}

		.nk-split-form-header { margin-bottom: 32px; }
		.nk-split-form-header h1 {
			margin: 0 0 4px;
			font-family: 'Manrope', sans-serif;
			font-size: 2.25rem;
			font-weight: 700;
			line-height: 1.15;
			letter-spacing: -0.02em;
			color: #0b1c30;
		}
		.nk-split-form-header p {
			margin: 0;
			font-size: 14px;
			line-height: 20px;
			font-weight: 500;
			color: #404941;
		}

		.nk-split-mobile-logo {
			display: flex;
			justify-content: center;
			margin-bottom: 24px;
		}
		@media (min-width: 768px) {
			.nk-split-mobile-logo { display: none; }
		}
		.nk-split-mobile-logo img {
			height: 4.5rem;
			width: auto;
		}

		.nk-field { margin-bottom: 24px; }
		.nk-field label {
			display: block;
			margin-bottom: 8px;
			font-size: 11px;
			font-weight: 600;
			letter-spacing: 0.12em;
			text-transform: uppercase;
			color: #404941;
		}
		.nk-field-input-wrap { position: relative; }
		.nk-field input[type="text"],
		.nk-field input[type="password"],
		.nk-field input[type="email"],
		.nk-field select {
			width: 100%;
			padding: 12px 0;
			border: 0;
			border-bottom: 2px solid #c0c9bf;
			border-radius: 0;
			background: transparent !important;
			color: #0b1c30 !important;
			font-family: 'Inter', sans-serif;
			font-size: 14px;
			line-height: 20px;
			outline: none;
			box-shadow: none !important;
			-webkit-appearance: none;
			transition: border-color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
		}
		.nk-field input:focus,
		.nk-field select:focus {
			border-bottom-color: #00341a;
			box-shadow: 0 6px 18px -10px rgba(0, 52, 26, 0.45) !important;
		}
		.nk-field input::placeholder { color: rgba(113, 121, 113, 0.55); }
		.nk-field input:-webkit-autofill,
		.nk-field input:-webkit-autofill:hover,
		.nk-field input:-webkit-autofill:focus {
			-webkit-text-fill-color: #0b1c30 !important;
			box-shadow: 0 0 0 100rem #ffffff inset !important;
			-webkit-box-shadow: 0 0 0 100rem #ffffff inset !important;
		}

		.nk-pw-toggle {
			position: absolute;
			right: 0;
			top: 50%;
			transform: translateY(-50%);
			border: 0;
			background: transparent;
			padding: 4px;
			color: #717971;
			cursor: pointer;
			line-height: 0;
			transition: color 0.2s ease;
		}
		.nk-pw-toggle:hover { color: #00341a; }
		.nk-pw-toggle .material-symbols-outlined { font-size: 20px; }

		.nk-form-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin: 8px 0 24px;
			gap: 12px;
		}
		.nk-remember {
			position: relative;
			display: inline-flex;
			cursor: pointer;
			user-select: none;
		}
		.nk-remember input[type="checkbox"] {
			appearance: none;
			-webkit-appearance: none;
			-moz-appearance: none;
			position: absolute;
			inset: 0;
			width: 100%;
			height: 100%;
			margin: 0;
			padding: 0;
			opacity: 0;
			z-index: 2;
			cursor: pointer;
		}
		.nk-remember__chip {
			display: inline-flex;
			align-items: center;
			gap: 7px;
			padding: 8px 16px 8px 12px;
			border-radius: 999px;
			border: 1.5px solid #d5ddd7;
			background: #f8fbf9;
			color: #4a544d;
			font-size: 13px;
			font-weight: 600;
			letter-spacing: 0.02em;
			transition: background 0.28s ease, border-color 0.28s ease, color 0.28s ease, box-shadow 0.28s ease, transform 0.2s ease;
			pointer-events: none;
		}
		.nk-remember__icon {
			font-size: 18px;
			line-height: 1;
			color: #8b968f;
			transition: color 0.28s ease, transform 0.28s cubic-bezier(0.34, 1.4, 0.64, 1);
		}
		.nk-remember:hover .nk-remember__chip {
			border-color: #b8c8bc;
			background: #f1f6f3;
			transform: translateY(-1px);
		}
		.nk-remember input:checked + .nk-remember__chip {
			background: linear-gradient(135deg, #00341a 0%, #0a5c32 100%);
			border-color: #00341a;
			color: #ffffff;
			box-shadow: 0 4px 16px rgba(0, 52, 26, 0.24);
		}
		.nk-remember input:checked + .nk-remember__chip .nk-remember__icon {
			color: #6bfe9c;
			font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
			transform: scale(1.1);
		}
		.nk-remember input:focus-visible + .nk-remember__chip {
			outline: 2px solid rgba(107, 254, 156, 0.8);
			outline-offset: 3px;
		}

		.forgotPasswordLink {
			font-size: 14px;
			font-weight: 600;
			color: #00341a !important;
			text-decoration: none !important;
			cursor: pointer;
			white-space: nowrap;
		}
		.forgotPasswordLink:hover { opacity: 0.8; }

		.nk-submit-btn {
			position: relative;
			overflow: hidden;
			width: 100%;
			padding: 16px;
			border: 0;
			border-radius: 12px;
			background: #00341a;
			color: #ffffff;
			font-family: 'Manrope', sans-serif;
			font-size: 20px;
			font-weight: 700;
			line-height: 28px;
			cursor: pointer;
			transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease;
		}
		.nk-submit-btn::after {
			content: "";
			position: absolute;
			top: 0;
			left: -120%;
			width: 60%;
			height: 100%;
			background: linear-gradient(105deg, transparent 0%, rgba(107, 254, 156, 0.28) 45%, transparent 100%);
			transform: skewX(-18deg);
			pointer-events: none;
		}
		.nk-submit-btn:hover {
			box-shadow: 0 16px 40px rgba(0, 52, 26, 0.25);
			transform: translateY(-2px);
		}
		.nk-submit-btn:hover::after {
			animation: nkBtnShimmer 0.85s ease;
		}
		.nk-submit-btn:active { transform: translateY(0); }

		.failureMessage, .successMessage {
			display: block;
			padding: 10px 12px;
			margin-bottom: 16px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 600;
		}
		.failureMessage {
			color: #93000a;
			background: #ffdad6;
			border: 1px solid #ffb4ab;
		}
		.successMessage {
			color: #0d4c2b;
			background: #b2f1c3;
			border: 1px solid #97d4a8;
		}

		.nk-skin-select {
			margin-bottom: 16px;
		}
		.nk-skin-select select {
			width: 100%;
			padding: 10px 0;
			border: 0;
			border-bottom: 2px solid #c0c9bf;
			background: transparent;
			font-size: 14px;
			color: #0b1c30;
		}

		.nk-forgot-back {
			display: inline-block;
			margin-top: 12px;
			font-size: 14px;
			font-weight: 600;
			color: #00341a;
			cursor: pointer;
		}

		.hide { display: none !important; }

		html[data-theme="dark"] body[data-view="Login"] .nk-split-form-panel,
		html[data-theme="dark"] body[data-view="Login"] .nk-split-form-panel input,
		html[data-theme="dark"] body[data-view="Login"] .nk-split-form-panel select {
			background: #ffffff !important;
			color: #0b1c30 !important;
		}

		/* ── Login motion (entrance + ambient) ── */
		@keyframes nkFadeUp {
			from { opacity: 0; transform: translateY(18px); }
			to { opacity: 1; transform: translateY(0); }
		}
		@keyframes nkFadeIn {
			from { opacity: 0; }
			to { opacity: 1; }
		}
		@keyframes nkGlowBreath {
			0%, 100% { opacity: 0.75; transform: scale(1); }
			50% { opacity: 1; transform: scale(1.06); }
		}
		@keyframes nkKenBurns {
			from { transform: scale(1); }
			to { transform: scale(1.045); }
		}
		@keyframes nkBlobFloatA {
			0%, 100% { transform: translate(0, 0); }
			50% { transform: translate(18px, -22px); }
		}
		@keyframes nkBlobFloatB {
			0%, 100% { transform: translate(0, 0); }
			50% { transform: translate(-24px, 16px); }
		}
		@keyframes nkBtnShimmer {
			from { left: -120%; }
			to { left: 140%; }
		}
		@keyframes nkAccentPulse {
			0%, 100% { opacity: 0.85; }
			50% { opacity: 1; text-shadow: 0 0 24px rgba(107, 254, 156, 0.35); }
		}

		.nk-split-brand__logo,
		.nk-split-brand__photo-wrap,
		.nk-split-brand__copy,
		.nk-split-mobile-logo,
		.nk-split-form-header,
		#loginFormDiv .nk-field,
		#loginFormDiv .nk-skin-select,
		#loginFormDiv .nk-form-row,
		#loginFormDiv .nk-submit-btn,
		.failureMessage:not(.hide),
		.successMessage:not(.hide) {
			opacity: 0;
		}

		body.nk-login-ready .nk-split-brand__logo {
			animation: nkFadeUp 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.05s both;
		}
		body.nk-login-ready .nk-split-brand__photo-wrap {
			animation: nkFadeUp 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.18s both;
		}
		body.nk-login-ready .nk-split-brand__copy {
			animation: nkFadeUp 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.32s both;
		}
		body.nk-login-ready .nk-split-brand__headline span {
			animation: nkAccentPulse 3.8s ease-in-out 1.1s infinite;
		}
		body.nk-login-ready .nk-split-mobile-logo {
			animation: nkFadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.05s both;
		}
		body.nk-login-ready .nk-split-form-header {
			animation: nkFadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.12s both;
		}
		body.nk-login-ready #loginFormDiv .nk-field:nth-of-type(1) {
			animation: nkFadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.22s both;
		}
		body.nk-login-ready #loginFormDiv .nk-field:nth-of-type(2) {
			animation: nkFadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.3s both;
		}
		body.nk-login-ready #loginFormDiv .nk-skin-select {
			animation: nkFadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.34s both;
		}
		body.nk-login-ready #loginFormDiv .nk-form-row {
			animation: nkFadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.38s both;
		}
		body.nk-login-ready #loginFormDiv .nk-submit-btn {
			animation: nkFadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.46s both;
		}
		body.nk-login-ready .failureMessage:not(.hide),
		body.nk-login-ready .successMessage:not(.hide) {
			animation: nkFadeIn 0.4s ease 0.2s both;
		}

		@media (prefers-reduced-motion: reduce) {
			.nk-split-brand__logo,
			.nk-split-brand__photo-wrap,
			.nk-split-brand__copy,
			.nk-split-mobile-logo,
			.nk-split-form-header,
			#loginFormDiv .nk-field,
			#loginFormDiv .nk-skin-select,
			#loginFormDiv .nk-form-row,
			#loginFormDiv .nk-submit-btn,
			.failureMessage:not(.hide),
			.successMessage:not(.hide) {
				opacity: 1 !important;
				animation: none !important;
			}
			.nk-split-brand__photo-glow,
			.nk-split-brand__decor::before,
			.nk-split-brand__decor::after,
			.nk-split-brand__blob,
			.nk-split-brand__slide.is-active img,
			body.nk-login-ready .nk-split-brand__headline span,
			.nk-submit-btn:hover::after {
				animation: none !important;
			}
		}
	</style>

	<span class="app-nav"></span>
	<div class="container-fluid loginPageContainer nk-split-login">
		<main class="nk-split-main">

			{* ── Left: brand visual ── *}
			<section class="nk-split-brand" aria-hidden="true">
				<div class="nk-split-brand__decor">
					<span class="nk-split-brand__blob nk-split-brand__blob--a" aria-hidden="true"></span>
					<span class="nk-split-brand__blob nk-split-brand__blob--b" aria-hidden="true"></span>
				</div>
				<div class="nk-split-brand__inner">
					<div class="nk-split-brand__logo">
						<img src="layouts/v7/resources/Images/nguyenkhoa-login-logo.png?v=20260703c" alt="Nguyên Khoa" width="320" height="128">
					</div>

					<div class="nk-split-brand__photo-wrap">
						<div class="nk-split-brand__photo-glow"></div>
						<div class="nk-split-brand__photo-figure">
							<div class="nk-split-brand__slideshow" data-interval="5200">
								<img class="nk-split-brand__slideshow-sizer" src="layouts/v7/resources/Images/nguyenkhoa-login-left.png?v=20260706b" alt="" width="471" height="530" aria-hidden="true">
								<div class="nk-split-brand__slide is-active">
									<img src="layouts/v7/resources/Images/nguyenkhoa-login-left.png?v=20260706b" alt="Nguyên Khoa" width="471" height="530">
								</div>
								<div class="nk-split-brand__slide">
									<img src="layouts/v7/resources/Images/nguyenkhoa-login-slide-2.png?v=20260706b" alt="Đào tạo Nguyên Khoa" width="1024" height="1024">
								</div>
								<div class="nk-split-brand__slide">
									<img src="layouts/v7/resources/Images/nguyenkhoa-login-slide-3.png?v=20260706b" alt="Demo sản phẩm Nguyên Khoa" width="576" height="1024">
								</div>
							</div>
						</div>
					</div>

					<div class="nk-split-brand__copy">
						<h2 class="nk-split-brand__headline">
							Giải pháp quản lý<br><span>khách hàng chuyên nghiệp</span>
						</h2>
						<p class="nk-split-brand__desc">
							Tối ưu hóa quy trình kinh doanh F&amp;B với hệ thống được thiết kế riêng cho sự tăng trưởng của thương hiệu Nguyên Khoa.
						</p>
					</div>
				</div>
			</section>

			{* ── Right: login form ── *}
			<section class="nk-split-form-panel">
				<div class="nk-split-form-inner">

					<div class="nk-split-mobile-logo">
						<img src="layouts/v7/resources/Images/nguyenkhoa-login-logo.png?v=20260703c" alt="Nguyên Khoa" width="200" height="80">
					</div>

					<div class="nk-split-form-header">
						<h1>Chào mừng</h1>
						<p>Đăng nhập để bắt đầu phiên làm việc</p>
					</div>

					<span class="{if !$ERROR}hide{/if} failureMessage" id="validationMessage">{$MESSAGE}</span>
					<span class="{if !$MAIL_STATUS}hide{/if} successMessage">{$MESSAGE}</span>

					<div id="loginFormDiv">
						<form class="nk-login-form" method="POST" action="index.php">
							<input type="hidden" name="module" value="Users"/>
							<input type="hidden" name="action" value="Login"/>

							<div class="nk-field">
								<label for="username">Email / Tài khoản</label>
								<div class="nk-field-input-wrap">
									<input id="username" type="text" name="username" placeholder="username@nguyenkhoa.vn" autocomplete="username" required>
								</div>
							</div>

							<div class="nk-field">
								<label for="password">Mật khẩu</label>
								<div class="nk-field-input-wrap">
									<input id="password" type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
									<button type="button" class="nk-pw-toggle" id="nkPwToggle" aria-label="Hiện mật khẩu">
										<span class="material-symbols-outlined" id="nkPwToggleIcon">visibility</span>
									</button>
								</div>
							</div>

							{assign var="CUSTOM_SKINS" value=Vtiger_Theme::getAllSkins()}
							{if !empty($CUSTOM_SKINS)}
							<div class="nk-skin-select">
								<label for="skin" style="display:block;margin-bottom:8px;font-size:11px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#404941;">Giao diện</label>
								<select id="skin" name="skin">
									<option value="">Mặc định</option>
									{foreach item=CUSTOM_SKIN from=$CUSTOM_SKINS}
									<option value="{$CUSTOM_SKIN}">{$CUSTOM_SKIN}</option>
									{/foreach}
								</select>
							</div>
							{/if}

							<div class="nk-form-row">
								<label class="nk-remember">
									<input type="checkbox" name="remember" value="1">
									<span class="nk-remember__chip" aria-hidden="true">
										<span class="material-symbols-outlined nk-remember__icon">bookmark</span>
										<span>Ghi nhớ</span>
									</span>
								</label>
								<a class="forgotPasswordLink">Quên mật khẩu?</a>
							</div>

							<button type="submit" class="nk-submit-btn">Tiếp tục</button>
						</form>
					</div>

					<div id="forgotPasswordDiv" class="hide">
						<form class="nk-login-form" action="forgotPassword.php" method="POST">
							<div class="nk-field">
								<label for="fusername">Tài khoản</label>
								<div class="nk-field-input-wrap">
									<input id="fusername" type="text" name="username" placeholder="Tên đăng nhập" autocomplete="username">
								</div>
							</div>

							<div class="nk-field">
								<label for="email">Email</label>
								<div class="nk-field-input-wrap">
									<input id="email" type="email" name="emailId" placeholder="email@nguyenkhoa.vn" autocomplete="email">
								</div>
							</div>

							<button type="submit" class="nk-submit-btn forgot-submit-btn">Gửi yêu cầu</button>
							<a class="forgotPasswordLink nk-forgot-back">← Quay lại đăng nhập</a>
						</form>
					</div>

				</div>
			</section>
		</main>

		<script>
			(function () {
				function markReady() {
					if (document.body) document.body.classList.add('nk-login-ready');
				}
				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', markReady);
				} else {
					requestAnimationFrame(markReady);
				}
				/* Fallback nếu animation class không kịp gắn */
				setTimeout(markReady, 1200);
			})();

			jQuery(document).ready(function () {
				var validationMessage = jQuery('#validationMessage');
				var forgotPasswordDiv = jQuery('#forgotPasswordDiv');
				var loginFormDiv = jQuery('#loginFormDiv');

				document.body.classList.add('nk-login-ready');

				loginFormDiv.find('#username').focus();

				(function () {
					var $slideshow = jQuery('.nk-split-brand__slideshow');
					if (!$slideshow.length) return;
					var $slides = $slideshow.find('.nk-split-brand__slide');
					if ($slides.length < 2) return;
					var idx = 0;
					var interval = parseInt($slideshow.data('interval'), 10) || 5200;
					setInterval(function () {
						$slides.removeClass('is-active');
						idx = (idx + 1) % $slides.length;
						var $next = $slides.eq(idx);
						$next.addClass('is-active');
						/* Restart Ken Burns on each slide */
						var img = $next.find('img')[0];
						if (img) {
							img.style.animation = 'none';
							void img.offsetWidth;
							img.style.animation = '';
						}
					}, interval);
				})();

				jQuery('#nkPwToggle').on('click', function () {
					var $pw = jQuery('#password');
					var $icon = jQuery('#nkPwToggleIcon');
					if ($pw.attr('type') === 'password') {
						$pw.attr('type', 'text');
						$icon.text('visibility_off');
					} else {
						$pw.attr('type', 'password');
						$icon.text('visibility');
					}
				});

				loginFormDiv.find('a.forgotPasswordLink').click(function () {
					loginFormDiv.addClass('hide');
					forgotPasswordDiv.removeClass('hide');
					validationMessage.addClass('hide');
				});

				forgotPasswordDiv.find('a.forgotPasswordLink').click(function () {
					forgotPasswordDiv.addClass('hide');
					loginFormDiv.removeClass('hide');
					validationMessage.addClass('hide');
				});

				loginFormDiv.find('button.nk-submit-btn').on('click', function () {
					var username = loginFormDiv.find('#username').val();
					var password = jQuery('#password').val();
					var result = true;
					var errorMessage = '';
					if (username === '') {
						errorMessage = 'Vui lòng nhập tên đăng nhập';
						result = false;
					} else if (password === '') {
						errorMessage = 'Vui lòng nhập mật khẩu';
						result = false;
					}
					if (errorMessage) {
						validationMessage.removeClass('hide').text(errorMessage);
					}
					return result;
				});

				forgotPasswordDiv.find('button.forgot-submit-btn').on('click', function () {
					var username = jQuery('#forgotPasswordDiv #fusername').val();
					var email = jQuery('#email').val();
					var email1 = email.replace(/^\s+/, '').replace(/\s+$/, '');
					var emailFilter = /^[^@]+@[^@.]+\.[^@]*\w\w$/;
					var illegalChars = /[\(\)\<\>\,\;\:\\\"\[\]]/;
					var result = true;
					var errorMessage = '';
					if (username === '') {
						errorMessage = 'Vui lòng nhập tên đăng nhập';
						result = false;
					} else if (!emailFilter.test(email1) || email === '') {
						errorMessage = 'Vui lòng nhập email hợp lệ';
						result = false;
					} else if (email.match(illegalChars)) {
						errorMessage = 'Email chứa ký tự không hợp lệ.';
						result = false;
					}
					if (errorMessage) {
						validationMessage.removeClass('hide').text(errorMessage);
					}
					return result;
				});
			});
		</script>
	</div>
{/strip}
