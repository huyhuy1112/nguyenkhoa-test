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
		body {
			background: url(layouts/v7/resources/Images/login-background.jpg);
			background-position: center;
			background-size: cover;
			width: 100%;
			background-repeat: no-repeat;
		}
		hr {
			margin-top: 15px;
			background-color: #7C7C7C;
			height: 2px;
			border-width: 0;
		}
		h3, h4 {
			margin-top: 0px;
		}
		hgroup {
			text-align:center;
			margin-top: 4em;
		}
		input {
			font-size: 16px;
			padding: 10px 10px 10px 0px;
			-webkit-appearance: none;
			display: block;
			color: #636363;
			width: 100%;
			border: none;
			border-radius: 0;
			border-bottom: 1px solid #757575;
		}
		input:focus {
			outline: none;
		}
		label {
			font-size: 16px;
			font-weight: normal;
			position: absolute;
			pointer-events: none;
			left: 0px;
			top: 10px;
			transition: all 0.2s ease;
		}
		input:focus ~ label, input.used ~ label {
			top: -20px;
			transform: scale(.75);
			left: -12px;
			font-size: 18px;
		}
		input:focus ~ .bar:before, input:focus ~ .bar:after {
			width: 50%;
		}
		select {
			font-size: 16px;
		}
		#page {
			padding-top: 86px;
		}
		.widgetHeight {
			height: 460px;
			margin-top: 20px !important;
		}
		.loginDiv {
			max-width: 380px;
			margin: 0 auto;
			border-radius: 4px;
			box-shadow: 0 0 10px gray;
			background-color: #FFFFFF;
		}
		.marketingDiv {
			color: #303030;
                        height: 510px !important;
		}
		.separatorDiv {
			background-color: #7C7C7C;
			width: 2px;
			height: 460px;
			margin-left: 20px;
		}
		.user-logo {
			max-height: 96px;
			height: auto;
			width: auto;
			margin: 0 auto;
			padding: 0;
			display: block;
		}
		/* Ensure background behind logo stays white (UI only) */
		.mk-login-logo-wrap {
			background: #ffffff;
			border-radius: 12px;
			padding-top: 26px;
			padding-bottom: 16px;
		}
		}
		.blockLink {
			border: 1px solid #303030;
			padding: 3px 5px;
		}
		.group {
			position: relative;
			margin: 20px 20px 40px;
		}
		.failureMessage {
			color: red;
			display: block;
			text-align: center;
			padding: 0px 0px 10px;
		}
		.successMessage {
			color: green;
			display: block;
			text-align: center;
			padding: 0px 0px 10px;
		}
		.inActiveImgDiv {
			padding: 5px;
			text-align: center;
			margin: 30px 0px;
		}
		.app-footer p {
			margin-top: 0px;
		}
		.footer {
			background-color: #fbfbfb;
			height:26px;
		}
		.bar {
			position: relative;
			display: block;
			width: 100%;
		}
		.bar:before, .bar:after {
			content: '';
			width: 0;
			bottom: 1px;
			position: absolute;
			height: 1px;
			background: #35aa47;
			transition: all 0.2s ease;
		}
		.bar:before {
			left: 50%;
		}
		.bar:after {
			right: 50%;
		}
		.button {
			position: relative;
			display: inline-block;
			padding: 9px;
			margin: .3em 0 1em 0;
			width: 100%;
			vertical-align: middle;
			color: #fff;
			font-size: 16px;
			line-height: 20px;
			-webkit-font-smoothing: antialiased;
			text-align: center;
			letter-spacing: 1px;
			background: transparent;
			border: 0;
			cursor: pointer;
			transition: all 0.15s ease;
		}
		.button:focus {
			outline: 0;
		}
		.buttonBlue {
			background-image: linear-gradient(to bottom, #35aa47 0px, #35aa47 100%)
		}
		.ripples {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			overflow: hidden;
			background: transparent;
		}
                .mCSB_container{
                    height: inherit;
                }

		//Animations
		@keyframes inputHighlighter {
			from {
				background: #4a89dc;
			}
			to 	{
				width: 0;
				background: transparent;
			}
		}
		@keyframes ripples {
			0% {
				opacity: 0;
			}
			25% {
				opacity: 1;
			}
			100% {
				width: 200%;
				padding-bottom: 200%;
				opacity: 0;
			}
		}

		/* TDB Solution - login slogan (animated) */
		.tdb-slogan {
			margin: 0;
			padding: 10px 12px;
			border-radius: 10px;
			background: rgba(53, 170, 71, 0.06);
			border: 1px solid rgba(53, 170, 71, 0.18);
			color: #1f2937;
		}
		/* When shown on the right panel, make it breathe */
		.tdb-slogan.tdb-slogan--panel {
			width: 100%;
			box-sizing: border-box;
			padding: 18px 18px;
			margin-top: 24px;
			background: transparent;
			backdrop-filter: none;
			border: 1px solid rgba(255,255,255,0.35);
			box-shadow: none;
			text-align: center;
		}
		.tdb-slogan.tdb-slogan--panel .tdb-line:nth-child(1) {
			font-size: 20px;
			line-height: 1.35;
		}
		.tdb-slogan.tdb-slogan--panel .tdb-line:nth-child(n+2) {
			font-size: 15px;
			line-height: 1.4;
		}
		.tdb-slogan .tdb-key {
			font-weight: 800;
			font-size: 16.5px;
			letter-spacing: 0.01em;
			color: #facc15; /* yellow */
		}
		.tdb-slogan .tdb-line {
			opacity: 0;
			transform: translateY(6px);
			animation: tdbFadeUp 600ms ease forwards;
		}
		.tdb-slogan .tdb-line:nth-child(1) { animation-delay: 120ms; font-weight: 600; }
		.tdb-slogan .tdb-line:nth-child(2) { animation-delay: 340ms; color: #0f172a; }
		.tdb-slogan .tdb-line:nth-child(3) { animation-delay: 520ms; color: #0f172a; }
		.tdb-slogan .tdb-line:nth-child(4) { animation-delay: 700ms; color: #0f172a; }
		.tdb-slogan .tdb-line + .tdb-line { margin-top: 6px; }
		@keyframes tdbFadeUp {
			from { opacity: 0; transform: translateY(6px); }
			to { opacity: 1; transform: translateY(0); }
		}
	</style>

	<span class="app-nav"></span>
	<div class="container-fluid loginPageContainer">
		<div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
			<div class="loginDiv widgetHeight">
				<div class="mk-login-logo-wrap">
					<img class="img-responsive user-logo" src="layouts/v7/skins/images/bace-login-logo.png">
				</div>
				<div>
					<span class="{if !$ERROR}hide{/if} failureMessage" id="validationMessage">{$MESSAGE}</span>
					<span class="{if !$MAIL_STATUS}hide{/if} successMessage">{$MESSAGE}</span>
				</div>

				<div id="loginFormDiv">
					<form class="form-horizontal" method="POST" action="index.php">
						<input type="hidden" name="module" value="Users"/>
						<input type="hidden" name="action" value="Login"/>
						<div class="group">
							<input id="username" type="text" name="username" placeholder="Username">
							<span class="bar"></span>
							<label>Username</label>
						</div>
						<div class="group">
							<input id="password" type="password" name="password" placeholder="Password">
							<span class="bar"></span>
							<label>Password</label>
						</div>
						{assign var="CUSTOM_SKINS" value=Vtiger_Theme::getAllSkins()}
						{if !empty($CUSTOM_SKINS)}
						<div class="group" style="margin-bottom: 10px;">
							<select id="skin" name="skin" placeholder="Skin" style="text-transform: capitalize; width:100%;height:30px;">
								<option value="">Default Skin</option>
								{foreach item=CUSTOM_SKIN from=$CUSTOM_SKINS}
								<option value="{$CUSTOM_SKIN}">{$CUSTOM_SKIN}</option>
								{/foreach}
							</select>
						</div>
						{/if}
					<div class="group">
							<button type="submit" class="button buttonBlue">Sign in</button><br>
							<a class="forgotPasswordLink" style="color: #15c;">forgot password?</a>
						</div>
					</form>
				</div>

				<div id="forgotPasswordDiv" class="hide">
					<form class="form-horizontal" action="forgotPassword.php" method="POST">
						<div class="group">
							<input id="fusername" type="text" name="username" placeholder="Username" >
							<span class="bar"></span>
							<label>Username</label>
						</div>
						<div class="group">
							<input id="email" type="email" name="emailId" placeholder="Email" >
							<span class="bar"></span>
							<label>Email</label>
						</div>
						<div class="group">
							<button type="submit" class="button buttonBlue forgot-submit-btn">Submit</button><br>
							<span>Please enter details and submit<a class="forgotPasswordLink pull-right" style="color: #15c;">Back</a></span>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-1 hidden-xs hidden-sm hidden-md">
			<div class="separatorDiv"></div>
		</div>

		{* Replace removed marketing/news panel with TDB slogan panel *}
		<div class="col-lg-5 hidden-xs hidden-sm hidden-md">
			<div class="marketingDiv widgetHeight" style="background: transparent;">
				<div class="tdb-slogan tdb-slogan--panel" aria-label="TDB Solution Slogan">
					<div class="tdb-line">TDB Solution sáng tạo và đổi mới, chúng tôi mang đến khách hàng sự hài lòng nhờ đồng hành và cung cấp dịch vụ chất lượng cao.</div>
					<div class="tdb-line"><span class="tdb-key">Tận tâm</span> phục vụ - <span class="tdb-key">Tiên phong</span> công nghệ</div>
					<div class="tdb-line"><span class="tdb-key">Đồng hành</span> bền vững - <span class="tdb-key">Đổi mới</span> không ngừng</div>
					<div class="tdb-line"><span class="tdb-key">Bền vững</span> chiến lược - <span class="tdb-key">Bản lĩnh</span> hành động</div>
				</div>
			</div>
		</div>

		<script>
			jQuery(document).ready(function () {
				var validationMessage = jQuery('#validationMessage');
				var forgotPasswordDiv = jQuery('#forgotPasswordDiv');

				var loginFormDiv = jQuery('#loginFormDiv');
				loginFormDiv.find('#password').focus();

				loginFormDiv.find('a').click(function () {
					loginFormDiv.toggleClass('hide');
					forgotPasswordDiv.toggleClass('hide');
					validationMessage.addClass('hide');
				});

				forgotPasswordDiv.find('a').click(function () {
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
