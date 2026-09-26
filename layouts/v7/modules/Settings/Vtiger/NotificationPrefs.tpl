{*+***********************************************************************************
 * Settings → Quản lý thông báo (per-user toggles + company R1 schedule for admin)
 *************************************************************************************}
{strip}
{assign var=R1 value=$NK_R1_SETTINGS}
{assign var=R1_DAYS value=','|explode:$R1.work_days}
<div class="nk-notif-prefs" id="nk-notif-prefs">
	<div class="nk-notif-prefs__hero">
		<p class="nk-notif-prefs__eyebrow">Cá nhân</p>
		<h1 class="nk-notif-prefs__title">{vtranslate('LBL_NK_NOTIFICATION_PREFS', $QUALIFIED_MODULE)}</h1>
		<p class="nk-notif-prefs__desc">{vtranslate('LBL_NK_NOTIFICATION_PREFS_DESC', $QUALIFIED_MODULE)}</p>
	</div>

	{if $NK_R1_IS_ADMIN}
	<section class="nk-notif-prefs__card nk-notif-prefs__card--r1" id="nk-r1-settings-card">
		<p class="nk-notif-prefs__eyebrow" style="padding:16px 28px 0;margin:0">Công ty · Chỉ admin</p>
		<h2 class="nk-notif-prefs__group-title">Nhắc R1 / Hẹn gọi lại</h2>
		<p class="nk-notif-prefs__r1-desc">Khung giờ làm việc dùng chung toàn công ty. Ngoài khung giờ, nhắc dồn sang giờ mở cửa ngày làm việc tiếp theo.</p>
		<form id="nk-r1-settings-form" class="nk-r1-form" autocomplete="off">
			<div class="nk-r1-grid">
				<label class="nk-r1-field">
					<span>Giờ bắt đầu</span>
					<input type="time" name="work_start" value="{$R1.work_start|escape}" required />
				</label>
				<label class="nk-r1-field">
					<span>Giờ kết thúc</span>
					<input type="time" name="work_end" value="{$R1.work_end|escape}" required />
				</label>
				<label class="nk-r1-field">
					<span>Khoảng cách nhắc (giờ)</span>
					<input type="number" name="gap_hours" min="1" max="24" value="{$R1.gap_hours|escape}" required />
				</label>
				<label class="nk-r1-field">
					<span>Số lần tối đa trước Ngưng CSKH</span>
					<input type="number" name="max_attempts" min="1" max="20" value="{$R1.max_attempts|escape}" required />
				</label>
			</div>
			<div class="nk-r1-days">
				<span class="nk-r1-days__label">Ngày làm việc</span>
				<div class="nk-r1-days__row">
					{foreach from=$NK_R1_DAY_LABELS key=DCODE item=DLABEL}
						<label class="nk-r1-day">
							<input type="checkbox" class="js-nk-r1-day" value="{$DCODE}" {if in_array((string)$DCODE, $R1_DAYS)}checked{/if} />
							<span>{$DLABEL}</span>
						</label>
					{/foreach}
				</div>
			</div>
			<div class="nk-notif-prefs__actions" style="padding: 8px 28px 20px;">
				<button type="submit" class="btn btn-success nk-notif-prefs__save">Lưu lịch R1</button>
				<span class="nk-notif-prefs__hint" id="nk-r1-settings-status" aria-live="polite"></span>
			</div>
		</form>
	</section>
	{else}
	<section class="nk-notif-prefs__card">
		<h2 class="nk-notif-prefs__group-title">Nhắc R1 / Hẹn gọi lại</h2>
		<p class="nk-notif-prefs__r1-desc" style="padding:0 28px 20px">
			Công ty đang dùng khung <strong>{$R1.work_start|escape}–{$R1.work_end|escape}</strong>,
			nhắc mỗi <strong>{$R1.gap_hours|escape} giờ</strong>, tối đa <strong>{$R1.max_attempts|escape} lần</strong>.
			Chỉ admin được chỉnh.
		</p>
	</section>
	{/if}

	<form id="nk-notif-prefs-form" class="nk-notif-prefs__form" autocomplete="off">
		<section class="nk-notif-prefs__card">
			<h2 class="nk-notif-prefs__group-title">Âm thanh chuông</h2>
			<label class="nk-notif-prefs__row">
				<span class="nk-notif-prefs__label">Bật âm thanh khi có thông báo mới</span>
				<input type="checkbox" name="sound_enabled" class="nk-notif-prefs__switch" value="1" {if $NK_NOTIF_SOUND.enabled}checked{/if} />
			</label>
		</section>

		{foreach from=$NK_NOTIF_GROUPS key=GROUP_NAME item=ITEMS}
			<section class="nk-notif-prefs__card">
				<h2 class="nk-notif-prefs__group-title">{$GROUP_NAME|escape}</h2>
				{foreach from=$ITEMS item=ITEM}
					<label class="nk-notif-prefs__row">
						<span class="nk-notif-prefs__label">{$ITEM.label|escape}</span>
						<input type="checkbox" class="nk-notif-prefs__switch js-nk-notif-channel" name="channels[{$ITEM.key|escape}]" data-channel="{$ITEM.key|escape}" value="1" {if $ITEM.enabled}checked{/if} />
					</label>
				{/foreach}
			</section>
		{/foreach}

		<div class="nk-notif-prefs__actions">
			<button type="submit" class="btn btn-success nk-notif-prefs__save">{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</button>
			<span class="nk-notif-prefs__hint" id="nk-notif-prefs-status" aria-live="polite"></span>
		</div>
	</form>
</div>
{/strip}
