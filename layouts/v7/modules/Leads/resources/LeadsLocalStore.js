/**
 * Leads UI — browser cache only (localStorage). No vtiger DB.
 */
(function (root) {
  "use strict";

  var KEYS = {
    version: "bace_leads_cache_version",
    leads: "bace_leads_cache_v1",
    segments: "bace_lead_segments_v1",
  };

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
    var row = Object.assign({ tags: [], activities: [], openTickets: 0 }, lead);
    if (!row.activities) row.activities = [];
    return row;
  }

  function ensureSeeded() {
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

  function getLeads() {
    ensureSeeded();
    return read(KEYS.leads, []);
  }

  function setLeads(leads) {
    write(KEYS.leads, leads);
    return leads;
  }

  function getLead(id) {
    return getLeads().find(function (l) {
      return l.id === id;
    }) || null;
  }

  function create(patch) {
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
    return lead;
  }

  function update(id, patch) {
    var leads = getLeads();
    var idx = leads.findIndex(function (l) {
      return l.id === id;
    });
    if (idx < 0) return null;
    leads[idx] = Object.assign({}, leads[idx], patch, { id: id });
    setLeads(leads);
    return leads[idx];
  }

  function remove(id) {
    setLeads(
      getLeads().filter(function (l) {
        return l.id !== id;
      }),
    );
  }

  function getSegments() {
    ensureSeeded();
    return read(KEYS.segments, []);
  }

  function saveSegments(segments) {
    write(KEYS.segments, segments);
    return segments;
  }

  function resetDemo() {
    try {
      root.localStorage.removeItem(KEYS.leads);
      root.localStorage.removeItem(KEYS.version);
      root.localStorage.removeItem(KEYS.segments);
    } catch (e) { /* ignore */ }
    ensureSeeded();
  }

  ensureSeeded();

  root.LeadsLocalStore = {
    CACHE_ONLY: true,
    KEYS: KEYS,
    getLeads: getLeads,
    setLeads: setLeads,
    getLead: getLead,
    create: create,
    update: update,
    remove: remove,
    getSegments: getSegments,
    saveSegments: saveSegments,
    resetDemo: resetDemo,
    ensureSeeded: ensureSeeded,
  };
})(typeof window !== "undefined" ? window : this);
