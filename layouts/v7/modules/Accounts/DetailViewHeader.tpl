{* Accounts Detail header: Opp-style flat hero (identity + actions; tags inline in title). *}
{strip}
{if !empty($MK_ACCOUNTS_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
<div class="detailview-header-block mk-acc-detail-hero-strip">
	<div class="detailview-header mk-acc-detail-hero mk-acc-detail-hero--flat">
		<div class="mk-acc-detail-hero__row mk-acc-detail-hero__main">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
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
