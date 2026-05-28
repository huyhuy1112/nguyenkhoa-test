{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	<div class="mk-plan-detail-fields-wrap">
		{include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
	</div>
{else}
	{include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
{/if}
{/strip}
