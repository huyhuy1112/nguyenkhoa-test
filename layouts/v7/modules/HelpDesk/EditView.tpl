{strip}

<div class="tickets-edit-wrapper container-fluid">
    <div class="row">
        <div class="col-md-8 col-sm-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        {if $TICKET}
                            Edit Ticket {$TICKET.ticket_code}
                        {else}
                            New Ticket
                        {/if}
                    </h4>
                </div>
                <div class="panel-body">
                    <form method="post" action="index.php" enctype="multipart/form-data">
                        <input type="hidden" name="module" value="HelpDesk" />
                        <input type="hidden" name="action" value="SaveTicket" />
                        {if $TICKET}
                            <input type="hidden" name="ticket_id" value="{$TICKET.id}" />
                        {/if}

                        <div class="form-group">
                            <label>Customer (Contact) <span class="text-danger">*</span></label>
                            <select name="customer_id" id="TicketsCustomerSelect" class="form-control input-sm" required>
                                <option value="">-- Chọn contact --</option>
                                {foreach from=$CONTACTS item=C}
                                    <option value="{$C.contactid}"
                                            data-org="{$C.accountname|default:''|escape}"
                                        {if $TICKET && $TICKET.customer_id eq $C.contactid}selected="selected"{/if}>
                                        {$C.firstname} {$C.lastname} (ID: {$C.contactid})
                                    </option>
                                {/foreach}
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Organization</label>
                            <input type="text" id="TicketsOrganizationDisplay" class="form-control input-sm" value="" readonly />
                            <p class="help-block small text-muted">
                                Tự động lấy theo Organization của Contact đã chọn.
                            </p>
                        </div>

                        <div class="form-group">
                            <label>Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control input-sm"
                                   value="{$TICKET.subject|default:''|escape}" required />
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control input-sm" rows="4">{$TICKET.description|default:''}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Priority</label>
                                    {assign var=prio value=$TICKET.priority|default:'Medium'}
                                    <select name="priority" class="form-control input-sm">
                                        {foreach from=['Critical','High','Medium','Low'] item=P}
                                            <option value="{$P}" {if $prio eq $P}selected="selected"{/if}>{$P}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Status</label>
                                    {assign var=st value=$TICKET.status|default:'Open'}
                                    <select name="status" class="form-control input-sm">
                                        {foreach from=['Open','In Progress','Resolved','Closed'] item=S}
                                            <option value="{$S}" {if $st eq $S}selected="selected"{/if}>{$S}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Assigned To</label>
                                    <select name="assigned_users_ids[]" class="form-control input-sm" multiple="multiple">
                                        {foreach from=$USERS item=U}
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
                        </div>

                        <div class="form-group">
                            <label>Attach Files</label>
                            <input type="file" name="ticket_files[]" multiple="multiple" class="form-control input-sm" />
                            <p class="help-block small text-muted">
                                Hỗ trợ jpg, png, pdf, docx, xlsx, zip, ...
                            </p>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <span class="fa fa-check"></span>
                            Save
                        </button>
                        <a href="index.php?module=HelpDesk&view=List" class="btn btn-default">
                            Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-5">
            <div class="alert alert-info small">
                <strong>Ghi chú:</strong><br />
                Đây là form đơn giản để test hệ thống Ticket mới.<br />
                Sau khi lưu, bạn có thể mở lại Ticket và thao tác thêm trong màn hình chi tiết.
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    (function () {
        function updateOrganization() {
            var select = document.getElementById('TicketsCustomerSelect');
            var orgInput = document.getElementById('TicketsOrganizationDisplay');
            if (!select || !orgInput) return;
            var opt = select.options[select.selectedIndex];
            var org = opt ? opt.getAttribute('data-org') : '';
            orgInput.value = org || '';
        }
        document.addEventListener('DOMContentLoaded', function () {
            var select = document.getElementById('TicketsCustomerSelect');
            if (select) {
                select.addEventListener('change', updateOrganization);
                updateOrganization();
            }
        });
    })();
</script>

{/strip}

