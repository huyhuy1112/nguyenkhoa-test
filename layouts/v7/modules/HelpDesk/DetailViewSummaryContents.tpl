{* HelpDesk TicketDetail — single page (match design mockup) *}
{strip}
	{include file="partials/TicketDetailBreadcrumb.tpl"|vtemplate_path:$MODULE}
	{include file="partials/TicketDetailHeader.tpl"|vtemplate_path:$MODULE}

	<div class="mk-hd-detail-tabs-wrap">
		<nav class="mk-hd-detail-tabs" role="tablist" aria-label="Ticket sections">
			<button type="button" class="mk-hd-detail-tabs__btn is-active" role="tab" aria-selected="true" data-mk-hd-tab="overview">Overview</button>
			<button type="button" class="mk-hd-detail-tabs__btn" role="tab" aria-selected="false" data-mk-hd-tab="activity">Activity</button>
			<button type="button" class="mk-hd-detail-tabs__btn" role="tab" aria-selected="false" data-mk-hd-tab="files">Files</button>
			<button type="button" class="mk-hd-detail-tabs__btn" role="tab" aria-selected="false" data-mk-hd-tab="time">Time logs</button>
		</nav>
		<div class="mk-hd-detail-tabs__extras">
			<button type="button" class="mk-hd-detail-tabs__btn mk-hd-detail-tabs__btn--icon" role="tab" aria-selected="false" data-mk-hd-tab="customer">
				{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='USER'}<span>Customer</span>
			</button>
			<button type="button" class="mk-hd-detail-tabs__btn mk-hd-detail-tabs__btn--icon" role="tab" aria-selected="false" data-mk-hd-tab="project">
				{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='ROCKET'}<span>Project</span>
			</button>
			{if !empty($DETAILVIEWBASIC_LINKS)}
				{foreach from=$DETAILVIEWBASIC_LINKS item=L}
					<a class="mk-hd-detail-tabs__btn mk-hd-detail-tabs__btn--icon mk-hd-detail-tabs__btn--link" href="{$L.url|escape:'html'}">
						{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='FAQ'}<span>{$L.label|escape:'html'}</span>
					</a>
				{/foreach}
			{else}
				<a class="mk-hd-detail-tabs__btn mk-hd-detail-tabs__btn--icon mk-hd-detail-tabs__btn--link" href="index.php?module=HelpDesk&amp;action=CreateFAQ&amp;ticket_id={$TICKET.id}&amp;app=SUPPORT">
					{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='FAQ'}<span>Create FAQ</span>
				</a>
			{/if}
		</div>
	</div>

	<div class="mk-hd-detail-bento">
		<div class="mk-hd-detail-main">
			<div class="mk-hd-detail-panel is-active" id="mk-hd-tab-overview" role="tabpanel">
				<section class="mk-hd-card">
					<header class="mk-hd-card__head">
						<h2 class="mk-hd-card__title">Description</h2>
						<span class="mk-hd-card__ic" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='INFO'}</span>
					</header>
					<div class="mk-hd-card__body mk-hd-card__body--description">
						{if $TICKET.description}
							<div class="mk-hd-description">{$TICKET.description|nl2br}</div>
						{else}
							<p class="mk-hd-muted mk-hd-muted--empty">No Related Documents</p>
						{/if}
					</div>
				</section>

				<section class="mk-hd-card">
					<header class="mk-hd-card__head">
						<h2 class="mk-hd-card__title">
							<span class="mk-hd-card__title-ic" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='ACTIVITIES'}</span>
							Recent Activities
						</h2>
						<a class="mk-hd-card__link" href="#" data-mk-hd-tab-link="activity">View All</a>
					</header>
					<div class="mk-hd-card__body mk-hd-card__body--centered">
						{if $RELATED_ACTIVITIES|@count gt 0}
							<ul class="mk-hd-activity-preview">
								{foreach from=$RELATED_ACTIVITIES item=RA name=rap}
									{if $smarty.foreach.rap.iteration lte 3}
										<li>
											<strong>{$RA.activity_type|default:'Activity'|escape:'html'}</strong>
											<span>{$RA.content|default:'-'|escape:'html'}</span>
											<time>{$RA.activity_date|default:'-'|escape:'html'}</time>
										</li>
									{/if}
								{/foreach}
							</ul>
						{else}
							<div class="mk-hd-empty">
								<span class="mk-hd-empty__ic" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='CHECK'}</span>
								<p>No activities linked to this ticket.</p>
							</div>
						{/if}
						<a class="mk-hd-btn mk-hd-btn--outline mk-hd-btn--block" href="index.php?module=Activities&amp;view=Edit&amp;app=SUPPORT&amp;ticketid={$TICKET.id}&amp;from_ticket=1">+ Add Activity</a>
					</div>
				</section>
			</div>

			<div class="mk-hd-detail-panel" id="mk-hd-tab-activity" role="tabpanel" hidden>
				<section class="mk-hd-card">
					<header class="mk-hd-card__head"><h2 class="mk-hd-card__title">Activity Timeline</h2></header>
					<div class="mk-hd-card__body">
						{if $ACTIVITY_LOGS|@count gt 0}
							<ul class="mk-hd-timeline">
								{foreach from=$ACTIVITY_LOGS item=A}
									<li class="mk-hd-timeline__item">
										<time class="mk-hd-timeline__time">{$A.changed_at|escape:'html'}</time>
										<div class="mk-hd-timeline__body">
											<strong>{$A.user_name|escape:'html'}</strong>
											<span>{$A.action_label|default:$A.action_type|escape:'html'}</span>
											{if $A.action_details}<em>{$A.action_details|escape:'html'}</em>{/if}
										</div>
									</li>
								{/foreach}
							</ul>
						{else}
							<p class="mk-hd-muted">No activity yet.</p>
						{/if}
					</div>
				</section>
				<section class="mk-hd-card">
					<header class="mk-hd-card__head">
						<h2 class="mk-hd-card__title">Related Activities</h2>
						<a class="mk-hd-btn mk-hd-btn--primary mk-hd-btn--sm" href="index.php?module=Activities&amp;view=Edit&amp;app=SUPPORT&amp;ticketid={$TICKET.id}&amp;from_ticket=1">+ Create Activity</a>
					</header>
					<div class="mk-hd-card__body mk-hd-table-wrap">
						{if $RELATED_ACTIVITIES|@count gt 0}
							<table class="mk-hd-mini-table">
								<thead><tr><th>Type</th><th>Content</th><th>Assigned</th><th>Date</th><th>Status</th><th></th></tr></thead>
								<tbody>
									{foreach from=$RELATED_ACTIVITIES item=RA}
										<tr>
											<td>{$RA.activity_type|default:'-'|escape:'html'}</td>
											<td>{$RA.content|default:'-'|escape:'html'}</td>
											<td>{$RA.assigned_name|default:'-'|escape:'html'}</td>
											<td>{$RA.activity_date|default:'-'|escape:'html'}</td>
											<td>{$RA.status|default:'-'|escape:'html'}</td>
											<td><a href="index.php?module=Activities&amp;view=Detail&amp;record={$RA.activityid}&amp;app=SUPPORT">View</a></td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						{else}
							<p class="mk-hd-muted">No activities linked to this ticket.</p>
						{/if}
					</div>
				</section>
			</div>

			<div class="mk-hd-detail-panel" id="mk-hd-tab-files" role="tabpanel" hidden>
				<section class="mk-hd-card">
					<header class="mk-hd-card__head"><h2 class="mk-hd-card__title">Files</h2></header>
					<div class="mk-hd-card__body">
						{if $TICKET_FILES|@count gt 0}
							<table class="mk-hd-mini-table">
								<thead><tr><th>File</th><th>Type</th><th>Uploaded By</th><th>Uploaded At</th></tr></thead>
								<tbody>
									{foreach from=$TICKET_FILES item=F}
										{assign var=basename value=$F.file_path|basename}
										<tr>
											<td><a href="{$F.file_path|escape:'html'}" target="_blank" rel="noopener">{$basename|escape:'html'}</a></td>
											<td>{$F.file_type|default:'-'|escape:'html'}</td>
											<td>{$F.uploaded_by_name|default:$F.uploaded_by|escape:'html'}</td>
											<td>{$F.uploaded_at|escape:'html'}</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						{else}
							<p class="mk-hd-muted">No files attached.</p>
						{/if}
					</div>
				</section>
			</div>

			<div class="mk-hd-detail-panel" id="mk-hd-tab-time" role="tabpanel" hidden>
				<section class="mk-hd-card">
					<header class="mk-hd-card__head"><h2 class="mk-hd-card__title">Time Logs</h2></header>
					<div class="mk-hd-card__body">
						{if $TIME_LOGS|@count gt 0}
							<table class="mk-hd-mini-table">
								<thead><tr><th>User</th><th>Minutes</th><th>Note</th><th>Created At</th></tr></thead>
								<tbody>
									{foreach from=$TIME_LOGS item=TL}
										<tr>
											<td>{$TL.user_name|escape:'html'}</td>
											<td>{$TL.minutes_spent}</td>
											<td>{$TL.note|escape:'html'}</td>
											<td>{$TL.created_at|escape:'html'}</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						{else}
							<p class="mk-hd-muted">No time logs.</p>
						{/if}
					</div>
				</section>
			</div>

			<div class="mk-hd-detail-panel" id="mk-hd-tab-customer" role="tabpanel" hidden>
				<section class="mk-hd-card">
					<header class="mk-hd-card__head"><h2 class="mk-hd-card__title">Customer</h2></header>
					<div class="mk-hd-card__body">
						<dl class="mk-hd-kv">
							<dt>Name</dt><dd>{$CUSTOMER_NAME|default:'-'|escape:'html'}</dd>
							<dt>Customer ID</dt><dd>{$TICKET.customer_id}</dd>
						</dl>
					</div>
				</section>
			</div>

			<div class="mk-hd-detail-panel" id="mk-hd-tab-project" role="tabpanel" hidden>
				<section class="mk-hd-card">
					<header class="mk-hd-card__head"><h2 class="mk-hd-card__title">Project</h2></header>
					<div class="mk-hd-card__body">
						<dl class="mk-hd-kv">
							<dt>Project</dt><dd>{$PROJECT_NAME|default:'-'|escape:'html'}</dd>
							<dt>Project ID</dt><dd>{if $TICKET.project_id}{$TICKET.project_id}{else}-{/if}</dd>
						</dl>
					</div>
				</section>
			</div>
		</div>

		<aside class="mk-hd-detail-side">
			<section class="mk-hd-card">
				<header class="mk-hd-card__head">
					<h2 class="mk-hd-card__title">
						<span class="mk-hd-card__title-ic" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='ASSIGN'}</span>
						Assignments
					</h2>
				</header>
				<div class="mk-hd-card__body">
					<form method="post" action="index.php" class="mk-hd-assign-form" id="mkHdAssignForm">
						<input type="hidden" name="module" value="HelpDesk" />
						<input type="hidden" name="app" value="SUPPORT" />
						<input type="hidden" name="action" value="SaveTicket" />
						<input type="hidden" name="mode" value="assignUsers" />
						<input type="hidden" name="ticket_id" value="{$TICKET.id}" />
						<div class="mk-hd-assign-search">
							<div class="mk-hd-assign-search__field">
								<label class="mk-hd-sr-only" for="mkHdAssignSelect">Search staffs</label>
								<select name="assigned_users_ids[]" class="mk-hd-assign-select" multiple="multiple" id="mkHdAssignSelect" size="4" aria-label="Search staffs">
									{foreach from=$ALL_USERS item=U}
										<option value="{$U.id}" {if in_array($U.id, $ASSIGNED_USER_IDS)}selected="selected"{/if}>
											{$U.first_name} {$U.last_name} (ID: {$U.id})
										</option>
									{/foreach}
								</select>
							</div>
							<div class="mk-hd-assign-search__action">
								<button type="submit" class="mk-hd-btn mk-hd-btn--primary mk-hd-btn--pill">Update</button>
							</div>
						</div>
					</form>
					<form method="post" action="index.php" id="mkHdUnassignForm" class="mk-hd-assign-unassign-form" hidden>
						<input type="hidden" name="module" value="HelpDesk" />
						<input type="hidden" name="app" value="SUPPORT" />
						<input type="hidden" name="action" value="SaveTicket" />
						<input type="hidden" name="mode" value="unassignUser" />
						<input type="hidden" name="ticket_id" value="{$TICKET.id}" />
						<input type="hidden" name="user_id" id="mkHdUnassignUserId" value="" />
					</form>
					{if $ASSIGNED_USERS|@count gt 0}
						<div class="mk-hd-assign-tags" aria-label="Assigned staff">
							{foreach from=$ASSIGNED_USERS item=U}
								<span class="mk-hd-assign-tag">
									<span class="mk-hd-assign-tag__label">{$U.first_name} {$U.last_name} (ID: {$U.id})</span>
									<button type="button" class="mk-hd-assign-tag__remove" data-mk-hd-unassign="{$U.id}" aria-label="Remove {$U.first_name} {$U.last_name}">
										{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='CLOSE'}
									</button>
								</span>
							{/foreach}
						</div>
					{/if}
				</div>
			</section>

			<section class="mk-hd-card">
				<header class="mk-hd-card__head">
					<h2 class="mk-hd-card__title">
						<span class="mk-hd-card__title-ic" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='SLA'}</span>
						SLA
					</h2>
				</header>
				<div class="mk-hd-card__body">
					{if $SLA_ENTRIES|@count gt 0}
						<p class="mk-hd-sla-intro">Support Rules SLA: <span class="mk-hd-chip">RULE</span></p>
						{foreach from=$SLA_ENTRIES item=S}
							<div class="mk-hd-sla-row">
								<div class="mk-hd-sla-row__name">{$S.rule_name|escape:'html'}</div>
								<div class="mk-hd-sla-row__cols">
									<div>
										<span class="mk-hd-field-label">Deadline</span>
										<strong>{$S.deadline_at|default:'-'|escape:'html'}</strong>
									</div>
									<div>
										<span class="mk-hd-field-label">Remaining</span>
										{if $S.status eq 'pending' && $S.remaining_seconds !== null}
											{assign var=mins value=($S.remaining_seconds/60)|ceil}
											<strong class="{if $S.remaining_seconds lt 0}mk-hd-text-danger{/if}">{$mins} min</strong>
										{elseif $S.status eq 'overdue'}
											<strong class="mk-hd-text-danger">Overdue</strong>
										{else}
											<strong>—</strong>
										{/if}
									</div>
								</div>
							</div>
						{/foreach}
					{else}
						<dl class="mk-hd-kv mk-hd-kv--compact">
							<dt>SLA Due</dt><dd>{$TICKET.sla_due_at|default:'-'|escape:'html'}</dd>
							<dt>Overdue</dt><dd>{if $TICKET.is_overdue}Yes{else}No{/if}</dd>
						</dl>
					{/if}
				</div>
			</section>

			<section class="mk-hd-card">
				<header class="mk-hd-card__head">
					<h2 class="mk-hd-card__title">
						<span class="mk-hd-card__title-ic" aria-hidden="true">{include file="partials/TicketDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='STATUS'}</span>
						Status &amp; Time
					</h2>
				</header>
				<div class="mk-hd-card__body">
					<form method="post" action="index.php" class="mk-hd-status-form">
						<input type="hidden" name="module" value="HelpDesk" />
						<input type="hidden" name="app" value="SUPPORT" />
						<input type="hidden" name="action" value="SaveTicket" />
						<input type="hidden" name="mode" value="changeStatus" />
						<input type="hidden" name="ticket_id" value="{$TICKET.id}" />
						<label class="mk-hd-field-label">Status</label>
						<div class="mk-hd-status-row">
							<select name="status" class="mk-hd-input">
								{foreach from=['Open','In Progress','Resolved','Closed'] item=S}
									<option value="{$S}" {if $TICKET.status eq $S}selected="selected"{/if}>{$S}</option>
								{/foreach}
							</select>
							<button type="submit" class="mk-hd-btn mk-hd-btn--ghost mk-hd-btn--sm">Update Status</button>
						</div>
					</form>

					<form method="post" action="index.php" class="mk-hd-time-form">
						<input type="hidden" name="module" value="HelpDesk" />
						<input type="hidden" name="app" value="SUPPORT" />
						<input type="hidden" name="action" value="SaveTicket" />
						<input type="hidden" name="mode" value="addTimeLog" />
						<input type="hidden" name="ticket_id" value="{$TICKET.id}" />
						<label class="mk-hd-field-label">Minutes</label>
						<input type="number" name="minutes_spent" min="1" class="mk-hd-input" value="" placeholder="0" />
						<label class="mk-hd-field-label">Note</label>
						<textarea name="note" class="mk-hd-input mk-hd-textarea" rows="3" placeholder="Add a note about time spent"></textarea>
						<div class="mk-hd-time-form__actions">
							<button type="submit" class="mk-hd-btn mk-hd-btn--primary mk-hd-btn--pill">+ Add Time</button>
						</div>
					</form>
				</div>
			</section>
		</aside>
	</div>
{/strip}
