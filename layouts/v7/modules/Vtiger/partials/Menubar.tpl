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
	{foreach key=moduleName item=moduleModel from=$SELECTED_CATEGORY_MENU_LIST}
		{* UI cleanup: hide separate Products/Services from sidebar module strip *}
		{if $moduleName eq 'Products' || $moduleName eq 'Services'}{continue}{/if}
		{assign var='translatedModuleLabel' value=vtranslate($moduleModel->get('label'),$moduleName )}
		{* Calendar: MANAGEMENT = Schedule, SUPPORT = Activities *}
		{if $moduleName eq 'Calendar' && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT'}
			{assign var='translatedModuleLabel' value=vtranslate('LBL_SCHEDULE','Calendar')}
		{elseif $moduleName eq 'Calendar' && $SELECTED_MENU_CATEGORY eq 'SUPPORT'}
			{assign var='translatedModuleLabel' value=vtranslate('LBL_ACTIVITIES','Calendar')}
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
					{elseif $moduleName eq 'SupportFAQ'}{assign var=MK_MOD_FA value='fa-question-circle'}
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
	{/foreach}
</div>
{/if}
