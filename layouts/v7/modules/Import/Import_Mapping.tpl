{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<input type="hidden" name="merge_type" value='{$USER_INPUT->get('merge_type')}' />
	<input type="hidden" name="merge_fields" value='{if isset($MERGE_FIELDS)}{$MERGE_FIELDS}{else}""{/if}' />
	<input type="hidden" name="lineitem_currency" value='{if isset($LINEITEM_CURRENCY)}{$LINEITEM_CURRENCY}{else}''{/if}'>
	<input type="hidden" id="mandatory_fields" name="mandatory_fields" value='{$ENCODED_MANDATORY_FIELDS}' />
	<input type="hidden" name="field_mapping" id="field_mapping" value="" />
	<input type="hidden" name="default_values" id="default_values" value="" />
	<table width="100%" class="table table-bordered">
		<thead>
			<tr>
				{if $HAS_HEADER eq true}
					<th width="25%">{'LBL_FILE_COLUMN_HEADER'|@vtranslate:$MODULE}</th>
					{/if}
				<th width="25%">{'LBL_ROW_1'|@vtranslate:$MODULE}</th>
				<th width="23%">{'LBL_CRM_FIELDS'|@vtranslate:$MODULE}</th>
				<th width="27%">{'LBL_DEFAULT_VALUE'|@vtranslate:$MODULE}</th>
			</tr>
		</thead>
		<tbody>
			{foreach key=_HEADER_NAME item=_FIELD_VALUE from=$ROW_1_DATA name="headerIterator"}
				{assign var="_COUNTER" value=$smarty.foreach.headerIterator.iteration}
				{assign var="_HEADER_RAW" value=decode_html($_HEADER_NAME)}
				{assign var="_HEADER_NORM" value=strtolower(trim($_HEADER_RAW|replace:"\xEF\xBB\xBF":"")|regex_replace:"/\\s+/":" ")}
				{assign var="_SELECTED_FIELD_NAME" value=""}

				{* Campaigns: deterministic English-header auto-map (no multi-selected options) *}
				{if $FOR_MODULE eq 'Campaigns'}
					{if $_HEADER_NORM eq 'campaign name'}{assign var="_SELECTED_FIELD_NAME" value="campaignname"}
					{elseif $_HEADER_NORM eq 'campaign status'}{assign var="_SELECTED_FIELD_NAME" value="campaignstatus"}
					{elseif $_HEADER_NORM eq 'campaign type'}{assign var="_SELECTED_FIELD_NAME" value="campaigntype"}
					{elseif $_HEADER_NORM eq 'start date'}{assign var="_SELECTED_FIELD_NAME" value="start_date"}
					{elseif $_HEADER_NORM eq 'expected close date'}{assign var="_SELECTED_FIELD_NAME" value="closingdate"}
					{elseif $_HEADER_NORM eq 'expected revenue'}{assign var="_SELECTED_FIELD_NAME" value="expectedrevenue"}
					{elseif $_HEADER_NORM eq 'assigned to'}{assign var="_SELECTED_FIELD_NAME" value="assigned_user_id"}
					{elseif $_HEADER_NORM eq 'description'}{assign var="_SELECTED_FIELD_NAME" value="description"}
					{/if}
				{/if}

				{* Contacts: deterministic English-header auto-map (safe common fields) *}
				{if $FOR_MODULE eq 'Contacts'}
					{if $_HEADER_NORM eq 'first name' || $_HEADER_NORM eq 'firstname'}{assign var="_SELECTED_FIELD_NAME" value="firstname"}
					{elseif $_HEADER_NORM eq 'last name' || $_HEADER_NORM eq 'lastname'}{assign var="_SELECTED_FIELD_NAME" value="lastname"}
					{elseif $_HEADER_NORM eq 'organization name' || $_HEADER_NORM eq 'account name' || $_HEADER_NORM eq 'organization'}{assign var="_SELECTED_FIELD_NAME" value="account_id"}
					{elseif $_HEADER_NORM eq 'email' || $_HEADER_NORM eq 'email address'}{assign var="_SELECTED_FIELD_NAME" value="email"}
					{elseif $_HEADER_NORM eq 'mobile phone' || $_HEADER_NORM eq 'mobile'}{assign var="_SELECTED_FIELD_NAME" value="mobile"}
					{elseif $_HEADER_NORM eq 'assigned to' || $_HEADER_NORM eq 'assigned_user_id'}{assign var="_SELECTED_FIELD_NAME" value="assigned_user_id"}
					{/if}
				{/if}

				<tr class="fieldIdentifier" id="fieldIdentifier{$_COUNTER}">
					{if $HAS_HEADER eq true}
						<td>
							<span style="word-break:break-all" name="header_name">{$_HEADER_NAME}</span>
						</td>
					{/if}
					<td>
						<span>{$_FIELD_VALUE|@textlength_check}</span>
					</td>
					<td>
						<input type="hidden" name="row_counter" value="{$_COUNTER}" />
						<select name="mapped_fields" class="select2 mappedFieldsSelect" style="width:100%" onchange="Vtiger_Import_Js.loadDefaultValueWidget('fieldIdentifier{$_COUNTER}')">
							<option value="">{'LBL_SELECT_OPTION'|@vtranslate:$FOR_MODULE}</option>
							{foreach key=_FIELD_NAME item=_FIELD_INFO from=$AVAILABLE_FIELDS}
								{assign var="_TRANSLATED_FIELD_LABEL" value=$_FIELD_INFO->getFieldLabelKey()|@vtranslate:$FOR_MODULE}
								{assign var="EVENTS_TRANSLATED_FIELD_LABEL" value=$_FIELD_INFO->getFieldLabelKey()|@vtranslate:Events}
								{assign var="_FALLBACK_SELECTED" value=($_HEADER_NORM eq strtolower($_TRANSLATED_FIELD_LABEL))}
								<option value="{$_FIELD_NAME}"
										{if $_FIELD_NAME eq $_SELECTED_FIELD_NAME} selected
										{elseif $_SELECTED_FIELD_NAME eq '' && $_FALLBACK_SELECTED} selected
										{elseif $_FIELD_NAME eq 'due_date' && $_HEADER_NORM eq strtolower($EVENTS_TRANSLATED_FIELD_LABEL)} selected
										{/if}
										data-label="{$_TRANSLATED_FIELD_LABEL}">{$_TRANSLATED_FIELD_LABEL}{if $_FIELD_INFO->isMandatory() eq 'true' || $_FIELD_NAME eq 'activitytype'}&nbsp; (*){/if}</option>
							{/foreach}
						</select>
					</td>
					<td name="default_value_container">&nbsp;</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{/strip}