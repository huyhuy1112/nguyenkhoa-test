{* Potentials Create: dashboard split shell — sidebar + topbar unchanged. *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-opportunity-create-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260603_no_dup_footer" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Potentials/resources/OpportunityMkEdit.css')}&mk_v=20260603_no_dup_footer" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Potentials/resources/OpportunityMkEdit.js')}&mk_v=20260527_create9"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-opportunity-create="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-opportunity-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-opportunity-edit-page">
			<div class="editViewPageDiv viewContent mk-opportunity-edit-inner">
{/strip}
