{*+**********************************************************************************
 * ProjectTask Detail PreProcess - two-column layout for subtask detail
 ************************************************************************************}
{* modules/Vtiger/views/Detail.php *}
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
