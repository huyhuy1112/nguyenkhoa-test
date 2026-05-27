/**
 * Leads Detail — SALES modern UI (demo data).
 */
(function () {
  "use strict";

  var LIST_URL = "index.php?module=Leads&view=List&app=SALES";

  var LEADS = [
    {
      id: "L004",
      name: "Phạm Quốc Dũng",
      phone: "0978 111 222",
      type: "Franchise",
      stage: "New Purchase",
      tier: "Gold",
      owner: "Hà",
      lastTouch: "11:25 21/05/2026 · 0d ago",
      nextAction: "Ký nhượng quyền khu vực",
      value: 48000000,
      tags: ["zalo", "da_hoc", "nhuong_quyen", "mua_lan_dau", "vang"],
      timeline: [
        { type: "call", label: "CALL", time: "11:25 21/05/2026", text: "Gọi tư vấn lần đầu." },
        { type: "note", label: "NOTE", time: "11:25 19/05/2026", text: "Khách hỏi lịch học buổi tối." },
        { type: "meeting", label: "MEETING", time: "11:25 16/05/2026", text: "Hẹn gặp tại văn phòng để demo." },
      ],
    },
    {
      id: "L005",
      name: "Cao Thanh Tùng",
      phone: "0904 555 013",
      type: "PCTH Program",
      stage: "Repeat Purchase",
      tier: "Gold",
      owner: "Linh",
      lastTouch: "Today · 0d ago",
      nextAction: "Demo PCTH advanced",
      value: 28400000,
      tags: ["pcth", "mkt", "mua_lai", "vang"],
      timeline: [
        { type: "call", label: "CALL", time: "10:00 20/05/2026", text: "Follow-up sau demo." },
        { type: "note", label: "NOTE", time: "09:30 18/05/2026", text: "Quan tâm nhánh Marketing." },
      ],
    },
    {
      id: "L001",
      name: "Nguyễn Văn An",
      phone: "0901 234 567",
      type: "Free Class",
      stage: "New Purchase",
      tier: "Gold",
      owner: "Linh",
      lastTouch: "1d ago",
      nextAction: "Gọi tư vấn khoá nâng cao",
      value: 12000000,
      tags: ["facebook", "chua_hoc", "mien_phi_online", "mua_lan_dau", "vang"],
      timeline: [{ type: "note", label: "NOTE", time: "14:00 19/05/2026", text: "Đăng ký lớp miễn phí online." }],
    },
  ];

  function $(id) {
    return document.getElementById(id);
  }

  function formatVnd(n) {
    try {
      return new Intl.NumberFormat("vi-VN").format(n) + " đ";
    } catch (e) {
      return String(n) + " đ";
    }
  }

  var TIER_TAG_MAP = {
    vang: { label: "Vàng", pillKey: "gold" },
    bac: { label: "Bạc", pillKey: "silver" },
    dong: { label: "Đồng", pillKey: "bronze" },
  };

  function tierFromTags(tags) {
    if (!tags || !tags.length) return null;
    var i;
    for (i = 0; i < tags.length; i++) {
      var t = String(tags[i] || "").toLowerCase();
      if (TIER_TAG_MAP[t]) {
        return TIER_TAG_MAP[t];
      }
    }
    return null;
  }

  function pillClass(kind, text) {
    var t = String(text || "").toLowerCase();
    if (kind === "type") {
      if (t.indexOf("franchise") >= 0) return "mk-ld-pill mk-ld-pill--franchise";
      if (t.indexOf("pcth") >= 0) return "mk-ld-pill mk-ld-pill--pcth";
      return "mk-ld-pill mk-ld-pill--type";
    }
    if (kind === "stage") {
      if (t.indexOf("new") >= 0) return "mk-ld-pill mk-ld-pill--stage-new";
      if (t.indexOf("repeat") >= 0) return "mk-ld-pill mk-ld-pill--stage-repeat";
      if (t.indexOf("not") >= 0) return "mk-ld-pill mk-ld-pill--stage-not";
      return "mk-ld-pill mk-ld-pill--stage";
    }
    if (kind === "tier") {
      if (t.indexOf("gold") >= 0 || t === "vang" || t.indexOf("vàng") >= 0) {
        return "mk-ld-pill mk-ld-pill--gold";
      }
      if (t.indexOf("silver") >= 0 || t === "bac" || t.indexOf("bạc") >= 0) {
        return "mk-ld-pill mk-ld-pill--silver";
      }
      if (t.indexOf("bronze") >= 0 || t === "dong" || t.indexOf("đồng") >= 0) {
        return "mk-ld-pill mk-ld-pill--bronze";
      }
      return "mk-ld-pill mk-ld-pill--bronze";
    }
    return "mk-ld-pill mk-ld-pill--tag";
  }

  function resolveLead() {
    var root = $("mk-leads-detail-root");
    var param = root && root.getAttribute("data-record-id");
    if (param) {
      var found = LEADS.filter(function (l) {
        return String(l.id) === String(param);
      })[0];
      if (found) return found;
      var num = parseInt(param, 10);
      if (!isNaN(num) && LEADS[num - 1]) return LEADS[num - 1];
    }
    return LEADS[0];
  }

  function renderPill(text, kind) {
    return '<span class="' + pillClass(kind, text) + '">' + text + "</span>";
  }

  function renderTimeline(items) {
    if (!items || !items.length) {
      return '<p class="mk-ld-timeline-empty">Chưa có hoạt động.</p>';
    }
    return items
      .map(function (it) {
        return (
          '<article class="mk-ld-timeline__item mk-ld-timeline__item--' +
          it.type +
          '">' +
          '<div class="mk-ld-timeline__icon" aria-hidden="true"></div>' +
          '<div class="mk-ld-timeline__body">' +
          '<div class="mk-ld-timeline__meta"><span class="mk-ld-timeline__type">' +
          it.label +
          '</span><span class="mk-ld-timeline__time">' +
          it.time +
          "</span></div>" +
          '<p class="mk-ld-timeline__text">' +
          it.text +
          "</p></div></article>"
        );
      })
      .join("");
  }

  function renderTags(tags, lead) {
    var host = $("mk-ld-tags");
    if (!host) return;
    host.innerHTML = tags
      .map(function (t) {
        return (
          '<span class="mk-ld-tag-chip" data-tag="' +
          t +
          '">#' +
          t +
          '<button type="button" class="mk-ld-tag-chip__x" data-remove-tag="' +
          t +
          '" aria-label="Remove">×</button></span>'
        );
      })
      .join("");
    host.querySelectorAll("[data-remove-tag]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var tag = btn.getAttribute("data-remove-tag");
        lead.tags = lead.tags.filter(function (x) {
          return x !== tag;
        });
        renderTags(lead.tags, lead);
        syncQuickInsights(lead);
      });
    });
  }

  function syncQuickInsights(lead) {
    var tierMeta = tierFromTags(lead.tags);
    var summaryPills = $("mk-ld-summary-pills");
    if (summaryPills) {
      summaryPills.innerHTML =
        renderPill(lead.type, "type") +
        renderPill(lead.stage, "stage") +
        (tierMeta ? renderPill(tierMeta.label, "tier") : "");
    }

    var insightType = $("mk-ld-insight-type");
    var insightStage = $("mk-ld-insight-stage");
    var insightTier = $("mk-ld-insight-tier");
    if (insightType) insightType.innerHTML = renderPill(lead.type, "type");
    if (insightStage) insightStage.innerHTML = renderPill(lead.stage, "stage");
    if (insightTier) {
      insightTier.innerHTML = tierMeta
        ? renderPill(tierMeta.label, "tier")
        : '<span class="mk-ld-insight-empty">—</span>';
    }
  }

  function render(lead) {
    var setText = function (id, val) {
      var el = $(id);
      if (el) el.textContent = val;
    };

    setText("mk-ld-crumb-id", lead.id);
    setText("mk-ld-name", lead.name);
    setText("mk-ld-phone", lead.phone);
    setText("mk-ld-summary-name", lead.name);
    setText("mk-ld-summary-phone", lead.phone);
    setText("mk-ld-deal-value", formatVnd(lead.value));
    setText("mk-ld-last-touch", lead.lastTouch);

    var owner = $("mk-ld-owner");
    if (owner) owner.value = lead.owner;
    var val = $("mk-ld-value");
    if (val) val.value = String(lead.value);
    var next = $("mk-ld-next-action");
    if (next) next.value = lead.nextAction;

    var summaryPills = $("mk-ld-summary-pills");
    if (summaryPills) {
      summaryPills.innerHTML =
        renderPill(lead.type, "type") +
        renderPill(lead.stage, "stage") +
        renderPill(lead.tier, "tier");
    }

    var insightType = $("mk-ld-insight-type");
    var insightStage = $("mk-ld-insight-stage");
    var insightTier = $("mk-ld-insight-tier");
    if (insightType) insightType.innerHTML = renderPill(lead.type, "type");
    if (insightStage) insightStage.innerHTML = renderPill(lead.stage, "stage");
    if (insightTier) insightTier.innerHTML = renderPill(lead.tier, "tier");

    var timeline = $("mk-ld-timeline");
    var count = $("mk-ld-timeline-count");
    if (timeline) timeline.innerHTML = renderTimeline(lead.timeline);
    if (count) count.textContent = (lead.timeline ? lead.timeline.length : 0) + " items";

    renderTags(lead.tags.slice(), lead);
  }

  function init() {
    if (!$("mk-ld-detail")) return;
    var lead = resolveLead();
    render(lead);

    var save = $("mk-ld-btn-save");
    if (save) {
      save.addEventListener("click", function () {
        alert("Lead đã lưu (UI demo).\n\n" + lead.name);
      });
    }

    var tagAdd = $("mk-ld-tag-add");
    var tagInput = $("mk-ld-tag-input");
    if (tagAdd && tagInput) {
      tagAdd.addEventListener("click", function () {
        var t = tagInput.value.trim().toLowerCase().replace(/\s+/g, "_");
        if (!t || lead.tags.indexOf(t) >= 0) return;
        lead.tags.push(t);
        tagInput.value = "";
        renderTags(lead.tags, lead);
        syncQuickInsights(lead);
      });
    }

    document.querySelectorAll("[data-log]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var ta = $("mk-ld-log-input");
        if (ta && !ta.value.trim()) {
          ta.focus();
          return;
        }
        alert("Đã ghi " + btn.textContent.trim() + " (UI demo).");
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
