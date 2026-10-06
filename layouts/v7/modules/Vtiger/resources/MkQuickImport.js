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

  function notify(msg, isError) {
    if (global.app && app.helper) {
      if (isError && app.helper.showErrorNotification) {
        app.helper.showErrorNotification({ message: msg });
        return;
      }
      if (!isError && app.helper.showSuccessNotification) {
        app.helper.showSuccessNotification({ message: msg });
        return;
      }
    }
    window.alert(msg);
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
        notify('Chỉ hỗ trợ file .xlsx (Khách lẻ / Miutea).', true);
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
        ('Import từ file:\n' + file.name + '\n\nTiếp tục?');
      if (!window.confirm(confirmMsg)) {
        input.value = '';
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
    });
  }

  global.MkQuickImport = { bind: bind };
})(window);
