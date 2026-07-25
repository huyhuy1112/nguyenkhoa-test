/**
 * Opp list — tag categories per BA Excel (distinct from Leads list filters).
 */
(function (root) {
  "use strict";

  function isVi() {
    try {
      var lang =
        typeof app !== "undefined" && app.getUserLanguage
          ? String(app.getUserLanguage() || "").toLowerCase()
          : "";
      if (!lang) return true;
      if (lang.indexOf("en") === 0) return false;
      return true;
    } catch (e) {
      return true;
    }
  }

  function pickLabel(vi, en) {
    return vi || en || "";
  }

  var TAG_ALIASES = {
    kv1: "kv1",
    kv2: "kv2",
    kv3: "kv3",
    facebook: "facebook",
    tiktok: "tiktok",
    ladipage_fb: "ladipage_fb",
    "ladipage fb": "ladipage_fb",
    ladypage_fb: "ladipage_fb",
    website: "website",
    zalo: "zalo",
    hotline: "hotline",
    other: "other",
    other_source: "other_source",
    individual: "individual",
    company: "company",
    co_quan: "co_quan",
    da_co_quan: "co_quan",
    chuan_bi_mo: "chuan_bi_mo",
    chi_moi_quan: "chuan_bi_mo",
    ch_mo_quan: "chuan_bi_mo",
    gia_dinh: "gia_dinh",
    mien_phi_online: "mien_phi_online",
    mien_phi_offline: "mien_phi_offline",
    da_tg_free: "da_tg_free",
    da_tg_fb1: "da_tg_fb1",
    da_tg_f_b1: "da_tg_fb1",
    thu_3: "thu_3",
    chua_hoc: "chua_hoc",
    da_hoc: "da_hoc",
    pcth: "pcth",
    van_hanh: "van_hanh",
    mkt: "mkt",
    lop_khac: "lop_khac",
    nguyen_lieu_chuoi: "nguyen_lieu_chuoi",
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
    xac_nhan_tham_gia: "xac_nhan_tham_gia",
    khong_xac_nhan_tham_gia: "khong_xac_nhan_tham_gia",
    vang: "vang",
    bac: "bac",
    dong: "dong",
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
    not_participating: "khong_xac_nhan_tham_gia",
    deposited: "da_ky_quy",
    family: "gia_dinh",
    has_store: "co_quan",
    preparing_to_open: "chuan_bi_mo",
    in_care: "dang_cham_soc",
    consulting: "dang_tu_van",
    considering: "kh_can_nhac",
    stable_purchase: "mua_on_dinh",
    attended_free: "da_tg_free",
    tuesday: "thu_3",
  };

  var TAG_META_RAW = {
    kv1: { vi: "Khu vực 1", en: "Area 1", cat: "area", cls: "mk-tag--kv1" },
    kv2: { vi: "Khu vực 2", en: "Area 2", cat: "area", cls: "mk-tag--kv2" },
    kv3: { vi: "Khu vực 3", en: "Area 3", cat: "area", cls: "mk-tag--kv3" },
    facebook: { vi: "Facebook", en: "Facebook", cat: "source", cls: "mk-tag--facebook" },
    tiktok: { vi: "TikTok", en: "TikTok", cat: "source", cls: "mk-tag--tiktok" },
    ladipage_fb: { vi: "Ladipage FB", en: "Ladipage FB", cat: "source", cls: "mk-tag--ladipage" },
    website: { vi: "Website", en: "Website", cat: "source", cls: "mk-tag--website" },
    zalo: { vi: "Zalo", en: "Zalo", cat: "source", cls: "mk-tag--zalo" },
    hotline: { vi: "Hotline", en: "Hotline", cat: "source", cls: "mk-tag--hotline" },
    other: { vi: "Khác", en: "Other", cat: "source", cls: "mk-tag--other" },
    other_source: { vi: "Khác", en: "Other", cat: "source", cls: "mk-tag--other" },
    individual: { vi: "Cá nhân", en: "Individual", cat: "customer", cls: "mk-tag--individual" },
    company: { vi: "Công ty", en: "Company", cat: "customer", cls: "mk-tag--company" },
    co_quan: { vi: "Đã có quán", en: "Has store", cat: "customer", cls: "mk-tag--co-quan" },
    chuan_bi_mo: { vi: "CH chuẩn bị mở quán", en: "Preparing to open", cat: "customer", cls: "mk-tag--chuan-bi-mo" },
    gia_dinh: { vi: "Gia đình", en: "Family", cat: "customer", cls: "mk-tag--gia-dinh" },
    mien_phi_online: { vi: "Miễn phí Online", en: "Free Online", cat: "class", cls: "mk-tag--free-online" },
    mien_phi_offline: { vi: "Miễn phí Offline", en: "Free Offline", cat: "class", cls: "mk-tag--free-offline" },
    da_tg_free: { vi: "Đã TG FREE", en: "Attended FREE", cat: "class", cls: "mk-tag--da-tg-free" },
    da_tg_fb1: { vi: "Đã TG F&B1", en: "Attended F&B1", cat: "class", cls: "mk-tag--da-tg-fb1" },
    thu_3: { vi: "THỨ 3", en: "Tuesday", cat: "class", cls: "mk-tag--thu-3" },
    chua_hoc: { vi: "Chưa học", en: "Not studied", cat: "class", cls: "mk-tag--chua-hoc" },
    da_hoc: { vi: "Đã học", en: "Studied", cat: "class", cls: "mk-tag--da-hoc" },
    pcth: { vi: "PCTH", en: "PCTH", cat: "class", cls: "mk-tag--pcth" },
    nguyen_lieu_chuoi: { vi: "Nguyên liệu chuỗi", en: "Chain materials", cat: "class", cls: "mk-tag--nguyen-lieu-chuoi" },
    van_hanh: { vi: "Vận hành", en: "Operations", cat: "class", cls: "mk-tag--van-hanh" },
    mkt: { vi: "Marketing", en: "Marketing", cat: "class", cls: "mk-tag--mkt" },
    lop_khac: { vi: "Lớp khác", en: "Other class", cat: "class", cls: "mk-tag--lop-khac" },
    tiem_nang: { vi: "Tiềm năng", en: "Potential", cat: "material", cls: "mk-tag--tiem-nang" },
    mua_lan_dau: { vi: "Mua lần đầu", en: "First purchase", cat: "material", cls: "mk-tag--mua-lan-dau" },
    mua_lai: { vi: "Mua lại", en: "Repeat purchase", cat: "material", cls: "mk-tag--mua-lai" },
    mua_on_dinh: { vi: "Mua ổn định", en: "Stable purchase", cat: "material", cls: "mk-tag--mua-on-dinh" },
    dang_cham_soc: { vi: "Đang chăm sóc", en: "In care", cat: "material", cls: "mk-tag--dang-cham-soc" },
    dang_tu_van: { vi: "Đang tư vấn", en: "Consulting", cat: "material", cls: "mk-tag--dang-tu-van" },
    kh_can_nhac: { vi: "KH Cân Nhắc", en: "Considering", cat: "material", cls: "mk-tag--kh-can-nhac" },
    khong_mua: { vi: "Không mua", en: "Not buying", cat: "material", cls: "mk-tag--khong-mua" },
    ngung_mua: { vi: "Ngưng mua", en: "Stopped buying", cat: "material", cls: "mk-tag--ngung-mua" },
    nhuong_quyen: { vi: "Nhượng quyền", en: "Franchise", cat: "franchise", cls: "mk-tag--nhuong-quyen" },
    da_ky_quy: { vi: "Đã Ký Quỹ", en: "Deposited", cat: "franchise", cls: "mk-tag--da-ky-quy" },
    xac_nhan_tham_gia: { vi: "Xác nhận tham gia", en: "Confirmed", cat: "confirm", cls: "mk-tag--xac-nhan" },
    khong_xac_nhan_tham_gia: { vi: "Không tham gia", en: "Not participating", cat: "confirm", cls: "mk-tag--khong-xac-nhan" },
    vang: { vi: "Vàng", en: "Gold", cat: "tier", cls: "mk-tag--vang" },
    bac: { vi: "Bạc", en: "Silver", cat: "tier", cls: "mk-tag--bac" },
    dong: { vi: "Đồng", en: "Bronze", cat: "tier", cls: "mk-tag--dong" },
  };

  var AREA_TAGS = ["kv1", "kv2", "kv3"];
  var SOURCE_TAGS = [
    "facebook", "tiktok", "ladipage_fb", "website", "zalo", "hotline",
    "nguyen_khoa_fnb", "nguyen_lieu_gia_si", "khach_tu_tim_toi", "khach_di_chung",
    "other", "other_source",
  ];
  var CUSTOMER_TAGS = ["co_quan", "chuan_bi_mo", "gia_dinh"];
  var CLASS_TAGS = [
    "da_tg_free", "da_tg_fb1", "thu_3", "mien_phi_online", "mien_phi_offline",
    "chua_hoc", "da_hoc", "pcth", "van_hanh", "mkt", "lop_khac", "nguyen_lieu_chuoi",
  ];
  var MATERIAL_TAGS = [
    "dang_tu_van", "mua_lan_dau", "dung_cham_soc", "kh_can_nhac",
    "mua_lai", "mua_it_lai", "ngung_mua", "tiem_nang", "mua_on_dinh",
    "dang_cham_soc", "khong_mua",
  ];
  var FRANCHISE_TAGS = [
    "dang_tu_van", "khong_nghe_may", "thue_bao", "tiem_nang", "tham_khao",
    "dung_cham_soc", "khong_du_tai_chinh", "da_ky_quy", "mien_bac", "nhuong_quyen",
  ];
  var CONFIRM_TAGS = ["xac_nhan_tham_gia", "khong_xac_nhan_tham_gia"];
  var TIER_TAGS = ["vang", "bac", "dong"];
  var ALL_KNOWN_TAGS = []
    .concat(AREA_TAGS, SOURCE_TAGS, CUSTOMER_TAGS, CLASS_TAGS, MATERIAL_TAGS, FRANCHISE_TAGS, CONFIRM_TAGS, TIER_TAGS);

  function catalogLabel(tag) {
    var labels = root.MK_OPP_TAG_LABELS || {};
    var key = normalizeTag(tag);
    if (key && labels[key]) return labels[key];
    if (tag && labels[tag]) return labels[tag];
    return null;
  }

  function isCatalogScopedTag(tag) {
    return !!catalogLabel(tag);
  }

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
    var custom = [];
    (tags || []).forEach(function (t) {
      // Tag tự tạo / catalog scope_opp — không thuộc cột BA cố định
      if (!findTagInPool([t], ALL_KNOWN_TAGS)) {
        custom.push(t);
      }
    });
    return {
      area: findTagInPool(tags, AREA_TAGS),
      source: findTagInPool(tags, SOURCE_TAGS),
      customer: findTagInPool(tags, CUSTOMER_TAGS),
      classTag: findTagInPool(tags, CLASS_TAGS),
      material: findTagInPool(tags, MATERIAL_TAGS),
      franchise: findTagInPool(tags, FRANCHISE_TAGS),
      confirm: findTagInPool(tags, CONFIRM_TAGS),
      tier: findTagInPool(tags, TIER_TAGS),
      custom: custom,
      customTag: custom.length ? custom[0] : null,
    };
  }

  function tagMeta(tag) {
    var key = normalizeTag(tag);
    var raw = TAG_META_RAW[key];
    if (raw) {
      return {
        label: pickLabel(raw.vi, raw.en),
        cat: raw.cat,
        cls: raw.cls,
        key: key,
      };
    }
    var catalogName = catalogLabel(tag);
    if (catalogName) {
      return { label: catalogName, cat: "custom", cls: "mk-tag--custom", key: key };
    }
    return { label: String(tag || ""), cat: "other", cls: "mk-tag--other", key: key };
  }

  function labelForTag(key, fallback) {
    var meta = tagMeta(key);
    if (meta && meta.label) return meta.label;
    return fallback || key;
  }

  /** Groups for list / inline tag editor (BA columns + confirm + tier). */
  var CREATE_TAG_GROUPS = [
    { id: "area", labelVi: "Khu vực", labelEn: "Region", tags: AREA_TAGS },
    { id: "source", labelVi: "Nguồn data", labelEn: "Source", tags: SOURCE_TAGS },
    { id: "customer", labelVi: "Dạng khách hàng", labelEn: "Customer type", tags: CUSTOMER_TAGS },
    { id: "class", labelVi: "Tag lớp học", labelEn: "Class", tags: CLASS_TAGS },
    { id: "material", labelVi: "Tag nguyên liệu", labelEn: "Material", tags: MATERIAL_TAGS },
    { id: "franchise", labelVi: "Tag nhượng quyền", labelEn: "Franchise", tags: FRANCHISE_TAGS },
    { id: "confirm", labelVi: "Xác nhận tham gia", labelEn: "Confirm", tags: CONFIRM_TAGS },
    { id: "tier", labelVi: "Hạng khách", labelEn: "Tier", tags: TIER_TAGS },
  ];

  function getCreateTagCatalog() {
    return CREATE_TAG_GROUPS.map(function (g) {
      return {
        id: g.id,
        label: pickLabel(g.labelVi, g.labelEn),
        tags: (g.tags || []).map(function (k) {
          var meta = tagMeta(k);
          return { key: meta.key || normalizeTag(k) || k, label: meta.label || k };
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

  root.PotentialsLovableRef = {
    TAG_META_RAW: TAG_META_RAW,
    AREA_TAGS: AREA_TAGS,
    SOURCE_TAGS: SOURCE_TAGS,
    CUSTOMER_TAGS: CUSTOMER_TAGS,
    CLASS_TAGS: CLASS_TAGS,
    MATERIAL_TAGS: MATERIAL_TAGS,
    FRANCHISE_TAGS: FRANCHISE_TAGS,
    CONFIRM_TAGS: CONFIRM_TAGS,
    TIER_TAGS: TIER_TAGS,
    ALL_KNOWN_TAGS: ALL_KNOWN_TAGS,
    CREATE_TAG_GROUPS: CREATE_TAG_GROUPS,
    catalogLabel: catalogLabel,
    isCatalogScopedTag: isCatalogScopedTag,
    isVi: isVi,
    pickLabel: pickLabel,
    normalizeTag: normalizeTag,
    findTagInPool: findTagInPool,
    categorizeTags: categorizeTags,
    tagMeta: tagMeta,
    labelForTag: labelForTag,
    getCreateTagCatalog: getCreateTagCatalog,
    getCreateTagKeys: getCreateTagKeys,
  };
})(typeof window !== "undefined" ? window : this);
