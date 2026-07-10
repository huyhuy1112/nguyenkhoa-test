{* Quotes ListViewContents: SALES POS shell like SalesOrder; else stock Vtiger. *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<div class="mk-so-page mk-so-list-sales-root mk-so-pos-page mk-so-pos-list-enabled mk-qt-page">
		<div class="mk-so-pos-layout" id="mk-qt-pos-layout">
			<div class="mk-so-pos-main">
				{include file="partials/QuotesPosListHeader.tpl"|vtemplate_path:$MODULE}
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
