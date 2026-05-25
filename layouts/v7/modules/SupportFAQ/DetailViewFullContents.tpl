{strip}
{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}

<div class="mk-sf-faq-detail-tabs-wrap">
	<nav class="mk-sf-faq-detail-tabs" role="tablist" aria-label="FAQ sections">
		<button type="button" class="mk-sf-faq-detail-tabs__btn is-active" role="tab" aria-selected="true" data-mk-sf-faq-tab="summary">Summary</button>
		<button type="button" class="mk-sf-faq-detail-tabs__btn" role="tab" aria-selected="false" data-mk-sf-faq-tab="details">Details</button>
	</nav>
</div>

<div class="mk-sf-faq-detail-panels">
	<div class="mk-sf-faq-detail-panel is-active" id="mk-sf-faq-tab-summary" role="tabpanel">
		{include file="DetailViewSummary.tpl"|vtemplate_path:$MODULE}
	</div>
	<div class="mk-sf-faq-detail-panel" id="mk-sf-faq-tab-details" role="tabpanel" hidden>
		<section class="mk-sf-faq-detail-summary">
			<header class="mk-sf-faq-detail-summary__head">
				<h2 class="mk-sf-faq-detail-summary__title">Details</h2>
			</header>
			<div class="mk-sf-faq-detail-summary__body mk-sf-faq-detail-summary__body--pad">
				<p class="mk-sf-faq-detail-muted">All FAQ fields are shown on the Summary tab. Use Edit to update this record.</p>
			</div>
		</section>
	</div>
</div>
{/strip}
