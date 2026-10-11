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
				<p class="mk-wh-proto-title__sub">Tổng hợp tồn kho, hàng chờ QC, phiếu xuất chờ duyệt, lô sắp hết hạn, dự kiến hết hàng và cảnh báo tồn lâu ngày.</p>
			</div>
			<div class="mk-wh-mgmt-toolbar">
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">Danh sách kho</a>
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhTransfer&amp;app=INVENTORY">Chuyển kho</a>
			</div>
		</header>

		<section class="mk-wh-mgmt-kpis" aria-label="KPI đa kho" id="mkWhDashKpis"></section>

		<div class="mk-wh-mgmt-panel mk-wh-settings-panel" aria-label="Cài đặt kho" id="mkWhSettingsPanel">
			<div class="mk-wh-mgmt-panel__head">
				<h2 class="mk-wh-mgmt-panel__title">Cài đặt kho</h2>
			</div>
			<div class="mk-wh-settings-row" style="padding:12px 16px;display:flex;align-items:flex-start;gap:24px;flex-wrap:wrap;">
				<label class="mk-wh-settings-toggle" style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0;">
					<input type="checkbox" id="mkWhAllowNegativeStock" />
					<span>
						<strong>Cho phép tồn kho âm</strong>
						<span style="display:block;opacity:.75;font-size:12px;">
							Khi bật: xác nhận đơn / xuất kho được trừ quá tồn hiện có
							(vd. tồn 10, đặt 11 → tồn −1). Tắt: chặn khi thiếu hàng.
						</span>
					</span>
				</label>
				<label class="mk-wh-settings-expiry" style="display:flex;align-items:center;gap:10px;margin:0;">
					<span>
						<strong>Cảnh báo HSD (ngày)</strong>
						<span style="display:block;opacity:.75;font-size:12px;">Mặc định hệ thống. Sản phẩm có thể ghi đè.</span>
					</span>
					<input type="number" id="mkWhExpiryWarnDays" min="1" max="730" step="1" style="width:72px;height:36px;padding:0 8px;border:1px solid #cbd5e1;border-radius:8px;" />
				</label>
				<label class="mk-wh-settings-slow" style="display:flex;align-items:center;gap:10px;margin:0;">
					<span>
						<strong>Cửa sổ bán X ngày (DOI)</strong>
						<span style="display:block;opacity:.75;font-size:12px;">AVG_SALES = tổng xuất / X ngày. 30 / 60 / 90 tùy quy trình.</span>
					</span>
					<input type="number" id="mkWhSlowWindowDays" min="7" max="365" step="1" style="width:72px;height:36px;padding:0 8px;border:1px solid #cbd5e1;border-radius:8px;" />
				</label>
				<label class="mk-wh-settings-slow" style="display:flex;align-items:center;gap:10px;margin:0;">
					<span>
						<strong>Ngưỡng DOI</strong>
						<span style="display:block;opacity:.75;font-size:12px;">Số ngày tồn kỳ vọng (mẫu Risk).</span>
					</span>
					<input type="number" id="mkWhDoiThreshold" min="1" max="730" step="1" style="width:72px;height:36px;padding:0 8px;border:1px solid #cbd5e1;border-radius:8px;" />
				</label>
				<label class="mk-wh-settings-slow" style="display:flex;align-items:center;gap:10px;margin:0;">
					<span>
						<strong>Ngưỡng DSI</strong>
						<span style="display:block;opacity:.75;font-size:12px;">Ngày không phát sinh bán.</span>
					</span>
					<input type="number" id="mkWhDsiThreshold" min="1" max="730" step="1" style="width:72px;height:36px;padding:0 8px;border:1px solid #cbd5e1;border-radius:8px;" />
				</label>
				<label class="mk-wh-settings-slow" style="display:flex;align-items:center;gap:10px;margin:0;">
					<span>
						<strong>Age max</strong>
						<span style="display:block;opacity:.75;font-size:12px;">Tuổi tồn tối đa (ngày kể từ nhập).</span>
					</span>
					<input type="number" id="mkWhAgeMax" min="1" max="730" step="1" style="width:72px;height:36px;padding:0 8px;border:1px solid #cbd5e1;border-radius:8px;" />
				</label>
				<span id="mkWhSettingsStatus" class="mk-wh-settings-status" style="font-size:12px;opacity:.8;" aria-live="polite"></span>
			</div>
		</div>

		<div class="mk-wh-mgmt-panel" aria-label="Cảnh báo tồn lâu ngày" id="mkWhSlowPanel">
			<div class="mk-wh-mgmt-panel__head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
				<h2 class="mk-wh-mgmt-panel__title">Cảnh báo tồn lâu ngày</h2>
				<label style="display:flex;align-items:center;gap:8px;margin:0;font-size:13px;">
					<input type="checkbox" id="mkWhSlowOnlyAlert" checked="checked" />
					Chỉ hiện Risk ≥ 1.0
				</label>
			</div>
			<div class="mk-wh-mgmt-table-wrap">
				<table class="mk-wh-mgmt-table" role="table">
					<thead>
						<tr>
							<th>Kho</th>
							<th>Sản phẩm</th>
							<th class="mk-wh-mgmt-td-right">Tồn</th>
							<th class="mk-wh-mgmt-td-right">AVG bán/ngày</th>
							<th class="mk-wh-mgmt-td-right">DOI</th>
							<th class="mk-wh-mgmt-td-right">DSI</th>
							<th class="mk-wh-mgmt-td-right">Age</th>
							<th class="mk-wh-mgmt-td-right">Giá trị</th>
							<th class="mk-wh-mgmt-td-right">Risk</th>
							<th>Mức</th>
							<th>Hành động</th>
						</tr>
					</thead>
					<tbody id="mkWhSlowTableBody">
						<tr><td colspan="11" class="mk-wh-proto-muted">Đang tải…</td></tr>
					</tbody>
				</table>
			</div>
		</div>

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
							<th class="mk-wh-mgmt-td-right">Sắp hết hàng</th>
							<th class="mk-wh-mgmt-td-right">Tồn lâu</th>
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
