{* KiotViet-style inline quote detail (expanded under list row) *}
{strip}
{assign var=FINAL_DETAILS value=$RELATED_PRODUCTS.1.final_details}
<div class="mk-so-inline-detail" data-record-id="{$RECORD->getId()}" data-module="Quotes" data-detail-url="{$INLINE_DETAIL_URL|escape}" data-edit-url="{$INLINE_EDIT_URL|escape}" data-print-url="{$INLINE_PRINT_URL|escape}" data-print-download-url="{$INLINE_PRINT_DOWNLOAD_URL|escape}" data-excel-url="index.php?module=Quotes&amp;action=ExportExcelForSale&amp;record={$RECORD->getId()}" data-amount-words="{$FINAL_DETAILS.amount_in_words|default:''|escape}" data-created-date="{$RECORD->getDisplayValue('createdtime')|escape}">
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
				<div class="mk-so-inline-detail__field" data-field-name="{$INFO_FIELD.name|escape}">
					<label class="mk-so-inline-detail__field-label">{$INFO_FIELD.label|escape}</label>
					<div class="mk-so-inline-detail__field-view">{$INFO_FIELD.value}</div>
				</div>
			{/foreach}
		</div>
	{/if}

	<div class="mk-so-inline-detail__lines-wrap">
		<table class="mk-so-inline-detail__lines">
			<thead>
				<tr>
					<th>Mã hàng</th>
					<th>Tên hàng</th>
					<th class="is-num">Số lượng</th>
					<th class="is-num">Đơn giá</th>
					<th class="is-num">Giảm giá</th>
					<th class="is-num">Giá bán</th>
					<th class="is-num">Thành tiền</th>
				</tr>
			</thead>
			<tbody>
				{assign var=HAS_LINE_ITEMS value=false}
				{foreach from=$RELATED_PRODUCTS key=IDX item=LINE}
					{if $IDX > 0 && $LINE["hdnProductId$IDX"]|default:'' neq ''}
						{assign var=HAS_LINE_ITEMS value=true}
						{assign var=DISCOUNT_TEXT value='0'}
						{if $LINE["discount_amount$IDX"]|default:'' neq '' && $LINE["discount_amount$IDX"] neq '0'}
							{assign var=DISCOUNT_TEXT value=$LINE["discount_amount$IDX"]}
						{elseif $LINE["discount_percent$IDX"]|default:'' neq '' && $LINE["discount_percent$IDX"] neq '0'}
							{assign var=DISCOUNT_TEXT value=$LINE["discount_percent$IDX"]|cat:'%'}
						{/if}
						<tr>
							<td class="is-code">
								{if $LINE["hdnProductId$IDX"]|default:'' neq ''}
									<a href="index.php?module={$LINE["entityType$IDX"]|default:'Products'}&view=Detail&record={$LINE["hdnProductId$IDX"]}" target="_blank" rel="noopener">
										{$LINE["hdnProductcode$IDX"]|default:'--'}
									</a>
								{else}
									{$LINE["hdnProductcode$IDX"]|default:'--'}
								{/if}
							</td>
							<td class="is-name">{$LINE["productName$IDX"]|default:'--'}</td>
							<td class="is-num">{$LINE["qty$IDX"]|default:'0'}</td>
							<td class="is-num">{$LINE["listPrice$IDX"]|default:'0'}</td>
							<td class="is-num">{$DISCOUNT_TEXT}</td>
							<td class="is-num">{$LINE["unitPrice$IDX"]|default:$LINE["listPrice$IDX"]|default:'0'}</td>
							<td class="is-num is-total">{$LINE["productTotal$IDX"]|default:'0'}</td>
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
	</div>

	<div class="mk-so-inline-detail__bottom">
		<div class="mk-so-inline-detail__notes">
			<label class="mk-so-inline-detail__notes-label" for="mk-qt-inline-note-{$RECORD->getId()}">Ghi chú</label>
			<textarea id="mk-qt-inline-note-{$RECORD->getId()}" class="mk-so-inline-detail__notes-input inputElement" name="description" rows="4" readonly>{$INLINE_NOTES|escape}</textarea>
		</div>
		<div class="mk-so-inline-detail__totals">
			<div class="mk-so-inline-detail__total-row">
				<span class="mk-so-inline-detail__total-label">Tổng tiền hàng</span>
				<strong class="mk-so-inline-detail__total-value">{$FINAL_DETAILS.hdnSubTotal|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row">
				<span class="mk-so-inline-detail__total-label">Thuế</span>
				<strong class="mk-so-inline-detail__total-value">{$FINAL_DETAILS.tax_totalamount|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row">
				<span class="mk-so-inline-detail__total-label">Giảm giá báo giá</span>
				<strong class="mk-so-inline-detail__total-value">{$FINAL_DETAILS.discountTotal_final|default:$FINAL_DETAILS.discount_amount_final|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row mk-so-inline-detail__total-row--grand">
				<span class="mk-so-inline-detail__total-label">Tổng cộng</span>
				<strong class="mk-so-inline-detail__total-value">{$FINAL_DETAILS.grandTotal|default:'0'}</strong>
			</div>
		</div>
	</div>

	<div class="mk-so-inline-detail__actions">
		<div class="mk-so-inline-detail__actions-left">
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
			<a class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__dup-btn" href="{$RECORD->getDuplicateRecordUrl()}&app=SALES" title="Nhân bản báo giá">
				<i class="fa fa-copy" aria-hidden="true"></i>
				<span>Nhân bản</span>
			</a>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--primary mk-so-inline-detail__confirm-order-btn" data-confirm-url="{$INLINE_CONFIRM_URL|escape}" title="Xác nhận và chuyển thành đơn hàng">
				<i class="fa fa-shopping-cart" aria-hidden="true"></i>
				<span>Xác nhận đơn hàng</span>
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
