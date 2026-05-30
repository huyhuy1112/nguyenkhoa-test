/*+**********************************************************************************
 * Calendar main view — Figma toolbar + mini calendar placement (UI only).
 *************************************************************************************/
(function ($) {
  "use strict";

  var VIEW_MAP = {
    day: "agendaDay",
    week: "agendaWeek",
    month: "month",
    agenda: "vtAgendaList",
  };

  var VIEW_REVERSE = {
    agendaDay: "day",
    agendaWeek: "week",
    month: "month",
    vtAgendaList: "agenda",
  };

  function isMkCalUi() {
    return document.documentElement.classList.contains("mk-cal-ui-ready");
  }

  function getMainCal() {
    return $("#mycalendar");
  }

  function formatTitle(momentDate) {
    if (!momentDate || !momentDate.isValid || !momentDate.isValid()) {
      return "";
    }
    var m = momentDate.month() + 1;
    var y = momentDate.year();
    return "Tháng " + m + " năm " + y;
  }

  function syncToolbarTitle(view) {
    var title = "";
    if (view && view.intervalStart) {
      title = formatTitle(moment(view.intervalStart));
    }
    if (!title) {
      var cal = getMainCal();
      if (cal.length && cal.fullCalendar) {
        var v = cal.fullCalendar("getView");
        if (v && v.intervalStart) {
          title = formatTitle(moment(v.intervalStart));
        }
      }
    }
    if (title) {
      $("#mk-cal-toolbar-title, #mk-cal-mini-title").text(title);
    }
  }

  function setActiveTab(viewName) {
    var key = VIEW_REVERSE[viewName] || "week";
    $(".mk-cal-view-tab")
      .removeClass("is-active")
      .attr("aria-selected", "false");
    $('.mk-cal-view-tab[data-view="' + key + '"]')
      .addClass("is-active")
      .attr("aria-selected", "true");
  }

  function moveMiniCalendar() {
    var $host = $("#mk-cal-mini-host");
    var $wrap = $("#calendar-mini-wrap");
    if (!$host.length || !$wrap.length) {
      return;
    }
    if ($wrap.closest("#mk-cal-mini-host").length) {
      return;
    }
    $wrap.appendTo($host);
    $wrap.find(".calendar-mini-label").hide();
  }

  function hideLegacySidebar() {
    Calendar_Calendar_Js.sideBarEssentialsState = "hidden";
    $(".sidebar-essentials").addClass("hide mk-cal-hide-legacy");
    $(".content-area").addClass("full-width");
    $(".essentials-toggle").addClass("hide mk-cal-hide-legacy");
  }

  function bindToolbar() {
    var $cal = getMainCal();
    if (!$cal.length || !$cal.fullCalendar) {
      return;
    }

    $("#mk-cal-today").on("click.mkCalUi", function () {
      $cal.fullCalendar("today");
    });
    $("#mk-cal-prev").on("click.mkCalUi", function () {
      $cal.fullCalendar("prev");
    });
    $("#mk-cal-next").on("click.mkCalUi", function () {
      $cal.fullCalendar("next");
    });

    $(".mk-cal-view-tab").on("click.mkCalUi", function () {
      var view = $(this).data("view");
      var fcView = VIEW_MAP[view];
      if (fcView) {
        $cal.fullCalendar("changeView", fcView);
      }
    });

    $("#mk-cal-picker").on("click.mkCalUi", function () {
      var $goto = $cal.find(".vt-goto-date");
      if ($goto.length) {
        $goto.trigger("click");
      }
    });

    $("#mk-cal-mini-prev").on("click.mkCalUi", function () {
      var $mini = $("#calendar-mini");
      if ($mini.length && $mini.fullCalendar) {
        $mini.fullCalendar("prev");
        syncMiniTitle();
      }
    });
    $("#mk-cal-mini-next").on("click.mkCalUi", function () {
      var $mini = $("#calendar-mini");
      if ($mini.length && $mini.fullCalendar) {
        $mini.fullCalendar("next");
        syncMiniTitle();
      }
    });

    $("#mk-cal-choose-year").on("click.mkCalUi", function () {
      var url = "index.php?module=Calendar&view=Year&app=MANAGEMENT";
      window.location.href = url;
    });

    $cal.on("viewRender.mkCalUi", function (view) {
      if (view && view.name) {
        setActiveTab(view.name);
      }
      syncToolbarTitle(view);
    });
  }

  function syncMiniTitle() {
    var $mini = $("#calendar-mini");
    if ($mini.length && $mini.fullCalendar) {
      var v = $mini.fullCalendar("getView");
      if (v && v.intervalStart) {
        var t = formatTitle(moment(v.intervalStart));
        if (t) {
          $("#mk-cal-mini-title").text(t);
        }
      }
    }
  }

  function resolveCalendarApp() {
    var params = new URLSearchParams(window.location.search);
    var app = (params.get("app") || "MANAGEMENT").toUpperCase();
    if (app === "SUPPORT") {
      app = "MANAGEMENT";
      params.set("app", app);
      var qs = params.toString();
      window.history.replaceState(
        null,
        "",
        window.location.pathname + (qs ? "?" + qs : "")
      );
    }
    return app;
  }

  function syncSidebarAppFromUrl() {
    var app = resolveCalendarApp();
    document.body.setAttribute("data-app", app);
    $(".mk-dash-app-group").removeClass("mk-dash-app-group--active");
    var $group = $('.mk-dash-app-group[data-mk-app="' + app + '"]');
    if ($group.length) {
      $group.addClass("mk-dash-app-group--active mk-dash-app-group--open");
    }
  }

  function bindSidebarActions() {
    var $sb = $("#mk-cal-sidebar");
    if (!$sb.length) {
      return;
    }
    $sb.off("click.mkCalSidebar");
    $sb.on("click.mkCalSidebar", "[data-mk-cal-action='event']", function (e) {
      e.preventDefault();
      if (window.Calendar_Calendar_Js && Calendar_Calendar_Js.showCreateEventModal) {
        Calendar_Calendar_Js.showCreateEventModal();
      }
    });
    $sb.on("click.mkCalSidebar", "[data-mk-cal-action='task']", function (e) {
      e.preventDefault();
      if (window.Calendar_Calendar_Js && Calendar_Calendar_Js.showCreateTaskModal) {
        Calendar_Calendar_Js.showCreateTaskModal();
      }
    });
    $sb.on("click.mkCalSidebar", "[data-mk-cal-action='settings']", function (e) {
      e.preventDefault();
      if (window.Calendar_Calendar_Js && Calendar_Calendar_Js.showCalendarSettings) {
        Calendar_Calendar_Js.showCalendarSettings();
      }
    });
  }

  function initMkCalUi() {
    if (!isMkCalUi()) {
      return;
    }
    syncSidebarAppFromUrl();
    bindSidebarActions();
    hideLegacySidebar();

    if ($(".calendar-yearview").length) {
      return;
    }

    moveMiniCalendar();

    var tries = 0;
    var timer = setInterval(function () {
      tries += 1;
      moveMiniCalendar();
      var $cal = getMainCal();
      if ($cal.length && $cal.hasClass("fc")) {
        clearInterval(timer);
        bindToolbar();
        var v = $cal.fullCalendar("getView");
        if (v) {
          setActiveTab(v.name);
          syncToolbarTitle(v);
        }
        syncMiniTitle();
        return;
      }
      if (tries > 40) {
        clearInterval(timer);
        bindToolbar();
      }
    }, 150);
  }

  $(function () {
    initMkCalUi();
  });

  app.event.on("post.CalendarView.load", function () {
    initMkCalUi();
  });
})(jQuery);
