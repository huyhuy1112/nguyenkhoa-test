/**
 * Settings UI — sidebar search, subnav accordion, shortcut keyboard nav.
 */
(function ($) {
	"use strict";

	var MK_SETTINGS_UI_VER = "20260814_settings_ui2";

	function isSettingsPage() {
		return document.body && document.body.getAttribute("data-parent") === "Settings";
	}

	function registerSidebarSearch() {
		var $input = $("#settingsMenuSearch");
		if (!$input.length || !$.fn.instaFilta) {
			return;
		}
		if ($input.data("mkSettingsFilta")) {
			return;
		}
		$input.instaFilta({
			targets: ".menuItemLabel",
			sections: ".instaSearch",
			markMatches: true,
			onFilterComplete: function () {
				if ($input.val().length <= 0) {
					$("#mk-settings-subnav .collapse.in").removeClass("in");
					$("#mk-settings-subnav .mk-settings-subnav-group-toggle.is-open").removeClass("is-open");
					return;
				}
				$("#mk-settings-subnav")
					.find('[data-instafilta-hide="false"]')
					.closest(".collapse")
					.filter(":not(.in)")
					.addClass("in");
				$("#mk-settings-subnav")
					.find('[data-instafilta-hide="false"]')
					.closest(".mk-settings-subnav-group")
					.find(".mk-settings-subnav-group-toggle")
					.addClass("is-open");
			},
		});
		$input.data("mkSettingsFilta", true);
	}

	function registerSubnavAccordion() {
		var $root = $("#mk-settings-subnav");
		if (!$root.length) {
			return;
		}
		$root.on("shown.bs.collapse", ".collapse", function () {
			$(this)
				.prev(".mk-settings-subnav-group-toggle")
				.addClass("is-open")
				.attr("aria-expanded", "true");
		});
		$root.on("hidden.bs.collapse", ".collapse", function () {
			$(this)
				.prev(".mk-settings-subnav-group-toggle")
				.removeClass("is-open")
				.attr("aria-expanded", "false");
		});
	}

	function registerShortcutKeys() {
		$("#settingsShortCutsContainer").on("keydown", ".moduleBlock", function (e) {
			if (e.key === "Enter" || e.key === " ") {
				e.preventDefault();
				var url = $(this).data("url");
				if (url) {
					window.location.href = url;
				}
			}
		});
	}

	function registerStatCardHover() {
		$(".mk-settings-stat-card, .mk-settings-shortcut-card").each(function (i) {
			this.style.setProperty("--nk-set-delay", i * 50 + "ms");
		});
	}

	function init() {
		if (!isSettingsPage()) {
			return;
		}
		registerSidebarSearch();
		registerSubnavAccordion();
		registerShortcutKeys();
		registerStatCardHover();
	}

	$(init);

	window.MkSettingsUi = { version: MK_SETTINGS_UI_VER };
})(jQuery);
