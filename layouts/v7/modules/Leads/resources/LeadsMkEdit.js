/**
 * Tag-Driven Create / Edit Lead — cache UI (LeadsLocalStore) + tags preview.
 */
(function () {
  "use strict";

  var LIST_URL = "index.php?module=Leads&view=List&app=SALES";

  var TAG_POOLS = {
    customerType: ["individual", "company"],
    leadSource: ["facebook", "tiktok", "website", "zalo", "other_source"],
    intent: ["chua_hoc", "da_hoc", "nguyen_lieu_chuoi"],
    entry: ["mien_phi_online", "mien_phi_offline", "pcth"],
    entryBranch: ["van_hanh", "mkt", "lop_khac", "nhuong_quyen"],
    purchaseStatus: ["mua_lan_dau", "mua_lai", "khong_mua", "ngung_mua"],
    tier: ["vang", "bac", "dong"],
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
    if (state.customerType) tags.push(state.customerType);
    if (state.leadSource) tags.push(state.leadSource);
    if (state.intent) tags.push(state.intent);
    if (state.entry) tags.push(state.entry);
    if (state.entryBranch) tags.push(state.entryBranch);
    if (state.purchaseStatus) tags.push(state.purchaseStatus);
    if (state.tier) tags.push(state.tier);
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

  function setChoiceGroup(group, btn) {
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
    if (btn) setChoiceGroup(group, btn);
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
    if (state.entry) pills.push(state.entry);
    if (state.entryBranch) pills.push(state.entryBranch);
    if (!pills.length) {
      foot.hidden = true;
      foot.innerHTML = "";
      return;
    }
    foot.hidden = false;
    foot.innerHTML = pills
      .map(function (t) {
        return '<span class="mk-td-card-tag-foot">#' + t + "</span>";
      })
      .join("");
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
    if (btn) setChoiceGroup("customer-status", btn);
  }

  function applyTagsFromLead(tags, lead) {
    activateChoice("customer-type", findTag(tags, TAG_POOLS.customerType) || "individual");
    if (lead && lead.segment) activateSegment(lead.segment);
    activateChoice("lead-source", findTag(tags, TAG_POOLS.leadSource));
    activateChoice("purchase-status", findTag(tags, TAG_POOLS.purchaseStatus));
    activateChoice("customer-tier", findTag(tags, TAG_POOLS.tier));
    setSelectByTag("mk-td-intent", findTag(tags, TAG_POOLS.intent));
    var entryTag = findTag(tags, TAG_POOLS.entry);
    setSelectByTag("mk-td-entry", entryTag);
    if (entryTag === "pcth") {
      setSelectByTag("mk-td-entry-branch", findTag(tags, TAG_POOLS.entryBranch));
    }
    renderTags();
  }

  function hydrateFromStore(recordId) {
    var store = window.LeadsLocalStore;
    if (!store || !recordId || typeof store.getLead !== "function") return;
    var lead = store.getLead(recordId);
    if (!lead) return;

    if ($("mk-td-name")) $("mk-td-name").value = lead.name || "";
    if ($("mk-td-phone")) $("mk-td-phone").value = lead.phone || "";
    if ($("mk-td-cccd")) $("mk-td-cccd").value = lead.cccd || "";
    if ($("mk-td-email")) $("mk-td-email").value = lead.email || "";
    if ($("mk-td-area")) $("mk-td-area").value = lead.area || "";
    if ($("mk-td-notes")) $("mk-td-notes").value = lead.notes || "";
    if ($("mk-td-company-name")) $("mk-td-company-name").value = lead.companyName || "";
    if ($("mk-td-owner") && lead.owner) {
      var ownerEl = $("mk-td-owner");
      var found = false;
      for (var i = 0; i < ownerEl.options.length; i++) {
        if (ownerEl.options[i].value === lead.owner) {
          ownerEl.selectedIndex = i;
          found = true;
          break;
        }
      }
      if (!found) {
        var opt = document.createElement("option");
        opt.value = lead.owner;
        opt.textContent = lead.owner;
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
      area: ($("mk-td-area") && $("mk-td-area").value.trim()) || "",
      notes: ($("mk-td-notes") && $("mk-td-notes").value.trim()) || "",
      companyName:
        state.customerType === "company"
          ? (($("mk-td-company-name") && $("mk-td-company-name").value.trim()) || "")
          : "",
      owner: ownerEl ? ownerEl.value : "Linh",
      tags: collectTags(),
      last_touch: new Date().toISOString(),
    };
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
    var savedId = recordId;

    if (store) {
      if (isEdit && recordId && typeof store.update === "function") {
        store.update(recordId, patch);
      } else if (typeof store.create === "function") {
        var created = store.create(patch);
        savedId = created && created.id ? created.id : savedId;
      }
    }

    window.location.href = savedId ? detailUrl(savedId) : LIST_URL;
  }

  function init() {
    var root = $("mk-td-create");
    if (!root) return;

    root.querySelectorAll(".mk-td-choice[data-group]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        setChoiceGroup(btn.getAttribute("data-group"), btn);
      });
    });

    bindSelect("mk-td-intent", "intent");
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
    if (recordId && root.getAttribute("data-mode") === "edit") {
      hydrateFromStore(recordId);
    } else {
      renderTags();
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
