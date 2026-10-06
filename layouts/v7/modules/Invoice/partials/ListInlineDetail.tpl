{* Invoice inline panel — snapshot from SalesOrder → Invoice *}
{strip}
{assign var=FINAL_DETAILS value=$RELATED_PRODUCTS.1.final_details}
{assign var=INV_NO value=$RECORD->getDisplayValue('invoice_no')}
{if $INV_NO eq '' || $INV_NO eq '--'}
	{assign var=INV_NO value=$RECORD->getDisplayValue('subject')}
{/if}
<div class="mk-so-inline-detail mk-inv-inline-detail" data-record-id="{$RECORD->getId()}" data-module="Invoice" data-detail-url="{$INLINE_DETAIL_URL|escape}" data-edit-url="{$INLINE_EDIT_URL|escape}" data-print-url="{$INLINE_PRINT_URL|escape}" data-created-date="{$INLINE_CREATED_DATE|default:''|escape}" data-grand-raw="{$INLINE_GRAND_RAW|default:0|escape}" data-paid-field="{$INLINE_PAID_FIELD|default:'received'|escape}" data-invoice-status="{$INLINE_INVOICE_STATUS|default:''|escape}">
	<div class="mk-so-inline-detail__tabs" role="tablist">
		<button type="button" class="mk-so-inline-detail__tab is-active" role="tab" aria-selected="true">Thông tin hóa đơn</button>
	</div>

	<div class="mk-so-inline-detail__hero mk-inv-inline-detail__hero">
		<div class="mk-so-inline-detail__hero-main">
			<div class="mk-so-inline-detail__customer">
				<span class="mk-so-inline-detail__customer-name">{if isset($INLINE_CUSTOMER_NAME) && $INLINE_CUSTOMER_NAME neq '' && $INLINE_CUSTOMER_NAME neq '—'}{$INLINE_CUSTOMER_NAME|escape}{else}—{/if}</span>
			</div>
			<div class="mk-so-inline-detail__order-no">{$INV_NO|escape}</div>
		</div>
		{if $INLINE_INVOICE_STATUS_LABEL neq ''}
			<div class="mk-inv-inline-detail__status-pill">{$INLINE_INVOICE_STATUS_LABEL|escape}</div>
		{/if}
	</div>

	{if isset($INLINE_SO_ID) && $INLINE_SO_ID gt 0}
		<div class="mk-inv-inline-detail__source">
			<span class="mk-inv-inline-detail__source-label">Từ đơn hàng</span>
			{if $INLINE_SO_URL neq ''}
				<a class="mk-inv-inline-detail__source-link" href="{$INLINE_SO_URL|escape}" target="_blank" rel="noopener">
					{if $INLINE_SO_NO neq ''}{$INLINE_SO_NO|escape}{else}#{$INLINE_SO_ID}{/if}
				</a>
			{else}
				<strong>{if $INLINE_SO_NO neq ''}{$INLINE_SO_NO|escape}{else}#{$INLINE_SO_ID}{/if}</strong>
			{/if}
			{if $INLINE_SO_STATUS neq ''}
				<span class="mk-inv-inline-detail__source-status">{$INLINE_SO_STATUS|escape}</span>
			{/if}
		</div>
	{/if}

	{if isset($INLINE_INFO_FIELDS) && $INLINE_INFO_FIELDS|@count gt 0}
		<div class="mk-so-inline-detail__fields">
			{foreach from=$INLINE_INFO_FIELDS item=INFO_FIELD}
				<div class="mk-so-inline-detail__field" data-field-name="{$INFO_FIELD.name|escape}" data-editable="0">
					<label class="mk-so-inline-detail__field-label">{$INFO_FIELD.label|escape}</label>
					<div class="mk-so-inline-detail__field-view">{if !empty($INFO_FIELD.is_html)}{$INFO_FIELD.value nofilter}{else}{$INFO_FIELD.value|escape}{/if}</div>
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
					<th class="is-num">SL</th>
					<th class="is-num">Đơn giá</th>
					<th class="is-num">Thành tiền</th>
				</tr>
			</thead>
			<tbody>
				{assign var=HAS_LINE_ITEMS value=false}
				{foreach from=$RELATED_PRODUCTS key=IDX item=LINE}
					{if $IDX > 0 && $LINE["hdnProductId$IDX"]|default:'' neq ''}
						{assign var=HAS_LINE_ITEMS value=true}
						<tr>
							<td class="is-code">{$LINE["lineSku$IDX"]|default:$LINE["hdnProductcode$IDX"]|default:'—'}</td>
							<td class="is-name">{$LINE["productName$IDX"]|default:'—'}</td>
							<td class="is-num">{$LINE["qty$IDX"]|default:'0'}</td>
							<td class="is-num">{$LINE["listPrice$IDX"]|default:$LINE["unitPrice$IDX"]|default:'0'}</td>
							<td class="is-num is-total">{$LINE["productTotal$IDX"]|default:$LINE["netPrice$IDX"]|default:'0'}</td>
						</tr>
					{/if}
				{/foreach}
				{if !$HAS_LINE_ITEMS}
					<tr>
						<td colspan="5" class="mk-so-inline-detail__empty-lines">Chưa có dòng hàng trên hóa đơn.</td>
					</tr>
				{/if}
			</tbody>
		</table>
	</div>

	<div class="mk-so-inline-detail__bottom">
		<div class="mk-inv-inline-detail__hint">
			<p>Hóa đơn được tạo từ đơn hàng. Kiểm tra khách hàng, dòng hàng và số tiền trước khi xử lý kế toán.</p>
		</div>
		<div class="mk-so-inline-detail__totals">
			<div class="mk-so-inline-detail__total-row">
				<span class="mk-so-inline-detail__total-label">Tổng tiền hàng</span>
				<strong class="mk-so-inline-detail__total-value">{$FINAL_DETAILS.hdnSubTotal|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row mk-so-inline-detail__total-row--paid">
				<span class="mk-so-inline-detail__total-label">Khách đã trả</span>
				<strong class="mk-so-inline-detail__total-value">{$INLINE_PAID_DISPLAY|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row">
				<span class="mk-so-inline-detail__total-label">Còn lại</span>
				<strong class="mk-so-inline-detail__total-value">{$INLINE_REMAINING_DISPLAY|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row mk-so-inline-detail__total-row--grand">
				<span class="mk-so-inline-detail__total-label">Tổng cộng</span>
				<strong class="mk-so-inline-detail__total-value mk-so-inline-detail__grand-value">{$FINAL_DETAILS.grandTotal|default:'0'}</strong>
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
			{if isset($INLINE_SO_URL) && $INLINE_SO_URL neq ''}
				<a class="mk-so-inline-detail__action mk-so-inline-detail__action--outline" href="{$INLINE_SO_URL|escape}" target="_blank" rel="noopener">
					<i class="fa fa-shopping-cart" aria-hidden="true"></i>
					<span>Mở đơn hàng</span>
				</a>
			{/if}
			<a class="mk-so-inline-detail__action mk-so-inline-detail__action--outline" href="{$INLINE_DETAIL_URL|escape}">
				<i class="fa fa-file-text-o" aria-hidden="true"></i>
				<span>Chi tiết HĐ</span>
			</a>
			<a class="mk-so-inline-detail__action mk-so-inline-detail__action--primary" href="{$INLINE_EDIT_URL|escape}">
				<i class="fa fa-pencil" aria-hidden="true"></i>
				<span>Sửa hóa đơn</span>
			</a>
		</div>
	</div>
</div>
{/strip}
