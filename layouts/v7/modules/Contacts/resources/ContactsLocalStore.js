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
    saveInlineFields: function (id, patch) {
      var oid = String(id || "");
      var data = { record: oid };
      if (patch && Object.prototype.hasOwnProperty.call(patch, "phone")) {
        data.phone = patch.phone;
      }
      if (patch && Object.prototype.hasOwnProperty.call(patch, "address")) {
        data.address = patch.address;
      }
      return apiRequest("save_inline_fields", data).then(function (res) {
        root.ContactsLocalStore.patchContact(oid, {
          phone: res && res.phone != null ? res.phone : patch.phone,
          address: res && res.address != null ? res.address : patch.address,
        });
        return res;
      });
    },
    saveCredentials: function (id, daCapBang, daCapTaiKhoan) {
      var oid = String(id || "");
      return apiRequest("credential_save", {
        record: oid,
        da_cap_bang: daCapBang || "Chưa cấp",
        da_cap_tai_khoan: daCapTaiKhoan || "Chưa cấp tài khoản",
      }).then(function (res) {
        var creds = (res && res.credentials) || {};
        root.ContactsLocalStore.patchContact(oid, {
          da_cap_bang: creds.da_cap_bang || daCapBang,
          da_cap_tai_khoan: creds.da_cap_tai_khoan || daCapTaiKhoan,
        });
        return creds;
      });
    },
  };
})(window);
