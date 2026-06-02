{* Warehouse Prototype Detail — UI-only demo (no DB) *}
{strip}
<div class="mk-gi-page">
	<div class="mk-gi-suite-card mk-wh-proto-suite">
		<section class="mk-wh-proto">
			<header class="mk-wh-proto-head">
				<div class="mk-wh-proto-title">
					<p class="mk-wh-proto-title__sub" style="margin:0 0 8px;">
						<a class="mk-wh-proto-link" href="index.php?module=Warehouse&amp;view=Prototype&amp;app=INVENTORY">← Quay lại Prototype kho</a>
					</p>
					<h1 class="mk-wh-proto-title__h1">Chi tiết phiếu — {$MK_PROTO_CODE|escape:'html'}</h1>
					<p class="mk-wh-proto-title__sub">Trang detail prototype để demo cho backend: thông tin chung, danh sách dòng hàng, trạng thái QC và lịch sử thao tác.</p>
				</div>
				<div class="mk-wh-proto-role">
					<div class="mk-wh-proto-role__label">Ngữ cảnh</div>
					<div class="mk-wh-proto-role__control">
						<span class="mk-wh-proto-role__ic" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="1.6"/><path d="M8 8h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 12h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
						</span>
						<select class="mk-wh-proto-select" disabled="disabled">
							<option>{if $MK_PROTO_TAB}{$MK_PROTO_TAB|escape:'html'}{else}inbound{/if}</option>
						</select>
					</div>
				</div>
			</header>

			<section class="mk-wh-proto-card" aria-label="Tổng quan">
				<header class="mk-wh-proto-card__head">
					<h2 class="mk-wh-proto-card__title">Thông tin chung</h2>
					<div style="display:flex;gap:10px;align-items:center;">
						<a class="mk-wh-proto-btn mk-wh-proto-btn--ghost" href="index.php?module=GoodsReceipt&amp;view=Edit&amp;app=INVENTORY">Mở form thật (Vtiger)</a>
						<button type="button" class="mk-wh-proto-btn">Duyệt / QC (demo)</button>
					</div>
				</header>
				<div class="mk-wh-proto-form-grid">
					<div class="mk-wh-proto-field">
						<div class="mk-wh-proto-field__label">Mã phiếu</div>
						<div class="mk-wh-proto-field__value">{$MK_PROTO_CODE|escape:'html'}</div>
					</div>
					<div class="mk-wh-proto-field">
						<div class="mk-wh-proto-field__label">Nhà cung cấp</div>
						<div class="mk-wh-proto-field__value">Vinamilk Logistics</div>
					</div>
					<div class="mk-wh-proto-field">
						<div class="mk-wh-proto-field__label">PO</div>
						<div class="mk-wh-proto-field__value">PO-2026-0151</div>
					</div>
					<div class="mk-wh-proto-field">
						<div class="mk-wh-proto-field__label">Trạng thái</div>
						<div class="mk-wh-proto-field__value"><span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Chờ QC</span></div>
					</div>
				</div>
			</section>

			<section class="mk-wh-proto-card" aria-label="Danh sách dòng hàng">
				<header class="mk-wh-proto-card__head">
					<h2 class="mk-wh-proto-card__title">Dòng hàng</h2>
					<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost">Xuất file (demo)</button>
				</header>
				<div class="mk-wh-proto-table-wrap">
					<table class="mk-wh-proto-table" role="table">
						<thead>
							<tr>
								<th>SKU</th>
								<th>Tên hàng</th>
								<th class="mk-wh-proto-td-right">SL</th>
								<th>HSD</th>
								<th>QC</th>
								<th class="mk-wh-proto-td-right">Thao tác</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><span class="mk-gi-chip">SKU-00021</span></td>
								<td>Vitamin C 500mg</td>
								<td class="mk-wh-proto-td-right">100</td>
								<td>2026-09-10</td>
								<td><span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Chờ QC</span></td>
								<td class="mk-wh-proto-td-right"><a class="mk-wh-proto-link" href="javascript:void(0)">Cập nhật QC</a></td>
							</tr>
							<tr>
								<td><span class="mk-gi-chip">SKU-00008</span></td>
								<td>Sữa tươi 1L</td>
								<td class="mk-wh-proto-td-right">40</td>
								<td>2026-08-02</td>
								<td><span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đạt</span></td>
								<td class="mk-wh-proto-td-right"><a class="mk-wh-proto-link" href="javascript:void(0)">Xem</a></td>
							</tr>
						</tbody>
					</table>
				</div>
			</section>
		</section>
	</div>
</div>
{/strip}

