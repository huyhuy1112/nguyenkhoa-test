{*+**********************************************************************************
* Documents List — Premium SaaS content area (MANAGEMENT shell unchanged)
*************************************************************************************}
{strip}
{include file="PicklistColorMap.tpl"|vtemplate_path:$MODULE}

{if !isset($SELECTED_MENU_CATEGORY)}
	{assign var=SELECTED_MENU_CATEGORY value=""}
{/if}
{if !isset($FOLDERS) || $FOLDERS === null}
	{assign var=FOLDERS value=array()}
{/if}
{if !isset($ADD_FOLDER_URL)}
	{assign var=ADD_FOLDER_URL value="index.php?module=Documents&view=AddFolder"}
{/if}
{if !isset($DOCUMENTS_LIST_URL)}
	{assign var=DOCUMENTS_LIST_URL value="index.php?module=Documents&view=List"}
{/if}

{assign var=APP_PARAM value=""}
{if $SELECTED_MENU_CATEGORY}
	{assign var=APP_PARAM value="&app={$SELECTED_MENU_CATEGORY}"}
{/if}

{assign var=FOLDER_COUNT value=$FOLDERS|@count}

<div class="doc-management-view mk-doc-workspace" data-folder-id="{if isset($FOLDER_ID) && $FOLDER_ID !== '' && $FOLDER_ID !== null}{$FOLDER_ID}{/if}" data-folder-value="{if isset($FOLDER_VALUE) && $FOLDER_VALUE !== ''}{$FOLDER_VALUE|escape:'html'}{/if}" data-mk-doc-module="{$MODULE}">
	<aside class="mk-doc-rail" aria-label="{vtranslate('LBL_FOLDERS', $MODULE)}">
		<div class="mk-doc-rail__head">
			<div class="mk-doc-rail__head-text">
				<span class="mk-doc-rail__label">{vtranslate('LBL_FOLDERS', $MODULE)}</span>
				<span class="mk-doc-rail__count">{$FOLDER_COUNT}</span>
			</div>
		</div>
		<div class="mk-doc-rail__search">
			<svg class="mk-doc-rail__search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.4"/><path d="M10.5 10.5 14 14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
			<input type="search" id="mkDocFolderSearch" class="mk-doc-rail__search-input" placeholder="Search folders…" autocomplete="off" />
		</div>
		<nav class="mk-doc-rail__tree doc-management-folders" id="mkDocFolderTree">
			{foreach item=FOLDER from=$FOLDERS}
				{if $FOLDER}
				{assign var=FID value=$FOLDER->getId()}
				{assign var=FNAME value=$FOLDER->getName()}
				{assign var=IS_ACTIVE value=($FOLDER_ID eq $FID || ($FOLDER_VALUE eq $FNAME))}
				<div class="mk-doc-folder doc-management-folder-row" data-folder-name="{$FNAME|escape:'html'}">
					<a href="index.php?module=Documents&view=List&folder_id={$FID}&folder_value={$FNAME|escape:'url'}{$APP_PARAM}" class="mk-doc-folder__link doc-management-folder-item{if $IS_ACTIVE} active is-active{/if}">
						<span class="mk-doc-folder__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M3 5.5A1.5 1.5 0 0 1 4.5 4h3l1.2 1.5H13.5A1.5 1.5 0 0 1 15 7v6.5A1.5 1.5 0 0 1 13.5 15h-9A1.5 1.5 0 0 1 3 13.5V5.5Z" stroke="currentColor" stroke-width="1.35"/></svg>
						</span>
						<span class="mk-doc-folder__name doc-folder-name">{$FNAME|escape:'html'}</span>
					</a>
					{if $FNAME neq 'Default' && $FNAME neq 'Google Drive' && $FNAME neq 'Dropbox'}
						<div class="mk-doc-folder__actions">
							<button type="button" class="mk-doc-folder__act doc-edit-folder" data-folder-id="{$FID}" title="{vtranslate('LBL_EDIT', $MODULE)}">
								<i class="fa fa-pencil" aria-hidden="true"></i>
							</button>
							<button type="button" class="mk-doc-folder__act doc-delete-folder" data-deletable="{if !$FOLDER->hasDocuments()}1{else}0{/if}" data-folder-id="{$FID}" title="{vtranslate('LBL_DELETE', $MODULE)}">
								<i class="fa fa-trash-o" aria-hidden="true"></i>
							</button>
						</div>
					{/if}
				</div>
				{/if}
			{/foreach}
			<p class="mk-doc-rail__empty" id="mkDocFolderEmpty" hidden>No folders match your search.</p>
		</nav>
		{if $IS_CREATE_PERMITTED}
			<div class="mk-doc-rail__foot">
				<a href="{$ADD_FOLDER_URL}&return_url={$DOCUMENTS_LIST_URL|escape:'url'}{$APP_PARAM}" class="mk-doc-rail__add doc-management-add-folder">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
					<span>{vtranslate('LBL_ADD_FOLDER', $MODULE)}</span>
				</a>
			</div>
		{/if}
	</aside>

	<div class="mk-doc-panel doc-management-main">
		{include file="partials/DocumentsListHeader.tpl"|vtemplate_path:$MODULE}

		<div class="mk-doc-toolbar">
			<div class="mk-doc-toolbar__left">
				{if isset($FOLDER_VALUE) && $FOLDER_VALUE !== ''}
					<div class="mk-doc-chip mk-doc-chip--folder">
						<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M2.5 4.5A1 1 0 0 1 3.5 3.5h2.2L7 5h3.5A1 1 0 0 1 11.5 6v4.5a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1V4.5Z" stroke="currentColor" stroke-width="1.2"/></svg>
						<span>{$FOLDER_VALUE|escape:'html'}</span>
					</div>
				{/if}
			</div>
			<div class="mk-doc-toolbar__right">
				{if isset($TAGS) && $TAGS|@count > 0}
					<div class="mk-doc-tag-filter doc-tag-filter dropdown">
						<button type="button" class="mk-doc-btn mk-doc-btn--soft dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fa fa-tags" aria-hidden="true"></i>
							<span>{vtranslate('LBL_TAGS', $MODULE)|default:'Tags'}</span>
							<span class="mk-doc-caret"></span>
						</button>
						<ul class="dropdown-menu dropdown-menu-right mk-doc-dropdown">
							<li><a href="index.php?module=Documents&view=List&folder_id={$FOLDER_ID}&folder_value={$FOLDER_VALUE|escape:'url'}{$APP_PARAM}">{vtranslate('LBL_ALL', $MODULE)|default:'All'}</a></li>
							<li role="separator" class="divider"></li>
							{foreach item=TAG_MODEL from=$TAGS}
								<li><a href="index.php?module=Documents&view=List&tag={$TAG_MODEL->getId()}&folder_id={$FOLDER_ID}&folder_value={$FOLDER_VALUE|escape:'url'}{$APP_PARAM}">{$TAG_MODEL->getName()|escape:'html'}</a></li>
							{/foreach}
						</ul>
					</div>
				{/if}
			</div>
		</div>

		<div class="mk-doc-dropzone" id="mkDocDropzone" aria-hidden="true">
			<div class="mk-doc-dropzone__inner">
				<svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M24 8v20M14 18l10-10 10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 32v4a4 4 0 0 0 4 4h20a4 4 0 0 0 4-4v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				<p class="mk-doc-dropzone__title">Drop files to upload</p>
				<p class="mk-doc-dropzone__sub">Release to open the upload flow</p>
			</div>
		</div>

		<div class="mk-doc-table-card" id="mkDocTableCard">
			<div class="mk-doc-table-card__body" id="mkDocTableBody">
				{include file="ListViewContents.tpl"|vtemplate_path:'Vtiger'}
			</div>
			<div class="mk-doc-empty" id="mkDocEmpty" hidden>
				<div class="mk-doc-empty__icon" aria-hidden="true">
					<svg width="56" height="56" viewBox="0 0 56 56" fill="none"><path d="M14 18a4 4 0 0 1 4-4h8l4 5h14a4 4 0 0 1 4 4v18a4 4 0 0 1-4 4H18a4 4 0 0 1-4-4V18Z" stroke="currentColor" stroke-width="2"/><path d="M28 30v10M23 35h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</div>
				<h3 class="mk-doc-empty__title">No documents yet</h3>
				<p class="mk-doc-empty__text">Upload or create a document to populate this folder.</p>
				{if $IS_CREATE_PERMITTED && $CREATE_DOCUMENT_URL}
					<a href="{$CREATE_DOCUMENT_URL}" class="mk-doc-btn mk-doc-btn--primary">{vtranslate('LBL_NEW_DOCUMENT', $MODULE)}</a>
				{/if}
			</div>
		</div>

	</div>
</div>
{/strip}
