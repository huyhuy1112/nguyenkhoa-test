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
  };
})(window);
