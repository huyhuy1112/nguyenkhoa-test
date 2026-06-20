{* Duplicate handling — modern dual-list picker *}

<div class="importBlockContainer hide mk-import-panel" id="importStep2Conatiner">
	<div class="mk-import-panel__head">
		<h3 class="mk-import-panel__title">{'LBL_DUPLICATE_RECORD_HANDLING'|@vtranslate:$MODULE}</h3>
		<p class="mk-import-panel__sub">Chọn cách xử lý bản ghi trùng và các trường dùng để đối chiếu.</p>
	</div>

	<table class="table table-borderless mk-import-dup-table" id="duplicates_merge_configuration" cellpadding="0">
		<tr class="mk-import-dup-row mk-import-dup-row--merge">
			<td colspan="2">
				<label class="mk-import-label" for="merge_type">{'LBL_SPECIFY_MERGE_TYPE'|@vtranslate:$MODULE}</label>
				<select name="merge_type" id="merge_type" class="select select2 form-control mk-import-select">
					{foreach key=_MERGE_TYPE item=_MERGE_TYPE_LABEL from=$AUTO_MERGE_TYPES}
						<option value="{$_MERGE_TYPE}">{$_MERGE_TYPE_LABEL|@vtranslate:$MODULE}</option>
					{/foreach}
				</select>
				<p class="mk-import-field-hint">Skip = bỏ qua dòng trùng · Merge = gộp · Overwrite = ghi đè · Ignore = vẫn tạo mới.</p>
			</td>
		</tr>
		<tr class="mk-import-dup-row mk-import-dup-row--fields">
			<td colspan="2">
				<label class="mk-import-label">{'LBL_SELECT_MERGE_FIELDS'|@vtranslate:$MODULE}</label>
				<div class="mk-import-dual-list">
					<div class="mk-import-dual-list__col">
						<div class="mk-import-dual-list__title">
							<i class="fa fa-list-ul" aria-hidden="true"></i>
							{'LBL_AVAILABLE_FIELDS'|@vtranslate:$MODULE}
						</div>
						<select id="available_fields" multiple size="10" name="available_fields" class="mk-import-dual-list__select">
							{foreach key=_FIELD_NAME item=_FIELD_INFO from=$AVAILABLE_FIELDS}
								{if $_FIELD_NAME eq 'tags'} {continue} {/if}
								<option value="{$_FIELD_NAME}">{$_FIELD_INFO->getFieldLabelKey()|@vtranslate:$FOR_MODULE}</option>
							{/foreach}
						</select>
					</div>
					<div class="mk-import-dual-list__actions">
						<button type="button" class="mk-import-dual-list__btn" title="Thêm trường" onClick="return Vtiger_Import_Js.copySelectedOptions('#available_fields', '#selected_merge_fields')">
							<i class="fa fa-chevron-right" aria-hidden="true"></i>
						</button>
						<button type="button" class="mk-import-dual-list__btn" title="Bỏ trường" onClick="return Vtiger_Import_Js.removeSelectedOptions('#selected_merge_fields')">
							<i class="fa fa-chevron-left" aria-hidden="true"></i>
						</button>
					</div>
					<div class="mk-import-dual-list__col">
						<div class="mk-import-dual-list__title">
							<i class="fa fa-check-square-o" aria-hidden="true"></i>
							{'LBL_SELECTED_FIELDS'|@vtranslate:$MODULE}
						</div>
						<input type="hidden" id="merge_fields" size="10" name="merge_fields" value="" />
						<select id="selected_merge_fields" size="10" name="selected_merge_fields" multiple class="mk-import-dual-list__select">
							{foreach key=_FIELD_NAME item=_FIELD_INFO from=$ENTITY_FIELDS}
								<option value="{$_FIELD_NAME}">{$_FIELD_INFO->getFieldLabelKey()|@vtranslate:$FOR_MODULE}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</td>
		</tr>
	</table>
</div>
