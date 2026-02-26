{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Documents/views/AddFolder.php *}
{strip}
<div class="modal-dialog modelContainer doc-add-folder-modal">
	<div class="modal-content">
	{assign var=HEADER_TITLE value={vtranslate('LBL_ADD_NEW_FOLDER', $MODULE)}}
	{if $FOLDER_ID}
		{assign var=HEADER_TITLE value="{vtranslate('LBL_EDIT_FOLDER', $MODULE)}: {$FOLDER_NAME}"}
	{/if}
	{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
	<form class="form-horizontal" id="addDocumentsFolder" method="post" action="index.php">
		<input type="hidden" name="module" value="{$MODULE}" />
		<input type="hidden" name="action" value="Folder" />
		<input type="hidden" name="mode" value="save" />
		<input type="hidden" name="return_url" value="{$RETURN_URL|escape:'html'}" />
		{if $FOLDER_ID neq null}
			<input type="hidden" name="folderid" value="{$FOLDER_ID}" />
			<input type="hidden" name="savemode" value="{$SAVE_MODE}" />
		{/if}
		<div class="modal-body">
			<div class="container-fluid">
				<div class="form-group">
					<label class="control-label fieldLabel col-sm-3">
						<span class="redColor">*</span>
						{vtranslate('LBL_FOLDER_NAME', $MODULE)}
					</label>
					<div class="controls col-sm-9">
						<input class="inputElement" id="documentsFolderName" data-rule-required="true" name="foldername" type="text" value="{$FOLDER_NAME|default:''|escape:'html'}"/>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label fieldLabel col-sm-3">
						{vtranslate('LBL_FOLDER_DESCRIPTION', $MODULE)}
					</label>
					<div class="controls col-sm-9">
						<textarea rows="3" class="inputElement form-control" name="folderdesc" id="description" style="resize: vertical;">{$FOLDER_DESC|default:''|escape:'html'}</textarea>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label fieldLabel col-sm-3">
						{vtranslate('LBL_SHARING', $MODULE)|default:'Phân quyền xem'}
					</label>
					<div class="controls col-sm-9">
						<small class="text-muted">{vtranslate('LBL_FOLDER_SHARING_HINT', $MODULE)|default:'Chọn user/nhóm được phép xem folder. Để trống = tất cả.'}</small>
						<div class="row marginTop10px">
							<div class="col-sm-6">
								<label class="small">{vtranslate('LBL_USERS', $MODULE)|default:'User'}</label>
								<select name="shared_user_ids[]" multiple="multiple" class="select2 col-sm-12" style="min-height:80px;">
									{foreach key=uid item=uname from=$ACCESSIBLE_USERS}
									<option value="{$uid}" {if in_array($uid, $SHARED_USER_IDS)}selected{/if}>{$uname|escape:'html'}</option>
									{/foreach}
								</select>
							</div>
							<div class="col-sm-6">
								<label class="small">{vtranslate('LBL_GROUPS', $MODULE)|default:'Nhóm'}</label>
								<select name="shared_group_ids[]" multiple="multiple" class="select2 col-sm-12" style="min-height:80px;">
									{foreach key=gid item=gname from=$ACCESSIBLE_GROUPS}
									<option value="{$gid}" {if in_array($gid, $SHARED_GROUP_IDS)}selected{/if}>{$gname|escape:'html'}</option>
									{/foreach}
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer ">
			<center>
				<button class="btn btn-success" type="submit" name="saveButton"><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>
				{if $IS_FULL_PAGE}
					<a href="{$RETURN_URL|escape:'html'}" class="cancelLink">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				{else}
					<a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				{/if}
			</center>
		</div>
	</form>
	</div>
</div>
{/strip}

