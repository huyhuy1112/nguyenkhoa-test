/**
 * Opp list — tag categories per BA Excel (distinct from Leads list filters).
 */
(function (root) {
  "use strict";

  var TAG_ALIASES = {
    kv1: "kv1",
    kv2: "kv2",
    kv3: "kv3",
    facebook: "facebook",
    tiktok: "tiktok",
    ladipage_fb: "ladipage_fb",
    "ladipage fb": "ladipage_fb",
    website: "website",
    zalo: "zalo",
    other: "other",
    other_source: "other_source",
    individual: "individual",
    company: "company",
    co_quan: "co_quan",
    "da_co_quan": "co_quan",
    chuan_bi_mo: "chuan_bi_mo",
    "chi_moi_quan": "chuan_bi_mo",
    gia_dinh: "gia_dinh",
    mien_phi_online: "mien_phi_online",
    mien_phi_offline: "mien_phi_offline",
    da_tg_free: "da_tg_free",
    chua_hoc: "chua_hoc",
    da_hoc: "da_hoc",
    pcth: "pcth",
    van_hanh: "van_hanh",
    mkt: "mkt",
    lop_khac: "lop_khac",
    mua_lan_dau: "mua_lan_dau",
    mua_lai: "mua_lai",
    dang_cham_soc: "dang_cham_soc",
    dang_tu_van: "dang_tu_van",
    kh_can_nhac: "kh_can_nhac",
    khong_mua: "khong_mua",
    ngung_mua: "ngung_mua",
    nhuong_quyen: "nhuong_quyen",
    da_ky_quy: "da_ky_quy",
    xac_nhan_tham_gia: "xac_nhan_tham_gia",
    khong_xac_nhan_tham_gia: "khong_xac_nhan_tham_gia",
    vang: "vang",
    bac: "bac",
    dong: "dong",
    gold: "vang",
    silver: "bac",
    bronze: "dong",
  };

  var TAG_META = {
    kv1: { label: "KV1", cat: "area", cls: "mk-tag--kv1" },
    kv2: { label: "KV2", cat: "area", cls: "mk-tag--kv2" },
    kv3: { label: "KV3", cat: "area", cls: "mk-tag--kv3" },
    facebook: { label: "Facebook", cat: "source", cls: "mk-tag--facebook" },
    tiktok: { label: "TikTok", cat: "source", cls: "mk-tag--tiktok" },
    ladipage_fb: { label: "Ladipage FB", cat: "source", cls: "mk-tag--ladipage" },
    website: { label: "Website", cat: "source", cls: "mk-tag--website" },
    zalo: { label: "Zalo", cat: "source", cls: "mk-tag--zalo" },
    other: { label: "Khác", cat: "source", cls: "mk-tag--other" },
    other_source: { label: "Khác", cat: "source", cls: "mk-tag--other" },
    individual: { label: "Cá nhân", cat: "customer", cls: "mk-tag--individual" },
    company: { label: "Công ty", cat: "customer", cls: "mk-tag--company" },
    co_quan: { label: "Đã có quán", cat: "customer", cls: "mk-tag--co-quan" },
    chuan_bi_mo: { label: "Chị mới quán", cat: "customer", cls: "mk-tag--chuan-bi-mo" },
    gia_dinh: { label: "Gia đình", cat: "customer", cls: "mk-tag--gia-dinh" },
    mien_phi_online: { label: "Free Online", cat: "class", cls: "mk-tag--free-online" },
    mien_phi_offline: { label: "Free Offline", cat: "class", cls: "mk-tag--free-offline" },
    da_tg_free: { label: "Đã TG FREE", cat: "class", cls: "mk-tag--da-tg-free" },
    chua_hoc: { label: "Chưa học", cat: "class", cls: "mk-tag--chua-hoc" },
    da_hoc: { label: "Đã học", cat: "class", cls: "mk-tag--da-hoc" },
    pcth: { label: "PCTH", cat: "class", cls: "mk-tag--pcth" },
    nguyen_lieu_chuoi: { label: "Nguyên liệu chuỗi", cat: "class", cls: "mk-tag--nguyen-lieu-chuoi" },
    van_hanh: { label: "Vận hành", cat: "class", cls: "mk-tag--van-hanh" },
    mkt: { label: "Marketing", cat: "class", cls: "mk-tag--mkt" },
    lop_khac: { label: "Lớp khác", cat: "class", cls: "mk-tag--lop-khac" },
    mua_lan_dau: { label: "Mua lần đầu", cat: "material", cls: "mk-tag--mua-lan-dau" },
    mua_lai: { label: "Mua lại", cat: "material", cls: "mk-tag--mua-lai" },
    dang_cham_soc: { label: "Đang chăm sóc", cat: "material", cls: "mk-tag--dang-cham-soc" },
    dang_tu_van: { label: "Đang tư vấn", cat: "material", cls: "mk-tag--dang-tu-van" },
    kh_can_nhac: { label: "KH Cần Nhắc", cat: "material", cls: "mk-tag--kh-can-nhac" },
    khong_mua: { label: "Không mua", cat: "material", cls: "mk-tag--khong-mua" },
    ngung_mua: { label: "Ngưng mua", cat: "material", cls: "mk-tag--ngung-mua" },
    nhuong_quyen: { label: "Nhượng quyền", cat: "franchise", cls: "mk-tag--nhuong-quyen" },
    da_ky_quy: { label: "Đã Ký Quỹ", cat: "franchise", cls: "mk-tag--da-ky-quy" },
    xac_nhan_tham_gia: { label: "Xác nhận tham gia", cat: "confirm", cls: "mk-tag--xac-nhan" },
    khong_xac_nhan_tham_gia: { label: "Không xác nhận", cat: "confirm", cls: "mk-tag--khong-xac-nhan" },
    vang: { label: "Vàng", cat: "tier", cls: "mk-tag--vang" },
    bac: { label: "Bạc", cat: "tier", cls: "mk-tag--bac" },
    dong: { label: "Đồng", cat: "tier", cls: "mk-tag--dong" },
  };

  var AREA_TAGS = ["kv1", "kv2", "kv3", "KV1", "KV2", "KV3"];
  var SOURCE_TAGS = ["facebook", "tiktok", "ladipage_fb", "website", "zalo", "other", "other_source"];
  var CUSTOMER_TAGS = ["co_quan", "chuan_bi_mo", "gia_dinh", "individual", "company"];
  var CLASS_TAGS = ["da_tg_free", "mien_phi_online", "mien_phi_offline", "chua_hoc", "da_hoc", "pcth", "van_hanh", "mkt", "lop_khac", "nguyen_lieu_chuoi"];
  var MATERIAL_TAGS = ["mua_lan_dau", "mua_lai", "dang_cham_soc", "dang_tu_van", "kh_can_nhac", "khong_mua", "ngung_mua"];
  var FRANCHISE_TAGS = ["nhuong_quyen", "da_ky_quy", "dang_tu_van"];
  var CONFIRM_TAGS = ["xac_nhan_tham_gia", "khong_xac_nhan_tham_gia"];
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
      area: findTagInPool(tags, AREA_TAGS),
      source: findTagInPool(tags, SOURCE_TAGS),
      customer: findTagInPool(tags, CUSTOMER_TAGS),
      classTag: findTagInPool(tags, CLASS_TAGS),
      material: findTagInPool(tags, MATERIAL_TAGS),
      franchise: findTagInPool(tags, FRANCHISE_TAGS),
      confirm: findTagInPool(tags, CONFIRM_TAGS),
      tier: findTagInPool(tags, TIER_TAGS),
    };
  }

  function tagMeta(tag) {
    var key = normalizeTag(tag);
    return TAG_META[key] || { label: String(tag || ""), cat: "other", cls: "mk-tag--other" };
  }

  root.PotentialsLovableRef = {
    TAG_META: TAG_META,
    AREA_TAGS: AREA_TAGS,
    SOURCE_TAGS: SOURCE_TAGS,
    CUSTOMER_TAGS: CUSTOMER_TAGS,
    CLASS_TAGS: CLASS_TAGS,
    MATERIAL_TAGS: MATERIAL_TAGS,
    FRANCHISE_TAGS: FRANCHISE_TAGS,
    CONFIRM_TAGS: CONFIRM_TAGS,
    TIER_TAGS: TIER_TAGS,
    normalizeTag: normalizeTag,
    findTagInPool: findTagInPool,
    categorizeTags: categorizeTags,
    tagMeta: tagMeta,
  };
})(typeof window !== "undefined" ? window : this);
