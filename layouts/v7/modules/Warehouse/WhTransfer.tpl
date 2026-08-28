{* Chuyển kho giữa các kho — localStorage prototype *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt">
		<header class="mk-wh-proto-head">
			<div class="mk-wh-proto-title">
				<div class="mk-wh-mgmt-breadcrumb">
					<a href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">← Danh sách kho</a>
				</div>
				<h1 class="mk-wh-proto-title__h1">Chuyển kho giữa các kho</h1>
				<p class="mk-wh-proto-title__sub">Yêu cầu, duyệt và theo dõi luồng hàng giữa các kho. Dữ liệu lưu cache trình duyệt.</p>
			</div>
			<div class="mk-wh-mgmt-toolbar">
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhDashboard&amp;app=INVENTORY">Dashboard tổng</a>
				<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--primary" id="mkWhTransferCreateBtn">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					Tạo yêu cầu
				</button>
			</div>
		</header>

		<div class="mk-wh-mgmt-panel" aria-label="Phiếu chuyển kho">
			<div class="mk-wh-mgmt-panel__head">
				<h2 class="mk-wh-mgmt-panel__title">Phiếu chuyển kho</h2>
			</div>
			<div class="mk-wh-mgmt-table-wrap">
				<table class="mk-wh-mgmt-table" role="table">
					<thead>
						<tr>
							<th>Mã</th>
							<th>Từ kho</th>
							<th></th>
							<th>Đến kho</th>
							<th>SKU / Lô</th>
							<th class="mk-wh-mgmt-td-right">SL</th>
							<th>Lý do</th>
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

<div class="mk-wh-mgmt-modal hide" id="mkWhTransferFormModal" role="dialog" aria-modal="true" aria-labelledby="mkWhTransferFormTitle">
	<div class="mk-wh-mgmt-modal__backdrop" data-mk-wh-trf-close="1"></div>
	<div class="mk-wh-mgmt-modal__panel">
		<header class="mk-wh-mgmt-modal__head">
			<h3 id="mkWhTransferFormTitle">Tạo phiếu chuyển kho</h3>
			<button type="button" class="mk-wh-mgmt-modal__close" data-mk-wh-trf-close="1" aria-label="Đóng">&times;</button>
		</header>
		<form id="mkWhTransferForm" class="mk-wh-mgmt-form">
			<div class="mk-wh-mgmt-form-grid">
				<label class="mk-wh-mgmt-field"><span>Từ kho *</span>
					<select name="from" id="mkWhTrfFrom" required><option value="">Chọn kho nguồn</option></select>
				</label>
				<label class="mk-wh-mgmt-field"><span>Đến kho *</span>
					<select name="to" id="mkWhTrfTo" required><option value="">Chọn kho đích</option></select>
				</label>
				<label class="mk-wh-mgmt-field mk-wh-mgmt-field--full"><span>SKU / Lô *</span>
					<select name="lotKey" id="mkWhTrfLot" required disabled="disabled"><option value="">Chọn kho nguồn trước</option></select>
				</label>
				<label class="mk-wh-mgmt-field mk-wh-mgmt-field--full"><span>Số lượng *</span><input type="number" name="qty" id="mkWhTrfQty" min="1" required /></label>
				<label class="mk-wh-mgmt-field mk-wh-mgmt-field--full"><span>Lý do</span><textarea name="reason" id="mkWhTrfReason" rows="2" placeholder="VD: cân đối tồn giữa các kho"></textarea></label>
			</div>
			<footer class="mk-wh-mgmt-modal__foot">
				<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" data-mk-wh-trf-close="1">Hủy</button>
				<button type="submit" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--primary">Tạo phiếu</button>
			</footer>
		</form>
	</div>
</div>
{/strip}
