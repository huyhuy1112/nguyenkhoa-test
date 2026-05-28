{*+**********************************************************************************
 * Plans Create (MARKETING app): dashboard split shell + topbar.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-plans-edit-ui-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/PlansEdit.css')}?mk_v=20260528_plans_edit1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Plans/resources/PlansMkEdit.js')}?mk_v=20260528_plans_edit1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-plans-edit="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-plan-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-plan-edit-page">
			<div class="editViewContainer viewContent clearfix mk-plan-edit-inner">
				<div class="mk-plan-edit-body">
{/strip}
{else}
{include file="IndexViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}

