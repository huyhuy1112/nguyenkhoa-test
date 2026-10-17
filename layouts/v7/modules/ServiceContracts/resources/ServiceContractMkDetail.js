/**
 * ServiceContracts Detail — franchise fields (SALES).
 */
(function ($) {
	"use strict";

	function isScoped() {
		var b = document.body;
		return !!(
			b &&
			b.getAttribute("data-module") === "ServiceContracts" &&
			b.getAttribute("data-view") === "Detail" &&
			b.getAttribute("data-app") === "SALES"
		);
	}

	function escapeHtml(s) {
		return String(s || "")
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;");
	}

	function formatMoneyVnd(n) {
		if (n === null || n === undefined || n === "") return "—";
		var num = Math.round(Number(n) || 0);
		return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + " ₫";
	}

	function hasValue(value) {
		if (value == null) return false;
		var s = String(value).trim();
		return s !== "" && s !== "—" && s !== "-" && s !== "— Đang ẩn / chưa bật";
	}

	function field(label, value) {
		if (!hasValue(value)) return "";
		return (
			'<div class="mk-sc-mk-detail__field">' +
			'<span class="mk-sc-mk-detail__label">' +
			escapeHtml(label) +
			"</span>" +
			'<strong class="mk-sc-mk-detail__value">' +
			escapeHtml(String(value)) +
			"</strong></div>"
		);
	}

	function panel(title, rows) {
		var html = rows
			.map(function (r) {
				return field(r[0], r[1]);
			})
			.join("");
		if (!html) return "";
		return (
			'<section class="mk-sc-mk-detail__panel">' +
			"<h3>" +
			escapeHtml(title) +
			"</h3>" +
			'<div class="mk-sc-mk-detail__grid">' +
			html +
			"</div></section>"
		);
	}

	function apiGet(recordId) {
		return new Promise(function (resolve, reject) {
			if (!window.app || !app.request || !app.request.post) {
				reject(new Error("app.request unavailable"));
				return;
			}
			app.request
				.post({
					data: {
						module: "ServiceContracts",
						action: "ModernApi",
						mode: "get",
						record: recordId,
					},
				})
				.then(function (err, res) {
					if (err || !res || res.success === false) {
						reject(new Error((res && (res.error || res.message)) || String(err || "fail")));
						return;
					}
					resolve(res.contract || {});
				});
		});
	}

	function render(data) {
		var $root = $("#mkScMkDetail");
		if (!$root.length) return;
		$root.prop("hidden", false);

		var aff = data.affiliate_code || "";
		var affVisible = Number(data.affiliate_visible) !== 0 && !!aff;
		var recordId = String($root.attr("data-record") || data.id || data.crmid || "").trim();
		var $aff = $("#mkScMkAff");
		var $toggle = $("#mkScMkAffVisible");
		$aff.prop("hidden", !affVisible).text(affVisible ? aff : "");
		$toggle.prop("checked", affVisible).prop("disabled", !recordId);

		var tier = [data.affiliate_tier_prefix || "", data.affiliate_tier_name || ""]
			.filter(function (part) {
				return String(part || "").trim() !== "";
			})
			.join(" — ");
		$("#mkScMkIdentity").html(
			'<div class="mk-sc-mk-detail__who">' +
				"<strong>" +
				escapeHtml(data.full_name || "Khách nhượng quyền") +
				"</strong>" +
				(hasValue(data.phone) ? "<span>" + escapeHtml(data.phone) + "</span>" : "") +
			"</div>" +
			'<div class="mk-sc-mk-detail__pills">' +
				(hasValue(data.franchise_status)
					? '<span class="mk-sc-mk-detail__pill">' + escapeHtml(data.franchise_status) + "</span>"
					: "") +
				(hasValue(data.data_source)
					? '<span class="mk-sc-mk-detail__pill mk-sc-mk-detail__pill--source">' +
						escapeHtml(data.data_source) +
						"</span>"
					: "") +
				(hasValue(data.contact_status)
					? '<span class="mk-sc-mk-detail__pill mk-sc-mk-detail__pill--care">' +
						escapeHtml(data.contact_status) +
						"</span>"
					: "") +
			"</div>"
		);
		$("#mkScMkFields").html(
			panel("Hồ sơ", [
				["Ngày tiếp nhận", data.received_date],
				["Địa chỉ / ghi chú", data.business_note],
				["Người giới thiệu", data.referrer],
				["Ngày đăng ký", data.registration_date],
				["Ngày hết hạn bảo lưu", data.retention_expires_at],
				["Ngày ký hợp đồng", data.contract_signed_date],
			]) +
			panel("Giới thiệu và thanh toán", [
				["Mã giới thiệu của khách", affVisible ? aff : ""],
				["Hạng mã", tier],
				["Tiền thưởng khi dùng mã này", formatMoneyVnd(data.affiliate_reward_amount)],
				["Mã người đã giới thiệu", data.referral_code],
				["Tiền thưởng phải trả", formatMoneyVnd(data.referral_reward_amount)],
				["Sale phụ trách", data.sale_owner],
				["Số cửa hàng", data.store_count != null && data.store_count !== "" ? data.store_count : ""],
				["Điều kiện thanh toán", data.payment_condition],
				["Ngày đã thanh toán", data.payment_date],
			])
		);

		var lt = data.lastTouchCalls || {};
		var calls = lt.calls || [];
		var callLogHtml = calls.length
			? calls
					.map(function (call) {
						var line =
							call.label ||
							(call.called_at_label || "") +
								" Call #" +
								(call.n || "") +
								" Kết quả: " +
								(call.result || "");
						if (call.note && String(line).indexOf("Ghi chú:") < 0) {
							line += " Ghi chú: " + call.note;
						}
						return (
							'<div class="mk-sc-mk-detail__ix-body mk-sc-mk-detail__call-line">' +
							escapeHtml(line) +
							"</div>"
						);
					})
					.join("")
			: '<div class="mk-sc-mk-detail__ix-body">Chưa có cuộc gọi</div>';
		var materials = String(data.interaction_materials || "").trim();
		var materialsHtml = materials
			? '<div class="mk-sc-mk-detail__ix">' +
				'<span class="mk-sc-mk-detail__label">Tương tác nguyên liệu / máy móc</span>' +
				'<div class="mk-sc-mk-detail__ix-body">' +
				escapeHtml(materials) +
				"</div></div>"
			: "";
		$("#mkScMkInteractions").html(
			"<h3>Tương tác gần đây</h3>" +
				'<div class="mk-sc-mk-detail__ix">' +
				'<span class="mk-sc-mk-detail__label">Cuộc gọi</span>' +
				callLogHtml +
				"</div>" +
				materialsHtml
		);

		function hideStock() {
			$(".mk-sc-summary-grid, .detailViewInfo .block, .summaryView, .summaryWidgetContainer, .details .block")
				.addClass("mk-sc-mk-detail-hide-stock");
			$(".details .blockData").closest(".block").addClass("mk-sc-mk-detail-hide-stock");
		}
		hideStock();
		window.setTimeout(hideStock, 400);
		window.setTimeout(hideStock, 1200);

		$toggle.off("change.mkScAff").on("change.mkScAff", function (e) {
			e.preventDefault();
			if (!recordId || $toggle.prop("disabled")) return;
			var visible = !!$toggle.prop("checked");
			$toggle.prop("disabled", true);
			if (!window.app || !app.request || !app.request.post) {
				$toggle.prop("disabled", false).prop("checked", !visible);
				return;
			}
			app.request
				.post({
					data: {
						module: "ServiceContracts",
						action: "ModernApi",
						mode: "set_affiliate_visible",
						record: recordId,
						visible: visible ? 1 : 0,
					},
				})
				.then(function (err, res) {
					if (err || !res || res.success === false) {
						$toggle.prop("disabled", false).prop("checked", !visible);
						window.alert(
							(res && (res.error || res.message)) ||
								(err && err.message) ||
								"Không cập nhật được AFF."
						);
						return;
					}
					apiGet(recordId).then(render).catch(function () {
						render(
							(res.contract) || {
								affiliate_code: res.affiliate_code || aff,
								affiliate_visible: res.affiliate_visible,
							}
						);
					});
				});
		});
	}

	function boot() {
		if (!isScoped()) return;
		var $host = $("#mkScMkDetail");
		if (!$host.length) return;
		var id = String($host.attr("data-record") || $("#recordId").val() || "").trim();
		if (!id) return;
		apiGet(id)
			.then(render)
			.catch(function () {
				/* keep stock detail if franchise payload fails */
			});
	}

	$(boot);
	$(document).on("pjax:complete ready post.ProcessRelatedListContent", boot);
})(window.jQuery);
