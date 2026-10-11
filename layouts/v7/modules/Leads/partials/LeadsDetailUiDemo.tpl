{* Leads Detail UI v4 — clean 3-column CRM layout. IDs kept for LeadsDetailUiDemo.js. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Leads&view=List&app=SALES'}

<div class="mk-ld-v4" data-mk-ld-v4="1">
	<nav class="mk-ld-v4-crumb" aria-label="Breadcrumb">
		<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">{vtranslate('LBL_SALES', 'Vtiger')}</a>
		<span class="mk-ld-v4-crumb__sep" aria-hidden="true">/</span>
		<a href="{$MK_LIST_URL}">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		<span class="mk-ld-v4-crumb__sep" aria-hidden="true">/</span>
		<span class="mk-ld-v4-crumb__current" id="mk-ld-ui-crumb-name">—</span>
	</nav>

	<header class="mk-ld-v4-hero mk-ld-v4-hero--flat detailview-header-block">
		<div class="mk-ld-v4-hero__main detailview-header">
			<div class="mk-ld-v4-hero__identity">
				<div class="mk-ld-v4-hero__avatar recordImage bgleads app-SALES" aria-hidden="true">
					<span class="mk-ld-v4-hero__avatar-ic">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='LEAD'}</span>
				</div>
				<div class="mk-ld-v4-hero__text recordBasicInfo">
					<div class="mk-ld-v4-hero__title-row">
						<h1 class="mk-ld-v4-hero__name">
							<span class="recordLabel" id="mk-ld-ui-title" title="">—</span>
						</h1>
						<p class="mk-ld-v4-hero__company" id="mk-ld-ui-subtitle"></p>
					</div>
					<div class="mk-ld-v4-hero__meta-row">
						<div class="mk-ld-v4-hero__meta mk-lead-detail-hero__meta" id="mk-ld-ui-meta"></div>
						<div class="mk-ld-v4-tags mk-ld-v4-tags--inline">
							<div class="tagsContainer" id="mk-ld-ui-tags">
								<button type="button" class="mk-ld-v4-tag-add" id="mk-ld-ui-add-tag">
									<i class="fa fa-plus"></i> {vtranslate('LBL_ADD_TAG', $MODULE_NAME)}
								</button>
								<span class="tagList" id="mk-ld-ui-tag-list"></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="mk-ld-v4-hero__actions detailViewButtoncontainer">
				<div class="mk-ld-v4-actions">
					<button type="button" class="mk-ld-v4-iconbtn" id="mk-ld-ui-follow" title="{vtranslate('LBL_FOLLOW', $MODULE_NAME)}">
						<span class="mk-ld-v4-iconbtn__ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='FOLLOW'}</span>
						<span class="mk-ld-v4-iconbtn__txt">{vtranslate('LBL_FOLLOW', $MODULE_NAME)}</span>
					</button>
					<button type="button" class="mk-ld-v4-iconbtn" data-mk-demo-action="edit" title="{vtranslate('LBL_EDIT', $MODULE_NAME)}">
						<span class="mk-ld-v4-iconbtn__ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='EDIT'}</span>
						<span class="mk-ld-v4-iconbtn__txt">{vtranslate('LBL_EDIT', $MODULE_NAME)}</span>
					</button>
					<button type="button" class="mk-ld-v4-iconbtn" data-mk-demo-action="email" title="{vtranslate('LBL_SEND_EMAIL', $MODULE_NAME)}">
						<span class="mk-ld-v4-iconbtn__ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='EMAIL'}</span>
						<span class="mk-ld-v4-iconbtn__txt">{vtranslate('LBL_SEND_EMAIL', $MODULE_NAME)}</span>
					</button>
					<div class="mk-ld-v4-more btn-group">
						<button type="button" class="mk-ld-v4-iconbtn dropdown-toggle" data-toggle="dropdown" title="{vtranslate('LBL_MORE', $MODULE_NAME)}">
							<span class="mk-ld-v4-iconbtn__ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='MORE'}</span>
							<span class="mk-ld-v4-iconbtn__txt">{vtranslate('LBL_MORE', $MODULE_NAME)}</span>
						</button>
						<ul class="dropdown-menu dropdown-menu-right">
							<li><a href="javascript:void(0)" data-mk-demo-action="call">{vtranslate('LBL_MK_CREATE_CALL', $MODULE_NAME)}</a></li>
							<li class="divider"></li>
							<li><a href="javascript:void(0)" data-mk-demo-action="duplicate">{vtranslate('LBL_DUPLICATE', $MODULE_NAME)}</a></li>
							<li><a href="javascript:void(0)" data-mk-demo-action="delete">{vtranslate('LBL_DELETE', $MODULE_NAME)}</a></li>
						</ul>
					</div>
					<button type="button" class="mk-ld-v4-cta" data-mk-demo-action="convert">
						<span aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='CONVERT'}</span>
						<span class="mk-lead-detail-btn__txt">{vtranslate('LBL_CONVERT_LEAD', $MODULE_NAME)}</span>
					</button>
				</div>
			</div>
		</div>
	</header>

	<div class="mk-ld-v4-kpi mk-lead-kpi" id="mk-ld-ui-kpi" aria-label="Chỉ số nhanh"></div>

	<div class="mk-ld-v4-tabs related-tabs" id="mk-ld-ui-related-tabs">
		<ul class="mk-ld-v4-tabs__list nav nav-tabs">
			<li class="tab-item active" data-mk-ui-tab="summary" data-label-key="Summary">
				<a href="javascript:void(0)"><strong>{vtranslate('LBL_SUMMARY', $MODULE_NAME)}</strong></a>
			</li>
			<li class="tab-item" data-mk-ui-tab="detail" data-label-key="Details">
				<a href="javascript:void(0)"><strong>{vtranslate('LBL_DETAILS', $MODULE_NAME)}</strong></a>
			</li>
			<li class="tab-item" data-mk-ui-tab="updates" data-label-key="Updates">
				<a href="javascript:void(0)"><strong>{vtranslate('LBL_UPDATES', $MODULE_NAME)}</strong></a>
			</li>
			<li class="tab-item mk-ld-v4-tabs__badge" data-module="Calendar" data-mk-scroll="activities" title="{vtranslate('LBL_ACTIVITIES', 'Calendar')}">
				<a href="javascript:void(0)">
					<span class="tab-icon">{include file="partials/LeadDetailTabSvgIcon.tpl"|@vtemplate_path:$MODULE MODULE='Calendar'}</span>
					<span class="numberCircle" data-count="0" data-badge="calendar">0</span>
				</a>
			</li>
		</ul>
	</div>

	<div class="mk-ld-v4-body details">
		<div id="mk-ld-ui-panel-summary" class="mk-ld-ui-panel">
			<form id="detailView" class="mk-ld-v4-form" method="POST" onsubmit="return false;">
				<div class="mk-ld-v4-grid">
					{* LEFT *}
					<aside class="mk-ld-v4-col mk-ld-v4-col--left">
						<section class="mk-ld-v4-panel" id="mk-ld-ui-section-key">
							<header class="mk-ld-v4-panel__head">
								<h2 class="mk-ld-v4-panel__title">{vtranslate('LBL_KEY_FIELDS', $MODULE_NAME)}</h2>
							</header>
							<div class="mk-ld-v4-panel__body summaryView">
								<div class="summaryViewFields mk-ld-v4-fields mk-lead-detail-kv-wrap" id="mk-ld-ui-key-fields"></div>
							</div>
						</section>
						<section class="mk-ld-v4-panel" id="mk-ld-ui-section-verify">
							<header class="mk-ld-v4-panel__head">
								<h2 class="mk-ld-v4-panel__title">Sales xác minh (Bộ B)</h2>
							</header>
							<div class="mk-ld-v4-panel__body">
								<div class="mk-lead-verify" id="mk-ld-ui-verify"></div>
							</div>
						</section>
					</aside>

					{* CENTER *}
					<section class="mk-ld-v4-col mk-ld-v4-col--center">
						<div class="mk-ld-v4-panel mk-ld-v4-panel--fill" id="mk-ld-ui-section-activity-log">
							<header class="mk-ld-v4-panel__head mk-lead-activity-log__head">
								<h2 class="mk-ld-v4-panel__title">{vtranslate('LBL_MK_ACTIVITY_LOG', $MODULE_NAME)}</h2>
								<div class="mk-ld-v4-panel__tools mk-lead-activity-log__head-right">
									<span class="mk-ld-v4-count" id="mk-ld-ui-activity-log-count">0 {vtranslate('LBL_MK_ACTIVITY_LOG_ITEMS', $MODULE_NAME)}</span>
									<div class="btn-group mk-lead-split-add">
										<button type="button" class="mk-lead-split-add__main" id="mk-ld-ui-activity-log-create" data-mk-log="task" title="{vtranslate('LBL_MK_CREATE_TASK', $MODULE_NAME)}">
											<span class="mk-lead-split-add__plus" aria-hidden="true">+</span>
											<span class="mk-lead-split-add__label">{vtranslate('LBL_MK_ADD', $MODULE_NAME)}</span>
										</button>
										<button type="button" class="mk-lead-split-add__toggle" aria-haspopup="true" aria-expanded="false" title="{vtranslate('LBL_MK_OPEN_ACTIVITY_MENU', $MODULE_NAME)}">
											<span class="mk-lead-split-add__caret" aria-hidden="true"></span>
											<span class="sr-only">{vtranslate('LBL_MK_OPEN_ACTIVITY_MENU', $MODULE_NAME)}</span>
										</button>
										<ul class="dropdown-menu dropdown-menu-right mk-lead-activity-log__menu" role="menu">
											<li role="presentation"><button type="button" class="mk-lead-activity-log__menu-btn" role="menuitem" data-mk-log="note">{vtranslate('LBL_MK_ADD_NOTE', $MODULE_NAME)}</button></li>
											<li role="presentation"><button type="button" class="mk-lead-activity-log__menu-btn" role="menuitem" data-mk-log="call">{vtranslate('LBL_MK_LOG_CALL', $MODULE_NAME)}</button></li>
											<li role="presentation"><button type="button" class="mk-lead-activity-log__menu-btn" role="menuitem" data-mk-log="meeting">{vtranslate('LBL_MK_LOG_MEETING', $MODULE_NAME)}</button></li>
										</ul>
									</div>
								</div>
							</header>
							<div class="mk-ld-v4-panel__body">
								<div class="mk-lead-last-touch" id="mk-ld-ui-last-touch" hidden>
									<div class="mk-lead-last-touch__head">
										<strong class="mk-lead-last-touch__title">Last Touch (Call)</strong>
										<span class="mk-lead-last-touch__badge" id="mk-ld-ui-last-touch-badge"></span>
									</div>
									<p class="mk-lead-last-touch__hint" id="mk-ld-ui-last-touch-hint"></p>
									<ul class="mk-lead-last-touch__list" id="mk-ld-ui-last-touch-list"></ul>
								</div>
								<div class="mk-lead-activity-log__list" id="mk-ld-ui-activity-log"></div>
							</div>
						</div>
					</section>

					{* RIGHT *}
					<aside class="mk-ld-v4-col mk-ld-v4-col--right">
						<section class="mk-ld-v4-panel" id="mk-ld-ui-section-activities">
							<header class="mk-ld-v4-panel__head">
								<h2 class="mk-ld-v4-panel__title">{vtranslate('LBL_ACTIVITIES', 'Calendar')}</h2>
								<div class="mk-ld-v4-panel__tools">
									<button type="button" class="mk-ld-v4-mini createActivity toDotask" data-mk-qc="task" title="{vtranslate('LBL_ADD_TASK', 'Calendar')}">
										<i class="fa fa-plus"></i> {vtranslate('LBL_ADD_TASK', 'Calendar')}
									</button>
									<button type="button" class="mk-ld-v4-mini createActivity" data-mk-qc="meeting" data-name="Events" title="{vtranslate('LBL_ADD_EVENT', 'Calendar')}">
										<i class="fa fa-plus"></i> {vtranslate('LBL_ADD_EVENT', 'Calendar')}
									</button>
								</div>
							</header>
							<div class="mk-ld-v4-panel__body">
								<div id="relatedActivities" class="mk-lead-detail-related-activities">
									<div class="widget_contents" id="mk-ld-ui-activities"></div>
								</div>
							</div>
						</section>

						<section class="mk-ld-v4-panel" id="mk-ld-ui-section-documents">
							<header class="mk-ld-v4-panel__head">
								<h2 class="mk-ld-v4-panel__title">{vtranslate('Documents', $MODULE_NAME)}</h2>
								<button type="button" class="mk-ld-v4-mini" data-mk-demo-action="new-document">
									<span class="fa fa-plus"></span> {vtranslate('LBL_NEW_DOCUMENT', 'Documents')}
								</button>
							</header>
							<div class="mk-ld-v4-panel__body mk-lead-detail-documents__body">
								<div class="noContent">
									<p>{vtranslate('LBL_NO_RELATED', $MODULE_NAME)} {vtranslate('SINGLE_Documents', 'Documents')}</p>
								</div>
							</div>
						</section>
					</aside>
				</div>
			</form>

			<div class="mk-lead-lt-modal" id="mk-ld-lt-modal" hidden aria-hidden="true">
				<div class="mk-lead-lt-modal__backdrop" data-mk-lt-close="1"></div>
				<div class="mk-lead-lt-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mk-ld-lt-title">
					<div class="mk-lead-lt-modal__head">
						<h3 id="mk-ld-lt-title">Ghi Last Touch — Call</h3>
						<button type="button" class="mk-lead-lt-modal__x" data-mk-lt-close="1" aria-label="Đóng">&times;</button>
					</div>
					<div class="mk-lead-lt-modal__body">
						<p class="mk-lead-lt-modal__meta" id="mk-ld-lt-meta"></p>
						<label class="mk-lead-lt-modal__label" for="mk-ld-lt-result">Kết quả cuộc gọi</label>
						<select id="mk-ld-lt-result" class="mk-lead-lt-modal__select inputElement">
							<option value="Không nghe máy">Không nghe máy</option>
							<option value="Nghe máy">Nghe máy</option>
						</select>
						<label class="mk-lead-lt-modal__label" for="mk-ld-lt-note">Ghi chú</label>
						<textarea id="mk-ld-lt-note" class="mk-lead-lt-modal__note inputElement" rows="3" placeholder="Ví dụ: Khách quan tâm lớp học"></textarea>
						<p class="mk-lead-lt-modal__tip">Chọn <strong>Nghe máy</strong> sẽ tự chuyển Lead sang Opportunity.</p>
					</div>
					<div class="mk-lead-lt-modal__foot">
						<button type="button" class="btn btn-default" data-mk-lt-close="1">Hủy</button>
						<button type="button" class="btn btn-success" id="mk-ld-lt-save">Lưu cuộc gọi</button>
					</div>
				</div>
			</div>
		</div>

		<div id="mk-ld-ui-panel-detail" class="mk-ld-ui-panel hide">
			<div class="mk-ld-v4-panel">
				<div class="mk-ld-v4-panel__body">
					<div class="mk-lead-detail-details-grid" id="mk-ld-ui-detail-fields"></div>
				</div>
			</div>
		</div>

		<div id="mk-ld-ui-panel-updates" class="mk-ld-ui-panel hide">
			<section class="mk-ld-v4-panel">
				<header class="mk-ld-v4-panel__head">
					<h2 class="mk-ld-v4-panel__title">{vtranslate('LBL_UPDATES', $MODULE_NAME)}</h2>
				</header>
				<div class="mk-ld-v4-panel__body widget_contents" id="mk-ld-ui-updates">
					<div class="noContent"><p>Đang tải lịch sử cập nhật…</p></div>
				</div>
			</section>
		</div>
	</div>
</div>
{/strip}
