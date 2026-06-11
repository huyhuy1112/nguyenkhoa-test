{* Users Edit / PreferenceEdit — modern field grid (content only) *}
{strip}
{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
	<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
{/if}
<div name="editContent" class="mk-users-edit-content" data-mk-users-edit="lux-v2">
	{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE name=blockIterator}
		{if $BLOCK_LABEL neq 'LBL_CALENDAR_SETTINGS' && $BLOCK_FIELDS|@count gt 0}
			<div class="fieldBlockContainer mk-users-edit-block" data-block="{$BLOCK_LABEL}">
				<h4 class="mk-users-edit-block__title">{vtranslate($BLOCK_LABEL, $MODULE)}</h4>
				<div class="mk-users-edit-fields-grid">
					{assign var=COUNTER value=0}
					{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS name=blockfields}
						{assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
						{assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
						{assign var="refrenceListCount" value=php7_count($refrenceList)}
						{if $FIELD_MODEL->getName() eq 'theme' or $FIELD_MODEL->getName() eq 'rowheight'}
							<input type="hidden" name="{$FIELD_MODEL->getName()}" value="{$FIELD_MODEL->get('fieldvalue')}" />
							{continue}
						{/if}
						{if !$FIELD_MODEL->isEditable()}
							{continue}
						{/if}
						{assign var=MK_EDIT_FULL value=false}
						{assign var=MK_EDIT_IMAGE value=false}
						{if $FIELD_MODEL->get('uitype') eq "19" || $FIELD_MODEL->get('label') eq 'Signature'}
							{assign var=MK_EDIT_FULL value=true}
						{/if}
						{if $FIELD_MODEL->get('uitype') eq "69" || $FIELD_MODEL->get('uitype') eq "105"}
							{assign var=MK_EDIT_IMAGE value=true}
							{assign var=MK_EDIT_FULL value=true}
						{/if}
						<div class="mk-users-edit-field{if $MK_EDIT_FULL} mk-users-edit-field--full{/if}{if $MK_EDIT_IMAGE} mk-users-edit-field--image{/if}">
							<label class="mk-users-edit-field__label fieldLabel alignMiddle" for="{$MODULE}_editView_fieldName_{$FIELD_MODEL->getName()}">
								{if $isReferenceField eq "reference" && $refrenceListCount > 1}
									<select style="width: 100%; max-width: 220px;" class="select2 referenceModulesList">
										{foreach key=index item=value from=$refrenceList}
											<option value="{$value}">{vtranslate($value, $value)}</option>
										{/foreach}
									</select>
								{else}
									<span class="mk-users-edit-field__label-text">
										{vtranslate($FIELD_MODEL->get('label'), $MODULE)}
										{if $FIELD_MODEL->isMandatory() eq true}<span class="redColor">*</span>{/if}
									</span>
								{/if}
							</label>
							<div class="mk-users-edit-field__control fieldValue{if in_array($FIELD_MODEL->get('uitype'),array('19')) || $FIELD_MODEL->get('label') eq 'Signature'} fieldValueWidth80{/if}" id="{$MODULE}_editView_fieldName_{$FIELD_MODEL->getName()}">
								{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
							</div>
						</div>
					{/foreach}
				</div>
			</div>
		{/if}
	{/foreach}
</div>
{/strip}
