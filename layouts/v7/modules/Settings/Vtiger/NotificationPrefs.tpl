{*+***********************************************************************************
 * Settings → Quản lý thông báo (per-user toggles)
 *************************************************************************************}
{strip}
<div class="nk-notif-prefs" id="nk-notif-prefs">
	<div class="nk-notif-prefs__hero">
		<p class="nk-notif-prefs__eyebrow">Cá nhân</p>
		<h1 class="nk-notif-prefs__title">{vtranslate('LBL_NK_NOTIFICATION_PREFS', $QUALIFIED_MODULE)}</h1>
		<p class="nk-notif-prefs__desc">{vtranslate('LBL_NK_NOTIFICATION_PREFS_DESC', $QUALIFIED_MODULE)}</p>
	</div>

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
