{* POS list row: star only (KiotViet-style) *}
{strip}
<div class="mk-so-pos-star-cell table-actions">
	{if $LISTVIEW_ENTRY->get('starred') eq vtranslate('LBL_YES', $MODULE)}
		{assign var=STARRED value=true}
	{else}
		{assign var=STARRED value=false}
	{/if}
	{if $MODULE_MODEL->isStarredEnabled()}
		<a class="markStar fa icon action mk-so-pos-star-btn {if $STARRED} fa-star active {else} fa-star-o{/if}"
		   title="{if $STARRED}{vtranslate('LBL_STARRED', $MODULE)}{else}{vtranslate('LBL_NOT_STARRED', $MODULE)}{/if}"></a>
	{/if}
</div>
{/strip}
