{strip}
{* Cache-bust: đổi ?v= khi sửa CSS/JS để refresh tải bản mới *}
<link rel="stylesheet" type="text/css" href="layouts/v7/modules/Teams/resources/teams.css?v=2" media="screen" />

<div class="teams-page-container">
	<!-- Modern Header Section -->
	<div class="teams-modern-header">
		<div class="teams-header-content">
			<div class="teams-header-left">
				<h1 class="teams-main-title">
					<i class="fa fa-users" aria-hidden="true"></i>
					<span>Teams</span>
				</h1>
				<p class="teams-subtitle">Manage people and groups</p>
			</div>
			<div class="teams-header-right">
				{if $CAN_ADD_GROUP}
					<button type="button" class="btn btn-default teams-action-btn js-add-group" data-url="index.php?module=Teams&view=AddGroup&app=Management">
						<i class="fa fa-plus" aria-hidden="true"></i>
						{vtranslate('LBL_ADD_GROUP','Vtiger')}
					</button>
				{/if}
				{if $CAN_ADD_PERSON}
					<button type="button" class="btn btn-primary teams-action-btn js-add-person" data-url="index.php?module=Teams&view=People&app=Management&mode=modal">
						<i class="fa fa-user-plus" aria-hidden="true"></i>
						{vtranslate('LBL_ADD','Vtiger')} {vtranslate('LBL_PERSON','Vtiger')}
					</button>
				{/if}
			</div>
		</div>
	</div>

	<!-- Modern Tabs Navigation -->
	<div class="teams-modern-tabs">
		<ul class="nav nav-tabs teams-tabs-nav" role="tablist">
			<li role="presentation" class="{if $ACTIVE_TAB eq 'people'}active{/if}">
				<a href="index.php?module=Teams&view=List&tab=people&app=Management" aria-controls="people" role="tab">
					<i class="fa fa-users" aria-hidden="true"></i>
					<span>People</span>
				</a>
			</li>
			<li role="presentation" class="{if $ACTIVE_TAB eq 'groups'}active{/if}">
				<a href="index.php?module=Teams&view=List&tab=groups&app=Management" aria-controls="groups" role="tab">
					<i class="fa fa-object-group" aria-hidden="true"></i>
					<span>Groups</span>
				</a>
			</li>
			<li role="presentation" class="{if $ACTIVE_TAB eq 'settings'}active{/if}">
				<a href="index.php?module=Teams&view=List&tab=settings&app=Management" aria-controls="settings" role="tab">
					<i class="fa fa-cog" aria-hidden="true"></i>
					<span>Settings</span>
				</a>
			</li>
		</ul>
	</div>

	<!-- Content Area: tab People = giao diện ProofHub (sidebar + main), còn lại giữ cũ -->
	<div class="teams-content-wrapper">
		{if $ACTIVE_TAB eq 'people'}
			<div class="teams-people-layout">
				<div class="teams-people-sidebar">
					<h3 class="teams-people-sidebar-title">{vtranslate('LBL_PEOPLE','Teams')}</h3>
					<div class="teams-people-groups-section">
						<div class="teams-people-groups-header">
							<span class="teams-people-groups-label">
								<i class="fa fa-users" aria-hidden="true"></i>
								{vtranslate('LBL_GROUPS','Teams')}
							</span>
							<button type="button" class="teams-sidebar-icon js-add-group" data-url="index.php?module=Teams&view=AddGroup&app=Management" title="{vtranslate('LBL_ADD_GROUP','Vtiger')}"><i class="fa fa-plus"></i></button>
							<span class="teams-sidebar-icon" title="{vtranslate('LBL_SEARCH','Vtiger')}"><i class="fa fa-search"></i></span>
						</div>
						<ul class="teams-people-groups-list">
							<li class="{if $SELECTED_GROUP_ID eq 0}active{/if}">
								<a href="index.php?module=Teams&view=List&tab=people&app=Management">
									{vtranslate('LBL_ALL_PEOPLE','Teams')}
									<span class="teams-group-count">{$ALL_PEOPLE_ACTIVE_COUNT} {vtranslate('LBL_ACTIVE','Teams')}</span>
								</a>
							</li>
							{foreach item=GRP from=$GROUPS_SIDEBAR}
							<li class="{if $SELECTED_GROUP_ID eq $GRP.groupid}active{/if}">
								<a href="index.php?module=Teams&view=List&tab=people&app=Management&groupid={$GRP.groupid}">
									{$GRP.group_name|decode_html}
									<span class="teams-group-count">{$GRP.active_count} {vtranslate('LBL_ACTIVE','Teams')}</span>
								</a>
							</li>
							{/foreach}
						</ul>
					</div>
				</div>
				<div class="teams-people-main">
					<div class="teams-people-main-header">
						<span class="teams-people-current-view">
							<i class="fa fa-list" aria-hidden="true"></i>
							{if $SELECTED_GROUP_ID eq 0}
								{vtranslate('LBL_ALL_PEOPLE','Teams')}
							{else}
								{foreach item=GRP from=$GROUPS_SIDEBAR}
									{if $SELECTED_GROUP_ID eq $GRP.groupid}{$GRP.group_name|decode_html}{/if}
								{/foreach}
							{/if}
						</span>
						{if $CAN_ADD_PERSON}
						<div class="teams-people-add-wrap">
							<button type="button" class="btn btn-primary teams-people-add-btn js-add-person" data-url="index.php?module=Teams&view=People&app=Management&mode=modal">
								<i class="fa fa-plus" aria-hidden="true"></i> {vtranslate('LBL_ADD','Vtiger')}
							</button>
						</div>
						{/if}
						<input type="text" class="teams-people-search form-control" placeholder="{vtranslate('LBL_SEARCH_PEOPLE','Teams')}..." id="teams-people-search-input" />
						<div class="teams-people-filter-dropdown dropdown">
							<span class="teams-people-filter-icon dropdown-toggle" id="teams-people-filter-toggle" data-toggle="dropdown" title="{vtranslate('LBL_FILTER','Vtiger')}"><i class="fa fa-filter"></i></span>
							<ul class="dropdown-menu dropdown-menu-right teams-people-filter-menu" aria-labelledby="teams-people-filter-toggle">
								<li class="dropdown-header teams-filter-section">{vtranslate('LBL_PEOPLE_JOINED_COMPANY','Teams')}</li>
								<li><a href="#" class="teams-tenure-option" data-tenure="all">{vtranslate('LBL_TENURE_ALL','Teams')}</a></li>
								<li><a href="#" class="teams-tenure-option" data-tenure="under1">{vtranslate('LBL_TENURE_UNDER_1','Teams')}</a></li>
								<li><a href="#" class="teams-tenure-option" data-tenure="1-4">{vtranslate('LBL_TENURE_1_4','Teams')}</a></li>
								<li><a href="#" class="teams-tenure-option" data-tenure="5-7">{vtranslate('LBL_TENURE_5_7','Teams')}</a></li>
								<li><a href="#" class="teams-tenure-option" data-tenure="7-10">{vtranslate('LBL_TENURE_7_10','Teams')}</a></li>
							</ul>
						</div>
						<span class="teams-people-more-icon" title="{vtranslate('LBL_MORE','Vtiger')}"><i class="fa fa-ellipsis-v"></i></span>
					</div>
					<div class="teams-people-content">
						{include file='partials/People.tpl'|@vtemplate_path:$MODULE}
					</div>
				</div>
			</div>
		{else}
			<div class="teams-content">
				{if $ACTIVE_TAB eq 'groups'}
					{include file='partials/Groups.tpl'|@vtemplate_path:$MODULE}
				{elseif $ACTIVE_TAB eq 'settings'}
					{include file='partials/Settings.tpl'|@vtemplate_path:$MODULE}
				{/if}
			</div>
		{/if}
	</div>
</div>
{/strip}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Teams/resources/TeamsModal.js')}?v=2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Teams/resources/Group.js')}?v=2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Teams/resources/Person.js')}?v=2"></script>
<script type="text/javascript">
{literal}
jQuery(document).ready(function($) {
	// Initialize Bootstrap popover for project count
	$('.teams-project-count').each(function() {
		var $el = $(this);
		var userId = $el.data('userid');
		
		// Find corresponding script tag with projects data
		var $script = $('script.teams-projects-data[data-userid="' + userId + '"]');
		if ($script.length > 0) {
			try {
				var projectsData = JSON.parse($script.html());
				var projects = projectsData.projects || [];
				var count = projectsData.count || projects.length;
				
				if (projects.length > 0) {
					var html = '<div style="max-width: 300px;"><strong>Projects (' + count + '):</strong><ul style="margin: 8px 0 0 0; padding-left: 20px;">';
					
					$.each(projects, function(i, proj) {
						html += '<li style="margin: 4px 0;">' + $('<div>').text(proj.name || '').html();
						if (proj.status) {
							html += ' <span class="label label-default" style="font-size: 10px; margin-left: 6px;">' + $('<div>').text(proj.status).html() + '</span>';
						}
						html += '</li>';
					});
					
					html += '</ul></div>';
					
					$el.attr('data-content', html);
				}
			} catch(e) {
				console.error('[Teams] Error parsing projects data for user', userId, e);
			}
		}
	});
	
	$('.teams-project-count').popover({
		container: 'body',
		html: true
	});
	
	// Clean up popovers when page unloads
	$(window).on('beforeunload', function() {
		$('.teams-project-count').popover('destroy');
	});

	// Years from join date (YYYY-MM-DD) to today
	function getYearsInCompany(dateStr) {
		if (!dateStr || dateStr === '') return null;
		var join = new Date(dateStr);
		if (isNaN(join.getTime())) return null;
		var now = new Date();
		var years = (now - join) / (365.25 * 24 * 60 * 60 * 1000);
		return years;
	}
	function passesTenureFilter(years, tenure) {
		if (tenure === 'all') return true;
		if (years === null) return false;
		if (tenure === 'under1') return years >= 0 && years < 1;
		if (tenure === '1-4') return years >= 1 && years < 5;
		if (tenure === '5-7') return years >= 5 && years < 8;
		if (tenure === '7-10') return years >= 7 && years <= 10;
		return true;
	}
	var currentTenureFilter = 'all';
	function applyPeopleFilters() {
		var q = $('#teams-people-search-input').val().toLowerCase();
		$('.teams-people-row').each(function() {
			var $row = $(this);
			var dateJoined = $row.attr('data-date-joined') || '';
			var years = getYearsInCompany(dateJoined);
			var passTenure = passesTenureFilter(years, currentTenureFilter);
			var name = ($row.find('.teams-people-name-link').text() + ' ' + $row.find('.teams-people-email-cell').text()).toLowerCase();
			var passSearch = q === '' || name.indexOf(q) !== -1;
			$row.toggle(passTenure && passSearch);
		});
		$('.js-role-toggle').each(function() {
			var role = $(this).data('role');
			var $rows = $('.teams-role-row[data-role="' + role + '"]');
			var anyVisible = $rows.filter(function() { return $(this).is(':visible'); }).length > 0;
			$(this).toggle(anyVisible);
		});
	}
	$('#teams-people-search-input').on('keyup', function() {
		applyPeopleFilters();
	});
	$('.teams-tenure-option').on('click', function(e) {
		e.preventDefault();
		currentTenureFilter = $(this).data('tenure') || 'all';
		$('.teams-tenure-option').removeClass('active');
		$(this).addClass('active');
		applyPeopleFilters();
	});
	$('.teams-tenure-option[data-tenure="all"]').addClass('active');

	// ProofHub: collapse/expand role sections
	$('.js-role-toggle').on('click', function() {
		var role = $(this).data('role');
		var $rows = $('.teams-role-row[data-role="' + role + '"]');
		var $chevron = $(this).find('.teams-role-chevron');
		if ($rows.hasClass('teams-role-collapsed')) {
			$rows.removeClass('teams-role-collapsed');
			$chevron.removeClass('fa-chevron-right').addClass('fa-chevron-down');
		} else {
			$rows.addClass('teams-role-collapsed');
			$chevron.removeClass('fa-chevron-down').addClass('fa-chevron-right');
		}
	});

	// ProofHub: select all checkbox
	$('.teams-people-select-all').on('change', function() {
		$('.teams-people-row-select').prop('checked', $(this).prop('checked'));
	});
	
	// Auto-refresh status every 5 seconds (only on People tab) - more frequent for better real-time updates
	if (window.location.href.indexOf('tab=people') !== -1 || window.location.href.indexOf('tab=') === -1) {
		var statusRefreshInterval = setInterval(function() {
			// Get latest status from server
			AppConnector.request({
				module: 'Teams',
				action: 'PersonAjax',
				mode: 'getStatus'
			}).done(function(response) {
				if (response && response.success && response.result && response.result.status_map) {
					var statusMap = response.result.status_map;
					
					// Update status for each user row
					$('tbody tr[data-userid]').each(function() {
						var $row = $(this);
						var $statusCell = $row.find('td:nth-child(6)'); // Last active column
						
						// Get user ID from data attribute
						var userId = parseInt($row.attr('data-userid'));
						
						if (userId && statusMap[userId]) {
							var status = statusMap[userId];
							var newHtml = '';
							
							if (status.is_inactive) {
								newHtml = '<span class="label label-danger">Inactive</span>';
							} else if (status.is_online) {
								newHtml = '<span class="label label-success" style="display: inline-flex; align-items: center; gap: 6px;"><span class="dot-online"></span><span>Online</span></span>';
							} else if (status.status_label === 'Never logged in') {
								newHtml = '<span class="text-muted"><em>Never logged in</em></span>';
							} else {
								newHtml = '<span class="text-muted" style="display: inline-flex; align-items: center; gap: 6px;"><span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #999; margin-right: 2px;"></span><span>' + $('<div>').text(status.status_label).html() + '</span></span>';
							}
							
							$statusCell.html(newHtml);
						}
					});
				}
			}).fail(function(err) {
				console.error('[Teams] Status refresh failed', err);
			});
		}, 5000); // Every 5 seconds - more frequent for better real-time updates
		
		// Clear interval when leaving page
		$(window).on('beforeunload', function() {
			clearInterval(statusRefreshInterval);
		});
	}
});
{/literal}
</script>
