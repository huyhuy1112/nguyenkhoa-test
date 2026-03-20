{* Accounts/Organizations ListViewContents override for MARKETING app (visual only) *}
{strip}
{if $smarty.get.app eq 'MARKETING'}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
	<div class="mk">
		<div class="mk-page">
			{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
		</div>
	</div>
{else}
	{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}

