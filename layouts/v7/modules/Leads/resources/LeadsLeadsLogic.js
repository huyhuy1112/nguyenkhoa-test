/**
 * Tag-driven lead derivation + commerce/next-action helpers (cache-only UI).
 * Backend: see modules/Leads/docs/LEADS_CACHE_BACKEND_SPEC.md
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

  function pick(vi, en) {
    return vi || en || "";
  }

  var PURCHASE_MAP_RAW = {
    mua_lan_dau: { vi: "Mua lần đầu", en: "New Purchase" },
    mua_lai: { vi: "Mua lại", en: "Repeat Purchase" },
    khong_mua: { vi: "Không mua", en: "Not Buying" },
    ngung_mua: { vi: "Ngưng mua", en: "Stopped" },
  };

  var PROGRAM_MAP_RAW = {
    nhuong_quyen: { vi: "Nhượng quyền", en: "Franchise" },
    pcth: { vi: "Chương trình PCTH", en: "PCTH Program" },
    mien_phi_online: { vi: "Lớp miễn phí", en: "Free Class" },
    mien_phi_offline: { vi: "Lớp miễn phí", en: "Free Class" },
    van_hanh: { vi: "Chương trình PCTH", en: "PCTH Program" },
    mkt: { vi: "Chương trình PCTH", en: "PCTH Program" },
    lop_khac: { vi: "Chương trình PCTH", en: "PCTH Program" },
  };

  var TIER_MAP_RAW = {
    vang: { vi: "Vàng", en: "Gold" },
    bac: { vi: "Bạc", en: "Silver" },
    dong: { vi: "Đồng", en: "Bronze" },
  };

  var SEGMENT_LABELS = {
    gia_dinh: "Gia đình",
    chuan_bi_mo: "Chưa có quán",
    co_quan: "Đã có quán",
  };

  function mapLabel(rawMap, key, fallback) {
    var row = rawMap[key];
    if (!row) return fallback || key;
    return pick(row.vi, row.en);
  }

  var ACTIVITY_TYPES = ["task", "call", "meeting"];

  function daysSince(iso) {
    if (!iso) return 0;
    return Math.floor((Date.now() - new Date(iso).getTime()) / 86400000);
  }

  function findTag(tags, pool) {
    for (var i = 0; i < tags.length; i++) {
      if (pool.indexOf(tags[i]) >= 0) return tags[i];
    }
    return null;
  }

  var CSKH_ALERT_DAYS = 7;
  var CSKH_EXCLUDED_TAGS = [
    "ngung_cham_soc",
    "dung_cham_soc",
    "khong_xac_nhan_tham_gia",
  ];

  function leadExcludedFromCskh(tags) {
    for (var i = 0; i < CSKH_EXCLUDED_TAGS.length; i++) {
      if (tags.indexOf(CSKH_EXCLUDED_TAGS[i]) >= 0) return true;
    }
    return false;
  }

  /** Cùng điều kiện với Cảnh báo → Cần CSKH (rule-cskh). */
  function needsCskh(lead) {
    var tags = lead.tags || [];
    if (leadExcludedFromCskh(tags)) return false;
    return daysSince(lead.last_touch) >= CSKH_ALERT_DAYS;
  }

  function derive(lead) {
    var tags = lead.tags || [];
    var purchaseTag = findTag(tags, Object.keys(PURCHASE_MAP_RAW));
    var programTag = findTag(tags, Object.keys(PROGRAM_MAP_RAW));
    var tierTag = findTag(tags, Object.keys(TIER_MAP_RAW));
    var days = daysSince(lead.last_touch);
    var stage = purchaseTag
      ? mapLabel(PURCHASE_MAP_RAW, purchaseTag)
      : null;
    var type = programTag
      ? mapLabel(PROGRAM_MAP_RAW, programTag)
      : pick("Chương trình PCTH", "PCTH Program");
    var tier = tierTag ? mapLabel(TIER_MAP_RAW, tierTag) : null;
    var stale = needsCskh(lead);
    var high =
      (lead.value || 0) >= 25000000 &&
      (tags.indexOf("mua_lai") >= 0 || tags.indexOf("nhuong_quyen") >= 0);
    return {
      stage: stage,
      type: type,
      tier: tier,
      tierKey: tierTag || null,
      stale: stale,
      high: high,
      days: days,
    };
  }

  function parsePurchaseDate(dateStr) {
    if (!dateStr) return null;
    var parts = String(dateStr).split("/");
    if (parts.length !== 3) return null;
    var d = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10) - 1;
    var y = parseInt(parts[2], 10);
    if (!d || m < 0 || !y) return null;
    return new Date(y, m, d);
  }

  function purchasesInLastDays(purchases, days) {
    var cutoff = Date.now() - days * 86400000;
    return (purchases || []).filter(function (p) {
      var dt = parsePurchaseDate(p.date);
      return dt && dt.getTime() >= cutoff;
    });
  }

  /** Distinct order ids in rolling 30-day window (fallback: row count). */
  function monthlyOrderCount(lead) {
    var recent = purchasesInLastDays(lead.purchases || [], 30);
    if (!recent.length) return 0;
    var ids = {};
    recent.forEach(function (p) {
      ids[p.orderId || p.date + "|" + p.product] = true;
    });
    return Object.keys(ids).length;
  }

  function totalProductsPurchased(lead) {
    var sum = 0;
    (lead.purchases || []).forEach(function (p) {
      sum += parseInt(p.qty, 10) || 0;
    });
    return sum;
  }

  function recentOrderValue(lead) {
    var items = (lead.purchases || []).slice();
    if (!items.length) return 0;
    items.sort(function (a, b) {
      var da = parsePurchaseDate(a.date);
      var db = parsePurchaseDate(b.date);
      return (db ? db.getTime() : 0) - (da ? da.getTime() : 0);
    });
    return items[0].value || 0;
  }

  /** Group line items by order — for tab UI (order names, not products). */
  function groupOrders(purchases) {
    var map = {};
    (purchases || []).forEach(function (p) {
      var id = p.orderId || p.orderName || p.product || "order";
      if (!map[id]) {
        map[id] = {
          orderId: id,
          orderName: p.orderName || p.orderId || "Đơn hàng",
          value: 0,
          qty: 0,
          date: p.date,
          dateTs: parsePurchaseDate(p.date) ? parsePurchaseDate(p.date).getTime() : 0,
        };
      }
      map[id].value += p.value || 0;
      map[id].qty += parseInt(p.qty, 10) || 0;
      var ts = parsePurchaseDate(p.date) ? parsePurchaseDate(p.date).getTime() : 0;
      if (ts >= map[id].dateTs) {
        map[id].dateTs = ts;
        map[id].date = p.date;
      }
    });
    return Object.keys(map)
      .map(function (k) {
        return map[k];
      })
      .sort(function (a, b) {
        return b.dateTs - a.dateTs;
      });
  }

  function ordersInLastDays(lead, days) {
    var recentIds = {};
    var recent = purchasesInLastDays(lead.purchases || [], days);
    recent.forEach(function (p) {
      recentIds[p.orderId || p.orderName || p.product] = true;
    });
    return groupOrders(lead.purchases || []).filter(function (o) {
      return recentIds[o.orderId];
    });
  }

  /** Orders older than rolling window — excludes monthly tab list. */
  function ordersOutsideLastDays(lead, days) {
    var recentIds = {};
    purchasesInLastDays(lead.purchases || [], days).forEach(function (p) {
      recentIds[p.orderId || p.orderName || p.product] = true;
    });
    return groupOrders(lead.purchases || []).filter(function (o) {
      return !recentIds[o.orderId];
    });
  }

  function activityTypePrefix(type) {
    if (type === "call") return "Gọi: ";
    if (type === "meeting") return "Họp: ";
    if (type === "task") return "Việc: ";
    return "";
  }

  /**
   * Next Action (List / Detail) = ghi chú tự do đã lưu trên lead.
   * Không lấy từ Calendar / nhắc Last Touch Call.
   */
  function deriveNextAction(lead) {
    var stored = String(lead.next_action || "").trim();
    if (/^(Nhắc gọi Call\s*#|Đã nghe máy|Đã đủ 3 lần gọi|Gọi:\s*Nhắc gọi)/i.test(stored)) {
      return "";
    }
    return stored;
  }

  /** Khung thời gian hành động tiếp theo từ rule (alert_days + last_touch). */
  function nextActionTimeframeMeta(lead) {
    var alertDays = lead.rule_alert_days;
    if (alertDays == null || alertDays <= 0) return null;
    if (lead.next_action_overdue) {
      return {
        kind: "overdue",
        days: lead.next_action_days_overdue || 0,
        alertDays: alertDays,
      };
    }
    if (lead.next_action_days_remaining != null) {
      return {
        kind: "remaining",
        days: lead.next_action_days_remaining,
        alertDays: alertDays,
      };
    }
    var idle = daysSince(lead.last_touch);
    var rem = alertDays - idle;
    if (rem < 0) {
      return { kind: "overdue", days: -rem, alertDays: alertDays };
    }
    return { kind: "remaining", days: rem, alertDays: alertDays };
  }

  function nextActionTimeframeLabel(lead) {
    var meta = nextActionTimeframeMeta(lead);
    if (!meta) return "";
    if (meta.kind === "overdue") {
      return pick("Quá hạn " + meta.days + " ngày", "Overdue " + meta.days + "d");
    }
    if (meta.days === 0) {
      return pick("Hôm nay", "Today");
    }
    if (meta.kind === "remaining") {
      return pick("Còn " + meta.days + " ngày", meta.days + "d left");
    }
    return pick("Còn " + meta.alertDays + " ngày", meta.alertDays + "d left");
  }

  function nextActionCellHtml(lead, escFn) {
    var esc =
      typeof escFn === "function"
        ? escFn
        : function (s) {
            return String(s == null ? "" : s);
          };
    var next = deriveNextAction(lead);
    var tf = nextActionTimeframeLabel(lead);
    if (!next && !tf) {
      return '<span class="mk-leads-muted">—</span>';
    }
    var html = "";
    if (next) {
      html += '<span class="mk-leads-next-action__text">' + esc(next) + "</span>";
    }
    if (tf) {
      var meta = nextActionTimeframeMeta(lead);
      var cls = "mk-leads-next-action__time";
      if (meta && meta.kind === "overdue") {
        cls += " mk-leads-next-action__time--overdue";
      }
      html += '<span class="' + cls + '">' + esc(tf) + "</span>";
    }
    return '<div class="mk-leads-next-action">' + html + "</div>";
  }

  function openCalendarTasks(lead) {
    return (lead.calendarTasks || [])
      .filter(function (t) {
        var status = String(t.status || "open").toLowerCase();
        return status !== "done" && status !== "completed" && status !== "closed";
      })
      .map(function (t) {
        return {
          id: t.id,
          type: t.type,
          subject: t.subject,
          when: t.dueLabel || touchLabel(daysSince(t.dueAt)),
        };
      });
  }

  function fmtVND(n) {
    try {
      return new Intl.NumberFormat("vi-VN").format(n || 0) + " \u20ab";
    } catch (e) {
      return String(n || 0) + " \u20ab";
    }
  }

	function touchLabel(days) {
		if (days <= 0) return pick("Hôm nay", "Today");
		return pick(days + " ngày trước", days + "d ago");
	}

	/** Decode HTML entities from CRM (e.g. Kh&ocirc;ng → Không) before re-escaping for HTML. */
	function decodeHtmlEntities(str) {
		var s = String(str == null ? "" : str);
		if (!s || s.indexOf("&") < 0) {
			return s;
		}
		try {
			var ta = document.createElement("textarea");
			var prev = s;
			var i;
			for (i = 0; i < 3; i++) {
				ta.innerHTML = prev;
				var next = ta.value;
				if (next === prev) {
					break;
				}
				prev = next;
			}
			return prev;
		} catch (e) {
			return s;
		}
	}

	/**
	 * Cột / field "Tương tác gần đây" = log Last Touch Call (không dùng Today/Nd ago).
	 */
	function lastTouchCallLogHtml(lead, escFn) {
		var esc =
			typeof escFn === "function"
				? escFn
				: function (s) {
						return String(s == null ? "" : s)
							.replace(/&/g, "&amp;")
							.replace(/</g, "&lt;")
							.replace(/>/g, "&gt;")
							.replace(/"/g, "&quot;");
				  };
		var lt = lead && lead.lastTouchCalls ? lead.lastTouchCalls : null;
		var calls = lt && lt.calls ? lt.calls : [];
		if (!calls.length) {
			return '<span class="mk-leads-muted">Chưa có cuộc gọi</span>';
		}
		return (
			'<div class="mk-leads-call-log">' +
			calls
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
						'<div class="mk-leads-call-log__item">' +
						esc(decodeHtmlEntities(line)) +
						"</div>"
					);
				})
				.join("") +
			"</div>"
		);
	}

	function lastTouchCallLogText(lead) {
		var lt = lead && lead.lastTouchCalls ? lead.lastTouchCalls : null;
		var calls = lt && lt.calls ? lt.calls : [];
		if (!calls.length) {
			return "";
		}
		return calls
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
				return decodeHtmlEntities(line);
			})
			.join("\n");
	}

  function ownerInitials(name) {
    return String(name || "")
      .trim()
      .split(/\s+/)
      .slice(-1)[0]
      .charAt(0)
      .toUpperCase();
  }

  var OWNER_COLORS = { Linh: "#8b5cf6", Minh: "#10b981", Hà: "#f97316", Ha: "#f97316" };
  var OWNER_PALETTE = ["#6366f1", "#8b5cf6", "#0ea5e9", "#10b981", "#f97316", "#ec4899", "#14b8a6", "#e11d48"];

  function ownerColor(name, index) {
    if (OWNER_COLORS[name]) return OWNER_COLORS[name];
    var s = String(name || "");
    if (!s) return "#64748b";
    var hash = 0;
    for (var i = 0; i < s.length; i++) {
      hash = s.charCodeAt(i) + ((hash << 5) - hash);
    }
    if (typeof index === "number" && index >= 0) {
      return OWNER_PALETTE[index % OWNER_PALETTE.length];
    }
    return OWNER_PALETTE[Math.abs(hash) % OWNER_PALETTE.length];
  }

  /**
   * Vtiger Tag.tpl doesn't expose a stable tag key for styling.
   * We derive a normalized slug from the visible label and attach it as data-tag,
   * so CSS can reuse the same palette as Lead Create/Edit pills.
   */
  /** Display label / English tier name → canonical tag key (LeadsMkEdit.css data-tag). */
  var TAG_LABEL_ALIASES = {
    silver: "bac",
    gold: "vang",
    bronze: "dong",
    vang: "vang",
    bac: "bac",
    dong: "dong",
    facebook: "facebook",
    tiktok: "tiktok",
    website: "website",
    zalo: "zalo",
    individual: "individual",
    company: "company",
    mua_lan_dau: "mua_lan_dau",
    mua_lai: "mua_lai",
    mua_it_lai: "mua_it_lai",
    khong_mua: "khong_mua",
    ngung_mua: "ngung_mua",
    dang_tu_van: "dang_tu_van",
    dung_cham_soc: "dung_cham_soc",
    kh_can_nhac: "kh_can_nhac",
    chua_hoc: "chua_hoc",
    da_hoc: "da_hoc",
    nguyen_lieu_chuoi: "nguyen_lieu_chuoi",
    thu_3: "thu_3",
    lop_online: "lop_online",
    moi_lai: "moi_lai",
    da_tg_free: "da_tg_free",
    doi_lich: "doi_lich",
    dori_lich: "doi_lich",
    mien_phi_online: "mien_phi_online",
    mien_phi_offline: "mien_phi_offline",
    pcth: "pcth",
    van_hanh: "van_hanh",
    mkt: "mkt",
    lop_khac: "lop_khac",
    nhuong_quyen: "nhuong_quyen",
    kv1: "kv1",
    kv2: "kv2",
    kv3: "kv3",
  };

  function slugifyTagLabel(label) {
    var s = String(label || "").trim().toLowerCase();
    if (!s) return "";
    if (s.charAt(0) === "#") s = s.slice(1);
    try {
      s = s.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    } catch (e) {}
    s = s
      .replace(/đ/g, "d")
      .replace(/[^a-z0-9]+/g, "_")
      .replace(/^_+|_+$/g, "")
      .replace(/_+/g, "_");
    return s;
  }

  function resolveTagSlug(label) {
    var slug = slugifyTagLabel(label);
    if (!slug) return "";
    return TAG_LABEL_ALIASES[slug] || slug;
  }

  function applyVtigerTagDataAttrs(rootEl) {
    var rootNode = rootEl && rootEl.querySelector ? rootEl : document;
    if (!rootNode || !rootNode.querySelectorAll) return;
    var tags = rootNode.querySelectorAll(".tagContainer .tag, .detailTagList .tag, .multiLevelTagList .tag");
    for (var i = 0; i < tags.length; i++) {
      var el = tags[i];
      var labelEl = el.querySelector(".tagLabel");
      var label = labelEl ? labelEl.textContent : el.getAttribute("title") || "";
      var slug = resolveTagSlug(label);
      if (slug) el.setAttribute("data-tag", slug);
    }
  }

  // Run once and keep in sync when tags are edited/added.
  if (typeof document !== "undefined") {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", function () {
        applyVtigerTagDataAttrs(document);
      });
    } else {
      applyVtigerTagDataAttrs(document);
    }
    try {
      var mo = new MutationObserver(function (mutations) {
        for (var j = 0; j < mutations.length; j++) {
          var m = mutations[j];
          if (m.addedNodes && m.addedNodes.length) {
            applyVtigerTagDataAttrs(document);
            break;
          }
        }
      });
      mo.observe(document.documentElement || document.body, { childList: true, subtree: true });
    } catch (e) {}
  }

  root.LeadsLeadsLogic = {
    derive: derive,
    daysSince: daysSince,
    fmtVND: fmtVND,
    touchLabel: touchLabel,
    decodeHtmlEntities: decodeHtmlEntities,
    lastTouchCallLogHtml: lastTouchCallLogHtml,
    lastTouchCallLogText: lastTouchCallLogText,
    ownerInitials: ownerInitials,
    ownerColor: ownerColor,
    SEGMENT_LABELS: SEGMENT_LABELS,
    PURCHASE_MAP_RAW: PURCHASE_MAP_RAW,
    PROGRAM_MAP_RAW: PROGRAM_MAP_RAW,
    TIER_MAP_RAW: TIER_MAP_RAW,
    get PURCHASE_MAP() {
      var out = {};
      Object.keys(PURCHASE_MAP_RAW).forEach(function (k) {
        out[k] = mapLabel(PURCHASE_MAP_RAW, k);
      });
      return out;
    },
    get PROGRAM_MAP() {
      var out = {};
      Object.keys(PROGRAM_MAP_RAW).forEach(function (k) {
        out[k] = mapLabel(PROGRAM_MAP_RAW, k);
      });
      return out;
    },
    get TIER_MAP() {
      var out = {};
      Object.keys(TIER_MAP_RAW).forEach(function (k) {
        out[k] = mapLabel(TIER_MAP_RAW, k);
      });
      return out;
    },
    monthlyOrderCount: monthlyOrderCount,
    totalProductsPurchased: totalProductsPurchased,
    recentOrderValue: recentOrderValue,
    groupOrders: groupOrders,
    ordersInLastDays: ordersInLastDays,
    ordersOutsideLastDays: ordersOutsideLastDays,
    deriveNextAction: deriveNextAction,
    nextActionTimeframeMeta: nextActionTimeframeMeta,
    nextActionTimeframeLabel: nextActionTimeframeLabel,
    nextActionCellHtml: nextActionCellHtml,
    openCalendarTasks: openCalendarTasks,
    purchasesInLastDays: purchasesInLastDays,
    CSKH_ALERT_DAYS: CSKH_ALERT_DAYS,
    needsCskh: needsCskh,
    leadExcludedFromCskh: leadExcludedFromCskh,
  };
})(typeof window !== "undefined" ? window : this);
