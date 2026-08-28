{* ProductsServices ListViewContents: SALES Figma list card shell *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<div class="mk-so-page mk-so-list-sales-root mk-ps-page">
		{include file="partials/ProductsServicesListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-ps-table-card">
			{capture name=mk_ps_list_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_ps_list_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
