{strip}

<div class="helpdesk-rules-page">

<div class="support-rules-wrapper container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="support-rules-header-card">
                <div class="left">
                    <div class="title">Support Rules Engine</div>
                    <div class="subtitle">
                        Configure SLA times for each customer support level (1 – VIP, 2 – Standard, 3 – Basic).
                    </div>
                </div>
                <div class="right">
                    <a href="index.php?module=HelpDesk&amp;view=RuleEdit" class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> New Rule
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default support-rules-panel">
                <div class="panel-heading">
                    <h4 class="panel-title">Rules</h4>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover support-rules-table">
                            <thead>
                                <tr>
                                    <th>Rule Name</th>
                                    <th>Rule Type</th>
                                    <th>Level 1 (min)</th>
                                    <th>Level 2 (min)</th>
                                    <th>Level 3 (min)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {if $SUPPORT_RULES|@count gt 0}
                                    {foreach from=$SUPPORT_RULES item=R}
                                        <tr>
                                            <td>
                                                <a href="index.php?module=HelpDesk&amp;view=RuleEdit&amp;rule_id={$R.id}">
                                                    {$R.rule_name|escape}
                                                </a>
                                            </td>
                                            <td>
                                                {if $R.rule_type eq 'first_response'}
                                                    First Response
                                                {elseif $R.rule_type eq 'customer_update'}
                                                    Customer Update
                                                {elseif $R.rule_type eq 'project_progress_update'}
                                                    Project Progress Update
                                                {elseif $R.rule_type eq 'meeting_summary'}
                                                    Meeting Summary
                                                {else}
                                                    {$R.rule_type|escape}
                                                {/if}
                                            </td>
                                            <td>{$R.level_1_time_minutes|default:'-'}</td>
                                            <td>{$R.level_2_time_minutes|default:'-'}</td>
                                            <td>{$R.level_3_time_minutes|default:'-'}</td>
                                            <td>
                                                {if $R.is_active}
                                                    <span class="badge badge-status-active">Active</span>
                                                {else}
                                                    <span class="badge badge-status-inactive">Disabled</span>
                                                {/if}
                                            </td>
                                            <td>
                                                <a href="index.php?module=HelpDesk&amp;view=RuleEdit&amp;rule_id={$R.id}"
                                                   class="btn btn-xs btn-default">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </a>
                                                {if $R.is_active}
                                                    <a href="index.php?module=HelpDesk&amp;view=Rules&amp;mode=disable&amp;rule_id={$R.id}"
                                                       class="btn btn-xs btn-warning">Disable</a>
                                                {else}
                                                    <a href="index.php?module=HelpDesk&amp;view=Rules&amp;mode=enable&amp;rule_id={$R.id}"
                                                       class="btn btn-xs btn-success">Enable</a>
                                                {/if}
                                            </td>
                                        </tr>
                                    {/foreach}
                                {else}
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No rules defined yet. Click "New Rule" to create one.
                                        </td>
                                    </tr>
                                {/if}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

{/strip}

