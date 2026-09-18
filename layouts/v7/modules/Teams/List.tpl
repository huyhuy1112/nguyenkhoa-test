{strip}
<div class="mk-teams-page">
	<header class="mk-teams-action-header" role="region" aria-label="Nhân sự">
		<div class="mk-teams-action-header__text">
			<p class="mk-teams-action-header__eyebrow">Management</p>
			<h1 class="mk-teams-action-header__title">Nhân sự</h1>
			<p class="mk-teams-action-header__subtitle">Quản lý tài khoản và vai trò truy cập trong CRM</p>
		</div>
		<div class="mk-teams-action-header__actions">
			{if $CAN_ADD_PERSON}
				<button type="button" class="mk-teams-btn mk-teams-btn--primary js-add-person" data-url="index.php?module=Teams&view=People&app=MANAGEMENT&mode=modal">
					<span class="mk-teams-btn__ic" aria-hidden="true">+</span>
					<span class="mk-teams-btn__txt">Thêm tài khoản</span>
				</button>
			{/if}
		</div>
	</header>

	<section class="mk-teams-filters-card" aria-label="Bộ lọc">
		<div class="mk-teams-filters-row">
			<label class="mk-teams-search" for="teams-people-search-input">
				<span class="mk-teams-search__ic" aria-hidden="true"><i class="fa fa-search"></i></span>
				<input type="search" class="mk-teams-search__input" id="teams-people-search-input" placeholder="{vtranslate('LBL_SEARCH_PEOPLE','Teams')}..." autocomplete="off" />
			</label>
			<div class="mk-teams-filter-dropdown dropdown">
				<button type="button" class="mk-teams-btn mk-teams-btn--outline dropdown-toggle" id="teams-people-filter-toggle" data-toggle="dropdown">
					<span class="mk-teams-btn__txt">{vtranslate('LBL_PEOPLE_JOINED_COMPANY','Teams')}</span>
					<i class="fa fa-angle-down" aria-hidden="true"></i>
				</button>
				<ul class="dropdown-menu dropdown-menu-right mk-teams-filter-menu" aria-labelledby="teams-people-filter-toggle">
					<li><a href="#" class="teams-tenure-option" data-tenure="all">{vtranslate('LBL_TENURE_ALL','Teams')}</a></li>
					<li><a href="#" class="teams-tenure-option" data-tenure="under1">{vtranslate('LBL_TENURE_UNDER_1','Teams')}</a></li>
					<li><a href="#" class="teams-tenure-option" data-tenure="1-4">{vtranslate('LBL_TENURE_1_4','Teams')}</a></li>
					<li><a href="#" class="teams-tenure-option" data-tenure="5-7">{vtranslate('LBL_TENURE_5_7','Teams')}</a></li>
					<li><a href="#" class="teams-tenure-option" data-tenure="7-10">{vtranslate('LBL_TENURE_7_10','Teams')}</a></li>
				</ul>
			</div>
		</div>
	</section>

	<section class="mk-teams-table-card">
		{include file='partials/People.tpl'|@vtemplate_path:$MODULE}
	</section>
</div>
{/strip}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Teams/resources/TeamsModal.js')}?v=6"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Teams/resources/Person.js')}?v=6"></script>
<script type="text/javascript">
{literal}
jQuery(document).ready(function($) {
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
		var label = $(this).text();
		$('#teams-people-filter-toggle .mk-teams-btn__txt').text(label);
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

	var statusRefreshInterval = setInterval(function() {
		AppConnector.request({ module: 'Teams', action: 'PersonAjax', mode: 'getStatus' }).done(function(response) {
			if (response && response.success && response.result && response.result.status_map) {
				var statusMap = response.result.status_map;
				$('tbody tr[data-userid]').each(function() {
					var $row = $(this);
					var $statusCell = $row.find('.teams-people-lastactive-cell');
					var userId = parseInt($row.attr('data-userid'), 10);
					if (userId && statusMap[userId]) {
						var status = statusMap[userId];
						var newHtml = '';
						if (status.is_inactive) {
							newHtml = '<span class="mk-teams-badge mk-teams-badge--danger">Ngừng hoạt động</span>';
						} else if (status.is_online) {
							newHtml = '<span class="mk-teams-badge mk-teams-badge--online"><span class="mk-teams-status-dot"></span>Trực tuyến</span>';
						} else if (status.status_label === 'Never logged in') {
							newHtml = '<span class="mk-teams-muted">Chưa đăng nhập</span>';
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
});
{/literal}
</script>
