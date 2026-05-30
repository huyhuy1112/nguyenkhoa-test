{strip}
<section class="mk-act-detail-hero-card">
	<div class="mk-act-detail-hero">
		<div class="mk-act-detail-hero__brand">
			<div class="mk-act-detail-hero__icon-col">
				<div class="mk-act-detail-hero__icon" aria-hidden="true">
					{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='MEGAPHONE'}
				</div>
				<button type="button" class="mk-act-btn mk-act-btn--tag" disabled title="Coming soon">+ Add Tag</button>
			</div>
			<div class="mk-act-detail-hero__text">
				<h1 class="mk-act-detail-hero__title">{$RECORD_DATA.subject|escape}</h1>
				<div class="mk-act-detail-hero__meta">
					{if $RECORD_DATA.type_label neq '—'}
						<span class="mk-act-type-pill mk-act-type-pill--{$RECORD_DATA.type_class|escape}">{$RECORD_DATA.type_label|escape}</span>
					{/if}
					<span class="mk-act-detail-hero__created">Created at {$RECORD_DATA.created_at_display|escape}</span>
				</div>
			</div>
		</div>
		<div class="mk-act-detail-hero__actions">
			<button type="button" class="mk-act-btn mk-act-btn--ghost" disabled title="Coming soon">
				{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='IMPORT'}<span>Import</span>
			</button>
			<button type="button" class="mk-act-btn mk-act-btn--ghost" disabled title="Coming soon">
				{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='CUSTOMIZE'}<span>Customize</span>
			</button>
			<a class="mk-act-btn mk-act-btn--primary" href="index.php?module=Activities&amp;view=Edit&amp;record={$RECORD_ID}&amp;app=SUPPORT">
				{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}<span>Add record</span>
			</a>
		</div>
	</div>
</section>
{/strip}