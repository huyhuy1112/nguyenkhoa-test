/**
 * Paint Vtiger detail tags with the same palette as Lead Create/Edit (LeadsMkEdit.css).
 */
(function (root, $) {
  "use strict";

  var TAG_LABEL_ALIASES = {
    silver: "bac",
    gold: "vang",
    bronze: "dong",
  };

  var TAG_COLORS = {
    individual: { bg: "#dbeafe", border: "#93c5fd", color: "#1d4ed8" },
    company: { bg: "#ede9fe", border: "#c4b5fd", color: "#6d28d9" },
    vang: { bg: "#fef9c3", border: "#facc15", color: "#713f12" },
    bac: { bg: "#f1f5f9", border: "#94a3b8", color: "#334155" },
    dong: { bg: "#ffedd5", border: "#fb923c", color: "#9a3412" },
    facebook: { bg: "#dbeafe", border: "#60a5fa", color: "#1d4ed8" },
    tiktok: { bg: "#fce7f3", border: "#f9a8d4", color: "#9d174d" },
    website: { bg: "#d1fae5", border: "#6ee7b7", color: "#047857" },
    zalo: { bg: "#e0f2fe", border: "#7dd3fc", color: "#0369a1" },
    mua_lan_dau: { bg: "#d1fae5", border: "#6ee7b7", color: "#047857" },
    mua_lai: { bg: "#1c1917", border: "#1c1917", color: "#fbbf24" },
    mua_it_lai: { bg: "#991b1b", border: "#7f1d1d", color: "#fff" },
    khong_mua: { bg: "#fee2e2", border: "#fca5a5", color: "#b91c1c" },
    ngung_mua: { bg: "#78350f", border: "#78350f", color: "#fde68a" },
    chua_hoc: { bg: "#eff6ff", border: "#bfdbfe", color: "#1d4ed8" },
    da_hoc: { bg: "#eff6ff", border: "#bfdbfe", color: "#1d4ed8" },
    nguyen_lieu_chuoi: { bg: "#eff6ff", border: "#bfdbfe", color: "#1d4ed8" },
    mien_phi_online: { bg: "#e0e7ff", border: "#a5b4fc", color: "#4338ca" },
    mien_phi_offline: { bg: "#e0e7ff", border: "#a5b4fc", color: "#4338ca" },
    pcth: { bg: "#f3f4f6", border: "#d1d5db", color: "#374151" },
    kv1: { bg: "#ecfdf5", border: "#6ee7b7", color: "#047857" },
    kv2: { bg: "#ecfdf5", border: "#6ee7b7", color: "#047857" },
    kv3: { bg: "#ecfdf5", border: "#6ee7b7", color: "#047857" },
    van_hanh: { bg: "#ede9fe", border: "#c4b5fd", color: "#6d28d9" },
    mkt: { bg: "#ede9fe", border: "#c4b5fd", color: "#6d28d9" },
    lop_khac: { bg: "#ede9fe", border: "#c4b5fd", color: "#6d28d9" },
    nhuong_quyen: { bg: "#ffe4e6", border: "#fda4af", color: "#be123c" },
    moi_quen: { bg: "#dbeafe", border: "#93c5fd", color: "#1d4ed8" },
    da_co_quan_he: { bg: "#ffe4e6", border: "#fda4af", color: "#be123c" },
    chua_mqbh: { bg: "#ede9fe", border: "#c4b5fd", color: "#6d28d9" },
    da_tg_free: { bg: "#e0e7ff", border: "#a5b4fc", color: "#4338ca" },
    thu_3: { bg: "#fecaca", border: "#f87171", color: "#991b1b" },
    lop_online: { bg: "#dbeafe", border: "#93c5fd", color: "#1d4ed8" },
    doi_lich: { bg: "#e2e8f0", border: "#cbd5e1", color: "#334155" },
    dang_tu_van: { bg: "#e0f2fe", border: "#7dd3fc", color: "#0f172a" },
    dung_cham_soc: { bg: "#dc2626", border: "#b91c1c", color: "#fff" },
    da_ky_quy: { bg: "#dbeafe", border: "#93c5fd", color: "#1d4ed8" },
    dang_cham_soc: { bg: "#ecfdf5", border: "#6ee7b7", color: "#047857" },
    kh_can_nhac: { bg: "#1d4ed8", border: "#1e40af", color: "#fff" },
    l1: { bg: "#e0f2fe", border: "#7dd3fc", color: "#0369a1" },
    l2: { bg: "#ede9fe", border: "#c4b5fd", color: "#6d28d9" },
    chua_990k: { bg: "#fff7ed", border: "#fdba74", color: "#c2410c" },
    da_990k: { bg: "#dcfce7", border: "#86efac", color: "#166534" },
  };

  function slugify(label) {
    var s = String(label || "").trim().toLowerCase();
    if (!s) return "";
    if (s.charAt(0) === "#") s = s.slice(1);
    try {
      s = s.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    } catch (e) {}
    return s
      .replace(/đ/g, "d")
      .replace(/[^a-z0-9]+/g, "_")
      .replace(/^_+|_+$/g, "")
      .replace(/_+/g, "_");
  }

  function resolveSlug(label, existing) {
    var slug = slugify(existing || label);
    if (!slug) return "";
    return TAG_LABEL_ALIASES[slug] || slug;
  }

  function paintTag(el) {
    if (!el || !el.classList) return;
    if (!el.classList.contains("tag") && !el.classList.contains("mk-lead-detail-tag-chip")) return;
    var labelEl = el.querySelector(".tagLabel");
    var label = labelEl ? labelEl.textContent : "";
    var slug = resolveSlug(label, el.getAttribute("data-tag") || el.getAttribute("title") || "");
    if (!slug) return;
    el.setAttribute("data-tag", slug);
    var palette = TAG_COLORS[slug];
    if (!palette) return;
    el.style.setProperty("background", palette.bg, "important");
    el.style.setProperty("background-color", palette.bg, "important");
    el.style.setProperty("border-color", palette.border, "important");
    el.style.setProperty("color", palette.color, "important");
    var icons = el.querySelectorAll("i");
    for (var i = 0; i < icons.length; i++) {
      icons[i].style.setProperty("color", palette.color, "important");
    }
  }

  function paintAll(root) {
    var scope = root && root.querySelector ? root : document;
    var tags = scope.querySelectorAll(
      ".mk-lead-detail-hero__tags .tag, .mk-lead-detail-hero__tags .mk-lead-detail-tag-chip, .mk-opportunity-detail-hero__tags .tag, .mk-contact-detail-hero__tags .tag, .tagContainer .detailTagList .tag, .mk-opp-tags-modal .tag, .mk-contact-tags-modal .tag, .myModal .mk-opp-tags-modal .tag, .myModal .mk-contact-tags-modal .tag, .modal .mk-opp-tags-modal .tag, .modal .mk-contact-tags-modal .tag, #mk-ld-ui-tag-list .tag"
    );
    for (var i = 0; i < tags.length; i++) {
      paintTag(tags[i]);
    }
  }

  function schedulePaint() {
    paintAll(document);
    if ($) {
      $(".mk-lead-detail-hero__tags .tag, .mk-opportunity-detail-hero__tags .tag, .mk-contact-detail-hero__tags .tag").each(function () {
        paintTag(this);
      });
    }
  }

  function boot() {
    schedulePaint();
    var tries = 0;
    var timer = setInterval(function () {
      schedulePaint();
      tries += 1;
      if (tries >= 12) clearInterval(timer);
    }, 250);
    try {
      var mo = new MutationObserver(function () {
        schedulePaint();
      });
      mo.observe(document.body || document.documentElement, { childList: true, subtree: true });
    } catch (e) {}
    if ($) {
      $(document).on("click", "#addTagTriggerer, .saveTag, .deleteTag, .cancelSaveTag, .moreTags", function () {
        setTimeout(schedulePaint, 80);
        setTimeout(schedulePaint, 400);
      });
    }
  }

  if ($) {
    $(boot);
  } else if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }

  root.MkLeadsDetailTags = { paintAll: schedulePaint, paintTag: paintTag };
})(typeof window !== "undefined" ? window : this, typeof jQuery !== "undefined" ? jQuery : null);
