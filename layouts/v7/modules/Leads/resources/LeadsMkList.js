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
  var BUSINESS_MODELS = [
    "TS Topping",
    "Xe đẩy",
    "Cà phê máy lạnh",
    "Cà phê sân vườn",
    "TS Pha máy",
    "Cà phê không gian mở",
  ];
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
    return [{ id: "phone_dup", name: "Trùng SĐT", filters: { phoneDupOnly: true } }];
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
    phoneDupOnly: false,
  };

  var state = {
    filters: Object.assign({}, EMPTY),
    sortKey: "last_touch",
    sortDir: "desc",
    page: 1,
    selected: {},
    activeSegment: null,
    filtersOpen: false,
    listMode: "active", // active | trash
    trashCache: null,
    viewMode: "table", // table | kanban
    productTab: "all", // all | unclassified | online | offline | nvl | franchise
  };

  var FALLBACK_PRODUCT_CATALOG = {
    groups: [
      {
        code: "online",
        label: "Online",
        stages: [
          { code: "moi", label: "Mới" },
          { code: "dang_tu_van", label: "Đang tư vấn" },
          { code: "da_bao_gia", label: "Đã báo giá" },
          { code: "da_chot", label: "Đã chốt" },
          { code: "khong_mua", label: "Không mua" },
        ],
      },
      {
        code: "offline",
        label: "Offline",
        stages: [
          { code: "moi", label: "Mới" },
          { code: "dang_tu_van", label: "Đang tư vấn" },
          { code: "da_bao_gia", label: "Đã báo giá" },
          { code: "da_hen_lop", label: "Đã hẹn lớp" },
          { code: "da_chot", label: "Đã chốt" },
          { code: "khong_mua", label: "Không mua" },
        ],
      },
      {
        code: "nvl",
        label: "NVL",
        stages: [
          { code: "moi", label: "Mới" },
          { code: "dang_tu_van", label: "Đang tư vấn" },
          { code: "da_bao_gia", label: "Đã báo giá" },
          { code: "da_chot", label: "Đã chốt" },
          { code: "da_giao", label: "Đã giao" },
          { code: "khong_mua", label: "Không mua" },
        ],
      },
      {
        code: "franchise",
        label: "Nhượng quyền",
        stages: [
          { code: "moi", label: "Mới" },
          { code: "dang_tu_van", label: "Đang tư vấn" },
          { code: "da_bao_gia", label: "Đã báo giá" },
          { code: "da_chot", label: "Đã chốt" },
          { code: "khong_mua", label: "Không mua" },
        ],
      },
    ],
    stage_labels: {
      moi: "Mới",
      dang_tu_van: "Đang tư vấn",
      da_bao_gia: "Đã báo giá",
      da_hen_lop: "Đã hẹn lớp",
      da_chot: "Đã chốt",
      da_giao: "Đã giao",
      khong_mua: "Không mua",
    },
  };

  function $(id) {
    return document.getElementById(id);
  }

  function productCatalog() {
    var cat = store && store.getProductCatalog ? store.getProductCatalog() : null;
    return cat && cat.groups && cat.groups.length ? cat : FALLBACK_PRODUCT_CATALOG;
  }

  function productGroups() {
    return productCatalog().groups || [];
  }

  function stageLabelOf(code) {
    var labels = productCatalog().stage_labels || {};
    return labels[code] || code || "";
  }

  function leadProducts(lead) {
    if (!lead || !lead.products || !lead.products.length) return [];
    if (Object.prototype.toString.call(lead.products) !== "[object Array]") return [];
    return lead.products.slice();
  }

  function leadIsUnclassified(lead) {
    return leadProducts(lead).length === 0;
  }

  function leadHasProductGroup(lead, group) {
    return leadProducts(lead).some(function (p) {
      return p.group === group;
    });
  }

  function visibleProducts(lead) {
    var list = leadProducts(lead);
    if (state.productTab && state.productTab !== "all" && state.productTab !== "unclassified") {
      return list.filter(function (p) {
        return p.group === state.productTab;
      });
    }
    return list;
  }

  function canEditPipeline(lead) {
    return !!(lead && (lead.can_edit_pipeline === 1 || lead.can_edit_pipeline === true));
  }

  function daysInStageLabel(product) {
    if (!product || product.stage === "khong_mua") return "";
    var d = product.days_in_stage;
    if (d == null || d === "") return "";
    var n = parseInt(d, 10);
    if (isNaN(n)) return "";
    return n + " ngày";
  }

  function productChipHtml(product, lead) {
    var canEdit = canEditPipeline(lead);
    var title = product.product_name
      ? product.group_label + " — " + product.product_name
      : product.group_label;
    return (
      '<span class="mk-lead-pchip mk-lead-pchip--' +
      esc(product.group) +
      '" title="' +
      esc(title) +
      '">' +
      esc(product.group_label || product.group) +
      (canEdit
        ? '<button type="button" class="mk-lead-pchip__x" data-product-remove="' +
          esc(product.id) +
          '" title="Bỏ nhóm">×</button>'
        : "") +
      "</span>"
    );
  }

  function renderProductChipsCell(lead) {
    var list = visibleProducts(lead);
    var canEdit = canEditPipeline(lead);
    if (!list.length) {
      return (
        '<span class="mk-lead-pchips">' +
        '<span class="mk-lead-pchip mk-lead-pchip--none">Chưa phân loại</span>' +
        (canEdit
          ? '<button type="button" class="mk-lead-padd" data-product-add="' +
            esc(lead.id) +
            '" title="Gắn nhóm sản phẩm">+</button>'
          : "") +
        "</span>"
      );
    }
    var html = '<span class="mk-lead-pchips">' + list.map(function (p) {
      return productChipHtml(p, lead);
    }).join("");
    var have = {};
    leadProducts(lead).forEach(function (p) {
      have[p.group] = 1;
    });
    var leftover = productGroups().some(function (g) {
      return !have[g.code];
    });
    if (canEdit && leftover && state.productTab === "all") {
      html +=
        '<button type="button" class="mk-lead-padd" data-product-add="' +
        esc(lead.id) +
        '" title="Gắn nhóm sản phẩm">+</button>';
    }
    return html + "</span>";
  }

  function stageSelectHtml(product, lead) {
    var group = productGroups().find(function (g) {
      return g.code === product.group;
    });
    var stages = group && group.stages ? group.stages : [];
    if (!canEditPipeline(lead) || !stages.length) {
      return (
        '<span class="mk-lead-pstage-line">' +
        "<strong>" +
        esc(product.group_label || product.group) +
        ":</strong> " +
        esc(product.stage_label || stageLabelOf(product.stage)) +
        (daysInStageLabel(product)
          ? ' <em class="mk-lead-pdays">' + esc(daysInStageLabel(product)) + "</em>"
          : "") +
        "</span>"
      );
    }
    return (
      '<span class="mk-lead-pstage-line">' +
      "<strong>" +
      esc(product.group_label || product.group) +
      ":</strong> " +
      '<select class="mk-lead-pstage-select" data-product-id="' +
      esc(product.id) +
      '">' +
      stages
        .map(function (st) {
          var code = st.code || st;
          var label = st.label || stageLabelOf(code);
          return (
            '<option value="' +
            esc(code) +
            '"' +
            (product.stage === code ? " selected" : "") +
            ">" +
            esc(label) +
            "</option>"
          );
        })
        .join("") +
      "</select>" +
      (daysInStageLabel(product)
        ? ' <em class="mk-lead-pdays">' + esc(daysInStageLabel(product)) + "</em>"
        : "") +
      "</span>"
    );
  }

  function renderProductStageCell(lead) {
    var list = visibleProducts(lead);
    if (!list.length) {
      return '<span class="mk-leads-muted">—</span>';
    }
    return (
      '<div class="mk-lead-pstages">' +
      list
        .map(function (p) {
          return stageSelectHtml(p, lead);
        })
        .join("") +
      "</div>"
    );
  }

  function closeProductPopover() {
    var el = document.getElementById("mk-leads-product-popover");
    if (el && el.parentNode) el.parentNode.removeChild(el);
  }

  function openProductAddPopover(btn, lead) {
    closeProductPopover();
    var have = {};
    leadProducts(lead).forEach(function (p) {
      have[p.group] = 1;
    });
    var groups = productGroups().filter(function (g) {
      return !have[g.code];
    });
    if (!groups.length) {
      window.alert("Lead đã gắn đủ 4 nhóm sản phẩm.");
      return;
    }
    var pop = document.createElement("div");
    pop.id = "mk-leads-product-popover";
    pop.className = "mk-lead-ppop";
    pop.innerHTML =
      '<div class="mk-lead-ppop__title">Gắn nhóm sản phẩm</div>' +
      groups
        .map(function (g) {
          return (
            '<button type="button" class="mk-lead-ppop__item" data-group="' +
            esc(g.code) +
            '">' +
            esc(g.label) +
            "</button>"
          );
        })
        .join("");
    document.body.appendChild(pop);
    var rect = btn.getBoundingClientRect();
    pop.style.left = Math.min(rect.left, window.innerWidth - 220) + "px";
    pop.style.top = rect.bottom + 6 + window.scrollY + "px";
    pop.addEventListener("click", function (e) {
      var item = e.target.closest("[data-group]");
      if (!item) return;
      e.preventDefault();
      var group = item.getAttribute("data-group");
      var name = window.prompt("Tên sản phẩm cụ thể (mô tả, có thể để trống)", "") || "";
      closeProductPopover();
      if (!store || !store.productUpsert) return;
      store
        .productUpsert(leadCrmId(lead) || lead.id, group, name)
        .then(function () {
          refreshListBody();
        })
        .catch(function (err) {
          window.alert(typeof err === "string" ? err : (err && err.message) || "Không gắn được sản phẩm.");
        });
    });
  }

  function countLeadsForProductTab(baseLeads, tab) {
    var prev = state.productTab;
    state.productTab = tab;
    var n = filterLeads(baseLeads).length;
    state.productTab = prev;
    return n;
  }

  function productTabItemsHtml(baseLeads) {
    var items = [{ id: "unclassified", label: "Chưa phân loại" }].concat(
      productGroups().map(function (g) {
        return { id: g.code, label: g.label };
      }),
    );
    return items
      .map(function (it) {
        var n = countLeadsForProductTab(baseLeads, it.id);
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
      })
      .join("");
  }

  function applyViewMode() {
    var tableWrap = $("mk-leads-table-wrap");
    var kanban = $("mk-leads-kanban");
    var isKanban = state.viewMode === "kanban";
    if (tableWrap) tableWrap.hidden = isKanban;
    if (kanban) kanban.hidden = !isKanban;
    var toggle = $("mk-leads-view-toggle");
    var tableBtn = $("mk-leads-view-table");
    var kanbanBtn = $("mk-leads-view-kanban");
    if (toggle) toggle.setAttribute("data-mode", isKanban ? "kanban" : "table");
    if (tableBtn) {
      tableBtn.classList.toggle("is-active", !isKanban);
      tableBtn.setAttribute("aria-selected", !isKanban ? "true" : "false");
    }
    if (kanbanBtn) {
      kanbanBtn.classList.toggle("is-active", isKanban);
      kanbanBtn.setAttribute("aria-selected", isKanban ? "true" : "false");
    }
  }

  function refreshListBody() {
    renderSegments();
    applyViewMode();
    if (state.viewMode === "kanban") {
      renderKanban();
    } else {
      renderTable();
    }
  }

  function renderKanban() {
    var root = $("mk-leads-kanban");
    if (!root) return;
    var tab = state.productTab;
    if (tab === "all" || tab === "unclassified") {
      root.innerHTML =
        '<div class="mk-leads-kanban-empty">Chọn tab Online / Offline / NVL / Nhượng quyền để kéo stage trên Kanban. Lead chưa gắn nhóm không nằm trong cột nào.</div>';
      return;
    }
    var group = productGroups().find(function (g) {
      return g.code === tab;
    });
    if (!group) {
      root.innerHTML = "";
      return;
    }
    var rows = filterLeads(getLeads());
    var byStage = {};
    (group.stages || []).forEach(function (st) {
      byStage[st.code] = [];
    });
    rows.forEach(function (l) {
      var prod = leadProducts(l).find(function (p) {
        return p.group === tab;
      });
      if (!prod) return;
      if (!byStage[prod.stage]) byStage[prod.stage] = [];
      byStage[prod.stage].push({ lead: l, product: prod });
    });
    root.innerHTML =
      '<div class="mk-leads-kanban__cols">' +
      (group.stages || [])
        .map(function (st) {
          var cards = byStage[st.code] || [];
          return (
            '<section class="mk-leads-kanban-col mk-leads-kanban-col--' +
            esc(st.code) +
            '" data-stage="' +
            esc(st.code) +
            '">' +
            '<header class="mk-leads-kanban-col__head"><span>' +
            esc(st.label) +
            '</span><span class="mk-leads-kanban-col__n">' +
            cards.length +
            "</span></header>" +
            '<div class="mk-leads-kanban-col__body">' +
            cards
              .map(function (item) {
                var l = item.lead;
                var p = item.product;
                var days = daysInStageLabel(p);
                var can = canEditPipeline(l);
                return (
                  '<article class="mk-leads-kanban-card' +
                  (can ? " is-draggable" : "") +
                  '" data-product-id="' +
                  esc(p.id) +
                  '" data-lead-id="' +
                  esc(l.id) +
                  '"' +
                  (can ? ' draggable="true"' : "") +
                  ">" +
                  '<a class="mk-leads-kanban-card__name" href="' +
                  detailUrl(l.id) +
                  '">' +
                  esc(decodeHtmlEntities(l.name)) +
                  "</a>" +
                  (l.phone
                    ? '<div class="mk-leads-kanban-card__phone">' +
                      esc(decodeHtmlEntities(l.phone)) +
                      "</div>"
                    : "") +
                  (p.product_name
                    ? '<div class="mk-leads-kanban-card__sub">' +
                      esc(decodeHtmlEntities(p.product_name)) +
                      "</div>"
                    : "") +
                  (days ? '<div class="mk-leads-kanban-card__days">' + esc(days) + "</div>" : "") +
                  (!can ? '<div class="mk-leads-kanban-card__ro">Chỉ xem</div>' : "") +
                  "</article>"
                );
              })
              .join("") +
            "</div></section>"
          );
        })
        .join("") +
      "</div>";
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
      if (TIER_TAGS.indexOf(key) >= 0) return;
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

  function digitsOnly(value) {
    return String(value || "").replace(/\D+/g, "");
  }

  /** Normalize phone for dup grouping (align with pad-9 digit + last-10 style). */
  function normalizePhoneKey(phone) {
    var d = digitsOnly(phone);
    if (!d) return "";
    if (d.length === 9 && /^[3-9]/.test(d)) d = "0" + d;
    if (d.length > 11) d = d.slice(-10);
    return d;
  }

  function maskPhoneKey(digits) {
    var d = String(digits || "");
    if (d.length <= 4) return d;
    return d.slice(0, 3) + "***" + d.slice(-3);
  }

  var PHONE_DUP_GROUP_COLORS = 8;

  /**
   * Map of normalized phone → { letter, index, count, mask, key }
   * Only phones with count > 1. Letters A,B,C… by frequency then phone.
   */
  function buildPhoneDupGroups(leads) {
    var counts = {};
    (leads || []).forEach(function (l) {
      var k = normalizePhoneKey(l && l.phone);
      if (!k) return;
      counts[k] = (counts[k] || 0) + 1;
    });
    var keys = Object.keys(counts).filter(function (k) {
      return counts[k] > 1;
    });
    keys.sort(function (a, b) {
      if (counts[b] !== counts[a]) return counts[b] - counts[a];
      return a < b ? -1 : a > b ? 1 : 0;
    });
    var map = {};
    keys.forEach(function (k, i) {
      var letter =
        i < 26 ? String.fromCharCode(65 + i) : "G" + (i - 25); // A…Z then G2…
      map[k] = {
        key: k,
        letter: letter,
        index: i % PHONE_DUP_GROUP_COLORS,
        count: counts[k],
        mask: maskPhoneKey(k),
      };
    });
    return map;
  }

  function phoneDupGroupOf(lead, groupMap) {
    if (!lead || !groupMap) return null;
    var k = normalizePhoneKey(lead.phone);
    if (!k || !groupMap[k]) return null;
    return groupMap[k];
  }

  function addressOf(lead) {
    if (!lead) return "";
    var address = decodeHtmlEntities(String(lead.address || "")).trim();
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

  var INLINE_PLACEHOLDERS = {
    address: "Nhập địa chỉ",
    phone: "Nhập SĐT",
  };

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

  function businessModelSelectHtml(leadId, value) {
    var current = String(value || "").trim();
    var opts = [['', "—"]].concat(
      BUSINESS_MODELS.map(function (label) {
        return [label, label];
      })
    );
    return (
      '<select class="mk-leads-region-select mk-leads-biz-select" data-field="business_model" data-lead-id="' +
      esc(leadId) +
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

  function editableCellHtml(field, value, leadId, placeholder) {
    var shown = value;
    if (field === "phone" && value && window.MkPhoneFormat && typeof window.MkPhoneFormat.format === "function") {
      shown = window.MkPhoneFormat.format(value) || value;
    }
    var display = shown
      ? esc(decodeHtmlEntities(shown))
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

  function commitBusinessModelChange(select) {
    if (!select || !store || !store.update) return;
    var leadId = select.getAttribute("data-lead-id");
    if (!leadId) return;
    select.disabled = true;
    store
      .update(leadId, { business_model: select.value || "" })
      .then(function () {
        renderTable();
      })
      .catch(function (err) {
        console.error(err);
        window.alert("Không lưu được mô hình kinh doanh.");
        renderTable();
      });
  }

  function beginInlineEdit(btn) {
    if (!btn || !btn.getAttribute) return;
    var field = btn.getAttribute("data-field");
    var leadId = btn.getAttribute("data-lead-id");
    var placeholder = INLINE_PLACEHOLDERS[field] || "";
    var current = btn.textContent.trim();
    if (current === "—" || (placeholder && current === placeholder)) {
      current = "";
    }
    var input = document.createElement("input");
    input.type = field === "phone" ? "tel" : "text";
    input.className = "mk-leads-inline-input";
    input.value = current;
    if (placeholder) {
      input.setAttribute("placeholder", placeholder);
    }
    input.setAttribute("data-field", field);
    input.setAttribute("data-lead-id", leadId);
    if (field === "phone") {
      input.setAttribute("inputmode", "numeric");
      // Formatted display is "xxxx xxx xxx" (12 chars) for 10 digits.
      input.setAttribute("maxlength", "12");
      input.addEventListener("input", function () {
        var next =
          window.MkPhoneFormat && typeof window.MkPhoneFormat.formatInput === "function"
            ? window.MkPhoneFormat.formatInput(input.value)
            : String(input.value || "").replace(/\D+/g, "").slice(0, 10);
        if (next !== input.value) {
          input.value = next;
        }
      });
    }
    btn.replaceWith(input);
    input.focus();
    if (current) {
      input.select();
    }
  }

  function esc(s) {
    // Use == null so numeric 0 is preserved (KPI "Mới hôm nay" etc.)
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/"/g, "&quot;");
  }

  function getLeads() {
    if (state.listMode === "trash") {
      return state.trashCache ? state.trashCache.slice() : [];
    }
    return store ? store.getLeads() : [];
  }

  function loadTrashThenRender() {
    if (!store || typeof store.listTrash !== "function") {
      state.trashCache = [];
      renderAll();
      return Promise.resolve();
    }
    return store
      .listTrash()
      .then(function (rows) {
        state.trashCache = rows || [];
        renderAll();
      })
      .catch(function () {
        state.trashCache = [];
        renderAll();
      });
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


  function showConvertToast(message, isError) {
    var msg = String(message || "").trim();
    if (!msg) return;
    if (window.app && app.helper) {
      if (!isError && app.helper.showSuccessNotification) {
        app.helper.showSuccessNotification({ message: msg }, { delay: 2800 });
        return;
      }
      if (isError && app.helper.showErrorNotification) {
        app.helper.showErrorNotification({ message: msg });
        return;
      }
    }
    var old = document.getElementById("mk-leads-convert-toast");
    if (old && old.parentNode) old.parentNode.removeChild(old);
    var el = document.createElement("div");
    el.id = "mk-leads-convert-toast";
    el.className = "mk-leads-convert-toast" + (isError ? " is-error" : "");
    el.setAttribute("role", "status");
    el.textContent = msg;
    document.body.appendChild(el);
    window.setTimeout(function () {
      el.classList.add("is-show");
    }, 10);
    window.setTimeout(function () {
      el.classList.remove("is-show");
      window.setTimeout(function () {
        if (el.parentNode) el.parentNode.removeChild(el);
      }, 280);
    }, 2800);
  }

  function openBulkConvertModal(rows) {
    var convertible = rows.filter(isLeadConvertible);
    if (!convertible.length) {
      showConvertToast("Các lead đã chọn đều đã chuyển sang Cơ hội hoặc không thể chuyển.", true);
      return;
    }
    runBulkConvert(rows, "Internal");
  }

  function markInlineConvertDone(btn, potentialUrl) {
    if (!btn) return;
    btn.classList.add("is-converted");
    btn.disabled = true;
    btn.setAttribute("aria-disabled", "true");
    btn.setAttribute("hidden", "hidden");
    btn.style.display = "none";
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
    var str = String(s == null ? "" : s);
    var ta = document.createElement("textarea");
    var i;
    for (i = 0; i < 3; i++) {
      ta.innerHTML = str;
      var next = ta.value;
      if (next === str) break;
      str = next;
    }
    return str;
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
      '<div class="mk-lead-lt-modal__head-main">' +
      '<span class="mk-lead-lt-modal__icon" aria-hidden="true"><i class="fa fa-phone"></i></span>' +
      "<div>" +
      '<h3 id="mk-leads-lt-title">Ghi Last Touch — Call</h3>' +
      '<p class="mk-lead-lt-modal__sub">Theo dõi chuỗi tối đa 3 cuộc gọi</p>' +
      "</div></div>" +
      '<button type="button" class="mk-lead-lt-modal__x" data-mk-lt-close="1" aria-label="Đóng">&times;</button>' +
      "</div>" +
      '<div class="mk-lead-lt-modal__body">' +
      '<div class="mk-lead-lt-modal__meta-card" id="mk-leads-lt-meta"></div>' +
      '<label class="mk-lead-lt-modal__label" for="mk-leads-lt-result">Kết quả cuộc gọi</label>' +
      '<div class="mk-lead-lt-modal__select-wrap">' +
      '<select id="mk-leads-lt-result" class="mk-lead-lt-modal__select inputElement">' +
      '<option value="Không nghe máy">Không nghe máy</option>' +
      '<option value="Nghe máy">Nghe máy</option>' +
      "</select></div>" +
      '<label class="mk-lead-lt-modal__label" for="mk-leads-lt-note">Ghi chú</label>' +
      '<textarea id="mk-leads-lt-note" class="mk-lead-lt-modal__note inputElement" rows="6" placeholder="Ví dụ: Khách quan tâm lớp học"></textarea>' +
      '<p class="mk-lead-lt-modal__tip">Chọn <strong>Nghe máy</strong> sẽ tự chuyển Lead sang Cơ hội. <strong>Không nghe máy</strong> → nhắc gọi lần sau sau khoảng 5 giờ.</p>' +
      "</div>" +
      '<div class="mk-lead-lt-modal__foot">' +
      '<button type="button" class="mk-lead-lt-modal__btn mk-lead-lt-modal__btn--ghost" data-mk-lt-close="1">Hủy</button>' +
      '<button type="button" class="mk-lead-lt-modal__btn mk-lead-lt-modal__btn--primary" id="mk-leads-lt-save"><i class="fa fa-check" aria-hidden="true"></i> Lưu cuộc gọi</button>' +
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
      meta.innerHTML =
        '<span class="mk-lead-lt-modal__meta-n">Call #' +
        String(nextN) +
        "</span>" +
        '<span class="mk-lead-lt-modal__meta-text">Khoảng 5 giờ giữa các lần gọi (chuông Thông báo)' +
        (reminder ? " · Nhắc lần trước: " + reminder : "") +
        ".</span>";
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
            "index.php?module=Potentials&view=List&app=SALES";
          if (window.app && app.helper && app.helper.showSuccessNotification) {
            app.helper.showSuccessNotification({
              message: "Nghe máy — đã chuyển sang Cơ hội.",
            });
          }
          if (url) {
            window.setTimeout(function () {
              window.location.href = url;
            }, 400);
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

  function needsSalesVerify(l) {
    if (!l) return false;
    if (Number(l.needs_sales_verify) === 1 || Number(l.sheet_source) === 1) return true;
    if (isOnlineVerifyLead(l)) return true;
    if (l.form_c1 || l.form_c2 || l.form_c3) return true;
    var tags = l.tags || [];
    for (var i = 0; i < tags.length; i++) {
      if (String(tags[i]).toLowerCase() === "zalo") return true;
    }
    return false;
  }

  function isOnlineVerifyLead(l) {
    if (!l || Number(l.sheet_source) === 1) return false;
    if (l.verify_mode === "sales_b") return false;
    if (l.verify_mode === "online_gd12") return true;
    if (l.online_status || l.online_path === "oa") return true;
    if (l.online_q1 || l.online_q2 || l.online_q3 || l.online_q4) return true;
    var tags = l.tags || [];
    for (var i = 0; i < tags.length; i++) {
      var t = String(tags[i]).toLowerCase();
      if (t === "mien_phi_online" || t.indexOf("online_") === 0) return true;
    }
    return false;
  }

  function listOnlineVerifyOptions() {
    return {
      q1: [
        { code: "A", label: "Học phục vụ gia đình / sở thích" },
        { code: "B", label: "Xe đẩy / mang đi / online / tại nhà" },
        { code: "C", label: "Chuẩn bị mở quán, đã có mặt bằng" },
        { code: "D", label: "Đã có quán, kinh doanh chưa tốt" },
        { code: "E", label: "Đã có quán, kinh doanh ổn định / tốt" },
      ],
      q2: [
        { code: "A", label: "Trong 1 tháng / ngay bây giờ" },
        { code: "B", label: "1–3 tháng" },
        { code: "C", label: "3–6 tháng" },
        { code: "D", label: "Trên 6 tháng / chưa xác định" },
      ],
      q3: [
        { code: "A", label: "Dưới 50 triệu" },
        { code: "B", label: "Từ 50 đến dưới 100 triệu" },
        { code: "C", label: "Từ 100 đến dưới 300 triệu" },
        { code: "D", label: "Từ 300 đến dưới 500 triệu" },
        { code: "E", label: "Từ 500 triệu trở lên" },
      ],
      q4: [
        { code: "A", label: "Xe đẩy cà phê – trà sữa – trà trái cây" },
        { code: "B", label: "Trà sữa – topping, mặt bằng 20–30 m²" },
        { code: "C", label: "Trà sữa pha máy, mặt bằng 20–30 m²" },
        { code: "D", label: "Cà phê – trà sữa, máy lạnh" },
        { code: "E", label: "Cà phê sân vườn, diện tích vừa – lớn" },
        { code: "F", label: "Cà phê không gian mở, diện tích nhỏ" },
        { code: "G", label: "Học pha chế cho gia đình / sở thích" },
      ],
    };
  }

  function screeningStatusHtml(l) {
    var pot = l.potential_level || "";
    var elig = l.eligibility_result || "";
    var scr = l.screening_result || "";
    var label = l.screening_label || "";
    var pills = {
      so_luoc_du_dk: ["mk-leads-screen-pill--ok", "Sơ lược đủ điều kiện"],
      can_xm_muc_dich: ["mk-leads-screen-pill--warn", "Cần xác minh mục đích"],
      can_xm_mo_hinh: ["mk-leads-screen-pill--info", "Cần xác minh mô hình"],
      so_luoc_khong_dk: ["mk-leads-screen-pill--fail", "Sơ lược không đủ điều kiện"],
      khong_dat: ["mk-leads-screen-pill--fail", "Không đạt"],
      tiem_nang: ["mk-leads-screen-pill--ok", "Tiềm năng"],
      sieu_tiem_nang: ["mk-leads-screen-pill--hot", "Siêu tiềm năng"],
      binh_thuong: ["mk-leads-screen-pill--info", "Bình thường"],
    };
    if (elig === "khong_du_dk") {
      return (
        '<span class="mk-leads-screen-pill mk-leads-screen-pill--fail" title="Kết luận sau xác minh">' +
        esc(l.eligibility_label || "Không đủ điều kiện") +
        "</span>"
      );
    }
    if (pot && pills[pot]) {
      return (
        '<span class="mk-leads-screen-pill ' +
        pills[pot][0] +
        '" title="Mức tiềm năng sau xác minh">' +
        esc(l.potential_label || pills[pot][1]) +
        "</span>"
      );
    }
    if (pills[scr]) {
      return (
        '<span class="mk-leads-screen-pill ' +
        pills[scr][0] +
        '" title="Kết quả sơ lược">' +
        esc(label || pills[scr][1]) +
        "</span>"
      );
    }
    return "";
  }

  function listVerifyOptions() {
    return {
      c1: [
        { code: "A", label: "Chuẩn bị mở quán" },
        { code: "B", label: "Đã có quán, muốn cập nhật kiến thức / công thức / menu" },
        { code: "C", label: "Đã có quán, đang gặp vấn đề cần cải thiện" },
        { code: "D", label: "Học để biết thêm, phục vụ gia đình hoặc sở thích" },
      ],
      c2: [
        { code: "A", label: "Xe đẩy cà phê – trà sữa – trà trái cây" },
        { code: "B", label: "Trà sữa – topping, có mặt bằng 20–30 m²" },
        { code: "C", label: "Trà sữa pha máy, có mặt bằng 20–30 m²" },
        { code: "D", label: "Cà phê – trà sữa, máy lạnh" },
        { code: "E", label: "Cà phê sân vườn, diện tích vừa – lớn" },
        { code: "F", label: "Cà phê không gian mở, diện tích nhỏ" },
        { code: "G", label: "Học pha chế cho gia đình / sở thích" },
      ],
      c3: [
        { code: "A", label: "Dưới 50 triệu" },
        { code: "B", label: "Từ 50 đến dưới 100 triệu" },
        { code: "C", label: "Từ 100 đến dưới 300 triệu" },
        { code: "D", label: "Từ 300 đến dưới 500 triệu" },
        { code: "E", label: "Từ 500 triệu trở lên" },
      ],
    };
  }

  function listVerifySelectHtml(name, options, selected, placeholder) {
    var html = '<select class="mk-leads-verify-select" data-mk-verify="' + esc(name) + '">';
    html += '<option value="">' + esc(placeholder || "— Chọn —") + "</option>";
    (options || []).forEach(function (opt) {
      var code = String(opt.code || opt);
      var label = opt.label
        ? (/^[A-G]$/i.test(code) ? code + " — " + opt.label : opt.label)
        : code;
      html +=
        '<option value="' +
        esc(code) +
        '"' +
        (String(selected || "") === code ? " selected" : "") +
        ">" +
        esc(label) +
        "</option>";
    });
    html += "</select>";
    return html;
  }

  function ensureListVerifyPanel() {
    var existing = document.getElementById("mk-leads-verify-panel");
    if (existing) return existing;
    var wrap = document.createElement("div");
    wrap.id = "mk-leads-verify-panel";
    wrap.className = "mk-leads-verify-panel";
    wrap.hidden = true;
    wrap.setAttribute("aria-hidden", "true");
    wrap.innerHTML =
      '<div class="mk-leads-verify-panel__backdrop" data-mk-verify-close="1"></div>' +
      '<aside class="mk-leads-verify-panel__sheet" role="dialog" aria-modal="true" aria-labelledby="mk-leads-verify-title">' +
      '<header class="mk-leads-verify-panel__head">' +
      '<div class="mk-leads-verify-panel__head-main">' +
      '<span class="mk-leads-verify-panel__badge" id="mk-leads-verify-badge">Bộ B</span>' +
      "<div>" +
      '<h3 id="mk-leads-verify-title">Sales xác minh</h3>' +
      '<p class="mk-leads-verify-panel__sub" id="mk-leads-verify-sub">Gọi xác minh C1–C3, rồi C4/C5 nếu đủ điều kiện</p>' +
      "</div></div>" +
      '<button type="button" class="mk-leads-verify-panel__x" data-mk-verify-close="1" aria-label="Đóng">&times;</button>' +
      "</header>" +
      '<div class="mk-leads-verify-panel__body" id="mk-leads-verify-body"></div>' +
      '<footer class="mk-leads-verify-panel__foot">' +
      '<button type="button" class="mk-leads-verify-panel__btn mk-leads-verify-panel__btn--ghost" data-mk-verify-action="preview">Xem kết luận</button>' +
      '<button type="button" class="mk-leads-verify-panel__btn mk-leads-verify-panel__btn--primary" data-mk-verify-action="save">Lưu xác minh</button>' +
      "</footer></aside>";
    document.body.appendChild(wrap);
    wrap.addEventListener("click", function (e) {
      if (e.target && e.target.getAttribute && e.target.getAttribute("data-mk-verify-close") === "1") {
        e.preventDefault();
        closeListVerifyPanel();
      }
    });
    wrap.addEventListener("change", function (e) {
      var t = e.target;
      if (t && t.getAttribute && t.getAttribute("data-mk-verify")) {
        syncListVerifyC45(wrap);
        setListVerifyMsg("", "");
      }
    });
    wrap.addEventListener("click", function (e) {
      var btn = e.target && e.target.closest ? e.target.closest("[data-mk-verify-action]") : null;
      if (!btn) return;
      e.preventDefault();
      submitListVerify(btn.getAttribute("data-mk-verify-action"), btn);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && wrap && !wrap.hidden) {
        closeListVerifyPanel();
      }
    });
    return wrap;
  }

  function closeListVerifyPanel() {
    var panel = document.getElementById("mk-leads-verify-panel");
    if (!panel) return;
    panel.hidden = true;
    panel.setAttribute("aria-hidden", "true");
    document.body.classList.remove("mk-leads-verify-open");
    panel._mkLead = null;
    panel._mkVerifyMode = null;
  }

  function listVerifyFormHint(code, label) {
    if (!code) return '<span class="mk-leads-verify-formhint mk-leads-verify-formhint--empty">Form chưa có</span>';
    return (
      '<span class="mk-leads-verify-formhint">Form: <strong>' +
      esc(code) +
      "</strong>" +
      (label ? " — " + esc(label) : "") +
      "</span>"
    );
  }

  function paintListVerifyHeader(lead, online) {
    var badge = document.getElementById("mk-leads-verify-badge");
    var title = document.getElementById("mk-leads-verify-title");
    var sub = document.getElementById("mk-leads-verify-sub");
    if (badge) badge.textContent = online ? "Online 1.2" : "Bộ B";
    if (title) title.textContent = online ? "Xác minh Online (4 câu)" : "Sales xác minh";
    if (sub) {
      sub.textContent = online
        ? (lead.name || "Lead") +
          (lead.phone ? " · " + lead.phone : "") +
          " · Zalo OA — bộ 4 câu"
        : (lead.name || "Lead") +
          (lead.phone ? " · " + lead.phone : "") +
          " · Google Sheet — C1–C3";
    }
  }

  function listVerifyStatusHtml(lead) {
    if (lead.eligibility_label || lead.potential_label) {
      return (
        '<div class="mk-leads-verify-result" data-mk-verify-status="1">' +
        (lead.eligibility_label
          ? '<div class="mk-leads-verify-result__row"><span>Điều kiện</span><strong>' +
            esc(lead.eligibility_label) +
            "</strong></div>"
          : "") +
        (lead.potential_label
          ? '<div class="mk-leads-verify-result__row"><span>Mức tiềm năng</span><strong>' +
            esc(lead.potential_label) +
            "</strong></div>"
          : "") +
        (lead.verify_score != null
          ? '<div class="mk-leads-verify-result__row"><span>Điểm</span><strong>' +
            esc(String(lead.verify_score)) +
            "</strong></div>"
          : "") +
        "</div>"
      );
    }
    return '<div class="mk-leads-verify-result" data-mk-verify-status="1" hidden></div>';
  }

  function fillListVerifyBodyOnline(lead) {
    var body = document.getElementById("mk-leads-verify-body");
    if (!body || !lead) return;
    var opts =
      (lead.online_verify_options && lead.online_verify_options.q1
        ? lead.online_verify_options
        : null) || listOnlineVerifyOptions();
    var q1 = lead.online_q1 || "";
    var q2 = lead.online_q2 || "";
    var q3 = lead.online_q3 || "";
    var q4 = lead.online_q4 || "";
    paintListVerifyHeader(lead, true);
    body.innerHTML =
      '<div class="mk-leads-verify-hero">' +
      '<div class="mk-leads-verify-hero__name">' +
      esc(lead.name || "Lead") +
      "</div>" +
      '<div class="mk-leads-verify-hero__meta">' +
      screeningStatusHtml(lead) +
      (lead.phone ? '<span class="mk-leads-verify-phone">' + esc(lead.phone) + "</span>" : "") +
      "</div></div>" +
      '<section class="mk-leads-verify-section">' +
      "<h4>Đáp án Form Zalo OA <span>(4 câu)</span></h4>" +
      '<div class="mk-leads-verify-formcards mk-leads-verify-formcards--4">' +
      '<div class="mk-leads-verify-formcard"><em>Q1</em><strong>' +
      esc(lead.online_q1 || "—") +
      "</strong><small>" +
      esc(lead.online_q1_label || "Chưa có từ Form OA") +
      "</small></div>" +
      '<div class="mk-leads-verify-formcard"><em>Q2</em><strong>' +
      esc(lead.online_q2 || "—") +
      "</strong><small>" +
      esc(lead.online_q2_label || "Chưa có từ Form OA") +
      "</small></div>" +
      '<div class="mk-leads-verify-formcard"><em>Q3</em><strong>' +
      esc(lead.online_q3 || "—") +
      "</strong><small>" +
      esc(lead.online_q3_label || "Chưa có từ Form OA") +
      "</small></div>" +
      '<div class="mk-leads-verify-formcard"><em>Q4</em><strong>' +
      esc(lead.online_q4 || "—") +
      "</strong><small>" +
      esc(lead.online_q4_label || "Chưa có từ Form OA") +
      "</small></div></div></section>" +
      '<section class="mk-leads-verify-section">' +
      "<h4>Sau cuộc gọi / chỉnh đáp án</h4>" +
      '<label class="mk-leads-verify-field"><span>Câu 1 — Tình trạng</span>' +
      listVerifySelectHtml("q1", opts.q1, q1) +
      listVerifyFormHint(lead.online_q1, lead.online_q1_label) +
      "</label>" +
      '<label class="mk-leads-verify-field"><span>Câu 2 — Thời gian dự kiến</span>' +
      listVerifySelectHtml("q2", opts.q2, q2) +
      listVerifyFormHint(lead.online_q2, lead.online_q2_label) +
      "</label>" +
      '<label class="mk-leads-verify-field"><span>Câu 3 — Ngân sách</span>' +
      listVerifySelectHtml("q3", opts.q3, q3) +
      listVerifyFormHint(lead.online_q3, lead.online_q3_label) +
      "</label>" +
      '<label class="mk-leads-verify-field"><span>Câu 4 — Mô hình</span>' +
      listVerifySelectHtml("q4", opts.q4, q4) +
      listVerifyFormHint(lead.online_q4, lead.online_q4_label) +
      "</label></section>" +
      listVerifyStatusHtml(lead) +
      '<p class="mk-leads-verify-err" data-mk-verify-err hidden></p>' +
      '<p class="mk-leads-verify-ok" data-mk-verify-ok hidden></p>';
  }

  function fillListVerifyBodySheet(lead) {
    var body = document.getElementById("mk-leads-verify-body");
    if (!body || !lead) return;
    var opts = (lead.verify_options && lead.verify_options.c1 ? lead.verify_options : null) || listVerifyOptions();
    var c1 = lead.verify_c1 || lead.form_c1 || "";
    var c2 = lead.verify_c2 || lead.form_c2 || "";
    var c3 = lead.verify_c3 || lead.form_c3 || "";
    var c4 = lead.verify_c4 != null && lead.verify_c4 !== "" ? String(lead.verify_c4) : "";
    var c5 = lead.verify_c5 != null && lead.verify_c5 !== "" ? String(lead.verify_c5) : "";
    var levels =
      opts.c4 && opts.c4.length
        ? opts.c4
        : [1, 2, 3, 4].map(function (n) {
            return { code: String(n), label: "Mức " + n };
          });
    var levels5 =
      opts.c5 && opts.c5.length
        ? opts.c5
        : levels;
    var c4Label = opts.c4_label || "Câu 4 — Đánh giá mức độ quyết tâm / nhu cầu (1–4)";
    var c5Label = opts.c5_label || "Câu 5 — Đánh giá mức độ phù hợp / khả năng triển khai (1–4)";
    paintListVerifyHeader(lead, false);
    body.innerHTML =
      '<div class="mk-leads-verify-hero">' +
      '<div class="mk-leads-verify-hero__name">' +
      esc(lead.name || "Lead") +
      "</div>" +
      '<div class="mk-leads-verify-hero__meta">' +
      screeningStatusHtml(lead) +
      (lead.phone ? '<span class="mk-leads-verify-phone">' + esc(lead.phone) + "</span>" : "") +
      "</div></div>" +
      '<section class="mk-leads-verify-section">' +
      '<h4>Đáp án Form Google Sheet <span>(3 câu)</span></h4>' +
      '<div class="mk-leads-verify-formcards">' +
      '<div class="mk-leads-verify-formcard"><em>C1</em><strong>' +
      esc(lead.form_c1 || "—") +
      "</strong><small>" +
      esc(lead.form_c1_label || "Chưa có từ Form") +
      "</small></div>" +
      '<div class="mk-leads-verify-formcard"><em>C2</em><strong>' +
      esc(lead.form_c2 || "—") +
      "</strong><small>" +
      esc(lead.form_c2_label || "Chưa có từ Form") +
      "</small></div>" +
      '<div class="mk-leads-verify-formcard"><em>C3</em><strong>' +
      esc(lead.form_c3 || "—") +
      "</strong><small>" +
      esc(lead.form_c3_label || "Chưa có từ Form") +
      "</small></div></div></section>" +
      '<section class="mk-leads-verify-section">' +
      "<h4>Sau cuộc gọi — Bộ B</h4>" +
      '<label class="mk-leads-verify-field"><span>Câu 1 — Tình trạng</span>' +
      listVerifySelectHtml("c1", opts.c1, c1) +
      listVerifyFormHint(lead.form_c1, lead.form_c1_label) +
      "</label>" +
      '<label class="mk-leads-verify-field"><span>Câu 2 — Mô hình</span>' +
      listVerifySelectHtml("c2", opts.c2, c2) +
      listVerifyFormHint(lead.form_c2, lead.form_c2_label) +
      "</label>" +
      '<label class="mk-leads-verify-field"><span>Câu 3 — Ngân sách</span>' +
      listVerifySelectHtml("c3", opts.c3, c3) +
      listVerifyFormHint(lead.form_c3, lead.form_c3_label) +
      "</label>" +
      '<div class="mk-leads-verify-c45" data-mk-verify-c45>' +
      '<p class="mk-leads-verify-c45__hint" data-mk-verify-c45-hint>Đủ điều kiện sơ lược — chọn C4 và C5 để chấm mức tiềm năng.</p>' +
      '<label class="mk-leads-verify-field"><span>' +
      esc(c4Label) +
      "</span>" +
      listVerifySelectHtml("c4", levels, c4, "— Chọn mức —") +
      "</label>" +
      '<label class="mk-leads-verify-field"><span>' +
      esc(c5Label) +
      "</span>" +
      listVerifySelectHtml("c5", levels5, c5, "— Chọn mức —") +
      "</label></div>" +
      '<p class="mk-leads-verify-c45-skip" data-mk-verify-c45-skip hidden>Không đủ điều kiện (lớp loại) — không cần C4/C5.</p>' +
      '<label class="mk-leads-verify-field"><span>Lý do đổi đáp án <em>(bắt buộc nếu khác Form)</em></span>' +
      '<textarea class="mk-leads-verify-note" rows="2" data-mk-verify="change_reason" placeholder="Ví dụ: Khách khai Form nhầm mô hình">' +
      esc(lead.verify_change_reason || "") +
      "</textarea></label></section>" +
      listVerifyStatusHtml(lead) +
      '<p class="mk-leads-verify-err" data-mk-verify-err hidden></p>' +
      '<p class="mk-leads-verify-ok" data-mk-verify-ok hidden></p>';
    syncListVerifyC45(document.getElementById("mk-leads-verify-panel"));
  }

  function fillListVerifyBody(lead) {
    var body = document.getElementById("mk-leads-verify-body");
    var panel = document.getElementById("mk-leads-verify-panel");
    if (!body || !lead) return;
    var online = isOnlineVerifyLead(lead);
    if (panel) panel._mkVerifyMode = online ? "online_gd12" : "sales_b";
    if (online) fillListVerifyBodyOnline(lead);
    else fillListVerifyBodySheet(lead);
  }

  function readListVerifyPayload(host) {
    host = host || document.getElementById("mk-leads-verify-panel");
    var get = function (name) {
      var el = host ? host.querySelector('[data-mk-verify="' + name + '"]') : null;
      return el ? String(el.value || "").trim() : "";
    };
    var mode = (host && host._mkVerifyMode) || "sales_b";
    if (mode === "online_gd12") {
      return {
        mode: "online_gd12",
        q1: get("q1"),
        q2: get("q2"),
        q3: get("q3"),
        q4: get("q4"),
      };
    }
    return {
      mode: "sales_b",
      c1: get("c1"),
      c2: get("c2"),
      c3: get("c3"),
      c4: get("c4") ? parseInt(get("c4"), 10) : 0,
      c5: get("c5") ? parseInt(get("c5"), 10) : 0,
      change_reason: get("change_reason"),
    };
  }

  function syncListVerifyC45(host) {
    if (!host || host._mkVerifyMode === "online_gd12") return;
    var payload = readListVerifyPayload(host);
    var c45 = host.querySelector("[data-mk-verify-c45]");
    var skip = host.querySelector("[data-mk-verify-c45-skip]");
    if (!c45) return;
    var excluded =
      payload.c1 === "D" ||
      payload.c2 === "G" ||
      payload.c3 === "A" ||
      (payload.c2 === "A" && payload.c3 === "B");
    c45.hidden = !!excluded;
    if (skip) skip.hidden = !excluded;
  }

  function setListVerifyMsg(err, ok) {
    var host = document.getElementById("mk-leads-verify-panel");
    if (!host) return;
    var errEl = host.querySelector("[data-mk-verify-err]");
    var okEl = host.querySelector("[data-mk-verify-ok]");
    if (errEl) {
      errEl.hidden = !err;
      errEl.textContent = err || "";
    }
    if (okEl) {
      okEl.hidden = !ok;
      okEl.textContent = ok || "";
    }
  }

  function paintListVerifyPreview(result) {
    var box = document.querySelector("#mk-leads-verify-panel [data-mk-verify-status]");
    if (!box || !result) return;
    box.hidden = false;
    var tone =
      result.eligibility_result === "khong_du_dk"
        ? " is-fail"
        : result.potential_level === "sieu_tiem_nang"
          ? " is-hot"
          : result.potential_level === "tiem_nang"
            ? " is-ok"
            : "";
    box.className = "mk-leads-verify-result" + tone;
    var html = "";
    if (result.eligibility_label) {
      html +=
        '<div class="mk-leads-verify-result__row"><span>Điều kiện</span><strong>' +
        esc(result.eligibility_label) +
        "</strong></div>";
    }
    if (result.reason) {
      html +=
        '<div class="mk-leads-verify-result__row"><span>Lý do</span><strong>' +
        esc(result.reason) +
        "</strong></div>";
    }
    if (result.potential_label) {
      html +=
        '<div class="mk-leads-verify-result__row"><span>Mức tiềm năng</span><strong>' +
        esc(result.potential_label) +
        "</strong></div>";
    }
    if (result.score != null) {
      html +=
        '<div class="mk-leads-verify-result__row"><span>Điểm</span><strong>' +
        esc(String(result.score)) +
        "</strong></div>";
    }
    box.innerHTML = html || "<div>Chưa đủ dữ liệu để chấm điểm.</div>";
  }

  function openListVerifyPanel(leadId) {
    var cached = findLeadByRecordId(leadId);
    var panel = ensureListVerifyPanel();
    document.body.classList.add("mk-leads-verify-open");
    panel.hidden = false;
    panel.setAttribute("aria-hidden", "false");
    fillListVerifyBody(cached || { id: leadId, name: "Đang tải…" });
    var load =
      store && store.fetchLead
        ? store.fetchLead(leadId, true)
        : Promise.resolve(cached);
    load
      .then(function (fresh) {
        var lead = fresh || cached;
        panel._mkLead = lead;
        fillListVerifyBody(lead);
      })
      .catch(function () {
        panel._mkLead = cached;
      });
  }

  function submitListVerify(action, btn) {
    var panel = document.getElementById("mk-leads-verify-panel");
    var lead = panel && panel._mkLead;
    var payload = readListVerifyPayload(panel);
    var online = payload.mode === "online_gd12";
    setListVerifyMsg("", "");
    if (online) {
      if (!payload.q1 || !payload.q2 || !payload.q3 || !payload.q4) {
        setListVerifyMsg("Vui lòng chọn đủ Q1–Q4 (4 câu Online).", "");
        return;
      }
    } else {
      if (!payload.c1 || !payload.c2 || !payload.c3) {
        setListVerifyMsg("Vui lòng chọn đủ C1, C2, C3.", "");
        return;
      }
      var excluded =
        payload.c1 === "D" ||
        payload.c2 === "G" ||
        payload.c3 === "A" ||
        (payload.c2 === "A" && payload.c3 === "B");
      if (!excluded && (!payload.c4 || !payload.c5)) {
        setListVerifyMsg("Đủ điều kiện — vui lòng chọn Câu 4 và Câu 5 (mức 1–4) để chấm tiềm năng.", "");
        return;
      }
    }
    if (typeof app === "undefined" || !app.request) {
      setListVerifyMsg("API không sẵn sàng.", "");
      return;
    }
    if (btn) btn.disabled = true;
    var data = {
      module: "Leads",
      action: "ModernApi",
      mode: online
        ? action === "save"
          ? "online_verify_save"
          : "online_verify_preview"
        : action === "save"
          ? "sales_verify_save"
          : "sales_verify_preview",
      payload: JSON.stringify(payload),
    };
    if (action === "save") {
      data.id = (lead && (lead.crmid || lead.id)) || "";
      data.record = data.id;
    }
    app.request.post({ data: data }).then(function (err, res) {
      if (btn) btn.disabled = false;
      if (action === "preview") {
        if (err || !res || !res.result) {
          setListVerifyMsg((err && (err.message || err)) || (res && res.error) || "Không tính được kết luận.", "");
          return;
        }
        paintListVerifyPreview(res.result);
        return;
      }
      if (err || !res || !res.success) {
        setListVerifyMsg((err && (err.message || err)) || (res && res.error) || "Lưu xác minh thất bại.", "");
        return;
      }
      var fresh = res.lead || lead;
      if (store && typeof store.importLead === "function" && fresh) {
        store.importLead(fresh);
      }
      panel._mkLead = fresh;
      fillListVerifyBody(fresh);
      setListVerifyMsg("", "Đã lưu kết quả xác minh.");
      if (res.result) paintListVerifyPreview(res.result);
      renderTable();
      if (window.app && app.helper && app.helper.showSuccessNotification) {
        app.helper.showSuccessNotification({
          message: online ? "Đã lưu xác minh Online (4 câu)." : "Đã lưu xác minh Bộ B.",
        });
      }
    });
  }

  function runInlineConvert(btn, lead, recordId) {
    if (window.app && app.helper && app.helper.showProgress) {
      app.helper.showProgress();
    }
    convertSingleLead(leadCrmId(lead) || recordId, "Internal")
      .then(function (res) {
        if (window.app && app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        var potentialUrl =
          (res && (res.redirect || res.potentialUrl)) ||
          "index.php?module=Potentials&view=List&app=SALES";
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
            showConvertToast("Lead này đã được chuyển trước đó.");
          } else {
            showConvertToast("Đã chuyển sang Cơ hội.");
          }
          if (potentialUrl) {
            window.setTimeout(function () {
              window.location.href = potentialUrl;
            }, 450);
          }
        });
      })
      .catch(function (err) {
        if (window.app && app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        showConvertToast((err && err.message) || "Chuyển sang Cơ hội thất bại", true);
      });
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
      showConvertToast("Lead này đã được chuyển sang Cơ hội.");
      if (url) {
        window.setTimeout(function () {
          window.location.href = url;
        }, 400);
      }
      return;
    }

    runInlineConvert(btn, lead, recordId);
  }

  function runBulkConvert(rows, orderCategory) {
    var convertible = rows.filter(isLeadConvertible);
    var skipped = rows.length - convertible.length;
    if (!convertible.length) {
      showConvertToast("Không có lead nào có thể chuyển sang Cơ hội.", true);
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
          showConvertToast(msg, failed > 0);
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
    var qDigits = digitsOnly(q);
    return leads.filter(function (l) {
      // Converted leads live only as Opp — never show them on Leads list.
      if (state.listMode !== "trash") {
        var hasProducts = leadProducts(l).length > 0;
        if (!hasProducts && (l.converted || l.potentialId || l.canConvert === false)) return false;
      }
      var d = logic.derive(l);
      if (q) {
        var hay = [l.name, l.phone, l.email || "", l.companyName || "", l.area || "", addressOf(l), regionKeyOf(l)].join(" ").toLowerCase();
        var textMatch = hay.indexOf(q) >= 0;
        var phoneMatch = qDigits.length >= 3 && digitsOnly(l.phone).indexOf(qDigits) >= 0;
        if (!textMatch && !phoneMatch) return false;
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
      if (f.phoneDupOnly && !l.phone_dup) return false;
      if (state.productTab === "unclassified") {
        if (!leadIsUnclassified(l)) return false;
      } else if (state.productTab && state.productTab !== "all") {
        if (!leadHasProductGroup(l, state.productTab)) return false;
      }
      return true;
    });
  }

  function sortLeads(list) {
    var key = state.sortKey;
    var dir = state.sortDir === "asc" ? 1 : -1;
    var phoneDupMode = !!(state.filters && state.filters.phoneDupOnly);
    var groupMap = phoneDupMode ? buildPhoneDupGroups(getLeads()) : null;
    var groupOrder = groupMap
      ? Object.keys(groupMap).reduce(function (acc, k, i) {
          acc[k] = i;
          return acc;
        }, {})
      : {};
    return list.slice().sort(function (a, b) {
      if (phoneDupMode && groupMap) {
        var ka = normalizePhoneKey(a.phone);
        var kb = normalizePhoneKey(b.phone);
        var oa = groupOrder.hasOwnProperty(ka) ? groupOrder[ka] : 9999;
        var ob = groupOrder.hasOwnProperty(kb) ? groupOrder[kb] : 9999;
        if (oa !== ob) return oa - ob;
        if (ka !== kb) {
          if (ka < kb) return -1;
          if (ka > kb) return 1;
        }
      }
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
      var createdMs = l.createdtime ? new Date(l.createdtime).getTime() : NaN;
      if (!isNaN(createdMs) && createdMs >= todayStart.getTime()) newToday++;
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
    if (f.phoneDupOnly) n++;
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
      { label: t("JS_MK_KPI_STALE", "Cần CSKH"), value: kpis.stale, trend: "-4%", up: false, linkAlerts: true },
      { label: t("JS_MK_KPI_CONV", "Tỷ lệ chuyển đổi"), value: kpis.conv + "%", trend: "+1.4%", up: true },
    ];
    var kpiIcons = (icons && icons.KPI) || [];
    var kpiTones = (icons && icons.KPI_TONES) || ["blue", "violet", "emerald", "cyan", "rose", "indigo"];
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
    var allOn =
      !state.activeSegment && state.listMode !== "trash" && state.productTab === "all"
        ? " is-active"
        : "";
    PRESET_SEGMENTS = getPresetSegments();
    var html =
      '<button type="button" class="mk-leads-segment-btn' +
      allOn +
      '" data-segment="__all__">' +
      esc(t("JS_MK_FILTER_ALL", "Tất cả")) +
      "</button>";
    var trashOn = state.listMode === "trash" ? " is-active" : "";
    html +=
      '<button type="button" class="mk-leads-segment-btn mk-leads-segment-btn--trash' +
      trashOn +
      '" data-segment="__trash__">Thùng rác</button>';
    html += PRESET_SEGMENTS.map(function (s) {
      var on = state.activeSegment === s.id ? " is-active" : "";
      var extra =
        s.id === "phone_dup" ? " mk-leads-segment-btn--phone-dup" : "";
      return (
        '<button type="button" class="mk-leads-segment-btn' +
        extra +
        on +
        '" data-segment="' +
        esc(s.id) +
        '">' +
        esc(s.name) +
        "</button>"
      );
    }).join("");
    html += productTabItemsHtml(getLeads());
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
      fieldSelect(t("JS_MK_FILTER_OWNER", "Phụ trách"), "owner", f.owner, owners.map(function (o) { return [o, o]; })) +
      fieldSelect(t("JS_MK_FILTER_AREA", "Khu vực"), "area", f.area, areas.map(function (a) { return [a, a]; })) +
      fieldSelect(t("JS_MK_FILTER_CUSTOMER_TYPE", "Loại khách"), "segment", f.segment, Object.keys(segmentLabels).map(function (k) { return [k, segmentLabels[k]]; })) +
      fieldTouch(t("JS_MK_FILTER_LAST_TOUCH", "Tương tác gần"), "touchRange", f.touchRange) +
      toggleField(t("JS_MK_FILTER_STALE_ONLY", "Chỉ cần CSKH (xem Cảnh báo)"), "staleOnly", f.staleOnly, true) +
      toggleField(t("JS_MK_FILTER_HAS_NEXT", "Có hành động tiếp"), "hasNextAction", f.hasNextAction, false) +
      toggleField(t("JS_MK_FILTER_HAS_TICKET", "Có ticket mở"), "hasOpenTicket", f.hasOpenTicket, false) +
      toggleField("Chỉ Trùng SĐT", "phoneDupOnly", f.phoneDupOnly, true) +
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
        // Preserve tags not in Create catalog (custom / system call tags).
        var catalogKeys =
          ref && ref.getCreateTagKeys ? ref.getCreateTagKeys() : [];
        (lead.tags || []).forEach(function (tg) {
          var k = normalizeTagKey(tg);
          if (!k || TIER_TAGS.indexOf(k) >= 0) return;
          if (catalogKeys.indexOf(k) < 0 && nextTags.indexOf(k) < 0) {
            nextTags.push(k);
          }
        });
        nextTags = nextTags.filter(function (k) {
          return TIER_TAGS.indexOf(normalizeTagKey(k)) < 0;
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
    var phoneDupGroups = buildPhoneDupGroups(all);
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
        '<tr><td colspan="16" class="mk-leads-empty">' + esc(emptyMsg) + extraBtn + "</td></tr>";
    } else {
      tbody.innerHTML = pageRows
        .map(function (l) {
          var d = logic.derive(l);
          var g = phoneDupGroupOf(l, phoneDupGroups);
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
          var maxTagShow = 2;
          var tags = nonSourceTags.slice(0, maxTagShow);
          var extra = Math.max(0, nonSourceTags.length - maxTagShow);
          var checked = state.selected[l.id] ? " checked" : "";
          var crmId = leadCrmId(l);
          var createdLabel = formatCreatedLabel(l.createdtime);
          var tagsHtml = tags.length
            ? tags.map(tagBadgeHtml).join("")
            : nonSourceTags.length === 0
              ? '<span class="mk-leads-muted">Thêm thẻ…</span>'
              : "";
          var phoneDupHtml = "";
          if (g) {
            phoneDupHtml =
              '<span class="mk-leads-phone-dup mk-leads-phone-dup--g' +
              g.index +
              '" title="Nhóm ' +
              esc(g.letter) +
              " · SĐT " +
              esc(g.key) +
              " · " +
              g.count +
              ' lead cùng số — chỉ gộp trong cùng nhóm này">' +
              "Nhóm " +
              esc(g.letter) +
              " · " +
              esc(g.mask) +
              " ×" +
              g.count +
              "</span>";
          } else if (l.phone_dup) {
            phoneDupHtml =
              '<span class="mk-leads-phone-dup" title="SĐT trùng với lead khác — sale cần xác minh">Trùng SĐT' +
              (l.phone_dup_count > 1 ? " ×" + l.phone_dup_count : "") +
              "</span>";
          }
          return (
            '<tr class="mk-leads-row' +
            (d.high ? " mk-leads-row--hot" : "") +
            (state.selected[l.id] ? " mk-leads-row--selected" : "") +
            (g ? " mk-leads-row--phone-dup-g" + g.index : "") +
            '" data-id="' +
            esc(l.id) +
            '"' +
            (crmId && /^\d+$/.test(crmId) ? ' data-crmid="' + esc(crmId) + '"' : "") +
            (g ? ' data-phone-group="' + esc(g.letter) + '"' : "") +
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
            esc(decodeHtmlEntities(l.name)) +
            "</a>" +
            screeningStatusHtml(l) +
            (needsSalesVerify(l)
              ? '<button type="button" class="mk-leads-verify-btn' +
                (l.eligibility_result || l.potential_level ? " is-done" : "") +
                '" data-lead-id="' +
                esc(l.id) +
                '" title="' +
                (isOnlineVerifyLead(l) ? "Xác minh Online (4 câu)" : "Sales xác minh Bộ B (3 câu)") +
                '">' +
                '<svg class="mk-leads-verify-btn__ic" width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                '<path d="M12 3 5 6v6c0 5 3.2 8.2 7 9.5 3.8-1.3 7-4.5 7-9.5V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' +
                '<path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
                "</svg>" +
                "<span>" +
                (l.eligibility_result || l.potential_level ? "Đã xác minh" : "Xác minh") +
                "</span></button>"
              : "") +
            "</span></span></td>" +
            '<td class="mk-leads-td mk-leads-td--phone">' +
            '<span class="mk-leads-phone-wrap">' +
            editableCellHtml("phone", l.phone, l.id, "Nhập SĐT") +
            phoneDupHtml +
            "</span>" +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--area">' +
            regionSelectHtml(l.id, regionKeyOf(l)) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--address">' +
            editableCellHtml("address", addressOf(l), l.id, "Nhập địa chỉ") +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--biz">' +
            businessModelSelectHtml(l.id, l.business_model) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--products">' +
            renderProductChipsCell(l) +
            "</td>" +
            '<td class="mk-leads-td mk-leads-td--pstage">' +
            renderProductStageCell(l) +
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
            esc(decodeHtmlEntities(l.owner)) +
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
              var n = decodeHtmlEntities(String(l.notes || "").trim());
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
      (state.listMode === "trash"
        ? '<button type="button" class="mk-leads-bulk-btn" data-bulk="restore"><span class="mk-leads-bulk-btn__ic"></span><span>Khôi phục</span></button>' +
          '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--danger" data-bulk="purge"><span class="mk-leads-bulk-btn__ic"></span><span>Xóa vĩnh viễn</span></button>'
        : '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--convert" data-bulk="convert">' +
          '<span class="mk-leads-bulk-btn__ic">' +
          ic("convert") +
          "</span><span>Chuyển sang Cơ hội</span></button>" +
          '<button type="button" class="mk-leads-bulk-btn" data-bulk="merge">' +
          '<span class="mk-leads-bulk-btn__ic"></span><span>Gộp lead</span></button>' +
          '<button type="button" class="mk-leads-bulk-btn" data-bulk="export">' +
          '<span class="mk-leads-bulk-btn__ic">' +
          ic("export") +
          "</span><span>Xuất file</span></button>" +
          '<button type="button" class="mk-leads-bulk-btn mk-leads-bulk-btn--danger" data-bulk="delete">' +
          '<span class="mk-leads-bulk-btn__ic">' +
          ic("trash") +
          "</span><span>Xóa</span></button>") +
      "</div>" +
      '<button type="button" class="mk-leads-bulk-clear" data-bulk="clear">Bỏ chọn</button>' +
      "</div>";
  }

  function renderAll() {
    renderKpi();
    renderSegments();
    renderFiltersPanel();
    syncFilterControls();
    refreshListBody();
  }

  function clearSegmentFilters() {
    state.filters = Object.assign({}, EMPTY);
    state.activeSegment = null;
    state.listMode = "active";
    state.trashCache = null;
    state.productTab = "all";
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
        refreshListBody();
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
      if (!(e.target.closest && e.target.closest("#mk-leads-product-popover"))) {
        closeProductPopover();
      }
      var ptab = e.target.closest && e.target.closest("[data-product-tab]");
      if (ptab) {
        e.preventDefault();
        state.listMode = "active";
        state.trashCache = null;
        state.productTab = ptab.getAttribute("data-product-tab") || "all";
        state.page = 1;
        refreshListBody();
        return;
      }
      var viewBtn = e.target.closest && e.target.closest("[data-leads-view]");
      if (viewBtn) {
        e.preventDefault();
        state.viewMode = viewBtn.getAttribute("data-leads-view") === "kanban" ? "kanban" : "table";
        refreshListBody();
        return;
      }
      var addBtn = e.target.closest && e.target.closest("[data-product-add]");
      if (addBtn) {
        e.preventDefault();
        e.stopPropagation();
        var addId = addBtn.getAttribute("data-product-add");
        var addLead = getLeads().find(function (l) {
          return String(l.id) === String(addId) || String(leadCrmId(l)) === String(addId);
        });
        if (addLead) openProductAddPopover(addBtn, addLead);
        return;
      }
      var rmBtn = e.target.closest && e.target.closest("[data-product-remove]");
      if (rmBtn) {
        e.preventDefault();
        e.stopPropagation();
        if (!store || !store.productRemove) return;
        if (!window.confirm("Bỏ nhóm sản phẩm này khỏi lead?")) return;
        store
          .productRemove(rmBtn.getAttribute("data-product-remove"))
          .then(function () {
            refreshListBody();
          })
          .catch(function (err) {
            window.alert(typeof err === "string" ? err : (err && err.message) || "Không bỏ được nhóm.");
          });
        return;
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
      if (t.classList && t.classList.contains("mk-leads-biz-select")) {
        e.stopPropagation();
        commitBusinessModelChange(t);
        return;
      }
      if (t.classList && t.classList.contains("mk-leads-region-select")) {
        e.stopPropagation();
        commitRegionChange(t);
        return;
      }
      if (t.classList && t.classList.contains("mk-lead-pstage-select")) {
        e.stopPropagation();
        var productId = t.getAttribute("data-product-id");
        var stage = t.value;
        if (!store || !store.productSetStage || !productId) return;
        t.disabled = true;
        store
          .productSetStage(productId, stage)
          .then(function () {
            refreshListBody();
          })
          .catch(function (err) {
            t.disabled = false;
            window.alert(typeof err === "string" ? err : (err && err.message) || "Không đổi được stage.");
            refreshListBody();
          });
        return;
      }
      var key = t.getAttribute("data-fkey");
      if (!key) return;
      if (t.type === "checkbox") state.filters[key] = t.checked;
      else state.filters[key] = t.value;
      state.activeSegment = null;
      state.page = 1;
      refreshListBody();
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
          state.listMode = "active";
          state.trashCache = null;
          clearSelection();
          clearSegmentFilters();
          return;
        }
        if (id === "__trash__") {
          state.listMode = "trash";
          state.activeSegment = "__trash__";
          clearSelection();
          loadTrashThenRender();
          return;
        }
        state.listMode = "active";
        state.trashCache = null;
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
      refreshListBody();
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

    document.addEventListener(
      "click",
      function (e) {
        var verifyBtn = e.target.closest && e.target.closest(".mk-leads-verify-btn");
        if (verifyBtn) {
          e.preventDefault();
          e.stopPropagation();
          openListVerifyPanel(verifyBtn.getAttribute("data-lead-id"));
          return;
        }
        var inlineCallBtn =
          e.target.closest && e.target.closest(".mk-so-inline-detail__call-btn");
        if (!inlineCallBtn) return;
        e.preventDefault();
        e.stopPropagation();
        openListLastTouchModal(inlineCallBtn);
      },
      true
    );

    document.addEventListener("click", function (e) {
      var inlineCallBtn =
        e.target.closest && e.target.closest(".mk-so-inline-detail__call-btn");
      if (inlineCallBtn) {
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
          if (!window.confirm("Chuyển " + rows.length + " lead vào thùng rác?")) return;
          Promise.all(
            rows.map(function (l) {
              return store.remove(l.id);
            }),
          ).then(function () {
            clearSelection();
            return store.refreshLeadsList ? store.refreshLeadsList() : Promise.resolve();
          }).then(function () {
            renderAll();
          });
          return;
        }
        if (action === "restore") {
          Promise.all(
            rows.map(function (l) {
              return store.restoreLead(l.id);
            }),
          ).then(function () {
            clearSelection();
            return loadTrashThenRender();
          });
          return;
        }
        if (action === "purge") {
          if (!window.confirm("Xóa vĩnh viễn " + rows.length + " lead? Không khôi phục được.")) return;
          Promise.all(
            rows.map(function (l) {
              return store.purgeLead(l.id);
            }),
          ).then(function () {
            clearSelection();
            return loadTrashThenRender();
          });
          return;
        }
        if (action === "merge") {
          openMergeDialog(rows);
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

    var kanbanRoot = $("mk-leads-kanban");
    if (kanbanRoot) {
      kanbanRoot.addEventListener("dragstart", function (e) {
        var card = e.target.closest && e.target.closest(".mk-leads-kanban-card.is-draggable");
        if (!card) return;
        e.dataTransfer.setData("text/plain", card.getAttribute("data-product-id") || "");
        e.dataTransfer.effectAllowed = "move";
        card.classList.add("is-dragging");
      });
      kanbanRoot.addEventListener("dragend", function (e) {
        var card = e.target.closest && e.target.closest(".mk-leads-kanban-card");
        if (card) card.classList.remove("is-dragging");
        var cols = kanbanRoot.querySelectorAll(".mk-leads-kanban-col");
        for (var i = 0; i < cols.length; i++) cols[i].classList.remove("is-drop");
      });
      kanbanRoot.addEventListener("dragover", function (e) {
        var col = e.target.closest && e.target.closest(".mk-leads-kanban-col");
        if (!col) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = "move";
        var cols = kanbanRoot.querySelectorAll(".mk-leads-kanban-col");
        for (var j = 0; j < cols.length; j++) cols[j].classList.toggle("is-drop", cols[j] === col);
      });
      kanbanRoot.addEventListener("drop", function (e) {
        var col = e.target.closest && e.target.closest(".mk-leads-kanban-col");
        if (!col) return;
        e.preventDefault();
        var productId = e.dataTransfer.getData("text/plain");
        var stage = col.getAttribute("data-stage");
        if (!productId || !stage || !store || !store.productSetStage) return;
        store
          .productSetStage(productId, stage)
          .then(function () {
            refreshListBody();
          })
          .catch(function (err) {
            window.alert(typeof err === "string" ? err : (err && err.message) || "Không kéo được stage.");
          });
      });
    }
  }

  function closeMergeModal() {
    var el = document.getElementById("mk-leads-merge-modal");
    if (el) el.hidden = true;
  }

  function ensureMergeModal() {
    var existing = document.getElementById("mk-leads-merge-modal");
    if (existing) return existing;
    var root = document.createElement("div");
    root.id = "mk-leads-merge-modal";
    root.className = "mk-leads-sheet-modal mk-leads-merge-modal";
    root.hidden = true;
    root.innerHTML =
      '<div class="mk-leads-sheet-modal__backdrop" data-merge-close="1"></div>' +
      '<div class="mk-leads-sheet-modal__panel mk-leads-merge-modal__panel" role="dialog" aria-modal="true" aria-labelledby="mk-merge-title">' +
      '  <header class="mk-leads-sheet-modal__head">' +
      '    <h2 id="mk-merge-title">Gộp lead</h2>' +
      '    <button type="button" class="mk-leads-sheet-modal__x" data-merge-close="1" aria-label="Đóng">×</button>' +
      "  </header>" +
      '  <div class="mk-leads-sheet-modal__body">' +
      '    <p class="mk-leads-sheet-modal__hint">Chọn lead <strong>giữ lại</strong> — tên và hồ sơ của lead này sẽ được giữ. Lead còn lại vào thùng rác.</p>' +
      '    <div class="mk-leads-merge-warn" id="mk-merge-warn" hidden></div>' +
      '    <div class="mk-leads-merge-list" id="mk-merge-list" role="radiogroup" aria-label="Lead giữ lại"></div>' +
      "  </div>" +
      '  <footer class="mk-leads-sheet-modal__foot">' +
      '    <button type="button" class="mk-leads-btn mk-leads-btn--outline" data-merge-close="1">Huỷ</button>' +
      '    <button type="button" class="mk-leads-btn mk-leads-btn--primary" id="mk-merge-confirm">Gộp lead</button>' +
      "  </footer>" +
      "</div>";
    document.body.appendChild(root);
    root.addEventListener("click", function (e) {
      if (e.target && e.target.getAttribute && e.target.getAttribute("data-merge-close") === "1") {
        closeMergeModal();
      }
    });
    return root;
  }

  function runMergeLeads(keeper, others) {
    var chain = Promise.resolve();
    others.forEach(function (d) {
      chain = chain.then(function () {
        return store.mergeLeads(keeper.crmid || keeper.id, d.crmid || d.id);
      });
    });
    return chain
      .then(function () {
        clearSelection();
        return store.refreshLeadsList ? store.refreshLeadsList() : Promise.resolve();
      })
      .then(function () {
        renderAll();
        if (window.app && app.helper && app.helper.showSuccessNotification) {
          app.helper.showSuccessNotification({ message: "Đã gộp lead. Lead thừa đã vào thùng rác." });
        }
      });
  }

  function openMergeDialog(rows) {
    if (!rows || rows.length < 2) {
      window.alert("Chọn ít nhất 2 lead để gộp (thường cùng SĐT).");
      return;
    }
    var phones = {};
    rows.forEach(function (l) {
      var k = normalizePhoneKey(l.phone);
      if (k) phones[k] = true;
    });
    var phoneKeys = Object.keys(phones);
    var modal = ensureMergeModal();
    var list = document.getElementById("mk-merge-list");
    var warn = document.getElementById("mk-merge-warn");
    var confirmBtn = document.getElementById("mk-merge-confirm");
    if (!list || !confirmBtn) return;

    if (warn) {
      if (phoneKeys.length > 1) {
        warn.hidden = false;
        warn.innerHTML =
          "Các lead thuộc <strong>" +
          phoneKeys.length +
          "</strong> SĐT khác nhau (" +
          phoneKeys.map(maskPhoneKey).join(", ") +
          "). Nên chỉ gộp lead cùng nhóm SĐT.";
      } else {
        warn.hidden = true;
        warn.textContent = "";
      }
    }

    var groups = buildPhoneDupGroups(getLeads());
    list.innerHTML = rows
      .map(function (l, i) {
        var g = phoneDupGroupOf(l, groups);
        var gLabel = g ? '<span class="mk-leads-merge-badge">Nhóm ' + esc(g.letter) + "</span>" : "";
        var checked = i === 0 ? " checked" : "";
        return (
          '<label class="mk-leads-merge-item">' +
          '<input type="radio" name="mk-merge-keeper" value="' +
          i +
          '"' +
          checked +
          " />" +
          '<span class="mk-leads-merge-item__body">' +
          '<span class="mk-leads-merge-item__name">' +
          esc(l.name || "—") +
          "</span>" +
          '<span class="mk-leads-merge-item__meta">' +
          esc(l.phone || "Không có SĐT") +
          " · id " +
          esc(String(l.crmid || l.id)) +
          " " +
          gLabel +
          "</span>" +
          "</span></label>"
        );
      })
      .join("");

    confirmBtn.disabled = false;
    confirmBtn.onclick = function () {
      var picked = list.querySelector('input[name="mk-merge-keeper"]:checked');
      var idx = picked ? parseInt(picked.value, 10) : 0;
      if (isNaN(idx) || idx < 0 || idx >= rows.length) {
        window.alert("Chọn lead giữ lại.");
        return;
      }
      if (phoneKeys.length > 1) {
        var ok = window.confirm(
          "Bạn đang gộp lead khác SĐT. Chỉ nên gộp cùng nhóm SĐT.\n\nVẫn tiếp tục?",
        );
        if (!ok) return;
      }
      var keeper = rows[idx];
      var others = rows.filter(function (_, i) {
        return i !== idx;
      });
      confirmBtn.disabled = true;
      runMergeLeads(keeper, others)
        .then(function () {
          closeMergeModal();
        })
        .catch(function (err) {
          confirmBtn.disabled = false;
          window.alert((err && (err.message || err)) || "Gộp lead thất bại.");
        });
    };

    modal.hidden = false;
  }

  function mapFieldRowHtml(key, label) {
    return (
      '<label class="mk-leads-sheet-map-row" data-map-key="' +
      key +
      '"><span class="mk-leads-sheet-map-row__lab">' +
      label +
      '</span><input type="text" class="mk-leads-sheet-map-row__inp" data-map-field="' +
      key +
      '" placeholder="Tên cột trên Google Sheet" autocomplete="off" /></label>'
    );
  }

  function parseSheetMapRaw(raw) {
    try {
      var o = JSON.parse(String(raw || "{}").trim() || "{}");
      return o && typeof o === "object" ? o : {};
    } catch (e) {
      return {};
    }
  }

  function applySheetMapGuiFromObject(map) {
    map = map || {};
    var gui = document.getElementById("mk-sheet-map-gui");
    if (!gui) return;
    gui.querySelectorAll("[data-map-field]").forEach(function (inp) {
      var key = inp.getAttribute("data-map-field");
      if (!key) return;
      var v = map[key];
      inp.value = v == null || typeof v === "object" ? "" : String(v);
    });
  }

  function buildSheetMapFromGui() {
    var gui = document.getElementById("mk-sheet-map-gui");
    var map = {};
    if (!gui) {
      return parseSheetMapRaw(
        ((document.getElementById("mk-sheet-map") || {}).value) || "{}"
      );
    }
    gui.querySelectorAll("[data-map-field]").forEach(function (inp) {
      var key = inp.getAttribute("data-map-field");
      var val = String(inp.value || "").trim();
      if (!key) return;
      if (val) {
        map[key] = val;
      }
    });
    return map;
  }

  function syncSheetMapJsonFromGui() {
    var ta = document.getElementById("mk-sheet-map");
    if (!ta) return;
    try {
      ta.value = JSON.stringify(buildSheetMapFromGui(), null, 2);
    } catch (e) {
      /* ignore */
    }
  }

  function ensureSheetModal() {
    var existing = document.getElementById("mk-leads-sheet-modal");
    if (existing) return existing;
    var root = document.createElement("div");
    root.id = "mk-leads-sheet-modal";
    root.className = "mk-leads-sheet-modal";
    root.hidden = true;
    root.innerHTML =
      '<div class="mk-leads-sheet-modal__backdrop" data-sheet-close="1"></div>' +
      '<div class="mk-leads-sheet-modal__panel" role="dialog" aria-modal="true" aria-labelledby="mk-sheet-title">' +
      '  <header class="mk-leads-sheet-modal__head">' +
      '    <h2 id="mk-sheet-title">Google Sheet → Lead</h2>' +
      '    <button type="button" class="mk-leads-sheet-modal__x" data-sheet-close="1" aria-label="Đóng">×</button>' +
      "  </header>" +
      '  <div class="mk-leads-sheet-modal__body">' +
      '    <p class="mk-leads-sheet-modal__hint">Share sheet với email service account (Viewer), dán Spreadsheet URL/ID + Service Account JSON. Poll mỗi 1 phút.</p>' +
      '    <label class="mk-leads-sheet-field"><span>Link hoặc Spreadsheet ID</span>' +
      '      <input type="text" id="mk-sheet-spreadsheet" placeholder="https://docs.google.com/spreadsheets/d/.../edit hoặc ID" autocomplete="off" />' +
      "    </label>" +
      '    <label class="mk-leads-sheet-field"><span>Tên tab / range</span>' +
      '      <input type="text" id="mk-sheet-range" placeholder="Sheet1 hoặc Form!A:Z" />' +
      "    </label>" +
      '    <label class="mk-leads-sheet-field"><span>Service Account JSON <em id="mk-sheet-sa-status"></em></span>' +
      '      <textarea id="mk-sheet-sa" rows="6" placeholder="Dán toàn bộ JSON (type service_account…). Để trống nếu đã cấu hình và không đổi."></textarea>' +
      "    </label>" +
      '    <details class="mk-leads-sheet-advanced" open>' +
      "      <summary>Ánh xạ cột — map header Google Sheet sang field CRM</summary>" +
      '      <p class="mk-leads-sheet-map-hint">Map cột lõi + 3 câu Form. CRM tự tính <strong>Kết quả sơ lược</strong> và điền <strong>Mô hình kinh doanh</strong>. Trùng SĐT vẫn tạo lead mới (badge nhóm). Khu vực 1/2/3 điền nếu Sheet có cột khu vực.</p>' +
      '      <div class="mk-leads-sheet-map-gui" id="mk-sheet-map-gui">' +
      mapFieldRowHtml("name", "Tên khách") +
      mapFieldRowHtml("phone", "Số điện thoại") +
      mapFieldRowHtml("email", "Email") +
      mapFieldRowHtml("address", "Địa chỉ") +
      mapFieldRowHtml("q1", "Câu 1 – Tình trạng") +
      mapFieldRowHtml("q2", "Câu 2 – Mô hình") +
      mapFieldRowHtml("q3", "Câu 3 – Ngân sách") +
      mapFieldRowHtml("region", "Khu vực (1 / 2 / 3)") +
      "      </div>" +
      '      <details class="mk-leads-sheet-map-json">' +
      "        <summary>JSON nâng cao (tuỳ chọn)</summary>" +
      '        <textarea id="mk-sheet-map" rows="6" spellcheck="false"></textarea>' +
      "      </details>" +
      "    </details>" +
      '    <label class="mk-leads-sheet-check"><input type="checkbox" id="mk-sheet-enabled" /> Bật poll tự động mỗi 1 phút</label>' +
      '    <div class="mk-leads-sheet-status" id="mk-sheet-status" hidden></div>' +
      "  </div>" +
      '  <footer class="mk-leads-sheet-modal__foot">' +
      '    <button type="button" class="mk-leads-btn mk-leads-btn--outline" data-sheet-close="1">Huỷ</button>' +
      '    <button type="button" class="mk-leads-btn mk-leads-btn--outline" id="mk-sheet-poll">Lưu &amp; đồng bộ ngay</button>' +
      '    <button type="button" class="mk-leads-btn mk-leads-btn--primary" id="mk-sheet-save">Lưu</button>' +
      "  </footer>" +
      "</div>";
    document.body.appendChild(root);
    root.addEventListener("click", function (e) {
      if (e.target && e.target.getAttribute && e.target.getAttribute("data-sheet-close") === "1") {
        closeSheetModal();
      }
    });
    root.addEventListener("input", function (e) {
      var t = e.target;
      if (t && t.getAttribute && t.getAttribute("data-map-field")) {
        syncSheetMapJsonFromGui();
      } else if (t && t.id === "mk-sheet-map") {
        applySheetMapGuiFromObject(parseSheetMapRaw(t.value));
      }
    });
    return root;
  }

  function parseSpreadsheetId(input) {
    var s = String(input || "").trim();
    if (!s) return "";
    var m = s.match(/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/);
    if (m) return m[1];
    return s.replace(/[?#].*$/, "").trim();
  }

  function closeSheetModal() {
    var el = document.getElementById("mk-leads-sheet-modal");
    if (el) el.hidden = true;
  }

  function setSheetStatus(msg, isErr) {
    var box = document.getElementById("mk-sheet-status");
    if (!box) return;
    if (!msg) {
      box.hidden = true;
      box.textContent = "";
      return;
    }
    box.hidden = false;
    box.textContent = msg;
    box.classList.toggle("is-error", !!isErr);
  }

  function collectSheetPayload(requireSaIfMissing, settings) {
    var id = parseSpreadsheetId(document.getElementById("mk-sheet-spreadsheet").value);
    var range = (document.getElementById("mk-sheet-range").value || "").trim() || "Sheet1";
    var sa = (document.getElementById("mk-sheet-sa").value || "").trim();
    var en = document.getElementById("mk-sheet-enabled").checked;
    if (!id) {
      throw new Error("Thiếu Spreadsheet ID / link.");
    }
    // GUI is source of truth; push into JSON text for advanced view.
    var map = buildSheetMapFromGui();
    syncSheetMapJsonFromGui();
    var payload = {
      enabled: en ? 1 : 0,
      spreadsheet_id: id,
      sheet_range: range,
      column_map: map || {},
    };
    if (sa) {
      payload.service_account_json = sa;
    } else if (requireSaIfMissing && !(settings && settings.service_account_configured)) {
      throw new Error("Dán Service Account JSON lần đầu.");
    }
    return payload;
  }

  function openSheetSettings() {
    if (!store || typeof store.getSheetSettings !== "function") {
      window.alert("API chưa sẵn sàng.");
      return;
    }
    var modal = ensureSheetModal();
    setSheetStatus("");
    store
      .getSheetSettings()
      .then(function (s) {
        if (!s) {
          window.alert("Chỉ Admin cấu hình được Google Sheet.");
          return;
        }
        document.getElementById("mk-sheet-spreadsheet").value = s.spreadsheet_id || "";
        document.getElementById("mk-sheet-range").value = s.sheet_range || "Sheet1";
        var mapObj =
          typeof s.column_map === "object" && s.column_map
            ? s.column_map
            : parseSheetMapRaw(String(s.column_map || "{}"));
        document.getElementById("mk-sheet-map").value = JSON.stringify(mapObj, null, 2);
        applySheetMapGuiFromObject(mapObj);
        document.getElementById("mk-sheet-sa").value = "";
        document.getElementById("mk-sheet-enabled").checked = !!s.enabled;
        var st = document.getElementById("mk-sheet-sa-status");
        if (st) {
          st.textContent = s.service_account_configured
            ? s.service_account_email
              ? "(đã cấu hình: " + s.service_account_email + ")"
              : "(đã cấu hình)"
            : "(chưa có)";
        }
        setSheetStatus(
          [
            s.last_poll_at ? "Poll gần nhất: " + s.last_poll_at : "",
            s.last_result ? s.last_result : "",
            s.last_error ? "Lỗi: " + s.last_error : "",
          ]
            .filter(Boolean)
            .join(" · ") || "",
          !!s.last_error,
        );
        modal.hidden = false;

        var saveBtn = document.getElementById("mk-sheet-save");
        var pollBtn = document.getElementById("mk-sheet-poll");
        function doSave(andPoll) {
          setSheetStatus(andPoll ? "Đang lưu & đồng bộ…" : "Đang lưu…", false);
          var payload;
          try {
            payload = collectSheetPayload(true, s);
          } catch (err) {
            setSheetStatus(err.message || String(err), true);
            return;
          }
          saveBtn.disabled = true;
          pollBtn.disabled = true;
          return store
            .saveSheetSettings(payload)
            .then(function (next) {
              s = next || s;
              if (!andPoll) {
                setSheetStatus("Đã lưu cấu hình.", false);
                return;
              }
              return store.pollSheetNow().then(function (res) {
                var msg =
                  res && res.summary
                    ? res.summary
                    : res && res.error
                      ? res.error
                      : "Poll xong: imported=" +
                        (res && res.imported != null ? res.imported : "?");
                setSheetStatus(msg, !!(res && res.error) || (res && res.success === false));
                if (store.refreshLeadsList) return store.refreshLeadsList();
              });
            })
            .then(function () {
              renderAll();
            })
            .catch(function (err) {
              setSheetStatus((err && (err.message || err)) || "Lưu thất bại.", true);
            })
            .then(function () {
              saveBtn.disabled = false;
              pollBtn.disabled = false;
            });
        }
        saveBtn.onclick = function () {
          doSave(false);
        };
        pollBtn.onclick = function () {
          doSave(true);
        };
      })
      .catch(function (err) {
        window.alert((err && (err.message || err)) || "Không mở được cấu hình Sheet.");
      });
  }

  function decorateStaticIcons() {
    var map = {
      "mk-leads-segments-icon": "bookmark",
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
        var sheetBtn = $("mk-leads-sheet-btn");
        if (sheetBtn) {
          sheetBtn.addEventListener("click", function (e) {
            e.preventDefault();
            openSheetSettings();
          });
        }
        renderAll();
        syncSortHeaders();
        startLiveLeadsWatcher();
      })
      .catch(function (err) {
        console.error("Leads API bootstrap failed", err);
        decorateStaticIcons();
        bindEvents();
        renderAll();
        syncSortHeaders();
        startLiveLeadsWatcher();
      });
  }

  /**
   * Keep list in sync without full page reload:
   * - Soft refresh list ~every 20s (re-render only if set of leads changed)
   * - Admin: also run sheet poll ~every 45s while list is open
   * - When tab becomes visible again → refresh immediately
   */
  var _liveLeadsTimer = null;
  var _liveLeadsBusy = false;
  var _liveLeadIdsKey = "";
  var _lastSheetPollMs = 0;

  function isUiBusyForAutoRefresh() {
    if (document.querySelector(".mk-leads-inline-input")) return true;
    if (document.querySelector(".mk-leads-filter--dropdown.is-open")) return true;
    var sheetModal = document.getElementById("mk-leads-sheet-modal");
    if (sheetModal && !sheetModal.hidden) return true;
    if (document.querySelector(".modal.in, .modal.show")) return true;
    return false;
  }

  function leadIdsFingerprint(leads) {
    if (!leads || !leads.length) return "0";
    var ids = leads
      .map(function (l) {
        return String((l && (l.crmid || l.id)) || "");
      })
      .filter(Boolean)
      .sort();
    return String(ids.length) + ":" + ids.join(",");
  }

  function softRefreshLeadsList() {
    if (!store || typeof store.refreshLeadsList !== "function") {
      return Promise.resolve(false);
    }
    if (isUiBusyForAutoRefresh()) {
      return Promise.resolve(false);
    }
    var before = _liveLeadIdsKey;
    return store.refreshLeadsList().then(function (list) {
      var next = leadIdsFingerprint(list || (store.getLeads ? store.getLeads() : []));
      if (next !== before) {
        _liveLeadIdsKey = next;
        renderAll();
        return true;
      }
      return false;
    });
  }

  function runLiveLeadsTick(forceSheetPoll) {
    if (_liveLeadsBusy || document.hidden) return;
    if (!store) return;
    _liveLeadsBusy = true;

    var now = Date.now();
    var doSheetPoll =
      forceSheetPoll || now - _lastSheetPollMs >= 45000;

    var chain = Promise.resolve();
    if (doSheetPoll && typeof store.pollSheetNow === "function") {
      chain = (typeof store.sheetPollStatus === "function"
        ? store.sheetPollStatus()
        : Promise.resolve({ enabled: true })
      )
        .then(function (st) {
          if (st && st.enabled === false) return null;
          return store.pollSheetNow().catch(function () {
            return null;
          });
        })
        .then(function () {
          _lastSheetPollMs = Date.now();
        })
        .catch(function () {
          /* non-admin or network — list refresh still runs */
        });
    }

    chain
      .then(function () {
        return softRefreshLeadsList();
      })
      .catch(function () {
        /* silent */
      })
      .then(function () {
        _liveLeadsBusy = false;
      });
  }

  function startLiveLeadsWatcher() {
    if (_liveLeadsTimer) return;
    if (!store || typeof store.refreshLeadsList !== "function") return;
    _liveLeadIdsKey = leadIdsFingerprint(store.getLeads ? store.getLeads() : []);
    // First sync soon so sheet rows appear without waiting a full minute
    window.setTimeout(function () {
      runLiveLeadsTick(true);
    }, 6000);
    _liveLeadsTimer = window.setInterval(function () {
      runLiveLeadsTick(false);
    }, 20000);
    document.addEventListener("visibilitychange", function () {
      if (!document.hidden) {
        runLiveLeadsTick(true);
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
