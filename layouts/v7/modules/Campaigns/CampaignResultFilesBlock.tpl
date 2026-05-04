{* Server-rendered under Description — no JS relocation *}
<!-- CAMPAIGN_FILES_COUNT={if isset($CAMPAIGN_FILES)}{$CAMPAIGN_FILES|@count}{else}0{/if} -->
{if isset($CAMPAIGN_DETAIL_DESC_FILES.enabled) && $CAMPAIGN_DETAIL_DESC_FILES.enabled}
<div class="mk-campaign-description-files" id="campaign-detail-files-box">
	<h4>{vtranslate('LBL_CAMPAIGN_RESULT_FILES_SECTION', $MODULE_NAME)}</h4>
	<p class="text-muted small" style="margin-bottom:10px;">{vtranslate('LBL_CAMPAIGN_FILES_DETAIL_HELP', $MODULE_NAME)}</p>
	{if isset($CAMPAIGN_FILES) && $CAMPAIGN_FILES|@count gt 0}
		<ul class="list-unstyled campaigns-detail-file-list" style="margin-bottom:10px;">
			{foreach from=$CAMPAIGN_FILES item=f}
				<li style="margin-bottom:6px;">
					<span class="campaign-detail-file-name">{$f.original_name|escape:'html'}</span>
					{if !empty($f.is_image)}
						<a href="#" class="js-campaign-file-preview btn btn-default btn-xs" style="margin-left:8px;"
							data-preview-url="{$f.preview_url|escape:'html'}">{vtranslate('LBL_CAMPAIGN_FILE_PREVIEW', $MODULE_NAME)}</a>
					{/if}
					<a href="index.php?module=Campaigns&amp;action=DownloadCampaignFile&amp;record={$RECORD->getId()}&amp;fileid={$f.attachmentsid|default:$f.id}" class="btn btn-default btn-xs" style="margin-left:6px;" target="_blank" rel="noopener noreferrer">{vtranslate('LBL_CAMPAIGN_FILE_DOWNLOAD', $MODULE_NAME)}</a>
				</li>
			{/foreach}
		</ul>
	{else}
		<p class="text-muted small">{vtranslate('LBL_CAMPAIGN_FILES_NONE', $MODULE_NAME)}</p>
	{/if}
</div>
{/if}
