/* Leads list — b-ace.lovable.app/leads (localStorage cache, no DB) */
(function () {
  "use strict";

  var ANY = "__any__";
  var PAGE_SIZE = 15;
  var logic = window.LeadsLeadsLogic;
  var ref = window.LeadsLovableRef;
  var store = window.LeadsLocalStore;
  var icons = window.LeadsMkIcons;

  function ic(name) {
    return icons && icons.get ? icons.get(name) : "";
  }

  var SOURCE_TAGS = ["facebook", "tiktok", "website", "zalo", "other"];
  var PROGRAM_TAGS = ["mien_phi_online", "mien_phi_offline", "pcth", "van_hanh", "mkt", "lop_khac", "nhuong_quyen"];
  var PURCHASE_TAGS = ["mua_lan_dau", "mua_lai", "khong_mua", "ngung_mua"];
  var TIER_TAGS = ["vang", "bac", "dong"];
  var CUSTOMER_TAGS = ["individual", "company", "ca_nhan", "co_quan", "chuan_bi_mo", "gia_dinh"];
  var REGION_TAGS = ["kv1", "kv2", "kv3"];
  // Hide tags that already have dedicated list columns.
  var COLUMN_TAG_KEYS = SOURCE_TAGS.concat(CUSTOMER_TAGS, REGION_TAGS);

  function t(key, fallback) {
    if (typeof app !== "undefined" && app.vtranslate) {
      var translated = app.vtranslate(key);
      if (translated && translated !== key) return translated;
    }
    return fallback || key;
  }

  function isVi() {
    return ref && ref.isVi ? ref.isVi() : true;
  }

  function getPresetSegments() {
    if (ref && ref.getPresetSegments) return ref.getPresetSegments();
    return [
      { id: "new", name: "Khách mới", filters: { purchase: "mua_lan_dau" } },
      { id: "gold", name: "Khách vàng", filters: { tier: "vang" } },
      { id: "repeat", name: "Khách mua lại", filters: { purchase: "mua_lai" } },
      { id: "nobuy", name: "Khách không mua", filters: { purchase: "khong_mua" } },
      { id: "chain", name: "Khách chuỗi (PCTH)", filters: { program: "pcth" } },
      { id: "franchise", name: "Khách nhượng quyền", filters: { program: "nhuong_quyen" } },
      { id: "cskh", name: "Khách cần CSKH", filters: { staleOnly: true } },
    ];
  }

  var PRESET_SEGMENTS = getPresetSegments();

  var EMPTY = {
    search: "",
    source: ANY,
    program: ANY,
    purchase: ANY,
    tier: ANY,
    owner: ANY,
    area: ANY,
    segment: ANY,
    touchRange: "any",
    staleOnly: false,
    hasNextAction: false,
    hasOpenTicket: false,
  };

  var state = {
    filters: Object.assign({}, EMPTY),
    sortKey: "last_touch",
    sortDir: "desc",
    page: 1,
    selected: {},
    activeSegment: null,
    filtersOpen: true,
  };

  function $(id) {
    return document.getElementById(id);
  }

  function tagMeta(t) {
    return ref && ref.tagMeta ? ref.tagMeta(t) : { label: t, cls: "mk-tag", key: t };
  }

  function normalizeTagKey(tag) {
    if (ref && ref.normalizeTagKey) return ref.normalizeTagKey(tag);
    return String(tag || "").trim();
  }

  function resolveTagLabel(tag, labelOverride) {
    if (labelOverride) return labelOverride;
    if (ref && ref.labelForTag) return ref.labelForTag(tag);
    var m = tagMeta(tag);
    return (m && m.label) || tag;
  }

  function isColumnTag(tag, segmentKey, typeTag) {
    var key = normalizeTagKey(tag);
    if (!key) return true;
    if (COLUMN_TAG_KEYS.indexOf(key) >= 0) return true;
    if (segmentKey && key === normalizeTagKey(segmentKey)) return true;
    if (typeTag && key === normalizeTagKey(typeTag)) return true;
    return false;
  }

  function displayTagsForLead(lead) {
    var segmentKey = lead && lead.segment ? normalizeTagKey(lead.segment) : null;
    var typeTag = (lead.tags || []).find(function (tg) {
      var k = normalizeTagKey(tg);
      return k === "individual" || k === "company" || k === "ca_nhan";
    });
    if (typeTag) typeTag = normalizeTagKey(typeTag);
    var seen = {};
    var out = [];
    (lead.tags || []).forEach(function (tg) {
      var key = normalizeTagKey(tg);
      if (!key || seen[key]) return;
      if (isColumnTag(key, segmentKey, typeTag)) return;
      seen[key] = true;
      out.push(key);
    });
    return out;
  }

  function regionKeyOf(lead) {
    var tags = (lead && lead.tags) || [];
    var i;
    for (i = 0; i < tags.length; i++) {
      var k = normalizeTagKey(tags[i]);
      if (REGION_TAGS.indexOf(k) >= 0) return k;
    }
    var district = String((lead && lead.district) || "").trim();
    var m = district.match(/khu\s*v[ựuùúủũụ]\s*c\s*([123])/i) || district.match(/^kv\s*([123])$/i);
    if (!m) {
      m = String((lead && lead.area) || "").match(/khu\s*v[ựuùúủũụ]\s*c\s*([123])/i);
    }
    if (m) return "kv" + m[1];
    return "";
  }

  function addressOf(lead) {
    if (!lead) return "";
    var address = String(lead.address || "").trim();
    if (address) return address;
    var area = String(lead.area || "").trim();
    if (!area) return "";
    // "Khu vực 1, 131 da ad" → take the address part
    var split = area.match(/^khu\s*v[ựuùúủũụ]\s*c\s*[123]\s*,\s*(.+)$/i);
    if (split) return split[1].trim();
    // Plain area that is actually an address (no KV prefix)
    if (!/^khu\s*v[ựuùúủũụ]\s*c\s*[123]$/i.test(area) && !/^kv[123]$/i.test(area)) {
      return area;
    }
    return "";
  }

  function regionSelectHtml(leadId, regionKey) {
    var opts = [
      ["", "— Chọn khu vực —"],
      ["kv1", "Khu vực 1"],
      ["kv2", "Khu vực 2"],
      ["kv3", "Khu vực 3"],
    ];
    return (
      '<select class="mk-leads-region-select" data-field="region" data-lead-id="' +
      esc(leadId) +
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

  function editableCellHtml(field, value, leadId, placeholder) {
    var display = value
      ? esc(value)
      : '<span class="mk-leads-muted">' + esc(placeholder || "—") + "</span>";
    return (
      '<button type="button" class="mk-leads-inline-edit" data-field="' +
      esc(field) +
      '" data-lead-id="' +
      esc(leadId) +
      '" title="Nhấn để sửa">' +
      display +
      "</button>"
    );
  }

  function commitInlineEdit(input) {
    if (!input || !input.getAttribute) return;
    var field = input.getAttribute("data-field");
    var leadId = input.getAttribute("data-lead-id");
    if (!field || !leadId || !store || !store.update) {
      renderTable();
      return;
    }
    var val = input.value.trim();
    if (field === "phone" && val && !/^\d{10}$/.test(val.replace(/\s+/g, ""))) {
      window.alert("Số điện thoại phải đủ 10 số.");
      renderTable();
      return;
    }
    if (field === "phone") {
      val = val.replace(/\s+/g, "");
    }
    var patch = {};
    if (field === "address") {
      patch.address = val;
      var lead = getLeads().find(function (l) {
        return String(l.id) === String(leadId) || String(leadCrmId(l)) === String(leadId);
      });
      var regionKey = regionKeyOf(lead || {});
      var districtLabel = regionKey ? "Khu vực " + regionKey.replace("kv", "") : (lead && lead.district) || "";
      patch.district = districtLabel;
      patch.area = districtLabel && val ? districtLabel + ", " + val : districtLabel || val;
    } else {
      patch[field] = val;
    }
    input.disabled = true;
    store
      .update(leadId, patch)
      .then(function () {
        renderTable();
      })
      .catch(function (err) {
        console.error("Inline save failed", err);
        window.alert("Không lưu được " + field + ".");
        renderTable();
      });
  }

  function commitRegionChange(select) {
    if (!select || !store || !store.update) return;
    var leadId = select.getAttribute("data-lead-id");
    var regionKey = select.value;
    var lead = getLeads().find(function (l) {
      return String(l.id) === String(leadId) || String(leadCrmId(l)) === String(leadId);
    });
    if (!lead) return;
    var tags = (lead.tags || [])
      .map(normalizeTagKey)
      .filter(function (k) {
        return k && REGION_TAGS.indexOf(k) < 0;
      });
    if (regionKey) tags.push(regionKey);
    var districtLabel = regionKey ? "Khu vực " + regionKey.replace("kv", "") : "";
    var addr = addressOf(lead);
    select.disabled = true;
    store
      .update(leadId, {
        tags: tags,
        district: districtLabel,
        address: addr,
        area: districtLabel && addr ? districtLabel + ", " + addr : districtLabel || addr,
      })
      .then(function () {
        renderTable();
      })
      .catch(function (err) {
        console.error(err);
        window.alert("Không lưu được khu vực.");
        renderTable();
      });
  }

  function beginInlineEdit(btn) {
    if (!btn || !btn.getAttribute) return;
    var field = btn.getAttribute("data-field");
    var leadId = btn.getAttribute("data-lead-id");
    var current = btn.textContent.trim();
    if (current === "—") current = "";
    var input = document.createElement("input");
    input.type = field === "phone" ? "tel" : "text";
    input.className = "mk-leads-inline-input";
    input.value = current;
    input.setAttribute("data-field", field);
    input.setAttribute("data-lead-id", leadId);
    if (field === "phone") {
      input.setAttribute("inputmode", "numeric");
      input.setAttribute("maxlength", "10");
    }
    btn.replaceWith(input);
    input.focus();
    input.select();
  }

  function esc(s) {
    return String(s || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/"/g, "&quot;");
  }

  function getLeads() {
    return store ? store.getLeads() : [];
  }

  function leadCrmId(lead) {
    if (!lead) return "";
    return lead.crmid != null && lead.crmid !== "" ? String(lead.crmid) : String(lead.id || "");
  }

  function isLeadConvertible(lead) {
    if (!lead) return false;
    return lead.canConvert !== false && !lead.converted && !lead.potentialId;
  }

  function convertSingleLead(leadId, orderCategory) {
    return new Promise(function (resolve, reject) {
      if (!window.app || !app.request || !app.request.post) {
        reject(new Error("app.request unavailable"));
        return;
      }
      app.request
        .post({
          data: {
            module: "Leads",
            action: "ModernApi",
            mode: "convert",
            id: leadId,
            order_category: orderCategory,
          },
        })
        .then(function (err, res) {
          if (err || !res || res.success === false) {
            reject(err || (res && res.error) || new Error("Convert failed"));
            return;
          }
          resolve(res);
        });
    });
  }

  function promptOrderCategory() {
    var fallback = window.prompt("Loại Cơ hội: gõ Internal (Nội bộ) hoặc Project (Dự án)", "Internal");
    if (fallback === null) return null;
    var cat = String(fallback).trim();
    if (cat !== "Internal" && cat !== "Project") {
      window.alert("Chỉ chấp nhận Internal (Nội bộ) hoặc Project (Dự án).");
      return null;
    }
    return cat;
  }

  function openConvertOrderCategoryModal(options) {
    options = options || {};
    var introHtml = options.introHtml || "";
    var onConfirm = options.onConfirm || function () {};
    var radioName = options.radioName || "mk_leads_convert_order_category";

    if (!window.app || !app.helper || !app.helper.showModal) {
      var cat = promptOrderCategory();
      if (cat) onConfirm(cat);
      return;
    }

    var modalHtml =
      '<div class="modal-dialog mk-lead-convert-modal mk-leads-bulk-convert-modal">' +
      '<div class="modal-content">' +
      '<div class="modal-header">' +
      '<button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>' +
      '<h4 class="modal-title">Chuyển sang Cơ hội</h4>' +
      "</div>" +
      '<div class="modal-body">' +
      '<p class="mk-lead-convert-modal__intro">' +
      introHtml +
      "</p>" +
      '<div class="mk-lead-convert-modal__choices" role="radiogroup" aria-label="Loại đơn hàng">' +
      '<label class="mk-lead-convert-modal__choice is-selected">' +
      '<input type="radio" name="' +
      radioName +
      '" value="Internal" checked />' +
      '<span class="mk-lead-convert-modal__choice-body">' +
      '<span class="mk-lead-convert-modal__choice-title">Nội bộ</span>' +
      '<span class="mk-lead-convert-modal__choice-desc">Đơn nội bộ / bán hàng thông thường</span>' +
      "</span></label>" +
      '<label class="mk-lead-convert-modal__choice">' +
      '<input type="radio" name="' +
      radioName +
      '" value="Project" />' +
      '<span class="mk-lead-convert-modal__choice-body">' +
      '<span class="mk-lead-convert-modal__choice-title">Dự án</span>' +
      '<span class="mk-lead-convert-modal__choice-desc">Đơn dự án / triển khai theo dự án</span>' +
      "</span></label>" +
      "</div></div>" +
      '<div class="modal-footer">' +
      '<button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>' +
      '<button type="button" class="btn btn-primary mk-leads-bulk-convert-modal__submit">Chuyển sang Cơ hội</button>' +
      "</div></div></div>";

    app.helper.showModal(modalHtml, {
      backdrop: "static",
      keyboard: false,
      cb: function (container) {
        var $root = container.find(".mk-leads-bulk-convert-modal");
        $root.find(".mk-lead-convert-modal__choice").on("click", function () {
          $root.find(".mk-lead-convert-modal__choice").removeClass("is-selected");
          window.jQuery(this).addClass("is-selected");
          window.jQuery(this).find('input[type="radio"]').prop("checked", true);
        });
        $root.find(".mk-leads-bulk-convert-modal__submit").on("click", function () {
          var cat = $root.find('input[name="' + radioName + '"]:checked').val();
          if (cat !== "Internal" && cat !== "Project") {
            window.alert("Vui lòng chọn Nội bộ hoặc Dự án.");
            return;
          }
          app.helper.hideModal();
          onConfirm(cat);
        });
      },
    });
  }

  function openBulkConvertModal(rows) {
    var convertible = rows.filter(isLeadConvertible);
    if (!convertible.length) {
      window.alert("Các lead đã chọn đều đã chuyển sang Cơ hội hoặc không thể chuyển.");
      return;
    }
    openConvertOrderCategoryModal({
      radioName: "mk_leads_bulk_convert_order_category",
      introHtml:
        "Chuyển <strong>" +
        convertible.length +
        "</strong> lead đã chọn sang <strong>Liên hệ + Tổ chức + Cơ hội</strong>. Chọn loại đơn hàng:",
      onConfirm: function (cat) {
        runBulkConvert(rows, cat);
      },
    });
  }

  function markInlineConvertDone(btn, potentialUrl) {
    if (!btn) return;
    btn.classList.add("is-converted");
    btn.disabled = true;
    btn.setAttribute("aria-disabled", "true");
    btn.setAttribute("title", "Đã chuyển sang Cơ hội");
    var label = btn.querySelector("span");
    if (label) label.textContent = "Đã chuyển";
    if (potentialUrl) btn.setAttribute("data-potential-url", potentialUrl);
  }

  function escHtml(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function decodeHtmlEntities(s) {
    if (logic && logic.decodeHtmlEntities) return logic.decodeHtmlEntities(s);
    var ta = document.createElement("textarea");
    ta.innerHTML = String(s == null ? "" : s);
    return ta.value;
  }

  function renderInlineLastTouchPanel(panelOrBtn, lt) {
    if (!lt) return;
    var panel =
      panelOrBtn && panelOrBtn.classList && panelOrBtn.classList.contains("mk-so-inline-detail")
        ? panelOrBtn
        : panelOrBtn && panelOrBtn.closest
          ? panelOrBtn.closest(".mk-so-inline-detail")
          : null;
    if (!panel) return;
    var host = panel.querySelector('[data-role="last-touch"]');
    if (!host) return;

    var canAdd = lt.can_add !== false;
    var nextN = lt.next_n || 1;
    var count = typeof lt.count === "number" ? lt.count : (lt.calls || []).length;
    var max = lt.max_calls || 3;
    var SHORT_LT_HINT =
      "Gọi #1 → 5 giờ → #2 → #3. Không nghe máy: nhắc sau 5 giờ. Nghe máy → Cơ hội.";
    var hint =
      lt.count > 0 || lt.can_add === false
        ? lt.hint || SHORT_LT_HINT
        : SHORT_LT_HINT;
    var hintFull = lt.hint || SHORT_LT_HINT;
    var reminder = lt.reminder_at_label || "";

    host.setAttribute("data-lt-next", String(nextN));
    host.setAttribute("data-lt-hint", hintFull);
    host.setAttribute("data-lt-count", String(count));
    host.setAttribute("data-lt-max", String(max));
    if (reminder) host.setAttribute("data-lt-reminder", reminder);
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
      hintEl.setAttribute("title", hintFull);
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
            if (c.note && String(line).indexOf("Ghi chú:") < 0) {
              line += " Ghi chú: " + c.note;
            }
            return (
              '<li class="mk-so-inline-detail__last-touch-item">' +
              '<span class="mk-so-inline-detail__last-touch-n">Call #' +
              escHtml(String(c.n || "")) +
              "</span>" +
              '<span class="mk-so-inline-detail__last-touch-text">' +
              escHtml(decodeHtmlEntities(line)) +
              "</span></li>"
            );
          })
          .join("");
      }
    }

    var btn = host.querySelector(".mk-so-inline-detail__call-btn");
    if (!btn) return;
    btn.setAttribute("data-lt-next", String(nextN));
    btn.setAttribute("data-lt-hint", hint);
    if (reminder) btn.setAttribute("data-lt-reminder", reminder);
    else btn.removeAttribute("data-lt-reminder");
    var label = btn.querySelector("span");
    if (canAdd) {
      btn.classList.remove("is-locked");
      btn.disabled = false;
      btn.removeAttribute("aria-disabled");
      btn.setAttribute("title", "Ghi cuộc gọi Last Touch #" + nextN);
      if (label) label.textContent = "Ghi cuộc gọi";
    } else {
      markInlineCallLocked(btn, hint);
      if (label) label.textContent = "Đã đủ gọi";
    }
  }

  function ensureListLastTouchModal() {
    var existing = document.getElementById("mk-leads-lt-modal");
    if (existing) return existing;
    var wrap = document.createElement("div");
    wrap.id = "mk-leads-lt-modal";
    wrap.className = "mk-lead-lt-modal mk-leads-lt-modal";
    wrap.hidden = true;
    wrap.setAttribute("aria-hidden", "true");
    wrap.innerHTML =
      '<div class="mk-lead-lt-modal__backdrop" data-mk-lt-close="1"></div>' +
      '<div class="mk-lead-lt-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mk-leads-lt-title">' +
      '<div class="mk-lead-lt-modal__head">' +
      '<h3 id="mk-leads-lt-title">Ghi Last Touch — Call</h3>' +
      '<button type="button" class="mk-lead-lt-modal__x" data-mk-lt-close="1" aria-label="Đóng">&times;</button>' +
      "</div>" +
      '<div class="mk-lead-lt-modal__body">' +
      '<p class="mk-lead-lt-modal__meta" id="mk-leads-lt-meta"></p>' +
      '<label class="mk-lead-lt-modal__label" for="mk-leads-lt-result">Kết quả cuộc gọi</label>' +
      '<select id="mk-leads-lt-result" class="mk-lead-lt-modal__select inputElement">' +
      '<option value="Không nghe máy">Không nghe máy</option>' +
      '<option value="Nghe máy">Nghe máy</option>' +
      "</select>" +
      '<label class="mk-lead-lt-modal__label" for="mk-leads-lt-note">Ghi chú</label>' +
      '<textarea id="mk-leads-lt-note" class="mk-lead-lt-modal__note inputElement" rows="3" placeholder="Ví dụ: Khách quan tâm lớp học"></textarea>' +
      '<p class="mk-lead-lt-modal__tip">Chọn <strong>Nghe máy</strong> sẽ tự chuyển Lead sang Cơ hội. <strong>Không nghe máy</strong> → nhắc gọi lần sau sau khoảng 5 giờ.</p>' +
      "</div>" +
      '<div class="mk-lead-lt-modal__foot">' +
      '<button type="button" class="btn btn-default" data-mk-lt-close="1">Hủy</button>' +
      '<button type="button" class="btn btn-success" id="mk-leads-lt-save">Lưu cuộc gọi</button>' +
      "</div></div>";
    document.body.appendChild(wrap);
    wrap.addEventListener("click", function (e) {
      var t = e.target;
      if (t && t.getAttribute && t.getAttribute("data-mk-lt-close") === "1") {
        e.preventDefault();
        closeListLastTouchModal();
      }
    });
    var saveBtn = document.getElementById("mk-leads-lt-save");
    if (saveBtn) {
      saveBtn.addEventListener("click", function (e) {
        e.preventDefault();
        submitListLastTouchCall();
      });
    }
    return wrap;
  }

  function closeListLastTouchModal() {
    var modal = document.getElementById("mk-leads-lt-modal");
    if (!modal) return;
    modal.hidden = true;
    modal.setAttribute("aria-hidden", "true");
    modal._mkLeadId = "";
    modal._mkCallBtn = null;
  }

  function openListLastTouchModal(btn) {
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

    var modal = ensureListLastTouchModal();
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
    var meta = document.getElementById("mk-leads-lt-meta");
    if (meta) {
      meta.textContent =
        "Ghi nhận Call #" +
        nextN +
        (reminder ? " · Nhắc lần trước: " + reminder : "") +
        ". Khoảng 5 giờ giữa các lần gọi (chuông Thông báo).";
    }
    var resultEl = document.getElementById("mk-leads-lt-result");
    var noteEl = document.getElementById("mk-leads-lt-note");
    if (resultEl) resultEl.value = "Không nghe máy";
    if (noteEl) noteEl.value = "";
    modal._mkLeadId = recordId;
    modal._mkCallBtn = btn;
    modal.hidden = false;
    modal.setAttribute("aria-hidden", "false");
  }

  function markInlineCallLocked(btn, hint) {
    if (!btn) return;
    btn.classList.add("is-locked");
    btn.disabled = true;
    btn.setAttribute("aria-disabled", "true");
    if (hint) {
      btn.setAttribute("data-lt-hint", hint);
      btn.setAttribute("title", hint);
    }
  }

  function submitListLastTouchCall() {
    var modal = document.getElementById("mk-leads-lt-modal");
    var leadId = modal && modal._mkLeadId;
    var callBtn = modal && modal._mkCallBtn;
    if (!leadId) return;
    var resultEl = document.getElementById("mk-leads-lt-result");
    var noteEl = document.getElementById("mk-leads-lt-note");
    var saveBtn = document.getElementById("mk-leads-lt-save");
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
      module: "Leads",
      action: "ModernApi",
      mode: "last_touch_call_log",
      id: leadId,
      record: leadId,
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
          (res && res.error) ||
          "Không ghi được cuộc gọi Last Touch.";
        if (typeof msg === "object" && msg.message) msg = msg.message;
        window.alert(String(msg));
        return;
      }
      closeListLastTouchModal();
      var lt = res.lastTouchCalls || null;
      var lead = findLeadByRecordId(leadId);
      if (lead && lt) {
        lead.lastTouchCalls = lt;
        if (lt.logged && lt.logged.called_at) {
          lead.last_touch = lt.logged.called_at;
        }
      }
      if (callBtn) {
        renderInlineLastTouchPanel(callBtn, lt);
      }
      var convert = res.convert || (lt && lt.convert) || null;
      var panel = callBtn && callBtn.closest ? callBtn.closest(".mk-so-inline-detail") : null;
      var parentRow = panel && panel.closest
        ? panel.closest("tr.mk-so-inline-detail-row")
        : null;
      var dataRow = parentRow && parentRow.previousElementSibling
        ? parentRow.previousElementSibling
        : null;
      if (dataRow && dataRow.classList && dataRow.classList.contains("mk-leads-row") && lead) {
        var touchTd = dataRow.querySelector(".mk-leads-td--touch");
        if (touchTd && logic && logic.lastTouchCallLogHtml) {
          touchTd.innerHTML = logic.lastTouchCallLogHtml(lead, escHtml);
          var meta = logic.derive ? logic.derive(lead) : null;
          if (meta) {
            touchTd.classList.toggle("mk-leads-td--stale", !!meta.stale);
          }
        }
        if (lt && lt.lead && lt.lead.next_action != null && panel) {
          var nextTa = panel.querySelector(".mk-so-inline-detail__next-action-input");
          if (nextTa) nextTa.value = String(lt.lead.next_action || "");
        }
      }
      var refresh =
        store && store.refreshLeadsList ? store.refreshLeadsList() : Promise.resolve();
      refresh.then(function () {
        if (result === "Nghe máy" && convert) {
          renderAll();
          var url =
            convert.redirect ||
            convert.potentialUrl ||
            (convert.potentialId
              ? "index.php?module=Potentials&view=Detail&record=" +
                convert.potentialId +
                "&app=SALES"
              : "");
          if (window.app && app.helper && app.helper.showSuccessNotification) {
            app.helper.showSuccessNotification({
              message: "Nghe máy — đã chuyển sang Cơ hội.",
            });
          } else {
            window.alert("Nghe máy — đã chuyển sang Cơ hội.");
          }
          if (url && window.confirm("Mở Cơ hội vừa tạo?")) {
            window.location.href = url;
          }
          return;
        }
        var logged = (lt && lt.logged) || {};
        if (window.app && app.helper && app.helper.showSuccessNotification) {
          app.helper.showSuccessNotification({
            message: logged.label || "Đã ghi Last Touch Call.",
          });
        }
      });
    }
    if (typeof app !== "undefined" && app.request && app.request.post) {
      app.request.post({ data: postData }).then(function (err, res) {
        done(err, res);
      });
    } else {
      done({ message: "Không kết nối được máy chủ." }, null);
    }
  }

  function findLeadByRecordId(recordId) {
    var id = String(recordId || "");
    if (!id) return null;
    var leads = getLeads();
    for (var i = 0; i < leads.length; i++) {
      var lead = leads[i];
      if (leadCrmId(lead) === id || String(lead.id) === id) return lead;
    }
    return { id: id, crmid: id, canConvert: true };
  }

  function openInlineConvertModal(btn) {
    var recordId = String((btn && btn.getAttribute("data-record-id")) || "");
    if (!recordId) {
      var panel = btn && btn.closest ? btn.closest(".mk-so-inline-detail") : null;
      recordId = String((panel && panel.getAttribute("data-record-id")) || "");
    }
    if (!recordId) return;

    var lead = findLeadByRecordId(recordId);
    if (
      !isLeadConvertible(lead) ||
      (btn && (btn.classList.contains("is-converted") || btn.disabled))
    ) {
      var url = btn && btn.getAttribute("data-potential-url");
      window.alert("Lead này đã được chuyển sang Cơ hội.");
      if (url && window.confirm("Mở Cơ hội?")) {
        window.location.href = url;
      }
      return;
    }

    openConvertOrderCategoryModal({
      radioName: "mk_leads_inline_convert_order_category",
      introHtml:
        "Chuyển lead sang <strong>Liên hệ + Tổ chức + Cơ hội</strong>. Chọn loại đơn hàng:",
      onConfirm: function (cat) {
        if (window.app && app.helper && app.helper.showProgress) {
          app.helper.showProgress();
        }
        convertSingleLead(leadCrmId(lead) || recordId, cat)
          .then(function (res) {
            if (window.app && app.helper && app.helper.hideProgress) {
              app.helper.hideProgress();
            }
            var potentialUrl =
              (res && (res.redirect || res.potentialUrl)) ||
              (res && res.potentialId
                ? "index.php?module=Potentials&view=Detail&record=" +
                  res.potentialId +
                  "&app=SALES"
                : "");
            if (lead) {
              lead.converted = true;
              lead.canConvert = false;
              if (res && res.potentialId) lead.potentialId = res.potentialId;
            }
            markInlineConvertDone(btn, potentialUrl);
            var refresh = store && store.refreshLeadsList ? store.refreshLeadsList() : Promise.resolve();
            return refresh.then(function () {
              renderAll();
              if (res && res.already_converted) {
                window.alert("Lead này đã được chuyển trước đó.");
              } else {
                window.alert("Đã chuyển sang Cơ hội.");
              }
              if (potentialUrl && window.confirm("Mở Cơ hội vừa tạo?")) {
                window.location.href = potentialUrl;
              }
            });
          })
          .catch(function (err) {
            if (window.app && app.helper && app.helper.hideProgress) {
              app.helper.hideProgress();
            }
            window.alert((err && err.message) || "Chuyển sang Cơ hội thất bại");
          });
      },
    });
  }

  function runBulkConvert(rows, orderCategory) {
    var convertible = rows.filter(isLeadConvertible);
    var skipped = rows.length - convertible.length;
    if (!convertible.length) {
      window.alert("Không có lead nào có thể chuyển sang Cơ hội.");
      return;
    }
    if (window.app && app.helper && app.helper.showProgress) {
      app.helper.showProgress();
    }
    var ok = 0;
    var already = 0;
    var failed = 0;
    var chain = Promise.resolve();
    convertible.forEach(function (lead) {
      chain = chain.then(function () {
        return convertSingleLead(leadCrmId(lead), orderCategory)
          .then(function (res) {
            if (res && res.already_converted) already += 1;
            else ok += 1;
          })
          .catch(function () {
            failed += 1;
          });
      });
    });
    chain
      .then(function () {
        if (window.app && app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        var refresh = store && store.refreshLeadsList ? store.refreshLeadsList() : Promise.resolve();
        return refresh.then(function () {
          clearSelection();
          renderAll();
          var msg = "Đã chuyển " + ok + " lead sang Cơ hội.";
          if (already) msg += " " + already + " đã chuyển trước đó.";
          if (skipped) msg += " " + skipped + " bỏ qua.";
          if (failed) msg += " " + failed + " lỗi.";
          window.alert(msg);
        });
      })
      .catch(function () {
        if (window.app && app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
      });
  }

  function detailUrl(id) {
    return "index.php?module=Leads&view=Detail&record=" + encodeURIComponent(id) + "&app=SALES&mkLeadId=" + encodeURIComponent(id);
  }

  function inTouchWindow(iso, range) {
    if (range === "any") return true;
    var days = logic.daysSince(iso);
    var max = { "7d": 7, "30d": 30, "90d": 90 }[range];
    return days <= max;
  }

  function filterLeads(leads) {
    var f = state.filters;
    var q = f.search.trim().toLowerCase();
    return leads.filter(function (l) {
      var d = logic.derive(l);
      if (q) {
        var hay = [l.name, l.phone, l.email || "", l.companyName || "", l.area || "", addressOf(l), regionKeyOf(l)].join(" ").toLowerCase();
        if (hay.indexOf(q) < 0) return false;
      }
      if (f.source !== ANY && (l.tags || []).indexOf(f.source) < 0) return false;
      if (f.program !== ANY && (l.tags || []).indexOf(f.program) < 0) return false;
      if (f.purchase !== ANY && (l.tags || []).indexOf(f.purchase) < 0) return false;
      if (f.tier !== ANY && (l.tags || []).indexOf(f.tier) < 0) return false;
      if (f.owner !== ANY && l.owner !== f.owner) return false;
      if (f.area !== ANY && (l.area || "") !== f.area) return false;
      if (f.segment !== ANY && (l.segment || "") !== f.segment) return false;
      if (!inTouchWindow(l.last_touch, f.touchRange)) return false;
      if (f.staleOnly && !d.stale) return false;
      if (f.hasNextAction && !(logic.deriveNextAction ? logic.deriveNextAction(l) : l.next_action || "").trim())
        return false;
      if (f.hasOpenTicket && !(l.openTickets > 0)) return false;
      return true;
    });
  }

  function sortLeads(list) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    return list.slice().sort(function (a, b) {
      var av, bv;
      if (key === "name") {
        av = a.name;
        bv = b.name;
      } else if (key === "value") {
        av = a.value || 0;
        bv = b.value || 0;
      } else if (key === "createdtime") {
        av = a.createdtime ? new Date(a.createdtime).getTime() : 0;
        bv = b.createdtime ? new Date(b.createdtime).getTime() : 0;
      } else {
        av = new Date(a.last_touch).getTime();
        bv = new Date(b.last_touch).getTime();
      }
      if (av < bv) return -1 * dir;
      if (av > bv) return 1 * dir;
      return 0;
    });
  }

  function formatCreatedLabel(raw) {
    if (!raw) return "";
    var d = new Date(raw);
    if (isNaN(d.getTime())) return String(raw);
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
      pad(d.getMinutes())
    );
  }

  function computeKpis(leads) {
    var todayStart = new Date();
    todayStart.setHours(0, 0, 0, 0);
    var newToday = 0;
    var qualified = 0;
    var repeat = 0;
    var gold = 0;
    var stale = 0;
    leads.forEach(function (l) {
      var tags = l.tags || [];
      var d = logic.derive(l);
      if (new Date(l.last_touch).getTime() >= todayStart.getTime()) newToday++;
      if (tags.indexOf("mua_lan_dau") >= 0 || tags.indexOf("mua_lai") >= 0) qualified++;
      if (tags.indexOf("mua_lai") >= 0) repeat++;
      if (tags.indexOf("vang") >= 0) gold++;
      if (d.stale) stale++;
    });
    var conv = leads.length ? Math.round((qualified / leads.length) * 100) : 0;
    return { total: leads.length, newToday: newToday, qualified: qualified, repeat: repeat, gold: gold, stale: stale, conv: conv };
  }

  function activeFilterCount() {
    var f = state.filters;
    var n = 0;
    if (f.source !== ANY) n++;
    if (f.program !== ANY) n++;
    if (f.purchase !== ANY) n++;
    if (f.tier !== ANY) n++;
    if (f.owner !== ANY) n++;
    if (f.area !== ANY) n++;
    if (f.segment !== ANY) n++;
    if (f.touchRange !== "any") n++;
    if (f.staleOnly) n++;
    if (f.hasNextAction) n++;
    if (f.hasOpenTicket) n++;
    return n;
  }

  function renderKpi() {
    var host = $("mk-leads-kpi");
    if (!host) return;
    var kpis = computeKpis(getLeads());
    var items = [
      { label: t("JS_MK_KPI_TOTAL", "Tổng lead"), value: kpis.total, trend: "+8%", up: true },
      { label: t("JS_MK_KPI_NEW_TODAY", "Mới hôm nay"), value: kpis.newToday, trend: "+12%", up: true },
      { label: t("JS_MK_KPI_QUALIFIED", "Đủ điều kiện"), value: kpis.qualified, trend: "+5%", up: true },
      { label: t("JS_MK_KPI_REPEAT", "Mua lại"), value: kpis.repeat, trend: "+3%", up: true },
      { label: t("JS_MK_KPI_GOLD", "Hạng Vàng"), value: kpis.gold, trend: "+2", up: true },
      { label: t("JS_MK_KPI_STALE", "Cần CSKH"), value: kpis.stale, trend: "-4%", up: false, linkAlerts: true },
      { label: t("JS_MK_KPI_CONV", "Tỷ lệ chuyển đổi"), value: kpis.conv + "%", trend: "+1.4%", up: true },
    ];
    var kpiIcons = (icons && icons.KPI) || [];
    var kpiTones = (icons && icons.KPI_TONES) || ["blue", "violet", "emerald", "cyan", "amber", "rose", "indigo"];
    host.innerHTML = items
      .map(function (k, i) {
        var tone = kpiTones[i] || "blue";
        var alertsHref = "index.php?module=SupportFAQ&view=List&app=SUPPORT";
        var cardClass = "mk-leads-kpi-card" + (k.linkAlerts ? " mk-leads-kpi-card--link" : "");
        var cardTag = k.linkAlerts ? "a" : "div";
        var cardAttrs = k.linkAlerts
          ? ' class="' + cardClass + '" href="' + alertsHref + '" title="Xem chi tiết tại Hỗ trợ → Cảnh báo"'
          : ' class="' + cardClass + '"';
        return (
          "<" + cardTag + cardAttrs + ">" +
          '<div class="mk-leads-kpi-card__top">' +
          '<span class="mk-leads-kpi-card__label">' +
          '<span class="mk-leads-kpi-ic-wrap mk-leads-kpi-ic--' +
          tone +
          '">' +
          ic(kpiIcons[i] || "users") +
          "</span><span>" +
          esc(k.label) +
          (k.linkAlerts ? ' <span class="mk-leads-kpi-card__hint">→ Cảnh báo</span>' : "") +
          "</span></span>" +
          '<span class="mk-leads-kpi-card__trend' +
          (k.up ? " is-up" : " is-down") +
          '">' +
          esc(k.trend) +
          "</span></div>" +
          '<div class="mk-leads-kpi-card__value">' +
          esc(k.value) +
          "</div></" + cardTag + ">"
        );
      })
      .join("");
  }

  function renderSegments() {
    var host = $("mk-leads-segments");
    if (!host) return;
    var saved = store ? store.getSegments() : [];
    var allOn = !state.activeSegment ? " is-active" : "";
    PRESET_SEGMENTS = getPresetSegments();
    var html =
      '<button type="button" class="mk-leads-segment-btn' +
      allOn +
      '" data-segment="__all__">' +
      esc(t("JS_MK_FILTER_ALL", "Tất cả")) +
      "</button>";
    html += PRESET_SEGMENTS.map(function (s) {
      var on = state.activeSegment === s.id ? " is-active" : "";
      return '<button type="button" class="mk-leads-segment-btn' + on + '" data-segment="' + esc(s.id) + '">' + esc(s.name) + "</button>";
    }).join("");
    html += saved
      .map(function (s) {
        var on = state.activeSegment === s.id ? " is-active" : "";
        return (
          '<span class="mk-leads-segment-chip' +
          on +
          '">' +
          '<button type="button" class="mk-leads-segment-chip__main" data-segment="' +
          esc(s.id) +
          '" data-custom="1">' +
          esc(s.name) +
          "</button>" +
          '<button type="button" class="mk-leads-segment-chip__del" data-del-segment="' +
          esc(s.id) +
          '" aria-label="Xóa phân đoạn">' +
          ic("close") +
          "</button></span>"
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

  function renderFiltersPanel() {
    var host = $("mk-leads-filters-panel");
    if (!host) return;
    var leads = getLeads();
    var owners = [];
    var areas = [];
    leads.forEach(function (l) {
      if (owners.indexOf(l.owner) < 0) owners.push(l.owner);
      if (l.area && areas.indexOf(l.area) < 0) areas.push(l.area);
    });
    owners.sort();
    areas.sort();
    var f = state.filters;
    var segmentLabels =
      ref && ref.getSegmentLabels
        ? ref.getSegmentLabels()
        : logic.SEGMENT_LABELS || {};
    host.innerHTML =
      '<div class="mk-leads-filters-grid">' +
      fieldSelect(t("JS_MK_FILTER_SOURCE", "Nguồn"), "source", f.source, SOURCE_TAGS.map(function (tg) { return [tg, tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_PROGRAM", "Chương trình"), "program", f.program, PROGRAM_TAGS.map(function (tg) { return [tg, tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_PURCHASE", "Trạng thái mua"), "purchase", f.purchase, PURCHASE_TAGS.map(function (tg) { return [tg, tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_TIER", "Hạng"), "tier", f.tier, TIER_TAGS.map(function (tg) { return [tg, tagMeta(tg).label]; })) +
      fieldSelect(t("JS_MK_FILTER_OWNER", "Phụ trách"), "owner", f.owner, owners.map(function (o) { return [o, o]; })) +
      fieldSelect(t("JS_MK_FILTER_AREA", "Khu vực"), "area", f.area, areas.map(function (a) { return [a, a]; })) +
      fieldSelect(t("JS_MK_FILTER_CUSTOMER_TYPE", "Loại khách"), "segment", f.segment, Object.keys(segmentLabels).map(function (k) { return [k, segmentLabels[k]]; })) +
      fieldTouch(t("JS_MK_FILTER_LAST_TOUCH", "Tương tác gần"), "touchRange", f.touchRange) +
      toggleField(t("JS_MK_FILTER_STALE_ONLY", "Chỉ cần CSKH (xem Cảnh báo)"), "staleOnly", f.staleOnly, true) +
      toggleField(t("JS_MK_FILTER_HAS_NEXT", "Có hành động tiếp"), "hasNextAction", f.hasNextAction, false) +
      toggleField(t("JS_MK_FILTER_HAS_TICKET", "Có ticket mở"), "hasOpenTicket", f.hasOpenTicket, false) +
      "</div>";
    host.hidden = !state.filtersOpen;
  }

  function fieldSelect(label, key, val, pairs) {
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

  function fieldTouch(label, key, val) {
    return (
      '<label class="mk-leads-filter-field"><span class="mk-leads-filter-field__label">' +
      esc(label) +
      '</span><select class="mk-leads-filter-field__select" data-fkey="' +
      key +
      '"><option value="any">' +
      esc(t("JS_MK_FILTER_ANYTIME", "Mọi lúc")) +
      '</option><option value="7d">' +
      esc(t("JS_MK_FILTER_LAST_7D", "7 ngày gần đây")) +
      '</option><option value="30d">' +
      esc(t("JS_MK_FILTER_LAST_30D", "30 ngày gần đây")) +
      '</option><option value="90d">' +
      esc(t("JS_MK_FILTER_LAST_90D", "90 ngày gần đây")) +
      "</option></select></label>"
    );
  }

  function toggleField(label, key, on, warn) {
    return (
      '<label class="mk-leads-toggle-field' +
      (warn ? " mk-leads-toggle-field--warn" : "") +
      '"><span class="mk-leads-toggle-field__label">' +
      (warn ? ic("alert") : "") +
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
    document.querySelectorAll("[data-fkey]").forEach(function (el) {
      var key = el.getAttribute("data-fkey");
      if (el.type === "checkbox") el.checked = !!f[key];
      else if (f[key] !== undefined) el.value = f[key];
    });
  }

  function tagBadgeHtml(tag, labelOverride) {
    var raw = String(tag || "").trim();
    var key = normalizeTagKey(raw);
    var label = labelOverride || "";
    // Always force full call-attempt names — never show bare "1"/"2".
    var callMatch = /^goi_lan_(\d+)$/.exec(key) || /^(\d{1,2})$/.exec(key);
    if (callMatch) {
      key = "goi_lan_" + parseInt(callMatch[1], 10);
      label = "Gọi lần " + parseInt(callMatch[1], 10);
    } else if (!label) {
      label = resolveTagLabel(key || raw);
    }
    if (!label || /^\d+$/.test(String(label).trim())) {
      label = resolveTagLabel(key || raw) || humanizeFallback(key || raw);
    }
    return (
      '<span class="mk-tag" data-tag="' +
      esc(key || raw) +
      '" title="' +
      esc(label) +
      '">' +
      esc(label) +
      "</span>"
    );
  }

  function humanizeFallback(key) {
    if (ref && ref.humanizeTagKey) return ref.humanizeTagKey(key);
    return String(key || "").replace(/_/g, " ");
  }

  function closeTagPopover() {
    var pop = $("mk-leads-tag-popover");
    if (pop) pop.remove();
  }

  function openTagPopover(anchor, lead) {
    closeTagPopover();
    if (!lead || !store) return;
    var catalog =
      ref && ref.getCreateTagCatalog
        ? ref.getCreateTagCatalog()
        : [];
    var selected = {};
    (lead.tags || []).forEach(function (tg) {
      var k = normalizeTagKey(tg);
      if (k) selected[k] = true;
    });
    // Keep system call tags even if not in create catalog.
    Object.keys(selected).forEach(function (k) {
      if (/^goi_lan_\d+$/.test(k) && !selected[k]) selected[k] = true;
    });

    var pop = document.createElement("div");
    pop.id = "mk-leads-tag-popover";
    pop.className = "mk-leads-tag-popover";
    pop.setAttribute("data-lead-id", String(lead.id));
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
    var top = rect.bottom + window.scrollY + 6;
    var left = Math.min(rect.left + window.scrollX, window.scrollX + window.innerWidth - 360);
    pop.style.top = top + "px";
    pop.style.left = Math.max(8, left) + "px";

    pop.addEventListener("click", function (e) {
      e.stopPropagation();
      var chip = e.target.closest && e.target.closest(".mk-leads-tag-chip");
      if (chip) {
        chip.classList.toggle("is-on");
        chip.setAttribute("aria-pressed", chip.classList.contains("is-on") ? "true" : "false");
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
        // Preserve tags not in Create catalog (custom / system call tags).
        var catalogKeys =
          ref && ref.getCreateTagKeys ? ref.getCreateTagKeys() : [];
        (lead.tags || []).forEach(function (tg) {
          var k = normalizeTagKey(tg);
          if (!k) return;
          if (catalogKeys.indexOf(k) < 0 && nextTags.indexOf(k) < 0) {
            nextTags.push(k);
          }
        });
        var saveBtn = e.target.closest("[data-tag-save]");
        if (saveBtn) saveBtn.disabled = true;
        store
          .update(lead.id, { tags: nextTags })
          .then(function () {
            closeTagPopover();
            renderAll();
          })
          .catch(function (err) {
            console.error(err);
            window.alert("Không lưu được thẻ.");
            if (saveBtn) saveBtn.disabled = false;
          });
      }
    });
  }

  function renderTable() {
    var all = getLeads();
    var rows = sortLeads(filterLeads(all));
    var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
    if (state.page > totalPages) state.page = 1;
    var start = (state.page - 1) * PAGE_SIZE;
    var pageRows = rows.slice(start, start + PAGE_SIZE);
    var tbody = $("mk-leads-tbody");
    if (!tbody) return;

    if (!pageRows.length) {
      var emptyMsg =
        all.length === 0
          ? t(
              "JS_MK_NO_LEADS_LOADED",
              "Chưa có lead để hiển thị. Bấm Tải lại danh sách (hoặc F5). Nhiều lead cũ đã bị xóa mềm — không phải do bộ lọc.",
            )
          : t("JS_MK_NO_LEADS_MATCH", "Không có lead phù hợp bộ lọc.");
      var extraBtn = "";
      if (all.length === 0) {
        extraBtn =
          '<div style="margin-top:12px"><button type="button" class="mk-leads-btn mk-leads-btn--outline" id="mk-leads-reload-list">Tải lại danh sách</button></div>';
      } else if (activeFilterCount() > 0) {
        extraBtn =
          '<div style="margin-top:12px"><button type="button" class="mk-leads-btn mk-leads-btn--outline" id="mk-leads-clear-filters-empty">Xóa bộ lọc</button></div>';
      }
      tbody.innerHTML =
        '<tr><td colspan="13" class="mk-leads-empty">' + esc(emptyMsg) + extraBtn + "</td></tr>";
    } else {
      tbody.innerHTML = pageRows
        .map(function (l) {
          var d = logic.derive(l);
          var src = (l.tags || []).find(function (tg) {
            return SOURCE_TAGS.indexOf(tg) >= 0;
          });
          var segmentLabels =
            ref && ref.getSegmentLabels
              ? ref.getSegmentLabels()
              : logic.SEGMENT_LABELS || {};
          var segmentKey = l.segment || null;
          var typeTag = (l.tags || []).find(function (tg) {
            return tg === "individual" || tg === "company" || tg === "ca_nhan";
          });
          if (typeTag === "ca_nhan") typeTag = "individual";
          var custKey = segmentKey || typeTag || null;
          var custLabel = segmentKey
            ? segmentLabels[segmentKey] || segmentKey
            : typeTag
              ? tagMeta(typeTag).label || typeTag
              : null;
          var nonSourceTags = displayTagsForLead(l);
          var tags = nonSourceTags;
          var extra = 0;
          var checked = state.selected[l.id] ? " checked" : "";
          var crmId = leadCrmId(l);
          var createdLabel = formatCreatedLabel(l.createdtime);
          var tagsHtml = tags.length
            ? tags.map(tagBadgeHtml).join("")
            : '<span class="mk-leads-muted">Thêm thẻ…</span>';
          return (
            '<tr class="mk-leads-row' +
            (d.high ? " mk-leads-row--hot" : "") +
            (state.selected[l.id] ? " mk-leads-row--selected" : "") +
            '" data-id="' +
            esc(l.id) +
            '"' +
            (crmId && /^\d+$/.test(crmId) ? ' data-crmid="' + esc(crmId) + '"' : "") +
            ">" +
            '<td class="mk-leads-td mk-leads-td--check"><label class="mk-leads-check">' +
            '<input type="checkbox" class="mk-leads-check__input mk-leads-row-check" data-id="' +
            esc(l.id) +
            '"' +
            checked +
            ' /><span class="mk-leads-check__ui" aria-hidden="true"></span></label></td>' +
            '<td class="mk-leads-td mk-leads-td--created">' +
            (createdLabel
              ? esc(createdLabel)
              : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--lead">' +
            '<span class="mk-leads-lead-cell">' +
            (d.high ? '<span class="mk-leads-fire" title="Ưu tiên cao">&#9832;</span>' : ic("user")) +
            '<span class="mk-leads-lead-text"><a class="mk-leads-name" href="' +
            detailUrl(l.id) +
            '">' +
            esc(l.name) +
            "</a>" +
            "</span></span></td>" +
            '<td class="mk-leads-td mk-leads-td--phone">' +
            editableCellHtml("phone", l.phone, l.id, "Nhập SĐT") +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--area">' +
            regionSelectHtml(l.id, regionKeyOf(l)) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--address">' +
            editableCellHtml("address", addressOf(l), l.id, "Nhập địa chỉ") +
            "</td>" +
            '<td class="mk-leads-td">' +
            (src ? tagBadgeHtml(src) : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td">' +
            (custKey
              ? tagBadgeHtml(custKey, custLabel)
              : '<span class="mk-leads-muted">—</span>') +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--owner"><span class="mk-leads-owner-inner"><span class="mk-owner-avatar" style="background:' +
            logic.ownerColor(l.owner) +
            '">' +
            esc(logic.ownerInitials(l.owner)) +
            '</span><span>' +
            esc(l.owner) +
            "</span></span></td>" +
            '<td class="mk-leads-td mk-leads-td--tags"><button type="button" class="mk-leads-tags-edit" data-lead-id="' +
            esc(l.id) +
            '" title="Sửa thẻ"><div class="mk-leads-tags-stack">' +
            tagsHtml +
            (extra > 0 ? '<span class="mk-leads-tag-more">+' + extra + "</span>" : "") +
            "</div></button></td>" +
            '<td class="mk-leads-td mk-leads-td--touch' +
            (d.stale ? " mk-leads-td--stale" : "") +
            '">' +
            (logic.lastTouchCallLogHtml
              ? logic.lastTouchCallLogHtml(l, esc)
              : logic.touchLabel(d.days)) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--next">' +
            (logic.nextActionCellHtml
              ? logic.nextActionCellHtml(l, esc)
              : (function () {
                  var next = logic.deriveNextAction ? logic.deriveNextAction(l) : l.next_action || "";
                  return next ? esc(next) : '<span class="mk-leads-muted">—</span>';
                })()) +
            "</td>" +
            '<td class="mk-leads-td" data-col="notes">' +
            (function () {
              var n = String(l.notes || "").trim();
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

    var summary = $("mk-leads-filter-summary");
    if (summary) {
      summary.textContent =
        rows.length +
        " / " +
        all.length +
        " " +
        t("JS_MK_LEADS_COUNT_LABEL", "lead");
    }

    var pag = $("mk-leads-pagination");
    if (pag) {
      var from = rows.length ? start + 1 : 0;
      var to = start + pageRows.length;
      pag.innerHTML =
        '<span class="mk-leads-pagination__info">' +
        esc(t("JS_MK_SHOWING", "Hiển thị")) +
        " " +
        from +
        "\u2013" +
        to +
        " / " +
        rows.length +
        "</span>" +
        '<div class="mk-leads-pagination__btns">' +
        '<button type="button" class="mk-leads-page-btn" id="mk-leads-prev"' +
        (state.page <= 1 ? " disabled" : "") +
        ">" +
        esc(t("JS_MK_PREV", "Trước")) +
        "</button>" +
        '<span class="mk-leads-page-num">' +
        state.page +
        " / " +
        totalPages +
        "</span>" +
        '<button type="button" class="mk-leads-page-btn" id="mk-leads-next"' +
        (state.page >= totalPages ? " disabled" : "") +
        ">" +
        esc(t("JS_MK_NEXT", "Sau")) +
        "</button></div>";
    }

    var badge = $("mk-leads-filter-count");
    var reset = $("mk-leads-reset");
    var n = activeFilterCount();
    if (badge) {
      badge.hidden = n === 0;
      badge.textContent = String(n);
    }
    if (reset) reset.hidden = n === 0;

    var checkAll = $("mk-leads-check-all");
    if (checkAll) {
      var allOnPage = pageRows.length > 0 && pageRows.every(function (l) {
        return !!state.selected[l.id];
      });
      checkAll.checked = allOnPage;
      checkAll.indeterminate = !allOnPage && pageRows.some(function (l) {
        return !!state.selected[l.id];
      });
    }
    syncSortHeaders();
    renderBulkBar();
  }

  function selectedCount() {
    return Object.keys(state.selected).length;
  }

  function selectedLeads() {
    var ids = Object.keys(state.selected);
    return getLeads().filter(function (l) {
      return state.selected[l.id];
    });
  }

  function clearSelection() {
    state.selected = {};
    renderTable();
  }

  function renderBulkBar() {
    var bar = $("mk-leads-bulk");
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
      '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--convert" data-bulk="convert">' +
      '<span class="mk-leads-bulk-btn__ic">' +
      ic("convert") +
      "</span><span>Chuyển sang Cơ hội</span></button>" +
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

  function renderAll() {
    renderKpi();
    renderSegments();
    renderFiltersPanel();
    syncFilterControls();
    renderTable();
  }

  function clearSegmentFilters() {
    state.filters = Object.assign({}, EMPTY);
    state.activeSegment = null;
    state.page = 1;
    var search = $("mk-leads-search");
    if (search) search.value = "";
    renderAll();
  }

  function applySegment(id, filters, isCustom) {
    state.filters = Object.assign({}, EMPTY, filters || {});
    state.activeSegment = id;
    state.page = 1;
    var search = $("mk-leads-search");
    if (search) search.value = state.filters.search;
    renderAll();
  }

  function exportCsv(rows) {
    var lines = ["Name,Phone,Area,Owner,Value,Tags"];
    rows.forEach(function (l) {
      lines.push(
        [
          l.name,
          l.phone,
          l.area || "",
          l.owner,
          l.value,
          (l.tags || []).join("|"),
        ]
          .map(function (v) {
            return '"' + String(v).replace(/"/g, '""') + '"';
          })
          .join(","),
      );
    });
    var blob = new Blob(["\uFEFF" + lines.join("\n")], { type: "text/csv;charset=utf-8" });
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "leads-" + new Date().toISOString().slice(0, 10) + ".csv";
    a.click();
    URL.revokeObjectURL(a.href);
  }

  function bindEvents() {
    var search = $("mk-leads-search");
    if (search) {
      search.addEventListener("input", function () {
        state.filters.search = search.value;
        state.activeSegment = null;
        state.page = 1;
        renderTable();
      });
    }

    $("mk-leads-filters-toggle") &&
      $("mk-leads-filters-toggle").addEventListener("click", function () {
        state.filtersOpen = !state.filtersOpen;
        this.setAttribute("aria-expanded", state.filtersOpen ? "true" : "false");
        var panel = $("mk-leads-filters-panel");
        if (panel) panel.hidden = !state.filtersOpen;
      });

    $("mk-leads-reset") &&
      $("mk-leads-reset").addEventListener("click", function () {
        state.filters = Object.assign({}, EMPTY);
        state.activeSegment = null;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      });

    document.addEventListener("click", function (e) {
      var editBtn = e.target.closest && e.target.closest(".mk-leads-inline-edit");
      if (editBtn) {
        e.preventDefault();
        e.stopPropagation();
        beginInlineEdit(editBtn);
        return;
      }
      var tagsBtn = e.target.closest && e.target.closest(".mk-leads-tags-edit");
      if (tagsBtn) {
        e.preventDefault();
        e.stopPropagation();
        var leadId = tagsBtn.getAttribute("data-lead-id");
        var lead = getLeads().find(function (l) {
          return String(l.id) === String(leadId) || String(leadCrmId(l)) === String(leadId);
        });
        if (lead) openTagPopover(tagsBtn, lead);
        return;
      }
      if (!(e.target.closest && e.target.closest("#mk-leads-tag-popover"))) {
        closeTagPopover();
      }
      var t = e.target;
      if (!t || !t.id) return;
      if (t.id === "mk-leads-reload-list") {
        e.preventDefault();
        var boot = store.refreshLeadsList
          ? store.refreshLeadsList()
          : store.ready
            ? store.ready()
            : Promise.resolve();
        boot.then(function () {
          state.page = 1;
          renderAll();
        }).catch(function (err) {
          console.error("Leads reload failed", err);
          window.alert("Không tải được danh sách lead. Thử F5 hoặc đăng nhập lại.");
        });
        return;
      }
      if (t.id === "mk-leads-clear-filters-empty") {
        e.preventDefault();
        state.filters = Object.assign({}, EMPTY);
        state.activeSegment = null;
        state.page = 1;
        if (search) search.value = "";
        renderAll();
      }
    });

    document.addEventListener("change", function (e) {
      var t = e.target;
      if (!t || !t.getAttribute) return;
      if (t.classList && t.classList.contains("mk-leads-region-select")) {
        e.stopPropagation();
        commitRegionChange(t);
        return;
      }
      var key = t.getAttribute("data-fkey");
      if (!key) return;
      if (t.type === "checkbox") state.filters[key] = t.checked;
      else state.filters[key] = t.value;
      state.activeSegment = null;
      state.page = 1;
      renderTable();
    });

    $("mk-leads-segments") &&
      $("mk-leads-segments").addEventListener("click", function (e) {
        var delBtn = e.target.closest("[data-del-segment]");
        if (delBtn) {
          e.preventDefault();
          e.stopPropagation();
          var delId = delBtn.getAttribute("data-del-segment");
          if (store && delId) {
            store
              .saveSegments(
                store.getSegments().filter(function (s) {
                  return s.id !== delId;
                }),
              )
              .then(function () {
                if (state.activeSegment === delId) {
                  state.activeSegment = null;
                }
                renderSegments();
              });
          }
          return;
        }
        var btn = e.target.closest("[data-segment]");
        if (!btn) return;
        var id = btn.getAttribute("data-segment");
        if (id === "__all__") {
          clearSegmentFilters();
          return;
        }
        var custom = btn.getAttribute("data-custom");
        if (custom && store) {
          var seg = store.getSegments().find(function (s) { return s.id === id; });
          if (seg) {
            if (state.activeSegment === id) {
              clearSegmentFilters();
              return;
            }
            applySegment(id, seg.filters, true);
          }
          return;
        }
        var preset = PRESET_SEGMENTS.find(function (s) { return s.id === id; });
        if (preset) {
          if (state.activeSegment === id) {
            clearSegmentFilters();
            return;
          }
          applySegment(id, preset.filters, false);
        }
      });

    $("mk-leads-save-segment") &&
      $("mk-leads-save-segment").addEventListener("click", function () {
        var name = prompt("Tên phân đoạn");
        if (!name || !store) return;
        var list = store.getSegments();
        list.push({ id: "seg_" + Date.now(), name: name, filters: Object.assign({}, state.filters) });
        store.saveSegments(list).then(function () {
          renderSegments();
        });
      });

    document.addEventListener(
      "focusout",
      function (e) {
        if (e.target && e.target.classList && e.target.classList.contains("mk-leads-inline-input")) {
          commitInlineEdit(e.target);
        }
      },
      true,
    );

    document.addEventListener("keydown", function (e) {
      if (!e.target || !e.target.classList || !e.target.classList.contains("mk-leads-inline-input")) {
        return;
      }
      if (e.key === "Enter") {
        e.preventDefault();
        commitInlineEdit(e.target);
      }
      if (e.key === "Escape") {
        e.preventDefault();
        renderTable();
      }
    });

    document.addEventListener("mk-leads-list-field-updated", function (e) {
      if (!e || !e.detail || !store || !store.getLeads) return;
      var detail = e.detail;
      var leads = store.getLeads();
      var idx = leads.findIndex(function (l) {
        return String(leadCrmId(l)) === String(detail.id) || String(l.id) === String(detail.id);
      });
      if (idx < 0) return;
      leads[idx] = Object.assign({}, leads[idx], detail.patch || {});
      if (store.setLeads) store.setLeads(leads);
      renderTable();
    });

    $("mk-leads-table") &&
      $("mk-leads-table").addEventListener("click", function (e) {
        var th = e.target.closest("[data-sort]");
        if (th) {
          var k = th.getAttribute("data-sort");
          if (state.sortKey === k) state.sortDir = state.sortDir === "asc" ? "desc" : "asc";
          else {
            state.sortKey = k;
            state.sortDir = "desc";
          }
          renderTable();
          return;
        }
        if (e.target.closest && e.target.closest(".mk-leads-td--check")) return;
        // Row body click is handled by MkSalesPosInline (expand panel).
        // Name link still navigates to full detail.
        if (e.target.closest && e.target.closest("a.mk-leads-name")) return;
      });

    document.addEventListener("change", function (e) {
      if (e.target.classList.contains("mk-leads-row-check")) {
        var id = e.target.getAttribute("data-id");
        if (e.target.checked) state.selected[id] = true;
        else delete state.selected[id];
        renderTable();
      }
      if (e.target.id === "mk-leads-check-all") {
        var rows = sortLeads(filterLeads(getLeads())).slice((state.page - 1) * PAGE_SIZE, state.page * PAGE_SIZE);
        rows.forEach(function (l) {
          if (e.target.checked) state.selected[l.id] = true;
          else delete state.selected[l.id];
        });
        renderTable();
      }
    });

    document.addEventListener("click", function (e) {
      var inlineCallBtn =
        e.target.closest && e.target.closest(".mk-so-inline-detail__call-btn");
      if (inlineCallBtn) {
        e.preventDefault();
        e.stopPropagation();
        openListLastTouchModal(inlineCallBtn);
        return;
      }
      var inlineConvertBtn =
        e.target.closest && e.target.closest(".mk-so-inline-detail__convert-btn");
      if (inlineConvertBtn) {
        e.preventDefault();
        e.stopPropagation();
        openInlineConvertModal(inlineConvertBtn);
        return;
      }
      if (e.target.id === "mk-leads-prev") {
        state.page = Math.max(1, state.page - 1);
        renderTable();
      }
      if (e.target.id === "mk-leads-next") {
        state.page += 1;
        renderTable();
      }
      if (e.target.id === "mk-leads-export-btn") {
        var menu = $("mk-leads-export-menu");
        if (menu) menu.hidden = !menu.hidden;
      }
      var bulkBtn = e.target.closest && e.target.closest("[data-bulk]");
      if (bulkBtn) {
        e.preventDefault();
        e.stopPropagation();
        var action = bulkBtn.getAttribute("data-bulk");
        var rows = selectedLeads();
        if (!rows.length && action !== "clear") return;
        if (action === "clear") {
          clearSelection();
          return;
        }
        if (action === "export") {
          exportCsv(rows);
          return;
        }
        if (action === "convert") {
          openBulkConvertModal(rows);
          return;
        }
        if (action === "delete" || action === "archive") {
          if (!window.confirm("Xóa " + rows.length + " lead đã chọn?")) return;
          Promise.all(
            rows.map(function (l) {
              return store.remove(l.id);
            }),
          ).then(function () {
            clearSelection();
            renderAll();
          });
          return;
        }
      }
      if (e.target.closest && e.target.closest("[data-export]")) {
        var kind = e.target.getAttribute("data-export");
        var rows = sortLeads(filterLeads(getLeads()));
        if (kind === "csv") exportCsv(rows);
        if (kind === "print") window.print();
        var menu = $("mk-leads-export-menu");
        if (menu) menu.hidden = true;
      }
    });
  }

  function decorateStaticIcons() {
    var map = {
      "mk-leads-segments-icon": "bookmark",
      "mk-leads-save-segment-ic": "save",
      "mk-leads-search-ic": "search",
      "mk-leads-filters-ic": "filter",
      "mk-leads-filters-chev": "chevron",
      "mk-leads-import-ic": "import",
      "mk-leads-export-ic": "export",
      "mk-leads-create-ic": "plus",
    };
    Object.keys(map).forEach(function (id) {
      var el = $(id);
      if (el) el.innerHTML = ic(map[id]);
    });
    document.querySelectorAll(".mk-leads-sort-ic").forEach(function (el) {
      el.innerHTML = ic("sort");
    });
  }

  function syncSortHeaders() {
    document.querySelectorAll(".mk-leads-th--sort").forEach(function (th) {
      var key = th.getAttribute("data-sort");
      th.classList.toggle("is-sorted", state.sortKey === key);
      th.classList.toggle("is-asc", state.sortKey === key && state.sortDir === "asc");
      th.classList.toggle("is-desc", state.sortKey === key && state.sortDir === "desc");
    });
  }

  function init() {
    if (!logic || !store) return;
    var boot = store.refreshLeadsList
      ? store.refreshLeadsList()
      : store.ready
        ? store.ready()
        : Promise.resolve();
    boot
      .then(function () {
        decorateStaticIcons();
        bindEvents();
        renderAll();
        syncSortHeaders();
      })
      .catch(function (err) {
        console.error("Leads API bootstrap failed", err);
        decorateStaticIcons();
        bindEvents();
        renderAll();
        syncSortHeaders();
      });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
