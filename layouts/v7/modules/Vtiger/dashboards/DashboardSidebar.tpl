{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License.
* Dashboard-only sidebar (Figma-aligned). Icons: DashboardSidebarSvgIcon.tpl (designer SVG).
************************************************************************************}

{strip}
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
{assign var=USER_PRIVILEGES_MODEL value=Users_Privileges_Model::getCurrentUserPrivilegesModel()}
{assign var=DASHBOARD_MODULE_MODEL value=Vtiger_Module_Model::getInstance('Dashboard')}
{assign var=APP_GROUPED_MENU value=Settings_MenuEditor_Module_Model::getAllVisibleModules()}
{assign var=MK_SIDEBAR_APPS value=','|explode:'MARKETING,SALES,INVENTORY,SUPPORT,MANAGEMENT,TOOLS'}
{assign var=_mkHost value=$smarty.server.HTTP_HOST|default:$smarty.server.SERVER_NAME|default:''|lower}
{if $_mkHost|strstr:'nguyenkhoa-test'}
	{* Demo nguyenkhoa-test only — gói khách không có Marketing / Tools *}
	{assign var=MK_SIDEBAR_APPS value=','|explode:'SALES,INVENTORY,SUPPORT,MANAGEMENT'}
{/if}
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
		<div class="mk-dash-sidebar-logo">
			<a href="index.php" class="company-logo mk-dash-logo-link" title="Nguyên Khoa" aria-label="Nguyên Khoa home">
				<img class="mk-dash-bace-logo nguyenkhoa-logo" src="layouts/v7/resources/Images/nguyenkhoa-logo.png?v=20260629d" width="240" height="96" alt="Nguyên Khoa">
			</a>
			{if $_settingsActive}
				<p class="mk-settings-site-kicker">{vtranslate('LBL_SITE_SETTINGS','Vtiger')}</p>
			{/if}
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
				{assign var=_app_expand_initial value=(!$_dashViewActive && !$_settingsActive && $APP_NAME eq $SELECTED_MENU_CATEGORY)}
				{assign var=_app_route_active value=(!$_dashViewActive && !$_settingsActive && $APP_NAME eq $SELECTED_MENU_CATEGORY)}
				<div class="mk-dash-app-group{if $_app_expand_initial} mk-dash-app-group--open{/if}{if $_app_route_active} mk-dash-app-group--active{/if}" data-mk-app="{$APP_NAME|escape:'html'}">
					<button type="button" class="mk-dash-app-toggle" id="mk-dash-app-btn-{$APP_NAME}" aria-expanded="{if $_app_expand_initial}true{else}false{/if}" aria-controls="mk-dash-app-panel-{$APP_NAME}">
						<span class="mk-dash-app-ic" aria-hidden="true">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON=$APP_NAME}</span>
						<span class="mk-dash-app-label">{vtranslate("LBL_$APP_NAME",'Vtiger')}</span>
						<span class="mk-dash-app-chevron" aria-hidden="true">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='CHEVRON'}</span>
					</button>
					<div class="mk-dash-app-panel" id="mk-dash-app-panel-{$APP_NAME}" role="region" aria-labelledby="mk-dash-app-btn-{$APP_NAME}">
						{* MANAGEMENT: Main Page (landing) — không có trong MenuEditor mặc định *}
						{if $APP_NAME eq 'MANAGEMENT'}
							{assign var=_mkMainPageActive value=(!$_settingsActive && $MODULE eq 'Home' && ($VIEW eq 'MainPage' || $VIEW eq 'DashBoard'))}
							<a class="mk-dash-mod-link{if $_mkMainPageActive} mk-dash-mod-link--active{/if}" href="index.php?module=Home&amp;view=MainPage&amp;app=MANAGEMENT">
								<span class="mk-dash-mod-label">{vtranslate('LBL_MAIN_PAGE','Home')}</span>
							</a>
						{/if}
						{assign var=_mkHasLeads value=false}
						{assign var=_mkHasAccounts value=false}
						{assign var=_mkHasActivities value=false}
						{assign var=_mkHasCalendar value=false}
						{* SALES: Leads fallback at top when missing from MenuEditor *}
						{if $APP_NAME eq 'SALES'}
							{foreach item=_mkScanModel key=_mkScanName from=$APP_GROUPED_MENU[$APP_NAME]}
								{if $_mkScanName eq 'Leads'}{assign var=_mkHasLeads value=true}{/if}
							{/foreach}
							{if $_mkHasLeads eq false}
								{assign var=_mkLeadsActive value=(!$_settingsActive && $MENU_SELECTED_MODULENAME eq 'Leads')}
								<a class="mk-dash-mod-link{if $_mkLeadsActive} mk-dash-mod-link--active{/if}" href="index.php?module=Leads&amp;view=List&amp;app=SALES">
									<span class="mk-dash-mod-label">{vtranslate('Leads', 'Leads')}</span>
								</a>
								{assign var=_mkHasLeads value=true}
							{/if}
						{/if}
						{foreach item=moduleModel key=moduleName from=$APP_GROUPED_MENU[$APP_NAME]}
							{if $APP_NAME eq 'MANAGEMENT' && $moduleName eq 'Home'}{continue}{/if}
							{* SUPPORT: ẩn Schedule/Calendar — chỉ dùng Activities (Schedule chỉ ở MANAGEMENT) *}
							{if $APP_NAME eq 'SUPPORT' && ($moduleName eq 'Calendar' || $moduleName eq 'Schedule')}{continue}{/if}
							{if $moduleName eq 'Calendar'}{assign var=_mkHasCalendar value=true}{/if}
							{if $moduleName eq 'Leads'}{assign var=_mkHasLeads value=true}{/if}
							{if $moduleName eq 'Accounts'}{assign var=_mkHasAccounts value=true}{/if}
							{if $moduleName eq 'Activities'}{assign var=_mkHasActivities value=true}{/if}
							{* Leads belongs to SALES only — hide from Marketing sidebar *}
							{if $APP_NAME eq 'MARKETING' && $moduleName eq 'Leads'}{continue}{/if}
							{if $moduleName eq 'ExtensionStore'}{continue}{/if}
							{* SALES: keep ProductsServices under Kho; hide legacy Products/Services entries *}
							{if $APP_NAME eq 'SALES' && ($moduleName eq 'Products' || $moduleName eq 'Services' || $moduleName eq 'ProductsServices')}{continue}{/if}
							{* INVENTORY: ẩn Inbound / Storage / Outbound — dùng Danh sách kho thay thế *}
							{if $APP_NAME eq 'INVENTORY' && ($moduleName eq 'GoodsReceipt' || $moduleName eq 'GoodsIssue' || $moduleName eq 'Warehouse')}{continue}{/if}
							{if $moduleModel}
								{assign var=_mkModActive value=(!$_settingsActive && $MENU_SELECTED_MODULENAME eq $moduleName)}
								{* INVENTORY: Warehouse mgmt uses module=Warehouse but should not highlight Storage (Warehouse List) *}
								{if $MODULE eq 'Warehouse' && ($VIEW eq 'WhList' || $VIEW eq 'WhDashboard' || $VIEW eq 'WhDetail' || $VIEW eq 'WhTransfer') && $moduleName eq 'Warehouse'}
									{assign var=_mkModActive value=false}
								{/if}
								{if $MODULE eq 'HelpDesk' && ($VIEW eq 'Rules' || $VIEW eq 'RuleDetail')}
									{if $moduleName eq 'HelpDesk'}{assign var=_mkModActive value=false}{/if}
									{if $moduleName eq 'Rules'}{assign var=_mkModActive value=true}{/if}
								{/if}
								{if $MODULE eq 'SupportFAQ' && $moduleName eq 'SupportFAQ'}
									{assign var=_mkModActive value=true}
								{/if}
								<a class="mk-dash-mod-link{if $_mkModActive} mk-dash-mod-link--active{/if}" href="{$moduleModel->getDefaultUrl()}&app={$APP_NAME}">
									<span class="mk-dash-mod-label">{if $moduleName eq 'Rules'}Quản Lý rule{elseif $moduleName eq 'SupportFAQ'}Cảnh báo{elseif $moduleName eq 'Accounts'}Tuibao{elseif $moduleName eq 'Contacts'}Khách hàng{elseif $moduleName eq 'ProductsServices'}Hàng hoá{else}{vtranslate($moduleName, $moduleName)}{/if}</span>
								</a>
							{/if}
						{/foreach}

						{* MANAGEMENT: Schedule khi Calendar chưa có trong MenuEditor *}
						{if ($_mkHasCalendar eq false) && ($APP_NAME eq 'MANAGEMENT')}
							{assign var=_mkScheduleActive value=(!$_settingsActive && $MODULE eq 'Calendar' && $VIEW eq 'Calendar')}
							<a class="mk-dash-mod-link{if $_mkScheduleActive} mk-dash-mod-link--active{/if}" href="index.php?module=Calendar&amp;view=Calendar&amp;app=MANAGEMENT">
								<span class="mk-dash-mod-label">{vtranslate('LBL_SCHEDULE','Calendar')}</span>
							</a>
						{/if}
						{* SUPPORT: Activities when missing from MenuEditor *}
						{if ($_mkHasActivities eq false) && ($APP_NAME eq 'SUPPORT')}
							{assign var=_mkActivitiesActive value=(!$_settingsActive && $MENU_SELECTED_MODULENAME eq 'Activities')}
							<a class="mk-dash-mod-link{if $_mkActivitiesActive} mk-dash-mod-link--active{/if}" href="index.php?module=Activities&amp;view=List&amp;app=SUPPORT">
								<span class="mk-dash-mod-label">{vtranslate('LBL_ACTIVITIES','Calendar')}</span>
							</a>
						{/if}
						{* SUPPORT: Organizations (Accounts) when missing from MenuEditor *}
						{if ($_mkHasAccounts eq false) && ($APP_NAME eq 'SUPPORT')}
							{assign var=_mkAccountsActive value=(!$_settingsActive && $MENU_SELECTED_MODULENAME eq 'Accounts')}
							<a class="mk-dash-mod-link{if $_mkAccountsActive} mk-dash-mod-link--active{/if}" href="index.php?module=Accounts&amp;view=List&amp;app=SUPPORT">
								<span class="mk-dash-mod-label">Tuibao</span>
							</a>
						{/if}

						{* INVENTORY: Warehouse Management — Danh sách kho & Dashboard (localStorage prototype) *}
						{if $APP_NAME eq 'INVENTORY'}
							{assign var=_mkWhListActive value=(!$_settingsActive && $MODULE eq 'Warehouse' && ($VIEW eq 'WhList' || $VIEW eq 'WhDetail'))}
							<a class="mk-dash-mod-link{if $_mkWhListActive} mk-dash-mod-link--active{/if}" href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">
								<span class="mk-dash-mod-label">{vtranslate('LBL_WH_LIST','Warehouse')}</span>
							</a>
							{assign var=_mkWhDashActive value=(!$_settingsActive && $MODULE eq 'Warehouse' && $VIEW eq 'WhDashboard')}
							<a class="mk-dash-mod-link{if $_mkWhDashActive} mk-dash-mod-link--active{/if}" href="index.php?module=Warehouse&amp;view=WhDashboard&amp;app=INVENTORY">
								<span class="mk-dash-mod-label">{vtranslate('LBL_WH_DASHBOARD','Warehouse')}</span>
							</a>
							{assign var=_mkWhTrfActive value=(!$_settingsActive && $MODULE eq 'Warehouse' && $VIEW eq 'WhTransfer')}
							<a class="mk-dash-mod-link{if $_mkWhTrfActive} mk-dash-mod-link--active{/if}" href="index.php?module=Warehouse&amp;view=WhTransfer&amp;app=INVENTORY">
								<span class="mk-dash-mod-label">{vtranslate('LBL_WH_TRANSFER','Warehouse')}</span>
							</a>
							{assign var=_mkPsActive value=(!$_settingsActive && $MODULE eq 'ProductsServices')}
							<a class="mk-dash-mod-link{if $_mkPsActive} mk-dash-mod-link--active{/if}" href="index.php?module=ProductsServices&amp;view=List&amp;app=INVENTORY">
								<span class="mk-dash-mod-label">Hàng hoá</span>
							</a>
						{/if}
					</div>
				</div>
			{/foreach}

			<a class="mk-dash-nav-item{if $_settingsActive} mk-dash-nav-item--active{/if}" href="index.php?module=Vtiger&amp;parent=Settings&amp;view=Index" title="{vtranslate('LBL_SETTINGS','Vtiger')}">
				<span class="mk-dash-nav-ic" aria-hidden="true">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='SETTINGS'}</span>
				<span class="mk-dash-nav-label">{vtranslate('LBL_SETTINGS','Vtiger')}</span>
			</a>
			{if $_settingsActive}
				{include file="partials/SettingsSidebarSubmenu.tpl"|@vtemplate_path:'Settings:Vtiger'}
			{/if}
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
			<a class="mk-dash-sidebar-logout" href="index.php?module=Users&amp;action=Logout" title="{vtranslate('LBL_SIGN_OUT','Vtiger')}" aria-label="{vtranslate('LBL_SIGN_OUT','Vtiger')}">
				<span class="fa fa-sign-out" aria-hidden="true"></span>
			</a>
		</div>
</aside>
{/strip}
