{strip}

{assign var=RULE_ID value=$RULE_ID}
{assign var=R value=$RULE}

<div class="support-rules-wrapper container-fluid">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="support-rules-header">
                <div class="left">
                    <div class="title">
                        {if $RULE_ID gt 0}
                            Edit Support Rule
                        {else}
                            New Support Rule
                        {/if}
                    </div>
                    <div class="subtitle">
                        Configure SLA times per support level. Times are stored in minutes internally.
                    </div>
                </div>
                <div class="right">
                    <a href="index.php?module=HelpDesk&amp;view=Rules" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to Rules
                    </a>
                </div>
            </div>

            <div class="panel panel-default support-rules-panel">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        {if $RULE_ID gt 0}
                            Rule #{$RULE_ID}
                        {else}
                            Create Rule
                        {/if}
                    </h4>
                </div>
                <div class="panel-body">
                    <form method="post" action="index.php" class="form-horizontal">
                        <input type="hidden" name="module" value="HelpDesk" />
                        <input type="hidden" name="action" value="SaveRule" />
                        <input type="hidden" name="rule_id" value="{$RULE_ID}" />

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rule Name</label>
                            <div class="col-sm-9">
                                <input type="text" name="rule_name" class="form-control input-sm"
                                       value="{$R.rule_name|default:''|escape}" required="required" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rule Type</label>
                            <div class="col-sm-9">
                                <select name="rule_type" class="form-control input-sm" required="required">
                                    <option value="">-- Select --</option>
                                    {foreach from=$RULE_TYPES key=TYPE item=LABEL}
                                        <option value="{$TYPE}"
                                            {if $R.rule_type eq $TYPE}selected="selected"{/if}>
                                            {$LABEL}
                                        </option>
                                    {/foreach}
                                </select>
                                <p class="help-block small text-muted">
                                    First Response &rarr; thời gian phản hồi ban đầu.<br />
                                    Customer Update &rarr; thời gian phải cập nhật cho khách.<br />
                                    Project Progress Update &rarr; nhắc cập nhật tiến độ dự án.<br />
                                    Meeting Summary &rarr; hạn chót gửi biên bản họp.
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Description</label>
                            <div class="col-sm-9">
                                <textarea name="description" class="form-control input-sm" rows="3">{$R.description|default:''|escape}</textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Active</label>
                            <div class="col-sm-9">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="is_active" value="1"
                                           {if !isset($R.is_active) || $R.is_active eq 1}checked="checked"{/if} />
                                    This rule is active
                                </label>
                            </div>
                        </div>

                        <hr />

                        {*
                         * SLA per support level – values in minutes.
                         * We display helper inputs as "value + unit" but convert to minutes on save.
                         *}

                        {assign var=L1 value=$R.level_1_time_minutes|default:''}
                        {assign var=L2 value=$R.level_2_time_minutes|default:''}
                        {assign var=L3 value=$R.level_3_time_minutes|default:''}

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 1 (VIP)</label>
                            <div class="col-sm-9">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <input type="number" name="level_1_value" min="0" class="form-control input-sm"
                                               value="{$L1}" placeholder="Minutes" />
                                    </div>
                                    <div class="col-sm-6">
                                        <select name="level_1_unit" class="form-control input-sm">
                                            <option value="minutes">Minutes</option>
                                            <option value="hours">Hours</option>
                                            <option value="days">Days</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 2 (Standard)</label>
                            <div class="col-sm-9">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <input type="number" name="level_2_value" min="0" class="form-control input-sm"
                                               value="{$L2}" placeholder="Minutes" />
                                    </div>
                                    <div class="col-sm-6">
                                        <select name="level_2_unit" class="form-control input-sm">
                                            <option value="minutes">Minutes</option>
                                            <option value="hours">Hours</option>
                                            <option value="days">Days</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 3 (Basic)</label>
                            <div class="col-sm-9">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <input type="number" name="level_3_value" min="0" class="form-control input-sm"
                                               value="{$L3}" placeholder="Minutes" />
                                    </div>
                                    <div class="col-sm-6">
                                        <select name="level_3_unit" class="form-control input-sm">
                                            <option value="minutes">Minutes</option>
                                            <option value="hours">Hours</option>
                                            <option value="days">Days</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-12 text-right">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa fa-save"></i> Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{/strip}

{strip}

<div class="support-rule-edit-wrapper container-fluid">
    <div class="row">
        <div class="col-md-8 col-sm-9">
            <div class="panel panel-default support-rule-edit-panel">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        {if $RULE.id}Edit Rule: {$RULE.rule_name|escape}{else}New Support Rule{/if}
                    </h4>
                </div>
                <div class="panel-body">
                    <form class="form-horizontal" method="post" action="index.php">
                        <input type="hidden" name="module" value="HelpDesk" />
                        <input type="hidden" name="action" value="SaveRule" />
                        <input type="hidden" name="rule_id" value="{$RULE.id|default:''}" />

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rule Name</label>
                            <div class="col-sm-9">
                                <input type="text" name="rule_name" class="form-control input-sm"
                                       value="{$RULE.rule_name|default:''|escape}" required="required" />
                                <p class="help-block small text-muted">
                                    Ví dụ: "First Response for Tickets" hoặc "Customer Update Frequency".
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rule Type</label>
                            <div class="col-sm-9">
                                <select name="rule_type" class="form-control input-sm" required="required">
                                    <option value="">-- Select --</option>
                                    {foreach from=$RULE_TYPES key=K item=LABEL}
                                        <option value="{$K}"
                                            {if $RULE.rule_type eq $K}selected="selected"{/if}>{$LABEL}</option>
                                    {/foreach}
                                </select>
                                <p class="help-block small text-muted">
                                    Xác định loại SLA: phản hồi đầu tiên, cập nhật khách hàng, cập nhật tiến độ dự án, hay tổng kết meeting.
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Description</label>
                            <div class="col-sm-9">
                                <textarea name="description" class="form-control input-sm" rows="3">{$RULE.description|default:''|escape}</textarea>
                            </div>
                        </div>

                        <hr />

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 1 (VIP)</label>
                            <div class="col-sm-4">
                                <input type="number" min="1" name="level_1_time_minutes"
                                       class="form-control input-sm"
                                       value="{$RULE.level_1_time_minutes|default:''}" />
                            </div>
                            <div class="col-sm-5">
                                <p class="help-block small text-muted">
                                    SLA thời gian cho khách Level 1 (VIP), tính bằng phút. Để trống nếu không áp dụng.
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 2 (Standard)</label>
                            <div class="col-sm-4">
                                <input type="number" min="1" name="level_2_time_minutes"
                                       class="form-control input-sm"
                                       value="{$RULE.level_2_time_minutes|default:''}" />
                            </div>
                            <div class="col-sm-5">
                                <p class="help-block small text-muted">
                                    SLA thời gian cho khách Level 2 (tiêu chuẩn).
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Level 3 (Basic)</label>
                            <div class="col-sm-4">
                                <input type="number" min="1" name="level_3_time_minutes"
                                       class="form-control input-sm"
                                       value="{$RULE.level_3_time_minutes|default:''}" />
                            </div>
                            <div class="col-sm-5">
                                <p class="help-block small text-muted">
                                    SLA thời gian cho khách Level 3 (cơ bản).
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-9 col-sm-offset-3 text-right">
                                <a href="index.php?module=HelpDesk&amp;view=Rules" class="btn btn-default btn-sm">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Save Rule
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-3">
            <div class="panel panel-default support-rule-help-panel">
                <div class="panel-heading">
                    <h5 class="panel-title">About this rule</h5>
                </div>
                <div class="panel-body">
                    <p class="small text-muted">
                        Mỗi Rule định nghĩa deadline SLA khác nhau theo <strong>support_level</strong> của khách hàng:
                    </p>
                    <ul class="small">
                        <li><strong>Level 1</strong>: VIP support – thời gian phản hồi / cập nhật nhanh nhất.</li>
                        <li><strong>Level 2</strong>: Standard support – thời gian trung bình.</li>
                        <li><strong>Level 3</strong>: Basic support – thời gian dài hơn.</li>
                    </ul>
                    <p class="small text-muted">
                        Khi Ticket được tạo, hệ thống sẽ đọc <strong>support_level</strong> từ khách hàng
                        và tính <strong>deadline</strong> dựa trên Rule này.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{/strip}

