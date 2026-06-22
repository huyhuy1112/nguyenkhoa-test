/**
 * Lovable Leads UI — filtered reference (from user paste, Jun 2026).
 * Source routes: /leads/, /leads/$leadId, /leads/create
 * Port target: LeadsMkList.js, LeadsMkList.css, Detail, Edit templates.
 */
(function (root) {
  "use strict";

  var TAG_META = {
    facebook: { label: "Facebook", cat: "source", cls: "mk-tag--facebook" },
    tiktok: { label: "TikTok", cat: "source", cls: "mk-tag--tiktok" },
    website: { label: "Website", cat: "source", cls: "mk-tag--website" },
    zalo: { label: "Zalo", cat: "source", cls: "mk-tag--zalo" },
    other: { label: "Khác", cat: "source", cls: "mk-tag--other" },
    chua_hoc: { label: "Chưa học", cat: "learning", cls: "mk-tag--chua-hoc" },
    da_hoc: { label: "Đã học", cat: "learning", cls: "mk-tag--da-hoc" },
    mien_phi_online: { label: "Free Online", cat: "program", cls: "mk-tag--free-online" },
    mien_phi_offline: { label: "Free Offline", cat: "program", cls: "mk-tag--free-offline" },
    pcth: { label: "PCTH", cat: "program", cls: "mk-tag--pcth" },
    van_hanh: { label: "Vận hành", cat: "program", cls: "mk-tag--van-hanh" },
    mkt: { label: "Marketing", cat: "program", cls: "mk-tag--mkt" },
    lop_khac: { label: "Lớp khác", cat: "program", cls: "mk-tag--lop-khac" },
    nhuong_quyen: { label: "Nhượng quyền", cat: "program", cls: "mk-tag--nhuong-quyen" },
    mua_lan_dau: { label: "Mua lần đầu", cat: "purchase", cls: "mk-tag--mua-lan-dau" },
    mua_lai: { label: "Mua lại", cat: "purchase", cls: "mk-tag--mua-lai" },
    khong_mua: { label: "Không mua", cat: "purchase", cls: "mk-tag--khong-mua" },
    ngung_mua: { label: "Ngưng mua", cat: "purchase", cls: "mk-tag--ngung-mua" },
    vang: { label: "Gold", cat: "tier", cls: "mk-tag--vang" },
    bac: { label: "Silver", cat: "tier", cls: "mk-tag--bac" },
    dong: { label: "Bronze", cat: "tier", cls: "mk-tag--dong" },
    goi_lan_1: { label: "Gọi lần 1", cat: "call", cls: "mk-tag--goi-lan-1" },
    goi_lan_2: { label: "Gọi lần 2", cat: "call", cls: "mk-tag--goi-lan-2" },
    goi_lan_3: { label: "Gọi lần 3", cat: "call", cls: "mk-tag--goi-lan-3" },
  };

  var CALL_ATTEMPT_TAGS = ["goi_lan_1", "goi_lan_2", "goi_lan_3"];

  var SOURCE_TAGS = ["facebook", "tiktok", "website", "zalo", "other"];
  var PROGRAM_TAGS = ["mien_phi_online", "mien_phi_offline", "pcth", "van_hanh", "mkt", "lop_khac", "nhuong_quyen"];
  var PURCHASE_TAGS = ["mua_lan_dau", "mua_lai", "khong_mua", "ngung_mua"];
  var TIER_TAGS = ["vang", "bac", "dong"];

  var PRESET_SEGMENTS = [
    { id: "new", name: "Khách mới", filters: { purchase: "mua_lan_dau" } },
    { id: "gold", name: "Khách vàng", filters: { tier: "vang" } },
    { id: "repeat", name: "Khách mua lại", filters: { purchase: "mua_lai" } },
    { id: "nobuy", name: "Khách không mua", filters: { purchase: "khong_mua" } },
    { id: "chain", name: "Khách chuỗi (PCTH)", filters: { program: "pcth" } },
    { id: "franchise", name: "Khách nhượng quyền", filters: { program: "nhuong_quyen" } },
    { id: "cskh", name: "Khách cần CSKH", filters: { staleOnly: true } },
  ];

  var SEGMENT_LABELS = {
    gia_dinh: "Gia đình",
    chuan_bi_mo: "Chuẩn bị mở",
    co_quan: "Có quán",
  };

  var LIST_COLUMNS = [
    "checkbox", "lead", "phone", "area", "source", "customerType", "stage", "tier",
    "owner", "tags", "lastTouch", "nextAction", "value", "support", "stale",
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

  root.LeadsLovableRef = {
    CACHE_ONLY: CACHE_ONLY,
    STORAGE_KEYS: {
      leads: "bace_leads_cache_v1",
      segments: "bace_lead_segments_v1",
    },
    TAG_META: TAG_META,
    SOURCE_TAGS: SOURCE_TAGS,
    PROGRAM_TAGS: PROGRAM_TAGS,
    PURCHASE_TAGS: PURCHASE_TAGS,
    TIER_TAGS: TIER_TAGS,
    CALL_ATTEMPT_TAGS: CALL_ATTEMPT_TAGS,
    PRESET_SEGMENTS: PRESET_SEGMENTS,
    SEGMENT_LABELS: SEGMENT_LABELS,
    LIST_COLUMNS: LIST_COLUMNS,
    KPI_KEYS: KPI_KEYS,
    PAGE_SIZE: 15,
    SEGMENTS_STORAGE_KEY: "bace_lead_segments_v1",
    CRM_MAP: CRM_MAP,
    tagMeta: function (t) {
      return TAG_META[t] || { label: t, cat: "other", cls: "mk-tag--other" };
    },
  };
})(typeof window !== "undefined" ? window : this);
