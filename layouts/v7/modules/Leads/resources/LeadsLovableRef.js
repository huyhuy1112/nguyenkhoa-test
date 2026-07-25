/**
 * Lovable Leads UI — filtered reference (from user paste, Jun 2026).
 * Source routes: /leads/, /leads/$leadId, /leads/create
 * Port target: LeadsMkList.js, LeadsMkList.css, Detail, Edit templates.
 */
(function (root) {
  "use strict";

  function isVi() {
    // SALES UI mặc định tiếng Việt — chỉ dùng EN khi language rõ ràng là en*.
    try {
      var lang =
        typeof app !== "undefined" && app.getUserLanguage
          ? String(app.getUserLanguage() || "").toLowerCase()
          : "";
      if (!lang) return true;
      if (lang.indexOf("en") === 0) return false;
      if (lang.indexOf("vi") === 0 || lang === "vn" || lang.indexOf("vn_") === 0) return true;
      return true;
    } catch (e) {
      return true;
    }
  }

  function pickLabel(vi, en) {
    // Product UI = VI-first; luôn ưu tiên nhãn tiếng Việt khi có.
    return vi || en || "";
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

  // Customer type tags (used across Leads/Leads UI + inline tags).
  // Keep canonical keys as stored in CRM tags.
  TAG_META_RAW["individual"] = {
    vi: "Cá nhân",
    en: "Individual",
    cat: "customer-type",
    cls: "mk-tag--individual",
  };
  TAG_META_RAW["company"] = {
    vi: "Doanh nghiệp",
    en: "Company",
    cat: "customer-type",
    cls: "mk-tag--company",
  };
  // Some flows may store normalized Vietnamese keys.
  TAG_META_RAW["ca_nhan"] = TAG_META_RAW["individual"];
  TAG_META_RAW["co_quan"] = {
    vi: "Có quán",
    en: "Has store",
    cat: "customer-type",
    cls: "mk-tag--co-quan",
  };
  TAG_META_RAW["chuan_bi_mo"] = {
    vi: "Chuẩn bị mở",
    en: "Preparing to open",
    cat: "customer-type",
    cls: "mk-tag--chuan-bi-mo",
  };
  TAG_META_RAW["gia_dinh"] = {
    vi: "Gia đình",
    en: "Family",
    cat: "customer-type",
    cls: "mk-tag--gia-dinh",
  };
  TAG_META_RAW["hotline"] = { vi: "Hotline", en: "Hotline", cat: "source", cls: "mk-tag--hotline" };
  TAG_META_RAW["ladipage_fb"] = { vi: "Ladipage FB", en: "Ladipage FB", cat: "source", cls: "mk-tag--ladipage" };
  TAG_META_RAW["nguyen_lieu_chuoi"] = { vi: "NL chuỗi", en: "Chain supply", cat: "program", cls: "mk-tag--nguyen-lieu-chuoi" };
  TAG_META_RAW["mua_on_dinh"] = { vi: "Mua ổn định", en: "Stable buyer", cat: "purchase", cls: "mk-tag--mua-on-dinh" };
  TAG_META_RAW["tiem_nang"] = { vi: "Tiềm năng", en: "Potential", cat: "other", cls: "mk-tag--tiem-nang" };
  TAG_META_RAW["dang_cham_soc"] = { vi: "Đang chăm sóc", en: "In care", cat: "other", cls: "mk-tag--dang-cham-soc" };
  TAG_META_RAW["dang_tu_van"] = { vi: "Đang tư vấn", en: "Consulting", cat: "other", cls: "mk-tag--dang-tu-van" };
  TAG_META_RAW["kh_can_nhac"] = { vi: "KH cân nhắc", en: "Considering", cat: "other", cls: "mk-tag--kh-can-nhac" };
  TAG_META_RAW["kv1"] = { vi: "Khu vực 1", en: "Area 1", cat: "region", cls: "mk-tag--kv1" };
  TAG_META_RAW["kv2"] = { vi: "Khu vực 2", en: "Area 2", cat: "region", cls: "mk-tag--kv2" };
  TAG_META_RAW["kv3"] = { vi: "Khu vực 3", en: "Area 3", cat: "region", cls: "mk-tag--kv3" };
  TAG_META_RAW["moi_quen"] = { vi: "Mới quen", en: "New contact", cat: "other", cls: "mk-tag--moi-quen" };
  TAG_META_RAW["da_co_quan_he"] = { vi: "Đã có quan hệ", en: "Has relationship", cat: "other", cls: "mk-tag--da-co-quan-he" };
  TAG_META_RAW["chua_mqbh"] = { vi: "Chưa MQBH", en: "No MQBH", cat: "other", cls: "mk-tag--chua-mqbh" };
  TAG_META_RAW["da_tg_free"] = { vi: "Đã TG Free", en: "Joined free", cat: "other", cls: "mk-tag--da-tg-free" };
  TAG_META_RAW["da_tg_fb1"] = { vi: "Đã TG FB1", en: "Joined FB1", cat: "other", cls: "mk-tag--da-tg-fb1" };
  TAG_META_RAW["thu_3"] = { vi: "Thứ 3", en: "Tuesday", cat: "other", cls: "mk-tag--thu-3" };
  TAG_META_RAW["da_ky_quy"] = { vi: "Đã ký quỹ", en: "Deposit signed", cat: "other", cls: "mk-tag--da-ky-quy" };
  TAG_META_RAW["xac_nhan_tham_gia"] = { vi: "Xác nhận tham gia", en: "Confirmed", cat: "other", cls: "mk-tag--xac-nhan" };
  TAG_META_RAW["khong_xac_nhan_tham_gia"] = { vi: "Không tham gia", en: "Not joining", cat: "other", cls: "mk-tag--khong-xac-nhan" };
  TAG_META_RAW["l1"] = { vi: "L1", en: "L1", cat: "other", cls: "mk-tag--l1" };
  TAG_META_RAW["l2"] = { vi: "L2", en: "L2", cat: "other", cls: "mk-tag--l2" };
  TAG_META_RAW["chua_990k"] = { vi: "Chưa 990k", en: "Not 990k", cat: "other", cls: "mk-tag--chua-990k" };
  TAG_META_RAW["da_990k"] = { vi: "Đã 990k", en: "Paid 990k", cat: "other", cls: "mk-tag--da-990k" };
  TAG_META_RAW["lop_online"] = { vi: "Lớp online", en: "Online class", cat: "other", cls: "mk-tag--lop-online" };
  TAG_META_RAW["moi_lai"] = { vi: "Mời lại", en: "Re-invite", cat: "other", cls: "mk-tag--moi-lai" };
  TAG_META_RAW["doi_lich"] = { vi: "Dời lịch", en: "Reschedule", cat: "other", cls: "mk-tag--doi-lich" };
  TAG_META_RAW["khong_nghe_may"] = { vi: "Không nghe máy", en: "No answer", cat: "other", cls: "mk-tag--khong-nghe-may" };
  TAG_META_RAW["thue_bao"] = { vi: "Thuê bao", en: "Unreachable", cat: "other", cls: "mk-tag--thue-bao" };
  TAG_META_RAW["trung_so"] = { vi: "Trùng số", en: "Duplicate", cat: "other", cls: "mk-tag--trung-so" };
  TAG_META_RAW["ngung_cham_soc"] = { vi: "Ngừng chăm sóc", en: "Stopped care", cat: "other", cls: "mk-tag--ngung-cham-soc" };
  TAG_META_RAW["hoan_tien_lop_hoc"] = { vi: "Hoàn tiền lớp học", en: "Class refund", cat: "other", cls: "mk-tag--hoan-tien-lop-hoc" };
  TAG_META_RAW["khong_hoc"] = { vi: "Không học", en: "Not studying", cat: "other", cls: "mk-tag--khong-hoc" };
  TAG_META_RAW["da_pcth"] = { vi: "Đã PCTH", en: "Done PCTH", cat: "other", cls: "mk-tag--da-pcth" };
  TAG_META_RAW["chua_pcth"] = { vi: "Chưa PCTH", en: "No PCTH", cat: "other", cls: "mk-tag--chua-pcth" };
  TAG_META_RAW["dung_cham_soc"] = { vi: "Dừng chăm sóc", en: "Stop care", cat: "other", cls: "mk-tag--dung-cham-soc" };
  TAG_META_RAW["mua_it_lai"] = { vi: "Mua ít lại", en: "Buy less", cat: "other", cls: "mk-tag--mua-it-lai" };
  TAG_META_RAW["mien_bac"] = { vi: "Miền Bắc", en: "North", cat: "region", cls: "mk-tag--mien-bac" };
  TAG_META_RAW["tham_khao"] = { vi: "Tham khảo", en: "Reference", cat: "other", cls: "mk-tag--tham-khao" };
  TAG_META_RAW["khong_du_tai_chinh"] = { vi: "Không đủ tài chính", en: "Not enough budget", cat: "other", cls: "mk-tag--khong-du-tai-chinh" };
  TAG_META_RAW["chua_mqbb"] = { vi: "Chưa MQBB", en: "No MQBB", cat: "other", cls: "mk-tag--chua-mqbb" };
  TAG_META_RAW["da_mqbb"] = { vi: "Đã MQBB", en: "Has MQBB", cat: "other", cls: "mk-tag--da-mqbb" };
  TAG_META_RAW["chua_mqbb_chua_pcth"] = { vi: "Chưa MQBB + Chưa PCTH", en: "No MQBB + No PCTH", cat: "other", cls: "mk-tag--chua-mqbb-chua-pcth" };
  TAG_META_RAW["chua_mqbb_da_pcth"] = { vi: "Chưa MQBB + Đã PCTH", en: "No MQBB + Done PCTH", cat: "other", cls: "mk-tag--chua-mqbb-da-pcth" };
  TAG_META_RAW["da_mqbb_chua_pcth"] = { vi: "Đã MQBB + Chưa PCTH", en: "MQBB + No PCTH", cat: "other", cls: "mk-tag--da-mqbb-chua-pcth" };
  TAG_META_RAW["da_mqbb_da_pcth"] = { vi: "Đã MQBB + Đã PCTH", en: "MQBB + Done PCTH", cat: "other", cls: "mk-tag--da-mqbb-da-pcth" };
  TAG_META_RAW["other_source"] = TAG_META_RAW["other"];
  TAG_META_RAW["KV1"] = TAG_META_RAW["kv1"];
  TAG_META_RAW["KV2"] = TAG_META_RAW["kv2"];
  TAG_META_RAW["KV3"] = TAG_META_RAW["kv3"];
  TAG_META_RAW["L1"] = TAG_META_RAW["l1"];
  TAG_META_RAW["L2"] = TAG_META_RAW["l2"];

  /** Tags available on Create form — used for list/inline tag editors. */
  var CREATE_TAG_GROUPS = [
    {
      id: "source",
      labelVi: "Nguồn",
      labelEn: "Source",
      tags: ["facebook", "tiktok", "website", "zalo", "other"],
    },
    {
      id: "customer",
      labelVi: "Loại / trạng thái khách",
      labelEn: "Customer",
      tags: ["individual", "company", "co_quan", "chuan_bi_mo", "gia_dinh"],
    },
    {
      id: "learning",
      labelVi: "Học",
      labelEn: "Learning",
      tags: ["chua_hoc", "da_hoc"],
    },
    {
      id: "program",
      labelVi: "Chương trình",
      labelEn: "Program",
      tags: ["mien_phi_online", "mien_phi_offline", "pcth", "van_hanh", "mkt", "lop_khac", "nhuong_quyen"],
    },
    {
      id: "purchase",
      labelVi: "Tình trạng mua",
      labelEn: "Purchase",
      tags: ["mua_lan_dau", "mua_lai", "khong_mua", "ngung_mua"],
    },
    {
      id: "region",
      labelVi: "Khu vực",
      labelEn: "Region",
      tags: ["kv1", "kv2", "kv3", "mien_bac"],
    },
    {
      id: "care",
      labelVi: "Chăm sóc / trạng thái",
      labelEn: "Care",
      tags: [
        "dang_tu_van", "dung_cham_soc", "kh_can_nhac", "mua_it_lai", "nguyen_lieu_chuoi",
        "khong_nghe_may", "thue_bao", "tiem_nang", "tham_khao", "khong_du_tai_chinh", "da_ky_quy",
        "thu_3", "lop_online", "moi_lai", "da_tg_free", "doi_lich", "l1", "l2", "khong_hoc",
        "trung_so", "ngung_cham_soc", "chua_mqbb", "da_mqbb", "chua_mqbb_chua_pcth",
        "chua_mqbb_da_pcth", "da_mqbb_chua_pcth", "da_mqbb_da_pcth", "da_pcth", "chua_pcth",
        "da_990k", "chua_990k", "hoan_tien_lop_hoc",
      ],
    },
  ];

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
    "checkbox", "created", "lead", "phone", "area", "address", "source", "customerType",
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

  var TAG_ALIASES = {
    other_source: "other",
    ca_nhan: "individual",
    gold: "vang",
    silver: "bac",
    bronze: "dong",
    first_purchase: "mua_lan_dau",
    new_purchase: "mua_lan_dau",
    repeat_purchase: "mua_lai",
    not_buying: "khong_mua",
    stopped_buying: "ngung_mua",
    free_online: "mien_phi_online",
    free_offline: "mien_phi_offline",
    free_class: "mien_phi_online",
    franchise: "nhuong_quyen",
    operations: "van_hanh",
    marketing: "mkt",
    other_class: "lop_khac",
    not_studied: "chua_hoc",
    studied: "da_hoc",
    potential: "tiem_nang",
    confirmed: "xac_nhan_tham_gia",
    not_joining: "khong_xac_nhan_tham_gia",
    deposited: "da_ky_quy",
    individual: "individual",
    company: "company",
    family: "gia_dinh",
    has_store: "co_quan",
    preparing_to_open: "chuan_bi_mo",
  };

  function slugifyTag(label) {
    var s = String(label || "").trim().toLowerCase();
    if (!s) return "";
    if (s.charAt(0) === "#") s = s.slice(1);
    try {
      s = s.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    } catch (e) {}
    return s
      .replace(/đ/g, "d")
      .replace(/[^a-z0-9]+/g, "_")
      .replace(/^_+|_+$/g, "")
      .replace(/_+/g, "_");
  }

  function normalizeTagKey(raw) {
    var s = String(raw == null ? "" : raw).trim();
    if (!s) return "";
    if (s.charAt(0) === "#") s = s.slice(1);
    s = s.replace(/\s+/g, "_");
    var lower = s.toLowerCase();
    if (/^\d{1,2}$/.test(s)) {
      return "goi_lan_" + parseInt(s, 10);
    }
    if (/^goi[_\s-]?lan[_\s-]?(\d+)$/i.test(s)) {
      return "goi_lan_" + parseInt(RegExp.$1, 10);
    }
    if (TAG_ALIASES[lower]) return TAG_ALIASES[lower];
    var slug = slugifyTag(s);
    if (TAG_ALIASES[slug]) return TAG_ALIASES[slug];
    if (/^kv[123]$/i.test(s)) return lower;
    if (/^l[12]$/i.test(s)) return lower;
    // Normalize mixed-case CRM keys (chua_MQBB_da_PCTH → chua_mqbb_da_pcth)
    if (/^[a-z0-9_]+$/i.test(s)) {
      return TAG_ALIASES[lower] || lower;
    }
    if (slug && TAG_META_RAW[slug]) return slug;
    return slug || s;
  }

  function humanizeTagKey(key) {
    if (!key) return "";
    return String(key)
      .replace(/_/g, " ")
      .replace(/\b\w/g, function (c) {
        return c.toUpperCase();
      });
  }

  function tagMeta(t) {
    var key = normalizeTagKey(t);
    if (!key) {
      return { label: "", cat: "other", cls: "mk-tag--other", key: "" };
    }
    var raw = TAG_META_RAW[key] || TAG_META_RAW[String(t || "").trim()];
    if (raw) {
      return { label: pickLabel(raw.vi, raw.en), cat: raw.cat, cls: raw.cls, key: key };
    }
    if (SEGMENT_LABELS_RAW[key]) {
      return {
        label: pickLabel(SEGMENT_LABELS_RAW[key].vi, SEGMENT_LABELS_RAW[key].en),
        cat: "segment",
        cls: "mk-tag--" + key.replace(/_/g, "-"),
        key: key,
      };
    }
    var callMatch = /^goi_lan_(\d+)$/.exec(key);
    if (callMatch) {
      var n = parseInt(callMatch[1], 10);
      return {
        label: pickLabel("Gọi lần " + n, "Call #" + n),
        cat: "call",
        cls: "mk-tag--goi-lan-" + Math.min(n, 3),
        key: key,
      };
    }
    return { label: humanizeTagKey(key), cat: "other", cls: "mk-tag--other", key: key };
  }

  function labelForTag(key, fallback) {
    var meta = tagMeta(key);
    if (meta && meta.label) {
      return meta.label;
    }
    if (fallback) return fallback;
    return key;
  }

  function getCreateTagCatalog() {
    return CREATE_TAG_GROUPS.map(function (g) {
      return {
        id: g.id,
        label: pickLabel(g.labelVi, g.labelEn),
        tags: (g.tags || []).map(function (k) {
          var meta = tagMeta(k);
          return { key: meta.key || k, label: meta.label || k };
        }),
      };
    });
  }

  function getCreateTagKeys() {
    var keys = [];
    CREATE_TAG_GROUPS.forEach(function (g) {
      (g.tags || []).forEach(function (k) {
        var nk = normalizeTagKey(k);
        if (nk && keys.indexOf(nk) < 0) keys.push(nk);
      });
    });
    return keys;
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
    labelForTag: labelForTag,
    humanizeTagKey: humanizeTagKey,
    normalizeTagKey: normalizeTagKey,
    normalizeTag: normalizeTagKey,
    getCreateTagCatalog: getCreateTagCatalog,
    getCreateTagKeys: getCreateTagKeys,
    CREATE_TAG_GROUPS: CREATE_TAG_GROUPS,
  };
})(typeof window !== "undefined" ? window : this);
