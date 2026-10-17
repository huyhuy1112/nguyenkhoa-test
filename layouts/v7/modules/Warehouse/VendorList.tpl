{* Nhà cung cấp — list + expand kiểu Kiot *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt mk-purchase-page mk-kiot-vendor" id="mkVendorListRoot">
		<header class="mk-kiot-top">
			<div class="mk-kiot-top__left">
				<h1 class="mk-kiot-title">Danh mục nhà cung cấp</h1>
				<label class="mk-kiot-search">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
					<input type="search" id="mkVendorListQ" placeholder="Theo mã, tên, số điện thoại…" autocomplete="off" />
				</label>
			</div>
			<div class="mk-kiot-top__right">
				<button type="button" class="mk-kiot-btn" id="mkVendorImportBtn">Import</button>
				<input type="file" id="mkVendorImportFile" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hide" />
				<button type="button" class="mk-kiot-btn mk-kiot-btn--primary" id="mkVendorCreateBtn">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
					Nhà cung cấp
				</button>
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

	<div class="mk-vendor-modal" id="mkVendorModal" hidden>
		<div class="mk-vendor-modal__backdrop" data-mk-vendor-close="1"></div>
		<form class="mk-vendor-modal__card" id="mkVendorForm">
			<div class="mk-vendor-modal__head">
				<h2 id="mkVendorModalTitle">Tạo nhà cung cấp</h2>
				<button type="button" class="mk-vendor-modal__x" data-mk-vendor-close="1" aria-label="Đóng">&times;</button>
			</div>
			<div class="mk-vendor-modal__body">
				<input type="hidden" name="id" value="" />
				<div class="mk-vendor-grid">
					<label>Tên nhà cung cấp<input name="name" required placeholder="Bắt buộc" /></label>
					<label>Mã nhà cung cấp<input name="code" placeholder="Tự động" /></label>
					<label>Điện thoại<input name="phone" /></label>
					<label>Email<input name="email" type="email" placeholder="email@gmail.com" /></label>
				</div>
				<details class="mk-vendor-block" open>
					<summary>Địa chỉ</summary>
					<label>Địa chỉ<input name="street" placeholder="Nhập địa chỉ" /></label>
					<div class="mk-vendor-grid">
						<label>Khu vực<input name="state" placeholder="Tỉnh/Thành phố" /></label>
						<label>Phường/Xã<input name="city" placeholder="Phường/Xã" /></label>
					</div>
				</details>
				<details class="mk-vendor-block">
					<summary>Nhóm nhà cung cấp, ghi chú</summary>
					<label>Nhóm nhà cung cấp<input name="category" placeholder="Nhóm" /></label>
					<label>Ghi chú<textarea name="note" rows="2"></textarea></label>
				</details>
				<details class="mk-vendor-block" open>
					<summary>Thông tin xuất hóa đơn</summary>
					<div class="mk-vendor-grid">
						<label>Tên công ty<input name="company" placeholder="Nhập tên công ty" /></label>
						<label>Mã số thuế<input name="tax" placeholder="Nhập mã số thuế" /></label>
					</div>
					<label>Số CCCD/CMND<input name="identity" placeholder="Nhập số CCCD/CMND" /></label>
				</details>
			</div>
			<div class="mk-vendor-modal__foot">
				<button type="button" class="mk-kiot-btn" data-mk-vendor-close="1">Bỏ qua</button>
				<button type="submit" class="mk-kiot-btn mk-kiot-btn--primary">Lưu</button>
			</div>
		</form>
	</div>
</div>
{/strip}
