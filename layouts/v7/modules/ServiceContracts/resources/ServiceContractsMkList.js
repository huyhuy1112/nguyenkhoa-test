/* ServiceContracts list — Lovable UI (Leads shell) + affiliate column */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var ref = window.ServiceContractsLovableRef;
  var store = window.ServiceContractsLocalStore;
  var icons = window.LeadsMkIcons;
  var COL_COUNT = 12;

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
      { id: "tagged", name: pick("Có tag", "Has tag"), filters: { hasTag: true } },
      { id: "has_store", name: pick("Đã có quán", "Has store"), filters: { customerRank: "co_quan" } },
      { id: "no_store", name: pick("Chưa có quán", "No store yet"), filters: { customerRank: "chuan_bi_mo" } },
      { id: "family", name: pick("Gia đình", "Family"), filters: { customerRank: "gia_dinh" } },
      { id: "first_buy", name: pick("Mua lần đầu", "First purchase"), filters: { material: "mua_lan_dau" } },
      { id: "franchise", name: pick("Nhượng quyền", "Franchise"), filters: { franchise: "nhuong_quyen" } },
      { id: "deposit", name: pick("Đã ký quỹ", "Deposited"), filters: { franchise: "da_ky_quy" } },
      { id: "gold", name: pick("Hạng Vàng", "Gold tier"), filters: { tier: "vang" } },
      { id: "stale", name: pick("Lâu chưa chăm", "Stale"), filters: { staleOnly: true } },
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

  function getContracts() {
    return store ? store.getContracts() : [];
  }

  function detailUrl(id) {
    return (
      "index.php?module=ServiceContracts&view=Detail&record=" +
      encodeURIComponent(id) +
      "&app=SALES"
    );
  }

  function ownerInitials(name) {
    var parts = String(name || "")
      .trim()
      .split(/\s+/);
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

  function filterRows(rows) {
    var f = state.filters;
    var q = (f.search || "").toLowerCase().trim();
    return rows.filter(function (c) {
      var cats = categorize(c.tags);
      if (q) {
        var hay = [
          c.name,
          c.affiliate_code,
          c.contract_no,
          c.account,
          c.email,
          c.phone,
          c.owner,
          c.area,
          c.address,
          c.district,
          (c.tags || []).join(" "),
        ]
          .join(" ")
          .toLowerCase();
        if (hay.indexOf(q) < 0) return false;
      }
      if (f.hasTag && !(c.tags || []).length) return false;
      if (f.customerRank !== ANY && (!cats.customerRank || ref.normalizeTag(cats.customerRank) !== f.customerRank))
        return false;
      if (f.classTag !== ANY && (!cats.classTag || ref.normalizeTag(cats.classTag) !== f.classTag)) return false;
      if (f.material !== ANY && (!cats.material || ref.normalizeTag(cats.material) !== f.material)) return false;
      if (f.franchise !== ANY && (!cats.franchise || ref.normalizeTag(cats.franchise) !== f.franchise))
        return false;
      if (f.tier !== ANY && (!cats.tier || ref.normalizeTag(cats.tier) !== f.tier)) return false;
      if (f.anyTag !== ANY && !hasNormalizedTag(c.tags, f.anyTag)) return false;
      if (f.staleOnly && !isStale(c)) return false;
      if (f.owner !== ANY && c.owner !== f.owner) return false;
      return true;
    });
  }

  function sortRows(rows) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var av = a[key];
      var bv = b[key];
      if (key === "last_touch" || key === "createdtime") {
        av = av ? new Date(av).getTime() : 0;
        bv = bv ? new Date(bv).getTime() : 0;
        if (av < bv) return -1 * dir;
        if (av > bv) return 1 * dir;
        return 0;
      }
      av = String(av || "").toLowerCase();
      bv = String(bv || "").toLowerCase();
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  function formatDateTimeLabel(raw) {
    if (!raw) return "";
    var d = new Date(raw);
    if (isNaN(d.getTime())) return String(raw);
    var mm = String(d.getMonth() + 1);
    mm = mm.length < 2 ? "0" + mm : mm;
    var dd = String(d.getDate());
    dd = dd.length < 2 ? "0" + dd : dd;
    var yyyy = d.getFullYear();
    var h = d.getHours();
    var m = d.getMinutes();
    var ampm = h >= 12 ? "PM" : "AM";
    var h12 = h % 12;
    if (h12 === 0) h12 = 12;
    var mmins = String(m);
    mmins = mmins.length < 2 ? "0" + mmins : mmins;
    return mm + "-" + dd + "-" + yyyy + " " + h12 + ":" + mmins + " " + ampm;
  }

  function dateCell(raw) {
    var label = formatDateTimeLabel(raw);
    return label
      ? '<span class="mk-leads-date">' + esc(label) + "</span>"
      : '<span class="mk-leads-muted">—</span>';
  }

  function nextActionCell(c) {
    var next = String(c.next_action || "").trim();
    var overdue = !!c.next_action_overdue;
    var days =
      overdue && c.next_action_days_overdue != null
        ? c.next_action_days_overdue
        : c.next_action_days_remaining != null
          ? c.next_action_days_remaining
          : null;
    var alertLabel = "";
    if (c.rule_alert_days != null && Number(c.rule_alert_days) > 0) {
      if (overdue) {
        alertLabel = pick("Quá hạn " + days + " ngày", "Overdue " + days + "d");
      } else if (days != null) {
        alertLabel = pick("Còn " + days + " ngày", days + "d left");
      } else {
        alertLabel = pick("Cảnh báo " + c.rule_alert_days + " ngày", "Alert " + c.rule_alert_days + "d");
      }
    }
    var chip =
      alertLabel !== ""
        ? '<span class="mk-leads-next-alert' +
          (overdue ? " is-overdue" : "") +
          '">' +
          esc(alertLabel) +
          "</span>"
        : "";
    return (
      '<div class="mk-leads-next-cell" data-sc-id="' +
      esc(c.id) +
      '">' +
      '<button type="button" class="mk-leads-next-edit" data-sc-next="' +
      esc(c.id) +
      '" title="' +
      esc(pick("Sửa hành động tiếp theo", "Edit next action")) +
      '">' +
      (next
        ? '<span class="mk-leads-next-text">' + esc(next) + "</span>"
        : '<span class="mk-leads-muted">' + esc(pick("Thêm…", "Add…")) + "</span>") +
      chip +
      "</button></div>"
    );
  }

  function stackedTags(tags) {
    if (!tags || !tags.length) {
      return '<span class="mk-leads-muted">' + esc(pick("Thêm thẻ", "Add tags")) + "</span>";
    }
    return tags
      .map(function (tg) {
        var meta = tagMeta(tg);
        return '<span class="mk-tag ' + esc(meta.cls || "mk-tag") + '">' + esc(meta.label) + "</span>";
      })
      .join(" ");
  }

  function computeKpis(rows) {
    var withTags = rows.filter(function (c) {
      return (c.tags || []).length > 0;
    }).length;
    var withPhone = rows.filter(function (c) {
      return !!c.phone;
    }).length;
    var withAff = rows.filter(function (c) {
      return !!c.affiliate_code;
    }).length;
    var franchise = rows.filter(function (c) {
      return categorize(c.tags).franchise;
    }).length;
    var gold = rows.filter(function (c) {
      var tg = categorize(c.tags).tier;
      return tg && ref.normalizeTag(tg) === "vang";
    }).length;
    var stale = rows.filter(isStale).length;
    return [
      {
        key: "total",
        label: t("JS_MK_KPI_TOTAL_SC", "Tổng khách CN"),
        value: rows.length,
        icon: "users",
        tone: "blue",
      },
      {
        key: "affiliate",
        label: t("JS_MK_KPI_AFFILIATE", "Có mã AFF"),
        value: withAff,
        icon: "check",
        tone: "emerald",
      },
      {
        key: "tagged",
        label: t("JS_MK_KPI_TAGGED", "Có tag"),
        value: withTags,
        icon: "crown",
        tone: "violet",
      },
      {
        key: "phone",
        label: t("JS_MK_KPI_PHONE", "Có SĐT"),
        value: withPhone,
        icon: "clock",
        tone: "cyan",
      },
      {
        key: "franchise",
        label: t("JS_MK_KPI_FRANCHISE", "Nhượng quyền"),
        value: franchise,
        icon: "trend",
        tone: "indigo",
      },
      { key: "gold", label: t("JS_MK_KPI_GOLD", "Hạng Vàng"), value: gold, icon: "crown", tone: "rose" },
      {
        key: "stale",
        label: t("JS_MK_KPI_STALE", "Lâu chưa chăm"),
        value: stale,
        icon: "alert",
        tone: "amber",
      },
    ];
  }

  function renderKpi(rows) {
    var host = $("mk-sc-kpi");
    if (!host) return;
    host.innerHTML = computeKpis(rows)
      .map(function (k) {
        return (
          '<article class="mk-leads-kpi-card" data-kpi="' +
          esc(k.key) +
          '">' +
          '<div class="mk-leads-kpi-card__top">' +
          '<span class="mk-leads-kpi-card__label">' +
          '<span class="mk-leads-kpi-ic-wrap mk-leads-kpi-ic--' +
          esc(k.tone) +
          '">' +
          ic(k.icon) +
          "</span>" +
          esc(k.label) +
          "</span></div>" +
          '<div class="mk-leads-kpi-card__value">' +
          esc(String(k.value)) +
          "</div>" +
          "</article>"
        );
      })
      .join("");
  }

  function renderSegments() {
    var host = $("mk-sc-segments");
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
      '<option value="' +
      ANY +
      '">' +
      esc(t("JS_MK_FILTER_ALL", "Tất cả")) +
      "</option>" +
      pairs
        .map(function (p) {
          return '<option value="' + esc(p[0]) + '">' + esc(p[1]) + "</option>";
        })
        .join("")
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

  function renderFiltersPanel() {
    var host = $("mk-sc-filters-panel");
    if (!host || !ref) return;
    var rows = getContracts();
    var owners = [];
    rows.forEach(function (c) {
      if (c.owner && owners.indexOf(c.owner) < 0) owners.push(c.owner);
    });
    owners.sort();
    host.innerHTML =
      '<div class="mk-leads-filters-grid">' +
      fieldSelect(
        t("JS_MK_FILTER_TIER", "Hạng khách hàng"),
        "tier",
        ref.TIER_TAGS.map(function (tg) {
          return [ref.normalizeTag(tg), tagMeta(tg).label];
        })
      ) +
      fieldSelect(
        t("JS_MK_FILTER_CUSTOMER_RANK", "Loại khách"),
        "customerRank",
        ref.CUSTOMER_RANK_TAGS.map(function (tg) {
          return [ref.normalizeTag(tg), tagMeta(tg).label];
        })
      ) +
      fieldSelect(
        t("JS_MK_FILTER_CLASS", "Tag lớp học"),
        "classTag",
        ref.CLASS_TAGS.map(function (tg) {
          return [ref.normalizeTag(tg), tagMeta(tg).label];
        })
      ) +
      fieldSelect(
        t("JS_MK_FILTER_MATERIAL", "Tag nguyên liệu"),
        "material",
        ref.MATERIAL_TAGS.map(function (tg) {
          return [ref.normalizeTag(tg), tagMeta(tg).label];
        })
      ) +
      fieldSelect(
        t("JS_MK_FILTER_FRANCHISE", "Tag nhượng quyền"),
        "franchise",
        ref.FRANCHISE_TAGS.map(function (tg) {
          return [ref.normalizeTag(tg), tagMeta(tg).label];
        })
      ) +
      fieldSelect(
        t("JS_MK_FILTER_OWNER", "Phụ trách"),
        "owner",
        owners.map(function (o) {
          return [o, o];
        })
      ) +
      "</div>";
    host.hidden = !state.filtersOpen;
    syncFilterControls();
  }

  function syncFilterControls() {
    var f = state.filters;
    document.querySelectorAll("#mk-sc-filters-panel [data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (key && f[key] != null) el.value = f[key];
    });
    var reset = $("mk-sc-reset");
    if (reset) {
      var dirty =
        f.search ||
        f.hasTag ||
        f.staleOnly ||
        f.customerRank !== ANY ||
        f.classTag !== ANY ||
        f.material !== ANY ||
        f.franchise !== ANY ||
        f.tier !== ANY ||
        f.owner !== ANY;
      reset.hidden = !dirty && !state.activeSegment;
    }
  }

  function closeTagPopover() {
    var pop = $("mk-sc-tag-popover");
    if (pop && pop.parentNode) pop.parentNode.removeChild(pop);
  }

  function openTagPopover(anchor, contract) {
    closeTagPopover();
    if (!ref || !ref.getCreateTagCatalog) return;
    var catalog = ref.getCreateTagCatalog();
    var selected = {};
    (contract.tags || []).forEach(function (tg) {
      selected[ref.normalizeTag(tg)] = true;
    });
    var pop = document.createElement("div");
    pop.id = "mk-sc-tag-popover";
    pop.className = "mk-leads-tag-popover";
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
        chip.classList.toggle("is-on");
        chip.setAttribute("aria-pressed", chip.classList.contains("is-on") ? "true" : "false");
        return;
      }
      if (
        e.target.closest &&
        (e.target.closest("[data-tag-cancel]") || e.target.closest(".mk-leads-tag-popover__close"))
      ) {
        closeTagPopover();
        return;
      }
      if (e.target.closest && e.target.closest("[data-tag-save]")) {
        var nextTags = [];
        pop.querySelectorAll(".mk-leads-tag-chip.is-on").forEach(function (el) {
          nextTags.push(el.getAttribute("data-tag"));
        });
        var saveBtn = e.target.closest("[data-tag-save]");
        if (saveBtn) saveBtn.disabled = true;
        var saveFn = store.saveTags
          ? store.saveTags(contract.crmid || contract.id, nextTags)
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

  function promptNextAction(contract) {
    var current = String(contract.next_action || "");
    var next = window.prompt(pick("Hành động tiếp theo", "Next action"), current);
    if (next === null) return;
    if (!store || !store.saveNextAction) return;
    store
      .saveNextAction(contract.crmid || contract.id, next)
      .then(function () {
        renderAll();
      })
      .catch(function () {
        window.alert("Không lưu được hành động tiếp theo.");
      });
  }

  function renderTable() {
    var all = getContracts();
    var rows = sortRows(filterRows(all));
    var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if (state.page > totalPages) state.page = 1;
    var start = (state.page - 1) * PAGE_SIZE;
    var pageRows = rows.slice(start, start + PAGE_SIZE);
    var tbody = $("mk-sc-tbody");
    if (!tbody) return;

    if (!pageRows.length) {
      tbody.innerHTML =
        '<tr><td colspan="' +
        COL_COUNT +
        '" class="mk-leads-empty">' +
        esc(t("JS_MK_NO_SC_MATCH", "Không có khách chuyển nhượng phù hợp bộ lọc.")) +
        "</td></tr>";
    } else {
      tbody.innerHTML = pageRows
        .map(function (c) {
          var crmId = c.crmid != null && c.crmid !== "" ? String(c.crmid) : String(c.id || "");
          var checked = state.selected[c.id] ? " checked" : "";
          var aff = c.affiliate_code
            ? '<code class="mk-sc-aff-code">' + esc(c.affiliate_code) + "</code>"
            : '<span class="mk-leads-muted">—</span>';
          return (
            '<tr class="mk-leads-row mk-sc-row' +
            (state.selected[c.id] ? " mk-leads-row--selected" : "") +
            '" data-id="' +
            esc(c.id) +
            '"' +
            (crmId && /^\d+$/.test(crmId) ? ' data-crmid="' + esc(crmId) + '"' : "") +
            ">" +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-sc-row-check" data-id="' +
            esc(c.id) +
            '"' +
            checked +
            " />" +
            '<span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td">' +
            dateCell(c.createdtime) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--lead"><span class="mk-leads-lead-cell">' +
            ic("user") +
            '<span class="mk-leads-lead-text"><a class="mk-leads-name" href="' +
            detailUrl(c.crmid || c.id) +
            '">' +
            esc(c.name) +
            "</a>" +
            (c.contract_no
              ? '<div class="mk-leads-sub">' + esc(c.contract_no) + "</div>"
              : "") +
            "</span></span></td>" +
            '<td class="mk-leads-td mk-sc-td--aff">' +
            aff +
            "</td>" +
            '<td class="mk-leads-td">' +
            (c.phone ? esc(c.phone) : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td">' +
            (c.area ? esc(c.area) : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td">' +
            (c.address || c.district
              ? esc([c.address, c.district].filter(Boolean).join(", "))
              : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--owner"><span class="mk-leads-owner-inner">' +
            '<span class="mk-owner-avatar" style="background:' +
            ownerColor(c.owner) +
            '">' +
            esc(ownerInitials(c.owner)) +
            "</span>" +
            "<span>" +
            esc(c.owner || "—") +
            "</span></span></td>" +
            '<td class="mk-leads-td mk-leads-td--tags"><button type="button" class="mk-leads-tags-edit" data-sc-id="' +
            esc(c.id) +
            '" title="Sửa thẻ">' +
            stackedTags(c.tags) +
            "</button></td>" +
            '<td class="mk-leads-td">' +
            dateCell(c.last_touch) +
            "</td>" +
            '<td class="mk-leads-td">' +
            nextActionCell(c) +
            "</td>" +
            '<td class="mk-leads-td" data-col="notes">' +
            (function () {
              var n = String(c.notes || "").trim();
              if (!n) return '<span class="mk-leads-muted">—</span>';
              var short = n.length > 80 ? n.slice(0, 80) + "…" : n;
              return '<span class="mk-leads-notes-cell" title="' + esc(n) + '">' + esc(short) + "</span>";
            })() +
            "</td></tr>"
          );
        })
        .join("");
    }

    var summary = $("mk-sc-filter-summary");
    if (summary) {
      summary.textContent =
        rows.length + " / " + all.length + " " + t("JS_MK_SC_COUNT_LABEL", "khách chuyển nhượng");
    }
    renderPagination(rows.length, totalPages);

    var checkAll = $("mk-sc-check-all");
    if (checkAll) {
      var allOnPage =
        pageRows.length > 0 &&
        pageRows.every(function (c) {
          return !!state.selected[c.id];
        });
      checkAll.checked = allOnPage;
      checkAll.indeterminate =
        !allOnPage &&
        pageRows.some(function (c) {
          return !!state.selected[c.id];
        });
    }
    renderBulkBar();
    syncFilterControls();
  }

  function selectedCount() {
    return Object.keys(state.selected).length;
  }

  function selectedRows() {
    return getContracts().filter(function (c) {
      return !!state.selected[c.id];
    });
  }

  function clearSelection() {
    state.selected = {};
    renderTable();
  }

  function renderBulkBar() {
    var bar = $("mk-sc-bulk");
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
      "</strong> selected</span>" +
      "</div>" +
      '<div class="mk-leads-bulk-bar__actions">' +
      '<button type="button" class="mk-leads-bulk-btn" data-bulk="export">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("export") +
      "</span><span>Export</span></button>" +
      '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--danger" data-bulk="delete">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("trash") +
      "</span><span>Xóa</span></button>" +
      "</div>" +
      '<button type="button" class="mk-leads-bulk-clear" data-bulk="clear">Clear</button>' +
      "</div>";
  }

  function exportCsv(rows) {
    var lines = ["Name,Affiliate,Phone,Area,Address,Owner,Tags,NextAction"];
    rows.forEach(function (c) {
      lines.push(
        [
          c.name || "",
          c.affiliate_code || "",
          c.phone || "",
          c.area || "",
          c.address || "",
          c.owner || "",
          (c.tags || []).join("|"),
          c.next_action || "",
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
    a.download = "khach-chuyen-nhuong.csv";
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  function renderPagination(total, totalPages) {
    var host = $("mk-sc-pagination");
    if (!host) return;
    if (totalPages <= 1) {
      host.innerHTML = "";
      return;
    }
    host.innerHTML =
      '<button type="button" class="mk-leads-page-btn" data-page="prev"' +
      (state.page <= 1 ? " disabled" : "") +
      ">‹</button>" +
      '<span class="mk-leads-page-info">' +
      esc(t("JS_MK_PAGE", "Trang")) +
      " " +
      state.page +
      " / " +
      totalPages +
      "</span>" +
      '<button type="button" class="mk-leads-page-btn" data-page="next"' +
      (state.page >= totalPages ? " disabled" : "") +
      ">›</button>";
  }

  function applySegment(segId) {
    if (segId === "__all__") {
      state.activeSegment = null;
      state.filters = Object.assign({}, EMPTY);
      state.page = 1;
      renderAll();
      return;
    }
    var seg = getPresetSegments().find(function (s) {
      return s.id === segId;
    });
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
    var rows = getContracts();
    renderKpi(rows);
    renderSegments();
    renderFiltersPanel();
    renderTable();
    document.documentElement.classList.add("mk-sc-list-ready");
  }

  function bindEvents() {
    var search = $("mk-sc-search");
    if (search) {
      search.addEventListener("input", function () {
        state.filters.search = search.value;
        state.page = 1;
        renderTable();
        renderKpi(filterRows(getContracts()));
      });
    }

    var toggle = $("mk-sc-filters-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        state.filtersOpen = !state.filtersOpen;
        toggle.setAttribute("aria-expanded", state.filtersOpen ? "true" : "false");
        var panel = $("mk-sc-filters-panel");
        if (panel) panel.hidden = !state.filtersOpen;
      });
    }

    document.addEventListener("change", function (e) {
      var el = e.target;
      if (!el) return;
      if (el.classList && el.classList.contains("mk-sc-row-check")) {
        var id = el.getAttribute("data-id");
        if (el.checked) state.selected[id] = true;
        else delete state.selected[id];
        renderTable();
        return;
      }
      if (el.id === "mk-sc-check-all") {
        var pageRows = sortRows(filterRows(getContracts())).slice(
          (state.page - 1) * PAGE_SIZE,
          state.page * PAGE_SIZE
        );
        pageRows.forEach(function (c) {
          if (el.checked) state.selected[c.id] = true;
          else delete state.selected[c.id];
        });
        renderTable();
        return;
      }
      if (!el.getAttribute || !el.closest("#mk-sc-filters-panel")) return;
      var key = el.getAttribute("data-fkey");
      if (!key) return;
      state.filters[key] = el.value;
      state.activeSegment = null;
      state.page = 1;
      renderAll();
    });

    document.addEventListener("click", function (e) {
      var sortTh = e.target.closest && e.target.closest("#mk-sc-table th[data-sort]");
      if (sortTh) {
        var sk = sortTh.getAttribute("data-sort");
        if (state.sortKey === sk) {
          state.sortDir = state.sortDir === "asc" ? "desc" : "asc";
        } else {
          state.sortKey = sk;
          state.sortDir = sk === "name" ? "asc" : "desc";
        }
        renderTable();
        return;
      }

      var tagsBtn = e.target.closest && e.target.closest(".mk-leads-tags-edit[data-sc-id]");
      if (tagsBtn) {
        e.preventDefault();
        e.stopPropagation();
        var cid = tagsBtn.getAttribute("data-sc-id");
        var contract = getContracts().find(function (c) {
          return String(c.id) === String(cid);
        });
        if (contract) openTagPopover(tagsBtn, contract);
        return;
      }

      var nextBtn = e.target.closest && e.target.closest("[data-sc-next]");
      if (nextBtn) {
        e.preventDefault();
        e.stopPropagation();
        var nid = nextBtn.getAttribute("data-sc-next");
        var row = getContracts().find(function (c) {
          return String(c.id) === String(nid);
        });
        if (row) promptNextAction(row);
        return;
      }

      if (!e.target.closest || !e.target.closest("#mk-sc-tag-popover")) {
        closeTagPopover();
      }

      var bulkBtn = e.target.closest && e.target.closest("#mk-sc-bulk [data-bulk]");
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
      if (action === "export") {
        exportCsv(rows);
        return;
      }
      if (action === "delete") {
        if (!window.confirm("Xóa " + rows.length + " khách chuyển nhượng đã chọn?")) return;
        if (!store || !store.remove) return;
        Promise.all(
          rows.map(function (c) {
            return store.remove(c.id);
          })
        ).then(function () {
          clearSelection();
          renderAll();
        });
      }
    });

    var segHost = $("mk-sc-segments");
    if (segHost) {
      segHost.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-seg]");
        if (!btn) return;
        applySegment(btn.getAttribute("data-seg"));
      });
    }

    var pag = $("mk-sc-pagination");
    if (pag) {
      pag.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-page]");
        if (!btn || btn.disabled) return;
        if (btn.getAttribute("data-page") === "prev") state.page--;
        else state.page++;
        renderTable();
      });
    }

    var reset = $("mk-sc-reset");
    if (reset) {
      reset.addEventListener("click", function () {
        state.filters = Object.assign({}, EMPTY);
        state.activeSegment = null;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      });
    }

    if ($("mk-sc-import-ic")) $("mk-sc-import-ic").innerHTML = ic("import");
    if ($("mk-sc-create-ic")) $("mk-sc-create-ic").innerHTML = ic("plus");
    if ($("mk-sc-search-ic")) $("mk-sc-search-ic").innerHTML = ic("search");
    if ($("mk-sc-segments-icon")) $("mk-sc-segments-icon").innerHTML = ic("filter");
    if ($("mk-sc-filters-ic")) $("mk-sc-filters-ic").innerHTML = ic("filter");
  }

  function init() {
    if (!document.querySelector(".mk-sc-page--lovable")) return;
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
