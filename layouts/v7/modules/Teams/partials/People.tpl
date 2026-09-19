{strip}
<div class="table-responsive mk-teams-table-wrap">
	<table class="table mk-teams-people-table">
		<thead>
			<tr>
				<th class="mk-teams-th mk-teams-th--check">
					<input type="checkbox" class="teams-people-select-all" title="{vtranslate('LBL_ACTIONS','Vtiger')}" />
				</th>
				<th class="mk-teams-th">{vtranslate('LBL_NAME','Teams')}</th>
				<th class="mk-teams-th">{vtranslate('LBL_EMAIL','Teams')}</th>
				<th class="mk-teams-th">{vtranslate('LBL_DATE_JOINED_COMPANY','Teams')}</th>
				<th class="mk-teams-th">{vtranslate('LBL_LAST_ACTIVE','Teams')}</th>
				<th class="mk-teams-th mk-teams-th--actions">{vtranslate('LBL_ACTIONS','Vtiger')}</th>
			</tr>
		</thead>
		<tbody>
			{foreach key=ROLE_NAME item=ROLE_PEOPLE from=$PEOPLE_BY_ROLE}
				{assign var=ROLE_COUNT value=$ROLE_PEOPLE|@count}
				<tr class="mk-teams-role-header js-role-toggle" data-role="{$ROLE_NAME|escape}" role="button">
					<td class="mk-teams-role-cell" colspan="6">
						<span class="mk-teams-role-chip">
							<i class="fa fa-chevron-down teams-role-chevron" aria-hidden="true"></i>
							<span class="teams-role-name">{$ROLE_NAME|decode_html}</span>
							<span class="mk-teams-role-count">{$ROLE_COUNT}</span>
						</span>
					</td>
				</tr>
				{foreach item=ROW from=$ROLE_PEOPLE name=roleRows}
					<tr data-userid="{$ROW.id}" class="teams-people-row teams-role-row mk-teams-people-row" data-role="{$ROLE_NAME|escape}" data-date-joined="{$ROW.date_joined_company_raw|escape}">
						<td class="mk-teams-td mk-teams-td--check">
							<input type="checkbox" class="teams-people-row-select" value="{$ROW.id}" />
						</td>
						<td class="mk-teams-td teams-people-name-cell">
							<span class="mk-teams-avatar" data-initial="{$ROW.initial}">{$ROW.initial}</span>
							<div class="mk-teams-person">
								<a href="index.php?module=Users&parent=Settings&view=Detail&record={$ROW.id}" class="teams-people-name-link mk-teams-person__name">{$ROW.full_name|decode_html}</a>
								<span class="mk-teams-person__sub teams-people-email-inline">{$ROW.email|decode_html}</span>
							</div>
						</td>
						<td class="mk-teams-td teams-people-email-cell">
							<a class="mk-teams-email" href="mailto:{$ROW.email|escape}">{$ROW.email|decode_html}</a>
						</td>
						<td class="mk-teams-td">{$ROW.date_joined_company|decode_html|default:'—'}</td>
						<td class="mk-teams-td teams-people-lastactive-cell">
							{if $ROW.is_inactive}
								<span class="mk-teams-badge mk-teams-badge--danger">Ngừng hoạt động</span>
							{elseif $ROW.is_online}
								<span class="mk-teams-badge mk-teams-badge--online"><span class="mk-teams-status-dot"></span>Trực tuyến</span>
							{elseif $ROW.status_label eq 'Never logged in'}
								<span class="mk-teams-muted">Chưa đăng nhập</span>
							{else}
								<span class="mk-teams-muted"><span class="mk-teams-status-dot mk-teams-status-dot--away"></span>{$ROW.status_label|decode_html}</span>
							{/if}
						</td>
						<td class="mk-teams-td mk-teams-td--actions teams-people-actions-cell">
							<div class="btn-group">
								<button class="mk-teams-more-btn dropdown-toggle" data-toggle="dropdown" title="{vtranslate('LBL_ACTIONS','Vtiger')}">
									<i class="fa fa-ellipsis-h"></i>
								</button>
								<ul class="dropdown-menu dropdown-menu-right mk-teams-actions-menu">
									<li><a href="index.php?module=Users&parent=Settings&view=Edit&record={$ROW.id}">{vtranslate('LBL_EDIT','Vtiger')}</a></li>
									<li><a href="index.php?module=Users&view=EditAjax&mode=changePassword&recordId={$ROW.id}" target="_blank">{vtranslate('LBL_CHANGE_PASSWORD','Users')}</a></li>
									{if $CAN_DEACTIVATE}
										<li class="divider"></li>
										<li><a href="#" class="js-delete-person mk-teams-action-danger" data-userid="{$ROW.id|escape}">Xoá tài khoản</a></li>
									{/if}
								</ul>
							</div>
						</td>
					</tr>
				{/foreach}
			{/foreach}
			{if $PEOPLE_BY_ROLE|@count eq 0}
				<tr>
					<td colspan="6" class="mk-teams-empty">Chưa có nhân sự nào. Bấm <strong>Thêm tài khoản</strong> để tạo mới.</td>
				</tr>
			{/if}
		</tbody>
	</table>
</div>
{/strip}
