{* Campaigns Detail header — Marketing hero shell *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
<div class="detailview-header-block mk-camp-detail-hero-strip">
	<div class="detailview-header mk-camp-detail-hero-head">
		<div class="mk-camp-detail-hero__row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
		</div>
		<div class="mk-camp-detail-hero__tags mk-camp-detail-hero__tags--inset">
			{include file="DetailViewTagList.tpl"|vtemplate_path:'Vtiger'}
		</div>
	</div>
</div>
{else}
<div class="detailview-header-block">
	<div class="detailview-header">
		<div class="row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|@vtemplate_path:'Vtiger'}
		</div>
	</div>
</div>
{/if}
{/strip}
