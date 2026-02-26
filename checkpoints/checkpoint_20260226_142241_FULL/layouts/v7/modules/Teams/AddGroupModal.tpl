{strip}
<div class="teams-modal">
	<div class="teams-modal-header">
		<h3 class="teams-modal-title">{if $IS_EDIT}Edit group{else}Add group{/if}</h3>
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

		<div class="form-group">
			<label class="control-label">Group Name *</label>
			<input type="text" name="group_name" class="form-control" required placeholder="Enter group name" value="{if $GROUP_DATA}{$GROUP_DATA.group_name|escape}{/if}" />
		</div>

		{if $IS_EDIT}
			{* Edit mode: Simple user selection with checkboxes *}
			<div class="form-group">
				<label class="control-label">Members *</label>
				<div class="teams-users-checkbox-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 12px;">
					{foreach item=U from=$TEAM_MEMBERS}
						{assign var="isSelected" value=false}
						{if $IS_EDIT && $SELECTED_USER_IDS}
							{foreach item=SUID from=$SELECTED_USER_IDS}
								{if $SUID eq $U.id}
									{assign var="isSelected" value=true}
								{/if}
							{/foreach}
						{/if}
						<div class="checkbox" style="margin: 8px 0;">
							<label>
								<input type="checkbox" name="userids[]" value="{$U.id|escape}" {if $isSelected}checked{/if} />
								<strong>{$U.first_name|escape} {$U.last_name|escape}</strong>
								<span class="text-muted">({$U.user_name|escape})</span>
							</label>
						</div>
					{/foreach}
				</div>
			</div>
		{else}
			{* Add mode: Unified checkbox list with preset options *}
			<div class="form-group">
				<label class="control-label">Assign Members *</label>
				<div class="teams-assign-members-container">
					{* Preset options - these are helpers to populate the user list *}
					<div class="teams-assign-options" style="margin-bottom: 12px;">
						<label class="radio-inline" style="margin-right: 20px;">
							<input type="radio" name="assign_method" value="users" checked class="teams-assign-method" />
							Select Users
						</label>
						<label class="radio-inline" style="margin-right: 20px;">
							<input type="radio" name="assign_method" value="groups" class="teams-assign-method" />
							Select Groups
						</label>
						<label class="radio-inline">
							<input type="radio" name="assign_method" value="all" class="teams-assign-method" />
							Select All Users
						</label>
					</div>

					{* Group selector (shown only when "Select Groups" is chosen) *}
					<div class="teams-assign-groups-field" style="display: none; margin-bottom: 12px;">
						<select name="groupids[]" multiple class="select2 inputElement form-control teams-group-selector" data-placeholder="Select groups to load their users...">
							{foreach item=G from=$EXISTING_GROUPS}
								<option value="{$G.groupid|escape}">{$G.group_name|escape}</option>
							{/foreach}
						</select>
					</div>

					{* Unified user checkbox list - always visible *}
					<div class="teams-users-checkbox-list-container">
						<div class="teams-users-checkbox-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 12px; background: #fff;">
							<div class="teams-users-placeholder text-muted" style="padding: 10px; font-style: italic; text-align: center;">
								Select an assignment method above to load users...
							</div>
						</div>
					</div>
				</div>
			</div>
		{/if}

		<div class="form-group text-right">
			<button type="submit" class="btn btn-primary">{if $IS_EDIT}{vtranslate('LBL_SAVE','Vtiger')}{else}{vtranslate('LBL_ADD','Vtiger')}{/if}</button>
			<button type="button" class="btn btn-default" data-dismiss="modal">{vtranslate('LBL_CANCEL','Vtiger')}</button>
		</div>
	</form>
</div>
{/strip}
