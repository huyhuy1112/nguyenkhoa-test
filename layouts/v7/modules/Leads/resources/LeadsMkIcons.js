/**
 * Inline SVG icons for Leads list (Lovable-style)
 */
(function (root) {
  "use strict";

  var ICONS = {
    users:
      '<svg class="mk-leads-ic" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.75"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>',
    clock:
      '<svg class="mk-leads-ic" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>',
    check:
      '<svg class="mk-leads-ic" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M22 4 12 14.01l-3-3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    repeat:
      '<svg class="mk-leads-ic" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 1l4 4-4 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 11V9a4 4 0 0 1 4-4h14M7 23l-4-4 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 13v2a4 4 0 0 1-4 4H3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    crown:
      '<svg class="mk-leads-ic" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 18h20v2H2v-2zM4 16l2-9 4 4 4-7 4 7 4-4 2 9H4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
    alert:
      '<svg class="mk-leads-ic" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>',
    trend:
      '<svg class="mk-leads-ic" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M23 6l-9.5 9.5-5-5L1 18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 6h6v6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    bookmark:
      '<svg class="mk-leads-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>',
    save:
      '<svg class="mk-leads-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>',
    search:
      '<svg class="mk-leads-ic" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>',
    filter:
      '<svg class="mk-leads-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>',
    chevron:
      '<svg class="mk-leads-ic mk-leads-ic--chev" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    sort:
      '<svg class="mk-leads-ic mk-leads-ic--sort" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 9l4-4 4 4M8 15l4 4 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    user:
      '<svg class="mk-leads-ic mk-leads-ic--lead" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"/><path d="M5 20c0-3.3 3.1-6 7-6s7 2.7 7 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    close:
      '<svg class="mk-leads-ic mk-leads-ic--close" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    export:
      '<svg class="mk-leads-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    plus:
      '<svg class="mk-leads-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    ticket:
      '<svg class="mk-leads-ic" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5H5a2 2 0 0 0-2 2v3a2 2 0 0 1 0 4v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3a2 2 0 0 1 0-4V7a2 2 0 0 0-2-2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
  };

  root.LeadsMkIcons = {
    get: function (name) {
      return ICONS[name] || "";
    },
    KPI: ["users", "clock", "check", "repeat", "crown", "alert", "trend"],
    KPI_TONES: ["blue", "violet", "emerald", "cyan", "amber", "rose", "indigo"],
  };
})(typeof window !== "undefined" ? window : this);
