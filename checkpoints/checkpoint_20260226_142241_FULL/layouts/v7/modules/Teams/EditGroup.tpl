{strip}
<div class="col-sm-12 col-xs-12">
	<div class="editViewPageDiv">
		<div class="editViewHeader">
			<h3>{vtranslate('LBL_EDIT_GROUP','Teams')}</h3>
		</div>
		<form class="form-horizontal" method="post" action="index.php">
			<input type="hidden" name="module" value="Teams" />
			<input type="hidden" name="action" value="SaveGroup" />
			<input type="hidden" name="app" value="Management" />
			<input type="hidden" name="record" value="{$GROUP_ID|escape}" />
			<input type="hidden" name="groupid" value="{$GROUP_ID|escape}" />
			<input type="hidden" name="mode" value="edit" />
			<input type="hidden" name="assign_type" value="USERS" />

			<div class="form-group">
				<label class="control-label col-sm-3">Group Name *</label>
				<div class="col-sm-6">
					<input type="text" name="group_name" class="form-control" required placeholder="Enter group name" value="{$GROUP_DATA.group_name|escape}" />
				</div>
			</div>

			<div class="form-group">
				<label class="control-label col-sm-3">Members *</label>
				<div class="col-sm-6">
					<div class="teams-users-checkbox-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 12px; background: #fff;">
						{foreach item=U from=$TEAM_MEMBERS}
							{assign var="isSelected" value=false}
							{if $SELECTED_USER_IDS}
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
			</div>

			<div class="form-group">
				<div class="col-sm-offset-3 col-sm-6">
					<button type="submit" class="btn btn-success">{vtranslate('LBL_SAVE','Vtiger')}</button>
					<a class="btn btn-default" href="index.php?module=Teams&view=List&tab=groups&app=Management">{vtranslate('LBL_CANCEL','Vtiger')}</a>
				</div>
			</div>
		</form>
	</div>
</div>
{/strip}
