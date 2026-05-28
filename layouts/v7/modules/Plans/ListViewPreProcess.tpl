{*+**********************************************************************************
 * Plans List (Marketing app): dashboard split shell + topbar (Campaigns pattern).
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-plans-ui-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/PlansList.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.css')}" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Plans/resources/List.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-plans-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-plan-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-plan-list-page">
			<div id="modnavigator" class="module-nav mk-plan-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-plan-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="listViewPageDiv content-area full-width mk-plan-list-content" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
