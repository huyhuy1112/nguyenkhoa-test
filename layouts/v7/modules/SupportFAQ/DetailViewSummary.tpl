{strip}
<section class="mk-sf-faq-detail-summary">
	<header class="mk-sf-faq-detail-summary__head">
		<h2 class="mk-sf-faq-detail-summary__title">Support FAQ Information</h2>
	</header>
	<div class="mk-sf-faq-detail-summary__body">
		<div class="mk-sf-faq-detail-fields-grid">
			<div class="mk-sf-faq-detail-field">
				<div class="mk-sf-faq-detail-field__label">Description</div>
				<div class="mk-sf-faq-detail-field__value">
					{if $RECORD_DATA.description neq ''}
						<div class="mk-sf-faq-detail-field__box">{$RECORD_DATA.description|escape:'html'|nl2br}</div>
					{else}
						<span class="mk-sf-faq-detail-field__empty">—</span>
					{/if}
				</div>
			</div>
			<div class="mk-sf-faq-detail-field">
				<div class="mk-sf-faq-detail-field__label">Related Ticket</div>
				<div class="mk-sf-faq-detail-field__value">
					{if $RECORD_DATA.related_ticket_id gt 0}
						<a href="{$RECORD_DATA.ticket_detail_url|escape:'html'}" class="mk-sf-faq-detail-field__link">{$RECORD_DATA.related_ticket_id}</a>
					{else}
						<span class="mk-sf-faq-detail-field__empty">—</span>
					{/if}
				</div>
			</div>
			<div class="mk-sf-faq-detail-field">
				<div class="mk-sf-faq-detail-field__label">Solution</div>
				<div class="mk-sf-faq-detail-field__value">
					{if $RECORD_DATA.solution neq ''}
						<div class="mk-sf-faq-detail-field__box">{$RECORD_DATA.solution|escape:'html'|nl2br}</div>
					{else}
						<span class="mk-sf-faq-detail-field__empty">—</span>
					{/if}
				</div>
			</div>
			<div class="mk-sf-faq-detail-field">
				<div class="mk-sf-faq-detail-field__label">Created Time</div>
				<div class="mk-sf-faq-detail-field__value">{$RECORD_DATA.created_display|escape:'html'}</div>
			</div>
			<div class="mk-sf-faq-detail-field">
				<div class="mk-sf-faq-detail-field__label">Occurrence Count</div>
				<div class="mk-sf-faq-detail-field__value">{$RECORD_DATA.occurrence_count}</div>
			</div>
			<div class="mk-sf-faq-detail-field">
				<div class="mk-sf-faq-detail-field__label">Question</div>
				<div class="mk-sf-faq-detail-field__value">{$RECORD_DATA.question|escape:'html'}</div>
			</div>
			<div class="mk-sf-faq-detail-field">
				<div class="mk-sf-faq-detail-field__label">Assigned To</div>
				<div class="mk-sf-faq-detail-field__value">
					<span class="mk-sf-faq-detail-assignee">
						<span class="mk-sf-faq-detail-assignee__avatar" aria-hidden="true">{$RECORD_DATA.assigned_initials|escape:'html'}</span>
						<span class="mk-sf-faq-detail-assignee__name">{$RECORD_DATA.assigned_name|escape:'html'}</span>
					</span>
				</div>
			</div>
			<div class="mk-sf-faq-detail-field">
				<div class="mk-sf-faq-detail-field__label">Modified Time</div>
				<div class="mk-sf-faq-detail-field__value">{$RECORD_DATA.modified_display|escape:'html'}</div>
			</div>
		</div>
	</div>
</section>
{/strip}
