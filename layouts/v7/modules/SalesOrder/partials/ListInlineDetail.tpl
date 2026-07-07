{* KiotViet-style inline order detail (expanded under list row) *}
{strip}
{assign var=FINAL_DETAILS value=$RELATED_PRODUCTS.1.final_details}
<div class="mk-so-inline-detail" data-record-id="{$RECORD->getId()}" data-module="SalesOrder" data-detail-url="{$INLINE_DETAIL_URL|escape}" data-print-url="{$INLINE_PRINT_URL|escape}" data-print-download-url="{$INLINE_PRINT_DOWNLOAD_URL|escape}">
	<div class="mk-so-inline-detail__tabs" role="tablist">
		<button type="button" class="mk-so-inline-detail__tab is-active" role="tab" aria-selected="true">Thông tin</button>
	</div>

	<div class="mk-so-inline-detail__hero">
		<div class="mk-so-inline-detail__hero-main">
			<div class="mk-so-inline-detail__customer">
				<span class="mk-so-inline-detail__customer-name">{$RECORD->getDisplayValue('account_id')}</span>
				<button type="button" class="mk-so-inline-detail__edit-toggle" title="Chỉnh sửa" aria-label="Chỉnh sửa" aria-pressed="false">
					<i class="fa fa-pencil" aria-hidden="true"></i>
				</button>
			</div>
			<div class="mk-so-inline-detail__order-no">{$RECORD->getDisplayValue('salesorder_no')}</div>
		</div>
		{if $INLINE_BRANCH_LABEL neq ''}
			<div class="mk-so-inline-detail__branch">{$INLINE_BRANCH_LABEL|escape}</div>
		{else}
			<div class="mk-so-inline-detail__branch mk-so-inline-detail__branch--muted">Chi nhánh trung tâm</div>
		{/if}
	</div>

	{if isset($INLINE_INFO_FIELDS) && $INLINE_INFO_FIELDS|@count gt 0}
		<div class="mk-so-inline-detail__fields">
			{foreach from=$INLINE_INFO_FIELDS item=INFO_FIELD}
				<div class="mk-so-inline-detail__field" data-field-name="{$INFO_FIELD.name|escape}" data-field-type="{$INFO_FIELD.data_type|escape}" data-editable="{if $INFO_FIELD.editable}1{else}0{/if}">
					<label class="mk-so-inline-detail__field-label">{$INFO_FIELD.label|escape}</label>
					<div class="mk-so-inline-detail__field-view">{$INFO_FIELD.value}</div>
					{if $INFO_FIELD.editable}
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
					{if $IDX > 0}
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
						<td colspan="7" class="mk-so-inline-detail__empty-lines">Chưa có hàng hóa trong đơn.</td>
					</tr>
				{/if}
			</tbody>
		</table>
	</div>

	<div class="mk-so-inline-detail__bottom">
		<div class="mk-so-inline-detail__notes">
			<label class="mk-so-inline-detail__notes-label" for="mk-so-inline-note-{$RECORD->getId()}">Ghi chú</label>
			<textarea id="mk-so-inline-note-{$RECORD->getId()}" class="mk-so-inline-detail__notes-input inputElement" name="description" rows="4" readonly>{decode_html($RECORD->get('description'))|escape}</textarea>
		</div>
		<div class="mk-so-inline-detail__totals">
			<div class="mk-so-inline-detail__total-row">
				<span>Tổng tiền hàng</span>
				<strong>{$FINAL_DETAILS.hdnSubTotal|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row">
				<span>Giảm giá phiếu đặt</span>
				<strong>{$FINAL_DETAILS.discountTotal_final|default:$FINAL_DETAILS.discount_amount_final|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row mk-so-inline-detail__total-row--grand">
				<span>Tổng cộng</span>
				<strong>{$FINAL_DETAILS.grandTotal|default:'0'}</strong>
			</div>
			{if $INLINE_PAID_FIELD neq ''}
				<div class="mk-so-inline-detail__total-row">
					<span>Khách đã trả</span>
					<strong>{$RECORD->getDisplayValue($INLINE_PAID_FIELD)}</strong>
				</div>
			{/if}
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
				<span>Xử lý đơn hàng</span>
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
