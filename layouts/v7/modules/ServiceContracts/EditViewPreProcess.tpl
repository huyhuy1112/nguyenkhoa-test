{* ServiceContracts Create: dashboard split shell — sidebar + topbar unchanged. *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-sc-create-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/SalesMkEditShell.css')}&mk_v=20260603_no_dup_footer" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/ServiceContracts/resources/ServiceContractMkEdit.css')}?mk_v=20260730_sc_dup_ui1" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ServiceContracts/resources/ServiceContractsLovableRef.js')}?mk_v=20260730_sc_dup_ui1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/ServiceContracts/resources/ServiceContractMkEdit.js')}?mk_v=20260730_sc_dup_ui1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-service-contract-create="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-sc-edit-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-sc-edit-page">
			<div class="editViewPageDiv viewContent mk-sc-edit-inner">
{/strip}
