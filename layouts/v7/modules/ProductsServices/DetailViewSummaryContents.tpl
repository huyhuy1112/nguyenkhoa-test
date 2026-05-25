{* ProductsServices DetailViewSummaryContents: SALES uses SummaryViewWidgets grid (SalesOrder pattern). *}
{strip}
    <form id="detailView" method="POST">
        {include file='SummaryViewWidgets.tpl'|vtemplate_path:$MODULE_NAME}
    </form>
{/strip}
