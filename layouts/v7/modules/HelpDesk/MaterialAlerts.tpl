{strip}
<div class="mk-tre-page mk-nl-page" lang="vi">
	<header class="mk-tre-hero">
		<div class="mk-tre-hero__copy">
			<p class="mk-tre-eyebrow">Tag Rule Engine · Hỗ trợ</p>
			<h1 class="mk-tre-title">Quản lý</h1>
		</div>
	</header>
	<div class="mk-tre-toolbar-bar">
		<div class="mk-tre-tabs" role="tablist">
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT#tab=rules">Rule</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT#tab=tags">Tag</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT#tab=scenarios">Kịch bản</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT#tab=affiliate">Mã giới thiệu</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT#tab=questions">Câu hỏi</a>
			<a class="mk-tre-tab is-active" href="index.php?module=HelpDesk&amp;view=MaterialAlerts&amp;app=SUPPORT" aria-current="page">Cảnh báo nguyên liệu</a>
		</div>
	</div>

	<p class="mk-nl__sub">Ngưỡng để trống thì cảnh báo định lượng không chạy. Thiếu đơn hợp lệ thì hồ sơ ghi chưa đủ dữ liệu.</p>

	<form class="mk-nl__card" id="mk-nl-settings">
		<div class="mk-nl__card-head">
			<h2>Ngưỡng</h2>
			<button type="submit">Lưu ngưỡng</button>
		</div>
		<div class="mk-nl__grid">
			{foreach from=$NL_FIELDS item=FIELD}
				<label class="mk-nl__field">
					<span>{$FIELD.label|escape}</span>
					<small>{$FIELD.hint|escape}</small>
					<input type="text" name="{$FIELD.key|escape}" value="{$NL_SETTINGS[$FIELD.key]|escape}" placeholder="Để trống" />
				</label>
			{/foreach}
		</div>
		<p class="mk-nl__msg" data-nl-msg hidden></p>
	</form>

	<form class="mk-nl__card" id="mk-nl-metrics">
		<div class="mk-nl__card-head">
			<h2>Chỉ số một khách</h2>
			<button type="submit">Xem CT01–CT09</button>
		</div>
		<label class="mk-nl__field mk-nl__field--id">Mã khách hàng
			<input type="number" name="contact_id" min="1" value="{if $NL_CONTACT_ID}{$NL_CONTACT_ID|escape}{/if}" placeholder="Nhập mã khách" />
		</label>
		{if $NL_METRICS}
			<ul class="mk-nl__metrics">
				<li>CT01 Tổng đã mua <strong>{$NL_METRICS.ct01|escape}</strong></li>
				<li>CT02 Mua 90 ngày <strong>{$NL_METRICS.ct02|escape}</strong></li>
				<li>CT03 Số đơn <strong>{$NL_METRICS.ct03|escape}</strong></li>
				<li>CT04 Đơn trung bình <strong>{$NL_METRICS.ct04|escape}</strong></li>
				<li>CT05 Ngày mua gần nhất <strong>{$NL_METRICS.ct05|escape}</strong></li>
				<li>CT07 Khoảng cách mua <strong>{$NL_METRICS.ct07|escape}</strong></li>
				<li>CT08 Chờ giao / chưa xong <strong>{$NL_METRICS.ct08|escape}</strong></li>
				<li>CT09 Vòng đời <strong>{$NL_METRICS.ct09_life|escape}</strong></li>
				<li>CT09 Hạng <strong>{$NL_METRICS.ct09_tier|escape}</strong></li>
			</ul>
		{/if}
	</form>

	<section class="mk-nl__card">
		<h2>Việc đang mở</h2>
		{if $NL_ALERTS|@count eq 0}
			<p class="mk-nl__empty">Chưa có việc. Báo giá quá hạn và đơn quá hạn thanh toán sẽ hiện khi có dữ liệu. Kỳ mua lại chỉ hiện sau khi nhập ngưỡng.</p>
		{/if}
		{foreach from=$NL_ALERTS item=ALERT}
			<article class="mk-nl__alert" data-id="{$ALERT.id}">
				<header>
					<strong>{$ALERT.code|escape}</strong>
					<span>{$ALERT.name|escape}</span>
					<em>{$ALERT.status|escape}</em>
				</header>
				<p>{$ALERT.title|escape}</p>
				<small>{$ALERT.detail|escape}</small>
				<div class="mk-nl__actions">
					<button type="button" data-next="accepted">Đã nhận</button>
					<button type="button" data-next="working">Đang xử lý</button>
					<button type="button" data-next="done">Hoàn thành</button>
					<button type="button" data-next="snoozed">Tạm hoãn</button>
					<button type="button" data-next="na">Không áp dụng</button>
				</div>
				<div class="mk-nl__close" hidden>
					<input type="text" data-field="result_note" placeholder="Kết quả / lý do" />
					<input type="text" data-field="evidence_ref" placeholder="Bằng chứng: đơn, cuộc gọi, chứng từ" />
					<input type="date" data-field="snooze_until" />
					<button type="button" data-send="1">Ghi</button>
				</div>
			</article>
		{/foreach}
	</section>
</div>
<style>
.mk-nl-page { width: 100%; max-width: none; box-sizing: border-box; }
.mk-nl__sub { margin: 8px 0 18px; color: #64748b; font-size: 15px; }
.mk-nl__card { background: #fff; border: 1px solid #dde4ec; border-radius: 16px; padding: 18px 20px 20px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
.mk-nl__card h2 { margin: 0; font-size: 18px; }
.mk-nl__card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
.mk-nl__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.mk-nl__field { display: flex; flex-direction: column; gap: 4px; min-width: 0; font-size: 14px; font-weight: 650; color: #0f172a; }
.mk-nl__field small { font-weight: 700; color: #08a045; letter-spacing: .04em; }
.mk-nl__field--id { max-width: 280px; }
.mk-nl input { width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-weight: 500; background: #fff; }
.mk-nl__card-head button, .mk-nl__close button, .mk-nl__actions button { border: 0; border-radius: 10px; padding: 8px 14px; font-weight: 700; cursor: pointer; }
.mk-nl__card-head button, .mk-nl__close button { background: #08a045; color: #fff; }
.mk-nl__actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.mk-nl__actions button { background: #f1f5f9; color: #0f172a; }
.mk-nl__alert { border-top: 1px solid #e8edf3; padding: 14px 0; }
.mk-nl__alert header { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.mk-nl__alert em { font-style: normal; background: #ecfdf3; color: #0b6e4f; border-radius: 999px; padding: 2px 8px; font-size: 12px; font-weight: 700; }
.mk-nl__metrics { margin: 14px 0 0; padding: 0; list-style: none; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.mk-nl__metrics li { background: #f8fafc; border-radius: 10px; padding: 10px 12px; }
.mk-nl__empty { color: #64748b; }
.mk-nl__msg { margin: 10px 0 0; color: #0b6e4f; font-weight: 700; }
.mk-nl__close { display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; margin-top: 10px; align-items: center; }
@media (max-width: 800px) {
	.mk-nl__grid, .mk-nl__metrics, .mk-nl__close { grid-template-columns: 1fr; }
}
</style>
<script>
(function () {
	function post(data) {
		return app.request.post({ data: data });
	}
	var settings = document.getElementById("mk-nl-settings");
	if (settings) {
		settings.addEventListener("submit", function (e) {
			e.preventDefault();
			var payload = {};
			settings.querySelectorAll("input[name]").forEach(function (input) {
				payload[input.name] = input.value;
			});
			post({
				module: "HelpDesk",
				action: "MaterialAlertsApi",
				mode: "save_settings",
				payload: JSON.stringify(payload)
			}).then(function (err) {
				var msg = settings.querySelector("[data-nl-msg]");
				msg.hidden = false;
				msg.textContent = err ? "Không lưu được ngưỡng." : "Đã lưu ngưỡng.";
			});
		});
	}
	var metrics = document.getElementById("mk-nl-metrics");
	if (metrics) {
		metrics.addEventListener("submit", function (e) {
			e.preventDefault();
			var id = metrics.querySelector("[name=contact_id]").value;
			window.location.href = "index.php?module=HelpDesk&view=MaterialAlerts&app=SUPPORT&contact_id=" + encodeURIComponent(id);
		});
	}
	document.querySelectorAll(".mk-nl__alert").forEach(function (card) {
		var next = "";
		card.querySelectorAll("[data-next]").forEach(function (btn) {
			btn.addEventListener("click", function () {
				next = btn.getAttribute("data-next");
				if (next === "accepted" || next === "working") {
					post({
						module: "HelpDesk", action: "MaterialAlertsApi", mode: "transition",
						id: card.getAttribute("data-id"), next: next
					}).then(function () { window.location.reload(); });
					return;
				}
				card.querySelector(".mk-nl__close").hidden = false;
			});
		});
		var send = card.querySelector("[data-send]");
		if (!send) return;
		send.addEventListener("click", function () {
			var data = {
				module: "HelpDesk", action: "MaterialAlertsApi", mode: "transition",
				id: card.getAttribute("data-id"), next: next
			};
			card.querySelectorAll("[data-field]").forEach(function (input) {
				data[input.getAttribute("data-field")] = input.value;
			});
			post(data).then(function (err, res) {
				if (err || (res && res.error)) {
					window.alert((err && err.message) || "Chưa đủ điều kiện để tắt việc.");
					return;
				}
				window.location.reload();
			});
		});
	});
})();
</script>
{/strip}
