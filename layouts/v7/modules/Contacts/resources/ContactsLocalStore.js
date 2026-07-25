/**
 * Contacts list store — CRM API (MK_CONTACTS_API_READY).
 */
(function (root) {
  "use strict";

  var _contacts = [];
  var _readyPromise = null;

  function useApi() {
    return !!root.MK_CONTACTS_API_READY;
  }

  function apiRequest(mode, extra) {
    var params = Object.assign({ module: "Contacts", action: "ModernApi", mode: mode }, extra || {});
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
      _contacts = [];
      return Promise.resolve(_contacts);
    }
    if (_readyPromise) {
      return _readyPromise;
    }
    _readyPromise = apiRequest("list")
      .then(function (res) {
        _contacts = Array.isArray(res.contacts) ? res.contacts : [];
        if (Array.isArray(res.assignable_users)) {
          root.MK_CONTACTS_ASSIGNABLE_USERS = res.assignable_users;
        }
        return _contacts;
      })
      .catch(function () {
        _contacts = [];
        return _contacts;
      });
    return _readyPromise;
  }

  root.ContactsLocalStore = {
    bootstrap: bootstrap,
    getContacts: function () {
      return _contacts.slice();
    },
    refresh: function () {
      _readyPromise = null;
      return bootstrap();
    },
    remove: function (id) {
      var oid = String(id || "");
      return apiRequest("delete", { id: oid }).then(function () {
        _contacts = _contacts.filter(function (c) {
          return String(c.id) !== oid && String(c.crmid || "") !== oid;
        });
      });
    },
    patchContact: function (id, patch) {
      var oid = String(id || "");
      if (!patch) return null;
      for (var i = 0; i < _contacts.length; i++) {
        var c = _contacts[i];
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
        root.ContactsLocalStore.patchContact(oid, { tags: next });
        return next;
      });
    },
  };
})(window);
