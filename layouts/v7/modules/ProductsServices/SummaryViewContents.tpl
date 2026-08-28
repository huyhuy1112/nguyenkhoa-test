{* ProductsServices Summary fields — safe catalog grid (no MODULE_MODEL required). *}
{strip}
{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
	<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
{/if}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
<table class="summary-table no-border mk-ps-v2-field-table">
	<tbody>
	{foreach item=FIELD_MODEL key=FIELD_NAME from=$SUMMARY_RECORD_STRUCTURE['SUMMARY_FIELDS']}
		{if $FIELD_NAME eq 'sku' || $FIELD_NAME eq 'productsservicesname' || $FIELD_NAME eq 'item_type' || $FIELD_NAME eq 'unit' || $FIELD_NAME eq 'needs_qc' || $FIELD_NAME eq 'price' || $FIELD_NAME eq 'wholesale_price' || $FIELD_NAME eq 'specification' || $FIELD_NAME eq 'assigned_user_id' || $FIELD_NAME eq 'description'}
			{assign var=fieldDataType value=$FIELD_MODEL->getFieldDataType()}
			<tr class="summaryViewEntries mk-ps-v2-field" data-field-name="{$FIELD_NAME}">
				<td class="fieldLabel">
					<label class="muted textOverflowEllipsis" title="{vtranslate($FIELD_MODEL->get('label'),$MODULE_NAME)}">
						{vtranslate($FIELD_MODEL->get('label'),$MODULE_NAME)}
					</label>
				</td>
				<td class="fieldValue{if $FIELD_NAME eq 'price' || $FIELD_NAME eq 'wholesale_price'} mk-ps-v2-field--money{/if}{if $FIELD_NAME eq 'needs_qc'} mk-ps-v2-field--bool{/if}">
					<div class="mk-ps-v2-field__value">
						{assign var=DISPLAY_VALUE value="{$FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue'))}"}
						<span class="value textOverflowEllipsis" title="{strip_tags($DISPLAY_VALUE)}" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '20' or $FIELD_MODEL->get('uitype') eq '21'}style="word-wrap: break-word;"{/if}>
							{if $FIELD_NAME eq 'price' || $FIELD_NAME eq 'wholesale_price'}
								{assign var=MK_MONEY value=$DISPLAY_VALUE|replace:'$':'₫'|replace:'USD':'₫'|replace:'US$':'₫'|replace:'&nbsp;':' '|regex_replace:'/\s+/':' '}
								<span class="mk-ps-v2-money">{$MK_MONEY}</span>
							{elseif $FIELD_NAME eq 'needs_qc'}
								{assign var=MK_QC_RAW value=$FIELD_MODEL->get('fieldvalue')}
								{if $MK_QC_RAW eq 1 || $MK_QC_RAW eq '1' || $MK_QC_RAW eq 'on' || $MK_QC_RAW eq vtranslate('LBL_YES', $MODULE_NAME)}
									<span class="mk-ps-v2-qc is-on">Cần QC</span>
								{else}
									<span class="mk-ps-v2-qc">Không cần QC</span>
								{/if}
							{else}
								{include file=$FIELD_MODEL->getUITypeModel()->getDetailViewTemplateName()|@vtemplate_path:$MODULE_NAME FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME RECORD=$RECORD}
							{/if}
						</span>
						{if $FIELD_MODEL->isEditable() eq 'true' && $IS_AJAX_ENABLED && $FIELD_MODEL->isAjaxEditable() eq 'true' && $FIELD_MODEL->get('uitype') neq 69 && $FIELD_NAME neq 'needs_qc'}
							<span class="hide edit">
								{if $FIELD_MODEL->getFieldDataType() eq 'multipicklist'}
								<input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}[]' data-type="{$fieldDataType}" data-displayvalue='{Vtiger_Util_Helper::toSafeHTML($FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue')))}' data-value="{$FIELD_MODEL->get('fieldvalue')}" />
								{else}
								<input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}' data-type="{$fieldDataType}" data-displayvalue='{Vtiger_Util_Helper::toSafeHTML($FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue')))}' data-value="{$FIELD_MODEL->get('fieldvalue')}" />
								{/if}
							</span>
							<span class="action"><a href="#" onclick="return false;" class="editAction fa fa-pencil"></a></span>
						{/if}
					</div>
				</td>
			</tr>
		{/if}
	{/foreach}
	</tbody>
</table>
{else}
<table class="summary-table no-border">
	<tbody>
	{foreach item=FIELD_MODEL key=FIELD_NAME from=$SUMMARY_RECORD_STRUCTURE['SUMMARY_FIELDS']}
		{assign var=fieldDataType value=$FIELD_MODEL->getFieldDataType()}
		<tr class="summaryViewEntries">
			<td class="fieldLabel">
				<label class="muted textOverflowEllipsis" title="{vtranslate($FIELD_MODEL->get('label'),$MODULE_NAME)}">
					{vtranslate($FIELD_MODEL->get('label'),$MODULE_NAME)}
					{if $FIELD_MODEL->get('uitype') eq '71' || $FIELD_MODEL->get('uitype') eq '72'}
						{assign var=CURRENCY_INFO value=getCurrencySymbolandCRate($USER_MODEL->get('currency_id'))}
						&nbsp;({$CURRENCY_INFO['symbol']})
					{/if}
				</label>
			</td>
			<td class="fieldValue">
				<div class="">
					{assign var=DISPLAY_VALUE value="{$FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue'))}"}
					<span class="value textOverflowEllipsis" title="{strip_tags($DISPLAY_VALUE)}" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '20' or $FIELD_MODEL->get('uitype') eq '21'}style="word-wrap: break-word;"{/if}>
						{include file=$FIELD_MODEL->getUITypeModel()->getDetailViewTemplateName()|@vtemplate_path:$MODULE_NAME FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME RECORD=$RECORD}
					</span>
					{if $FIELD_MODEL->isEditable() eq 'true' && $IS_AJAX_ENABLED && $FIELD_MODEL->isAjaxEditable() eq 'true' && $FIELD_MODEL->get('uitype') neq 69}
						<span class="hide edit">
							{if $FIELD_MODEL->getFieldDataType() eq 'multipicklist'}
							<input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}[]' data-type="{$fieldDataType}" data-displayvalue='{Vtiger_Util_Helper::toSafeHTML($FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue')))}' data-value="{$FIELD_MODEL->get('fieldvalue')}" />
							{else}
							<input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}' data-type="{$fieldDataType}" data-displayvalue='{Vtiger_Util_Helper::toSafeHTML($FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue')))}' data-value="{$FIELD_MODEL->get('fieldvalue')}" />
							{/if}
						</span>
						<span class="action"><a href="#" onclick="return false;" class="editAction fa fa-pencil"></a></span>
					{/if}
				</div>
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>
{/if}
{/strip}
