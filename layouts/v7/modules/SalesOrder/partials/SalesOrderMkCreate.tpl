{* Create Sales Order — same shell as Quote create (sticky head + 70/30 grid + right rail). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=SalesOrder&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-qt-create mk-so-create{if $MK_IS_EDIT} mk-qt-create--edit mk-so-create--edit{/if}" id="mkSoCreateWorkspace" data-mk-sales-order-create="1">
	<header class="mk-qt-sticky-head mk-so-sticky-head" id="mkSoStickyHead">
		<div class="mk-qt-sticky-head__inner">
			<div class="mk-qt-sticky-head__left">
				<nav class="mk-qt-sticky-head__crumb" aria-label="Breadcrumb">
					<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
					<span aria-hidden="true">/</span>
					<a href="{$MK_LIST_URL}">{vtranslate('SalesOrder', $MODULE)}</a>
					<span aria-hidden="true">/</span>
					{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
				</nav>
				<div class="mk-qt-sticky-head__title-row">
					{if $MK_IS_EDIT}
						<h1 class="mk-qt-sticky-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_SalesOrder', $MODULE)}</h1>
					{else}
						<h1 class="mk-qt-sticky-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_SalesOrder', $MODULE)}</h1>
					{/if}
					<span class="mk-qt-badge mk-qt-badge--stage mk-so-badge" id="mkSoHeadStageBadge">Draft</span>
				</div>
				<div class="mk-qt-autosave" id="mkSoAutosave" aria-live="polite">
					<span class="mk-qt-autosave__dot" aria-hidden="true"></span>
					<span class="mk-qt-autosave__text">Ready to save</span>
				</div>
			</div>
			<div class="mk-qt-sticky-head__actions">
				<a class="mk-qt-btn mk-qt-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-qt-btn mk-qt-btn--primary" id="mkSoSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-qt-create__grid">
		<div class="mk-qt-create__main">
			<div class="mk-qt-form-host" id="mkSoFormHost">
				{include file="partials/SalesOrderMkInventoryForm.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>

		<aside class="mk-qt-rail" id="mkSoOrderRail" aria-label="Order summary">
			<div class="mk-qt-rail-card mk-qt-rail-card--summary">
				<div class="mk-qt-rail-card__head">
					<span class="mk-qt-rail-card__icon" aria-hidden="true"><i class="fa fa-shopping-cart"></i></span>
					<h2 class="mk-qt-rail-card__title">Tóm tắt đơn hàng</h2>
				</div>
				<dl class="mk-qt-summary-list">
					<div class="mk-qt-summary-list__row">
						<dt>Trạng thái</dt>
						<dd id="mkSoRailStage">—</dd>
					</div>
					<div class="mk-qt-summary-list__row">
						<dt>Ngày hết hạn</dt>
						<dd id="mkSoRailDueDate">—</dd>
					</div>
					<div class="mk-qt-summary-list__row">
						<dt>Tổ chức</dt>
						<dd id="mkSoRailAccount">—</dd>
					</div>
					<div class="mk-qt-summary-list__row">
						<dt>Cơ hội</dt>
						<dd id="mkSoRailOpportunity">—</dd>
					</div>
					<div class="mk-qt-summary-list__row mk-qt-summary-list__row--total">
						<dt>Tổng cộng</dt>
						<dd id="mkSoRailTotal">—</dd>
					</div>
				</dl>
			</div>

			<div class="mk-qt-rail-card">
				<div class="mk-qt-rail-card__head">
					<span class="mk-qt-rail-card__icon" aria-hidden="true"><i class="fa fa-user"></i></span>
					<h2 class="mk-qt-rail-card__title">Phụ trách</h2>
				</div>
				<p class="mk-qt-rail-meta" id="mkSoRailOwner">—</p>
			</div>

			<div class="mk-qt-rail-card mk-qt-rail-card--muted">
				<div class="mk-qt-rail-card__head">
					<span class="mk-qt-rail-card__icon" aria-hidden="true"><i class="fa fa-clock-o"></i></span>
					<h2 class="mk-qt-rail-card__title">Hoạt động</h2>
				</div>
				<p class="mk-qt-rail-placeholder">Timeline hiện sau khi lưu đơn hàng.</p>
			</div>

			<div class="mk-qt-rail-card mk-qt-rail-card--ai">
				<div class="mk-qt-rail-card__head">
					<span class="mk-qt-rail-card__icon" aria-hidden="true"><i class="fa fa-magic"></i></span>
					<h2 class="mk-qt-rail-card__title">Gợi ý</h2>
				</div>
				<ul class="mk-qt-ai-list">
					<li>Thêm sản phẩm để hoàn thiện đơn hàng</li>
					<li>Chọn ngày hết hạn trước khi xuất kho</li>
					<li>Liên kết cơ hội để theo dõi pipeline</li>
				</ul>
				<p class="mk-qt-rail-note">Chỉ là gợi ý giao diện — không tự thay đổi dữ liệu.</p>
			</div>
		</aside>
	</div>
</div>
{/strip}
