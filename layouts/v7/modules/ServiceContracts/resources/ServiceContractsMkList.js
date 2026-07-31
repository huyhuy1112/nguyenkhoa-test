/* ServiceContracts list — Lovable UI (Leads shell) + affiliate column */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var ref = window.ServiceContractsLovableRef;
  var store = window.ServiceContractsLocalStore;
  var icons = window.LeadsMkIcons;
  var COL_COUNT = 13;

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

  var FRANCHISE_STATUS_OPTS = [
    "Quan Tâm/Tham Khảo",
    "Không đủ tài chính",
    "Đã Kí Quỹ",
    "Đang chăm sóc",
    "Chuyển sang Nguyên Khoa",
  ];
  var DATA_SOURCE_OPTS = ["Facebook", "TikTok", "Website", "Zalo", "Khác"];
  var CONTACT_STATUS_OPTS = ["Chưa gọi", "Đã gửi tư vấn", "Thuê bao", "Ko nghe Máy Lần 1"];

  function getPresetSegments() {
    return [
      {
        id: "caring",
        name: pick("Đang chăm sóc", "In care"),
        filters: { franchiseStatus: "Đang chăm sóc" },
      },
      {
        id: "interested",
        name: pick("Quan tâm / Tham khảo", "Interested"),
        filters: { franchiseStatus: "Quan Tâm/Tham Khảo" },
      },
      {
        id: "deposit",
        name: pick("Đã ký quỹ", "Deposited"),
        filters: { franchiseStatus: "Đã Kí Quỹ" },
      },
      {
        id: "zalo",
        name: "Zalo",
        filters: { dataSource: "Zalo" },
      },
      {
        id: "facebook",
        name: "Facebook",
        filters: { dataSource: "Facebook" },
      },
      {
        id: "tiktok",
        name: "TikTok",
        filters: { dataSource: "TikTok" },
      },
      {
        id: "no_call",
        name: pick("Chưa gọi", "Not called"),
        filters: { contactStatus: "Chưa gọi" },
      },
    ];
  }

  var EMPTY = {
    search: "",
    franchiseStatus: ANY,
    dataSource: ANY,
    contactStatus: ANY,
    referrer: ANY,
    owner: ANY,
  };

  var state = {
    filters: Object.assign({}, EMPTY),
    sortKey: "createdtime",
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

  function getContracts() {
    return store ? store.getContracts() : [];
  }

  function detailUrl(id) {
    return (
      "index.php?module=ServiceContracts&view=Detail&record=" +
      encodeURIComponent(id) +
      "&app=SALES"
    );
  }

  function ownerInitials(name) {
    var parts = String(name || "")
      .trim()
      .split(/\s+/);
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

  function ownerLabel(c) {
    return String((c && (c.sale_owner || c.owner)) || "").trim();
  }

  function filterRows(rows) {
    var f = state.filters;
    var q = (f.search || "").toLowerCase().trim();
    return rows.filter(function (c) {
      if (q) {
        var hay = [
          c.name,
          c.affiliate_code,
          c.contract_no,
          c.account,
          c.email,
          c.phone,
          ownerLabel(c),
          c.area,
          c.address,
          c.district,
          c.business_note,
          c.franchise_status,
          c.data_source,
          c.referrer,
          c.contact_status,
          c.interaction_1,
          c.interaction_2,
          c.interaction_3,
          c.interaction_materials,
          c.notes,
        ]
          .join(" ")
          .toLowerCase();
        if (hay.indexOf(q) < 0) {
          var qDigits = String(q).replace(/\D+/g, "");
          var phoneDigits = String(c.phone || "").replace(/\D+/g, "");
          if (!(qDigits.length >= 3 && phoneDigits.indexOf(qDigits) >= 0)) {
            return false;
          }
        }
      }
      if (f.franchiseStatus !== ANY && String(c.franchise_status || "") !== f.franchiseStatus) return false;
      if (f.dataSource !== ANY && String(c.data_source || "") !== f.dataSource) return false;
      if (f.contactStatus !== ANY && String(c.contact_status || "") !== f.contactStatus) return false;
      if (f.referrer !== ANY && String(c.referrer || "") !== f.referrer) return false;
      if (f.owner !== ANY && ownerLabel(c) !== f.owner) return false;
      return true;
    });
  }

  function sortRows(rows) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var av = a[key];
      var bv = b[key];
      if (key === "last_touch" || key === "createdtime" || key === "received_date") {
        av = av ? new Date(av).getTime() : 0;
        bv = bv ? new Date(bv).getTime() : 0;
        if (isNaN(av)) av = 0;
        if (isNaN(bv)) bv = 0;
        if (av < bv) return -1 * dir;
        if (av > bv) return 1 * dir;
        // Tie-break: mới hơn (id lớn hơn) đứng trên khi sort desc.
        var aid = parseInt(a.crmid || a.id, 10) || 0;
        var bid = parseInt(b.crmid || b.id, 10) || 0;
        if (aid < bid) return -1 * dir;
        if (aid > bid) return 1 * dir;
        return 0;
      }
      av = String(av || "").toLowerCase();
      bv = String(bv || "").toLowerCase();
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      var aid2 = parseInt(a.crmid || a.id, 10) || 0;
      var bid2 = parseInt(b.crmid || b.id, 10) || 0;
      if (aid2 < bid2) return -1 * dir;
      if (aid2 > bid2) return 1 * dir;
      return 0;
    });
  }

  function formatDateTimeLabel(raw) {
    if (!raw) return "";
    var s = String(raw).trim();
    // Already dd/mm/yyyy HH:mm
    if (/^\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}/.test(s)) {
      return s.slice(0, 16);
    }
    var m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?/);
    if (m) {
      var out = m[3] + "/" + m[2] + "/" + m[1];
      if (m[4] != null) {
        out += " " + m[4] + ":" + m[5];
      }
      return out;
    }
    var d = new Date(raw);
    if (isNaN(d.getTime())) return s;
    var dd = String(d.getDate());
    dd = dd.length < 2 ? "0" + dd : dd;
    var mm = String(d.getMonth() + 1);
    mm = mm.length < 2 ? "0" + mm : mm;
    var yyyy = d.getFullYear();
    var hh = String(d.getHours());
    hh = hh.length < 2 ? "0" + hh : hh;
    var mi = String(d.getMinutes());
    mi = mi.length < 2 ? "0" + mi : mi;
    return dd + "/" + mm + "/" + yyyy + " " + hh + ":" + mi;
  }

  function dateCell(raw) {
    var label = formatDateTimeLabel(raw);
    return label
      ? '<span class="mk-leads-date">' + esc(label) + "</span>"
      : '<span class="mk-leads-muted">—</span>';
  }

  function dateOnlyCell(raw) {
    var s = String(raw || "").trim();
    if (!s || s === "0000-00-00") {
      return '<span class="mk-leads-muted">—</span>';
    }
    // YYYY-MM-DD -> DD/MM/YYYY
    var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (m) {
      return '<span class="mk-leads-date">' + esc(m[3] + "/" + m[2] + "/" + m[1]) + "</span>";
    }
    return dateCell(s);
  }

  function phoneCell(raw) {
    var phone = String(raw || "").trim();
    if (!phone) {
      return '<span class="mk-leads-muted">—</span>';
    }
    var display =
      window.MkPhoneFormat && typeof window.MkPhoneFormat.format === "function"
        ? window.MkPhoneFormat.format(phone)
        : phone;
    return '<span class="mk-leads-phone">' + esc(display || phone) + "</span>";
  }

  function textCell(raw, opts) {
    var n = String(raw || "").trim();
    if (!n) {
      return '<span class="mk-leads-muted">—</span>';
    }
    var max = opts && opts.max ? opts.max : 80;
    var short = n.length > max ? n.slice(0, max) + "…" : n;
    return '<span class="mk-sc-cell-text" title="' + esc(n) + '">' + esc(short) + "</span>";
  }

  function pillClassFor(kind, value) {
    var v = String(value || "").toLowerCase();
    if (kind === "franchise_status") {
      if (v.indexOf("quan tâm") >= 0 || v.indexOf("tham khảo") >= 0) return "mk-sc-pill mk-sc-pill--pink";
      if (v.indexOf("tài chính") >= 0) return "mk-sc-pill mk-sc-pill--slate";
      if (v.indexOf("ký quỹ") >= 0 || v.indexOf("ki quỹ") >= 0) return "mk-sc-pill mk-sc-pill--red";
      if (v.indexOf("dừng") >= 0 || v.indexOf("ngưng") >= 0) return "mk-sc-pill mk-sc-pill--black";
      if (v.indexOf("chăm sóc") >= 0) return "mk-sc-pill mk-sc-pill--green";
      return "mk-sc-pill mk-sc-pill--slate";
    }
    if (kind === "data_source") {
      if (v.indexOf("facebook") >= 0 || v.indexOf("fb") >= 0) return "mk-sc-pill mk-sc-pill--fb";
      if (v.indexOf("tiktok") >= 0) return "mk-sc-pill mk-sc-pill--tiktok";
      if (v.indexOf("website") >= 0) return "mk-sc-pill mk-sc-pill--web";
      if (v.indexOf("zalo") >= 0) return "mk-sc-pill mk-sc-pill--zalo";
      return "mk-sc-pill mk-sc-pill--slate";
    }
    if (kind === "contact_status") {
      if (v.indexOf("chưa") >= 0) return "mk-sc-pill mk-sc-pill--red";
      if (v.indexOf("đã") >= 0 || v.indexOf("tư vấn") >= 0) return "mk-sc-pill mk-sc-pill--green";
      return "mk-sc-pill mk-sc-pill--slate";
    }
    return "mk-sc-pill";
  }

  function pillCell(kind, raw) {
    var v = String(raw || "").trim();
    if (!v) {
      return '<span class="mk-leads-muted">—</span>';
    }
    return '<span class="' + pillClassFor(kind, v) + '">' + esc(v) + "</span>";
  }

  function interactionNoteCell(field, value, recordId) {
    var cur = String(value || "").trim();
    var isMaterials = field === "interaction_materials";
    var cls = "mk-sc-ix-note" + (isMaterials ? " mk-sc-ix-note--lg" : "");
    var rows = "2";
    return (
      '<textarea class="' +
      cls +
      '" data-field="' +
      esc(field) +
      '" data-id="' +
      esc(String(recordId || "")) +
      '" data-prev="' +
      esc(cur) +
      '" rows="' +
      rows +
      '" placeholder="Ghi chú…" title="' +
      esc(cur || "Nhập ghi chú") +
      '">' +
      esc(cur) +
      "</textarea>"
    );
  }

  function lastTouchCallCell(c) {
    var lt = (c && c.lastTouchCalls) || {};
    var calls = lt.calls || [];
    var count = typeof lt.count === "number" ? lt.count : calls.length;
    var max = lt.max_calls || 3;
    if (!calls.length) {
      return (
        '<div class="mk-sc-lt-cell">' +
        '<span class="mk-sc-lt-badge is-open">' +
        esc(String(count) + "/" + String(max)) +
        "</span>" +
        '<span class="mk-leads-muted">Chưa có cuộc gọi</span></div>'
      );
    }
    return (
      '<div class="mk-sc-lt-cell">' +
      '<span class="mk-sc-lt-badge ' +
      (lt.can_add === false ? "is-done" : "is-open") +
      '">' +
      esc(String(count) + "/" + String(max)) +
      "</span>" +
      '<div class="mk-sc-lt-log">' +
      calls
        .map(function (call) {
          var line =
            call.label ||
            (call.called_at_label || "") +
              " Call #" +
              (call.n || "") +
              " Kết quả: " +
              (call.result || "");
          return '<div class="mk-sc-lt-log__item" title="' + esc(line) + '">' + esc(line) + "</div>";
        })
        .join("") +
      "</div></div>"
    );
  }

  function saveInteractionField(recordId, field, value) {
    var payload = {};
    payload[field] = value;
    var postData = {
      module: "ServiceContracts",
      action: "ModernApi",
      mode: "save_inline",
      record: recordId,
      payload: JSON.stringify(payload),
    };
    function done(err, res) {
      if (err || !res || res.success === false) {
        var msg =
          (err && err.message) ||
          (res && (res.error || res.message)) ||
          "Không lưu được tương tác.";
        if (window.app && app.helper && app.helper.showErrorNotification) {
          app.helper.showErrorNotification({ message: String(msg) });
        } else {
          window.alert(String(msg));
        }
        return;
      }
      var c = (res && res.contract) || {};
      if (store && typeof store.patchContract === "function") {
        var patch = {};
        patch[field] = c[field] != null ? c[field] : value;
        store.patchContract(String(recordId), patch);
      }
    }
    if (window.app && app.request && app.request.post) {
      app.request.post({ data: postData }).then(done);
    } else {
      fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: Object.keys(postData)
          .map(function (k) {
            return encodeURIComponent(k) + "=" + encodeURIComponent(postData[k]);
          })
          .join("&"),
        credentials: "same-origin",
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (r) {
          done(null, r && r.result ? r.result : r);
        })
        .catch(function () {
          done({ message: "Không kết nối được máy chủ." }, null);
        });
    }
  }

  function nextActionCell(c) {
    var next = String(c.next_action || "").trim();
    var overdue = !!c.next_action_overdue;
    var days =
      overdue && c.next_action_days_overdue != null
        ? c.next_action_days_overdue
        : c.next_action_days_remaining != null
          ? c.next_action_days_remaining
          : null;
    var alertLabel = "";
    if (c.rule_alert_days != null && Number(c.rule_alert_days) > 0) {
      if (overdue) {
        alertLabel = pick("Quá hạn " + days + " ngày", "Overdue " + days + "d");
      } else if (days != null) {
        alertLabel = pick("Còn " + days + " ngày", days + "d left");
      } else {
        alertLabel = pick("Cảnh báo " + c.rule_alert_days + " ngày", "Alert " + c.rule_alert_days + "d");
      }
    }
    var chip =
      alertLabel !== ""
        ? '<span class="mk-leads-next-alert' +
          (overdue ? " is-overdue" : "") +
          '">' +
          esc(alertLabel) +
          "</span>"
        : "";
    return (
      '<div class="mk-leads-next-cell" data-sc-id="' +
      esc(c.id) +
      '">' +
      '<button type="button" class="mk-leads-next-edit" data-sc-next="' +
      esc(c.id) +
      '" title="' +
      esc(pick("Sửa hành động tiếp theo", "Edit next action")) +
      '">' +
      (next
        ? '<span class="mk-leads-next-text">' + esc(next) + "</span>"
        : '<span class="mk-leads-muted">' + esc(pick("Thêm…", "Add…")) + "</span>") +
      chip +
      "</button></div>"
    );
  }

  function stackedTags(tags) {
    if (!tags || !tags.length) {
      return '<span class="mk-leads-muted">' + esc(pick("Thêm thẻ", "Add tags")) + "</span>";
    }
    return tags
      .map(function (tg) {
        var meta = tagMeta(tg);
        return '<span class="mk-tag ' + esc(meta.cls || "mk-tag") + '">' + esc(meta.label) + "</span>";
      })
      .join(" ");
  }

  function computeKpis(rows) {
    var withPhone = rows.filter(function (c) {
      return !!c.phone;
    }).length;
    var withAff = rows.filter(function (c) {
      return !!c.affiliate_code;
    }).length;
    var caring = rows.filter(function (c) {
      return String(c.franchise_status || "") === "Đang chăm sóc";
    }).length;
    var interested = rows.filter(function (c) {
      return String(c.franchise_status || "") === "Quan Tâm/Tham Khảo";
    }).length;
    var deposited = rows.filter(function (c) {
      return String(c.franchise_status || "") === "Đã Kí Quỹ";
    }).length;
    var noCall = rows.filter(function (c) {
      return String(c.contact_status || "") === "Chưa gọi";
    }).length;
    return [
      {
        key: "total",
        label: t("JS_MK_KPI_TOTAL_SC", "Tổng khách CN"),
        value: rows.length,
        icon: "users",
        tone: "blue",
      },
      {
        key: "affiliate",
        label: t("JS_MK_KPI_AFFILIATE", "Có mã AFF"),
        value: withAff,
        icon: "check",
        tone: "emerald",
      },
      {
        key: "phone",
        label: t("JS_MK_KPI_PHONE", "Có SĐT"),
        value: withPhone,
        icon: "clock",
        tone: "cyan",
      },
      {
        key: "caring",
        label: t("JS_MK_KPI_CARING", "Đang chăm sóc"),
        value: caring,
        icon: "trend",
        tone: "indigo",
      },
      {
        key: "interested",
        label: t("JS_MK_KPI_INTERESTED", "Quan tâm"),
        value: interested,
        icon: "crown",
        tone: "violet",
      },
      {
        key: "deposit",
        label: t("JS_MK_KPI_DEPOSIT", "Đã ký quỹ"),
        value: deposited,
        icon: "check",
        tone: "rose",
      },
      {
        key: "no_call",
        label: t("JS_MK_KPI_NO_CALL", "Chưa gọi"),
        value: noCall,
        icon: "alert",
        tone: "amber",
      },
    ];
  }

  function renderKpi(rows) {
    var host = $("mk-sc-kpi");
    if (!host) return;
    host.innerHTML = computeKpis(rows)
      .map(function (k) {
        return (
          '<article class="mk-leads-kpi-card" data-kpi="' +
          esc(k.key) +
          '">' +
          '<div class="mk-leads-kpi-card__top">' +
          '<span class="mk-leads-kpi-card__label">' +
          '<span class="mk-leads-kpi-ic-wrap mk-leads-kpi-ic--' +
          esc(k.tone) +
          '">' +
          ic(k.icon) +
          "</span>" +
          esc(k.label) +
          "</span></div>" +
          '<div class="mk-leads-kpi-card__value">' +
          esc(String(k.value)) +
          "</div>" +
          "</article>"
        );
      })
      .join("");
  }

  function renderSegments() {
    var host = $("mk-sc-segments");
    if (!host) return;
    var allOn = !state.activeSegment ? " is-active" : "";
    var html =
      '<button type="button" class="mk-leads-segment-btn' +
      allOn +
      '" data-seg="__all__">' +
      esc(t("JS_MK_FILTER_ALL", "Tất cả")) +
      "</button>";
    html += getPresetSegments()
      .map(function (seg) {
        var active = state.activeSegment === seg.id ? " is-active" : "";
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
    host.innerHTML = html;
  }

  function selectOptions(pairs) {
    return (
      '<option value="' +
      ANY +
      '">' +
      esc(t("JS_MK_FILTER_ALL", "Tất cả")) +
      "</option>" +
      pairs
        .map(function (p) {
          return '<option value="' + esc(p[0]) + '">' + esc(p[1]) + "</option>";
        })
        .join("")
    );
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

  function uniqueSorted(values) {
    var out = [];
    (values || []).forEach(function (v) {
      var s = String(v || "").trim();
      if (s && out.indexOf(s) < 0) out.push(s);
    });
    out.sort(function (a, b) {
      return a.localeCompare(b, "vi");
    });
    return out;
  }

  function mergePickOptions(defaults, fromRows) {
    return uniqueSorted((defaults || []).concat(fromRows || []));
  }

  function renderFiltersPanel() {
    var host = $("mk-sc-filters-panel");
    if (!host) return;
    var rows = getContracts();
    var statusVals = [];
    var sourceVals = [];
    var contactVals = [];
    var referrers = [];
    var owners = [];
    rows.forEach(function (c) {
      if (c.franchise_status) statusVals.push(c.franchise_status);
      if (c.data_source) sourceVals.push(c.data_source);
      if (c.contact_status) contactVals.push(c.contact_status);
      if (c.referrer) referrers.push(c.referrer);
      var o = ownerLabel(c);
      if (o) owners.push(o);
    });
    host.innerHTML =
      '<div class="mk-leads-filters-grid">' +
      fieldSelect(
        t("LBL_MK_SC_FRANCHISE_STATUS", "Trạng thái"),
        "franchiseStatus",
        mergePickOptions(FRANCHISE_STATUS_OPTS, statusVals).map(function (v) {
          return [v, v];
        })
      ) +
      fieldSelect(
        t("LBL_MK_SC_DATA_SOURCE", "Nguồn data"),
        "dataSource",
        mergePickOptions(DATA_SOURCE_OPTS, sourceVals).map(function (v) {
          return [v, v];
        })
      ) +
      fieldSelect(
        t("LBL_MK_SC_CONTACT_STATUS", "Liên hệ"),
        "contactStatus",
        mergePickOptions(CONTACT_STATUS_OPTS, contactVals).map(function (v) {
          return [v, v];
        })
      ) +
      fieldSelect(
        t("LBL_MK_SC_REFERRER", "Người giới thiệu"),
        "referrer",
        uniqueSorted(referrers).map(function (v) {
          return [v, v];
        })
      ) +
      fieldSelect(
        t("LBL_MK_SC_SALE_OWNER", "Sale phụ trách"),
        "owner",
        uniqueSorted(owners).map(function (o) {
          return [o, o];
        })
      ) +
      "</div>";
    host.hidden = !state.filtersOpen;
    syncFilterControls();
  }

  function syncFilterControls() {
    var f = state.filters;
    document.querySelectorAll("#mk-sc-filters-panel [data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (key && f[key] != null) el.value = f[key];
    });
    var reset = $("mk-sc-reset");
    if (reset) {
      var dirty =
        f.search ||
        f.franchiseStatus !== ANY ||
        f.dataSource !== ANY ||
        f.contactStatus !== ANY ||
        f.referrer !== ANY ||
        f.owner !== ANY;
      reset.hidden = !dirty && !state.activeSegment;
    }
  }

  function closeTagPopover() {
    var pop = $("mk-sc-tag-popover");
    if (pop && pop.parentNode) pop.parentNode.removeChild(pop);
  }

  function openTagPopover(anchor, contract) {
    closeTagPopover();
    if (!ref || !ref.getCreateTagCatalog) return;
    var catalog = ref.getCreateTagCatalog();
    var selected = {};
    (contract.tags || []).forEach(function (tg) {
      selected[ref.normalizeTag(tg)] = true;
    });
    var pop = document.createElement("div");
    pop.id = "mk-sc-tag-popover";
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
        chip.classList.toggle("is-on");
        chip.setAttribute("aria-pressed", chip.classList.contains("is-on") ? "true" : "false");
        return;
      }
      if (
        e.target.closest &&
        (e.target.closest("[data-tag-cancel]") || e.target.closest(".mk-leads-tag-popover__close"))
      ) {
        closeTagPopover();
        return;
      }
      if (e.target.closest && e.target.closest("[data-tag-save]")) {
        var nextTags = [];
        pop.querySelectorAll(".mk-leads-tag-chip.is-on").forEach(function (el) {
          nextTags.push(el.getAttribute("data-tag"));
        });
        var saveBtn = e.target.closest("[data-tag-save]");
        if (saveBtn) saveBtn.disabled = true;
        var saveFn = store.saveTags
          ? store.saveTags(contract.crmid || contract.id, nextTags)
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

  function promptNextAction(contract) {
    var current = String(contract.next_action || "");
    var next = window.prompt(pick("Hành động tiếp theo", "Next action"), current);
    if (next === null) return;
    if (!store || !store.saveNextAction) return;
    store
      .saveNextAction(contract.crmid || contract.id, next)
      .then(function () {
        renderAll();
      })
      .catch(function () {
        window.alert("Không lưu được hành động tiếp theo.");
      });
  }

  function renderTable() {
    var all = getContracts();
    var rows = sortRows(filterRows(all));
    var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if (state.page > totalPages) state.page = 1;
    var start = (state.page - 1) * PAGE_SIZE;
    var pageRows = rows.slice(start, start + PAGE_SIZE);
    var tbody = $("mk-sc-tbody");
    if (!tbody) return;

    if (!pageRows.length) {
      tbody.innerHTML =
        '<tr><td colspan="' +
        COL_COUNT +
        '" class="mk-leads-empty">' +
        esc(t("JS_MK_NO_SC_MATCH", "Không có khách chuyển nhượng phù hợp bộ lọc.")) +
        "</td></tr>";
    } else {
      tbody.innerHTML = pageRows
        .map(function (c) {
          var crmId = c.crmid != null && c.crmid !== "" ? String(c.crmid) : String(c.id || "");
          var checked = state.selected[c.id] ? " checked" : "";
          var rowId = crmId || String(c.id || "");
          return (
            '<tr class="mk-leads-row mk-sc-row' +
            (state.selected[c.id] ? " mk-leads-row--selected" : "") +
            '" data-id="' +
            esc(c.id) +
            '"' +
            (crmId && /^\d+$/.test(crmId) ? ' data-crmid="' + esc(crmId) + '"' : "") +
            ">" +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-sc-row-check" data-id="' +
            esc(c.id) +
            '"' +
            checked +
            " />" +
            '<span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td">' +
            dateOnlyCell(c.received_date || c.createdtime) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--lead"><span class="mk-leads-lead-cell mk-sc-name-cell">' +
            '<span class="mk-leads-lead-text"><a class="mk-leads-name" href="' +
            detailUrl(c.crmid || c.id) +
            '">' +
            esc(c.name) +
            "</a>" +
            (c.affiliate_code
              ? '<div class="mk-leads-sub"><code class="mk-sc-aff-code">' + esc(c.affiliate_code) + "</code></div>"
              : c.contract_no
                ? '<div class="mk-leads-sub">' + esc(c.contract_no) + "</div>"
                : "") +
            (c.notes
              ? '<div class="mk-leads-sub mk-sc-name-note" title="' + esc(c.notes) + '">' + esc(c.notes.length > 60 ? c.notes.slice(0, 60) + "…" : c.notes) + "</div>"
              : "") +
            "</span></span></td>" +
            '<td class="mk-leads-td mk-leads-td--phone">' +
            phoneCell(c.phone) +
            "</td>" +
            '<td class="mk-leads-td">' +
            textCell(c.business_note || c.address, { max: 70 }) +
            "</td>" +
            '<td class="mk-leads-td">' +
            pillCell("franchise_status", c.franchise_status) +
            "</td>" +
            '<td class="mk-leads-td">' +
            pillCell("data_source", c.data_source) +
            "</td>" +
            '<td class="mk-leads-td">' +
            textCell(c.referrer, { max: 40 }) +
            "</td>" +
            '<td class="mk-leads-td">' +
            pillCell("contact_status", c.contact_status) +
            "</td>" +
            '<td class="mk-leads-td">' +
            interactionNoteCell("interaction_1", c.interaction_1, rowId) +
            "</td>" +
            '<td class="mk-leads-td">' +
            interactionNoteCell("interaction_2", c.interaction_2, rowId) +
            "</td>" +
            '<td class="mk-leads-td">' +
            interactionNoteCell("interaction_3", c.interaction_3, rowId) +
            "</td>" +
            '<td class="mk-leads-td mk-sc-td--notes">' +
            interactionNoteCell("interaction_materials", c.interaction_materials, rowId) +
            "</td></tr>"
          );
        })
        .join("");
    }

    var summary = $("mk-sc-filter-summary");
    if (summary) {
      summary.textContent =
        rows.length + " / " + all.length + " " + t("JS_MK_SC_COUNT_LABEL", "khách chuyển nhượng");
    }
    renderPagination(rows.length, totalPages);

    var checkAll = $("mk-sc-check-all");
    if (checkAll) {
      var allOnPage =
        pageRows.length > 0 &&
        pageRows.every(function (c) {
          return !!state.selected[c.id];
        });
      checkAll.checked = allOnPage;
      checkAll.indeterminate =
        !allOnPage &&
        pageRows.some(function (c) {
          return !!state.selected[c.id];
        });
    }
    renderBulkBar();
    syncFilterControls();
  }

  function selectedCount() {
    return Object.keys(state.selected).length;
  }

  function selectedRows() {
    return getContracts().filter(function (c) {
      return !!state.selected[c.id];
    });
  }

  function clearSelection() {
    state.selected = {};
    renderTable();
  }

  function renderBulkBar() {
    var bar = $("mk-sc-bulk");
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
    var lines = [
      "NgayTiepNhan,HoTen,SDT,DiaChiKinhDoanh,TrangThai,NguonData,NguoiGioiThieu,LienHe,TuongTac1,TuongTac2,TuongTac3,TuongTacMayMoc",
    ];
    rows.forEach(function (c) {
      var phone =
        window.MkPhoneFormat && typeof window.MkPhoneFormat.format === "function"
          ? window.MkPhoneFormat.format(c.phone || "")
          : c.phone || "";
      lines.push(
        [
          c.received_date || "",
          c.name || "",
          phone,
          c.business_note || c.address || "",
          c.franchise_status || "",
          c.data_source || "",
          c.referrer || "",
          c.contact_status || "",
          c.interaction_1 || "",
          c.interaction_2 || "",
          c.interaction_3 || "",
          c.interaction_materials || "",
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
    a.download = "khach-chuyen-nhuong.csv";
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  function renderPagination(total, totalPages) {
    var host = $("mk-sc-pagination");
    if (!host) return;
    if (totalPages <= 1) {
      host.innerHTML = "";
      return;
    }
    host.innerHTML =
      '<button type="button" class="mk-leads-page-btn" data-page="prev"' +
      (state.page <= 1 ? " disabled" : "") +
      ">‹</button>" +
      '<span class="mk-leads-page-info">' +
      esc(t("JS_MK_PAGE", "Trang")) +
      " " +
      state.page +
      " / " +
      totalPages +
      "</span>" +
      '<button type="button" class="mk-leads-page-btn" data-page="next"' +
      (state.page >= totalPages ? " disabled" : "") +
      ">›</button>";
  }

  function applySegment(segId) {
    if (segId === "__all__") {
      state.activeSegment = null;
      state.filters = Object.assign({}, EMPTY);
      state.page = 1;
      renderAll();
      return;
    }
    var seg = getPresetSegments().find(function (s) {
      return s.id === segId;
    });
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
    var rows = getContracts();
    renderKpi(rows);
    renderSegments();
    renderFiltersPanel();
    renderTable();
    document.documentElement.classList.add("mk-sc-list-ready");
  }

  function ensureScLastTouchModal() {
    var existing = document.getElementById("mk-sc-lt-modal");
    if (existing) return existing;
    var wrap = document.createElement("div");
    wrap.id = "mk-sc-lt-modal";
    wrap.className = "mk-lead-lt-modal mk-leads-lt-modal mk-sc-lt-modal";
    wrap.hidden = true;
    wrap.setAttribute("aria-hidden", "true");
    wrap.innerHTML =
      '<div class="mk-lead-lt-modal__backdrop" data-mk-sc-lt-close="1"></div>' +
      '<div class="mk-lead-lt-modal__dialog mk-sc-lt-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mk-sc-lt-title">' +
      '<div class="mk-lead-lt-modal__head">' +
      '<h3 id="mk-sc-lt-title">Ghi Last Touch — Call</h3>' +
      '<button type="button" class="mk-lead-lt-modal__x" data-mk-sc-lt-close="1" aria-label="Đóng">&times;</button>' +
      "</div>" +
      '<div class="mk-lead-lt-modal__body">' +
      '<p class="mk-lead-lt-modal__meta" id="mk-sc-lt-meta"></p>' +
      '<label class="mk-lead-lt-modal__label" for="mk-sc-lt-result">Kết quả cuộc gọi</label>' +
      '<select id="mk-sc-lt-result" class="mk-lead-lt-modal__select" autocomplete="off">' +
      '<option value="Không nghe máy">Không nghe máy</option>' +
      '<option value="Nghe máy">Nghe máy</option>' +
      "</select>" +
      '<label class="mk-lead-lt-modal__label" for="mk-sc-lt-note">Ghi chú</label>' +
      '<textarea id="mk-sc-lt-note" class="mk-lead-lt-modal__note inputElement" rows="6" placeholder="Ví dụ: Khách quan tâm mặt bằng / cần tư vấn thêm"></textarea>' +
      '<p class="mk-lead-lt-modal__tip">Chọn <strong>Nghe máy</strong> → Liên hệ = Đã gửi tư vấn (không sang Opp). Ghi chú Call #N hiện ở Tương tác lần N. <strong>Không nghe máy</strong> → nhắc sau khoảng 5 giờ.</p>' +
      "</div>" +
      '<div class="mk-lead-lt-modal__foot">' +
      '<button type="button" class="btn btn-default" data-mk-sc-lt-close="1">Hủy</button>' +
      '<button type="button" class="btn btn-success" id="mk-sc-lt-save">Lưu cuộc gọi</button>' +
      "</div></div>";
    document.body.appendChild(wrap);
    wrap.addEventListener("click", function (e) {
      var t = e.target;
      if (t && t.getAttribute && t.getAttribute("data-mk-sc-lt-close") === "1") {
        e.preventDefault();
        closeScLastTouchModal();
      }
    });
    var saveBtn = document.getElementById("mk-sc-lt-save");
    if (saveBtn) {
      saveBtn.addEventListener("click", function (e) {
        e.preventDefault();
        submitScLastTouchCall();
      });
    }
    return wrap;
  }

  function closeScLastTouchModal() {
    var modal = document.getElementById("mk-sc-lt-modal");
    if (!modal) return;
    modal.hidden = true;
    modal.setAttribute("aria-hidden", "true");
    modal._mkScId = "";
    modal._mkCallBtn = null;
  }

  function openScLastTouchModal(btn) {
    var panel = btn && btn.closest ? btn.closest(".mk-so-inline-detail") : null;
    var host = panel ? panel.querySelector('[data-role="last-touch"]') : null;
    var recordId = String(
      (btn && btn.getAttribute("data-record-id")) ||
        (host && host.getAttribute("data-record-id")) ||
        (panel && panel.getAttribute("data-record-id")) ||
        ""
    );
    if (!recordId) return;
    if (
      (btn && (btn.disabled || btn.classList.contains("is-locked"))) ||
      (host && host.getAttribute("data-lt-locked") === "1")
    ) {
      window.alert(
        (btn && btn.getAttribute("data-lt-hint")) ||
          (host && host.getAttribute("data-lt-hint")) ||
          "Không thể ghi thêm cuộc gọi Last Touch."
      );
      return;
    }
    var modal = ensureScLastTouchModal();
    var nextN =
      parseInt(
        (btn && btn.getAttribute("data-lt-next")) ||
          (host && host.getAttribute("data-lt-next")) ||
          "1",
        10
      ) || 1;
    var reminder = String(
      (btn && btn.getAttribute("data-lt-reminder")) ||
        (host && host.getAttribute("data-lt-reminder")) ||
        ""
    ).trim();
    var meta = document.getElementById("mk-sc-lt-meta");
    if (meta) {
      meta.textContent =
        "Ghi nhận Call #" +
        nextN +
        (reminder ? " · Nhắc lần trước: " + reminder : "") +
        ". Khoảng 5 giờ giữa các lần gọi (chuông Thông báo).";
    }
    var resultEl = document.getElementById("mk-sc-lt-result");
    var noteEl = document.getElementById("mk-sc-lt-note");
    if (resultEl) resultEl.value = "Không nghe máy";
    if (noteEl) noteEl.value = "";
    modal._mkScId = recordId;
    modal._mkCallBtn = btn;
    modal.hidden = false;
    modal.setAttribute("aria-hidden", "false");
  }

  function applyScLastTouchToPanel(panel, lt) {
    if (!panel || !lt) return;
    var host = panel.querySelector('[data-role="last-touch"]');
    if (!host) return;
    var canAdd = lt.can_add !== false;
    var nextN = lt.next_n || 1;
    var count = typeof lt.count === "number" ? lt.count : (lt.calls || []).length;
    var max = lt.max_calls || 3;
    var hint = lt.hint || "Call #1 → 5 giờ → #2 → #3. Không nghe máy: nhắc sau 5 giờ. Nghe máy → dừng chuỗi gọi.";
    host.setAttribute("data-lt-next", String(nextN));
    host.setAttribute("data-lt-hint", hint);
    host.setAttribute("data-lt-count", String(count));
    host.setAttribute("data-lt-max", String(max));
    if (lt.reminder_at_label) host.setAttribute("data-lt-reminder", lt.reminder_at_label);
    else host.removeAttribute("data-lt-reminder");
    if (canAdd) host.removeAttribute("data-lt-locked");
    else host.setAttribute("data-lt-locked", "1");
    var badge = host.querySelector('[data-role="lt-badge"]');
    if (badge) {
      badge.textContent = count + "/" + max;
      badge.className =
        "mk-so-inline-detail__last-touch-badge" + (canAdd ? " is-open" : " is-done");
    }
    var hintEl = host.querySelector('[data-role="lt-hint"]');
    if (hintEl) {
      hintEl.textContent = hint;
      hintEl.setAttribute("title", hint);
    }
    var list = host.querySelector('[data-role="lt-list"]');
    if (list) {
      var calls = lt.calls || [];
      if (!calls.length) {
        list.innerHTML =
          '<li class="mk-so-inline-detail__last-touch-empty">Chưa có Call #1 — bấm “Ghi cuộc gọi”.</li>';
      } else {
        list.innerHTML = calls
          .map(function (c) {
            var line =
              c.label ||
              (c.called_at_label || "") +
                " Call #" +
                (c.n || "") +
                " Kết quả: " +
                (c.result || "");
            return (
              '<li class="mk-so-inline-detail__last-touch-item">' +
              '<span class="mk-so-inline-detail__last-touch-n">Call #' +
              esc(String(c.n || "")) +
              "</span>" +
              '<span class="mk-so-inline-detail__last-touch-text">' +
              esc(line) +
              "</span></li>"
            );
          })
          .join("");
      }
    }
    var btn = host.querySelector(".mk-so-inline-detail__call-btn");
    if (btn) {
      btn.setAttribute("data-lt-next", String(nextN));
      btn.setAttribute("data-lt-hint", hint);
      var label = btn.querySelector("span");
      if (canAdd) {
        btn.classList.remove("is-locked");
        btn.disabled = false;
        btn.removeAttribute("aria-disabled");
        if (label) label.textContent = "Ghi cuộc gọi";
      } else {
        btn.classList.add("is-locked");
        btn.disabled = true;
        btn.setAttribute("aria-disabled", "true");
        if (label) label.textContent = "Đã đủ gọi";
      }
    }
  }

  function submitScLastTouchCall() {
    var modal = document.getElementById("mk-sc-lt-modal");
    var recordId = modal && modal._mkScId;
    if (!recordId) return;
    var resultEl = document.getElementById("mk-sc-lt-result");
    var noteEl = document.getElementById("mk-sc-lt-note");
    var saveBtn = document.getElementById("mk-sc-lt-save");
    var result = resultEl ? String(resultEl.value || "").trim() : "";
    var note = noteEl ? String(noteEl.value || "").trim() : "";
    if (!result) {
      window.alert("Vui lòng chọn kết quả cuộc gọi.");
      return;
    }
    if (saveBtn) saveBtn.disabled = true;
    if (window.app && app.helper && app.helper.showProgress) {
      app.helper.showProgress();
    }
    var postData = {
      module: "ServiceContracts",
      action: "ModernApi",
      mode: "last_touch_call_log",
      id: recordId,
      record: recordId,
      call_result: result,
      note: note,
    };
    function done(err, res) {
      if (saveBtn) saveBtn.disabled = false;
      if (window.app && app.helper && app.helper.hideProgress) {
        app.helper.hideProgress();
      }
      if (err || !res || res.success === false) {
        var msg =
          (err && err.message) ||
          (res && (res.error || res.message)) ||
          "Không ghi được cuộc gọi Last Touch.";
        if (window.app && app.helper && app.helper.showErrorNotification) {
          app.helper.showErrorNotification({ message: String(msg) });
        } else {
          window.alert(String(msg));
        }
        return;
      }
      var lt = res.lastTouchCalls || res;
      if (store && typeof store.patchContract === "function") {
        var patch = { lastTouchCalls: lt };
        if (res.contract) {
          patch.contact_status = res.contract.contact_status || "";
          patch.last_touch = res.contract.last_touch || "";
          patch.interaction_1 = res.contract.interaction_1;
          patch.interaction_2 = res.contract.interaction_2;
          patch.interaction_3 = res.contract.interaction_3;
          patch.interaction_materials = res.contract.interaction_materials;
        }
        store.patchContract(String(recordId), patch);
      }
      var panel = document.querySelector(
        '.mk-so-inline-detail[data-record-id="' + recordId + '"]'
      );
      applyScLastTouchToPanel(panel, lt);
      if (panel && res.contract) {
        var c = res.contract;
        function patchField(name, val) {
          var field = panel.querySelector(
            '.mk-so-inline-detail__field[data-field-name="' + name + '"]'
          );
          if (!field) return;
          var view = field.querySelector(".mk-so-inline-detail__field-view");
          if (view) view.textContent = val || "—";
          var input = field.querySelector('[name="' + name + '"]');
          if (input) input.value = val || "";
        }
        patchField("contact_status", c.contact_status);
        patchField("interaction_1", c.interaction_1);
        patchField("interaction_2", c.interaction_2);
        patchField("interaction_3", c.interaction_3);
      }
      closeScLastTouchModal();
      renderTable();
      if (window.app && app.helper && app.helper.showSuccessNotification) {
        app.helper.showSuccessNotification({
          message: (res.logged && res.logged.label) || "Đã ghi Last Touch Call.",
        });
      }
    }
    if (window.app && app.request && app.request.post) {
      app.request.post({ data: postData }).then(done);
    } else {
      fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: Object.keys(postData)
          .map(function (k) {
            return encodeURIComponent(k) + "=" + encodeURIComponent(postData[k]);
          })
          .join("&"),
        credentials: "same-origin",
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (r) {
          done(null, r && r.result ? r.result : r);
        })
        .catch(function () {
          done({ message: "Không kết nối được máy chủ." }, null);
        });
    }
  }

  function bindEvents() {
    var search = $("mk-sc-search");
    if (search) {
      search.addEventListener("input", function () {
        state.filters.search = search.value;
        state.page = 1;
        renderTable();
        renderKpi(filterRows(getContracts()));
      });
    }

    var toggle = $("mk-sc-filters-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        state.filtersOpen = !state.filtersOpen;
        toggle.setAttribute("aria-expanded", state.filtersOpen ? "true" : "false");
        var panel = $("mk-sc-filters-panel");
        if (panel) panel.hidden = !state.filtersOpen;
      });
    }

    document.addEventListener("mk-sc-inline-saved", function (e) {
      var detail = (e && e.detail) || {};
      var c = detail.contract || {};
      var id = String(detail.id || c.id || "");
      if (!id || !store || typeof store.patchContract !== "function") return;
      store.patchContract(id, {
        franchise_status: c.franchise_status || "",
        contact_status: c.contact_status || "",
        data_source: c.data_source || "",
        phone: c.phone || "",
        business_note: c.business_note || "",
        referrer: c.referrer || "",
        sale_owner: c.sale_owner || "",
        notes: c.notes || c.description || "",
        interaction_1: c.interaction_1 || "",
        interaction_2: c.interaction_2 || "",
        interaction_3: c.interaction_3 || "",
        interaction_materials: c.interaction_materials || "",
        lastTouchCalls: c.lastTouchCalls || detail.lastTouchCalls || undefined,
      });
      renderTable();
    });

    document.addEventListener("click", function (e) {
      var callBtn =
        e.target && e.target.closest && e.target.closest(".mk-so-inline-detail__call-btn");
      if (callBtn) {
        e.preventDefault();
        e.stopPropagation();
        openScLastTouchModal(callBtn);
      }
    });

    document.addEventListener("change", function (e) {
      var el = e.target;
      if (!el) return;
      if (el.classList && el.classList.contains("mk-sc-ix-note")) {
        e.stopPropagation();
        var rid = el.getAttribute("data-id");
        var field = el.getAttribute("data-field");
        var next = String(el.value || "").trim();
        var prev = String(el.getAttribute("data-prev") || "").trim();
        if (rid && field && next !== prev) {
          el.setAttribute("data-prev", next);
          saveInteractionField(rid, field, next);
        }
        return;
      }
      if (el.classList && el.classList.contains("mk-sc-ix-select")) {
        e.stopPropagation();
        var ridSel = el.getAttribute("data-id");
        var fieldSel = el.getAttribute("data-field");
        if (ridSel && fieldSel) {
          saveInteractionField(ridSel, fieldSel, el.value || "");
        }
        return;
      }
      if (el.classList && el.classList.contains("mk-sc-row-check")) {
        var id = el.getAttribute("data-id");
        if (el.checked) state.selected[id] = true;
        else delete state.selected[id];
        renderTable();
        return;
      }
      if (el.id === "mk-sc-check-all") {
        var pageRows = sortRows(filterRows(getContracts())).slice(
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
      if (!el.getAttribute || !el.closest("#mk-sc-filters-panel")) return;
      var key = el.getAttribute("data-fkey");
      if (!key) return;
      state.filters[key] = el.value;
      state.activeSegment = null;
      state.page = 1;
      renderAll();
    });

    document.addEventListener("click", function (e) {
      var sortTh = e.target.closest && e.target.closest("#mk-sc-table th[data-sort]");
      if (sortTh) {
        var sk = sortTh.getAttribute("data-sort");
        if (state.sortKey === sk) {
          state.sortDir = state.sortDir === "asc" ? "desc" : "asc";
        } else {
          state.sortKey = sk;
          state.sortDir = sk === "name" ? "asc" : "desc";
        }
        renderTable();
        return;
      }

      var tagsBtn = e.target.closest && e.target.closest(".mk-leads-tags-edit[data-sc-id]");
      if (tagsBtn) {
        e.preventDefault();
        e.stopPropagation();
        var cid = tagsBtn.getAttribute("data-sc-id");
        var contract = getContracts().find(function (c) {
          return String(c.id) === String(cid);
        });
        if (contract) openTagPopover(tagsBtn, contract);
        return;
      }

      var nextBtn = e.target.closest && e.target.closest("[data-sc-next]");
      if (nextBtn) {
        e.preventDefault();
        e.stopPropagation();
        var nid = nextBtn.getAttribute("data-sc-next");
        var row = getContracts().find(function (c) {
          return String(c.id) === String(nid);
        });
        if (row) promptNextAction(row);
        return;
      }

      if (!e.target.closest || !e.target.closest("#mk-sc-tag-popover")) {
        closeTagPopover();
      }

      var bulkBtn = e.target.closest && e.target.closest("#mk-sc-bulk [data-bulk]");
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
        if (!window.confirm("Xóa " + rows.length + " khách chuyển nhượng đã chọn?")) return;
        if (!store || !store.remove) return;
        Promise.all(
          rows.map(function (c) {
            return store.remove(c.id);
          })
        ).then(function () {
          clearSelection();
          renderAll();
        });
      }
    });

    var segHost = $("mk-sc-segments");
    if (segHost) {
      segHost.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-seg]");
        if (!btn) return;
        applySegment(btn.getAttribute("data-seg"));
      });
    }

    var pag = $("mk-sc-pagination");
    if (pag) {
      pag.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-page]");
        if (!btn || btn.disabled) return;
        if (btn.getAttribute("data-page") === "prev") state.page--;
        else state.page++;
        renderTable();
      });
    }

    var reset = $("mk-sc-reset");
    if (reset) {
      reset.addEventListener("click", function () {
        state.filters = Object.assign({}, EMPTY);
        state.activeSegment = null;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      });
    }

    if ($("mk-sc-import-ic")) $("mk-sc-import-ic").innerHTML = ic("import");
    if ($("mk-sc-create-ic")) $("mk-sc-create-ic").innerHTML = ic("plus");
    if ($("mk-sc-search-ic")) $("mk-sc-search-ic").innerHTML = ic("search");
    if ($("mk-sc-segments-icon")) $("mk-sc-segments-icon").innerHTML = ic("filter");
    if ($("mk-sc-filters-ic")) $("mk-sc-filters-ic").innerHTML = ic("filter");
  }

  function init() {
    if (!document.querySelector(".mk-sc-page--lovable")) return;
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
