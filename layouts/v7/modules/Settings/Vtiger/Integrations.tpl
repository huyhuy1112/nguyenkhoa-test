{* Settings → Tích hợp hệ thống *}
{strip}
<div class="detailViewContainer nk-integ" id="NkSystemIntegrations">
	<p class="nk-integ__intro">{vtranslate('LBL_NK_SYSTEM_INTEGRATIONS_DESC', $QUALIFIED_MODULE)}</p>

	<div class="nk-integ__list">
		{foreach from=$CONNECTIONS item=CONN name=connLoop}
			<article class="nk-integ-card nk-integ-card--{$CONN.code|escape:'html'}" data-code="{$CONN.code|escape:'html'}" data-status="{$CONN.status|escape:'html'}" style="--nk-integ-delay: {$smarty.foreach.connLoop.iteration * 60}ms">
				<header class="nk-integ-card__head">
					<div class="nk-integ-card__brand">
						<span class="nk-integ-card__icon" aria-hidden="true">
							{assign var=ICON value=$CONN.icon|default:$CONN.code}
							<img src="layouts/v7/modules/Settings/Vtiger/resources/logos/{$ICON|escape:'url'}.svg" width="24" height="24" alt="" loading="lazy" />
						</span>
						<div class="nk-integ-card__titles">
							<h2 class="nk-integ-card__name">{$CONN.label|escape:'html'}</h2>
							<p class="nk-integ-card__desc">{$CONN.description|escape:'html'}</p>
						</div>
					</div>
					<span class="nk-integ-badge nk-integ-badge--{$CONN.status|escape:'html'}" data-role="status">
						<span class="nk-integ-badge__dot" aria-hidden="true"></span>
						<span class="nk-integ-badge__text">{$CONN.status_label|escape:'html'}</span>
					</span>
				</header>

				{if $CONN.hint}
					<div class="nk-integ-callout">
						<svg class="nk-integ-callout__ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
						<p>{$CONN.hint|escape:'html'}</p>
					</div>
				{/if}

				<form class="nk-integ-form" data-code="{$CONN.code|escape:'html'}" autocomplete="off">
					<div class="nk-integ-toolbar">
						<label class="nk-integ-toggle">
							<input type="checkbox" name="enabled" value="1" class="nk-integ-toggle__input" {if $CONN.enabled}checked{/if} />
							<span class="nk-integ-toggle__track" aria-hidden="true"><span class="nk-integ-toggle__thumb"></span></span>
							<span class="nk-integ-toggle__label">{vtranslate('LBL_NK_INTEG_ENABLED', $QUALIFIED_MODULE)}</span>
						</label>
					</div>

					{if $CONN.code eq 'google_sheet'}
						<div class="nk-integ-fields nk-integ-fields--2col">
							<label class="nk-integ-field nk-integ-field--wide">
								<span>{vtranslate('LBL_NK_INTEG_SHEET_URL', $QUALIFIED_MODULE)}</span>
								<input type="text" name="spreadsheet_id" value="{$CONN.extra.spreadsheet_id|escape:'html'}" placeholder="https://docs.google.com/spreadsheets/d/.../edit" autocomplete="off" />
							</label>
							<label class="nk-integ-field">
								<span>{vtranslate('LBL_NK_INTEG_SHEET_RANGE', $QUALIFIED_MODULE)}</span>
								<input type="text" name="sheet_range" value="{$CONN.extra.sheet_range|escape:'html'}" placeholder="Sheet1" autocomplete="off" />
							</label>
						</div>

						{if $CONN.credentials_configured}
							<div class="nk-integ-chip" data-role="sa-chip">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
								<span>{vtranslate('LBL_NK_INTEG_CONFIGURED', $QUALIFIED_MODULE)}{if $CONN.extra.service_account_email}: {$CONN.extra.service_account_email|escape:'html'}{/if}</span>
							</div>
						{/if}

						<details class="nk-integ-advanced">
							<summary>{vtranslate('LBL_NK_INTEG_ADVANCED', $QUALIFIED_MODULE)}</summary>
							<div class="nk-integ-advanced__body">
								<label class="nk-integ-field">
									<span>{vtranslate('LBL_NK_INTEG_SA_JSON', $QUALIFIED_MODULE)}</span>
									<textarea name="service_account_json" rows="6" placeholder="{vtranslate('LBL_NK_INTEG_SA_PLACEHOLDER', $QUALIFIED_MODULE)}"></textarea>
								</label>
								<label class="nk-integ-field">
									<span>{vtranslate('LBL_NK_INTEG_COLUMN_MAP', $QUALIFIED_MODULE)}</span>
									<textarea name="column_map" rows="6" spellcheck="false">{$CONN.extra.column_map_json|escape:'html'}</textarea>
								</label>
							</div>
						</details>
					{elseif $CONN.code eq 'zalo_oa'}
						<div class="nk-integ-fields nk-integ-fields--2col nk-integ-fields--zalo">
							<label class="nk-integ-field">
								<span>{vtranslate('LBL_NK_INTEG_ZALO_APP_ID', $QUALIFIED_MODULE)}</span>
								<input type="text" name="app_id" value="{$CONN.extra.app_id|escape:'html'}" placeholder="1952002764330598589" autocomplete="off" inputmode="numeric" />
							</label>
							<label class="nk-integ-field">
								<span>{vtranslate('LBL_NK_INTEG_ZALO_OA_ID', $QUALIFIED_MODULE)}</span>
								<input type="text" name="oa_id" value="{$CONN.extra.oa_id|escape:'html'}" placeholder="{vtranslate('LBL_NK_INTEG_ZALO_OA_ID_HINT', $QUALIFIED_MODULE)}" autocomplete="off" />
							</label>
							<label class="nk-integ-field nk-integ-field--wide">
								<span>{vtranslate('LBL_NK_INTEG_ZALO_SECRET', $QUALIFIED_MODULE)}</span>
								<input type="password" name="secret_key" value="" placeholder="{vtranslate('LBL_NK_INTEG_SECRET_PLACEHOLDER', $QUALIFIED_MODULE)}" autocomplete="new-password" />
								{if $CONN.extra.secret_configured}
									<em class="nk-integ-field__hint" data-role="zalo-secret-chip">{vtranslate('LBL_NK_INTEG_CONFIGURED', $QUALIFIED_MODULE)}</em>
								{/if}
							</label>
							<label class="nk-integ-field nk-integ-field--wide">
								<span>{vtranslate('LBL_NK_INTEG_ZALO_REFRESH', $QUALIFIED_MODULE)}</span>
								<input type="password" name="refresh_token" value="" placeholder="{vtranslate('LBL_NK_INTEG_ZALO_REFRESH_PLACEHOLDER', $QUALIFIED_MODULE)}" autocomplete="new-password" />
								{if $CONN.extra.refresh_token_configured}
									<em class="nk-integ-field__hint" data-role="zalo-refresh-chip">{vtranslate('LBL_NK_INTEG_CONFIGURED', $QUALIFIED_MODULE)}</em>
								{/if}
							</label>
						</div>

						<div class="nk-integ-zalo-meta">
							{if $CONN.extra.oa_name}
								<div class="nk-integ-chip" data-role="zalo-oa-chip">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
									<span data-role="zalo-oa-name">{$CONN.extra.oa_name|escape:'html'}</span>
								</div>
							{else}
								<div class="nk-integ-chip nk-integ-chip--muted" data-role="zalo-oa-chip" hidden>
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
									<span data-role="zalo-oa-name"></span>
								</div>
							{/if}
							{if $CONN.extra.expires_at}
								<p class="nk-integ-zalo-expiry" data-role="zalo-expiry">{vtranslate('LBL_NK_INTEG_ZALO_EXPIRES', $QUALIFIED_MODULE)}: {$CONN.extra.expires_at|escape:'html'}</p>
							{else}
								<p class="nk-integ-zalo-expiry" data-role="zalo-expiry" hidden></p>
							{/if}
							<label class="nk-integ-field nk-integ-field--wide nk-integ-zalo-callback">
								<span>{vtranslate('LBL_NK_INTEG_ZALO_CALLBACK', $QUALIFIED_MODULE)}</span>
								<input type="text" readonly value="{$CONN.extra.callback_url|escape:'html'}" onclick="this.select();" title="{vtranslate('LBL_NK_INTEG_ZALO_CALLBACK_HINT', $QUALIFIED_MODULE)}" />
								<em class="nk-integ-field__hint">{vtranslate('LBL_NK_INTEG_ZALO_CALLBACK_HINT', $QUALIFIED_MODULE)}</em>
							</label>
						</div>
					{else}
						<div class="nk-integ-fields nk-integ-fields--2col">
							<label class="nk-integ-field nk-integ-field--wide">
								<span>{vtranslate('LBL_NK_INTEG_BASE_URL', $QUALIFIED_MODULE)}</span>
								<input type="text" name="base_url" value="{$CONN.base_url|escape:'html'}" placeholder="https://api.example.com" autocomplete="off" />
							</label>
							<label class="nk-integ-field">
								<span>{vtranslate('LBL_NK_INTEG_USERNAME', $QUALIFIED_MODULE)}</span>
								<input type="text" name="username" value="{$CONN.username|escape:'html'}" autocomplete="off" />
							</label>
							<label class="nk-integ-field">
								<span>{vtranslate('LBL_NK_INTEG_API_KEY', $QUALIFIED_MODULE)}</span>
								<input type="password" name="api_key" value="" placeholder="{vtranslate('LBL_NK_INTEG_SECRET_PLACEHOLDER', $QUALIFIED_MODULE)}" autocomplete="new-password" />
								{if $CONN.credentials_configured}
									<em class="nk-integ-field__hint">{vtranslate('LBL_NK_INTEG_CONFIGURED', $QUALIFIED_MODULE)}</em>
								{/if}
							</label>
							<label class="nk-integ-field">
								<span>{vtranslate('LBL_NK_INTEG_PASSWORD', $QUALIFIED_MODULE)}</span>
								<input type="password" name="password" value="" placeholder="{vtranslate('LBL_NK_INTEG_SECRET_PLACEHOLDER', $QUALIFIED_MODULE)}" autocomplete="new-password" />
							</label>
						</div>
					{/if}

					<div class="nk-integ-status-row">
						<div class="nk-integ-stat">
							<span class="nk-integ-stat__label">{vtranslate('LBL_NK_INTEG_LAST_SYNC', $QUALIFIED_MODULE)}</span>
							<span class="nk-integ-stat__value" data-role="last-sync">{if $CONN.last_sync}{$CONN.last_sync|escape:'html'}{else}—{/if}</span>
						</div>
						<div class="nk-integ-stat nk-integ-stat--error{if $CONN.last_error} is-active{/if}">
							<span class="nk-integ-stat__label">{vtranslate('LBL_NK_INTEG_LAST_ERROR', $QUALIFIED_MODULE)}</span>
							<span class="nk-integ-stat__value" data-role="last-error">{if $CONN.last_error}{$CONN.last_error|escape:'html'}{else}—{/if}</span>
						</div>
					</div>

					<div class="nk-integ-msg" data-role="message" hidden></div>

					<footer class="nk-integ-actions">
						{if $CONN.code eq 'zalo_oa'}
							<button type="button" class="mk-settings-btn mk-settings-btn--zalo nk-integ-zalo-oauth" data-code="zalo_oa">
								<span class="nk-integ-btn__label">{vtranslate('LBL_NK_INTEG_ZALO_CONNECT', $QUALIFIED_MODULE)}</span>
							</button>
						{/if}
						<button type="button" class="mk-settings-btn mk-settings-btn--outline nk-integ-test" data-code="{$CONN.code|escape:'html'}">
							<span class="nk-integ-btn__label">{vtranslate('LBL_NK_INTEG_TEST', $QUALIFIED_MODULE)}</span>
						</button>
						<button type="submit" class="mk-settings-btn mk-settings-btn--primary nk-integ-save">
							<span class="nk-integ-btn__label">{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</span>
						</button>
					</footer>
				</form>
			</article>
		{/foreach}
	</div>
</div>
{/strip}
