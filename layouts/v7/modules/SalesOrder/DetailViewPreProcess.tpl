{* SalesOrder Detail: SALES or TOOLS dashboard split shell + topbar *}
{assign var=MK_SO_SALES_DETAIL value=false}
{assign var=MK_SO_TOOLS_DETAIL value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	{assign var=MK_SO_SALES_DETAIL value=true}
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'TOOLS') || (isset($smarty.get.app) && $smarty.get.app eq 'TOOLS') || (isset($smarty.request.app) && $smarty.request.app eq 'TOOLS')}
	{assign var=MK_SO_TOOLS_DETAIL value=true}
{/if}
{if $MK_SO_SALES_DETAIL}
{strip}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesUiMeta.tpl"|vtemplate_path:'Vtiger'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesDetailInlineEdit.css')}&mk_v=20260611_mk_inline_v3" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderDetail.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesRelatedList.css')}&mk_v=20260605_mk_rel_v2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesRelatedList.js')}&mk_v=20260605_mk_rel_v2"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-sales-order-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-so-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-so-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-so-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-so-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-so-detail-inner">
				{include file="partials/SalesOrderDetailBreadcrumb.tpl"|vtemplate_path:$MODULE}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-so-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
					{include file="ModuleRelatedTabs.tpl"|vtemplate_path:$MODULE}
					<div class="details mk-so-detail-details-row">
{/strip}
{elseif $MK_SO_TOOLS_DETAIL}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-sales-order-detail-tools');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SalesOrder/resources/SalesOrderToolsDetail.css')}?mk_v=20260605_so_dt1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-sales-order-detail-tools="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-so-tools-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-so-tools-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-so-tools-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-so-tools-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-so-tools-detail-inner">
				{include file="partials/SalesOrderToolsDetailBreadcrumb.tpl"|vtemplate_path:$MODULE}
				{include file="partials/SalesOrderToolsDetailHero.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-so-tools-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
{/strip}
{else}
{include file="DetailViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
