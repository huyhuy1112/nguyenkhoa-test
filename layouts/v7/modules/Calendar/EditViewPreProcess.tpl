{* Calendar / Events full-page create/edit — MANAGEMENT dashboard shell (sidebar + topbar). *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-event-create-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Calendar/resources/EventMkEdit.css')}&mk_v=20260603_activity_edit" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Calendar/resources/EventMkEdit.js')}&mk_v=20260603_activity_edit"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-activity-create="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-event-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-Calendar mk-event-edit-page">
			<div class="editViewPageDiv viewContent mk-event-edit-inner">
{/strip}
