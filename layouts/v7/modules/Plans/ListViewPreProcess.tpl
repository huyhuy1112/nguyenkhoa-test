{*+**********************************************************************************
 * Plans List (Marketing app): dashboard split shell + topbar (Campaigns pattern).
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<style type="text/css">
html.mk-plans-list-marketing:not(.mk-plans-ui-ready) #listViewContent { visibility: hidden; }
html.mk-plans-list-marketing.mk-plans-ui-ready #listViewContent { visibility: visible; }
html.mk-plans-list-marketing #listViewContent #scroller_wrapper.bottom-fixed-scroll,
html.mk-plans-list-marketing #listViewContent .bottom-fixed-scroll {
	display: none !important;
	height: 0 !important;
	margin: 0 !important;
	padding: 0 !important;
	border: none !important;
	overflow: hidden !important;
	position: absolute !important;
	left: -9999px !important;
	width: 0 !important;
	pointer-events: none !important;
}
</style>
<script type="text/javascript">document.documentElement.classList.add('mk-plans-list-marketing');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.css')}?mk_v=20260607_plans1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListTable.css')}?mk_v=20260603_mkt_std1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/PlansList.css')}?mk_v=20260607_plans1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.js')}?mk_v=20260607_plans1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Plans/resources/List.js')}?mk_v=20260607_plans1"></script>
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
