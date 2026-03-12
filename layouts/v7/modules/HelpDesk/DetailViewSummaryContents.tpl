{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is: vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{strip}

<div class="tickets-detail-wrapper container-fluid">
    <div class="row">
        <!-- Main content -->
        <div class="col-md-8 col-sm-7 tickets-detail-main">
            <!-- Header -->
            <div class="tickets-detail-header">
                <div class="left-block">
                    <div class="ticket-code">{$TICKET.ticket_code}</div>
                    <div class="ticket-subject">{$TICKET.subject|escape}</div>
                    <div class="ticket-meta">
                        <span class="meta-item">
                            <span class="label">Customer:</span>
                            <span class="value">{$CUSTOMER_NAME|default:'-'}</span>
                        </span>
                        <span class="meta-item">
                            <span class="label">Project:</span>
                            <span class="value">{$PROJECT_NAME|default:'-'}</span>
                        </span>
                        <span class="meta-item">
                            <span class="label">Created:</span>
                            <span class="value">{$TICKET.created_at}</span>
                        </span>
                    </div>
                </div>
                <div class="right-block">
                    <a class="btn btn-primary btn-sm" style="margin-right:8px;"
                       href="index.php?module=Activities&view=List&app=SUPPORT">
                        Activities
                    </a>
                    {if !empty($DETAILVIEWBASIC_LINKS)}
                        {foreach from=$DETAILVIEWBASIC_LINKS item=L}
                            <a class="btn btn-default btn-sm" style="margin-right:8px;"
                               href="{$L.url}">
                                {$L.label|escape}
                            </a>
                        {/foreach}
                    {/if}
                    <span class="badge badge-priority badge-priority-{$TICKET.priority|lower}">
                        {$TICKET.priority}
                    </span>
                    <span class="badge badge-status badge-status-{$TICKET.status|replace:' ':'_'|lower}">
                        {$TICKET.status}
                    </span>
                    {if $TICKET.sla_countdown !== null}
                        {if $TICKET.sla_countdown lt 0}
                            <span class="sla-chip sla-overdue">SLA Overdue</span>
                        {else}
                            {assign var=mins value=($TICKET.sla_countdown/60)|ceil}
                            <span class="sla-chip">SLA: {$mins} min</span>
                        {/if}
                    {/if}
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs tickets-detail-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#ticket-overview" aria-controls="ticket-overview" role="tab" data-toggle="tab">Overview</a>
                </li>
                <li role="presentation">
                    <a href="#ticket-activity" aria-controls="ticket-activity" role="tab" data-toggle="tab">Activity</a>
                </li>
                <li role="presentation">
                    <a href="#ticket-files" aria-controls="ticket-files" role="tab" data-toggle="tab">Files</a>
                </li>
                <li role="presentation">
                    <a href="#ticket-time" aria-controls="ticket-time" role="tab" data-toggle="tab">Time Logs</a>
                </li>
            </ul>

            <div class="tab-content tickets-detail-tab-content">
                <!-- Overview -->
                <div role="tabpanel" class="tab-pane active" id="ticket-overview">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h5 class="panel-title">Description</h5>
                        </div>
                        <div class="panel-body">
                            {if $TICKET.description}
                                {$TICKET.description|nl2br}
                            {else}
                                <span class="text-muted">No description.</span>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Activity timeline -->
                <div role="tabpanel" class="tab-pane" id="ticket-activity">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h5 class="panel-title">Activity Timeline</h5>
                        </div>
                        <div class="panel-body">
                            {if $ACTIVITY_LOGS|@count gt 0}
                                <ul class="tickets-activity-timeline list-unstyled">
                                    {foreach from=$ACTIVITY_LOGS item=A}
                                        <li class="timeline-item">
                                            <div class="time">{$A.changed_at}</div>
                                            <div class="main">
                                                <span class="user">{$A.user_name}</span>
                                                <span class="action">
                                                    {$A.action_label|default:$A.action_type}
                                                </span>
                                                {if $A.action_details}
                                                    <div class="values">
                                                        {$A.action_details|escape}
                                                    </div>
                                                {/if}
                                            </div>
                                        </li>
                                    {/foreach}
                                </ul>
                            {else}
                                <span class="text-muted">No activity yet.</span>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Files -->
                <div role="tabpanel" class="tab-pane" id="ticket-files">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h5 class="panel-title">Files</h5>
                        </div>
                        <div class="panel-body">
                            {if $TICKET_FILES|@count gt 0}
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Type</th>
                                            <th>Uploaded By</th>
                                            <th>Uploaded At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach from=$TICKET_FILES item=F}
                                            <tr>
                                                <td>
                                                    {assign var=ext value=$F.file_type|lower}
                                                    {assign var=basename value=$F.file_path|basename}

                                                    {if in_array($ext, ['png','jpg','jpeg','gif','webp'])}
                                                        <a href="{$F.file_path|escape}" target="_blank">
                                                            <img src="{$F.file_path|escape}" alt="{$basename|escape}"
                                                                 style="max-width: 160px; max-height: 90px; border-radius: 6px; box-shadow: 0 4px 12px rgba(15,23,42,0.18);" />
                                                        </a>
                                                        <div style="font-size: 11px; margin-top: 4px;">
                                                            {$basename|escape}
                                                            &nbsp;
                                                            <a href="{$F.file_path|escape}" download="{$basename|escape}" style="font-size: 11px;">
                                                                <i class="fa fa-download"></i>
                                                            </a>
                                                        </div>
                                                    {else}
                                                        <a href="{$F.file_path|escape}" download="{$basename|escape}">
                                                            {$basename|escape}
                                                        </a>
                                                    {/if}
                                                </td>
                                                <td>{$F.file_type|default:'-'}</td>
                                                <td>{$F.uploaded_by_name|default:$F.uploaded_by}</td>
                                                <td>{$F.uploaded_at}</td>
                                            </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            {else}
                                <span class="text-muted">No files attached.</span>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Time logs -->
                <div role="tabpanel" class="tab-pane" id="ticket-time">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h5 class="panel-title">Time Logs</h5>
                        </div>
                        <div class="panel-body">
                            {if $TIME_LOGS|@count gt 0}
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Minutes</th>
                                            <th>Note</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach from=$TIME_LOGS item=TL}
                                            <tr>
                                                <td>{$TL.user_name}</td>
                                                <td>{$TL.minutes_spent}</td>
                                                <td>{$TL.note|escape}</td>
                                                <td>{$TL.created_at}</td>
                                            </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            {else}
                                <span class="text-muted">No time logs.</span>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel panel-default" style="margin-top:12px;">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h5 class="panel-title">Related Activities</h5>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-xs btn-primary"
                           href="index.php?module=Activities&view=Edit&app=SUPPORT&ticketid={$TICKET.id}&from_ticket=1">
                            <span class="fa fa-plus"></span> Create Activity
                        </a>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-body">
                    {if $RELATED_ACTIVITIES|@count gt 0}
                        <table class="table table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Content</th>
                                    <th>Assigned</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$RELATED_ACTIVITIES item=RA}
                                    <tr>
                                        <td>{$RA.activity_type|default:'-'|escape}</td>
                                        <td>{$RA.content|default:'-'|escape}</td>
                                        <td>{$RA.assigned_name|default:'-'|escape}</td>
                                        <td>{$RA.activity_date|default:'-'|escape}</td>
                                        <td style="min-width: 150px;">
                                            <form method="post" action="index.php" style="display:flex; gap:6px;">
                                                <input type="hidden" name="module" value="Activities" />
                                                <input type="hidden" name="action" value="Save" />
                                                <input type="hidden" name="mode" value="changeStatus" />
                                                <input type="hidden" name="record" value="{$RA.activityid}" />
                                                <input type="hidden" name="ticketid" value="{$TICKET.id}" />
                                                <select name="status" class="form-control input-sm">
                                                    {foreach from=$ACTIVITY_STATUS_OPTIONS item=AS}
                                                        <option value="{$AS}" {if $RA.status eq $AS}selected="selected"{/if}>{$AS}</option>
                                                    {/foreach}
                                                </select>
                                                <button type="submit" class="btn btn-default btn-sm">Save</button>
                                            </form>
                                        </td>
                                        <td>
                                            <a class="btn btn-xs btn-default" href="index.php?module=Activities&view=Detail&record={$RA.activityid}&app=SUPPORT">View</a>
                                            <a class="btn btn-xs btn-default" href="index.php?module=Activities&view=Edit&record={$RA.activityid}&app=SUPPORT&from_ticket=1">Edit</a>
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    {else}
                        <span class="text-muted">No activities linked to this ticket.</span>
                    {/if}
                </div>
            </div>
        </div>

        <!-- Right panel: assignments, status, SLA -->
        <div class="col-md-4 col-sm-5 tickets-detail-side">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">Assignments</h5>
                </div>
                <div class="panel-body">
                    {if $ASSIGNED_USERS|@count gt 0}
                        <ul class="list-unstyled">
                            {foreach from=$ASSIGNED_USERS item=U}
                                <li>{$U.first_name} {$U.last_name}</li>
                            {/foreach}
                        </ul>
                    {else}
                        <span class="text-muted">No assignees.</span>
                    {/if}
                    <hr />
                    <form method="post" action="index.php" class="form-horizontal">
                        <input type="hidden" name="module" value="HelpDesk" />
                        <input type="hidden" name="action" value="SaveTicket" />
                        <input type="hidden" name="mode" value="assignUsers" />
                        <input type="hidden" name="ticket_id" value="{$TICKET.id}" />
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Assigned</label>
                            <div class="col-sm-8">
                                <select name="assigned_users_ids[]" class="form-control input-sm" multiple="multiple">
                                    {foreach from=$ALL_USERS item=U}
                                        <option value="{$U.id}"
                                            {if in_array($U.id, $ASSIGNED_USER_IDS)}selected="selected"{/if}>
                                            {$U.first_name} {$U.last_name} (ID: {$U.id})
                                        </option>
                                    {/foreach}
                                </select>
                                <p class="help-block small text-muted">
                                    Giữ Ctrl / Cmd để chọn nhiều người xử lý.
                                </p>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-12 text-right">
                                <button type="submit" class="btn btn-primary btn-sm">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">SLA</h5>
                </div>
                <div class="panel-body">
                    <p><strong>Legacy SLA Due:</strong> {$TICKET.sla_due_at|default:'-'}</p>
                    <p><strong>Overdue:</strong> {if $TICKET.is_overdue}Yes{else}No{/if}</p>
                    <p><strong>Closed At:</strong> {$TICKET.closed_at|default:'-'}</p>

                    <hr />

                    {if $SLA_ENTRIES|@count gt 0}
                        <p><strong>Support Rules SLA:</strong></p>
                        <ul class="list-unstyled">
                            {foreach from=$SLA_ENTRIES item=S}
                                <li style="margin-bottom:4px;">
                                    <span class="label label-default">
                                        {$S.rule_name|escape}
                                    </span>
                                    <br />
                                    Deadline: {$S.deadline_at}
                                    {if $S.status eq 'pending'}
                                        {if $S.remaining_seconds !== null}
                                            {assign var=mins value=($S.remaining_seconds/60)|ceil}
                                            <span class="sla-badge">
                                                Remaining: {$mins} min
                                            </span>
                                        {/if}
                                    {elseif $S.status eq 'overdue'}
                                        <span class="sla-badge sla-overdue">Overdue</span>
                                    {elseif $S.status eq 'completed'}
                                        <span class="sla-badge" style="background:#dcfce7;color:#166534;">
                                            Completed
                                        </span>
                                    {/if}
                                </li>
                            {/foreach}
                        </ul>
                    {else}
                        <p class="text-muted small">No SLA rules applied yet.</p>
                    {/if}
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">Status &amp; Time</h5>
                </div>
                <div class="panel-body">
                    <form method="post" action="index.php" class="form-horizontal">
                        <input type="hidden" name="module" value="HelpDesk" />
                        <input type="hidden" name="action" value="SaveTicket" />
                        <input type="hidden" name="mode" value="changeStatus" />
                        <input type="hidden" name="ticket_id" value="{$TICKET.id}" />
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Status</label>
                            <div class="col-sm-8">
                                <select name="status" class="form-control input-sm">
                                    {foreach from=['Open','In Progress','Resolved','Closed'] item=S}
                                        <option value="{$S}" {if $TICKET.status eq $S}selected="selected"{/if}>{$S}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-12 text-right">
                                <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
                            </div>
                        </div>
                    </form>

                    <hr />

                    <form method="post" action="index.php" class="form-horizontal">
                        <input type="hidden" name="module" value="HelpDesk" />
                        <input type="hidden" name="action" value="SaveTicket" />
                        <input type="hidden" name="mode" value="addTimeLog" />
                        <input type="hidden" name="ticket_id" value="{$TICKET.id}" />
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Minutes</label>
                            <div class="col-sm-8">
                                <input type="number" name="minutes_spent" min="1" class="form-control input-sm" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Note</label>
                            <div class="col-sm-8">
                                <input type="text" name="note" class="form-control input-sm" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-12 text-right">
                                <button type="submit" class="btn btn-default btn-sm">Add Time</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{/strip}