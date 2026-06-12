{* Chi tiết kho — nhập/xuất/QC/tồn theo warehouse_id (localStorage prototype) *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-proto mk-wh-mgmt" id="mkWhDetailRoot" data-wh-id="{$MK_WH_ID|escape:'html'}">
		<header class="mk-wh-proto-head">
			<div class="mk-wh-proto-title">
				<div class="mk-wh-mgmt-breadcrumb">
					<a href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">← Danh sách kho</a>
				</div>
				<h1 class="mk-wh-proto-title__h1" id="mkWhDetailTitle">Kho</h1>
				<p class="mk-wh-proto-title__sub" id="mkWhDetailDesc"></p>
			</div>
				<div class="mk-wh-proto-role">
					<div class="mk-wh-proto-role__label">Đang đăng nhập với vai trò</div>
					<div class="mk-wh-proto-role__control">
						<select class="mk-wh-proto-select" id="mkWhDetailRole">
							<option value="keeper">Thủ kho — Thủ kho Hà</option>
							<option value="qc">QC — QC Minh</option>
							<option value="manager">Quản lý kho — QL Tuấn</option>
						</select>
					</div>
				</div>
			</header>

			<div class="mk-wh-proto-perms" id="mkWhDetailPerms" role="status"></div>

		<section class="mk-wh-mgmt-kpis" aria-label="KPI kho" id="mkWhDetailKpis"></section>

			<nav class="mk-wh-proto-tabs" aria-label="Các tab kho" id="mkWhDetailTabs">
				<button type="button" class="mk-wh-proto-tab is-active" data-tab="inbound">Nhập kho</button>
				<button type="button" class="mk-wh-proto-tab" data-tab="qc">QC</button>
				<button type="button" class="mk-wh-proto-tab" data-tab="stock">Tồn kho</button>
				<button type="button" class="mk-wh-proto-tab" data-tab="outbound">Xuất kho</button>
			</nav>

			<section class="mk-wh-proto-stage">
				<header class="mk-wh-proto-stage__head">
					<h2 class="mk-wh-proto-stage__title" id="mkWhDetailStageTitle">Danh sách phiếu nhập kho</h2>
					<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--primary hide" id="mkWhDetailCreateBtn">Tạo phiếu</button>
				</header>
				<div class="mk-wh-proto-pane" id="mkWhDetailPane"></div>
			</section>
		</section>
</div>
<div class="mk-wh-mgmt-modal hide" id="mkWhDetailModal" role="dialog" aria-modal="true">
	<div class="mk-wh-mgmt-modal__backdrop" data-mk-wh-detail-close="1"></div>
	<div class="mk-wh-mgmt-modal__panel mk-wh-mgmt-modal__panel--wide">
		<header class="mk-wh-mgmt-modal__head">
			<h3 id="mkWhDetailModalTitle">Chi tiết</h3>
			<button type="button" class="mk-wh-mgmt-modal__close" data-mk-wh-detail-close="1">&times;</button>
		</header>
		<div id="mkWhDetailModalBody" class="mk-wh-mgmt-modal__body"></div>
		<footer class="mk-wh-mgmt-modal__foot" id="mkWhDetailModalFoot"></footer>
	</div>
</div>
{/strip}
