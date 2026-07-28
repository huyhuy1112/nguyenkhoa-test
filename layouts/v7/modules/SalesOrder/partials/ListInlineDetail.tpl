{* KiotViet-style inline order detail (expanded under list row) *}
{strip}
{assign var=FINAL_DETAILS value=$RELATED_PRODUCTS.1.final_details}
{assign var=INLINE_SOSTATUS value=$RECORD->get('sostatus')}
<div class="mk-so-inline-detail is-edit-mode" data-always-edit="1" data-record-id="{$RECORD->getId()}" data-module="SalesOrder" data-sostatus="{$INLINE_SOSTATUS|escape}" data-detail-url="{$INLINE_DETAIL_URL|escape}" data-print-url="{$INLINE_PRINT_URL|escape}" data-print-download-url="{$INLINE_PRINT_DOWNLOAD_URL|escape}" data-excel-url="index.php?module=SalesOrder&amp;action=ExportExcelForSale&amp;record={$RECORD->getId()}" data-amount-words="{$INLINE_AMOUNT_WORDS|default:''|escape}" data-created-date="{$INLINE_CREATED_DATE|default:''|escape}" data-grand-raw="{$INLINE_GRAND_RAW|default:0|escape}" data-paid-field="{$INLINE_PAID_FIELD|default:'received'|escape}">
	<div class="mk-so-inline-detail__tabs" role="tablist">
		<button type="button" class="mk-so-inline-detail__tab is-active" role="tab" aria-selected="true">Thông tin</button>
	</div>

	<div class="mk-so-inline-detail__hero">
		<div class="mk-so-inline-detail__hero-main">
			<div class="mk-so-inline-detail__customer">
				<span class="mk-so-inline-detail__customer-name">{if isset($INLINE_CUSTOMER_NAME) && $INLINE_CUSTOMER_NAME neq '' && $INLINE_CUSTOMER_NAME neq '—'}{$INLINE_CUSTOMER_NAME}{else}--{/if}</span>
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
					<div class="mk-so-inline-detail__field-view">{if !empty($INFO_FIELD.is_html)}{$INFO_FIELD.value nofilter}{else}{$INFO_FIELD.value}{/if}</div>
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
					<th>SKU</th>
					<th>Tên hàng</th>
					<th class="is-num">Số lượng</th>
					<th class="is-num">Đơn giá</th>
					<th class="is-num">Thuế</th>
					<th class="is-num">Giá bán</th>
					<th class="is-num">Thành tiền</th>
				</tr>
			</thead>
			<tbody>
				{assign var=HAS_LINE_ITEMS value=false}
				{foreach from=$RELATED_PRODUCTS key=IDX item=LINE}
					{if $IDX > 0 && $LINE["hdnProductId$IDX"]|default:'' neq ''}
						{assign var=HAS_LINE_ITEMS value=true}
						<tr data-qty="{$LINE["qty$IDX"]|default:'1'|escape}" data-price="{$LINE["listPrice$IDX"]|default:$LINE["unitPrice$IDX"]|default:'0'|escape}" data-total="{$LINE["productTotal$IDX"]|default:'0'|escape}" data-unit="{$LINE["usageunit$IDX"]|default:''|escape}">
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
							<td class="is-num">{$LINE["taxTotal$IDX"]|default:'0'}</td>
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
				<span>Thuế</span>
				<strong>{$FINAL_DETAILS.tax_totalamount|default:'0'}</strong>
			</div>
			<div class="mk-so-inline-detail__total-row mk-so-inline-detail__total-row--paid" data-field-name="{$INLINE_PAID_FIELD|default:'received'|escape}">
				<span class="mk-so-inline-detail__total-label">Khách đã trả</span>
				<span class="mk-so-inline-detail__paid-view">{$INLINE_PAID_DISPLAY|default:'0'}</span>
				<input type="text" class="mk-so-inline-detail__paid-input inputElement" name="{$INLINE_PAID_FIELD|default:'received'|escape}" value="{$INLINE_PAID_DISPLAY|default:'0'|escape}" inputmode="decimal" autocomplete="off" readonly />
			</div>
			<div class="mk-so-inline-detail__total-row mk-so-inline-detail__total-row--grand" data-grand-raw="{$INLINE_GRAND_RAW|default:0|escape}">
				<span>Tổng cộng</span>
				<strong class="mk-so-inline-detail__grand-value">{$INLINE_REMAINING_DISPLAY|default:$FINAL_DETAILS.grandTotal|default:'0'}</strong>
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
			{assign var=SO_ALREADY_CONFIRMED value=(
				$INLINE_SOSTATUS eq 'Approved' || $INLINE_SOSTATUS eq 'Đã xác nhận' || $INLINE_SOSTATUS eq 'Đã duyệt'
				|| $INLINE_SOSTATUS eq 'waiting_print' || $INLINE_SOSTATUS eq 'picking' || $INLINE_SOSTATUS eq 'packed'
				|| $INLINE_SOSTATUS eq 'shipped' || $INLINE_SOSTATUS eq 'rejected'
				|| $INLINE_SOSTATUS eq 'Delivered' || $INLINE_SOSTATUS eq 'Đã giao' || $INLINE_SOSTATUS eq 'Hoàn thành'
				|| $INLINE_SOSTATUS eq 'Chờ in phiếu' || $INLINE_SOSTATUS eq 'Đang soạn' || $INLINE_SOSTATUS eq 'Đã soạn'
				|| $INLINE_SOSTATUS eq 'Từ chối'
			)}
			{if !$SO_ALREADY_CONFIRMED}
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--primary mk-so-inline-detail__confirm-order-btn" title="Xác nhận đơn hàng và tạo phiếu xuất kho">
				<i class="fa fa-check" aria-hidden="true"></i>
				<span>Xác nhận đơn hàng</span>
			</button>
			{/if}
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__process-btn" title="Mở form chỉnh sửa">
				<i class="fa fa-pencil" aria-hidden="true"></i>
				<span>Sửa đơn</span>
			</button>
			<a class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__dup-btn" href="#" data-record-id="{$RECORD->getId()}" title="Nhân bản đơn hàng">
				<i class="fa fa-copy" aria-hidden="true"></i>
				<span>Nhân bản</span>
			</a>
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
