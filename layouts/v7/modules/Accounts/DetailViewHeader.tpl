{* Accounts Detail header: Sales gets hero shell + same includes as Vtiger for Detail.js compatibility. *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
<div class="detailview-header-block mk-acc-detail-hero-strip">
	<div class="detailview-header mk-acc-detail-hero">
		<div class="mk-acc-detail-hero__row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
		</div>
		<div class="mk-acc-detail-hero__tags">
			{include file="DetailViewTagList.tpl"|vtemplate_path:$MODULE}
		</div>
	</div>
</div>
{else}
<div class=" detailview-header-block">
	<div class="detailview-header">
		<div class="row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
		</div>
	</div>
</div>
{/if}
{/strip}
