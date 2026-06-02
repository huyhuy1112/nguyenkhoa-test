{* Task Management (MANAGEMENT): split shell + topbar — đồng bộ Main Page / Leads *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-task-mgmt-ui-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-task-mgmt-ui="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Calendar/resources/TaskManagementMkView.css')}&mk_v=20260601_task_mgmt_filters" />
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-task-mgmt-main" id="mk-dash-main" role="main">
			<div class="main-container main-container-{$MODULE} mk-task-mgmt-page">
				<div class="mk-task-mgmt-content" id="mk-task-mgmt-content">
