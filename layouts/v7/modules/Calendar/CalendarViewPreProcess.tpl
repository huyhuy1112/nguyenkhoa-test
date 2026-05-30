{* Calendar (SUPPORT): split shell + topbar — UI theo mockup Figma/SVG *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-cal-ui-ready');</script>
<script type="text/javascript">window.__MK_CAL_UI_BUILD__ = "20260529_year_light";</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
{* cache-bust to force browser reload *}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Calendar/resources/Calendar.css')}&mk_v=20260529_year_light" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Calendar/resources/CalendarMkView.css')}&mk_v=20260529_year_light" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Calendar/resources/CalendarMkView.js')}&mk_v=20260529_year_fit"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-calendar-ui="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-cal-main" id="mk-dash-main" role="main">
			<div class="main-container main-container-Calendar mk-cal-page">
				<div class="CalendarViewPageDiv content-area mk-cal-content-area" id="CalendarViewContent">
{/strip}