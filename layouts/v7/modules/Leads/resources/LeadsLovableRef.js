/**
 * Lovable Leads UI — filtered reference (from user paste, Jun 2026).
 * Source routes: /leads/, /leads/$leadId, /leads/create
 * Port target: LeadsMkList.js, LeadsMkList.css, Detail, Edit templates.
 */
(function (root) {
  "use strict";

  function isVi() {
    try {
      var lang =
        typeof app !== "undefined" && app.getUserLanguage
          ? String(app.getUserLanguage() || "")
          : "";
      return !lang || lang.indexOf("vi") === 0 || lang === "vn";
    } catch (e) {
      return true;
    }
  }

  function pickLabel(vi, en) {
    return isVi() ? vi : en || vi;
  }

  var TAG_META_RAW = {
    facebook: { vi: "Facebook", en: "Facebook", cat: "source", cls: "mk-tag--facebook" },
    tiktok: { vi: "TikTok", en: "TikTok", cat: "source", cls: "mk-tag--tiktok" },
    website: { vi: "Website", en: "Website", cat: "source", cls: "mk-tag--website" },
    zalo: { vi: "Zalo", en: "Zalo", cat: "source", cls: "mk-tag--zalo" },
    other: { vi: "Khác", en: "Other", cat: "source", cls: "mk-tag--other" },
    chua_hoc: { vi: "Chưa học", en: "Not studied", cat: "learning", cls: "mk-tag--chua-hoc" },
    da_hoc: { vi: "Đã học", en: "Studied", cat: "learning", cls: "mk-tag--da-hoc" },
    mien_phi_online: { vi: "Miễn phí Online", en: "Free Online", cat: "program", cls: "mk-tag--free-online" },
    mien_phi_offline: { vi: "Miễn phí Offline", en: "Free Offline", cat: "program", cls: "mk-tag--free-offline" },
    pcth: { vi: "PCTH", en: "PCTH", cat: "program", cls: "mk-tag--pcth" },
    van_hanh: { vi: "Vận hành", en: "Operations", cat: "program", cls: "mk-tag--van-hanh" },
    mkt: { vi: "Marketing", en: "Marketing", cat: "program", cls: "mk-tag--mkt" },
    lop_khac: { vi: "Lớp khác", en: "Other class", cat: "program", cls: "mk-tag--lop-khac" },
    nhuong_quyen: { vi: "Nhượng quyền", en: "Franchise", cat: "program", cls: "mk-tag--nhuong-quyen" },
    mua_lan_dau: { vi: "Mua lần đầu", en: "First purchase", cat: "purchase", cls: "mk-tag--mua-lan-dau" },
    mua_lai: { vi: "Mua lại", en: "Repeat purchase", cat: "purchase", cls: "mk-tag--mua-lai" },
    khong_mua: { vi: "Không mua", en: "Not buying", cat: "purchase", cls: "mk-tag--khong-mua" },
    ngung_mua: { vi: "Ngưng mua", en: "Stopped buying", cat: "purchase", cls: "mk-tag--ngung-mua" },
    vang: { vi: "Vàng", en: "Gold", cat: "tier", cls: "mk-tag--vang" },
    bac: { vi: "Bạc", en: "Silver", cat: "tier", cls: "mk-tag--bac" },
    dong: { vi: "Đồng", en: "Bronze", cat: "tier", cls: "mk-tag--dong" },
  };

  var CALL_ATTEMPT_MAX = 10;
  var CALL_ATTEMPT_TAGS = [];
  var i;
  for (i = 1; i <= CALL_ATTEMPT_MAX; i++) {
    CALL_ATTEMPT_TAGS.push("goi_lan_" + i);
    TAG_META_RAW["goi_lan_" + i] = {
      vi: "Gọi lần " + i,
      en: "Call #" + i,
      cat: "call",
      cls: "mk-tag--goi-lan-" + Math.min(i, 3),
    };
  }

  var SOURCE_TAGS = ["facebook", "tiktok", "website", "zalo", "other"];
  var PROGRAM_TAGS = ["mien_phi_online", "mien_phi_offline", "pcth", "van_hanh", "mkt", "lop_khac", "nhuong_quyen"];
  var PURCHASE_TAGS = ["mua_lan_dau", "mua_lai", "khong_mua", "ngung_mua"];
  var TIER_TAGS = ["vang", "bac", "dong"];

  var PRESET_SEGMENTS = [
    { id: "new", nameVi: "Khách mới", nameEn: "New customers", filters: { purchase: "mua_lan_dau" } },
    { id: "gold", nameVi: "Khách vàng", nameEn: "Gold customers", filters: { tier: "vang" } },
    { id: "repeat", nameVi: "Khách mua lại", nameEn: "Repeat customers", filters: { purchase: "mua_lai" } },
    { id: "nobuy", nameVi: "Khách không mua", nameEn: "Not buying", filters: { purchase: "khong_mua" } },
    { id: "chain", nameVi: "Khách chuỗi (PCTH)", nameEn: "Chain (PCTH)", filters: { program: "pcth" } },
    { id: "franchise", nameVi: "Khách nhượng quyền", nameEn: "Franchise", filters: { program: "nhuong_quyen" } },
    { id: "cskh", nameVi: "Khách cần CSKH", nameEn: "Needs care", filters: { staleOnly: true } },
  ];

  var SEGMENT_LABELS_RAW = {
    gia_dinh: { vi: "Gia đình", en: "Family" },
    chuan_bi_mo: { vi: "Chuẩn bị mở", en: "Preparing to open" },
    co_quan: { vi: "Có quán", en: "Has store" },
  };

  var LIST_COLUMNS = [
    "checkbox", "created", "lead", "phone", "area", "source", "customerType", "tier",
    "owner", "tags", "lastTouch", "nextAction",
  ];

  var KPI_KEYS = ["total", "newToday", "qualified", "repeat", "gold", "stale", "conv"];

  var CRM_MAP = {
    list: {
      tpl: "layouts/v7/modules/Leads/ListViewContents.tpl",
      header: "layouts/v7/modules/Leads/partials/LeadsMkListHeader.tpl",
      js: "layouts/v7/modules/Leads/resources/LeadsMkList.js",
      css: "layouts/v7/modules/Leads/resources/LeadsMkList.css",
    },
    detail: {
      tpl: "layouts/v7/modules/Leads/DetailView.tpl",
      css: "layouts/v7/modules/Leads/resources/LeadsMkDetail.css",
      js: "layouts/v7/modules/Leads/resources/Detail.js",
    },
    create: {
      tpl: "layouts/v7/modules/Leads/EditView.tpl",
      partial: "layouts/v7/modules/Leads/partials/LeadsMkEdit.tpl",
      js: "layouts/v7/modules/Leads/resources/LeadsMkEdit.js",
    },
  };

  /** UI reads/writes via LeadsLocalStore (localStorage) — no vtiger DB for demo Leads UI */
  var CACHE_ONLY = true;

  function tagMeta(t) {
    var raw = TAG_META_RAW[t];
    if (!raw) {
      return { label: t, cat: "other", cls: "mk-tag--other" };
    }
    return { label: pickLabel(raw.vi, raw.en), cat: raw.cat, cls: raw.cls };
  }

  function getPresetSegments() {
    return PRESET_SEGMENTS.map(function (s) {
      return {
        id: s.id,
        name: pickLabel(s.nameVi, s.nameEn),
        filters: s.filters,
      };
    });
  }

  function getSegmentLabels() {
    var out = {};
    Object.keys(SEGMENT_LABELS_RAW).forEach(function (k) {
      out[k] = pickLabel(SEGMENT_LABELS_RAW[k].vi, SEGMENT_LABELS_RAW[k].en);
    });
    return out;
  }

  // Backward-compatible TAG_META proxy (label resolves at read time via tagMeta)
  var TAG_META = {};
  Object.keys(TAG_META_RAW).forEach(function (k) {
    Object.defineProperty(TAG_META, k, {
      enumerable: true,
      get: function () {
        return tagMeta(k);
      },
    });
  });

  root.LeadsLovableRef = {
    CACHE_ONLY: CACHE_ONLY,
    STORAGE_KEYS: {
      leads: "bace_leads_cache_v1",
      segments: "bace_lead_segments_v1",
    },
    TAG_META: TAG_META,
    TAG_META_RAW: TAG_META_RAW,
    SOURCE_TAGS: SOURCE_TAGS,
    PROGRAM_TAGS: PROGRAM_TAGS,
    PURCHASE_TAGS: PURCHASE_TAGS,
    TIER_TAGS: TIER_TAGS,
    CALL_ATTEMPT_TAGS: CALL_ATTEMPT_TAGS,
    CALL_ATTEMPT_MAX: CALL_ATTEMPT_MAX,
    PRESET_SEGMENTS: PRESET_SEGMENTS,
    getPresetSegments: getPresetSegments,
    SEGMENT_LABELS: SEGMENT_LABELS_RAW,
    getSegmentLabels: getSegmentLabels,
    LIST_COLUMNS: LIST_COLUMNS,
    KPI_KEYS: KPI_KEYS,
    PAGE_SIZE: 15,
    SEGMENTS_STORAGE_KEY: "bace_lead_segments_v1",
    CRM_MAP: CRM_MAP,
    isVi: isVi,
    pickLabel: pickLabel,
    tagMeta: tagMeta,
  };
})(typeof window !== "undefined" ? window : this);
