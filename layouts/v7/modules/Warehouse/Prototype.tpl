{* Warehouse Prototype — UI-only demo (no DB) *}
{strip}
<div class="mk-gi-page">
	<div class="mk-gi-suite-card mk-wh-proto-suite">
		<section class="mk-wh-proto">
			<header class="mk-wh-proto-head">
				<div class="mk-wh-proto-title">
					<h1 class="mk-wh-proto-title__h1">Prototype — Quản lý kho theo vai trò</h1>
					<p class="mk-wh-proto-title__sub">Mô phỏng luồng nghiệp vụ: Thủ kho → QC → Quản lý kho. Phiếu nhập, QC, tồn kho, phiếu xuất.</p>
				</div>
				<div class="mk-wh-proto-role">
					<div class="mk-wh-proto-role__label">Đang đăng nhập với vai trò</div>
					<div class="mk-wh-proto-role__control">
						<span class="mk-wh-proto-role__ic" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="currentColor" stroke-width="1.6"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
						</span>
						<select class="mk-wh-proto-select" id="mkWhProtoRole">
							<option value="qc">QC — QC Minh</option>
							<option value="stock">Thủ kho — Thủ kho Huy</option>
							<option value="manager">Quản lý kho — Manager</option>
						</select>
					</div>
				</div>
			</header>

			<div class="mk-wh-proto-perms" id="mkWhProtoPerms" role="status" aria-live="polite">
				<span class="mk-wh-proto-perms__pill" id="mkWhProtoPermRole">QC</span>
				<span class="mk-wh-proto-perms__label">Quyền:</span>
				<span class="mk-wh-proto-perms__items" id="mkWhProtoPermItems">Ghi nhận kết quả QC (Đạt/Không đạt) • Ghi chú kiểm tra</span>
			</div>

			<div class="mk-wh-proto-banner" role="status" id="mkWhProtoBanner">
				<span class="mk-wh-proto-badge" id="mkWhProtoRoleBadge">QC</span>
				<span class="mk-wh-proto-banner__text" id="mkWhProtoRoleHint">Quyền: Chỉnh sửa kết quả QC (đạt/không đạt) • Ghi chú kiểm tra</span>
			</div>

			<section class="mk-wh-proto-kpis" aria-label="Warehouse KPIs">
				<article class="mk-wh-proto-kpi">
					<div class="mk-wh-proto-kpi__label">
						<span class="mk-wh-proto-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.9"/></svg></span>
						Phiếu chờ QC
					</div>
					<div class="mk-wh-proto-kpi__value" id="mkWhKpiPendingQc">1</div>
				</article>
				<article class="mk-wh-proto-kpi">
					<div class="mk-wh-proto-kpi__label">
						<span class="mk-wh-proto-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 3v12" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20h16" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></span>
						Phiếu xuất chờ duyệt
					</div>
					<div class="mk-wh-proto-kpi__value" id="mkWhKpiPendingApprove">1</div>
				</article>
				<article class="mk-wh-proto-kpi">
					<div class="mk-wh-proto-kpi__label">
						<span class="mk-wh-proto-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" stroke="currentColor" stroke-width="1.7"/><path d="M3.3 7.7 12 12l8.7-4.3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 22V12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
						SKU đang lưu kho
					</div>
					<div class="mk-wh-proto-kpi__value" id="mkWhKpiSku">4</div>
				</article>
				<article class="mk-wh-proto-kpi mk-wh-proto-kpi--danger">
					<div class="mk-wh-proto-kpi__label">
						<span class="mk-wh-proto-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 9v4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M12 16.6h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/><path d="M10.3 4.2 2.7 18a2 2 0 0 0 1.8 3h15a2 2 0 0 0 1.8-3L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
						Lô sắp hết hạn (&lt;90 ngày)
					</div>
					<div class="mk-wh-proto-kpi__value" id="mkWhKpiExpiring">2</div>
				</article>
			</section>

			<nav class="mk-wh-proto-tabs" aria-label="Warehouse sections">
				<button type="button" class="mk-wh-proto-tab is-active" data-tab="inbound">
					<span class="mk-wh-proto-tab__ic" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 3v12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					</span>
					Nhập kho
				</button>
				<button type="button" class="mk-wh-proto-tab" data-tab="qc">
					<span class="mk-wh-proto-tab__ic" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.8"/></svg>
					</span>
					QC
				</button>
				<button type="button" class="mk-wh-proto-tab" data-tab="stock">
					<span class="mk-wh-proto-tab__ic" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" stroke="currentColor" stroke-width="1.6"/><path d="M3.3 7.7 12 12l8.7-4.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 22V12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
					</span>
					Tồn kho
				</button>
				<button type="button" class="mk-wh-proto-tab" data-tab="outbound">
					<span class="mk-wh-proto-tab__ic" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 21V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M17 14l-5-5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 4h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					</span>
					Xuất kho
				</button>
			</nav>

			<section class="mk-wh-proto-stage" aria-label="Luồng kho (prototype)">
				<header class="mk-wh-proto-stage__head">
					<h2 class="mk-wh-proto-stage__title" id="mkWhProtoStageTitle">Hàng đợi QC</h2>
					<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--primary" id="mkWhProtoCreateBtn">Tạo phiếu nhập</button>
				</header>

				<div class="mk-wh-proto-pane" id="mkWhProtoPaneInbound">
					<div class="mk-wh-proto-table-wrap">
						<table class="mk-wh-proto-table" role="table">
							<thead>
								<tr>
									<th>Mã phiếu</th>
									<th>NCC</th>
									<th>PO</th>
									<th>Ngày tạo</th>
									<th>Trạng thái</th>
									<th class="mk-wh-proto-td-right">Thao tác</th>
								</tr>
							</thead>
							<tbody id="mkWhProtoInboundTbody"></tbody>
						</table>
					</div>
				</div>

				<div class="mk-wh-proto-pane hide" id="mkWhProtoPaneQc">
					<div class="mk-wh-proto-table-wrap">
						<table class="mk-wh-proto-table" role="table">
							<thead>
								<tr>
									<th>Mã phiếu</th>
									<th>NCC</th>
									<th>Mặt hàng</th>
									<th>Lô</th>
									<th>HSD</th>
									<th>SL</th>
									<th class="mk-wh-proto-td-right">QC</th>
								</tr>
							</thead>
							<tbody id="mkWhProtoQcTbody"></tbody>
						</table>
					</div>
				</div>

				<div class="mk-wh-proto-pane hide" id="mkWhProtoPaneStock">
					<div class="mk-wh-proto-table-wrap">
						<table class="mk-wh-proto-table" role="table">
							<thead>
								<tr>
									<th>SKU</th>
									<th>Tên hàng</th>
									<th>Lô</th>
									<th>HSD</th>
									<th class="mk-wh-proto-td-right">Vị trí</th>
									<th class="mk-wh-proto-td-right">Tồn</th>
								</tr>
							</thead>
							<tbody id="mkWhProtoStockTbody"></tbody>
						</table>
					</div>
				</div>

				<div class="mk-wh-proto-pane hide" id="mkWhProtoPaneOutbound">
					<div class="mk-wh-proto-table-wrap">
						<table class="mk-wh-proto-table" role="table">
							<thead>
								<tr>
									<th>Mã phiếu</th>
									<th>Khách hàng</th>
									<th>SO</th>
									<th>Ngày tạo</th>
									<th>Trạng thái</th>
									<th class="mk-wh-proto-td-right">Thao tác</th>
								</tr>
							</thead>
							<tbody id="mkWhProtoOutboundTbody"></tbody>
						</table>
					</div>
				</div>
			</section>

			<div class="mk-wh-proto-modal" id="mkWhProtoModal" aria-hidden="true">
				<div class="mk-wh-proto-modal__backdrop" data-mk-close="1"></div>
				<div class="mk-wh-proto-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mkWhProtoModalTitle">
					<header class="mk-wh-proto-modal__head">
						<h3 class="mk-wh-proto-modal__title" id="mkWhProtoModalTitle">Tạo phiếu</h3>
						<button type="button" class="mk-wh-proto-modal__close" data-mk-close="1" aria-label="Đóng">×</button>
					</header>
					<form class="mk-wh-proto-modal__body" id="mkWhProtoModalForm">
						<div class="mk-wh-proto-form-grid" id="mkWhProtoFormFields"></div>
						<div class="mk-wh-proto-modal__foot">
							<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost" data-mk-close="1">Hủy</button>
							<button type="submit" class="mk-wh-proto-btn mk-wh-proto-btn--primary" id="mkWhProtoSubmitBtn">Tạo phiếu</button>
						</div>
					</form>
				</div>
			</div>

			<div class="mk-wh-proto-dialog" id="mkWhProtoDialog" aria-hidden="true">
				<div class="mk-wh-proto-dialog__backdrop" data-mk-dialog-close="1"></div>
				<div class="mk-wh-proto-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="mkWhProtoDialogTitle">
					<header class="mk-wh-proto-dialog__head">
						<div class="mk-wh-proto-dialog__title">
							<h3 id="mkWhProtoDialogTitle">Phiếu</h3>
							<div class="mk-wh-proto-dialog__meta" id="mkWhProtoDialogMeta"></div>
						</div>
						<button type="button" class="mk-wh-proto-dialog__close" data-mk-dialog-close="1" aria-label="Đóng">×</button>
					</header>
					<div class="mk-wh-proto-dialog__body" id="mkWhProtoDialogBody"></div>
					<footer class="mk-wh-proto-dialog__foot">
						<button type="button" class="mk-wh-proto-mini-btn" data-mk-dialog-close="1">Đóng</button>
					</footer>
				</div>
			</div>
		</section>
	</div>
</div>
{/strip}

