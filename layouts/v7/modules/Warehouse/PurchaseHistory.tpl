{* Nhập hàng — list + panel phải chi tiết *}
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

		<div class="mk-kiot-layout" id="mkPurchaseHistLayout">
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

			<aside class="mk-kiot-sidepanel" id="mkPurchaseSidePanel" hidden aria-label="Chi tiết phiếu nhập">
				<header class="mk-kiot-sidepanel__head">
					<div class="mk-kiot-sidepanel__title-row">
						<h2 class="mk-kiot-sidepanel__title" id="mkPurchasePanelCode">—</h2>
						<span class="mk-purchase-badge" id="mkPurchasePanelStatus">—</span>
					</div>
					<button type="button" class="mk-kiot-sidepanel__close" id="mkPurchasePanelClose" aria-label="Đóng">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					</button>
				</header>
				<p class="mk-kiot-sidepanel__meta" id="mkPurchasePanelMeta"></p>
				<div class="mk-kiot-sidepanel__wh" id="mkPurchasePanelWh"></div>
				<div class="mk-kiot-sidepanel__info" id="mkPurchasePanelInfo"></div>
				<div class="mk-kiot-sidepanel__table-wrap">
					<table class="mk-kiot-table mk-kiot-table--compact">
						<thead>
							<tr>
								<th>Mã hàng</th>
								<th>Tên hàng</th>
								<th class="is-num">SL</th>
								<th class="is-num">Giá</th>
								<th class="is-num">Tiền</th>
							</tr>
						</thead>
						<tbody id="mkPurchasePanelLines">
							<tr><td colspan="5">Chọn một phiếu để xem chi tiết.</td></tr>
						</tbody>
					</table>
				</div>
				<footer class="mk-kiot-sidepanel__foot">
					<div class="mk-kiot-sidepanel__note" id="mkPurchasePanelNote"></div>
					<div class="mk-kiot-sidepanel__totals" id="mkPurchasePanelTotals"></div>
				</footer>
				<div class="mk-kiot-sidepanel__actions" id="mkPurchasePanelActions"></div>
			</aside>
		</div>
		<div class="mk-purchase-toast" id="mkPurchaseHistMsg" role="status" hidden></div>
	</section>
</div>
{/strip}
