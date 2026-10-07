{* Lịch sử phiếu mua hàng — UI hiện đại *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt mk-purchase-page mk-purchase-page--history" id="mkPurchaseHistoryRoot">
		<header class="mk-purchase-hero">
			<div class="mk-purchase-hero__copy">
				<p class="mk-purchase-eyebrow">
					<span class="mk-purchase-eyebrow__dot" aria-hidden="true"></span>
					Mua hàng · Lịch sử
				</p>
				<h1 class="mk-purchase-hero__title">Lịch sử phiếu MH</h1>
				<p class="mk-purchase-hero__sub">Theo dõi phiếu lưu tạm, chờ QC và đã nhập kho. Hoàn thành phiếu tạm để cộng tồn.</p>
			</div>
			<div class="mk-purchase-hero__actions">
				<a class="mk-purchase-btn mk-purchase-btn--primary" href="index.php?module=Warehouse&amp;view=PurchaseCreate&amp;app=INVENTORY">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
					Phiếu MH mới
				</a>
				<a class="mk-purchase-btn mk-purchase-btn--ghost" href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">Kho CRM</a>
			</div>
		</header>

		<div class="mk-purchase-kpi" id="mkPurchaseHistKpi" aria-label="Tổng quan phiếu mua">
			<article class="mk-purchase-kpi__card">
				<span class="mk-purchase-kpi__label">Tất cả</span>
				<strong class="mk-purchase-kpi__value" data-kpi="all">0</strong>
			</article>
			<article class="mk-purchase-kpi__card mk-purchase-kpi__card--draft">
				<span class="mk-purchase-kpi__label">Lưu tạm</span>
				<strong class="mk-purchase-kpi__value" data-kpi="draft">0</strong>
			</article>
			<article class="mk-purchase-kpi__card mk-purchase-kpi__card--qc">
				<span class="mk-purchase-kpi__label">Chờ QC</span>
				<strong class="mk-purchase-kpi__value" data-kpi="pending_qc">0</strong>
			</article>
			<article class="mk-purchase-kpi__card mk-purchase-kpi__card--ok">
				<span class="mk-purchase-kpi__label">Đã nhập kho</span>
				<strong class="mk-purchase-kpi__value" data-kpi="stored">0</strong>
			</article>
		</div>

		<div class="mk-purchase-toolbar">
			<label class="mk-purchase-search mk-purchase-search--lg">
				<svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
				<input type="search" id="mkPurchaseHistQ" placeholder="Tìm mã phiếu, NCC, tham chiếu…" autocomplete="off" />
			</label>
			<div class="mk-purchase-filters" id="mkPurchaseHistFilters" role="group" aria-label="Lọc trạng thái">
				<button type="button" class="mk-purchase-filter is-active" data-status="all">Tất cả</button>
				<button type="button" class="mk-purchase-filter" data-status="draft">Lưu tạm</button>
				<button type="button" class="mk-purchase-filter" data-status="pending_qc">Chờ QC</button>
				<button type="button" class="mk-purchase-filter" data-status="stored">Đã nhập kho</button>
			</div>
		</div>

		<div class="mk-purchase-card mk-purchase-card--table">
			<div class="mk-purchase-table-wrap">
				<table class="mk-purchase-table mk-purchase-table--history">
					<thead>
						<tr>
							<th>Mã phiếu</th>
							<th>Tham chiếu</th>
							<th>NCC</th>
							<th>Kho</th>
							<th>Trạng thái</th>
							<th class="is-num">Dòng</th>
							<th class="is-num">Tiền hàng</th>
							<th>Ngày tạo</th>
							<th class="is-action">Thao tác</th>
						</tr>
					</thead>
					<tbody id="mkPurchaseHistBody">
						<tr class="mk-purchase-empty-row">
							<td colspan="9">
								<div class="mk-purchase-empty">
									<p class="mk-purchase-empty__title">Đang tải…</p>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="mk-purchase-toast" id="mkPurchaseHistMsg" role="status" hidden></div>
	</section>
</div>
{/strip}
