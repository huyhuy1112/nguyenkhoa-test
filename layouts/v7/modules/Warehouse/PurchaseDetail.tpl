{* Chi tiết phiếu nhập hàng — kiểu Kiot PN *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt mk-purchase-page mk-kiot-detail" id="mkPurchaseDetailRoot"
		data-code="{$MK_PURCHASE_CODE|escape:'html'}"
		data-wh="{$MK_PURCHASE_WH|escape:'html'}">
		<div class="mk-kiot-detail-crumb">
			<a href="index.php?module=Warehouse&amp;view=PurchaseHistory&amp;app=INVENTORY">← Nhập hàng</a>
		</div>
		<header class="mk-kiot-detail-head">
			<div>
				<div class="mk-kiot-detail-title-row">
					<h1 class="mk-kiot-title" id="mkPurchaseDetailCode">—</h1>
					<span class="mk-purchase-badge" id="mkPurchaseDetailStatus">—</span>
				</div>
				<p class="mk-kiot-detail-meta" id="mkPurchaseDetailMeta"></p>
			</div>
			<div class="mk-kiot-detail-wh" id="mkPurchaseDetailWh"></div>
		</header>

		<div class="mk-kiot-detail-card">
			<div class="mk-kiot-detail-info" id="mkPurchaseDetailInfo"></div>
			<div class="mk-kiot-table-wrap">
				<table class="mk-kiot-table">
					<thead>
						<tr>
							<th>Mã hàng</th>
							<th>Tên hàng</th>
							<th class="is-num">Số lượng</th>
							<th class="is-num">Giá nhập</th>
							<th class="is-num">Thành tiền</th>
						</tr>
					</thead>
					<tbody id="mkPurchaseDetailLines">
						<tr><td colspan="5">Đang tải…</td></tr>
					</tbody>
				</table>
			</div>
			<footer class="mk-kiot-detail-foot">
				<div class="mk-kiot-detail-note" id="mkPurchaseDetailNote"></div>
				<div class="mk-kiot-detail-totals" id="mkPurchaseDetailTotals"></div>
			</footer>
			<div class="mk-kiot-detail-actions" id="mkPurchaseDetailActions"></div>
		</div>
		<div class="mk-purchase-toast" id="mkPurchaseDetailMsg" role="status" hidden></div>
	</section>
</div>
{/strip}
