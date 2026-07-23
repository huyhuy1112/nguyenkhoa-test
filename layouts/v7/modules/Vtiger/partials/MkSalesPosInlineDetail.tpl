{* Lightweight POS inline detail dropdown (Accounts / Leads / Opp / Contacts) *}
{strip}
<div class="mk-so-inline-detail is-edit-mode" data-always-edit="1" data-record-id="{$RECORD->getId()}" data-module="{$MODULE|escape}" data-detail-url="{$INLINE_DETAIL_URL|escape}" data-edit-url="{$INLINE_EDIT_URL|escape}">
	<div class="mk-so-inline-detail__tabs" role="tablist">
		<button type="button" class="mk-so-inline-detail__tab is-active" role="tab" aria-selected="true">Thông tin</button>
	</div>

	<div class="mk-so-inline-detail__hero">
		<div class="mk-so-inline-detail__hero-main">
			<div class="mk-so-inline-detail__customer">
				<span class="mk-so-inline-detail__customer-name">{$INLINE_TITLE|escape}</span>
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
					<span class="{if !empty($TAG.cls)}{$TAG.cls|escape}{else}mk-tag{/if}" data-tag="{$TAG.key|escape}" title="{$TAG.name|escape}">{$TAG.label|escape}</span>
				{/foreach}
			{else}
				<span class="mk-so-inline-detail__tags-empty">Chưa có tag</span>
			{/if}
		</div>
	</div>

	<div class="mk-so-inline-detail__bottom{if !empty($INLINE_SHOW_NEXT_ACTION) || !empty($INLINE_SHOW_CLASS_REG)} mk-so-inline-detail__bottom--split{/if}">
		{if !empty($INLINE_SHOW_CLASS_REG)}
			{assign var=CLASS_REG value=$INLINE_CLASS_REG|default:[]}
			<div class="mk-so-inline-detail__notes mk-so-inline-detail__class-reg" data-class-reg="1">
				<label class="mk-so-inline-detail__notes-label">Log đăng ký học</label>
				<p class="mk-so-inline-detail__class-reg-hint">{if isset($CLASS_REG.hint)}{$CLASS_REG.hint|escape}{/if}</p>
				<ul class="mk-so-inline-detail__class-reg-list">
					{if isset($CLASS_REG.logs) && $CLASS_REG.logs|@count gt 0}
						{foreach from=$CLASS_REG.logs item=REG_LOG}
							<li class="mk-so-inline-detail__class-reg-item{if !empty($REG_LOG.is_retake)} is-retake{/if}" data-id="{$REG_LOG.id|escape}">
								<span class="mk-so-inline-detail__class-reg-n{if !empty($REG_LOG.is_retake)} is-retake{/if}">{if !empty($REG_LOG.badge)}{$REG_LOG.badge|escape}{else}Lần {$REG_LOG.n|escape}{/if}</span>
								<span class="mk-so-inline-detail__class-reg-text">{$REG_LOG.label|escape}</span>
							</li>
						{/foreach}
					{else}
						<li class="mk-so-inline-detail__class-reg-empty">Chưa có lần đăng ký nào</li>
					{/if}
				</ul>
				{if !isset($CLASS_REG.can_add) || !empty($CLASS_REG.can_add)}
					<div class="mk-so-inline-detail__class-reg-add">
						<input type="text" class="mk-so-inline-detail__input mk-so-inline-detail__class-reg-date inputElement" placeholder="dd/mm/yyyy" maxlength="10" />
						<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__class-reg-add-btn">
							<i class="fa fa-plus" aria-hidden="true"></i>
							<span>Thêm đăng ký</span>
						</button>
					</div>
				{/if}
			</div>
		{elseif !empty($INLINE_SHOW_NEXT_ACTION)}
			<div class="mk-so-inline-detail__notes mk-so-inline-detail__next-action">
				<label class="mk-so-inline-detail__notes-label" for="mk-crm-inline-next-{$RECORD->getId()}">Hành động tiếp theo</label>
				<textarea id="mk-crm-inline-next-{$RECORD->getId()}" class="mk-so-inline-detail__notes-input mk-so-inline-detail__next-action-input inputElement" name="next_action" rows="3">{$INLINE_NEXT_ACTION|escape}</textarea>
			</div>
		{/if}
		<div class="mk-so-inline-detail__notes"{if empty($INLINE_SHOW_NEXT_ACTION) && empty($INLINE_SHOW_CLASS_REG)} style="grid-column: 1 / -1; width: 100%;"{/if}>
			<label class="mk-so-inline-detail__notes-label" for="mk-crm-inline-note-{$RECORD->getId()}">Ghi chú</label>
			<textarea id="mk-crm-inline-note-{$RECORD->getId()}" class="mk-so-inline-detail__notes-input inputElement" name="description" rows="3">{$INLINE_NOTES|escape}</textarea>
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
			{if $MODULE eq 'Accounts' && !empty($INLINE_PRINT_URL)}
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__print-btn" data-print-url="{$INLINE_PRINT_URL|escape}" data-print-download-url="{$INLINE_PRINT_DOWNLOAD_URL|default:$INLINE_PRINT_URL|escape}" title="In hợp đồng nhượng quyền TUI BAO">
				<i class="fa fa-file-pdf-o" aria-hidden="true"></i>
				<span class="mk-so-inline-detail__print-label">In hợp đồng</span>
			</button>
			{/if}
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__save-btn">
				<i class="fa fa-save" aria-hidden="true"></i>
				<span>Lưu</span>
			</button>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--primary mk-so-inline-detail__edit-btn">
				<i class="fa fa-external-link" aria-hidden="true"></i>
				<span>Mở form sửa</span>
			</button>
			{if $MODULE eq 'Leads'}
				{assign var=INLINE_CAN_CONVERT value=$INLINE_CAN_CONVERT|default:true}
				<button type="button"
					class="mk-so-inline-detail__action mk-so-inline-detail__action--convert mk-so-inline-detail__convert-btn{if empty($INLINE_CAN_CONVERT)} is-converted{/if}"
					data-record-id="{$RECORD->getId()|escape}"
					{if !empty($INLINE_POTENTIAL_URL)} data-potential-url="{$INLINE_POTENTIAL_URL|escape}"{/if}
					{if empty($INLINE_CAN_CONVERT)} disabled="disabled" aria-disabled="true"{/if}
					title="{if empty($INLINE_CAN_CONVERT)}Đã convert sang Opportunity{else}Convert to Opp{/if}">
					<i class="fa fa-exchange" aria-hidden="true"></i>
					<span>{if empty($INLINE_CAN_CONVERT)}Đã convert{else}Convert to Opp{/if}</span>
				</button>
			{/if}
		</div>
	</div>
</div>
{/strip}
