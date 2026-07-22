{* Leads Detail (SALES): split shell + Opp-style UI demo (no Vtiger record). *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkShell.css')}&mk_v=20260720_leads_font1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkConvertModal.css')}?mk_v=20260701_convert_modal_v1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkTagPalette.css')}&mk_v=20260715_tag_color_v1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsDetailShell.css')}&mk_v=20260716_last_touch1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesRelatedList.css')}&mk_v=20260721_rel_toggle1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Calendar/resources/CalendarQuickCreateTask.css')}&mk_v=20260603_event_ui" />
<script type="text/javascript">document.body.classList.add('mk-lead-detail-ui-loading');</script>
<script type="text/javascript">window.MK_LEADS_API_READY = true;</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesRelatedList.js')}&mk_v=20260721_rel_toggle1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLovableRef.js')}&mk_v=20260720_leads_tag_vi1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsSeedData.js')}&mk_v=20260619_leads_api4"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLocalStore.js')}&mk_v=20260622_leads_live_v1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLeadsLogic.js')}&mk_v=20260716_next_manual1"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-lead-detail="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-lead-detail-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-lead-detail-page">
			<div class="detailViewContainer viewContent clearfix mk-lead-detail-inner" id="mk-leads-detail-root" data-record-id="{$MK_LEADS_DETAIL_RECORD|escape:'html'}">
{/strip}
