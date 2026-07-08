{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* App menu CSS: đã chuyển vào layouts/v7/resources/custom.css (#app-menu.app-menu)
   MK app icons: designer SVG via DashboardSidebarSvgIcon.tpl *}
<div class="app-menu hide" id="app-menu">
	<div class="container-fluid">
		<div class="row">
			<div class="col-sm-2 col-xs-2 cursorPointer app-switcher-container">
				<div class="row app-navigator">
					<span id="menu-toggle-action" class="app-icon fa fa-bars"></span>
				</div>
			</div>
		</div>
		{assign var=USER_PRIVILEGES_MODEL value=Users_Privileges_Model::getCurrentUserPrivilegesModel()}
		{assign var=HOME_MODULE_MODEL value=Vtiger_Module_Model::getInstance('Home')}
		{assign var=DASHBOARD_MODULE_MODEL value=Vtiger_Module_Model::getInstance('Dashboard')}
		<div class="app-list row">
			{if $USER_PRIVILEGES_MODEL->hasModulePermission($DASHBOARD_MODULE_MODEL->getId())}
				<a class="menu-item app-item app-item-dashboard" href="index.php" title="Dashboard" data-app-name="DASHBOARD">
					<div class="menu-items-wrapper app-menu-items-wrapper">
						<span class="mk-icon app-icon-list mk-app-icon-svg">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='DASHBOARD'}</span>
						<span class="app-name textOverflowEllipsis"> {vtranslate('LBL_DASHBOARD',$MODULE)}</span>
						<span class="fa fa-chevron-right pull-right app-item-dashboard-chevron"></span>
					</div>
				</a>
			{/if}
			{assign var=APP_GROUPED_MENU value=Settings_MenuEditor_Module_Model::getAllVisibleModules()}
			{assign var=APP_LIST value=Vtiger_MenuStructure_Model::getAppMenuList()}
			{assign var=_mkHost value=$smarty.server.HTTP_HOST|default:$smarty.server.SERVER_NAME|default:''|lower}
			{foreach item=APP_NAME from=$APP_LIST}
				{if $APP_NAME eq 'ANALYTICS'} {continue}{/if}
				{if $_mkHost|strstr:'nguyenkhoa-test' && ($APP_NAME eq 'MARKETING' || $APP_NAME eq 'TOOLS')} {continue}{/if}
				{if !empty($APP_GROUPED_MENU.$APP_NAME)}
					<div class="dropdown app-modules-dropdown-container">
						{foreach item=APP_MENU_MODEL from=$APP_GROUPED_MENU.$APP_NAME}
							{assign var=FIRST_MENU_MODEL value=$APP_MENU_MODEL}
							{if $APP_MENU_MODEL}
								{break}
							{/if}
						{/foreach}
						{* Fix for Responsive Layout Menu - Changed data-default-url to # *}
						<div class="menu-item app-item dropdown-toggle app-item-color-{$APP_NAME}" data-app-name="{$APP_NAME}" id="{$APP_NAME}_modules_dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-default-url="#">
							<div class="menu-items-wrapper app-menu-items-wrapper">
								{if $APP_NAME eq 'MARKETING' || $APP_NAME eq 'SALES' || $APP_NAME eq 'INVENTORY' || $APP_NAME eq 'SUPPORT' || $APP_NAME eq 'MANAGEMENT' || $APP_NAME eq 'TOOLS'}
									<span class="mk-icon app-icon-list mk-app-icon-svg">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON=$APP_NAME}</span>
								{else}
									<span class="app-icon-list fa {$APP_IMAGE_MAP.$APP_NAME}"></span>
								{/if}
								<span class="app-name textOverflowEllipsis"> {vtranslate("LBL_$APP_NAME",'Vtiger')|upper}</span>
								<span class="fa fa-chevron-right pull-right"></span>
							</div>
						</div>
						<ul class="dropdown-menu app-modules-dropdown" aria-labelledby="{$APP_NAME}_modules_dropdownMenu">
							{* Custom: Management shortcuts (Main Page + Task Board) *}
							{if $APP_NAME eq 'MANAGEMENT'}
								<li>
									<a href="index.php?module=Home&view=MainPage&app=MANAGEMENT" title="{vtranslate('LBL_MAIN_PAGE','Home')}">
										<span class="mk-icon module-icon module-icon-lg"><i class="fa fa-home"></i></span>
										<span class="module-name textOverflowEllipsis"> {vtranslate('LBL_MAIN_PAGE','Home')}</span>
									</a>
								</li>
							{/if}
							{foreach item=moduleModel key=moduleName from=$APP_GROUPED_MENU[$APP_NAME]}
								{* SUPPORT: ẩn Schedule (Calendar) — Schedule chỉ ở MANAGEMENT *}
								{if $APP_NAME eq 'SUPPORT' && ($moduleName eq 'Calendar' || $moduleName eq 'Schedule')}{continue}{/if}
								{* SALES: ProductsServices lives under Kho; hide legacy Products/Services *}
								{if $APP_NAME eq 'SALES' && ($moduleName eq 'Products' || $moduleName eq 'Services' || $moduleName eq 'ProductsServices')}{continue}{/if}
								{* INVENTORY: ẩn Inbound / Storage / Outbound *}
								{if $APP_NAME eq 'INVENTORY' && ($moduleName eq 'GoodsReceipt' || $moduleName eq 'GoodsIssue' || $moduleName eq 'Warehouse')}{continue}{/if}
								{assign var='translatedModuleLabel' value=vtranslate($moduleModel->get('label'),$moduleName )}
								{* Calendar: MANAGEMENT = Schedule, SUPPORT = Activities *}
								{if $moduleName eq 'Calendar' && $APP_NAME eq 'MANAGEMENT'}
									{assign var='translatedModuleLabel' value=vtranslate('LBL_SCHEDULE','Calendar')}
								{elseif $moduleName eq 'Calendar' && $APP_NAME eq 'SUPPORT'}
									{assign var='translatedModuleLabel' value=vtranslate('LBL_ACTIVITIES','Calendar')}
								{elseif $moduleName eq 'Accounts'}
									{assign var='translatedModuleLabel' value='Tuibao'}
								{elseif $moduleName eq 'Contacts'}
									{assign var='translatedModuleLabel' value='Khách hàng'}
								{elseif $moduleName eq 'ProductsServices'}
									{assign var='translatedModuleLabel' value='Hàng hoá'}
								{/if}
								<li>
									{if $moduleName eq 'Reports' && $APP_NAME eq 'MANAGEMENT'}
										<a href="index.php?module=Reports&view=Management&app=MANAGEMENT" title="{$translatedModuleLabel}">
											<span class="mk-icon module-icon module-icon-lg"><i class="fa fa-bar-chart"></i></span>
											<span class="module-name textOverflowEllipsis"> {$translatedModuleLabel}</span>
										</a>
									{else}
										<a href="{$moduleModel->getDefaultUrl()}&app={$APP_NAME}" title="{$translatedModuleLabel}">
											{assign var=MK_MOD_FA value=''}
											{if $moduleName eq 'Campaigns'}{assign var=MK_MOD_FA value='fa-bullhorn'}
											{elseif $moduleName eq 'Leads'}{assign var=MK_MOD_FA value='fa-user-plus'}
											{elseif $moduleName eq 'Plans'}{assign var=MK_MOD_FA value='fa-calendar-o'}
											{elseif $moduleName eq 'Potentials'}{assign var=MK_MOD_FA value='fa-dollar'}
											{elseif $moduleName eq 'Quotes'}{assign var=MK_MOD_FA value='fa-file-text-o'}
											{elseif $moduleName eq 'SalesOrder'}{assign var=MK_MOD_FA value='fa-shopping-cart'}
											{elseif $moduleName eq 'ProductsServices'}{assign var=MK_MOD_FA value='fa-cubes'}
											{elseif $moduleName eq 'Contacts'}{assign var=MK_MOD_FA value='fa-user'}
											{elseif $moduleName eq 'Accounts'}{assign var=MK_MOD_FA value='fa-building'}
											{elseif $moduleName eq 'HelpDesk'}{assign var=MK_MOD_FA value='fa-ticket'}
											{elseif $moduleName eq 'GoodsReceipt'}{assign var=MK_MOD_FA value='fa-arrow-down'}
											{elseif $moduleName eq 'Warehouse'}{assign var=MK_MOD_FA value='fa-archive'}
											{elseif $moduleName eq 'GoodsIssue'}{assign var=MK_MOD_FA value='fa-arrow-up'}
											{elseif $moduleName eq 'Calendar' && $APP_NAME eq 'MANAGEMENT'}{assign var=MK_MOD_FA value='fa-calendar-o'}
											{elseif $moduleName eq 'Calendar' && $APP_NAME eq 'SUPPORT'}{assign var=MK_MOD_FA value='fa-tasks'}
											{elseif $moduleName eq 'Activities'}{assign var=MK_MOD_FA value='fa-tasks'}
											{elseif $moduleName eq 'Schedule'}{assign var=MK_MOD_FA value='fa-calendar-o'}
											{elseif $moduleName eq 'Rules'}{assign var=MK_MOD_FA value='fa-gavel'}
											{elseif $moduleName eq 'SupportFAQ'}{assign var=MK_MOD_FA value='fa-question-circle'}
											{elseif $moduleName eq 'Faq'}{assign var=MK_MOD_FA value='fa-question-circle'}
											{elseif $moduleName eq 'Teams'}{assign var=MK_MOD_FA value='fa-users'}
											{elseif $moduleName eq 'DocumentTemplate'}{assign var=MK_MOD_FA value='fa-file-text-o'}
											{/if}
											{if $MK_MOD_FA ne ''}
												<span class="mk-icon module-icon module-icon-lg"><i class="fa {$MK_MOD_FA}"></i></span>
											{else}
												<span class="mk-icon module-icon module-icon-lg">{$moduleModel->getModuleIcon()}</span>
											{/if}
											<span class="module-name textOverflowEllipsis"> {$translatedModuleLabel}</span>
										</a>
									{/if}
								</li>
							{/foreach}
							{if $APP_NAME eq 'INVENTORY'}
								<li>
									<a href="index.php?module=ProductsServices&amp;view=List&amp;app=INVENTORY" title="Hàng hoá">
										<span class="mk-icon module-icon module-icon-lg"><i class="fa fa-cubes"></i></span>
										<span class="module-name textOverflowEllipsis"> Hàng hoá</span>
									</a>
								</li>
							{/if}
						</ul>
					</div>
				{/if}
			{/foreach}
			<div class="app-list-divider"></div>
			{if $USER_MODEL->isAdminUser()}
				{if vtlib_isModuleActive('ExtensionStore')}
					<div class="menu-item app-item app-item-misc" data-default-url="index.php?module=ExtensionStore&parent=Settings&view=ExtensionStore">
						<div class="menu-items-wrapper">
							<span class="app-icon-list fa fa-shopping-cart"></span>
							<span class="app-name textOverflowEllipsis"> {vtranslate('LBL_EXTENSION_STORE', 'Settings:Vtiger')}</span>
						</div>
					</div>
				{/if}
			{/if}
			{if $USER_MODEL->isAdminUser()}
				<div class="dropdown app-modules-dropdown-container dropdown-compact">
					<div class="menu-item app-item dropdown-toggle app-item-misc" data-app-name="TOOLS" id="TOOLS_modules_dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" data-default-url="#">
						<div class="menu-items-wrapper app-menu-items-wrapper">
							<span class="mk-icon app-icon-list"><i class="fa fa-cog"></i></span>
							<span class="app-name textOverflowEllipsis"> {vtranslate('LBL_SETTINGS', 'Settings:Vtiger')}</span>
							{if $USER_MODEL->isAdminUser()}
								<span class="fa fa-chevron-right pull-right"></span>
							{/if}
						</div>
					</div>
					<ul class="dropdown-menu app-modules-dropdown dropdown-modules-compact" aria-labelledby="{$APP_NAME}_modules_dropdownMenu" data-height="0.27">
						<li>
							<a href="?module=Vtiger&parent=Settings&view=Index">
								<span class="mk-icon module-icon module-icon-lg"><i class="fa fa-cog"></i></span>
								<span class="module-name textOverflowEllipsis"> {vtranslate('LBL_CRM_SETTINGS','Vtiger')}</span>
							</a>
						</li>
						<li>
							<a href="?module=Users&parent=Settings&view=List">
								<span class="mk-icon module-icon module-icon-lg"><i class="fa fa-user"></i></span>
								<span class="module-name textOverflowEllipsis"> {vtranslate('LBL_MANAGE_USERS','Vtiger')}</span>
							</a>
						</li>
					</ul>
				</div>
			{/if}
		</div>
	</div>
</div>
