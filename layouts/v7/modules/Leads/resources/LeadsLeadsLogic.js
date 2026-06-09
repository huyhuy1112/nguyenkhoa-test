/**
 * Tag-driven lead derivation (port of Lovable leads-logic.ts)
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
  };
})(typeof window !== "undefined" ? window : this);
