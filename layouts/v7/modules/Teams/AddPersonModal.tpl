{strip}
<div class="teams-modal teams-add-person-modal">
	<div class="teams-modal-header">
		<p class="teams-modal-kicker">Management · Nhân sự</p>
		<h3 class="teams-modal-title">Tạo tài khoản mới</h3>
		<p class="teams-modal-subtitle">Điền thông tin và chọn vai trò để cấp quyền truy cập CRM.</p>
	</div>
	<form id="EditView" class="js-teams-add-person-form teams-modal-form" method="post" action="index.php">
		<input type="hidden" name="module" value="Teams" />
		<input type="hidden" name="action" value="SavePerson" />
		<input type="hidden" name="app" value="Management" />

		<div class="teams-modal-section">
			<h4 class="teams-modal-section-title">Thông tin cá nhân</h4>
			<div class="teams-modal-grid">
				<div class="form-group">
					<label class="control-label">Họ *</label>
					<input type="text" name="first_name" class="form-control" required placeholder="Nhập họ" autocomplete="given-name" />
				</div>
				<div class="form-group">
					<label class="control-label">Tên *</label>
					<input type="text" name="last_name" class="form-control" required placeholder="Nhập tên" autocomplete="family-name" />
				</div>
			</div>
			<div class="form-group teams-modal-field">
				<label class="control-label">Email *</label>
				<input type="email" name="email" class="form-control" required placeholder="name@congty.com" autocomplete="email" />
			</div>
			<div class="teams-modal-grid teams-modal-field">
				<div class="form-group">
					<label class="control-label">Chức danh *</label>
					<input type="text" name="title" class="form-control" required placeholder="VD: Nhân viên Sale" />
				</div>
				<div class="form-group">
					<label class="control-label">{vtranslate('LBL_DATE_JOINED_COMPANY','Teams')}</label>
					<input type="date" name="date_joined_company" class="form-control" />
				</div>
			</div>
		</div>

		<div class="teams-modal-section">
			<h4 class="teams-modal-section-title">Quyền truy cập</h4>
			<div class="teams-modal-grid">
				<div class="form-group">
					<label class="control-label">Vai trò *</label>
					<select name="roleid" class="form-control" required>
						<option value="">Chọn vai trò</option>
						{foreach item=R from=$ROLES}
							<option value="{$R.roleid|escape}">{$R.rolename|decode_html|escape}</option>
						{/foreach}
					</select>
					<p class="teams-modal-field-hint">Sale mới chọn vai trò <strong>Sale</strong> để vào vòng Round Robin Lead.</p>
				</div>
				<div class="form-group">
					<label class="control-label">Múi giờ</label>
					<select name="time_zone" class="form-control">
						{foreach item=TZ from=$TIMEZONES}
							<option value="{$TZ|escape}" {if $TZ eq 'Asia/Ho_Chi_Minh'}selected{/if}>{$TZ|escape}</option>
						{/foreach}
					</select>
				</div>
			</div>
		</div>

		<div class="teams-modal-section">
			<h4 class="teams-modal-section-title">Bảo mật</h4>
			<div class="form-group">
				<label class="control-label">Mật khẩu *</label>
				<input type="password" name="password" class="form-control" required placeholder="Nhập mật khẩu đăng nhập" autocomplete="new-password" />
			</div>
		</div>

		<div class="teams-modal-footer">
			<button type="button" class="teams-btn teams-btn--outline" data-dismiss="modal">Huỷ</button>
			<button type="submit" class="teams-btn teams-btn--primary">Tạo tài khoản</button>
		</div>
	</form>
</div>
{/strip}
