{* Contacts Create/Edit: dashboard shell.
   Load ContactMkEdit once here (after shell) — do NOT also register via Edit.php
   getHeaderCss with ?v= (breaks file_exists / duplicates CSS). *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-contact-create-ready');</script>
<style id="mk-ct-fouc-guard">
/* Hide create/edit until modern CSS + JS paint — kills 1s flash of stock Vtiger form */
html.mk-contact-create-ready:not(.mk-ct-painted) .mk-contacts-edit-main,
html.mk-contact-create-ready:not(.mk-ct-painted) #mkCtCreateWorkspace {
	opacity: 0 !important;
	visibility: hidden !important;
}
html.mk-contact-create-ready.mk-ct-painted .mk-contacts-edit-main,
html.mk-contact-create-ready.mk-ct-painted #mkCtCreateWorkspace {
	opacity: 1 !important;
	visibility: visible !important;
}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260803_ct_polish1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Contacts/resources/ContactMkEdit.css')}&mk_v=20260803_ct_polish1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsLovableRef.js')}&mk_v=20260803_ct_polish1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Contacts/resources/ContactMkEdit.js')}&mk_v=20260803_ct_polish1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-contact-create="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-contacts-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-contacts-edit-page">
			<div class="editViewPageDiv viewContent mk-contacts-edit-inner">
{/strip}
