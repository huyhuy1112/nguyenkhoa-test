{* Leads Detail — Opp-style layout, hydrated by LeadsDetailUiDemo.js (demo data). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Leads&view=List&app=SALES'}
<nav class="mk-lead-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-lead-detail-breadcrumb__list">
		<li class="mk-lead-detail-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=DashBoard&amp;app=SALES">{vtranslate('LBL_SALES', 'Vtiger')}</a>
		</li>
		<li class="mk-lead-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-lead-detail-breadcrumb__item">
			<a href="{$MK_LIST_URL}">{vtranslate($MODULE_NAME, $MODULE_NAME)}</a>
		</li>
		<li class="mk-lead-detail-breadcrumb__sep" aria-hidden="true">/</li>
		<li class="mk-lead-detail-breadcrumb__item mk-lead-detail-breadcrumb__item--current">
			<span class="mk-lead-detail-breadcrumb__text textOverflowEllipsis" id="mk-ld-ui-crumb-name" title="">—</span>
		</li>
	</ol>
</nav>

<div class="detailview-header-block mk-lead-detail-hero-strip">
	<div class="detailview-header mk-lead-detail-hero">
		<div class="mk-lead-detail-hero__row">
			<div class="mk-lead-detail-hero__left">
				<div class="mk-lead-detail-hero__identity clearfix">
					<div class="mk-lead-detail-hero__icon recordImage bgleads app-SALES">
						<span class="mk-lead-detail-hero__icon-glyph" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='LEAD'}</span>
					</div>
					<div class="mk-lead-detail-hero__text recordBasicInfo">
						<div class="info-row mk-lead-detail-hero__name-row">
							<h1 class="mk-lead-detail-hero__title">
								<span class="recordLabel pushDown" id="mk-ld-ui-title" title="">—</span>
							</h1>
						</div>
						<p class="mk-lead-detail-hero__subtitle" id="mk-ld-ui-subtitle" title="{vtranslate('company', $MODULE_NAME)}"></p>
						<div class="mk-lead-detail-hero__meta" id="mk-ld-ui-meta"></div>
					</div>
				</div>
			</div>
			<div class="detailViewButtoncontainer mk-lead-detail-actions">
				<div class="pull-right btn-toolbar mk-lead-detail-actions__toolbar">
					<div class="btn-group mk-lead-detail-actions__group">
						<button type="button" class="btn btn-default mk-lead-detail-btn mk-lead-detail-btn--ghost" id="mk-ld-ui-follow">
							<span class="mk-lead-detail-btn__ic mk-lead-detail-btn__ic--follow" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='FOLLOW'}</span>
							<span class="mk-lead-detail-btn__txt">{vtranslate('LBL_FOLLOW', $MODULE_NAME)}</span>
						</button>
						<button type="button" class="btn btn-default mk-lead-detail-btn mk-lead-detail-btn--ghost" data-mk-demo-action="edit">
							<span class="mk-lead-detail-btn__ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='EDIT'}</span>
							<span class="mk-lead-detail-btn__txt">{vtranslate('LBL_EDIT', $MODULE_NAME)}</span>
						</button>
						<button type="button" class="btn btn-default mk-lead-detail-btn mk-lead-detail-btn--primary" data-mk-demo-action="email">
							<span class="mk-lead-detail-btn__ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='EMAIL'}</span>
							<span class="mk-lead-detail-btn__txt">{vtranslate('LBL_SEND_EMAIL', $MODULE_NAME)}</span>
						</button>
						<button type="button" class="btn btn-default mk-lead-detail-btn mk-lead-detail-btn--ghost" data-mk-demo-action="convert">
							<span class="mk-lead-detail-btn__ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='CONVERT'}</span>
							<span class="mk-lead-detail-btn__txt">{vtranslate('LBL_CONVERT_LEAD', $MODULE_NAME)}</span>
						</button>
						<button type="button" class="btn btn-default mk-lead-detail-btn mk-lead-detail-btn--ghost dropdown-toggle" data-toggle="dropdown">
							<span class="mk-lead-detail-btn__ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='MORE'}</span>
							<span class="mk-lead-detail-btn__txt">{vtranslate('LBL_MORE', $MODULE_NAME)}</span>
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu dropdown-menu-right">
							<li><a href="javascript:void(0)" data-mk-demo-action="duplicate">{vtranslate('LBL_DUPLICATE', $MODULE_NAME)}</a></li>
							<li><a href="javascript:void(0)" data-mk-demo-action="delete">{vtranslate('LBL_DELETE', $MODULE_NAME)}</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="mk-lead-detail-hero__tags">
			<div class="tagsContainer" id="mk-ld-ui-tags">
				<span class="tagActions btn-group">
					<button type="button" class="btn btn-default mk-lead-detail-btn mk-lead-detail-btn--ghost btn-sm" id="mk-ld-ui-add-tag">
						<i class="fa fa-plus"></i> {vtranslate('LBL_ADD_TAG', $MODULE_NAME)}
					</button>
				</span>
				<span class="tagList" id="mk-ld-ui-tag-list"></span>
			</div>
		</div>
	</div>
</div>

<div class="detailview-content mk-lead-detailview-content">
	<div class="related-tabs row mk-lead-detail-related-tabs" id="mk-ld-ui-related-tabs">
		<nav class="navbar margin0" role="navigation">
			<div class="collapse navbar-collapse in" id="nav-tabs">
				<ul class="nav nav-tabs">
					<li class="tab-item active" data-mk-ui-tab="summary" data-label-key="Summary">
						<a href="javascript:void(0)" class="textOverflowEllipsis"><span class="tab-label"><strong>{vtranslate('LBL_SUMMARY', $MODULE_NAME)}</strong></span></a>
					</li>
					<li class="tab-item" data-mk-ui-tab="detail" data-label-key="Details">
						<a href="javascript:void(0)" class="textOverflowEllipsis"><span class="tab-label"><strong>{vtranslate('LBL_DETAILS', $MODULE_NAME)}</strong></span></a>
					</li>
					<li class="tab-item" data-mk-ui-tab="updates" data-label-key="Updates">
						<a href="javascript:void(0)" class="textOverflowEllipsis"><span class="tab-label"><strong>{vtranslate('LBL_UPDATES', $MODULE_NAME)}</strong></span></a>
					</li>
					<li class="tab-item" data-mk-ui-tab="purchase-history" data-label-key="PurchaseHistory">
						<a href="javascript:void(0)" class="textOverflowEllipsis" title="Lịch sử mua hàng">
							<span class="tab-label"><strong>Lịch sử mua hàng</strong></span>
						</a>
					</li>
					<li class="mk-lead-detail-tabs-spacer" role="presentation" aria-hidden="true"></li>
					<li class="tab-item" data-module="Calendar" data-mk-scroll="activities" title="{vtranslate('LBL_ACTIVITIES', 'Calendar')}">
						<a href="javascript:void(0)" class="textOverflowEllipsis">
							<span class="tab-icon mk-lead-tab-icon">{include file="partials/LeadDetailTabSvgIcon.tpl"|@vtemplate_path:$MODULE MODULE='Calendar'}</span>
							&nbsp;<span class="numberCircle" data-count="0" data-badge="calendar">0</span>
						</a>
					</li>
					<li class="tab-item" data-module="ModComments" data-mk-scroll="comments" title="{vtranslate('ModComments', 'ModComments')}">
						<a href="javascript:void(0)" class="textOverflowEllipsis">
							<span class="tab-icon mk-lead-tab-icon">{include file="partials/LeadDetailTabSvgIcon.tpl"|@vtemplate_path:$MODULE MODULE='ModComments'}</span>
							&nbsp;<span class="numberCircle" data-count="0" data-badge="comments">0</span>
						</a>
					</li>
				</ul>
			</div>
		</nav>
	</div>

	<div class="details mk-lead-detail-details-row">
		<div id="mk-ld-ui-panel-summary" class="mk-ld-ui-panel">
			<form id="detailView" class="clearfix mk-lead-detail-summary-form" method="POST" onsubmit="return false;">
				<div class="col-lg-12 resizable-summary-view mk-lead-detail-summary-col">
					<div class="mk-lead-detail-summary-grid">
						<div class="mk-lead-detail-summary-stack mk-lead-detail-summary-stack--left">
						<section class="mk-lead-detail-card mk-lead-detail-card--key mk-lead-detail-grid__key" id="mk-ld-ui-section-key">
							<div class="mk-lead-detail-card__head">
								<h2 class="mk-lead-detail-card__title">{vtranslate('LBL_KEY_FIELDS', $MODULE_NAME)}</h2>
							</div>
							<div class="summaryView mk-lead-detail-summaryView">
								<div class="summaryViewFields mk-lead-detail-kv-wrap" id="mk-ld-ui-key-fields"></div>
							</div>
						</section>

						<section class="mk-lead-detail-card mk-lead-detail-card--activity-log mk-lead-detail-grid__activity-log" id="mk-ld-ui-section-activity-log">
							<div class="mk-lead-detail-card__head mk-lead-activity-log__head">
								<h2 class="mk-lead-detail-card__title">{vtranslate('LBL_MK_ACTIVITY_LOG', $MODULE_NAME)}</h2>
								<div class="mk-lead-activity-log__head-right">
									<span class="mk-lead-activity-log__count" id="mk-ld-ui-activity-log-count">0 {vtranslate('LBL_MK_ACTIVITY_LOG_ITEMS', $MODULE_NAME)}</span>
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
							</div>
							<div class="mk-lead-activity-log__list" id="mk-ld-ui-activity-log"></div>
						</section>

						<section class="mk-lead-detail-card mk-lead-detail-card--documents mk-lead-detail-grid__documents" id="mk-ld-ui-section-documents">
							<div class="summaryWidgetContainer mk-lead-detail-widget-host">
								<div class="widgetContainer_documents">
									<div class="widget_header clearfix mk-lead-detail-card__head mk-lead-detail-documents__head">
										<h2 class="mk-lead-detail-card__title display-inline-block pull-left">{vtranslate('Documents', $MODULE_NAME)}</h2>
										<div class="pull-right">
											<button type="button" class="btn btn-default dropdown-toggle mk-lead-detail-btn mk-lead-detail-btn--ghost" data-mk-demo-action="new-document">
												<span class="fa fa-plus"></span>&nbsp;{vtranslate('LBL_NEW_DOCUMENT', 'Documents')}&nbsp; <span class="caret"></span>
											</button>
										</div>
									</div>
									<div class="widget_contents mk-lead-detail-documents__body">
										<div class="noContent">
											<p>{vtranslate('LBL_NO_RELATED', $MODULE_NAME)} {vtranslate('SINGLE_Documents', 'Documents')}</p>
										</div>
									</div>
								</div>
							</div>
						</section>
						</div>

						<div class="mk-lead-detail-summary-stack mk-lead-detail-summary-stack--right">
						<section class="mk-lead-detail-card mk-lead-detail-card--purchases mk-lead-detail-grid__purchases" id="mk-ld-ui-section-purchases">
							<div class="mk-lead-detail-card__head">
								<span class="mk-lead-purchase__cart-ic" aria-hidden="true">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-1.5 9h-12L6 6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.5" fill="currentColor"/><circle cx="17" cy="20" r="1.5" fill="currentColor"/></svg>
								</span>
								<h2 class="mk-lead-detail-card__title" id="mk-ld-ui-purchase-title">Lịch sử mua hàng (0)</h2>
							</div>
							<div class="mk-lead-purchase__body" id="mk-ld-ui-purchases"></div>
						</section>

						<section class="mk-lead-detail-card mk-lead-detail-card--activities mk-lead-detail-grid__activities" id="mk-ld-ui-section-activities">
							<div id="relatedActivities" class="mk-lead-detail-related-activities">
								<div class="summaryWidgetContainer">
									<div class="widget_header clearfix">
										<h4 class="display-inline-block pull-left mk-lead-detail-card__title">{vtranslate('LBL_ACTIVITIES', 'Calendar')}</h4>
										<div class="pull-right" style="margin-top: -5px;">
											<button type="button" class="btn addButton btn-sm btn-default mk-lead-detail-btn mk-lead-detail-btn--ghost createActivity toDotask" data-mk-qc="task" title="{vtranslate('LBL_ADD_TASK', 'Calendar')}">
												<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_TASK', 'Calendar')}
											</button>&nbsp;&nbsp;
											<button type="button" class="btn addButton btn-sm btn-default mk-lead-detail-btn mk-lead-detail-btn--ghost createActivity" data-mk-qc="meeting" data-name="Events" title="{vtranslate('LBL_ADD_EVENT', 'Calendar')}">
												<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_EVENT', 'Calendar')}
											</button>
										</div>
									</div>
									<div class="widget_contents" id="mk-ld-ui-activities"></div>
								</div>
							</div>
						</section>

						<section class="mk-lead-detail-card mk-lead-detail-card--comments mk-lead-detail-grid__comments" id="mk-ld-ui-section-comments">
							<div class="summaryWidgetContainer mk-lead-detail-widget-host">
								<div class="widgetContainer_comments">
									<div class="widget_header mk-lead-detail-card__head">
										<h2 class="mk-lead-detail-card__title">{vtranslate('ModComments', 'ModComments')}</h2>
									</div>
									<div class="widget_contents">
										<div class="commentContainer">
											<div class="addCommentBlock">
												<textarea class="commentTextArea" id="mk-ld-ui-comment" rows="5" placeholder="{vtranslate('LBL_POST_YOUR_COMMENT_HERE', 'ModComments')}"></textarea>
												<div class="row" style="margin-top: 8px;">
													<div class="col-lg-12 text-right">
														<button type="button" class="btn btn-success saveComment" id="mk-ld-ui-post-comment">{vtranslate('LBL_POST', 'ModComments')}</button>
													</div>
												</div>
											</div>
											<div class="recentCommentsBody" id="mk-ld-ui-comments-list"></div>
										</div>
									</div>
								</div>
							</div>
						</section>
						</div>
					</div>
				</div>
			</form>
		</div>

		<div id="mk-ld-ui-panel-detail" class="mk-ld-ui-panel hide">
			<div class="mk-lead-detail-card">
				<div class="mk-lead-detail-card__head">
					<h2 class="mk-lead-detail-card__title">{vtranslate('LBL_DETAILS', $MODULE_NAME)}</h2>
				</div>
				<div class="mk-lead-detail-overview-body detailview-table" id="mk-ld-ui-detail-fields"></div>
			</div>
		</div>

		<div id="mk-ld-ui-panel-purchase-history" class="mk-ld-ui-panel hide">
			<div class="mk-lead-detail-card">
				<div class="mk-lead-detail-card__head">
					<h2 class="mk-lead-detail-card__title">Lịch sử mua hàng</h2>
				</div>
				<div class="mk-lead-commerce-tabs" role="tablist" aria-label="Lịch sử mua hàng">
					<button type="button" class="mk-lead-commerce-tabs__btn is-active" data-mk-commerce-tab="orders-month" role="tab" aria-selected="true">Đơn hàng trong 1 tháng</button>
					<button type="button" class="mk-lead-commerce-tabs__btn" data-mk-commerce-tab="products-total" role="tab" aria-selected="false">Tổng sản phẩm mua hàng</button>
				</div>
				<div class="mk-lead-commerce-panels">
					<div class="mk-lead-commerce-panel" id="mk-ld-ui-commerce-orders-month" data-mk-commerce-panel="orders-month" role="tabpanel"></div>
					<div class="mk-lead-commerce-panel hide" id="mk-ld-ui-commerce-products-total" data-mk-commerce-panel="products-total" role="tabpanel"></div>
				</div>
			</div>
		</div>

		<div id="mk-ld-ui-panel-updates" class="mk-ld-ui-panel hide">
			<div class="mk-lead-detail-card">
				<div class="mk-lead-detail-card__head">
					<h2 class="mk-lead-detail-card__title">{vtranslate('LBL_UPDATES', $MODULE_NAME)}</h2>
				</div>
				<div class="widget_contents" id="mk-ld-ui-updates">
					<div class="noContent"><p>Chưa có lịch sử cập nhật (UI demo).</p></div>
				</div>
			</div>
		</div>
	</div>
</div>
{/strip}
