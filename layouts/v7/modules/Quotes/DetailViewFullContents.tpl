{* Quotes full detail: block fields + simplified line items (SALES-friendly). *}
{strip}
<form id="detailView" method="POST">
	{include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
	{include file='LineItemsDetail.tpl'|@vtemplate_path:$MODULE_NAME}
</form>
{/strip}
