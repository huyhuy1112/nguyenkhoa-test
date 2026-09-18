/* Potentials list — Opp-specific filters & tag columns (BA Excel) */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var ref = window.PotentialsLovableRef;
  var store = window.PotentialsLocalStore;
  var icons = window.LeadsMkIcons;
  var COL_COUNT = 17;
  var edubitCoursesCache = null;

  function t(key, fallback) {
    if (typeof app !== "undefined" && app.vtranslate) {
      var translated = app.vtranslate(key);
      if (translated && translated !== key) return translated;
    }
    return fallback || key;
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
      '<select class="mk-leads-region-select mk-leads-biz-select" data-field="business_model" data-opp-id="' +
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

  /** Phân nhóm theo tag/BA của Cơ hội — UI giống Leads (segment-btn) */
  function getPresetSegments() {
    return [
      { id: "prospecting", name: pick("Tiềm năng", "Prospecting"), filters: { sales_stage: "Prospecting" } },
      { id: "confirmed", name: pick("Xác nhận tham gia", "Confirmed"), filters: { confirm: "xac_nhan_tham_gia" } },
      { id: "first_buy", name: pick("Mua lần đầu", "First purchase"), filters: { material: "mua_lan_dau" } },
      { id: "franchise", name: pick("Nhượng quyền", "Franchise"), filters: { franchise: "nhuong_quyen" } },
      { id: "deposit", name: pick("Đã ký quỹ", "Deposited"), filters: { franchise: "da_ky_quy" } },
    ];
  }

  var EMPTY = {
    search: "",
    sales_stage: ANY,
    order_category: ANY,
    area: ANY,
    source: ANY,
    customer: ANY,
    classTag: ANY,
    material: ANY,
    franchise: ANY,
    confirm: ANY,
    tier: ANY,
    anyTag: ANY,
    owner: ANY,
    da_cap_bang: ANY,
    progress: ANY,
    staleOnly: false,
  };

  var state = {
    filters: Object.assign({}, EMPTY),
    sortKey: "last_touch",
    sortDir: "desc",
    page: 1,
    filtersOpen: false,
    activeSegment: null,
    selected: {},
  };

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
    var str = String(s == null ? "" : s);
    if (!str || str.indexOf("&") < 0) return str;
    var el = document.createElement("textarea");
    el.innerHTML = str;
    return el.value;
  }

  function esc(s) {
    return decodeHtml(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function getOpps() {
    return store ? store.getOpportunities() : [];
  }

  function detailUrl(id) {
    return "index.php?module=Potentials&view=Detail&record=" + encodeURIComponent(id) + "&app=SALES";
  }

  function stageLabel(stage) {
    var map = {
      Prospecting: "Tiềm năng",
      Qualification: "Chất lượng",
      "Needs Analysis": "Phân tích nhu cầu",
      "Proposal/Price Quote": "Đề nghị/Báo giá",
      "Negotiation/Review": "Đàm phán/Xem xét",
      "Closed Won": "Hoàn thành",
      "Closed Lost": "Không thành công",
    };
    return map[stage] || stage || "—";
  }

  function categoryLabel(cat) {
    if (cat === "Internal") return "Nội bộ";
    if (cat === "Project") return "Dự án";
    return cat || "";
  }

  function formatMoney(n) {
    var v = Number(n) || 0;
    try {
      if (window.MkCurrency && MkCurrency.format) return MkCurrency.format(v);
    } catch (e) { /* ignore */ }
    return v.toLocaleString("vi-VN") + " đ";
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

  function stagePillClass(stage) {
    var map = {
      "Closed Won": "mk-pill--emerald",
      "Closed Lost": "mk-pill--rose",
      Prospecting: "mk-pill--purple",
      Qualification: "mk-pill--blue",
      "Needs Analysis": "mk-pill--cyan",
      "Proposal/Price Quote": "mk-pill--indigo",
      "Negotiation/Review": "mk-pill--amber",
    };
    return map[stage] || "mk-pill--indigo";
  }

  function isStale(row) {
    var iso = row.last_touch || row.modifiedtime || row.closingdate;
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

  function filterOpps(rows) {
    var f = state.filters;
    var q = (f.search || "").toLowerCase().trim();
    return rows.filter(function (o) {
      var cats = categorize(o.tags);
      if (q) {
        var hay = [o.name, o.account, o.contact, o.owner, o.phone, o.address, o.notes, (o.tags || []).join(" ")]
          .join(" ")
          .toLowerCase();
        if (hay.indexOf(q) < 0) return false;
      }
      if (f.sales_stage !== ANY && o.sales_stage !== f.sales_stage) return false;
      if (f.order_category !== ANY && o.order_category !== f.order_category) return false;
      if (f.area !== ANY && (!cats.area || ref.normalizeTag(cats.area) !== f.area)) return false;
      if (f.source !== ANY && (!cats.source || ref.normalizeTag(cats.source) !== f.source)) return false;
      if (f.customer !== ANY && (!cats.customer || ref.normalizeTag(cats.customer) !== f.customer)) return false;
      if (f.classTag !== ANY && (!cats.classTag || ref.normalizeTag(cats.classTag) !== f.classTag)) return false;
      if (f.material !== ANY && (!cats.material || ref.normalizeTag(cats.material) !== f.material)) return false;
      if (f.franchise !== ANY && (!cats.franchise || ref.normalizeTag(cats.franchise) !== f.franchise)) return false;
      if (f.confirm !== ANY && (!cats.confirm || ref.normalizeTag(cats.confirm) !== f.confirm)) return false;
      if (f.tier !== ANY && (!cats.tier || ref.normalizeTag(cats.tier) !== f.tier)) return false;
      if (f.anyTag !== ANY && !hasNormalizedTag(o.tags, f.anyTag)) return false;
      if (f.staleOnly && !isStale(o)) return false;
      if (f.owner !== ANY && o.owner !== f.owner) return false;
      if (f.da_cap_bang !== ANY) {
        var bang = String(o.da_cap_bang || "Chưa cấp").trim();
        var wantIssued = f.da_cap_bang === "da_cap";
        var isIssued = /đã\s*cấp/i.test(bang) && !/chưa/i.test(bang);
        if (wantIssued !== isIssued) return false;
      }
      if (f.progress !== ANY) {
        var pct =
          o.edubit_progress_pct != null && o.edubit_progress_pct !== ""
            ? Number(o.edubit_progress_pct)
            : null;
        if (f.progress === "none" && pct != null) return false;
        if (f.progress === "lt50" && !(pct != null && pct < 50)) return false;
        if (f.progress === "50_79" && !(pct != null && pct >= 50 && pct < 80)) return false;
        if (f.progress === "80_99" && !(pct != null && pct >= 80 && pct < 100)) return false;
        if (f.progress === "done" && !(pct != null && pct >= 100)) return false;
      }
      return true;
    });
  }

  function formatDateTimeFull(iso) {
    if (!iso) return "";
    var d = new Date(iso);
    if (isNaN(d.getTime())) {
      var m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
      if (!m) return String(iso);
      return (
        m[3] +
        "/" +
        m[2] +
        "/" +
        m[1] +
        (m[4] ? " " + m[4] + ":" + m[5] + ":" + (m[6] || "00") : "")
      );
    }
    function pad(n) {
      return n < 10 ? "0" + n : String(n);
    }
    return (
      pad(d.getDate()) +
      "/" +
      pad(d.getMonth() + 1) +
      "/" +
      d.getFullYear() +
      " " +
      pad(d.getHours()) +
      ":" +
      pad(d.getMinutes()) +
      ":" +
      pad(d.getSeconds())
    );
  }

  function regionLabel(o, cats) {
    var area = cats && cats.area ? cats.area : "";
    if (area) {
      var key = ref && ref.normalizeTag ? ref.normalizeTag(area) : String(area);
      if (/^kv([123])$/i.test(key)) return "Khu vực " + RegExp.$1;
      return tagMeta(area).label || area;
    }
    var dist = String(o.district || "").trim();
    if (/khu\s*vực\s*([123])/iu.test(dist)) return "Khu vực " + RegExp.$1;
    return dist;
  }

  function regionKeyOf(o, cats) {
    var area = cats && cats.area ? cats.area : "";
    var key = ref && ref.normalizeTag ? ref.normalizeTag(area) : String(area || "");
    if (/^kv[123]$/i.test(key)) return key.toLowerCase();
    var dist = String((o && o.district) || "").trim();
    var m = dist.match(/khu\s*v[ựuùúủũụ]\s*c\s*([123])/i);
    if (m) return "kv" + m[1];
    return "";
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
      '" data-opp-id="' +
      esc(recordId) +
      '" title="Nhấn để sửa">' +
      display +
      "</button>"
    );
  }

  function regionSelectHtml(recordId, regionKey) {
    var opts = [
      ["", "— Chọn khu vực —"],
      ["kv1", "Khu vực 1"],
      ["kv2", "Khu vực 2"],
      ["kv3", "Khu vực 3"],
    ];
    return (
      '<select class="mk-leads-region-select" data-field="region" data-opp-id="' +
      esc(recordId) +
      '" title="Chọn khu vực">' +
      opts
        .map(function (o) {
          return (
            '<option value="' +
            esc(o[0]) +
            '"' +
            (regionKey === o[0] ? " selected" : "") +
            ">" +
            esc(o[1]) +
            "</option>"
          );
        })
        .join("") +
      "</select>"
    );
  }

  function beginInlineEdit(btn) {
    if (!btn || !btn.getAttribute) return;
    var field = btn.getAttribute("data-field");
    var recordId = btn.getAttribute("data-opp-id");
    var current = btn.textContent.trim();
    if (current === "—" || current === "Nhập SĐT" || current === "Nhập địa chỉ") current = "";
    var input = document.createElement("input");
    input.type = field === "phone" ? "tel" : "text";
    input.className = "mk-leads-inline-input";
    input.value = current;
    input.setAttribute("data-field", field);
    input.setAttribute("data-opp-id", recordId);
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
    if (!input || !input.getAttribute || !store) {
      renderTable();
      return;
    }
    var field = input.getAttribute("data-field");
    var recordId = input.getAttribute("data-opp-id");
    var val = String(input.value || "").trim();
    if (field === "phone") {
      val = val.replace(/\s+/g, "");
      if (val && !/^\d{10}$/.test(val)) {
        window.alert("Số điện thoại phải đủ 10 số.");
        renderTable();
        return;
      }
      input.disabled = true;
      store
        .saveInlinePhone(recordId, val)
        .then(function () {
          renderTable();
        })
        .catch(function (err) {
          window.alert((err && err.message) || "Không lưu được SĐT.");
          renderTable();
        });
      return;
    }
    if (field === "address") {
      var opp = getOpps().find(function (o) {
        return String(o.id) === String(recordId) || String(o.crmid || "") === String(recordId);
      });
      var cats = categorize((opp && opp.tags) || []);
      var regionKey = regionKeyOf(opp || {}, cats);
      input.disabled = true;
      store
        .saveInlineLocation(recordId, regionKey, val)
        .then(function () {
          renderTable();
        })
        .catch(function (err) {
          window.alert((err && err.message) || "Không lưu được địa chỉ.");
          renderTable();
        });
    }
  }

  function commitRegionChange(select) {
    if (!select || !store) return;
    var recordId = select.getAttribute("data-opp-id");
    var regionKey = select.value || "";
    var opp = getOpps().find(function (o) {
      return String(o.id) === String(recordId) || String(o.crmid || "") === String(recordId);
    });
    var address = opp ? String(opp.address || "").trim() : "";
    select.disabled = true;
    store
      .saveInlineLocation(recordId, regionKey, address)
      .then(function () {
        renderTable();
      })
      .catch(function (err) {
        window.alert((err && err.message) || "Không lưu được khu vực.");
        renderTable();
      });
  }

  function commitBusinessModelChange(select) {
    if (!select || !store || !store.saveInlineBusinessModel) return;
    var recordId = select.getAttribute("data-opp-id");
    if (!recordId) return;
    select.disabled = true;
    store
      .saveInlineBusinessModel(recordId, select.value || "")
      .then(function () {
        renderTable();
      })
      .catch(function (err) {
        window.alert((err && err.message) || "Không lưu được mô hình kinh doanh.");
        renderTable();
      });
  }

  function stackedTagsHtml(cats) {
    var parts = [];
    [cats.classTag, cats.material, cats.franchise, cats.tier]
      .concat(cats.credentials || [])
      .concat(cats.custom || [])
      .forEach(function (tg) {
        if (tg) parts.push(tagBadgeHtml(tg));
      });
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

  function closeTagPopover() {
    var old = document.getElementById("mk-opps-tag-popover");
    if (old && old.parentNode) old.parentNode.removeChild(old);
  }

  function openTagPopover(anchor, opp) {
    closeTagPopover();
    if (!opp || !store) return;
    var catalog = ref && ref.getCreateTagCatalog ? ref.getCreateTagCatalog() : [];
    var selected = {};
    (opp.tags || []).forEach(function (tg) {
      var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
      if (k) selected[k] = true;
    });
    var pop = document.createElement("div");
    pop.id = "mk-opps-tag-popover";
    pop.className = "mk-leads-tag-popover";
    pop.setAttribute("data-opp-id", String(opp.id));
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
          '<div class="mk-leads-tag-popover__group">' +
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
        var group = chip.closest(".mk-leads-tag-popover__group");
        var turningOn = !chip.classList.contains("is-on");
        if (group && turningOn) {
          group.querySelectorAll(".mk-leads-tag-chip.is-on").forEach(function (el) {
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
          nextTags.push(el.getAttribute("data-tag"));
        });
        var catalogKeys = ref && ref.getCreateTagKeys ? ref.getCreateTagKeys() : [];
        (opp.tags || []).forEach(function (tg) {
          var k = ref && ref.normalizeTag ? ref.normalizeTag(tg) : String(tg || "");
          if (!k) return;
          if (catalogKeys.indexOf(k) < 0 && nextTags.indexOf(k) < 0) nextTags.push(k);
        });
        var saveBtn = e.target.closest("[data-tag-save]");
        if (saveBtn) saveBtn.disabled = true;
        var saveFn = store.saveTags
          ? store.saveTags(opp.crmid || opp.id, nextTags)
          : Promise.reject(new Error("saveTags unavailable"));
        saveFn
          .then(function () {
            closeTagPopover();
            renderAll();
          })
          .catch(function () {
            window.alert("Không lưu được thẻ.");
            if (saveBtn) saveBtn.disabled = false;
          });
      }
    });
  }

  function notesCell(text) {
    var s = String(text || "").trim();
    if (!s) return '<span class="mk-leads-muted">—</span>';
    var short = s.length > 80 ? s.slice(0, 80) + "…" : s;
    return (
      '<span class="mk-leads-notes-cell" title="' +
      esc(s) +
      '">' +
      esc(short) +
      "</span>"
    );
  }

  function canOfflineCheckin(o) {
    var st = String((o && o.offline_status) || "");
    return (
      st === "offline_da_xac_nhan_lich" ||
      st === "offline_hen_lich_lai" ||
      st === "offline_khong_tham_gia" ||
      st === "offline_da_tham_gia" ||
      st === "offline_ngung_cskh_tam" ||
      st === "offline_ngung_cskh"
    );
  }

  /** Chỉ cho điểm danh khi chưa ghi nhận kết quả lớp. */
  function canEditAttendance(o) {
    var st = String((o && o.offline_status) || "");
    return st === "offline_da_xac_nhan_lich" || st === "offline_hen_lich_lai";
  }

  function canRescheduleAttendance(o) {
    var st = String((o && o.offline_status) || "");
    return st === "offline_khong_tham_gia" || st === "offline_ngung_cskh_tam";
  }

  function ensureEdubitCourses(cb) {
    if (edubitCoursesCache) {
      if (cb) cb(edubitCoursesCache);
      return;
    }
    if (!(window.app && app.request && app.request.post)) {
      edubitCoursesCache = [];
      if (cb) cb(edubitCoursesCache);
      return;
    }
    app.request
      .post({
        data: { module: "Potentials", action: "ModernApi", mode: "online_edubit_courses" },
      })
      .then(function (err, res) {
        edubitCoursesCache =
          !err && res && Array.isArray(res.courses) ? res.courses : [];
        if (cb) cb(edubitCoursesCache);
      });
  }

  function onlineEdubitOppCell(o) {
    var oid = esc(String(o.crmid || o.id || ""));
    var email = String(o.edubit_email || o.email || "").trim();
    var courses = edubitCoursesCache || [];
    if (!edubitCoursesCache) {
      ensureEdubitCourses(function () {
        renderTable();
      });
    }
    var opts =
      '<option value="">— chọn khóa —</option>' +
      courses
        .map(function (c) {
          var id = String((c && (c.id || c.course_id)) || "");
          var label = String((c && (c.label || c.name)) || id);
          var sel =
            o.edubit_course_id && String(o.edubit_course_id) === id ? " selected" : "";
          return (
            '<option value="' + esc(id) + '"' + sel + ">" + esc(label) + "</option>"
          );
        })
        .join("");
    if (o.edubit_user_id) {
      var pct =
        o.edubit_progress_pct != null && o.edubit_progress_pct !== ""
          ? String(o.edubit_progress_pct) + "%"
          : "—";
      return (
        '<div class="mk-opps-edubit" data-opp-id="' +
        oid +
        '">' +
        '<div class="mk-opps-checkin__status"><span class="mk-opps-checkin__label">Đã cấp TK Edubit</span></div>' +
        '<div class="mk-opps-checkin__hint">user=' +
        esc(String(o.edubit_user_id)) +
        " · " +
        esc(pct) +
        "</div></div>"
      );
    }
    return (
      '<div class="mk-opps-edubit" data-opp-id="' +
      oid +
      '">' +
      '<div class="mk-opps-checkin__status"><span class="mk-opps-checkin__label">Edubit — Cấp TK</span></div>' +
      (o.edubit_last_error
        ? '<div class="mk-opps-checkin__hint" style="color:#b91c1c">' +
          esc(String(o.edubit_last_error)) +
          "</div>"
        : "") +
      '<input type="email" class="inputElement mk-opps-edubit__email" data-mk-opp-edubit="email" data-opp-id="' +
      oid +
      '" value="' +
      esc(email) +
      '" placeholder="email học viên" />' +
      '<select class="inputElement mk-opps-edubit__course" data-mk-opp-edubit="course_id" data-opp-id="' +
      oid +
      '">' +
      opts +
      "</select>" +
      '<div class="mk-opps-checkin__actions">' +
      '<button type="button" class="mk-opps-checkin__btn mk-opps-checkin__btn--ok" data-mk-opp-edubit-action="provision" data-opp-id="' +
      oid +
      '">Cấp tài khoản</button>' +
      "</div>" +
      '<div class="mk-opps-checkin__hint">Email có sẵn thì chỉ bấm Cấp TK · xong sẽ xuống Khách hàng</div>' +
      "</div>"
    );
  }

  function submitOppEdubitProvision(btn) {
    var oid = btn && btn.getAttribute("data-opp-id");
    if (!oid || !(window.app && app.request && app.request.post)) return;
    var wrap = btn.closest ? btn.closest(".mk-opps-edubit") : null;
    var emailEl = wrap
      ? wrap.querySelector('[data-mk-opp-edubit="email"]')
      : null;
    var courseEl = wrap
      ? wrap.querySelector('[data-mk-opp-edubit="course_id"]')
      : null;
    var email = emailEl ? String(emailEl.value || "").trim() : "";
    var courseId = courseEl ? String(courseEl.value || "").trim() : "";
    if (!courseId) {
      if (window.app && app.helper && app.helper.showErrorNotification) {
        app.helper.showErrorNotification({ message: "Phải chọn khóa học — không có mặc định." });
      }
      return;
    }
    if (!email) {
      if (window.app && app.helper && app.helper.showErrorNotification) {
        app.helper.showErrorNotification({ message: "Nhập email học viên trước khi cấp TK." });
      }
      return;
    }
    var opp = getOpps().find(function (o) {
      return String(o.crmid || o.id) === String(oid);
    });
    btn.disabled = true;
    if (window.app && app.helper && app.helper.showProgress) {
      app.helper.showProgress();
    }
    app.request
      .post({
        data: {
          module: "Potentials",
          action: "ModernApi",
          mode: "online_edubit_provision",
          record: oid,
          id: oid,
          payload: JSON.stringify({
            course_id: courseId,
            email: email,
            name: (opp && (opp.contact || opp.name)) || "",
            phone: (opp && opp.phone) || "",
          }),
        },
      })
      .then(function (err, res) {
        btn.disabled = false;
        if (window.app && app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        if (err || !res || res.success === false) {
          var msg =
            (res && (res.error || res.message)) ||
            (err && (err.message || err)) ||
            "Cấp TK Edubit thất bại.";
          if (window.app && app.helper && app.helper.showErrorNotification) {
            app.helper.showErrorNotification({ message: String(msg) });
          }
          return;
        }
        var okMsg =
          (res && res.message) ||
          "Đã cấp TK Edubit và chuyển xuống Khách hàng.";
        if (window.app && app.helper && app.helper.showSuccessNotification) {
          app.helper.showSuccessNotification({ message: okMsg });
        }
        if (store && store.removeFromList) {
          store.removeFromList(String(oid));
          var alt = opp && opp.id ? String(opp.id) : "";
          if (alt && alt !== String(oid)) store.removeFromList(alt);
        }
        delete state.selected[String(oid)];
        renderAll();
        if (res.list_url || (res.customer && res.customer.list_url)) {
          // stay on Opp list; user sees toast + row removed
        }
      });
  }

  function offlineNoshowCountersHtml(o) {
    var r3 = Number(o.offline_r3_class) || 0;
    var miss = Number(o.offline_post_noshow_miss) || 0;
    var st = String(o.offline_status || "");
    var bits = [];
    if (st === "offline_khong_tham_gia" || st === "offline_ngung_cskh" || r3 > 0) {
      bits.push("No-show " + Math.min(r3, 3) + "/3");
    }
    if (
      miss > 0 ||
      st === "offline_khong_tham_gia" ||
      st === "offline_da_xac_nhan_lich" ||
      st === "offline_hen_lich_lai"
    ) {
      bits.push("Không gọi được " + Math.min(miss, 3) + "/3");
    }
    if (!bits.length) return "";
    return (
      '<div class="mk-opps-checkin__counters" title="Không tham gia tối đa 3 lần; Không gọi được 3 lần (sau XN lịch) → dừng tạm (đặt lịch lại 3 lần)">' +
      esc(bits.join(" · ")) +
      "</div>"
    );
  }

  function isAdminUser() {
    return !!window.MK_OPPS_IS_ADMIN;
  }

  function notifyOk(msg) {
    if (window.app && app.helper && app.helper.showSuccessNotification) {
      app.helper.showSuccessNotification({ message: msg });
      return;
    }
    if (window.app && app.helper && app.helper.showAlertNotification) {
      app.helper.showAlertNotification({ message: msg });
      return;
    }
  }

  function notifyErr(msg) {
    if (window.app && app.helper && app.helper.showErrorNotification) {
      app.helper.showErrorNotification({ message: msg });
      return;
    }
    if (window.app && app.helper && app.helper.showAlertNotification) {
      app.helper.showAlertNotification({ message: msg });
      return;
    }
    window.alert(msg);
  }

  function offlineClassMetaHtml(o) {
    var classDate = o.offline_class_date ? String(o.offline_class_date) : "";
    var classTime = o.offline_class_time ? String(o.offline_class_time) : "";
    var classPlace = o.offline_class_place ? String(o.offline_class_place).trim() : "";
    var zaloId = o.zalo_user_id ? String(o.zalo_user_id).trim() : "";
    var willCome = Number(o.offline_preclass_confirm) === 1;
    var parts = [];
    if (classDate || classTime) {
      parts.push(
        '<span class="mk-opps-checkin__meta-item">' +
          '<span class="mk-opps-checkin__meta-k">Lớp</span> ' +
          esc([classDate, classTime].filter(Boolean).join(" · ")) +
          "</span>"
      );
    }
    if (classPlace) {
      parts.push(
        '<span class="mk-opps-checkin__meta-item" title="' +
          esc(classPlace) +
          '">' +
          '<span class="mk-opps-checkin__meta-k">Địa điểm</span> ' +
          esc(classPlace.length > 42 ? classPlace.slice(0, 42) + "…" : classPlace) +
          "</span>"
      );
    }
    parts.push(
      '<span class="mk-opps-checkin__meta-item' +
        (willCome ? " is-yes" : " is-no") +
        '">' +
        '<span class="mk-opps-checkin__meta-k">Sẽ đến</span> ' +
        (willCome ? "Đã XN" : "Chưa XN") +
        "</span>"
    );
    if (zaloId) {
      var zaloShort = zaloId.length > 14 ? zaloId.slice(0, 10) + "…" : zaloId;
      parts.push(
        '<span class="mk-opps-checkin__meta-item" title="' +
          esc(zaloId) +
          '">' +
          '<span class="mk-opps-checkin__meta-k">OA id</span> ' +
          esc(zaloShort) +
          "</span>"
      );
    } else {
      parts.push(
        '<span class="mk-opps-checkin__meta-item is-muted">' +
          '<span class="mk-opps-checkin__meta-k">OA id</span> —</span>'
      );
    }
    return '<div class="mk-opps-checkin__meta">' + parts.join("") + "</div>";
  }

  function offlineCheckinCell(o) {
    if (!canOfflineCheckin(o)) {
      return '<span class="mk-leads-muted">—</span>';
    }
    var st = String((o && o.offline_status) || "");
    var label = String(o.offline_status_label || o.offline_status || "").trim();
    var checkedAt = o.offline_checked_in_at
      ? formatDateTimeFull(o.offline_checked_in_at)
      : "";
    var classDate = o.offline_class_date ? String(o.offline_class_date) : "";
    var editable = canEditAttendance(o);
    var reschedulable = canRescheduleAttendance(o);
    var html =
      '<div class="mk-opps-checkin" data-opp-id="' +
      esc(o.id) +
      '">' +
      '<div class="mk-opps-checkin__status">' +
      '<span class="mk-opps-checkin__label">' +
      esc(label || "Offline") +
      "</span>" +
      offlineNoshowCountersHtml(o) +
      offlineClassMetaHtml(o) +
      (checkedAt
        ? '<span class="mk-opps-checkin__at">Điểm danh: ' + esc(checkedAt) + "</span>"
        : "") +
      "</div>";
    if (st === "offline_ngung_cskh") {
      html += '<div class="mk-opps-checkin__hint">Đã ngưng CSKH (đủ 3 lần không tham gia)</div>';
    } else if (isAdminUser() && editable) {
      html +=
        '<div class="mk-opps-checkin__actions">' +
        '<button type="button" class="mk-opps-checkin__btn mk-opps-checkin__btn--ok" data-mk-opp-checkin="da_tham_gia" data-opp-id="' +
        esc(o.id) +
        '">Đã tham gia</button>' +
        '<button type="button" class="mk-opps-checkin__btn mk-opps-checkin__btn--no" data-mk-opp-checkin="khong_tham_gia" data-opp-id="' +
        esc(o.id) +
        '">Không tham gia</button>' +
        "</div>";
    } else if (isAdminUser() && reschedulable) {
      var hint =
        st === "offline_ngung_cskh_tam"
          ? "Dừng tạm — chọn ngày rồi chốt lịch mới (được 3 lần no-show mới)"
          : "Không đến — chọn ngày rồi chốt lịch mới";
      html +=
        '<div class="mk-opps-checkin__reschedule">' +
        '<input type="date" class="mk-opps-checkin__date-input" data-mk-opp-reschedule-date="' +
        esc(o.id) +
        '" value="' +
        esc(classDate) +
        '" title="Ngày học mới" />' +
        '<button type="button" class="mk-opps-checkin__btn mk-opps-checkin__btn--ok" data-mk-opp-reschedule="chot_lich_moi" data-opp-id="' +
        esc(o.id) +
        '">Chốt lịch mới</button>' +
        '<button type="button" class="mk-opps-checkin__btn mk-opps-checkin__btn--no" data-mk-opp-reschedule="hen_lich_lai" data-opp-id="' +
        esc(o.id) +
        '">Hẹn lịch lại</button>' +
        "</div>" +
        '<div class="mk-opps-checkin__hint">' +
        esc(hint) +
        "</div>";
    } else if (isAdminUser() && !editable) {
      html += '<div class="mk-opps-checkin__hint">Đã ghi nhận · khóa chọn lại</div>';
      if (st === "offline_da_tham_gia") {
        var oaOk = !!(o.zalo_user_id && String(o.zalo_user_id).trim());
        html +=
          '<div class="mk-opps-checkin__actions">' +
          '<button type="button" class="mk-opps-checkin__btn mk-opps-checkin__btn--oa" data-mk-opp-oa-qr="' +
          esc(o.id) +
          '">' +
          (oaOk ? "Xem QR OA · đã gắn" : "QR OA tại quầy") +
          "</button>" +
          "</div>";
        if (o.offline_oa_scan_note) {
          html +=
            '<div class="mk-opps-checkin__hint">' +
            esc(String(o.offline_oa_scan_note)) +
            "</div>";
        }
      }
    } else {
      html += '<div class="mk-opps-checkin__hint">Chỉ Admin ghi nhận</div>';
    }
    return html + "</div>";
  }

  function submitOfflineReschedule(action, btn) {
    var oid = btn && btn.getAttribute ? btn.getAttribute("data-opp-id") : "";
    if (!oid || !store || !store.offlineReschedule) return;
    var wrap = btn.closest ? btn.closest(".mk-opps-checkin") : null;
    var dateEl = wrap
      ? wrap.querySelector('[data-mk-opp-reschedule-date="' + oid + '"]')
      : null;
    var classDate = dateEl ? String(dateEl.value || "").trim() : "";
    if (action === "chot_lich_moi" && !classDate) {
      notifyErr("Chọn ngày học mới trước khi chốt.");
      return;
    }
    var buttons = wrap ? wrap.querySelectorAll("[data-mk-opp-reschedule]") : [btn];
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].disabled = true;
    }
    store
      .offlineReschedule(oid, action, { class_date: classDate })
      .then(function (res) {
        if (res && res.drop) {
          notifyOk("Đã đủ R3 → Ngưng CSKH Offline.");
        } else if (action === "chot_lich_moi") {
          notifyOk("Đã chốt lịch mới — mở lại điểm danh khi đến lớp.");
        } else {
          notifyOk("Đã ghi nhận hẹn lịch lại.");
        }
        renderAll();
      })
      .catch(function (err) {
        var msg =
          (err && err.message) ||
          (typeof err === "string" ? err : "") ||
          "Không xếp lịch lại được.";
        notifyErr(msg);
        for (var j = 0; j < buttons.length; j++) {
          buttons[j].disabled = false;
        }
      });
  }

  function submitOfflineCheckin(action, btn) {
    var oid = btn && btn.getAttribute ? btn.getAttribute("data-opp-id") : "";
    if (!oid || !store || !store.offlineCheckin) return;
    var wrap = btn.closest ? btn.closest(".mk-opps-checkin") : null;
    var buttons = wrap ? wrap.querySelectorAll("[data-mk-opp-checkin]") : [btn];
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].disabled = true;
    }
    store
      .offlineCheckin(oid, action)
      .then(function (res) {
        if (res && res.drop) {
          notifyOk("Đã đủ 3 lần không tham gia → Ngưng CSKH Offline.");
        } else if (action === "da_tham_gia") {
          notifyOk("Đã ghi nhận tham gia lớp — mở QR OA cho khách quét.");
          if (res && res.oa_qr) {
            openOaQrModal(oid, res.oa_qr);
          } else {
            openOaQrModal(oid, null);
          }
        } else {
          var r3 = res && res.lead ? Number(res.lead.offline_r3_class) || 0 : 0;
          notifyOk(
            "Đã ghi nhận không tham gia" + (r3 ? " · No-show " + r3 + "/3" : "") + "."
          );
        }
        renderAll();
      })
      .catch(function (err) {
        var msg =
          (err && err.message) ||
          (typeof err === "string" ? err : "") ||
          "Không ghi nhận được.";
        notifyErr(msg);
        for (var j = 0; j < buttons.length; j++) {
          buttons[j].disabled = false;
        }
      });
  }

  var oaQrPollTimer = null;

  function closeOaQrModal() {
    if (oaQrPollTimer) {
      clearInterval(oaQrPollTimer);
      oaQrPollTimer = null;
    }
    var m = document.getElementById("mk-opps-oa-qr-modal");
    if (m) m.remove();
  }

  function paintOaQrModal(host, oid, data) {
    if (!host || !data) return;
    var scanned = !!data.scanned || !!(data.zalo_user_id && String(data.zalo_user_id).trim());
    var follow = data.follow_url ? String(data.follow_url) : "";
    var qrImg = data.qr_image_url ? String(data.qr_image_url) : "";
    var phone = data.phone ? String(data.phone) : "";
    var note = data.note ? String(data.note) : "";
    var tips = Array.isArray(data.instructions) ? data.instructions : [];
    var statusHtml = scanned
      ? '<p class="mk-opps-oa-qr__ok">Đã gắn OA id: <code>' +
        esc(String(data.zalo_user_id)) +
        "</code></p>"
      : '<p class="mk-opps-oa-qr__wait">Chưa gắn OA id — nhờ khách quét <strong>QR form</strong> rồi điền đúng SĐT đăng ký' +
        (phone ? " (<strong>" + esc(phone) + "</strong>)" : "") +
        ".</p>";
    var qrBlock = qrImg
      ? '<img class="mk-opps-oa-qr__img" src="' +
        esc(qrImg) +
        '" alt="QR form Zalo OA" width="240" height="240" />'
      : '<p class="mk-opps-oa-qr__warn">Chưa có ảnh QR form. Kiểm tra file offline-oa-form-qr.png trên server.</p>';
    var tipsHtml = tips.length
      ? '<ul class="mk-opps-oa-qr__tips">' +
        tips
          .map(function (t) {
            return "<li>" + esc(String(t)) + "</li>";
          })
          .join("") +
        "</ul>"
      : "";
    host.innerHTML =
      '<div class="mk-opps-oa-qr__dialog" role="dialog" aria-modal="true" aria-label="QR form Zalo OA">' +
      '<header class="mk-opps-oa-qr__head">' +
      "<h3>Quét QR form điền thông tin</h3>" +
      '<button type="button" class="mk-opps-oa-qr__close" data-mk-oa-qr-close aria-label="Đóng">×</button>' +
      "</header>" +
      '<div class="mk-opps-oa-qr__body">' +
      '<div class="mk-opps-oa-qr__left">' +
      qrBlock +
      (follow
        ? '<a class="mk-opps-oa-qr__link" href="' +
          esc(follow) +
          '" target="_blank" rel="noopener">Mở link form / OA</a>'
        : '<p class="mk-opps-oa-qr__link-hint">Quét QR bên trái để mở form điền thông tin</p>') +
      "</div>" +
      '<div class="mk-opps-oa-qr__right">' +
      statusHtml +
      '<p class="mk-opps-oa-qr__howto-title">Cách dùng 3 nút</p>' +
      tipsHtml +
      (note ? '<p class="mk-opps-oa-qr__note">Ghi chú: ' + esc(note) + "</p>" : "") +
      '<div class="mk-opps-oa-qr__actions">' +
      '<button type="button" class="mk-opps-checkin__btn mk-opps-checkin__btn--ok" data-mk-oa-qr-refresh="' +
      esc(oid) +
      '">Làm mới trạng thái</button>' +
      '<button type="button" class="mk-opps-checkin__btn" data-mk-oa-qr-note="no_zalo" data-opp-id="' +
      esc(oid) +
      '">Không dùng Zalo</button>' +
      '<button type="button" class="mk-opps-checkin__btn mk-opps-checkin__btn--no" data-mk-oa-qr-note="refused" data-opp-id="' +
      esc(oid) +
      '">Không chịu quét</button>' +
      "</div>" +
      "</div>" +
      "</div>" +
      "</div>";
  }

  function openOaQrModal(oid, seed) {
    closeOaQrModal();
    var backdrop = document.createElement("div");
    backdrop.id = "mk-opps-oa-qr-modal";
    backdrop.className = "mk-opps-oa-qr";
    document.body.appendChild(backdrop);
    paintOaQrModal(backdrop, oid, seed || { scanned: false, instructions: [] });
    if (!seed || !seed.follow_url) {
      refreshOaQrModal(oid);
    }
    if (oaQrPollTimer) clearInterval(oaQrPollTimer);
    oaQrPollTimer = setInterval(function () {
      refreshOaQrModal(oid, true);
    }, 8000);
  }

  function refreshOaQrModal(oid, quiet) {
    if (!store || !store.offlineOaQr) return;
    store
      .offlineOaQr(oid)
      .then(function (res) {
        var host = document.getElementById("mk-opps-oa-qr-modal");
        if (!host) return;
        paintOaQrModal(host, oid, res || {});
        if (res && res.scanned && !quiet) {
          notifyOk("Đã gắn OA id cho khách.");
        }
        if (res && res.zalo_user_id) {
          rootPatchOaOnOpp(oid, res);
        }
      })
      .catch(function (err) {
        if (quiet) return;
        notifyErr((err && err.message) || "Không làm mới được QR OA.");
      });
  }

  function rootPatchOaOnOpp(oid, res) {
    if (!store || !store.patchOpportunity) return;
    store.patchOpportunity(String(oid), {
      zalo_user_id: res.zalo_user_id || "",
      offline_oa_scanned_at: res.scanned_at || "",
      offline_oa_scan_note: res.note || "",
    });
  }

  function saveOaQrNote(oid, kind) {
    if (!store || !store.offlineOaNote) return;
    store
      .offlineOaNote(oid, kind, "")
      .then(function (res) {
        notifyOk(kind === "no_zalo" ? "Đã ghi: khách không dùng Zalo." : "Đã ghi: không chịu quét.");
        var host = document.getElementById("mk-opps-oa-qr-modal");
        if (host) paintOaQrModal(host, oid, res || {});
        rootPatchOaOnOpp(oid, res || {});
        renderAll();
      })
      .catch(function (err) {
        notifyErr((err && err.message) || "Không lưu ghi chú.");
      });
  }

  function sortOpps(rows) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var av = a[key];
      var bv = b[key];
      if (key === "amount") {
        av = Number(av) || 0;
        bv = Number(bv) || 0;
      } else if (key === "converted_at" || key === "confirmed_at" || key === "createdtime") {
        av = new Date(av || 0).getTime() || 0;
        bv = new Date(bv || 0).getTime() || 0;
      } else if (key === "name") {
        av = String(oppCustomerName(a) || "").toLowerCase();
        bv = String(oppCustomerName(b) || "").toLowerCase();
      } else {
        av = String(av || "").toLowerCase();
        bv = String(bv || "").toLowerCase();
      }
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  function computeKpis(rows) {
    var total = rows.length;
    var prospecting = rows.filter(function (o) { return o.sales_stage === "Prospecting"; }).length;
    var withTags = rows.filter(function (o) { return (o.tags || []).length > 0; }).length;
    var franchise = rows.filter(function (o) { return categorize(o.tags).franchise; }).length;
    var confirmed = rows.filter(function (o) {
      var c = categorize(o.tags).confirm;
      return c && ref.normalizeTag(c) === "xac_nhan_tham_gia";
    }).length;
    var closingSoon = rows.filter(function (o) {
      if (!o.closingdate) return false;
      var diff = (new Date(o.closingdate) - new Date()) / 86400000;
      return diff >= 0 && diff <= 30;
    }).length;
    return [
      { key: "total", label: t("JS_MK_KPI_TOTAL_OPP", "Tổng cơ hội"), value: total, icon: "users", tone: "blue" },
      { key: "prospecting", label: pick("Tiềm năng", "Prospecting"), value: prospecting, icon: "check", tone: "emerald" },
      { key: "confirmed", label: pick("Xác nhận tham gia", "Confirmed"), value: confirmed, icon: "bookmark", tone: "cyan" },
      { key: "tagged", label: t("JS_MK_KPI_TAGGED", "Có tag"), value: withTags, icon: "crown", tone: "amber" },
      { key: "franchise", label: t("JS_MK_KPI_FRANCHISE", "Nhượng quyền"), value: franchise, icon: "repeat", tone: "rose" },
      { key: "closing", label: t("JS_MK_KPI_CLOSING", "Đóng trong 30 ngày"), value: closingSoon, icon: "clock", tone: "indigo" },
    ];
  }

  function renderKpi(rows) {
    var host = $("mk-opps-kpi");
    if (!host) return;
    host.innerHTML = computeKpis(rows)
      .map(function (k) {
        return (
          '<article class="mk-leads-kpi-card mk-opps-kpi-card" data-kpi="' + esc(k.key) + '">' +
          '<div class="mk-leads-kpi-card__top">' +
          '<span class="mk-leads-kpi-card__label">' +
          '<span class="mk-leads-kpi-ic-wrap mk-leads-kpi-ic--' + esc(k.tone) + '">' +
          ic(k.icon) +
          "</span>" +
          esc(k.label) +
          "</span></div>" +
          '<div class="mk-leads-kpi-card__value" title="' +
          esc(String(k.value)) +
          '">' +
          esc(String(k.value)) +
          "</div>" +
          "</article>"
        );
      })
      .join("");
  }

  function countSegmentRows(seg, rows) {
    if (!seg || !seg.filters) return rows.length;
    var prev = Object.assign({}, state.filters);
    Object.keys(seg.filters).forEach(function (k) {
      state.filters[k] = seg.filters[k];
    });
    var n = filterOpps(rows).length;
    state.filters = prev;
    return n;
  }

  function renderSegments() {
    var host = $("mk-opps-segments");
    if (!host) return;
    var rows = getOpps();
    var allOn = !state.activeSegment ? " is-active" : "";
    var html =
      '<button type="button" class="mk-leads-segment-btn' +
      allOn +
      '" data-seg="__all__">' +
      esc(t("JS_MK_FILTER_ALL", "Tất cả")) +
      ' <span class="mk-leads-ptab__n">' +
      rows.length +
      "</span></button>";
    html += getPresetSegments()
      .map(function (seg) {
        var active = state.activeSegment === seg.id ? " is-active" : "";
        var n = countSegmentRows(seg, rows);
        return (
          '<button type="button" class="mk-leads-segment-btn' +
          active +
          '" data-seg="' +
          esc(seg.id) +
          '">' +
          esc(seg.name) +
          ' <span class="mk-leads-ptab__n">' +
          n +
          "</span></button>"
        );
      })
      .join("");
    host.innerHTML = html;
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
    var host = $("mk-opps-filters-panel");
    if (!host || !ref) return;
    var rows = getOpps();
    var owners = [];
    var stages = [];
    rows.forEach(function (o) {
      if (o.owner && owners.indexOf(o.owner) < 0) owners.push(o.owner);
      if (o.sales_stage && stages.indexOf(o.sales_stage) < 0) stages.push(o.sales_stage);
    });
    owners.sort();
    stages.sort();
    host.innerHTML =
      '<div class="mk-leads-filters-grid">' +
      fieldSelect(t("JS_MK_FILTER_SALES_STAGE", "Giai đoạn bán hàng"), "sales_stage", stages.map(function (s) { return [s, stageLabel(s)]; })) +
      fieldSelect(t("JS_MK_FILTER_ORDER_CATEGORY", "Loại đơn hàng"), "order_category", [
        ["Internal", pick("Nội bộ", "Internal")],
        ["Project", pick("Dự án", "Project")],
      ]) +
      fieldSelect(t("JS_MK_FILTER_AREA", "Khu vực"), "area", ref.AREA_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_SOURCE", "Nguồn data"), "source", ref.SOURCE_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CUSTOMER", "Dạng khách hàng"), "customer", ref.CUSTOMER_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CLASS", "Tag lớp học"), "classTag", ref.CLASS_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_MATERIAL", "Tag nguyên liệu"), "material", ref.MATERIAL_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_FRANCHISE", "Tag nhượng quyền"), "franchise", ref.FRANCHISE_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_CONFIRM", "Xác nhận tham gia"), "confirm", ref.CONFIRM_TAGS.map(function (tg) { return [ref.normalizeTag(tg), tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_OWNER", "Phụ trách"), "owner", owners.map(function (o) { return [o, o]; })) +
      fieldSelect("Tiến trình", "progress", [
        ["none", "Chưa có tiến độ"],
        ["lt50", "Dưới 50%"],
        ["50_79", "50–79%"],
        ["80_99", "80–99%"],
        ["done", "Hoàn thành 100%"],
      ]) +
      fieldSelect("Đã cấp bằng", "da_cap_bang", [
        ["chua_cap", "Chưa cấp"],
        ["da_cap", "Đã cấp"],
      ]) +
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

  function syncFilterControls() {
    var f = state.filters;
    document.querySelectorAll("#mk-opps-filters-panel [data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (f[key] !== undefined) el.value = f[key];
    });
  }

  function tagCellHtml(primary, extras) {
    var html = "";
    if (primary) html += tagBadgeHtml(primary);
    if (extras && extras.length) {
      extras.forEach(function (t) {
        html += tagBadgeHtml(t);
      });
    }
    return html || '<span class="mk-leads-muted">—</span>';
  }

  function tagBadgeHtml(tag) {
    if (!tag) return '<span class="mk-leads-muted">—</span>';
    var m = tagMeta(tag);
    var key =
      ref && ref.normalizeTag ? ref.normalizeTag(tag) : String(tag || "").trim();
    if (!key) key = String(tag || "").trim();
    return (
      '<span class="mk-tag" data-tag="' +
      esc(key) +
      '" title="' +
      esc(String(tag)) +
      '">' +
      esc(m.label || key) +
      "</span>"
    );
  }

  function oppProgressCellHtml(o) {
    var pct =
      o.edubit_progress_pct != null && o.edubit_progress_pct !== ""
        ? Math.max(0, Math.min(100, Number(o.edubit_progress_pct) || 0))
        : null;
    var course = String(o.edubit_course_id || "").trim();
    if (pct === null && !o.edubit_user_id && !course) {
      return '<span class="mk-leads-muted">—</span>';
    }
    var shown = pct === null ? 0 : pct;
    return (
      '<div class="mk-contacts-edubit-progress" title="Tiến độ khóa Edubit' +
      (course ? " · " + esc(course) : "") +
      '">' +
      '<div class="mk-contacts-edubit-progress__bar"><span style="width:' +
      shown +
      '%"></span></div>' +
      '<div class="mk-contacts-edubit-progress__label">' +
      (pct === null ? "—" : shown + "%") +
      "</div>" +
      (course
        ? '<div class="mk-contacts-edubit-progress__meta">ID ' + esc(course) + "</div>"
        : "") +
      "</div>"
    );
  }

  function oppCredentialSelectHtml(o) {
    var options = ["Chưa cấp", "Đã cấp"];
    var cur = String(o.da_cap_bang || "").trim() || options[0];
    if (options.indexOf(cur) < 0) {
      cur = /đã\s*cấp/i.test(cur) && !/chưa/i.test(cur) ? options[1] : options[0];
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
      '<select class="mk-contacts-cred-select mk-opps-cred-select" data-cred-field="da_cap_bang" data-opp-id="' +
      esc(o.id) +
      '" title="Đã cấp bằng">' +
      opts +
      "</select>"
    );
  }

  function categoryPill(cat) {
    if (!cat) return '<span class="mk-leads-muted">—</span>';
    var cls = cat === "Project" ? "mk-pill--purple" : "mk-pill--orange";
    return '<span class="mk-pill ' + cls + '">' + esc(categoryLabel(cat)) + "</span>";
  }

  function renderTable() {
    var all = getOpps();
    var rows = sortOpps(filterOpps(all));
    var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if (state.page > totalPages) state.page = 1;
    var start = (state.page - 1) * PAGE_SIZE;
    var pageRows = rows.slice(start, start + PAGE_SIZE);
    var tbody = $("mk-opps-tbody");
    if (!tbody) return;

    if (!pageRows.length) {
      tbody.innerHTML =
        '<tr><td colspan="' +
        COL_COUNT +
        '" class="mk-leads-empty"><div class="mk-leads-empty__inner">' +
        esc(t("JS_MK_NO_OPPS_DISPLAY", "Không có cơ hội để hiển thị")) +
        "</div></td></tr>";
    } else {
      tbody.innerHTML = pageRows
        .map(function (o) {
          var cats = categorize(o.tags);
          var crmId = o.crmid != null && o.crmid !== "" ? String(o.crmid) : String(o.id || "");
          var customerName = oppCustomerName(o);
          if (!customerName || customerName === ".") customerName = "";
          var checked = state.selected[o.id] ? " checked" : "";
          return (
            '<tr class="mk-leads-row mk-opps-row' +
            (state.selected[o.id] ? " mk-leads-row--selected" : "") +
            '" data-id="' +
            esc(o.id) +
            '"' +
            (crmId && /^\d+$/.test(crmId) ? ' data-crmid="' + esc(crmId) + '"' : "") +
            ">" +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-opps-row-check" data-id="' + esc(o.id) + '"' + checked + " />" +
            '<span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td" data-col="converted_at">' +
            (o.converted_at || o.createdtime
              ? esc(formatDateTimeFull(o.converted_at || o.createdtime))
              : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--lead"><a class="mk-leads-name" href="' + detailUrl(o.crmid || o.id) + '">' +
            (customerName ? esc(customerName) : '<span class="mk-leads-muted">—</span>') +
            "</a></td>" +
            '<td class="mk-leads-td" data-col="phone">' +
            editableCellHtml("phone", o.phone, o.crmid || o.id, "Nhập SĐT") +
            "</td>" +
            '<td class="mk-leads-td" data-col="region">' +
            regionSelectHtml(o.crmid || o.id, regionKeyOf(o, cats)) +
            "</td>" +
            '<td class="mk-leads-td" data-col="address">' +
            editableCellHtml("address", o.address, o.crmid || o.id, "Nhập địa chỉ") +
            "</td>" +
            '<td class="mk-leads-td" data-col="source">' + tagBadgeHtml(cats.source) + "</td>" +
            '<td class="mk-leads-td" data-col="customer">' + tagBadgeHtml(cats.customer) + "</td>" +
            '<td class="mk-leads-td mk-leads-td--biz">' +
            businessModelSelectHtml(o.crmid || o.id, o.business_model) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--owner"><span class="mk-leads-owner-inner">' +
            '<span class="mk-owner-avatar" style="background:' + ownerColor(o.owner) + '">' + esc(ownerInitials(o.owner)) + "</span>" +
            "<span>" + esc(o.owner || "—") + "</span></span></td>" +
            '<td class="mk-leads-td mk-leads-td--tags" data-col="tags"><button type="button" class="mk-leads-tags-edit" data-opp-id="' +
            esc(o.id) +
            '" title="Sửa thẻ">' +
            stackedTagsHtml(cats) +
            "</button></td>" +
            '<td class="mk-leads-td" data-col="progress">' +
            oppProgressCellHtml(o) +
            "</td>" +
            '<td class="mk-leads-td" data-col="da_cap_bang">' +
            oppCredentialSelectHtml(o) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--touch" data-col="last_touch">' +
            (window.MkLastTouchCall && window.MkLastTouchCall.lastTouchCallLogHtml
              ? window.MkLastTouchCall.lastTouchCallLogHtml(o, esc)
              : window.LeadsLeadsLogic && window.LeadsLeadsLogic.lastTouchCallLogHtml
                ? window.LeadsLeadsLogic.lastTouchCallLogHtml(o, esc)
                : '<span class="mk-leads-muted">Chưa có cuộc gọi</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--next">' +
            (function () {
              var logic = window.LeadsLeadsLogic;
              if (logic && logic.nextActionCellHtml) {
                return logic.nextActionCellHtml(o, esc);
              }
              var next = String(o.next_action || "").trim();
              var tf = String(o.next_action_timeframe || "").trim();
              if (!next && !tf) return '<span class="mk-leads-muted">—</span>';
              var html = '<div class="mk-leads-next-action">';
              if (next) html += '<span class="mk-leads-next-action__text">' + esc(next) + "</span>";
              if (tf) {
                html +=
                  '<span class="mk-leads-next-action__time' +
                  (o.next_action_overdue ? " mk-leads-next-action__time--overdue" : "") +
                  '">' +
                  esc(tf) +
                  "</span>";
              }
              return html + "</div>";
            })() +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--checkin" data-col="attendance">' +
            offlineCheckinCell(o) +
            "</td>" +
            '<td class="mk-leads-td" data-col="notes">' + notesCell(o.notes) + "</td></tr>"
          );
        })
        .join("");
    }

    var summary = $("mk-opps-filter-summary");
    if (summary) {
      summary.textContent =
        rows.length + " / " + all.length + " " + t("JS_MK_OPPS_COUNT_LABEL", "cơ hội");
    }
    renderPagination(rows.length, totalPages);

    var checkAll = $("mk-opps-check-all");
    if (checkAll) {
      var allOnPage = pageRows.length > 0 && pageRows.every(function (o) {
        return !!state.selected[o.id];
      });
      checkAll.checked = allOnPage;
      checkAll.indeterminate = !allOnPage && pageRows.some(function (o) {
        return !!state.selected[o.id];
      });
    }
    renderBulkBar();
  }

  function selectedCount() {
    return Object.keys(state.selected).length;
  }

  function selectedRows() {
    return getOpps().filter(function (o) {
      return !!state.selected[o.id];
    });
  }

  function clearSelection() {
    state.selected = {};
    renderTable();
  }

  function pickTier(count) {
    if (typeof window.MkOppPickCustomerTier === "function") {
      return window.MkOppPickCustomerTier({ count: count || 1 });
    }
    var raw = window.prompt("Chọn hạng khách hàng: vang / bac / dong", "dong");
    if (raw === null) return Promise.resolve(null);
    var s = String(raw).trim().toLowerCase();
    if (s === "gold" || s === "vàng") s = "vang";
    if (s === "silver" || s === "bạc") s = "bac";
    if (s === "bronze" || s === "đồng") s = "dong";
    if (["vang", "bac", "dong"].indexOf(s) < 0) {
      window.alert("Hạng không hợp lệ. Chọn vang, bac hoặc dong.");
      return Promise.resolve(null);
    }
    return Promise.resolve(s);
  }

  function removeOppFromList(recordId) {
    removeOppsFromList([recordId]);
  }

  function removeOppsFromList(recordIds) {
    var ids = (recordIds || []).map(function (id) {
      return String(id || "");
    }).filter(Boolean);
    if (!ids.length) return;
    ids.forEach(function (id) {
      if (store && store.removeFromList) {
        store.removeFromList(id);
      }
      delete state.selected[id];
    });
    if (typeof window.mkSalesPosInlineClose === "function") {
      window.mkSalesPosInlineClose();
    }
    renderAll();
  }

  function convertOppToCustomer(recordId, tier) {
    var id = String(recordId || "");
    var tierKey = String(tier || "").trim().toLowerCase();
    if (!id) {
      return Promise.reject(new Error("Không tìm thấy ID Cơ hội."));
    }
    if (tierKey && ["vang", "bac", "dong"].indexOf(tierKey) < 0) {
      tierKey = "";
    }
    return new Promise(function (resolve, reject) {
      if (!(window.app && app.request && app.request.post)) {
        reject(new Error("Không kết nối được máy chủ."));
        return;
      }
      var postData = {
        module: "Potentials",
        action: "ConvertToCustomer",
        record: id,
      };
      if (tierKey) postData.tier = tierKey;
      app.request
        .post({
          data: postData,
        })
        .then(function (err, res) {
          if (err || !res || res.success === false) {
            var msg =
              (res && res.message) ||
              (err && err.message) ||
              "Không chuyển được sang Khách hàng.";
            reject(new Error(String(msg)));
            return;
          }
          var contactId = res.contact_id || (res.result && res.result.contact_id);
          if (!contactId) {
            reject(new Error("Không tìm thấy Contact để chuyển."));
            return;
          }
          resolve({
            contactId: contactId,
            tier: tierKey,
            url: "index.php?module=Contacts&view=List&app=SALES",
          });
        });
    });
  }

  function runBulkConvertToCustomer(rows) {
    if (!rows || !rows.length) return;
    (function () {
      if (window.app && app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
      var ok = 0;
      var fail = 0;
      var convertedIds = [];
      var chain = Promise.resolve();
      rows.forEach(function (o) {
        chain = chain.then(function () {
          var oid = o.crmid || o.id;
          return convertOppToCustomer(oid, "")
            .then(function () {
              ok += 1;
              convertedIds.push(oid);
            })
            .catch(function () {
              fail += 1;
            });
        });
      });
      chain.then(function () {
        if (window.app && app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        var msg = "Đã chuyển " + ok + " cơ hội sang Khách hàng.";
        if (fail) msg += " " + fail + " thất bại.";
        if (window.app && app.helper && app.helper.showSuccessNotification) {
          app.helper.showSuccessNotification({ message: msg });
        } else {
          window.alert(msg);
        }
        clearSelection();
        if (convertedIds.length) {
          removeOppsFromList(convertedIds);
        } else {
          renderAll();
        }
      });
    })();
  }

  function openInlineConvertToCustomer(btn) {
    var recordId = String((btn && btn.getAttribute("data-record-id")) || "");
    if (!recordId) {
      var panel = btn && btn.closest ? btn.closest(".mk-so-inline-detail") : null;
      recordId = String((panel && panel.getAttribute("data-record-id")) || "");
    }
    if (!recordId) return;
    if (btn) btn.disabled = true;
    if (window.app && app.helper && app.helper.showProgress) {
      app.helper.showProgress();
    }
    convertOppToCustomer(recordId, "")
        .then(function () {
          if (window.app && app.helper && app.helper.hideProgress) {
            app.helper.hideProgress();
          }
          if (btn) btn.disabled = false;
          if (window.app && app.helper && app.helper.showSuccessNotification) {
            app.helper.showSuccessNotification({
              message: "Đã chuyển sang Khách hàng. Cơ hội đã được ẩn khỏi danh sách.",
            });
          }
          removeOppFromList(recordId);
        })
        .catch(function (err) {
          if (window.app && app.helper && app.helper.hideProgress) {
            app.helper.hideProgress();
          }
          if (btn) btn.disabled = false;
          window.alert((err && err.message) || "Không chuyển được sang Khách hàng.");
        });
  }

  function renderBulkBar() {
    var bar = $("mk-opps-bulk");
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
      "</strong> đã chọn</span>" +
      "</div>" +
      '<div class="mk-leads-bulk-bar__actions">' +
      '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--convert" data-bulk="to_customer">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("convert") +
      "</span><span>Chuyển sang khách hàng</span></button>" +
      '<button type="button" class="mk-leads-bulk-btn" data-bulk="export">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("export") +
      "</span><span>Xuất file</span></button>" +
      '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--danger" data-bulk="delete">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("trash") +
      "</span><span>Xóa</span></button>" +
      "</div>" +
      '<button type="button" class="mk-leads-bulk-clear" data-bulk="clear">Bỏ chọn</button>' +
      "</div>";
  }

  function oppCustomerName(o) {
    var n = String((o && o.name) || "").trim();
    if (n && n !== ".") return n;
    n = String((o && o.contact) || "").trim();
    if (n && n !== ".") return n;
    n = String((o && o.account) || "").trim();
    if (n && n !== ".") return n;
    return "";
  }

  function exportCsv(rows) {
    var lines = ["Customer,OrderType,Stage,Amount,Owner,Tags"];
    rows.forEach(function (o) {
      var customerName = oppCustomerName(o);
      lines.push(
        [
          customerName,
          o.order_category || "",
          o.sales_stage || "",
          o.amount || 0,
          o.owner || "",
          (o.tags || []).join("|"),
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
    a.download = "opportunities.csv";
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  function renderPagination(total, totalPages) {
    var host = $("mk-opps-pagination");
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
      state.filters = Object.assign({}, EMPTY);
      state.page = 1;
      renderAll();
      return;
    }
    var seg = getPresetSegments().find(function (s) { return s.id === segId; });
    state.activeSegment = segId;
    state.filters = Object.assign({}, EMPTY);
    if (seg && seg.filters) {
      Object.keys(seg.filters).forEach(function (k) {
        state.filters[k] = seg.filters[k];
      });
    }
    state.page = 1;
    renderAll();
  }

  function renderAll() {
    var rows = getOpps();
    renderKpi(rows);
    renderSegments();
    renderFiltersPanel();
    renderTable();
    document.documentElement.classList.add("mk-opp-list-ready");
  }

  function bindEvents() {
    var search = $("mk-opps-search");
    if (search) {
      search.addEventListener("input", function () {
        state.filters.search = search.value;
        state.page = 1;
        renderTable();
        renderKpi(filterOpps(getOpps()));
      });
    }

    var toggle = $("mk-opps-filters-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        state.filtersOpen = !state.filtersOpen;
        toggle.setAttribute("aria-expanded", state.filtersOpen ? "true" : "false");
        var panel = $("mk-opps-filters-panel");
        if (panel) panel.hidden = !state.filtersOpen;
      });
    }

    document.addEventListener("change", function (e) {
      var el = e.target;
      if (!el) return;
      if (el.classList && el.classList.contains("mk-leads-biz-select") && el.getAttribute("data-opp-id")) {
        e.stopPropagation();
        commitBusinessModelChange(el);
        return;
      }
      if (el.classList && el.classList.contains("mk-leads-region-select") && el.getAttribute("data-opp-id")) {
        e.stopPropagation();
        commitRegionChange(el);
        return;
      }
      if (el.classList && el.classList.contains("mk-opps-cred-select") && el.getAttribute("data-opp-id")) {
        e.stopPropagation();
        var oid = el.getAttribute("data-opp-id");
        var opp = getOpps().find(function (o) {
          return String(o.id) === String(oid);
        });
        if (!opp || !(window.app && app.request && app.request.post)) return;
        var nextBang = el.value;
        var nextTk = opp.da_cap_tai_khoan || "Chưa cấp tài khoản";
        el.disabled = true;
        app.request
          .post({
            data: {
              module: "Potentials",
              action: "ModernApi",
              mode: "credential_save",
              record: opp.crmid || opp.id,
              id: opp.crmid || opp.id,
              da_cap_bang: nextBang,
              da_cap_tai_khoan: nextTk,
            },
          })
          .then(function (err, res) {
            el.disabled = false;
            if (err || !res || !res.success) {
              if (window.app && app.helper && app.helper.showErrorNotification) {
                app.helper.showErrorNotification({
                  message: (err && (err.message || err)) || (res && res.error) || "Không lưu được Đã cấp bằng.",
                });
              }
              return;
            }
            if (store && typeof store.patchOpportunity === "function") {
              store.patchOpportunity(opp.crmid || opp.id, {
                da_cap_bang: (res.credentials && res.credentials.da_cap_bang) || nextBang,
                da_cap_tai_khoan: (res.credentials && res.credentials.da_cap_tai_khoan) || nextTk,
              });
            } else {
              opp.da_cap_bang = nextBang;
              opp.da_cap_tai_khoan = nextTk;
            }
            renderTable();
          });
        return;
      }
      if (el.classList && el.classList.contains("mk-opps-row-check")) {
        var id = el.getAttribute("data-id");
        if (el.checked) state.selected[id] = true;
        else delete state.selected[id];
        renderTable();
        return;
      }
      if (el.id === "mk-opps-check-all") {
        var pageRows = sortOpps(filterOpps(getOpps())).slice(
          (state.page - 1) * PAGE_SIZE,
          state.page * PAGE_SIZE
        );
        pageRows.forEach(function (o) {
          if (el.checked) state.selected[o.id] = true;
          else delete state.selected[o.id];
        });
        renderTable();
        return;
      }
      if (!el.getAttribute || !el.closest("#mk-opps-filters-panel")) return;
      var key = el.getAttribute("data-fkey");
      if (!key) return;
      state.filters[key] = el.value;
      state.activeSegment = null;
      state.page = 1;
      renderAll();
    });

    document.addEventListener("click", function (e) {
      var editBtn = e.target.closest && e.target.closest(".mk-leads-inline-edit[data-opp-id]");
      if (editBtn) {
        e.preventDefault();
        e.stopPropagation();
        beginInlineEdit(editBtn);
        return;
      }
      var tagsBtn = e.target.closest && e.target.closest(".mk-leads-tags-edit[data-opp-id]");
      if (tagsBtn) {
        e.preventDefault();
        e.stopPropagation();
        var oid = tagsBtn.getAttribute("data-opp-id");
        var opp = getOpps().find(function (o) {
          return String(o.id) === String(oid);
        });
        if (opp) openTagPopover(tagsBtn, opp);
        return;
      }
      var checkinBtn =
        e.target.closest && e.target.closest("[data-mk-opp-checkin][data-opp-id]");
      if (checkinBtn) {
        e.preventDefault();
        e.stopPropagation();
        submitOfflineCheckin(checkinBtn.getAttribute("data-mk-opp-checkin"), checkinBtn);
        return;
      }
      var oaOpen = e.target.closest && e.target.closest("[data-mk-opp-oa-qr]");
      if (oaOpen) {
        e.preventDefault();
        e.stopPropagation();
        openOaQrModal(oaOpen.getAttribute("data-mk-opp-oa-qr"), null);
        return;
      }
      if (e.target.closest && e.target.closest("[data-mk-oa-qr-close]")) {
        e.preventDefault();
        e.stopPropagation();
        closeOaQrModal();
        return;
      }
      var oaRefresh = e.target.closest && e.target.closest("[data-mk-oa-qr-refresh]");
      if (oaRefresh) {
        e.preventDefault();
        e.stopPropagation();
        refreshOaQrModal(oaRefresh.getAttribute("data-mk-oa-qr-refresh"), false);
        return;
      }
      var oaNote = e.target.closest && e.target.closest("[data-mk-oa-qr-note][data-opp-id]");
      if (oaNote) {
        e.preventDefault();
        e.stopPropagation();
        saveOaQrNote(oaNote.getAttribute("data-opp-id"), oaNote.getAttribute("data-mk-oa-qr-note"));
        return;
      }
      var edubitBtn =
        e.target.closest && e.target.closest("[data-mk-opp-edubit-action][data-opp-id]");
      if (edubitBtn) {
        e.preventDefault();
        e.stopPropagation();
        submitOppEdubitProvision(edubitBtn);
        return;
      }
      var rescheduleBtn =
        e.target.closest && e.target.closest("[data-mk-opp-reschedule][data-opp-id]");
      if (rescheduleBtn) {
        e.preventDefault();
        e.stopPropagation();
        submitOfflineReschedule(
          rescheduleBtn.getAttribute("data-mk-opp-reschedule"),
          rescheduleBtn
        );
        return;
      }
      if (!e.target.closest || !e.target.closest("#mk-opps-tag-popover")) {
        closeTagPopover();
      }
      var inlineToCustomer =
        e.target.closest &&
        e.target.closest(".mk-so-inline-detail__to-customer-btn");
      if (inlineToCustomer) {
        e.preventDefault();
        e.stopPropagation();
        openInlineConvertToCustomer(inlineToCustomer);
        return;
      }
      var bulkBtn = e.target.closest && e.target.closest("#mk-opps-bulk [data-bulk]");
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
      if (action === "to_customer") {
        runBulkConvertToCustomer(rows);
        return;
      }
      if (action === "export") {
        exportCsv(rows);
        return;
      }
      if (action === "delete") {
        if (!window.confirm("Xóa " + rows.length + " cơ hội đã chọn?")) return;
        if (!store || !store.remove) return;
        Promise.all(
          rows.map(function (o) {
            return store.remove(o.id);
          })
        ).then(function () {
          clearSelection();
          renderAll();
        });
      }
    });

    document.addEventListener("mk-opps-confirm-updated", function (e) {
      var detail = (e && e.detail) || {};
      var id = detail.id != null ? String(detail.id) : "";
      if (!id) return;
      if (store && store.setConfirmTag) {
        store.setConfirmTag(id, detail.confirm || "");
      }
      // Keep expanded row; refresh KPIs / filter summary without wiping tbody.
      var all = getOpps();
      var rows = filterOpps(all);
      renderKpi(rows);
      var summary = $("mk-opps-filter-summary");
      if (summary) {
        summary.textContent =
          rows.length + " / " + all.length + " " + t("JS_MK_OPPS_COUNT_LABEL", "cơ hội");
      }
    });

    document.addEventListener("mk-opps-attendance-updated", function () {
      if (store && store.refresh) {
        store.refresh().then(function () {
          renderAll();
        });
      } else {
        renderAll();
      }
    });

    var segHost = $("mk-opps-segments");
    if (segHost) {
      segHost.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-seg]");
        if (!btn) return;
        applySegment(btn.getAttribute("data-seg"));
      });
    }

    var pag = $("mk-opps-pagination");
    if (pag) {
      pag.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-page]");
        if (!btn || btn.disabled) return;
        if (btn.getAttribute("data-page") === "prev") state.page--;
        else state.page++;
        renderTable();
      });
    }

    var reset = $("mk-opps-reset");
    if (reset) {
      reset.addEventListener("click", function () {
        state.filters = Object.assign({}, EMPTY);
        state.activeSegment = null;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      });
    }

    document.addEventListener(
      "focusout",
      function (e) {
        if (e.target && e.target.classList && e.target.classList.contains("mk-leads-inline-input") && e.target.getAttribute("data-opp-id")) {
          commitInlineEdit(e.target);
        }
      },
      true
    );
    document.addEventListener("keydown", function (e) {
      if (e.key !== "Enter") return;
      if (e.target && e.target.classList && e.target.classList.contains("mk-leads-inline-input") && e.target.getAttribute("data-opp-id")) {
        e.preventDefault();
        e.target.blur();
      }
    });

    if ($("mk-opps-import-ic")) $("mk-opps-import-ic").innerHTML = ic("import");
    if ($("mk-opps-create-ic")) $("mk-opps-create-ic").innerHTML = ic("plus");
    if ($("mk-opps-search-ic")) $("mk-opps-search-ic").innerHTML = ic("search");
    if ($("mk-opps-segments-icon")) $("mk-opps-segments-icon").innerHTML = ic("filter");
    if ($("mk-opps-filters-ic")) $("mk-opps-filters-ic").innerHTML = ic("filter");
  }

  function init() {
    if (!document.querySelector(".mk-opps-page")) return;
    if (window.MkLastTouchCall && window.MkLastTouchCall.create) {
      window.__mkOppLastTouch = window.MkLastTouchCall.create({
        module: "Potentials",
        onLogged: function (recordId, lt, res, callBtn) {
          var opps = getOpps();
          var row = opps.find(function (o) {
            return String(o.id) === String(recordId) || String(o.crmid) === String(recordId);
          });
          if (row && lt) {
            row.lastTouchCalls = lt;
            if (lt.logged && lt.logged.called_at) {
              row.last_touch = lt.logged.called_at;
            }
            var off = (lt && lt.offline) || (res && res.offline) || null;
            if (off) {
              if (off.status) row.offline_status = off.status;
              if (off.status_label) row.offline_status_label = off.status_label;
              if (typeof off.post_noshow_miss === "number") {
                row.offline_post_noshow_miss = off.post_noshow_miss;
              }
            }
            if (store && store.patchOpportunity) {
              store.patchOpportunity(row.id, {
                lastTouchCalls: lt,
                last_touch: row.last_touch,
                offline_status: row.offline_status,
                offline_status_label: row.offline_status_label,
                offline_post_noshow_miss: row.offline_post_noshow_miss,
              });
            }
          }
          if (window.__mkOppLastTouch) {
            window.__mkOppLastTouch.applyToPanel(callBtn, lt);
          }
          var touchTd = document.querySelector(
            'tr.mk-opps-row[data-id="' +
              String((row && row.id) || recordId) +
              '"] .mk-leads-td--touch'
          );
          if (touchTd && row) {
            touchTd.innerHTML = window.MkLastTouchCall.lastTouchCallLogHtml(row, esc);
          } else {
            renderTable();
          }
          if ((lt && lt.offline) || (res && res.offline)) {
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

  window.PotentialsMkListRefresh = function () {
    if (!document.querySelector(".mk-opps-page")) return;
    if (store && store.refresh) {
      return store.refresh().then(function () {
        renderAll();
      });
    }
    renderAll();
    return Promise.resolve();
  };
})();
