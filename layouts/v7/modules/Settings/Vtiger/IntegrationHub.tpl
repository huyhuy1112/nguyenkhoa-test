{* Settings → Integration Hub (UI shell — data from IntegrationHub.mock.js) *}
{strip}
<div class="detailViewContainer nk-hub" id="NkIntegrationHub" data-legacy-url="{$LEGACY_INTEGRATIONS_URL|escape:'html'}">
	<div class="nk-hub__top">
		<p class="nk-hub__intro">{vtranslate('LBL_NK_INTEGRATION_HUB_DESC', $QUALIFIED_MODULE)}</p>
		<div class="nk-hub__top-actions">
			<a class="nk-hub__legacy-link" href="{$LEGACY_INTEGRATIONS_URL|escape:'html'}">{vtranslate('LBL_NK_INTEGRATION_HUB_LEGACY', $QUALIFIED_MODULE)} →</a>
			<button type="button" class="mk-settings-btn mk-settings-btn--primary nk-hub__add-btn" id="nk-hub-add">
				<span>+ {vtranslate('LBL_NK_INTEGRATION_HUB_ADD', $QUALIFIED_MODULE)}</span>
			</button>
		</div>
	</div>

	<div class="nk-hub-stats" id="nk-hub-stats">
		<div class="nk-hub-stat nk-hub-stat--total">
			<span class="nk-hub-stat__ic" aria-hidden="true">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M10 13a5 5 0 0 1 7 0l1 1a3 3 0 0 1-3 3h-3a3 3 0 0 1-3-3l1-1z"/><path d="M12 3v4M8 7l2 2M16 7l-2 2"/></svg>
			</span>
			<div class="nk-hub-stat__body">
				<span class="nk-hub-stat__label">{vtranslate('LBL_NK_INTEGRATION_HUB_STAT_TOTAL', $QUALIFIED_MODULE)}</span>
				<strong class="nk-hub-stat__value" data-stat="total">0</strong>
				<span class="nk-hub-stat__sub">hệ thống</span>
			</div>
		</div>
		<div class="nk-hub-stat nk-hub-stat--active">
			<span class="nk-hub-stat__ic" aria-hidden="true">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20 6L9 17l-5-5"/></svg>
			</span>
			<div class="nk-hub-stat__body">
				<span class="nk-hub-stat__label">{vtranslate('LBL_NK_INTEGRATION_HUB_STAT_ACTIVE', $QUALIFIED_MODULE)}</span>
				<strong class="nk-hub-stat__value"><span data-stat="active">0</span> <span class="nk-hub-stat__pct">/ <span data-stat="active_pct">0</span>%</span></strong>
			</div>
		</div>
		<div class="nk-hub-stat nk-hub-stat--warning">
			<span class="nk-hub-stat__ic" aria-hidden="true">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
			</span>
			<div class="nk-hub-stat__body">
				<span class="nk-hub-stat__label">{vtranslate('LBL_NK_INTEGRATION_HUB_STAT_WARNING', $QUALIFIED_MODULE)}</span>
				<strong class="nk-hub-stat__value"><span data-stat="warning">0</span> <span class="nk-hub-stat__pct">/ <span data-stat="warning_pct">0</span>%</span></strong>
			</div>
		</div>
		<div class="nk-hub-stat nk-hub-stat--error">
			<span class="nk-hub-stat__ic" aria-hidden="true">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
			</span>
			<div class="nk-hub-stat__body">
				<span class="nk-hub-stat__label">{vtranslate('LBL_NK_INTEGRATION_HUB_STAT_ERROR', $QUALIFIED_MODULE)}</span>
				<strong class="nk-hub-stat__value"><span data-stat="error">0</span> <span class="nk-hub-stat__pct">/ <span data-stat="error_pct">0</span>%</span></strong>
			</div>
		</div>
		<div class="nk-hub-stat nk-hub-stat--sync">
			<span class="nk-hub-stat__ic" aria-hidden="true">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
			</span>
			<div class="nk-hub-stat__body">
				<span class="nk-hub-stat__label">{vtranslate('LBL_NK_INTEGRATION_HUB_STAT_SYNC_TODAY', $QUALIFIED_MODULE)}</span>
				<strong class="nk-hub-stat__value" data-stat="synced_today">0</strong>
				<span class="nk-hub-stat__sub">bản ghi</span>
			</div>
		</div>
	</div>

	<div class="nk-hub-layout">
		<div class="nk-hub-main">
			<section class="nk-hub-panel nk-hub-panel--connections">
				<header class="nk-hub-panel__head">
					<h2 class="nk-hub-panel__title">{vtranslate('LBL_NK_INTEGRATION_HUB_CONNECTIONS', $QUALIFIED_MODULE)}</h2>
					<div class="nk-hub-panel__tools">
						<select class="nk-hub-select" id="nk-hub-filter-status" aria-label="Filter">
							<option value="all">{vtranslate('LBL_NK_INTEGRATION_HUB_FILTER_ALL', $QUALIFIED_MODULE)}</option>
							<option value="active">{vtranslate('LBL_NK_HUB_STATUS_ACTIVE', $QUALIFIED_MODULE)}</option>
							<option value="warning">{vtranslate('LBL_NK_HUB_STATUS_WARNING', $QUALIFIED_MODULE)}</option>
							<option value="error">{vtranslate('LBL_NK_HUB_STATUS_ERROR', $QUALIFIED_MODULE)}</option>
							<option value="inactive">{vtranslate('LBL_NK_HUB_STATUS_INACTIVE', $QUALIFIED_MODULE)}</option>
						</select>
						<label class="nk-hub-search">
							<span class="fa fa-search" aria-hidden="true"></span>
							<input type="search" id="nk-hub-search" placeholder="{vtranslate('LBL_NK_INTEGRATION_HUB_SEARCH', $QUALIFIED_MODULE)}" autocomplete="off" />
						</label>
						<div class="nk-hub-view-toggle" role="group" aria-label="View">
							<button type="button" class="nk-hub-view-btn is-active" data-view="grid" title="Grid"><span class="fa fa-th-large"></span></button>
							<button type="button" class="nk-hub-view-btn" data-view="list" title="List"><span class="fa fa-list"></span></button>
						</div>
					</div>
				</header>

				<div class="nk-hub-cards is-grid" id="nk-hub-cards"></div>
				<p class="nk-hub-cards-empty" id="nk-hub-cards-empty" hidden>Không có kết nối phù hợp.</p>
				<p class="nk-hub-view-all"><a href="#" id="nk-hub-view-all">{vtranslate('LBL_NK_INTEGRATION_HUB_VIEW_ALL', $QUALIFIED_MODULE)} →</a></p>
			</section>

			<div class="nk-hub-bottom">
				<section class="nk-hub-panel nk-hub-panel--detail" id="nk-hub-detail">
					<header class="nk-hub-detail__head">
						<div class="nk-hub-detail__brand">
							<span class="nk-hub-card__icon" data-role="detail-icon"><img src="layouts/v7/modules/Settings/Vtiger/resources/logos/google_sheet.svg" alt="" loading="lazy" /></span>
							<div>
								<h2 class="nk-hub-detail__title" data-role="detail-title">—</h2>
								<span class="nk-hub-badge nk-hub-badge--inactive" data-role="detail-badge">—</span>
							</div>
						</div>
						<label class="nk-hub-toggle" title="Bật/tắt kết nối (demo UI)">
							<input type="checkbox" data-role="detail-enabled" />
							<span class="nk-hub-toggle__track"><span class="nk-hub-toggle__thumb"></span></span>
						</label>
					</header>
					<dl class="nk-hub-detail__grid">
						<div class="nk-hub-detail__row"><dt>URL</dt><dd data-role="detail-url">—</dd></div>
						<div class="nk-hub-detail__row"><dt>API Key</dt><dd data-role="detail-api">—</dd></div>
						<div class="nk-hub-detail__row"><dt>Webhook</dt><dd data-role="detail-webhook">—</dd></div>
						<div class="nk-hub-detail__row"><dt>Đồng bộ 2 chiều</dt><dd data-role="detail-twoway">—</dd></div>
						<div class="nk-hub-detail__row"><dt>Lần sync cuối</dt><dd data-role="detail-sync">—</dd></div>
						<div class="nk-hub-detail__row"><dt>Trạng thái</dt><dd data-role="detail-status">—</dd></div>
						<div class="nk-hub-detail__row nk-hub-detail__row--wide"><dt>Ghi chú</dt><dd data-role="detail-notes">—</dd></div>
					</dl>
					<footer class="nk-hub-detail__actions">
						<a class="mk-settings-btn mk-settings-btn--outline" data-role="detail-configure" href="{$LEGACY_INTEGRATIONS_URL|escape:'html'}">{vtranslate('LBL_NK_INTEGRATION_HUB_CONFIGURE', $QUALIFIED_MODULE)}</a>
						<button type="button" class="mk-settings-btn mk-settings-btn--primary nk-hub-detail__test">{vtranslate('LBL_NK_INTEGRATION_HUB_TEST', $QUALIFIED_MODULE)}</button>
						<button type="button" class="mk-settings-btn mk-settings-btn--danger nk-hub-detail__disconnect">{vtranslate('LBL_NK_INTEGRATION_HUB_DISCONNECT', $QUALIFIED_MODULE)}</button>
					</footer>
				</section>
			</div>

			<section class="nk-hub-panel nk-hub-panel--flow">
				<div class="nk-hub-pipeline__head">
					<h2 class="nk-hub-panel__title">{vtranslate('LBL_NK_INTEGRATION_HUB_FLOW', $QUALIFIED_MODULE)}</h2>
					<span class="nk-hub-pipeline__live"><span class="nk-hub-pipeline__live-dot"></span> Live sync</span>
				</div>

				<div class="nk-hub-pipeline" id="nk-hub-pipeline">
					<div class="nk-hub-pipeline__track" id="nk-hub-pipeline-track"></div>

					<div class="nk-hub-pipeline__progress" aria-hidden="true">
						<div class="nk-hub-pipeline__progress-bar"><span class="nk-hub-pipeline__progress-fill" id="nk-hub-pipeline-progress"></span></div>
						<div class="nk-hub-pipeline__progress-meta">
							<span>Luồng dữ liệu realtime</span>
							<strong id="nk-hub-pipeline-pct">68%</strong>
						</div>
					</div>

					<ul class="nk-hub-pipeline__legend">
						<li><span class="nk-hub-pipeline__dot nk-hub-pipeline__dot--ok"></span> Thành công</li>
						<li><span class="nk-hub-pipeline__dot nk-hub-pipeline__dot--warn"></span> Đang xử lý</li>
						<li><span class="nk-hub-pipeline__dot nk-hub-pipeline__dot--err"></span> Lỗi</li>
					</ul>
				</div>
			</section>
		</div>

		<aside class="nk-hub-log nk-hub-panel" id="nk-hub-log">
			<h2 class="nk-hub-log__title">{vtranslate('LBL_NK_INTEGRATION_HUB_ACTIVITY', $QUALIFIED_MODULE)}</h2>
			<div class="nk-hub-log__scroll">
				<ul class="nk-hub-log__list" id="nk-hub-log-list"></ul>
			</div>
			<p class="nk-hub-log__view-all"><a href="#" id="nk-hub-log-view-all">Xem tất cả nhật ký →</a></p>
		</aside>
	</div>

	{* Modals *}
	<div class="nk-hub-modal" id="nk-hub-modal-add" aria-hidden="true" role="dialog" aria-labelledby="nk-hub-modal-add-title">
		<div class="nk-hub-modal__backdrop"></div>
		<div class="nk-hub-modal__dialog">
			<header class="nk-hub-modal__head">
				<h3 id="nk-hub-modal-add-title">{vtranslate('LBL_NK_INTEGRATION_HUB_ADD', $QUALIFIED_MODULE)}</h3>
				<button type="button" class="nk-hub-modal__close" data-hub-modal-close aria-label="Đóng"><span class="fa fa-times"></span></button>
			</header>
			<div class="nk-hub-modal__body">
				<p class="nk-hub-modal__intro">Chọn loại kết nối cần thêm (demo UI).</p>
				<div class="nk-hub-add-grid" id="nk-hub-add-grid"></div>
			</div>
		</div>
	</div>

	<div class="nk-hub-modal" id="nk-hub-modal-test" aria-hidden="true" role="dialog" aria-labelledby="nk-hub-modal-test-title">
		<div class="nk-hub-modal__backdrop"></div>
		<div class="nk-hub-modal__dialog nk-hub-modal__dialog--sm">
			<header class="nk-hub-modal__head">
				<h3 id="nk-hub-modal-test-title">{vtranslate('LBL_NK_INTEGRATION_HUB_TEST', $QUALIFIED_MODULE)}</h3>
				<button type="button" class="nk-hub-modal__close" data-hub-modal-close aria-label="Đóng"><span class="fa fa-times"></span></button>
			</header>
			<div class="nk-hub-modal__body" id="nk-hub-test-body">
				<p>Kết nối: <strong id="nk-hub-test-name">—</strong></p>
			</div>
			<footer class="nk-hub-modal__foot">
				<button type="button" class="mk-settings-btn mk-settings-btn--primary" data-hub-modal-close>Đóng</button>
			</footer>
		</div>
	</div>

	<div class="nk-hub-modal" id="nk-hub-modal-disconnect" aria-hidden="true" role="dialog" aria-labelledby="nk-hub-modal-disconnect-title">
		<div class="nk-hub-modal__backdrop"></div>
		<div class="nk-hub-modal__dialog nk-hub-modal__dialog--sm">
			<header class="nk-hub-modal__head">
				<h3 id="nk-hub-modal-disconnect-title">{vtranslate('LBL_NK_INTEGRATION_HUB_DISCONNECT', $QUALIFIED_MODULE)}</h3>
				<button type="button" class="nk-hub-modal__close" data-hub-modal-close aria-label="Đóng"><span class="fa fa-times"></span></button>
			</header>
			<div class="nk-hub-modal__body">
				<p>Bạn có chắc muốn ngắt kết nối <strong id="nk-hub-disconnect-name">—</strong>?</p>
				<p class="nk-hub-modal-hint">Demo UI — không gọi API thật.</p>
			</div>
			<footer class="nk-hub-modal__foot">
				<button type="button" class="mk-settings-btn mk-settings-btn--outline" data-hub-modal-close>Hủy</button>
				<button type="button" class="mk-settings-btn mk-settings-btn--danger" id="nk-hub-disconnect-confirm">Ngắt kết nối</button>
			</footer>
		</div>
	</div>

	<script type="application/json" id="nk-hub-connections-json">{$HUB_CONNECTIONS_JSON nofilter}</script>
</div>
{/strip}
