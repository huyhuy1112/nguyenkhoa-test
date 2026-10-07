{* Nhập hàng — list kiểu Kiot (không gọi Lịch sử) *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt mk-purchase-page mk-purchase-page--history mk-kiot-inbound" id="mkPurchaseHistoryRoot">
		<header class="mk-kiot-top">
			<div class="mk-kiot-top__left">
				<h1 class="mk-kiot-title">Nhập hàng</h1>
				<label class="mk-kiot-search">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
					<input type="search" id="mkPurchaseHistQ" placeholder="Theo mã phiếu nhập, NCC…" autocomplete="off" />
				</label>
			</div>
			<div class="mk-kiot-top__right">
				<a class="mk-kiot-btn mk-kiot-btn--primary" href="index.php?module=Warehouse&amp;view=PurchaseCreate&amp;app=INVENTORY">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
					Nhập hàng
				</a>
			</div>
		</header>

		<div class="mk-kiot-layout">
			<aside class="mk-kiot-filters" id="mkPurchaseHistFilters">
				<div class="mk-kiot-filter-block">
					<p class="mk-kiot-filter-label">Kho hàng</p>
					<select id="mkPurchaseHistWh">
						<option value="">Tất cả kho</option>
					</select>
				</div>
				<div class="mk-kiot-filter-block">
					<p class="mk-kiot-filter-label">Trạng thái</p>
					<label class="mk-kiot-check"><input type="checkbox" data-status="draft" checked /> Phiếu tạm</label>
					<label class="mk-kiot-check"><input type="checkbox" data-status="pending_qc" checked /> Chờ QC</label>
					<label class="mk-kiot-check"><input type="checkbox" data-status="stored" checked /> Đã nhập hàng</label>
				</div>
			</aside>

			<div class="mk-kiot-main">
				<div class="mk-kiot-table-wrap">
					<table class="mk-kiot-table" id="mkPurchaseHistTable">
						<thead>
							<tr>
								<th>Mã nhập hàng</th>
								<th>Tham chiếu</th>
								<th>Thời gian</th>
								<th>Nhà cung cấp</th>
								<th>Kho</th>
								<th class="is-num">Cần trả NCC</th>
								<th>Trạng thái</th>
							</tr>
							<tr class="mk-kiot-sum">
								<td colspan="5"></td>
								<td class="is-num" id="mkPurchaseHistDueSum">0</td>
								<td></td>
							</tr>
						</thead>
						<tbody id="mkPurchaseHistBody">
							<tr><td colspan="7">Đang tải…</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="mk-purchase-toast" id="mkPurchaseHistMsg" role="status" hidden></div>
	</section>
</div>
{/strip}
