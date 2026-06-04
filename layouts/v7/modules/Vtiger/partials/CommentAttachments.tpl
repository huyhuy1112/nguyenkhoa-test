{* Renders ModComments file attachments (links + inline image preview). *}
{strip}
{assign var="FILE_DETAILS" value=$COMMENT->getFileNameAndDownloadURL()}
{if !empty($FILE_DETAILS)}
	<div class="mk-comment-attachments">
		{foreach key=index item=FILE_DETAIL from=$FILE_DETAILS}
			{assign var="FILE_NAME" value=$FILE_DETAIL['trimmedFileName']}
			{if empty($FILE_NAME)}{assign var="FILE_NAME" value=$FILE_DETAIL['rawFileName']}{/if}
			{if !empty($FILE_NAME)}
				<div class="commentAttachmentName mk-comment-attachment-item">
					{if !empty($FILE_DETAIL.isImage)}
						<div class="mk-comment-attachment-image">
							<a href="{$FILE_DETAIL.url}" target="_blank" rel="noopener noreferrer" title="{$FILE_DETAIL.rawFileName|escape:'html'}">
								<img src="{$FILE_DETAIL.inlineUrl}" alt="{$FILE_DETAIL.rawFileName|escape:'html'}" class="mk-comment-attachment-img" loading="lazy" />
							</a>
						</div>
					{/if}
					<div class="filePreview clearfix">
						<span class="fa fa-paperclip cursorPointer"></span>&nbsp;&nbsp;
						<a class="previewfile" onclick="Vtiger_Detail_Js.previewFile(event,{$COMMENT->get('id')},{$FILE_DETAIL['attachmentId']});" data-filename="{$FILE_NAME|escape:'html'}" href="javascript:void(0)" name="viewfile">
							<span title="{$FILE_DETAIL['rawFileName']|escape:'html'}" style="line-height:1.5em;">{$FILE_NAME}</span>&nbsp;
						</a>&nbsp;
						<a name="downloadfile" href="{$FILE_DETAIL['url']}" target="_blank" rel="noopener noreferrer">
							<i title="{vtranslate('LBL_DOWNLOAD_FILE',$MODULE_NAME)}" class="fa fa-download alignMiddle"></i>
						</a>
					</div>
				</div>
			{/if}
		{/foreach}
	</div>
{/if}
{/strip}
