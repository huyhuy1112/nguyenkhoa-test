{* Horizontal folder navigator — MANAGEMENT Documents list *}
{strip}
<div class="mk-doc-folder-strip" role="navigation" aria-label="{vtranslate('LBL_FOLDERS', $MODULE)}">
	<div class="mk-doc-folder-strip__scroll">
		{foreach item=FOLDER from=$FOLDERS}
			{if $FOLDER}
			{assign var=FID value=$FOLDER->getId()}
			{assign var=FNAME value=$FOLDER->getName()}
			{assign var=IS_ACTIVE value=($FOLDER_ID eq $FID || ($FOLDER_VALUE eq $FNAME))}
			<div class="mk-doc-folder-chip-wrap">
				<a href="index.php?module=Documents&view=List&folder_id={$FID}&folder_value={$FNAME|escape:'url'}{$APP_PARAM}" class="mk-doc-folder-chip{if $IS_ACTIVE} is-active{/if}">
					<i class="fa fa-folder{if $IS_ACTIVE}-open{/if}" aria-hidden="true"></i>
					<span>{$FNAME|escape:'html'}</span>
				</a>
				{if $FNAME neq 'Default' && $FNAME neq 'Google Drive' && $FNAME neq 'Dropbox'}
					<span class="mk-doc-folder-chip__menu">
						<button type="button" class="mk-doc-folder-chip__btn doc-edit-folder" data-folder-id="{$FID}" title="{vtranslate('LBL_EDIT', $MODULE)}"><i class="fa fa-pencil"></i></button>
						<button type="button" class="mk-doc-folder-chip__btn doc-delete-folder" data-deletable="{if !$FOLDER->hasDocuments()}1{else}0{/if}" data-folder-id="{$FID}" title="{vtranslate('LBL_DELETE', $MODULE)}"><i class="fa fa-trash-o"></i></button>
					</span>
				{/if}
			</div>
			{/if}
		{/foreach}
	</div>
	{if $IS_CREATE_PERMITTED}
		<a href="{$ADD_FOLDER_URL}&return_url={$DOCUMENTS_LIST_URL|escape:'url'}{$APP_PARAM}" class="mk-doc-folder-chip mk-doc-folder-chip--add" title="{vtranslate('LBL_ADD_FOLDER', $MODULE)}">
			<i class="fa fa-plus" aria-hidden="true"></i>
		</a>
	{/if}
</div>
{/strip}
