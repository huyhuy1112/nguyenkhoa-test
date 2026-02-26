{strip}
<div class="table-responsive teams-people-table-wrap">
	<table class="table listViewEntriesTable teams-people-table">
		<thead>
			<tr>
				<th class="teams-people-th-checkbox">
					<input type="checkbox" class="teams-people-select-all" title="{vtranslate('LBL_ACTIONS','Vtiger')}" />
				</th>
				<th>{vtranslate('LBL_NAME','Vtiger')}</th>
				<th>{vtranslate('LBL_EMAIL','Vtiger')}</th>
				<th>{vtranslate('LBL_DATE_JOINED_COMPANY','Teams')}</th>
				<th>{vtranslate('LBL_ASSIGNED_PROJECTS','Teams')}</th>
				<th>{vtranslate('LBL_LAST_ACTIVE','Teams')}</th>
				<th class="actions">{vtranslate('LBL_ACTIONS','Vtiger')}</th>
			</tr>
		</thead>
		<tbody>
			{foreach key=ROLE_NAME item=ROLE_PEOPLE from=$PEOPLE_BY_ROLE}
				{assign var=ROLE_COUNT value=$ROLE_PEOPLE|@count}
				<tr class="teams-people-role-header js-role-toggle" data-role="{$ROLE_NAME|escape}" role="button">
					<td class="teams-people-role-th" colspan="7">
						<i class="fa fa-chevron-down teams-role-chevron" aria-hidden="true"></i>
						<span class="teams-role-name">{$ROLE_NAME|decode_html} ({$ROLE_COUNT})</span>
					</td>
				</tr>
				{foreach item=ROW from=$ROLE_PEOPLE name=roleRows}
					<tr data-userid="{$ROW.id}" class="teams-people-row teams-role-row" data-role="{$ROLE_NAME|escape}" data-date-joined="{$ROW.date_joined_company_raw|escape}">
						<td class="teams-people-checkbox-cell">
							<input type="checkbox" class="teams-people-row-select" value="{$ROW.id}" />
						</td>
						<td class="teams-people-name-cell">
							<span class="teams-people-avatar teams-avatar-initial-{$ROW.initial|lower}" data-initial="{$ROW.initial}">{$ROW.initial}</span>
							<div class="teams-people-name-block">
								<a href="index.php?module=Users&parent=Settings&view=Detail&record={$ROW.id}" class="teams-people-name-link">{$ROW.full_name|decode_html}</a>
								<span class="teams-people-email-inline">{$ROW.email|decode_html}</span>
							</div>
						</td>
						<td class="teams-people-email-cell">{$ROW.email|decode_html}</td>
						<td class="teams-people-role-cell">{$ROW.date_joined_company|decode_html}</td>
						<td class="teams-people-projects-cell">
							{if $ROW.project_count > 0}
								<span class="teams-project-count"
									data-toggle="popover"
									data-trigger="hover"
									data-placement="left"
									data-html="true"
									data-userid="{$ROW.id}"
									style="cursor: pointer; color: #337ab7; text-decoration: underline; font-weight: 500;">
									{$ROW.project_count}
								</span>
								<script type="text/template" class="teams-projects-data" data-userid="{$ROW.id}">
								{literal}{{/literal}
									"projects": [
										{foreach item=PROJ from=$ROW.projects name=projloop}
											{literal}{{/literal}"id":{$PROJ.id},"name":"{$PROJ.name|escape:'javascript'}","status":"{$PROJ.status|escape:'javascript'}"{literal}}{/literal}{if not $smarty.foreach.projloop.last},{/if}
										{/foreach}
									],
									"count": {$ROW.project_count}
								{literal}}{/literal}
								</script>
							{else}
								0
							{/if}
						</td>
						<td class="teams-people-lastactive-cell">
							{if $ROW.is_inactive}
								<span class="label label-danger">Inactive</span>
							{elseif $ROW.is_online}
								<span class="label label-success teams-status-online">
									<span class="dot-online"></span>
									<span>Online</span>
								</span>
							{elseif $ROW.status_label eq 'Never logged in'}
								<span class="text-muted teams-status-never"><i class="fa fa-info-circle"></i> Never logged in</span>
							{else}
								<span class="text-muted teams-status-ago">
									<span class="teams-status-dot"></span>
									<span>{$ROW.status_label|decode_html}</span>
								</span>
							{/if}
						</td>
						<td class="teams-people-actions-cell">
							<div class="btn-group">
								<button class="btn btn-default btn-sm dropdown-toggle teams-action-btn" data-toggle="dropdown" title="{vtranslate('LBL_ACTIONS','Vtiger')}">
									<i class="fa fa-ellipsis-v"></i>
								</button>
								<ul class="dropdown-menu dropdown-menu-right">
									<li><a href="index.php?module=Users&parent=Settings&view=Edit&record={$ROW.id}">{vtranslate('LBL_EDIT','Vtiger')}</a></li>
									<li><a href="index.php?module=Users&view=EditAjax&mode=changePassword&recordId={$ROW.id}" target="_blank">{vtranslate('LBL_CHANGE_PASSWORD','Users')}</a></li>
									<li><a href="index.php?module=Teams&view=AddToGroup&app=Management&userid={$ROW.id}">{vtranslate('LBL_ADD_GROUP','Vtiger')}</a></li>
									{if $CAN_DEACTIVATE}
										<li><a href="#" class="js-delete-person dropdown-item" data-userid="{$ROW.id|escape}">Delete</a></li>
									{/if}
								</ul>
							</div>
						</td>
					</tr>
				{/foreach}
			{/foreach}
			{if $PEOPLE_BY_ROLE|@count eq 0}
				<tr><td colspan="7" class="text-center text-muted">No people found.</td></tr>
			{/if}
		</tbody>
	</table>
</div>
{/strip}
