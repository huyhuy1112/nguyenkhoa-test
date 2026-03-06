{strip}

<div class="tickets-modern-wrapper container-fluid">
    <div class="row">
        <!-- Sidebar filter -->
        <div class="col-md-3 col-sm-4 tickets-filter-sidebar">
            <div class="panel panel-default tickets-filter-card">
                <div class="panel-heading">
                    <h5 class="panel-title">Filters</h5>
                </div>
                <div class="panel-body">
                    <form method="get" action="" id="TicketsFilterForm">
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

        <!-- Main content -->
        <div class="col-md-9 col-sm-8 tickets-main-area">
            <!-- Stats cards -->
            <div class="row tickets-stats-row">
                <div class="col-sm-4">
                    <div class="tickets-stat-card tickets-stat-open">
                        <div class="label">Open</div>
                        <div class="value">{$TICKET_STATS.total_open}</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="tickets-stat-card tickets-stat-overdue">
                        <div class="label">Overdue</div>
                        <div class="value">{$TICKET_STATS.total_overdue}</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="tickets-stat-card tickets-stat-priority">
                        <div class="label">By Priority</div>
                        <div class="value">
                            <span class="prio prio-critical">C: {$TICKET_STATS.by_priority.Critical}</span>
                            <span class="prio prio-high">H: {$TICKET_STATS.by_priority.High}</span>
                            <span class="prio prio-medium">M: {$TICKET_STATS.by_priority.Medium}</span>
                            <span class="prio prio-low">L: {$TICKET_STATS.by_priority.Low}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket table -->
            <div class="panel panel-default tickets-table-panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h4 class="panel-title">Tickets</h4>
                    </div>
                    <div class="pull-right">
                        <a href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT" class="btn btn-default btn-sm" style="margin-right:6px;">
                            <span class="fa fa-tasks"></span> Activities
                        </a>
                        <a href="index.php?module=HelpDesk&amp;view=Rules" class="btn btn-default btn-sm" style="margin-right:6px;">
                            <span class="fa fa-cog"></span> Rules
                        </a>
                        <a href="index.php?module=HelpDesk&amp;view=Edit" class="btn btn-success btn-sm">
                            <span class="fa fa-plus"></span> New Ticket
                        </a>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-body tickets-table-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover tickets-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Customer</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>SLA</th>
                                    <th>Assigned</th>
                                </tr>
                            </thead>
                            <tbody>
                                {if $TICKETS|@count gt 0}
                                    {foreach from=$TICKETS item=T}
                                        <tr>
                                            <td>
                                                <a href="index.php?module=HelpDesk&amp;view=TicketDetail&amp;record={$T.id}" class="ticket-code-link">
                                                    {$T.ticket_code}
                                                </a>
                                            </td>
                                            <td>{$T.customer_name|escape}</td>
                                            <td>{$T.subject|escape}</td>
                                            <td>
                                                <span class="badge badge-priority badge-priority-{$T.priority|lower}">{$T.priority}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-status badge-status-{$T.status|replace:' ':'_'|lower}">{$T.status}</span>
                                            </td>
                                            <td>
                                                {if $T.sla_countdown !== null}
                                                    {if $T.sla_countdown lt 0}
                                                        <span class="sla-badge sla-overdue">Overdue</span>
                                                    {else}
                                                        <span class="sla-badge">
                                                            {assign var=mins value=($T.sla_countdown/60)|ceil}
                                                            {$mins} min
                                                        </span>
                                                    {/if}
                                                {else}
                                                    <span class="text-muted">–</span>
                                                {/if}
                                            </td>
                                            <td>
                                                {if $T.assigned_users}
                                                    <span class="assigned-users">{$T.assigned_users|escape}</span>
                                                {else}
                                                    <span class="text-muted">Unassigned</span>
                                                {/if}
                                            </td>
                                        </tr>
                                    {/foreach}
                                {else}
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No tickets found.
                                        </td>
                                    </tr>
                                {/if}
                            </tbody>
                        </table>
                    </div>

                    <!-- Simple pagination -->
                    {if $PAGE_COUNT gt 1}
                        <div class="tickets-pagination text-right">
                            <ul class="pagination pagination-sm" style="margin: 5px 0 0;">
                                {section name=page start=1 loop=$PAGE_COUNT+1}
                                    {assign var=p value=$smarty.section.page.index}
                                    <li class="{if $p eq $CURRENT_PAGE}active{/if}">
                                        <a href="index.php?module=HelpDesk&amp;view=List&amp;page={$p}&amp;status={$FILTER_STATUS|escape}&amp;priority={$FILTER_PRIORITY|escape}&amp;search={$FILTER_SEARCH|escape}">
                                            {$p}
                                        </a>
                                    </li>
                                {/section}
                            </ul>
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>

{/strip}

