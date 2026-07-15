{* ProductsServices Create: dashboard split shell — sidebar + topbar unchanged. No FOUC. *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">
document.documentElement.classList.add('mk-ps-create-ready','mk-ps-create-boot');
if (document.body) { document.body.classList.add('mk-ps-create-ready'); }
else { document.addEventListener('DOMContentLoaded', function () { document.body.classList.add('mk-ps-create-ready'); }); }
</script>
{* Critical anti-FOUC: hide form until MkEdit.css + JS paint; key v4 shell tokens inline *}
<style type="text/css">
html.mk-ps-create-boot:not(.mk-ps-create-painted) #mkPsCreateWorkspace {
	visibility: hidden !important;
}
html.mk-ps-create-boot.mk-ps-create-painted #mkPsCreateWorkspace,
html.mk-ps-create-ready #mkPsCreateWorkspace {
	visibility: visible !important;
}
/* Inline shell so reload never flashes legacy vtiger edit chrome */
html.mk-ps-create-ready body[data-module="ProductsServices"][data-view="Edit"] .mk-dash-main.mk-ps-edit-main {
	background: #f4f7f5 !important;
	font-family: Inter, system-ui, -apple-system, 'Segoe UI', sans-serif !important;
}
html.mk-ps-create-ready body[data-module="ProductsServices"][data-view="Edit"] .mk-ps-create.mk-ps-create--v4 {
	max-width: 880px !important;
	margin: 0 auto !important;
	padding: 8px 0 32px !important;
}
html.mk-ps-create-ready body[data-module="ProductsServices"][data-view="Edit"] .module-nav,
html.mk-ps-create-ready body[data-module="ProductsServices"][data-view="Edit"] #modnavigator,
html.mk-ps-create-ready body[data-module="ProductsServices"][data-view="Edit"] .editViewHeader,
html.mk-ps-create-ready body[data-module="ProductsServices"][data-view="Edit"] .content-area > .row:has(.detailViewButtoncontainer) {
	display: none !important;
}
</style>
{* MkEdit last-wins styles FIRST among theme files so "new CSS" is what paints *}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesMkEdit.css')}&mk_v=20260715_ps_create_v5" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260715_ps_create_v5" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
{* Re-assert MkEdit after shell/theme so create UI always wins on reload *}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesMkEdit.css')}&mk_v=20260715_ps_create_v5" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ProductsServices/resources/ProductsServicesMkEdit.js')}&mk_v=20260715_ps_create_v5"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-ps-create="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-productsservices-edit-main mk-ps-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-ps-edit-page{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')} mk-ps-inventory-page{/if}">
			<div class="editViewPageDiv viewContent mk-ps-edit-inner">
{/strip}
