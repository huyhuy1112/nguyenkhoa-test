{* ServiceContracts ListViewContents: SALES POS shell like Quotes *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<div class="mk-so-page mk-so-list-sales-root mk-so-pos-page mk-so-pos-list-enabled mk-sc-page">
		<div class="mk-so-pos-layout" id="mk-sc-pos-layout">
			<div class="mk-so-pos-main">
				{assign var=MK_POS_SEARCH_ID value='mk-sc-pos-search'}
				{assign var=MK_POS_SEARCH_CLEAR_ID value='mk-sc-pos-search-clear'}
				{assign var=MK_POS_SEARCH_PLACEHOLDER value='Theo tiêu đề hợp đồng'}
				{assign var=MK_POS_TITLE value=vtranslate($MODULE, $MODULE)}
				{include file="partials/MkSalesPosListHeader.tpl"|vtemplate_path:'Vtiger'}
				<div class="mk-so-table-card mk-sc-table-card">
					{capture name=mk_sc_sales_lv}{include file="partials/MkSalesPosListContents.tpl"|vtemplate_path:'Vtiger'}{/capture}
					{$smarty.capture.mk_sc_sales_lv}
				</div>
			</div>
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
