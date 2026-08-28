{* Invoice POS list row: checkbox + star (mass actions in toolbar) *}
{strip}
{if !isset($SELECTED_MENU_CATEGORY)}
	{assign var=SELECTED_MENU_CATEGORY value='SALES'}
{/if}
<div class="mk-so-pos-row-actions table-actions">
	{if !$SEARCH_MODE_RESULTS}
		<span class="mk-so-pos-check input">
			<input type="checkbox" value="{$LISTVIEW_ENTRY->getId()}" class="listViewEntriesCheckBox" title="{vtranslate('LBL_SELECT', $MODULE)}" />
		</span>
	{else}
		<span class="mk-so-pos-check input mk-so-pos-check--spacer" aria-hidden="true"></span>
	{/if}
	{if $LISTVIEW_ENTRY->get('starred') eq vtranslate('LBL_YES', $MODULE)}
		{assign var=STARRED value=true}
	{else}
		{assign var=STARRED value=false}
	{/if}
	{if $MODULE_MODEL->isStarredEnabled()}
		<span class="mk-so-pos-star-slot">
			<a class="markStar fa icon action mk-so-pos-star-btn {if $STARRED} fa-star active {else} fa-star-o{/if}"
			   title="{if $STARRED}{vtranslate('LBL_STARRED', $MODULE)}{else}{vtranslate('LBL_NOT_STARRED', $MODULE)}{/if}"></a>
		</span>
	{else}
		<span class="mk-so-pos-star-slot" aria-hidden="true"></span>
	{/if}
</div>
{/strip}
