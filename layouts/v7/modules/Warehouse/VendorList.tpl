{* Nhà cung cấp — list + expand kiểu Kiot *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt mk-purchase-page mk-kiot-vendor" id="mkVendorListRoot">
		<header class="mk-kiot-top">
			<div class="mk-kiot-top__left">
				<h1 class="mk-kiot-title">Nhà cung cấp</h1>
				<label class="mk-kiot-search">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
					<input type="search" id="mkVendorListQ" placeholder="Theo mã, tên, số điện thoại…" autocomplete="off" />
				</label>
			</div>
			<div class="mk-kiot-top__right">
				<a class="mk-kiot-btn mk-kiot-btn--primary" href="index.php?module=Vendors&amp;view=Edit&amp;app=INVENTORY">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
					Nhà cung cấp
				</a>
			</div>
		</header>

		<div class="mk-kiot-layout">
			<aside class="mk-kiot-filters">
				<div class="mk-kiot-filter-block">
					<p class="mk-kiot-filter-label">Nhóm nhà cung cấp</p>
					<select id="mkVendorGroupFilter">
						<option value="">Tất cả nhóm</option>
					</select>
				</div>
				<div class="mk-kiot-filter-block">
					<p class="mk-kiot-filter-label">Trạng thái</p>
					<div class="mk-purchase-filters" id="mkVendorStatusFilters">
						<button type="button" class="mk-purchase-filter is-active" data-active="all">Tất cả</button>
						<button type="button" class="mk-purchase-filter" data-active="1">Đang hoạt động</button>
					</div>
				</div>
			</aside>

			<div class="mk-kiot-main">
				<div class="mk-kiot-table-wrap">
					<table class="mk-kiot-table mk-kiot-table--vendor">
						<thead>
							<tr>
								<th>Mã nhà cung cấp</th>
								<th>Tên nhà cung cấp</th>
								<th>Điện thoại</th>
								<th>Email</th>
								<th class="is-num">Nợ cần trả hiện tại</th>
								<th class="is-num">Tổng mua</th>
							</tr>
							<tr class="mk-kiot-sum">
								<td colspan="4"></td>
								<td class="is-num" id="mkVendorDueSum">0</td>
								<td class="is-num" id="mkVendorTotalSum">0</td>
							</tr>
						</thead>
						<tbody id="mkVendorListBody">
							<tr><td colspan="6">Đang tải…</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</section>
</div>
{/strip}
