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

  var COLLAPSE_KEY = "mk_dash_sidebar_collapsed";
  var ANIM_MS = 420;
  var sidebarAnimating = false;
  var sidebarAnimTimer = null;

  function isDesktopSidebar() {
    return window.matchMedia && window.matchMedia("(min-width: 992px)").matches;
  }

  function prefersReducedMotion() {
    return window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function getSidebarOpenWidth() {
    var $sb = $(".mk-dashboard-sidebar").first();
    if ($sb.length) {
      var w = $sb[0].getBoundingClientRect().width;
      if (w > 40) return Math.round(w);
    }
    var raw = getComputedStyle(document.documentElement).getPropertyValue("--mk-dash-sidebar-w");
    var parsed = parseFloat(raw);
    return !isNaN(parsed) && parsed > 40 ? Math.round(parsed) : 256;
  }

  function shellNodes() {
    // Only the shell: topbar is position:fixed inside it; transforming
    // children too would double-offset and fight left: var(--mk-dash-sidebar-w).
    return Array.prototype.slice.call(document.querySelectorAll(".mk-app-shell"));
  }

  function clearShellTransforms(nodes) {
    nodes.forEach(function (el) {
      el.style.transition = "none";
      el.style.transform = "";
      el.style.willChange = "";
    });
  }

  function setSidebarCollapsed(on, opts) {
    opts = opts || {};
    var collapsed = !!on;
    var instant = !!opts.instant || !isDesktopSidebar() || prefersReducedMotion();
    var root = document.documentElement;
    var already = root.classList.contains("mk-dash-sidebar-collapsed");
    if (collapsed === already && !opts.force) {
      return;
    }
    if (sidebarAnimating && !instant) {
      return;
    }

    $(".mk-dash-sidebar-collapse-btn").attr("aria-expanded", collapsed ? "false" : "true");
    $(".mk-dash-sidebar-expand-fab").attr("aria-expanded", collapsed ? "true" : "false");
    try {
      window.localStorage.setItem(COLLAPSE_KEY, collapsed ? "1" : "0");
    } catch (e) {
      /* ignore */
    }

    if (instant) {
      if (sidebarAnimTimer) {
        window.clearTimeout(sidebarAnimTimer);
        sidebarAnimTimer = null;
      }
      sidebarAnimating = false;
      root.classList.remove("mk-dash-sidebar-animating");
      clearShellTransforms(shellNodes());
      root.classList.toggle("mk-dash-sidebar-collapsed", collapsed);
      return;
    }

    sidebarAnimating = true;
    var nodes = shellNodes();
    var openW = getSidebarOpenWidth();
    if (!collapsed) {
      // Sidebar is off-screen while collapsed — use stored open width.
      openW = parseFloat(
        getComputedStyle(document.documentElement).getPropertyValue("--mk-dash-sidebar-open-w")
      );
      if (isNaN(openW) || openW < 40) openW = 256;
    } else if (openW < 40) {
      openW = 256;
    }

    root.classList.add("mk-dash-sidebar-animating");
    nodes.forEach(function (el) {
      el.style.transition = "none";
      el.style.willChange = "transform";
    });

    if (collapsed) {
      root.classList.add("mk-dash-sidebar-collapsed");
      nodes.forEach(function (el) {
        el.style.transform = "translate3d(" + openW + "px, 0, 0)";
      });
      void document.body.offsetWidth;
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          nodes.forEach(function (el) {
            el.style.transition = "transform " + ANIM_MS + "ms cubic-bezier(0.25, 0.1, 0.25, 1)";
            el.style.transform = "translate3d(0, 0, 0)";
          });
        });
      });
    } else {
      root.classList.remove("mk-dash-sidebar-collapsed");
      nodes.forEach(function (el) {
        el.style.transform = "translate3d(" + -openW + "px, 0, 0)";
      });
      void document.body.offsetWidth;
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          nodes.forEach(function (el) {
            el.style.transition = "transform " + ANIM_MS + "ms cubic-bezier(0.25, 0.1, 0.25, 1)";
            el.style.transform = "translate3d(0, 0, 0)";
          });
        });
      });
    }

    if (sidebarAnimTimer) {
      window.clearTimeout(sidebarAnimTimer);
    }
    sidebarAnimTimer = window.setTimeout(function () {
      clearShellTransforms(nodes);
      root.classList.remove("mk-dash-sidebar-animating");
      sidebarAnimating = false;
      sidebarAnimTimer = null;
    }, ANIM_MS + 40);
  }

  function initDesktopCollapse() {
    var $collapse = $(".mk-dash-sidebar-collapse-btn");
    var $expand = $(".mk-dash-sidebar-expand-fab");
    if (!$collapse.length && !$expand.length) {
      return;
    }
    try {
      if (isDesktopSidebar() && window.localStorage.getItem(COLLAPSE_KEY) === "1") {
        setSidebarCollapsed(true, { instant: true });
      } else if (!isDesktopSidebar()) {
        document.documentElement.classList.remove("mk-dash-sidebar-collapsed");
      }
    } catch (e) {
      /* ignore */
    }
    $collapse.off("click.mkDashCollapse").on("click.mkDashCollapse", function (e) {
      e.preventDefault();
      if (!isDesktopSidebar()) {
        return;
      }
      setSidebarCollapsed(true);
    });
    $expand.off("click.mkDashExpand").on("click.mkDashExpand", function (e) {
      e.preventDefault();
      setSidebarCollapsed(false);
    });
    if (window.matchMedia) {
      var mq = window.matchMedia("(min-width: 992px)");
      var onChange = function () {
        if (!mq.matches) {
          document.documentElement.classList.remove("mk-dash-sidebar-collapsed", "mk-dash-sidebar-animating");
          clearShellTransforms(shellNodes());
          sidebarAnimating = false;
        } else {
          try {
            if (window.localStorage.getItem(COLLAPSE_KEY) === "1") {
              setSidebarCollapsed(true, { instant: true });
            }
          } catch (err) {
            /* ignore */
          }
        }
      };
      if (mq.addEventListener) {
        mq.addEventListener("change", onChange);
      } else if (mq.addListener) {
        mq.addListener(onChange);
      }
    }
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
        $btn.trigger("blur");
        return;
      }
      $nav.find(".mk-dash-app-group").removeClass("mk-dash-app-group--open");
      $nav.find(".mk-dash-app-toggle").attr("aria-expanded", "false");
      $g.addClass("mk-dash-app-group--open");
      $btn.attr("aria-expanded", "true");
      // Avoid stuck :focus white-on-transparent label after click
      $btn.trigger("blur");
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
      $btn.trigger("blur");
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
    initDesktopCollapse();
    // Settings: đóng mọi app group mặc định + đảm bảo chữ menu luôn hiện
    if (document.body && document.body.getAttribute("data-parent") === "Settings") {
      var $nav = $(".mk-dash-sidebar-nav--accordion");
      $nav.find(".mk-dash-app-group").removeClass("mk-dash-app-group--open");
      $nav.find(".mk-dash-app-toggle").attr("aria-expanded", "false");
      $nav.find(".mk-dash-app-toggle, .mk-dash-nav-item").each(function () {
        if (this.blur) this.blur();
      });
    }
  }

  $(init);
})(jQuery);
