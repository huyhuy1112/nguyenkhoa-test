{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{assign var=APP_IMAGE_MAP value=Vtiger_MenuStructure_Model::getAppIcons()}
{assign var=MK_INDICATOR_FA value=''}
{if $MODULE eq 'Home' || !$MODULE}{assign var=MK_INDICATOR_FA value='fa-tachometer'}
{elseif $SELECTED_MENU_CATEGORY eq 'MARKETING'}{assign var=MK_INDICATOR_FA value='fa-bullhorn'}
{elseif $SELECTED_MENU_CATEGORY eq 'SALES'}{assign var=MK_INDICATOR_FA value='fa-dollar'}
{elseif $SELECTED_MENU_CATEGORY eq 'INVENTORY'}{assign var=MK_INDICATOR_FA value='fa-cubes'}
{elseif $SELECTED_MENU_CATEGORY eq 'SUPPORT'}{assign var=MK_INDICATOR_FA value='fa-ticket'}
{elseif $SELECTED_MENU_CATEGORY eq 'MANAGEMENT'}{assign var=MK_INDICATOR_FA value='fa-sitemap'}
{elseif $SELECTED_MENU_CATEGORY eq 'PROJECT'}{assign var=MK_INDICATOR_FA value='fa-briefcase'}
{elseif $SELECTED_MENU_CATEGORY eq 'TOOLS'}{assign var=MK_INDICATOR_FA value='fa-wrench'}
{/if}

<div class="col-sm-1 col-xs-2 app-indicator-icon-container app-{$SELECTED_MENU_CATEGORY} app-trigger cursorPointer" title="{if $MODULE eq 'Home' || !$MODULE} {vtranslate('LBL_DASHBOARD')} {else}{vtranslate("LBL_$SELECTED_MENU_CATEGORY")}{/if} (click để mở menu)">
	<div class="row">
		<span class="app-indicator-icon fa {if $MK_INDICATOR_FA ne ''}{$MK_INDICATOR_FA}{else}{$APP_IMAGE_MAP[$SELECTED_MENU_CATEGORY]}{/if}"></span>
	</div>
</div>

{include file="modules/Vtiger/partials/SidebarAppMenu.tpl"}