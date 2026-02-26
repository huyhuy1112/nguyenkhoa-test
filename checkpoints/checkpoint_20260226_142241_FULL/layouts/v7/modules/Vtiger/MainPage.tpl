{strip}
<div class="container-fluid mainpage-wrap">
	<div class="mainpage-banner">
		<div class="mainpage-banner-inner">
			<span class="mainpage-banner-logo">TDB</span>
			<div class="mainpage-banner-text">
				<div class="mainpage-banner-title">TDB SOLUTION</div>
				<div class="mainpage-banner-subtitle">Management Dashboard</div>
			</div>
		</div>
	</div>
	{if $MAINPAGE_CAN_SEE_TEAM_STATUS}
	<form method="get" class="mainpage-global-filter" id="mainpage-global-filter-form">
		<input type="hidden" name="module" value="Home" />
		<input type="hidden" name="view" value="MainPage" />
		<div class="mainpage-global-filter-inner">
			<span class="mainpage-global-filter-title"><i class="fa fa-filter"></i> Bộ lọc trang:</span>
			<label class="mainpage-global-filter-label">Người phụ trách</label>
			<select name="team_filter_user" class="form-control input-sm mainpage-global-filter-select">
				<option value="">— Tất cả —</option>
				{foreach from=$MAINPAGE_TEAM_FILTER_OPTIONS.users item=u}
					<option value="{$u.id}"{if $MAINPAGE_TEAM_FILTER_USER == $u.id} selected="selected"{/if}>{$u.name|escape:'html'}</option>
				{/foreach}
			</select>
			<label class="mainpage-global-filter-label">Phòng ban</label>
			<select name="team_filter_department" class="form-control input-sm mainpage-global-filter-select">
				<option value="">— Tất cả —</option>
				{foreach from=$MAINPAGE_TEAM_FILTER_OPTIONS.departments item=d}
					<option value="{$d|escape:'html'}"{if $MAINPAGE_TEAM_FILTER_DEPARTMENT == $d} selected="selected"{/if}>{$d|escape:'html'}</option>
				{/foreach}
			</select>
			<label class="mainpage-global-filter-label">Thời gian</label>
			<input type="date" name="team_filter_date" class="form-control input-sm mainpage-global-filter-date" value="{$MAINPAGE_TEAM_FILTER_DATE|escape:'html'}" />
			<button type="submit" class="btn btn-primary btn-sm mainpage-global-filter-btn"><i class="fa fa-filter"></i> Lọc</button>
		</div>
	</form>
	{/if}
	<div class="mainpage-grid">
		<div class="mainpage-card announcements area-ann">
			<div class="card-header subtle">
				<div class="title"><i class="fa fa-bullhorn"></i> Announcements</div>
				<div class="actions">
					<button type="button" class="btn btn-default btn-xs" id="mainpage-announcement-add" title="Add announcement"><i class="fa fa-plus"></i> Add</button>
				</div>
			</div>
			<div class="card-body announcements-body ann-list-wrap">
				{if $MAINPAGE_ANNOUNCEMENTS|@count gt 0}
					<ul class="announcements-list list-unstyled">
						{foreach from=$MAINPAGE_ANNOUNCEMENTS item=ann}
							<li class="announcement-item ann-item-clickable" data-id="{$ann.id}" data-creatorid="{$ann.creatorid}" data-title="{$ann.title|escape:'html'}" data-creatorname="{$ann.creatorName|escape:'html'}" data-timeago="{$ann.timeAgo|escape:'html'}">
								<span class="ann-item-title">{$ann.title|escape:'html'|default:'(No title)'}</span>
								<span class="ann-item-time">{$ann.timeAgo|escape:'html'}</span>
							</li>
						{/foreach}
					</ul>
				{else}
					<div class="ann-empty text-muted small">Chưa có dữ liệu. Thêm thông báo bằng nút Add.</div>
				{/if}
			</div>
		</div>

		<div class="modal fade" id="mainpage-announcement-detail-modal" tabindex="-1" role="dialog">
			<div class="modal-dialog ann-detail-dialog" role="document">
				<div class="modal-content">
					<div class="modal-body ann-detail-body">
						<button type="button" class="close ann-detail-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<div class="row">
							<div class="col-sm-8 ann-detail-left">
								<div class="ann-detail-author">
									<span class="ann-avatar ann-avatar-user" id="ann-detail-avatar">H</span>
									<span class="ann-detail-name" id="ann-detail-name">-</span>
									<span class="ann-detail-time-badge" id="ann-detail-time">-</span>
								</div>
								<div class="ann-detail-content">
									<h5 class="ann-detail-title" id="ann-detail-title"></h5>
									<div class="ann-detail-desc" id="ann-detail-desc"></div>
								</div>
							</div>
							<div class="col-sm-4 ann-detail-right">
								<div class="ann-detail-tabs">
									<button type="button" class="ann-tab active" data-tab="comments">Comments <span class="badge" id="ann-detail-comments-badge">0</span></button>
									<button type="button" class="ann-tab" data-tab="subscribers">Subscribers <span class="badge" id="ann-detail-subscribers-badge">0</span></button>
								</div>
								<div id="ann-detail-panel-comments" class="ann-detail-panel">
									<ul class="ann-comments-list list-unstyled" id="ann-detail-comments-list"></ul>
									<div class="ann-add-comment">
										<div class="task-comment-toolbar"><button type="button" class="btn btn-default btn-xs ann-detail-comment-upload-btn" title="Upload from computer"><span class="fa fa-paperclip"></span> Upload</button>
										<input type="file" class="ann-detail-comment-file-input" accept="*" style="display:none">
										<span class="ann-detail-comment-file-name text-muted small"></span></div>
										<textarea class="form-control" id="ann-detail-comment-input" rows="2" placeholder="Write a comment"></textarea>
										<button type="button" class="btn btn-primary btn-sm" id="ann-detail-comment-add">Add</button>
									</div>
								</div>
								<div id="ann-detail-panel-subscribers" class="ann-detail-panel hide">
									<div class="ann-subscribers-list" id="ann-detail-subscribers-list"></div>
									<div id="ann-detail-subscriber-manage" class="hide">
										<div class="ann-subscriber-tags" id="ann-detail-subscriber-tags"></div>
										<label class="small">Thêm người nhận:</label>
										<select class="form-control select2 ann-detail-add-subscriber" id="ann-detail-add-subscriber" style="width:100%;" multiple="multiple">
											<optgroup label="Groups">
												{foreach from=$MAINPAGE_ACCESSIBLE_GROUPS key=gid item=gname}
													<option value="g_{$gid}" data-type="group" data-initial="G" data-name="{$gname|escape:'html'}">{$gname|escape:'html'}</option>
												{/foreach}
											</optgroup>
											<optgroup label="Users">
												{foreach from=$MAINPAGE_ASSIGNABLE_USERS key=uid item=uname}
													{assign var="initial" value=$uname|substr:0:1|upper}
													<option value="u_{$uid}" data-type="user" data-name="{$uname|escape:'html'}">{$uname|escape:'html'}</option>
												{/foreach}
											</optgroup>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer ann-detail-footer ann-detail-creator-actions hide">
						<button type="button" class="btn btn-danger" id="ann-detail-delete-btn" title="Xóa thông báo"><i class="fa fa-trash-o"></i> Xóa thông báo</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade ann-add-modal" id="mainpage-announcement-modal" tabindex="-1" role="dialog">
			<div class="modal-dialog ann-add-modal-dialog" role="document">
				<div class="modal-content ann-add-modal-content">
					<div class="modal-header ann-add-modal-header">
						<h4 class="modal-title"><i class="fa fa-bullhorn"></i> Thêm thông báo</h4>
						<button type="button" class="close ann-add-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body ann-add-modal-body">
						<form id="mainpage-announcement-form" class="ann-add-form">
							<div class="form-group ann-form-group">
								<label for="ann-title" class="ann-label">Tiêu đề</label>
								<input type="text" class="form-control ann-input" id="ann-title" name="title" placeholder="Nhập tiêu đề thông báo" />
							</div>
							<div class="form-group ann-form-group">
								<label for="ann-description" class="ann-label">Nội dung</label>
								<textarea class="form-control ckEditorSource ann-input ann-textarea" id="ann-description" name="announcement" rows="5" placeholder="Mô tả chi tiết..."></textarea>
							</div>
							<div class="form-group ann-form-group">
								<label for="ann-subscribers" class="ann-label">Người nhận</label>
								<select class="form-control select2 ann-subscribers-select ann-input" id="ann-subscribers" name="ann_subscribers[]" multiple="multiple" style="width:100%;">
									<optgroup label="Groups">
										{foreach from=$MAINPAGE_ACCESSIBLE_GROUPS key=gid item=gname}
											<option value="g_{$gid}" data-type="group" data-initial="G" data-name="{$gname|escape:'html'}">{$gname|escape:'html'}</option>
										{/foreach}
									</optgroup>
									<optgroup label="Users">
										{foreach from=$MAINPAGE_ASSIGNABLE_USERS key=uid item=uname}
											{assign var="initial" value=$uname|substr:0:1|upper}
											<option value="u_{$uid}" data-type="user" data-name="{$uname|escape:'html'}"{if $uid == $MAINPAGE_CURRENT_USER_ID} data-me="1"{/if}>{if $uid == $MAINPAGE_CURRENT_USER_ID}{$uname|escape:'html'} (Me){else}{$uname|escape:'html'}{/if}</option>
										{/foreach}
									</optgroup>
								</select>
								<small class="ann-hint">Chọn User hoặc Group. Để trống = gửi cho tất cả.</small>
							</div>
							<div class="ann-add-row">
								<div class="form-group ann-form-group ann-form-group-inline">
									<label for="ann-lasts" class="ann-label">Thời gian hiển thị</label>
									<select class="form-control ann-input ann-select" id="ann-lasts" name="lasts">
										<option value="24">24 giờ</option>
										<option value="48">48 giờ</option>
										<option value="168">7 ngày</option>
										<option value="0">Đến khi xóa</option>
									</select>
								</div>
								<div class="ann-check-group">
									<label class="ann-checkbox-label"><input type="checkbox" id="ann-allow-comments" name="allow_comments" class="ann-checkbox" checked /> Cho phép bình luận</label>
									<label class="ann-checkbox-label"><input type="checkbox" id="ann-pin" name="pin" class="ann-checkbox" /> Ghim lên đầu</label>
								</div>
							</div>
						</form>
					</div>
					<div class="modal-footer ann-add-modal-footer">
						<button type="button" class="btn btn-default ann-btn-cancel" data-dismiss="modal">Hủy</button>
						<button type="button" class="btn btn-primary ann-btn-submit" id="mainpage-announcement-submit"><i class="fa fa-paper-plane-o"></i> Đăng thông báo</button>
					</div>
				</div>
			</div>
		</div>

		<div class="mainpage-card shortcuts area-shortcuts">
			<div class="card-header subtle">
				<div class="title"><i class="fa fa-star-o"></i> My shortcuts</div>
			</div>
			<div class="card-body shortcuts-grid">
				<a href="{$MAINPAGE_LINKS.projecttask_list}" class="shortcut"><i class="fa fa-check-square-o text-primary"></i><span>My tasks</span>{if $MAINPAGE_TASK_COUNT gt 0}<span class="badge blue">{$MAINPAGE_TASK_COUNT}</span>{/if}</a>
				<a href="{$MAINPAGE_LINKS.calendar}" class="shortcut"><i class="fa fa-calendar text-primary"></i><span>My events & milestones</span></a>
				<a href="{$MAINPAGE_LINKS.projecttask_list}" class="shortcut"><i class="fa fa-clock-o text-primary"></i><span>Thời gian phiên</span></a>
				<a href="{$MAINPAGE_LINKS.calendar}" class="shortcut"><i class="fa fa-list-alt text-primary"></i><span>My activities</span></a>
				<a href="{$MAINPAGE_LINKS.home}" class="shortcut"><i class="fa fa-sticky-note-o text-primary"></i><span>Stickies</span></a>
				<a href="{$MAINPAGE_LINKS.home}" class="shortcut"><i class="fa fa-bookmark-o text-primary"></i><span>Bookmarks</span></a>
			</div>
		</div>

		<div class="mainpage-card projects area-projects">
			<div class="card-header subtle">
				<div class="title"><i class="fa fa-folder-open-o"></i> My projects</div>
				<div class="actions">
					<a href="{$MAINPAGE_LINKS.project_list}" class="btn btn-default btn-xs">Xem tất cả</a>
				</div>
			</div>
			<div class="table-responsive">
				<table class="table mainpage-table table-hover">
					<thead>
						<tr>
							<th style="width: 46%;">Title</th>
							<th>Start</th>
							<th>End</th>
							<th style="width: 60px;">Status</th>
						</tr>
					</thead>
					<tbody>
						{if $MAINPAGE_PROJECTS|@count gt 0}
							{foreach from=$MAINPAGE_PROJECTS item=p}
								<tr>
									<td><a href="{$p.url}" class="text-primary">{$p.title|escape:'html'}</a></td>
									<td>{$p.startdate|escape:'html'}</td>
									<td>{$p.enddate|escape:'html'}</td>
									<td><span class="status-pill gray">{$p.status|escape:'html'|default:'-'}</span></td>
								</tr>
							{/foreach}
						{else}
							<tr><td colspan="4" class="text-muted text-center">Chưa có dữ liệu. <a href="{$MAINPAGE_LINKS.project_list}">Tạo project</a></td></tr>
						{/if}
					</tbody>
				</table>
			</div>
		</div>

		<div class="mainpage-card agenda area-agenda">
			<div class="card-header subtle">
				<div class="title"><i class="fa fa-calendar-check-o"></i> Agenda</div>
				<div class="tab-group agenda-tabs">
					<span class="tab active" data-agenda-panel="today">Today</span>
					<span class="tab" data-agenda-panel="upcoming">Upcoming</span>
					<a href="{$MAINPAGE_LINKS.calendar}" class="tab" target="_blank" rel="noopener noreferrer" title="Quá hạn - Công việc/lịch đã qua ngày hẹn">Overdue <span class="small text-muted">(Quá hạn)</span></a>
				</div>
			</div>
			<div class="card-body">
				<div id="agenda-panel-today" class="agenda-panel">
					{if $MAINPAGE_AGENDA|@count gt 0}
						<ul class="agenda-list list-unstyled">
							{foreach from=$MAINPAGE_AGENDA item=a}
								<li class="agenda-item agenda-item-row" {if $a.color}style="border-left: 3px solid {$a.color};"{/if}>
									<a href="{$a.url}" class="text-primary agenda-item-title">{$a.title|escape:'html'}</a>
									<span class="agenda-date text-muted">{$a.dateDisplay|escape:'html'}</span>
									{if $a.timeDisplay}<span class="agenda-time text-muted">{$a.timeDisplay|escape:'html'}</span>{/if}
									{if $a.type}<span class="label label-default">{$a.type}</span>{/if}
								</li>
							{/foreach}
						</ul>
					{else}
						<div class="agenda-empty text-muted small">Chưa có lịch hôm nay. <a href="{$MAINPAGE_LINKS.calendar}">Mở lịch (Schedule)</a></div>
					{/if}
					<a href="{$MAINPAGE_LINKS.calendar}" class="btn btn-default btn-xs">Mở lịch (Schedule)</a>
				</div>
				<div id="agenda-panel-upcoming" class="agenda-panel hide">
					{if $MAINPAGE_AGENDA_UPCOMING|@count gt 0}
						<ul class="agenda-list list-unstyled">
							{foreach from=$MAINPAGE_AGENDA_UPCOMING item=a}
								<li class="agenda-item agenda-item-row" {if $a.color}style="border-left: 3px solid {$a.color};"{/if}>
									<a href="{$a.url}" class="text-primary agenda-item-title">{$a.title|escape:'html'}</a>
									<span class="agenda-date text-muted">{$a.dateDisplay|escape:'html'}</span>
									{if $a.timeDisplay}<span class="agenda-time text-muted">{$a.timeDisplay|escape:'html'}</span>{/if}
									{if $a.type}<span class="label label-default">{$a.type}</span>{/if}
								</li>
							{/foreach}
						</ul>
					{else}
						<div class="agenda-empty text-muted small">Chưa có lịch sắp tới (ngày mai, ngày kia...). <a href="{$MAINPAGE_LINKS.calendar}">Mở lịch (Schedule)</a></div>
					{/if}
					<a href="{$MAINPAGE_LINKS.calendar}" class="btn btn-default btn-xs" target="_blank" rel="noopener noreferrer">Mở lịch (Schedule)</a>
				</div>
			</div>
		</div>
		<script type="text/javascript">
		(function(){
			var tabs = document.querySelectorAll('.agenda-tabs [data-agenda-panel]');
			var panels = document.querySelectorAll('.agenda-panel');
			if (!tabs.length || !panels.length) return;
			function showPanel(id) {
				panels.forEach(function(p) {
					p.classList.add('hide');
					if (p.id === 'agenda-panel-' + id) p.classList.remove('hide');
				});
				tabs.forEach(function(t) {
					t.classList.toggle('active', t.getAttribute('data-agenda-panel') === id);
				});
			}
			tabs.forEach(function(t) {
				t.addEventListener('click', function() { showPanel(this.getAttribute('data-agenda-panel')); });
			});
		})();
		</script>

		<div class="mainpage-card tasks area-tasks">
			<div class="card-header subtle">
				<div class="title"><i class="fa fa-list-ul"></i> My tasks</div>
				<div class="actions">
					<a href="{$MAINPAGE_LINKS.projecttask_list}" class="btn btn-default btn-xs">Xem tất cả</a>
				</div>
			</div>
			<div class="table-responsive">
				<table class="table mainpage-table">
					<thead>
						<tr>
							<th>Title</th>
							<th>Due date</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						{if $MAINPAGE_TASKS|@count gt 0}
							{foreach from=$MAINPAGE_TASKS item=t}
								<tr>
									<td><a href="{$t.url}" class="text-primary">{$t.title|escape:'html'}</a></td>
									<td>{$t.duedate|escape:'html'}</td>
									<td><span class="status-pill gray">{$t.status|escape:'html'|default:'-'}</span></td>
								</tr>
							{/foreach}
						{else}
							<tr><td colspan="3" class="text-muted text-center">Chưa có dữ liệu. <a href="{$MAINPAGE_LINKS.projecttask_list}">Tạo task</a></td></tr>
						{/if}
					</tbody>
				</table>
			</div>
		</div>

		<div class="mainpage-card time area-time">
			<div class="card-header subtle">
				<div class="title-block">
					<div class="title"><i class="fa fa-users"></i> Team Status</div>
					<div class="subtitle text-muted small">Nhân sự đi làm, vắng mặt, làm ở nhà, đi công tác,…</div>
				</div>
			</div>
			<div class="card-body time-grid">
				{if $MAINPAGE_CAN_SEE_TEAM_STATUS}
					{* CEO/Admin: block Người nghỉ phép (nếu có) + toàn bộ trạng thái *}
					{if $MAINPAGE_TEAM_STATUS_LEAVE_ONLY|@count gt 0}
						<div class="team-status-leave-block">
							<div class="team-status-leave-block-title"><i class="fa fa-calendar-minus-o"></i> Người nghỉ phép ({$MAINPAGE_TEAM_FILTER_DATE_DISPLAY|escape:'html'})</div>
							<ul class="team-status-list team-status-list-leave list-unstyled">
								{foreach from=$MAINPAGE_TEAM_STATUS_LEAVE_ONLY item=member}
									<li class="team-status-item team-status-leave">
										<span class="team-status-avatar">{$member.initial|escape:'html'}</span>
										<span class="team-status-name">{$member.name|escape:'html'}</span>
										<span class="team-status-badge status-leave">{$member.status_label|escape:'html'}</span>
										{if $member.leave_note}<span class="team-status-leave-note text-muted">— {$member.leave_note|escape:'html'}</span>{/if}
									</li>
								{/foreach}
							</ul>
						</div>
					{/if}
					<div class="team-status-legend text-muted small">
						<span class="team-status-legend-item"><i class="fa fa-circle status-online"></i> Online</span>
						<span class="team-status-legend-item"><i class="fa fa-circle status-offline"></i> Offline</span>
						<span class="team-status-legend-item"><i class="fa fa-calendar-minus-o status-leave"></i> Ngày nghỉ phép</span>
					</div>
					<div class="team-status-list-wrap">
						{if $MAINPAGE_TEAM_STATUS|@count gt 0}
							<ul class="team-status-list list-unstyled">
								{foreach from=$MAINPAGE_TEAM_STATUS item=member}
									<li class="team-status-item team-status-{$member.status}">
										<span class="team-status-avatar">{$member.initial|escape:'html'}</span>
										<span class="team-status-name">{$member.name|escape:'html'}</span>
										<span class="team-status-badge status-{$member.status}">{$member.status_label|escape:'html'}</span>
										{if $member.leave_note}<span class="team-status-leave-note text-muted">({$member.leave_note|escape:'html'})</span>{/if}
									</li>
								{/foreach}
							</ul>
						{else}
							<div class="text-muted small">Chưa có dữ liệu thành viên.</div>
						{/if}
					</div>
				{else}
					{* User thường: xem thời gian phiên + lịch sử đăng nhập của mình *}
					{if $MAINPAGE_LOGIN_TIMESTAMP gt 0}
						<div class="logged-time-wrap">
							<div class="logged-time-value" id="mainpage-logged-time-display">{$MAINPAGE_LOGGED_TIME_DISPLAY|escape:'html'}</div>
							<div class="logged-time-label">Đã đăng nhập từ lúc bắt đầu phiên</div>
							<input type="hidden" id="mainpage-login-timestamp" value="{$MAINPAGE_LOGIN_TIMESTAMP}" />
						</div>
					{else}
						<div class="time-empty text-muted small">Đăng nhập lại để bắt đầu tính thời gian phiên làm việc.</div>
					{/if}
					<div class="login-history-section">
						<div class="login-history-title">Lịch sử đăng nhập</div>
						{if $MAINPAGE_LOGIN_HISTORY|@count gt 0}
							<ul class="login-history-list list-unstyled">
								{foreach from=$MAINPAGE_LOGIN_HISTORY item=hist}
									<li class="login-history-item">
										<span class="hist-time">{$hist.login_display|escape:'html'} → {$hist.logout_display|escape:'html'}</span>
										{if $hist.duration_display != '-'}<span class="hist-duration">{$hist.duration_display|escape:'html'}</span>{/if}
										<span class="hist-status status-{if $hist.status == 'Signed off'}off{else}on{/if}">{$hist.status|escape:'html'}</span>
									</li>
								{/foreach}
							</ul>
						{else}
							<div class="text-muted small">Chưa có lịch sử.</div>
						{/if}
					</div>
				{/if}
			</div>
		</div>

		<div class="mainpage-card time area-time-demo">
			<div class="card-header subtle">
				<div class="title-block">
					<div class="title"><i class="fa fa-users"></i> Test Team Status</div>
					<div class="subtitle text-muted small">Ví dụ demo Online / Offline / Nghỉ phép (không dùng dữ liệu thật)</div>
				</div>
			</div>
			<div class="card-body time-grid">
				<div class="team-status-legend text-muted small">
					<span class="team-status-legend-item"><i class="fa fa-circle status-online"></i> Online</span>
					<span class="team-status-legend-item"><i class="fa fa-circle status-offline"></i> Offline</span>
					<span class="team-status-legend-item"><i class="fa fa-calendar-minus-o status-leave"></i> Nghỉ phép</span>
				</div>
				<div class="team-status-list-wrap">
					<ul class="team-status-list list-unstyled">
						<li class="team-status-item team-status-online">
							<span class="team-status-avatar">HA</span>
							<span class="team-status-name">Huy Admin (Online demo)</span>
							<span class="team-status-badge status-online">Online</span>
						</li>
						<li class="team-status-item team-status-offline">
							<span class="team-status-avatar">NB</span>
							<span class="team-status-name">Nam Bán hàng (Offline demo)</span>
							<span class="team-status-badge status-offline">Offline</span>
						</li>
						<li class="team-status-item team-status-leave">
							<span class="team-status-avatar">LT</span>
							<span class="team-status-name">Lan Trợ lý (Nghỉ phép demo)</span>
							<span class="team-status-badge status-leave">Nghỉ phép</span>
							<span class="team-status-leave-note text-muted">— Nghỉ phép năm (3 ngày)</span>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<div class="mainpage-card kpi area-kpi">
			<div class="card-header subtle">
				<div class="title"><i class="fa fa-bar-chart"></i> KPI</div>
				<div class="actions kpi-actions">
					<select class="form-control input-sm kpi-chart-select" id="mainpage-kpi-type" style="max-width:140px;">
						<option value="bar-ngang">Bar ngang</option>
						<option value="bar-doc">Bar dọc</option>
						<option value="tron">Biểu đồ tròn</option>
						<option value="donut">Donut</option>
						<option value="duong">Đường</option>
						<option value="stacked">Bar xếp chồng</option>
					</select>
					<select class="form-control input-sm kpi-chart-select" id="mainpage-kpi-select" style="max-width:180px;">
						<option value="kinhdoanh">Phòng Kinh doanh</option>
						<option value="kythuat">Phòng Kỹ thuật</option>
						<option value="nhansu">Phòng Nhân sự</option>
						<option value="ketoan">Phòng Kế toán</option>
						<option value="marketing">Phòng Marketing</option>
					</select>
				</div>
			</div>
			<div class="card-body kpi-body">
				<div id="kpi-chart-kinhdoanh" class="kpi-chart-panel" data-vals="85,72,91" data-colors="#059669,#10b981,#34d399" data-labels="Doanh thu T1,Chỉ tiêu bán hàng,Khách hàng mới"></div>
				<div id="kpi-chart-kythuat" class="kpi-chart-panel hide" data-vals="78,95,88" data-colors="#3b82f6,#60a5fa,#93c5fd" data-labels="Hoàn thành sprint,Code review,Bug fix rate"></div>
				<div id="kpi-chart-nhansu" class="kpi-chart-panel hide" data-vals="68,82,94" data-colors="#8b5cf6,#a78bfa,#c4b5fd" data-labels="Tuyển dụng,Đào tạo nội bộ,Tỷ lệ giữ chân"></div>
				<div id="kpi-chart-ketoan" class="kpi-chart-panel hide" data-vals="92,100,76" data-colors="#ec4899,#f472b6,#f9a8d4" data-labels="Thu chi cân đối,Báo cáo đúng hạn,Kiểm toán nội bộ"></div>
				<div id="kpi-chart-marketing" class="kpi-chart-panel hide" data-vals="89,65,80" data-colors="#f59e0b,#fbbf24,#fcd34d" data-labels="Lượt tiếp cận,Conversion rate,Brand awareness"></div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
(function() {
	/* Chuyển modal ra body để tránh backdrop che modal (stacking context) */
	var addModal = document.getElementById('mainpage-announcement-modal');
	var detailModalEl = document.getElementById('mainpage-announcement-detail-modal');
	if (addModal && addModal.parentNode !== document.body) document.body.appendChild(addModal);
	if (detailModalEl && detailModalEl.parentNode !== document.body) document.body.appendChild(detailModalEl);

	var addBtn = document.getElementById('mainpage-announcement-add');
	var modal = document.getElementById('mainpage-announcement-modal');
	var submitBtn = document.getElementById('mainpage-announcement-submit');
	var titleInput = document.getElementById('ann-title');
	var descInput = document.getElementById('ann-description');
	var $subSelect = jQuery('#ann-subscribers');
	if (!addBtn || !modal || !submitBtn) return;

	function subscriberOptionTemplate(id, text, element) {
		var type = 'user';
		var name = text || '';
		if (element) {
			type = element.getAttribute('data-type') || (id && id.indexOf('g_') === 0 ? 'group' : 'user');
			name = element.getAttribute('data-name') || element.textContent || text;
		} else if (id) {
			type = id.indexOf('g_') === 0 ? 'group' : 'user';
		}
		var initial = type === 'group' ? 'G' : (name ? name.charAt(0).toUpperCase() : '?');
		var circleClass = type === 'group' ? 'ann-sub-circle ann-sub-circle-group' : 'ann-sub-circle ann-sub-circle-user';
		return '<span class="ann-sub-option"><span class="' + circleClass + '">' + initial + '</span> ' + name + '</span>';
	}

	if ($subSelect.length && typeof $subSelect.select2 === 'function') {
		$subSelect.select2({
			placeholder: 'Chọn User hoặc Group...',
			allowClear: true,
			templateResult: function(state) {
				if (!state || !state.id) return state.text;
				return subscriberOptionTemplate(state.id, state.text, state.element);
			},
			templateSelection: function(data) {
				if (!data || !data.id) return data.text || '';
				return subscriberOptionTemplate(data.id, data.text, data.element);
			}
		});
	}

	var annDescCkId = 'ann-description';
	var ckEditorToolbar = [
		{ name: 'basic', items: [ 'Bold', 'Italic', 'Underline', '-', 'TextColor', 'BGColor', '-', 'Link', 'Unlink', '-', 'NumberedList', 'BulletedList', '-', 'Table', 'Smiley', '-', 'RemoveFormat' ] }
	];

	function initAnnouncementCkEditor() {
		if (typeof CKEDITOR === 'undefined' || typeof Vtiger_CkEditor_Js === 'undefined') return;
		var $ta = jQuery('#' + annDescCkId);
		if (!$ta.length) return;
		if (CKEDITOR.instances[annDescCkId]) {
			CKEDITOR.remove(CKEDITOR.instances[annDescCkId]);
		}
		var ck = new Vtiger_CkEditor_Js();
		ck.loadCkEditor($ta, {
			height: 180,
			toolbar: ckEditorToolbar
		});
	}

	function destroyAnnouncementCkEditor() {
		if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[annDescCkId]) {
			CKEDITOR.instances[annDescCkId].updateElement();
			CKEDITOR.remove(CKEDITOR.instances[annDescCkId]);
		}
	}

	function getAnnouncementContent() {
		if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[annDescCkId]) {
			return CKEDITOR.instances[annDescCkId].getData();
		}
		return descInput ? descInput.value : '';
	}

	jQuery(modal).on('shown.bs.modal', function() {
		setTimeout(function() { initAnnouncementCkEditor(); }, 100);
	}).on('hidden.bs.modal', function() {
		destroyAnnouncementCkEditor();
	});

	addBtn.addEventListener('click', function() {
		if (titleInput) titleInput.value = '';
		if (descInput) descInput.value = '';
		if ($subSelect.length && $subSelect.data('select2')) {
			$subSelect.val(null).trigger('change');
		}
		jQuery(modal).modal('show');
	});

	submitBtn.addEventListener('click', function() {
		var title = titleInput ? titleInput.value.trim() : '';
		var announcement = getAnnouncementContent();
		if (typeof announcement !== 'string') announcement = '';
		announcement = announcement.trim();
		var raw = $subSelect.length ? ($subSelect.val() || []) : [];
		var subscriberIds = [];
		var subscriberGroupIds = [];
		raw.forEach(function(v) {
			if (v && v.indexOf('u_') === 0) subscriberIds.push(v.replace('u_', ''));
			else if (v && v.indexOf('g_') === 0) subscriberGroupIds.push(v.replace('g_', ''));
		});
		var params = {
			module: 'Home',
			action: 'SaveAnnouncementAjax',
			title: title || 'Announcement',
			announcement: announcement,
			subscriber_ids: subscriberIds.length ? subscriberIds.join(',') : '',
			subscriber_group_ids: subscriberGroupIds.length ? subscriberGroupIds.join(',') : ''
		};
		submitBtn.disabled = true;
		app.request.post({ data: params }).then(function(err, data) {
			submitBtn.disabled = false;
			if (err) {
				app.helper.showAlert({ title: app.vtranslate('JS_MESSAGE'), text: err.message || 'Error saving.' });
				return;
			}
			jQuery(modal).modal('hide');
			if (app.helper && app.helper.showSuccessNotification) {
				app.helper.showSuccessNotification({ message: 'Announcement saved.' });
			}
			window.location.reload();
		});
	});

	var detailModal = document.getElementById('mainpage-announcement-detail-modal');
	var currentDetailId = null;
	var currentDetailAnnouncement = null;
	var $detailAddSub = jQuery('#ann-detail-add-subscriber');
	var detailAddSubSelect2Inited = false;
	var detailCommentsPollTimer = null;
	var DETAIL_POLL_INTERVAL_MS = 4000;

	if (!jQuery('#ann-comment-lightbox').length) {
		jQuery('body').append('<div id="ann-comment-lightbox" class="ann-comment-lightbox"><div class="ann-comment-lightbox-backdrop"></div><div class="ann-comment-lightbox-content"><button type="button" class="ann-comment-lightbox-close" aria-label="Close">&times;</button><img class="ann-comment-lightbox-img" alt="" /><a href="#" class="ann-comment-lightbox-download" target="_blank" download><span class="fa fa-download"></span> Download</a></div></div>');
		jQuery(document).on('click', '#mainpage-announcement-detail-modal .ann-comment-attachment a', function(e) {
			var link = jQuery(this);
			var img = link.find('img.ann-comment-img');
			if (img.length) {
				e.preventDefault();
				var src = img.attr('src');
				var href = link.attr('href');
				if (!src) return;
				var lb = jQuery('#ann-comment-lightbox');
				lb.find('.ann-comment-lightbox-img').attr('src', src);
				lb.find('.ann-comment-lightbox-download').attr('href', href || src).attr('download', '');
				lb.show();
			}
		});
		jQuery(document).on('click', '#ann-comment-lightbox .ann-comment-lightbox-close, #ann-comment-lightbox .ann-comment-lightbox-backdrop', function() {
			jQuery('#ann-comment-lightbox').hide();
		});
	}

	function renderDetailComments(comments) {
		var list = jQuery('#ann-detail-comments-list');
		list.empty();
		(comments || []).forEach(function(c) {
			var attHtml = '';
			(c.attachments || []).forEach(function(a) {
				var ext = (a.name || '').split('.').pop().toLowerCase();
				var isImg = /^(jpg|jpeg|png|gif|webp|bmp)$/.test(ext);
				var safeName = (a.name || 'file').replace(/</g,'&lt;').replace(/>/g,'&gt;');
				var url = (a.url || '#').replace(/"/g,'&quot;');
				var imgUrl = isImg ? url.replace('action=DownloadAnnouncementCommentFile','action=InlineAnnouncementCommentFile') : url;
				if (isImg) {
					attHtml += '<div class="ann-comment-attachment"><a href="' + url + '" target="_blank"><img src="' + imgUrl + '" alt="" class="ann-comment-img" /></a></div>';
				} else {
					attHtml += '<div class="ann-comment-attachment"><a href="' + url + '" target="_blank" class="ann-comment-file-link">' + safeName + '</a></div>';
				}
			});
			list.append('<li class="ann-comment-item"><span class="ann-avatar ann-avatar-user ann-avatar-sm">' + (c.userName ? c.userName.charAt(0).toUpperCase() : '?') + '</span> <span class="ann-comment-meta">' + (c.userName || '') + ' ' + (c.timeAgo || '') + '</span><div class="ann-comment-text">' + (c.comment_text || '').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>' + attHtml + '</li>');
		});
		jQuery('#ann-detail-comments-badge').text((comments || []).length);
	}

	function pollDetailComments() {
		if (!currentDetailId) return;
		app.request.post({ data: { module: 'Home', action: 'GetAnnouncementDetailAjax', id: currentDetailId } }).then(function(err, data) {
			if (err || !currentDetailId) return;
			var res = (data && data.result) ? data.result : (data || {});
			if (res.comments && res.announcement) {
				renderDetailComments(res.comments);
				var subCount = (res.announcement.subscribers && res.announcement.subscribers.length) ? res.announcement.subscribers.length : 0;
				jQuery('#ann-detail-subscribers-badge').text(subCount);
			}
		});
	}

	function startDetailCommentsPoll() {
		stopDetailCommentsPoll();
		detailCommentsPollTimer = setInterval(pollDetailComments, DETAIL_POLL_INTERVAL_MS);
	}

	function stopDetailCommentsPoll() {
		if (detailCommentsPollTimer) {
			clearInterval(detailCommentsPollTimer);
			detailCommentsPollTimer = null;
		}
	}

	function renderSubscriberTags(subscribers) {
		var container = jQuery('#ann-detail-subscriber-tags');
		container.empty();
		if (!subscribers || !subscribers.length) return;
		subscribers.forEach(function(s) {
			var type = s.type || 'user';
			var id = s.id;
			var name = (s.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
			var initial = type === 'group' ? 'G' : (name ? name.charAt(0).toUpperCase() : '?');
			var circleClass = type === 'group' ? 'ann-sub-circle ann-sub-circle-group' : 'ann-sub-circle ann-sub-circle-user';
			var pill = jQuery('<span class="ann-sub-pill">' +
				'<span class="' + circleClass + '">' + initial + '</span> ' +
				'<span class="ann-sub-name">' + name + '</span> ' +
				'<button type="button" class="ann-sub-remove" data-type="' + type + '" data-id="' + id + '" aria-label="Remove">&times;</button></span>');
			container.append(pill);
		});
	}

	function initDetailAddSubscriberSelect2() {
		if (detailAddSubSelect2Inited || !$detailAddSub.length || typeof $detailAddSub.select2 !== 'function') return;
		$detailAddSub.select2({
			placeholder: 'Thêm User hoặc Group...',
			allowClear: true,
			templateResult: function(state) {
				if (!state || !state.id) return state.text;
				return subscriberOptionTemplate(state.id, state.text, state.element);
			},
			templateSelection: function(data) {
				if (!data || !data.id) return data.text || '';
				return subscriberOptionTemplate(data.id, data.text, data.element);
			}
		});
		detailAddSubSelect2Inited = true;
	}

	function openAnnouncementDetail(announcementId) {
		currentDetailId = announcementId;
		currentDetailAnnouncement = null;
		app.request.post({ data: { module: 'Home', action: 'GetAnnouncementDetailAjax', id: announcementId } }).then(function(err, data) {
			var res = (data && data.result) ? data.result : (data || {});
			if (err || !res.announcement) {
				if (app.helper && app.helper.showAlert) app.helper.showAlert({ title: '', text: (err && err.message) ? err.message : 'Cannot load.' });
				return;
			}
			var a = res.announcement;
			currentDetailAnnouncement = a;
			var comments = res.comments || [];
			var initial = (a.creatorName && a.creatorName.length) ? a.creatorName.charAt(0).toUpperCase() : '?';
			jQuery('#ann-detail-avatar').text(initial).attr('class', 'ann-avatar ann-avatar-user');
			jQuery('#ann-detail-name').text(a.creatorName || '-');
			jQuery('#ann-detail-time').text(a.timeAgo || '');
			jQuery('#ann-detail-title').text(a.title || '(No title)');
			jQuery('#ann-detail-desc').html(a.announcement || '');
			renderDetailComments(comments);
			var subCount = (a.subscribers && a.subscribers.length) ? a.subscribers.length : 0;
			jQuery('#ann-detail-subscribers-badge').text(subCount);
			var subList = jQuery('#ann-detail-subscribers-list');
			var manageDiv = jQuery('#ann-detail-subscriber-manage');
			var creatorActions = jQuery('.ann-detail-creator-actions');
			if (a.isCreator) {
				creatorActions.removeClass('hide');
				subList.empty().addClass('hide');
				manageDiv.removeClass('hide');
				renderSubscriberTags(a.subscribers || []);
				initDetailAddSubscriberSelect2();
				if ($detailAddSub.length && $detailAddSub.data('select2')) {
					$detailAddSub.val(null).trigger('change');
				}
			} else {
				creatorActions.addClass('hide');
				manageDiv.addClass('hide');
				subList.removeClass('hide').empty();
				if (a.assignedToUsersStr) subList.append('<div class="small"><strong>Users:</strong> ' + (a.assignedToUsersStr || '-') + '</div>');
				if (a.assignedToGroupsStr) subList.append('<div class="small"><strong>Groups:</strong> ' + (a.assignedToGroupsStr || '-') + '</div>');
				if (!a.assignedToUsersStr && !a.assignedToGroupsStr) subList.append('<div class="small text-muted">All</div>');
			}
			jQuery('#ann-detail-comment-input').val('');
			jQuery(detailModal).modal('show');
			startDetailCommentsPoll();
		});
	}

	jQuery(detailModal).on('hidden.bs.modal', function() {
		stopDetailCommentsPoll();
		currentDetailId = null;
		currentDetailAnnouncement = null;
	});

	jQuery(document).on('click', '.ann-item-clickable', function() {
		var id = jQuery(this).data('id');
		if (id) openAnnouncementDetail(id);
	});

	jQuery(detailModal).on('click', '.ann-tab', function() {
		var tab = jQuery(this).data('tab');
		jQuery(detailModal).find('.ann-tab').removeClass('active');
		jQuery(this).addClass('active');
		jQuery('#ann-detail-panel-comments').toggleClass('hide', tab !== 'comments');
		jQuery('#ann-detail-panel-subscribers').toggleClass('hide', tab !== 'subscribers');
	});

	function updateDetailSubscribers(subscriberIds, subscriberGroupIds, callback) {
		if (!currentDetailId) return;
		var uIds = Array.isArray(subscriberIds) ? subscriberIds : (subscriberIds || '').toString().split(',').filter(Boolean);
		var gIds = Array.isArray(subscriberGroupIds) ? subscriberGroupIds : (subscriberGroupIds || '').toString().split(',').filter(Boolean);
		app.request.post({
			data: {
				module: 'Home',
				action: 'UpdateAnnouncementSubscribersAjax',
				id: currentDetailId,
				subscriber_ids: uIds.join(','),
				subscriber_group_ids: gIds.join(',')
			}
		}).then(function(err, data) {
			if (err) {
				if (app.helper && app.helper.showAlert) app.helper.showAlert({ title: '', text: err.message || 'Error.' });
				return;
			}
			var res = (data && data.result) ? data.result : (data || {});
			if (res.announcement) {
				currentDetailAnnouncement = res.announcement;
				renderSubscriberTags(res.announcement.subscribers || []);
				jQuery('#ann-detail-subscribers-badge').text((res.announcement.subscribers && res.announcement.subscribers.length) ? res.announcement.subscribers.length : 0);
			}
			if (callback) callback(err, data);
		});
	}

	jQuery(detailModal).on('click', '.ann-sub-remove', function() {
		var btn = jQuery(this);
		var type = btn.data('type');
		var id = parseInt(btn.data('id'), 10);
		if (!currentDetailAnnouncement || !currentDetailAnnouncement.subscribers) return;
		var subs = currentDetailAnnouncement.subscribers.filter(function(s) {
			return !(String(s.type) === type && parseInt(s.id, 10) === id);
		});
		var userIds = [];
		var groupIds = [];
		subs.forEach(function(s) {
			if (s.type === 'group') groupIds.push(parseInt(s.id, 10));
			else userIds.push(parseInt(s.id, 10));
		});
		updateDetailSubscribers(userIds, groupIds);
	});

	$detailAddSub.on('change', function() {
		var selected = $detailAddSub.val();
		if (!selected || !selected.length || !currentDetailAnnouncement) return;
		var subs = (currentDetailAnnouncement.subscribers || []).slice();
		var existingKeys = {};
		subs.forEach(function(s) {
			existingKeys[s.type + '_' + s.id] = true;
		});
		selected.forEach(function(v) {
			if (!v) return;
			var type = v.indexOf('g_') === 0 ? 'group' : 'user';
			var id = parseInt(v.replace('u_', '').replace('g_', ''), 10);
			if (existingKeys[type + '_' + id]) return;
			existingKeys[type + '_' + id] = true;
			var opt = $detailAddSub.find('option[value="' + v.replace(/"/g, '\\"') + '"]');
			var name = opt.length ? (opt.attr('data-name') || opt.text()) : ('#' + id);
			subs.push({ type: type, id: id, name: name });
		});
		var userIds = [];
		var groupIds = [];
		subs.forEach(function(s) {
			if (s.type === 'group') groupIds.push(parseInt(s.id, 10));
			else userIds.push(parseInt(s.id, 10));
		});
		$detailAddSub.val(null).trigger('change');
		updateDetailSubscribers(userIds, groupIds);
	});

	jQuery('#ann-detail-delete-btn').on('click', function() {
		if (!currentDetailId) return;
		if (!confirm('Bạn có chắc muốn xóa thông báo này?')) return;
		var btn = this;
		btn.disabled = true;
		app.request.post({ data: { module: 'Home', action: 'DeleteAnnouncementAjax', id: currentDetailId } }).then(function(err, data) {
			btn.disabled = false;
			if (err) {
				if (app.helper && app.helper.showAlert) app.helper.showAlert({ title: '', text: err.message || 'Không thể xóa.' });
				return;
			}
			jQuery(detailModal).modal('hide');
			jQuery('.ann-item-clickable[data-id="' + currentDetailId + '"]').closest('li').fadeOut(300, function() {
				jQuery(this).remove();
			});
			if (app.helper && app.helper.showSuccessNotification) {
				app.helper.showSuccessNotification({ message: 'Đã xóa thông báo.' });
			}
			currentDetailId = null;
			currentDetailAnnouncement = null;
		});
	});

	jQuery(document).on('click', '.ann-detail-comment-upload-btn', function(e) {
		e.preventDefault();
		jQuery('#mainpage-announcement-detail-modal .ann-detail-comment-file-input').trigger('click');
	});
	jQuery(document).on('change', '#mainpage-announcement-detail-modal .ann-detail-comment-file-input', function() {
		var input = jQuery(this);
		var file = input[0].files && input[0].files[0];
		jQuery('.ann-detail-comment-file-name').text(file ? file.name : '').toggleClass('hidden', !file);
	});

	jQuery('#ann-detail-comment-add').on('click', function() {
		if (!currentDetailId) return;
		var text = jQuery('#ann-detail-comment-input').val();
		var fileInput = jQuery('#mainpage-announcement-detail-modal .ann-detail-comment-file-input')[0];
		var file = fileInput && fileInput.files && fileInput.files[0];
		if ((!text || !text.trim()) && !file) return;
		var btn = this;
		btn.disabled = true;
		var done = function(err, data) {
			btn.disabled = false;
			if (err) {
				if (app.helper && app.helper.showAlert) app.helper.showAlert({ title: '', text: err.message || 'Error.' });
				return;
			}
			jQuery('#ann-detail-comment-input').val('');
			jQuery('#mainpage-announcement-detail-modal .ann-detail-comment-file-input').val('');
			jQuery('.ann-detail-comment-file-name').text('').addClass('hidden');
			var res = (data && data.result) ? data.result : (data || {});
			renderDetailComments(res.comments || []);
		};
		if (file) {
			var fd = new FormData();
			fd.append('module', 'Home');
			fd.append('action', 'AddAnnouncementCommentAjax');
			fd.append('id', currentDetailId);
			fd.append('comment_text', (text || ' ').trim());
			fd.append('filename', file, file.name || 'file');
			jQuery.ajax({ url: 'index.php', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
				.done(function(data) { done(null, data); })
				.fail(function() { done({ message: 'Request failed' }); });
		} else {
			app.request.post({ data: { module: 'Home', action: 'AddAnnouncementCommentAjax', id: currentDetailId, comment_text: text.trim() } }).then(done);
		}
	});
})();

(function() {
	var el = document.getElementById('mainpage-logged-time-display');
	var tsEl = document.getElementById('mainpage-login-timestamp');
	if (!el || !tsEl) return;
	var loginTs = parseInt(tsEl.value, 10);
	if (!loginTs) return;
	function formatLoggedTime(seconds) {
		seconds = Math.floor(seconds);
		if (seconds < 60) return seconds + ' giây';
		if (seconds < 3600) return Math.floor(seconds / 60) + ' phút';
		var h = Math.floor(seconds / 3600);
		var m = Math.floor((seconds % 3600) / 60);
		return m > 0 ? h + 'h ' + m + 'm' : h + 'h';
	}
	function update() {
		var sec = Math.max(0, Math.floor(Date.now() / 1000) - loginTs);
		el.textContent = formatLoggedTime(sec);
	}
	update();
	setInterval(update, 60000);
})();

(function() {
	var selDept = document.getElementById('mainpage-kpi-select');
	var selType = document.getElementById('mainpage-kpi-type');
	var panels = document.querySelectorAll('.kpi-chart-panel');
	if (!selDept || !selType || !panels.length) return;

	var deptNames = { kinhdoanh:'Kinh doanh', kythuat:'Kỹ thuật', nhansu:'Nhân sự', ketoan:'Kế toán', marketing:'Marketing' };

	function parseData(el) {
		var vals = (el.getAttribute('data-vals') || '').split(',').map(function(v){ return parseInt(v,10) || 0; });
		var colors = (el.getAttribute('data-colors') || '').split(',');
		var labels = (el.getAttribute('data-labels') || '').split(',');
		return { vals: vals, colors: colors, labels: labels };
	}

	function renderChart(panel, type) {
		var d = parseData(panel);
		var vals = d.vals, colors = d.colors, labels = d.labels;
		var deptId = panel.id.replace('kpi-chart-','');
		var title = 'KPI Phòng ' + (deptNames[deptId] || deptId);
		panel.innerHTML = '<div class="kpi-chart-title">' + title + '</div>';
		panel.className = panel.className.replace(/\bkpi-type-\S+/g,'') + ' kpi-type-' + type;

		if (type === 'bar-ngang') {
			var wrap = document.createElement('div'); wrap.className = 'kpi-bar-chart';
			for (var i=0; i<vals.length; i++) {
				var row = document.createElement('div'); row.className = 'kpi-bar-row';
				row.innerHTML = '<span class="kpi-bar-label">' + (labels[i]||'') + '</span>' +
					'<div class="kpi-bar-wrap"><div class="kpi-bar" style="width:' + vals[i] + '%; background:' + (colors[i]||'#6366f1') + ';"></div>' +
					'<span class="kpi-bar-val">' + vals[i] + '%</span></div>';
				wrap.appendChild(row);
			}
			panel.appendChild(wrap);
		} else if (type === 'bar-doc') {
			var wrap = document.createElement('div'); wrap.className = 'kpi-bar-chart kpi-bar-vertical';
			var maxVal = Math.max.apply(null, vals);
			for (var i=0; i<vals.length; i++) {
				var row = document.createElement('div'); row.className = 'kpi-bar-row kpi-bar-row-vertical';
				var h = maxVal ? (vals[i] / maxVal * 100) : 0;
				row.innerHTML = '<span class="kpi-bar-label">' + (labels[i]||'') + '</span>' +
					'<div class="kpi-bar-vertical-wrap"><div class="kpi-bar-vertical" style="height:' + h + '%; background:' + (colors[i]||'#6366f1') + ';"></div>' +
					'<span class="kpi-bar-val">' + vals[i] + '%</span></div>';
				wrap.appendChild(row);
			}
			panel.appendChild(wrap);
		} else if (type === 'tron' || type === 'donut') {
			var total = vals.reduce(function(a,b){ return a+b; }, 0);
			if (!total) total = 1;
			var acc = 0;
			var segs = vals.map(function(v,i){ var start=acc; acc += (v/total)*360; return { start:start, end:acc, c:colors[i]||'#6366f1', l:labels[i], v:vals[i] }; });
			var conic = segs.map(function(s){ return s.c + ' ' + s.start + 'deg ' + s.end + 'deg'; }).join(', ');
			var pie = document.createElement('div');
			pie.className = 'kpi-pie-chart' + (type === 'donut' ? ' kpi-donut' : '');
			pie.style.background = 'conic-gradient(' + conic + ')';
			panel.appendChild(pie);
			var leg = document.createElement('div'); leg.className = 'kpi-pie-legend';
			segs.forEach(function(s,i){
				var sp = document.createElement('span'); sp.className = 'kpi-pie-legend-item';
				sp.innerHTML = '<i style="background:' + s.c + '"></i> ' + (s.l||'') + ' (' + s.v + '%)';
				leg.appendChild(sp);
			});
			panel.appendChild(leg);
		} else if (type === 'duong') {
			var wrap = document.createElement('div'); wrap.className = 'kpi-line-chart';
			var maxVal = Math.max.apply(null, vals);
			var svg = '<svg viewBox="0 0 300 120" preserveAspectRatio="none"><polyline fill="none" stroke="#6366f1" stroke-width="2" points="';
			var pts = vals.map(function(v,i){ var x = (i/(vals.length-1||1))*300; var y = 110 - (maxVal ? (v/maxVal)*100 : 0); return x + ',' + y; }).join(' ');
			svg += pts + '"/></svg>';
			wrap.innerHTML = svg;
			panel.appendChild(wrap);
			var rowWrap = document.createElement('div'); rowWrap.className = 'kpi-line-legend';
			for (var i=0; i<vals.length; i++) {
				var sp = document.createElement('span'); sp.className = 'kpi-line-legend-item';
				sp.innerHTML = '<i style="background:' + (colors[i]||'#6366f1') + '"></i> ' + (labels[i]||'') + ': ' + vals[i] + '%';
				rowWrap.appendChild(sp);
			}
			panel.appendChild(rowWrap);
		} else if (type === 'stacked') {
			var total = vals.reduce(function(a,b){ return a+b; }, 0);
			if (!total) total = 1;
			var wrap = document.createElement('div'); wrap.className = 'kpi-stacked-chart';
			var bar = document.createElement('div'); bar.className = 'kpi-stacked-bar';
			vals.forEach(function(v,i){
				var seg = document.createElement('div');
				seg.className = 'kpi-stacked-seg';
				seg.style.width = (v/total*100) + '%';
				seg.style.background = colors[i]||'#6366f1';
				seg.title = (labels[i]||'') + ': ' + v + '%';
				bar.appendChild(seg);
			});
			wrap.appendChild(bar);
			var leg = document.createElement('div'); leg.className = 'kpi-stacked-legend';
			vals.forEach(function(v,i){
				var sp = document.createElement('span'); sp.className = 'kpi-stacked-legend-item';
				sp.innerHTML = '<i style="background:' + (colors[i]||'#6366f1') + '"></i> ' + (labels[i]||'') + ' ' + v + '%';
				leg.appendChild(sp);
			});
			wrap.appendChild(leg);
			panel.appendChild(wrap);
		}
	}

	function refresh() {
		var dept = selDept.value;
		var type = selType.value;
		panels.forEach(function(p) {
			var visible = p.id === 'kpi-chart-' + dept;
			p.classList.toggle('hide', !visible);
			if (visible) { p.innerHTML = ''; renderChart(p, type); }
		});
	}

	selDept.addEventListener('change', refresh);
	selType.addEventListener('change', refresh);
	refresh();
})();
</script>
{/strip}
