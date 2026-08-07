/**
 * ServiceContracts Create/Edit — franchise form (SALES only).
 * Kiểm tra trùng: SĐT + ngày đăng ký / bảo lưu (180 ngày theo Rule).
 */
(function ($) {
	"use strict";

	var MK_BUILD = "20260807_sc_src_aff1";
	var DEFAULT_PICKLISTS = {
		franchise_status: [
			"Quan Tâm/Tham Khảo",
			"Không đủ tài chính",
			"Đã Kí Quỹ",
			"Đang chăm sóc",
			"Chuyển sang Nguyên Khoa",
		],
		// BA: Nguồn data = Được giới thiệu (Affiliate) hoặc trống
		data_source: ["Được giới thiệu"],
		contact_status: ["Chưa gọi", "Đã gửi tư vấn", "Thuê bao", "Ko nghe Máy Lần 1", "Ko nghe Máy Lần 2", "Ko nghe Máy Lần 3"],
	};
	var PAYMENT_OPTIONS = ["Chuyển khoản", "Tiền mặt", "Thẻ", "Ví"];
	var DEFAULT_RETENTION_DAYS = 180;
	var assignableUsers = [];
	var referrerOptions = [];
	var affiliateTiers = [];
	var lastRetentionDays = DEFAULT_RETENTION_DAYS;
	var dupState = { result: "", in_retention: false, match: null };

	function isScoped() {
		return (
			$("body").data("module") === "ServiceContracts" &&
			$("body").data("view") === "Edit" &&
			($("body").data("app") === "SALES" || !$("body").data("app")) &&
			$("#mkScCreateWorkspace").length > 0 &&
			$("#mkScFranchiseForm").length > 0
		);
	}

	function recordId() {
		var fromInput = String($("#mkScRecordId").val() || "").trim();
		if (fromInput) return fromInput;
		return String($("#mkScCreateWorkspace").attr("data-record-id") || "").trim();
	}

	function listUrl() {
		return "index.php?module=ServiceContracts&view=List&app=SALES";
	}

	function detailUrl(id) {
		return "index.php?module=ServiceContracts&view=Detail&record=" + encodeURIComponent(id) + "&app=SALES";
	}

	function todayYmd() {
		var d = new Date();
		var m = d.getMonth() + 1;
		var day = d.getDate();
		return (
			d.getFullYear() +
			"-" +
			(m < 10 ? "0" : "") +
			m +
			"-" +
			(day < 10 ? "0" : "") +
			day
		);
	}

	function showError(msg) {
		var $el = $("#mkScFormError");
		if (!$el.length) return;
		$el.text(msg || "").prop("hidden", !msg);
	}

	function clearDupBanner() {
		$("#mkScDupSlot").prop("hidden", true);
		$("#mkScDupBanner").empty().removeClass("mk-sc-dup-banner--warn mk-sc-dup-banner--ok");
		$("#mkScPhone").removeClass("mk-sc-input--dup");
	}

	function showDupBanner(dup) {
		var $slot = $("#mkScDupSlot");
		var $banner = $("#mkScDupBanner");
		if (!$slot.length || !$banner.length) return;
		var m = (dup && dup.match) || {};
		var referrer = m.referral_code || m.referrer || "—";
		var sale = m.sale_owner || "—";
		var reg = m.registration_date || "—";
		var exp = m.retention_expires_at || "—";

		if (dup && dup.in_retention) {
			$banner
				.addClass("mk-sc-dup-banner--warn")
				.removeClass("mk-sc-dup-banner--ok")
				.html(
					'<div class="mk-sc-dup-banner__title">SĐT này đang được bảo lưu — không thể tạo mới</div>' +
						'<div class="mk-sc-dup-banner__grid">' +
						'<div><span>Người giới thiệu</span><strong>' +
						escapeHtml(referrer) +
						"</strong></div>" +
						"<div><span>Sale phụ trách</span><strong>" +
						escapeHtml(sale) +
						"</strong></div>" +
						"<div><span>Ngày đăng ký</span><strong>" +
						escapeHtml(reg) +
						"</strong></div>" +
						"<div><span>Hết hạn bảo lưu</span><strong>" +
						escapeHtml(exp) +
						"</strong></div>" +
						"</div>" +
						'<p class="mk-sc-dup-banner__note">Không được nhận quyền giới thiệu mới cho đến khi hết hạn bảo lưu.</p>'
				);
			$slot.prop("hidden", false);
			$("#mkScPhone").addClass("mk-sc-input--dup");
			try {
				$slot[0].scrollIntoView({ behavior: "smooth", block: "nearest" });
			} catch (e) {}
			return;
		}

		var result = (dup && dup.result) || "";
		if (result.indexOf("hết hạn") >= 0) {
			$banner
				.addClass("mk-sc-dup-banner--ok")
				.removeClass("mk-sc-dup-banner--warn")
				.html(
					'<div class="mk-sc-dup-banner__title">SĐT trùng nhưng đã hết hạn bảo lưu</div>' +
						'<p class="mk-sc-dup-banner__note">Có thể đăng ký lại và gán người giới thiệu mới.</p>'
				);
			$slot.prop("hidden", false);
			$("#mkScPhone").removeClass("mk-sc-input--dup");
			return;
		}

		clearDupBanner();
	}

	function escapeHtml(s) {
		return String(s || "")
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;");
	}

	function escapeAttr(s) {
		return escapeHtml(s).replace(/'/g, "&#39;");
	}

	function fillSelect($sel, options, selected, includeEmpty) {
		var html = includeEmpty === false ? "" : '<option value="">—</option>';
		var selNorm = String(selected || "").trim().toLowerCase();
		var matched = false;
		(options || []).forEach(function (opt) {
			var v = String(opt || "");
			var isSel = selNorm !== "" && v.toLowerCase() === selNorm;
			if (isSel) matched = true;
			html +=
				'<option value="' +
				escapeAttr(v) +
				'"' +
				(isSel ? " selected" : "") +
				">" +
				escapeHtml(v) +
				"</option>";
		});
		if (selNorm && !matched && selected) {
			html +=
				'<option value="' +
				escapeAttr(selected) +
				'" selected>' +
				escapeHtml(selected) +
				"</option>";
		}
		$sel.html(html);
	}

	function enhanceSearchableSelect($sel, opts) {
		if (!$sel || !$sel.length || !$.fn.select2) return;
		opts = opts || {};
		try {
			if ($sel.data("select2")) {
				$sel.select2("destroy");
			}
		} catch (e) {}
		$sel.select2({
			placeholder: opts.placeholder || "— Chọn —",
			allowClear: opts.allowClear !== false,
			width: "100%",
			dropdownCssClass: "mk-sc-s2-drop",
			minimumResultsForSearch: 0,
			formatNoMatches: function () {
				return "Không tìm thấy";
			},
			formatSearching: function () {
				return "Đang tìm…";
			},
		});
		var val = $sel.val();
		if (val) {
			$sel.select2("val", val);
		} else {
			$sel.select2("val", "");
		}
	}

	function fillUserSelect(selectedId, selectedLabel) {
		var html = '<option value="">—</option>';
		var found = false;
		(assignableUsers || []).forEach(function (u) {
			var id = String(u.id || "");
			var label = String(u.label || u.user_name || id);
			var sel = selectedId && id === String(selectedId);
			if (sel) found = true;
			if (!sel && selectedLabel && label === selectedLabel) {
				sel = true;
				found = true;
			}
			html +=
				'<option value="' +
				escapeAttr(id) +
				'" data-label="' +
				escapeAttr(label) +
				'"' +
				(sel ? " selected" : "") +
				">" +
				escapeHtml(label) +
				"</option>";
		});
		if (!found && selectedLabel) {
			html +=
				'<option value="' +
				escapeAttr(selectedId || selectedLabel) +
				'" data-label="' +
				escapeAttr(selectedLabel) +
				'" selected>' +
				escapeHtml(selectedLabel) +
				"</option>";
		}
		$("#mkScSaleOwner").html(html);
		enhanceSearchableSelect($("#mkScSaleOwner"), {
			placeholder: "— Tìm / chọn sale phụ trách —",
		});
		syncSaleOwnerLabel();
	}

	function syncSaleOwnerLabel() {
		var $opt = $("#mkScSaleOwner option:selected");
		$("#mkScSaleOwnerLabel").val($opt.attr("data-label") || $opt.text() || "");
	}

	function applyPicklists(picklists, selected) {
		var p = picklists || DEFAULT_PICKLISTS;
		selected = selected || {};
		fillSelect($("#mkScFranchiseStatus"), p.franchise_status || DEFAULT_PICKLISTS.franchise_status, selected.franchise_status || "");
		fillSelect($("#mkScDataSource"), p.data_source || DEFAULT_PICKLISTS.data_source, selected.data_source || "");
		fillSelect($("#mkScContactStatus"), p.contact_status || DEFAULT_PICKLISTS.contact_status, selected.contact_status || "");
		fillSelect($("#mkScPaymentCondition"), PAYMENT_OPTIONS, selected.payment_condition || "Chuyển khoản", false);
	}

	function setAffiliateBadge(code) {
		var $badge = $("#mkScAffiliateBadge");
		var $hint = $("#mkScAffiliateHint");
		if (!code) {
			$badge.prop("hidden", true).text("");
			return;
		}
		$badge.prop("hidden", false).text(code);
		if ($hint.length && recordId()) {
			$hint.text(code);
		}
	}

	function formatMoneyVnd(n) {
		if (n === null || n === undefined || n === "") return "";
		var num = Math.round(Number(n) || 0);
		return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + " ₫";
	}

	function addDaysYmd(ymd, days) {
		if (!ymd || !/^\d{4}-\d{2}-\d{2}$/.test(ymd)) return "";
		var d = new Date(ymd + "T00:00:00");
		if (isNaN(d.getTime())) return "";
		d.setDate(d.getDate() + (parseInt(days, 10) || 0));
		var m = d.getMonth() + 1;
		var day = d.getDate();
		return (
			d.getFullYear() +
			"-" +
			(m < 10 ? "0" : "") +
			m +
			"-" +
			(day < 10 ? "0" : "") +
			day
		);
	}

	/**
	 * Dropdown mã AFF người giới thiệu (= mã giới thiệu).
	 */
	function fillReferrerAffSelect(selectedCode) {
		var html = '<option value="">— Chọn mã AFF người giới thiệu —</option>';
		var sel = String(selectedCode || "").toUpperCase();
		(referrerOptions || []).forEach(function (r) {
			var code = String(r.affiliate_code || "").toUpperCase();
			var label = r.label || code;
			html +=
				'<option value="' +
				escapeAttr(code) +
				'" data-name="' +
				escapeAttr(r.full_name || "") +
				'" data-reward="' +
				escapeAttr(r.reward_amount != null ? r.reward_amount : "") +
				'" data-retention="' +
				escapeAttr(r.retention_days || DEFAULT_RETENTION_DAYS) +
				'" data-tier="' +
				escapeAttr(r.tier_name || "") +
				'"' +
				(code === sel ? " selected" : "") +
				">" +
				escapeHtml(label) +
				"</option>";
		});
		$("#mkScReferrerAff").html(html);
		enhanceSearchableSelect($("#mkScReferrerAff"), {
			placeholder: "— Tìm / chọn mã AFF người giới thiệu —",
		});
	}

	function fillOwnTierSelect(selectedPrefix) {
		var html = "";
		var sel = String(selectedPrefix || "D").toUpperCase();
		var list = affiliateTiers && affiliateTiers.length ? affiliateTiers : [
			{ prefix: "A", tier_name: "Diamond", reward_amount: 30000000 },
			{ prefix: "B", tier_name: "Gold", reward_amount: 20000000 },
			{ prefix: "C", tier_name: "Silver", reward_amount: 10000000 },
			{ prefix: "D", tier_name: "Standard", reward_amount: 5000000 },
		];
		list.forEach(function (t) {
			var p = String(t.prefix || "").toUpperCase();
			var label =
				p +
				" — " +
				(t.tier_name || "") +
				" (" +
				formatMoneyVnd(t.reward_amount) +
				")";
			html +=
				'<option value="' +
				escapeAttr(p) +
				'" data-reward="' +
				escapeAttr(t.reward_amount != null ? t.reward_amount : "") +
				'"' +
				(p === sel ? " selected" : "") +
				">" +
				escapeHtml(label) +
				"</option>";
		});
		$("#mkScOwnTier").html(html);
		syncOwnTierReward();
	}

	function syncOwnTierReward() {
		var $opt = $("#mkScOwnTier option:selected");
		var amt = $opt.attr("data-reward");
		$("#mkScOwnTierReward").val(amt !== undefined && amt !== "" ? formatMoneyVnd(amt) : "");
	}

	function applyReferrerAffSelection() {
		var $opt = $("#mkScReferrerAff option:selected");
		var code = String($opt.val() || "").toUpperCase();
		var name = $opt.attr("data-name") || "";
		$("#mkScReferralCode").val(code);
		$("#mkScReferrer").val(name || code);
		// BA: có mã AFF giới thiệu → Nguồn data = "Được giới thiệu"; không → trống ("-")
		var $ds = $("#mkScDataSource");
		if ($ds.length) {
			$ds.val(code ? "Được giới thiệu" : "");
		}
		if (!code) {
			clearRewardOnly();
			lastRetentionDays = DEFAULT_RETENTION_DAYS;
			recomputeRetentionExpiry();
			return Promise.resolve(null);
		}
		var reward = $opt.attr("data-reward");
		var retention = parseInt($opt.attr("data-retention"), 10) || DEFAULT_RETENTION_DAYS;
		if (reward !== undefined && reward !== "") {
			lastRetentionDays = retention;
			$("#mkScReferralRewardAmount").val(String(reward));
			$("#mkScReferralReward").val(formatMoneyVnd(reward));
			recomputeRetentionExpiry();
			return Promise.resolve({ reward_amount: Number(reward), retention_days: retention });
		}
		return resolveReferralCode();
	}

	function clearRewardOnly() {
		$("#mkScReferralReward").val("");
		$("#mkScReferralRewardAmount").val("");
	}

	function applyResolvedTier(tier) {
		if (!tier) {
			clearRewardOnly();
			lastRetentionDays = DEFAULT_RETENTION_DAYS;
			recomputeRetentionExpiry();
			return;
		}
		lastRetentionDays = parseInt(tier.retention_days, 10) || DEFAULT_RETENTION_DAYS;
		$("#mkScReferralRewardAmount").val(
			tier.reward_amount != null ? String(tier.reward_amount) : ""
		);
		$("#mkScReferralReward").val(formatMoneyVnd(tier.reward_amount));
		if (tier.referrer_name) {
			$("#mkScReferrer").val(tier.referrer_name);
		}
		recomputeRetentionExpiry();
	}

	function ensureRegistrationDate() {
		var reg = String($("#mkScRegistrationDate").val() || "").trim();
		if (!reg) {
			reg = String($("#mkScReceivedDate").val() || "").trim() || todayYmd();
			$("#mkScRegistrationDate").val(reg);
		}
		return reg;
	}

	function recomputeRetentionExpiry() {
		var reg = ensureRegistrationDate();
		var expires = addDaysYmd(reg, lastRetentionDays);
		$("#mkScRetentionExpires").val(expires || "");
	}

	function resolveReferralCode() {
		var code = String($("#mkScReferralCode").val() || "")
			.trim()
			.toUpperCase();
		$("#mkScReferralCode").val(code);
		if (!code) {
			clearRewardOnly();
			lastRetentionDays = DEFAULT_RETENTION_DAYS;
			recomputeRetentionExpiry();
			return Promise.resolve(null);
		}
		var asOf = ensureRegistrationDate();
		return apiRequest("resolve_referral", { code: code, as_of: asOf || "" })
			.then(function (res) {
				var tier = (res && res.tier) || null;
				applyResolvedTier(tier);
				return tier;
			})
			.catch(function () {
				clearRewardOnly();
				lastRetentionDays = DEFAULT_RETENTION_DAYS;
				recomputeRetentionExpiry();
				return null;
			});
	}

	function runDuplicateCheck() {
		var phoneRaw = String($("#mkScPhone").val() || "").trim();
		var phone =
			window.MkPhoneFormat && typeof window.MkPhoneFormat.digitsOnly === "function"
				? window.MkPhoneFormat.digitsOnly(phoneRaw)
				: phoneRaw.replace(/\D+/g, "");
		phone = String(phone || "").slice(0, 10);
		if (!phone || phone.length < 8) {
			dupState = { result: "", in_retention: false, match: null };
			$("#mkScDuplicateResultValue").val("");
			clearDupBanner();
			showError("");
			return Promise.resolve(null);
		}
		return apiRequest("check_duplicate", { phone: phone, record: recordId() || "" })
			.then(function (res) {
				var dup = (res && res.duplicate) || {};
				dupState = {
					result: dup.result || "Không trùng",
					in_retention: !!dup.in_retention,
					match: dup.match || null,
				};
				$("#mkScDuplicateResultValue").val(dupState.result);
				if (dup.in_retention || (dup.result || "").indexOf("hết hạn") >= 0) {
					showDupBanner(dup);
					showError("");
					if (!dup.in_retention && (dup.result || "").indexOf("hết hạn") >= 0) {
						$("#mkScRegistrationDate").val(todayYmd());
						recomputeRetentionExpiry();
					}
				} else {
					clearDupBanner();
					showError("");
				}
				return dup;
			})
			.catch(function () {
				return null;
			});
	}

	function escapeHtml(s) {
		return String(s == null ? "" : s)
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;");
	}

	function refreshLastTouchPanel(id) {
		var rid = String(id || recordId() || "").trim();
		var $badge = $("#mkScLtBadge");
		var $hint = $("#mkScLtHint");
		var $list = $("#mkScLtList");
		var $btn = $("#mkScLtLogBtn");
		if (!$badge.length) return;
		if (!rid) {
			$badge.text("0/3").removeClass("is-done").addClass("is-open");
			$hint.text("Lưu khách trước rồi mới ghi cuộc gọi Last Touch.");
			$list.html('<li class="mk-sc-last-touch-box__empty">Chưa có Call #1 — bấm “Ghi cuộc gọi”.</li>');
			$btn.prop("disabled", true).attr("data-lt-locked", "1");
			return;
		}
		apiRequest("last_touch_call_list", { record: rid, id: rid })
			.then(function (res) {
				var lt = (res && res.lastTouchCalls) || {};
				var calls = lt.calls || [];
				var count = typeof lt.count === "number" ? lt.count : calls.length;
				var max = lt.max_calls || 3;
				var canAdd = lt.can_add !== false;
				$badge
					.text(count + "/" + max)
					.toggleClass("is-open", canAdd)
					.toggleClass("is-done", !canAdd);
				$hint.text(
					lt.hint ||
						"Call #1 → 5 giờ → #2 → #3. Không nghe máy: nhắc sau 5 giờ. Nghe máy → dừng chuỗi gọi."
				);
				if (!calls.length) {
					$list.html(
						'<li class="mk-sc-last-touch-box__empty">Chưa có Call #1 — bấm “Ghi cuộc gọi”.</li>'
					);
				} else {
					$list.html(
						calls
							.map(function (c) {
								var line =
									c.label ||
									(c.called_at_label || "") +
										" Call #" +
										(c.n || "") +
										" Kết quả: " +
										(c.result || "");
								return (
									'<li class="mk-sc-last-touch-box__item"><strong>Call #' +
									escapeHtml(String(c.n || "")) +
									"</strong> " +
									escapeHtml(line) +
									"</li>"
								);
							})
							.join("")
					);
				}
				$btn
					.prop("disabled", !canAdd)
					.attr("data-lt-next", String(lt.next_n || 1))
					.attr("data-lt-hint", lt.hint || "")
					.attr("data-record-id", rid);
				if (canAdd) $btn.removeAttr("data-lt-locked");
				else $btn.attr("data-lt-locked", "1");
				$btn.find("span").text(canAdd ? "Ghi cuộc gọi" : "Đã đủ gọi");
			})
			.catch(function () {
				$hint.text("Không tải được Last Touch Call.");
			});
	}

	function ensureEditLastTouchModal() {
		var existing = document.getElementById("mk-sc-edit-lt-modal");
		if (existing) return existing;
		var wrap = document.createElement("div");
		wrap.id = "mk-sc-edit-lt-modal";
		wrap.className = "mk-lead-lt-modal mk-leads-lt-modal mk-sc-lt-modal";
		wrap.hidden = true;
		wrap.innerHTML =
			'<div class="mk-lead-lt-modal__backdrop" data-mk-sc-edit-lt-close="1"></div>' +
			'<div class="mk-lead-lt-modal__dialog mk-sc-lt-modal__dialog" role="dialog" aria-modal="true">' +
			'<div class="mk-lead-lt-modal__head"><h3>Ghi Last Touch — Call</h3>' +
			'<button type="button" class="mk-lead-lt-modal__x" data-mk-sc-edit-lt-close="1">&times;</button></div>' +
			'<div class="mk-lead-lt-modal__body">' +
			'<p class="mk-lead-lt-modal__meta" id="mk-sc-edit-lt-meta"></p>' +
			'<label class="mk-lead-lt-modal__label" for="mk-sc-edit-lt-result">Kết quả cuộc gọi</label>' +
			'<select id="mk-sc-edit-lt-result" class="mk-lead-lt-modal__select" autocomplete="off">' +
			'<option value="Không nghe máy">Không nghe máy</option>' +
			'<option value="Nghe máy">Nghe máy</option></select>' +
			'<label class="mk-lead-lt-modal__label" for="mk-sc-edit-lt-note">Ghi chú</label>' +
			'<textarea id="mk-sc-edit-lt-note" class="mk-lead-lt-modal__note inputElement" rows="6" placeholder="Ví dụ: Khách quan tâm mặt bằng"></textarea>' +
			'<p class="mk-lead-lt-modal__tip">Chọn <strong>Nghe máy</strong> → Liên hệ = Đã gửi tư vấn (không sang Opp). Ghi chú Call #N hiện ở Tương tác lần N. <strong>Không nghe máy</strong> → nhắc sau khoảng 5 giờ.</p>' +
			"</div>" +
			'<div class="mk-lead-lt-modal__foot">' +
			'<button type="button" class="btn btn-default" data-mk-sc-edit-lt-close="1">Hủy</button>' +
			'<button type="button" class="btn btn-success" id="mk-sc-edit-lt-save">Lưu cuộc gọi</button>' +
			"</div></div>";
		document.body.appendChild(wrap);
		wrap.addEventListener("click", function (e) {
			if (e.target && e.target.getAttribute && e.target.getAttribute("data-mk-sc-edit-lt-close") === "1") {
				wrap.hidden = true;
			}
		});
		$("#mk-sc-edit-lt-save").on("click", function () {
			var rid = wrap._mkScId;
			var result = String($("#mk-sc-edit-lt-result").val() || "").trim();
			var note = String($("#mk-sc-edit-lt-note").val() || "").trim();
			if (!rid || !result) return;
			apiRequest("last_touch_call_log", {
				record: rid,
				id: rid,
				call_result: result,
				note: note,
			})
				.then(function () {
					wrap.hidden = true;
					refreshLastTouchPanel(rid);
					// Reload form fields so Liên hệ + Tương tác lần N cập nhật
					apiRequest("get_franchise", { record: rid, id: rid }).then(function (res) {
						if (res && res.contract) {
							fillForm($.extend({}, res.contract, { picklists: res.picklists }));
						}
					});
					if (window.app && app.helper && app.helper.showSuccessNotification) {
						app.helper.showSuccessNotification({ message: "Đã ghi Last Touch Call." });
					}
				})
				.catch(function (err) {
					var msg = (err && err.message) || "Không ghi được cuộc gọi.";
					if (window.app && app.helper && app.helper.showErrorNotification) {
						app.helper.showErrorNotification({ message: String(msg) });
					} else {
						window.alert(String(msg));
					}
				});
		});
		return wrap;
	}

	function fillForm(data) {
		if (!data) return;
		$("#mkScRecordId").val(data.id || data.crmid || "");
		$("#mkScCreateWorkspace").attr("data-record-id", data.id || data.crmid || "");
		$("#mkScFullName").val(data.full_name || "");
		var phoneVal = data.phone || "";
		if (window.MkPhoneFormat && typeof window.MkPhoneFormat.format === "function") {
			phoneVal = window.MkPhoneFormat.format(phoneVal) || phoneVal;
		}
		$("#mkScPhone").val(phoneVal);
		$("#mkScEmail").val(data.email || "");
		$("#mkScReceivedDate").val(data.received_date || "");
		$("#mkScBusinessNote").val(data.business_note || "");
		$("#mkScReferrer").val(data.referrer || "");

		fillReferrerAffSelect(data.referral_code || "");
		$("#mkScReferralCode").val(String(data.referral_code || "").toUpperCase());
		$("#mkScReferralRewardAmount").val(
			data.referral_reward_amount != null ? String(data.referral_reward_amount) : ""
		);
		$("#mkScReferralReward").val(formatMoneyVnd(data.referral_reward_amount));
		fillOwnTierSelect(data.affiliate_tier_prefix || "D");
		$("#mkScRegistrationDate").val(data.registration_date || data.received_date || "");
		$("#mkScDuplicateResultValue").val(data.duplicate_check_result || "");
		$("#mkScRetentionExpires").val(data.retention_expires_at || "");
		$("#mkScContractSigned").val(data.contract_signed_date || "");
		$("#mkScStoreCount").val(data.store_count != null ? data.store_count : "");
		$("#mkScPaymentDate").val(data.payment_date || "");

		applyPicklists(data.picklists || DEFAULT_PICKLISTS, data);
		fillUserSelect(data.sale_owner_id || "", data.sale_owner || "");
		setAffiliateBadge(data.affiliate_code || "");

		if (!$("#mkScRegistrationDate").val()) ensureRegistrationDate();
		if (!$("#mkScRetentionExpires").val()) recomputeRetentionExpiry();

		if (data.referral_code) {
			resolveReferralCode();
		}
	}

	function collectPayload() {
		var phoneRaw = String($("#mkScPhone").val() || "").trim();
		var phone =
			window.MkPhoneFormat && typeof window.MkPhoneFormat.digitsOnly === "function"
				? window.MkPhoneFormat.digitsOnly(phoneRaw)
				: phoneRaw.replace(/\D+/g, "");
		phone = String(phone || "").slice(0, 10);
		syncSaleOwnerLabel();
		ensureRegistrationDate();
		recomputeRetentionExpiry();
		var affCode = String($("#mkScReferrerAff").val() || $("#mkScReferralCode").val() || "")
			.trim()
			.toUpperCase();
		$("#mkScReferralCode").val(affCode);
		var $affOpt = $("#mkScReferrerAff option:selected");
		var referrerName =
			String($("#mkScReferrer").val() || "").trim() ||
			String($affOpt.attr("data-name") || "").trim() ||
			affCode;
		$("#mkScReferrer").val(referrerName);
		return {
			id: recordId(),
			full_name: String($("#mkScFullName").val() || "").trim(),
			phone: phone,
			email: String($("#mkScEmail").val() || "").trim(),
			received_date: String($("#mkScReceivedDate").val() || "").trim(),
			business_note: String($("#mkScBusinessNote").val() || "").trim(),
			franchise_status: String($("#mkScFranchiseStatus").val() || "").trim(),
			data_source: String($("#mkScDataSource").val() || "").trim(),
			referrer: referrerName,
			contact_status: String($("#mkScContactStatus").val() || "").trim(),
			referral_code: affCode,
			affiliate_tier_prefix: String($("#mkScOwnTier").val() || "D").trim().toUpperCase(),
			registration_date: String($("#mkScRegistrationDate").val() || "").trim(),
			sale_owner: String($("#mkScSaleOwnerLabel").val() || "").trim(),
			sale_owner_id: String($("#mkScSaleOwner").val() || "").trim(),
			contract_signed_date: String($("#mkScContractSigned").val() || "").trim(),
			store_count: String($("#mkScStoreCount").val() || "").trim(),
			payment_condition: String($("#mkScPaymentCondition").val() || "").trim() || "Chuyển khoản",
			payment_date: String($("#mkScPaymentDate").val() || "").trim(),
		};
	}

	function apiRequest(mode, extra) {
		var params = $.extend(
			{ module: "ServiceContracts", action: "ModernApi", mode: mode },
			extra || {}
		);
		return new Promise(function (resolve, reject) {
			if (window.app && app.request && app.request.post) {
				app.request.post({ data: params }).then(function (err, res) {
					if (err) {
						reject(typeof err === "string" ? new Error(err) : err);
						return;
					}
					if (!res || res.success === false) {
						reject(new Error((res && (res.error || res.message)) || "API failed"));
						return;
					}
					resolve(res);
				});
				return;
			}
			reject(new Error("app.request unavailable"));
		});
	}

	function setSaving(on) {
		$("#mkScSaveTop").prop("disabled", !!on);
		$("#mkScFranchiseForm").toggleClass("is-saving", !!on);
	}

	function saveForm() {
		showError("");
		var payload = collectPayload();
		if (!payload.full_name || !payload.phone) {
			showError("Vui lòng nhập Họ tên và SĐT.");
			if (!payload.full_name) $("#mkScFullName").focus();
			else $("#mkScPhone").focus();
			return;
		}
		if (String(payload.phone).replace(/\D+/g, "").length > 10) {
			showError("SĐT chỉ được tối đa 10 số.");
			$("#mkScPhone").focus();
			return;
		}

		setSaving(true);
		runDuplicateCheck()
			.then(function (dup) {
				if (dup && dup.in_retention) {
					setSaving(false);
					showDupBanner(dup);
					$("#mkScPhone").focus();
					return null;
				}
				payload = collectPayload();
				return apiRequest("save", { payload: JSON.stringify(payload), record: payload.id || "" });
			})
			.then(function (res) {
				if (!res) return;
				var c = (res && res.contract) || {};
				var id = c.id || c.crmid || payload.id;
				if (id) {
					window.location.href = detailUrl(id);
					return;
				}
				window.location.href = listUrl();
			})
			.catch(function (err) {
				setSaving(false);
				showError((err && err.message) || "Không lưu được.");
			});
	}

	function loadMetaThen(done) {
		apiRequest("meta", { record: recordId() || "" })
			.then(function (res) {
				if (res && Array.isArray(res.assignable_users)) {
					assignableUsers = res.assignable_users;
				}
				if (res && Array.isArray(res.referrers)) {
					referrerOptions = res.referrers;
				}
				if (res && Array.isArray(res.affiliate_tiers)) {
					affiliateTiers = res.affiliate_tiers;
				}
				if (res && res.picklists) applyPicklists(res.picklists, {});
				else applyPicklists(DEFAULT_PICKLISTS, {});
				fillUserSelect("", "");
				fillReferrerAffSelect("");
				fillOwnTierSelect("D");
				if (typeof done === "function") done(res);
			})
			.catch(function () {
				applyPicklists(DEFAULT_PICKLISTS, {});
				fillUserSelect("", "");
				fillReferrerAffSelect("");
				fillOwnTierSelect("D");
				if (typeof done === "function") done(null);
			});
	}

	function loadForEdit() {
		var id = recordId();
		loadMetaThen(function () {
			if (!id) {
				$("#mkScPaymentCondition").val("Chuyển khoản");
				ensureRegistrationDate();
				recomputeRetentionExpiry();
				return;
			}
			setSaving(true);
			apiRequest("get", { record: id })
				.then(function (res) {
					setSaving(false);
					fillForm((res && res.contract) || {});
				})
				.catch(function (err) {
					setSaving(false);
					showError((err && err.message) || "Không tải được dữ liệu.");
				});
		});
	}

	function bindActions() {
		$("#mkScSaveTop")
			.off("click.mkScSave")
			.on("click.mkScSave", function (e) {
				e.preventDefault();
				saveForm();
			});

		$("#mkScFranchiseForm")
			.off("submit.mkScSave")
			.on("submit.mkScSave", function (e) {
				e.preventDefault();
				saveForm();
			});

		$("#mkScReferrerAff")
			.off("change.mkScRef")
			.on("change.mkScRef", function () {
				applyReferrerAffSelection();
			});

		$("#mkScOwnTier")
			.off("change.mkScOwnTier")
			.on("change.mkScOwnTier", syncOwnTierReward);

		$("#mkScRegistrationDate, #mkScReceivedDate")
			.off("change.mkScReg")
			.on("change.mkScReg", function () {
				recomputeRetentionExpiry();
				if (String($("#mkScReferralCode").val() || "").trim()) {
					resolveReferralCode();
				}
			});

		$("#mkScPhone")
			.off("input.mkScPhone blur.mkScDup")
			.on("input.mkScPhone", function () {
				var raw = String(this.value || "");
				var digits =
					window.MkPhoneFormat && typeof window.MkPhoneFormat.digitsOnly === "function"
						? window.MkPhoneFormat.digitsOnly(raw)
						: raw.replace(/\D+/g, "");
				digits = digits.slice(0, 10);
				var next =
					window.MkPhoneFormat && typeof window.MkPhoneFormat.formatInput === "function"
						? window.MkPhoneFormat.formatInput(digits)
						: digits;
				if (next !== raw) {
					this.value = next;
				}
			})
			.on("blur.mkScDup", function () {
				runDuplicateCheck();
			});

		$("#mkScSaleOwner")
			.off("change.mkScSale")
			.on("change.mkScSale", syncSaleOwnerLabel);

		$(document)
			.off("keydown.mkScCreate")
			.on("keydown.mkScCreate", function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") {
					if (!$(e.target).closest("#mkScFranchiseForm").length) return;
					e.preventDefault();
					saveForm();
				}
			});
	}

	function hideLegacyChrome() {
		$("#mkScFormHost").find("#modnavigator, .editViewModNavigator, .editViewHeader").addClass("mk-sc-hide-legacy");
	}

	function boot() {
		if (!isScoped()) return;
		window.__MK_SC_FRANCHISE_BUILD__ = MK_BUILD;
		hideLegacyChrome();
		bindActions();
		loadForEdit();
	}

	$(boot);
	$(document).on("pjax:complete ready", boot);
})(window.jQuery);
