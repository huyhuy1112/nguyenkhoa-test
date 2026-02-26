{strip}
<div class="col-sm-12 col-xs-12">
	<form class="form-horizontal" method="post" action="index.php">
		<input type="hidden" name="module" value="Teams" />
		<input type="hidden" name="action" value="SavePerson" />
		<input type="hidden" name="app" value="Management" />

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-4">First name *</label>
					<div class="col-sm-8">
						<input type="text" name="first_name" class="form-control" required />
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-4">Last name *</label>
					<div class="col-sm-8">
						<input type="text" name="last_name" class="form-control" required />
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-4">Email *</label>
					<div class="col-sm-8">
						<input type="email" name="email" class="form-control" required />
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-4">Title *</label>
					<div class="col-sm-8">
						<input type="text" name="title" class="form-control" required />
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-4">Password *</label>
					<div class="col-sm-8">
						<input type="password" name="password" class="form-control" required />
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-4">Timezone *</label>
					<div class="col-sm-8">
						<select name="time_zone" class="form-control" required>
							<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
							{foreach item=TZ from=$TIMEZONES}
								<option value="{$TZ|escape}">{$TZ|escape}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-4">{vtranslate('LBL_DATE_JOINED_COMPANY','Teams')}</label>
					<div class="col-sm-8">
						<input type="date" name="date_joined_company" class="form-control" />
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-4">Access role *</label>
					<div class="col-sm-8">
						<select name="roleid" class="form-control" required>
							<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
							{foreach item=R from=$ROLES}
								<option value="{$R.roleid|escape}">{$R.rolename|escape}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-4">Group *</label>
					<div class="col-sm-8">
						<select name="team_groupid" class="form-control" required>
							<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
							{foreach item=G from=$TEAM_GROUPS_LIST}
								<option value="{$G.groupid|escape}">{$G.group_name|escape}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="form-group">
			<div class="col-sm-offset-4 col-sm-8">
				<button type="submit" class="btn btn-success">{vtranslate('LBL_SAVE','Vtiger')}</button>
				<a class="btn btn-default" href="index.php?module=Teams&view=List&app=Management">{vtranslate('LBL_CANCEL','Vtiger')}</a>
			</div>
		</div>
	</form>
</div>
{/strip}
