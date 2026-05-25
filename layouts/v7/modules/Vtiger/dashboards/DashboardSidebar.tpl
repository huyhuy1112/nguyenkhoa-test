{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License.
* Dashboard-only sidebar (Figma-aligned). Icons: DashboardSidebarSvgIcon.tpl (designer SVG).
************************************************************************************}

{strip}
{assign var=USER_PRIVILEGES_MODEL value=Users_Privileges_Model::getCurrentUserPrivilegesModel()}
{assign var=DASHBOARD_MODULE_MODEL value=Vtiger_Module_Model::getInstance('Dashboard')}
{assign var=APP_GROUPED_MENU value=Settings_MenuEditor_Module_Model::getAllVisibleModules()}
{assign var=MK_SIDEBAR_APPS value=','|explode:'MARKETING,SALES,INVENTORY,SUPPORT,MANAGEMENT,TOOLS'}
{assign var=_dashViewActive value=($VIEW eq 'DashBoard' || $VIEW eq 'ModernDashboard')}
{assign var=_settingsActive value=(isset($PARENT_MODULE) && $PARENT_MODULE eq 'Settings')}
{assign var=_userImgs value=$USER_MODEL->getImageDetails()}
{assign var=_userPhoto value=''}
{if isset($_userImgs[0]) && isset($_userImgs[0].url) && $_userImgs[0].url neq ''}
	{assign var=_userPhoto value=$_userImgs[0].url}
{/if}
{assign var=_roleName value=$USER_MODEL->getUserRoleName()}
{assign var=_userInitial value=''}
{if $USER_MODEL->get('first_name') neq ''}
	{assign var=_userInitial value=$USER_MODEL->get('first_name')|substr:0:1}
{elseif $USER_MODEL->get('last_name') neq ''}
	{assign var=_userInitial value=$USER_MODEL->get('last_name')|substr:0:1}
{else}
	{assign var=_userInitial value=$USER_MODEL->get('user_name')|substr:0:1}
{/if}

<button type="button" class="mk-dash-sidebar-mobile-toggle" aria-controls="mk-dash-sidebar" aria-expanded="false" title="{vtranslate('LBL_MENU',$MODULE)}">
	{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='MENU'}
</button>
<div class="mk-dash-drawer-backdrop" aria-hidden="true"></div>

<aside id="mk-dash-sidebar" class="mk-sidebar mk-dashboard-sidebar" aria-label="Dashboard sidebar">
	<div class="mk-dash-sidebar-brand">
		<div id="appnavigator" class="cursorPointer app-switcher-container mk-dash-appnavigator" data-app-class="fa-sitemap" title="{vtranslate('LBL_MENU',$MODULE)}">
			<div class="row app-navigator mk-dash-appnavigator-inner">
				<span class="mk-dash-hamburger-svg" aria-hidden="true">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='MENU'}</span>
			</div>
		</div>
		<div class="mk-dash-sidebar-logo">
			<a href="index.php" class="company-logo mk-dash-logo-link" title="B-ACE" aria-label="B-ACE home">
				<img class="mk-dash-bace-logo" src="{vresource_url('layouts/v7/resources/Images/bace-logo-figma.png')}" width="218" height="40" alt="B-ACE">
			</a>
		</div>
	</div>
	<div class="mk-dash-sidebar-scroll">
		<nav class="mk-dash-sidebar-nav mk-dash-sidebar-nav--accordion" aria-label="Primary navigation" data-mk-dash-accordion="1">
			<div class="mk-dash-sidebar-nav-track">
			{if $USER_PRIVILEGES_MODEL->hasModulePermission($DASHBOARD_MODULE_MODEL->getId())}
				<a class="mk-dash-nav-item{if $_dashViewActive} mk-dash-nav-item--active{/if}" href="index.php" title="{vtranslate('LBL_DASHBOARD',$MODULE)}">
					<span class="mk-dash-nav-ic" aria-hidden="true">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='DASHBOARD'}</span>
					<span class="mk-dash-nav-label">{vtranslate('LBL_DASHBOARD',$MODULE)}</span>
				</a>
			{/if}

			{foreach item=APP_NAME from=$MK_SIDEBAR_APPS}
				{if !isset($APP_GROUPED_MENU[$APP_NAME]) || php7_count($APP_GROUPED_MENU[$APP_NAME]) eq 0} {continue}{/if}
				{assign var=_app_expand_initial value=(!$_dashViewActive && $APP_NAME eq $SELECTED_MENU_CATEGORY)}
				{assign var=_app_route_active value=(!$_dashViewActive && $APP_NAME eq $SELECTED_MENU_CATEGORY)}
				<div class="mk-dash-app-group{if $_app_expand_initial} mk-dash-app-group--open{/if}{if $_app_route_active} mk-dash-app-group--active{/if}" data-mk-app="{$APP_NAME|escape:'html'}">
					<button type="button" class="mk-dash-app-toggle" id="mk-dash-app-btn-{$APP_NAME}" aria-expanded="{if $_app_expand_initial}true{else}false{/if}" aria-controls="mk-dash-app-panel-{$APP_NAME}">
						<span class="mk-dash-app-ic" aria-hidden="true">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON=$APP_NAME}</span>
						<span class="mk-dash-app-label">{vtranslate("LBL_$APP_NAME")}</span>
						<span class="mk-dash-app-chevron" aria-hidden="true">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='CHEVRON'}</span>
					</button>
					<div class="mk-dash-app-panel" id="mk-dash-app-panel-{$APP_NAME}" role="region" aria-labelledby="mk-dash-app-btn-{$APP_NAME}">
						{* MANAGEMENT: Main Page (landing) — không có trong MenuEditor mặc định *}
						{if $APP_NAME eq 'MANAGEMENT'}
							{assign var=_mkMainPageActive value=($MODULE eq 'Home' && ($VIEW eq 'MainPage' || $VIEW eq 'DashBoard'))}
							<a class="mk-dash-mod-link{if $_mkMainPageActive} mk-dash-mod-link--active{/if}" href="index.php?module=Home&amp;view=MainPage&amp;app=MANAGEMENT">
								<span class="mk-dash-mod-label">Main Page</span>
							</a>
						{/if}
						{assign var=_mkHasLeads value=false}
						{assign var=_mkHasAccounts value=false}
						{assign var=_mkHasCalendar value=false}
						{foreach item=moduleModel key=moduleName from=$APP_GROUPED_MENU[$APP_NAME]}
							{if $APP_NAME eq 'MANAGEMENT' && $moduleName eq 'Home'}{continue}{/if}
							{* SUPPORT: ẩn Schedule/Calendar — chỉ dùng Activities (nếu có module riêng) *}
							{if $APP_NAME eq 'SUPPORT' && $moduleName eq 'Calendar'}{continue}{/if}
							{if $moduleName eq 'Calendar'}{assign var=_mkHasCalendar value=true}{/if}
							{if $moduleName eq 'Leads'}{assign var=_mkHasLeads value=true}{/if}
							{if $moduleName eq 'Accounts'}{assign var=_mkHasAccounts value=true}{/if}
							{* Leads belongs to SALES only — hide from Marketing sidebar *}
							{if $APP_NAME eq 'MARKETING' && $moduleName eq 'Leads'}{continue}{/if}
							{if $moduleName eq 'ExtensionStore'}{continue}{/if}
							{* SALES: keep ProductsServices; hide legacy Products/Services entries *}
							{if $APP_NAME eq 'SALES' && ($moduleName eq 'Products' || $moduleName eq 'Services')}{continue}{/if}
							{if $moduleModel}
								{assign var=_mkModActive value=($MENU_SELECTED_MODULENAME eq $moduleName)}
								{if $MODULE eq 'HelpDesk' && ($VIEW eq 'Rules' || $VIEW eq 'RuleDetail')}
									{if $moduleName eq 'HelpDesk'}{assign var=_mkModActive value=false}{/if}
									{if $moduleName eq 'Rules'}{assign var=_mkModActive value=true}{/if}
								{/if}
								<a class="mk-dash-mod-link{if $_mkModActive} mk-dash-mod-link--active{/if}" href="{$moduleModel->getDefaultUrl()}&app={$APP_NAME}">
									<span class="mk-dash-mod-label">{vtranslate($moduleName, $moduleName)}</span>
								</a>
							{/if}
						{/foreach}

						{* SALES: Leads modern list (sidebar fallback when missing from MenuEditor) *}
						{if ($_mkHasLeads eq false) && ($APP_NAME eq 'SALES')}
							{assign var=_mkLeadsActive value=($MENU_SELECTED_MODULENAME eq 'Leads')}
							<a class="mk-dash-mod-link{if $_mkLeadsActive} mk-dash-mod-link--active{/if}" href="index.php?module=Leads&amp;view=List&amp;app=SALES">
								<span class="mk-dash-mod-label">{vtranslate('Leads', 'Leads')}</span>
							</a>
						{/if}
						{* MANAGEMENT: Schedule khi Calendar chưa có trong MenuEditor *}
						{if ($_mkHasCalendar eq false) && ($APP_NAME eq 'MANAGEMENT')}
							{assign var=_mkScheduleActive value=($MODULE eq 'Calendar' && $VIEW eq 'Calendar')}
							<a class="mk-dash-mod-link{if $_mkScheduleActive} mk-dash-mod-link--active{/if}" href="index.php?module=Calendar&amp;view=Calendar&amp;app=MANAGEMENT">
								<span class="mk-dash-mod-label">{vtranslate('LBL_SCHEDULE','Calendar')}</span>
							</a>
						{/if}
						{* SUPPORT: Organizations (Accounts) when missing from MenuEditor *}
						{if ($_mkHasAccounts eq false) && ($APP_NAME eq 'SUPPORT')}
							{assign var=_mkAccountsActive value=($MENU_SELECTED_MODULENAME eq 'Accounts')}
							<a class="mk-dash-mod-link{if $_mkAccountsActive} mk-dash-mod-link--active{/if}" href="index.php?module=Accounts&amp;view=List&amp;app=SUPPORT">
								<span class="mk-dash-mod-label">{vtranslate('Accounts', 'Accounts')}</span>
							</a>
						{/if}
					</div>
				</div>
			{/foreach}

			<a class="mk-dash-nav-item{if $_settingsActive} mk-dash-nav-item--active{/if}" href="index.php?module=Vtiger&amp;parent=Settings&amp;view=Index" title="{vtranslate('LBL_SETTINGS','Vtiger')}">
				<span class="mk-dash-nav-ic" aria-hidden="true">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='SETTINGS'}</span>
				<span class="mk-dash-nav-label">{vtranslate('LBL_SETTINGS','Vtiger')}</span>
			</a>
			</div>
		</nav>
	</div>

	<div class="mk-dash-sidebar-footer">
			<div class="mk-dash-sidebar-user">
				{if $_userPhoto neq ''}
					<img class="mk-dash-user-avatar mk-dash-user-avatar--img" src="{$_userPhoto|@escape:'html'}" width="40" height="40" alt="" />
				{else}
					<span class="mk-dash-user-avatar mk-dash-user-avatar--fallback" aria-hidden="true">{$_userInitial|upper|escape:'html'}</span>
				{/if}
				<div class="mk-dash-user-meta">
					<div class="mk-dash-user-name textOverflowEllipsis">{decode_html($USER_MODEL->get('first_name'))} {decode_html($USER_MODEL->get('last_name'))}</div>
					{if $_roleName neq ''}
						<div class="mk-dash-user-role textOverflowEllipsis">{decode_html($_roleName)}</div>
					{/if}
				</div>
			</div>
		</div>
</aside>
{/strip}
