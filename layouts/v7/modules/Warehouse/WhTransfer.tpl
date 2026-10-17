{* Phiếu xuất chuyển kho đã giao — quản lý kho xác nhận tại đây *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt">
		<header class="mk-wh-proto-head">
			<div class="mk-wh-proto-title">
				<div class="mk-wh-mgmt-breadcrumb">
					<a href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">← Danh sách kho</a>
				</div>
				<h1 class="mk-wh-proto-title__h1">Chuyển kho giữa các kho</h1>
				<p class="mk-wh-proto-title__sub">Phiếu xuất loại Chuyển kho khi đã giao sẽ hiện ở đây. Quản lý kho xác nhận khi hàng đã chuyển xong.</p>
			</div>
			<div class="mk-wh-mgmt-toolbar">
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">Danh sách kho</a>
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhDashboard&amp;app=INVENTORY">Bảng điều khiển kho</a>
			</div>
		</header>

		<div class="mk-wh-mgmt-panel" aria-label="Phiếu chuyển kho">
			<div class="mk-wh-mgmt-panel__head">
				<h2 class="mk-wh-mgmt-panel__title">Phiếu chuyển kho đã giao</h2>
			</div>
			<div class="mk-wh-mgmt-table-wrap">
				<table class="mk-wh-mgmt-table" role="table">
					<thead>
						<tr>
							<th>Mã phiếu</th>
							<th>Từ kho</th>
							<th></th>
							<th>Đến kho</th>
							<th>Mã chuyển kho</th>
							<th>Hàng</th>
							<th class="mk-wh-mgmt-td-right">SL</th>
							<th>Ngày tạo</th>
							<th>Trạng thái</th>
							<th class="mk-wh-mgmt-td-right">Thao tác</th>
						</tr>
					</thead>
					<tbody id="mkWhTransferTableBody"></tbody>
				</table>
			</div>
		</div>
	</section>
</div>
{/strip}
