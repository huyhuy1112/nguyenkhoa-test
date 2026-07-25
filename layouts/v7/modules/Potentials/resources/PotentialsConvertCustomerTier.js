/**
 * Opp → Khách hàng: modal chọn hạng Vàng / Bạc / Đồng.
 * Exposes: window.MkOppPickCustomerTier(opts) → Promise<string|null>
 *   resolves with "vang"|"bac"|"dong", or null if cancelled.
 */
(function (global) {
  "use strict";

  var STYLE_ID = "mk-opp-tier-picker-style";
  var TIERS = [
    { key: "vang", label: "Vàng", hint: "Khách hạng Vàng", tone: "gold" },
    { key: "bac", label: "Bạc", hint: "Khách hạng Bạc", tone: "silver" },
    { key: "dong", label: "Đồng", hint: "Khách hạng Đồng", tone: "bronze" },
  ];

  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;
    var css =
      ".mk-opp-tier-picker{position:fixed;inset:0;z-index:12000;display:flex;align-items:center;justify-content:center;padding:20px;}" +
      ".mk-opp-tier-picker__backdrop{position:absolute;inset:0;background:rgba(28,22,16,.45);}" +
      ".mk-opp-tier-picker__panel{position:relative;width:min(440px,100%);background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(43,33,24,.22);overflow:hidden;font-family:Inter,system-ui,sans-serif;}" +
      ".mk-opp-tier-picker__head{padding:18px 20px 12px;border-bottom:1px solid #f0ebe3;background:linear-gradient(180deg,#fffdf8,#fff);}" +
      ".mk-opp-tier-picker__eyebrow{display:block;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#a89882;margin-bottom:4px;}" +
      ".mk-opp-tier-picker__title{margin:0;font-size:17px;font-weight:700;color:#2b2118;}" +
      ".mk-opp-tier-picker__desc{margin:6px 0 0;font-size:13px;color:#6b5e50;line-height:1.45;}" +
      ".mk-opp-tier-picker__body{padding:16px 20px;display:grid;gap:10px;}" +
      ".mk-opp-tier-picker__opt{display:flex;align-items:center;gap:12px;width:100%;padding:12px 14px;border:1.5px solid #e8dfd0;border-radius:12px;background:#faf9f7;cursor:pointer;text-align:left;transition:border-color .15s,box-shadow .15s,background .15s;}" +
      ".mk-opp-tier-picker__opt:hover{border-color:#c4b49a;background:#fff;}" +
      ".mk-opp-tier-picker__opt.is-on{border-color:#2b2118;background:#fff;box-shadow:0 0 0 3px rgba(43,33,24,.08);}" +
      ".mk-opp-tier-picker__opt.is-on[data-tone=gold]{border-color:#d4a017;background:linear-gradient(180deg,#fff8e1,#fff);box-shadow:0 0 0 3px rgba(212,160,23,.18);}" +
      ".mk-opp-tier-picker__opt.is-on[data-tone=silver]{border-color:#8a93a0;background:linear-gradient(180deg,#f3f4f6,#fff);box-shadow:0 0 0 3px rgba(138,147,160,.18);}" +
      ".mk-opp-tier-picker__opt.is-on[data-tone=bronze]{border-color:#b87333;background:linear-gradient(180deg,#fff1e6,#fff);box-shadow:0 0 0 3px rgba(184,115,51,.18);}" +
      ".mk-opp-tier-picker__ic{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}" +
      ".mk-opp-tier-picker__ic--gold{background:#fef3c7;}" +
      ".mk-opp-tier-picker__ic--silver{background:#e5e7eb;}" +
      ".mk-opp-tier-picker__ic--bronze{background:#fed7aa;}" +
      ".mk-opp-tier-picker__opt-label{display:block;font-size:14px;font-weight:700;color:#2b2118;}" +
      ".mk-opp-tier-picker__opt-hint{display:block;font-size:12px;color:#8a7d6c;margin-top:1px;}" +
      ".mk-opp-tier-picker__foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 20px 18px;border-top:1px solid #f0ebe3;background:#faf9f7;}" +
      ".mk-opp-tier-picker__btn{border:0;border-radius:10px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;}" +
      ".mk-opp-tier-picker__btn--ghost{background:transparent;color:#6b5e50;}" +
      ".mk-opp-tier-picker__btn--ghost:hover{background:#f0ebe3;}" +
      ".mk-opp-tier-picker__btn--primary{background:#2b2118;color:#fff;}" +
      ".mk-opp-tier-picker__btn--primary:hover{background:#1a140f;}" +
      ".mk-opp-tier-picker__btn--primary:disabled{opacity:.45;cursor:not-allowed;}";
    var el = document.createElement("style");
    el.id = STYLE_ID;
    el.type = "text/css";
    el.appendChild(document.createTextNode(css));
    document.head.appendChild(el);
  }

  /**
   * @param {{title?:string,desc?:string,count?:number}} opts
   * @returns {Promise<string|null>}
   */
  function pickCustomerTier(opts) {
    opts = opts || {};
    ensureStyles();
    var count = opts.count || 1;
    var title = opts.title || "Chọn hạng khách hàng";
    var desc =
      opts.desc ||
      (count > 1
        ? "Chọn hạng áp dụng cho " + count + " khách hàng sẽ tạo từ cơ hội đã chọn."
        : "Hạng Vàng / Bạc / Đồng sẽ được gắn vào Khách hàng (không còn chọn trên Lead).");

    return new Promise(function (resolve) {
      var selected = null;
      var root = document.createElement("div");
      root.className = "mk-opp-tier-picker";
      root.setAttribute("role", "dialog");
      root.setAttribute("aria-modal", "true");

      var optsHtml = TIERS.map(function (t) {
        return (
          '<button type="button" class="mk-opp-tier-picker__opt" data-tier="' +
          t.key +
          '" data-tone="' +
          t.tone +
          '">' +
          '<span class="mk-opp-tier-picker__ic mk-opp-tier-picker__ic--' +
          t.tone +
          '" aria-hidden="true">👑</span>' +
          "<span>" +
          '<span class="mk-opp-tier-picker__opt-label">' +
          t.label +
          "</span>" +
          '<span class="mk-opp-tier-picker__opt-hint">' +
          t.hint +
          "</span>" +
          "</span>" +
          "</button>"
        );
      }).join("");

      root.innerHTML =
        '<div class="mk-opp-tier-picker__backdrop" data-mk-tier-cancel="1"></div>' +
        '<div class="mk-opp-tier-picker__panel">' +
        '<div class="mk-opp-tier-picker__head">' +
        '<span class="mk-opp-tier-picker__eyebrow">Cơ hội → Khách hàng</span>' +
        '<h3 class="mk-opp-tier-picker__title">' +
        title +
        "</h3>" +
        '<p class="mk-opp-tier-picker__desc">' +
        desc +
        "</p>" +
        "</div>" +
        '<div class="mk-opp-tier-picker__body">' +
        optsHtml +
        "</div>" +
        '<div class="mk-opp-tier-picker__foot">' +
        '<button type="button" class="mk-opp-tier-picker__btn mk-opp-tier-picker__btn--ghost" data-mk-tier-cancel="1">Huỷ</button>' +
        '<button type="button" class="mk-opp-tier-picker__btn mk-opp-tier-picker__btn--primary" data-mk-tier-confirm="1" disabled>Chuyển sang Khách hàng</button>' +
        "</div>" +
        "</div>";

      function close(value) {
        document.removeEventListener("keydown", onKey);
        if (root.parentNode) root.parentNode.removeChild(root);
        resolve(value);
      }

      function onKey(e) {
        if (e.key === "Escape") close(null);
      }

      root.addEventListener("click", function (e) {
        var t = e.target;
        if (!t) return;
        var cancel = t.closest ? t.closest("[data-mk-tier-cancel]") : null;
        if (cancel) {
          close(null);
          return;
        }
        var confirm = t.closest ? t.closest("[data-mk-tier-confirm]") : null;
        if (confirm) {
          if (selected) close(selected);
          return;
        }
        var opt = t.closest ? t.closest(".mk-opp-tier-picker__opt") : null;
        if (opt) {
          selected = opt.getAttribute("data-tier");
          root.querySelectorAll(".mk-opp-tier-picker__opt").forEach(function (el) {
            el.classList.toggle("is-on", el === opt);
          });
          var btn = root.querySelector("[data-mk-tier-confirm]");
          if (btn) btn.disabled = !selected;
        }
      });

      document.addEventListener("keydown", onKey);
      document.body.appendChild(root);
    });
  }

  global.MkOppPickCustomerTier = pickCustomerTier;
})(window);
