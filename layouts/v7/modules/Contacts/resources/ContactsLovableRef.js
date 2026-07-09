/**
 * Contacts list — tag categories per BA Excel (distinct from Leads / Opp).
 */
(function (root) {
  "use strict";

  var TAG_ALIASES = {
    gold: "vang",
    silver: "bac",
    bronze: "dong",
    ch_moi_quen: "moi_quen",
    co_quan_he: "da_co_quan_he",
    chua_mqbh: "chua_mqbh",
    da_tg_free: "da_tg_free",
    mua_lan_dau: "mua_lan_dau",
    mua_lai: "mua_lai",
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
  };

  var TAG_META = {
    moi_quen: { label: "CH - Mới quen", cat: "customerRank", cls: "mk-tag--moi-quen" },
    da_co_quan_he: { label: "Đã có quan hệ", cat: "customerRank", cls: "mk-tag--co-quan-he" },
    chua_mqbh: { label: "Chưa MQBH", cat: "classTag", cls: "mk-tag--chua-mqbh" },
    da_tg_free: { label: "Đã TG FREE", cat: "classTag", cls: "mk-tag--da-tg-free" },
    mua_lan_dau: { label: "Mua lần đầu", cat: "material", cls: "mk-tag--mua-lan-dau" },
    mua_lai: { label: "Mua lại", cat: "material", cls: "mk-tag--mua-lai" },
    dang_cham_soc: { label: "Đang chăm sóc", cat: "material", cls: "mk-tag--dang-cham-soc" },
    kh_can_nhac: { label: "KH Cần Nhắc", cat: "material", cls: "mk-tag--kh-can-nhac" },
    khong_mua: { label: "Không mua", cat: "material", cls: "mk-tag--khong-mua" },
    ngung_mua: { label: "Ngưng mua", cat: "material", cls: "mk-tag--ngung-mua" },
    nhuong_quyen: { label: "Nhượng quyền", cat: "franchise", cls: "mk-tag--nhuong-quyen" },
    da_ky_quy: { label: "Đã Ký Quỹ", cat: "franchise", cls: "mk-tag--da-ky-quy" },
    dang_tu_van: { label: "Đang tư vấn", cat: "franchise", cls: "mk-tag--dang-tu-van" },
    vang: { label: "Vàng", cat: "tier", cls: "mk-tag--vang" },
    bac: { label: "Bạc", cat: "tier", cls: "mk-tag--bac" },
    dong: { label: "Đồng", cat: "tier", cls: "mk-tag--dong" },
  };

  var CUSTOMER_RANK_TAGS = ["moi_quen", "da_co_quan_he"];
  var CLASS_TAGS = ["chua_mqbh", "da_tg_free"];
  var MATERIAL_TAGS = ["mua_lan_dau", "mua_lai", "dang_cham_soc", "kh_can_nhac", "khong_mua", "ngung_mua"];
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
    return TAG_META[key] || { label: String(tag || ""), cat: "other", cls: "mk-tag--other" };
  }

  function isAllowedContactTag(tag) {
    return !!TAG_META[normalizeTag(tag)];
  }

  root.ContactsLovableRef = {
    TAG_META: TAG_META,
    CUSTOMER_RANK_TAGS: CUSTOMER_RANK_TAGS,
    CLASS_TAGS: CLASS_TAGS,
    MATERIAL_TAGS: MATERIAL_TAGS,
    FRANCHISE_TAGS: FRANCHISE_TAGS,
    TIER_TAGS: TIER_TAGS,
    normalizeTag: normalizeTag,
    findTagInPool: findTagInPool,
    categorizeTags: categorizeTags,
    tagMeta: tagMeta,
    isAllowedContactTag: isAllowedContactTag,
  };
})(typeof window !== "undefined" ? window : this);
