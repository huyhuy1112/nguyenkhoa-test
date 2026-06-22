{*+**********************************************************************************
 * Potentials Detail (Sales app): reuse the SALES dashboard split shell + topbar.
 * Inner DOM matches stock Vtiger Detail (.detailViewContainer, .detailview-content,
 * #recordId, .related-tabs) so Vtiger Detail.js / Detail-AJAX continues to work.
 ************************************************************************************}
{if $MODULE eq 'Potentials' || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY|trim eq 'SALES')) || (isset($smarty.get.app) && ($smarty.get.app|trim eq 'SALES'))}
{strip}
{include file="modules/Vtiger/Header.tpl"}
{include file="partials/MkSalesUiMeta.tpl"|vtemplate_path:'Vtiger'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesDetailInlineEdit.css')}&mk_v=20260611_mk_inline_v3" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Potentials/resources/Detail.css')}&mk_v=20260622_opp_topbar_v1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLeadsLogic.js')}&mk_v=20260619_opp_commerce1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Potentials/resources/Detail.js')}&mk_v=20260620_opp_keyfields_v1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Potentials/resources/OpportunityDetailCommerce.js')}&mk_v=20260619_opp_commerce1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-opportunity-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-opportunity-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-opportunity-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-opportunity-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-opportunity-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-opportunity-detail-inner">
				{include file="partials/OpportunityDetailBreadcrumb.tpl"|@vtemplate_path:$MODULE}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-opportunity-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
					{include file="ModuleRelatedTabs.tpl"|vtemplate_path:$MODULE}
					<div class="details row mk-opportunity-detail-details-row">
{/strip}
{else}
{include file="DetailViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
