{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{if $MENU_STRUCTURE}
{assign var="topMenus" value=$MENU_STRUCTURE->getTop()}
{assign var="moreMenus" value=$MENU_STRUCTURE->getMore()}

<div id="modules-menu" class="modules-menu">
	{if $SELECTED_MENU_CATEGORY eq 'MANAGEMENT'}
		<ul title="{vtranslate('LBL_MAIN_PAGE','Home')}" class="module-qtip">
			<li{if $MODULE eq 'Home' && ($VIEW eq 'MainPage' || $VIEW eq 'DashBoard')} class="active"{/if}>
				<a href="index.php?module=Home&amp;view=MainPage&amp;app=MANAGEMENT">
					<span class="mk-icon menubar-module-icon"><i class="fa fa-home"></i></span>
					<span>{vtranslate('LBL_MAIN_PAGE','Home')}</span>
				</a>
			</li>
		</ul>
	{/if}
	{foreach key=moduleName item=moduleModel from=$SELECTED_CATEGORY_MENU_LIST}
		{if $SELECTED_MENU_CATEGORY eq 'MANAGEMENT' && $moduleName eq 'Home'}{continue}{/if}
		{* SALES: hide legacy Products/Services; ProductsServices lives under Kho *}
		{if $SELECTED_MENU_CATEGORY eq 'SALES' && ($moduleName eq 'Products' || $moduleName eq 'Services' || $moduleName eq 'ProductsServices')}{continue}{/if}
		{assign var='translatedModuleLabel' value=vtranslate($moduleModel->get('label'),$moduleName )}
		{* Calendar: MANAGEMENT = Schedule, SUPPORT = Activities *}
		{if $moduleName eq 'Calendar' && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT'}
			{assign var='translatedModuleLabel' value=vtranslate('LBL_SCHEDULE','Calendar')}
		{elseif $moduleName eq 'Calendar' && $SELECTED_MENU_CATEGORY eq 'SUPPORT'}
			{assign var='translatedModuleLabel' value=vtranslate('LBL_ACTIVITIES','Calendar')}
		{elseif $moduleName eq 'Accounts'}
			{assign var='translatedModuleLabel' value='Hợp đồng nhượng quyền'}
		{elseif $moduleName eq 'Contacts'}
			{assign var='translatedModuleLabel' value='Khách hàng'}
		{elseif $moduleName eq 'ProductsServices'}
			{assign var='translatedModuleLabel' value='Hàng hoá'}
		{elseif $moduleName eq 'ServiceContracts'}
			{assign var='translatedModuleLabel' value='Khách hàng nhượng quyền'}
		{elseif $moduleName eq 'SupportFAQ'}
			{assign var='translatedModuleLabel' value='Cảnh báo'}
		{/if}
		<ul title="{$translatedModuleLabel}" class="module-qtip">
			<li {if $MODULE eq $moduleName}class="active"{else}class=""{/if}>
				<a href="{$moduleModel->getDefaultUrl()}&app={$SELECTED_MENU_CATEGORY}">
					{assign var=MK_MOD_FA value=''}
					{if $moduleName eq 'Campaigns'}{assign var=MK_MOD_FA value='fa-bullhorn'}
					{elseif $moduleName eq 'Leads'}{assign var=MK_MOD_FA value='fa-user-plus'}
					{elseif $moduleName eq 'Plans'}{assign var=MK_MOD_FA value='fa-calendar-o'}
					{elseif $moduleName eq 'Potentials'}{assign var=MK_MOD_FA value='fa-dollar'}
					{elseif $moduleName eq 'Quotes'}{assign var=MK_MOD_FA value='fa-file-text-o'}
					{elseif $moduleName eq 'Invoice'}{assign var=MK_MOD_FA value='fa-file-text-o'}
					{elseif $moduleName eq 'SalesOrder'}{assign var=MK_MOD_FA value='fa-shopping-cart'}
					{elseif $moduleName eq 'ProductsServices'}{assign var=MK_MOD_FA value='fa-cubes'}
					{elseif $moduleName eq 'Contacts'}{assign var=MK_MOD_FA value='fa-user'}
					{elseif $moduleName eq 'Accounts'}{assign var=MK_MOD_FA value='fa-building'}
					{elseif $moduleName eq 'HelpDesk'}{assign var=MK_MOD_FA value='fa-ticket'}
					{elseif $moduleName eq 'GoodsReceipt'}{assign var=MK_MOD_FA value='fa-arrow-down'}
					{elseif $moduleName eq 'Warehouse'}{assign var=MK_MOD_FA value='fa-archive'}
					{elseif $moduleName eq 'GoodsIssue'}{assign var=MK_MOD_FA value='fa-arrow-up'}
					{elseif $moduleName eq 'Calendar' && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT'}{assign var=MK_MOD_FA value='fa-calendar-o'}
					{elseif $moduleName eq 'Calendar' && $SELECTED_MENU_CATEGORY eq 'SUPPORT'}{assign var=MK_MOD_FA value='fa-tasks'}
					{elseif $moduleName eq 'Activities'}{assign var=MK_MOD_FA value='fa-tasks'}
					{elseif $moduleName eq 'Schedule'}{assign var=MK_MOD_FA value='fa-calendar-o'}
					{elseif $moduleName eq 'Rules'}{assign var=MK_MOD_FA value='fa-gavel'}
					{elseif $moduleName eq 'SupportFAQ'}{assign var=MK_MOD_FA value='fa-bell'}
					{elseif $moduleName eq 'Faq'}{assign var=MK_MOD_FA value='fa-question-circle'}
					{elseif $moduleName eq 'Teams'}{assign var=MK_MOD_FA value='fa-users'}
					{elseif $moduleName eq 'DocumentTemplate'}{assign var=MK_MOD_FA value='fa-file-text-o'}
					{/if}
					{if $MK_MOD_FA ne ''}
						<span class="mk-icon menubar-module-icon"><i class="fa {$MK_MOD_FA}"></i></span>
					{else}
						<span class="mk-icon menubar-module-icon">{$moduleModel->getModuleIcon()}</span>
					{/if}
					<span>{$translatedModuleLabel}</span>
				</a>
			</li>
		</ul>
	{foreachelse}
		{if $MODULE eq 'Campaigns'}
			{assign var=_CampMenuMod value=Vtiger_Module_Model::getInstance('Campaigns')}
			<ul title="{vtranslate('Campaigns','Campaigns')}" class="module-qtip mk-campaigns-menubar-fallback">
				<li class="active">
					<a href="{$_CampMenuMod->getDefaultUrl()}&app={if $SELECTED_MENU_CATEGORY}{$SELECTED_MENU_CATEGORY}{else}MARKETING{/if}"
					   title="{vtranslate('LBL_MARKETING','Vtiger')} — {vtranslate('Campaigns','Campaigns')}">
						<span class="mk-icon menubar-module-icon"><i class="fa fa-bullhorn"></i></span>
						<span>Campaigns</span>
					</a>
				</li>
			</ul>
		{/if}
	{/foreach}
	{if $SELECTED_MENU_CATEGORY eq 'INVENTORY' && $MODULE eq 'ProductsServices'}
		<ul title="Hàng hoá" class="module-qtip">
			<li class="active">
				<a href="index.php?module=ProductsServices&amp;view=List&amp;app=INVENTORY">
					<span class="mk-icon menubar-module-icon"><i class="fa fa-cubes"></i></span>
					<span>Hàng hoá</span>
				</a>
			</li>
		</ul>
	{/if}
</div>
{elseif $MODULE eq 'Campaigns'}
	{assign var=_CampMenuMod value=Vtiger_Module_Model::getInstance('Campaigns')}
	<div id="modules-menu" class="modules-menu mk-campaigns-menubar-fallback">
		<ul title="{vtranslate('Campaigns','Campaigns')}" class="module-qtip">
			<li class="active">
				<a href="{$_CampMenuMod->getDefaultUrl()}&app={if $SELECTED_MENU_CATEGORY}{$SELECTED_MENU_CATEGORY}{else}MARKETING{/if}"
				   title="{vtranslate('LBL_MARKETING','Vtiger')} — {vtranslate('Campaigns','Campaigns')}">
					<span class="mk-icon menubar-module-icon"><i class="fa fa-bullhorn"></i></span>
					<span>Campaigns</span>
				</a>
			</li>
		</ul>
</div>
{/if}
