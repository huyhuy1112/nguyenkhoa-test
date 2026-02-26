{*+**********************************************************************************
* Cấu trúc nav giống Vtiger/IndexViewPreProcess và các trang Management (Home, Teams...)
* để menu hamburger bật bình thường (cùng Topbar, SidebarHeader, overlayPageContent).
************************************************************************************}
{strip}
{include file="modules/Vtiger/partials/Topbar.tpl"}

<div class="container-fluid app-nav app-nav-{$SELECTED_MENU_CATEGORY}">
    <div class="row">
        {include file="partials/SidebarHeader.tpl"|vtemplate_path:$MODULE}
        {include file="ModuleHeader.tpl"|vtemplate_path:$MODULE}
    </div>
</div>
</nav>
<div id='overlayPageContent' class='fade modal overlayPageContent content-area overlay-container-60' tabindex='-1' role='dialog' aria-hidden='true'>
    <div class="data">
    </div>
    <div class="modal-dialog">
    </div>
</div>
<div class="clearfix main-container main-container-Reports">
    <div class="editViewPageDiv viewContent">
        <div class="reports-content-area">
{/strip}