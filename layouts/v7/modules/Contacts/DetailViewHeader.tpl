{* Contacts Detail header: Sales hero shell + stock includes for Detail.js compatibility. *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
<div class="detailview-header-block mk-contact-detail-hero-strip">
	<div class="detailview-header mk-contact-hero mk-contact-detail-hero">
		<div class="mk-contact-detail-hero__row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
		</div>
		<div class="mk-contact-detail-hero__tags">
			{include file="DetailViewTagList.tpl"|vtemplate_path:'Vtiger'}
		</div>
	</div>
</div>
{else}
<div class=" detailview-header-block">
	<div class="detailview-header">
		<div class="row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|@vtemplate_path:'Vtiger'}
		</div>
	</div>
</div>
{/if}
{/strip}
