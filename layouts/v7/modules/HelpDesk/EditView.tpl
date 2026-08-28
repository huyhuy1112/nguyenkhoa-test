{strip}
{assign var=MK_HD_IS_SUPPORT value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT') || !isset($smarty.get.app) || $smarty.get.app eq ''}
	{assign var=MK_HD_IS_SUPPORT value=true}
{/if}

{if $MK_HD_IS_SUPPORT}
<div class="mk-hd-page">
	<div class="mk-hd-suite-card">
		<div class="mk-hd-page-head">
			{include file="partials/TicketEditHeader.tpl"|vtemplate_path:$MODULE}
		</div>
		<div class="mk-hd-edit-toolbar">
			<a class="mk-hd-toolbar-link" href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT">
				{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='ACTIVITIES'}
				<span>Activities</span>
			</a>
			<a class="mk-hd-toolbar-link" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT">
				{include file="partials/TicketListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='RULES'}
				<span>Quản Lý rule</span>
			</a>
		</div>
		<div class="mk-hd-edit-content">
			<div class="mk-hd-edit-layout">
				<div class="mk-hd-edit-main-col">
					<div class="mk-hd-detail-card">
						<div class="mk-hd-detail-card__head">
							<h2 class="mk-hd-detail-card__title">Ticket details</h2>
						</div>
						<div class="mk-hd-detail-card__body">
							<form method="post" action="index.php" enctype="multipart/form-data" class="mk-hd-edit-form" id="mkHdTicketForm">
								<input type="hidden" name="module" value="HelpDesk" />
								<input type="hidden" name="action" value="SaveTicket" />
								{if $TICKET}
									<input type="hidden" name="ticket_id" value="{$TICKET.id}" />
								{/if}

								<div class="mk-hd-edit-fields">
									<div class="mk-hd-edit-field mk-hd-edit-field--wide">
										<label class="mk-hd-edit-field__label" for="TicketsCustomerSelect">Customer (Contact) <span class="mk-hd-req">*</span></label>
										<select name="customer_id" id="TicketsCustomerSelect" class="mk-hd-edit-input" required>
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

									<div class="mk-hd-edit-field mk-hd-edit-field--wide">
										<label class="mk-hd-edit-field__label" for="TicketsOrganizationDisplay">Organization</label>
										<input type="text" id="TicketsOrganizationDisplay" class="mk-hd-edit-input" value="" readonly />
										<p class="mk-hd-edit-hint">Tự động lấy theo Organization của Contact đã chọn.</p>
									</div>

									<div class="mk-hd-edit-field mk-hd-edit-field--wide">
										<label class="mk-hd-edit-field__label" for="mkHdTicketSubject">Subject <span class="mk-hd-req">*</span></label>
										<input type="text" name="subject" id="mkHdTicketSubject" class="mk-hd-edit-input"
											   value="{$TICKET.subject|default:''|escape}" required />
									</div>

									<div class="mk-hd-edit-field mk-hd-edit-field--wide">
										<label class="mk-hd-edit-field__label" for="mkHdTicketDescription">Description</label>
										<textarea name="description" id="mkHdTicketDescription" class="mk-hd-edit-input mk-hd-edit-input--textarea" rows="4">{$TICKET.description|default:''}</textarea>
									</div>

									<div class="mk-hd-edit-field">
										<label class="mk-hd-edit-field__label" for="mkHdTicketPriority">Priority</label>
										{assign var=prio value=$TICKET.priority|default:'Medium'}
										<select name="priority" id="mkHdTicketPriority" class="mk-hd-edit-input">
											{foreach from=['Critical','High','Medium','Low'] item=P}
												<option value="{$P}" {if $prio eq $P}selected="selected"{/if}>{$P}</option>
											{/foreach}
										</select>
									</div>

									<div class="mk-hd-edit-field">
										<label class="mk-hd-edit-field__label" for="mkHdTicketStatus">Status</label>
										{assign var=st value=$TICKET.status|default:'Open'}
										<select name="status" id="mkHdTicketStatus" class="mk-hd-edit-input">
											{foreach from=['Open','In Progress','Resolved','Closed'] item=S}
												<option value="{$S}" {if $st eq $S}selected="selected"{/if}>{$S}</option>
											{/foreach}
										</select>
									</div>

									<div class="mk-hd-edit-field mk-hd-edit-field--wide">
										<label class="mk-hd-edit-field__label" for="mkHdTicketAssignees">Assigned To</label>
										<select name="assigned_users_ids[]" id="mkHdTicketAssignees" class="mk-hd-edit-input mk-hd-edit-input--multi" multiple="multiple">
											{foreach from=$USERS item=U}
												<option value="{$U.id}"
													{if in_array($U.id, $ASSIGNED_USER_IDS)}selected="selected"{/if}>
													{$U.first_name} {$U.last_name} (ID: {$U.id})
												</option>
											{/foreach}
										</select>
										<p class="mk-hd-edit-hint">Giữ Ctrl / Cmd để chọn nhiều người xử lý.</p>
									</div>

									<div class="mk-hd-edit-field mk-hd-edit-field--wide">
										<label class="mk-hd-edit-field__label" for="mkHdTicketFiles">Attach Files</label>
										<input type="file" name="ticket_files[]" id="mkHdTicketFiles" multiple="multiple" class="mk-hd-edit-input" />
										<p class="mk-hd-edit-hint">Hỗ trợ jpg, png, pdf, docx, xlsx, zip, ...</p>
									</div>
								</div>

								<div class="mk-hd-edit-actions">
									<button type="submit" class="mk-hd-btn mk-hd-btn--primary">
										<span class="mk-hd-btn__txt">Save ticket</span>
									</button>
									<a href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT" class="mk-hd-btn mk-hd-btn--ghost">
										<span class="mk-hd-btn__txt">Cancel</span>
									</a>
								</div>
							</form>
						</div>
					</div>
				</div>

				<aside class="mk-hd-edit-aside" aria-label="Tips">
					<div class="mk-hd-aside-card mk-hd-aside-card--tips">
						<div class="mk-hd-aside-card__head">
							<span class="mk-hd-aside-card__icon mk-hd-aside-card__icon--tips" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2a7 7 0 0 1 7 7c0 2.4-1.2 4.5-3 5.7V17a1 1 0 0 1-1 1h-6a1 1 0 0 1-1-1v-2.3C6.2 13.5 5 11.4 5 9a7 7 0 0 1 7-7Z" stroke="currentColor" stroke-width="1.75"/><path d="M9 21h6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
							</span>
							<h3 class="mk-hd-aside-card__title">Quick tips</h3>
						</div>
						<div class="mk-hd-aside-card__body">
							<p class="mk-hd-aside-card__text">
								Chọn <strong>Contact</strong> trước — <strong>Organization</strong> sẽ tự điền theo hồ sơ khách hàng.
							</p>
							<p class="mk-hd-aside-card__text mk-hd-aside-card__text--secondary">
								Sau khi lưu, mở Ticket detail để theo dõi SLA, comment và đính kèm thêm.
							</p>
						</div>
					</div>

					<div class="mk-hd-aside-card mk-hd-aside-card--priority">
						<div class="mk-hd-aside-card__head">
							<span class="mk-hd-aside-card__icon mk-hd-aside-card__icon--priority" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 3l2.4 6.8H21l-5.4 4 2.1 6.8L12 16.6 6.3 20.6l2.1-6.8L3 9.8h6.6L12 3Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>
							</span>
							<h3 class="mk-hd-aside-card__title">Priority guide</h3>
						</div>
						<ul class="mk-hd-priority-guide">
							<li class="mk-hd-priority-guide__item mk-hd-priority-guide__item--critical">
								<span class="mk-hd-priority-guide__badge">Critical</span>
								<span class="mk-hd-priority-guide__desc">Hệ thống ngừng hoạt động</span>
							</li>
							<li class="mk-hd-priority-guide__item mk-hd-priority-guide__item--high">
								<span class="mk-hd-priority-guide__badge">High</span>
								<span class="mk-hd-priority-guide__desc">Ảnh hưởng nghiêm trọng</span>
							</li>
							<li class="mk-hd-priority-guide__item mk-hd-priority-guide__item--medium">
								<span class="mk-hd-priority-guide__badge">Medium</span>
								<span class="mk-hd-priority-guide__desc">Cần xử lý trong ngày</span>
							</li>
							<li class="mk-hd-priority-guide__item mk-hd-priority-guide__item--low">
								<span class="mk-hd-priority-guide__badge">Low</span>
								<span class="mk-hd-priority-guide__desc">Có thể lên lịch sau</span>
							</li>
						</ul>
					</div>
				</aside>
			</div>
		</div>
	</div>
</div>
{else}
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
                            <select name="customer_id" id="TicketsCustomerSelectLegacy" class="form-control input-sm" required>
                                <option value="">-- Chọn contact --</option>
                                {foreach from=$CONTACTS item=C}
                                    <option value="{$C.contactid}" data-org="{$C.accountname|default:''|escape}"
                                        {if $TICKET && $TICKET.customer_id eq $C.contactid}selected="selected"{/if}>
                                        {$C.firstname} {$C.lastname} (ID: {$C.contactid})
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control input-sm" value="{$TICKET.subject|default:''|escape}" required />
                        </div>
                        <button type="submit" class="btn btn-success">Save</button>
                        <a href="index.php?module=HelpDesk&view=List" class="btn btn-default">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
{/if}

<script type="text/javascript">
(function () {
	function updateOrganization() {
		var select = document.getElementById('TicketsCustomerSelect') || document.getElementById('TicketsCustomerSelectLegacy');
		var orgInput = document.getElementById('TicketsOrganizationDisplay');
		if (!select || !orgInput) return;
		var opt = select.options[select.selectedIndex];
		var org = opt ? opt.getAttribute('data-org') : '';
		orgInput.value = org || '';
	}
	document.addEventListener('DOMContentLoaded', function () {
		var select = document.getElementById('TicketsCustomerSelect') || document.getElementById('TicketsCustomerSelectLegacy');
		if (select) {
			select.addEventListener('change', updateOrganization);
			updateOrganization();
		}
	});
})();
</script>
{/strip}
