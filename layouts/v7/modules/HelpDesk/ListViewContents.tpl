{* HelpDesk Tickets list — modern SUPPORT UI (design-aligned) *}
{strip}
{assign var=MK_HD_IS_SUPPORT value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT') || !isset($smarty.get.app) || $smarty.get.app eq ''}
	{assign var=MK_HD_IS_SUPPORT value=true}
{/if}

{if $MK_HD_IS_SUPPORT}
<div class="mk-hd-page">
	<div class="mk-hd-suite-card">
		<div class="mk-hd-hero">
			<aside class="mk-hd-filter-sidebar" aria-label="Ticket filters">
				<form method="get" action="index.php" class="mk-hd-filter-form" id="TicketsFilterForm">
					<input type="hidden" name="module" value="HelpDesk" />
					<input type="hidden" name="view" value="List" />
					<input type="hidden" name="app" value="SUPPORT" />
					<label class="mk-hd-field">
						<span class="mk-hd-field__label">Status</span>
						<select name="status" class="mk-hd-input mk-hd-select">
							<option value="">All</option>
							{foreach from=['Open','In Progress','Resolved','Closed'] item=STATUS}
								<option value="{$STATUS}" {if $FILTER_STATUS eq $STATUS}selected="selected"{/if}>{$STATUS}</option>
							{/foreach}
						</select>
					</label>
					<label class="mk-hd-field">
						<span class="mk-hd-field__label">Priority</span>
						<select name="priority" class="mk-hd-input mk-hd-select">
							<option value="">All</option>
							{foreach from=['Critical','High','Medium','Low'] item=PRIO}
								<option value="{$PRIO}" {if $FILTER_PRIORITY eq $PRIO}selected="selected"{/if}>{$PRIO}</option>
							{/foreach}
						</select>
					</label>
					<label class="mk-hd-field">
						<span class="mk-hd-field__label">Search</span>
						<input type="text" name="search" class="mk-hd-input" placeholder="Code or subject" value="{$FILTER_SEARCH|escape:'html'}" />
					</label>
					<button type="submit" class="mk-hd-btn mk-hd-btn--filter">
						<span class="mk-hd-btn__ic" aria-hidden="true">{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='FILTER'}</span>
						<span class="mk-hd-btn__txt">Apply Filters</span>
					</button>
				</form>
			</aside>

			<div class="mk-hd-hero__main">
				{include file="partials/TicketListHeader.tpl"|vtemplate_path:$MODULE}

				<div class="mk-hd-stats" role="group" aria-label="Ticket summary">
					<div class="mk-hd-stat-card">
						<span class="mk-hd-stat-card__label">Open</span>
						<span class="mk-hd-stat-card__value">{$TICKET_STATS.total_open}</span>
					</div>
					<div class="mk-hd-stat-card">
						<span class="mk-hd-stat-card__label">Overdue</span>
						<span class="mk-hd-stat-card__value">{$TICKET_STATS.total_overdue}</span>
					</div>
					<div class="mk-hd-stat-card mk-hd-stat-card--priority">
						<span class="mk-hd-stat-card__label">By Priority</span>
						<div class="mk-hd-stat-card__pills">
							<span class="mk-hd-prio mk-hd-prio--critical">C: {$TICKET_STATS.by_priority.Critical}</span>
							<span class="mk-hd-prio mk-hd-prio--high">H: {$TICKET_STATS.by_priority.High}</span>
							<span class="mk-hd-prio mk-hd-prio--medium">M: {$TICKET_STATS.by_priority.Medium}</span>
							<span class="mk-hd-prio mk-hd-prio--low">L: {$TICKET_STATS.by_priority.Low}</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="mk-hd-table-panel">
			<div class="mk-hd-table-toolbar">
				<div class="mk-hd-table-toolbar__left">
					<div class="mk-hd-view-toggle" role="group" aria-label="View mode">
						<button type="button" class="mk-hd-view-toggle__btn" disabled aria-pressed="false" title="Grid view">
							{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='GRID'}
						</button>
						<button type="button" class="mk-hd-view-toggle__btn is-active" aria-pressed="true" title="List view">
							{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='LIST'}
						</button>
					</div>
					<p class="mk-hd-table-toolbar__count" id="mkHdTicketCount">
						Showing <strong>{$SHOW_FROM}</strong> to <strong>{$SHOW_TO}</strong> of <strong>{$TOTAL_COUNT}</strong> ticket{if $TOTAL_COUNT ne 1}s{/if}
					</p>
				</div>
				<div class="mk-hd-table-toolbar__right">
					<a class="mk-hd-toolbar-link" href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT">
						{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='ACTIVITIES'}
						<span>Activities</span>
					</a>
					<a class="mk-hd-toolbar-link" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT">
						{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='RULES'}
						<span>Rules</span>
					</a>
				</div>
			</div>

			<div class="mk-hd-table-wrap">
				<table class="mk-hd-table" id="mkHdTicketTable">
					<colgroup>
						<col class="mk-hd-col mk-hd-col--code" />
						<col class="mk-hd-col mk-hd-col--customer" />
						<col class="mk-hd-col mk-hd-col--subject" />
						<col class="mk-hd-col mk-hd-col--priority" />
						<col class="mk-hd-col mk-hd-col--status" />
						<col class="mk-hd-col mk-hd-col--sla" />
						<col class="mk-hd-col mk-hd-col--assigned" />
					</colgroup>
					<thead>
						<tr>
							<th scope="col">Code</th>
							<th scope="col">Customer</th>
							<th scope="col">Subject</th>
							<th scope="col">Priority</th>
							<th scope="col">Status</th>
							<th scope="col">SLA</th>
							<th scope="col">Assigned To</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$TICKETS item=T}
							<tr>
								<td class="mk-hd-table__code">
									<a href="index.php?module=HelpDesk&amp;view=TicketDetail&amp;record={$T.id}&amp;app=SUPPORT" class="mk-hd-code-link">{$T.ticket_code|escape:'html'}</a>
								</td>
								<td class="mk-hd-table__customer">{$T.customer_name|escape:'html'}</td>
								<td class="mk-hd-table__subject" title="{$T.subject|escape:'html'}">{$T.subject|escape:'html'}</td>
								<td class="mk-hd-table__priority">
									<span class="mk-hd-badge mk-hd-badge--priority mk-hd-badge--priority-{$T.priority|lower}">{$T.priority}</span>
								</td>
								<td class="mk-hd-table__status">
									<span class="mk-hd-badge mk-hd-badge--status mk-hd-badge--status-{$T.status|replace:' ':'_'|lower}">{$T.status}</span>
								</td>
								<td class="mk-hd-table__sla">
									{if $T.sla_countdown !== null}
										{if $T.sla_countdown lt 0}
											<span class="mk-hd-sla mk-hd-sla--overdue">Overdue</span>
										{else}
											{assign var=mins value=($T.sla_countdown/60)|ceil}
											<span class="mk-hd-sla">{$mins} min</span>
										{/if}
									{else}
										<span class="mk-hd-muted">—</span>
									{/if}
								</td>
								<td class="mk-hd-table__assigned">
									{if $T.assigned_users}
										<span class="mk-hd-assignee">
											<span class="mk-hd-assignee__avatar" aria-hidden="true">{$T.assigned_initials|escape:'html'}</span>
											<span class="mk-hd-assignee__name" title="{$T.assigned_users|escape:'html'}">{$T.assigned_users|escape:'html'}</span>
										</span>
									{else}
										<span class="mk-hd-muted">Unassigned</span>
									{/if}
								</td>
							</tr>
						{foreachelse}
							<tr>
								<td colspan="7" class="mk-hd-table__empty">No tickets found.</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>

			{if $PAGE_COUNT gt 1}
				<nav class="mk-hd-pagination" aria-label="Tickets pagination">
					<ul class="mk-hd-pagination__list">
						{if $CURRENT_PAGE gt 1}
							<li>
								<a class="mk-hd-pagination__btn mk-hd-pagination__btn--arrow" href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT&amp;page={$CURRENT_PAGE-1}&amp;status={$FILTER_STATUS|escape:'url'}&amp;priority={$FILTER_PRIORITY|escape:'url'}&amp;search={$FILTER_SEARCH|escape:'url'}" aria-label="Previous page">&lsaquo;</a>
							</li>
						{/if}
						{section name=page start=1 loop=$PAGE_COUNT+1}
							{assign var=p value=$smarty.section.page.index}
							<li>
								<a class="mk-hd-pagination__btn{if $p eq $CURRENT_PAGE} is-active{/if}" href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT&amp;page={$p}&amp;status={$FILTER_STATUS|escape:'url'}&amp;priority={$FILTER_PRIORITY|escape:'url'}&amp;search={$FILTER_SEARCH|escape:'url'}"{if $p eq $CURRENT_PAGE} aria-current="page"{/if}>{$p}</a>
							</li>
						{/section}
						{if $CURRENT_PAGE lt $PAGE_COUNT}
							<li>
								<a class="mk-hd-pagination__btn mk-hd-pagination__btn--arrow" href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT&amp;page={$CURRENT_PAGE+1}&amp;status={$FILTER_STATUS|escape:'url'}&amp;priority={$FILTER_PRIORITY|escape:'url'}&amp;search={$FILTER_SEARCH|escape:'url'}" aria-label="Next page">&rsaquo;</a>
							</li>
						{/if}
					</ul>
				</nav>
			{/if}
		</div>
	</div>
</div>
{else}
<div class="tickets-modern-wrapper container-fluid">
    <div class="row">
        <div class="col-md-3 col-sm-4 tickets-filter-sidebar">
            <div class="panel panel-default tickets-filter-card">
                <div class="panel-heading"><h5 class="panel-title">Filters</h5></div>
                <div class="panel-body">
                    <form method="get" action="" id="TicketsFilterFormLegacy">
                        <input type="hidden" name="module" value="HelpDesk" />
                        <input type="hidden" name="view" value="List" />
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control input-sm">
                                <option value="">All</option>
                                {foreach from=['Open','In Progress','Resolved','Closed'] item=STATUS}
                                    <option value="{$STATUS}" {if $FILTER_STATUS eq $STATUS}selected="selected"{/if}>{$STATUS}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select name="priority" class="form-control input-sm">
                                <option value="">All</option>
                                {foreach from=['Critical','High','Medium','Low'] item=PRIO}
                                    <option value="{$PRIO}" {if $FILTER_PRIORITY eq $PRIO}selected="selected"{/if}>{$PRIO}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control input-sm" placeholder="Code or subject" value="{$FILTER_SEARCH|escape}" />
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm btn-block">Apply</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-9 col-sm-8 tickets-main-area">
            <div class="panel panel-default tickets-table-panel">
                <div class="panel-heading">
                    <h4 class="panel-title pull-left">Tickets</h4>
                    <a href="index.php?module=HelpDesk&amp;view=Edit" class="btn btn-success btn-sm pull-right">+ New Ticket</a>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-body">
                    <p class="text-muted">Use SUPPORT app for the modern tickets UI.</p>
                </div>
            </div>
        </div>
    </div>
</div>
{/if}
{/strip}
