{strip}

<div class="support-rules-wrapper container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="support-rules-header">
                <div class="left">
                    <div class="title">Support Rules Engine</div>
                    <div class="subtitle">
                        Define SLA policies for First Response, Customer Updates, Project Progress and Meeting Summary based on customer support level (1–3).
                    </div>
                </div>
                <div class="right">
                    <a href="index.php?module=HelpDesk&amp;view=RuleEdit" class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> New Rule
                    </a>
                </div>
            </div>

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
                                    <th>Level 1 SLA</th>
                                    <th>Level 2 SLA</th>
                                    <th>Level 3 SLA</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
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
                                                <span class="badge badge-rule-type">{$R.rule_type}</span>
                                            </td>
                                            <td>
                                                {if $R.level_1_time_minutes}
                                                    {$R.level_1_time_minutes} min
                                                {else}
                                                    <span class="text-muted">–</span>
                                                {/if}
                                            </td>
                                            <td>
                                                {if $R.level_2_time_minutes}
                                                    {$R.level_2_time_minutes} min
                                                {else}
                                                    <span class="text-muted">–</span>
                                                {/if}
                                            </td>
                                            <td>
                                                {if $R.level_3_time_minutes}
                                                    {$R.level_3_time_minutes} min
                                                {else}
                                                    <span class="text-muted">–</span>
                                                {/if}
                                            </td>
                                            <td>
                                                {if $R.is_active}
                                                    <span class="label label-success">Active</span>
                                                {else}
                                                    <span class="label label-default">Disabled</span>
                                                {/if}
                                            </td>
                                            <td class="text-right">
                                                <a href="index.php?module=HelpDesk&amp;view=RuleEdit&amp;rule_id={$R.id}" class="btn btn-xs btn-primary">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    {/foreach}
                                {else}
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No rules configured yet. Click "New Rule" to create the first one.
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

{/strip}

