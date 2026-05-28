{* Campaigns Edit: ROI + phase slots + description files (multipart → vtiger_attachments) *}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Campaigns/resources/CampaignDetail.css')}" />
{if isset($CAMPAIGN_INITIAL_PHASE_COUNT)}
<input type="hidden" id="campaigns-phase-count-initial" value="{$CAMPAIGN_INITIAL_PHASE_COUNT}" />
{/if}
<div id="campaign-files-edit-strings" class="hide"
	data-help="{vtranslate('LBL_CAMPAIGN_FILES_EDIT_HELP', 'Campaigns')|escape:'html'}"></div>
{if isset($CAMPAIGN_FILES_JSON)}
<script type="application/json" id="campaign-files-json">{$CAMPAIGN_FILES_JSON nofilter}</script>
{/if}
{assign var=MK_IS_MARKETING value=($smarty.get.app eq 'MARKETING')}
{assign var=MK_IS_CREATE value=empty($RECORD_ID)}
{if $MK_IS_MARKETING}
	{include file="partials/CampaignMkEnterprise.tpl"|vtemplate_path:$MODULE}
{else}
	{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
{/if}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/Edit.js')}"></script>
