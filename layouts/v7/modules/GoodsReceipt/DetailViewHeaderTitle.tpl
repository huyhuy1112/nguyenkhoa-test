{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	<div class="mk-gr-detail-hero__left">
		<div class="mk-gr-detail-hero__identity">
			<div class="mk-gr-detail-hero__icon" aria-hidden="true">
				{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='INBOUND'}
			</div>
			<div class="mk-gr-detail-hero__text">
				<h1 class="mk-gr-detail-hero__title">
					<span class="recordLabel" title="{decode_html($RECORD_DATA.subject)|escape:'html'}">{decode_html($RECORD_DATA.subject)|escape:'html'}</span>
				</h1>
				<p class="mk-gr-detail-hero__subtitle">Inbound receipt details and supporting documents</p>
				<div class="mk-gr-detail-hero__meta">
					<span class="mk-gr-detail-status-pill">Received</span>
					{if $RECORD_DATA.code}<span class="mk-gi-chip mk-gr-detail-hero__code">{$RECORD_DATA.code|escape:'html'}</span>{/if}
					{if $RECORD_DATA.received_date}
						<span class="mk-gr-detail-hero__meta-date">
							<svg class="mk-gr-detail-hero__meta-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
							{$RECORD_DATA.received_date|escape:'html'}
						</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="col-sm-8">
		<h3 style="margin-top:0;">{$RECORD_DATA.subject|escape:'html'}</h3>
	</div>
{/if}
{/strip}
