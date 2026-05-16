{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
{if $smarty.get.app eq 'MARKETING'}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
{/if}
	<form id="detailView" class="clearfix {if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}mk-acc-detail-summary-form{/if}" method="POST" style="position: relative">
		<div class="col-lg-12 resizable-summary-view {if $smarty.get.app eq 'MARKETING'}mk mk-page{/if} {if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}mk-acc-detail-summary-col{/if}">
			{include file='SummaryViewWidgets.tpl'|vtemplate_path:$MODULE_NAME}
		</div>
	</form>
{/strip}