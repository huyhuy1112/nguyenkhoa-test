/**
 * Shared Last Touch Call modal + panel refresh (Opp / Contacts / reusable).
 * Config: { module, listModuleClass, findRow, onLogged }
 */
(function (global) {
  "use strict";

  function esc(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function decodeHtmlEntities(s) {
    var ta = document.createElement("textarea");
    ta.innerHTML = String(s == null ? "" : s);
    return ta.value;
  }

  function lastTouchCallLogHtml(row, escFn) {
    var e = typeof escFn === "function" ? escFn : esc;
    var lt = row && row.lastTouchCalls ? row.lastTouchCalls : null;
    var calls = lt && lt.calls ? lt.calls : [];
    if (!calls.length) {
      return '<span class="mk-leads-muted">Chưa có cuộc gọi</span>';
    }
    return (
      '<div class="mk-leads-call-log">' +
      calls
        .map(function (c) {
          var line =
            c.label ||
            (c.called_at_label || "") +
              " Call #" +
              (c.n || "") +
              " Kết quả: " +
              (c.result || "");
          if (c.note && String(line).indexOf("Ghi chú:") < 0) {
            line += " Ghi chú: " + c.note;
          }
          return (
            '<div class="mk-leads-call-log__item">' + e(decodeHtmlEntities(line)) + "</div>"
          );
        })
        .join("") +
      "</div>"
    );
  }

  function createController(cfg) {
    var moduleName = cfg.module || "Potentials";
    var modalId = "mk-lt-modal-" + moduleName.toLowerCase();
    var prefix = modalId;

    function ensureModal() {
      var existing = document.getElementById(modalId);
      if (existing) return existing;
      var tip =
        cfg.tipHtml ||
        "Chọn <strong>Nghe máy</strong> để kết thúc chuỗi gọi. <strong>Không nghe máy</strong> → nhắc gọi lần sau sau khoảng 5 giờ (chuông Thông báo).";
      var wrap = document.createElement("div");
      wrap.id = modalId;
      wrap.className = "mk-lead-lt-modal mk-leads-lt-modal mk-lt-modal--modern";
      wrap.hidden = true;
      wrap.setAttribute("aria-hidden", "true");
      wrap.innerHTML =
        '<div class="mk-lead-lt-modal__backdrop" data-mk-lt-close="1"></div>' +
        '<div class="mk-lead-lt-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="' +
        prefix +
        '-title">' +
        '<div class="mk-lead-lt-modal__head">' +
        '<div class="mk-lead-lt-modal__head-main">' +
        '<span class="mk-lead-lt-modal__icon" aria-hidden="true"><i class="fa fa-phone"></i></span>' +
        "<div>" +
        '<h3 id="' +
        prefix +
        '-title">Ghi Last Touch — Call</h3>' +
        '<p class="mk-lead-lt-modal__sub">Theo dõi chuỗi tối đa 3 cuộc gọi</p>' +
        "</div></div>" +
        '<button type="button" class="mk-lead-lt-modal__x" data-mk-lt-close="1" aria-label="Đóng">&times;</button>' +
        "</div>" +
        '<div class="mk-lead-lt-modal__body">' +
        '<div class="mk-lead-lt-modal__meta-card" id="' +
        prefix +
        '-meta"></div>' +
        '<label class="mk-lead-lt-modal__label" for="' +
        prefix +
        '-result">Kết quả cuộc gọi</label>' +
        '<div class="mk-lead-lt-modal__select-wrap">' +
        '<select id="' +
        prefix +
        '-result" class="mk-lead-lt-modal__select inputElement">' +
        '<option value="Không nghe máy">Không nghe máy</option>' +
        '<option value="Nghe máy">Nghe máy</option>' +
        "</select></div>" +
        '<label class="mk-lead-lt-modal__label" for="' +
        prefix +
        '-note">Ghi chú</label>' +
        '<textarea id="' +
        prefix +
        '-note" class="mk-lead-lt-modal__note inputElement" rows="6" placeholder="Ví dụ: Khách quan tâm lớp học / hẹn gọi lại"></textarea>' +
        '<p class="mk-lead-lt-modal__tip">' +
        tip +
        "</p>" +
        "</div>" +
        '<div class="mk-lead-lt-modal__foot">' +
        '<button type="button" class="mk-lead-lt-modal__btn mk-lead-lt-modal__btn--ghost" data-mk-lt-close="1">Hủy</button>' +
        '<button type="button" class="mk-lead-lt-modal__btn mk-lead-lt-modal__btn--primary" id="' +
        prefix +
        '-save"><i class="fa fa-check" aria-hidden="true"></i> Lưu cuộc gọi</button>' +
        "</div></div>";
      document.body.appendChild(wrap);
      wrap.addEventListener("click", function (e) {
        var t = e.target;
        if (t && t.getAttribute && t.getAttribute("data-mk-lt-close") === "1") {
          e.preventDefault();
          closeModal();
        }
      });
      var saveBtn = document.getElementById(prefix + "-save");
      if (saveBtn) {
        saveBtn.addEventListener("click", function (e) {
          e.preventDefault();
          submitCall();
        });
      }
      return wrap;
    }

    function closeModal() {
      var modal = document.getElementById(modalId);
      if (!modal) return;
      modal.hidden = true;
      modal.setAttribute("aria-hidden", "true");
      modal._mkRecordId = "";
      modal._mkCallBtn = null;
    }

    function openModal(btn) {
      var panel = btn && btn.closest ? btn.closest(".mk-so-inline-detail") : null;
      var host = panel ? panel.querySelector('[data-role="last-touch"]') : null;
      var recordId = String(
        (btn && btn.getAttribute("data-record-id")) ||
          (host && host.getAttribute("data-record-id")) ||
          (panel && panel.getAttribute("data-record-id")) ||
          ""
      );
      if (!recordId) return;
      if (
        (btn && (btn.disabled || btn.classList.contains("is-locked"))) ||
        (host && host.getAttribute("data-lt-locked") === "1")
      ) {
        window.alert(
          (btn && btn.getAttribute("data-lt-hint")) ||
            (host && host.getAttribute("data-lt-hint")) ||
            "Không thể ghi thêm cuộc gọi Last Touch."
        );
        return;
      }
      var modal = ensureModal();
      var nextN =
        parseInt(
          (btn && btn.getAttribute("data-lt-next")) ||
            (host && host.getAttribute("data-lt-next")) ||
            "1",
          10
        ) || 1;
      var reminder = String(
        (btn && btn.getAttribute("data-lt-reminder")) ||
          (host && host.getAttribute("data-lt-reminder")) ||
          ""
      ).trim();
      var meta = document.getElementById(prefix + "-meta");
      if (meta) {
        meta.innerHTML =
          '<span class="mk-lead-lt-modal__meta-n">Call #' +
          esc(String(nextN)) +
          "</span>" +
          '<span class="mk-lead-lt-modal__meta-text">Khoảng 5 giờ giữa các lần gọi' +
          (reminder ? " · Nhắc lần trước: " + esc(reminder) : "") +
          ".</span>";
      }
      var resultEl = document.getElementById(prefix + "-result");
      var noteEl = document.getElementById(prefix + "-note");
      if (resultEl) resultEl.value = "Không nghe máy";
      if (noteEl) noteEl.value = "";
      modal._mkRecordId = recordId;
      modal._mkCallBtn = btn;
      modal.hidden = false;
      modal.setAttribute("aria-hidden", "false");
      if (noteEl && noteEl.focus) {
        setTimeout(function () {
          noteEl.focus();
        }, 40);
      }
    }

    function applyToPanel(panelOrBtn, lt) {
      if (!lt) return;
      var panel =
        panelOrBtn && panelOrBtn.classList && panelOrBtn.classList.contains("mk-so-inline-detail")
          ? panelOrBtn
          : panelOrBtn && panelOrBtn.closest
            ? panelOrBtn.closest(".mk-so-inline-detail")
            : null;
      if (!panel) return;
      var host = panel.querySelector('[data-role="last-touch"]');
      if (!host) return;
      var canAdd = lt.can_add !== false;
      var nextN = lt.next_n || 1;
      var count = typeof lt.count === "number" ? lt.count : (lt.calls || []).length;
      var max = lt.max_calls || 3;
      var SHORT =
        "Call #1 → 5 giờ → #2 → #3. Không nghe máy: nhắc sau 5 giờ. Nghe máy → dừng chuỗi gọi.";
      var hint = lt.count > 0 || lt.can_add === false ? lt.hint || SHORT : SHORT;
      var hintFull = lt.hint || SHORT;
      var reminder = lt.reminder_at_label || "";
      host.setAttribute("data-lt-next", String(nextN));
      host.setAttribute("data-lt-hint", hintFull);
      host.setAttribute("data-lt-count", String(count));
      host.setAttribute("data-lt-max", String(max));
      if (reminder) host.setAttribute("data-lt-reminder", reminder);
      else host.removeAttribute("data-lt-reminder");
      if (canAdd) host.removeAttribute("data-lt-locked");
      else host.setAttribute("data-lt-locked", "1");
      var badge = host.querySelector('[data-role="lt-badge"]');
      if (badge) {
        badge.textContent = count + "/" + max;
        badge.className =
          "mk-so-inline-detail__last-touch-badge" + (canAdd ? " is-open" : " is-done");
      }
      var hintEl = host.querySelector('[data-role="lt-hint"]');
      if (hintEl) {
        hintEl.textContent = hint;
        hintEl.setAttribute("title", hintFull);
      }
      var list = host.querySelector('[data-role="lt-list"]');
      if (list) {
        var calls = lt.calls || [];
        if (!calls.length) {
          list.innerHTML =
            '<li class="mk-so-inline-detail__last-touch-empty">Chưa có Call #1 — bấm “Ghi cuộc gọi”.</li>';
        } else {
          list.innerHTML = calls
            .map(function (c) {
              var line =
                c.label ||
                (c.called_at_label || "") +
                  " Call #" +
                  (c.n || "") +
                  " Kết quả: " +
                  (c.result || "");
              if (c.note && String(line).indexOf("Ghi chú:") < 0) {
                line += " Ghi chú: " + c.note;
              }
              return (
                '<li class="mk-so-inline-detail__last-touch-item">' +
                '<span class="mk-so-inline-detail__last-touch-n">Call #' +
                esc(String(c.n || "")) +
                "</span>" +
                '<span class="mk-so-inline-detail__last-touch-text">' +
                esc(decodeHtmlEntities(line)) +
                "</span></li>"
              );
            })
            .join("");
        }
      }
      var btn = host.querySelector(".mk-so-inline-detail__call-btn");
      if (!btn) return;
      btn.setAttribute("data-lt-next", String(nextN));
      btn.setAttribute("data-lt-hint", hint);
      if (reminder) btn.setAttribute("data-lt-reminder", reminder);
      else btn.removeAttribute("data-lt-reminder");
      var label = btn.querySelector("span");
      if (canAdd) {
        btn.classList.remove("is-locked");
        btn.disabled = false;
        btn.removeAttribute("aria-disabled");
        btn.setAttribute("title", "Ghi cuộc gọi Last Touch #" + nextN);
        if (label) label.textContent = "Ghi cuộc gọi";
      } else {
        btn.classList.add("is-locked");
        btn.disabled = true;
        btn.setAttribute("aria-disabled", "true");
        if (hint) {
          btn.setAttribute("data-lt-hint", hint);
          btn.setAttribute("title", hint);
        }
        if (label) label.textContent = "Đã đủ gọi";
      }
    }

    function submitCall() {
      var modal = document.getElementById(modalId);
      var recordId = modal && modal._mkRecordId;
      var callBtn = modal && modal._mkCallBtn;
      if (!recordId) return;
      var resultEl = document.getElementById(prefix + "-result");
      var noteEl = document.getElementById(prefix + "-note");
      var saveBtn = document.getElementById(prefix + "-save");
      var result = resultEl ? String(resultEl.value || "").trim() : "";
      var note = noteEl ? String(noteEl.value || "").trim() : "";
      if (!result) {
        window.alert("Vui lòng chọn kết quả cuộc gọi.");
        return;
      }
      if (saveBtn) saveBtn.disabled = true;
      if (window.app && app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
      var postData = {
        module: moduleName,
        action: "ModernApi",
        mode: "last_touch_call_log",
        id: recordId,
        record: recordId,
        call_result: result,
        note: note,
      };
      function done(err, res) {
        if (saveBtn) saveBtn.disabled = false;
        if (window.app && app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        if (err || !res || res.success === false) {
          var msg =
            (err && err.message) ||
            (res && (res.error || res.message)) ||
            "Không ghi được cuộc gọi Last Touch.";
          if (typeof msg === "object" && msg.message) msg = msg.message;
          if (window.app && app.helper && app.helper.showErrorNotification) {
            app.helper.showErrorNotification({ message: String(msg) });
          } else {
            window.alert(String(msg));
          }
          return;
        }
        closeModal();
        var lt = res.lastTouchCalls || res;
        if (typeof cfg.onLogged === "function") {
          cfg.onLogged(recordId, lt, res, callBtn);
        } else {
          applyToPanel(callBtn, lt);
        }
        if (window.app && app.helper && app.helper.showSuccessNotification) {
          app.helper.showSuccessNotification({
            message:
              (lt && lt.logged && lt.logged.label) ||
              (res.logged && res.logged.label) ||
              "Đã ghi Last Touch Call.",
          });
        }
      }
      if (window.app && app.request && app.request.post) {
        app.request.post({ data: postData }).then(done);
      } else {
        fetch("index.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: Object.keys(postData)
            .map(function (k) {
              return encodeURIComponent(k) + "=" + encodeURIComponent(postData[k]);
            })
            .join("&"),
          credentials: "same-origin",
        })
          .then(function (r) {
            return r.json();
          })
          .then(function (r) {
            done(null, r && r.result ? r.result : r);
          })
          .catch(function () {
            done({ message: "Không kết nối được máy chủ." }, null);
          });
      }
    }

    function bindClick() {
      document.addEventListener(
        "click",
        function (e) {
          var callBtn =
            e.target && e.target.closest && e.target.closest(".mk-so-inline-detail__call-btn");
          if (!callBtn) return;
          var panel = callBtn.closest(".mk-so-inline-detail");
          if (!panel) return;
          var mod =
            callBtn.getAttribute("data-lt-module") ||
            panel.getAttribute("data-module") ||
            "";
          if (String(mod) !== String(moduleName)) return;
          e.preventDefault();
          e.stopPropagation();
          openModal(callBtn);
        },
        true
      );
    }

    bindClick();

    return {
      openModal: openModal,
      closeModal: closeModal,
      applyToPanel: applyToPanel,
      lastTouchCallLogHtml: lastTouchCallLogHtml,
    };
  }

  global.MkLastTouchCall = {
    create: createController,
    lastTouchCallLogHtml: lastTouchCallLogHtml,
  };
})(window);
