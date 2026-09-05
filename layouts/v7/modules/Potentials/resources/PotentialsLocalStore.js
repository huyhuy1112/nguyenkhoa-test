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
    removeFromList: function (id) {
      var oid = String(id || "");
      _opps = _opps.filter(function (o) {
        return String(o.id) !== oid && String(o.crmid || "") !== oid;
      });
      return _opps;
    },
    remove: function (id) {
      var oid = String(id || "");
      return apiRequest("delete", { id: oid }).then(function () {
        _opps = _opps.filter(function (o) {
          return String(o.id) !== oid && String(o.crmid || "") !== oid;
        });
      });
    },
    /**
     * Replace confirm tags on a cached opportunity without full API reload.
     */
    setConfirmTag: function (id, confirmTag, confirmedAt) {
      var oid = String(id || "");
      var confirmPool = ["xac_nhan_tham_gia", "khong_xac_nhan_tham_gia"];
      var ref = root.PotentialsLovableRef;
      for (var i = 0; i < _opps.length; i++) {
        var o = _opps[i];
        if (String(o.id) !== oid && String(o.crmid || "") !== oid) {
          continue;
        }
        var tags = Array.isArray(o.tags) ? o.tags.slice() : [];
        tags = tags.filter(function (tg) {
          var key = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "").toLowerCase();
          return confirmPool.indexOf(key) < 0;
        });
        if (confirmTag) {
          tags.push(confirmTag);
        }
        o.tags = tags;
        if (confirmTag === "xac_nhan_tham_gia") {
          o.confirmed_at = confirmedAt || o.confirmed_at || new Date().toISOString();
        } else {
          o.confirmed_at = "";
        }
        return o;
      }
      return null;
    },
    patchOpportunity: function (id, patch) {
      var oid = String(id || "");
      if (!patch) return null;
      for (var i = 0; i < _opps.length; i++) {
        var o = _opps[i];
        if (String(o.id) !== oid && String(o.crmid || "") !== oid) continue;
        Object.keys(patch).forEach(function (k) {
          o[k] = patch[k];
        });
        return o;
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
        root.PotentialsLocalStore.patchOpportunity(oid, { tags: next });
        return next;
      });
    },
    saveInlineLocation: function (id, region, address) {
      var oid = String(id || "");
      return apiRequest("save_inline_location", {
        record: oid,
        mk_region: region || "",
        mk_address: address || "",
      }).then(function (res) {
        root.PotentialsLocalStore.patchOpportunity(oid, {
          address: (res && res.address) || address || "",
          district: (res && res.district) || "",
          tags: (res && res.tags) || undefined,
        });
        return res;
      });
    },
    saveInlinePhone: function (id, phone) {
      var oid = String(id || "");
      return apiRequest("save_inline_phone", {
        record: oid,
        phone: phone || "",
      }).then(function (res) {
        root.PotentialsLocalStore.patchOpportunity(oid, {
          phone: (res && res.phone) || phone || "",
        });
        return res;
      });
    },
    saveInlineBusinessModel: function (id, businessModel) {
      var oid = String(id || "");
      return apiRequest("save_inline_business_model", {
        record: oid,
        business_model: businessModel || "",
      }).then(function (res) {
        root.PotentialsLocalStore.patchOpportunity(oid, {
          business_model: res && res.business_model != null ? res.business_model : businessModel || "",
        });
        return res;
      });
    },
    /**
     * Bước 3 Offline — Admin điểm danh tại lớp (không OA).
     * @param {string|number} id potential id
     * @param {string} action da_tham_gia|khong_tham_gia
     */
    offlineCheckin: function (id, action) {
      var oid = String(id || "");
      return apiRequest("offline_checkin", {
        record: oid,
        offline_action: action || "",
      }).then(function (res) {
        if (res && res.opportunity) {
          root.PotentialsLocalStore.patchOpportunity(oid, res.opportunity);
        } else {
          var patch = {};
          if (res && res.status) {
            patch.offline_status = res.status;
            patch.offline_status_label = res.status_label || "";
          }
          if (res && Array.isArray(res.tags)) {
            patch.tags = res.tags;
          }
          if (res && res.checked_in_at !== undefined) {
            patch.offline_checked_in_at = res.checked_in_at || "";
          }
          root.PotentialsLocalStore.patchOpportunity(oid, patch);
        }
        return res;
      });
    },
  };
})(window);
