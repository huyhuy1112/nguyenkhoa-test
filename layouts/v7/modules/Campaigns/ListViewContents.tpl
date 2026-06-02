{* Campaigns ListViewContents: MARKETING Figma list card shell *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	<div class="mk-so-page mk-so-list-sales-root mk-camp-page">
		{include file="partials/CampaignsListHeader.tpl"|vtemplate_path:$MODULE}
		<div class="mk-so-table-card mk-camp-table-card">
			{capture name=mk_camp_marketing_lv}{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}{/capture}
			{$smarty.capture.mk_camp_marketing_lv}
		</div>
	</div>
{else}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Campaigns/resources/CampaignDetail.css')}" />
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
