/**
 * Settings list tables — single vertical scroll (page), horizontal scroll on .table-container only.
 */
(function ($) {
	"use strict";

	function isSettingsPage() {
		return document.body && document.body.getAttribute("data-parent") === "Settings";
	}

	function releaseInnerListScroll() {
		if (!isSettingsPage()) {
			return;
		}
		var $containers = $(
			"#table-content.table-container, .mk-settings-subpage .table-container, #listViewContent .table-container"
		);
		$containers.each(function () {
			var $el = $(this);
			if ($.fn.perfectScrollbar && $el.data("ps")) {
				try {
					$el.perfectScrollbar("destroy");
				} catch (e) {
					/* ignore */
				}
			}
			$el.css({
				height: "auto",
				maxHeight: "none",
				overflowX: "auto",
				overflowY: "visible",
			});
		});
	}

	function init() {
		if (!isSettingsPage()) {
			return;
		}
		releaseInnerListScroll();
		if (typeof app !== "undefined" && app.event) {
			app.event.off("post.listViewFilter.click.mkSettingsScroll");
			app.event.on("post.listViewFilter.click.mkSettingsScroll", function () {
				setTimeout(releaseInnerListScroll, 0);
			});
		}
		$(window).off("resize.mkSettingsScroll").on("resize.mkSettingsScroll", function () {
			releaseInnerListScroll();
		});
	}

	$(init);
})(jQuery);
