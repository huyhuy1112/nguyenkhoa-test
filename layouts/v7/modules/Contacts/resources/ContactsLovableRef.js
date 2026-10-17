/**
 * Contacts list — tag categories per BA Excel (distinct from Leads / Opp).
 *
 * Loại khách = công ty | cá nhân
 * Nhóm NVL   = miutea | khách lẻ  (import / lọc)
 * Tình trạng = đã/chưa có quán, gia đình… (không còn gọi là loại khách)
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

  var TAG_ALIASES = {
    gold: "vang",
    silver: "bac",
    bronze: "dong",
    ch_moi_quen: "moi_quen",
    co_quan_he: "da_co_quan_he",
    da_co_quan: "co_quan",
    da_co_quan_: "co_quan",
    chua_co_quan: "chuan_bi_mo",
    chua_co_quan_: "chuan_bi_mo",
    chi_moi_quan: "chuan_bi_mo",
    ch_mo_quan: "chuan_bi_mo",
    gia_dinh: "gia_dinh",
    chua_mqbh: "chua_mqbh",
    da_tg_free: "da_tg_free",
    da_tg_fb1: "da_tg_fb1",
    da_tg_f_b1: "da_tg_fb1",
    thu_3: "thu_3",
    pcth: "pcth",
    chuong_trinh_pcth: "pcth",
    van_hanh: "van_hanh",
    mkt: "mkt",
    lop_khac: "lop_khac",
    mien_phi_online: "mien_phi_online",
    mien_phi_offline: "mien_phi_offline",
    da_mqbb: "da_mqbb",
    da_990k: "da_990k",
    da_pcth: "da_pcth",
    da_pcthcb: "da_pcthcb",
    da_pcth_cb: "da_pcthcb",
    offline_hen_goi_lai: "offline_hen_goi_lai",
    offline_khong_nghe_may: "offline_khong_nghe_may",
    offline_sai_thong_tin: "offline_sai_thong_tin",
    offline_hen_lich_lai: "offline_hen_lich_lai",
    offline_chuyen_chuong_trinh: "offline_chuyen_chuong_trinh",
    offline_ngung_cskh: "offline_ngung_cskh",
    tiem_nang: "tiem_nang",
    mua_lan_dau: "mua_lan_dau",
    mua_lai: "mua_lai",
    mua_on_dinh: "mua_on_dinh",
    dang_cham_soc: "dang_cham_soc",
    dang_tu_van: "dang_tu_van",
    kh_can_nhac: "kh_can_nhac",
    khong_mua: "khong_mua",
    ngung_mua: "ngung_mua",
    nhuong_quyen: "nhuong_quyen",
    da_ky_quy: "da_ky_quy",
    vang: "vang",
    bac: "bac",
    dong: "dong",
    moi_quen: "moi_quen",
    da_co_quan_he: "da_co_quan_he",
    co_quan: "co_quan",
    chuan_bi_mo: "chuan_bi_mo",
    // Loại khách (pháp nhân)
    individual: "ca_nhan",
    ca_nhan: "ca_nhan",
    company: "cong_ty",
    cong_ty: "cong_ty",
    doanh_nghiep: "cong_ty",
    // Nhóm NVL / kênh import
    miutea: "miutea",
    khach_le: "khach_le",
    khachle: "khach_le",
    khach_le_: "khach_le",
  };

  var TAG_META_RAW = {
    // Loại khách — công ty | cá nhân
    ca_nhan: { vi: "Cá nhân", en: "Individual", cat: "customerType", cls: "mk-tag--ca-nhan" },
    cong_ty: { vi: "Công ty", en: "Company", cat: "customerType", cls: "mk-tag--cong-ty" },
    // Nhóm NVL — miutea | khách lẻ
    miutea: { vi: "Miutea", en: "Miutea", cat: "nvlSegment", cls: "mk-tag--miutea" },
    khach_le: { vi: "Khách lẻ", en: "Retail", cat: "nvlSegment", cls: "mk-tag--khach-le" },
    // Tình trạng khách (đã/chưa quán…) — không còn là "Loại khách"
    moi_quen: { vi: "CH - Mới quen", en: "New contact", cat: "customerStatus", cls: "mk-tag--moi-quen" },
    da_co_quan_he: { vi: "Đã có quan hệ", en: "Has relationship", cat: "customerStatus", cls: "mk-tag--co-quan-he" },
    co_quan: { vi: "Đã có quán", en: "Has store", cat: "customerStatus", cls: "mk-tag--co-quan" },
    chuan_bi_mo: { vi: "Chưa có quán", en: "No store yet", cat: "customerStatus", cls: "mk-tag--chuan-bi-mo" },
    gia_dinh: { vi: "Gia đình", en: "Family", cat: "customerStatus", cls: "mk-tag--gia-dinh" },
    da_cap_bang: { vi: "Cấp bằng", en: "Certificate", cat: "customerStatus", cls: "mk-tag--da-cap-bang" },
    da_cap_tai_khoan: { vi: "Đã cấp tài khoản", en: "Account issued", cat: "customerStatus", cls: "mk-tag--da-cap-tai-khoan" },
    chua_mqbh: { vi: "Chưa MQBH", en: "No MQBH", cat: "classTag", cls: "mk-tag--chua-mqbh" },
    da_tg_free: { vi: "Đã TG FREE", en: "Attended FREE", cat: "classTag", cls: "mk-tag--da-tg-free" },
    da_tg_fb1: { vi: "Đã TG F&B1", en: "Attended F&B1", cat: "classTag", cls: "mk-tag--da-tg-fb1" },
    thu_3: { vi: "THỨ 3", en: "Tuesday", cat: "classTag", cls: "mk-tag--thu-3" },
    pcth: { vi: "PCTH", en: "PCTH", cat: "classTag", cls: "mk-tag--pcth" },
    da_pcth: { vi: "Đã PCTH", en: "Done PCTH", cat: "classTag", cls: "mk-tag--da-pcth" },
    da_pcthcb: { vi: "Đã PCTHCB", en: "Done PCTHCB", cat: "classTag", cls: "mk-tag--da-pcthcb" },
    da_mqbb: { vi: "Đã MQBB", en: "Done MQBB", cat: "classTag", cls: "mk-tag--da-mqbb" },
    da_990k: { vi: "Đã 990k", en: "Paid 990k", cat: "classTag", cls: "mk-tag--da-990k" },
    gd14_moi_dang_ky: { vi: "990k — Mới đăng ký", en: "990k — New", cat: "classTag", cls: "mk-tag--da-990k" },
    gd14_chua_xep_buoi: { vi: "990k — Chưa xếp buổi học", en: "990k — Unscheduled", cat: "classTag", cls: "mk-tag--da-990k" },
    gd14_da_xac_nhan_lich: { vi: "990k — Đã xác nhận lịch học", en: "990k — Class confirmed", cat: "classTag", cls: "mk-tag--da-990k" },
    gd14_khong_tham_gia: { vi: "990k — Không tham gia lớp học", en: "990k — Absent", cat: "classTag", cls: "mk-tag--da-990k" },
    gd14_da_tham_gia: { vi: "990k — Đã tham gia lớp học", en: "990k — Attended", cat: "classTag", cls: "mk-tag--da-990k" },
    combo_mo_quan: { vi: "Combo giải pháp mở quán", en: "Open-store combo", cat: "classTag", cls: "mk-tag--da-mqbb" },
    mien_phi_offline: { vi: "Miễn phí Offline", en: "Free Offline", cat: "classTag", cls: "mk-tag--mien-phi-offline" },
    mien_phi_online: { vi: "Miễn phí Online", en: "Free Online", cat: "classTag", cls: "mk-tag--mien-phi-online" },
    van_hanh: { vi: "Vận hành", en: "Operations", cat: "classTag", cls: "mk-tag--van-hanh" },
    mkt: { vi: "MKT", en: "MKT", cat: "classTag", cls: "mk-tag--mkt" },
    lop_khac: { vi: "Lớp khác", en: "Other class", cat: "classTag", cls: "mk-tag--lop-khac" },
    offline_hen_goi_lai: { vi: "Hẹn gọi lại", en: "Callback", cat: "offline", cls: "mk-tag--offline" },
    offline_khong_nghe_may: { vi: "Không nghe máy", en: "No answer", cat: "offline", cls: "mk-tag--offline" },
    offline_sai_thong_tin: { vi: "Sai thông tin", en: "Bad info", cat: "offline", cls: "mk-tag--offline" },
    offline_hen_lich_lai: { vi: "Hẹn lịch lại", en: "Reschedule", cat: "offline", cls: "mk-tag--offline" },
    offline_chuyen_chuong_trinh: { vi: "Chuyển CT", en: "Switch program", cat: "offline", cls: "mk-tag--offline" },
    offline_ngung_cskh: { vi: "Ngưng CSKH", en: "Stop care", cat: "offline", cls: "mk-tag--offline" },
    tiem_nang: { vi: "Tiềm năng", en: "Potential", cat: "material", cls: "mk-tag--tiem-nang" },
    mua_lan_dau: { vi: "Mua lần đầu", en: "First purchase", cat: "material", cls: "mk-tag--mua-lan-dau" },
    mua_lai: { vi: "Mua lại", en: "Repeat purchase", cat: "material", cls: "mk-tag--mua-lai" },
    mua_on_dinh: { vi: "Mua ổn định", en: "Stable purchase", cat: "material", cls: "mk-tag--mua-on-dinh" },
    dang_cham_soc: { vi: "Đang chăm sóc", en: "In care", cat: "material", cls: "mk-tag--dang-cham-soc" },
    dang_tu_van: { vi: "Đang tư vấn", en: "Consulting", cat: "franchise", cls: "mk-tag--dang-tu-van" },
    kh_can_nhac: { vi: "KH Cân Nhắc", en: "Considering", cat: "material", cls: "mk-tag--kh-can-nhac" },
    khong_mua: { vi: "Không mua", en: "Not buying", cat: "material", cls: "mk-tag--khong-mua" },
    ngung_mua: { vi: "Ngưng mua", en: "Stopped buying", cat: "material", cls: "mk-tag--ngung-mua" },
    nhuong_quyen: { vi: "Nhượng quyền", en: "Franchise", cat: "franchise", cls: "mk-tag--nhuong-quyen" },
    da_ky_quy: { vi: "Đã Ký Quỹ", en: "Deposited", cat: "franchise", cls: "mk-tag--da-ky-quy" },
    vang: { vi: "Vàng", en: "Gold", cat: "tier", cls: "mk-tag--vang" },
    bac: { vi: "Bạc", en: "Silver", cat: "tier", cls: "mk-tag--bac" },
    dong: { vi: "Đồng", en: "Bronze", cat: "tier", cls: "mk-tag--dong" },
  };

  /** Loại khách — chỉ công ty | cá nhân */
  var CUSTOMER_TYPE_TAGS = ["ca_nhan", "cong_ty"];
  /** Nhóm NVL / kênh — miutea | khách lẻ */
  var NVL_SEGMENT_TAGS = ["miutea", "khach_le"];
  /** Tình trạng khách — đã/chưa quán, gia đình… */
  var CUSTOMER_STATUS_TAGS = ["co_quan", "chuan_bi_mo", "gia_dinh", "moi_quen", "da_co_quan_he"];
  /** @deprecated use CUSTOMER_STATUS_TAGS — kept for older callers */
  var CUSTOMER_RANK_TAGS = CUSTOMER_STATUS_TAGS;
  // Credential status uses list dropdowns only — not tag chips.
  var CREDENTIAL_TAGS = [];
  var CLASS_TAGS = [
    "da_mqbb",
    "da_990k",
    "gd14_moi_dang_ky",
    "gd14_chua_xep_buoi",
    "gd14_da_xac_nhan_lich",
    "gd14_khong_tham_gia",
    "gd14_da_tham_gia",
    "combo_mo_quan",
    "da_pcth",
    "da_pcthcb",
    "mien_phi_offline",
    "mien_phi_online",
    "chua_mqbh",
    "da_tg_free",
    "da_tg_fb1",
    "thu_3",
    "pcth",
    "van_hanh",
    "mkt",
    "lop_khac",
  ];
  var OFFLINE_STATUS_TAGS = [
    "offline_hen_goi_lai",
    "offline_khong_nghe_may",
    "offline_sai_thong_tin",
    "offline_hen_lich_lai",
    "offline_chuyen_chuong_trinh",
    "offline_ngung_cskh",
  ];
  var MATERIAL_TAGS = [
    "tiem_nang", "mua_lan_dau", "mua_lai", "mua_on_dinh", "dang_cham_soc",
    "kh_can_nhac", "khong_mua", "ngung_mua",
  ];
  var FRANCHISE_TAGS = ["nhuong_quyen", "da_ky_quy", "dang_tu_van"];
  var TIER_TAGS = ["vang", "bac", "dong"];

  function slugify(label) {
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

  function normalizeTag(tag) {
    var slug = slugify(tag);
    if (!slug) return "";
    return TAG_ALIASES[slug] || slug;
  }

  function findTagInPool(tags, pool) {
    if (!tags || !tags.length || !pool || !pool.length) return null;
    var normalizedTags = tags.map(function (t) {
      return { raw: t, key: normalizeTag(t) };
    });
    for (var i = 0; i < pool.length; i++) {
      var want = normalizeTag(pool[i]);
      for (var j = 0; j < normalizedTags.length; j++) {
        if (normalizedTags[j].key === want) return normalizedTags[j].raw;
      }
    }
    return null;
  }

  function categorizeTags(tags) {
    var status = findTagInPool(tags, CUSTOMER_STATUS_TAGS);
    return {
      customerType: findTagInPool(tags, CUSTOMER_TYPE_TAGS),
      nvlSegment: findTagInPool(tags, NVL_SEGMENT_TAGS),
      customerStatus: status,
      // Alias: older list code used customerRank for "đã/chưa quán"
      customerRank: status,
      classTag: findTagInPool(tags, CLASS_TAGS),
      material: findTagInPool(tags, MATERIAL_TAGS),
      franchise: findTagInPool(tags, FRANCHISE_TAGS),
      tier: findTagInPool(tags, TIER_TAGS),
    };
  }

  function tagMeta(tag) {
    var key = normalizeTag(tag);
    var raw = TAG_META_RAW[key];
    if (!raw) {
      return { label: String(tag || ""), cat: "other", cls: "mk-tag--other", key: key };
    }
    return {
      label: pickLabel(raw.vi, raw.en),
      cat: raw.cat,
      cls: raw.cls,
      key: key,
    };
  }

  function labelForTag(key, fallback) {
    var meta = tagMeta(key);
    if (meta && meta.label) return meta.label;
    return fallback || key;
  }

  function isAllowedContactTag(tag) {
    return !!TAG_META_RAW[normalizeTag(tag)];
  }

  /** Groups for list / inline tag editor */
  var CREATE_TAG_GROUPS = [
    { id: "tier", labelVi: "Hạng khách", labelEn: "Tier", tags: TIER_TAGS },
    {
      id: "customerType",
      labelVi: "Loại khách",
      labelEn: "Customer type",
      tags: CUSTOMER_TYPE_TAGS,
    },
    {
      id: "nvlSegment",
      labelVi: "Nhóm NVL",
      labelEn: "NVL segment",
      tags: NVL_SEGMENT_TAGS,
    },
    {
      id: "customerStatus",
      labelVi: "Tình trạng khách",
      labelEn: "Customer status",
      tags: CUSTOMER_STATUS_TAGS,
    },
    { id: "class", labelVi: "Lớp học", labelEn: "Class", tags: CLASS_TAGS },
    { id: "material", labelVi: "Tag nguyên liệu", labelEn: "Material", tags: MATERIAL_TAGS },
    { id: "franchise", labelVi: "Tag nhượng quyền", labelEn: "Franchise", tags: FRANCHISE_TAGS },
  ];

  function getCreateTagCatalog() {
    return CREATE_TAG_GROUPS.map(function (g) {
      return {
        id: g.id,
        label: pickLabel(g.labelVi, g.labelEn),
        tags: (g.tags || []).map(function (k) {
          var meta = tagMeta(k);
          return {
            key: meta.key || normalizeTag(k) || k,
            label: meta.label || k,
            cls: meta.cls || "",
          };
        }),
      };
    });
  }

  function getCreateTagKeys() {
    var keys = [];
    CREATE_TAG_GROUPS.forEach(function (g) {
      (g.tags || []).forEach(function (k) {
        var nk = normalizeTag(k);
        if (nk && keys.indexOf(nk) < 0) keys.push(nk);
      });
    });
    return keys;
  }

  root.ContactsLovableRef = {
    TAG_META_RAW: TAG_META_RAW,
    CUSTOMER_TYPE_TAGS: CUSTOMER_TYPE_TAGS,
    NVL_SEGMENT_TAGS: NVL_SEGMENT_TAGS,
    CUSTOMER_STATUS_TAGS: CUSTOMER_STATUS_TAGS,
    CUSTOMER_RANK_TAGS: CUSTOMER_RANK_TAGS,
    CREDENTIAL_TAGS: CREDENTIAL_TAGS,
    CLASS_TAGS: CLASS_TAGS,
    OFFLINE_STATUS_TAGS: OFFLINE_STATUS_TAGS,
    MATERIAL_TAGS: MATERIAL_TAGS,
    FRANCHISE_TAGS: FRANCHISE_TAGS,
    TIER_TAGS: TIER_TAGS,
    CREATE_TAG_GROUPS: CREATE_TAG_GROUPS,
    isVi: isVi,
    pickLabel: pickLabel,
    normalizeTag: normalizeTag,
    findTagInPool: findTagInPool,
    categorizeTags: categorizeTags,
    tagMeta: tagMeta,
    labelForTag: labelForTag,
    isAllowedContactTag: isAllowedContactTag,
    getCreateTagCatalog: getCreateTagCatalog,
    getCreateTagKeys: getCreateTagKeys,
  };
})(typeof window !== "undefined" ? window : this);
