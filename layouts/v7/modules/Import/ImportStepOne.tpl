{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}

<div class ="importBlockContainer show" id = "uploadFileContainer">
    <table class = "table table-borderless" cellpadding = "30" >
        <span>
			{if $FORMAT eq 'vcf'}
				<h4>&nbsp;&nbsp;&nbsp;{'LBL_IMPORT_FROM_VCF_FILE'|@vtranslate:$MODULE}</h4>
			{else if $FORMAT eq 'ics'}
				<h4>&nbsp;&nbsp;&nbsp;{'LBL_IMPORT_FROM_ICS_FILE'|@vtranslate:$MODULE}</h4>
			{else}
				<h4>&nbsp;&nbsp;&nbsp;{'LBL_IMPORT_FROM_CSV_FILE'|@vtranslate:$MODULE}</h4>
			{/if}
        </span>
        <hr>
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Campaigns'}
			<tr id="campaigns_import_success_banner_row" class="hide">
				<td colspan="2">
					<div id="campaigns_import_success_banner" class="alert alert-success alert-dismissible" style="margin: 0 0 10px 0;">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
						<strong>Campaign import completed successfully.</strong>
					</div>
				</td>
			</tr>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Plans'}
			<tr id="plans_import_success_banner_row" class="hide">
				<td colspan="2">
					<div id="plans_import_success_banner" class="alert alert-success alert-dismissible" style="margin: 0 0 10px 0;">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
						<strong>Plans import completed successfully.</strong>
					</div>
				</td>
			</tr>
		{/if}
        <tr id="file_type_container" style="height:50px">
			{if $FORMAT eq 'vcf'}
				<td>{'LBL_SELECT_VCF_FILE'|@vtranslate:$MODULE}</td>
			{else if $FORMAT eq 'ics'}
				<td>{'LBL_SELECT_ICS_FILE'|@vtranslate:$MODULE}</td>
			{else}
				<td>{'LBL_SELECT_CSV_FILE'|@vtranslate:$MODULE}</td>
			{/if}
            <td data-import-upload-size="{$IMPORT_UPLOAD_SIZE}" data-import-upload-size-mb="{$IMPORT_UPLOAD_SIZE_MB}">
                <div>
                    <input type="hidden" id="type" name="type" value="csv" />
                    <input type="hidden" name="is_scheduled" value="1" />
                    <div class="fileUploadBtn btn btn-primary">
                        <span><i class="fa fa-laptop"></i> {vtranslate('Select from My Computer', $MODULE)}</span>
                        <input type="file" name="import_file" id="import_file" onchange="Vtiger_Import_Js.checkFileType(event)" data-file-formats="{if $FORMAT eq ''}csv{else}{$FORMAT}{/if}" />
                    </div>
                    <div id="importFileDetails" class="padding10"></div>
                </div>
            </td>
        </tr>
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Campaigns'}
			<tr id="campaigns_sample_file_container" style="height:50px">
				<td>{vtranslate('Reference file', $MODULE)}</td>
				<td>
					<a class="btn btn-default" href="index.php?module=Campaigns&action=DownloadImportSample">
						<i class="fa fa-download"></i>&nbsp;{vtranslate('Download Sample CSV', $MODULE)}
					</a>
					<div class="text-muted" style="margin-top:6px;">
						{vtranslate('Use this file format as a reference for Campaigns import.', $MODULE)}
						<span id="campaigns_import_status_values_hint" style="margin-left:6px;"></span>
					</div>
				</td>
			</tr>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Plans'}
			<tr id="plans_sample_file_container" style="height:50px">
				<td>{vtranslate('Reference file', $MODULE)}</td>
				<td>
					<a class="btn btn-default" href="index.php?module=Plans&action=DownloadImportSample">
						<i class="fa fa-download"></i>&nbsp;{vtranslate('Download Sample CSV', $MODULE)}
					</a>
					<div class="text-muted" style="margin-top:6px;">
						{vtranslate('Use this file format as a reference for Plans import.', $MODULE)}
						<span id="plans_import_status_values_hint" style="margin-left:6px;"></span>
					</div>
				</td>
			</tr>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Contacts'}
			<tr id="contacts_sample_file_container" style="height:50px">
				<td>{vtranslate('Reference file', $MODULE)}</td>
				<td>
					<a class="btn btn-default" href="index.php?module=Contacts&action=DownloadImportSample">
						<i class="fa fa-download"></i>&nbsp;{vtranslate('Download Sample CSV', $MODULE)}
					</a>
					<div class="text-muted" style="margin-top:6px;">
						{vtranslate('Use this file format as a reference for Contacts import.', $MODULE)}
					</div>
				</td>
			</tr>
		{/if}
        {if $FORMAT eq 'csv'}
            <tr id="has_header_container" style="height:50px">
                <td>{'LBL_HAS_HEADER'|@vtranslate:$MODULE}</td>
                <td>
                    <input type="checkbox" id="has_header" name="has_header" checked />
                </td>
            </tr>
        {/if}
		{if $FORMAT neq 'ics'}
			<tr id="file_encoding_container" style="height:50px">
				<td>{'LBL_CHARACTER_ENCODING'|@vtranslate:$MODULE}</td>
				<td>
					<select name="file_encoding" id="file_encoding" class="select2">
						{foreach key=_FILE_ENCODING item=_FILE_ENCODING_LABEL from=$SUPPORTED_FILE_ENCODING}
							<option value="{$_FILE_ENCODING}">{$_FILE_ENCODING_LABEL|@vtranslate:$MODULE}</option>
						{/foreach}
					</select>
				</td>
			</tr>
		{/if}
        {if $FORMAT eq 'csv'}
            <tr id="delimiter_container" style="height:50px">
                <td>{'LBL_DELIMITER'|@vtranslate:$MODULE}</td>
                <td>
                    {foreach key=_DELIMITER item=_DELIMITER_LABEL from=$SUPPORTED_DELIMITERS name=delimiters}
                        &nbsp;&nbsp;<label class="radio-group"><input type="radio" name="delimiter" value="{$_DELIMITER}" {if $smarty.foreach.delimiters.index eq 0} checked="true" {/if} style="margin-bottom: -2px;">&nbsp;&nbsp;{$_DELIMITER_LABEL|@vtranslate:$MODULE}</label>
                    {/foreach}
                </td>
            </tr>
            {if isset($MULTI_CURRENCY) && $MULTI_CURRENCY}
                <tr id="lineitem_currency_container" style="height:50px">
                    <td>{vtranslate('LBL_IMPORT_LINEITEMS_CURRENCY',$MODULE)}</td>
                    <td>
                        <select name="lineitem_currency" id="lineitem_currency" class = "select2">
                            {$i = 0}
                            {foreach key=id item=CURRENCY from=$CURRENCIES}
                                <option value="{$CURRENCY['currency_id']}">{$CURRENCY['currencycode']}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
            {/if}
        {/if}
    </table>
</div>
