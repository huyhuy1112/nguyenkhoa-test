{strip}
<section class="mk-sf-faq-detail-top-card">
	{include file="partials/FaqDetailBreadcrumb.tpl"|vtemplate_path:$MODULE}
	<div class="mk-sf-faq-detail-hero">
		<div class="mk-sf-faq-detail-hero__brand">
			<div class="mk-sf-faq-detail-hero__icon-col">
				<div class="mk-sf-faq-detail-hero__icon" aria-hidden="true">FAQ</div>
				<button type="button" class="mk-sf-faq-btn mk-sf-faq-btn--tag" disabled title="Coming soon">+ Add Tag</button>
			</div>
			<div class="mk-sf-faq-detail-hero__text">
				<h1 class="mk-sf-faq-detail-hero__title">{$RECORD_DATA.question|escape:'html'}</h1>
				<p class="mk-sf-faq-detail-hero__meta">Created at {$RECORD_DATA.created_at_display|escape:'html'}</p>
			</div>
		</div>
		{include file="partials/FaqDetailHeroActions.tpl"|vtemplate_path:$MODULE}
	</div>
</section>
{/strip}
