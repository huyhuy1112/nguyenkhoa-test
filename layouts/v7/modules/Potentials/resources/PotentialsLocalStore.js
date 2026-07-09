/**
 * Potentials list store — CRM API (MK_OPPS_API_READY).
 */
(function (root) {
  "use strict";

  var _opps = [];
  var _readyPromise = null;

  function useApi() {
    return !!root.MK_OPPS_API_READY;
  }

  function apiRequest(mode, extra) {
    var params = Object.assign({ module: "Potentials", action: "ModernApi", mode: mode }, extra || {});
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
      _opps = [];
      return Promise.resolve(_opps);
    }
    if (_readyPromise) {
      return _readyPromise;
    }
    _readyPromise = apiRequest("list")
      .then(function (res) {
        _opps = Array.isArray(res.opportunities) ? res.opportunities : [];
        if (Array.isArray(res.assignable_users)) {
          root.MK_OPPS_ASSIGNABLE_USERS = res.assignable_users;
        }
        return _opps;
      })
      .catch(function () {
        _opps = [];
        return _opps;
      });
    return _readyPromise;
  }

  root.PotentialsLocalStore = {
    bootstrap: bootstrap,
    getOpportunities: function () {
      return _opps.slice();
    },
    refresh: function () {
      _readyPromise = null;
      return bootstrap();
    },
  };
})(window);
