{strip}
<div class="col-sm-12 col-xs-12">
	<div class="teams-add-person-page">
		<p class="teams-add-person-page__eyebrow">Management · Nhân sự</p>
		<h2 class="teams-add-person-page__title">Tạo tài khoản mới</h2>
		<p class="teams-add-person-page__desc">Điền thông tin và chọn vai trò để cấp quyền truy cập CRM.</p>
		<form class="form-horizontal js-teams-add-person-form" method="post" action="index.php">
			<input type="hidden" name="module" value="Teams" />
			<input type="hidden" name="action" value="SavePerson" />
			<input type="hidden" name="app" value="Management" />

			<div class="row">
				<div class="col-sm-6">
					<div class="form-group">
						<label class="control-label col-sm-4">Họ *</label>
						<div class="col-sm-8">
							<input type="text" name="first_name" class="form-control" required placeholder="Nhập họ" />
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label class="control-label col-sm-4">Tên *</label>
						<div class="col-sm-8">
							<input type="text" name="last_name" class="form-control" required placeholder="Nhập tên" />
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-6">
					<div class="form-group">
						<label class="control-label col-sm-4">Email *</label>
						<div class="col-sm-8">
							<input type="email" name="email" class="form-control" required placeholder="name@congty.com" />
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label class="control-label col-sm-4">Chức danh *</label>
						<div class="col-sm-8">
							<input type="text" name="title" class="form-control" required placeholder="VD: Nhân viên Sale" />
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
						<label class="control-label col-sm-4">Vai trò *</label>
						<div class="col-sm-8">
							<select name="roleid" class="form-control" required>
								<option value="">Chọn vai trò</option>
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
						<label class="control-label col-sm-4">Mật khẩu *</label>
						<div class="col-sm-8">
							<input type="password" name="password" class="form-control" required autocomplete="new-password" />
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label class="control-label col-sm-4">Múi giờ</label>
						<div class="col-sm-8">
							<select name="time_zone" class="form-control">
								{foreach item=TZ from=$TIMEZONES}
									<option value="{$TZ|escape}" {if $TZ eq 'Asia/Ho_Chi_Minh'}selected{/if}>{$TZ|escape}</option>
								{/foreach}
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="form-group">
				<div class="col-sm-offset-4 col-sm-8">
					<button type="submit" class="btn btn-success">Tạo tài khoản</button>
					<a class="btn btn-default" href="index.php?module=Teams&view=List&app=Management">Huỷ</a>
				</div>
			</div>
		</form>
	</div>
</div>
{/strip}
