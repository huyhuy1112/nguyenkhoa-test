/* Contacts list — Lovable UI (same shell as Leads / Potentials) */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var ref = window.ContactsLovableRef;
  var store = window.ContactsLocalStore;
  var icons = window.LeadsMkIcons;
  var COL_COUNT = 10;

  function t(key, fallback) {
    if (typeof app !== "undefined" && app.vtranslate) {
      var translated = app.vtranslate(key);
      if (translated && translated !== key) return translated;
    }
    return fallback || key;
  }

  function pick(vi, en) {
    return ref && ref.pickLabel ? ref.pickLabel(vi, en) : vi;
  }

  /** Phân nhóm theo tag BA của Khách hàng — UI giống Leads (segment-btn) */
  function getPresetSegments() {
    return [
      { id: "tagged", name: pick("Có tag", "Has tag"), filters: { hasTag: true } },
      { id: "new_cust", name: pick("CH - Mới quen", "New contact"), filters: { customerRank: "moi_quen" } },
      { id: "related", name: pick("Đã có quan hệ", "Has relationship"), filters: { customerRank: "da_co_quan_he" } },
      { id: "first_buy", name: pick("Mua lần đầu", "First purchase"), filters: { material: "mua_lan_dau" } },
      { id: "franchise", name: pick("Nhượng quyền", "Franchise"), filters: { franchise: "nhuong_quyen" } },
      { id: "deposit", name: pick("Đã ký quỹ", "Deposited"), filters: { franchise: "da_ky_quy" } },
      { id: "gold", name: pick("Hạng Vàng", "Gold tier"), filters: { tier: "vang" } },
    ];
  }

  var EMPTY = {
    search: "",
    customerRank: ANY,
    classTag: ANY,
    material: ANY,
    franchise: ANY,
    tier: ANY,
    anyTag: ANY,
    owner: ANY,
    hasTag: false,
    hasAccount: false,
    staleOnly: false,
  };

  var state = {
    filters: Object.assign({}, EMPTY),
    sortKey: "last_touch",
    sortDir: "desc",
    page: 1,
    filtersOpen: true,
    activeSegment: null,
  };

  function $(id) {
    return document.getElementById(id);
  }

  function ic(name) {
    return icons && icons.get ? icons.get(name) : "";
  }

  function tagMeta(tg) {
    return ref && ref.tagMeta ? ref.tagMeta(tg) : { label: tg, cls: "mk-tag" };
  }

  function categorize(tags) {
    return ref && ref.categorizeTags ? ref.categorizeTags(tags || []) : {};
  }

  function decodeHtml(s) {
    var str = String(s == null ? "" : s);
    if (!str || str.indexOf("&") < 0) return str;
    var el = document.createElement("textarea");
    el.innerHTML = str;
    return el.value;
  }

  function esc(s) {
    return decodeHtml(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function getContacts() {
    return store ? store.getContacts() : [];
  }

  function detailUrl(id) {
    return "index.php?module=Contacts&view=Detail&record=" + encodeURIComponent(id) + "&app=SALES";
  }

  function ownerInitials(name) {
    var parts = String(name || "").trim().split(/\s+/);
    if (!parts[0]) return "?";
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
  }

  function ownerColor(name) {
    var h = 0;
    var s = String(name || "");
    for (var i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) % 360;
    return "hsl(" + h + ", 52%, 42%)";
  }

  function isStale(row) {
    var iso = row.last_touch || row.modifiedtime;
    if (!iso) return false;
    var tms = new Date(iso).getTime();
    if (isNaN(tms)) return false;
    return Math.floor((Date.now() - tms) / 86400000) >= 7;
  }

  function hasNormalizedTag(tags, key) {
    if (!tags || !tags.length || !key || !ref) return false;
    for (var i = 0; i < tags.length; i++) {
      if (ref.normalizeTag(tags[i]) === key) return true;
    }
    return false;
  }

  function filterContacts(rows) {
    var f = state.filters;
    var q = (f.search || "").toLowerCase().trim();
    return rows.filter(function (c) {
      var cats = categorize(c.tags);
      if (q) {
        var hay = [c.name, c.title, c.account, c.email, c.phone, c.owner, (c.tags || []).join(" ")]
          .join(" ")
          .toLowerCase();
        if (hay.indexOf(q) < 0) return false;
      }
      if (f.hasTag && !(c.tags || []).length) return false;
      if (f.hasAccount && !c.account) return false;
      if (f.customerRank !== ANY && (!cats.customerRank || ref.normalizeTag(cats.customerRank) !== f.customerRank)) return false;
      if (f.classTag !== ANY && (!cats.classTag || ref.normalizeTag(cats.classTag) !== f.classTag)) return false;
      if (f.material !== ANY && (!cats.material || ref.normalizeTag(cats.material) !== f.material)) return false;
      if (f.franchise !== ANY && (!cats.franchise || ref.normalizeTag(cats.franchise) !== f.franchise)) return false;
      if (f.tier !== ANY && (!cats.tier || ref.normalizeTag(cats.tier) !== f.tier)) return false;
      if (f.anyTag !== ANY && !hasNormalizedTag(c.tags, f.anyTag)) return false;
      if (f.staleOnly && !isStale(c)) return false;
      if (f.owner !== ANY && c.owner !== f.owner) return false;
      return true;
    });
  }

  function sortContacts(rows) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var av = a[key];
      var bv = b[key];
      av = String(av || "").toLowerCase();
      bv = String(bv || "").toLowerCase();
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  function computeKpis(rows) {
    var withTags = rows.filter(function (c) { return (c.tags || []).length > 0; }).length;
    var withEmail = rows.filter(function (c) { return !!c.email; }).length;
    var withPhone = rows.filter(function (c) { return !!c.phone; }).length;
    var withAccount = rows.filter(function (c) { return !!c.account; }).length;
    var gold = rows.filter(function (c) {
      var tg = categorize(c.tags).tier;
      return tg && ref.normalizeTag(tg) === "vang";
    }).length;
    var franchise = rows.filter(function (c) { return categorize(c.tags).franchise; }).length;
    return [
      { key: "total", label: t("JS_MK_KPI_TOTAL_CONTACT", "Tổng khách hàng"), value: rows.length, icon: "users", tone: "blue" },
      { key: "tagged", label: t("JS_MK_KPI_TAGGED", "Có tag"), value: withTags, icon: "crown", tone: "violet" },
      { key: "email", label: t("JS_MK_KPI_EMAIL", "Có email"), value: withEmail, icon: "check", tone: "emerald" },
      { key: "phone", label: t("JS_MK_KPI_PHONE", "Có SĐT"), value: withPhone, icon: "clock", tone: "cyan" },
      { key: "account", label: t("JS_MK_KPI_ACCOUNT", "Có tổ chức"), value: withAccount, icon: "repeat", tone: "amber" },
      { key: "gold", label: t("JS_MK_KPI_GOLD", "Hạng Vàng"), value: gold, icon: "crown", tone: "rose" },
      { key: "franchise", label: t("JS_MK_KPI_FRANCHISE", "Nhượng quyền"), value: franchise, icon: "trend", tone: "indigo" },
    ];
  }

  function renderKpi(rows) {
    var host = $("mk-contacts-kpi");
    if (!host) return;
    host.innerHTML = computeKpis(rows)
      .map(function (k) {
        return (
          '<article class="mk-leads-kpi-card mk-contacts-kpi-card" data-kpi="' + esc(k.key) + '">' +
          '<div class="mk-leads-kpi-card__top">' +
          '<span class="mk-leads-kpi-card__label">' +
          '<span class="mk-leads-kpi-ic-wrap mk-leads-kpi-ic--' + esc(k.tone) + '">' +
          ic(k.icon) +
          "</span>" +
          esc(k.label) +
          "</span></div>" +
          '<div class="mk-leads-kpi-card__value">' + esc(String(k.value)) + "</div>" +
          "</article>"
        );
      })
      .join("");
  }

  function renderSegments() {
    var host = $("mk-contacts-segments");
    if (!host) return;
    var allOn = !state.activeSegment ? " is-active" : "";
    var html =
      '<button type="button" class="mk-leads-segment-btn' +
      allOn +
      '" data-seg="__all__">' +
      esc(t("JS_MK_FILTER_ALL", "Tất cả")) +
      "</button>";
    html += getPresetSegments()
      .map(function (seg) {
        var active = state.activeSegment === seg.id ? " is-active" : "";
        return (
          '<button type="button" class="mk-leads-segment-btn' +
          active +
          '" data-seg="' +
          esc(seg.id) +
          '">' +
          esc(seg.name) +
          "</button>"
        );
      })
      .join("");
    host.innerHTML = html;
  }

  function selectOptions(pairs) {
    return (
      '<option value="' + ANY + '">' + esc(t("JS_MK_FILTER_ALL", "Tất cả")) + "</option>" +
      pairs.map(function (p) {
        return '<option value="' + esc(p[0]) + '">' + esc(p[1]) + "</option>";
      }).join("")
    );
  }

  function renderFiltersPanel() {
    var host = $("mk-contacts-filters-panel");
    if (!host || !ref) return;
    var rows = getContacts();
    var owners = [];
    rows.forEach(function (c) {
      if (c.owner && owners.indexOf(c.owner) < 0) owners.push(c.owner);
    });
    owners.sort();
    host.innerHTML =
      '<div class="mk-leads-filters-grid">' +
      fieldSelect(t("JS_MK_FILTER_TIER", "Hạng khách hàng"), "tier", ref.TIER_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CUSTOMER_RANK", "Phân nhóm"), "customerRank", ref.CUSTOMER_RANK_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CLASS", "Tag lớp học"), "classTag", ref.CLASS_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_MATERIAL", "Tag nguyên liệu"), "material", ref.MATERIAL_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_FRANCHISE", "Tag nhượng quyền"), "franchise", ref.FRANCHISE_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_OWNER", "Phụ trách"), "owner", owners.map(function (o) { return [o, o]; })) +
      "</div>";
    host.hidden = !state.filtersOpen;
    syncFilterControls();
  }

  function fieldSelect(label, key, pairs) {
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

  function syncFilterControls() {
    var f = state.filters;
    document.querySelectorAll("#mk-contacts-filters-panel [data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (f[key] !== undefined) el.value = f[key];
    });
  }

  function tagBadgeHtml(tag) {
    if (!tag) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(tag);
    return '<span class="mk-tag ' + m.cls + '">' + esc(m.label) + "</span>";
  }

  function tierPill(tags) {
    var tier = categorize(tags).tier;
    if (!tier) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(tier);
    var slug = ref.normalizeTag(tier);
    var cls =
      slug === "vang"
        ? "mk-pill--tier mk-pill--gold"
        : slug === "bac"
          ? "mk-pill--tier mk-pill--silver"
          : "mk-pill--tier mk-pill--bronze";
    return '<span class="mk-pill ' + cls + '">' + esc(m.label) + "</span>";
  }

  function renderTable() {
    var all = getContacts();
    var rows = sortContacts(filterContacts(all));
    var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if (state.page > totalPages) state.page = 1;
    var start = (state.page - 1) * PAGE_SIZE;
    var pageRows = rows.slice(start, start + PAGE_SIZE);
    var tbody = $("mk-contacts-tbody");
    if (!tbody) return;

    if (!pageRows.length) {
      tbody.innerHTML =
        '<tr><td colspan="' +
        COL_COUNT +
        '" class="mk-leads-empty">' +
        esc(t("JS_MK_NO_CONTACTS_MATCH", "Không có khách hàng phù hợp bộ lọc.")) +
        "</td></tr>";
    } else {
      tbody.innerHTML = pageRows
        .map(function (c) {
          var cats = categorize(c.tags);
          var crmId = c.crmid != null && c.crmid !== "" ? String(c.crmid) : String(c.id || "");
          return (
            '<tr class="mk-leads-row mk-contacts-row" data-id="' +
            esc(c.id) +
            '"' +
            (crmId && /^\d+$/.test(crmId) ? ' data-crmid="' + esc(crmId) + '"' : "") +
            ">" +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-contacts-row-check" data-id="' + esc(c.id) + '" />' +
            '<span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td mk-leads-td--lead"><span class="mk-leads-lead-cell">' +
            ic("user") +
            '<span class="mk-leads-lead-text"><a class="mk-leads-name" href="' + detailUrl(c.crmid || c.id) + '">' + esc(c.name) + "</a>" +
            (c.title ? '<div class="mk-leads-sub">' + esc(c.title) + "</div>" : "") +
            "</span></span></td>" +
            '<td class="mk-leads-td">' + (c.phone ? esc(c.phone) : '<span class="mk-leads-muted">—</span>') + "</td>" +
            '<td class="mk-leads-td">' + (c.account ? '<span class="mk-pill mk-pill--blue">' + esc(c.account) + "</span>" : '<span class="mk-leads-muted">—</span>') + "</td>" +
            '<td class="mk-leads-td">' + tierPill(c.tags) + "</td>" +
            '<td class="mk-leads-td">' + tagBadgeHtml(cats.customerRank) + "</td>" +
            '<td class="mk-leads-td">' + tagBadgeHtml(cats.classTag) + "</td>" +
            '<td class="mk-leads-td">' + tagBadgeHtml(cats.material) + "</td>" +
            '<td class="mk-leads-td">' + tagBadgeHtml(cats.franchise) + "</td>" +
            '<td class="mk-leads-td mk-leads-td--owner"><span class="mk-leads-owner-inner">' +
            '<span class="mk-owner-avatar" style="background:' + ownerColor(c.owner) + '">' + esc(ownerInitials(c.owner)) + "</span>" +
            "<span>" + esc(c.owner || "—") + "</span></span></td></tr>"
          );
        })
        .join("");
    }

    var summary = $("mk-contacts-filter-summary");
    if (summary) {
      summary.textContent =
        rows.length + " / " + all.length + " " + t("JS_MK_CONTACTS_COUNT_LABEL", "khách hàng");
    }
    renderPagination(rows.length, totalPages);
  }

  function renderPagination(total, totalPages) {
    var host = $("mk-contacts-pagination");
    if (!host) return;
    if (totalPages <= 1) {
      host.innerHTML = "";
      return;
    }
    host.innerHTML =
      '<button type="button" class="mk-leads-page-btn" data-page="prev"' + (state.page <= 1 ? " disabled" : "") + ">‹</button>" +
      '<span class="mk-leads-page-info">' +
      esc(t("JS_MK_PAGE", "Trang")) +
      " " +
      state.page +
      " / " +
      totalPages +
      "</span>" +
      '<button type="button" class="mk-leads-page-btn" data-page="next"' + (state.page >= totalPages ? " disabled" : "") + ">›</button>";
  }

  function applySegment(segId) {
    if (segId === "__all__") {
      state.activeSegment = null;
      state.filters = Object.assign({}, EMPTY);
      state.page = 1;
      renderAll();
      return;
    }
    var seg = getPresetSegments().find(function (s) { return s.id === segId; });
    state.activeSegment = segId;
    state.filters = Object.assign({}, EMPTY);
    if (seg && seg.filters) {
      Object.keys(seg.filters).forEach(function (k) {
        state.filters[k] = seg.filters[k];
      });
    }
    state.page = 1;
    renderAll();
  }

  function renderAll() {
    var rows = getContacts();
    renderKpi(rows);
    renderSegments();
    renderFiltersPanel();
    renderTable();
    document.documentElement.classList.add("mk-contacts-list-ready");
  }

  function bindEvents() {
    var search = $("mk-contacts-search");
    if (search) {
      search.addEventListener("input", function () {
        state.filters.search = search.value;
        state.page = 1;
        renderTable();
        renderKpi(filterContacts(getContacts()));
      });
    }

    var toggle = $("mk-contacts-filters-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        state.filtersOpen = !state.filtersOpen;
        toggle.setAttribute("aria-expanded", state.filtersOpen ? "true" : "false");
        var panel = $("mk-contacts-filters-panel");
        if (panel) panel.hidden = !state.filtersOpen;
      });
    }

    document.addEventListener("change", function (e) {
      var el = e.target;
      if (!el || !el.getAttribute || !el.closest("#mk-contacts-filters-panel")) return;
      var key = el.getAttribute("data-fkey");
      if (!key) return;
      state.filters[key] = el.value;
      state.activeSegment = null;
      state.page = 1;
      renderAll();
    });

    var segHost = $("mk-contacts-segments");
    if (segHost) {
      segHost.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-seg]");
        if (!btn) return;
        applySegment(btn.getAttribute("data-seg"));
      });
    }

    var pag = $("mk-contacts-pagination");
    if (pag) {
      pag.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-page]");
        if (!btn || btn.disabled) return;
        if (btn.getAttribute("data-page") === "prev") state.page--;
        else state.page++;
        renderTable();
      });
    }

    var reset = $("mk-contacts-reset");
    if (reset) {
      reset.addEventListener("click", function () {
        state.filters = Object.assign({}, EMPTY);
        state.activeSegment = null;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      });
    }

    if ($("mk-contacts-import-ic")) $("mk-contacts-import-ic").innerHTML = ic("import");
    if ($("mk-contacts-create-ic")) $("mk-contacts-create-ic").innerHTML = ic("plus");
    if ($("mk-contacts-search-ic")) $("mk-contacts-search-ic").innerHTML = ic("search");
    if ($("mk-contacts-segments-icon")) $("mk-contacts-segments-icon").innerHTML = ic("filter");
    if ($("mk-contacts-filters-ic")) $("mk-contacts-filters-ic").innerHTML = ic("filter");
  }

  function init() {
    if (!document.querySelector(".mk-contacts-page")) return;
    bindEvents();
    var boot = store && store.bootstrap ? store.bootstrap() : Promise.resolve([]);
    boot.then(function () {
      renderAll();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
