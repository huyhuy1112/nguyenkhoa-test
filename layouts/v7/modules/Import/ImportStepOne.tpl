{*<!--
/*********************************************************************************
** Import Step 1 — file upload (CSV / Excel / VCF / ICS)
********************************************************************************/
-->*}

<div class="importBlockContainer show mk-import-panel" id="uploadFileContainer">
	<div class="mk-import-panel__head">
		{if $FORMAT eq 'vcf'}
			<h3 class="mk-import-panel__title">{'LBL_IMPORT_FROM_VCF_FILE'|@vtranslate:$MODULE}</h3>
		{elseif $FORMAT eq 'ics'}
			<h3 class="mk-import-panel__title">{'LBL_IMPORT_FROM_ICS_FILE'|@vtranslate:$MODULE}</h3>
		{else}
			<h3 class="mk-import-panel__title">{'LBL_IMPORT_FROM_CSV_FILE'|@vtranslate:$MODULE}</h3>
			<p class="mk-import-panel__sub">Hỗ trợ CSV và Excel (.xlsx, .xls). Tiếng Việt được tự nhận encoding (UTF-8 / Windows). Map cột tự động ở bước 2.</p>
		{/if}
	</div>

	<table class="table table-borderless" cellpadding="0">
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Campaigns'}
			<tr id="campaigns_import_success_banner_row" class="hide">
				<td colspan="2">
					<div id="campaigns_import_success_banner" class="alert alert-success alert-dismissible" style="margin: 0;">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<strong>Campaign import completed successfully.</strong>
					</div>
				</td>
			</tr>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Plans'}
			<tr id="plans_import_success_banner_row" class="hide">
				<td colspan="2">
					<div id="plans_import_success_banner" class="alert alert-success alert-dismissible" style="margin: 0;">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<strong>Plans import completed successfully.</strong>
					</div>
				</td>
			</tr>
		{/if}

		<tr id="file_type_container">
			{if $FORMAT eq 'vcf'}
				<td>{'LBL_SELECT_VCF_FILE'|@vtranslate:$MODULE}</td>
			{elseif $FORMAT eq 'ics'}
				<td>{'LBL_SELECT_ICS_FILE'|@vtranslate:$MODULE}</td>
			{else}
				<td>{'LBL_SELECT_CSV_FILE'|@vtranslate:$MODULE}</td>
			{/if}
			<td data-import-upload-size="{$IMPORT_UPLOAD_SIZE}" data-import-upload-size-mb="{$IMPORT_UPLOAD_SIZE_MB}">
				<input type="hidden" id="type" name="type" value="csv" />
				<div class="fileUploadBtn btn btn-primary">
					<span><i class="fa fa-cloud-upload"></i> {vtranslate('Select from My Computer', $MODULE)}</span>
					<input type="file" name="import_file" id="import_file" onchange="Vtiger_Import_Js.checkFileType(event)" data-file-formats="{if $FORMAT eq ''}csv|xlsx|xls{else}{$FORMAT}{/if}" />
				</div>
				<div id="importFileDetails" class="padding10"></div>
			</td>
		</tr>

		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Campaigns'}
			<tr id="campaigns_sample_file_container">
				<td>{vtranslate('Reference file', $MODULE)}</td>
				<td>
					<a class="mk-import-sample-link" href="index.php?module=Campaigns&action=DownloadImportSample">
						<i class="fa fa-download"></i> {vtranslate('Download Sample CSV', $MODULE)}
					</a>
					<div class="mk-import-hint">
						{vtranslate('Use this file format as a reference for Campaigns import.', $MODULE)}
						<span id="campaigns_import_status_values_hint"></span>
					</div>
				</td>
			</tr>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Plans'}
			<tr id="plans_sample_file_container">
				<td>{vtranslate('Reference file', $MODULE)}</td>
				<td>
					<a class="mk-import-sample-link" href="index.php?module=Plans&action=DownloadImportSample">
						<i class="fa fa-download"></i> {vtranslate('Download Sample CSV', $MODULE)}
					</a>
					<div class="mk-import-hint">
						{vtranslate('Use this file format as a reference for Plans import.', $MODULE)}
						<span id="plans_import_status_values_hint"></span>
					</div>
				</td>
			</tr>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Contacts'}
			<tr id="contacts_sample_file_container">
				<td>{vtranslate('Reference file', $MODULE)}</td>
				<td>
					<a class="mk-import-sample-link" href="index.php?module=Contacts&action=DownloadImportSample">
						<i class="fa fa-download"></i> {vtranslate('Download Sample CSV', $MODULE)}
					</a>
					<div class="mk-import-hint">{vtranslate('Use this file format as a reference for Contacts import.', $MODULE)}</div>
				</td>
			</tr>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Potentials'}
			<tr id="potentials_sample_file_container">
				<td>{vtranslate('Reference file', $MODULE)}</td>
				<td>
					<a class="mk-import-sample-link" href="index.php?module=Potentials&action=DownloadImportSample">
						<i class="fa fa-download"></i> Tải file mẫu Orders
					</a>
					<div class="mk-import-hint">Chọn file Opportunities.csv. Tự map <strong>Project Name</strong>, <strong>Organization Name</strong>, <strong>Contact Name</strong>. Bấm <strong>Import ngay</strong>.</div>
				</td>
			</tr>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Accounts'}
			<tr id="accounts_sample_file_container">
				<td>{vtranslate('Reference file', $MODULE)}</td>
				<td>
					<a class="mk-import-sample-link" href="index.php?module=Accounts&action=DownloadImportSample">
						<i class="fa fa-download"></i> Tải file mẫu Tổ chức
					</a>
					<div class="mk-import-hint">Chọn file Organizations.csv. Tự map <strong>Organization Name</strong>, <strong>Billing Address</strong>, <strong>Company Code</strong>. Bấm <strong>Import ngay</strong>.</div>
				</td>
			</tr>
		{/if}

		{if $FORMAT eq 'csv'}
			<tr id="has_header_container">
				<td>{'LBL_HAS_HEADER'|@vtranslate:$MODULE}</td>
				<td><input type="checkbox" id="has_header" name="has_header" checked /></td>
			</tr>
		{/if}

		{if $FORMAT neq 'ics'}
			<tr id="file_encoding_container">
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
			<tr id="delimiter_container">
				<td>{'LBL_DELIMITER'|@vtranslate:$MODULE}</td>
				<td>
					{foreach key=_DELIMITER item=_DELIMITER_LABEL from=$SUPPORTED_DELIMITERS name=delimiters}
						<label class="radio-group"><input type="radio" name="delimiter" value="{$_DELIMITER}" {if $smarty.foreach.delimiters.index eq 0} checked="true" {/if}>&nbsp;{$_DELIMITER_LABEL|@vtranslate:$MODULE}</label>
					{/foreach}
				</td>
			</tr>
			{if isset($MULTI_CURRENCY) && $MULTI_CURRENCY}
				<tr id="lineitem_currency_container">
					<td>{vtranslate('LBL_IMPORT_LINEITEMS_CURRENCY',$MODULE)}</td>
					<td>
						<select name="lineitem_currency" id="lineitem_currency" class="select2">
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
