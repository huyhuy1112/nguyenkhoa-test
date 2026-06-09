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
	{if $MODULE eq 'Calendar' || $MODULE eq 'Events'}
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Calendar/resources/CalendarQuickCreateTask.css')}&mk_v=20260605_event_ui_v2" />
	<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesQuickCreate.css')}&mk_v=20260605_mk_qc_v2" />
	<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkSalesQuickCreate.js')}&mk_v=20260605_mk_qc_v2"></script>
	{/if}
	<div class="modal-dialog modal-md{if $MODULE eq 'Calendar'} mk-qc-task-modal{elseif $MODULE eq 'Events'} mk-qc-event-modal{/if}">
		<div class="modal-content">
			<form class="form-horizontal recordEditView" id="QuickCreate" name="QuickCreate" method="post" action="index.php">
				{if $MODE eq 'edit' && !empty($RECORD_ID)}
					{assign var=HEADER_TITLE value={vtranslate('LBL_EDITING', $MODULE)}|cat:" "|cat:{vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}}
				{else}
					{assign var=HEADER_TITLE value={vtranslate('LBL_QUICK_CREATE', $MODULE)}|cat:" "|cat:{vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}}
				{/if}
				{if $MODULE eq 'Events'}
					{include file="partials/MkSalesQuickCreateModalHeader.tpl"|vtemplate_path:'Vtiger' TITLE=$HEADER_TITLE}
				{else}
					{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
				{/if}

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

					<div class="quickCreateContent calendarQuickCreateContent{if $MODULE eq 'Calendar'} mk-qc-task-content{elseif $MODULE eq 'Events'} mk-qc-event-content{/if}">
						{if $MODULE eq 'Calendar'}
							{if !empty($PICKIST_DEPENDENCY_DATASOURCE_TODO)}
								<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE_TODO)}' />
							{/if}
						{else}
							{if !empty($PICKIST_DEPENDENCY_DATASOURCE_EVENT)}
								<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE_EVENT)}' />
							{/if}
						{/if}

						{if $MODULE eq 'Calendar'}
						<input type="hidden" name="activitytype" value="Task" />
						{elseif $MODULE eq 'Events'}
						<input type="hidden" name="activitytype" value="Meeting" />
						{/if}

						{* Subject / Title *}
						<div class="{if $MODULE eq 'Calendar' || $MODULE eq 'Events'}mk-qc-task__title google-task-title-wrap{else}google-task-title-wrap{/if}">
							{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['subject']}
							{assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
							{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
							<input id="{$MODULE}_editView_fieldName_{$FIELD_MODEL->get('name')}" type="text" class="inputElement {if $FIELD_MODEL->isNameField()}nameField{/if}" name="{$FIELD_MODEL->getFieldName()}" value="{$FIELD_MODEL->get('fieldvalue')}"
								   {if $FIELD_MODEL->get('uitype') eq '3' || $FIELD_MODEL->get('uitype') eq '4'|| $FIELD_MODEL->isReadOnly()} readonly {/if} {if !empty($SPECIAL_VALIDATOR)}data-validator="{Zend_Json::encode($SPECIAL_VALIDATOR)}"{/if}
								   {if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
								   {foreach item=VALIDATOR from=$FIELD_INFO["validator"]}
									   {assign var=VALIDATOR_NAME value=$VALIDATOR["name"]}
									   data-rule-{$VALIDATOR_NAME} = "true"
								   {/foreach}
								   placeholder="{if $MODULE eq 'Calendar'}{vtranslate('LBL_ADD_TITLE','Calendar')}{elseif $MODULE eq 'Events'}{vtranslate('LBL_ADD_TITLE','Calendar')}{else}{vtranslate($FIELD_MODEL->get('label'), $MODULE)} *{/if}" />
						</div>

						{* ----- TASK (Calendar): compact quick-create ----- *}
						{if $MODULE eq 'Calendar'}
						<div class="mk-qc-task google-task-form calendar-task-qc">
							<div class="mk-qc-task__row mk-qc-task__when calendar-qc-datetime-row">
								<span class="mk-qc-task__icon fa fa-clock-o" aria-hidden="true"></span>
								<div class="mk-qc-task__body">
									<div class="mk-qc-task__summary calendar-qc-datetime-summary">—</div>
									<div class="mk-qc-task__inputs calendar-qc-datetime-inputs">
										{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['date_start']}
										{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
									</div>
								</div>
							</div>

							<div class="mk-qc-task__allday">
								<label>
									<input type="checkbox" name="allday" value="1" />
									{vtranslate('LBL_ALL_DAY', $MODULE)}
								</label>
								<span class="mk-qc-task__allday-hint">{vtranslate('LBL_ALL_DAY_HINT', $MODULE)}</span>
							</div>

							<div class="mk-qc-task__repeat calendar-repeat-section">
								<span class="mk-qc-task__repeat-label">{vtranslate('LBL_REPEAT', $MODULE)}</span>
								<select name="calendar_repeat_type" id="calendar_repeat_type" class="form-control inputElement">
									<option value="">{vtranslate('LBL_DOES_NOT_REPEAT', $MODULE)}</option>
									<option value="Daily">{vtranslate('LBL_DAILY', $MODULE)}</option>
									<option value="Weekly">{vtranslate('LBL_WEEKLY', $MODULE)}</option>
									<option value="Monthly">{vtranslate('LBL_MONTHLY', $MODULE)}</option>
									<option value="Yearly">{vtranslate('LBL_YEARLY', $MODULE)}</option>
								</select>
								<input type="hidden" name="recurringtype" id="calendar_recurringtype_hidden" value="" />
							</div>

							<div class="mk-qc-task__row mk-qc-task__deadline">
								<span class="mk-qc-task__icon fa fa-flag-o" aria-hidden="true"></span>
								<div class="mk-qc-task__body mk-qc-task__inputs">
									<input type="text" class="inputElement dateField" name="due_date" value="" placeholder="{vtranslate('LBL_ADD_DEADLINE', $MODULE)}" data-date-format="{$USER_MODEL->get('date_format')}" data-rule-required="false" />
									<input type="text" name="time_end" class="timepicker-default form-control input-sm" data-format="24" placeholder="HH:mm" />
								</div>
							</div>

							<div class="mk-qc-task__row mk-qc-task__desc">
								<span class="mk-qc-task__icon fa fa-align-left" aria-hidden="true"></span>
								<div class="mk-qc-task__body">
									<textarea name="description" class="form-control" rows="3" placeholder="{vtranslate('LBL_ADD_DESCRIPTION', $MODULE)}"></textarea>
								</div>
							</div>

							<div class="mk-qc-task__meta google-task-meta">
								{if isset($RECORD_STRUCTURE['assigned_user_id'])}
									{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['assigned_user_id']}
									<div class="mk-qc-task__field mk-qc-task__field--owner">
										<label>{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{if $FIELD_MODEL->isMandatory()} <span class="redColor">*</span>{/if}</label>
										{if empty($RECORD_ID) && $MODE neq 'edit'}
											<input type="hidden" name="assigned_user_id" value="{$USER_MODEL->get('id')}" />
											<div class="mk-qc-owner-locked" aria-readonly="true">{$USER_MODEL->getDisplayName()}</div>
										{else}
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
										{/if}
									</div>
								{/if}
								{if isset($RECORD_STRUCTURE['taskstatus'])}
									{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['taskstatus']}
									<div class="mk-qc-task__field">
										<label>{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{if $FIELD_MODEL->isMandatory()} <span class="redColor">*</span>{/if}</label>
										{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
									</div>
								{/if}
							</div>
						</div>
						{* ----- EVENT (Events): compact quick-create (Log Meeting) ----- *}
						{elseif $MODULE eq 'Events'}
						<div class="mk-qc-task mk-qc-event google-task-form calendar-event-qc">
							<div class="mk-qc-task__row mk-qc-task__when calendar-qc-datetime-row">
								<span class="mk-qc-task__icon fa fa-clock-o" aria-hidden="true"></span>
								<div class="mk-qc-task__body">
									<div class="mk-qc-event__range calendar-qc-datetime-inputs">
										{if isset($RECORD_STRUCTURE['date_start'])}
										<div class="mk-qc-event__col calendar-date-time-wrapper">
											<span class="mk-qc-event__lbl">{vtranslate('LBL_START_DATE','Calendar')}</span>
											{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['date_start']}
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
											{if isset($RECORD_STRUCTURE['time_start'])}
											<div class="mk-qc-event__time">
												{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['time_start']}
												{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
											</div>
											{/if}
										</div>
										{/if}
										<span class="mk-qc-event__to muted">{vtranslate('LBL_TO','Calendar')}</span>
										{if isset($RECORD_STRUCTURE['due_date'])}
										<div class="mk-qc-event__col calendar-date-time-wrapper">
											<span class="mk-qc-event__lbl">{vtranslate('LBL_END_DATE','Calendar')}</span>
											{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['due_date']}
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
											{if isset($RECORD_STRUCTURE['time_end'])}
											<div class="mk-qc-event__time">
												{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['time_end']}
												{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
											</div>
											{/if}
										</div>
										{/if}
									</div>
									<div id="calendar-duration-display" class="mk-qc-event__duration"></div>
								</div>
							</div>

							<div class="mk-qc-task__allday">
								<label>
									<input type="checkbox" name="allday" id="calendar_allday" value="1" />
									{vtranslate('LBL_ALL_DAY', $MODULE)}
								</label>
							</div>

							<div class="mk-qc-task__repeat calendar-repeat-section">
								<span class="mk-qc-task__repeat-label">{vtranslate('LBL_REPEAT', 'Calendar')}</span>
								<select name="calendar_repeat_type" id="calendar_repeat_type" class="form-control inputElement">
									<option value="">{vtranslate('LBL_DOES_NOT_REPEAT', 'Calendar')}</option>
									<option value="Daily">{vtranslate('LBL_DAILY', 'Calendar')}</option>
									<option value="Weekly">{vtranslate('LBL_WEEKLY', 'Calendar')}</option>
									<option value="Monthly">{vtranslate('LBL_MONTHLY', 'Calendar')}</option>
									<option value="Yearly">{vtranslate('LBL_YEARLY', 'Calendar')}</option>
								</select>
								<input type="hidden" name="recurringtype" id="calendar_recurringtype_hidden" value="" />
							</div>

							<div class="mk-qc-task__row mk-qc-task__desc">
								<span class="mk-qc-task__icon fa fa-map-marker" aria-hidden="true"></span>
								<div class="mk-qc-task__body">
									<input type="text" name="location" class="form-control inputElement" placeholder="{vtranslate('LBL_LOCATION','Events')}" />
								</div>
							</div>

							<div class="mk-qc-task__row mk-qc-task__desc">
								<span class="mk-qc-task__icon fa fa-align-left" aria-hidden="true"></span>
								<div class="mk-qc-task__body">
									<textarea name="description" class="form-control" rows="3" placeholder="{vtranslate('LBL_ADD_DESCRIPTION', 'Calendar')}"></textarea>
								</div>
							</div>

							<div class="mk-qc-task__meta google-task-meta">
								{if isset($RECORD_STRUCTURE['assigned_user_id'])}
									{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['assigned_user_id']}
									<div class="mk-qc-task__field mk-qc-task__field--owner">
										<label>{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{if $FIELD_MODEL->isMandatory()} <span class="redColor">*</span>{/if}</label>
										{if empty($RECORD_ID) && $MODE neq 'edit'}
											<input type="hidden" name="assigned_user_id" value="{$USER_MODEL->get('id')}" />
											<div class="mk-qc-owner-locked" aria-readonly="true">{$USER_MODEL->getDisplayName()}</div>
										{else}
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
										{/if}
									</div>
								{/if}
								{if isset($RECORD_STRUCTURE['eventstatus'])}
									{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['eventstatus']}
									<div class="mk-qc-task__field">
										<label>{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{if $FIELD_MODEL->isMandatory()} <span class="redColor">*</span>{/if}</label>
										{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
									</div>
								{/if}
							</div>
						</div>
						{/if}
						{if $MODULE neq 'Events'}
						<div class="container-fluid paddingTop15{if $MODULE eq 'Calendar'} mk-qc-extra-fields{/if}">
							<table class="massEditTable table no-border">
								<tr>
									{foreach key=FIELD_NAME item=FIELD_MODEL from=$RECORD_STRUCTURE name=blockfields}
									{if $FIELD_NAME eq 'subject' || $FIELD_NAME eq 'date_start' || $FIELD_NAME eq 'due_date' || $FIELD_NAME eq 'time_start' || ($MODULE eq 'Events' && $FIELD_NAME eq 'time_end') || ($MODULE eq 'Events' && $FIELD_NAME eq 'description') || ($MODULE eq 'Events' && $FIELD_NAME eq 'assigned_user_id') || ($MODULE eq 'Events' && $FIELD_NAME eq 'eventstatus') || ($MODULE eq 'Events' && $FIELD_NAME eq 'activitytype') || ($MODULE eq 'Events' && $FIELD_NAME eq 'location') || ($MODULE eq 'Calendar' && $FIELD_NAME eq 'description') || ($MODULE eq 'Calendar' && $FIELD_NAME eq 'assigned_user_id') || ($MODULE eq 'Calendar' && $FIELD_NAME eq 'taskstatus') || ($MODULE eq 'Calendar' && $FIELD_NAME eq 'taskpriority') || ($MODULE eq 'Calendar' && $FIELD_NAME eq 'activitytype')}
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
						{/if}
					</div>
				</div>
				<div class="modal-footer{if $MODULE eq 'Calendar' || $MODULE eq 'Events'} mk-qc-task-footer{/if}">
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
					<button class="btn btn-default" id="goToFullForm" data-edit-view-url="{$EDIT_VIEW_URL}" type="button">{vtranslate('LBL_GO_TO_FULL_FORM', $MODULE)}</button>
					<div class="mk-qc-footer-actions">
						<a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
						<button {if $BUTTON_ID neq null} id="{$BUTTON_ID}" {/if} class="btn btn-success" type="submit" name="saveButton">{$BUTTON_LABEL}</button>
					</div>
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
