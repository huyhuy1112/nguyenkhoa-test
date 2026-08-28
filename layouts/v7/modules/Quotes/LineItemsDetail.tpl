{* Quotes Detail (SALES): bảng hàng gọn giống list inline — dễ đọc. *}
{strip}
{assign var=FINAL_DETAILS value=$RELATED_PRODUCTS.1.final_details}
{assign var=CURRENCY_INFO value=$RECORD->getCurrencyInfo()}
{if empty($CURRENCY_INFO) || !is_array($CURRENCY_INFO)}
	{assign var=CURRENCY_INFO value=['currency_name' => 'VND', 'currency_symbol' => 'đ']}
{/if}
<div class="details block mk-qt-lineitems-host">
	<div class="mk-qt-lines-detail">
		<div class="mk-qt-lines-detail__meta">
			<span class="mk-qt-lines-detail__meta-item"><strong>Chi tiết</strong></span>
			<span class="mk-qt-lines-detail__meta-item">
				{vtranslate('LBL_CURRENCY', $MODULE_NAME)}:
				{if !empty($CURRENCY_INFO.currency_name)}
					{vtranslate($CURRENCY_INFO.currency_name, $MODULE_NAME)}
				{else}
					VND
				{/if}
				{if !empty($CURRENCY_INFO.currency_symbol)}
					({$CURRENCY_INFO.currency_symbol})
				{/if}
			</span>
			{if !empty($FINAL_DETAILS.taxtype)}
				<span class="mk-qt-lines-detail__meta-item">{vtranslate('LBL_TAX_MODE', $MODULE_NAME)}: {vtranslate($FINAL_DETAILS.taxtype, $MODULE_NAME)}</span>
			{/if}
		</div>
		<div class="mk-qt-lines-detail__table-wrap">
			<table class="mk-qt-lines-detail__table">
				<thead>
					<tr>
						<th>SKU</th>
						<th>Tên hàng</th>
						<th class="is-num">Số lượng</th>
						<th class="is-num">Đơn giá</th>
						<th class="is-num">Thuế</th>
						<th class="is-num">Thành tiền</th>
					</tr>
				</thead>
				<tbody>
					{assign var=HAS_LINE_ITEMS value=false}
					{foreach key=INDEX item=LINE_ITEM_DETAIL from=$RELATED_PRODUCTS}
						{if $INDEX > 0 && $LINE_ITEM_DETAIL["hdnProductId$INDEX"]|default:'' neq ''}
							{assign var=HAS_LINE_ITEMS value=true}
							<tr>
								<td class="is-code">
									{if !empty($LINE_ITEM_DETAIL["lineSku$INDEX"])}
										{$LINE_ITEM_DETAIL["lineSku$INDEX"]}
									{elseif !empty($LINE_ITEM_DETAIL["hdnProductcode$INDEX"])}
										{$LINE_ITEM_DETAIL["hdnProductcode$INDEX"]}
									{else}
										--
									{/if}
								</td>
								<td class="is-name">
									{if !empty($LINE_ITEM_DETAIL["productDeleted$INDEX"])}
										{$LINE_ITEM_DETAIL["productName$INDEX"]}
									{else}
										<a href="index.php?module={$LINE_ITEM_DETAIL["entityType$INDEX"]}&view=Detail&record={$LINE_ITEM_DETAIL["hdnProductId$INDEX"]}" target="_blank" rel="noopener">
											{$LINE_ITEM_DETAIL["productName$INDEX"]}
										</a>
									{/if}
								</td>
								<td class="is-num">{$LINE_ITEM_DETAIL["qty$INDEX"]|default:'0'}</td>
								<td class="is-num">{$LINE_ITEM_DETAIL["listPrice$INDEX"]|default:'0'}</td>
								<td class="is-num">{$LINE_ITEM_DETAIL["taxTotal$INDEX"]|default:'0'}</td>
								<td class="is-num is-total">{$LINE_ITEM_DETAIL["netPrice$INDEX"]|default:$LINE_ITEM_DETAIL["productTotal$INDEX"]|default:'0'}</td>
							</tr>
						{/if}
					{/foreach}
					{if !$HAS_LINE_ITEMS}
						<tr>
							<td colspan="6" class="mk-qt-lines-detail__empty">Chưa có hàng hóa trong báo giá.</td>
						</tr>
					{/if}
				</tbody>
			</table>
		</div>
		<div class="mk-qt-lines-detail__totals">
			<div class="mk-qt-lines-detail__total-row">
				<span>Tổng tiền hàng</span>
				<strong>{$FINAL_DETAILS.hdnSubTotal|default:'0'}</strong>
			</div>
			<div class="mk-qt-lines-detail__total-row">
				<span>Thuế</span>
				<strong>{$FINAL_DETAILS.tax_totalamount|default:'0'}</strong>
			</div>
			<div class="mk-qt-lines-detail__total-row mk-qt-lines-detail__total-row--grand">
				<span>Tổng cộng</span>
				<strong>{$FINAL_DETAILS.grandTotal|default:'0'}</strong>
			</div>
		</div>
	</div>
</div>
{/strip}
