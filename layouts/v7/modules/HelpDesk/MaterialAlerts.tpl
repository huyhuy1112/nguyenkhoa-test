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
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=rules">Rule</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=tags">Tag</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=scenarios">Kịch bản</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=affiliate">Mã giới thiệu</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=questions">Câu hỏi</a>
			<a class="mk-tre-tab is-active" href="index.php?module=HelpDesk&amp;view=MaterialAlerts&amp;app=SUPPORT" aria-current="page">Cảnh báo nguyên liệu</a>
		</div>
	</div>

	<form class="mk-nl-settings" id="mk-nl-settings">
		<div class="mk-nl-settings__bar">
			<div>
				<h2>Chọn khi nào thì báo</h2>
				<p>Điền số vào ô muốn bật. Ô để trống thì việc đó không báo. Việc đang mở và chỉ số một khách nằm ở menu Cảnh báo.</p>
			</div>
			<div class="mk-nl-settings__actions">
				<p class="mk-nl-msg" data-nl-msg hidden></p>
				<button type="submit" class="mk-nl-save">Lưu ngưỡng</button>
			</div>
		</div>
		<div class="mk-nl-settings__groups">
		{assign var=NL_GROUP value=''}
		{foreach from=$NL_FIELDS item=FIELD}
			{if $FIELD.group neq $NL_GROUP}
				{if $NL_GROUP neq ''}</div></section>{/if}
				{assign var=NL_GROUP value=$FIELD.group}
				<section class="mk-nl-group">
					<h3>{$FIELD.group|escape}</h3>
					<div class="mk-nl-grid">
			{/if}
			<label class="mk-nl-field">
				<span>{$FIELD.label|escape}</span>
				<small>{$FIELD.hint|escape}</small>
				<input type="text" name="{$FIELD.key|escape}" value="{$NL_SETTINGS[$FIELD.key]|escape}" placeholder="{$FIELD.placeholder|escape}" />
			</label>
		{/foreach}
		{if $NL_GROUP neq ''}</div></section>{/if}
		</div>
	</form>

</div>
<style>
.mk-nl-page { width: 100%; max-width: none; box-sizing: border-box; }
.mk-nl-settings__bar,
.mk-nl-group {
	background: #fff;
	border: 1px solid #e2e8f0;
	border-radius: 16px;
	box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}
.mk-nl-settings__bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	padding: 18px 20px;
	margin-bottom: 14px;
}
.mk-nl-settings__bar h2 {
	margin: 0;
	font-size: 20px;
	color: #0f172a;
}
.mk-nl-settings__bar p {
	margin: 6px 0 0;
	max-width: 640px;
	color: #64748b;
	font-size: 14px;
	line-height: 1.45;
}
.mk-nl-settings__groups {
	display: flex;
	flex-direction: column;
	gap: 14px;
}
.mk-nl-group {
	padding: 18px 20px 20px;
}
.mk-nl-group h3 {
	margin: 0 0 14px;
	padding-left: 12px;
	border-left: 3px solid #08a045;
	font-size: 15px;
	color: #0b6e4f;
}
.mk-nl-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 14px;
}
.mk-nl-field {
	display: flex;
	flex-direction: column;
	gap: 6px;
	min-width: 0;
	padding: 14px;
	background: #f8fafc;
	border: 1px solid #e8edf3;
	border-radius: 14px;
}
.mk-nl-field span {
	font-size: 14px;
	font-weight: 700;
	color: #0f172a;
	line-height: 1.35;
}
.mk-nl-field small {
	font-size: 12.5px;
	font-weight: 500;
	color: #64748b;
	line-height: 1.45;
}
.mk-nl-field input {
	width: 100%;
	box-sizing: border-box;
	margin-top: 4px;
	border: 1px solid #dbe3ec;
	border-radius: 12px;
	padding: 11px 12px;
	background: #fff;
	color: #0f172a;
	font-weight: 500;
}
.mk-nl-field input:focus {
	outline: none;
	border-color: #08a045;
	box-shadow: 0 0 0 3px rgba(8, 160, 69, 0.15);
}
.mk-nl-save {
	border: 0;
	border-radius: 12px;
	padding: 10px 16px;
	background: #08a045;
	color: #fff;
	font-weight: 700;
	cursor: pointer;
	white-space: nowrap;
	box-shadow: 0 1px 2px rgba(8, 160, 69, 0.25);
}
.mk-nl-save:hover { background: #078a3b; }
.mk-nl-settings__actions {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-shrink: 0;
}
.mk-nl-msg {
	margin: 0;
	color: #0b6e4f;
	font-weight: 700;
}
@media (max-width: 800px) {
	.mk-nl-settings__bar { flex-direction: column; align-items: stretch; }
	.mk-nl-settings__actions { justify-content: flex-end; }
	.mk-nl-grid { grid-template-columns: 1fr; }
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
})();
</script>
{/strip}
