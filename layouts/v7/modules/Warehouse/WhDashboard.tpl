{* Dashboard kho — tổng hợp đa kho (localStorage prototype) *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt">
		<header class="mk-wh-proto-head">
			<div class="mk-wh-proto-title">
				<div class="mk-wh-mgmt-breadcrumb">
					<a href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">← Danh sách kho</a>
				</div>
				<h1 class="mk-wh-proto-title__h1">Dashboard tất cả kho</h1>
				<p class="mk-wh-proto-title__sub">Tổng hợp tồn kho, hàng chờ QC, phiếu xuất chờ duyệt và lô sắp hết hạn.</p>
			</div>
			<div class="mk-wh-mgmt-toolbar">
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">Danh sách kho</a>
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhTransfer&amp;app=INVENTORY">Chuyển kho</a>
			</div>
		</header>

		<section class="mk-wh-mgmt-kpis" aria-label="KPI đa kho" id="mkWhDashKpis"></section>

		<div class="mk-wh-mgmt-panel" aria-label="Phân tích theo kho">
			<div class="mk-wh-mgmt-panel__head">
				<h2 class="mk-wh-mgmt-panel__title">Phân tích theo kho</h2>
			</div>
			<div class="mk-wh-mgmt-table-wrap">
				<table class="mk-wh-mgmt-table" role="table">
					<thead>
						<tr>
							<th>Kho</th>
							<th>Quản lý</th>
							<th class="mk-wh-mgmt-td-right">SKU</th>
							<th class="mk-wh-mgmt-td-right">Tồn</th>
							<th class="mk-wh-mgmt-td-right">Chờ QC</th>
							<th class="mk-wh-mgmt-td-right">Xuất chờ duyệt</th>
							<th class="mk-wh-mgmt-td-right">Sắp hết hạn</th>
							<th>Trạng thái</th>
							<th class="mk-wh-mgmt-td-right"></th>
						</tr>
					</thead>
					<tbody id="mkWhDashTableBody"></tbody>
				</table>
			</div>
		</div>
	</section>
</div>
{/strip}
