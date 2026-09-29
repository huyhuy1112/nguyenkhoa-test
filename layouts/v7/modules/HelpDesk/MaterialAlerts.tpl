{strip}
<div class="mk-nl">
	<h1 class="mk-nl__title">Cảnh báo nguyên liệu</h1>
	<p class="mk-nl__sub">Ngưỡng để trống thì cảnh báo định lượng không chạy. Thiếu đơn hợp lệ thì hồ sơ ghi chưa đủ dữ liệu.</p>

	<form class="mk-nl__card" id="mk-nl-settings">
		<h2>Ngưỡng</h2>
		<div class="mk-nl__grid">
			{foreach from=$NL_FIELDS item=FIELD}
				<label>
					<span>{$FIELD.label|escape}</span>
					<small>{$FIELD.hint|escape}</small>
					<input type="text" name="{$FIELD.key|escape}" value="{$NL_SETTINGS[$FIELD.key]|escape}" placeholder="Để trống" />
				</label>
			{/foreach}
		</div>
		<button type="submit">Lưu ngưỡng</button>
		<p class="mk-nl__msg" data-nl-msg hidden></p>
	</form>

	<form class="mk-nl__card" id="mk-nl-metrics">
		<h2>Chỉ số một khách</h2>
		<label>Mã khách hàng
			<input type="number" name="contact_id" min="1" value="{if $NL_CONTACT_ID}{$NL_CONTACT_ID|escape}{/if}" placeholder="contact id" />
		</label>
		<button type="submit">Xem CT01–CT09</button>
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
			<p>Chưa có việc. Báo giá quá hạn và đơn quá hạn thanh toán sẽ hiện khi có dữ liệu. Kỳ mua lại chỉ hiện sau khi nhập ngưỡng.</p>
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
.mk-nl { max-width: 980px; display: flex; flex-direction: column; gap: 16px; }
.mk-nl__title { margin: 0; font-size: 22px; }
.mk-nl__sub, .mk-nl__card small { color: #64748b; }
.mk-nl__card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; }
.mk-nl__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.mk-nl__grid label, .mk-nl__card > label { display: flex; flex-direction: column; gap: 4px; font-size: 13px; font-weight: 650; }
.mk-nl input { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-weight: 500; }
.mk-nl button { margin-top: 10px; border: 0; border-radius: 8px; background: #15803d; color: #fff; padding: 8px 12px; font-weight: 700; cursor: pointer; }
.mk-nl__actions button { background: #e2e8f0; color: #0f172a; margin-right: 6px; }
.mk-nl__alert { border-top: 1px solid #e2e8f0; padding: 12px 0; }
.mk-nl__alert header { display: flex; gap: 10px; align-items: center; }
.mk-nl__metrics { margin: 12px 0 0; padding: 0; list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.mk-nl__metrics li { background: #f8fafc; border-radius: 8px; padding: 8px 10px; }
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
