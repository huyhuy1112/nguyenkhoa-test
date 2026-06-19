/* Leads list — b-ace.lovable.app/leads (localStorage cache, no DB) */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var logic = window.LeadsLeadsLogic;
  var ref = window.LeadsLovableRef;
  var store = window.LeadsLocalStore;
  var icons = window.LeadsMkIcons;

  function ic(name) {
    return icons && icons.get ? icons.get(name) : "";
  }

  var SOURCE_TAGS = ["facebook", "tiktok", "website", "zalo", "other"];
  var PROGRAM_TAGS = ["mien_phi_online", "mien_phi_offline", "pcth", "van_hanh", "mkt", "lop_khac", "nhuong_quyen"];
  var PURCHASE_TAGS = ["mua_lan_dau", "mua_lai", "khong_mua", "ngung_mua"];
  var TIER_TAGS = ["vang", "bac", "dong"];

  var PRESET_SEGMENTS = (ref && ref.PRESET_SEGMENTS) || [
    { id: "new", name: "Khách mới", filters: { purchase: "mua_lan_dau" } },
    { id: "gold", name: "Khách vàng", filters: { tier: "vang" } },
    { id: "repeat", name: "Khách mua lại", filters: { purchase: "mua_lai" } },
    { id: "nobuy", name: "Khách không mua", filters: { purchase: "khong_mua" } },
    { id: "chain", name: "Khách chuỗi (PCTH)", filters: { program: "pcth" } },
    { id: "franchise", name: "Khách nhượng quyền", filters: { program: "nhuong_quyen" } },
    { id: "cskh", name: "Khách cần CSKH", filters: { staleOnly: true } },
  ];

  var EMPTY = {
    search: "",
    source: ANY,
    program: ANY,
    purchase: ANY,
    tier: ANY,
    owner: ANY,
    area: ANY,
    segment: ANY,
    touchRange: "any",
    staleOnly: false,
    hasNextAction: false,
    hasOpenTicket: false,
  };

  var BULK_OWNERS = ["Hà", "Linh", "Minh"];
  var TAG_CAT_LABELS = {
    source: "SOURCE",
    learning: "LEARNING",
    program: "PROGRAM",
    purchase: "PURCHASE",
    tier: "TIER",
    other: "OTHER",
  };

  var state = {
    filters: Object.assign({}, EMPTY),
    sortKey: "last_touch",
    sortDir: "desc",
    page: 1,
    selected: {},
    activeSegment: null,
    filtersOpen: true,
    bulkMenu: null,
  };

  function $(id) {
    return document.getElementById(id);
  }

  function tagMeta(t) {
    return ref && ref.tagMeta ? ref.tagMeta(t) : { label: t, cls: "mk-tag" };
  }

  function esc(s) {
    return String(s || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/"/g, "&quot;");
  }

  function getLeads() {
    return store ? store.getLeads() : [];
  }

  function detailUrl(id) {
    return "index.php?module=Leads&view=Detail&record=" + encodeURIComponent(id) + "&app=SALES&mkLeadId=" + encodeURIComponent(id);
  }

  function inTouchWindow(iso, range) {
    if (range === "any") return true;
    var days = logic.daysSince(iso);
    var max = { "7d": 7, "30d": 30, "90d": 90 }[range];
    return days <= max;
  }

  function filterLeads(leads) {
    var f = state.filters;
    var q = f.search.trim().toLowerCase();
    return leads.filter(function (l) {
      var d = logic.derive(l);
      if (q) {
        var hay = [l.name, l.phone, l.email || "", l.companyName || "", l.area || ""].join(" ").toLowerCase();
        if (hay.indexOf(q) < 0) return false;
      }
      if (f.source !== ANY && (l.tags || []).indexOf(f.source) < 0) return false;
      if (f.program !== ANY && (l.tags || []).indexOf(f.program) < 0) return false;
      if (f.purchase !== ANY && (l.tags || []).indexOf(f.purchase) < 0) return false;
      if (f.tier !== ANY && (l.tags || []).indexOf(f.tier) < 0) return false;
      if (f.owner !== ANY && l.owner !== f.owner) return false;
      if (f.area !== ANY && (l.area || "") !== f.area) return false;
      if (f.segment !== ANY && (l.segment || "") !== f.segment) return false;
      if (!inTouchWindow(l.last_touch, f.touchRange)) return false;
      if (f.staleOnly && !d.stale) return false;
      if (f.hasNextAction && !(logic.deriveNextAction ? logic.deriveNextAction(l) : l.next_action || "").trim())
        return false;
      if (f.hasOpenTicket && !(l.openTickets > 0)) return false;
      return true;
    });
  }

  function sortLeads(list) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    return list.slice().sort(function (a, b) {
      var av, bv;
      if (key === "name") {
        av = a.name;
        bv = b.name;
      } else if (key === "value") {
        av = a.value || 0;
        bv = b.value || 0;
      } else {
        av = new Date(a.last_touch).getTime();
        bv = new Date(b.last_touch).getTime();
      }
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  function computeKpis(leads) {
    var todayStart = new Date();
    todayStart.setHours(0, 0, 0, 0);
    var newToday = 0;
    var qualified = 0;
    var repeat = 0;
    var gold = 0;
    var stale = 0;
    leads.forEach(function (l) {
      var tags = l.tags || [];
      var d = logic.derive(l);
      if (new Date(l.last_touch).getTime() >= todayStart.getTime()) newToday++;
      if (tags.indexOf("mua_lan_dau") >= 0 || tags.indexOf("mua_lai") >= 0) qualified++;
      if (tags.indexOf("mua_lai") >= 0) repeat++;
      if (tags.indexOf("vang") >= 0) gold++;
      if (d.stale) stale++;
    });
    var conv = leads.length ? Math.round((qualified / leads.length) * 100) : 0;
    return { total: leads.length, newToday: newToday, qualified: qualified, repeat: repeat, gold: gold, stale: stale, conv: conv };
  }

  function activeFilterCount() {
    var f = state.filters;
    var n = 0;
    if (f.source !== ANY) n++;
    if (f.program !== ANY) n++;
    if (f.purchase !== ANY) n++;
    if (f.tier !== ANY) n++;
    if (f.owner !== ANY) n++;
    if (f.area !== ANY) n++;
    if (f.segment !== ANY) n++;
    if (f.touchRange !== "any") n++;
    if (f.staleOnly) n++;
    if (f.hasNextAction) n++;
    if (f.hasOpenTicket) n++;
    return n;
  }

  function renderKpi() {
    var host = $("mk-leads-kpi");
    if (!host) return;
    var kpis = computeKpis(getLeads());
    var items = [
      { label: "Total Leads", value: kpis.total, trend: "+8%", up: true },
      { label: "New Today", value: kpis.newToday, trend: "+12%", up: true },
      { label: "Qualified", value: kpis.qualified, trend: "+5%", up: true },
      { label: "Repeat", value: kpis.repeat, trend: "+3%", up: true },
      { label: "Gold", value: kpis.gold, trend: "+2", up: true },
      { label: "Stale", value: kpis.stale, trend: "-4%", up: false },
      { label: "Conv. Rate", value: kpis.conv + "%", trend: "+1.4%", up: true },
    ];
    var kpiIcons = (icons && icons.KPI) || [];
    var kpiTones = (icons && icons.KPI_TONES) || ["blue", "violet", "emerald", "cyan", "amber", "rose", "indigo"];
    host.innerHTML = items
      .map(function (k, i) {
        var tone = kpiTones[i] || "blue";
        return (
          '<div class="mk-leads-kpi-card">' +
          '<div class="mk-leads-kpi-card__top">' +
          '<span class="mk-leads-kpi-card__label">' +
          '<span class="mk-leads-kpi-ic-wrap mk-leads-kpi-ic--' +
          tone +
          '">' +
          ic(kpiIcons[i] || "users") +
          "</span><span>" +
          esc(k.label) +
          "</span></span>" +
          '<span class="mk-leads-kpi-card__trend' +
          (k.up ? " is-up" : " is-down") +
          '">' +
          esc(k.trend) +
          "</span></div>" +
          '<div class="mk-leads-kpi-card__value">' +
          esc(k.value) +
          "</div></div>"
        );
      })
      .join("");
  }

  function renderSegments() {
    var host = $("mk-leads-segments");
    if (!host) return;
    var saved = store ? store.getSegments() : [];
    var html = PRESET_SEGMENTS.map(function (s) {
      var on = state.activeSegment === s.id ? " is-active" : "";
      return '<button type="button" class="mk-leads-segment-btn' + on + '" data-segment="' + esc(s.id) + '">' + esc(s.name) + "</button>";
    }).join("");
    html += saved
      .map(function (s) {
        var on = state.activeSegment === s.id ? " is-active" : "";
        return (
          '<span class="mk-leads-segment-chip' +
          on +
          '">' +
          '<button type="button" class="mk-leads-segment-chip__main" data-segment="' +
          esc(s.id) +
          '" data-custom="1">' +
          esc(s.name) +
          "</button>" +
          '<button type="button" class="mk-leads-segment-chip__del" data-del-segment="' +
          esc(s.id) +
          '" aria-label="Delete segment">' +
          ic("close") +
          "</button></span>"
        );
      })
      .join("");
    host.innerHTML = html;
  }

  function selectOptions(pairs) {
    return (
      '<option value="' +
      ANY +
      '">All</option>' +
      pairs
        .map(function (p) {
          return '<option value="' + esc(p[0]) + '">' + esc(p[1]) + "</option>";
        })
        .join("")
    );
  }

  function renderFiltersPanel() {
    var host = $("mk-leads-filters-panel");
    if (!host) return;
    var leads = getLeads();
    var owners = [];
    var areas = [];
    leads.forEach(function (l) {
      if (owners.indexOf(l.owner) < 0) owners.push(l.owner);
      if (l.area && areas.indexOf(l.area) < 0) areas.push(l.area);
    });
    owners.sort();
    areas.sort();
    var f = state.filters;
    host.innerHTML =
      '<div class="mk-leads-filters-grid">' +
      fieldSelect("Source", "source", f.source, SOURCE_TAGS.map(function (t) { return [t, tagMeta(t).label]; })) +
      fieldSelect("Program", "program", f.program, PROGRAM_TAGS.map(function (t) { return [t, tagMeta(t).label]; })) +
      fieldSelect("Purchase status", "purchase", f.purchase, PURCHASE_TAGS.map(function (t) { return [t, tagMeta(t).label]; })) +
      fieldSelect("Tier", "tier", f.tier, TIER_TAGS.map(function (t) { return [t, tagMeta(t).label]; })) +
      fieldSelect("Owner", "owner", f.owner, owners.map(function (o) { return [o, o]; })) +
      fieldSelect("Area", "area", f.area, areas.map(function (a) { return [a, a]; })) +
      fieldSelect("Customer Type", "segment", f.segment, Object.keys(logic.SEGMENT_LABELS).map(function (k) { return [k, logic.SEGMENT_LABELS[k]]; })) +
      fieldTouch("Last touch", "touchRange", f.touchRange) +
      toggleField("Stale only", "staleOnly", f.staleOnly, true) +
      toggleField("Has next action", "hasNextAction", f.hasNextAction, false) +
      toggleField("Has open ticket", "hasOpenTicket", f.hasOpenTicket, false) +
      "</div>";
    host.hidden = !state.filtersOpen;
  }

  function fieldSelect(label, key, val, pairs) {
    return (
      '<label class="mk-leads-filter-field"><span class="mk-leads-filter-field__label">' +
      esc(label) +
      '</span><select class="mk-leads-filter-field__select" data-fkey="' +
      key +
      '">' +
      selectOptions(pairs) +
      "</select></label>"
    );
  }

  function fieldTouch(label, key, val) {
    return (
      '<label class="mk-leads-filter-field"><span class="mk-leads-filter-field__label">' +
      esc(label) +
      '</span><select class="mk-leads-filter-field__select" data-fkey="' +
      key +
      '"><option value="any">Anytime</option><option value="7d">Last 7 days</option><option value="30d">Last 30 days</option><option value="90d">Last 90 days</option></select></label>'
    );
  }

  function toggleField(label, key, on, warn) {
    return (
      '<label class="mk-leads-toggle-field' +
      (warn ? " mk-leads-toggle-field--warn" : "") +
      '"><span class="mk-leads-toggle-field__label">' +
      (warn ? ic("alert") : "") +
      esc(label) +
      '</span><input type="checkbox" class="mk-leads-toggle-field__input" data-fkey="' +
      key +
      '"' +
      (on ? " checked" : "") +
      " /></label>"
    );
  }

  function syncFilterControls() {
    var f = state.filters;
    document.querySelectorAll("[data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (el.type === "checkbox") el.checked = !!f[key];
      else if (f[key] !== undefined) el.value = f[key];
    });
  }

  function tagBadgeHtml(tag) {
    var m = tagMeta(tag);
    return '<span class="mk-tag ' + m.cls + '">' + esc(m.label) + "</span>";
  }

  function renderTable() {
    var all = getLeads();
    var rows = sortLeads(filterLeads(all));
    var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if (state.page > totalPages) state.page = 1;
    var start = (state.page - 1) * PAGE_SIZE;
    var pageRows = rows.slice(start, start + PAGE_SIZE);
    var tbody = $("mk-leads-tbody");
    if (!tbody) return;

    if (!pageRows.length) {
      tbody.innerHTML = '<tr><td colspan="15" class="mk-leads-empty">No leads match these filters.</td></tr>';
    } else {
      tbody.innerHTML = pageRows
        .map(function (l) {
          var d = logic.derive(l);
          var src = (l.tags || []).find(function (t) {
            return SOURCE_TAGS.indexOf(t) >= 0;
          });
          var custLabel = l.segment ? logic.SEGMENT_LABELS[l.segment] : d.type;
          var nonSourceTags = (l.tags || []).filter(function (t) {
            return SOURCE_TAGS.indexOf(t) < 0;
          });
          var tags = nonSourceTags.slice(0, 3);
          var extra = nonSourceTags.length - tags.length;
          var checked = state.selected[l.id] ? " checked" : "";
          return (
            '<tr class="mk-leads-row' +
            (d.high ? " mk-leads-row--hot" : "") +
            (state.selected[l.id] ? " mk-leads-row--selected" : "") +
            '" data-id="' +
            esc(l.id) +
            '">' +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-leads-row-check" data-id="' +
            esc(l.id) +
            '"' +
            checked +
            ' /><span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td mk-leads-td--lead">' +
            '<span class="mk-leads-lead-cell">' +
            (d.high ? '<span class="mk-leads-fire" title="High priority">&#9832;</span>' : ic("user")) +
            '<span class="mk-leads-lead-text"><a class="mk-leads-name" href="' +
            detailUrl(l.id) +
            '">' +
            esc(l.name) +
            "</a>" +
            (l.companyName ? '<div class="mk-leads-sub">' + esc(l.companyName) + "</div>" : "") +
            "</span></span></td>" +
            '<td class="mk-leads-td">' +
            esc(l.phone) +
            "</td>" +
            '<td class="mk-leads-td">' +
            (l.area ? esc(l.area) : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td">' +
            (src ? tagBadgeHtml(src) : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td"><span class="mk-pill mk-pill--blue">' +
            esc(custLabel) +
            "</span></td>" +
            '<td class="mk-leads-td"><span class="mk-pill mk-pill--purple">' +
            esc(d.stage) +
            "</span></td>" +
            '<td class="mk-leads-td">' +
            (d.tier ? '<span class="mk-pill mk-pill--tier mk-pill--' + d.tier.toLowerCase() + '">' + esc(d.tier) + "</span>" : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--owner"><span class="mk-leads-owner-inner"><span class="mk-owner-avatar" style="background:' +
            logic.ownerColor(l.owner) +
            '">' +
            esc(logic.ownerInitials(l.owner)) +
            '</span><span>' +
            esc(l.owner) +
            "</span></span></td>" +
            '<td class="mk-leads-td mk-leads-td--tags"><div class="mk-leads-tags-stack">' +
            (tags.length ? tags.map(tagBadgeHtml).join("") : '<span class="mk-leads-muted">—</span>') +
            (extra > 0 ? '<span class="mk-leads-tag-more">+' + extra + "</span>" : "") +
            "</div></td>" +
            '<td class="mk-leads-td' +
            (d.stale ? " mk-leads-td--stale" : "") +
            '">' +
            logic.touchLabel(d.days) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--next">' +
            (function () {
              var next = logic.deriveNextAction ? logic.deriveNextAction(l) : l.next_action || "";
              return next ? esc(next) : '<span class="mk-leads-muted">—</span>';
            })() +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--right">' +
            logic.fmtVND(l.value) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--center mk-leads-td--support">' +
            (l.openTickets > 0
              ? '<span class="mk-pill mk-pill--support">' + ic("ticket") + l.openTickets + " open</span>"
              : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--center">' +
            (d.stale
              ? '<span class="mk-stale-pill"><span class="mk-dot mk-dot--stale"></span> Stale</span>'
              : '<span class="mk-dot mk-dot--ok"></span>') +
            "</td></tr>"
          );
        })
        .join("");
    }

    var summary = $("mk-leads-filter-summary");
    if (summary) summary.textContent = rows.length + " of " + all.length + " leads";

    var pag = $("mk-leads-pagination");
    if (pag) {
      var from = rows.length ? start + 1 : 0;
      var to = start + pageRows.length;
      pag.innerHTML =
        '<span class="mk-leads-pagination__info">Showing ' +
        from +
        "\u2013" +
        to +
        " of " +
        rows.length +
        "</span>" +
        '<div class="mk-leads-pagination__btns">' +
        '<button type="button" class="mk-leads-page-btn" id="mk-leads-prev"' +
        (state.page <= 1 ? " disabled" : "") +
        ">Prev</button>" +
        '<span class="mk-leads-page-num">' +
        state.page +
        " / " +
        totalPages +
        "</span>" +
        '<button type="button" class="mk-leads-page-btn" id="mk-leads-next"' +
        (state.page >= totalPages ? " disabled" : "") +
        ">Next</button></div>";
    }

    var badge = $("mk-leads-filter-count");
    var reset = $("mk-leads-reset");
    var n = activeFilterCount();
    if (badge) {
      badge.hidden = n === 0;
      badge.textContent = String(n);
    }
    if (reset) reset.hidden = n === 0;

    var checkAll = $("mk-leads-check-all");
    if (checkAll) {
      var allOnPage = pageRows.length > 0 && pageRows.every(function (l) {
        return !!state.selected[l.id];
      });
      checkAll.checked = allOnPage;
      checkAll.indeterminate = !allOnPage && pageRows.some(function (l) {
        return !!state.selected[l.id];
      });
    }
    syncSortHeaders();
    renderBulkBar();
  }

  function selectedCount() {
    return Object.keys(state.selected).length;
  }

  function selectedLeads() {
    var ids = Object.keys(state.selected);
    return getLeads().filter(function (l) {
      return state.selected[l.id];
    });
  }

  function clearSelection() {
    state.selected = {};
    state.bulkMenu = null;
    renderTable();
  }

  function getAllBulkTags() {
    var meta = ref && ref.TAG_META ? ref.TAG_META : {};
    return Object.keys(meta).map(function (k) {
      var m = meta[k];
      return { key: k, label: m.label, cat: m.cat, cls: m.cls };
    });
  }

  function getSelectedLeadTags() {
    var set = {};
    selectedLeads().forEach(function (l) {
      (l.tags || []).forEach(function (t) {
        set[t] = true;
      });
    });
    return Object.keys(set).sort();
  }

  function bulkMenuHtml(type) {
    if (state.bulkMenu !== type) return "";
    var html = '<div class="mk-leads-bulk-menu" role="menu">';
    if (type === "assign") {
      BULK_OWNERS.forEach(function (name) {
        html +=
          '<button type="button" class="mk-leads-bulk-menu__item" data-bulk-pick="owner" data-value="' +
          esc(name) +
          '">' +
          '<span class="mk-owner-avatar mk-leads-bulk-menu__avatar" style="background:' +
          logic.ownerColor(name) +
          '">' +
          esc(logic.ownerInitials(name)) +
          "</span>" +
          '<span class="mk-leads-bulk-menu__label">' +
          esc(name) +
          "</span></button>";
      });
    } else if (type === "add-tag") {
      getAllBulkTags().forEach(function (t) {
        html +=
          '<button type="button" class="mk-leads-bulk-menu__item" data-bulk-pick="add-tag" data-value="' +
          esc(t.key) +
          '">' +
          '<span class="mk-leads-bulk-dot ' +
          esc(t.cls) +
          '"></span>' +
          '<span class="mk-leads-bulk-menu__label">' +
          esc(t.label) +
          '</span><span class="mk-leads-bulk-menu__cat">' +
          esc(TAG_CAT_LABELS[t.cat] || String(t.cat || "").toUpperCase()) +
          "</span></button>";
      });
    } else if (type === "remove-tag") {
      var tags = getSelectedLeadTags();
      if (!tags.length) {
        html += '<div class="mk-leads-bulk-menu__empty">No tags on selected leads</div>';
      } else {
        tags.forEach(function (key) {
          var m = tagMeta(key);
          html +=
            '<button type="button" class="mk-leads-bulk-menu__item" data-bulk-pick="remove-tag" data-value="' +
            esc(key) +
            '">' +
            '<span class="mk-leads-bulk-dot ' +
            esc(m.cls) +
            '"></span>' +
            '<span class="mk-leads-bulk-menu__label">' +
            esc(m.label) +
            "</span></button>";
        });
      }
    }
    html += "</div>";
    return html;
  }

  function bulkActionWrap(action, label) {
    var open = state.bulkMenu === action;
    return (
      '<div class="mk-leads-bulk-action">' +
      '<button type="button" class="mk-leads-bulk-btn' +
      (open ? " is-open" : "") +
      '" data-bulk="' +
      action +
      '"><span>' +
      label +
      "</span></button>" +
      bulkMenuHtml(action) +
      "</div>"
    );
  }

  function applyBulkOwner(owner) {
    var rows = selectedLeads();
    Promise.all(
      rows.map(function (l) {
        return store.update(l.id, { owner: owner });
      }),
    ).then(function () {
      state.bulkMenu = null;
      renderAll();
    });
  }

  function applyBulkAddTag(tagKey) {
    var rows = selectedLeads();
    Promise.all(
      rows.map(function (l) {
        var tags = (l.tags || []).slice();
        if (tags.indexOf(tagKey) < 0) tags.push(tagKey);
        return store.update(l.id, { tags: tags });
      }),
    ).then(function () {
      state.bulkMenu = null;
      renderAll();
    });
  }

  function applyBulkRemoveTag(tagKey) {
    var rows = selectedLeads();
    Promise.all(
      rows.map(function (l) {
        var tags = (l.tags || []).filter(function (x) {
          return x !== tagKey;
        });
        return store.update(l.id, { tags: tags });
      }),
    ).then(function () {
      state.bulkMenu = null;
      renderAll();
    });
  }

  function renderBulkBar() {
    var bar = $("mk-leads-bulk");
    if (!bar) return;
    var n = selectedCount();
    if (!n) {
      bar.hidden = true;
      bar.innerHTML = "";
      state.bulkMenu = null;
      return;
    }
    bar.hidden = false;
    bar.innerHTML =
      '<div class="mk-leads-bulk-bar__inner">' +
      '<div class="mk-leads-bulk-bar__left">' +
      '<span class="mk-leads-bulk-badge" aria-hidden="true">' +
      ic("bulkCheck") +
      "</span>" +
      '<span class="mk-leads-bulk-bar__count"><strong>' +
      n +
      "</strong> selected</span>" +
      "</div>" +
      '<div class="mk-leads-bulk-bar__actions">' +
      bulkActionWrap("assign", "Assign owner") +
      bulkActionWrap("add-tag", "Add tag") +
      bulkActionWrap("remove-tag", "Remove tag") +
      '<button type="button" class="mk-leads-bulk-btn" data-bulk="export">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("export") +
      "</span><span>Export</span></button>" +
      '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--danger" data-bulk="archive">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("trash") +
      "</span><span>Archive</span></button>" +
      "</div>" +
      '<button type="button" class="mk-leads-bulk-clear" data-bulk="clear">Clear</button>' +
      "</div>";
  }

  function renderAll() {
    renderKpi();
    renderSegments();
    renderFiltersPanel();
    syncFilterControls();
    renderTable();
  }

  function applySegment(id, filters, isCustom) {
    state.filters = Object.assign({}, EMPTY, filters || {});
    state.activeSegment = id;
    state.page = 1;
    var search = $("mk-leads-search");
    if (search) search.value = state.filters.search;
    renderAll();
  }

  function exportCsv(rows) {
    var lines = ["Name,Phone,Area,Owner,Value,Tags"];
    rows.forEach(function (l) {
      lines.push(
        [
          l.name,
          l.phone,
          l.area || "",
          l.owner,
          l.value,
          (l.tags || []).join("|"),
        ]
          .map(function (v) {
            return '"' + String(v).replace(/"/g, '""') + '"';
          })
          .join(","),
      );
    });
    var blob = new Blob(["\uFEFF" + lines.join("\n")], { type: "text/csv;charset=utf-8" });
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "leads-" + new Date().toISOString().slice(0, 10) + ".csv";
    a.click();
    URL.revokeObjectURL(a.href);
  }

  function bindEvents() {
    var search = $("mk-leads-search");
    if (search) {
      search.addEventListener("input", function () {
        state.filters.search = search.value;
        state.activeSegment = null;
        state.page = 1;
        renderTable();
      });
    }

    $("mk-leads-filters-toggle") &&
      $("mk-leads-filters-toggle").addEventListener("click", function () {
        state.filtersOpen = !state.filtersOpen;
        this.setAttribute("aria-expanded", state.filtersOpen ? "true" : "false");
        var panel = $("mk-leads-filters-panel");
        if (panel) panel.hidden = !state.filtersOpen;
      });

    $("mk-leads-reset") &&
      $("mk-leads-reset").addEventListener("click", function () {
        state.filters = Object.assign({}, EMPTY);
        state.activeSegment = null;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      });

    document.addEventListener("change", function (e) {
      var t = e.target;
      if (!t || !t.getAttribute) return;
      var key = t.getAttribute("data-fkey");
      if (!key) return;
      if (t.type === "checkbox") state.filters[key] = t.checked;
      else state.filters[key] = t.value;
      state.activeSegment = null;
      state.page = 1;
      renderTable();
    });

    $("mk-leads-segments") &&
      $("mk-leads-segments").addEventListener("click", function (e) {
        var delBtn = e.target.closest("[data-del-segment]");
        if (delBtn) {
          e.preventDefault();
          e.stopPropagation();
          var delId = delBtn.getAttribute("data-del-segment");
          if (store && delId) {
            store
              .saveSegments(
                store.getSegments().filter(function (s) {
                  return s.id !== delId;
                }),
              )
              .then(function () {
                if (state.activeSegment === delId) {
                  state.activeSegment = null;
                }
                renderSegments();
              });
          }
          return;
        }
        var btn = e.target.closest("[data-segment]");
        if (!btn) return;
        var id = btn.getAttribute("data-segment");
        var custom = btn.getAttribute("data-custom");
        if (custom && store) {
          var seg = store.getSegments().find(function (s) { return s.id === id; });
          if (seg) applySegment(id, seg.filters, true);
          return;
        }
        var preset = PRESET_SEGMENTS.find(function (s) { return s.id === id; });
        if (preset) applySegment(id, preset.filters, false);
      });

    $("mk-leads-save-segment") &&
      $("mk-leads-save-segment").addEventListener("click", function () {
        var name = prompt("Segment name");
        if (!name || !store) return;
        var list = store.getSegments();
        list.push({ id: "seg_" + Date.now(), name: name, filters: Object.assign({}, state.filters) });
        store.saveSegments(list).then(function () {
          renderSegments();
        });
      });

    $("mk-leads-table") &&
      $("mk-leads-table").addEventListener("click", function (e) {
        var th = e.target.closest("[data-sort]");
        if (th) {
          var k = th.getAttribute("data-sort");
          if (state.sortKey === k) state.sortDir = state.sortDir === "asc" ? "desc" : "asc";
          else {
            state.sortKey = k;
            state.sortDir = "desc";
          }
          renderTable();
          return;
        }
        if (e.target.closest && e.target.closest(".mk-leads-td--check")) return;
        var tr = e.target.closest("tr[data-id]");
        if (tr && !e.target.closest("a")) {
          window.location.href = detailUrl(tr.getAttribute("data-id"));
        }
      });

    document.addEventListener("change", function (e) {
      if (e.target.classList.contains("mk-leads-row-check")) {
        var id = e.target.getAttribute("data-id");
        if (e.target.checked) state.selected[id] = true;
        else delete state.selected[id];
        renderTable();
      }
      if (e.target.id === "mk-leads-check-all") {
        var rows = sortLeads(filterLeads(getLeads())).slice((state.page - 1) * PAGE_SIZE, state.page * PAGE_SIZE);
        rows.forEach(function (l) {
          if (e.target.checked) state.selected[l.id] = true;
          else delete state.selected[l.id];
        });
        renderTable();
      }
    });

    document.addEventListener("click", function (e) {
      var pick = e.target.closest && e.target.closest("[data-bulk-pick]");
      if (pick) {
        e.preventDefault();
        e.stopPropagation();
        var pickType = pick.getAttribute("data-bulk-pick");
        var val = pick.getAttribute("data-value");
        if (!val || !store) return;
        if (pickType === "owner") applyBulkOwner(val);
        else if (pickType === "add-tag") applyBulkAddTag(val);
        else if (pickType === "remove-tag") applyBulkRemoveTag(val);
        return;
      }

      if (state.bulkMenu && !(e.target.closest && e.target.closest(".mk-leads-bulk-bar"))) {
        state.bulkMenu = null;
        renderBulkBar();
      }

      if (e.target.id === "mk-leads-prev") {
        state.page = Math.max(1, state.page - 1);
        renderTable();
      }
      if (e.target.id === "mk-leads-next") {
        state.page += 1;
        renderTable();
      }
      if (e.target.id === "mk-leads-export-btn") {
        var menu = $("mk-leads-export-menu");
        if (menu) menu.hidden = !menu.hidden;
      }
      var bulkBtn = e.target.closest && e.target.closest("[data-bulk]");
      if (bulkBtn) {
        e.preventDefault();
        e.stopPropagation();
        var action = bulkBtn.getAttribute("data-bulk");
        var rows = selectedLeads();
        if (!rows.length && action !== "clear") return;
        if (action === "clear") {
          clearSelection();
          return;
        }
        if (action === "export") {
          exportCsv(rows);
          return;
        }
        if (action === "archive") {
          if (!window.confirm("Archive " + rows.length + " selected lead(s)?")) return;
          Promise.all(
            rows.map(function (l) {
              return store.remove(l.id);
            }),
          ).then(function () {
            clearSelection();
            renderAll();
          });
          return;
        }
        if (action === "assign" || action === "add-tag" || action === "remove-tag") {
          state.bulkMenu = state.bulkMenu === action ? null : action;
          renderBulkBar();
          return;
        }
      }
      if (e.target.closest && e.target.closest("[data-export]")) {
        var kind = e.target.getAttribute("data-export");
        var rows = sortLeads(filterLeads(getLeads()));
        if (kind === "csv") exportCsv(rows);
        if (kind === "print") window.print();
        var menu = $("mk-leads-export-menu");
        if (menu) menu.hidden = true;
      }
    });
  }

  function decorateStaticIcons() {
    var map = {
      "mk-leads-segments-icon": "bookmark",
      "mk-leads-save-segment-ic": "save",
      "mk-leads-search-ic": "search",
      "mk-leads-filters-ic": "filter",
      "mk-leads-filters-chev": "chevron",
      "mk-leads-export-ic": "export",
      "mk-leads-create-ic": "plus",
    };
    Object.keys(map).forEach(function (id) {
      var el = $(id);
      if (el) el.innerHTML = ic(map[id]);
    });
    document.querySelectorAll(".mk-leads-sort-ic").forEach(function (el) {
      el.innerHTML = ic("sort");
    });
  }

  function syncSortHeaders() {
    document.querySelectorAll(".mk-leads-th--sort").forEach(function (th) {
      var key = th.getAttribute("data-sort");
      th.classList.toggle("is-sorted", state.sortKey === key);
      th.classList.toggle("is-asc", state.sortKey === key && state.sortDir === "asc");
      th.classList.toggle("is-desc", state.sortKey === key && state.sortDir === "desc");
    });
  }

  function init() {
    if (!logic || !store) return;
    var boot = store.ready ? store.ready() : Promise.resolve();
    boot
      .then(function () {
        decorateStaticIcons();
        bindEvents();
        renderAll();
        syncSortHeaders();
      })
      .catch(function (err) {
        console.error("Leads API bootstrap failed", err);
        decorateStaticIcons();
        bindEvents();
        renderAll();
        syncSortHeaders();
      });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
