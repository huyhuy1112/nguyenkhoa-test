/**
 * ServiceContracts list store — CRM API (MK_SC_API_READY).
 */
(function (root) {
  "use strict";

  var _contracts = [];
  var _readyPromise = null;

  function useApi() {
    return !!root.MK_SC_API_READY;
  }

  function apiRequest(mode, extra) {
    var params = Object.assign(
      { module: "ServiceContracts", action: "ModernApi", mode: mode },
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
      _contracts = [];
      return Promise.resolve(_contracts);
    }
    if (_readyPromise) {
      return _readyPromise;
    }
    _readyPromise = apiRequest("list")
      .then(function (res) {
        _contracts = Array.isArray(res.contracts) ? res.contracts : [];
        if (Array.isArray(res.assignable_users)) {
          root.MK_SC_ASSIGNABLE_USERS = res.assignable_users;
        }
        return _contracts;
      })
      .catch(function () {
        _contracts = [];
        return _contracts;
      });
    return _readyPromise;
  }

  root.ServiceContractsLocalStore = {
    bootstrap: bootstrap,
    getContracts: function () {
      return _contracts.slice();
    },
    refresh: function () {
      _readyPromise = null;
      return bootstrap();
    },
    remove: function (id) {
      var oid = String(id || "");
      return apiRequest("delete", { id: oid }).then(function () {
        _contracts = _contracts.filter(function (c) {
          return String(c.id) !== oid && String(c.crmid || "") !== oid;
        });
      });
    },
    patchContract: function (id, patch) {
      var oid = String(id || "");
      if (!patch) return null;
      for (var i = 0; i < _contracts.length; i++) {
        var c = _contracts[i];
        if (String(c.id) !== oid && String(c.crmid || "") !== oid) continue;
        Object.keys(patch).forEach(function (k) {
          c[k] = patch[k];
        });
        return c;
      }
      return null;
    },
    saveTags: function (id, tags) {
      var oid = String(id || "");
      return apiRequest("save_tags", {
        record: oid,
        tags: JSON.stringify(tags || []),
      }).then(function (res) {
        var next = (res && res.tags) || tags || [];
        root.ServiceContractsLocalStore.patchContract(oid, { tags: next });
        return next;
      });
    },
    saveNextAction: function (id, nextAction) {
      var oid = String(id || "");
      return apiRequest("save_next_action", {
        record: oid,
        next_action: nextAction || "",
      }).then(function (res) {
        var next = (res && res.next_action) != null ? res.next_action : nextAction || "";
        root.ServiceContractsLocalStore.patchContract(oid, { next_action: next });
        return next;
      });
    },
  };
})(window);
