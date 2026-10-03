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
        if (Array.isArray(res.offline_classes)) {
          root.MK_OFFLINE_CLASSES = res.offline_classes;
        }
        if (Array.isArray(res.gd14_questions)) {
          root.MK_GD14_QUESTIONS = { questions: res.gd14_questions };
        }
        if (Array.isArray(res.gd14_courses)) {
          root.MK_GD14_COURSES = res.gd14_courses;
        }
        if (Array.isArray(res.screening_questions)) {
          root.MK_SCREENING_QUESTIONS = { questions: res.screening_questions };
        } else if (res.screening_options && Array.isArray(res.screening_options.questions)) {
          root.MK_SCREENING_QUESTIONS = { questions: res.screening_options.questions };
        }
        if (res && res.is_admin != null) {
          root.MK_CONTACTS_IS_ADMIN = Number(res.is_admin) === 1;
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
      if (patch && Object.prototype.hasOwnProperty.call(patch, "business_model")) {
        data.business_model = patch.business_model;
      }
      return apiRequest("save_inline_fields", data).then(function (res) {
        var next = {};
        if (Object.prototype.hasOwnProperty.call(data, "phone")) {
          next.phone = res && res.phone != null ? res.phone : patch.phone;
        }
        if (Object.prototype.hasOwnProperty.call(data, "address")) {
          next.address = res && res.address != null ? res.address : patch.address;
        }
        if (Object.prototype.hasOwnProperty.call(data, "business_model")) {
          next.business_model = res && res.business_model != null ? res.business_model : patch.business_model;
        }
        root.ContactsLocalStore.patchContact(oid, next);
        return res;
      });
    },
    saveGd14Answers: function (id, payload) {
      var oid = String(id || "");
      return apiRequest("gd14_answers", {
        record: oid,
        payload: JSON.stringify(payload || {}),
      }).then(function (res) {
        var patch = {};
        if (res && res.gd14) patch.gd14 = res.gd14;
        if (res && res.compare) patch.compare = res.compare;
        if (res && res.verify_lines) patch.verify_lines = res.verify_lines;
        root.ContactsLocalStore.patchContact(oid, patch);
        return res;
      });
    },
    gd14ClassStep: function (id, step, fields) {
      var oid = String(id || "");
      var data = Object.assign({ record: oid, step: step || "" }, fields || {});
      return apiRequest("gd14_class", data).then(function (res) {
        var patch = {};
        if (res && res.gd14) patch.gd14 = res.gd14;
        if (res && res.verify_lines) patch.verify_lines = res.verify_lines;
        if (res && res.compare) patch.compare = res.compare;
        if (res && res.tag) {
          var current = null;
          for (var i = 0; i < _contacts.length; i++) {
            var row = _contacts[i];
            if (String(row.id) === oid || String(row.crmid || "") === oid) {
              current = row;
              break;
            }
          }
          var tags = ((current && current.tags) || []).filter(function (tag) {
            return String(tag).toLowerCase().indexOf("gd14_") !== 0;
          });
          tags.push(res.tag);
          patch.tags = tags;
        }
        root.ContactsLocalStore.patchContact(oid, patch);
        return res;
      });
    },
    saveOfflineAttend: function (id, classCode, datetime) {
      var oid = String(id || "");
      return apiRequest("save_offline_attend", {
        record: oid,
        class_code: classCode || "mqbb",
        datetime: datetime || "",
      }).then(function (res) {
        var patch = {};
        var code = (res && res.class_code) || classCode || "mqbb";
        var iso = (res && res.datetime) || "";
        if (code === "pcth_cb") patch.thoigian_pcthcb = iso;
        else if (code === "pcth") patch.thoigian_pcth = iso;
        else patch.thoigian_mqbb = iso;
        root.ContactsLocalStore.patchContact(oid, patch);
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
    renewEdubitAccess: function (id, reason) {
      var oid = String(id || "");
      var data = { record: oid };
      if (reason) data.reason = reason;
      return apiRequest("edubit_renew", data).then(function (res) {
        if (!res || res.success === false) {
          throw new Error((res && res.error) || "Gia hạn thất bại");
        }
        var next = {};
        if (res.edubit_expires_at) next.edubit_expires_at = res.edubit_expires_at;
        if (typeof res.edubit_renew_count !== "undefined") {
          next.edubit_renew_count = res.edubit_renew_count;
        }
        if (typeof res.edubit_renew_remaining !== "undefined") {
          next.edubit_renew_remaining = res.edubit_renew_remaining;
        }
        if (typeof res.can_edubit_renew !== "undefined") {
          next.can_edubit_renew = res.can_edubit_renew;
        }
        if (res.status) next.online_status = res.status;
        root.ContactsLocalStore.patchContact(oid, next);
        return res;
      });
    },
    provisionEdubit: function (id, payload) {
      var oid = String(id || "");
      return apiRequest("edubit_provision", {
        record: oid,
        id: oid,
        payload: JSON.stringify(payload || {}),
      }).then(function (res) {
        if (!res || res.success === false) {
          throw new Error((res && res.error) || "Cấp TK thất bại");
        }
        var next = {};
        if (res.edubit_email) next.edubit_email = res.edubit_email;
        if (res.edubit_user_id) next.edubit_user_id = res.edubit_user_id;
        if (res.edubit_course_id) next.edubit_course_id = res.edubit_course_id;
        if (res.edubit_expires_at) next.edubit_expires_at = res.edubit_expires_at;
        if (res.edubit_activated_at) next.edubit_activated_at = res.edubit_activated_at;
        if (Array.isArray(res.courses)) next.edubit_courses = res.courses;
        if (res.edubit_user_id || (res.added_course_ids && res.added_course_ids.length)) {
          next.da_cap_tai_khoan = "Đã cấp";
          next.online_status = "online_dang_hoc";
        }
        root.ContactsLocalStore.patchContact(oid, next);
        return res;
      });
    },
    syncEdubitAll: function (limit) {
      var data = {};
      if (limit) data.limit = limit;
      return apiRequest("edubit_sync_all", data).then(function (res) {
        if (!res || res.success === false) {
          throw new Error((res && res.error) || "Đồng bộ thất bại");
        }
        return res;
      });
    },
  };
})(window);
