/**
 * Tag-driven lead derivation + commerce/next-action helpers (cache-only UI).
 * Backend: see modules/Leads/docs/LEADS_CACHE_BACKEND_SPEC.md
 */
(function (root) {
  "use strict";

  var PURCHASE_MAP = {
    mua_lan_dau: "New Purchase",
    mua_lai: "Repeat Purchase",
    khong_mua: "Not Buying",
    ngung_mua: "Stopped",
  };

  var PROGRAM_MAP = {
    nhuong_quyen: "Franchise",
    pcth: "PCTH Program",
    mien_phi_online: "Free Class",
    mien_phi_offline: "Free Class",
    van_hanh: "PCTH Program",
    mkt: "PCTH Program",
    lop_khac: "PCTH Program",
  };

  var TIER_MAP = { vang: "Gold", bac: "Silver", dong: "Bronze" };

  var SEGMENT_LABELS = {
    gia_dinh: "Gia đình",
    chuan_bi_mo: "Chuẩn bị mở",
    co_quan: "Có quán",
  };

  var ACTIVITY_TYPES = ["task", "call", "meeting"];

  function daysSince(iso) {
    if (!iso) return 0;
    return Math.floor((Date.now() - new Date(iso).getTime()) / 86400000);
  }

  function findTag(tags, pool) {
    for (var i = 0; i < tags.length; i++) {
      if (pool.indexOf(tags[i]) >= 0) return tags[i];
    }
    return null;
  }

  function derive(lead) {
    var tags = lead.tags || [];
    var purchaseTag = findTag(tags, Object.keys(PURCHASE_MAP));
    var programTag = findTag(tags, Object.keys(PROGRAM_MAP));
    var tierTag = findTag(tags, Object.keys(TIER_MAP));
    var days = daysSince(lead.last_touch);
    var stage = purchaseTag ? PURCHASE_MAP[purchaseTag] : "New Purchase";
    var type = programTag ? PROGRAM_MAP[programTag] : "PCTH Program";
    var tier = tierTag ? TIER_MAP[tierTag] : null;
    var stale = days >= 7;
    var high =
      (lead.value || 0) >= 25000000 &&
      (tags.indexOf("mua_lai") >= 0 || tags.indexOf("nhuong_quyen") >= 0);
    return { stage: stage, type: type, tier: tier, stale: stale, high: high, days: days };
  }

  function parsePurchaseDate(dateStr) {
    if (!dateStr) return null;
    var parts = String(dateStr).split("/");
    if (parts.length !== 3) return null;
    var d = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10) - 1;
    var y = parseInt(parts[2], 10);
    if (!d || m < 0 || !y) return null;
    return new Date(y, m, d);
  }

  function purchasesInLastDays(purchases, days) {
    var cutoff = Date.now() - days * 86400000;
    return (purchases || []).filter(function (p) {
      var dt = parsePurchaseDate(p.date);
      return dt && dt.getTime() >= cutoff;
    });
  }

  /** Distinct order ids in rolling 30-day window (fallback: row count). */
  function monthlyOrderCount(lead) {
    var recent = purchasesInLastDays(lead.purchases || [], 30);
    if (!recent.length) return 0;
    var ids = {};
    recent.forEach(function (p) {
      ids[p.orderId || p.date + "|" + p.product] = true;
    });
    return Object.keys(ids).length;
  }

  function totalProductsPurchased(lead) {
    var sum = 0;
    (lead.purchases || []).forEach(function (p) {
      sum += parseInt(p.qty, 10) || 0;
    });
    return sum;
  }

  function recentOrderValue(lead) {
    var items = (lead.purchases || []).slice();
    if (!items.length) return 0;
    items.sort(function (a, b) {
      var da = parsePurchaseDate(a.date);
      var db = parsePurchaseDate(b.date);
      return (db ? db.getTime() : 0) - (da ? da.getTime() : 0);
    });
    return items[0].value || 0;
  }

  function activityTypePrefix(type) {
    if (type === "call") return "Gọi: ";
    if (type === "meeting") return "Họp: ";
    if (type === "task") return "Việc: ";
    return "";
  }

  /**
   * Next Action (List column) = earliest open Calendar activity (Task/Call/Meeting).
   * Fallback: legacy next_action string in cache.
   */
  function deriveNextAction(lead) {
    var tasks = (lead.calendarTasks || []).filter(function (t) {
      if (!t || ACTIVITY_TYPES.indexOf(t.type) < 0) return false;
      var status = String(t.status || "open").toLowerCase();
      return status !== "done" && status !== "completed" && status !== "closed";
    });
    if (tasks.length) {
      tasks.sort(function (a, b) {
        var da = a.dueAt ? new Date(a.dueAt).getTime() : Number.MAX_SAFE_INTEGER;
        var db = b.dueAt ? new Date(b.dueAt).getTime() : Number.MAX_SAFE_INTEGER;
        return da - db;
      });
      var top = tasks[0];
      return activityTypePrefix(top.type) + String(top.subject || "").trim();
    }
    return String(lead.next_action || "").trim();
  }

  function openCalendarTasks(lead) {
    return (lead.calendarTasks || [])
      .filter(function (t) {
        var status = String(t.status || "open").toLowerCase();
        return status !== "done" && status !== "completed" && status !== "closed";
      })
      .map(function (t) {
        return {
          type: t.type,
          subject: t.subject,
          when: t.dueLabel || touchLabel(daysSince(t.dueAt)),
        };
      });
  }

  function fmtVND(n) {
    try {
      return new Intl.NumberFormat("vi-VN").format(n || 0) + " \u20ab";
    } catch (e) {
      return String(n || 0) + " \u20ab";
    }
  }

  function touchLabel(days) {
    if (days <= 0) return "Today";
    return days + "d ago";
  }

  function ownerInitials(name) {
    return String(name || "")
      .trim()
      .split(/\s+/)
      .slice(-1)[0]
      .charAt(0)
      .toUpperCase();
  }

  var OWNER_COLORS = { Linh: "#8b5cf6", Minh: "#10b981", Hà: "#f97316", Ha: "#f97316" };

  function ownerColor(name) {
    return OWNER_COLORS[name] || "#64748b";
  }

  root.LeadsLeadsLogic = {
    derive: derive,
    daysSince: daysSince,
    fmtVND: fmtVND,
    touchLabel: touchLabel,
    ownerInitials: ownerInitials,
    ownerColor: ownerColor,
    SEGMENT_LABELS: SEGMENT_LABELS,
    PURCHASE_MAP: PURCHASE_MAP,
    PROGRAM_MAP: PROGRAM_MAP,
    monthlyOrderCount: monthlyOrderCount,
    totalProductsPurchased: totalProductsPurchased,
    recentOrderValue: recentOrderValue,
    deriveNextAction: deriveNextAction,
    openCalendarTasks: openCalendarTasks,
    purchasesInLastDays: purchasesInLastDays,
  };
})(typeof window !== "undefined" ? window : this);
