{*+**********************************************************************************
* Document Management - List view with folder sidebar (Management)
*************************************************************************************}
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
	{assign var=APP_PARAM value="&app=`$SELECTED_MENU_CATEGORY`"}
{/if}

<div class="doc-management-view" data-folder-id="{if isset($FOLDER_ID) && $FOLDER_ID !== '' && $FOLDER_ID !== null}{$FOLDER_ID}{/if}" data-folder-value="{if isset($FOLDER_VALUE) && $FOLDER_VALUE !== ''}{$FOLDER_VALUE|escape:'html'}{/if}">
	<aside class="doc-management-sidebar">
		<div class="doc-management-sidebar-header">
			<span class="doc-management-sidebar-title">{vtranslate('LBL_FOLDERS', $MODULE)}</span>
		</div>
		<nav class="doc-management-folders">
			{foreach item=FOLDER from=$FOLDERS}
				{if $FOLDER}
				{assign var=FID value=$FOLDER->getId()}
				{assign var=FNAME value=$FOLDER->getName()}
				{assign var=IS_ACTIVE value=($FOLDER_ID eq $FID || ($FOLDER_VALUE eq $FNAME))}
				<div class="doc-management-folder-row" style="display:flex;align-items:center;margin-bottom:4px;">
					<a href="index.php?module=Documents&view=List&folder_id={$FID}&folder_value={$FNAME|escape:'url'}{$APP_PARAM}" class="doc-management-folder-item{if $IS_ACTIVE} active{/if}" style="flex:1;">
						<i class="fa fa-folder{if $IS_ACTIVE}-open{/if} doc-folder-icon"></i>
						<span class="doc-folder-name">{$FNAME|escape:'html'}</span>
					</a>
					{if $FNAME neq 'Default' && $FNAME neq 'Google Drive' && $FNAME neq 'Dropbox'}
						<span class="fa fa-pencil-square-o doc-edit-folder cursorPointer" data-folder-id="{$FID}" title="Edit" style="margin-right:6px;"></span>
						<span class="fa fa-trash doc-delete-folder cursorPointer" data-deletable="{if !$FOLDER->hasDocuments()}1{else}0{/if}" data-folder-id="{$FID}" title="Delete"></span>
					{/if}
				</div>
				{/if}
			{/foreach}
		</nav>
		{if $IS_CREATE_PERMITTED}
			<div class="doc-management-sidebar-footer">
				<a href="{$ADD_FOLDER_URL}&return_url={$DOCUMENTS_LIST_URL|escape:'url'}{$APP_PARAM}" class="doc-management-add-folder">
					<i class="fa fa-plus-circle"></i> {vtranslate('LBL_ADD_FOLDER', $MODULE)}
				</a>
			</div>
		{/if}
	</aside>
		<div class="doc-management-main">
		<div class="doc-management-toolbar clearfix">
			{if $IS_CREATE_PERMITTED && $CREATE_DOCUMENT_URL}
				<a href="{$CREATE_DOCUMENT_URL}" class="btn btn-primary doc-btn-new-document">
					<i class="fa fa-plus"></i> {vtranslate('LBL_NEW_DOCUMENT', $MODULE)}
				</a>
			{/if}
			{* Nút History: xem lịch sử thao tác Documents (sử dụng ModTracker) *}
			<a href="index.php?module=Documents&view=History{$APP_PARAM}" class="btn btn-default doc-btn-history">
				<i class="fa fa-history"></i> History
			</a>
			{if isset($FOLDER_ID) && $FOLDER_ID !== '' && $FOLDER_ID !== null}
				<span class="doc-current-folder-hint text-muted small">
					<i class="fa fa-folder-open-o"></i> {vtranslate('LBL_VIEWING_FOLDER', $MODULE)|default:'Đang xem folder'}: <strong>{$FOLDER_VALUE|escape:'html'}</strong>
				</span>
			{/if}
			{if isset($TAGS) && $TAGS|@count > 0}
				<div class="doc-tag-filter dropdown pull-right">
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<i class="fa fa-tags"></i> {vtranslate('LBL_TAGS', $MODULE)|default:'Tag'} <span class="caret"></span>
					</button>
					<ul class="dropdown-menu dropdown-menu-right">
						<li><a href="index.php?module=Documents&view=List&folder_id={$FOLDER_ID}&folder_value={$FOLDER_VALUE|escape:'url'}{$APP_PARAM}">{vtranslate('LBL_ALL', $MODULE)|default:'Tất cả'}</a></li>
						<li role="separator" class="divider"></li>
						{foreach item=TAG_MODEL from=$TAGS}
							<li><a href="index.php?module=Documents&view=List&tag={$TAG_MODEL->getId()}&folder_id={$FOLDER_ID}&folder_value={$FOLDER_VALUE|escape:'url'}{$APP_PARAM}">{$TAG_MODEL->getName()|escape:'html'}</a></li>
						{/foreach}
					</ul>
				</div>
			{/if}
		</div>
		{include file="ListViewContents.tpl"|vtemplate_path:'Vtiger'}
	</div>
</div>
