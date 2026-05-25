{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
	<div class="mk-go-detail-hero__left">
		<div class="mk-go-detail-hero__identity">
			<div class="mk-go-detail-hero__icon" aria-hidden="true">
				{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='OUTBOUND'}
			</div>
			<div class="mk-go-detail-hero__text">
				<h1 class="mk-go-detail-hero__title">
					<span class="recordLabel" title="{$RECORD_DATA.subject|escape:'html'}">{$RECORD_DATA.subject|escape:'html'}</span>
				</h1>
				<p class="mk-go-detail-hero__subtitle">Outbound issue details and stock deduction</p>
				<div class="mk-go-detail-hero__meta">
					<span class="mk-go-detail-status-pill">Issued</span>
					{if $RECORD_DATA.code}<span class="mk-gi-chip mk-go-detail-hero__code">{$RECORD_DATA.code|escape:'html'}</span>{/if}
					{if $RECORD_DATA.issued_date}
						<span class="mk-go-detail-hero__meta-date">
							<svg class="mk-go-detail-hero__meta-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
							{$RECORD_DATA.issued_date|escape:'html'}
						</span>
					{/if}
					{if $RECORD_DATA.destination}
						<span class="mk-go-detail-hero__meta-dest">{$RECORD_DATA.destination|escape:'html'}</span>
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
