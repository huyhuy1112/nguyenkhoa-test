/**
 * One-click list Import (warehouse-style): button → file picker → SimpleImport action.
 * Used by Contacts (Kiot Excel) and Accounts (auto-map SimpleImport).
 */
(function (global) {
  'use strict';

  function csrfToken() {
    try {
      if (typeof csrfMagicName !== 'undefined' && typeof csrfMagicToken !== 'undefined') {
        return { name: csrfMagicName, token: csrfMagicToken };
      }
    } catch (e) {}
    try {
      if (global.app && typeof app.getCsrfToken === 'function') {
        return { name: '__vtrftk', token: app.getCsrfToken() };
      }
    } catch (e2) {}
    return null;
  }

  function escapeHtml(s) {
    return String(s || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  function notify(msg, isError) {
    var safe = escapeHtml(msg).replace(/\\n/g, "\n").replace(/\n/g, "<br>");
    if (global.app && app.helper) {
      if (isError && app.helper.showErrorNotification) {
        app.helper.showErrorNotification({ message: safe });
        return;
      }
      if (!isError && app.helper.showSuccessNotification) {
        app.helper.showSuccessNotification({ message: safe });
        return;
      }
    }
    window.alert(msg);
  }

  function ensureConfirmStyles() {
    if (document.getElementById("mkQuickImportConfirmStyle")) {
      return;
    }
    var style = document.createElement("style");
    style.id = "mkQuickImportConfirmStyle";
    style.textContent =
      "#mkQuickImportConfirm{position:fixed;inset:0;z-index:10050;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.45);padding:24px;}" +
      "#mkQuickImportConfirm .mk-qi-card{width:min(440px,100%);background:#fff;border-radius:16px;box-shadow:0 20px 50px rgba(15,23,42,.18);padding:22px 22px 18px;font-family:inherit;color:#111827;}" +
      "#mkQuickImportConfirm h3{margin:0 0 8px;font-size:18px;font-weight:700;color:#14532d;}" +
      "#mkQuickImportConfirm p{margin:0 0 8px;font-size:14px;line-height:1.45;color:#374151;}" +
      "#mkQuickImportConfirm .mk-qi-file{margin:10px 0 16px;padding:10px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-size:13px;color:#166534;word-break:break-all;}" +
      "#mkQuickImportConfirm .mk-qi-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:8px;}" +
      "#mkQuickImportConfirm button{border-radius:999px;padding:8px 16px;font-size:14px;font-weight:600;cursor:pointer;border:1px solid transparent;}" +
      "#mkQuickImportConfirm .mk-qi-cancel{background:#fff;border-color:#d1d5db;color:#374151;}" +
      "#mkQuickImportConfirm .mk-qi-ok{background:#15803d;color:#fff;}";
    document.head.appendChild(style);
  }

  function confirmImport(message, fileName) {
    ensureConfirmStyles();
    return new Promise(function (resolve) {
      var root = document.createElement("div");
      root.id = "mkQuickImportConfirm";
      var lines = String(message || "")
        .split(/\n+/)
        .map(function (line) { return line.trim(); })
        .filter(function (line) {
          return line && line !== "Tiếp tục?";
        });
      var title = lines.shift() || "Nhập dữ liệu từ Excel";
      var body = lines.map(function (line) {
        return "<p>" + escapeHtml(line) + "</p>";
      }).join("");
      root.innerHTML =
        '<div class="mk-qi-card" role="dialog" aria-modal="true">' +
        "<h3>" + escapeHtml(title) + "</h3>" +
        body +
        '<div class="mk-qi-file">' + escapeHtml(fileName || "") + "</div>" +
        '<div class="mk-qi-actions">' +
        '<button type="button" class="mk-qi-cancel">Huỷ</button>' +
        '<button type="button" class="mk-qi-ok">Tiếp tục</button>' +
        "</div></div>";
      function close(ok) {
        if (root.parentNode) {
          root.parentNode.removeChild(root);
        }
        resolve(!!ok);
      }
      root.addEventListener("click", function (e) {
        if (e.target === root) {
          close(false);
        }
      });
      root.querySelector(".mk-qi-cancel").addEventListener("click", function () { close(false); });
      root.querySelector(".mk-qi-ok").addEventListener("click", function () { close(true); });
      document.body.appendChild(root);
      root.querySelector(".mk-qi-ok").focus();
    });
  }

  function bind(opts) {
    opts = opts || {};
    var btn = document.getElementById(opts.buttonId);
    var input = document.getElementById(opts.fileInputId);
    var moduleName = opts.module || '';
    if (!btn || !input || !moduleName) {
      return;
    }
    if (btn.getAttribute('data-mk-quick-bound') === '1') {
      return;
    }
    btn.setAttribute('data-mk-quick-bound', '1');

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (btn.getAttribute('disabled') === 'disabled') {
        return;
      }
      input.value = '';
      input.click();
    });

    input.addEventListener('change', function () {
      var file = input.files && input.files[0] ? input.files[0] : null;
      if (!file) {
        return;
      }
      var name = String(file.name || '').toLowerCase();
      var acceptXlsxOnly = !!opts.xlsxOnly;
      if (acceptXlsxOnly && !/\.xlsx$/i.test(name)) {
        notify(opts.xlsxRejectMessage || 'Chỉ hỗ trợ file .xlsx (Khách lẻ / Miutea).', true);
        input.value = '';
        return;
      }
      if (!acceptXlsxOnly && !/\.(xlsx|xls|csv)$/i.test(name)) {
        notify('Chỉ hỗ trợ file .xlsx / .xls / .csv.', true);
        input.value = '';
        return;
      }

      var confirmMsg =
        opts.confirmMessage ||
        ("Import từ file:\n" + file.name + "\n\nTiếp tục?");
      confirmImport(confirmMsg, file.name).then(function (ok) {
        if (!ok) {
          input.value = "";
          return;
        }
        startUpload();
      });
    });

    function startUpload() {
      var file = input.files && input.files[0] ? input.files[0] : null;
      if (!file) {
        return;
      }

      var labelEl = btn.querySelector('.mk-leads-btn__txt');
      var prevLabel = labelEl ? labelEl.textContent : btn.textContent;
      btn.setAttribute('disabled', 'disabled');
      if (labelEl) {
        labelEl.textContent = 'Đang import…';
      }

      var fd = new FormData();
      fd.append('module', moduleName);
      fd.append('action', 'SimpleImport');
      fd.append('import_file', file);
      var csrf = csrfToken();
      if (csrf && csrf.name && csrf.token) {
        fd.append(csrf.name, csrf.token);
      }

      var done = function () {
        btn.removeAttribute('disabled');
        if (labelEl) {
          labelEl.textContent = prevLabel || 'Import';
        }
        input.value = '';
      };

      fetch('index.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (res) {
          return res.text().then(function (text) {
            var data = null;
            try {
              data = JSON.parse(text);
            } catch (eParse) {
              throw new Error(text ? String(text).substring(0, 200) : 'Import thất bại.');
            }
            return data;
          });
        })
        .then(function (data) {
          var result = (data && data.result) || data || {};
          if (data && data.success === false) {
            throw new Error((data.error && data.error.message) || data.message || 'Import thất bại.');
          }
          if (result.success === false && !result.imported && !result.updated) {
            throw new Error(result.message || 'Import thất bại.');
          }
          var msg = result.message || 'Import hoàn tất.';
          notify(msg, false);
          if (typeof opts.onDone === 'function') {
            opts.onDone(result);
          } else {
            window.location.reload();
          }
          done();
        })
        .catch(function (err) {
          notify((err && err.message) || 'Import thất bại.', true);
          done();
        });
    }
  }

  global.MkQuickImport = { bind: bind };
})(window);
