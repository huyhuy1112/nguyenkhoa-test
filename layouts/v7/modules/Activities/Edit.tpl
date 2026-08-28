{strip}
{assign var=MK_ACT_IS_SUPPORT value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT') || !isset($smarty.get.app) || $smarty.get.app eq ''}
	{assign var=MK_ACT_IS_SUPPORT value=true}
{/if}

{if $MK_ACT_IS_SUPPORT}
<div class="mk-act-page">
	<div class="mk-act-suite-card">
		<div class="mk-act-page-head">
			{include file="partials/ActivityEditHeader.tpl"|vtemplate_path:$MODULE}
		</div>
		<div class="mk-act-edit-toolbar">
			<a class="mk-act-edit-toolbar-link" href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">
				{include file="partials/ActivityListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='TICKETS'}
				<span>Tickets</span>
			</a>
			<a class="mk-act-edit-toolbar-link" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT">
				{include file="partials/ActivityListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='RULES'}
				<span>Rules</span>
			</a>
		</div>
		<div class="mk-act-edit-content">
			<div class="mk-act-edit-layout">
				<div class="mk-act-edit-main-col">
					<div class="mk-act-detail-card">
						<div class="mk-act-detail-card__head">
							<h2 class="mk-act-detail-card__title">Activity details</h2>
						</div>
						<div class="mk-act-detail-card__body">
							<form method="post" action="index.php" class="mk-act-edit-form" id="mkActEditForm">
								<input type="hidden" name="module" value="Activities" />
								<input type="hidden" name="action" value="Save" />
								<input type="hidden" name="record" value="{$RECORD.activityid}" />
								<input type="hidden" name="app" value="SUPPORT" />
								<input type="hidden" name="ticket_id" value="{$RECORD.ticketid|default:''}" />
								<input type="hidden" name="from_ticket" value="{$FROM_TICKET|default:0}" />

								<div class="mk-act-edit-fields">
									<div class="mk-act-edit-field">
										<label class="mk-act-edit-field__label" for="mkActType">Activity type</label>
										<select name="activity_type" id="mkActType" class="mk-act-edit-input">
											{foreach from=$TYPE_OPTIONS item=opt}
												<option value="{$opt|escape}" {if $RECORD.activity_type eq $opt}selected{/if}>{$opt|escape}</option>
											{/foreach}
										</select>
									</div>
									<div class="mk-act-edit-field">
										<label class="mk-act-edit-field__label" for="mkActStatus">Status <span class="mk-act-req">*</span></label>
										<select name="status" id="mkActStatus" class="mk-act-edit-input" required>
											{foreach from=$STATUS_OPTIONS item=opt}
												<option value="{$opt|escape}" {if $RECORD.status eq $opt}selected{/if}>{$opt|escape}</option>
											{/foreach}
										</select>
									</div>

									<div class="mk-act-edit-field mk-act-edit-field--wide">
										<label class="mk-act-edit-field__label" for="mkActContent">Content <span class="mk-act-req">*</span></label>
										<textarea name="content" id="mkActContent" class="mk-act-edit-input mk-act-edit-input--textarea" rows="3" required>{$RECORD.content|escape}</textarea>
									</div>

									<div class="mk-act-edit-field">
										<label class="mk-act-edit-field__label" for="mkActOrg">Organization</label>
										<select name="organizationid" id="mkActOrg" class="mk-act-edit-input">
											<option value="">-- None --</option>
											{foreach from=$ACCOUNTS item=acc}
												<option value="{$acc.accountid}" {if $RECORD.organizationid eq $acc.accountid}selected{/if}>{$acc.accountname|escape}</option>
											{/foreach}
										</select>
									</div>
									<div class="mk-act-edit-field">
										<label class="mk-act-edit-field__label" for="mkActProject">Project</label>
										<select name="projectid" id="mkActProject" class="mk-act-edit-input">
											<option value="">-- None --</option>
											{foreach from=$PROJECTS item=pr}
												<option value="{$pr.projectid}" {if $RECORD.projectid eq $pr.projectid}selected{/if}>{$pr.projectname|escape}</option>
											{/foreach}
										</select>
									</div>

									<div class="mk-act-edit-field">
										<label class="mk-act-edit-field__label" for="mkActDate">Activity date</label>
										<input type="datetime-local" name="activity_date" id="mkActDate" class="mk-act-edit-input"
											   value="{$RECORD.activity_date|replace:' ':'T'|escape}" />
									</div>
									<div class="mk-act-edit-field">
										<label class="mk-act-edit-field__label" for="mkActAssignee">Assigned to</label>
										<select name="assigned_user_id" id="mkActAssignee" class="mk-act-edit-input">
											{foreach from=$USERS item=u}
												<option value="{$u.id}" {if $RECORD.assigned_user_id eq $u.id}selected{/if}>
													{$u.first_name|escape} {$u.last_name|escape}
												</option>
											{/foreach}
										</select>
									</div>

									<div class="mk-act-edit-field">
										<label class="mk-act-edit-field__label" for="mkActNoteBefore">Note before</label>
										<textarea name="note_before" id="mkActNoteBefore" class="mk-act-edit-input mk-act-edit-input--textarea-sm" rows="2">{$RECORD.note_before|escape}</textarea>
									</div>
									<div class="mk-act-edit-field">
										<label class="mk-act-edit-field__label" for="mkActNoteAfter">Note after</label>
										<textarea name="note_after" id="mkActNoteAfter" class="mk-act-edit-input mk-act-edit-input--textarea-sm" rows="2">{$RECORD.note_after|escape}</textarea>
									</div>
								</div>

								<div class="mk-act-edit-actions">
									<button type="submit" class="mk-act-edit-btn mk-act-edit-btn--primary">
										<span class="mk-act-edit-btn__txt">Save activity</span>
									</button>
									{if $FROM_TICKET gt 0 && $RECORD.ticketid gt 0}
										<a href="index.php?module=HelpDesk&amp;view=TicketDetail&amp;record={$RECORD.ticketid}&amp;app=SUPPORT" class="mk-act-edit-btn mk-act-edit-btn--ghost">
											<span class="mk-act-edit-btn__txt">Cancel</span>
										</a>
									{else}
										<a href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT" class="mk-act-edit-btn mk-act-edit-btn--ghost">
											<span class="mk-act-edit-btn__txt">Cancel</span>
										</a>
									{/if}
								</div>
							</form>
						</div>
					</div>
				</div>

				<aside class="mk-act-edit-aside" aria-label="Guidance">
					<div class="mk-act-aside-card mk-act-aside-card--tips">
						<div class="mk-act-aside-card__head">
							<span class="mk-act-aside-card__icon mk-act-aside-card__icon--tips" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2a7 7 0 0 1 7 7c0 2.4-1.2 4.5-3 5.7V17a1 1 0 0 1-1 1h-6a1 1 0 0 1-1-1v-2.3C6.2 13.5 5 11.4 5 9a7 7 0 0 1 7-7Z" stroke="currentColor" stroke-width="1.75"/><path d="M9 21h6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
							</span>
							<h3 class="mk-act-aside-card__title">Quick tips</h3>
						</div>
						<div class="mk-act-aside-card__body">
							<p class="mk-act-aside-card__text">Gắn <strong>Organization</strong> hoặc <strong>Project</strong> để lọc activity trên danh sách.</p>
							<p class="mk-act-aside-card__text mk-act-aside-card__text--muted">Tạo từ Ticket detail sẽ tự liên kết ticket khi lưu.</p>
						</div>
					</div>
					<div class="mk-act-aside-card mk-act-aside-card--status">
						<div class="mk-act-aside-card__head">
							<span class="mk-act-aside-card__icon mk-act-aside-card__icon--status" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
							</span>
							<h3 class="mk-act-aside-card__title">Status guide</h3>
						</div>
						<ul class="mk-act-status-guide">
							<li class="mk-act-status-guide__item mk-act-status-guide__item--scheduled">
								<span class="mk-act-status-guide__badge">Scheduled</span>
								<span class="mk-act-status-guide__desc">Đã lên lịch, chưa bắt đầu</span>
							</li>
							<li class="mk-act-status-guide__item mk-act-status-guide__item--ready">
								<span class="mk-act-status-guide__badge">Ready</span>
								<span class="mk-act-status-guide__desc">Sẵn sàng thực hiện</span>
							</li>
							<li class="mk-act-status-guide__item mk-act-status-guide__item--completed">
								<span class="mk-act-status-guide__badge">Completed</span>
								<span class="mk-act-status-guide__desc">Đã hoàn thành</span>
							</li>
							<li class="mk-act-status-guide__item mk-act-status-guide__item--skipped">
								<span class="mk-act-status-guide__badge">Skipped</span>
								<span class="mk-act-status-guide__desc">Bỏ qua / không thực hiện</span>
							</li>
						</ul>
					</div>
				</aside>
			</div>
		</div>
	</div>
</div>
{else}
<div class="container-fluid" style="padding:18px;">
    <div class="panel panel-default" style="border-radius:12px; box-shadow:0 12px 30px rgba(15,23,42,0.12);">
        <div class="panel-heading">
            <h4 class="panel-title">
                {if $RECORD.activityid}Edit Activity #{$RECORD.activityid}{else}New Activity{/if}
            </h4>
        </div>
        <div class="panel-body">
            <form method="post" action="index.php">
                <input type="hidden" name="module" value="Activities" />
                <input type="hidden" name="action" value="Save" />
                <input type="hidden" name="record" value="{$RECORD.activityid}" />
                <input type="hidden" name="app" value="SUPPORT" />
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" class="form-control" rows="3" required>{$RECORD.content|escape}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="index.php?module=Activities&view=List&app=SUPPORT" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
{/if}
{/strip}
