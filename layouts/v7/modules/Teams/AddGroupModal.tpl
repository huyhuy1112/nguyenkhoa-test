{strip}
<div class="teams-modal teams-add-group-modal">
	<div class="teams-modal-header">
		<p class="teams-modal-kicker">{vtranslate('LBL_MANAGEMENT','Vtiger')}</p>
		<h3 class="teams-modal-title">{if $IS_EDIT}Edit group{else}Add group{/if}</h3>
		<p class="teams-modal-subtitle">{if $IS_EDIT}Update group name and member assignments.{else}Organize people into a group for easier assignment and reporting.{/if}</p>
	</div>
	<form class="js-teams-add-group-form teams-modal-form" method="post" action="index.php">
		<input type="hidden" name="module" value="Teams" />
		<input type="hidden" name="action" value="SaveGroup" />
		<input type="hidden" name="app" value="Management" />
		{if $IS_EDIT}
			<input type="hidden" name="groupid" value="{$GROUP_ID|escape}" />
			<input type="hidden" name="mode" value="edit" />
			<input type="hidden" name="assign_type" value="USERS" />
		{/if}

		<div class="teams-modal-section">
			<h4 class="teams-modal-section-title">Group details</h4>
			<div class="form-group">
				<label class="control-label">Group Name *</label>
				<input type="text" name="group_name" class="form-control" required placeholder="Enter group name" value="{if $GROUP_DATA}{$GROUP_DATA.group_name|escape}{/if}" />
			</div>
		</div>

		{if $IS_EDIT}
			<div class="teams-modal-section">
				<h4 class="teams-modal-section-title">Members</h4>
				<div class="form-group">
					<div class="teams-users-checkbox-list">
						{foreach item=U from=$TEAM_MEMBERS}
							{assign var="isSelected" value=false}
							{if $IS_EDIT && $SELECTED_USER_IDS}
								{foreach item=SUID from=$SELECTED_USER_IDS}
									{if $SUID eq $U.id}
										{assign var="isSelected" value=true}
									{/if}
								{/foreach}
							{/if}
							<div class="checkbox">
								<label>
									<input type="checkbox" name="userids[]" value="{$U.id|escape}" {if $isSelected}checked{/if} />
									<strong>{$U.first_name|escape} {$U.last_name|escape}</strong>
									<span class="text-muted">({$U.user_name|escape})</span>
								</label>
							</div>
						{/foreach}
					</div>
				</div>
			</div>
		{else}
			<div class="teams-modal-section">
				<h4 class="teams-modal-section-title">Assign members</h4>
				<div class="form-group">
					<div class="teams-assign-members-container">
						<div class="teams-assign-options">
							<label class="radio-inline">
								<input type="radio" name="assign_method" value="users" checked class="teams-assign-method" />
								Select Users
							</label>
							<label class="radio-inline">
								<input type="radio" name="assign_method" value="groups" class="teams-assign-method" />
								Select Groups
							</label>
							<label class="radio-inline">
								<input type="radio" name="assign_method" value="all" class="teams-assign-method" />
								Select All Users
							</label>
						</div>

						<div class="teams-assign-groups-field" style="display: none; margin-bottom: 12px;">
							<select name="groupids[]" multiple class="select2 inputElement form-control teams-group-selector" data-placeholder="Select groups to load their users...">
								{foreach item=G from=$EXISTING_GROUPS}
									<option value="{$G.groupid|escape}">{$G.group_name|escape}</option>
								{/foreach}
							</select>
						</div>

						<div class="teams-users-checkbox-list-container">
							<div class="teams-users-checkbox-list">
								<div class="teams-users-placeholder text-muted">
									Select an assignment method above to load users...
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		{/if}

		<div class="teams-modal-footer">
			<button type="button" class="btn btn-default teams-btn-secondary" data-dismiss="modal">
				{vtranslate('LBL_CANCEL','Vtiger')}
			</button>
			<button type="submit" class="btn btn-primary teams-btn-primary">
				{if $IS_EDIT}{vtranslate('LBL_SAVE','Vtiger')}{else}{vtranslate('LBL_ADD','Vtiger')}{/if}
			</button>
		</div>
	</form>
</div>
{/strip}
