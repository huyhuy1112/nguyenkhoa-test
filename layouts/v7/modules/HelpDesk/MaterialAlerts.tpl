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
			<div>
				<h2>Chỉ số một khách</h2>
				<p class="mk-nl__sub" style="margin:4px 0 0;">CT01–CT09 trên hồ sơ khách nguyên liệu. Nhập mã để xem thử.</p>
			</div>
		</div>
		<div class="mk-nl__lookup">
			<label class="mk-nl__field mk-nl__field--id">Mã khách hàng
				<input type="number" name="contact_id" min="1" value="{if $NL_CONTACT_ID}{$NL_CONTACT_ID|escape}{/if}" placeholder="Nhập mã khách" />
			</label>
			<button type="submit">Xem chỉ số</button>
		</div>
		{if $NL_METRICS}
			<div class="mk-nl-board">
				<div class="mk-nl-board__status">
					<div><em>Vòng đời</em><strong>{$NL_METRICS.ct09_life|escape}</strong></div>
					<div><em>Hạng</em><strong>{$NL_METRICS.ct09_tier|escape}</strong></div>
				</div>
				<div class="mk-nl-board__grid">
					<article class="mk-nl-tile"><small>CT01</small><span>Tổng đã mua</span><strong>{$NL_METRICS.ct01|escape}</strong></article>
					<article class="mk-nl-tile{if $NL_METRICS.ct02 eq 'chưa đủ dữ liệu'} is-empty{/if}"><small>CT02</small><span>Mua 90 ngày</span><strong>{$NL_METRICS.ct02|escape}</strong></article>
					<article class="mk-nl-tile{if $NL_METRICS.ct03 eq 'chưa đủ dữ liệu'} is-empty{/if}"><small>CT03</small><span>Số đơn</span><strong>{$NL_METRICS.ct03|escape}</strong></article>
					<article class="mk-nl-tile{if $NL_METRICS.ct04 eq 'chưa đủ dữ liệu'} is-empty{/if}"><small>CT04</small><span>Đơn trung bình</span><strong>{$NL_METRICS.ct04|escape}</strong></article>
					<article class="mk-nl-tile{if $NL_METRICS.ct05 eq 'chưa đủ dữ liệu'} is-empty{/if}"><small>CT05</small><span>Mua gần nhất</span><strong>{$NL_METRICS.ct05|escape}</strong></article>
					<article class="mk-nl-tile{if $NL_METRICS.ct07 eq 'chưa đủ dữ liệu'} is-empty{/if}"><small>CT07</small><span>Khoảng cách mua</span><strong>{$NL_METRICS.ct07|escape}</strong></article>
					<article class="mk-nl-tile"><small>CT08</small><span>Chờ giao</span><strong>{$NL_METRICS.ct08|escape}</strong></article>
				</div>
			</div>
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
					<input type="text" data-field="next_task" placeholder="Việc tiếp theo" />
					<input type="text" data-field="next_owner" placeholder="Người phụ trách" />
					<input type="date" data-field="next_due" title="Hạn việc tiếp theo" />
					<input type="date" data-field="snooze_until" title="Ngày kiểm tra lại nếu tạm hoãn" />
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
.mk-nl__card-head button, .mk-nl__close button, .mk-nl__actions button, .mk-nl__lookup button { border: 0; border-radius: 10px; padding: 8px 14px; font-weight: 700; cursor: pointer; }
.mk-nl__card-head button, .mk-nl__close button, .mk-nl__lookup button { background: #08a045; color: #fff; }
.mk-nl__actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.mk-nl__actions button { background: #f1f5f9; color: #0f172a; }
.mk-nl__alert { border-top: 1px solid #e8edf3; padding: 14px 0; }
.mk-nl__alert header { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.mk-nl__alert em { font-style: normal; background: #ecfdf3; color: #0b6e4f; border-radius: 999px; padding: 2px 8px; font-size: 12px; font-weight: 700; }
.mk-nl__lookup { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
.mk-nl__lookup button { height: 42px; }
.mk-nl-board { margin-top: 16px; }
.mk-nl-board__status { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
.mk-nl-board__status div { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 14px; }
.mk-nl-board__status em { display: block; font-style: normal; font-size: 12px; font-weight: 700; color: #166534; letter-spacing: .04em; text-transform: uppercase; }
.mk-nl-board__status strong { display: block; margin-top: 4px; font-size: 16px; color: #14532d; }
.mk-nl-board__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.mk-nl-tile { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; min-width: 0; }
.mk-nl-tile small { display: block; font-size: 11px; font-weight: 800; color: #08a045; letter-spacing: .06em; }
.mk-nl-tile span { display: block; margin-top: 4px; font-size: 12px; color: #64748b; }
.mk-nl-tile strong { display: block; margin-top: 6px; font-size: 16px; color: #0f172a; word-break: break-word; }
.mk-nl-tile.is-empty strong { font-size: 13px; font-weight: 650; color: #94a3b8; }
.mk-nl__empty { color: #64748b; }
.mk-nl__msg { margin: 10px 0 0; color: #0b6e4f; font-weight: 700; }
.mk-nl__close { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; align-items: center; }
.mk-nl__close button { grid-column: 1 / -1; justify-self: start; }
@media (max-width: 800px) {
	.mk-nl__grid, .mk-nl__close, .mk-nl-board__grid, .mk-nl-board__status { grid-template-columns: 1fr; }
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
