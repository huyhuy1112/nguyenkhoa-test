/* Contacts list — Lovable UI (same shell as Leads / Potentials) */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var ref = window.ContactsLovableRef;
  var store = window.ContactsLocalStore;
  var icons = window.LeadsMkIcons;
  var COL_COUNT = 19;

  function t(key, fallback) {
    if (typeof app !== "undefined" && app.vtranslate) {
      var translated = app.vtranslate(key);
      if (translated && translated !== key) return translated;
    }
    return fallback || key;
  }

  function notifyUser(type, message) {
    var helper =
      window.app && window.app.helper
        ? window.app.helper
        : typeof app !== "undefined" && app.helper
          ? app.helper
          : null;
    var method =
      type === "success"
        ? "showSuccessNotification"
        : type === "warning"
          ? "showAlertNotification"
          : "showErrorNotification";
    if (helper && typeof helper[method] === "function") {
      helper[method]({ message: String(message || "") });
      return;
    }
    window.alert(String(message || ""));
  }

  function confirmAction(options) {
    options = options || {};
    var title = options.title || "Xác nhận thao tác";
    var question = options.question || "Bạn có chắc chắn muốn tiếp tục?";
    var hint = options.hint || "";
    var tone = options.tone === "danger" ? "danger" : "primary";
    var icon = tone === "danger" ? "fa-trash-o" : options.icon || "fa-question-circle";
    var html =
      '<div class="mk-ui-confirm">' +
      '<span class="mk-ui-confirm__icon mk-ui-confirm__icon--' +
      tone +
      '" aria-hidden="true"><i class="fa ' +
      icon +
      '"></i></span>' +
      '<div class="mk-ui-confirm__copy">' +
      '<div class="mk-ui-confirm__question">' +
      esc(question) +
      "</div>" +
      (hint ? '<div class="mk-ui-confirm__hint">' + esc(hint) + "</div>" : "") +
      "</div></div>";
    var helper =
      window.app && window.app.helper
        ? window.app.helper
        : typeof app !== "undefined" && app.helper
          ? app.helper
          : null;

    if (helper && typeof helper.showConfirmationBox === "function") {
      return new Promise(function (resolve) {
        helper
          .showConfirmationBox({
            title: title,
            message: html,
            htmlSupportEnable: true,
            buttons: {
              cancel: {
                label: "Hủy",
                className: "btn mk-ui-confirm__btn mk-ui-confirm__btn--cancel",
              },
              confirm: {
                label: options.confirmLabel || "Xác nhận",
                className:
                  "btn mk-ui-confirm__btn mk-ui-confirm__btn--" +
                  (tone === "danger" ? "danger" : "primary"),
              },
            },
          })
          .then(
            function () {
              resolve(true);
            },
            function () {
              resolve(false);
            }
          );
      });
    }

    return Promise.resolve(window.confirm(question + (hint ? "\n" + hint : "")));
  }

  function pick(vi, en) {
    return ref && ref.pickLabel ? ref.pickLabel(vi, en) : vi;
  }

  var BUSINESS_MODELS = [
    "TS Topping",
    "Xe đẩy",
    "Cà phê máy lạnh",
    "Cà phê sân vườn",
    "TS Pha máy",
    "Cà phê không gian mở",
  ];

  function businessModelSelectHtml(recordId, value) {
    var current = String(value || "").trim();
    var opts = [['', "—"]].concat(
      BUSINESS_MODELS.map(function (label) {
        return [label, label];
      })
    );
    return (
      '<select class="mk-leads-region-select mk-leads-biz-select" data-field="business_model" data-contact-id="' +
      esc(recordId) +
      '" title="Mô hình kinh doanh">' +
      opts
        .map(function (o) {
          return (
            '<option value="' +
            esc(o[0]) +
            '"' +
            (current === o[0] ? " selected" : "") +
            ">" +
            esc(o[1]) +
            "</option>"
          );
        })
        .join("") +
      "</select>"
    );
  }

  function commitBusinessModelChange(select) {
    if (!select || !store || !store.saveInlineFields) return;
    var recordId = select.getAttribute("data-contact-id");
    if (!recordId) return;
    select.disabled = true;
    store
      .saveInlineFields(recordId, { business_model: select.value || "" })
      .then(function () {
        renderTable();
      })
      .catch(function () {
        notifyUser("error", "Không lưu được mô hình kinh doanh.");
        renderTable();
      });
  }

  /** Tag lớp học — lọc theo tag (tag con NVL nằm ở NVL_SUB_FILTERS; NVL/Nhượng quyền dùng product tabs) */
  function getPresetSegments() {
    return [
      { id: "lane_courses", name: pick("Khóa học", "Courses"), filters: { lane: "courses" } },
      { id: "da_mqbb", name: "Đã MQBB", filters: { classTag: "da_mqbb" } },
      { id: "da_990k", name: "Đã 990k", filters: { classTag: "da_990k" } },
      { id: "da_pcth", name: "Đã PCTH", filters: { classTag: "da_pcth" } },
      { id: "mien_phi_offline", name: "Miễn phí Offline", filters: { classTag: "mien_phi_offline" } },
      { id: "mien_phi_online", name: "Miễn phí Online", filters: { classTag: "mien_phi_online" } },
      { id: "da_pcthcb", name: "Đã PCTHCB", filters: { classTag: "da_pcthcb" } },
    ];
  }

  /** Tag con của NVL — chỉ hiện khi chọn tab NVL */
  var NVL_SUB_FILTERS = [
    { id: "miutea", label: "Miutea", filters: { nvlSegment: "miutea" } },
    { id: "khach_le", label: pick("Khách lẻ", "Retail"), filters: { nvlSegment: "khach_le" } },
    { id: "has_store", label: pick("Đã có quán", "Has store"), filters: { customerStatus: "co_quan" } },
    { id: "no_store", label: pick("Chưa có quán", "No store yet"), filters: { customerStatus: "chuan_bi_mo" } },
    { id: "deposit", label: pick("Đã ký quỹ", "Deposited"), filters: { franchise: "da_ky_quy" } },
    { id: "gold", label: pick("Hạng Vàng", "Gold tier"), filters: { tier: "vang" } },
    { id: "silver", label: pick("Hạng Bạc", "Silver tier"), filters: { tier: "bac" } },
    { id: "bronze", label: pick("Hạng Đồng", "Bronze tier"), filters: { tier: "dong" } },
  ];

  /** Tag lớp tính là 1 "khóa" khi đếm 1/2/3/4 khóa dưới filter Khóa học */
  var COURSE_COUNT_TAGS = [
    "da_mqbb",
    "da_990k",
    "da_pcth",
    "da_pcthcb",
    "mien_phi_offline",
    "mien_phi_online",
  ];

  var COURSE_COUNT_CHIPS = [
    { n: 4, label: "Học 4 khóa" },
    { n: 3, label: "Học 3 khóa" },
    { n: 2, label: "Học 2 khóa" },
    { n: 1, label: "Học 1 khóa" },
  ];

  /** Tên ngắn khóa Edubit (đồng bộ ProductCatalog) */
  var EDUBIT_COURSE_LABELS = {
    "29403": "MQBB 990k",
    "29218": "PCTH",
    "28108": "PCTH Cơ bản",
    "27312": "Miễn phí Opp",
  };

  var EMPTY = {
    search: "",
    lane: ANY,
    customerType: ANY,
    nvlSegment: ANY,
    customerStatus: ANY,
    /** @deprecated alias of customerStatus */
    customerRank: ANY,
    classTag: ANY,
    material: ANY,
    franchise: ANY,
    tier: ANY,
    anyTag: ANY,
    owner: ANY,
    offlineStatus: ANY,
    onlineStatus: ANY,
    progress: ANY,
    gd14Tag: ANY,
    courseCount: ANY,
    hasTag: false,
    hasAccount: false,
    hasNextAction: false,
    staleOnly: false,
  };

  var OFFLINE_STATUS_FILTERS = [
    { key: "offline_hen_goi_lai", label: "Hẹn gọi lại" },
    { key: "offline_khong_nghe_may", label: "Không nghe máy" },
    { key: "offline_sai_thong_tin", label: "Sai thông tin" },
    { key: "offline_hen_lich_lai", label: "Hẹn lịch lại" },
    { key: "offline_chuyen_chuong_trinh", label: "Chuyển CT" },
    { key: "offline_ngung_cskh", label: "Ngưng CSKH" },
  ];

  var ONLINE_STATUS_FILTERS = [
    { key: "online_chua_dien_form", label: "Chưa điền form" },
    { key: "online_chua_dk_tk", label: "Chưa ĐK tài khoản" },
    { key: "online_khong_du_dk", label: "Không đủ ĐK" },
    { key: "offline_hen_goi_lai", label: "Hẹn gọi lại" },
    { key: "offline_khong_nghe_may", label: "Không nghe máy" },
    { key: "offline_sai_thong_tin", label: "Sai thông tin" },
    { key: "online_ngung_cskh", label: "Ngưng CSKH" },
  ];

  var PRODUCT_TABS = [
    { id: "online", label: "Online" },
    { id: "offline", label: "Offline" },
    { id: "pcth", label: "PCTH" },
    { id: "mqbb", label: "MQBB" },
    { id: "nvl", label: "NVL" },
  ];

  var state = {
    filters: Object.assign({ classTags: [] }, EMPTY),
    sortKey: "last_touch",
    sortDir: "desc",
    page: 1,
    filtersOpen: false,
    activeSegment: null,
    selected: {},
    productTab: "all",
    nvlSubFilter: ANY,
  };

  function clearNvlSubFilters() {
    state.nvlSubFilter = ANY;
    state.filters.customerStatus = ANY;
    state.filters.customerRank = ANY;
    state.filters.nvlSegment = ANY;
    state.filters.franchise = ANY;
    state.filters.tier = ANY;
  }

  function clearCourseCountFilter() {
    state.filters.courseCount = ANY;
  }

  function applyNvlSubFilter(subId) {
    state.productTab = "nvl";
    state.activeSegment = null;
    clearCourseCountFilter();
    state.filters.customerStatus = ANY;
    state.filters.customerRank = ANY;
    state.filters.nvlSegment = ANY;
    state.filters.franchise = ANY;
    state.filters.tier = ANY;
    state.nvlSubFilter = subId || ANY;
    if (subId && subId !== ANY) {
      var item = null;
      for (var i = 0; i < NVL_SUB_FILTERS.length; i++) {
        if (NVL_SUB_FILTERS[i].id === subId) {
          item = NVL_SUB_FILTERS[i];
          break;
        }
      }
      if (item && item.filters) {
        Object.keys(item.filters).forEach(function (k) {
          state.filters[k] = item.filters[k];
        });
      }
    }
    state.page = 1;
    renderAll();
  }

  function applyCourseCountFilter(n) {
    state.activeSegment = "lane_courses";
    state.productTab = "all";
    clearNvlSubFilters();
    state.filters.lane = "courses";
    state.filters.offlineStatus = ANY;
    state.filters.courseCount = n && n !== ANY ? String(n) : ANY;
    state.page = 1;
    renderAll();
  }

  function $(id) {
    return document.getElementById(id);
  }

  function ic(name) {
    return icons && icons.get ? icons.get(name) : "";
  }

  function tagMeta(tg) {
    return ref && ref.tagMeta ? ref.tagMeta(tg) : { label: tg, cls: "mk-tag" };
  }

  function categorize(tags) {
    return ref && ref.categorizeTags ? ref.categorizeTags(tags || []) : {};
  }

  function decodeHtml(s) {
    if (window.mkDecodeHtml) return window.mkDecodeHtml(s);
    var str = String(s == null ? "" : s);
    if (!str || str.indexOf("&") < 0) return str;
    var el = document.createElement("textarea");
    var i;
    for (i = 0; i < 5; i++) {
      el.innerHTML = str;
      var next = el.value;
      if (next === str) break;
      str = next;
    }
    return str;
  }

  function esc(s) {
    return decodeHtml(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function getContacts() {
    return store ? store.getContacts() : [];
  }

  function detailUrl(id) {
    return "index.php?module=Contacts&view=Detail&record=" + encodeURIComponent(id) + "&app=SALES";
  }

  function ownerInitials(name) {
    var parts = String(name || "").trim().split(/\s+/);
    if (!parts[0]) return "?";
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
  }

  function ownerColor(name) {
    var h = 0;
    var s = String(name || "");
    for (var i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) % 360;
    return "hsl(" + h + ", 52%, 42%)";
  }

  function isStale(row) {
    var iso = row.last_touch || row.modifiedtime;
    if (!iso) return false;
    var tms = new Date(iso).getTime();
    if (isNaN(tms)) return false;
    return Math.floor((Date.now() - tms) / 86400000) >= 7;
  }

  function hasNormalizedTag(tags, key) {
    if (!tags || !tags.length || !key || !ref) return false;
    for (var i = 0; i < tags.length; i++) {
      if (ref.normalizeTag(tags[i]) === key) return true;
    }
    return false;
  }

  /** Số tag lớp/khóa distinct trên Contact (dùng đếm 1/2/3/4 khóa). */
  function courseTagCount(contact) {
    var seen = {};
    var n = 0;
    (contact && contact.tags ? contact.tags : []).forEach(function (tg) {
      var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "").toLowerCase();
      if (COURSE_COUNT_TAGS.indexOf(k) < 0 || seen[k]) return;
      seen[k] = 1;
      n++;
    });
    return n;
  }

  function contactMatchesCourseLane(c) {
    var cats = categorize(c.tags);
    var hasFranchise = !!cats.franchise;
    var hasMaterial = !!cats.material;
    var hasCourse =
      !!cats.classTag ||
      !!(c.edubit_user_id || c.edubit_course_id) ||
      (Array.isArray(c.edubit_courses) && c.edubit_courses.length > 0) ||
      !!(c.thoigian_dangky || c.thoigian_pcth || c.thoigian_mqbb || c.thoigian_pcthcb);
    if (!hasCourse && !hasMaterial && hasFranchise) return false;
    if (!hasCourse && hasFranchise && !hasMaterial) return false;
    return !!hasCourse;
  }

  function countCourseTagBuckets(rows) {
    var buckets = { 1: 0, 2: 0, 3: 0, 4: 0 };
    (rows || []).forEach(function (c) {
      if (!contactMatchesCourseLane(c)) return;
      var n = courseTagCount(c);
      if (n <= 0) return;
      if (n >= 4) buckets[4]++;
      else buckets[n]++;
    });
    return buckets;
  }

  function edubitCourseLabel(courseId, fallback) {
    var cid = String(courseId || "").replace(/\D+/g, "");
    if (cid && EDUBIT_COURSE_LABELS[cid]) return EDUBIT_COURSE_LABELS[cid];
    var fb = String(fallback || "").trim();
    if (fb) return fb;
    return cid ? "Khóa " + cid : "Khóa học";
  }

  function resolveEdubitCourses(contact) {
    var courses = Array.isArray(contact && contact.edubit_courses) ? contact.edubit_courses.slice() : [];
    if (courses.length) return courses;
    if (contact && (contact.edubit_course_id || contact.edubit_user_id || contact.edubit_progress_pct != null)) {
      return [
        {
          course_id: contact.edubit_course_id || "",
          label: "",
          progress_pct:
            contact.edubit_progress_pct != null && contact.edubit_progress_pct !== ""
              ? Number(contact.edubit_progress_pct)
              : 0,
        },
      ];
    }
    return [];
  }

  function productGroupsFromTags(tags) {
    var set = {};
    (tags || []).forEach(function (tg) {
      var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "").toLowerCase();
      if (
        k === "mien_phi_online" ||
        k === "da_990k" ||
        k.indexOf("online_") === 0
      ) {
        set.online = 1;
      } else if (
        k === "mien_phi_offline" ||
        k === "da_tg_free" ||
        k === "da_tg_fb1" ||
        k.indexOf("offline_") === 0
      ) {
        set.offline = 1;
      }
      if (/pcth/i.test(k)) set.pcth = 1;
      if (/mqbb/i.test(k) || k === "combo_mo_quan") set.mqbb = 1;
      if (k === "nhuong_quyen" || k === "da_ky_quy" || k === "dang_tu_van") {
        set.franchise = 1;
      } else if (
        k === "mua_lan_dau" ||
        k === "mua_lai" ||
        k === "mua_on_dinh" ||
        k === "tiem_nang" ||
        k === "dang_cham_soc"
      ) {
        set.nvl = 1;
      }
    });
    return Object.keys(set);
  }

  function hasProductGroup(row, group) {
    var g = productGroupsFromTags(row && row.tags);
    if (group === "unclassified") return g.length === 0;
    return g.indexOf(group) >= 0;
  }

  function countProductTab(rows, tabId) {
    var n = 0;
    (rows || []).forEach(function (c) {
      if (hasProductGroup(c, tabId)) n++;
    });
    return n;
  }

  function productTabItemsHtml(rows) {
    return PRODUCT_TABS.map(function (it) {
      var n = countProductTab(rows, it.id);
      return (
        '<button type="button" class="mk-leads-segment-btn mk-leads-ptab' +
        (state.productTab === it.id ? " is-active" : "") +
        '" data-product-tab="' +
        esc(it.id) +
        '">' +
        esc(it.label) +
        ' <span class="mk-leads-ptab__n">' +
        n +
        "</span></button>"
      );
    }).join("");
  }

  function filterContacts(rows) {
    var f = state.filters;
    var q = (f.search || "").toLowerCase().trim();
    return rows.filter(function (c) {
      var cats = categorize(c.tags);
      if (state.productTab && state.productTab !== "all") {
        if (!hasProductGroup(c, state.productTab)) return false;
      }
      if (f.offlineStatus && f.offlineStatus !== ANY) {
        if (!hasNormalizedTag(c.tags, f.offlineStatus)) return false;
      }
      if (f.onlineStatus && f.onlineStatus !== ANY) {
        if (!hasNormalizedTag(c.tags, f.onlineStatus)) return false;
      }
      if (f.progress && f.progress !== ANY) {
        var pct =
          c.edubit_progress_pct != null && c.edubit_progress_pct !== ""
            ? Number(c.edubit_progress_pct)
            : null;
        if (Array.isArray(c.edubit_courses) && c.edubit_courses.length && (pct === null || isNaN(pct))) {
          pct = Number(c.edubit_courses[0].progress_pct);
          if (isNaN(pct)) pct = null;
        }
        if (f.progress === "none" && pct != null) return false;
        if (f.progress === "lt50" && !(pct != null && pct < 50)) return false;
        if (f.progress === "50_79" && !(pct != null && pct >= 50 && pct < 80)) return false;
        if (f.progress === "80_99" && !(pct != null && pct >= 80 && pct < 100)) return false;
        if (f.progress === "done" && !(pct != null && pct >= 100)) return false;
      }
      if (f.courseCount && f.courseCount !== ANY) {
        var wantN = parseInt(f.courseCount, 10);
        var haveN = courseTagCount(c);
        if (wantN >= 4) {
          if (haveN < 4) return false;
        } else if (haveN !== wantN) {
          return false;
        }
      }
      if (q) {
        var hay = [c.name, c.title, c.account, c.address, c.email, c.phone, c.owner, (c.tags || []).join(" ")]
          .join(" ")
          .toLowerCase();
        if (hay.indexOf(q) < 0) return false;
      }
      if (f.hasTag && !(c.tags || []).length) return false;
      if (f.hasAccount && !c.account) return false;
      if (f.hasNextAction && !deriveNextAction(c)) return false;
      if (f.customerType !== ANY && (!cats.customerType || ref.normalizeTag(cats.customerType) !== f.customerType)) return false;
      if (f.nvlSegment !== ANY && (!cats.nvlSegment || ref.normalizeTag(cats.nvlSegment) !== f.nvlSegment)) return false;
      var statusWant = f.customerStatus !== ANY ? f.customerStatus : f.customerRank;
      if (statusWant !== ANY && (!cats.customerStatus || ref.normalizeTag(cats.customerStatus) !== statusWant)) return false;
      var needClass = [];
      if (f.classTag !== ANY && f.classTag) {
        needClass.push(f.classTag);
      }
      if (Array.isArray(f.classTags)) {
        f.classTags.forEach(function (tg) {
          if (tg && needClass.indexOf(tg) < 0) needClass.push(tg);
        });
      }
      for (var ci = 0; ci < needClass.length; ci++) {
        if (!hasNormalizedTag(c.tags, needClass[ci])) return false;
      }
      if (f.material !== ANY && (!cats.material || ref.normalizeTag(cats.material) !== f.material)) return false;
      if (f.franchise !== ANY && (!cats.franchise || ref.normalizeTag(cats.franchise) !== f.franchise)) return false;
      if (f.tier !== ANY && (!cats.tier || ref.normalizeTag(cats.tier) !== f.tier)) return false;
      if (f.gd14Tag && f.gd14Tag !== ANY && !hasNormalizedTag(c.tags, f.gd14Tag)) return false;
      if (f.anyTag !== ANY && !hasNormalizedTag(c.tags, f.anyTag)) return false;
      if (f.staleOnly && !isStale(c)) return false;
      if (f.owner !== ANY && c.owner !== f.owner) return false;
      if (f.lane !== ANY) {
        var hasFranchise = !!cats.franchise;
        var hasMaterial = !!cats.material;
        var courseClass = false;
        (c.tags || []).forEach(function (tg) {
          var key = ref.normalizeTag(tg);
          if (!key || key.indexOf("gd14_") === 0) return;
          if (COURSE_COUNT_TAGS.indexOf(key) >= 0) courseClass = true;
        });
        var hasCourse =
          courseClass ||
          !!(c.edubit_user_id || c.edubit_course_id) ||
          (Array.isArray(c.edubit_courses) && c.edubit_courses.length > 0) ||
          !!(c.thoigian_dangky || c.thoigian_pcth || c.thoigian_mqbb || c.thoigian_pcthcb);
        if (f.lane === "franchise") {
          if (!hasFranchise) return false;
        } else if (f.lane === "materials") {
          // NL: có tag nguyên liệu, ưu tiên không bắt buộc loại trừ NQ nếu cùng lúc có cả hai
          if (!hasMaterial) return false;
        } else if (f.lane === "courses") {
          if (!hasCourse && !hasMaterial && hasFranchise) return false;
          if (!hasCourse && hasFranchise && !hasMaterial) return false;
          if (!hasCourse) return false;
        }
      }
      return true;
    });
  }

  function sortContacts(rows) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var av = a[key];
      var bv = b[key];
      if (key === "thoigian_dangky" || key === "thoigian_pcth" || key === "thoigian_mqbb" || key === "thoigian_pcthcb" || key === "converted_at") {
        av = av ? new Date(av).getTime() : 0;
        bv = bv ? new Date(bv).getTime() : 0;
        if (av < bv) return -1 * dir;
        if (av > bv) return 1 * dir;
        return 0;
      }
      av = String(av || "").toLowerCase();
      bv = String(bv || "").toLowerCase();
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  function formatDateTimeLabel(raw) {
    if (!raw) return "";
    var d = new Date(raw);
    if (isNaN(d.getTime())) return String(raw);
    var mm = String(d.getMonth() + 1).padStart
      ? String(d.getMonth() + 1).padStart(2, "0")
      : ("0" + (d.getMonth() + 1)).slice(-2);
    var dd = String(d.getDate()).padStart
      ? String(d.getDate()).padStart(2, "0")
      : ("0" + d.getDate()).slice(-2);
    var yyyy = d.getFullYear();
    var h = d.getHours();
    var m = d.getMinutes();
    var ampm = h >= 12 ? "PM" : "AM";
    var h12 = h % 12;
    if (h12 === 0) h12 = 12;
    var mmins = String(m).padStart ? String(m).padStart(2, "0") : ("0" + m).slice(-2);
    // DD-MM-YYYY (ngày → tháng), khớp yêu cầu list Contacts
    return dd + "-" + mm + "-" + yyyy + " " + h12 + ":" + mmins + " " + ampm;
  }

  function dateCell(raw) {
    var label = formatDateTimeLabel(raw);
    return label
      ? '<span class="mk-leads-date">' + esc(label) + "</span>"
      : '<span class="mk-leads-muted">—</span>';
  }

  function computeKpis(rows) {
    var withTags = rows.filter(function (c) { return (c.tags || []).length > 0; }).length;
    var withPhone = rows.filter(function (c) { return !!c.phone; }).length;
    var withCapTk = rows.filter(function (c) {
      return isCredentialIssued(c.da_cap_tai_khoan, "tk");
    }).length;
    var withCapBang = rows.filter(function (c) {
      return isCredentialIssued(c.da_cap_bang, "bang");
    }).length;
    var gold = rows.filter(function (c) {
      var tg = categorize(c.tags).tier;
      return tg && ref.normalizeTag(tg) === "vang";
    }).length;
    return [
      { key: "total", label: t("JS_MK_KPI_TOTAL_CONTACT", "Tổng khách hàng"), value: rows.length, icon: "users", tone: "blue" },
      { key: "tagged", label: t("JS_MK_KPI_TAGGED", "Có tag"), value: withTags, icon: "crown", tone: "violet" },
      { key: "cap_tk", label: t("JS_MK_KPI_CAP_TK", "Đã cấp tài khoản"), value: withCapTk, icon: "check", tone: "emerald" },
      { key: "phone", label: t("JS_MK_KPI_PHONE", "Có SĐT"), value: withPhone, icon: "clock", tone: "cyan" },
      { key: "cap_bang", label: t("JS_MK_KPI_CAP_BANG", "Cấp bằng"), value: withCapBang, icon: "repeat", tone: "amber" },
      { key: "gold", label: t("JS_MK_KPI_GOLD", "Hạng Vàng"), value: gold, icon: "crown", tone: "rose" },
    ];
  }

  function renderKpi(rows) {
    var host = $("mk-contacts-kpi");
    if (!host) return;
    host.innerHTML = computeKpis(rows)
      .map(function (k) {
        return (
          '<article class="mk-leads-kpi-card mk-contacts-kpi-card" data-kpi="' + esc(k.key) + '">' +
          '<div class="mk-leads-kpi-card__top">' +
          '<span class="mk-leads-kpi-card__label">' +
          '<span class="mk-leads-kpi-ic-wrap mk-leads-kpi-ic--' + esc(k.tone) + '">' +
          ic(k.icon) +
          "</span>" +
          esc(k.label) +
          "</span></div>" +
          '<div class="mk-leads-kpi-card__value">' + esc(String(k.value)) + "</div>" +
          "</article>"
        );
      })
      .join("");
  }

  function renderSegments() {
    var host = $("mk-contacts-segments");
    if (!host) return;
    var rows = getContacts();
    var allOn = !state.activeSegment && state.productTab === "all" ? " is-active" : "";

    var courseHtml =
      '<button type="button" class="mk-leads-segment-btn' +
      allOn +
      '" data-seg="__all__">' +
      esc(t("JS_MK_FILTER_ALL", "Tất cả")) +
      "</button>";
    courseHtml += getPresetSegments()
      .map(function (seg) {
        var active =
          state.activeSegment === seg.id ||
          (seg.filters &&
            seg.filters.classTag &&
            Array.isArray(state.filters.classTags) &&
            state.filters.classTags.indexOf(seg.filters.classTag) >= 0)
            ? " is-active"
            : "";
        return (
          '<button type="button" class="mk-leads-segment-btn' +
          active +
          '" data-seg="' +
          esc(seg.id) +
          '">' +
          esc(seg.name) +
          "</button>"
        );
      })
      .join("");
    if (state.activeSegment === "lane_courses" || state.filters.lane === "courses") {
      courseHtml += '<span class="mk-leads-offline-filters" role="group" aria-label="Số khóa học theo tag">';
      var fcc = (state.filters && state.filters.courseCount) || ANY;
      var buckets = countCourseTagBuckets(rows);
      courseHtml +=
        '<button type="button" class="mk-leads-offline-filter' +
        (fcc === ANY ? " is-active" : "") +
        '" data-course-count="' +
        ANY +
        '">Tất cả khóa</button>';
      COURSE_COUNT_CHIPS.forEach(function (it) {
        var n = buckets[it.n] || 0;
        courseHtml +=
          '<button type="button" class="mk-leads-offline-filter' +
          (String(fcc) === String(it.n) ? " is-active" : "") +
          '" data-course-count="' +
          esc(String(it.n)) +
          '">' +
          esc(it.label) +
          ' <span class="mk-leads-ptab__n">(' +
          n +
          ")</span></button>";
      });
      courseHtml += "</span>";
    }

    var productHtml = productTabItemsHtml(rows);
    if (state.productTab === "offline") {
      productHtml += '<span class="mk-leads-offline-filters" role="group" aria-label="Lọc trạng thái Offline">';
      var fos = (state.filters && state.filters.offlineStatus) || ANY;
      productHtml +=
        '<button type="button" class="mk-leads-offline-filter' +
        (fos === ANY ? " is-active" : "") +
        '" data-offline-status="' +
        ANY +
        '">Tất cả Offline</button>';
      OFFLINE_STATUS_FILTERS.forEach(function (it) {
        productHtml +=
          '<button type="button" class="mk-leads-offline-filter' +
          (fos === it.key ? " is-active" : "") +
          '" data-offline-status="' +
          esc(it.key) +
          '">' +
          esc(it.label) +
          "</button>";
      });
      productHtml += "</span>";
    }
    if (state.productTab === "online") {
      productHtml +=
        '<span class="mk-leads-offline-filters mk-leads-offline-filters--online" role="group" aria-label="Lọc trạng thái Online">';
      var fosOn = (state.filters && state.filters.onlineStatus) || ANY;
      productHtml +=
        '<button type="button" class="mk-leads-offline-filter' +
        (fosOn === ANY ? " is-active" : "") +
        '" data-online-status="' +
        ANY +
        '">Tất cả Online</button>';
      ONLINE_STATUS_FILTERS.forEach(function (it) {
        productHtml +=
          '<button type="button" class="mk-leads-offline-filter' +
          (fosOn === it.key ? " is-active" : "") +
          '" data-online-status="' +
          esc(it.key) +
          '">' +
          esc(it.label) +
          "</button>";
      });
      productHtml += "</span>";
    }
    if (state.productTab === "pcth" || state.productTab === "mqbb") {
      var tone = state.productTab === "pcth" ? " mk-leads-offline-filters--pcth" : " mk-leads-offline-filters--mqbb";
      var allProg = state.productTab === "pcth" ? "Tất cả PCTH" : "Tất cả MQBB";
      productHtml +=
        '<span class="mk-leads-offline-filters' +
        tone +
        '" role="group" aria-label="Lọc điểm rơi ' +
        esc(state.productTab.toUpperCase()) +
        '">';
      var fosProg = (state.filters && state.filters.offlineStatus) || ANY;
      productHtml +=
        '<button type="button" class="mk-leads-offline-filter' +
        (fosProg === ANY ? " is-active" : "") +
        '" data-offline-status="' +
        ANY +
        '">' +
        esc(allProg) +
        "</button>";
      OFFLINE_STATUS_FILTERS.forEach(function (it) {
        productHtml +=
          '<button type="button" class="mk-leads-offline-filter' +
          (fosProg === it.key ? " is-active" : "") +
          '" data-offline-status="' +
          esc(it.key) +
          '">' +
          esc(it.label) +
          "</button>";
      });
      productHtml += "</span>";
    }
    if (state.productTab === "nvl") {
      productHtml += '<span class="mk-leads-offline-filters" role="group" aria-label="Lọc NVL">';
      var fnvl = state.nvlSubFilter || ANY;
      productHtml +=
        '<button type="button" class="mk-leads-offline-filter' +
        (fnvl === ANY ? " is-active" : "") +
        '" data-nvl-sub="' +
        ANY +
        '">Tất cả NVL</button>';
      NVL_SUB_FILTERS.forEach(function (it) {
        productHtml +=
          '<button type="button" class="mk-leads-offline-filter' +
          (fnvl === it.id ? " is-active" : "") +
          '" data-nvl-sub="' +
          esc(it.id) +
          '">' +
          esc(it.label) +
          "</button>";
      });
      productHtml += "</span>";
    }

    host.innerHTML =
      '<div class="mk-contacts-seg-rows">' +
      '<div class="mk-contacts-seg-row" data-seg-group="course">' +
      '<span class="mk-contacts-seg-row__label">Lớp học</span>' +
      '<div class="mk-contacts-seg-row__chips mk-leads-segments">' +
      courseHtml +
      "</div></div>" +
      '<div class="mk-contacts-seg-row" data-seg-group="product">' +
      '<span class="mk-contacts-seg-row__label">Sản phẩm</span>' +
      '<div class="mk-contacts-seg-row__chips mk-leads-segments">' +
      productHtml +
      "</div></div></div>";
  }

  function selectOptions(pairs) {
    return (
      '<option value="' + ANY + '">' + esc(t("JS_MK_FILTER_ALL", "Tất cả")) + "</option>" +
      pairs.map(function (p) {
        return '<option value="' + esc(p[0]) + '">' + esc(p[1]) + "</option>";
      }).join("")
    );
  }

  function renderFiltersPanel() {
    var host = $("mk-contacts-filters-panel");
    if (!host || !ref) return;
    var rows = getContacts();
    var owners = [];
    rows.forEach(function (c) {
      if (c.owner && owners.indexOf(c.owner) < 0) owners.push(c.owner);
    });
    owners.sort();
    host.innerHTML =
      '<div class="mk-leads-filters-grid">' +
      fieldSelect("Phân khu", "lane", [
        ["courses", "Khóa học"],
        ["materials", "Nguyên liệu"],
      ]) +
      fieldSelect(t("JS_MK_FILTER_CUSTOMER_TYPE", "Loại khách"), "customerType", (ref.CUSTOMER_TYPE_TAGS || []).map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_NVL_SEGMENT", "Nhóm NVL"), "nvlSegment", (ref.NVL_SEGMENT_TAGS || []).map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CUSTOMER_STATUS", "Tình trạng khách"), "customerStatus", (ref.CUSTOMER_STATUS_TAGS || ref.CUSTOMER_RANK_TAGS || []).map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_TIER", "Hạng khách hàng"), "tier", ref.TIER_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CLASS", "Tag lớp học"), "classTag", ref.CLASS_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_MATERIAL", "Tag nguyên liệu"), "material", ref.MATERIAL_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_PROGRESS", "Tiến trình"), "progress", [
        ["none", "Chưa có tiến độ"],
        ["lt50", "Dưới 50%"],
        ["50_79", "50–79%"],
        ["80_99", "80–99%"],
        ["done", "Hoàn thành 100%"],
      ]) +
      fieldSelect(t("JS_MK_FILTER_OWNER", "Phụ trách"), "owner", owners.map(function (o) { return [o, o]; })) +
      toggleField(t("JS_MK_FILTER_HAS_NEXT", "Có hành động tiếp"), "hasNextAction", !!state.filters.hasNextAction, false) +
      "</div>";
    host.hidden = !state.filtersOpen;
    syncFilterControls();
  }

  function fieldSelect(label, key, pairs) {
    return (
      '<label class="mk-leads-filter-field"><span class="mk-leads-filter-field__label">' +
      esc(label) +
      '</span><select class="mk-leads-filter-field__select" data-fkey="' +
      key +
      '">' +
      selectOptions(pairs) +
      "</select></label>"
    );
  }

  function toggleField(label, key, on, warn) {
    return (
      '<label class="mk-leads-toggle-field' +
      (warn ? " mk-leads-toggle-field--warn" : "") +
      '"><span class="mk-leads-toggle-field__label">' +
      esc(label) +
      '</span><input type="checkbox" class="mk-leads-toggle-field__input" data-fkey="' +
      key +
      '"' +
      (on ? " checked" : "") +
      " /></label>"
    );
  }

  function syncFilterControls() {
    var f = state.filters;
    document.querySelectorAll("#mk-contacts-filters-panel [data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (el.type === "checkbox") el.checked = !!f[key];
      else if (f[key] !== undefined) el.value = f[key];
    });
  }

  function tagBadgeHtml(tag) {
    if (!tag) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(tag);
    var key = ref.normalizeTag(tag) || String(tag || "").trim();
    return (
      '<span class="mk-tag" data-tag="' +
      esc(key) +
      '">' +
      esc(m.label) +
      "</span>"
    );
  }

  function tierPill(tags) {
    var tier = categorize(tags).tier;
    if (!tier) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(tier);
    var key = ref.normalizeTag(tier);
    return (
      '<span class="mk-tag" data-tag="' +
      esc(key) +
      '">' +
      esc(m.label) +
      "</span>"
    );
  }

  function stackedContactTags(contact) {
    var list = (contact && contact.tags) || [];
    var seen = {};
    var parts = [];
    function pushTag(tg) {
      var key = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
      if (!key || seen[key]) return;
      if (key === "da_cap_bang" || key === "da_cap_tai_khoan") return;
      // Dedicated columns — skip duplicate chips
      if (key === "ca_nhan" || key === "cong_ty" || key === "miutea" || key === "khach_le") return;
      seen[key] = true;
      parts.push(tagBadgeHtml(tg));
    }
    list.forEach(pushTag);
    if (!parts.length) return '<span class="mk-leads-muted">Thêm thẻ…</span>';
    var maxShow = 2;
    var shown = parts.slice(0, maxShow);
    var extra = parts.length - shown.length;
    return (
      '<div class="mk-leads-tags-stack">' +
      shown.join("") +
      (extra > 0 ? '<span class="mk-leads-tag-more">+' + extra + "</span>" : "") +
      "</div>"
    );
  }

  function editableCellHtml(field, value, recordId, placeholder) {
    var shown = value;
    if (field === "phone" && value && window.MkPhoneFormat && typeof window.MkPhoneFormat.format === "function") {
      shown = window.MkPhoneFormat.format(value) || value;
    }
    var display = shown
      ? esc(shown)
      : '<span class="mk-leads-muted">' + esc(placeholder || "—") + "</span>";
    return (
      '<button type="button" class="mk-leads-inline-edit" data-field="' +
      esc(field) +
      '" data-contact-id="' +
      esc(recordId) +
      '" title="Nhấn để sửa">' +
      display +
      "</button>"
    );
  }

  function beginInlineEdit(btn) {
    if (!btn || !btn.getAttribute) return;
    var field = btn.getAttribute("data-field");
    var recordId = btn.getAttribute("data-contact-id");
    var current = btn.textContent.trim();
    if (current === "—" || current === "Nhập SĐT" || current === "Nhập địa chỉ") current = "";
    var input = document.createElement("input");
    input.type = field === "phone" ? "tel" : "text";
    input.className = "mk-leads-inline-input";
    input.value = current;
    input.setAttribute("data-field", field);
    input.setAttribute("data-contact-id", recordId);
    if (field === "phone") {
      input.setAttribute("inputmode", "numeric");
      input.setAttribute("maxlength", "12");
      input.addEventListener("input", function () {
        var next =
          window.MkPhoneFormat && typeof window.MkPhoneFormat.formatInput === "function"
            ? window.MkPhoneFormat.formatInput(input.value)
            : String(input.value || "").replace(/\D+/g, "").slice(0, 10);
        if (next !== input.value) input.value = next;
      });
    }
    btn.replaceWith(input);
    input.focus();
    if (current) input.select();
  }

  function commitInlineEdit(input) {
    if (!input || !input.getAttribute || !store || !store.saveInlineFields) {
      renderTable();
      return;
    }
    var field = input.getAttribute("data-field");
    var recordId = input.getAttribute("data-contact-id");
    var val = String(input.value || "").trim();
    var patch = {};
    if (field === "phone") {
      val = val.replace(/\s+/g, "");
      if (val && !/^\d{10}$/.test(val)) {
        notifyUser("warning", "Số điện thoại phải đủ 10 số.");
        renderTable();
        return;
      }
      patch.phone = val;
    } else if (field === "address") {
      patch.address = val;
    } else {
      renderTable();
      return;
    }
    input.disabled = true;
    store
      .saveInlineFields(recordId, patch)
      .then(function () {
        renderTable();
      })
      .catch(function (err) {
        notifyUser("error", (err && err.message) || "Không lưu được.");
        renderTable();
      });
  }

  function edubitProgressTimelineHtml(contact) {
    var courses = resolveEdubitCourses(contact);
    if (!courses.length) return null;

    function fmtDay(iso) {
      if (!iso) return "";
      var d = new Date(iso);
      if (isNaN(d.getTime())) return "";
      var dd = String(d.getDate()).padStart(2, "0");
      var mm = String(d.getMonth() + 1).padStart(2, "0");
      return dd + "/" + mm;
    }

    var renewCount = Number(contact.edubit_renew_count) || 0;
    var renewLeft =
      contact.edubit_renew_remaining != null
        ? Number(contact.edubit_renew_remaining)
        : Math.max(0, 3 - renewCount);
    var canRenew = Number(contact.can_edubit_renew) === 1 && renewLeft > 0;
    var exp = fmtDay(contact.edubit_expires_at);
    var renewBtn = canRenew
      ? '<button type="button" class="mk-contacts-edubit-renew" data-mk-edubit-renew="' +
        esc(contact.id) +
        '" title="Gia hạn +10 ngày (tối đa 3 lần)">Gia hạn</button>'
      : contact.edubit_expires_at
        ? '<span class="mk-contacts-edubit-renew-muted">' +
          (renewLeft > 0 ? "Còn " + renewLeft + " GH" : "Hết lượt GH") +
          "</span>"
        : "";

    var rows = courses
      .map(function (c, idx) {
        var cid = String((c && c.course_id) || "");
        var p =
          c && c.progress_pct != null && c.progress_pct !== ""
            ? Math.max(0, Math.min(100, Number(c.progress_pct) || 0))
            : 0;
        if (
          courses.length === 1 &&
          contact.edubit_progress_pct != null &&
          contact.edubit_progress_pct !== ""
        ) {
          p = Math.max(0, Math.min(100, Number(contact.edubit_progress_pct) || 0));
        }
        var lab = edubitCourseLabel(cid, c && c.label);
        var metaExtra = [];
        if (courses.length === 1) {
          if (exp) metaExtra.push("Hết hạn " + exp);
          metaExtra.push("GH " + renewCount + "/3");
        }
        return (
          '<div class="mk-contacts-edubit-progress' +
          (courses.length > 1 ? " mk-contacts-edubit-progress--multi" : "") +
          '" title="' +
          esc(lab + " · " + p + "%") +
          '">' +
          '<div class="mk-contacts-edubit-progress__meta">' +
          esc(lab) +
          (metaExtra.length ? " · " + esc(metaExtra.join(" · ")) : "") +
          "</div>" +
          '<div class="mk-contacts-edubit-progress__bar"><span style="width:' +
          p +
          '%"></span></div>' +
          '<div class="mk-contacts-edubit-progress__label">' +
          p +
          "%</div>" +
          (idx === 0 && courses.length === 1 ? renewBtn : "") +
          "</div>"
        );
      })
      .join("");

    if (courses.length === 1) return rows;
    return (
      '<div class="mk-contacts-edubit-courses">' +
      rows +
      (renewBtn ? '<div class="mk-contacts-edubit-courses__renew">' + renewBtn + "</div>" : "") +
      "</div>"
    );
  }

  function bangCellHtml(contact) {
    return credentialSelectHtml(contact, "bang");
  }

  function deriveNextAction(contact) {
    var stored = String((contact && contact.next_action) || "").trim();
    if (/^(Nhắc gọi Call\s*#|Đã nghe máy|Đã đủ 3 lần gọi|Gọi:\s*Nhắc gọi)/i.test(stored)) {
      return "";
    }
    return stored;
  }

  function daysSinceIso(iso) {
    if (!iso) return 0;
    var ts = new Date(iso).getTime();
    if (isNaN(ts)) return 0;
    return Math.max(0, Math.floor((Date.now() - ts) / 86400000));
  }

  function nextActionTimeframeMeta(contact) {
    var alertDays = contact && contact.rule_alert_days;
    if (alertDays == null || alertDays <= 0) return null;
    if (contact.next_action_overdue) {
      return {
        kind: "overdue",
        days: contact.next_action_days_overdue || 0,
        alertDays: alertDays,
      };
    }
    if (contact.next_action_days_remaining != null) {
      return {
        kind: "remaining",
        days: contact.next_action_days_remaining,
        alertDays: alertDays,
      };
    }
    var idle = daysSinceIso(contact.last_touch);
    var rem = alertDays - idle;
    if (rem < 0) {
      return { kind: "overdue", days: -rem, alertDays: alertDays };
    }
    return { kind: "remaining", days: rem, alertDays: alertDays };
  }

  function nextActionTimeframeLabel(contact) {
    var meta = nextActionTimeframeMeta(contact);
    if (!meta) return "";
    if (meta.kind === "overdue") {
      return "Quá hạn " + meta.days + " ngày";
    }
    if (meta.days === 0) {
      return "Hôm nay";
    }
    if (meta.kind === "remaining") {
      return "Còn " + meta.days + " ngày";
    }
    return "Còn " + meta.alertDays + " ngày";
  }

  function nextActionCellHtml(contact) {
    var next = deriveNextAction(contact);
    var tf = nextActionTimeframeLabel(contact);
    if (!next && !tf) {
      return '<span class="mk-leads-muted">—</span>';
    }
    var html = "";
    if (next) {
      html += '<span class="mk-leads-next-action__text">' + esc(next) + "</span>";
    }
    if (tf) {
      var meta = nextActionTimeframeMeta(contact);
      var cls = "mk-leads-next-action__time";
      if (meta && meta.kind === "overdue") {
        cls += " mk-leads-next-action__time--overdue";
      }
      html += '<span class="' + cls + '">' + esc(tf) + "</span>";
    }
    return '<div class="mk-leads-next-action">' + html + "</div>";
  }

  function progressCellHtml(contact) {
    var timeline = edubitProgressTimelineHtml(contact);
    return timeline || '<span class="mk-leads-muted">—</span>';
  }

  function productChipsFromTags(contact) {
    var groups = productGroupsFromTags(contact && contact.tags);
    var labels = { online: "Online", offline: "Offline", nvl: "NVL", pcth: "PCTH", mqbb: "MQBB" };
    if (!groups.length) {
      return '<span class="mk-leads-muted">—</span>';
    }
    return (
      '<span class="mk-lead-pchips">' +
      groups
        .map(function (g) {
          return (
            '<span class="mk-lead-pchip mk-lead-pchip--' +
            esc(g) +
            '">' +
            esc(labels[g] || g) +
            "</span>"
          );
        })
        .join("") +
      "</span>"
    );
  }

  function customerTypeCellHtml(contact) {
    var cats = categorize(contact && contact.tags);
    if (!cats.customerType) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(cats.customerType);
    return (
      '<span class="mk-tag ' +
      esc(m.cls || "") +
      '">' +
      esc(m.label || cats.customerType) +
      "</span>"
    );
  }

  function nvlSegmentCellHtml(contact) {
    var cats = categorize(contact && contact.tags);
    if (!cats.nvlSegment) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(cats.nvlSegment);
    return (
      '<span class="mk-tag ' +
      esc(m.cls || "") +
      '">' +
      esc(m.label || cats.nvlSegment) +
      "</span>"
    );
  }

  function toDatetimeLocalValue(iso) {
    if (!iso) return "";
    var d = new Date(iso);
    if (isNaN(d.getTime())) return "";
    var pad = function (n) {
      return (n < 10 ? "0" : "") + n;
    };
    return (
      d.getFullYear() +
      "-" +
      pad(d.getMonth() + 1) +
      "-" +
      pad(d.getDate()) +
      "T" +
      pad(d.getHours()) +
      ":" +
      pad(d.getMinutes())
    );
  }

  function offlineClassOptions() {
    var rows = window.MK_OFFLINE_CLASSES;
    if (!Array.isArray(rows) || !rows.length) {
      return [
        ["mqbb", "MQBB"],
        ["pcth", "PCTH"],
        ["pcth_cb", "PCTHCB"],
      ];
    }
    return rows.map(function (row) {
      return [row.code, row.label];
    });
  }

  function offlineAttendCellHtml(contact) {
    var id = contact.crmid || contact.id;
    var attend = contact.offline_attend && typeof contact.offline_attend === "object" ? contact.offline_attend : {};
    var legacy = {
      mqbb: contact.thoigian_mqbb || "",
      pcth: contact.thoigian_pcth || "",
      pcth_cb: contact.thoigian_pcthcb || "",
    };
    var opts = offlineClassOptions();
    var curClass = opts[0] ? opts[0][0] : "mqbb";
    opts.forEach(function (o) {
      if (attend[o[0]] || legacy[o[0]]) curClass = o[0];
    });
    var dataAttrs = opts.map(function (o) {
      var iso = attend[o[0]] || legacy[o[0]] || "";
      return ' data-at-' + esc(o[0]) + '="' + esc(toDatetimeLocalValue(iso)) + '"';
    }).join("");
    var currentIso = attend[curClass] || legacy[curClass] || "";
    var adminOpts = window.MK_CONTACTS_IS_ADMIN
      ? '<option value="__add__">＋ Thêm lớp…</option><option value="__del__">× Xóa lớp này</option>'
      : "";
    return (
      '<div class="mk-contacts-offline-attend" data-contact-id="' +
      esc(id) +
      '" data-class="' +
      esc(curClass) +
      '"' +
      dataAttrs +
      ">" +
      '<select class="mk-leads-region-select mk-contacts-offline-class" title="Lớp Offline">' +
      opts
        .map(function (o) {
          return (
            '<option value="' +
            esc(o[0]) +
            '"' +
            (curClass === o[0] ? " selected" : "") +
            ">" +
            esc(o[1]) +
            "</option>"
          );
        })
        .join("") +
      adminOpts +
      "</select>" +
      '<input type="datetime-local" class="mk-leads-inline-input mk-contacts-offline-dt" value="' +
      esc(toDatetimeLocalValue(currentIso)) +
      '" title="Thời gian tham gia Offline" />' +
      "</div>"
    );
  }

  function isCredentialIssued(value, kind) {
    var v = String(value || "").trim();
    if (!v) return false;
    if (kind === "tk") return /đã\s*cấp/i.test(v) && !/chưa/i.test(v);
    return /đã\s*cấp/i.test(v) && !/chưa/i.test(v);
  }

  function credentialSelectHtml(contact, kind) {
    var field = kind === "tk" ? "da_cap_tai_khoan" : "da_cap_bang";
    var options =
      kind === "tk"
        ? ["Chưa cấp tài khoản", "Đã cấp"]
        : ["Chưa cấp", "Đã cấp"];
    var cur = String((contact && contact[field]) || "").trim() || options[0];
    if (options.indexOf(cur) < 0) {
      cur = isCredentialIssued(cur, kind) ? options[1] : options[0];
    }
    var opts = options
      .map(function (opt) {
        return (
          '<option value="' +
          esc(opt) +
          '"' +
          (opt === cur ? " selected" : "") +
          ">" +
          esc(opt) +
          "</option>"
        );
      })
      .join("");
    return (
      '<select class="mk-contacts-cred-select" data-cred-field="' +
      esc(field) +
      '" data-contact-id="' +
      esc(contact.id) +
      '" title="Sửa trạng thái">' +
      opts +
      "</select>"
    );
  }

  function closeTagPopover() {
    var old = document.getElementById("mk-contacts-tag-popover");
    if (old && old.parentNode) old.parentNode.removeChild(old);
  }

  function openTagPopover(anchor, contact) {
    closeTagPopover();
    if (!contact || !store) return;
    var catalog = ref && ref.getCreateTagCatalog ? ref.getCreateTagCatalog() : [];
    var selected = {};
    (contact.tags || []).forEach(function (tg) {
      var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
      if (k === "da_cap_bang" || k === "da_cap_tai_khoan") return;
      if (k) selected[k] = true;
    });
    var pop = document.createElement("div");
    pop.id = "mk-contacts-tag-popover";
    pop.className = "mk-leads-tag-popover";
    var groupsHtml = catalog
      .map(function (g) {
        var chips = (g.tags || [])
          .map(function (item) {
            var on = !!selected[item.key];
            return (
              '<button type="button" class="mk-leads-tag-chip' +
              (on ? " is-on" : "") +
              '" data-tag="' +
              esc(item.key) +
              '" aria-pressed="' +
              (on ? "true" : "false") +
              '">' +
              esc(item.label) +
              "</button>"
            );
          })
          .join("");
        return (
          '<div class="mk-leads-tag-popover__group" data-group="' +
          esc(g.id) +
          '">' +
          '<div class="mk-leads-tag-popover__group-title">' +
          esc(g.label) +
          "</div>" +
          '<div class="mk-leads-tag-popover__chips">' +
          chips +
          "</div></div>"
        );
      })
      .join("");
    pop.innerHTML =
      '<div class="mk-leads-tag-popover__head"><strong>Sửa thẻ</strong>' +
      '<button type="button" class="mk-leads-tag-popover__close" aria-label="Đóng">&times;</button></div>' +
      '<div class="mk-leads-tag-popover__body">' +
      groupsHtml +
      "</div>" +
      '<div class="mk-leads-tag-popover__foot">' +
      '<button type="button" class="mk-leads-btn mk-leads-btn--outline" data-tag-cancel="1">Hủy</button>' +
      '<button type="button" class="mk-leads-btn" data-tag-save="1">Lưu thẻ</button>' +
      "</div>";
    document.body.appendChild(pop);
    var rect = anchor.getBoundingClientRect();
    pop.style.top = rect.bottom + window.scrollY + 6 + "px";
    pop.style.left =
      Math.max(8, Math.min(rect.left + window.scrollX, window.scrollX + window.innerWidth - 360)) + "px";
    pop.addEventListener("click", function (e) {
      e.stopPropagation();
      var chip = e.target.closest && e.target.closest(".mk-leads-tag-chip");
      if (chip) {
        var groupEl = chip.closest(".mk-leads-tag-popover__group");
        var groupId = groupEl ? groupEl.getAttribute("data-group") : "";
        var turningOn = !chip.classList.contains("is-on");
        if (groupEl && turningOn && groupId !== "class") {
          groupEl.querySelectorAll(".mk-leads-tag-chip.is-on").forEach(function (el) {
            el.classList.remove("is-on");
            el.setAttribute("aria-pressed", "false");
          });
        }
        chip.classList.toggle("is-on", turningOn);
        chip.setAttribute("aria-pressed", turningOn ? "true" : "false");
        return;
      }
      if (e.target.closest && (e.target.closest("[data-tag-cancel]") || e.target.closest(".mk-leads-tag-popover__close"))) {
        closeTagPopover();
        return;
      }
      if (e.target.closest && e.target.closest("[data-tag-save]")) {
        var nextTags = [];
        pop.querySelectorAll(".mk-leads-tag-chip.is-on").forEach(function (el) {
          var key = el.getAttribute("data-tag");
          if (key === "da_cap_bang" || key === "da_cap_tai_khoan") return;
          nextTags.push(key);
        });
        var saveBtn = e.target.closest("[data-tag-save]");
        if (saveBtn) saveBtn.disabled = true;
        var saveFn = store.saveTags
          ? store.saveTags(contact.crmid || contact.id, nextTags)
          : Promise.reject(new Error("saveTags unavailable"));
        saveFn
          .then(function () {
            closeTagPopover();
            renderAll();
          })
          .catch(function () {
            notifyUser("error", "Không lưu được thẻ.");
            if (saveBtn) saveBtn.disabled = false;
          });
      }
    });
  }

  function gd14Questions() {
    var bank = window.MK_GD14_QUESTIONS;
    return bank && Array.isArray(bank.questions) ? bank.questions : [];
  }

  function screeningQuestions() {
    var bank = window.MK_SCREENING_QUESTIONS;
    var rows = bank && Array.isArray(bank.questions) ? bank.questions : [];
    return rows.filter(function (q) {
      return q && q.active !== false && ["c1", "c2", "c3"].indexOf(String(q.id || "").toLowerCase()) >= 0;
    });
  }

  function gd14Courses() {
    return Array.isArray(window.MK_GD14_COURSES) ? window.MK_GD14_COURSES : [];
  }

  function gd14OptionLabel(question, code) {
    var want = String(code || "").toLowerCase();
    var options = (question && question.options) || [];
    for (var i = 0; i < options.length; i++) {
      if (String(options[i].code || "").toLowerCase() === want) return options[i].label || want;
    }
    return want;
  }

  function gd14CourseLabel(code) {
    var want = String(code || "").toLowerCase();
    var rows = gd14Courses();
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i].code || "").toLowerCase() === want) return rows[i].label || want;
    }
    return want;
  }

  function fmtIsoDay(iso) {
    if (!iso) return "—";
    var d = new Date(iso);
    if (isNaN(d.getTime())) return "—";
    var dd = String(d.getDate()).padStart(2, "0");
    var mm = String(d.getMonth() + 1).padStart(2, "0");
    return dd + "/" + mm + "/" + d.getFullYear();
  }

  function findContact(id) {
    var want = String(id || "");
    var rows = getContacts();
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i].id) === want || String(rows[i].crmid || "") === want) return rows[i];
    }
    return null;
  }

  function contactIsOnlineLane(contact) {
    var groups = productGroupsFromTags((contact && contact.tags) || []);
    return !!groups.online;
  }

  function contactShowsEdubit(contact) {
    if (!contact) return false;
    if (contact.edubit_user_id || contact.edubit_course_id) return true;
    if (Array.isArray(contact.edubit_courses) && contact.edubit_courses.length) return true;
    if (contactHasGd14(contact)) return true;
    return contactIsOnlineLane(contact);
  }

  function compareModeOf(contact) {
    var compare = contact && contact.compare && typeof contact.compare === "object" ? contact.compare : {};
    var mode = String(compare.mode || "").toLowerCase();
    if (mode === "gd14" || mode === "gd11") return mode;
    return contactHasGd14(contact) ? "gd14" : "gd11";
  }

  function verifyOpenHtml(row) {
    var lines = row && Array.isArray(row.verify_lines) ? row.verify_lines : [];
    var compare = row && row.compare && typeof row.compare === "object" ? row.compare : {};
    var hasGd14 = contactHasGd14(row);
    if (!lines.length && !hasGd14 && !Number(compare.editable)) return "";
    var elig = String(compare.eligibility_result || "").toLowerCase();
    if (!elig && lines.length) {
      for (var i = 0; i < lines.length; i++) {
        var lab = String((lines[i] && lines[i].label) || "");
        var val = String((lines[i] && lines[i].value) || "").toLowerCase();
        if (lab.indexOf("Điều kiện") >= 0 || lab.indexOf("ieu kien") >= 0) {
          if (val.indexOf("không đủ") >= 0 || val.indexOf("khong du") >= 0) elig = "khong_du_dk";
          else if (val.indexOf("đủ") >= 0 || val.indexOf("du ") >= 0) elig = "du_dk";
        }
      }
    }
    var done = Number(compare.editable) === 1 || elig === "du_dk" || !!(compare.course || (compare.answers && Object.keys(compare.answers).length));
    var fail = elig === "khong_du_dk";
    var label = fail ? "Không đủ điều kiện" : done ? "Đã xác minh" : hasGd14 ? "Xác minh 990k" : "Xác minh";
    var title = fail ? "Không đủ điều kiện 990k" : done ? "Đã xác minh — mở để chỉnh đáp án" : "Xác minh / đối chiếu 990k";
    var cls =
      "mk-leads-verify-btn" + (fail ? " is-fail" : done ? " is-done" : "");
    return (
      '<button type="button" class="' +
      cls +
      '" data-mk-gd14-open="' +
      esc(row.crmid || row.id) +
      '" title="' +
      esc(title) +
      '">' +
      '<svg class="mk-leads-verify-btn__ic" width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
      '<path d="M12 3 5 6v6c0 5 3.2 8.2 7 9.5 3.8-1.3 7-4.5 7-9.5V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' +
      '<path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
      "</svg><span>" +
      esc(label) +
      "</span></button>"
    );
  }

  function contactHasGd14(contact) {
    var tags = (contact && contact.tags) || [];
    for (var i = 0; i < tags.length; i++) {
      if (String(tags[i]).toLowerCase().indexOf("gd14_") === 0) return true;
    }
    return false;
  }

  function gd14ClassCellHtml(contact) {
    if (!contactHasGd14(contact)) return '<span class="mk-leads-muted">—</span>';
    var id = contact.crmid || contact.id;
    var g = contact.gd14 && typeof contact.gd14 === "object" ? contact.gd14 : {};
    var tags = (contact.tags || []).map(function (tag) {
      return String(tag).toLowerCase();
    });
    var stopped = tags.indexOf("gd14_ngung_cham_soc") >= 0 || !!g.drop;
    var attended = tags.indexOf("gd14_da_tham_gia") >= 0;
    var fresh = tags.indexOf("gd14_moi_dang_ky") >= 0;
    var meta =
      '<div class="mk-gd14-class__meta">R4 không tham gia <strong>' +
      esc(String(g.r4 || 0)) +
      "/3</strong>" +
      (g.class_date
        ? " · " + esc(g.class_date) + (g.class_time ? " " + esc(g.class_time) : "")
        : "") +
      (g.class_place ? " · " + esc(g.class_place) : "") +
      (Number(g.preclass) === 1 ? " · Sẽ đến" : "") +
      "</div>";
    if (fresh) {
      return '<div class="mk-gd14-class">' + meta + '<div class="mk-gd14-class__note">Hết bảo lưu, về tag Mới đăng ký.</div></div>';
    }
    if (stopped && !attended) {
      return (
        '<div class="mk-gd14-class">' +
        meta +
        '<div class="mk-gd14-class__note">' +
        esc(g.drop_reason || "Đã ngưng chăm sóc") +
        "</div></div>"
      );
    }
    if (attended) {
      return (
        '<div class="mk-gd14-class">' +
        meta +
        '<div class="mk-gd14-class__note">Đã điểm danh' +
        (g.checked_in_at ? " · " + esc(g.checked_in_at) : "") +
        "</div>" +
        '<button type="button" class="mk-gd14-class__btn" data-mk-gd14-qr="1">Mã QR</button></div>'
      );
    }
    return (
      '<div class="mk-gd14-class" data-contact-id="' +
      esc(id) +
      '">' +
      meta +
      '<div class="mk-gd14-class__form">' +
      '<input type="date" class="mk-gd14-class__input" data-mk-gd14-date value="' +
      esc(g.class_date || "") +
      '" />' +
      '<input type="time" class="mk-gd14-class__input" data-mk-gd14-time value="' +
      esc(g.class_time || "") +
      '" />' +
      '<input type="text" class="mk-gd14-class__input" data-mk-gd14-place value="' +
      esc(g.class_place || "") +
      '" placeholder="Địa điểm" />' +
      "</div>" +
      '<div class="mk-gd14-class__actions">' +
      '<button type="button" class="mk-gd14-class__btn" data-mk-gd14-step="schedule" data-contact-id="' +
      esc(id) +
      '">Lưu lớp</button>' +
      '<button type="button" class="mk-gd14-class__btn" data-mk-gd14-step="preclass_yes" data-contact-id="' +
      esc(id) +
      '">Sẽ đến</button>' +
      '<button type="button" class="mk-gd14-class__btn" data-mk-gd14-step="preclass_no" data-contact-id="' +
      esc(id) +
      '">Chưa chắc</button>' +
      '<button type="button" class="mk-gd14-class__btn" data-mk-gd14-qr="1">Mã QR</button>' +
      '<button type="button" class="mk-gd14-class__btn mk-gd14-class__btn--ok" data-mk-gd14-step="da_tham_gia" data-contact-id="' +
      esc(id) +
      '">Đã tham gia</button>' +
      '<button type="button" class="mk-gd14-class__btn mk-gd14-class__btn--no" data-mk-gd14-step="khong_tham_gia" data-contact-id="' +
      esc(id) +
      '">Không tham gia</button>' +
      "</div></div>"
    );
  }

  function openGd14QrModal() {
    var host = document.getElementById("mk-contacts-gd14-qr");
    if (!host) {
      host = document.createElement("div");
      host.id = "mk-contacts-gd14-qr";
      host.className = "mk-gd14-qr";
      host.innerHTML =
        '<div class="mk-gd14-qr__card" role="dialog" aria-label="Mã QR điểm danh 990k">' +
        '<button type="button" class="mk-gd14-qr__close" data-mk-gd14-qr-close aria-label="Đóng">×</button>' +
        "<h3>Điểm danh lớp 990k</h3>" +
        '<img src="layouts/v7/modules/Potentials/resources/offline-oa-form-qr.png?mk_v=20261002_gd14flow1" alt="Mã QR điểm danh" />' +
        "<p>Khách quét mã tại quầy. Sales ghi Đã tham gia hoặc Không tham gia trên dòng khách.</p>" +
        "</div>";
      document.body.appendChild(host);
    }
    host.hidden = false;
  }

  function compareSelectHtml(name, options, selected, placeholder) {
    var html = '<select class="mk-gd14-panel__select" data-mk-gd14-field="' + esc(name) + '">';
    html += '<option value="">' + esc(placeholder || "— Chọn —") + "</option>";
    (options || []).forEach(function (opt) {
      var code = String(opt.code || "");
      html +=
        '<option value="' +
        esc(code) +
        '"' +
        (String(selected || "").toLowerCase() === code.toLowerCase() ? " selected" : "") +
        ">" +
        esc(opt.label || code) +
        "</option>";
    });
    return html + "</select>";
  }

  function compareFormCardsHtml(questions, form) {
    if (!questions || !questions.length) {
      return '<p class="mk-gd14-panel__empty">Chưa có đáp án form.</p>';
    }
    return (
      '<div class="mk-gd14-panel__formcards">' +
      questions
        .map(function (q) {
          var code = form[q.id] || "";
          return (
            '<div class="mk-gd14-panel__formcard"><em>' +
            esc(String(q.id || "").toUpperCase()) +
            "</em><strong>" +
            esc(code ? gd14OptionLabel(q, code) : "—") +
            "</strong><small>" +
            esc(code ? "Mã " + String(code).toUpperCase() : "Form chưa có") +
            "</small></div>"
          );
        })
        .join("") +
      "</div>"
    );
  }

  function compareEdubitBlockHtml(contact) {
    if (!contactShowsEdubit(contact)) return "";
    var id = contact.crmid || contact.id;
    var courses = resolveEdubitCourses(contact);
    var owned = {};
    courses.forEach(function (c) {
      var cid = String((c && c.course_id) || "").replace(/\D+/g, "");
      if (cid) owned[cid] = true;
    });
    if (contact.edubit_course_id) {
      owned[String(contact.edubit_course_id).replace(/\D+/g, "")] = true;
    }
    var choices = [
      { id: "29403", label: "29403 — Khai trương quán bài bản (990k)" },
      { id: "29218", label: "29218 — Pha chế tổng hợp" },
      { id: "28108", label: "28108 — Pha chế tổng hợp cơ bản" },
    ];
    var opts = choices
      .map(function (c) {
        var isOwned = !!owned[c.id];
        return (
          '<label class="mk-gd14-panel__course' +
          (isOwned ? " is-owned" : "") +
          '"><input type="checkbox" value="' +
          esc(c.id) +
          '" data-mk-panel-edubit-course="1"' +
          (isOwned ? " checked disabled" : "") +
          " /><span>" +
          esc(c.label) +
          "</span>" +
          (isOwned ? "<em>Đã có</em>" : "") +
          "</label>"
        );
      })
      .join("");
    var hasAcc = !!(contact.edubit_user_id || courses.length);
    var statusRows =
      '<div class="mk-gd14-panel__status">' +
      '<div class="mk-gd14-panel__line"><span>Tài khoản</span><strong>' +
      esc(contact.edubit_email || contact.email || (hasAcc ? "Đã cấp" : "Chưa cấp")) +
      "</strong></div>" +
      '<div class="mk-gd14-panel__line"><span>Lớp / khóa</span><strong>' +
      esc(
        courses.length
          ? courses
              .map(function (c) {
                return edubitCourseLabel(c.course_id, c.label);
              })
              .join(", ")
          : contact.edubit_course_id
            ? edubitCourseLabel(contact.edubit_course_id)
            : "—"
      ) +
      "</strong></div>" +
      '<div class="mk-gd14-panel__line"><span>Hết hạn</span><strong>' +
      esc(fmtIsoDay(contact.edubit_expires_at)) +
      "</strong></div>" +
      (contact.edubit_user_id
        ? '<div class="mk-gd14-panel__line"><span>User ID</span><strong>' +
          esc(String(contact.edubit_user_id)) +
          "</strong></div>"
        : "") +
      "</div>";
    return (
      '<section class="mk-gd14-panel__card mk-gd14-panel__card--edubit" data-mk-panel-edubit="1">' +
      "<h3>Edubit — Tài khoản & lớp</h3>" +
      '<p>Online và 990k: cấp TK, xem email / khóa / hạn truy cập (đồng bộ detail).</p>' +
      statusRows +
      '<label class="mk-gd14-panel__field"><span>Email học viên</span>' +
      '<input type="email" class="mk-gd14-panel__select" data-mk-panel-edubit="email" value="' +
      esc(contact.edubit_email || contact.email || "") +
      '" placeholder="bắt buộc" ' +
      (hasAcc ? "readonly" : "") +
      " /></label>" +
      (hasAcc
        ? ""
        : '<label class="mk-gd14-panel__field"><span>Mật khẩu</span>' +
          '<input type="text" class="mk-gd14-panel__select" data-mk-panel-edubit="password" value="" placeholder="Để trống thì Edubit tự sinh" autocomplete="new-password" /></label>') +
      '<div class="mk-gd14-panel__field"><span>Khóa học</span><div class="mk-gd14-panel__courses">' +
      opts +
      "</div></div>" +
      '<button type="button" class="mk-gd14-panel__btn mk-gd14-panel__btn--primary" data-mk-panel-edubit-provision="' +
      esc(id) +
      '">' +
      (hasAcc ? "Thêm khóa học" : "Cấp TK + kích hoạt khóa") +
      "</button>" +
      '<p class="mk-gd14-panel__hint" data-mk-panel-edubit-status hidden></p>' +
      "</section>"
    );
  }

  function comparePanelHtml(contact) {
    var compare = contact.compare && typeof contact.compare === "object" ? contact.compare : {};
    var form = compare.form || {};
    var answers = compare.answers || {};
    var mode = compareModeOf(contact);
    var isGd14 = mode === "gd14" || contactHasGd14(contact);
    var questions = isGd14 ? gd14Questions() : screeningQuestions();
    var editable = Number(compare.editable) === 1 || isGd14 || Object.keys(form).length > 0 || Object.keys(answers).length > 0;
    var badge = isGd14 ? "990k" : "3 câu";
    var subtitle = isGd14
      ? "Đối chiếu form 990k — sales sửa được đáp án sau gọi"
      : "Xác minh GD 1.1 / 1.2 — sửa C1–C3 như trên Lead";
    var elig = String(compare.eligibility_result || "").toLowerCase();
    var pot = String(compare.potential_level || "").toLowerCase();
    var resultChip = "";
    if (elig === "du_dk") resultChip = '<span class="mk-gd14-panel__chip is-ok">Đủ điều kiện</span>';
    else if (elig === "khong_du_dk") resultChip = '<span class="mk-gd14-panel__chip is-fail">Không đủ ĐK</span>';
    if (pot) {
      var potLabel =
        pot === "sieu_tiem_nang"
          ? "Siêu tiềm năng"
          : pot === "tiem_nang"
            ? "Tiềm năng"
            : pot === "binh_thuong"
              ? "Bình thường"
              : pot === "khong_du_dk"
                ? "Không đủ ĐK"
                : pot;
      resultChip += '<span class="mk-gd14-panel__chip">' + esc(potLabel) + "</span>";
    }

    var answerRows = questions
      .map(function (q) {
        var formCode = form[q.id] || "";
        var selected = answers[q.id] || formCode || "";
        return (
          '<label class="mk-gd14-panel__field"><span>' +
          esc(q.label || q.id) +
          (formCode
            ? ' <small class="mk-gd14-panel__formhint">Form: ' + esc(String(formCode).toUpperCase()) + "</small>"
            : "") +
          "</span>" +
          compareSelectHtml(q.id, q.options || [], selected, "— Chọn —") +
          "</label>"
        );
      })
      .join("");

    var editBlock = "";
    if (editable) {
      if (isGd14) {
        editBlock =
          '<section class="mk-gd14-panel__card" data-mk-verify-mode="gd14"><h3>Sau đối chiếu</h3>' +
          "<p>Đáp án sales sửa được khi khách đổi ý.</p>" +
          answerRows +
          '<label class="mk-gd14-panel__field"><span>Mục tiêu khách nêu</span><input class="mk-gd14-panel__select" data-mk-gd14-field="goal" value="' +
          esc(compare.goal || "") +
          '" /></label>' +
          '<label class="mk-gd14-panel__field"><span>Khoá đã chọn</span>' +
          compareSelectHtml("course", gd14Courses(), compare.course || "", "— Chưa chọn —") +
          "</label>" +
          '<button type="button" class="mk-gd14-panel__btn mk-gd14-panel__btn--primary" data-mk-gd14-save="' +
          esc(contact.crmid || contact.id) +
          '">Lưu đối chiếu</button></section>';
      } else {
        editBlock =
          '<section class="mk-gd14-panel__card" data-mk-verify-mode="gd11"><h3>Sales xác minh</h3>' +
          "<p>Chọn lại C1–C3 khi khách khai sai hoặc đổi ý — lưu sẽ chấm lại mức tiềm năng.</p>" +
          answerRows +
          '<button type="button" class="mk-gd14-panel__btn mk-gd14-panel__btn--primary" data-mk-gd14-save="' +
          esc(contact.crmid || contact.id) +
          '">Lưu xác minh</button></section>';
      }
    }

    var staticLines =
      !editable && Array.isArray(contact.verify_lines) && contact.verify_lines.length
        ? '<section class="mk-gd14-panel__card"><h3>Đã ghi</h3>' +
          contact.verify_lines
            .map(function (line) {
              return (
                '<div class="mk-gd14-panel__line"><span>' +
                esc(line.label || "") +
                "</span><strong>" +
                esc(line.value || "") +
                "</strong></div>"
              );
            })
            .join("") +
          "</section>"
        : "";

    return (
      '<header class="mk-gd14-panel__head">' +
      '<div class="mk-gd14-panel__head-main"><span class="mk-gd14-panel__badge">' +
      esc(badge) +
      "</span><div><h2>" +
      esc(contact.name || "Khách hàng") +
      '</h2><p class="mk-gd14-panel__sub">' +
      esc(subtitle) +
      "</p>" +
      (resultChip ? '<div class="mk-gd14-panel__chips">' + resultChip + "</div>" : "") +
      (contact.phone ? '<p class="mk-gd14-panel__phone">' + esc(contact.phone) + "</p>" : "") +
      '</div></div><button type="button" class="mk-gd14-panel__close" data-mk-gd14-close aria-label="Đóng">×</button></header>' +
      '<div class="mk-gd14-panel__body">' +
      '<section class="mk-gd14-panel__card"><h3>Đáp án form</h3><p>Khách tự khai, không sửa ở đây.</p>' +
      compareFormCardsHtml(questions, form) +
      "</section>" +
      editBlock +
      staticLines +
      (contactHasGd14(contact)
        ? '<section class="mk-gd14-panel__card"><h3>Lớp 990k</h3>' + gd14ClassCellHtml(contact) + "</section>"
        : "") +
      compareEdubitBlockHtml(contact) +
      "</div>"
    );
  }

  function closeComparePanel() {
    var host = document.getElementById("mk-contacts-gd14-panel");
    if (!host) return;
    host.hidden = true;
    host.setAttribute("aria-hidden", "true");
    document.body.classList.remove("mk-gd14-panel-open");
  }

  function openComparePanel(id) {
    var contact = findContact(id);
    if (!contact) return;
    var host = document.getElementById("mk-contacts-gd14-panel");
    if (!host) {
      host = document.createElement("div");
      host.id = "mk-contacts-gd14-panel";
      host.className = "mk-gd14-panel";
      host.innerHTML =
        '<div class="mk-gd14-panel__backdrop" data-mk-gd14-close="1"></div>' +
        '<aside class="mk-gd14-panel__sheet" data-mk-gd14-sheet role="dialog" aria-modal="true"></aside>';
      document.body.appendChild(host);
      if (!document.documentElement.getAttribute("data-mk-gd14-esc")) {
        document.documentElement.setAttribute("data-mk-gd14-esc", "1");
        document.addEventListener("keydown", function (e) {
          if (e.key === "Escape") closeComparePanel();
        });
      }
    }
    host.hidden = false;
    host.setAttribute("aria-hidden", "false");
    host.setAttribute("data-contact-id", String(contact.crmid || contact.id));
    document.body.classList.add("mk-gd14-panel-open");
    var sheet = host.querySelector("[data-mk-gd14-sheet]");
    if (sheet) sheet.innerHTML = comparePanelHtml(contact);
  }

  function refreshComparePanel() {
    var host = document.getElementById("mk-contacts-gd14-panel");
    if (!host || host.hidden) return;
    openComparePanel(host.getAttribute("data-contact-id"));
  }

  function renderTable() {
    var all = getContacts();
    var rows = sortContacts(filterContacts(all));
    var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if (state.page > totalPages) state.page = 1;
    var start = (state.page - 1) * PAGE_SIZE;
    var pageRows = rows.slice(start, start + PAGE_SIZE);
    var tbody = $("mk-contacts-tbody");
    if (!tbody) return;

    if (!pageRows.length) {
      tbody.innerHTML =
        '<tr><td colspan="' +
        COL_COUNT +
        '" class="mk-leads-empty"><div class="mk-leads-empty__inner">' +
        esc(t("JS_MK_NO_CONTACTS_DISPLAY", "Không có khách hàng để hiển thị")) +
        "</div></td></tr>";
    } else {
      tbody.innerHTML = pageRows
        .map(function (c) {
          var crmId = c.crmid != null && c.crmid !== "" ? String(c.crmid) : String(c.id || "");
          var checked = state.selected[c.id] ? " checked" : "";
          return (
            '<tr class="mk-leads-row mk-contacts-row' +
            (state.selected[c.id] ? " mk-leads-row--selected" : "") +
            '" data-id="' +
            esc(c.id) +
            '"' +
            (crmId && /^\d+$/.test(crmId) ? ' data-crmid="' + esc(crmId) + '"' : "") +
            ">" +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-contacts-row-check" data-id="' + esc(c.id) + '"' + checked + " />" +
            '<span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td">' + dateCell(c.converted_at) + "</td>" +
            '<td class="mk-leads-td mk-leads-td--lead"><span class="mk-leads-lead-cell">' +
            ic("user") +
            '<span class="mk-leads-lead-text"><a class="mk-leads-name" href="' + detailUrl(c.crmid || c.id) + '">' + esc(c.name) + "</a>" +
            (c.title ? '<div class="mk-leads-sub">' + esc(c.title) + "</div>" : "") +
            verifyOpenHtml(c) +
            "</span></span></td>" +
            '<td class="mk-leads-td">' +
            editableCellHtml("phone", c.phone, c.crmid || c.id, "Nhập SĐT") +
            "</td>" +
            '<td class="mk-leads-td">' +
            editableCellHtml("address", c.address, c.crmid || c.id, "Nhập địa chỉ") +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--biz">' +
            businessModelSelectHtml(c.crmid || c.id, c.business_model) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--products">' +
            productChipsFromTags(c) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--cust">' +
            customerTypeCellHtml(c) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--nvl-seg">' +
            nvlSegmentCellHtml(c) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--tags"><button type="button" class="mk-leads-tags-edit" data-contact-id="' +
            esc(c.id) +
            '" title="Sửa thẻ">' +
            stackedContactTags(c) +
            "</button></td>" +
            '<td class="mk-leads-td">' + progressCellHtml(c) + "</td>" +
            '<td class="mk-leads-td">' + bangCellHtml(c) + "</td>" +
            '<td class="mk-leads-td">' + credentialSelectHtml(c, "tk") + "</td>" +
            '<td class="mk-leads-td">' + dateCell(c.thoigian_dangky) + "</td>" +
            '<td class="mk-leads-td">' + offlineAttendCellHtml(c) + "</td>" +
            '<td class="mk-leads-td mk-leads-td--owner"><span class="mk-leads-owner-inner">' +
            '<span class="mk-owner-avatar" style="background:' + ownerColor(c.owner) + '">' + esc(ownerInitials(c.owner)) + "</span>" +
            "<span>" + esc(c.owner || "—") + "</span></span></td>" +
            '<td class="mk-leads-td mk-leads-td--touch" data-col="last_touch">' +
            (window.MkLastTouchCall && window.MkLastTouchCall.lastTouchCallLogHtml
              ? window.MkLastTouchCall.lastTouchCallLogHtml(c, esc)
              : '<span class="mk-leads-muted">Chưa có cuộc gọi</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--next" data-col="next_action">' +
            nextActionCellHtml(c) +
            "</td>" +
            '<td class="mk-leads-td" data-col="notes">' +
            (function () {
              var n = String(c.notes || "").trim();
              if (!n) return '<span class="mk-leads-muted">—</span>';
              var short = n.length > 80 ? n.slice(0, 80) + "…" : n;
              return (
                '<span class="mk-leads-notes-cell" title="' +
                esc(n) +
                '">' +
                esc(short) +
                "</span>"
              );
            })() +
            "</td></tr>"
          );
        })
        .join("");
    }

    var summary = $("mk-contacts-filter-summary");
    if (summary) {
      summary.textContent =
        rows.length + " / " + all.length + " " + t("JS_MK_CONTACTS_COUNT_LABEL", "khách hàng");
    }
    renderPagination(rows.length, totalPages);

    var checkAll = $("mk-contacts-check-all");
    if (checkAll) {
      var allOnPage = pageRows.length > 0 && pageRows.every(function (c) {
        return !!state.selected[c.id];
      });
      checkAll.checked = allOnPage;
      checkAll.indeterminate = !allOnPage && pageRows.some(function (c) {
        return !!state.selected[c.id];
      });
    }
    renderBulkBar();
  }

  function selectedCount() {
    return Object.keys(state.selected).length;
  }

  function selectedRows() {
    return getContacts().filter(function (c) {
      return !!state.selected[c.id];
    });
  }

  function clearSelection() {
    state.selected = {};
    renderTable();
  }

  function renderBulkBar() {
    var bar = $("mk-contacts-bulk");
    if (!bar) return;
    var n = selectedCount();
    if (!n) {
      bar.hidden = true;
      bar.innerHTML = "";
      return;
    }
    bar.hidden = false;
    bar.innerHTML =
      '<div class="mk-leads-bulk-bar__inner">' +
      '<div class="mk-leads-bulk-bar__left">' +
      '<span class="mk-leads-bulk-badge" aria-hidden="true">' +
      ic("bulkCheck") +
      "</span>" +
      '<span class="mk-leads-bulk-bar__count"><strong>' +
      n +
      "</strong> selected</span>" +
      "</div>" +
      '<div class="mk-leads-bulk-bar__actions">' +
      '<button type="button" class="mk-leads-bulk-btn" data-bulk="export">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("export") +
      "</span><span>Export</span></button>" +
      '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--danger" data-bulk="delete">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("trash") +
      "</span><span>Xóa</span></button>" +
      "</div>" +
      '<button type="button" class="mk-leads-bulk-clear" data-bulk="clear">Clear</button>' +
      "</div>";
  }

  function exportCsv(rows) {
    var lines = ["Name,Phone,Account,Owner,Tags"];
    rows.forEach(function (c) {
      lines.push(
        [
          c.name || "",
          c.phone || "",
          c.account || "",
          c.owner || "",
          (c.tags || []).join("|"),
        ]
          .map(function (v) {
            return '"' + String(v).replace(/"/g, '""') + '"';
          })
          .join(",")
      );
    });
    var blob = new Blob([lines.join("\n")], { type: "text/csv;charset=utf-8;" });
    var url = URL.createObjectURL(blob);
    var a = document.createElement("a");
    a.href = url;
    a.download = "contacts.csv";
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  function renderPagination(total, totalPages) {
    var host = $("mk-contacts-pagination");
    if (!host) return;
    var pages = Math.max(1, totalPages || 1);
    var pageSize = PAGE_SIZE;
    var start = total ? (state.page - 1) * pageSize : 0;
    var from = total ? start + 1 : 0;
    var to = Math.min(start + pageSize, total);
    // Always show Leads-style footer (even when 1 page)
    host.innerHTML =
      '<span class="mk-leads-pagination__info">' +
      esc(t("JS_MK_SHOWING", "Hiển thị")) +
      " " +
      from +
      "\u2013" +
      to +
      " / " +
      total +
      "</span>" +
      '<div class="mk-leads-pagination__btns">' +
      '<button type="button" class="mk-leads-page-btn" data-page="prev"' +
      (state.page <= 1 ? " disabled" : "") +
      ">" +
      esc(t("JS_MK_PREV", "Trước")) +
      "</button>" +
      '<span class="mk-leads-page-num">' +
      state.page +
      " / " +
      pages +
      "</span>" +
      '<button type="button" class="mk-leads-page-btn" data-page="next"' +
      (state.page >= pages ? " disabled" : "") +
      ">" +
      esc(t("JS_MK_NEXT", "Sau")) +
      "</button></div>";
  }

  function applySegment(segId) {
    if (segId === "__all__") {
      state.activeSegment = null;
      state.productTab = "all";
      state.nvlSubFilter = ANY;
      state.filters = Object.assign({ classTags: [] }, EMPTY);
      state.page = 1;
      renderAll();
      return;
    }
    var seg = getPresetSegments().find(function (s) { return s.id === segId; });
    if (seg && seg.filters && seg.filters.classTag) {
      if (!Array.isArray(state.filters.classTags)) state.filters.classTags = [];
      var tag = seg.filters.classTag;
      var idx = state.filters.classTags.indexOf(tag);
      if (idx >= 0) state.filters.classTags.splice(idx, 1);
      else state.filters.classTags.push(tag);
      state.filters.classTag = ANY;
      state.filters.lane = ANY;
      state.filters.courseCount = ANY;
      state.productTab = "all";
      state.activeSegment = state.filters.classTags.length
        ? state.filters.classTags[state.filters.classTags.length - 1]
        : null;
      state.page = 1;
      renderAll();
      return;
    }
    state.activeSegment = segId;
    state.productTab = "all";
    state.nvlSubFilter = ANY;
    state.filters = Object.assign({ classTags: [] }, EMPTY);
    if (seg && seg.filters) {
      Object.keys(seg.filters).forEach(function (k) {
        state.filters[k] = seg.filters[k];
      });
    }
    // Chỉ giữ subfilter số khóa khi đang ở Khóa học; segment khác thì clear.
    if (segId !== "lane_courses") {
      state.filters.courseCount = ANY;
    }
    state.page = 1;
    renderAll();
  }

  function renderAll() {
    var rows = getContacts();
    renderKpi(rows);
    renderSegments();
    renderFiltersPanel();
    renderTable();
    document.documentElement.classList.add("mk-contacts-list-ready");
  }

  function bindEvents() {
    var search = $("mk-contacts-search");
    if (search) {
      search.addEventListener("input", function () {
        state.filters.search = search.value;
        state.page = 1;
        renderTable();
        renderKpi(filterContacts(getContacts()));
      });
    }

    var toggle = $("mk-contacts-filters-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        state.filtersOpen = !state.filtersOpen;
        toggle.setAttribute("aria-expanded", state.filtersOpen ? "true" : "false");
        var panel = $("mk-contacts-filters-panel");
        if (panel) panel.hidden = !state.filtersOpen;
      });
    }

    document.addEventListener("change", function (e) {
      var el = e.target;
      if (!el) return;
      if (el.classList && el.classList.contains("mk-leads-biz-select")) {
        e.stopPropagation();
        commitBusinessModelChange(el);
        return;
      }
      if (el.classList && el.classList.contains("mk-contacts-cred-select")) {
        var contactId = el.getAttribute("data-contact-id");
        var field = el.getAttribute("data-cred-field");
        var contact = getContacts().find(function (c) {
          return String(c.id) === String(contactId);
        });
        if (!contact || !field) return;
        var nextBang = field === "da_cap_bang" ? el.value : contact.da_cap_bang;
        var nextTk = field === "da_cap_tai_khoan" ? el.value : contact.da_cap_tai_khoan;
        el.disabled = true;
        var saveCred = store.saveCredentials
          ? store.saveCredentials(contact.crmid || contact.id, nextBang, nextTk)
          : Promise.resolve(
              store.patchContact(contact.crmid || contact.id, {
                da_cap_bang: nextBang,
                da_cap_tai_khoan: nextTk,
              })
            );
        saveCred
          .then(function () {
            // Strip legacy credential tags — status lives on dropdowns only.
            var tags = ((contact.tags || []).slice()).filter(function (tg) {
              var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
              return k !== "da_cap_bang" && k !== "da_cap_tai_khoan";
            });
            if (store.saveTags) {
              return store.saveTags(contact.crmid || contact.id, tags);
            }
            store.patchContact(contact.crmid || contact.id, { tags: tags });
          })
          .then(function () {
            renderAll();
          })
          .catch(function () {
            notifyUser("error", "Không lưu được trạng thái cấp bằng / tài khoản.");
            renderTable();
          });
        return;
      }
      if (el.classList && el.classList.contains("mk-contacts-row-check")) {
        var id = el.getAttribute("data-id");
        if (el.checked) state.selected[id] = true;
        else delete state.selected[id];
        renderTable();
        return;
      }
      if (el.id === "mk-contacts-check-all") {
        var pageRows = sortContacts(filterContacts(getContacts())).slice(
          (state.page - 1) * PAGE_SIZE,
          state.page * PAGE_SIZE
        );
        pageRows.forEach(function (c) {
          if (el.checked) state.selected[c.id] = true;
          else delete state.selected[c.id];
        });
        renderTable();
        return;
      }
      if (!el.getAttribute || !el.closest("#mk-contacts-filters-panel")) return;
      var key = el.getAttribute("data-fkey");
      if (!key) return;
      if (el.type === "checkbox") {
        state.filters[key] = !!el.checked;
      } else {
        state.filters[key] = el.value;
        if (key === "classTag") {
          state.filters.classTags = el.value && el.value !== ANY ? [el.value] : [];
        }
      }
      state.activeSegment = null;
      state.page = 1;
      renderAll();
    });

    document.addEventListener("click", function (e) {
      if (e.target.closest && e.target.closest("[data-mk-gd14-close]")) {
        closeComparePanel();
        return;
      }
      var openBtn = e.target.closest && e.target.closest("[data-mk-gd14-open]");
      if (openBtn) {
        e.preventDefault();
        openComparePanel(openBtn.getAttribute("data-mk-gd14-open"));
        return;
      }
      var saveBtn = e.target.closest && e.target.closest("[data-mk-gd14-save]");
      if (saveBtn) {
        e.preventDefault();
        var sid = saveBtn.getAttribute("data-mk-gd14-save");
        var sheet = saveBtn.closest ? saveBtn.closest("[data-mk-gd14-sheet]") : null;
        var modeCard = saveBtn.closest ? saveBtn.closest("[data-mk-verify-mode]") : null;
        var payload = { mode: modeCard ? modeCard.getAttribute("data-mk-verify-mode") || "" : "" };
        if (sheet) {
          sheet.querySelectorAll("[data-mk-gd14-field]").forEach(function (el) {
            payload[el.getAttribute("data-mk-gd14-field")] = el.value || "";
          });
        }
        if (!store || !store.saveGd14Answers) return;
        saveBtn.disabled = true;
        store
          .saveGd14Answers(sid, payload)
          .then(function (res) {
            notifyUser("success", (res && res.message) || "Đã cập nhật đối chiếu.");
            renderTable();
            refreshComparePanel();
          })
          .catch(function (err) {
            notifyUser("error", (err && (err.message || err)) || "Không lưu được đối chiếu.");
            saveBtn.disabled = false;
          });
        return;
      }
      var edubitProvBtn =
        e.target && e.target.closest ? e.target.closest("[data-mk-panel-edubit-provision]") : null;
      if (edubitProvBtn) {
        e.preventDefault();
        var eid = edubitProvBtn.getAttribute("data-mk-panel-edubit-provision");
        var ebox = edubitProvBtn.closest ? edubitProvBtn.closest("[data-mk-panel-edubit]") : null;
        if (!ebox || !store || !store.provisionEdubit) return;
        var courseIds = [];
        ebox.querySelectorAll("[data-mk-panel-edubit-course]").forEach(function (box) {
          if (box.checked && !box.disabled) courseIds.push(String(box.value || "").trim());
        });
        var emailEl = ebox.querySelector('[data-mk-panel-edubit="email"]');
        var passEl = ebox.querySelector('[data-mk-panel-edubit="password"]');
        var statusEl = ebox.querySelector("[data-mk-panel-edubit-status]");
        var email = emailEl ? String(emailEl.value || "").trim() : "";
        var password = passEl ? String(passEl.value || "") : "";
        if (!courseIds.length) {
          notifyUser("error", "Chọn ít nhất một khóa chưa có trên tài khoản.");
          return;
        }
        if (!email) {
          notifyUser("error", "Nhập email học viên trước khi cấp TK.");
          return;
        }
        edubitProvBtn.disabled = true;
        store
          .provisionEdubit(eid, { course_ids: courseIds, email: email, password: password })
          .then(function (res) {
            notifyUser("success", (res && res.message) || "Đã cấp TK Edubit.");
            if (statusEl) {
              statusEl.hidden = false;
              statusEl.textContent = (res && res.message) || "Đã cấp TK Edubit.";
            }
            renderTable();
            refreshComparePanel();
          })
          .catch(function (err) {
            var msg = (err && (err.message || err)) || "Cấp TK thất bại.";
            notifyUser("error", msg);
            if (statusEl) {
              statusEl.hidden = false;
              statusEl.textContent = String(msg);
            }
            edubitProvBtn.disabled = false;
          });
        return;
      }
      if (e.target.closest && e.target.closest("[data-mk-gd14-qr-close]")) {
        var qrHost = document.getElementById("mk-contacts-gd14-qr");
        if (qrHost) qrHost.hidden = true;
        return;
      }
      if (e.target.closest && e.target.closest("[data-mk-gd14-qr]")) {
        e.preventDefault();
        openGd14QrModal();
        return;
      }
      var gd14Btn = e.target.closest && e.target.closest("[data-mk-gd14-step][data-contact-id]");
      if (gd14Btn) {
        e.preventDefault();
        e.stopPropagation();
        var step = gd14Btn.getAttribute("data-mk-gd14-step");
        var cid = gd14Btn.getAttribute("data-contact-id");
        var box = gd14Btn.closest ? gd14Btn.closest(".mk-gd14-class") : null;
        var fields = {
          class_date: box && box.querySelector("[data-mk-gd14-date]") ? box.querySelector("[data-mk-gd14-date]").value : "",
          class_time: box && box.querySelector("[data-mk-gd14-time]") ? box.querySelector("[data-mk-gd14-time]").value : "",
          class_place: box && box.querySelector("[data-mk-gd14-place]") ? box.querySelector("[data-mk-gd14-place]").value : "",
        };
        if (!store || !store.gd14ClassStep) return;
        gd14Btn.disabled = true;
        store
          .gd14ClassStep(cid, step, fields)
          .then(function (res) {
            notifyUser("success", (res && res.message) || "Đã cập nhật lớp 990k.");
            renderTable();
            refreshComparePanel();
          })
          .catch(function (err) {
            notifyUser("error", (err && (err.message || err)) || "Không cập nhật được lớp 990k.");
            gd14Btn.disabled = false;
          });
        return;
      }
      var renewBtn =
        e.target && e.target.closest ? e.target.closest("[data-mk-edubit-renew]") : null;
      if (renewBtn) {
        e.preventDefault();
        e.stopPropagation();
        var rid = renewBtn.getAttribute("data-mk-edubit-renew");
        if (!rid) return;
        confirmAction({
          title: "Xác nhận gia hạn Edubit",
          question: "Gia hạn thêm 10 ngày truy cập Edubit?",
          hint: "Mỗi khách hàng được gia hạn tối đa 3 lần và không đặt lại bộ đếm.",
          icon: "fa-clock-o",
          confirmLabel: "Gia hạn",
        }).then(function (confirmed) {
          if (!confirmed) return;
          renewBtn.disabled = true;
          store
            .renewEdubitAccess(rid)
            .then(function (res) {
              notifyUser("success", (res && res.message) || "Đã gia hạn.");
              renderTable();
              refreshComparePanel();
            })
            .catch(function (err) {
              notifyUser("error", (err && err.message) || "Không gia hạn được.");
              renderTable();
            });
        });
        return;
      }
      var editBtn = e.target.closest && e.target.closest(".mk-leads-inline-edit[data-contact-id]");
      if (editBtn) {
        e.preventDefault();
        e.stopPropagation();
        beginInlineEdit(editBtn);
        return;
      }
      var tagsBtn = e.target.closest && e.target.closest(".mk-leads-tags-edit[data-contact-id]");
      if (tagsBtn) {
        e.preventDefault();
        e.stopPropagation();
        var cid = tagsBtn.getAttribute("data-contact-id");
        var contact = getContacts().find(function (c) {
          return String(c.id) === String(cid);
        });
        if (contact) openTagPopover(tagsBtn, contact);
        return;
      }
      if (!e.target.closest || !e.target.closest("#mk-contacts-tag-popover")) {
        closeTagPopover();
      }
      var bulkBtn = e.target.closest && e.target.closest("#mk-contacts-bulk [data-bulk]");
      if (!bulkBtn) return;
      e.preventDefault();
      e.stopPropagation();
      var action = bulkBtn.getAttribute("data-bulk");
      var rows = selectedRows();
      if (!rows.length && action !== "clear") return;
      if (action === "clear") {
        clearSelection();
        return;
      }
      if (action === "export") {
        exportCsv(rows);
        return;
      }
      if (action === "delete") {
        confirmAction({
          title: "Xác nhận xóa khách hàng",
          question: "Xóa " + rows.length + " khách hàng đã chọn?",
          hint: "Dữ liệu đã xóa có thể ảnh hưởng đến các bản ghi liên quan.",
          tone: "danger",
          confirmLabel: "Xóa khách hàng",
        }).then(function (confirmed) {
          if (!confirmed || !store || !store.remove) return;
          Promise.all(
            rows.map(function (c) {
              return store.remove(c.id);
            })
          )
            .then(function () {
              clearSelection();
              renderAll();
              notifyUser("success", "Đã xóa " + rows.length + " khách hàng.");
            })
            .catch(function (err) {
              notifyUser("error", (err && err.message) || "Không xóa được khách hàng.");
            });
        });
        return;
      }
    });

    var segHost = $("mk-contacts-segments");
    if (segHost) {
      segHost.addEventListener("click", function (e) {
        var offlineBtn = e.target.closest("[data-offline-status]");
        if (offlineBtn) {
          state.filters.offlineStatus = offlineBtn.getAttribute("data-offline-status") || ANY;
          if (state.productTab !== "pcth" && state.productTab !== "mqbb" && state.productTab !== "offline") {
            state.productTab = "offline";
          }
          state.activeSegment = null;
          clearNvlSubFilters();
          clearCourseCountFilter();
          state.page = 1;
          renderAll();
          return;
        }
        var onlineBtn = e.target.closest("[data-online-status]");
        if (onlineBtn) {
          state.filters.onlineStatus = onlineBtn.getAttribute("data-online-status") || ANY;
          state.productTab = "online";
          state.activeSegment = null;
          clearNvlSubFilters();
          clearCourseCountFilter();
          state.page = 1;
          renderAll();
          return;
        }
        var courseCountBtn = e.target.closest("[data-course-count]");
        if (courseCountBtn) {
          applyCourseCountFilter(courseCountBtn.getAttribute("data-course-count") || ANY);
          return;
        }
        var nvlBtn = e.target.closest("[data-nvl-sub]");
        if (nvlBtn) {
          applyNvlSubFilter(nvlBtn.getAttribute("data-nvl-sub") || ANY);
          return;
        }
        var ptab = e.target.closest("[data-product-tab]");
        if (ptab) {
          var nextTab = ptab.getAttribute("data-product-tab") || "all";
          if (nextTab !== "offline" && nextTab !== "pcth" && nextTab !== "mqbb") {
            state.filters.offlineStatus = ANY;
          }
          if (nextTab !== "online") {
            state.filters.onlineStatus = ANY;
          }
          if (nextTab !== "nvl") {
            clearNvlSubFilters();
          }
          clearCourseCountFilter();
          state.filters.lane = ANY;
          state.productTab = nextTab;
          state.activeSegment = null;
          state.page = 1;
          renderAll();
          return;
        }
        var btn = e.target.closest("[data-seg]");
        if (!btn) return;
        applySegment(btn.getAttribute("data-seg"));
      });
    }

    var pag = $("mk-contacts-pagination");
    if (pag) {
      pag.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-page]");
        if (!btn || btn.disabled) return;
        if (btn.getAttribute("data-page") === "prev") state.page--;
        else state.page++;
        renderTable();
      });
    }

    var reset = $("mk-contacts-reset");
    if (reset) {
      reset.addEventListener("click", function () {
        state.filters = Object.assign({ classTags: [] }, EMPTY);
        state.activeSegment = null;
        state.productTab = "all";
        state.nvlSubFilter = ANY;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      });
    }

    document.addEventListener("change", function (e) {
      var wrap = e.target && e.target.closest ? e.target.closest(".mk-contacts-offline-attend") : null;
      if (!wrap) return;
      var isClass = e.target.classList.contains("mk-contacts-offline-class");
      var isDt = e.target.classList.contains("mk-contacts-offline-dt");
      if (!isClass && !isDt) return;
      var contactId = wrap.getAttribute("data-contact-id");
      var classSel = wrap.querySelector(".mk-contacts-offline-class");
      var dt = wrap.querySelector(".mk-contacts-offline-dt");
      if (!contactId || !store || !store.saveOfflineAttend) return;
      var classCode = classSel ? classSel.value : "mqbb";
      if (classCode === "__add__" || classCode === "__del__") {
        var prev = wrap.getAttribute("data-class") || "";
        if (classSel && prev) classSel.value = prev;
        var helper = window.app && app.helper ? app.helper : null;
        if (classCode === "__add__") {
          var ask = helper && helper.showPromptBox
            ? helper.showPromptBox({
                title: "Thêm lớp",
                message: "Tên lớp mới",
                placeholder: "Ví dụ: Lớp A",
                confirmLabel: "Thêm",
              })
            : Promise.reject();
          ask.then(function (name) {
            name = String(name || "").trim();
            if (!name) return;
            app.request.post({
              data: { module: "Contacts", action: "ModernApi", mode: "offline_class_add", label: name },
            }).then(function (err, res) {
              if (err || !res || res.success === false) {
                notifyUser("error", (err && err.message) || (res && res.error) || "Không lưu được lớp.");
                return;
              }
              window.MK_OFFLINE_CLASSES = res.offline_classes || [];
              renderAll();
            });
          }, function () {});
          return;
        }
        confirmAction({
          title: "Xóa lớp",
          question: "Xóa lớp này khỏi danh sách dùng chung?",
          hint: "Khách đã chọn lớp này vẫn giữ ngày đã lưu.",
          tone: "danger",
          confirmLabel: "Xóa",
        }).then(function (ok) {
          if (!ok || !prev) return;
          app.request.post({
            data: { module: "Contacts", action: "ModernApi", mode: "offline_class_delete", code: prev },
          }).then(function (err, res) {
            if (err || !res || res.success === false) {
              notifyUser("error", (err && err.message) || (res && res.error) || "Không xóa được lớp.");
              return;
            }
            window.MK_OFFLINE_CLASSES = res.offline_classes || [];
            renderAll();
          });
        });
        return;
      }
      if (isClass) wrap.setAttribute("data-class", classCode);
      if (isClass && dt) {
        dt.value = wrap.getAttribute("data-at-" + classCode) || "";
        return;
      }
      var datetime = dt ? dt.value : "";
      store
        .saveOfflineAttend(contactId, classCode, datetime)
        .then(function (res) {
          var iso = (res && res.datetime) || "";
          wrap.setAttribute("data-at-" + classCode, toDatetimeLocalValue(iso));
          if (store.refresh) return store.refresh();
        })
        .then(function () {
          renderAll();
        })
        .catch(function () {
          notifyUser("error", "Không lưu được thời gian tham gia Offline.");
        });
    });

    document.addEventListener(
      "focusout",
      function (e) {
        if (e.target && e.target.classList && e.target.classList.contains("mk-leads-inline-input") && e.target.getAttribute("data-contact-id")) {
          commitInlineEdit(e.target);
        }
      },
      true
    );
    document.addEventListener("keydown", function (e) {
      if (e.key !== "Enter") return;
      if (e.target && e.target.classList && e.target.classList.contains("mk-leads-inline-input") && e.target.getAttribute("data-contact-id")) {
        e.preventDefault();
        e.target.blur();
      }
    });

    document.addEventListener("mk-contacts-list-field-updated", function (e) {
      if (!e || !e.detail || !store || !store.patchContact) return;
      var detail = e.detail;
      if (!store.patchContact(detail.id, detail.patch || {})) return;
      renderTable();
    });

    if ($("mk-contacts-import-ic")) $("mk-contacts-import-ic").innerHTML = ic("import");
    if ($("mk-contacts-create-ic")) $("mk-contacts-create-ic").innerHTML = ic("plus");
    if ($("mk-contacts-edubit-sync-ic")) $("mk-contacts-edubit-sync-ic").innerHTML = ic("repeat");
    if ($("mk-contacts-search-ic")) $("mk-contacts-search-ic").innerHTML = ic("search");
    if ($("mk-contacts-segments-icon")) $("mk-contacts-segments-icon").innerHTML = ic("filter");
    if ($("mk-contacts-filters-ic")) $("mk-contacts-filters-ic").innerHTML = ic("filter");

    var syncBtn = $("mk-contacts-edubit-sync-btn");
    if (syncBtn) {
      syncBtn.addEventListener("click", function () {
        if (!store || typeof store.syncEdubitAll !== "function") {
          notifyUser("error", "API đồng bộ chưa sẵn sàng.");
          return;
        }
        confirmAction({
          title: "Xác nhận đồng bộ Edubit",
          question: "Đồng bộ tiến độ cho tất cả khách hàng đã cấp tài khoản?",
          hint: "Hệ thống sẽ lấy tiến độ mới nhất từ Edubit. Thao tác có thể mất vài giây.",
          icon: "fa-refresh",
          confirmLabel: "Đồng bộ",
        }).then(function (confirmed) {
          if (!confirmed) return;
          syncBtn.disabled = true;
          var txt = syncBtn.querySelector(".mk-leads-btn__txt");
          var oldTxt = txt ? txt.textContent : "";
          if (txt) txt.textContent = "Đang đồng bộ…";
          store
            .syncEdubitAll(150)
            .then(function (res) {
              notifyUser("success", (res && res.message) || "Đã đồng bộ tiến độ.");
              return store.refresh();
            })
            .then(function () {
              renderAll();
            })
            .catch(function (err) {
              notifyUser("error", (err && err.message) || "Không đồng bộ được tiến độ.");
            })
            .then(function () {
              syncBtn.disabled = false;
              if (txt) txt.textContent = oldTxt || "Đồng bộ tiến độ";
            });
          });
        });
    }
  }

  function init() {
    if (!document.querySelector(".mk-contacts-page")) return;
    if (window.MkLastTouchCall && window.MkLastTouchCall.create) {
      window.__mkContactsLastTouch = window.MkLastTouchCall.create({
        module: "Contacts",
        onLogged: function (recordId, lt, res, callBtn) {
          var rows = getContacts();
          var row = rows.find(function (c) {
            return String(c.id) === String(recordId) || String(c.crmid) === String(recordId);
          });
          if (row && lt) {
            row.lastTouchCalls = lt;
            if (lt.logged && lt.logged.called_at) {
              row.last_touch = lt.logged.called_at;
            }
            if (store && store.patchContact) {
              store.patchContact(row.id, {
                lastTouchCalls: lt,
                last_touch: row.last_touch,
              });
            }
          }
          if (window.__mkContactsLastTouch) {
            window.__mkContactsLastTouch.applyToPanel(callBtn, lt);
          }
          var touchTd = document.querySelector(
            'tr.mk-contacts-row[data-id="' +
              String((row && row.id) || recordId) +
              '"] .mk-leads-td--touch'
          );
          if (touchTd && row) {
            touchTd.innerHTML = window.MkLastTouchCall.lastTouchCallLogHtml(row, esc);
          } else {
            renderTable();
          }
        },
      });
    }
    bindEvents();
    var boot = store && store.bootstrap ? store.bootstrap() : Promise.resolve([]);
    boot.then(function () {
      renderAll();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
