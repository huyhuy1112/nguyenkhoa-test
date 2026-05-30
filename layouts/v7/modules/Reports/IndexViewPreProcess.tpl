{*+**********************************************************************************
 * Reports Management (MANAGEMENT app): dashboard split shell — same as ProjectTask / Project list.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-reports-mgmt-management');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Reports/resources/ReportsMkManagement.css')}&mk_v=20260520_mgmt5" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-reports-mgmt="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-reports-mgmt-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-Reports mk-reports-mgmt-page">
			<div id="modnavigator" class="module-nav mk-reports-mgmt-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-reports-mgmt-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="reports-content-area mk-reports-mgmt-content">
{/strip}
{else}
{strip}
{include file="modules/Vtiger/partials/Topbar.tpl"}

<div class="container-fluid app-nav app-nav-{$SELECTED_MENU_CATEGORY}">
    <div class="row">
        {include file="partials/SidebarHeader.tpl"|vtemplate_path:$MODULE}
        {include file="ModuleHeader.tpl"|vtemplate_path:$MODULE}
    </div>
</div>
</nav>
<div id='overlayPageContent' class='fade modal overlayPageContent content-area overlay-container-60' tabindex='-1' role='dialog' aria-hidden='true'>
    <div class="data">
    </div>
    <div class="modal-dialog">
    </div>
</div>
<div class="clearfix main-container main-container-Reports">
    <div class="editViewPageDiv viewContent">
        <div class="reports-content-area">
{/strip}
{/if}
