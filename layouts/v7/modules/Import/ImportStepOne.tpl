{*<!--
/*********************************************************************************
** Import Step 1 — modern upload (CSV / Excel / VCF / ICS)
********************************************************************************/
-->*}

<div class="importBlockContainer show mk-import-panel" id="uploadFileContainer">
	<div class="mk-import-panel__head">
		{if $FORMAT eq 'vcf'}
			<h3 class="mk-import-panel__title">{'LBL_IMPORT_FROM_VCF_FILE'|@vtranslate:$MODULE}</h3>
		{elseif $FORMAT eq 'ics'}
			<h3 class="mk-import-panel__title">{'LBL_IMPORT_FROM_ICS_FILE'|@vtranslate:$MODULE}</h3>
		{else}
			<h3 class="mk-import-panel__title">Tải file lên để nhập liệu</h3>
			<p class="mk-import-panel__sub">Hỗ trợ CSV và Excel (.xlsx, .xls). Nhận encoding tiếng Việt tự động (UTF-8 / Windows). Map cột ở bước sau. Nên chia file lớn thành nhiều lần nếu trên ~20.000 dòng.</p>
		{/if}
	</div>

	{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Campaigns'}
		<div id="campaigns_import_success_banner_row" class="hide mk-import-banner-row">
			<div id="campaigns_import_success_banner" class="alert alert-success alert-dismissible" style="margin: 0;">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<strong>Campaign import completed successfully.</strong>
			</div>
		</div>
	{/if}
	{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Plans'}
		<div id="plans_import_success_banner_row" class="hide mk-import-banner-row">
			<div id="plans_import_success_banner" class="alert alert-success alert-dismissible" style="margin: 0;">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<strong>Plans import completed successfully.</strong>
			</div>
		</div>
	{/if}

	<div class="mk-import-fields" id="file_type_container">
		<input type="hidden" id="type" name="type" value="csv" />
		<div
			class="mk-import-dropzone"
			id="mk-import-dropzone"
			data-import-upload-size="{$IMPORT_UPLOAD_SIZE}"
			data-import-upload-size-mb="{$IMPORT_UPLOAD_SIZE_MB}"
		>
			<div class="mk-import-dropzone__icon" aria-hidden="true">
				<svg width="36" height="36" viewBox="0 0 24 24" fill="none">
					<path d="M12 16V4m0 0 4 4m-4-4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M4 14v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</div>
			<div class="mk-import-dropzone__copy">
				<strong>Kéo thả file vào đây</strong>
				<span>hoặc chọn từ máy tính</span>
			</div>
			<label class="mk-import-dropzone__btn fileUploadBtn btn btn-primary">
				<span><i class="fa fa-cloud-upload"></i> Chọn file CSV / Excel</span>
				<input type="file" name="import_file" id="import_file" onchange="Vtiger_Import_Js.checkFileType(event)" data-file-formats="{if $FORMAT eq ''}csv|xlsx|xls{else}{$FORMAT}{/if}" />
			</label>
			<div id="importFileDetails" class="mk-import-dropzone__file padding10">Chưa chọn file — CSV hoặc Excel (.xlsx, .xls)</div>
			<p class="mk-import-dropzone__hint">Tối đa ~{$IMPORT_UPLOAD_SIZE_MB} MB / lần · File lớn nên tách theo đợt để import ổn định</p>
		</div>

		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Campaigns'}
			<div class="mk-import-option-card" id="campaigns_sample_file_container">
				<div class="mk-import-option-card__label">{vtranslate('Reference file', $MODULE)}</div>
				<div>
					<a class="mk-import-sample-link" href="index.php?module=Campaigns&action=DownloadImportSample">
						<i class="fa fa-download"></i> {vtranslate('Download Sample CSV', $MODULE)}
					</a>
					<div class="mk-import-hint">
						{vtranslate('Use this file format as a reference for Campaigns import.', $MODULE)}
						<span id="campaigns_import_status_values_hint"></span>
					</div>
				</div>
			</div>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Plans'}
			<div class="mk-import-option-card" id="plans_sample_file_container">
				<div class="mk-import-option-card__label">{vtranslate('Reference file', $MODULE)}</div>
				<div>
					<a class="mk-import-sample-link" href="index.php?module=Plans&action=DownloadImportSample">
						<i class="fa fa-download"></i> {vtranslate('Download Sample CSV', $MODULE)}
					</a>
					<div class="mk-import-hint">
						{vtranslate('Use this file format as a reference for Plans import.', $MODULE)}
						<span id="plans_import_status_values_hint"></span>
					</div>
				</div>
			</div>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Contacts'}
			<div class="mk-import-option-card" id="contacts_sample_file_container">
				<div class="mk-import-option-card__label">{vtranslate('Reference file', $MODULE)}</div>
				<div>
					<a class="mk-import-sample-link" href="index.php?module=Contacts&action=DownloadImportSample">
						<i class="fa fa-download"></i> {vtranslate('Download Sample CSV', $MODULE)}
					</a>
					<div class="mk-import-hint">{vtranslate('Use this file format as a reference for Contacts import.', $MODULE)}</div>
				</div>
			</div>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Potentials'}
			<div class="mk-import-option-card" id="potentials_sample_file_container">
				<div class="mk-import-option-card__label">{vtranslate('Reference file', $MODULE)}</div>
				<div>
					<a class="mk-import-sample-link" href="index.php?module=Potentials&action=DownloadImportSample">
						<i class="fa fa-download"></i> Tải file mẫu Orders
					</a>
					<div class="mk-import-hint">Chọn file Opportunities.csv. Tự map <strong>Project Name</strong>, <strong>Organization Name</strong>, <strong>Contact Name</strong>. Bấm <strong>Import ngay</strong>.</div>
				</div>
			</div>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Accounts'}
			<div class="mk-import-option-card" id="accounts_sample_file_container">
				<div class="mk-import-option-card__label">{vtranslate('Reference file', $MODULE)}</div>
				<div>
					<a class="mk-import-sample-link" href="index.php?module=Accounts&action=DownloadImportSample">
						<i class="fa fa-download"></i> Tải file mẫu Tổ chức
					</a>
					<div class="mk-import-hint">Chọn file Organizations.csv. Tự map <strong>Organization Name</strong>, <strong>Billing Address</strong>, <strong>Company Code</strong>. Bấm <strong>Import ngay</strong>.</div>
				</div>
			</div>
		{/if}
		{if $FORMAT eq 'csv' && isset($FOR_MODULE) && $FOR_MODULE eq 'Leads'}
			<div class="mk-import-option-card" id="leads_sample_hint_container">
				<div class="mk-import-option-card__label">Gợi ý cột</div>
				<div class="mk-import-hint">Nên có: <strong>Họ tên</strong>, <strong>Điện thoại</strong>, <strong>Nguồn</strong>, <strong>Khu vực</strong>, <strong>Ghi chú</strong>. Map cột tự động ở bước tiếp theo.</div>
			</div>
		{/if}

		{if $FORMAT eq 'csv'}
			<div class="mk-import-option-card" id="has_header_container">
				<div class="mk-import-option-card__label">{'LBL_HAS_HEADER'|@vtranslate:$MODULE}</div>
				<label class="mk-import-check">
					<input type="checkbox" id="has_header" name="has_header" checked />
					<span>File có hàng tiêu đề cột</span>
				</label>
			</div>
		{/if}

		{if $FORMAT neq 'ics'}
			<div class="mk-import-option-card" id="file_encoding_container">
				<div class="mk-import-option-card__label">{'LBL_CHARACTER_ENCODING'|@vtranslate:$MODULE}</div>
				<select name="file_encoding" id="file_encoding" class="select2 mk-import-select">
					{foreach key=_FILE_ENCODING item=_FILE_ENCODING_LABEL from=$SUPPORTED_FILE_ENCODING}
						<option value="{$_FILE_ENCODING}">{$_FILE_ENCODING_LABEL|@vtranslate:$MODULE}</option>
					{/foreach}
				</select>
			</div>
		{/if}

		{if $FORMAT eq 'csv'}
			<div class="mk-import-option-card" id="delimiter_container">
				<div class="mk-import-option-card__label">{'LBL_DELIMITER'|@vtranslate:$MODULE}</div>
				<div class="mk-import-radio-row">
					{foreach key=_DELIMITER item=_DELIMITER_LABEL from=$SUPPORTED_DELIMITERS name=delimiters}
						<label class="mk-import-radio">
							<input type="radio" name="delimiter" value="{$_DELIMITER}" {if $smarty.foreach.delimiters.index eq 0} checked="true" {/if} />
							<span>{$_DELIMITER_LABEL|@vtranslate:$MODULE}</span>
						</label>
					{/foreach}
				</div>
			</div>
			{if isset($MULTI_CURRENCY) && $MULTI_CURRENCY}
				<div class="mk-import-option-card" id="lineitem_currency_container">
					<div class="mk-import-option-card__label">{vtranslate('LBL_IMPORT_LINEITEMS_CURRENCY',$MODULE)}</div>
					<select name="lineitem_currency" id="lineitem_currency" class="select2 mk-import-select">
						{foreach key=id item=CURRENCY from=$CURRENCIES}
							<option value="{$CURRENCY['currency_id']}">{$CURRENCY['currencycode']}</option>
						{/foreach}
					</select>
				</div>
			{/if}
		{/if}
	</div>
</div>
