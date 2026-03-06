{strip}

<div class="support-rules-wrapper container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="support-rules-header-card">
                <div class="left">
                    <div class="title">
                        {if $RULE.id}Edit Rule: {$RULE.rule_name|escape}{else}Create Support Rule{/if}
                    </div>
                    <div class="subtitle">
                        Configure SLA times (in minutes) for each customer support level.
                    </div>
                </div>
                <div class="right">
                    <a href="index.php?module=HelpDesk&amp;view=Rules" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to Rules
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default support-rules-panel">
                <div class="panel-heading">
                    <h4 class="panel-title">Rule Configuration</h4>
                </div>
                <div class="panel-body">
                    <form class="form-horizontal" method="post" action="index.php">
                        <input type="hidden" name="module" value="HelpDesk" />
                        <input type="hidden" name="action" value="SaveRule" />
                        {if $RULE.id}
                            <input type="hidden" name="id" value="{$RULE.id}" />
                        {/if}

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rule Name</label>
                            <div class="col-sm-9">
                                <input type="text" name="rule_name" class="form-control input-sm"
                                       value="{$RULE.rule_name|escape}" required="required" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rule Type</label>
                            <div class="col-sm-9">
                                <select name="rule_type" class="form-control input-sm" required="required">
                                    <option value="">-- Select --</option>
                                    {foreach from=$RULE_TYPES key=TYPE item=LABEL}
                                        <option value="{$TYPE}"
                                            {if $RULE.rule_type eq $TYPE}selected="selected"{/if}>
                                            {$LABEL}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Description</label>
                            <div class="col-sm-9">
                                <textarea name="description" class="form-control input-sm" rows="3">{$RULE.description|escape}</textarea>
                                <p class="help-block small text-muted">
                                    Example: First Response – defines how quickly support must respond to a new ticket.
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Active</label>
                            <div class="col-sm-9">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="is_active" value="1"
                                           {if !isset($RULE.is_active) || $RULE.is_active}checked="checked"{/if} />
                                    Enabled
                                </label>
                            </div>
                        </div>

                        <hr />

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 1 (VIP)</label>
                            <div class="col-sm-4">
                                <input type="number" name="level_1_time_minutes" min="1"
                                       class="form-control input-sm"
                                       value="{$RULE.level_1_time_minutes}" />
                            </div>
                            <div class="col-sm-5 help-text">
                                <span class="text-muted">Minutes – e.g. 15 for VIP first response.</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 2 (Standard)</label>
                            <div class="col-sm-4">
                                <input type="number" name="level_2_time_minutes" min="1"
                                       class="form-control input-sm"
                                       value="{$RULE.level_2_time_minutes}" />
                            </div>
                            <div class="col-sm-5 help-text">
                                <span class="text-muted">Minutes – e.g. 240 for standard response.</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 3 (Basic)</label>
                            <div class="col-sm-4">
                                <input type="number" name="level_3_time_minutes" min="1"
                                       class="form-control input-sm"
                                       value="{$RULE.level_3_time_minutes}" />
                            </div>
                            <div class="col-sm-5 help-text">
                                <span class="text-muted">Minutes – e.g. 480 for basic support.</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-12 text-right">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa fa-save"></i> Save Rule
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-default support-rules-panel info">
                <div class="panel-heading">
                    <h4 class="panel-title">Support Levels Guide</h4>
                </div>
                <div class="panel-body">
                    <p><strong>Level 1 – VIP</strong><br />Fastest response and follow-up.</p>
                    <p><strong>Level 2 – Standard</strong><br />Normal SLA for most customers.</p>
                    <p><strong>Level 3 – Basic</strong><br />Relaxed SLA for low-priority accounts.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{/strip}

{strip}

<div class="support-rules-wrapper container-fluid">
    <div class="row">
        <div class="col-md-8 col-sm-9">
            <div class="support-rules-header">
                <div class="left">
                    <h2 class="title">
                        {if $RULE.id}Edit Rule: {$RULE.rule_name|escape}{else}New Support Rule{/if}
                    </h2>
                    <p class="subtitle">
                        Cấu hình SLA theo support level (1 = VIP, 2 = Standard, 3 = Basic) cho từng loại rule.
                    </p>
                </div>
                <div class="right">
                    <a href="index.php?module=HelpDesk&amp;view=Rules" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to list
                    </a>
                </div>
            </div>

            <div class="panel panel-default support-rules-panel">
                <div class="panel-heading">
                    <h4 class="panel-title">Rule Configuration</h4>
                </div>
                <div class="panel-body">
                    <form class="form-horizontal" method="post" action="index.php">
                        <input type="hidden" name="module" value="HelpDesk" />
                        <input type="hidden" name="action" value="SaveRule" />
                        <input type="hidden" name="rule_id" value="{$RULE.id}" />

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rule Name</label>
                            <div class="col-sm-9">
                                <input type="text" name="rule_name" class="form-control input-sm"
                                       value="{$RULE.rule_name|escape}" required="required" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rule Type</label>
                            <div class="col-sm-9">
                                <select name="rule_type" class="form-control input-sm">
                                    <option value="first_response" {if $RULE.rule_type eq 'first_response'}selected="selected"{/if}>
                                        First Response
                                    </option>
                                    <option value="customer_update" {if $RULE.rule_type eq 'customer_update'}selected="selected"{/if}>
                                        Customer Update
                                    </option>
                                    <option value="project_progress_update" {if $RULE.rule_type eq 'project_progress_update'}selected="selected"{/if}>
                                        Project Progress Update
                                    </option>
                                    <option value="meeting_summary" {if $RULE.rule_type eq 'meeting_summary'}selected="selected"{/if}>
                                        Meeting Summary
                                    </option>
                                </select>
                                <p class="help-block small text-muted">
                                    Ví dụ: <strong>First Response</strong> = thời gian phải trả lời ticket lần đầu.
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Description</label>
                            <div class="col-sm-9">
                                <textarea name="description" class="form-control input-sm" rows="3">{$RULE.description|escape}</textarea>
                            </div>
                        </div>

                        <hr />

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 1 (VIP)</label>
                            <div class="col-sm-4">
                                <input type="number" name="level_1_time_minutes"
                                       class="form-control input-sm"
                                       min="0"
                                       value="{$RULE.level_1_time_minutes}" />
                            </div>
                            <label class="col-sm-2 control-label text-left">minutes</label>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 2 (Standard)</label>
                            <div class="col-sm-4">
                                <input type="number" name="level_2_time_minutes"
                                       class="form-control input-sm"
                                       min="0"
                                       value="{$RULE.level_2_time_minutes}" />
                            </div>
                            <label class="col-sm-2 control-label text-left">minutes</label>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 3 (Basic)</label>
                            <div class="col-sm-4">
                                <input type="number" name="level_3_time_minutes"
                                       class="form-control input-sm"
                                       min="0"
                                       value="{$RULE.level_3_time_minutes}" />
                            </div>
                            <label class="col-sm-2 control-label text-left">minutes</label>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-9">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="is_active" value="1"
                                               {if $RULE.is_active}checked="checked"{/if} />
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-9">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <a href="index.php?module=HelpDesk&amp;view=Rules" class="btn btn-default btn-sm">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{/strip}

