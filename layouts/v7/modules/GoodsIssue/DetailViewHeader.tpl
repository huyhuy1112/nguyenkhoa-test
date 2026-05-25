{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
<div class="detailview-header-block mk-go-detail-hero-strip">
	<div class="detailview-header mk-go-detail-hero-head">
		<div class="mk-go-detail-hero__row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
		</div>
	</div>
</div>
{else}
<div class="detailview-header-block">
	<div class="detailview-header">
		<div class="row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
		</div>
	</div>
</div>
{/if}
{/strip}
