/* Potentials list — Opp-specific filters & tag columns (BA Excel) */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var ref = window.PotentialsLovableRef;
  var store = window.PotentialsLocalStore;
  var icons = window.LeadsMkIcons;

  var PRESET_SEGMENTS = [
    { id: "quote", name: "Báo giá", filters: { sales_stage: "Prospecting" } },
    { id: "prospecting", name: "Tiềm năng", filters: { sales_stage: "Prospecting" } },
    { id: "internal", name: "Internal", filters: { order_category: "Internal" } },
    { id: "project", name: "Project", filters: { order_category: "Project" } },
    { id: "first_buy", name: "Mua lần đầu", filters: { material: "mua_lan_dau" } },
    { id: "franchise", name: "Nhượng quyền", filters: { franchise: "nhuong_quyen" } },
    { id: "deposit", name: "Đã ký quỹ", filters: { franchise: "da_ky_quy" } },
    { id: "confirmed", name: "Xác nhận TG", filters: { confirm: "xac_nhan_tham_gia" } },
  ];

  var EMPTY = {
    search: "",
    sales_stage: ANY,
    order_category: ANY,
    area: ANY,
    source: ANY,
    customer: ANY,
    classTag: ANY,
    material: ANY,
    franchise: ANY,
    confirm: ANY,
    owner: ANY,
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

  function tagMeta(t) {
    return ref && ref.tagMeta ? ref.tagMeta(t) : { label: t, cls: "mk-tag" };
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

  function getOpps() {
    return store ? store.getOpportunities() : [];
  }

  function detailUrl(id) {
    return "index.php?module=Potentials&view=Detail&record=" + encodeURIComponent(id) + "&app=SALES";
  }

  function stageLabel(stage) {
    var map = {
      Prospecting: "Báo giá",
      Qualification: "Chất lượng",
      "Needs Analysis": "Phân tích nhu cầu",
      "Proposal/Price Quote": "Đề nghị/Báo giá",
      "Negotiation/Review": "Đàm phán/Xem xét",
      "Closed Won": "Hoàn thành",
      "Closed Lost": "Không thành công",
    };
    return map[stage] || stage || "—";
  }

  function formatMoney(n) {
    var v = Number(n) || 0;
    try {
      if (window.MkCurrency && MkCurrency.format) return MkCurrency.format(v);
    } catch (e) { /* ignore */ }
    return v.toLocaleString("vi-VN") + " đ";
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

  function stagePillClass(stage) {
    var map = {
      "Closed Won": "mk-pill--emerald",
      "Closed Lost": "mk-pill--rose",
      Prospecting: "mk-pill--purple",
      Qualification: "mk-pill--blue",
      "Needs Analysis": "mk-pill--cyan",
      "Proposal/Price Quote": "mk-pill--indigo",
      "Negotiation/Review": "mk-pill--amber",
    };
    return map[stage] || "mk-pill--indigo";
  }

  function closingDatePill(dateStr) {
    if (!dateStr) return '<span class="mk-leads-muted">—</span>';
    var d = new Date(dateStr);
    if (isNaN(d.getTime())) return '<span class="mk-pill mk-pill--date">' + esc(dateStr) + "</span>";
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    d.setHours(0, 0, 0, 0);
    var diff = (d - today) / 86400000;
    var cls = "mk-pill mk-pill--date";
    if (diff < 0) cls += " mk-pill--date-overdue";
    else if (diff <= 7) cls += " mk-pill--date-soon";
    else if (diff <= 30) cls += " mk-pill--date-month";
    else cls += " mk-pill--date-future";
    return '<span class="' + cls + '">' + ic("clock") + esc(dateStr) + "</span>";
  }

  function filterOpps(rows) {
    var f = state.filters;
    var q = (f.search || "").toLowerCase().trim();
    return rows.filter(function (o) {
      var cats = categorize(o.tags);
      if (q) {
        var hay = [o.name, o.account, o.contact, o.owner, (o.tags || []).join(" ")]
          .join(" ")
          .toLowerCase();
        if (hay.indexOf(q) < 0) return false;
      }
      if (f.sales_stage !== ANY && o.sales_stage !== f.sales_stage) return false;
      if (f.order_category !== ANY && o.order_category !== f.order_category) return false;
      if (f.area !== ANY && (!cats.area || ref.normalizeTag(cats.area) !== f.area)) return false;
      if (f.source !== ANY && (!cats.source || ref.normalizeTag(cats.source) !== f.source)) return false;
      if (f.customer !== ANY && (!cats.customer || ref.normalizeTag(cats.customer) !== f.customer)) return false;
      if (f.classTag !== ANY && (!cats.classTag || ref.normalizeTag(cats.classTag) !== f.classTag)) return false;
      if (f.material !== ANY && (!cats.material || ref.normalizeTag(cats.material) !== f.material)) return false;
      if (f.franchise !== ANY && (!cats.franchise || ref.normalizeTag(cats.franchise) !== f.franchise)) return false;
      if (f.confirm !== ANY && (!cats.confirm || ref.normalizeTag(cats.confirm) !== f.confirm)) return false;
      if (f.owner !== ANY && o.owner !== f.owner) return false;
      return true;
    });
  }

  function sortOpps(rows) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var av = a[key];
      var bv = b[key];
      if (key === "amount") {
        av = Number(av) || 0;
        bv = Number(bv) || 0;
      } else {
        av = String(av || "").toLowerCase();
        bv = String(bv || "").toLowerCase();
      }
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  function computeKpis(rows) {
    var total = rows.length;
    var pipeline = rows.reduce(function (s, o) { return s + (Number(o.amount) || 0); }, 0);
    var internal = rows.filter(function (o) { return o.order_category === "Internal"; }).length;
    var project = rows.filter(function (o) { return o.order_category === "Project"; }).length;
    var withTags = rows.filter(function (o) { return (o.tags || []).length > 0; }).length;
    var franchise = rows.filter(function (o) { return categorize(o.tags).franchise; }).length;
    var confirmed = rows.filter(function (o) {
      var c = categorize(o.tags).confirm;
      return c && ref.normalizeTag(c) === "xac_nhan_tham_gia";
    }).length;
    var closingSoon = rows.filter(function (o) {
      if (!o.closingdate) return false;
      var diff = (new Date(o.closingdate) - new Date()) / 86400000;
      return diff >= 0 && diff <= 30;
    }).length;
    return [
      { key: "total", label: "Tổng cơ hội", value: total, icon: "users", tone: "blue" },
      { key: "pipeline", label: "Pipeline", value: formatMoney(pipeline), icon: "trend", tone: "violet" },
      { key: "internal", label: "Internal", value: internal, icon: "check", tone: "emerald" },
      { key: "project", label: "Project", value: project, icon: "bookmark", tone: "cyan" },
      { key: "tagged", label: "Có tag", value: withTags, icon: "crown", tone: "amber" },
      { key: "franchise", label: "Nhượng quyền", value: franchise, icon: "repeat", tone: "rose" },
      { key: "closing", label: "Đóng trong 30 ngày", value: closingSoon, icon: "clock", tone: "indigo" },
      { key: "confirmed", label: "Xác nhận TG", value: confirmed, icon: "check", tone: "emerald" },
    ];
  }

  function renderKpi(rows) {
    var host = $("mk-opps-kpi");
    if (!host) return;
    host.innerHTML = computeKpis(rows)
      .map(function (k) {
        return (
          '<article class="mk-leads-kpi-card mk-opps-kpi-card" data-kpi="' + esc(k.key) + '">' +
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
    var host = $("mk-opps-segments");
    if (!host) return;
    host.innerHTML = PRESET_SEGMENTS.map(function (seg) {
      var active = state.activeSegment === seg.id ? " is-active" : "";
      return '<button type="button" class="mk-leads-segment-chip' + active + '" data-seg="' + esc(seg.id) + '">' + esc(seg.name) + "</button>";
    }).join("");
  }

  function selectOptions(pairs) {
    return (
      '<option value="' + ANY + '">Tất cả</option>' +
      pairs.map(function (p) {
        return '<option value="' + esc(p[0]) + '">' + esc(p[1]) + "</option>";
      }).join("")
    );
  }

  function renderFiltersPanel() {
    var host = $("mk-opps-filters-panel");
    if (!host || !ref) return;
    var rows = getOpps();
    var owners = [];
    var stages = [];
    rows.forEach(function (o) {
      if (o.owner && owners.indexOf(o.owner) < 0) owners.push(o.owner);
      if (o.sales_stage && stages.indexOf(o.sales_stage) < 0) stages.push(o.sales_stage);
    });
    owners.sort();
    stages.sort();
    host.innerHTML =
      '<div class="mk-leads-filters-grid">' +
      fieldSelect("Giai đoạn bán hàng", "sales_stage", stages.map(function (s) { return [s, stageLabel(s)]; })) +
      fieldSelect("Loại đơn hàng", "order_category", [["Internal", "Internal"], ["Project", "Project"]]) +
      fieldSelect("Khu vực", "area", ref.AREA_TAGS.map(function (t) { return [ref.normalizeTag(t), tagMeta(t).label]; })) +
      fieldSelect("Nguồn data", "source", ref.SOURCE_TAGS.map(function (t) { return [ref.normalizeTag(t), tagMeta(t).label]; })) +
      fieldSelect("Dạng khách hàng", "customer", ref.CUSTOMER_TAGS.map(function (t) { return [ref.normalizeTag(t), tagMeta(t).label]; })) +
      fieldSelect("Tag lớp học", "classTag", ref.CLASS_TAGS.map(function (t) { return [ref.normalizeTag(t), tagMeta(t).label]; })) +
      fieldSelect("Tag nguyên liệu", "material", ref.MATERIAL_TAGS.map(function (t) { return [ref.normalizeTag(t), tagMeta(t).label]; })) +
      fieldSelect("Tag nhượng quyền", "franchise", ref.FRANCHISE_TAGS.map(function (t) { return [ref.normalizeTag(t), tagMeta(t).label]; })) +
      fieldSelect("Phụ trách", "owner", owners.map(function (o) { return [o, o]; })) +
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
    document.querySelectorAll("#mk-opps-filters-panel [data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (f[key] !== undefined) el.value = f[key];
    });
  }

  function tagBadgeHtml(tag) {
    if (!tag) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(tag);
    return '<span class="mk-tag ' + m.cls + '">' + esc(m.label) + "</span>";
  }

  function categoryPill(cat) {
    if (!cat) return '<span class="mk-leads-muted">—</span>';
    var cls = cat === "Project" ? "mk-pill--purple" : "mk-pill--orange";
    return '<span class="mk-pill ' + cls + '">' + esc(cat) + "</span>";
  }

  function tagStackHtml(tags) {
    var list = tags || [];
    var shown = list.slice(0, 3);
    var extra = list.length - shown.length;
    if (!shown.length) return '<span class="mk-leads-muted">—</span>';
    return (
      shown.map(tagBadgeHtml).join("") +
      (extra > 0 ? '<span class="mk-leads-tag-more">+' + extra + "</span>" : "")
    );
  }

  var COL_COUNT = 10;

  function renderTable() {
    var all = getOpps();
    var rows = sortOpps(filterOpps(all));
    var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if (state.page > totalPages) state.page = 1;
    var start = (state.page - 1) * PAGE_SIZE;
    var pageRows = rows.slice(start, start + PAGE_SIZE);
    var tbody = $("mk-opps-tbody");
    if (!tbody) return;

    if (!pageRows.length) {
      tbody.innerHTML = '<tr><td colspan="' + COL_COUNT + '" class="mk-leads-empty">Không có cơ hội phù hợp bộ lọc.</td></tr>';
    } else {
      tbody.innerHTML = pageRows
        .map(function (o) {
          var tags = o.tags || [];
          var amountCls = Number(o.amount) > 0 ? " mk-opps-amount--positive" : "";
          return (
            '<tr class="mk-leads-row mk-opps-row" data-id="' + esc(o.id) + '">' +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-opps-row-check" data-id="' + esc(o.id) + '" />' +
            '<span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td mk-leads-td--lead"><a class="mk-leads-name" href="' + detailUrl(o.crmid || o.id) + '">' + esc(o.name) + "</a></td>" +
            '<td class="mk-leads-td">' + (o.account ? esc(o.account) : '<span class="mk-leads-muted">—</span>') + "</td>" +
            '<td class="mk-leads-td">' + (o.contact ? esc(o.contact) : '<span class="mk-leads-muted">—</span>') + "</td>" +
            '<td class="mk-leads-td">' + categoryPill(o.order_category) + "</td>" +
            '<td class="mk-leads-td"><span class="mk-pill ' + stagePillClass(o.sales_stage) + '">' + esc(stageLabel(o.sales_stage)) + "</span></td>" +
            '<td class="mk-leads-td mk-opps-amount' + amountCls + '">' + esc(formatMoney(o.amount)) + "</td>" +
            '<td class="mk-leads-td">' + closingDatePill(o.closingdate) + "</td>" +
            '<td class="mk-leads-td mk-leads-td--tags"><div class="mk-leads-tags-stack">' + tagStackHtml(tags) + "</div></td>" +
            '<td class="mk-leads-td mk-leads-td--owner"><span class="mk-leads-owner-inner">' +
            '<span class="mk-owner-avatar" style="background:' + ownerColor(o.owner) + '">' + esc(ownerInitials(o.owner)) + "</span>" +
            "<span>" + esc(o.owner || "—") + "</span></span></td></tr>"
          );
        })
        .join("");
    }

    var summary = $("mk-opps-filter-summary");
    if (summary) summary.textContent = rows.length + " / " + all.length + " cơ hội";
    renderPagination(rows.length, totalPages);
  }

  function renderPagination(total, totalPages) {
    var host = $("mk-opps-pagination");
    if (!host) return;
    if (totalPages <= 1) {
      host.innerHTML = "";
      return;
    }
    host.innerHTML =
      '<button type="button" class="mk-leads-page-btn" data-page="prev"' + (state.page <= 1 ? " disabled" : "") + ">‹</button>" +
      '<span class="mk-leads-page-info">Trang ' + state.page + " / " + totalPages + "</span>" +
      '<button type="button" class="mk-leads-page-btn" data-page="next"' + (state.page >= totalPages ? " disabled" : "") + ">›</button>";
  }

  function applySegment(segId) {
    var seg = PRESET_SEGMENTS.find(function (s) { return s.id === segId; });
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
    var rows = getOpps();
    renderKpi(rows);
    renderSegments();
    renderFiltersPanel();
    renderTable();
    document.documentElement.classList.add("mk-opp-list-ready");
  }

  function bindEvents() {
    var search = $("mk-opps-search");
    if (search) {
      search.addEventListener("input", function () {
        state.filters.search = search.value;
        state.page = 1;
        renderTable();
        renderKpi(filterOpps(getOpps()));
      });
    }

    var toggle = $("mk-opps-filters-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        state.filtersOpen = !state.filtersOpen;
        toggle.setAttribute("aria-expanded", state.filtersOpen ? "true" : "false");
        var panel = $("mk-opps-filters-panel");
        if (panel) panel.hidden = !state.filtersOpen;
      });
    }

    document.addEventListener("change", function (e) {
      var el = e.target;
      if (!el || !el.getAttribute || !el.closest("#mk-opps-filters-panel")) return;
      var key = el.getAttribute("data-fkey");
      if (!key) return;
      state.filters[key] = el.value;
      state.activeSegment = null;
      state.page = 1;
      renderAll();
    });

    var segHost = $("mk-opps-segments");
    if (segHost) {
      segHost.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-seg]");
        if (!btn) return;
        applySegment(btn.getAttribute("data-seg"));
      });
    }

    var pag = $("mk-opps-pagination");
    if (pag) {
      pag.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-page]");
        if (!btn || btn.disabled) return;
        if (btn.getAttribute("data-page") === "prev") state.page--;
        else state.page++;
        renderTable();
      });
    }

    var reset = $("mk-opps-reset");
    if (reset) {
      reset.addEventListener("click", function () {
        state.filters = Object.assign({}, EMPTY);
        state.activeSegment = null;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      });
    }

    if ($("mk-opps-import-ic")) $("mk-opps-import-ic").innerHTML = ic("import");
    if ($("mk-opps-create-ic")) $("mk-opps-create-ic").innerHTML = ic("plus");
    if ($("mk-opps-search-ic")) $("mk-opps-search-ic").innerHTML = ic("search");
    if ($("mk-opps-segments-icon")) $("mk-opps-segments-icon").innerHTML = ic("filter");
    if ($("mk-opps-filters-ic")) $("mk-opps-filters-ic").innerHTML = ic("filter");
  }

  function init() {
    if (!document.querySelector(".mk-opps-page")) return;
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
