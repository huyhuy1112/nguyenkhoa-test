{* Danh sách kho — multi-warehouse (database) — UI v2 *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt mk-wh-list-v2">
		<header class="mk-wh-proto-head mk-wh-list-v2__head">
			<div class="mk-wh-proto-title">
				<p class="mk-wh-list-v2__eyebrow">Kho · Inventory</p>
				<h1 class="mk-wh-proto-title__h1">Danh sách kho</h1>
				<p class="mk-wh-proto-title__sub">Theo dõi tồn, hạn dùng và cảnh báo hết hàng trên mọi kho — mở kho để nhập / xuất / QC.</p>
			</div>
			<div class="mk-wh-mgmt-toolbar">
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhDashboard&amp;app=INVENTORY">Bảng điều khiển kho</a>
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhTransfer&amp;app=INVENTORY">Chuyển kho</a>
				<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--primary" id="mkWhMgmtCreateBtn">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					Tạo kho mới
				</button>
			</div>
		</header>

		<div class="mk-wh-list-kpi" id="mkWhListKpi" aria-label="Tổng quan kho"></div>

		<div class="mk-wh-list-tools">
			<label class="mk-wh-list-search">
				<svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
				<input type="search" id="mkWhListSearch" placeholder="Tìm mã, tên kho, địa chỉ, quản lý…" autocomplete="off" />
			</label>
			<div class="mk-wh-list-filters" id="mkWhListFilters" role="group" aria-label="Lọc trạng thái">
				<button type="button" class="mk-wh-list-filter is-active" data-mk-wh-filter="all">Tất cả</button>
				<button type="button" class="mk-wh-list-filter" data-mk-wh-filter="active">Hoạt động</button>
				<button type="button" class="mk-wh-list-filter" data-mk-wh-filter="inactive">Tạm dừng</button>
				<button type="button" class="mk-wh-list-filter" data-mk-wh-filter="archived">Lưu trữ</button>
			</div>
		</div>

		<div class="mk-wh-mgmt-cards" id="mkWhMgmtCardGrid" aria-label="Danh sách kho dạng thẻ"></div>

		<div class="mk-wh-mgmt-panel mk-wh-list-panel" aria-label="Bảng kho">
			<div class="mk-wh-mgmt-panel__head">
				<div>
					<h2 class="mk-wh-mgmt-panel__title">Bảng tổng hợp</h2>
					<p class="mk-wh-list-panel__hint" id="mkWhListTableHint">Đang tải…</p>
				</div>
			</div>
			<div class="mk-wh-mgmt-table-wrap">
				<table class="mk-wh-mgmt-table" role="table">
					<thead>
						<tr>
							<th>Mã</th>
							<th>Tên kho</th>
							<th>Loại</th>
							<th>Địa chỉ</th>
							<th>Quản lý</th>
							<th class="mk-wh-mgmt-td-right">SKU</th>
							<th class="mk-wh-mgmt-td-right">Tồn</th>
							<th class="mk-wh-mgmt-td-right">HSD</th>
							<th class="mk-wh-mgmt-td-right">Hết hàng</th>
							<th>Trạng thái</th>
							<th>Ngày tạo</th>
							<th class="mk-wh-mgmt-td-right">Thao tác</th>
						</tr>
					</thead>
					<tbody id="mkWhMgmtTableBody"></tbody>
				</table>
			</div>
		</div>
	</section>
</div>

<div class="mk-wh-mgmt-modal hide" id="mkWhMgmtFormModal" role="dialog" aria-modal="true" aria-labelledby="mkWhMgmtFormTitle">
	<div class="mk-wh-mgmt-modal__backdrop" data-mk-wh-close="1"></div>
	<div class="mk-wh-mgmt-modal__panel">
		<header class="mk-wh-mgmt-modal__head">
			<h3 id="mkWhMgmtFormTitle">Tạo kho mới</h3>
			<button type="button" class="mk-wh-mgmt-modal__close" data-mk-wh-close="1" aria-label="Đóng">&times;</button>
		</header>
		<form id="mkWhMgmtForm" class="mk-wh-mgmt-form">
			<input type="hidden" name="editId" id="mkWhMgmtEditId" value="" />
			<div class="mk-wh-mgmt-form-grid">
				<label class="mk-wh-mgmt-field"><span>Mã kho *</span><input type="text" name="code" id="mkWhMgmtCode" placeholder="WH-004" required /></label>
				<label class="mk-wh-mgmt-field"><span>Tên kho *</span><input type="text" name="name" id="mkWhMgmtName" placeholder="Kho Đà Nẵng" required /></label>
				<label class="mk-wh-mgmt-field"><span>Loại kho</span>
					<select name="type" id="mkWhMgmtType">
						<option value="central">Kho trung tâm</option>
						<option value="branch" selected="selected">Kho chi nhánh</option>
						<option value="transit">Kho trung chuyển</option>
						<option value="cold">Kho lạnh</option>
					</select>
				</label>
				<label class="mk-wh-mgmt-field"><span>Trạng thái</span>
					<select name="status" id="mkWhMgmtStatus">
						<option value="active" selected="selected">Hoạt động</option>
						<option value="inactive">Tạm dừng</option>
						<option value="archived">Lưu trữ</option>
					</select>
				</label>
				<label class="mk-wh-mgmt-field mk-wh-mgmt-field--full"><span>Địa chỉ</span><input type="text" name="address" id="mkWhMgmtAddress" /></label>
				<label class="mk-wh-mgmt-field mk-wh-mgmt-field--full"><span>Quản lý kho</span><input type="text" name="manager" id="mkWhMgmtManager" /></label>
			</div>
			<footer class="mk-wh-mgmt-modal__foot">
				<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" data-mk-wh-close="1">Hủy</button>
				<button type="submit" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--primary" id="mkWhMgmtFormSubmit">Tạo</button>
			</footer>
		</form>
	</div>
</div>
{/strip}
