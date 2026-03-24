{strip}
<div class="container-fluid" style="padding:18px;">
    <div class="panel panel-default" style="border-radius:12px; box-shadow:0 12px 30px rgba(15,23,42,0.12);">
        <div class="panel-heading">
            <h4 class="panel-title">
                {if $RECORD.activityid}Edit Activity #{ $RECORD.activityid }{else}New Activity{/if}
            </h4>
        </div>
        <div class="panel-body">
            <form method="post" action="index.php">
                <input type="hidden" name="module" value="Activities" />
                <input type="hidden" name="action" value="Save" />
                <input type="hidden" name="record" value="{$RECORD.activityid}" />
                <input type="hidden" name="app" value="SUPPORT" />
                <input type="hidden" name="ticket_id" value="{$RECORD.ticket_id|default:''}" />
                <input type="hidden" name="from_ticket" value="{$FROM_TICKET|default:0}" />

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Activity Type</label>
                            <select name="activity_type" class="form-control input-sm">
                                {foreach from=$TYPE_OPTIONS item=opt}
                                    <option value="{$opt|escape}" {if $RECORD.activity_type eq $opt}selected{/if}>{$opt|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control input-sm" required>
                                {foreach from=$STATUS_OPTIONS item=opt}
                                    <option value="{$opt|escape}" {if $RECORD.status eq $opt}selected{/if}>{$opt|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" class="form-control" rows="3" required>{$RECORD.content|escape}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Organization</label>
                            <select name="organizationid" class="form-control input-sm">
                                <option value="">-- None --</option>
                                {foreach from=$ACCOUNTS item=acc}
                                    <option value="{$acc.accountid}" {if $RECORD.organizationid eq $acc.accountid}selected{/if}>
                                        {$acc.accountname|escape}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Project</label>
                            <select name="projectid" class="form-control input-sm">
                                <option value="">-- None --</option>
                                {foreach from=$PROJECTS item=pr}
                                    <option value="{$pr.projectid}" {if $RECORD.projectid eq $pr.projectid}selected{/if}>
                                        {$pr.projectname|escape}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Activity Date</label>
                            <input type="datetime-local" name="activity_date" class="form-control input-sm"
                                   value="{$RECORD.activity_date|replace:' ':'T'|escape}" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Assigned To</label>
                            <select name="assigned_user_id" class="form-control input-sm">
                                {foreach from=$USERS item=u}
                                    <option value="{$u.id}" {if $RECORD.assigned_user_id eq $u.id}selected{/if}>
                                        {$u.first_name|escape} {$u.last_name|escape}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Note Before</label>
                            <textarea name="note_before" class="form-control" rows="2">{$RECORD.note_before|escape}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Note After</label>
                            <textarea name="note_after" class="form-control" rows="2">{$RECORD.note_after|escape}</textarea>
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    {if $FROM_TICKET gt 0 && $RECORD.ticket_id gt 0}
                        <a href="index.php?module=HelpDesk&view=TicketDetail&record={$RECORD.ticket_id}&app=SUPPORT" class="btn btn-default">Cancel</a>
                    {else}
                        <a href="index.php?module=Activities&view=List&app=SUPPORT" class="btn btn-default">Cancel</a>
                    {/if}
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
{/strip}
