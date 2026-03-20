{* Campaigns Edit: reuse core Vtiger EditView and add ROI JS *}
{if $smarty.get.app eq 'MARKETING'}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
	<div class="mk">
		<div class="mk-page">
			{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
		</div>
	</div>
{else}
	{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
{/if}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/Edit.js')}"></script>

