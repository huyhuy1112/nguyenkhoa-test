{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{strip}
	{foreach key=index item=jsModel from=$SCRIPTS}
		<script type="{$jsModel->getType()}" src="{$jsModel->getSrc()}"></script>
	{/foreach}
	<div class="modal-dialog modal-md">
		<div class="modal-content">
			<form class="form-horizontal recordEditView" id="QuickCreate" name="QuickCreate" method="post" action="index.php">
				{if $MODE eq 'edit' && !empty($RECORD_ID)}
					{assign var=HEADER_TITLE value={vtranslate('LBL_EDITING', $MODULE)}|cat:" "|cat:{vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}}
				{else}
					{assign var=HEADER_TITLE value={vtranslate('LBL_QUICK_CREATE', $MODULE)}|cat:" "|cat:{vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}}
				{/if}
				{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}

				<div class="modal-body">
					{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
						<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
					{/if}
					<input type="hidden" name="module" value="{$MODULE}">
					<input type="hidden" name="action" value="SaveAjax">
					<input type="hidden" name="calendarModule" value="{$MODULE}">
					<input type="hidden" name="defaultCallDuration" value="{$USER_MODEL->get('callduration')}" />
					<input type="hidden" name="defaultOtherEventDuration" value="{$USER_MODEL->get('othereventduration')}" />
					{if $MODE eq 'edit' && !empty($RECORD_ID)}
						<input type="hidden" name="record" value="{$RECORD_ID}" />
						<input type="hidden" name="mode" value="{$MODE}" />
					{else}
						<input type="hidden" name="record" value="">
					{/if}

					{assign var="RECORD_STRUCTURE_MODEL" value=$QUICK_CREATE_CONTENTS[$MODULE]['recordStructureModel']}
					{assign var="RECORD_STRUCTURE" value=$QUICK_CREATE_CONTENTS[$MODULE]['recordStructure']}
					{assign var="BLOCK_FIELDS" value=$QUICK_CREATE_CONTENTS[$MODULE]['recordStructure']} {* Dependency in Time UiType template *}
					{assign var="MODULE_MODEL" value=$QUICK_CREATE_CONTENTS[$MODULE]['moduleModel']}

					<div class="quickCreateContent calendarQuickCreateContent" style="padding-top:2%;margin-top:5px;">
						{if $MODULE eq 'Calendar'}
							{if !empty($PICKIST_DEPENDENCY_DATASOURCE_TODO)}
								<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE_TODO)}' />
							{/if}
						{else}
							{if !empty($PICKIST_DEPENDENCY_DATASOURCE_EVENT)}
								<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE_EVENT)}' />
							{/if}
						{/if}

						{* Subject / Title: "Add title" cho Task, label chuẩn cho Event *}
						<div class="{if $MODULE eq 'Calendar'}google-task-title-wrap{/if}">
							{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['subject']}
							<div style="margin-left: 14px;width: 95%;">
								{assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
								{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
								<input id="{$MODULE}_editView_fieldName_{$FIELD_MODEL->get('name')}" type="text" class="inputElement {if $FIELD_MODEL->isNameField()}nameField{/if}" name="{$FIELD_MODEL->getFieldName()}" value="{$FIELD_MODEL->get('fieldvalue')}"
									   {if $FIELD_MODEL->get('uitype') eq '3' || $FIELD_MODEL->get('uitype') eq '4'|| $FIELD_MODEL->isReadOnly()} readonly {/if} {if !empty($SPECIAL_VALIDATOR)}data-validator="{Zend_Json::encode($SPECIAL_VALIDATOR)}"{/if}  
									   {if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
									   {foreach item=VALIDATOR from=$FIELD_INFO["validator"]}
										   {assign var=VALIDATOR_NAME value=$VALIDATOR["name"]}
										   data-rule-{$VALIDATOR_NAME} = "true" 
									   {/foreach}
									   placeholder="{if $MODULE eq 'Calendar'}{vtranslate('LBL_ADD_TITLE','Calendar')}{else}{vtranslate($FIELD_MODEL->get('label'), $MODULE)} *{/if}" style="width: 100%;"/>
							</div>
						</div>

						{* ----- TASK (Calendar): Form giống hình - thời gian 1 dòng "Thursday, Jan 1 8:45am - 4:45pm", deadline optional ----- *}
						{if $MODULE eq 'Calendar'}
						<div class="google-task-form calendar-task-qc" style="margin-top: 16px;">
							{* Dòng thời gian: icon đồng hồ + text "Thursday, January 1 8:45am - 4:45pm" (JS cập nhật) *}
							<div class="google-task-row calendar-qc-datetime-row" style="display: flex; align-items: flex-start; margin-bottom: 10px;">
								<span class="fa fa-clock-o" style="width: 24px; margin-right: 12px; color: #5f6368; margin-top: 6px;"></span>
								<div style="flex: 1;">
									<div class="calendar-qc-datetime-summary" style="font-size: 14px; color: #202124; margin-bottom: 8px; min-height: 22px;">—</div>
									<div class="calendar-qc-datetime-inputs" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
										{* date_start dùng DateTime.tpl → đã có cả ô ngày + ô giờ (time_start) bên trong, không include time_start lần nữa *}
										{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['date_start']}
										{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
									</div>
								</div>
							</div>
							{* All day: khi tick thì trên full calendar sẽ vẽ trên hàng All day từ ngày bắt đầu đến hết ngày deadline (điền deadline để span nhiều ngày) *}
							<div class="google-task-row" style="margin-bottom: 12px; padding-left: 36px;">
								<label class="checkbox-inline">
									<input type="checkbox" name="allday" value="1" /> {vtranslate('LBL_ALL_DAY','Calendar')}
								</label>
								<span class="muted small" style="margin-left: 8px;">({vtranslate('LBL_ALL_DAY_HINT','Calendar')})</span>
							</div>
							{* Repeat: giống Event - chữ Repeat ở trên, ô chọn bên dưới *}
							<div class="calendar-repeat-section" style="margin-top: 12px; margin-bottom: 12px; padding: 10px 15px; padding-left: 36px; background: #f9f9f9; border-radius: 4px;">
								<div class="form-group" style="margin-bottom: 0;">
									<label class="control-label" style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">Repeat</label>
									<select name="calendar_repeat_type" id="calendar_repeat_type" class="form-control" style="font-size: 13px; max-width: 280px;">
										<option value="">{vtranslate('LBL_DOES_NOT_REPEAT','Calendar')}</option>
										<option value="Daily">Daily</option>
										<option value="Weekly">Weekly</option>
										<option value="Monthly">Monthly</option>
										<option value="Yearly">Yearly</option>
									</select>
									<input type="hidden" name="recurringtype" id="calendar_recurringtype_hidden" value="" />
								</div>
							</div>
							{* Deadline: optional, để trống, placeholder "Add deadline" *}
							<div class="google-task-row" style="display: flex; align-items: center; margin-bottom: 12px;">
								<span class="fa fa-bullseye" style="width: 24px; margin-right: 12px; color: #5f6368;"></span>
								<div style="flex: 1; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
									<input type="text" class="inputElement dateField" name="due_date" value="" placeholder="{vtranslate('LBL_ADD_DEADLINE','Calendar')}" data-date-format="{$USER_MODEL->get('date_format')}" data-rule-required="false" style="max-width: 220px;" />
									<input type="text" name="time_end" class="timepicker-default form-control input-sm" data-format="24" placeholder="HH:mm" style="width: 80px;" />
								</div>
							</div>
							{* Add description *}
							<div class="google-task-row" style="display: flex; align-items: flex-start; margin-bottom: 12px;">
								<span class="fa fa-align-left" style="width: 24px; margin-right: 12px; color: #5f6368; margin-top: 8px;"></span>
								<div style="flex: 1;">
									<textarea name="description" class="form-control" rows="3" placeholder="{vtranslate('LBL_ADD_DESCRIPTION','Calendar')}" style="resize: vertical; font-size: 13px;"></textarea>
								</div>
							</div>
						</div>
						{* Assigned To + Status: hàng kiểu Google (List / Calendar owner) *}
						<div class="google-task-meta" style="margin-top: 8px; padding-left: 36px; font-size: 13px; color: #5f6368;">
							{* Các field Assigned To, Status vẫn nằm trong table bên dưới *}
						</div>
						{* ----- EVENT (Events): ngày + giờ, All Day, Duration ----- *}
						{else}
						<div class="row" style="padding-top: 2%;">
							<div class="col-sm-12">
								<div class="col-sm-5 calendar-date-time-wrapper">
									{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['date_start']}
									{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
									{if isset($RECORD_STRUCTURE['time_start'])}
									<div style="margin-top: 8px;">
										{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['time_start']}
										{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
									</div>
									{/if}
								</div>
								<div class="muted col-sm-1" style="line-height: 67px; left: 20px; padding-right: 7%; text-align: center;">
									{vtranslate('LBL_TO',$MODULE)}
								</div>
								<div class="col-sm-5 calendar-date-time-wrapper">
									{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['due_date']}
									{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
									{if isset($RECORD_STRUCTURE['time_end'])}
									<div style="margin-top: 8px;">
										{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['time_end']}
										{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
									</div>
									{/if}
								</div>
							</div>
							<div class="col-sm-12" style="margin-top: 10px; padding-left: 14px;">
								<label class="checkbox-inline">
									<input type="checkbox" name="allday" id="calendar_allday" value="1" />
									<strong>{vtranslate('LBL_ALL_DAY','Calendar')}</strong>
								</label>
								<span id="calendar-duration-display" style="margin-left: 15px; color: #666; font-size: 12px;"></span>
							</div>
						</div>
						{/if}
						<div class="container-fluid paddingTop15">
							<table class="massEditTable table no-border">
								<tr>
									{foreach key=FIELD_NAME item=FIELD_MODEL from=$RECORD_STRUCTURE name=blockfields}
									{if $FIELD_NAME eq 'subject' || $FIELD_NAME eq 'date_start' || $FIELD_NAME eq 'due_date' || $FIELD_NAME eq 'time_start' || ($MODULE eq 'Events' && $FIELD_NAME eq 'time_end') || ($MODULE eq 'Calendar' && $FIELD_NAME eq 'description')}
								</tr>{continue}
								{/if}
								{assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
								{assign var="referenceList" value=$FIELD_MODEL->getReferenceList()}
								{assign var="referenceListCount" value=php7_count($referenceList)}
								{if $FIELD_MODEL->get('uitype') eq "19"}
								{if $COUNTER eq '1'}
								<td></td><td></td></tr><tr>
									{assign var=COUNTER value=0}
									{/if}
									{/if}
								</tr><tr>
									<td class='fieldLabel col-lg-3'>
										{if $isReferenceField neq "reference"}<label class="muted">{/if}
											{if $isReferenceField eq "reference"}
												{if $referenceListCount > 1}
													{assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
													{assign var="REFERENCED_MODULE_STRUCT" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
													{if !empty($REFERENCED_MODULE_STRUCT)}
														{assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCT->get('name')}
													{/if}
													<span class="">
														<select style="width: 150px;" class="select2 referenceModulesList">
															{foreach key=index item=value from=$referenceList}
																<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME} selected {/if} >{vtranslate($value, $value)}</option>
															{/foreach}
														</select>
													</span>
												{else}
													<label class="muted">{vtranslate($FIELD_MODEL->get('label'), $MODULE)} &nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}</label>
												{/if}
											{else}
												{vtranslate($FIELD_MODEL->get('label'), $MODULE)}&nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
											{/if}
											{if $isReferenceField neq "reference"}</label>{/if}
									</td>
									<td class="fieldValue col-lg-9" {if $FIELD_MODEL->get('uitype') eq '19'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
										{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
									</td>
									{/foreach}
								</tr>
							</table>
						</div>
						
						{* Repeat + Optional Details: chỉ cho EVENT, không cho Task *}
						{if $MODULE eq 'Events'}
						<div class="calendar-repeat-section" style="margin-top: 15px; padding: 10px 15px; background: #f9f9f9; border-radius: 4px;">
							<div class="form-group" style="margin-bottom: 0;">
								<label class="control-label" style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">Repeat</label>
								<select name="calendar_repeat_type" id="calendar_repeat_type" class="form-control" style="font-size: 13px;">
									<option value="">Does not repeat</option>
									<option value="Daily">Daily</option>
									<option value="Weekly">Weekly</option>
									<option value="Monthly">Monthly</option>
									<option value="Yearly">Yearly</option>
								</select>
								<input type="hidden" name="recurringtype" id="calendar_recurringtype_hidden" value="" />
							</div>
						</div>
						<div class="calendar-optional-fields" style="margin-top: 15px; padding: 10px 15px; background: #f9f9f9; border-radius: 4px;">
							<h5 style="margin-top: 0; margin-bottom: 12px; font-size: 13px; color: #666; font-weight: bold;">Optional Details</h5>
							<div class="form-group" style="margin-bottom: 10px;">
								<label class="control-label" style="font-size: 12px;">Location</label>
								<input type="text" name="location" class="form-control" placeholder="Add location" style="font-size: 13px;" />
							</div>
							<div class="form-group" style="margin-bottom: 10px;">
								<label class="control-label" style="font-size: 12px;">Meeting Link</label>
								<input type="url" name="meeting_link" class="form-control" placeholder="https://meet.google.com/..." style="font-size: 13px;" />
							</div>
							<div class="form-group" style="margin-bottom: 0;">
								<label class="control-label" style="font-size: 12px;">Description</label>
								<textarea name="description" class="form-control" rows="3" placeholder="Add description" style="font-size: 13px; resize: vertical;"></textarea>
							</div>
						</div>
						{/if}
					</div>
				</div>
				<div class="modal-footer">
					<center>
						{if $BUTTON_NAME neq null}
							{assign var=BUTTON_LABEL value=$BUTTON_NAME}
						{else}
							{assign var=BUTTON_LABEL value={vtranslate('LBL_SAVE', $MODULE)}}
						{/if}
						{assign var="CALENDAR_MODULE_MODEL" value=$QUICK_CREATE_CONTENTS['Calendar']['moduleModel']}
						{assign var="EDIT_VIEW_URL" value=$CALENDAR_MODULE_MODEL->getCreateTaskRecordUrl()}
						{if $MODULE eq 'Events'}
							{assign var="EDIT_VIEW_URL" value=$CALENDAR_MODULE_MODEL->getCreateEventRecordUrl()}
						{/if}
						<button class="btn btn-default" id="goToFullForm" data-edit-view-url="{$EDIT_VIEW_URL}" type="button"><strong>{vtranslate('LBL_GO_TO_FULL_FORM', $MODULE)}</strong></button>
						<button {if $BUTTON_ID neq null} id="{$BUTTON_ID}" {/if} class="btn btn-success" type="submit" name="saveButton"><strong>{$BUTTON_LABEL}</strong></button>
						<a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
					</center>
				</div>
			</form>
		</div>
		{if $FIELDS_INFO neq null}
			<script type="text/javascript">
				var quickcreate_uimeta = (function () {
					var fieldInfo = {$FIELDS_INFO};
					return {
						field: {
							get: function (name, property) {
								if (name && property === undefined) {
									return fieldInfo[name];
								}
								if (name && property) {
									return fieldInfo[name][property]
								}
							},
							isMandatory: function (name) {
								if (fieldInfo[name]) {
									return fieldInfo[name].mandatory;
								}
								return false;
							},
							getType: function (name) {
								if (fieldInfo[name]) {
									return fieldInfo[name].type;
								}
								return false;
							}
						},
					};
				})();
			</script>
		{/if}
	</div>
{/strip}
