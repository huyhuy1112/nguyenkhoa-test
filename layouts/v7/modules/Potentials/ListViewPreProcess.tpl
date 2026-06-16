{*+**********************************************************************************
 * Potentials List (Sales app): reuse Accounts SALES dashboard shell.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-opportunity-list-sales');</script>
<style type="text/css">
html.mk-opportunity-list-sales:not(.mk-opp-list-ready) #listViewContent { visibility: hidden; }
html.mk-opportunity-list-sales.mk-opp-list-ready #listViewContent { visibility: visible; }
html.mk-opportunity-list-sales #listViewContent #scroller_wrapper.bottom-fixed-scroll,
html.mk-opportunity-list-sales #listViewContent .bottom-fixed-scroll {
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
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}?mk_v=20260607_unified1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Potentials/resources/List.js')}?mk_v=20260607_opp_bundle1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Potentials/resources/InternalOrderProtection.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-opportunity-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Potentials/resources/OpportunityList.css')}?mk_v=20260607_pagejump2" onload="document.documentElement.classList.add('mk-opp-list-ready')" />
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-opportunity-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-opportunity-list-page">
			<div id="modnavigator" class="module-nav mk-opportunity-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-opportunity-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="listViewPageDiv content-area full-width mk-opportunity-list-content" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Potentials/resources/InternalOrderProtection.js')}"></script>
{/if}
