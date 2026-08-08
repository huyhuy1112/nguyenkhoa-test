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

	function field(label, value) {
		var v = value == null || value === "" ? "—" : String(value);
		return (
			'<div class="mk-sc-mk-detail__field">' +
			'<span class="mk-sc-mk-detail__label">' +
			escapeHtml(label) +
			"</span>" +
			'<strong class="mk-sc-mk-detail__value">' +
			escapeHtml(v) +
			"</strong></div>"
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
		var $aff = $("#mkScMkAff");
		if (aff) $aff.prop("hidden", false).text(aff);
		else $aff.prop("hidden", true).text("");

		var rows = [
			["Ngày tiếp nhận", data.received_date],
			["Họ tên", data.full_name],
			["SĐT", data.phone],
			["Đ/c kinh doanh / Note", data.business_note],
			["Trạng thái", data.franchise_status],
			["Nguồn data", data.data_source],
			["Người giới thiệu", data.referrer],
			["Liên hệ", data.contact_status],
			["Mã AFF (mã giới thiệu của khách)", data.affiliate_code],
			[
				"Hạng mã AFF",
				(data.affiliate_tier_prefix || "") +
					(data.affiliate_tier_name ? " — " + data.affiliate_tier_name : ""),
			],
			["Tiền thưởng khi dùng mã của khách này", formatMoneyVnd(data.affiliate_reward_amount)],
			["Mã giới thiệu đã chọn (người GT)", data.referral_code],
			["Tiền thưởng phải trả", formatMoneyVnd(data.referral_reward_amount)],
			["Ngày đăng ký", data.registration_date],
			["Ngày hết hạn bảo lưu", data.retention_expires_at],
			["Sale phụ trách", data.sale_owner],
			["Ngày ký HĐ", data.contract_signed_date],
			["Số cửa hàng", data.store_count != null ? data.store_count : ""],
			["Điều kiện TT", data.payment_condition],
			["Ngày đã thanh toán", data.payment_date],
		];
		$("#mkScMkFields").html(
			rows
				.map(function (r) {
					return field(r[0], r[1]);
				})
				.join("")
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
		var materials = data.interaction_materials || "";
		$("#mkScMkInteractions").html(
			"<h3>Tương tác gần đây</h3>" +
				'<div class="mk-sc-mk-detail__ix">' +
				'<span class="mk-sc-mk-detail__label">Last Touch Call</span>' +
				callLogHtml +
				"</div>" +
				'<div class="mk-sc-mk-detail__ix is-materials">' +
				'<span class="mk-sc-mk-detail__label">TƯƠNG TÁC TỰ MỞ NGUYÊN LIỆU MÁY MÓC</span>' +
				'<div class="mk-sc-mk-detail__ix-body">' +
				escapeHtml(materials || "—") +
				"</div></div>"
		);

		function hideStock() {
			$(".detailViewInfo .block, .summaryView, .summaryWidgetContainer, .details .block")
				.addClass("mk-sc-mk-detail-hide-stock");
			$(".details .blockData").closest(".block").addClass("mk-sc-mk-detail-hide-stock");
		}
		hideStock();
		window.setTimeout(hideStock, 400);
		window.setTimeout(hideStock, 1200);
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
