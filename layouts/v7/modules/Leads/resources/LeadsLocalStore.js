/**
 * Leads UI store — Phase 1: CRM API when MK_LEADS_API_READY, else localStorage demo.
 */
(function (root) {
  "use strict";

  var KEYS = {
    version: "bace_leads_cache_version",
    leads: "bace_leads_cache_v1",
    segments: "bace_lead_segments_v1",
  };

  var _memLeads = null;
  var _memSegments = null;
  var _assignableUsers = null;
  var _readyPromise = null;
  var _bootstrapped = false;

  function useApi() {
    return !!root.MK_LEADS_API_READY;
  }

  function read(key, fallback) {
    try {
      var raw = root.localStorage.getItem(key);
      if (!raw) return fallback;
      return JSON.parse(raw);
    } catch (e) {
      return fallback;
    }
  }

  function write(key, value) {
    try {
      root.localStorage.setItem(key, JSON.stringify(value));
      return true;
    } catch (e) {
      return false;
    }
  }

  function uid(prefix) {
    return (prefix || "L") + "-" + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
  }

  function normalizeLead(lead) {
    var row = Object.assign(
      {
        tags: [],
        activities: [],
        purchases: [],
        calendarTasks: [],
        openTickets: 0,
        email: "",
        cccd: "",
        district: "",
        address: "",
        area: "",
        notes: "",
        companyName: "",
        segment: "",
      },
      lead,
    );
    if (!row.activities) row.activities = [];
    if (!row.purchases) row.purchases = [];
    if (!row.calendarTasks) row.calendarTasks = [];
    if (!row.comments) row.comments = [];
    if (!row.modUpdates) row.modUpdates = [];
    return row;
  }

  function apiRequest(mode, extra) {
    var params = Object.assign({ module: "Leads", action: "ModernApi", mode: mode }, extra || {});
    return new Promise(function (resolve, reject) {
      if (root.app && root.app.request && root.app.request.post) {
        root.app.request.post({ data: params }).then(function (err, res) {
          if (err) {
            reject(err);
            return;
          }
          if (res && res.success === false) {
            reject(res.error || res.message || "API error");
            return;
          }
          resolve(res || {});
        });
        return;
      }
      reject(new Error("app.request unavailable"));
    });
  }

  function bootstrapFromApi() {
    return apiRequest("list").then(function (res) {
      _memLeads = dedupeLeadsByCrmid((res.leads || []).map(normalizeLead));
      _assignableUsers = Array.isArray(res.assignable_users) ? res.assignable_users.slice() : null;
      _bootstrapped = true;
      return _memLeads;
    }).then(function () {
      var dedupeKey = "mk_leads_deduped_v3";
      if (read(dedupeKey, false)) {
        return _memLeads;
      }
      return apiRequest("dedupe_leads", { apply: 1 }).then(function (dedupeRes) {
        write(dedupeKey, true);
        if (dedupeRes && dedupeRes.deleted > 0) {
          return apiRequest("list").then(function (res2) {
            _memLeads = dedupeLeadsByCrmid((res2.leads || []).map(normalizeLead));
            if (Array.isArray(res2.assignable_users)) {
              _assignableUsers = res2.assignable_users.slice();
            }
            return _memLeads;
          });
        }
        return _memLeads;
      }).catch(function () {
        write(dedupeKey, true);
        return _memLeads;
      });
    }).then(function () {
      return apiRequest("segments_list").then(function (segRes) {
        _memSegments = segRes.segments || [];
        return _memLeads;
      }).catch(function () {
        _memSegments = [];
        return _memLeads;
      });
    });
  }

  function ensureSeeded() {
    if (useApi()) {
      return;
    }
    var ver = root.LeadsSeedData ? root.LeadsSeedData.VERSION : "0";
    if (read(KEYS.version, "") !== ver && root.LeadsSeedData) {
      write(
        KEYS.leads,
        root.LeadsSeedData.leads.map(function (l) {
          return normalizeLead(l);
        }),
      );
      write(KEYS.version, ver);
      write(KEYS.segments, []);
    }
    if (!read(KEYS.leads, null)) {
      write(KEYS.leads, []);
    }
    if (!read(KEYS.segments, null)) {
      write(KEYS.segments, []);
    }
  }

  function ready() {
    if (!useApi()) {
      ensureSeeded();
      return Promise.resolve();
    }
    if (_bootstrapped) {
      return Promise.resolve();
    }
    if (!_readyPromise) {
      _readyPromise = bootstrapFromApi().catch(function (err) {
        _readyPromise = null;
        throw err;
      });
    }
    return _readyPromise;
  }

  function getLeads() {
    if (useApi()) {
      return _memLeads ? _memLeads.slice() : [];
    }
    ensureSeeded();
    return read(KEYS.leads, []);
  }

  function setLeads(leads) {
    if (useApi()) {
      _memLeads = leads.slice();
      return _memLeads;
    }
    write(KEYS.leads, leads);
    return leads;
  }

  function matchLeadId(lead, id) {
    if (!lead || id == null || id === "") return false;
    var needle = String(id);
    if (String(lead.id) === needle) return true;
    if (lead.crmid != null && String(lead.crmid) === needle) return true;
    return false;
  }

  function dedupeLeadsByCrmid(leads) {
    var seen = {};
    var out = [];
    (leads || []).forEach(function (l) {
      var key = l.crmid != null && l.crmid !== "" ? "c:" + l.crmid : "i:" + l.id;
      if (seen[key]) return;
      seen[key] = true;
      out.push(l);
    });
    return out;
  }

  function findLeadIndex(leads, lead) {
    if (!lead || !leads || !leads.length) return -1;
    for (var i = 0; i < leads.length; i++) {
      var a = leads[i];
      if (matchLeadId(a, lead.id)) return i;
      if (lead.crmid != null && matchLeadId(a, lead.crmid)) return i;
      if (a.crmid != null && matchLeadId(a, lead.id)) return i;
      if (a.crmid != null && lead.crmid != null && String(a.crmid) === String(lead.crmid)) return i;
    }
    return -1;
  }

  function getLead(id) {
    var leads = getLeads();
    for (var i = 0; i < leads.length; i++) {
      if (matchLeadId(leads[i], id)) {
        return leads[i];
      }
    }
    return null;
  }

  function upsertMemLead(lead) {
    if (!lead) return null;
    var leads = _memLeads ? _memLeads.slice() : [];
    var row = normalizeLead(lead);
    var idx = findLeadIndex(leads, row);
    if (idx >= 0) {
      leads[idx] = row;
    } else {
      leads.unshift(row);
    }
    _memLeads = dedupeLeadsByCrmid(leads);
    return row;
  }

  function fetchLead(id, force, withFeed) {
    if (!useApi()) {
      return Promise.resolve(getLead(id));
    }
    if (!force) {
      var cached = getLead(id);
      if (cached) {
        return Promise.resolve(cached);
      }
    }
    var feedFlag = withFeed === false ? 0 : 1;
    return apiRequest("get", { id: id, with_feed: feedFlag }).then(function (res) {
      if (!res || !res.lead) {
        return getLead(id);
      }
      return upsertMemLead(res.lead);
    });
  }

  function reloadLead(id) {
    return fetchLead(id, true);
  }

  function refreshLeadsList() {
    if (!useApi()) {
      ensureSeeded();
      return Promise.resolve(getLeads());
    }
    return apiRequest("list").then(function (res) {
      _memLeads = dedupeLeadsByCrmid((res.leads || []).map(normalizeLead));
      if (Array.isArray(res.assignable_users)) {
        _assignableUsers = res.assignable_users.slice();
      }
      _bootstrapped = true;
      return _memLeads;
    });
  }

  function create(patch) {
    if (useApi()) {
      return apiRequest("save", { payload: JSON.stringify(patch || {}) }).then(function (res) {
        return upsertMemLead(res.lead);
      });
    }
    var leads = getLeads();
    var lead = normalizeLead(
      Object.assign(
        {
          id: uid("L"),
          name: "New Lead",
          phone: "",
          email: "",
          owner: "Linh",
          value: 0,
          last_touch: new Date().toISOString(),
          next_action: "",
          tags: [],
        },
        patch,
      ),
    );
    leads.unshift(lead);
    setLeads(leads);
    return Promise.resolve(lead);
  }

  function assignOwner(ids, ownerUserId) {
    if (useApi()) {
      return apiRequest("bulk_assign_owner", {
        payload: JSON.stringify({ ids: ids || [], owner: ownerUserId }),
      }).then(function (res) {
        var leads = (res.leads || []).map(normalizeLead);
        leads.forEach(upsertMemLead);
        return leads;
      });
    }
    var leads = getLeads();
    var ownerLabel = String(ownerUserId || "");
    var users = root.LeadsLocalStore && root.LeadsLocalStore.getAssignableUsers
      ? root.LeadsLocalStore.getAssignableUsers()
      : [];
    users.forEach(function (u) {
      if (String(u.id) === ownerLabel) {
        ownerLabel = u.label || u.user_name || ownerLabel;
      }
    });
    (ids || []).forEach(function (id) {
      var idx = leads.findIndex(function (l) {
        return matchLeadId(l, id);
      });
      if (idx >= 0) {
        leads[idx] = Object.assign({}, leads[idx], { owner: ownerLabel });
      }
    });
    setLeads(leads);
    return Promise.resolve(leads);
  }

  function update(id, patch) {
    if (useApi()) {
      var existing = getLead(id);
      var ensureExisting = existing
        ? Promise.resolve(existing)
        : fetchLead(id, true).catch(function () {
            return null;
          });
      return ensureExisting.then(function (existingRow) {
        var merged = Object.assign({}, existingRow || {}, patch || {}, {
          id: existingRow ? existingRow.id : id,
        });
        return apiRequest("save", {
          record: id,
          payload: JSON.stringify(merged),
        }).then(function (res) {
          return upsertMemLead(res.lead);
        });
      });
    }
    var leads = getLeads();
    var idx = leads.findIndex(function (l) {
      return matchLeadId(l, id);
    });
    if (idx < 0) return Promise.resolve(null);
    leads[idx] = Object.assign({}, leads[idx], patch, { id: leads[idx].id });
    setLeads(leads);
    return Promise.resolve(leads[idx]);
  }

  function syncCalendarTasks(id, calendarTasks) {
    if (!useApi()) {
      return update(id, { calendarTasks: calendarTasks || [] });
    }
    return apiRequest("calendar_tasks_sync", {
      id: id,
      payload: JSON.stringify({ calendarTasks: calendarTasks || [] }),
    }).then(function (res) {
      return upsertMemLead(res.lead);
    });
  }

  function remove(id) {
    if (useApi()) {
      return apiRequest("delete", { id: id }).then(function () {
        if (_memLeads) {
          _memLeads = _memLeads.filter(function (l) {
            return !matchLeadId(l, id);
          });
        }
      });
    }
    setLeads(
      getLeads().filter(function (l) {
        return !matchLeadId(l, id);
      }),
    );
    return Promise.resolve();
  }

  function getSegments() {
    if (useApi()) {
      return _memSegments ? _memSegments.slice() : [];
    }
    ensureSeeded();
    return read(KEYS.segments, []);
  }

  function saveSegments(segments) {
    if (useApi()) {
      return apiRequest("segments_save", {
        payload: JSON.stringify({ segments: segments }),
      }).then(function (res) {
        _memSegments = res.segments || segments;
        return _memSegments;
      });
    }
    write(KEYS.segments, segments);
    return Promise.resolve(segments);
  }

  function resetDemo() {
    if (useApi()) {
      return apiRequest("seed", { force: 1 }).then(function () {
        _bootstrapped = false;
        _readyPromise = null;
        return ready();
      });
    }
    try {
      root.localStorage.removeItem(KEYS.leads);
      root.localStorage.removeItem(KEYS.version);
      root.localStorage.removeItem(KEYS.segments);
    } catch (e) { /* ignore */ }
    ensureSeeded();
    return Promise.resolve();
  }

  if (!useApi()) {
    ensureSeeded();
  }

  root.LeadsLocalStore = {
    CACHE_ONLY: !useApi(),
    API_READY: useApi(),
    KEYS: KEYS,
    ready: ready,
    getLeads: getLeads,
    getAssignableUsers: function () {
      if (_assignableUsers && _assignableUsers.length) {
        return _assignableUsers.slice();
      }
      if (root.MK_LEADS_ASSIGNABLE_USERS && root.MK_LEADS_ASSIGNABLE_USERS.length) {
        return root.MK_LEADS_ASSIGNABLE_USERS.slice();
      }
      return [];
    },
    setLeads: setLeads,
    getLead: getLead,
    fetchLead: fetchLead,
    reloadLead: reloadLead,
    importLead: upsertMemLead,
    refreshLeadsList: refreshLeadsList,
    assignOwner: assignOwner,
    create: create,
    update: update,
    syncCalendarTasks: syncCalendarTasks,
    remove: remove,
    getSegments: getSegments,
    saveSegments: saveSegments,
    resetDemo: resetDemo,
    ensureSeeded: ensureSeeded,
  };
})(typeof window !== "undefined" ? window : this);
