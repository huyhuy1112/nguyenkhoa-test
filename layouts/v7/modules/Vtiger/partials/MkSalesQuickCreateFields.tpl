{* Two-column luxury grid for standard Vtiger Quick Create field sets *}
{strip}
	<div class="mk-qc-sales-grid">
		{foreach key=FIELD_NAME item=FIELD_MODEL from=$RECORD_STRUCTURE name=blockfields}
			{assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
			{assign var="referenceList" value=$FIELD_MODEL->getReferenceList()}
			{assign var="referenceListCount" value=php7_count($referenceList)}
			{assign var="isSalutationField" value=($FIELD_MODEL->getUITypeModel()->getTemplateName() eq 'uitypes/Salutation.tpl')}
			<div class="mk-qc-sales-field fieldLabel fieldValue{if $FIELD_MODEL->get('uitype') eq '19'} mk-qc-sales-field--full{/if}{if $isSalutationField} mk-qc-sales-field--salutation{/if}">
				<div class="mk-qc-sales-field__label">
					{if $isReferenceField eq "reference"}
						{if $referenceListCount > 1}
							{assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
							{assign var="REFERENCED_MODULE_STRUCT" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
							{if !empty($REFERENCED_MODULE_STRUCT)}
								{assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCT->get('name')}
							{/if}
							<span class="mk-qc-sales-ref-modules">
								<select class="select2 referenceModulesList {if $FIELD_MODEL->isMandatory() eq true}reference-mandatory{/if}">
									{foreach key=index item=value from=$referenceList}
										<option value="{$value}" {if isset($REFERENCED_MODULE_NAME) && $value eq $REFERENCED_MODULE_NAME} selected {/if}>{vtranslate($value, $value)}</option>
									{/foreach}
								</select>
							</span>
						{else}
							<label class="mk-qc-sales-label">{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{if $FIELD_MODEL->isMandatory() eq true} <span class="mk-qc-required">*</span>{/if}</label>
						{/if}
					{elseif $FIELD_MODEL->get('uitype') eq '83'}
						{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) MODULE=$MODULE PULL_RIGHT=true}
					{else}
						<label class="mk-qc-sales-label">{vtranslate($FIELD_MODEL->get('label'), $MODULE)}{if $FIELD_MODEL->isMandatory() eq true} <span class="mk-qc-required">*</span>{/if}</label>
					{/if}
				</div>
				{if $FIELD_MODEL->get('uitype') neq '83'}
					<div class="mk-qc-sales-field__value">
						{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
					</div>
				{/if}
			</div>
		{/foreach}
	</div>
{/strip}
