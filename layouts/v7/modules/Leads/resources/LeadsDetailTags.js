/**
 * Ensure Vtiger/detail tags expose stable data-tag keys for LeadsMkTagPalette.css.
 */
(function (root, $) {
  "use strict";

	var TAG_LABEL_ALIASES = {
    silver: "bac",
    gold: "vang",
    bronze: "dong",
    ca_nhan: "individual",
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
  }

  function paintAll(root) {
    var scope = root && root.querySelector ? root : document;
    var tags = scope.querySelectorAll(
      ".mk-lead-detail-hero__tags .tag, .mk-lead-detail-hero__tags .mk-lead-detail-tag-chip, .mk-opportunity-detail-hero__tags .tag, .mk-contact-detail-hero__tags .tag, .tagContainer .detailTagList .tag, .mk-opp-tags-modal .tag, .mk-contact-tags-modal .tag, .myModal .mk-opp-tags-modal .tag, .myModal .mk-contact-tags-modal .tag, .modal .mk-opp-tags-modal .tag, .modal .mk-contact-tags-modal .tag, #mk-ld-ui-tag-list .tag, #mk-ld-ui-tag-list .mk-lead-detail-tag-chip"
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
