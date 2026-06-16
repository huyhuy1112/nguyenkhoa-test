{*+**********************************************************************************
 * Project Detail (MANAGEMENT app): dashboard split shell + topbar (ProjectTask / Project list pattern).
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-project-detail-management');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Project/resources/ProjectMkDetail.css')}&mk_v=20260607_detail_v33" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Project/resources/ProjectMkRelatedList.css')}&mk_v=20260607_detail_v31" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Project/resources/ProjectMkChart.css')}&mk_v=20260607_detail_v36" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Project/resources/ProjectMkTaskBoard.css')}&mk_v=20260529_detail16" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Project/resources/ProjectMkTaskDetail.css')}&mk_v=20260529_detail16" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesRelatedList.js')}&mk_v=20260607_detail_v31"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Project/resources/ProjectMkDetail.js')}&mk_v=20260607_detail_v36"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-project-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-project-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-project-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-project-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-project-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-project-detail-inner">
				{include file="partials/ProjectDetailBreadcrumb.tpl"|vtemplate_path:$MODULE}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-project-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
					{include file="ModuleRelatedTabs.tpl"|vtemplate_path:$MODULE}
					<div class="details mk-project-detail-details-row">
{/strip}
{else}
{include file="DetailViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
