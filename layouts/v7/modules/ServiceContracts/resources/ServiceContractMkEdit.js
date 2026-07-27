/**
 * ServiceContracts Create/Edit — franchise spreadsheet form (SALES).
 */
(function ($) {
	"use strict";

	var MK_BUILD = "20260727_sc_franchise1";
	var DEFAULT_PICKLISTS = {
		franchise_status: [
			"Quan Tâm/Tham Khảo",
			"Không đủ tài chính",
			"Đã Kí Quỹ",
			"Đang chăm sóc",
			"Chuyển sang Nguyên Khoa",
		],
		fanpage: [
			"FB Nhượng quyền TaiRao",
			"Hotline",
			"Nguyên Khoa F&B",
			"Chủ quán giới thiệu",
		],
		data_source: ["Ads Nhượng Quyền", "Lớp Học Miễn Phí", "FCTH"],
		contact_status: ["Chưa gọi", "Đã gửi tư vấn", "Thuê bao", "Ko nghe Máy Lần 1"],
	};

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

	function showError(msg) {
		var $el = $("#mkScFormError");
		if (!$el.length) {
			window.alert(msg);
			return;
		}
		$el.text(msg || "").prop("hidden", !msg);
	}

	function fillSelect($sel, options, selected) {
		var html = '<option value="">—</option>';
		(options || []).forEach(function (opt) {
			var v = String(opt || "");
			html +=
				'<option value="' +
				escapeAttr(v) +
				'"' +
				(v === selected ? " selected" : "") +
				">" +
				escapeHtml(v) +
				"</option>";
		});
		$sel.html(html);
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

	function applyPicklists(picklists) {
		var p = picklists || DEFAULT_PICKLISTS;
		fillSelect($("#mkScFranchiseStatus"), p.franchise_status || DEFAULT_PICKLISTS.franchise_status, "");
		fillSelect($("#mkScFanpage"), p.fanpage || DEFAULT_PICKLISTS.fanpage, "");
		fillSelect($("#mkScDataSource"), p.data_source || DEFAULT_PICKLISTS.data_source, "");
		fillSelect($("#mkScContactStatus"), p.contact_status || DEFAULT_PICKLISTS.contact_status, "");
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

	function fillForm(data) {
		if (!data) return;
		$("#mkScRecordId").val(data.id || data.crmid || "");
		$("#mkScCreateWorkspace").attr("data-record-id", data.id || data.crmid || "");
		$("#mkScFullName").val(data.full_name || "");
		$("#mkScPhone").val(data.phone || "");
		$("#mkScReceivedDate").val(data.received_date || "");
		$("#mkScBusinessNote").val(data.business_note || "");
		$("#mkScReferrer").val(data.referrer || "");
		$("#mkScInteraction1").val(data.interaction_1 || "");
		$("#mkScInteraction2").val(data.interaction_2 || "");
		$("#mkScInteraction3").val(data.interaction_3 || "");
		$("#mkScInteractionMaterials").val(data.interaction_materials || "");

		var p = data.picklists || DEFAULT_PICKLISTS;
		fillSelect($("#mkScFranchiseStatus"), p.franchise_status, data.franchise_status || "");
		fillSelect($("#mkScFanpage"), p.fanpage, data.fanpage || "");
		fillSelect($("#mkScDataSource"), p.data_source, data.data_source || "");
		fillSelect($("#mkScContactStatus"), p.contact_status, data.contact_status || "");
		setAffiliateBadge(data.affiliate_code || "");
	}

	function collectPayload() {
		return {
			id: recordId(),
			full_name: String($("#mkScFullName").val() || "").trim(),
			phone: String($("#mkScPhone").val() || "").trim(),
			received_date: String($("#mkScReceivedDate").val() || "").trim(),
			business_note: String($("#mkScBusinessNote").val() || "").trim(),
			franchise_status: String($("#mkScFranchiseStatus").val() || "").trim(),
			fanpage: String($("#mkScFanpage").val() || "").trim(),
			data_source: String($("#mkScDataSource").val() || "").trim(),
			referrer: String($("#mkScReferrer").val() || "").trim(),
			contact_status: String($("#mkScContactStatus").val() || "").trim(),
			interaction_1: String($("#mkScInteraction1").val() || "").trim(),
			interaction_2: String($("#mkScInteraction2").val() || "").trim(),
			interaction_3: String($("#mkScInteraction3").val() || "").trim(),
			interaction_materials: String($("#mkScInteractionMaterials").val() || "").trim(),
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
		apiRequest("save", { payload: JSON.stringify(payload), record: payload.id || "" })
			.then(function (res) {
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

	function loadForEdit() {
		var id = recordId();
		applyPicklists(DEFAULT_PICKLISTS);
		if (!id) {
			apiRequest("picklists")
				.then(function (res) {
					if (res && res.picklists) applyPicklists(res.picklists);
				})
				.catch(function () {});
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
				applyPicklists(DEFAULT_PICKLISTS);
				showError((err && err.message) || "Không tải được dữ liệu.");
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
		// Stock EditView is no longer embedded; keep any leftover chrome hidden.
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
