{*+**********************************************************************************
 * Settings shell — same split layout as Marketing/Sales (DashboardSidebar + app topbar).
 * Local-only UI refresh; icons: SettingsShortcutSvgIcon.tpl (SVG placeholders).
 ************************************************************************************}
{strip}
{include file="modules/Vtiger/Header.tpl"}
<script type="text/javascript">document.documentElement.classList.add('mk-settings-ui-ready');document.body.setAttribute('data-parent','Settings');</script>
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Settings/Vtiger/resources/SettingsUi.css')}?mk_v=20260814_settings_ui2" />
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Settings/Vtiger/resources/SettingsCards.css')}?mk_v=20260814_settings_ui2" />
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/DashboardSidebarNav.js')}?mk_v=20260814_settings_ui2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Settings/Vtiger/resources/SettingsListScroll.js')}?mk_v=20260814_settings_ui2"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Settings/Vtiger/resources/SettingsUi.js')}?mk_v=20260814_settings_ui2"></script>
<script type="text/javascript" src="{vresource_url('~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.js')}"></script>
<div id="mk-dash-split-root" class="mk-dash-split-root" data-mk-dash-split-root="1" data-mk-settings-ui="1">
	{include file="dashboards/DashboardSidebar.tpl"|vtemplate_path:'Vtiger'}
	<div class="mk-app-shell">
		<header class="mk-topbar" role="banner">
			{include file="partials/DashboardAppTopbar.tpl"|@vtemplate_path:'Vtiger'}
		</header>
		<div id="overlayPageContent" class="fade modal content-area overlayPageContent overlay-container-60" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="data"></div>
			<div class="modal-dialog"></div>
		</div>
		<main class="mk-dash-main mk-settings-main" id="mk-dash-main" role="main">
			<div class="main-container clearfix mk-settings-page">
				<div class="module-nav settingsNav mk-settings-hide-legacy" id="modnavigator">
					<div class="hidden-xs hidden-sm height100Per">
						{include file="modules/Settings/Vtiger/Sidebar.tpl"}
					</div>
				</div>
				<div class="settingsPageDiv content-area clearfix mk-settings-content mk-settings-subpage">
{if isset($FIELDS_INFO) && $FIELDS_INFO neq null}
	<script type="text/javascript">
		var uimeta = (function() {
			var fieldInfo  = {$FIELDS_INFO};
			return {
				field: {
					get: function(name, property) {
						if(name && property === undefined) {
							return fieldInfo[name];
						}
						if(name && property) {
							return fieldInfo[name][property]
						}
					},
					isMandatory : function(name){
						if(fieldInfo[name]) {
							return fieldInfo[name].mandatory;
						}
						return false;
					},
					getType : function(name){
						if(fieldInfo[name]) {
							return fieldInfo[name].type
						}
						return false;
					}
				},
			};
		})();
	</script>
{/if}
{if !($MODULE eq 'Vtiger' && $VIEW eq 'Index')}
	{include file="partials/SettingsSubpageHeader.tpl"|@vtemplate_path:'Settings:Vtiger'}
{/if}
{/strip}
