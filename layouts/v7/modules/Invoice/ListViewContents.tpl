{* Invoice ListViewContents: SUPPORT app — Opportunities-style card shell *}
{strip}
{assign var=MK_INV_MK_LIST value=false}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SUPPORT' || $SELECTED_MENU_CATEGORY eq 'TOOLS')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SUPPORT' || $smarty.get.app eq 'TOOLS')) || (isset($smarty.request.app) && ($smarty.request.app eq 'SUPPORT' || $smarty.request.app eq 'TOOLS'))}
	{assign var=MK_INV_MK_LIST value=true}
{/if}
{if $MK_INV_MK_LIST}
	<div class="mk-so-page mk-so-list-sales-root mk-opportunity-page">
		{include file="partials/InvoiceSupportListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-opportunity-table-card">
			{capture name=mk_inv_support_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_inv_support_lv}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
