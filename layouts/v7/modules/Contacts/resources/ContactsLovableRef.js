/**
 * Contacts list — tag categories per BA Excel (distinct from Leads / Opp).
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
  };

  var TAG_META_RAW = {
    moi_quen: { vi: "CH - Mới quen", en: "New contact", cat: "customerRank", cls: "mk-tag--moi-quen" },
    da_co_quan_he: { vi: "Đã có quan hệ", en: "Has relationship", cat: "customerRank", cls: "mk-tag--co-quan-he" },
    co_quan: { vi: "Đã có quán", en: "Has store", cat: "customerRank", cls: "mk-tag--co-quan" },
    chuan_bi_mo: { vi: "Chưa có quán", en: "No store yet", cat: "customerRank", cls: "mk-tag--chuan-bi-mo" },
    gia_dinh: { vi: "Gia đình", en: "Family", cat: "customerRank", cls: "mk-tag--gia-dinh" },
    chua_mqbh: { vi: "Chưa MQBH", en: "No MQBH", cat: "classTag", cls: "mk-tag--chua-mqbh" },
    da_tg_free: { vi: "Đã TG FREE", en: "Attended FREE", cat: "classTag", cls: "mk-tag--da-tg-free" },
    da_tg_fb1: { vi: "Đã TG F&B1", en: "Attended F&B1", cat: "classTag", cls: "mk-tag--da-tg-fb1" },
    thu_3: { vi: "THỨ 3", en: "Tuesday", cat: "classTag", cls: "mk-tag--thu-3" },
    pcth: { vi: "PCTH", en: "PCTH", cat: "classTag", cls: "mk-tag--pcth" },
    van_hanh: { vi: "Vận hành", en: "Operations", cat: "classTag", cls: "mk-tag--van-hanh" },
    mkt: { vi: "MKT", en: "MKT", cat: "classTag", cls: "mk-tag--mkt" },
    lop_khac: { vi: "Lớp khác", en: "Other class", cat: "classTag", cls: "mk-tag--lop-khac" },
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

  /** Loại khách — khớp Trạng thái khách trên Lead (Đã/Chưa có quán, Gia đình). */
  var CUSTOMER_RANK_TAGS = ["co_quan", "chuan_bi_mo", "gia_dinh", "moi_quen", "da_co_quan_he"];
  var CLASS_TAGS = ["chua_mqbh", "da_tg_free", "da_tg_fb1", "thu_3", "pcth", "van_hanh", "mkt", "lop_khac"];
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
    return {
      customerRank: findTagInPool(tags, CUSTOMER_RANK_TAGS),
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
      return { label: String(tag || ""), cat: "other", cls: "mk-tag--other" };
    }
    return {
      label: pickLabel(raw.vi, raw.en),
      cat: raw.cat,
      cls: raw.cls,
    };
  }

  function isAllowedContactTag(tag) {
    return !!TAG_META_RAW[normalizeTag(tag)];
  }

  root.ContactsLovableRef = {
    TAG_META_RAW: TAG_META_RAW,
    CUSTOMER_RANK_TAGS: CUSTOMER_RANK_TAGS,
    CLASS_TAGS: CLASS_TAGS,
    MATERIAL_TAGS: MATERIAL_TAGS,
    FRANCHISE_TAGS: FRANCHISE_TAGS,
    TIER_TAGS: TIER_TAGS,
    isVi: isVi,
    pickLabel: pickLabel,
    normalizeTag: normalizeTag,
    findTagInPool: findTagInPool,
    categorizeTags: categorizeTags,
    tagMeta: tagMeta,
    isAllowedContactTag: isAllowedContactTag,
  };
})(typeof window !== "undefined" ? window : this);
