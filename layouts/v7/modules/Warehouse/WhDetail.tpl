{* Chi tiết kho — nhập/xuất/QC/tồn theo warehouse_id (database) *}
{strip}
<div class="mk-gi-page">
	<div class="mk-gi-suite-card mk-wh-proto-suite" id="mkWhPrototypeRoot">
	<section class="mk-wh-proto" id="mkWhDetailRoot"
		data-wh-id="{$MK_WH_ID|escape:'html'}"
		data-can-write="{$MK_WH_CAN_WRITE|default:0}"
		data-can-qc="{$MK_WH_CAN_QC|default:0}"
		data-user-name="{$MK_WH_USER_NAME|escape:'html'}">
		<header class="mk-wh-proto-head">
			<div class="mk-wh-proto-title">
				<div class="mk-wh-mgmt-breadcrumb">
					<a href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">← Danh sách kho</a>
				</div>
				<h1 class="mk-wh-proto-title__h1" id="mkWhDetailTitle">Kho</h1>
				<p class="mk-wh-proto-title__sub" id="mkWhDetailDesc"></p>
			</div>
				<div class="mk-wh-proto-actions">
					<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--history" id="mkWhAuditHistoryBtn" title="Xem lịch sử tạo / chỉnh sửa phiếu nhập &amp; xuất">
						<span class="mk-wh-proto-btn__ic" aria-hidden="true">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.5 12a8.5 8.5 0 1 0 2.2-5.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M3.5 4.5v4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</span>
						Lịch sử chỉnh sửa
					</button>
				</div>
			</header>

		<section class="mk-wh-proto-kpis" aria-label="Warehouse KPIs">
			<article class="mk-wh-proto-kpi">
				<div class="mk-wh-proto-kpi__label">
					<span class="mk-wh-proto-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.9"/></svg></span>
					Phiếu chờ QC
				</div>
				<div class="mk-wh-proto-kpi__value" id="mkWhKpiPendingQc">0</div>
			</article>
			<article class="mk-wh-proto-kpi">
				<div class="mk-wh-proto-kpi__label">
					<span class="mk-wh-proto-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 3v12" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20h16" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></span>
					Phiếu xuất chờ duyệt
				</div>
				<div class="mk-wh-proto-kpi__value" id="mkWhKpiPendingApprove">0</div>
			</article>
			<article class="mk-wh-proto-kpi">
				<div class="mk-wh-proto-kpi__label">
					<span class="mk-wh-proto-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" stroke="currentColor" stroke-width="1.7"/><path d="M3.3 7.7 12 12l8.7-4.3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 22V12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
					SKU đang lưu kho
				</div>
				<div class="mk-wh-proto-kpi__value" id="mkWhKpiSku">0</div>
			</article>
			<article class="mk-wh-proto-kpi mk-wh-proto-kpi--danger">
				<div class="mk-wh-proto-kpi__label">
					<span class="mk-wh-proto-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 9v4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M12 16.6h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/><path d="M10.3 4.2 2.7 18a2 2 0 0 0 1.8 3h15a2 2 0 0 0 1.8-3L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
					Lô sắp hết hạn
				</div>
				<div class="mk-wh-proto-kpi__value" id="mkWhKpiExpiring">0</div>
			</article>
			<article class="mk-wh-proto-kpi mk-wh-proto-kpi--danger">
				<div class="mk-wh-proto-kpi__label">
					<span class="mk-wh-proto-kpi__ic" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M3 12h4l3-8 4 16 3-8h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
					Dự kiến hết hàng
				</div>
				<div class="mk-wh-proto-kpi__value" id="mkWhKpiStockout">0</div>
			</article>
		</section>

			<nav class="mk-wh-proto-tabs" aria-label="Warehouse sections" id="mkWhDetailTabs">
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
				<button type="button" class="mk-wh-proto-tab" data-tab="returns">
					<span class="mk-wh-proto-tab__ic" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 12a9 9 0 1 0 3-6.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M3 4v5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</span>
					Thu hồi / Trả
				</button>
			</nav>

			<section class="mk-wh-proto-stage">
				<header class="mk-wh-proto-stage__head">
					<h2 class="mk-wh-proto-stage__title" id="mkWhProtoStageTitle">Danh sách phiếu nhập</h2>
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
									<th>NSX / HSD</th>
									<th>SL</th>
									<th class="mk-wh-proto-td-right">QC</th>
								</tr>
							</thead>
							<tbody id="mkWhProtoQcTbody"></tbody>
						</table>
					</div>
				</div>

				<div class="mk-wh-proto-pane hide" id="mkWhProtoPaneStock">
					<div class="mk-wh-proto-filters" id="mkWhProtoStockFilters" aria-label="Bộ lọc tồn kho">
						<div class="mk-wh-proto-filters__row">
							<label class="mk-wh-proto-filter">
								<span class="mk-wh-proto-filter__label">HSD</span>
								<select class="mk-wh-proto-filter__control" id="mkWhProtoFilterHsd">
									<option value="all">Tất cả</option>
									<option value="soon">Sắp hết hạn</option>
									<option value="valid">Còn hạn</option>
									<option value="expired">Quá hạn</option>
								</select>
							</label>
							<div class="mk-wh-proto-filter mk-wh-proto-filter--search" role="search">
								<span class="mk-wh-proto-filter__label">Tìm kiếm</span>
								<div class="mk-wh-stock-search">
									<span class="mk-wh-stock-search__ic" aria-hidden="true"><i class="fa fa-search"></i></span>
									<input class="mk-wh-stock-search__input" id="mkWhProtoStockSearch" type="search"
										placeholder="Tìm theo tên, SKU hoặc lô…" autocomplete="off" />
									<button type="button" class="mk-wh-stock-search__clear" id="mkWhProtoStockSearchClear"
										aria-label="Xóa tìm kiếm" hidden>&times;</button>
								</div>
							</div>
							<label class="mk-wh-proto-filter">
								<span class="mk-wh-proto-filter__label">Giá</span>
								<select class="mk-wh-proto-filter__control" id="mkWhProtoFilterPrice">
									<option value="all">Tất cả</option>
									<option value="asc">Thấp → Cao</option>
									<option value="desc">Cao → Thấp</option>
								</select>
							</label>
							<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost mk-wh-proto-filter-reset" id="mkWhProtoFilterReset">Xóa lọc</button>
						</div>
						<p class="mk-wh-proto-filters__summary" id="mkWhProtoFilterSummary" aria-live="polite"></p>
					</div>
					<div class="mk-wh-proto-table-wrap">
						<table class="mk-wh-proto-table" role="table">
							<thead>
								<tr>
									<th>SKU</th>
									<th>Tên hàng</th>
									<th>Lô</th>
									<th>HSD</th>
									<th>Dự kiến hết</th>
									<th class="mk-wh-proto-td-right">Giá</th>
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
									<th>Loại xuất</th>
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

				<div class="mk-wh-proto-pane hide" id="mkWhProtoPaneReturns">
					<div class="mk-wh-proto-table-wrap">
						<table class="mk-wh-proto-table" role="table">
							<thead>
								<tr>
									<th>Mã phiếu</th>
									<th>Loại</th>
									<th>Nguồn</th>
									<th>Hoàn tiền</th>
									<th>Ngày tạo</th>
									<th>Trạng thái</th>
									<th class="mk-wh-proto-td-right">Thao tác</th>
								</tr>
							</thead>
							<tbody id="mkWhProtoReturnsTbody"></tbody>
						</table>
					</div>
				</div>
			</section>
		</section>
	</div>
</div>

{* Prototype-style modal: Tạo phiếu nhập / xuất (workspace-size) *}
<div class="mk-wh-proto-modal" id="mkWhProtoModal" aria-hidden="true">
	<div class="mk-wh-proto-modal__backdrop" data-mk-close="1"></div>
	<div class="mk-wh-proto-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mkWhProtoModalTitle">
		<header class="mk-wh-proto-modal__head">
			<div class="mk-wh-proto-modal__head-main">
				<span class="mk-wh-proto-modal__eyebrow" id="mkWhProtoModalEyebrow">Kho</span>
				<h3 class="mk-wh-proto-modal__title" id="mkWhProtoModalTitle">Tạo phiếu</h3>
				<p class="mk-wh-proto-modal__sub" id="mkWhProtoModalSub" hidden></p>
			</div>
			<button type="button" class="mk-wh-proto-modal__close" data-mk-close="1" aria-label="Đóng">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
			</button>
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

{* Prototype-style dialog cho phiếu nhập/xuất (timeline màu sắc) *}
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
		<footer class="mk-wh-proto-dialog__foot" id="mkWhProtoDialogFoot">
			<button type="button" class="mk-wh-proto-mini-btn" data-mk-dialog-close="1">Đóng</button>
		</footer>
	</div>
</div>

<div class="mk-wh-qc-lightbox" id="mkWhQcLightbox" aria-hidden="true">
	<div class="mk-wh-qc-lightbox__backdrop" data-mk-action="qc-lightbox-close"></div>
	<div class="mk-wh-qc-lightbox__panel" role="dialog" aria-modal="true" aria-label="Xem ảnh QC">
		<div class="mk-wh-qc-lightbox__tools">
			<div class="mk-wh-qc-lightbox__zoom-group" title="Kính lúp">
				<button type="button" class="mk-wh-qc-lightbox__zoom-btn" data-mk-action="qc-lightbox-zoom-out" title="Thu nhỏ">−</button>
				<span class="mk-wh-qc-lightbox__zoom-label" data-mk-action="qc-lightbox-zoom-reset" data-mk-qc-zoom-label="1" title="Về 100%">100%</span>
				<button type="button" class="mk-wh-qc-lightbox__zoom-btn" data-mk-action="qc-lightbox-zoom-in" title="Phóng to">+</button>
			</div>
			<button type="button" class="mk-wh-qc-lightbox__close" data-mk-action="qc-lightbox-close" aria-label="Đóng">&times;</button>
		</div>
		<button type="button" class="mk-wh-qc-lightbox__nav mk-wh-qc-lightbox__nav--prev" data-mk-action="qc-lightbox-prev" aria-label="Ảnh trước">&#8249;</button>
		<div class="mk-wh-qc-lightbox__stage" data-mk-qc-lightbox-stage="1">
			<img class="mk-wh-qc-lightbox__img" alt="" />
		</div>
		<button type="button" class="mk-wh-qc-lightbox__nav mk-wh-qc-lightbox__nav--next" data-mk-action="qc-lightbox-next" aria-label="Ảnh sau">&#8250;</button>
		<div class="mk-wh-qc-lightbox__caption"></div>
	</div>
</div>

<div class="mk-wh-proto-modal" id="mkWhReturnModal" aria-hidden="true">
	<div class="mk-wh-proto-modal__backdrop" data-mk-return-close="1"></div>
	<div class="mk-wh-proto-modal__dialog mk-wh-proto-modal__dialog--lux mk-wh-proto-modal__dialog--compact mk-wh-proto-modal__dialog--workspace mk-wh-proto-modal__dialog--return" role="dialog" aria-modal="true" aria-labelledby="mkWhReturnModalTitle">
		<header class="mk-wh-proto-modal__head">
			<div class="mk-wh-proto-modal__head-main">
				<span class="mk-wh-proto-modal__eyebrow">KHO</span>
				<h3 class="mk-wh-proto-modal__title" id="mkWhReturnModalTitle">Tạo phiếu thu hồi / trả hàng</h3>
				<p class="mk-wh-proto-modal__sub">Gộp nhiều phiếu xuất của kho này. Chỉ nhập lại những dòng đã chọn số lượng — không bắt buộc trả hết.</p>
			</div>
			<button type="button" class="mk-wh-proto-modal__close" data-mk-return-close="1" aria-label="Đóng">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
			</button>
		</header>
		<form class="mk-wh-proto-modal__body" id="mkWhReturnForm" autocomplete="off">
			<div class="mk-wh-return-workspace">
				<aside class="mk-wh-return-pane mk-wh-return-pane--issues">
					<div class="mk-wh-proto-field">
						<label for="mkWhReturnDocType">Loại phiếu</label>
						<select id="mkWhReturnDocType">
							<option value="return">Trả hàng</option>
							<option value="recall">Thu hồi</option>
						</select>
					</div>
					<div class="mk-wh-proto-field">
						<label for="mkWhReturnSourceQ">Phiếu xuất kho</label>
						<div class="mk-wh-return-search">
							<input type="search" id="mkWhReturnSourceQ" placeholder="Mã phiếu xuất, khách hàng…" autocomplete="off" />
							<button type="button" id="mkWhReturnSourceSearchBtn">Tìm</button>
						</div>
						<p class="mk-wh-return-hint">Chọn một hoặc nhiều phiếu đã xuất. Có thể gộp.</p>
						<div id="mkWhReturnSourceResults" class="mk-wh-return-results"></div>
					</div>
					<div class="mk-wh-return-picked-wrap">
						<div class="mk-wh-return-picked-head">Đã chọn <span id="mkWhReturnPickedCount">0</span></div>
						<div id="mkWhReturnPickedList" class="mk-wh-return-picked-list"></div>
					</div>
					<input type="hidden" id="mkWhReturnSourceType" value="retail" />
					<input type="hidden" id="mkWhReturnSoId" value="" />
					<input type="hidden" id="mkWhReturnScId" value="" />
					<input type="hidden" id="mkWhReturnSourceLabel" value="" />
					<div class="mk-wh-proto-field mk-wh-proto-field--check">
						<label class="mk-wh-proto-check" for="mkWhReturnRefund">
							<span class="mk-wh-proto-check__box">
								<input class="mk-wh-proto-check__input" type="checkbox" id="mkWhReturnRefund" />
								<span class="mk-wh-proto-check__visual" aria-hidden="true">
									<svg class="mk-wh-proto-check__icon" width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2.2 6.1 4.8 8.7 9.8 3.3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
								</span>
							</span>
							<span class="mk-wh-proto-check__text">
								<span class="mk-wh-proto-check__label">Hoàn tiền trên đơn hàng (tùy chọn)</span>
								<span class="mk-wh-proto-check__hint">Tắt: chỉ nhập kho. Bật: giảm số đã thu trên SO.</span>
							</span>
						</label>
					</div>
					<div class="mk-wh-proto-field">
						<label for="mkWhReturnNote">Ghi chú</label>
						<textarea id="mkWhReturnNote" rows="3" placeholder="Lý do trả / thu hồi"></textarea>
					</div>
				</aside>
				<section class="mk-wh-return-pane mk-wh-return-pane--lines">
					<div class="mk-wh-proto-lines mk-wh-proto-lines--catalog">
						<div class="mk-wh-proto-lines__head">
							<span class="mk-wh-proto-lines__ttl">Sản phẩm cần trả</span>
							<button type="button" class="mk-wh-proto-mini-btn" id="mkWhReturnTakeAll" hidden>Lấy hết</button>
						</div>
						<p class="mk-wh-return-hint" id="mkWhReturnLinesHint">Chọn phiếu xuất bên trái, rồi nhập số lượng từng dòng. Dòng để 0 sẽ không trả.</p>
						<div class="mk-wh-proto-table-wrap mk-wh-proto-lines__tableWrap">
							<table class="mk-wh-proto-table mk-wh-proto-lines__table mk-wh-return-lines">
								<thead>
									<tr>
										<th class="mk-wh-return-col-name">Tên hàng</th>
										<th class="mk-wh-return-col-sku">SKU</th>
										<th class="mk-wh-return-col-lot">Lô</th>
										<th class="mk-wh-return-col-issue">Phiếu xuất</th>
										<th class="mk-wh-return-col-max mk-wh-proto-td-right">Đã xuất</th>
										<th class="mk-wh-return-col-qty mk-wh-proto-td-right">SL trả</th>
										<th class="mk-wh-return-col-act"></th>
									</tr>
								</thead>
								<tbody id="mkWhReturnLinesBody"></tbody>
							</table>
						</div>
					</div>
				</section>
			</div>
		</form>
		<div class="mk-wh-proto-modal__foot">
			<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost" data-mk-return-close="1">Hủy</button>
			<button type="submit" class="mk-wh-proto-btn mk-wh-proto-btn--primary" id="mkWhReturnSave" form="mkWhReturnForm">Lưu phiếu</button>
		</div>
	</div>
</div>

{/strip}
