{* Quotes ListViewContents: SALES POS shell like SalesOrder; else stock Vtiger. *}
{strip}
{* POST AJAX has no smarty.get.app — always treat Quotes list as SALES POS when category missing/Sales. *}
{assign var=MK_QT_IS_SALES value=false}
{if (isset($SELECTED_MENU_CATEGORY) && strtoupper($SELECTED_MENU_CATEGORY) eq 'SALES')}
	{assign var=MK_QT_IS_SALES value=true}
{elseif isset($smarty.request.app) && strtoupper($smarty.request.app) eq 'SALES'}
	{assign var=MK_QT_IS_SALES value=true}
{elseif isset($smarty.get.app) && strtoupper($smarty.get.app) eq 'SALES'}
	{assign var=MK_QT_IS_SALES value=true}
{elseif !isset($SELECTED_MENU_CATEGORY) || $SELECTED_MENU_CATEGORY eq ''}
	{* Quotes module list defaults to SALES POS shell *}
	{assign var=MK_QT_IS_SALES value=true}
{/if}
{if $MK_QT_IS_SALES}
	{if !isset($MK_QUOTE_SCOPE) || $MK_QUOTE_SCOPE eq ''}
		{assign var=MK_QUOTE_SCOPE value='all'}
	{/if}
	{if !isset($SELECTED_MENU_CATEGORY) || $SELECTED_MENU_CATEGORY eq ''}
		{assign var=SELECTED_MENU_CATEGORY value='SALES'}
	{/if}
	<div class="mk-so-page mk-so-list-sales-root mk-so-pos-page mk-so-pos-list-enabled mk-qt-page" data-mk-quote-scope="{$MK_QUOTE_SCOPE|escape}">
		<div class="mk-so-pos-layout" id="mk-qt-pos-layout">
			<div class="mk-so-pos-main">
				{include file="partials/QuotesPosListHeader.tpl"|vtemplate_path:$MODULE}
				<nav class="mk-qt-scope-tabs" role="tablist" aria-label="Lọc báo giá">
					<button type="button" class="mk-qt-scope-tab{if $MK_QUOTE_SCOPE neq 'franchise'} is-active{/if}" role="tab" aria-selected="{if $MK_QUOTE_SCOPE neq 'franchise'}true{else}false{/if}" data-mk-quote-scope="all" id="mk-qt-scope-all">
						<span class="mk-qt-scope-tab__label">Tất cả</span>
					</button>
					<button type="button" class="mk-qt-scope-tab{if $MK_QUOTE_SCOPE eq 'franchise'} is-active{/if}" role="tab" aria-selected="{if $MK_QUOTE_SCOPE eq 'franchise'}true{else}false{/if}" data-mk-quote-scope="franchise" id="mk-qt-scope-franchise">
						<span class="mk-qt-scope-tab__label">Nhượng quyền</span>
					</button>
				</nav>
				<input type="hidden" id="mk-quote-scope" name="mk_quote_scope" value="{$MK_QUOTE_SCOPE|escape}" />
				<div class="mk-so-table-card mk-qt-table-card">
					{capture name=mk_qt_sales_lv}{include file="partials/ListViewContentsPos.tpl"|vtemplate_path:$MODULE}{/capture}
					{$smarty.capture.mk_qt_sales_lv}
				</div>
			</div>
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
