/**
 * Dashboard-only: accordion app navigation + mobile drawer for mk-dashboard-sidebar.
 * Accordion JS toggles .mk-dash-app-group--open only (submenu visibility).
 * .mk-dash-app-group--active is set in DashboardSidebar.tpl (current app route); do not toggle in JS.
 */
(function ($) {
  "use strict";

  function closeMobileDrawer() {
    $("body").removeClass("mk-dash-drawer-open");
    $(".mk-dashboard-sidebar").removeClass("mk-dashboard-sidebar--drawer-open");
    $(".mk-dash-sidebar-mobile-toggle").attr("aria-expanded", "false");
  }

  function initAccordion() {
    var $nav = $(".mk-dash-sidebar-nav--accordion");
    if (!$nav.length) {
      return;
    }
    $nav.off("click.mkDashAcc").on("click.mkDashAcc", ".mk-dash-app-toggle", function (e) {
      e.preventDefault();
      var $btn = $(this);
      var $g = $btn.closest(".mk-dash-app-group");
      var isOpen = $g.hasClass("mk-dash-app-group--open");
      if (isOpen) {
        $g.removeClass("mk-dash-app-group--open");
        $btn.attr("aria-expanded", "false");
        return;
      }
      $nav.find(".mk-dash-app-group").removeClass("mk-dash-app-group--open");
      $nav.find(".mk-dash-app-toggle").attr("aria-expanded", "false");
      $g.addClass("mk-dash-app-group--open");
      $btn.attr("aria-expanded", "true");
    });

    // Nested module groups (e.g. Tuibao → Khách hàng / Hợp đồng nhượng quyền)
    $nav.off("click.mkDashModAcc").on("click.mkDashModAcc", ".mk-dash-mod-toggle", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $btn = $(this);
      var $g = $btn.closest(".mk-dash-mod-group");
      var isOpen = $g.hasClass("mk-dash-mod-group--open");
      $g.toggleClass("mk-dash-mod-group--open", !isOpen);
      $btn.attr("aria-expanded", !isOpen ? "true" : "false");
    });
  }

  function initMobileDrawer() {
    var $btn = $(".mk-dash-sidebar-mobile-toggle");
    var $backdrop = $(".mk-dash-drawer-backdrop");
    if (!$btn.length) {
      return;
    }
    $btn.off("click.mkDashDrawer").on("click.mkDashDrawer", function (e) {
      e.preventDefault();
      var willOpen = !$("body").hasClass("mk-dash-drawer-open");
      $("body").toggleClass("mk-dash-drawer-open", willOpen);
      $(".mk-dashboard-sidebar").toggleClass(
        "mk-dashboard-sidebar--drawer-open",
        willOpen
      );
      $btn.attr("aria-expanded", willOpen ? "true" : "false");
    });
    $backdrop.off("click.mkDashBackdrop").on("click.mkDashBackdrop", function () {
      if ($("body").hasClass("mk-dash-drawer-open")) {
        closeMobileDrawer();
      }
    });
    $(document).off("keydown.mkDashEsc").on("keydown.mkDashEsc", function (ev) {
      if (ev.keyCode === 27 && $("body").hasClass("mk-dash-drawer-open")) {
        closeMobileDrawer();
      }
    });
  }

  function init() {
    if (!$(".mk-dash-sidebar-nav--accordion").length) {
      return;
    }
    initAccordion();
    initMobileDrawer();
  }

  $(init);
})(jQuery);
