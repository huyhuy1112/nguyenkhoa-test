/**
 * Tag-Driven Create / Edit Lead — cache UI (LeadsLocalStore) + tags preview.
 */
(function () {
  "use strict";

  var LIST_URL = "index.php?module=Leads&view=List&app=SALES";

  var TAG_POOLS = {
    customerType: ["individual", "company"],
    leadSource: ["facebook", "tiktok", "website", "zalo", "other_source"],
    intent: [
      "dang_tu_van",
      "mua_lan_dau",
      "dung_cham_soc",
      "kh_can_nhac",
      "mua_lai",
      "mua_it_lai",
      "ngung_mua",
    ],
    franchise: [
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
    entry: [
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
  };

  function $(id) {
    return document.getElementById(id);
  }

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
    return tags;
  }

  function renderTags() {
    var list = $("mk-td-tags-list");
    var trigger = $("mk-td-tags-trigger");
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

    if (trigger) {
      var n = tags.length;
      trigger.textContent =
        "WORKFLOW TRIGGER: " + n + " tag(s) → khớp script & automation tương ứng.";
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
    activateChoice("purchase-status", findTag(tags, TAG_POOLS.purchaseStatus));
    activateChoice("customer-tier", findTag(tags, TAG_POOLS.tier));
    setSelectByTag("mk-td-district", findTag(tags, TAG_POOLS.region));
    setSelectByTag("mk-td-intent", findTag(tags, TAG_POOLS.intent));
    var entryTag = findTag(tags, TAG_POOLS.entry);
    setSelectByTag("mk-td-entry", entryTag);
    if (entryTag === "pcth") {
      setSelectByTag("mk-td-entry-branch", findTag(tags, TAG_POOLS.entryBranch));
    }
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
    if ($("mk-td-phone")) $("mk-td-phone").value = lead.phone || "";
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
  }

  function buildLeadPatch() {
    var name = ($("mk-td-name") && $("mk-td-name").value) || "";
    var phone = ($("mk-td-phone") && $("mk-td-phone").value) || "";
    var ownerEl = $("mk-td-owner");
    return {
      name: name.trim(),
      phone: phone.trim(),
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
    var phone = ($("mk-td-phone") && $("mk-td-phone").value) || "";
    if (!name.trim() || !phone.trim()) {
      alert("Vui lòng nhập Họ tên và Số điện thoại.");
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
    var recordId = root && root.getAttribute("data-record-id");
    var isEdit = root && root.getAttribute("data-mode") === "edit";
    var patch = buildLeadPatch();
    var store = window.LeadsLocalStore;
    var savePromise;

    if (store) {
      if (isEdit && recordId && typeof store.update === "function") {
        savePromise = store.update(recordId, patch);
      } else if (typeof store.create === "function") {
        savePromise = store.create(patch);
      }
    }

    Promise.resolve(savePromise)
      .then(function (lead) {
        var savedId = lead && lead.id ? lead.id : recordId;
        if (!savedId) {
          window.location.href = LIST_URL;
          return;
        }

        var leadObj = lead && lead.id ? lead : { id: savedId };
        return Promise.resolve(autoConvertToOppIfNeeded(leadObj)).then(function (res) {
          if (res && res.redirect) {
            window.location.href = res.redirect;
            return;
          }
          if (res && res.potentialId) {
            window.location.href = potentialDetailUrl(res.potentialId);
            return;
          }
          window.location.href = detailUrl(savedId);
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

    var reasonTa = $("mk-td-purchase-reason-text");
    if (reasonTa) {
      reasonTa.addEventListener("input", function () {
        state.purchaseReason = reasonTa.value;
      });
    }

    ["mk-td-save-top", "mk-td-save-aside"].forEach(function (id) {
      var b = $(id);
      if (b) b.addEventListener("click", mockSave);
    });

    var initialType = document.querySelector('.mk-td-choice[data-group="customer-type"].is-on');
    if (initialType) {
      state.customerType = initialType.getAttribute("data-tag");
    }
    syncCustomerTypePanel();

    var recordId = root.getAttribute("data-record-id");
    var boot = store && store.ready ? store.ready() : Promise.resolve();
    boot.then(function () {
      if (recordId && root.getAttribute("data-mode") === "edit") {
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
