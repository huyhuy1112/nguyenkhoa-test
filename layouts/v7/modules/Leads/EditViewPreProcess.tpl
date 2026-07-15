{* Leads Create: dashboard split shell + topbar (SALES). *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-leads-ui-ready');</script>
<script type="text/javascript">window.__MK_LEADS_UI_BUILD__ = "20260619_leads_api4";</script>
<script type="text/javascript">window.MK_LEADS_API_READY = true;</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260603_no_dup_footer" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkShell.css')}&mk_v=20260520_2030" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkTagPalette.css')}&mk_v=20260714_dd_neutral1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkEdit.css')}&mk_v=20260714_dd_neutral1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLovableRef.js')}&mk_v=20260619_leads_api4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsSeedData.js')}&mk_v=20260619_leads_api4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLocalStore.js')}&mk_v=20260714_edit_fix4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkEdit.js')}&mk_v=20260716_inline_cats1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-leads-edit="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-leads-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-leads-edit-page">
			<div class="editViewPageDiv viewContent mk-leads-edit-inner">
{/strip}
