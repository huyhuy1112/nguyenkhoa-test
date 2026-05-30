{strip}
<section class="mk-act-detail-card">
	<header class="mk-act-detail-card__head">
		<h2 class="mk-act-detail-card__title">
			Activity #{$RECORD_ID}
			<span class="mk-act-detail-card__info" aria-hidden="true">{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='INFO'}</span>
		</h2>
	</header>
	<div class="mk-act-detail-card__body">
		<dl class="mk-act-detail-fields">
			<div class="mk-act-detail-fields__row">
				<dt>Type</dt>
				<dd>
					{if $RECORD_DATA.type_label neq '—'}
						<span class="mk-act-type-pill mk-act-type-pill--{$RECORD_DATA.type_class|escape}">{$RECORD_DATA.type_label|escape}</span>
					{else}
						<span class="mk-act-detail-empty-dash">—</span>
					{/if}
				</dd>
			</div>

			<div class="mk-act-detail-fields__row mk-act-detail-fields__row--content">
				<dt>Content</dt>
				<dd>
					<div class="mk-act-detail-content-box">{$RECORD_DATA.content|escape|nl2br}</div>
				</dd>
			</div>

			<div class="mk-act-detail-fields__row">
				<dt>Organization</dt>
				<dd>{if $RECORD_DATA.org_name neq ''}{$RECORD_DATA.org_name|escape}{else}<span class="mk-act-detail-empty-dash">—</span>{/if}</dd>
			</div>

			<div class="mk-act-detail-fields__row mk-act-detail-fields__row--group">
				<dt>Tracking Tag</dt>
				<dd>
					<div class="mk-act-detail-subfields">
						<div class="mk-act-detail-subfields__item">
							<span class="mk-act-detail-subfields__label">Project</span>
							<span class="mk-act-detail-subfields__value">{if $RECORD_DATA.project_name neq ''}{$RECORD_DATA.project_name|escape}{else}<span class="mk-act-detail-empty-dash">—</span>{/if}</span>
						</div>
						<div class="mk-act-detail-subfields__item">
							<span class="mk-act-detail-subfields__label">Ticket ID</span>
							<span class="mk-act-detail-subfields__value">
								{if $RECORD_DATA.ticketid gt 0}
									<a href="index.php?module=HelpDesk&amp;view=TicketDetail&amp;record={$RECORD_DATA.ticketid}&amp;app=SUPPORT">{$RECORD_DATA.ticketid}</a>
								{else}
									<span class="mk-act-detail-empty-dash">—</span>
								{/if}
							</span>
						</div>
					</div>
				</dd>
			</div>

			<div class="mk-act-detail-fields__row">
				<dt>Assigned To</dt>
				<dd>
					<span class="mk-act-detail-assignee">
						<span class="mk-act-detail-assignee__avatar" aria-hidden="true">{$RECORD_DATA.assigned_initials|escape}</span>
						<span class="mk-act-detail-assignee__name">{$RECORD_DATA.assigned_name|escape}</span>
					</span>
				</dd>
			</div>

			<div class="mk-act-detail-fields__row">
				<dt>Execution &amp; Status</dt>
				<dd>
					<span class="mk-act-detail-exec">
						<span class="mk-act-detail-exec__date">
							{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='CALENDAR'}
							<span>{$RECORD_DATA.activity_date_display|escape}</span>
						</span>
						{if $RECORD_DATA.status neq '—'}
							<span class="mk-act-status-pill mk-act-status-pill--{$RECORD_DATA.status_class|escape}">{$RECORD_DATA.status|escape}</span>
						{/if}
					</span>
				</dd>
			</div>

			<div class="mk-act-detail-fields__row mk-act-detail-fields__row--notes">
				<dt>Notes</dt>
				<dd>
					<div class="mk-act-detail-notes">
						<div class="mk-act-detail-notes__block">
							<span class="mk-act-detail-notes__label">Before</span>
							<div class="mk-act-detail-notes__value">
								{if $RECORD_DATA.note_before neq ''}{$RECORD_DATA.note_before|escape|nl2br}{else}<span class="mk-act-detail-empty-dash">—</span>{/if}
							</div>
						</div>
						<div class="mk-act-detail-notes__block">
							<span class="mk-act-detail-notes__label">After</span>
							<div class="mk-act-detail-notes__value">
								{if $RECORD_DATA.note_after neq ''}{$RECORD_DATA.note_after|escape|nl2br}{else}<span class="mk-act-detail-empty-dash">—</span>{/if}
							</div>
						</div>
					</div>
				</dd>
			</div>

			<div class="mk-act-detail-fields__row mk-act-detail-fields__row--logs">
				<dt>Logs</dt>
				<dd>
					<ul class="mk-act-detail-logs">
						<li>
							{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='LOG_CREATED'}
							<span><strong>Created:</strong> {$RECORD_DATA.created_display|escape}</span>
						</li>
						<li>
							{include file="partials/ActivityDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='LOG_MODIFIED'}
							<span><strong>Modified:</strong> {$RECORD_DATA.modified_display|escape}</span>
						</li>
					</ul>
				</dd>
			</div>
		</dl>
	</div>
</section>
{/strip}