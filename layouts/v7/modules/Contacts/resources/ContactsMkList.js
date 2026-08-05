/* Contacts list — Lovable UI (same shell as Leads / Potentials) */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var ref = window.ContactsLovableRef;
  var store = window.ContactsLocalStore;
  var icons = window.LeadsMkIcons;
  var COL_COUNT = 14;

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

  /** Loại khách chips — khớp Trạng thái khách trên Lead */
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
        var hay = [c.name, c.title, c.account, c.address, c.email, c.phone, c.owner, (c.tags || []).join(" ")]
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
      if (key === "thoigian_dangky" || key === "thoigian_pcth" || key === "thoigian_mqbb" || key === "converted_at") {
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
    var mm = String(d.getMonth() + 1).padStart
      ? String(d.getMonth() + 1).padStart(2, "0")
      : ("0" + (d.getMonth() + 1)).slice(-2);
    var dd = String(d.getDate()).padStart
      ? String(d.getDate()).padStart(2, "0")
      : ("0" + d.getDate()).slice(-2);
    var yyyy = d.getFullYear();
    var h = d.getHours();
    var m = d.getMinutes();
    var ampm = h >= 12 ? "PM" : "AM";
    var h12 = h % 12;
    if (h12 === 0) h12 = 12;
    var mmins = String(m).padStart ? String(m).padStart(2, "0") : ("0" + m).slice(-2);
    // DD-MM-YYYY (ngày → tháng), khớp yêu cầu list Contacts
    return dd + "-" + mm + "-" + yyyy + " " + h12 + ":" + mmins + " " + ampm;
  }

  function dateCell(raw) {
    var label = formatDateTimeLabel(raw);
    return label
      ? '<span class="mk-leads-date">' + esc(label) + "</span>"
      : '<span class="mk-leads-muted">—</span>';
  }

  function computeKpis(rows) {
    var withTags = rows.filter(function (c) { return (c.tags || []).length > 0; }).length;
    var withPhone = rows.filter(function (c) { return !!c.phone; }).length;
    var withCapTk = rows.filter(function (c) {
      return isCredentialIssued(c.da_cap_tai_khoan, "tk");
    }).length;
    var withCapBang = rows.filter(function (c) {
      return isCredentialIssued(c.da_cap_bang, "bang");
    }).length;
    var gold = rows.filter(function (c) {
      var tg = categorize(c.tags).tier;
      return tg && ref.normalizeTag(tg) === "vang";
    }).length;
    var franchise = rows.filter(function (c) { return categorize(c.tags).franchise; }).length;
    return [
      { key: "total", label: t("JS_MK_KPI_TOTAL_CONTACT", "Tổng khách hàng"), value: rows.length, icon: "users", tone: "blue" },
      { key: "tagged", label: t("JS_MK_KPI_TAGGED", "Có tag"), value: withTags, icon: "crown", tone: "violet" },
      { key: "cap_tk", label: t("JS_MK_KPI_CAP_TK", "Đã cấp tài khoản"), value: withCapTk, icon: "check", tone: "emerald" },
      { key: "phone", label: t("JS_MK_KPI_PHONE", "Có SĐT"), value: withPhone, icon: "clock", tone: "cyan" },
      { key: "cap_bang", label: t("JS_MK_KPI_CAP_BANG", "Đã cấp bằng"), value: withCapBang, icon: "repeat", tone: "amber" },
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
      fieldSelect(t("JS_MK_FILTER_CUSTOMER_RANK", "Loại khách"), "customerRank", ref.CUSTOMER_RANK_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
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
    var key = ref.normalizeTag(tag) || String(tag || "").trim();
    return (
      '<span class="mk-tag" data-tag="' +
      esc(key) +
      '">' +
      esc(m.label) +
      "</span>"
    );
  }

  function tierPill(tags) {
    var tier = categorize(tags).tier;
    if (!tier) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(tier);
    var key = ref.normalizeTag(tier);
    return (
      '<span class="mk-tag" data-tag="' +
      esc(key) +
      '">' +
      esc(m.label) +
      "</span>"
    );
  }

  function stackedContactTags(contact) {
    var list = (contact && contact.tags) || [];
    var seen = {};
    var parts = [];
    function pushTag(tg) {
      var key = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
      if (!key || seen[key]) return;
      if (key === "da_cap_bang" || key === "da_cap_tai_khoan") return;
      seen[key] = true;
      parts.push(tagBadgeHtml(tg));
    }
    list.forEach(pushTag);
    if (!parts.length) return '<span class="mk-leads-muted">Thêm thẻ…</span>';
    var maxShow = 2;
    var shown = parts.slice(0, maxShow);
    var extra = parts.length - shown.length;
    return (
      '<div class="mk-leads-tags-stack">' +
      shown.join("") +
      (extra > 0 ? '<span class="mk-leads-tag-more">+' + extra + "</span>" : "") +
      "</div>"
    );
  }

  function editableCellHtml(field, value, recordId, placeholder) {
    var shown = value;
    if (field === "phone" && value && window.MkPhoneFormat && typeof window.MkPhoneFormat.format === "function") {
      shown = window.MkPhoneFormat.format(value) || value;
    }
    var display = shown
      ? esc(shown)
      : '<span class="mk-leads-muted">' + esc(placeholder || "—") + "</span>";
    return (
      '<button type="button" class="mk-leads-inline-edit" data-field="' +
      esc(field) +
      '" data-contact-id="' +
      esc(recordId) +
      '" title="Nhấn để sửa">' +
      display +
      "</button>"
    );
  }

  function beginInlineEdit(btn) {
    if (!btn || !btn.getAttribute) return;
    var field = btn.getAttribute("data-field");
    var recordId = btn.getAttribute("data-contact-id");
    var current = btn.textContent.trim();
    if (current === "—" || current === "Nhập SĐT" || current === "Nhập địa chỉ") current = "";
    var input = document.createElement("input");
    input.type = field === "phone" ? "tel" : "text";
    input.className = "mk-leads-inline-input";
    input.value = current;
    input.setAttribute("data-field", field);
    input.setAttribute("data-contact-id", recordId);
    if (field === "phone") {
      input.setAttribute("inputmode", "numeric");
      input.setAttribute("maxlength", "12");
      input.addEventListener("input", function () {
        var next =
          window.MkPhoneFormat && typeof window.MkPhoneFormat.formatInput === "function"
            ? window.MkPhoneFormat.formatInput(input.value)
            : String(input.value || "").replace(/\D+/g, "").slice(0, 10);
        if (next !== input.value) input.value = next;
      });
    }
    btn.replaceWith(input);
    input.focus();
    if (current) input.select();
  }

  function commitInlineEdit(input) {
    if (!input || !input.getAttribute || !store || !store.saveInlineFields) {
      renderTable();
      return;
    }
    var field = input.getAttribute("data-field");
    var recordId = input.getAttribute("data-contact-id");
    var val = String(input.value || "").trim();
    var patch = {};
    if (field === "phone") {
      val = val.replace(/\s+/g, "");
      if (val && !/^\d{10}$/.test(val)) {
        window.alert("Số điện thoại phải đủ 10 số.");
        renderTable();
        return;
      }
      patch.phone = val;
    } else if (field === "address") {
      patch.address = val;
    } else {
      renderTable();
      return;
    }
    input.disabled = true;
    store
      .saveInlineFields(recordId, patch)
      .then(function () {
        renderTable();
      })
      .catch(function (err) {
        window.alert((err && err.message) || "Không lưu được.");
        renderTable();
      });
  }

  function isCredentialIssued(value, kind) {
    var v = String(value || "").trim();
    if (!v) return false;
    if (kind === "tk") return /đã\s*cấp/i.test(v) && !/chưa/i.test(v);
    return /đã\s*cấp/i.test(v) && !/chưa/i.test(v);
  }

  function credentialSelectHtml(contact, kind) {
    var field = kind === "tk" ? "da_cap_tai_khoan" : "da_cap_bang";
    var options =
      kind === "tk"
        ? ["Chưa cấp tài khoản", "Đã cấp tài khoản"]
        : ["Chưa cấp", "Đã cấp"];
    var cur = String((contact && contact[field]) || "").trim() || options[0];
    if (options.indexOf(cur) < 0) {
      cur = isCredentialIssued(cur, kind) ? options[1] : options[0];
    }
    var opts = options
      .map(function (opt) {
        return (
          '<option value="' +
          esc(opt) +
          '"' +
          (opt === cur ? " selected" : "") +
          ">" +
          esc(opt) +
          "</option>"
        );
      })
      .join("");
    return (
      '<select class="mk-contacts-cred-select" data-cred-field="' +
      esc(field) +
      '" data-contact-id="' +
      esc(contact.id) +
      '" title="Sửa trạng thái">' +
      opts +
      "</select>"
    );
  }

  function closeTagPopover() {
    var old = document.getElementById("mk-contacts-tag-popover");
    if (old && old.parentNode) old.parentNode.removeChild(old);
  }

  function openTagPopover(anchor, contact) {
    closeTagPopover();
    if (!contact || !store) return;
    var catalog = ref && ref.getCreateTagCatalog ? ref.getCreateTagCatalog() : [];
    var selected = {};
    (contact.tags || []).forEach(function (tg) {
      var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
      if (k === "da_cap_bang" || k === "da_cap_tai_khoan") return;
      if (k) selected[k] = true;
    });
    var pop = document.createElement("div");
    pop.id = "mk-contacts-tag-popover";
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
          var key = el.getAttribute("data-tag");
          if (key === "da_cap_bang" || key === "da_cap_tai_khoan") return;
          nextTags.push(key);
        });
        var saveBtn = e.target.closest("[data-tag-save]");
        if (saveBtn) saveBtn.disabled = true;
        var saveFn = store.saveTags
          ? store.saveTags(contact.crmid || contact.id, nextTags)
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
          var crmId = c.crmid != null && c.crmid !== "" ? String(c.crmid) : String(c.id || "");
          var checked = state.selected[c.id] ? " checked" : "";
          return (
            '<tr class="mk-leads-row mk-contacts-row' +
            (state.selected[c.id] ? " mk-leads-row--selected" : "") +
            '" data-id="' +
            esc(c.id) +
            '"' +
            (crmId && /^\d+$/.test(crmId) ? ' data-crmid="' + esc(crmId) + '"' : "") +
            ">" +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-contacts-row-check" data-id="' + esc(c.id) + '"' + checked + " />" +
            '<span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td">' + dateCell(c.converted_at) + "</td>" +
            '<td class="mk-leads-td mk-leads-td--lead"><span class="mk-leads-lead-cell">' +
            ic("user") +
            '<span class="mk-leads-lead-text"><a class="mk-leads-name" href="' + detailUrl(c.crmid || c.id) + '">' + esc(c.name) + "</a>" +
            (c.title ? '<div class="mk-leads-sub">' + esc(c.title) + "</div>" : "") +
            "</span></span></td>" +
            '<td class="mk-leads-td">' +
            editableCellHtml("phone", c.phone, c.crmid || c.id, "Nhập SĐT") +
            "</td>" +
            '<td class="mk-leads-td">' +
            editableCellHtml("address", c.address, c.crmid || c.id, "Nhập địa chỉ") +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--tags"><button type="button" class="mk-leads-tags-edit" data-contact-id="' +
            esc(c.id) +
            '" title="Sửa thẻ">' +
            stackedContactTags(c) +
            "</button></td>" +
            '<td class="mk-leads-td">' + credentialSelectHtml(c, "bang") + "</td>" +
            '<td class="mk-leads-td">' + credentialSelectHtml(c, "tk") + "</td>" +
            '<td class="mk-leads-td">' + dateCell(c.thoigian_dangky) + "</td>" +
            '<td class="mk-leads-td">' + dateCell(c.thoigian_pcth) + "</td>" +
            '<td class="mk-leads-td">' + dateCell(c.thoigian_mqbb) + "</td>" +
            '<td class="mk-leads-td mk-leads-td--owner"><span class="mk-leads-owner-inner">' +
            '<span class="mk-owner-avatar" style="background:' + ownerColor(c.owner) + '">' + esc(ownerInitials(c.owner)) + "</span>" +
            "<span>" + esc(c.owner || "—") + "</span></span></td>" +
            '<td class="mk-leads-td mk-leads-td--touch" data-col="last_touch">' +
            (window.MkLastTouchCall && window.MkLastTouchCall.lastTouchCallLogHtml
              ? window.MkLastTouchCall.lastTouchCallLogHtml(c, esc)
              : '<span class="mk-leads-muted">Chưa có cuộc gọi</span>') +
            "</td>" +
            '<td class="mk-leads-td" data-col="notes">' +
            (function () {
              var n = String(c.notes || "").trim();
              if (!n) return '<span class="mk-leads-muted">—</span>';
              var short = n.length > 80 ? n.slice(0, 80) + "…" : n;
              return (
                '<span class="mk-leads-notes-cell" title="' +
                esc(n) +
                '">' +
                esc(short) +
                "</span>"
              );
            })() +
            "</td></tr>"
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

    var checkAll = $("mk-contacts-check-all");
    if (checkAll) {
      var allOnPage = pageRows.length > 0 && pageRows.every(function (c) {
        return !!state.selected[c.id];
      });
      checkAll.checked = allOnPage;
      checkAll.indeterminate = !allOnPage && pageRows.some(function (c) {
        return !!state.selected[c.id];
      });
    }
    renderBulkBar();
  }

  function selectedCount() {
    return Object.keys(state.selected).length;
  }

  function selectedRows() {
    return getContacts().filter(function (c) {
      return !!state.selected[c.id];
    });
  }

  function clearSelection() {
    state.selected = {};
    renderTable();
  }

  function renderBulkBar() {
    var bar = $("mk-contacts-bulk");
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
    var lines = ["Name,Phone,Account,Owner,Tags"];
    rows.forEach(function (c) {
      lines.push(
        [
          c.name || "",
          c.phone || "",
          c.account || "",
          c.owner || "",
          (c.tags || []).join("|"),
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
    a.download = "contacts.csv";
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
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
      if (!el) return;
      if (el.classList && el.classList.contains("mk-contacts-cred-select")) {
        var contactId = el.getAttribute("data-contact-id");
        var field = el.getAttribute("data-cred-field");
        var contact = getContacts().find(function (c) {
          return String(c.id) === String(contactId);
        });
        if (!contact || !field) return;
        var nextBang = field === "da_cap_bang" ? el.value : contact.da_cap_bang;
        var nextTk = field === "da_cap_tai_khoan" ? el.value : contact.da_cap_tai_khoan;
        el.disabled = true;
        var saveCred = store.saveCredentials
          ? store.saveCredentials(contact.crmid || contact.id, nextBang, nextTk)
          : Promise.resolve(
              store.patchContact(contact.crmid || contact.id, {
                da_cap_bang: nextBang,
                da_cap_tai_khoan: nextTk,
              })
            );
        saveCred
          .then(function () {
            // Strip legacy credential tags — status lives on dropdowns only.
            var tags = ((contact.tags || []).slice()).filter(function (tg) {
              var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
              return k !== "da_cap_bang" && k !== "da_cap_tai_khoan";
            });
            if (store.saveTags) {
              return store.saveTags(contact.crmid || contact.id, tags);
            }
            store.patchContact(contact.crmid || contact.id, { tags: tags });
          })
          .then(function () {
            renderAll();
          })
          .catch(function () {
            window.alert("Không lưu được trạng thái cấp bằng / tài khoản.");
            renderTable();
          });
        return;
      }
      if (el.classList && el.classList.contains("mk-contacts-row-check")) {
        var id = el.getAttribute("data-id");
        if (el.checked) state.selected[id] = true;
        else delete state.selected[id];
        renderTable();
        return;
      }
      if (el.id === "mk-contacts-check-all") {
        var pageRows = sortContacts(filterContacts(getContacts())).slice(
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
      if (!el.getAttribute || !el.closest("#mk-contacts-filters-panel")) return;
      var key = el.getAttribute("data-fkey");
      if (!key) return;
      state.filters[key] = el.value;
      state.activeSegment = null;
      state.page = 1;
      renderAll();
    });

    document.addEventListener("click", function (e) {
      var editBtn = e.target.closest && e.target.closest(".mk-leads-inline-edit[data-contact-id]");
      if (editBtn) {
        e.preventDefault();
        e.stopPropagation();
        beginInlineEdit(editBtn);
        return;
      }
      var tagsBtn = e.target.closest && e.target.closest(".mk-leads-tags-edit[data-contact-id]");
      if (tagsBtn) {
        e.preventDefault();
        e.stopPropagation();
        var cid = tagsBtn.getAttribute("data-contact-id");
        var contact = getContacts().find(function (c) {
          return String(c.id) === String(cid);
        });
        if (contact) openTagPopover(tagsBtn, contact);
        return;
      }
      if (!e.target.closest || !e.target.closest("#mk-contacts-tag-popover")) {
        closeTagPopover();
      }
      var bulkBtn = e.target.closest && e.target.closest("#mk-contacts-bulk [data-bulk]");
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
        if (!window.confirm("Xóa " + rows.length + " khách hàng đã chọn?")) return;
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

    document.addEventListener(
      "focusout",
      function (e) {
        if (e.target && e.target.classList && e.target.classList.contains("mk-leads-inline-input") && e.target.getAttribute("data-contact-id")) {
          commitInlineEdit(e.target);
        }
      },
      true
    );
    document.addEventListener("keydown", function (e) {
      if (e.key !== "Enter") return;
      if (e.target && e.target.classList && e.target.classList.contains("mk-leads-inline-input") && e.target.getAttribute("data-contact-id")) {
        e.preventDefault();
        e.target.blur();
      }
    });

    document.addEventListener("mk-contacts-list-field-updated", function (e) {
      if (!e || !e.detail || !store || !store.patchContact) return;
      var detail = e.detail;
      if (!store.patchContact(detail.id, detail.patch || {})) return;
      renderTable();
    });

    if ($("mk-contacts-import-ic")) $("mk-contacts-import-ic").innerHTML = ic("import");
    if ($("mk-contacts-create-ic")) $("mk-contacts-create-ic").innerHTML = ic("plus");
    if ($("mk-contacts-search-ic")) $("mk-contacts-search-ic").innerHTML = ic("search");
    if ($("mk-contacts-segments-icon")) $("mk-contacts-segments-icon").innerHTML = ic("filter");
    if ($("mk-contacts-filters-ic")) $("mk-contacts-filters-ic").innerHTML = ic("filter");
  }

  function init() {
    if (!document.querySelector(".mk-contacts-page")) return;
    if (window.MkLastTouchCall && window.MkLastTouchCall.create) {
      window.__mkContactsLastTouch = window.MkLastTouchCall.create({
        module: "Contacts",
        onLogged: function (recordId, lt, res, callBtn) {
          var rows = getContacts();
          var row = rows.find(function (c) {
            return String(c.id) === String(recordId) || String(c.crmid) === String(recordId);
          });
          if (row && lt) {
            row.lastTouchCalls = lt;
            if (lt.logged && lt.logged.called_at) {
              row.last_touch = lt.logged.called_at;
            }
            if (store && store.patchContact) {
              store.patchContact(row.id, {
                lastTouchCalls: lt,
                last_touch: row.last_touch,
              });
            }
          }
          if (window.__mkContactsLastTouch) {
            window.__mkContactsLastTouch.applyToPanel(callBtn, lt);
          }
          var touchTd = document.querySelector(
            'tr.mk-contacts-row[data-id="' +
              String((row && row.id) || recordId) +
              '"] .mk-leads-td--touch'
          );
          if (touchTd && row) {
            touchTd.innerHTML = window.MkLastTouchCall.lastTouchCallLogHtml(row, esc);
          } else {
            renderTable();
          }
        },
      });
    }
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
