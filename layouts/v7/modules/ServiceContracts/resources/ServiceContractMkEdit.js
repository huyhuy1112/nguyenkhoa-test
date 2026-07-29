/**
 * ServiceContracts Create/Edit — franchise form (SALES only).
 * Kiểm tra trùng: SĐT + ngày đăng ký / bảo lưu (180 ngày theo Rule).
 */
(function ($) {
	"use strict";

	var MK_BUILD = "20260729_sc_franchise_ux4";
	var DEFAULT_PICKLISTS = {
		franchise_status: [
			"Quan Tâm/Tham Khảo",
			"Không đủ tài chính",
			"Đã Kí Quỹ",
			"Đang chăm sóc",
			"Chuyển sang Nguyên Khoa",
		],
		data_source: ["Facebook", "TikTok", "Website", "Zalo", "Khác"],
		contact_status: ["Chưa gọi", "Đã gửi tư vấn", "Thuê bao", "Ko nghe Máy Lần 1"],
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
		if (!$el.length) {
			window.alert(msg);
			return;
		}
		$el.text(msg || "").prop("hidden", !msg);
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

	function dupAlertMessage(dup) {
		var result = (dup && dup.result) || "";
		var m = (dup && dup.match) || {};
		if (dup && dup.in_retention) {
			return (
				"Trùng còn hiệu lực.\n\n" +
				"Người giới thiệu hiện tại: " +
				(m.referral_code || m.referrer || "—") +
				"\nSale phụ trách: " +
				(m.sale_owner || m.full_name || "—") +
				"\nNgày đăng ký: " +
				(m.registration_date || "—") +
				"\nHết hạn bảo lưu: " +
				(m.retention_expires_at || "—") +
				"\n\nKhông được nhận quyền giới thiệu mới."
			);
		}
		if (result.indexOf("hết hạn") >= 0) {
			return (
				"Trùng nhưng đã hết hạn bảo lưu.\n\nCó thể đăng ký lại và gán người giới thiệu mới."
			);
		}
		return "";
	}

	function runDuplicateCheck() {
		var phoneRaw = String($("#mkScPhone").val() || "").trim();
		var phone =
			window.MkPhoneFormat && typeof window.MkPhoneFormat.digitsOnly === "function"
				? window.MkPhoneFormat.digitsOnly(phoneRaw)
				: phoneRaw.replace(/\D+/g, "");
		if (!phone || phone.length < 8) {
			dupState = { result: "", in_retention: false, match: null };
			$("#mkScDuplicateResultValue").val("");
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
				if (dup.in_retention) {
					showError(dupAlertMessage(dup).replace(/\n/g, " — "));
				} else {
					showError("");
					if ((dup.result || "").indexOf("hết hạn") >= 0) {
						$("#mkScRegistrationDate").val(todayYmd());
						recomputeRetentionExpiry();
					}
				}
				return dup;
			})
			.catch(function () {
				return null;
			});
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
		$("#mkScReceivedDate").val(data.received_date || "");
		$("#mkScBusinessNote").val(data.business_note || "");
		$("#mkScReferrer").val(data.referrer || "");
		$("#mkScInteraction1").val(data.interaction_1 || "");
		$("#mkScInteraction2").val(data.interaction_2 || "");
		$("#mkScInteraction3").val(data.interaction_3 || "");
		$("#mkScInteractionMaterials").val(data.interaction_materials || "");

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
			received_date: String($("#mkScReceivedDate").val() || "").trim(),
			business_note: String($("#mkScBusinessNote").val() || "").trim(),
			franchise_status: String($("#mkScFranchiseStatus").val() || "").trim(),
			data_source: String($("#mkScDataSource").val() || "").trim(),
			referrer: referrerName,
			contact_status: String($("#mkScContactStatus").val() || "").trim(),
			interaction_1: String($("#mkScInteraction1").val() || "").trim(),
			interaction_2: String($("#mkScInteraction2").val() || "").trim(),
			interaction_3: String($("#mkScInteraction3").val() || "").trim(),
			interaction_materials: String($("#mkScInteractionMaterials").val() || "").trim(),
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
		$("#mkScSaveTop, #mkScSaveBottom").prop("disabled", !!on);
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

		setSaving(true);
		runDuplicateCheck()
			.then(function (dup) {
				if (dup && dup.in_retention) {
					setSaving(false);
					window.alert(dupAlertMessage(dup));
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
			.off("blur.mkScDup")
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
