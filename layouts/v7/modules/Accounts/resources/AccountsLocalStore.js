/**
 * Accounts (KH NQ tiềm năng) list store — CRM ModernApi.
 */
(function (root) {
  "use strict";

  var _accounts = [];
  var _readyPromise = null;

  function useApi() {
    return !!root.MK_ACC_API_READY;
  }

  function apiRequest(mode, extra) {
    var params = Object.assign(
      { module: "Accounts", action: "ModernApi", mode: mode },
      extra || {}
    );
    return new Promise(function (resolve, reject) {
      if (root.app && root.app.request && root.app.request.post) {
        root.app.request.post({ data: params }).then(function (err, res) {
          if (err) {
            reject(err);
            return;
          }
          if (!res || res.success === false) {
            reject((res && res.error) || new Error("API failed"));
            return;
          }
          resolve(res);
        });
        return;
      }
      reject(new Error("app.request unavailable"));
    });
  }

  function bootstrap() {
    if (!useApi()) {
      _accounts = [];
      return Promise.resolve(_accounts);
    }
    if (_readyPromise) {
      return _readyPromise;
    }
    _readyPromise = apiRequest("list")
      .then(function (res) {
        _accounts = Array.isArray(res.accounts) ? res.accounts : [];
        if (Array.isArray(res.assignable_users)) {
          root.MK_ACC_ASSIGNABLE_USERS = res.assignable_users;
        }
        return _accounts;
      })
      .catch(function () {
        _accounts = [];
        return _accounts;
      });
    return _readyPromise;
  }

  root.AccountsLocalStore = {
    bootstrap: bootstrap,
    getAccounts: function () {
      return _accounts.slice();
    },
    refresh: function () {
      _readyPromise = null;
      return bootstrap();
    },
    patchAccount: function (id, patch) {
      var oid = String(id || "");
      if (!patch) return null;
      for (var i = 0; i < _accounts.length; i++) {
        var a = _accounts[i];
        if (String(a.id) !== oid && String(a.crmid || "") !== oid) continue;
        Object.keys(patch).forEach(function (k) {
          a[k] = patch[k];
        });
        return a;
      }
      return null;
    },
    updateFields: function (id, fields) {
      var oid = String(id || "");
      return apiRequest("save_inline", {
        id: oid,
        record: oid,
        payload: JSON.stringify(fields || {}),
      }).then(function (res) {
        var a = (res && res.account) || {};
        var patch = Object.assign({}, fields || {}, {
          phone: a.phone != null ? a.phone : fields.phone,
          business_note: a.business_note != null ? a.business_note : fields.business_note,
          data_source: a.data_source,
          referrer: a.referrer,
          franchise_status: a.franchise_status,
          contact_status: a.contact_status,
          interaction_materials: a.interaction_materials,
        });
        root.AccountsLocalStore.patchAccount(oid, patch);
        return a;
      });
    },
    saveTags: function (id, tags) {
      var oid = String(id || "");
      return apiRequest("save_tags", {
        id: oid,
        payload: JSON.stringify({ tags: tags || [] }),
      }).then(function (res) {
        if (res && res.account) {
          var idx = -1;
          for (var i = 0; i < _accounts.length; i++) {
            if (String(_accounts[i].id) === oid || String(_accounts[i].crmid || "") === oid) {
              idx = i;
              break;
            }
          }
          if (idx >= 0) _accounts[idx] = res.account;
          else _accounts.unshift(res.account);
          return res;
        }
        if (res && res.tags) {
          root.AccountsLocalStore.patchAccount(oid, { tags: res.tags });
        }
        return res;
      });
    },
  };
})(window);
