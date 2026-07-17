/**
 * Tag-Driven Create / Edit Lead — cache UI (LeadsLocalStore) + tags preview.
 */
(function () {
  "use strict";

  var LIST_URL = "index.php?module=Leads&view=List&app=SALES";

  function getEditRecordId() {
    var root = $("mk-td-create");
    var id = root && root.getAttribute("data-record-id");
    if (id) return String(id).trim();
    try {
      var m = window.location.search.match(/[?&]record=([^&]+)/);
      return m ? decodeURIComponent(m[1]) : "";
    } catch (e0) {
      return "";
    }
  }

  function isEditMode() {
    var root = $("mk-td-create");
    if (root && root.getAttribute("data-mode") === "edit") return true;
    return !!getEditRecordId();
  }

  function resolveApiRecordId(recordId, lead) {
    if (lead && lead.crmid != null && lead.crmid !== "") return String(lead.crmid);
    if (/^\d+$/.test(String(recordId || ""))) return String(recordId);
    return recordId || "";
  }

  function digitsOnly(value) {
    return String(value || "").replace(/\D/g, "").slice(0, 10);
  }

  function bindPhoneInput() {
    var phoneEl = $("mk-td-phone");
    if (!phoneEl) return;
    phoneEl.addEventListener("input", function () {
      var next = digitsOnly(phoneEl.value);
      if (phoneEl.value !== next) phoneEl.value = next;
    });
    phoneEl.addEventListener("paste", function (e) {
      e.preventDefault();
      var text = "";
      try {
        text = (e.clipboardData || window.clipboardData).getData("text") || "";
      } catch (err) {
        text = "";
      }
      phoneEl.value = digitsOnly(text);
    });
  }

  var CREATE_TAG_GROUPS = Array.isArray(window.MK_LEAD_CREATE_TAG_GROUPS)
    ? window.MK_LEAD_CREATE_TAG_GROUPS
    : [];

  function childIdsFromGroup(groupId) {
    for (var i = 0; i < CREATE_TAG_GROUPS.length; i++) {
      if (CREATE_TAG_GROUPS[i].id === groupId) {
        return (CREATE_TAG_GROUPS[i].children || []).map(function (c) {
          return c.id;
        });
      }
    }
    return null;
  }

  var TAG_POOLS = {
    customerType: ["individual", "company"],
    leadSource: ["facebook", "tiktok", "website", "zalo", "other_source"],
    intent: childIdsFromGroup("nguyen_lieu") || [
      "dang_tu_van",
      "mua_lan_dau",
      "dung_cham_soc",
      "kh_can_nhac",
      "mua_lai",
      "mua_it_lai",
      "ngung_mua",
    ],
    franchise: childIdsFromGroup("nhuong_quyen_group") || [
      "dang_tu_van",
      "khong_nghe_may",
      "thue_bao",
      "tiem_nang",
      "tham_khao",
      "dung_cham_soc",
      "khong_du_tai_chinh",
      "da_ky_quy",
      "mien_bac",
    ],
    entry: childIdsFromGroup("lop_hoc") || [
      "thu_3",
      "lop_online",
      "moi_lai",
      "da_tg_free",
      "doi_lich",
      "L1",
      "L2",
      "khong_hoc",
      "thue_bao",
      "trung_so",
      "khong_nghe_may",
      "ngung_cham_soc",
      "chua_MQBB_chua_PCTH",
      "chua_MQBB_da_PCTH",
      "da_MQBB_chua_PCTH",
      "da_MQBB_da_PCTH",
      "da_MQBB",
      "chua_MQBB",
      "da_PCTH",
      "chua_PCTH",
      "da_990k",
      "chua_990k",
      "hoan_tien_lop_hoc",
    ],
    entryBranch: ["van_hanh", "mkt", "lop_khac", "nhuong_quyen"],
    purchaseStatus: ["mua_lan_dau", "mua_lai", "khong_mua", "ngung_mua"],
    tier: ["vang", "bac", "dong"],
    region: ["KV1", "KV2", "KV3"],
  };

  var KNOWN_CREATE_GROUP_IDS = {
    nguyen_lieu: true,
    nhuong_quyen_group: true,
    lop_hoc: true,
  };

  function detailUrl(recordId) {
    return (
      "index.php?module=Leads&view=Detail&record=" +
      encodeURIComponent(recordId || "") +
      "&app=SALES&mkLeadId=" +
      encodeURIComponent(recordId || "")
    );
  }

  var state = {
    customerType: null,
    leadSource: null,
    customerStatus: null,
    intent: null,
    entry: null,
    franchise: null,
    entryBranch: null,
    purchaseStatus: null,
    purchaseReason: "",
    tier: null,
    extraGroupTags: {},
  };

  function $(id) {
    return document.getElementById(id);
  }

  function getSearchSelectIds() {
    var ids = ["mk-td-owner", "mk-td-entry-branch"];
    var seen = { "mk-td-owner": true, "mk-td-entry-branch": true };
    document.querySelectorAll(".js-mk-create-group-select").forEach(function (el) {
      if (el.id && !seen[el.id]) {
        seen[el.id] = true;
        ids.push(el.id);
      }
    });
    ["mk-td-intent", "mk-td-franchise", "mk-td-entry"].forEach(function (id) {
      if (!seen[id] && $(id)) {
        seen[id] = true;
        ids.push(id);
      }
    });
    return ids;
  }

  var SEARCH_SELECT_IDS = getSearchSelectIds();

  function syncSearchSelectTrigger(selectEl) {
    var wrap = selectEl && selectEl.closest ? selectEl.closest(".mk-td-search-select") : null;
    if (!wrap) return;
    var trigger = wrap.querySelector(".mk-td-search-select__trigger");
    if (!trigger) return;
    var opt = selectEl.options[selectEl.selectedIndex];
    trigger.textContent = opt && opt.text ? opt.text : "— Chọn —";
    var picked = !!selectEl.value;
    trigger.classList.toggle("mk-td-select--picked", picked);
    wrap.classList.toggle("mk-td-search-select--picked", picked);
    if (opt && opt.getAttribute) {
      var tagKey = opt.getAttribute("data-tag");
      if (tagKey) trigger.setAttribute("data-tag", tagKey);
      else trigger.removeAttribute("data-tag");
    } else {
      trigger.removeAttribute("data-tag");
    }
  }

  function closeAllSearchSelects(exceptWrap) {
    document.querySelectorAll(".mk-td-search-select.is-open").forEach(function (w) {
      if (exceptWrap && w === exceptWrap) return;
      w.classList.remove("is-open");
      var panel = w.querySelector(".mk-td-search-select__panel");
      if (panel) panel.hidden = true;
      var trigger = w.querySelector(".mk-td-search-select__trigger");
      if (trigger) trigger.setAttribute("aria-expanded", "false");
    });
  }

  function filterSearchSelectList(wrap, query) {
    var list = wrap.querySelector(".mk-td-search-select__list");
    if (!list) return;
    var q = String(query || "").trim().toLowerCase();
    var visible = 0;
    list.querySelectorAll(".mk-td-search-select__option").forEach(function (btn) {
      var text = String(btn.getAttribute("data-label") || btn.textContent || "").toLowerCase();
      var show = !q || text.indexOf(q) >= 0;
      btn.hidden = !show;
      if (show) visible += 1;
    });
    var empty = wrap.querySelector(".mk-td-search-select__empty");
    if (empty) empty.hidden = visible > 0 || !q;
  }

  function pickSearchSelectOption(selectEl, value) {
    for (var i = 0; i < selectEl.options.length; i++) {
      if (selectEl.options[i].value === value) {
        selectEl.selectedIndex = i;
        syncSearchSelectTrigger(selectEl);
        selectEl.dispatchEvent(new Event("change", { bubbles: true }));
        return;
      }
    }
  }

  function initSearchableSelect(selectEl) {
    if (!selectEl || selectEl.dataset.mkSearchInit === "1") return;
    if (selectEl.options.length < 2) return;
    selectEl.dataset.mkSearchInit = "1";

    var wrap = document.createElement("div");
    wrap.className = "mk-td-search-select";

    var trigger = document.createElement("button");
    trigger.type = "button";
    trigger.className = "mk-td-search-select__trigger mk-td-select";
    trigger.setAttribute("aria-haspopup", "listbox");
    trigger.setAttribute("aria-expanded", "false");

    var panel = document.createElement("div");
    panel.className = "mk-td-search-select__panel";
    panel.hidden = true;

    var search = document.createElement("input");
    search.type = "search";
    search.className = "mk-td-search-select__search";
    search.placeholder = "Tìm kiếm…";
    search.autocomplete = "off";

    var list = document.createElement("div");
    list.className = "mk-td-search-select__list";

    var empty = document.createElement("p");
    empty.className = "mk-td-search-select__empty";
    empty.textContent = "Không tìm thấy";
    empty.hidden = true;

    for (var i = 0; i < selectEl.options.length; i++) {
      (function (opt) {
        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "mk-td-search-select__option";
        btn.setAttribute("data-value", opt.value);
        btn.setAttribute("data-label", opt.text || "");
        var optTag = opt.getAttribute && opt.getAttribute("data-tag");
        if (optTag) btn.setAttribute("data-tag", optTag);
        btn.textContent = opt.text || opt.value || "—";
        if (!opt.value) btn.classList.add("mk-td-search-select__option--placeholder");
        btn.addEventListener("click", function () {
          pickSearchSelectOption(selectEl, opt.value);
          wrap.classList.remove("is-open");
          panel.hidden = true;
          trigger.setAttribute("aria-expanded", "false");
        });
        list.appendChild(btn);
      })(selectEl.options[i]);
    }

    var parent = selectEl.parentNode;
    parent.insertBefore(wrap, selectEl);
    wrap.appendChild(selectEl);
    wrap.appendChild(trigger);
    wrap.appendChild(panel);
    panel.appendChild(search);
    panel.appendChild(list);
    panel.appendChild(empty);

    selectEl.classList.add("mk-td-select--native-hidden");

    trigger.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var open = !wrap.classList.contains("is-open");
      closeAllSearchSelects(open ? wrap : null);
      wrap.classList.toggle("is-open", open);
      panel.hidden = !open;
      trigger.setAttribute("aria-expanded", open ? "true" : "false");
      if (open) {
        search.value = "";
        filterSearchSelectList(wrap, "");
        window.setTimeout(function () {
          search.focus();
        }, 0);
      }
    });

    search.addEventListener("input", function () {
      filterSearchSelectList(wrap, search.value);
    });
    search.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        wrap.classList.remove("is-open");
        panel.hidden = true;
        trigger.setAttribute("aria-expanded", "false");
      }
    });

    selectEl.addEventListener("change", function () {
      syncSearchSelectTrigger(selectEl);
      list.querySelectorAll(".mk-td-search-select__option").forEach(function (btn) {
        btn.classList.toggle("is-selected", btn.getAttribute("data-value") === selectEl.value);
      });
    });

    syncSearchSelectTrigger(selectEl);
    selectEl.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function initSearchableSelects() {
    SEARCH_SELECT_IDS.forEach(function (id) {
      var el = $(id);
      if (el) initSearchableSelect(el);
    });
  }

  function refreshSearchSelectTriggers() {
    SEARCH_SELECT_IDS.forEach(function (id) {
      var el = $(id);
      if (el) syncSearchSelectTrigger(el);
    });
  }

  document.addEventListener("click", function (e) {
    if (!e.target.closest || !e.target.closest(".mk-td-search-select")) {
      closeAllSearchSelects(null);
    }
  });

  function findTag(tags, pool) {
    if (!tags || !tags.length) return null;
    for (var i = 0; i < tags.length; i++) {
      if (pool.indexOf(tags[i]) >= 0) return tags[i];
    }
    return null;
  }

  function collectTags() {
    var tags = [];
    var seen = {};
    function pushTag(t) {
      if (!t || seen[t]) return;
      seen[t] = true;
      tags.push(t);
    }
    pushTag(state.customerType);
    pushTag(state.leadSource);
    pushTag(state.customerStatus);
    pushTag(state.intent);
    pushTag(state.entry);
    pushTag(state.entryBranch);
    pushTag(state.purchaseStatus);
    pushTag(state.franchise);
    pushTag(state.tier);
    pushTag(state.regionTag);
    Object.keys(state.extraGroupTags || {}).forEach(function (gid) {
      pushTag(state.extraGroupTags[gid]);
    });
    return tags;
  }

  function renderExtraGroupFoot(groupId, selectEl) {
    var foot = $("mk-td-g-" + groupId + "-tag-foot");
    if (!foot) return;
    var tag = state.extraGroupTags[groupId];
    if (!tag) {
      foot.hidden = true;
      foot.innerHTML = "";
      return;
    }
    var label = tag;
    if (selectEl && selectEl.selectedIndex >= 0 && selectEl.options[selectEl.selectedIndex]) {
      label = selectEl.options[selectEl.selectedIndex].text || tag;
    }
    foot.hidden = false;
    foot.innerHTML =
      '<span class="mk-td-tag-pill" data-tag="' + tag + '">' + label + "</span>";
  }

  function bindExtraCreateGroupSelects() {
    document.querySelectorAll(".js-mk-create-group-select").forEach(function (el) {
      var gid = el.getAttribute("data-group-id") || "";
      var tagGroup = el.getAttribute("data-tag-group") || "";
      if (!gid || KNOWN_CREATE_GROUP_IDS[gid]) return;
      if (tagGroup === "intent" || tagGroup === "franchise" || tagGroup === "entry") return;
      if (el.getAttribute("data-mk-bound") === "1") return;
      el.setAttribute("data-mk-bound", "1");
      el.addEventListener("change", function () {
        var opt = el.options[el.selectedIndex];
        var tag = opt && opt.getAttribute("data-tag") ? opt.getAttribute("data-tag") : null;
        state.extraGroupTags[gid] = tag;
        el.classList.toggle("mk-td-select--picked", !!el.value);
        renderExtraGroupFoot(gid, el);
        renderTags();
      });
    });
  }

  function renderTags() {
    var list = $("mk-td-tags-list");
    if (!list) return;

    var tags = collectTags();
    if (!tags.length) {
      list.innerHTML = '<span class="mk-td-tag-pill mk-td-tag-pill--empty">Chưa có tag</span>';
    } else {
      list.innerHTML = tags
        .map(function (t) {
          return '<span class="mk-td-tag-pill" data-tag="' + t + '">#' + t + "</span>";
        })
        .join("");
    }
  }

  function syncCustomerTypePanel() {
    var panel = $("mk-td-company-panel");
    var isCompany = state.customerType === "company";
    if (panel) {
      panel.hidden = !isCompany;
    }
    if (!isCompany) {
      ["mk-td-company-name", "mk-td-company-tax", "mk-td-company-rep"].forEach(function (id) {
        var el = $(id);
        if (el) el.value = "";
      });
    }
  }

  function setChoiceGroup(group, btn, opts) {
    opts = opts || {};
    var toggleable = group === "purchase-status" || group === "customer-tier";
    var alreadyOn = btn.classList.contains("is-on");

    // Hình 3 #05/#06: bấm lại option đang chọn → huỷ pick (cho phép không chọn).
    // hydrate từ lead (force) thì không toggle-off.
    if (toggleable && alreadyOn && !opts.force) {
      btn.classList.remove("is-on");
      if (group === "purchase-status") {
        state.purchaseStatus = null;
        syncPurchaseReasonPanel(null);
      } else if (group === "customer-tier") {
        state.tier = null;
      }
      renderTags();
      return;
    }

    document.querySelectorAll('.mk-td-choice[data-group="' + group + '"]').forEach(function (el) {
      el.classList.toggle("is-on", el === btn);
    });
    var tag = btn.getAttribute("data-tag");
    if (group === "customer-type") {
      state.customerType = tag;
      syncCustomerTypePanel();
    } else if (group === "lead-source") state.leadSource = tag;
    else if (group === "customer-status") {
      state.customerStatus = btn.getAttribute("data-segment") || null;
    } else if (group === "purchase-status") {
      state.purchaseStatus = tag;
      syncPurchaseReasonPanel(btn);
    } else if (group === "customer-tier") state.tier = tag;
    renderTags();
  }

  function activateChoice(group, tag) {
    if (!tag) return;
    var btn = document.querySelector(
      '.mk-td-choice[data-group="' + group + '"][data-tag="' + tag + '"]'
    );
    if (btn) setChoiceGroup(group, btn, { force: true });
  }

  function syncPurchaseReasonPanel(btn) {
    var panel = $("mk-td-purchase-reason");
    var foot = $("mk-td-purchase-tag-foot");
    var needs = btn && btn.getAttribute("data-needs-reason") === "1";
    if (panel) panel.hidden = !needs;
    if (foot) {
      foot.hidden = !needs;
      if (needs) foot.textContent = "#" + (btn.getAttribute("data-tag") || "");
    }
    if (!needs) {
      var ta = $("mk-td-purchase-reason-text");
      if (ta) ta.value = "";
      state.purchaseReason = "";
    }
  }

  function bindSelect(id, key, onSync) {
    var el = $(id);
    if (!el) return;
    function sync() {
      var opt = el.options[el.selectedIndex];
      state[key] = opt && opt.getAttribute("data-tag") ? opt.getAttribute("data-tag") : null;
      el.classList.toggle("mk-td-select--picked", !!el.value);
      if (onSync) onSync(el, opt);
      renderTags();
    }
    el.addEventListener("change", sync);
    sync();
  }

  function setSelectByTag(id, tag) {
    var el = $(id);
    if (!el || !tag) return;
    for (var i = 0; i < el.options.length; i++) {
      if (el.options[i].getAttribute("data-tag") === tag) {
        el.selectedIndex = i;
        el.dispatchEvent(new Event("change", { bubbles: true }));
        break;
      }
    }
  }

  function renderEntryTagFoot() {
    var foot = $("mk-td-entry-tag-foot");
    if (!foot) return;
    var pills = [];
    var entrySel = $("mk-td-entry");
    var branchSel = $("mk-td-entry-branch");
    if (state.entry) {
      var entryLabel = state.entry;
      if (entrySel && entrySel.selectedIndex >= 0 && entrySel.options[entrySel.selectedIndex]) {
        entryLabel = entrySel.options[entrySel.selectedIndex].text || state.entry;
      }
      pills.push({ tag: state.entry, label: entryLabel });
    }
    if (state.entryBranch) {
      var branchLabel = state.entryBranch;
      if (branchSel && branchSel.selectedIndex >= 0 && branchSel.options[branchSel.selectedIndex]) {
        branchLabel = branchSel.options[branchSel.selectedIndex].text || state.entryBranch;
      }
      pills.push({ tag: state.entryBranch, label: branchLabel });
    }
    if (!pills.length) {
      foot.hidden = true;
      foot.innerHTML = "";
      return;
    }
    foot.hidden = false;
    foot.innerHTML = pills
      .map(function (p) {
        return (
          '<span class="mk-td-tag-pill" data-tag="' + p.tag + '">' + p.label + "</span>"
        );
      })
      .join("");
  }

  function renderIntentTagFoot() {
    var foot = $("mk-td-intent-tag-foot");
    if (!foot) return;
    if (!state.intent) {
      foot.hidden = true;
      foot.innerHTML = "";
      return;
    }
    var sel = $("mk-td-intent");
    var label = state.intent;
    if (sel && sel.selectedIndex >= 0 && sel.options[sel.selectedIndex]) {
      label = sel.options[sel.selectedIndex].text || state.intent;
    }
    foot.hidden = false;
    foot.innerHTML =
      '<span class="mk-td-tag-pill" data-tag="' +
      state.intent +
      '">' +
      label +
      "</span>";
  }

  function renderFranchiseTagFoot() {
    var foot = $("mk-td-franchise-tag-foot");
    if (!foot) return;
    if (!state.franchise) {
      foot.hidden = true;
      foot.innerHTML = "";
      return;
    }
    foot.hidden = false;
    foot.innerHTML =
      '<span class="mk-td-tag-pill" data-tag="' +
      state.franchise +
      '">#' +
      state.franchise +
      "</span>";
  }

  function setPcthBranchVisible(show) {
    var pcthWrap = $("mk-td-entry-pcth-wrap");
    if (!pcthWrap) return;
    if (show) {
      pcthWrap.removeAttribute("hidden");
      pcthWrap.classList.add("is-visible");
      pcthWrap.style.display = "block";
    } else {
      pcthWrap.setAttribute("hidden", "hidden");
      pcthWrap.classList.remove("is-visible");
      pcthWrap.style.display = "none";
    }
  }

  function syncEntryProgram(el) {
    var branchEl = $("mk-td-entry-branch");
    var isPcth = el && el.value === "pcth";

    setPcthBranchVisible(isPcth);
    if (!isPcth && branchEl) {
      branchEl.value = "";
      state.entryBranch = null;
      branchEl.classList.remove("mk-td-select--picked");
    }
    renderEntryTagFoot();
  }

  function bindEntryProgram() {
    var el = $("mk-td-entry");
    var branchEl = $("mk-td-entry-branch");
    if (!el) return;

    function onEntryChange() {
      var opt = el.options[el.selectedIndex];
      state.entry = opt && opt.getAttribute("data-tag") ? opt.getAttribute("data-tag") : null;
      el.classList.toggle("mk-td-select--picked", !!el.value);
      syncEntryProgram(el);
      renderTags();
    }

    el.addEventListener("change", onEntryChange);

    if (branchEl) {
      branchEl.addEventListener("change", function () {
        var opt = branchEl.options[branchEl.selectedIndex];
        state.entryBranch = opt && opt.getAttribute("data-tag") ? opt.getAttribute("data-tag") : null;
        branchEl.classList.toggle("mk-td-select--picked", !!branchEl.value);
        renderEntryTagFoot();
        renderTags();
      });
    }

    onEntryChange();
  }

  function activateSegment(segment) {
    if (!segment) return;
    var btn = document.querySelector(
      '.mk-td-choice[data-group="customer-status"][data-segment="' + segment + '"]'
    );
    if (btn) setChoiceGroup("customer-status", btn, { force: true });
  }

  function applyTagsFromLead(tags, lead) {
    activateChoice("customer-type", findTag(tags, TAG_POOLS.customerType) || "individual");
    if (lead && lead.segment) activateSegment(lead.segment);
    activateChoice("lead-source", findTag(tags, TAG_POOLS.leadSource));
    // Purchase Status trước — không để purchase tag spill sang Nguyên liệu
    activateChoice("purchase-status", findTag(tags, TAG_POOLS.purchaseStatus));
    activateChoice("customer-tier", findTag(tags, TAG_POOLS.tier));
    setSelectByTag("mk-td-district", findTag(tags, TAG_POOLS.region));
    setSelectByTag("mk-td-intent", findTag(tags, TAG_POOLS.intent));
    setSelectByTag("mk-td-franchise", findTag(tags, TAG_POOLS.franchise));
    var entryTag = findTag(tags, TAG_POOLS.entry);
    setSelectByTag("mk-td-entry", entryTag);
    if (entryTag === "pcth") {
      setSelectByTag("mk-td-entry-branch", findTag(tags, TAG_POOLS.entryBranch));
    }
    CREATE_TAG_GROUPS.forEach(function (g) {
      if (!g || !g.id || KNOWN_CREATE_GROUP_IDS[g.id]) return;
      var pool = (g.children || []).map(function (c) {
        return c.id;
      });
      var hit = findTag(tags, pool);
      var selId = "mk-td-g-" + g.id;
      if (hit) {
        setSelectByTag(selId, hit);
        state.extraGroupTags[g.id] = hit;
        renderExtraGroupFoot(g.id, $(selId));
      }
    });
    renderTags();
  }

  function setSelectValue(id, value) {
    var el = $(id);
    if (!el) return;
    var found = false;
    for (var i = 0; i < el.options.length; i++) {
      if (el.options[i].value === value) {
        el.selectedIndex = i;
        found = true;
        break;
      }
    }
    if (!found && value) {
      var opt = document.createElement("option");
      opt.value = value;
      opt.textContent = value;
      opt.selected = true;
      el.appendChild(opt);
    }
  }

  function hydrateDistrictAddress(lead) {
    var district = lead.district || "";
    var address = lead.address || "";
    var regionTag = lead.tags ? findTag(lead.tags, TAG_POOLS.region) : null;
    if (!district && regionTag) {
      district = "Khu vực " + regionTag.replace(/^KV/i, "");
    }
    if (!district && lead.area) {
      var kvMatch = String(lead.area).match(/^Khu vực\s*([123])/i);
      if (kvMatch) {
        district = "Khu vực " + kvMatch[1];
      } else {
        address = address || lead.area;
      }
    }
    if (regionTag) {
      setSelectByTag("mk-td-district", regionTag);
    } else {
      setSelectValue("mk-td-district", district);
    }
    if ($("mk-td-address")) $("mk-td-address").value = address;
  }

  function hydrateFromStore(recordId) {
    var store = window.LeadsLocalStore;
    if (!store || !recordId || typeof store.getLead !== "function") return;
    var lead = store.getLead(recordId);
    if (!lead && store.getLeads) {
      var all = store.getLeads();
      for (var i = 0; i < all.length; i++) {
        if (String(all[i].crmid) === String(recordId) || String(all[i].id) === String(recordId)) {
          lead = all[i];
          break;
        }
      }
    }
    if (!lead) return;

    if ($("mk-td-name")) $("mk-td-name").value = lead.name || "";
    if ($("mk-td-phone")) $("mk-td-phone").value = digitsOnly(lead.phone || "");
    if ($("mk-td-cccd")) $("mk-td-cccd").value = lead.cccd || "";
    if ($("mk-td-email")) $("mk-td-email").value = lead.email || "";
    hydrateDistrictAddress(lead);
    if ($("mk-td-notes")) $("mk-td-notes").value = lead.notes || "";
    if ($("mk-td-company-name")) $("mk-td-company-name").value = lead.companyName || "";
    if ($("mk-td-owner") && lead.owner) {
      var ownerEl = $("mk-td-owner");
      var ownerVal = lead.owner_username || lead.owner;
      var found = false;
      for (var i = 0; i < ownerEl.options.length; i++) {
        if (
          ownerEl.options[i].value === ownerVal ||
          ownerEl.options[i].textContent === lead.owner
        ) {
          ownerEl.selectedIndex = i;
          found = true;
          break;
        }
      }
      if (!found && ownerVal) {
        var opt = document.createElement("option");
        opt.value = ownerVal;
        opt.textContent = lead.owner || ownerVal;
        opt.selected = true;
        ownerEl.appendChild(opt);
      }
    }

    var crumb = $("mk-td-crumb-record");
    if (crumb && lead.name) {
      crumb.textContent = lead.name;
      crumb.title = lead.name;
    }

    applyTagsFromLead(lead.tags || [], lead);
    refreshSearchSelectTriggers();
  }

  function buildLeadPatch() {
    var name = ($("mk-td-name") && $("mk-td-name").value) || "";
    var phone = digitsOnly(($("mk-td-phone") && $("mk-td-phone").value) || "");
    if ($("mk-td-phone")) $("mk-td-phone").value = phone;
    var ownerEl = $("mk-td-owner");
    return {
      name: name.trim(),
      phone: phone,
      cccd: ($("mk-td-cccd") && $("mk-td-cccd").value.trim()) || "",
      email: ($("mk-td-email") && $("mk-td-email").value.trim()) || "",
      segment: state.customerStatus || "",
      district: ($("mk-td-district") && $("mk-td-district").value) || "",
      address: ($("mk-td-address") && $("mk-td-address").value.trim()) || "",
      area: (function () {
        var district = ($("mk-td-district") && $("mk-td-district").value) || "";
        var address = ($("mk-td-address") && $("mk-td-address").value.trim()) || "";
        if (district && address) return district + ", " + address;
        return district || address;
      })(),
      notes: ($("mk-td-notes") && $("mk-td-notes").value.trim()) || "",
      companyName:
        state.customerType === "company"
          ? (($("mk-td-company-name") && $("mk-td-company-name").value.trim()) || "")
          : "",
      owner: ownerEl ? ownerEl.value : "",
      tags: collectTags(),
      last_touch: new Date().toISOString(),
    };
  }

  function potentialDetailUrl(potentialId) {
    return (
      "index.php?module=Potentials&view=Detail&record=" +
      encodeURIComponent(potentialId || "") +
      "&app=SALES"
    );
  }

  function autoConvertToOppIfNeeded(lead) {
    if (!lead || !lead.id) return Promise.resolve(null);
    if (state.customerStatus !== "co_quan") return Promise.resolve(null);
    if (!window.app || !app.request || !app.request.post) return Promise.resolve(null);

    // Default order category (no modal): Internal
    // Vtiger app.request returns a jQuery Deferred — no Promise.catch; wrap instead.
    return new Promise(function (resolve) {
      try {
        app.request
          .post({
            data: {
              module: "Leads",
              action: "ModernApi",
              mode: "convert",
              id: lead.id,
              order_category: "Internal",
            },
          })
          .then(function (err, res) {
            if (err || !res || res.success === false) {
              resolve(null);
              return;
            }
            resolve(res);
          });
      } catch (e) {
        resolve(null);
      }
    });
  }

  function mockSave() {
    var name = ($("mk-td-name") && $("mk-td-name").value) || "";
    var phone = digitsOnly(($("mk-td-phone") && $("mk-td-phone").value) || "");
    if ($("mk-td-phone")) $("mk-td-phone").value = phone;
    if (!name.trim() || !phone) {
      alert("Vui lòng nhập Họ tên và Số điện thoại.");
      return;
    }
    if (phone.length !== 10) {
      alert("Số điện thoại phải đủ 10 số.");
      return;
    }
    if (state.customerType === "company") {
      var companyName = ($("mk-td-company-name") && $("mk-td-company-name").value) || "";
      if (!companyName.trim()) {
        alert("Vui lòng nhập tên công ty / doanh nghiệp.");
        return;
      }
    }
    var purchaseVal = document.querySelector(
      '.mk-td-choice[data-group="purchase-status"].is-on'
    );
    if (purchaseVal && purchaseVal.getAttribute("data-needs-reason") === "1") {
      var reason = ($("mk-td-purchase-reason-text") && $("mk-td-purchase-reason-text").value) || "";
      if (!reason.trim()) {
        alert("Vui lòng nhập lý do không mua.");
        return;
      }
      state.purchaseReason = reason.trim();
    }
    if ($("mk-td-entry") && $("mk-td-entry").value === "pcth") {
      var branch = $("mk-td-entry-branch");
      if (!branch || !branch.value) {
        alert("Vui lòng chọn nhánh lớp PCTH.");
        return;
      }
    }

    var root = $("mk-td-create");
    var recordId = getEditRecordId();
    var isEdit = isEditMode();
    if (!recordId) {
      try {
        var urlRec = window.location.search.match(/[?&]record=([^&]+)/);
        if (urlRec) recordId = decodeURIComponent(urlRec[1]);
      } catch (eUrl) {
        /* ignore */
      }
    }
    if (recordId && !isEdit) isEdit = true;

    var patch = buildLeadPatch();
    var store = window.LeadsLocalStore;
    var existingLead = null;
    if (store && recordId && typeof store.getLead === "function") {
      existingLead = store.getLead(recordId);
    }
    if (isEdit && recordId) {
      var apiId = resolveApiRecordId(recordId, existingLead);
      if (/^\d+$/.test(String(apiId))) {
        patch.crmid = parseInt(apiId, 10);
      }
      if (existingLead && existingLead.id) {
        patch.id = existingLead.id;
      }
    }
    var savePromise;

    if (!store) {
      alert("Không thể lưu — hệ thống chưa tải xong. Vui lòng tải lại trang.");
      return;
    }

    if (isEdit && recordId && typeof store.update === "function") {
      savePromise = store.update(resolveApiRecordId(recordId, existingLead) || recordId, patch);
    } else if (typeof store.create === "function") {
      savePromise = store.create(patch);
    }

    if (!savePromise) {
      alert("Không thể lưu lead — thiếu mã bản ghi.");
      return;
    }

    Promise.resolve(savePromise)
      .then(function (lead) {
        if (isEdit) {
          window.location.href = LIST_URL;
          return;
        }

        var leadObj = lead && lead.id ? lead : { id: (lead && (lead.crmid || lead.id)) || recordId };
        return Promise.resolve(autoConvertToOppIfNeeded(leadObj)).then(function () {
          window.location.href = LIST_URL;
        });
      })
      .catch(function (err) {
        alert(err && err.message ? err.message : String(err || "Save failed"));
      });
  }

  function init() {
    var root = $("mk-td-create");
    if (!root) return;
    var store = window.LeadsLocalStore;

    SEARCH_SELECT_IDS = getSearchSelectIds();
    initSearchableSelects();
    bindPhoneInput();

    root.querySelectorAll(".mk-td-choice[data-group]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        setChoiceGroup(btn.getAttribute("data-group"), btn);
      });
    });

    bindSelect("mk-td-intent", "intent", function () {
      renderIntentTagFoot();
    });
    bindSelect("mk-td-franchise", "franchise", function () {
      renderFranchiseTagFoot();
    });
    bindSelect("mk-td-district", "regionTag");
    bindEntryProgram();
    bindExtraCreateGroupSelects();

    var reasonTa = $("mk-td-purchase-reason-text");
    if (reasonTa) {
      reasonTa.addEventListener("input", function () {
        state.purchaseReason = reasonTa.value;
      });
    }

    var saveTop = $("mk-td-save-top");
    if (saveTop) saveTop.addEventListener("click", mockSave);

    var initialType = document.querySelector('.mk-td-choice[data-group="customer-type"].is-on');
    if (initialType) {
      state.customerType = initialType.getAttribute("data-tag");
    }
    syncCustomerTypePanel();

    var recordId = getEditRecordId();
    var boot = store && store.ready ? store.ready() : Promise.resolve();
    boot.then(function () {
      if (recordId && isEditMode()) {
        var load =
          store && store.reloadLead
            ? store.reloadLead(recordId)
            : store && store.fetchLead
              ? store.fetchLead(recordId, true)
              : Promise.resolve();
        load
          .then(function () {
            hydrateFromStore(recordId);
          })
          .catch(function (err) {
            console.error("Lead edit hydrate failed", err);
            renderTags();
          });
        return;
      }
      renderTags();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
