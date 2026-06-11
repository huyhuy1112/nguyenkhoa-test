{*+**********************************************************************************
* Users detail blocks — modern field grid (replaces legacy 4-column table layout)
*************************************************************************************}

{strip}
	<input type=hidden name="timeFormatOptions" data-value='{$DAY_STARTS}' />
	<input type='hidden' name='pwd_regex' value={ZEND_json::encode($PWD_REGEX)} />
	{foreach key=BLOCK_LABEL_KEY item=FIELD_MODEL_LIST from=$RECORD_STRUCTURE name=DetailViewBlockViewLoop}
		{if $BLOCK_LABEL_KEY neq 'LBL_CALENDAR_SETTINGS'}
			{assign var=BLOCK value=$BLOCK_LIST[$BLOCK_LABEL_KEY]}
			{if $BLOCK eq null or $FIELD_MODEL_LIST|@count lte 0}{continue}{/if}
			<div class="block block_{$BLOCK_LABEL_KEY}" data-block="{$BLOCK_LABEL_KEY}" data-blockid="{$BLOCK_LIST[$BLOCK_LABEL_KEY]->get('id')}">
				{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
				<div>
					<h4>{vtranslate({$BLOCK_LABEL_KEY},{$MODULE_NAME})}</h4>
				</div>
				<hr>
				<div class="blockData">
					<div class="mk-users-fields-grid">
						{foreach item=FIELD_MODEL key=FIELD_NAME from=$FIELD_MODEL_LIST}
							{assign var=fieldDataType value=$FIELD_MODEL->getFieldDataType()}
							{if !$FIELD_MODEL->isViewableInDetailView()}
								{continue}
							{/if}
							{if $FIELD_MODEL->getName() eq 'theme' or $FIELD_MODEL->getName() eq 'rowheight'}
								{continue}
							{/if}

							{if $FIELD_MODEL->get('uitype') eq "83"}
								{foreach item=tax key=count from=$TAXCLASS_DETAILS}
									<div class="mk-users-field {$WIDTHTYPE}">
										<div class="mk-users-field__label fieldLabel">
											<span class="muted">{vtranslate($tax.taxlabel, $MODULE)}(%)</span>
										</div>
										<div class="mk-users-field__value fieldValue">
											<span class="value" data-field-type="{$FIELD_MODEL->getFieldDataType()}">
												{if $tax.check_value eq 1}{$tax.percentage}{else}0{/if}
											</span>
										</div>
									</div>
								{/foreach}

							{elseif $FIELD_MODEL->get('uitype') eq "69" || $FIELD_MODEL->get('uitype') eq "105"}
								{assign var=MK_USER_HAS_PHOTO value=false}
								{assign var=MK_USER_PHOTO_URL value=''}
								{assign var=MK_USER_PHOTO_ALT value=$RECORD->getName()|escape}
								{if isset($IMAGE_DETAILS) && $IMAGE_DETAILS|@count gt 0}
									{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
										{if !empty($IMAGE_INFO.url)}
											{assign var=MK_USER_HAS_PHOTO value=true}
											{assign var=MK_USER_PHOTO_URL value=$IMAGE_INFO.url}
											{if !empty($IMAGE_INFO.orgname)}{assign var=MK_USER_PHOTO_ALT value=$IMAGE_INFO.orgname|escape}{/if}
										{/if}
									{/foreach}
								{/if}
								{if !$MK_USER_HAS_PHOTO}
									{foreach key=ITER item=IMAGE_INFO from=$RECORD->getImageDetails()}
										{if !empty($IMAGE_INFO.url)}
											{assign var=MK_USER_HAS_PHOTO value=true}
											{assign var=MK_USER_PHOTO_URL value=$IMAGE_INFO.url}
											{if !empty($IMAGE_INFO.orgname)}{assign var=MK_USER_PHOTO_ALT value=$IMAGE_INFO.orgname|escape}{/if}
										{/if}
									{/foreach}
								{/if}
								{if (isset($VIEW) && $VIEW eq 'PreferenceDetail') || (isset($smarty.request.view) && $smarty.request.view eq 'PreferenceDetail')}
									{assign var=MK_PHOTO_EDIT_URL value=$RECORD->getPreferenceEditViewUrl()}
								{else}
									{assign var=MK_PHOTO_EDIT_URL value=$RECORD->getEditViewUrl()}
								{/if}
								<div class="mk-users-field mk-users-field--image mk-users-field--full {$WIDTHTYPE}" id="{$MODULE_NAME}_detailView_fieldValue_{$FIELD_MODEL->getName()}">
									<div class="mk-users-photo-card fieldValue">
										{if $MK_USER_HAS_PHOTO}
											<div class="mk-users-photo mk-users-photo--has-image">
												<img class="mk-users-photo__img" src="{$MK_USER_PHOTO_URL}" alt="{$MK_USER_PHOTO_ALT|escape:'html'}" loading="lazy">
											</div>
										{else}
											<div class="mk-users-photo mk-users-photo--empty">
												<div class="mk-users-photo__placeholder" aria-hidden="true">
													<span class="mk-users-photo__initials">{$RECORD->getName()|substr:0:2}</span>
												</div>
												<p class="mk-users-photo__empty-title">Chưa có ảnh đại diện</p>
												<p class="mk-users-photo__empty-hint">Tải ảnh lên trong màn hình chỉnh sửa hồ sơ.</p>
											</div>
										{/if}
										<div class="mk-users-photo__actions">
											<a class="btn btn-default mk-users-photo__edit-btn" href="{$MK_PHOTO_EDIT_URL|escape:'html'}">
												<i class="fa fa-pencil" aria-hidden="true"></i>
												{if $MK_USER_HAS_PHOTO}{vtranslate('LBL_EDIT', $MODULE_NAME)}{else}{vtranslate('LBL_UPLOAD', 'Vtiger')}{/if}
											</a>
										</div>
									</div>
								</div>

							{else}
								{assign var=MK_FIELD_FULL value=false}
								{if $FIELD_MODEL->get('uitype') eq "19" or $FIELD_MODEL->get('uitype') eq "20" or $fieldDataType eq 'reminder' or $fieldDataType eq 'recurrence'}
									{assign var=MK_FIELD_FULL value=true}
								{/if}
								{assign var=FIELD_VALUE value=$FIELD_MODEL->get('fieldvalue')}
								{if $fieldDataType eq 'multipicklist'}
									{assign var=FIELD_DISPLAY_VALUE value=$FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue'))}
								{else}
									{assign var=FIELD_DISPLAY_VALUE value=Vtiger_Util_Helper::toSafeHTML($FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue')))}
								{/if}

								<div class="mk-users-field{if $MK_FIELD_FULL} mk-users-field--full{/if} {$WIDTHTYPE}">
									<div class="mk-users-field__label fieldLabel {$WIDTHTYPE}" id="{$MODULE_NAME}_detailView_fieldLabel_{$FIELD_MODEL->getName()}">
										<span class="muted">
											{if $MODULE_NAME eq 'Documents' && $FIELD_MODEL->get('label') eq "File Name" && $RECORD->get('filelocationtype') eq 'E'}
												{vtranslate("LBL_FILE_URL",{$MODULE_NAME})}
											{else}
												{vtranslate({$FIELD_MODEL->get('label')},{$MODULE_NAME})}
											{/if}
											{if ($FIELD_MODEL->get('uitype') eq '72') && ($FIELD_MODEL->getName() eq 'unit_price')}
												({$BASE_CURRENCY_SYMBOL})
											{/if}
										</span>
									</div>
									<div class="mk-users-field__value fieldValue {$WIDTHTYPE}" id="{$MODULE_NAME}_detailView_fieldValue_{$FIELD_MODEL->getName()}">
										<span class="value" data-field-type="{$FIELD_MODEL->getFieldDataType()}" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '20' or $FIELD_MODEL->get('uitype') eq '21'} style="white-space:normal;" {/if} {if $fieldDataType eq 'email'}title='{$FIELD_MODEL->get('fieldvalue')}'{/if}>
											{if $FIELD_MODEL->getName() neq 'defaultlandingpage'}
												{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getDetailViewTemplateName(),$MODULE_NAME) FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME RECORD=$RECORD}
											{else}
												{vtranslate($FIELD_MODEL->get('fieldvalue'),$FIELD_MODEL->get('fieldvalue'))}
											{/if}
										</span>
										{if $IS_AJAX_ENABLED && $FIELD_MODEL->isEditable() eq 'true' && $FIELD_MODEL->isAjaxEditable() eq 'true'}
											<span class="hide edit">
												{if $fieldDataType eq 'multipicklist'}
													<input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}[]' data-type="{$fieldDataType}" data-displayvalue='{$FIELD_DISPLAY_VALUE}' data-value="{$FIELD_VALUE}" />
												{else}
													<input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}' data-type="{$fieldDataType}" data-displayvalue='{$FIELD_DISPLAY_VALUE}' data-value="{$FIELD_VALUE}" />
												{/if}
											</span>
											<span class="action"><a href="#" onclick="return false;" class="editAction fa fa-pencil"></a></span>
										{/if}
									</div>
								</div>
							{/if}
						{/foreach}
					</div>
				</div>
			</div>
		{/if}
	{/foreach}

	{if $MODULE_NAME eq 'Users' && isset($DATE_JOINED_COMPANY)}
		<div class="block block_custom" data-block="LBL_DATE_JOINED_COMPANY">
			<div>
				<h4>{vtranslate('LBL_DATE_JOINED_COMPANY','Teams')}</h4>
			</div>
			<hr>
			<div class="blockData">
				<div class="mk-users-fields-grid">
					<div class="mk-users-field">
						<div class="mk-users-field__label fieldLabel">
							<span class="muted">{vtranslate('LBL_DATE_JOINED_COMPANY','Teams')}</span>
						</div>
						<div class="mk-users-field__value fieldValue">
							<span class="value">
								{if $DATE_JOINED_COMPANY}
									{$DATE_JOINED_COMPANY|decode_html}
								{else}
									<span class="muted">—</span>
								{/if}
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/if}
{/strip}
