{*+**********************************************************************************
 * Quotes List (Sales app): B-ACE sidebar + dashboard topbar shell (same as SalesOrder).
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesListAntiFouc.tpl"|@vtemplate_path:'Vtiger'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Quotes/resources/QuoteList.css')}?mk_v=20260710_qt_massdup4" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.css')}?mk_v=20260624_sales_antifouc1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListTable.css')}?mk_v=20260606_sales_search9" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosList.css')}?mk_v=20260710_pos4" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesListShared.js')}?mk_v=20260703_global_search3"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Quotes/resources/List.js')}?mk_v=20260710_qt_massdup4"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-quotes-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-qt-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-qt-list-page">
			<div id="modnavigator" class="module-nav mk-qt-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-qt-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="listViewPageDiv content-area full-width mk-qt-list-content" id="listViewContent">
{/strip}
{else}
{include file="ListViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
