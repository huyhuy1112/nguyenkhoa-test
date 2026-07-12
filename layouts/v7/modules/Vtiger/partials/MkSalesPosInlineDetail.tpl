{* Lightweight POS inline detail dropdown (Accounts / Leads / Opp / Contacts) *}
{strip}
<div class="mk-so-inline-detail" data-record-id="{$RECORD->getId()}" data-module="{$MODULE|escape}" data-detail-url="{$INLINE_DETAIL_URL|escape}" data-edit-url="{$INLINE_EDIT_URL|escape}">
	<div class="mk-so-inline-detail__tabs" role="tablist">
		<button type="button" class="mk-so-inline-detail__tab is-active" role="tab" aria-selected="true">Thông tin</button>
	</div>

	<div class="mk-so-inline-detail__hero">
		<div class="mk-so-inline-detail__hero-main">
			<div class="mk-so-inline-detail__customer">
				<span class="mk-so-inline-detail__customer-name">{$INLINE_TITLE|escape}</span>
				<button type="button" class="mk-so-inline-detail__edit-toggle" title="Chỉnh sửa" aria-label="Chỉnh sửa" aria-pressed="false">
					<i class="fa fa-pencil" aria-hidden="true"></i>
				</button>
			</div>
			{if $INLINE_SUBTITLE neq ''}
				<div class="mk-so-inline-detail__order-no">{$INLINE_SUBTITLE|escape}</div>
			{/if}
		</div>
	</div>

	{if isset($INLINE_INFO_FIELDS) && $INLINE_INFO_FIELDS|@count gt 0}
		<div class="mk-so-inline-detail__fields">
			{foreach from=$INLINE_INFO_FIELDS item=INFO_FIELD}
				<div class="mk-so-inline-detail__field" data-field-name="{$INFO_FIELD.name|escape}" data-field-type="{$INFO_FIELD.data_type|default:'string'|escape}" data-editable="{if !empty($INFO_FIELD.editable)}1{else}0{/if}">
					<label class="mk-so-inline-detail__field-label">{$INFO_FIELD.label|escape}</label>
					<div class="mk-so-inline-detail__field-view">{$INFO_FIELD.value|escape}</div>
					{if !empty($INFO_FIELD.editable)}
						<div class="mk-so-inline-detail__field-edit">
							{if $INFO_FIELD.data_type eq 'picklist'}
								<select class="mk-so-inline-detail__input mk-so-inline-detail__select-native" name="{$INFO_FIELD.name|escape}">
									{foreach from=$INFO_FIELD.picklist_values key=PK item=PL}
										<option value="{$PK|escape}" {if $INFO_FIELD.raw_value eq $PK}selected="selected"{/if}>{$PL|escape}</option>
									{/foreach}
								</select>
							{elseif $INFO_FIELD.data_type eq 'owner'}
								<select class="mk-so-inline-detail__input inputElement" name="{$INFO_FIELD.name|escape}">
									{foreach from=$INLINE_ASSIGNED_USERS key=UID item=UNAME}
										<option value="{$UID|escape}" {if $INFO_FIELD.raw_value eq $UID}selected="selected"{/if}>{$UNAME|escape}</option>
									{/foreach}
								</select>
							{elseif $INFO_FIELD.data_type eq 'date' || $INFO_FIELD.data_type eq 'datetime'}
								<input type="text" class="mk-so-inline-detail__input inputElement dateField" name="{$INFO_FIELD.name|escape}" value="{$INFO_FIELD.raw_value|escape}" data-date-format="{$USER_MODEL->get('date_format')|escape}" />
							{else}
								<input type="text" class="mk-so-inline-detail__input inputElement" name="{$INFO_FIELD.name|escape}" value="{$INFO_FIELD.raw_value|escape}" />
							{/if}
						</div>
					{/if}
				</div>
			{/foreach}
		</div>
	{/if}

	<div class="mk-so-inline-detail__tags">
		<label class="mk-so-inline-detail__field-label">Tags</label>
		<div class="mk-so-inline-detail__tags-list">
			{if isset($INLINE_TAGS) && $INLINE_TAGS|@count gt 0}
				{foreach from=$INLINE_TAGS item=TAG}
					<span class="{$TAG.cls|escape}" title="{$TAG.name|escape}">{$TAG.label|escape}</span>
				{/foreach}
			{else}
				<span class="mk-so-inline-detail__tags-empty">Chưa có tag</span>
			{/if}
		</div>
	</div>

	<div class="mk-so-inline-detail__bottom">
		<div class="mk-so-inline-detail__notes" style="grid-column: 1 / -1; width: 100%;">
			<label class="mk-so-inline-detail__notes-label" for="mk-crm-inline-note-{$RECORD->getId()}">Ghi chú</label>
			<textarea id="mk-crm-inline-note-{$RECORD->getId()}" class="mk-so-inline-detail__notes-input inputElement" name="description" rows="3" readonly>{$INLINE_NOTES|escape}</textarea>
		</div>
	</div>

	<div class="mk-so-inline-detail__actions">
		<div class="mk-so-inline-detail__actions-left">
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--ghost mk-so-inline-detail__cancel-edit">
				<i class="fa fa-times" aria-hidden="true"></i>
				<span>Hủy sửa</span>
			</button>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--ghost mk-so-inline-detail__view-full-btn">
				<i class="fa fa-expand" aria-hidden="true"></i>
				<span>Xem đầy đủ</span>
			</button>
		</div>
		<div class="mk-so-inline-detail__actions-right">
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__save-btn">
				<i class="fa fa-save" aria-hidden="true"></i>
				<span>Lưu</span>
			</button>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--primary mk-so-inline-detail__edit-btn">
				<i class="fa fa-external-link" aria-hidden="true"></i>
				<span>Mở form sửa</span>
			</button>
		</div>
	</div>
</div>
{/strip}
