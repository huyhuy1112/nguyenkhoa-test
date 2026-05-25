{* Storage list — breadcrumb + hero title + inbound/outbound CTAs *}
{strip}
<div class="mk-gi-header">
	<nav class="mk-gi-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-gi-breadcrumb__list">
			<li class="mk-gi-breadcrumb__item">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=INVENTORY">Home</a>
			</li>
			<li class="mk-gi-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-gi-breadcrumb__item mk-gi-breadcrumb__item--current">
				<span>Storage</span>
			</li>
		</ol>
	</nav>
	<header class="mk-gi-action-header" role="region" aria-label="Storage">
		<div class="mk-gi-action-header__brand">
			<span class="mk-gi-action-header__icon" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='STORAGE'}</span>
			<div class="mk-gi-action-header__text">
				<h1 class="mk-gi-action-header__title">Storage</h1>
				<p class="mk-gi-action-header__subtitle">Authoritative current stock per catalog or legacy identity, derived from Inbound/Outbound.</p>
			</div>
		</div>
		<div class="mk-gi-action-header__actions mk-gi-action-header__actions--dual">
			<a class="mk-gi-btn mk-gi-btn--primary" href="index.php?module=GoodsReceipt&amp;view=Edit&amp;app=INVENTORY">
				<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}</span>
				<span class="mk-gi-btn__txt">Add Inbound</span>
			</a>
			<a class="mk-gi-btn mk-gi-btn--primary" href="index.php?module=GoodsIssue&amp;view=Edit&amp;app=INVENTORY">
				<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/WarehouseListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}</span>
				<span class="mk-gi-btn__txt">Add Outbound</span>
			</a>
		</div>
	</header>
</div>
{/strip}
