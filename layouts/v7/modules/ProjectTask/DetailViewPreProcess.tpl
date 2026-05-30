{*+**********************************************************************************
 * ProjectTask Detail (MANAGEMENT app): dashboard split shell + topbar (Project pattern).
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-projecttask-detail-management');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProjectTask/resources/ProjectTaskMkDetail.css')}&mk_v=20260529_ptask_detail1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ProjectTask/resources/ProjectTaskMkDetail.js')}&mk_v=20260529_ptask_detail1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-projecttask-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-projecttask-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-projecttask-detail-page">
			<div id="modnavigator" class="module-nav detailViewModNavigator clearfix mk-projecttask-detail-hide-legacy">
				<div class="mod-switcher-container">
					{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-projecttask-detail-hide-legacy">
				{include file="partials/SidebarEssentials.tpl"|vtemplate_path:$MODULE}
			</div>
			<div class="detailViewContainer viewContent clearfix mk-projecttask-detail-inner">
				{include file="partials/ProjectTaskDetailBreadcrumb.tpl"|vtemplate_path:$MODULE}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
				<div class="detailview-content mk-projecttask-detailview-content">
					<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
					{include file="ModuleRelatedTabs.tpl"|vtemplate_path:$MODULE}
					<div class="details mk-projecttask-detail-details-row">
{/strip}
{else}
{* Legacy shell *}
{include file="modules/Vtiger/partials/Topbar.tpl"}
<div class="container-fluid app-nav app-nav-{$SELECTED_MENU_CATEGORY}">
    <div class="row">
        {include file="partials/SidebarHeader.tpl"|vtemplate_path:$MODULE}
        {include file="ModuleHeader.tpl"|vtemplate_path:$MODULE}
    </div>
</div>
</nav>
<div id='overlayPageContent' class='fade modal overlayPageContent content-area overlay-container-60' tabindex='-1' role='dialog' aria-hidden='true'>
    <div class="data"></div>
    <div class="modal-dialog"></div>
</div>
<div class="container-fluid main-container">
    <div class="row">
        <div id="modnavigator" class="module-nav detailViewModNavigator clearfix">
            <div class="mod-switcher-container">
                {include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
            </div>
        </div>
        {assign var=IS_SUBTASK value=($MODULE_NAME eq 'ProjectTask' && $RECORD->get('parent_projecttaskid'))}
        <div class="detailViewContainer viewContent clearfix{if $IS_SUBTASK} projecttask-subtask-detail-view{/if}">
            <div class="col-sm-12 col-xs-12 content-area">
                {include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
                {if !$IS_SUBTASK}
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6">
                        {include file="DetailViewTagList.tpl"|vtemplate_path:$MODULE}
                    </div>
                </div>
                {/if}
            </div>
            <div class="detailview-content container-fluid">
                <input id="recordId" type="hidden" value="{$RECORD->getId()}" />
                {include file="ModuleRelatedTabs.tpl"|vtemplate_path:$MODULE}
                <div class="details row" style="margin-top:10px;">
{/if}
