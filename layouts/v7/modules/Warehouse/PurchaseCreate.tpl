{* Tạo phiếu nhập hàng — từ nút + Nhập hàng *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt mk-purchase-page mk-purchase-page--create" id="mkPurchaseCreateRoot" data-can-write="{$MK_WH_CAN_WRITE|default:0}">
		<header class="mk-purchase-hero">
			<div class="mk-purchase-hero__copy">
				<p class="mk-purchase-eyebrow">
					<span class="mk-purchase-eyebrow__dot" aria-hidden="true"></span>
					<a href="index.php?module=Warehouse&amp;view=PurchaseHistory&amp;app=INVENTORY" style="color:inherit;text-decoration:none">Nhập hàng</a>
					· Tạo mới
				</p>
				<h1 class="mk-purchase-hero__title">Nhập hàng</h1>
				<p class="mk-purchase-hero__sub">Chọn kho, nhà cung cấp và hàng — <strong>Lưu tạm</strong> hoặc <strong>Hoàn thành</strong> để cộng tồn kho.</p>
			</div>
			<div class="mk-purchase-hero__actions">
				<a class="mk-purchase-btn mk-purchase-btn--ghost" href="index.php?module=Warehouse&amp;view=PurchaseHistory&amp;app=INVENTORY">Danh sách nhập hàng</a>
			</div>
		</header>

		<div class="mk-purchase-grid">
			<aside class="mk-purchase-card mk-purchase-side">
				<div class="mk-purchase-card__head">
					<div class="mk-purchase-card__icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M8 7h13M8 12h13M8 17h13M3 7h.01M3 12h.01M3 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					</div>
					<div>
						<h2 class="mk-purchase-card__title">Thông tin phiếu</h2>
						<p class="mk-purchase-card__hint">Bắt buộc chọn kho và nhà cung cấp</p>
					</div>
				</div>

				<label class="mk-purchase-field">
					<span>Kho nhận <em>*</em></span>
					<select id="mkPurchaseWh" required></select>
				</label>
				<label class="mk-purchase-field mk-purchase-field--suggest">
					<span>Nhà cung cấp <em>*</em></span>
					<input type="text" id="mkPurchaseVendorQ" placeholder="Tìm tên / mã / SĐT…" autocomplete="off" />
					<input type="hidden" id="mkPurchaseVendorId" value="" />
					<div class="mk-purchase-suggest" id="mkPurchaseVendorSuggest" hidden></div>
				</label>
				<label class="mk-purchase-field">
					<span>Tên NCC trên phiếu</span>
					<input type="text" id="mkPurchaseSupplier" placeholder="Tự điền khi chọn NCC" />
				</label>
				<label class="mk-purchase-field">
					<span>Tham chiếu</span>
					<input type="text" id="mkPurchasePoRef" placeholder="Trống = tự tạo MH-…" />
				</label>

				<div class="mk-purchase-pay-block">
					<p class="mk-purchase-pay-block__label">Thanh toán</p>
					<label class="mk-purchase-field">
						<span>Giảm giá (VNĐ)</span>
						<input type="number" id="mkPurchaseDiscount" min="0" step="1000" value="0" />
					</label>
					<label class="mk-purchase-field">
						<span>Tiền trả NCC (VNĐ)</span>
						<input type="number" id="mkPurchasePaid" min="0" step="1000" value="0" />
					</label>
					<label class="mk-purchase-field">
						<span>Hình thức / ghi chú</span>
						<input type="text" id="mkPurchasePayNote" placeholder="TM / CK / công nợ…" />
					</label>
				</div>
			</aside>

			<div class="mk-purchase-card mk-purchase-main">
				<div class="mk-purchase-lines-head">
					<div>
						<h2 class="mk-purchase-card__title">Dòng hàng</h2>
						<p class="mk-purchase-card__hint" id="mkPurchaseLineCount">0 mặt hàng</p>
					</div>
					<div class="mk-purchase-add-row">
						<div class="mk-purchase-search">
							<svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
							<input type="search" id="mkPurchaseProductQ" placeholder="Tìm hàng theo mã hoặc tên…" autocomplete="off" />
						</div>
						<button type="button" class="mk-purchase-btn mk-purchase-btn--primary" id="mkPurchaseAddProductBtn">Thêm</button>
					</div>
				</div>
				<div class="mk-purchase-suggest mk-purchase-suggest--wide" id="mkPurchaseProductSuggest" hidden></div>

				<div class="mk-purchase-table-wrap">
					<table class="mk-purchase-table" id="mkPurchaseLinesTable">
						<thead>
							<tr>
								<th>Mã</th>
								<th>Tên hàng</th>
								<th>Lô</th>
								<th class="is-num">SL</th>
								<th class="is-num">Giá nhập</th>
								<th class="is-num">Thành tiền</th>
								<th class="is-action"></th>
							</tr>
						</thead>
						<tbody id="mkPurchaseLinesBody">
							<tr class="mk-purchase-empty-row">
								<td colspan="7">
									<div class="mk-purchase-empty">
										<div class="mk-purchase-empty__icon" aria-hidden="true">
											<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
										</div>
										<p class="mk-purchase-empty__title">Chưa có dòng hàng</p>
										<p class="mk-purchase-empty__sub">Gõ mã hoặc tên sản phẩm ở ô tìm kiếm rồi chọn để thêm.</p>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<footer class="mk-purchase-footer">
					<div class="mk-purchase-totals">
						<div class="mk-purchase-total">
							<span>Tổng hàng</span>
							<strong id="mkPurchaseSubtotal">0</strong>
						</div>
						<div class="mk-purchase-total">
							<span>Giảm giá</span>
							<strong id="mkPurchaseDiscountLbl">0</strong>
						</div>
						<div class="mk-purchase-total mk-purchase-total--due">
							<span>Cần trả NCC</span>
							<strong id="mkPurchaseDue">0</strong>
						</div>
					</div>
					<div class="mk-purchase-actions">
						<button type="button" class="mk-purchase-btn mk-purchase-btn--ghost" id="mkPurchaseDraftBtn">Lưu tạm</button>
						<button type="button" class="mk-purchase-btn mk-purchase-btn--primary mk-purchase-btn--lg" id="mkPurchaseCompleteBtn">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 7 10 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							Hoàn thành
						</button>
					</div>
				</footer>
			</div>
		</div>

		<div class="mk-purchase-toast" id="mkPurchaseMsg" role="status" hidden></div>
	</section>
</div>
{/strip}
