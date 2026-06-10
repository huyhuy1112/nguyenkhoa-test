{strip}
<div class="teams-modal teams-add-person-modal">
	<div class="teams-modal-header">
		<p class="teams-modal-kicker">{vtranslate('LBL_MANAGEMENT','Vtiger')}</p>
		<h3 class="teams-modal-title">Add person</h3>
		<p class="teams-modal-subtitle">Create a team member account with role, group, and access settings.</p>
	</div>
	<form id="EditView" class="js-teams-add-person-form teams-modal-form" method="post" action="index.php">
		<input type="hidden" name="module" value="Teams" />
		<input type="hidden" name="action" value="SavePerson" />
		<input type="hidden" name="app" value="Management" />

		<div class="teams-modal-section">
			<h4 class="teams-modal-section-title">Profile</h4>
			<div class="teams-modal-grid">
				<div class="form-group">
					<label class="control-label">First name *</label>
					<input type="text" name="first_name" class="form-control" required placeholder="Enter first name" />
				</div>
				<div class="form-group">
					<label class="control-label">Last name *</label>
					<input type="text" name="last_name" class="form-control" required placeholder="Enter last name" />
				</div>
			</div>
			<div class="form-group" style="margin-top:14px;">
				<label class="control-label">Email *</label>
				<input type="email" name="email" class="form-control" required placeholder="Enter email address" />
			</div>
			<div class="form-group" style="margin-top:14px;">
				<label class="control-label">Title *</label>
				<input type="text" name="title" class="form-control" required placeholder="Enter job title" />
			</div>
			<div class="form-group" style="margin-top:14px;">
				<label class="control-label">{vtranslate('LBL_DATE_JOINED_COMPANY','Teams')}</label>
				<input type="date" name="date_joined_company" class="form-control" />
			</div>
		</div>

		<div class="teams-modal-section">
			<h4 class="teams-modal-section-title">Access</h4>
			<div class="teams-modal-grid">
				<div class="form-group">
					<label class="control-label">Access role *</label>
					<select name="roleid" class="form-control" required>
						<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
						{foreach item=R from=$ROLES}
							<option value="{$R.roleid|escape}">{$R.rolename|decode_html|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="form-group">
					<label class="control-label">Group *</label>
					<select name="team_groupid" class="form-control" required>
						<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
						{foreach item=G from=$TEAM_GROUPS_LIST}
							<option value="{$G.groupid|escape}">{$G.group_name|escape}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="form-group" style="margin-top:14px;">
				<label class="control-label">Projects</label>
				<select name="projectid" class="form-control">
					<option value="">{vtranslate('LBL_NONE','Vtiger')}</option>
					{foreach item=P from=$PROJECTS}
						<option value="{$P.projectid|escape}">{$P.projectname|escape}</option>
					{/foreach}
				</select>
			</div>
			<div class="form-group" style="margin-top:14px;">
				<label class="control-label">Timezone *</label>
				<select name="time_zone" class="form-control" required>
					<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
					{foreach item=TZ from=$TIMEZONES}
						<option value="{$TZ|escape}">{$TZ|escape}</option>
					{/foreach}
				</select>
			</div>
		</div>

		<div class="teams-modal-section">
			<h4 class="teams-modal-section-title">Security</h4>
			<div class="form-group">
				<label class="control-label">Password *</label>
				<input type="password" name="password" class="form-control" required placeholder="Enter password" autocomplete="new-password" />
			</div>
		</div>

		<div class="teams-modal-footer">
			<button type="button" class="btn btn-default teams-btn-secondary" data-dismiss="modal">
				{vtranslate('LBL_CANCEL','Vtiger')}
			</button>
			<button type="submit" class="btn btn-primary teams-btn-primary">
				{vtranslate('LBL_SAVE','Vtiger')}
			</button>
		</div>
	</form>
</div>
{/strip}
