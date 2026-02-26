{*+**********************************************************************************
* Schedule Quick Create Task - Form giống hình: Add title, Date/Time, All day, Repeat, Deadline, Description, Assigned To, Status
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

					{assign var="RECORD_STRUCTURE_MODEL" value=$QUICK_CREATE_CONTENTS['Calendar']['recordStructureModel']}
					{assign var="RECORD_STRUCTURE" value=$QUICK_CREATE_CONTENTS['Calendar']['recordStructure']}
					{assign var="BLOCK_FIELDS" value=$QUICK_CREATE_CONTENTS['Calendar']['recordStructure']}
					{assign var="MODULE_MODEL" value=$QUICK_CREATE_CONTENTS['Calendar']['moduleModel']}

					<div class="quickCreateContent scheduleQuickCreateContent" style="padding-top:2%;margin-top:5px;">
						{if !empty($PICKIST_DEPENDENCY_DATASOURCE_TODO)}
							<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE_TODO)}' />
						{/if}

						{* 1. Add title *}
						<div>
							{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['subject']}
							<div style="margin-left: 14px;width: 95%;">
								{assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
								{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
								<input id="{$MODULE}_editView_fieldName_{$FIELD_MODEL->get('name')}" type="text" class="inputElement nameField" name="{$FIELD_MODEL->getFieldName()}" value="{$FIELD_MODEL->get('fieldvalue')}"
									   {if !empty($SPECIAL_VALIDATOR)}data-validator="{Zend_Json::encode($SPECIAL_VALIDATOR)}"{/if}
									   {if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
									   {foreach item=VALIDATOR from=$FIELD_INFO["validator"]}
										   {assign var=VALIDATOR_NAME value=$VALIDATOR["name"]}
										   data-rule-{$VALIDATOR_NAME} = "true"
									   {/foreach}
									   placeholder="{vtranslate('LBL_ADD_TITLE','Calendar')}" style="width: 100%; font-size: 14px;"/>
							</div>
						</div>

						{* 2. Date and Time - icon + summary line + date + time *}
						<div class="schedule-qc-datetime-row" style="margin-top: 16px; display: flex; align-items: flex-start;">
							<span class="fa fa-clock-o" style="width: 24px; margin-right: 12px; color: #5f6368; margin-top: 6px;"></span>
							<div style="flex: 1;">
								<div class="calendar-qc-datetime-summary" style="font-size: 14px; color: #202124; margin-bottom: 8px; min-height: 22px;">—</div>
								<div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
									{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['date_start']}
									{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),'Calendar')}
								</div>
							</div>
						</div>

						{* 3. All Day *}
						<div style="margin-top: 10px; padding-left: 36px;">
							<label class="checkbox-inline">
								<input type="checkbox" name="allday" id="schedule_allday" value="1" data-schedule-allday="1" />
								<strong>{vtranslate('LBL_ALL_DAY','Calendar')}</strong>
							</label>
							<span id="schedule-duration-display" style="margin-left: 15px; color: #666; font-size: 12px;">({vtranslate('LBL_ALL_DAY_HINT','Calendar')})</span>
						</div>

						{* 4. Repeat *}
						<div class="schedule-qc-repeat" style="margin-top: 12px; margin-bottom: 12px; padding: 10px 15px; padding-left: 36px; background: #f9f9f9; border-radius: 4px;">
							<label class="control-label" style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">{vtranslate('Recurrence','Calendar')}</label>
							<select name="calendar_repeat_type" id="schedule_repeat_type" class="form-control" style="font-size: 13px; max-width: 280px;">
								<option value="">{vtranslate('LBL_DOES_NOT_REPEAT','Calendar')}</option>
								<option value="Daily">Daily</option>
								<option value="Weekly">Weekly</option>
								<option value="Monthly">Monthly</option>
								<option value="Yearly">Yearly</option>
							</select>
							<input type="hidden" name="recurringtype" id="schedule_recurringtype_hidden" value="" />
						</div>

						{* 5. Deadline - Add deadline + HH:mm *}
						<div style="display: flex; align-items: center; margin-bottom: 12px;">
							<span class="fa fa-bullseye" style="width: 24px; margin-right: 12px; color: #5f6368;"></span>
							<div style="flex: 1; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
								{assign var="FIELD_MODEL" value=$RECORD_STRUCTURE['due_date']}
								{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),'Calendar')}
								{if isset($RECORD_STRUCTURE['time_end'])}
									{assign var="TIME_END_MODEL" value=$RECORD_STRUCTURE['time_end']}
									{include file=vtemplate_path($TIME_END_MODEL->getUITypeModel()->getTemplateName(),'Calendar')}
								{/if}
							</div>
						</div>

						{* 6. Add description *}
						<div style="display: flex; align-items: flex-start; margin-bottom: 12px;">
							<span class="fa fa-align-left" style="width: 24px; margin-right: 12px; color: #5f6368; margin-top: 8px;"></span>
							<div style="flex: 1;">
								<textarea name="description" class="form-control" rows="3" placeholder="{vtranslate('LBL_ADD_DESCRIPTION','Calendar')}" style="resize: vertical; font-size: 13px;"></textarea>
							</div>
						</div>

						{* 7. Assigned To + Status (table) *}
						<div class="container-fluid paddingTop15">
							<table class="massEditTable table no-border">
								<tr>
									{foreach key=FIELD_NAME item=FIELD_MODEL from=$RECORD_STRUCTURE name=blockfields}
									{if $FIELD_NAME eq 'subject' || $FIELD_NAME eq 'date_start' || $FIELD_NAME eq 'due_date' || $FIELD_NAME eq 'time_start' || $FIELD_NAME eq 'time_end' || $FIELD_NAME eq 'description' || $FIELD_NAME eq 'recurringtype'}
									{continue}
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
													<label class="muted">{vtranslate($FIELD_MODEL->get('label'), 'Calendar')} &nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}</label>
												{/if}
											{else}
												{vtranslate($FIELD_MODEL->get('label'), 'Calendar')}&nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
											{/if}
											{if $isReferenceField neq "reference"}</label>{/if}
									</td>
									<td class="fieldValue col-lg-9" {if $FIELD_MODEL->get('uitype') eq '19'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
										{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),'Calendar')}
									</td>
									{/foreach}
								</tr>
							</table>
						</div>
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
