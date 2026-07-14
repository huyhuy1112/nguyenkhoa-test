{* Leads List: dashboard split shell + topbar (SALES). *}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-leads-ui-ready');</script>
<script type="text/javascript">window.__MK_LEADS_UI_BUILD__ = "20260619_leads_api3";</script>
<script type="text/javascript">window.MK_LEADS_API_READY = true;</script>
{if isset($MK_LEADS_ASSIGNABLE_USERS)}
<script type="text/javascript">window.MK_LEADS_ASSIGNABLE_USERS = {Zend_Json::encode($MK_LEADS_ASSIGNABLE_USERS)};</script>
{/if}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkShell.css')}&mk_v=20260520_2030" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkList.css')}&mk_v=20260711_i18n_tags1" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkConvertModal.css')}?mk_v=20260701_convert_modal_v1" />
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkListLovable.css')}&mk_v=20260714_tagpill2" />
<style type="text/css">
/* Critical: remove 1px clipped input line beside circular checkbox */
.mk-leads-page--lovable .mk-leads-check { position: relative; width: 16px; height: 16px; isolation: isolate; }
.mk-leads-page--lovable .mk-leads-check__input {
	position: absolute; inset: 0; width: 16px; height: 16px; margin: 0; padding: 0; opacity: 0;
	border: 0 !important; outline: 0 !important; background: transparent !important;
	-webkit-appearance: none; appearance: none; z-index: 2; cursor: pointer; box-shadow: none !important;
}
</style>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkMarketingListShared.css')}" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLovableRef.js')}&mk_v=20260711_i18n_tags1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsSeedData.js')}&mk_v=20260711_i18n_tags1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLocalStore.js')}&mk_v=20260625_leads_assign_v3"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsLeadsLogic.js')}&mk_v=20260711_i18n_tags1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkIcons.js')}?mk_v=20260711_i18n_tags1"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Leads/resources/LeadsMkList.js')}&mk_v=20260713_bulk_delete1"></script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.css')}?mk_v=20260714_tagpill2" />
<script type="text/javascript">
window.__mkSalesPosInlineConfig = {
	module: 'Leads',
	tableSelector: '#mk-leads-table',
	rowSelector: 'tr.mk-leads-row',
	colspan: 14,
	enabledSelector: '[data-mk-leads-list]',
	loadingText: 'Đang tải chi tiết lead...',
	errorText: 'Không tải được chi tiết lead.'
};
</script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesPosInline.js')}?mk_v=20260714_tagpill2"></script>
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
