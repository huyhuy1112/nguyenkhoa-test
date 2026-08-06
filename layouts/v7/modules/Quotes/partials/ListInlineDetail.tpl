{* KiotViet-style inline quote detail (expanded under list row) *}
{strip}
{assign var=FINAL_DETAILS value=$RELATED_PRODUCTS.1.final_details}
<div class="mk-so-inline-detail is-edit-mode" data-always-edit="1" data-record-id="{$RECORD->getId()}" data-module="Quotes" data-quote-stage="{$INLINE_QUOTE_STAGE|default:''|escape}" data-can-confirm-order="{if !empty($INLINE_CAN_CONFIRM_ORDER)}1{else}0{/if}" data-detail-url="{$INLINE_DETAIL_URL|escape}" data-edit-url="{$INLINE_EDIT_URL|escape}" data-print-url="{$INLINE_PRINT_URL|escape}" data-print-download-url="{$INLINE_PRINT_DOWNLOAD_URL|escape}" data-excel-url="index.php?module=Quotes&amp;action=ExportExcelForSale&amp;record={$RECORD->getId()}" data-amount-words="{$FINAL_DETAILS.amount_in_words|default:''|escape}" data-created-date="{$RECORD->getDisplayValue('createdtime')|escape}" data-subtotal-raw="{$FINAL_DETAILS.hdnSubTotal|default:'0'|escape}">
	<div class="mk-so-inline-detail__tabs" role="tablist">
		<button type="button" class="mk-so-inline-detail__tab is-active" role="tab" aria-selected="true">Thông tin</button>
	</div>

	<div class="mk-so-inline-detail__hero">
		<div class="mk-so-inline-detail__hero-main">
			<div class="mk-so-inline-detail__customer">
				<span class="mk-so-inline-detail__customer-name">{$INLINE_CUSTOMER_NAME|escape}</span>
			</div>
			<div class="mk-so-inline-detail__order-no">{$RECORD->getDisplayValue('quote_no')}</div>
		</div>
	</div>

	{if isset($INLINE_INFO_FIELDS) && $INLINE_INFO_FIELDS|@count gt 0}
		<div class="mk-so-inline-detail__fields">
			{foreach from=$INLINE_INFO_FIELDS item=INFO_FIELD}
				<div class="mk-so-inline-detail__field" data-field-name="{$INFO_FIELD.name|escape}" data-field-type="{$INFO_FIELD.data_type|default:'string'|escape}" data-editable="{if !empty($INFO_FIELD.editable)}1{else}0{/if}">
					<label class="mk-so-inline-detail__field-label">{$INFO_FIELD.label|escape}</label>
					<div class="mk-so-inline-detail__field-view">{$INFO_FIELD.value}</div>
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

	<div class="mk-so-inline-detail__lines-wrap">
		<table class="mk-so-inline-detail__lines">
			<thead>
				<tr>
					<th>SKU</th>
					<th>Tên hàng</th>
					<th class="is-num">Số lượng</th>
					<th class="is-num">Đơn giá</th>
					<th class="is-num">Chiết khấu</th>
					<th class="is-num">Thành tiền</th>
					<th class="is-note">Ghi chú</th>
				</tr>
			</thead>
			<tbody>
				{assign var=HAS_LINE_ITEMS value=false}
				{foreach from=$RELATED_PRODUCTS key=IDX item=LINE}
					{if $IDX > 0 && $LINE["hdnProductId$IDX"]|default:'' neq ''}
						{assign var=HAS_LINE_ITEMS value=true}
						{assign var=LINE_COMMENT value=$LINE["comment$IDX"]|default:''}
						{assign var=DISC_PCT value=$LINE["discount_percent$IDX"]|default:0}
						{assign var=DISC_TOTAL value=$LINE["discountTotal$IDX"]|default:0}
						<tr data-qty="{$LINE["qty$IDX"]|default:'1'|escape}" data-price="{$LINE["listPrice$IDX"]|default:$LINE["unitPrice$IDX"]|default:'0'|escape}" data-total="{$LINE["netPrice$IDX"]|default:$LINE["productTotal$IDX"]|default:'0'|escape}" data-unit="{$LINE["usageunit$IDX"]|default:''|escape}" data-sequence="{$IDX|escape}">
							<td class="is-code">
								{if $LINE["hdnProductId$IDX"]|default:'' neq ''}
									<a href="index.php?module={$LINE["entityType$IDX"]|default:'Products'}&view=Detail&record={$LINE["hdnProductId$IDX"]}" target="_blank" rel="noopener">
										{$LINE["lineSku$IDX"]|default:$LINE["hdnProductcode$IDX"]|default:'--'}
									</a>
								{else}
									{$LINE["lineSku$IDX"]|default:$LINE["hdnProductcode$IDX"]|default:'--'}
								{/if}
							</td>
							<td class="is-name">{$LINE["productName$IDX"]|default:'--'}</td>
							<td class="is-num">{$LINE["qty$IDX"]|default:'0'}</td>
							<td class="is-num">{$LINE["listPrice$IDX"]|default:'0'}</td>
							<td class="is-num is-disc">
								{if $DISC_PCT neq '' && $DISC_PCT neq 0}
									{$DISC_PCT}%
								{elseif $DISC_TOTAL neq '' && $DISC_TOTAL neq 0}
									{$DISC_TOTAL}
								{else}
									0%
								{/if}
							</td>
							<td class="is-num is-total">{$LINE["netPrice$IDX"]|default:$LINE["productTotal$IDX"]|default:'0'}</td>
							<td class="is-note">
								<input type="text" class="mk-so-inline-detail__line-note inputElement" name="line_comment_{$IDX|escape}" data-sequence="{$IDX|escape}" value="{$LINE_COMMENT|escape}" placeholder="Ghi chú dòng" maxlength="500" autocomplete="off" />
							</td>
						</tr>
					{/if}
				{/foreach}
				{if !$HAS_LINE_ITEMS}
					<tr>
						<td colspan="7" class="mk-so-inline-detail__empty-lines">Chưa có hàng hóa trong báo giá.</td>
					</tr>
				{/if}
			</tbody>
		</table>
		<p class="mk-so-inline-detail__vat-note" role="note"><strong>Đơn giá này đã bao gồm VAT</strong></p>
	</div>

	<div class="mk-so-inline-detail__bottom">
		<div class="mk-so-inline-detail__notes">
			<label class="mk-so-inline-detail__notes-label" for="mk-qt-inline-note-{$RECORD->getId()}">Ghi chú</label>
			<textarea id="mk-qt-inline-note-{$RECORD->getId()}" class="mk-so-inline-detail__notes-input inputElement" name="description" rows="4">{$INLINE_NOTES|escape}</textarea>
		</div>
		<div class="mk-so-inline-detail__totals">
			<div class="mk-so-inline-detail__total-row">
				<span class="mk-so-inline-detail__total-label">Tổng tiền hàng</span>
				<strong class="mk-so-inline-detail__total-value mk-so-inline-detail__subtotal-value">{$FINAL_DETAILS.hdnSubTotal|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row mk-so-inline-detail__total-row--grand">
				<span class="mk-so-inline-detail__total-label">Tổng cộng</span>
				<input type="text" class="mk-so-inline-detail__total-value mk-so-inline-detail__grand-input inputElement" name="hdnGrandTotal_manual" value="{$FINAL_DETAILS.grandTotal|default:'0'|escape}" inputmode="numeric" autocomplete="off" title="Có thể sửa tay tổng cộng" aria-label="Tổng cộng" />
			</div>
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
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--primary mk-so-inline-detail__process-btn">
				<i class="fa fa-check" aria-hidden="true"></i>
				<span>Xử lý báo giá</span>
			</button>
			<a class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__dup-btn" href="#" data-record-id="{$RECORD->getId()}" title="Nhân bản báo giá">
				<i class="fa fa-copy" aria-hidden="true"></i>
				<span>Nhân bản</span>
			</a>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--primary mk-so-inline-detail__confirm-order-btn{if empty($INLINE_CAN_CONFIRM_ORDER)} is-hidden{/if}" data-confirm-url="{$INLINE_CONFIRM_URL|escape}" title="Xác nhận và chuyển thành đơn hàng">
				<i class="fa fa-shopping-cart" aria-hidden="true"></i>
				<span>Xác nhận đơn hàng</span>
			</button>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__save-btn">
				<i class="fa fa-save" aria-hidden="true"></i>
				<span>Lưu</span>
			</button>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__print-btn" data-print-url="{$INLINE_PRINT_URL|escape}" data-print-download-url="{$INLINE_PRINT_DOWNLOAD_URL|escape}" data-print-ready="0">
				<i class="fa fa-print" aria-hidden="true"></i>
				<span class="mk-so-inline-detail__print-label">In</span>
			</button>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__export-btn" data-export-ready="0">
				<i class="fa fa-file-excel-o" aria-hidden="true"></i>
				<span class="mk-so-inline-detail__export-label">Export Excel</span>
			</button>
		</div>
	</div>
</div>
{/strip}
