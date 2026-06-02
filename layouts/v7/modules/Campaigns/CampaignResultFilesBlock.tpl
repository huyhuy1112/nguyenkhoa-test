{* Server-rendered under Description — no JS relocation *}
<!-- CAMPAIGN_FILES_COUNT={if isset($CAMPAIGN_FILES)}{$CAMPAIGN_FILES|@count}{else}0{/if} -->
{if isset($CAMPAIGN_DETAIL_DESC_FILES.enabled) && $CAMPAIGN_DETAIL_DESC_FILES.enabled}
<div class="mk-campaign-description-files" id="campaign-detail-files-box">
	<h4>{vtranslate('LBL_CAMPAIGN_RESULT_FILES_SECTION', $MODULE_NAME)}</h4>
	<p class="mk-files-help small">{vtranslate('LBL_CAMPAIGN_FILES_DETAIL_HELP', $MODULE_NAME)}</p>
	{if isset($CAMPAIGN_FILES) && $CAMPAIGN_FILES|@count gt 0}
		<ul class="mk-file-list campaigns-detail-file-list">
			{foreach from=$CAMPAIGN_FILES item=f}
				<li class="mk-file-row">
					<span class="mk-file-icon" aria-hidden="true"><i class="fa fa-file-o"></i></span>
					<span class="mk-file-name">
						<span class="mk-file-name-text campaign-detail-file-name">{$f.original_name|escape:'html'}</span>
					</span>
					<span class="mk-file-actions">
						{if !empty($f.is_image)}
							<a href="#"
							   class="js-campaign-file-preview btn btn-default btn-xs mk-btn mk-btn--primary"
							   data-preview-url="{$f.preview_url|escape:'html'}">{vtranslate('LBL_CAMPAIGN_FILE_PREVIEW', $MODULE_NAME)}</a>
						{/if}
						<a href="index.php?module=Campaigns&amp;action=DownloadCampaignFile&amp;record={$RECORD->getId()}&amp;fileid={$f.attachmentsid|default:$f.id}"
						   class="btn btn-default btn-xs mk-btn mk-btn--download"
						   target="_blank" rel="noopener noreferrer">{vtranslate('LBL_CAMPAIGN_FILE_DOWNLOAD', $MODULE_NAME)}</a>
					</span>
				</li>
			{/foreach}
		</ul>
	{else}
		<div class="mk-files-empty">
			<div class="mk-files-empty-title">{vtranslate('LBL_CAMPAIGN_FILES_NONE', $MODULE_NAME)}</div>
			<div class="small">{vtranslate('LBL_CAMPAIGN_FILES_DETAIL_HELP', $MODULE_NAME)}</div>
		</div>
	{/if}
</div>
{/if}
