{strip}
{assign var=MK_INV_MK_DETAIL value=false}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SUPPORT' || $SELECTED_MENU_CATEGORY eq 'TOOLS')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SUPPORT' || $smarty.get.app eq 'TOOLS')) || (isset($smarty.request.app) && ($smarty.request.app eq 'SUPPORT' || $smarty.request.app eq 'TOOLS'))}
	{assign var=MK_INV_MK_DETAIL value=true}
{/if}
{if $MK_INV_MK_DETAIL}
<div class="detailview-header-block mk-inv-detail-hero-strip">
	<div class="detailview-header mk-inv-detail-hero-head">
		<div class="mk-inv-detail-hero__row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
		</div>
		<div class="mk-inv-detail-hero__tags mk-inv-detail-hero__tags--inset">
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
