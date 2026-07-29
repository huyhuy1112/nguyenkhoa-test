/* Potentials list — Opp-specific filters & tag columns (BA Excel) */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var ref = window.PotentialsLovableRef;
  var store = window.PotentialsLocalStore;
  var icons = window.LeadsMkIcons;
  var COL_COUNT = 15;

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

  /** Phân nhóm theo tag/BA của Cơ hội — UI giống Leads (segment-btn) */
  function getPresetSegments() {
    return [
      { id: "quote", name: pick("Báo giá", "Quotes"), filters: { sales_stage: "Prospecting" } },
      { id: "prospecting", name: pick("Tiềm năng", "Prospecting"), filters: { sales_stage: "Prospecting" } },
      { id: "internal", name: pick("Nội bộ", "Internal"), filters: { order_category: "Internal" } },
      { id: "project", name: pick("Dự án", "Project"), filters: { order_category: "Project" } },
      { id: "first_buy", name: pick("Mua lần đầu", "First purchase"), filters: { material: "mua_lan_dau" } },
      { id: "franchise", name: pick("Nhượng quyền", "Franchise"), filters: { franchise: "nhuong_quyen" } },
      { id: "deposit", name: pick("Đã ký quỹ", "Deposited"), filters: { franchise: "da_ky_quy" } },
      { id: "confirmed", name: pick("Xác nhận TG", "Confirmed"), filters: { confirm: "xac_nhan_tham_gia" } },
    ];
  }

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
    tier: ANY,
    anyTag: ANY,
    owner: ANY,
    staleOnly: false,
  };

  var state = {
    filters: Object.assign({}, EMPTY),
    sortKey: "last_touch",
    sortDir: "desc",
    page: 1,
    filtersOpen: true,
    activeSegment: null,
    selected: {},
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

  function categoryLabel(cat) {
    if (cat === "Internal") return "Nội bộ";
    if (cat === "Project") return "Dự án";
    return cat || "";
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

  function isStale(row) {
    var iso = row.last_touch || row.modifiedtime || row.closingdate;
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

  function filterOpps(rows) {
    var f = state.filters;
    var q = (f.search || "").toLowerCase().trim();
    return rows.filter(function (o) {
      var cats = categorize(o.tags);
      if (q) {
        var hay = [o.name, o.account, o.contact, o.owner, o.phone, o.address, o.notes, (o.tags || []).join(" ")]
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
      if (f.tier !== ANY && (!cats.tier || ref.normalizeTag(cats.tier) !== f.tier)) return false;
      if (f.anyTag !== ANY && !hasNormalizedTag(o.tags, f.anyTag)) return false;
      if (f.staleOnly && !isStale(o)) return false;
      if (f.owner !== ANY && o.owner !== f.owner) return false;
      return true;
    });
  }

  function formatDateTimeFull(iso) {
    if (!iso) return "";
    var d = new Date(iso);
    if (isNaN(d.getTime())) {
      var m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
      if (!m) return String(iso);
      return (
        m[3] +
        "/" +
        m[2] +
        "/" +
        m[1] +
        (m[4] ? " " + m[4] + ":" + m[5] + ":" + (m[6] || "00") : "")
      );
    }
    function pad(n) {
      return n < 10 ? "0" + n : String(n);
    }
    return (
      pad(d.getDate()) +
      "/" +
      pad(d.getMonth() + 1) +
      "/" +
      d.getFullYear() +
      " " +
      pad(d.getHours()) +
      ":" +
      pad(d.getMinutes()) +
      ":" +
      pad(d.getSeconds())
    );
  }

  function regionLabel(o, cats) {
    var area = cats && cats.area ? cats.area : "";
    if (area) {
      var key = ref && ref.normalizeTag ? ref.normalizeTag(area) : String(area);
      if (/^kv([123])$/i.test(key)) return "Khu vực " + RegExp.$1;
      return tagMeta(area).label || area;
    }
    var dist = String(o.district || "").trim();
    if (/khu\s*vực\s*([123])/iu.test(dist)) return "Khu vực " + RegExp.$1;
    return dist;
  }

  function stackedTagsHtml(cats) {
    var parts = [];
    [cats.classTag, cats.material, cats.franchise, cats.tier]
      .concat(cats.credentials || [])
      .concat(cats.custom || [])
      .forEach(function (tg) {
        if (tg) parts.push(tagBadgeHtml(tg));
      });
    return parts.length
      ? '<div class="mk-leads-tags-stack">' + parts.join("") + "</div>"
      : '<span class="mk-leads-muted">Thêm thẻ…</span>';
  }

  function closeTagPopover() {
    var old = document.getElementById("mk-opps-tag-popover");
    if (old && old.parentNode) old.parentNode.removeChild(old);
  }

  function openTagPopover(anchor, opp) {
    closeTagPopover();
    if (!opp || !store) return;
    var catalog = ref && ref.getCreateTagCatalog ? ref.getCreateTagCatalog() : [];
    var selected = {};
    (opp.tags || []).forEach(function (tg) {
      var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
      if (k) selected[k] = true;
    });
    var pop = document.createElement("div");
    pop.id = "mk-opps-tag-popover";
    pop.className = "mk-leads-tag-popover";
    pop.setAttribute("data-opp-id", String(opp.id));
    var groupsHtml = catalog
      .map(function (g) {
        var chips = (g.tags || [])
          .map(function (item) {
            var on = !!selected[item.key];
            return (
              '<button type="button" class="mk-leads-tag-chip' +
              (on ? " is-on" : "") +
              '" data-tag="' +
              esc(item.key) +
              '" aria-pressed="' +
              (on ? "true" : "false") +
              '">' +
              esc(item.label) +
              "</button>"
            );
          })
          .join("");
        return (
          '<div class="mk-leads-tag-popover__group">' +
          '<div class="mk-leads-tag-popover__group-title">' +
          esc(g.label) +
          "</div>" +
          '<div class="mk-leads-tag-popover__chips">' +
          chips +
          "</div></div>"
        );
      })
      .join("");
    pop.innerHTML =
      '<div class="mk-leads-tag-popover__head"><strong>Sửa thẻ</strong>' +
      '<button type="button" class="mk-leads-tag-popover__close" aria-label="Đóng">&times;</button></div>' +
      '<div class="mk-leads-tag-popover__body">' +
      groupsHtml +
      "</div>" +
      '<div class="mk-leads-tag-popover__foot">' +
      '<button type="button" class="mk-leads-btn mk-leads-btn--outline" data-tag-cancel="1">Hủy</button>' +
      '<button type="button" class="mk-leads-btn" data-tag-save="1">Lưu thẻ</button>' +
      "</div>";
    document.body.appendChild(pop);
    var rect = anchor.getBoundingClientRect();
    pop.style.top = rect.bottom + window.scrollY + 6 + "px";
    pop.style.left =
      Math.max(8, Math.min(rect.left + window.scrollX, window.scrollX + window.innerWidth - 360)) + "px";
    pop.addEventListener("click", function (e) {
      e.stopPropagation();
      var chip = e.target.closest && e.target.closest(".mk-leads-tag-chip");
      if (chip) {
        var group = chip.closest(".mk-leads-tag-popover__group");
        var turningOn = !chip.classList.contains("is-on");
        if (group && turningOn) {
          group.querySelectorAll(".mk-leads-tag-chip.is-on").forEach(function (el) {
            el.classList.remove("is-on");
            el.setAttribute("aria-pressed", "false");
          });
        }
        chip.classList.toggle("is-on", turningOn);
        chip.setAttribute("aria-pressed", turningOn ? "true" : "false");
        return;
      }
      if (e.target.closest && (e.target.closest("[data-tag-cancel]") || e.target.closest(".mk-leads-tag-popover__close"))) {
        closeTagPopover();
        return;
      }
      if (e.target.closest && e.target.closest("[data-tag-save]")) {
        var nextTags = [];
        pop.querySelectorAll(".mk-leads-tag-chip.is-on").forEach(function (el) {
          nextTags.push(el.getAttribute("data-tag"));
        });
        var catalogKeys = ref && ref.getCreateTagKeys ? ref.getCreateTagKeys() : [];
        (opp.tags || []).forEach(function (tg) {
          var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
          if (!k) return;
          if (catalogKeys.indexOf(k) < 0 && nextTags.indexOf(k) < 0) nextTags.push(k);
        });
        var saveBtn = e.target.closest("[data-tag-save]");
        if (saveBtn) saveBtn.disabled = true;
        var saveFn = store.saveTags
          ? store.saveTags(opp.crmid || opp.id, nextTags)
          : Promise.reject(new Error("saveTags unavailable"));
        saveFn
          .then(function () {
            closeTagPopover();
            renderAll();
          })
          .catch(function () {
            window.alert("Không lưu được thẻ.");
            if (saveBtn) saveBtn.disabled = false;
          });
      }
    });
  }

  function notesCell(text) {
    var s = String(text || "").trim();
    if (!s) return '<span class="mk-leads-muted">—</span>';
    var short = s.length > 80 ? s.slice(0, 80) + "…" : s;
    return (
      '<span class="mk-leads-notes-cell" title="' +
      esc(s) +
      '">' +
      esc(short) +
      "</span>"
    );
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
      } else if (key === "converted_at" || key === "confirmed_at" || key === "createdtime") {
        av = new Date(av || 0).getTime() || 0;
        bv = new Date(bv || 0).getTime() || 0;
      } else if (key === "name") {
        av = String(a.contact || a.account || a.name || "").toLowerCase();
        bv = String(b.contact || b.account || b.name || "").toLowerCase();
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
      { key: "total", label: t("JS_MK_KPI_TOTAL_OPP", "Tổng cơ hội"), value: total, icon: "users", tone: "blue" },
      { key: "pipeline", label: t("JS_MK_KPI_PIPELINE", "Pipeline"), value: formatMoney(pipeline), icon: "trend", tone: "violet" },
      { key: "internal", label: pick("Nội bộ", "Internal"), value: internal, icon: "check", tone: "emerald" },
      { key: "project", label: pick("Dự án", "Project"), value: project, icon: "bookmark", tone: "cyan" },
      { key: "tagged", label: t("JS_MK_KPI_TAGGED", "Có tag"), value: withTags, icon: "crown", tone: "amber" },
      { key: "franchise", label: t("JS_MK_KPI_FRANCHISE", "Nhượng quyền"), value: franchise, icon: "repeat", tone: "rose" },
      { key: "closing", label: t("JS_MK_KPI_CLOSING", "Đóng trong 30 ngày"), value: closingSoon, icon: "clock", tone: "indigo" },
      { key: "confirmed", label: t("JS_MK_KPI_CONFIRMED", "Xác nhận TG"), value: confirmed, icon: "check", tone: "emerald" },
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
      fieldSelect(t("JS_MK_FILTER_SALES_STAGE", "Giai đoạn bán hàng"), "sales_stage", stages.map(function (s) { return [s, stageLabel(s)]; })) +
      fieldSelect(t("JS_MK_FILTER_ORDER_CATEGORY", "Loại đơn hàng"), "order_category", [
        ["Internal", pick("Nội bộ", "Internal")],
        ["Project", pick("Dự án", "Project")],
      ]) +
      fieldSelect(t("JS_MK_FILTER_AREA", "Khu vực"), "area", ref.AREA_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_SOURCE", "Nguồn data"), "source", ref.SOURCE_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CUSTOMER", "Dạng khách hàng"), "customer", ref.CUSTOMER_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CLASS", "Tag lớp học"), "classTag", ref.CLASS_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_MATERIAL", "Tag nguyên liệu"), "material", ref.MATERIAL_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_FRANCHISE", "Tag nhượng quyền"), "franchise", ref.FRANCHISE_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CONFIRM", "Xác nhận tham gia"), "confirm", ref.CONFIRM_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
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
    document.querySelectorAll("#mk-opps-filters-panel [data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (f[key] !== undefined) el.value = f[key];
    });
  }

  function tagCellHtml(primary, extras) {
    var html = "";
    if (primary) html += tagBadgeHtml(primary);
    if (extras && extras.length) {
      extras.forEach(function (t) {
        html += tagBadgeHtml(t);
      });
    }
    return html || '<span class="mk-leads-muted">—</span>';
  }

  function tagBadgeHtml(tag) {
    if (!tag) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(tag);
    var key =
      ref && ref.normalizeTag ? ref.normalizeTag(tag) : String(tag || "").trim();
    if (!key) key = String(tag || "").trim();
    return (
      '<span class="mk-tag" data-tag="' +
      esc(key) +
      '" title="' +
      esc(String(tag)) +
      '">' +
      esc(m.label || key) +
      "</span>"
    );
  }

  function categoryPill(cat) {
    if (!cat) return '<span class="mk-leads-muted">—</span>';
    var cls = cat === "Project" ? "mk-pill--purple" : "mk-pill--orange";
    return '<span class="mk-pill ' + cls + '">' + esc(categoryLabel(cat)) + "</span>";
  }

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
      tbody.innerHTML =
        '<tr><td colspan="' +
        COL_COUNT +
        '" class="mk-leads-empty">' +
        esc(t("JS_MK_NO_OPPS_MATCH", "Không có cơ hội phù hợp bộ lọc.")) +
        "</td></tr>";
    } else {
      tbody.innerHTML = pageRows
        .map(function (o) {
          var cats = categorize(o.tags);
          var crmId = o.crmid != null && o.crmid !== "" ? String(o.crmid) : String(o.id || "");
          var customerName = String(o.contact || o.account || o.name || "").trim();
          if (!customerName || customerName === ".") customerName = "";
          var checked = state.selected[o.id] ? " checked" : "";
          return (
            '<tr class="mk-leads-row mk-opps-row' +
            (state.selected[o.id] ? " mk-leads-row--selected" : "") +
            '" data-id="' +
            esc(o.id) +
            '"' +
            (crmId && /^\d+$/.test(crmId) ? ' data-crmid="' + esc(crmId) + '"' : "") +
            ">" +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-opps-row-check" data-id="' + esc(o.id) + '"' + checked + " />" +
            '<span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td" data-col="converted_at">' +
            (o.converted_at || o.createdtime
              ? esc(formatDateTimeFull(o.converted_at || o.createdtime))
              : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--lead"><a class="mk-leads-name" href="' + detailUrl(o.crmid || o.id) + '">' +
            (customerName ? esc(customerName) : '<span class="mk-leads-muted">—</span>') +
            "</a></td>" +
            '<td class="mk-leads-td" data-col="phone">' +
            (o.phone
              ? esc(
                  window.MkPhoneFormat && typeof window.MkPhoneFormat.format === "function"
                    ? window.MkPhoneFormat.format(o.phone) || o.phone
                    : o.phone
                )
              : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td" data-col="region">' +
            (function () {
              var rl = regionLabel(o, cats);
              return rl ? esc(rl) : '<span class="mk-leads-muted">—</span>';
            })() +
            "</td>" +
            '<td class="mk-leads-td" data-col="address">' +
            (o.address ? esc(o.address) : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td" data-col="source">' + tagBadgeHtml(cats.source) + "</td>" +
            '<td class="mk-leads-td" data-col="customer">' + tagBadgeHtml(cats.customer) + "</td>" +
            '<td class="mk-leads-td"><span class="mk-pill ' + stagePillClass(o.sales_stage) + '">' + esc(stageLabel(o.sales_stage)) + "</span></td>" +
            '<td class="mk-leads-td mk-leads-td--owner"><span class="mk-leads-owner-inner">' +
            '<span class="mk-owner-avatar" style="background:' + ownerColor(o.owner) + '">' + esc(ownerInitials(o.owner)) + "</span>" +
            "<span>" + esc(o.owner || "—") + "</span></span></td>" +
            '<td class="mk-leads-td" data-col="tags"><button type="button" class="mk-leads-tags-edit" data-opp-id="' +
            esc(o.id) +
            '" title="Sửa thẻ">' +
            stackedTagsHtml(cats) +
            "</button></td>" +
            '<td class="mk-leads-td" data-col="confirm">' + tagBadgeHtml(cats.confirm) + "</td>" +
            '<td class="mk-leads-td" data-col="confirmed_at">' +
            (o.confirmed_at
              ? esc(formatDateTimeFull(o.confirmed_at))
              : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--next">' +
            (function () {
              var logic = window.LeadsLeadsLogic;
              if (logic && logic.nextActionCellHtml) {
                return logic.nextActionCellHtml(o, esc);
              }
              var next = String(o.next_action || "").trim();
              var tf = String(o.next_action_timeframe || "").trim();
              if (!next && !tf) return '<span class="mk-leads-muted">—</span>';
              var html = '<div class="mk-leads-next-action">';
              if (next) html += '<span class="mk-leads-next-action__text">' + esc(next) + "</span>";
              if (tf) {
                html +=
                  '<span class="mk-leads-next-action__time' +
                  (o.next_action_overdue ? " mk-leads-next-action__time--overdue" : "") +
                  '">' +
                  esc(tf) +
                  "</span>";
              }
              return html + "</div>";
            })() +
            "</td>" +
            '<td class="mk-leads-td" data-col="notes">' + notesCell(o.notes) + "</td></tr>"
          );
        })
        .join("");
    }

    var summary = $("mk-opps-filter-summary");
    if (summary) {
      summary.textContent =
        rows.length + " / " + all.length + " " + t("JS_MK_OPPS_COUNT_LABEL", "cơ hội");
    }
    renderPagination(rows.length, totalPages);

    var checkAll = $("mk-opps-check-all");
    if (checkAll) {
      var allOnPage = pageRows.length > 0 && pageRows.every(function (o) {
        return !!state.selected[o.id];
      });
      checkAll.checked = allOnPage;
      checkAll.indeterminate = !allOnPage && pageRows.some(function (o) {
        return !!state.selected[o.id];
      });
    }
    renderBulkBar();
  }

  function selectedCount() {
    return Object.keys(state.selected).length;
  }

  function selectedRows() {
    return getOpps().filter(function (o) {
      return !!state.selected[o.id];
    });
  }

  function clearSelection() {
    state.selected = {};
    renderTable();
  }

  function pickTier(count) {
    if (typeof window.MkOppPickCustomerTier === "function") {
      return window.MkOppPickCustomerTier({ count: count || 1 });
    }
    var raw = window.prompt("Chọn hạng khách hàng: vang / bac / dong", "dong");
    if (raw === null) return Promise.resolve(null);
    var s = String(raw).trim().toLowerCase();
    if (s === "gold" || s === "vàng") s = "vang";
    if (s === "silver" || s === "bạc") s = "bac";
    if (s === "bronze" || s === "đồng") s = "dong";
    if (["vang", "bac", "dong"].indexOf(s) < 0) {
      window.alert("Hạng không hợp lệ. Chọn vang, bac hoặc dong.");
      return Promise.resolve(null);
    }
    return Promise.resolve(s);
  }

  function convertOppToCustomer(recordId, tier) {
    var id = String(recordId || "");
    var tierKey = String(tier || "").trim().toLowerCase();
    if (!id) {
      return Promise.reject(new Error("Không tìm thấy ID Cơ hội."));
    }
    if (tierKey && ["vang", "bac", "dong"].indexOf(tierKey) < 0) {
      tierKey = "";
    }
    return new Promise(function (resolve, reject) {
      if (!(window.app && app.request && app.request.post)) {
        reject(new Error("Không kết nối được máy chủ."));
        return;
      }
      var postData = {
        module: "Potentials",
        action: "ConvertToCustomer",
        record: id,
      };
      if (tierKey) postData.tier = tierKey;
      app.request
        .post({
          data: postData,
        })
        .then(function (err, res) {
          if (err || !res || res.success === false) {
            var msg =
              (res && res.message) ||
              (err && err.message) ||
              "Không chuyển được sang Khách hàng.";
            reject(new Error(String(msg)));
            return;
          }
          var contactId = res.contact_id || (res.result && res.result.contact_id);
          if (!contactId) {
            reject(new Error("Không tìm thấy Contact để chuyển."));
            return;
          }
          resolve({
            contactId: contactId,
            tier: tierKey,
            url: "index.php?module=Contacts&view=List&app=SALES",
          });
        });
    });
  }

  function runBulkConvertToCustomer(rows) {
    if (!rows || !rows.length) return;
    (function () {
      if (window.app && app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
      var ok = 0;
      var fail = 0;
      var firstUrl = "";
      var chain = Promise.resolve();
      rows.forEach(function (o) {
        chain = chain.then(function () {
          return convertOppToCustomer(o.crmid || o.id, "")
            .then(function (res) {
              ok += 1;
              if (!firstUrl && res && res.url) firstUrl = res.url;
            })
            .catch(function () {
              fail += 1;
            });
        });
      });
      chain.then(function () {
        if (window.app && app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        var msg = "Đã chuyển " + ok + " cơ hội sang Khách hàng.";
        if (fail) msg += " " + fail + " thất bại.";
        if (window.app && app.helper && app.helper.showSuccessNotification) {
          app.helper.showSuccessNotification({ message: msg });
        } else {
          window.alert(msg);
        }
        clearSelection();
        if (ok > 0) {
          window.setTimeout(function () {
            window.location.href = firstUrl || "index.php?module=Contacts&view=List&app=SALES";
          }, 400);
        } else {
          renderAll();
        }
      });
    })();
  }

  function openInlineConvertToCustomer(btn) {
    var recordId = String((btn && btn.getAttribute("data-record-id")) || "");
    if (!recordId) {
      var panel = btn && btn.closest ? btn.closest(".mk-so-inline-detail") : null;
      recordId = String((panel && panel.getAttribute("data-record-id")) || "");
    }
    if (!recordId) return;
    if (btn) btn.disabled = true;
    if (window.app && app.helper && app.helper.showProgress) {
      app.helper.showProgress();
    }
    convertOppToCustomer(recordId, "")
        .then(function (res) {
          if (window.app && app.helper && app.helper.hideProgress) {
            app.helper.hideProgress();
          }
          if (btn) btn.disabled = false;
          if (window.app && app.helper && app.helper.showSuccessNotification) {
            app.helper.showSuccessNotification({
              message: "Đã chuyển sang Khách hàng.",
            });
          }
          window.setTimeout(function () {
            window.location.href =
              (res && res.url) || "index.php?module=Contacts&view=List&app=SALES";
          }, 400);
        })
        .catch(function (err) {
          if (window.app && app.helper && app.helper.hideProgress) {
            app.helper.hideProgress();
          }
          if (btn) btn.disabled = false;
          window.alert((err && err.message) || "Không chuyển được sang Khách hàng.");
        });
  }

  function renderBulkBar() {
    var bar = $("mk-opps-bulk");
    if (!bar) return;
    var n = selectedCount();
    if (!n) {
      bar.hidden = true;
      bar.innerHTML = "";
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
      "</strong> đã chọn</span>" +
      "</div>" +
      '<div class="mk-leads-bulk-bar__actions">' +
      '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--convert" data-bulk="to_customer">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("convert") +
      "</span><span>Chuyển sang khách hàng</span></button>" +
      '<button type="button" class="mk-leads-bulk-btn" data-bulk="export">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("export") +
      "</span><span>Xuất file</span></button>" +
      '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--danger" data-bulk="delete">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("trash") +
      "</span><span>Xóa</span></button>" +
      "</div>" +
      '<button type="button" class="mk-leads-bulk-clear" data-bulk="clear">Bỏ chọn</button>' +
      "</div>";
  }

  function exportCsv(rows) {
    var lines = ["Customer,OrderType,Stage,Amount,Owner,Tags"];
    rows.forEach(function (o) {
      var customerName = String(o.contact || o.account || o.name || "").trim();
      lines.push(
        [
          customerName,
          o.order_category || "",
          o.sales_stage || "",
          o.amount || 0,
          o.owner || "",
          (o.tags || []).join("|"),
        ]
          .map(function (v) {
            return '"' + String(v).replace(/"/g, '""') + '"';
          })
          .join(",")
      );
    });
    var blob = new Blob([lines.join("\n")], { type: "text/csv;charset=utf-8;" });
    var url = URL.createObjectURL(blob);
    var a = document.createElement("a");
    a.href = url;
    a.download = "opportunities.csv";
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
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
      if (!el) return;
      if (el.classList && el.classList.contains("mk-opps-row-check")) {
        var id = el.getAttribute("data-id");
        if (el.checked) state.selected[id] = true;
        else delete state.selected[id];
        renderTable();
        return;
      }
      if (el.id === "mk-opps-check-all") {
        var pageRows = sortOpps(filterOpps(getOpps())).slice(
          (state.page - 1) * PAGE_SIZE,
          state.page * PAGE_SIZE
        );
        pageRows.forEach(function (o) {
          if (el.checked) state.selected[o.id] = true;
          else delete state.selected[o.id];
        });
        renderTable();
        return;
      }
      if (!el.getAttribute || !el.closest("#mk-opps-filters-panel")) return;
      var key = el.getAttribute("data-fkey");
      if (!key) return;
      state.filters[key] = el.value;
      state.activeSegment = null;
      state.page = 1;
      renderAll();
    });

    document.addEventListener("click", function (e) {
      var tagsBtn = e.target.closest && e.target.closest(".mk-leads-tags-edit[data-opp-id]");
      if (tagsBtn) {
        e.preventDefault();
        e.stopPropagation();
        var oid = tagsBtn.getAttribute("data-opp-id");
        var opp = getOpps().find(function (o) {
          return String(o.id) === String(oid);
        });
        if (opp) openTagPopover(tagsBtn, opp);
        return;
      }
      if (!e.target.closest || !e.target.closest("#mk-opps-tag-popover")) {
        closeTagPopover();
      }
      var inlineToCustomer =
        e.target.closest &&
        e.target.closest(".mk-so-inline-detail__to-customer-btn");
      if (inlineToCustomer) {
        e.preventDefault();
        e.stopPropagation();
        openInlineConvertToCustomer(inlineToCustomer);
        return;
      }
      var bulkBtn = e.target.closest && e.target.closest("#mk-opps-bulk [data-bulk]");
      if (!bulkBtn) return;
      e.preventDefault();
      e.stopPropagation();
      var action = bulkBtn.getAttribute("data-bulk");
      var rows = selectedRows();
      if (!rows.length && action !== "clear") return;
      if (action === "clear") {
        clearSelection();
        return;
      }
      if (action === "to_customer") {
        runBulkConvertToCustomer(rows);
        return;
      }
      if (action === "export") {
        exportCsv(rows);
        return;
      }
      if (action === "delete") {
        if (!window.confirm("Xóa " + rows.length + " cơ hội đã chọn?")) return;
        if (!store || !store.remove) return;
        Promise.all(
          rows.map(function (o) {
            return store.remove(o.id);
          })
        ).then(function () {
          clearSelection();
          renderAll();
        });
      }
    });

    document.addEventListener("mk-opps-confirm-updated", function (e) {
      var detail = (e && e.detail) || {};
      var id = detail.id != null ? String(detail.id) : "";
      if (!id) return;
      if (store && store.setConfirmTag) {
        store.setConfirmTag(id, detail.confirm || "");
      }
      // Keep expanded row; refresh KPIs / filter summary without wiping tbody.
      var all = getOpps();
      var rows = filterOpps(all);
      renderKpi(rows);
      var summary = $("mk-opps-filter-summary");
      if (summary) {
        summary.textContent =
          rows.length + " / " + all.length + " " + t("JS_MK_OPPS_COUNT_LABEL", "cơ hội");
      }
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
