{*+**********************************************************************************
 * Accounts Organizations Detail (Sales + Support): dashboard split shell + topbar; legacy app nav omitted.
 * Inner detail DOM matches stock Vtiger for Detail.js (.detailViewContainer, .detailview-content, #recordId, .related-tabs).
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'SUPPORT')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'SUPPORT'))}
{strip}
{include file="partials/AccountsModernUiInit.tpl"|vtemplate_path:$MODULE}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesUiMeta.tpl"|vtemplate_path:'Vtiger'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesDetailInlineEdit.css')}&mk_v=20260611_mk_inline_v3" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsDetail.css')}&mk_v=20260605_acc_tabs_v5" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesRelatedList.css')}&mk_v=20260605_mk_rel_v2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesRelatedList.js')}&mk_v=20260605_mk_rel_v2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsDetail.js')}&mk_v=20260605_acc_tabs_v5"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-accounts-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-accounts-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-accounts-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-accounts-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-accounts-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-accounts-detail-inner">
				{include file="partials/AccountsDetailBreadcrumb.tpl"|@vtemplate_path:$MODULE}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-acc-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
					{include file="ModuleRelatedTabs.tpl"|vtemplate_path:$MODULE}
					<div class="details mk-acc-detail-details-row">
{/strip}
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
{strip}
{include file="partials/AccountsModernUiInit.tpl"|vtemplate_path:$MODULE}
{include file="modules/Vtiger/Header.tpl"}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsDetail.css')}&mk_v=20260605_acc_tabs_v5" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Accounts/resources/AccountsDetail.js')}&mk_v=20260605_acc_tabs_v5"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-accounts-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-accounts-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-accounts-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-accounts-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-accounts-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-accounts-detail-inner">
				{include file="partials/AccountsDetailBreadcrumb.tpl"|@vtemplate_path:$MODULE}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-acc-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
					{include file="ModuleRelatedTabs.tpl"|vtemplate_path:$MODULE}
					<div class="details mk-acc-detail-details-row">
{/strip}
{else}
{include file="DetailViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
