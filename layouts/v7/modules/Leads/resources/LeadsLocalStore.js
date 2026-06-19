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
      _memLeads = (res.leads || []).map(normalizeLead);
      _bootstrapped = true;
      return _memLeads;
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
    var idx = -1;
    for (var i = 0; i < leads.length; i++) {
      if (matchLeadId(leads[i], lead.id) || (lead.crmid && matchLeadId(leads[i], lead.crmid))) {
        idx = i;
        break;
      }
    }
    var row = normalizeLead(lead);
    if (idx >= 0) {
      leads[idx] = row;
    } else {
      leads.unshift(row);
    }
    _memLeads = leads;
    return row;
  }

  function fetchLead(id, force) {
    if (!useApi()) {
      return Promise.resolve(getLead(id));
    }
    if (!force) {
      var cached = getLead(id);
      if (cached) {
        return Promise.resolve(cached);
      }
    }
    return apiRequest("get", { id: id, with_feed: 1 }).then(function (res) {
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
      _memLeads = (res.leads || []).map(normalizeLead);
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

  function update(id, patch) {
    if (useApi()) {
      var existing = getLead(id);
      var merged = Object.assign({}, existing || {}, patch || {}, { id: existing ? existing.id : id });
      return apiRequest("save", {
        record: id,
        payload: JSON.stringify(merged),
      }).then(function (res) {
        return upsertMemLead(res.lead);
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
    setLeads: setLeads,
    getLead: getLead,
    fetchLead: fetchLead,
    reloadLead: reloadLead,
    refreshLeadsList: refreshLeadsList,
    create: create,
    update: update,
    remove: remove,
    getSegments: getSegments,
    saveSegments: saveSegments,
    resetDemo: resetDemo,
    ensureSeeded: ensureSeeded,
  };
})(typeof window !== "undefined" ? window : this);
