{*+**********************************************************************************
 * SupportFAQ → Cảnh báo (SUPPORT): dashboard split shell + Tag Rule Engine alerts.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT') || !isset($smarty.get.app) || $smarty.get.app eq ''}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-hd-ui-ready', 'mk-tre-ui-ready', 'mk-tre-alerts-ready');</script>
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/DashBoard.css')}" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/SupportFAQ/resources/SupportFAQList.css')}?mk_v=20260714_db2" />
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/HelpDesk/resources/MkTagRuleEngine.css')}?mk_v=20260714_db2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/HelpDesk/resources/MkTagRuleEngineStore.js')}?mk_v=20260714_db2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/HelpDesk/resources/MkTagRuleAlerts.js')}?mk_v=20260714_db2"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-supportfaq-alerts="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-content mk-sf-faq-main" id="mk-dash-main" role="main">
		<div class="main-container main-container-{$MODULE} mk-sf-faq-page mk-sf-alerts-page">
			<div id="listViewContent" class="listViewPageDiv content-area full-width mk-sf-faq-content">
{/strip}
{else}
{include file="IndexViewPreProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
