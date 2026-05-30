{strip}
<div class="mk-teams-page">
	<header class="mk-teams-page-header">
		<div class="mk-teams-page-header__text">
			<div class="mk-teams-breadcrumb" aria-label="Breadcrumb">
				<span>{vtranslate($MODULE, $MODULE)}</span>
			</div>
			<h1 class="mk-teams-page-title">Teams</h1>
			<p class="mk-teams-page-subtitle">Manage people and groups</p>
		</div>
		<div class="mk-teams-page-header__actions">
			{if $CAN_ADD_GROUP}
				<button type="button" class="mk-teams-btn mk-teams-btn--outline js-add-group" data-url="index.php?module=Teams&view=AddGroup&app=MANAGEMENT">
					<span class="mk-teams-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='IMPORT'}</span>
					<span class="mk-teams-btn__txt">{vtranslate('LBL_ADD_GROUP','Vtiger')}</span>
				</button>
			{/if}
			{if $CAN_ADD_PERSON}
				<button type="button" class="mk-teams-btn mk-teams-btn--primary js-add-person" data-url="index.php?module=Teams&view=People&app=MANAGEMENT&mode=modal">
					<span class="mk-teams-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='PLUS'}</span>
					<span class="mk-teams-btn__txt">{vtranslate('LBL_ADD','Vtiger')} {vtranslate('LBL_PERSON','Teams')}</span>
				</button>
			{/if}
		</div>
	</header>

	<nav class="mk-teams-tabs" role="tablist" aria-label="Teams sections">
		<a class="mk-teams-tab {if $ACTIVE_TAB eq 'people'}is-active{/if}" href="index.php?module=Teams&view=List&tab=people&app=MANAGEMENT" role="tab" {if $ACTIVE_TAB eq 'people'}aria-current="page"{/if}>
			<span class="mk-teams-tab__label">People</span>
		</a>
		<a class="mk-teams-tab {if $ACTIVE_TAB eq 'groups'}is-active{/if}" href="index.php?module=Teams&view=List&tab=groups&app=MANAGEMENT" role="tab" {if $ACTIVE_TAB eq 'groups'}aria-current="page"{/if}>
			<span class="mk-teams-tab__label">Groups</span>
		</a>
		<a class="mk-teams-tab {if $ACTIVE_TAB eq 'settings'}is-active{/if}" href="index.php?module=Teams&view=List&tab=settings&app=MANAGEMENT" role="tab" {if $ACTIVE_TAB eq 'settings'}aria-current="page"{/if}>
			<span class="mk-teams-tab__label">Settings</span>
		</a>
	</nav>

	<div class="mk-teams-body">
		{if $ACTIVE_TAB eq 'people'}
			<div class="mk-teams-people-layout">
				<aside class="mk-teams-groups-panel" aria-label="Groups">
					<h2 class="mk-teams-groups-panel__title">{vtranslate('LBL_PEOPLE','Teams')}</h2>
					<div class="mk-teams-groups-panel__head">
						<span class="mk-teams-groups-panel__label">{vtranslate('LBL_GROUPS','Teams')}</span>
						<div class="mk-teams-groups-panel__tools">
							{if $CAN_ADD_GROUP}
								<button type="button" class="mk-teams-icon-btn js-add-group" data-url="index.php?module=Teams&view=AddGroup&app=MANAGEMENT" title="{vtranslate('LBL_ADD_GROUP','Vtiger')}">
									<i class="fa fa-plus" aria-hidden="true"></i>
								</button>
							{/if}
							<button type="button" class="mk-teams-icon-btn" id="mk-teams-groups-search-toggle" title="{vtranslate('LBL_SEARCH','Vtiger')}">
								<i class="fa fa-search" aria-hidden="true"></i>
							</button>
						</div>
					</div>
					<input type="search" class="mk-teams-groups-search" id="mk-teams-groups-search" placeholder="{vtranslate('LBL_SEARCH','Vtiger')} groups..." hidden />
					<ul class="mk-teams-groups-list">
						<li class="mk-teams-groups-item {if $SELECTED_GROUP_ID eq 0}is-active{/if}">
							<a href="index.php?module=Teams&view=List&tab=people&app=MANAGEMENT" class="mk-teams-groups-link">
								<span class="mk-teams-groups-link__name">{vtranslate('LBL_ALL_PEOPLE','Teams')}</span>
								<span class="mk-teams-groups-link__meta">{$ALL_PEOPLE_ACTIVE_COUNT} {vtranslate('LBL_ACTIVE','Teams')}</span>
							</a>
						</li>
						{foreach item=GRP from=$GROUPS_SIDEBAR}
							<li class="mk-teams-groups-item {if $SELECTED_GROUP_ID eq $GRP.groupid}is-active{/if}" data-group-name="{$GRP.group_name|lower|escape}">
								<a href="index.php?module=Teams&view=List&tab=people&app=MANAGEMENT&groupid={$GRP.groupid}" class="mk-teams-groups-link">
									<span class="mk-teams-groups-link__name">{$GRP.group_name|decode_html}</span>
									<span class="mk-teams-groups-link__meta">{$GRP.active_count} {vtranslate('LBL_ACTIVE','Teams')}</span>
								</a>
							</li>
						{/foreach}
					</ul>
				</aside>

				<section class="mk-teams-people-panel">
					<div class="mk-teams-people-toolbar">
						<h2 class="mk-teams-people-toolbar__title">
							{if $SELECTED_GROUP_ID eq 0}
								{vtranslate('LBL_ALL_PEOPLE','Teams')}
							{else}
								{foreach item=GRP from=$GROUPS_SIDEBAR}
									{if $SELECTED_GROUP_ID eq $GRP.groupid}{$GRP.group_name|decode_html}{/if}
								{/foreach}
							{/if}
						</h2>
						<div class="mk-teams-people-toolbar__actions">
							<div class="mk-teams-search-wrap">
								<i class="fa fa-search mk-teams-search-wrap__icon" aria-hidden="true"></i>
								<input type="search" class="mk-teams-search-input" placeholder="{vtranslate('LBL_SEARCH_PEOPLE','Teams')}..." id="teams-people-search-input" autocomplete="off" />
							</div>
							<div class="mk-teams-filter-dropdown dropdown">
								<button type="button" class="mk-teams-icon-btn dropdown-toggle" id="teams-people-filter-toggle" data-toggle="dropdown" title="{vtranslate('LBL_FILTER','Vtiger')}">
									<i class="fa fa-filter" aria-hidden="true"></i>
								</button>
								<ul class="dropdown-menu dropdown-menu-right mk-teams-filter-menu" aria-labelledby="teams-people-filter-toggle">
									<li class="dropdown-header">{vtranslate('LBL_PEOPLE_JOINED_COMPANY','Teams')}</li>
									<li><a href="#" class="teams-tenure-option" data-tenure="all">{vtranslate('LBL_TENURE_ALL','Teams')}</a></li>
									<li><a href="#" class="teams-tenure-option" data-tenure="under1">{vtranslate('LBL_TENURE_UNDER_1','Teams')}</a></li>
									<li><a href="#" class="teams-tenure-option" data-tenure="1-4">{vtranslate('LBL_TENURE_1_4','Teams')}</a></li>
									<li><a href="#" class="teams-tenure-option" data-tenure="5-7">{vtranslate('LBL_TENURE_5_7','Teams')}</a></li>
									<li><a href="#" class="teams-tenure-option" data-tenure="7-10">{vtranslate('LBL_TENURE_7_10','Teams')}</a></li>
								</ul>
							</div>
							{if $CAN_ADD_PERSON}
								<button type="button" class="mk-teams-btn mk-teams-btn--primary mk-teams-btn--sm js-add-person" data-url="index.php?module=Teams&view=People&app=MANAGEMENT&mode=modal">
									<span class="mk-teams-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='PLUS'}</span>
									<span class="mk-teams-btn__txt">{vtranslate('LBL_ADD','Vtiger')}</span>
								</button>
							{/if}
						</div>
					</div>
					<div class="mk-teams-table-card">
						{include file='partials/People.tpl'|@vtemplate_path:$MODULE}
					</div>
				</section>
			</div>
		{else}
			<div class="mk-teams-table-card mk-teams-table-card--solo">
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
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Teams/resources/TeamsModal.js')}?v=3"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Teams/resources/Group.js')}?v=3"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Teams/resources/Person.js')}?v=3"></script>
<script type="text/javascript">
{literal}
jQuery(document).ready(function($) {
	$('#mk-teams-groups-search-toggle').on('click', function() {
		var $input = $('#mk-teams-groups-search');
		$input.prop('hidden', !$input.prop('hidden'));
		if (!$input.prop('hidden')) {
			$input.focus();
		}
	});
	$('#mk-teams-groups-search').on('input', function() {
		var q = $(this).val().toLowerCase();
		$('.mk-teams-groups-item[data-group-name]').each(function() {
			var name = $(this).attr('data-group-name') || '';
			$(this).toggle(q === '' || name.indexOf(q) !== -1);
		});
	});

	$('.teams-project-count').each(function() {
		var $el = $(this);
		var userId = $el.data('userid');
		var $script = $('script.teams-projects-data[data-userid="' + userId + '"]');
		if ($script.length > 0) {
			try {
				var projectsData = JSON.parse($script.html());
				var projects = projectsData.projects || [];
				var count = projectsData.count || projects.length;
				if (projects.length > 0) {
					var html = '<div style="max-width:300px;"><strong>Projects (' + count + '):</strong><ul style="margin:8px 0 0;padding-left:18px;">';
					$.each(projects, function(i, proj) {
						html += '<li style="margin:4px 0;">' + $('<div>').text(proj.name || '').html();
						if (proj.status) {
							html += ' <span style="font-size:11px;color:#40627e;">' + $('<div>').text(proj.status).html() + '</span>';
						}
						html += '</li>';
					});
					html += '</ul></div>';
					$el.attr('data-content', html);
				}
			} catch (e) {
				console.error('[Teams] projects popover', e);
			}
		}
	});
	$('.teams-project-count').popover({ container: 'body', html: true });
	$(window).on('beforeunload', function() {
		$('.teams-project-count').popover('destroy');
	});

	function getYearsInCompany(dateStr) {
		if (!dateStr) return null;
		var join = new Date(dateStr);
		if (isNaN(join.getTime())) return null;
		return (new Date() - join) / (365.25 * 24 * 60 * 60 * 1000);
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
			var years = getYearsInCompany($row.attr('data-date-joined') || '');
			var passTenure = passesTenureFilter(years, currentTenureFilter);
			var name = ($row.find('.teams-people-name-link').text() + ' ' + $row.find('.teams-people-email-cell').text()).toLowerCase();
			$row.toggle(passTenure && (q === '' || name.indexOf(q) !== -1));
		});
		$('.js-role-toggle').each(function() {
			var role = $(this).data('role');
			var anyVisible = $('.teams-role-row[data-role="' + role + '"]').filter(function() { return $(this).is(':visible'); }).length > 0;
			$(this).toggle(anyVisible);
		});
	}
	$('#teams-people-search-input').on('input keyup', applyPeopleFilters);
	$('.teams-tenure-option').on('click', function(e) {
		e.preventDefault();
		currentTenureFilter = $(this).data('tenure') || 'all';
		$('.teams-tenure-option').removeClass('active');
		$(this).addClass('active');
		applyPeopleFilters();
	});
	$('.teams-tenure-option[data-tenure="all"]').addClass('active');

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
	$('.teams-people-select-all').on('change', function() {
		$('.teams-people-row-select').prop('checked', $(this).prop('checked'));
	});

	if (window.location.href.indexOf('tab=people') !== -1 || window.location.href.indexOf('tab=') === -1) {
		var statusRefreshInterval = setInterval(function() {
			AppConnector.request({ module: 'Teams', action: 'PersonAjax', mode: 'getStatus' }).done(function(response) {
				if (response && response.success && response.result && response.result.status_map) {
					var statusMap = response.result.status_map;
					$('tbody tr[data-userid]').each(function() {
						var $row = $(this);
						var $statusCell = $row.find('td:nth-child(6)');
						var userId = parseInt($row.attr('data-userid'), 10);
						if (userId && statusMap[userId]) {
							var status = statusMap[userId];
							var newHtml = '';
							if (status.is_inactive) {
								newHtml = '<span class="mk-teams-badge mk-teams-badge--danger">Inactive</span>';
							} else if (status.is_online) {
								newHtml = '<span class="mk-teams-badge mk-teams-badge--online"><span class="mk-teams-status-dot"></span>Online</span>';
							} else if (status.status_label === 'Never logged in') {
								newHtml = '<span class="mk-teams-muted">Never logged in</span>';
							} else {
								newHtml = '<span class="mk-teams-muted"><span class="mk-teams-status-dot mk-teams-status-dot--away"></span>' + $('<div>').text(status.status_label).html() + '</span>';
							}
							$statusCell.html(newHtml);
						}
					});
				}
			});
		}, 5000);
		$(window).on('beforeunload', function() { clearInterval(statusRefreshInterval); });
	}
});
{/literal}
</script>
