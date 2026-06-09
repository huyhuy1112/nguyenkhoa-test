{* Leads List: dashboard split shell + topbar (SALES). *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-leads-ui-ready');</script>
<script type="text/javascript">window.__MK_LEADS_UI_BUILD__ = "20260606_lovable4";</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkShell.css')}&mk_v=20260520_2030" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkList.css')}&mk_v=20260606_lovable4" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkListLovable.css')}&mk_v=20260606_lovable4" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.css')}" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLovableRef.js')}&mk_v=20260606_lovable4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsSeedData.js')}&mk_v=20260606_lovable4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLocalStore.js')}&mk_v=20260606_lovable4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLeadsLogic.js')}&mk_v=20260606_lovable4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkIcons.js')}&mk_v=20260606_lovable4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkList.js')}&mk_v=20260606_lovable4"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-leads-list="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-leads-list-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-leads-list-page">
			<div id="modnavigator" class="module-nav mk-leads-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div id="sidebar-essentials" class="sidebar-essentials hide mk-leads-hide-legacy" style="display:none !important" aria-hidden="true"></div>
			<div class="listViewPageDiv content-area full-width mk-leads-list-content" id="listViewContent">
{/strip}
