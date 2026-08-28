{* Potentials Detail header: Sales gets hero shell + same Vtiger includes for Detail.js compatibility. *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
<div class="detailview-header-block mk-opportunity-detail-hero-strip">
	<div class="detailview-header mk-opportunity-detail-hero">
		<div class="mk-opportunity-detail-hero__row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
		</div>
		<div class="mk-opportunity-detail-hero__tags">
			{include file="DetailViewTagList.tpl"|vtemplate_path:$MODULE}
		</div>
	</div>
</div>
{else}
<div class=" detailview-header-block">
	<div class="detailview-header">
		<div class="row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:'Vtiger'}
		</div>
	</div>
</div>
{/if}
{/strip}
