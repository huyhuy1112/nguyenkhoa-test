{strip}
<div class="teams-modal" style="padding: 20px;">
	<div class="teams-modal-header" style="margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 15px;">
		<h3 class="teams-modal-title" style="margin: 0; font-size: 18px; font-weight: 600;">Add person</h3>
	</div>
	<form id="EditView" class="js-teams-add-person-form teams-modal-form" method="post" action="index.php">
		<input type="hidden" name="module" value="Teams" />
		<input type="hidden" name="action" value="SavePerson" />
		<input type="hidden" name="app" value="Management" />

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

		<div class="form-group">
			<label class="control-label">Email *</label>
			<input type="email" name="email" class="form-control" required placeholder="Enter email address" />
		</div>

		<div class="form-group">
			<label class="control-label">Title *</label>
			<input type="text" name="title" class="form-control" required placeholder="Enter job title" />
		</div>

		<div class="form-group">
			<label class="control-label">{vtranslate('LBL_DATE_JOINED_COMPANY','Teams')}</label>
			<input type="date" name="date_joined_company" class="form-control" placeholder="YYYY-MM-DD" />
		</div>

		<div class="teams-modal-grid">
			<div class="form-group">
				<label class="control-label">Access role *</label>
				<select name="roleid" class="form-control" required>
					<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
					{foreach item=R from=$ROLES}
						<option value="{$R.roleid|escape}">{$R.rolename|escape}</option>
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

		<div class="form-group">
			<label class="control-label">Projects</label>
			<select name="projectid" class="form-control">
				<option value="">{vtranslate('LBL_NONE','Vtiger')}</option>
				{foreach item=P from=$PROJECTS}
					<option value="{$P.projectid|escape}">{$P.projectname|escape}</option>
				{/foreach}
			</select>
		</div>

		<div class="form-group">
			<label class="control-label">Timezone *</label>
			<select name="time_zone" class="form-control" required>
				<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
				{foreach item=TZ from=$TIMEZONES}
					<option value="{$TZ|escape}">{$TZ|escape}</option>
				{/foreach}
			</select>
		</div>

		<div class="form-group">
			<label class="control-label">Password *</label>
			<input type="password" name="password" class="form-control" required placeholder="Enter password" />
		</div>

		<div class="modal-footer" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; text-align: right;">
			<button type="submit" class="btn btn-success">
				<strong>Save</strong>
			</button>
			<button type="button" class="btn btn-default" data-dismiss="modal">
				Cancel
			</button>
		</div>
	</form>
</div>
{/strip}
