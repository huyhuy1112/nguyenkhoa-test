/* Accounts list — Leads-like UI for KH NQ tiềm năng + tags */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var ref = window.AccountsLovableRef;
  var store = window.AccountsLocalStore;
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

  function getPresetSegments() {
    return [
      { id: "ca_nhan", name: pick("Cá nhân", "Individual"), filters: { customerType: "ca_nhan" } },
      { id: "cong_ty", name: pick("Công ty", "Company"), filters: { customerType: "cong_ty" } },
      { id: "has_store", name: pick("Đã có quán", "Has store"), filters: { customerStatus: "co_quan" } },
      { id: "no_store", name: pick("Chưa có quán", "No store yet"), filters: { customerStatus: "chuan_bi_mo" } },
      { id: "gold", name: pick("Hạng Vàng", "Gold"), filters: { tier: "vang" } },
      { id: "silver", name: pick("Hạng Bạc", "Silver"), filters: { tier: "bac" } },
      { id: "bronze", name: pick("Hạng Đồng", "Bronze"), filters: { tier: "dong" } },
      { id: "no_tags", name: pick("Chưa phân loại", "Unclassified"), filters: { noTags: true } },
    ];
  }

  var EMPTY = {
    search: "",
    customerType: ANY,
    customerStatus: ANY,
    tier: ANY,
    classTag: ANY,
    material: ANY,
    franchise: ANY,
    anyTag: ANY,
    owner: ANY,
    noTags: false,
  };

  var state = {
    filters: Object.assign({}, EMPTY),
    sortKey: "createdtime",
    sortDir: "desc",
    page: 1,
    filtersOpen: false,
    activeSegment: null,
    selected: {},
  };

  function $(id) {
    return document.getElementById(id);
  }

  function ic(name) {
    return icons && icons.get ? icons.get(name) : "";
  }

  function esc(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function tagMeta(tg) {
    return ref && ref.tagMeta ? ref.tagMeta(tg) : { label: tg, cls: "mk-tag" };
  }

  function categorize(tags) {
    return ref && ref.categorizeTags ? ref.categorizeTags(tags || []) : {};
  }

  function getAccounts() {
    return store && store.getAccounts ? store.getAccounts() : [];
  }

  function ownerLabel(a) {
    return String((a && a.owner) || "").trim();
  }

  function filterRows(rows) {
    var f = state.filters;
    var q = (f.search || "").toLowerCase().trim();
    return rows.filter(function (a) {
      if (q) {
        var hay = [a.name, a.account_no, a.phone, a.email, a.address, ownerLabel(a), (a.tags || []).join(" ")]
          .join(" ")
          .toLowerCase();
        if (hay.indexOf(q) < 0) {
          var qDigits = String(q).replace(/\D+/g, "");
          var phoneDigits = String(a.phone || "").replace(/\D+/g, "");
          if (!(qDigits.length >= 3 && phoneDigits.indexOf(qDigits) >= 0)) return false;
        }
      }
      var cats = categorize(a.tags || []);
      if (f.customerType !== ANY && (!cats.customerType || ref.normalizeTag(cats.customerType) !== f.customerType)) return false;
      if (f.customerStatus !== ANY && (!cats.customerStatus || ref.normalizeTag(cats.customerStatus) !== f.customerStatus)) return false;
      if (f.tier !== ANY && (!cats.tier || ref.normalizeTag(cats.tier) !== f.tier)) return false;
      if (f.classTag !== ANY && (!cats.classTag || ref.normalizeTag(cats.classTag) !== f.classTag)) return false;
      if (f.material !== ANY && (!cats.material || ref.normalizeTag(cats.material) !== f.material)) return false;
      if (f.franchise !== ANY && (!cats.franchise || ref.normalizeTag(cats.franchise) !== f.franchise)) return false;
      if (f.anyTag !== ANY && !(a.tags || []).some(function (tg) { return ref.normalizeTag(tg) === f.anyTag; })) return false;
      if (f.owner !== ANY && ownerLabel(a) !== f.owner) return false;
      if (f.noTags && (a.tags || []).length) return false;
      return true;
    });
  }

  function sortRows(rows) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var av = a[key];
      var bv = b[key];
      if (key === "createdtime" || key === "last_touch") {
        av = av ? new Date(av).getTime() : 0;
        bv = bv ? new Date(bv).getTime() : 0;
        if (isNaN(av)) av = 0;
        if (isNaN(bv)) bv = 0;
      } else {
        av = String(av || "").toLowerCase();
        bv = String(bv || "").toLowerCase();
      }
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  function dateCell(iso) {
    if (!iso) return '<span class="mk-leads-muted">—</span>';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return '<span class="mk-leads-muted">—</span>';
    var dd = String(d.getDate()).padStart(2, "0");
    var mm = String(d.getMonth() + 1).padStart(2, "0");
    var yyyy = d.getFullYear();
    var hh = String(d.getHours()).padStart(2, "0");
    var mi = String(d.getMinutes()).padStart(2, "0");
    return esc(dd + "/" + mm + "/" + yyyy + " " + hh + ":" + mi);
  }

  function detailUrl(id) {
    return "index.php?module=Accounts&view=Detail&record=" + encodeURIComponent(id) + "&app=SALES";
  }

  function stackedTags(tags) {
    if (!tags || !tags.length) {
      return '<span class="mk-leads-muted">' + esc(pick("Chưa phân loại", "Unclassified")) + "</span>";
    }
    return tags
      .slice(0, 6)
      .map(function (tg) {
        var meta = tagMeta(tg);
        return '<span class="mk-tag ' + esc(meta.cls || "mk-tag") + '">' + esc(meta.label) + "</span>";
      })
      .join(" ");
  }

  function renderKpi() {
    var host = $("mk-acc-kpi");
    if (!host) return;
    var all = getAccounts();
    var filtered = filterRows(all);
    var withPhone = filtered.filter(function (a) { return !!a.phone; }).length;
    var withTags = filtered.filter(function (a) { return (a.tags || []).length > 0; }).length;
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var newToday = filtered.filter(function (a) {
      if (!a.createdtime) return false;
      var d = new Date(a.createdtime);
      return !isNaN(d.getTime()) && d >= today;
    }).length;
    host.innerHTML =
      kpiCard(pick("Tổng KH", "Total"), filtered.length, "users") +
      kpiCard(pick("Mới hôm nay", "New today"), newToday, "plus") +
      kpiCard(pick("Có SĐT", "Has phone"), withPhone, "phone") +
      kpiCard(pick("Đã gắn thẻ", "Tagged"), withTags, "tag");
  }

  function kpiCard(label, value, icon) {
    return (
      '<div class="mk-leads-kpi-card"><div class="mk-leads-kpi-card__ic">' +
      ic(icon) +
      '</div><div class="mk-leads-kpi-card__body"><div class="mk-leads-kpi-card__val">' +
      esc(String(value)) +
      '</div><div class="mk-leads-kpi-card__lab">' +
      esc(label) +
      "</div></div></div>"
    );
  }

  function renderSegments() {
    var host = $("mk-acc-segments");
    if (!host) return;
    var segs = getPresetSegments();
    host.innerHTML =
      '<button type="button" class="mk-leads-seg' +
      (!state.activeSegment ? " is-on" : "") +
      '" data-seg="">' +
      esc(pick("Tất cả", "All")) +
      "</button>" +
      segs
        .map(function (s) {
          return (
            '<button type="button" class="mk-leads-seg' +
            (state.activeSegment === s.id ? " is-on" : "") +
            '" data-seg="' +
            esc(s.id) +
            '">' +
            esc(s.name) +
            "</button>"
          );
        })
        .join("");
  }

  function selectOptions(pairs) {
    return (
      '<option value="' + ANY + '">' + esc(t("JS_MK_FILTER_ALL", "Tất cả")) + "</option>" +
      pairs.map(function (p) {
        return '<option value="' + esc(p[0]) + '">' + esc(p[1]) + "</option>";
      }).join("")
    );
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

  function toggleField(label, key, on) {
    return (
      '<label class="mk-leads-toggle-field"><span class="mk-leads-toggle-field__label">' +
      esc(label) +
      '</span><input type="checkbox" class="mk-leads-toggle-field__input" data-fkey="' +
      key +
      '"' +
      (on ? " checked" : "") +
      " /></label>"
    );
  }

  function renderFiltersPanel() {
    var host = $("mk-acc-filters-panel");
    if (!host || !ref) return;
    var rows = getAccounts();
    var owners = [];
    var allTags = [];
    rows.forEach(function (a) {
      var o = ownerLabel(a);
      if (o && owners.indexOf(o) < 0) owners.push(o);
      (a.tags || []).forEach(function (tg) {
        var k = ref.normalizeTag(tg);
        if (k && allTags.indexOf(k) < 0) allTags.push(k);
      });
    });
    owners.sort();
    allTags.sort();
    host.innerHTML =
      '<div class="mk-leads-filters-grid">' +
      fieldSelect(t("JS_MK_FILTER_CUSTOMER_TYPE", "Loại khách"), "customerType", (ref.CUSTOMER_TYPE_TAGS || []).map(function (tg) {
        return [ref.normalizeTag(tg), tagMeta(tg).label];
      })) +
      fieldSelect(t("JS_MK_FILTER_CUSTOMER_STATUS", "Tình trạng khách"), "customerStatus", (ref.CUSTOMER_STATUS_TAGS || ref.CUSTOMER_RANK_TAGS || []).map(function (tg) {
        return [ref.normalizeTag(tg), tagMeta(tg).label];
      })) +
      fieldSelect(t("JS_MK_FILTER_TIER", "Hạng khách hàng"), "tier", (ref.TIER_TAGS || []).map(function (tg) {
        return [ref.normalizeTag(tg), tagMeta(tg).label];
      })) +
      fieldSelect(t("JS_MK_FILTER_CLASS", "Tag lớp học"), "classTag", (ref.CLASS_TAGS || []).map(function (tg) {
        return [ref.normalizeTag(tg), tagMeta(tg).label];
      })) +
      fieldSelect(t("JS_MK_FILTER_MATERIAL", "Tag nguyên liệu"), "material", (ref.MATERIAL_TAGS || []).map(function (tg) {
        return [ref.normalizeTag(tg), tagMeta(tg).label];
      })) +
      fieldSelect("Thẻ", "anyTag", allTags.map(function (k) {
        return [k, tagMeta(k).label || k];
      })) +
      fieldSelect(t("JS_MK_FILTER_OWNER", "Phụ trách"), "owner", owners.map(function (o) { return [o, o]; })) +
      toggleField(pick("Chưa có thẻ", "No tags"), "noTags", !!state.filters.noTags) +
      "</div>";
    host.hidden = !state.filtersOpen;
    syncFilterControls();
  }

  function syncFilterControls() {
    var f = state.filters;
    document.querySelectorAll("#mk-acc-filters-panel [data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (key && f[key] != null) {
        if (el.type === "checkbox") el.checked = !!f[key];
        else el.value = f[key];
      }
    });
    var reset = $("mk-acc-reset");
    if (reset) {
      var dirty =
        f.search ||
        f.customerType !== ANY ||
        f.customerStatus !== ANY ||
        f.tier !== ANY ||
        f.classTag !== ANY ||
        f.material !== ANY ||
        f.franchise !== ANY ||
        f.anyTag !== ANY ||
        f.owner !== ANY ||
        f.noTags;
      reset.hidden = !dirty && !state.activeSegment;
    }
  }

  function closeTagPopover() {
    var pop = $("mk-acc-tag-popover");
    if (pop && pop.parentNode) pop.parentNode.removeChild(pop);
  }

  function openTagPopover(anchor, account) {
    closeTagPopover();
    if (!ref || !ref.getCreateTagCatalog) return;
    var catalog = ref.getCreateTagCatalog();
    var selected = {};
    (account.tags || []).forEach(function (tg) {
      selected[ref.normalizeTag(tg)] = true;
    });
    var root = document.createElement("div");
    root.id = "mk-acc-tag-popover";
    root.className = "mk-leads-tag-popover";
    root.innerHTML =
      '<div class="mk-leads-tag-popover__head"><strong>' +
      esc(pick("Gắn thẻ", "Tags")) +
      '</strong><button type="button" class="mk-leads-tag-popover__close" data-close="1" aria-label="Đóng">&times;</button></div>' +
      '<div class="mk-leads-tag-popover__body">' +
      catalog
        .map(function (g) {
          var chips = (g.tags || [])
            .map(function (tg) {
              var key = tg.key || ref.normalizeTag(tg.label || tg);
              var on = !!selected[key];
              return (
                '<button type="button" class="mk-tag mk-leads-tag-pick' +
                (on ? " is-on" : "") +
                '" data-tag="' +
                esc(key) +
                '">' +
                esc(tg.label || key) +
                "</button>"
              );
            })
            .join("");
          return (
            '<div class="mk-leads-tag-popover__group"><div class="mk-leads-tag-popover__group-title">' +
            esc(g.label) +
            '</div><div class="mk-leads-tag-popover__chips">' +
            chips +
            "</div></div>"
          );
        })
        .join("") +
      "</div>" +
      '<div class="mk-leads-tag-popover__foot"><button type="button" class="mk-leads-btn mk-leads-btn--primary" data-save="1">' +
      esc(pick("Lưu thẻ", "Save tags")) +
      "</button></div>";
    document.body.appendChild(root);
    var rect = anchor.getBoundingClientRect();
    root.style.position = "fixed";
    root.style.top = Math.min(window.innerHeight - 360, rect.bottom + 6) + "px";
    root.style.left = Math.max(8, Math.min(window.innerWidth - 360, rect.left)) + "px";
    root.style.zIndex = "10050";

    root.addEventListener("click", function (e) {
      if (e.target.getAttribute("data-close") === "1") {
        closeTagPopover();
        return;
      }
      var pickBtn = e.target.closest && e.target.closest("[data-tag]");
      if (pickBtn) {
        pickBtn.classList.toggle("is-on");
        return;
      }
      if (e.target.getAttribute("data-save") === "1") {
        var tags = [];
        root.querySelectorAll("[data-tag].is-on").forEach(function (el) {
          tags.push(el.getAttribute("data-tag"));
        });
        e.target.disabled = true;
        store
          .saveTags(account.crmid || account.id, tags)
          .then(function () {
            closeTagPopover();
            renderAll();
          })
          .catch(function () {
            window.alert(pick("Không lưu được thẻ.", "Could not save tags."));
            e.target.disabled = false;
          });
      }
    });
  }

  function renderTable() {
    var all = getAccounts();
    var rows = sortRows(filterRows(all));
    var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if (state.page > totalPages) state.page = 1;
    var start = (state.page - 1) * PAGE_SIZE;
    var pageRows = rows.slice(start, start + PAGE_SIZE);
    var tbody = $("mk-acc-tbody");
    if (!tbody) return;

    if (!pageRows.length) {
      tbody.innerHTML =
        '<tr><td colspan="' +
        COL_COUNT +
        '" class="mk-leads-empty"><div class="mk-leads-empty__inner">' +
        esc(pick("Không có khách hàng nhượng quyền tiềm năng", "No franchise prospects")) +
        "</div></td></tr>";
    } else {
      tbody.innerHTML = pageRows
        .map(function (a) {
          var crmId = a.crmid != null ? String(a.crmid) : String(a.id || "");
          var checked = state.selected[a.id] ? " checked" : "";
          return (
            '<tr class="mk-leads-row' +
            (state.selected[a.id] ? " mk-leads-row--selected" : "") +
            '" data-id="' +
            esc(a.id) +
            '"' +
            (crmId ? ' data-crmid="' + esc(crmId) + '"' : "") +
            ">" +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-acc-row-check" data-id="' +
            esc(a.id) +
            '"' +
            checked +
            ' /><span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td">' +
            dateCell(a.createdtime) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--lead"><a class="mk-leads-name" href="' +
            detailUrl(crmId) +
            '">' +
            esc(a.name || "—") +
            "</a>" +
            (a.account_no ? '<div class="mk-leads-sub"><code>' + esc(a.account_no) + "</code></div>" : "") +
            "</td>" +
            '<td class="mk-leads-td">' +
            esc(a.phone || "—") +
            "</td>" +
            '<td class="mk-leads-td">' +
            esc(a.address || "—") +
            "</td>" +
            '<td class="mk-leads-td">' +
            esc(a.account_no || "—") +
            "</td>" +
            '<td class="mk-leads-td">' +
            (ownerLabel(a) ? esc(ownerLabel(a)) : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td"><button type="button" class="mk-leads-tags-edit" data-acc-id="' +
            esc(a.id) +
            '">' +
            stackedTags(a.tags) +
            ' <span class="mk-leads-tags-add" aria-hidden="true">+</span></button></td>' +
            '<td class="mk-leads-td">' +
            dateCell(a.last_touch) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--actions"><a class="mk-leads-icon-btn" href="' +
            detailUrl(crmId) +
            '" title="Chi tiết">→</a></td>' +
            "</tr>"
          );
        })
        .join("");
    }

    var summary = $("mk-acc-filter-summary");
    if (summary) {
      summary.textContent = rows.length + " / " + all.length + " " + pick("khách", "accounts");
    }
    renderPagination(rows.length, totalPages);
    syncFilterControls();
  }

  function renderPagination(total, totalPages) {
    var host = $("mk-acc-pagination");
    if (!host) return;
    var pages = Math.max(1, totalPages || 1);
    var start = total ? (state.page - 1) * PAGE_SIZE : 0;
    var from = total ? start + 1 : 0;
    var to = Math.min(start + PAGE_SIZE, total);
    host.innerHTML =
      '<span class="mk-leads-pagination__info">' +
      esc(t("JS_MK_SHOWING", "Hiển thị")) +
      " " +
      from +
      "–" +
      to +
      " / " +
      total +
      '</span><div class="mk-leads-pagination__btns">' +
      '<button type="button" class="mk-leads-page-btn" data-page="prev"' +
      (state.page <= 1 ? " disabled" : "") +
      ">" +
      esc(t("JS_MK_PREV", "Trước")) +
      '</button><span class="mk-leads-page-num">' +
      state.page +
      " / " +
      pages +
      '</span><button type="button" class="mk-leads-page-btn" data-page="next"' +
      (state.page >= pages ? " disabled" : "") +
      ">" +
      esc(t("JS_MK_NEXT", "Sau")) +
      "</button></div>";
  }

  function renderAll() {
    renderKpi();
    renderSegments();
    renderFiltersPanel();
    renderTable();
  }

  function applySegment(id) {
    state.activeSegment = id || null;
    state.filters = Object.assign({}, EMPTY);
    if (id) {
      var seg = getPresetSegments().find(function (s) {
        return s.id === id;
      });
      if (seg && seg.filters) {
        Object.keys(seg.filters).forEach(function (k) {
          state.filters[k] = seg.filters[k];
        });
      }
    }
    state.page = 1;
    renderAll();
  }

  function decorateIcons() {
    if ($("mk-acc-search-ic")) $("mk-acc-search-ic").innerHTML = ic("search");
    if ($("mk-acc-filters-ic")) $("mk-acc-filters-ic").innerHTML = ic("filter");
    if ($("mk-acc-segments-icon")) $("mk-acc-segments-icon").innerHTML = ic("layers");
    if ($("mk-acc-import-ic")) $("mk-acc-import-ic").innerHTML = ic("upload");
    if ($("mk-acc-create-ic")) $("mk-acc-create-ic").innerHTML = ic("plus");
  }

  function bindEvents() {
    var search = $("mk-acc-search");
    if (search) {
      var timer = null;
      search.addEventListener("input", function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          state.filters.search = search.value;
          state.page = 1;
          renderAll();
        }, 180);
      });
    }
    var toggle = $("mk-acc-filters-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        state.filtersOpen = !state.filtersOpen;
        toggle.setAttribute("aria-expanded", state.filtersOpen ? "true" : "false");
        var panel = $("mk-acc-filters-panel");
        if (panel) panel.hidden = !state.filtersOpen;
        if (state.filtersOpen) renderFiltersPanel();
      });
    }
    var reset = $("mk-acc-reset");
    if (reset) {
      reset.addEventListener("click", function () {
        state.filters = Object.assign({}, EMPTY);
        state.activeSegment = null;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      });
    }
    document.addEventListener("click", function (e) {
      var seg = e.target.closest && e.target.closest("#mk-acc-segments [data-seg]");
      if (seg) {
        applySegment(seg.getAttribute("data-seg") || "");
        return;
      }
      var pageBtn = e.target.closest && e.target.closest("#mk-acc-pagination [data-page]");
      if (pageBtn) {
        var act = pageBtn.getAttribute("data-page");
        if (act === "prev" && state.page > 1) state.page -= 1;
        if (act === "next") state.page += 1;
        renderTable();
        return;
      }
      var sortTh = e.target.closest && e.target.closest("#mk-acc-table th[data-sort]");
      if (sortTh) {
        var sk = sortTh.getAttribute("data-sort");
        if (state.sortKey === sk) state.sortDir = state.sortDir === "asc" ? "desc" : "asc";
        else {
          state.sortKey = sk;
          state.sortDir = sk === "name" ? "asc" : "desc";
        }
        renderTable();
        return;
      }
      var tagsBtn = e.target.closest && e.target.closest(".mk-leads-tags-edit[data-acc-id]");
      if (tagsBtn) {
        e.preventDefault();
        var aid = tagsBtn.getAttribute("data-acc-id");
        var account = getAccounts().find(function (a) {
          return String(a.id) === String(aid);
        });
        if (account) openTagPopover(tagsBtn, account);
        return;
      }
      if (!e.target.closest || !e.target.closest("#mk-acc-tag-popover")) {
        if (!(e.target.closest && e.target.closest(".mk-leads-tags-edit"))) closeTagPopover();
      }
    });
    document.addEventListener("change", function (e) {
      var el = e.target;
      if (!el) return;
      if (el.classList && el.classList.contains("mk-acc-row-check")) {
        if (el.checked) state.selected[el.getAttribute("data-id")] = true;
        else delete state.selected[el.getAttribute("data-id")];
        renderTable();
        return;
      }
      if (el.id === "mk-acc-check-all") {
        var pageRows = sortRows(filterRows(getAccounts())).slice(
          (state.page - 1) * PAGE_SIZE,
          state.page * PAGE_SIZE
        );
        pageRows.forEach(function (a) {
          if (el.checked) state.selected[a.id] = true;
          else delete state.selected[a.id];
        });
        renderTable();
        return;
      }
      if (!el.getAttribute || !el.closest("#mk-acc-filters-panel")) return;
      var key = el.getAttribute("data-fkey");
      if (!key) return;
      if (el.type === "checkbox") state.filters[key] = !!el.checked;
      else state.filters[key] = el.value;
      state.activeSegment = null;
      state.page = 1;
      renderAll();
    });
  }

  function init() {
    if (!document.querySelector("[data-mk-acc-list]")) return;
    document.documentElement.classList.add("mk-sales-list-ready", "mk-accounts-list-ready");
    decorateIcons();
    bindEvents();
    var boot = store && store.bootstrap ? store.bootstrap() : Promise.resolve();
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
