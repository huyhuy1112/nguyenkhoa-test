{* Campaigns list: load Campaign chrome CSS (sidebar/header helpers scoped body[data-module=Campaigns]) *}
{strip}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Campaigns/resources/CampaignDetail.css')}" />
{include file="ListViewContents.tpl"|@vtemplate_path:'Vtiger'}
{/strip}
