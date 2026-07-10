{* Lightweight POS inline detail dropdown (Accounts / ServiceContracts) *}
{strip}
<div class="mk-so-inline-detail" data-record-id="{$RECORD->getId()}" data-module="{$MODULE|escape}" data-detail-url="{$INLINE_DETAIL_URL|escape}" data-edit-url="{$INLINE_EDIT_URL|escape}">
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
				<div class="mk-so-inline-detail__field" data-field-name="{$INFO_FIELD.name|escape}">
					<label class="mk-so-inline-detail__field-label">{$INFO_FIELD.label|escape}</label>
					<div class="mk-so-inline-detail__field-view">{$INFO_FIELD.value|escape}</div>
				</div>
			{/foreach}
		</div>
	{/if}

	{if $INLINE_NOTES neq ''}
		<div class="mk-so-inline-detail__bottom">
			<div class="mk-so-inline-detail__notes" style="grid-column: 1 / -1; width: 100%;">
				<label class="mk-so-inline-detail__notes-label">Ghi chú</label>
				<textarea class="mk-so-inline-detail__notes-input inputElement" rows="3" readonly>{$INLINE_NOTES|escape}</textarea>
			</div>
		</div>
	{/if}

	<div class="mk-so-inline-detail__actions">
		<div class="mk-so-inline-detail__actions-left">
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--ghost mk-so-inline-detail__view-full-btn">
				<i class="fa fa-expand" aria-hidden="true"></i>
				<span>Xem đầy đủ</span>
			</button>
		</div>
		<div class="mk-so-inline-detail__actions-right">
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--primary mk-so-inline-detail__edit-btn">
				<i class="fa fa-pencil" aria-hidden="true"></i>
				<span>Chỉnh sửa</span>
			</button>
		</div>
	</div>
</div>
{/strip}
