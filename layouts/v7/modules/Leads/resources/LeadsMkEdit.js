/**
 * Tag-Driven Create Lead — UI only (tags preview + mock save).
 */
(function () {
  "use strict";

  var LIST_URL = "index.php?module=Leads&view=List&app=SALES";

  var state = {
    customerType: null,
    leadSource: null,
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
        "WORKFLOW TRIGGER: " +
        n +
        " tag(s) → khớp script & automation tương ứng.";
    }
  }

  function setChoiceGroup(group, btn) {
    document.querySelectorAll('.mk-td-choice[data-group="' + group + '"]').forEach(function (el) {
      el.classList.toggle("is-on", el === btn);
    });
    var tag = btn.getAttribute("data-tag");
    if (group === "customer-type") state.customerType = tag;
    else if (group === "lead-source") state.leadSource = tag;
    else if (group === "purchase-status") {
      state.purchaseStatus = tag;
      syncPurchaseReasonPanel(btn);
    } else if (group === "customer-tier") state.tier = tag;
    renderTags();
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

  function mockSave() {
    var name = ($("mk-td-name") && $("mk-td-name").value) || "";
    var phone = ($("mk-td-phone") && $("mk-td-phone").value) || "";
    if (!name.trim() || !phone.trim()) {
      alert("Vui lòng nhập Họ tên và SĐT (UI demo).");
      return;
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
    var tags = collectTags();
    alert(
      "Lead đã lưu (UI demo).\n\n" +
        "Tên: " +
        name.trim() +
        "\nTags: " +
        (tags.length ? tags.map(function (t) { return "#" + t; }).join(", ") : "(none)") +
        (state.purchaseReason ? "\nLý do: " + state.purchaseReason : "")
    );
    window.location.href = LIST_URL;
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

    renderTags();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
