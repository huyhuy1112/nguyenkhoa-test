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
	<table width="100%" class="table table-bordered mk-import-map-table importMappingTable">
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
					{if $_HEADER_NORM eq 'campaign name' || $_HEADER_NORM eq 'tên chiến dịch'}{assign var="_SELECTED_FIELD_NAME" value="campaignname"}
					{elseif $_HEADER_NORM eq 'campaign status' || $_HEADER_NORM eq 'trạng thái' || $_HEADER_NORM eq 'trạng thái chiến dịch'}{assign var="_SELECTED_FIELD_NAME" value="campaignstatus"}
					{elseif $_HEADER_NORM eq 'campaign type' || $_HEADER_NORM eq 'loại chiến dịch'}{assign var="_SELECTED_FIELD_NAME" value="campaigntype"}
					{elseif $_HEADER_NORM eq 'start date' || $_HEADER_NORM eq 'ngày bắt đầu'}{assign var="_SELECTED_FIELD_NAME" value="start_date"}
					{elseif $_HEADER_NORM eq 'expected close date' || $_HEADER_NORM eq 'ngày kết thúc dự kiến'}{assign var="_SELECTED_FIELD_NAME" value="closingdate"}
					{elseif $_HEADER_NORM eq 'expected revenue' || $_HEADER_NORM eq 'doanh thu dự kiến'}{assign var="_SELECTED_FIELD_NAME" value="expectedrevenue"}
					{elseif $_HEADER_NORM eq 'assigned to' || $_HEADER_NORM eq 'phụ trách'}{assign var="_SELECTED_FIELD_NAME" value="assigned_user_id"}
					{elseif $_HEADER_NORM eq 'description' || $_HEADER_NORM eq 'mô tả' || $_HEADER_NORM eq 'ghi chú'}{assign var="_SELECTED_FIELD_NAME" value="description"}
					{/if}
				{/if}

				{* Contacts: English + Vietnamese headers *}
				{if $FOR_MODULE eq 'Contacts'}
					{if $_HEADER_NORM eq 'first name' || $_HEADER_NORM eq 'firstname' || $_HEADER_NORM eq 'họ'}{assign var="_SELECTED_FIELD_NAME" value="firstname"}
					{elseif $_HEADER_NORM eq 'last name' || $_HEADER_NORM eq 'lastname' || $_HEADER_NORM eq 'tên'}{assign var="_SELECTED_FIELD_NAME" value="lastname"}
					{elseif $_HEADER_NORM eq 'organization name' || $_HEADER_NORM eq 'account name' || $_HEADER_NORM eq 'organization' || $_HEADER_NORM eq 'tên tổ chức' || $_HEADER_NORM eq 'tên khách hàng'}{assign var="_SELECTED_FIELD_NAME" value="account_id"}
					{elseif $_HEADER_NORM eq 'email' || $_HEADER_NORM eq 'email address' || $_HEADER_NORM eq 'email liên lạc'}{assign var="_SELECTED_FIELD_NAME" value="email"}
					{elseif $_HEADER_NORM eq 'mobile phone' || $_HEADER_NORM eq 'mobile' || $_HEADER_NORM eq 'sđt' || $_HEADER_NORM eq 'điện thoại'}{assign var="_SELECTED_FIELD_NAME" value="mobile"}
					{elseif $_HEADER_NORM eq 'assigned to' || $_HEADER_NORM eq 'assigned_user_id' || $_HEADER_NORM eq 'phụ trách'}{assign var="_SELECTED_FIELD_NAME" value="assigned_user_id"}
					{elseif $_HEADER_NORM eq 'phone' || $_HEADER_NORM eq 'số điện thoại'}{assign var="_SELECTED_FIELD_NAME" value="phone"}
					{/if}
				{/if}

				{* Plans: English + Vietnamese headers *}
				{if $FOR_MODULE eq 'Plans'}
					{if $_HEADER_NORM eq 'plan name' || $_HEADER_NORM eq 'tên kế hoạch' || $_HEADER_NORM eq 'tên plan'}{assign var="_SELECTED_FIELD_NAME" value="planname"}
					{elseif $_HEADER_NORM eq 'status' || $_HEADER_NORM eq 'trạng thái' || $_HEADER_NORM eq 'plan status'}{assign var="_SELECTED_FIELD_NAME" value="plan_status"}
					{elseif $_HEADER_NORM eq 'start date' || $_HEADER_NORM eq 'ngày bắt đầu'}{assign var="_SELECTED_FIELD_NAME" value="start_date"}
					{elseif $_HEADER_NORM eq 'end date' || $_HEADER_NORM eq 'ngày kết thúc' || $_HEADER_NORM eq 'expected close date'}{assign var="_SELECTED_FIELD_NAME" value="end_date"}
					{elseif $_HEADER_NORM eq 'assigned to' || $_HEADER_NORM eq 'phụ trách'}{assign var="_SELECTED_FIELD_NAME" value="assigned_user_id"}
					{elseif $_HEADER_NORM eq 'description' || $_HEADER_NORM eq 'mô tả' || $_HEADER_NORM eq 'ghi chú'}{assign var="_SELECTED_FIELD_NAME" value="description"}
					{/if}
				{/if}

				{* Potentials / Orders: Vietnamese + English export headers *}
				{if $FOR_MODULE eq 'Potentials'}
					{if $_HEADER_NORM eq 'ghi chú' || $_HEADER_NORM eq 'description'}{assign var="_SELECTED_FIELD_NAME" value="description"}
					{elseif $_HEADER_NORM eq 'tiêu đề' || $_HEADER_NORM eq 'potential name' || $_HEADER_NORM eq 'potential' || $_HEADER_NORM eq 'opportunity name'}{assign var="_SELECTED_FIELD_NAME" value="potentialname"}
					{elseif $_HEADER_NORM eq 'project name' || $_HEADER_NORM eq 'tên dự án' || $_HEADER_NORM eq 'projectname'}{assign var="_SELECTED_FIELD_NAME" value="cf_857"}
					{elseif $_HEADER_NORM eq 'mã orders' || $_HEADER_NORM eq 'potential no' || $_HEADER_NORM eq 'order code'}{assign var="_SELECTED_FIELD_NAME" value="potential_no"}
					{elseif $_HEADER_NORM eq 'tên khách hàng' || $_HEADER_NORM eq 'organization name' || $_HEADER_NORM eq 'organisation name' || $_HEADER_NORM eq 'related to'}{assign var="_SELECTED_FIELD_NAME" value="related_to"}
					{elseif $_HEADER_NORM eq 'tên liên hệ' || $_HEADER_NORM eq 'contact name'}{assign var="_SELECTED_FIELD_NAME" value="contact_id"}
					{elseif $_HEADER_NORM eq 'loại order' || $_HEADER_NORM eq 'type' || $_HEADER_NORM eq 'opportunity type'}{assign var="_SELECTED_FIELD_NAME" value="opportunity_type"}
					{elseif $_HEADER_NORM eq 'giá trị dự kiến' || $_HEADER_NORM eq 'amount'}{assign var="_SELECTED_FIELD_NAME" value="amount"}
					{elseif $_HEADER_NORM eq 'nguồn order' || $_HEADER_NORM eq 'lead source'}{assign var="_SELECTED_FIELD_NAME" value="leadsource"}
					{elseif $_HEADER_NORM eq 'ngày dự kiến kết thúc' || $_HEADER_NORM eq 'expected close date'}{assign var="_SELECTED_FIELD_NAME" value="closingdate"}
					{elseif $_HEADER_NORM eq 'phụ trách' || $_HEADER_NORM eq 'assigned to'}{assign var="_SELECTED_FIELD_NAME" value="assigned_user_id"}
					{elseif $_HEADER_NORM eq 'bước tiếp theo_d' || $_HEADER_NORM eq 'bước tiếp theo' || $_HEADER_NORM eq 'next step'}{assign var="_SELECTED_FIELD_NAME" value="nextstep"}
					{elseif $_HEADER_NORM eq 'nguồn chiến dịch' || $_HEADER_NORM eq 'campaign source'}{assign var="_SELECTED_FIELD_NAME" value="campaignid"}
					{elseif $_HEADER_NORM eq 'trạng thái order' || $_HEADER_NORM eq 'sales stage'}{assign var="_SELECTED_FIELD_NAME" value="sales_stage"}
					{elseif $_HEADER_NORM eq 'xác suất' || $_HEADER_NORM eq 'probability'}{assign var="_SELECTED_FIELD_NAME" value="probability"}
					{elseif $_HEADER_NORM eq 'dự đoán giá trị' || $_HEADER_NORM eq 'forecast amount'}{assign var="_SELECTED_FIELD_NAME" value="forecast_amount"}
					{elseif $_HEADER_NORM eq 'phân loại order' || $_HEADER_NORM eq 'order category'}{assign var="_SELECTED_FIELD_NAME" value="order_category"}
					{/if}
				{/if}

				{* Accounts / Tổ chức: Vietnamese + English export headers *}
				{if $FOR_MODULE eq 'Accounts'}
					{if $_HEADER_NORM eq 'tên' || $_HEADER_NORM eq 'tên ngắn gọn thường gọi' || $_HEADER_NORM eq 'account name' || $_HEADER_NORM eq 'organization name'}{assign var="_SELECTED_FIELD_NAME" value="accountname"}
					{elseif $_HEADER_NORM eq 'tên đầy đủ' || $_HEADER_NORM eq 'fullname' || $_HEADER_NORM eq 'full name'}{assign var="_SELECTED_FIELD_NAME" value="fullname"}
					{elseif $_HEADER_NORM eq 'mã số thuế' || $_HEADER_NORM eq 'sic code' || $_HEADER_NORM eq 'tax id'}{assign var="_SELECTED_FIELD_NAME" value="siccode"}
					{elseif $_HEADER_NORM eq 'địa chỉ trụ sở chính' || $_HEADER_NORM eq 'địa chỉ' || $_HEADER_NORM eq 'billing address' || $_HEADER_NORM eq 'bill street'}{assign var="_SELECTED_FIELD_NAME" value="bill_street"}
					{elseif $_HEADER_NORM eq 'số điện thoại liên hệ' || $_HEADER_NORM eq 'phone' || $_HEADER_NORM eq 'mobile' || $_HEADER_NORM eq 'primary phone'}{assign var="_SELECTED_FIELD_NAME" value="phone"}
					{elseif $_HEADER_NORM eq 'email liên lạc' || $_HEADER_NORM eq 'email' || $_HEADER_NORM eq 'primary email'}{assign var="_SELECTED_FIELD_NAME" value="email1"}
					{elseif $_HEADER_NORM eq 'trang web' || $_HEADER_NORM eq 'website'}{assign var="_SELECTED_FIELD_NAME" value="website"}
					{elseif $_HEADER_NORM eq 'ngành nghề kinh doanh' || $_HEADER_NORM eq 'industry' || $_HEADER_NORM eq 'ngành'}{assign var="_SELECTED_FIELD_NAME" value="industry"}
					{elseif $_HEADER_NORM eq 'phụ trách' || $_HEADER_NORM eq 'assigned to'}{assign var="_SELECTED_FIELD_NAME" value="assigned_user_id"}
					{elseif $_HEADER_NORM eq 'số hiệu tổ chức' || $_HEADER_NORM eq 'account no' || $_HEADER_NORM eq 'organization number'}{assign var="_SELECTED_FIELD_NAME" value="account_no"}
					{elseif $_HEADER_NORM eq 'description' || $_HEADER_NORM eq 'mô tả' || $_HEADER_NORM eq 'ghi chú'}{assign var="_SELECTED_FIELD_NAME" value="description"}
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