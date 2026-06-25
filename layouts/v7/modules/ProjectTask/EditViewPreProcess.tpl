{* ProjectTask Create (MANAGEMENT app): dashboard split shell — sidebar + topbar. *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-projecttask-create-ready', 'mk-projecttask-edit-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProjectTask/resources/ProjectTaskMkEdit.css')}&mk_v=20260605_ptask_edit1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ProjectTask/resources/ProjectTaskMkEdit.js')}&mk_v=20260625_ptask_save1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-projecttask-shell="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-projecttask-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-projecttask-edit-page">
			<div class="editViewPageDiv viewContent mk-projecttask-edit-inner">
{/strip}
